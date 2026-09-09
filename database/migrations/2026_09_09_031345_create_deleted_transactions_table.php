<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deleted_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('no_nota');
            $table->string('transaction_code')->nullable();
            $table->string('customer_name');
            $table->string('customer_identity_no')->nullable();
            $table->string('currency');
            $table->decimal('amount_foreign', 15, 2);
            $table->decimal('rate', 15, 2);
            $table->decimal('total_idr', 15, 2);
            $table->string('type'); // buy / sell
            $table->string('deleted_by_name'); // Nama Admin/Kasir yang menghapus
            $table->string('deleted_by_role'); // Role (admin / cashier)
            $table->string('deletion_type');   // 'SINGLE_ITEM' atau 'FULL_NOTA'
            $table->timestamp('transaction_date')->nullable(); // Tanggal transaksi awal
            $table->timestamps(); // created_at = Waktu saat dihapus
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deleted_transactions');
    }
};
