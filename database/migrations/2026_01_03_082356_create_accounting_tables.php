<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TABEL CHART OF ACCOUNTS (Daftar Akun / COA)
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Cth: 1-1001
            $table->string('name');           // Cth: Kas Besar
            
            // Tipe Akun Utama (Untuk Pengelompokan Laporan)
            $table->enum('type', [
                'ASSET',        // Harta (Kas, Bank, Piutang, Inventaris)
                'LIABILITY',    // Kewajiban (Hutang)
                'EQUITY',       // Modal (Modal Disetor, Laba Ditahan)
                'REVENUE',      // Pendapatan (Jual Valas, Jasa)
                'EXPENSE'       // Beban (Gaji, Listrik, HPP)
            ]);
            
            // Posisi Saldo Normal (Untuk Validasi Akuntansi)
            $table->enum('normal_balance', ['DEBIT', 'CREDIT']);
            
            $table->boolean('is_locked')->default(false); // Akun bawaan sistem tidak bisa dihapus
            $table->timestamps();
        });

        // 2. TABEL JOURNAL ENTRIES (Header Transaksi Jurnal)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('reference_no')->unique(); // No Bukti (JU-202310-001)
            $table->string('description')->nullable();
            
            // Relasi (Opsional)
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });

        // 3. TABEL JOURNAL ITEMS (Detail Debit & Kredit)
        Schema::create('journal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            
            $table->decimal('debit', 20, 2)->default(0);
            $table->decimal('credit', 20, 2)->default(0);
            
            $table->timestamps();
        });

        // 4. TABEL FIXED ASSETS (Aset Tetap)
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Cth: Motor Honda Beat
            $table->string('serial_number')->nullable();
            $table->date('purchase_date');
            
            $table->decimal('purchase_cost', 20, 2);    // Harga Beli
            $table->decimal('residual_value', 20, 2)->default(0); // Nilai Sisa (Residu)
            $table->integer('useful_life_months');      // Umur Ekonomis (Bulan)
            
            // Nilai Buku Terkini (Dihitung otomatis nanti)
            $table->decimal('accumulated_depreciation', 20, 2)->default(0);
            $table->decimal('book_value', 20, 2);
            
            $table->enum('status', ['ACTIVE', 'SOLD', 'DISPOSED'])->default('ACTIVE');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->timestamps();
        });

        // 5. TABEL DEPRECIATION HISTORY (Riwayat Penyusutan)
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->onDelete('cascade');
            $table->date('date'); // Tanggal Penyusutan (Biasanya akhir bulan)
            $table->decimal('amount', 20, 2); // Nilai Penyusutan Bulan Ini
            
            // Link ke Jurnal (Agar otomatis masuk Laporan Keuangan)
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            
            $table->timestamps();
        });

        // === SEEDER OTOMATIS (AGAR ANDA TIDAK INPUT MANUAL DARI NOL) ===
        // Kita isi Daftar Akun Standar (COA) Money Changer
        $accounts = [
            // A. ASET (1-xxxx)
            ['code' => '1-1001', 'name' => 'Kas Besar (Vault)', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1-1002', 'name' => 'Kas Kasir', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1-1003', 'name' => 'Bank BCA', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1-1004', 'name' => 'Bank Mandiri', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1-1010', 'name' => 'Persediaan Valuta Asing', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1-1020', 'name' => 'Sewa Dibayar Dimuka', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1-1030', 'name' => 'Piutang Usaha', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1-2001', 'name' => 'Inventaris Kantor', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1-2002', 'name' => 'Kendaraan Operasional', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1-2099', 'name' => 'Akumulasi Penyusutan Aset', 'type' => 'ASSET', 'normal_balance' => 'CREDIT'], // Kontra Aset

            // B. KEWAJIBAN (2-xxxx)
            ['code' => '2-1001', 'name' => 'Hutang Usaha', 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT'],
            ['code' => '2-1002', 'name' => 'Hutang Gaji', 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT'],
            ['code' => '2-1003', 'name' => 'Hutang Pajak', 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT'],

            // C. EKUITAS (3-xxxx)
            ['code' => '3-1001', 'name' => 'Modal Disetor', 'type' => 'EQUITY', 'normal_balance' => 'CREDIT'],
            ['code' => '3-1002', 'name' => 'Laba Ditahan', 'type' => 'EQUITY', 'normal_balance' => 'CREDIT'],
            ['code' => '3-1003', 'name' => 'Prive Pemilik', 'type' => 'EQUITY', 'normal_balance' => 'DEBIT'], // Kontra Ekuitas
            ['code' => '3-9999', 'name' => 'Ikhtisar Laba Rugi', 'type' => 'EQUITY', 'normal_balance' => 'CREDIT'],

            // D. PENDAPATAN (4-xxxx)
            ['code' => '4-1001', 'name' => 'Pendapatan Jual Valas', 'type' => 'REVENUE', 'normal_balance' => 'CREDIT'],
            ['code' => '4-2001', 'name' => 'Pendapatan Jasa Lain', 'type' => 'REVENUE', 'normal_balance' => 'CREDIT'],
            ['code' => '4-3001', 'name' => 'Pendapatan Bunga Bank', 'type' => 'REVENUE', 'normal_balance' => 'CREDIT'],

            // E. BEBAN (5-xxxx) -> HPP
            ['code' => '5-1000', 'name' => 'HPP - Valuta Asing', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            
            // F. BEBAN OPERASIONAL (6-xxxx)
            ['code' => '6-1001', 'name' => 'Beban Gaji & Tunjangan', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6-1002', 'name' => 'Beban Listrik, Air, Internet', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6-1003', 'name' => 'Beban Sewa Kantor', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6-1004', 'name' => 'Beban ATK & Fotocopy', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6-1005', 'name' => 'Beban Pemeliharaan Aset', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6-1006', 'name' => 'Beban Transportasi & BBM', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6-1007', 'name' => 'Beban Penyusutan Aset', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6-1008', 'name' => 'Beban Pajak & Perizinan', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6-1009', 'name' => 'Beban Entertainment', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6-1010', 'name' => 'Beban Sembahyang', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6-1099', 'name' => 'Beban Lain-lain', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
        ];

        DB::table('chart_of_accounts')->insert($accounts);
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('journal_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
    }
};