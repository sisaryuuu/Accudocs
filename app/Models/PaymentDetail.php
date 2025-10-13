<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDetail extends Model
{
     protected $table = 'payment_details';
    
    protected $fillable = [
         'dv_request_mode_of_payment_id',
        'bank_name',
        'bank_accnum',
        'bank_address',
    ];

        public function dvRequestModeOfPayment()
    {
        return $this->belongsTo(DvRequestModeOfPayment::class);
    }
}
