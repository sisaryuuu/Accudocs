<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DvRequestController extends Controller
{
    /**
     * Display all DV Requests for this focal person.
     */
  public function index()
{
    // Ensure focal person is authenticated
    if (!Auth::guard('focal')->check()) {
        return redirect()->route('login')->with('error', 'Please log in first.');
    }

    $focalId = Auth::guard('focal')->id();

    try {
        // Fetch DV requests with related Focal Person and Program
        $dvRequests = DvRequest::with(['focalperson', 'program','respocenter'])
                        ->where('focalperson_id', $focalId)
                        ->latest()
                        ->get();
    } catch (\Exception $e) {
        return back()->with('error', 'Database error: ' . $e->getMessage());
    }

    return view('FocalPerson.dvrequest.index', compact('dvRequests'));
}

    /**
     * Show the form for creating a new DV Request.
     */
    public function create()
    {
        $focalPerson = Auth::guard('focal')->user(); // authenticated focal person
        $programs = Program::all();
        return view('FocalPerson.dvrequest.submitdvors', compact('programs','focalPerson'));
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
        ]);
        
           $dvRequest = DvRequest::create([
        'focalperson_id' => Auth::guard('focal')->id(),
        'program_id'     => $request->program_id,
        'amount'         => $request->amount,
        'particulars'    => $request->particulars,
        'fname'          => $request->fname,
        'mi'             => $request->mi,
        'lname'          => $request->lname,
        'tin_number'     => $request->tin,
        'respocenter_id' => $request->respocenter_id,
        'address'        => $request->address,
        'status'         => 'submitted',
    ]);
        $this->logAction($dvRequest->id, 'submitted', 'submitted', 'Request submitted by Focal Person');;


    }

    /**
     * Display a single DV Request.
     */
    public function show(DvRequest $dvRequest)
    {
        // Security: ensure the DV belongs to this focal person
        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized access.');
        }

        // Eager load related data
        $dvRequest->load(['program', 'logs.focalPerson', 'logs.accountingUnit']);

        // Pass logs explicitly to the view
        $logs = $dvRequest->logs;

        // Get the latest step for the progress stepper
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

        public function destroy(DvRequest $dvRequest)
    {
        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized action.');
                }

                // Log the deletion BEFORE deleting
            $this->logAction($dvRequest->id, 'deleted', 'deleted', 'DV Request deleted by Focal Person');

                // Now delete the DV request
                $dvRequest->delete();

                return redirect()->route('FocalPerson.dvrequest.index')
                                ->with('success', 'DV Request deleted successfully.');
            }
        /**
         * View logs of a DV Request.
         */
            public function logs(DvRequest $dvRequest)
    {
        if (Auth::guard('focal')->check()) {
            if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
                abort(403, 'Unauthorized access to this request.');
            }
        }

        if (Auth::guard('web')->check()) {
            // Accounting unit → you can add more granular checks here
        }

        // Load logs with relationships and debug info
        $logs = $dvRequest->logs()
            ->with(['focalPerson', 'accountingUnit'])
            ->latest()
            ->get();

        // Debug: Check what's in the logs
        foreach ($logs as $log) {
            \Log::info('Log ID: ' . $log->id, [
                'focalperson_id' => $log->focalperson_id,
                'accountingunit_id' => $log->accountingunit_id,
                'role' => $log->role,
                'action' => $log->action,
                'has_focal_person' => $log->focalPerson ? 'YES' : 'NO',
                'has_accounting_unit' => $log->accountingUnit ? 'YES' : 'NO',
            ]);
        }

        return view('FocalPerson.dvrequest.logs', compact('dvRequest', 'logs'));
    }

protected function logAction(int $dvRequestId, string $status, string $action, string $remarks): void
{
   

    if (Auth::guard('focal')->check()) { // Focal Person
         DvRequestLog::create([
        'dv_request_id'    => $dvRequestId,
        'focalperson_id'   => Auth::guard('focal')->id(),
        'status'           => $status,
        'action'           => $action,
        'remarks'          => $remarks, 
    ]);
    } elseif (Auth::guard('web')->check()) { // Accounting Unit
        DvRequestLog::create([
            'dv_request_id'    => $dvRequestId,
            'accountingunit_id'=> Auth::guard('web')->id(),
            'status'           => $status,
            'action'           => $action,
            'remarks'          => $remarks,
        ]);
    }else {
        DvRequestLog::create([
            'dv_request_id' => $dvRequestId,
            'action' => $action, 
            'status' => $status,
            'remarks' => $remarks, 
        ]);
            
    }
}
    public function latestStep(DvRequest $dvRequest)
{
    $lastLog = $dvRequest->logs()->latest()->first();
    $action  = $lastLog?->action ?? 'Submitted';

    $normalized = match (true) {
        str_contains($action, 'Submitted')   => 'Submitted',
        str_contains($action, 'IMSD')        => 'IMSD',
        str_contains($action, 'Incoming')    => 'Incoming',
        str_contains($action, 'Budget')      => 'Budget Officer',
        str_contains($action, 'Outgoing')    => 'Outgoing',
        str_contains($action, 'Completed')   => 'Completed',
        default                              => 'Submitted',
    };

    return response()->json([
        'lastStep' => $normalized,
    ]);

}

}