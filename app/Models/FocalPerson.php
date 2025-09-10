<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\HasProfileImage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class FocalPerson extends Authenticatable
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
        'program_id',
        'respocenter_id',
        'profile_image',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

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

    protected function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            // Avoid double-hashing if the password is already hashed
            $this->attributes['password'] =
                (strlen($value) === 60 && preg_match('/^\$2y\$/', $value))
                ? $value
                : Hash::make($value);
        }
    }
}
