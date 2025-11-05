<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\TriggersDashboardUpdate;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use TriggersDashboardUpdate;

    /**
     * Display the admin dashboard
     */
    public function index()
    {
        $data = $this->getDashboardData();
        
        // Add Pusher config for frontend
        $data['pusher_key'] = env('PUSHER_APP_KEY');
        $data['pusher_cluster'] = env('PUSHER_APP_CLUSTER');
        
        return view('Admin.dashboard', $data);
    }

    /**
     * API endpoint to get current dashboard data
     */
    public function fetchData(Request $request)
    {
        try {
            $data = $this->getDashboardData();
            
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
     * Filter dashboard data by date range
     */
    public function filter(Request $request)
    {
        try {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            
            // Get filtered data based on date range
            $data = $this->getFilteredDashboardData($startDate, $endDate);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]);
            }
            
            return view('Admin.dashboard', $data);
            
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to filter dashboard data',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->route('Admin.dashboard')
                ->with('error', 'Failed to apply filters');
        }
    }

    /**
     * Manual trigger endpoint for real-time updates
     */
    public function refresh()
    {
        $this->triggerDashboardUpdate();
        
        return response()->json([
            'success' => true,
            'message' => 'Dashboard refresh triggered',
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get filtered dashboard data based on date range
     */
    private function getFilteredDashboardData($startDate = null, $endDate = null)
    {
        // Use your existing getDashboardData method but with date filters
        $data = $this->getDashboardData();
        
        // If no dates provided, return regular data
        if (!$startDate || !$endDate) {
            return $data;
        }
        
        // Add date filtering logic here for each metric
        // This is a simplified version - you'll need to implement actual filtering
        
        return array_merge($data, [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'applied' => true
            ]
        ]);
    }
}