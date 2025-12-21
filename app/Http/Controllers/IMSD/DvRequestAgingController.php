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
use Illuminate\Support\Facades\DB;

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
                        'alert_id' => $latestAlert ? $latestAlert->id : null,
                        'control_number' => $request->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'status' => $request->status,
                        'days_old' => $daysOld,
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'urgency' => $this->getUrgencyLevel($daysOld),
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
        
        // Define completed statuses
        $completedStatuses = [
            'cashier_released',
            '%_rejected',
        ];
        
        $tenDaysAgo = now()->subDays(10);
        $sevenDaysAgo = now()->subDays(7);
        $threeDaysAgo = now()->subDays(3);
        
        $statistics = [
            'critical' => DvRequest::whereNotIn('status', $completedStatuses)
                ->where('created_at', '<', $tenDaysAgo)
                ->count(),
            'high' => DvRequest::whereNotIn('status', $completedStatuses)
                ->where('created_at', '>=', $tenDaysAgo)
                ->where('created_at', '<', $sevenDaysAgo)
                ->count(),
            'medium' => DvRequest::whereNotIn('status', $completedStatuses)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->where('created_at', '<', $threeDaysAgo)
                ->count(),
            'total_aging' => DvRequest::whereNotIn('status', $completedStatuses)
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

      public function sendAgingAlerts($criticalOnly = false)
    {
        try {
            Log::info('Starting aging alerts', ['criticalOnly' => $criticalOnly]);
            
            if ($criticalOnly) {
                // Send only critical alerts (10+ days)
                return $this->sendCriticalAgingAlerts();
            } else {
                // Send all alerts (3+ days)
                return $this->sendAllAgingAlerts();
            }

        } catch (\Exception $e) {
            Log::error('Error in sendAgingAlerts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send alerts for ALL aging documents (3+ days)
     */
    private function sendAllAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with('focalPerson')
                ->whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            foreach ($agingRequests as $request) {
                 $daysOld = abs(floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay(), false))) ?: 0;
                // Send real-time alert via Pusher
                $this->pusher->trigger('dv-requests-imsd', 'document-aging-alert', [
                    'dv_request_id' => $request->id,
                    'control_number' => $request->accounting_control_number,
                    'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                    'program' => $request->program->prcode ?? 'N/A',
                    'status' => $request->status,
                    'days_old' => $daysOld,
                    'urgency' => $this->getUrgencyLevel($daysOld),
                    'alert_type' => 'all',
                    'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            }

            // Also send a general update notification
            $this->pusher->trigger('dv-requests-imsd', 'imsd-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_aging' => $agingRequests->count(),
                'alert_type' => 'all',
            ]);

            Log::info('All aging alerts sent', [
                'count' => $agingRequests->count(),
                'threshold' => $agingThreshold,
            ]);

            return response()->json([
                'success' => true,
                'alerted_count' => $agingRequests->count(),
                'threshold_days' => $agingThreshold,
                'alert_type' => 'all',
                'message' => "All aging document alerts sent (3+ days)",
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending all aging alerts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send alerts for CRITICAL aging documents (10+ days)
     */
    private function sendCriticalAgingAlerts()
    {
        try {
            $criticalThreshold = 10;
            $cutoffTime = now()->subDays($criticalThreshold);

            $criticalRequests = DvRequest::with('focalPerson')
                ->whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            foreach ($criticalRequests as $request) {
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
                    'alert_type' => 'critical',
                    'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            }

            // Also send a critical update notification
            $this->pusher->trigger('dv-requests-imsd', 'imsd-critical-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_critical' => $criticalRequests->count(),
                'alert_type' => 'critical',
                'threshold_days' => $criticalThreshold,
            ]);

            Log::warning('Critical aging alerts sent', [
                'count' => $criticalRequests->count(),
                'threshold' => $criticalThreshold,
            ]);

            return response()->json([
                'success' => true,
                'alerted_count' => $criticalRequests->count(),
                'threshold_days' => $criticalThreshold,
                'alert_type' => 'critical',
                'message' => "Critical aging document alerts sent (10+ days)",
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending critical aging alerts: ' . $e->getMessage());
            throw $e;
        }
    }


  public function notifyFocalPersonsOfAgingDocuments()
{
    try {
        Log::info('🚀 Starting focal person aging notifications');
        
        $criticalThreshold = 10; // Notify when request reaches 10+ days
        
        // Get current time
        $now = now('Asia/Manila');
        $cutoffDate = $now->copy()->subDays($criticalThreshold);

        // Define COMPLETED statuses (exclude these)
        $completedStatuses = [
            'cashier_released',
            '%_rejected',
        ];

        Log::info('Date parameters', [
            'current_date' => $now->toDateTimeString(),
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'threshold_days' => $criticalThreshold,
        ]);

        // Get ALL requests that are 10+ days old AND NOT completed
        $agingRequests = DvRequest::with(['focalPerson', 'program'])
            ->whereNotIn('status', $completedStatuses)
            ->where('created_at', '<=', $cutoffDate)
            ->get();

        Log::info('Found requests for notification', [
            'total_requests' => $agingRequests->count(),    
            'threshold_days' => $criticalThreshold,
        ]);

        $notifiedCount = 0;
        $skippedCount = 0;
        $alreadyNotified = 0;
        $errors = [];

        foreach ($agingRequests as $request) {
            try {
                if (!$request->focalPerson) {
                    Log::warning('Skipping - no focal person', [
                        'dv_request_id' => $request->id,
                        'reference_number' => $request->reference_number,
                        'status' => $request->status
                    ]);
                    $skippedCount++;
                    continue;
                }

                // FIX: Calculate days old correctly (absolute value, created is always in past)
                $createdAt = \Carbon\Carbon::parse($request->created_at, 'Asia/Manila');
                $daysOld = abs(floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay(), false))) ?: 0;
                // Send real-time alert via Pusher
                
                // Additional check: ensure it's actually in the past
                if ($createdAt->isFuture()) {
                    Log::warning('Skipping - document created_at is in future', [
                        'dv_request_id' => $request->id,
                        'created_at' => $createdAt->toDateTimeString(),
                    ]);
                    $skippedCount++;
                    continue;
                }
                
                Log::debug('Days calculation', [
                    'id' => $request->id,
                    'created_at' => $createdAt->toDateTimeString(),
                    'current_time' => $now->toDateTimeString(),
                    'days_old' => $daysOld,
                ]);

                // Check if it's 10+ days (should always be true due to query filter, but double-check)
                if ($daysOld < $criticalThreshold) {
                    Log::info("Skipping - not old enough", [
                        'dv_request_id' => $request->id,
                        'required_days' => $criticalThreshold,
                        'actual_days' => $daysOld
                    ]);
                    $skippedCount++;
                    continue;
                }

                // Check if we already sent AGING notification today for this request
                $alreadySent = DB::table('notifications')
                    ->where('focalperson_id', $request->focalPerson->id)
                    ->where('dv_request_id', $request->id)
                    ->where('status', 'document_aging')
                    ->exists();

                if ($alreadySent) {
                    Log::info("Already notified today", [
                        'dv_request_id' => $request->id,
                        'focal_person_id' => $request->focalPerson->id,
                    ]);
                    $alreadyNotified++;
                    continue;
                }

                $urgencyLevel = $this->getUrgencyLevel($daysOld);
                $focalPersonName = trim($request->focalPerson->fname . ' ' . ($request->focalPerson->mi ? $request->focalPerson->mi . ' ' : '') . $request->focalPerson->lname);
                $focalPersonEmail = $request->focalPerson->email;
                $controlNumber = $request->accounting_control_number 
                    ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT)
                    : 'Not assigned';

                // 1. Create database notification
                DB::table('notifications')->insert([
                    'focalperson_id' => $request->focalPerson->id,
                    'dv_request_id' => $request->id,
                    'status' => 'document_aging',
                    'action' => 'Delayed',
                    'remarks' => "Document has been pending for {$daysOld} days",
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 2. Send email notification
                if (!empty($focalPersonEmail) && filter_var($focalPersonEmail, FILTER_VALIDATE_EMAIL)) {
                    try {
                        $subject = $this->getUrgencyText($urgencyLevel) . " ALERT: DV Request Delayed - {$request->reference_number}";
                        
                        $statusFormatted = str_replace('_', ' ', ucwords($request->status, '_'));
                        $programName = $request->program->prcode ?? 'N/A';
                        $submittedDate = $createdAt->format('M d, Y');
                        $currentDate = $now->format('M d, Y');
                        $currentTime = $now->format('h:i A');
                        
                        $actionUrl = route('dv.verify', $request->reference_number);
                        
                        $htmlContent = view('emails.document_aging_alert', [
                            'subject' => $subject,
                            'focalPersonName' => $focalPersonName,
                            'referenceNumber' => $request->reference_number,
                            'controlNumber' => $controlNumber,
                            'program' => $programName,
                            'status' => $statusFormatted,
                            'daysOld' => $daysOld,
                            'submittedDate' => $submittedDate,
                            'currentDate' => $currentDate,
                            'currentTime' => $currentTime,
                            'urgencyLevel' => $urgencyLevel,
                            'urgencyText' => $this->getUrgencyText($urgencyLevel),
                            'urgencyEmoji' => $this->getUrgencyEmoji($urgencyLevel),
                            'actionUrl' => $actionUrl,
                            'organizationName' => 'DOLE XI',
                            'systemName' => 'ACCUDOCS',
                        ])->render();
                        
                        $emailSent = app(\App\Services\SendPulseServices::class)->sendAgingEmail(
                            $focalPersonEmail,
                            $focalPersonName,
                            $request,
                            $daysOld,
                            $urgencyLevel,
                            $subject,
                            $htmlContent
                        );
                        
                        if ($emailSent) {
                            Log::info("📧 Email sent to focal person", [
                                'dv_request_id' => $request->id,
                                'focal_person_email' => $focalPersonEmail,
                                'days_old' => $daysOld,
                            ]);
                        }
                        
                    } catch (\Exception $emailError) {
                        Log::error("Email sending error for request {$request->id}: " . $emailError->getMessage());
                    }
                }

                // 3. Send real-time Pusher notification
                $this->pusher->trigger("dv-requests-{$request->focalPerson->id}", 'document-critical-aging', [
                    'dv_request_id' => $request->id,
                    'reference_number' => $request->reference_number,
                    'control_number' => $controlNumber,
                    'days_old' => $daysOld,
                    'urgency' => $urgencyLevel,
                    'message' => "⚠️ Your DV request has been delayed for {$daysOld} days without processing",
                    'action_url' => route('FocalPerson.dvrequest.logs', $request),
                    'timestamp' => $now->format('M d, Y h:i A'),
                    'status' => $request->status,
                ]);

                $notifiedCount++;
                
                Log::info("✅ Successfully notified focal person", [
                    'dv_request_id' => $request->id,
                    'reference_number' => $request->reference_number,
                    'days_old' => $daysOld,
                    'urgency' => $urgencyLevel,
                ]);

            } catch (\Exception $e) {
                $errors[] = [
                    'request_id' => $request->id,
                    'error' => $e->getMessage()
                ];
                Log::error("Error notifying for request {$request->id}: " . $e->getMessage());
            }
        }

        Log::info('🎯 Focal person aging notifications completed', [
            'total_candidates' => $agingRequests->count(),
            'notified_count' => $notifiedCount,
            'already_notified_today' => $alreadyNotified,
            'skipped_count' => $skippedCount,
            'errors_count' => count($errors),
        ]);

        return response()->json([
            'success' => true,
            'notified_count' => $notifiedCount,
            'already_notified_today' => $alreadyNotified,
            'skipped_count' => $skippedCount,
            'errors' => $errors,
            'threshold_days' => $criticalThreshold,
            'message' => "Successfully notified {$notifiedCount} focal person(s) of {$criticalThreshold}+-day aging documents",
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error sending focal person aging notifications: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to send aging notifications',
            'error' => $e->getMessage()
        ], 500);
    }
}

private function sendAgingEmailNotification($request, $focalPersonName, $daysOld, $urgencyLevel, $controlNumber)
    {
        try {
            $urgencyText = $this->getUrgencyText($urgencyLevel);
            $subject = "{$urgencyText} ALERT: DV Request Delayed - {$request->reference_number}";
            
            $statusFormatted = str_replace('_', ' ', ucwords($request->status, '_'));
            $programName = $request->program->prcode ?? 'N/A';
            $submittedDate = $request->created_at->format('M d, Y');
            $currentDate = now()->format('M d, Y');
            $currentTime = now()->timezone('Asia/Manila')->format('h:i A');
            
            $actionUrl = route('FocalPerson.dvrequest.logs', $request);
            
            // Generate HTML content for the email
            $htmlContent = view('emails.document_aging_alert', [
                'subject' => $subject,
                'focalPersonName' => $focalPersonName,
                'referenceNumber' => $request->reference_number,
                'controlNumber' => $controlNumber,
                'program' => $programName,
                'status' => $statusFormatted,
                'daysOld' => $daysOld,
                'submittedDate' => $submittedDate,
                'currentDate' => $currentDate,
                'currentTime' => $currentTime,
                'urgencyLevel' => $urgencyLevel,
                'urgencyText' => $urgencyText,
                'urgencyEmoji' => $this->getUrgencyEmoji($urgencyLevel),
                'actionUrl' => $actionUrl,
                'organizationName' => 'DOLE XI',
                'systemName' => 'ACCUDOCS',
            ])->render();
            
            // Send using SendPulse service (it has built-in SMTP fallback)
            $result = $this->sendPulse->sendAgingEmail(
                $request->focalPerson->email,
                $focalPersonName,
                $request,
                $daysOld,
                $urgencyLevel,
                $subject,
                $htmlContent
            );
            
            if ($result) {
                Log::info("📧 Email sent to focal person via SendPulse", [
                    'dv_request_id' => $request->id,
                    'focal_person_email' => $request->focalPerson->email,
                    'focal_person_name' => $focalPersonName,
                    'provider' => 'SendPulse',
                ]);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("Failed to send email for request {$request->id}: " . $e->getMessage());
            throw $e;
        }
    }

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
     * Get urgency level based on whole number of days
     */
    protected function getUrgencyLevel($daysOld)
    {
        // Since $daysOld is already a whole number from diffInDays(),
        // we can use simple integer comparisons
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
}