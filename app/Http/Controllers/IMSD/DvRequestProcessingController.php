<?php

namespace App\Http\Controllers\IMSD;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Services\PusherService;
use App\Models\TUPADAttachment;
use App\Models\DocumentAgingAlert;
use App\Models\IMSDVerificationHistory;
use App\Models\TEVAttachment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DvRequestProcessingController extends Controller

{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

            // Index method: list all DV requests pending IMSD processing
        public function index(Request $request)
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $query = DvRequest::query()
            ->whereIn('status', ['submitted', 'IMSD_in_process'])
            ->where(function ($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by') // not locked
                ->orWhere('locked_by', $currentUserId) // locked by me
                ->orWhere('locked_at', '<=', $lockDuration); // lock expired
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

        return view('RegionalOffices.IMSD.index', compact('dvRequests'));
    }


    // For JSON polling with search
    public function fetchAll(Request $request)
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $query = DvRequest::with(['focalPerson', 'program', 'respocenter', 'accountingUnit'])
            ->whereIn('status', ['submitted', 'IMSD_in_process','IMSD_received'])
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

        $dvRequests = $query->latest()->get();

    $result = $dvRequests->map(function ($request) {
    $base = [
        'id'             => $request->id,
        'accounting_control_number' => $request->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 5, '0', STR_PAD_LEFT): '—', 
        'submitted_by'   => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
        'submitted_to'   => auth()->id(),
        'program'        => $request->program->prcode ?? 'N/A',
        'respocenter'    => $request->respocenter->rccode ?? 'N/A',
        'amount'         => number_format($request->amount, 2),
        'status'         => $request->status,
        'date'           => $request->created_at->format('M d, Y h:i A'),
    ];

    // Only include per-user sequence if the logged-in user is a focal person
    if (auth()->guard('focal')->check()) {
        $base['user_sequence'] = $request->user_sequence;
    }

    return $base;
});

        return response()->json($result);
    }

   public function show($id)
{
    $dvRequest = DvRequest::with([
        'accountingUnit',
        'program',
        'focalPerson',
        'gipAttachments',
        'tupadAttachments',
        'tevAttachments',
        'logs'
    ])
    ->whereHas('program', fn($q) => $q->whereIn('prcode', ['GIP', 'TUPAD', 'TEV']))
    ->findOrFail($id);

    $lockDuration   = now()->subMinutes(10);
    $currentUserId  = auth()->id();

    if ($dvRequest->locked_by && $dvRequest->locked_by !== $currentUserId && $dvRequest->locked_at > $lockDuration) {
        return redirect()->route('RegionalOffices.IMSD.index')
            ->with('error', 'This DV Request is currently being processed by another user.');
    }

    if (!$dvRequest->locked_by || $dvRequest->locked_at <= $lockDuration) {
        $dvRequest->update([
            'locked_by' => $currentUserId,
            'locked_at' => now(),
        ]);
    }

    // ✅ Always define
    $checklistItems = [];

    // ========================================
    // GIP: Ensure checklist items exist
    // ========================================
    if ($dvRequest->program->prcode === 'GIP') {
        $checklistItems = [
            'EUP Payroll', 'Payroll', 'Pacsval', 'DTR with Accomplishment Report',
            'DTR Log', 'Profile', 'Brgy. Certification', 'TOR/My Grade', 'Insurance',
            'Birth Certificate', 'Internship Agreement', 'Evaluation Form-GIP',
        ];

        foreach ($checklistItems as $item) {
            $dvRequest->gipAttachments()->firstOrCreate(['name' => $item], ['is_present' => false]);
        }
    }

    $tupadAttachments = collect();
    if ($dvRequest->program->prcode === 'TUPAD' && $dvRequest->tupad_modality) {
        $tupadAttachments = $dvRequest->tupadAttachments()
            ->where('modality', $dvRequest->tupad_modality)
            ->orderBy('sort_order')
            ->get();
    }

    $tevAttachments = collect();
    if ($dvRequest->program->prcode === 'TEV' && $dvRequest->tev_modality) {
        $tevAttachments = $dvRequest->tevAttachments()
            ->where('modality', $dvRequest->tev_modality)
            ->orderBy('sort_order')
            ->get();
    }

    $lastLog = $dvRequest->logs()->latest()->first();
    $lastStep = $lastLog ? $lastLog->action : 'Submitted';

    return view('RegionalOffices.IMSD.verifydv', compact(
        'dvRequest',
        'checklistItems',
        'tupadAttachments',
        'tevAttachments',
        'lastStep'
    ));
}

   public function receive(Request $request, $id)
{
    try {
        $dvRequest = DvRequest::findOrFail($id);
        $statusBefore = $dvRequest->status;
        $receivedAt = now();
        
        if ($dvRequest->status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been received.'
            ], 422);
        }
        
        // ✅ DEFINE THESE VARIABLES BEFORE USING THEM
        $controlNumber = $dvRequest->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 5, '0', STR_PAD_LEFT) : 'Unknown';
        $userName = auth()->user()->name ?? 'IMSD User';
        
        $dvRequest->update([
            'status'           => 'IMSD_received',
            'accountingunit_id'=> auth()->id(),
            'received_by'      => auth()->id(),
            'received_at'      => $receivedAt,
            'locked_by'        => auth()->id(),
            'locked_at'        => $receivedAt,
        ]);
        
        // ✅ AUTO-ACKNOWLEDGE AGING ALERTS
        $acknowledgedAlerts = DocumentAgingAlert::where('dv_request_id', $id)
            ->where('acknowledged', false)
            ->update([
                'acknowledged' => true,
                'acknowledged_at' => now(),
                'acknowledged_by' => auth()->id(),
            ]);

        $this->saveVerificationHistory(
            $dvRequest,
            'receive',
            'Request officially received by IMSD',
            [
                'status_before' => $statusBefore,
                'status_after'  => 'IMSD_received',
                'received_by'   => auth()->id(),
                'received_at'   => $receivedAt->toISOString(),
            ]
        );

        // Log
        $this->logAction(
            $dvRequest->id,
            'IMSD',
            'IMSD_received',
            'Request officially received by IMSD'
        );
        
        // Notify focal person
        $focalId = $dvRequest->focalperson_id;
        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => 'IMSD_received',
            'action'         => 'Received',
            'remarks'        => 'Request officially received by IMSD',
        ]);
        
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'IMSD_received',
            'isRejected'    => false,
            'remarks'       => 'Request officially received by IMSD',
            'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
        
        // ✅ NOW THESE VARIABLES ARE DEFINED
        $this->pusher->trigger('dv-requests-imsd', 'dv-request-received', [
            'dv_request_id' => $dvRequest->id,
            'control_number' => $controlNumber,
            'received_by' => $userName,
            'received_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            'acknowledged_alerts_count' => $acknowledgedAlerts,
        ]);

        // Check if request expects JSON (AJAX)
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document has been received by IMSD.',
                'acknowledged_alerts' => $acknowledgedAlerts, // ✅ Include this in response
                'data' => [
                    'id' => $dvRequest->id,
                    'status' => 'IMSD_received',
                    'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                ]
            ]);
        }
        
        // Fallback for non-AJAX requests (if needed)
        return redirect()->route('RegionalOffices.IMSD.index')
            ->with('success', 'Document has been received by IMSD.');
            
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
   public function startProcessing(Request $request, $id)
{
    try {
        $dvRequest = DvRequest::findOrFail($id);
        $statusBefore = $dvRequest->status;
        $receivedAt = now();
        if ($dvRequest->status !== 'IMSD_received') {
            return response()->json([
                'success' => false,
                'message' => 'Request must be received first before processing.'
            ], 422);
        }

        $startedAt = now();

        $dvRequest->update([
            'status'    => 'IMSD_in_process',
            'locked_by' => auth()->id(),
            'locked_at' => $startedAt,
        ]);

    

        $this->saveVerificationHistory(
            $dvRequest,
            'process',
            'Processing started by IMSD',
            [
                'action'        => 'processing_started',
                'status_before' => $statusBefore,
                'status_after'  => 'IMSD_processing',
                'processed_by'  => auth()->id(),
                'processed_at'  => $startedAt->toISOString(),
            ]
        );
                // Log
        $this->logAction(
            $dvRequest->id,
            'IMSD',
            'IMSD_in_process',
            'Processing started by IMSD'
        );

        // Notify focal person
        $focalId = $dvRequest->focalperson_id;
        $status  = 'IMSD_in_process';
        $action  = 'Processing Started';
        $remarks = 'Request is now being processed by IMSD';

        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => $status,
            'action'         => $action,
            'remarks'        => $remarks,
        ]);

       dispatch(function () use ($dvRequest, $focalId, $startedAt) {
        try {
            app(\App\Services\PusherService::class)->trigger('dv-requests-' . $focalId, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'IMSD_in_process',
                'isRejected'    => false,
                'remarks'       => 'Request is now being processed by IMSD',
                'timestamp'     => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            app(\App\Services\PusherService::class)->trigger('dv-requests-imsd', 'dv-request-processing', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'IMSD_in_process',
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Pusher dispatch failed: ' . $e->getMessage());
        }
    });

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
        return redirect()->route('RegionalOffices.IMSD.index')
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


    public function fetchimsdRecent()
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $recentDvRequests = DvRequest::with('focalPerson')
            ->whereIn('status', ['submitted', 'IMSD_in_process','IMSD_received'])
            ->whereNotNull('focalperson_id')
            ->where(function ($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by') // not locked
                ->orWhere('locked_by', $currentUserId) // locked by me
                ->orWhere('locked_at', '<=', $lockDuration); // lock expired
            })
            ->latest()
            ->take(8)
            ->get();

        return response()->json($recentDvRequests);
    }

    
public function verify(Request $request, $id)
{
    $request->validate([
        'remarks'         => 'nullable|string|max:1000',
        'modality'        => 'nullable|string',
        'tev_modality'    => 'nullable|string',
        'attachments'     => 'array',
        'tev_attachments' => 'array',
    ]);

    $dvRequest = DvRequest::with(['gipAttachments', 'tupadAttachments', 'tevAttachments'])->findOrFail($id);
    $statusBefore = $dvRequest->status;

    
    $attachmentData = [];
    // --- TUPAD ATTACHMENTS ---
    if ($dvRequest->program->prcode === 'TUPAD' && $request->filled('modality')) {
        $modality = $request->modality;
        $dvRequest->update(['tupad_modality' => $modality]);

        // Initialize if not yet created
        $existingCount = $dvRequest->tupadAttachments()->where('modality', $modality)->count();
        if ($existingCount === 0) {
            $templates = TUPADAttachment::whereNull('dv_request_id')
                ->where('modality', $modality)
                ->orderBy('sort_order')
                ->get();

            foreach ($templates as $template) {
                TUPADAttachment::create([
                    'dv_request_id' => $dvRequest->id,
                    'name' => $template->name,
                    'modality' => $template->modality,
                    'sort_order' => $template->sort_order,
                    'is_present' => false,
                ]);
            }
        }

        // Update checked attachments
        foreach ($dvRequest->tupadAttachments()->where('modality', $modality)->get() as $attachment) {
            $isPresent = isset($request->attachments[$attachment->name]);
            $attachment->update(['is_present' => $isPresent]);

              $attachmentData[] = [
                'name' => $attachment->name,
                'is_present' => $isPresent,
                'modality' => $modality,
                'type' => 'tupad'
            ];
        }
    }

    // --- TEV ATTACHMENTS ---
    if ($dvRequest->program->prcode === 'TEV' && $request->filled('tev_modality')) {
        $modality = $request->tev_modality;
        $dvRequest->update(['tev_modality' => $modality]);

        // Initialize if not yet created
        $existingCount = $dvRequest->tevAttachments()->where('modality', $modality)->count();
        if ($existingCount === 0) {
            $templates = TEVAttachment::whereNull('dv_request_id')
                ->where('modality', $modality)
                ->orderBy('sort_order')
                ->get();

            foreach ($templates as $template) {
                TEVAttachment::create([
                    'dv_request_id' => $dvRequest->id,
                    'name' => $template->name,
                    'modality' => $template->modality,
                    'sort_order' => $template->sort_order,
                    'is_present' => false,
                ]);
            }
        }

        // Update checked attachments
        foreach ($dvRequest->tevAttachments()->where('modality', $modality)->get() as $attachment) {
            $isPresent = isset($request->tev_attachments[$attachment->name]);
            $attachment->update(['is_present' => $isPresent]);

             $attachmentData[] = [
                'name' => $attachment->name,
                'is_present' => $isPresent,
                'modality' => $modality,
                'type' => 'tev'
            ];
        }
    }

    // --- GIP ATTACHMENTS ---
    if ($dvRequest->program->prcode === 'GIP') {
        $checklistItems = [
            'EUP Payroll', 'Payroll', 'Pacsval', 'DTR with Accomplishment Report',
            'DTR Log', 'Profile', 'Brgy. Certification', 'TOR/My Grade', 'Insurance',
            'Birth Certificate', 'Internship Agreement', 'Evaluation Form-GIP',
        ];

        foreach ($checklistItems as $item) {
            $isPresent = isset($request->attachments[$item]);
            $attachment = $dvRequest->gipAttachments()->firstOrCreate(['name' => $item]);
            $attachment->update(['is_present' => $isPresent]);
                 $attachmentData[] = [
                'name' => $item,
                'is_present' => $isPresent,
                'type' => 'gip'
            ];
        }
    }

        $verifiedAt = now();
    // --- Common Post-Verification Logic ---
    $dvRequest->update([
        'status' => 'IMSD_verified',
        'locked_by' => null,
        'locked_at' => null,
    ]);


    $focalId = $dvRequest->focalperson_id;
    $status = 'IMSD_verified';
    $action = 'Approved';
    $remarks = $request->remarks ?? 'Request verified by IMSD';

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
        'isRejected'    => false,
        'remarks'       => $remarks,
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);

    $programCode = $dvRequest->program->prcode;
    $channelMap = [
        'GIP' => 'dv-requests-incoming-gip',
        'TEV' => 'dv-requests-incoming-tev',
        'TUPAD' => 'dv-requests-incoming-tupad',
    ];

    if (isset($channelMap[$programCode])) {
        $this->pusher->trigger($channelMap[$programCode], 'dv-request-submitted', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'IMSD_verified',
            'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
            'program'       => $programCode,
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
    }

    $this->saveVerificationHistory(
        $dvRequest,
        'verify',
        $request->remarks ?? 'Request verified by IMSD',
        [
            'action'             => 'verified',
            'status_before'      => $statusBefore,
            'status_after'       => 'IMSD_verified',
            'attachments_checked'=> $attachmentData,
            'modality'           => $request->modality ?? $request->tev_modality ?? null,
            'verified_by'        => auth()->id(),
            'verified_at'        => $verifiedAt->toISOString(),
        ]
    );

    $this->logAction(
        $dvRequest->id,
        'IMSD',
        'Approved',
        $remarks
    );

    return redirect()->route('RegionalOffices.IMSD.index')
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
        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->whereIn('prcode', ['GIP', 'TUPAD', 'TEV']))
                              ->findOrFail($id);

        // ✅ Only allow rejection if status is currently in IMSD stage
        if (!in_array($dvRequest->status, ['submitted', 'IMSD_received', 'IMSD_in_process'])) {
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
            'status'     => 'IMSD_rejected',
            'locked_by'  => null,
            'locked_at'  => null,
        ]);


          $this->saveVerificationHistory(
            $dvRequest,
            'reject', // ✅ Changed from 'process' to 'reject'
            $remarks,
            [
                'action' => 'rejected',
                'rejected_by' => auth()->id(),
                'rejected_at' => $rejectedAt->toISOString(), // ✅ Use $rejectedAt, not $startedAt
                'rejection_reason' => $remarks
            ]
        );
        // ✅ Log rejection
        $this->logAction(
            $dvRequest->id,
            'IMSD',
            'IMSD_rejected',
            "Rejected by IMSD: {$remarks}"
        );

        // ✅ Notify focal person
        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => 'IMSD_rejected',
            'action'         => 'Rejected',
            'remarks'        => $remarks,
        ]);

        // ✅ Real-time push to focal person
        if (isset($this->pusher)) {
            $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'IMSD_rejected',
                'isRejected'    => true,
                'remarks'       => $remarks,
                'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            // ✅ Real-time push to IMSD dashboard
            $this->pusher->trigger('dv-requests-imsd', 'dv-request-rejected', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'IMSD_rejected',
                'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);
        }

        // ✅ Always return JSON
        return response()->json([
            'success' => true,
            'message' => 'DV request has been rejected successfully.',
            'data' => [
                'id'         => $dvRequest->id,
                'status'     => 'IMSD_rejected',
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
        \Log::error('Error rejecting IMSD DV request: ' . $e->getMessage());
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
                                $q->whereIn('prcode', ['GIP','TUPAD','TEV'])) 
                            ->whereIn('status', ['submitted'])
                            ->orderBy('created_at', 'asc')
                            ->get();

    if ($pendingRequests->isEmpty()) {
        return redirect()->route('RegionalOffices.IMSD.index')
                         ->with('info', 'No IMSD DV requests pending verification.');
    }

    // Check for one you can work on (unlocked or locked by you, or expired lock)
    $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
        return !$req->locked_by 
            || $req->locked_by == $currentUserId 
            || $req->locked_at <= $lockDuration;
    });

    if ($available) {
        return redirect()->route('RegionalOffices.IMSD.verifydv', $available->id);
    }

    // If all are locked by others
    return redirect()->route('RegionalOffices.IMSD.index')
                     ->with('info', 'All pending GIP DV requests are currently locked by other users.');
}

      public function releaseLock($id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->locked_by === auth()->id()) {
            $dvRequest->update([
                'locked_by' => null,
                'locked_at' => null,
                'status'    => 'submitted',
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


    public function verificationHistory(Request $request)
    {
        $query = IMSDVerificationHistory::with([
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

        return view('RegionalOffices.IMSD.loghistory', compact('verificationHistory'));
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

protected function saveVerificationHistory($dvRequest, $step, $remarks = null, $data = [])
{
    $statusBefore = $data['status_before'] ?? ($dvRequest->getOriginal('status') ?? 'unknown');
    $statusAfter  = $data['status_after'] ?? $dvRequest->status;

    IMSDVerificationHistory::create([
        'dv_request_id'      => $dvRequest->id,
        'verified_by'        => auth()->id(),
        'verification_step'  => $step,
        'status_before'      => $statusBefore,
        'status_after'       => $statusAfter,
        'verification_data'  => $data,
        'remarks'            => $remarks,
        'verified_at'        => now(),
    ]);
}

    
}
