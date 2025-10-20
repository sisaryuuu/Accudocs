<?php

namespace App\Http\Controllers\QRCode;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Notification;
use App\Services\PusherService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DOLEQRController extends Controller
{
    protected PusherService $pusher;

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
                'total_pending' => DvRequest::whereIn('status', ['RANCA_validated', 'RD_received'])
                    ->where('qr_status', 'active')
                    ->count(),
                'awaiting_receipt' => DvRequest::where('status', 'RANCA_validated')
                    ->where('qr_status', 'active')
                    ->count(),
                'awaiting_approval' => DvRequest::where('status', 'RD_received')
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

    /**
     * Trigger real-time updates when DV status changes
     */
    public function triggerStatusUpdate($dvId, $newStatus, $remarks = '')
    {
        try {
            $dv = DvRequest::findOrFail($dvId);

            // Trigger for both cashier and regional director dashboards
            $this->pusher->trigger('cashier-dashboard', 'dv-status-updated', [
                'dv_id' => $dv->id,
                'dv_number' => $dv->dv_number,
                'new_status' => $newStatus,
                'previous_status' => $dv->status,
                'remarks' => $remarks,
                'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            $this->pusher->trigger('regional-director-dashboard', 'dv-status-updated', [
                'dv_id' => $dv->id,
                'dv_number' => $dv->dv_number,
                'new_status' => $newStatus,
                'previous_status' => $dv->status,
                'remarks' => $remarks,
                'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ]);

            // Update the DV status
            $dv->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated and real-time notifications sent'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error triggering status update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating status'
            ], 500);
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
            if (in_array($previousStatus, ['RANCA_validated', 'RD_received'])) {
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

    /**
     * Trigger when new DV is added to queue
     */
    public function triggerNewDvAdded($dvId)
    {
        try {
            $dv = DvRequest::with(['program', 'focalPerson'])
                ->where('id', $dvId)
                ->where('qr_status', 'active')
                ->firstOrFail();

            $data = [
                'dv' => [
                    'id' => $dv->id,
                    'dv_number' => $dv->dv_number,
                    'status' => $dv->status,
                    'amount' => $dv->amount,
                    'focal_person' => [
                        'fname' => $dv->focalPerson->fname,
                        'lname' => $dv->focalPerson->lname,
                    ]
                ],
                'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ];

            // Route to correct dashboard based on status
            if (in_array($dv->status, ['Regional_Director_signed_approved', 'Cashier_received'])) {
                $this->pusher->trigger('cashier-dashboard', 'new-dv-added', $data);
            }

            if (in_array($dv->status, ['RANCA_validated', 'RD_received'])) {
                $this->pusher->trigger('regional-director-dashboard', 'new-dv-added', $data);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            \Log::error('Error triggering new DV notification: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Trigger when DV is removed from queue
     */
    public function triggerDvRemoved($dvId, $reason = 'completed')
    {
        try {
            $dv = DvRequest::findOrFail($dvId);

            $data = [
                'dv_id' => $dv->id,
                'dv_number' => $dv->dv_number,
                'reason' => $reason,
                'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            ];

            // Notify both dashboards
            $this->pusher->trigger('cashier-dashboard', 'dv-removed', $data);
            $this->pusher->trigger('regional-director-dashboard', 'dv-removed', $data);

            return response()->json([
                'success' => true,
                'message' => 'DV removal notification sent'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error triggering DV removal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sending removal notification'
            ], 500);
        }
    }

/**
 * Get today's transactions for cashier
 */
public function getTodaysTransactions()
{
    $transactions = DvRequest::whereIn('status', ['Cashier_received', 'Cashier_released'])
        ->whereDate('updated_at', today())
        ->select('dv_number', 'amount', 'status', 'updated_at')
        ->orderBy('updated_at', 'desc')
        ->get();

    return response()->json($transactions);
}

    public function regionalDirectorDashboard()
    {
        $pendingDvs = DvRequest::whereIn('status', ['RANCA_validated', 'RD_received'])
            ->where('qr_status', 'active')
            ->with(['program', 'focalPerson'])
            ->orderBy('created_at', 'desc')
            ->get();
     
        $stats = [
            'total_pending' => $pendingDvs->count(),
            'awaiting_receipt' => DvRequest::where('status', 'RANCA_validated')->where('qr_status', 'active')->count(),
            'awaiting_approval' => DvRequest::where('status', 'RD_received')->where('qr_status', 'active')->count(),
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
    // DEBUG: Log the access attempt
    \Log::info('QR Status Update Interface accessed', [
        'token' => $token,
        'authenticated' => auth()->check(),
        'user_roles' => auth()->check() ? auth()->user()->getRoleNames() : 'not_authenticated'
    ]);

    // Check if user is authenticated
    if (!auth()->check()) {
        // Store the token in session and redirect to login
        session(['qr_redirect_token' => $token]);
        
        \Log::info('User not authenticated, redirecting to login', ['token' => $token]);
        
        return redirect()->route('login')
            ->with('info', 'Please log in first to access the DV status update page.');
    }

    // Check if user has the right role
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

            if ($user && $user->hasRole('Regional_Director')) {
                if ($dvRequest->status === 'RANCA_validated') {
                    $newStatus = 'RD_received';
                    $action = 'Received by Regional Director via QR Scan';
                    $role = 'Regional_Director';
                } elseif ($dvRequest->status === 'RD_received') {
                    $newStatus = 'Regional_Director_signed_approved';
                    $action = 'Signed and Approved by Regional Director via QR Scan';
                    $role = 'Regional_Director';
                }
            } elseif ($user && $user->hasRole('Cashier')) {
                if ($dvRequest->status === 'Regional_Director_signed_approved') {
                    $newStatus = 'Cashier_received';
                    $action = 'Received for Processing by Cashier via QR Scan';
                    $role = 'Cashier';
                } elseif ($dvRequest->status === 'Cashier_received') {
                    $newStatus = 'Cashier_released';
                    $action = 'Transaction Released by Cashier via QR Scan';
                    $role = 'Cashier';
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
                // Update DV status
                $dvRequest->update([
                    'status' => $newStatus,
                    'updated_by' => $user->id
                ]);

                // Reload to get updated data
                $dvRequest->refresh();

                // Log the action
                $this->logQrAction(
                    $dvRequest->id,
                    $user->id,
                    $role,
                    $newStatus,
                    $action . ' - Scanned at: ' . now()->format('Y-m-d H:i:s')
                );

                // CRITICAL: Send real-time notifications to BOTH dashboards
                $this->broadcastStatusUpdate($dvRequest, $previousStatus, $newStatus, $role);

                // Notify focal person
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
                        'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    ]);
                }

                \Log::info('DV status updated via QR code', [
                    'dv_id' => $dvRequest->id,
                    'dv_number' => $dvRequest->dv_number,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'updated_by' => $user->id,
                    'user_role' => $role,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Status updated successfully by {$role}",
                    'new_status' => $newStatus,
                    'dv_number' => $dvRequest->dv_number,
                    'updated_by' => $user->name,
                    'role' => $role,
                    'next_action' => $this->getNextAction($newStatus, $role)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "No action available for current status: {$dvRequest->status}",
                    'current_status' => $dvRequest->status,
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
    /**
     * Get next action description based on current status
     */
     private function getNextAction($currentStatus, $role)
    {
        $actions = [
            'RD_received' => 'Next: Regional Director to sign and approve',
            'Regional_Director_signed_approved' => 'Next: Forward to Cashier for processing',
            'Cashier_received' => 'Next: Cashier to complete transaction',
            'Cashier_released' => 'Transaction fully completed'
        ];

        return $actions[$currentStatus] ?? 'Process completed';
    }
    /**
     * Get required status for role
     */
    private function getRequiredStatus($role)
    {
        $requirements = [
            'Regional_Director' => ['RANCA_validated', 'RD_received'],
            'Cashier' => ['Regional_Director_signed_approved', 'Cashier_received']
        ];

        return $requirements[$role] ?? [];
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
                    'payee' => trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? '')),
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
    private function getStatusDescription($status)
    {
        $descriptions = [
            'Outgoing_finalized' => 'Finalized by Outgoing - QR Generated',
            'RANCA_validated' => 'Validated by Accounting III - QR Activated for Scanning',
            'RD_received' => 'Received by Regional Director',
            'Regional_Director_signed_approved' => 'Signed and Approved by Regional Director',
            'Cashier_received' => 'Received and Processed by Cashier',
            'Cashier_released' => 'Transaction Released by Cashier'
        ];

        return $descriptions[$status] ?? $status;
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

            $token = $dvRequest->qr_code_token;
            
            if (!$token) {
                // Generate new token if doesn't exist
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

            return response()->json([
                'success' => true,
                'qr_code' => $base64QrCode,
                'dv_number' => $dvRequest->dv_number,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            \Log::error('QR Code Generation Error in DOLEQRController: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error generating QR code'
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
}