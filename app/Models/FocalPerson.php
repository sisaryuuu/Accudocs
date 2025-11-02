<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\HasProfileImage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\FocalPersonVerifyEmail;

class FocalPerson extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, HasProfileImage, Notifiable;

    protected $table = 'focalpersons';
    protected $guard_name = 'focal'; // Correct guard for Spatie + Notifications

    protected $attributes = [
        'profile_image' => 'default.png',
    ];

    protected $fillable = [
        'fname',
        'mi',
        'lname',
        'TIN_number',
        'Address',
        'email',
        'password',
        'contactnumber',
        'respocenter_id',
        'profile_image',
        'email_verified_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Laravel 9+ automatic hashing
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function respoCenter()
    {
        return $this->belongsTo(ResponCenter::class, 'respocenter_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */
    
    public function getFullNameAttribute()
    {
        return trim("{$this->fname} {$this->mi} {$this->lname}");
    }

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
        // You can use the default notification or create a custom one
        $this->notify(new FocalPersonVerifyEmail);
    }

    /**
     * Get the email address that should be used for verification.
     */
    public function getRouteKeyForVerification()
    {
        return $this->getKey();
    }

    // Optional: Remove the manual password mutator since we're using Laravel's casting
    // protected function setPasswordAttribute($value)
    // {
    //     if (!empty($value)) {
    //         // Avoid double-hashing if the password is already hashed
    //         $this->attributes['password'] =
    //             (strlen($value) === 60 && preg_match('/^\$2y\$/', $value))
    //             ? $value
    //             : Hash::make($value);
    //     }
    // }
}