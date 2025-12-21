<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\TriggersDashboardUpdate;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use TriggersDashboardUpdate;

    /**
     * Display the admin dashboard
     */
    public function index(Request $request)
    {
        // Get date filters from request or use defaults
        $dateFrom = $request->get('date_from', now()->subMonths(12)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        
        // Get dashboard data with filters
        $data = $this->getDashboardData($dateFrom, $dateTo);
        
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
        
        return view('Admin.dashboard', $data);
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
            
            // Add export-specific formatting
            $exportData = [
                'generated_at' => now()->toDateTimeString(),
                'date_range' => [
                    'from' => $dateFrom,
                    'to' => $dateTo
                ],
                'metrics' => [
                    'total_requests' => $data['totalRequests'],
                    'pending_requests' => $data['pendingRequests'],
                    'approved_requests' => $data['approvedRequests'],
                    'rejected_requests' => $data['rejectedRequests'],
                    'average_processing_time' => $data['averageProcessingTime'],
                ],
                'program_breakdown' => $data['requestsPerProgram'],
                'budget_breakdown' => $data['budgetPerProgram'],
                'sla_analysis' => $data['slaAnalysis'],
                'insights' => $data['insights']
            ];
            
            return response()->json([
                'success' => true,
                'data' => $exportData,
                'message' => 'Export data prepared successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare export data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}