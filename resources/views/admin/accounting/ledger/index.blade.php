@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto pb-20">

    {{-- 1. HEADER HALAMAN & FILTER --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2 border-l-4 border-primary pl-3">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Buku Besar (General Ledger)
                </h2>
                <p class="text-xs text-gray-500 mt-1 pl-3.5 font-bold uppercase tracking-wide">Detail mutasi transaksi per akun.</p>
            </div>
        </div>

        {{-- FORM FILTER --}}
        <form action="{{ route('keuangan.buku_besar') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end pt-4 border-t border-gray-100">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Pilih Akun (COA)</label>
                <select name="account_id" class="w-full border-gray-300 rounded-lg p-2.5 text-sm font-bold focus:ring-primary focus:border-primary cursor-pointer text-gray-700" onchange="this.form.submit()">
                    <option value="">-- PILIH AKUN --</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ $accountId == $acc->id ? 'selected' : '' }}>
                            {{ $acc->code }} - {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-primary focus:border-primary cursor-pointer font-medium">
            </div>
            
            <div class="flex gap-2">
                <div class="w-full">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-primary focus:border-primary cursor-pointer font-medium">
                </div>
                {{-- Tombol Filter Primary --}}
                <button type="submit" class="bg-primary text-white p-2.5 rounded-lg mt-auto shadow-md hover:opacity-90 transition w-12 flex items-center justify-center transform active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </button>
            </div>
        </form>
    </div>

    @if($selectedAccount && $ledgerData)
    
        {{-- 2. KARTU RINGKASAN --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {{-- Saldo Awal --}}
            <div class="bg-white border border-gray-200 p-5 rounded-xl shadow-sm border-l-4 border-l-gray-400 flex flex-col justify-between">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Saldo Awal (Per {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }})</span>
                <div class="text-xl font-mono font-bold text-gray-700 mt-2">
                    Rp {{ number_format($ledgerData['opening_balance']) }}
                </div>
            </div>
            
            {{-- Mutasi (Tetap netral/informative) --}}
            <div class="bg-white border border-gray-200 p-5 rounded-xl shadow-sm border-l-4 border-l-blue-400 flex flex-col justify-between">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Mutasi Periode Ini</span>
                <div class="flex justify-between mt-2 text-sm font-mono items-center">
                    <div class="text-green-600 font-bold bg-green-50 px-2 py-1 rounded" title="Total Debit">
                        + {{ number_format($ledgerData['total_debit']) }}
                    </div>
                    <span class="text-gray-300">|</span>
                    <div class="text-red-600 font-bold bg-red-50 px-2 py-1 rounded" title="Total Kredit">
                        - {{ number_format($ledgerData['total_credit']) }}
                    </div>
                </div>
            </div>

            {{-- Saldo Akhir (Primary Color) --}}
            <div class="bg-primary text-white p-5 rounded-xl shadow-lg shadow-blue-200 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute right-0 top-0 w-24 h-24 bg-white/10 rounded-full -mr-6 -mt-6 blur-xl"></div>
                <span class="text-[10px] font-bold text-blue-100 uppercase tracking-widest relative z-10">Saldo Akhir</span>
                <div class="text-2xl font-mono font-bold mt-2 relative z-10">
                    Rp {{ number_format($ledgerData['ending_balance']) }}
                </div>
            </div>
        </div>

        {{-- 3. TABEL DATA (Clean White Style) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    {{-- Header Putih dengan Text Primary --}}
                    <thead class="bg-white text-primary font-bold uppercase text-xs border-b-2 border-primary">
                        <tr>
                            <th class="p-4 border-r border-gray-100 w-32">Tanggal</th>
                            <th class="p-4 border-r border-gray-100 w-40">No. Ref</th>
                            <th class="p-4 border-r border-gray-100">Keterangan</th>
                            <th class="p-4 border-r border-gray-100 text-right w-36">Debit</th>
                            <th class="p-4 border-r border-gray-100 text-right w-36">Kredit</th>
                            <th class="p-4 text-right w-44 bg-gray-50 text-gray-600">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {{-- Baris Saldo Awal --}}
                        <tr class="bg-gray-50/50 font-bold text-gray-500 italic text-xs">
                            <td class="p-4 border-r border-gray-100 text-center" colspan="3">--- SALDO AWAL ---</td>
                            <td class="p-4 text-right border-r border-gray-100">-</td>
                            <td class="p-4 text-right border-r border-gray-100">-</td>
                            <td class="p-4 text-right font-mono text-gray-700 bg-gray-100/50 border-l border-gray-200">
                                {{ number_format($ledgerData['opening_balance']) }}
                            </td>
                        </tr>

                        {{-- Loop Transaksi --}}
                        @forelse($ledgerData['transactions'] as $row)
                        <tr class="hover:bg-blue-50/20 transition group">
                            {{-- Tanggal --}}
                            <td class="p-4 font-mono text-gray-600 border-r border-gray-100 text-xs">
                                {{ \Carbon\Carbon::parse($row->journalEntry->date)->format('d/m/Y') }}
                            </td>
                            {{-- No Ref (Biru) --}}
                            <td class="p-4 font-mono text-xs text-primary font-bold border-r border-gray-100 group-hover:underline cursor-pointer">
                                {{ $row->journalEntry->reference_no ?? '-' }}
                            </td>
                            {{-- Keterangan --}}
                            <td class="p-4 border-r border-gray-100 text-gray-700 uppercase text-xs font-medium">
                                {{ $row->journalEntry->description }}
                            </td>
                            {{-- Debit (Hijau) --}}
                            <td class="p-4 text-right font-mono text-green-700 font-bold border-r border-gray-100 text-xs">
                                {{ $row->debit > 0 ? number_format($row->debit) : '-' }}
                            </td>
                            {{-- Kredit (Merah) --}}
                            <td class="p-4 text-right font-mono text-red-600 font-bold border-r border-gray-100 text-xs">
                                {{ $row->credit > 0 ? number_format($row->credit) : '-' }}
                            </td>
                            {{-- Saldo Berjalan --}}
                            <td class="p-4 text-right font-mono font-bold text-gray-800 bg-gray-50/50 border-l border-gray-200 text-xs">
                                {{ number_format($row->running_balance) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400 bg-gray-50 italic text-sm">
                                Tidak ada mutasi transaksi pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @else
        {{-- Tampilan Kosong (Belum Pilih Akun) --}}
        <div class="text-center py-24 bg-white rounded-xl shadow-sm border border-gray-200 mt-6 flex flex-col items-center justify-center">
            <div class="bg-blue-50 w-20 h-20 rounded-full flex items-center justify-center mb-4 text-primary animate-pulse">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800">Silakan Pilih Akun (COA)</h3>
            <p class="text-gray-400 text-xs max-w-xs mx-auto mt-2 uppercase tracking-wide">Pilih akun pada filter di atas untuk melihat rincian buku besar.</p>
        </div>
    @endif

</div>
@endsection