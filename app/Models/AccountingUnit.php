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
        'email_verified_at',
        'primary_role', // ✅ Add primary role field
        
        // Concurrent login tracking fields
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
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'last_logout_at' => 'datetime',
    ];

    // ============================================
    // Relationships
    // ============================================
    
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    // ============================================
    // Multi-Role Methods
    // ============================================

    /**
     * Get all roles with additional info
     */
    public function getAllRolesWithInfo()
    {
        return $this->roles()->with('permissions')->get();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Get primary role (first role assigned or specified primary)
     */
    public function getPrimaryRoleAttribute()
    {
        if ($this->attributes['primary_role'] ?? null) {
            return $this->attributes['primary_role'];
        }
        
        return $this->roles()->first()?->name;
    }

    /**
     * Set primary role
     */
    public function setPrimaryRoleAttribute($value)
    {
        $this->attributes['primary_role'] = $value;
    }

    /**
     * Get current active role (from session or primary)
     */
    public function getCurrentRoleAttribute()
    {
        return session('current_role', $this->primary_role);
    }

    /**
     * Check if user has multiple roles
     */
    public function getHasMultipleRolesAttribute()
    {
        return $this->roles()->count() > 1;
    }

    /**
     * Get role names as array
     */
    public function getRoleNamesArrayAttribute()
    {
        return $this->getRoleNames()->toArray();
    }

    /**
     * Switch to a different role
     */
    public function switchToRole($roleName)
    {
        if (!$this->hasRole($roleName)) {
            throw new \Exception("User does not have role: {$roleName}");
        }

        session(['current_role' => $roleName]);
        return true;
    }

    /**
     * Get permissions for current role
     */
    public function getCurrentRolePermissions()
    {
        $currentRole = $this->current_role;
        $role = $this->roles()->where('name', $currentRole)->first();
        
        return $role ? $role->permissions : collect();
    }

    /**
     * Check permission for current role
     */
    public function canWithCurrentRole($permission)
    {
        return $this->hasPermissionTo($permission);
    }

    /**
     * Get dashboard route for current role
     */
    public function getDashboardRouteAttribute()
    {
        $roleRoutes = [
            'Admin' => 'Admin.dashboard',
            'IMSD' => 'RegionalOffices.IMSD.index',
            'Budget_Officer' => 'BudgetOfficer.dvrequests.index',
            'Accounting_III' => 'Accounting.dvrequests.index',
            'Outgoing' => 'Outgoing.dvrequests.index',
            'Focal_Person' => 'FocalPerson.dvrequest.index',
            'Incoming-GIP' => 'Incoming.GIP.index',
            'Incoming-TUPAD' => 'Incoming.TUPAD.index',
            'Incoming-TEV' => 'Incoming.TEV.index',
            'Regional_Director' => 'regional.director.dashboard',
            'Cashier' => 'cashier.dashboard',
        ];

        return $roleRoutes[$this->current_role] ?? 'login';
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

        // Consider user active if logged in within the last 24 hours
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
        ]);
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
        \Illuminate\Support\Facades\DB::table('active_sessions')
            ->where('user_id', $this->id)
            ->where('user_type', self::class)
            ->delete();
    }

    /**
     * Check if user has active session
     */
    public function hasActiveSession()
    {
        // Check both user table and active_sessions table
        $activeInTable = $this->is_active;
        
        $activeInSessions = \Illuminate\Support\Facades\DB::table('active_sessions')
            ->where('user_id', $this->id)
            ->where('user_type', self::class)
            ->where('last_activity', '>=', now()->subHours(24))
            ->exists();

        return $activeInTable || $activeInSessions;
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

    // ============================================
    // Password Mutator (Fallback for older Laravel)
    // ============================================
    
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
     * Scope for users with multiple roles
     */
    public function scopeWithMultipleRoles($query)
    {
        return $query->has('roles', '>', 1);
    }

    /**
     * Scope for users with specific role
     */
    public function scopeWithRole($query, $role)
    {
        return $query->whereHas('roles', function($q) use ($role) {
            $q->where('name', $role);
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
}