<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Biaya Operasional (PERBAIKAN STRUKTUR)
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            
            // Kolom Data
            $table->date('date');
            $table->string('name');     // <--- WAJIB ADA (Nama Pengeluaran)
            $table->string('category')->nullable(); 
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            
            // Relasi (Foreign Keys)
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade'); // <--- WAJIB ADA
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');  // <--- INI YANG ERROR TADI
            
            $table->timestamps();
        });

        // 2. Tabel Mutasi Modal
        Schema::create('equity_mutations', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('type', ['PRIVE', 'SETOR_MODAL']);
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('equity_mutations');
    }
};