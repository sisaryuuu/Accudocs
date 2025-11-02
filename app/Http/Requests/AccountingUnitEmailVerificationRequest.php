<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Http\FormRequest;

class AccountingUnitEmailVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');
        
        if (!$user) {
            \Log::error('❌ No accounting unit authenticated', [
                'route_id' => $this->route('id'),
            ]);
            return false;
        }

        if (!hash_equals((string) $this->route('id'), (string) $user->getKey())) {
            \Log::error('❌ User ID mismatch', [
                'route_id' => $this->route('id'),
                'user_id' => $user->getKey()
            ]);
            return false;
        }

        $expectedHash = sha1($user->getEmailForVerification());
        if (!hash_equals((string) $this->route('hash'), $expectedHash)) {
            \Log::error('❌ Email hash mismatch', [
                'route_hash' => $this->route('hash'),
                'expected_hash' => $expectedHash,
            ]);
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function fulfill(): void
    {
        $user = $this->user('web');
        
        if ($user && !$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            
            \Log::info('✅ AccountingUnit email verified', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }
    }
}