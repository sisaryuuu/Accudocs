<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Notification;
use App\Services\PusherService;
use App\Models\GIPAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GIPDvRequestProcessingController extends Controller
{
     protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

      public function index(Request $request)
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $query = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
            ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_in_process','Incoming-GIP_received'])
            ->where(function ($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by')
                ->orWhere('locked_by', $currentUserId)
                ->orWhere('locked_at', '<=', $lockDuration);
            });

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->whereHas('program', fn($p) => $p->where('prcode', 'like', "%{$search}%"))
                  ->orWhereHas('focalPerson', fn($fp) =>
                      $fp->where('fname', 'like', "%{$search}%")
                         ->orWhere('lname', 'like', "%{$search}%")
                  )
                  ->orWhereHas('respocenter', fn($r) => $r->where('rccode', 'like', "%{$search}%"))
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $dvRequests = $query->orderBy('created_at', 'desc')->paginate(10);
          $sidebarCounts = $this->getSidebarCounts();   
        return view('Incoming.GIP.index', compact('dvRequests','sidebarCounts'));
    }
    
    public function show($id)
{
    $dvRequest = DvRequest::with(['accountingUnit','program','gipAttachments','logs'])
                        ->whereHas('program', fn($q) => 
                            $q->where('prcode', 'GIP')) 
                        ->findOrFail($id);

    $lockDuration   = now()->subMinutes(10);
    $currentUserId  = auth()->id();

    // Lock handling
    if ($dvRequest->locked_by && $dvRequest->locked_by !== $currentUserId && $dvRequest->locked_at > $lockDuration) {
        return redirect()->route('Incoming.GIP.index')
            ->with('error', 'This DV Request is currently being processed by another user.');
    }

    if (!$dvRequest->locked_by || $dvRequest->locked_at <= $lockDuration) {
        $dvRequest->update([
            'locked_by' => $currentUserId,
            'locked_at' => now(),
        ]);
    }

    // ✅ Do NOT update status here — handled in receive()

    // Get last log step
    $lastLog  = $dvRequest->logs()->latest()->first();
    $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

    // Fetch recent DV requests
    $recentDvRequests = DvRequest::whereHas('program', fn($q) => 
                                    $q->where('prcode', 'GIP')) 
                                ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_in_process', 'Incoming-GIP_received'])
                                ->whereNotNull('focalperson_id')
                                ->latest()
                                ->take(5)
                                ->get();

    return view('Incoming.GIP.verifygipdv', compact('dvRequest', 'recentDvRequests', 'lastStep'));
}

 public function receive(Request $request, $id)
{
    try {
        $dvRequest = DvRequest::findOrFail($id);
        
        if ($dvRequest->status !== 'IMSD_verified') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been received.'
            ], 422);
        }
        
        $receivedAt = now();
        
        $dvRequest->update([
            'status'           => 'Incoming-GIP_received',
            'accountingunit_id'=> auth()->id(),
            'received_by'      => auth()->id(),
            'received_at'      => $receivedAt,
            'locked_by'        => auth()->id(),
            'locked_at'        => $receivedAt,
        ]);
        $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Incoming-GIP_received',
            'Request officially received by Incoming-GIP'
        );
        
       
        $focalId = $dvRequest->focalperson_id;
        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => 'Incoming-GIP_received',
            'action'         => 'Received',
            'remarks'        => 'Request officially received by Incoming-GIP',
        ]);
        
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'Incoming-GIP_received',
            'remarks'       => 'Request officially received by Incoming-GIP',
            'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
        
       
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document has been received by  Incoming-GIP.',
                'data' => [
                    'id' => $dvRequest->id,
                    'status' => 'Incoming-GIP_received',
                    'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                ]
            ]);
        }
        
        // Fallback for non-AJAX requests (if needed)
        return redirect()->route('Incoming.GIP.index')
            ->with('success', 'Document has been received by Incoming-GIP.');
            
    } catch (\Exception $e) {
        // Log the error
        \Log::error('Error receiving DV request: ' . $e->getMessage());
        
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the request.'
            ], 500);
        }
        
        return redirect()->back()->with('error', 'An error occurred while processing the request.');
    }
}
 public function fetchgipRecent()
    {
         $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);
        
        $recentDvRequests = DvRequest::with('focalPerson')
                                ->whereHas('program', fn($q) => $q->where('prcode', 'GIP')) 
                                ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_in_process','Incoming-GIP_received'])
                                ->whereNotNull('focalperson_id')
                                ->where(function ($q) use ($currentUserId,$lockDuration){
                                    $q->whereNull('locked_by')
                                    ->orWhere('locked_by',$currentUserId)
                                    ->orWhere('locked_at','<=',$lockDuration);
                                })
                                ->latest()
                                ->take(5)
                                ->get();


        return response()->json($recentDvRequests);
    }

     public function fetchAll(Request $request)
    {
         $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $query = DvRequest::with(['focalPerson', 'program', 'respocenter'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
            ->whereIn('status', ['IMSD_verified','Incoming-GIP_in_process','Incoming-GIP_received'])
            ->where(function($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by')
                ->orWhere('locked_by', $currentUserId)
                ->orWhere('locked_at','<=',$lockDuration);
            });

        // 🔍 Apply search filter for AJAX
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->whereHas('program', fn($p) => $p->where('prcode', 'like', "%{$search}%"))
                  ->orWhereHas('focalPerson', fn($fp) =>
                      $fp->where('fname', 'like', "%{$search}%")
                         ->orWhere('lname', 'like', "%{$search}%")
                  )
                  ->orWhereHas('respocenter', fn($r) => $r->where('rccode', 'like', "%{$search}%"))
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $dvRequests = $query->latest()->get();

        $result = $dvRequests->map(fn($request) => [
            'id'           => $request->id,
            'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
            'program'      => $request->program->prcode ?? 'N/A',
            'respocenter'  => trim(($request->respocenter->rccode ?? '') . ' - ' . ($request->respocenter->description ?? '')) ?? 'N/A',
            'amount'       => number_format($request->amount, 2),
            'status'       => $request->status,
            'date'         => $request->created_at->format('M d, Y h:i A'),
        ]);

        return response()->json($result);
    }

    public function fetch($id)
    {
        $dvRequest = DvRequest::with(['focalPerson', 'program','respocenter','accountingUnit'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
             ->where(function($q) use ($currentUserId,$lockDuration){
                $q->whereNull('locked_by')
                ->orWhere('locked_by',$currentUserId)
                ->orwhere('locked_at','<=',$lockDuration);
            })
            ->findOrFail($id);
        
        return response()->json([
            'id'           => $dvRequest->id,
            'dv_number'    => $dvRequest->dv_number ?? '—',
            'title'        => $dvRequest->particulars ?? 'No Title',
            'status'       => $dvRequest->status,
            'amount'       => number_format($dvRequest->amount, 2),
            'submitted_by' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
            'program'      => $dvRequest->program->prcode ?? 'N/A',
            'accountingunit' => trim(($dvRequest->accountingUnit->fname ?? '') . ' ' . ($dvRequest->accountingUnit->lname ?? '')),  
            'respocenter'  => $dvRequest->respocenter->rccode ?? 'N/A',
            'date'         => $dvRequest->created_at->format('M d, Y h:i A'),
        ]);
    }

    public function verify(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP')) 
                              ->findOrFail($id);

        $dvRequest->update([
        'status' => 'Incoming-GIP_verified',
        'locked_by'=> null,
        'locked_at'=> null,
        ]);

        $checkedAttachments = $request->input('attachments', []);
        foreach ($dvRequest->gipAttachments as $attachment) {
        $attachment->is_present = isset($checkedAttachments[$attachment->name]);
        $attachment->save();
    }

        $focalId = $dvRequest->focalperson_id;
        $status = 'Incoming-GIP_verified';
        $action = 'Approved';
        $remarks = $request->remarks ?? 'Request rejected by Incoming-TUPAD';

            $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'IMSD_verified',
            'remarks'       => $request->remarks ?? 'Request verified by IMSD',
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);

      $notification = Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $status,
                    'action'         => $action,
                    'remarks'        => $remarks,
                ]);
     $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Approved',
            $request->remarks ?? 'Request verified by Incoming-GIP'
        );

            
        return redirect()->route('Incoming.GIP.index')
                         ->with('success', 'DV Request has been verified successfully.');
    }
        public function reject(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
                            ->findOrFail($id);

        // Update status to rejected
         $dvRequest->update([
        'status' => 'Incoming-GIP_rejected',
        'locked_by'=> null,
        'locked_at'=> null,
        ]);


        // Log the rejection
        $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Rejected',
            $request->remarks ?? 'Request rejected by Incoming-GIP'
        );

        return redirect()->route('Incoming.GIP.index')
                        ->with('error', 'DV Request has been rejected.');
    }

    
   public function redirectToFirstPending()
{
    $currentUserId = auth()->id();
    $lockDuration = now()->subMinutes(10);

    // Get all pending GIP requests
    $pendingRequests = DvRequest::whereHas('program', fn($q) => 
                                $q->where('prcode', 'GIP')) 
                            ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_in_process','Incoming-GIP_received'])
                            ->orderBy('created_at', 'asc')
                            ->get();

    if ($pendingRequests->isEmpty()) {
        return redirect()->route('Incoming.GIP.index')
                         ->with('info', 'No GIP DV requests pending verification.');
    }

    // Check for one you can work on (unlocked or locked by you, or expired lock)
    $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
        return !$req->locked_by 
            || $req->locked_by == $currentUserId 
            || $req->locked_at <= $lockDuration;
    });

    if ($available) {
        return redirect()->route('Incoming.GIP.dvrequests.withid', $available->id);
    }

    // If all are locked by others
    return redirect()->route('Incoming.GIP.index')
                     ->with('info', 'All pending GIP DV requests are currently locked by other users.');
}


      public function releaseLock($id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->locked_by === auth()->id()) {
            $dvRequest->update([
                'locked_by' => null,
                'locked_at' => null,
                'received_by'      => null,
                'received_at'      => null,
                'status' => 'IMSD_verified'
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function refreshLock(Request $request, $id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->locked_by !== auth()->id()) {
            return response()->json(['error' => 'Not authorized to refresh lock'], 403);
        }

        $dvRequest->update([
            'locked_at' => now(), // refresh timestamp
        ]);

        return response()->json(['success' => true, 'locked_at' => $dvRequest->locked_at]);
    }



public function history()
{
    $dvRequests = DvRequest::with(['program', 'focalPerson', 'respocenter', 'accountingUnit'])
        ->whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
        ->whereIn('status', ['Incoming-GIP_verified','Incoming-GIP_rejected']) 
        ->orderBy('updated_at', 'desc')
        ->get();

    return view('Incoming.GIP.loghistory', compact('dvRequests'));
}
  protected function logAction($dvRequestId, $action, $status, $remarks = null)
    {
        $logData = [
            'dv_request_id' => $dvRequestId,
            'action'        => $action,
            'status'        => $status,
            'remarks'       => $remarks,
        ];

        // Detect which guard is active
        if (auth()->guard('focal')->check()) {
            $logData['focalperson_id'] = auth()->id();
        } elseif (auth()->guard('web')->check()) {
            $logData['accountingunit_id'] = auth()->id();
        } else {
            $logData['external_actor'] = 'System';
        }

        DvRequestLog::create($logData);
    }


}
