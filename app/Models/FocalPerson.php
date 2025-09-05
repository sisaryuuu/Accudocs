<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\HasProfileImage;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class FocalPerson extends Authenticatable
{
    use HasFactory, HasRoles, HasProfileImage;

    protected $table = 'focalpersons';
    protected $guard_name = 'focal';

    protected $attributes = [
    'profile_image' => 'default.png',];

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
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function respoCenter()
    {
        return $this->belongsTo(ResponCenter::class, 'respocenter_id');
    }
     protected function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
        public function getFullNameAttribute()
    {
        return "{$this->fname} {$this->mi} {$this->lname}";
    }
}