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
use Illuminate\Support\Facades\DB;
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
     */
    public function getAgingDocuments()
    {
        try {
            Log::info('Fetching GIP aging documents');
            
            $agingThreshold = 3; // days
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereHas('program', function($q) {
                    $q->where('prcode', 'GIP');
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_received', 'GIP_in_process'])
                ->where('created_at', '<=', $cutoffTime)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($request) {
                    $daysOld = now()->diffInDays($request->created_at);
                    
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
                        'days_old' => $daysOld,
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'urgency' => $this->getUrgencyLevel($daysOld),
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
            
            // Define completed statuses for GIP
            $completedStatuses = [
                'cashier_released',
                '%_rejected',
                'completed',
            ];
            
            $tenDaysAgo = now()->subDays(10);
            $sevenDaysAgo = now()->subDays(7);
            $threeDaysAgo = now()->subDays(3);
            
            $statistics = [
                'critical' => DvRequest::whereHas('program', function($q) {
                        $q->where('prcode', 'GIP');
                    })
                    ->whereNotIn('status', $completedStatuses)
                    ->where('created_at', '<', $tenDaysAgo)
                    ->count(),
                'high' => DvRequest::whereHas('program', function($q) {
                        $q->where('prcode', 'GIP');
                    })
                    ->whereNotIn('status', $completedStatuses)
                    ->where('created_at', '>=', $tenDaysAgo)
                    ->where('created_at', '<', $sevenDaysAgo)
                    ->count(),
                'medium' => DvRequest::whereHas('program', function($q) {
                        $q->where('prcode', 'GIP');
                    })
                    ->whereNotIn('status', $completedStatuses)
                    ->where('created_at', '>=', $sevenDaysAgo)
                    ->where('created_at', '<', $threeDaysAgo)
                    ->count(),
                'total_aging' => DvRequest::whereHas('program', function($q) {
                        $q->where('prcode', 'GIP');
                    })
                    ->whereNotIn('status', $completedStatuses)
                    ->where('created_at', '<', $threeDaysAgo)
                    ->count(),
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
     * Send aging alerts for GIP dashboard
     */
    public function sendAgingAlerts($criticalOnly = false)
    {
        try {
            Log::info('Starting GIP aging alerts', ['criticalOnly' => $criticalOnly]);
            
            if ($criticalOnly) {
                return $this->sendCriticalGIPAgingAlerts();
            } else {
                return $this->sendAllGIPAgingAlerts();
            }

        } catch (\Exception $e) {
            Log::error('Error in sendAgingAlerts for GIP: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send GIP aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send alerts for ALL aging GIP documents (3+ days)
     */
    private function sendAllGIPAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program'])
                ->whereHas('program', function($q) {
                    $q->where('prcode', 'GIP');
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_received', 'GIP_in_process'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            foreach ($agingRequests as $request) {
                $daysOld = now()->diffInDays($request->created_at);
                
                $this->pusher->trigger('dv-requests-incoming-gip', 'document-aging-alert', [
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

            $this->pusher->trigger('dv-requests-incoming-gip', 'gip-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_aging' => $agingRequests->count(),
                'alert_type' => 'all',
            ]);

            Log::info('GIP all aging alerts sent', [
                'count' => $agingRequests->count(),
                'threshold' => $agingThreshold,
            ]);

            return response()->json([
                'success' => true,
                'alerted_count' => $agingRequests->count(),
                'threshold_days' => $agingThreshold,
                'alert_type' => 'all',
                'message' => "GIP aging document alerts sent (3+ days)",
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending all GIP aging alerts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send alerts for CRITICAL aging GIP documents (10+ days)
     */
    private function sendCriticalGIPAgingAlerts()
    {
        try {
            $criticalThreshold = 10;
            $cutoffTime = now()->subDays($criticalThreshold);

            $criticalRequests = DvRequest::with(['focalPerson', 'program'])
                ->whereHas('program', function($q) {
                    $q->where('prcode', 'GIP');
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-GIP_received', 'GIP_in_process'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            foreach ($criticalRequests as $request) {
                $daysOld = now()->diffInDays($request->created_at);
                
                $this->pusher->trigger('dv-requests-incoming-gip', 'document-aging-alert', [
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

            $this->pusher->trigger('dv-requests-incoming-gip', 'gip-critical-aging-update', [
                'updated_at' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'total_critical' => $criticalRequests->count(),
                'alert_type' => 'critical',
                'threshold_days' => $criticalThreshold,
            ]);

            Log::warning('GIP critical aging alerts sent', [
                'count' => $criticalRequests->count(),
                'threshold' => $criticalThreshold,
            ]);

            return response()->json([
                'success' => true,
                'alerted_count' => $criticalRequests->count(),
                'threshold_days' => $criticalThreshold,
                'alert_type' => 'critical',
                'message' => "GIP critical aging document alerts sent (10+ days)",
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending critical GIP aging alerts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Notify focal persons when GIP documents are 10+ days old
     */
    public function notifyFocalPersonsOfAgingDocuments()
    {
        try {
            Log::info('🚀 Starting GIP focal person aging notifications');
            
            $criticalThreshold = 10;
            $now = now('Asia/Manila');
            $cutoffDate = $now->copy()->subDays($criticalThreshold);

            $completedStatuses = [
                'cashier_released',
                '%_rejected',
                'completed',
            ];

            Log::info('GIP notification parameters', [
                'current_date' => $now->toDateTimeString(),
                'cutoff_date' => $cutoffDate->toDateTimeString(),
                'threshold_days' => $criticalThreshold,
            ]);

            $agingRequests = DvRequest::with(['focalPerson', 'program'])
                ->whereHas('program', function($q) {
                    $q->where('prcode', 'GIP');
                })
                ->whereNotIn('status', $completedStatuses)
                ->where('created_at', '<=', $cutoffDate)
                ->get();

            Log::info('GIP requests for notification', [
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
                        Log::warning('GIP - Skipping, no focal person', [
                            'dv_request_id' => $request->id,
                            'reference_number' => $request->reference_number,
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    $createdAt = Carbon::parse($request->created_at, 'Asia/Manila');
                    $daysOld = abs(floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay(), false))) ?: 0;
                    
                    if ($createdAt->isFuture()) {
                        Log::warning('GIP - Skipping, document in future', [
                            'dv_request_id' => $request->id,
                        ]);
                        $skippedCount++;
                        continue;
                    }
                    
                    if ($daysOld < $criticalThreshold) {
                        Log::info("GIP - Skipping, not old enough", [
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
                        Log::info("GIP - Already notified today", [
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
                        'remarks' => "GIP document has been pending for {$daysOld} days",
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // 2. Send email notification
                    if (!empty($focalPersonEmail) && filter_var($focalPersonEmail, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $subject = $this->getUrgencyText($urgencyLevel) . " ALERT: GIP DV Request Delayed - {$request->reference_number}";
                            
                            $statusFormatted = str_replace('_', ' ', ucwords($request->status, '_'));
                            $programName = $request->program->prcode ?? 'N/A';
                            $submittedDate = $createdAt->format('M d, Y');
                            $currentDate = $now->format('M d, Y');
                            $currentTime = $now->format('h:i A');
                            
                            $actionUrl = route('FocalPerson.dvrequest.logs', $request);
                            
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
                                'department' => 'GIP',
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
                                Log::info("📧 GIP email sent to focal person", [
                                    'dv_request_id' => $request->id,
                                    'focal_person_email' => $focalPersonEmail,
                                ]);
                            }
                            
                        } catch (\Exception $emailError) {
                            Log::error("GIP email error for request {$request->id}: " . $emailError->getMessage());
                        }
                    }

                    // 3. Send real-time Pusher notification
                    $this->pusher->trigger("dv-requests-{$request->focalPerson->id}", 'document-critical-aging-gip', [
                        'dv_request_id' => $request->id,
                        'reference_number' => $request->reference_number,
                        'control_number' => $controlNumber,
                        'days_old' => $daysOld,
                        'urgency' => $urgencyLevel,
                        'department' => 'GIP',
                        'message' => "⚠️ Your GIP DV request has been delayed for {$daysOld} days",
                        'action_url' => route('dv.verify', $request->reference_number),
                        'timestamp' => $now->format('M d, Y h:i A'),
                        'status' => $request->status,
                    ]);

                    $notifiedCount++;
                    
                    Log::info("✅ GIP - Successfully notified focal person", [
                        'dv_request_id' => $request->id,
                        'reference_number' => $request->reference_number,
                        'days_old' => $daysOld,
                    ]);

                } catch (\Exception $e) {
                    $errors[] = [
                        'request_id' => $request->id,
                        'error' => $e->getMessage()
                    ];
                    Log::error("GIP error notifying request {$request->id}: " . $e->getMessage());
                }
            }

            Log::info('🎯 GIP focal person aging notifications completed', [
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
                'message' => "Successfully notified {$notifiedCount} focal person(s) of GIP {$criticalThreshold}+-day aging documents",
            ]);

        } catch (\Exception $e) {
            Log::error('❌ GIP error sending focal person aging notifications: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send GIP aging notifications',
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