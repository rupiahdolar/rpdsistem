@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto">
    
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-primary">Set Modal Awal & Stok Valas</h2>
        <a href="{{ route('admin.capital.index') }}" class="text-gray-500 hover:text-gray-700 font-medium">
            &larr; Kembali
        </a>
    </div>

    <form action="{{ route('admin.capital.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></span>
                Modal Kas (Rupiah)
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Cabang</label>
                    <select name="branch_id" class="w-full border-gray-300 rounded-lg focus:ring-secondary focus:border-secondary" required>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Shift</label>
                    <select name="shift" class="w-full border-gray-300 rounded-lg focus:ring-secondary focus:border-secondary" required>
                        <option value="pagi">Shift Pagi</option>
                        <option value="malam">Shift Malam</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Modal Awal (IDR)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500 font-bold">Rp</span>
                        <input type="number" name="amount_idr" class="w-full pl-10 border-gray-300 rounded-lg focus:ring-secondary focus:border-secondary font-mono font-bold text-lg" placeholder="0" required>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">*Saldo kas untuk pembelian valas</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span class="bg-green-100 text-green-600 p-1.5 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></span>
                Stok Awal Valas (Fisik)
            </h3>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4 text-sm text-yellow-800">
                ⚠️ Isi jumlah nominal valas yang ada di brankas (Contoh: USD 500). Kosongkan jika tidak ada stok.
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach($currencies as $curr)
                <div class="relative group">
                    <label class="block text-xs font-bold text-gray-500 mb-1">{{ $curr->code }} - {{ $curr->name }}</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-400 font-bold text-xs">{{ $curr->code }}</span>
                        </div>
                        <input type="number" step="0.01" name="stock[{{ $curr->code }}]" 
                            class="w-full pl-12 pr-3 py-2 border border-gray-200 rounded-md focus:ring-primary focus:border-primary text-sm font-mono font-bold text-gray-800 group-hover:border-gray-400 transition" 
                            placeholder="0">
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <a href="{{ route('admin.capital.index') }}" class="px-6 py-3 rounded-lg bg-gray-100 text-gray-600 font-bold hover:bg-gray-200 transition">Batal</a>
            <button type="submit" class="px-6 py-3 rounded-lg bg-gradient-to-r from-primary to-blue-900 text-white font-bold shadow-lg hover:shadow-xl hover:scale-105 transition transform">
                Simpan Modal & Stok
            </button>
        </div>

    </form>
</div>
@endsection