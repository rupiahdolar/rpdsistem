@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto pb-20">
    
    {{-- 1. HEADER HALAMAN --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2 border-l-4 border-primary pl-3">
                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                Jurnal Umum
            </h1>
            <p class="text-xs text-gray-500 mt-1 pl-3.5 font-bold uppercase tracking-wide">History pencatatan & Buku Besar</p>
        </div>

        {{-- Tombol Buat Baru --}}
        <a href="{{ route('accounting.journals.create') }}" class="bg-primary hover:opacity-90 text-white px-5 py-2.5 rounded-lg shadow-lg transition flex items-center gap-2 text-xs font-bold uppercase tracking-wider transform active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            INPUT JURNAL MANUAL
        </a>
    </div>

    {{-- 2. AREA FILTER --}}
    <div class="bg-white p-5 shadow-sm border border-gray-200 mb-6 rounded-xl">
        <form action="{{ route('accounting.journals.index') }}" method="GET">
            <div class="flex flex-col md:flex-row items-end gap-4">
                
                {{-- Filter Cabang --}}
                @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
                <div class="w-full md:w-1/4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Kantor Cabang</label>
                    <select name="branch_id" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-primary focus:border-primary cursor-pointer">
                        <option value="">-- SEMUA CABANG --</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Filter User --}}
                @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
                <div class="w-full md:w-1/4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">User Pembuat</label>
                    <select name="user_id" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-primary focus:border-primary cursor-pointer">
                        <option value="">-- SEMUA USER --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>
                                {{ strtoupper($user->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Filter Tanggal --}}
                <div class="w-full md:w-auto">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Periode Tanggal</label>
                    <div class="flex items-center bg-gray-50 border border-gray-300 rounded-lg overflow-hidden">
                        <input type="date" name="start_date" value="{{ $startDate }}" class="bg-transparent text-sm p-2 outline-none text-gray-700 font-bold focus:bg-white w-full">
                        <span class="text-gray-400 px-2 font-bold">-</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="bg-transparent text-sm p-2 outline-none text-gray-700 font-bold focus:bg-white w-full">
                    </div>
                </div>
                
                {{-- Tombol Cari --}}
                <div class="w-full md:w-auto pb-[1px]">
                    <button type="submit" class="w-full bg-primary text-white px-6 py-2 rounded-lg font-bold hover:opacity-90 transition shadow flex items-center justify-center gap-2 h-[38px] text-xs uppercase tracking-wider">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        FILTER
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
    <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm flex justify-between items-center">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900 font-bold">&times;</button>
    </div>
    @endif

    {{-- 3. TABEL DATA (Clean White Style) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-sm text-left">
            {{-- HEADER BARU --}}
            <thead class="bg-white text-primary font-bold uppercase text-xs border-b-2 border-primary">
                <tr>
                    <th class="p-4 w-40 border-r border-gray-100">Tanggal & Ref</th>
                    <th class="p-4 border-r border-gray-100">Keterangan / Akun</th>
                    <th class="p-4 text-right w-32 border-r border-gray-100">Debit</th>
                    <th class="p-4 text-right w-32 border-r border-gray-100">Kredit</th>
                    @if(Auth::user()->role == 'owner')
                        <th class="p-4 text-center w-20">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($entries as $entry)
                    
                    {{-- BARIS HEADER TRANSAKSI --}}
                    <tr class="bg-gray-50/80">
                        <td class="p-3 border-t border-gray-200 align-top">
                            <div class="font-bold text-gray-800 text-xs">
                                {{ \Carbon\Carbon::parse($entry->date)->format('d/m/Y') }}
                            </div>
                            {{-- No Ref jadi Biru --}}
                            <div class="text-[10px] font-mono text-primary font-bold mt-1">
                                {{ $entry->reference_no }}
                            </div>
                        </td>
                        <td class="p-3 border-t border-gray-200 align-middle" colspan="3">
                            <div class="font-bold text-gray-800 text-xs uppercase tracking-wide">
                                {{ $entry->description }}
                            </div>
                            <div class="text-[10px] text-gray-400 mt-0.5 flex items-center gap-2">
                                <span class="uppercase font-bold tracking-wider text-[9px]">BY: {{ $entry->user->name ?? 'SYSTEM' }}</span>
                                <span class="text-gray-300">|</span>
                                <span class="uppercase font-bold tracking-wider text-[9px]">CABANG: {{ $entry->branch->name ?? '-' }}</span>
                            </div>
                        </td>
                        
                        {{-- HANYA OWNER --}}
                        @if(Auth::user()->role == 'owner')
                        <td class="p-3 border-t border-gray-200 text-center align-middle">
                            <form action="{{ route('accounting.journals.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('PERINGATAN: Menghapus jurnal ini akan mengubah angka di Neraca & Laba Rugi.\n\nYakin hapus?');">
                                @csrf @method('DELETE')
                                <button class="text-gray-400 hover:text-red-600 transition p-1.5 rounded hover:bg-red-50" title="Hapus Jurnal">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </td>
                        @endif
                    </tr>

                    {{-- BARIS RINCIAN ITEM --}}
                    @foreach($entry->items as $item)
                    <tr class="bg-white hover:bg-blue-50/10 group transition border-none">
                        <td class="border-none"></td> 
                        
                        {{-- Kolom Akun --}}
                        <td class="px-4 py-1.5 border-none">
                            <div class="flex items-center">
                                {{-- Indentasi Kredit --}}
                                @if($item->credit > 0) <div class="w-8 border-l border-gray-200 h-4 mr-2"></div> @endif
                                
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[10px] text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded font-bold border border-gray-200">
                                        {{ $item->chartOfAccount->code }}
                                    </span>
                                    <span class="text-xs text-gray-600 font-medium group-hover:text-black transition uppercase">
                                        {{ $item->chartOfAccount->name }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- Kolom Debit --}}
                        <td class="px-4 py-1.5 text-right border-none">
                            @if($item->debit > 0)
                                <span class="font-mono text-gray-800 font-bold text-xs">
                                    {{ number_format($item->debit) }}
                                </span>
                            @endif
                        </td>

                        {{-- Kolom Kredit --}}
                        <td class="px-4 py-1.5 text-right border-none">
                            @if($item->credit > 0)
                                <span class="font-mono text-gray-800 font-bold text-xs">
                                    {{ number_format($item->credit) }}
                                </span>
                            @endif
                        </td>

                        @if(Auth::user()->role == 'owner')
                            <td class="border-none"></td>
                        @endif
                    </tr>
                    @endforeach
                    
                    {{-- Spacer --}}
                    <tr><td colspan="{{ Auth::user()->role == 'owner' ? '5' : '4' }}" class="h-px bg-gray-100 p-0"></td></tr>

                @empty
                    <tr>
                        <td colspan="{{ Auth::user()->role == 'owner' ? '5' : '4' }}" class="p-10 text-center text-gray-400 italic bg-gray-50">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                <span class="text-sm font-medium">Tidak ada data jurnal pada periode ini.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        {{-- Pagination --}}
        <div class="p-4 bg-gray-50 border-t border-gray-200">
            {{ $entries->links() }}
        </div>
    </div>
</div>
@endsection