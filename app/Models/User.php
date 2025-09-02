<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function focalperson()
    {
        return $this->hasOne(FocalPerson::class);
    }
     public function accountingunit()
    {
        return $this->hasOne(AccountingUnit::class);
    }
}
