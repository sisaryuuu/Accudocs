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

        return view('admin.document-tracking.index', compact(
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
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.document-tracking.show', compact('dvRequest', 'logs'));
    }

    public function getRequestLogs(DvRequest $dvRequest)
    {
        $logs = DvRequestLog::where('dv_request_id', $dvRequest->id)
            ->with(['focalPerson', 'accountingUnit'])
            ->orderBy('created_at', 'asc')
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
}