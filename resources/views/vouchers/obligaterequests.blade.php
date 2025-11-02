<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obligation Request and Status</title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11px;
            margin: 0;
            padding: 15px;
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
        .no-border { border: none; }
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
        @media print {
            body { 
                margin: 0.3cm;
                padding: 0;
            }
            .no-print { display: none; }
        }
        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            z-index: 1000;
        }
        .print-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Print ORS</button>

    <!-- First Table - Header -->
    <table class="compact-table no-bottom-margin tight-table">
        <tr>
            <td rowspan="3" style="width: 15%; text-align: center; border: 1px solid #000; padding: 2px 3px;">
                <img src="{{ asset('storage/logo/DOLE.png') }}" alt="DOLE Logo" style="width: 70px; height: auto;">
            </td>
            <td colspan="4" class="center bold" style="border: 1px solid #000; padding: 2px 3px;">
                Republic of the Philippines<br>
                DEPARTMENT OF LABOR AND EMPLOYMENT<br>
                Regional Office No. XI<br>
                Davao City
            </td>
        </tr>
        <tr>
            <td colspan="4" class="center bold" style="border: 1px solid #000; padding: 2px 3px;">OBLIGATION REQUEST AND STATUS</td>
        </tr>
    </table>

    <table>
        
    </table>
    <!-- Second Table - Payee Information -->
    <table class="compact-table no-bottom-margin tight-table">
        <tr>
            <td style="width: 30%; border: 1px solid #000; padding: 2px 3px;"><br>NAME OF PAYEE: {{ strtoupper($data['payee']) }}</td> 
             <td style="width: 10%; border: 1px solid #000; padding: 2px 3px;">No.: {{ $data['ors_no'] }} <br><br>
                                                                                 Date: {{ $data['date'] }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 2px 3px;" colspan="2">Address:<b> {{ strtoupper($data['address']) }}</b></td>
        </tr>
    </table>
        
    <!-- Third Table - Particulars -->
    <table class="compact-table no-bottom-margin tight-table">
        <tr class="section-title">
             <td style="width: 20%; border: 1px solid #000; padding: 2px 3px;">Responsibility <br> Center</td>
            <td style="width: 30%; border: 1px solid #000; padding: 2px 3px;">Particulars</td>
            <td style="width: 15%; border: 1px solid #000; padding: 2px 3px;">MFO/P.P.A</td>
            <td style="width: 25%; border: 1px solid #000; padding: 2px 3px;">UACS / Expenditure</td>
            <td style="width: 30%; border: 1px solid #000; padding: 2px 3px;">Amount</td>
        </tr>
        <tr style="height: 275px;">
            <td style="border: 1px solid #000; padding: 2px 3px; text-align:center"><strong>{{ $data['responsibility_center'] }}</strong></td>
            <td style="border: 1px solid #000; padding: 2px 3px;">{{$data['particulars'] }}</td>
            <td class="center" style="border: 1px solid #000; padding: 2px 3px; text-align:center">{{ $data['mfo'] }}</td>
            <td class="center" style="border: 1px solid #000; padding: 2px 3px; white-space: pre-line;">{{ $data['uacs'] }}</td>
            <td class="center" style="border: 1px solid #000; padding: 2px 3px;"><strong>₱{{ number_format($data['amount'], 2) }}</strong></td>
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
            <td class="center" style="border: 1px solid #000; padding: 2px 3px;">{{ $row['obligation'] }}</td>
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
            <td class="center bold" style="border: 1px solid #000; padding: 2px 3px;">₱{{ number_format($data['amount'], 2) }}</td>
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