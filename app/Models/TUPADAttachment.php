<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TupadAttachment extends Model
{
    use HasFactory;

    protected $table = 'tupad_attachments';

    protected $fillable = [
        'dv_request_id',
        'name',
        'modality',
        'is_present',
        'sort_order',
    ];

    protected $casts = [
        'is_present' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Each attachment belongs to one DV Request (nullable if master template)
    public function dvRequest()
    {
        return $this->belongsTo(DvRequest::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    // Get only master template (no dv_request_id)
    public function scopeMaster($query)
    {
        return $query->whereNull('dv_request_id');
    }

    // Get only attached to a specific dv_request
    public function scopeForRequest($query, $dvRequestId)
    {
        return $query->where('dv_request_id', $dvRequestId);
    }

    // Group by modality
    public function scopeByModality($query, $modality)
    {
        return $query->where('modality', $modality);
    }
}
