<?php

namespace App\Http\Controllers\IMSD;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Services\PusherService;
use App\Models\DocumentAgingAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
            $rejectionThreshold = 15; // days for auto-rejection
            $cutoffTime = now()->subDays($agingThreshold);
            $rejectionCutoffTime = now()->subDays($rejectionThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('status', ['submitted', 'IMSD_received'])
                ->where('created_at', '<=', $cutoffTime)
                ->orderBy('created_at', 'asc')  
                ->get()
                ->map(function ($request) use ($rejectionThreshold) {
                    $daysOld = now()->diffInDays($request->created_at);
                    
                    // Check if request is eligible for auto-rejection
                    $isEligibleForRejection = $daysOld >= $rejectionThreshold && 
                                            in_array($request->status, ['submitted', 'IMSD_received', 'IMSD_in_process']);
                    
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
                        'eligible_for_auto_rejection' => $isEligibleForRejection,
                        'rejection_threshold_days' => $rejectionThreshold,
                    ];
                });

            Log::info('Aging documents fetched successfully', ['count' => $agingRequests->count()]);

            return response()->json([
                'success' => true,
                'aging_count' => $agingRequests->count(),
                'documents' => $agingRequests,
                'notification_threshold_days' => $agingThreshold,
                'rejection_threshold_days' => $rejectionThreshold,
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
            
            // Define completed statuses - INCLUDE IMSD_rejected
            $completedStatuses = [
                'cashier_released',
                'IMSD_rejected',
                '%_rejected',
            ];
            
            $rejectionThreshold = 15; // days for auto-rejection
            $tenDaysAgo = now()->subDays(10);
            $sevenDaysAgo = now()->subDays(7);
            $threeDaysAgo = now()->subDays(3);
            $rejectionCutoff = now()->subDays($rejectionThreshold);
            
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
                'auto_rejection_candidates' => DvRequest::whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process'])
                    ->where('created_at', '<=', $rejectionCutoff)
                    ->count(),
                'rejection_threshold_days' => $rejectionThreshold,
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
    
    public function notifyFocalPersonsOfAgingDocuments()
    {
        try {
            Log::info('🚀 Starting IMSD focal person aging notifications with auto-rejection');
            
            $criticalThreshold = 5; // Notify when request reaches 5+ days
            $rejectionThreshold = 15; // Auto-reject after 15+ days
            $now = now('Asia/Manila');
            $cutoffDate = $now->copy()->subDays($criticalThreshold);
            $rejectionCutoffDate = $now->copy()->subDays($rejectionThreshold);

            // Define COMPLETED statuses (exclude these) - INCLUDE IMSD_rejected
            $completedStatuses = [
                'cashier_released',
                'IMSD_rejected',
                '%_rejected',
            ];

            Log::info('IMSD notification parameters', [
                'notification_threshold_days' => $criticalThreshold,
                'rejection_threshold_days' => $rejectionThreshold,
            ]);

            // Get ALL IMSD requests that are 10+ days old AND NOT completed
            $agingRequests = DvRequest::with(['focalPerson', 'program'])
                ->whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process'])
                ->whereNotIn('status', $completedStatuses)
                ->where('created_at', '<=', $cutoffDate)
                ->get();

            $notifiedCount = 0;
            $skippedCount = 0;
            $alreadyNotified = 0;
            $autoRejectedCount = 0;
            $errors = [];

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
                    
                    // Check for auto-rejection (15+ days)
                    if ($daysOld >= $rejectionThreshold) {
                        Log::info("IMSD - Auto-rejecting request (15+ days old)", [
                            'dv_request_id' => $request->id,
                            'reference_number' => $request->reference_number,
                            'days_old' => $daysOld,
                        ]);
                        
                        try {
                            // Auto-reject the request
                            $this->autoRejectIMSDRequest($request, $daysOld);
                            $autoRejectedCount++;
                            continue; // Skip further processing
                        } catch (\Exception $rejectError) {
                            Log::error("IMSD - Failed to auto-reject request {$request->id}: " . $rejectError->getMessage());
                            $errors[] = ['request_id' => $request->id, 'error' => 'Auto-rejection failed'];
                            // Continue with normal processing even if rejection failed
                        }
                    }
                    
                    // Skip if not old enough for notification (less than 10 days)
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
                        'remarks' => "IMSD document has been pending for {$daysOld} days",
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // 2. Send email notification
                    if (!empty($focalPersonEmail) && filter_var($focalPersonEmail, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $subject = $this->getUrgencyText($urgencyLevel) . " ALERT: IMSD DV Request Delayed - {$request->reference_number}";
                            
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
                                'organizationName' => 'DOLE XI Regional Office',
                                'systemName' => 'ACCUDOCS',
                                'department' => 'IMSD Department',
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
                            
                        } catch (\Exception $emailError) {
                            Log::error("IMSD email error for request {$request->id}: " . $emailError->getMessage());
                        }
                    }

                    // 3. Send real-time Pusher notification
                    $this->pusher->trigger("dv-requests-{$request->focalPerson->id}", 'document-critical-aging-imsd', [
                        'dv_request_id' => $request->id,
                        'reference_number' => $request->reference_number,
                        'control_number' => $controlNumber,
                        'days_old' => $daysOld,
                        'urgency' => $urgencyLevel,
                        'department' => 'IMSD Department',
                        'message' => "⚠️ Your IMSD DV request has been delayed for {$daysOld} days",
                        'action_url' => route('dv.verify', $request->reference_number),
                        'timestamp' => $now->format('M d, Y h:i A'),
                        'status' => $request->status,
                    ]);

                    $notifiedCount++;

                } catch (\Exception $e) {
                    $errors[] = ['request_id' => $request->id, 'error' => $e->getMessage()];
                    Log::error("IMSD error notifying request {$request->id}: " . $e->getMessage());
                }
            }

            Log::info('🎯 IMSD focal person aging notifications completed', [
                'total_candidates' => $agingRequests->count(),
                'notified_count' => $notifiedCount,
                'already_notified_today' => $alreadyNotified,
                'skipped_count' => $skippedCount,
                'auto_rejected_count' => $autoRejectedCount,
                'errors_count' => count($errors),
            ]);

            return response()->json([
                'success' => true,
                'notified_count' => $notifiedCount,
                'already_notified_today' => $alreadyNotified,
                'skipped_count' => $skippedCount,
                'auto_rejected_count' => $autoRejectedCount,
                'errors' => $errors,
                'notification_threshold_days' => $criticalThreshold,
                'rejection_threshold_days' => $rejectionThreshold,
                'message' => "Successfully notified {$notifiedCount} focal person(s) and auto-rejected {$autoRejectedCount} DV request(s) pending 15+ days",
            ]);

        } catch (\Exception $e) {
            Log::error('❌ IMSD error sending focal person aging notifications: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send IMSD aging notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Send aging alerts for IMSD dashboard
     */
    public function sendAgingAlerts($criticalOnly = false)
    {
        try {
            Log::info('🚀 Starting IMSD aging alerts', ['criticalOnly' => $criticalOnly]);
            
            if ($criticalOnly) {
                return $this->sendCriticalIMSDAgingAlerts();
            } else {
                return $this->sendAllIMSDAgingAlerts();
            }

        } catch (\Exception $e) {
            Log::error('❌ Error in sendAgingAlerts for IMSD: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send IMSD aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send alerts for ALL aging IMSD documents (3+ days)
     */
    private function sendAllIMSDAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program'])
                ->whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            foreach ($agingRequests as $request) {
                $daysOld = now()->diffInDays($request->created_at);
                
                $this->pusher->trigger('dv-requests-imsd', 'document-aging-alert', [
                    'dv_request_id' => $request->id,
                    'control_number' => $request->accounting_control_number 
                        ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT)
                        : 'Not assigned',
                    'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                    'program' => $request->program->prcode ?? 'N/A',
                    'status' => $request->status,
                    'days_old' => $daysOld,
                    'urgency' => $this->getUrgencyLevel($daysOld),
                    'alert_type' => 'all',
                    'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            }

            $this->pusher->trigger('dv-requests-imsd', 'imsd-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_aging' => $agingRequests->count(),
                'alert_type' => 'all',
            ]);

            Log::info('IMSD all aging alerts sent', [
                'count' => $agingRequests->count(),
                'threshold' => $agingThreshold,
            ]);

            return response()->json([
                'success' => true,
                'alerted_count' => $agingRequests->count(),
                'threshold_days' => $agingThreshold,
                'alert_type' => 'all',
                'message' => "IMSD aging document alerts sent (3+ days)",
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error sending all IMSD aging alerts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send alerts for CRITICAL aging IMSD documents (10+ days)
     */
    private function sendCriticalIMSDAgingAlerts()
    {
        try {
            $criticalThreshold = 10;
            $cutoffTime = now()->subDays($criticalThreshold);

            $criticalRequests = DvRequest::with(['focalPerson', 'program'])
                ->whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            $alertedCount = 0;
            $autoRejectedCount = 0;

            foreach ($criticalRequests as $request) {
                $daysOld = now()->diffInDays($request->created_at);
                
                // Check for auto-rejection (15+ days)
                if ($daysOld >= 15) {
                    try {
                        $this->autoRejectIMSDRequest($request, $daysOld);
                        $autoRejectedCount++;
                    } catch (\Exception $rejectError) {
                        Log::error("IMSD - Failed to auto-reject request {$request->id} in critical alerts: " . $rejectError->getMessage());
                    }
                } else {
                    // Only send alerts for requests that are 10+ days old but less than 15 days
                    $this->pusher->trigger('dv-requests-imsd', 'document-aging-alert', [
                        'dv_request_id' => $request->id,
                        'control_number' => $request->accounting_control_number 
                            ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT)
                            : 'Not assigned',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'status' => $request->status,
                        'days_old' => $daysOld,
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'alert_type' => 'critical',
                        'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);
                    $alertedCount++;
                }
            }

            $this->pusher->trigger('dv-requests-imsd', 'imsd-critical-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_critical' => $criticalRequests->count(),
                'alerted_count' => $alertedCount,
                'auto_rejected_count' => $autoRejectedCount,
                'alert_type' => 'critical',
                'threshold_days' => $criticalThreshold,
            ]);

            Log::warning('IMSD critical aging alerts sent', [
                'count' => $alertedCount,
                'auto_rejected_count' => $autoRejectedCount,
                'threshold' => $criticalThreshold,
            ]);

            return response()->json([
                'success' => true,
                'alerted_count' => $alertedCount,
                'auto_rejected_count' => $autoRejectedCount,
                'threshold_days' => $criticalThreshold,
                'alert_type' => 'critical',
                'message' => "IMSD critical aging document alerts sent (10+ days). Auto-rejected: {$autoRejectedCount}",
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error sending critical IMSD aging alerts: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Auto-reject an IMSD request that has been pending for 15+ days
     * STATUS: IMSD_rejected
     */
    private function autoRejectIMSDRequest(DvRequest $request, int $daysOld)
    {
        DB::beginTransaction();
        
        try {
            // Get current status before update
            $previousStatus = $request->status;
            
            // Update request status to IMSD_rejected (specific status)
            $request->update([
                'status' => 'IMSD_rejected',
                'system_remarks' => "Auto-rejected by system after {$daysOld} days of inactivity.",
                'rejected_at' => now(),
            ]);
            
            // Create main rejection log - ONLY 'action', NO 'status'
            DB::table('dv_request_logs')->insert([
                'dv_request_id' => $request->id,
                'focalperson_id' => null,
                'accountingunit_id' => null,
                'external_actor' => 'System',
                'action' => 'Auto-Rejected',
                'remarks' => "This request was closed automatically after {$daysOld} days of inactivity. Original status was '{$previousStatus}'. Please refer to your email for further instructions.",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Create notification for the focal person
            DB::table('notifications')->insert([
                'focalperson_id' => $request->focalPerson->id,
                'dv_request_id' => $request->id,
                'status' => 'IMSD_rejected',
                'action' => 'Auto-Rejected',
                'remarks' => "IMSD DV request was automatically rejected after {$daysOld} days of inactivity",
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Send email notification about auto-rejection
            $emailSent = $this->sendIMSDRejectionEmail($request, $daysOld);
            
            // Send Pusher notification with correct status
            $this->sendPusherNotification($request, $daysOld, 'IMSD');
            
            DB::commit();
            
            Log::info("✅ IMSD - Successfully auto-rejected request {$request->id}", [
                'reference_number' => $request->reference_number,
                'days_pending' => $daysOld,
                'new_status' => 'IMSD_rejected',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("IMSD - Failed to auto-reject request {$request->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send Pusher notification for auto-rejection
     */
    private function sendPusherNotification(DvRequest $request, int $daysOld, string $department)
    {
        try {
            $this->pusher->trigger("dv-requests-{$request->focalPerson->id}", 'document-auto-rejected-imsd', [
                'dv_request_id' => $request->id,
                'reference_number' => $request->reference_number,
                'days_old' => $daysOld,
                'department' => $department . ' Department',
                'message' => "❌ Your IMSD DV request has been automatically rejected after {$daysOld} days of inactivity",
                'timestamp' => now()->format('M d, Y h:i A'),
                'status' => 'IMSD_rejected', // Updated status
            ]);
            
            // Also send to IMSD channel for dashboard updates
            $this->pusher->trigger('dv-requests-imsd', 'document-auto-rejected-imsd', [
                'dv_request_id' => $request->id,
                'reference_number' => $request->reference_number,
                'control_number' => $request->accounting_control_number 
                    ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT)
                    : 'Not assigned',
                'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                'program' => $request->program->prcode ?? 'N/A',
                'days_old' => $daysOld,
                'status' => 'IMSD_rejected',
                'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);
            
        } catch (\Exception $e) {
            Log::error("IMSD - Pusher notification failed: " . $e->getMessage());
            
            // Log Pusher failure - ONLY 'action', NO 'status'
            DB::table('dv_request_logs')->insert([
                'dv_request_id' => $request->id,
                'focalperson_id' => null,
                'accountingunit_id' => null,
                'external_actor' => 'System',
                'action' => 'Pusher-Notification-Failed',
                'remarks' => "Failed to send Pusher notification for auto-rejection: " . $e->getMessage(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Send IMSD rejection email notification
     */
    private function sendIMSDRejectionEmail(DvRequest $request, int $daysOld): bool
    {
        try {
            if (!$request->focalPerson || empty($request->focalPerson->email)) {
                return false;
            }
            
            $focalPersonName = trim($request->focalPerson->fname . ' ' . 
                ($request->focalPerson->mi ? $request->focalPerson->mi . ' ' : '') . 
                $request->focalPerson->lname);
            
            $subject = "🚫 AUTO-REJECTED: IMSD DV Request - {$request->reference_number}";
            
            $createdAt = Carbon::parse($request->created_at, 'Asia/Manila');
            $submittedDate = $createdAt->format('M d, Y');
            $rejectedDate = now('Asia/Manila')->format('M d, Y');
            $rejectedTime = now('Asia/Manila')->format('h:i A');
            $programName = $request->program->prcode ?? 'N/A';
            
            // Define variables for the email template
            $statusFormatted = str_replace('_', ' ', ucwords($request->status, '_'));
            $currentDate = $rejectedDate;
            $currentTime = $rejectedTime;
            $urgencyLevel = 'critical';
            $actionUrl = route('dv.verify', $request->reference_number);
            $controlNumber = $request->accounting_control_number 
                ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT)
                : 'Not assigned';
            
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
                'organizationName' => 'DOLE XI Regional Office',
                'systemName' => 'ACCUDOCS',
                'department' => 'IMSD Department',
            ])->render();
            
            return app(\App\Services\SendPulseServices::class)->sendAgingEmail(
                $request->focalPerson->email,
                $focalPersonName,
                $request,
                $daysOld,
                'critical',
                $subject,
                $htmlContent
            );
            
        } catch (\Exception $e) {
            Log::error("IMSD - Auto-rejection email error for request {$request->id}: " . $e->getMessage());
            return false;
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
}