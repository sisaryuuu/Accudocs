<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Notification;
use App\Models\IncomingGIPVerificationHistory;
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
        $statusBefore = $dvRequest->status;
        $receivedAt = now();

        // FIXED: Check if it's already received by Incoming-GIP, not just if it's not IMSD_verified
        if ($dvRequest->status === 'Incoming-GIP_received') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been received by Incoming-GIP.'
                ], 422);
            }
            return redirect()->back()->with('error', 'This request has already been received by Incoming-GIP.');
        }

        // FIXED: Check if the request is in a state that can be received
        if ($dvRequest->status !== 'IMSD_verified') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request cannot be received. Current status: ' . $statusBefore
                ], 422);
            }
            return redirect()->back()->with('error', 'This request cannot be received. Current status: ' . $statusBefore);
        }

        // Use database transaction to ensure data consistency
        DB::transaction(function () use ($dvRequest, $statusBefore, $receivedAt) {
            $dvRequest->update([
                'status'           => 'Incoming-GIP_received',
                'accountingunit_id'=> auth()->id(),
                'received_by'      => auth()->id(),
                'received_at'      => $receivedAt,
                'locked_by'        => auth()->id(),
                'locked_at'        => $receivedAt,
            ]);
            
            $this->saveIncomingGIPVerificationHistory(
                $dvRequest,
                'receive',
                'Request officially received by Incoming-GIP',
                [
                    'status_before' => $statusBefore,
                    'status_after' =>'Incoming-GIP_received',
                    'received_by' => auth()->id(),
                    'received_at' => $receivedAt->toISOString()
                ]
            );
            
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
            
            // Trigger Pusher events
            $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'Incoming-GIP_received',
                'remarks'       => 'Request officially received by Incoming-GIP',
                'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);
            
            $this->pusher->trigger('dv-requests-incoming-gip', 'dv-request-received', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'Incoming-GIP_received',
                'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);
        });

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
        \Log::error('Error receiving DV request: ' . $e->getMessage(), [
            'dv_request_id' => $id,
            'user_id' => auth()->id(),
            'exception' => $e->getTraceAsString()
        ]);
        
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the request: ' . $e->getMessage()
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
            $newStatus = 'Incoming-GIP_in_process';

            // 🔒 Validation checks
            if ($statusBefore === $newStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request is already being processed.'
                ], 422);
            }

            if ($statusBefore !== 'Incoming-GIP_received') {
                return response()->json([
                    'success' => false,
                    'message' => 'Request must be received first before processing.'
                ], 422);
            }

            // ✅ Use transaction for atomic operations
            DB::transaction(function () use ($dvRequest, $statusBefore, $newStatus, $startedAt) {

                $dvRequest->update([
                    'status'    => $newStatus,
                    'locked_by' => auth()->id(),
                    'locked_at' => $startedAt,
                ]);

                // 📝 Save verification history
                $this->saveIncomingGIPVerificationHistory(
                    $dvRequest,
                    'process',
                    'Processing started by Incoming-GIP',
                    [
                        'status_before' => $statusBefore,
                        'status_after'  => $newStatus,
                        'processed_by'  => auth()->id(),
                        'processed_at'  => $startedAt->toISOString(),
                    ]
                );

                // 🧾 Log action
                $this->logAction(
                    $dvRequest->id,
                    'Incoming',
                    $newStatus,
                    'Processing started by Incoming-GIP'
                );

                // 🔔 Create notification
                Notification::create([
                    'focalperson_id' => $dvRequest->focalperson_id,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $newStatus,
                    'action'         => 'Processing Started',
                    'remarks'        => 'Request is now being processed by Incoming-GIP',
                ]);

                // 📢 Pusher events
                if (isset($this->pusher)) {
                    $timestamp = $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A');

                    $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'status-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => $newStatus,
                        'remarks'       => 'Request is now being processed by Incoming-GIP',
                        'timestamp'     => $timestamp,
                    ]);

                    $this->pusher->trigger('dv-requests-incoming-gip', 'dv-request-processing', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => $newStatus,
                        'timestamp'     => $timestamp,
                    ]);
                }
            });

            // ✅ Success response
            return response()->json([
                'success' => true,
                'message' => 'Processing started successfully.',
                'data' => [
                    'id'         => $dvRequest->id,
                    'status'     => $newStatus,
                    'started_at' => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error starting processing: ' . $e->getMessage(), [
                'dv_request_id' => $id,
                'user_id' => auth()->id(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while starting processing.'
            ], 500);
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
    try {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dvRequest = DvRequest::whereHas('program', fn($q) => 
            $q->where('prcode', 'GIP')
        )->findOrFail($id);

        $statusBefore = $dvRequest->status;
        $verifiedAt = now();
        $newStatus = 'Incoming-GIP_verified';

        // 🔒 Validation checks
        if ($statusBefore === $newStatus) {
            return redirect()->back()->with('error', 'This request has already been verified.');
        }

        if (!in_array($statusBefore, ['Incoming-GIP_in_process', 'Incoming-GIP_received'])) {
            return redirect()->back()->with('error', 'Request must be processed or received first before verification.');
        }

        // ✅ Transaction for data integrity
        DB::transaction(function () use ($dvRequest, $statusBefore, $verifiedAt, $request, $newStatus) {

            // Update status
            $dvRequest->update([
                'status'    => $newStatus,
                'locked_by' => null,
                'locked_at' => null,
            ]);

            // ✅ Update attachment checks
            $checkedAttachments = $request->input('gip_attachments', []);
            $attachmentData = [];

            foreach ($dvRequest->gipAttachments as $attachment) {
                $isPresent = isset($checkedAttachments[$attachment->id]);
                $attachment->update(['is_present' => $isPresent]);

                $attachmentData[] = [
                    'attachment_id' => $attachment->id,
                    'is_present'    => $isPresent,
                ];
            }

            // 📝 Save verification history
            $this->saveIncomingGIPVerificationHistory(
                $dvRequest,
                'verify',
                $request->remarks ?? 'Request verified by Incoming-GIP',
                [
                    'status_before'        => $statusBefore,
                    'status_after'         => $newStatus,
                    'attachments_checked'  => $attachmentData,
                    'verified_by'          => auth()->id(),
                    'verified_at'          => $verifiedAt->toISOString(),
                ]
            );

            // 🧾 Log action
            $this->logAction(
                $dvRequest->id,
                'Incoming',
                $newStatus,
                $request->remarks ?? 'Request verified by Incoming-GIP'
            );

            // 🔔 Notify focal person
            Notification::create([
                'focalperson_id' => $dvRequest->focalperson_id,
                'dv_request_id'  => $dvRequest->id,
                'status'         => $newStatus,
                'action'         => 'Verified',
                'remarks'        => $request->remarks ?? 'Request verified by Incoming-GIP',
            ]);

            // 📢 Pusher notifications
            if (isset($this->pusher)) {
                $timestamp = $verifiedAt->timezone('Asia/Manila')->format('M d, Y h:i A');
                $focalId = $dvRequest->focalperson_id;
                $remarks = $request->remarks ?? 'Request verified by Incoming-GIP';

                // To Budget Officer
                $this->pusher->trigger('dv-requests-budget-officer', 'dv-request-verified', [
                    'dv_request_id' => $dvRequest->id,
                    'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                    'program'       => $dvRequest->program->prcode ?? 'N/A',
                    'status'        => $newStatus,
                    'timestamp'     => $timestamp,
                ]);

                // To Focal Person
                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => $newStatus,
                    'remarks'       => $remarks,
                    'timestamp'     => $timestamp,
                ]);

                // To Incoming-GIP group
                $this->pusher->trigger('dv-requests-incoming-gip', 'dv-request-verified', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => $newStatus,
                    'timestamp'     => $timestamp,
                ]);
            }
        });

        // ✅ Success redirect
        return redirect()
            ->route('Incoming.GIP.index')
            ->with('success', 'DV Request has been verified successfully.');

    } catch (\Exception $e) {
        \Log::error('Error verifying DV request: ' . $e->getMessage(), [
            'dv_request_id' => $id,
            'user_id'       => auth()->id(),
            'file'          => $e->getFile(),
            'line'          => $e->getLine(),
        ]);

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'An error occurred while verifying the request.');
    }
}

       public function reject(Request $request, $id)
{
    try {
        // Validate remarks
        $request->validate([
            'remarks' => 'required|string|max:1000|min:10',
        ]);

        // Find DV request under GIP program
        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
                              ->findOrFail($id);

        $statusBefore = $dvRequest->status;

        // Restrict rejection to allowed statuses
        if (!in_array($dvRequest->status, ['Incoming-GIP_received', 'Incoming-GIP_in_process'])) {
            return response()->json([
                'success' => false,
                'message' => 'This DV request cannot be rejected at its current stage.'
            ], 422);
        }

        $rejectedAt = now();
        $focalId = $dvRequest->focalperson_id;
        $remarks = trim($request->remarks);

        // ✅ Database transaction for consistency
        DB::transaction(function () use ($dvRequest, $statusBefore, $rejectedAt, $remarks, $focalId) {

            // Update DV request status
            $dvRequest->update([
                'status'    => 'Incoming-GIP_rejected',
                'locked_by' => null,
                'locked_at' => null,
            ]);

            // Save verification history
            $this->saveIncomingGIPVerificationHistory(
                $dvRequest,
                'reject',
                $remarks,
                [
                    'status_before'   => $statusBefore,
                    'status_after'    => 'Incoming-GIP_rejected',
                    'action'          => 'rejected',
                    'rejected_by'     => auth()->id(),
                    'rejected_at'     => $rejectedAt->toISOString(),
                    'rejection_reason'=> $remarks,
                ]
            );

            // Log rejection
            $this->logAction(
                $dvRequest->id,
                'Incoming-GIP',
                'Incoming-GIP_rejected',
                "Rejected by Incoming-GIP: {$remarks}"
            );

            // Create notification
            Notification::create([
                'focalperson_id' => $focalId,
                'dv_request_id'  => $dvRequest->id,
                'status'         => 'Incoming-GIP_rejected',
                'action'         => 'Rejected',
                'remarks'        => $remarks,
            ]);

            // 🔔 Trigger real-time updates
            if (isset($this->pusher)) {
                $timestamp = $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A');

                // Notify focal person
                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => 'Incoming-GIP_rejected',
                    'remarks'       => $remarks,
                    'timestamp'     => $timestamp,
                ]);

                // Notify Incoming-GIP dashboard
                $this->pusher->trigger('dv-requests-incoming-gip', 'dv-request-rejected', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => 'Incoming-GIP_rejected',
                    'timestamp'     => $timestamp,
                ]);
            }
        });

        // ✅ Return success JSON
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
        $verificationHistory = IncomingGIPVerificationHistory::with([
            'dvRequest.program',
            'dvRequest.focalPerson',
                    'dvRequest.respocenter',
                    'verifier'
                ])
                ->where('verified_by', auth()->id())
                ->orderBy('verified_at', 'desc')
                ->paginate(20);

                return view('Incoming.GIP.loghistory', compact('verificationHistory'));
        }

        protected function saveIncomingGIPVerificationHistory($dvRequest, $step, $remarks = null, array $data = [])
        {
            try {
                IncomingGIPVerificationHistory::create([
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



