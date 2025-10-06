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

            // Pending (example: still submitted)
    $pendingRequests = DB::table('dv_requests')
        ->where('status', 'submitted')
        ->count();

        // Approved (example: final stage)
    $approvedRequests = DB::table('dv_requests')
        ->where('status', 'approved') // ⚠️ i-adjust depende sa final stage
        ->count();
       

        // Documents handled per role
    $documentsPerRole = DB::table('dv_request_logs')
        ->select('action', DB::raw('COUNT(*) as total'))
        ->groupBy('action')
        ->pluck('total', 'action');

// Monthly Requests Trend (formatted)
$monthlyRequests = DB::table('dv_requests')
    ->select(DB::raw("DATE_FORMAT(created_at, '%M %Y') as month"), DB::raw('COUNT(*) as total'))
    ->groupBy('month')
    ->orderByRaw("MIN(created_at)") // para sakto order by actual date
    ->pluck('total', 'month');

 // Top Program
        $topProgram = DB::table('programs')
            ->leftJoin('dv_requests', 'dv_requests.program_id', '=', 'programs.id')
            ->select('programs.prcode', DB::raw('COUNT(dv_requests.id) as total'))
            ->groupBy('programs.prcode')
            ->orderByDesc('total')
            ->first();

            // Monthly trend (compare current vs last month)
        $thisMonth = DB::table('dv_requests')
            ->whereMonth('created_at', now()->month)
            ->count();
        $lastMonth = DB::table('dv_requests')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();
        $trend = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2) : 0;

 // Auto-generate insights
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
    

    return view('admin.dashboard', [
        'requestsPerProgram' => $orderedPrograms,
        'totalRequests'      => $totalRequests,
        'pendingRequests'    => $pendingRequests,
        'approvedRequests'   => $approvedRequests,
        'averageProcessingTime' => $averageProcessingTime,
        'documentsPerRole'   => $documentsPerRole,
        'monthlyRequests' => $monthlyRequests,
        'insights' => $insights
    ]);
    }
}
