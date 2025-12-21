<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', 'Helvetica Neue', Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 25px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 22px;
            margin-bottom: 10px;
            color: white;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .urgency-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .urgency-critical {
            background-color: #dc3545;
            color: white;
        }
        
        .urgency-high {
            background-color: #fd7e14;
            color: white;
        }
        
        .urgency-medium {
            background-color: #ffc107;
            color: #212529;
        }
        
        .urgency-low {
            background-color: #6c757d;
            color: white;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert-section {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        
        .alert-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #d84315;
        }
        
        .document-details {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            font-weight: bold;
            color: #495057;
            width: 160px;
            flex-shrink: 0;
        }
        
        .detail-value {
            color: #212529;
            flex-grow: 1;
        }
        
        .action-section {
            text-align: center;
            padding: 25px;
            background-color: #e8f4fd;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            margin: 15px 0;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #6c757d;
            font-size: 13px;
            border-top: 1px solid #e9ecef;
        }
        
        .footer-logo {
            margin-bottom: 15px;
        }
        
        .contact-info {
            margin-top: 15px;
            font-size: 12px;
            color: #868e96;
        }
        
        .timestamp {
            text-align: right;
            font-size: 12px;
            color: #868e96;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px dashed #dee2e6;
        }
        
        .days-old {
            font-size: 28px;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
            margin: 15px 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            background-color: #e9ecef;
            border-radius: 12px;
            font-size: 12px;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $organizationName }} - {{ $systemName }}</h1>
            <p>Disbursement Voucher System Notification</p>
            <div class="urgency-badge urgency-{{ $urgencyLevel }}">
                {{ $urgencyEmoji }} {{ $urgencyText }}
            </div>
        </div>
        
        <!-- Content -->
        <div class="content">
            <!-- Alert Section -->
            <div class="alert-section">
                <div class="alert-title">{{ $urgencyEmoji }} Document Processing Delay Alert</div>
                <p>Dear <strong>{{ $focalPersonName }}</strong>,</p>
                <p>This is to inform you that your Disbursement Voucher (DV) request has been pending for:</p>
                
                <div class="days-old">{{ $daysOld }} DAYS</div>
                
                <p>Please take necessary action to follow up on the status of this document.</p>
            </div>
            
            <!-- Document Details -->
            <div class="document-details">
                <h3 style="margin-bottom: 20px; color: #1e3c72;">📋 DVORS Information</h3>
                
                <div class="detail-row">
                    <div class="detail-label">Reference Number:</div>
                    <div class="detail-value"><strong>{{ $referenceNumber }}</strong></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Control Number:</div>
                    <div class="detail-value">{{ $controlNumber }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Program/Project:</div>
                    <div class="detail-value">{{ $program }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Current Status:</div>
                    <div class="detail-value">
                        <span class="status-badge">{{ $status }}</span>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Date Submitted:</div>
                    <div class="detail-value">{{ $submittedDate }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Days Since Submission:</div>
                    <div class="detail-value" style="color: #dc3545; font-weight: bold;">
                        {{ $daysOld }} days
                    </div>
                </div>
            </div>
            
            <!-- Action Section -->
            <div class="action-section">
                <h3 style="margin-bottom: 15px; color: #1e3c72;">🚀 Required Action</h3>
                <p>Please follow up with the concerned office/department regarding the status of your request.</p>
                
                <a href="{{ $actionUrl }}" class="action-button">
                    👉 View Complete Request Details
                </a>
                
                <p style="margin-top: 15px; font-size: 14px; color: #495057;">
                    <em>Click the button above to access the complete history and current status of your DV request.</em>
                </p>
            </div>
            
            <!-- Timestamp -->
            <div class="timestamp">
                Notification generated: {{ $currentDate }} at {{ $currentTime }} (Asia/Manila)
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-logo">
                <strong style="color: #1e3c72;">{{ $organizationName }}</strong><br>
                <span style="color: #2a5298;">{{ $systemName }} System</span>
            </div>
            
            <p>This is an automated notification from the Document Verification System.</p>
            <p>Please do not reply to this email. For inquiries or assistance, please contact:</p>
            
            <div class="contact-info">
                Department of Labor and Employment Regional XI<br>
            </div>
            
            <p style="margin-top: 15px; font-size: 12px; color: #adb5bd;">
                © {{ date('Y') }} Department of Labor and Employment Regional XI. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>