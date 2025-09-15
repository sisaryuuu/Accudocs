<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;



class TupadDvRequestProcessingController extends Controller
{
 public function index(Request $request)
    {
        $query = DvRequest::whereHas('program', fn($q) =>
                        $q->where('prcode', 'TUPAD'))
                    ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_in_process']);


        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->whereHas('program', fn($p) => $p->where('prcode', 'like', "%{$search}%"))
                  ->orWhereHas('focalPerson', fn($fp) =>
                      $fp->where('fname', 'like', "%{$search}%")
                         ->orWhere('lname', 'like', "%{$search}%")
                  )
                  ->orWhereHas('respocenter', fn($r) => $r->where('rccode', 'like', "%{$search}%"))
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $dvRequests = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('Incoming.TUPAD.index', compact('dvRequests'));
    }
    
    public function show($id)
{
    $dvRequest = DvRequest::with(['accountingUnit','program','logs'])
                        ->whereHas('program', fn($q) => 
                            $q->where('prcode', 'TUPAD')) 
                        ->findOrFail($id);

    if ($dvRequest->status == 'IMSD_verified') {
        $dvRequest->update([
            'status'            => 'Incoming-TUPAD_in_process',
            'accountingunit_id' => auth()->id(),
            'received_by'       => auth()->id(),
            'received_at'       => now(),
        ]);

        $this->logAction(
            $dvRequest->id, 
            'Incoming',                    
            'Incoming-TUPAD_in_process',     
            'Request received by Incoming for TUPAD processing' 
        );
}
    $lastLog = $dvRequest->logs()->latest()->first();
    $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

    return view('Incoming.TUPAD.verifytupaddv', compact('dvRequest', 'lastStep'));
}
 public function fetchtupadRecent()
    {
        $recentDvRequests = DvRequest::with('focalPerson')
        ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
        ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_in_process'])
        ->whereNotNull('focalperson_id')
        ->latest()
        ->take(5)
        ->get();

        return response()->json($recentDvRequests);
    }

     public function fetchAll(Request $request)
    {
        $query = DvRequest::with(['focalPerson', 'program', 'respocenter'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
            ->whereIn('status', ['IMSD_verified','Incoming-TUPAD_in_process']);

        // 🔍 Apply search filter for AJAX
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->whereHas('program', fn($p) => $p->where('prcode', 'like', "%{$search}%"))
                  ->orWhereHas('focalPerson', fn($fp) =>
                      $fp->where('fname', 'like', "%{$search}%")
                         ->orWhere('lname', 'like', "%{$search}%")
                  )
                  ->orWhereHas('respocenter', fn($r) => $r->where('rccode', 'like', "%{$search}%"))
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $dvRequests = $query->latest()->get();

        $result = $dvRequests->map(fn($request) => [
            'id'           => $request->id,
            'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
            'program'      => $request->program->prcode ?? 'N/A',
            'respocenter'  => trim(($request->respocenter->rccode ?? '') . ' - ' . ($request->respocenter->description ?? '')) ?? 'N/A',
            'amount'       => number_format($request->amount, 2),
            'status'       => $request->status,
            'date'         => $request->created_at->format('M d, Y h:i A'),
        ]);

        return response()->json($result);
    }

    public function fetch($id)
    {
        $dvRequest = DvRequest::with(['focalPerson', 'program','respocenter','accountingUnit'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD')) 
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

        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TUPAD')) 
                              ->findOrFail($id);

        $dvRequest->status = 'Incoming-TUPAD_verified';
        $dvRequest->save();
          
     $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Approved',
            $request->remarks ?? 'DV Request approved forwarded to Budget Officer.'
        );

        return redirect()->route('Incoming.TUPAD.dvrequests.withid', $dvRequest->id)
                         ->with('success', 'DV Request Approved.');
    }
        public function reject(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
                            ->findOrFail($id);

        // Update status to rejected
        $dvRequest->status = 'Incoming-TUPAD_rejected';
        $dvRequest->save();

        // Log the rejection
        $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Rejected',
            $request->remarks ?? 'Request rejected by Incoming-TUPAD'
        );

        return redirect()->route('Incoming.TUPAD.index')
                        ->with('error', 'DV Request has been rejected.');
    }

    
    public function redirectToFirstPending()
    {
        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TUPAD')) 
                              ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_in_process'])
                              ->orderBy('created_at', 'asc')
                              ->first();

        if (!$dvRequest) {
            return redirect()->route('Incoming.TUPAD.index')
                             ->with('info', 'No TUPAD DV requests pending verification.');
        }

        return redirect()->route('Incoming.TUPAD.dvrequests.withid', $dvRequest->id);
    }
public function history()
{
    $dvRequests = DvRequest::with(['program', 'focalPerson', 'respocenter', 'accountingUnit'])
        ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
        ->where('status', 'Incoming-TUPAD_verified') 
        ->orderBy('updated_at', 'desc')
        ->get();

    return view('Incoming.TUPAD.loghistory', compact('dvRequests'));
}
  protected function logAction($dvRequestId, $action,$status, $remarks = null)
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
