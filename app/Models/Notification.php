<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

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
}