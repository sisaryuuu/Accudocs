<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\DocumentAgingAlert;
use App\Services\PusherService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TUPADRequestAgingController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * Get aging TUPAD documents
     * Documents aged 3+ days in TUPAD-related statuses
     */
    public function getAgingDocuments()
    {
        try {
            Log::info('Fetching TUPAD aging documents');
            
            $agingThreshold = 3; // days
            $rejectionThreshold = 15; // days for auto-rejection
            $cutoffTime = now()->subDays($agingThreshold);
            $rejectionCutoffTime = now()->subDays($rejectionThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereHas('program', function($query) {
                    $query->where('prcode', 'TUPAD'); // ONLY TUPAD PROGRAM
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_received', 'Incoming-TUPAD_in_process'])
                ->where('created_at', '<=', $cutoffTime)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($request) use ($rejectionThreshold) {
                    // Use proper days calculation with whole numbers
                    $daysOld = now()->diffInDays(Carbon::parse($request->created_at), false);
                    $daysOld = floor((int) $daysOld);
                    
                    // Check if request is eligible for auto-rejection
                    $isEligibleForRejection = $daysOld >= $rejectionThreshold && 
                                            in_array($request->status, ['IMSD_verified', 'Incoming-TUPAD_received', 'Incoming-TUPAD_in_process']);
                    
                    // Get the latest alert for this document
                    $latestAlert = DocumentAgingAlert::where('dv_request_id', $request->id)
                        ->where('alert_date', today())
                        ->first();
                    
                    $controlNumber = $request->accounting_control_number 
                        ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT)
                        : '—';
                    
                    return [
                        'id' => $request->id,
                        'alert_id' => $latestAlert ? $latestAlert->id : null,
                        'control_number' => $controlNumber,
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'document_type' => 'TUPAD',
                        'status' => $request->status,
                        'days_old' => $daysOld, // Positive whole number
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'eligible_for_auto_rejection' => $isEligibleForRejection,
                        'rejection_threshold_days' => $rejectionThreshold,
                    ];
                });

            Log::info('TUPAD aging documents fetched successfully', ['count' => $agingRequests->count()]);

            return response()->json([
                'success' => true,
                'aging_count' => $agingRequests->count(),
                'documents' => $agingRequests,
                'notification_threshold_days' => $agingThreshold,
                'rejection_threshold_days' => $rejectionThreshold,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching TUPAD aging documents: ' . $e->getMessage(), [
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
        Log::info('Fetching TUPAD aging statistics');
        
        // Define the statuses that should be counted as "aging"
        $agingStatuses = ['IMSD_verified', 'Incoming-TUPAD_received'];
        
        $rejectionThreshold = 15; // days for auto-rejection
        $tenDaysAgo = now()->subDays(10);
        $sevenDaysAgo = now()->subDays(7);
        $threeDaysAgo = now()->subDays(3);
        $rejectionCutoff = now()->subDays($rejectionThreshold);
        
        $statistics = [
            'critical' => DvRequest::whereHas('program', function($q) {
                    $q->where('prcode', 'TUPAD');
                })
                ->whereIn('status', $agingStatuses)
                ->where('created_at', '<', $tenDaysAgo)
                ->count(),
            'high' => DvRequest::whereHas('program', function($q) {
                    $q->where('prcode', 'TUPAD');
                })
                ->whereIn('status', $agingStatuses)
                ->where('created_at', '>=', $tenDaysAgo)
                ->where('created_at', '<', $sevenDaysAgo)
                ->count(),
            'medium' => DvRequest::whereHas('program', function($q) {
                    $q->where('prcode', 'TUPAD');
                })
                ->whereIn('status', $agingStatuses)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->where('created_at', '<', $threeDaysAgo)
                ->count(),
            'total_aging' => DvRequest::whereHas('program', function($q) {
                    $q->where('prcode', 'TUPAD');
                })
                ->whereIn('status', $agingStatuses)
                ->where('created_at', '<', $threeDaysAgo)
                ->count(),
            'auto_rejection_candidates' => DvRequest::whereHas('program', function($q) {
                    $q->where('prcode', 'TUPAD');
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_received', 'TUPAD_in_process'])
                ->where('created_at', '<=', $rejectionCutoff)
                ->count(),
            'rejection_threshold_days' => $rejectionThreshold,
        ];

        Log::info('TUPAD aging statistics fetched successfully', $statistics);

        return response()->json([
            'success' => true,
            ...$statistics
        ]);

    } catch (\Exception $e) {
        Log::error('Error fetching TUPAD aging statistics: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch TUPAD aging statistics',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Notify focal persons of aging TUPAD documents with auto-rejection
     */
    public function notifyFocalPersonsOfAgingDocuments()
    {
        try {
            Log::info('🚀 Starting TUPAD focal person aging notifications with auto-rejection');
            
            $criticalThreshold = 5; // Notify when request reaches 5+ days
            $rejectionThreshold = 15; // Auto-reject after 15+ days
            $now = now('Asia/Manila');
            $cutoffDate = $now->copy()->subDays($criticalThreshold);
            $rejectionCutoffDate = $now->copy()->subDays($rejectionThreshold);

            // Define COMPLETED statuses (exclude these) - INCLUDE Incoming-TUPAD_rejected
            $completedStatuses = [
                'cashier_released',
                'Incoming-TUPAD_rejected',
                '%_rejected',
                'completed',
            ];

            Log::info('TUPAD notification parameters', [
                'notification_threshold_days' => $criticalThreshold,
                'rejection_threshold_days' => $rejectionThreshold,
            ]);

            // Get TUPAD requests that are 5+ days old AND NOT completed
            $agingRequests = DvRequest::with(['focalPerson', 'program'])
                ->whereHas('program', function($q) {
                    $q->where('prcode', 'TUPAD');
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_received', 'Incoming-TUPAD_in_process'])
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
                        Log::warning('TUPAD - Skipping, no focal person', [
                            'dv_request_id' => $request->id,
                            'reference_number' => $request->reference_number,
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    $createdAt = Carbon::parse($request->created_at, 'Asia/Manila');
                    $daysOld = abs(floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay(), false))) ?: 0;
                    
                    if ($createdAt->isFuture()) {
                        Log::warning('TUPAD - Skipping, document in future', [
                            'dv_request_id' => $request->id,
                        ]);
                        $skippedCount++;
                        continue;
                    }
                    
                    // Check for auto-rejection (15+ days)
                    if ($daysOld >= $rejectionThreshold) {
                        Log::info("TUPAD - Auto-rejecting request (15+ days old)", [
                            'dv_request_id' => $request->id,
                            'reference_number' => $request->reference_number,
                            'days_old' => $daysOld,
                        ]);
                        
                        try {
                            // Auto-reject the request
                            $this->autoRejectTUPADRequest($request, $daysOld);
                            $autoRejectedCount++;
                            continue; // Skip further processing
                        } catch (\Exception $rejectError) {
                            Log::error("TUPAD - Failed to auto-reject request {$request->id}: " . $rejectError->getMessage());
                            $errors[] = ['request_id' => $request->id, 'error' => 'Auto-rejection failed'];
                            // Continue with normal processing even if rejection failed
                        }
                    }
                    
                    // Skip if not old enough for notification (less than 5 days)
                    if ($daysOld < $criticalThreshold) {
                        Log::info("TUPAD - Skipping, not old enough", [
                            'dv_request_id' => $request->id,
                            'days_old' => $daysOld
                        ]);
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
                        Log::info("TUPAD - Already notified today", [
                            'dv_request_id' => $request->id,
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
                        'remarks' => "TUPAD document has been pending for {$daysOld} days",
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // 2. Send email notification
                    if (!empty($focalPersonEmail) && filter_var($focalPersonEmail, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $subject = $this->getUrgencyText($urgencyLevel) . " ALERT: TUPAD DV Request Delayed - {$request->reference_number}";
                            
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
                                'department' => 'TUPAD',
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
                            Log::error("TUPAD email error for request {$request->id}: " . $emailError->getMessage());
                        }
                    }

                    // 3. Send real-time Pusher notification
                    $this->pusher->trigger("dv-requests-{$request->focalPerson->id}", 'document-critical-aging-tupad', [
                        'dv_request_id' => $request->id,
                        'reference_number' => $request->reference_number,
                        'control_number' => $controlNumber,
                        'days_old' => $daysOld,
                        'urgency' => $urgencyLevel,
                        'department' => 'TUPAD',
                        'message' => "⚠️ Your TUPAD DV request has been delayed for {$daysOld} days",
                        'action_url' => route('FocalPerson.dvrequest.logs', $request),
                        'timestamp' => $now->format('M d, Y h:i A'),
                        'status' => $request->status,
                    ]);

                    $notifiedCount++;
                    
                    Log::info("✅ TUPAD - Successfully notified focal person", [
                        'dv_request_id' => $request->id,
                        'reference_number' => $request->reference_number,
                        'days_old' => $daysOld,
                    ]);

                } catch (\Exception $e) {
                    $errors[] = [
                        'request_id' => $request->id,
                        'error' => $e->getMessage()
                    ];
                    Log::error("TUPAD error notifying request {$request->id}: " . $e->getMessage());
                }
            }

            Log::info('🎯 TUPAD focal person aging notifications completed', [
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
                'message' => "Successfully notified {$notifiedCount} focal person(s) and auto-rejected {$autoRejectedCount} TUPAD DV request(s) pending 15+ days",
            ]);

        } catch (\Exception $e) {
            Log::error('❌ TUPAD error sending focal person aging notifications: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send TUPAD aging notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-reject a TUPAD request that has been pending for 15+ days
     * STATUS: Incoming-TUPAD_rejected
     */
    private function autoRejectTUPADRequest(DvRequest $request, int $daysOld)
    {
        DB::beginTransaction();
        
        try {
            // Get current status before update
            $previousStatus = $request->status;
            
            // Update request status to Incoming-TUPAD_rejected (specific status)
            $request->update([
                'status' => 'Incoming-TUPAD_rejected',
                'system_remarks' => "Auto-rejected by system after {$daysOld} days of inactivity in TUPAD department.",
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
                'status' => 'Incoming-TUPAD_rejected',
                'action' => 'Auto-Rejected',
                'remarks' => "TUPAD DV request was automatically rejected after {$daysOld} days of inactivity",
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Send email notification about auto-rejection
            $emailSent = $this->sendTUPADRejectionEmail($request, $daysOld);
            
            
            // Send Pusher notification with correct status
            $this->sendPusherNotification($request, $daysOld, 'TUPAD');
            
            DB::commit();
            
            Log::info("✅ TUPAD - Successfully auto-rejected request {$request->id}", [
                'reference_number' => $request->reference_number,
                'days_pending' => $daysOld,
                'new_status' => 'Incoming-TUPAD_rejected',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("TUPAD - Failed to auto-reject request {$request->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send Pusher notification for auto-rejection
     */
    private function sendPusherNotification(DvRequest $request, int $daysOld, string $department)
    {
        try {
            $this->pusher->trigger("dv-requests-{$request->focalPerson->id}", 'document-auto-rejected-tupad', [
                'dv_request_id' => $request->id,
                'reference_number' => $request->reference_number,
                'days_old' => $daysOld,
                'department' => $department . ' Department',
                'message' => "❌ Your TUPAD DV request has been automatically rejected after {$daysOld} days of inactivity",
                'timestamp' => now()->format('M d, Y h:i A'),
                'status' => 'Incoming-TUPAD_rejected', // Updated status
            ]);
        
            
        } catch (\Exception $e) {
            Log::error("TUPAD - Pusher notification failed: " . $e->getMessage());
            
        }
    }

    /**
     * Send TUPAD rejection email notification
     */
    private function sendTUPADRejectionEmail(DvRequest $request, int $daysOld): bool
    {
        try {
            if (!$request->focalPerson || empty($request->focalPerson->email)) {
                return false;
            }
            
            $focalPersonName = trim($request->focalPerson->fname . ' ' . 
                ($request->focalPerson->mi ? $request->focalPerson->mi . ' ' : '') . 
                $request->focalPerson->lname);
            
            $subject = "🚫 AUTO-REJECTED: TUPAD DV Request - {$request->reference_number}";
            
            $createdAt = Carbon::parse($request->created_at, 'Asia/Manila');
            $submittedDate = $createdAt->format('M d, Y');
            $rejectedDate = now('Asia/Manila')->format('M d, Y');
            $rejectedTime = now('Asia/Manila')->format('h:i A');
            
            $programName = $request->program->prcode ?? 'N/A';
            
          
            
            $htmlContent = view('emails.document_auto_rejected', [
                'subject' => $subject,
                'focalPersonName' => $focalPersonName,
                'referenceNumber' => $request->reference_number,
                'program' => $programName,
                'daysOld' => $daysOld,
                'submittedDate' => $submittedDate,
                'rejectedDate' => $rejectedDate,
                'rejectedTime' => $rejectedTime,
                'rejectionReason' => "Auto-rejected by system after {$daysOld} days of inactivity. Status: Incoming-TUPAD_rejected",
                'organizationName' => 'DOLE XI Regional Office',
                'systemName' => 'ACCUDOCS',
                'department' => 'TUPAD Department',
                'contactEmail' => 'dolexi.accudocs@example.com',
                'contactPhone' => '(082) 221-3141',
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
            Log::error("TUPAD - Auto-rejection email error for request {$request->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send aging alerts for TUPAD documents
     */
    public function sendAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with('focalPerson')
                ->whereHas('program', function($q) {
                    $q->where('prcode', 'TUPAD');
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_received', 'Incoming-TUPAD_in_process'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            if ($agingRequests->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No aging TUPAD documents to alert'
                ]);
            }

            $alertedCount = 0;
            foreach ($agingRequests as $request) {

               $daysOld = abs(floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay(), false))) ?: 0;

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
                    $this->pusher->trigger('dv-requests-incoming-tupad', 'document-aging-alert', [
                        'dv_request_id' => $request->id,
                        'control_number' => $request->accounting_control_number,
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'document_type' => 'TUPAD',
                        'status' => $request->status,
                        'days_old' => $daysOld, // Positive whole number
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
                'message' => 'TUPAD aging document alerts sent',
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending TUPAD aging alerts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send TUPAD aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method: Get urgency level based on days old
     */
    private function getUrgencyLevel($daysOld)
    {
        $daysOld = abs((int) $daysOld);
        
        if ($daysOld >= 10) return 'critical';
        if ($daysOld >= 7) return 'high';
        if ($daysOld >= 3) return 'medium';
        return 'low';
    }

    /**
     * Helper method: Get urgency text
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
     * Helper method: Get urgency emoji
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
}