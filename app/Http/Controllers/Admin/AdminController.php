<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; 
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Program;
use App\Models\Responcenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function documentTracking(Request $request)
    {
        $query = DvRequest::with(['focalPerson', 'program', 'respocenter'])
            ->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('particulars', 'like', "%{$search}%")
                  ->orWhereHas('focalPerson', function($q) use ($search) {
                      $q->where('fname', 'like', "%{$search}%")
                        ->orWhere('lname', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $dvRequests = $query->paginate(20);

        // Statistics for cards
        $totalRequests = DvRequest::count();
        $completedRequests = DvRequest::where('status', 'Cashier_released')->count();
        $inProgressRequests = DvRequest::where('status', '!=', 'Cashier_released')->count();
        $rejectedRequests = DvRequest::where('status', 'like', '%rejected%')->count();

        return view('Admin.Document-Tracking.index', compact(
            'dvRequests', 
            'totalRequests',
            'completedRequests',
            'inProgressRequests',
            'rejectedRequests'
        ));
    }

    public function documentTrackingShow(DvRequest $dvRequest)
    {
        $logs = DvRequestLog::where('dv_request_id', $dvRequest->id)
            ->with(['focalPerson', 'accountingUnit'])
            ->orderBy('created_at', 'desc') // Changed to desc to show recent first
            ->get();

        return view('Admin.Document-Tracking.show', compact('dvRequest', 'logs'));
    }

    public function getRequestLogs(DvRequest $dvRequest)
    {
        $logs = DvRequestLog::where('dv_request_id', $dvRequest->id)
            ->with(['focalPerson', 'accountingUnit'])
            ->orderBy('created_at', 'desc') // Changed to desc to show recent first
            ->get();

        if (request()->ajax()) {
            $html = view('admin.document-tracking.partials.logs-table', compact('logs'))->render();
            
            return response()->json([
                'html' => $html,
                'count' => $logs->count(),
                'lastUpdated' => now()->diffForHumans(),
                'logs' => $logs->map(function($log) {
                    return [
                        'id' => $log->id,
                        'action' => strtolower($log->action),
                        'remarks' => $log->remarks,
                        'external_actor' => $log->external_actor,
                        'created_at_human' => $log->created_at->diffForHumans(),
                        'focal_person' => $log->focalPerson ? [
                            'fname' => $log->focalPerson->fname,
                            'lname' => $log->focalPerson->lname,
                            'profile_image' => $log->focalPerson->profile_image
                        ] : null,
                        'accounting_unit' => $log->accountingUnit ? [
                            'fname' => $log->accountingUnit->fname,
                            'lname' => $log->accountingUnit->lname,
                            'profile_image' => $log->accountingUnit->profile_image
                        ] : null,
                    ];
                })
            ]);
        }

        return response()->json($logs);
    }

    public function getLatestStep(DvRequest $dvRequest)
    {
        $lastLog = $dvRequest->logs()->latest()->first();
        
        $statusToStepMap = [
            'submitted' => 'Submitted',
            'IMSD_received' => 'IMSD', 'IMSD_in_process' => 'IMSD', 'IMSD_verified' => 'IMSD', 'IMSD_rejected' => 'IMSD',
            'Incoming-TUPAD_received' => 'Incoming', 'Incoming-TUPAD_in_process' => 'Incoming', 'Incoming-TUPAD_verified' => 'Incoming', 'Incoming-TUPAD_rejected' => 'Incoming',
            'Incoming-GIP_received' => 'Incoming', 'Incoming-GIP_in_process' => 'Incoming', 'Incoming-GIP_verified' => 'Incoming', 'Incoming-GIP_rejected' => 'Incoming',
            'Incoming-TEV_received' => 'Incoming', 'Incoming-TEV_in_process' => 'Incoming', 'Incoming-TEV_verified' => 'Incoming', 'Incoming-TEV_rejected' => 'Incoming',
            'ORS_received' => 'Budget_Officer', 'ORS_in_process' => 'Budget_Officer', 'ORS_obligated' => 'Budget_Officer', 'ORS_rejected' => 'Budget_Officer',
            'Outgoing_received' => 'Outgoing', 'Outgoing_in_process' => 'Outgoing', 'Outgoing_finalized' => 'Outgoing', 'Outgoing_rejected' => 'Outgoing',
            'RANCA_received' => 'Accountant_III', 'RANCA_in_process' => 'Accountant_III', 'RANCA_validated' => 'Accountant_III', 'RANCA_rejected' => 'Accountant_III',
            'Regional_Director_received' => 'Regional_Director', 'Regional_Director_signed_approved' => 'Regional_Director',
            'Cashier_received' => 'Cashier', 'Cashier_released' => 'Cashier',
        ];

        $lastStep = 'Submitted';
        if (isset($statusToStepMap[$dvRequest->status])) {
            $lastStep = $statusToStepMap[$dvRequest->status];
        } elseif ($lastLog && isset($statusToStepMap[$lastLog->action])) {
            $lastStep = $statusToStepMap[$lastLog->action];
        }

        $isRejected = stripos($dvRequest->status, 'rejected') !== false || 
                     ($lastLog && stripos($lastLog->action, 'rejected') !== false);

        $isStepCompleted = $dvRequest->status === 'Cashier_released' ||
                          stripos($dvRequest->status, '_verified') !== false ||
                          stripos($dvRequest->status, '_obligated') !== false ||
                          stripos($dvRequest->status, '_finalized') !== false ||
                          stripos($dvRequest->status, '_validated') !== false;

        return response()->json([
            'lastStep' => $lastStep,
            'currentStatus' => $dvRequest->status,
            'isRejected' => $isRejected,
            'isStepCompleted' => $isStepCompleted,
            'isCompleted' => $dvRequest->status === 'Cashier_released',
            'lastLogAction' => $lastLog?->action,
            'debug' => [
                'status' => $dvRequest->status,
                'last_log_action' => $lastLog?->action,
                'mapped_step' => $lastStep
            ]
        ]);
    }
   
/**
 * Get ALL exceeded documents from ALL departments 
 * IMSD: submitted, IMSD_received, IMSD_in_process, IMSD_verified
 * Incoming: Incoming-*_received, Incoming-*_in_process
 * Budget Officer: Incoming-*_verified, ORS_received, ORS_in_process, ORS_onhold, ORS_for_revision
 * Outgoing: ORS_obligated, Outgoing_received
 */
/**
 * Get ALL exceeded documents from ALL departments 
 * IMSD: submitted, IMSD_received, IMSD_in_process, IMSD_verified
 * Incoming: Incoming-*_received, Incoming-*_in_process
 * Budget Officer: Incoming-*_verified, ORS_received, ORS_in_process, ORS_onhold, ORS_for_revision
 * Outgoing: ORS_obligated, Outgoing_received
 * Accountant/Accountant_III: Outgoing_finalized, RANCA_received
 */
public function getAllExceededDocuments()
{
    $exceededDocs = DvRequest::with(['program', 'focalPerson', 'orsRequest'])
        ->where(function($q) {
            // IMSD statuses
            $q->whereIn('status', [
                'submitted', 
                'IMSD_received', 
                'IMSD_in_process',
                'IMSD_verified'
              ])
              // Incoming statuses
              ->orWhereIn('status', [
                  'Incoming-TUPAD_received', 'Incoming-TUPAD_in_process',
                  'Incoming-GIP_received', 'Incoming-GIP_in_process',
                  'Incoming-TEV_received', 'Incoming-TEV_in_process'
              ])
              // Budget Officer statuses
              ->orWhereIn('status', [
                  'Incoming-TUPAD_verified',
                  'Incoming-GIP_verified',
                  'Incoming-TEV_verified',
                  'ORS_received',
                  'ORS_in_process',
                  'ORS_onhold',
                  'ORS_for_revision'
              ])
              // Outgoing statuses
              ->orWhereIn('status', [
                  'ORS_obligated',
                  'Outgoing_received'
              ])
              // 🚨🚨🚨 ACCOUNTANT/ACCOUNTANT_III STATUSES - ADD THIS 🚨🚨🚨
              ->orWhereIn('status', [
                  'Outgoing_finalized',
                  'RANCA_received'
              ]);
        })
        ->where('created_at', '<=', now()->subDays(10))
        ->orderBy('created_at', 'asc')
        ->get()
        ->map(function ($doc) {
            // Determine department and friendly status
            $department = 'IMSD';
            $status_friendly = $doc->status;
            $badge_color = 'bg-red-100 text-red-800';
            $department_color = 'bg-red-600';
            
            if (str_contains($doc->status, 'Incoming-')) {
                if (str_contains($doc->status, '_verified')) {
                    $department = 'Budget Officer';
                    $badge_color = 'bg-purple-100 text-purple-800';
                    $department_color = 'bg-purple-600';
                    $status_friendly = 'Pending ORS Creation';
                } else {
                    $department = 'Incoming';
                    $badge_color = 'bg-blue-100 text-blue-800';
                    $department_color = 'bg-blue-600';
                    $status_friendly = match($doc->status) {
                        'Incoming-TUPAD_received', 'Incoming-GIP_received', 'Incoming-TEV_received' 
                            => 'Pending Verification',
                        'Incoming-TUPAD_in_process', 'Incoming-GIP_in_process', 'Incoming-TEV_in_process' 
                            => 'In Process',
                        default => $doc->status
                    };
                }
            } 
            elseif (in_array($doc->status, [
                'ORS_received', 
                'ORS_in_process', 
                'ORS_onhold', 
                'ORS_for_revision'
            ])) {
                $department = 'Budget Officer';
                $badge_color = 'bg-purple-100 text-purple-800';
                $department_color = 'bg-purple-600';
                $status_friendly = match($doc->status) {
                    'ORS_received' => 'Pending Obligation',
                    'ORS_in_process' => 'In Process',
                    'ORS_onhold' => 'On Hold',
                    'ORS_for_revision' => 'For Revision',
                    default => $doc->status
                };
            }
            // Outgoing department
            elseif (in_array($doc->status, ['ORS_obligated', 'Outgoing_received'])) {
                $department = 'Outgoing';
                $badge_color = 'bg-green-100 text-green-800';
                $department_color = 'bg-green-600';
                $status_friendly = match($doc->status) {
                    'ORS_obligated' => 'Pending Outgoing Receive',
                    'Outgoing_received' => 'Pending Finalization',
                    default => $doc->status
                };
            }
            // 🚨🚨🚨 ACCOUNTANT/ACCOUNTANT_III DEPARTMENT - ADD THIS 🚨🚨🚨
            elseif (in_array($doc->status, ['Outgoing_finalized', 'RANCA_received'])) {
                $department = 'Accountant';
                $badge_color = 'bg-indigo-100 text-indigo-800';
                $department_color = 'bg-indigo-600';
                $status_friendly = match($doc->status) {
                    'Outgoing_finalized' => 'Pending RANCA Receive',
                    'RANCA_received' => 'Pending Validation',
                    default => $doc->status
                };
            }
            else {
                $badge_color = 'bg-red-100 text-red-800';
                $department_color = 'bg-red-600';
                $status_friendly = match($doc->status) {
                    'submitted' => 'Pending IMSD Receiving',
                    'IMSD_received' => 'Pending IMSD Verification',
                    'IMSD_in_process' => 'IMSD In Process',
                    'IMSD_verified' => 'Waiting for Incoming',
                    default => $doc->status
                };
            }
            
            // Get ORS number if available
            $ors_number = null;
            if ($doc->orsRequest) {
                $ors_number = $doc->orsRequest->ors_number;
            }
            
            return [
                'id' => $doc->id,
                'control_number' => $doc->accounting_control_number 
                    ? 'CN #-' . now()->year . '-' . str_pad($doc->accounting_control_number, 4, '0', STR_PAD_LEFT)
                    : 'Pending-CN',
                'ors_number' => $ors_number,
                'program' => $doc->program->prcode ?? 'N/A',
                'days_old' => now()->diffInDays($doc->created_at),
                'status' => $status_friendly,
                'original_status' => $doc->status,
                'status_badge_color' => $badge_color,
                'amount' => '₱' . number_format($doc->amount, 2),
                'focal' => trim(($doc->focalPerson->fname ?? '') . ' ' . ($doc->focalPerson->lname ?? '')),
                'department' => $department,
                'department_badge' => $department_color,
                'is_urgent' => $doc->is_urgent ?? false,
                'program_code' => $doc->program->prcode ?? 'N/A',
            ];
        });

    // Summary statistics - ADD ACCOUNTANT COUNTS
    $summary = [
        'total' => $exceededDocs->count(),
        'imsd' => $exceededDocs->where('department', 'IMSD')->count(),
        'incoming' => $exceededDocs->where('department', 'Incoming')->count(),
        'budget_officer' => $exceededDocs->where('department', 'Budget Officer')->count(),
        'outgoing' => $exceededDocs->where('department', 'Outgoing')->count(),
        'accountant' => $exceededDocs->where('department', 'Accountant')->count(), // 🚨 ADDED
        'incoming_tupad' => $exceededDocs->where('program', 'TUPAD')->where('department', 'Incoming')->count(),
        'incoming_gip' => $exceededDocs->where('program', 'GIP')->where('department', 'Incoming')->count(),
        'incoming_tev' => $exceededDocs->where('program', 'TEV')->where('department', 'Incoming')->count(),
        'budget_pending_ors' => $exceededDocs->whereIn('original_status', [
            'Incoming-TUPAD_verified',
            'Incoming-GIP_verified', 
            'Incoming-TEV_verified'
        ])->count(),
        'budget_pending_obligation' => $exceededDocs->where('original_status', 'ORS_received')->count(),
        'outgoing_pending_receive' => $exceededDocs->where('original_status', 'ORS_obligated')->count(),
        'outgoing_pending_finalize' => $exceededDocs->where('original_status', 'Outgoing_received')->count(),
        'accountant_pending_receive' => $exceededDocs->where('original_status', 'Outgoing_finalized')->count(), // 🚨 ADDED
        'accountant_pending_validate' => $exceededDocs->where('original_status', 'RANCA_received')->count(), // 🚨 ADDED
        'urgent' => $exceededDocs->where('is_urgent', true)->count(),
        'not_urgent' => $exceededDocs->where('is_urgent', false)->count(),
    ];

    return response()->json([
        'success' => true,
        'count' => $exceededDocs->count(),
        'summary' => $summary,
        'documents' => $exceededDocs,
        'message' => 'Documents exceeding 10-day SLA'
    ]);
}
public function markAsUrgent(Request $request)
    {
        try {
            $request->validate([
                'dv_request_ids' => 'required|array',
                'dv_request_ids.*' => 'exists:dv_requests,id',
                'remarks' => 'nullable|string|max:255'
            ]);

            $updated = DvRequest::whereIn('id', $request->dv_request_ids)
                ->update([
                    'is_urgent' => true,
                    'marked_urgent_at' => now(),
                    'marked_urgent_by' => auth()->id(),
                    'urgent_remarks' => $request->remarks ?? 'Marked urgent from dashboard'
                ]);

            // 🔥 NEW: Broadcast to IMSD channel for each document
            $pusher = app(\App\Services\PusherService::class);
            
            foreach ($request->dv_request_ids as $id) {
                // Log the action
                DvRequestLog::create([
                    'dv_request_id' => $id,
                    'accountingunit_id' => auth()->id(),
                    'action' => 'marked_urgent',
                    'remarks' => $request->remarks ?? 'Marked urgent from admin dashboard'
                ]);
                
                // Get the DV request details for broadcasting
                $dvRequest = DvRequest::with(['program', 'focalPerson'])->find($id);
                if ($dvRequest) {
                    $controlNumber = $dvRequest->accounting_control_number 
                        ? 'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) 
                        : 'Pending-CN';
                    
                    // 🔥 Broadcast to IMSD channel
                    try {
                        $pusher->trigger('dv-requests-imsd', 'dv-request-marked-urgent', [
                            'dv_request_id' => $dvRequest->id,
                            'control_number' => $controlNumber,
                            'is_urgent' => true,
                            'marked_by' => auth()->user()->name ?? 'Admin',
                            'marked_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                            'remarks' => $request->remarks ?? 'Marked urgent from admin dashboard',
                            'program' => $dvRequest->program->prcode ?? 'N/A',
                            'submitted_by' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                            'status' => $dvRequest->status,
                            'amount' => number_format($dvRequest->amount, 2),
                            'days_old' => now()->diffInDays($dvRequest->created_at),
                        ]);
                    } catch (\Exception $pusherError) {
                        \Log::warning('Pusher broadcast failed for urgent marking DV #' . $dvRequest->id);
                    }
                    
                    // 🔥 Also notify the focal person
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$updated} document(s) marked as urgent",
                'count' => $updated
            ]);

        } catch (\Exception $e) {
            \Log::error('Error marking documents as urgent: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}