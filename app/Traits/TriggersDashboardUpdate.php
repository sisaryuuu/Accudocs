<?php

namespace App\Traits;

use App\Events\DashboardUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

trait TriggersDashboardUpdate
{
    use BudgetThresholdAnalysis;

    private $useDummyData = false;
    
    private function calculateWorkingDays($start, $end)
    {
        $start = \Carbon\Carbon::parse($start);
        $end = \Carbon\Carbon::parse($end);
        $workingDays = 0;
        $current = $start->copy();
        
        while ($current->lte($end)) {
            // Check if it's a weekday (1 = Monday, 7 = Sunday)
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }
        
        return $workingDays;
    }

    /**
     * Trigger real-time dashboard update via Pusher
     * Call this method after any DV request status change
     * 
     * @return void
     */
    protected function triggerDashboardUpdate()
    {
        try {
            Log::info('Triggering dashboard update', [
                'controller' => get_class($this),
                'user_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString()
            ]);

            // Get fresh dashboard data
            $data = $this->getDashboardData();
            
            // Broadcast the event via Pusher
            event(new DashboardUpdated($data));
            
            Log::info('Dashboard update triggered successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to trigger dashboard update', [
                'controller' => get_class($this),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Don't throw - dashboard update failure shouldn't break the main operation
        }
    }

    /**
     * Validate date range
     */
    private function validateDateRange($dateFrom, $dateTo)
    {
        try {
            $from = Carbon::parse($dateFrom)->startOfDay();
            $to = Carbon::parse($dateTo)->endOfDay();
            
            // Ensure date range is valid
            if ($from->gt($to)) {
                // Swap if from is after to
                $temp = $from;
                $from = $to;
                $to = $temp;
            }
            
            // Limit date range to maximum 5 years for performance
            if ($from->diffInYears($to) > 5) {
                $to = $from->copy()->addYears(5);
            }
            
            return [
                'from' => $from,
                'to' => $to,
                'is_valid' => true
            ];
        } catch (\Exception $e) {
            // Return default range if invalid
            return [
                'from' => now()->subMonths(12)->startOfDay(),
                'to' => now()->endOfDay(),
                'is_valid' => false
            ];
        }
    }

    /**
     * Get all dashboard data
     * Centralized method to ensure consistent data across all dashboard views
     * 
     * @return array
     */
    protected function getDashboardData($dateFrom = null, $dateTo = null)
    {
        if ($this->useDummyData) {
            return $this->getDummyDashboardData($dateFrom, $dateTo);
        }

        return $this->getRealDashboardData($dateFrom, $dateTo);
    }

    /**
     * Get real dashboard data from database
     */
  /**
 * Get real dashboard data from database
 */
protected function getRealDashboardData($dateFrom = null, $dateTo = null)
{
    // First, check if dates are provided and validate them
    $hasDateFilter = $this->validateDate($dateFrom) && $this->validateDate($dateTo);
    
    if ($hasDateFilter) {
        $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        $dateTo = Carbon::parse($dateTo)->endOfDay();
        
        // Ensure date range is valid
        if ($dateFrom->gt($dateTo)) {
            $temp = $dateFrom;
            $dateFrom = $dateTo;
            $dateTo = $temp;
        }
    } else {
        // Default to last 12 months if no valid dates provided
        $dateFrom = now()->subMonths(12)->startOfDay();
        $dateTo = now()->endOfDay();
    }
    
    // Log the date range being used for debugging
    Log::info('Dashboard data filtering', [
        'date_from' => $dateFrom->format('Y-m-d H:i:s'),
        'date_to' => $dateTo->format('Y-m-d H:i:s'),
        'range_days' => $dateFrom->diffInDays($dateTo),
        'has_date_filter' => $hasDateFilter
    ]);

    // =============================================
    // REQUESTS PER PROGRAM
    // =============================================
    $requestsPerProgram = DB::table('programs')
        ->leftJoin('dv_requests', function($join) use ($dateFrom, $dateTo, $hasDateFilter) {
            $join->on('dv_requests.program_id', '=', 'programs.id');
            
            // Apply date filter if we have valid dates
            if ($hasDateFilter) {
                $join->whereBetween('dv_requests.created_at', [$dateFrom, $dateTo]);
            }
        })
        ->select('programs.prcode', DB::raw('COUNT(dv_requests.id) as total'))
        ->groupBy('programs.prcode')
        ->pluck('total', 'prcode');

    // Order: TUPAD, GIP, TEV
    $orderedPrograms = collect([
        'TUPAD' => $requestsPerProgram->get('TUPAD', 0),
        'GIP'   => $requestsPerProgram->get('GIP', 0),
        'TEV'   => $requestsPerProgram->get('TEV', 0),
    ]);

    // =============================================
    // KEY PERFORMANCE INDICATORS (KPIs)
    // =============================================
    // Total requests based on creation date
    $totalRequestsQuery = DB::table('dv_requests');
    
    // Pending: created in date range AND not yet submitted
    $pendingRequestsQuery = DB::table('dv_requests')->whereNot('status', 'submitted');
    
    // **FIX: Approved requests - check BOTH created_at AND updated_at**
    // A request is "approved in this period" if it was released/approved during this period
    $approvedRequestsQuery = DB::table('dv_requests')
        ->where('status', 'Cashier_released');
    
    // **FIX: Rejected requests - check BOTH created_at AND updated_at**
    $rejectedRequestsQuery = DB::table('dv_requests')
        ->where('status', 'like', '%_rejected');

    // Apply date filter if provided
    if ($hasDateFilter) {
        // Total: created in this period
        $totalRequestsQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
        
        // Pending: created in this period
        $pendingRequestsQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
        
        // **FIX: Approved - released/updated in this period OR created in this period**
        // This ensures we capture requests released during the selected period
        $approvedRequestsQuery->where(function($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('updated_at', [$dateFrom, $dateTo]);
        });
        
        // **FIX: Rejected - same logic**
        $rejectedRequestsQuery->where(function($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('updated_at', [$dateFrom, $dateTo]);
        });
    }

    $totalRequests = $totalRequestsQuery->count();
    $pendingRequests = $pendingRequestsQuery->count();
    $approvedRequests = $approvedRequestsQuery->count();
    $rejectedRequests = $rejectedRequestsQuery->count();

    // Average processing time in minutes for requests within date range
    $averageProcessingTimeQuery = DB::table('dv_requests')
        ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_minutes'))
        ->where('status', 'Cashier_released');

    if ($hasDateFilter) {
        // **FIX: Check updated_at for completed requests**
        $averageProcessingTimeQuery->whereBetween('updated_at', [$dateFrom, $dateTo]);
    }

    $averageProcessingTime = $averageProcessingTimeQuery->value('avg_minutes') ?? 0;

    // =============================================
    // DOCUMENTS PER ROLE
    // =============================================
    $documentsPerRoleQuery = DB::table('dv_request_logs')
        ->join('accountingunits', 'dv_request_logs.accountingunit_id', '=', 'accountingunits.id')
        ->join('model_has_roles', 'accountingunits.id', '=', 'model_has_roles.model_id')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->select('roles.name as role_name', DB::raw('COUNT(DISTINCT dv_request_id) as total_documents'))
        ->whereNotNull('accountingunit_id')
        ->whereNotIn('roles.name', ['Focal_Person', 'ADMIN']);

    if ($hasDateFilter) {
        $documentsPerRoleQuery->whereBetween('dv_request_logs.created_at', [$dateFrom, $dateTo]);
    }

    $documentsPerRole = $documentsPerRoleQuery
        ->groupBy('roles.name')
        ->pluck('total_documents', 'role_name');

    // =============================================
    // MONTHLY REQUESTS TREND
    // =============================================
    $monthlyRequestsQuery = DB::table('dv_requests')
        ->select(
            DB::raw("DATE_FORMAT(created_at, '%M %Y') as month"),
            DB::raw('COUNT(*) as total')
        );

    if ($hasDateFilter) {
        $monthlyRequestsQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
    }

    $monthlyRequests = $monthlyRequestsQuery
        ->groupBy('month')
        ->orderByRaw("MIN(created_at)")
        ->pluck('total', 'month');

    // =============================================
    // BUDGET PER PROGRAM (OBLIGATED ONLY)
    // =============================================
    $budgetPerProgramQuery = DB::table('budget_obligation_history as h')
        ->join('dv_requests as d', 'h.dv_request_id', '=', 'd.id')
        ->join('programs as p', 'd.program_id', '=', 'p.id')
        ->where('h.status_after', 'ORS_obligated')
        ->where('h.verification_step', 'obligate')
        ->select('p.prcode', DB::raw('SUM(h.obligated_amount) as total_budget'));

    if ($hasDateFilter) {
        $budgetPerProgramQuery->whereBetween('h.created_at', [$dateFrom, $dateTo]);
    }

    $budgetPerProgram = $budgetPerProgramQuery
        ->groupBy('p.prcode')
        ->pluck('total_budget', 'p.prcode');

    // Order: TUPAD, GIP, TEV
    $orderedBudget = collect([
        'TUPAD' => $budgetPerProgram->get('TUPAD', 0),
        'GIP'   => $budgetPerProgram->get('GIP', 0),
        'TEV'   => $budgetPerProgram->get('TEV', 0),
    ]);
    
    // =============================================
    // STAFF PERFORMANCE - FIXED TO INCLUDE ZERO VALUES
    // =============================================
    
// Get all staff members but only show their highest priority non-admin role
$allStaff = DB::table('accountingunits as au')
    ->join('model_has_roles as mhr', 'au.id', '=', 'mhr.model_id')
    ->join('roles as r', 'mhr.role_id', '=', 'r.id')
    ->whereNotNull('au.id')
    ->whereNotIn('r.name', [
        'Focal_Person', 
        'ADMIN', 
        'Focal Person', 
        'Admin',
        'admin',
        'ADMINISTRATOR',
        'Administrator',
        'administrator'
    ])
    ->select(
        'au.id as user_id',
        'au.fname as first_name',
        'au.lname as last_name',
        'r.name as role',
        DB::raw("CASE 
            -- Highest priority roles first
            WHEN r.name = 'Regional_Director' THEN 1
            WHEN r.name = 'Accountant_III' THEN 2
            WHEN r.name = 'Budget_Officer' THEN 3
            WHEN r.name = 'IMSD' THEN 4
            WHEN r.name = 'IMDSD' THEN 4
            WHEN r.name = 'Cashier' THEN 5
            WHEN r.name LIKE 'Incoming-%' THEN 6
            WHEN r.name = 'Outgoing' THEN 7
            ELSE 8
        END as priority")
    )
    ->orderBy('au.id')
    ->orderBy('priority')
    ->get()
    ->groupBy('user_id')
    ->map(function($staffRecords) {
        // Take the highest priority role (lowest priority number)
        $bestRole = $staffRecords->sortBy('priority')->first();
        
        return (object)[
            'user_id' => $bestRole->user_id,
            'first_name' => $bestRole->first_name,
            'last_name' => $bestRole->last_name,
            'role' => $bestRole->role
        ];
    })
    ->values();

    // Then get processed documents count for each staff in the date range
    $processedCounts = DB::table('dv_request_logs')
        ->join('accountingunits', 'dv_request_logs.accountingunit_id', '=', 'accountingunits.id')
        ->select(
            'dv_request_logs.accountingunit_id',
            DB::raw('COUNT(DISTINCT dv_request_logs.dv_request_id) as processed_count')
        )
        ->whereNotNull('dv_request_logs.accountingunit_id');

    if ($hasDateFilter) {
        $processedCounts->whereBetween('dv_request_logs.created_at', [$dateFrom, $dateTo]);
    }

    $processedCounts = $processedCounts->groupBy('dv_request_logs.accountingunit_id')
        ->pluck('processed_count', 'accountingunit_id');

    // Calculate average processing time per staff (similar fix for zero values)
  // Calculate average processing time per staff (without additional date filter)
$avgProcessingTimes = DB::table(DB::raw('(
    SELECT 
        au.id as user_id,
        TIMESTAMPDIFF(MINUTE, 
            (SELECT MAX(created_at) 
             FROM dv_request_logs prev 
             WHERE prev.dv_request_id = current_log.dv_request_id 
             AND prev.created_at < current_log.created_at
             AND prev.accountingunit_id IS NOT NULL),
            current_log.created_at
        ) as processing_minutes,
        current_log.accountingunit_id
    FROM dv_request_logs current_log
    INNER JOIN accountingunits au ON current_log.accountingunit_id = au.id
    WHERE current_log.accountingunit_id IS NOT NULL
) as processing_data'))
->select(
    'accountingunit_id',
    DB::raw('ROUND(AVG(
        CASE 
            WHEN processing_minutes BETWEEN 1 AND 480 
            THEN processing_minutes 
            ELSE NULL 
        END
    ) / 60.0, 2) as avg_hours')
)
->groupBy('accountingunit_id');

// Remove the date filter here since we're already filtering by staff who processed documents
$avgProcessingTimes = $avgProcessingTimes->pluck('avg_hours', 'accountingunit_id');

    // Combine all staff with their processed counts and average times (including zeros)
    $staffPerformance = $allStaff->map(function($staff) use ($processedCounts, $avgProcessingTimes) {
        $processedCount = $processedCounts->get($staff->user_id, 0);
        $avgHours = $processedCount > 0 ? $avgProcessingTimes->get($staff->user_id, 0) : 0;
        
        return (object)[
            'user_id' => $staff->user_id,
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'role' => $staff->role,
            'documents_processed' => $processedCount,
            'avg_processing_hours' => $avgHours
        ];
    })->sortByDesc('documents_processed')->values();

    // Monthly Staff Productivity Trend - FIXED to include months with zero activity
    $allMonthsInRange = $this->generateAllMonthsInRange($dateFrom, $dateTo);
    
    $monthlyProductivityData = DB::table('dv_request_logs')
        ->join('accountingunits', 'dv_request_logs.accountingunit_id', '=', 'accountingunits.id')
        ->select(
            DB::raw("DATE_FORMAT(dv_request_logs.created_at, '%Y-%m') as month"),
            DB::raw("DATE_FORMAT(dv_request_logs.created_at, '%M %Y') as month_label"),
            DB::raw('COUNT(DISTINCT dv_request_logs.dv_request_id) as total_processed'),
            DB::raw('COUNT(DISTINCT dv_request_logs.accountingunit_id) as active_staff')
        )
        ->whereNotNull('dv_request_logs.accountingunit_id');

    if ($hasDateFilter) {
        $monthlyProductivityData->whereBetween('dv_request_logs.created_at', [$dateFrom, $dateTo]);
    }

    $monthlyProductivityData = $monthlyProductivityData
        ->groupBy('month', 'month_label')
        ->orderBy('month')
        ->get()
        ->keyBy('month');

    // Merge with all months to ensure every month appears (with zeros if no data)
    $monthlyStaffProductivity = collect();
    foreach ($allMonthsInRange as $month => $monthLabel) {
        if ($monthlyProductivityData->has($month)) {
            $monthlyStaffProductivity->push($monthlyProductivityData->get($month));
        } else {
            // Add zero data for this month
            $monthlyStaffProductivity->push((object)[
                'month' => $month,
                'month_label' => $monthLabel,
                'total_processed' => 0,
                'active_staff' => 0
            ]);
        }
    }

    // Staff Workload Distribution by Role - FIXED to include roles with zero documents
    $workloadData = DB::table('roles as r')
        ->leftJoin('model_has_roles as mhr', 'r.id', '=', 'mhr.role_id')
        ->leftJoin('accountingunits as au', 'mhr.model_id', '=', 'au.id')
        ->leftJoin('dv_request_logs as logs', function($join) use ($dateFrom, $dateTo, $hasDateFilter) {
            $join->on('au.id', '=', 'logs.accountingunit_id');
            if ($hasDateFilter) {
                $join->whereBetween('logs.created_at', [$dateFrom, $dateTo]);
            }
        })
        ->whereNotIn('r.name', ['Focal_Person', 'ADMIN', 'Focal Person', 'Admin'])
        ->select(
            'r.name as role',
            DB::raw('COUNT(DISTINCT au.id) as staff_count'),
            DB::raw('COUNT(DISTINCT logs.dv_request_id) as total_documents'),
            DB::raw('CASE 
                WHEN COUNT(DISTINCT au.id) > 0 
                THEN ROUND(COUNT(DISTINCT logs.dv_request_id) / COUNT(DISTINCT au.id), 2) 
                ELSE 0 
            END as avg_docs_per_staff')
        )
        ->groupBy('r.name')
        ->orderByDesc('total_documents')
        ->get();

    $workloadByRole = $workloadData;

    // Top Performers - FIXED to include only those with actual processing time
    $topPerformersQuery = DB::table(DB::raw('(
        SELECT 
            au.id as user_id,
            CONCAT(au.fname, \' \', au.lname) as staff_name,
            current_log.dv_request_id,
            current_log.created_at as log_created_at,
            TIMESTAMPDIFF(MINUTE,
                (SELECT MAX(created_at) 
                 FROM dv_request_logs prev 
                 WHERE prev.dv_request_id = current_log.dv_request_id 
                 AND prev.created_at < current_log.created_at
                 AND prev.accountingunit_id IS NOT NULL),
                current_log.created_at
            ) as processing_minutes
        FROM dv_request_logs current_log
        INNER JOIN accountingunits au ON current_log.accountingunit_id = au.id
        WHERE current_log.accountingunit_id IS NOT NULL
    ) as processing_data'))
    ->select(
        'staff_name',
        DB::raw('COUNT(DISTINCT dv_request_id) as documents_handled'),
        DB::raw('ROUND(AVG(
            CASE 
                WHEN processing_minutes BETWEEN 1 AND 480
                THEN processing_minutes 
                ELSE NULL 
            END
        ) / 60.0, 2) as avg_processing_hours')
    )
    ->groupBy('user_id', 'staff_name')
    ->having('documents_handled', '>=', 5) // Only include staff with at least 5 documents
    ->havingRaw('avg_processing_hours > 0 AND avg_processing_hours < 100');

    // Apply date filter to top performers
    if ($hasDateFilter) {
        $topPerformersQuery->whereBetween('log_created_at', [$dateFrom, $dateTo]);
    }

    $topPerformers = $topPerformersQuery
        ->orderBy('avg_processing_hours', 'asc')
        ->limit(10)
        ->get();
// =============================================
// ROLE-BASED PENDING BOTTLENECK ANALYSIS 
// USING VERIFIED_AT AND RECEIVED_AT TIMESTAMPS
// =============================================
$rejectedStatuses = [
    'IMSD_rejected', 
    'Incoming-GIP_rejected', 
    'Incoming-TUPAD_rejected', 
    'Incoming-TEV_rejected', 
    'Budget_Officer_rejected',
    'ORS_rejected',
    'Outgoing_rejected',
    'Accountant_III_rejected',
    'RANCA_rejected',
    'Regional_Director_rejected',
    'Cashier_rejected'
];

// Define excluded roles
$excludedStatuses = [
    'Regional_Director_received', 
    'Cashier_in_process', 
    'Cashier_released'
];

// Define role-based thresholds with proper timestamp fields
$roleThresholds = [
    'IMSD' => [
        'statuses' => ['submitted', 'IMSD_received', 'IMSD_in_process'],
        'threshold' => 2,
        'color' => '#ef4444',
        'timestamp_field' => 'created_at'
    ],
    'Incoming-GIP' => [
        'statuses' => ['IMSD_verified', 'Incoming-GIP_received', 'Incoming-GIP_verified'],
        'threshold' => 2,
        'color' => '#3b82f6',
        'timestamp_field' => 'imsd_verified_at'
    ],
    'Incoming-TUPAD' => [
        'statuses' => ['IMSD_verified', 'Incoming-TUPAD_received', 'Incoming-TUPAD_verified'],
        'threshold' => 2,
        'color' => '#10b981',
        'timestamp_field' => 'imsd_verified_at'
    ],
    'Incoming-TEV' => [
        'statuses' => ['IMSD_verified', 'Incoming-TEV_received', 'Incoming-TEV_verified'],
        'threshold' => 2,
        'color' => '#f59e0b',
        'timestamp_field' => 'imsd_verified_at'
    ],
    'Budget_Officer' => [
        'statuses' => ['Incoming-GIP_verified', 'Incoming-TUPAD_verified', 'Incoming-TEV_verified', 'ORS_received', 'Budget_in_process', 'ORS_obligated'],
        'threshold' => 3,
        'color' => '#8b5cf6',
        'timestamp_field' => 'budget_received_at'
    ],
    'Outgoing' => [
        'statuses' => ['ORS_obligated', 'Outgoing_received', 'Outgoing_finalized'],
        'threshold' => 2,
        'color' => '#6366f1',
        'timestamp_field' => 'outgoing_received_at'
    ],
    'Accountant_III' => [
        'statuses' => ['Outgoing_finalized', 'RANCA_received'],
        'threshold' => 3,
        'color' => '#ec4899',
        'timestamp_field' => 'accountant_received_at'
    ],
];

// Calculate role-level summary with actual timestamps - FIXED TO INCLUDE ALL ROLES
$pendingData = DB::table('dv_requests as dr')
    ->join('programs as p', 'dr.program_id', '=', 'p.id')
    ->when($hasDateFilter, function ($query) use ($dateFrom, $dateTo) {
        $query->whereBetween('dr.created_at', [$dateFrom, $dateTo]);
    })
    ->select(
        DB::raw('CASE 
            WHEN dr.status IN ("submitted", "IMSD_received", "IMSD_in_process") THEN "IMSD"
            
            WHEN dr.status = "IMSD_verified" AND p.prcode = "GIP" THEN "Incoming-GIP"
            WHEN dr.status = "IMSD_verified" AND p.prcode = "TUPAD" THEN "Incoming-TUPAD"
            WHEN dr.status = "IMSD_verified" AND p.prcode = "TEV" THEN "Incoming-TEV"
            
            WHEN dr.status IN ("Incoming-GIP_received", "Incoming-GIP_verified") THEN "Incoming-GIP"
            WHEN dr.status IN ("Incoming-TUPAD_received", "Incoming-TUPAD_verified") THEN "Incoming-TUPAD"
            WHEN dr.status IN ("Incoming-TEV_received", "Incoming-TEV_verified") THEN "Incoming-TEV"
            
            WHEN dr.status IN ("ORS_received", "Budget_in_process", "ORS_obligated") THEN "Budget_Officer"
            
            WHEN dr.status IN ("Outgoing_received", "Outgoing_finalized") THEN "Outgoing"
            
            WHEN dr.status = "RANCA_received" THEN "Accountant_III"
            
            WHEN dr.status = "Regional_Director_received" THEN "Regional_Director"
            
            WHEN dr.status IN ("Cashier_in_process", "Cashier_released") THEN "Cashier"
            
            ELSE NULL
        END as role'),
        
        DB::raw('COUNT(*) as total_pending'),
        
        // Calculate average wait time using the appropriate timestamps
        DB::raw('ROUND(AVG(
            CASE 
                -- IMSD Role
                WHEN dr.status IN ("submitted", "IMSD_received", "IMSD_in_process") 
                    THEN TIMESTAMPDIFF(DAY, dr.created_at, NOW())
                
                -- IMSD Verified (waiting for Incoming)
                WHEN dr.status = "IMSD_verified" 
                    THEN TIMESTAMPDIFF(DAY, dr.imsd_verified_at, NOW())
                
                -- Incoming Roles
                WHEN dr.status = "Incoming-GIP_received" 
                    THEN TIMESTAMPDIFF(DAY, dr.gip_received_at, NOW())
                WHEN dr.status = "Incoming-GIP_verified" 
                    THEN TIMESTAMPDIFF(DAY, dr.gip_verified_at, NOW())
                WHEN dr.status = "Incoming-TUPAD_received" 
                    THEN TIMESTAMPDIFF(DAY, dr.tupad_received_at, NOW())
                WHEN dr.status = "Incoming-TUPAD_verified" 
                    THEN TIMESTAMPDIFF(DAY, dr.tupad_verified_at, NOW())
                WHEN dr.status = "Incoming-TEV_received" 
                    THEN TIMESTAMPDIFF(DAY, dr.tev_received_at, NOW())
                WHEN dr.status = "Incoming-TEV_verified" 
                    THEN TIMESTAMPDIFF(DAY, dr.tev_verified_at, NOW())
                
                -- Budget Officer
                WHEN dr.status IN ("ORS_received", "Budget_in_process") 
                    THEN TIMESTAMPDIFF(DAY, dr.budget_received_at, NOW())
                WHEN dr.status = "ORS_obligated" 
                    THEN TIMESTAMPDIFF(DAY, dr.budget_verified_at, NOW())
                
                -- Outgoing
                WHEN dr.status = "Outgoing_received" 
                    THEN TIMESTAMPDIFF(DAY, dr.outgoing_received_at, NOW())
                WHEN dr.status = "Outgoing_finalized" 
                    THEN TIMESTAMPDIFF(DAY, dr.outgoing_verified_at, NOW())
                
                -- Accountant
                WHEN dr.status = "RANCA_received" 
                    THEN TIMESTAMPDIFF(DAY, dr.accountant_received_at, NOW())
                
                ELSE TIMESTAMPDIFF(DAY, dr.updated_at, NOW())
            END
        ), 1) as avg_wait_days'),
        
        // Count requests that exceeded SLA (over threshold)
        DB::raw('SUM(CASE 
            WHEN dr.status IN ("submitted", "IMSD_received", "IMSD_in_process") 
                AND TIMESTAMPDIFF(DAY, dr.created_at, NOW()) > 3 THEN 1
                
            WHEN dr.status = "IMSD_verified" 
                AND TIMESTAMPDIFF(DAY, dr.imsd_verified_at, NOW()) > 2 THEN 1
                
            WHEN dr.status IN ("Incoming-GIP_received", "Incoming-TUPAD_received", "Incoming-TEV_received") 
                AND TIMESTAMPDIFF(DAY, 
                    CASE 
                        WHEN dr.status = "Incoming-GIP_received" THEN dr.gip_received_at
                        WHEN dr.status = "Incoming-TUPAD_received" THEN dr.tupad_received_at
                        WHEN dr.status = "Incoming-TEV_received" THEN dr.tev_received_at
                    END, NOW()) > 3 THEN 1
                
            WHEN dr.status IN ("Incoming-GIP_verified", "Incoming-TUPAD_verified", "Incoming-TEV_verified") 
                AND TIMESTAMPDIFF(DAY, 
                    CASE 
                        WHEN dr.status = "Incoming-GIP_verified" THEN dr.gip_verified_at
                        WHEN dr.status = "Incoming-TUPAD_verified" THEN dr.tupad_verified_at
                        WHEN dr.status = "Incoming-TEV_verified" THEN dr.tev_verified_at
                    END, NOW()) > 2 THEN 1
                
            WHEN dr.status IN ("ORS_received", "Budget_in_process") 
                AND TIMESTAMPDIFF(DAY, dr.budget_received_at, NOW()) > 3 THEN 1
                
            WHEN dr.status = "ORS_obligated" 
                AND TIMESTAMPDIFF(DAY, dr.budget_verified_at, NOW()) > 3 THEN 1
                
            WHEN dr.status = "Outgoing_received" 
                AND TIMESTAMPDIFF(DAY, dr.outgoing_received_at, NOW()) > 3 THEN 1
                
            WHEN dr.status = "Outgoing_finalized" 
                AND TIMESTAMPDIFF(DAY, dr.outgoing_verified_at, NOW()) > 2 THEN 1
                
            WHEN dr.status = "RANCA_received" 
                AND TIMESTAMPDIFF(DAY, dr.accountant_received_at, NOW()) > 3 THEN 1
                
            ELSE 0
        END) as exceeded_sla_count')
    )
    ->whereNotNull(DB::raw('CASE 
        WHEN dr.status IN ("submitted", "IMSD_received", "IMSD_in_process") THEN "IMSD"
        WHEN dr.status = "IMSD_verified" AND p.prcode = "GIP" THEN "Incoming-GIP"
        WHEN dr.status = "IMSD_verified" AND p.prcode = "TUPAD" THEN "Incoming-TUPAD"
        WHEN dr.status = "IMSD_verified" AND p.prcode = "TEV" THEN "Incoming-TEV"
        WHEN dr.status IN ("Incoming-GIP_received", "Incoming-GIP_verified") THEN "Incoming-GIP"
        WHEN dr.status IN ("Incoming-TUPAD_received", "Incoming-TUPAD_verified") THEN "Incoming-TUPAD"
        WHEN dr.status IN ("Incoming-TEV_received", "Incoming-TEV_verified") THEN "Incoming-TEV"
        WHEN dr.status IN ("ORS_received", "Budget_in_process", "ORS_obligated") THEN "Budget_Officer"
        WHEN dr.status IN ("Outgoing_received", "Outgoing_finalized") THEN "Outgoing"
        WHEN dr.status = "RANCA_received" THEN "Accountant_III"
        WHEN dr.status = "Regional_Director_received" THEN "Regional_Director"
        WHEN dr.status IN ("Cashier_in_process", "Cashier_released") THEN "Cashier"
        ELSE NULL
    END'))
    ->whereNotIn('dr.status', [
        'Cashier_released',
        'IMSD_rejected', 
        'Incoming-GIP_rejected', 
        'Incoming-TUPAD_rejected', 
        'Incoming-TEV_rejected', 
        'Budget_Officer_rejected', 
        'Accountant_III_rejected',
        'Regional_Director_rejected',
        'Cashier_rejected'
    ])
    ->groupBy('role')
    ->get()
    ->keyBy('role');

// Create a collection with all roles (including zeros)
$allRoles = collect(array_keys($roleThresholds));
$pendingByRole = $allRoles->map(function($role) use ($pendingData, $roleThresholds) {
    if ($pendingData->has($role)) {
        return $pendingData->get($role);
    } else {
        // Create a zero-data entry for this role
        return (object)[
            'role' => $role,
            'total_pending' => 0,
            'avg_wait_days' => 0,
            'exceeded_sla_count' => 0,
            'threshold' => $roleThresholds[$role]['threshold'] ?? 3,
            'color' => $roleThresholds[$role]['color'] ?? '#6b7280',
            'critical_count' => 0
        ];
    }
})->sortByDesc('total_pending')->values();

// Add colors, thresholds, and critical count to each role (for the zero entries that need it)
foreach ($pendingByRole as $role) {
    if (isset($roleThresholds[$role->role])) {
        $role->threshold = $roleThresholds[$role->role]['threshold'];
        $role->color = $roleThresholds[$role->role]['color'];
        $criticalThreshold = $role->threshold * 2;
        
        // Only calculate critical count if there are pending requests
        if ($role->total_pending > 0) {
            // Define excluded statuses for this query
            $excludedForCritical = array_merge($rejectedStatuses, $excludedStatuses);

            // Build critical count query based on role
        $criticalCountQuery = DB::table('dv_requests as dr')
            ->join('programs as p', 'dr.program_id', '=', 'p.id')
            ->whereNotIn('dr.status', $excludedForCritical)
            ->when($hasDateFilter, function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('dr.created_at', [$dateFrom, $dateTo]);
            });

            switch ($role->role) {
                case 'IMSD':
                    $criticalCountQuery->whereIn('dr.status', ['submitted', 'IMSD_received', 'IMSD_in_process'])
                        ->whereRaw('TIMESTAMPDIFF(DAY, dr.created_at, NOW()) >= ?', [$criticalThreshold]);
                    break;
                    
                case 'Incoming-GIP':
                    $criticalCountQuery->where(function($q) use ($criticalThreshold) {
                        $q->where(function($q2) {
                            $q2->where('dr.status', 'IMSD_verified')
                               ->where('p.prcode', 'GIP');
                        })->orWhereIn('dr.status', ['Incoming-GIP_received', 'Incoming-GIP_verified']);
                    })
                    ->whereRaw('CASE 
                        WHEN dr.status = "IMSD_verified" AND p.prcode = "GIP" THEN TIMESTAMPDIFF(DAY, dr.imsd_verified_at, NOW())
                        WHEN dr.status = "Incoming-GIP_received" THEN TIMESTAMPDIFF(DAY, dr.gip_received_at, NOW())
                        WHEN dr.status = "Incoming-GIP_verified" THEN TIMESTAMPDIFF(DAY, dr.gip_verified_at, NOW())
                        ELSE 0
                    END >= ?', [$criticalThreshold]);
                    break;
                    
                case 'Incoming-TUPAD':
                    $criticalCountQuery->where(function($q) use ($criticalThreshold) {
                        $q->where(function($q2) {
                            $q2->where('dr.status', 'IMSD_verified')
                               ->where('p.prcode', 'TUPAD');
                        })->orWhereIn('dr.status', ['Incoming-TUPAD_received', 'Incoming-TUPAD_verified']);
                    })
                    ->whereRaw('CASE 
                        WHEN dr.status = "IMSD_verified" AND p.prcode = "TUPAD" THEN TIMESTAMPDIFF(DAY, dr.imsd_verified_at, NOW())
                        WHEN dr.status = "Incoming-TUPAD_received" THEN TIMESTAMPDIFF(DAY, dr.tupad_received_at, NOW())
                        WHEN dr.status = "Incoming-TUPAD_verified" THEN TIMESTAMPDIFF(DAY, dr.tupad_verified_at, NOW())
                        ELSE 0
                    END >= ?', [$criticalThreshold]);
                    break;
                    
                case 'Incoming-TEV':
                    $criticalCountQuery->where(function($q) use ($criticalThreshold) {
                        $q->where(function($q2) {
                            $q2->where('dr.status', 'IMSD_verified')
                               ->where('p.prcode', 'TEV');
                        })->orWhereIn('dr.status', ['Incoming-TEV_received', 'Incoming-TEV_verified']);
                    })
                    ->whereRaw('CASE 
                        WHEN dr.status = "IMSD_verified" AND p.prcode = "TEV" THEN TIMESTAMPDIFF(DAY, dr.imsd_verified_at, NOW())
                        WHEN dr.status = "Incoming-TEV_received" THEN TIMESTAMPDIFF(DAY, dr.tev_received_at, NOW())
                        WHEN dr.status = "Incoming-TEV_verified" THEN TIMESTAMPDIFF(DAY, dr.tev_verified_at, NOW())
                        ELSE 0
                    END >= ?', [$criticalThreshold]);
                    break;
                    
                case 'Budget_Officer':
                    $criticalCountQuery->whereIn('dr.status', ['ORS_received', 'Budget_in_process', 'ORS_obligated'])
                        ->whereRaw('CASE 
                            WHEN dr.status IN ("ORS_received", "Budget_in_process") THEN TIMESTAMPDIFF(DAY, dr.budget_received_at, NOW())
                            WHEN dr.status = "ORS_obligated" THEN TIMESTAMPDIFF(DAY, dr.budget_verified_at, NOW())
                            ELSE 0
                        END >= ?', [$criticalThreshold]);
                    break;
                    
                case 'Outgoing':
                    $criticalCountQuery->whereIn('dr.status', ['Outgoing_received', 'Outgoing_finalized'])
                        ->whereRaw('CASE 
                            WHEN dr.status = "Outgoing_received" THEN TIMESTAMPDIFF(DAY, dr.outgoing_received_at, NOW())
                            WHEN dr.status = "Outgoing_finalized" THEN TIMESTAMPDIFF(DAY, dr.outgoing_verified_at, NOW())
                            ELSE 0
                        END >= ?', [$criticalThreshold]);
                    break;
                    
                case 'Accountant_III':
                    $criticalCountQuery->where('dr.status', 'RANCA_received')
                        ->whereRaw('TIMESTAMPDIFF(DAY, dr.accountant_received_at, NOW()) >= ?', [$criticalThreshold]);
                    break;
            }

            $role->critical_count = $criticalCountQuery->count();
        } else {
            $role->critical_count = 0;
        }
    } else {
        $role->threshold = 5;
        $role->color = '#6b7280';
        $role->critical_count = 0;
    }
}

// Calculate threshold metrics
$thresholdMetrics = [
    'total_pending' => $pendingByRole->sum('total_pending'),
    'total_delayed' => $pendingByRole->sum('exceeded_sla_count'),
    'total_critical' => $pendingByRole->sum('critical_count'),
    'delay_rate' => $pendingByRole->sum('total_pending') > 0 
        ? round(($pendingByRole->sum('exceeded_sla_count') / $pendingByRole->sum('total_pending')) * 100, 1) 
        : 0
];
    // Staff Performance Summary & Insights
    $insights = [];
    
    if ($staffPerformance->isNotEmpty()) {
        $topStaff = $staffPerformance->first();
        if ($topStaff->documents_processed > 0) {
            $insights[] = "Top performer: <span class='font-semibold'>{$topStaff->first_name} {$topStaff->last_name}</span> ({$topStaff->role}) processed {$topStaff->documents_processed} documents.";
        } else {
            $insights[] = "No documents processed in this period.";
        }
        
        $avgDocsPerStaff = $staffPerformance->avg('documents_processed');
        $insights[] = "Average documents per staff: <span class='font-semibold'>" . round($avgDocsPerStaff, 2) . "</span>.";
        $insights[] = "Total active staff: <span class='font-semibold'>" . $staffPerformance->count() . "</span>.";
        
        if ($topPerformers->isNotEmpty()) {
            $fastest = $topPerformers->first();
            $Insights[] = "Fastest processor: <span class='font-semibold'>{$fastest->staff_name}</span> with avg {$fastest->avg_processing_hours} hours per document.";
        }
    }
            
// =============================================
// 10-DAY COMPLIANCE ANALYSIS (FROM IMSD RECEIVED TO ACCOUNTANT VERIFIED)
// USING ACTUAL TIMESTAMPS FROM YOUR MIGRATION
// =============================================
  $excludedRoles = ['submitted', 'focal_person', 'Focal_person', 'Focal Person', 'admin', 'Admin', 'Administrator'];
// Get all completed requests (reached Accountant verified) within date range
$completedRequestsQuery = DB::table('dv_requests')
    ->select(
        'id as dv_request_id',
        'imsd_received_at',
        'accountant_verified_at',
        DB::raw('TIMESTAMPDIFF(DAY, imsd_received_at, accountant_verified_at) as total_days'),
        DB::raw('TIMESTAMPDIFF(HOUR, imsd_received_at, accountant_verified_at) as total_hours')
    )
    ->whereNotNull('imsd_received_at')
    ->whereNotNull('accountant_verified_at');

// Filter by completion date (accountant_verified_at)
if ($hasDateFilter) {
    $completedRequestsQuery->whereBetween('accountant_verified_at', [$dateFrom, $dateTo]);
}

$completedRequests = $completedRequestsQuery->get();

// Calculate compliance metrics
$slaData = [];
$totalViolations = 0;
$totalDays = 0;
$violationDays = 0;

foreach ($completedRequests as $request) {
    // Calculate actual days (including weekends) - 10 calendar days compliance
    $totalDaysSpent = $request->total_days;
    $isViolation = $totalDaysSpent > 10;
    
    $slaData[] = [
        'dv_request_id' => $request->dv_request_id,
        'imsd_received' => $request->imsd_received_at,
        'accountant_verified' => $request->accountant_verified_at,
        'total_days' => $totalDaysSpent,
        'total_hours' => $request->total_hours,
        'sla_status' => $isViolation ? 'VIOLATION' : 'WITHIN SLA',
        'days_over' => $isViolation ? $totalDaysSpent - 10 : 0
    ];
    
    $totalDays += $totalDaysSpent;
    if ($isViolation) {
        $totalViolations++;
        $violationDays += ($totalDaysSpent - 10);
    }
}

// SLA Summary - ONLY avg_working_days (no avg_processing_days)
$slaAnalysis = [
    'total_completed_requests' => count($slaData),
    'within_sla' => count($slaData) - $totalViolations,
    'sla_violations' => $totalViolations,
    'violation_rate_percent' => count($slaData) > 0 ? round(($totalViolations / count($slaData)) * 100, 2) : 0,
    'avg_working_days' => count($slaData) > 0 ? round($totalDays / count($slaData), 2) : 0,
    'avg_violation_days' => $totalViolations > 0 ? round($violationDays / $totalViolations, 2) : 0,
    'worst_performer' => count($slaData) > 0 ? collect($slaData)->max('total_days') : 0,
    'best_performer' => count($slaData) > 0 ? collect($slaData)->min('total_days') : 0,
];

// Add SLA insights
$slaInsights = [];
if ($slaAnalysis['violation_rate_percent'] > 0) {
    $slaInsights[] = "10-Day Compliance: <span class='text-red-600 font-semibold'>{$slaAnalysis['violation_rate_percent']}%</span> of requests exceeded 10 days.(avg {$slaAnalysis['avg_violation_days']} days over).";
} else {
    $slaInsights[] = "10-Day Compliance: <span class='text-green-600 font-semibold'>100%</span> of requests met 10-day SLA.";
}

if ($slaAnalysis['avg_working_days'] > 0) {
    $slaInsights[] = "Average processing time: <span class='font-semibold'>{$slaAnalysis['avg_working_days']} calendar days</span> from IMSD to Accountant III verification.";
}

// Merge with existing insights
$insights = array_merge($insights ?? [], $slaInsights);

    $bottlenecksQuery = DB::table(DB::raw('(
        SELECT 
            logs.*,
            logs.created_at as log_created_at,
            LAG(logs.created_at) OVER (PARTITION BY logs.dv_request_id ORDER BY logs.created_at) as prev_action_time,
            start_times.voucher_received_time,
            CASE
                WHEN logs.action = "IMSD" THEN "IMSD"
                WHEN logs.action = "Budget_Officer" THEN "Budget Officer"
                WHEN logs.action LIKE "Incoming-TEV%" AND logs.action NOT LIKE "%rejected%" THEN "Incoming-TEV" 
                WHEN logs.action LIKE "Incoming-GIP%" AND logs.action NOT LIKE "%rejected%" THEN "Incoming-GIP"
                WHEN logs.action LIKE "Incoming-TUPAD%" AND logs.action NOT LIKE "%rejected%" THEN "Incoming-TUPAD"
                WHEN logs.action = "Outgoing" THEN "Outgoing"
                WHEN logs.action = "Accountant_III" THEN "Accountant III"
                WHEN logs.action = "Regional_Director" THEN "Regional Director" 
                WHEN logs.action = "Cashier" THEN "Cashier"
                ELSE NULL
            END as role_name
        FROM dv_request_logs logs
        INNER JOIN (
            SELECT dv_request_id, MIN(created_at) as voucher_received_time
            FROM dv_request_logs
            WHERE action NOT IN (\'' . implode("','", $excludedRoles) . '\')
            GROUP BY dv_request_id
        ) start_times ON logs.dv_request_id = start_times.dv_request_id
        WHERE logs.action IS NOT NULL
        AND logs.action NOT IN (\'' . implode("','", $excludedRoles) . '\')
        AND logs.action NOT LIKE "%rejected%"
    ) as workflow'))
    ->select(
        'role_name',
        DB::raw('COUNT(DISTINCT dv_request_id) as total_processed'),
        DB::raw('ROUND(AVG(
            CASE 
                WHEN DATE(prev_action_time) != DATE(log_created_at) THEN NULL
                WHEN TIMESTAMPDIFF(HOUR, prev_action_time, log_created_at) > 10 THEN NULL
                WHEN TIMESTAMPDIFF(MINUTE, prev_action_time, log_created_at) BETWEEN 1 AND 480 
                THEN TIMESTAMPDIFF(MINUTE, prev_action_time, log_created_at)
                ELSE NULL
        END) / 60.0, 2) as avg_hours'),
        DB::raw('ROUND(AVG(TIMESTAMPDIFF(DAY, voucher_received_time, log_created_at)), 2) as avg_days_into_process')
    )
    ->whereNotNull('role_name')
    ->whereNotNull('prev_action_time')
    ->groupBy('role_name')
    ->having('total_processed', '>=', 1)
    ->having('avg_hours', '>', 0);

    // Apply date filter to bottlenecks
    if ($hasDateFilter) {
        $bottlenecksQuery->whereBetween('log_created_at', [$dateFrom, $dateTo]);
    }

    $bottlenecks = $bottlenecksQuery
        ->orderByDesc('avg_hours')
        ->limit(8)
        ->get();
    
    $topProgramQuery = DB::table('programs')
        ->leftJoin('dv_requests', function($join) use ($dateFrom, $dateTo, $hasDateFilter) {
            $join->on('dv_requests.program_id', '=', 'programs.id');
            
            if ($hasDateFilter) {
                $join->whereBetween('dv_requests.created_at', [$dateFrom, $dateTo]);
            }
        })
        ->select('programs.prcode', DB::raw('COUNT(dv_requests.id) as total'))
        ->groupBy('programs.prcode')
        ->orderByDesc('total');
    
    $topProgram = $topProgramQuery->first();
        
    // Calculate trend (current period vs previous period of same length)
    $periodDays = $dateFrom->diffInDays($dateTo);
    $previousPeriodStart = $dateFrom->copy()->subDays($periodDays + 1);
    $previousPeriodEnd = $dateFrom->copy()->subDay()->endOfDay();
    
    $previousPeriodCount = DB::table('dv_requests')
        ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
        ->count();
    
    // Calculate % trend (safe for division)
    if ($previousPeriodCount > 0) {
        $trend = round((($totalRequests - $previousPeriodCount) / $previousPeriodCount) * 100, 2);
    } else {
        $trend = 0; // avoid division by zero
    }
    
    // Build insights
    if ($topProgram && $topProgram->total > 0) {
        $insights[] = "{$topProgram->prcode} has the highest number of requests ({$topProgram->total}).";
    } elseif ($hasDateFilter && $totalRequests == 0) {
        $insights[] = "No requests found for the selected date range.";
    }
    
    if ($trend > 0 && $previousPeriodCount > 0) {
        $insights[] = "Requests increased by <span class='text-green-600 font-semibold'>{$trend}%</span> compared to previous period.";
    } elseif ($trend < 0 && $previousPeriodCount > 0) {
        $insights[] = "Requests decreased by <span class='text-red-600 font-semibold'>" . abs($trend) . "%</span> compared to previous period.";
    } else {
        $insights[] = "Requests remained stable compared to previous period.";
    }
    
    // Optional contextual stats
    if (!empty($pendingRequests) && $pendingRequests > 0) {
        $insights[] = "There are <span class='font-semibold'>{$pendingRequests}</span> requests still pending.";
    }
    
    if (!empty($approvedRequests) && $approvedRequests > 0) {
        $insights[] = "A total of <span class='font-semibold'>{$approvedRequests}</span> requests have been approved.";
    }

    // Add average processing time in days for display
    $avgProcessingDays = $averageProcessingTime > 0 ? round(($averageProcessingTime / 60) / 24, 1) : 0;
    $budgetAnalysis = $this->getBudgetAnalysisWithThresholds($dateFrom, $dateTo);
    
    // =============================================
    // RETURN ALL DATA
    // =============================================
    return [
        'requestsPerProgram'    => $orderedPrograms,
        'totalRequests'         => $totalRequests,
        'pendingRequests'       => $pendingRequests,
        'approvedRequests'      => $approvedRequests,
        'rejectedRequests'      => $rejectedRequests,
        'averageProcessingTime' => $avgProcessingDays, // Now in days for display
        'documentsPerRole'      => $documentsPerRole,
        'monthlyRequests'       => $monthlyRequests,
        'budgetPerProgram'      => $orderedBudget,
        'budgetAnalysis'        => $budgetAnalysis,
        'staffPerformance' => [
            'individual_metrics' => $staffPerformance,
            'monthly_productivity' => $monthlyStaffProductivity,
            'workload_by_role' => $workloadByRole,
            'top_performers' => $topPerformers,
            'insights' => $insights
        ],
        'bottlenecks'           => $bottlenecks,
       'pendingBottlenecks'    => $pendingBottlenecks ?? collect([]), // Add this line
        'pendingByRole' => $pendingByRole,                  // Role-based summary for cards
        'thresholdMetrics' => $thresholdMetrics,      
        'slaAnalysis'           => $slaAnalysis,
        'insights'              => $insights,
        'dateRange' => [
            'from' => $dateFrom->format('Y-m-d'),
            'to' => $dateTo->format('Y-m-d'),
            'from_formatted' => $dateFrom->format('M d, Y'),
            'to_formatted' => $dateTo->format('M d, Y'),
            'days' => $periodDays
        ],
        'debug' => [
            'date_range_applied' => $hasDateFilter,
            'query_date_from' => $dateFrom->format('Y-m-d H:i:s'),
            'query_date_to' => $dateTo->format('Y-m-d H:i:s'),
            'total_requests_query' => $totalRequests,
            'is_dummy_data' => false,
            'filtered_requests_count' => $totalRequests,
            'approved_count' => $approvedRequests,
            'sla_completed_count' => $slaAnalysis['total_completed_requests']
        ]
    ];
}

// Add this method to app/Traits/TriggersDashboardUpdate.php

/**
 * Get department threshold configuration for bottleneck analysis
 */
protected function getDepartmentThresholds()
{
    return [
        // IMSD Department - 3 days for department action
        'IMSD' => [
            'statuses' => ['submitted', 'IMSD_received', 'IMSD_in_process'],
            'threshold_days' => 3,
            'type' => 'department_action',
            'name' => 'IMSD'
        ],
        
        // Incoming GIP - 2 days for transition
        'Incoming-GIP (Transition)' => [
            'statuses' => ['IMSD_verified'],
            'program_filter' => ['GIP'],
            'threshold_days' => 2,
            'type' => 'transition',
            'name' => 'Incoming-GIP (Transition)'
        ],
        
        // Incoming GIP Received - 3 days for department action
        'Incoming-GIP' => [
            'statuses' => ['Incoming-GIP_received'],
            'program_filter' => ['GIP'],
            'threshold_days' => 3,
            'type' => 'department_action',
            'name' => 'Incoming-GIP'
        ],
        
        // Incoming TUPAD - 2 days for transition
        'Incoming-TUPAD (Transition)' => [
            'statuses' => ['IMSD_verified'],
            'program_filter' => ['TUPAD'],
            'threshold_days' => 2,
            'type' => 'transition',
            'name' => 'Incoming-TUPAD (Transition)'
        ],
        
        // Incoming TUPAD Received - 3 days for department action
        'Incoming-TUPAD' => [
            'statuses' => ['Incoming-TUPAD_received'],
            'program_filter' => ['TUPAD'],
            'threshold_days' => 3,
            'type' => 'department_action',
            'name' => 'Incoming-TUPAD'
        ],
        
        // Incoming TEV - 2 days for transition
        'Incoming-TEV (Transition)' => [
            'statuses' => ['IMSD_verified'],
            'program_filter' => ['TEV'],
            'threshold_days' => 2,
            'type' => 'transition',
            'name' => 'Incoming-TEV (Transition)'
        ],
        
        // Incoming TEV Received - 3 days for department action
        'Incoming-TEV' => [
            'statuses' => ['Incoming-TEV_received'],
            'program_filter' => ['TEV'],
            'threshold_days' => 3,
            'type' => 'department_action',
            'name' => 'Incoming-TEV'
        ],
        
        // Budget Officer Transition - 3 days
        'Budget Officer (Transition)' => [
            'statuses' => ['Incoming-GIP_verified', 'Incoming-TUPAD_verified', 'Incoming-TEV_verified'],
            'threshold_days' => 3,
            'type' => 'transition',
            'name' => 'Budget Officer (Transition)'
        ],
        
        // Budget Officer Received - 3 days for department action
        'Budget Officer' => [
            'statuses' => ['ORS_received', 'Budget_in_process'],
            'threshold_days' => 3,
            'type' => 'department_action',
            'name' => 'Budget Officer'
        ],
        
        // Outgoing Transition - 2 days
        'Outgoing (Transition)' => [
            'statuses' => ['ORS_obligated'],
            'threshold_days' => 2,
            'type' => 'transition',
            'name' => 'Outgoing (Transition)'
        ],
        
        // Outgoing Received - 3 days for department action
        'Outgoing' => [
            'statuses' => ['Outgoing_received'],
            'threshold_days' => 3,
            'type' => 'department_action',
            'name' => 'Outgoing'
        ],
        
        // Accountant Transition - 3 days
        'Accountant III (Transition)' => [
            'statuses' => ['Outgoing_finalized'],
            'threshold_days' => 3,
            'type' => 'transition',
            'name' => 'Accountant III (Transition)'
        ],
        
        // Accountant Received - 3 days for department action
        'Accountant III' => [
            'statuses' => ['RANCA_received'],
            'threshold_days' => 3,
            'type' => 'department_action',
            'name' => 'Accountant III'
        ],
        
        // Cashier - 2 days for final processing
        'Cashier' => [
            'statuses' => ['Cashier_in_process'],
            'threshold_days' => 2,
            'type' => 'department_action',
            'name' => 'Cashier'
        ],
        
        // Regional Director - 1 day for signature
        'Regional Director' => [
            'statuses' => ['Regional_Director_received'],
            'threshold_days' => 1,
            'type' => 'department_action',
            'name' => 'Regional Director'
        ],
    ];
}
/**
 * Helper method to generate all months in a date range
 */
private function generateAllMonthsInRange($startDate, $endDate)
{
    $months = [];
    $current = Carbon::parse($startDate)->startOfMonth();
    $end = Carbon::parse($endDate)->startOfMonth();
    
    while ($current->lte($end)) {
        $monthKey = $current->format('Y-m');
        $monthLabel = $current->format('F Y');
        $months[$monthKey] = $monthLabel;
        $current->addMonth();
    }
    
    return $months;
}
/**
 * Generate comprehensive dummy data for the entire dashboard
 */
protected function getDummyDashboardData($dateFrom = null, $dateTo = null)
{
    // Parse dates if provided
    $dateFrom = $dateFrom ? Carbon::parse($dateFrom) : now()->subMonths(3)->startOfDay();
    $dateTo = $dateTo ? Carbon::parse($dateTo) : now()->endOfDay();
    
    $periodDays = $dateFrom->diffInDays($dateTo);
    
    // =============================================
    // KPI CARDS DATA
    // =============================================
    $totalRequests = 187;
    $pendingRequests = 42;
    $approvedRequests = 128;
    $rejectedRequests = 17;
    
    // =============================================
    // REQUESTS PER PROGRAM
    // =============================================
    $requestsPerProgram = collect([
        'TUPAD' => 94,
        'GIP'   => 53,
        'TEV'   => 40
    ]);

    // =============================================
    // DOCUMENTS PER ROLE (ALL ROLES)
    // =============================================
    $documentsPerRole = collect([
        'IMSD' => 187,
        'Incoming-TUPAD' => 94,
        'Incoming-GIP' => 53,
        'Incoming-TEV' => 40,
        'Budget_Officer' => 145,
        'Outgoing' => 128,
        'Accountant_III' => 115,
        'Regional_Director' => 98,
        'Cashier' => 85
    ]);

    // =============================================
    // MONTHLY REQUESTS TREND
    // =============================================
    $monthlyRequests = collect([
        'January 2026' => 52,
        'February 2026' => 61,
        'March 2026' => 74
    ]);

    // =============================================
    // BUDGET PER PROGRAM (OBLIGATED)
    // =============================================
    $budgetPerProgram = collect([
        'TUPAD' => 1250000.00,
        'GIP'   => 980000.00,
        'TEV'   => 720000.00
    ]);

    // =============================================
    // BUDGET ANALYSIS WITH THRESHOLDS
    // =============================================
    $budgetAnalysis = [
        'programs' => [
            'TUPAD' => [
                'allocated_budget' => 1500000.00,
                'obligated_budget' => 1250000.00,
                'remaining_budget' => 250000.00,
                'request_count' => 94,
                'utilization_rate' => 83.33,
                'status' => 'WARNING',
                'alert_level' => 'warning',
                'description' => '⚠️ Budget utilization at 83.33% (₱1.25M of ₱1.5M). Monitor remaining ₱250K.',
                'allow_new_requests' => true
            ],
            'GIP' => [
                'allocated_budget' => 1000000.00,
                'obligated_budget' => 980000.00,
                'remaining_budget' => 20000.00,
                'request_count' => 53,
                'utilization_rate' => 98.00,
                'status' => 'CRITICAL',
                'alert_level' => 'critical',
                'description' => '🚨 CRITICAL: 98% utilized (₱980K of ₱1.0M). Only ₱20K remaining.',
                'allow_new_requests' => true
            ],
            'TEV' => [
                'allocated_budget' => 800000.00,
                'obligated_budget' => 720000.00,
                'remaining_budget' => 80000.00,
                'request_count' => 40,
                'utilization_rate' => 90.00,
                'status' => 'HIGH_RISK',
                'alert_level' => 'critical',
                'description' => '🔥 High risk: 90% utilized. Only ₱80K remaining.',
                'allow_new_requests' => true
            ]
        ],
        'overall_status' => [
            'status' => 'CRITICAL',
            'message' => 'Budget alert: GIP at 98% (CRITICAL), TEV at 90% (HIGH RISK). Immediate budget review required.'
        ],
        'recommendations' => [
            [
                'priority' => 'URGENT',
                'program' => 'GIP',
                'action' => 'STRICT MONITORING',
                'message' => 'GIP has reached 98% utilization. Review all pending requests and prioritize only critical ones.'
            ],
            [
                'priority' => 'HIGH',
                'program' => 'TEV',
                'action' => 'STRICT MONITORING',
                'message' => 'TEV at 90% utilization. Review all pending requests and prioritize only critical ones.'
            ],
            [
                'priority' => 'MEDIUM',
                'program' => 'TUPAD',
                'action' => 'MONITOR',
                'message' => 'TUPAD at 83.33% utilization. Expected to reach 90% soon based on current volume.'
            ],
            [
                'priority' => 'HIGH',
                'program' => 'ALL',
                'action' => 'EMERGENCY REVIEW',
                'message' => 'Budget situation critical. Schedule emergency budget review meeting immediately.'
            ]
        ]
    ];

    // =============================================
    // EXCEEDED DOCUMENTS DATA (FOR URGENT MODAL)
    // =============================================
    $exceededDocuments = $this->generateDummyExceededDocuments();
    
    $totalExceededCount = count($exceededDocuments);
    
    $exceededSummary = [
        'total' => $totalExceededCount,
        'imsd' => $exceededDocuments->where('department', 'IMSD')->count(),
        'incoming' => $exceededDocuments->whereIn('department', ['Incoming-GIP', 'Incoming-TUPAD', 'Incoming-TEV'])->count(),
        'incoming_gip' => $exceededDocuments->where('department', 'Incoming-GIP')->count(),
        'incoming_tupad' => $exceededDocuments->where('department', 'Incoming-TUPAD')->count(),
        'incoming_tev' => $exceededDocuments->where('department', 'Incoming-TEV')->count(),
        'budget_officer' => $exceededDocuments->where('department', 'Budget Officer')->count(),
        'outgoing' => $exceededDocuments->where('department', 'Outgoing')->count(),
        'accountant' => $exceededDocuments->where('department', 'Accountant')->count(),
        'urgent' => $exceededDocuments->where('is_urgent', true)->count(),
        'not_urgent' => $exceededDocuments->where('is_urgent', false)->count()
    ];

    // =============================================
    // PENDING BY ROLE (BOTTLENECK ANALYSIS)
    // =============================================
    $pendingByRole = collect([
        (object)[
            'role' => 'IMSD',
            'total_pending' => 5,
            'avg_wait_days' => 2.8,
            'exceeded_sla_count' => 0,
            'threshold' => 2,

            'critical_count' => 0
        ],
        (object)[
            'role' => 'Incoming-GIP',
            'total_pending' => 8,
            'avg_wait_days' => 2.5,
            'exceeded_sla_count' => 2,
            'threshold' => 2,
 
            'critical_count' => 0
        ],
        (object)[
            'role' => 'Incoming-TUPAD',
            'total_pending' => 10,
            'avg_wait_days' => 2.9,
            'exceeded_sla_count' => 3,
            'threshold' => 2,

            'critical_count' => 1
        ],
        (object)[
            'role' => 'Incoming-TEV',
            'total_pending' => 6,
            'avg_wait_days' => 2.2,
            'exceeded_sla_count' => 1,
            'threshold' => 2,

            'critical_count' => 0
        ],
        (object)[
            'role' => 'Budget_Officer',
            'total_pending' => 15,
            'avg_wait_days' => 4.2,
            'exceeded_sla_count' => 10,
            'threshold' => 3,

            'critical_count' => 5
        ],
        (object)[
            'role' => 'Outgoing',
            'total_pending' => 7,
            'avg_wait_days' => 2.0,
            'exceeded_sla_count' => 1,
            'threshold' => 2,

            'critical_count' => 0
        ],
        (object)[
            'role' => 'Accountant_III',
            'total_pending' => 9,
            'avg_wait_days' => 3.5,
            'exceeded_sla_count' => 3,
            'threshold' => 3,

            'critical_count' => 1
        ]
    ]);

    $thresholdMetrics = [
        'total_pending' => 67,
        'total_delayed' => 19,
        'total_critical' => 5,
        'delay_rate' => 28.4
    ];

    // =============================================
    // STAFF PERFORMANCE DATA (ALL ROLES)
    // =============================================
    $staffPerformance = $this->generateDummyStaffPerformance();
    $monthlyStaffProductivity = $this->generateDummyMonthlyProductivity();
    $workloadByRole = $this->generateDummyWorkloadByRole();
    $topPerformers = $this->generateDummyTopPerformers();
    $staffInsights = $this->generateDummyStaffInsights($staffPerformance, $topPerformers);

    // =============================================
    // BOTTLENECKS ANALYSIS
    // =============================================
    $bottlenecks = collect([
        (object)[
            'role_name' => 'Budget Officer',
            'total_processed' => 145,
            'avg_hours' => 6.5,
            'avg_days_into_process' => 3.0
        ],
        (object)[
            'role_name' => 'Accountant III',
            'total_processed' => 115,
            'avg_hours' => 5.0,
            'avg_days_into_process' => 4.2
        ],
        (object)[
            'role_name' => 'Regional Director',
            'total_processed' => 98,
            'avg_hours' => 4.5,
            'avg_days_into_process' => 5.8
        ],
        (object)[
            'role_name' => 'IMSD',
            'total_processed' => 187,
            'avg_hours' => 3.5,
            'avg_days_into_process' => 1.9
        ],
        (object)[
            'role_name' => 'Incoming-TUPAD',
            'total_processed' => 94,
            'avg_hours' => 2.4,
            'avg_days_into_process' => 1.1
        ],
        (object)[
            'role_name' => 'Incoming-GIP',
            'total_processed' => 53,
            'avg_hours' => 2.2,
            'avg_days_into_process' => 1.0
        ],
        (object)[
            'role_name' => 'Incoming-TEV',
            'total_processed' => 40,
            'avg_hours' => 2.0,
            'avg_days_into_process' => 0.9
        ],
        (object)[
            'role_name' => 'Outgoing',
            'total_processed' => 128,
            'avg_hours' => 2.6,
            'avg_days_into_process' => 7.0
        ],
        (object)[
            'role_name' => 'Cashier',
            'total_processed' => 85,
            'avg_hours' => 2.1,
            'avg_days_into_process' => 7.8
        ]
    ]);

    // =============================================
    // SLA ANALYSIS (10-DAY COMPLIANCE)
    // =============================================
    $slaAnalysis = [
        'total_completed_requests' => 128,
        'within_sla' => 92,
        'sla_violations' => 36,
        'violation_rate_percent' => 28.1,
        'avg_working_days' => 8.5,
        'avg_violation_days' => 4.1,
        'worst_performer' => 17,
        'best_performer' => 3
    ];

    // =============================================
    // TREND CALCULATIONS
    // =============================================
    $trend = [
        'total' => 18.5,
        'pending' => -3.2,
        'approved' => 22.1,
        'rejected' => -1.5
    ];

    // =============================================
    // INSIGHTS
    // =============================================
    $insights = [
        "<span class='text-green-600 font-semibold'>Performance Overview:</span> Total requests increased by 42% from January (52) to March (74).",
        "<span class='text-blue-600 font-semibold'>TUPAD</span> leads with 94 requests (50.3% of total).",
        "Budget Officer remains primary bottleneck with 15 pending documents (avg 4.2 days wait).",
        "<span class='text-red-600 font-semibold'>CRITICAL BUDGET ALERT:</span> GIP at 98% utilization (₱20K remaining).",
        "<span class='text-orange-600 font-semibold'>HIGH RISK:</span> TEV at 90% utilization - strict monitoring required.",
        "Turnaround Time violation rate at 28.1% (36 of 128 completed requests exceeded 10-day target).",
        "<span class='text-purple-600 font-semibold'>March 2026</span> saw highest volume with 74 requests - 19% increase from February.",
        "Maria Santos is top performer with 45 documents processed.",
        "Budget Officer shows highest processing time at 6.5 hours average.",
        "GIP program at CRITICAL budget - recommend immediate budget augmentation."
    ];

    // Add SLA insights
    $insights[] = "10-Day Compliance: <span class='" . ($slaAnalysis['violation_rate_percent'] > 20 ? 'text-red-600' : 'text-green-600') . " font-semibold'>{$slaAnalysis['violation_rate_percent']}%</span> violation rate.";
    $insights[] = "Average processing time: <span class='font-semibold'>{$slaAnalysis['avg_working_days']} calendar days</span> from IMSD receipt to Accountant verification.";

    // =============================================
    // RETURN ALL DATA
    // =============================================
    return [
        // KPI Cards
        'requestsPerProgram'    => $requestsPerProgram,
        'totalRequests'         => $totalRequests,
        'pendingRequests'       => $pendingRequests,
        'approvedRequests'      => $approvedRequests,
        'rejectedRequests'      => $rejectedRequests,
        'averageProcessingTime' => 8.5, // days
        'documentsPerRole'      => $documentsPerRole,
        'monthlyRequests'       => $monthlyRequests,
        'budgetPerProgram'      => $budgetPerProgram,
        'budgetAnalysis'        => $budgetAnalysis,
        'trends'                => $trend,
        
        // Urgent Modal Data
        'exceededDocuments'     => $exceededDocuments,
        'totalExceededCount'    => $totalExceededCount,
        'exceededSummary'       => $exceededSummary,
        
        // Staff Performance
        'staffPerformance' => [
            'individual_metrics' => $staffPerformance,
            'monthly_productivity' => $monthlyStaffProductivity,
            'workload_by_role' => $workloadByRole,
            'top_performers' => $topPerformers,
            'insights' => $staffInsights
        ],
        
        // Bottleneck Analysis
        'bottlenecks'           => $bottlenecks,
        'pendingByRole'         => $pendingByRole,
        'thresholdMetrics'      => $thresholdMetrics,
        
        // SLA Analysis
        'slaAnalysis'           => $slaAnalysis,
        
        // Insights
        'insights'              => $insights,
        
        // Date Range Info
        'dateRange' => [
            'from' => $dateFrom->format('Y-m-d'),
            'to' => $dateTo->format('Y-m-d'),
            'from_formatted' => $dateFrom->format('M d, Y'),
            'to_formatted' => $dateTo->format('M d, Y'),
            'days' => $periodDays
        ],
        
        // Debug Info
        'debug' => [
            'date_range_applied' => true,
            'query_date_from' => $dateFrom->format('Y-m-d H:i:s'),
            'query_date_to' => $dateTo->format('Y-m-d H:i:s'),
            'total_requests_query' => $totalRequests,
            'is_dummy_data' => true,
            'filtered_requests_count' => $totalRequests,
            'approved_count' => $approvedRequests,
            'sla_completed_count' => $slaAnalysis['total_completed_requests']
        ]
    ];
}

/**
 * Generate dummy exceeded documents for urgent modal
 */
private function generateDummyExceededDocuments()
{
    $focalPersons = ['Juan Dela Cruz', 'Maria Santos', 'Pedro Reyes', 'Ana Gonzales', 'Carlo Mendoza'];
    $programs = ['TUPAD', 'GIP', 'TEV'];
    $statuses = ['submitted', 'IMSD_received', 'IMSD_in_process', 'IMSD_verified', 'Incoming_received'];
    
    $documents = collect();
    
    // Generate 18 dummy documents
    for ($i = 1; $i <= 18; $i++) {
        $daysOld = rand(12, 22);
        $department = $this->getRandomDepartment();
        $program = $programs[array_rand($programs)];
        
        $documents->push((object)[
            'id' => 2000 + $i,
            'control_number' => 'DV-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
            'ors_number' => rand(0, 1) ? 'ORS-2026-' . rand(100, 999) : null,
            'program' => $program,
            'department' => $department,
            'days_old' => $daysOld,
            'status' => $statuses[array_rand($statuses)],
            'amount' => '₱' . number_format(rand(5000, 150000), 2),
            'focal' => $focalPersons[array_rand($focalPersons)],
            'is_urgent' => $daysOld > 18 ? true : (rand(1, 100) <= 35),
            'created_at' => now()->subDays($daysOld)->format('Y-m-d H:i:s')
        ]);
    }
    
    return $documents;
}

/**
 * Get random department
 */
private function getRandomDepartment()
{
    $departments = [
        'IMSD', 
        'Incoming-GIP', 
        'Incoming-TUPAD', 
        'Incoming-TEV', 
        'Budget Officer', 
        'Outgoing', 
        'Accountant'
    ];
    
    return $departments[array_rand($departments)];
}

/**
 * Generate dummy staff performance data
 */
private function generateDummyStaffPerformance()
{
    return collect([
        // IMSD Staff
        (object)[
            'user_id' => 101,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'role' => 'IMSD',
            'documents_processed' => 45,
            'avg_processing_hours' => 2.1
        ],
        (object)[
            'user_id' => 102,
            'first_name' => 'Roberto',
            'last_name' => 'Lim',
            'role' => 'IMSD',
            'documents_processed' => 38,
            'avg_processing_hours' => 2.7
        ],
        
        // Incoming Staff
        (object)[
            'user_id' => 201,
            'first_name' => 'Ramon',
            'last_name' => 'Garcia',
            'role' => 'Incoming-TUPAD',
            'documents_processed' => 42,
            'avg_processing_hours' => 2.4
        ],
        (object)[
            'user_id' => 202,
            'first_name' => 'Liza',
            'last_name' => 'Mendoza',
            'role' => 'Incoming-GIP',
            'documents_processed' => 28,
            'avg_processing_hours' => 2.2
        ],
        (object)[
            'user_id' => 203,
            'first_name' => 'Antonio',
            'last_name' => 'Ramos',
            'role' => 'Incoming-TEV',
            'documents_processed' => 24,
            'avg_processing_hours' => 2.0
        ],
        
        // Budget Officer
        (object)[
            'user_id' => 301,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'role' => 'Budget_Officer',
            'documents_processed' => 38,
            'avg_processing_hours' => 3.1
        ],
        (object)[
            'user_id' => 302,
            'first_name' => 'Lorna',
            'last_name' => 'Diaz',
            'role' => 'Budget_Officer',
            'documents_processed' => 32,
            'avg_processing_hours' => 3.6
        ],
        
        // Outgoing
        (object)[
            'user_id' => 401,
            'first_name' => 'Pedro',
            'last_name' => 'Reyes',
            'role' => 'Outgoing',
            'documents_processed' => 35,
            'avg_processing_hours' => 2.1
        ],
        
        // Accountant III
        (object)[
            'user_id' => 501,
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'role' => 'Accountant_III',
            'documents_processed' => 33,
            'avg_processing_hours' => 2.7
        ],
        (object)[
            'user_id' => 502,
            'first_name' => 'Carlo',
            'last_name' => 'Mendoza',
            'role' => 'Accountant_III',
            'documents_processed' => 28,
            'avg_processing_hours' => 3.0
        ],
        
        // Regional Director
        (object)[
            'user_id' => 601,
            'first_name' => 'Elena',
            'last_name' => 'Torres',
            'role' => 'Regional_Director',
            'documents_processed' => 24,
            'avg_processing_hours' => 4.2
        ],
        
        // Cashier
        (object)[
            'user_id' => 701,
            'first_name' => 'Carlos',
            'last_name' => 'Gonzales',
            'role' => 'Cashier',
            'documents_processed' => 30,
            'avg_processing_hours' => 1.8
        ]
    ]);
}

/**
 * Generate dummy monthly productivity
 */
private function generateDummyMonthlyProductivity()
{
    return collect([
        (object)[
            'month' => '2026-01',
            'month_label' => 'January 2026',
            'total_processed' => 245,
            'active_staff' => 12
        ],
        (object)[
            'month' => '2026-02',
            'month_label' => 'February 2026',
            'total_processed' => 268,
            'active_staff' => 12
        ],
        (object)[
            'month' => '2026-03',
            'month_label' => 'March 2026',
            'total_processed' => 292,
            'active_staff' => 13
        ]
    ]);
}

/**
 * Generate dummy workload by role
 */
private function generateDummyWorkloadByRole()
{
    return collect([
        (object)[
            'role' => 'IMSD',
            'staff_count' => 2,
            'total_documents' => 83,
            'avg_docs_per_staff' => 41.50
        ],
        (object)[
            'role' => 'Incoming-TUPAD',
            'staff_count' => 1,
            'total_documents' => 42,
            'avg_docs_per_staff' => 42.00
        ],
        (object)[
            'role' => 'Incoming-GIP',
            'staff_count' => 1,
            'total_documents' => 28,
            'avg_docs_per_staff' => 28.00
        ],
        (object)[
            'role' => 'Incoming-TEV',
            'staff_count' => 1,
            'total_documents' => 24,
            'avg_docs_per_staff' => 24.00
        ],
        (object)[
            'role' => 'Budget_Officer',
            'staff_count' => 2,
            'total_documents' => 70,
            'avg_docs_per_staff' => 35.00
        ],
        (object)[
            'role' => 'Outgoing',
            'staff_count' => 1,
            'total_documents' => 35,
            'avg_docs_per_staff' => 35.00
        ],
        (object)[
            'role' => 'Accountant_III',
            'staff_count' => 2,
            'total_documents' => 61,
            'avg_docs_per_staff' => 30.50
        ],
        (object)[
            'role' => 'Regional_Director',
            'staff_count' => 1,
            'total_documents' => 24,
            'avg_docs_per_staff' => 24.00
        ],
        (object)[
            'role' => 'Cashier',
            'staff_count' => 1,
            'total_documents' => 30,
            'avg_docs_per_staff' => 30.00
        ]
    ]);
}

/**
 * Generate dummy top performers
 */
private function generateDummyTopPerformers()
{
    return collect([
        (object)[
            'staff_name' => 'Maria Santos',
            'documents_handled' => 45,
            'avg_processing_hours' => 2.1
        ],
        (object)[
            'staff_name' => 'Carlos Gonzales',
            'documents_handled' => 30,
            'avg_processing_hours' => 1.8
        ],
        (object)[
            'staff_name' => 'Ramon Garcia',
            'documents_handled' => 42,
            'avg_processing_hours' => 2.4
        ],
        (object)[
            'staff_name' => 'Ana Reyes',
            'documents_handled' => 33,
            'avg_processing_hours' => 2.7
        ],
        (object)[
            'staff_name' => 'Pedro Reyes',
            'documents_handled' => 35,
            'avg_processing_hours' => 2.1
        ],
        (object)[
            'staff_name' => 'Juan Dela Cruz',
            'documents_handled' => 38,
            'avg_processing_hours' => 3.1
        ],
        (object)[
            'staff_name' => 'Antonio Ramos',
            'documents_handled' => 24,
            'avg_processing_hours' => 2.0
        ],
        (object)[
            'staff_name' => 'Liza Mendoza',
            'documents_handled' => 28,
            'avg_processing_hours' => 2.2
        ],
        (object)[
            'staff_name' => 'Roberto Lim',
            'documents_handled' => 38,
            'avg_processing_hours' => 2.7
        ],
        (object)[
            'staff_name' => 'Elena Torres',
            'documents_handled' => 24,
            'avg_processing_hours' => 4.2
        ]
    ]);
}

/**
 * Generate dummy staff insights
 */
private function generateDummyStaffInsights($staffPerformance, $topPerformers)
{
    $avgDocs = $staffPerformance->avg('documents_processed');
    $fastest = $topPerformers->sortBy('avg_processing_hours')->first();
    
    return [
        "Top performer: <span class='font-semibold'>Maria Santos</span> (IMSD) processed 45 documents.",
        "Average documents per staff: <span class='font-semibold'>" . round($avgDocs, 1) . "</span>.",
        "Fastest processor: <span class='font-semibold'>{$fastest->staff_name}</span> (Cashier) with avg {$fastest->avg_processing_hours} hours per document.",
        "Budget Officer role has highest workload with 35 documents per staff average.",
        "March 2026 saw highest productivity with 292 total documents processed.",
        "Incoming roles processed 94 initial documents with excellent turnaround (< 2.5 hours).",
        "Cashier maintains fastest processing at 1.8 hours average.",
        "IMSD staff handled 83 documents with 41.5 average per staff."
    ];
}

    /**
     * Validate date string
     */
    private function validateDate($date)
    {
        if (empty($date)) {
            return false;
        }
        
        try {
            Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            Log::error('Invalid date format in dashboard filter', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}