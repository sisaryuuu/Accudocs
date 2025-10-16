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
     * Web interface for QR code status update
     */
    public function qrStatusUpdateInterface($token)
    {
        $dvRequest = DvRequest::with(['program', 'focalPerson'])->where('qr_code_token', $token)->first();
        
        if (!$dvRequest) {
            abort(404, 'Invalid QR code or DV request not found');
        }

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
            // Find the DV request by token
            $dvRequest = DvRequest::with(['focalPerson'])->where('qr_code_token', $token)->first();
            
            if (!$dvRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code or DV request not found'
                ], 404);
            }

            // Check if QR code is activated (by Accounting III)
            if ($dvRequest->qr_status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code not yet activated. Please wait for Accounting III validation.',
                    'current_status' => $dvRequest->status,
                    'qr_status' => $dvRequest->qr_status
                ], 422);
            }

            // Check if QR code is still valid
            if ($dvRequest->qr_code_generated_at && $dvRequest->qr_code_generated_at->diffInDays(now()) > 30) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code has expired'
                ], 410);
            }

            // Check user role and update status accordingly
            $user = auth()->user();
            $newStatus = null;
            $action = '';
            $role = '';

            if ($user && $user->hasRole('regional_director')) {
                // Regional Director actions - starting from RANCA_validated
                if ($dvRequest->status === 'RANCA_validated') {
                    $newStatus = 'RD_received';
                    $action = 'Received by Regional Director via QR Scan';
                    $role = 'Regional Director';
                } elseif ($dvRequest->status === 'RD_received') {
                    $newStatus = 'RD_signed_approved';
                    $action = 'Signed and Approved by Regional Director via QR Scan';
                    $role = 'Regional Director';
                }
            } elseif ($user && $user->hasRole('cashier')) {
                // Cashier actions - starting from RD_signed_approved
                if ($dvRequest->status === 'RD_signed_approved') {
                    $newStatus = 'Cashier_processing';
                    $action = 'Received for Processing by Cashier via QR Scan';
                    $role = 'Cashier';
                } elseif ($dvRequest->status === 'Cashier_processing') {
                    $newStatus = 'Cashier_completed';
                    $action = 'Transaction Completed by Cashier via QR Scan';
                    $role = 'Cashier';
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in with appropriate role to update status',
                    'login_required' => true,
                    'required_roles' => ['regional_director', 'cashier'],
                    'dv_info' => [
                        'dv_number' => $dvRequest->dv_number,
                        'current_status' => $dvRequest->status,
                        'payee' => $dvRequest->payee_name,
                        'amount' => $dvRequest->amount,
                        'program' => $dvRequest->program->prname ?? 'N/A',
                        'qr_status' => $dvRequest->qr_status
                    ]
                ]);
            }

            if ($newStatus) {
                // Update DV status
                $dvRequest->update([
                    'status' => $newStatus,
                    'updated_by' => $user->id
                ]);

                // Log the action
                $this->logQrAction(
                    $dvRequest->id,
                    $user->id,
                    $role,
                    $newStatus,
                    $action . ' - Scanned at: ' . now()->format('Y-m-d H:i:s')
                );

                // Notify focal person
                if ($dvRequest->focalperson_id) {
                    Notification::create([
                        'focalperson_id' => $dvRequest->focalperson_id,
                        'dv_request_id'  => $dvRequest->id,
                        'status'         => $newStatus,
                        'action'         => 'Status Updated via QR',
                        'remarks'        => "DV status updated to {$newStatus} by {$role} via QR code scan",
                    ]);

                    // Real-time notification
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
                    'previous_status' => $dvRequest->getOriginal('status'),
                    'new_status' => $newStatus,
                    'updated_by' => $user->id,
                    'user_role' => $role,
                    'via_qr' => true
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
                    'required_status' => $this->getRequiredStatus($role)
                ], 422);
            }

        } catch (\Exception $e) {
            \Log::error('QR Status Update Error: ' . $e->getMessage(), [
                'token' => $token,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
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
            'RD_signed_approved' => 'Next: Forward to Cashier for processing',
            'Cashier_processing' => 'Next: Cashier to complete transaction',
            'Cashier_completed' => 'Transaction fully completed'
        ];

        return $actions[$currentStatus] ?? 'Process completed';
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
                    'payee' => $dvRequest->payee_name,
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
            'RD_signed_approved' => 'Signed and Approved by Regional Director',
            'Cashier_received' => 'Received and Processed by Cashier',
            'Cashier_completed' => 'Transaction Completed by Cashier'
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
            'action' => 'QR_Scan_' . $role,
            'status' => $status,
            'remarks' => $remarks,
        ]);
    }
}