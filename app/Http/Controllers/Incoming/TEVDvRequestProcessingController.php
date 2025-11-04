<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use App\Traits\TriggersDashboardUpdate; // ✅ Add trait
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\IncomingTEVVerificationHistory;
use App\Models\DocumentAgingAlert;
use App\Models\Notification;
use App\Services\PusherService;
use App\Services\SendPulseServices;
use App\Models\TEVAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TEVDvRequestProcessingController extends Controller
{
      use TriggersDashboardUpdate; // ✅ This trait provides getDashboardData() and triggerDashboardUpdate()

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
        return view('Incoming.TEV.index', compact('dvRequests'));
    }
    
    public function show($id)
{
    $dvRequest = DvRequest::with([
        'accountingUnit',
        'program',
        'logs',
        'focalPerson',
        'respocenter',
        'modeOfPayments',
        'paymentDetails', // This will use your hasManyThrough relationship
        'tevAttachments',
        'logs'])
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

    $tevSelectedModality= $dvRequest->tev_modality;

    $tevAttachments = collect();
    if($tevSelectedModality){
        $tevAttachments = $dvRequest->tevAttachments()
        ->where('modality', $tevSelectedModality)
        ->orderBy('sort_order')
        ->get();
    }


    $lastLog = $dvRequest->logs()->latest()->first();
    $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

    return view('Incoming.TEV.verifytevdv', compact('dvRequest', 'lastStep','tevAttachments'));
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

        // ✅ DEFINE VARIABLES FOR PUSHER
        $controlNumber = $dvRequest->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 5, '0', STR_PAD_LEFT) : 'Unknown';
        $userName = auth()->user()->name ?? 'TEV User';

        $dvRequest->update([
            'status'           => 'Incoming-TEV_received',
            'accountingunit_id'=> $currentUserId,
            'received_by'      => $currentUserId,
            'received_at'      => $receivedAt,
            'locked_by'        => $currentUserId,
            'locked_at'        => $receivedAt,
        ]);
    
        // Log the action
        $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Incoming-TEV_received',
            'Request officially received by Incoming-TEV'
        );
        
        $this->saveIncomingTEVVerificationHistory(
            $dvRequest,
            'receive',
            'Request officially received by Incoming-TEV',
            [
                'status_before' => $statusBefore,
                'status_after'  => 'Incoming-TEV_received',
                'received_by'   => $currentUserId,
                'received_at'   => $receivedAt->toISOString(),
            ]
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
        
        // ✅ ENHANCED: Broadcast to Incoming-TEV channel with acknowledgment info
        $this->pusher->trigger('dv-requests-incoming-tev', 'dv-request-received', [
            'dv_request_id' => $dvRequest->id,
            'control_number' => $controlNumber,
            'received_by' => $userName,
            'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
            'action_performer_id' => $currentUserId, // ADD THIS
            'received_by_user_id' => $currentUserId, // ADD THIS
        ]);

          $this->triggerDashboardUpdate();
        // JSON response for AJAX

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document has been received by Incoming-TEV.',
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
        $statusBefore = $dvRequest->status;
        $startedAt = now();
        $currentUserId = auth()->id();


        if ($dvRequest->status !== 'Incoming-TEV_received') {
            return response()->json([
                'success' => false,
                'message' => 'Request must be received first before processing.'
            ], 422);
        }

    

        $dvRequest->update([
            'status'    => 'Incoming-TEV_in_process',
            'locked_by' => $currentUserId,
            'locked_at' => $startedAt,
        ]);

      
        // Log
        $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Incoming-TEV_in_process',
            'Processing started by Incoming-TEV',
        );

          $this->saveIncomingTEVVerificationHistory(
            $dvRequest,
            'process',
            'Processing started by Incoming-TEV',
            [
                'status_before' => $statusBefore,
                'status_after'  => 'Incoming-TEV_in_process',
                'processed_by'  => $currentUserId,
                'started_at'    => $startedAt->toISOString()
            ]
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

        $this->pusher->trigger('dv-requests-incoming-tev', 'dv-request-processing', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
            'timestamp'     => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            'action_performer_id' => $currentUserId, // ADD THIS
            'processed_by_user_id' => $currentUserId, // ADD THIS
        ]);
        
          $this->triggerDashboardUpdate();
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
        'tev_attachments' => 'array',
    ]);

    $dvRequest = DvRequest::with('tevAttachments')
        ->whereHas('program', fn($q) => $q->where('prcode', 'TEV')) 
        ->findOrFail($id);
    $statusBefore = $dvRequest->status;
    $verifiedAt = now(); // Define verifiedAt here
    $currentUserId = auth()->id();


    $attachmentData = [];
    if ($dvRequest->tev_modality) {
        // Get all submitted attachment IDs
        $submittedIds = $request->has('tev_attachments') 
            ? array_keys($request->tev_attachments) 
            : [];

        // Update all attachments for this modality AND collect data for history
        $dvRequest->tevAttachments()
            ->where('modality', $dvRequest->tev_modality)
            ->get()
            ->each(function($attachment) use ($submittedIds, &$attachmentData) {
                $isPresent = in_array($attachment->id, $submittedIds);
                
                // Update the attachment
                $attachment->update([
                    'is_present' => $isPresent
                ]);
                
                // Collect attachment data for verification history
                $attachmentData[] = [
                    'id' => $attachment->id,
                    'name' => $attachment->name,
                    'is_present' => $isPresent,
                    'modality' => $attachment->modality,
                    'type' => 'tev'
                ];
            });
    }
                        
    // Update DV request status
    $dvRequest->update([
        'status'     => 'Incoming-TEV_verified',
        'locked_by'  => null,
        'locked_at'  => null,
    ]);

    // Define remarks with proper fallback
    $remarks = $request->remarks ?? 'DV Request approved and forwarded to Budget Officer.';
    $status = 'Incoming-TEV_verified';
    $action = 'Approved';
    $focalId = $dvRequest->focalperson_id;

 
      // ✅ FIXED: Save verification history FIRST with proper data
    $this->saveIncomingTEVVerificationHistory(
        $dvRequest,
        'verify',
        $remarks, // Use the defined remarks variable
        [
            'status_before' => $statusBefore,
            'status_after'  => 'Incoming-TEV_verified',
            'verified_by'   => $currentUserId,
            'attachments_checked' => $attachmentData,
            'modality' => $dvRequest->tev_modality,
            'verified_at' => $verifiedAt->toISOString()
        ]
    );

    // ✅ FIXED: Log action with proper parameters
    $this->logAction(
        $dvRequest->id,
        'Incoming-TEV',     // Department
        'Approved',         // Action
        'Incoming-TEV_verified', // Status
        $remarks            // Remarks
    );

     


    // Create notification for focal person
    Notification::create([
        'focalperson_id' => $focalId,
        'dv_request_id'  => $dvRequest->id,
        'status'         => $status,
        'action'         => $action,
        'remarks'        => $remarks,
    ]);

    // Trigger Pusher notifications
    $this->pusher->trigger('dv-requests-budget-officer', 'dv-request-verified', [
        'dv_request_id' => $dvRequest->id,
        'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
        'program'       => $dvRequest->program->prcode ?? 'N/A',
        'status'        => $status,
        'timestamp'     => $verifiedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        
    ]);

    if ($focalId) {
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
            'isRejected'    => false,
            'remarks'       => $remarks,
            'timestamp'     => $verifiedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
    }

    $this->pusher->trigger('dv-requests-incoming-tev', 'dv-request-verified', [
        'dv_request_id' => $dvRequest->id,
        'status'        => $status,
        'timestamp'     => $verifiedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        'action_performer_id' => $currentUserId, // ADD THIS
        'verified_by_user_id' => $currentUserId, // ADD THIS
    ]);

    $this->triggerDashboardUpdate();

    return redirect()->route('Incoming.TEV.index')
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
            $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
                                ->findOrFail($id);

            $statusBefore = $dvRequest->status;
            $currentUserId = auth()->id();
            // ✅ Only allow rejection if status is currently in Incoming-TEV stage
            if (!in_array($dvRequest->status, ['Incoming-TEV_received', 'Incoming-TEV_in_process'])) {
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
                'status'     => 'Incoming-TEV_rejected',
                'locked_by'  => null,
                'locked_at'  => null,
            ]);


            $this->saveIncomingTEVVerificationHistory(
                    $dvRequest,
                    'reject',
                    $remarks,
                    [
                        'status_before'   => $statusBefore,
                        'status_after'    => 'Incoming-TEV_rejected',
                        'action'          => 'rejected',
                        'rejected_by'     =>  $currentUserId,
                        'rejected_at'     => $rejectedAt->toISOString(),
                        'rejection_reason'=> $remarks,
                    ]
                );


            // ✅ Log rejection
            $this->logAction(
                $dvRequest->id,
                'Incoming-TEV',
                'Incoming-TEV_rejected',
                "Rejected by Incoming-TEV: {$remarks}"
            );

              $emailSent = $this->sendRejectionEmail($dvRequest, $remarks);

            // ✅ Notify focal person
            Notification::create([
                'focalperson_id' => $focalId,
                'dv_request_id'  => $dvRequest->id,
                'status'         => 'Incoming-TEV_rejected',
                'action'         => 'Rejected',
                'remarks'        => $remarks,
            ]);

            // ✅ Real-time push to focal person
            if (isset($this->pusher)) {
                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => 'Incoming-TEV_rejected',
                    'remarks'       => $remarks,
                    'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);

                // ✅ Real-time push to Incoming-TEV dashboard
                $this->pusher->trigger('dv-requests-incoming-tev', 'dv-request-rejected', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => 'Incoming-TEV_rejected',
                    'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'action_performer_id' => $currentUserId, // ADD THIS
                    'rejected_by_user_id' => $currentUserId, // ADD THIS
                ]);
            }

               $this->triggerDashboardUpdate();

            $responseMessage = $emailSent ?? false
                ? 'DV request has been rejected and notification sent.' 
                : 'DV request has been rejected but email notification failed.';


            // ✅ Always return JSON
            return response()->json([
                'success' => true,
                'message' => $responseMessage,
                'data' => [
                    'id'         => $dvRequest->id,
                    'status'     => 'Incoming-TEV_rejected',
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
            \Log::error('Error rejecting TEV DV request: ' . $e->getMessage());
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
            
            $subject = "DV Request Rejected - Incoming-TEV - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            $htmlContent = view('emails.dv_rejected', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'rejectionRemarks' => $rejectionRemarks,
                'rejectedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'rejectedBy' => auth()->user()->name ?? 'Incoming-TEV User',
                'department' => 'Incoming-TEV'
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

    // Get all pending TEV requests
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

        public function history(Request $request)
        {
            $query = IncomingTEVVerificationHistory::with([
                'dvRequest.program',
                'dvRequest.focalPerson',
                'dvRequest.respocenter',
                'verifier'
            ])
            ->where('verified_by', auth()->id());

            // Search by DV Number
            if ($request->has('search') && $request->search) {
                $query->whereHas('dvRequest', function($q) use ($request) {
                    $q->where('dv_number', 'like', '%' . $request->search . '%');
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

            return view('Incoming.TEV.loghistory', compact('verificationHistory'));
        }

    protected function saveIncomingTEVVerificationHistory($dvRequest, $step, $remarks = null, $data = [])
    {
            try  {
                IncomingTEVVerificationHistory::create([
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
                \Log::error('Failed to save Incoming-TEV verification history', [
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
