<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman formulir Edit Profil / Ubah Password
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Proses Simpan Perubahan Profil
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // 1. Validasi Input
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Email boleh sama dengan punya sendiri, tapi gak boleh sama dengan orang lain
            'email' => ['nullable', 'email', 'unique:users,email,'.$user->id], 
        ]);

        // 2. Update Data Dasar
        $user->name = strtoupper($request->name);
        $user->email = $request->email;

        // 3. Update Password (Hanya jika diisi)
        if ($request->filled('password')) {
            $request->validate([
                'current_password' => ['required', 'current_password'], // Wajib isi password lama
                'password' => ['required', 'confirmed', 'min:8'],       // Password baru min 8 karakter
            ], [
                'current_password.current_password' => 'Password lama Anda salah.',
                'password.min' => 'Password baru minimal 8 karakter.',
                'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            ]);

            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('settings.index')
                         ->with('success', 'Profil & Password berhasil diperbarui!');
    }

    /**
     * Hapus Akun (Opsional, jarang dipakai di sistem kantor)
     */
    public function destroy(Request $request)
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}