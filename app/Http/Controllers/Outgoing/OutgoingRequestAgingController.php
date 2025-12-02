<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\DocumentAgingAlert;
use App\Services\PusherService;
use App\Notifications\DocumentAgingNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class OutgoingRequestAgingController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * Get aging documents for Outgoing dashboard
     * Documents aged 3+ days in 'ORS_obligated' or 'Outgoing_received' status
     */
    public function getAgingDocuments()
    {
        try {
            Log::info('Fetching Outgoing aging documents');
            
            $agingThreshold = 3; // days
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('status', ['ORS_obligated', 'Outgoing_received'])
                ->where('created_at', '<=', $cutoffTime)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($request) use ($cutoffTime) {
                    // Cast to integer to ensure whole number
                    $daysOld = (int) $request->created_at->diffInDays(now());
                    
                    return [
                        'id' => $request->id,
                        'control_number' => $request->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'status' => $request->status,
                        'days_old' => $daysOld,
                        'urgency' => $this->getUrgencyLevel($daysOld),
                    ];
                });

            Log::info('Outgoing aging documents fetched successfully', ['count' => $agingRequests->count()]);

            return response()->json([
                'success' => true,
                'documents' => $agingRequests,
                'aging_count' => $agingRequests->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching Outgoing aging documents: ' . $e->getMessage());
            
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
            $tenDaysAgo = now()->subDays(10);
            $sevenDaysAgo = now()->subDays(7);
            $threeDaysAgo = now()->subDays(3);
            
            $baseQuery = DvRequest::whereIn('status', ['ORS_obligated', 'Outgoing_received']);

            $statistics = [
                'critical' => (clone $baseQuery)->where('created_at', '<=', $tenDaysAgo)->count(),
                'high' => (clone $baseQuery)->where('created_at', '>', $tenDaysAgo)
                    ->where('created_at', '<=', $sevenDaysAgo)->count(),
                'medium' => (clone $baseQuery)->where('created_at', '>', $sevenDaysAgo)
                    ->where('created_at', '<=', $threeDaysAgo)->count(),
                'total_aging' => (clone $baseQuery)->where('created_at', '<=', $threeDaysAgo)->count(),
            ];

            return response()->json([
                'success' => true,
                ...$statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching Outgoing aging statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determine urgency based on days old
     */
    protected function getUrgencyLevel($daysOld)
    {
        if ($daysOld >= 10) return 'critical';
        if ($daysOld >= 7) return 'high';
        if ($daysOld >= 3) return 'medium';
        return 'low';
    }

    /**
     * Send aging alerts for Outgoing documents
     */
    public function sendAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with('focalPerson')
                ->whereIn('status', ['ORS_obligated', 'Outgoing_received'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            if ($agingRequests->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No aging Outgoing documents to alert'
                ]);
            }

            $alertedCount = 0;
            foreach ($agingRequests as $request) {
                // Cast to integer to ensure whole number
                $daysOld = (int) now()->diffInDays($request->created_at);

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
                    $this->pusher->trigger('dv-requests-outgoing', 'document-aging-alert', [
                        'dv_request_id' => $request->id,
                        'control_number' => $request->accounting_control_number,
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'document_type' => 'Outgoing',
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
                'message' => 'Outgoing aging document alerts sent',
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending Outgoing aging alerts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send Outgoing aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Acknowledge aging alert
     */
    public function acknowledgeAlert($alertId)
    {
        try {
            $alert = DocumentAgingAlert::findOrFail($alertId);
            
            $alert->update([
                'acknowledged' => true,
                'acknowledged_at' => now(),
                'acknowledged_by' => auth()->id(),
            ]);

            Log::info('Outgoing aging alert acknowledged', [
                'alert_id' => $alertId,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aging alert acknowledged successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error acknowledging Outgoing aging alert: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to acknowledge aging alert',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get aging alerts for specific user
     */
    public function getUserAgingAlerts()
    {
        try {
            $userId = auth()->id();
            
            $alerts = DocumentAgingAlert::with(['dvRequest.focalPerson', 'dvRequest.program'])
                ->whereHas('dvRequest', function($query) {
                    $query->whereIn('status', ['ORS_obligated', 'Outgoing_received']);
                })
                ->where('alert_date', today())
                ->where('acknowledged', false)
                ->orderBy('days_old', 'desc')
                ->get()
                ->map(function ($alert) {
                    return [
                        'id' => $alert->id,
                        'dv_request_id' => $alert->dv_request_id,
                        'control_number' => $alert->dvRequest->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($alert->dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
                        'submitted_by' => trim(($alert->dvRequest->focalPerson->fname ?? '') . ' ' . ($alert->dvRequest->focalPerson->lname ?? '')),
                        'program' => $alert->dvRequest->program->prcode ?? 'N/A',
                        'days_old' => $alert->days_old,
                        'urgency' => $alert->urgency,
                        'alert_date' => $alert->alert_date->format('M d, Y'),
                    ];
                });

            return response()->json([
                'success' => true,
                'alerts' => $alerts,
                'count' => $alerts->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching user Outgoing aging alerts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}