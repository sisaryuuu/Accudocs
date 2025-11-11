<?php

namespace App\Traits;

use App\Events\DashboardUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Trait TriggersDashboardUpdate
 * 
 * This trait provides methods to trigger real-time dashboard updates
 * whenever DV request data changes. Include this trait in any controller
 * that modifies DV requests (create, update, approve, reject, etc.)
 * 
 * Usage:
 * ```php
 * use App\Traits\TriggersDashboardUpdate;
 * 
 * class YourController extends Controller
 * {
 *     use TriggersDashboardUpdate;
 *     
 *     public function approve($id)
 *     {
 *         // Your logic...
 *         $this->triggerDashboardUpdate();
 *     }
 * }
 * ```
 */
trait TriggersDashboardUpdate
{
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
     * Get all dashboard data
     * Centralized method to ensure consistent data across all dashboard views
     * 
     * @return array
     */
    protected function getDashboardData()
    {
        // =============================================
        // REQUESTS PER PROGRAM
        // =============================================
        $requestsPerProgram = DB::table('programs')
            ->leftJoin('dv_requests', 'dv_requests.program_id', '=', 'programs.id')
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
        $totalRequests = DB::table('dv_requests')->count();
        
        $pendingRequests = DB::table('dv_requests')
            ->where('status', 'submitted')
            ->count();
    

        $approvedRequests = DB::table('dv_requests')
            ->where('status', 'Cashier_released')
            ->count();

        $rejectedRequests = DB::table('dv_requests')
            ->where('status', 'like', '%_rejected')
            ->count();

        // Average processing time in minutes
        $averageProcessingTime = DB::table('dv_requests')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_minutes'))
            ->value('avg_minutes') ?? 0;


// Count DISTINCT documents that reached each role
$documentsPerRole = DB::table('dv_request_logs')
    ->join('accountingunits', 'dv_request_logs.accountingunit_id', '=', 'accountingunits.id')
    ->join('model_has_roles', 'accountingunits.id', '=', 'model_has_roles.model_id')
    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
    ->select('roles.name as role_name', DB::raw('COUNT(DISTINCT dv_request_id) as total_documents'))
    ->whereNotNull('accountingunit_id')
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
            ->select('p.prcode', DB::raw('SUM(h.obligated_amount) as total_budget'))
            ->groupBy('p.prcode')
            ->pluck('total_budget', 'p.prcode');

        // Order: TUPAD, GIP, TEV
        $orderedBudget = collect([
            'TUPAD' => $budgetPerProgram->get('TUPAD', 0),
            'GIP'   => $budgetPerProgram->get('GIP', 0),
            'TEV'   => $budgetPerProgram->get('TEV', 0),
        ]);


    $staffPerformance = DB::table('dv_requests')
        ->select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
            DB::raw('COUNT(id) as total_requests')
        )
        ->where('created_at', '>=', now()->subMonths(12)) // only last 12 months
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->map(function ($item) {
            $item->month_label = date('F Y', strtotime($item->month . '-01'));
            return $item;
        });
        
  // =============================================
// 10-WORKING-DAY SLA ANALYSIS
// =============================================
        $excludedRoles = ['submitted', 'focal_person', 'Focal_person', 'Focal Person', 'admin', 'Admin', 'Administrator'];
        
        // Step 1: Get all completed requests (reached Regional Director)
        $completedRequests = DB::table('dv_request_logs')
            ->select(
                'dv_request_id',
                DB::raw('MIN(created_at) as voucher_received_time'),
                DB::raw('MAX(CASE WHEN action = "Regional_Director" THEN created_at END) as rd_received_time')
            )
            ->where('created_at', '>=', now()->subMonths(12))
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
                GROUP BY dv_request_id
            ) start_times ON logs.dv_request_id = start_times.dv_request_id
            WHERE logs.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
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
        ->whereNotNull('role_name')
        ->whereNotNull('prev_action_time')
        ->groupBy('role_name')
        ->having('total_processed', '>=', 1)
        ->having('avg_hours', '>', 0)
        ->orderByDesc('avg_hours')
        ->limit(8)
        ->get();
        
    $topProgram = DB::table('programs')
        ->leftJoin('dv_requests', 'dv_requests.program_id', '=', 'programs.id')
        ->select('programs.prcode', DB::raw('COUNT(dv_requests.id) as total'))
        ->groupBy('programs.prcode')
        ->orderByDesc('total')
        ->first();
    
    // 🕒 Calculate trend (this month vs last month)
    $thisMonth = DB::table('dv_requests')
        ->whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year)
        ->count();
    
    $lastMonth = DB::table('dv_requests')
        ->whereMonth('created_at', now()->subMonth()->month)
        ->whereYear('created_at', now()->subMonth()->year)
        ->count();
    
    // 📈 Calculate % trend (safe for division)
    if ($lastMonth > 0) {
        $trend = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    } else {
        $trend = 0; // avoid division by zero
    }
    
    // 🧠 Build insights
    $insights = [];
    
    if ($topProgram && $topProgram->total > 0) {
        $insights[] = "{$topProgram->prcode} has the highest number of requests ({$topProgram->total}).";
    }
    
    if ($trend > 0) {
        $insights[] = "Requests increased by <span class='text-green-600 font-semibold'>{$trend}%</span> compared to last month.";
    } elseif ($trend < 0) {
        $insights[] = "Requests decreased by <span class='text-red-600 font-semibold'>" . abs($trend) . "%</span> compared to last month.";
    } else {
        $insights[] = "Requests remained stable compared to last month.";
    }
    
    // Optional contextual stats
    if (!empty($pendingRequests) && $pendingRequests > 0) {
        $insights[] = "There are <span class='font-semibold'>{$pendingRequests}</span> requests still pending.";
    }
    
    if (!empty($approvedRequests) && $approvedRequests > 0) {
        $insights[] = "A total of <span class='font-semibold'>{$approvedRequests}</span> requests have been approved.";
    }


        // =============================================
        // RETURN ALL DATA
        // =============================================
        return [
    'requestsPerProgram'    => $orderedPrograms,
    'totalRequests'         => $totalRequests,
    'pendingRequests'       => $pendingRequests,
    'approvedRequests'      => $approvedRequests,
    'rejectedRequests'      => $rejectedRequests,
    'averageProcessingTime' => $averageProcessingTime,
    'documentsPerRole'      => $documentsPerRole,
    'monthlyRequests'       => $monthlyRequests,
    'budgetPerProgram'      => $orderedBudget,
    'staffPerformance'      => $staffPerformance,
    'bottlenecks'           => $bottlenecks,
    'slaAnalysis'           => $slaAnalysis, // Add this line
    'insights'              => $insights,
];
    }
}