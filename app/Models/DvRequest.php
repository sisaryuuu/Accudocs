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
        'focalperson_id',
        'program_id',
        'respocenter_id',
        'particulars',
        'amount',
        'status',
        'received_by',
        'received_at',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
        'finalized_by',
        'finalized_at',
        'released_by',
        'released_at',
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

    // Workflow stage relationships
    public function receivedBy()
    {
        return $this->belongsTo(AccountingUnit::class, 'received_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(AccountingUnit::class, 'verified_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(AccountingUnit::class, 'approved_by');
    }

    public function finalizedBy()
    {
        return $this->belongsTo(AccountingUnit::class, 'finalized_by');
    }

    public function releasedBy()
    {
        return $this->belongsTo(AccountingUnit::class, 'released_by');
    }
}
