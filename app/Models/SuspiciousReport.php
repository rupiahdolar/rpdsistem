<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuspiciousReport extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database yang terhubung dengan model ini.
     */
    protected $table = 'suspicious_reports';

    /**
     * Kolom-kolom yang diizinkan untuk diisi secara Mass Assignment (create/update).
     */
    protected $fillable = [
        'customer_name',
        'identity_no',
        'suspicious_reason',
        'status',
        'reported_by',
    ];

    /**
     * Relasi ke model User (Pengguna/Petugas yang melaporkan LTKM).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}