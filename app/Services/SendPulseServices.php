<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendPulseServices
{
    private $clientId;
    private $clientSecret;
    private $baseUrl = 'https://api.sendpulse.com';

    public function __construct()
    {
        $this->clientId = config('services.sendpulse.client_id');
        $this->clientSecret = config('services.sendpulse.client_secret');
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

    public function sendRejectionEmail($toEmail, $toName, $dvRequest, $rejectionRemarks)
    {
        try {
            Log::info('Preparing to send rejection email via SendPulse', [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'dv_request_id' => $dvRequest->id
            ]);

            $accessToken = $this->getAccessToken();

            $subject = "DV Request Rejected - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            
            $htmlContent = view('emails.dv_rejected', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'rejectionRemarks' => $rejectionRemarks,
                'rejectedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'rejectedBy' => auth()->user()->name ?? 'System'
            ])->render();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("{$this->baseUrl}/smtp/emails", [
                'email' => [
                    'html' => base64_encode($htmlContent),
                    'text' => strip_tags($htmlContent),
                    'subject' => $subject,
                    'from' => [
                        'name' => config('mail.from_name', 'ACCUDOCS DOLE XI'),
                        'email' => config('mail.from_address', 'c.mulato.517213@umindanao.edu.ph')
                    ],
                    'to' => [
                        [
                            'name' => $toName,
                            'email' => $toEmail
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('SendPulse email sent successfully', [
                    'to' => $toEmail,
                    'dv_request_id' => $dvRequest->id,
                    'subject' => $subject,
                    'sendpulse_response' => $responseData
                ]);
                return true;
            }

            Log::warning('SendPulse email failed, falling back to SMTP', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            // Fallback to SMTP
            return $this->sendViaSMTP($toEmail, $toName, $subject, $htmlContent);

        } catch (\Exception $e) {
            Log::error('SendPulse service exception, falling back to SMTP: ' . $e->getMessage());
            
            // Fallback to SMTP
            $subject = "DV Request Rejected - " . ($dvRequest->accounting_control_number ?? 'Pending-CN');
            $htmlContent = view('emails.dv_rejected', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'rejectionRemarks' => $rejectionRemarks,
                'rejectedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'rejectedBy' => auth()->user()->name ?? 'IMSD User'
            ])->render();
            
            return $this->sendViaSMTP($toEmail, $toName, $subject, $htmlContent);
        }
    }

    private function sendViaSMTP($toEmail, $toName, $subject, $htmlContent)
    {
        try {
            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($toEmail, $toName, $subject, $htmlContent) {
                $message->to($toEmail, $toName)
                       ->subject($subject)
                       ->from(config('mail.from.address'), config('mail.from.name'))
                       ->html($htmlContent);
            });

            Log::info('Fallback SMTP email sent successfully', ['to' => $toEmail]);
            return true;
        } catch (\Exception $e) {
            Log::error('Fallback SMTP also failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendReleaseEmail($toEmail, $toName, $dvRequest)
    {
        try {
            Log::info('Preparing to send release email via SendPulse', [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'dv_request_id' => $dvRequest->id
            ]);

            $accessToken = $this->getAccessToken();

            $subject = "DV Request Released - " . ($dvRequest->dv_number ?? $dvRequest->accounting_control_number ?? 'Pending-CN');
            
            $htmlContent = view('emails.dv_request_released', [
                'focalPerson' => $dvRequest->focalPerson,
                'dvRequest' => $dvRequest,
                'releasedAt' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'releasedBy' => auth()->user()->name ?? 'Cashier'
            ])->render();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("{$this->baseUrl}/smtp/emails", [
                'email' => [
                    'html' => base64_encode($htmlContent),
                    'text' => strip_tags($htmlContent),
                    'subject' => $subject,
                    'from' => [
                        'name' => config('mail.from_name', 'ACCUDOCS DOLE XI'),
                        'email' => config('mail.from_address', 'c.mulato.517213@umindanao.edu.ph')
                    ],
                    'to' => [
                        [
                            'name' => $toName,
                            'email' => $toEmail
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('SendPulse release email sent successfully', [
                    'to' => $toEmail,
                    'dv_request_id' => $dvRequest->id,
                    'subject' => $subject,
                    'sendpulse_response' => $responseData
                ]);
                return true;
            }

            Log::warning('SendPulse release email failed, falling back to SMTP', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            // Fallback to SMTP
            return $this->sendReleaseViaSMTP($toEmail, $toName, $subject, $htmlContent);

        } catch (\Exception $e) {
            Log::error('SendPulse service exception for release email, falling back to SMTP: ' . $e->getMessage());
            
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

    private function sendReleaseViaSMTP($toEmail, $toName, $subject, $htmlContent)
    {
        try {
            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($toEmail, $toName, $subject, $htmlContent) {
                $message->to($toEmail, $toName)
                       ->subject($subject)
                       ->from(config('mail.from.address'), config('mail.from.name'))
                       ->html($htmlContent);
            });

            Log::info('Fallback SMTP release email sent successfully', ['to' => $toEmail]);
            return true;
        } catch (\Exception $e) {
            Log::error('Fallback SMTP for release email also failed: ' . $e->getMessage());
            return false;
        }
    }
}