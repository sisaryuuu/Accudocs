<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Notification;
use App\Services\PusherService;
use App\Models\GIPAttachment;

class GIPDvRequestProcessingController extends Controller
{
     protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

      public function index(Request $request)
    {
        $query = DvRequest::whereHas('program', fn($q) =>
                        $q->where('prcode', 'GIP'))
                    ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_in_process']);


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

        return view('Incoming.GIP.index', compact('dvRequests'));
    }
    
    public function show($id)
{
    $dvRequest = DvRequest::with(['accountingUnit','program','gipAttachments','logs'])
                        ->whereHas('program', fn($q) => 
                            $q->where('prcode', 'GIP')) 
                        ->findOrFail($id);

    if ($dvRequest->status == 'IMSD_verified') {
        $dvRequest->update([
            'status'            => 'Incoming-GIP_in_process',
            'accountingunit_id' => auth()->id(),
            'received_by'       => auth()->id(),
            'received_at'       => now(),
        ]);
            $focalId = $dvRequest->focalperson_id;
                $status = 'Incoming-GIP_in_process';
                $action = 'Received';
                $remarks = $request->remarks ?? 'Request received by Incoming for GIP processing';

                $notification = Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $status,
                    'action'         => $action,
                    'remarks'        => $remarks,
                ]);

        if ($dvRequest->focalperson_id) {
            $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status'        => $status,
                'remarks'       =>  $remarks,
                'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);
        }
        $this->logAction(
            $dvRequest->id, 
            'Incoming',                    
            'Incoming-GIP_in_process',      
            'Request received by Incoming for GIP processing'
        );
}
    $lastLog = $dvRequest->logs()->latest()->first();
    $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

    $recentDvRequests = DvRequest::whereHas('program', fn($q) => 
                                    $q->where('prcode', 'GIP')) 
                                ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_in_process'])
                                ->whereNotNull('focalperson_id')
                                ->latest()
                                ->take(5)
                                ->get();

    return view('Incoming.GIP.verifygipdv', compact('dvRequest', 'recentDvRequests', 'lastStep'));
}
 public function fetchgipRecent()
    {
        $recentDvRequests = DvRequest::with('focalPerson')
                                ->whereHas('program', fn($q) => $q->where('prcode', 'GIP')) 
                                ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_in_process'])
                                ->whereNotNull('focalperson_id')
                                ->latest()
                                ->take(5)
                                ->get();


        return response()->json($recentDvRequests);
    }

     public function fetchAll(Request $request)
    {
        $query = DvRequest::with(['focalPerson', 'program', 'respocenter'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
            ->whereIn('status', ['IMSD_verified','Incoming-GIP_in_process']);

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
            ->whereHas('program', fn($q) => $q->where('prcode', 'GIP')) 
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

        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP')) 
                              ->findOrFail($id);

        $dvRequest->status = 'Incoming-GIP_verified';
        $dvRequest->save();
          
        $checkedAttachments = $request->input('attachments', []);
        foreach ($dvRequest->gipAttachments as $attachment) {
        $attachment->is_present = isset($checkedAttachments[$attachment->name]);
        $attachment->save();
    }
     $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Approved',
            $request->remarks ?? 'Request verified by Incoming-GIP'
        );
            $focalId = $dvRequest->focalperson_id;
            $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'IMSD_verified',
            'remarks'       => $request->remarks ?? 'Request verified by IMSD',
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);
        return redirect()->route('Incoming.GIP.dvrequests.withid', $dvRequest->id)
                         ->with('success', 'DV Request has been verified successfully.');
    }
        public function reject(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP'))
                            ->findOrFail($id);

        // Update status to rejected
        $dvRequest->status = 'Incoming-GIP_rejected';
        $dvRequest->save();

        // Log the rejection
        $this->logAction(
            $dvRequest->id,
            'Incoming',
            'Rejected',
            $request->remarks ?? 'Request rejected by Incoming-GIP'
        );

        return redirect()->route('Incoming.GIP.index')
                        ->with('error', 'DV Request has been rejected.');
    }

    
    public function redirectToFirstPending()
    {
        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'GIP')) 
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
        ->whereIn('status', ['Incoming-GIP_verified','Incoming-GIP_rejected']) 
        ->orderBy('updated_at', 'desc')
        ->get();

    return view('Incoming.GIP.loghistory', compact('dvRequests'));
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
