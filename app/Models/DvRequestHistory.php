<?php
// app/Models/DvRequestHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DvRequestHistory extends Model
{
    use HasFactory;

    protected $table = 'dv_request_history';

    protected $fillable = [
        'reference_number',
        'dv_number',
        'user_sequence',
        'accounting_control_number',
        'accountingunit_id',
        'focalperson_id',
        'program_id',
        'respocenter_id',
        'particulars',
        'amount',
        'status',
        'tupad_modality',
        'tev_modality',
        'locked_by',
        'locked_at',
        'ors_number', // Added ORS number
        'history_reason',
        'moved_to_history_at',
        'moved_by'
    ];

    // Relationships
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

    public function orsRequest()
    {
        return $this->hasOne(OrsRequest::class, 'dv_request_id');
    }
}