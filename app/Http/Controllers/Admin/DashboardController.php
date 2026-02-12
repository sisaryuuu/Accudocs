<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\TriggersDashboardUpdate;
use App\Models\DvRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use TriggersDashboardUpdate;

    /**
     * Display the admin dashboard with insights
     */
    public function index(Request $request)
    {
        // Get date filters from request or use defaults
        $dateFrom = $request->get('date_from', now()->subMonths(12)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        
        // Get dashboard data with filters
        $data = $this->getDashboardData($dateFrom, $dateTo);
        
        // Generate chart-specific insights
        $data['chartInsights'] = $this->generateChartInsights($data);
        
        // Add Pusher config for frontend
        $data['pusher_key'] = env('PUSHER_APP_KEY');
        $data['pusher_cluster'] = env('PUSHER_APP_CLUSTER');
        
        // Add filter info to data
        $data['filters'] = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'date_from_formatted' => Carbon::parse($dateFrom)->format('M d, Y'),
            'date_to_formatted' => Carbon::parse($dateTo)->format('M d, Y'),
            'days_count' => Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)),
            'applied' => $request->has('date_from') || $request->has('date_to')
        ];
 $totalExceededCount = DvRequest::where(function($q) {
        $q->whereIn('status', [
            'submitted', 'IMSD_received', 'IMSD_in_process', 'IMSD_verified'
        ])
        ->orWhereIn('status', [
            'Incoming-TUPAD_received', 'Incoming-TUPAD_in_process',
            'Incoming-GIP_received', 'Incoming-GIP_in_process',
            'Incoming-TEV_received', 'Incoming-TEV_in_process'
        ])
        ->orWhereIn('status', [
            'Incoming-TUPAD_verified',
            'Incoming-GIP_verified',
            'Incoming-TEV_verified',
            'ORS_received',
            'ORS_in_process',
            'ORS_onhold',
            'ORS_for_revision'
        ])
        ->orWhereIn('status', [
            'ORS_obligated',
            'Outgoing_received'
        ])
        // 🚨🚨🚨 ADD ACCOUNTANT STATUSES 🚨🚨🚨
        ->orWhereIn('status', [
            'Outgoing_finalized',
            'RANCA_received'
        ]);
    })
    ->where('created_at', '<=', now()->subDays(10))
    ->count();

