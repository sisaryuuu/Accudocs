<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;


     const STATUS_DOCUMENT_AGING = 'document_aging';
    const STATUS_IMSD_RECEIVED = 'IMSD_received';
    const STATUS_IMSD_VERIFIED = 'IMSD_verified';
    const STATUS_INCOMING_TEV_RECEIVED = 'Incoming-TEV_received';
    const STATUS_INCOMING_TEV_VERIFIED = 'Incoming-TEV_verified';
    const STATUS_ORS_RECEIVED = 'ORS_received';
    const STATUS_RANCA_RECEIVED = 'RANCA_received';


    protected $fillable = [
        'focalperson_id', 
        'dv_request_id',  // ✅ Add this new field
        'status', 
        'action', 
        'remarks', 
        'read_at'
    ];

    protected $dates = ['read_at'];

    public function focalPerson()
    {
        return $this->belongsTo(FocalPerson::class);
    }

    public function dvRequest()
    {
        return $this->belongsTo(DvRequest::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
     public function isAgingNotification(): bool
    {
        return $this->status === self::STATUS_DOCUMENT_AGING;
    }
}