<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContinuingOrCurrent extends Model
{
    protected $table = 'contcurrents';
    
    protected $fillable = [
        'ccode',
        'description',
    ];

    public function OrsRequests()
    {
        return $this->hasMany(OrsRequest::class, 'continuingorcurrent_id');
    }
}
