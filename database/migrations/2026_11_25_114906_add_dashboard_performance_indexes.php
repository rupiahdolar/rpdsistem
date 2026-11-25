<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['branch_id', 'created_at'], 'idx_trx_branch_created');
            $table->index(['branch_id', 'payment_method', 'created_at'], 'idx_trx_branch_pay_created');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['branch_id', 'date'], 'idx_exp_branch_date');
        });

        Schema::table('internal_mutations', function (Blueprint $table) {
            $table->index(['branch_id', 'transaction_date'], 'idx_mut_branch_date');
        });

        Schema::table('debt_payments', function (Blueprint $table) {
            $table->index(['branch_id', 'date'], 'idx_debt_branch_date');
        });

        Schema::table('loan_receipts', function (Blueprint $table) {
            $table->index(['branch_id', 'date'], 'idx_loan_branch_date');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_trx_branch_created');
            $table->dropIndex('idx_trx_branch_pay_created');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('idx_exp_branch_date');
        });

        Schema::table('internal_mutations', function (Blueprint $table) {
            $table->dropIndex('idx_mut_branch_date');
        });

        Schema::table('debt_payments', function (Blueprint $table) {
            $table->dropIndex('idx_debt_branch_date');
        });

        Schema::table('loan_receipts', function (Blueprint $table) {
            $table->dropIndex('idx_loan_branch_date');
        });
    }
};