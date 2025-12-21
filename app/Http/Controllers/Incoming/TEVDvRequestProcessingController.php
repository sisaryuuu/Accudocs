<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use App\Traits\TriggersDashboardUpdate; // ✅ Add trait
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\IncomingTEVVerificationHistory;
use App\Models\DocumentAgingAlert;
use App\Models\Notification;
use App\Services\PusherService;
use App\Services\SendPulseServices;
use App\Services\IprogSmsService;
use App\Models\TEVAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TEVDvRequestProcessingController extends Controller
{
      use TriggersDashboardUpdate; // ✅ This trait provides getDashboardData() and triggerDashboardUpdate()

      protected PusherService $pusher;
      protected IprogSmsService $smsService;
      

    public function __construct(PusherService $pusher, IprogSmsService $smsService)
    {
        $this->pusher = $pusher;
        $this->smsService =$smsService;
    }

 public function index(Request $request)
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $query = DvRequest::whereHas('program', fn($q) =>$q->where('prcode', 'TEV'))
                    ->whereIn('status', ['IMSD_verified', 'Incoming-TEV_received'])
                    ->where(function ($q) use ($currentUserId,$lockDuration){
                        $q->whereNull('locked_by')
                        ->orWhere('locked_by',$currentUserId)
                        ->orWhere('locked_at','<=',$lockDuration);
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
        return view('Incoming.TEV.index', compact('dvRequests'));
    }
    
 public function show($id)
{
    $dvRequest = DvRequest::with([
        'accountingUnit',
        'program',
        'focalPerson',
        'respocenter',
        'modeOfPayments',
        'paymentDetails',
        'tevAttachments',
        'logs'
    ])
        ->whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
        ->findOrFail($id);

    $lockDuration = now()->subMinutes(10);
    $currentUserId = auth()->id();

    if ($dvRequest->locked_by && $dvRequest->locked_by !== $currentUserId && $dvRequest->locked_at > $lockDuration) {
        return redirect()->route('Incoming.TEV.index')
            ->with('error', 'This DV Request is currently being processed by another user.');
    }

    // ✅ AUTO-SET DEFAULT MODALITY IF NOT SET
    if (!$dvRequest->tev_modality) {
        $dvRequest->update([
            'tev_modality' => 'Cash Advance for Local Travel', // Set default modality
            'locked_by' => $currentUserId,
            'locked_at' => now(),
        ]);
        $dvRequest->refresh();
    } else {
        if (!$dvRequest->locked_by || $dvRequest->locked_at <= $lockDuration) {
            $dvRequest->update([
                'locked_by' => $currentUserId,
                'locked_at' => now(),
            ]);
        }
    }

    // ✅ CONSISTENT VARIABLE NAME
    $tevSelectedModality = $dvRequest->tev_modality;

    Log::info('=== TEV SHOW METHOD DEBUG ===', [
        'dv_request_id' => $dvRequest->id,
        'tev_modality_from_db' => $dvRequest->getRawOriginal('tev_modality'),
        'tev_modality_selected' => $tevSelectedModality,
    ]);

    // ✅ CRITICAL FIX: Delete attachments that don't match current modality
    $deletedCount = TEVAttachment::where('dv_request_id', $dvRequest->id)
        ->where('modality', '!=', $tevSelectedModality)
        ->delete();

    if ($deletedCount > 0) {
        Log::info('Deleted attachments from wrong modality:', [
            'dv_request_id' => $dvRequest->id,
            'deleted_count' => $deletedCount,
            'kept_modality' => $tevSelectedModality,
        ]);
    }

    // Get or create attachments for CURRENT modality only
    $tevAttachments = $this->getOrCreateTevAttachmentsForDvRequest($dvRequest, $tevSelectedModality);

    Log::info('Show method - attachments after cleanup:', [
        'selected_modality' => $tevSelectedModality,
        'attachment_count' => $tevAttachments->count(),
        'attachment_ids' => $tevAttachments->pluck('id')->toArray(),
        'attachment_names' => $tevAttachments->pluck('name')->toArray(),
        'all_modalities' => $tevAttachments->pluck('modality')->unique()->toArray(),
    ]);

    // Check if master templates exist
    $masterCount = TEVAttachment::whereNull('dv_request_id')
        ->where('modality', $tevSelectedModality)
        ->count();

    Log::info('TEV Master templates check', [
        'modality' => $tevSelectedModality,
        'master_count' => $masterCount,
    ]);

    if ($tevAttachments->isEmpty()) {
        Log::error('NO TEV ATTACHMENTS FOUND AFTER CREATION!', [
            'dv_request_id' => $dvRequest->id,
            'modality' => $tevSelectedModality,
            'master_templates_exist' => $masterCount > 0,
        ]);
    }

    $lastLog = $dvRequest->logs()->latest()->first();
    $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

    return view('Incoming.TEV.verifytevdv', compact(
        'dvRequest',
        'lastStep',
        'tevAttachments',
        'tevSelectedModality'
    ));
}
public function changeModality(Request $request, $id)
{
    $request->validate([
        'tev_modality' => 'required|string|in:Cash Advance for Local Travel,Liquidation of Cash Advances',
    ]);

    $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
        ->findOrFail($id);

    $newModality = $request->tev_modality;
    $oldModality = $dvRequest->tev_modality;

    Log::info('Modality change requested', [
        'dv_request_id' => $dvRequest->id,
        'old_modality' => $oldModality,
        'new_modality' => $newModality,
    ]);

    // If modality is different, delete old attachments and create new ones
    if ($oldModality !== $newModality) {
        // Delete old modality attachments
        $deleted = TEVAttachment::where('dv_request_id', $dvRequest->id)
            ->where('modality', '!=', $newModality)
            ->delete();

        Log::info('Deleted old modality attachments', [
            'deleted_count' => $deleted,
            'old_modality' => $oldModality,
        ]);

        // Update DV request modality
        $dvRequest->update(['tev_modality' => $newModality]);

        // Create new attachments for the new modality
        $newAttachments = $this->getOrCreateTevAttachmentsForDvRequest($dvRequest, $newModality);

        Log::info('Created new modality attachments', [
            'new_modality' => $newModality,
            'created_count' => $newAttachments->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Modality changed successfully',
            'modality' => $newModality,
            'attachments_count' => $newAttachments->count(),
            'reload_required' => true, // Tell frontend to reload the page
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Modality unchanged',
        'modality' => $newModality,
        'reload_required' => false,
    ]);
}
protected function getOrCreateTevAttachmentsForDvRequest($dvRequest, $selectedModality)
{
    if (!$selectedModality) {
        return collect();
    }

    Log::info('getOrCreateTevAttachmentsForDvRequest called', [
        'dv_request_id' => $dvRequest->id,
        'selected_modality' => $selectedModality,
    ]);

    // ✅ FIRST: Check if attachments already exist for this DV request AND selected modality
    $existingAttachments = $dvRequest->tevAttachments()
        ->where('modality', $selectedModality) // ✅ MUST filter by modality
        ->orderBy('sort_order')
        ->get();

    if ($existingAttachments->isNotEmpty()) {
        Log::info('Found existing attachments for this modality', [
            'count' => $existingAttachments->count(),
            'modality' => $selectedModality,
            'attachment_names' => $existingAttachments->pluck('name')->toArray(),
            'first_few_modalities' => $existingAttachments->take(3)->pluck('modality')->toArray(),
        ]);
        
        // ✅ DOUBLE-CHECK: Ensure ALL returned attachments are for the selected modality
        $wrongModalityAttachments = $existingAttachments->where('modality', '!=', $selectedModality);
        if ($wrongModalityAttachments->isNotEmpty()) {
            Log::error('ERROR: Found attachments with wrong modality!', [
                'expected_modality' => $selectedModality,
                'wrong_attachments' => $wrongModalityAttachments->map(function($att) {
                    return ['id' => $att->id, 'name' => $att->name, 'modality' => $att->modality];
                })->toArray(),
            ]);
        }
        
        return $existingAttachments;
    }

    // ✅ If no attachments exist for this modality, create them from master templates
    $masterTemplates = TEVAttachment::whereNull('dv_request_id')
        ->where('modality', $selectedModality) // ✅ Only get templates for the selected modality
        ->orderBy('sort_order')
        ->get();

    if ($masterTemplates->isEmpty()) {
        Log::warning("No TEV master templates found for modality: {$selectedModality}");
        return collect();
    }

    Log::info('Master templates found', [
        'modality' => $selectedModality,
        'template_count' => $masterTemplates->count(),
        'template_names' => $masterTemplates->pluck('name')->toArray(),
    ]);

    // Create attachments for this DV request with N/A default
    $createdAttachments = [];
    foreach ($masterTemplates as $template) {
        $createdAttachments[] = TEVAttachment::create([
            'dv_request_id' => $dvRequest->id,
            'name' => $template->name,
            'modality' => $selectedModality, // ✅ Use the selected modality
            'is_present' => null,  // ✅ N/A by default
            'remarks' => null,     // ✅ Added remarks field
            'sort_order' => $template->sort_order,
        ]);
    }

    Log::info("Created " . count($createdAttachments) . " TEV attachments for DV Request {$dvRequest->id}, modality: {$selectedModality}");

    return collect($createdAttachments);
}

  public function receive(Request $request, $id)
    {
        try {
            $dvRequest = DvRequest::findOrFail($id);
            $statusBefore = $dvRequest->status;
            $receivedAt = now();
            $currentUserId = auth()->id();

            if ($dvRequest->status !== 'IMSD_verified') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been received.'
                ], 422);
            }

            // ✅ DEFINE VARIABLES FOR PUSHER
            $controlNumber = $dvRequest->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) : 'Unknown';
            $userName = auth()->user()->name ?? 'TEV User';

            $dvRequest->update([
                'status' => 'Incoming-TEV_received',
                'accountingunit_id' => $currentUserId,
                'received_by' => $currentUserId,
                'received_at' => $receivedAt,
                'locked_by' => $currentUserId,
                'locked_at' => $receivedAt,
            ]);

            // Log the action
            $this->logAction(
                $dvRequest->id,
                'Incoming',
                'Incoming-TEV_received',
                'Request officially received by Incoming-TEV'
            );

            $this->saveIncomingTEVVerificationHistory(
                $dvRequest,
                'receive',
                'Request officially received by Incoming-TEV',
                [
                    'status_before' => $statusBefore,
                    'status_after' => 'Incoming-TEV_received',
                    'received_by' => $currentUserId,
                    'received_at' => $receivedAt->toISOString(),
                ]
            );

            $focalId = $dvRequest->focalperson_id;
            Notification::create([
                'focalperson_id' => $focalId,
                'dv_request_id' => $dvRequest->id,
                'status' => 'Incoming-TEV_received',
                'action' => 'Received',
                'remarks' => 'Request officially received by Incoming-TEV',
            ]);

            $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                'dv_request_id' => $dvRequest->id,
                'status' => 'Incoming-TEV_received',
                'remarks' => 'Request officially received by Incoming-TEV',
                'timestamp' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            // ✅ ENHANCED: Broadcast to Incoming-TEV channel
            $this->pusher->trigger('dv-requests-incoming-tev', 'dv-request-received', [
                'dv_request_id' => $dvRequest->id,
                'control_number' => $controlNumber,
                'received_by' => $userName,
                'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'submitted_by' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                'action_performer_id' => $currentUserId,
                'received_by_user_id' => $currentUserId,
            ]);

            $this->triggerDashboardUpdate();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document has been received by Incoming-TEV.',
                    'data' => [
                        'id' => $dvRequest->id,
                        'status' => 'Incoming-TEV_received',
                        'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A')
                    ]
                ]);
            }

            return redirect()->route('Incoming.TEV.index')
                ->with('success', 'Document has been received by Incoming-TEV.');

        } catch (\Exception $e) {
            Log::error('Error receiving DV request: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing the request.'
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while processing the request.');
        }
    }

 public function fetchtevRecent()
 
    {
          $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);
        $recentDvRequests = DvRequest::with('focalPerson')
        ->whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
        ->whereIn('status', ['IMSD_verified', 'Incoming-TEV_received'])
        ->whereNotNull('focalperson_id')
         ->where(function ($q) use ($currentUserId,$lockDuration){
        $q->whereNull('locked_by')
        ->orWhere('locked_by',$currentUserId)
        ->orWhere('locked_at','<=',$lockDuration);
        })
        ->latest()
        ->take(5)
        ->get();

        return response()->json($recentDvRequests);
    }

    public function fetchAll(Request $request)
{
    $currentUserId = auth()->id();
    $lockDuration = now()->subMinutes(10);

    $query = DvRequest::with(['focalPerson', 'program', 'respocenter'])
        ->whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
        ->whereIn('status', ['IMSD_verified', 'Incoming-TEV_received', 'Incoming-TEV_in_process'])
        ->where(function($q) use ($currentUserId, $lockDuration) {
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
              ->orWhere('status', 'like', "%{$search}%")
              ->orWhere('particulars', 'like', "%{$search}%")
              ->orWhere('accounting_control_number', 'like', "%{$search}%");
        });
    }

    // Get all DV requests with created_at for age calculation
    $dvRequests = $query->select('dv_requests.*')
                        ->addSelect(DB::raw('TIMESTAMPDIFF(DAY, dv_requests.created_at, NOW()) as days_old'))
                        ->get();
    
    // Calculate age for each request and add priority score
    $dvRequests = $dvRequests->map(function ($request) {
        $daysOld = now()->diffInDays($request->created_at);
        
        // Priority scoring system:
        // - Critical (10+ days): 1000 points
        // - High (7-9 days): 500 points
        // - Medium (3-6 days): 200 points
        // - Recent (0-2 days): 0 points
        
        $priorityScore = 0;
        
        if ($daysOld >= 10) {
            $priorityScore += 1000; // Critical priority
        } elseif ($daysOld >= 7) {
            $priorityScore += 500; // High priority
        } elseif ($daysOld >= 3) {
            $priorityScore += 200; // Medium priority
        }
        
        // Higher priority for 'IMSD_verified' vs 'Incoming-TEV_received'
        if ($request->status === 'IMSD_verified') {
            $priorityScore += 50;
        } elseif ($request->status === 'Incoming-TEV_received') {
            $priorityScore += 25;
        }
        // 'Incoming-TEV_in_process' gets 0 additional points
        
        // Add small random factor to prevent exact ties
        $priorityScore += rand(0, 10);
        
        $request->days_old = $daysOld;
        $request->priority_score = $priorityScore;
        $request->urgency = $this->getUrgencyLevel($daysOld);
        
        return $request;
    });
    
    // Sort by priority score (descending) so highest priority comes first
    $dvRequests = $dvRequests->sortByDesc('priority_score')->values();

    $result = $dvRequests->map(function ($request) {
        return [
            'id'           => $request->id,
            'accounting_control_number' => $request->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT): '—',
            'submitted_by' => trim(($request->payee_fname ?? $request->focalPerson->fname ?? '') . ' ' . ($request->payee_lname ?? $request->focalPerson->lname ?? '')),
            'program'      => $request->program->prcode ?? 'N/A',
            'respocenter'  => trim(($request->respocenter->rccode ?? '') . ' - ' . ($request->respocenter->description ?? '')) ?? 'N/A',
            'particulars'  => $request->particulars,
            'amount'       => number_format($request->amount, 2),
            'status'       => $request->status,
            'date'         => $request->created_at->timezone('Asia/Manila')->format('M d, Y h:i A'),
            'created_at_timestamp' => $request->created_at->toISOString(),
            'days_old'     => $request->days_old,
            'urgency'      => $request->urgency,
            'priority_score' => $request->priority_score,
        ];
    });

    return response()->json($result);
}

