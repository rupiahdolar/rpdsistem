<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->date('date');
            $table->foreignId('liability_account_id')->constrained('chart_of_accounts'); // Akun Utang (Kredit)
            $table->foreignId('cash_account_id')->constrained('chart_of_accounts');    // Akun Kas/Bank (Debit)
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_receipts');
    }
};