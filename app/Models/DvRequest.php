<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DvRequest extends Model
{
    use HasFactory;

    protected $table = 'dv_requests';

    protected $fillable = [
        'dv_number',
        'reference_number',
        'accounting_control_number',
        'user_sequence',     
        'accountingunit_id',
        'received_by_id',
        'focalperson_id',
        'program_id',
        'respocenter_id',
        'particulars',
        'amount',
        'status',
        'locked_at',
        'locked_by',
    ];

    public function logs()
    {
        return $this->hasMany(DvRequestLog::class, 'dv_request_id');
    }
    public function gipAttachments()
    {
        return $this->hasMany(GIPAttachment::class, 'dv_request_id');
    }
    public function tupadAttachments()
    {
        return $this->hasMany(TUPADAttachment::class, 'dv_request_id');
    }

    public function focalperson()
    {
        return $this->belongsTo(FocalPerson::class, 'focalperson_id');
    }

    public function orsRequest()
    {
        return $this->hasOne(OrsRequest::class, 'dv_request_id');
    }
    public function modeOfPayments()
    {
        return $this->belongsToMany(ModeOfPayment::class, 'dv_request_mode_of_payment')
                    ->using(DvRequestModeOfPayment::class)
                    ->withPivot('id') // pivot I
                    ->withTimestamps();
    }

    public function paymentDetails()
    {
        return $this->hasManyThrough(
            PaymentDetail::class,
            DvRequestModeOfPayment::class,
            'dv_request_id', // FK on pivot
            'dv_request_mode_of_payment_id', // FK on details
            'id', // local DV id
            'id'  // pivot id
        );
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }
    public function respocenter()
    {
        return $this->belongsTo(ResponCenter::class, 'respocenter_id');
    }
    public function receivedBy() 
    { 
        return $this->belongsTo(AccountingUnit::class, 'received_by_id');
    }

    // Workflow stage relationships
    public function accountingUnit()
    {
        return $this->belongsTo(AccountingUnit::class, 'accountingunit_id');
    }
}
