<?php

namespace App\Http\Controllers\AccountingIII;

use App\Http\Controllers\Controller;
use App\Traits\TriggersDashboardUpdate;
use App\Traits\HandlesDepartmentTransitions;
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\Notification;
use App\Models\RANCAValidationHistory;
use App\Models\DvRequestLog;
use App\Models\DvRequestHistory;
use App\Services\PusherService;
use App\Services\SendPulseServices;
use App\Services\IprogSmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RANCAController extends Controller
{
    use TriggersDashboardUpdate, HandlesDepartmentTransitions;
    
    protected PusherService $pusher;
    protected IprogSmsService $smsService;

    public function __construct(PusherService $pusher, IprogSmsService $smsService)
    {
        $this->pusher = $pusher;
        $this->smsService = $smsService;
    }

    /** Dashboard */
    public function index()
    {
        return view('Accounting.index');
    }

   /** Fetch DV requests for Accounting/RANCA */
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
        ->whereIn('status', ['Outgoing_finalized', 'RANCA_received'])
        ->where(function ($q) use ($currentUserId, $lockDuration) {
            $q->whereNull('locked_by')
              ->orWhere('locked_by', $currentUserId)
              ->orWhere('locked_at', '<=', $lockDuration);
        })
        ->when($searchTerm, function ($query) use ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('accounting_control_number', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('dv_number', 'LIKE', "%{$searchTerm}%")
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
            // Use accountant_received_at if available, otherwise created_at
            $baseDate = $request->accountant_received_at ?? $request->created_at;
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
            
            if ($request->status === 'Outgoing_finalized') {
                $priorityScore += 100; // Extra priority for unreceived
            }
            
            if ($request->status === 'RANCA_received') {
                $priorityScore += 50;
            }
            
            $priorityScore += rand(0, 10);
            
            $request->days_old = $daysOld;
            $request->priority_score = $priorityScore;
            $request->urgency = $this->getAccountingUrgencyLevel($daysOld);
            
            // Override urgency if marked urgent
            if ($request->is_urgent) {
                $request->urgency = 'urgent';
            }
            
            return $request;
        });
        
        $dvRequests = $dvRequests->sortByDesc('priority_score')->values();

        return response()->json($dvRequests->map(function ($req) {
            $baseDate = $req->accountant_received_at ?? $req->created_at;
            
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
                'accountant_received_at' => $safeToISO($req->accountant_received_at),
                'accountant_verified_at' => $safeToISO($req->accountant_verified_at),
                'accountant_last_action_at' => $safeToISO($req->accountant_last_action_at),
                'last_action_at' => $safeToISO($req->last_action_at),
                'days_old'     => $req->days_old,
                'urgency'      => $req->urgency,
                'priority_score' => $req->priority_score,
                // 🚨🚨🚨 ADD THIS LINE - CRITICAL FOR URGENT FLAG 🚨🚨🚨
                'is_urgent'    => $req->is_urgent ?? false,
            ];
        }));

    } catch (\Exception $e) {
        Log::error('Error in Accounting fetchDvRequests: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'error' => 'Failed to fetch DV requests: ' . $e->getMessage()
        ], 500);
    }
}

    public function fetchDvRecent()
    {
        $currentUserId = auth()->id();
        $lockDuration  = now()->subMinutes(10);

        $recentDvRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
            ->whereIn('status', ['Outgoing_finalized', 'RANCA_received'])
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

    protected function getAccountingUrgencyLevel($daysOld)
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

    /** Show DV for processing */
    public function show($id)
    {
        $dvRequest = DvRequest::with([
            'respocenter',
            'focalPerson',
            'orsRequest',
            'tupadAttachments',
            'tevAttachments',
            'logs',
        ])->findOrFail($id);

        $currentUserId = auth()->id();
        $lockDuration  = now()->subMinutes(10);

        if ($dvRequest->locked_by 
            && $dvRequest->locked_by !== $currentUserId 
            && $dvRequest->locked_at >= $lockDuration) {
            return redirect()->route('Accounting.dvrequests.index')
                ->with('error', 'This DV Request is currently being processed by another user.');
        }

        if (!$dvRequest->locked_by || $dvRequest->locked_at < $lockDuration || $dvRequest->locked_by === $currentUserId) {
            $dvRequest->update([
                'locked_by' => $currentUserId,
                'locked_at' => now(),
            ]);
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

        return view('Accounting.validatedv', compact(
            'dvRequest', 
            'lastStep',
            'selectedModality',
            'tupadAttachments',
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
            $newStatus = 'RANCA_received';

            if ($dvRequest->status !== 'Outgoing_finalized') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is not yet ready for RANCA to receive.'
                ], 422);
            }

            DB::transaction(function () use ($dvRequest, $statusBefore, $receivedAt, $currentUserId, $newStatus) {
                // Update accountant specific timestamps using trait
                $this->updateDepartmentTimestamp($dvRequest, $newStatus);
                
                $dvRequest->refresh();
                
                $dvRequest->update([
                    'last_action_at' => $receivedAt,
                    'last_action_type' => 'received',
                    'last_acted_by' => $currentUserId,
                    'status' => $newStatus,
                    'accountingunit_id' => $currentUserId,
                    'received_by' => $currentUserId,
                    'received_at' => $receivedAt,
                    'locked_by' => $currentUserId,
                    'locked_at' => $receivedAt,
                ]);

                $this->saveRANCAValidationHistory(
                    $dvRequest,
                    'receive',
                    'DV request received by RANCA for validation',
                    [
                        'status_before' => $statusBefore,
                        'status_after'  => $newStatus,
                        'received_by' => $currentUserId,
                        'received_at' => $receivedAt->toISOString(),
                        'accountant_received_at' => $this->safeDateToISO($dvRequest->accountant_received_at),
                        'accountant_last_action_at' => $this->safeDateToISO($dvRequest->accountant_last_action_at),
                        'last_action_at' => $this->safeDateToISO($dvRequest->last_action_at),
                        'amount' => $dvRequest->amount
                    ]
                );

                $this->logAction(
                    $dvRequest->id,
                    'Accountant_III',
                    $newStatus,
                    'DV request received by RANCA for validation. Accountant received at: ' . 
                    $this->formatDateForLog($dvRequest->accountant_received_at)
                );

                $focalId = $dvRequest->focalperson_id;
                $remarks = "DV request received by RANCA for validation";

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
                ]);

                $this->pusher->trigger('dv-requests-ranca', 'dv-request-received', [
                    'dv_request_id' => $dvRequest->id,
                    'control_number' => $dvRequest->accounting_control_number,
                    'status'        => $dvRequest->status,
                    'received_by'   => auth()->user()->name ?? 'RANCA User',
                    'action_performer_id' => $currentUserId,
                    'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'accountant_received_at' => $this->safeDateToISO($dvRequest->accountant_received_at),
                    'accountant_last_action_at' => $this->safeDateToISO($dvRequest->accountant_last_action_at),
                    'last_action_at' => $this->safeDateToISO($dvRequest->last_action_at),
                ]);
            });

            $this->triggerDashboardUpdate();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document has been received by RANCA.',
                    'data' => [
                        'id'          => $dvRequest->id,
                        'status'      => $newStatus,
                        'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'accountant_received_at' => $this->formatDateForLog($dvRequest->accountant_received_at),
                        'accountant_last_action_at' => $this->formatDateForLog($dvRequest->accountant_last_action_at),
                        'last_action_at' => $this->formatDateForLog($dvRequest->last_action_at),
                    ]
                ]);
            }

            return redirect()->route('Accounting.dvrequests.index')
                ->with('success', 'Document has been received by RANCA.');

        } catch (\Exception $e) {
            Log::error('Error receiving DV request by RANCA: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing the request.'
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while processing the request.');
        }
    }

    /** Redirect to first pending */
    public function redirectToFirstPending()
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $pendingRequests = DvRequest::whereIn('status', ['Outgoing_finalized', 'RANCA_received'])
                                ->orderBy('created_at', 'asc')
                                ->get();

        if ($pendingRequests->isEmpty()) {
            return redirect()->route('Accounting.dvrequests.index')
                             ->with('info', 'No DV requests pending validation.');
        }

        $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
            return !$req->locked_by 
                || $req->locked_by == $currentUserId 
                || $req->locked_at <= $lockDuration;
        });

        if ($available) {
            return redirect()->route('Accounting.dvrequests.show', $available->id);
        }

        return redirect()->route('Accounting.dvrequests.index')
                         ->with('info', 'All pending DV requests are currently locked by other users.');
    }

    /** Update amount during processing */
    public function updateAmount(Request $request, $id)
    {
        $request->validate([
            'amount'  => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $dvRequest = DvRequest::with('orsRequest')->findOrFail($id);
            $statusBefore = $dvRequest->status;

            $currentUserId = auth()->id();
            $lockDuration = now()->subMinutes(10);
            
            if ($dvRequest->locked_by && 
                $dvRequest->locked_by !== $currentUserId && 
                $dvRequest->locked_at >= $lockDuration) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is currently locked by another user.'
                ], 422);
            }

            if (!in_array($dvRequest->status, ['RANCA_received'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount can only be edited while request is received.'
                ], 422);
            }

            $oldAmount = $dvRequest->amount;
            $newAmount = $request->amount;

            DB::beginTransaction();

            // Update accountant last action timestamp using trait
            $this->updateDepartmentTimestamp($dvRequest, $dvRequest->status);
            
            $dvRequest->refresh();

            $dvRequest->update([
                'amount' => $newAmount,
                'last_action_at' => now(),
                'last_action_type' => 'amount_update',
                'last_acted_by' => $currentUserId,
                'locked_by' => $currentUserId,
                'locked_at' => now(),
            ]);

            if ($dvRequest->orsRequest) {
                $dvRequest->orsRequest->update([
                    'gross' => $newAmount,
                ]);
            }

            $this->saveRANCAValidationHistory(
                $dvRequest,
                'amount_update',
                "Amount updated from ₱" . number_format($oldAmount, 2) . 
                " to ₱" . number_format($newAmount, 2) . 
                ($request->remarks ? " - Reason: " . $request->remarks : ""),
                [
                    'status_before' => $statusBefore,
                    'status_after'  => $dvRequest->status,
                    'old_amount' => $oldAmount,
                    'new_amount' => $newAmount,
                    'updated_by' => $currentUserId,
                    'updated_at' => now()->toISOString(),
                    'accountant_last_action_at' => $this->safeDateToISO($dvRequest->accountant_last_action_at),
                    'last_action_at' => $this->safeDateToISO($dvRequest->last_action_at),
                ]
            );

            $this->logAction(
                $dvRequest->id,
                'Accountant_III',
                'RANCA_amount_updated',
                "DV/ORS amount updated from ₱" . number_format($oldAmount, 2) . 
                " to ₱" . number_format($newAmount, 2) . 
                ($request->remarks ? " - Reason: " . $request->remarks : "")
            );

            if ($dvRequest->focalperson_id) {
                Notification::create([
                    'focalperson_id' => $dvRequest->focalperson_id,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $dvRequest->status,
                    'action'         => 'Accountant_III',
                    'remarks'        => $request->remarks ?? "RANCA updated the DV amount from ₱" . number_format($oldAmount, 2) . " to ₱" . number_format($newAmount, 2),
                ]);

                $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'amount-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'old_amount' => number_format($oldAmount, 2),
                    'new_amount' => number_format($newAmount, 2),
                    'remarks' => $request->remarks,
                    'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Amount updated successfully.',
                'data' => [
                    'old_amount' => number_format($oldAmount, 2),
                    'new_amount' => number_format($newAmount, 2),
                    'dv_amount' => number_format($dvRequest->amount, 2),
                    'ors_gross' => number_format($dvRequest->orsRequest->gross ?? 0, 2),
                    'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'accountant_last_action_at' => $this->formatDateForLog($dvRequest->accountant_last_action_at),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating amount in RANCA: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the amount.'
            ], 500);
        }
    }

    /** Validate/Finalize DV */
