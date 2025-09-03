<?php

namespace App\Http\Controllers\IMSD;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use Illuminate\Http\Request;

class DvRequestProcessingController extends Controller
{
    // Index method
    public function index()
    {
        $dvRequests = DvRequest::whereIn('status', ['submitted', 'IMSD_in_process'])
                               ->orderBy('created_at', 'desc')
                               ->get();

        return view('RegionalOffices.IMSD.index', compact('dvRequests'));
    }
    // Return JSON for polling
    public function fetchAll()
    {
   $dvRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
            ->whereIn('status', ['submitted','IMSD_in_process'])
            ->latest()
            ->get();
            $result = $dvRequests->map(fn($request) => [
            'id'           => $request->id,
            'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
            'program'      => $request->program->prcode ?? 'N/A',
            'respocenter'  => $request->respocenter->rccode ?? 'N/A',
            'amount'       => number_format($request->amount, 2),
            'status'       => $request->status,
            'date'         => $request->created_at->format('M d, Y h:i A'),
        ]);

        return response()->json($result);
    }
    // Show / process method
    public function show($id)
    {
        $dvRequest = DvRequest::findOrFail($id);    

        if ($dvRequest->status == 'submitted') {
            $dvRequest->status = 'IMSD_in_process';
            $dvRequest->save();

            $this->logAction($dvRequest->id, 'IMSD_in_process', 'Request received by IMSD for verification');
        }
        $recentDvRequests = DvRequest::with('focalPerson')
                                ->whereIn('status', ['submitted', 'IMSD_in_process'])
                                ->whereNotNull('focalperson_id') 
                                 ->orderBy('created_at', 'desc')
                                 ->take(5)
                                 ->get();

        return view('RegionalOffices.IMSD.verifydv', compact('dvRequest','recentDvRequests'));
    }

    // Verify method
    public function verify(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dvRequest = DvRequest::findOrFail($id);
        $dvRequest->status = 'IMSD_verified';
        $dvRequest->verified_by = auth()->user()->id;
        $dvRequest->save();

        $this->logAction($dvRequest->id, 'IMSD_verified', $request->remarks ?? 'Request verified by IMSD');

        return redirect()->route('RegionalOffices.IMSD.index')
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
