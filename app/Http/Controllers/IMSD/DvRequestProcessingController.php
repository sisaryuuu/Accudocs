<?php

namespace App\Http\Controllers\IMSD;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Services\PusherService;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DvRequestProcessingController extends Controller

{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

            // Index method: list all DV requests pending IMSD processing
        public function index(Request $request)
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $query = DvRequest::query()
            ->whereIn('status', ['submitted', 'IMSD_in_process'])
            ->where(function ($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by') // not locked
                ->orWhere('locked_by', $currentUserId) // locked by me
                ->orWhere('locked_at', '<=', $lockDuration); // lock expired
            });

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
        $sidebarCounts = $this->getSidebarCounts();
        
        return view('RegionalOffices.IMSD.index', compact('dvRequests','sidebarCounts'));
    }


    // For JSON polling with search
    public function fetchAll(Request $request)
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $query = DvRequest::with(['focalPerson', 'program', 'respocenter', 'accountingUnit'])
            ->whereIn('status', ['submitted', 'IMSD_in_process','IMSD_received'])
            ->where(function ($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by')
                ->orWhere('locked_by', $currentUserId)
                ->orWhere('locked_at', '<=', $lockDuration);
            });

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

        $lockDuration   = now()->subMinutes(10);
        $currentUserId  = auth()->id();

        // Check lock
        if ($dvRequest->locked_by && $dvRequest->locked_by !== $currentUserId && $dvRequest->locked_at > $lockDuration) {
            return redirect()->route('RegionalOffices.IMSD.index')
                ->with('error', 'This DV Request is currently being processed by another user.');
        }

        // Lock if free or expired
        if (!$dvRequest->locked_by || $dvRequest->locked_at <= $lockDuration) {
            $dvRequest->update([
                'locked_by' => $currentUserId,
                'locked_at' => now(),
            ]);
        }

        // Ensure checklist items exist
        $checklistItems = [
            'EUP Payroll', 'Payroll', 'Pacsval', 'DTR with Accomplishment Report',
            'DTR Log', 'Profile', 'Brgy. Certification', 'TOR/My Grade', 'Insurance',
            'Birth Certificate', 'Internship Agreement', 'Evaluation Form-GIP',
        ];

        foreach ($checklistItems as $item) {
            $dvRequest->gipAttachments()->firstOrCreate(['name' => $item], ['is_present' => false]);
        }

        // Stepper
        $lastLog = $dvRequest->logs()->latest()->first();
        $lastStep = $lastLog ? $lastLog->action : 'Submitted';

        return view('RegionalOffices.IMSD.verifydv', compact(
            'dvRequest', 'checklistItems', 'lastStep'
        ));
    }
   public function receive(Request $request, $id)
{
    try {
        $dvRequest = DvRequest::findOrFail($id);
        
        if ($dvRequest->status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been received.'
            ], 422);
        }
        
        $receivedAt = now();
        
        $dvRequest->update([
            'status'           => 'IMSD_received',
            'accountingunit_id'=> auth()->id(),
            'received_by'      => auth()->id(),
            'received_at'      => $receivedAt,
            'locked_by'        => auth()->id(),
            'locked_at'        => $receivedAt,
        ]);
        
        // Log
        $this->logAction(
            $dvRequest->id,
            'IMSD',
            'IMSD_received',
            'Request officially received by IMSD'
        );
        
        // Notify focal person
        $focalId = $dvRequest->focalperson_id;
        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => 'IMSD_received',
            'action'         => 'Received',
            'remarks'        => 'Request officially received by IMSD',
        ]);
        
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'IMSD_received',
            'remarks'       => 'Request officially received by IMSD',
            'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
        
        // Check if request expects JSON (AJAX)
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document has been received by IMSD.',
                'data' => [
                    'id' => $dvRequest->id,
                    'status' => 'IMSD_received',
                    'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                ]
            ]);
        }
        
        // Fallback for non-AJAX requests (if needed)
        return redirect()->route('RegionalOffices.IMSD.index')
            ->with('success', 'Document has been received by IMSD.');
            
    } catch (\Exception $e) {
        // Log the error
        \Log::error('Error receiving DV request: ' . $e->getMessage());
        
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the request.'
            ], 500);
        }
        
        return redirect()->back()->with('error', 'An error occurred while processing the request.');
    }
}
   public function startProcessing(Request $request, $id)
{
    try {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->status !== 'IMSD_received') {
            return response()->json([
                'success' => false,
                'message' => 'Request must be received first before processing.'
            ], 422);
        }

        $startedAt = now();

        $dvRequest->update([
            'status'    => 'IMSD_in_process',
            'locked_by' => auth()->id(),
            'locked_at' => $startedAt,
        ]);

        // Log
        $this->logAction(
            $dvRequest->id,
            'IMSD',
            'IMSD_in_process',
            'Processing started by IMSD'
        );

        // Notify focal person
        $focalId = $dvRequest->focalperson_id;
        $status  = 'IMSD_in_process';
        $action  = 'Processing Started';
        $remarks = 'Request is now being processed by IMSD';

        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => $status,
            'action'         => $action,
            'remarks'        => $remarks,
        ]);

        // Trigger real-time event
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $status,
            'remarks'       => $remarks,
            'timestamp'     => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        // JSON response for AJAX
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Processing started.',
                'data' => [
                    'id'         => $dvRequest->id,
                    'status'     => $status,
                    'started_at' => $startedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                ]
            ]);
        }

        // Fallback redirect
        return redirect()->route('RegionalOffices.IMSD.index')
            ->with('success', 'Processing started.');

    } catch (\Exception $e) {
        \Log::error('Error starting processing: ' . $e->getMessage());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while starting processing.'
            ], 500);
        }

        return redirect()->back()->with('error', 'An error occurred while starting processing.');
    }
}


    public function fetchimsdRecent()
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $recentDvRequests = DvRequest::with('focalPerson')
            ->whereIn('status', ['submitted', 'IMSD_in_process','IMSD_received'])
            ->whereNotNull('focalperson_id')
            ->where(function ($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by') // not locked
                ->orWhere('locked_by', $currentUserId) // locked by me
                ->orWhere('locked_at', '<=', $lockDuration); // lock expired
            })
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

       // Run only if program is GIP
        if ($dvRequest->program->prcode === 'GIP') {
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
        }
            $dvRequest->update([
            'status' => 'IMSD_verified',
            'locked_by' => null,
            'locked_at' => null,
        ]);
        
        $focalId = $dvRequest->focalperson_id;
        $status = 'IMSD_verified';
        $action = 'Approved'; // or something meaningful for your workflow
        $remarks = $request->remarks ?? 'Request verified by IMSD';

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
    $currentUserId = auth()->id();
    $lockDuration = now()->subMinutes(10);

    // Get all pending GIP requests
    $pendingRequests = DvRequest::whereHas('program', fn($q) => 
                                $q->whereIn('prcode', ['GIP','TUPAD'])) 
                            ->whereIn('status', ['submitted'])
                            ->orderBy('created_at', 'asc')
                            ->get();

    if ($pendingRequests->isEmpty()) {
        return redirect()->route('RegionalOffices.IMSD.index')
                         ->with('info', 'No IMSD DV requests pending verification.');
    }

    // Check for one you can work on (unlocked or locked by you, or expired lock)
    $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
        return !$req->locked_by 
            || $req->locked_by == $currentUserId 
            || $req->locked_at <= $lockDuration;
    });

    if ($available) {
        return redirect()->route('RegionalOffices.IMSD.verifydv', $available->id);
    }

    // If all are locked by others
    return redirect()->route('RegionalOffices.IMSD.index')
                     ->with('info', 'All pending GIP DV requests are currently locked by other users.');
}

      public function releaseLock($id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->locked_by === auth()->id()) {
            $dvRequest->update([
                'locked_by' => null,
                'locked_at' => null,
                'status'    => 'submitted',
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function refreshLock(Request $request, $id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->locked_by !== auth()->id()) {
            return response()->json(['error' => 'Not authorized to refresh lock'], 403);
        }

        $dvRequest->update([
            'locked_at' => now(), // refresh timestamp
        ]);

        return response()->json(['success' => true, 'locked_at' => $dvRequest->locked_at]);
    }


    public function history()
    {
        $dvRequests = DvRequest::with(['program', 'focalPerson', 'respocenter', 'accountingUnit', 'logs'])
            ->whereHas('program', fn($q) => $q->whereIn('prcode', ['GIP', 'TUPAD']))
            ->where('status', 'IMSD_verified')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('RegionalOffices.IMSD.loghistory', compact('dvRequests'));
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
