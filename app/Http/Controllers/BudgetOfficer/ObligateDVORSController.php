<?php

namespace App\Http\Controllers\BudgetOfficer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrsRequest;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Services\PusherService;
use App\Models\Notification;
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
    $currentUserId = auth()->id();
    $lockDuration  = now()->subMinutes(10);

    $dvRequests = DvRequest::with(['program', 'focalPerson', 'respocenter','orsRequest'])
        ->whereIn('status', ['Incoming-TUPAD_verified', 'Incoming-GIP_verified','ORS_received','ORS_onhold'])
        ->where(function ($q) use ($currentUserId, $lockDuration) {
            $q->whereNull('locked_by')                // not locked
              ->orWhere('locked_by', $currentUserId)  // locked by me
              ->orWhere('locked_at', '<=', $lockDuration); // lock expired
        })
        ->latest()
        ->get();

    return response()->json($dvRequests->map(function ($req) {
        return [
            'id'           => $req->id,
            'submitted_by' => trim(($req->focalPerson->fname ?? '') . ' ' . ($req->focalPerson->lname ?? '')),
            'program'      => $req->program->prcode ?? 'N/A',
            'respocenter'  => trim(($req->respocenter->rccode ?? '') . ' - ' . ($req->respocenter->description ?? '')) ?? 'N/A',
            'amount'       => number_format($req->amount, 2),
            'status'       => $req->status,
            'date'         => $req->created_at->format('M d, Y'),
            'ors_number'   => $req->orsRequest->ors_number ?? null,
            'uacscode_id'  => $req->orsRequest->uacscode_id ?? null,
            'adlcode_id'   => $req->orsRequest->adlcode_id ?? null,
            'mfopap_id'    => $req->orsRequest->mfopap_id ?? null,
        ];
    }));
}

    public function redirectToFirstPending()
{
    $currentUserId = auth()->id();
    $lockDuration = now()->subMinutes(10);

    $pendingRequests = DvRequest::whereHas('program', fn($q) => 
                                $q->where('prcode', 'GIP','TUPAD')) 
                            ->whereIn('status', ['Incoming-TUPAD_verified', 'Incoming-GIP_verified','ORS_received'])
                            ->orderBy('created_at', 'asc')
                            ->get();

    if ($pendingRequests->isEmpty()) {
        return redirect()->route('Budgetofficer.index')
                         ->with('info', 'No ORS requests pending verification.');
    }

    // Check for one you can work on (unlocked or locked by you, or expired lock)
    $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
        return !$req->locked_by 
            || $req->locked_by == $currentUserId 
            || $req->locked_at <= $lockDuration;
    });

    if ($available) {
        return redirect()->route('BudgetOfficer.dvrequests.withid', $available->id);
    }

    // If all are locked by others
    return redirect()->route('BudgetOfficer.index')
                     ->with('info', 'All pending ORS requests are currently locked by other users.');
}

   public function show($id)
{
    $dvRequest = DvRequest::with(['respocenter','focalPerson','orsRequest','logs'])
                    ->findOrFail($id);

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
    return view('BudgetOfficer.obligatedvors', [
        'dvRequest'           => $dvRequest,
        'orsRequest'          => $orsRequest,
        'lastStep'            => $lastStep,
        'uacsCodes'           => UacsCode::all(),
        'adlCodes'            => AdlCode::all(),
        'allotment_classes'   => AllotmentClass::all(),
        'mfopaps'             => Mfopap::all(),
        'contcurrents'        => ContinuingOrCurrent::all(),
    ]);
}

