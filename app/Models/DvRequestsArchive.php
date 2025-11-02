<?php
// app/Models/DvRequestsArchive.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class DvRequestsArchive extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dv_requests_archive';

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
        'original_status',
        'archive_reason',
        'completed_at',
        'archived_at',
        'archived_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'locked_at' => 'datetime',
        'completed_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    // Relationships
    public function focalperson()
    {
        return $this->belongsTo(FocalPerson::class, 'focalperson_id');
    }

    public function accountingUnit()
    {
        return $this->belongsTo(AccountingUnit::class, 'accountingunit_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function respocenter()
    {
        return $this->belongsTo(RespoCenter::class, 'respocenter_id');
    }

    public function archivedBy()
    {
        return $this->belongsTo(FocalPerson::class, 'archived_by');
    }
}