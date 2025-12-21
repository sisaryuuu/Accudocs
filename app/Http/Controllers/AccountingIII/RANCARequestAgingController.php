<?php

namespace App\Http\Controllers\AccountingIII;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DocumentAgingAlert;
use App\Services\PusherService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Traits\EmailBatchingTrait;
class RANCARequestAgingController extends Controller
{
    use EmailBatchingTrait;
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * Get aging documents for RANCA dashboard
     */
    public function getAgingDocuments()
    {
        try {
            Log::info('Fetching RANCA aging documents');
            
            $agingThreshold = 3; // days
            // Use startOfDay() to compare calendar days, not exact hours
            $cutoffTime = now()->subDays($agingThreshold)->startOfDay();

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
                    // Calculate whole calendar days
                    $daysOld = floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay()));
                    
                    // Get the latest alert for this DV request if it exists
                    $latestAlert = DocumentAgingAlert::where('dv_request_id', $request->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    return [
                        'id' => $request->id,
                        'alert_id' => $latestAlert ? $latestAlert->id : null,
                        'control_number' => $request->control_number ?? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number ?? $request->id, 4, '0', STR_PAD_LEFT),
                        'dv_number' => $request->dv_number ?? '—',
                        'ors_number' => $request->ors_number ?? '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')) ?: 'Unknown',
                        'program' => $request->program->prcode ?? 'N/A',
                        'respocenter' => $request->respocenter->respocenter_code ?? 'N/A',
                        'amount' => $request->amount ?? 0,
                        'document_type' => 'RANCA',
                        'current_status' => $request->status,
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'days_old' => $daysOld,
                        'days_in_status' => $daysOld,
                        'urgency' => $this->getUrgencyLevel($daysOld),
                    ];
                });

            Log::info('RANCA aging documents fetched successfully', ['count' => $agingRequests->count()]);

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
     * Get RANCA aging statistics
     */
    public function getAgingStatistics()
    {
        try {
            Log::info('Fetching RANCA aging statistics');
            
            // Use startOfDay() for consistent calculations
            $tenDaysAgo = now()->subDays(10)->startOfDay();
            $sevenDaysAgo = now()->subDays(7)->startOfDay();
            $threeDaysAgo = now()->subDays(3)->startOfDay();
            
            $baseQuery = DvRequest::whereIn('status', [
                'Outgoing_finalized',
                'RANCA_received',
                'RANCA_in_process',
            ]);

            $statistics = [
                'critical' => (clone $baseQuery)->where('created_at', '<', $tenDaysAgo)->count(),
                'high' => (clone $baseQuery)->where('created_at', '>=', $tenDaysAgo)
                    ->where('created_at', '<', $sevenDaysAgo)->count(),
                'medium' => (clone $baseQuery)->where('created_at', '>=', $sevenDaysAgo)
                    ->where('created_at', '<', $threeDaysAgo)->count(),
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
     * Send aging alerts for RANCA documents
     */
    public function sendAgingAlerts($criticalOnly = false)
    {
        try {
            Log::info('Starting RANCA aging alerts', ['criticalOnly' => $criticalOnly]);
            
            if ($criticalOnly) {
                return $this->sendCriticalRANCAAgingAlerts();
            } else {
                return $this->sendAllRANCAAgingAlerts();
            }

        } catch (\Exception $e) {
            Log::error('Error in sendAgingAlerts for RANCA: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send RANCA aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send alerts for ALL aging RANCA documents (3+ days)
     */
    private function sendAllRANCAAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            // Use startOfDay() for consistent day calculation
            $cutoffTime = now()->subDays($agingThreshold)->startOfDay();

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('status', [
                    'Outgoing_finalized',
                    'RANCA_received',
                    'RANCA_in_process',
                ])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            $alertedCount = 0;
            foreach ($agingRequests as $request) {
                // Calculate whole calendar days
                $daysOld = floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay()));

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
                    $this->pusher->trigger('dv-requests-ranca', 'ranca-aging-alert', [
                        'dv_request_id' => $request->id,
                        'control_number' => $request->control_number ?? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number ?? $request->id, 4, '0', STR_PAD_LEFT),
                        'dv_number' => $request->dv_number ?? '—',
                        'ors_number' => $request->ors_number ?? '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')) ?: 'Unknown',
                        'program' => $request->program->prcode ?? 'N/A',
                        'respocenter' => $request->respocenter->respocenter_code ?? 'N/A',
                        'amount' => $request->amount ?? 0,
                        'current_status' => $request->status,
                        'days_old' => $daysOld,
                        'days_in_status' => $daysOld,
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'alert_type' => 'all',
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);

                    $alertedCount++;
                }
            }

            $this->pusher->trigger('dv-requests-ranca', 'ranca-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_aging' => $agingRequests->count(),
                'alerted_count' => $alertedCount,
                'alert_type' => 'all',
            ]);

            Log::info('RANCA all aging alerts sent', [
                'count' => $alertedCount,
                'threshold' => $agingThreshold,
            ]);

            return response()->json([
                'success' => true,
                'alerted_count' => $alertedCount,
                'total_aging' => $agingRequests->count(),
                'threshold_days' => $agingThreshold,
                'alert_type' => 'all',
                'message' => "RANCA aging document alerts sent (3+ days)",
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending all RANCA aging alerts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send alerts for CRITICAL aging RANCA documents (10+ days)
     */
    private function sendCriticalRANCAAgingAlerts()
    {
        try {
            $criticalThreshold = 10;
            // Use startOfDay() for consistent day calculation
            $cutoffTime = now()->subDays($criticalThreshold)->startOfDay();

            $criticalRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('status', [
                    'Outgoing_finalized',
                    'RANCA_received',
                    'RANCA_in_process',
                ])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            $alertedCount = 0;
            foreach ($criticalRequests as $request) {
                // Calculate whole calendar days
                $daysOld = floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay()));

                // Send real-time alert via Pusher for critical
                $this->pusher->trigger('dv-requests-ranca', 'ranca-critical-aging-alert', [
                    'dv_request_id' => $request->id,
                    'control_number' => $request->control_number ?? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number ?? $request->id, 4, '0', STR_PAD_LEFT),
                    'dv_number' => $request->dv_number ?? '—',
                    'ors_number' => $request->ors_number ?? '—',
                    'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')) ?: 'Unknown',
                    'program' => $request->program->prcode ?? 'N/A',
                    'respocenter' => $request->respocenter->respocenter_code ?? 'N/A',
                    'amount' => $request->amount ?? 0,
                    'current_status' => $request->status,
                    'days_old' => $daysOld,
                    'days_in_status' => $daysOld,
                    'urgency' => $this->getUrgencyLevel($daysOld),
                    'alert_type' => 'critical',
                    'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                    'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);

                $alertedCount++;
            }

            $this->pusher->trigger('dv-requests-ranca', 'ranca-critical-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_critical' => $criticalRequests->count(),
                'alerted_count' => $alertedCount,
                'alert_type' => 'critical',
                'threshold_days' => $criticalThreshold,
            ]);

            Log::warning('RANCA critical aging alerts sent', [
                'count' => $alertedCount,
                'threshold' => $criticalThreshold,
            ]);

            return response()->json([
                'success' => true,
                'alerted_count' => $alertedCount,
                'total_critical' => $criticalRequests->count(),
                'threshold_days' => $criticalThreshold,
                'alert_type' => 'critical',
                'message' => "RANCA critical aging document alerts sent (10+ days)",
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending critical RANCA aging alerts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
 * Notify focal persons when RANCA documents are 10+ days old
 * Updated to match GIP structure with proper date handling
 */
 public function notifyFocalPersonsOfAgingDocuments()
    {
        try {
            Log::info('🚀 Starting RANCA focal person aging notifications with batching');
            
            $criticalThreshold = 10;
            $now = now('Asia/Manila');
            $cutoffDate = $now->copy()->subDays($criticalThreshold);

            // Define completed statuses for RANCA
            $completedStatuses = [
                'cashier_released',
                '%_rejected',
                'completed',
                'accounting_released',
            ];

            Log::info('RANCA notification parameters with batching', [
                'current_date' => $now->toDateTimeString(),
                'cutoff_date' => $cutoffDate->toDateTimeString(),
                'threshold_days' => $criticalThreshold,
                'batching_enabled' => true
            ]);

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('status', [
                    'Outgoing_finalized',
                    'RANCA_received',
                    'RANCA_in_process',
                ])
                ->whereNotIn('status', $completedStatuses)
                ->where('created_at', '<=', $cutoffDate)
                ->get();

            Log::info('RANCA requests for notification', [
                'total_requests' => $agingRequests->count(),
                'threshold_days' => $criticalThreshold,
            ]);

            $notifiedCount = 0;
            $skippedCount = 0;
            $alreadyNotified = 0;
            $errors = [];

            // Filter out already notified today and calculate days
            $eligibleRequests = [];
            foreach ($agingRequests as $request) {
                try {
                    if (!$request->focalPerson) {
                        $skippedCount++;
                        continue;
                    }

                    $createdAt = Carbon::parse($request->created_at, 'Asia/Manila');
                    $daysOld = abs(floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay(), false))) ?: 0;
                    
                    if ($createdAt->isFuture()) {
                        $skippedCount++;
                        continue;
                    }
                    
                    if ($daysOld < $criticalThreshold) {
                        $skippedCount++;
                        continue;
                    }

                    // Check if already notified today
                    $alreadySent = DB::table('notifications')
                        ->where('focalperson_id', $request->focalPerson->id)
                        ->where('dv_request_id', $request->id)
                        ->where('status', 'document_aging')
                        ->whereDate('created_at', today())
                        ->exists(); 

                    if ($alreadySent) {
                        $alreadyNotified++;
                        continue;
                    }

                    $urgencyLevel = $this->getUrgencyLevel($daysOld);
                    
                    // Add to eligible requests
                    $eligibleRequests[] = [
                        'request' => $request,
                        'days_old' => $daysOld,
                        'urgency_level' => $urgencyLevel
                    ];

                } catch (\Exception $e) {
                    $errors[] = [
                        'request_id' => $request->id,
                        'error' => $e->getMessage()
                    ];
                    Log::error("RANCA error processing request {$request->id}: " . $e->getMessage());
                }
            }

            Log::info('RANCA - Eligible requests for email batching', [
                'eligible_count' => count($eligibleRequests),
                'skipped_count' => $skippedCount,
                'already_notified' => $alreadyNotified
            ]);

            // Send emails using batching
            $emailResults = $this->sendAgingEmailsWithBatching(
                array_column($eligibleRequests, 'request'),
                function($request, $email, $name) use ($eligibleRequests) {
                    $requestData = collect($eligibleRequests)->firstWhere('request.id', $request->id);
                    $daysOld = $requestData['days_old'] ?? 0;
                    $urgencyLevel = $requestData['urgency_level'] ?? 'critical';
                    
                    return $this->sendIndividualAgingEmail($request, $email, $name, $daysOld, $urgencyLevel);
                }
            );

            // Create database notifications for successful emails
            foreach ($eligibleRequests as $eligible) {
                $request = $eligible['request'];
                $daysOld = $eligible['days_old'];
                
                DB::table('notifications')->insert([
                    'focalperson_id' => $request->focalPerson->id,
                    'dv_request_id' => $request->id,
                    'status' => 'document_aging',
                    'action' => 'Delayed',
                    'remarks' => "RANCA document has been pending for {$daysOld} days",
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $notifiedCount = $emailResults['success'];

            Log::info('🎯 RANCA focal person aging notifications completed with batching', [
                'total_candidates' => $agingRequests->count(),
                'notified_count' => $notifiedCount,
                'already_notified_today' => $alreadyNotified,
                'skipped_count' => $skippedCount,
                'email_results' => $emailResults,
                'errors_count' => count($errors),
            ]);

            return response()->json([
                'success' => true,
                'notified_count' => $notifiedCount,
                'already_notified_today' => $alreadyNotified,
                'skipped_count' => $skippedCount,
                'email_results' => $emailResults,
                'errors' => $errors,
                'threshold_days' => $criticalThreshold,
                'batching_used' => true,
                'message' => "Successfully notified {$notifiedCount} focal person(s) of RANCA {$criticalThreshold}+-day aging documents (using batching)",
            ]);

        } catch (\Exception $e) {
            Log::error('❌ RANCA error sending focal person aging notifications: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send RANCA aging notifications',
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
            $alerts = DocumentAgingAlert::with(['dvRequest.focalPerson', 'dvRequest.program', 'dvRequest.respocenter'])
                ->whereHas('dvRequest', function($query) {
                    $query->whereIn('status', [
                        'Outgoing_finalized',
                        'RANCA_received',
                        'RANCA_in_process',
                    ]);
                })
                ->where('alert_date', today())
                ->where('acknowledged', false)
                ->orderBy('days_old', 'desc')
                ->get()
                ->map(function ($alert) {
                    return [
                        'id' => $alert->id,
                        'dv_request_id' => $alert->dv_request_id,
                        'control_number' => $alert->dvRequest->control_number ?? 
                            'CN #-' . now()->year . '-' . str_pad($alert->dvRequest->accounting_control_number ?? $alert->dvRequest->id, 4, '0', STR_PAD_LEFT),
                        'dv_number' => $alert->dvRequest->dv_number ?? '—',
                        'ors_number' => $alert->dvRequest->ors_number ?? '—',
                        'submitted_by' => trim(($alert->dvRequest->focalPerson->fname ?? '') . ' ' . ($alert->dvRequest->focalPerson->lname ?? '')) ?: 'Unknown',
                        'program' => $alert->dvRequest->program->prcode ?? 'N/A',
                        'respocenter' => $alert->dvRequest->respocenter->respocenter_code ?? 'N/A',
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
            Log::error('Error fetching user RANCA aging alerts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get urgency level based on days old
     */
    protected function getUrgencyLevel($daysOld)
    {
        if ($daysOld >= 10) {
            return 'critical';
        } elseif ($daysOld >= 7) {
            return 'high';
        } elseif ($daysOld >= 3) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get urgency text for notifications
     */
    private function getUrgencyText($urgencyLevel)
    {
        $urgencyMap = [
            'critical' => '🚨 CRITICAL',
            'high' => '⚠️ HIGH PRIORITY',
            'medium' => '📋 MEDIUM PRIORITY',
            'low' => 'ℹ️ LOW PRIORITY',
        ];
        
        return $urgencyMap[$urgencyLevel] ?? 'ℹ️ NOTIFICATION';
    }

    /**
     * Get urgency emoji for email content
     */
    private function getUrgencyEmoji($urgencyLevel)
    {
        $emojiMap = [
            'critical' => '🚨',
            'high' => '⚠️',
            'medium' => '📋',
            'low' => 'ℹ️',
        ];
        
        return $emojiMap[$urgencyLevel] ?? '📄';
    }

    /**
     * Get aging category for reporting
     */
    protected function getAgingCategory($daysOld)
    {
        if ($daysOld >= 10) return 'Critical (10+ days)';
        if ($daysOld >= 7) return 'High (7-9 days)';
        if ($daysOld >= 3) return 'Medium (3-6 days)';
        return 'Low (0-2 days)';
    }
}