<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use App\Traits\HasProfileImage;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\AccountingUnitVerifyEmail;

class AccountingUnit extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, HasProfileImage, Notifiable;

    protected $table = 'accountingunits';
    protected $guard_name = 'web';

    protected $attributes = [
        'profile_image' => 'default.png',
    ];

    protected $fillable = [
        'fname',
        'mi', 
        'lname',
        'email',
        'phone',
        'password',
        'status',
        'profile_image',
        'email_verified_at', // ✅ Add this
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // ✅ Use Laravel 11's automatic hashing
    ];

    // ============================================
    // Relationships
    // ============================================
    
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    // ============================================
    // Accessors
    // ============================================
    
    public function getFullNameAttribute()
    {
        return trim("{$this->fname} {$this->mi} {$this->lname}");
    }

    // ============================================
    // Email Verification Methods
    // ============================================
    
    /**
     * Get the email address that should be used for verification.
     */
    public function getEmailForVerification()
    {
        return $this->email;
    }

    /**
     * Determine if the user has verified their email address.
     */
    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark the given user's email as verified.
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new AccountingUnitVerifyEmail);
    }

    /**
     * Get the identifier that will be stored in the route key.
     */
    public function getRouteKeyForVerification()
    {
        return $this->getKey();
    }

    // ============================================
    // Password Mutator (Fallback for older Laravel)
    // ============================================
    
    // Note: With 'password' => 'hashed' cast, this mutator is optional
    // But keeping it for backwards compatibility
    protected function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            // Avoid double-hashing
            $this->attributes['password'] =
                (strlen($value) === 60 && preg_match('/^\$2y\$/', $value))
                ? $value
                : Hash::make($value);
        }
    }
}