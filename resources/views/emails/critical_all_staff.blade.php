<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background: #dc2626; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { border: 1px solid #ddd; border-top: none; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f3f4f6; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        .critical { color: #dc2626; font-weight: bold; }
        .action-box { background: #f0f9ff; padding: 20px; margin: 20px 0; border-left: 4px solid #3b82f6; }
        .btn { background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Critical Documents Alert</h1>
            <p>To: All Accounting Staff | {{ $date }}</p>
        </div>
        
        <div class="content">
            <h2>Attention Accounting Team,</h2>
            
            <p>There are <strong style="color: #dc2626;">{{ $count }} documents</strong> that have been pending for <strong>10+ days</strong> and need immediate processing.</p>
            
            <!-- Quick Stats -->
            <div style="background: #f8fafc; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p style="margin: 0;">
                    <strong>📊 Team Overview:</strong><br>
                    • Documents: {{ $count }} total<br>
                    • Amount: ₱{{ number_format($total_amount, 2) }}<br>
                    • Staff Notified: {{ $staff_count }} team members<br>
                    • All documents are available for anyone to process
                </p>
            </div>
            
            <!-- Documents List -->
            <h3>📄 Documents Needing Attention:</h3>
            <table>
                <thead>
                    <tr>
                        <th>Control #</th>
                        <th>Program</th>
                        <th>Age</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $doc)
                    <tr>
                        <td><strong>{{ $doc->accounting_control_number ?: 'Pending-CN' }}</strong></td>
                        <td>{{ $doc->program->prcode ?? 'GIP' }}</td>
                        <td class="critical">{{ now()->diffInDays($doc->created_at) }} days</td>
                        <td>₱{{ number_format($doc->amount, 2) }}</td>
                        <td>{{ $doc->status }}</td>
                        <td>
                            <a href="{{ url('/dv-requests/' . $doc->id) }}" 
                               style="color: #3b82f6; text-decoration: underline; font-size: 12px;">
                                Process →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <!-- Team Instructions -->
            <div class="action-box">
                <h3 style="margin-top: 0;">🎯 Team Coordination Instructions:</h3>
                <p><strong>Since documents are not pre-assigned, please:</strong></p>
                <ol>
                    <li><strong>Check the list above</strong> and pick documents you can process</li>
                    <li><strong>Mark documents as "In Process"</strong> when you start working on them</li>
                    <li><strong>Communicate with colleagues</strong> to avoid duplicate work</li>
                    <li><strong>Focus on oldest documents first</strong> (prioritize by age)</li>
                    <li><strong>Update status immediately</strong> after processing</li>
                </ol>
                
                <p style="margin-top: 15px; font-style: italic;">
                    Tip: Use the system's "Lock" feature when working on a document to prevent conflicts.
                </p>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="{{ config('app.url') }}/Incoming/GIP" class="btn">
                        📊 Go to Documents Dashboard
                    </a>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280;">
                <p>
                    <strong>System Information:</strong><br>
                    • Sent to all {{ $staff_count }} accounting staff members<br>
                    • Generated: {{ $date }} at {{ $time }}<br>
                    • System: ACCUDOCS DOLE XI<br>
                    • All documents are available for any staff member to process
                </p>
                <p style="margin-top: 10px;">
                    <em>If you process a document, please update its status immediately.</em>
                </p>
            </div>
        </div>
    </div>
</body>
</html>