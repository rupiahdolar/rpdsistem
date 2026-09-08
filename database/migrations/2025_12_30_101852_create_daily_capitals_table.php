<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ganti nama tabel jadi 'initial_capitals'
        Schema::create('initial_capitals', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            
            // Relasi ke Cabang
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            
            // Modal Rupiah
            $table->decimal('amount', 20, 2)->default(0); 
            
            // PENTING: Kolom JSON untuk simpan stok valas (Qty & Rate)
            $table->json('forex_stocks')->nullable();     
            
            $table->string('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('initial_capitals');
    }
};