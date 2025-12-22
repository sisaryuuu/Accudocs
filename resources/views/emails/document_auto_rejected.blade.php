<!DOCTYPE html>
<html>
<head>
    <title>{{ $subject }}</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .container { 
            max-width: 650px; 
            margin: 0 auto; 
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .header { 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
            color: white; 
            padding: 30px 20px;
            text-align: center;
        }
        .content { 
            padding: 30px; 
        }
        .rejection-banner {
            background: #fff5f5;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
        }
        .document-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 25px;
            margin: 25px 0;
            border-radius: 10px;
            border-left: 4px solid #6c757d;
        }
        .timeline-card {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 25px;
            margin: 25px 0;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
        }
        .action-card {
            background: #e7f5ff;
            border: 1px solid #c5e1ff;
            padding: 25px;
            margin: 25px 0;
            border-radius: 10px;
            border-left: 4px solid #007bff;
        }
        .footer { 
            margin-top: 40px;
            padding: 25px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            font-size: 13px; 
            color: #6c757d;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 0 5px 5px 0;
        }
        .badge-critical {
            background: #dc3545;
            color: white;
        }
        .stat-box {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            padding: 12px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        .stat-label {
            font-weight: 600;
            color: #6c757d;
        }
        .stat-value {
            font-weight: 700;
            color: #212529;
        }
        h1, h2, h3 {
            margin-top: 0;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        h2 {
            font-size: 22px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        h3 {
            font-size: 18px;
            color: #495057;
            margin-bottom: 15px;
        }
        .contact-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .urgency-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dc3545;
            color: white;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚫 DOCUMENT AUTO-REJECTED</h1>
            <p>{{ $organizationName }} </p>
            <div class="urgency-indicator">
                ⚠️ SYSTEM AUTOMATION - ACTION REQUIRED
            </div>
        </div>
        
        <div class="content">
            <p>Dear <strong>{{ $focalPersonName }}</strong>,</p>
            
            <p>This notification serves as official confirmation that your DV request has been <strong>automatically rejected</strong> by the system due to prolonged inactivity exceeding the 15-day processing threshold.</p>
            
            <div class="rejection-banner">
                <h2>🔴 IMMEDIATE ATTENTION REQUIRED</h2>
                <p><strong>Your document has exceeded the maximum allowable processing time and has been automatically rejected.</strong> This action is in accordance with our document processing policy to ensure timely handling of all requests.</p>
            </div>
            
            <div class="document-card">
                <h3>📄 DOCUMENT DETAILS</h3>
                <div class="stat-box">
                    <div class="stat-label">Reference Number:</div>
                    <div class="stat-value">{{ $referenceNumber }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Program:</div>
                    <div class="stat-value">{{ $program }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Submitted Date:</div>
                    <div class="stat-value">{{ $submittedDate }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Days Pending:</div>
                    <div class="stat-value">
                        <span class="badge badge-critical">{{ $daysOld }} DAYS</span>
                    </div>
                </div>
            </div>
            
            <div class="timeline-card">
                <h3>⏰ TIMELINE & REJECTION DETAILS</h3>
                <div class="stat-box">
                    <div class="stat-label">Date Submitted:</div>
                    <div class="stat-value">{{ $submittedDate }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Date Rejected:</div>
                    <div class="stat-value">{{ $rejectedDate }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Time Rejected:</div>
                    <div class="stat-value">{{ $rejectedTime }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Delay:</div>
                    <div class="stat-value">{{ $daysOld }} days (exceeds 15-day limit)</div>
                </div>
                
                <h4>Rejection Reason:</h4>
                <p style="padding: 15px; background: white; border-radius: 6px; margin: 10px 0;">
                    {{ $rejectionReason }}
                </p>
            </div>
            
            <div class="action-card">
                <h3>📋 REQUIRED NEXT STEPS</h3>
                <ol style="padding-left: 20px;">
                    <li><strong>Review Rejection Details</strong> - Understand why the document was rejected</li>
                    <li><strong>Prepare New Submission</strong> - Create a new DV request with updated information</li>
                    <li><strong>Resubmit Promptly</strong> - Submit the new request as soon as possible</li>
                    <li><strong>Monitor Processing</strong> - Track the new request to ensure timely processing</li>
                </ol>
                
                <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 6px;">
                    <strong>⚠️ Important Policy Note:</strong>
                    <p>To prevent future auto-rejections, ensure all documents are processed within <strong>15 calendar days</strong>. Regular follow-ups are recommended for time-sensitive requests.</p>
                </div>
            </div>
            
            <div class="contact-info">
                <h3>📞 CONTACT & SUPPORT</h3>
                <p>For assistance or inquiries regarding this auto-rejection:</p>
                <div class="stat-box">
                    <div class="stat-label">System:</div>
                    <div class="stat-value">{{ $systemName }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Support Email:</div>
                    <div class="stat-value">{{ $contactEmail }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Support Phone:</div>
                    <div class="stat-value">{{ $contactPhone }}</div>
                </div>
            </div>
            
            <p style="text-align: center; margin-top: 30px;">
                <strong>Best regards,</strong><br>
                <em>{{ $organizationName }}</em>
            </p>
        </div>
        
        <div class="footer">
            <p><strong>Reference:</strong> {{ $referenceNumber }} | Auto-Rejected on {{ $rejectedDate }}</p>
            <p><strong>Policy:</strong> Document Processing Time Limit (15 Days Maximum)</p>
            <p style="font-size: 12px; color: #adb5bd; margin-top: 15px;">
                This is an automated message from {{ $systemName }} System. Please do not reply to this email.<br>
                &copy; {{ date('Y') }} {{ $organizationName }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>