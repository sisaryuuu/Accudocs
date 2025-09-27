<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrsRequest;
use App\Models\DvRequest;
use App\Services\PusherService;
use App\Models\Notification;
use App\Models\DvRequestLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OutgoingDvController extends Controller
{
     protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    public function index()
    {
         $sidebarCounts = $this->getSidebarCounts();
        return view('Outgoing.index',compact('sidebarCounts'));
    }

     public function fetchDvRequests()
    {
         $currentUserId = auth()->id();
         $lockDuration  = now()->subMinutes(10);
        $dvRequests = DvRequest::with(['program', 'focalPerson', 'respocenter', 'orsRequest'])
            ->whereIn('status', ['ORS_obligated','Outgoing_received','Outgoing_finalized'])
            ->where(function ($q) use ($currentUserId, $lockDuration) {
            $q->whereNull('locked_by')              
              ->orWhere('locked_by', $currentUserId)  
              ->orWhere('locked_at', '<=', $lockDuration); 
        })
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
         $currentUserId = auth()->id();
        $lockDuration  = now()->subMinutes(10);

        $recentDvRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
            ->whereIn('status', ['ORS_obligated','Outgoing_received'])
            ->whereNotNull('focalperson_id')
             ->where(function ($q) use ($currentUserId, $lockDuration) {
            $q->whereNull('locked_by')                // not locked
              ->orWhere('locked_by', $currentUserId)  // locked by me
              ->orWhere('locked_at', '<=', $lockDuration); // lock expired
        })
            ->latest()
            ->take(5)
            ->get();

         return response()->json($recentDvRequests);
        
    }
    
       public function show($id)
{
    $dvRequest = DvRequest::with(['respocenter','focalPerson','orsRequest','logs'])
        ->findOrFail($id);

    $currentUserId = auth()->id();
    $lockDuration  = now()->subMinutes(10);

    // Prevent access if locked by another active user
    if ($dvRequest->locked_by 
        && $dvRequest->locked_by !== $currentUserId 
        && $dvRequest->locked_at >= $lockDuration) {
        return redirect()->route('Outgoing.dvrequests.index')
            ->with('error', 'This DV Request is currently being processed by another user.');
    }

    // Acquire lock if expired or owned
    if (!$dvRequest->locked_by || $dvRequest->locked_at < $lockDuration || $dvRequest->locked_by === $currentUserId) {
        $dvRequest->update([
            'locked_by' => $currentUserId,
            'locked_at' => now(),
        ]);
    }

    $lastLog  = $dvRequest->logs()->latest()->first();
    $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

    return view('Outgoing.finalizedv', compact('dvRequest', 'lastStep'));
}

public function receive(Request $request, $id)
{
    try {
        $dvRequest = DvRequest::findOrFail($id);

        // Only allow if status is already obligated
        if ($dvRequest->status !== 'ORS_obligated') {
            return response()->json([
                'success' => false,
                'message' => 'This request is not yet ready for Outgoing to receive.'
            ], 422);
        }

        $receivedAt = now();

        // Update DV request
        $dvRequest->update([
            'status'      => 'Outgoing_received',
            'received_by' => auth()->id(),
            'received_at' => $receivedAt,
            'locked_by'   => auth()->id(),
            'locked_at'   => $receivedAt,
        ]);

        // Log action
        $this->logAction(
            $dvRequest->id,
            'Outgoing',
            'Outgoing_received',
            'DV request received by Outgoing for final verification'
        );

        // Notify focal person
        $focalId = $dvRequest->focalperson_id;
        $remarks = $request->remarks ?? "DV request received by Outgoing for final verification";

        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => 'Outgoing_received', 
            'action'         => 'Received',
            'remarks'        => $remarks,
        ]);

        // Real-time push
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'Outgoing_received',
            'remarks'       => $remarks,
            'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        // Response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document has been received by Outgoing.',
                'data' => [
                    'id'          => $dvRequest->id,
                    'status'      => 'Outgoing_received',
                    'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                ]
            ]);
        }

        return redirect()->route('Outgoing.dvrequests.index')
            ->with('success', 'Document has been received by Outgoing.');

    } catch (\Exception $e) {
        \Log::error('Error receiving DV request by Outgoing: ' . $e->getMessage());

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

        if ($dvRequest->status !== 'Outgoing_received') {
            return response()->json([
                'success' => false,
                'message' => 'Request must be received first before processing.'
            ], 422);
        }

        $startedAt = now();

        $dvRequest->update([
            'status'    => 'Outgoing_in_process',
            'locked_by' => auth()->id(),
            'locked_at' => $startedAt,
        ]);

        // Log
        $this->logAction(
            $dvRequest->id,
            'Outgoing',
            'Outgoing_in_process',
            'Processing started by Outgoing',
        );

        // Notify focal person
        $focalId = $dvRequest->focalperson_id;
        $status  = 'Outgoing_in_process';
        $action  = 'Processing Started';
        $remarks = 'Request is now being processed by Outgoing';

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
        return redirect()->route('Incoming.GIP.index')
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


   public function redirectToFirstPending()
{
    $currentUserId = auth()->id();
    $lockDuration = now()->subMinutes(10);

    // Get all pending GIP requests
    $pendingRequests = DvRequest::whereIn('status', ['ORS_Obligated', 'Outgoing_received'])
                            ->orderBy('created_at', 'asc')
                            ->get();

    if ($pendingRequests->isEmpty()) {
        return redirect()->route('Outgoing.dvrequests.index')
                         ->with('info', 'No DV requests pending verification.');
    }

    // Check for one you can work on (unlocked or locked by you, or expired lock)
    $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
        return !$req->locked_by 
            || $req->locked_by == $currentUserId 
            || $req->locked_at <= $lockDuration;
    });

    if ($available) {
        return redirect()->route('Outgoing.dvrequests.withid', $available->id);
    }

    // If all are locked by others
    return redirect()->route('Outgoing.dvrequests.index')
                     ->with('info', 'All pending GIP DV requests are currently locked by other users.');
}


    public function finalize(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->status !== 'Outgoing_in_process') {
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


      DvRequest::where('id', $dvRequest->id)
        ->update([
        'status'     => 'Outgoing_finalized',
        'dv_number'  => $dvNumber,
        'locked_by'  => null,
        'locked_at'  => null,
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

        return redirect()->route('Outgoing.dvrequests.index')
                        ->with('success', "DV Request finalized with DV Number: {$dvNumber}");
    }

    public function releaseLock($id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->locked_by === auth()->id()) {
            $dvRequest->update([
                'locked_by' => null,
                'locked_at' => null,
                'status' =>'ORS_obligated',
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
