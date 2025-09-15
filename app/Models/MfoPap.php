<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfoPap extends Model
{
    protected $table = 'mfopaps';
    
    protected $fillable = [
        'mfocode',
        'mfodescription',
    ];

    public function OrsRequests()
    {
        return $this->hasMany(OrsRequest::class, 'mfopap_id');
    }
}
