<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edited_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('no_nota');
            $table->string('transaction_code');
            $table->string('customer_name');
            $table->string('customer_identity_no')->nullable();
            
            // Simpan perubahan data dalam format JSON atau text ringkas
            $table->text('changes_summary')->nullable(); // Menjelaskan apa saja yang diubah (misal: nominal, rate, nama nasabah)
            $table->decimal('old_total_idr', 15, 2)->default(0);
            $table->decimal('new_total_idr', 15, 2)->default(0);

            $table->string('edited_by_name');
            $table->string('edited_by_role');
            $table->timestamp('transaction_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edited_transactions');
    }
};