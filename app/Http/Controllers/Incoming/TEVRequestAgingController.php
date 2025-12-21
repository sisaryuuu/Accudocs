<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use App\Models\DvRequest;
use App\Models\DocumentAgingAlert;
use App\Services\PusherService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class TEVRequestAgingController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

    public function getAgingDocuments()
    {
        try {
            Log::info('Fetching TEV aging documents');
            
            $agingThreshold = 3; // days
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereHas('program', function($query) {
                    $query->where('prcode', 'TEV'); // ONLY TEV PROGRAM
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-TEV_received', 'Incoming-TEV_in_process'])
                ->where('created_at', '<=', $cutoffTime)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($request) {
                    // FIX: Use proper days calculation with whole numbers
                    $daysOld = now()->diffInDays(Carbon::parse($request->created_at), false);
                    $daysOld = floor((int) $daysOld);
                    
                    return [
                        'id' => $request->id,
                        'control_number' => $request->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) : '—',
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'status' => $request->status,
                        'days_old' => $daysOld, // Now this will be a positive whole number
                        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
                        'urgency' => $this->getUrgencyLevel($daysOld),
                    ];
                });

            Log::info('TEV aging documents fetched successfully', ['count' => $agingRequests->count()]);

            return response()->json([
                'success' => true,
                'aging_count' => $agingRequests->count(),
                'documents' => $agingRequests,
                'threshold_days' => $agingThreshold,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching TEV aging documents: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch aging documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get TEV aging statistics
     */
    public function getAgingStatistics()
    {
        try {
            Log::info('Fetching TEV aging statistics');
            
            $tenDaysAgo = now()->subDays(10);
            $sevenDaysAgo = now()->subDays(7);
            $threeDaysAgo = now()->subDays(3);
            
            $baseQuery = function() {
                return DvRequest::whereHas('program', function($query) {
                    $query->where('prcode', 'TEV'); // ONLY TEV PROGRAM
                })->whereIn('status', ['IMSD_verified', 'Incoming-TEV_received', 'Incoming-TEV_in_process']);
            };
            
            $statistics = [
                'critical' => $baseQuery()->where('created_at', '<', $tenDaysAgo)->count(),
                'high' => $baseQuery()->where('created_at', '>=', $tenDaysAgo)
                    ->where('created_at', '<', $sevenDaysAgo)->count(),
                'medium' => $baseQuery()->where('created_at', '>=', $sevenDaysAgo)
                    ->where('created_at', '<', $threeDaysAgo)->count(),
                'total_aging' => $baseQuery()->where('created_at', '<', $threeDaysAgo)->count(),
            ];

            Log::info('TEV aging statistics fetched successfully', $statistics);

            return response()->json([
                'success' => true,
                ...$statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching TEV aging statistics: ' . $e->getMessage(), [
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
        Log::info('🚀 Starting TEV focal person aging notifications');
        
        $criticalThreshold = 10;
        $now = now('Asia/Manila');
        $cutoffDate = $now->copy()->subDays($criticalThreshold);

        $completedStatuses = [
            'cashier_released',
            '%_rejected',
            'completed',
        ];

        Log::info('TEV notification parameters', [
            'current_date' => $now->toDateTimeString(),
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'threshold_days' => $criticalThreshold,
        ]);

        $agingRequests = DvRequest::with(['focalPerson', 'program'])
            ->whereHas('program', function($q) {
                $q->where('prcode', 'TEV');
            })
            ->whereNotIn('status', $completedStatuses)
            ->where('created_at', '<=', $cutoffDate)
            ->get();

        Log::info('TEV requests for notification', [
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
                    Log::warning('TEV - Skipping, no focal person', [
                        'dv_request_id' => $request->id,
                        'reference_number' => $request->reference_number,
                    ]);
                    $skippedCount++;
                    continue;
                }

                $createdAt = Carbon::parse($request->created_at, 'Asia/Manila');
                $daysOld = abs(floor(now()->startOfDay()->diffInDays($request->created_at->startOfDay(), false))) ?: 0;
                
                if ($createdAt->isFuture()) {
                    Log::warning('TEV - Skipping, document in future', [
                        'dv_request_id' => $request->id,
                    ]);
                    $skippedCount++;
                    continue;
                }
                
                if ($daysOld < $criticalThreshold) {
                    Log::info("TEV - Skipping, not old enough", [
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
                    Log::info("TEV - Already notified today", [
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
                    'remarks' => "TEV document has been pending for {$daysOld} days",
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 2. Send email notification
                if (!empty($focalPersonEmail) && filter_var($focalPersonEmail, FILTER_VALIDATE_EMAIL)) {
                    try {
                        $subject = $this->getUrgencyText($urgencyLevel) . " ALERT: TEV DV Request Delayed - {$request->reference_number}";
                        
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
                            'department' => 'TEV',
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
                            Log::info("📧 TEV email sent to focal person", [
                                'dv_request_id' => $request->id,
                                'focal_person_email' => $focalPersonEmail,
                            ]);
                        }
                        
                    } catch (\Exception $emailError) {
                        Log::error("TEV email error for request {$request->id}: " . $emailError->getMessage());
                    }
                }

                // 3. Send real-time Pusher notification
                $this->pusher->trigger("dv-requests-{$request->focalPerson->id}", 'document-critical-aging-tev', [
                    'dv_request_id' => $request->id,
                    'reference_number' => $request->reference_number,
                    'control_number' => $controlNumber,
                    'days_old' => $daysOld,
                    'urgency' => $urgencyLevel,
                    'department' => 'TEV',
                    'message' => "⚠️ Your TEV DV request has been delayed for {$daysOld} days",
                    'action_url' => route('FocalPerson.dvrequest.logs', $request),
                    'timestamp' => $now->format('M d, Y h:i A'),
                    'status' => $request->status,
                ]);

                $notifiedCount++;
                
                Log::info("✅ TEV - Successfully notified focal person", [
                    'dv_request_id' => $request->id,
                    'reference_number' => $request->reference_number,
                    'days_old' => $daysOld,
                ]);

            } catch (\Exception $e) {
                $errors[] = [
                    'request_id' => $request->id,
                    'error' => $e->getMessage()
                ];
                Log::error("TEV error notifying request {$request->id}: " . $e->getMessage());
            }
        }

        Log::info('🎯 TEV focal person aging notifications completed', [
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
            'message' => "Successfully notified {$notifiedCount} focal person(s) of TEV {$criticalThreshold}+-day aging documents",
        ]);

    } catch (\Exception $e) {
        Log::error('❌ TEV error sending focal person aging notifications: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to send TEV aging notifications',
            'error' => $e->getMessage()
        ], 500);
    }
}

// Don't forget to add these helper methods if not already in your TEV controller:
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
    /**
     * Determine urgency level based on days old
     */
    protected function getUrgencyLevel($daysOld)
    {
        // FIX: Ensure we're working with positive whole numbers
        $daysOld = floor((int) $daysOld);
        
        if ($daysOld >= 10) return 'critical';
        if ($daysOld >= 7) return 'high';
        if ($daysOld >= 3) return 'medium';
        return 'low';
    }

    /**
     * Send aging alerts for TEV documents
     */
    public function sendAgingAlerts()
    {
        try {
            $agingThreshold = 3;
            $cutoffTime = now()->subDays($agingThreshold);

            $agingRequests = DvRequest::with('focalPerson')
                ->whereHas('program', function($q) {
                    $q->where('prcode', 'TEV');
                })
                ->whereIn('status', ['IMSD_verified', 'Incoming-TEV_received'])
                ->where('created_at', '<=', $cutoffTime)
                ->get();

            if ($agingRequests->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No aging TEV documents to alert'
                ]);
            }

            $alertedCount = 0;
            foreach ($agingRequests as $request) {
                // FIX: Use proper days calculation with whole numbers
                $daysOld = now()->diffInDays(Carbon::parse($request->created_at), false);
                $daysOld = floor((int) $daysOld);

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
                    $this->pusher->trigger('dv-requests-incoming-tev', 'document-aging-alert', [
                        'dv_request_id' => $request->id,
                        'control_number' => $request->accounting_control_number,
                        'submitted_by' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
                        'program' => $request->program->prcode ?? 'N/A',
                        'document_type' => 'TEV',
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
                'message' => 'TEV aging document alerts sent',
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending TEV aging alerts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send TEV aging alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}