<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disbursement Voucher</title>
    <style>
        @page { size: A4; margin: 10mm; }
        body { 
            font-family: Arial, sans-serif; 
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
        }
        
        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin-right: 3px;
            vertical-align: middle;
            text-align: center;
            line-height: 12px;
            font-size: 10px;
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
        }
        
        input[type="text"] {
            border: none;
            border-bottom: 1px solid #000;
            width: 100%;
            font-family: Arial, sans-serif;
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
        <tr>
            <td rowspan="3" class="logo-cell">
                <div class="logo-placeholder">DOLE<br>LOGO</div>
            </td>
            <td class="center" style="width: 60%;">
                Republic of the Philippines<br>
                <strong style="font-size: 11px;">DEPARTMENT OF LABOR AND EMPLOYMENT XI</strong>
            </td>
            <td style="width: 20%;">
                Fund Cluster: ___________
            </td>
        </tr>
        <tr>
            <td class="center" style="font-size: 13px; font-weight: bold;">
                DISBURSEMENT VOUCHER
            </td>
            <td>
                Date: ___________
            </td>
        </tr>
        <tr>
            <td class="no-border"></td>
            <td>
                DV No.: ___________
            </td>
        </tr>
    </table>

    <!-- Mode of Payment -->
    <table>
        <tr>
            <td class="bold" style="width: 15%;">Mode of Payment</td>
            <td style="width: 15%;"><span class="checkbox">☑</span> MDS Check</td>
            <td style="width: 20%;"><span class="checkbox">☐</span> Commercial Check</td>
            <td style="width: 15%;"><span class="checkbox">☐</span> ADA</td>
            <td><span class="checkbox">☐</span> Others (Please specify) ___________</td>
        </tr>
    </table>

    <!-- Payee Information -->
    <table>
        <tr>
            <td class="bold" style="width: 12%;">Payee</td>
            <td style="width: 38%;" colspan="2">NAME OF PAYEE</td>
            <td style="width: 18%;">TIN/Employee No.:</td>
            <td style="width: 32%;">ORS/BURS No.:</td>
        </tr>
        <tr>
            <td class="bold">Address</td>
            <td colspan="4">DAVAO CITY</td>
        </tr>
    </table>

    <!-- Particulars Section -->
    <table>
        <tr>
            <td class="center bold" style="width: 40%; background-color: #f5f5f5;">Particulars</td>
            <td class="center bold" style="width: 20%; background-color: #f5f5f5;">Responsibility Center</td>
            <td class="center bold" style="width: 20%; background-color: #f5f5f5;">MFO/PAP</td>
            <td class="center bold" style="width: 20%; background-color: #f5f5f5;">Amount</td>
        </tr>
        <tr>
            <td class="particulars-section">
                <br><br>
                <table style="border: none; margin-top: 10px;" cellpadding="2">
                    <tr style="border: none;">
                        <td class="no-border" colspan="2"></td>
                        <td class="no-border" style="border-top: 1px solid #000; border-bottom: 3px double #000; font-weight: bold;"></td>
                    </tr>
                </table>
            </td>
            <td style="text-align: center;">16-001-03<br>0001-0003<br>IMSD</td>
            <td></td>
            <td style="vertical-align: bottom; text-align: right; padding-bottom: 10px;">-</td>
        </tr>
    </table>

    <!-- Section A -->
    <table>
        <tr>
            <td style="width: 30px;" class="bold center">A.</td>
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
            <td style="width: 30px;" class="bold center">B.</td>
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
            <td>Rents - Buildings and Structures</td>
            <td>3829980501 (782)</td>
            <td></td>
            <td style="text-align: right;">-</td>
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
        <tr>
            <td colspan="4" style="height: 20px;"></td>
        </tr>
    </table>

    <!-- Sections C and D -->
    <table>
        <tr>
            <td style="width: 30px;" class="bold center border-right">C.</td>
            <td class="bold border-right" style="width: 48%;">Certified:</td>
            <td style="width: 30px;" class="bold center">D.</td>
            <td class="bold" style="width: 48%;">Approved for Payment</td>
        </tr>
        <tr>
            <td class="border-right"></td>
            <td class="border-right" style="padding: 5px;">
                <div><span class="checkbox">☐</span> Cash available</div>
                <div style="margin-top: 5px;"><span class="checkbox">☐</span> Subject to Authority to Debit Account (when applicable)</div>
                <div style="margin-top: 5px;"><span class="checkbox">☐</span> Supporting documents complete and amount claimed</div>
                <div style="margin-left: 18px;">proper</div>
            </td>
            <td colspan="2" rowspan="2"></td>
        </tr>
        <tr>
            <td class="border-right"></td>
            <td class="signature-section center border-right">
                <div class="signature-line"></div>
                <div style="margin-top: 8px;">
                    <table style="border: none; width: 100%;">
                        <tr style="border: none;">
                            <td class="no-border center" style="width: 50%;">
                                <div style="border-bottom: 1px solid #000; margin: 0 10px 2px 10px; padding-top: 2px;">Signature</div>
                                <div class="small">Printed Name</div>
                            </td>
                            <td class="no-border center" style="width: 50%;">
                                 <div class="bold">{{ $accountant_name ?? 'NAME OF ACCOUNTANT' }}</div>
                                <div class="small">Accountant III</div>
                            </td>
                        </tr>
                        <tr style="border: none;">
                            <td class="no-border center">
                                <div style="border-bottom: 1px solid #000; margin: 0 10px 2px 10px; padding-top: 2px;">Position</div>
                            </td>
                            <td class="no-border center">
                                <div style="border-bottom: 1px solid #000; margin: 0 10px 2px 10px; padding-top: 2px;">Position</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="border-right"></td>
            <td colspan="2" class="signature-section center">
                <div class="signature-line"></div>
                <div style="margin-top: 8px;">
                    <table style="border: none; width: 100%;">
                        <tr style="border: none;">
                            <td class="no-border center" style="width: 50%;">
                                <div style="border-bottom: 1px solid #000; margin: 0 10px 2px 10px; padding-top: 2px;">Signature</div>
                                <div class="small">Printed Name</div>
                            </td>
                            <td class="no-border center" style="width: 50%;">
                                <div class="bold">{{ $regional_director ?? 'NAME OF REGIONAL DIRECTOR' }}</div>
                                <div class="small">Regional Director</div>
                            </td>
                        </tr>
                        <tr style="border: none;">
                            <td class="no-border center">
                                <div style="border-bottom: 1px solid #000; margin: 0 10px 2px 10px; padding-top: 2px;">Position</div>
                            </td>
                            <td class="no-border center">
                                <div style="border-bottom: 1px solid #000; margin: 0 10px 2px 10px; padding-top: 2px;">Date</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Section E - Receipt of Payment -->
    <table>
        <tr>
            <td class="bold center" style="width: 30px;">E.</td>
            <td class="bold" colspan="3">Receipt of Payment</td>
            <td class="center bold" rowspan="3" style="width: 20%;">JEV No.</td>
        </tr>
        <tr>
            <td></td>
            <td style="width: 25%;">
                Check/ADA No.: _____________
            </td>
            <td style="width: 25%;">Date: _____________</td>
            <td style="width: 25%;">
                Bank Name & Account Number:<br>
                _________________________
            </td>
        </tr>
        <tr>
            <td></td>
            <td>Signature: _____________</td>
            <td colspan="2">Date: _____________</td>
        </tr>
        <tr>
            <td colspan="5" style="padding: 8px;">
                Official Receipt No. & Date Other Documents: _______________________________________________
            </td>
        </tr>
    </table>

    <div class="no-print" style="margin-top: 20px; text-align: center; padding: 10px; background: #f0f0f0; border-radius: 5px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
            Print Voucher
        </button>
    </div>

</body>
</html>