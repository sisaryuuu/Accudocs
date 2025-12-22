<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\DocumentAgingAlert;
use App\Services\PusherService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Traits\EmailBatchingTrait;

class OutgoingRequestAgingController extends Controller
{
    use EmailBatchingTrait;
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * Get aging documents for Outgoing dashboard
     */
    public function getAgingDocuments()
    {
        try {
            Log::info('Fetching Outgoing aging documents');
            
            $agingThreshold = 3; // days
            $rejectionThreshold = 15; // days for auto-rejection
            // Use startOfDay() to compare calendar days, not exact hours
            $cutoffTime = now()->subDays($agingThreshold)->startOfDay();
            $rejectionCutoffTime = now()->subDays($rejectionThreshold)->startOfDay();

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('status', ['ORS_obligated', 'Outgoing_received'])
                ->where('created_at', '<=', $cutoffTime)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($request) use ($rejectionThreshold) {
                    // Calculate whole calendar days
                    $daysOld = floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay()));
                    
                    // Check if request is eligible for auto-rejection
                    $isEligibleForRejection = $daysOld >= $rejectionThreshold && 
                                            in_array($request->status, ['ORS_obligated', 'Outgoing_received']);
                    
                    // Get the latest alert for this DV request if it exists
                    $latestAlert = DocumentAgingAlert::where('dv_request_id', $request->id)
                        ->where('alert_date', today())
                        ->first();
                    
                    return [
                        'id' => $request->id,
                        'alert_id' => $latestAlert ? $latestAlert->id : null,
                        'control_number' => $request->accounting_control_number ? 
                            'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'document_type' => 'Outgoing',
                        'current_status' => $request->status,
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'days_old' => $daysOld,
                        'days_in_status' => $daysOld, // For JavaScript compatibility
                        'urgency' => $this->getUrgencyLevel($daysOld),
                        'eligible_for_auto_rejection' => $isEligibleForRejection,
                        'rejection_threshold_days' => $rejectionThreshold,
                    ];
                });

            Log::info('Outgoing aging documents fetched successfully', ['count' => $agingRequests->count()]);

            return response()->json([
                'success' => true,
                'aging_count' => $agingRequests->count(),
                'documents' => $agingRequests,
                'notification_threshold_days' => $agingThreshold,
                'rejection_threshold_days' => $rejectionThreshold,
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

    /**
     * Get Outgoing aging statistics
     */
    public function getAgingStatistics()
    {
        try {
            Log::info('Fetching Outgoing aging statistics');
            
            // Define completed statuses for Outgoing - INCLUDE Outgoing_rejected
            $completedStatuses = [
                'cashier_released',
                'Outgoing_rejected',
                '%_rejected',
                'completed',
                'budget_officer_released',
                'accounting_released',
                'Outgoing_finalized',
            ];
            
            $rejectionThreshold = 15; // days for auto-rejection
            // Use startOfDay() for consistent calculations
            $tenDaysAgo = now()->subDays(10)->startOfDay();
            $sevenDaysAgo = now()->subDays(7)->startOfDay();
            $threeDaysAgo = now()->subDays(3)->startOfDay();
            $rejectionCutoff = now()->subDays($rejectionThreshold)->startOfDay();
            
            $baseQuery = DvRequest::whereIn('status', ['ORS_obligated', 'Outgoing_received']);

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
                    ->whereNotIn('status', $completedStatuses)
                    ->where('created_at', '<=', $rejectionCutoff)
                    ->count(),
                'rejection_threshold_days' => $rejectionThreshold,
            ];

            Log::info('Outgoing aging statistics fetched successfully', $statistics);

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
     * Send aging alerts for Outgoing documents
     */
    public function sendAgingAlerts($criticalOnly = false)
    {
        try {
            Log::info('Starting Outgoing aging alerts', ['criticalOnly' => $criticalOnly]);
            
            if ($criticalOnly) {
                return $this->sendCriticalOutgoingAgingAlerts();
            } else {
                return $this->sendAllOutgoingAgingAlerts();
            }

        } catch (\Exception $e) {
            Log::error('Error in sendAgingAlerts for Outgoing: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send Outgoing aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Notify focal persons when Outgoing documents are 5+ days old with auto-rejection after 15+ days
     */
    public function notifyFocalPersonsOfAgingDocuments()
    {
        try {
            Log::info('🚀 Starting Outgoing focal person aging notifications with batching and auto-rejection');
            
            $criticalThreshold = 5; // Notify when request reaches 5+ days
            $rejectionThreshold = 15; // Auto-reject after 15+ days
            $now = now('Asia/Manila');
            $cutoffDate = $now->copy()->subDays($criticalThreshold);
            $rejectionCutoffDate = $now->copy()->subDays($rejectionThreshold);

            // Define completed statuses for Outgoing - INCLUDE Outgoing_rejected
            $completedStatuses = [
                'cashier_released',
                'Outgoing_rejected',
                '%_rejected',
                'completed',
                'budget_officer_released',
                'accounting_released',
                'Outgoing_finalized',
            ];

            Log::info('Outgoing notification parameters with batching and auto-rejection', [
                'notification_threshold_days' => $criticalThreshold,
                'rejection_threshold_days' => $rejectionThreshold,
                'batching_enabled' => true
            ]);

            // Get Outgoing requests that are 5+ days old AND NOT completed
            $agingRequests = DvRequest::with(['focalPerson', 'program'])
                ->whereIn('status', [
                    'ORS_obligated',
                    'Outgoing_received',
                ])
                ->whereNotIn('status', $completedStatuses)
                ->where('created_at', '<=', $cutoffDate)
                ->get();

            Log::info('Outgoing requests for notification', [
                'total_requests' => $agingRequests->count(),
                'threshold_days' => $criticalThreshold,
            ]);

            $notifiedCount = 0;
            $skippedCount = 0;
            $alreadyNotified = 0;
            $autoRejectedCount = 0;
            $errors = [];

            // Filter out already notified today and calculate days
            $eligibleRequests = [];
            foreach ($agingRequests as $request) {
                try {
                    if (!$request->focalPerson) {
                        Log::warning('Outgoing - Skipping, no focal person', [
                            'dv_request_id' => $request->id,
                            'reference_number' => $request->reference_number,
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    $createdAt = Carbon::parse($request->created_at, 'Asia/Manila');
                    $daysOld = abs(floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay(), false))) ?: 0;
                    
                    if ($createdAt->isFuture()) {
                        Log::warning('Outgoing - Skipping, document in future', [
                            'dv_request_id' => $request->id,
                            'created_at' => $createdAt->format('Y-m-d H:i:s'),
                            'now' => $now->format('Y-m-d H:i:s'),
                        ]);
                        $skippedCount++;
                        continue;
                    }
                    
                    // Check for auto-rejection (15+ days)
                    if ($daysOld >= $rejectionThreshold) {
                        Log::info("Outgoing - Auto-rejecting request (15+ days old)", [
                            'dv_request_id' => $request->id,
                            'reference_number' => $request->reference_number,
                            'days_old' => $daysOld,
                        ]);
                        
                        try {
                            // Auto-reject the request
                            $this->autoRejectOutgoingRequest($request, $daysOld);
                            $autoRejectedCount++;
                            continue; // Skip further processing
                        } catch (\Exception $rejectError) {
                            Log::error("Outgoing - Failed to auto-reject request {$request->id}: " . $rejectError->getMessage());
                            $errors[] = ['request_id' => $request->id, 'error' => 'Auto-rejection failed'];
                            // Continue with normal processing even if rejection failed
                        }
                    }
                    
                    // Skip if not old enough for notification (less than 5 days)
                    if ($daysOld < $criticalThreshold) {
                        Log::info("Outgoing - Skipping, not old enough", [
                            'dv_request_id' => $request->id,
                            'days_old' => $daysOld,
                            'threshold' => $criticalThreshold,
                            'created_at' => $createdAt->format('Y-m-d'),
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
                        Log::info("Outgoing - Already notified today", [
                            'dv_request_id' => $request->id,
                        ]);
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
                    Log::error("Outgoing error processing request {$request->id}: " . $e->getMessage());
                }
            }

            Log::info('Outgoing - Eligible requests for email batching', [
                'eligible_count' => count($eligibleRequests),
                'skipped_count' => $skippedCount,
                'already_notified' => $alreadyNotified,
                'auto_rejected' => $autoRejectedCount,
            ]);

            // Send emails using batching
            $emailResults = $this->sendAgingEmailsWithBatching(
                array_column($eligibleRequests, 'request'),
                function($request, $email, $name) use ($eligibleRequests) {
                    $requestData = collect($eligibleRequests)->firstWhere('request.id', $request->id);
                    $daysOld = $requestData['days_old'] ?? 0;
                    $urgencyLevel = $requestData['urgency_level'] ?? 'critical';
                    
                    return $this->sendIndividualAgingEmail($request, $email, $name, $daysOld, $urgencyLevel, 'Outgoing');
                }
            );

            // Create database notifications for successful emails and send Pusher notifications
            foreach ($eligibleRequests as $eligible) {
                $request = $eligible['request'];
                $daysOld = $eligible['days_old'];
                $urgencyLevel = $eligible['urgency_level'];
                
                // Create database notification
                DB::table('notifications')->insert([
                    'focalperson_id' => $request->focalPerson->id,
                    'dv_request_id' => $request->id,
                    'status' => 'document_aging',
                    'action' => 'Delayed',
                    'remarks' => "Outgoing document has been pending for {$daysOld} days",
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
             
                
                // Send real-time Pusher notification
                $controlNumber = $request->accounting_control_number 
                    ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT)
                    : 'Not assigned';
                    
                $this->pusher->trigger("dv-requests-{$request->focalPerson->id}", 'document-critical-aging-outgoing', [
                    'dv_request_id' => $request->id,
                    'reference_number' => $request->reference_number,
                    'control_number' => $controlNumber,
                    'days_old' => $daysOld,
                    'urgency' => $urgencyLevel,
                    'department' => 'Outgoing Department',
                    'message' => "⚠️ Your Outgoing DV request has been delayed for {$daysOld} days",
                    'action_url' => route('dv.verify', $request->reference_number),
                    'timestamp' => $now->format('M d, Y h:i A'),
                    'status' => $request->status,
                ]);
                
            }

            $notifiedCount = $emailResults['success'];

            Log::info('🎯 Outgoing focal person aging notifications completed with batching and auto-rejection', [
                'total_candidates' => $agingRequests->count(),
                'notified_count' => $notifiedCount,
                'already_notified_today' => $alreadyNotified,
                'skipped_count' => $skippedCount,
                'auto_rejected_count' => $autoRejectedCount,
                'email_results' => $emailResults,
                'errors_count' => count($errors),
            ]);

            return response()->json([
                'success' => true,
                'notified_count' => $notifiedCount,
                'already_notified_today' => $alreadyNotified,
                'skipped_count' => $skippedCount,
                'auto_rejected_count' => $autoRejectedCount,
                'email_results' => $emailResults,
                'errors' => $errors,
                'notification_threshold_days' => $criticalThreshold,
                'rejection_threshold_days' => $rejectionThreshold,
                'batching_used' => true,
                'message' => "Successfully notified {$notifiedCount} focal person(s) and auto-rejected {$autoRejectedCount} Outgoing DV request(s) pending 15+ days (using batching)",
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Outgoing error sending focal person aging notifications: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send Outgoing aging notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-reject an Outgoing request that has been pending for 15+ days
     * STATUS: Outgoing_rejected
     */
    private function autoRejectOutgoingRequest(DvRequest $request, int $daysOld)
    {
        DB::beginTransaction();
        
        try {
            // Get current status before update
            $previousStatus = $request->status;
            
            // Update request status to Outgoing_rejected (specific status)
            $request->update([
                'status' => 'Outgoing_rejected',
                'system_remarks' => "Auto-rejected by system after {$daysOld} days of inactivity in Outgoing department.",
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
                'status' => 'Outgoing_rejected',
                'action' => 'Auto-Rejected',
                'remarks' => "Outgoing DV request was automatically rejected after {$daysOld} days of inactivity",
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Send email notification about auto-rejection
            $emailSent = $this->sendOutgoingRejectionEmail($request, $daysOld);
                     
            // Send Pusher notification with correct status
            $this->sendPusherNotification($request, $daysOld, 'Outgoing');
            
            DB::commit();
            
            Log::info("✅ Outgoing - Successfully auto-rejected request {$request->id}", [
                'reference_number' => $request->reference_number,
                'days_pending' => $daysOld,
                'new_status' => 'Outgoing_rejected',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Outgoing - Failed to auto-reject request {$request->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send Pusher notification for auto-rejection
     */
    private function sendPusherNotification(DvRequest $request, int $daysOld, string $department)
    {
        try {
            $controlNumber = $request->accounting_control_number 
                ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT)
                : 'Not assigned';
            
            $this->pusher->trigger("dv-requests-{$request->focalPerson->id}", 'document-auto-rejected-outgoing', [
                'dv_request_id' => $request->id,
                'reference_number' => $request->reference_number,
                'control_number' => $controlNumber,
                'days_old' => $daysOld,
                'department' => $department . ' Department',
                'message' => "❌ Your Outgoing DV request has been automatically rejected after {$daysOld} days of inactivity",
                'timestamp' => now()->format('M d, Y h:i A'),
                'status' => 'Outgoing_rejected', // Updated status
            ]);
            
         
        } catch (\Exception $e) {
            Log::error("Outgoing - Pusher notification failed: " . $e->getMessage());
            
      
        }
    }

    /**
     * Send Outgoing rejection email notification
     */
    private function sendOutgoingRejectionEmail(DvRequest $request, int $daysOld): bool
    {
        try {
            if (!$request->focalPerson || empty($request->focalPerson->email)) {
                return false;
            }
            
            $focalPersonName = trim($request->focalPerson->fname . ' ' . 
                ($request->focalPerson->mi ? $request->focalPerson->mi . ' ' : '') . 
                $request->focalPerson->lname);
            
            $subject = "🚫 AUTO-REJECTED: Outgoing DV Request - {$request->reference_number}";
            
            $createdAt = Carbon::parse($request->created_at, 'Asia/Manila');
            $submittedDate = $createdAt->format('M d, Y');
            $rejectedDate = now('Asia/Manila')->format('M d, Y');
            $rejectedTime = now('Asia/Manila')->format('h:i A');
            
            $programName = $request->program->prcode ?? 'N/A';
            
            // Generate audit reference
      
            
            $htmlContent = view('emails.document_auto_rejected', [
                'subject' => $subject,
                'focalPersonName' => $focalPersonName,
                'referenceNumber' => $request->reference_number,
                'controlNumber' => $request->accounting_control_number ? 
                    'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : 'Not assigned',
                'program' => $programName,
                'daysOld' => $daysOld,
                'submittedDate' => $submittedDate,
                'rejectedDate' => $rejectedDate,
                'rejectedTime' => $rejectedTime,
                'rejectionReason' => "Auto-rejected by system after {$daysOld} days of inactivity in Outgoing department. Status: Outgoing_rejected",
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
            Log::error("Outgoing - Auto-rejection email error for request {$request->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send alerts for ALL aging Outgoing documents (3+ days)
     */
    private function sendAllOutgoingAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            // Use startOfDay() for consistent day calculation
            $cutoffTime = now()->subDays($agingThreshold)->startOfDay();

            $agingRequests = DvRequest::with(['focalPerson', 'program'])
                ->whereIn('status', ['ORS_obligated', 'Outgoing_received'])
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
                    $this->pusher->trigger('dv-requests-outgoing', 'outgoing-aging-alert', [
                        'dv_request_id' => $request->id,
                        'control_number' => $request->accounting_control_number ? 
                            'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
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

            $this->pusher->trigger('dv-requests-outgoing', 'outgoing-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_aging' => $agingRequests->count(),
                'alerted_count' => $alertedCount,
                'alert_type' => 'all',
            ]);

            Log::info('Outgoing all aging alerts sent', [
                'count' => $alertedCount,
                'threshold' => $agingThreshold,
            ]);

            return response()->json([
                'success' => true,
                'alerted_count' => $alertedCount,
                'total_aging' => $agingRequests->count(),
                'threshold_days' => $agingThreshold,
                'alert_type' => 'all',
                'message' => "Outgoing aging document alerts sent (3+ days)",
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending all Outgoing aging alerts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send alerts for CRITICAL aging Outgoing documents (10+ days)
     */
    private function sendCriticalOutgoingAgingAlerts()
    {
        try {
            $criticalThreshold = 10;
            // Use startOfDay() for consistent day calculation
            $cutoffTime = now()->subDays($criticalThreshold)->startOfDay();

            $criticalRequests = DvRequest::with(['focalPerson', 'program'])
                ->whereIn('status', ['ORS_obligated', 'Outgoing_received'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            $alertedCount = 0;
            foreach ($criticalRequests as $request) {
                // Calculate whole calendar days
                $daysOld = floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay()));

                // Send real-time alert via Pusher for critical
                $this->pusher->trigger('dv-requests-outgoing', 'outgoing-critical-aging-alert', [
                    'dv_request_id' => $request->id,
                    'control_number' => $request->accounting_control_number ? 
                        'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
                    'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                    'program' => $request->program->prcode ?? 'N/A',
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

            $this->pusher->trigger('dv-requests-outgoing', 'outgoing-critical-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_critical' => $criticalRequests->count(),
                'alerted_count' => $alertedCount,
                'alert_type' => 'critical',
                'threshold_days' => $criticalThreshold,
            ]);

            Log::warning('Outgoing critical aging alerts sent', [
                'count' => $alertedCount,
                'threshold' => $criticalThreshold,
            ]);

            return response()->json([
                'success' => true,
                'alerted_count' => $alertedCount,
                'total_critical' => $criticalRequests->count(),
                'threshold_days' => $criticalThreshold,
                'alert_type' => 'critical',
                'message' => "Outgoing critical aging document alerts sent (10+ days)",
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending critical Outgoing aging alerts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send individual aging email for a focal person
     */
    private function sendIndividualAgingEmail($request, $email, $name, $daysOld, $urgencyLevel, $department = 'Outgoing')
    {
        try {
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning("{$department} - No valid email for focal person", [
                    'dv_request_id' => $request->id,
                    'email' => $email,
                ]);
                return false;
            }

            $subject = $this->getUrgencyText($urgencyLevel) . " ALERT: {$department} DV Request Delayed - {$request->reference_number}";
            
            $statusFormatted = str_replace('_', ' ', ucwords($request->status, '_'));
            $programName = $request->program->prcode ?? 'N/A';
            $createdAt = Carbon::parse($request->created_at, 'Asia/Manila');
            $submittedDate = $createdAt->format('M d, Y');
            $currentDate = now('Asia/Manila')->format('M d, Y');
            $currentTime = now('Asia/Manila')->format('h:i A');
            
            $controlNumber = $request->accounting_control_number 
                ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT)
                : 'Not assigned';
            
            $actionUrl = route('dv.verify', $request->reference_number);
            
            $htmlContent = view('emails.document_aging_alert', [
                'subject' => $subject,
                'focalPersonName' => $name,
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
                
            ])->render();
            
            Log::info("📧 {$department} - Preparing to send email", [
                'dv_request_id' => $request->id,
                'to_email' => $email,
                'subject' => $subject,
                'days_old' => $daysOld,
            ]);
            
            $emailSent = app(\App\Services\SendPulseServices::class)->sendAgingEmail(
                $email,
                $name,
                $request,
                $daysOld,
                $urgencyLevel,
                $subject,
                $htmlContent
            );
            
            if ($emailSent) {
                // Log email sent - ONLY 'action', NO 'status'
                
                Log::info("📧 {$department} email sent to focal person", [
                    'dv_request_id' => $request->id,
                    'focal_person_email' => $email,
                    'days_reported' => $daysOld,
                ]);
                return true;
            } else {
                Log::warning("📧 {$department} email failed to send", [
                    'dv_request_id' => $request->id,
                    'focal_person_email' => $email,
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error("{$department} email error for request {$request->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get aging alerts for specific user
     */
    public function getUserAgingAlerts()
    {
        try {
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
                        'control_number' => $alert->dvRequest->accounting_control_number ? 
                            'CN #-' . now()->year . '-' . str_pad($alert->dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
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