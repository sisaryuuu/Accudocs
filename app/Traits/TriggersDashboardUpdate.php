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

    // Add this method to the TriggersDashboardUpdate trait
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
  protected function getRealDashboardData($dateFrom = null, $dateTo = null)
{
    // Validate and parse dates
    $dateFrom = $this->validateDate($dateFrom) ? Carbon::parse($dateFrom)->startOfDay() : now()->subMonths(12);
    $dateTo = $this->validateDate($dateTo) ? Carbon::parse($dateTo)->endOfDay() : now()->endOfDay();
    
    // Ensure date range is valid
    if ($dateFrom->gt($dateTo)) {
        $temp = $dateFrom;
        $dateFrom = $dateTo;
        $dateTo = $temp;
    }
    
    // Log the date range being used for debugging
    Log::info('Dashboard data filtering', [
        'date_from' => $dateFrom->format('Y-m-d H:i:s'),
        'date_to' => $dateTo->format('Y-m-d H:i:s'),
        'range_days' => $dateFrom->diffInDays($dateTo)
    ]);

    // =============================================
    // REQUESTS PER PROGRAM
    // =============================================
    $requestsPerProgram = DB::table('programs')
        ->leftJoin('dv_requests', function($join) use ($dateFrom, $dateTo) {
            $join->on('dv_requests.program_id', '=', 'programs.id')
                 ->whereBetween('dv_requests.created_at', [$dateFrom, $dateTo]);
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
    $totalRequests = DB::table('dv_requests')
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->count();
    
    $pendingRequests = DB::table('dv_requests')
        ->where('status', 'submitted')
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->count();

    $approvedRequests = DB::table('dv_requests')
        ->where('status', 'Cashier_released')
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->count();

    $rejectedRequests = DB::table('dv_requests')
        ->where('status', 'like', '%_rejected')
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->count();

    // Average processing time in minutes for requests within date range
    $averageProcessingTime = DB::table('dv_requests')
        ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_minutes'))
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->value('avg_minutes') ?? 0;

    // =============================================
    // DOCUMENTS PER ROLE
    // =============================================
    $documentsPerRole = DB::table('dv_request_logs')
        ->join('accountingunits', 'dv_request_logs.accountingunit_id', '=', 'accountingunits.id')
        ->join('model_has_roles', 'accountingunits.id', '=', 'model_has_roles.model_id')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->select('roles.name as role_name', DB::raw('COUNT(DISTINCT dv_request_id) as total_documents'))
        ->whereNotNull('accountingunit_id')
        ->whereBetween('dv_request_logs.created_at', [$dateFrom, $dateTo])
        ->whereNotIn('roles.name', ['Focal_Person', 'ADMIN'])
        ->groupBy('roles.name')
        ->pluck('total_documents', 'role_name');

    // =============================================
    // MONTHLY REQUESTS TREND
    // =============================================
    $monthlyRequests = DB::table('dv_requests')
        ->select(
            DB::raw("DATE_FORMAT(created_at, '%M %Y') as month"),
            DB::raw('COUNT(*) as total')
        )
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->groupBy('month')
        ->orderByRaw("MIN(created_at)")
        ->pluck('total', 'month');

    // =============================================
    // BUDGET PER PROGRAM (OBLIGATED ONLY)
    // =============================================
    $budgetPerProgram = DB::table('budget_obligation_history as h')
        ->join('dv_requests as d', 'h.dv_request_id', '=', 'd.id')
        ->join('programs as p', 'd.program_id', '=', 'p.id')
        ->where('h.status_after', 'ORS_obligated')
        ->where('h.verification_step', 'obligate')
        ->whereBetween('h.created_at', [$dateFrom, $dateTo])
        ->select('p.prcode', DB::raw('SUM(h.obligated_amount) as total_budget'))
        ->groupBy('p.prcode')
        ->pluck('total_budget', 'p.prcode');

    // Order: TUPAD, GIP, TEV
    $orderedBudget = collect([
        'TUPAD' => $budgetPerProgram->get('TUPAD', 0),
        'GIP'   => $budgetPerProgram->get('GIP', 0),
        'TEV'   => $budgetPerProgram->get('TEV', 0),
    ]);
    
    // =============================================
    // STAFF PERFORMANCE
    // =============================================
    $staffPerformance = DB::table(DB::raw('(
        SELECT 
            au.id as user_id,
            au.fname as first_name,
            au.lname as last_name,
            roles.name as role,
            logs.dv_request_id,
            TIMESTAMPDIFF(MINUTE, 
                (SELECT MAX(created_at) 
                 FROM dv_request_logs prev 
                 WHERE prev.dv_request_id = logs.dv_request_id 
                 AND prev.created_at < logs.created_at
                 AND prev.accountingunit_id IS NOT NULL),
                logs.created_at
            ) as processing_minutes
        FROM dv_request_logs logs
        INNER JOIN accountingunits au ON logs.accountingunit_id = au.id
        INNER JOIN model_has_roles mhr ON au.id = mhr.model_id
        INNER JOIN roles ON mhr.role_id = roles.id
        WHERE logs.created_at BETWEEN ? AND ?
        AND logs.accountingunit_id IS NOT NULL
        AND roles.name NOT IN (\'Focal_Person\', \'ADMIN\', \'Focal Person\', \'Admin\')
    ) as processing_data'))
    ->select(
        'user_id',
        'first_name', 
        'last_name',
        'role',
        DB::raw('COUNT(DISTINCT dv_request_id) as documents_processed'),
        DB::raw('ROUND(AVG(
            CASE 
                WHEN processing_minutes BETWEEN 1 AND 480 
                THEN processing_minutes 
                ELSE NULL 
            END
        ) / 60.0, 2) as avg_processing_hours')
    )
    ->setBindings([$dateFrom, $dateTo])
    ->groupBy('user_id', 'first_name', 'last_name', 'role')
    ->having('documents_processed', '>=', 5)
    ->orderByDesc('documents_processed')
    ->get();

    // Monthly Staff Productivity Trend
    $monthlyStaffProductivity = DB::table('dv_request_logs')
        ->join('accountingunits', 'dv_request_logs.accountingunit_id', '=', 'accountingunits.id')
        ->select(
            DB::raw("DATE_FORMAT(dv_request_logs.created_at, '%Y-%m') as month"),
            DB::raw("DATE_FORMAT(dv_request_logs.created_at, '%M %Y') as month_label"),
            DB::raw('COUNT(DISTINCT dv_request_logs.dv_request_id) as total_processed'),
            DB::raw('COUNT(DISTINCT dv_request_logs.accountingunit_id) as active_staff')
        )
        ->whereBetween('dv_request_logs.created_at', [$dateFrom, $dateTo])
        ->whereNotNull('dv_request_logs.accountingunit_id')
        ->groupBy('month', 'month_label')
        ->orderBy('month')
        ->get();
    
    // Staff Workload Distribution by Role
    $workloadByRole = DB::table('dv_request_logs')
        ->join('accountingunits', 'dv_request_logs.accountingunit_id', '=', 'accountingunits.id')
        ->join('model_has_roles', 'accountingunits.id', '=', 'model_has_roles.model_id')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->select(
            'roles.name as role',
            DB::raw('COUNT(DISTINCT dv_request_logs.accountingunit_id) as staff_count'),
            DB::raw('COUNT(DISTINCT dv_request_logs.dv_request_id) as total_documents'),
            DB::raw('ROUND(COUNT(DISTINCT dv_request_logs.dv_request_id) / COUNT(DISTINCT dv_request_logs.accountingunit_id), 2) as avg_docs_per_staff')
        )
        ->whereBetween('dv_request_logs.created_at', [$dateFrom, $dateTo])
        ->whereNotNull('dv_request_logs.accountingunit_id')
        ->whereNotIn('roles.name', ['Focal_Person', 'ADMIN'])
        ->groupBy('roles.name')
        ->orderByDesc('total_documents')
        ->get();

    $topPerformers = DB::table(DB::raw('(
        SELECT 
            au.id as user_id,
            CONCAT(au.fname, \' \', au.lname) as staff_name,
            current_log.dv_request_id,
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
        WHERE current_log.created_at BETWEEN ? AND ?
        AND current_log.accountingunit_id IS NOT NULL
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
    ->setBindings([$dateFrom, $dateTo])
    ->groupBy('user_id', 'staff_name')
    ->having('documents_handled', '>=', 10)
    ->havingRaw('avg_processing_hours > 0 AND avg_processing_hours < 100')
    ->orderBy('avg_processing_hours', 'asc')
    ->limit(10)
    ->get();
    
    // Staff Performance Summary & Insights
    $staffInsights = [];
    
    if ($staffPerformance->isNotEmpty()) {
        $topStaff = $staffPerformance->first();
       $staffInsights[] = "Top performer: <span class='font-semibold'>{$topStaff->first_name} {$topStaff->last_name}</span> ({$topStaff->role}) processed {$topStaff->documents_processed} documents.";
        
        $avgDocsPerStaff = $staffPerformance->avg('documents_processed');
        $staffInsights[] = "Average documents per staff: <span class='font-semibold'>" . round($avgDocsPerStaff, 2) . "</span>.";
        
        if ($topPerformers->isNotEmpty()) {
            $fastest = $topPerformers->first();
            $staffInsights[] = "Fastest processor: <span class='font-semibold'>{$fastest->staff_name}</span> with avg {$fastest->avg_processing_hours} hours per document.";
        }
    }
            
    // =============================================
    // 10-WORKING-DAY SLA ANALYSIS
    // =============================================
    $excludedRoles = ['submitted', 'focal_person', 'Focal_person', 'Focal Person', 'admin', 'Admin', 'Administrator'];
    
    // Step 1: Get all completed requests (reached Regional Director) within date range
    $completedRequests = DB::table('dv_request_logs')
        ->select(
            'dv_request_id',
            DB::raw('MIN(created_at) as voucher_received_time'),
            DB::raw('MAX(CASE WHEN action = "Regional_Director" THEN created_at END) as rd_received_time')
        )
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->whereNotIn('action', $excludedRoles)
        ->groupBy('dv_request_id')
        ->havingNotNull('rd_received_time')
        ->get();
    
    // Step 2: Calculate working days for each request
    $slaData = [];
    $totalViolations = 0;
    
    foreach ($completedRequests as $request) {
        $workingDays = $this->calculateWorkingDays($request->voucher_received_time, $request->rd_received_time);
        $isViolation = $workingDays > 10;
        
        $slaData[] = [
            'dv_request_id' => $request->dv_request_id,
            'working_days' => $workingDays,
            'sla_status' => $isViolation ? 'VIOLATION' : 'WITHIN SLA',
            'days_over' => $isViolation ? $workingDays - 10 : 0
        ];
        
        if ($isViolation) {
            $totalViolations++;
        }
    }
    
    // Step 3: SLA Summary
    $slaAnalysis = [
        'total_completed_requests' => count($slaData),
        'within_sla' => count($slaData) - $totalViolations,
        'sla_violations' => $totalViolations,
        'violation_rate_percent' => count($slaData) > 0 ? round(($totalViolations / count($slaData)) * 100, 2) : 0,
        'avg_working_days' => count($slaData) > 0 ? round(collect($slaData)->avg('working_days'), 2) : 0,
        'worst_performer' => count($slaData) > 0 ? collect($slaData)->max('working_days') : 0,
    ];
    
    // Add SLA insights
    $insights = [];
    if ($slaAnalysis['violation_rate_percent'] > 0) {
        $insights[] = "SLA Compliance: <span class='text-red-600 font-semibold'>{$slaAnalysis['violation_rate_percent']}%</span> of requests exceeded 10-working-day SLA.";
    } else {
        $insights[] = "SLA Compliance: <span class='text-green-600 font-semibold'>100%</span> of requests met 10-working-day SLA.";
    }
    
    if ($slaAnalysis['avg_working_days'] > 0) {
        $insights[] = "Average processing time: <span class='font-semibold'>{$slaAnalysis['avg_working_days']} working days</span>.";
    }

    $bottlenecks = DB::table(DB::raw('(
        SELECT 
            logs.*,
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
            AND created_at BETWEEN ? AND ?
            GROUP BY dv_request_id
        ) start_times ON logs.dv_request_id = start_times.dv_request_id
        WHERE logs.created_at BETWEEN ? AND ?
        AND logs.action IS NOT NULL
        AND logs.action NOT IN (\'' . implode("','", $excludedRoles) . '\')
        AND logs.action NOT LIKE "%rejected%"
    ) as workflow'))
    ->select(
        'role_name',
        DB::raw('COUNT(DISTINCT dv_request_id) as total_processed'),
        DB::raw('ROUND(AVG(
            CASE 
                WHEN DATE(prev_action_time) != DATE(created_at) THEN NULL
                WHEN TIMESTAMPDIFF(HOUR, prev_action_time, created_at) > 10 THEN NULL
                WHEN TIMESTAMPDIFF(MINUTE, prev_action_time, created_at) BETWEEN 1 AND 480 
                THEN TIMESTAMPDIFF(MINUTE, prev_action_time, created_at)
                ELSE NULL
            END
        ) / 60.0, 2) as avg_hours'),
        DB::raw('ROUND(AVG(TIMESTAMPDIFF(DAY, voucher_received_time, created_at)), 2) as avg_days_into_process')
    )
    ->setBindings([$dateFrom, $dateTo, $dateFrom, $dateTo])
    ->whereNotNull('role_name')
    ->whereNotNull('prev_action_time')
    ->groupBy('role_name')
    ->having('total_processed', '>=', 1)
    ->having('avg_hours', '>', 0)
    ->orderByDesc('avg_hours')
    ->limit(8)
    ->get();
    
    $topProgram = DB::table('programs')
        ->leftJoin('dv_requests', function($join) use ($dateFrom, $dateTo) {
            $join->on('dv_requests.program_id', '=', 'programs.id')
                 ->whereBetween('dv_requests.created_at', [$dateFrom, $dateTo]);
        })
        ->select('programs.prcode', DB::raw('COUNT(dv_requests.id) as total'))
        ->groupBy('programs.prcode')
        ->orderByDesc('total')
        ->first();
    
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
    }
    
    if ($trend > 0) {
        $insights[] = "Requests increased by <span class='text-green-600 font-semibold'>{$trend}%</span> compared to previous period.";
    } elseif ($trend < 0) {
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
            'insights' => $staffInsights
        ],
        'bottlenecks'           => $bottlenecks,
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
            'date_range_applied' => true,
            'query_date_from' => $dateFrom->format('Y-m-d H:i:s'),
            'query_date_to' => $dateTo->format('Y-m-d H:i:s'),
            'total_requests_query' => $totalRequests,
            'is_dummy_data' => false
        ]
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

    /**
     * Generate comprehensive dummy data for testing
     */
    protected function getDummyDashboardData()
    {
        // =============================================
        // REQUESTS PER PROGRAM (Dummy Data)
        // =============================================
        $requestsPerProgram = collect([
            'TUPAD' => 45,
            'GIP'   => 32,
            'TEV'   => 28
        ]);

        // =============================================
        // KEY PERFORMANCE INDICATORS (Dummy Data)
        // =============================================
        $totalRequests = 105;
        $pendingRequests = 15;
        $approvedRequests = 78;
        $rejectedRequests = 12;
        $averageProcessingTime = $this->generateDummyAverageProcessingTime(); // hours

        // =============================================
        // DOCUMENTS PER ROLE (Dummy Data) - INCLUDING INCOMING ROLES
        // =============================================
        $documentsPerRole = collect([
            'Incoming-TUPAD' => 45,
            'Incoming-GIP' => 32,
            'Incoming-TEV' => 28,
            'IMSD' => 89,
            'Budget Officer' => 92,
            'Accountant III' => 85,
            'Cashier' => 78,
            'Regional Director' => 75
        ]);

        // =============================================
        // MONTHLY REQUESTS TREND (Dummy Data)
        // =============================================
        $monthlyRequests = collect([
            'January 2024' => 8,
            'February 2024' => 12,
            'March 2024' => 15,
            'April 2024' => 18,
            'May 2024' => 22,
            'June 2024' => 30
        ]);

        // =============================================
        // BUDGET PER PROGRAM (Dummy Data)
        // =============================================
        $budgetPerProgram = collect([
            'TUPAD' => 1250000.00,
            'GIP'   => 890000.00,
            'TEV'   => 650000.00
        ]);

        // =============================================
        // STAFF PERFORMANCE (Comprehensive Dummy Data) - INCLUDING INCOMING ROLES
        // =============================================
        $staffPerformance = $this->generateDummyStaffPerformance();
        $monthlyStaffProductivity = $this->generateDummyMonthlyProductivity();
        $workloadByRole = $this->generateDummyWorkloadByRole();
        $topPerformers = $this->generateDummyTopPerformers();
        $staffInsights = $this->generateDummyStaffInsights($staffPerformance, $topPerformers);

        // =============================================
        // BOTTLENECKS ANALYSIS (Dummy Data) - INCLUDING INCOMING ROLES
        // =============================================
        $bottlenecks = $this->generateDummyBottlenecks();

        // =============================================
        // SLA ANALYSIS (Dummy Data)
        // =============================================
        $slaAnalysis = [
            'total_completed_requests' => 85,
            'within_sla' => 68,
            'sla_violations' => 17,
            'violation_rate_percent' => 20.0,
            'avg_working_days' => 8.5,
            'worst_performer' => 15,
        ];

        // =============================================
        // INSIGHTS (Dummy Data)
        // =============================================
        $insights = [
            "TUPAD has the highest number of requests (45).",
            "Requests increased by <span class='text-green-600 font-semibold'>15%</span> compared to last month.",
            "There are <span class='font-semibold'>15</span> requests still pending.",
            "A total of <span class='font-semibold'>78</span> requests have been approved.",
            "SLA Compliance: <span class='text-red-600 font-semibold'>20%</span> of requests exceeded 10-working-day SLA.",
            "Average processing time: <span class='font-semibold'>8.5 working days</span>.",
            "Incoming-TUPAD processed 45 documents with fastest turnaround (2.5 hours).",
            "Overall average processing time is <span class='font-semibold'>4.5 hours</span> per request."
        ];

        // =============================================
        // RETURN ALL DUMMY DATA
        // =============================================
        return [
            'requestsPerProgram'    => $requestsPerProgram,
            'totalRequests'         => $totalRequests,
            'pendingRequests'       => $pendingRequests,
            'approvedRequests'      => $approvedRequests,
            'rejectedRequests'      => $rejectedRequests,
            'averageProcessingTime' => $averageProcessingTime,
            'documentsPerRole'      => $documentsPerRole,
            'monthlyRequests'       => $monthlyRequests,
            'budgetPerProgram'      => $budgetPerProgram,
            'staffPerformance' => [
                'individual_metrics' => $staffPerformance,
                'monthly_productivity' => $monthlyStaffProductivity,
                'workload_by_role' => $workloadByRole,
                'top_performers' => $topPerformers,
                'insights' => $staffInsights
            ],
            'bottlenecks'           => $bottlenecks,
            'slaAnalysis'           => $slaAnalysis,
            'insights'              => $insights,
        ];
    }

    /**
     * Generate dummy average processing time data with realistic variations
     */
    private function generateDummyAverageProcessingTime()
    {
        // Generate realistic processing times in hours with some variation
        $baseTime = 4.5; // Base average in hours
        $variation = mt_rand(-10, 10) / 10; // ±1 hour variation
        $trend = mt_rand(-5, 5) / 100; // Small trend variation
        
        $processingTime = $baseTime + $variation + $trend;
        
        // Ensure it's within reasonable bounds (1-8 hours)
        $processingTime = max(1.0, min(8.0, $processingTime));
        
        // Round to 1 decimal place
        return round($processingTime, 1);
    }

    /**
     * Generate dummy staff performance data - INCLUDING INCOMING ROLES
     */
    private function generateDummyStaffPerformance()
    {
        return collect([
            // Incoming Roles Staff
            (object)[
                'user_id' => 201,
                'first_name' => 'Ramon',
                'last_name' => 'Garcia',
                'role' => 'Incoming-TUPAD',
                'documents_processed' => 45,
                'avg_processing_hours' => 2.5
            ],
            (object)[
                'user_id' => 202,
                'first_name' => 'Liza',
                'last_name' => 'Mendoza',
                'role' => 'Incoming-GIP',
                'documents_processed' => 32,
                'avg_processing_hours' => 2.3
            ],
            (object)[
                'user_id' => 203,
                'first_name' => 'Antonio',
                'last_name' => 'Ramos',
                'role' => 'Incoming-TEV',
                'documents_processed' => 28,
                'avg_processing_hours' => 2.1
            ],
            // Regular Roles Staff
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
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'role' => 'Budget Officer',
                'documents_processed' => 38,
                'avg_processing_hours' => 3.2
            ],
            (object)[
                'user_id' => 103,
                'first_name' => 'Ana',
                'last_name' => 'Reyes',
                'role' => 'Accountant III',
                'documents_processed' => 42,
                'avg_processing_hours' => 2.8
            ],
            (object)[
                'user_id' => 104,
                'first_name' => 'Carlos',
                'last_name' => 'Gonzales',
                'role' => 'Cashier',
                'documents_processed' => 35,
                'avg_processing_hours' => 1.9
            ],
            (object)[
                'user_id' => 105,
                'first_name' => 'Elena',
                'last_name' => 'Torres',
                'role' => 'Regional Director',
                'documents_processed' => 28,
                'avg_processing_hours' => 4.5
            ],
            (object)[
                'user_id' => 106,
                'first_name' => 'Roberto',
                'last_name' => 'Lim',
                'role' => 'IMSD',
                'documents_processed' => 31,
                'avg_processing_hours' => 2.7
            ],
            (object)[
                'user_id' => 107,
                'first_name' => 'Lorna',
                'last_name' => 'Diaz',
                'role' => 'Budget Officer',
                'documents_processed' => 29,
                'avg_processing_hours' => 3.8
            ]
        ]);
    }

    /**
     * Generate dummy monthly productivity data
     */
    private function generateDummyMonthlyProductivity()
    {
        return collect([
            (object)[
                'month' => '2024-01',
                'month_label' => 'January 2024',
                'total_processed' => 120,
                'active_staff' => 10
            ],
            (object)[
                'month' => '2024-02',
                'month_label' => 'February 2024',
                'total_processed' => 135,
                'active_staff' => 11
            ],
            (object)[
                'month' => '2024-03',
                'month_label' => 'March 2024',
                'total_processed' => 158,
                'active_staff' => 11
            ],
            (object)[
                'month' => '2024-04',
                'month_label' => 'April 2024',
                'total_processed' => 142,
                'active_staff' => 10
            ],
            (object)[
                'month' => '2024-05',
                'month_label' => 'May 2024',
                'total_processed' => 165,
                'active_staff' => 12
            ],
            (object)[
                'month' => '2024-06',
                'month_label' => 'June 2024',
                'total_processed' => 180,
                'active_staff' => 12
            ]
        ]);
    }

    /**
     * Generate dummy workload by role data - INCLUDING INCOMING ROLES
     */
    private function generateDummyWorkloadByRole()
    {
        return collect([
            (object)[
                'role' => 'Incoming-TUPAD',
                'staff_count' => 2,
                'total_documents' => 45,
                'avg_docs_per_staff' => 22.50
            ],
            (object)[
                'role' => 'Incoming-GIP',
                'staff_count' => 2,
                'total_documents' => 32,
                'avg_docs_per_staff' => 16.00
            ],
            (object)[
                'role' => 'Incoming-TEV',
                'staff_count' => 2,
                'total_documents' => 28,
                'avg_docs_per_staff' => 14.00
            ],
            (object)[
                'role' => 'IMSD',
                'staff_count' => 3,
                'total_documents' => 245,
                'avg_docs_per_staff' => 81.67
            ],
            (object)[
                'role' => 'Budget Officer',
                'staff_count' => 2,
                'total_documents' => 198,
                'avg_docs_per_staff' => 99.00
            ],
            (object)[
                'role' => 'Accountant III',
                'staff_count' => 2,
                'total_documents' => 175,
                'avg_docs_per_staff' => 87.50
            ],
            (object)[
                'role' => 'Cashier',
                'staff_count' => 1,
                'total_documents' => 85,
                'avg_docs_per_staff' => 85.00
            ],
            (object)[
                'role' => 'Regional Director',
                'staff_count' => 1,
                'total_documents' => 75,
                'avg_docs_per_staff' => 75.00
            ]
        ]);
    }

    /**
     * Generate dummy top performers data - INCLUDING INCOMING ROLES STAFF
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
                'documents_handled' => 35,
                'avg_processing_hours' => 1.9
            ],
            (object)[
                'staff_name' => 'Antonio Ramos',
                'documents_handled' => 28,
                'avg_processing_hours' => 2.1
            ],
            (object)[
                'staff_name' => 'Ana Reyes',
                'documents_handled' => 42,
                'avg_processing_hours' => 2.8
            ],
            (object)[
                'staff_name' => 'Ramon Garcia',
                'documents_handled' => 45,
                'avg_processing_hours' => 2.5
            ],
            (object)[
                'staff_name' => 'Roberto Lim',
                'documents_handled' => 31,
                'avg_processing_hours' => 2.7
            ],
            (object)[
                'staff_name' => 'Liza Mendoza',
                'documents_handled' => 32,
                'avg_processing_hours' => 2.3
            ],
            (object)[
                'staff_name' => 'Juan Dela Cruz',
                'documents_handled' => 38,
                'avg_processing_hours' => 3.2
            ]
        ]);
    }

    /**
     * Generate dummy staff insights - INCLUDING INCOMING ROLES
     */
    private function generateDummyStaffInsights($staffPerformance, $topPerformers)
    {
        return [
            "Top performer: <span class='font-semibold'>Maria Santos</span> (IMSD) processed 45 documents.",
            "Average documents per staff: <span class='font-semibold'>35.71</span>.",
            "Fastest processor: <span class='font-semibold'>Carlos Gonzales</span> with avg 1.9 hours per document.",
            "Budget Officer role has the highest workload with 99 documents per staff on average.",
            "Productivity increased by 50% from January to June 2024.",
            "Incoming roles processed 105 initial requests with excellent turnaround times."
        ];
    }

    /**
     * Generate dummy bottlenecks data - INCLUDING INCOMING ROLES
     */
    private function generateDummyBottlenecks()
    {
        return collect([
            (object)[
                'role_name' => 'Budget Officer',
                'total_processed' => 92,
                'avg_hours' => 6.8,
                'avg_days_into_process' => 3.2
            ],
            (object)[
                'role_name' => 'Regional Director',
                'total_processed' => 75,
                'avg_hours' => 5.2,
                'avg_days_into_process' => 6.8
            ],
            (object)[
                'role_name' => 'Accountant III',
                'total_processed' => 85,
                'avg_hours' => 4.1,
                'avg_days_into_process' => 4.5
            ],
            (object)[
                'role_name' => 'IMSD',
                'total_processed' => 89,
                'avg_hours' => 3.8,
                'avg_days_into_process' => 2.1
            ],
            (object)[
                'role_name' => 'Cashier',
                'total_processed' => 78,
                'avg_hours' => 2.9,
                'avg_days_into_process' => 7.2
            ],
            (object)[
                'role_name' => 'Incoming-TUPAD',
                'total_processed' => 45,
                'avg_hours' => 2.5,
                'avg_days_into_process' => 1.2
            ],
            (object)[
                'role_name' => 'Incoming-GIP',
                'total_processed' => 32,
                'avg_hours' => 2.3,
                'avg_days_into_process' => 1.1
            ],
            (object)[
                'role_name' => 'Incoming-TEV',
                'total_processed' => 28,
                'avg_hours' => 2.1,
                'avg_days_into_process' => 1.0
            ]
        ]);
    }
}