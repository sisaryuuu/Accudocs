<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ORSAgingAlert extends Model
{
    use HasFactory;

    protected $table = 'ors_aging_alerts';

    protected $fillable = [
        'dv_request_id',
        'ors_request_id',
        'current_status',
        'days_in_status',
        'threshold_days',
        'urgency',
        'alert_date',
        'acknowledged',
        'acknowledged_at',
        'acknowledged_by',
        'acknowledgment_notes'
    ];

    protected $casts = [
        'alert_date' => 'date',
        'acknowledged_at' => 'datetime',
    ];

    // Relationships
    public function dvRequest()
    {
        return $this->belongsTo(DvRequest::class);
    }

    public function orsRequest()
    {
        return $this->belongsTo(OrsRequest::class);
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(AccountingUnit::class, 'acknowledged_by');
    }

    // Scopes
    public function scopeUnacknowledged($query)
    {
        return $query->where('acknowledged', false);
    }

    public function scopeUrgent($query)
    {
        return $query->whereIn('urgency', ['critical', 'high']);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('current_status', $status);
    }

    public function scopeToday($query)
    {
        return $query->where('alert_date', today());
    }

    // Methods
    public function markAsAcknowledged($userId, $notes = null)
    {
        $this->update([
            'acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
            'acknowledgment_notes' => $notes,
        ]);
    }

    public function isOverdue()
    {
        return $this->days_in_status > $this->threshold_days;
    }
}