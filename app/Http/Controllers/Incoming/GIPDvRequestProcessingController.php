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
        
        // Notify focal person
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
        
        // ✅ NEW: Broadcast to Incoming-GIP channel for real-time table updates
        $this->pusher->trigger('dv-requests-incoming-gip', 'dv-request-received', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'Incoming-GIP_received',
            'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
            'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
        
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document has been received by Incoming-GIP.',
                'data' => [
                    'id' => $dvRequest->id,
                    'status' => 'Incoming-GIP_received',
                    'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                ]
            ]);
        }
        
        return redirect()->route('Incoming.GIP.index')
            ->with('success', 'Document has been received by Incoming-GIP.');
            
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

        if ($dvRequest->status !== 'Incoming-GIP_received') {
            return response()->json([
                'success' => false,
                'message' => 'Request must be received first before processing.'
            ], 422);
        }

        $startedAt = now();

        $dvRequest->update([
            'status'    => 'Incoming-GIP_in_process',
            'locked_by' => auth()->id(),
            'locked_at' => $startedAt,
        ]);

        $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Incoming-GIP_in_process',
            'Processing started by Incoming-GIP',
        );

        // Notify focal person
        $focalId = $dvRequest->focalperson_id;
        $status  = 'Incoming-GIP_in_process';
        $action  = 'Processing Started';
        $remarks = 'Request is now being processed by Incoming-GIP';

        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => $status,
            'action'         => $action,
            'remarks'        => $remarks,
        ]);

        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
            'remarks'       => $remarks,
            'timestamp'     => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        // ✅ NEW: Broadcast to Incoming-GIP channel
        $this->pusher->trigger('dv-requests-incoming-gip', 'dv-request-processing', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
            'timestamp'     => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

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

        return redirect()->route('Incoming.GIP.index')
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
            'accounting_control_number' => $request->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 5, '0', STR_PAD_LEFT): '—',
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
   public function updateAttachments(Request $request, $id)
    {
        try {
            $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
                                  ->findOrFail($id);

            // Verify the user has permission (locked by them)
            if ($dvRequest->locked_by !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update this request.'
                ], 403);
            }

            // Get checked attachment IDs from request
            $checkedAttachments = $request->input('gip_attachments', []);

            // Update all attachments for this DV request
            foreach ($dvRequest->gipAttachments as $attachment) {
                $attachment->is_present = isset($checkedAttachments[$attachment->id]);
                $attachment->save();
            }

            // Log the update
            $this->logAction(
                $dvRequest->id,
                'Incoming',
                $dvRequest->status,
                'Attachment checklist updated by Incoming-GIP'
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Attachments updated successfully.',
                    'data' => [
                        'updated_count' => $dvRequest->gipAttachments->count(),
                        'present_count' => $dvRequest->gipAttachments->where('is_present', true)->count()
                    ]
                ]);
            }

            return redirect()->back()->with('success', 'Attachments updated successfully.');

        } catch (\Exception $e) {
            \Log::error('Error updating attachments: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating attachments.'
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while updating attachments.');
        }
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
    $this->pusher->trigger('dv-requests-budget-officer', 'dv-request-verified', [
        'dv_request_id' => $dvRequest->id,
        'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
        'program'       => $dvRequest->program->prcode ?? 'N/A',
        'status'        => 'Incoming-GIP_verified',
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);
    $checkedAttachments = $request->input('gip_attachments', []);
    
    foreach ($dvRequest->gipAttachments as $attachment) {
        $attachment->is_present = isset($checkedAttachments[$attachment->id]);
        $attachment->save();
    }

    $focalId = $dvRequest->focalperson_id;
    $status = 'Incoming-GIP_verified';
    $action = 'Approved';
    $remarks = $request->remarks ?? 'Request verified by Incoming-GIP';

    $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
        'dv_request_id' => $dvRequest->id,
        'status'        => 'Incoming-GIP_verified',
        'remarks'       => $request->remarks ?? 'Request verified by Incoming-GIP',
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);

    // ✅ NEW: Broadcast to Incoming-GIP channel
    $this->pusher->trigger('dv-requests-incoming-gip', 'dv-request-verified', [
        'dv_request_id' => $dvRequest->id,
        'status'        => $status,
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);

    Notification::create([
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
    try {
        // Validate input
        $request->validate([
            'remarks' => 'required|string|max:1000|min:10',
        ]);

        // Find the DV request
        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
                              ->findOrFail($id);

        // ✅ Only allow rejection if status is currently in Incoming-GIP stage
        if (!in_array($dvRequest->status, ['Incoming-GIP_received', 'Incoming-GIP_in_process'])) {
            return response()->json([
                'success' => false,
                'message' => 'This DV request cannot be rejected at its current stage.'
            ], 422);
        }

        $rejectedAt = now();
        $focalId = $dvRequest->focalperson_id;
        $remarks = trim($request->remarks);

        // ✅ Update DV request
        $dvRequest->update([
            'status'     => 'Incoming-GIP_rejected',
            'locked_by'  => null,
            'locked_at'  => null,
        ]);

        // ✅ Log rejection
        $this->logAction(
            $dvRequest->id,
            'Incoming-GIP',
            'Incoming-GIP_rejected',
            "Rejected by Incoming-GIP: {$remarks}"
        );

        // ✅ Notify focal person
        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => 'Incoming-GIP_rejected',
            'action'         => 'Rejected',
            'remarks'        => $remarks,
        ]);

        // ✅ Real-time push to focal person
        if (isset($this->pusher)) {
            $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'Incoming-GIP_rejected',
                'remarks'       => $remarks,
                'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            // ✅ Real-time push to Incoming-GIP dashboard
            $this->pusher->trigger('dv-requests-incoming-gip', 'dv-request-rejected', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'Incoming-GIP_rejected',
                'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);
        }

        // ✅ Always return JSON
        return response()->json([
            'success' => true,
            'message' => 'DV request has been rejected successfully.',
            'data' => [
                'id'         => $dvRequest->id,
                'status'     => 'Incoming-GIP_rejected',
                'remarks'    => $remarks,
            ]
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        \Log::error('DV Request not found: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'DV request not found.'
        ], 404);

    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Validation error: ' . json_encode($e->errors()));
        
        return response()->json([
            'success' => false,
            'message' => 'Validation failed: ' . implode(', ', $e->errors()['remarks'] ?? ['Invalid input'])
        ], 422);

    } catch (\Exception $e) {
        \Log::error('Error rejecting GIP DV request: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while rejecting the DV request: ' . $e->getMessage()
        ], 500);
    }
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
