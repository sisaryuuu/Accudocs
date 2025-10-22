<?php

namespace App\Http\Controllers\AccountingIII;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DocumentAgingAlert;
use App\Services\PusherService;
use Illuminate\Support\Facades\Log;

class RANCARequestAgingController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * Get aging documents for RANCA - USING DV_REQUEST_ID
     */
    public function getAgingDocuments()
    {
        try {
            Log::info('Fetching RANCA aging documents using DV request IDs');
            
            $agingThreshold = 3; // days
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('status', [
                    'Outgoing_finalized',
                    'RANCA_received', 
                    'RANCA_in_process',
                ])
                ->where('created_at', '<=', $cutoffTime)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($request) {
                    // Calculate days old - ensure it's an integer
                    $daysOld = (int) now()->diffInDays($request->created_at);
                    
                    // Check if there's any alert for this document
                    $hasAlert = DocumentAgingAlert::where('dv_request_id', $request->id)
                        ->exists();
                    
                    return [
                        'id' => $request->id,
                        'control_number' => $request->control_number ?? '#' . $request->id,
                        'dv_number' => $request->dv_number ?? '—',
                        'ors_number' => $request->ors_number ?? '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')) ?: 'Unknown',
                        'program' => $request->program->prcode ?? 'N/A',
                        'respocenter' => $request->respocenter->respocenter_code ?? 'N/A',
                        'amount' => $request->amount ?? 0,
                        'current_status' => $request->status,
                        'status' => $request->status, // Add this for JS compatibility
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'days_in_status' => $daysOld,
                        'days_old' => $daysOld, // Add this explicitly for JS
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'has_alert' => $hasAlert,
                    ];
                });

            Log::info('RANCA aging documents fetched successfully using DV request IDs', ['count' => $agingRequests->count()]);

            return response()->json([
                'success' => true,
                'aging_count' => $agingRequests->count(),
                'documents' => $agingRequests,
                'threshold_days' => $agingThreshold,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching RANCA aging documents: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch RANCA aging documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get RANCA aging statistics - USING DV_REQUEST_ID
     */
    public function getAgingStatistics()
    {
        try {
            Log::info('Fetching RANCA aging statistics using DV request IDs');
            
            $tenDaysAgo = now()->subDays(10);
            $sevenDaysAgo = now()->subDays(7);
            $threeDaysAgo = now()->subDays(3);
            
            $baseQuery = DvRequest::whereIn('status', [
                'Outgoing_finalized',
                'RANCA_received',
                'RANCA_in_process',
            ]);

            $statistics = [
                'critical' => (clone $baseQuery)->where('created_at', '<', $tenDaysAgo)->count(),
                'high' => (clone $baseQuery)
                    ->where('created_at', '>=', $tenDaysAgo)
                    ->where('created_at', '<', $sevenDaysAgo)
                    ->count(),
                'medium' => (clone $baseQuery)
                    ->where('created_at', '>=', $sevenDaysAgo)
                    ->where('created_at', '<', $threeDaysAgo)
                    ->count(),
                'total_aging' => (clone $baseQuery)->where('created_at', '<', $threeDaysAgo)->count(),
            ];

            Log::info('RANCA aging statistics fetched successfully', $statistics);

            return response()->json([
                'success' => true,
                ...$statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching RANCA aging statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch RANCA aging statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send aging alerts for RANCA documents - USING DV_REQUEST_ID
     */
    public function sendAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('status', [
                    'Outgoing_finalized',
                    'RANCA_received',
                    'RANCA_in_process',
                ])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            if ($agingRequests->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No aging RANCA documents to alert'
                ]);
            }

            $alertedCount = 0;
            foreach ($agingRequests as $request) {
                $daysOld = (int) now()->diffInDays($request->created_at);

                // Check if alert already exists for today
                $existingAlert = DocumentAgingAlert::where('dv_request_id', $request->id)
                    ->where('alert_date', today())
                    ->exists();

                if (!$existingAlert) {
                    // Create alert record using dv_request_id
                    DocumentAgingAlert::create([
                        'dv_request_id' => $request->id,
                        'days_old' => $daysOld,
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'alert_date' => today(),
                    ]);

                    // Send real-time alert via Pusher
                    $this->pusher->trigger('dv-requests-ranca', 'ranca-aging-alert', [
                        'dv_request_id' => $request->id,
                        'control_number' => $request->control_number ?? '#' . $request->id,
                        'dv_number' => $request->dv_number ?? '—',
                        'ors_number' => $request->ors_number ?? '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')) ?: 'Unknown',
                        'program' => $request->program->prcode ?? 'N/A',
                        'respocenter' => $request->respocenter->respocenter_code ?? 'N/A',
                        'amount' => $request->amount ?? 0,
                        'current_status' => $request->status,
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
                'message' => 'RANCA aging alerts sent successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending RANCA aging alerts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send RANCA aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get aging report for RANCA
     */
    public function getAgingReport()
    {
        try {
            $agingThreshold = 3;
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('status', [
                    'Outgoing_finalized',
                    'RANCA_received',
                    'RANCA_in_process',
                ])
                ->where('created_at', '<=', $cutoffTime)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($request) {
                    $daysOld = (int) now()->diffInDays($request->created_at);
                    
                    return [
                        'id' => $request->id,
                        'control_number' => $request->control_number ?? '#' . $request->id,
                        'dv_number' => $request->dv_number ?? '—',
                        'ors_number' => $request->ors_number ?? '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')) ?: 'Unknown',
                        'program' => $request->program->prcode ?? 'N/A',
                        'respocenter' => $request->respocenter->respocenter_code ?? 'N/A',
                        'amount' => $request->amount ?? 0,
                        'current_status' => $request->status,
                        'submitted_date' => $request->created_at->format('M d, Y'),
                        'days_old' => $daysOld,
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'aging_category' => $this->getAgingCategory($daysOld),
                    ];
                });

            // Group by urgency level for reporting
            $groupedReport = $agingRequests->groupBy('urgency')->map(function ($group, $urgency) {
                return [
                    'count' => $group->count(),
                    'documents' => $group->values()
                ];
            });

            return response()->json([
                'success' => true,
                'report' => $groupedReport,
                'total_documents' => $agingRequests->count(),
                'threshold_days' => $agingThreshold,
                'generated_at' => now()->format('M d, Y h:i A'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating RANCA aging report: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate aging report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clean up old alerts (keep only alerts from last 30 days)
     */
    public function cleanupOldAlerts()
    {
        try {
            $thirtyDaysAgo = now()->subDays(30);
            
            $deletedCount = DocumentAgingAlert::where('alert_date', '<', $thirtyDaysAgo)
                ->delete();

            Log::info('RANCA old alerts cleanup completed', ['deleted_count' => $deletedCount]);

            return response()->json([
                'success' => true,
                'message' => 'Old alerts cleaned up successfully',
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error cleaning up RANCA old alerts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup old alerts',
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

    protected function getAgingCategory($daysOld)
    {
        if ($daysOld >= 10) return 'Critical (10+ days)';
        if ($daysOld >= 7) return 'High (7-9 days)';
        if ($daysOld >= 3) return 'Medium (3-6 days)';
        return 'Low (0-2 days)';
    }
}