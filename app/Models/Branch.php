<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'address'
    ];

    // Relasi ke User (Kasir)
    public function users()
    {
        return $this->belongsToMany(User::class, 'branch_user');
    }
}