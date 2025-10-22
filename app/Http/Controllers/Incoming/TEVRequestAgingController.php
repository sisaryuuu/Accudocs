<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DocumentAgingAlert;
use App\Services\PusherService;
use Illuminate\Support\Facades\Log;

class TEVRequestAgingController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

       public function getAgingDocuments()
    {
        try {
            Log::info('Fetching TEV aging documents');
            
            $agingThreshold = 3; // days
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('status', ['IMSD_verified', 'Incoming-TEV_received', 'Incoming-TEV_in_process'])
                ->where('created_at', '<=', $cutoffTime)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($request) {
                    $daysOld = now()->diffInDays($request->created_at);
                    
                    return [
                        'id' => $request->id,
                        'control_number' => $request->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 5, '0', STR_PAD_LEFT) : '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'status' => $request->status,
                        'days_old' => $daysOld,
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'urgency' => $this->getUrgencyLevel($daysOld),
                    ];
                });

            Log::info('TEV aging documents fetched successfully', ['count' => $agingRequests->count()]);

            return response()->json([
                'success' => true,
                'aging_count' => $agingRequests->count(),
                'documents' => $agingRequests,
                'threshold_days' => $agingThreshold,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching TEV aging documents: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch aging documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get TEV aging statistics
     */
     public function getAgingStatistics()
    {
        try {
            Log::info('Fetching TEV aging statistics');
            
            $tenDaysAgo = now()->subDays(10);
            $sevenDaysAgo = now()->subDays(7);
            $threeDaysAgo = now()->subDays(3);
            
            $statistics = [
                'critical' => DvRequest::whereIn('status', ['IMSD_verified', 'Incoming-TEV_received', 'Incoming-TEV_in_process'])
                    ->where('created_at', '<', $tenDaysAgo)
                    ->count(),
                'high' => DvRequest::whereIn('status', ['IMSD_verified', 'Incoming-TEV_received', 'Incoming-TEV_in_process'])
                    ->where('created_at', '>=', $tenDaysAgo)
                    ->where('created_at', '<', $sevenDaysAgo)
                    ->count(),
                'medium' => DvRequest::whereIn('status', ['IMSD_verified', 'Incoming-TEV_received', 'Incoming-TEV_in_process'])
                    ->where('created_at', '>=', $sevenDaysAgo)
                    ->where('created_at', '<', $threeDaysAgo)
                    ->count(),
                'total_aging' => DvRequest::whereIn('status', ['IMSD_verified', 'Incoming-TEV_received', 'Incoming-TEV_in_process'])
                    ->where('created_at', '<', $threeDaysAgo)
                    ->count(),
            ];

            Log::info('TEV aging statistics fetched successfully', $statistics);

            return response()->json([
                'success' => true,
                ...$statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching TEV aging statistics: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch aging statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determine urgency level based on days old
     */
    protected function getUrgencyLevel($daysOld)
    {
        if ($daysOld >= 10) return 'critical';
        if ($daysOld >= 7) return 'high';
        if ($daysOld >= 3) return 'medium';
        return 'low';
    }

    /**
     * Send aging alerts for TEV documents
     */
    public function sendAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with('focalPerson')
                ->whereHas('program', function($q) {
                    $q->where('prcode', 'TEV');
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-TEV_received'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            if ($agingRequests->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No aging TEV documents to alert'
                ]);
            }

            $alertedCount = 0;
            foreach ($agingRequests as $request) {
                $daysOld = now()->diffInDays($request->created_at);

                // Check if alert already exists for today
                $existingAlert = DocumentAgingAlert::where('dv_request_id', $request->id)
                    ->where('alert_date', today())
                    ->exists();

                if (!$existingAlert) {
                    // Create alert record
                    DocumentAgingAlert::create([
                        'dv_request_id' => $request->id,
                        'days_old' => $daysOld,
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'alert_date' => today(),
                    ]);

                    // Send real-time alert via Pusher
                    $this->pusher->trigger('dv-requests-incoming-tev', 'document-aging-alert', [
                        'dv_request_id' => $request->id,
                        'control_number' => $request->accounting_control_number,
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'document_type' => 'TEV',
                        'status' => $request->status,
                        'days_old' => $daysOld,
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);

                    $alertedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'alerted_count' => $alertedCount,
                'message' => 'TEV aging document alerts sent',
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending TEV aging alerts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send TEV aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}