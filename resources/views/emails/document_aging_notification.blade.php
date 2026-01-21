<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Aging Alert - ACCUDOCS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
        }
        
        .email-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header-decoration {
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        
        .email-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .email-header p {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .urgency-banner {
            background: linear-gradient(135deg, {{ $urgency_color ?? '#dc2626' }} 0%, {{ $urgency_color ?? '#dc2626' }}99 100%);
            color: white;
            padding: 16px 32px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 16px;
        }
        
        .urgency-banner::before {
            content: '';
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .content-section {
            padding: 40px 32px;
        }
        
        .greeting {
            font-size: 18px;
            margin-bottom: 32px;
            color: #475569;
        }
        
        .greeting strong {
            color: #1e293b;
            font-weight: 600;
        }
        
        .alert-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fef9c3 100%);
            border-left: 6px solid #f59e0b;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
            box-shadow: 0 4px 6px rgba(245, 158, 11, 0.1);
        }
        
        .alert-card.critical {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left-color: #dc2626;
            box-shadow: 0 4px 6px rgba(220, 38, 38, 0.1);
        }
        
        .alert-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 12px;
        }
        
        .critical .alert-title {
            color: #7f1d1d;
        }
        
        .days-counter {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin: 32px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .days-display {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .days-number {
            font-size: 56px;
            font-weight: 800;
            color: {{ ($days_old ?? 0) >= 10 ? '#dc2626' : '#059669' }};
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .days-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .threshold-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f1f5f9;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .transition-info {
            background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
            border: 2px solid #3b82f6;
            position: relative;
        }
        
        .transition-info::before {
            content: '🔄 TRANSITION';
            position: absolute;
            top: -12px;
            left: 20px;
            background: #3b82f6;
            color: white;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge {
            background: #3b82f6;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin: 0 4px;
        }
        
        .document-details {
            background: white;
            border-radius: 12px;
            padding: 32px;
            margin: 32px 0;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.03);
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .detail-item {
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        
        .detail-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        
        .detail-value {
            font-size: 16px;
            color: #1e293b;
            font-weight: 500;
        }
        
        .action-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            margin: 24px 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
        
        .recommendations {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-radius: 12px;
            padding: 28px;
            margin: 32px 0;
            border: 2px solid #10b981;
        }
        
        .recommendations-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 20px;
        }
        
        .recommendation-list {
            list-style: none;
            padding-left: 0;
        }
        
        .recommendation-list li {
            padding: 10px 0;
            padding-left: 30px;
            position: relative;
            color: #374151;
        }
        
        .recommendation-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
            font-size: 16px;
        }
        
        .email-footer {
            background: #1e293b;
            color: #cbd5e1;
            padding: 32px;
            text-align: center;
            border-top: 1px solid #334155;
        }
        
        .footer-logo {
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 16px;
            letter-spacing: 1px;
        }
        
        .footer-info {
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 16px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .footer-note {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #334155;
        }
        
        .status-chip {
            background: #e0e7ff;
            color: #3730a3;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .amount-display {
            font-size: 22px;
            font-weight: 700;
            color: #059669;
            letter-spacing: 0.5px;
        }
        
        @media (max-width: 600px) {
            .content-section {
                padding: 24px 16px;
            }
            
            .email-header {
                padding: 32px 24px;
            }
            
            .email-header h1 {
                font-size: 24px;
            }
            
            .days-number {
                font-size: 42px;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .action-button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="header-decoration"></div>
            <h1>⏰ Document Aging Alert</h1>
            <p>{{ $department_name ?? 'Document Processing System' }} • ACCUDOCS Notification</p>
        </div>
        
        <!-- Urgency Banner -->
        <div class="urgency-banner">
            {{ $urgency_text ?? '⚠️ PRIORITY NOTIFICATION' }}
        </div>
        
        <!-- Main Content -->
        <div class="content-section">
            <!-- Greeting -->
            <p class="greeting">Dear <strong>{{ $recipient_name ?? 'Valued Team Member' }}</strong>,</p>
            
            <!-- Alert Card -->
            <div class="alert-card {{ ($days_old ?? 0) >= 10 ? 'critical' : '' }}">
                <div class="alert-title">
                    <span>{{ ($days_old ?? 0) >= 10 ? '🚨 CRITICAL ALERT' : '⚠️ ACTION REQUIRED' }}</span>
                </div>
                <p style="color: {{ ($days_old ?? 0) >= 10 ? '#7f1d1d' : '#92400e' }}; font-size: 15px;">
                    @if(isset($check_type) && $check_type === 'transition')
                        A disbursement voucher requires immediate action to <strong>{{ $department_name ?? 'your department' }}</strong>. 
                        It has been pending for <strong>{{ $days_old ?? 0 }} day{{ ($days_old ?? 0) > 1 ? 's' : '' }}</strong> 
                        since verification.
                    @else
                        A document has been awaiting action in <strong>{{ $department_name ?? 'your department' }}</strong> 
                        for <strong>{{ $days_old ?? 0 }} day{{ ($days_old ?? 0) > 1 ? 's' : '' }}</strong> 
                        without processing.
                    @endif
                </p>
            </div>
            
            <!-- Days Counter -->
            <div class="days-counter">
                <div class="days-display">
                    <div class="days-number">{{ $days_old ?? 0 }}</div>
                    <div class="days-label">DAYS PENDING</div>
                </div>
                <div class="threshold-info">
                    <span>Alert Threshold: {{ $threshold ?? 3 }}+ days</span>
                    <span class="status-chip">{{ $urgency_level ?? 'medium'|upper }}</span>
                </div>
            </div>
            
            <!-- Transition Info (if applicable) -->
            @if(isset($check_type) && $check_type === 'transition')
            <div class="transition-info">
                <div style="margin-bottom: 12px; font-weight: 600; color: #1e40af;">Document Transfer Required</div>
                <div style="display: flex; align-items: center; justify-content: center; gap: 20px; flex-wrap: wrap;">
                    <span class="status-badge">{{ $transition_from ?? 'Previous' }}</span>
                    <span style="color: #3b82f6;">→</span>
                    <span class="status-badge" style="background: #10b981;">{{ $transition_to ?? 'Next' }}</span>
                </div>
                <p style="margin-top: 16px; color: #475569; font-size: 14px; font-style: italic;">
                    {{ $action_required ?? 'Please receive this document in your department\'s system.' }}
                </p>
            </div>
            @endif
            
            <!-- Document Details -->
            <div class="document-details">
                <h3 style="color: #1e293b; margin-bottom: 24px; font-size: 20px; font-weight: 700;">📄 Document Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Control Number</div>
                        <div class="detail-value" style="font-weight: 700; color: #3b82f6;">{{ $control_number ?? 'Pending-CN' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Submitted By</div>
                        <div class="detail-value">{{ $submitted_by ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Program</div>
                        <div class="detail-value">{{ $program ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Amount</div>
                        <div class="detail-value amount-display">₱{{ $amount ?? '0.00' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Current Status</div>
                        <div class="detail-value">
                            <span class="status-chip">
                                {{ str_replace('_', ' ', $status ?? 'Unknown') }}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Submitted Date</div>
                        <div class="detail-value">{{ $submitted_date ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Base Date Used</div>
                        <div class="detail-value">{{ $base_date ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
            
            <!-- Action Button -->
            @if(isset($view_url) && $view_url !== '#')
            <div style="text-align: center;">
                <a href="{{ $view_url }}" class="action-button">
                    📋 View Document Details
                </a>
            </div>
            @endif
            
            <!-- Recommendations -->
            <div class="recommendations">
                <div class="recommendations-title">📌 Recommended Actions</div>
                <ul class="recommendation-list">
                    @if(isset($check_type) && $check_type === 'transition')
                    <li>Log into ACCUDOCS system immediately</li>
                    <li>Process the pending document</li>
                    <li>Verify all details and attachments</li>
                    <li>Process within 24 hours to avoid delays</li>
                    @else
                    <li>Review the document details immediately</li>
                    <li>Check all required information and attachments</li>
                    <li>Process or forward to next department promptly</li>
                    <li>Add remarks if returning with issues</li>
                    <li>Update status within the system</li>
                    @endif
                </ul>
            </div>
            
            <!-- Timeline Note -->
            <div style="background: #f8fafc; border-radius: 8px; padding: 16px; margin-top: 24px; border-left: 4px solid #94a3b8;">
                <div style="display: flex; align-items: center; gap: 10px; color: #64748b; font-size: 14px;">
                    <span>📅</span>
                    <span><strong>Timing Note:</strong> Document processing time started from {{ $base_date ?? $submitted_date ?? 'submission date' }}</span>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-logo">ACCUDOCS</div>
            <div class="footer-info">
                DOLE Region XI Document Tracking System<br>
                Automated Notification Service
            </div>
            <div class="footer-note">
                <p>This is an automated email. Please do not reply directly.</p>
                <p>For assistance, contact your system administrator or the Accounting Unit.</p>
                <p>© {{ date('Y') }} Department of Labor and Employment - Region XI</p>
            </div>
        </div>
    </div>
</body>
</html>