<?php

namespace App\Http\Controllers\IMSD;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Services\PusherService;
use App\Services\SendPulseServices;
use App\Notifications\DvRequestRejected;
use App\Models\TUPADAttachment;
use App\Models\DocumentAgingAlert;
use App\Models\IMSDVerificationHistory;
use App\Models\DvRequestHistory;
use App\Models\TEVAttachment;
use App\Models\Notification;
use App\Traits\TriggersDashboardUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DvRequestProcessingController extends Controller
{
    protected PusherService $pusher;
    use TriggersDashboardUpdate;
    
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
            ->whereIn('status', ['submitted', 'IMSD_received'])
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

        // Changed from desc to asc to show newest at bottom
        $dvRequests = $query->orderBy('created_at', 'asc')->paginate(10);

        return view('RegionalOffices.IMSD.index', compact('dvRequests'));
    }

    // For JSON polling with search
    public function fetchAll(Request $request)
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $query = DvRequest::with(['focalPerson', 'program', 'respocenter', 'accountingUnit'])
            ->whereIn('status', ['submitted', 'IMSD_received'])
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

        // Changed from latest() to oldest() or orderBy asc
        $dvRequests = $query->orderBy('created_at', 'asc')->get();

        $result = $dvRequests->map(function ($request) {
            $base = [
                'id'             => $request->id,
                'accounting_control_number' => $request->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT): '—', 
                'submitted_by'   => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                'submitted_to'   => auth()->id(),
                'program'        => $request->program->prcode ?? 'N/A',
                'respocenter'    => $request->respocenter->rccode ?? 'N/A',
                'amount'         => number_format($request->amount, 2),
                'status'         => $request->status,
                'date'           => $request->created_at->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ];

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

        $checklistItems = [];

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
            if ($request->expectsJson() || $request->ajax()) {
                if (!$request->isMethod('POST')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Method not allowed.'
                    ], 405);
                }
            }

            $dvRequest = DvRequest::findOrFail($id);
            $statusBefore = $dvRequest->status;
            $receivedAt = now();
            
            if ($dvRequest->status !== 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request cannot be received in its current status: ' . $dvRequest->status
                ], 422);
            }
            
            $controlNumber = $dvRequest->accounting_control_number 
                ? 'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) 
                : 'Pending-CN';
            
            $userName = auth()->user()->name ?? 'IMSD User';
            $currentUserId = auth()->id();
            
            DB::beginTransaction();
            
            try {
                // Status changed directly to IMSD_received (ready for verification)
                $dvRequest->update([
                    'status'            => 'IMSD_received',
                    'accountingunit_id' => $currentUserId,
                    'received_by'       => $currentUserId,
                    'received_at'       => $receivedAt,
                    'locked_by'         => $currentUserId,
                    'locked_at'         => $receivedAt,
                ]);
                
                $this->saveVerificationHistory(
                    $dvRequest,
                    'receive',
                    'Request officially received by IMSD',
                    [
                        'status_before' => $statusBefore,
                        'status_after'  => 'IMSD_received',
                        'received_by'   => $currentUserId,
                        'received_at'   => $receivedAt->toISOString(),
                    ]
                );

                $this->logAction(
                    $dvRequest->id,
                    'IMSD',
                    'IMSD_received',
                    'Request officially received by IMSD'
                );
                
                $focalId = $dvRequest->focalperson_id;
                if ($focalId) {
                    Notification::create([
                        'focalperson_id' => $focalId,
                        'dv_request_id'  => $dvRequest->id,
                        'status'         => 'IMSD_received',
                        'action'         => 'Received',
                        'remarks'        => 'Request officially received by IMSD',
                    ]);
                    
                    try {
                        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                            'dv_request_id' => $dvRequest->id,
                            'status'        => 'IMSD_received',
                            'isRejected'    => false,
                            'remarks'       => 'Request officially received by IMSD',
                            'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        ]);
                    } catch (\Exception $pusherError) {
                        \Log::warning('Pusher focal notification failed: ' . $pusherError->getMessage());
                    }
                }
                
                try {
                    $this->pusher->trigger('dv-requests-imsd', 'dv-request-received', [
                        'dv_request_id' => $dvRequest->id,
                        'control_number' => $controlNumber,
                        'received_by' => $userName,
                        'received_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);
                } catch (\Exception $pusherError) {
                    \Log::warning('Pusher IMSD notification failed: ' . $pusherError->getMessage());
                }
                
                DB::commit();
                $this->triggerDashboardUpdate();

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Document has been received by IMSD and is ready for verification.',
                        'data' => [
                            'id' => $dvRequest->id,
                            'status' => 'IMSD_received',
                            'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                            'control_number' => $controlNumber
                        ]
                    ]);
                }
                
                return redirect()->route('RegionalOffices.IMSD.index')
                    ->with('success', 'Document has been received by IMSD and is ready for verification.');
                    
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('DV Request not found: ' . $e->getMessage());
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'DV request not found.'
                ], 404);
            }
            
            return redirect()->back()->with('error', 'DV request not found.');
            
        } catch (\Exception $e) {
            \Log::error('Error receiving DV request: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing the request: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'An error occurred while processing the request.');
        }
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
        
        // TUPAD ATTACHMENTS
        if ($dvRequest->program->prcode === 'TUPAD' && $request->filled('modality')) {
            $modality = $request->modality;
            $dvRequest->update(['tupad_modality' => $modality]);

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

        // TEV ATTACHMENTS
        if ($dvRequest->program->prcode === 'TEV' && $request->filled('tev_modality')) {
            $modality = $request->tev_modality;
            $dvRequest->update(['tev_modality' => $modality]);

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

        // GIP ATTACHMENTS
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
        
        $this->triggerDashboardUpdate();
        
        return redirect()->route('RegionalOffices.IMSD.index')
            ->with('success', 'DV Request has been verified successfully.');
    }

    protected function sendRejectionEmail($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            if (!$focalPerson || empty($focalPerson->email)) {
                \Log::warning('Cannot send rejection email: focal person or email missing', [
                    'dv_request_id' => $dvRequest->id,
                    'focal_person_id' => $focalPerson->id ?? 'null'
                ]);
                return false;
            }

            $toEmail = $focalPerson->email;
            $toName = trim($focalPerson->fname . ' ' . $focalPerson->lname);
            
            \Log::info('Attempting to send rejection email via SendPulse', [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'dv_request_id' => $dvRequest->id
            ]);

            if (!class_exists(SendPulseServices::class)) {
                \Log::error('SendPulseServices class not found');
                return $this->sendFallbackEmail($toEmail, $toName, $dvRequest, $rejectionRemarks);
            }

            $sendPulseService = new SendPulseServices();
            $result = $sendPulseService->sendRejectionEmail($toEmail, $toName, $dvRequest, $rejectionRemarks);
            
            if ($result) {
                \Log::info('SendPulse rejection email sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'to_email' => $toEmail
                ]);
            } else {
                \Log::error('SendPulse failed to send rejection email', [
                    'dv_request_id' => $dvRequest->id,
                    'to_email' => $toEmail
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            \Log::error('SendPulse rejection email failed: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id,
                'exception' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function sendFallbackEmail($toEmail, $toName, $dvRequest, $rejectionRemarks)
    {
        try {
            \Log::info('Using fallback SMTP email');
            
            $subject = "DV Request Rejected - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            $htmlContent = view('emails.dv_rejected', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'rejectionRemarks' => $rejectionRemarks,
                'rejectedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'rejectedBy' => auth()->user()->name ?? 'IMSD User'
            ])->render();

            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($toEmail, $toName, $subject, $htmlContent) {
                $message->to($toEmail, $toName)
                       ->subject($subject)
                       ->from(config('mail.from.address'), config('mail.from.name'))
                       ->html($htmlContent);
            });

            \Log::info('Fallback SMTP email sent successfully', ['to' => $toEmail]);
            return true;
        } catch (\Exception $e) {
            \Log::error('Fallback SMTP also failed: ' . $e->getMessage());
            return false;
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $request->validate([
                'remarks' => 'required|string|max:1000|min:10',
            ]);

            $dvRequest = DvRequest::whereHas('program', fn($q) => $q->whereIn('prcode', ['GIP', 'TUPAD', 'TEV']))
                                  ->findOrFail($id);

            if (!in_array($dvRequest->status, ['submitted', 'IMSD_received'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This DV request cannot be rejected at its current stage.'
                ], 422);
            }

            $rejectedAt = now();
            $focalId = $dvRequest->focalperson_id;
            $remarks = trim($request->remarks);
            $statusBefore = $dvRequest->status;

            DB::beginTransaction();

            try {
                $this->archiveToHistory($dvRequest, 'IMSD_rejected', $remarks);
                
                $dvRequest->update([
                    'status'     => 'IMSD_rejected',
                    'locked_by'  => null,
                    'locked_at'  => null,
                ]);

                $this->logAction(
                    $dvRequest->id,
                    'IMSD',
                    'IMSD_rejected',
                    "Rejected by IMSD: {$remarks}"
                );

                $emailSent = $this->sendRejectionEmail($dvRequest, $remarks);

                $this->saveVerificationHistory(
                    $dvRequest,
                    'reject',
                    $remarks,
                    [
                        'action' => 'rejected',
                        'status_before' => $statusBefore,
                        'status_after' => 'IMSD_rejected',
                        'rejected_by' => auth()->id(),
                        'rejected_at' => $rejectedAt->toISOString(),
                        'rejection_reason' => $remarks
                    ]
                );

                Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => 'IMSD_rejected',
                    'action'         => 'Rejected',
                    'remarks'        => $remarks,
                ]);

                DB::commit();
                $this->triggerDashboardUpdate();
                
                $responseMessage = $emailSent 
                    ? 'DV request has been rejected and notification sent.' 
                    : 'DV request has been rejected but email notification failed.';    

                if (isset($this->pusher)) {
                    $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => 'IMSD_rejected',
                        'isRejected'    => true,
                        'remarks'       => $remarks,
                        'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);

                    $this->pusher->trigger('dv-requests-imsd', 'dv-request-rejected', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => 'IMSD_rejected',
                        'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => $responseMessage,
                    'data' => [
                        'id'         => $dvRequest->id,
                        'status'     => 'IMSD_rejected',
                        'remarks'    => $remarks,
                        'email_sent' => $emailSent
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

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

        $pendingRequests = DvRequest::whereHas('program', fn($q) => 
                                    $q->whereIn('prcode', ['GIP','TUPAD','TEV'])) 
                                ->whereIn('status', ['submitted'])
                                ->orderBy('created_at', 'asc')
                                ->get();

        if ($pendingRequests->isEmpty()) {
            return redirect()->route('RegionalOffices.IMSD.index')
                             ->with('info', 'No IMSD DV requests pending verification.');
        }

        $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
            return !$req->locked_by 
                || $req->locked_by == $currentUserId 
                || $req->locked_at <= $lockDuration;
        });

        if ($available) {
            return redirect()->route('RegionalOffices.IMSD.verifydv', $available->id);
        }

        return redirect()->route('RegionalOffices.IMSD.index')
                         ->with('info', 'All pending IMSD DV requests are currently locked by other users.');
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
            'locked_at' => now(),
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

        if ($request->has('search') && $request->search) {
            $query->whereHas('dvRequest', function($q) use ($request) {
                $q->where('accounting_control_number', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('program') && $request->program) {
            $query->whereHas('dvRequest.program', function($q) use ($request) {
                $q->where('prcode', $request->program);
            });
        }

        if ($request->has('step') && $request->step) {
            $query->where('verification_step', $request->step);
        }

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
}