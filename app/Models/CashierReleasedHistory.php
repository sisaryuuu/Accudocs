<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierReleasedHistory extends Model
{
    use HasFactory;

    protected $table = 'cashier_releasedhistory';

    protected $fillable = [
        'dv_request_id',
        'released_by',
        'release_step',
        'status_before',
        'status_after',
        'release_data',
        'remarks',
        'released_at'
    ];

    protected $casts = [
        'release_data' => 'array',
        'released_at' => 'datetime'
    ];

    public function dvRequest()
    {
        return $this->belongsTo(DvRequest::class);
    }

    public function cashier()
    {
        return $this->belongsTo(AccountingUnit::class, 'released_by');
    }
}