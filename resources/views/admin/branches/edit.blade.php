@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    
    <a href="{{ route('admin.branches.index') }}" class="text-gray-500 hover:text-primary mb-4 inline-flex items-center text-sm font-bold">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali
    </a>

    <div class="bg-white rounded-2xl shadow-lg border-t-4 border-yellow-500 p-8">
        <h1 class="text-2xl font-bold text-primary mb-6">Edit Kantor Cabang</h1>
        
        <form action="{{ route('cabang.update', $branch->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            {{-- 1. ID CABANG (READ ONLY / HANYA DILIHAT) --}}
            <div class="mb-5">
                <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">ID Cabang (System)</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 font-bold">#</span>
                    </div>
                    <input type="text" value="{{ $branch->id }}" 
                           class="w-full bg-gray-100 text-gray-500 border-gray-200 rounded-lg pl-8 p-3 font-mono font-bold cursor-not-allowed" 
                           readonly>
                </div>
                <p class="text-[10px] text-gray-400 mt-1">*ID tidak dapat diubah karena merupakan kunci database.</p>
            </div>

            {{-- 2. NAMA KANTOR --}}
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Kantor / Outlet</label>
                <input type="text" name="name" value="{{ old('name', $branch->name) }}" class="w-full border-gray-300 rounded-lg p-3 focus:ring-secondary focus:border-secondary shadow-sm uppercase font-bold text-gray-800" required>
            </div>

            {{-- 3. ALAMAT --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Alamat Lengkap</label>
                <textarea name="address" rows="3" class="w-full border-gray-300 rounded-lg p-3 focus:ring-secondary focus:border-secondary shadow-sm uppercase font-medium text-gray-700" required>{{ old('address', $branch->address) }}</textarea>
            </div>

            <button type="submit" class="w-full bg-yellow-500 text-white font-bold py-3 rounded-lg hover:bg-yellow-600 transition shadow-md flex justify-center items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                UPDATE DATA CABANG
            </button>
        </form>
    </div>
</div>
@endsection