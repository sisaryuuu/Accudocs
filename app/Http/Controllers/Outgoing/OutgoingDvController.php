<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrsRequest;
use App\Models\AdlCode;
use App\Models\UacsCode;
use App\Models\Mfopap;
use App\Models\DvRequest;
use App\Services\PusherService;
use App\Models\GIPAttachment;
use App\Models\TUPADAttachment;
use App\Models\TEVAttachment;
use App\Models\Notification;
use App\Models\DvRequestLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

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
            ->whereIn('status', ['ORS_obligated','Outgoing_received','Outgoing_finalized','Outgoing_in_process'])
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
                'control_no' => $req->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($req->accounting_control_number, 5, '0', STR_PAD_LEFT): '—',
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
    $dvRequest = DvRequest::with(['respocenter','focalPerson','orsRequest','tupadAttachments','tevAttachments','logs'])
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

     $selectedModality = $dvRequest->tupad_modality;
    
   $attachments = collect();
    if ($selectedModality) {
        $attachments = $dvRequest->tupadAttachments()
            ->where('modality', $selectedModality)
            ->orderBy('sort_order')
            ->get();
    }
    $tevSelectedModality= $dvRequest->tev_modality;

    $tevAttachments = collect();
    if($tevSelectedModality){
        $tevAttachments = $dvRequest->tevAttachments()
        ->where('modality', $tevSelectedModality)
        ->orderBy('sort_order')
        ->get();
    }

    $lastLog  = $dvRequest->logs()->latest()->first();
    $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

    return view('Outgoing.finalizedv', compact('dvRequest', 'lastStep' ,'attachments',
        'selectedModality','tevSelectedModality','tevAttachments'));
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
             'accountingunit_id'=> auth()->id(),
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

        // ✅ To Budget Officer channel (for real-time table update)
        $this->pusher->trigger('dv-requests-outgoing', 'dv-request-received', [
            'dv_request_id' => $dvRequest->id,
            'status'        => $dvRequest->status,
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

         $this->pusher->trigger('dv-requests-outgoing', 'dv-request-processing', [
                'dv_request_id' => $dvRequest->id,
                'status'        => $status,
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
        'gip_attachments' => 'nullable|array',
        'gip_attachments.*' => 'nullable',
        'attachments' => 'nullable|array',
        'attachments.*' => 'nullable',
        'tev_attachments' => 'nullable|array',
        'tev_attachments.*' => 'nullable',
    ]);

    $dvRequest = DvRequest::with(['gipAttachments', 'tupadAttachments', 'tevAttachments'])->findOrFail($id);

    if ($dvRequest->status !== 'Outgoing_in_process') {
        return redirect()->back()->with('error', 'This DV request cannot be finalized yet.');
    }

    // Generate DV Number
    $year  = date('Y');
    $month = date('m');
    $series = $dvRequest->id;
    $seriesFormatted = str_pad($series, 4, '0', STR_PAD_LEFT);
    $dvNumber = "{$year}-{$month}-{$seriesFormatted}";

    // ✅ Handle GIP Attachments
    if ($dvRequest->program->prcode === 'GIP' && $request->has('gip_attachments')) {
        foreach ($request->input('gip_attachments', []) as $attachmentId => $value) {
            $attachment = $dvRequest->gipAttachments()->find($attachmentId);
            if ($attachment) {
                $attachment->update(['is_present' => true]);
            }
        }
        
        // Mark unchecked as not present
        $checkedIds = array_keys($request->input('gip_attachments', []));
        $dvRequest->gipAttachments()
            ->whereNotIn('id', $checkedIds)
            ->update(['is_present' => false]);
    }

    // ✅ Handle TUPAD Attachments
    if ($dvRequest->program->prcode === 'TUPAD' && $dvRequest->tupad_modality && $request->has('attachments')) {
        foreach ($request->input('attachments', []) as $attachmentId => $value) {
            $attachment = $dvRequest->tupadAttachments()->find($attachmentId);
            if ($attachment) {
                $attachment->update(['is_present' => true]);
            }
        }
        
        // Mark unchecked as not present
        $checkedIds = array_keys($request->input('attachments', []));
        $dvRequest->tupadAttachments()
            ->where('modality', $dvRequest->tupad_modality)
            ->whereNotIn('id', $checkedIds)
            ->update(['is_present' => false]);
    }

    // ✅ Handle TEV Attachments
    if ($dvRequest->program->prcode === 'TEV' && $dvRequest->tev_modality && $request->has('tev_attachments')) {
        foreach ($request->input('tev_attachments', []) as $attachmentId => $value) {
            $attachment = $dvRequest->tevAttachments()->find($attachmentId);
            if ($attachment) {
                $attachment->update(['is_present' => true]);
            }
        }
        
        // Mark unchecked as not present
        $checkedIds = array_keys($request->input('tev_attachments', []));
        $dvRequest->tevAttachments()
            ->where('modality', $dvRequest->tev_modality)
            ->whereNotIn('id', $checkedIds)
            ->update(['is_present' => false]);
    }

    // Update DV Request
    DvRequest::where('id', $dvRequest->id)->update([
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
    $action = 'Finalized';
    $remarks = ($request->remarks ?? 'DV finalized by Outgoing') . " | DV Number: {$dvNumber}";

    Notification::create([
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

    $this->pusher->trigger('dv-requests-outgoing', 'dv-request-finalized', [
        'dv_request_id' => $dvRequest->id,
        'dv_number'     => $dvRequest->dv_number,
        'status'        => $status,
        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
    ]);

    $this->logAction(
        $dvRequest->id,
        'Outgoing',
        'Outgoing_finalized',
        $remarks
    );

     return redirect()->route('Outgoing.dvrequests.preview', $dvRequest->id)
        ->with('success', "DV Request finalized with DV Number: {$dvNumber}")
        ->with('print_modal', true);
}
    public function reject(Request $request, $id)
{
    $request->validate([
        'remarks' => 'required|string|max:1000',
    ]);

    try {
        $dvRequest = DvRequest::findOrFail($id);

        // ✅ Only allow rejection if status is currently in Outgoing stage
        if (!in_array($dvRequest->status, ['Outgoing_in_process'])) {
            return response()->json([
                'success' => false,
                'message' => 'This DV request cannot be rejected at its current stage.'
            ], 422);
        }

        $rejectedAt = now();
        $focalId = $dvRequest->focalperson_id;
        $remarks = trim($request->remarks);

        // ✅ Update DV request
        $dvRequest->update([
            'status'     => 'Outgoing_rejected',
            'locked_by'  => null,
            'locked_at'  => null,
        ]);

        // ✅ Log rejection - REMOVE the prefix, just use remarks
        $this->logAction(
            $dvRequest->id,
            'Outgoing',
            'Outgoing_rejected',
            $remarks  // ✅ Changed from "Rejected by Outgoing: {$remarks}"
        );

        // ✅ Notify focal person
        Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id'  => $dvRequest->id,
            'status'         => 'Outgoing_rejected',
            'action'         => 'Rejected',
            'remarks'        => $remarks,
        ]);

        // ✅ Real-time push to focal person
        $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'Outgoing_rejected',
            'remarks'       => $remarks,
            'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        // ✅ Real-time push to Outgoing dashboard
        $this->pusher->trigger('dv-requests-outgoing', 'dv-request-rejected', [
            'dv_request_id' => $dvRequest->id,
            'status'        => 'Outgoing_rejected',
            'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        // ✅ Return JSON if AJAX
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'DV request has been rejected successfully.',
                'data' => [
                    'id'         => $dvRequest->id,
                    'status'     => 'Outgoing_rejected',
                    'remarks'    => $remarks,
                ]
            ]);
        }

        // ✅ Fallback redirect
        return redirect()->route('Outgoing.dvrequests.index')
                         ->with('error', 'DV request has been rejected.');

    } catch (\Exception $e) {
        \Log::error('Error rejecting DV request: ' . $e->getMessage());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the DV request.'
            ], 500);
        }

        return redirect()->back()->with('error', 'An error occurred while rejecting the DV request.');
    }
}
 public function printOfficialDv($id)
{
    $dvRequest = DvRequest::with([
        'program',
        'focalPerson',
        'respocenter',
        'orsRequest.uacsCodes', // ✅ Load through orsRequest
    ])->findOrFail($id);

    if ($dvRequest->status !== 'Outgoing_finalized') {
        return redirect()->back()->with('error', 'This DV has not been finalized yet.');
    }

    if (!$dvRequest->dv_number) {
        return redirect()->back()->with('error', 'DV Number has not been assigned yet.');
    }

    return view('Outgoing.print-official-dv', compact('dvRequest'));
}

