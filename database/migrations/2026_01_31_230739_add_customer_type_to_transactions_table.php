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
            // Menambahkan kolom customer_type setelah customer_job
            // Kita set default 'INDIVIDUAL' agar data lama tidak error/null
            $table->string('customer_type')->default('INDIVIDUAL')->after('customer_job'); 
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('customer_type');
        });
    }
};
