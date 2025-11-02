<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TextbeltService
{
    protected $apiUrl;
    protected $freeKey;
    protected $paidKey;

    public function __construct()
    {
        $this->apiUrl = config('services.textbelt.api_url');
        $this->freeKey = config('services.textbelt.free_key');
        $this->paidKey = config('services.textbelt.paid_key');
    }

    public function sendFreeSms($phone, $message)
    {
        return $this->sendSms($phone, $message, $this->freeKey);
    }

    public function sendPaidSms($phone, $message)
    {
        if (empty($this->paidKey)) {
            return [
                'success' => false,
                'error' => 'Paid Textbelt key not configured'
            ];
        }
        return $this->sendSms($phone, $message, $this->paidKey);
    }

    protected function sendSms($phone, $message, $key)
    {
        try {
            $response = Http::timeout(30)->post($this->apiUrl, [
                'phone' => $this->formatPhone($phone),
                'message' => $message,
                'key' => $key
            ]);

            $result = $response->json();

            return [
                'success' => $result['success'] ?? false,
                'text_id' => $result['textId'] ?? null,
                'quota_remaining' => $result['quotaRemaining'] ?? 0,
                'message' => $result['message'] ?? 'Unknown response',
                'raw_response' => $result
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function formatPhone($phone)
    {
        // Remove spaces and ensure proper format
        return preg_replace('/\s+/', '', trim($phone));
    }

    public function checkQuota()
    {
        try {
            $response = Http::get('https://textbelt.com/quota/' . $this->freeKey);
            return $response->json();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}