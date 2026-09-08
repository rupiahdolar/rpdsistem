<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // --- 1. RELASI & IDENTITAS (BAGIAN YANG DIPERBAIKI) ---
            $table->string('transaction_code')->unique(); // Wajib Unique agar tidak duplikat
            
            // Terhubung ke kantor cabang
            $table->foreignId('branch_id')->constrained()->onDelete('cascade'); 
            
            // Terhubung ke user yang login (Kasir/Admin)
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            
            // [PENTING] Ini penghubung ke Tombol "Tutup Shift"
            // Menggantikan 'cashier_session_id'
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade'); 
            
            // --- 2. DATA KEUANGAN (TIDAK DIUBAH, SUDAH BAGUS) ---
            $table->enum('type', ['buy', 'sell']); // Beli/Jual
            $table->string('currency', 3);         // USD, SGD, EUR
            $table->decimal('amount_foreign', 15, 2); // Jumlah Valas
            $table->decimal('rate', 15, 2);           // Kurs
            $table->decimal('total_idr', 15, 2);      // Total Rupiah
            
            // --- 3. DATA NASABAH (CDD - Customer Due Diligence) ---
            $table->string('no_nota')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_identity_no')->nullable();
            $table->string('customer_id_type')->nullable(); // KTP/PASPOR  
            $table->string('customer_country')->nullable();  
            $table->string('customer_job')->nullable();      
            $table->string('customer_address')->nullable();  

            // --- 4. DATA APU-PPT (Pencegahan Pencucian Uang) ---
            // Wajib untuk pelaporan LTKT/LTKM ke PPATK
            $table->string('source_of_funds')->nullable(); // Gaji, Warisan, dll
            $table->string('transaction_purpose')->nullable(); // Wisata, Bisnis, dll

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};