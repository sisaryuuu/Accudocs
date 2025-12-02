<?php
// app/Services/IprogSmsService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IprogSmsService
{
    protected $baseUrl;
    protected $token;
    protected $httpClient;

    public function __construct()
    {
        $this->baseUrl = config('services.iprogsms.base_url');
        $this->token = config('services.iprogsms.token');
        
        // ⚡ HTTP OPTIMIZATIONS - Pre-configured client
        $this->httpClient = Http::withOptions([
            'timeout' => 8,           // Reduced from 30 to 8 seconds
            'connect_timeout' => 4,   // Faster connection timeout
        ]);
    }

    /**
     * ⚡ OPTIMIZED SMS SENDING - Fast timeout for rejection notifications
     */
    public function sendSMS($phoneNumber, $message)
    {
        // Fast validation first
        if (!$this->isValidPhilippineNumber($phoneNumber)) {
            Log::warning('Invalid Philippine number format', ['phone' => $phoneNumber]);
            return [
                'success' => false,
                'error' => 'Invalid Philippine mobile number format'
            ];
        }

        try {
            // Format phone number to iProg's required format (639171234567)
            $phoneNumber = $this->formatPhilippineNumber($phoneNumber);
            
            $data = [
                'api_token' => $this->token,
                'message' => $message,
                'phone_number' => $phoneNumber
            ];

            Log::info('Sending SMS via iProg', [
                'phone' => $phoneNumber,
                'message_length' => strlen($message)
            ]);

            // ⚡ FAST TIMEOUT - Maximum 8 seconds
            $response = $this->httpClient->asForm()
                ->post($this->baseUrl . '/sms_messages', $data);

            $responseData = $response->json();

            if ($response->successful()) {
                Log::info('SMS sent successfully', [
                    'phone' => $phoneNumber,
                    'message_id' => $responseData['id'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseData,
                    'message_id' => $responseData['id'] ?? null,
                    'message' => 'SMS sent successfully'
                ];
            } else {
                Log::warning('SMS sending failed', [ // Changed to warning for non-critical failures
                    'phone' => $phoneNumber,
                    'status' => $response->status(),
                    'response' => $responseData
                ]);
                
                return [
                    'success' => false,
                    'error' => $responseData['message'] ?? 'SMS sending failed',
                    'status' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            // ⚡ SILENT FAILURE - Don't throw critical errors, just log as warning
            Log::warning('SMS service timeout - continuing: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Service temporarily unavailable. Please try again later.'
            ];
        }
    }

    /**
     * ⚡ ULTRA-FAST SMS SENDING - For rejection notifications (even faster)
     */
    public function sendQuickSMS($phoneNumber, $message)
    {
        // Ultra-fast validation
        if (!$this->isValidPhilippineNumber($phoneNumber)) {
            return ['success' => false, 'error' => 'Invalid number'];
        }

        try {
            $formattedNumber = $this->formatPhilippineNumber($phoneNumber);
            
            // ⚡ ULTRA-FAST TIMEOUT - 5 seconds max for rejections
            $response = Http::asForm()
                ->timeout(5)
                ->connectTimeout(3)
                ->post($this->baseUrl . '/sms_messages', [
                    'api_token' => $this->token,
                    'message' => $message,
                    'phone_number' => $formattedNumber
                ]);

            $success = $response->successful();
            
            if ($success) {
                Log::info('Quick SMS sent successfully', ['phone' => $formattedNumber]);
            } else {
                Log::warning('Quick SMS failed silently', ['phone' => $formattedNumber]);
            }
            
            return ['success' => $success];
            
        } catch (\Exception $e) {
            Log::warning('Quick SMS timeout - continuing silently');
            return ['success' => false];
        }
    }

    /**
     * ⚡ OPTIMIZED BULK SMS CONCURRENT
     */
    public function sendBulkSMSConcurrent(array $recipientsWithMessages)
    {
        $promises = [];
        $results = [
            'successful' => [],
            'failed' => [],
            'total_sent' => 0,
            'total_failed' => 0
        ];

        foreach ($recipientsWithMessages as $index => $item) {
            $phoneNumber = $item['phone'];
            $message = $item['message'];
            
            if (!$this->isValidPhilippineNumber($phoneNumber)) {
                $results['failed'][$phoneNumber] = 'Invalid Philippine number format';
                $results['total_failed']++;
                continue;
            }

            $formattedNumber = $this->formatPhilippineNumber($phoneNumber);
            
            $promises[$index] = Http::asForm()
                ->timeout(10) // Reduced timeout for bulk
                ->async()
                ->post($this->baseUrl . '/sms_messages', [
                    'api_token' => $this->token,
                    'message' => $message,
                    'phone_number' => $formattedNumber
                ]);
        }

        // Wait for all requests to complete
        $responses = Http::pool($promises);

        foreach ($responses as $index => $response) {
            $phoneNumber = $recipientsWithMessages[$index]['phone'];
            
            try {
                if ($response instanceof \Exception) {
                    throw $response;
                }

                $responseData = $response->json();
                
                if ($response->successful()) {
                    $results['successful'][$phoneNumber] = $responseData;
                    $results['total_sent']++;
                    
                    Log::info('Bulk SMS sent successfully', [
                        'phone' => $phoneNumber,
                        'message_id' => $responseData['id'] ?? null
                    ]);
                } else {
                    $results['failed'][$phoneNumber] = $responseData['message'] ?? 'SMS sending failed';
                    $results['total_failed']++;
                    
                    Log::warning('Bulk SMS failed', [ // Changed to warning
                        'phone' => $phoneNumber,
                        'status' => $response->status(),
                        'response' => $responseData
                    ]);
                }
            } catch (\Exception $e) {
                $results['failed'][$phoneNumber] = $e->getMessage();
                $results['total_failed']++;
                
                Log::warning('Bulk SMS exception', [ // Changed to warning
                    'phone' => $phoneNumber,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * ⚡ OPTIMIZED BULK SMS WITH THROTTLE
     */
    public function sendBulkSMSWithThrottle(array $recipientsWithMessages, $batchSize = 5, $delayBetweenBatches = 1) // Reduced delay
    {
        $allResults = [
            'successful' => [],
            'failed' => [],
            'total_sent' => 0,
            'total_failed' => 0
        ];

        // Split into batches
        $batches = array_chunk($recipientsWithMessages, $batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            Log::info("Processing SMS batch " . ($batchIndex + 1) . " of " . count($batches));
            
            // Process current batch concurrently
            $batchResults = $this->sendBulkSMSConcurrent($batch);
            
            // Merge results
            $allResults['successful'] = array_merge($allResults['successful'], $batchResults['successful']);
            $allResults['failed'] = array_merge($allResults['failed'], $batchResults['failed']);
            $allResults['total_sent'] += $batchResults['total_sent'];
            $allResults['total_failed'] += $batchResults['total_failed'];
            
            // Shorter delay between batches (except the last one)
            if ($batchIndex < count($batches) - 1) {
                sleep($delayBetweenBatches);
            }
        }
        
        return $allResults;
    }

    /**
     * Quick health check for SMS service
     */
    public function healthCheck()
    {
        try {
            $response = Http::timeout(6) // Reduced health check timeout
                ->get($this->baseUrl . '/health'); // Adjust endpoint as per iProg API
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('SMS service health check failed: ' . $e->getMessage()); // Changed to warning
            return false;
        }
    }

    /**
     * Send bulk SMS to multiple Philippine numbers
     */
    public function sendBulkSMS($recipients, $message)
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'total_sent' => 0,
            'total_failed' => 0
        ];
        
        foreach ($recipients as $phoneNumber) {
            if (!$this->isValidPhilippineNumber($phoneNumber)) {
                $results['failed'][$phoneNumber] = 'Invalid Philippine number format';
                $results['total_failed']++;
                continue;
            }
            
            // Use quick SMS for better performance
            $result = $this->sendQuickSMS($phoneNumber, $message);
            
            if ($result['success']) {
                $results['successful'][$phoneNumber] = $result;
                $results['total_sent']++;
            } else {
                $results['failed'][$phoneNumber] = $result['error'] ?? 'SMS failed';
                $results['total_failed']++;
            }
        }
        
        return $results;
    }

    /**
     * Validate Philippine mobile number
     */
    private function isValidPhilippineNumber($phoneNumber)
    {
        // Remove all non-numeric characters except +
        $cleanNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Check for common Philippine mobile number patterns:
        $patterns = [
            '/^\+63(9\d{9})$/',        // +639171234567
            '/^63(9\d{9})$/',          // 639171234567
            '/^09(\d{9})$/',           // 09171234567
            '/^9(\d{9})$/'             // 9171234567
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleanNumber)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Format Philippine number to iProg format (639171234567)
     */
    private function formatPhilippineNumber($phoneNumber)
    {
        // Remove all non-numeric characters
        $cleanNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Convert to 639171234567 format
        if (str_starts_with($cleanNumber, '0')) {
            return '63' . substr($cleanNumber, 1);
        } elseif (str_starts_with($cleanNumber, '9') && strlen($cleanNumber) === 10) {
            return '63' . $cleanNumber;
        }
        
        return $cleanNumber;
    }

    /**
     * Quick test method
     */
    public function testService($phoneNumber = '09235323646')
    {
        return $this->sendQuickSMS( // Use quick SMS for testing
            $phoneNumber, 
            'ACCUDOCS SMS Service Test - Please ignore this message.'
        );
    }
    
}