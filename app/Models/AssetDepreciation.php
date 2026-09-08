<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDepreciation extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Fungsi bawaan sebelumnya (dibiarkan agar aman jika dipakai di halaman lain)
    public function fixedAsset()
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    // ==========================================
    // FUNGSI BARU UNTUK HALAMAN LABA RUGI & ARUS KAS
    // ==========================================
    public function asset()
    {
        // Harus disebutkan 'fixed_asset_id' agar sistem tahu kunci penghubungnya
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}