<?php

namespace App\Http\Controllers\AccountingIII;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrsRequest;
use App\Models\AdlCode;
use App\Models\UacsCode;
use App\Models\Mfopap;
use App\Models\DvRequest;
use App\Models\RANCAValidationHistory;
use App\Services\PusherService;
use App\Services\SendPulseServices; // ADD SENDPULSE SERVICE
use App\Services\IprogSmsService; // ADD SMS SERVICE
use App\Models\TUPADAttachment;
use App\Models\GIPAttachment;
use App\Models\TEVAttachment;
use App\Models\Notification;
use App\Models\DvRequestLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Traits\EmailBatchingTrait;
class RANCAController extends Controller

{

    use EmailBatchingTrait;
    protected PusherService $pusher;
    protected IprogSmsService $smsService; // ADD SMS SERVICE PROPERTY

    public function __construct(PusherService $pusher, IprogSmsService $smsService) // UPDATE CONSTRUCTOR
    {
        $this->pusher = $pusher;
        $this->smsService = $smsService; // INITIALIZE SMS SERVICE
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
        
        // Get search parameter from request
        $searchTerm = $request->input('search', '');

        $query = DvRequest::with([
                'program', 
                'focalPerson', 
                'respocenter',
                'orsRequest',
            ])
            ->whereIn('status', ['Outgoing_finalized', 'RANCA_received']) // RANCA_in_process removed
            ->where(function ($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by')
                  ->orWhere('locked_by', $currentUserId)
                  ->orWhere('locked_at', '<=', $lockDuration);
            })
            ->when($searchTerm, function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    // Search in control number
                    $q->where('accounting_control_number', 'LIKE', "%{$searchTerm}%")
                      // Search in DV number
                      ->orWhere('dv_number', 'LIKE', "%{$searchTerm}%")
                      // Search in focal person name
                      ->orWhereHas('focalPerson', function ($personQuery) use ($searchTerm) {
                          $personQuery->where('fname', 'LIKE', "%{$searchTerm}%")
                                     ->orWhere('lname', 'LIKE', "%{$searchTerm}%");
                      })
                      // Search in program code
                      ->orWhereHas('program', function ($programQuery) use ($searchTerm) {
                          $programQuery->where('prcode', 'LIKE', "%{$searchTerm}%");
                      })
                      // Search in respocenter
                      ->orWhereHas('respocenter', function ($centerQuery) use ($searchTerm) {
                          $centerQuery->where('rccode', 'LIKE', "%{$searchTerm}%")
                                     ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                      })
                      // Search in particulars
                      ->orWhere('particulars', 'LIKE', "%{$searchTerm}%")
                      // Search in amount (handle numeric search)
                      ->orWhere('amount', 'LIKE', "%{$searchTerm}%")
                      // Search in status
                      ->orWhere('status', 'LIKE', "%{$searchTerm}%")
                      // Search in ORS number
                      ->orWhereHas('orsRequest', function ($orsQuery) use ($searchTerm) {
                          $orsQuery->where('ors_number', 'LIKE', "%{$searchTerm}%");
                      });
                });
            });

        // Get all DV requests with created_at for age calculation
        $dvRequests = $query->select('dv_requests.*')
                            ->addSelect(DB::raw('TIMESTAMPDIFF(DAY, dv_requests.created_at, NOW()) as days_old'))
                            ->get();
        
        // Calculate priority for Accounting/RANCA
        $dvRequests = $dvRequests->map(function ($request) {
            // Calculate days old
            $daysOld = now()->diffInDays($request->created_at);
            
            // Priority scoring system for Accounting/RANCA:
            // - Critical (10+ days): 1000 points
            // - High (7-9 days): 500 points
            // - Medium (3-6 days): 200 points
            // - Recent (0-2 days): 0 points
            
            $priorityScore = 0;
            
            if ($daysOld >= 10) {
                $priorityScore += 1000; // Critical priority
            } elseif ($daysOld >= 7) {
                $priorityScore += 500; // High priority
            } elseif ($daysOld >= 3) {
                $priorityScore += 200; // Medium priority
            }
            
            // Higher priority for pending receipt (Outgoing_finalized)
            if ($request->status === 'Outgoing_finalized') {
                $priorityScore += 100; // Extra priority for unreceived documents
            }
            
            // Higher priority for in-process documents
            if ($request->status === 'RANCA_received' || $request->status === 'RANCA_in_process') {
                $priorityScore += 50; // Medium priority for documents being processed
            }
            
            // Add small random factor to prevent exact ties
            $priorityScore += rand(0, 10);
            
            $request->days_old = $daysOld;
            $request->priority_score = $priorityScore;
            $request->urgency = $this->getAccountingUrgencyLevel($daysOld);
            
            return $request;
        });
        
        // Sort by priority score (descending) so highest priority comes first
        $dvRequests = $dvRequests->sortByDesc('priority_score')->values();

        return response()->json($dvRequests->map(function ($req) {
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
                'date'         => $req->created_at->format('M d, Y'),
                'created_at_timestamp' => $req->created_at->toISOString(),
                'days_old'     => $req->days_old,
                'urgency'      => $req->urgency,
                'priority_score' => $req->priority_score,
            ];
        }));

    } catch (\Exception $e) {
        \Log::error('Error in Accounting fetchDvRequests: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'error' => 'Failed to fetch DV requests: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Get Accounting urgency level based on whole number of days old
 */
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
        $dvRequest = DvRequest::with(['respocenter','focalPerson','orsRequest','tupadAttachments','tevAttachments','logs'])
            ->findOrFail($id);

        $currentUserId = auth()->id();
        $lockDuration  = now()->subMinutes(10);

        if ($dvRequest->locked_by 
            && $dvRequest->locked_by !== $currentUserId 
            && $dvRequest->locked_at >= $lockDuration) {
            return redirect()->route('Accounting.dvrequests.index')
                ->with('error', 'This DV Request is currently locked by another user.');
        }

        $selectedModality = $dvRequest->tupad_modality;
    
        $attachments = collect();
        if ($selectedModality) {
            $attachments = $dvRequest->tupadAttachments()
                ->where('modality', $selectedModality)
                ->orderBy('sort_order')
                ->get();
        }

        $dvRequest->update([
            'locked_by' => $currentUserId,
            'locked_at' => now(),
        ]);

        $lastLog  = $dvRequest->logs()->latest()->first();
        $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;


          $tevSelectedModality= $dvRequest->tev_modality;

    $tevAttachments = collect();
    if($tevSelectedModality){
        $tevAttachments = $dvRequest->tevAttachments()
        ->where('modality', $tevSelectedModality)
        ->orderBy('sort_order')
        ->get();
    }

    $lastLog  = $dvRequest->logs()->latest()->first();
    $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

        return view('Accounting.validatedv', compact('dvRequest', 'lastStep','selectedModality','attachments','tevSelectedModality','tevAttachments'));
    }

    public function receive(Request $request, $id)
    {
        try {
            $dvRequest = DvRequest::findOrFail($id);
            $statusBefore = $dvRequest->status;
            $receivedAt = now();

            if ($dvRequest->status !== 'Outgoing_finalized') {
                return response()->json(['success' => false, 'message' => 'Not ready for RANCA.'], 422);
            }

            $dvRequest->update([
                'status'            => 'RANCA_received',
                'accountingunit_id' => auth()->id(),
                'received_by'       => auth()->id(),
                'received_at'       => $receivedAt,
                'locked_by'         => auth()->id(),
                'locked_at'         => $receivedAt,
            ]);

            // ✅ SAVE VERIFICATION HISTORY
            $this->saveRANCAValidationHistory(
                $dvRequest,
                'receive',
                'DV Request received by Accountant for processing',
                [
                    'status_before' => $statusBefore,
                    'status_after' =>'RANCA_received',
                    'received_by' => auth()->id(),
                    'received_at' => $receivedAt->toISOString(),
                    'amount' => $dvRequest->amount,
                    'dv_number' => $dvRequest->dv_number
                ]
            );

            $this->logAction($dvRequest->id, 'Accountant_III', 'RANCA_received', 'Received by RANCA');

            // Notify focal person
            $focalId = $dvRequest->focalperson_id;
            $remarks = 'DV Request received by RANCA for processing';

            if ($focalId) {
                Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => 'RANCA_received',
                    'action'         => 'Received',
                    'remarks'        => $remarks,
                ]);

                // Real-time notification to focal person
                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => 'RANCA_received',
                    'remarks'       => $remarks,
                    'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            }

            // ✅ ADD action_performer_id
            $this->pusher->trigger('dv-requests-ranca', 'dv-request-received', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'RANCA_received',
                'action_performer_id' => auth()->id(), // ✅ ADDED
                'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            // JSON response
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Document has been received by Accountant.',
                    'data' => [
                        'id' => $dvRequest->id,
                        'status' => 'RANCA_received',
                        'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                    ]
                ]);
            }

            return redirect()->route('Accounting.dvrequests.index')
                ->with('success', 'Document has been received by Accountant.');

        } catch (\Exception $e) {
            \Log::error('Error receiving DV in RANCA: ' . $e->getMessage());
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while processing the request.');
        }
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
                    'message' => 'Amount can only be edited while request is received or in process.'
                ], 422);
            }

            $oldAmount = $dvRequest->amount;
            $newAmount = $request->amount;

            DB::beginTransaction();

            $dvRequest->update([
                'amount' => $newAmount,
                'locked_by' => $currentUserId,
                'locked_at' => now(),
            ]);

            if ($dvRequest->orsRequest) {
                $dvRequest->orsRequest->update([
                    'gross' => $newAmount,
                ]);
            }

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
                    'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating amount in RANCA: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the amount.'
            ], 500);
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
                             ->with('info', 'No DV requests pending RANCA processing.');
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

    /** Validate/Finalize DV */
  /** Validate/Finalize DV */
