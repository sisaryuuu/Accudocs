<?php

namespace App\Http\Controllers\BudgetOfficer;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\DocumentAgingAlert;
use App\Services\PusherService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
            $rejectionThreshold = 15; // days for auto-rejection
            // Use startOfDay() to compare calendar days, not exact hours
            $cutoffTime = now()->subDays($agingThreshold)->startOfDay();
            $rejectionCutoffTime = now()->subDays($rejectionThreshold)->startOfDay();

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
                ->map(function ($request) use ($rejectionThreshold) {
                    // FIX: Use floor() with startOfDay() to get whole calendar days
                    $daysOld = floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay()));
                    
                    // Check if request is eligible for auto-rejection
                    $isEligibleForRejection = $daysOld >= $rejectionThreshold && 
                                            in_array($request->status, [
                                                'Incoming-GIP_verified',
                                                'Incoming-TUPAD_verified', 
                                                'Incoming-TEV_verified',
                                                'ORS_received',
                                                'ORS_in_process',
                                            ]);
                    
                    // Get the latest alert for this DV request if it exists
                    $latestAlert = DocumentAgingAlert::where('dv_request_id', $request->id)
                        ->where('alert_date', today())
                        ->first();
                    
                    return [
                        'id' => $request->id, // This is the DV request ID
                        'alert_id' => $latestAlert ? $latestAlert->id : null,
                        'control_number' => $request->accounting_control_number ? 
                            'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
                        'ors_number' => $request->orsRequest->ors_number ?? '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'document_type' => 'ORS',
                        'current_status' => $request->status,
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'days_old' => $daysOld,
                        'days_in_status' => $daysOld, // Add this for JavaScript compatibility
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'eligible_for_auto_rejection' => $isEligibleForRejection,
                        'rejection_threshold_days' => $rejectionThreshold,
                    ];
                });

            Log::info('ORS aging documents fetched successfully using DV request IDs', ['count' => $agingRequests->count()]);

            return response()->json([
                'success' => true,
                'aging_count' => $agingRequests->count(),
                'documents' => $agingRequests,
                'notification_threshold_days' => $agingThreshold,
                'rejection_threshold_days' => $rejectionThreshold,
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
            
            // Define completed statuses for ORS - INCLUDE ORS_rejected
            $completedStatuses = [
                'cashier_released',
                'ORS_rejected',
                '%_rejected',
                'completed',
                'budget_officer_released',
                'accounting_released',
            ];
            
            $rejectionThreshold = 15; // days for auto-rejection
            // Use startOfDay() for consistent calculations
            $tenDaysAgo = now()->subDays(10)->startOfDay();
            $sevenDaysAgo = now()->subDays(7)->startOfDay();
            $threeDaysAgo = now()->subDays(3)->startOfDay();
            $rejectionCutoff = now()->subDays($rejectionThreshold)->startOfDay();
            
            $baseQuery = DvRequest::whereIn('status', [
                'Incoming-GIP_verified',
                'Incoming-TUPAD_verified',
                'Incoming-TEV_verified',
                'ORS_received',
                'ORS_in_process',
            ]);

            $statistics = [
                'critical' => (clone $baseQuery)
                    ->whereNotIn('status', $completedStatuses)
                    ->where('created_at', '<', $tenDaysAgo)
                    ->count(),
                'high' => (clone $baseQuery)
                    ->whereNotIn('status', $completedStatuses)
                    ->where('created_at', '>=', $tenDaysAgo)
                    ->where('created_at', '<', $sevenDaysAgo)
                    ->count(),
                'medium' => (clone $baseQuery)
                    ->whereNotIn('status', $completedStatuses)
                    ->where('created_at', '>=', $sevenDaysAgo)
                    ->where('created_at', '<', $threeDaysAgo)
                    ->count(),
                'total_aging' => (clone $baseQuery)
                    ->whereNotIn('status', $completedStatuses)
                    ->where('created_at', '<', $threeDaysAgo)
                    ->count(),
                'auto_rejection_candidates' => (clone $baseQuery)
                    ->where('created_at', '<=', $rejectionCutoff)
                    ->count(),
                'rejection_threshold_days' => $rejectionThreshold,
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
     * Notify focal persons when ORS documents are 5+ days old with auto-rejection after 15+ days
     */
    public function notifyFocalPersonsOfAgingDocuments()
    {
        try {
            Log::info('🚀 Starting ORS focal person aging notifications with auto-rejection');
            
            $criticalThreshold = 5; // Notify when request reaches 5+ days
            $rejectionThreshold = 15; // Auto-reject after 15+ days
            $now = now('Asia/Manila');
            // Use startOfDay() for consistent cutoff
            $cutoffDate = $now->copy()->subDays($criticalThreshold)->startOfDay();
            $rejectionCutoffDate = $now->copy()->subDays($rejectionThreshold)->startOfDay();

            // Define completed statuses for ORS - INCLUDE ORS_rejected
            $completedStatuses = [
                'cashier_released',
                'ORS_rejected',
                '%_rejected',
                'completed',
                'budget_officer_released',
                'accounting_released',
            ];

            Log::info('ORS notification parameters', [
                'notification_threshold_days' => $criticalThreshold,
                'rejection_threshold_days' => $rejectionThreshold,
            ]);

            // Get ORS requests that are 5+ days old AND NOT completed
            $agingRequests = DvRequest::with(['focalPerson', 'program', 'orsRequest'])
                ->whereIn('status', [
                    'Incoming-GIP_verified',
                    'Incoming-TUPAD_verified',
                    'Incoming-TEV_verified',
                    'ORS_received',
                    'ORS_in_process',
                ])
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
                        Log::warning('ORS - Skipping, no focal person', [
                            'dv_request_id' => $request->id,
                            'reference_number' => $request->reference_number,
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    $createdAt = Carbon::parse($request->created_at, 'Asia/Manila');
                    // This calculation is already correct
                    $daysOld = abs(floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay(), false))) ?: 0;
                    
                    if ($createdAt->isFuture()) {
                        Log::warning('ORS - Skipping, document in future', [
                            'dv_request_id' => $request->id,
                        ]);
                        $skippedCount++;
                        continue;
                    }
                    
                    // Check for auto-rejection (15+ days)
                    if ($daysOld >= $rejectionThreshold) {
                        Log::info("ORS - Auto-rejecting request (15+ days old)", [
                            'dv_request_id' => $request->id,
                            'reference_number' => $request->reference_number,
                            'days_old' => $daysOld,
                        ]);
                        
                        try {
                            // Auto-reject the request
                            $this->autoRejectORSRequest($request, $daysOld);
                            $autoRejectedCount++;
                            continue; // Skip further processing
                        } catch (\Exception $rejectError) {
                            Log::error("ORS - Failed to auto-reject request {$request->id}: " . $rejectError->getMessage());
                            $errors[] = ['request_id' => $request->id, 'error' => 'Auto-rejection failed'];
                            // Continue with normal processing even if rejection failed
                        }
                    }
                    
                    // Skip if not old enough for notification (less than 5 days)
                    if ($daysOld < $criticalThreshold) {
                        Log::info("ORS - Skipping, not old enough", [
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
                        Log::info("ORS - Already notified today", [
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
                    $orsNumber = $request->orsRequest->ors_number ?? 'Not assigned';

                    // 1. Create database notification
                    DB::table('notifications')->insert([
                        'focalperson_id' => $request->focalPerson->id,
                        'dv_request_id' => $request->id,
                        'status' => 'document_aging',
                        'action' => 'Delayed',
                        'remarks' => "ORS document has been pending for {$daysOld} days",
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // 2. Send email notification
                    if (!empty($focalPersonEmail) && filter_var($focalPersonEmail, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $subject = $this->getUrgencyText($urgencyLevel) . " ALERT: ORS DV Request Delayed - {$request->reference_number}";
                            
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
                                'orsNumber' => $orsNumber,
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
                            Log::error("ORS email error for request {$request->id}: " . $emailError->getMessage());
                        }
                    }

                    // 3. Send real-time Pusher notification
                    $this->pusher->trigger("dv-requests-{$request->focalPerson->id}", 'document-critical-aging-ors', [
                        'dv_request_id' => $request->id,
                        'reference_number' => $request->reference_number,
                        'control_number' => $controlNumber,
                        'ors_number' => $orsNumber,
                        'days_old' => $daysOld,
                        'urgency' => $urgencyLevel,
                        'department' => 'Budget Officer - ORS',
                        'message' => "⚠️ Your ORS DV request has been delayed for {$daysOld} days",
                        'action_url' => route('dv.verify', $request->reference_number),
                        'timestamp' => $now->format('M d, Y h:i A'),
                        'status' => $request->status,
                    ]);

                    $notifiedCount++;
                    
                    Log::info("✅ ORS - Successfully notified focal person", [
                        'dv_request_id' => $request->id,
                        'reference_number' => $request->reference_number,
                        'days_old' => $daysOld,
                    ]);

                } catch (\Exception $e) {
                    $errors[] = [
                        'request_id' => $request->id,
                        'error' => $e->getMessage()
                    ];
                    Log::error("ORS error notifying request {$request->id}: " . $e->getMessage());
                }
            }

            Log::info('🎯 ORS focal person aging notifications completed', [
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
                'message' => "Successfully notified {$notifiedCount} focal person(s) and auto-rejected {$autoRejectedCount} ORS DV request(s) pending 15+ days",
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ORS error sending focal person aging notifications: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send ORS aging notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-reject an ORS request that has been pending for 15+ days
     * STATUS: ORS_rejected
     */
    private function autoRejectORSRequest(DvRequest $request, int $daysOld)
    {
        DB::beginTransaction();
        
        try {
            // Get current status before update
            $previousStatus = $request->status;
            
            // Update request status to ORS_rejected (specific status)
            $request->update([
                'status' => 'ORS_rejected',
                'system_remarks' => "Auto-rejected by system after {$daysOld} days of inactivity in Budget Officer (ORS) department.",
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
                'status' => 'ORS_rejected',
                'action' => 'Auto-Rejected',
                'remarks' => "ORS DV request was automatically rejected after {$daysOld} days of inactivity",
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Send email notification about auto-rejection
            $emailSent = $this->sendORSRejectionEmail($request, $daysOld);
            
          
            // Send Pusher notification with correct status
            $this->sendPusherNotification($request, $daysOld, 'Budget Officer - ORS');
            
            DB::commit();
            
            Log::info("✅ ORS - Successfully auto-rejected request {$request->id}", [
                'reference_number' => $request->reference_number,
                'days_pending' => $daysOld,
                'new_status' => 'ORS_rejected',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ORS - Failed to auto-reject request {$request->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send Pusher notification for auto-rejection
     */
    private function sendPusherNotification(DvRequest $request, int $daysOld, string $department)
    {
        try {
            $this->pusher->trigger("dv-requests-{$request->focalPerson->id}", 'document-auto-rejected-ors', [
                'dv_request_id' => $request->id,
                'reference_number' => $request->reference_number,
                'days_old' => $daysOld,
                'department' => $department,
                'message' => "❌ Your ORS DV request has been automatically rejected after {$daysOld} days of inactivity",
                'timestamp' => now()->format('M d, Y h:i A'),
                'status' => 'ORS_rejected', // Updated status
            ]);
            

            
        } catch (\Exception $e) {
            Log::error("ORS - Pusher notification failed: " . $e->getMessage());
            
        }
    }

    /**
     * Send ORS rejection email notification
     */
    private function sendORSRejectionEmail(DvRequest $request, int $daysOld): bool
    {
        try {
            if (!$request->focalPerson || empty($request->focalPerson->email)) {
                return false;
            }
            
            $focalPersonName = trim($request->focalPerson->fname . ' ' . 
                ($request->focalPerson->mi ? $request->focalPerson->mi . ' ' : '') . 
                $request->focalPerson->lname);
            
            $subject = "🚫 AUTO-REJECTED: ORS DV Request - {$request->reference_number}";
            
            $createdAt = Carbon::parse($request->created_at, 'Asia/Manila');
            $submittedDate = $createdAt->format('M d, Y');
            $rejectedDate = now('Asia/Manila')->format('M d, Y');
            $rejectedTime = now('Asia/Manila')->format('h:i A');
            
            $programName = $request->program->prcode ?? 'N/A';
            $orsNumber = $request->orsRequest->ors_number ?? 'Not assigned';
    
            
            $htmlContent = view('emails.document_auto_rejected', [
                'subject' => $subject,
                'focalPersonName' => $focalPersonName,
                'referenceNumber' => $request->reference_number,
                'controlNumber' => $request->accounting_control_number ? 
                    'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : 'Not assigned',
                'orsNumber' => $orsNumber,
                'program' => $programName,
                'daysOld' => $daysOld,
                'submittedDate' => $submittedDate,
                'rejectedDate' => $rejectedDate,
                'rejectedTime' => $rejectedTime,
                'rejectionReason' => "Auto-rejected by system after {$daysOld} days of inactivity in Budget Officer (ORS) department. Status: ORS_rejected",
                'organizationName' => 'DOLE XI Regional Office',
                'systemName' => 'ACCUDOCS',
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
            Log::error("ORS - Auto-rejection email error for request {$request->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send aging alerts for ORS documents
     */
    public function sendAgingAlerts($criticalOnly = false)
    {
        try {
            Log::info('Starting ORS aging alerts', ['criticalOnly' => $criticalOnly]);
            
            if ($criticalOnly) {
                return $this->sendCriticalORSAgingAlerts();
            } else {
                return $this->sendAllORSAgingAlerts();
            }

        } catch (\Exception $e) {
            Log::error('Error in sendAgingAlerts for ORS: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send ORS aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send alerts for ALL aging ORS documents (3+ days)
     */
    private function sendAllORSAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            // Use startOfDay() for consistent day calculation
            $cutoffTime = now()->subDays($agingThreshold)->startOfDay();

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'orsRequest'])
                ->whereIn('status', [
                    'Incoming-GIP_verified',
                    'Incoming-TUPAD_verified',
                    'Incoming-TEV_verified',
                    'ORS_received',
                    'ORS_in_process',
                ])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            $alertedCount = 0;
            foreach ($agingRequests as $request) {
                // FIX: Use floor() with startOfDay()
                $daysOld = floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay()));

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
                        'control_number' => $request->accounting_control_number ? 
                            'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
                        'ors_number' => $request->orsRequest->ors_number ?? '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'current_status' => $request->status,
                        'days_old' => $daysOld,
                        'days_in_status' => $daysOld, // Add for JavaScript
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'alert_type' => 'all',
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);

                    $alertedCount++;
                }
            }

            $this->pusher->trigger('dv-requests-budget-officer', 'ors-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_aging' => $agingRequests->count(),
                'alerted_count' => $alertedCount,
                'alert_type' => 'all',
            ]);

            Log::info('ORS all aging alerts sent', [
                'count' => $alertedCount,
                'threshold' => $agingThreshold,
            ]);

            return response()->json([
                'success' => true,
                'alerted_count' => $alertedCount,
                'total_aging' => $agingRequests->count(),
                'threshold_days' => $agingThreshold,
                'alert_type' => 'all',
                'message' => "ORS aging document alerts sent (3+ days)",
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending all ORS aging alerts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send alerts for CRITICAL aging ORS documents (10+ days)
     */
    private function sendCriticalORSAgingAlerts()
    {
        try {
            $criticalThreshold = 10;
            // Use startOfDay() for consistent day calculation
            $cutoffTime = now()->subDays($criticalThreshold)->startOfDay();

            $criticalRequests = DvRequest::with(['focalPerson', 'program', 'orsRequest'])
                ->whereIn('status', [
                    'Incoming-GIP_verified',
                    'Incoming-TUPAD_verified',
                    'Incoming-TEV_verified',
                    'ORS_received',
                    'ORS_in_process',
                ])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            $alertedCount = 0;
            foreach ($criticalRequests as $request) {
                // FIX: Use floor() with startOfDay()
                $daysOld = floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay()));

                // Send real-time alert via Pusher for critical
                $this->pusher->trigger('dv-requests-budget-officer', 'ors-critical-aging-alert', [
                    'dv_request_id' => $request->id,
                    'control_number' => $request->accounting_control_number ? 
                        'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
                    'ors_number' => $request->orsRequest->ors_number ?? '—',
                    'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                    'program' => $request->program->prcode ?? 'N/A',
                    'current_status' => $request->status,
                    'days_old' => $daysOld,
                    'days_in_status' => $daysOld, // Add for JavaScript
                    'urgency' => $this->getUrgencyLevel($daysOld),
                    'alert_type' => 'critical',
                    'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                    'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);

                $alertedCount++;
            }

            $this->pusher->trigger('dv-requests-budget-officer', 'ors-critical-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_critical' => $criticalRequests->count(),
                'alerted_count' => $alertedCount,
                'alert_type' => 'critical',
                'threshold_days' => $criticalThreshold,
            ]);

            Log::warning('ORS critical aging alerts sent', [
                'count' => $alertedCount,
                'threshold' => $criticalThreshold,
            ]);

            return response()->json([
                'success' => true,
                'alerted_count' => $alertedCount,
                'total_critical' => $criticalRequests->count(),
                'threshold_days' => $criticalThreshold,
                'alert_type' => 'critical',
                'message' => "ORS critical aging document alerts sent (10+ days)",
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending critical ORS aging alerts: ' . $e->getMessage());
            throw $e;
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
}