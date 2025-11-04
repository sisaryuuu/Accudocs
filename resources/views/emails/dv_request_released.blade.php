<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DV Request Released</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #28a745;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .details {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
        .status-badge {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ DV Request Released</h1>
        <p>Department of Labor and Employment - Region XI</p>
    </div>
    
    <div class="content">
        <p>Dear <strong>{{ $focalPerson->fname }} {{ $focalPerson->lname }}</strong>,</p>
        
        <p>We are pleased to inform you that your Disbursement Voucher request has been successfully released by the Cashier.</p>
        
        <div class="status-badge">
            TRANSACTION COMPLETED
        </div>

        <div class="details">
            <div class="info-row">
                <span class="info-label">DV Number:</span>
                <span class="info-value">{{ $dvRequest->dv_number ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Reference Number:</span>
                <span class="info-value">{{ $dvRequest->reference_number ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Amount:</span>
                <span class="info-value">₱ {{ number_format($dvRequest->amount, 2) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Payee:</span>
                <span class="info-value">{{ $focalPerson->fname }} {{ $focalPerson->lname }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Program:</span>
                <span class="info-value">{{ $dvRequest->program->description ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Particulars:</span>
                <span class="info-value">{{ $dvRequest->particulars }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Released By:</span>
                <span class="info-value">{{ $releasedBy }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Released At:</span>
                <span class="info-value">{{ $releasedAt }}</span>
            </div>
        </div>

        <p><strong>Next Steps:</strong> Your transaction has been fully processed and completed. You may now proceed with any necessary follow-up actions.</p>

        <p>Thank you for using ACCUDOCS System.</p>
        
        <div class="footer">
            <p>This is an automated notification from ACCUDOCS System.<br>
            Department of Labor and Employment Regional Office XI<br>
            Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>