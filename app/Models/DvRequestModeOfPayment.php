<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\Pivot;


class DvRequestModeOfPayment extends Pivot
{
    protected $table = 'dv_request_mode_of_payment';
    protected $fillable = ['dv_request_id', 'mode_of_payment_id'];

    public function paymentDetail()
    {
        return $this->hasOne(PaymentDetail::class, 'dv_request_mode_of_payment_id');
    }
    public function mop()
    {
        return $this->belongsTo(ModeOfPayment::class, 'mode_of_payment_id');
    }
}

