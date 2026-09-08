@extends('layouts.app')

@section('content')
<div x-data="{ 
    deleteModalOpen: false, 
    deleteItemUrl: '',
    deleteNotaUrl: '',
    deleteNota: '' 
}" class="flex flex-col min-h-screen pb-20">

    {{-- HEADER & FILTER SECTION --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 border-b border-gray-100 pb-4">
            <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Data Transaksi Nasabah
            </h2>

            <div class="flex gap-2">
                <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-green-700 transition flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Excel
                </a>
                <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" target="_blank" class="bg-red-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-red-700 transition flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    PDF
                </a>
            </div>
        </div>

        {{-- FILTER FORM --}}
        <form action="{{ route('nasabah.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <input type="hidden" name="filter_submit" value="1">
            
            <div class="md:col-span-2">
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Cari Nama / ID / Nota</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Ketik..." class="w-full border-gray-300 rounded-lg text-xs font-bold focus:ring-primary focus:border-primary h-10 pl-9">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <div class="md:col-span-2 grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full border-gray-300 rounded-lg text-xs font-bold focus:ring-primary focus:border-primary h-10 cursor-pointer">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full border-gray-300 rounded-lg text-xs font-bold focus:ring-primary focus:border-primary h-10 cursor-pointer">
                </div>
            </div>

            <div class="md:col-span-1">
                @if(in_array(auth()->user()->role, ['admin', 'owner']))
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Cabang</label>
                    <select name="branch_id" class="w-full border-gray-300 rounded-lg text-xs font-bold focus:ring-primary focus:border-primary h-10 cursor-pointer">
                        <option value="">-- SEMUA --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                @else
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Urutkan</label>
                    <select name="sort" class="w-full border-gray-300 rounded-lg text-xs font-bold focus:ring-primary focus:border-primary h-10 cursor-pointer">
                        <option value="latest" {{ $sort == 'latest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ $sort == 'oldest' ? 'selected' : '' }}>Terlama</option>
                    </select>
                @endif
            </div>

            <div class="md:col-span-1 flex items-end">
                <button type="submit" class="w-full bg-primary hover:opacity-90 text-white font-bold h-10 rounded-lg shadow transition text-xs uppercase tracking-wider flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Filter
                </button>
            </div>
        </form>
    </div>

    {{-- ALERT --}}
    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm flex justify-between items-center">
        <span class="font-bold text-sm">{{ session('success') }}</span>
        <button onclick="this.parentElement.remove()" class="text-green-900 font-bold hover:text-green-700">✕</button>
    </div>
    @endif

    {{-- TABLE DATA --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left whitespace-nowrap">
                <thead class="bg-primary text-white border-b-2 border-primary uppercase text-[10px] tracking-wider font-bold">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">No. Nota</th>
                        <th class="px-4 py-3">Nasabah & PIC</th>
                        <th class="px-4 py-3">Tipe ID</th>
                        <th class="px-4 py-3">No ID</th>
                        <th class="px-4 py-3 text-center">L/P</th>
                        <th class="px-4 py-3">Tgl Lahir/Pendirian</th>
                        <th class="px-4 py-3">Alamat</th>
                        <th class="px-4 py-3">Pekerjaan</th>
                        <th class="px-4 py-3">Negara</th>
                        <th class="px-4 py-3">Sumber Dana</th>
                        <th class="px-4 py-3">Tujuan</th>
                        <th class="px-4 py-3 text-center">Tipe</th>
                        <th class="px-4 py-3 text-center">Valas</th>
                        <th class="px-4 py-3 text-right">Jumlah</th>
                        <th class="px-4 py-3 text-right">Rate</th>
                        <th class="px-4 py-3 text-right">Total (IDR)</th>
                        <th class="px-4 py-3 text-center bg-yellow-500 text-white sticky right-0 z-20 shadow-lg">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-600 text-xs">
                    @forelse($transactions as $trx)
                    <tr class="hover:bg-blue-50 transition duration-150 group">
                        {{-- 1. Tanggal --}}
                        <td class="px-4 py-3 font-bold text-gray-700">
                            {{ $trx->created_at->format('d/m/Y') }}
                            <div class="text-[9px] text-gray-400">{{ $trx->created_at->format('H:i') }}</div>
                        </td>

                        {{-- 2. No Nota --}}
                        <td class="px-4 py-3 font-mono text-primary font-bold">
                            {{ $trx->no_nota }}
                        </td>

                        {{-- 3. Nama Nasabah & Tipe --}}
                        <td class="px-4 py-3">
                            <div class="flex flex-col">
                                @if($trx->customer_type == 'CORPORATE')
                                    <span class="bg-purple-100 text-purple-700 border border-purple-200 px-1.5 py-0.5 rounded text-[9px] font-bold w-fit mb-1">
                                        KORPORASI
                                    </span>
                                @else
                                    <span class="bg-gray-100 text-gray-600 border border-gray-200 px-1.5 py-0.5 rounded text-[9px] font-bold w-fit mb-1">
                                        INDIVIDU
                                    </span>
                                @endif
                                <span class="font-bold text-gray-800 uppercase">{{ $trx->customer_name }}</span>
                                @if($trx->customer_type == 'CORPORATE' && $trx->representative_name)
                                    <span class="text-[10px] text-gray-500 italic mt-0.5">
                                        PIC: {{ $trx->representative_name }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- 4-12. Kolom Lainnya (Standard) --}}
                        <td class="px-4 py-3 text-center">{{ $trx->customer_id_type }}</td>
                        <td class="px-4 py-3 font-mono text-gray-600">{{ $trx->customer_identity_no }}</td>
                        <td class="px-4 py-3 text-center font-bold">{{ $trx->customer_gender ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $trx->customer_dob ? date('d/m/Y', strtotime($trx->customer_dob)) : '-' }}</td>
                        <td class="px-4 py-3" title="{{ $trx->customer_address }}">{{ Str::limit($trx->customer_address, 25) }}</td>
                        <td class="px-4 py-3 uppercase">{{ $trx->customer_job }}</td>
                        <td class="px-4 py-3 uppercase">{{ $trx->customer_country }}</td>
                        <td class="px-4 py-3">{{ $trx->source_of_funds }}</td>
                        <td class="px-4 py-3">{{ $trx->transaction_purpose }}</td>

                        {{-- 13. Tipe (Jual/Beli) --}}
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded font-bold text-[9px] {{ $trx->type == 'buy' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $trx->type == 'buy' ? 'BELI' : 'JUAL' }}
                            </span>
                        </td>

                        {{-- 14-17. Angka --}}
                        <td class="px-4 py-3 text-center font-bold text-gray-800">{{ $trx->currency }}</td>
                        <td class="px-4 py-3 text-right font-mono text-gray-700">{{ number_format($trx->amount_foreign, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-gray-500">{{ number_format($trx->rate) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-primary font-mono bg-blue-50/30">Rp {{ number_format($trx->total_idr) }}</td>

                        {{-- 18. AKSI --}}
                        <td class="px-4 py-3 text-center border-l border-gray-100 sticky right-0 z-10 bg-white group-hover:bg-blue-50 shadow-[-4px_0_8px_-4px_rgba(0,0,0,0.1)]">
                            <div class="flex items-center justify-center gap-2">
                                
                                {{-- Tombol Print --}}
                                <a href="{{ route('nasabah.print', $trx->id) }}" target="_blank" class="text-blue-500 hover:text-blue-700 bg-blue-50 p-1.5 rounded transition border border-blue-200" title="Print Struk">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                </a>

                                @if(auth()->user()->role === 'owner')
                                    
                                    {{-- Tombol Edit --}}
                                    <a href="{{ route('nasabah.edit', $trx->id) }}" class="text-yellow-500 hover:text-yellow-700 bg-yellow-50 p-1.5 rounded transition border border-yellow-200" title="Edit Seluruh Nota">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </a>

                                    {{-- Tombol Hapus (Trigger Modal) --}}
                                    <button @click="deleteModalOpen = true; 
                                                    deleteItemUrl = '{{ route('nasabah.destroy', $trx->id) }}'; 
                                                    deleteNotaUrl = '{{ route('nasabah.destroy_nota', $trx->id) }}'; 
                                                    deleteNota = '{{ $trx->no_nota }}'" 
                                            class="text-red-500 hover:text-red-700 bg-red-50 p-1.5 rounded transition border border-red-200" 
                                            title="Hapus Data">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                
                                @endif

                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="18" class="p-8 text-center text-gray-400 bg-gray-50 border-t border-gray-100">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span class="font-medium">Tidak ada data transaksi yang ditemukan.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="bg-gray-50 p-4 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
    </div>

    {{-- MODAL HAPUS (DUAL MODE) --}}
    <div x-show="deleteModalOpen" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 backdrop-blur-sm" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full text-center" @click.away="deleteModalOpen = false">
            <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Pilih Metode Hapus</h3>
            
            <div class="bg-gray-100 rounded p-2 mb-4">
                <p class="text-[10px] text-gray-500 uppercase">Target Nota:</p>
                <p class="font-mono font-bold text-gray-800" x-text="deleteNota"></p>
            </div>

            <p class="text-xs text-gray-500 mb-6 leading-relaxed">
                Silakan pilih apakah ingin menghapus <b>hanya baris item ini saja</b> atau <b>seluruh transaksi dalam nota ini</b>.
            </p>
            
            <div class="flex flex-col gap-3">
                
                {{-- Opsi 1: Hapus Item Saja --}}
                <form :action="deleteItemUrl" method="POST" class="w-full">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full py-2.5 bg-white border border-red-200 text-red-600 rounded-lg text-xs font-bold hover:bg-red-50 transition">
                        HANYA ITEM INI SAJA
                    </button>
                </form>

                {{-- Opsi 2: Hapus Satu Nota Full --}}
                <form :action="deleteNotaUrl" method="POST" class="w-full">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full py-2.5 bg-red-600 text-white rounded-lg text-xs font-bold hover:bg-red-700 shadow-md transition">
                        HAPUS SATU NOTA (SEMUA)
                    </button>
                </form>

                <button @click="deleteModalOpen = false" type="button" class="mt-2 text-xs font-bold text-gray-400 hover:text-gray-600 underline">
                    Batal, jangan hapus apapun
                </button>
            </div>
        </div>
    </div>

</div>
@endsection