<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TEVAttachment extends Model
{
     protected $table = 'tev_attachments';

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
}
