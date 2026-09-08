<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            
            // 1. Kolom Metode Pembayaran (CASH / TRANSFER)
            if (!Schema::hasColumn('transactions', 'payment_method')) {
                $table->enum('payment_method', ['CASH', 'TRANSFER'])
                      ->default('CASH')
                      ->after('total_idr'); // Letakkan setelah kolom total_idr
            }

            // 2. Kolom ID Bank (Jika Transfer, masuk ke bank mana?)
            if (!Schema::hasColumn('transactions', 'bank_account_id')) {
                $table->unsignedBigInteger('bank_account_id')
                      ->nullable()
                      ->after('payment_method');
            }
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('transactions', 'bank_account_id')) {
                $table->dropColumn('bank_account_id');
            }
        });
    }
};