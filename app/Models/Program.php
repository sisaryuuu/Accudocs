<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;
    protected $table = 'programs';
    protected $fillable = [
        'prcode',
        'description'
    ];

    /**
     * Get the accounting units associated with the program.
     */
    public function accountingUnits()
    {
        return $this->hasMany(AccountingUnit::class);
    }
}