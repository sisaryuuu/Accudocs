<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrsRequest extends Model
{
    protected $table = 'ors_requests';
    
    protected $fillable = [
        'orsnumber',
        'dv_request_id',
        'accountingunit_id',
        'focalperson_id',
        'uacscode_id',
        'adlcode_id',
        'allotmentclass_id',
        'mfopap_id',
        'continuingorcurrent_id'
    ];


    public function DvRequest()
    {
        return $this->belongsTo(DvRequest::class, 'dv_request_id');
    }
      public function logs()
    {
        return $this->hasMany(DvRequestLog::class, 'dv_request_id');
    }
    public function AccountingUnit()
    {
        return $this->belongsTo(AccountingUnit::class, 'accountingunit_id');
    }
    public function FocalPerson()
    {
        return $this->belongsTo(FocalPerson::class, 'focalperson_id');
    }
    public function UacsCode()
    {
        return $this->belongsTo(UacsCode::class, 'uacscode_id');
    }

    public function AdlCode()
    {
        return $this->belongsTo(AdlCode::class, 'adlcode_id');
    }

    public function ContinuingOrCurrent()
    {
        return $this->belongsTo(ContinuingOrCurrent::class, 'continuingorcurrent_id');
    }

    public function AllotmentClass()
    {
        return $this->belongsTo(AllotmentClass::class, 'allotmentclass_id');
    }

    public function Mfopap()
    {
        return $this->belongsTo(Mfopap::class, 'mfopap_id');
    }
}
