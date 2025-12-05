<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GIPAttachment extends Model
{
    use HasFactory;

    protected $table = 'gip_attachments';

    protected $fillable = [
        'dv_request_id',
        'name',
        'is_present',
        'remarks', // ✅ Added remarks field
    ];

    /**
     * ✅ Cast is_present to handle three states: true, false, null
     */
    protected $casts = [
        'is_present' => 'boolean',
    ];

    /**
     * ✅ Accessor to get status label
     */
    public function getStatusLabelAttribute()
    {
        if ($this->is_present === true) {
            return 'Present';
        } elseif ($this->is_present === false) {
            return 'Not Present';
        } else {
            return 'N/A';
        }
    }

    /**
     * ✅ Accessor to get status color
     */
    public function getStatusColorAttribute()
    {
        if ($this->is_present === true) {
            return 'green';
        } elseif ($this->is_present === false) {
            return 'red';
        } else {
            return 'gray';
        }
    }

    /**
     * ✅ Check if attachment needs remarks
     */
    public function needsRemarks()
    {
        return $this->is_present === false;
    }

    /**
     * Relationship to DV Request
     */
    public function dvRequest()
    {
        return $this->belongsTo(DvRequest::class);
    }
}