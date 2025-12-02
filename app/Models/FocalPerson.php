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
    use Notifiable, HasRoles;

    protected $table = 'focalpersons';
    protected $guard_name = 'focal';

    protected $fillable = [
        'fname',
        'mi', 
        'lname',
        'TIN_number',
        'Address',
        'email',
        'profile_image',
        'password',
        'contactnumber',
        'respocenter_id',
        'email_verified_at',
        'remember_token',
        
        // ✅ Add concurrent login tracking fields
        'last_login_at',
        'last_login_ip',
        'last_logout_at',
        'session_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_logout_at' => 'datetime',
    ];

    protected $attributes = [
        'profile_image' => 'default.png',
    ];

    // ============================================
    // Relationships
    // ============================================
    
    public function respocenter()
    {
        return $this->belongsTo(ResponCenter::class, 'respocenter_id');
    }

    // ============================================
    // Accessors
    // ============================================
    
    public function getFullNameAttribute()
    {
        return trim("{$this->fname} {$this->mi} {$this->lname}");
    }

    /**
     * Check if user is currently logged in (active session)
     */
    public function getIsActiveAttribute()
    {
        if (!$this->last_login_at) {
            return false;
        }

        // Consider user active if logged in within the last 24 hours AND no logout or login > logout
        return $this->last_login_at->gt(now()->subHours(24)) && 
               (!$this->last_logout_at || $this->last_login_at->gt($this->last_logout_at));
    }

    /**
     * Get current session status
     */
    public function getSessionStatusAttribute()
    {
        if (!$this->last_login_at) {
            return 'never_logged_in';
        }

        if ($this->is_active) {
            return 'active';
        }

        return 'inactive';
    }

    /**
     * Get formatted last activity time
     */
    public function getLastActivityFormattedAttribute()
    {
        if (!$this->last_login_at) {
            return 'Never';
        }

        return $this->last_login_at->diffForHumans();
    }

    // ============================================
    // Session Management Methods
    // ============================================

    /**
     * Record user login
     */
    public function recordLogin($ipAddress = null)
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
            'session_id' => session()->getId(),
            'last_logout_at' => null, // Clear logout when logging in
        ]);
        
        \Log::info("FocalPerson login recorded: {$this->email} from {$ipAddress}");
    }

    /**
     * Record user logout
     */
    public function recordLogout()
    {
        $this->update([
            'last_logout_at' => now(),
            'session_id' => null,
        ]);
        
        \Log::info("FocalPerson logout recorded: {$this->email}");
    }

    /**
     * Force logout user from all sessions
     */
    public function forceLogout()
    {
        $this->update([
            'last_logout_at' => now(),
            'session_id' => null,
        ]);

        // Also clear from active_sessions table
        $deleted = \Illuminate\Support\Facades\DB::table('active_sessions')
            ->where('user_id', $this->id)
            ->where('user_type', self::class)
            ->delete();
            
        \Log::info("FocalPerson force logout: {$this->email}, cleared {$deleted} active sessions");
    }

    /**
     * Check if user has active session
     */
    public function hasActiveSession()
    {
        // Check active_sessions table first (most reliable)
        $activeInSessions = \Illuminate\Support\Facades\DB::table('active_sessions')
            ->where('user_id', $this->id)
            ->where('user_type', self::class)
            ->where('last_activity', '>=', now()->subHours(24))
            ->exists();

        if ($activeInSessions) {
            return true;
        }

        // Fallback to user table check (less reliable)
        return $this->is_active;
    }

    /**
     * Get active session information
     */
    public function getActiveSession()
    {
        return \Illuminate\Support\Facades\DB::table('active_sessions')
            ->where('user_id', $this->id)
            ->where('user_type', self::class)
            ->where('last_activity', '>=', now()->subHours(24))
            ->first();
    }

    /**
     * Get all active sessions for this user
     */
    public function getAllActiveSessions()
    {
        return \Illuminate\Support\Facades\DB::table('active_sessions')
            ->where('user_id', $this->id)
            ->where('user_type', self::class)
            ->where('last_activity', '>=', now()->subHours(24))
            ->get();
    }

    // ============================================
    // Authentication Methods
    // ============================================

    /**
     * Get the email address for password reset
     */
    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    /**
     * Send password reset notification
     */
    public function sendPasswordResetNotification($token)
    {
        // You can implement custom password reset notification if needed
        $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
    }

    /**
     * Check if user email is verified
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

    // ============================================
    // Role & Permission Methods
    // ============================================

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $this->hasRole($roles);
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    // ============================================
    // Scopes
    // ============================================

    /**
     * Scope for users with active sessions
     */
    public function scopeWithActiveSessions($query)
    {
        return $query->where(function($q) {
            $q->where('last_login_at', '>=', now()->subHours(24))
              ->where(function($q2) {
                  $q2->whereNull('last_logout_at')
                     ->orWhereColumn('last_login_at', '>', 'last_logout_at');
              });
        });
    }

    /**
     * Scope for users currently online
     */
    public function scopeOnline($query)
    {
        return $query->withActiveSessions();
    }

    /**
     * Scope for users who have never logged in
     */
    public function scopeNeverLoggedIn($query)
    {
        return $query->whereNull('last_login_at');
    }

    /**
     * Scope for users who are verified
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope for users who are not verified
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    /**
     * Scope for users by respocenter
     */
    public function scopeByResponcenter($query, $responcenterId)
    {
        return $query->where('respocenter_id', $responcenterId);
    }

    // ============================================
    // Utility Methods
    // ============================================

    /**
     * Get user's initial for avatar
     */
    public function getInitialsAttribute()
    {
        $initials = '';
        
        if ($this->fname) {
            $initials .= strtoupper(substr($this->fname, 0, 1));
        }
        
        if ($this->lname) {
            $initials .= strtoupper(substr($this->lname, 0, 1));
        }

        return $initials ?: 'FP';
    }

    /**
     * Get profile image URL
     */
    public function getProfileImageUrlAttribute()
    {
        if ($this->profile_image && $this->profile_image !== 'default.png') {
            return asset('storage/profiles/' . $this->profile_image);
        }

        return asset('images/default-profile.png');
    }

    /**
     * Check if user can access QR features
     */
    public function canAccessQrFeatures()
    {
        $allowedRoles = ['Cashier', 'Regional_Director'];
        return $this->hasAnyRole($allowedRoles);
    }

    /**
     * Get user type for session tracking
     */
    public function getUserTypeForSession()
    {
        return self::class;
    }

    // ============================================
    // Password Mutator
    // ============================================
    
    /**
     * Set password attribute with hashing
     */
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

    // ============================================
    // Event Handlers (Optional)
    // ============================================
    
    protected static function boot()
    {
        parent::boot();

        // Auto-generate some data if needed
        static::creating(function ($model) {
            // Add any creation logic here
        });

        // Clean up sessions when user is deleted
        static::deleting(function ($model) {
            \Illuminate\Support\Facades\DB::table('active_sessions')
                ->where('user_id', $model->id)
                ->where('user_type', self::class)
                ->delete();
        });
    }
}