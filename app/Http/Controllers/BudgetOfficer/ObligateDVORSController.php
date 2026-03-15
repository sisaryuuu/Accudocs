<?php

namespace App\Http\Controllers\BudgetOfficer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrsRequest;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\DvRequestHistory;
use App\Models\Program;
use App\Services\PusherService;
use App\Services\SendPulseServices;
use App\Services\IprogSmsService;
use App\Models\Notification;
use App\Models\BudgetObligationHistory;
use App\Models\DocumentAgingAlert;
use App\Models\UacsCode;
use App\Models\AdlCode;
use App\Models\AllotmentClass;
use App\Models\MfoPap;
use App\Models\ContinuingOrCurrent;
use App\Traits\TriggersDashboardUpdate;
use App\Traits\BudgetThresholdAnalysis;
use App\Traits\HandlesDepartmentTransitions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ObligateDVORSController extends Controller
{
    use TriggersDashboardUpdate, BudgetThresholdAnalysis, HandlesDepartmentTransitions;

    protected PusherService $pusher;
    protected IprogSmsService $smsService;

    public function __construct(PusherService $pusher, IprogSmsService $smsService)
    {
        $this->pusher = $pusher;
        $this->smsService = $smsService;
    }

    public function index()
    {
        return view('BudgetOfficer.index');
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
                'orsRequest.uacsCodes',
                'orsRequest.AdlCode',
                'orsRequest.Mfopap'
            ])
            ->whereIn('status', [
                'Incoming-TUPAD_verified', 
                'Incoming-GIP_verified',
                'Incoming-TEV_verified',
                'ORS_received',
                'ORS_in_process',
                'ORS_onhold',
                'ORS_for_revision',
            ])
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
                      });
                });
            });

        $dvRequests = $query->get();
        
        $dvRequests = $dvRequests->map(function ($request) {
            $baseDate = $request->budget_received_at ?? $request->created_at;
            $daysOld = now()->diffInDays($baseDate);
            
            $priorityScore = 0;
            
            // ✅ URGENT flag - highest priority (50,000 points)
            if ($request->is_urgent) {
                $priorityScore += 50000;
            }
            
            if ($daysOld >= 10) {
                $priorityScore += 1000;
            } elseif ($daysOld >= 7) {
                $priorityScore += 500;
            } elseif ($daysOld >= 5) {
                $priorityScore += 200;
            } elseif ($daysOld >= 3) {
                $priorityScore += 100;
            }
            
            if (in_array($request->status, ['Incoming-TUPAD_verified', 'Incoming-GIP_verified', 'Incoming-TEV_verified'])) {
                $priorityScore += 50;
            } elseif ($request->status === 'ORS_received') {
                $priorityScore += 25;
            }
            
            if ($request->status === 'ORS_onhold' || $request->status === 'ORS_for_revision') {
                $priorityScore += 100;
            }
            
            $priorityScore += rand(0, 10);
            
            $request->days_old = $daysOld;
            $request->priority_score = $priorityScore;
            $request->urgency_level = $this->getUrgencyLevel($daysOld);
            
            return $request;
        });
        
        $dvRequests = $dvRequests->sortByDesc('priority_score')->values();

        $result = $dvRequests->map(function ($req) {
            $dateUsed = $req->budget_received_at ? 'budget_received_at' : 'created_at';
            $baseDate = $req->budget_received_at ?? $req->created_at;
            
            $orsRequest = $req->orsRequest;

            $uacsCodes = $orsRequest ? ($orsRequest->uacsCodes ?? collect()) : collect();
            $uacsDisplay = $uacsCodes->isNotEmpty() 
                ? $uacsCodes->map(function($uacs) {
                    return $uacs->uacscode . ' - ' . $uacs->description;
                })->implode('<br>') : '—';

            $adlCode = ($orsRequest && $orsRequest->AdlCode) ? 
                $orsRequest->AdlCode->adlcode . ' - ' . $orsRequest->AdlCode->description : 
                '—';
            
            $mfopapCode = '—';
            $mfocode = '—';
            $mfodescription = '—';
            
            if ($orsRequest && $orsRequest->Mfopap) {
                $mfocode = $orsRequest->Mfopap->mfocode ?? '—';
                $mfodescription = $orsRequest->Mfopap->mfodescription ?? '—';
                $mfopapCode = $mfocode . ' - ' . $mfodescription;
            }

            $orsNumber = $orsRequest ? ($orsRequest->ors_number ?? null) : null;

            // ✅ FIXED: Separate user-flagged urgent from system-calculated priority
            $isUserFlaggedUrgent = $req->is_urgent ?? false;
            $systemPriorityLevel = $req->urgency_level ?? 'low';
            
            // ✅ Determine display urgency: 
            // - If user flagged urgent -> show "urgent"
            // - Otherwise show system priority (critical/high/medium/low)
            $displayUrgency = $isUserFlaggedUrgent ? 'urgent' : $systemPriorityLevel;

            return [
                'id'                    => $req->id,
                'control_no'            => $req->accounting_control_number ? 
                    'CN #-' . now()->year . '-' . str_pad($req->accounting_control_number, 4, '0', STR_PAD_LEFT) : 
                    '—',
                'ors_number'            => $orsNumber,
                'submitted_by'          => trim(($req->focalPerson->fname ?? '') . ' ' . ($req->focalPerson->lname ?? '')),
                'program'               => $req->program->prcode ?? 'N/A',
                'respocenter'           => trim(($req->respocenter->rccode ?? '') . ' - ' . ($req->respocenter->description ?? '')) ?? 'N/A',
                'particulars'           => $req->particulars,
                'amount'                => number_format($req->amount, 2),
                'status'                => $req->status,
                'date'                  => $baseDate->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'created_at_timestamp'  => $req->created_at->toISOString(),
                'budget_received_at'    => $req->budget_received_at ? $req->budget_received_at->toISOString() : null,
                'budget_last_action_at' => $req->budget_last_action_at ? $req->budget_last_action_at->toISOString() : null,
                'last_action_at'        => $req->last_action_at ? $req->last_action_at->toISOString() : null,
                'days_old'              => $req->days_old ?? 0,
                // ✅ FIXED: These three fields clearly separate the concepts
                'urgency'               => $displayUrgency,                    // What to display in UI
                'is_urgent'             => $isUserFlaggedUrgent,               // User-flagged urgent (true/false)
                'system_priority'       => $systemPriorityLevel,               // System-calculated priority
                'priority_score'        => $req->priority_score ?? 0,
                'date_used_for_age'     => $dateUsed,
                'uacs_codes'            => $uacsDisplay,
                'adl_code'              => $adlCode,
                'mfopap_code'           => $mfopapCode,
                'mfocode'               => $mfocode,
                'mfodescription'        => $mfodescription,
            ];
        });

        return response()->json($result);

    } catch (\Exception $e) {
        \Log::error('Error in Budget Officer fetchDvRequests: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'error' => 'Failed to fetch DV requests: ' . $e->getMessage()
        ], 500);
    }
}

    protected function getUrgencyLevel($daysOld)
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

    public function redirectToFirstPending()
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $pendingRequests = DvRequest::whereHas('program', function($q) {
                                        $q->whereIn('prcode', ['GIP', 'TUPAD', 'TEV']);
                                    }) 
                                    ->whereIn('status', ['Incoming-TUPAD_verified', 'Incoming-GIP_verified','Incoming-TEV_verified','ORS_received'])
                                    ->orderBy('created_at', 'asc')
                                    ->get();

        if ($pendingRequests->isEmpty()) {
            return redirect()->route('BudgetOfficer.dvrequests.index')
                             ->with('info', 'No ORS requests pending verification.');
        }

        $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
            return !$req->locked_by 
                || $req->locked_by == $currentUserId 
                || $req->locked_at <= $lockDuration;
        });

        if ($available) {
            return redirect()->route('BudgetOfficer.dvrequests.withid', $available->id);
        }

        return redirect()->route('BudgetOfficer.index')
                         ->with('info', 'All pending ORS requests are currently locked by other users.');
    }

    public function show($id)
    {
        $dvRequest = DvRequest::with([
            'respocenter',
            'focalPerson',
            'orsRequest',
            'orsRequest.uacsCodes',
            'logs',
            'gipAttachments',
            'tupadAttachments',  
            'tevAttachments',
            'program'
        ])->findOrFail($id);

        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        if ($dvRequest->locked_by && $dvRequest->locked_by !== $currentUserId && $dvRequest->locked_at >= $lockDuration) {
            return redirect()->route('BudgetOfficer.dvrequests.index')
                ->with('error', 'This DV Request is currently being processed by another user.');
        }

        if (!$dvRequest->locked_by || $dvRequest->locked_at < $lockDuration || $dvRequest->locked_by === $currentUserId) {
            $dvRequest->update([
                'locked_by' => $currentUserId,
                'locked_at' => now(),
            ]);
        }
        
        $orsRequest = $dvRequest->orsRequest ?? null;
        $lastLog = $dvRequest->logs()->latest()->first();
        $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;
        
        $uacsCodes = $orsRequest ? $orsRequest->uacsCodes()->orderBy('pivot_order')->get() : collect();
        
        $selectableUacsCodes = UacsCode::selectable()
            ->with('parent.parent.parent')
            ->orderBy('level')
            ->orderBy('sort_order')
            ->orderBy('uacscode')
            ->get()
            ->map(function($uacs) {
                return [
                    'id' => $uacs->id,
                    'code' => $uacs->uacscode,
                    'description' => $uacs->description,
                    'full_path' => $uacs->full_path,
                    'level' => $uacs->level,
                ];
            });

        $attachments = collect();
        $selectedModality = null;
        $tevAttachments = collect();
        $tevSelectedModality = null;
        $tupadAttachments = collect();
        $tupadSelectedModality = null;

        switch ($dvRequest->program->prcode ?? '') {
            case 'GIP':
                $attachments = $dvRequest->gipAttachments ?? collect();
                break;
                
            case 'TUPAD':
                $tupadSelectedModality = $dvRequest->tupad_modality;
                if ($tupadSelectedModality) {
                    $tupadAttachments = $dvRequest->tupadAttachments()
                        ->where('modality', $tupadSelectedModality)
                        ->orderBy('sort_order')
                        ->get();
                }
                break;
                
            case 'TEV':
                $tevSelectedModality = $dvRequest->tev_modality;
                if ($tevSelectedModality) {
                    $tevAttachments = $dvRequest->tevAttachments()
                        ->where('modality', $tevSelectedModality)
                        ->orderBy('sort_order')
                        ->get();
                }
                break;
        }
        
        return view('BudgetOfficer.obligatedvors', [
            'dvRequest'           => $dvRequest,
            'orsRequest'          => $orsRequest,
            'lastStep'            => $lastStep,
            'selectableUacsCodes' => $selectableUacsCodes,
            'adlCodes'            => AdlCode::orderBy('adlcode')->get(),
            'allotment_classes'   => AllotmentClass::all(),
            'mfopaps'             => MfoPap::all(),
            'contcurrents'        => ContinuingOrCurrent::all(),
            'uacsCodes'           => $uacsCodes,
            'amount'              => $dvRequest->amount,
            'attachments'         => $attachments,
            'selectedModality'    => $selectedModality, 
            'tevAttachments'      => $tevAttachments,
            'tevSelectedModality' => $tevSelectedModality,
            'tupadAttachments'    => $tupadAttachments,
            'tupadSelectedModality' => $tupadSelectedModality,
        ]);
    }

    public function receive(Request $request, $id)
    {
        try {
            $dvRequest = DvRequest::with(['orsRequest', 'focalPerson'])->findOrFail($id);
            $statusBefore = $dvRequest->status;
            $receivedAt = now();
            $currentUserId = auth()->id();
            $newStatus = 'ORS_received';

            if ($dvRequest->status === 'ORS_received') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This request has already been received by Budget Officer.'
                    ], 422);
                }
                return redirect()->back()->with('error', 'This request has already been received by Budget Officer.');
            }

            if (!in_array($dvRequest->status, ['Incoming-TUPAD_verified', 'Incoming-GIP_verified','Incoming-TEV_verified'])) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This request cannot be received. Current status: ' . $statusBefore
                    ], 422);
                }
                return redirect()->back()->with('error', 'This request cannot be received. Current status: ' . $statusBefore);
            }

            DB::transaction(function () use (
                $dvRequest, 
                $statusBefore, 
                $receivedAt, 
                $request, 
                $currentUserId,
                $newStatus
            ) {
                $this->updateDepartmentTimestamp($dvRequest, $newStatus);
                
                if ($dvRequest->orsRequest) {
                    $dvRequest->orsRequest->update([
                        'status'            => $newStatus,
                        'gross'             => $request->input('amount', $dvRequest->amount),
                        'accountingunit_id' => $currentUserId,
                        'received_by'       => $currentUserId,
                        'received_at'       => $receivedAt,
                        'budget_received_at' => $receivedAt,
                        'budget_last_action_at' => $receivedAt,
                    ]);
                }

                $dvRequest->update([
                    'status'            => $newStatus,
                    'gross'             => $request->input('amount', $dvRequest->amount),
                    'accountingunit_id' => $currentUserId,
                    'received_by'       => $currentUserId,
                    'received_at'       => $receivedAt,
                    'budget_received_at' => $receivedAt,
                    'budget_last_action_at' => $receivedAt,
                    'locked_by'         => $currentUserId,
                    'locked_at'         => $receivedAt,
                ]);

                $this->saveBudgetObligationHistory(
                    $dvRequest,
                    'receive',
                    'ORS request received by Budget Officer for processing',
                    [
                        'status_before' => $statusBefore,
                        'status_after'  => $newStatus,
                        'received_by' => $currentUserId,
                        'received_at' => $receivedAt->toISOString(),
                        'budget_received_at' => $receivedAt->toISOString(),
                        'budget_last_action_at' => $receivedAt->toISOString(),
                        'last_action_at' => $receivedAt->toISOString(),
                        'amount' => $dvRequest->amount,
                        'ors_request_id' => $dvRequest->orsRequest->id ?? null
                    ]
                );

                $this->logAction(
                    $dvRequest->id,
                    'Budget_Officer',
                    $newStatus,
                    'ORS request received by Budget Officer for processing. Budget Timestamp: ' . 
                    $receivedAt->format('M d, Y H:i:s')
                );

                $focalId = $dvRequest->focalperson_id;
                $remarks = $request->remarks ?? "ORS request received by Budget Officer for processing";

                Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $dvRequest->status,
                    'action'         => 'Received',
                    'remarks'        => $remarks,
                ]);

                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => $dvRequest->status,
                    'remarks'       => $remarks,
                    'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);

                $this->pusher->trigger('dv-requests-budget-officer', 'dv-request-received', [
                    'dv_request_id' => $dvRequest->id,
                    'control_number' => $dvRequest->accounting_control_number ? 
                        'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) : 'Unknown',
                    'status' => $dvRequest->status,
                    'submitted_by' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                    'received_by' => auth()->user()->name ?? 'Budget Officer',
                    'timestamp' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'action_performer_id' => $currentUserId,
                    'budget_received_at' => $receivedAt->toISOString(),
                ]);
            });

            $this->triggerDashboardUpdate();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document has been received by Budget Officer (ORS).',
                    'data' => [
                        'id'          => $dvRequest->id,
                        'status'      => $dvRequest->status,
                        'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'budget_received_at' => $receivedAt->format('M d, Y h:i A'),
                        'budget_last_action_at' => $receivedAt->format('M d, Y H:i:s'),
                        'last_action_at' => $receivedAt->format('M d, Y H:i:s'),
                    ]
                ]);
            }

            return redirect()->route('BudgetOfficer.dvrequests.index')
                ->with('success', 'Document has been received by Budget Officer (ORS).');

        } catch (\Exception $e) {
            \Log::error('Error receiving DV request in Budget Officer: ' . $e->getMessage(), [
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

    public function fetchRecent()
    {
        $currentUserId = auth()->id();
        $lockDuration  = now()->subMinutes(10);
        $recentDvRequest = DvRequest::with('focalPerson') 
            ->whereIn('status', ['Incoming-TUPAD_verified', 'Incoming-GIP_verified','Incoming-TEV_verified', 'ORS_received'])
            ->whereNotNull('focalperson_id')
            ->where(function ($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by')
                  ->orWhere('locked_by', $currentUserId)
                  ->orWhere('locked_at', '<=', $lockDuration);
            })
            ->latest()
            ->take(5)
            ->get();

        return response()->json($recentDvRequest);
    }

    public function updateAmount(Request $request, $id)
    {
        try {
            \Log::info('=== UPDATE AMOUNT REQUEST ===', [
                'dv_request_id' => $id,
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            $validated = $request->validate([
                'amount'  => 'required|numeric|min:0.01',
                'remarks' => 'required|string|max:1000|min:10',
            ], [
                'amount.required' => 'Amount is required',
                'amount.numeric' => 'Amount must be a number',
                'amount.min' => 'Amount must be greater than 0',
                'remarks.required' => 'Please provide a reason for changing the amount',
                'remarks.min' => 'Reason must be at least 10 characters',
            ]);

            $dvRequest = DvRequest::with('orsRequest')->findOrFail($id);

            if (!in_array($dvRequest->status, ['ORS_received', 'ORS_onhold'])) {
                \Log::warning('Amount edit rejected - invalid status', [
                    'dv_request_id' => $id,
                    'current_status' => $dvRequest->status
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Amount can only be edited while request is received or on hold.'
                ], 422);
            }

            $currentUserId = auth()->id();
            $lockDuration = now()->subMinutes(10);
            
            if ($dvRequest->locked_by && 
                $dvRequest->locked_by !== $currentUserId && 
                $dvRequest->locked_at >= $lockDuration) {
                \Log::warning('Amount edit rejected - locked by another user', [
                    'dv_request_id' => $id,
                    'locked_by' => $dvRequest->locked_by,
                    'current_user' => $currentUserId
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'This request is currently locked by another user.'
                ], 422);
            }

            $oldAmount = $dvRequest->amount;
            $newAmount = $validated['amount'];
            $newStatus = 'ORS_in_process';
            $updatedAt = now();

            DB::beginTransaction();

            try {
                $this->updateDepartmentTimestamp($dvRequest, $newStatus);
                
                $dvRequest->update([
                    'amount'    => $newAmount,
                    'budget_last_action_at' => $updatedAt,
                    'locked_by' => $currentUserId,
                    'locked_at' => $updatedAt,
                ]);

                if ($dvRequest->orsRequest) {
                    $dvRequest->orsRequest->update([
                        'gross' => $newAmount,
                        'budget_last_action_at' => $updatedAt,
                    ]);
                }
                
                $this->logAction(
                    $dvRequest->id,
                    'Budget_Officer',
                    'Amount_Edited',
                    "DV/ORS Gross updated from ₱" . number_format($oldAmount, 2) . 
                    " to ₱" . number_format($newAmount, 2) . 
                    " - Reason: " . $validated['remarks'] .
                    " | Budget Last action: " . $updatedAt->format('M d, Y H:i:s')
                );

                $this->saveBudgetObligationHistory(
                    $dvRequest,
                    'amount_updated',
                    "Amount updated: ₱" . number_format($oldAmount, 2) . " to ₱" . number_format($newAmount, 2),
                    [
                        'status_before' => $dvRequest->getOriginal('status'),
                        'status_after'  => $newStatus,
                        'old_amount'    => $oldAmount,
                        'new_amount'    => $newAmount,
                        'reason'        => $validated['remarks'],
                        'updated_by'    => $currentUserId,
                        'updated_at'    => $updatedAt->toISOString(),
                        'budget_last_action_at' => $updatedAt->toISOString(),
                        'last_action_at' => $dvRequest->last_action_at ? $dvRequest->last_action_at->toISOString() : null,
                    ]
                );

                if ($dvRequest->focalperson_id) {
                    Notification::create([
                        'focalperson_id' => $dvRequest->focalperson_id,
                        'dv_request_id'  => $dvRequest->id,
                        'status'         => $dvRequest->status,
                        'action'         => 'Amount Updated',
                        'remarks'        => "Budget Officer updated the DV/ORS gross amount from ₱" . 
                                          number_format($oldAmount, 2) . " to ₱" . 
                                          number_format($newAmount, 2) . " - Reason: " . $validated['remarks'],
                    ]);

                    $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'amount-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'old_amount'    => number_format($oldAmount, 2),
                        'new_amount'    => number_format($newAmount, 2),
                        'remarks'       => $validated['remarks'],
                        'timestamp'     => $updatedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);

                    $this->pusher->trigger('dv-requests-budget-officer', 'amount-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'old_amount'    => number_format($oldAmount, 2),
                        'new_amount'    => number_format($newAmount, 2),
                        'timestamp'     => $updatedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'action_performer_id' => $currentUserId,
                        'budget_last_action_at' => $updatedAt->toISOString(),
                    ]);
                }

                DB::commit();

                \Log::info('Amount updated successfully', [
                    'dv_request_id' => $id,
                    'old_amount' => $oldAmount,
                    'new_amount' => $newAmount,
                    'budget_last_action_at' => $updatedAt,
                    'last_action_at' => $updatedAt,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Amount successfully updated.',
                    'data' => [
                        'old_amount' => number_format($oldAmount, 2),
                        'new_amount' => number_format($newAmount, 2),
                        'dv_amount'  => number_format($dvRequest->amount, 2),
                        'ors_gross'  => number_format($dvRequest->orsRequest->gross ?? 0, 2),
                        'updated_at' => $updatedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'budget_last_action_at' => $updatedAt->format('M d, Y H:i:s'),
                        'last_action_at' => $updatedAt->format('M d, Y H:i:s'),
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in updateAmount', [
                'errors' => $e->errors(),
                'dv_request_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating amount: ' . $e->getMessage(), [
                'dv_request_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the amount: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function storeADLCode(Request $request)
    {
        try {
            \Log::info('storeADLCode called with data:', $request->all());

            $validated = $request->validate([
                'adlcode' => 'required|string|max:50|unique:adlcodes,adlcode',
                'description' => 'required|string|max:255'
            ]);

            \Log::info('Validation passed, creating ADL Code');

            $adlCode = AdlCode::create($validated);

            \Log::info('ADL Code created successfully:', ['id' => $adlCode->id, 'code' => $adlCode->adlcode]);

            return response()->json([
                'success' => true,
                'message' => 'ADL Code created successfully',
                'adlCode' => $adlCode
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating ADL Code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ADL Code: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkADLCode(Request $request)
    {
        $exists = AdlCode::where('adlcode', $request->adlcode)->exists();
        
        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'ADL Code already exists' : 'ADL Code is available'
        ]);
    }

    public function checkBudgetAvailability(Request $request, $dvRequestId)
    {
        try {
            $dvRequest = DvRequest::with('program')->findOrFail($dvRequestId);
            $programCode = $dvRequest->program->prcode;
            $requestAmount = $dvRequest->amount;

            $budgetCheck = $this->canAccommodateAmount($programCode, $requestAmount);

            return response()->json([
                'success' => true,
                'allowed' => $budgetCheck['allowed'],
                'program' => $programCode,
                'request_amount' => number_format($requestAmount, 2),
                'remaining_budget' => number_format($budgetCheck['remaining_budget'], 2),
                'reason' => $budgetCheck['reason'],
                'details' => [
                    'allocated_budget' => $budgetCheck['allocated_budget'] ?? 0,
                    'obligated_budget' => $budgetCheck['obligated_budget'] ?? 0,
                    'budget_exceeded' => $budgetCheck['budget_exceeded'] ?? false
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Budget availability check failed', [
                'dv_request_id' => $dvRequestId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check budget availability: ' . $e->getMessage()
            ], 500);
        }
    }

   public function store(Request $request, $dvRequestId)
{
    $request->validate([
        'uacscode_ids'       => 'required|array|min:1',
        'uacscode_ids.*'     => 'required|exists:uacscodes,id|distinct',
        'uacs_amounts'       => 'required|array|min:1',
        'uacs_amounts.*'     => 'required|numeric|min:0.01',
        'adlcode_ids'        => 'required|array|min:1',
        'adlcode_ids.*'      => 'required|exists:adlcodes,id|distinct',
        'allotment_class_id' => 'required|exists:allotment_classes,id',
        'mfopap_id'          => 'required|exists:mfopaps,id',
        'contcurrent_id'     => 'required|exists:contcurrents,id',
        'gross'              => 'nullable|numeric',
        'remarks'            => 'nullable|string|max:1000',
    ]);

    $dvRequest = DvRequest::with('program')->findOrFail($dvRequestId)->fresh();
    $programCode = $dvRequest->program->prcode ?? null;
    $requestAmount = $dvRequest->amount;

    $totalUacsAmount = array_sum($request->uacs_amounts);
    $grossAmount = $dvRequest->amount;

    if ($programCode) {
        $budgetCheck = $this->canAccommodateAmount($programCode, $requestAmount);

        if (!$budgetCheck['allowed']) {
            \Log::warning('ORS obligation blocked due to budget threshold', [
                'dv_request_id' => $dvRequestId,
                'program' => $programCode,
                'request_amount' => $requestAmount,
                'remaining_budget' => $budgetCheck['remaining_budget'],
                'reason' => $budgetCheck['reason']
            ]);

            return back()->withErrors([
                'budget' => "❌ OBLIGATION RESTRICTED: " . $budgetCheck['reason'] . 
                           "<br><br>📊 <strong>Budget Status for {$programCode}:</strong>" .
                           "<br>• Remaining Budget: ₱" . number_format($budgetCheck['remaining_budget'], 2) .
                           "<br>• Request Amount: ₱" . number_format($requestAmount, 2) .
                           "<br><br>⚠️ Please contact the Budget Officer to request budget augmentation."
            ])->withInput();
        }
    }

    if (abs($totalUacsAmount - $grossAmount) > 0.01) {
        return back()->withErrors([
            'uacs_amounts' => "Total UACS amounts (₱" . number_format($totalUacsAmount, 2) . 
                            ") must equal the gross amount (₱" . number_format($grossAmount, 2) . ")"
        ])->withInput();
    }

    \Log::info('=== START ORS STORE METHOD WITH UACS AMOUNTS ===');
    \Log::info('Total UACS Amount: ' . $totalUacsAmount);
    \Log::info('Gross Amount: ' . $grossAmount);
    \Log::info('Program: ' . $programCode);
    \Log::info('Request Amount: ' . $requestAmount);

    $statusBefore = $dvRequest->status;
    $currentUserId = auth()->id();
    $newStatus = 'ORS_obligated';
    $obligatedAt = now();
    
    // Declare variables that need to be accessed outside the transaction
    $orsNumber = null;
    $uacsCount = 0;
    $adlCount = 0;

    DB::transaction(function () use (
        $dvRequest, 
        $statusBefore, 
        $request, 
        $currentUserId, 
        $newStatus, 
        $totalUacsAmount,
        $obligatedAt,
        &$orsNumber, // Pass by reference
        &$uacsCount, // Pass by reference
        &$adlCount   // Pass by reference
    ) {
        \Log::info('Starting transaction for ORS creation');
        
        $this->updateDepartmentTimestamp($dvRequest, $newStatus);
        
        $dvRequest->update([
            'status' => $newStatus,
            'budget_verified_at' => $obligatedAt,
            'budget_last_action_at' => $obligatedAt,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        $orsNumber = $this->generateOrsNumber($request->allotment_class_id, $request->contcurrent_id, $dvRequest);
        
        $primaryAdlCodeId = $request->adlcode_ids[0];
        
        $orsRequest = OrsRequest::create([
            'ors_number'         => $orsNumber,
            'dv_request_id'      => $dvRequest->id,
            'focalperson_id'     => $dvRequest->focalperson_id,         
            'accountingunit_id'  => $dvRequest->accounting_id ?? null,
            'adlcode_id'         => $primaryAdlCodeId,
            'allotment_class_id' => $request->allotment_class_id,
            'mfopap_id'          => $request->mfopap_id,
            'contcurrent_id' => $request->contcurrent_id,
            'gross'              => $dvRequest->amount,
            'remarks'            => $request->remarks,
            'status'             => $newStatus,
            'obligated_by'       => $currentUserId,
            'obligated_at'       => $obligatedAt,
            'budget_received_at' => $dvRequest->budget_received_at,
            'budget_verified_at' => $obligatedAt,
            'budget_last_action_at' => $obligatedAt,
            'last_action_at'     => $obligatedAt,
        ]);

        \Log::info('ORS Request created with ID: ' . $orsRequest->id);
        
        $uacsAttachData = [];
        foreach ($request->uacscode_ids as $index => $uacsId) {
            $amount = $request->uacs_amounts[$index] ?? 0;
            $uacsAttachData[$uacsId] = [
                'order' => $index + 1,
                'amount' => $amount
            ];
            \Log::info("UACS Code #{$index}: ID={$uacsId}, Amount={$amount}");
        }
        $orsRequest->uacsCodes()->attach($uacsAttachData);
        
        $adlAttachData = [];
        foreach ($request->adlcode_ids as $index => $adlId) {
            $adlAttachData[$adlId] = ['order' => $index + 1];
        }
        $orsRequest->adlCodes()->attach($adlAttachData);

        \Log::info('UACS codes with amounts and ADL codes attached successfully');
        
        $uacsCodes = UacsCode::whereIn('id', $request->uacscode_ids)->get();
        $uacsData = $uacsCodes->map(function($uacs, $index) use ($request) {
            $amount = $request->uacs_amounts[$index] ?? 0;
            return [
                'id' => $uacs->id,
                'code' => $uacs->uacscode,
                'description' => $uacs->description,
                'full_path' => $uacs->full_path,
                'amount' => $amount
            ];
        })->toArray();

        $adlCodes = AdlCode::whereIn('id', $request->adlcode_ids)->get();
        $adlData = $adlCodes->map(function($adl) {
            return [
                'id' => $adl->id,
                'code' => $adl->adlcode,
                'description' => $adl->description
            ];
        })->toArray();

        $allotmentClass = AllotmentClass::find($request->allotment_class_id);
        $mfopap = Mfopap::find($request->mfopap_id);
        $contcurrent = ContinuingOrCurrent::find($request->contcurrent_id);

        $this->saveBudgetObligationHistory(
            $dvRequest,
            'obligate',
            $request->remarks ?? "ORS Number $orsNumber assigned with " . count($request->uacscode_ids) . " UACS codes and " . count($request->adlcode_ids) . " ADL codes.",
            [
                'status_before' => $statusBefore,
                'status_after' => $newStatus,
                'ors_number' => $orsNumber,
                'ors_request_id' => $orsRequest->id,
                'adl_codes' => $adlData,
                'adl_count' => count($request->adlcode_ids),
                'allotment_class_id' => $request->allotment_class_id,
                'allotment_class' => $allotmentClass ? $allotmentClass->allotcode . ' - ' . $allotmentClass->description : null,
                'mfopap_id' => $request->mfopap_id,
                'mfopap' => $mfopap ? $mfopap->mfocode . ' - ' . $mfopap->mfodescription : null,
                'contcurrent_id' => $request->contcurrent_id,
                'contcurrent' => $contcurrent ? $contcurrent->ccode . ' - ' . $contcurrent->description : null,
                'uacs_codes' => $uacsData,
                'uacs_count' => count($request->uacscode_ids),
                'total_uacs_amount' => $totalUacsAmount,
                'amount' => $dvRequest->amount,
                'obligated_by' => $currentUserId,
                'obligated_at' => $obligatedAt->toISOString(),
                'budget_received_at' => $dvRequest->budget_received_at ? $dvRequest->budget_received_at->toISOString() : null,
                'budget_verified_at' => $obligatedAt->toISOString(),
                'budget_last_action_at' => $obligatedAt->toISOString(),
                'last_action_at' => $obligatedAt->toISOString(),
            ]
        );

        $focalId = $dvRequest->focalperson_id;
        $status  = $newStatus;
        $action  = 'Obligated';
        $uacsCount = count($request->uacscode_ids);
        $adlCount = count($request->adlcode_ids);
        $remarks = $request->remarks ?? "ORS Request Created with ORS Number: $orsNumber ({$uacsCount} UACS codes with amounts, {$adlCount} ADL codes)";

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
            'timestamp'     => $obligatedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        $this->pusher->trigger('dv-requests-budget-officer', 'ors-obligated', [
            'dv_request_id' => $dvRequest->id,
            'ors_number'    => $orsNumber,
            'status'        => $status,
            'timestamp'     => $obligatedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            'action_performer_id' => $currentUserId,
            'budget_received_at' => $dvRequest->budget_received_at ? $dvRequest->budget_received_at->toISOString() : null,
            'budget_verified_at' => $obligatedAt->toISOString(),
        ]);
        
        $this->pusher->trigger('dv-requests-outgoing', 'dv-request-submitted', [
            'dv_request_id' => $dvRequest->id,
            'control_number' => $dvRequest->accounting_control_number,
            'ors_number' => $orsRequest->ors_number,
            'status' => $newStatus,
            'submitted_by' => $dvRequest->focalPerson->fname . ' ' . $dvRequest->focalPerson->lname,
            'program' => $dvRequest->program->prcode,
            'amount' => $dvRequest->amount,
            'timestamp' => $obligatedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            'is_urgent' => $dvRequest->is_urgent ?? false,
        ]);
        
        $this->logAction(
            $dvRequest->id,
            'Budget_Officer',
            $dvRequest->status,
            "ORS Number $orsNumber assigned with $uacsCount UACS codes (total: ₱" . number_format($totalUacsAmount, 2) . ") and $adlCount ADL codes." .
            " | Budget Verified: " . $obligatedAt->format('M d, Y H:i:s')
        );
    });

    $this->triggerDashboardUpdate();

    \Log::info('=== END ORS STORE METHOD - Redirecting to preview ===');

    return redirect()->route('BudgetOfficer.dvrequests.previewOrs', $dvRequest->id)
        ->with('success', "ORS Request Created with ORS Number: $orsNumber ($uacsCount UACS codes with amounts, $adlCount ADL codes)")
        ->with('print_modal', true);
}

    public function previewOrs($id)
    {
        try {
            \Log::info('Accessing previewOrs for ID: ' . $id);
            
            $dvRequest = DvRequest::with([
                'program',
                'focalPerson',
                'respocenter',
                'orsRequest.uacsCodes',
                'orsRequest.adlCodes',
                'orsRequest.Mfopap',
                'orsRequest.allotmentClass',
                'orsRequest.continuingOrCurrent',
                'verificationHistories'
            ])->findOrFail($id);
            
            \Log::info('DV Request found:', [
                'id' => $dvRequest->id,
                'status' => $dvRequest->status,
                'ors_number' => $dvRequest->orsRequest->ors_number ?? null,
                'budget_received_at' => $dvRequest->budget_received_at,
                'budget_verified_at' => $dvRequest->budget_verified_at,
                'budget_last_action_at' => $dvRequest->budget_last_action_at,
                'last_action_at' => $dvRequest->last_action_at,
            ]);

            $orsRequest = $dvRequest->orsRequest;

            $uacsCodes = $orsRequest->uacsCodes;
            
            $uacsLines = [];
            $amountLines = [];
            
            foreach ($uacsCodes as $uacs) {
                $amount = $uacs->pivot->amount ?? 0;
                $uacsLines[] = $uacs->uacscode . ' - ' . $uacs->description;
                $amountLines[] = '₱' . number_format($amount, 2);
            }

            if (empty($uacsLines)) {
                $uacsLines[] = '—';
                $amountLines[] = '—';
            }

            $uacsDisplay = implode("\n", $uacsLines);
            $uacsAmountsDisplay = implode("\n", $amountLines);

            $mfoDisplay = $orsRequest->Mfopap 
                ? $orsRequest->Mfopap->mfocode . ' - ' . $orsRequest->Mfopap->mfodescription 
                : '—';

            $adlCodes = $orsRequest->adlCodes;
            $adlDisplay = $adlCodes->isNotEmpty() 
                ? $adlCodes->map(function($adl) {
                    return $adl->adlcode . ' - ' . $adl->description;
                })->implode("\n") 
                : '—';

            $data = [
                'responsibility_center' => trim(($dvRequest->respocenter->rccode ?? '') . ' - ' . ($dvRequest->respocenter->description ?? '')),
                'ors_no' => $orsRequest->ors_number,
                'payee' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                'particulars' => $dvRequest->particulars ?? $dvRequest->purpose ?? 'N/A',
                'date' => $orsRequest->obligated_at ? $orsRequest->obligated_at->format('F d, Y') : now()->format('F d, Y'),
                'address' => 'Davao City',      
                'particular_desc' => $dvRequest->program->prcode ?? 'Program',
                'mfo' => $mfoDisplay,
                'adl_codes' => $adlDisplay,
                'uacs' => $uacsDisplay,
                'uacs_amounts' => $uacsAmountsDisplay,
                'amount' => $dvRequest->amount,
            ];

            $status = [
                [
                    'date' => $orsRequest->obligated_at ? $orsRequest->obligated_at->format('m/d/Y') : now()->format('m/d/Y'),
                    'particulars' => $dvRequest->particulars ?? $dvRequest->purpose ?? 'N/A',
                    'ref_no' => $orsRequest->ors_number,
                    'obligation' => '₱' . number_format($dvRequest->amount, 2),
                    'payment' => '',
                    'due_status' => 'Not yet Due',
                ]
            ];

            $imsd_head = \App\Models\Setting::get('imsd_head', 'NAME OF IMSD HEAD');
            $budget_officer = \App\Models\Setting::get('budget_officer', auth()->user()->name ?? 'Budget Officer');

            return view('BudgetOfficer.previewors', [
                'data' => $data,
                'status' => $status,
                'imsd_head' => $imsd_head,
                'budget_officer' => $budget_officer,
                'dvRequest' => $dvRequest,
                'orsRequest' => $orsRequest,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in previewOrs: ' . $e->getMessage());
            return redirect()->route('BudgetOfficer.dvrequests.index')
                ->with('error', 'Error accessing ORS preview: ' . $e->getMessage());
        }
    }

    public function generateORSPDF($id)
    {
        try {
            \Log::info('=== ORS PDF Generation Started ===', ['id' => $id]);
            
            $dvRequest = DvRequest::with([
                'program',
                'focalPerson', 
                'respocenter',
                'orsRequest.uacsCodes',
                'orsRequest.adlCodes',
                'orsRequest.Mfopap',
                'orsRequest.allotmentClass',
                'orsRequest.continuingOrCurrent',
                'verificationHistories'
            ])->findOrFail($id);

            $obligatedHistory = $dvRequest->verificationHistories()
                ->where('status_after', 'ORS_obligated')
                ->latest()
                ->first();

            if (!$obligatedHistory || !$dvRequest->orsRequest) {
                \Log::error('ORS not obligated for PDF generation', ['id' => $dvRequest->id]);
                return redirect()->back()->with('error', 'ORS must be obligated before generating PDF.');
            }

            $orsRequest = $dvRequest->orsRequest;

            $logo_url = null;
            $logoPath = '/home/u203167746/domains/accudocs.accudocs.site/AccudocsProject/storage/app/public/logo/DOLE.png';
            
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $base64 = base64_encode($imageData);
                $logo_url = 'data:image/png;base64,' . $base64;
                \Log::info('Logo loaded for ORS PDF');
            } else {
                $svg = '<svg width="200" height="100" xmlns="http://www.w3.org/2000/svg">
                    <rect width="200" height="100" fill="#f8f9fa" stroke="#dee2e6"/>
                    <text x="100" y="50" text-anchor="middle" dy=".3em" font-family="Arial" font-size="12" fill="#6c757d">DOLE LOGO</text>
                </svg>';
                $logo_url = 'data:image/svg+xml;base64,' . base64_encode($svg);
                \Log::info('Using SVG fallback logo for ORS PDF');
            }

            $uacsItems = [];
            foreach ($orsRequest->uacsCodes as $uacs) {
                $amount = $uacs->pivot->amount ?? 0;
                $uacsItems[] = [
                    'code' => $uacs->uacscode,
                    'description' => $uacs->description,
                    'amount' => $amount
                ];
            }

            $mfoDisplay = $orsRequest->Mfopap 
                ? $orsRequest->Mfopap->mfocode . ' - ' . $orsRequest->Mfopap->mfodescription 
                : '—';

            $adlCodes = $orsRequest->adlCodes;
            $adlDisplay = $adlCodes->isNotEmpty() 
                ? $adlCodes->map(function($adl) {
                    return $adl->adlcode . ' - ' . $adl->description;
                })->implode("\n") 
                : '—';

            $uacsLines = [];
            $amountLines = [];
            foreach ($uacsItems as $item) {
                $uacsLines[] = $item['code'] . ' - ' . $item['description'];
                $amountLines[] = '₱' . number_format($item['amount'], 2);
            }

            $uacsDisplay = implode("\n", $uacsLines);
            $uacsAmountsDisplay = implode("\n", $amountLines);

            $data = [
                'responsibility_center' => trim(($dvRequest->respocenter->rccode ?? '') . ' - ' . ($dvRequest->respocenter->description ?? '')),
                'ors_no' => $orsRequest->ors_number,
                'payee' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                'particulars' => $dvRequest->particulars ?? $dvRequest->purpose ?? 'N/A',
                'date' => $orsRequest->obligated_at ? $orsRequest->obligated_at->format('F d, Y') : now()->format('F d, Y'),
                'address' => $dvRequest->focalPerson->Address ?? 'Davao City',      
                'particular_desc' => $dvRequest->program->prcode ?? 'Program',
                'mfo' => $mfoDisplay,
                'adl_codes' => $adlDisplay,
                'uacs_items' => $uacsItems,
                'uacs' => $uacsDisplay,
                'uacs_amounts' => $uacsAmountsDisplay,
                'amount' => $dvRequest->amount,
            ];

            $status = [
                [
                    'date' => $orsRequest->obligated_at ? $orsRequest->obligated_at->format('m/d/Y') : now()->format('m/d/Y'),
                    'particulars' => $dvRequest->particulars ?? $dvRequest->purpose ?? 'N/A',
                    'ref_no' => $orsRequest->ors_number,
                    'obligation' => number_format($dvRequest->amount, 2),
                    'payment' => '',
                    'due_status' => 'Not yet Due',
                ]
            ];

            $imsd_head = \App\Models\Setting::get('imsd_head', 'NAME OF IMSD HEAD');
            $budget_officer = \App\Models\Setting::get('budget_officer', auth()->user()->name ?? 'Budget Officer');

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

            $html = view('pdf.finalors', compact(
                'data', 
                'status',
                'imsd_head',
                'budget_officer',
                'logo_url'
            ))->render();

            $dompdf->loadHtml($html);
            $dompdf->render();

            $filename = 'ORS_' . $orsRequest->ors_number . '_' . now()->format('Ymd_His') . '.pdf';

            return $dompdf->stream($filename);

        } catch (\Exception $e) {
            \Log::error('ORS PDF Generation Failed', [
                'id' => $id, 
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to generate ORS PDF: ' . $e->getMessage());
        }
    }

    private function formatFullName($fname, $mi, $lname)
    {
        $name = trim($fname . ' ' . ($mi ? $mi . ' ' : '') . $lname);
        return $name ?: 'NAME OF PAYEE';
    }

    public function reject(Request $request, $id)
    {
        try {
            $request->validate([
                'remarks' => 'required|string|max:1000|min:10',
            ], [
                'remarks.required' => 'Please provide a reason for rejection.',
                'remarks.min' => 'Rejection reason must be at least 10 characters.',
            ]);

            $dvRequest = DvRequest::with(['orsRequest', 'focalPerson'])->findOrFail($id);
            $statusBefore = $dvRequest->status;
            $currentUserId = auth()->id();
            $rejectedAt = now();
            $newStatus = 'ORS_rejected';

            if (!in_array($dvRequest->status, [
                'Incoming-TUPAD_verified', 
                'Incoming-GIP_verified',
                'Incoming-TEV_verified',
                'ORS_received',
                'ORS_onhold'
            ])) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This request cannot be rejected at its current status: ' . $statusBefore
                    ], 422);
                }
                return redirect()->back()->with('error', 'This request cannot be rejected at its current status: ' . $statusBefore);
            }

            $lockDuration = now()->subMinutes(10);
            if ($dvRequest->locked_by && 
                $dvRequest->locked_by !== $currentUserId && 
                $dvRequest->locked_at >= $lockDuration) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This request is currently locked by another user.'
                    ], 422);
                }
                return redirect()->back()->with('error', 'This request is currently locked by another user.');
            }

            $emailSent = false;
            $smsSent = false;

            DB::transaction(function () use (
                $dvRequest, 
                $statusBefore, 
                $rejectedAt, 
                $request, 
                $currentUserId,
                $newStatus,
                &$emailSent,
                &$smsSent
            ) {
                $this->archiveToHistory($dvRequest, $newStatus, $request->remarks);
                
                $this->updateDepartmentTimestamp($dvRequest, $newStatus);
                
                $dvRequest->update([
                    'status'                 => $newStatus,
                    'rejected_by'            => $currentUserId,
                    'rejected_at'            => $rejectedAt,
                    'budget_last_action_at'  => $rejectedAt,
                    'locked_by'              => null,
                    'locked_at'              => null,
                    'last_action_at'         => $rejectedAt,
                ]);

                if ($dvRequest->orsRequest) {
                    $dvRequest->orsRequest->update([
                        'status'                 => $newStatus,
                        'rejected_by'            => $currentUserId,
                        'rejected_at'            => $rejectedAt,
                        'budget_last_action_at'  => $rejectedAt,
                        'last_action_at'         => $rejectedAt,
                    ]);
                }

                $emailSent = $this->sendRejectionEmail($dvRequest, $request->remarks);

                $smsSent = $this->sendRejectionSMS($dvRequest, $request->remarks);

                $this->saveBudgetObligationHistory(
                    $dvRequest,
                    'reject',
                    $request->remarks,
                    [
                        'status_before'       => $statusBefore,
                        'status_after'        => $newStatus,
                        'rejected_by'         => $currentUserId,
                        'rejected_at'         => $rejectedAt->toISOString(),
                        'budget_last_action_at' => $rejectedAt->toISOString(),
                        'rejection_reason'    => $request->remarks,
                        'amount'              => $dvRequest->amount,
                        'ors_request_id'      => $dvRequest->orsRequest->id ?? null,
                        'budget_received_at'  => $dvRequest->budget_received_at ? $dvRequest->budget_received_at->toISOString() : null,
                        'last_action_at'      => $rejectedAt->toISOString(),
                    ]
                );

                $this->logAction(
                    $dvRequest->id,
                    'Budget_Officer',
                    $newStatus,
                    "Rejected by Budget Officer: {$request->remarks}. " . 
                    "Budget Last Action: {$rejectedAt->format('M d, Y H:i:s')}"
                );

                $focalId = $dvRequest->focalperson_id;
                if ($focalId) {
                    Notification::create([
                        'focalperson_id' => $focalId,
                        'dv_request_id'  => $dvRequest->id,
                        'status'         => $newStatus,
                        'action'         => 'Rejected',
                        'remarks'        => $request->remarks,
                    ]);

                    $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => $newStatus,
                        'action'        => 'Rejected',
                        'remarks'       => $request->remarks,
                        'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'budget_last_action_at' => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);
                }

                $this->pusher->trigger('dv-requests-budget-officer', 'ors-rejected', [
                    'dv_request_id'      => $dvRequest->id,
                    'control_number'     => $dvRequest->accounting_control_number ? 
                        'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) : 
                        'Unknown',
                    'status'             => $newStatus,
                    'rejected_by'        => auth()->user()->name ?? 'Budget Officer',
                    'timestamp'          => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'action_performer_id' => $currentUserId,
                    'budget_received_at' => $dvRequest->budget_received_at ? $dvRequest->budget_received_at->timezone('Asia/Manila')->format('M d, Y h:i A') : null,
                    'budget_last_action_at' => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'last_action_at'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            });

            $this->triggerDashboardUpdate();

            $responseMessage = 'ORS request has been rejected';
            
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
                        'id'                   => $dvRequest->id,
                        'status'               => $newStatus,
                        'rejected_at'          => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'budget_last_action_at' => $rejectedAt->timezone('Asia/Manila')->format('M d, Y H:i:s'),
                        'email_sent'           => $emailSent,
                        'sms_sent'             => $smsSent,
                        'budget_received_at'   => $dvRequest->budget_received_at ? $dvRequest->budget_received_at->timezone('Asia/Manila')->format('M d, Y h:i A') : null,
                        'last_action_at'       => $rejectedAt->timezone('Asia/Manila')->format('M d, Y H:i:s'),
                        'timestamps' => [
                            'rejected' => $rejectedAt->toISOString(),
                            'budget_last_action' => $rejectedAt->toISOString(),
                            'budget_received' => $dvRequest->budget_received_at ? $dvRequest->budget_received_at->toISOString() : null,
                            'last_action' => $rejectedAt->toISOString(),
                        ]
                    ]
                ]);
            }

            return redirect()->route('BudgetOfficer.dvrequests.index')
                ->with('success', 'ORS request has been rejected successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in reject method', [
                'errors' => $e->errors(),
                'dv_request_id' => $id
            ]);
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->validator->errors()->first()
                ], 422);
            }
            return redirect()->back()->withErrors($e->validator)->withInput();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error rejecting ORS request: ' . $e->getMessage(), [
                'dv_request_id' => $id,
                'user_id' => auth()->id(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while rejecting the request: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while rejecting the request: ' . $e->getMessage());
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
            'history_reason' => $reason,
            'moved_to_history_at' => now(),
            'moved_by' => auth()->id(),
        ]);
    }

    protected function sendRejectionSMS($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            if (!$focalPerson || empty($focalPerson->contactnumber)) {
                \Log::warning('Cannot send rejection SMS: focal person or contact number missing', [
                    'dv_request_id' => $dvRequest->id,
                    'focal_person_id' => $focalPerson->id ?? 'null',
                    'has_contactnumber' => !empty($focalPerson->contactnumber)
                ]);
                return false;
            }

            $phoneNumber = $focalPerson->contactnumber;
            
            $truncatedRemarks = strlen($rejectionRemarks) > 100 
                ? substr($rejectionRemarks, 0, 97) . '...'
                : $rejectionRemarks;

            $userSequence = $dvRequest->user_sequence ?? 'N/A';
            $message = "Hi {$focalPerson->fname}, your DVORS #{$userSequence}. {$truncatedRemarks}.";

            \Log::info('Sending Budget Officer rejection SMS via iProg', [
                'phone' => $phoneNumber,
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $userSequence,
                'message_length' => strlen($message)    
            ]);

            $result = $this->smsService->sendSMS($phoneNumber, $message);
            
            if ($result['success']) {
                \Log::info('Budget Officer rejection SMS sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'user_sequence' => $userSequence,
                    'phone' => $phoneNumber,
                    'message_id' => $result['message_id'] ?? 'N/A'
                ]);
                return true;
            } else {
                \Log::error('Failed to send Budget Officer rejection SMS', [
                    'dv_request_id' => $dvRequest->id,
                    'user_sequence' => $userSequence,
                    'phone' => $phoneNumber,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            \Log::error('Budget Officer SMS rejection notification failed: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $dvRequest->user_sequence ?? 'N/A',
                'exception' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function history(Request $request)
    {
        $query = BudgetObligationHistory::with([
            'dvRequest.program',
            'dvRequest.focalPerson', 
            'dvRequest.respocenter',
            'orsRequest.uacsCodes',
            'orsRequest.adlCodes',
            'orsRequest.Mfopap',
            'orsRequest.AllotmentClass',
            'orsRequest.ContinuingOrCurrent',
            'verifier'
        ]);
        if ($request->has('search') && $request->search) {
            $query->whereHas('dvRequest', function($q) use ($request) {
                $searchTerm = $request->search;
                
                if (preg_match('/CN\s*#-(\d{4})-(\d+)/', $searchTerm, $matches)) {
                    $year = $matches[1];
                    $controlNumber = $matches[2];
                    
                    $q->where('accounting_control_number', 'like', '%' . $controlNumber . '%');
                } 
                elseif (preg_match('/(\d{4})-(\d+)/', $searchTerm, $matches)) {
                    $controlNumber = $matches[2];
                    $q->where('accounting_control_number', 'like', '%' . $controlNumber . '%');
                }
                else {
                    $numbersOnly = preg_replace('/[^0-9]/', '', $searchTerm);
                    if (!empty($numbersOnly)) {
                        $q->where('accounting_control_number', 'like', '%' . $numbersOnly . '%');
                    }
                }
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

        $programs = Program::all();

        return view('BudgetOfficer.loghistory', compact('verificationHistory', 'programs'));
    }

    protected function saveBudgetObligationHistory($dvRequest, $step, $remarks = null, array $data = [])
    {
        $obligatedAmount = null;
        
        if ($step === 'obligate') {
            $obligatedAmount = $dvRequest->amount;
        } elseif ($step === 'receive') {
            $obligatedAmount = $dvRequest->amount;
        }
        
        BudgetObligationHistory::create([
            'dv_request_id' => $dvRequest->id,
            'ors_request_id' => $dvRequest->orsRequest->id ?? null,
            'verified_by' => auth()->id(),
            'verification_step' => $step,
            'status_before' => $data['status_before'] ?? $dvRequest->getOriginal('status'),
            'status_after' => $dvRequest->status,
            'verification_data' => $data,
            'obligated_amount' => $obligatedAmount,
            'remarks' => $remarks,
            'verified_at' => now(),
        ]);
    }
    
    private function generateOrsNumber($allotmentClassId, $contcurrentId, $dvRequest)
    {
        $year  = now()->format('Y');
        $month = now()->format('m');

        $allotment = AllotmentClass::findOrFail($allotmentClassId)->allotcode;
        $ccode     = ContinuingOrCurrent::findOrFail($contcurrentId)->ccode;

        $series = $dvRequest->accounting_control_number;
        $seriesFormatted = str_pad($series, 4, '0', STR_PAD_LEFT);

        $orsNumber = "{$allotment}-{$ccode}-{$year}-{$month}-{$seriesFormatted}";

        $exists = OrsRequest::where('ors_number', $orsNumber)->exists();
        if ($exists) {
            $orsNumber .= '-DUP';
        }
        if (empty($dvRequest->accounting_control_number)) {
            return redirect()->back()->with('error', 'Accounting Control Number is required before obligation.');
        }
        return $orsNumber;
    }

    public function onhold(Request $request, $id)
    {
        $dvRequest = DvRequest::findOrFail($id);
        $newStatus = 'ORS_onhold';

        $this->updateDepartmentTimestamp($dvRequest, $newStatus);
        
        $dvRequest->update([
            'status' => $newStatus,
        ]);
        
        if ($dvRequest->orsRequest) {
            $dvRequest->orsRequest->update([
                'status' => $newStatus,
            ]);
        }

        $focalId = $dvRequest->focalperson_id;
        $status  = $newStatus;
        $action  = 'On hold';
        $remarks = $request->remarks ?? "ORS request placed on hold";

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
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        $this->pusher->trigger('dv-requests-budget-officer', 'ors-onhold', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        $this->logAction(
            $dvRequest->id,
            'Budget Officer',
            $newStatus,
            ($request->remarks ?? 'ORS request placed on hold') . 
            ' | Budget Last action: ' . now()->format('M d, Y H:i:s')
        );

        $this->triggerDashboardUpdate();

        return redirect()
            ->back()
            ->with('success', 'The ORS request has been placed on hold.');
    }

    public function revise(Request $request, $id)
    {
        $dvRequest = DvRequest::findOrFail($id);
        $newStatus = 'ORS_for_revision';

        $this->updateDepartmentTimestamp($dvRequest, $newStatus);
        
        $dvRequest->update([
            'status' => $newStatus,
        ]);

        if ($dvRequest->orsRequest) {
            $dvRequest->orsRequest->update([
                'status' => $newStatus,
            ]);
        }

        $focalId = $dvRequest->focalperson_id;
        $remarks = $request->remarks ?? 'ORS request sent back for revision';

        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => $newStatus,
            'action'         => 'For Revision',
            'remarks'        => $remarks,
        ]);

        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $newStatus,
            'remarks'       => $remarks,
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        $this->pusher->trigger('dv-requests-budget-officer', 'ors-revised', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $newStatus,
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        $this->logAction(
            $dvRequest->id,
            'Budget_Officer',
            $newStatus,
            $remarks . ' | Budget Last action: ' . now()->format('M d, Y H:i:s')
        );

        $this->triggerDashboardUpdate();

        return redirect()
            ->back()
            ->with('success', 'The ORS request has been sent back for revision.');
    }

    protected function logAction($dvRequestId, $action, $status, $remarks = null)
    {
        $logData = [
            'dv_request_id' => $dvRequestId,
            'action'        => $action,
            'status'        => $status,
            'remarks'       => $remarks,
        ];

        if (auth()->guard('web')->check()) {
            $logData['accountingunit_id'] = auth()->id();
        } elseif (auth()->guard('focal')->check()) {
            $logData['focalperson_id'] = auth()->id();
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
                return $this->sendFallbackEmail($toEmail, $toName, $dvRequest, 'ORS_rejected', $rejectionRemarks);
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

    protected function sendFallbackEmail($toEmail, $toName, $dvRequest, $status, $remarks = null)
    {
        try {
            \Log::info('Using fallback SMTP email for status: ' . $status);
            
            $subject = "DV/ORS Request Status Updated - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            $htmlContent = view('emails.dv_status_updated', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'status' => $status,
                'remarks' => $remarks,
                'updatedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'updatedBy' => auth()->user()->name ?? 'Budget Officer'
            ])->render();

            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($toEmail, $toName, $subject, $htmlContent) {
                $message->to($toEmail, $toName)
                       ->subject($subject)
                       ->from(config('mail.from.address'), config('mail.from.name'))
                       ->html($htmlContent);
            });

            \Log::info('Fallback SMTP email sent successfully', ['to' => $toEmail, 'status' => $status]);
            return true;
        } catch (\Exception $e) {
            \Log::error('Fallback SMTP also failed: ' . $e->getMessage());
            return false;
        }
    }
}