<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Kuhaon tanan programs with requests count
        $requestsPerProgram = DB::table('programs')
            ->leftJoin('dv_requests', 'dv_requests.program_id', '=', 'programs.id')
            ->select('programs.prcode', DB::raw('COUNT(dv_requests.id) as total'))
            ->groupBy('programs.prcode')
            ->pluck('total', 'prcode');

        // Reorder manually (TUPAD, GIP, TEV)
        $orderedPrograms = collect([
            'TUPAD' => $requestsPerProgram->get('TUPAD', 0),
            'GIP'   => $requestsPerProgram->get('GIP', 0),
            'TEV'   => $requestsPerProgram->get('TEV', 0),
        ]);

        // KPI: total requests
        $totalRequests = DB::table('dv_requests')->count();

        // KPI: average processing time (minutes)
        $averageProcessingTime = DB::table('dv_requests')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_minutes'))
            ->value('avg_minutes');

        // Pending & Approved
        $pendingRequests = DB::table('dv_requests')->where('status', 'submitted')->count();
        $approvedRequests = DB::table('dv_requests')->where('status', 'approved')->count();

        // Documents handled per role
        $documentsPerRole = DB::table('dv_request_logs')
            ->select('action', DB::raw('COUNT(*) as total'))
            ->groupBy('action')
            ->pluck('total', 'action');

        // Monthly Requests Trend
        $monthlyRequests = DB::table('dv_requests')
            ->select(DB::raw("DATE_FORMAT(created_at, '%M %Y') as month"), DB::raw('COUNT(*) as total'))
            ->groupBy('month')
            ->orderByRaw("MIN(created_at)")
            ->pluck('total', 'month');

        // Top Program
        $topProgram = DB::table('programs')
            ->leftJoin('dv_requests', 'dv_requests.program_id', '=', 'programs.id')
            ->select('programs.prcode', DB::raw('COUNT(dv_requests.id) as total'))
            ->groupBy('programs.prcode')
            ->orderByDesc('total')
            ->first();

        // Monthly trend
        $thisMonth = DB::table('dv_requests')->whereMonth('created_at', now()->month)->count();
        $lastMonth = DB::table('dv_requests')->whereMonth('created_at', now()->subMonth()->month)->count();
        $trend = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2) : 0;

        // Insights
        $insights = [];
        if ($topProgram) {
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

        // Budget per program
        $budgetPerProgram = DB::table('dv_requests')
            ->join('programs', 'dv_requests.program_id', '=', 'programs.id')
            ->select('programs.prcode', DB::raw('SUM(dv_requests.amount) as total_budget'))
            ->groupBy('programs.prcode')
            ->pluck('total_budget', 'prcode');

        $orderedBudget = collect([
            'TUPAD' => $budgetPerProgram->get('TUPAD', 0),
            'GIP'   => $budgetPerProgram->get('GIP', 0),
            'TEV'   => $budgetPerProgram->get('TEV', 0),
        ]);

        // Staff performance
        $staffPerformance = DB::table('dv_requests')
            ->select('locked_by',
                DB::raw('COUNT(*) as total_processed'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, locked_at)) as avg_hours')
            )
            ->whereNotNull('locked_by')
            ->groupBy('locked_by')
            ->get()
            ->map(function ($item) {
                $user = DB::table('users')->where('id', $item->locked_by)->first();
                $item->staff_name = $user ? $user->full_name : 'Unknown Staff';
                return $item;
            });

        // Bottlenecks
        $bottlenecks = $staffPerformance->sortByDesc('avg_hours')->take(5)->values();

        return view('admin.dashboard', [
            'requestsPerProgram' => $orderedPrograms,
            'totalRequests' => $totalRequests,
            'pendingRequests' => $pendingRequests,
            'approvedRequests' => $approvedRequests,
            'averageProcessingTime' => $averageProcessingTime,
            'documentsPerRole' => $documentsPerRole,
            'monthlyRequests' => $monthlyRequests,
            'insights' => $insights,
            'budgetPerProgram' => $orderedBudget,
            'staffPerformance' => $staffPerformance,
            'bottlenecks' => $bottlenecks,
        ]);
    }

    public function fetchData()
    {
        // Same computations as index(), but return JSON for AJAX
        $requestsPerProgram = DB::table('programs')
            ->leftJoin('dv_requests', 'dv_requests.program_id', '=', 'programs.id')
            ->select('programs.prcode', DB::raw('COUNT(dv_requests.id) as total'))
            ->groupBy('programs.prcode')
            ->pluck('total', 'prcode');

        $orderedPrograms = collect([
            'TUPAD' => $requestsPerProgram->get('TUPAD', 0),
            'GIP'   => $requestsPerProgram->get('GIP', 0),
            'TEV'   => $requestsPerProgram->get('TEV', 0),
        ]);

        $totalRequests = DB::table('dv_requests')->count();
        $pendingRequests = DB::table('dv_requests')->where('status', 'submitted')->count();
        $approvedRequests = DB::table('dv_requests')->where('status', 'approved')->count();

        $averageProcessingTime = DB::table('dv_requests')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_minutes'))
            ->value('avg_minutes');

        $documentsPerRole = DB::table('dv_request_logs')
            ->select('action', DB::raw('COUNT(*) as total'))
            ->groupBy('action')
            ->pluck('total', 'action');

        $monthlyRequests = DB::table('dv_requests')
            ->select(DB::raw("DATE_FORMAT(created_at, '%M %Y') as month"), DB::raw('COUNT(*) as total'))
            ->groupBy('month')
            ->orderByRaw("MIN(created_at)")
            ->pluck('total', 'month');

        $budgetPerProgram = DB::table('dv_requests')
            ->join('programs', 'dv_requests.program_id', '=', 'programs.id')
            ->select('programs.prcode', DB::raw('SUM(dv_requests.amount) as total_budget'))
            ->groupBy('programs.prcode')
            ->pluck('total_budget', 'prcode');

        $orderedBudget = collect([
            'TUPAD' => $budgetPerProgram->get('TUPAD', 0),
            'GIP'   => $budgetPerProgram->get('GIP', 0),
            'TEV'   => $budgetPerProgram->get('TEV', 0),
        ]);

        $staffPerformance = DB::table('dv_requests')
            ->select('locked_by',
                DB::raw('COUNT(*) as total_processed'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, locked_at)) as avg_hours')
            )
            ->whereNotNull('locked_by')
            ->groupBy('locked_by')
            ->get()
            ->map(function ($item) {
                $user = DB::table('users')->where('id', $item->locked_by)->first();
                $item->staff_name = $user ? $user->full_name : 'Unknown Staff';
                return $item;
            });

        $bottlenecks = $staffPerformance->sortByDesc('avg_hours')->take(5)->values();

        // Insights
        $topProgram = DB::table('programs')
            ->leftJoin('dv_requests', 'dv_requests.program_id', '=', 'programs.id')
            ->select('programs.prcode', DB::raw('COUNT(dv_requests.id) as total'))
            ->groupBy('programs.prcode')
            ->orderByDesc('total')
            ->first();

        $thisMonth = DB::table('dv_requests')->whereMonth('created_at', now()->month)->count();
        $lastMonth = DB::table('dv_requests')->whereMonth('created_at', now()->subMonth()->month)->count();
        $trend = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2) : 0;

        $insights = [];
        if ($topProgram) $insights[] = "{$topProgram->prcode} has the highest number of requests ({$topProgram->total}).";
        if ($trend > 0) $insights[] = "Requests increased by {$trend}% compared to last month.";
        elseif ($trend < 0) $insights[] = "Requests decreased by " . abs($trend) . "% compared to last month.";
        else $insights[] = "Requests remained stable compared to last month.";
        if ($pendingRequests > 0) $insights[] = "There are {$pendingRequests} requests still pending.";
        if ($approvedRequests > 0) $insights[] = "A total of {$approvedRequests} requests have been approved.";

        return response()->json([
            'requestsPerProgram' => $orderedPrograms,
            'totalRequests' => $totalRequests,
            'pendingRequests' => $pendingRequests,
            'approvedRequests' => $approvedRequests,
            'averageProcessingTime' => $averageProcessingTime,
            'documentsPerRole' => $documentsPerRole,
            'monthlyRequests' => $monthlyRequests,
            'budgetPerProgram' => $orderedBudget,
            'staffPerformance' => $staffPerformance,
            'bottlenecks' => $bottlenecks,
            'insights' => $insights,
        ]);
    }
}
