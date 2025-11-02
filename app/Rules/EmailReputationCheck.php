<?php

namespace App\Rules;

use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class EmailReputationCheck implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $apiKey = config('services.email_reputation.api_key');
        $apiUrl = config('services.email_reputation.api_url');
        
        if (empty($apiKey) || empty($apiUrl)) {
            Log::warning('Email Reputation API configuration missing');
            return; // Skip validation if not configured
        }

        try {
            // Make API request based on the format in your image
            $response = Http::timeout(15)
                ->get($apiUrl, [
                    'api_key' => $apiKey,
                    'email' => $value
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Adjust these based on the actual API response structure
                $isValid = $data['is_valid'] ?? true;
                $reputationScore = $data['reputation_score'] ?? 100;
                $isSpam = $data['is_spam'] ?? false;
                $isDisposable = $data['is_disposable'] ?? false;

                // Customize these checks based on your requirements
                if (isset($data['is_valid']) && !$data['is_valid']) {
                    $fail('The email address is invalid.');
                    return;
                }

                if ($isSpam) {
                    $fail('This email address appears to be associated with spam.');
                    return;
                }

                if ($isDisposable) {
                    $fail('Disposable/temporary email addresses are not allowed.');
                    return;
                }

                // Optional: Check reputation score (adjust threshold as needed)
                if ($reputationScore < 50) { // 0-100 scale, lower = worse reputation
                    $fail('This email address has a poor reputation and cannot be used.');
                    return;
                }

            } else {
                // Log API error but don't block registration
                Log::warning('Email Reputation API error: ' . $response->status() . ' - ' . $response->body());
            }
            
        } catch (\Exception $e) {
            // If API fails (timeout, network issue), don't block registration
            Log::error('Email reputation check failed: ' . $e->getMessage());
        }
    }
}