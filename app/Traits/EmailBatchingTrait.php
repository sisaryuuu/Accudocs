<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait EmailBatchingTrait
{
    /**
     * Send emails with batching and rate limiting
     */
    protected function sendAgingEmailsWithBatching($requests, $callback)
    {
        $emailService = app(\App\Services\SendPulseServices::class);
        $emailJobs = [];
        
        // Prepare email jobs
        foreach ($requests as $request) {
            try {
                if (!$request->focalPerson) {
                    continue;
                }
                
                $focalPersonEmail = $request->focalPerson->email;
                $focalPersonName = trim($request->focalPerson->fname . ' ' . 
                    ($request->focalPerson->mi ? $request->focalPerson->mi . ' ' : '') . 
                    $request->focalPerson->lname);
                
                if (empty($focalPersonEmail) || !filter_var($focalPersonEmail, FILTER_VALIDATE_EMAIL)) {
                    Log::warning('Invalid email for focal person', [
                        'dv_request_id' => $request->id,
                        'email' => $focalPersonEmail
                    ]);
                    continue;
                }
                
                $emailJobs[] = [
                    'request' => $request,
                    'email' => $focalPersonEmail,
                    'name' => $focalPersonName,
                    'callback' => function() use ($callback, $request, $focalPersonEmail, $focalPersonName) {
                        return $callback($request, $focalPersonEmail, $focalPersonName);
                    }
                ];
                
            } catch (\Exception $e) {
                Log::error('Error preparing email job: ' . $e->getMessage(), [
                    'dv_request_id' => $request->id ?? 'unknown'
                ]);
            }
        }
        
        Log::info('Prepared ' . count($emailJobs) . ' email jobs for batching', [
            'total_requests' => count($requests),
            'valid_emails' => count($emailJobs)
        ]);
        
        if (empty($emailJobs)) {
            return ['success' => 0, 'failed' => 0];
        }
        
        // Process in batches
        $results = $emailService->processEmailBatch($emailJobs, function($emailJob) {
            try {
                return $emailJob['callback']();
            } catch (\Exception $e) {
                Log::error('Error in email job callback: ' . $e->getMessage());
                return false;
            }
        });
        
        $successful = count(array_filter($results));
        $failed = count($results) - $successful;
        
        return [
            'success' => $successful,
            'failed' => $failed,
            'total' => count($results)
        ];
    }
    
    /**
     * Send individual aging email (to be used in callback)
     */
    protected function sendIndividualAgingEmail($request, $focalPersonEmail, $focalPersonName, $daysOld, $urgencyLevel)
    {
        try {
            $now = now('Asia/Manila');
            $createdAt = \Carbon\Carbon::parse($request->created_at, 'Asia/Manila');
            
            $controlNumber = $request->control_number ?? 
                'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number ?? $request->id, 4, '0', STR_PAD_LEFT);
            
            $dvNumber = $request->dv_number ?? 'Not assigned';
            $orsNumber = $request->ors_number ?? 'Not assigned';
            $programName = $request->program->prcode ?? 'N/A';
            $respoCenter = $request->respocenter->respocenter_code ?? 'N/A';
            
            $urgencyText = $this->getUrgencyText($urgencyLevel);
            $subject = "{$urgencyText} ALERT: RANCA DV Request Delayed - {$request->reference_number}";
            
            $statusFormatted = str_replace('_', ' ', ucwords($request->status, '_'));
            $submittedDate = $createdAt->format('M d, Y');
            $currentDate = $now->format('M d, Y');
            $currentTime = $now->format('h:i A');
            
            $actionUrl = route('dv.verify', $request->reference_number);
            
            $htmlContent = view('emails.document_aging_alert', [
                'subject' => $subject,
                'focalPersonName' => $focalPersonName,
                'referenceNumber' => $request->reference_number,
                'controlNumber' => $controlNumber,
                'dvNumber' => $dvNumber,
                'orsNumber' => $orsNumber,
                'program' => $programName,
                'respocenter' => $respoCenter,
                'amount' => $request->amount ?? 0,
                'status' => $statusFormatted,
                'daysOld' => $daysOld,
                'submittedDate' => $submittedDate,
                'currentDate' => $currentDate,
                'currentTime' => $currentTime,
                'urgencyLevel' => $urgencyLevel,
                'urgencyText' => $urgencyText,
                'urgencyEmoji' => $this->getUrgencyEmoji($urgencyLevel),
                'actionUrl' => $actionUrl,
                'organizationName' => 'DOLE XI Regional Office',
                'systemName' => 'ACCUDOCS',
                'department' => 'Accounting III - RANCA',
            ])->render();
            
            $emailService = app(\App\Services\SendPulseServices::class);
            
            $emailSent = $emailService->sendAgingEmail(
                $focalPersonEmail,
                $focalPersonName,
                $request,
                $daysOld,
                $urgencyLevel,
                $subject,
                $htmlContent
            );
            
            if ($emailSent) {
                Log::info("📧 Email sent successfully via batching", [
                    'dv_request_id' => $request->id,
                    'focal_person_email' => $focalPersonEmail,
                ]);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("Error sending individual aging email: " . $e->getMessage(), [
                'dv_request_id' => $request->id,
                'email' => $focalPersonEmail
            ]);
            return false;
        }
    }
}