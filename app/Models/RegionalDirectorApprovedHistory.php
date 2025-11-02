<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegionalDirectorApprovedHistory extends Model
{
    use HasFactory;

    protected $table = 'regional_director_history';

    protected $fillable = [
        'dv_request_id',
        'approved_by',
        'approval_step',
        'status_before',
        'status_after',
        'approval_data',
        'remarks',
        'approved_at'
    ];

    protected $casts = [
        'approval_data' => 'array',
        'approved_at' => 'datetime'
    ];

    public function dvRequest()
    {
        return $this->belongsTo(DvRequest::class);
    }

    public function regionalDirector()
    {
        return $this->belongsTo(AccountingUnit::class, 'approved_by');
    }
}