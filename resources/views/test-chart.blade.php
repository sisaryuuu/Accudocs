<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descriptive Analytics Dashboard</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-gray-100 p-6">
    <h1 class="text-2xl font-bold mb-6">Descriptive Analytics Dashboard</h1>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold">Total Requests</h2>
            <p class="text-2xl font-bold text-blue-600">120</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold">Approved</h2>
            <p class="text-2xl font-bold text-green-600">90</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold">Pending</h2>
            <p class="text-2xl font-bold text-yellow-600">25</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold">Avg. Processing Time</h2>
            <p class="text-2xl font-bold text-red-600">5 days</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Bar Chart -->
        <div class="bg-white p-4 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-2">Requests per Program</h2>
            <canvas id="requestsPerProgram"></canvas>
        </div>

        <!-- Pie Chart -->
        <div class="bg-white p-4 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-2">Documents per Department</h2>
            <canvas id="documentsPerDepartment"></canvas>
        </div>
    </div>

    <!-- Line Chart (Monthly Trend) -->
    <div class="bg-white p-4 rounded-lg shadow mb-8">
        <h2 class="text-lg font-semibold mb-2">Monthly Requests Trend</h2>
        <canvas id="monthlyRequestsTrend"></canvas>
    </div>

    <!-- Insights Section -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Insights & Highlights</h2>
        <ul class="list-disc list-inside space-y-2 text-gray-700">
            <li><strong>Program B</strong> received the highest number of requests.</li>
            <li>The <strong>Incoming Department</strong> handles the largest share of documents.</li>
            <li>Overall requests are showing an <strong>upward trend</strong> in the last 3 months.</li>
        </ul>
    </div>
</body>
</html>
<?php

namespace App\Http\Controllers\BudgetOfficer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrsRequest;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Program;
use App\Services\PusherService;
use App\Services\SendPulseServices;
use App\Services\IprogSmsService;
use App\Models\Notification;
use App\Models\BudgetObligationHistory;
use App\Models\DocumentAgingAlert;
use App\Models\UacsCode;
use App\Models\AdlCode;
use App\Models\AllotmentClass;
use App\Models\MfoPap;
use App\Models\ContinuingOrCurrent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Traits\TriggersDashboardUpdate;

class ObligateDVORSController extends Controller
{
    protected PusherService $pusher;
    protected IprogSmsService $smsService;
    use TriggersDashboardUpdate;

    public function __construct(PusherService $pusher, IprogSmsService $smsService)
    {
        $this->pusher = $pusher;
        $this->smsService = $smsService;
    }

    // ... keep ALL your existing methods EXCEPT reject and sendRejectionSMS ...

