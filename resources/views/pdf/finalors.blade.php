<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Obligation Request and Status</title>
    <style>
        @page { 
            size: Legal; 
            margin: 0mm; 
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11px;
            margin: 0;
            padding: 15px;
            line-height: 1.2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        td, th {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .section-title {
            text-align: center;
            font-weight: bold;
            background-color: #f5f5f5;
        }
        .compact-table {
            margin-bottom: 0;
            border-spacing: 0;
        }
        .no-bottom-margin {
            margin-bottom: 0 !important;
        }
        .tight-table {
            border-collapse: collapse;
            border-spacing: 0;
        }
        
        /* Header specific styles */
        .header-table td {
            vertical-align: middle;
            padding: 8px;
        }
        .org-info {
            text-align: center;
            line-height: 1.5;
        }
        .org-info .small-text {
            font-size: 14px;
        }
        .org-info .dept-name {
            font-weight: bold;
            font-size: 16px;
        }
        .org-info .red-text {
            font-size: 12px;
        }
        .section-header {
            text-align: center;
            font-weight: bold;
            padding: 6px 0;
            font-size: 11px;
        }
        
        /* Peso symbol styling */
        .peso-symbol {
            display: inline-block;
            width: 12px;
            height: 16px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='16' viewBox='0 0 12 16'%3E%3Cpath d='M0 2h2V0h6v2h2v2h2v6h-2v2H8v2H2v-2H0v-2h2V4H0V2zm4 4v4h4V6H4z' fill='black'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            margin-right: 4px;
            vertical-align: middle;
        }
        
        @media print {
            body { 
                margin: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>

   <!-- Header Table -->
<table class="header-table compact-table no-bottom-margin tight-table">
    <tr>
        <td style="width: 12%; text-align: left; border: 1px solid #000; border-right: none; padding: 10px;">
          <img src="{{ public_path('storage/logo/DOLE.png') }}" alt="DOLE Logo"  style="display: block; margin: 5px auto; width: 120px; height: auto; max-height: 120px;">
        </td>
        <td style="width: 88%; border: 1px solid #000; border-left: none; padding: 10px;">
            <div class="org-info">
                <span class="small-text">Republic of the Philippines</span><br>
                <span class="dept-name">DEPARTMENT OF LABOR AND EMPLOYMENT</span><br>
                <span class="red-text">Regional Office No. XI</span><br>
                <span class="small-text">Davao City</span>
            </div>
        </td>
    </tr>
</table>

<!-- Title Section -->
<table class="compact-table no-bottom-margin tight-table">
    <tr>
        <td style="width: 12%; text-align: left; border: 1px solid #000; border-right: none; border-top: none; padding: 10px;">
            <!-- Empty cell -->
        </td>
        <td class="section-header" style="border: 1px solid #000; border-left: none; border-top: none;">
            OBLIGATION REQUEST AND STATUS
        </td>
    </tr>
</table>

    <!-- Second Table - Payee Information -->
    <table class="compact-table no-bottom-margin tight-table">
        <tr>
            <td style="width: 70%; border: 1px solid #000; padding: 2px 3px;">
                <div style="min-height: 40px; padding-top: 8px;">
                    NAME OF PAYEE: {{ strtoupper($data['payee'] ?? '') }}
                </div>
            </td> 
            <td style="width: 30%; border: 1px solid #000; padding: 2px 3px;">
                No.: {{ $data['ors_no'] ?? '____________' }} 
                <br><br>
                Date: {{ $data['date'] ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 2px 3px;" colspan="2">
                Address: <b>{{ strtoupper($data['address'] ?? '') }}</b>
            </td>
        </tr>
    </table>
<!-- Third Table - Particulars -->
<table class="compact-table no-bottom-margin tight-table">
    <tr class="section-title">
        <td style="width: 20%; border: 1px solid #000; padding: 2px 3px;">Responsibility <br> Center</td>
        <td style="width: 30%; border: 1px solid #000; padding: 2px 3px;">Particulars</td>
        <td style="width: 25%; border: 1px solid #000; padding: 2px 3px;">MFO/P.P.A</td>
        <td style="width: 30%; border: 1px solid #000; padding: 2px 3px;">UACS / Expenditure</td>
        <td style="width: 15%; border: 1px solid #000; padding: 2px 3px;">Amount</td>
    </tr>
    
    <tr>
        <td style="border: 1px solid #000; padding: 2px 3px; text-align:center; vertical-align: top; height: 275px;">
            <div style="height: 275px; display: flex; align-items: flex-start; justify-content: center;">
                <strong>{{ $data['responsibility_center'] ?? '' }}</strong>
            </div>
        </td>
        <td style="border: 1px solid #000; padding: 2px 3px; vertical-align: top; height: 275px;">
            <div style="height: 275px; display: flex; align-items: flex-start;">
                {{ $data['particulars'] ?? '' }}
            </div>
        </td>
        <td style="border: 1px solid #000; padding: 2px 3px; text-align:center; vertical-align: top; height: 275px;">
            <div style="height: 275px; display: flex; flex-direction: column; align-items: center; justify-content: flex-start;">
                {{ $data['mfo'] ?? '' }}
                @if(isset($data['adl_codes']) && $data['adl_codes'] != '—')
                    <br>
                    <span style="font-size: 10px; white-space: pre-line;">{{ $data['adl_codes'] ?? '' }}</span>
                @endif
            </div>
        </td>
            <td style="border: 1px solid #000; padding: 2px 3px; vertical-align: top; height: 275px;">
        <div style="height: 275px; display: flex; flex-direction: column; justify-content: flex-start;">
            @if(isset($data['uacs_items']) && count($data['uacs_items']) > 0)
                @foreach($data['uacs_items'] as $item)
                    <div style="padding: 2px 0;">
                        {{ $item['code'] }} - {{ $item['description'] }}
                    </div>
                @endforeach
            @else
                <div>—</div>
            @endif
        </div>
    </td>
    <td style="border: 1px solid #000; padding: 2px 3px; text-align: right; vertical-align: top; height: 275px;">
        <div style="height: 275px; display: flex; flex-direction: column; justify-content: flex-start;">
            @if(isset($data['uacs_items']) && count($data['uacs_items']) > 0)
                @foreach($data['uacs_items'] as $item)
                    <div style="padding: 2px 0;">
                       <span style="font-family: DejaVu Sans, sans-serif;">₱</span>{{ number_format($item['amount'], 2) }}
                    </div>
                @endforeach
            @else
                <div>—</div>
            @endif
        </div>
    </td>
    </tr>
    
    <!-- Total Row -->
    <tr>
        <td colspan="4" class="right bold" style="border: 1px solid #000; padding: 2px 3px;">TOTAL</td>
        <td class="center bold" style="border: 1px solid #000; padding: 2px 3px;">
            <span style="font-family: DejaVu Sans, sans-serif;">₱</span>{{ number_format($data['amount'] ?? 0, 2) }}
        </td>
    </tr>
</table>
    
<!-- Certification Section - Structured like Sections C and D -->
<table class="compact-table no-bottom-margin tight-table">
    <tr>
        <td class="section-label" style="width: 3%; border: 1px solid #000; padding: 2px;"></td>
        <td class="bold" style="width: 47%; border: 1px solid #000; padding: 2px;"></td>
        <td class="section-label" style="width: 3%; border: 1px solid #000; padding: 2px;">B.</td>
        <td class="bold" style="width: 47%; border: 1px solid #000; padding: 2px;">Funds Available:</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000;"></td>
        <td style="border: 1px solid #000; padding: 3px; vertical-align: top;">
            <div style="margin: 3px 0;">
                ( &nbsp; &nbsp; &nbsp; ) Charges to appropriation/allotment necessary,
            </div>
            <div style="margin: 3px 0; margin-left: 15px;">
                lawful and under my direct supervision.
            </div>
            <div style="margin: 3px 0;">
                ( &nbsp; &nbsp; &nbsp; ) Supporting documents valid, proper and legal.
            </div>
        </td>
        <td style="border: 1px solid #000;"></td>
        <td style="border: 1px solid #000; padding: 3px; vertical-align: top;">
            <div style="margin: 3px 0;">
                ( &nbsp; &nbsp; &nbsp; ) Appropriation/Allotment available and obligated
            </div>
            <div style="margin: 3px 0; margin-left: 15px;">
                for the purpose as indicated above
            </div>
        </td>
    </tr>
    <tr>
        <td style="border: 1px solid #000;"></td>
        <td style="border: 1px solid #000; padding: 3px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="border: 1px solid #000; padding: 3px; width: 25%; text-align: center; vertical-align: middle;">
                        Signature
                    </td>
                    <td style="border: 1px solid #000; border-left: none; padding: 15px; width: 75%;">
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; border-top: none; padding: 3px; text-align: center; vertical-align: middle;">
                        Printed Name
                    </td>
                    <td style="border: 1px solid #000; border-top: none; border-left: none; padding: 3px; text-align: center;">
                        <strong>{{ strtoupper($imsd_head ?? 'NAME OF IMSD HEAD') }}</strong>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; border-top: none; padding: 3px; text-align: center; vertical-align: middle;">
                        Position
                    </td>
                    <td style="border: 1px solid #000; border-top: none; border-left: none; padding: 3px; text-align: center;">
                        <strong>HEAD, IMSD</strong>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; border-top: none; padding: 3px; text-align: center; vertical-align: middle;">
                        Date
                    </td>
                    <td style="border: 1px solid #000; border-top: none; border-left: none; padding: 3px; text-align: center;">
                       
                    </td>
                </tr>
            </table>
        </td>
        <td style="border: 1px solid #000;"></td>
        <td style="border: 1px solid #000; padding: 3px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="border: 1px solid #000; padding: 3px; width: 25%; text-align: center; vertical-align: middle;">
                        Signature
                    </td>
                    <td style="border: 1px solid #000; border-left: none; padding: 15px; width: 75%;">
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; border-top: none; padding: 3px; text-align: center; vertical-align: middle;">
                        Printed Name
                    </td>
                    <td style="border: 1px solid #000; border-top: none; border-left: none; padding: 3px; text-align: center;">
                        <strong>{{ strtoupper($budget_officer ?? 'BUDGET OFFICER\'S NAME') }}</strong>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; border-top: none; padding: 3px; text-align: center; vertical-align: middle;">
                        Position
                    </td>
                    <td style="border: 1px solid #000; border-top: none; border-left: none; padding: 3px; text-align: center;">
                        <strong>Administrative Officer V/ Budget Officer</strong>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; border-top: none; padding: 3px; text-align: center; vertical-align: middle;">
                        Date
                    </td>
                    <td style="border: 1px solid #000; border-top: none; border-left: none; padding: 3px; text-align: center;">
                        {{ $data['date'] }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
    <!-- Fifth Table - Status of Obligation -->
    <table class="compact-table tight-table">
        <tr class="section-title">
            <td colspan="6" style="border: 1px solid #000; padding: 2px 3px;">C. STATUS OF OBLIGATION</td>
        </tr>
        <tr class="center bold">
            <td style="width: 12%; border: 1px solid #000; padding: 2px 3px;">Date</td>
            <td style="width: 30%; border: 1px solid #000; padding: 2px 3px;">Particulars</td>
            <td style="width: 20%; border: 1px solid #000; padding: 2px 3px;">ORS/JEV/RCI/RADAI No.</td>
            <td style="width: 12%; border: 1px solid #000; padding: 2px 3px;">Obligation</td>
            <td style="width: 13%; border: 1px solid #000; padding: 2px 3px;">Payment</td>
            <td style="width: 13%; border: 1px solid #000; padding: 2px 3px;">Not yet Due / Due & Demandable</td>
        </tr>

        @foreach($status as $row)
        <tr>
            <td class="center" style="border: 1px solid #000; padding: 2px 3px;">{{ $row['date'] }}</td>
            <td style="border: 1px solid #000; padding: 2px 3px;">{{ $row['particulars'] }}</td>
            <td class="center" style="border: 1px solid #000; padding: 2px 3px;">{{ $row['ref_no'] }}</td>
            <td class="center" style="border: 1px solid #000; padding: 2px 3px;"><span style="font-family: DejaVu Sans, sans-serif;">₱</span>{{$row['obligation']}}</td>
            <td class="center" style="border: 1px solid #000; padding: 2px 3px;">{{ $row['payment'] }}</td>
            <td class="center" style="border: 1px solid #000; padding: 2px 3px;">{{ $row['due_status'] }}</td>
        </tr>
        @endforeach
 
        @for($i = count($status); $i < 5; $i++)
        <tr>
            <td style="height: 16px; border: 1px solid #000; padding: 2px 3px;"></td>
            <td style="border: 1px solid #000; padding: 2px 3px;"></td>
            <td style="border: 1px solid #000; padding: 2px 3px;"></td>
            <td style="border: 1px solid #000; padding: 2px 3px;"></td>
            <td style="border: 1px solid #000; padding: 2px 3px;"></td>
            <td style="border: 1px solid #000; padding: 2px 3px;"></td>
        </tr>
        @endfor

        <tr>
            <td colspan="3" class="right bold" style="border: 1px solid #000; padding: 2px 3px;">Total</td>
            <td class="center bold" style="border: 1px solid #000; padding: 2px 3px;"> <span style="font-family: DejaVu Sans, sans-serif;">₱</span>{{ number_format($data['amount'], 2) }}</td>
            <td colspan="2" style="border: 1px solid #000; padding: 2px 3px;"></td>
        </tr>
    </table>

    <script>
        // Auto-print option (optional)
        document.addEventListener('DOMContentLoaded', function() {
            // Uncomment the line below if you want the document to auto-print when loaded
            // window.print();
        });
    </script>
</body>
</html>