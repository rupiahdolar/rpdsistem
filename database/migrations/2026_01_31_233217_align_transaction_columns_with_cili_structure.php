<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Kita gunakan Raw SQL karena mengubah Column Type ke ENUM di Laravel sering bermasalah dengan Doctrine DBAL
        
        // 1. Ubah customer_type dari VARCHAR jadi ENUM
        // Pastikan dulu data lama yang 'INDIVIDUAL' (string) aman, mysql otomatis handle konversinya jika stringnya sama.
        DB::statement("ALTER TABLE transactions MODIFY COLUMN customer_type ENUM('INDIVIDUAL','CORPORATE') DEFAULT 'INDIVIDUAL' COMMENT 'Tipe Nasabah'");

        // 2. Ubah customer_gender dari VARCHAR jadi ENUM
        DB::statement("ALTER TABLE transactions MODIFY COLUMN customer_gender ENUM('L','P') DEFAULT NULL COMMENT 'L=Laki-laki, P=Perempuan'");

        // 3. Ubah representative_id_type jadi VARCHAR(50) sesuai CILI
        DB::statement("ALTER TABLE transactions MODIFY COLUMN representative_id_type VARCHAR(50) DEFAULT NULL COMMENT 'Tipe ID Pengurus'");

        // 4. Update komentar kolom lainnya agar sama persis (Opsional, tapi biar rapi)
        DB::statement("ALTER TABLE transactions MODIFY COLUMN customer_dob DATE DEFAULT NULL COMMENT 'Tanggal Lahir'");
        DB::statement("ALTER TABLE transactions MODIFY COLUMN representative_name VARCHAR(255) DEFAULT NULL COMMENT 'Nama Pengurus/Kuasa'");
        DB::statement("ALTER TABLE transactions MODIFY COLUMN representative_id_no VARCHAR(255) DEFAULT NULL COMMENT 'No ID Pengurus'");
    }

    public function down()
    {
        // Kembalikan ke VARCHAR jika di-rollback
        DB::statement("ALTER TABLE transactions MODIFY COLUMN customer_type VARCHAR(255) DEFAULT 'INDIVIDUAL'");
        DB::statement("ALTER TABLE transactions MODIFY COLUMN customer_gender VARCHAR(255) DEFAULT NULL");
        DB::statement("ALTER TABLE transactions MODIFY COLUMN representative_id_type VARCHAR(255) DEFAULT NULL");
    }
};