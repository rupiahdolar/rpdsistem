<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. MATA UANG (Panggil Seeder Terpisah)
        $this->call([CurrencySeeder::class]);

        // 2. SETTINGS (Draft Website / Ganti Kulit)
        if (Schema::hasTable('settings')) {
            $settings = [
                ['key' => 'app_name', 'value' => 'MONEY CHANGER PRO'],
                ['key' => 'app_logo', 'value' => 'logo.png'],
                ['key' => 'primary_color', 'value' => '#0A2647'],   // Navy Blue
                ['key' => 'secondary_color', 'value' => '#D4AF37'], // Gold
            ];
            foreach ($settings as $setting) {
                DB::table('settings')->updateOrInsert(
                    ['key' => $setting['key']],
                    ['value' => $setting['value'], 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // 3. KANTOR CABANG PUSAT
        $pusat = Branch::firstOrCreate(
            ['name' => 'KANTOR PUSAT'],
            ['address' => 'Jl. Kantor Pusat']
        );

        // 4. USERS (HANYA OWNER & ADMIN)
        
        // A. OWNER
        $owner = User::firstOrCreate(
            ['username' => 'owner'],
            [
                'name' => 'Big Boss Owner',
                'email' => 'owner@example.com',
                'role' => 'owner',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        // Hubungkan Owner ke Kantor Pusat
        if ($owner->branches()->where('branch_id', $pusat->id)->doesntExist()) {
            $owner->branches()->attach($pusat->id);
        }

        // B. ADMIN
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Manajer Operasional',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        // Hubungkan Admin ke Kantor Pusat
        if ($admin->branches()->where('branch_id', $pusat->id)->doesntExist()) {
            $admin->branches()->attach($pusat->id);
        }
    }
}