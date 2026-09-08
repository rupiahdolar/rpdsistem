<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relasi ke Header Jurnal
    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    // Relasi ke Akun (COA)
    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}