<?php

namespace App\Http\Controllers\QRCode;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Notification;
use App\Models\RegionalDirectorApprovedHistory;
use App\Models\CashierReleasedHistory;
use App\Services\PusherService;
use App\Services\SendPulseServices;
use App\Traits\TriggersDashboardUpdate; 
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;

class DOLEQRController extends Controller
{
    protected PusherService $pusher;
    use TriggersDashboardUpdate;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
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

    /**
     * Bulk receive DVs for Regional Director
     */
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
                        $action . ' - Bulk processed',
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
                        $action . ' - Bulk processed at: ' . $receivedAt->format('Y-m-d H:i:s')
                    );

                    $this->broadcastStatusUpdate($dvRequest, $previousStatus, $newStatus, 'Regional_Director');

                    if ($dvRequest->focalperson_id) {
                        Notification::create([
                            'focalperson_id' => $dvRequest->focalperson_id,
                            'dv_request_id'  => $dvRequest->id,
                            'status'         => $newStatus,
                            'action'         => 'Status Updated via Bulk Receive',
                            'remarks'        => "DV status updated to {$newStatus} by Regional Director via bulk receive",
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

    /**
 * Bulk sign and approve DVs for Regional Director
 */
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
                    $action = 'Signed and Approved by Regional Director - Bulk processed';
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
                        $action . ' at: ' . $approvedAt->format('Y-m-d H:i:s')
                    );

                    $this->broadcastStatusUpdate($dvRequest, $previousStatus, $newStatus, 'Regional_Director');

                    if ($dvRequest->focalperson_id) {
                        Notification::create([
                            'focalperson_id' => $dvRequest->focalperson_id,
                            'dv_request_id'  => $dvRequest->id,
                            'status'         => $newStatus,
                            'action'         => 'Status Updated via Bulk Sign & Approve',
                            'remarks'        => "DV status updated to {$newStatus} by Regional Director via bulk sign and approve",
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

            $this->pusher->trigger('regional-director-dashboard', 'bulk-sign-approve-completed', [
                'successful_dvs' => $results['successful'],
                'successful_count' => count($results['successful']),
                'failed_count' => count($results['failed']),
                'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            $this->updateDashboardStatistics();

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



    /**
     * Bulk receive DVs for Cashier
     */
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

                    if ($dvRequest->status !== 'Regional_Director_signed_approved') {
                        $results['failed'][] = [
                            'dv_id' => $dvId,
                            'dv_number' => $dvRequest->dv_number,
                            'reason' => "Invalid status for receiving. Current status: {$dvRequest->status}"
                        ];
                        continue;
                    }

                    $previousStatus = $dvRequest->status;
                    $newStatus = 'Cashier_received';
                    $action = 'Request Received by Cashier';
                    $receivedAt = now();

                    $dvRequest->update([
                        'status' => $newStatus,
                        'updated_by' => $user->id
                    ]);

                    $dvRequest->refresh();

                         if ($newStatus === 'Cashier_released') {
                    $this->sendReleaseEmailNotification($dvRequest, $user);
                }

                    // ✅ Save Cashier History
                    $this->saveCashierHistory(
                        $dvRequest,
                        'received',
                        $action . ' - Bulk processed',
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
                        'Cashier',
                        $newStatus,
                        $action . ' - Bulk processed at: ' . $receivedAt->format('Y-m-d H:i:s')
                    );

                    $this->broadcastStatusUpdate($dvRequest, $previousStatus, $newStatus, 'Cashier');

                    if ($dvRequest->focalperson_id) {
                        Notification::create([
                            'focalperson_id' => $dvRequest->focalperson_id,
                            'dv_request_id'  => $dvRequest->id,
                            'status'         => $newStatus,
                            'action'         => 'Status Updated via Bulk Receive',
                            'remarks'        => "DV status updated to {$newStatus} by Cashier via bulk receive",
                        ]);

                        $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'status-updated', [
                            'dv_request_id' => $dvRequest->id,
                            'status'        => $newStatus,
                            'remarks'       => "Status updated by Cashier via bulk receive",
                            'timestamp'     => $receivedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        ]);
                    }

                    $results['successful'][] = [
                        'dv_id' => $dvRequest->id,
                        'dv_number' => $dvRequest->dv_number,
                        'new_status' => $newStatus
                    ];

                    \Log::info('DV bulk received by Cashier', [
                        'dv_id' => $dvRequest->id,
                        'dv_number' => $dvRequest->dv_number,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'updated_by' => $user->id
                    ]);

                } catch (\Exception $e) {
                    \Log::error('Error processing DV in cashier bulk receive: ' . $e->getMessage(), [
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

            $this->pusher->trigger('cashier-dashboard', 'bulk-receive-completed', [
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
            
            \Log::error('Cashier bulk receive error: ' . $e->getMessage(), [
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

        /**
     * Bulk release DVs for Cashier
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

                    if ($dvRequest->status !== 'Cashier_received') {
                        $results['failed'][] = [
                            'dv_id' => $dvId,
                            'dv_number' => $dvRequest->dv_number,
                            'reason' => "Invalid status for release. Current status: {$dvRequest->status}. Expected: Cashier_received"
                        ];
                        continue;
                    }

                    $previousStatus = $dvRequest->status;
                    $newStatus = 'Cashier_released';
                    $action = 'Transaction Released by Cashier - Bulk processed';
                    $releasedAt = now();

                    $dvRequest->update([
                        'status' => $newStatus,
                        'updated_by' => $user->id
                    ]);

                    $dvRequest->refresh();

                    // ✅ Send email notification for release
                    $this->sendReleaseEmailNotification($dvRequest, $user);

                    // ✅ Save Cashier History
                    $this->saveCashierHistory(
                        $dvRequest,
                        'released',
                        $action,
                        [
                            'status_before' => $previousStatus,
                            'status_after' => $newStatus,
                            'released_by' => $user->id,
                            'released_at' => $releasedAt->toISOString(),
                            'amount' => $dvRequest->amount,
                            'dv_number' => $dvRequest->dv_number,
                            'processing_type' => 'bulk_release'
                        ]
                    );

                    $this->logQrAction(
                        $dvRequest->id,
                        $user->id,
                        'Cashier',
                        $newStatus,
                        $action . ' at: ' . $releasedAt->format('Y-m-d H:i:s')
                    );

                    $this->broadcastStatusUpdate($dvRequest, $previousStatus, $newStatus, 'Cashier');

                    if ($dvRequest->focalperson_id) {
                        Notification::create([
                            'focalperson_id' => $dvRequest->focalperson_id,
                            'dv_request_id'  => $dvRequest->id,
                            'status'         => $newStatus,
                            'action'         => 'Status Updated via Bulk Release',
                            'remarks'        => "DV status updated to {$newStatus} by Cashier via bulk release",
                        ]);

                        $this->pusher->trigger('dv-requests-' . $dvRequest->focalperson_id, 'status-updated', [
                            'dv_request_id' => $dvRequest->id,
                            'status'        => $newStatus,
                            'remarks'       => "Status updated by Cashier via bulk release",
                            'timestamp'     => $releasedAt->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        ]);
                    }

                    $results['successful'][] = [
                        'dv_id' => $dvRequest->id,
                        'dv_number' => $dvRequest->dv_number,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus
                    ];

                    \Log::info('DV bulk released by Cashier', [
                        'dv_id' => $dvRequest->id,
                        'dv_number' => $dvRequest->dv_number,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'updated_by' => $user->id
                    ]);

                } catch (\Exception $e) {
                    \Log::error('Error processing DV in cashier bulk release: ' . $e->getMessage(), [
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

            $this->pusher->trigger('cashier-dashboard', 'bulk-release-completed', [
                'successful_dvs' => $results['successful'],
                'successful_count' => count($results['successful']),
                'failed_count' => count($results['failed']),
                'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            $this->updateDashboardStatistics();

            return response()->json([
                'success' => true,
                'message' => 'Bulk release completed',
                'results' => $results,
                'summary' => [
                    'total_processed' => count($dvIds),
                    'successful' => count($results['successful']),
                    'failed' => count($results['failed'])
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Cashier bulk release error: ' . $e->getMessage(), [
                'dv_ids' => $dvIds,
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
     * Update dashboard statistics after bulk operations
     */
   /**
 * Update dashboard statistics after bulk operations
 */
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
            ->get();
     
        $stats = [
            'total_pending' => $pendingDvs->count(),
            'awaiting_receipt' => DvRequest::where('status', 'RANCA_validated')->where('qr_status', 'active')->count(),
            'awaiting_approval' => DvRequest::where('status', 'Regional_Director_received')->where('qr_status', 'active')->count(),
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
            ->get();

        $stats = [
            'total_pending' => $pendingDvs->count(),
            'awaiting_processing' => DvRequest::where('status', 'Regional_Director_signed_approved')->where('qr_status', 'active')->count(),
            'in_processing' => DvRequest::where('status', 'Cashier_received')->where('qr_status', 'active')->count(),
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
                    $action = 'Received by Regional Director via QR Scan';
                    $role = 'Regional_Director';
                    $step = 'received';
                } elseif ($dvRequest->status === 'Regional_Director_received') {
                    $newStatus = 'Regional_Director_signed_approved';
                    $action = 'Signed and Approved by Regional Director via QR Scan';
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
                    $action = 'Received for Processing by Cashier via QR Scan';
                    $role = 'Cashier';
                    $step = 'received';
                } elseif ($dvRequest->status === 'Cashier_received') {
                    $newStatus = 'Cashier_released';
                    $action = 'Transaction Released by Cashier via QR Scan';
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

                  if ($newStatus === 'Cashier_released') {
                $this->sendReleaseEmailNotification($dvRequest, $user);
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
        } else {
            \Log::warning('Cannot send release email - focal person or email missing', [
                'dv_id' => $dvRequest->id,
                'focal_person_id' => $dvRequest->focalperson_id,
                'has_focal_person' => !is_null($focalPerson),
                'has_email' => $focalPerson && !empty($focalPerson->email)
            ]);
        }
    } catch (\Exception $e) {
        \Log::error('Failed to send release email notification: ' . $e->getMessage(), [
            'dv_id' => $dvRequest->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
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
}
