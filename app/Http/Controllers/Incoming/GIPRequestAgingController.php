<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\DocumentAgingAlert;
use App\Services\PusherService;
use App\Notifications\DocumentAgingNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GIPRequestAgingController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * Get aging documents for GIP dashboard
     * Documents aged 3+ days in 'IMSD_verified' or 'Incoming-GIP_received' status
     */
    public function getAgingDocuments()
    {
        try {
            Log::info('Fetching GIP aging documents');
            
            $agingThreshold = 3; // days
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereHas('program', function($q) {
                    // ✅ FIX: Filter specifically for GIP program
                    $q->where('prcode', 'GIP');
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_received'])
                ->where('created_at', '<=', $cutoffTime)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($request) {
                    // FIX: Use proper days calculation with whole numbers
                    $daysOld = now()->diffInDays(Carbon::parse($request->created_at), false);
                    $daysOld = abs((int) $daysOld);
                    
                    // Get the latest alert for this document
                    $latestAlert = DocumentAgingAlert::where('dv_request_id', $request->id)
                        ->where('alert_date', today())
                        ->first();
                    
                    return [
                        'id' => $request->id,
                        'alert_id' => $latestAlert ? $latestAlert->id : null,
                        'control_number' => $request->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'document_type' => 'GIP',
                        'status' => $request->status,
                        'days_old' => $daysOld, // Now this will be a positive whole number
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'acknowledged' => $latestAlert ? $latestAlert->acknowledged : false,
                    ];
                });

            Log::info('GIP aging documents fetched successfully', ['count' => $agingRequests->count()]);

            return response()->json([
                'success' => true,
                'aging_count' => $agingRequests->count(),
                'documents' => $agingRequests,
                'threshold_days' => $agingThreshold,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching GIP aging documents: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GIP aging documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get GIP aging statistics
     */
    public function getAgingStatistics()
    {
        try {
            Log::info('Fetching GIP aging statistics');
            
            $tenDaysAgo = now()->subDays(10);
            $sevenDaysAgo = now()->subDays(7);
            $threeDaysAgo = now()->subDays(3);
            
            $baseQuery = DvRequest::whereHas('program', function($q) {
                    // ✅ FIX: Filter specifically for GIP program
                    $q->where('prcode', 'GIP');
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_received']);

            $statistics = [
                'critical' => (clone $baseQuery)->where('created_at', '<', $tenDaysAgo)->count(),
                'high' => (clone $baseQuery)->where('created_at', '>=', $tenDaysAgo)
                    ->where('created_at', '<', $sevenDaysAgo)->count(),
                'medium' => (clone $baseQuery)->where('created_at', '>=', $sevenDaysAgo)
                    ->where('created_at', '<', $threeDaysAgo)->count(),
                'total_aging' => (clone $baseQuery)->where('created_at', '<', $threeDaysAgo)->count(),
            ];

            Log::info('GIP aging statistics fetched successfully', $statistics);

            return response()->json([
                'success' => true,
                ...$statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching GIP aging statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GIP aging statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determine urgency based on days old
     */
    protected function getUrgencyLevel($daysOld)
    {
        // FIX: Ensure we're working with positive whole numbers
        $daysOld = abs((int) $daysOld);
        
        if ($daysOld >= 10) return 'critical';
        if ($daysOld >= 7) return 'high';
        if ($daysOld >= 3) return 'medium';
        return 'low';
    }

    /**
     * Send aging alerts for GIP documents
     */
    public function sendAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with('focalPerson')
                ->whereHas('program', function($q) {
                    // ✅ FIX: Filter specifically for GIP program
                    $q->where('prcode', 'GIP');
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_received'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            if ($agingRequests->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No aging GIP documents to alert'
                ]);
            }

            $alertedCount = 0;
            foreach ($agingRequests as $request) {
                // FIX: Use proper days calculation with whole numbers
                $daysOld = now()->diffInDays(Carbon::parse($request->created_at), false);
                $daysOld = abs((int) $daysOld);

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
                    $this->pusher->trigger('dv-requests-incoming-gip', 'document-aging-alert', [
                        'dv_request_id' => $request->id,
                        'control_number' => $request->accounting_control_number,
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'document_type' => 'GIP',
                        'status' => $request->status,
                        'days_old' => $daysOld, // Now this will be a positive whole number
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
                'message' => 'GIP aging document alerts sent',
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending GIP aging alerts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send GIP aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}