<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAgingAlert extends Model
{
    use HasFactory;

    protected $table = 'document_aging_alerts';

    protected $fillable = [
        'dv_request_id',
        'days_old',
        'urgency',
        'alert_date',
    ];

    protected $casts = [
        'alert_date' => 'date',
    ];

    /**
     * Get the DV Request associated with this aging alert
     */
    public function dvRequest(): BelongsTo
    {
        return $this->belongsTo(DvRequest::class, 'dv_request_id');
    }

    public function scopeByUrgency($query, $urgency)
    {
        return $query->where('urgency', $urgency);
    }

    /**
     * Scope: Get critical alerts (10+ days)
     */
    public function scopeCritical($query)
    {
        return $query->where('urgency', 'critical');
    }

    /**
     * Scope: Get high priority alerts (7-10 days)
     */
    public function scopeHigh($query)
    {
        return $query->where('urgency', 'high');
    }

    /**
     * Scope: Get medium priority alerts (3-7 days)
     */
    public function scopeMedium($query)
    {
        return $query->where('urgency', 'medium');
    }

}