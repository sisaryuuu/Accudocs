<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update DV Status - DOLE XI</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container { 
            max-width: 500px; 
            width: 100%;
            margin: 20px; 
            padding: 30px; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }
        .header h2 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .header h3 {
            color: #007bff;
            margin-top: 0;
        }
        .dv-info { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 25px; 
            border-left: 4px solid #007bff;
        }
        .btn { 
            padding: 12px 24px; 
            margin: 8px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-approve { 
            background: #28a745; 
            color: white; 
        }
        .btn-approve:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .btn-receive { 
            background: #007bff; 
            color: white; 
        }
        .btn-receive:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        .status-message { 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 6px; 
            text-align: center;
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            border: 1px solid #bee5eb;
        }
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #007bff;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 8px;
        }
        .logo {
            text-align: center;
            margin-bottom: 15px;
        }
        .logo img {
            height: 60px;
        }
        .status-flow {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            color: #495057;
        }
        .current-status {
            background: #007bff;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h2 style="color: #2c3e50; margin: 0;">🏢 DOLE REGION XI</h2>
        </div>
        
        <div class="header">
            <h3>QR Code Status Update</h3>
            <p style="color: #666; margin: 0;">Disbursement Voucher System</p>
        </div>

        <!-- QR Status Check -->
        @if($dvRequest->qr_status !== 'active')
            <div class="status-message info">
                <h4>⏳ QR Code Not Yet Activated</h4>
                <p>The QR code on this document is not yet activated for scanning.</p>
                <p>Please wait for Accounting III to validate and activate the QR code.</p>
                <p><strong>Current Status:</strong> {{ $dvRequest->status }}</p>
                <p><strong>QR Status:</strong> 
                    @if($dvRequest->qr_status === 'inactive')
                        <span style="color: #dc3545;">Inactive</span>
                    @else
                        <span style="color: #6c757d;">Not Set</span>
                    @endif
                </p>
            </div>
        @else
            <!-- Status Flow -->
            <div class="status-flow">
                📋 Status Flow: Outgoing → Accounting III → RD → Cashier
                <div class="current-status">Current: {{ $dvRequest->status }}</div>
            </div>

            <!-- DV Information -->
            <div class="dv-info">
                <h4>📄 Disbursement Voucher Information</h4>
                <p><strong>DV Number:</strong> {{ $dvRequest->dv_number }}</p>
                <p><strong>Payee:</strong> {{ $dvRequest->payee_name }}</p>
                <p><strong>Amount:</strong> ₱{{ number_format($dvRequest->amount, 2) }}</p>
                <p><strong>Current Status:</strong> 
                    <span style="color: #007bff; font-weight: bold;">{{ $dvRequest->status }}</span>
                </p>
                <p><strong>Program:</strong> {{ $dvRequest->program->prname ?? 'N/A' }}</p>
                <p><strong>Date Created:</strong> {{ $dvRequest->created_at->format('M d, Y') }}</p>
            </div>

            <!-- Authentication Check -->
            @auth
                <div id="statusUpdateSection">
                    <!-- Regional Director Actions -->
                    @if(auth()->user()->hasRole('regional_director'))
                        @if($dvRequest->status === 'RANCA_validated')
                            <button class="btn btn-receive" onclick="updateStatus('rd_receive')">
                                ✅ Mark as Received by Regional Director
                                <span class="role-badge">RD</span>
                            </button>
                        @elseif($dvRequest->status === 'RD_received')
                            <button class="btn btn-approve" onclick="updateStatus('rd_approve')">
                                ✍️ Sign and Approve as Regional Director
                                <span class="role-badge">RD</span>
                            </button>
                        @else
                            <div class="status-message info">
                                <p>✅ Regional Director actions completed</p>
                                <p>Waiting for Cashier to process</p>
                            </div>
                        @endif
                    @endif

                    <!-- Cashier Actions -->
                    @if(auth()->user()->hasRole('cashier'))
                        @if($dvRequest->status === 'RD_signed_approved')
                            <button class="btn btn-receive" onclick="updateStatus('cashier_receive')">
                                ✅ Receive for Processing by Cashier
                                <span class="role-badge">Cashier</span>
                            </button>
                        @elseif($dvRequest->status === 'Cashier_processing')
                            <button class="btn btn-approve" onclick="updateStatus('cashier_complete')">
                                ✅ Mark Transaction as Completed
                                <span class="role-badge">Cashier</span>
                            </button>
                        @else
                            <div class="status-message info">
                                <p>⏳ Waiting for Regional Director approval</p>
                                <p>Current status: {{ $dvRequest->status }}</p>
                            </div>
                        @endif
                    @endif

                    <!-- No Permission -->
                    @if(!auth()->user()->hasRole('regional_director') && !auth()->user()->hasRole('cashier'))
                        <div class="status-message info">
                            <h4>⚠️ Access Restricted</h4>
                            <p>You don't have permission to update DV status.</p>
                            <p><strong>Required roles:</strong> Regional Director or Cashier</p>
                            <p><strong>Your role:</strong> {{ auth()->user()->getRoleNames()->first() ?? 'No role assigned' }}</p>
                        </div>
                    @endif
                </div>
            @else
                <!-- Not Authenticated -->
                <div class="status-message info">
                    <h4>🔐 Authentication Required</h4>
                    <p>Please log in to update the DV status.</p>
                    <a href="{{ route('login') }}" class="btn" style="background: #6f42c1; color: white; text-decoration: none; display: inline-block; margin-top: 10px;">
                        🔑 Login to System
                    </a>
                </div>
            @endauth
        @endif

        <!-- Status Message Container -->
        <div id="statusMessage"></div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
            <small style="color: #666;">
                DOLE Region XI - Disbursement Voucher System<br>
                QR Code Status Update Interface
            </small>
        </div>
    </div>

    <script>
        function updateStatus(action) {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳ Updating...';
            button.disabled = true;

            fetch('{{ route("dv.qr.update-status", $token) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: action })
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('statusMessage');
                if (data.success) {
                    messageDiv.innerHTML = `<div class="status-message success">
                        <h4>✅ Success!</h4>
                        <p>${data.message}</p>
                        <p><strong>New Status:</strong> ${data.new_status}</p>
                        <p><strong>DV Number:</strong> ${data.dv_number}</p>
                        <p><strong>Updated by:</strong> ${data.updated_by} (${data.role})</p>
                        ${data.next_action ? `<p><strong>Next Step:</strong> ${data.next_action}</p>` : ''}
                    </div>`;
                    
                    // Reload the page after 2 seconds to show updated buttons
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                    
                } else {
                    messageDiv.innerHTML = `<div class="status-message error">
                        <h4>❌ Error</h4>
                        <p>${data.message}</p>
                        ${data.login_required ? '<p><a href="{{ route("login") }}" style="color: #007bff;">Please login here</a></p>' : ''}
                    </div>`;
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('statusMessage').innerHTML = 
                    '<div class="status-message error">Network error occurred. Please try again.</div>';
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    </script>
</body>
</html>