<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    // Kita pakai guarded id saja biar praktis, atau fillable lengkap seperti di bawah
    protected $guarded = ['id'];

    // Kolom tanggal agar otomatis jadi object Carbon (bisa diformat jam/tanggal)
    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Relasi ke Transaksi (Shift ini punya banyak transaksi)
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}