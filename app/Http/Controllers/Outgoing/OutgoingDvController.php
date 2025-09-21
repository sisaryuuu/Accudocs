<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrsRequest;
use App\Models\DvRequest;
use App\Services\PusherService;
use App\Models\Notification;
use App\Models\DvRequestLog;

class OutgoingDvController extends Controller
{
     protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    public function index()
    {

        return view('Outgoing.index');
    }

     public function fetchDvRequests()
    {
        $dvRequests = DvRequest::with(['program', 'focalPerson', 'respocenter', 'orsRequest'])
            ->whereIn('status', ['ORS_obligated','Outgoing_received','Outgoing_finalized']) 
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
                'dv_number'    => $req->dv_number ?? null,
            ];
        }));
    }
    public function fetchDvRecent()
    {
        $recentDvRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
            ->whereIn('status', ['ORS_obligated','Outgoing_received'])
            ->whereNotNull('focalperson_id')
            ->latest()
            ->take(5)
            ->get();

         return response()->json($recentDvRequests);
        
    }
    
        public function show($id)
    {
        $dvRequest = DvRequest::with(['respocenter','focalPerson','orsRequest','logs'])
            ->findOrFail($id);

        $orsRequest = $dvRequest->orsRequest ?? null;

        // If the DV just came from Budget Officer
        if ($dvRequest->status === 'ORS_obligated') {
            $dvRequest->update([
                'status' => 'Outgoing_received',
            ]);

        $focalId = $dvRequest->focalperson_id;
        $status = 'Outgoing_received';
        $action = 'Received'; // or something meaningful for your workflow
        $remarks = $request->remarks ?? "DV request received by Outgoing for final verification";

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
                'Outgoing',
                'Outgoing_received',
                'DV request received by Outgoing for final verification'
            );
        }

        $lastLog = $dvRequest->logs()->latest()->first();
        $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

      return view('Outgoing.finalizedv' ,compact('dvRequest', 'lastStep'));
    }
public function finalize(Request $request, $id)
{
    $request->validate([
        'remarks' => 'nullable|string|max:1000',
    ]);

    $dvRequest = DvRequest::findOrFail($id);

    if ($dvRequest->status !== 'Outgoing_received') {
        return redirect()->back()->with('error', 'This DV request cannot be finalized yet.');
    }

    // Generate DV Number (YYYY-MM-XXXX)
    $year  = date('Y');
    $month = date('m');

    $latestDv = DvRequest::whereYear('created_at', $year)
        ->whereMonth('created_at', $month)
        ->whereNotNull('dv_number')
        ->orderBy('dv_number', 'desc')
        ->first();

    $series = $dvRequest->id;
    $seriesFormatted = str_pad($series, 4, '0', STR_PAD_LEFT);
    $dvNumber = "{$year}-{$month}-{$seriesFormatted}";

    // Update DV Request
    $dvRequest->update([
        'dv_number' => $dvNumber,
        'status'    => 'Outgoing_finalized',
    ]);

     if ($dvRequest->focalPerson) {
        $dvRequest->focalPerson->update([
            'dv_number' => $dvNumber
        ]);
    }
        $focalId = $dvRequest->focalperson_id;
        $status = 'Outgoing_finalized';
        $action = 'Finalized'; // or something meaningful for your workflow
        $remarks = ($request->remarks ?? 'DV finalized by Outgoing') . " | DV Number: {$dvNumber}";

        $notification = Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => $status,
            'action'         => $action,
            'remarks'        => $remarks,
        ]);
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
        'dv_request_id' => $dvRequest->id,
        'status'        => $dvRequest->status,
        'dv_number'     => $dvRequest->dv_number,
        'remarks'       => $remarks,
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);
        
    $this->logAction(
        $dvRequest->id,
        'Outgoing',
        'Finalized',
        ($request->remarks ?? 'DV finalized by Outgoing') . " | DV Number: {$dvNumber}"
    );

    return redirect()->route('Outgoing.dvrequests.show', $dvRequest->id)
                     ->with('success', "DV Request finalized with DV Number: {$dvNumber}");
}
public function history()
    {
        $dvRequests = DvRequest::with([
                'program',
                'focalPerson',
                'respocenter',
                'accountingUnit',
                'orsRequest',
                'logs'
            ])
            ->where('status', 'Outgoing_finalized')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('Outgoing.dvloghistory', compact('dvRequests'));
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