/**
 * Get urgency level based on whole number of days
 */
protected function getUrgencyLevel($daysOld)
{
    if ($daysOld >= 10) {
        return 'critical';
    } elseif ($daysOld >= 7) {
        return 'high';
    } elseif ($daysOld >= 3) {
        return 'medium';
    } else {
        return 'low';
    }
}

    public function fetch($id)
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);
        
        $dvRequest = DvRequest::with(['focalPerson', 'program','respocenter','accountingUnit'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
             ->where(function($q) use ($currentUserId,$lockDuration){
                $q->whereNull('locked_by')
                ->orWhere('locked_by',$currentUserId)
                ->orwhere('locked_at','<=',$lockDuration);
            })
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
    // Validate the request
    $request->validate([
        'remarks' => 'nullable|string|max:1000',
        'tev_modality' => 'required|string',
        'tev_attachment_status_value' => 'nullable|array',
        'tev_attachment_status_value.*' => 'nullable|in:0,1,null',
        'tev_attachment_remarks' => 'nullable|array',
        'tev_attachment_remarks.*' => 'nullable|string|max:500',
    ]);

    // Find the DV request
    $dvRequest = DvRequest::with('tevAttachments')
        ->whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
        ->findOrFail($id);

    Log::info('===== TEV VERIFY DEBUG START =====');
    Log::info('DV Request ID: ' . $id);
    Log::info('Modality from form: ' . $request->tev_modality);
    Log::info('Full request data:', $request->all());
    
    // Get all form data
    $attachmentStatusValues = $request->input('tev_attachment_status_value', []);
    $attachmentRemarks = $request->input('tev_attachment_remarks', []);
    
    Log::info('Form data received:', [
        'status_values' => $attachmentStatusValues,
        'remarks' => $attachmentRemarks,
    ]);

    // ✅ UPDATE MODALITY IF CHANGED
    if ($request->has('tev_modality') && $request->tev_modality !== $dvRequest->tev_modality) {
        Log::info('Updating modality from ' . $dvRequest->tev_modality . ' to ' . $request->tev_modality);
        
        // Delete old modality attachments
        TEVAttachment::where('dv_request_id', $dvRequest->id)
            ->where('modality', '!=', $request->tev_modality)
            ->delete();
        
        $dvRequest->update(['tev_modality' => $request->tev_modality]);
    }

    $statusBefore = $dvRequest->status;
    $verifiedAt = now();
    $currentUserId = auth()->id();
    $presentCount = 0;
    $notPresentCount = 0;
    $naCount = 0;
    $attachmentData = [];

    // ✅ CRITICAL FIX: Process TEV attachments directly from form data
    if ($dvRequest->tev_modality && !empty($attachmentStatusValues)) {
        // Get current modality attachments from database
        $currentModalityAttachments = $this->getOrCreateTevAttachmentsForDvRequest($dvRequest, $dvRequest->tev_modality);
        
        Log::info('Processing ' . $currentModalityAttachments->count() . ' attachments for modality: ' . $dvRequest->tev_modality);
        
        // ✅ NEW APPROACH: Process each form submission directly
        foreach ($attachmentStatusValues as $formKey => $statusValue) {
            Log::info('Processing form entry:', [
                'form_key' => $formKey,
                'status_value' => $statusValue,
                'remarks' => $attachmentRemarks[$formKey] ?? 'N/A',
            ]);
            
            // Find the attachment by ID
            $attachment = null;
            
            // Check if formKey is a direct database ID
            if (is_numeric($formKey)) {
                $attachment = $currentModalityAttachments->where('id', (int)$formKey)->first();
                
                if (!$attachment) {
                    Log::warning('Attachment ID not found in current modality:', [
                        'id' => $formKey,
                        'modality' => $dvRequest->tev_modality
                    ]);
                    continue;
                }
            } else {
                // Try to match by name pattern in formKey
                foreach ($currentModalityAttachments as $att) {
                    $cleanName = strtolower(preg_replace('/[^a-z0-9]/', '_', $att->name));
                    if (str_contains(strtolower($formKey), $cleanName)) {
                        $attachment = $att;
                        break;
                    }
                }
                
                if (!$attachment) {
                    Log::warning('Could not match form key to attachment:', ['form_key' => $formKey]);
                    continue;
                }
            }
            
            // ✅ Process the attachment with the form values
            $this->processSingleTevAttachment(
                $attachment,
                $statusValue,
                $attachmentRemarks[$formKey] ?? null,
                $presentCount,
                $notPresentCount,
                $naCount,
                $attachmentData
            );
        }
        
        // ✅ Handle attachments not in form (default to N/A)
        foreach ($currentModalityAttachments as $attachment) {
            $found = false;
            
            foreach (array_keys($attachmentStatusValues) as $formKey) {
                if (is_numeric($formKey) && (int)$formKey === $attachment->id) {
                    $found = true;
                    break;
                }
                
                $cleanName = strtolower(preg_replace('/[^a-z0-9]/', '_', $attachment->name));
                if (str_contains(strtolower($formKey), $cleanName)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                Log::info('Attachment not in form, setting to N/A:', [
                    'id' => $attachment->id,
                    'name' => $attachment->name
                ]);
                
                $this->processSingleTevAttachment(
                    $attachment,
                    null, // N/A
                    'Not applicable',
                    $presentCount,
                    $notPresentCount,
                    $naCount,
                    $attachmentData
                );
            }
        }
    } else {
        Log::warning('No attachment status values received in form!');
    }

    Log::info('Final attachment counts:', [
        'present' => $presentCount,
        'not_present' => $notPresentCount,
        'na' => $naCount,
        'total_processed' => $presentCount + $notPresentCount + $naCount,
    ]);

    $totalCount = $presentCount + $notPresentCount + $naCount;
    $attachmentSummary = "{$presentCount} present, {$notPresentCount} not present, {$naCount} N/A (Total: {$totalCount})";
    
    $baseRemarks = $request->remarks ?? 'TEV DV Request approved and forwarded to Budget Officer.';
    $remarksWithAttachments = $baseRemarks . " [Attachments: {$attachmentSummary}]";

    // Update DV request status
    $dvRequest->update([
        'status' => 'Incoming-TEV_verified',
        'locked_by' => null,
        'locked_at' => null,
    ]);

    // Pusher notification to Budget Officer
    $this->pusher->trigger('dv-requests-budget-officer', 'dv-request-verified', [
        'dv_request_id' => $dvRequest->id,
        'submitted_by' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
        'program' => $dvRequest->program->prcode ?? 'N/A',
        'status' => 'Incoming-TEV_verified',
        'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        'attachments_summary' => $attachmentSummary,
    ]);

    $focalId = $dvRequest->focalperson_id;
    $status = 'Incoming-TEV_verified';
    $action = 'Incoming-TEV_verified';

    // Notification to focal person
    Notification::create([
        'focalperson_id' => $focalId,
        'dv_request_id' => $dvRequest->id,
        'status' => $status,
        'action' => $action,
        'remarks' => $request->remarks ?? 'TEV DV Request verified and forwarded to Budget Officer.',
    ]);

    if ($dvRequest->focalperson_id) {
        $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'status-updated', [
            'dv_request_id' => $dvRequest->id,
            'status' => $status,
            'remarks' => 'Request approved by Incoming for TEV processing',
            'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
    }

    // Pusher to Incoming-TEV dashboard
    $this->pusher->trigger('dv-requests-incoming-tev', 'dv-request-verified', [
        'dv_request_id' => $dvRequest->id,
        'status' => $status,
        'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        'action_performer_id' => $currentUserId,
        'verified_by_user_id' => $currentUserId,
        'attachments_summary' => $attachmentSummary,
    ]);

    // Save verification history
    $this->saveIncomingTEVVerificationHistory(
        $dvRequest,
        'verify',
        $remarksWithAttachments,
        [
            'action' => 'verified',
            'status_before' => $statusBefore,
            'status_after' => 'Incoming-TEV_verified',
            'attachments_checked' => $attachmentData,
            'attachments_summary' => $attachmentSummary,
            'present_count' => $presentCount,
            'not_present_count' => $notPresentCount,
            'na_count' => $naCount,
            'total_count' => $totalCount,
            'verified_by' => $currentUserId,
            'verified_at' => $verifiedAt->toISOString()
        ]
    );

    // Log action
    $this->logAction(
        $dvRequest->id,
        'Incoming',
        'Incoming-TEV_verified',
        $remarksWithAttachments
    );

    $this->triggerDashboardUpdate();

    Log::info('TEV Verify - Complete', [
        'dv_request_id' => $dvRequest->id,
        'status' => 'Incoming-TEV_verified',
        'attachment_details' => $attachmentData,
    ]);

    return redirect()->route('Incoming.TEV.index')
        ->with('success', 'TEV DV Request Approved.')
        ->with('attachments_summary', $attachmentSummary);
}
private function processSingleTevAttachment(
    $attachment, 
    $status, 
    $remarks,
    &$presentCount,
    &$notPresentCount,
    &$naCount,
    &$attachmentData
) {
    // ✅ FIXED: Proper status conversion
    $isPresent = null;
    
    // Debug the incoming status
    Log::info('Processing attachment - raw input:', [
        'attachment_id' => $attachment->id,
        'attachment_name' => $attachment->name,
        'raw_status' => $status,
        'status_type' => gettype($status),
        'status_is_string_1' => $status === '1',
        'status_is_int_1' => $status === 1,
        'status_is_string_0' => $status === '0',
        'status_is_int_0' => $status === 0,
    ]);
    
    // ✅ CRITICAL FIX: Proper type checking with strict comparison
    if ($status === '1' || $status === 1 || $status === true) {
        $isPresent = 1; // Database stores as integer
        $presentCount++;
        Log::info('✅ Attachment marked as PRESENT (1)');
    } elseif ($status === '0' || $status === 0 || $status === false) {
        $isPresent = 0; // Database stores as integer
        $notPresentCount++;
        Log::info('✅ Attachment marked as NOT PRESENT (0)');
    } else {
        // Anything else (null, 'null', empty string, etc.) = N/A
        $isPresent = null;
        $naCount++;
        Log::info('✅ Attachment marked as N/A (null)');
    }

    // ✅ Set default remarks based on status if not provided
    if ($remarks === null || trim($remarks) === '') {
        if ($isPresent === 1) {
            $remarks = 'The attachment is present';
        } elseif ($isPresent === null) {
            $remarks = 'Not applicable';
        } elseif ($isPresent === 0) {
            $remarks = 'Not present - details not specified';
        }
    }

    // ✅ UPDATE ATTACHMENT IN DATABASE
    $updateData = [
        'is_present' => $isPresent,
        'remarks' => $remarks,
    ];

    Log::info('Updating attachment with final data:', [
        'attachment_id' => $attachment->id,
        'attachment_name' => $attachment->name,
        'is_present_value' => $isPresent,
        'is_present_type' => gettype($isPresent),
        'remarks' => $remarks,
    ]);

    $updateResult = $attachment->update($updateData);
    
    // Verify the update worked
    $freshAttachment = $attachment->fresh();
    Log::info('Attachment updated - verification:', [
        'update_success' => $updateResult,
        'db_is_present' => $freshAttachment->is_present,
        'db_is_present_type' => gettype($freshAttachment->is_present),
        'db_remarks' => $freshAttachment->remarks,
    ]);

    // Store for history
    $attachmentData[] = [
        'id' => $attachment->id,
        'name' => $attachment->name,
        'is_present' => $isPresent,
        'status_label' => $isPresent === 1 ? 'Present' : ($isPresent === 0 ? 'Not Present' : 'N/A'),
        'remarks' => $remarks,
        'modality' => $attachment->modality,
        'type' => 'tev'
    ];
}
public function reject(Request $request, $id)
    {
        try {
            // Validate input
            $request->validate([
                'remarks' => 'required|string|max:1000|min:10',
            ]);

            // Find the DV request
            $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TEV'))
                                ->findOrFail($id);

            $statusBefore = $dvRequest->status;
            $currentUserId = auth()->id();
            
            // ✅ Only allow rejection if status is currently in Incoming-TEV stage
            if ($dvRequest->status !== 'Incoming-TEV_received') {
                return response()->json([
                    'success' => false,
                    'message' => 'This DV request cannot be rejected at its current stage.'
                ], 422);
            }

            $rejectedAt = now();
            $focalId = $dvRequest->focalperson_id;
            $remarks = trim($request->remarks);

            // ✅ PHASE 1: FAST DATABASE OPERATIONS ONLY
            DB::transaction(function () use ($dvRequest, $statusBefore, $rejectedAt, $remarks, $focalId, $currentUserId) {
                // ✅ Update DV request
                $dvRequest->update([
                    'status'     => 'Incoming-TEV_rejected',
                    'locked_by'  => null,
                    'locked_at'  => null,
                ]);

                $this->saveIncomingTEVVerificationHistory(
                    $dvRequest,
                    'reject',
                    $remarks,
                    [
                        'status_before'   => $statusBefore,
                        'status_after'    => 'Incoming-TEV_rejected',
                        'action'          => 'rejected',
                        'rejected_by'     => $currentUserId,
                        'rejected_at'     => $rejectedAt->toISOString(),
                        'rejection_reason'=> $remarks,
                    ]
                );

                // ✅ Log rejection
                $this->logAction(
                    $dvRequest->id,
                    'Incoming-TEV',
                    'Incoming-TEV_rejected',
                    "Rejected by Incoming-TEV: {$remarks}"
                );

                // ✅ Notify focal person
                Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => 'Incoming-TEV_rejected',
                    'action'         => 'Rejected',
                    'remarks'        => $remarks,
                ]);

                // ✅ Real-time push to focal person
                if (isset($this->pusher)) {
                    $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => 'Incoming-TEV_rejected',
                        'remarks'       => $remarks,
                        'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);

                    // ✅ Real-time push to Incoming-TEV dashboard
                    $this->pusher->trigger('dv-requests-incoming-tev', 'dv-request-rejected', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => 'Incoming-TEV_rejected',
                        'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'action_performer_id' => $currentUserId,
                        'rejected_by_user_id' => $currentUserId,
                    ]);
                }
            });

            // ✅ PHASE 2: SEND NOTIFICATIONS AFTER DB TRANSACTION (NON-BLOCKING)
            $smsSent = $this->sendRejectionSMS($dvRequest, $remarks);
            $emailSent = $this->sendRejectionEmail($dvRequest, $remarks);

            $this->triggerDashboardUpdate();

            // ✅ IMMEDIATE RESPONSE TO USER
            $responseMessage = 'DV request has been rejected successfully.';
            if ($smsSent) {
                $responseMessage .= ' SMS notification sent.';
            } else {
                Log::warning('SMS failed but request was rejected successfully');
            }

            return response()->json([
                'success' => true,
                'message' => $responseMessage,
                'data' => [
                    'id'         => $dvRequest->id,
                    'status'     => 'Incoming-TEV_rejected',
                    'remarks'    => $remarks,
                    'sms_sent'   => $smsSent,
                    'email_sent' => $emailSent
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('DV Request not found: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'DV request not found.'
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ' . json_encode($e->errors()));
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()['remarks'] ?? ['Invalid input'])
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error rejecting TEV DV request: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the DV request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ SEND REJECTION SMS - For TEV rejections
     */
    protected function sendRejectionSMS($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            // Quick validation
            if (!$focalPerson || empty($focalPerson->contactnumber)) {
                Log::warning('Cannot send rejection SMS: focal person or contact number missing', [
                    'dv_request_id' => $dvRequest->id
                ]);
                return false;
            }

            $phoneNumber = $focalPerson->contactnumber;
            
            // ⚡ SHORT MESSAGE - Faster transmission
            $userSequence = $dvRequest->user_sequence ?? 'N/A';
            $truncatedRemarks = strlen($rejectionRemarks) > 80 
                ? substr($rejectionRemarks, 0, 77) . '...' 
                : $rejectionRemarks;

            $message = "Hi {$focalPerson->fname}, DV #{$userSequence} rejected: {$truncatedRemarks} - DOLE XI";

            Log::info('Sending TEV rejection SMS', [
                'phone' => $phoneNumber,
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $userSequence
            ]);

            // ⚡ USE QUICK SMS METHOD - 5 second timeout
            $result = $this->smsService->sendQuickSMS($phoneNumber, $message);
            
            if ($result['success']) {
                Log::info('TEV rejection SMS sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'phone' => $phoneNumber
                ]);
                return true;
            } else {
                Log::warning('TEV rejection SMS failed - continuing', [
                    'dv_request_id' => $dvRequest->id,
                    'phone' => $phoneNumber
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            // ⚡ SILENT FAILURE - Don't affect user experience
            Log::warning('TEV SMS rejection failed silently: ' . $e->getMessage());
            return false;
        }
    }

     /**
     * ✅ SEND REJECTION EMAIL - SendPulse with SMTP fallback
     */
    protected function sendRejectionEmail($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            if (!$focalPerson || empty($focalPerson->email)) {
                Log::warning('Cannot send rejection email: focal person or email missing', [
                    'dv_request_id' => $dvRequest->id,
                    'focal_person_id' => $focalPerson->id ?? 'null'
                ]);
                return false;
            }

            $toEmail = $focalPerson->email;
            $toName = trim($focalPerson->fname . ' ' . $focalPerson->lname);
            
            Log::info('Attempting to send rejection email via SendPulse', [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'dv_request_id' => $dvRequest->id
            ]);

            // Try SendPulse first
            if (class_exists(SendPulseServices::class)) {
                $sendPulseService = new SendPulseServices();
                $result = $sendPulseService->sendRejectionEmail($toEmail, $toName, $dvRequest, $rejectionRemarks);
                
                if ($result) {
                    Log::info('SendPulse rejection email sent successfully', [
                        'dv_request_id' => $dvRequest->id,
                        'to_email' => $toEmail
                    ]);
                    return true;
                }
            }

            // Fallback to SMTP
            return $this->sendFallbackEmail($toEmail, $toName, $dvRequest, $rejectionRemarks);
            
        } catch (\Exception $e) {
            Log::error('SendPulse rejection email failed: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id,
                'exception' => $e->getTraceAsString()
            ]);
            
            // Try fallback
            return $this->sendFallbackEmail($toEmail ?? null, $toName ?? null, $dvRequest, $rejectionRemarks);
        }
    }

    /**
     * ✅ FALLBACK EMAIL - Using Laravel SMTP
     */
    protected function sendFallbackEmail($toEmail, $toName, $dvRequest, $rejectionRemarks)
    {
        try {
            if (!$toEmail) {
                return false;
            }

            Log::info('Using fallback SMTP email');
            
            $subject = "DV Request Rejected - Incoming-TEV - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            $htmlContent = view('emails.dv_rejected', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'rejectionRemarks' => $rejectionRemarks,
                'rejectedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'rejectedBy' => auth()->user()->name ?? 'Incoming-TEV User',
                'department' => 'Incoming-TEV'
            ])->render();

            Mail::send([], [], function ($message) use ($toEmail, $toName, $subject, $htmlContent) {
                $message->to($toEmail, $toName)
                       ->subject($subject)
                       ->from(config('mail.from.address'), config('mail.from.name'))
                       ->html($htmlContent);
            });

            Log::info('Fallback SMTP email sent successfully', ['to' => $toEmail]);
            return true;
            
        } catch (\Exception $e) {
            Log::error('Fallback SMTP also failed: ' . $e->getMessage());
            return false;
        }
    }



    public function redirectToFirstPending()
{
    $currentUserId = auth()->id();
    $lockDuration = now()->subMinutes(10);

    // Get all pending TEV requests
    $pendingRequests = DvRequest::whereHas('program', fn($q) => 
                                $q->where('prcode', 'TEV')) 
                            ->whereIn('status', ['IMSD_verified', 'Incoming-TEV_received'])
                            ->orderBy('created_at', 'asc')
                            ->get();

    if ($pendingRequests->isEmpty()) {
        return redirect()->route('Incoming.TEV.index')
                         ->with('info', 'No TEV DV requests pending verification.');
    }

    // Check for one you can work on (unlocked or locked by you, or expired lock)
    $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
        return !$req->locked_by 
            || $req->locked_by == $currentUserId 
            || $req->locked_at <= $lockDuration;
    });

    if ($available) {
        return redirect()->route('Incoming.TEV.dvrequests.withid', $available->id);
    }

    // If all are locked by others
    return redirect()->route('Incoming.TEV.index')
                     ->with('info', 'All pending TEV DV requests are currently locked by other users.');
}

      public function releaseLock($id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->locked_by === auth()->id()) {
            $dvRequest->update([
                'locked_by' => null,
                'locked_at' => null,
                'status'    => 'IMSD_verified', 
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
            $query = IncomingTEVVerificationHistory::with([
                'dvRequest.program:id,prcode',
                'dvRequest.focalPerson:id,fname,lname',
                'dvRequest.respocenter:id,rccode,description'
            ]);
        
            // Search by Control Number
            if ($request->has('search') && $request->search) {
                $query->whereHas('dvRequest', function($q) use ($request) {
                    $q->where('accounting_control_number', 'like', '%' . $request->search . '%')
                      ->orWhere('dv_number', 'like', '%' . $request->search . '%');
                });
            }
        
            // Filter by Program
            if ($request->has('program') && $request->program) {
                $query->whereHas('dvRequest.program', function($q) use ($request) {
                    $q->where('prcode', $request->program);
                });
            }
        
            // Filter by Modality
            if ($request->has('modality') && $request->modality) {
                $query->whereHas('dvRequest', function($q) use ($request) {
                    $q->where('tev_modality', $request->modality);
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
        
            return view('Incoming.TEV.loghistory', compact('verificationHistory'));
        }

    protected function saveIncomingTEVVerificationHistory($dvRequest, $step, $remarks = null, $data = [])
    {
            try  {
                IncomingTEVVerificationHistory::create([
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
                \Log::error('Failed to save Incoming-TEV verification history', [
                    'dv_request_id' => $dvRequest->id ?? null,
                    'step' => $step,
                    'error' => $e->getMessage(),
                ]);

                // Optionally, rethrow to ensure rollback inside DB::transaction
                throw $e;
            }
        }
    protected function logAction($dvRequestId, $department, $action, $remarks = null)
        {
            try {
                $logData = [
                    'dv_request_id' => $dvRequestId,
                    'action'        => $action,
                    'remarks'       => $remarks,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                // Set the correct user based on who is performing the action
                if (auth()->guard('web')->check()) {
                    // Accounting/Admin user - set accountingunit_id
                    $logData['accountingunit_id'] = auth()->id();
                    // Don't set focalperson_id for accounting users
                } elseif (auth()->guard('focal')->check()) {
                    // Focal person - set focalperson_id
                    $logData['focalperson_id'] = auth()->id();
                } else {
                    // System or external actor
                    $logData['external_actor'] = 'System';
                }

                DvRequestLog::create($logData);

            } catch (\Exception $e) {
                \Log::error('Failed to log action: ' . $e->getMessage(), [
                    'dv_request_id' => $dvRequestId,
                    'action' => $action,
                ]);
                // Don't throw error - just log and continue
            }
        }
}