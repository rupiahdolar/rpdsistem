<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('internal_mutations', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            
            // Enum: bank_to_cash (Tarik Tunai) atau cash_to_bank (Setor Tunai)
            $table->enum('type', ['bank_to_cash', 'cash_to_bank']); 
            
            // Relasi ke Cabang (Boleh null jika Head Office)
            $table->unsignedBigInteger('branch_id')->nullable();
            
            // Relasi ke Akun Bank (Chart of Account)
            $table->unsignedBigInteger('bank_account_id');
            
            $table->decimal('amount', 20, 2)->default(0);
            $table->text('description')->nullable();
            
            // Relasi ke User yang input
            $table->unsignedBigInteger('user_id');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('internal_mutations');
    }
};