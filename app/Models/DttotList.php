<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DttotList extends Model
{
    use HasFactory;
    
    // INI YANG KURANG TADI (IZIN KOLOM)
    protected $fillable = [
        'name',
        'birth_info',
        'address',
        'nationality',
        'description',
        'source_doc'
    ];
}