<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disbursement Voucher</title>
    <style>
        @page { size: Legal; margin: 0mm; }
        body { 
            font-family: "Times New Roman", Times, serif; 
            font-size: 10px;
            margin: 0; 
            padding: 15px;
            line-height: 1.2;
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
        
        .header-table td {
            font-weight: bold;
            font-size: 9px;
        }
        
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .small { font-size: 9px; }
        .no-border { border: none !important; }
        .border-right { border-right: 1px solid #000; }
        
        .logo-cell {
            width: 70px;
            text-align: center;
            padding: 5px;
        }
        
        .logo-placeholder {
            width: 50px;
            height: 50px;
            background: #e0e0e0;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #666;
            font-family: "Times New Roman", Times, serif;
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
        border-radius: 2px; /* Slightly rounded corners for clarity */
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
        
        input[type="text"] {
            border: none;
            border-bottom: 1px solid #000;
            width: 100%;
            font-family: "Times New Roman", Times, serif;
            font-size: 10px;
        }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr style="border-bottom: none;">
           <td rowspan="2" style="width: 15%; text-align: center; border-right: none;">
            <img src="{{ asset('storage/logo/DOLE.png') }}" 
                alt="DOLE Logo" 
                style="width: 100px; height: auto; display: block; margin: 0 auto;">
        </td>
        
            <td class="center no-inner-border" style="width: 60%; border-bottom: none;">
                <br>
                Republic of the Philippines<br><br>
                <strong style="font-size: 11px;">DEPARTMENT OF LABOR AND EMPLOYMENT XI</strong><br><br>
                <strong style="font-size: 12px; text-decoration: underline;">DISBURSEMENT VOUCHER</strong><br><br>
            </td>
            <td style="width: 20%;">
                Fund Cluster: ___________
            </td>
        </tr>
        <tr>
            <td class="no-border"></td>
            <td>
                Date: <strong>{{ $date ?? '_____________' }}</strong><br><br>
                DV No.: <strong>{{ $dv_no ?? '_____________' }}</strong>
            </td>
        </tr>
    </table>

    <!-- Mode of Payment -->
    <table>
        <tr>
            <td class="bold" style="width: 15%;">Mode of Payment</td>
          <td style="width: 15%;">
    <span class="checkbox {{ ($mode_of_payment ?? '') == 'MDS Check' ? 'checked' : '' }}">
        {{ ($mode_of_payment ?? '') == 'MDS Check' ? '✓' : '' }}
    </span> MDS Check
</td>
<td style="width: 20%;">
    <span class="checkbox {{ ($mode_of_payment ?? '') == 'Commercial Check' ? 'checked' : '' }}">
        {{ ($mode_of_payment ?? '') == 'Commercial Check' ? '✓' : '' }}
    </span> Commercial Check
</td>
<td style="width: 15%;">
    <span class="checkbox {{ ($mode_of_payment ?? '') == 'ADA' ? 'checked' : '' }}">
        {{ ($mode_of_payment ?? '') == 'ADA' ? '✓' : '' }}
    </span> ADA
</td>
<td>
    <span class="checkbox {{ !in_array($mode_of_payment ?? '', ['MDS Check', 'Commercial Check', 'ADA']) && ($mode_of_payment ?? '') ? 'checked' : '' }}">
        {{ !in_array($mode_of_payment ?? '', ['MDS Check', 'Commercial Check', 'ADA']) ? '✓' : '' }}
    </span>
    Others: {{ !in_array($mode_of_payment ?? '', ['MDS Check', 'Commercial Check', 'ADA']) ? $mode_of_payment : '___________' }}
</td>
        </tr>
    </table>

    <!-- Payee Information -->
    <table>
        <tr>
            <td class="bold" style="width: 5%; height: 30px;">Payee</td>
            <td style="width: 38%;" colspan="2">
                <strong>{{ strtoupper($payee ?? 'NAME OF PAYEE') }}</strong>
            </td>
            <td style="width: 30%;">
                TIN/Employee No.: <strong>{{ $tin ?? '___________' }}</strong>
            </td>
            <td style="width: 18%;">
                ORS/BURS No.: <strong>{{ $ors_no ?? '___________' }}</strong>
            </td>
        </tr>
        <tr>
            <td class="bold">Address</td>
            <td colspan="4">
                <strong>{{ strtoupper($address ?? 'DAVAO CITY') }}</strong>
            </td>
        </tr>
    </table>

    <!-- Particulars Section -->
    <table>
        <tr>
            <td class="center bold" style="width: 30%; background-color: #f5f5f5;">Particulars</td>
            <td class="center bold" style="width: 20%; background-color: #f5f5f5;">Responsibility Center</td>
            <td class="center bold" style="width: 20%; background-color: #f5f5f5;">MFO/PAP</td>
            <td class="center bold" style="width: 20%; background-color: #f5f5f5;">Amount</td>
        </tr>
        <tr style="height: 275px;">
            <td class="particulars-section" style="vertica  l-align: top; padding: 10px;">
                <div style="white-space: pre-wrap;">{{ $particulars ?? '' }}</div>
                <br><br>
                <table style="border: none; margin-top: 10px;" cellpadding="2">
                    <tr style="border: none;">
                        <td class="no-border" colspan="2"></td>
                        <td class="no-border" style="border-top: 1px solid #000; border-bottom: 3px double #000; font-weight: bold;"></td>
                    </tr>
                </table>
            </td>
            <td style="text-align: center;">
                <br>
                <strong>{{ $respocenter_code }}</strong><br>
                {{ $respocenter_desc}}<br>
            </td>
            <td style="text-align: center;">
            </td>
            <td style="vertical-align: bottom; text-align: right; padding-bottom: 10px;">
                <strong>₱{{ $amount ?? '-' }}</strong>
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
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td style="text-align: right;"></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td style="text-align: right;"></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td style="text-align: right;"></td>
        </tr>
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
                <div >JEV No.</div>
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