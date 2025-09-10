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
    ];


    public function dvRequest()
    {
        return $this->belongsTo(DVRequest::class);
    }
}
