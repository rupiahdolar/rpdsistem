<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dttot_lists', function (Blueprint $table) {
            // TAMBAHKAN BARIS INI (Mengubah kolom 'name' menjadi TEXT)
            $table->text('name')->nullable()->change();

            if (!Schema::hasColumn('dttot_lists', 'densus_code')) {
                $table->string('densus_code')->nullable()->after('id');
            }
            if (!Schema::hasColumn('dttot_lists', 'entity_type')) {
                $table->string('entity_type')->nullable()->after('name');
            }
            if (!Schema::hasColumn('dttot_lists', 'birth_place')) {
                $table->string('birth_place')->nullable()->after('entity_type');
            }
            if (!Schema::hasColumn('dttot_lists', 'birth_date')) {
                $table->string('birth_date')->nullable()->after('birth_place');
            }
            if (!Schema::hasColumn('dttot_lists', 'nationality')) {
                $table->string('nationality')->nullable()->after('birth_date');
            }
            if (!Schema::hasColumn('dttot_lists', 'address')) {
                $table->text('address')->nullable()->after('nationality');
            }
            if (!Schema::hasColumn('dttot_lists', 'description')) {
                $table->longText('description')->nullable()->after('address');
            }
            if (!Schema::hasColumn('dttot_lists', 'source_doc')) {
                $table->string('source_doc')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dttot_lists', function (Blueprint $table) {
            $table->dropColumn([
                'densus_code',
                'entity_type',
                'birth_place',
                'birth_date',
                'nationality',
                'address',
                'description',
                'source_doc'
            ]);
        });
    }
};