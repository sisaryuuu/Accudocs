<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Disbursement Voucher</title>
    <style>
        @page { 
            size: Legal; 
            margin: 0mm; 
        }
            body { 
            font-family: "Times New Roman", Times, serif;
            font-size: 10px;
            margin: 0; 
            padding: 15px;
            line-height: 1.2;
        }
        /* Special font for symbols only */
        .symbol {
            font-family: "Arial Unicode MS", "Times New Roman", serif;
        }
        
        .checkbox {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1.4px solid #000;
            margin-right: 4px;
            text-align: center;
            line-height: 12px;
            font-size: 10px; 
            font-weight: bold;
            background-color: #fff;
            font-family: "DejaVu Sans", "Arial Unicode MS", "Times New Roman", serif;
        }
        .checkbox.checked {
        background-color: #000;
        color: #fff;
    }
        .checkbox.checked::before {
            content: "✓";
        }
    
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-bottom: 0;
            word-wrap: break-word;
        }
        
        td, th { 
            border: 1px solid #000; 
            padding: 4px 6px;
            vertical-align: top;
            font-weight: normal;
        }
        
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .small { font-size: 9px; }
        
        .signature-line {
            border-top: 1px solid #000;
            width: 70%;
            margin: 35px auto 3px auto;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <tr>
            <!-- Left: Logo -->
            <td rowspan="2" style="width: 15%; text-align: center; border: 1px solid #000; border-right: none;">
            <img src="{{ $logo_url ?? asset('storage/logo/DOLE.png') }}"
                alt="DOLE Logo"
                style="display: block; margin: 5px auto; width: 120px; height: auto; max-height: 120px;">
        </td>

            <td rowspan="2" style="width: 65%; text-align: center; border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none; padding: 0;">
                <br>
                <strong style="font-size: 16px;">DEPARTMENT OF LABOR AND EMPLOYMENT XI</strong><br><br><br><br>
                <strong style="font-size: 20px;">DISBURSEMENT VOUCHER</strong>
                <br><br><br>
            </td>
            <td style="width: 20%; border: 1px solid #000; padding: 4px 6px; vertical-align: top;">
                <strong>Fund Cluster:</strong>
                <br><br>
            </td>
        </tr>
        <tr>
            <td style="border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 6px; vertical-align: top;">
                <strong>Date:</strong> {{ $data['date'] ?? '_____________' }}<br><br><br>
                <strong>DV No.:</strong> {{ $data['dv_no'] ?? '_____________' }}
            </td>
        </tr>
    </table>

    <!-- Mode of Payment with Fixed Checkboxes -->
    <table>
        <tr>
            <td class="bold" style="width: 15%;">Mode of Payment</td>
            <td style="width: 15%;">
                <span class="checkbox {{ ($mode_of_payment ?? '') == 'MDS Check' ? 'checked' : '' }}"></span> MDS Check
            </td>
            <td style="width: 20%;">
                <span class="checkbox {{ ($mode_of_payment ?? '') == 'Commercial Check' ? 'checked' : '' }}"></span> Commercial Check
            </td>
            <td style="width: 15%;">
                <span class="checkbox {{ ($mode_of_payment ?? '') == 'ADA' ? 'checked' : '' }}"></span> ADA
            </td>
            <td>
                <span class="checkbox {{ !in_array($mode_of_payment ?? '', ['MDS Check', 'Commercial Check', 'ADA']) && ($mode_of_payment ?? '') ? 'checked' : '' }}"></span>
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
    <table style="height: 300px;">
        <tr>
            <td class="center bold" style="width: 30%; background-color: #f5f5f5;">Particulars</td>
            <td class="center bold" style="width: 20%; background-color: #f5f5f5;">Responsibility Center</td>
            <td class="center bold" style="width: 20%; background-color: #f5f5f5;">MFO/PAP</td>
            <td class="center bold" style="width: 20%; background-color: #f5f5f5;">Amount</td>
        </tr>
        <tr style="height: 275px;">
            <td style="width: 30%; vertical-align: top; padding: 10px; height: 275px;">
                <div style="white-space: pre-wrap; height: 200px; overflow: hidden;">{{ $particulars ?? '' }}</div>
            </td>
            <td style="width: 20%; padding: 10px; height: 275px; text-align: center;">
                <strong>{{ $respocenter_code ?? '' }}</strong><br>
                {{ $respocenter_desc ?? '' }}
            </td>
            <td style="width: 20%; padding: 10px; height: 275px; text-align: center;">
                <strong>{{ $program_code ?? '' }}</strong><br>
                {{ $program_desc ?? '' }}
            </td>
          <td style="width: 20%; vertical-align: bottom; text-align: right; padding: 10px; height: 275px;">
            <strong><span style="font-family: DejaVu Sans, sans-serif;">₱</span> {{ $amount ?? '-' }}</strong>
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