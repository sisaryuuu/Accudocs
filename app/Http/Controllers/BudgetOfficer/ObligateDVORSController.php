<?php

namespace App\Http\Controllers\BudgetOfficer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrsRequest;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Program;
use App\Services\PusherService;
use App\Services\SendPulseServices; // ADD SENDPULSE SERVICE
use App\Models\Notification;
use App\Models\BudgetObligationHistory;
use App\Models\DocumentAgingAlert;
use App\Models\UacsCode;
use App\Models\AdlCode;
use App\Models\AllotmentClass;
use App\Models\Mfopap;
use App\Models\ContinuingOrCurrent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\TriggersDashboardUpdate; // ADD DASHBOARD UPDATE TRAIT

class ObligateDVORSController extends Controller
{
    protected PusherService $pusher;
    use TriggersDashboardUpdate; // USE THE TRAIT

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    public function index()
    {
        return view('BudgetOfficer.index');
    }

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
                ->whereIn('status', [
                    'Incoming-TUPAD_verified', 
                    'Incoming-GIP_verified',
                    'Incoming-TEV_verified',
                    'ORS_received',
                    'ORS_onhold',
                ])
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

                // ✅ SAFE: Get ADL code
                $adlCode = ($orsRequest && $orsRequest->AdlCode) ? 
                    $orsRequest->AdlCode->adlcode . ' - ' . $orsRequest->AdlCode->description : 
                    '—';
                
                // ✅ SAFE: Get MFO/PAP code (FIXED)
                $mfopapCode = '—';
                $mfocode = '—';
                $mfodescription = '—';
                
                if ($orsRequest && $orsRequest->Mfopap) {
                    $mfocode = $orsRequest->Mfopap->mfocode ?? '—';
                    $mfodescription = $orsRequest->Mfopap->mfodescription ?? '—';
                    $mfopapCode = $mfocode . ' - ' . $mfodescription;
                }

                // ✅ SAFE: Get ORS number
                $orsNumber = $orsRequest ? ($orsRequest->ors_number ?? null) : null;

