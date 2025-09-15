<?php

namespace App\Http\Controllers\IMSD;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DvRequestProcessingController extends Controller
{
    // Define the workflow steps as a class property
    protected $workflowSteps = [
        'Submitted',
        'Regional_Office',
        'Incoming',
        'IMSD',             
        'Budget_Officer',
        'Outgoing',
        'Completed',
    ];

        // Index method: list all DV requests pending IMSD processing
        public function index(Request $request)
    {
        $query = DvRequest::query()
            ->whereIn('status', ['submitted', 'IMSD_in_process']);

        // Apply search filter
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

        return view('RegionalOffices.IMSD.index', compact('dvRequests'));
    }

    // For JSON polling with search
    public function fetchAll(Request $request)
    {
        $query = DvRequest::with(['focalPerson', 'program', 'respocenter', 'accountingUnit'])
            ->whereIn('status', ['submitted', 'IMSD_in_process']);

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
            'submitted_to' => auth()->id(),
            'program'      => $request->program->prcode ?? 'N/A',
            'respocenter'  => $request->respocenter->rccode ?? 'N/A',
            'amount'       => number_format($request->amount, 2),
            'status'       => $request->status,
            'date'         => $request->created_at->format('M d, Y h:i A'),
        ]);

        return response()->json($result);
    }

    public function show($id)
    {
        $dvRequest = DvRequest::with([
            'accountingUnit',
            'program',
            'focalPerson',
            'gipAttachments',
            'logs'
        ])
        ->whereHas('program', fn($q) => $q->whereIn('prcode', ['GIP', 'TUPAD']))
        ->findOrFail($id);

        // Automatically mark as IMSD_in_process if just submitted
            if (strtolower($dvRequest->status) === 'submitted') {
            $dvRequest->update([
                'status' => 'IMSD_in_process',
                'accountingunit_id' => auth()->id(),
                'received_by' => auth()->id(),
                'received_at' => now(),
            ]);

            $this->logAction(
                $dvRequest->id,
                'IMSD',               // role
                'IMSD_in_process',    // status
                'Request received by IMSD for verification' // remarks
            );
        }


        // Ensure all checklist items exist
        $checklistItems = [
            'EUP Payroll', 'Payroll', 'Pacsval', 'DTR with Accomplishment Report',
            'DTR Log', 'Profile', 'Brgy. Certification', 'TOR/My Grade', 'Insurance',
            'Birth Certificate', 'Internship Agreement', 'Evaluation Form-GIP',
        ];

        foreach ($checklistItems as $item) {
            $dvRequest->gipAttachments()->firstOrCreate(['name' => $item], ['is_present' => false]);
        }

        // Determine last step for progress stepper
        $lastLog = $dvRequest->logs()->latest()->first();
        $lastStep = $lastLog ? $lastLog->action : 'Submitted';  

        return view('RegionalOffices.IMSD.verifydv', compact(
            'dvRequest', 'checklistItems', 'lastStep'
        ));
    }
    public function fetchimsdRecent()
    {
        $recentDvRequests = DvRequest::with('focalPerson')
        ->whereIn('status', ['Submitted', 'IMSD_in_process'])
            ->whereNotNull('focalperson_id')
            ->latest()
            ->take(8)
            ->get();

        return response()->json($recentDvRequests);
    }


    public function verify(Request $request, $id)
    {
        $request->validate([
            'remarks'     => 'nullable|string|max:1000',
            'attachments' => 'array',
        ]);

        $dvRequest = DvRequest::with('gipAttachments')->findOrFail($id);

        // Update attachments
        $checklistItems = [
            'EUP Payroll', 'Payroll', 'Pacsval', 'DTR with Accomplishment Report',
            'DTR Log', 'Profile', 'Brgy. Certification', 'TOR/My Grade', 'Insurance',
            'Birth Certificate', 'Internship Agreement', 'Evaluation Form-GIP',
        ];

        foreach ($checklistItems as $item) {
            $isPresent = isset($request->attachments[$item]);
            $attachment = $dvRequest->gipAttachments()->firstOrCreate(['name' => $item]);
            $attachment->update(['is_present' => $isPresent]);
        }

        // Mark DV request as verified
        $dvRequest->update([
            'status' => 'IMSD_verified',
            'received_by' => auth()->id(),
        ]);

        // Log IMSD step for progress stepper
            $this->logAction(
                $dvRequest->id,
                'IMSD',
                'Approved',
                $request->remarks ?? 'Request verified by IMSD'
            );

        return redirect()->route('RegionalOffices.IMSD.index')
            ->with('success', 'DV Request has been verified successfully.');
    }

    // Redirect to first pending DV request
    public function redirectToFirstPending()
    {
        $dvRequest = DvRequest::whereHas('program', fn($q) => 
                            $q->whereIn('prcode', ['GIP', 'TUPAD']))
                        ->whereIn('status', ['submitted', 'IMSD_in_process'])
                        ->orderBy('created_at', 'asc')
                        ->first();

        if (!$dvRequest) {
            return redirect()->route('RegionalOffices.IMSD.index')
                            ->with('info', 'No DV requests pending IMSD verification.');
        }

        return redirect()->route('RegionalOffices.IMSD.verifydv', ['id' => $dvRequest->id]);
    }

    // History log view
    public function history()
    {
        $dvRequests = DvRequest::with(['program', 'focalPerson', 'respocenter', 'accountingUnit', 'logs'])
            ->whereHas('program', fn($q) => $q->whereIn('prcode', ['GIP', 'TUPAD']))
            ->where('status', 'IMSD_verified')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('RegionalOffices.IMSD.loghistory', compact('dvRequests'));
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
