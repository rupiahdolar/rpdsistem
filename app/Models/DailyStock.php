<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'branch_id',
        'currency_code',
        'amount',       // Qty (Lembar)
        'average_rate', // Rate Modal
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}