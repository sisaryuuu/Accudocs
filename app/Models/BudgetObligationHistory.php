<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetObligationHistory extends Model
{
    protected $table = 'budget_obligation_history';
    
    protected $fillable = [
        'dv_request_id',
        'ors_request_id',
        'verified_by',
        'verification_step',
        'status_before',
        'status_after',
        'verification_data',
        'remarks',
        'verified_at'
    ];
    
    protected $casts = [
        'verification_data' => 'array',
        'verified_at' => 'datetime'
    ];
    
    public function dvRequest()
    {
        return $this->belongsTo(DvRequest::class);
    }
    
    public function orsRequest()
    {
        return $this->belongsTo(OrsRequest::class);
    }
    
    public function verifier()
    {
        return $this->belongsTo(AccountingUnit::class, 'verified_by');
    }
}