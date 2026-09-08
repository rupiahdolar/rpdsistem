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
        Schema::table('transactions', function (Blueprint $table) {
            // Menambahkan index gabungan (Composite Index)
            // Sistem akan langsung menunjuk ke nomor identitas pada rentang waktu tertentu
            $table->index(['customer_identity_no', 'created_at'], 'idx_threshold_check');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Menghapus index jika melakukan rollback
            $table->dropIndex('idx_threshold_check');
        });
    }
};