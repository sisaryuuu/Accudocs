<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Traits\TriggersDashboardUpdate;
use App\Traits\HandlesDepartmentTransitions;
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\Notification;
use App\Models\OutgoingFinalizationHistory;
use App\Models\DvRequestLog;
use App\Models\DvRequestHistory;
use App\Services\PusherService;
use App\Services\SendPulseServices;
use App\Services\IprogSmsService;
use App\Models\GIPAttachment;
use App\Models\TUPADAttachment;
use App\Models\TEVAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OutgoingDvController extends Controller
{
    use TriggersDashboardUpdate, HandlesDepartmentTransitions;
    
    protected PusherService $pusher;
    protected IprogSmsService $smsService;

    public function __construct(PusherService $pusher, IprogSmsService $smsService)
    {
        $this->pusher = $pusher;
        $this->smsService = $smsService;
    }

    public function index()
    {
        return view('Outgoing.index');
    }

  public function fetchDvRequests(Request $request)
{
    try {
        $currentUserId = auth()->id();
        $lockDuration  = now()->subMinutes(10);
        
        $searchTerm = $request->input('search', '');

        $query = DvRequest::with([
            'program', 
            'focalPerson', 
            'respocenter',
            'orsRequest',
        ])
        ->whereIn('status', ['ORS_obligated', 'Outgoing_received'])
        ->where(function ($q) use ($currentUserId, $lockDuration) {
            $q->whereNull('locked_by')
              ->orWhere('locked_by', $currentUserId)
              ->orWhere('locked_at', '<=', $lockDuration);
        })
        ->when($searchTerm, function ($query) use ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('accounting_control_number', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('focalPerson', function ($personQuery) use ($searchTerm) {
                      $personQuery->where('fname', 'LIKE', "%{$searchTerm}%")
                                 ->orWhere('lname', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('program', function ($programQuery) use ($searchTerm) {
                      $programQuery->where('prcode', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('respocenter', function ($centerQuery) use ($searchTerm) {
                      $centerQuery->where('rccode', 'LIKE', "%{$searchTerm}%")
                                 ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhere('particulars', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('amount', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('status', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('orsRequest', function ($orsQuery) use ($searchTerm) {
                      $orsQuery->where('ors_number', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhere('dv_number', 'LIKE', "%{$searchTerm}%");
            });
        });

        $dvRequests = $query->get();
        
        $dvRequests = $dvRequests->map(function ($request) {
            // Use outgoing_received_at if available, otherwise created_at
            $baseDate = $request->outgoing_received_at ?? $request->created_at;
            $daysOld = $baseDate ? now()->diffInDays($baseDate) : 0;
            
            $priorityScore = 0;
            
            // 🚨 URGENT FLAG - HIGHEST PRIORITY
            if ($request->is_urgent) {
                $priorityScore += 50000; // Highest priority for urgent documents
            }
            
            if ($daysOld >= 10) {
                $priorityScore += 1000;
            } elseif ($daysOld >= 7) {
                $priorityScore += 500;
            } elseif ($daysOld >= 3) {
                $priorityScore += 200;
            }
            
            if ($request->status === 'ORS_obligated') {
                $priorityScore += 100;
            }
            
            if ($request->status === 'Outgoing_received') {
                $priorityScore += 50;
            }
            
            $priorityScore += rand(0, 10);
            
            $request->days_old = $daysOld;
            $request->priority_score = $priorityScore;
            $request->urgency = $this->getOutgoingUrgencyLevel($daysOld);
            
            // Override urgency if marked urgent
            if ($request->is_urgent) {
                $request->urgency = 'urgent';
            }
            
            return $request;
        });
        
        $dvRequests = $dvRequests->sortByDesc('priority_score')->values();

        return response()->json($dvRequests->map(function ($req) {
            $baseDate = $req->outgoing_received_at ?? $req->created_at;
            
            // Helper function to safely convert date to ISO string
            $safeToISO = function ($date) {
                if (!$date) return null;
                if (is_string($date)) {
                    try {
                        $date = \Carbon\Carbon::parse($date);
                    } catch (\Exception $e) {
                        return null;
                    }
                }
                return method_exists($date, 'toISOString') ? $date->toISOString() : null;
            };
            
            return [
                'id'           => $req->id,
                'control_no'   => $req->accounting_control_number ? 
                    'CN #-' . now()->year . '-' . str_pad($req->accounting_control_number, 4, '0', STR_PAD_LEFT) : 
                    '—',
                'dv_number'    => $req->dv_number ?? null,
                'ors_number'   => $req->orsRequest->ors_number ?? null,
                'submitted_by' => trim(($req->focalPerson->fname ?? '') . ' ' . ($req->focalPerson->lname ?? '')),
                'program'      => $req->program->prcode ?? 'N/A',
                'respocenter'  => trim(($req->respocenter->rccode ?? '') . ' - ' . ($req->respocenter->description ?? '')) ?? 'N/A',
                'particulars'  => $req->particulars,
                'amount'       => number_format($req->amount, 2),
                'status'       => $req->status,
                'date'         => $baseDate ? $baseDate->format('M d, Y') : '—',
                'created_at_timestamp' => $safeToISO($req->created_at),
                'outgoing_received_at' => $safeToISO($req->outgoing_received_at),
                'outgoing_verified_at' => $safeToISO($req->outgoing_verified_at),
                'outgoing_last_action_at' => $safeToISO($req->outgoing_last_action_at),
                'last_action_at' => $safeToISO($req->last_action_at),
                'days_old'     => $req->days_old,
                'urgency'      => $req->urgency,
                'priority_score' => $req->priority_score,
                // 🚨🚨🚨 ADD THIS LINE - CRITICAL FOR URGENT FLAG 🚨🚨🚨
                'is_urgent'    => $req->is_urgent ?? false,
            ];
        }));

    } catch (\Exception $e) {
        Log::error('Error in Outgoing fetchDvRequests: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'error' => 'Failed to fetch DV requests: ' . $e->getMessage()
        ], 500);
    }
}

    protected function getOutgoingUrgencyLevel($daysOld)
    {
        if ($daysOld >= 10) {
            return 'critical';
        } elseif ($daysOld >= 7) {
            return 'high';
        } elseif ($daysOld >= 3) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    public function fetchDvRecent()
    {
        $currentUserId = auth()->id();
        $lockDuration  = now()->subMinutes(10);

        $recentDvRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
            ->whereIn('status', ['ORS_obligated', 'Outgoing_received'])
            ->whereNotNull('focalperson_id')
            ->where(function ($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by')
                  ->orWhere('locked_by', $currentUserId)
                  ->orWhere('locked_at', '<=', $lockDuration);
            })
            ->latest()
            ->take(5)
            ->get();

        return response()->json($recentDvRequests);
    }

    public function show($id)
    {
        $dvRequest = DvRequest::with([
            'respocenter',
            'focalPerson',
            'orsRequest',
            'gipAttachments',
            'tupadAttachments',
            'tevAttachments',
            'logs',
        ])->findOrFail($id);

        $currentUserId = auth()->id();
        $lockDuration  = now()->subMinutes(10);

        if ($dvRequest->locked_by 
            && $dvRequest->locked_by !== $currentUserId 
            && $dvRequest->locked_at >= $lockDuration) {
            return redirect()->route('Outgoing.dvrequests.index')
                ->with('error', 'This DV Request is currently being processed by another user.');
        }

        if (!$dvRequest->locked_by || $dvRequest->locked_at < $lockDuration || $dvRequest->locked_by === $currentUserId) {
            $dvRequest->update([
                'locked_by' => $currentUserId,
                'locked_at' => now(),
            ]);
        }

        // For GIP: Get GIP attachments
        $gipAttachments = collect();
        if ($dvRequest->program->prcode === 'GIP') {
            $gipAttachments = $dvRequest->gipAttachments()->get();
            
            $naAttachmentNames = ['DTR Log', 'Profile', 'Brgy. Certification', 'TOR/My Grade', 'Insurance', 'Birth Certificate'];
            
            $gipAttachments->each(function($attachment) use ($naAttachmentNames) {
                $attachment->is_na = in_array($attachment->name, $naAttachmentNames);
            });
        }

        // For TUPAD: Get selected modality and attachments
        $selectedModality = $dvRequest->tupad_modality;
        $tupadAttachments = collect();
        if ($selectedModality) {
            $tupadAttachments = $dvRequest->tupadAttachments()
                ->where('modality', $selectedModality)
                ->orderBy('sort_order')
                ->get();
        }

        // For TEV: Get selected modality and attachments
        $tevSelectedModality = $dvRequest->tev_modality;
        $tevAttachments = collect();
        if ($tevSelectedModality) {
            $tevAttachments = $dvRequest->tevAttachments()
                ->where('modality', $tevSelectedModality)
                ->orderBy('sort_order')
                ->get();
        }

        $lastLog  = $dvRequest->logs()->latest()->first();
        $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

        return view('Outgoing.finalizedv', compact(
            'dvRequest', 
            'lastStep',
            'gipAttachments',
            'tupadAttachments',
            'selectedModality',
            'tevSelectedModality',
            'tevAttachments'
        ));
    }

    public function receive(Request $request, $id)
    {
        try {
            $dvRequest = DvRequest::findOrFail($id);
            $statusBefore = $dvRequest->status;
            $receivedAt = now();
            $currentUserId = auth()->id();
            $newStatus = 'Outgoing_received';

            if ($dvRequest->status !== 'ORS_obligated') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is not yet ready for Outgoing to receive.'
                ], 422);
            }

            DB::transaction(function () use ($dvRequest, $statusBefore, $receivedAt, $currentUserId, $newStatus) {
                // Update Outgoing specific timestamps
                $dvRequest->update([
                    'outgoing_received_at' => $receivedAt,
                    'outgoing_last_action_at' => $receivedAt,
                    'last_action_at' => $receivedAt,
                    'last_action_type' => 'received',
                    'last_acted_by' => $currentUserId,
                ]);
                
                $dvRequest->update([
                    'status'      => $newStatus,
                    'accountingunit_id' => $currentUserId,
                    'received_by' => $currentUserId,
                    'received_at' => $receivedAt,
                    'locked_by'   => $currentUserId,
                    'locked_at'   => $receivedAt,
                ]);

                $this->saveOutgoingFinalizationHistory(
                    $dvRequest,
                    'receive',
                    'DV request received by Outgoing for final verification',
                    [
                        'status_before' => $statusBefore,
                        'status_after'  => $newStatus,
                        'received_by' => $currentUserId,
                        'received_at' => $receivedAt->toISOString(),
                        'outgoing_received_at' => $receivedAt->toISOString(),
                        'outgoing_last_action_at' => $receivedAt->toISOString(),
                        'last_action_at' => $receivedAt->toISOString(),
                        'amount' => $dvRequest->amount
                    ]
                );

                $this->logAction(
                    $dvRequest->id,
                    'Outgoing',
                    $newStatus,
                    'DV request received by Outgoing for final verification. Outgoing received at: ' . 
                    $receivedAt->format('M d, Y H:i:s')
                );

                $focalId = $dvRequest->focalperson_id;
                $remarks = "DV request received by Outgoing for final verification";

                Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $newStatus,
                    'action'         => 'Received',
                    'remarks'        => $remarks,
                ]);

                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => $newStatus,
                    'remarks'       => $remarks,
                    'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'is_urgent' => $dvRequest->is_urgent ?? false,
                ]);

                $this->pusher->trigger('dv-requests-outgoing', 'dv-request-received', [
                    'dv_request_id' => $dvRequest->id,
                    'control_number' => $dvRequest->accounting_control_number,
                    'status'        => $dvRequest->status,
                    'received_by'   => auth()->user()->name ?? 'Outgoing User',
                    'action_performer_id' => $currentUserId,
                    'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'outgoing_received_at' => $receivedAt->toISOString(),
                    'outgoing_last_action_at' => $receivedAt->toISOString(),
                    'last_action_at' => $receivedAt->toISOString(), 
                    'is_urgent' => $dvRequest->is_urgent ?? false,
                ]);
            });

            $this->triggerDashboardUpdate();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document has been received by Outgoing.',
                    'data' => [
                        'id'          => $dvRequest->id,
                        'status'      => $newStatus,
                        'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'outgoing_received_at' => $receivedAt->format('M d, Y H:i:s'),
                        'outgoing_last_action_at' => $receivedAt->format('M d, Y H:i:s'),
                        'last_action_at' => $receivedAt->format('M d, Y H:i:s'),
                        'is_urgent' => $dvRequest->is_urgent ?? false,
                    ]
                ]);
            }

            return redirect()->route('Outgoing.dvrequests.index')
                ->with('success', 'Document has been received by Outgoing.');

        } catch (\Exception $e) {
            Log::error('Error receiving DV request by Outgoing: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing the request.'
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while processing the request.');
        }
    }

    public function redirectToFirstPending()
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $pendingRequests = DvRequest::whereIn('status', ['ORS_Obligated', 'Outgoing_received'])
                                ->orderBy('created_at', 'asc')
                                ->get();

        if ($pendingRequests->isEmpty()) {
            return redirect()->route('Outgoing.dvrequests.index')
                             ->with('info', 'No DV requests pending verification.');
        }

        $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
            return !$req->locked_by 
                || $req->locked_by == $currentUserId 
                || $req->locked_at <= $lockDuration;
        });

        if ($available) {
            return redirect()->route('Outgoing.dvrequests.withid', $available->id);
        }

        return redirect()->route('Outgoing.dvrequests.index')
                         ->with('info', 'All pending DV requests are currently locked by other users.');
    }

    public function updateAttachment(Request $request, $id)
    {
        $request->validate([
            'attachment_id' => 'required|integer',
            'attachment_type' => 'required|string|in:gip,tupad,tev',
            'is_present' => 'required|boolean',
            'remarks' => 'nullable|string|max:500'
        ]);

        try {
            $dvRequest = DvRequest::findOrFail($id);
            
            if ($dvRequest->locked_by && $dvRequest->locked_by !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This DV request is locked by another user.'
                ], 403);
            }

            $attachmentId = $request->attachment_id;
            $attachmentType = $request->attachment_type;
            $isPresent = $request->is_present;
            
            $attachment = null;
            
            switch ($attachmentType) {
                case 'gip':
                    $attachment = $dvRequest->gipAttachments()->find($attachmentId);
                    break;
                case 'tupad':
                    $attachment = $dvRequest->tupadAttachments()->find($attachmentId);
                    break;
                case 'tev':
                    $attachment = $dvRequest->tevAttachments()->find($attachmentId);
                    break;
            }
            
            if (!$attachment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attachment not found.'
                ], 404);
            }
            
            $attachment->update([
                'is_present' => $isPresent,
                'remarks' => $request->remarks ?? ($isPresent ? 'Marked as present by Outgoing' : 'Marked as missing by Outgoing'),
                'updated_at' => now()
            ]);
            
            // Update last action timestamp
            $dvRequest->update([
                'outgoing_last_action_at' => now(),
                'last_action_at' => now(),
                'last_action_type' => 'attachment_update',
                'last_acted_by' => auth()->id(),
            ]);
            
            Log::info('Attachment status updated', [
                'dv_request_id' => $id,
                'attachment_id' => $attachmentId,
                'attachment_type' => $attachmentType,
                'attachment_name' => $attachment->name,
                'is_present' => $isPresent,
                'updated_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Attachment status updated successfully.',
                'data' => [
                    'attachment_id' => $attachmentId,
                    'is_present' => $isPresent,
                    'updated_at' => $attachment->updated_at->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'outgoing_last_action_at' => $dvRequest->outgoing_last_action_at->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating attachment: ' . $e->getMessage(), [
                'dv_request_id' => $id,
                'attachment_id' => $request->attachment_id,
                'error' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the attachment.'
            ], 500);
        }
    }

    public function finalize(Request $request, $id)
{
    $request->validate([
        'remarks' => 'nullable|string|max:1000',
        'gip_attachments' => 'nullable|array',
        'gip_attachments.*' => 'nullable',
        'attachments' => 'nullable|array',
        'attachments.*' => 'nullable',
        'tev_attachments' => 'nullable|array',
        'tev_attachments.*' => 'nullable',
    ]);

    try {
        $dvRequest = DvRequest::with(['gipAttachments', 'tupadAttachments', 'tevAttachments'])->findOrFail($id);
        $statusBefore = $dvRequest->status;
        $currentUserId = auth()->id();
        $newStatus = 'Outgoing_finalized';
        $finalizedAt = now();

        if ($dvRequest->status !== 'Outgoing_received') {
            return redirect()->back()->with('error', 'This DV request cannot be finalized yet.');
        }

        // Generate DV Number
        $year  = date('Y');
        $month = date('m');
        $series = $dvRequest->accounting_control_number;
        $seriesFormatted = str_pad($series, 4, '0', STR_PAD_LEFT);
        $dvNumber = "{$year}-{$month}-{$seriesFormatted}";

        if (empty($dvRequest->accounting_control_number)) {
            return redirect()->back()->with('error', 'Accounting Control Number is required before finalization.');
        }

        $qrCode = $this->generateDvQrCode($dvRequest->id, $dvNumber);

        DB::transaction(function () use (
            $dvRequest, 
            $statusBefore, 
            $finalizedAt, 
            $request, 
            $dvNumber,
            $qrCode,
            $currentUserId,
            $newStatus
        ) {
            // Call the trait method to set department timestamps
            $this->updateDepartmentTimestamp($dvRequest, $newStatus);
            
            // Refresh the model to get updated timestamps
            $dvRequest->refresh();
            
            // Now manually update the other timestamp fields
            $dvRequest->update([
                'last_action_at' => $finalizedAt,
                'last_action_type' => 'finalized',
                'last_acted_by' => $currentUserId,
                'status' => $newStatus,
                'dv_number' => $dvNumber,
                'locked_by' => null,
                'locked_at' => null,
            ]);

            $attachmentData = [];

            // Handle GIP Attachments
            if ($dvRequest->program->prcode === 'GIP') {
                $gipInput = $request->input('gip_attachments', []);
                $allGipIds = $dvRequest->gipAttachments()->pluck('id')->toArray();
                
                foreach ($allGipIds as $attachmentId) {
                    $attachment = $dvRequest->gipAttachments()->find($attachmentId);
                    
                    if ($attachment) {
                        $isPresent = isset($gipInput[$attachmentId]) && $gipInput[$attachmentId] === 'true';
                        
                        $attachment->update([
                            'is_present' => $isPresent,
                            'remarks' => $isPresent 
                                ? 'Marked as present by Outgoing' 
                                : 'Marked as not present by Outgoing',
                            'updated_at' => now()
                        ]);
                        
                        $attachmentData[] = [
                            'attachment_id' => $attachmentId,
                            'name' => $attachment->name,
                            'is_present' => $isPresent,
                            'status' => $isPresent ? 'Present' : 'Not Present'
                        ];
                    }
                }
            }

            // Handle TUPAD Attachments
            if ($dvRequest->program->prcode === 'TUPAD' && $dvRequest->tupad_modality) {
                $tupadInput = $request->input('attachments', []);
                $allTupadIds = $dvRequest->tupadAttachments()
                    ->where('modality', $dvRequest->tupad_modality)
                    ->pluck('id')
                    ->toArray();
                
                foreach ($allTupadIds as $attachmentId) {
                    $attachment = $dvRequest->tupadAttachments()->find($attachmentId);
                    
                    if ($attachment) {
                        $isPresent = isset($tupadInput[$attachmentId]) && $tupadInput[$attachmentId] === 'true';
                        
                        $attachment->update([
                            'is_present' => $isPresent,
                            'remarks' => $isPresent 
                                ? 'Marked as present by Outgoing' 
                                : 'Marked as not present by Outgoing',
                            'updated_at' => now()
                        ]);
                        
                        $attachmentData[] = [
                            'attachment_id' => $attachmentId,
                            'name' => $attachment->name,
                            'modality' => $dvRequest->tupad_modality,
                            'is_present' => $isPresent,
                            'status' => $isPresent ? 'Present' : 'Not Present'
                        ];
                    }
                }
            }

            // Handle TEV Attachments
            if ($dvRequest->program->prcode === 'TEV' && $dvRequest->tev_modality) {
                $tevInput = $request->input('tev_attachments', []);
                $allTevIds = $dvRequest->tevAttachments()
                    ->where('modality', $dvRequest->tev_modality)
                    ->pluck('id')
                    ->toArray();
                
                foreach ($allTevIds as $attachmentId) {
                    $attachment = $dvRequest->tevAttachments()->find($attachmentId);
                    
                    if ($attachment) {
                        $isPresent = isset($tevInput[$attachmentId]) && $tevInput[$attachmentId] === 'true';
                        
                        $attachment->update([
                            'is_present' => $isPresent,
                            'remarks' => $isPresent 
                                ? 'Marked as present by Outgoing' 
                                : 'Marked as not present by Outgoing',
                            'updated_at' => now()
                        ]);
                        
                        $attachmentData[] = [
                            'attachment_id' => $attachmentId,
                            'name' => $attachment->name,
                            'modality' => $dvRequest->tev_modality,
                            'is_present' => $isPresent,
                            'status' => $isPresent ? 'Present' : 'Not Present'
                        ];
                    }
                }
            }

            // Update focal person's DV number
            if ($dvRequest->focalPerson) {
                $dvRequest->focalPerson->update([
                    'dv_number' => $dvNumber
                ]);
            }

            // Save finalization history - USE HELPER FUNCTION FOR DATE CONVERSION
            $this->saveOutgoingFinalizationHistory(
                $dvRequest,
                'finalize',
                $request->remarks ?? "DV finalized with DV Number: {$dvNumber}",
                [
                    'status_before' => $statusBefore,
                    'status_after' => $newStatus,
                    'dv_number' => $dvNumber,
                    'attachments_checked' => $attachmentData,
                    'qr_code_generated' => !empty($qrCode),
                    'finalized_by' => $currentUserId,
                    'finalized_at' => $finalizedAt->toISOString(),
                    'outgoing_verified_at' => $this->safeDateToISO($dvRequest->outgoing_verified_at),
                    'outgoing_last_action_at' => $this->safeDateToISO($dvRequest->outgoing_last_action_at),
                    'last_action_at' => $this->safeDateToISO($dvRequest->last_action_at),
                    'amount' => $dvRequest->amount,
                    'program' => $dvRequest->program->prcode ?? null
                ]
            );

            $focalId = $dvRequest->focalperson_id;
            $remarks = ($request->remarks ?? 'DV finalized by Outgoing') . " | DV Number: {$dvNumber}";

            Notification::create([
                'focalperson_id' => $focalId,
                'dv_request_id'  => $dvRequest->id,
                'status'         => $newStatus,
                'action'         => 'Finalized',
                'remarks'        => $remarks,
            ]);

            $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status'        => $newStatus,
                'dv_number'     => $dvNumber,
                'remarks'       => $remarks,
                'timestamp'     => $finalizedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            $this->pusher->trigger('dv-requests-outgoing', 'dv-request-finalized', [
                'dv_request_id' => $dvRequest->id,
                'dv_number'     => $dvNumber,
                'status'        => $newStatus,
                'action_performer_id' => $currentUserId,
                'outgoing_verified_at' => $this->safeDateToISO($dvRequest->outgoing_verified_at),
                'outgoing_last_action_at' => $this->safeDateToISO($dvRequest->outgoing_last_action_at),
                'last_action_at' => $this->safeDateToISO($dvRequest->last_action_at),
                'timestamp'     => $finalizedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

           // ✅ CRITICAL: Trigger Pusher event for RANCA with is_urgent flag
            $this->pusher->trigger('dv-requests-ranca', 'dv-request-finalized', [
                'dv_request_id' => $dvRequest->id,
                'control_number' => $dvRequest->accounting_control_number ?
                    'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) :
                    'Pending',
                'dv_number' => $dvRequest->dv_number ?? '—',
                'ors_number' => $dvRequest->orsRequest->ors_number ?? '—',
                'status' => $newStatus,
                'submitted_by' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                'program' => $dvRequest->program->prcode ?? 'N/A',
                'particulars' => $dvRequest->particulars ?? 'N/A',
                'amount' => number_format($dvRequest->amount, 2),
                'timestamp' => $finalizedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'action_performer_id' => $currentUserId,
                'outgoing_finalized_at' => $finalizedAt->toISOString(),
                // ✅ CRITICAL: Pass the urgent flag to RANCA
                'is_urgent' => $dvRequest->is_urgent ?? false,
                'days_old' => now()->diffInDays($dvRequest->outgoing_received_at ?? $dvRequest->budget_verified_at ?? $dvRequest->created_at),
            ]);


            $this->logAction(
                $dvRequest->id,
                'Outgoing',
                $newStatus,
                $remarks . ' | Outgoing verified at: ' . $this->formatDateForLog($dvRequest->outgoing_verified_at)
            );
        });

        $this->triggerDashboardUpdate();

        return redirect()->route('Outgoing.dvrequests.preview', $dvRequest->id)
            ->with('success', "DV Request finalized with DV Number: {$dvNumber}")
            ->with('print_modal', true);

    } catch (\Exception $e) {
        Log::error('Error finalizing DV request: ' . $e->getMessage(), [
            'dv_request_id' => $id,
            'error' => $e->getTraceAsString()
        ]);
        
        return redirect()->back()->with('error', 'An error occurred while finalizing the DV request: ' . $e->getMessage());
    }
}

// Add these helper methods to the controller class:

/**
 * Safely convert a date to ISO string format
 */
protected function safeDateToISO($date)
{
    if (!$date) {
        return null;
    }
    
    if (is_string($date)) {
        try {
            $date = \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            Log::warning('Failed to parse date string: ' . $date);
            return null;
        }
    }
    
    if ($date instanceof \Carbon\Carbon) {
        return $date->toISOString();
    }
    
    return null;
}

/**
 * Format date for logging purposes
 */
protected function formatDateForLog($date)
{
    if (!$date) {
        return 'Not set';
    }
    
    if (is_string($date)) {
        try {
            $date = \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return 'Invalid date format';
        }
    }
    
    if ($date instanceof \Carbon\Carbon) {
        return $date->format('M d, Y H:i:s');
    }
    
    return 'Not set';
}

    private function generateDvQrCode($dvRequestId, $dvNumber)
    {
        try {
            Log::info('Starting QR Code Generation via API', [
                'dv_request_id' => $dvRequestId,
                'dv_number' => $dvNumber,
            ]);

            $dvRequest = DvRequest::find($dvRequestId);
            
            if (!$dvRequest) {
                Log::error('DV Request not found for QR generation', ['dv_id' => $dvRequestId]);
                return null;
            }

            if ($dvRequest->qr_code_data) {
                Log::info('Using existing QR code from database');
                return $dvRequest->qr_code_data;
            }

            $token = $dvRequest->qr_code_token ?? hash('sha256', $dvRequestId . $dvNumber . now()->timestamp . uniqid());
            $verifyUrl = route('dv.qr.update-status', ['token' => $token]);
            
            $qrCodeContent = null;
            
            $apiUrl1 = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&format=png&data=' . urlencode($verifyUrl);
            $qrCodeContent = @file_get_contents($apiUrl1);
            
            if ($qrCodeContent === false || empty($qrCodeContent)) {
                $apiUrl2 = 'https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=' . urlencode($verifyUrl) . '&choe=UTF-8';
                $qrCodeContent = @file_get_contents($apiUrl2);
            }
            
            if ($qrCodeContent === false || empty($qrCodeContent)) {
                $apiUrl3 = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($verifyUrl);
                $qrCodeContent = @file_get_contents($apiUrl3);
            }

            if ($qrCodeContent === false || empty($qrCodeContent)) {
                Log::error('All QR code APIs failed');
                throw new \Exception('Failed to generate QR code from external APIs');
            }

            $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCodeContent);

            if (empty($base64QrCode)) {
                throw new \Exception('Failed to encode QR code image to base64');   
            }

            $updateData = [
                'qr_code_token' => $token,
                'qr_code_generated_at' => now(),
                'qr_status' => 'inactive',
                'qr_code_data' => $base64QrCode,
            ];

            $updated = $dvRequest->update($updateData);

            if (!$updated) {
                Log::error('Failed to update DV request with QR code data');
                throw new \Exception('Failed to save QR code to database');
            }

            Log::info('QR Code generated via API successfully', [
                'dv_id' => $dvRequestId,
                'dv_number' => $dvNumber,
                'token' => substr($token, 0, 10) . '...',
                'qr_status' => 'inactive',
            ]);

            return $base64QrCode;

        } catch (\Exception $e) {
            Log::error('QR Code Generation Error: ' . $e->getMessage(), [
                'dv_id' => $dvRequestId,
                'dv_number' => $dvNumber,
            ]);
            return null;
        }
    }

    public function getQrCodeForDv($id)
    {
        try {
            $dvRequest = DvRequest::findOrFail($id);
            
            if ($dvRequest->qr_code_token && $dvRequest->qr_code_data) {
                return response()->json([
                    'success' => true,
                    'qr_code' => $dvRequest->qr_code_data,
                    'dv_number' => $dvRequest->dv_number
                ]);
            }
            
            if ($dvRequest->dv_number) {
                $qrCode = $this->generateDvQrCode($dvRequest->id, $dvRequest->dv_number);
                
                if ($qrCode) {
                    return response()->json([
                        'success' => true,
                        'qr_code' => $qrCode,
                        'dv_number' => $dvRequest->dv_number
                    ]);
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate QR code'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Error fetching QR code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating QR code'
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);
    
        try {
            $dvRequest = DvRequest::findOrFail($id);
            $statusBefore = $dvRequest->status;
            $currentUserId = auth()->id();
            $rejectedAt = now();
            $newStatus = 'Outgoing_rejected';

            if (!in_array($dvRequest->status, ['Outgoing_received'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This DV request cannot be rejected at its current stage.'
                ], 422);
            }

            $focalId = $dvRequest->focalperson_id;
            $remarks = trim($request->remarks);

            // Initialize notification variables
            $emailSent = false;
            $smsSent = false;

            DB::transaction(function () use (
                $dvRequest, 
                $statusBefore, 
                $rejectedAt, 
                $remarks,
                $focalId,
                $currentUserId,
                $newStatus,
                &$emailSent,
                &$smsSent
            ) {
                // Archive to history table
                $this->archiveToHistory($dvRequest, $newStatus, $remarks);
                
                // Update Outgoing specific timestamps
                $dvRequest->update([
                    'outgoing_last_action_at' => $rejectedAt,
                    'last_action_at' => $rejectedAt,
                    'last_action_type' => 'rejected',
                    'last_acted_by' => $currentUserId,
                ]);
                
                // Update DV request
                $dvRequest->update([
                    'status'     => $newStatus,
                    'locked_by'  => null,
                    'locked_at'  => null,
                ]);

                // Send rejection notifications
                $emailSent = $this->sendRejectionEmail($dvRequest, $remarks);
                $smsSent = $this->sendRejectionSMS($dvRequest, $remarks);

                // Save finalization history
                $this->saveOutgoingFinalizationHistory(
                    $dvRequest,
                    'reject',
                    $remarks,
                    [
                        'status_before' => $statusBefore,
                        'status_after' => $newStatus,
                        'rejected_by' => $currentUserId,
                        'rejected_at' => $rejectedAt->toISOString(),
                        'rejection_reason' => $remarks,
                        'email_sent' => $emailSent,
                        'sms_sent' => $smsSent,
                        'outgoing_last_action_at' => $rejectedAt->toISOString(),
                        'last_action_at' => $rejectedAt->toISOString(),
                    ]
                );

                $this->logAction(
                    $dvRequest->id,
                    'Outgoing',
                    $newStatus,
                    "Rejected by Outgoing: {$remarks}. Outgoing last action at: " . $rejectedAt->format('M d, Y H:i:s')
                );

                Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $newStatus,
                    'action'         => 'Rejected',
                    'remarks'        => $remarks,
                ]);

                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => $newStatus,
                    'remarks'       => $remarks,
                    'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);

                $this->pusher->trigger('dv-requests-outgoing', 'dv-request-rejected', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => $newStatus,
                    'action_performer_id' => $currentUserId,
                    'outgoing_last_action_at' => $rejectedAt->toISOString(),
                    'last_action_at' => $rejectedAt->toISOString(),
                    'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            });

            $this->triggerDashboardUpdate();

            $responseMessage = 'DV request has been rejected';
            
            if ($emailSent && $smsSent) {
                $responseMessage .= ' and notifications sent.';
            } elseif ($emailSent) {
                $responseMessage .= ' (email sent, SMS failed).';
            } elseif ($smsSent) {
                $responseMessage .= ' (SMS sent, email failed).';
            } else {
                $responseMessage .= ' (both notifications failed).';
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $responseMessage,
                    'data' => [
                        'id'         => $dvRequest->id,
                        'status'     => $newStatus,
                        'remarks'    => $remarks,
                        'email_sent' => $emailSent,
                        'sms_sent'   => $smsSent,
                        'outgoing_last_action_at' => $rejectedAt->format('M d, Y H:i:s'),
                        'last_action_at' => $rejectedAt->format('M d, Y H:i:s'),
                    ]
                ]);
            }

            return redirect()->route('Outgoing.dvrequests.index')
                             ->with('success', $responseMessage);

        } catch (\Exception $e) {
            Log::error('Error rejecting DV request: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while rejecting the DV request.'
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while rejecting the DV request.');
        }
    }

    protected function sendRejectionSMS($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            if (!$focalPerson || empty($focalPerson->contactnumber)) {
                Log::warning('Cannot send rejection SMS: focal person or contact number missing', [
                    'dv_request_id' => $dvRequest->id,
                    'focal_person_id' => $focalPerson->id ?? 'null',
                    'has_contactnumber' => !empty($focalPerson->contactnumber)
                ]);
                return false;
            }
    
            $phoneNumber = $focalPerson->contactnumber;
            
            $truncatedRemarks = strlen($rejectionRemarks) > 50 
                ? substr($rejectionRemarks, 0, 47) . '...' 
                : $rejectionRemarks;

            $userSequence = $dvRequest->user_sequence ?? 'N/A';
            $message = "Hi {$focalPerson->fname}, your DV Request #{$userSequence} has been rejected by Outgoing. Reason: {$truncatedRemarks}.";

            Log::info('Sending Outgoing rejection SMS via iProg', [
                'phone' => $phoneNumber,
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $userSequence,
                'message_length' => strlen($message)
            ]);

            $result = $this->smsService->sendSMS($phoneNumber, $message);
            
            if ($result['success']) {
                Log::info('Outgoing rejection SMS sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'user_sequence' => $userSequence,
                    'phone' => $phoneNumber,
                    'message_id' => $result['message_id'] ?? 'N/A'
                ]);
                return true;
            } else {
                Log::error('Failed to send Outgoing rejection SMS', [
                    'dv_request_id' => $dvRequest->id,
                    'user_sequence' => $userSequence,
                    'phone' => $phoneNumber,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('Outgoing SMS rejection notification failed: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $dvRequest->user_sequence ?? 'N/A',
                'exception' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function printOfficialDv($id)
    {
        $dvRequest = DvRequest::with([
            'program',
            'focalPerson',
            'respocenter',
            'orsRequest.uacsCodes',
        ])->findOrFail($id);

        if ($dvRequest->status !== 'Outgoing_finalized') {
            return redirect()->back()->with('error', 'This DV has not been finalized yet.');
        }

        if (!$dvRequest->dv_number) {
            return redirect()->back()->with('error', 'DV Number has not been assigned yet.');
        }

        return view('Outgoing.print-official-dv', compact('dvRequest'));
    }

    public function previewDv($id)
    {
        try {
            Log::info('Accessing previewDv for ID: ' . $id);
            
            $dvRequest = DvRequest::with([
                'program',
                'focalPerson',
                'respocenter',
                'modeOfPayments',
                'orsRequest.uacsCodes',
                'orsRequest.Mfopap'
            ])->findOrFail($id);
            
            Log::info('DV Request found:', [
                'id' => $dvRequest->id,
                'status' => $dvRequest->status,
                'dv_number' => $dvRequest->dv_number,
                'finalized' => $dvRequest->status === 'Outgoing_finalized'
            ]);

            if (!$dvRequest->dv_number) {
                Log::warning('DV number missing for preview', ['id' => $dvRequest->id]);
                return redirect()->back()->with('error', 'DV Number has not been assigned yet.');
            }

            $modeOfPayment = $dvRequest->modeOfPayments->first();
            $paymentDetail = $modeOfPayment?->pivot?->paymentDetail;
            $orsRequest = $dvRequest->orsRequest;
            $uacsCodes = $orsRequest && $orsRequest->uacsCodes ? $orsRequest->uacsCodes : collect();

            // Generate or retrieve QR code
            if ($dvRequest->qr_code_data) {
                $qrCode = $dvRequest->qr_code_data;
            } else {
                $qrCode = $this->generateDvQrCode($dvRequest->id, $dvRequest->dv_number);
                $dvRequest->refresh();
            }
            
            $data = [
                'dvRequest' => $dvRequest,
                'date' => $dvRequest->created_at->format('m/d/Y'),
                'dv_no' => $dvRequest->dv_number,
                'mode_of_payment' => $modeOfPayment?->mopname ?? 'Not specified',
                'payee' => $dvRequest->payee_name ?? ($dvRequest->focalPerson ? 
                        trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')) : 'N/A'),
                'tin' => $dvRequest->focalPerson->TIN_number ?? $dvRequest->focalPerson->tin_number ?? '___________',
                'amount' => number_format($dvRequest->amount, 2),
                'address' => $dvRequest->Address ?? $dvRequest->payee_address ?? 'DAVAO CITY',
                'particulars' => $dvRequest->particulars ?? $dvRequest->purpose ?? 'N/A',
                'respocenter_code' => $dvRequest->respocenter->rccode ?? '',
                'respocenter_desc' => $dvRequest->respocenter->description ?? '',
                'mfocode' => $orsRequest && $orsRequest->mfopap ? ($orsRequest->mfopap->mfocode ?? '') : '',
                'mfodescription' => $orsRequest && $orsRequest->mfopap ? ($orsRequest->mfopap->mfodescription ?? '') : '',
                'uacsCodes' => $uacsCodes,
                'bank_name' => $paymentDetail?->bank_name ?? '',
                'bank_accnum' => $paymentDetail?->bank_accnum ?? '',
                'bank_address' => $paymentDetail?->bank_address ?? '',
                'ors_number' => $orsRequest->ors_number ?? '___________',
                'imsd_head' => \App\Models\Setting::get('imsd_head', 'NAME OF IMSD HEAD'),
                'accountant_name' => \App\Models\Setting::get('accountant_name', 'NAME OF ACCOUNTANT'),
                'regional_director' => \App\Models\Setting::get('regional_director', 'NAME OF REGIONAL DIRECTOR'),
            ];

            Log::info('Successfully loading preview for DV: ' . $dvRequest->dv_number);
            
            return view('Outgoing.previewdv', [
                'data' => $data,
                'dvRequest' => $dvRequest,
                'orsRequest' => $orsRequest,
                'uacsCodes' => $uacsCodes,
                'qrCode' => $qrCode,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in previewDv: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error accessing DV preview: ' . $e->getMessage());
        }
    }

    public function generatePdf($id)
    {
        try {
            Log::info('=== DV PDF Generation Started ===', ['id' => $id]);
            
            $dvRequest = DvRequest::with([
                'program',
                'focalPerson', 
                'respocenter',
                'modeOfPayments',
                'orsRequest.uacsCodes',
                'orsRequest.Mfopap',
                'orsRequest.allotmentClass',
                'orsRequest.continuingOrCurrent'
            ])->findOrFail($id);

            if ($dvRequest->status !== 'Outgoing_finalized') {
                return redirect()->back()->with('error', 'This DV has not been finalized yet.');
            }

            if (!$dvRequest->dv_number) {
                return redirect()->back()->with('error', 'DV Number has not been assigned yet.');
            }

            // Logo handling
            $logo_url = null;
            $logoPath = '/home/u203167746/domains/accudocs.accudocs.site/AccudocsProject/storage/app/public/logo/DOLE.png';
            
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $base64 = base64_encode($imageData);
                $logo_url = 'data:image/png;base64,' . $base64;
                Log::info('Logo loaded for DV PDF');
            } else {
                $svg = '<svg width="200" height="100" xmlns="http://www.w3.org/2000/svg">
                    <rect width="200" height="100" fill="#f8f9fa" stroke="#dee2e6"/>
                    <text x="100" y="50" text-anchor="middle" dy=".3em" font-family="Arial" font-size="12" fill="#6c757d">DOLE LOGO</text>
                </svg>';
                $logo_url = 'data:image/svg+xml;base64,' . base64_encode($svg);
                Log::info('Using SVG fallback logo for DV PDF');
            }

            // Get payment details
            $modeOfPayment = $dvRequest->modeOfPayments->first();
            $paymentDetail = null;
            
            if (method_exists($dvRequest, 'paymentDetail')) {
                $paymentDetail = $dvRequest->paymentDetail;
            } elseif ($modeOfPayment && method_exists($modeOfPayment->pivot, 'paymentDetail')) {
                $paymentDetail = $modeOfPayment->pivot->paymentDetail;
            }

            $orsRequest = $dvRequest->orsRequest;
            $uacsCodes = $orsRequest && $orsRequest->uacsCodes ? $orsRequest->uacsCodes : collect();    

            // QR Code handling
            $qrCode = $dvRequest->qr_code_data;
            
            if (!$qrCode) {
                Log::info('Generating QR code for DV', ['dv_no' => $dvRequest->dv_number]);
                $qrCode = $this->generateDvQrCode($dvRequest->id, $dvRequest->dv_number);
                $dvRequest->update(['qr_code_data' => $qrCode]);
                Log::info('QR code generated and saved');
            }

            // Prepare UACS data
            $uacsItems = [];
            foreach ($uacsCodes as $uacs) {
                $amount = $uacs->pivot->amount ?? 0;
                $uacsItems[] = [
                    'code' => $uacs->uacscode ?? '',
                    'description' => $uacs->description ?? '',
                    'amount' => $amount
                ];
            }

            // Prepare data for PDF
            $data = [
                'date' => $dvRequest->created_at->format('F d, Y'),
                'dv_no' => $dvRequest->dv_number,
                'mode_of_payment' => $modeOfPayment?->mopname ?? 'Not specified',
                'payee' => $this->formatFullName(
                    $dvRequest->focalPerson->fname ?? '',
                    $dvRequest->focalPerson->mi ?? '',
                    $dvRequest->focalPerson->lname ?? ''
                ),
                'tin' => $dvRequest->focalPerson->TIN_number ?? '___________',
                'ors_number' => $orsRequest->ors_number ?? '___________',
                'address' => $dvRequest->focalPerson->Address ?? 'DAVAO CITY',
                'particulars' => $dvRequest->particulars ?? 'N/A',
                'amount' => number_format($dvRequest->amount, 2),
                'respocenter_code' => $dvRequest->respocenter->rccode ?? '',
                'respocenter_desc' => $dvRequest->respocenter->description ?? '',
                'mfocode' => $orsRequest->Mfopap->mfocode ?? '',
                'mfodescription' => $orsRequest->Mfopap->mfodescription ?? '',
                'bank_name' => $paymentDetail?->bank_name ?? '',
                'bank_accnum' => $paymentDetail?->bank_accnum ?? '',
                'bank_address' => $paymentDetail?->bank_address ?? '',
                'responsibility_center' => $dvRequest->respocenter ? 
                    ($dvRequest->respocenter->rccode . ' - ' . $dvRequest->respocenter->description) : '',
                'mfo' => $orsRequest->Mfopap ? 
                    ($orsRequest->Mfopap->mfocode . ' - ' . $orsRequest->Mfopap->mfodescription) : '',
                'uacs_items' => $uacsItems,
                'ors_no' => $dvRequest->orsRequest->ors_number ?? '____________',
                'logo_url' => $logo_url
            ];

            // Get settings
            $imsd_head = \App\Models\Setting::get('imsd_head', 'NAME OF IMSD HEAD');
            $accountant_name = \App\Models\Setting::get('accountant_name', 'NAME OF ACCOUNTANT');
            $regional_director = \App\Models\Setting::get('regional_director', 'NAME OF REGIONAL DIRECTOR');
            $budget_officer = \App\Models\Setting::get('budget_officer', 'BUDGET OFFICER\'S NAME');

            // Prepare status data for the table
            $status = [
                [
                    'date' => $dvRequest->created_at->format('m/d/Y'),
                    'particulars' => $dvRequest->particulars ?? '',
                    'ref_no' => $dvRequest->orsRequest->ors_number ?? '____________',
                    'obligation' => number_format($dvRequest->amount, 2),
                    'payment' => '',
                    'due_status' => 'Not yet Due'
                ]
            ];

            Log::info('DV PDF Data Prepared', [
                'dv_no' => $data['dv_no'],
                'payee' => $data['payee'],
                'amount' => $data['amount'],
                'uacs_count' => count($data['uacs_items'])
            ]);

            // Use manual DOMPDF instance
            $dompdf = new \Dompdf\Dompdf();
            
            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Times New Roman');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);
            $options->set('isFontSubsettingEnabled', true);
            $options->set('chroot', base_path());
            $options->set('tempDir', sys_get_temp_dir());
            $options->set('fontDir', storage_path('fonts'));
            $options->set('fontCache', storage_path('fonts'));
            
            $dompdf->setOptions($options);
            $dompdf->setPaper('Legal', 'portrait');

            // Load HTML content
            $html = view('pdf.finaldv', compact(
                'data', 
                'imsd_head',
                'accountant_name',
                'regional_director',
                'budget_officer',
                'qrCode',
                'status',
                'uacsCodes',
                'logo_url'
            ))->render();

            $dompdf->loadHtml($html);
            $dompdf->render();

            $filename = 'DV_' . str_replace('-', '_', $dvRequest->dv_number) . '_' . now()->format('Ymd_His') . '.pdf';
            
            Log::info('DV PDF Generated Successfully', ['filename' => $filename]);
            
            return $dompdf->stream($filename);

        } catch (\Exception $e) {
            Log::error('PDF Generation Error: ' . $e->getMessage(), [
                'dv_request_id' => $id,
                'error' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    private function formatFullName($fname, $mi, $lname)
    {
        $name = trim($fname . ' ' . ($mi ? $mi . ' ' : '') . $lname);
        return $name ?: 'NAME OF PAYEE';
    }

    public function previewFromHistory($id)
    {
        try {
            $dvRequest = DvRequest::with([
                'program',
                'focalPerson',
                'respocenter',
                'orsRequest'
            ])->findOrFail($id);

            $warning = null;
            if ($dvRequest->status !== 'Outgoing_finalized') {
                $warning = 'Note: This DV request is not yet finalized.';
            }

            return view('Outgoing.history-preview', compact('dvRequest', 'warning'));
            
        } catch (\Exception $e) {
            Log::error('Error in previewFromHistory: ' . $e->getMessage());
            return redirect()->route('dvrequests.dvloghistory')
                ->with('error', 'Unable to view DV request: ' . $e->getMessage());
        }
    }

    public function releaseLock($id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->locked_by === auth()->id()) {
            $dvRequest->update([
                'locked_by' => null,
                'locked_at' => null,
                'status' =>'ORS_obligated',
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
        $query = OutgoingFinalizationHistory::with([
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

        if ($verificationHistory->count() > 0) {
            Log::info('First record data:', [
                'dv_request_id' => $verificationHistory->first()->dv_request_id,
                'has_dvRequest' => !is_null($verificationHistory->first()->dvRequest),
                'has_program' => !is_null($verificationHistory->first()->dvRequest->program ?? null),
                'has_focalPerson' => !is_null($verificationHistory->first()->dvRequest->focalPerson ?? null),
                'verification_data' => $verificationHistory->first()->verification_data,
            ]);
        } else {
            Log::info('No verification history records found for the given filters.');
        }
                                    
        return view('Outgoing.dvloghistory', compact('verificationHistory'));
    }

    protected function saveOutgoingFinalizationHistory($dvRequest, $step, $remarks = null, $data = [])
    { 
        try { 
            OutgoingFinalizationHistory::create([
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
            Log::error('Failed to save Outgoing verification history', [
                'dv_request_id' => $dvRequest->id ?? null,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
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

            if (!class_exists(SendPulseServices::class)) {
                Log::error('SendPulseServices class not found');
                return $this->sendFallbackEmail($toEmail, $toName, $dvRequest, 'Outgoing_rejected', $rejectionRemarks);
            }

            $sendPulseService = new SendPulseServices();
            $result = $sendPulseService->sendRejectionEmail($toEmail, $toName, $dvRequest, $rejectionRemarks);
            
            if ($result) {
                Log::info('SendPulse rejection email sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'to_email' => $toEmail
                ]);
            } else {
                Log::error('SendPulse failed to send rejection email', [
                    'dv_request_id' => $dvRequest->id,
                    'to_email' => $toEmail
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('SendPulse rejection email failed: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id,
                'exception' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function sendFallbackEmail($toEmail, $toName, $dvRequest, $status, $remarks = null)
    {
        try {
            Log::info('Using fallback SMTP email for status: ' . $status);
            
            $subject = "DV Request Status Updated - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            $htmlContent = view('emails.dv_status_updated', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'status' => $status,
                'remarks' => $remarks,
                'updatedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'updatedBy' => auth()->user()->name ?? 'Outgoing User'
            ])->render();

            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($toEmail, $toName, $subject, $htmlContent) {
                $message->to($toEmail, $toName)
                       ->subject($subject)
                       ->from(config('mail.from.address'), config('mail.from.name'))
                       ->html($htmlContent);
            });

            Log::info('Fallback SMTP email sent successfully', ['to' => $toEmail, 'status' => $status]);
            return true;
        } catch (\Exception $e) {
            Log::error('Fallback SMTP also failed: ' . $e->getMessage());
            return false;
        }
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
            'outgoing_received_at' => $dvRequest->outgoing_received_at,
            'outgoing_verified_at' => $dvRequest->outgoing_verified_at,
            'outgoing_last_action_at' => $dvRequest->outgoing_last_action_at,
            'history_reason' => $reason,
            'moved_to_history_at' => now(),
            'moved_by' => auth()->id(),
        ]);
    }
}