<?php

namespace App\Http\Controllers\IMSD;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Services\PusherService;
use App\Models\DocumentAgingAlert;
use App\Notifications\DocumentAgingNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class DvRequestAgingController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * Get aging documents for the IMSD dashboard
     * Documents aged 3+ days in 'submitted' or 'IMSD_received' status
     */
        public function getAgingDocuments()
    {
        try {
            Log::info('Fetching aging documents');
            
            $agingThreshold = 3; // days
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('status', ['submitted', 'IMSD_received'])
                ->where('created_at', '<=', $cutoffTime)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($request) {
                    $daysOld = now()->diffInDays($request->created_at);
                    
                    // Get the latest alert for this document
                    $latestAlert = DocumentAgingAlert::where('dv_request_id', $request->id)
                        ->where('alert_date', today())
                        ->first();
                    
                    return [
                        'id' => $request->id,
                        'alert_id' => $latestAlert ? $latestAlert->id : null, // ← ADD THIS
                        'control_number' => $request->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 5, '0', STR_PAD_LEFT) : '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'status' => $request->status,
                        'days_old' => $daysOld,
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'acknowledged' => $latestAlert ? $latestAlert->acknowledged : false, // ← ADD THIS
                    ];
                });

            Log::info('Aging documents fetched successfully', ['count' => $agingRequests->count()]);

            return response()->json([
                'success' => true,
                'aging_count' => $agingRequests->count(),
                'documents' => $agingRequests,
                'threshold_days' => $agingThreshold,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching aging documents: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch aging documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        public function getAgingStatistics()
    {
        try {
            Log::info('Fetching aging statistics');
            
            $tenDaysAgo = now()->subDays(10);
            $sevenDaysAgo = now()->subDays(7);
            $threeDaysAgo = now()->subDays(3);
            
            $statistics = [
                'critical' => DvRequest::whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process']) // ✅ Include received
                    ->where('created_at', '<', $tenDaysAgo)
                    ->count(),
                'high' => DvRequest::whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process']) // ✅ Include received
                    ->where('created_at', '>=', $tenDaysAgo)
                    ->where('created_at', '<', $sevenDaysAgo)
                    ->count(),
                'medium' => DvRequest::whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process']) // ✅ Include received
                    ->where('created_at', '>=', $sevenDaysAgo)
                    ->where('created_at', '<', $threeDaysAgo)
                    ->count(),
                'total_aging' => DvRequest::whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process']) // ✅ Include received
                    ->where('created_at', '<', $threeDaysAgo)
                    ->count(),
            ];

            Log::info('Aging statistics fetched successfully', $statistics);

            return response()->json([
                'success' => true,
                ...$statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching aging statistics: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch aging statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

     public function sendAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with('focalPerson')
                ->whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            foreach ($agingRequests as $request) {
                $daysOld = now()->diffInDays($request->created_at);
                
                // Send real-time alert via Pusher
                $this->pusher->trigger('dv-requests-imsd', 'document-aging-alert', [
                    'dv_request_id' => $request->id,
                    'control_number' => $request->accounting_control_number,
                    'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                    'program' => $request->program->prcode ?? 'N/A',
                    'status' => $request->status,
                    'days_old' => $daysOld,
                    'urgency' => $this->getUrgencyLevel($daysOld),
                    'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            }

            // Also send a general update notification
            $this->pusher->trigger('dv-requests-imsd', 'imsd-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_aging' => $agingRequests->count(),
            ]);

            Log::info('IMSD aging alerts sent', ['count' => $agingRequests->count()]);

            return response()->json([
                'success' => true,
                'alerted_count' => $agingRequests->count(),
                'message' => 'IMSD aging document alerts sent',
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending IMSD aging alerts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function getUrgencyLevel($daysOld)
    {
        if ($daysOld >= 10) return 'critical';
        if ($daysOld >= 7) return 'high';
        if ($daysOld >= 3) return 'medium';
        return 'low';
    }
}