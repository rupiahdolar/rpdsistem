<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username', // Pastikan ini ada
        'email',
        'password',
        'role',     // Pastikan ini ada
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // === INI FUNGSI YANG HILANG DAN BIKIN ERROR ===
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user');
    }
    
    // Opsional: Relasi ke Shift (Agar admin bisa cek history shift user)
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }
}