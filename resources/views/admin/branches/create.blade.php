@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    
    <a href="{{ route('admin.branches.index') }}" class="text-gray-500 hover:text-primary mb-4 inline-flex items-center text-sm font-bold">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali
    </a>

    <div class="bg-white rounded-2xl shadow-lg border-t-4 border-secondary p-8">
        <h1 class="text-2xl font-bold text-primary mb-2">Tambah Kantor Cabang</h1>
        <p class="text-sm text-gray-500 mb-6 italic">ID Cabang akan dibuat secara otomatis oleh sistem setelah disimpan.</p>
        
        <form action="{{ route('cabang.store') }}" method="POST">
            @csrf
            
            {{-- 1. NAMA KANTOR --}}
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Kantor / Outlet <span class="text-red-500">*</span></label>
                <input type="text" name="name" class="w-full border-gray-300 rounded-lg p-3 focus:ring-secondary focus:border-secondary shadow-sm uppercase font-bold text-gray-800" placeholder="CONTOH: KANTOR PUSAT DENPASAR" required>
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- 2. ALAMAT --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Alamat Lengkap <span class="text-red-500">*</span></label>
                <textarea name="address" rows="3" class="w-full border-gray-300 rounded-lg p-3 focus:ring-secondary focus:border-secondary shadow-sm uppercase font-medium text-gray-700" placeholder="ALAMAT LENGKAP KANTOR..." required></textarea>
                @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:bg-blue-900 transition shadow-md flex justify-center items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                SIMPAN DATA CABANG
            </button>
        </form>
    </div>
</div>
@endsection