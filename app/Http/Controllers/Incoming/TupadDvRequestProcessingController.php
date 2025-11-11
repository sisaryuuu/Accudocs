<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use App\Traits\TriggersDashboardUpdate; // ✅ Add trait
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Notification;
use App\Models\IncomingTUPADVerificationHistory;
use App\Models\DocumentAgingAlert;
use App\Models\TUPADAttachment;
use App\Services\PusherService;
use App\Services\SendPulseServices;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TupadDvRequestProcessingController extends Controller
{

     use TriggersDashboardUpdate; 

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
                    ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_received'])
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
        return view('Incoming.TUPAD.index', compact('dvRequests'));
    }
    // ... your existing methods ...

/**
 * Get or create TUPAD attachments for a DV request based on modality
 */
protected function getOrCreateAttachmentsForDvRequest(DvRequest $dvRequest, string $modality)
{
    try {
        // Get existing attachments for this DV request and modality
        $existingAttachments = $dvRequest->tupadAttachments()
            ->where('modality', $modality)
            ->get();

        // If attachments already exist, return them
        if ($existingAttachments->isNotEmpty()) {
            \Log::info('Existing attachments found', [
                'dv_request_id' => $dvRequest->id,
                'modality' => $modality,
                'count' => $existingAttachments->count()
            ]);
            return $existingAttachments;
        }

        // Get master template attachments for this modality
        $masterAttachments = TUPADAttachment::whereNull('dv_request_id')
            ->where('modality', $modality)
            ->get();

        if ($masterAttachments->isEmpty()) {
            \Log::warning('No master attachments found for modality', [
                'modality' => $modality,
                'dv_request_id' => $dvRequest->id
            ]);
            return collect();
        }

        // Create copies of master attachments for this DV request
        $createdAttachments = [];
        foreach ($masterAttachments as $master) {
            $attachment = TUPADAttachment::create([
                'dv_request_id' => $dvRequest->id,
                'name' => $master->name,
                'modality' => $master->modality,
                'is_present' => false, // Default to not present
                'is_required' => $master->is_required,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $createdAttachments[] = $attachment;
        }

        \Log::info('Created new attachments from master templates', [
            'dv_request_id' => $dvRequest->id,
            'modality' => $modality,
            'count' => count($createdAttachments)
        ]);

        return collect($createdAttachments);

    } catch (\Exception $e) {
        \Log::error('Error in getOrCreateAttachmentsForDvRequest', [
            'dv_request_id' => $dvRequest->id,
            'modality' => $modality,
            'error' => $e->getMessage()
        ]);
        return collect();
    }
}

    public function show($id)
    {
        $dvRequest = DvRequest::with([
            'accountingUnit',
            'program',
            'focalPerson',
            'respocenter',
            'modeOfPayments',
            'paymentDetails',
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
    
        // ✅ AUTO-SET DEFAULT MODALITY IF NOT SET
        if (!$dvRequest->tupad_modality) {
            $dvRequest->update([
                'tupad_modality' => 'rpmt',
                'locked_by' => $currentUserId,
                'locked_at' => now(),
            ]);
            $dvRequest->refresh();
        } else {
            if (!$dvRequest->locked_by || $dvRequest->locked_at <= $lockDuration) {
                $dvRequest->update([
                    'locked_by' => $currentUserId,
                    'locked_at' => now(),
                ]);
            }
        }
        
        $selectedModality = $dvRequest->tupad_modality;
        
        // ✅ DEBUG: Log everything
        \Log::info('=== TUPAD ATTACHMENTS DEBUG ===', [
            'dv_request_id' => $dvRequest->id,
            'selected_modality' => $selectedModality,
            'tupad_modality_from_db' => $dvRequest->getRawOriginal('tupad_modality'),
        ]);
        
        // Get or create attachments
        $attachments = $this->getOrCreateAttachmentsForDvRequest($dvRequest, $selectedModality);
        
        // ✅ DEBUG: Log attachment results
        \Log::info('Attachments fetched', [
            'count' => $attachments->count(),
            'names' => $attachments->pluck('name')->toArray(),
            'is_present' => $attachments->pluck('is_present', 'name')->toArray(),
        ]);
    
        // Check if master templates exist
        $masterCount = TUPADAttachment::whereNull('dv_request_id')
            ->where('modality', $selectedModality)
            ->count();
        
        \Log::info('Master templates check', [
            'modality' => $selectedModality,
            'master_count' => $masterCount,
        ]);
    
        if ($attachments->isEmpty()) {
            \Log::error('NO ATTACHMENTS FOUND!', [
                'dv_request_id' => $dvRequest->id,
                'modality' => $selectedModality,
                'master_templates_exist' => $masterCount > 0,
            ]);
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
            $currentUserId = auth()->id();
    
             
            if ($dvRequest->status !== 'IMSD_verified') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been received.'  
                ], 422);
            }
    
            $controlNumber = $dvRequest->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) : 'Unknown';
            $userName = auth()->user()->name ?? 'TUPAD User';
    
            // Update the DV request status first
            $dvRequest->update([
                'status'           => 'Incoming-TUPAD_received',
                'accountingunit_id'=> $currentUserId,
                'received_by'      => $currentUserId,
                'received_at'      => $receivedAt,
                'locked_by'        => $currentUserId,
                'locked_at'        => $receivedAt,
            ]);
    
    // Save history and send notifications
            $this->saveIncomingTUPADVerificationHistory(
                $dvRequest,
                'receive',
                'Request officially received by Incoming-TUPAD',
                [
                    'status_before' => $statusBefore,
                    'status_after' => 'Incoming-TUPAD_received',
                    'received_by' => $currentUserId,
                    'received_at' => $receivedAt->toISOString(),
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
    
            // Pusher notifications
            $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'Incoming-TUPAD_received',
                'remarks'       => 'Request officially received by Incoming-TUPAD',
                'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);
    
            // ✅ FIX: Broadcast to TUPAD channel with proper acknowledged count
            $this->pusher->trigger('dv-requests-incoming-tupad', 'dv-request-received', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'Incoming-TUPAD_received',
                'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                'received_by'   => $userName,
                'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'action_performer_id' => $currentUserId, // ADD THIS
                'received_by_user_id' => $currentUserId, // ADD THIS
            ]);
    
            $this->triggerDashboardUpdate();
    
            // JSON response for AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document has been received by Incoming-TUPAD.',
                    'data' => [
                        'id' => $dvRequest->id,
                        'status' => 'Incoming-TUPAD_received',
                        'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
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

 public function fetchtupadRecent()
 
    {
          $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);
        $recentDvRequests = DvRequest::with('focalPerson')
        ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
        ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_received'])
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
            ->whereIn('status', ['IMSD_verified','Incoming-TUPAD_received'])
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
            'accounting_control_number' => $request->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT): '—',
            'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
            'program'      => $request->program->prcode ?? 'N/A',
            'respocenter'  => trim(($request->respocenter->rccode ?? '') . ' - ' . ($request->respocenter->description ?? '')) ?? 'N/A',
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
        'tupad_modality' => 'sometimes|string',
    ]);

    $dvRequest = DvRequest::with('tupadAttachments')
        ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD')) 
        ->findOrFail($id);
    
    $statusBefore = $dvRequest->status;
    $verifiedAt = now();
    $currentUserId = auth()->id();
    $attachmentData = [];
    
    // ✅ UPDATE MODALITY IF CHANGED
    if ($request->has('tupad_modality') && $request->tupad_modality !== $dvRequest->tupad_modality) {
        $dvRequest->update(['tupad_modality' => $request->tupad_modality]);
        $dvRequest->refresh();
        
        // Ensure attachments exist for new modality
        $this->getOrCreateAttachmentsForDvRequest($dvRequest, $request->tupad_modality);
        $dvRequest->refresh(); // Refresh relationships
    }
    
    // ✅ FIXED: Update TUPAD attachments correctly
    if ($dvRequest->tupad_modality && $request->has('attachments')) {
        // Get all attachments for current modality
        $allAttachments = $dvRequest->tupadAttachments()
            ->where('modality', $dvRequest->tupad_modality)
            ->get();

        foreach ($allAttachments as $attachment) {
            // ✅ FIX: Check if this attachment name exists in the request
            // The form sends attachment names as keys, not IDs
            $isPresent = false;
            
            // Check both possible formats: by name and by ID
            foreach ($request->attachments as $key => $value) {
                if ($key == $attachment->id || $key == $attachment->name) {
                    $isPresent = true;
                    break;
                }
            }
            
            $attachment->update(['is_present' => $isPresent]);

            $attachmentData[] = [
                'id' => $attachment->id,
                'name' => $attachment->name,
                'is_present' => $isPresent,
                'modality' => $attachment->modality,
                'type' => 'tupad'
            ];
            
            \Log::info('TUPAD Attachment updated', [
                'attachment_id' => $attachment->id,
                'attachment_name' => $attachment->name,
                'is_present' => $isPresent,
                'request_attachments' => $request->attachments
            ]);
        }
    }

    // Update status
    $dvRequest->update([
        'status'     => 'Incoming-TUPAD_verified',
        'locked_by'  => null,
        'locked_at'  => null,
    ]);
    
    // Pusher notification to Budget Officer
    $this->pusher->trigger('dv-requests-budget-officer', 'dv-request-verified', [
        'dv_request_id' => $dvRequest->id,
        'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
        'program'       => $dvRequest->program->prcode ?? 'N/A',
        'status'        => 'Incoming-TUPAD_verified',
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);
    
    $focalId = $dvRequest->focalperson_id;
    $status = 'Incoming-TUPAD_verified';
    $action = 'Incoming-TUPAD_verified';
    $remarks = $request->remarks ?? 'DV Request approved forwarded to Budget Officer.';

    // Notification to focal person
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
    
    // Pusher to Incoming-TUPAD dashboard
    $this->pusher->trigger('dv-requests-incoming-tupad', 'dv-request-verified', [
        'dv_request_id' => $dvRequest->id,
        'status'        => $status,
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        'action_performer_id' => $currentUserId,
        'verified_by_user_id' => $currentUserId,
    ]);

      // ✅ FIX: Make sure attachments_checked is properly saved
    $this->saveIncomingTUPADVerificationHistory(
        $dvRequest,
        'verify',
        $request->remarks ?? 'Request verified by Incoming-TUPAD',
        [
            'action' => 'verified',
            'status_before' => $statusBefore,
            'status_after'  => 'Incoming-TUPAD_verified',
            'attachments_checked' => $attachmentData, // ✅ This should now contain the correct data
            'verified_by' => $currentUserId,
            'verified_at' => $verifiedAt->toISOString(),
            'modality' => $dvRequest->tupad_modality // ✅ Also save modality for reference
        ]
    );

    // Log action
    $this->logAction(
        $dvRequest->id,
        'Incoming-TUPAD_verified',
        'Incoming-TUPAD_verified',
        $request->remarks ?? 'DV Request approved forwarded to Budget Officer.'
    );

    $this->triggerDashboardUpdate();
    
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
        $currentUserId = auth()->id();
    
        // ✅ Only allow rejection if status is currently in Incoming-TUPAD stage
        if ($dvRequest->status !== 'Incoming-TUPAD_received') {
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
                        'rejected_by' => $currentUserId,
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

          $emailSent = $this->sendRejectionEmail($dvRequest, $remarks);

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
                'action_performer_id' => $currentUserId, // ADD THIS
                'verified_by_user_id' => $currentUserId, // ADD THIS
            ]);
        }
            $this->triggerDashboardUpdate();

            $responseMessage = $emailSent ?? false
                ? 'DV request has been rejected'
                : 'DV request has been rejected';

        // ✅ Always return JSON
        return response()->json([
            'success' => true,
            'message' => $responseMessage,
            'data' => [
                'id'         => $dvRequest->id,
                'status'     => 'Incoming-TUPAD_rejected',
                'remarks'    => $remarks,
                'email_sent' => $emailSent,
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
            
            $subject = "DV Request Rejected - Incoming-TUPAD - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            $htmlContent = view('emails.dv_rejected', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'rejectionRemarks' => $rejectionRemarks,
                'rejectedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'rejectedBy' => auth()->user()->name ?? 'Incoming-TUPAD User',
                'department' => 'Incoming-TUPAD'
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

    // Get all pending TUPAD requests
    $pendingRequests = DvRequest::whereHas('program', fn($q) => 
                                $q->where('prcode', 'TUPAD')) 
                            ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_received'])
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

    public function history(Request $request)
    {
        $query = IncomingTUPADVerificationHistory::with([
            'dvRequest.program',
            'dvRequest.focalPerson',
            'dvRequest.respocenter',
            'verifier'
        ])
        ->where('verified_by', auth()->id());

        // Search by DV Number
        if ($request->has('search') && $request->search) {
            $query->whereHas('dvRequest', function($q) use ($request) {
                $q->where('accounting_control_number', 'like', '%' . $request->search . '%');
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
        \Log::error('Failed to save Incoming-TUPAD verification history', [
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