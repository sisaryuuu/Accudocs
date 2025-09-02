<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DvRequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'dv_request_id',
        'accountingunit_id',
        'external_actor',
        'action',
        'remarks',
    ];

    public function dvRequest()
    {
        return $this->belongsTo(DvRequest::class);
    }
    public function accountingUnit()
    {
        return $this->belongsTo(AccountingUnit::class,'accountingunit_id');
    }
    public function focalperson()
    {
        return $this->belongsTo(FocalPerson::class, 'focalperson_id');
    }
}
