<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DvRequest;
use App\Services\SendPulseServices;
use Illuminate\Support\Facades\Log;

class DirectEmailTest extends Command
{
    protected $signature = 'test:send-aging-email {request-id} {--email=}';
    protected $description = 'Directly send aging email for a specific request';

    public function handle()
    {
        $requestId = $this->argument('request-id');
        $customEmail = $this->option('email');
        
        $this->info("🧪 Direct Email Test for Request ID: {$requestId}");
        $this->newLine();
        
        try {
            $request = DvRequest::with(['focalPerson', 'program'])->find($requestId);
            
            if (!$request) {
                $this->error("❌ Request not found!");
                return 1;
            }
            
            if (!$request->focalPerson && !$customEmail) {
                $this->error("❌ No focal person found and no custom email provided!");
                return 1;
            }
            
            // Calculate days old
            $createdAt = \Carbon\Carbon::parse($request->created_at);
            $daysOld = floor($createdAt->diffInDays(now(), false));
            
            // Get urgency level
            $urgencyLevel = $this->getUrgencyLevel($daysOld);
            
            // Prepare recipient info
            $recipientEmail = $customEmail ?? $request->focalPerson->email;
            $recipientName = $customEmail 
                ? 'Test Recipient' 
                : trim($request->focalPerson->fname . ' ' . ($request->focalPerson->mi ? $request->focalPerson->mi . ' ' : '') . $request->focalPerson->lname);
            
            // Display info
            $this->table(
                ['Field', 'Value'],
                [
                    ['Request ID', $request->id],
                    ['Reference', $request->reference_number],
                    ['Status', $request->status],
                    ['Created', $createdAt->format('M d, Y h:i A')],
                    ['Days Old', $daysOld],
                    ['Urgency', strtoupper($urgencyLevel)],
                    ['Program', $request->program->prcode ?? 'N/A'],
                    ['Recipient Name', $recipientName],
                    ['Recipient Email', $recipientEmail],
                ]
            );
            
            $this->newLine();
            
            if (!$this->confirm('Send aging notification email?')) {
                $this->info('Cancelled.');
                return 0;
            }
            
            $this->info('📧 Sending email...');
            $this->newLine();
            
            // Prepare email content
            $controlNumber = $request->accounting_control_number 
                ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT)
                : 'Not assigned';
            
            $subject = $this->getUrgencyText($urgencyLevel) . " ALERT: DV Request Delayed - {$request->reference_number}";
            $statusFormatted = str_replace('_', ' ', ucwords($request->status, '_'));
            $programName = $request->program->prcode ?? 'N/A';
            $submittedDate = $createdAt->format('M d, Y');
            $currentDate = now()->format('M d, Y');
            $currentTime = now()->timezone('Asia/Manila')->format('h:i A');
            
            $actionUrl = route('dv.verify', $request->reference_number);
            
            // Generate HTML content
            $htmlContent = view('emails.document_aging_alert', [
                'subject' => $subject,
                'focalPersonName' => $recipientName,
                'referenceNumber' => $request->reference_number,
                'controlNumber' => $controlNumber,
                'program' => $programName,
                'status' => $statusFormatted,
                'daysOld' => $daysOld,
                'submittedDate' => $submittedDate,
                'currentDate' => $currentDate,
                'currentTime' => $currentTime,
                'urgencyLevel' => $urgencyLevel,
                'urgencyText' => $this->getUrgencyText($urgencyLevel),
                'urgencyEmoji' => $this->getUrgencyEmoji($urgencyLevel),
                'actionUrl' => $actionUrl,
                'organizationName' => 'DOLE XI',
                'systemName' => 'ACCUDOCS',
            ])->render();
            
            // Send email
            $sendPulse = app(SendPulseServices::class);
            $result = $sendPulse->sendAgingEmail(
                $recipientEmail,
                $recipientName,
                $request,
                $daysOld,
                $urgencyLevel,
                $subject,
                $htmlContent
            );
            
            if ($result) {
                $this->newLine();
                $this->info("✅ Email sent successfully!");
                $this->info("📬 Sent to: {$recipientEmail}");
                $this->info("📋 Subject: {$subject}");
                $this->newLine();
                $this->info("💡 Check your email inbox (and spam folder)");
            } else {
                $this->error("❌ Failed to send email");
                $this->error("Check storage/logs/laravel.log for details");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
    
    private function getUrgencyLevel($daysOld)
    {
        if ($daysOld >= 10) {
            return 'critical';
        } elseif ($daysOld >= 7) {
            return 'high';
        } elseif ($daysOld >= 3) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    private function getUrgencyText($urgencyLevel)
    {
        $urgencyMap = [
            'critical' => '🚨 CRITICAL',
            'high' => '⚠️ HIGH PRIORITY',
            'medium' => '📋 MEDIUM PRIORITY',
            'low' => 'ℹ️ LOW PRIORITY',
        ];
        
        return $urgencyMap[$urgencyLevel] ?? 'ℹ️ NOTIFICATION';
    }
    
    private function getUrgencyEmoji($urgencyLevel)
    {
        $emojiMap = [
            'critical' => '🚨',
            'high' => '⚠️',
            'medium' => '📋',
            'low' => 'ℹ️',
        ];
        
        return $emojiMap[$urgencyLevel] ?? '📄';
    }
}