// Pass to view
return view('Admin.dashboard', compact('totalExceededCount') + $data);
    }

    /**
     * API endpoint to get current dashboard data
     */
    public function fetchData(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from', now()->subMonths(12)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));
            
            $data = $this->getDashboardData($dateFrom, $dateTo);
            
            // Generate insights
            $data['chartInsights'] = $this->generateChartInsights($data);
            
            // Add filter info
            $data['filters'] = [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'applied' => true
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filter dashboard data by date range (GET method for form submission)
     */
    public function filter(Request $request)
    {
        try {
            // Validate dates
            $validated = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from'
            ]);
            
            $dateFrom = $validated['date_from'] ?? now()->subMonths(12)->format('Y-m-d');
            $dateTo = $validated['date_to'] ?? now()->format('Y-m-d');
            
            // Redirect to main dashboard with filter parameters
            return redirect()->route('admin.dashboard', [
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]);
            
        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Failed to apply filters: ' . $e->getMessage());
        }
    }

    /**
     * Manual trigger endpoint for real-time updates
     */
    public function refresh(Request $request)
    {
        try {
            $this->triggerDashboardUpdate();
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard refresh triggered',
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick filter presets
     */
    public function preset($preset)
    {
        $today = now();
        
        switch ($preset) {
            case 'today':
                $dateFrom = $today->format('Y-m-d');
                $dateTo = $today->format('Y-m-d');
                break;
                
            case 'yesterday':
                $dateFrom = $today->subDay()->format('Y-m-d');
                $dateTo = $dateFrom;
                break;
                
            case 'this_week':
                $dateFrom = $today->copy()->startOfWeek()->format('Y-m-d');
                $dateTo = $today->copy()->endOfWeek()->format('Y-m-d');
                break;
                
            case 'last_week':
                $dateFrom = $today->copy()->subWeek()->startOfWeek()->format('Y-m-d');
                $dateTo = $today->copy()->subWeek()->endOfWeek()->format('Y-m-d');
                break;
                
            case 'this_month':
                $dateFrom = $today->copy()->startOfMonth()->format('Y-m-d');
                $dateTo = $today->copy()->endOfMonth()->format('Y-m-d');
                break;
                
            case 'last_month':
                $dateFrom = $today->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $dateTo = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
                
            case 'last_3_months':
                $dateFrom = $today->copy()->subMonths(3)->format('Y-m-d');
                $dateTo = $today->copy()->format('Y-m-d');
                break;
                
            case 'last_6_months':
                $dateFrom = $today->copy()->subMonths(6)->format('Y-m-d');
                $dateTo = $today->copy()->format('Y-m-d');
                break;
                
            case 'last_year':
                $dateFrom = $today->copy()->subYear()->format('Y-m-d');
                $dateTo = $today->copy()->format('Y-m-d');
                break;
                
            default:
                $dateFrom = now()->subMonths(12)->format('Y-m-d');
                $dateTo = now()->format('Y-m-d');
        }
        
        return redirect()->route('admin.dashboard', [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
    }

    /**
     * Get dashboard data for export
     */
    public function export(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from', now()->subMonths(12)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));
            
            $data = $this->getDashboardData($dateFrom, $dateTo);
            
            // Generate CSV export
            $filename = 'dashboard_export_' . now()->format('Y-m-d_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];
            
            $callback = function() use ($data, $dateFrom, $dateTo) {
                $file = fopen('php://output', 'w');
                
                // Header Section
                fputcsv($file, ['DOLE Region XI Dashboard Export']);
                fputcsv($file, ['Generated:', now()->format('F d, Y h:i A')]);
                fputcsv($file, ['Period:', "$dateFrom to $dateTo"]);
                fputcsv($file, []);
                
                // KPIs Section
                fputcsv($file, ['KEY PERFORMANCE INDICATORS']);
                fputcsv($file, ['Metric', 'Value']);
                fputcsv($file, ['Total Requests', $data['totalRequests'] ?? 0]);
                fputcsv($file, ['Pending Requests', $data['pendingRequests'] ?? 0]);
                fputcsv($file, ['Approved Requests', $data['approvedRequests'] ?? 0]);
                fputcsv($file, ['Rejected Requests', $data['rejectedRequests'] ?? 0]);
                fputcsv($file, ['Average Processing Time (days)', $data['averageProcessingTime'] ?? 0]);
                fputcsv($file, []);
                
                // Requests per Program
                fputcsv($file, ['REQUESTS PER PROGRAM']);
                fputcsv($file, ['Program', 'Requests']);
                foreach (($data['requestsPerProgram'] ?? []) as $program => $count) {
                    fputcsv($file, [$program, $count]);
                }
                fputcsv($file, []);
                
                // Budget per Program
                fputcsv($file, ['BUDGET OBLIGATED PER PROGRAM']);
                fputcsv($file, ['Program', 'Amount (PHP)']);
                foreach (($data['budgetPerProgram'] ?? []) as $program => $amount) {
                    fputcsv($file, [$program, number_format($amount, 2)]);
                }
                fputcsv($file, []);
                
                // SLA Analysis
                if (isset($data['slaAnalysis'])) {
                    fputcsv($file, ['SLA COMPLIANCE ANALYSIS']);
                    fputcsv($file, ['Metric', 'Value']);
                    fputcsv($file, ['Total Completed Requests', $data['slaAnalysis']['total_completed_requests']]);
                    fputcsv($file, ['Within SLA (≤10 days)', $data['slaAnalysis']['within_sla']]);
                    fputcsv($file, ['SLA Violations (>10 days)', $data['slaAnalysis']['sla_violations']]);
                    fputcsv($file, ['Violation Rate (%)', $data['slaAnalysis']['violation_rate_percent']]);
                    fputcsv($file, ['Average Working Days', $data['slaAnalysis']['avg_working_days']]);
                    fputcsv($file, ['Worst Case (days)', $data['slaAnalysis']['worst_performer']]);
                    fputcsv($file, []);
                }
                
                // Staff Performance
                if (isset($data['staffPerformance']['individual_metrics'])) {
                    fputcsv($file, ['STAFF PERFORMANCE']);
                    fputcsv($file, ['Name', 'Role', 'Documents Processed', 'Avg Processing Hours']);
                    foreach ($data['staffPerformance']['individual_metrics'] as $staff) {
                        fputcsv($file, [
                            "{$staff->first_name} {$staff->last_name}",
                            $staff->role,
                            $staff->documents_processed,
                            $staff->avg_processing_hours
                        ]);
                    }
                    fputcsv($file, []);
                }
                
                // Bottlenecks
                if (isset($data['bottlenecks']) && count($data['bottlenecks']) > 0) {
                    fputcsv($file, ['WORKFLOW BOTTLENECKS']);
                    fputcsv($file, ['Role', 'Avg Processing Hours', 'Total Processed', 'Days Into Process']);
                    foreach ($data['bottlenecks'] as $bottleneck) {
                        fputcsv($file, [
                            $bottleneck->role_name,
                            $bottleneck->avg_hours,
                            $bottleneck->total_processed,
                            $bottleneck->avg_days_into_process
                        ]);
                    }
                    fputcsv($file, []);
                }
                
                // Insights
                if (isset($data['insights'])) {
                    fputcsv($file, ['INSIGHTS & RECOMMENDATIONS']);
                    foreach ($data['insights'] as $insight) {
                        fputcsv($file, [strip_tags($insight)]);
                    }
                }
                
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to export data: ' . $e->getMessage());
        }
    }

    /**
     * Generate chart-specific insights for dashboard
     */
    private function generateChartInsights($data)
    {
        return [
            'requestsPerProgram' => $this->generateProgramInsights($data),
            'budgetPerProgram' => $this->generateBudgetInsights($data),
            'documentsPerRole' => $this->generateRoleInsights($data),
            'monthlyRequests' => $this->generateTrendInsights($data),
            'staffPerformance' => $this->generateStaffInsights($data),
            'bottlenecks' => $this->generateBottleneckInsights($data)
        ];
    }

    private function generateProgramInsights($data)
    {
        $programs = $data['requestsPerProgram'] ?? collect([]);
        $total = $programs->sum();
        
        if ($total == 0) return null;
        
        $topProgram = $programs->sortDesc()->keys()->first();
        $topCount = $programs->max();
        $topPercentage = round(($topCount / $total) * 100, 1);
        
        return [
            'topProgram' => $topProgram,
            'topCount' => $topCount,
            'topPercentage' => $topPercentage,
            'isBalanced' => $this->checkProgramBalance($programs, $total),
            'avgPerProgram' => round($total / count($programs), 1)
        ];
    }

    private function generateBudgetInsights($data)
    {
        $budgets = $data['budgetPerProgram'] ?? collect([]);
        $requests = $data['requestsPerProgram'] ?? collect([]);
        $totalBudget = $budgets->sum();
        
        if ($totalBudget == 0) return null;
        
        $costPerRequest = [];
        foreach ($budgets as $program => $budget) {
            $count = $requests->get($program, 1);
            $costPerRequest[$program] = $count > 0 ? round($budget / $count, 2) : 0;
        }
        
        arsort($costPerRequest);
        
        return [
            'totalBudget' => $totalBudget,
            'topBudgetProgram' => $budgets->sortDesc()->keys()->first(),
            'topBudgetAmount' => $budgets->max(),
            'costPerRequest' => $costPerRequest,
            'mostExpensive' => array_key_first($costPerRequest),
            'leastExpensive' => array_key_last($costPerRequest)
        ];
    }

    private function generateRoleInsights($data)
    {
        $docsPerRole = $data['documentsPerRole'] ?? collect([]);
        
        if ($docsPerRole->isEmpty()) return null;
        
        $totalDocs = $docsPerRole->sum();
        $busiestRole = $docsPerRole->sortDesc()->keys()->first();
        $lightestRole = $docsPerRole->sort()->keys()->first();
        
        return [
            'totalDocs' => $totalDocs,
            'busiestRole' => $busiestRole,
            'busiestCount' => $docsPerRole->max(),
            'lightestRole' => $lightestRole,
            'lightestCount' => $docsPerRole->min(),
            'workloadGap' => $docsPerRole->max() - $docsPerRole->min(),
            'avgDocsPerRole' => round($totalDocs / count($docsPerRole), 1)
        ];
    }

    private function generateTrendInsights($data)
    {
        $monthly = $data['monthlyRequests'] ?? collect([]);
        
        if ($monthly->count() < 2) return null;
        
        $values = $monthly->values();
        $latest = $values->last();
        $previous = $values->slice(-2, 1)->first();
        
        $growth = $previous > 0 ? round((($latest - $previous) / $previous) * 100, 1) : 0;
        
        return [
            'latestMonth' => $monthly->keys()->last(),
            'latestValue' => $latest,
            'previousValue' => $previous,
            'growth' => $growth,
            'peakMonth' => $monthly->sortDesc()->keys()->first(),
            'peakValue' => $monthly->max(),
            'avgMonthly' => $values->avg()
        ];
    }

    private function generateStaffInsights($data)
    {
        $staff = collect($data['staffPerformance']['individual_metrics'] ?? []);
        
        if ($staff->isEmpty()) return null;
        
        $avgDocs = $staff->avg('documents_processed');
        $avgHours = $staff->avg('avg_processing_hours');
        
        return [
            'totalStaff' => $staff->count(),
            'avgDocsProcessed' => round($avgDocs, 1),
            'avgProcessingHours' => round($avgHours, 2),
            'topPerformer' => $staff->sortByDesc('documents_processed')->first(),
            'highPerformers' => $staff->filter(fn($s) => $s->documents_processed > $avgDocs * 1.2)->count(),
            'lowPerformers' => $staff->filter(fn($s) => $s->documents_processed < $avgDocs * 0.8)->count()
        ];
    }

    private function generateBottleneckInsights($data)
    {
        $bottlenecks = collect($data['bottlenecks'] ?? []);
        
        if ($bottlenecks->isEmpty()) return null;
        
        $slowest = $bottlenecks->sortByDesc('avg_hours')->first();
        $fastest = $bottlenecks->sortBy('avg_hours')->first();
        
        return [
            'slowestRole' => $slowest,
            'fastestRole' => $fastest,
            'processingGap' => $slowest->avg_hours - $fastest->avg_hours,
            'criticalCount' => $bottlenecks->filter(fn($b) => $b->avg_hours > 4)->count(),
            'totalAvgHours' => $bottlenecks->sum('avg_hours')
        ];
    }

    private function checkProgramBalance($programs, $total)
    {
        $avgValue = $total / count($programs);
        $variance = 0;
        
        foreach ($programs as $value) {
            $variance += pow($value - $avgValue, 2);
        }
        
        $variance = $variance / count($programs);
        return $variance < ($avgValue * 0.5);
    }
}