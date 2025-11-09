<?php

namespace App\Http\Controllers\BudgetOfficer;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DocumentAgingAlert;
use App\Services\PusherService;
use Illuminate\Support\Facades\Log;

class ORSRequestAgingController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * Get aging ORS documents for Budget Officer - USING DV_REQUEST_ID
     */
    public function getAgingDocuments()
{
    try {
        Log::info('Fetching ORS aging documents using DV request IDs');
        
        $agingThreshold = 3; // days
        $cutoffTime = now()->subDays($agingThreshold);

        $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter', 'orsRequest'])
            ->whereIn('status', [
                'Incoming-GIP_verified',
                'Incoming-TUPAD_verified', 
                'Incoming-TEV_verified',
                'ORS_received',
                'ORS_in_process',
            ])
            ->where('created_at', '<=', $cutoffTime)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($request) {
                $daysOld = now()->diffInDays($request->created_at);
                
                // Get the latest alert for this DV request if it exists
                $latestAlert = DocumentAgingAlert::where('dv_request_id', $request->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                return [
                    'id' => $request->id, // This is the DV request ID
                    'alert_id' => $latestAlert ? $latestAlert->id : null,
                    'control_number' => $request->accounting_control_number ? 
                        'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
                    'ors_number' => $request->orsRequest->ors_number ?? '—',
                    'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                    'program' => $request->program->prcode ?? 'N/A',
                    'current_status' => $request->status,
                    'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                    'days_in_status' => $daysOld,
                    'urgency' => $this->getUrgencyLevel($daysOld),
                ];
            });

        Log::info('ORS aging documents fetched successfully using DV request IDs', ['count' => $agingRequests->count()]);

        return response()->json([
            'success' => true,
            'aging_count' => $agingRequests->count(),
            'documents' => $agingRequests,
            'threshold_days' => $agingThreshold,
        ]);

    } catch (\Exception $e) {
        Log::error('Error fetching Budget Officer ORS aging documents: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch ORS aging documents',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Get ORS aging statistics - USING DV_REQUEST_ID
     */
    public function getAgingStatistics()
    {
        try {
            Log::info('Fetching ORS aging statistics using DV request IDs');
            
            $sevenDaysAgo = now()->subDays(7);
            $fiveDaysAgo = now()->subDays(5);
            $threeDaysAgo = now()->subDays(3);
            
            $baseQuery = DvRequest::whereIn('status', [
                'Incoming-GIP_verified',
                'Incoming-TUPAD_verified',
                'Incoming-TEV_verified',
                'ORS_received',
                'ORS_in_process',
            ]);

            $statistics = [
                'critical' => (clone $baseQuery)->where('created_at', '<', $sevenDaysAgo)->count(),
                'high' => (clone $baseQuery)->where('created_at', '>=', $sevenDaysAgo)
                    ->where('created_at', '<', $fiveDaysAgo)->count(),
                'medium' => (clone $baseQuery)->where('created_at', '>=', $fiveDaysAgo)
                    ->where('created_at', '<', $threeDaysAgo)->count(),
                'total_aging' => (clone $baseQuery)->where('created_at', '<', $threeDaysAgo)->count(),
            ];

            Log::info('ORS aging statistics fetched successfully', $statistics);

            return response()->json([
                'success' => true,
                ...$statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching Budget Officer ORS aging statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ORS aging statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send aging alerts for ORS documents - USING DV_REQUEST_ID
     */
    public function sendAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'orsRequest', 'program'])
                ->whereIn('status', [
                    'Incoming-GIP_verified',
                    'Incoming-TUPAD_verified',
                    'Incoming-TEV_verified',
                    'ORS_received',
                    'ORS_in_process',
                ])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            if ($agingRequests->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No aging ORS documents to alert'
                ]);
            }

            $alertedCount = 0;
            foreach ($agingRequests as $request) {
                $daysOld = now()->diffInDays($request->created_at);

                // ✅ Use DocumentAgingAlert with dv_request_id
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
                    $this->pusher->trigger('dv-requests-budget-officer', 'ors-aging-alert', [
                        'dv_request_id' => $request->id,
                        'control_number' => $request->accounting_control_number,
                        'ors_number' => $request->orsRequest->ors_number ?? '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
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
                'message' => 'ORS aging alerts sent successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending ORS aging alerts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send ORS aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function getUrgencyLevel($daysOld)
    {
        if ($daysOld >= 7) return 'critical';
        if ($daysOld >= 5) return 'high';
        if ($daysOld >= 3) return 'medium';
        return 'low';
    }
}