public function validateDV(Request $request, $id)
{
    $request->validate([
        'remarks' => 'nullable|string|max:1000',
    ]);

    try {
        $dvRequest = DvRequest::with(['focalPerson', 'program', 'respocenter'])->findOrFail($id);
        $statusBefore = $dvRequest->status;
        $currentUserId = auth()->id();
        $newStatus = 'RANCA_validated';
        $validatedAt = now();

        if ($dvRequest->status !== 'RANCA_received') {
            return redirect()->back()->with('error', 'This DV request cannot be validated.');
        }

        DB::transaction(function () use (
            $dvRequest, 
            $statusBefore, 
            $validatedAt, 
            $request, 
            $currentUserId,
            $newStatus
        ) {
            // Update accountant specific timestamps using trait
            $this->updateDepartmentTimestamp($dvRequest, $newStatus);
            
            $dvRequest->refresh();
            
            // 🚨🚨🚨 AUTO-REMOVE URGENT FLAG WHEN ACCOUNTANT VALIDATES 🚨🚨🚨
            $dvRequest->update([
                'last_action_at' => $validatedAt,
                'last_action_type' => 'validated',
                'last_acted_by' => $currentUserId,
                'status' => $newStatus,
                'qr_status'    => 'active', 
                'finalized_by' => $currentUserId,
                'finalized_at' => $validatedAt,
                'locked_by'    => null,
                'locked_at'    => null,
                // 👇👇👇 ADD THIS LINE TO REMOVE URGENT FLAG 👇👇👇
                'is_urgent' => false,
            ]);

            $dvRequest->refresh();

            $remarks = $request->remarks ?? 'DV Request validated by Accountant';

            // Log that urgent flag was removed
            if ($dvRequest->getOriginal('is_urgent') == 1) {
                Log::info('🚨 Urgent flag automatically removed by Accountant validation', [
                    'dv_request_id' => $dvRequest->id,
                    'dv_number' => $dvRequest->dv_number,
                    'validated_by' => $currentUserId,
                    'validated_at' => $validatedAt->toISOString()
                ]);
            }

            // Save validation history
            $this->saveRANCAValidationHistory(
                $dvRequest,
                'validate',
                $remarks,
                [
                    'status_before' => $statusBefore,
                    'status_after' => $newStatus,
                    'dv_number' => $dvRequest->dv_number,
                    'qr_status' => 'active',
                    'validated_by' => $currentUserId,
                    'validated_at' => $validatedAt->toISOString(),
                    'accountant_verified_at' => $this->safeDateToISO($dvRequest->accountant_verified_at),
                    'accountant_last_action_at' => $this->safeDateToISO($dvRequest->accountant_last_action_at),
                    'last_action_at' => $this->safeDateToISO($dvRequest->last_action_at),
                    'amount' => $dvRequest->amount,
                    'program' => $dvRequest->program->prcode ?? null,
                    // 👇 ADD THIS TO TRACK URGENT FLAG REMOVAL
                    'urgent_flag_removed' => $dvRequest->getOriginal('is_urgent') == 1,
                ]
            );

            $this->logAction(
                $dvRequest->id, 
                'Accountant_III', 
                $newStatus, 
                $remarks . ' | Accountant verified at: ' . $this->formatDateForLog($dvRequest->accountant_verified_at) .
                ($dvRequest->getOriginal('is_urgent') == 1 ? ' | 🚨 Urgent flag automatically removed' : '')
            );

            // Notify focal person
            $focalId = $dvRequest->focalperson_id;

            if ($focalId) {
                Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $newStatus,
                    'action'         => 'Validated',
                    'remarks'        => $remarks . ($dvRequest->getOriginal('is_urgent') == 1 ? ' (Urgent flag resolved)' : ''),
                ]);

                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => $newStatus,
                    'remarks'       => $remarks,
                    'timestamp'     => $validatedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'is_urgent'     => false, // Send updated urgent status
                ]);
            }
            
            // Send to regional director dashboard
            $this->pusher->trigger('regional-director-dashboard', 'new-dv-available', [
                'dv_id' => $dvRequest->id,
                'dv_number' => $dvRequest->dv_number,
                'status' => $newStatus,
                'amount' => $dvRequest->amount,
                'payee' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                'program' => $dvRequest->program->prcode ?? null,
                'timestamp' => $validatedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'validated_by' => auth()->user()->name,
                'accountant_verified_at' => $this->safeDateToISO($dvRequest->accountant_verified_at),
                'accountant_last_action_at' => $this->safeDateToISO($dvRequest->accountant_last_action_at),
                'is_urgent' => false, // Send updated urgent status
                'dv_data' => [
                    'id' => $dvRequest->id,
                    'dv_number' => $dvRequest->dv_number,
                    'reference_number' => $dvRequest->reference_number,
                    'amount' => $dvRequest->amount,
                    'status' => $newStatus,
                    'mode_of_payment' => $dvRequest->mode_of_payment,
                    'particulars' => $dvRequest->particulars,
                    'qr_code_token' => $dvRequest->qr_code_token,
                    'qr_status' => $dvRequest->qr_status,
                    'is_urgent' => false, // Send updated urgent status
                    'accountant_verified_at' => $this->safeDateToISO($dvRequest->accountant_verified_at),
                    'accountant_last_action_at' => $this->safeDateToISO($dvRequest->accountant_last_action_at),
                    'updated_at' => $dvRequest->updated_at->toISOString(),
                    'focal_person' => [
                        'fname' => $dvRequest->focalPerson->fname,
                        'lname' => $dvRequest->focalPerson->lname,
                        'position' => $dvRequest->focalPerson->position,
                        'TIN_number' => $dvRequest->focalPerson->TIN_number,
                        'contactnumber' => $dvRequest->focalPerson->contactnumber,
                    ],
                    'program' => [
                        'prcode' => $dvRequest->program->prcode ?? null,
                        'description' => $dvRequest->program->description ?? null,
                    ]
                ]
            ]);

            // Real-time notification to RANCA channel
            $this->pusher->trigger('dv-requests-ranca', 'dv-request-validated', [
                'dv_request_id' => $dvRequest->id,
                'status'        => $newStatus,
                'action_performer_id' => $currentUserId,
                'accountant_verified_at' => $this->safeDateToISO($dvRequest->accountant_verified_at),
                'accountant_last_action_at' => $this->safeDateToISO($dvRequest->accountant_last_action_at),
                'timestamp'     => $validatedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'is_urgent'     => false, // Send updated urgent status
            ]);
        });

        $this->triggerDashboardUpdate();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'DV Request has been validated by Accountant and urgent flag ' . 
                            ($dvRequest->getOriginal('is_urgent') == 1 ? 'removed.' : 'was not set.'),
                'data' => [
                    'id' => $dvRequest->id,
                    'status' => $newStatus,
                    'is_urgent' => false,
                    'urgent_removed' => $dvRequest->getOriginal('is_urgent') == 1,
                    'accountant_verified_at' => $this->formatDateForLog($dvRequest->accountant_verified_at),
                    'accountant_last_action_at' => $this->formatDateForLog($dvRequest->accountant_last_action_at),
                ]
            ]);
        }

        $successMessage = 'DV Request has been validated by Accountant.';
        if ($dvRequest->getOriginal('is_urgent') == 1) {
            $successMessage .= ' Urgent flag has been automatically removed.';
        }

        return redirect()->route('Accounting.dvrequests.index')
            ->with('success', $successMessage);

        return response()->json([
            'success' => true,
            'message' => 'Document has been finalized and forwarded to RANCA.' . 
                        ($dvRequest->is_urgent ? ' [🚨 URGENT]' : ''),
            'data' => [
                'id' => $dvRequest->id,
                'status' => $newStatus,
                'finalized_at' => $finalizedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'outgoing_finalized_at' => $finalizedAt->format('M d, Y h:i A'),
                'is_urgent' => $dvRequest->is_urgent ?? false,
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Error validating DV in RANCA: ' . $e->getMessage());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
        }

        return back()->with('error', 'An error occurred while validating the request.');
    }
}

    /** Reject DV Request */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000|min:10',
        ], [
            'remarks.required' => 'Please provide a reason for rejection.',
            'remarks.min' => 'Rejection reason must be at least 10 characters.',
        ]);

        try {
            $dvRequest = DvRequest::with(['orsRequest', 'focalPerson'])->findOrFail($id);
            $statusBefore = $dvRequest->status;
            $currentUserId = auth()->id();
            $rejectedAt = now();
            $newStatus = 'RANCA_rejected';

            // Check if request can be rejected by Accountant
            if ($dvRequest->status !== 'RANCA_received') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request cannot be rejected at its current status: ' . $statusBefore
                ], 422);
            }

            // Check if locked by another user
            $lockDuration = now()->subMinutes(10);
            if ($dvRequest->locked_by && 
                $dvRequest->locked_by !== $currentUserId && 
                $dvRequest->locked_at >= $lockDuration) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is currently locked by another user.'
                ], 422);
            }

            // Initialize notification variables
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
                // Update accountant specific timestamps using trait
                $this->updateDepartmentTimestamp($dvRequest, $newStatus);
                
                $dvRequest->refresh();
                
                // Update DV Request
                $dvRequest->update([
                    'last_action_at' => $rejectedAt,
                    'last_action_type' => 'rejected',
                    'last_acted_by' => $currentUserId,
                    'status' => $newStatus,
                    'rejected_by'  => $currentUserId,
                    'rejected_at'  => $rejectedAt,
                    'locked_by'    => null,
                    'locked_at'    => null,
                ]);

                // Update ORS Request if exists
                if ($dvRequest->orsRequest) {
                    $dvRequest->orsRequest->update([
                        'status'      => $newStatus,
                        'rejected_by' => $currentUserId,
                        'rejected_at' => $rejectedAt,
                    ]);
                }

                // Send rejection notifications
                $emailSent = $this->sendRejectionEmail($dvRequest, $request->remarks);
                $smsSent = $this->sendRejectionSMS($dvRequest, $request->remarks);

                // Save validation history
                $this->saveRANCAValidationHistory(
                    $dvRequest,
                    'reject',
                    $request->remarks,
                    [
                        'status_before'    => $statusBefore,
                        'status_after'     => $newStatus,
                        'rejected_by'      => $currentUserId,
                        'rejected_at'      => $rejectedAt->toISOString(),
                        'rejection_reason' => $request->remarks,
                        'accountant_last_action_at' => $this->safeDateToISO($dvRequest->accountant_last_action_at),
                        'last_action_at' => $this->safeDateToISO($dvRequest->last_action_at),
                        'amount'           => $dvRequest->amount,
                        'dv_number'        => $dvRequest->dv_number,
                        'ors_request_id'   => $dvRequest->orsRequest->id ?? null,
                        'email_sent'       => $emailSent,
                        'sms_sent'         => $smsSent
                    ]
                );

                // Log action
                $this->logAction(
                    $dvRequest->id,
                    'Accountant_III',
                    $newStatus,
                    'DV request rejected by Accountant: ' . $request->remarks . 
                    ' | Accountant last action at: ' . $this->formatDateForLog($dvRequest->accountant_last_action_at)
                );

                // Create notification for focal person
                $focalId = $dvRequest->focalperson_id;
                if ($focalId) {
                    Notification::create([
                        'focalperson_id' => $focalId,
                        'dv_request_id'  => $dvRequest->id,
                        'status'         => $newStatus,
                        'action'         => 'Rejected by Accountant',
                        'remarks'        => $request->remarks,
                    ]);

                    // Trigger Pusher event to focal person
                    $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => $newStatus,
                        'action'        => 'Rejected',
                        'remarks'       => $request->remarks,
                        'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);
                }

                // Trigger Pusher event to RANCA channel
                $this->pusher->trigger('dv-requests-ranca', 'dv-request-rejected', [
                    'dv_request_id'      => $dvRequest->id,
                    'control_number'     => $dvRequest->accounting_control_number ? 
                        'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) : 
                        'Unknown',
                    'status'             => $newStatus,
                    'rejected_by'        => auth()->user()->name ?? 'RANCA Officer',
                    'action_performer_id' => $currentUserId,
                    'accountant_last_action_at' => $this->safeDateToISO($dvRequest->accountant_last_action_at),
                    'last_action_at' => $this->safeDateToISO($dvRequest->last_action_at),
                    'timestamp'          => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            });

            $this->triggerDashboardUpdate();

            // Update response message with notification status
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
                        'id'          => $dvRequest->id,
                        'status'      => $newStatus,
                        'rejected_at' => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'accountant_last_action_at' => $this->formatDateForLog($dvRequest->accountant_last_action_at),
                        'last_action_at' => $this->formatDateForLog($dvRequest->last_action_at),
                        'email_sent'  => $emailSent,
                        'sms_sent'    => $smsSent
                    ]
                ]);
            }

            return redirect()->route('Accounting.dvrequests.index')
                ->with('success', $responseMessage);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->validator->errors()->first()
                ], 422);
            }
            return redirect()->back()->withErrors($e->validator)->withInput();
            
        } catch (\Exception $e) {
            Log::error('Error rejecting DV request in RANCA: ' . $e->getMessage(), [
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

            return redirect()->back()->with('error', 'An error occurred while rejecting the request.');
        }
    }

    /** Release lock */
    public function releaseLock($id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->locked_by === auth()->id()) {
            $dvRequest->update([
                'locked_by' => null,
                'locked_at' => null,
                'status'    => 'Outgoing_finalized',
            ]);
        }

        return response()->json(['success' => true]);
    }

    /** Refresh lock */
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

    /** History logs */
    public function history(Request $request)
    {
        $query = RANCAValidationHistory::with([
            'dvRequest.program',
            'dvRequest.focalPerson',
            'dvRequest.respocenter',
            'dvRequest.orsRequest',
            'verifier'
        ])
        ->where('verified_by', auth()->id());

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

        return view('Accounting.loghistory', compact('verificationHistory'));
    }

    /** Helper Methods */
    
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

    protected function sendRejectionSMS($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            if (!$focalPerson || empty($focalPerson->contactnumber)) {
                Log::warning('Cannot send RANCA rejection SMS: focal person or contact number missing', [
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
            $message = "Hi {$focalPerson->fname}, your DV Request #{$userSequence} has been rejected by RANCA. Reason: {$truncatedRemarks}.";

            Log::info('Sending RANCA rejection SMS via iProg', [
                'phone' => $phoneNumber,
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $userSequence,
                'message_length' => strlen($message)
            ]);

            $result = $this->smsService->sendSMS($phoneNumber, $message);
            
            if ($result['success']) {
                Log::info('RANCA rejection SMS sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'user_sequence' => $userSequence,
                    'phone' => $phoneNumber,
                    'message_id' => $result['message_id'] ?? 'N/A'
                ]);
                return true;
            } else {
                Log::error('Failed to send RANCA rejection SMS', [
                    'dv_request_id' => $dvRequest->id,
                    'user_sequence' => $userSequence,
                    'phone' => $phoneNumber,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('RANCA SMS rejection notification failed: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $dvRequest->user_sequence ?? 'N/A',
                'exception' => $e->getTraceAsString()
            ]);
            return false;
        }
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
            
            Log::info('Attempting to send RANCA rejection email via SendPulse', [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'dv_request_id' => $dvRequest->id
            ]);

            if (!class_exists(SendPulseServices::class)) {
                Log::error('SendPulseServices class not found');
                return $this->sendFallbackEmail($toEmail, $toName, $dvRequest, 'RANCA_rejected', $rejectionRemarks);
            }

            $sendPulseService = new SendPulseServices();
            $result = $sendPulseService->sendRejectionEmail($toEmail, $toName, $dvRequest, $rejectionRemarks);
            
            if ($result) {
                Log::info('SendPulse RANCA rejection email sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'to_email' => $toEmail
                ]);
            } else {
                Log::error('SendPulse failed to send RANCA rejection email', [
                    'dv_request_id' => $dvRequest->id,
                    'to_email' => $toEmail
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('SendPulse RANCA rejection email failed: ' . $e->getMessage(), [
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
                'updatedBy' => auth()->user()->name ?? 'Accountant'
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

    /** Logging helper */
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

    protected function saveRANCAValidationHistory($dvRequest, $step, $remarks = null, $data = [])
    {
        try {
            RANCAValidationHistory::create([
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
            Log::error('Failed to save RANCA verification history', [
                'dv_request_id' => $dvRequest->id ?? null,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);

            throw $e;
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
            'accountant_received_at' => $dvRequest->accountant_received_at,
            'accountant_verified_at' => $dvRequest->accountant_verified_at,
            'accountant_last_action_at' => $dvRequest->accountant_last_action_at,
            'history_reason' => $reason,
            'moved_to_history_at' => now(),
            'moved_by' => auth()->id(),
        ]);
    }
}