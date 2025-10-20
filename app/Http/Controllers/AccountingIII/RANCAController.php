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
use App\Models\TUPADAttachment;
use App\Models\GIPAttachment;
use App\Models\TEVAttachment;
use App\Models\Notification;
use App\Models\DvRequestLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RANCAController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    /** Dashboard */
    public function index()
    {
        return view('Accounting.index');
    }

    /** Fetch DV requests for RANCA */
    public function fetchDvRequests()
    {
        try {
            $currentUserId = auth()->id();
            $lockDuration  = now()->subMinutes(10);

            $dvRequests = DvRequest::with([
                    'program', 
                    'focalPerson', 
                    'respocenter',
                    'orsRequest',
                    'orsRequest.uacsCodes',
                    'orsRequest.AdlCode',
                    'orsRequest.Mfopap'
                ])
               ->whereIn('status', ['Outgoing_finalized','RANCA_received','RANCA_in_process'])
                ->where(function ($q) use ($currentUserId, $lockDuration) {
                    $q->whereNull('locked_by')
                      ->orWhere('locked_by', $currentUserId)
                      ->orWhere('locked_at', '<=', $lockDuration);
                })
                ->latest()
                ->get();

            return response()->json($dvRequests->map(function ($req) {
                // ✅ SAFE: Check if orsRequest exists first
                $orsRequest = $req->orsRequest;

                // ✅ SAFE: Handle UACS codes
                $uacsCodes = $orsRequest ? ($orsRequest->uacsCodes ?? collect()) : collect();
                $uacsDisplay = $uacsCodes->isNotEmpty() 
                    ? $uacsCodes->map(function($uacs) {
                        return $uacs->uacscode . ' - ' . $uacs->description;
                    })->implode('<br>') 
                    : '—';

                // ✅ SAFE: Get ADL code - check if orsRequest AND adlCode exist
                $adlCode = ($orsRequest && $orsRequest->AdlCode) ? 
                    $orsRequest->AdlCode->adlcode . ' - ' . $orsRequest->AdlCode->description : 
                    '—';
                
                // ✅ SAFE: Get MFO/PAP code - check if orsRequest AND mfopap exist
                $mfopapCode = ($orsRequest && $orsRequest->Mfopap) ? 
                    $orsRequest->Mfopap->mfocode . ' - ' . $orsRequest->Mfopap->mfodescription : 
                    '—';

                // ✅ SAFE: Get ORS number
                $orsNumber = $orsRequest ? ($orsRequest->ors_number ?? null) : null;

                return [
                    'id'           => $req->id,
                    'control_no'   => $req->accounting_control_number ? 
                        'CN #-' . now()->year . '-' . str_pad($req->accounting_control_number, 5, '0', STR_PAD_LEFT) : 
                        '—',
                    'dv_number'   => $req->dv_number ?? '—',
                    'submitted_by' => trim(($req->focalPerson->fname ?? '') . ' ' . ($req->focalPerson->lname ?? '')),
                    'program'      => $req->program->prcode ?? 'N/A',
                    'respocenter'  => trim(($req->respocenter->rccode ?? '') . ' - ' . ($req->respocenter->description ?? '')) ?? 'N/A',
                    'amount'       => number_format($req->amount, 2),
                    'status'       => $req->status,
                    'date'         => $req->created_at->format('M d, Y'),
                    'ors_number'   => $orsNumber,
                    'uacs_codes'   => $uacsDisplay,
                    'allotment_code'  => $req->allotcode ?? '—',
                    'adl_code'     => $adlCode,
                    'mfopap_code'  => $mfopapCode,
                ];
            }));

        } catch (\Exception $e) {
            \Log::error('Error in fetchDvRequests: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Failed to fetch DV requests: ' . $e->getMessage()
            ], 500);
        }
    }

    /** Fetch recent DV requests */
    public function fetchDvRecent()
    {
        try {
            $currentUserId = auth()->id();
            $lockDuration  = now()->subMinutes(10);

            $recent = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('status', ['Outgoing_finalized','RANCA_received','RANCA_in_process'])
                ->whereNotNull('focalperson_id')
                ->where(function ($q) use ($currentUserId, $lockDuration) {
                    $q->whereNull('locked_by')
                      ->orWhere('locked_by', $currentUserId)
                      ->orWhere('locked_at', '<=', $lockDuration);
                })
                ->latest()
                ->take(5)
                ->get();

            return response()->json($recent);
            
        } catch (\Exception $e) {
            \Log::error('Error in RANCA fetchDvRecent: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch recent requests'], 500);
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

    /** Receive DV */
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
                'DV Request received by RANCA for processing',
                [
                    'status_before' => $statusBefore,
                    'status_after' =>'RANCA_received',
                    'received_by' => auth()->id(),
                    'received_at' => $receivedAt->toISOString(),
                    'amount' => $dvRequest->amount,
                    'dv_number' => $dvRequest->dv_number
                ]
            );

            $this->logAction($dvRequest->id, 'Accounting_III', 'RANCA_received', 'Received by RANCA');

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

            // Real-time notification to RANCA channel for table update
            $this->pusher->trigger('dv-requests-ranca', 'dv-request-received', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'RANCA_received',
                'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            // JSON response
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Document has been received by RANCA.',
                    'data' => [
                        'id' => $dvRequest->id,
                        'status' => 'RANCA_received',
                        'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                    ]
                ]);
            }

            return redirect()->route('Accounting.dvrequests.index')
                ->with('success', 'Document has been received by RANCA.');

        } catch (\Exception $e) {
            \Log::error('Error receiving DV in RANCA: ' . $e->getMessage());
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while processing the request.');
        }
    }

    /** Start processing */
    public function startProcessing(Request $request, $id)
    {
        try {
            $dvRequest = DvRequest::findOrFail($id);
            $statusBefore = $dvRequest->status;
            $startedAt = now();

            if ($dvRequest->status !== 'RANCA_received') {
                return response()->json(['success' => false, 'message' => 'Must be received first.'], 422);
            }

            $dvRequest->update([
                'status'    => 'RANCA_in_process',
                'locked_by' => auth()->id(),
                'locked_at' => $startedAt,
            ]);

            // ✅ SAVE VERIFICATION HISTORY
            $this->saveRANCAValidationHistory(
                $dvRequest,
                'process',
                'Processing started by RANCA',
                [
                    'status_before' => $statusBefore,
                    'status_after' => 'RANCA_in_process',
                    'processed_by' => auth()->id(),
                    'processed_at' => $startedAt->toISOString(),
                    'amount' => $dvRequest->amount,
                    'dv_number' => $dvRequest->dv_number
                ]
            );

            $this->logAction($dvRequest->id, 'Accounting_III', 'RANCA_in_process', 'Processing started by RANCA');

            // Notify focal person
            $focalId = $dvRequest->focalperson_id;
            $remarks = 'DV Request is now being processed by RANCA';

            if ($focalId) {
                Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => 'RANCA_in_process',
                    'action'         => 'Processing Started',
                    'remarks'        => $remarks,
                ]);

                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => 'RANCA_in_process',
                    'remarks'       => $remarks,
                    'timestamp'     => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            }

            // Real-time notification to RANCA channel
            $this->pusher->trigger('dv-requests-ranca', 'dv-request-processing', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'RANCA_in_process',
                'timestamp'     => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Processing started.',
                    'data' => [
                        'id' => $dvRequest->id,
                        'status' => 'RANCA_in_process',
                        'started_at' => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                    ]
                ]);
            }

            return redirect()->route('Accounting.dvrequests.index')
                ->with('success', 'Processing started.');

        } catch (\Exception $e) {
            \Log::error('Error starting RANCA processing: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while starting processing.');
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

            if (!in_array($dvRequest->status, ['RANCA_received', 'RANCA_in_process'])) {
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
                'Accounting_III',
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
                    'action'         => 'Accounting_III',
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
    public function validateDV(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $dvRequest = DvRequest::findOrFail($id);
            $statusBefore = $dvRequest->status;

            if (!in_array($dvRequest->status, ['RANCA_received', 'RANCA_in_process'])) {
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

            $remarks = $request->remarks ?? 'DV Request finalized by RANCA';

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
                'Accounting_III', 
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
             $this->pusher->trigger('regional-director-dashboard', 'dv-validated-by-accounting', [
            'dv_id'      => $dvRequest->id,
            'dv_number'  => $dvRequest->dv_number,
            'status'     => 'RANCA_validated',
            'amount'     => $dvRequest->amount,
            'payee'      => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
            'program'    => $dvRequest->program->prcode ?? null,
            'timestamp'  => $finalizedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            'validated_by' => auth()->user()->name,
        ]);
            // Real-time notification to RANCA channel
            $this->pusher->trigger('dv-requests-ranca', 'dv-request-finalized', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'RANCA_validated',
                'timestamp'     => $finalizedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'DV Request has been finalized by RANCA.',
                    'data' => [
                        'id' => $dvRequest->id,
                        'status' => 'RANCA_validated',
                    ]
                ]);
            }

            return redirect()->route('Accounting.dvrequests.index')
                ->with('success', 'DV Request has been finalized by RANCA.');

        } catch (\Exception $e) {
            \Log::error('Error finalizing DV in RANCA: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
            }

            return back()->with('error', 'An error occurred while finalizing the request.');
        }
    }

    /** Reject DV */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);

        try {
            $dvRequest = DvRequest::findOrFail($id);
            $statusBefore = $dvRequest->status;

            if (!in_array($dvRequest->status, ['RANCA_in_process'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This DV request cannot be rejected at its current stage.'
                ], 422);
            }

            $rejectedAt = now();
            $focalId = $dvRequest->focalperson_id;
            $remarks = trim($request->remarks);

            $dvRequest->update([
                'status'     => 'RANCA_rejected',
                'locked_by'  => null,
                'locked_at'  => null,
            ]);

            // ✅ SAVE VERIFICATION HISTORY
            $this->saveRANCAValidationHistory(
                $dvRequest,
                'reject',
                $remarks,
                [
                    'status_before' => $statusBefore,
                    'status_after'  => 'RANCA_rejected',
                    'rejected_by' => auth()->id(),
                    'rejected_at' => $rejectedAt->toISOString(),
                    'rejection_reason' => $remarks
                ]
            );

            $this->logAction(
                $dvRequest->id,
                'Accounting_III',
                'RANCA_rejected',
                $remarks
            );

            if ($focalId) {
                Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => 'RANCA_rejected',
                    'action'         => 'Rejected',
                    'remarks'        => $remarks,
                ]);

                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => 'RANCA_rejected',
                    'remarks'       => $remarks,
                    'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            }

            $this->pusher->trigger('dv-requests-ranca', 'dv-request-rejected', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'RANCA_rejected',
                'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'DV request has been rejected successfully.',
                    'data' => [
                        'id'         => $dvRequest->id,
                        'status'     => 'RANCA_rejected',
                        'remarks'    => $remarks,
                    ]
                ]);
            }

            return redirect()->route('Accounting.dvrequests.index')
                             ->with('error', 'DV request has been rejected.');

        } catch (\Exception $e) {
            \Log::error('Error rejecting DV request: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while rejecting the DV request.'
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while rejecting the DV request.');
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
    public function history()
    {
        $verificationHistory = RANCAValidationHistory::with([
            'dvRequest.program',
            'dvRequest.focalPerson', 
            'dvRequest.respocenter',
            'dvRequest.orsRequest',
            'verifier'
        ])
        ->where('verified_by', auth()->id())
        ->orderBy('verified_at', 'desc')
        ->paginate(20);

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
        try{
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
                \Log::error('Failed to save Incoming-GIP verification history', [
                    'dv_request_id' => $dvRequest->id ?? null,
                    'step' => $step,
                    'error' => $e->getMessage(),
                ]);

                // Optionally, rethrow to ensure rollback inside DB::transaction
                throw $e;
            }
    }
}