<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponCenter extends Model
{
    use HasFactory;

    protected $table = 'responcenters';

    protected $fillable = [
        'rccode',
        'description'
    ];



    /**
     * Get the focal persons associated with the program.
     */
    public function focalPersons()
    {
        return $this->hasMany(FocalPerson::class);
    }
}