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
        $dvRequests = DvRequest::whereHas('program', fn($q) => 
                                $q->where('prcode', 'GIP')) // ✅ Only GIP
                               ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_in_process'])
                               ->orderBy('created_at', 'desc')
                               ->get();

        return view('Incoming.GIP.index', compact('dvRequests'));
    }
    
    public function show($id)
    {
        $dvRequest = DvRequest::with(['accountingUnit','program'])
                            ->whereHas('program', fn($q) => 
                                $q->where('prcode', 'GIP')) // ✅ Only GIP
                            ->findOrFail($id);

        if ($dvRequest->status == 'IMSD_verified') {
            $dvRequest->status = 'Incoming-GIP_in_process';
            $dvRequest->accountingunit_id = auth()->id(); // assign current user as processor
            $dvRequest->received_by = auth()->id(); // ✅ mark who received
            $dvRequest->received_at = now();        // ✅ mark timestamp
            $dvRequest->save();

            $this->logAction(
                $dvRequest->id, 
                'Incoming-GIP_in_process', 
                'Request received by Incoming for GIP processing'
            );
        }

        $recentDvRequests = DvRequest::whereHas('program', fn($q) => 
                                        $q->where('prcode', 'GIP')) // ✅ Only GIP
                                    ->orderBy('created_at', 'desc')
                                    ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_in_process'])
                                    ->whereNotNull('focalperson_id')
                                    ->take(5)
                                    ->get();

        return view('Incoming.GIP.verifygipdv', compact('dvRequest', 'recentDvRequests'));
    }


    public function fetchAll() {
        $dvRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'GIP')) // ✅ Only GIP
            ->whereIn('status', ['IMSD_verified','Incoming-GIP_in_process'])
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

    public function fetch($id)
    {
        $dvRequest = DvRequest::with(['focalPerson', 'program','respocenter','accountingUnit'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'GIP')) // ✅ Only GIP
            ->findOrFail($id);
        
        return response()->json([
            'id'           => $dvRequest->id,
            'dv_number'    => $dvRequest->dv_number ?? '—',
            'title'        => $dvRequest->particulars ?? 'No Title',
            'status'       => $dvRequest->status,
            'amount'       => number_format($dvRequest->amount, 2),
            'submitted_by' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
            'program'      => $dvRequest->program->prcode ?? 'N/A',
            'accountingunit' => trim(($dvRequest->accountingUnit->fname ?? '') . ' ' . ($dvRequest->accountingUnit->lname ?? '')),  
            'respocenter'  => $dvRequest->respocenter->rccode ?? 'N/A',
            'date'         => $dvRequest->created_at->format('M d, Y h:i A'),
        ]);
    }

    public function verify(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP')) // ✅ Only GIP
                              ->findOrFail($id);

        $dvRequest->status = 'Incoming-GIP_verified';
        $dvRequest->save();

        $this->logAction($dvRequest->id, 'Incoming-GIP_verified', $request->remarks ?? 'Request verified by Incoming-GIP');

        return redirect()->route('Incoming.GIP.dvrequests.withid', $dvRequest->id)
                         ->with('success', 'DV Request has been verified successfully.');
    }
    
    public function redirectToFirstPending()
    {
        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP')) // ✅ Only GIP
                              ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_in_process'])
                              ->orderBy('created_at', 'asc')
                              ->first();

        if (!$dvRequest) {
            return redirect()->route('Incoming.GIP.index')
                             ->with('info', 'No GIP DV requests pending verification.');
        }

        return redirect()->route('Incoming.GIP.dvrequests.withid', $dvRequest->id);
    }
public function history()
{
    $dvRequests = DvRequest::with(['program', 'focalPerson', 'respocenter', 'accountingUnit'])
        ->whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
        ->where('status', 'Incoming-GIP_verified') // ✅ only processed/verified
        ->orderBy('updated_at', 'desc')
        ->get();

    return view('Incoming.GIP.loghistory', compact('dvRequests'));
}
    protected function logAction($dvRequestId, $action, $remarks = null)
    {
        DvRequestLog::create([
            'dv_request_id' => $dvRequestId,
            'action'        => $action,
            'remarks'       => $remarks,
            'user_id'       => auth()->id(),
        ]);
    }
}
