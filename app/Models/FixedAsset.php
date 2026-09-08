<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relasi ke History Penyusutan
    public function depreciations()
    {
        return $this->hasMany(AssetDepreciation::class);
    }

    // Hitung Nilai Buku Saat Ini (Harga Beli - Sudah Susut Berapa)
    public function getCurrentBookValueAttribute()
    {
        return $this->purchase_cost - $this->accumulated_depreciation;
    }
}