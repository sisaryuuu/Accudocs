<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Http\FormRequest;

class FocalEmailVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('focal');
        
        if (!$user) {
            \Log::error('No focal user found during verification');
            return false;
        }

        // Validate ID matches
        if (!hash_equals((string) $this->route('id'), (string) $user->getKey())) {
            \Log::error('ID mismatch', [
                'route_id' => $this->route('id'),
                'user_id' => $user->getKey()
            ]);
            return false;
        }

        // Validate hash matches email
        if (!hash_equals((string) $this->route('hash'), sha1($user->getEmailForVerification()))) {
            \Log::error('Hash mismatch', [
                'route_hash' => $this->route('hash'),
                'expected_hash' => sha1($user->getEmailForVerification()),
                'email' => $user->getEmailForVerification()
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
        $user = $this->user('focal');
        
        if ($user && !$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            
            \Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        }
    }
}