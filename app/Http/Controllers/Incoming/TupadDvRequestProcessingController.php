<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use App\Traits\TriggersDashboardUpdate;
use App\Traits\HandlesDepartmentTransitions; // ⭐ ADD THIS
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\DvRequestHistory; // ⭐ ADD THIS
use App\Models\IncomingTUPADVerificationHistory;
use App\Models\Notification;
use App\Models\TUPADAttachment;
use App\Services\PusherService;
use App\Services\SendPulseServices;
use App\Services\IprogSmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TupadDvRequestProcessingController extends Controller
{
    use TriggersDashboardUpdate, HandlesDepartmentTransitions; // ⭐ ADD THE TRAIT

    protected PusherService $pusher;
    protected IprogSmsService $smsService;

    public function __construct(PusherService $pusher, IprogSmsService $smsService)
    {
        $this->pusher = $pusher;
        $this->smsService = $smsService;
    }

    public function index(Request $request)
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        $query = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
            ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_received'])
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

        $dvRequests = $query->orderBy('created_at', 'desc')->paginate(10);
        return view('Incoming.TUPAD.index', compact('dvRequests'));
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
            'tupadAttachments',
            'logs'
        ])
            ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
            ->findOrFail($id);

        $lockDuration = now()->subMinutes(10);
        $currentUserId = auth()->id();

        if ($dvRequest->locked_by && $dvRequest->locked_by !== $currentUserId && $dvRequest->locked_at > $lockDuration) {
            return redirect()->route('Incoming.TUPAD.index')
                ->with('error', 'This DV Request is currently being processed by another user.');
        }

        // ✅ AUTO-SET DEFAULT MODALITY IF NOT SET
        if (!$dvRequest->tupad_modality) {
            $dvRequest->update([
                'tupad_modality' => 'rpmt',
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
        $tupadSelectedModality = $dvRequest->tupad_modality;

        Log::info('=== TUPAD SHOW METHOD DEBUG ===', [
            'dv_request_id' => $dvRequest->id,
            'tupad_modality_from_db' => $dvRequest->getRawOriginal('tupad_modality'),
            'tupad_modality_selected' => $tupadSelectedModality,
        ]);

        // ✅ CRITICAL FIX: Delete attachments that don't match current modality
        $deletedCount = TUPADAttachment::where('dv_request_id', $dvRequest->id)
            ->where('modality', '!=', $tupadSelectedModality)
            ->delete();

        if ($deletedCount > 0) {
            Log::info('Deleted attachments from wrong modality:', [
                'dv_request_id' => $dvRequest->id,
                'deleted_count' => $deletedCount,
                'kept_modality' => $tupadSelectedModality,
            ]);
        }

        // Get or create attachments for CURRENT modality only
        $tupadAttachments = $this->getOrCreateTupadAttachmentsForDvRequest($dvRequest, $tupadSelectedModality);

        Log::info('Show method - attachments after cleanup:', [
            'selected_modality' => $tupadSelectedModality,
            'attachment_count' => $tupadAttachments->count(),
            'attachment_ids' => $tupadAttachments->pluck('id')->toArray(),
            'attachment_names' => $tupadAttachments->pluck('name')->toArray(),
            'all_modalities' => $tupadAttachments->pluck('modality')->unique()->toArray(),
        ]);

        // Check if master templates exist
        $masterCount = TUPADAttachment::whereNull('dv_request_id')
            ->where('modality', $tupadSelectedModality)
            ->count();

        Log::info('TUPAD Master templates check', [
            'modality' => $tupadSelectedModality,
            'master_count' => $masterCount,
        ]);

        if ($tupadAttachments->isEmpty()) {
            Log::error('NO TUPAD ATTACHMENTS FOUND AFTER CREATION!', [
                'dv_request_id' => $dvRequest->id,
                'modality' => $tupadSelectedModality,
                'master_templates_exist' => $masterCount > 0,
            ]);
        }

        $lastLog = $dvRequest->logs()->latest()->first();
        $lastStep = $lastLog ? $lastLog->action : $dvRequest->status;

        return view('Incoming.TUPAD.verifytupaddv', compact(
            'dvRequest',
            'lastStep',
            'tupadAttachments',
            'tupadSelectedModality'
        ));
    }

    public function changeModality(Request $request, $id)
    {
        $request->validate([
            'tupad_modality' => 'required|string|in:rpmt,lgu,ngo_po,money_remittance,individual_beneficiaries,group_beneficiaries',
        ]);

        $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
            ->findOrFail($id);

        $newModality = $request->tupad_modality;
        $oldModality = $dvRequest->tupad_modality;

        Log::info('TUPAD Modality change requested', [
            'dv_request_id' => $dvRequest->id,
            'old_modality' => $oldModality,
            'new_modality' => $newModality,
        ]);

        // If modality is different, delete old attachments and create new ones
        if ($oldModality !== $newModality) {
            // Delete old modality attachments
            $deleted = TUPADAttachment::where('dv_request_id', $dvRequest->id)
                ->where('modality', '!=', $newModality)
                ->delete();

            Log::info('Deleted old TUPAD modality attachments', [
                'deleted_count' => $deleted,
                'old_modality' => $oldModality,
            ]);

            // Update DV request modality
            $dvRequest->update(['tupad_modality' => $newModality]);

            // Create new attachments for the new modality
            $newAttachments = $this->getOrCreateTupadAttachmentsForDvRequest($dvRequest, $newModality);

            Log::info('Created new TUPAD modality attachments', [
                'new_modality' => $newModality,
                'created_count' => $newAttachments->count(),
                'attachment_names' => $newAttachments->pluck('name')->toArray(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'TUPAD modality changed successfully',
                'modality' => $newModality,
                'attachments_count' => $newAttachments->count(),
                'reload_required' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Modality unchanged',
            'modality' => $newModality,
            'reload_required' => false,
        ]);
    }

    protected function getOrCreateTupadAttachmentsForDvRequest($dvRequest, $selectedModality)
    {
        if (!$selectedModality) {
            return collect();
        }

        Log::info('getOrCreateTupadAttachmentsForDvRequest called', [
            'dv_request_id' => $dvRequest->id,
            'selected_modality' => $selectedModality,
        ]);

        // ✅ FIRST: Check if attachments already exist for this DV request AND selected modality
        $existingAttachments = $dvRequest->tupadAttachments()
            ->where('modality', $selectedModality)
            ->orderBy('sort_order')
            ->get();

        if ($existingAttachments->isNotEmpty()) {
            Log::info('Found existing attachments for this modality', [
                'count' => $existingAttachments->count(),
                'modality' => $selectedModality,
                'attachment_names' => $existingAttachments->pluck('name')->toArray(),
            ]);
            return $existingAttachments;
        }

        // ✅ If no attachments exist for this modality, create them from master templates
        $masterTemplates = TUPADAttachment::whereNull('dv_request_id')
            ->where('modality', $selectedModality)
            ->orderBy('sort_order')
            ->get();

        if ($masterTemplates->isEmpty()) {
            Log::warning("No TUPAD master templates found for modality: {$selectedModality}");
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
            $createdAttachments[] = TUPADAttachment::create([
                'dv_request_id' => $dvRequest->id,
                'name' => $template->name,
                'modality' => $selectedModality,
                'is_present' => null,
                'remarks' => null,
                'sort_order' => $template->sort_order,
                'is_required' => $template->is_required ?? false,
            ]);
        }

        Log::info("Created " . count($createdAttachments) . " TUPAD attachments for DV Request {$dvRequest->id}, modality: {$selectedModality}");

        return collect($createdAttachments);
    }

    public function receive(Request $request, $id)
    {
        try {
            $dvRequest = DvRequest::findOrFail($id);
            $statusBefore = $dvRequest->status;
            $receivedAt = now();
            $currentUserId = auth()->id();
            $newStatus = 'Incoming-TUPAD_received';

            // Validation checks
            if ($dvRequest->status === 'Incoming-TUPAD_received') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This request has already been received by Incoming-TUPAD.'
                    ], 422);
                }
                return redirect()->back()->with('error', 'This request has already been received by Incoming-TUPAD.');
            }

            if ($dvRequest->status !== 'IMSD_verified') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This request cannot be received. Current status: ' . $statusBefore
                    ], 422);
                }
                return redirect()->back()->with('error', 'This request cannot be received. Current status: ' . $statusBefore);
            }

            DB::transaction(function () use ($dvRequest, $statusBefore, $receivedAt, $currentUserId, $newStatus) {
                // ⭐ UPDATED: Use trait method for timestamp updates
                $this->updateDepartmentTimestamp($dvRequest, $newStatus);
                
                $dvRequest->update([
                    'status'           => $newStatus,
                    'accountingunit_id'=> $currentUserId,
                    'received_by'      => $currentUserId,
                    'received_at'      => $receivedAt,
                    'locked_by'        => $currentUserId,
                    'locked_at'        => $receivedAt,
                ]);
                
                // Initialize TUPAD attachments based on modality
                $selectedModality = $dvRequest->tupad_modality ?? 'rpmt';
                $this->getOrCreateTupadAttachmentsForDvRequest($dvRequest, $selectedModality);
                
                $this->saveIncomingTUPADVerificationHistory(
                    $dvRequest,
                    'receive',
                    'Request officially received by Incoming-TUPAD',
                    [
                        'status_before' => $statusBefore,
                        'status_after' => $newStatus,
                        'received_by' => $currentUserId,
                        'received_at' => $receivedAt->toISOString(),
                        'tupad_received_at' => $dvRequest->tupad_received_at ? $dvRequest->tupad_received_at->toISOString() : null,
                        'last_action_at' => $dvRequest->last_action_at ? $dvRequest->last_action_at->toISOString() : null,
                    ]
                );
                
                $this->logAction(
                    $dvRequest->id,
                    'Incoming',
                    $newStatus,
                    'Request officially received by Incoming-TUPAD. Timestamp: ' . ($dvRequest->tupad_received_at ? $dvRequest->tupad_received_at->format('M d, Y H:i:s') : 'Not set')
                );
                
                // Notify focal person
                $focalId = $dvRequest->focalperson_id;
                Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $newStatus,
                    'action'         => 'Received',
                    'remarks'        => 'Request officially received by Incoming-TUPAD',
                ]);
                
                // Trigger Pusher events
                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status'        => $newStatus,
                    'remarks'       => 'Request officially received by Incoming-TUPAD',
                    'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
                
                $this->pusher->trigger('dv-requests-incoming-tupad', 'dv-request-received', [
                    'dv_request_id' => $dvRequest->id,
                    'accounting_control_number' => $dvRequest->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) : 'Pending-CN',
                    'status'        => $newStatus,
                    'submitted_by'  => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                    'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'action_performer_id' => $currentUserId,
                    'received_by' => auth()->user()->name ?? 'Incoming-TUPAD User',
                    'dv_request' => [
                        'accounting_control_number' => $dvRequest->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) : 'Pending-CN',
                    ],
                    'tupad_received_at' => $dvRequest->tupad_received_at ? $dvRequest->tupad_received_at->toISOString() : null,
                ]);
            });
    
            // ✅ TRIGGER DASHBOARD UPDATE
            $this->triggerDashboardUpdate();
    
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document has been received by Incoming-TUPAD.',
                    'data' => [
                        'id' => $dvRequest->id,
                        'status' => 'Incoming-TUPAD_received',
                        'received_at' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'tupad_received_at' => $dvRequest->tupad_received_at ? $dvRequest->tupad_received_at->format('M d, Y h:i A') : null,
                        'last_action_at' => $dvRequest->last_action_at ? $dvRequest->last_action_at->format('M d, Y H:i:s') : null,
                    ]
                ]);
            }
            
            return redirect()->route('Incoming.TUPAD.index')
                ->with('success', 'Document has been received by Incoming-TUPAD.');
                
        } catch (\Exception $e) {
            Log::error('Error receiving DV request: ' . $e->getMessage(), [
                'dv_request_id' => $id,
                'user_id' => auth()->id(),
                'exception' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing the request: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'An error occurred while processing the request.');
        }
    }

    public function fetchtupadRecent()
    {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);
        $recentDvRequests = DvRequest::with('focalPerson')
            ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
            ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_received'])
            ->whereNotNull('focalperson_id')
            ->where(function ($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by')
                    ->orWhere('locked_by', $currentUserId)
                    ->orWhere('locked_at', '<=', $lockDuration);
            })
            ->latest()
            ->take(5)
            ->get();

        return response()->json($recentDvRequests);
    }

   public function fetchAll(Request $request)
{
    try {
        $currentUserId = auth()->id();
        $lockDuration = now()->subMinutes(10);

        \Log::info('TUPAD fetchAll called', [
            'user_id' => $currentUserId,
            'search' => $request->input('search'),
            'url' => $request->fullUrl()
        ]);

        $query = DvRequest::with(['focalPerson', 'program', 'respocenter'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
            ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_received'])
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
                         ->orWhereRaw("CONCAT(fname, ' ', lname) LIKE ?", ["%{$search}%"])
                  )
                  ->orWhereHas('respocenter', fn($r) => $r->where('rccode', 'like', "%{$search}%")
                                                          ->orWhere('description', 'like', "%{$search}%"))
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhere('particulars', 'like', "%{$search}%")
                  ->orWhere('accounting_control_number', 'like', "%{$search}%");
            });
        }

        // Get the results
        $dvRequests = $query->get();
        
        \Log::info('TUPAD fetchAll results count', ['count' => $dvRequests->count()]);

        // Calculate age for each request and add priority score
        $dvRequests = $dvRequests->map(function ($request) {
            // For TUPAD, use tupad_received_at if available, otherwise created_at
            $baseDate = $request->tupad_received_at ?? $request->created_at;
            $daysOld = now()->diffInDays($baseDate);
            
            // PRIORITY SCORING: Incoming-TUPAD_received gets HIGHEST priority
            $priorityScore = 0;
            
            // Status-based priority - Incoming-TUPAD_received gets HIGHEST priority (comes FIRST)
            if ($request->status === 'Incoming-TUPAD_received') {
                $priorityScore += 10000; // EXTREMELY HIGH priority - ensures it's always first
            } elseif ($request->status === 'IMSD_verified') {
                $priorityScore += 50;
            }
            
            // Age-based priority (secondary factor)
            if ($daysOld >= 10) {
                $priorityScore += 1000; // Critical priority
            } elseif ($daysOld >= 7) {
                $priorityScore += 500; // High priority
            } elseif ($daysOld >= 3) {
                $priorityScore += 200; // Medium priority
            }
            
            // Add small random factor to prevent exact ties
            $priorityScore += rand(0, 10);
            
            $request->days_old = $daysOld;
            $request->priority_score = $priorityScore;
            $request->urgency = $this->getUrgencyLevel($daysOld);
            
            return $request;
        });
        
        // Sort by priority score (descending) - HIGHEST priority comes FIRST
        // This ensures Incoming-TUPAD_received documents (10000+ score) appear at the top
        $dvRequests = $dvRequests->sortByDesc('priority_score')->values();

        $result = $dvRequests->map(function ($request) {
            // Show which date was used for age calculation
            $dateUsed = $request->tupad_received_at ? 'tupad_received_at' : 'created_at';
            $baseDate = $request->tupad_received_at ?? $request->created_at;
            
            return [
                'id'           => $request->id,
                'accounting_control_number' => $request->accounting_control_number 
                    ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT)
                    : '—',
                'submitted_by' => trim(($request->payee_fname ?? $request->focalPerson?->fname ?? '') 
                    . ' ' . ($request->payee_lname ?? $request->focalPerson?->lname ?? '')),
                'program'      => $request->program->prcode ?? 'N/A',
                'respocenter'  => $request->respocenter 
                    ? trim(($request->respocenter->rccode ?? '') . ' - ' . ($request->respocenter->description ?? ''))
                    : 'N/A',
                'particulars'  => $request->particulars ?? 'N/A',
                'amount'       => number_format($request->amount ?? 0, 2),
                'status'       => $request->status ?? 'N/A',
                'date'         => $baseDate->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'created_at_timestamp' => $request->created_at->toISOString(),
                'tupad_received_at' => $request->tupad_received_at ? $request->tupad_received_at->toISOString() : null,
                'tupad_last_action_at' => $request->tupad_last_action_at ? $request->tupad_last_action_at->toISOString() : null,
                'last_action_at' => $request->last_action_at ? $request->last_action_at->toISOString() : null,
                'days_old'     => $request->days_old ?? 0,
                'urgency'      => $request->urgency ?? 'low',
                'priority_score' => $request->priority_score ?? 0,
                'date_used_for_age' => $dateUsed,
            ];
        });

        \Log::info('TUPAD fetchAll returning', ['result_count' => $result->count()]);

        return response()->json($result);

    } catch (\Exception $e) {
        \Log::error('Error in TUPAD fetchAll: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'request' => $request->all()
        ]);
        
        return response()->json([
            'error' => 'Server Error',
            'message' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null
        ], 500);
    }
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

        $dvRequest = DvRequest::with(['focalPerson', 'program', 'respocenter', 'accountingUnit'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
            ->where(function ($q) use ($currentUserId, $lockDuration) {
                $q->whereNull('locked_by')
                    ->orWhere('locked_by', $currentUserId)
                    ->orWhere('locked_at', '<=', $lockDuration);
            })
            ->findOrFail($id);

        // ⭐ ADDED: Calculate age based on tupad_received_at
        $baseDate = $dvRequest->tupad_received_at ?? $dvRequest->created_at;
        $daysOld = now()->diffInDays($baseDate);
        
        return response()->json([
            'id' => $dvRequest->id,
            'dv_number' => $dvRequest->dv_number ?? '—',
            'title' => $dvRequest->particulars ?? 'No Title',
            'status' => $dvRequest->status,
            'amount' => number_format($dvRequest->amount, 2),
            'submitted_by' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
            'program' => $dvRequest->program->prcode ?? 'N/A',
            'accountingunit' => trim(($dvRequest->accountingUnit->fname ?? '') . ' ' . ($dvRequest->accountingUnit->lname ?? '')),
            'respocenter' => $dvRequest->respocenter->rccode ?? 'N/A',
            'date' => $baseDate->format('M d, Y h:i A'),
            'tupad_received_at' => $dvRequest->tupad_received_at ? $dvRequest->tupad_received_at->format('M d, Y h:i A') : null,
            'days_old'     => $daysOld,
            'urgency'      => $this->getUrgencyLevel($daysOld),
        ]);
    }

    public function verify(Request $request, $id)
    {
        // Validate the request
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
            'tupad_modality' => 'required|string',
            'tupad_attachment_status_value' => 'nullable|array',
            'tupad_attachment_status_value.*' => 'nullable|in:0,1,null',
            'tupad_attachment_remarks' => 'nullable|array',
            'tupad_attachment_remarks.*' => 'nullable|string|max:500',
        ]);

        // Find the DV request
        $dvRequest = DvRequest::with('tupadAttachments')
            ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
            ->findOrFail($id);

        $statusBefore = $dvRequest->status;
        $verifiedAt = now();
        $newStatus = 'Incoming-TUPAD_verified';
        $currentUserId = auth()->id();

        // Validation checks
        if ($statusBefore === $newStatus) {
            return redirect()->back()->with('error', 'This request has already been verified.');
        }

        if ($statusBefore !== 'Incoming-TUPAD_received') {
            return redirect()->back()->with('error', 'Request must be received first before verification.');
        }

        DB::transaction(function () use ($dvRequest, $statusBefore, $verifiedAt, $request, $newStatus, $currentUserId) {
            // ⭐ UPDATED: Use trait method for timestamp updates
            $this->updateDepartmentTimestamp($dvRequest, $newStatus);
            
            // ✅ UPDATE MODALITY IF CHANGED
            if ($request->has('tupad_modality') && $request->tupad_modality !== $dvRequest->tupad_modality) {
                Log::info('Updating modality from ' . $dvRequest->tupad_modality . ' to ' . $request->tupad_modality);

                // Delete old modality attachments
                TUPADAttachment::where('dv_request_id', $dvRequest->id)
                    ->where('modality', '!=', $request->tupad_modality)
                    ->delete();

                $dvRequest->update(['tupad_modality' => $request->tupad_modality]);
            }

            $dvRequest->update([
                'status' => 'Incoming-TUPAD_verified',
                'locked_by' => null,
                'locked_at' => null,
            ]);

            // Get all form data
            $attachmentStatusValues = $request->input('tupad_attachment_status_value', []);
            $attachmentRemarks = $request->input('tupad_attachment_remarks', []);
            $presentCount = 0;
            $notPresentCount = 0;
            $naCount = 0;
            $attachmentData = [];

            // ✅ CRITICAL FIX: Process TUPAD attachments directly from form data
            if ($dvRequest->tupad_modality && !empty($attachmentStatusValues)) {
                // Get current modality attachments from database
                $currentModalityAttachments = $this->getOrCreateTupadAttachmentsForDvRequest($dvRequest, $dvRequest->tupad_modality);

                Log::info('Processing ' . $currentModalityAttachments->count() . ' attachments for modality: ' . $dvRequest->tupad_modality);

                // ✅ Process each form submission directly
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
                                'modality' => $dvRequest->tupad_modality
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
                    $this->processSingleTupadAttachment(
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

                        $this->processSingleTupadAttachment(
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
            }

            $totalCount = $presentCount + $notPresentCount + $naCount;
            $attachmentSummary = "{$presentCount} present, {$notPresentCount} not present, {$naCount} N/A (Total: {$totalCount})";

            $baseRemarks = $request->remarks ?? 'TUPAD DV Request approved and forwarded to Budget Officer.';
            $remarksWithAttachments = $baseRemarks . " [Attachments: {$attachmentSummary}]";

            $this->saveIncomingTUPADVerificationHistory(
                $dvRequest,
                'verify',
                $remarksWithAttachments,
                [
                    'status_before'        => $statusBefore,
                    'status_after'         => $newStatus,
                    'attachments_checked'  => $attachmentData,
                    'verified_by'          => $currentUserId,
                    'verified_at'          => $verifiedAt->toISOString(),
                    'tupad_received_at'    => $dvRequest->tupad_received_at ? $dvRequest->tupad_received_at->toISOString() : null,
                    'tupad_last_action_at' => $dvRequest->tupad_last_action_at ? $dvRequest->tupad_last_action_at->toISOString() : null,
                    'last_action_at'       => $dvRequest->last_action_at ? $dvRequest->last_action_at->toISOString() : null,
                ]
            );

            $this->logAction(
                $dvRequest->id,
                'Incoming',
                $newStatus,
                ($request->remarks ?? 'Request verified by Incoming-TUPAD') . 
                ' | Last action: ' . ($dvRequest->last_action_at ? $dvRequest->last_action_at->format('M d, Y H:i:s') : 'Not set')
            );

            Notification::create([
                'focalperson_id' => $dvRequest->focalperson_id,
                'dv_request_id' => $dvRequest->id,
                'status' => $newStatus,
                'action' => 'Verified',
                'remarks' => $request->remarks ?? 'Request verified by Incoming-TUPAD',
            ]);

            if (isset($this->pusher)) {
                $timestamp = $verifiedAt->timezone('Asia/Manila')->format('M d, Y h:i A');
                $focalId = $dvRequest->focalperson_id;
                $remarks = $request->remarks ?? 'Request verified by Incoming-TUPAD';

                $this->pusher->trigger('dv-requests-budget-officer', 'dv-request-verified', [
                    'dv_request_id' => $dvRequest->id,
                    'submitted_by' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                    'program' => $dvRequest->program->prcode ?? 'N/A',
                    'status' => $newStatus,
                    'timestamp' => $timestamp,
                    'action_performer_id' => $currentUserId,
                    'tupad_received_at' => $dvRequest->tupad_received_at ? $dvRequest->tupad_received_at->format('M d, Y H:i:s') : null,
                    'tupad_last_action_at' => $dvRequest->tupad_last_action_at ? $dvRequest->tupad_last_action_at->format('M d, Y H:i:s') : null,
                ]);

                $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                    'dv_request_id' => $dvRequest->id,
                    'status' => $newStatus,
                    'remarks' => $remarks,
                    'timestamp' => $timestamp,
                ]);

                $this->pusher->trigger('dv-requests-incoming-tupad', 'dv-request-verified', [
                    'dv_request_id' => $dvRequest->id,
                    'status' => $newStatus,
                    'timestamp' => $timestamp,
                    'action_performer_id' => $currentUserId,
                    'verified_by_user_id' => $currentUserId,
                    'tupad_last_action_at' => $dvRequest->tupad_last_action_at ? $dvRequest->tupad_last_action_at->toISOString() : null,
                ]);
            }
        });

        // ✅ TRIGGER DASHBOARD UPDATE
        $this->triggerDashboardUpdate();

        return redirect()->route('Incoming.TUPAD.index')
            ->with('success', 'TUPAD DV Request Approved.')
            ->with('attachments_summary', $attachmentSummary ?? '');
    }

    private function processSingleTupadAttachment(
        $attachment,
        $status,
        $remarks,
        &$presentCount,
        &$notPresentCount,
        &$naCount,
        &$attachmentData
    ) {
        $isPresent = null;

        // ✅ CRITICAL FIX: Proper type checking with strict comparison
        if ($status === '1' || $status === 1 || $status === true) {
            $isPresent = 1;
            $presentCount++;
        } elseif ($status === '0' || $status === 0 || $status === false) {
            $isPresent = 0;
            $notPresentCount++;
        } else {
            // Anything else (null, 'null', empty string, etc.) = N/A
            $isPresent = null;
            $naCount++;
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

        $updateResult = $attachment->update($updateData);

        // Store for history
        $attachmentData[] = [
            'id' => $attachment->id,
            'name' => $attachment->name,
            'is_present' => $isPresent,
            'status_label' => $isPresent === 1 ? 'Present' : ($isPresent === 0 ? 'Not Present' : 'N/A'),
            'remarks' => $remarks,
            'modality' => $attachment->modality,
            'type' => 'tupad'
        ];
    }

    /**
     * Handle attachment updates with three-state status and remarks
     */
    public function updateAttachments(Request $request, $id)
    {
        try {
            $request->validate([
                'tupad_attachment_status_value' => 'required|array',
                'tupad_attachment_status_value.*' => 'nullable|in:0,1,null',
                'tupad_attachment_remarks' => 'nullable|array',
                'tupad_attachment_remarks.*' => 'nullable|string|max:500',
            ]);

            $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
                                ->findOrFail($id);

            if ($dvRequest->locked_by !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update this request.'
                ], 403);
            }

            $attachmentStatuses = $request->input('tupad_attachment_status_value', []);
            $attachmentRemarks = $request->input('tupad_attachment_remarks', []);
            $updatedCount = 0;
            $presentCount = 0;
            $notPresentCount = 0;
            $naCount = 0;

            foreach ($dvRequest->tupadAttachments as $attachment) {
                $status = $attachmentStatuses[$attachment->id] ?? null;
                
                // Convert status to proper format
                if ($status === 'null' || $status === null) {
                    $isPresent = null;
                    $naCount++;
                } elseif ((int)$status === 1) {
                    $isPresent = true;
                    $presentCount++;
                } else {
                    $isPresent = false;
                    $notPresentCount++;
                }

                // Get remarks (only relevant for "Not Present" status)
                $remarks = $attachmentRemarks[$attachment->id] ?? null;
                
                // Special handling for Present status
                if ($isPresent === true) {
                    $remarks = 'The attachment is present';
                }

                // Update attachment
                $attachment->update([
                    'is_present' => $isPresent,
                    'remarks' => $remarks,
                ]);

                $updatedCount++;
            }

            $this->logAction(
                $dvRequest->id,
                'Incoming',
                $dvRequest->status,
                "Attachment checklist updated: {$presentCount} present, {$notPresentCount} not present, {$naCount} N/A"
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Attachments updated successfully.',
                    'data' => [
                        'updated_count' => $updatedCount,
                        'present_count' => $presentCount,
                        'not_present_count' => $notPresentCount,
                        'na_count' => $naCount,
                    ]
                ]);
            }

            return redirect()->back()->with('success', 'Attachments updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating attachments: ' . $e->getMessage(), [
                'dv_request_id' => $id,
                'error' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating attachments.'
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while updating attachments.');
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $request->validate([
                'remarks' => 'required|string|max:1000|min:10',
            ]);
    
            $dvRequest = DvRequest::whereHas('program', fn($q) => $q->where('prcode', 'TUPAD'))
                                ->findOrFail($id);
    
            $statusBefore = $dvRequest->status;
            $currentUserId = auth()->id();
    
            if ($dvRequest->status !== 'Incoming-TUPAD_received') {
                return response()->json([
                    'success' => false,
                    'message' => 'This DV request cannot be rejected at its current stage.'
                ], 422);
            }
    
            $rejectedAt = now();
            $focalId = $dvRequest->focalperson_id;
            $remarks = trim($request->remarks);
            $newStatus = 'Incoming-TUPAD_rejected';
    
            // ✅ PHASE 1: FAST DATABASE OPERATIONS ONLY
            DB::transaction(function () use ($dvRequest, $statusBefore, $rejectedAt, $remarks, $focalId, $currentUserId, $newStatus) {
                
                // ✅ ARCHIVE TO HISTORY TABLE FIRST
                $this->archiveToHistory($dvRequest, $newStatus, $remarks);
                
                // ⭐ UPDATED: Update department timestamp for rejection
                $this->updateDepartmentTimestamp($dvRequest, $newStatus);
                
                // Update main request
                $dvRequest->update([
                    'status'    => $newStatus,
                    'locked_by' => null,
                    'locked_at' => null,
                ]);
    
                // Save verification history
                $this->saveIncomingTUPADVerificationHistory(
                    $dvRequest,
                    'reject',
                    $remarks,
                    [
                        'status_before'   => $statusBefore,
                        'status_after'    => $newStatus,
                        'action'          => 'rejected',
                        'rejected_by'     => $currentUserId,
                        'rejected_at'     => $rejectedAt->toISOString(),
                        'rejection_reason'=> $remarks,
                        'last_action_at'  => $dvRequest->last_action_at ? $dvRequest->last_action_at->toISOString() : null,
                    ]
                );
    
                // Log action
                $this->logAction(
                    $dvRequest->id,
                    'Incoming-TUPAD',
                    $newStatus,
                    "Rejected by Incoming-TUPAD: {$remarks}. Last action: " . 
                    ($dvRequest->last_action_at ? $dvRequest->last_action_at->format('M d, Y H:i:s') : 'Not set')
                );
    
                // Create notification
                Notification::create([
                    'focalperson_id' => $focalId,
                    'dv_request_id'  => $dvRequest->id,
                    'status'         => $newStatus,
                    'action'         => 'Rejected',
                    'remarks'        => $remarks,
                ]);
    
                // Pusher events
                if (isset($this->pusher)) {
                    $timestamp = $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A');
    
                    $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => $newStatus,
                        'isRejected'    => true,
                        'remarks'       => $remarks,
                        'timestamp'     => $timestamp,
                    ]);
    
                    $this->pusher->trigger('dv-requests-incoming-tupad', 'dv-request-rejected', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => $newStatus,
                        'timestamp'     => $timestamp,
                        'action_performer_id' => $currentUserId,
                        'rejected_by_user_id' => $currentUserId,
                        'last_action_at' => $dvRequest->last_action_at ? $dvRequest->last_action_at->toISOString() : null,
                    ]);
                }
            });

            // ✅ PHASE 2: SEND NOTIFICATIONS AFTER DB TRANSACTION (NON-BLOCKING)
            $smsSent = $this->sendRejectionSMS($dvRequest, $remarks);
            $emailSent = $this->sendRejectionEmail($dvRequest, $remarks);

            // ✅ TRIGGER DASHBOARD UPDATE
            $this->triggerDashboardUpdate();
    
            // ✅ IMMEDIATE RESPONSE TO USER
            $responseMessage = 'DV request has been rejected successfully.';
            if ($smsSent) {
                $responseMessage .= ' SMS notification sent.';
            } else {
                $responseMessage .= ' SMS will be retried.';
            }
    
            return response()->json([
                'success' => true,
                'message' => $responseMessage,
                'data' => [
                    'id'         => $dvRequest->id,
                    'status'     => $newStatus,
                    'remarks'    => $remarks,
                    'sms_sent'   => $smsSent,
                    'email_sent' => $emailSent,
                    'last_action_at' => $dvRequest->last_action_at ? $dvRequest->last_action_at->format('M d, Y H:i:s') : null,
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
            Log::error('Error rejecting TUPAD DV request: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the DV request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ARCHIVE TO HISTORY TABLE
     */
    protected function archiveToHistory(DvRequest $dvRequest, $status, $reason)
    {
        return DvRequestHistory::create([
            'dv_request_id' => $dvRequest->id,
            'reference_number' => $dvRequest->reference_number,
            'dv_number' => $dvRequest->dv_number,
            'ors_number' => $dvRequest->ors_number,
            'user_sequence' => $dvRequest->user_sequence,
            'accounting_control_number' => $dvRequest->accounting_control_number,
            'accountingunit_id' => $dvRequest->accountingunit_id,
            'focalperson_id' => $dvRequest->focalperson_id,
            'program_id' => $dvRequest->program_id,
            'respocenter_id' => $dvRequest->respocenter_id,
            'particulars' => $dvRequest->particulars,
            'amount' => $dvRequest->amount,
            'status' => $status,
            'tupad_modality' => $dvRequest->tupad_modality,
            'tev_modality' => $dvRequest->tev_modality,
            'history_reason' => $reason,
            'moved_to_history_at' => now(),
            'moved_by' => auth()->id(),
        ]);
    }

    protected function sendRejectionSMS($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            // Quick validation
            if (!$focalPerson || empty($focalPerson->contactnumber)) {
                Log::warning('Cannot send rejection SMS: focal person or contact number missing', [
                    'dv_request_id' => $dvRequest->id,
                    'focal_person_id' => $focalPerson->id ?? 'null'
                ]);
                return false;
            }

            $phoneNumber = $focalPerson->contactnumber;
            
            // ⚡ CREATE SHORT MESSAGE - Faster transmission
            $userSequence = $dvRequest->user_sequence ?? 'N/A';
            $truncatedRemarks = strlen($rejectionRemarks) > 80
                ? substr($rejectionRemarks, 0, 77) . '...' 
                : $rejectionRemarks;

            $message = "Hi {$focalPerson->fname}, your DV Request #{$userSequence} has been rejected. Reason: {$truncatedRemarks}. - ACCUDOCS DOLE XI";

            Log::info('Sending TUPAD rejection SMS', [
                'phone' => $phoneNumber,
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $userSequence
            ]);

            // ⚡ USE QUICK SMS METHOD - Fast timeout
            $result = $this->smsService->sendQuickSMS($phoneNumber, $message);
            
            if ($result['success']) {
                Log::info('TUPAD rejection SMS sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'phone' => $phoneNumber
                ]);
                return true;
            } else {
                Log::warning('TUPAD rejection SMS failed - continuing', [
                    'dv_request_id' => $dvRequest->id,
                    'phone' => $phoneNumber
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            // ⚡ SILENT FAILURE - Don't affect user experience
            Log::warning('TUPAD SMS rejection failed silently: ' . $e->getMessage(), [
                'dv_request_id' => $dvRequest->id
            ]);
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
            
            $subject = "DV Request Rejected - Incoming-TUPAD - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            $htmlContent = view('emails.dv_rejected', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'rejectionRemarks' => $rejectionRemarks,
                'rejectedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'rejectedBy' => auth()->user()->name ?? 'Incoming-TUPAD User',
                'department' => 'Incoming-TUPAD'
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

        // Get all pending TUPAD requests
        $pendingRequests = DvRequest::whereHas('program', fn($q) =>
            $q->where('prcode', 'TUPAD'))
            ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_received'])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($pendingRequests->isEmpty()) {
            return redirect()->route('Incoming.TUPAD.index')
                ->with('info', 'No TUPAD DV requests pending verification.');
        }

        // Check for one you can work on (unlocked or locked by you, or expired lock)
        $available = $pendingRequests->first(function ($req) use ($currentUserId, $lockDuration) {
            return !$req->locked_by
                || $req->locked_by == $currentUserId
                || $req->locked_at <= $lockDuration;
        });

        if ($available) {
            return redirect()->route('Incoming.TUPAD.dvrequests.withid', $available->id);
        }

        // If all are locked by others
        return redirect()->route('Incoming.TUPAD.index')
            ->with('info', 'All pending TUPAD DV requests are currently locked by other users.');
    }

    public function releaseLock($id)
    {
        $dvRequest = DvRequest::findOrFail($id);

        if ($dvRequest->locked_by === auth()->id()) {
            $dvRequest->update([
                'locked_by' => null,
                'locked_at' => null,
                'received_by'      => null,
                'received_at'      => null,
                'status' => 'IMSD_verified'
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
            'locked_at' => now(),
        ]);

        return response()->json(['success' => true, 'locked_at' => $dvRequest->locked_at]);
    }

    public function history(Request $request)
    {
        $query = IncomingTUPADVerificationHistory::with([
            'dvRequest.program:id,prcode',
            'dvRequest.focalPerson:id,fname,lname',
            'dvRequest.respocenter:id,rccode,description'
        ]);

        // Search by Control Number
        if ($request->has('search') && $request->search) {
            $query->whereHas('dvRequest', function ($q) use ($request) {
                $q->where('accounting_control_number', 'like', '%' . $request->search . '%')
                    ->orWhere('dv_number', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by Program
        if ($request->has('program') && $request->program) {
            $query->whereHas('dvRequest.program', function ($q) use ($request) {
                $q->where('prcode', $request->program);
            });
        }

        // Filter by Modality
        if ($request->has('modality') && $request->modality) {
            $query->whereHas('dvRequest', function ($q) use ($request) {
                $q->where('tupad_modality', $request->modality);
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

        return view('Incoming.TUPAD.loghistory', compact('verificationHistory'));
    }

    protected function saveIncomingTUPADVerificationHistory($dvRequest, $step, $remarks = null, array $data = [])
    {
        try {
            IncomingTUPADVerificationHistory::create([
                'dv_request_id' => $dvRequest->id,
                'verified_by' => auth()->id(),
                'verification_step' => $step,
                'status_before' => $data['status_before'] ?? $dvRequest->getOriginal('status'),
                'status_after' => $data['status_after'] ?? $dvRequest->status,
                'verification_data' => $data,
                'remarks' => $remarks,
                'verified_at' => now(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to save Incoming-TUPAD verification history', [
                'dv_request_id' => $dvRequest->id ?? null,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function logAction($dvRequestId, $department, $action, $remarks = null)
    {
        try {
            $logData = [
                'dv_request_id' => $dvRequestId,
                'action' => $action,
                'remarks' => $remarks,
                'created_at' => now(),
                'updated_at' => now(),
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
        }
    }
}