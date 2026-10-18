<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebtPayment extends Model
{
    use HasFactory;

    protected $table = 'debt_payments';

    protected $fillable = [
        'branch_id',
        'date',
        'liability_account_id',
        'payment_account_id',
        'amount',
        'interest_amount',
        'description',
        'created_by',
    ];

    // Relasi ke Cabang
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Relasi ke Akun Utang (COA)
    public function liabilityAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'liability_account_id');
    }

    // Relasi ke Akun Kas/Bank (COA)
    public function paymentAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'payment_account_id');
    }

    // Relasi ke User pembuat
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}