public function receive(Request $request, $id)
{
    try {
        $dvRequest = DvRequest::with('orsRequest')->findOrFail($id);

        // Only allow if status is verified from IMSD or Incoming
        if (!in_array($dvRequest->status, ['Incoming-TUPAD_verified', 'Incoming-GIP_verified'])) {
            return response()->json([
                'success' => false,
                'message' => 'This request is not ready to be received by the Budget Officer.'
            ], 422);
        }

        $receivedAt = now();

        // Update ORS request if it exists
        if ($dvRequest->orsRequest) {
            $dvRequest->orsRequest->update([
                'status'      => 'ORS_received',
                'received_by' => auth()->id(),
                'received_at' => $receivedAt,
            ]);
        }

        // Update DV request
        $dvRequest->update([
            'status'      => 'ORS_received',
            'received_by' => auth()->id(),
            'received_at' => $receivedAt,
            'locked_by'   => auth()->id(),
            'locked_at'   => $receivedAt,
        ]);

        // Log action
        $this->logAction(
            $dvRequest->id,
            'Budget_Officer',
            'ORS_received',
            'ORS request received by Budget Officer for processing'
        );

        // Notify focal person
        $focalId = $dvRequest->focalperson_id;
        $remarks = $request->remarks ?? "ORS request received by Budget Officer for processing";

        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => 'ORS_received',
            'action'         => 'Received',
            'remarks'        => $remarks,
        ]);

        // Real-time push
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'ORS_received',
            'remarks'       => $remarks,
            'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        // Return response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document has been received by Budget Officer (ORS).',
                'data' => [
                    'id'          => $dvRequest->id,
                    'status'      => 'ORS_received',
                    'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                ]
            ]);
        }

        return redirect()->route('BudgetOfficer.dvrequests.index')
            ->with('success', 'Document has been received by Budget Officer (ORS).');

    } catch (\Exception $e) {
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
        $dvRequest = DvRequest::with('orsRequest')->findOrFail($id);

        // ✅ Allow only if already received (ORS_received)
        if ($dvRequest->status !== 'ORS_received') {
            return response()->json([
                'success' => false,
                'message' => 'Request must be received before processing.'
            ], 422);
        }

        $startedAt = now();

        // Update DV request
        $dvRequest->update([
            'status'    => 'ORS_in_process',
            'locked_by' => auth()->id(),
            'locked_at' => $startedAt,
        ]);

        // Log action
        $this->logAction(
            $dvRequest->id,
            'Budget_Officer',
            'ORS_in_process',
            'Processing started by Budget Officer'
        );

        // Notify focal person
        $focalId = $dvRequest->focalperson_id;
        $status  = 'ORS_in_process';
        $action  = 'Processing Started';
        $remarks = $request->remarks ?? 'Request is now being processed by Budget Officer';

        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => $status,
            'action'         => $action,
            'remarks'        => $remarks,
        ]);

        // Trigger real-time event
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
            'remarks'       => $remarks,
            'timestamp'     => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        // Return JSON for AJAX
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

        // Redirect fallback
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
            ->whereIn('status', ['Incoming-TUPAD_verified', 'Incoming-GIP_verified', 'ORS_received'])
            ->whereNotNull('focalperson_id')
             ->where(function ($q) use ($currentUserId, $lockDuration) {
            $q->whereNull('locked_by')                // not locked
              ->orWhere('locked_by', $currentUserId)  // locked by me
              ->orWhere('locked_at', '<=', $lockDuration); // lock expired
        })
            ->latest()
            ->take(5)
            ->get();

        return response()->json($recentDvRequest);
    }

