<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Notifications\DvRequestUpdated;
use App\Models\DvRequestLog;
use App\Models\Program;
use App\Services\PusherService;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DvRequestController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * Display a listing of the DV Requests for the authenticated Focal Person.
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

    // Pass the full $dv object
    $this->logAction($dv, 'SUBMITTED', 'submitted', 'Request submitted by Focal Person');

    return redirect()->route('FocalPerson.dvrequest.index')
        ->with('success', 'DV Request submitted successfully!');
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

                // Now delete the DV request
                $dvRequest->delete();

                return redirect()->route('FocalPerson.dvrequest.index')
                                ->with('success', 'DV Request deleted successfully.');
            }
        
            public function logs(DvRequest $dvRequest)
    {
        if (Auth::guard('focal')->check()) {
            if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
                abort(403, 'Unauthorized access to this request.');
            }
        }

        if (Auth::guard('web')->check()) {
          
        }

        
        $logs = $dvRequest->logs()
            ->with(['focalPerson', 'accountingUnit'])
            ->latest()
            ->get();

      
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
   protected function logAction(DvRequest $dvRequest, string $status, string $action, string $remarks): void
{
    $focalId = Auth::guard('focal')->id();
    $log = DvRequestLog::create([
        'dv_request_id' => $dvRequest->id,
        'focalperson_id' => $focalId ?? null,
        'accountingunit_id' => Auth::guard('web')->id() ?? null,
        'status' => $status,
        'action' => $action,
        'remarks' => $remarks,
    ]);

    $notification = Notification::create([
        'focalperson_id' => $focalId,
        'dv_request_id' => $dvRequest->id,
        'status' => $status,
        'action' => $action,
        'remarks' => $remarks,
    ]);

    $this->pusher->trigger('dv-requests'. $focalId,'status-updated', [
        'id' => $notification->id,
        'dv_request_id' => $dvRequest->id,
        'status'        => $status,
        'action'        => $action,
        'remarks'       => $remarks,
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);
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