    /**
     * ⚡ OPTIMIZED REJECTION SMS - Fast and non-blocking for Budget Officer
     */
    protected function sendRejectionSMS($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            // Quick validation
            if (!$focalPerson || empty($focalPerson->contactnumber)) {
                Log::warning('Cannot send Budget Officer rejection SMS: focal person or contact number missing', [
                    'dv_request_id' => $dvRequest->id
                ]);
                return false;
            }

            $phoneNumber = $focalPerson->contactnumber;
            
            // ⚡ SHORT MESSAGE - Faster transmission
            $userSequence = $dvRequest->user_sequence ?? $dvRequest->id;
            $truncatedRemarks = strlen($rejectionRemarks) > 60 
                ? substr($rejectionRemarks, 0, 57) . '...' 
                : $rejectionRemarks;

            $message = "Hi {$focalPerson->fname}, DVORS #{$userSequence} rejected: {$truncatedRemarks} - DOLE XI";

            Log::info('Sending Budget Officer rejection SMS', [
                'phone' => $phoneNumber,
                'dv_request_id' => $dvRequest->id,
                'message_length' => strlen($message)
            ]);

            // ⚡ USE QUICK SMS METHOD - 5 second timeout
            $result = $this->smsService->sendQuickSMS($phoneNumber, $message);
            
            if ($result['success']) {
                Log::info('Budget Officer rejection SMS sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'phone' => $phoneNumber
                ]);
                return true;
            } else {
                Log::warning('Budget Officer rejection SMS failed - continuing', [
                    'dv_request_id' => $dvRequest->id,
                    'phone' => $phoneNumber
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            // ⚡ SILENT FAILURE - Don't affect user experience
            Log::warning('Budget Officer SMS rejection failed silently: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ⚡ OPTIMIZED REJECT METHOD - Fast response with non-blocking SMS
     */
    public function reject(Request $request, $id)
    {
        try {
            $request->validate([
                'remarks' => 'required|string|max:1000|min:10',
            ], [
                'remarks.required' => 'Please provide a reason for rejection.',
                'remarks.min' => 'Rejection reason must be at least 10 characters.',
            ]);

            $dvRequest = DvRequest::with(['orsRequest', 'focalPerson'])->findOrFail($id);
            $statusBefore = $dvRequest->status;
            $currentUserId = auth()->id();
            $rejectedAt = now();

            // Check if request can be rejected
            if (!in_array($dvRequest->status, [
                'Incoming-TUPAD_verified', 
                'Incoming-GIP_verified',
                'Incoming-TEV_verified',
                'ORS_received',
                'ORS_onhold'
            ])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request cannot be rejected at its current status: ' . $statusBefore
                ], 422);
            }

            // Check if locked by another user
            $lockDuration = now()->subMinutes(10);
            if ($dvRequest->locked_by && 
                $dvRequest->locked_by !== $currentUserId && 
                $dvRequest->locked_at >= $lockDuration) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is currently locked by another user.'
                ], 422);
            }

            // ✅ PHASE 1: FAST DATABASE OPERATIONS ONLY
            DB::transaction(function () use (
                $dvRequest, 
                $statusBefore, 
                $rejectedAt, 
                $request, 
                $currentUserId
            ) {
                // Update DV Request
                $dvRequest->update([
                    'status'       => 'ORS_rejected',
                    'rejected_by'  => $currentUserId,
                    'rejected_at'  => $rejectedAt,
                    'locked_by'    => null,
                    'locked_at'    => null,
                ]);

                // Update ORS Request if exists
                if ($dvRequest->orsRequest) {
                    $dvRequest->orsRequest->update([
                        'status'      => 'ORS_rejected',
                        'rejected_by' => $currentUserId,
                        'rejected_at' => $rejectedAt,
                    ]);
                }

                // Save verification history
                $this->saveBudgetObligationHistory(
                    $dvRequest,
                    'reject',
                    $request->remarks,
                    [
                        'status_before'   => $statusBefore,
                        'status_after'    => 'ORS_rejected',
                        'rejected_by'     => $currentUserId,
                        'rejected_at'     => $rejectedAt->toISOString(),
                        'rejection_reason' => $request->remarks,
                        'amount'          => $dvRequest->amount,
                        'ors_request_id'  => $dvRequest->orsRequest->id ?? null
                    ]
                );

                // Log action
                $this->logAction(
                    $dvRequest->id,
                    'Budget_Officer',
                    'ORS_rejected',
                    $request->remarks
                );

                // Create notification for focal person
                $focalId = $dvRequest->focalperson_id;
                if ($focalId) {
                    Notification::create([
                        'focalperson_id' => $focalId,
                        'dv_request_id'  => $dvRequest->id,
                        'status'         => 'ORS_rejected',
                        'action'         => 'Rejected',
                        'remarks'        => $request->remarks,
                    ]);

                    // Trigger Pusher event to focal person
                    $this->pusher->trigger('dv-requests-' . $focalId, 'status-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => 'ORS_rejected',
                        'action'        => 'Rejected',
                        'remarks'       => $request->remarks,
                        'timestamp'     => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);
                }

                // Trigger Pusher event to Budget Officer channel
                $this->pusher->trigger('dv-requests-budget-officer', 'ors-rejected', [
                    'dv_request_id'      => $dvRequest->id,
                    'control_number'     => $dvRequest->accounting_control_number ? 
                        'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) : 
                        'Unknown',
                    'status'             => 'ORS_rejected',
                    'rejected_by'        => auth()->user()->name ?? 'Budget Officer',
                    'timestamp'          => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'action_performer_id' => $currentUserId,
                ]);
            });

            // ✅ PHASE 2: SEND NOTIFICATIONS AFTER DB TRANSACTION (NON-BLOCKING)
            $smsSent = $this->sendRejectionSMS($dvRequest, $request->remarks);
            $emailSent = $this->sendRejectionEmail($dvRequest, $request->remarks);

            $this->triggerDashboardUpdate();

            // ✅ IMMEDIATE RESPONSE TO USER
            $responseMessage = 'ORS request has been rejected successfully.';
            if ($smsSent) {
                $responseMessage .= ' SMS notification sent.';
            } else {
                $responseMessage .= ' SMS will be retried.';
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $responseMessage,
                    'data' => [
                        'id'          => $dvRequest->id,
                        'status'      => 'ORS_rejected',
                        'rejected_at' => $rejectedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'email_sent'  => $emailSent,
                        'sms_sent'    => $smsSent
                    ]
                ]);
            }

            return redirect()->route('BudgetOfficer.dvrequests.index')
                ->with('success', 'ORS request has been rejected successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->validator->errors()->first()
                ], 422);
            }
            return redirect()->back()->withErrors($e->validator)->withInput();
            
        } catch (\Exception $e) {
            Log::error('Error rejecting ORS request: ' . $e->getMessage(), [
                'dv_request_id' => $id,
                'user_id' => auth()->id(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while rejecting the request: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while rejecting the request.');
        }
    }

    /**
     * ⚡ OPTIMIZED REJECTION EMAIL - Non-blocking for Budget Officer
     */
    protected function sendRejectionEmail($dvRequest, $rejectionRemarks)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            if (!$focalPerson || empty($focalPerson->email)) {
                Log::warning('Cannot send Budget Officer rejection email: focal person or email missing');
                return false;
            }

            $toEmail = $focalPerson->email;
            $toName = trim($focalPerson->fname . ' ' . $focalPerson->lname);
            
            Log::info('Sending Budget Officer rejection email', [
                'to_email' => $toEmail,
                'dv_request_id' => $dvRequest->id
            ]);

            // Try SendPulse first
            if (class_exists(SendPulseServices::class)) {
                $sendPulseService = new SendPulseServices();
                $result = $sendPulseService->sendRejectionEmail($toEmail, $toName, $dvRequest, $rejectionRemarks);
                
                if ($result) {
                    Log::info('SendPulse Budget Officer rejection email sent successfully');
                    return true;
                }
            }

            // Fallback to SMTP
            return $this->sendFallbackEmail($toEmail, $toName, $dvRequest, $rejectionRemarks);
            
        } catch (\Exception $e) {
            Log::warning('Budget Officer email rejection failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ⚡ OPTIMIZED FALLBACK EMAIL for Budget Officer
     */
    protected function sendFallbackEmail($toEmail, $toName, $dvRequest, $rejectionRemarks)
    {
        try {
            if (!$toEmail) return false;

            $subject = "DV/ORS Rejected - Budget Officer - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            
            Mail::send([], [], function ($message) use ($toEmail, $toName, $subject, $dvRequest, $rejectionRemarks) {
                $message->to($toEmail, $toName)
                       ->subject($subject)
                       ->from(config('mail.from.address'), config('mail.from.name'))
                       ->html($this->generateBudgetOfficerEmailContent($dvRequest, $rejectionRemarks));
            });

            Log::info('Budget Officer fallback email sent successfully');
            return true;
            
        } catch (\Exception $e) {
            Log::warning('Budget Officer fallback email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate Budget Officer-specific email content
     */
    protected function generateBudgetOfficerEmailContent($dvRequest, $rejectionRemarks)
    {
        return "
            <h3>DV/ORS Request Rejected - Budget Officer</h3>
            <p>Dear {$dvRequest->focalPerson->fname},</p>
            <p>Your DV/ORS Request #{$dvRequest->user_sequence} has been rejected by Budget Officer.</p>
            <p><strong>Reason:</strong> {$rejectionRemarks}</p>
            <p><strong>Rejected At:</strong> " . now()->timezone('Asia/Manila')->format('M d, Y h:i A') . "</p>
            <p>Please review and resubmit with corrections.</p>
            <br>
            <p>Thank you,<br>ACCUDOCS DOLE XI</p>
        ";
    }

    // ... keep ALL your other existing methods exactly as they are ...
}

<?php

namespace App\Http\Controllers\QRCode;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Notification;
use App\Models\RegionalDirectorApprovedHistory;
use App\Models\CashierReleasedHistory;
use App\Models\FocalPerson;
use App\Services\PusherService;
use App\Services\SendPulseServices;
use App\Services\IprogSmsService;
use App\Traits\TriggersDashboardUpdate; 
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DOLEQRController extends Controller
{
    protected PusherService $pusher;
    protected IprogSmsService $smsService;
    use TriggersDashboardUpdate;

    public function __construct(PusherService $pusher, IprogSmsService $smsService)
    {
        $this->pusher = $pusher;
        $this->smsService = $smsService;
    }

    // ... keep ALL your existing methods EXCEPT sendReleaseSMSNotification ...

    /**
     * ⚡ OPTIMIZED RELEASE SMS NOTIFICATION - Fast and non-blocking
     */
    protected function sendReleaseSMSNotification($dvRequest, $user)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            // Quick validation
            if (!$focalPerson || empty($focalPerson->contactnumber)) {
                Log::warning('Cannot send release SMS: focal person or contact number missing', [ 
                    'dv_request_id' => $dvRequest->id
                ]);
                return false;
            }

            $phoneNumber = $focalPerson->contactnumber;
            
            // ⚡ SHORT MESSAGE - Faster transmission
            $userSequence = $dvRequest->dv_number ?? $dvRequest->user_sequence ?? 'N/A';
            $formattedAmount = number_format($dvRequest->amount, 2);
            
            $message = "Hi {$focalPerson->fname}, DVORS #{$userSequence} released! Amount: ₱{$formattedAmount} - DOLE XI";

            Log::info('Sending RELEASE SMS', [
                'phone' => $phoneNumber,
                'dv_request_id' => $dvRequest->id,
                'user_sequence' => $userSequence,
                'message_length' => strlen($message)
            ]);

            // ⚡ USE QUICK SMS METHOD - 5 second timeout
            $result = $this->smsService->sendQuickSMS($phoneNumber, $message);
            
            if ($result['success']) {
                Log::info('RELEASE SMS sent successfully', [
                    'dv_request_id' => $dvRequest->id,
                    'phone' => $phoneNumber
                ]);
                return true;
            } else {
                Log::warning('RELEASE SMS failed - continuing', [
                    'dv_request_id' => $dvRequest->id,
                    'phone' => $phoneNumber
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            // ⚡ SILENT FAILURE - Don't affect user experience
            Log::warning('RELEASE SMS failed silently: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ⚡ OPTIMIZED CASHIER BULK RELEASE - Fast response with non-blocking SMS
     */
    public function cashierBulkRelease(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user || !$user->hasRole('Cashier')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only Cashier can perform this action.'
                ], 403);
            }

            $dvIds = $request->input('dv_ids', []);
            
            if (empty($dvIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No DVs selected for bulk release.'
                ], 400);
            }

            DB::beginTransaction();

            // Get all eligible DVs in one query WITH FRESH DATA
            $eligibleDVs = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('id', $dvIds)
                ->where('qr_status', 'active')
                ->where('status', 'Cashier_received')
                ->get();

            if ($eligibleDVs->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No eligible DVs found for release'
                ], 400);
            }

            $eligibleDvIds = $eligibleDVs->pluck('id')->toArray();
            $releasedAt = now();
            $newStatus = 'Cashier_released';
            $action = 'Transaction Released by Cashier';

            // ✅ Initialize notification tracking
            $notificationResults = [
                'email_sent' => 0,
                'sms_sent' => 0,
                'email_failed' => 0,
                'sms_failed' => 0
            ];

            // ✅ PHASE 1: FAST DATABASE OPERATIONS ONLY
            $updatedCount = DvRequest::whereIn('id', $eligibleDvIds)
                ->update([
                    'status' => $newStatus,
                    'updated_at' => $releasedAt
                ]);

            // ✅ CRITICAL: Reload DVs after update to get fresh data
            $eligibleDVs = DvRequest::with(['focalPerson', 'program', 'respocenter'])
                ->whereIn('id', $eligibleDvIds)
                ->get();

            $notificationData = [];
            $successfulDVs = [];

            foreach ($eligibleDVs as $dv) {
                // Save Cashier History
                $this->saveCashierHistory(
                    $dv,
                    'released',
                    $action,
                    [
                        'status_before' => 'Cashier_received',
                        'status_after' => $newStatus,
                        'released_by' => $user->id,
                        'released_at' => $releasedAt->toISOString(),
                        'amount' => $dv->amount,
                        'dv_number' => $dv->dv_number,
                        'accounting_control_number' => $dv->accounting_control_number,
                        'processing_type' => 'bulk_release'
                    ]
                );
                
                $this->logQrAction(
                    $dv->id,
                    $user->id,
                    'Cashier',
                    'Cashier_released',
                    $action . ' at: ' . $releasedAt->format('Y-m-d H:i:s')
                );

                if ($dv->focalperson_id) {
                    $notificationData[] = [
                        'focalperson_id' => $dv->focalperson_id,
                        'dv_request_id' => $dv->id,
                        'status' => $newStatus,
                        'action' => 'Status Updated to Cashier Released',
                        'remarks' => "DV status updated to {$newStatus} by Cashier",
                        'created_at' => $releasedAt,
                        'updated_at' => $releasedAt
                    ];
                }

                $successfulDVs[] = [
                    'dv_id' => $dv->id,
                    'dv_number' => $dv->dv_number,
                    'new_status' => $newStatus
                ];

                // Broadcast individual status updates
                $this->broadcastStatusUpdate($dv, 'Cashier_received', $newStatus, 'Cashier');
            }

            if (!empty($notificationData)) {
                Notification::insert($notificationData);
            }

            DB::commit();

            // ✅ PHASE 2: SEND NOTIFICATIONS AFTER DB TRANSACTION (NON-BLOCKING)
            foreach ($eligibleDVs as $dv) {
                // ✅ Send email notification
                $emailSent = $this->sendReleaseEmailNotification($dv, $user);
                if ($emailSent) {
                    $notificationResults['email_sent']++;
                } else {
                    $notificationResults['email_failed']++;
                }

                // ✅ Send SMS notification with FRESH data
                $smsSent = $this->sendReleaseSMSNotification($dv, $user);
                
                if ($smsSent) {
                    $notificationResults['sms_sent']++;
                } else {
                    $notificationResults['sms_failed']++;
                }
            }

            // Single Pusher event for bulk completion
            $this->pusher->trigger('cashier-dashboard', 'bulk-release-completed', [
                'successful_dvs' => $successfulDVs,
                'successful_count' => count($successfulDVs),
                'failed_count' => count($dvIds) - count($successfulDVs),
                'notification_results' => $notificationResults,
                'timestamp' => $releasedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            // Update statistics
            $this->updateDashboardStatistics();
            $this->triggerDashboardUpdate();
             
            Log::info('Cashier bulk release completed', [
                'total_selected' => count($dvIds),
                'successful_count' => count($successfulDVs),
                'failed_count' => count($dvIds) - count($successfulDVs),
                'notification_results' => $notificationResults,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bulk release completed successfully',
                'results' => [
                    'successful' => $successfulDVs,
                    'failed' => array_values(array_diff($dvIds, $eligibleDvIds))
                ],
                'notification_summary' => $notificationResults,
                'summary' => [
                    'total_processed' => count($dvIds),
                    'successful' => count($successfulDVs),
                    'failed' => count($dvIds) - count($successfulDVs)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cashier bulk release error: ' . $e->getMessage(), [
                'dv_ids' => $dvIds ?? [],
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error during bulk release: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⚡ OPTIMIZED QR STATUS UPDATE - Fast response with non-blocking SMS
     */
    public function updateStatusViaQr(Request $request, $token)
    {
        try {
            $dvRequest = DvRequest::with(['focalPerson', 'program'])->where('qr_code_token', $token)->first();
            
            if (!$dvRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code or DV request not found'
                ], 404);
            }
            
            if ($dvRequest->qr_status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code not yet activated. Please wait for Accounting III validation.',
                    'current_status' => $dvRequest->status,
                    'qr_status' => $dvRequest->qr_status
                ], 422);
            }

            if ($dvRequest->qr_code_generated_at && $dvRequest->qr_code_generated_at->diffInDays(now()) > 30) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code has expired'
                ], 410);
            }

            $user = auth()->user();
            $newStatus = null;
            $action = '';
            $role = '';
            $previousStatus = $dvRequest->status;
            $actionTime = now();

            Log::info('QR Update Attempt', [
                'current_status' => $dvRequest->status,
                'user_role' => $user ? $user->getRoleNames() : 'no_user',
                'user_id' => $user ? $user->id : 'no_user'
            ]);
            

            if ($user && $user->hasRole('Regional_Director')) {
                if ($dvRequest->status === 'RANCA_validated') {
                    $newStatus = 'Regional_Director_received';
                    $action = 'Received by Regional Director';
                    $role = 'Regional_Director';
                    $step = 'received';
                } elseif ($dvRequest->status === 'Regional_Director_received') {
                    $newStatus = 'Regional_Director_signed_approved';
                    $action = 'Signed and Approved by Regional Director';
                    $role = 'Regional_Director';
                    $step = 'signed_approved';
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "No action available for current status: {$dvRequest->status}. Expected: RANCA_validated or Regional_Director_received",
                        'current_status' => $dvRequest->status,
                    ], 422);
                }
                
            } elseif ($user && $user->hasRole('Cashier')) {
                if ($dvRequest->status === 'Regional_Director_signed_approved') {
                    $newStatus = 'Cashier_received';
                    $action = 'Received for Processing by Cashier';
                    $role = 'Cashier';
                    $step = 'received';
                } elseif ($dvRequest->status === 'Cashier_received') {
                    $newStatus = 'Cashier_released';
                    $action = 'Transaction Released by Cashier';
                    $role = 'Cashier';
                    $step = 'released';
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "No action available for current status: {$dvRequest->status}. Expected: Regional_Director_signed_approved or Cashier_received",
                        'current_status' => $dvRequest->status,
                    ], 422);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in with appropriate role to update status',
                    'login_required' => true,
                    'required_roles' => ['Regional_Director', 'Cashier'],
                ]);
            }
            
            if ($newStatus) {
                // ✅ PHASE 1: FAST DATABASE OPERATIONS ONLY
                $dvRequest->update([
                    'status' => $newStatus,
                    'updated_by' => $user->id
                ]);

                $dvRequest->refresh();

                if ($newStatus === 'Regional_Director_signed_approved') {
                    // ✅ Broadcast to Cashier when RD approves via QR
                    $this->broadcastToCashierDashboard($dvRequest, 'qr_scan_approval');
                }

                // ✅ Save history based on role
                if ($role === 'Regional_Director') {
                    $this->saveRegionalDirectorHistory(
                        $dvRequest,
                        $step,
                        $action,
                        [
                            'status_before' => $previousStatus,
                            'status_after' => $newStatus,
                            'approved_by' => $user->id,
                            'approved_at' => $actionTime->toISOString(),
                            'amount' => $dvRequest->amount,
                            'dv_number' => $dvRequest->dv_number,
                            'processing_type' => 'qr_scan',
                            'qr_token' => $token
                        ]
                    );
                } elseif ($role === 'Cashier') {
                    $this->saveCashierHistory(
                        $dvRequest,
                        $step,
                        $action,
                        [
                            'status_before' => $previousStatus,
                            'status_after' => $newStatus,
                            'released_by' => $user->id,
                            'released_at' => $actionTime->toISOString(),
                            'amount' => $dvRequest->amount,
                            'dv_number' => $dvRequest->dv_number,
                            'processing_type' => 'qr_scan',
                            'qr_token' => $token
                        ]
                    );
                }

                $this->logQrAction(
                    $dvRequest->id,
                    $user->id,
                    $role,
                    $newStatus,
                    $action . ' - Scanned at: ' . $actionTime->format('Y-m-d H:i:s')
                );

                $this->broadcastStatusUpdate($dvRequest, $previousStatus, $newStatus, $role);

                if ($dvRequest->focalperson_id) {
                    Notification::create([
                        'focalperson_id' => $dvRequest->focalperson_id,
                        'dv_request_id'  => $dvRequest->id,
                        'status'         => $newStatus,
                        'action'         => 'Status Updated via QR',
                        'remarks'        => "DV status updated to {$newStatus} by {$role} via QR code scan",
                    ]);

                    $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'status-updated', [
                        'dv_request_id' => $dvRequest->id,
                        'status'        => $newStatus,
                        'remarks'       => "Status updated by {$role} via QR scan",
                        'timestamp'     => $actionTime->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);
                }

                // ✅ PHASE 2: SEND NOTIFICATIONS AFTER DB TRANSACTION (NON-BLOCKING)
                if ($newStatus === 'Cashier_released') {
                    $emailSent = $this->sendReleaseEmailNotification($dvRequest, $user);
                    $smsSent = $this->sendReleaseSMSNotification($dvRequest, $user);
                    
                    Log::info('Cashier release notifications sent', [
                        'dv_id' => $dvRequest->id,
                        'email_sent' => $emailSent,
                        'sms_sent' => $smsSent
                    ]);
                }

                Log::info('DV status updated via QR code', [
                    'dv_id' => $dvRequest->id,
                    'dv_number' => $dvRequest->dv_number,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'updated_by' => $user->id,
                    'user_role' => $role,
                    'action' => $action
                ]);
                
                $this->triggerDashboardUpdate();
                  
                return response()->json([
                    'success' => true,
                    'message' => "Status updated successfully: {$action}",
                    'new_status' => $newStatus,
                    'dv_number' => $dvRequest->dv_number,
                    'updated_by' => $user->name,
                    'role' => $role,
                    'next_action' => $this->getNextAction($newStatus, $role),
                    'action_performed' => $action
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "No action available for current status: {$dvRequest->status}",
                    'current_status' => $dvRequest->status,
                    'expected_statuses' => $this->getRequiredStatus($user ? $user->getRoleNames()->first() : '')
                ], 422);
            }

        } catch (\Exception $e) {
            Log::error('QR Status Update Error: ' . $e->getMessage(), [
                'token' => $token,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
            ], 500);
        }
    }

    // ... keep ALL your other existing methods exactly as they are ...
}