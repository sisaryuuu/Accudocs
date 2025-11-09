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

        // =============================================
        // DOCUMENTS HANDLED PER ROLE
        $documentsPerRole = DB::table('dv_request_logs')
        ->join('accountingunits', 'dv_request_logs.accountingunit_id', '=', 'accountingunits.id')
        ->join('model_has_roles', 'accountingunits.id', '=', 'model_has_roles.model_id')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->select('roles.name as role_name', DB::raw('COUNT(DISTINCT dv_request_id) as total_documents'))
        ->whereNotNull('accountingunit_id') // Only count where accounting unit processed it
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
        $budgetPerProgram = DB::table('ors_requests as h')
            ->join('dv_requests as d', 'h.dv_request_id', '=', 'd.id')
            ->join('programs as p', 'd.program_id', '=', 'p.id')
            ->where('h.status', 'ORS_obligated')
            ->select('p.prcode', DB::raw('SUM(d.amount) as total_budget'))
            ->groupBy('p.prcode')
            ->pluck('total_budget', 'p.prcode');

        // Order: TUPAD, GIP, TEV
        $orderedBudget = collect([
            'TUPAD' => $budgetPerProgram->get('TUPAD', 0),
            'GIP'   => $budgetPerProgram->get('GIP', 0),
            'TEV'   => $budgetPerProgram->get('TEV', 0),
        ]);

        // =============================================
// STAFF PERFORMANCE - AVERAGE TOTAL REQUESTS PER MONTH (LAST 12 MONTHS)
// =============================================
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
        // BOTTLENECKS (Top 5 Slowest Processing)
        // =============================================
        $bottlenecks = $staffPerformance
            ->sortByDesc('avg_hours')
            ->take(5)
            ->values();

        // =============================================
        // INSIGHTS & HIGHLIGHTS
        // =============================================
        
        // Find top program
        $topProgram = DB::table('programs')
            ->leftJoin('dv_requests', 'dv_requests.program_id', '=', 'programs.id')
            ->select('programs.prcode', DB::raw('COUNT(dv_requests.id) as total'))
            ->groupBy('programs.prcode')
            ->orderByDesc('total')
            ->first();

        // Calculate trend (this month vs last month)
        $thisMonth = DB::table('dv_requests')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        $lastMonth = DB::table('dv_requests')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        
        $trend = $lastMonth > 0 
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2) 
            : 0;

        // Build insights array
        $insights = [];
        
        if ($topProgram && $topProgram->total > 0) {
            $insights[] = "{$topProgram->prcode} has the highest number of requests ({$topProgram->total}).";
        }
        
        if ($trend > 0) {
            $insights[] = "Requests increased by {$trend}% compared to last month.";
        } elseif ($trend < 0) {
            $insights[] = "Requests decreased by " . abs($trend) . "% compared to last month.";
        } else {
            $insights[] = "Requests remained stable compared to last month.";
        }
        
        if ($pendingRequests > 0) {
            $insights[] = "There are {$pendingRequests} requests still pending.";
        }
        
        if ($approvedRequests > 0) {
            $insights[] = "A total of {$approvedRequests} requests have been approved.";
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
            'insights'              => $insights,
        ];
    }
}