<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Relasi ke Detail Item Jurnal (Debit/Kredit)
     */
    public function items()
    {
        return $this->hasMany(JournalItem::class);
    }

    /**
     * Relasi ke Kantor Cabang
     * (Ini yang menyebabkan error RelationNotFoundException tadi)
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relasi ke User pembuat jurnal
     */
    public function user() // created_by
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}