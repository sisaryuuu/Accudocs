<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disbursement Voucher</title>
    <style>
        @page { 
            size: Legal; 
            margin: 1mm; 
        }
        body { 
            font-family: "Times New Roman", Times, serif; 
            font-size: 10px;
            margin: 0; 
            padding: 15px;
            line-height: 1.2;
            position: relative;
        }
    
        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            width: fit-content;
        }

        .qr-label, .qr-status, .qr-placeholder, .qr-footer {
            font-size: 12px;
            margin: 4px 0;
        }

        .qr-label {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 6px;
            color: #333;
        }

        /* QR Code Size: Minimum 1x1 cm (approximately 38x38 pixels at 96 DPI) */
        .qr-image {
            width: 114px;  /* 1 cm equivalent */
            height: 114px; /* 1 cm equivalent */
            min-width: 114px;
            min-height: 114px;
            display: block;
            margin: 0 auto;
            border: 1px solid #f0f0f0; /* subtle border for visibility */
        }

        .qr-code-placeholder {
            width: 114px;  /* 1 cm equivalent */
            height: 114px; /* 1 cm equivalent */
            min-width: 114px;
            min-height: 114px;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            color: #666;
            font-size: 6px; /* smaller font for small placeholder */
            font-weight: bold;
            margin: 0 auto;
            line-height: 1;
            text-align: center;
            padding: 2px;
            box-sizing: border-box;
        }
        
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-bottom: 0;
        }
        
        td, th { 
            border: 1px solid #000; 
            padding: 4px 6px;
            vertical-align: top;
            font-weight: normal;
            font-family: "Times New Roman", Times, serif;
        }
        
        .header-table {
            border: 1px solid #000;
        }
        
        .header-table td {
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #000;
        }
        
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .small { font-size: 9px; }
        .no-border { border: none !important; }
        .border-right { border-right: 1px solid #000; }
        .no-inner-border { border: none !important; }
        .section-label { width: 30px; font-weight: bold; text-align: center; border-right: 1px solid #000; }
        
        .logo-cell {
            width: 70px;
            text-align: center;
            padding: 5px;
        }
        
        .checkbox {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
            border: 1.4px solid #000;
            margin-right: 4px;
            vertical-align: middle;
            text-align: center;
            line-height: 1;
            font-size: 12px;
            font-weight: bold;
            background-color: #fff;
            border-radius: 2px;
        }

        .checkbox.checked {
            background-color: #000;
            color: #fff;
        }
        
        .particulars-section {
            min-height: 120px;
        }
        
        .signature-section {
            min-height: 60px;
            position: relative;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            width: 70%;
            margin: 35px auto 3px auto;
        }
        
        .accounting-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            padding: 5px;
            font-family: "Times New Roman", Times, serif;
        }
        
        /* Fix border issues in header table */
        .header-table tr:first-child td {
            border-top: 1px solid #000;
        }
        
        .header-table tr:last-child td {
            border-bottom: 1px solid #000;
        }
        
        .header-table td:first-child {
            border-left: 1px solid #000;
        }
        
        .header-table td:last-child {
            border-right: 1px solid #000;
        }
        
        /* Logo size for both screen and print */
        .dole-logo {
            width: 120px !important;
            height: auto !important;
            max-width: 120px !important;
        }
        
        @media print {
            body { 
                padding: 0; 
                margin: 1cm;
            }
            
            .no-print { display: none; }
            
            .dole-logo {
                width: 175px !important;
                height: auto !important;
                max-width: 175px !important;
            }
            
            /* Ensure QR code maintains minimum 1x1 cm size in print */
            .qr-image {
                width: 2cm !important;  /* Physical 1 cm size */
                height: 2cm !important; /* Physical 1 cm size */
                min-width: 2cm !important;
                min-height: 2cm !important;
                margin: 8px auto !important;
            }
            
            .qr-code-placeholder {
                width: 2cm !important;  /* Physical 1 cm size */
                height: 2cm !important; /* Physical 1 cm size */
                min-width: 2cm !important;
                min-height: 2cm !important;
                font-size: 6px !important;
                border: 1px solid #000 !important;
                margin: 8px auto !important;
            }
            
            .qr-label {
                font-size: 9px !important;
                margin-bottom: 6px !important;
            }
            
            .qr-footer {
                font-size: 7px !important;
            }
        }
    </style>
</head>
<body>
    <br><br>

    <table style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px;">
        <tr>
            <!-- Left: Logo -->
            <td rowspan="3" style="width: 15%; text-align: center; border: 1px solid #000; border-right: none;">
                <img src="{{ asset('storage/logo/DOLE.png') }}" 
                     alt="DOLE Logo" 
                     class="dole-logo"
                     style="margin: 5px auto; display: block;">
            </td>

            <!-- Middle: Header -->
            <td rowspan="3" style="width: 65%; text-align: center; border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none; padding: 4px 0;">
                <br>
                <strong style="font-size: 16px;">DEPARTMENT OF LABOR AND EMPLOYMENT XI</strong><br><br><br><br><br><br><br><br>
                <strong style="font-size: 20px;">DISBURSEMENT VOUCHER</strong>
            </td>

            <!-- Right: Fund Cluster -->
            <td style="width: 20%; border: 1px solid #000; padding: 4px 6px;">
                Fund Cluster :
                <br><br><br>
            </td>
        </tr>
        <tr>
            <!-- Right: DV No. -->
            <td style="border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 6px;">
                Date: <strong>{{ $data['date'] ?? '_____________' }}</strong><br><br>
                DV No.: <strong>{{ $data['dv_no'] ?? '_____________' }}</strong>
                <br>
                <div class="qr-label" style="text-align:center">SCAN FOR UPDATE STATUS</div>

                @if(isset($qrCode) && $qrCode)
                    <img src="{{ $qrCode }}" alt="DV QR Code" class="qr-image">
                @else
                    <div class="qr-code-placeholder">QR<br>CODE</div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Mode of Payment -->
    <table>
        <tr>
            <td class="bold" style="width: 15%;">Mode of Payment</td>
            <td style="width: 15%;">
                <span class="checkbox {{ ($data['mode_of_payment'] ?? '') == 'MDS Check' ? 'checked' : '' }}">
                    {{ ($data['mode_of_payment'] ?? '') == 'MDS Check' ? '✓' : '' }}
                </span> MDS Check
            </td>
            <td style="width: 20%;">
                <span class="checkbox {{ ($data['mode_of_payment'] ?? '') == 'Commercial Check' ? 'checked' : '' }}">
                    {{ ($data['mode_of_payment'] ?? '') == 'Commercial Check' ? '✓' : '' }}
                </span> Commercial Check
            </td>
            <td style="width: 15%;">
                <span class="checkbox {{ ($data['mode_of_payment'] ?? '') == 'ADA' ? 'checked' : '' }}">
                    {{ ($data['mode_of_payment'] ?? '') == 'ADA' ? '✓' : '' }}
                </span> ADA
            </td>
            <td>
                <span class="checkbox {{ !in_array($data['mode_of_payment'] ?? '', ['MDS Check', 'Commercial Check', 'ADA']) && ($data['mode_of_payment'] ?? '') ? 'checked' : '' }}">
                    {{ !in_array($data['mode_of_payment'] ?? '', ['MDS Check', 'Commercial Check', 'ADA']) ? '✓' : '' }}
                </span>
                Others: {{ !in_array($data['mode_of_payment'] ?? '', ['MDS Check', 'Commercial Check', 'ADA']) ? $data['mode_of_payment'] : '___________' }}
            </td>
        </tr>
    </table>

    <!-- Payee Information -->
    <table>
        <tr>
            <td class="bold" style="width: 5%; height: 30px; font-size: 12px;">Payee</td>
            <td style="width: 38%; font-size: 12px;" colspan="2" >
                <strong>{{ strtoupper($data['payee'] ?? 'NAME OF PAYEE') }}</strong>
            </td>
            <td style="width: 30%; font-size: 12px;">
                TIN/Employee No.: <br> <strong>{{ $data['tin'] ?? '___________' }}</strong>
            </td>
            <td style="width: 18%; font-size: 12px;"> 
                ORS/BURS No.: <br><strong>{{ $data['ors_number'] ?? '___________' }}</strong>
            </td>
        </tr>
        <tr>
            <td style="font-size: 12px;" class = "bold">Address</td>
            <td colspan="4" style="font-size: 12px;">
                <strong>{{ strtoupper($data['address'] ?? 'DAVAO CITY') }}</strong>
            </td>
        </tr>
    </table>

    <!-- Particulars Section -->
    <table>
        <tr>
            <td class="center bold" style="width: 30%; font-size:13px; background-color: #f5f5f5;">Particulars</td>
            <td class="center bold" style="width: 20%; font-size:13px; background-color: #f5f5f5;">Responsibility Center</td>
            <td class="center bold" style="width: 20%; font-size:13px; background-color: #f5f5f5;">MFO/PAP</td>
            <td class="center bold" style="width: 20%; font-size:13px; background-color: #f5f5f5;">Amount</td>
        </tr>
        <tr style="height: 275px;">
            <td class="particulars-section" style="vertical-align: top; padding: 10px; font-size: 12px">
                <br>
                <div style="white-space: pre-wrap;" >{{ $data['particulars'] ?? '' }}</div>
                <br><br>
                <table style="border: none; margin-top: 10px;" cellpadding="2">
                    <tr style="border: none;">
                        <td class="no-border" colspan="2"></td>
                        <td class="no-border" style="border-top: 1px solid #000; border-bottom: 3px double #000; font-weight: bold;"></td>
                    </tr>
                </table>
            </td>
            <td style="text-align: center; font-size: 12px;">
                <br>
                <strong>{{ $data['respocenter_code'] ?? '' }}</strong><br>
                {{ $data['respocenter_desc'] ?? '' }}<br>
            </td>
            <td style="text-align: center; font-size: 12px;">
                <br>
                @if(isset($data['mfocode']) && $data['mfocode'])
                    <strong>{{ $data['mfocode'] }}</strong><br>
                    {{ $data['mfodescription'] ?? '' }}
                @else
                    <em>Not yet obligated</em>
                @endif
            </td>
            <td style="vertical-align: bottom; text-align: right; padding-bottom: 10px;">
                <strong>₱{{ $data['amount'] ?? '-' }}</strong>
            </td>
        </tr>
    </table>

    <!-- Section A -->
    <table>
        <tr>
            <td style="width: 30px;" class="bold center border-right">A.</td>
            <td class="bold">
                Certified: Expenses/Cash Advance necessary, lawful and incurred under my direct supervision.
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="signature-section center">
                <div class="signature-line"></div>
                <div class="bold">{{ $imsd_head ?? 'NAME OF IMSD HEAD' }}</div>
                <div class="small">HEAD, IMSD</div>
            </td>
        </tr>
    </table>

    <!-- Section B - Accounting Entry -->
    <table>
        <tr>
            <td style="width: 30px;" class="bold center border-right">B.</td>
            <td class="bold">Accounting Entry:</td>
        </tr>
    </table>

    <table class="accounting-table">
        <tr>
            <th style="width: 40%;">Account Title</th>
            <th style="width: 25%;">UACS Code</th>
            <th style="width: 17.5%;">Debit</th>
            <th style="width: 17.5%;">Credit</th>
        </tr>
        
        @php
            $uacsCodes = $uacsCodes ?? collect();
        @endphp

        @if($uacsCodes->count() > 0)
            @foreach($uacsCodes as $index => $uacs)
            <tr>
                <td>{{ $uacs->description ?? 'N/A' }}</td>
                <td>{{ $uacs->uacscode ?? 'N/A' }}</td>
                 <td>&nbsp;</td>
                  <td>&nbsp;</td>
            </tr>
            @endforeach
            
        @else
            <!-- Default entries when no UACS codes are assigned -->
            <tr>
                <td>Rents - Buildings and Structures</td>
                <td>3829980501 (782)</td>
                <td></td>
                <td style="text-align: right;">₱{{ number_format($totalAmount, 2) ?? '-' }}</td>
            </tr>
            <tr>
                <td>Due to BIR</td>
                <td>20210110004123</td>
                <td></td>
                <td style="text-align: right;">-</td>
            </tr>
            <tr>
                <td>Cash - MDS, Regular</td>
                <td>10180408001080</td>
                <td></td>
                <td style="text-align: right;">-</td>
            </tr>
        @endif

                  
        <tr>
            <td colspan="4" style="height: 20px;"></td>
        </tr>
    </table>

    <!-- Sections C and D -->
    <table>
        <tr>
            <td class="section-label">C.</td>
            <td class="bold" style="width: 47%;">Certified:</td>
            <td class="section-label">D.</td>
            <td class="bold" style="width: 47%;">Approved for Payment:</td>
        </tr>
        <tr>
            <td></td>
            <td style="vertical-align: top;">
                <div style="margin: 5px 0;">
                    <span class="checkbox"></span> Cash available
                </div>
                <div style="margin: 5px 0;">
                    <span class="checkbox"></span> Subject to Authority to Debit Account<br>
                    <span style="margin-left: 20px;">(when applicable)</span>
                </div>
                <div style="margin: 5px 0;">
                    <span class="checkbox"></span> Supporting documents complete and<br>
                    <span style="margin-left: 20px;">amount claimed proper</span>
                </div>
            </td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: 1px solid #000; padding: 4px; width: 20%; text-align: center; vertical-align: middle;">
                            <span class="small">Signature</span>
                        </td>
                        <td style="border: 1px solid #000; border-left: none; padding: 20px; width: 80%;">
                            
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; border-top: none; padding: 4px; text-align: center; vertical-align: middle;">
                            <span class="small">Printed Name</span>
                        </td>
                        <td style="border: 1px solid #000; border-top: none; border-left: none; padding: 4px; text-align: center;">
                            <strong>{{ strtoupper($accountant_name ?? 'NAME OF ACCOUNTANT') }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; border-top: none; padding: 4px; text-align: center; vertical-align: middle;">
                            <span class="small">Position</span>
                        </td>
                        <td style="border: 1px solid #000; border-top: none; border-left: none; padding: 4px; text-align: center;">
                            <strong>Accountant III</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; border-top: none; padding: 4px; text-align: center; vertical-align: middle;">
                            <span class="small">Date</span>
                        </td>
                        <td style="border: 1px solid #000; border-top: none; border-left: none; padding: 4px;">
                            
                        </td>
                    </tr>
                </table>
            </td>
            <td></td>
            <td>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: 1px solid #000; padding: 4px; width: 20%; text-align: center; vertical-align: middle;">
                            <span class="small">Signature</span>
                        </td>
                        <td style="border: 1px solid #000; border-left: none; padding: 20px; width: 80%;">
                            
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; border-top: none; padding: 4px; text-align: center; vertical-align: middle;">
                            <span class="small">Printed Name</span>
                        </td>
                        <td style="border: 1px solid #000; border-top: none; border-left: none; padding: 4px; text-align: center;">
                            <strong>{{ strtoupper($regional_director ?? 'NAME OF REGIONAL DIRECTOR') }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; border-top: none; padding: 4px; text-align: center; vertical-align: middle;">
                            <span class="small">Position</span>
                        </td>
                        <td style="border: 1px solid #000; border-top: none; border-left: none; padding: 4px; text-align: center;">
                            <strong>Regional Director</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; border-top: none; padding: 4px; text-align: center; vertical-align: middle;">
                            <span class="small">Date</span>
                        </td>
                        <td style="border: 1px solid #000; border-top: none; border-left: none; padding: 4px;">
                            
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="bold center section-label">E.</td>
            <td class="bold" colspan="3" style="padding: 8px; background-color: #e5e7eb;">Receipt of Payment</td>
            <td class="bold" style="width: 22%; padding: 8px; background-color: #e5e7eb;" rowspan="2">
                <div>JEV No.</div>
            </td>
        </tr>
        <tr>
            <td></td>
            <td style="width: 20%; padding: 8px;"><strong>Check/ADA No.:</strong></td>
            <td style="width: 18%; padding: 8px;"><strong>Date:</strong></td>
            <td style="width: 35%; padding: 8px;">
                <strong>Bank Name:</strong> {{ $bank_name ?? '___________' }}<br>
                <strong>Account No.:</strong> {{ $bank_accnum ?? '___________' }}<br>
            </td>
        </tr>
        <tr>
            <td></td>
            <td style="padding: 8px; background-color: #f3f4f6;"><strong>Signature:</strong></td>
            <td style="padding: 8px; background-color: #f3f4f6;"><strong>Date:</strong></td>
            <td style="padding: 8px; background-color: #f3f4f6;"><strong>Printed Name: {{ strtoupper($payee ?? 'NAME OF PAYEE') }} </strong></td>
            <td style="padding: 8px; background-color: #f3f4f6;"><strong>Date:</strong></td>
        </tr>

        <tr>
            <td colspan="5">
                <strong>Official Receipt No. & Date / Other Supporting Documents:</strong>
                <div style="border-top: 1px solid #000; margin-top: 8px;"></div>
            </td>
        </tr>
    </table>
</body>
</html>