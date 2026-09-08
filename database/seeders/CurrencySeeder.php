<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        // Daftar 22 Mata Uang Lengkap
        $currencies = [
            ['code' => 'USD', 'name' => 'Dollar Amerika'],
            ['code' => 'AUD', 'name' => 'Dollar Australia'],
            ['code' => 'EUR', 'name' => 'Euro'],
            ['code' => 'GBP', 'name' => 'Poundsterling Inggris'],
            ['code' => 'CHF', 'name' => 'Franc Swiss'],
            ['code' => 'JPY', 'name' => 'Yen Jepang'],
            ['code' => 'SGD', 'name' => 'Dollar Singapura'],
            ['code' => 'CAD', 'name' => 'Dollar Kanada'],
            ['code' => 'MYR', 'name' => 'Ringgit Malaysia'],
            ['code' => 'NZD', 'name' => 'Dollar Selandia Baru'],
            ['code' => 'HKD', 'name' => 'Dollar Hongkong'],
            ['code' => 'CNY', 'name' => 'Yuan China'],
            ['code' => 'BND', 'name' => 'Dollar Brunei'],
            ['code' => 'SAR', 'name' => 'Riyal Arab Saudi'],
            ['code' => 'AED', 'name' => 'Dirham Uni Emirat Arab'],
            ['code' => 'THB', 'name' => 'Baht Thailand'],
            ['code' => 'PHP', 'name' => 'Peso Filipina'],
            ['code' => 'SEK', 'name' => 'Krona Swedia'],
            ['code' => 'NOK', 'name' => 'Krone Norwegia'],
            ['code' => 'DKK', 'name' => 'Krone Denmark'],
            ['code' => 'KRW', 'name' => 'Won Korea Selatan'],
            ['code' => 'TWD', 'name' => 'Dollar Taiwan Baru'],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                ['code' => $currency['code']], 
                ['name' => $currency['name']]
            );
        }
    }
}