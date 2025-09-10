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
        'accountingunit_id',
        'received_by_id',
        'focalperson_id',
        'program_id',
        'respocenter_id',
        'particulars',
        'amount',
        'status',
    ];

    protected $casts = [
        'received_at'   => 'datetime',
        'verified_at'   => 'datetime',
        'approved_at'   => 'datetime',
        'finalized_at'  => 'datetime',
        'released_at'   => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function logs()
    {
        return $this->hasMany(DvRequestLog::class, 'dv_request_id');
    }
    public function gipAttachments()
    {
        return $this->hasMany(GIPAttachment::class, 'dv_request_id');
    }

    public function focalperson()
    {
        return $this->belongsTo(FocalPerson::class, 'focalperson_id');
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
