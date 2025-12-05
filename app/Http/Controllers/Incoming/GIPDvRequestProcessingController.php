<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use App\Traits\TriggersDashboardUpdate; // ✅ Add trait
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\DvRequestHistory;
use App\Models\Notification;
use App\Models\IncomingGIPVerificationHistory;
use App\Models\DocumentAgingAlert;
use App\Services\PusherService;
use App\Services\SendPulseServices;
use App\Services\IprogSmsService;
use App\Models\GIPAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GIPDvRequestProcessingController extends Controller
{
    use TriggersDashboardUpdate; // ✅ Add trait for dashboard updates
    
    protected PusherService $pusher;
    protected IprogSmsService $smsService;

    public function __construct(PusherService $pusher, IprogSmsService $smsService)
    {
        $this->pusher = $pusher;
        $this->smsService = $smsService;

    }

    public function index(Request $request)
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $query = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
            ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_received'])
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

        return view('Incoming.GIP.index', compact('dvRequests'));
    }
    
          public function show($id)
    {
        $dvRequest = DvRequest::with(['accountingUnit','program','gipAttachments','logs', 'focalPerson', 'respocenter', 'modeOfPayments', 'paymentDetails'])
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
    
        // ✅ FIX: Initialize GIP attachments if they don't exist
        $checklistItems = [
            'EUP Payroll', 'Payroll', 'Pacsval', 'DTR with Accomplishment Report',
            'DTR Log', 'Profile', 'Brgy. Certification', 'TOR/My Grade', 'Insurance',
            'Birth Certificate', 'Internship Agreement', 'Evaluation Form-GIP',
        ];
    
        // Check if any GIP attachments exist for this request
        if ($dvRequest->gipAttachments->count() === 0) {
            foreach ($checklistItems as $item) {
                $dvRequest->gipAttachments()->firstOrCreate(
                    ['name' => $item], 
                   [
                'is_present' => null,  // ✅ Changed from false to null (N/A by default)
                'remarks' => null
                   ]
                );
            }
            // Reload the relationship to get the newly created attachments
            $dvRequest->load('gipAttachments');
        }
    
        // Get last log step
        $lastLog  = $dvRequest->logs()->latest()->first();
        $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;
    
        // Fetch recent DV requests
        $recentDvRequests = DvRequest::whereHas('program', fn($q) => 
                                        $q->where('prcode', 'GIP')) 
                                    ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_received'])
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
            $currentUserId = auth()->id();
    
            // Validation checks
            if ($dvRequest->status === 'Incoming-GIP_received') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This request has already been received by Incoming-GIP.'
                    ], 422);
                }
                return redirect()->back()->with('error', 'This request has already been received by Incoming-GIP.');
            }
    
            if ($dvRequest->status !== 'IMSD_verified') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This request cannot be received. Current status: ' . $statusBefore
                    ], 422);
                }
                return redirect()->back()->with('error', 'This request cannot be received. Current status: ' . $statusBefore);
            }
    
            DB::transaction(function () use ($dvRequest, $statusBefore, $receivedAt, $currentUserId) {
                $dvRequest->update([
                    'status'           => 'Incoming-GIP_received',
                    'accountingunit_id'=> $currentUserId,
                    'received_by'      => $currentUserId,
                    'received_at'      => $receivedAt,
                    'locked_by'        => $currentUserId,
                    'locked_at'        => $receivedAt,
                ]);
                
                // ✅ FIX: Initialize GIP attachments when receiving the document
                $checklistItems = [
                    'EUP Payroll', 'Payroll', 'Pacsval', 'DTR with Accomplishment Report',
                    'DTR Log', 'Profile', 'Brgy. Certification', 'TOR/My Grade', 'Insurance',
                    'Birth Certificate', 'Internship Agreement', 'Evaluation Form-GIP',
                ];
    
                foreach ($checklistItems as $item) {
                    $dvRequest->gipAttachments()->firstOrCreate(
                        ['name' => $item], 
                                [
            'is_present' => null,  // ✅ Changed from false to null (N/A by default)
            'remarks' => null
        ]

                    );
                }
                
                $this->saveIncomingGIPVerificationHistory(
                    $dvRequest,
                    'receive',
                    'Request officially received by Incoming-GIP',
                    [
                        'status_before' => $statusBefore,
                        'status_after' =>'Incoming-GIP_received',
                        'received_by' => $currentUserId,
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
                        'accounting_control_number' => $dvRequest->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) : 'Pending-CN',
                        'status'        => 'Incoming-GIP_received',
                        'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                        'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'action_performer_id' => $currentUserId,
                        'received_by' => auth()->user()->name ?? 'Incoming-GIP User', // Add this
                        // Include the nested structure for compatibility
                        'dv_request' => [
                            'accounting_control_number' => $dvRequest->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) : 'Pending-CN',
                        ]
                    ]);
            });
    
            // ✅ TRIGGER DASHBOARD UPDATE
            $this->triggerDashboardUpdate();
    
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
            Log::error('Error receiving DV request: ' . $e->getMessage(), [
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

    public function fetchgipRecent()
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);
        
        $recentDvRequests = DvRequest::with('focalPerson')
                                ->whereHas('program', fn($q) => $q->where('prcode', 'GIP')) 
                                ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_received'])
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
            ->whereIn('status', ['IMSD_verified','Incoming-GIP_received'])
            ->where(function($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by')
                ->orWhere('locked_by', $currentUserId)
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
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhere('particulars', 'like', "%{$search}%");
            });
        }

        $dvRequests = $query->latest()->get();

        $result = $dvRequests->map(fn($request) => [
            'id'           => $request->id,
            'accounting_control_number' => $request->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT): '—',
            'submitted_by' => trim(($request->payee_fname ?? $request->focalPerson->fname) . ' ' . ($request->payee_lname ??$request->focalPerson->lname)),
            'program'      => $request->program->prcode ?? 'N/A',
            'respocenter'  => trim(($request->respocenter->rccode ?? '') . ' - ' . ($request->respocenter->description ?? '')) ?? 'N/A',
            'particulars' => $request->particulars,
            'amount'       => number_format($request->amount, 2),
            'status'       => $request->status,
            'date'         => $request->created_at->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        return response()->json($result);
    }

    public function fetch($id)
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);
        
        $dvRequest = DvRequest::with(['focalPerson', 'program','respocenter','accountingUnit'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
            ->where(function($q) use ($currentUserId,$lockDuration){
                $q->whereNull('locked_by')
                ->orWhere('locked_by',$currentUserId)
                ->orWhere('locked_at','<=',$lockDuration);
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
    try {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
            'gip_attachment_status_value' => 'required|array',
            'gip_attachment_status_value.*' => 'nullable|in:0,1,null',
            'gip_attachment_remarks' => 'nullable|array',
            'gip_attachment_remarks.*' => 'nullable|string|max:500',
        ]);

        $dvRequest = DvRequest::whereHas('program', fn($q) => 
            $q->where('prcode', 'GIP')
        )->findOrFail($id);

        $statusBefore = $dvRequest->status;
        $verifiedAt = now();
        $newStatus = 'Incoming-GIP_verified';
        $currentUserId = auth()->id();

        // Validation checks
        if ($statusBefore === $newStatus) {
            return redirect()->back()->with('error', 'This request has already been verified.');
        }

        if ($statusBefore !== 'Incoming-GIP_received') {
            return redirect()->back()->with('error', 'Request must be received first before verification.');
        }

        DB::transaction(function () use ($dvRequest, $statusBefore, $verifiedAt, $request, $newStatus, $currentUserId) {
            $dvRequest->update([
                'status'    => $newStatus,
                'locked_by' => null,
                'locked_at' => null,
            ]);

            // ✅ UPDATED: Read from hidden inputs instead of checkbox array
            $attachmentStatuses = $request->input('gip_attachment_status_value', []);
            $attachmentRemarks = $request->input('gip_attachment_remarks', []);
            $attachmentData = [];

            foreach ($dvRequest->gipAttachments as $attachment) {
                $status = $attachmentStatuses[$attachment->id] ?? null;
                
                // Convert status to proper format
                if ($status === 'null' || $status === null) {
                    $isPresent = null;
                } else {
                    $isPresent = (int)$status === 1 ? true : false;
                }

                // Get remarks (only relevant for "Not Present" status)
                $remarks = $attachmentRemarks[$attachment->id] ?? null;
                
                // Special handling for Present status
                if ($isPresent === true) {
                    $remarks = 'The attachment is present';
                }

                // Update attachment
                $attachment->update([
                    'is_present' => $isPresent,
                    'remarks' => $remarks,
                ]);

                // Collect data for history
                $attachmentData[] = [
                    'attachment_id' => $attachment->id,
                    'name'          => $attachment->name,
                    'is_present'    => $isPresent,
                    'status_label'  => $isPresent === true ? 'Present' : ($isPresent === false ? 'Not Present' : 'N/A'),
                    'remarks'       => $remarks,
                ];
            }

            $this->saveIncomingGIPVerificationHistory(
                $dvRequest,
                'verify',
                $request->remarks ?? 'Request verified by Incoming-GIP',
                [
                    'status_before'        => $statusBefore,
                    'status_after'         => $newStatus,
                    'attachments_checked'  => $attachmentData,
                    'verified_by'          => $currentUserId,
                    'verified_at'          => $verifiedAt->toISOString(),
                ]
            );

            $this->logAction(
                $dvRequest->id,
                'Incoming',
                $newStatus,
                $request->remarks ?? 'Request verified by Incoming-GIP'
            );

            Notification::create([
                'focalperson_id' => $dvRequest->focalperson_id,
                'dv_request_id'  => $dvRequest->id,
                'status'         => $newStatus,
                'action'         => 'Verified',
                'remarks'        => $request->remarks ?? 'Request verified by Incoming-GIP',
            ]);

            if (isset($this->pusher)) {
                $timestamp = $verifiedAt->timezone('Asia/Manila')->format('M d, Y h:i A');
                $focalId = $dvRequest->focalperson_id;
                $remarks = $request->remarks ?? 'Request verified by Incoming-GIP';

                $this->pusher->trigger('dv-requests-budget-officer', 'dv-request-verified', [
                    'dv_request_id' => $dvRequest->id,
                    'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                    'program'       => $dvRequest->program->prcode ?? 'N/A',
                    'status'        => $newStatus,
                    'timestamp'     => $timestamp,
                    'action_performer_id' => $currentUserId,
                ]);

                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => $newStatus,
                    'remarks'       => $remarks,
                    'timestamp'     => $timestamp,
                ]);

                $this->pusher->trigger('dv-requests-incoming-gip', 'dv-request-verified', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => $newStatus,
                    'timestamp'     => $timestamp,
                    'action_performer_id' => $currentUserId,
                    'verified_by_user_id' => $currentUserId,
                ]);
            }
        });

        // ✅ TRIGGER DASHBOARD UPDATE
        $this->triggerDashboardUpdate();

        return redirect()
            ->route('Incoming.GIP.index')
            ->with('success', 'DV Request has been verified successfully.');

    } catch (\Exception $e) {
        Log::error('Error verifying DV request: ' . $e->getMessage(), [
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

/**
 * ✅ UPDATED: Handle attachment updates with three-state status and remarks
 */
public function updateAttachments(Request $request, $id)
{
    try {
        $request->validate([
            'gip_attachment_status_value' => 'required|array',
            'gip_attachment_status_value.*' => 'nullable|in:0,1,null',
            'gip_attachment_remarks' => 'nullable|array',
            'gip_attachment_remarks.*' => 'nullable|string|max:500',
        ]);

        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
                              ->findOrFail($id);

        if ($dvRequest->locked_by !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this request.'
            ], 403);
        }

        $attachmentStatuses = $request->input('gip_attachment_status_value', []);
        $attachmentRemarks = $request->input('gip_attachment_remarks', []);
        $updatedCount = 0;
        $presentCount = 0;
        $notPresentCount = 0;
        $naCount = 0;

        foreach ($dvRequest->gipAttachments as $attachment) {
            $status = $attachmentStatuses[$attachment->id] ?? null;
            
            // Convert status to proper format
            if ($status === 'null' || $status === null) {
                $isPresent = null;
                $naCount++;
            } elseif ((int)$status === 1) {
                $isPresent = true;
                $presentCount++;
            } else {
                $isPresent = false;
                $notPresentCount++;
            }

            // Get remarks (only relevant for "Not Present" status)
            $remarks = $attachmentRemarks[$attachment->id] ?? null;
            
            // Special handling for Present status
            if ($isPresent === true) {
                $remarks = 'The attachment is present';
            }

            // Update attachment
            $attachment->update([
                'is_present' => $isPresent,
                'remarks' => $remarks,
            ]);

            $updatedCount++;
        }

        $this->logAction(
            $dvRequest->id,
            'Incoming',
            $dvRequest->status,
            "Attachment checklist updated: {$presentCount} present, {$notPresentCount} not present, {$naCount} N/A"
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Attachments updated successfully.',
                'data' => [
                    'updated_count' => $updatedCount,
                    'present_count' => $presentCount,
                    'not_present_count' => $notPresentCount,
                    'na_count' => $naCount,
                ]
            ]);
        }

        return redirect()->back()->with('success', 'Attachments updated successfully.');

    } catch (\Exception $e) {
        Log::error('Error updating attachments: ' . $e->getMessage(), [
            'dv_request_id' => $id,
            'error' => $e->getTraceAsString()
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating attachments.'
            ], 500);
        }

        return redirect()->back()->with('error', 'An error occurred while updating attachments.');
    }
}
         public function reject(Request $request, $id)
    {
        try {
            $request->validate([
                'remarks' => 'required|string|max:1000|min:10',
            ]);
    
            $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
                                ->findOrFail($id);
    
            $statusBefore = $dvRequest->status;
            $currentUserId = auth()->id();
    
            if ($dvRequest->status !== 'Incoming-GIP_received') {
                return response()->json([
                    'success' => false,
                    'message' => 'This DV request cannot be rejected at its current stage.'
                ], 422);
            }
    
            $rejectedAt = now();
            $focalId = $dvRequest->focalperson_id;
            $remarks = trim($request->remarks);
    
            // ✅ PHASE 1: FAST DATABASE OPERATIONS ONLY
            DB::transaction(function () use ($dvRequest, $statusBefore, $rejectedAt, $remarks, $focalId, $currentUserId) {
                
                // ✅ ARCHIVE TO HISTORY TABLE FIRST
                $this->archiveToHistory($dvRequest, 'Incoming-GIP_rejected', $remarks);
                
                // Update main request
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
                        'rejected_by'     => $currentUserId,
                        'rejected_at'     => $rejectedAt->toISOString(),
                        'rejection_reason'=> $remarks,
                    ]
                );
    
                // Log action
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
    
                // Pusher events
                if (isset($this->pusher)) {
                    $timestamp = $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A');
    
                    $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => 'Incoming-GIP_rejected',
                        'isRejected'    => true,
                        'remarks'       => $remarks,
                        'timestamp'     => $timestamp,
                    ]);
    
                    $this->pusher->trigger('dv-requests-incoming-gip', 'dv-request-rejected', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => 'Incoming-GIP_rejected',
                        'timestamp'     => $timestamp,
                        'action_performer_id' => $currentUserId,
                        'rejected_by_user_id' => $currentUserId,
                    ]);
                }
            });

            // ✅ PHASE 2: SEND NOTIFICATIONS AFTER DB TRANSACTION (NON-BLOCKING)
            $smsSent = $this->sendRejectionSMS($dvRequest, $remarks);
            $emailSent = $this->sendRejectionEmail($dvRequest, $remarks);

            // ✅ TRIGGER DASHBOARD UPDATE
            $this->triggerDashboardUpdate();
    
            // ✅ IMMEDIATE RESPONSE TO USER
            $responseMessage = 'DV request has been rejected successfully.';
            if ($smsSent) {
                $responseMessage .= ' SMS notification sent.';
            } else {
                $responseMessage .= ' SMS will be retried.';
            }
    
            return response()->json([
                'success' => true,
                'message' => $responseMessage,
                'data' => [
                    'id'         => $dvRequest->id,
                    'status'     => 'Incoming-GIP_rejected',
                    'remarks'    => $remarks,
                    'sms_sent'   => $smsSent,
                    'email_sent' => $emailSent
                ]
            ], 200);
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('DV Request not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'DV request not found.'
            ], 404);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()['remarks'] ?? ['Invalid input'])
            ], 422);
    
        } catch (\Exception $e) {
            Log::error('Error rejecting GIP DV request: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the DV request: ' . $e->getMessage()
            ], 500);
        }
    }
    
      protected function sendRejectionSMS($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            // Quick validation
            if (!$focalPerson || empty($focalPerson->contactnumber)) {
                Log::warning('Cannot send rejection SMS: focal person or contact number missing', [
                    'dv_request_id' => $dvRequest->id,
                    'focal_person_id' => $focalPerson->id ?? 'null'
                ]);
                return false;
            }

            $phoneNumber = $focalPerson->contactnumber;
            
            // ⚡ CREATE SHORT MESSAGE - Faster transmission
            $userSequence = $dvRequest->user_sequence ?? 'N/A';
            $truncatedRemarks = strlen($rejectionRemarks) > 80
                ? substr($rejectionRemarks, 0, 77) . '...' 
                : $rejectionRemarks;

            $message = "Hi {$focalPerson->fname}, your DV Request #{$userSequence} has been rejected. Reason: {$truncatedRemarks}. - ACCUDOCS DOLE XI";

            Log::info('Sending GIP rejection SMS', [
                'phone' => $phoneNumber,
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $userSequence
            ]);

            // ⚡ USE QUICK SMS METHOD - Fast timeout
             $result = $this->smsService->sendQuickSMS($phoneNumber, $message);
            
            if ($result['success']) {
                Log::info('GIP rejection SMS sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'phone' => $phoneNumber
                ]);
                return true;
            } else {
                Log::warning('GIP rejection SMS failed - continuing', [
                    'dv_request_id' => $dvRequest->id,
                    'phone' => $phoneNumber
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            // ⚡ SILENT FAILURE - Don't affect user experience
            Log::warning('GIP SMS rejection failed silently: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id
            ]);
            return false;
        }
    }

    /**
     * ✅ SEND REJECTION EMAIL - SendPulse with SMTP fallback
     */
    protected function sendRejectionEmail($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            if (!$focalPerson || empty($focalPerson->email)) {
                Log::warning('Cannot send rejection email: focal person or email missing', [
                    'dv_request_id' => $dvRequest->id,
                    'focal_person_id' => $focalPerson->id ?? 'null'
                ]);
                return false;
            }

            $toEmail = $focalPerson->email;
            $toName = trim($focalPerson->fname . ' ' . $focalPerson->lname);
            
            Log::info('Attempting to send rejection email via SendPulse', [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'dv_request_id' => $dvRequest->id
            ]);

            // Try SendPulse first
            if (class_exists(SendPulseServices::class)) {
                $sendPulseService = new SendPulseServices();
                $result = $sendPulseService->sendRejectionEmail($toEmail, $toName, $dvRequest, $rejectionRemarks);
                
                if ($result) {
                    Log::info('SendPulse rejection email sent successfully', [
                        'dv_request_id' => $dvRequest->id,
                        'to_email' => $toEmail
                    ]);
                    return true;
                }
            }

            // Fallback to SMTP
            return $this->sendFallbackEmail($toEmail, $toName, $dvRequest, $rejectionRemarks);
            
        } catch (\Exception $e) {
            Log::error('SendPulse rejection email failed: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id,
                'exception' => $e->getTraceAsString()
            ]);
            
            // Try fallback
            return $this->sendFallbackEmail($toEmail ?? null, $toName ?? null, $dvRequest, $rejectionRemarks);
        }
    }

    /**
     * ✅ FALLBACK EMAIL - Using Laravel SMTP
     */
    protected function sendFallbackEmail($toEmail, $toName, $dvRequest, $rejectionRemarks)
    {
        try {
            if (!$toEmail) {
                return false;
            }

            Log::info('Using fallback SMTP email');
            
            $subject = "DV Request Rejected - Incoming-GIP - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            $htmlContent = view('emails.dv_rejected', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'rejectionRemarks' => $rejectionRemarks,
                'rejectedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'rejectedBy' => auth()->user()->name ?? 'Incoming-GIP User',
                'department' => 'Incoming-GIP'
            ])->render();

            Mail::send([], [], function ($message) use ($toEmail, $toName, $subject, $htmlContent) {
                $message->to($toEmail, $toName)
                       ->subject($subject)
                       ->from(config('mail.from.address'), config('mail.from.name'))
                       ->html($htmlContent);
            });

            Log::info('Fallback SMTP email sent successfully', ['to' => $toEmail]);
            return true;
            
        } catch (\Exception $e) {
            Log::error('Fallback SMTP also failed: ' . $e->getMessage());
            return false;
        }
    }

    public function redirectToFirstPending()
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $pendingRequests = DvRequest::whereHas('program', fn($q) => 
                                    $q->where('prcode', 'GIP')) 
                                ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_received'])
                                ->orderBy('created_at', 'asc')
                                ->get();

        if ($pendingRequests->isEmpty()) {
            return redirect()->route('Incoming.GIP.index')
                             ->with('info', 'No GIP DV requests pending verification.');
        }

        $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
            return !$req->locked_by 
                || $req->locked_by == $currentUserId 
                || $req->locked_at <= $lockDuration;
        });

        if ($available) {
            return redirect()->route('Incoming.GIP.dvrequests.withid', $available->id);
        }

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
            'locked_at' => now(),
        ]);

        return response()->json(['success' => true, 'locked_at' => $dvRequest->locked_at]);
    }

        public function history(Request $request)
    {
        $query = IncomingGIPVerificationHistory::with([
            'dvRequest.program:id,prcode',
            'dvRequest.focalPerson:id,fname,lname', 
            'dvRequest.respocenter:id,rccode,description'
        ]);
    
        // Search by DV Number
        if ($request->has('search') && $request->search) {
            $query->whereHas('dvRequest', function($q) use ($request) {
                $q->where('accounting_control_number', 'like', '%' . $request->search . '%')
                  ->orWhere('dv_number', 'like', '%' . $request->search . '%');
            });
        }
    
        // Filter by Program
        if ($request->has('program') && $request->program) {
            $query->whereHas('dvRequest.program', function($q) use ($request) {
                $q->where('prcode', $request->program);
            });
        }
    
        // Filter by Verification Step
        if ($request->has('step') && $request->step) {
            $query->where('verification_step', $request->step);
        }
    
        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('verified_at', '>=', $request->date_from);
        }
    
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('verified_at', '<=', $request->date_to);
        }
    
        $verificationHistory = $query->orderBy('verified_at', 'desc')
                                    ->paginate(20)
                                    ->appends($request->all());
    
        return view('Incoming.GIP.loghistory', compact('verificationHistory'));
    }

    /**
     * ✅ ARCHIVE TO HISTORY TABLE
     */
    protected function archiveToHistory(DvRequest $dvRequest, $status, $reason)
    {
        return DvRequestHistory::create([
            'dv_request_id' => $dvRequest->id,
            'reference_number' => $dvRequest->reference_number,
            'dv_number' => $dvRequest->dv_number,
            'ors_number' => $dvRequest->ors_number,
            'user_sequence' => $dvRequest->user_sequence,
            'accounting_control_number' => $dvRequest->accounting_control_number,
            'accountingunit_id' => $dvRequest->accountingunit_id,
            'focalperson_id' => $dvRequest->focalperson_id,
            'program_id' => $dvRequest->program_id,
            'respocenter_id' => $dvRequest->respocenter_id,
            'particulars' => $dvRequest->particulars,
            'amount' => $dvRequest->amount,
            'status' => $status,
            'tupad_modality' => $dvRequest->tupad_modality,
            'tev_modality' => $dvRequest->tev_modality,
            'history_reason' => $reason,
            'moved_to_history_at' => now(),
            'moved_by' => auth()->id(),
        ]);
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
            Log::error('Failed to save Incoming-GIP verification history', [
                'dv_request_id' => $dvRequest->id ?? null,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);

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

            if (auth()->guard('web')->check()) {
                $logData['accountingunit_id'] = auth()->id();
            } elseif (auth()->guard('focal')->check()) {
                $logData['focalperson_id'] = auth()->id();
            } else {
                $logData['external_actor'] = 'System';
            }

            DvRequestLog::create($logData);

        } catch (\Exception $e) {
            Log::error('Failed to log action: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequestId,
                'action' => $action,
            ]);
        }
    }

    /**
     * Helper method to get sidebar counts
     */
}