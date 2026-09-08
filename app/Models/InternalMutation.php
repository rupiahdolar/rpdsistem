<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalMutation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Relasi ke Akun Bank
    public function bankAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'bank_account_id');
    }

    // Relasi ke Cabang
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Relasi ke User yang input
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}