public function validateDV(Request $request, $id)
{
    $request->validate([
        'remarks' => 'nullable|string|max:1000',
    ]);

    try {
        $dvRequest = DvRequest::with(['focalPerson', 'program', 'respocenter'])->findOrFail($id);
        $statusBefore = $dvRequest->status;

        if ($dvRequest->status !== 'RANCA_received') {
            return redirect()->back()->with('error', 'This DV request cannot be finalized.');
        }

        $finalizedAt = now();
        $dvRequest->update([
            'status'       => 'RANCA_validated',
            'qr_status'    => 'active', 
            'finalized_by' => auth()->id(),
            'finalized_at' => $finalizedAt,
            'locked_by'    => null,
            'locked_at'    => null,
        ]);

        $dvRequest->refresh(); // Refresh to get updated data

        $remarks = $request->remarks ?? 'DV Request finalized by Accountant';

        // ✅ SAVE VERIFICATION HISTORY
        $this->saveRANCAValidationHistory(
            $dvRequest,
            'validate',
            $remarks,
            [
                'status_before' => $statusBefore,
                'status_after' => 'RANCA_validated',
                'dv_number' => $dvRequest->dv_number,
                'qr_status' => 'active',
                'validated_by' => auth()->id(),
                'validated_at' => $finalizedAt->toISOString(),
                'amount' => $dvRequest->amount,
                'program' => $dvRequest->program->prcode ?? null
            ]
        );

        $this->logAction(
            $dvRequest->id, 
            'Accountant_III', 
            'RANCA_validated', 
            $remarks
        );

        // Notify focal person
        $focalId = $dvRequest->focalperson_id;

        if ($focalId) {
            Notification::create([
                'focalperson_id' => $focalId,
                'dv_request_id'  => $dvRequest->id,
                'status'         => 'RANCA_validated',
                'action'         => 'Finalized',
                'remarks'        => $remarks,
            ]);

            $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'RANCA_validated',
                'remarks'       => $remarks,
                'timestamp'     => $finalizedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);
        }
        
        // ✅ FIXED: Send complete DV data to regional director dashboard
        $this->pusher->trigger('regional-director-dashboard', 'new-dv-available', [
            'dv_id' => $dvRequest->id,
            'dv_number' => $dvRequest->dv_number,
            'status' => 'RANCA_validated',
            'amount' => $dvRequest->amount,
            'payee' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
            'program' => $dvRequest->program->prcode ?? null,
            'timestamp' => $finalizedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            'validated_by' => auth()->user()->name,
            'dv_data' => [ // ✅ ADD complete DV data
                'id' => $dvRequest->id,
                'dv_number' => $dvRequest->dv_number,
                'reference_number' => $dvRequest->reference_number,
                'amount' => $dvRequest->amount,
                'status' => 'RANCA_validated',
                'mode_of_payment' => $dvRequest->mode_of_payment,
                'particulars' => $dvRequest->particulars,
                'qr_code_token' => $dvRequest->qr_code_token,
                'qr_status' => $dvRequest->qr_status,
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
        $this->pusher->trigger('dv-requests-ranca', 'dv-request-finalized', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'RANCA_validated',
            'action_performer_id' => auth()->id(),
            'timestamp'     => $finalizedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'DV Request has been finalized by Accountant.',
                'data' => [
                    'id' => $dvRequest->id,
                    'status' => 'RANCA_validated',
                ]
            ]);
        }

        return redirect()->route('Accounting.dvrequests.index')
            ->with('success', 'DV Request has been finalized by Accountant.');

    } catch (\Exception $e) {
        \Log::error('Error finalizing DV in RANCA: ' . $e->getMessage());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
        }

        return back()->with('error', 'An error occurred while finalizing the request.');
    }
}

    /** Reject DV Request - RANCA Version with SMS Notification */
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

            // ✅ Initialize notification variables
            $emailSent = false;
            $smsSent = false;

            DB::transaction(function () use (
                $dvRequest, 
                $statusBefore, 
                $rejectedAt, 
                $request, 
                $currentUserId,
                &$emailSent,
                $smsSent
            ) {
                // Update DV Request
                $dvRequest->update([
                    'status'       => 'RANCA_rejected',
                    'rejected_by'  => $currentUserId,
                    'rejected_at'  => $rejectedAt,
                    'locked_by'    => null,
                    'locked_at'    => null,
                ]);

                // Update ORS Request if exists
                if ($dvRequest->orsRequest) {
                    $dvRequest->orsRequest->update([
                        'status'      => 'RANCA_rejected',
                        'rejected_by' => $currentUserId,
                        'rejected_at' => $rejectedAt,
                    ]);
                }

                // ✅ SAVE VERIFICATION HISTORY
                $this->saveRANCAValidationHistory(
                    $dvRequest,
                    'reject',
                    $request->remarks,
                    [
                        'status_before'    => $statusBefore,
                        'status_after'     => 'RANCA_rejected',
                        'rejected_by'      => $currentUserId,
                        'rejected_at'      => $rejectedAt->toISOString(),
                        'rejection_reason' => $request->remarks,
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
                    'RANCA_rejected',
                    'DV request rejected by Accountant: ' . $request->remarks
                );

                // Create notification for focal person
                $focalId = $dvRequest->focalperson_id;
                if ($focalId) {
                    Notification::create([
                        'focalperson_id' => $focalId,
                        'dv_request_id'  => $dvRequest->id,
                        'status'         => 'RANCA_rejected',
                        'action'         => 'Rejected by Accountant',
                        'remarks'        => $request->remarks,
                    ]);

                    // Trigger Pusher event to focal person
                    $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => 'RANCA_rejected',
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
                    'status'             => 'RANCA_rejected',
                    'rejected_by'        => auth()->user()->name ?? 'RANCA Officer',
                    'timestamp'          => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'action_performer_id' => $currentUserId,
                ]);
            });

            
                // ✅ SEND REJECTION EMAIL USING SENDPULSE
            $emailSent = $this->sendRejectionEmail($dvRequest, $request->remarks);

                // ✅ NEW: SEND REJECTION SMS
            $smsSent = $this->sendRejectionSMS($dvRequest, $request->remarks);

            $this->triggerDashboardUpdate();

            // ✅ UPDATE RESPONSE MESSAGE TO INCLUDE NOTIFICATION STATUS
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
                        'status'      => 'RANCA_rejected',
                        'rejected_at' => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
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
            \Log::error('Error rejecting DV request in RANCA: ' . $e->getMessage(), [
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

    /** ✅ SEND REJECTION SMS - For RANCA rejections */
    protected function sendRejectionSMS($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            // Use 'contactnumber' instead of 'phone'
            if (!$focalPerson || empty($focalPerson->contactnumber)) {
                Log::warning('Cannot send Budget Officer rejection SMS: focal person or contact number missing', [
                    'dv_request_id' => $dvRequest->id
                ]);
                return false;
            }

            $phoneNumber = $focalPerson->contactnumber;
            
            // Truncate remarks for SMS (SMS has character limits)
            $truncatedRemarks = strlen($rejectionRemarks) > 100 
                ? substr($rejectionRemarks, 0, 97) . '...' 
                : $rejectionRemarks;

            // ✅ Use user_sequence instead of id
            $userSequence = $dvRequest->user_sequence ?? 'N/A';

            $message = "Hi {$focalPerson->fname}, your DV Request #{$userSequence} has been rejected by Accountant. Reason: {$truncatedRemarks}. - ACCUDOCS DOLE XI";

            \Log::info('Sending RANCA rejection SMS via iProg', [
                'phone' => $phoneNumber,
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $userSequence,
                'message_length' => strlen($message)
            ]);

            $result = $this->smsService->sendSMS($phoneNumber, $message);
            
            if ($result['success']) {
                \Log::info('RANCA rejection SMS sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'user_sequence' => $userSequence,
                    'phone' => $phoneNumber,
                    'message_id' => $result['message_id'] ?? 'N/A'
                ]);
                return true;
            } else {
                \Log::error('Failed to send RANCA rejection SMS', [
                    'dv_request_id' => $dvRequest->id,
                    'user_sequence' => $userSequence,
                    'phone' => $phoneNumber,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            \Log::error('RANCA SMS rejection notification failed: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $dvRequest->user_sequence ?? 'N/A',
                'exception' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /** ✅ SEND REJECTION EMAIL - For RANCA rejections */
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
            
            \Log::info('Attempting to send RANCA rejection email via SendPulse', [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'dv_request_id' => $dvRequest->id
            ]);

            // Check if class exists before instantiating
            if (!class_exists(SendPulseServices::class)) {
                \Log::error('SendPulseServices class not found');
                return $this->sendFallbackEmail($toEmail, $toName, $dvRequest, 'RANCA_rejected', $rejectionRemarks);
            }

            $sendPulseService = new SendPulseServices();
            $result = $sendPulseService->sendRejectionEmail($toEmail, $toName, $dvRequest, $rejectionRemarks);
            
            if ($result) {
                \Log::info('SendPulse RANCA rejection email sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'to_email' => $toEmail
                ]);
            } else {
                \Log::error('SendPulse failed to send RANCA rejection email', [
                    'dv_request_id' => $dvRequest->id,
                    'to_email' => $toEmail
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            \Log::error('SendPulse RANCA rejection email failed: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id,
                'exception' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /** ✅ FALLBACK EMAIL METHOD */
    protected function sendFallbackEmail($toEmail, $toName, $dvRequest, $status, $remarks = null)
    {
        try {
            \Log::info('Using fallback SMTP email for status: ' . $status);
            
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

            \Log::info('Fallback SMTP email sent successfully', ['to' => $toEmail, 'status' => $status]);
            return true;
        } catch (\Exception $e) {
            \Log::error('Fallback SMTP also failed: ' . $e->getMessage());
            return false;
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

    /** History logs - UPDATED to use verification history */
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

        return view('Accounting.loghistory', compact('verificationHistory'));
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

    // ✅ ADD this new method for saving verification history
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
            \Log::error('Failed to save RANCA verification history', [
                'dv_request_id' => $dvRequest->id ?? null,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);

            // Optionally, rethrow to ensure rollback inside DB::transaction
            throw $e;
        }
    }
}