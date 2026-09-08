<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Data Tambahan Customer
            if (!Schema::hasColumn('transactions', 'customer_gender')) {
                $table->string('customer_gender')->nullable()->after('customer_type');
            }
            if (!Schema::hasColumn('transactions', 'customer_dob')) {
                $table->date('customer_dob')->nullable()->after('customer_gender');
            }

            // Data Tambahan Representative (Wakil)
            // representative_name sudah kita buat tadi, jadi kita lanjut ke ID-nya
            if (!Schema::hasColumn('transactions', 'representative_id_type')) {
                $table->string('representative_id_type')->nullable()->after('representative_name');
            }
            if (!Schema::hasColumn('transactions', 'representative_id_no')) {
                $table->string('representative_id_no')->nullable()->after('representative_id_type');
            }
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'customer_gender', 
                'customer_dob', 
                'representative_id_type', 
                'representative_id_no'
            ]);
        });
    }
};
