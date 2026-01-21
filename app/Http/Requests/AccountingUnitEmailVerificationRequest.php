<?php

namespace App\Http\Requests;

use App\Models\AccountingUnit;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Http\FormRequest;

class AccountingUnitEmailVerificationRequest extends FormRequest
{
    protected $accountingUnit;

    public function authorize(): bool
    {
        \Log::info('🔍 Email Verification Attempt', [
            'route_id' => $this->route('id'),
            'route_hash' => $this->route('hash'),
            'url' => $this->fullUrl(),
        ]);

        // Find the accounting unit by ID
        $this->accountingUnit = AccountingUnit::find($this->route('id'));
        
        if (!$this->accountingUnit) {
            \Log::error('❌ AccountingUnit not found', [
                'id' => $this->route('id')
            ]);
            abort(404, 'User not found');
        }

        \Log::info('✅ AccountingUnit found', [
            'id' => $this->accountingUnit->id,
            'email' => $this->accountingUnit->email,
            'verified' => $this->accountingUnit->hasVerifiedEmail(),
        ]);

        // If already verified, allow through
        if ($this->accountingUnit->hasVerifiedEmail()) {
            \Log::info('ℹ️ Email already verified');
            return true;
        }

        // Verify the hash
        $expectedHash = sha1($this->accountingUnit->getEmailForVerification());
        
        \Log::info('🔐 Hash Comparison', [
            'expected' => $expectedHash,
            'received' => $this->route('hash'),
            'match' => hash_equals((string) $this->route('hash'), $expectedHash)
        ]);

        if (!hash_equals((string) $this->route('hash'), $expectedHash)) {
            \Log::error('❌ Hash mismatch - Invalid verification link');
            abort(403, 'Invalid verification link');
        }

        \Log::info('✅ Authorization successful');
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function fulfill(): void
    {
        if (!$this->accountingUnit) {
            $this->accountingUnit = AccountingUnit::find($this->route('id'));
        }
        
        if (!$this->accountingUnit) {
            \Log::error('❌ Cannot fulfill - AccountingUnit not found');
            return;
        }

        if ($this->accountingUnit->hasVerifiedEmail()) {
            \Log::info('ℹ️ Email already verified, skipping');
            return;
        }

        $this->accountingUnit->markEmailAsVerified();
        event(new Verified($this->accountingUnit));
        
        \Log::info('✅ Email verification completed', [
            'user_id' => $this->accountingUnit->id,
            'email' => $this->accountingUnit->email,
            'verified_at' => $this->accountingUnit->email_verified_at,
        ]);
    }

    /**
     * Get the AccountingUnit instance for this request
     */
    public function getAccountingUnit()
    {
        return $this->accountingUnit;
    }
}