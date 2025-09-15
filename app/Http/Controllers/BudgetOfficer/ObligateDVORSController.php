<?php

namespace App\Http\Controllers\BudgetOfficer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrsRequest;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\UacsCode;
use App\Models\AdlCode;
use App\Models\AllotmentClass;
use App\Models\Mfopap;
use App\Models\ContinuingOrCurrent;

class ObligateDVORSController extends Controller
{
    public function index()
    {
        
        return view('BudgetOfficer.index');
    }

 public function fetchDvRequests()
{
    $dvRequests = DvRequest::with(['program', 'focalPerson', 'respocenter','orsRequest'])
        ->whereIn('status', ['Incoming-TUPAD_verified', 'Incoming-GIP_verified','ORS_received'])
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
            'orsRequest'   => $req->orsRequest->ors_number ?? null,
          
        ];
    }));
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
        $recentDvRequest = DvRequest::with('focalPerson') // 👈 load related user
            ->whereIn('status', ['Incoming-TUPAD_verified', 'Incoming-GIP_verified', 'ORS_received'])
            ->whereNotNull('focalperson_id')
            ->latest()
            ->take(5)
            ->get();

        return response()->json($recentDvRequest);
    }


    public function store(Request $request, $dvRequestId)
{
    $request->validate([
        'uacscode_id'       => 'required|exists:uacscodes,id',
        'adlcode_id'        => 'required|exists:adlcodes,id',
        'allotmentclass_id' => 'required|exists:allotment_classes,id',
        'mfopap_id'         => 'required|exists:mfopaps,id',
        'continuingorcurrent_id' => 'required|exists:contcurrents,id',
    ]);

    $dvRequest = DvRequest::findOrFail($dvRequestId);

    // ✅ Generate ORS number right away
    $orsNumber = $this->generateOrsNumber(
        $request->allotmentclass_id,
        $dvRequest->respocenter->ccode
    );

    // ✅ Create ORS record with ORS number
    $ors = OrsRequest::create([
        'orsnumber'           => $orsNumber,
        'dv_request_id'       => $dvRequest->id,
        'accounting_id'       => $dvRequest->accounting_id,
        'focalperson_id'      => $dvRequest->focalperson_id,
        'uacscode_id'         => $request->uacscode_id,
        'adlcode_id'          => $request->adlcode_id,
        'allotmentclass_id'   => $request->allotmentclass_id,
        'mfopap_id'           => $request->mfopap_id,
        'continuingorcurrent_id' => $request->continuingorcurrent_id,
        'status'              => 'ORS_received',
        'obligated_by'        => auth()->id(),
        'obligated_at'        => now(),
    ]);

    // ✅ Update DV Request status
    $dvRequest->update([
        'status' => 'ORS_obligated'
    ]);

    // ✅ Log action
    $this->logAction(
        $dvRequest->id,
        'Budget Officer',
        'ORS Obligated ',
        'ORS Number ' . $orsNumber . ' assigned.'
    );

    return redirect()
        ->route('BudgetOfficer.obligatedvors.show', $dvRequest->id)
        ->with('success', 'ORS has been obligated and assigned ORS Number: ' . $orsNumber);
}
    public function history()
    {
        $orsRequests = OrsRequest::with(['dvRequest.program', 'dvRequest.focalPerson', 'dvRequest.respocenter'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('BudgetOfficer.ORS.history', compact('orsRequests'));
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

        private function logAction($dvRequestId, $action, $status, $remarks = null)
    {
        DvRequestLog::create([
            'dv_request_id' => $dvRequestId,
            'action'        => $action,
            'status'        => $status,
            'remarks'       => $remarks,
            'user_id'       => auth()->id(),
        ]);
    }
}