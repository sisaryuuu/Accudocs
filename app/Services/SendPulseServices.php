<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendPulseServices
{
    private $clientId;
    private $clientSecret;
    private $baseUrl = 'https://api.sendpulse.com';
    private $lastEmailSentAt = null;
    private $quotaCheckEnabled = true;
    
    // Rate limiting configuration
    private $minDelayBetweenEmails = 2.0; // 2 seconds minimum between emails
    private $batchSize = 3; // Process 3 emails then pause
    private $batchDelay = 5; // 5 second delay between batches
    private $emailsSentInCurrentHour = 0;
    private $hourlyLimit = 50; // SendPulse free tier typically has 50 emails/hour

    public function __construct()
    {
        $this->clientId = config('services.sendpulse.client_id');
        $this->clientSecret = config('services.sendpulse.client_secret');
        $this->loadEmailCount();
    }

    private function getAccessToken()
    {
        return Cache::remember('sendpulse_access_token', 3600, function () {
            Log::info('Requesting SendPulse access token');
            
            $response = Http::asForm()->post("{$this->baseUrl}/oauth/access_token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('SendPulse access token obtained successfully');
                return $data['access_token'];
            }

            Log::error('SendPulse authentication failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            throw new \Exception('SendPulse authentication failed. Status: ' . $response->status());
        });
    }

    /**
     * Load email count from cache
     */
    private function loadEmailCount()
    {
        $hourKey = 'sendpulse_emails_hour_' . date('Y-m-d-H');
        $this->emailsSentInCurrentHour = Cache::get($hourKey, 0);
    }

    /**
     * Increment email count
     */
    private function incrementEmailCount()
    {
        $hourKey = 'sendpulse_emails_hour_' . date('Y-m-d-H');
        $this->emailsSentInCurrentHour++;
        Cache::put($hourKey, $this->emailsSentInCurrentHour, now()->addHour());
    }

    /**
     * Apply rate limiting between emails
     */
    private function applyRateLimit()
    {
        if ($this->lastEmailSentAt) {
            $timeSinceLastEmail = microtime(true) - $this->lastEmailSentAt;
            
            if ($timeSinceLastEmail < $this->minDelayBetweenEmails) {
                $sleepTime = ($this->minDelayBetweenEmails - $timeSinceLastEmail) * 1000000;
                usleep($sleepTime);
                Log::debug('SendPulse rate limiting applied', [
                    'sleep_ms' => $sleepTime / 1000,
                    'emails_this_hour' => $this->emailsSentInCurrentHour
                ]);
            }
        }
        $this->lastEmailSentAt = microtime(true);
    }

    /**
     * Check if hourly limit is reached
     */
    private function isHourlyLimitReached()
    {
        return $this->emailsSentInCurrentHour >= $this->hourlyLimit;
    }

    /**
     * Check if SendPulse quota is exceeded
     */
    private function isQuotaExceeded()
    {
        if (!$this->quotaCheckEnabled) {
            return false;
        }
        
        return Cache::get('sendpulse_quota_exceeded', false);
    }

    /**
     * Mark SendPulse quota as exceeded
     */
    private function markQuotaExceeded($hours = 6)
    {
        Cache::put('sendpulse_quota_exceeded', true, now()->addHours($hours));
        Log::warning('SendPulse quota marked as exceeded for ' . $hours . ' hours', [
            'emails_sent_this_hour' => $this->emailsSentInCurrentHour
        ]);
    }

    /**
     * Clear quota exceeded flag
     */
    public function clearQuotaExceeded()
    {
        Cache::forget('sendpulse_quota_exceeded');
        Log::info('SendPulse quota exceeded flag cleared');
    }

    /**
     * Send email via SendPulse with rate limiting and quota checking
     */
    private function sendViaSendPulse($emailData, $emailType = 'aging')
    {
        try {
            // Check quota first
            if ($this->isQuotaExceeded()) {
                Log::info('SendPulse quota exceeded, using SMTP instead', [
                    'email_type' => $emailType,
                    'to_email' => $emailData['to'][0]['email'] ?? 'unknown'
                ]);
                return false;
            }

            // Check hourly limit
            if ($this->isHourlyLimitReached()) {
                Log::warning('SendPulse hourly limit reached, using SMTP', [
                    'emails_sent' => $this->emailsSentInCurrentHour,
                    'hourly_limit' => $this->hourlyLimit
                ]);
                return false;
            }

            // Apply rate limiting
            $this->applyRateLimit();

            $accessToken = $this->getAccessToken();

            Log::debug('Sending email via SendPulse', [
                'email_type' => $emailType,
                'to_email' => $emailData['to'][0]['email'] ?? 'unknown',
                'emails_this_hour' => $this->emailsSentInCurrentHour
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("{$this->baseUrl}/smtp/emails", [
                'email' => $emailData
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $this->incrementEmailCount();
                
                Log::info('SendPulse ' . $emailType . ' email sent successfully', [
                    'to' => $emailData['to'][0]['email'],
                    'subject' => $emailData['subject'],
                    'emails_this_hour' => $this->emailsSentInCurrentHour,
                    'sendpulse_response_id' => $responseData['id'] ?? 'unknown'
                ]);
                return true;
            }

            // Check for quota errors
            if ($response->status() === 422) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? '';
                
                if (str_contains($errorMessage, 'quota') || 
                    str_contains($errorMessage, 'bandwidth') ||
                    str_contains($errorMessage, 'limit') ||
                    str_contains($errorMessage, 'exceeded')) {
                    
                    Log::warning('SendPulse quota/limit exceeded detected', [
                        'status' => $response->status(),
                        'error_message' => $errorMessage,
                        'emails_this_hour' => $this->emailsSentInCurrentHour
                    ]);
                    
                    $this->markQuotaExceeded();
                    return false;
                }
            }

            Log::warning('SendPulse email failed (non-quota error)', [
                'status' => $response->status(),
                'response' => $response->body(),
                'email_type' => $emailType,
                'to_email' => $emailData['to'][0]['email'] ?? 'unknown'
            ]);
            
            return false;

        } catch (\Exception $e) {
            Log::error('SendPulse service exception: ' . $e->getMessage(), [
                'email_type' => $emailType,
                'to_email' => $emailData['to'][0]['email'] ?? 'unknown'
            ]);
            return false;
        }
    }

    /**
     * Send aging notification email
     */
    public function sendAgingEmail($toEmail, $toName, $dvRequest, $daysOld, $urgencyLevel, $subject, $htmlContent)
    {
        try {
            Log::info('Preparing to send aging notification email', [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'dv_request_id' => $dvRequest->id ?? 'unknown',
                'days_old' => $daysOld,
                'urgency_level' => $urgencyLevel,
                'emails_this_hour' => $this->emailsSentInCurrentHour
            ]);

            // Prepare email data
            $emailData = [
                'html' => base64_encode($htmlContent),
                'text' => strip_tags($htmlContent),
                'subject' => $subject,
                'from' => [
                    'name' => config('mail.from.name', 'ACCUDOCS DOLE XI'),
                    'email' => config('mail.from.address', 'c.mulato.517213@umindanao.edu.ph')
                ],
                'to' => [
                    [
                        'name' => $toName,
                        'email' => $toEmail
                    ]
                ]
            ];

            // Try SendPulse first
            $sendpulseSuccess = $this->sendViaSendPulse($emailData, 'aging');
            
            if ($sendpulseSuccess) {
                return true;
            }

            // Fallback to SMTP
            Log::info('Falling back to SMTP for aging email', [
                'to_email' => $toEmail,
                'reason' => $this->isQuotaExceeded() ? 'quota_exceeded' : 
                            ($this->isHourlyLimitReached() ? 'hourly_limit_reached' : 'sendpulse_failed'),
                'emails_this_hour' => $this->emailsSentInCurrentHour
            ]);
            
            return $this->sendAgingViaSMTP($toEmail, $toName, $subject, $htmlContent);

        } catch (\Exception $e) {
            Log::error('Error in sendAgingEmail: ' . $e->getMessage(), [
                'to_email' => $toEmail,
                'dv_request_id' => $dvRequest->id ?? 'unknown'
            ]);
            
            // Last resort: try SMTP directly
            return $this->sendAgingViaSMTP($toEmail, $toName, $subject, $htmlContent);
        }
    }

    /**
     * Send rejection email
     */
    public function sendRejectionEmail($toEmail, $toName, $dvRequest, $rejectionRemarks)
    {
        try {
            Log::info('Preparing to send rejection email', [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'dv_request_id' => $dvRequest->id,
                'emails_this_hour' => $this->emailsSentInCurrentHour
            ]);

            $subject = "DV Request Rejected - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            
            $htmlContent = view('emails.dv_rejected', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'rejectionRemarks' => $rejectionRemarks,
                'rejectedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'rejectedBy' => auth()->user()->name ?? 'System'
            ])->render();

            // Prepare email data
            $emailData = [
                'html' => base64_encode($htmlContent),
                'text' => strip_tags($htmlContent),
                'subject' => $subject,
                'from' => [
                    'name' => config('mail.from.name', 'ACCUDOCS DOLE XI'),
                    'email' => config('mail.from.address', 'c.mulato.517213@umindanao.edu.ph')
                ],
                'to' => [
                    [
                        'name' => $toName,
                        'email' => $toEmail
                    ]
                ]
            ];

            // Try SendPulse first
            $sendpulseSuccess = $this->sendViaSendPulse($emailData, 'rejection');
            
            if ($sendpulseSuccess) {
                return true;
            }

            // Fallback to SMTP
            return $this->sendViaSMTP($toEmail, $toName, $subject, $htmlContent);

        } catch (\Exception $e) {
            Log::error('Error in sendRejectionEmail: ' . $e->getMessage());
            
            // Fallback to SMTP
            $subject = "DV Request Rejected - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            $htmlContent = view('emails.dv_rejected', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'rejectionRemarks' => $rejectionRemarks,
                'rejectedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'rejectedBy' => auth()->user()->name ?? 'System'
            ])->render();
            
            return $this->sendViaSMTP($toEmail, $toName, $subject, $htmlContent);
        }
    }

    /**
     * Send release email
     */
    public function sendReleaseEmail($toEmail, $toName, $dvRequest)
    {
        try {
            Log::info('Preparing to send release email', [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'dv_request_id' => $dvRequest->id,
                'emails_this_hour' => $this->emailsSentInCurrentHour
            ]);

            $subject = "DV Request Released - " . ($dvRequest->dv_number ?? $dvRequest->accounting_control_number ?? 'Pending-CN');
            
            $htmlContent = view('emails.dv_request_released', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'releasedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'releasedBy' => auth()->user()->name ?? 'Cashier'
            ])->render();

            // Prepare email data
            $emailData = [
                'html' => base64_encode($htmlContent),
                'text' => strip_tags($htmlContent),
                'subject' => $subject,
                'from' => [
                    'name' => config('mail.from.name', 'ACCUDOCS DOLE XI'),
                    'email' => config('mail.from.address', 'c.mulato.517213@umindanao.edu.ph')
                ],
                'to' => [
                    [
                        'name' => $toName,
                        'email' => $toEmail
                    ]
                ]
            ];

            // Try SendPulse first
            $sendpulseSuccess = $this->sendViaSendPulse($emailData, 'release');
            
            if ($sendpulseSuccess) {
                return true;
            }

            // Fallback to SMTP
            return $this->sendReleaseViaSMTP($toEmail, $toName, $subject, $htmlContent);

        } catch (\Exception $e) {
            Log::error('Error in sendReleaseEmail: ' . $e->getMessage());
            
            // Fallback to SMTP
            $subject = "DV Request Released - " . ($dvRequest->dv_number ?? $dvRequest->accounting_control_number ?? 'Pending-CN');
            $htmlContent = view('emails.dv_request_released', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'releasedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'releasedBy' => auth()->user()->name ?? 'Cashier'
            ])->render();
            
            return $this->sendReleaseViaSMTP($toEmail, $toName, $subject, $htmlContent);
        }
    }

    /**
     * Generic SMTP fallback
     */
    private function sendViaSMTP($toEmail, $toName, $subject, $htmlContent)
    {
        try {
            Mail::send([], [], function ($message) use ($toEmail, $toName, $subject, $htmlContent) {
                $message->to($toEmail, $toName)
                       ->subject($subject)
                       ->from(config('mail.from.address'), config('mail.from.name'))
                       ->html($htmlContent);
            });

            Log::info('Fallback SMTP email sent successfully', [
                'to' => $toEmail,
                'provider' => 'SMTP',
                'subject' => $subject
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Fallback SMTP failed: ' . $e->getMessage(), [
                'to_email' => $toEmail
            ]);
            return false;
        }
    }

    /**
     * SMTP fallback for aging emails
     */
    private function sendAgingViaSMTP($toEmail, $toName, $subject, $htmlContent)
    {
        return $this->sendViaSMTP($toEmail, $toName, $subject, $htmlContent);
    }

    /**
     * SMTP fallback for release emails
     */
    private function sendReleaseViaSMTP($toEmail, $toName, $subject, $htmlContent)
    {
        return $this->sendViaSMTP($toEmail, $toName, $subject, $htmlContent);
    }

    /**
     * Get quota status
     */
    public function getQuotaStatus()
    {
        $hourKey = 'sendpulse_emails_hour_' . date('Y-m-d-H');
        $emailsThisHour = Cache::get($hourKey, 0);
        
        return [
            'quota_exceeded' => $this->isQuotaExceeded(),
            'hourly_limit_reached' => $emailsThisHour >= $this->hourlyLimit,
            'emails_sent_this_hour' => $emailsThisHour,
            'hourly_limit' => $this->hourlyLimit,
            'rate_limiting_enabled' => true,
            'min_delay_between_emails' => $this->minDelayBetweenEmails . ' seconds',
            'batch_size' => $this->batchSize,
            'batch_delay' => $this->batchDelay . ' seconds',
            'current_hour' => date('Y-m-d H:00:00'),
        ];
    }

    /**
     * Disable quota checking (for testing)
     */
    public function disableQuotaChecking()
    {
        $this->quotaCheckEnabled = false;
        Log::info('SendPulse quota checking disabled');
    }

    /**
     * Enable quota checking
     */
    public function enableQuotaChecking()
    {
        $this->quotaCheckEnabled = true;
        Log::info('SendPulse quota checking enabled');
    }

    /**
     * Process emails in batches with delays
     */
    public function processEmailBatch(array $emailJobs, $callback)
    {
        $results = [];
        $batchCount = 0;
        $totalJobs = count($emailJobs);
        
        Log::info('Starting email batch processing', [
            'total_emails' => $totalJobs,
            'batch_size' => $this->batchSize,
            'batch_delay' => $this->batchDelay
        ]);
        
        foreach ($emailJobs as $index => $emailJob) {
            try {
                Log::debug('Processing email ' . ($index + 1) . ' of ' . $totalJobs, [
                    'email' => $emailJob['email'] ?? 'unknown',
                    'batch_count' => $batchCount + 1
                ]);
                
                $result = $callback($emailJob);
                $results[] = $result;
                
                $batchCount++;
                
                // Check if we need to pause between batches
                if ($batchCount >= $this->batchSize && $index < $totalJobs - 1) {
                    Log::info('Email batch completed, pausing for ' . $this->batchDelay . ' seconds', [
                        'processed' => $index + 1,
                        'total' => $totalJobs,
                        'batch_size' => $this->batchSize,
                        'next_batch_starts_at' => $index + 2
                    ]);
                    
                    sleep($this->batchDelay);
                    $batchCount = 0;
                }
                
            } catch (\Exception $e) {
                Log::error('Error processing email job: ' . $e->getMessage(), [
                    'email_job' => $emailJob
                ]);
                $results[] = false;
            }
        }
        
        Log::info('Email batch processing completed', [
            'total_processed' => count($results),
            'successful' => count(array_filter($results)),
            'failed' => count($results) - count(array_filter($results))
        ]);
        
        return $results;
    }

    /**
     * Reset hourly counter
     */
    public function resetHourlyCounter()
    {
        $hourKey = 'sendpulse_emails_hour_' . date('Y-m-d-H');
        Cache::forget($hourKey);
        $this->emailsSentInCurrentHour = 0;
        Log::info('SendPulse hourly counter reset');
    }
}