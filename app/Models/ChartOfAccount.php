<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Helper untuk menampilkan nama lengkap akun (Kode - Nama)
    public function getFullNameAttribute()
    {
        return $this->code . ' - ' . $this->name;
    }

    // === [YANG TADI KURANG] ===
    // Relasi: Satu Akun bisa memiliki banyak Item Jurnal (Debit/Kredit)
    public function journalItems()
    {
        // Pastikan nama model JournalItem benar dan namespace-nya sesuai
        return $this->hasMany(JournalItem::class, 'account_id');
    }
}