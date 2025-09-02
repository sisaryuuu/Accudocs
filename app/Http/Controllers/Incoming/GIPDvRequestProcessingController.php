<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;

class GIPDvRequestProcessingController extends Controller
{
    public function index()
    {
        $dvRequests = DvRequest::whereIn('status', ['IMSD_verified', 'Incoming-GIP_in_process'])
                               ->orderBy('created_at', 'desc')
                               ->get();

        return view('Incoming.GIP.index', compact('dvRequests'));
    }
  public function show($id)
{
    $dvRequest = DvRequest::findOrFail($id);

    if ($dvRequest->status == 'IMSD_verified') {
        $dvRequest->status = 'Incoming-GIP_in_process';
        $dvRequest->save();

        $this->logAction($dvRequest->id, 'Incoming-GIP_in_process', 'Request received by Incoming for GIP processing');
    }

    // 👇 Fetch the 5 most recent DV Requests (adjust the where() if you only want GIP-related ones)
    $recentDvRequests = DvRequest::orderBy('created_at', 'desc')
                                ->take(5)
                                ->get();

    return view('Incoming.GIP.verifygipdv', compact('dvRequest', 'recentDvRequests'));
}

    public function fetch($id)
{
    $dvRequest = DvRequest::with(['focalPerson', 'program','responcenter'])
        ->findOrFail($id);

    return response()->json([
        'id'           => $dvRequest->id,
        'dv_number'    => $dvRequest->dv_number ?? '—',
        'title'        => $dvRequest->particulars ?? 'No Title',
        'status'       => $dvRequest->status,
        'amount'       => number_format($dvRequest->amount, 2),
        'submitted_by' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
        'program'      => $dvRequest->program->prcode ?? 'N/A',
        'responcenter' => $dvRequest->responcenter->rccode ?? 'N/A',
        'date'         => $dvRequest->created_at->format('M d, Y h:i A'),
    ]);
}
    public function verify(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dvRequest = DvRequest::findOrFail($id);
        $dvRequest->status = 'Incoming-GIP_verified';
        $dvRequest->verified_by = auth()->user()->id;
        $dvRequest->save();

        $this->logAction($dvRequest->id, 'Incoming-GIP_verified', $request->remarks ?? 'Request verified by Incoming-GIP');

        return redirect()->route('Incoming.GIP.index')
                         ->with('success', 'DV Request has been verified successfully.');
    }

    // ← Place logAction method here
    /**
     * Log an action for a DV Request
     */
   protected function logAction($dvRequestId, $action, $remarks = null)
        {
                DvRequestLog::create([
                'dv_request_id' => $dvRequestId,
                'action'        => $action, // must match your table column
                'remarks'       => $remarks,
                'user_id'       => auth()->id(),
            ]);
        }
}
