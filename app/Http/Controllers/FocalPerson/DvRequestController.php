<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;

use App\Models\DvRequest;
use App\Notifications\DvRequestUpdated;
use App\Models\DvRequestLog;
use App\Models\Program;
use App\Models\ModeOfPayment;
use App\Models\PaymentDetail;
use App\Models\DvRequestModeOfPayment;
use App\Services\PusherService;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class DvRequestController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

  public function index()
{
    // Ensure focal person is authenticated
    if (!Auth::guard('focal')->check()) {
        return redirect()->route('login')->with('error', 'Please log in first.');
    }

    $focalId = Auth::guard('focal')->id();

    try {
        // Fetch DV requests with related Focal Person and Program
        $dvRequests = DvRequest::with(['focalperson', 'program','respocenter', 'modeOfPayments' ])
                        ->where('focalperson_id', $focalId)
                        ->latest()
                        ->get();
    } catch (\Exception $e) {
        return back()->with('error', 'Database error: ' . $e->getMessage());
    }

    return view('FocalPerson.dvrequest.index', compact('dvRequests'));
}

   public function refresh()
    {
        $dvRequests = DvRequest::select('id', 'status', 'dv_number', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($dvRequests);
    } 
    public function create()
    {
        $focalPerson = Auth::guard('focal')->user(); // authenticated focal person
        $programs = Program::all();
        $mops = ModeOfPayment::all();
        return view('FocalPerson.dvrequest.submitdvors', compact('programs','focalPerson','mops'));
    }

    /**
     * Store a newly created DV Request.
     */
  public function store(Request $request)
{
    $request->validate([
        'program_id'  => 'required|exists:programs,id',
        'respocenter_id' =>'required|exists:responcenters,id',
        'amount'      => 'required|numeric|min:0',
        'particulars' => 'required|string|max:255',
        'mode_of_payment_id' => 'required|exists:mode_of_payments,id',
        'payment_details.bank_name'   => 'nullable|string|max:255',
        'payment_details.bank_accnum' => 'nullable|string|max:255',
        'payment_details.bank_address'=> 'nullable|string|max:255',
    ]);

    $focalpersonId = Auth::guard('focal')->id();

    if (!$focalpersonId) {
        abort(403, 'Only focal persons can submit DV requests.');
    }

    $dv = DB::transaction(function () use ($request, $focalpersonId) {
        // global control number (accounting)
        $nextControlNumber = (DvRequest::max('accounting_control_number') ?? 0) + 1;

        // per-user sequence
        $nextUserSequence = (DvRequest::where('focalperson_id', $focalpersonId)->max('user_sequence') ?? 0) + 1;

        // generate a unique reference_number (very unlikely to loop, but we check existence)
        do {
            $referenceNumber = 'DVORS-' . strtoupper(Str::random(8)); // e.g. DV-8F3C9A2B
        } while (DvRequest::where('reference_number', $referenceNumber)->exists());

        // create DV
        $dv = DvRequest::create([
            'focalperson_id'            => $focalpersonId,
            'reference_number'          => $referenceNumber,
            'accounting_control_number' => $nextControlNumber,
            'user_sequence'             => $nextUserSequence,
            'program_id'                => $request->program_id,
            'amount'                    => $request->amount,
            'particulars'               => $request->particulars,
            'fname'                     => $request->fname,
            'mi'                        => $request->mi,
            'lname'                     => $request->lname,
            'tin_number'                => $request->tin,
            'respocenter_id'            => $request->respocenter_id,
            'address'                   => $request->address,
            'status'                    => 'submitted',
        ]);

        // attach mode of payment
        $dv->modeOfPayments()->attach($request->mode_of_payment_id);

        // retrieve pivot and save payment details
        $dvRequestMop = DvRequestModeOfPayment::where('dv_request_id', $dv->id)
            ->where('mode_of_payment_id', $request->mode_of_payment_id)
            ->first();

        $dvRequestMop->paymentDetail()->create([
            'bank_name'   => $request->input("payment_details.bank_name"),
            'bank_accnum' => $request->input("payment_details.bank_accnum"),
            'bank_address'=> $request->input("payment_details.bank_address"),
        ]);

        return $dv;
    });

    $this->logAction($dv, 'SUBMITTED', 'submitted', 'Request submitted by Focal Person');

    return redirect()->route('FocalPerson.dvrequest.index')
        ->with('success', 'DV Request submitted successfully!');
}

    public function show(DvRequest $dvRequest)
    {
       
        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized access.');
        }
        $dvRequest->load(['program', 'logs.focalPerson', 'logs.accountingUnit']);

        $logs = $dvRequest->logs;
        $lastLog = $dvRequest->logs()->latest()->first();
        return response()->json([
        'lastStep' => $lastLog ? $lastLog->role : 'Submitted'
        ]);

        return view('FocalPerson.dvrequest.show', compact('dvRequest', 'logs', 'lastStep'));
    }
        public function search(Request $request)
    {
        if (!Auth::guard('focal')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $focalId = Auth::guard('focal')->id();
        $query   = $request->get('query');

        $dvRequests = DvRequest::with(['program', 'respocenter'])
            ->where('focalperson_id', $focalId)
            ->where(function ($q) use ($query) {
                $q->where('control_no', 'like', "%{$query}%")   // ✅ added control_no
                ->orWhere('particulars', 'like', "%{$query}%")
                ->orWhere('amount', 'like', "%{$query}%")
                ->orWhere('status', 'like', "%{$query}%")
                ->orWhereHas('program', function ($p) use ($query) {
                    $p->where('prcode', 'like', "%{$query}%");
                })
                ->orWhereHas('respocenter', function ($r) use ($query) {
                    $r->where('rccode', 'like', "%{$query}%");
                });
            })
            ->latest()
            ->get();

        return response()->json($dvRequests);
}
        public function edit(DvRequest $dvRequest)
    {
        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized access.');
        }

           if (!in_array($dvRequest->status, ['submitted', 'ORS_for_revision'])) {
            return redirect()->route('FocalPerson.dvrequest.index')
                ->with('error', 'This DV request cannot be edited unless it is in ORS_for_revision status.');
        }

      
    $programs = Program::all();
    $dvRequest->load('modeOfPayments');

        return view('FocalPerson.dvrequest.edit', compact('dvRequest', 'programs'));
    }

    public function update(Request $request, DvRequest $dvRequest)
{
    if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
        abort(403, 'Unauthorized access.');
    }

    // Allow editing if status is 'submitted' or 'ORS_for_revision'
    if (!in_array($dvRequest->status, ['submitted', 'ORS_for_revision'])) {
        return redirect()->route('FocalPerson.dvrequest.index')
            ->with('error', 'This DV request cannot be updated unless it is in submitted or ORS_for_revision status.');
    }

    $request->validate([
        'program_id'            => 'required|exists:programs,id',
        'respocenter_id'        => 'required|exists:responcenters,id',
        'amount'                => 'required|numeric|min:0',
        'particulars'           => 'required|string|max:255',
        'fname'                 => 'nullable|string|max:255',
        'mi'                    => 'nullable|string|max:10',
        'lname'                 => 'nullable|string|max:255',
        'tin_number'            => 'nullable|string|max:20',
        'address'               => 'nullable|string|max:255',
        'mode_of_payment_id'    => 'required|exists:mode_of_payments,id',
        'payment_details.bank_name'   => 'nullable|string|max:255',
        'payment_details.bank_accnum' => 'nullable|string|max:255',
        'payment_details.bank_address'=> 'nullable|string|max:255',
    ]);

    // Capture old values for logging
    $oldData = $dvRequest->only([
        'program_id', 'respocenter_id', 'amount', 'particulars',
        'fname', 'mi', 'lname', 'tin_number', 'address'
    ]);

    $dvRequest->update($request->only(array_keys($oldData)));

    // Handle mode of payment update
    $modeId = $request->mode_of_payment_id;
    if (!$dvRequest->modeOfPayments->contains($modeId)) {
        // Detach old and attach new mode
        $dvRequest->modeOfPayments()->sync([$modeId]);
    }

    // Update payment details in pivot
    $dvRequestMop = DvRequestModeOfPayment::where('dv_request_id', $dvRequest->id)
        ->where('mode_of_payment_id', $modeId)
        ->first();

    if ($dvRequestMop) {
        $dvRequestMop->paymentDetail()->updateOrCreate(
            ['dv_request_mode_of_payment_id' => $dvRequestMop->id],
            [
                'bank_name'   => $request->input("payment_details.bank_name"),
                'bank_accnum' => $request->input("payment_details.bank_accnum"),
                'bank_address'=> $request->input("payment_details.bank_address"),
            ]
        );
    }

    // Determine changes for logging
    $changes = [];
    foreach ($oldData as $key => $oldValue) {
        $newValue = $dvRequest->$key;
        if ($oldValue != $newValue) {
            $changes[] = ucfirst(str_replace('_', ' ', $key)) . " changed from '{$oldValue}' to '{$newValue}'";
        }
    }

    $changes[] = "Mode of Payment updated"; // Always log if the payment mode can be updated
    $remarks = implode('; ', $changes);

    $this->logAction($dvRequest, 'ORS_revision_updated', 'Updated', $remarks);

    return redirect()->route('FocalPerson.dvrequest.index')
        ->with('success', 'DV Request updated successfully.');
}

        public function destroy(DvRequest $dvRequest)
    {
        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized action.');
                }

                $dvRequest->delete();

                return redirect()->route('FocalPerson.dvrequest.index')
                                ->with('success', 'DV Request deleted successfully.');
            }
        

