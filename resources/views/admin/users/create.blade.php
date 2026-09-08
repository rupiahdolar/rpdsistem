@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    
    <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-primary mb-4 inline-flex items-center text-sm font-bold">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali
    </a>

    {{-- KITA PAKAI x-data UNTUK LOGIKA HIDE/SHOW --}}
    <div class="bg-white rounded-2xl shadow-lg border-t-4 border-secondary p-8" x-data="{ role: '{{ old('role', 'cashier') }}' }">
        <h1 class="text-2xl font-bold text-primary mb-6">Tambah Pengguna Baru</h1>
        
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            
            {{-- PILIH ROLE (ADMIN / KASIR) --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Tipe Akun (Role)</label>
                <div class="grid grid-cols-2 gap-4">
                    {{-- Opsi Kasir --}}
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="cashier" x-model="role" class="peer sr-only">
                        <div class="rounded-lg border-2 border-gray-200 p-4 hover:bg-gray-50 peer-checked:border-secondary peer-checked:bg-yellow-50 transition text-center">
                            <div class="font-bold text-gray-700 peer-checked:text-yellow-800">STAFF KASIR</div>
                            <div class="text-xs text-gray-500 mt-1">Input Transaksi & Shift</div>
                        </div>
                    </label>

                    {{-- Opsi Admin --}}
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="admin" x-model="role" class="peer sr-only">
                        <div class="rounded-lg border-2 border-gray-200 p-4 hover:bg-gray-50 peer-checked:border-primary peer-checked:bg-blue-50 transition text-center">
                            <div class="font-bold text-gray-700 peer-checked:text-blue-900">ADMIN / DIREKSI</div>
                            <div class="text-xs text-gray-500 mt-1">Full Akses & Laporan</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- NAMA & USERNAME --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full border-gray-300 rounded-lg p-3 focus:ring-secondary uppercase shadow-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Username Login</label>
                    <input type="text" name="username" value="{{ old('username') }}" class="w-full border-gray-300 rounded-lg p-3 focus:ring-secondary shadow-sm" placeholder="cth: admin01" required>
                    @error('username') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- EMAIL --}}
            <div class="mb-4">
                {{-- 1. Hapus tulisan (Opsional) --}}
                <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                
                {{-- 2. Tambahkan attribute 'required' di input --}}
                <input type="email" name="email" value="{{ old('email') }}" class="w-full border-gray-300 rounded-lg p-3 shadow-sm" placeholder="contoh: kasir1@bmm.co.id" required>
                
                {{-- 3. Tambahkan pesan error validasi agar user tahu jika email sudah dipakai --}}
                @error('email') 
                    <p class="text-red-500 text-xs mt-1 font-bold">⚠ {{ $message }}</p> 
                @enderror
            </div>

            {{-- PASSWORD --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" class="w-full border-gray-300 rounded-lg p-3 shadow-sm" required>
                    @error('password') 
                        <p class="text-red-500 text-xs mt-1 font-bold animate-pulse">⚠ {{ $message }}</p> 
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" class="w-full border-gray-300 rounded-lg p-3 shadow-sm" required>
                </div>
            </div>

            {{-- 
                AREA INI HANYA MUNCUL JIKA ROLE == CASHIER 
                Kita gunakan x-show dan x-transition dari Alpine.js
            --}}
            <div x-show="role === 'cashier'" x-transition 
                 class="mb-8 bg-gray-50 p-4 rounded-lg border border-gray-200 relative">
                
                {{-- Panah kecil --}}
                <div class="absolute -top-2 left-10 w-4 h-4 bg-gray-50 border-t border-l border-gray-200 transform rotate-45"></div>

                <label class="block text-sm font-bold text-gray-800 mb-3">Tugaskan di Cabang Mana? (Wajib untuk Kasir)</label>
                
                @if($branches->isEmpty())
                    <div class="text-red-500 text-sm italic bg-red-50 p-3 rounded border border-red-200">
                        ⚠ Belum ada data cabang. Silakan input di menu "Kantor Cabang" terlebih dahulu!
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($branches as $branch)
                        <label class="flex items-center space-x-3 p-3 border rounded-lg bg-white hover:bg-blue-50 cursor-pointer transition">
                            <input type="checkbox" name="branches[]" value="{{ $branch->id }}" class="w-5 h-5 text-secondary rounded focus:ring-secondary">
                            <span class="text-gray-700 font-medium">{{ $branch->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('branches') <p class="text-red-500 text-xs mt-2 font-bold">⚠ {{ $message }}</p> @enderror
                @endif
            </div>

            {{-- PESAN KHUSUS ADMIN --}}
            <div x-show="role === 'admin'" x-transition class="mb-8 bg-blue-50 p-4 rounded-lg border border-blue-200 text-blue-800 text-sm">
                <strong>Info:</strong> Akun Admin/Direksi secara otomatis memiliki akses ke <u>semua data cabang</u> dan laporan. Tidak perlu memilih lokasi spesifik.
            </div>

            <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:bg-blue-900 transition shadow-md">
                SIMPAN PENGGUNA BARU
            </button>
        </form>
    </div>
</div>
@endsection