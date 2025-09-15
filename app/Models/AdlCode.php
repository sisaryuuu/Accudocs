<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdlCode extends Model
{
    protected $table = 'adlcodes';
    
    protected $fillable = [
        'adlcode',
        'description',
    ];

    public function OrsRequests()
    {
        return $this->hasMany(OrsRequest::class, 'adlcode_id');
    }
    
}
