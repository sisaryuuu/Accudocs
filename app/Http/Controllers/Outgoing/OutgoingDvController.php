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
use App\Services\SendPulseServices; // ADD SENDPULSE SERVICE
use App\Services\IprogSmsService;
use App\Models\GIPAttachment;
use App\Models\TUPADAttachment;
use App\Models\TEVAttachment;
use App\Models\Notification;
use App\Models\OutgoingFinalizationHistory;
use App\Models\DvRequestLog;
use SimpleSoftwareIO\QrCode\Facades\Qrcode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Traits\TriggersDashboardUpdate; // ADD DASHBOARD UPDATE TRAIT

class OutgoingDvController extends Controller
{
    protected PusherService $pusher;
    protected IprogSmsService $smsService;
    use TriggersDashboardUpdate; // USE THE TRAIT

    public function __construct(PusherService $pusher, IprogSmsService $smsService)
    {
        $this->pusher = $pusher;
        $this->smsService = $smsService;
    }

    public function index()
    {
        return view('Outgoing.index');
    }

    public function fetchDvRequests(Request $request)
    {
        $currentUserId = auth()->id();
        $lockDuration  = now()->subMinutes(10);
        
        // Get search parameter from request
        $searchTerm = $request->input('search', '');

        $dvRequests = DvRequest::with(['program', 'focalPerson', 'respocenter', 'orsRequest'])
            ->whereIn('status', ['ORS_obligated','Outgoing_received']) // REMOVED: Outgoing_in_process
            ->where(function ($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by')              
                ->orWhere('locked_by', $currentUserId)  
                ->orWhere('locked_at', '<=', $lockDuration); 
            })
            ->when($searchTerm, function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    // Search in control number
                    $q->where('accounting_control_number', 'LIKE', "%{$searchTerm}%")
                    // Search in focal person name
                    ->orWhereHas('focalPerson', function ($personQuery) use ($searchTerm) {
                        $personQuery->where('fname', 'LIKE', "%{$searchTerm}%")
                                    ->orWhere('lname', 'LIKE', "%{$searchTerm}%");
                    })
                    // Search in program code
                    ->orWhereHas('program', function ($programQuery) use ($searchTerm) {
                        $programQuery->where('prcode', 'LIKE', "%{$searchTerm}%");
                    })
                    // Search in respocenter
                    ->orWhereHas('respocenter', function ($centerQuery) use ($searchTerm) {
                        $centerQuery->where('rccode', 'LIKE', "%{$searchTerm}%")
                                    ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                    })
                    // Search in particulars
                    ->orWhere('particulars', 'LIKE', "%{$searchTerm}%")
                    // Search in amount (handle numeric search)
                    ->orWhere('amount', 'LIKE', "%{$searchTerm}%")
                    // Search in status
                    ->orWhere('status', 'LIKE', "%{$searchTerm}%")
                    // Search in ORS number
                    ->orWhereHas('orsRequest', function ($orsQuery) use ($searchTerm) {
                        $orsQuery->where('ors_number', 'LIKE', "%{$searchTerm}%");
                    })
                    // Search in DV number
                    ->orWhere('dv_number', 'LIKE', "%{$searchTerm}%");
                });
            })
            ->latest()
            ->get();

        return response()->json($dvRequests->map(function ($req) {
            return [
                'id'           => $req->id,
                'control_no' => $req->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($req->accounting_control_number, 4, '0', STR_PAD_LEFT): '—',
                'submitted_by' => trim(($req->focalPerson->fname ?? '') . ' ' . ($req->focalPerson->lname ?? '')),
                'program'      => $req->program->prcode ?? 'N/A',
                'respocenter'  => trim(($req->respocenter->rccode ?? '') . ' - ' . ($req->respocenter->description ?? '')) ?? 'N/A',
                'particulars'  => $req->particulars,
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
            ->whereIn('status', ['ORS_obligated','Outgoing_received']) // REMOVED: Outgoing_in_process
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
            $statusBefore = $dvRequest->status;
            $receivedAt = now();

            if ($dvRequest->status !== 'ORS_obligated') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is not yet ready for Outgoing to receive.'
                ], 422);
            }

            $dvRequest->update([
                'status'      => 'Outgoing_received',
                'accountingunit_id'=> auth()->id(),
                'received_by' => auth()->id(),
                'received_at' => $receivedAt,
                'locked_by'   => auth()->id(),
                'locked_at'   => $receivedAt,
            ]);


            $this->saveOutgoingFinalizationHistory(
                $dvRequest,
                'receive',
                'DV request received by Outgoing for final verification',
                [
                    'status_before' => $statusBefore,
                    'status_after'  => 'Outgoing_received',
                    'received_by' => auth()->id(),
                    'received_at' => $receivedAt->toISOString(),
                    'amount' => $dvRequest->amount
                ]
            );

            $this->logAction(
                $dvRequest->id,
                'Outgoing',
                'Outgoing_received',
                'DV request received by Outgoing for final verification'
            );

            $focalId = $dvRequest->focalperson_id;
            $remarks = $request->remarks ?? "DV request received by Outgoing for final verification";

            Notification::create([
                'focalperson_id' => $focalId,
                'dv_request_id'  => $dvRequest->id,
                'status'         => 'Outgoing_received', 
                'action'         => 'Received',
                'remarks'        => $remarks,
            ]);

            $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status'        => 'Outgoing_received',
                'remarks'       => $remarks,
                'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            // ✅ ADD action_performer_id
            $this->pusher->trigger('dv-requests-outgoing', 'dv-request-received', [
                'dv_request_id' => $dvRequest->id,
                'control_number' => $dvRequest->accounting_control_number,
                'status'        => $dvRequest->status,
                'received_by'   => auth()->user()->name ?? 'Outgoing User',
                'action_performer_id' => auth()->id(),
                'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            $this->triggerDashboardUpdate(); // TRIGGER DASHBOARD UPDATE

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

    // REMOVED: startProcessing method

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
        $statusBefore = $dvRequest->status;

        // UPDATED: Allow finalization from Outgoing_received status (removed Outgoing_in_process)
        if ($dvRequest->status !== 'Outgoing_received') {
            return redirect()->back()->with('error', 'This DV request cannot be finalized yet.');
        }

        // Generate DV Number
        $year  = date('Y');
        $month = date('m');
        $series = $dvRequest->accounting_control_number; // fallback if null
        $seriesFormatted = str_pad($series, 4, '0', STR_PAD_LEFT);
        $dvNumber = "{$year}-{$month}-{$seriesFormatted}";

        if (empty($dvRequest->accounting_control_number)) {
            return redirect()->back()->with('error', 'Accounting Control Number is required before finalization.');
        }

        $qrCode = $this->generateDvQrCode($dvRequest->id, $dvNumber);

        // Collect attachment data for verification history
        $attachmentData = [];

        // ✅ Handle GIP Attachments
        if ($dvRequest->program->prcode === 'GIP' && $request->has('gip_attachments')) {
            foreach ($request->input('gip_attachments', []) as $attachmentId => $value) {
                $attachment = $dvRequest->gipAttachments()->find($attachmentId);
                if ($attachment) {
                    $attachment->update(['is_present' => true]);
                    
                    // Collect attachment data
                    $attachmentData[] = [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'is_present' => true,
                        'type' => 'gip'
                    ];
                }
            }
            
            // Mark unchecked as not present and collect data
            $checkedIds = array_keys($request->input('gip_attachments', []));
            $dvRequest->gipAttachments()
                ->whereNotIn('id', $checkedIds)
                ->each(function($attachment) use (&$attachmentData) {
                    $attachment->update(['is_present' => false]);
                    
                    $attachmentData[] = [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'is_present' => false,
                        'type' => 'gip'
                    ];
                });
        }

        // ✅ Handle TUPAD Attachments
        if ($dvRequest->program->prcode === 'TUPAD' && $dvRequest->tupad_modality && $request->has('attachments')) {
            foreach ($request->input('attachments', []) as $attachmentId => $value) {
                $attachment = $dvRequest->tupadAttachments()->find($attachmentId);
                if ($attachment) {
                    $attachment->update(['is_present' => true]);
                    
                    $attachmentData[] = [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'is_present' => true,
                        'type' => 'tupad',
                        'modality' => $attachment->modality
                    ];
                }
            }
            
            // Mark unchecked as not present
            $checkedIds = array_keys($request->input('attachments', []));
            $dvRequest->tupadAttachments()
                ->where('modality', $dvRequest->tupad_modality)
                ->whereNotIn('id', $checkedIds)
                ->each(function($attachment) use (&$attachmentData) {
                    $attachment->update(['is_present' => false]);
                    
                    $attachmentData[] = [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'is_present' => false,
                        'type' => 'tupad',
                        'modality' => $attachment->modality
                    ];
                });
        }

        // ✅ Handle TEV Attachments
        if ($dvRequest->program->prcode === 'TEV' && $dvRequest->tev_modality && $request->has('tev_attachments')) {
            foreach ($request->input('tev_attachments', []) as $attachmentId => $value) {
                $attachment = $dvRequest->tevAttachments()->find($attachmentId);
                if ($attachment) {
                    $attachment->update(['is_present' => true]);
                    
                    $attachmentData[] = [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'is_present' => true,
                        'type' => 'tev',
                        'modality' => $attachment->modality
                    ];
                }
            }
            
            // Mark unchecked as not present
            $checkedIds = array_keys($request->input('tev_attachments', []));
            $dvRequest->tevAttachments()
                ->where('modality', $dvRequest->tev_modality)
                ->whereNotIn('id', $checkedIds)
                ->each(function($attachment) use (&$attachmentData) {
                    $attachment->update(['is_present' => false]);
                    
                    $attachmentData[] = [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'is_present' => false,
                        'type' => 'tev',
                        'modality' => $attachment->modality
                    ];
                });
        }

        // ✅ UPDATE DV REQUEST STATUS FIRST
        $dvRequest->update([
            'status'     => 'Outgoing_finalized', // This should be the new status
            'dv_number'  => $dvNumber,
            'locked_by'  => null,
            'locked_at'  => null,
        ]);

        // ✅ Refresh the model to get the updated status
        $dvRequest->refresh();

        if ($dvRequest->focalPerson) {
            $dvRequest->focalPerson->update([
                'dv_number' => $dvNumber
            ]);
        }

        // ✅ NOW SAVE FINALIZATION HISTORY WITH CORRECT STATUS_AFTER
        $finalizedAt = now();
        $this->saveOutgoingFinalizationHistory(
            $dvRequest,
            'finalize',
            $request->remarks ?? "DV finalized with DV Number: {$dvNumber}",
            [
                'status_before' => $statusBefore,
                'status_after' => 'Outgoing_finalized', 
                'dv_number' => $dvNumber,
                'attachments_checked' => $attachmentData,
                'qr_code_generated' => !empty($qrCode),
                'finalized_by' => auth()->id(),
                'finalized_at' => $finalizedAt->toISOString(),
                'amount' => $dvRequest->amount,
                'program' => $dvRequest->program->prcode ?? null
            ]
        );

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
            'timestamp'     => $finalizedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        $this->pusher->trigger('dv-requests-outgoing', 'dv-request-finalized', [
            'dv_request_id' => $dvRequest->id,
            'dv_number'     => $dvRequest->dv_number,
            'status'        => $status,
            'action_performer_id' => auth()->id(), // ✅ ADD THIS
            'timestamp'     => $finalizedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        $this->pusher->trigger('dv-requests-ranca', 'dv-request-submitted', [
            'dv_request_id' => $dvRequest->id,
            'control_number' => $dvRequest->control_number ?? '#' . $dvRequest->id,
            'submitted_by' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
            'program' => $dvRequest->program->prcode ?? 'N/A',
            'amount' => $dvRequest->amount,
            'status' => 'Outgoing_finalized',
            'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);

        \Log::info('✅ Broadcast to RANCA successful');
        
        $this->logAction(
            $dvRequest->id,
            'Outgoing',
            'Outgoing_finalized',
            $remarks
        );

        $this->triggerDashboardUpdate(); // TRIGGER DASHBOARD UPDATE

        return redirect()->route('Outgoing.dvrequests.preview', $dvRequest->id)
            ->with('success', "DV Request finalized with DV Number: {$dvNumber}")
            ->with('print_modal', true);
    }
    private function generateDvQrCode($dvRequestId, $dvNumber)
    {
        try {
            \Log::info('Starting QR Code Generation via API', [
                'dv_request_id' => $dvRequestId,
                'dv_number' => $dvNumber,
            ]);

            $dvRequest = DvRequest::find($dvRequestId);
            
            if (!$dvRequest) {
                \Log::error('DV Request not found for QR generation', ['dv_id' => $dvRequestId]);
                return null;
            }

            // ✅ FIX: Check if QR code already exists FIRST (remove the recursive call)
            if ($dvRequest->qr_code_data) {
                \Log::info('Using existing QR code from database');
                return $dvRequest->qr_code_data;
            }

            // Create a unique token
            $token = $dvRequest->qr_code_token ?? hash('sha256', $dvRequestId . $dvNumber . now()->timestamp . uniqid());
            
            // Generate QR code data - use the centralized QR controller route
            $qrData = json_encode([
                'dv_id' => $dvRequestId,
                'dv_number' => $dvNumber,
                'token' => $token,
                'action_url' => route('dv.qr.update-status', ['token' => $token]),
                'timestamp' => now()->timestamp,
                'type' => 'dv_status_update'
            ]);

            // ✅ Use external QR code APIs with SMALLER SIZE (150x150 instead of 300x300)
            $verifyUrl = route('dv.qr.update-status', ['token' => $token]);
            
            // Try multiple QR code APIs for reliability
            $qrCodeContent = null;
            
            // API 1: QR Server API - REDUCED SIZE TO 150x150
            $apiUrl1 = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&format=png&data=' . urlencode($verifyUrl);
            $qrCodeContent = @file_get_contents($apiUrl1);
            
            if ($qrCodeContent === false || empty($qrCodeContent)) {
                \Log::warning('QR Server API failed, trying Google Charts API');
                
                // API 2: Google Charts API (fallback) - REDUCED SIZE TO 150x150
                $apiUrl2 = 'https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=' . urlencode($verifyUrl) . '&choe=UTF-8';
                $qrCodeContent = @file_get_contents($apiUrl2);
            }
            
            if ($qrCodeContent === false || empty($qrCodeContent)) {
                \Log::warning('Google Charts API failed, trying GoQR API');
                
                // API 3: GoQR API (fallback) - REDUCED SIZE TO 150x150
                $apiUrl3 = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($verifyUrl);
                $qrCodeContent = @file_get_contents($apiUrl3);
            }

            if ($qrCodeContent === false || empty($qrCodeContent)) {
                \Log::error('All QR code APIs failed');
                throw new \Exception('Failed to generate QR code from external APIs');
            }

            // Convert to base64
            $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCodeContent);

            // Verify the base64 data is valid
            if (empty($base64QrCode)) {
                throw new \Exception('Failed to encode QR code image to base64');   
            }

            // Store QR code info in the database
            $updateData = [
                'qr_code_token' => $token,
                'qr_code_generated_at' => now(),
                'qr_status' => 'inactive',
                'qr_code_data' => $base64QrCode,
            ];

            $updated = $dvRequest->update($updateData);

            if (!$updated) {
                \Log::error('Failed to update DV request with QR code data');
                throw new \Exception('Failed to save QR code to database');
            }

            \Log::info('QR Code generated via API successfully', [
                'dv_id' => $dvRequestId,
                'dv_number' => $dvNumber,
                'token' => substr($token, 0, 10) . '...',
                'qr_status' => 'inactive',
                'api_used' => 'External QR API',
                'size' => '150x150'
            ]);

            return $base64QrCode;

        } catch (\Exception $e) {
            \Log::error('QR Code Generation Error: ' . $e->getMessage(), [
                'dv_id' => $dvRequestId,
                'dv_number' => $dvNumber,
            ]);
            return null;
        }
    }
    // Helper method to display QR code in your view
    public function getQrCodeForDv($id)
    {
        try {
            $dvRequest = DvRequest::findOrFail($id);
            
            // If QR code already exists and is valid, return it
            if ($dvRequest->qr_code_token && $dvRequest->qr_code_data) {
                return response()->json([
                    'success' => true,
                    'qr_code' => $dvRequest->qr_code_data,
                    'dv_number' => $dvRequest->dv_number
                ]);
            }
            
            // Generate new QR code if it doesn't exist
            if ($dvRequest->dv_number) {
                $qrCode = $this->generateDvQrCode($dvRequest->id, $dvRequest->dv_number);
                
                if ($qrCode) {
                    return response()->json([
                        'success' => true,
                        'qr_code' => $qrCode,
                        'dv_number' => $dvRequest->dv_number
                    ]);
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate QR code'
            ], 500);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching QR code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating QR code'
            ], 500);
        }
    }

       public function reject(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);
    
        try {
            $dvRequest = DvRequest::findOrFail($id);
            $statusBefore = $dvRequest->status;
    
            // ✅ Only allow rejection if status is currently in Outgoing stage
            if (!in_array($dvRequest->status, ['Outgoing_received'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This DV request cannot be rejected at its current stage.'
                ], 422);
            }
    
            $rejectedAt = now();
            $focalId = $dvRequest->focalperson_id;
            $remarks = trim($request->remarks);
    
            // ✅ Initialize notification variables
            $emailSent = false;
            $smsSent = false;
    
            DB::transaction(function () use (
                $dvRequest, 
                $statusBefore, 
                $rejectedAt, 
                $request, 
                $focalId,
                $remarks,
                &$emailSent,
                &$smsSent
            ) {
                // ✅ Update DV request
                $dvRequest->update([
                    'status'     => 'Outgoing_rejected',
                    'locked_by'  => null,
                    'locked_at'  => null,
                ]);
    
                // ✅ SEND REJECTION EMAIL USING SENDPULSE
                $emailSent = $this->sendRejectionEmail($dvRequest, $remarks);
    
                // ✅ NEW: SEND REJECTION SMS
                $smsSent = $this->sendRejectionSMS($dvRequest, $remarks);
    
                // ✅ SAVE FINALIZATION HISTORY
                $this->saveOutgoingFinalizationHistory(
                    $dvRequest,
                    'reject',
                    $remarks,
                    [
                        'status_before' => $statusBefore,
                        'status_after' => 'Outgoing_rejected',
                        'rejected_by' => auth()->id(),
                        'rejected_at' => $rejectedAt->toISOString(),
                        'rejection_reason' => $remarks,
                        'email_sent' => $emailSent,
                        'sms_sent' => $smsSent
                    ]
                );
    
                // ✅ Log rejection
                $this->logAction(
                    $dvRequest->id,
                    'Outgoing',
                    'Outgoing_rejected',
                    $remarks
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
                    'action_performer_id' => auth()->id(),
                    'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            });
    
            $this->triggerDashboardUpdate(); // TRIGGER DASHBOARD UPDATE
    
            // ✅ UPDATE RESPONSE MESSAGE TO INCLUDE NOTIFICATION STATUS
            $responseMessage = 'DV request has been rejected';
            
            if ($emailSent && $smsSent) {
                $responseMessage .= ' and notifications sent.';
            } elseif ($emailSent) {
                $responseMessage .= ' (email sent, SMS failed).';
            } elseif ($smsSent) {
                $responseMessage .= ' (SMS sent, email failed).';
            } else {
                $responseMessage .= ' (both notifications failed).';
            }
    
            // ✅ Return JSON if AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $responseMessage,
                    'data' => [
                        'id'         => $dvRequest->id,
                        'status'     => 'Outgoing_rejected',
                        'remarks'    => $remarks,
                        'email_sent' => $emailSent,
                        'sms_sent'   => $smsSent
                    ]
                ]);
            }
    
            // ✅ Fallback redirect
            return redirect()->route('Outgoing.dvrequests.index')
                             ->with('success', $responseMessage);
    
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
    
    
    /**
 * ✅ SEND REJECTION SMS - For Outgoing rejections
 */
    protected function sendRejectionSMS($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            // Use 'contactnumber' instead of 'phone'
            if (!$focalPerson || empty($focalPerson->contactnumber)) {
                \Log::warning('Cannot send rejection SMS: focal person or contact number missing', [
                    'dv_request_id' => $dvRequest->id,
                    'focal_person_id' => $focalPerson->id ?? 'null',
                    'has_contactnumber' => !empty($focalPerson->contactnumber)
                ]);
                return false;
            }
    
            $phoneNumber = $focalPerson->contactnumber;
            
            // Truncate remarks for SMS (SMS has character limits)
            $truncatedRemarks = strlen($rejectionRemarks) > 50 
                ? substr($rejectionRemarks, 0, 47) . '...' 
                : $rejectionRemarks;
    
            // ✅ Use user_sequence instead of id
            $userSequence = $dvRequest->user_sequence ?? 'N/A';
            $message = "Hi {$focalPerson->fname}, your DV Request #{$userSequence} has been rejected by Outgoing. Reason: {$truncatedRemarks}.";
    
            \Log::info('Sending Outgoing rejection SMS via iProg', [
                'phone' => $phoneNumber,
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $userSequence,
                'message_length' => strlen($message)
            ]);
    
            $result = $this->smsService->sendSMS($phoneNumber, $message);
            
            if ($result['success']) {
                \Log::info('Outgoing rejection SMS sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'user_sequence' => $userSequence,
                    'phone' => $phoneNumber,
                    'message_id' => $result['message_id'] ?? 'N/A'
                ]);
                return true;
            } else {
                \Log::error('Failed to send Outgoing rejection SMS', [
                    'dv_request_id' => $dvRequest->id,
                    'user_sequence' => $userSequence,
                    'phone' => $phoneNumber,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            \Log::error('Outgoing SMS rejection notification failed: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $dvRequest->user_sequence ?? 'N/A',
                'exception' => $e->getTraceAsString()
            ]);
            return false;
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

public function previewDv($id)
    {
        try {
            \Log::info('Accessing previewDv for ID: ' . $id);
            
            $dvRequest = DvRequest::with([
                'program',
                'focalPerson',
                'respocenter',
                'modeOfPayments',
                'orsRequest.uacsCodes',
                'orsRequest.Mfopap'
            ])->findOrFail($id);
            
            \Log::info('DV Request found:', [
                'id' => $dvRequest->id,
                'status' => $dvRequest->status,
                'dv_number' => $dvRequest->dv_number,
                'finalized' => $dvRequest->status === 'Outgoing_finalized'
            ]);
        

            
            if (!$dvRequest->dv_number) {
                \Log::warning('DV number missing for preview', ['id' => $dvRequest->id]);
                return redirect()->back()->with('error', 'DV Number has not been assigned yet.');
            }

            $modeOfPayment = $dvRequest->modeOfPayments->first();
            $paymentDetail = $modeOfPayment?->pivot?->paymentDetail;
            $orsRequest = $dvRequest->orsRequest;
            $uacsCodes = $orsRequest && $orsRequest->uacsCodes ? $orsRequest->uacsCodes : collect();

            // Generate or retrieve QR code
            if ($dvRequest->qr_code_data) {
                $qrCode = $dvRequest->qr_code_data;
            } else {
                $qrCode = $this->generateDvQrCode($dvRequest->id, $dvRequest->dv_number);
                $dvRequest->refresh();
            }
            
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
                'respocenter_code' => $dvRequest->respocenter->rccode ?? '',
                'respocenter_desc' => $dvRequest->respocenter->description ?? '',
                'mfocode' => $orsRequest && $orsRequest->mfopap ? ($orsRequest->mfopap->mfocode ?? '') : '',
                'mfodescription' => $orsRequest && $orsRequest->mfopap ? ($orsRequest->mfopap->mfodescription ?? '') : '',
                'uacsCodes' => $uacsCodes,
                'bank_name' => $paymentDetail?->bank_name ?? '',
                'bank_accnum' => $paymentDetail?->bank_accnum ?? '',
                'bank_address' => $paymentDetail?->bank_address ?? '',
                'ors_number' => $orsRequest->ors_number ?? '___________',
                'imsd_head' => \App\Models\Setting::get('imsd_head', 'NAME OF IMSD HEAD'),
                'accountant_name' => \App\Models\Setting::get('accountant_name', 'NAME OF ACCOUNTANT'),
                'regional_director' => \App\Models\Setting::get('regional_director', 'NAME OF REGIONAL DIRECTOR'),
            ];

            \Log::info('Successfully loading preview for DV: ' . $dvRequest->dv_number);
            
            return view('Outgoing.previewdv', [
                'data' => $data,
                'dvRequest' => $dvRequest,
                'orsRequest' => $orsRequest,
                'uacsCodes' => $uacsCodes,
                'qrCode' => $qrCode,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in previewDv: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error accessing DV preview: ' . $e->getMessage());
        }
    }
    public function generatePdf($id)
{
    try {
        \Log::info('=== DV PDF Generation Started ===', ['id' => $id]);
        
        $dvRequest = DvRequest::with([
            'program',
            'focalPerson', 
            'respocenter',
            'modeOfPayments',
            'orsRequest.uacsCodes',
            'orsRequest.Mfopap',
            'orsRequest.allotmentClass',
            'orsRequest.continuingOrCurrent'
        ])->findOrFail($id);

        if ($dvRequest->status !== 'Outgoing_finalized') {
            return redirect()->back()->with('error', 'This DV has not been finalized yet.');
        }

        if (!$dvRequest->dv_number) {
            return redirect()->back()->with('error', 'DV Number has not been assigned yet.');
        }

        // ✅ LOGO HANDLING - SAME AS YOUR ORS METHOD
        $logo_url = null;
        $logoPath = '/home/u203167746/domains/accudocs.accudocs.site/AccudocsProject/storage/app/public/logo/DOLE.png';
        
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $base64 = base64_encode($imageData);
            $logo_url = 'data:image/png;base64,' . $base64;
            \Log::info('Logo loaded for DV PDF');
        } else {
            // Fallback SVG logo
            $svg = '<svg width="200" height="100" xmlns="http://www.w3.org/2000/svg">
                <rect width="200" height="100" fill="#f8f9fa" stroke="#dee2e6"/>
                <text x="100" y="50" text-anchor="middle" dy=".3em" font-family="Arial" font-size="12" fill="#6c757d">DOLE LOGO</text>
            </svg>';
            $logo_url = 'data:image/svg+xml;base64,' . base64_encode($svg);
            \Log::info('Using SVG fallback logo for DV PDF');
        }

        // Get payment details
        $modeOfPayment = $dvRequest->modeOfPayments->first();
        $paymentDetail = null;
        
        if (method_exists($dvRequest, 'paymentDetail')) {
            $paymentDetail = $dvRequest->paymentDetail;
        } elseif ($modeOfPayment && method_exists($modeOfPayment->pivot, 'paymentDetail')) {
            $paymentDetail = $modeOfPayment->pivot->paymentDetail;
        }

        $orsRequest = $dvRequest->orsRequest;
        
        // Get UACS codes from ORS request
        $uacsCodes = $orsRequest && $orsRequest->uacsCodes ? $orsRequest->uacsCodes : collect();    

        // ✅ QR CODE HANDLING
        $qrCode = $dvRequest->qr_code_data;
        
        if (!$qrCode) {
            \Log::info('Generating QR code for DV', ['dv_no' => $dvRequest->dv_number]);
            $qrCode = $this->generateDvQrCode($dvRequest->id, $dvRequest->dv_number);
            $dvRequest->update(['qr_code_data' => $qrCode]);
            \Log::info('QR code generated and saved');
        }

        // ✅ PREPARE UACS DATA AS STRUCTURED ARRAY - SAME AS YOUR ORS METHOD
        $uacsItems = [];
        foreach ($uacsCodes as $uacs) {
            $amount = $uacs->pivot->amount ?? 0;
            $uacsItems[] = [
                'code' => $uacs->uacscode ?? '',
                'description' => $uacs->description ?? '',
                'amount' => $amount
            ];
        }

        // ✅ PREPARE DATA FOR PDF
        $data = [
            'date' => $dvRequest->created_at->format('F d, Y'),
            'dv_no' => $dvRequest->dv_number,
            'mode_of_payment' => $modeOfPayment?->mopname ?? 'Not specified',
            'payee' => $this->formatFullName(
                $dvRequest->focalPerson->fname ?? '',
                $dvRequest->focalPerson->mi ?? '',
                $dvRequest->focalPerson->lname ?? ''
            ),
            'tin' => $dvRequest->focalPerson->TIN_number ?? '___________',
            'ors_number' => $orsRequest->ors_number ?? '___________',
            'address' => $dvRequest->focalPerson->Address ?? 'DAVAO CITY',
            'particulars' => $dvRequest->particulars ?? 'N/A',
            'amount' => number_format($dvRequest->amount, 2),
            'respocenter_code' => $dvRequest->respocenter->rccode ?? '',
            'respocenter_desc' => $dvRequest->respocenter->description ?? '',
            'mfocode' => $orsRequest->Mfopap->mfocode ?? '',
            'mfodescription' => $orsRequest->Mfopap->mfodescription ?? '',
            'bank_name' => $paymentDetail?->bank_name ?? '',
            'bank_accnum' => $paymentDetail?->bank_accnum ?? '',
            'bank_address' => $paymentDetail?->bank_address ?? '',
            'responsibility_center' => $dvRequest->respocenter ? 
                ($dvRequest->respocenter->rccode . ' - ' . $dvRequest->respocenter->description) : '',
            'mfo' => $orsRequest->Mfopap ? 
                ($orsRequest->Mfopap->mfocode . ' - ' . $orsRequest->Mfopap->mfodescription) : '',
            'uacs_items' => $uacsItems, // ✅ STRUCTURED UACS DATA
            'ors_no' => $dvRequest->orsRequest->ors_number ?? '____________',
            'logo_url' => $logo_url // ✅ ADD LOGO URL TO DATA
        ];

        // ✅ GET SETTINGS
        $imsd_head = \App\Models\Setting::get('imsd_head', 'NAME OF IMSD HEAD');
        $accountant_name = \App\Models\Setting::get('accountant_name', 'NAME OF ACCOUNTANT');
        $regional_director = \App\Models\Setting::get('regional_director', 'NAME OF REGIONAL DIRECTOR');
        $budget_officer = \App\Models\Setting::get('budget_officer', 'BUDGET OFFICER\'S NAME');

        // Prepare status data for the table
        $status = [
            [
                'date' => $dvRequest->created_at->format('m/d/Y'),
                'particulars' => $dvRequest->particulars ?? '',
                'ref_no' => $dvRequest->orsRequest->ors_number ?? '____________',
                'obligation' => number_format($dvRequest->amount, 2),
                'payment' => '',
                'due_status' => 'Not yet Due'
            ]
        ];

        \Log::info('DV PDF Data Prepared', [
            'dv_no' => $data['dv_no'],
            'payee' => $data['payee'],
            'amount' => $data['amount'],
            'uacs_count' => count($data['uacs_items'])
        ]);

        // ✅ USE MANUAL DOMPDF INSTANCE LIKE YOUR ORS METHOD
        $dompdf = new \Dompdf\Dompdf();
        
        // Set options directly
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Times New Roman');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false); // ← CRITICAL: Set to false for base64 images
        $options->set('isFontSubsettingEnabled', true);
        $options->set('chroot', base_path());
        $options->set('tempDir', sys_get_temp_dir());
        $options->set('fontDir', storage_path('fonts'));
        $options->set('fontCache', storage_path('fonts'));
        
        $dompdf->setOptions($options);
        $dompdf->setPaper('Legal', 'portrait');

        // Load HTML content - INCLUDE LOGO_URL IN COMPACT
        $html = view('pdf.finaldv', compact(
            'data', 
            'imsd_head',
            'accountant_name',
            'regional_director',
            'budget_officer',
            'qrCode',
            'status',
            'uacsCodes',
            'logo_url' // ✅ ADD THIS
        ))->render();

        $dompdf->loadHtml($html);
        $dompdf->render();

        $filename = 'DV_' . str_replace('-', '_', $dvRequest->dv_number) . '_' . now()->format('Ymd_His') . '.pdf';
        
        \Log::info('DV PDF Generated Successfully', ['filename' => $filename]);
        
        return $dompdf->stream($filename);

    } catch (\Exception $e) {
        \Log::error('PDF Generation Error: ' . $e->getMessage(), [
            'dv_request_id' => $id,
            'error' => $e->getTraceAsString()
        ]);
        return redirect()->back()->with('error', 'Error generating PDF: ' . $e->getMessage());
    }
}

// Keep your helper method
private function formatFullName($fname, $mi, $lname)
{
    $name = trim($fname . ' ' . ($mi ? $mi . ' ' : '') . $lname);
    return $name ?: 'NAME OF PAYEE';
}


    // Add this method to your OutgoingDvController
    public function previewFromHistory($id)
    {
        try {
            $dvRequest = DvRequest::with([
                'program',
                'focalPerson',
                'respocenter',
                'orsRequest'
            ])->findOrFail($id);

            // Allow viewing from history even if not finalized
            // But show a warning if not finalized
            $warning = null;
            if ($dvRequest->status !== 'Outgoing_finalized') {
                $warning = 'Note: This DV request is not yet finalized.';
            }

            return view('Outgoing.history-preview', compact('dvRequest', 'warning'));
            
        } catch (\Exception $e) {
            \Log::error('Error in previewFromHistory: ' . $e->getMessage());
            return redirect()->route('dvrequests.dvloghistory')
                ->with('error', 'Unable to view DV request: ' . $e->getMessage());
        }
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

        $qrCode = $this->generateDvQrCode($dvRequest->id, $dvRequest->dv_number);

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
    
    public function history(Request $request)
    {
        $query = OutgoingFinalizationHistory::with([
            'dvRequest.program',
            'dvRequest.focalPerson', 
            'dvRequest.respocenter',
            'verifier'
        ])
        ->where('verified_by', auth()->id());

        // Search by DV Number
        if ($request->has('search') && $request->search) {
            $query->whereHas('dvRequest', function($q) use ($request) {
                $q->where('dv_number', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by Program
        if ($request->has('program') && $request->program) {
            $query->whereHas('dvRequest.program', function($q) use ($request) {
                $q->where('prcode', $request->program);
            });
        }

        // Filter by Verification Step
        if ($request->has('step') && $request->step) {
            $query->where('verification_step', $request->step);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('verified_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('verified_at', '<=', $request->date_to);
        }

        $verificationHistory = $query->orderBy('verified_at', 'desc')
                                    ->paginate(20)
                                    ->appends($request->all());

        // Debug: Check what data you're getting
        if ($verificationHistory->count() > 0) {
            logger('First record data:', [
                'dv_request_id' => $verificationHistory->first()->dv_request_id,
                'has_dvRequest' => !is_null($verificationHistory->first()->dvRequest),
                'has_program' => !is_null($verificationHistory->first()->dvRequest->program ?? null),
                'has_focalPerson' => !is_null($verificationHistory->first()->dvRequest->focalPerson ?? null),
                'verification_data' => $verificationHistory->first()->verification_data,
            ]);
        } else {
            logger('No verification history records found for the given filters.');
        }

                                    
        return view('Outgoing.dvloghistory', compact('verificationHistory'));
    }

    // ADD this new method for saving verification history
    protected function saveOutgoingFinalizationHistory($dvRequest, $step, $remarks = null, $data = [])
    { 
        try{ 
            OutgoingFinalizationHistory::create([
                'dv_request_id'      => $dvRequest->id,
                'verified_by'        => auth()->id(),
                'verification_step'  => $step,
                'status_before'      => $data['status_before'] ?? $dvRequest->getOriginal('status'),
                'status_after'       => $data['status_after'] ?? $dvRequest->status,
                'verification_data'  => $data,
                'remarks'            => $remarks,
                'verified_at'        => now(),
            ]);
                    
        } catch (\Exception $e) {
            \Log::error('Failed to save Incoming-GIP verification history', [
                'dv_request_id' => $dvRequest->id ?? null,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);

            // Optionally, rethrow to ensure rollback inside DB::transaction
            throw $e;
        }
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

    // ✅ ADD SENDPULSE EMAIL METHODS


    protected function sendRejectionEmail($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            if (!$focalPerson || empty($focalPerson->email)) {
                \Log::warning('Cannot send rejection email: focal person or email missing', [
                    'dv_request_id' => $dvRequest->id,
                    'focal_person_id' => $focalPerson->id ?? 'null'
                ]);
                return false;
            }

            $toEmail = $focalPerson->email;
            $toName = trim($focalPerson->fname . ' ' . $focalPerson->lname);
            
            \Log::info('Attempting to send rejection email via SendPulse', [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'dv_request_id' => $dvRequest->id
            ]);

            // Check if class exists before instantiating
            if (!class_exists(SendPulseServices::class)) {
                \Log::error('SendPulseServices class not found');
                return $this->sendFallbackEmail($toEmail, $toName, $dvRequest, 'Outgoing_rejected', $rejectionRemarks);
            }

            $sendPulseService = new SendPulseServices();
            $result = $sendPulseService->sendRejectionEmail($toEmail, $toName, $dvRequest, $rejectionRemarks);
            
            if ($result) {
                \Log::info('SendPulse rejection email sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'to_email' => $toEmail
                ]);
            } else {
                \Log::error('SendPulse failed to send rejection email', [
                    'dv_request_id' => $dvRequest->id,
                    'to_email' => $toEmail
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            \Log::error('SendPulse rejection email failed: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id,
                'exception' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    // Add fallback email method
    protected function sendFallbackEmail($toEmail, $toName, $dvRequest, $status, $remarks = null)
    {
        try {
            \Log::info('Using fallback SMTP email for status: ' . $status);
            
            $subject = "DV Request Status Updated - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            $htmlContent = view('emails.dv_status_updated', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'status' => $status,
                'remarks' => $remarks,
                'updatedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'updatedBy' => auth()->user()->name ?? 'Outgoing User'
            ])->render();

            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($toEmail, $toName, $subject, $htmlContent) {
                $message->to($toEmail, $toName)
                       ->subject($subject)
                       ->from(config('mail.from.address'), config('mail.from.name'))
                       ->html($htmlContent);
            });

            \Log::info('Fallback SMTP email sent successfully', ['to' => $toEmail, 'status' => $status]);
            return true;
        } catch (\Exception $e) {
            \Log::error('Fallback SMTP also failed: ' . $e->getMessage());
            return false;
        }
    }
}