public function logs(DvRequest $dvRequest, Request $request)
{
    $logs = $dvRequest->logs()
        ->with(['focalperson', 'accountingUnit'])
        ->orderBy('created_at', 'desc')
        ->get();

    if ($request->ajax() || $request->get('ajax') == '1') {
        $html = '';
        
        foreach ($logs as $index => $log) {
            $actorName = 'System';
            $profileImage = asset('storage/profiles/default.png');
            
            // Determine actor name and profile image
            if ($log->focalperson) {
                $actorName = trim($log->focalperson->fname . ' ' . ($log->focalperson->mi ?? '') . ' ' . $log->focalperson->lname);
                if ($log->focalperson->profile_image) {
                    $profileImage = asset('storage/profiles/' . $log->focalperson->profile_image);
                }
            } elseif ($log->accountingUnit) {
                $actorName = ($log->accountingUnit->fname ?? '') . ' ' . ($log->accountingUnit->lname ?? '');
                if ($log->accountingUnit->profile_image) {
                    $profileImage = asset('storage/profiles/' . $log->accountingUnit->profile_image);
                }
            } elseif ($log->external_actor) {
                $actorName = $log->external_actor;
            }
            
            // Build table row HTML
            $html .= '<tr class="hover:bg-gray-50 transition-colors duration-150">';
            
            // Step number
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $html .= '<div class="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-800 rounded-full text-xs font-bold">';
            $html .= ($index + 1);
            $html .= '</div></td>';
            
            // Actor info
            $html .= '<td class="px-6 py-4"><div class="flex items-center">';
            $html .= '<div class="flex-shrink-0 w-10 h-10 rounded-full overflow-hidden">';
            
            if ($log->focalperson || $log->accountingUnit) {
                $html .= '<img src="' . $profileImage . '" class="w-10 h-10 object-cover">';
            } else {
                $html .= '<div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center rounded-full">';
                $html .= '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>';
                $html .= '</svg></div>';
            }
            
            $html .= '</div><div class="ml-3">';
            $html .= '<div class="text-sm font-medium text-gray-900">' . htmlspecialchars($actorName) . '</div>';
            $html .= '<div class="text-xs text-gray-500">' . htmlspecialchars(ucfirst($log->action)) . '</div>';
            $html .= '</div></div></td>';
            
            // Remarks
            $html .= '<td class="px-6 py-4">';
            if ($log->remarks && trim($log->remarks) !== '') {
                $html .= '<div class="bg-gray-50 rounded-lg p-3 border-l-4 border-blue-400">';
                $html .= '<p class="text-gray-700">' . htmlspecialchars(ucfirst($log->remarks)) . '</p></div>';
            } else {
                $html .= '<span class="text-gray-400 italic">No remarks</span>';
            }
            $html .= '</td>';
            
            // Date & Time
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $html .= '<div class="text-sm text-gray-900 font-medium">' . $log->created_at->format('M d, Y') . '</div>';
            $html .= '<div class="text-xs text-gray-500">' . $log->created_at->format('h:i A') . '</div>';
            $html .= '<div class="text-xs text-gray-400 mt-1">' . $log->created_at->diffForHumans() . '</div>';
            $html .= '</td></tr>';
        }
        
        return response()->json([
            'html' => $html,
            'count' => $logs->count(),
            'lastUpdated' => $logs->first()?->created_at->diffForHumans() ?? 'Never'
        ]);
    }

    // Regular view response for initial page load
    $steps = ['Submitted', 'IMSD', 'Incoming', 'Budget Officer', 'Outgoing','Accountant III','Regional Director','Cashier', 'Completed'];
    
    return view('FocalPerson.dvrequest.logs', compact('dvRequest', 'logs', 'steps'));
}
protected function logAction(DvRequest $dvRequest, string $status, string $action, string $remarks): void
{
    $focalId = Auth::guard('focal')->id();
    $accountingUnitId = null;
    $actorName = 'System';
    $actorRole = 'System';
    $actorAvatar = asset('storage/profiles/default.png'); // fallback

    // Check if accounting unit is logged in
    if (Auth::guard('web')->check()) {
        $accountingUnit = Auth::guard('web')->user();
        $accountingUnitId = $accountingUnit->id;
        
        // Fix: Use proper field names for accounting unit
        $actorName = trim(($accountingUnit->fname ?? '') . ' ' . ($accountingUnit->lname ?? '')) ?: 'Accounting Unit';
        $actorRole = 'Accounting Unit';
        
        // Fix: Check for profile image
        if (!empty($accountingUnit->profile_image)) {
            $actorAvatar = asset('storage/profiles/' . $accountingUnit->profile_image);
        }
    } 
    // Check if focal person is logged in
    elseif ($focalId) {
        $focal = Auth::guard('focal')->user();
        
        // Fix: Use proper field names for focal person
        $actorName = trim(($focal->fname ?? '') . ' ' . ($focal->mi ?? '') . ' ' . ($focal->lname ?? '')) ?: 'Focal Person';
        $actorRole = 'Focal Person';
        
        // Fix: Check for profile image
        if (!empty($focal->profile_image)) {
            $actorAvatar = asset('storage/profiles/' . $focal->profile_image);
        }
    }

    // Create the log entry
    $log = DvRequestLog::create([
        'dv_request_id'     => $dvRequest->id,
        'focalperson_id'    => $focalId,
        'accountingunit_id' => $accountingUnitId,
        'status'            => $status,
        'action'            => $action,
        'remarks'           => $remarks,
    ]);

    // Create notification
    $notification = Notification::create([
        'focalperson_id' => $focalId,
        'dv_request_id' => $dvRequest->id,
        'status' => $status,
        'action' => $action,
        'remarks' => $remarks,
    ]);

    // Fix: Use the focal person ID for the channel, not the request ID
    $channelName = 'dv-requests-' . ($dvRequest->focalperson_id ?? $focalId);
    
    // Trigger Pusher event
    $this->pusher->trigger($channelName, 'status-updated', [
        'id'            => $notification->id,
        'dv_request_id' => $dvRequest->id,
        'dv_number'     => $dvRequest->dv_number,
        'status'        => $status,
        'action'        => $action,
        'remarks'       => $remarks,
        'actor_name'    => $actorName,
        'actor_role'    => $actorRole,
        'actor_avatar'  => $actorAvatar,
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        'relative'      => now()->diffForHumans(),
    ]);
}




   public function latestStep(DvRequest $dvRequest)
{
    $lastLog = $dvRequest->logs()->latest()->first();
    $lastAction = $lastLog ? $lastLog->action : 'submitted';

    $statusToStepMap = [
        'submitted' => 'Submitted',
        'IMSD_received' => 'IMSD',
        'IMSD_in_process' => 'IMSD',
        'IMSD_verified' => 'IMSD',
        'Incoming-TUPAD_received' => 'Incoming',
        'Incoming-TUPAD_in_process' => 'Incoming',
        'Incoming-TUPAD_verified' => 'Incoming',
        'Incoming-TUPAD_rejected' => 'Incoming',
        'Incoming-GIP_received' => 'Incoming',
        'Incoming-GIP_in_process' => 'Incoming',
        'Incoming-GIP_verified' => 'Incoming',
        'Incoming-GIP_rejected' => 'Incoming',
        'Incoming-TEV_received' => 'Incoming',
        'Incoming-TEV_in_process' => 'Incoming',
        'Incoming-TEV_verified' => 'Incoming',
        'Incoming-TEV_rejected' => 'Incoming',
        'ORS_received' => 'Budget_Officer',
        'ORS_in_process' => 'Budget_Officer',
        'ORS_obligated' => 'Budget_Officer',
        'ORS_onhold' => 'Budget_Officer',
        'ORS_for_revision' => 'Budget_Officer',
        'Outgoing_received' => 'Outgoing',
        'Outgoing_in_process' => 'Outgoing',
        'Outgoing_finalized' => 'Outgoing',
        'RANCA_received' => 'Accounting_III',
        'RANCA_in_process' => 'Accounting_III',
        'RANCA_validated' => 'Accounting_III',
        'RANCA_amount_updated' => 'Accounting_III',
    ];

    // Map lastStep from latest log only
    $lastStep = $statusToStepMap[$lastAction] ?? $lastAction;

    $isRejected = stripos($lastAction, 'rejected') !== false;

    return response()->json([
        'lastStep' => $lastStep,
        'isRejected' => $isRejected
    ]);
}



}
   