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
        
        $dv = DvRequest::create([
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
        $this->logAction($dv->id  , 'submitted', 'Request submitted by Focal Person');

        return redirect()->route('FocalPerson.dvrequest.index')
            ->with('success', 'DV Request submitted successfully!');
    }

    /**
     * Display a single DV Request.
     */
   public function show(DvRequest $dvRequest)
{
    if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
        abort(403, 'Unauthorized access.');
    }

    // Eager load related program and logs (with focalPerson)
    $dvRequest->load(['program', 'logs.focalPerson']);

    $steps = [
        'Regional_Office',
        'Incoming',
        'Budget_Officer',
        'Outgoing',
        'Completed',
    ];

    // Determine current step from last log
    $lastLog = $dvRequest->logs->last();
    $currentStep = $lastLog 
        ? array_search($lastLog->role, $steps)
        : 0;

    // Pass $logs explicitly to the view
    $logs = $dvRequest->logs;

    return view('FocalPerson.dvrequest.show', compact('dvRequest', 'steps', 'currentStep', 'logs'));
}


public function updateStatus(Request $request, DvRequest $dvRequest)
{
    $steps = [
        'Regional_Office',
        'Incoming',
        'Budget_Officer',
        'Outgoing',
        'Completed',
    ];

    $lastLog = $dvRequest->logs()->latest()->first();
    $currentStepIndex = $lastLog
        ? array_search($lastLog->role, $steps)
        : 0;

    $allowedRole = $steps[$currentStepIndex];

    if (!auth()->user()->hasRole($allowedRole)) {
        abort(403, 'You cannot process this step.');
    }

    $nextStepIndex = $currentStepIndex + 1;
    $nextRole = $steps[$nextStepIndex] ?? 'Completed';

    $dvRequest->logs()->create([
        'focalperson_id' => auth()->id(),
        'role' => $nextRole,
        'status' => $nextRole === 'Completed' ? 'completed' : 'in-progress',
        'remarks' => $request->remarks,
    ]);

    return redirect()->route('FocalPerson.dvrequest.show', $dvRequest)
                     ->with('success', 'Moved to next step.');
}
    
    public function destroy(DvRequest $dvRequest)
{
    if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
        abort(403, 'Unauthorized action.');
            }

            // Log the deletion BEFORE deleting
            $this->logAction($dvRequest->id, 'deleted', 'DV Request deleted by Focal Person');

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
        // Ensure the focal person can only view logs of their own requests
        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized access to this request.');
        }

        $logs = DvRequestLog::where('dv_request_id', $dvRequest->id)
            ->with('dvRequest','accountingUnit')
            ->latest()
            ->get();

        return view('FocalPerson.dvrequest.logs', compact('dvRequest', 'logs'));
    }

    /**
     * Incoming Officer marks request as received.
     */
    public function receive(DvRequest $dvRequest)
    {
        $dvRequest->update([
            'status'      => 'received',
            'received_by' => Auth::guard('focal')->id(),
            'received_at' => now(),
        ]);

        $this->logAction($dvRequest->id, 'received', 'Document received by Incoming Officer');

        return back()->with('success', 'Request marked as received.');
    }

    /**
     * Budget Officer verifies request.
     */
    public function verify(DvRequest $dvRequest)
    {
        $dvRequest->update([
            'status'      => 'verified',
            'verified_by' => Auth::guard('focal')->id(),
            'verified_at' => now(),
        ]);

        $this->logAction($dvRequest->id, 'verified', 'Document verified by Budget Officer');

        return back()->with('success', 'Request verified successfully.');
    }

    /**
     * Approve request (e.g. Outgoing/Regional Director).
     */
    public function approve(DvRequest $dvRequest)
    {
        $dvRequest->update([
            'status'      => 'approved',
            'approved_by' => Auth::guard('focal')->id(),
            'approved_at' => now(),
        ]);

        $this->logAction($dvRequest->id, 'approved', 'Document approved');

        return back()->with('success', 'Request approved successfully.');
    }

    public function finalize(DvRequest $dvRequest, Request $request)
    {
        $request->validate([
            'dv_number' => 'required|string|unique:dv_requests,dv_number',
        ]);

        $dvRequest->update([
            'status'        => 'finalized',
            'finalized_by'  => Auth::guard('focal')->id(),
            'finalized_at'  => now(),
            'dv_number'     => $request->dv_number,
        ]);

        $this->logAction($dvRequest->id, 'finalized', 'DV number assigned');

        return back()->with('success', 'Request finalized successfully.');
    }

    /**
     * Log DV actions to the database.
     */
    protected function logAction(int $dvRequestId, string $action, string $remarks): void
    {
        DvRequestLog::create([
            'dv_request_id' => $dvRequestId,
            'user_id'       => Auth::guard('focal')->id(),
            'action'        => $action,
            'remarks'       => $remarks,
        ]);
    }
}