                return [
                    'id'           => $req->id,
                    'control_no'   => $req->accounting_control_number ? 
                        'CN #-' . now()->year . '-' . str_pad($req->accounting_control_number, 5, '0', STR_PAD_LEFT) : 
                        '—',
                    'submitted_by' => trim(($req->focalPerson->fname ?? '') . ' ' . ($req->focalPerson->lname ?? '')),
                    'program'      => $req->program->prcode ?? 'N/A',
                    'respocenter'  => trim(($req->respocenter->rccode ?? '') . ' - ' . ($req->respocenter->description ?? '')) ?? 'N/A',
                    'amount'       => number_format($req->amount, 2),
                    'status'       => $req->status,
                    'date'         => $req->created_at->format('M d, Y'),
                    'ors_number'   => $orsNumber,
                    'uacs_codes'   => $uacsDisplay,
                    'adl_code'     => $adlCode,
                    'mfopap_code'  => $mfopapCode, // ✅ FIXED: Added this field
                    'mfocode'      => $mfocode,
                    'mfodescription' => $mfodescription,
                ];
            }));

        } catch (\Exception $e) {
            \Log::error('Error in Budget Officer fetchDvRequests: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Failed to fetch DV requests: ' . $e->getMessage()
            ], 500);
        }
    }

    public function redirectToFirstPending()
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        // ✅ FIXED: Corrected whereHas syntax
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
            'gipAttachments', // ✅ ADD GIP attachments
            'tupadAttachments', // ✅ ADD TUPAD attachments  
            'tevAttachments', // ✅ ADD TEV attachments
            'program' // ✅ Ensure program is loaded
        ])->findOrFail($id);

        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        // ✅ FIXED: Changed locked_fby to locked_by
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

        // ✅ GET ATTACHMENTS BASED ON PROGRAM
        $attachments = collect();
        $selectedModality = null;
        $tevAttachments = collect();
        $tevSelectedModality = null;

        switch ($dvRequest->program->prcode ?? '') {
            case 'GIP':
                $attachments = $dvRequest->gipAttachments ?? collect();
                break;
                
            case 'TUPAD':
                $selectedModality = $dvRequest->tupad_modality;
                if ($selectedModality) {
                    $attachments = $dvRequest->tupadAttachments()
                        ->where('modality', $selectedModality)
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
            'mfopaps'             => Mfopap::all(),
            'contcurrents'        => ContinuingOrCurrent::all(),
            'uacsCodes'           => $uacsCodes,
            'amount'              => $dvRequest->amount,
            'attachments'         => $attachments, // ✅ PASS ATTACHMENTS
            'selectedModality'    => $selectedModality, // ✅ PASS MODALITY
            'tevAttachments'      => $tevAttachments, // ✅ PASS TEV ATTACHMENTS
            'tevSelectedModality' => $tevSelectedModality, // ✅ PASS TEV MODALITY
        ]);
    }

    public function receive(Request $request, $id)
    {
        try {
            $dvRequest = DvRequest::with(['orsRequest', 'focalPerson'])->findOrFail($id);
            $statusBefore = $dvRequest->status;
            $receivedAt = now();
            $currentUserId = auth()->id();

            // FIXED: Check if it's already received by Budget Officer, not just if it's not verified
            if ($dvRequest->status === 'ORS_received') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This request has already been received by Budget Officer.'
                    ], 422);
                }
                return redirect()->back()->with('error', 'This request has already been received by Budget Officer.');
            }

            // FIXED: Check if the request is in a state that can be received
            if (!in_array($dvRequest->status, ['Incoming-TUPAD_verified', 'Incoming-GIP_verified','Incoming-TEV_verified'])) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This request cannot be received. Current status: ' . $statusBefore
                    ], 422);
                }
                return redirect()->back()->with('error', 'This request cannot be received. Current status: ' . $statusBefore);
            }

            // Use database transaction to ensure data consistency
            DB::transaction(function () use (
                $dvRequest, 
                $statusBefore, 
                $receivedAt, 
                $request, 
                $currentUserId
            ) {
                // Update ORS Request first (if exists)
                if ($dvRequest->orsRequest) {
                    $dvRequest->orsRequest->update([
                        'status'            => 'ORS_received',
                        'gross'             => $request->input('amount', $dvRequest->amount),
                        'accountingunit_id' => $currentUserId,
                        'received_by'       => $currentUserId,
                        'received_at'       => $receivedAt,
                    ]);
                }

                // Update DV Request
                $dvRequest->update([
                    'status'            => 'ORS_received',
                    'gross'             => $request->input('amount', $dvRequest->amount),
                    'accountingunit_id' => $currentUserId,
                    'received_by'       => $currentUserId,
                    'received_at'       => $receivedAt,
                    'locked_by'         => $currentUserId,
                    'locked_at'         => $receivedAt,
                ]);

                // ✅ SEND EMAIL NOTIFICATION USING SENDPULSE
             

                // ✅ SAVE VERIFICATION HISTORY (NO ACKNOWLEDGMENT)
                $this->saveBudgetObligationHistory(
                    $dvRequest,
                    'receive',
                    'ORS request received by Budget Officer for processing',
                    [
                        'status_before' => $statusBefore,
                        'status_after'  => 'ORS_received',
                        'received_by' => $currentUserId,
                        'received_at' => $receivedAt->toISOString(),
                        'amount' => $dvRequest->amount,
                        'ors_request_id' => $dvRequest->orsRequest->id ?? null
                    ]
                );

                $this->logAction(
                    $dvRequest->id,
                    'Budget_Officer',
                    $dvRequest->status,
                    'ORS request received by Budget Officer for processing'
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

                // Trigger Pusher events
                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => $dvRequest->status,
                    'remarks'       => $remarks,
                    'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);

                $this->pusher->trigger('dv-requests-budget-officer', 'dv-request-received', [
                    'dv_request_id' => $dvRequest->id,
                    'control_number' => $dvRequest->accounting_control_number ? 
                        'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 5, '0', STR_PAD_LEFT) : 'Unknown',
                    'status' => $dvRequest->status,
                    'submitted_by' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                    'received_by' => auth()->user()->name ?? 'Budget Officer',
                    'timestamp' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'action_performer_id' => $currentUserId,
                    'received_by_user_id' => $currentUserId, 
                ]);
            });

            $this->triggerDashboardUpdate(); // TRIGGER DASHBOARD UPDATE

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document has been received by Budget Officer (ORS).',
                    'data' => [
                        'id'          => $dvRequest->id,
                        'status'      => $dvRequest->status,
                        'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
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
        $request->validate([
            'amount'  => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dvRequest = DvRequest::with('orsRequest')->findOrFail($id);

        if (!in_array($dvRequest->status, ['ORS_received', 'ORS_onhold'])) {
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
            return response()->json([
                'success' => false,
                'message' => 'This request is currently locked by another user.'
            ], 422);
        }

        $oldAmount = $dvRequest->amount;
        $newAmount = $request->amount;

        try {
            DB::beginTransaction();

            $dvRequest->update([
                'amount'    => $newAmount,
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
                'Budget_Officer',
                'Amount_Edited',
                "DV/ORS Gross updated from ₱" . number_format($oldAmount, 2) . 
                " to ₱" . number_format($newAmount, 2) . 
                ($request->remarks ? " - Reason: " . $request->remarks : "")
            );

            if ($dvRequest->focalperson_id) {
                Notification::create([
                    'focalperson_id' => $dvRequest->focalperson_id,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $dvRequest->status,
                    'action'         => 'Amount Updated',
                    'remarks'        => $request->remarks ?? "Budget Officer updated the DV/ORS gross amount from ₱" . number_format($oldAmount, 2) . " to ₱" . number_format($newAmount, 2),
                ]);

                // ✅ Trigger Pusher events
                $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'amount-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'old_amount'    => number_format($oldAmount, 2),
                    'new_amount'    => number_format($newAmount, 2),
                    'remarks'       => $request->remarks,
                    'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);

                // ✅ To Budget Officer channel
                $this->pusher->trigger('dv-requests-budget-officer', 'amount-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'old_amount'    => number_format($oldAmount, 2),
                    'new_amount'    => number_format($newAmount, 2),
                    'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'action_performer_id' => $currentUserId,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Amount successfully updated.',
                'data' => [
                    'old_amount' => number_format($oldAmount, 2),
                    'new_amount' => number_format($newAmount, 2),
                    'dv_amount'  => number_format($dvRequest->amount, 2),
                    'ors_gross'  => number_format($dvRequest->orsRequest->gross ?? 0, 2),
                    'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating amount: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the amount.'
            ], 500);
        }
    }

    public function storeADLCode(Request $request)
    {
        try {
            \Log::info('storeADLCode called with data:', $request->all());

            // Fix: Change 'adl_codes' to 'adlcodes'
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

    /**
     * Check if ADL Code already exists
     */
    public function checkADLCode(Request $request)
    {
        // Fix: Change 'adl_codes' to 'adlcodes'
        $exists = AdlCode::where('adlcode', $request->adlcode)->exists();
        
        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'ADL Code already exists' : 'ADL Code is available'
        ]);
    }

public function store(Request $request, $dvRequestId)
{
    $request->validate([
        'uacscode_ids'       => 'required|array|min:1',
        'uacscode_ids.*'     => 'required|exists:uacscodes,id|distinct',
        'adlcode_ids'        => 'required|array|min:1',
        'adlcode_ids.*'      => 'required|exists:adlcodes,id|distinct',
        'allotment_class_id' => 'required|exists:allotment_classes,id',
        'mfopap_id'          => 'required|exists:mfopaps,id',
        'contcurrent_id'     => 'required|exists:contcurrents,id',
        'gross'              => 'nullable|numeric',
        'remarks'            => 'nullable|string|max:1000',
    ]);

    // Add extensive debugging
    \Log::info('=== START ORS STORE METHOD ===');
    \Log::info('DV Request ID:', ['id' => $dvRequestId]);
    \Log::info('User ID:', ['user_id' => auth()->id()]);

    $dvRequest = DvRequest::findOrFail($dvRequestId)->fresh();
    $statusBefore = $dvRequest->status;
    $currentUserId = auth()->id();

    \Log::info('Before DV Request update:', [
        'id' => $dvRequest->id,
        'current_status' => $dvRequest->status,
        'locked_by' => $dvRequest->locked_by,
        'locked_at' => $dvRequest->locked_at
    ]);

    // METHOD 1: Use save() instead of update() for better reliability
    $dvRequest->status = 'ORS_obligated';
    $dvRequest->locked_by = null;
    $dvRequest->locked_at = null;
    $saveResult = $dvRequest->save();

    \Log::info('DV Request save result:', [
        'success' => $saveResult,
        'new_status' => $dvRequest->status,
        'id' => $dvRequest->id
    ]);

    // Refresh and verify
    $dvRequest->refresh();
    \Log::info('After refresh:', [
        'status' => $dvRequest->status,
        'is_obligated' => $dvRequest->status === 'ORS_obligated'
    ]);

    $orsNumber = null;
    $orsRequest = null;

    DB::transaction(function () use ($request, $dvRequest, &$orsNumber, &$orsRequest) {
        \Log::info('Starting transaction for ORS creation');
        
        $orsNumber = $this->generateOrsNumber($request->allotment_class_id, $request->contcurrent_id, $dvRequest);
        
        // ✅ NOTE: We'll store the first ADL code as primary for backward compatibility
        $primaryAdlCodeId = $request->adlcode_ids[0];
        
        // Create ORS Request
        $orsRequest = OrsRequest::create([
            'ors_number'         => $orsNumber,
            'dv_request_id'      => $dvRequest->id,
            'focalperson_id'     => $dvRequest->focalperson_id,         
            'accountingunit_id'  => $dvRequest->accounting_id ?? null,
            'adlcode_id'         => $primaryAdlCodeId,
            'allotment_class_id' => $request->allotment_class_id,
            'mfopap_id'          => $request->mfopap_id,
            'contcurrent_id'     => $request->contcurrent_id,
            'gross'              => $dvRequest->amount,
            'remarks'            => $request->remarks,
            'status'             => 'ORS_obligated',
            'obligated_by'       => auth()->id(),
            'obligated_at'       => now(),
        ]);

        \Log::info('ORS Request created:', [
            'ors_id' => $orsRequest->id,
            'ors_number' => $orsRequest->ors_number,
            'status' => $orsRequest->status
        ]);
        
        // Attach multiple UACS codes to ORS Request
        $uacsAttachData = [];
        foreach ($request->uacscode_ids as $index => $uacsId) {
            $uacsAttachData[$uacsId] = ['order' => $index + 1];
        }
        $orsRequest->uacsCodes()->attach($uacsAttachData);
        
        // ✅ NEW: Attach multiple ADL codes to ORS Request
        $adlAttachData = [];
        foreach ($request->adlcode_ids as $index => $adlId) {
            $adlAttachData[$adlId] = ['order' => $index + 1];
        }
        $orsRequest->adlCodes()->attach($adlAttachData);

        \Log::info('UACS and ADL codes attached:', [
            'uacs_count' => count($uacsAttachData),
            'adl_count' => count($adlAttachData)
        ]);
    });

    // Double-check DV Request status after transaction
    $dvRequest->refresh();
    \Log::info('Final DV Request status check:', [
        'id' => $dvRequest->id,
        'status' => $dvRequest->status,
        'is_obligated' => $dvRequest->status === 'ORS_obligated'
    ]);

    // Get UACS codes data for verification history
    $uacsCodes = UacsCode::whereIn('id', $request->uacscode_ids)->get();
    $uacsData = $uacsCodes->map(function($uacs) {
        return [
            'id' => $uacs->id,
            'code' => $uacs->uacscode,
            'description' => $uacs->description,
            'full_path' => $uacs->full_path
        ];
    })->toArray();

    // ✅ NEW: Get ADL codes data for verification history
    $adlCodes = AdlCode::whereIn('id', $request->adlcode_ids)->get();
    $adlData = $adlCodes->map(function($adl) {
        return [
            'id' => $adl->id,
            'code' => $adl->adlcode,
            'description' => $adl->description
        ];
    })->toArray();

    // Get additional data for verification history
    $allotmentClass = AllotmentClass::find($request->allotment_class_id);
    $mfopap = Mfopap::find($request->mfopap_id);
    $contcurrent = ContinuingOrCurrent::find($request->contcurrent_id);

    // ✅ SAVE VERIFICATION HISTORY
    $this->saveBudgetObligationHistory(
        $dvRequest,
        'obligate',
        $request->remarks ?? "ORS Number $orsNumber assigned with " . count($request->uacscode_ids) . " UACS codes and " . count($request->adlcode_ids) . " ADL codes.",
        [
            'status_before' => $statusBefore,
            'status_after' => 'ORS_obligated',
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
            'amount' => $dvRequest->amount,
            'obligated_by' => auth()->id(),
            'obligated_at' => now()->toISOString()
        ]
    );

    $focalId = $dvRequest->focalperson_id;
    $status  = 'ORS_obligated';
    $action  = 'Obligated';
    $uacsCount = count($request->uacscode_ids);
    $adlCount = count($request->adlcode_ids);
    $remarks = $request->remarks ?? "ORS Request Created with ORS Number: $orsNumber ({$uacsCount} UACS codes, {$adlCount} ADL codes)";

    Notification::create([
        'focalperson_id' => $focalId,
        'dv_request_id'  => $dvRequest->id,
        'status'         => $status,
        'action'         => $action,
        'remarks'        => $remarks,
    ]);

    // Trigger Pusher events
    $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
        'dv_request_id' => $dvRequest->id,
        'status'        => $status,
        'remarks'       => $remarks,
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);

    // To Budget Officer channel
    $this->pusher->trigger('dv-requests-budget-officer', 'ors-obligated', [
        'dv_request_id' => $dvRequest->id,
        'ors_number'    => $orsNumber,
        'status'        => $status,
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        'action_performer_id' => $currentUserId,
    ]);
    
    $this->pusher->trigger('dv-requests-outgoing', 'dv-request-submitted', [
        'dv_request_id' => $dvRequest->id,
        'control_number' => $dvRequest->accounting_control_number,
        'ors_number' => $orsRequest->ors_number,
        'status' => 'ORS_obligated',
        'submitted_by' => $dvRequest->focalPerson->fname . ' ' . $dvRequest->focalPerson->lname,
        'program' => $dvRequest->program->prcode,
        'amount' => $dvRequest->amount,
        'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);
    
    $this->logAction(
        $dvRequest->id,
        'Budget_Officer',
        $dvRequest->status,
        "ORS Number $orsNumber assigned with $uacsCount UACS codes and $adlCount ADL codes."
    );

    $this->triggerDashboardUpdate();

    \Log::info('=== END ORS STORE METHOD - Redirecting to preview ===');
    \Log::info('Redirecting to preview with DV Request ID:', ['id' => $dvRequest->id]);

    return redirect()->route('BudgetOfficer.dvrequests.previewOrs', $dvRequest->id)
        ->with('success', "ORS Request Created with ORS Number: $orsNumber ($uacsCount UACS codes, $adlCount ADL codes)")
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
            'orsRequest',
            'orsRequest.uacsCodes',
            'orsRequest.AdlCode',
            'orsRequest.Mfopap',
            'orsRequest.allotmentClass',
            'orsRequest.continuingOrCurrent',
            'verificationHistories' // Make sure this relationship exists
        ])->findOrFail($id);
        
        \Log::info('DV Request found:', [
            'id' => $dvRequest->id,
            'status' => $dvRequest->status,
            'ors_number' => $dvRequest->orsRequest->ors_number ?? null
        ]);

        // FIXED: Check verification history properly
        $obligatedHistory = $dvRequest->verificationHistories()
            ->where('status_after', 'ORS_obligated')
            ->latest()
            ->first();

        \Log::info('Verification history check:', [
            'has_obligated_history' => !is_null($obligatedHistory),
            'history_status_after' => $obligatedHistory->status_after ?? 'No obligated history',
            'has_ors_request' => !is_null($dvRequest->orsRequest)
        ]);

        // Check if ORS has been obligated in verification history
        if (!$obligatedHistory || !$dvRequest->orsRequest) {
            \Log::warning('ORS not obligated for preview', [
                'id' => $dvRequest->id,
                'has_obligated_history' => !is_null($obligatedHistory),
                'has_ors_request' => !is_null($dvRequest->orsRequest),
                'dv_status' => $dvRequest->status
            ]);
            return redirect()->route('BudgetOfficer.dvrequests.index')
                ->with('error', 'ORS must be obligated before viewing the document.');
        }

        $orsRequest = $dvRequest->orsRequest;

        if (!$orsRequest->ors_number) {
            \Log::warning('ORS number missing for preview', ['id' => $dvRequest->id]);
            return redirect()->back()->with('error', 'ORS Number has not been assigned yet.');
        }

        // Prepare UACS codes display
        $uacsCodes = $orsRequest->uacsCodes;
        
        $uacsDisplay = $uacsCodes->isNotEmpty() 
            ? $uacsCodes->map(function($uacs) {
                return $uacs->uacscode . ' - ' . $uacs->description;
            })->implode("\n") 
            : '—';

        // Prepare MFO/PAP display
        $mfoDisplay = $orsRequest->Mfopap 
            ? $orsRequest->Mfopap->mfocode . ' - ' . $orsRequest->Mfopap->mfodescription 
            : '—';

     

        // Prepare data for the view
        $data = [
            'responsibility_center' => trim(($dvRequest->respocenter->rccode ?? '') . ' - ' . ($dvRequest->respocenter->description ?? '')),
            'ors_no' => $orsRequest->ors_number,
            'payee' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
            'particulars' => $dvRequest->particulars ?? $dvRequest->purpose ?? 'N/A',
            'date' => $orsRequest->obligated_at ? $orsRequest->obligated_at->format('F d, Y') : now()->format('F d, Y'),
            'address' => 'Davao City',      
            'particular_desc' => $dvRequest->program->prcode ?? 'Program',
            'mfo' => $mfoDisplay,
            'uacs' => $uacsDisplay,
            'amount' => $dvRequest->amount,
        ];

        // Status rows
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

        // FIX: Use the same method as DV preview - get from Settings model
        $imsd_head = \App\Models\Setting::get('imsd_head', 'NAME OF IMSD HEAD');
        $budget_officer = \App\Models\Setting::get('budget_officer', auth()->user()->name ?? 'Budget Officer');

        \Log::info('Signatories loaded:', [
            'imsd_head' => $imsd_head,
            'budget_officer' => $budget_officer
        ]);

        \Log::info('Successfully loading preview for ORS: ' . $orsRequest->ors_number);

        return view('BudgetOfficer.previewors', [
            'data' => $data,
            'status' => $status,
            'imsd_head' => $imsd_head,
            'budget_officer' => $budget_officer,
            'dvRequest' => $dvRequest,
            'orsRequest' => $orsRequest,
        ]);

    } catch (\Exception $e) {
        \Log::error('Error in previewOrs: ' . $e->getMessage(), [
            'id' => $id,
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()->route('BudgetOfficer.dvrequests.index')
            ->with('error', 'Error accessing ORS preview: ' . $e->getMessage());
    }
}
    
private function numberToWords($number)
{
    if ($number == 0) {
            return 'zero';
        }

        $ones = [
            '', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
            'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
            'seventeen', 'eighteen', 'nineteen'
        ];

        $tens = [
            '', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'
        ];

        $scales = [
            '', 'thousand', 'million', 'billion', 'trillion'
        ];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            $digit = floor($number / 10);
            $remainder = $number % 10;
            return $tens[$digit] . ($remainder ? '-' . $ones[$remainder] : '');
        }

        if ($number < 1000) {
            $digit = floor($number / 100);
            $remainder = $number % 100;
            return $ones[$digit] . ' hundred' . ($remainder ? ' ' . $this->numberToWords($remainder) : '');
        }

        // Handle larger numbers
        $scaleIndex = 0;
        $words = [];

        while ($number > 0) {
            $chunk = $number % 1000;
            
            if ($chunk > 0) {
                $chunkWords = $this->numberToWords($chunk);
                if ($scales[$scaleIndex]) {
                    $chunkWords .= ' ' . $scales[$scaleIndex];
                }
                array_unshift($words, $chunkWords);
            }
            
            $number = floor($number / 1000);
            $scaleIndex++;
        }

        return implode(' ', $words);
    }

    /**
     * Reject an ORS request
     */
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

            // Check if request can be rejected
            if (!in_array($dvRequest->status, [
                'Incoming-TUPAD_verified', 
                'Incoming-GIP_verified',
                'Incoming-TEV_verified',
                'ORS_received',
                'ORS_onhold'
            ])) {
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

            DB::transaction(function () use (
                $dvRequest, 
                $statusBefore, 
                $rejectedAt, 
                $request, 
                $currentUserId
            ) {
                // Update DV Request
                $dvRequest->update([
                    'status'       => 'ORS_rejected',
                    'rejected_by'  => $currentUserId,
                    'rejected_at'  => $rejectedAt,
                    'locked_by'    => null,
                    'locked_at'    => null,
                ]);

                // Update ORS Request if exists
                if ($dvRequest->orsRequest) {
                    $dvRequest->orsRequest->update([
                        'status'      => 'ORS_rejected',
                        'rejected_by' => $currentUserId,
                        'rejected_at' => $rejectedAt,
                    ]);
                }

                // ✅ SEND REJECTION EMAIL USING SENDPULSE
                $this->sendRejectionEmail($dvRequest, $request->remarks);

                // Save verification history
                $this->saveBudgetObligationHistory(
                    $dvRequest,
                    'reject',
                    $request->remarks,
                    [
                        'status_before'   => $statusBefore,
                        'status_after'    => 'ORS_rejected',
                        'rejected_by'     => $currentUserId,
                        'rejected_at'     => $rejectedAt->toISOString(),
                        'rejection_reason' => $request->remarks,
                        'amount'          => $dvRequest->amount,
                        'ors_request_id'  => $dvRequest->orsRequest->id ?? null
                    ]
                );

                // Log action
                $this->logAction(
                    $dvRequest->id,
                    'Budget_Officer',
                    'ORS_rejected',
                    'ORS request rejected: ' . $request->remarks
                );

                // Create notification for focal person
                $focalId = $dvRequest->focalperson_id;
                if ($focalId) {
                    Notification::create([
                        'focalperson_id' => $focalId,
                        'dv_request_id'  => $dvRequest->id,
                        'status'         => 'ORS_rejected',
                        'action'         => 'Rejected',
                        'remarks'        => $request->remarks,
                    ]);

                    // Trigger Pusher event to focal person
                    $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => 'ORS_rejected',
                        'action'        => 'Rejected',
                        'remarks'       => $request->remarks,
                        'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);
                }

                // Trigger Pusher event to Budget Officer channel
                $this->pusher->trigger('dv-requests-budget-officer', 'ors-rejected', [
                    'dv_request_id'      => $dvRequest->id,
                    'control_number'     => $dvRequest->accounting_control_number ? 
                        'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 5, '0', STR_PAD_LEFT) : 
                        'Unknown',
                    'status'             => 'ORS_rejected',
                    'rejected_by'        => auth()->user()->name ?? 'Budget Officer',
                    'timestamp'          => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'action_performer_id' => $currentUserId,
                ]);
            });

            $this->triggerDashboardUpdate(); // TRIGGER DASHBOARD UPDATE

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'ORS request has been rejected successfully.',
                    'data' => [
                        'id'          => $dvRequest->id,
                        'status'      => 'ORS_rejected',
                        'rejected_at' => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                    ]
                ]);
            }

            return redirect()->route('BudgetOfficer.dvrequests.index')
                ->with('success', 'ORS request has been rejected successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->validator->errors()->first()
                ], 422);
            }
            return redirect()->back()->withErrors($e->validator)->withInput();
            
        } catch (\Exception $e) {
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

            return redirect()->back()->with('error', 'An error occurred while rejecting the request.');
        }
    }

    public function history(Request $request)
    {
        $query = BudgetObligationHistory::with([
            'dvRequest.program',
            'dvRequest.focalPerson', 
            'dvRequest.respocenter',
            'orsRequest.uacsCodes',
            'orsRequest.adlCodes',           // Use adlCodes (many-to-many) instead of adlCode
            'orsRequest.Mfopap',
            'orsRequest.AllotmentClass',
            'orsRequest.ContinuingOrCurrent',
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

        // Get programs for filter dropdown
        $programs = Program::all();

        return view('BudgetOfficer.loghistory', compact('verificationHistory', 'programs'));
    }

    // VERIFICATION HISTORY HELPER METHOD
    protected function saveBudgetObligationHistory($dvRequest, $step, $remarks = null, $data = [])
    {
        BudgetObligationHistory::create([
            'dv_request_id' => $dvRequest->id,
            'ors_request_id' => $dvRequest->orsRequest->id ?? null,
            'verified_by' => auth()->id(),
            'verification_step' => $step,
            'status_before' => $data['status_before'] ?? $dvRequest->getOriginal('status'),
            'status_after' => $dvRequest->status,
            'verification_data' => $data,
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

        // ✅ Use the accounting_control_number as the series
        $series = $dvRequest->accounting_control_number; // fallback if null
        $seriesFormatted = str_pad($series, 4, '0', STR_PAD_LEFT);

        $orsNumber = "{$allotment}-{$ccode}-{$year}-{$month}-{$seriesFormatted}";

        // ✅ Ensure uniqueness (just to be safe)
        $exists = OrsRequest::where('ors_number', $orsNumber)->exists();
        if ($exists) {
            // Append a suffix if duplicate found (shouldn't normally happen)
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

        $dvRequest->update([
            'status' => 'ORS_onhold',
        ]);
        
        if ($dvRequest->orsRequest) {
            $dvRequest->orsRequest->update([
                'status' => 'ORS_onhold',
            ]);
        }

        // ✅ SEND EMAIL NOTIFICATION USING SENDPULSE
        

        $focalId = $dvRequest->focalperson_id;
        $status  = 'ORS_onhold';
        $action  = 'On hold';
        $remarks = $request->remarks ?? "ORS request placed on hold";

        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => $status,
            'action'         => $action,
            'remarks'        => $remarks,
        ]);

        // ✅ Trigger Pusher events
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
            'remarks'       => $remarks,
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        // ✅ To Budget Officer channel
        $this->pusher->trigger('dv-requests-budget-officer', 'ors-onhold', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        $this->logAction(
            $dvRequest->id,
            'Budget Officer',
            'ORS_onhold',
            $request->remarks ?? 'ORS request placed on hold'
        );

        $this->triggerDashboardUpdate(); // TRIGGER DASHBOARD UPDATE

        return redirect()
            ->back()
            ->with('success', 'The ORS request has been placed on hold.');
    }

    public function revise(Request $request, $id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        $dvRequest->update([
            'status' => 'ORS_for_revision',
        ]);

        if ($dvRequest->orsRequest) {
            $dvRequest->orsRequest->update([
                'status' => 'ORS_for_revision',
            ]);
        }

        // ✅ SEND EMAIL NOTIFICATION USING SENDPULSE

        $focalId = $dvRequest->focalperson_id;
        $remarks = $request->remarks ?? 'ORS request sent back for revision';

        // ✅ Notify focal person
        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => 'ORS_for_revision',
            'action'         => 'For Revision',
            'remarks'        => $remarks,
        ]);

        // ✅ Trigger Pusher events
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'ORS_for_revision',
            'remarks'       => $remarks,
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        // ✅ To Budget Officer channel
        $this->pusher->trigger('dv-requests-budget-officer', 'ors-revised', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'ORS_for_revision',
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        $this->logAction(
            $dvRequest->id,
            'Budget_Officer',
            'ORS_for_revision',
            $remarks
        );

        $this->triggerDashboardUpdate(); // TRIGGER DASHBOARD UPDATE

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

            // Check if class exists before instantiating
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

    // Add fallback email method
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