<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update DV Status - DOLE XI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background circles */
        body::before,
        body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 20s infinite ease-in-out;
        }

        body::before {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
        }

        body::after {
            width: 400px;
            height: 400px;
            bottom: -150px;
            right: -150px;
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(50px, 50px) scale(1.1); }
        }

        .container { 
            max-width: 550px; 
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            position: relative;
            z-index: 1;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .header-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .logo-section {
            position: relative;
            z-index: 1;
            margin-bottom: 15px;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            margin-bottom: 10px;
        }

        .header-banner h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-banner p {
            font-size: 14px;
            opacity: 0.95;
            font-weight: 500;
        }

        .content-section {
            padding: 30px;
        }

        .status-flow {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            text-align: center;
            border: 2px solid #e9ecef;
            position: relative;
            overflow: hidden;
        }

        .status-flow::before {
            content: '→';
            position: absolute;
            font-size: 100px;
            color: rgba(102, 126, 234, 0.05);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .status-flow-text {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 12px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .current-status {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            position: relative;
            z-index: 1;
        }

        .dv-info { 
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 25px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .dv-info h4 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6c757d;
            font-size: 13px;
            font-weight: 500;
        }

        .info-value {
            color: #2c3e50;
            font-size: 13px;
            font-weight: 600;
            text-align: right;
        }

        .amount-highlight {
            color: #28a745;
            font-size: 16px;
        }

        .btn { 
            padding: 14px 24px;
            margin: 8px 0;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn span {
            position: relative;
            z-index: 1;
        }

        .btn-approve { 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .btn-receive { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-receive:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-scan { 
            background: linear-gradient(135deg, #6f42c1 0%, #9b59b6 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
        }

        .btn-scan:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4);
        }

        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            opacity: 0.6;
        }

        .status-message { 
            padding: 20px;
            margin: 20px 0;
            border-radius: 12px;
            text-align: center;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success { 
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #c3e6cb;
        }

        .error { 
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #f5c6cb;
        }

        .info { 
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
            border: 2px solid #bee5eb;
        }

        .status-message h4 {
            margin-bottom: 12px;
            font-size: 18px;
        }

        .status-message p {
            margin: 8px 0;
            font-size: 14px;
            line-height: 1.6;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .footer {
            text-align: center;
            padding: 20px 30px;
            border-top: 1px solid #f0f0f0;
            background: #fafafa;
        }

        .footer small {
            color: #6c757d;
            font-size: 12px;
            line-height: 1.6;
        }

        .login-btn {
            background: linear-gradient(135deg, #6f42c1 0%, #9b59b6 100%);
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 12px;
            margin-top: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4);
        }

        .qr-inactive-badge {
            display: inline-block;
            padding: 8px 16px;
            background: #ffc107;
            color: #856404;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .action-buttons .btn {
            margin: 0;
            flex: 1;
        }

        /* Loading animation */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        /* Responsive */
        @media (max-width: 576px) {
            body {
                padding: 10px;
            }

            .container {
                border-radius: 16px;
            }

            .header-banner {
                padding: 20px;
            }

            .content-section {
                padding: 20px;
            }

            .header-banner h2 {
                font-size: 20px;
            }

            .dv-info {
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }
        }

        .border-t {
            border-top: 1px solid #e9ecef;
        }

        .pt-6 {
            padding-top: 24px;
        }

        .mt-6 {
            margin-top: 24px;
        }

        .text-center {
            text-align: center;
        }

        .text-lg {
            font-size: 18px;
        }

        .font-semibold {
            font-weight: 600;
        }

        .text-gray-700 {
            color: #374151;
        }

        .mb-3 {
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Banner -->
        <div class="header-banner">
            <div class="logo-section">
                <div class="logo-icon">🏢</div>
                <h2>DOLE REGION XI</h2>
                <p>QR Code Status Update System</p>
            </div>
        </div>

        <div class="content-section">
            <!-- QR Status Check -->
            @if($dvRequest->qr_status !== 'active')
                <div class="status-message info">
                    <h4>⏳ QR Code Not Yet Activated</h4>
                    <p>The QR code on this document is not yet activated for scanning.</p>
                    <p>Please wait for Accounting III to validate and activate the QR code.</p>
                    <div style="margin-top: 15px;">
                        <strong>Current Status:</strong> {{ $dvRequest->status }}<br>
                        <span class="qr-inactive-badge">
                            QR Status: 
                            @if($dvRequest->qr_status === 'inactive')
                                Inactive
                            @else
                                Not Set
                            @endif
                        </span>
                    </div>
                </div>
            @else
                <!-- Status Flow -->
                <div class="status-flow">
                    <div class="status-flow-text">
                        📋 Outgoing → Accounting III → Regional Director → Cashier
                    </div>
                    <div class="current-status">
                        <span>Current: {{ $dvRequest->status }}</span>
                    </div>
                </div>

                <!-- DV Information -->
                <div class="dv-info">
                    <h4>📄 Disbursement Voucher Details</h4>
                    
                    <div class="info-row">
                        <span class="info-label">DV Number</span>
                        <span class="info-value">{{ $dvRequest->dv_number }}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Payee</span>
                        <span class="info-value">{{ $dvRequest->focalPerson->fname ?? 'N/A' }} {{ $dvRequest->focalPerson->lname ?? 'N/A' }}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Amount</span>
                        <span class="info-value amount-highlight">₱{{ number_format($dvRequest->amount, 2) }}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Program</span>
                        <span class="info-value">{{ $dvRequest->program->prcode ?? 'N/A' }} - {{ $dvRequest->program->description ?? 'N/A' }}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Date Created</span>
                        <span class="info-value">{{ $dvRequest->created_at->format('M d, Y') }}</span>
                    </div>
                </div>

                <!-- Authentication Check -->
                @auth
                    <div id="statusUpdateSection">
                        <!-- Regional Director Actions -->
                        @if(auth()->user()->hasRole('Regional_Director'))
                            @if($dvRequest->status === 'RANCA_validated')
                                <div class="action-buttons">
                                    <button class="btn btn-receive" onclick="updateStatus('receive')">
                                        <span>📥 Receive Document</span>
                                        <span class="role-badge">RD</span>
                                    </button>
                                    <button class="btn btn-scan" onclick="scanAnotherQR()">
                                        <span>📷 Scan Another QR</span>
                                    </button>
                                </div>
                                <div class="status-message info" style="margin-top: 15px;">
                                    <p><strong>Next Action:</strong> Scan again after receiving to sign and approve</p>
                                </div>
                            @elseif($dvRequest->status === 'Regional_Director_received')
                                <div class="action-buttons">
                                    <button class="btn btn-approve" onclick="updateStatus('approve')">
                                        <span>✍️ Sign and Approve</span>
                                        <span class="role-badge">RD</span>
                                    </button>
                                    <button class="btn btn-scan" onclick="scanAnotherQR()">
                                        <span>📷 Scan Another QR</span>
                                    </button>
                                </div>
                                <div class="status-message info" style="margin-top: 15px;">
                                    <p><strong>Note:</strong> This is your second scan to complete the approval process</p>
                                </div>
                            @else
                                <div class="status-message success">
                                    <h4>✅ Regional Director Actions Completed</h4>
                                    <p>This DV has been approved and is now with the Cashier for processing.</p>
                                    <div class="action-buttons" style="margin-top: 15px;">
                                        <button class="btn btn-scan" onclick="scanAnotherQR()">
                                            <span>📷 Scan Another QR</span>
                                        </button>
                                        <button class="btn btn-receive" onclick="goToDashboard()">
                                            <span>📊 Go to Dashboard</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @endif

                        <!-- Cashier Actions -->
                        @if(auth()->user()->hasRole('Cashier'))
                            @if($dvRequest->status === 'Regional_Director_signed_approved')
                                <div class="action-buttons">
                                    <button class="btn btn-receive" onclick="updateStatus('receive')">
                                        <span>📥 Receive for Processing</span>
                                        <span class="role-badge">Cashier</span>
                                    </button>
                                    <button class="btn btn-scan" onclick="scanAnotherQR()">
                                        <span>📷 Scan Another QR</span>
                                    </button>
                                </div>
                                <div class="status-message info" style="margin-top: 15px;">
                                    <p><strong>Next Action:</strong> Scan again after processing to mark as completed</p>
                                </div>
                            @elseif($dvRequest->status === 'Cashier_received')
                                <div class="action-buttons">
                                    <button class="btn btn-approve" onclick="updateStatus('complete')">
                                        <span>✅ Release Payment</span>
                                        <span class="role-badge">Cashier</span>
                                    </button>
                                    <button class="btn btn-scan" onclick="scanAnotherQR()">
                                        <span>📷 Scan Another QR</span>
                                    </button>
                                </div>
                                <div class="status-message info" style="margin-top: 15px;">
                                    <p><strong>Note:</strong> This is your second scan to complete the transaction</p>
                                </div>
                            @else
                                <div class="status-message info">
                                    <h4>⏳ Waiting for Regional Director</h4>
                                    <p>This DV needs to be approved by the Regional Director first.</p>
                                    <p><strong>Current Status:</strong> {{ $dvRequest->status }}</p>
                                    <div class="action-buttons" style="margin-top: 15px;">
                                        <button class="btn btn-scan" onclick="scanAnotherQR()">
                                            <span>📷 Scan Another QR</span>
                                        </button>
                                        <button class="btn btn-receive" onclick="goToDashboard()">
                                            <span>📊 Go to Dashboard</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @endif

                        <!-- No Permission -->
                        @if(!auth()->user()->hasRole('Regional_Director') && !auth()->user()->hasRole('Cashier'))
                            <div class="status-message error">
                                <h4>⚠️ Access Restricted</h4>
                                <p>You don't have permission to update this DV status.</p>
                                <p><strong>Required roles:</strong> Regional Director or Cashier</p>
                                <p><strong>Your role:</strong> {{ auth()->user()->getRoleNames()->first() ?? 'No role assigned' }}</p>
                                <div class="action-buttons" style="margin-top: 15px;">
                                    <button class="btn btn-scan" onclick="goToDashboard()">
                                        <span>📊 Go to Dashboard</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <!-- Not Authenticated -->
                    <div class="status-message info">
                        <h4>🔐 Authentication Required</h4>
                        <p>Please log in to update the DV status.</p>
                        <div class="action-buttons" style="margin-top: 15px;">
                            <a href="{{ route('login') }}" class="btn btn-scan">
                                <span>🔑 Login to System</span>
                            </a>
                        </div>
                    </div>
                @endauth
            @endif

            <!-- Status Message Container -->
            <div id="statusMessage"></div>

            <!-- Scan Another QR Section (Always visible for authenticated users) -->
            @auth
                @if(auth()->user()->hasAnyRole(['Regional_Director', 'Cashier']))
                    <div class="border-t pt-6 mt-6">
                        <div class="text-center">
                            <h4 class="text-lg font-semibold text-gray-700 mb-3">Quick Actions</h4>
                            <div class="action-buttons">
                                <button class="btn btn-scan" onclick="scanAnotherQR()">
                                    <span>📷 Scan Another QR Code</span>
                                </button>
                                <button class="btn btn-receive" onclick="goToDashboard()">
                                    <span>📊 View Dashboard</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            @endauth
        </div>

        <!-- Footer -->
        <div class="footer">
            <small>
                <strong>DOLE Region XI</strong><br>
                Disbursement Voucher QR Code System<br>
                Secure • Fast • Reliable
            </small>
        </div>
    </div>

    <script>
        function updateStatus(action) {
            const button = event.target.closest('.btn');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<span>⏳ Updating...</span>';
            button.disabled = true;
            button.classList.add('loading');

            // Simple POST without action parameter - backend handles based on current status
            fetch('{{ route("dv.qr.update-status", $token) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({}) // No action parameter needed - backend handles logic
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('statusMessage');
                if (data.success) {
                    messageDiv.innerHTML = `
                        <div class="status-message success">
                            <h4>✅ Success!</h4>
                            <p>${data.message}</p>
                            <p><strong>New Status:</strong> ${data.new_status}</p>
                            <p><strong>DV Number:</strong> ${data.dv_number}</p>
                            <p><strong>Updated by:</strong> ${data.updated_by} (${data.role})</p>
                            ${data.next_action ? `<p><strong>Next Step:</strong> ${data.next_action}</p>` : ''}
                            <div class="action-buttons" style="margin-top: 15px;">
                                <button class="btn btn-scan" onclick="scanAnotherQR()">
                                    <span>📷 Scan Another QR</span>
                                </button>
                                <button class="btn btn-receive" onclick="goToDashboard()">
                                    <span>📊 Go to Dashboard</span>
                                </button>
                            </div>
                        </div>
                    `;
                    
                    // Reload after 3 seconds to show updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                    
                } else {
                    messageDiv.innerHTML = `
                        <div class="status-message error">
                            <h4>❌ Error</h4>
                            <p>${data.message}</p>
                            ${data.current_status ? `<p><strong>Current Status:</strong> ${data.current_status}</p>` : ''}
                            ${data.login_required ? '<p><a href="{{ route("login") }}" class="login-btn">Please login here</a></p>' : ''}
                        </div>
                    `;
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                    button.classList.remove('loading');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('statusMessage').innerHTML = `
                    <div class="status-message error">
                        <h4>❌ Network Error</h4>
                        <p>Unable to connect to server. Please check your internet connection and try again.</p>
                    </div>
                `;
                button.innerHTML = originalHTML;
                button.disabled = false;
                button.classList.remove('loading');
            });
        }

        function scanAnotherQR() {
            // Redirect to the appropriate scanner based on user role
            @if(auth()->user()->hasRole('Regional_Director'))
                window.location.href = '{{ route("regional.director.scanner") }}';
            @elseif(auth()->user()->hasRole('Cashier'))
                window.location.href = '{{ route("cashier.scanner") }}';
            @else
                window.location.href = '{{ route("login") }}';
            @endif
        }

        function goToDashboard() {
            // Redirect to the appropriate dashboard based on user role
            @if(auth()->user()->hasRole('Regional_Director'))
                window.location.href = '{{ route("regional.director.dashboard") }}';
            @elseif(auth()->user()->hasRole('Cashier'))
                window.location.href = '{{ route("cashier.dashboard") }}';
            @else
                window.location.href = '{{ route("login") }}';
            @endif
        }

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            // Ctrl + S for Scan Another QR
            if (event.ctrlKey && event.key === 's') {
                event.preventDefault();
                scanAnotherQR();
            }
            // Ctrl + D for Dashboard
            if (event.ctrlKey && event.key === 'd') {
                event.preventDefault();
                goToDashboard();
            }
        });

        // Show keyboard shortcuts info
        console.log('Keyboard shortcuts:');
        console.log('Ctrl + S - Scan Another QR');
        console.log('Ctrl + D - Go to Dashboard');
    </script>
</body>
</html>