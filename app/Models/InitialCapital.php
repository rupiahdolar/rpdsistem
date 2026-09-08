<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB; // <--- PENTING: Tambahan Wajib

class InitialCapital extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'branch_id',
        'amount',
        'forex_stocks',
        'description',
        'user_id'
    ];

    /**
     * Casting Data
     */
    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'forex_stocks' => 'array', 
    ];

    /**
     * SATPAM OTOMATIS (BOOTED METHOD)
     * Kalau Modal Awal dihapus, Jurnalnya (CAP-XXXXXX) ikut mati.
     */
    protected static function booted()
    {
        static::deleting(function ($capital) {
            // Logic Generatenya: CAP + ID (dipad 6 digit)
            // Contoh: ID 5 jadi CAP-000005
            $refNo = 'CAP-' . str_pad($capital->id, 6, '0', STR_PAD_LEFT);
            
            // Tembak Jurnalnya
            DB::table('journal_entries')
                ->where('reference_no', $refNo)
                ->delete();
        });
    }

    // --- RELASI ---

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}