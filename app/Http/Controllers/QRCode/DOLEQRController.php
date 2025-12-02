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
use App\Services\IprogSmsService; // ADD SMS SERVICE
use App\Traits\TriggersDashboardUpdate; 
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DOLEQRController extends Controller
{
    protected PusherService $pusher;
    protected IprogSmsService $smsService; // ADD SMS SERVICE PROPERTY
    use TriggersDashboardUpdate;

    public function __construct(PusherService $pusher, IprogSmsService $smsService) // UPDATE CONSTRUCTOR
    {
        $this->pusher = $pusher;
        $this->smsService = $smsService; // INITIALIZE SMS SERVICE
    }

    /**
     * Regional Director QR Scanner Interface
     */
    public function regionalDirectorScanner()
    {
        return view('DOLEQR.regional-director-scanner');
    }

    /**
     * Cashier QR Scanner Interface
     */
    public function cashierScanner()
    {
        return view('DOLEQR.cashier-scanner');
    }

    public function bulkReceiveDVs(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user || !$user->hasRole('Regional_Director')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only Regional Director can perform this action.'
                ], 403);    
            }

            $dvIds = $request->input('dv_ids', []);
            
            if (empty($dvIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No DVs selected for bulk receive.'
                ], 400);
            }

            $results = [
                'successful' => [],
                'failed' => []
            ];

            DB::beginTransaction();

            foreach ($dvIds as $dvId) {
                try {
                    $dvRequest = DvRequest::with(['focalPerson', 'program'])
                        ->where('id', $dvId)
                        ->where('qr_status', 'active')
                        ->first();

                    if (!$dvRequest) {
                        $results['failed'][] = [
                            'dv_id' => $dvId,
                            'reason' => 'DV not found or QR not active'
                        ];
                        continue;
                    }

                    if ($dvRequest->status !== 'RANCA_validated') {
                        $results['failed'][] = [
                            'dv_id' => $dvId,
                            'dv_number' => $dvRequest->dv_number,
                            'reason' => "Invalid status for receiving. Current status: {$dvRequest->status}"
                        ];
                        continue;
                    }

                    $previousStatus = $dvRequest->status;
                    $newStatus = 'Regional_Director_received';
                    $action = 'Request Received by Regional Director';
                    $receivedAt = now();

                    $dvRequest->update([
                        'status' => $newStatus,
                        'updated_by' => $user->id
                    ]);

                    $dvRequest->refresh();

                    // ✅ Save Regional Director History
                    $this->saveRegionalDirectorHistory(
                        $dvRequest,
                        'received',
                        $action,
                        [
                            'status_before' => $previousStatus,
                            'status_after' => $newStatus,
                            'received_by' => $user->id,
                            'received_at' => $receivedAt->toISOString(),
                            'amount' => $dvRequest->amount,
                            'dv_number' => $dvRequest->dv_number,
                            'processing_type' => 'bulk'
                        ]
                    );

                    $this->logQrAction(
                        $dvRequest->id,
                        $user->id,
                        'Regional_Director',
                        $newStatus,
                       $action
                       );

                    $this->broadcastStatusUpdate($dvRequest, $previousStatus, $newStatus, 'Regional_Director');

                    if ($dvRequest->focalperson_id) {
                        Notification::create([
                            'focalperson_id' => $dvRequest->focalperson_id,
                            'dv_request_id'  => $dvRequest->id,
                            'status'         => $newStatus,
                            'action'         => 'Status Updated via Bulk Receive',
                            'remarks'        => "DV status updated to {$newStatus}",
                        ]);

                        $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'status-updated', [
                            'dv_request_id' => $dvRequest->id,
                            'status'        => $newStatus,
                            'remarks'       => "Status updated by Regional Director via bulk receive",
                            'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        ]);
                    }

                    $results['successful'][] = [
                        'dv_id' => $dvRequest->id,
                        'dv_number' => $dvRequest->dv_number,
                        'new_status' => $newStatus
                    ];

                    \Log::info('DV bulk received by Regional Director', [
                        'dv_id' => $dvRequest->id,
                        'dv_number' => $dvRequest->dv_number,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'updated_by' => $user->id
                    ]);

                } catch (\Exception $e) {
                    \Log::error('Error processing DV in bulk receive: ' . $e->getMessage(), [
                        'dv_id' => $dvId,
                        'error' => $e->getMessage()
                    ]);

                    $results['failed'][] = [
                        'dv_id' => $dvId,
                        'reason' => 'Processing error: ' . $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $this->pusher->trigger('regional-director-dashboard', 'bulk-receive-completed', [
                'successful_dvs' => $results['successful'],
                'successful_count' => count($results['successful']),
                'failed_count' => count($results['failed']),
                'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            $this->updateDashboardStatistics();
            $this->triggerDashboardUpdate();

            return response()->json([
                'success' => true,
                'message' => 'Bulk receive completed',
                'results' => $results,
                'summary' => [
                    'total_processed' => count($dvIds),
                    'successful' => count($results['successful']),
                    'failed' => count($results['failed'])
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Bulk receive error: ' . $e->getMessage(), [
                'dv_ids' => $dvIds,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error during bulk receive: ' . $e->getMessage()
            ], 500);
        }
    }


    public function bulkSignApproveDVs(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user || !$user->hasRole('Regional_Director')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only Regional Director can perform this action.'
                ], 403);
            }

            $dvIds = $request->input('dv_ids', []);
            
            if (empty($dvIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No DVs selected for bulk sign and approve.'
                ], 400);
            }

            $results = [
                'successful' => [],
                'failed' => []
            ];

            DB::beginTransaction();

            foreach ($dvIds as $dvId) {
                try {
                    $dvRequest = DvRequest::with(['focalPerson', 'program'])
                        ->where('id', $dvId)
                        ->where('qr_status', 'active')
                        ->first();

                    if (!$dvRequest) {
                        $results['failed'][] = [
                            'dv_id' => $dvId,
                            'reason' => 'DV not found or QR not active'
                        ];
                        continue;
                    }

                    if ($dvRequest->status !== 'Regional_Director_received') {
                        $results['failed'][] = [
                            'dv_id' => $dvId,
                            'dv_number' => $dvRequest->dv_number,
                            'reason' => "Invalid status for sign and approve. Current status: {$dvRequest->status}. Expected: Regional_Director_received"
                        ];
                        continue;
                    }

                    $previousStatus = $dvRequest->status;
                    $newStatus = 'Regional_Director_signed_approved';
                    $action = 'Signed and Approved by Regional Director';
                    $approvedAt = now();

                    $dvRequest->update([
                        'status' => $newStatus,
                        'updated_by' => $user->id
                    ]);

                    $dvRequest->refresh();

                    // ✅ Save Regional Director History
                    $this->saveRegionalDirectorHistory(
                        $dvRequest,
                        'signed_approved',
                        $action,
                        [
                            'status_before' => $previousStatus,
                            'status_after' => $newStatus,
                            'approved_by' => $user->id,
                            'approved_at' => $approvedAt->toISOString(),
                            'amount' => $dvRequest->amount,
                            'dv_number' => $dvRequest->dv_number,
                            'processing_type' => 'bulk_sign_approve'
                        ]
                    );

                    $this->logQrAction(
                        $dvRequest->id,
                        $user->id,
                        'Regional_Director',
                        $newStatus,
                        $action
                    );

                    // ✅ CRITICAL FIX: Broadcast individual status update FIRST
                    $this->broadcastStatusUpdate($dvRequest, $previousStatus, $newStatus, 'Regional_Director');

                    // ✅ CRITICAL FIX: Then broadcast to Cashier dashboard with complete data
                    $this->broadcastToCashierDashboard($dvRequest, 'bulk_sign_approve');

                    if ($dvRequest->focalperson_id) {
                        Notification::create([
                            'focalperson_id' => $dvRequest->focalperson_id,
                            'dv_request_id'  => $dvRequest->id,
                            'status'         => $newStatus,
                            'action'         => 'Status Updated via Bulk Sign & Approve',
                            'remarks'        => "DV status updated to {$newStatus} by Regional Director",
                        ]);

                        $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'status-updated', [
                            'dv_request_id' => $dvRequest->id,
                            'status'        => $newStatus,
                            'remarks'       => "Status updated by Regional Director via bulk sign and approve",
                            'timestamp'     => $approvedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        ]);
                    }

                    $results['successful'][] = [
                        'dv_id' => $dvRequest->id,
                        'dv_number' => $dvRequest->dv_number,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus
                    ];

                    \Log::info('DV bulk signed and approved by Regional Director', [
                        'dv_id' => $dvRequest->id,
                        'dv_number' => $dvRequest->dv_number,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'updated_by' => $user->id
                    ]);

                } catch (\Exception $e) {
                    \Log::error('Error processing DV in bulk sign and approve: ' . $e->getMessage(), [
                        'dv_id' => $dvId,
                        'error' => $e->getMessage()
                    ]);

                    $results['failed'][] = [
                        'dv_id' => $dvId,
                        'reason' => 'Processing error: ' . $e->getMessage()
                    ];
                }
            }

            DB::commit();

            // ✅ CRITICAL FIX: Send consolidated bulk completion events AFTER transaction
            if (count($results['successful']) > 0) {
                // Broadcast to Cashier dashboard with ALL successful DVs
                $this->pusher->trigger('cashier-dashboard', 'bulk-sign-approve-completed', [
                    'successful_dvs' => $results['successful'],
                    'successful_count' => count($results['successful']),
                    'failed_count' => count($results['failed']),
                    'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    'message' => count($results['successful']) . ' DVs signed and approved, ready for cashier processing'
                ]);

                // Also send individual "new-dv-available" events for each successful DV
                foreach ($results['successful'] as $successfulDv) {
                    $dv = DvRequest::with(['focalPerson', 'program'])->find($successfulDv['dv_id']);
                    if ($dv) {
                        $this->broadcastToCashierDashboard($dv, 'new_dv_available');
                    }
                }
            }

            // Broadcast to Regional Director dashboard
            $this->pusher->trigger('regional-director-dashboard', 'bulk-sign-approve-completed', [
                'successful_dvs' => $results['successful'],
                'successful_count' => count($results['successful']),
                'failed_count' => count($results['failed']),
                'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            $this->updateDashboardStatistics();
            $this->triggerDashboardUpdate();
        
            return response()->json([
                'success' => true,
                'message' => 'Bulk sign and approve completed',
                'results' => $results,
                'summary' => [
                    'total_processed' => count($dvIds),
                    'successful' => count($results['successful']),
                    'failed' => count($results['failed'])
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Bulk sign and approve error: ' . $e->getMessage(), [
                'dv_ids' => $dvIds,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error during bulk sign and approve: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cashierBulkReceive(Request $request)
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
                    'message' => 'No DVs selected for bulk receive.'
                ], 400);
            }

            DB::beginTransaction();

            // Get all eligible DVs in one query
            $eligibleDVs = DvRequest::with(['focalPerson', 'program'])
                ->whereIn('id', $dvIds)
                ->where('qr_status', 'active')
                ->where('status', 'Regional_Director_signed_approved')
                ->get();

            if ($eligibleDVs->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No eligible DVs found for receiving'
                ], 400);
            }

            $eligibleDvIds = $eligibleDVs->pluck('id')->toArray();
            $receivedAt = now();
            $newStatus = 'Cashier_received';
            $action = 'Request Received by Cashier';

            // Bulk update all DVs in one query
            $updatedCount = DvRequest::whereIn('id', $eligibleDvIds)
                ->update([
                    'status' => $newStatus,
                    'updated_at' => $receivedAt
                ]);

            // Prepare data for bulk operations
            $notificationData = [];
            $qrActionData = [];
            $successfulDVs = [];

            foreach ($eligibleDVs as $dv) {
                // ✅ Save Cashier History using consistent pattern
                $this->saveCashierHistory(
                    $dv,
                    'received',
                    $action,
                    [
                        'status_before' => 'Regional_Director_signed_approved',
                        'status_after' => $newStatus,
                        'received_by' => $user->id,
                        'received_at' => $receivedAt->toISOString(),
                        'amount' => $dv->amount,
                        'dv_number' => $dv->dv_number,
                        'processing_type' => 'bulk_receive'
                    ]
                );
                
                $this->logQrAction(
                $dv->id,
                $user->id,
                'Cashier',
                $newStatus,
                $action
                );

                $qrActionData[] = [
                    'dv_request_id' => $dv->id,
                    'user_id' => $user->id,
                    'user_role' => 'Cashier',
                    'action' => $newStatus,
                    'description' => $action . ' at: ' . $receivedAt->format('Y-m-d H:i:s'),
                    'created_at' => $receivedAt,
                    'updated_at' => $receivedAt
                ];

                if ($dv->focalperson_id) {
                    $notificationData[] = [
                        'focalperson_id' => $dv->focalperson_id,
                        'dv_request_id' => $dv->id,
                        'status' => $newStatus,
                        'action' => 'Status updated to Cashier Received',
                        'remarks' => "DV status updated to {$newStatus}",
                        'created_at' => $receivedAt,
                        'updated_at' => $receivedAt
                    ];
                }

                $successfulDVs[] = [
                    'dv_id' => $dv->id,
                    'dv_number' => $dv->dv_number,
                    'new_status' => $newStatus
                ];

                // Broadcast individual status updates (if needed)
                $this->broadcastStatusUpdate($dv, 'Regional_Director_signed_approved', $newStatus, 'Cashier');
            }

            // Bulk insert notifications
            if (!empty($notificationData)) {
                Notification::insert($notificationData);
            }

            DB::commit();

            // Send single Pusher event for bulk completion
            $this->pusher->trigger('cashier-dashboard', 'bulk-receive-completed', [
                'successful_dvs' => $successfulDVs,
                'successful_count' => count($successfulDVs),
                'failed_count' => count($dvIds) - count($successfulDVs),
                'timestamp' => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            // Update statistics
            $this->updateDashboardStatistics();
             $this->triggerDashboardUpdate();
            \Log::info('Cashier bulk receive completed', [
                'total_selected' => count($dvIds),
                'successful_count' => count($successfulDVs),
                'failed_count' => count($dvIds) - count($successfulDVs),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bulk receive completed successfully',
                'results' => [
                    'successful' => $successfulDVs,
                    'failed' => array_values(array_diff($dvIds, $eligibleDvIds))
                ],
                'summary' => [
                    'total_processed' => count($dvIds),
                    'successful' => count($successfulDVs),
                    'failed' => count($dvIds) - count($successfulDVs)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Cashier bulk receive error: ' . $e->getMessage(), [
                'dv_ids' => $dvIds ?? [],
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error during bulk receive: ' . $e->getMessage()
            ], 500);
        }
    }

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

        // Bulk update all DVs
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
                $newStatus,
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

            // ✅ Send email notification
            $emailSent = $this->sendReleaseEmailNotification($dv, $user);
            if ($emailSent) {
                $notificationResults['email_sent']++;
            } else {
                $notificationResults['email_failed']++;
            }

            // ✅ Send SMS notification with FRESH data
            \Log::info('About to send SMS for DV', [
                'dv_id' => $dv->id,
                'dv_number' => $dv->dv_number,
                'user_sequence' => $dv->user_sequence,
                'focal_person' => $dv->focalPerson ? $dv->focalPerson->fname . ' ' . $dv->focalPerson->lname : 'Unknown',
                'contact_number' => $dv->focalPerson ? $dv->focalPerson->contactnumber : 'N/A',
                'amount' => $dv->amount
            ]);

            $smsSent = $this->sendReleaseSMSNotification($dv, $user);
            if ($smsSent) {
                $notificationResults['sms_sent']++;
            } else {
                $notificationResults['sms_failed']++;
            }
            
            // Broadcast individual status updates
            $this->broadcastStatusUpdate($dv, 'Cashier_received', $newStatus, 'Cashier');
        }

        if (!empty($notificationData)) {
            Notification::insert($notificationData);
        }

        DB::commit();

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
         
        \Log::info('Cashier bulk release completed', [
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
        \Log::error('Cashier bulk release error: ' . $e->getMessage(), [
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
    private function updateDashboardStatistics()
    {
        try {
            // Get updated stats for regional director
            $rdStats = [
                'total_pending' => DvRequest::whereIn('status', ['RANCA_validated', 'Regional_Director_received'])
                    ->where('qr_status', 'active')
                    ->count(),
                'awaiting_receipt' => DvRequest::where('status', 'RANCA_validated')
                    ->where('qr_status', 'active')
                    ->count(),
                'awaiting_approval' => DvRequest::where('status', 'Regional_Director_received')
                    ->where('qr_status', 'active')
                    ->count(),
                'awaiting_sign_approve' => DvRequest::where('status', 'Regional_Director_received')
                    ->where('qr_status', 'active')
                    ->count(),
            ];

            // Get updated stats for cashier
            $cashierStats = [
                'total_pending' => DvRequest::whereIn('status', ['Regional_Director_signed_approved', 'Cashier_received'])
                    ->where('qr_status', 'active')
                    ->count(),
                'awaiting_processing' => DvRequest::where('status', 'Regional_Director_signed_approved')
                    ->where('qr_status', 'active')
                    ->count(),
                'in_processing' => DvRequest::where('status', 'Cashier_received')
                    ->where('qr_status', 'active')
                    ->count(),
                'awaiting_release' => DvRequest::where('status', 'Cashier_received')
                    ->where('qr_status', 'active')
                    ->count(),
            ];

            // Broadcast updated statistics
            $this->pusher->trigger('regional-director-dashboard', 'stats-updated', [
                'stats' => $rdStats,
                'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A')
            ]);

            $this->pusher->trigger('cashier-dashboard', 'stats-updated', [
                'stats' => $cashierStats,
                'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A')
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating dashboard statistics: ' . $e->getMessage());
        }
    }

    public function getCashierStats()
    {
        try {
            $stats = [
                'total_pending' => DvRequest::whereIn('status', ['Regional_Director_signed_approved', 'Cashier_received'])
                    ->where('qr_status', 'active')
                    ->count(),
                'awaiting_processing' => DvRequest::where('status', 'Regional_Director_signed_approved')
                    ->where('qr_status', 'active')
                    ->count(),
                'in_processing' => DvRequest::where('status', 'Cashier_received')
                    ->where('qr_status', 'active')
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching cashier stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics'
            ], 500);
        }
    }

    /**
     * Get real-time statistics for regional director dashboard
     */
    public function getRegionalDirectorStats()
    {
        try {
            $stats = [
                'total_pending' => DvRequest::whereIn('status', ['RANCA_validated', 'Regional_Director_received'])
                    ->where('qr_status', 'active')
                    ->count(),
                'awaiting_receipt' => DvRequest::where('status', 'RANCA_validated')
                    ->where('qr_status', 'active')
                    ->count(),
                'awaiting_approval' => DvRequest::where('status', 'Regional_Director_received')
                    ->where('qr_status', 'active')
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching regional director stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics'
            ], 500);
        }
    }

    /**
     * Get DV details for real-time updates
     */
    public function getDvDetails($id)
    {
        try {
            $dv = DvRequest::with(['program', 'focalPerson'])
                ->where('id', $id)
                ->where('qr_status', 'active')
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'dv' => [
                    'id' => $dv->id,
                    'dv_number' => $dv->dv_number,
                    'reference_number' => $dv->reference_number,
                    'amount' => $dv->amount,
                    'status' => $dv->status,
                    'mode_of_payment' => $dv->mode_of_payment,
                    'particulars' => $dv->particulars,
                    'qr_code_token' => $dv->qr_code_token,
                    'qr_status' => $dv->qr_status,
                    'focal_person' => [
                        'fname' => $dv->focalPerson->fname,
                        'lname' => $dv->focalPerson->lname,
                        'position' => $dv->focalPerson->position,
                        'tin_number' => $dv->focalPerson->TIN_number,
                    ],
                    'program' => [
                        'prcode' => $dv->program->prcode ?? null,
                        'description' => $dv->program->description ?? null,
                    ],
                    'updated_at' => $dv->updated_at->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching DV details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'DV not found'
            ], 404);
        }
    }

    private function broadcastStatusUpdate($dvRequest, $previousStatus, $newStatus, $role)
    {
        $updateData = [
            'dv_id' => $dvRequest->id,
            'dv_number' => $dvRequest->dv_number,
            'new_status' => $newStatus,
            'previous_status' => $previousStatus,
            'amount' => $dvRequest->amount,
            'payee' => $dvRequest->focalPerson->fname . ' ' . $dvRequest->focalPerson->lname,
            'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            'updated_by' => auth()->user()->name,
        ];

        // Send to Regional Director dashboard
        $this->pusher->trigger('regional-director-dashboard', 'dv-status-updated', $updateData);
        
        // Send to Cashier dashboard
        $this->pusher->trigger('cashier-dashboard', 'dv-status-updated', $updateData);

        // Also send removal event if needed
        if (in_array($newStatus, ['Regional_Director_signed_approved', 'Cashier_released'])) {
            // Notify Regional Director dashboard if DV moved out of their queue
            if (in_array($previousStatus, ['RANCA_validated', 'Regional_Director_received'])) {
                $this->pusher->trigger('regional-director-dashboard', 'dv-removed', [
                    'dv_id' => $dvRequest->id,
                    'dv_number' => $dvRequest->dv_number,
                    'reason' => 'forwarded_to_cashier',
                    'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            }
            
            // Notify Cashier dashboard if DV moved out of their queue
            if (in_array($previousStatus, ['Regional_Director_signed_approved', 'Cashier_received']) && $newStatus === 'Cashier_released') {
                $this->pusher->trigger('cashier-dashboard', 'dv-removed', [
                    'dv_id' => $dvRequest->id,
                    'dv_number' => $dvRequest->dv_number,
                    'reason' => 'completed',
                    'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                ]);
            }
        }

        \Log::info('Broadcast status update', [
            'dv_number' => $dvRequest->dv_number,
            'channels_notified' => ['regional-director-dashboard', 'cashier-dashboard'],
            'new_status' => $newStatus,
        ]);
    }

   public function regionalDirectorDashboard()
    {
        $pendingDvs = DvRequest::whereIn('status', ['RANCA_validated', 'Regional_Director_received'])
            ->where('qr_status', 'active')
            ->with(['program', 'focalPerson'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // More efficient stats calculation using the same base query
        $baseQuery = DvRequest::whereIn('status', ['RANCA_validated', 'Regional_Director_received'])
            ->where('qr_status', 'active');

        $stats = [
            'total_pending' => $baseQuery->count(),
            'awaiting_receipt' => (clone $baseQuery)->where('status', 'RANCA_validated')->count(),
            'awaiting_approval' => (clone $baseQuery)->where('status', 'Regional_Director_received')->count(),
        ];

        return view('DOLEQR.regional-director-dashboard', [
            'pendingDvs' => $pendingDvs,
            'stats' => $stats
        ]);
    }

    public function cashierDashboard()
    {
        $pendingDvs = DvRequest::whereIn('status', ['Regional_Director_signed_approved', 'Cashier_received'])
            ->where('qr_status', 'active')
            ->with(['program', 'focalPerson'])
            ->orderBy('created_at', 'desc')
            ->paginate(10); // Changed from get() to paginate(10)

        $baseQuery = DvRequest::whereIn('status', ['Regional_Director_signed_approved', 'Cashier_received'])
            ->where('qr_status', 'active');
        
        $stats = [
            'total_pending' => $baseQuery->count(),
            'awaiting_processing' => (clone $baseQuery)->where('status', 'Regional_Director_signed_approved')->count(),
            'in_processing' => (clone $baseQuery)->where('status', 'Cashier_received')->count(),
        ];

        return view('DOLEQR.cashier-dashboard', [
            'pendingDvs' => $pendingDvs,
            'stats' => $stats
        ]);
    }
    
    public function qrStatusUpdateInterface($token)
    {
        \Log::info('QR Status Update Interface accessed', [
            'token' => $token,
            'authenticated' => auth()->check(),
            'user_roles' => auth()->check() ? auth()->user()->getRoleNames() : 'not_authenticated'
        ]);

        if (!auth()->check()) {
            session(['qr_redirect_token' => $token]);
            
            \Log::info('User not authenticated, redirecting to login', ['token' => $token]);
            
            return redirect()->route('login')
                ->with('info', 'Please log in first to access the DV status update page.');
        }

        $user = auth()->user();
        $requiredRoles = ['Cashier', 'Regional_Director'];
        
        if (!$user->hasAnyRole($requiredRoles)) {
            \Log::info('User lacks required role', [
                'user_roles' => $user->getRoleNames(),
                'required_roles' => $requiredRoles
            ]);
            
            return redirect()->route('login')
                ->with('error', 'You do not have permission to access this page. Please log in with Cashier or Regional Director credentials.');
        }

        $dvRequest = DvRequest::with(['program', 'focalPerson'])->where('qr_code_token', $token)->first();
        
        if (!$dvRequest) {
            \Log::error('DV request not found for token', ['token' => $token]);
            abort(404, 'Invalid QR code or DV request not found');
        }

        \Log::info('QR Status Update Interface loaded successfully', [
            'dv_number' => $dvRequest->dv_number,
            'status' => $dvRequest->status
        ]);

        return view('DOLEQR.qr-status-update', [
            'dvRequest' => $dvRequest,
            'token' => $token
        ]);
    }

    /**
     * Handle QR code status update
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

            \Log::info('QR Update Attempt', [
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
                $dvRequest->update([
                    'status' => $newStatus,
                    'updated_by' => $user->id
                ]);

                $dvRequest->refresh();


                  if ($newStatus === 'Regional_Director_signed_approved') {
                    // ✅ ADD THIS: Broadcast to Cashier when RD approves via QR
                    $this->broadcastToCashierDashboard($dvRequest, 'qr_scan_approval');
                }

                // ✅ Send email and SMS notifications when Cashier releases
                if ($newStatus === 'Cashier_released') {
                    $emailSent = $this->sendReleaseEmailNotification($dvRequest, $user);
                    $smsSent = $this->sendReleaseSMSNotification($dvRequest, $user);
                    
                    \Log::info('Cashier release notifications sent', [
                        'dv_id' => $dvRequest->id,
                        'email_sent' => $emailSent,
                        'sms_sent' => $smsSent
                    ]);
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

                \Log::info('DV status updated via QR code', [
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
            \Log::error('QR Status Update Error: ' . $e->getMessage(), [
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

    private function sendReleaseEmailNotification($dvRequest, $user)
    {
        try {
            $focalPerson = $dvRequest->focalPerson;
            
            if ($focalPerson && $focalPerson->email) {
                \Log::info('Attempting to send release email notification', [
                    'dv_id' => $dvRequest->id,
                    'dv_number' => $dvRequest->dv_number,
                    'recipient_email' => $focalPerson->email,
                    'recipient_name' => $focalPerson->fname . ' ' . $focalPerson->lname,
                    'status' => $dvRequest->status
                ]);
                
                $sendPulseService = new SendPulseServices();
                $emailSent = $sendPulseService->sendReleaseEmail(
                    $focalPerson->email,
                    $focalPerson->fname . ' ' . $focalPerson->lname,
                    $dvRequest
                );
                
                if ($emailSent) {
                    \Log::info('Release email notification sent successfully', [
                        'dv_id' => $dvRequest->id,
                        'dv_number' => $dvRequest->dv_number,
                        'recipient' => $focalPerson->email
                    ]);
                } else {
                    \Log::error('Failed to send release email notification - SendPulse service returned false', [
                        'dv_id' => $dvRequest->id,
                        'dv_number' => $dvRequest->dv_number
                    ]);
                }
                return $emailSent;
            } else {
                \Log::warning('Cannot send release email - focal person or email missing', [
                    'dv_id' => $dvRequest->id,
                    'focal_person_id' => $dvRequest->focalperson_id,
                    'has_focal_person' => !is_null($focalPerson),
                    'has_email' => $focalPerson && !empty($focalPerson->email)
                ]);
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send release email notification: ' . $e->getMessage(), [
                'dv_id' => $dvRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

      private function broadcastToCashierDashboard($dvRequest, $actionType)
{
    try {
        // ✅ RELOAD the DV with ALL relationships and data
        $dvRequest = DvRequest::with(['focalPerson', 'program'])
            ->where('id', $dvRequest->id)
            ->first();
            
        if (!$dvRequest) {
            \Log::error('DV not found for broadcasting', ['dv_id' => $dvRequest->id]);
            return;
        }

        // ✅ Get QR code data if available
        $qrCodeData = null;
        if ($dvRequest->qr_code_data) {
            $qrCodeData = $dvRequest->qr_code_data;
        } else {
            // Generate QR code data if not exists
            $qrCodeData = $this->generateQrCodeData($dvRequest);
        }

        $dvData = [
            'id' => $dvRequest->id,
            'dv_number' => $dvRequest->dv_number,
            'reference_number' => $dvRequest->reference_number,
            'amount' => $dvRequest->amount, // ✅ Real amount
            'status' => $dvRequest->status,
            'mode_of_payment' => $dvRequest->mode_of_payment,
            'particulars' => $dvRequest->particulars,
            'qr_code_token' => $dvRequest->qr_code_token,
            'qr_code_data' => $qrCodeData, // ✅ QR code image data
            'qr_status' => $dvRequest->qr_status,
            'payee_name' => $dvRequest->focalPerson ? ($dvRequest->focalPerson->fname . ' ' . $dvRequest->focalPerson->lname) : 'Unknown Payee',
            'tin_number' => $dvRequest->focalPerson ? $dvRequest->focalPerson->TIN_number : 'N/A',
            'program_code' => $dvRequest->program->prcode ?? 'N/A',
            'program_description' => $dvRequest->program->description ?? 'N/A',
            'action_type' => $actionType,
            'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ];

       $this->pusher->trigger('cashier-dashboard', 'new-dv-available', [
            'dv' => $dvData,
            'message' => 'New DV ready for cashier processing',
            'action_required' => true,
            'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A')
        ]);

        \Log::info('✅ Broadcasted complete DV data to cashier', [
            'dv_id' => $dvRequest->id,
            'dv_number' => $dvRequest->dv_number,
            'amount' => $dvRequest->amount,
            'has_qr_code' => !empty($qrCodeData),
            'action_type' => $actionType
        ]);

    } catch (\Exception $e) {
        \Log::error('Error broadcasting to cashier dashboard: ' . $e->getMessage(), [
            'dv_id' => $dvRequest->id ?? 'unknown',
            'error' => $e->getMessage()
        ]);
    }
}


  protected function sendReleaseSMSNotification($dvRequest, $user)
{
    try {
        $focalPerson = $dvRequest->focalPerson;
        
        // Use 'contactnumber' instead of 'phone'
        if (!$focalPerson || empty($focalPerson->contactnumber)) {
            \Log::warning('Cannot send release SMS: focal person or contact number missing', [ 
                'dv_request_id' => $dvRequest->id,
                'focal_person_id' => $focalPerson->id ?? 'null',
                'has_contactnumber' => !empty($focalPerson->contactnumber)
            ]);
            return false;
        }

        $phoneNumber = $focalPerson->contactnumber;
        
        // ✅ Use user_sequence as requested
        $userSequence = $dvRequest->user_sequence ?? $dvRequest->dv_number ?? 'N/A';
        
        // ✅ Format amount properly
        $formattedAmount = number_format($dvRequest->amount, 2);
        
        // ✅ Release message (not rejection)
        $message = "Hi {$focalPerson->fname}, your DVORS Request #{$userSequence} has been released!";

        Log::info('Sending RELEASE SMS via iProg', [
            'phone' => $phoneNumber,
            'dv_request_id' => $dvRequest->id,
            'user_sequence' => $userSequence,
            'amount' => $formattedAmount,
            'message_length' => strlen($message)
        ]);

        $result = $this->smsService->sendSMS($phoneNumber, $message);
        
        if ($result['success']) {
            \Log::info('RELEASE SMS sent successfully', [
                'dv_request_id' => $dvRequest->id,
                'phone' => $phoneNumber,
                'user_sequence' => $userSequence,
                'message_id' => $result['message_id'] ?? 'N/A'
            ]);
            return true;
        } else {
            \Log::error('Failed to send RELEASE SMS', [
                'dv_request_id' => $dvRequest->id,
                'phone' => $phoneNumber,
                'error' => $result['error'] ?? 'Unknown error'
            ]);
            return false;
        }
        
    } catch (\Exception $e) {
        \Log::error('RELEASE SMS notification failed: ' . $e->getMessage(), [
            'dv_request_id' => $dvRequest->id,
            'exception' => $e->getTraceAsString()
        ]);
        return false;
    }
}
    /**
     * Verify DV endpoint (public access)
     */
    public function verifyDv($reference)
    {
        try {
            $dvRequest = DvRequest::with(['program', 'focalPerson'])
                ->where('reference_number', $reference)
                ->orWhere('dv_number', $reference)
                ->first();

            if (!$dvRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'DV not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'dv_number' => $dvRequest->dv_number,
                    'reference_number' => $dvRequest->reference_number,
                    'payee' => trim(($dvRequest->focalPerson->fname ?? '') . ' ' . ($dvRequest->focalPerson->lname ?? '')),
                    'amount' => $dvRequest->amount,
                    'status' => $dvRequest->status,
                    'status_description' => $this->getStatusDescription($dvRequest->status),
                    'program' => $dvRequest->program->prname ?? 'N/A',
                    'created_at' => $dvRequest->created_at->format('Y-m-d H:i:s'),
                    'qr_code_generated_at' => $dvRequest->qr_code_generated_at?->format('Y-m-d H:i:s'),
                    'is_qr_active' => $dvRequest->qr_code_token && (!$dvRequest->qr_code_generated_at || $dvRequest->qr_code_generated_at->diffInDays(now()) <= 30)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('DV Verification Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error verifying DV'
            ], 500);
        }
    }

    /**
     * Get human-readable status description
     */
    private function getNextAction($currentStatus, $role)
    {
        $actions = [
            'RANCA_validated' => 'Next: Regional Director to receive document',
            'Regional_Director_received' => 'Next: Regional Director to sign and approve',
            'Regional_Director_signed_approved' => 'Next: Forward to Cashier for processing',
            'Cashier_received' => 'Next: Cashier to complete transaction',
            'Cashier_released' => 'Transaction fully completed'
        ];

        return $actions[$currentStatus] ?? 'Process completed';
    }

    /**
     * Generate QR code for any DV (utility method)
     */
    public function generateQrCode($dvRequestId)
    {
        try {
            $dvRequest = DvRequest::find($dvRequestId);
            
            if (!$dvRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'DV request not found'
                ], 404);
            }

            // Check if we already have QR code data stored
            if ($dvRequest->qr_code_data) {
                return response()->json([
                    'success' => true,
                    'qr_code' => $dvRequest->qr_code_data,
                    'dv_number' => $dvRequest->dv_number,
                    'token' => $dvRequest->qr_code_token,
                    'source' => 'database'
                ]);
            }

            // If no QR code data exists, generate a new one
            $token = $dvRequest->qr_code_token;
            
            if (!$token) {
                $token = hash('sha256', $dvRequest->id . $dvRequest->dv_number . now()->timestamp . uniqid());
                $dvRequest->update([
                    'qr_code_token' => $token,
                    'qr_code_generated_at' => now(),
                ]);
            }

            $qrData = json_encode([
                'dv_id' => $dvRequest->id,
                'dv_number' => $dvRequest->dv_number,
                'token' => $token,
                'action_url' => route('dv.qr.update-status', ['token' => $token]),
                'timestamp' => now()->timestamp,
                'type' => 'dv_status_update'
            ]);

            $qrCodeImage = QrCode::format('png')
                ->size(200)
                ->margin(2)
                ->errorCorrection('H')
                ->generate($qrData);

            $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCodeImage);

            // Save the generated QR code to the database for future use
            $dvRequest->update([
                'qr_code_data' => $base64QrCode
            ]);

            return response()->json([
                'success' => true,
                'qr_code' => $base64QrCode,
                'dv_number' => $dvRequest->dv_number,
                'token' => $token,
                'source' => 'generated'
            ]);

        } catch (\Exception $e) {
            \Log::error('QR Code Generation Error in DOLEQRController: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error generating QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get existing QR code from database
     */
    public function getQrCode($dvRequestId)
    {
        try {
            $dvRequest = DvRequest::find($dvRequestId);
            
            if (!$dvRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'DV request not found'
                ], 404);
            }

            if (!$dvRequest->qr_code_data) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code not found for this DV'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'qr_code' => $dvRequest->qr_code_data,
                'dv_number' => $dvRequest->dv_number,
                'token' => $dvRequest->qr_code_token
            ]);

        } catch (\Exception $e) {
            \Log::error('QR Code Fetch Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching QR code'
            ], 500);
        }
    }

    /**
     * Log QR code actions
     */
    private function logQrAction($dvRequestId, $userId, $role, $status, $remarks = null)
    {
        DvRequestLog::create([
            'dv_request_id' => $dvRequestId,
            'accountingunit_id' => $userId,
            'action' => $role,
            'status' => $status,
            'remarks' => $remarks,
        ]);
    }

    /**
     * Get status description for verification
     */
    private function getStatusDescription($status)
    {
        $descriptions = [
            'RANCA_validated' => 'Validated by Accounting III - Ready for Regional Director',
            'Regional_Director_received' => 'Received by Regional Director',
            'Regional_Director_signed_approved' => 'Signed and Approved by Regional Director - Ready for Cashier',
            'Cashier_received' => 'Received by Cashier - In Processing',
            'Cashier_released' => 'Transaction Completed by Cashier'
        ];

        return $descriptions[$status] ?? $status;
    }

    /**
     * Get required status for user role
     */
    private function getRequiredStatus($role)
    {
        $requirements = [
            'Regional_Director' => ['RANCA_validated', 'Regional_Director_received'],
            'Cashier' => ['Regional_Director_signed_approved', 'Cashier_received']
        ];

        return $requirements[$role] ?? [];
    }

     protected function saveRegionalDirectorHistory($dvRequest, $step, $remarks = null, $data = [])
    {
        try {
            RegionalDirectorApprovedHistory::create([
                'dv_request_id'   => $dvRequest->id,
                'approved_by'     => auth()->id(),
                'approval_step'   => $step,
                'status_before'   => $data['status_before'] ?? $dvRequest->getOriginal('status'),
                'status_after'    => $data['status_after'] ?? $dvRequest->status,
                'approval_data'   => $data,
                'remarks'         => $remarks,
                'approved_at'     => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to save Regional Director approval history', [
                'dv_request_id' => $dvRequest->id ?? null,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * ✅ NEW: Save Cashier History
     */
    protected function saveCashierHistory($dvRequest, $step, $remarks = null, $data = [])
    {
        try {
            CashierReleasedHistory::create([
                'dv_request_id' => $dvRequest->id,
                'released_by'   => auth()->id(),
                'release_step'  => $step,
                'status_before' => $data['status_before'] ?? $dvRequest->getOriginal('status'),
                'status_after'  => $data['status_after'] ?? $dvRequest->status,
                'release_data'  => $data,
                'remarks'       => $remarks,
                'released_at'   => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to save Cashier release history', [
                'dv_request_id' => $dvRequest->id ?? null,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * ✅ NEW: Regional Director History View
     */
    public function regionalDirectorHistory(Request $request)
    {
        $query = RegionalDirectorApprovedHistory::with([
            'dvRequest.program',
            'dvRequest.focalPerson',
            'dvRequest.respocenter',
            'regionalDirector'
        ])
        ->where('approved_by', auth()->id());

        // Search by DV Number
        if ($request->has('search') && $request->search) {
            $query->whereHas('dvRequest', function($q) use ($request) {
                $q->where('dv_number', 'like', '%' . $request->search . '%')
                  ->orWhere('accounting_control_number', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by Program
        if ($request->has('program') && $request->program) {
            $query->whereHas('dvRequest.program', function($q) use ($request) {
                $q->where('prcode', $request->program);
            });
        }

        // Filter by Approval Step
        if ($request->has('step') && $request->step) {
            $query->where('approval_step', $request->step);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('approved_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('approved_at', '<=', $request->date_to);
        }

        $approvalHistory = $query->orderBy('approved_at', 'desc')
                                ->paginate(20)
                                ->appends($request->all());

        return view('DOLEQR.regional-director-approvedhistory', compact('approvalHistory'));
    }

    /**
     * ✅ NEW: Cashier History View
     */
    public function cashierHistory(Request $request)
    {
        $query = CashierReleasedHistory::with([
            'dvRequest.program',
            'dvRequest.focalPerson',
            'dvRequest.respocenter',
            'cashier'
        ])
        ->where('released_by', auth()->id());

        // Search by DV Number
        if ($request->has('search') && $request->search) {
            $query->whereHas('dvRequest', function($q) use ($request) {
                $q->where('dv_number', 'like', '%' . $request->search . '%')
                  ->orWhere('accounting_control_number', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by Program
        if ($request->has('program') && $request->program) {
            $query->whereHas('dvRequest.program', function($q) use ($request) {
                $q->where('prcode', $request->program);
            });
        }

        // Filter by Release Step
        if ($request->has('step') && $request->step) {
            $query->where('release_step', $request->step);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('released_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('released_at', '<=', $request->date_to);
            }

        $releaseHistory = $query->orderBy('released_at', 'desc')
                                ->paginate(20)
                                ->appends($request->all());

        return view('DOLEQR.cashier-releasedhistory', compact('releaseHistory'));
    }
public function fetchDVs(Request $request)
{
    try {
        $search = $request->get('search', '');
        
        $dvs = DvRequest::with(['focalPerson', 'program'])
            ->whereIn('status', ['Regional_Director_signed_approved', 'Cashier_received'])
            ->where('qr_status', 'active')
            ->when($search, function($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('dv_number', 'like', "%{$search}%")
                      ->orWhere('particulars', 'like', "%{$search}%")
                      ->orWhere('reference_number', 'like', "%{$search}%")
                      ->orWhereHas('focalPerson', function($q) use ($search) {
                          $q->where('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%");
                      })
                      ->orWhereHas('program', function($q) use ($search) {
                          $q->where('prcode', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function($dv) {
                return [
                    'id' => $dv->id,
                    'dv_number' => $dv->dv_number,
                    'reference_number' => $dv->reference_number,
                    'amount' => $dv->amount,
                    'particulars' => $dv->particulars,
                    'status' => $dv->status,
                    'updated_at' => $dv->updated_at->toISOString(),
                    'qr_code_token' => $dv->qr_code_token,
                    'qr_code_data' => $dv->qr_code_data, // Make sure this is included
                    'focal_person' => $dv->focalPerson ? [
                        'fname' => $dv->focalPerson->fname,
                        'lname' => $dv->focalPerson->lname,
                        'contactnumber' => $dv->focalPerson->contactnumber,
                        'TIN_number' => $dv->focalPerson->TIN_number
                    ] : null,
                    'program' => $dv->program ? [
                        'prcode' => $dv->program->prcode,
                        'description' => $dv->program->description
                    ] : null
                ];
            });

        return response()->json($dvs);

    } catch (\Exception $e) {
        \Log::error('Error fetching DVs for cashier: ' . $e->getMessage());
        
        return response()->json([
            'error' => 'Error fetching DVs: ' . $e->getMessage()
        ], 500);
    }
}
public function fetchRegionalDirectorDVs(Request $request)
{
    try {
        $search = $request->get('search', '');
        
        $dvs = DvRequest::with(['focalPerson', 'program'])
            ->whereIn('status', ['RANCA_validated', 'Regional_Director_received'])
            ->where('qr_status', 'active')
            ->when($search, function($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('dv_number', 'like', "%{$search}%")
                      ->orWhere('particulars', 'like', "%{$search}%")
                      ->orWhere('reference_number', 'like', "%{$search}%")
                      ->orWhereHas('focalPerson', function($q) use ($search) {
                          $q->where('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%");
                      })
                      ->orWhereHas('program', function($q) use ($search) {
                          $q->where('prcode', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function($dv) {
                return [
                    'id' => $dv->id,
                    'dv_number' => $dv->dv_number,
                    'reference_number' => $dv->reference_number,
                    'amount' => $dv->amount,
                    'particulars' => $dv->particulars,
                    'status' => $dv->status,
                    'updated_at' => $dv->updated_at->toISOString(),
                    'qr_code_token' => $dv->qr_code_token,
                    'qr_code_data' => $dv->qr_code_data, // Make sure this is included
                    'focal_person' => $dv->focalPerson ? [
                        'fname' => $dv->focalPerson->fname,
                        'lname' => $dv->focalPerson->lname,
                        'contactnumber' => $dv->focalPerson->contactnumber,
                        'TIN_number' => $dv->focalPerson->TIN_number
                    ] : null,
                    'program' => $dv->program ? [
                        'prcode' => $dv->program->prcode,
                        'description' => $dv->program->description
                    ] : null
                ];
            });

        return response()->json($dvs);

    } catch (\Exception $e) {
        \Log::error('Error fetching DVs for regional director: ' . $e->getMessage());
        
        return response()->json([
            'error' => 'Error fetching DVs: ' . $e->getMessage()
        ], 500);
    }
}
}