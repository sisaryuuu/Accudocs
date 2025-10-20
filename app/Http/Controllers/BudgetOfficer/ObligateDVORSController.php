<?php

namespace App\Http\Controllers\BudgetOfficer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrsRequest;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Services\PusherService;
use App\Models\Notification;
use App\Models\BudgetObligationHistory;
use App\Models\UacsCode;
use App\Models\AdlCode;
use App\Models\AllotmentClass;
use App\Models\Mfopap;
use App\Models\ContinuingOrCurrent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ObligateDVORSController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

   public function index()
    {
        $sidebarCounts = $this->getSidebarCounts();
        return view('BudgetOfficer.index', compact('sidebarCounts'));
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
                    'ORS_in_process',
                    'ORS_onhold',
                    'ORS_obligated'
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
            'logs'
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
        ]);
    }

     public function receive(Request $request, $id)
    {
        try {
            $dvRequest = DvRequest::with('orsRequest')->findOrFail($id);
            $statusBefore = $dvRequest->status;
            $receivedAt = now();

            if (!in_array($dvRequest->status, ['Incoming-TUPAD_verified', 'Incoming-GIP_verified','Incoming-TEV_verified'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is not ready to be received by the Budget Officer.'
                ], 422);
            }

            // Update ORS Request first (if exists)
            if ($dvRequest->orsRequest) {
                $dvRequest->orsRequest->update([
                    'status'            => 'ORS_received',
                    'gross'             => $request->input('amount', $dvRequest->amount),
                    'accountingunit_id' => auth()->id(),
                    'received_by'       => auth()->id(),
                    'received_at'       => $receivedAt,
                ]);
            }

            // Update DV Request
            $dvRequest->update([
                'status'            => 'ORS_received',
                'gross'             => $request->input('amount', $dvRequest->amount),
                'accountingunit_id' => auth()->id(),
                'received_by'       => auth()->id(),
                'received_at'       => $receivedAt,
                'locked_by'         => auth()->id(),
                'locked_at'         => $receivedAt,
            ]);

            // ✅ SAVE VERIFICATION HISTORY
            $this->saveBudgetObligationHistory(
                $dvRequest,
                'receive',
                'ORS request received by Budget Officer for processing',
                [
                    'status_before' => $statusBefore,
                    'status_after'  => 'ORS_received',
                    'received_by' => auth()->id(),
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
                'status'        => $dvRequest->status,
                'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

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

    public function startProcessing(Request $request, $id)
    {
        try {
            $dvRequest = DvRequest::with('orsRequest')->findOrFail($id);
            $statusBefore = $dvRequest->status;
            $startedAt = now();

            if ($dvRequest->status !== 'ORS_received') {
                return response()->json([
                    'success' => false,
                    'message' => 'Request must be received before processing.'
                ], 422);
            }

            $dvRequest->update([
                'status'    => 'ORS_in_process',
                'gross'     => $request->input('amount', $dvRequest->amount),
                'locked_by' => auth()->id(),
                'locked_at' => $startedAt,
            ]);

            if ($dvRequest->orsRequest) {
                $dvRequest->orsRequest->update([
                    'gross' => $dvRequest->amount,
                ]);
            }

            // ✅ SAVE VERIFICATION HISTORY
            $this->saveBudgetObligationHistory(
                $dvRequest,
                'process',
                'Processing started by Budget Officer',
                [
                    'status_before' => $statusBefore,
                    'status_after'  => 'ORS_in_process',
                    'processed_by' => auth()->id(),
                    'processed_at' => $startedAt->toISOString(),
                    'amount' => $dvRequest->amount,
                    'ors_request_id' => $dvRequest->orsRequest->id ?? null
                ]
            );

            $this->logAction(
                $dvRequest->id,
                'Budget_Officer',
                $dvRequest->status,
                'Processing started by Budget Officer'
            );

            $focalId = $dvRequest->focalperson_id;
            $status  = $dvRequest->status;
            $action  = 'Processing Started';
            $remarks = $request->remarks ?? 'Request is now being processed by Budget Officer';

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
                'timestamp'     => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            try {
                $this->pusher->trigger('dv-requests-budget-officer', 'dv-request-processing', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => $status,
                    'timestamp'     => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            } catch (\Throwable $e) {
                \Log::warning("Pusher failed during startProcessing: " . $e->getMessage());
            }

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

            return redirect()->route('BudgetOfficer.dvrequests.index')
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

        if (!in_array($dvRequest->status, ['ORS_received', 'ORS_in_process', 'ORS_onhold'])) {
            return response()->json([
                'success' => false,
                'message' => 'Amount can only be edited while request is received, in process, or on hold.'
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
            'adlcode_id'         => 'required|exists:adlcodes,id',
            'allotment_class_id' => 'required|exists:allotment_classes,id',
            'mfopap_id'          => 'required|exists:mfopaps,id',
            'contcurrent_id'     => 'required|exists:contcurrents,id',
            'gross'              => 'nullable|numeric',
            'remarks'            => 'nullable|string|max:1000',
        ]);

        $dvRequest = DvRequest::findOrFail($dvRequestId)->fresh();
        $statusBefore = $dvRequest->status;
        
        $dvRequest->update([
            'status'    => 'ORS_obligated',
            'locked_by' => null,
            'locked_at' => null,
        ]);
        
        $orsNumber = null;
        $orsRequest = null;

        DB::transaction(function () use ($request, $dvRequest, &$orsNumber, &$orsRequest) {
            $orsNumber = $this->generateOrsNumber($request->allotment_class_id, $request->contcurrent_id);
            
            // Create ORS Request
            $orsRequest = OrsRequest::create([
                'ors_number'         => $orsNumber,
                'dv_request_id'      => $dvRequest->id,
                'focalperson_id'     => $dvRequest->focalperson_id,         
                'accountingunit_id'  => $dvRequest->accounting_id ?? null,
                'adlcode_id'         => $request->adlcode_id,
                'allotment_class_id' => $request->allotment_class_id,
                'mfopap_id'          => $request->mfopap_id,
                'contcurrent_id'     => $request->contcurrent_id,
                'gross'              => $dvRequest->amount,
                'remarks'            => $request->remarks,
                'status'             => 'ORS_obligated',
                'obligated_by'       => auth()->id(),
                'obligated_at'       => now(),
            ]);
            
            // Attach multiple UACS codes to ORS Request
            $attachData = [];
            foreach ($request->uacscode_ids as $index => $uacsId) {
                $attachData[$uacsId] = ['order' => $index + 1];
            }
            
            $orsRequest->uacsCodes()->attach($attachData);
        });

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

        // Get additional data for verification history
        $adlCode = AdlCode::find($request->adlcode_id);
        $allotmentClass = AllotmentClass::find($request->allotment_class_id);
        $mfopap = Mfopap::find($request->mfopap_id);
        $contcurrent = ContinuingOrCurrent::find($request->contcurrent_id);

        // ✅ SAVE VERIFICATION HISTORY
        $this->saveBudgetObligationHistory(
            $dvRequest,
            'obligate',
            $request->remarks ?? "ORS Number $orsNumber assigned with " . count($request->uacscode_ids) . " UACS codes.",
            [
                'status_before' => $statusBefore,
                'status_after' => 'ORS_obligated',
                'ors_number' => $orsNumber,
                'ors_request_id' => $orsRequest->id,
                'adlcode_id' => $request->adlcode_id,
                'adlcode' => $adlCode ? $adlCode->adlcode . ' - ' . $adlCode->description : null,
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
        $remarks = $request->remarks ?? "ORS Request Created with ORS Number: $orsNumber ({$uacsCount} UACS codes)";

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
        ]);

        $this->logAction(
            $dvRequest->id,
            'Budget_Officer',
            $dvRequest->status,
            "ORS Number $orsNumber assigned with $uacsCount UACS codes."
        );

        return redirect()->route('BudgetOfficer.dvrequests.index')
            ->with('success', "ORS Request Created with ORS Number: $orsNumber ($uacsCount UACS codes)");
    }

    

    public function releaseLock($id)
    {
        $dvRequest = DvRequest::with('program')->findOrFail($id);

        if ($dvRequest->locked_by === auth()->id()) {
            $status = match ($dvRequest->program->prcode) {
                'GIP'   => 'Incoming-GIP_verified',
                'TUPAD' => 'Incoming-TUPAD_verified',
                default => $dvRequest->status,
            };

            $dvRequest->update([
                'locked_by' => null,
                'locked_at' => null,
                'status'    => $status,
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

   public function history()
    {
        $verificationHistory = BudgetObligationHistory::with([
            'dvRequest.program',
            'dvRequest.focalPerson', 
            'dvRequest.respocenter',
            'orsRequest',
            'orsRequest.uacsCodes',
            'verifier'
        ])
        ->where('verified_by', auth()->id())
        ->orderBy('verified_at', 'desc')
        ->paginate(20);

        return view('BudgetOfficer.loghistory', compact('verificationHistory'));
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

   private function generateOrsNumber($allotmentClassId, $contcurrentId)
{
    $year  = now()->format('Y');
    $month = now()->format('m');

    $allotment = AllotmentClass::findOrFail($allotmentClassId)->allotcode;
    $ccode     = ContinuingOrCurrent::findOrFail($contcurrentId)->ccode;

    $series = OrsRequest::whereYear('created_at', $year)
        ->whereMonth('created_at', $month)
        ->where('allotment_class_id', $allotmentClassId)
        ->where('contcurrent_id', $contcurrentId)
        ->count() + 1;

    // Ensure uniqueness by looping until ORS number not yet used
    do {
        $orsNumber = "{$allotment}-{$ccode}-{$year}-{$month}-" . str_pad($series, 4, '0', STR_PAD_LEFT);
        $exists = OrsRequest::where('ors_number', $orsNumber)->exists();
        $series++;
    } while ($exists);

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
}