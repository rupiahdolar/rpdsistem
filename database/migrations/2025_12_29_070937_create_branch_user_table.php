<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            // Kuncian ke User
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Kuncian ke Branch (Kantor)
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            
            // Mencegah duplikat (Satu orang tidak bisa didaftarkan 2x di kantor yang sama)
            $table->unique(['user_id', 'branch_id']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_user');
    }
};
