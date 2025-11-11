<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TUPADAttachment extends Model
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
        'sort_order' => 'integer',
    ];

    /**
     * Relationship: Belongs to DV Request
     */
    public function dvRequest()
    {
        return $this->belongsTo(DvRequest::class, 'dv_request_id');
    }

    /**
     * Scope: Get only master templates
     */
    public function scopeMasterTemplates($query)
    {
        return $query->whereNull('dv_request_id');
    }

    /**
     * Scope: Get attachments for a specific modality
     */
    public function scopeForModality($query, $modality)
    {
        return $query->where('modality', $modality);
    }

    /**
     * Scope: Get attachments for a specific DV request
     */
    public function scopeForDvRequest($query, $dvRequestId)
    {
        return $query->where('dv_request_id', $dvRequestId);
    }
}