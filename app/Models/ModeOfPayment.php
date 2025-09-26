<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeOfPayment extends Model
{
    protected $table = 'mode_of_payments';
    
    protected $fillable = [
        'mopname',
    ];

        public function dvRequests()
    {
        return $this->belongsToMany(DvRequest::class, 'dv_request_mode_of_payment')
                    ->withTimestamps()
                    ->withPivot('id');
    }
}
