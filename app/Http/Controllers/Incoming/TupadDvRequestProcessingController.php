<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Notification;
use App\Models\IncomingTUPADVerificationHistory;
use App\Models\TUPADAttachment;
use App\Services\PusherService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TupadDvRequestProcessingController extends Controller
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

        $query = DvRequest::whereHas('program', fn($q) =>$q->where('prcode', 'TUPAD'))
                    ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_in_process','Incoming-TUPAD_received'])
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
        return view('Incoming.TUPAD.index', compact('dvRequests','sidebarCounts'));
    }
 public function show($id)
{
    $dvRequest = DvRequest::with([
      'accountingUnit',
        'program',
        'focalPerson',
        'respocenter',
        'modeOfPayments',
        'paymentDetails', // This will use your hasManyThrough relationship
        'tupadAttachments',
        'logs'
    ])
    ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD')) 
    ->findOrFail($id);
    
    $lockDuration = now()->subMinutes(10);
    $currentUserId = auth()->id();

    if ($dvRequest->locked_by && $dvRequest->locked_by !== $currentUserId && $dvRequest->locked_at > $lockDuration) {
        return redirect()->route('Incoming.TUPAD.index')
            ->with('error', 'This DV Request is currently being processed by another user.');
    }

    if (!$dvRequest->locked_by || $dvRequest->locked_at <= $lockDuration) {
        $dvRequest->update([
            'locked_by' => $currentUserId,
            'locked_at' => now(),
        ]);
    }
    
    // Get attachments for the selected modality only
    $selectedModality = $dvRequest->tupad_modality;
    
   $attachments = collect();
    if ($selectedModality) {
        $attachments = $dvRequest->tupadAttachments()
            ->where('modality', $selectedModality)
            ->orderBy('sort_order')
            ->get();
    }

    $lastLog = $dvRequest->logs()->latest()->first();
    $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

    return view('Incoming.TUPAD.verifytupaddv', compact(
        'dvRequest', 
        'lastStep', 
        'attachments',
        'selectedModality'
    ));
}


 public function receive(Request $request, $id)
{
    try {
        $dvRequest = DvRequest::findOrFail($id);
        $statusBefore = $dvRequest->status;
        $receivedAt = now();
        if ($dvRequest->status !== 'IMSD_verified') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been received.'  
            ], 422);
        }
        
       
        $dvRequest->update([
            'status'           => 'Incoming-TUPAD_received',
            'accountingunit_id'=> auth()->id(),
            'received_by'      => auth()->id(),
            'received_at'      => $receivedAt,
            'locked_by'        => auth()->id(),
            'locked_at'        => $receivedAt,
        ]);

        $this->saveIncomingTUPADVerificationHistory(
            $dvRequest,
            'receive',
            'Request officially received by Incoming-TUPAD',
            [
                'status_before' => $statusBefore,
                'status_after' =>'Incoming-TUPAD_received',
                    'received_by' => auth()->id(),
                    'received_at' => $receivedAt->toISOString()
                ]
            );

        $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Incoming-TUPAD_received',
            'Request officially received by Incoming-TUPAD'
        );
    
        $focalId = $dvRequest->focalperson_id;
        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => 'Incoming-TUPAD_received',
            'action'         => 'Received',
            'remarks'        => 'Request officially received by Incoming-TUPAD',
        ]);
        
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'Incoming-TUPAD_received',
            'remarks'       => 'Request officially received by Incoming-TUPAD',
            'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
        
         // ✅ NEW: Broadcast to Incoming-GIP channel for real-time table updates
        $this->pusher->trigger('dv-requests-incoming-tupad', 'dv-request-received', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'Incoming-TUPAD_received',
            'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
            'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document has been received by  Incoming-TUPAD.',
                'data' => [
                    'id' => $dvRequest->id,
                    'status' => 'Incoming-TUPAD_received',
                    'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                ]
            ]);
        }
        
        return redirect()->route('Incoming.TUPAD.index')
            ->with('success', 'Document has been received by Incoming-TUPAD.');
            
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
         $statusBefore = $dvRequest->status;
            $startedAt = now();
        if ($dvRequest->status !== 'Incoming-TUPAD_received') {
            return response()->json([
                'success' => false,
                'message' => 'Request must be received first before processing.'
            ], 422);
        }

        $startedAt = now();

        $dvRequest->update([
            'status'    => 'Incoming-TUPAD_in_process',
            'locked_by' => auth()->id(),
            'locked_at' => $startedAt,
        ]);
          $this->saveIncomingTUPADVerificationHistory(
                $dvRequest,
                'process',
                'Processing started by Incoming-TUPAD',
                [
                    'status_before' => $statusBefore,
                    'status_after' => 'Incoming-TUPAD_in_process',
                    'processed_by' => auth()->id(),
                    'processed_at' => $startedAt->toISOString()
                ]
            );

        // Log
        $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Incoming-TUPAD_in_process',
            'Processing started by Incoming-TUPAD',
        );

        // Notify focal person
        $focalId = $dvRequest->focalperson_id;
        $status  = 'Incoming-TUPAD_in_process';
        $action  = 'Processing Started';
        $remarks = 'Request is now being processed by Incoming-TUPAD';

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

         $this->pusher->trigger('dv-requests-incoming-tupad', 'dv-request-processing', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
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
        return redirect()->route('Incoming.TUPAD.index')
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


 public function fetchtupadRecent()
 
    {
          $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);
        $recentDvRequests = DvRequest::with('focalPerson')
        ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
        ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_in_process','Incoming-TUPAD_received'])
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
            ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
            ->whereIn('status', ['IMSD_verified','Incoming-TUPAD_in_process','Incoming-TUPAD_received'])
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
            ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
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
        'attachments' => 'array',
    ]);

    $dvRequest = DvRequest::with('tupadAttachments')
        ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD')) 
        ->findOrFail($id);
    $statusBefore = $dvRequest->status;

    $verifiedAt = now();
    $attachmentData = [];
    
    // Update TUPAD attachments if any were checked/unchecked
    if ($request->has('attachments') && $dvRequest->tupad_modality) {
        // Mark submitted attachments as present
        foreach ($request->attachments as $attachmentId => $value) {
            $attachment = $dvRequest->tupadAttachments()->find($attachmentId);
            if ($attachment) {
                $isPresent = (bool)$value; // Fix: define $isPresent
                $attachment->update(['is_present' => $isPresent]);

                $attachmentData[] = [
                    'id' => $attachment->id,
                    'name' => $attachment->name,
                    'is_present' => $isPresent,
                    'modality' => $attachment->modality,
                    'type' => 'tupad' // Changed from 'tev' to 'tupad'
                ];
            }
        }
        
        // Uncheck attachments that weren't submitted
        $submittedIds = array_keys($request->attachments);
        $dvRequest->tupadAttachments()
            ->where('modality', $dvRequest->tupad_modality)
            ->whereNotIn('id', $submittedIds)
            ->update(['is_present' => false]);
    }

    // Update status
    $dvRequest->update([ // Use the model instance instead of query builder
        'status'     => 'Incoming-TUPAD_verified',
        'locked_by'  => null,
        'locked_at'  => null,
    ]);
    
     $this->pusher->trigger('dv-requests-budget-officer', 'dv-request-verified', [
        'dv_request_id' => $dvRequest->id,
        'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
        'program'       => $dvRequest->program->prcode ?? 'N/A',
        'status'        => 'Incoming-TUPAD_verified',
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);
    $attachmentData = [];
    $focalId = $dvRequest->focalperson_id;
    $status = 'Incoming-TUPAD_verified';
    $action = 'Approved';
    $remarks = $request->remarks ?? 'DV Request approved forwarded to Budget Officer.';

    Notification::create([
        'focalperson_id' => $focalId,
        'dv_request_id'  => $dvRequest->id,
        'status'         => $status,
        'action'         => $action,
        'remarks'        => $remarks,
    ]);

    if ($dvRequest->focalperson_id) {
        $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
            'remarks'       => 'Request approved by Incoming for TUPAD processing',
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
    }
    
    $this->pusher->trigger('dv-requests-incoming-tupad', 'dv-request-verified', [
        'dv_request_id' => $dvRequest->id,
        'status'        => $status,
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);
  $attachmentData = [];

   $this->saveIncomingTUPADVerificationHistory(
            $dvRequest,
            'verify',
            $request->remarks ?? 'Request verified by Incoming-TUPAD',
            [
                'action' => 'verified',
                'status_before' => $statusBefore,
                'status_after'  => 'Incoming-TUPAD_verified',
                'attachments_checked' => $attachmentData,
                'verified_by' => auth()->id(),
                'verified_at' => $verifiedAt->toISOString()
            ]
        );

    $this->logAction(
        $dvRequest->id,
        'Incoming',
        'Approved',
        $request->remarks ?? 'DV Request approved forwarded to Budget Officer.'
    );

    return redirect()->route('Incoming.TUPAD.index')
        ->with('success', 'DV Request Approved.');
}

public function reject(Request $request, $id)
{
    try {
        // Validate input
        $request->validate([
            'remarks' => 'required|string|max:1000|min:10',
        ]);

        // Find the DV request
        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
                              ->findOrFail($id);
    $statusBefore = $dvRequest->status;
        // ✅ Only allow rejection if status is currently in Incoming-TUPAD stage
        if (!in_array($dvRequest->status, ['Incoming-TUPAD_received', 'Incoming-TUPAD_in_process'])) {
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
            'status'     => 'Incoming-TUPAD_rejected',
            'locked_by'  => null,
            'locked_at'  => null,
        ]);
    $this->saveIncomingTUPADVerificationHistory(
                    $dvRequest,
                    'reject',
                    $remarks,
                    [
                        'action' => 'rejected',
                        'status_before' => $statusBefore,
                        'status_after'  => 'Incoming-TUPAD_rejected',
                        'rejected_by' => auth()->id(),
                        'rejected_at' => $rejectedAt->toISOString(),
                        'rejection_reason' => $remarks
                    ]
                );
        // ✅ Log rejection
        $this->logAction(
            $dvRequest->id,
            'Incoming-TUPAD',
            'Incoming-TUPAD_rejected',
            "Rejected by Incoming-TUPAD: {$remarks}"
        );

        // ✅ Notify focal person
        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => 'Incoming-TUPAD_rejected',
            'action'         => 'Rejected',
            'remarks'        => $remarks,
        ]);

        // ✅ Real-time push to focal person
        if (isset($this->pusher)) {
            $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'Incoming-TUPAD_rejected',
                'remarks'       => $remarks,
                'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            // ✅ Real-time push to Incoming-TUPAD dashboard
            $this->pusher->trigger('dv-requests-incoming-tupad', 'dv-request-rejected', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'Incoming-TUPAD_rejected',
                'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);
        }

        // ✅ Always return JSON
        return response()->json([
            'success' => true,
            'message' => 'DV request has been rejected successfully.',
            'data' => [
                'id'         => $dvRequest->id,
                'status'     => 'Incoming-TUPAD_rejected',
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
        \Log::error('Error rejecting TUPAD DV request: ' . $e->getMessage());
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
                                $q->where('prcode', 'TUPAD')) 
                            ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_in_process','Incoming-TUPAD_received'])
                            ->orderBy('created_at', 'asc')
                            ->get();

    if ($pendingRequests->isEmpty()) {
        return redirect()->route('Incoming.TUPAD.index')
                         ->with('info', 'No TUPAD DV requests pending verification.');
    }

    // Check for one you can work on (unlocked or locked by you, or expired lock)
    $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
        return !$req->locked_by 
            || $req->locked_by == $currentUserId 
            || $req->locked_at <= $lockDuration;
    });

    if ($available) {
        return redirect()->route('Incoming.TUPAD.dvrequests.withid', $available->id);
    }

    // If all are locked by others
    return redirect()->route('Incoming.TUPAD.index')
                     ->with('info', 'All pending TUPAD DV requests are currently locked by other users.');
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
        $verificationHistory = IncomingTUPADVerificationHistory::with([
            'dvRequest.program',
            'dvRequest.focalPerson',
                    'dvRequest.respocenter',
                    'verifier'
                ])
                ->where('verified_by', auth()->id())
                ->orderBy('verified_at', 'desc')
                ->paginate(20);

                return view('Incoming.TUPAD.loghistory', compact('verificationHistory'));
        }

 protected function saveIncomingTUPADVerificationHistory($dvRequest, $step, $remarks = null, $data = [])
        {
          try{  IncomingTUPADVerificationHistory::create([
            'dv_request_id'      => $dvRequest->id,
            'verified_by'        => auth()->id(),
            'verification_step'  => $step,
            'status_before'      => $data['status_before'] ?? $dvRequest->getOriginal('status'),
            'status_after'       => $data['status_after'] ?? $dvRequest->status,
            'verification_data'  => $data,
            'remarks'            => $remarks,
            'verified_at'        => now(),
        ]);

    } catch (\Exception $e) {
        \Log::error('Failed to save Incoming-GIP verification history', [
            'dv_request_id' => $dvRequest->id ?? null,
            'step' => $step,
            'error' => $e->getMessage(),
        ]);

        // Optionally, rethrow to ensure rollback inside DB::transaction
        throw $e;
    }
}
  protected function logAction($dvRequestId, $department, $action, $remarks = null)
        {
            try {
                $logData = [
                    'dv_request_id' => $dvRequestId,
                    'action'        => $action,
                    'remarks'       => $remarks,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                // Set the correct user based on who is performing the action
                if (auth()->guard('web')->check()) {
                    // Accounting/Admin user - set accountingunit_id
                    $logData['accountingunit_id'] = auth()->id();
                    // Don't set focalperson_id for accounting users
                } elseif (auth()->guard('focal')->check()) {
                    // Focal person - set focalperson_id
                    $logData['focalperson_id'] = auth()->id();
                } else {
                    // System or external actor
                    $logData['external_actor'] = 'System';
                }

                DvRequestLog::create($logData);

            } catch (\Exception $e) {
                \Log::error('Failed to log action: ' . $e->getMessage(), [
                    'dv_request_id' => $dvRequestId,
                    'action' => $action,
                ]);
                // Don't throw error - just log and continue
            }
        }
}
