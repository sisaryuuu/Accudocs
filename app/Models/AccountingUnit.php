<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use App\Traits\HasProfileImage;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;

class AccountingUnit extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, HasProfileImage;

    protected $table = 'accountingunits';
    protected $guard_name = 'web';

    protected $fillable = [
        'fname',
        'mi',
        'lname',
        'email',
        'password',
        'program_id',
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
        return "{$this->fname} {$this->mi} {$this->lname}";
    }

    protected function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }
}
