<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dttot_lists', function (Blueprint $table) {
            $table->id();
            $table->string('densus_code')->nullable()->index(); // Kode Densus (Contoh: ILQ-308, IDD-032)
            $table->string('entity_type')->default('Orang');    // Orang / Korporasi
            $table->text('name');                               // Nama Lengkap + Alias
            $table->text('birth_place')->nullable();            // Tempat Lahir
            $table->string('birth_date')->nullable();           // Tanggal Lahir (String agar fleksibel)
            $table->string('nationality')->nullable();          // WN/Asal Negara
            $table->text('address')->nullable();                // Alamat
            $table->text('description')->nullable();            // Deskripsi / NIK / No Paspor / Catatan
            $table->string('source_doc')->nullable();           // Nama File Sumber Excel
            $table->timestamps();
        });

        // 2. Tabel LTKM (Laporan Transaksi Keuangan Mencurigakan)
        Schema::create('suspicious_reports', function (Blueprint $table) {
            $table->id();
            // Bisa disambungkan ke transaksi yang ada (Opsional)
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            
            // Atau input manual jika orangnya tidak jadi transaksi (Attempted)
            $table->string('customer_name')->nullable();
            $table->string('identity_no')->nullable();
            
            $table->text('suspicious_reason'); // Alasan mencurigakan (Structuring, Identitas Palsu, dll)
            $table->string('status')->default('PENDING'); // PENDING, REPORTED to PPATK
            $table->foreignId('reported_by')->constrained('users'); // Siapa yang lapor (Admin)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dttot_lists');
        Schema::dropIfExists('suspicious_reports');
    }
};