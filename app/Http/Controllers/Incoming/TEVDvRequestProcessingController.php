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

class TEVDvRequestProcessingController extends Controller
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

        $query = DvRequest::whereHas('program', fn($q) =>$q->where('prcode', 'TEV'))
                    ->whereIn('status', ['IMSD_verified', 'Incoming-TEV_in_process','Incoming-TEV_received'])
                    ->where(function ($q) use ($currentUserId,$lockDuration){
                        $q->whereNull('locked_by')
                        ->orWhere('locked_by',$currentUserId)
                        ->orWhere('locked_at','<=',$lockDuration);
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
        return view('Incoming.TEV.index', compact('dvRequests','sidebarCounts'));
    }
    
    public function show($id)
{
    $dvRequest = DvRequest::with(['accountingUnit','program','logs'])
                        ->whereHas('program', fn($q) => 
                            $q->where('prcode', 'TEV')) 
                        ->findOrFail($id);
    $lockDuration = now()->subMinutes(10);
    $currentUserId = auth()->id();

     if ($dvRequest->locked_by && $dvRequest->locked_by !== $currentUserId && $dvRequest->locked_at > $lockDuration) {
        return redirect()->route('Incoming.TEV.index')
            ->with('error', 'This DV Request is currently being processed by another user.');
    }

    if (!$dvRequest->locked_by || $dvRequest->locked_at <= $lockDuration) {
        $dvRequest->update([
            'locked_by' => $currentUserId,
            'locked_at' => now(),
        ]);
    }
    $lastLog = $dvRequest->logs()->latest()->first();
    $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

    return view('Incoming.TEV.verifytevdv', compact('dvRequest', 'lastStep'));
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
            'status'           => 'Incoming-TEV_received',
            'accountingunit_id'=> auth()->id(),
            'received_by'      => auth()->id(),
            'received_at'      => $receivedAt,
            'locked_by'        => auth()->id(),
            'locked_at'        => $receivedAt,
        ]);
        $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Incoming-TEV_received',
            'Request officially received by Incoming-TEV'
        );
        
       
        $focalId = $dvRequest->focalperson_id;
        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => 'Incoming-TEV_received',
            'action'         => 'Received',
            'remarks'        => 'Request officially received by Incoming-TEV',
        ]);
        
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'Incoming-TEV_received',
            'remarks'       => 'Request officially received by Incoming-TEV',
            'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
        
       
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document has been received by  Incoming-TEV.',
                'data' => [
                    'id' => $dvRequest->id,
                    'status' => 'Incoming-TEV_received',
                    'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                ]
            ]);
        }
        
        return redirect()->route('Incoming.TEV.index')
            ->with('success', 'Document has been received by Incoming-TEV.');
            
    } catch (\Exception $e) {
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
public function startProcessing(Request $request, $id)
{
    try {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->status !== 'Incoming-TEV_received') {
            return response()->json([
                'success' => false,
                'message' => 'Request must be received first before processing.'
            ], 422);
        }

        $startedAt = now();

        $dvRequest->update([
            'status'    => 'Incoming-TEV_in_process',
            'locked_by' => auth()->id(),
            'locked_at' => $startedAt,
        ]);

        // Log
        $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Incoming-TEV_in_process',
            'Processing started by Incoming-TEV',
        );

        // Notify focal person
        $focalId = $dvRequest->focalperson_id;
        $status  = 'Incoming-TEV_in_process';
        $action  = 'Processing Started';
        $remarks = 'Request is now being processed by Incoming-TEV';

        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => $status,
            'action'         => $action,
            'remarks'        => $remarks,
        ]);

        // Trigger real-time event
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
            'remarks'       => $remarks,
            'timestamp'     => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        // JSON response for AJAX
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Processing started.',
                'data' => [
                    'id'         => $dvRequest->id,
                    'status'     => $status,
                    'started_at' => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                ]
            ]);
        }

        // Fallback redirect
        return redirect()->route('Incoming.TEV.index')
            ->with('success', 'Processing started.');

    } catch (\Exception $e) {
        \Log::error('Error starting processing: ' . $e->getMessage());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while starting processing.'
            ], 500);
        }

        return redirect()->back()->with('error', 'An error occurred while starting processing.');
    }   
}


 public function fetchtevRecent()
 
    {
          $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);
        $recentDvRequests = DvRequest::with('focalPerson')
        ->whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
        ->whereIn('status', ['IMSD_verified', 'Incoming-TEV_in_process','Incoming-TEV_received'])
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
            ->whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
            ->whereIn('status', ['IMSD_verified','Incoming-TEV_in_process','Incoming-TEV_received'])
            ->where(function($q) use ($currentUserId,$lockDuration){
                $q->whereNull('locked_by')
                ->orWhere('locked_by',$currentUserId)
                ->orwhere('locked_at','<=',$lockDuration);
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

        $dvRequests = $query->latest()->get();

        $result = $dvRequests->map(fn($request) => [
            'id'           => $request->id,
             'accounting_control_number' => $request->accounting_control_number,
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
            ->whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
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

        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TEV')) 
                              ->findOrFail($id);

                              
         DvRequest::where('id', $dvRequest->id)->update([
        'status'     => 'Incoming-TEV_verified',
        'locked_by'  => null,
        'locked_at'  => null,
      ]);
         $focalId = $dvRequest->focalperson_id;
                $status = 'Incoming-TEV_verified';
                $action = 'Approved';
                $remarks = $request->remarks ?? 'DV Request approved forwarded to Budget Officer.';

                $notification = Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $status,
                    'action'         => $action,
                    'remarks'        => $remarks,
                ]);

        if ($dvRequest->focalperson_id) {
            $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status'        =>  $status,
                'remarks'       => 'Request approved by Incoming-TEV',
                'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);
        }
     $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Approved',
            $request->remarks ?? 'DV Request approved forwarded to Budget Officer.'
        );

        return redirect()->route('Incoming.TEV.index', $dvRequest->id)
                         ->with('success', 'DV Request Approved.');
    }
        public function reject(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
                            ->findOrFail($id);

        // Update status to rejected
        $dvRequest->status = 'Incoming-TEV_rejected';
        $dvRequest->save();
        $focalId = $dvRequest->focalperson_id;
                $status = 'Incoming-TEV_rejected';
                $action = 'Rejected';
                $remarks = $request->remarks ?? 'Request rejected by Incoming-TEV';

                $notification = Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $status,
                    'action'         => $action,
                    'remarks'        => $remarks,
                ]);

        if ($dvRequest->focalperson_id) {
            $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status'        =>  $status,
                'remarks'       =>  $remarks,
                'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);
        }
        // Log the rejection
        $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Rejected',
            $request->remarks ?? 'Request rejected by Incoming-TEV'
        );

        return redirect()->route('Incoming.TEV.index')
                        ->with('error', 'DV Request has been rejected.');
    }

    
    public function redirectToFirstPending()
{
    $currentUserId = auth()->id();
    $lockDuration = now()->subMinutes(10);

    // Get all pending GIP requests
    $pendingRequests = DvRequest::whereHas('program', fn($q) => 
                                $q->where('prcode', 'TEV')) 
                            ->whereIn('status', ['IMSD_verified', 'Incoming-TEV_in_process','Incoming-TEV_received'])
                            ->orderBy('created_at', 'asc')
                            ->get();

    if ($pendingRequests->isEmpty()) {
        return redirect()->route('Incoming.TEV.index')
                         ->with('info', 'No TEV DV requests pending verification.');
    }

    // Check for one you can work on (unlocked or locked by you, or expired lock)
    $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
        return !$req->locked_by 
            || $req->locked_by == $currentUserId 
            || $req->locked_at <= $lockDuration;
    });

    if ($available) {
        return redirect()->route('Incoming.TEV.dvrequests.withid', $available->id);
    }

    // If all are locked by others
    return redirect()->route('Incoming.TEV.index')
                     ->with('info', 'All pending TEV DV requests are currently locked by other users.');
}

      public function releaseLock($id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->locked_by === auth()->id()) {
            $dvRequest->update([
                'locked_by' => null,
                'locked_at' => null,
                'status'    => 'IMSD_verified', 
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
        ->whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
        ->where('status', 'Incoming-TEV_verified') 
        ->orderBy('updated_at', 'desc')
        ->get();

    return view('Incoming.TEV.loghistory', compact('dvRequests'));
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
