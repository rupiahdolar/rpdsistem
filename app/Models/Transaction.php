<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB; // <--- PENTING: Tambahan Library Database

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    // Kita ubah dari $guarded ke $fillable agar lebih eksplisit dan aman.
    protected $fillable = [
        'transaction_code',
        'branch_id',
        'user_id',
        'shift_id',
        'no_nota',
        
        // --- DATA NASABAH UTAMA ---
        'customer_type',
        'customer_name',
        'customer_identity_no',
        'customer_id_type',
        'customer_phone', // <-- TAMBAHKAN INI
        'customer_gender',      
        'customer_dob',         
        'customer_address',
        'customer_job',
        'customer_country',
        
        // --- DATA PENGURUS/PIC ---
        'representative_name',    
        'representative_id_type', 
        'representative_id_no',   

        // --- DATA APU PPT ---
        'source_of_funds',
        'transaction_purpose',

        // --- DATA TRANSAKSI ---
        'type',
        'currency',
        'amount_foreign',
        'rate',
        'total_idr',
        'payment_method',
        'bank_account_id',
    ];

    /**
     * SATPAM OTOMATIS (BOOTED METHOD)
     * Fungsi ini akan jalan sendiri saat Transaksi dihapus.
     */
    protected static function booted()
    {
        static::deleting(function ($transaction) {
            // 1. HAPUS JURNAL AKUNTANSI (Buku Besar)
            // Cari jurnal yang Reference No-nya sama dengan No Nota transaksi ini
            if ($transaction->no_nota) {
                DB::table('journal_entries')
                    ->where('reference_no', $transaction->no_nota)
                    ->delete();
            }

            // 2. HAPUS ARUS KAS (Jika tabelnya terpisah/menggunakan tabel lain)
            // (Opsional: Aktifkan jika Mamang punya tabel cash_flows terpisah)
            // DB::table('cash_flows')->where('reference_no', $transaction->no_nota)->delete();
        });
    }

    // --- RELASI-RELASI ---

    // Relasi ke User (Kasir)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Cabang
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Relasi ke Shift
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
    
    // Relasi ke Akun Bank (Jika Transfer)
    public function bankAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'bank_account_id');
    }
}