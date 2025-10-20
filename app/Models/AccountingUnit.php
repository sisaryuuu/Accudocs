<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use App\Traits\HasProfileImage;
use Spatie\Permission\Traits\HasRoles;

class AccountingUnit extends Authenticatable
{
    use HasFactory, HasRoles, HasProfileImage;

    protected $table = 'accountingunits';
    protected $guard_name = 'web'; // adjust if you have a custom "accountingunit" guard

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->fname} {$this->mi} {$this->lname}");
    }

    protected function setPasswordAttribute($value)
    {
        if (!empty($value)) {

            $this->attributes['password'] =
                (strlen($value) === 60 && preg_match('/^\$2y\$/', $value))
                ? $value
                : Hash::make($value);
        }
    }
}
