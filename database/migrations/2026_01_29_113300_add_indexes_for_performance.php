<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('internal_mutations', function (Blueprint $table) {
            $table->index('transaction_date'); // Biar filter tanggal cepat
            $table->index('bank_account_id');  // Biar filter bank cepat
            $table->index('type');             // Biar filter jenis cepat
        });
        
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->index('date');             // Untuk CashFlow
            $table->index('branch_id');
        });

        Schema::table('journal_items', function (Blueprint $table) {
            $table->index('account_id');       // Untuk filter buku besar/cashflow
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
