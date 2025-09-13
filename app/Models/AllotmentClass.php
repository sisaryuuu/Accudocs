<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllotmentClass extends Model
{
    protected $table = 'allotment_classes';
    
    protected $fillable = [
        'allotcode',
        'description',
    ];

    public function OrsRequests()
    {
        return $this->hasMany(OrsRequest::class, 'allotmentclass_id');
    }
}