public function store(Request $request, $dvRequestId)
{
    // Validate inputs
    $request->validate([
        'uacscode_id'      => 'required|string|max:255',
        'adlcode_id'       => 'required|string|max:255',
        'allotment_class_id'=> 'required|string|max:255',
        'mfopap_id'        => 'required|string|max:255',
        'contcurrent_id'   => 'required|string|max:255',
        'gross'            => 'nullable|numeric',
        'net'              => 'nullable|numeric',
        'remarks'          => 'nullable|string|max:1000',
    ]);

    $dvRequest = DvRequest::findOrFail($dvRequestId);

    $dvRequest->status  = 'ORS_obligated';
    $dvRequest->locked_by  = null;
    $dvRequest->locked_at  = null;
    $dvRequest->save();

    $year = date('Y');
    $month = date('m');
    $latestOrs = OrsRequest::latest()->first();
    $series = $latestOrs ? $latestOrs->id + 1 : 1;
    $series = str_pad($series, 4, '0', STR_PAD_LEFT);

    // Extract year & month
    $year = now()->format('Y');
    $month = now()->format('m');

    $orsNumber = "{$request->allotment_class_id}-{$request->contcurrent_id}-{$year}-{$month}-{$series}";

    $ors = OrsRequest::create([
        'ors_number'        => $orsNumber,
        'dv_request_id'     => $dvRequest->id,
        'focalperson_id'    => $dvRequest->focalperson_id,
        'accountingunit_id' => $dvRequest->accounting_id ?? null,
        'uacscode_id'       => $request->uacscode_id,
        'adlcode_id'        => $request->adlcode_id,
        'allotment_class_id'=> $request->allotment_class_id,
        'mfopap_id'         => $request->mfopap_id,
        'contcurrent_id'    => $request->contcurrent_id,
        'gross'             => $request->gross,
        'net'               => $request->net,
        'remarks'           => $request->remarks,
        'status'            => 'ORS_received',
        'obligated_by'      => auth()->id(),
        'obligated_at'      => now(),
    ]);

     $ors->update(['status' => 'ORS_obligated']); // ✅ sync status with DV


        $focalId = $dvRequest->focalperson_id;
        $status = 'ORS_obligated';
        $action = 'Obligated'; // or something meaningful for your workflow
        $remarks = $request->remarks ?? "ORS Test Request Created with ORS Number: $orsNumber";

        $notification = Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => $status,
            'action'         => $action,
            'remarks'        => $remarks,
        ]);
        $this->pusher->trigger('dv-requests-' .$focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
            'remarks'       => $remarks,
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

    $this->logAction(
        $dvRequest->id,
        'Budget_Officer',
        'ORS_obligated',
        'ORS Number ' . $orsNumber . ' assigned.'
    );
    return redirect()->route('BudgetOfficer.dvrequests.index')
        ->with('success', "ORS Test Request Created with ORS Number: $orsNumber");
}


      public function releaseLock($id)
{
    $dvRequest = DvRequest::with('program')->findOrFail($id);

    if ($dvRequest->locked_by === auth()->id()) {
        // Determine status based on program
        $status = match ($dvRequest->program->prcode) {
            'GIP'   => 'Incoming-GIP_verified',
            'TUPAD' => 'Incoming-TUPAD_verified',
            default => $dvRequest->status, // fallback to current status if not GIP/TUPAD
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
            'locked_at' => now(), // refresh timestamp
        ]);

        return response()->json(['success' => true, 'locked_at' => $dvRequest->locked_at]);
    }


    public function history()
    {
        $orsRequests = OrsRequest::with(['dvRequest.program', 'dvRequest.focalPerson', 'dvRequest.respocenter'])
            ->whereIn('status', ['ORS_obligated', 'ORS_onhold', 'ORS_for_revision','ORS_received'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('BudgetOfficer.loghistory', compact('orsRequests'));
    }
 private function generateOrsNumber($allotmentClassId, $ccode)
    {
        $year  = now()->format('Y');
        $month = now()->format('m');

        // Count for series
        $series = OrsRequest::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        $allotment = str_pad($allotmentClassId, 2, '0', STR_PAD_LEFT);

        return "{$allotment}-{$ccode}-{$year}-{$month}-" . str_pad($series, 4, '0', STR_PAD_LEFT);
    }
    public function onhold(Request $request, $id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        // Update DV Request status
        $dvRequest->update([
            'status' => 'ORS_onhold',
        ]);
        if ($dvRequest->orsRequest) {
            $dvRequest->orsRequest->update([
                'status' => 'ORS_onhold',
            ]);
        }

        $focalId = $dvRequest->focalperson_id;
        $status = 'ORS_onhold';
        $action = 'On hold'; // or something meaningful for your workflow
        $remarks = $request->remarks ?? "ORS request placed on hold";

        $notification = Notification::create([
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

    // Update DV Request status
    $dvRequest->update([
        'status' => 'ORS_for_revision',
    ]);

    // If ORS exists, update it too
    if ($dvRequest->orsRequest) {
        $dvRequest->orsRequest->update([
            'status' => 'ORS_for_revision',
        ]);
    }

    // Log action
    $this->logAction(
        $dvRequest->id,
        'Budget_Officer',
        'ORS_for_revision',
        $request->remarks ?? 'ORS request sent back for revision'
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


}