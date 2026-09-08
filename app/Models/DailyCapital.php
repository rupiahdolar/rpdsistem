<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCapital extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'branch_id',
        'amount',
        'description',
        'user_id',
    ];

    // Relasi ke Cabang
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Relasi ke User (Siapa yang input)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}