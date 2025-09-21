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

class ObligateDVORSController extends Controller
{
     protected PusherService $pusher;

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
    $dvRequests = DvRequest::with(['program', 'focalPerson', 'respocenter','orsRequest'])
        ->whereIn('status', ['Incoming-TUPAD_verified', 'Incoming-GIP_verified','ORS_received','ORS_onhold'])
        ->latest()
        ->get();

    return response()->json($dvRequests->map(function ($req) {
        return [
            'id'           => $req->id,
            'submitted_by' => trim(($req->focalPerson->fname ?? '') . ' ' . ($req->focalPerson->lname ?? '')),
            'program'      => $req->program->prcode ?? 'N/A',
           'respocenter'  => trim(($req->respocenter->rccode ?? '') . ' - ' . ($req ->respocenter->description ?? '')) ?? 'N/A',
            'amount'       => number_format($req->amount, 2),
            'status'       => $req->status,
            'date'         => $req->created_at->format('M d, Y'),
            'ors_number'   => $req->orsRequest->ors_number ?? null,
            'uacscode_id'     => $req->orsRequest->uacscode_id?? null,
            'adlcode_id'      => $req->orsRequest->adlcode_id ?? null,
            'mfopap_id'       => $req->orsRequest->mfopap_id ?? null,
          
        ];
    }));
}
public function redirectToFirstPending()
{
    $dvRequest = DvRequest::whereIn('status', ['ORS_received', 'Incoming-TUPAD_verified','Incoming-GIP_verified','ORS_onhold'])
                          ->orderBy('created_at', 'asc')
                          ->first();

    if (!$dvRequest) {
        return redirect()->route('BudgetOfficer.dvrequests.index')
                         ->with('info', 'No DV requests pending obligation.');
    }

    return redirect()->route('BudgetOfficer.dvrequests.withid', $dvRequest->id);
}

   public function show($id)
{
    $dvRequest = DvRequest::with(['respocenter','focalPerson','orsRequest','logs'])
                    ->findOrFail($id);
    $orsRequest = $dvRequest->orsRequest ?? null;

    if (in_array($dvRequest->status, ['Incoming-TUPAD_verified', 'Incoming-GIP_verified'])) {

        if ($orsRequest) {
            $orsRequest->update([
                'status'      => 'ORS_received',
                'received_by' => auth()->id(),
                'received_at' => now(),
            ]);
        }

        $dvRequest->update([
            'status' => 'ORS_received',
        ]);

        $focalId = $dvRequest->focalperson_id;
        $status = 'Received Obligation Request and Status Received';
        $action = 'Received'; // or something meaningful for your workflow
        $remarks = $request->remarks ?? "Received ORS For Request Processing.";

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
            'Budget Officer',
            'ORS_received',
            'ORS request received by Budget Officer for processing'
        );
    }

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
// In ObligatedDVORSController.php
    public function fetchRecent()
    {
        $recentDvRequest = DvRequest::with('focalPerson') 
            ->whereIn('status', ['Incoming-TUPAD_verified', 'Incoming-GIP_verified', 'ORS_received'])
            ->whereNotNull('focalperson_id')
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

    $dvRequest->update(['status' => 'ORS_obligated']);
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
        'Budget Officer',
        'ORS_obligated',
        'ORS Number ' . $orsNumber . ' assigned.'
    );
    return redirect()
        ->back()
        ->with('success', "ORS Test Request Created with ORS Number: $orsNumber");
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
        'Budget Officer',
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