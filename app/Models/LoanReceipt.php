<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 'date', 'liability_account_id', 'cash_account_id', 
        'amount', 'description', 'created_by'
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function liabilityAccount() {
        return $this->belongsTo(ChartOfAccount::class, 'liability_account_id');
    }

    public function cashAccount() {
        return $this->belongsTo(ChartOfAccount::class, 'cash_account_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'created_by');
    }
}