public function generatePdf($id)
{
    try {
        $dvRequest = DvRequest::with([
            'program',
            'focalPerson', 
            'respocenter',
            'orsRequest.uacsCodes', // ✅ Load through orsRequest
        ])->findOrFail($id);

        if ($dvRequest->status !== 'Outgoing_finalized') {
            return redirect()->back()->with('error', 'This DV has not been finalized yet.');
        }

        if (!$dvRequest->dv_number) {
            return redirect()->back()->with('error', 'DV Number has not been assigned yet.');
        }

        $data = $this->prepareDvData($dvRequest);
        
        $pdf = Pdf::loadView('Outgoing.pdf.dv-document', $data);
        $pdf->setPaper('legal', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'times',
            'dpi' => 150,
        ]);

        $filename = 'DV_' . str_replace('-', '_', $dvRequest->dv_number) . '_' . date('Ymd') . '.pdf';
        
        return $pdf->download($filename);

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error generating PDF: ' . $e->getMessage());
    }
}

public function previewDv($id)
{
    $dvRequest = DvRequest::with([
        'program',
        'focalPerson',
        'respocenter',
        'modeOfPayments',
        'orsRequest.uacsCodes',
        'orsRequest.Mfopap' // ✅ Load through orsRequest
    ])->findOrFail($id);
    $dvRequest->modeOfPayments->each(fn($mop) => $mop->pivot->load('paymentDetail'));
    if ($dvRequest->status !== 'Outgoing_finalized') {
        return redirect()->back()->with('error', 'This DV has not been finalized yet.');
    }
    
    
    $modeOfPayment = $dvRequest->modeOfPayments->first();
    $paymentDetail = $modeOfPayment?->pivot?->paymentDetail;
    $orsRequest = $dvRequest->orsRequest;
    $uacsCodes = $orsRequest && $orsRequest->uacsCodes ? $orsRequest->uacsCodes : collect();
    $totalAmount = $uacsCodes->count() > 0 ? $uacsCodes->sum('amount') : $dvRequest->amount;
    
    $data = [
        'dvRequest' => $dvRequest,
        'date' => $dvRequest->created_at->format('m/d/Y'),
        'dv_no' => $dvRequest->dv_number,
        'mode_of_payment' => $modeOfPayment?->mopname ?? 'Not specified',
        'payee' => $dvRequest->payee_name ?? ($dvRequest->focalPerson ? 
                trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')) : 'N/A'),
        'tin' => $dvRequest->focalPerson->TIN_number ?? $dvRequest->focalPerson->tin_number ?? '___________',
        'amount' => number_format($dvRequest->amount, 2),
        'address' => $dvRequest->Address ?? $dvRequest->payee_address ?? 'DAVAO CITY',
        'particulars' => $dvRequest->particulars ?? $dvRequest->purpose ?? 'N/A',
        'respocenter_code' => $dvRequest->respocenter->code ?? '',
        'respocenter_desc' => $dvRequest->respocenter->description ?? '',
        'mfocode' => $orsRequest && $orsRequest->mfopap ? ($orsRequest->mfopap->mfocode ?? '') : '',
        'mfodescription' => $orsRequest && $orsRequest->mfopap ? ($orsRequest->mfopap->mfodescription ?? '') : '',
        'amount' => number_format($totalAmount, 2),
        'uacsCodes' => $uacsCodes,
        'imsd_head' => 'Name of IMSD Head',
        'accountant_name' => 'Name of Accountant',
        'regional_director' => 'Name of Regional Director',
        'bank_name' => $paymentDetail?->bank_name ?? '',
        'bank_accnum' => $paymentDetail?->bank_accnum ?? '',
        'bank_address' => $paymentDetail?->bank_address ?? '',
        'ors_number' => $orsRequest->ors_number ?? '___________',
        'imsd_head' => \App\Models\Setting::get('imsd_head', 'NAME OF IMSD HEAD'),
        'accountant_name' => \App\Models\Setting::get('accountant_name', 'NAME OF ACCOUNTANT'),
        'regional_director' => \App\Models\Setting::get('regional_director', 'NAME OF REGIONAL DIRECTOR'),
    ];

    // ✅ Return view with prepared data
    return view('Outgoing.previewdv', [
    'data' => $data,
    'dvRequest' => $dvRequest,
    'orsRequest' => $orsRequest,
    'uacsCodes' => $uacsCodes,
    
]);
}

private function getValidatedDvRequest($id)
{
    $dvRequest = DvRequest::with([
        'program',
        'focalPerson', 
        'respocenter',
        'orsRequest.uacsCodes', // ✅ Load through orsRequest
        'orsRequest.Mfopap' // ✅ Load through orsRequest
    ])->findOrFail($id);

    if ($dvRequest->status !== 'Outgoing_finalized') {
        throw new \Exception('This DV has not been finalized yet.');
    }

    if (!$dvRequest->dv_number) {
        throw new \Exception('DV Number has not been assigned yet.');
    }

    return $dvRequest;
}

    private function prepareDvData($dvRequest)
{
    // Get the ORS request data with UACS codes
    $orsRequest = $dvRequest->orsRequest;
    
    // Get UACS codes from ORS request (not from DV request)
    $uacsCodes = $orsRequest && $orsRequest->uacsCodes ? $orsRequest->uacsCodes : collect();
    
    // Calculate total amount from UACS codes if available, otherwise use DV amount
    $totalAmount = $uacsCodes->count() > 0 ? $uacsCodes->sum('amount') : $dvRequest->amount;
    
    return [
        'dvRequest' => $dvRequest,
        'date' => $dvRequest->created_at->format('m/d/Y'),
        'dv_no' => $dvRequest->dv_number,
        'mode_of_payment' => $dvRequest->mode_of_payment ?? 'MDS Check',
        'payee' => $dvRequest->payee_name ?? ($dvRequest->focalPerson ? 
                trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')) : 'N/A'),
        'tin' => $dvRequest->TIN_number ?? $dvRequest->tin_number ?? '___________',
        'address' => $dvRequest->Address ?? $dvRequest->payee_address ?? 'DAVAO CITY',
        'particulars' => $dvRequest->particulars ?? $dvRequest->purpose ?? 'N/A',
        'respocenter_code' => $dvRequest->respocenter->code ?? '',
        'respocenter_desc' => $dvRequest->respocenter->description ?? '',
        'mfocode' => $dvRequest->program->code ?? $dvRequest->program->prcode ?? '',
        'mfodescription' => $dvRequest->program->name ?? '',
        'amount' => number_format($totalAmount, 2),
        'uacsCodes' => $uacsCodes,
        'imsd_head' => 'Name of IMSD Head',
        'accountant_name' => 'Name of Accountant',
        'regional_director' => 'Name of Regional Director',
        'bank_name' => $dvRequest->bank_name ?? '___________',
        'bank_accnum' => $dvRequest->bank_account_number ?? $dvRequest->bank_accnum ?? '___________',
        'ors_number' => $orsRequest->ors_number ?? '___________',
    ];
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
