<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UacsCode extends Model
{
    protected $table = 'uacscodes';
    
    protected $fillable = [
        'uacscode',
        'description',
    ];

    public function OrsRequests()
    {
        return $this->hasMany(OrsRequest::class, 'uacscode_id');
    }
}
