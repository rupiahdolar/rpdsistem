@extends('layouts.app')

@section('content')
{{-- IMPORT FONT UNTUK CETAK --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Text:ital@0;1&family=Spectral:wght@400;500;700&display=swap" rel="stylesheet">

<style>
    /* CSS KHUSUS CETAK (PRINT) */
    @media print {
        @page { 
            size: A4 landscape; 
            margin: 10mm; 
        }
        
        html, body { 
            height: auto !important; 
            overflow: visible !important; 
            background: white !important; 
            color: black !important; 
            font-family: "Times New Roman", Times, serif !important;
        }

        /* Sembunyikan Elemen UI Website */
        nav, aside, header, footer, .no-print, form, .btn-action, .screen-only { 
            display: none !important; 
        }

        /* Reset Layout Grid agar tercetak 2 Kolom Seimbang */
        .print-grid { 
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 2rem;
            width: 100%;
            align-items: start;
        }

        .print-divider { 
            border-right: 1px solid #000 !important; 
            padding-right: 20px; 
        }

        /* Table Styling untuk Print */
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border-bottom: 1px dotted #ccc; padding: 4px 0; }
        .total-row { border-top: 2px solid #000 !important; border-bottom: 2px solid #000 !important; font-weight: bold; }
        .header-section { border-bottom: 2px solid #000; margin-bottom: 20px; }
    }
</style>

<div class="max-w-7xl mx-auto pb-20">

    {{-- 1. HEADER HALAMAN & FILTER (TAMPILAN WEB) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 no-print">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            
            {{-- JUDUL --}}
            <div>
                <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2 border-l-4 border-primary pl-3">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                    Laporan Posisi Keuangan (Neraca)
                </h2>
                <p class="text-xs text-gray-500 mt-1 pl-3.5 font-bold uppercase tracking-wide">Snapshot Aset, Kewajiban, dan Modal Perusahaan.</p>
            </div>

            {{-- FILTER TAHUN --}}
            <form action="{{ route('keuangan.neraca') }}" method="GET" class="flex items-center gap-2 bg-gray-50 p-2 rounded-lg border border-gray-200 shadow-sm">
                <select name="year" class="bg-transparent border-none text-sm font-bold text-gray-700 focus:ring-0 cursor-pointer py-1" onchange="this.form.submit()">
                    @foreach(range(date('Y')-3, date('Y')+1) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                    @endforeach
                </select>
                <div class="w-px h-6 bg-gray-300 mx-1"></div>
                <button type="button" onclick="window.print()" class="bg-primary hover:opacity-90 text-white px-3 py-1.5 rounded-md shadow transition flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider transform active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    CETAK
                </button>
            </form>
        </div>
    </div>

    {{-- 2. KERTAS KERJA LAPORAN --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 min-h-[800px] p-8 relative">
        
        {{-- KOP LAPORAN --}}
        <div class="text-center mb-10 pb-6 border-b-2 border-gray-800 header-section">
            <h1 class="text-2xl font-bold uppercase tracking-widest text-gray-900">PT. Rupiah Dollar Valuta</h1>
            <h2 class="text-lg font-bold text-gray-600 uppercase mt-1 tracking-wide">Laporan Posisi Keuangan (Neraca)</h2>
            <p class="text-sm font-bold text-gray-500 mt-2 bg-gray-100 inline-block px-4 py-1 rounded-full uppercase tracking-wider no-print">PER 31 DESEMBER {{ $year }}</p>
            <p class="text-sm font-bold text-gray-500 mt-2 uppercase tracking-wider print:block hidden">PER 31 DESEMBER {{ $year }}</p>
        </div>

        {{-- GRID 2 KOLOM (AKTIVA & PASIVA) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 print-grid">
            
            {{-- A. AKTIVA (ASSETS) --}}
            <div class="print-divider md:border-r md:border-gray-200 md:pr-10">
                <div class="bg-primary text-white px-4 py-2 text-xs font-bold uppercase mb-6 rounded shadow-sm print:bg-gray-200 print:text-black print:shadow-none">
                    I. AKTIVA (ASSETS)
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left py-3 border-b-2 border-gray-100 font-bold text-gray-500 uppercase text-xs tracking-wider">Akun</th>
                            <th class="text-right py-3 border-b-2 border-gray-100 font-bold text-gray-500 uppercase text-xs tracking-wider">Saldo (IDR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        {{-- LOOP DATA ASET --}}
                        @foreach($assets as $acc)
                            @if($acc->balance != 0)
                            <tr class="hover:bg-blue-50/10 transition">
                                <td class="py-2.5 pl-2">
                                    <div class="font-bold text-gray-700">{{ $acc->name }}</div>
                                    <div class="text-[10px] text-gray-400 font-mono font-bold">{{ $acc->code }}</div>
                                </td>
                                <td class="py-2.5 text-right font-mono font-bold text-gray-600">
                                    {{ number_format($acc->balance) }}
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        {{-- TOTAL ASET --}}
                        <tr class="bg-blue-50/50 total-row">
                            <td class="py-4 font-bold text-primary uppercase pl-4 tracking-wide text-xs">TOTAL AKTIVA</td>
                            <td class="py-4 text-right font-bold font-mono text-primary text-lg pr-2">
                                Rp {{ number_format($totalAssets) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- B. PASIVA (LIABILITY + EQUITY) --}}
            <div class="md:pl-4">
                <div class="bg-gray-700 text-white px-4 py-2 text-xs font-bold uppercase mb-6 rounded shadow-sm print:bg-gray-200 print:text-black print:shadow-none">
                    II. PASIVA (LIABILITIES & EQUITY)
                </div>

                {{-- 1. KEWAJIBAN --}}
                <div class="mb-8">
                    <h4 class="font-bold text-gray-700 text-xs uppercase border-b border-gray-200 pb-2 mb-3 tracking-wide">A. Kewajiban (Hutang)</h4>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-50">
                            @foreach($liabilities as $acc)
                                @if($acc->balance != 0)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-2 pl-2">
                                        <div class="font-bold text-gray-700">{{ $acc->name }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono font-bold">{{ $acc->code }}</div>
                                    </td>
                                    <td class="py-2 text-right font-mono font-bold text-gray-600">
                                        {{ number_format($acc->balance) }}
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                            <tr class="bg-red-50/50">
                                <td class="py-2.5 pl-4 text-xs font-bold text-red-700 uppercase italic">Total Kewajiban</td>
                                <td class="py-2.5 text-right font-bold font-mono text-red-700 text-sm pr-2">
                                    {{ number_format($totalLiabilities) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- 2. EKUITAS --}}
                <div>
                    <h4 class="font-bold text-gray-700 text-xs uppercase border-b border-gray-200 pb-2 mb-3 tracking-wide">B. Ekuitas (Modal)</h4>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-50">
                            @foreach($equities as $acc)
                                @if($acc->balance != 0)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-2 pl-2">
                                        <div class="font-bold text-gray-700">{{ $acc->name }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono font-bold">{{ $acc->code }}</div>
                                    </td>
                                    <td class="py-2 text-right font-mono font-bold text-gray-600">
                                        {{ number_format($acc->balance) }}
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                            
                            {{-- LABA DITAHAN --}}
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-2 pl-2">
                                    <div class="font-bold text-gray-700">Laba Ditahan / Tahun Berjalan</div>
                                    <div class="text-[10px] text-gray-400 font-mono font-bold">3-9999</div>
                                </td>
                                <td class="py-2 text-right font-mono font-bold text-gray-600">
                                    {{ number_format($currentEarnings) }}
                                </td>
                            </tr>

                            <tr class="bg-green-50/50">
                                <td class="py-2.5 pl-4 text-xs font-bold text-green-700 uppercase italic">Total Ekuitas</td>
                                <td class="py-2.5 text-right font-bold font-mono text-green-700 text-sm pr-2">
                                    {{ number_format($totalEquity + $currentEarnings) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- TOTAL PASIVA --}}
                <div class="mt-8 border-t-2 border-gray-800 pt-1 total-row">
                    <div class="flex justify-between items-center bg-gray-100 p-4 rounded print:bg-transparent print:p-2">
                        <span class="font-bold text-gray-900 uppercase tracking-wide text-xs">TOTAL PASIVA</span>
                        <span class="font-bold font-mono text-gray-900 text-lg">Rp {{ number_format($totalPasiva) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- TANDA TANGAN (HANYA MUNCUL SAAT PRINT) --}}
        <div class="hidden print:flex justify-between px-16 mt-20 text-center text-sm break-inside-avoid">
            <div>
                <p class="mb-16">Disiapkan Oleh,</p>
                <div class="border-t border-black px-8 pt-1 font-bold">Finance / Accounting</div>
            </div>
            <div>
                <p class="mb-1">Denpasar, {{ date('d F Y') }}</p>
                <p class="mb-16">Disetujui Oleh,</p>
                <div class="border-t border-black px-8 pt-1 font-bold">Direktur Utama</div>
            </div>
        </div>

    </div>
</div>
@endsection