@extends('layouts.app')

@section('content')
<div class="flex flex-col h-full bg-gray-50 pb-20">

    <style>
        /* --- CSS KHUSUS PRINT & EXPORT --- */
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
            body { 
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: white !important;
                font-family: sans-serif;
            }
            
            /* Sembunyikan elemen navigasi & web-only layout */
            aside, nav, .no-print, form, .btn-action, .screen-only { 
                display: none !important; 
            }
            
            /* Munculkan elemen khusus print */
            .print-only { display: block !important; }

            /* Reset Layout untuk Pagination */
            .flex-col, .overflow-hidden, .card-box, .main-container { 
                display: block !important;
                overflow: visible !important; 
                height: auto !important;
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Header Laporan Print */
            .print-header { 
                display: block !important; 
                text-align: center; 
                margin-bottom: 20px; 
                border-bottom: 2px solid #000; 
                padding-bottom: 10px; 
            }

            /* STANDARD TABLE STYLING (Agar Excel & PDF Rapi) */
            table { 
                width: 100% !important; 
                border-collapse: collapse !important; 
                font-size: 11px !important; 
                margin-bottom: 20px !important;
            }
            th, td { 
                border: 1px solid #000 !important; 
                padding: 6px !important; 
                color: #000 !important; 
                vertical-align: top;
            }
            thead { display: table-header-group; } 
            tr { page-break-inside: avoid; } 
            
            /* Paksa Hitam Putih saat Print */
            .text-primary { color: #000 !important; }
            .bg-primary { background-color: #eee !important; color: #000 !important; }
            .text-green-600, .text-red-600, .text-orange-600 { color: #000 !important; }
        }
        
        /* Helper Classes */
        .print-header { display: none; }
        .print-only { display: none; }
    </style>

    {{-- HEADER & FILTER SECTION (TAMPILAN WEB) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8 no-print">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            
            {{-- JUDUL HALAMAN --}}
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Laporan Mutasi Bulanan
                </h2>
                <p class="text-xs text-gray-500 mt-1 uppercase font-bold tracking-wide">
                    Periode: {{ date('F Y', mktime(0, 0, 0, $filterMonth, 1, $filterYear)) }}
                </p>
            </div>

            {{-- FORM FILTER --}}
            <form action="{{ route('mutasi.bulanan') }}" method="GET" class="flex flex-wrap items-center gap-3 justify-end">
                @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
                    <select name="branch_id" class="border-gray-300 rounded-lg text-xs font-bold text-gray-700 focus:ring-primary focus:border-primary bg-white cursor-pointer h-10" onchange="this.form.submit()">
                        <option value="">-- SEMUA CABANG --</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="month" class="border-gray-300 rounded-lg text-xs font-bold text-gray-700 focus:ring-primary focus:border-primary bg-white cursor-pointer h-10" onchange="this.form.submit()">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $filterMonth == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m, 1)) }}</option>
                    @endforeach
                </select>
                <select name="year" class="border-gray-300 rounded-lg text-xs font-bold text-gray-700 focus:ring-primary focus:border-primary bg-white cursor-pointer h-10" onchange="this.form.submit()">
                    @foreach(range(date('Y')-2, date('Y')+2) as $y)
                        <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                
                {{-- TOMBOL EXPORT --}}
                <div class="flex gap-2 ml-2">
                    <button type="button" onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg transition shadow flex items-center gap-1 h-10" title="Download Excel">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span class="text-xs font-bold">XLS</span>
                    </button>
                    <button type="button" onclick="window.print()" class="bg-primary hover:opacity-90 text-white px-3 py-2 rounded-lg transition shadow flex items-center gap-1 h-10" title="Cetak PDF">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        <span class="text-xs font-bold">PDF</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- LOGIKA PHP: HITUNG HPP & LABA RUGI (DIPERBAIKI) --}}
    @php
        // PERBAIKAN LOGIKA:
        // $brankas['in']  = PEMBELIAN (Uang Keluar Real)
        // $brankas['out'] = PENJUALAN (Uang Masuk Real)
        
        // HPP = Awal + Pembelian - Akhir
        $hpp = $valuation['start'] + $brankas['in'] - $valuation['end'];
        
        // Laba Kotor = Penjualan - HPP
        $labaKotor = $brankas['out'] - $hpp;
        
        $biayaOps = $brankas['expense'];
        $labaBersih = $labaKotor - $biayaOps;
    @endphp

    {{-- CONTAINER UTAMA PRINT/EXPORT --}}
    <div id="printableArea" class="px-6 pb-10 space-y-8">

        {{-- HEADER SURAT (HANYA MUNCUL DI PRINT) --}}
        <div class="print-header">
            <h1 class="text-2xl font-bold uppercase">PT. Rupiah Dollar Valuta</h1>
            <p class="text-sm">Authorized Money Changer</p>
            <hr class="my-2 border-black">
            <h2 class="text-xl font-bold mt-2">LAPORAN MUTASI BULANAN</h2>
            <p class="text-sm">
                Periode: {{ date('F Y', mktime(0, 0, 0, $filterMonth, 1, $filterYear)) }}
                @if(Auth::user()->role == 'admin' && request('branch_id')) | Cabang: {{ $branches->where('id', request('branch_id'))->first()->name ?? '-' }} @endif
            </p>
        </div>

        {{-- BAGIAN 1: RINGKASAN KEUANGAN (TABEL GABUNGAN SCREEN & PRINT) --}}
        
        {{-- A. TABEL ARUS KAS FISIK --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-box mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-white no-print">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide flex items-center gap-2">
                    <span class="w-2 h-6 bg-primary rounded-sm"></span>
                    I. Ringkasan Arus Kas Fisik (Tunai)
                </h3>
            </div>
            <div class="print-header mt-4 mb-2 px-1 hidden">
                <h3 class="font-bold text-sm uppercase">I. Ringkasan Arus Kas Fisik</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table id="summaryTable" class="w-full text-sm text-left border-collapse">
                    <thead class="bg-white text-primary border-b-2 border-primary uppercase text-xs font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 text-center">Modal Awal</th>
                            <th class="px-6 py-4 text-center text-green-700">Pembelian (Out)</th>
                            <th class="px-6 py-4 text-center text-red-700">Penjualan (In)</th>
                            <th class="px-6 py-4 text-center text-orange-600">Biaya Ops</th>
                            <th class="px-6 py-4 text-center bg-gray-50 text-gray-800">Sisa Fisik</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="text-center font-mono font-bold text-gray-800">
                            <td class="px-6 py-5 align-middle">Rp {{ number_format($brankas['start']) }}</td>
                            {{-- INI SUDAH DITUKAR DI CONTROLLER: IN=Pembelian, OUT=Penjualan --}}
                            <td class="px-6 py-5 align-middle text-green-600">Rp {{ number_format($brankas['in']) }}</td>
                            <td class="px-6 py-5 align-middle text-red-600">Rp {{ number_format($brankas['out']) }}</td>
                            <td class="px-6 py-5 align-middle text-orange-600">Rp {{ number_format($brankas['expense']) }}</td>
                            <td class="px-6 py-5 align-middle bg-gray-50 border-x border-gray-100 text-lg">Rp {{ number_format($brankas['end']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- B. TABEL LABA RUGI (ESTIMASI) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-box">
            <div class="px-6 py-4 border-b border-gray-100 bg-white no-print">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide flex items-center gap-2">
                    <span class="w-2 h-6 bg-green-600 rounded-sm"></span>
                    II. Laporan Laba Rugi (Estimasi)
                </h3>
            </div>
            <div class="print-header mt-4 mb-2 px-1 hidden">
                <h3 class="font-bold text-sm uppercase">II. Laporan Laba Rugi</h3>
            </div>

            <div class="overflow-x-auto">
                <table id="labaRugiTable" class="w-full text-sm text-left border-collapse">
                    <tbody class="divide-y divide-gray-100">
                        {{-- Pendapatan --}}
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-bold text-gray-600 w-1/2">A. TOTAL PENJUALAN (OMSET)</td>
                            {{-- Penjualan diambil dari 'out' (sesuai request Anda) --}}
                            <td class="px-6 py-3 font-mono font-bold text-green-700 text-right">Rp {{ number_format($brankas['out']) }}</td>
                        </tr>
                        
                        {{-- HPP --}}
                        <tr class="bg-gray-50/50">
                            <td class="px-6 py-3 font-bold text-gray-600 pl-10">
                                <span class="block text-xs text-gray-400 font-normal uppercase mb-1">Harga Pokok Penjualan (HPP)</span>
                                1. Persediaan Awal Valas
                            </td>
                            <td class="px-6 py-3 font-mono text-gray-500 text-right">{{ number_format($valuation['start']) }}</td>
                        </tr>
                        <tr class="bg-gray-50/50">
                            <td class="px-6 py-3 font-bold text-gray-600 pl-10">2. Pembelian Valas (+)</td>
                            {{-- Pembelian diambil dari 'in' --}}
                            <td class="px-6 py-3 font-mono text-gray-500 text-right">{{ number_format($brankas['in']) }}</td>
                        </tr>
                        <tr class="bg-gray-50/50">
                            <td class="px-6 py-3 font-bold text-gray-600 pl-10">3. Persediaan Akhir Valas (-)</td>
                            <td class="px-6 py-3 font-mono text-gray-500 text-right">({{ number_format($valuation['end']) }})</td>
                        </tr>
                        <tr class="border-t border-gray-200">
                            <td class="px-6 py-3 font-bold text-gray-800 w-1/2">B. TOTAL HPP (1 + 2 - 3)</td>
                            <td class="px-6 py-3 font-mono font-bold text-red-600 text-right">Rp ({{ number_format($hpp) }})</td>
                        </tr>

                        {{-- Laba Kotor --}}
                        <tr class="bg-blue-50/20 border-t-2 border-gray-100">
                            <td class="px-6 py-4 font-black text-gray-700">C. LABA KOTOR (A - B)</td>
                            <td class="px-6 py-4 font-mono font-black text-gray-800 text-right">Rp {{ number_format($labaKotor) }}</td>
                        </tr>

                        {{-- Biaya --}}
                        <tr>
                            <td class="px-6 py-3 font-bold text-gray-600">D. BIAYA OPERASIONAL (-)</td>
                            <td class="px-6 py-3 font-mono font-bold text-orange-600 text-right">Rp ({{ number_format($biayaOps) }})</td>
                        </tr>

                        {{-- Final --}}
                        <tr class="bg-primary text-white">
                            <td class="px-6 py-4 font-black uppercase text-sm">E. LABA BERSIH (C - D)</td>
                            <td class="px-6 py-4 font-mono font-black text-xl text-right">Rp {{ number_format($labaBersih) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- BAGIAN 2: MUTASI BANK --}}
        <br>
        <h3 class="print-only font-bold mb-2">III. MUTASI REKENING BANK</h3>
        <div class="screen-only no-print mt-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-white">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide flex items-center gap-2">
                    <span class="w-2 h-6 bg-primary rounded-sm"></span>
                    III. Mutasi Rekening Bank
                </h3>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-box">
            <div class="overflow-x-auto">
                <table id="bankTable" class="w-full text-sm text-left border-collapse">
                    <thead class="bg-white text-primary border-b-2 border-primary uppercase text-xs font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 text-left">Nama Bank</th>
                            <th class="px-6 py-4 text-center">Saldo Awal</th>
                            <th class="px-6 py-4 text-center">Masuk</th>
                            <th class="px-6 py-4 text-center">Keluar</th>
                            <th class="px-6 py-4 text-center font-black">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($bankReport as $bank)
                        <tr class="text-center font-mono text-xs hover:bg-blue-50 transition duration-150">
                            <td class="px-6 py-4 text-left font-bold text-gray-700">{{ $bank['name'] }}</td>
                            <td class="px-6 py-4 text-gray-900">{{ number_format($bank['start']) }}</td>
                            <td class="px-6 py-4 text-gray-900 font-bold">{{ $bank['in'] > 0 ? number_format($bank['in']) : '-' }}</td>
                            <td class="px-6 py-4 text-gray-900 font-bold">{{ $bank['out'] > 0 ? number_format($bank['out']) : '-' }}</td>
                            <td class="px-6 py-4 font-bold text-gray-900 bg-primary/5">{{ number_format($bank['end']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="p-6 text-center text-gray-400 italic">Belum ada akun bank yang terdaftar.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 text-xs font-bold border-t border-gray-200">
                        <tr class="text-center">
                            <td class="px-6 py-4 text-right uppercase text-gray-600">TOTAL BANK:</td>
                            <td class="px-6 py-4 text-gray-900">{{ number_format($totalBankStart) }}</td>
                            <td class="px-6 py-4 text-gray-900">{{ number_format($totalBankIn) }}</td>
                            <td class="px-6 py-4 text-gray-900">{{ number_format($totalBankOut) }}</td>
                            <td class="px-6 py-4 bg-blue-100 text-gray-900">{{ number_format($totalBankEnd) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- BAGIAN 3: TABEL RINCIAN MUTASI VALAS --}}
        <br>
        <h3 class="print-only font-bold mb-2">IV. RINCIAN MUTASI PER MATA UANG</h3>
        <div class="screen-only no-print mt-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-white">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide flex items-center gap-2">
                    <span class="w-2 h-6 bg-primary rounded-sm"></span>
                    IV. Rincian Mutasi Per Mata Uang
                </h3>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-box">
            <div class="overflow-x-auto">
                <table id="mutasiTable" class="w-full text-sm text-left border-collapse">
                    <thead class="bg-white text-primary uppercase text-xs font-bold tracking-wider">
                        <tr>
                            <th class="p-2 text-center align-middle border-b-2 border-primary text-primary bg-white sticky left-0 z-10" rowspan="2">KODE</th>
                            <th class="p-2 text-center border-b-2 border-primary border-l border-gray-100" colspan="2">STOK AWAL</th>
                            <th class="p-2 text-center border-b-2 border-primary border-l border-gray-100" colspan="2">PEMBELIAN</th>
                            <th class="p-2 text-center border-b-2 border-primary border-l border-gray-100" colspan="2">PENJUALAN</th>
                            <th class="p-2 text-center border-b-2 border-primary border-l border-gray-100" colspan="3">STOK AKHIR</th>
                        </tr>
                        <tr class="text-[10px] text-gray-500">
                            <th class="p-2 text-center border-b border-gray-100 border-l">Qty</th><th class="p-2 text-center border-b border-gray-100">IDR</th>
                            <th class="p-2 text-center border-b border-gray-100 border-l">Qty</th><th class="p-2 text-center border-b border-gray-100">IDR</th>
                            <th class="p-2 text-center border-b border-gray-100 border-l">Qty</th><th class="p-2 text-center border-b border-gray-100">IDR</th>
                            <th class="p-2 text-center border-b border-gray-100 border-l">Qty</th><th class="p-2 text-center border-b border-gray-100">Rate</th><th class="p-2 text-center border-b border-gray-100">Valuasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($currencyReport as $row)
                        <tr class="text-center font-mono text-xs hover:bg-blue-50 transition duration-150 group">
                            <td class="p-3 font-bold text-center text-gray-900 border-r border-gray-100 bg-white sticky left-0 group-hover:bg-blue-50">
                                {{ $row['currency'] ?? $row['code'] }}
                            </td>
                            {{-- Awal --}}
                            <td class="p-3 text-right text-gray-900">{{ number_format($row['awal']['qty']) }}</td>
                            <td class="p-3 text-right text-gray-900 border-r border-gray-100">{{ number_format($row['awal']['total']) }}</td>
                            {{-- Beli --}}
                            <td class="p-3 text-right text-gray-900 font-bold bg-gray-50/50">{{ $row['beli']['qty'] > 0 ? number_format($row['beli']['qty']) : '-' }}</td>
                            <td class="p-3 text-right text-gray-900 bg-gray-50/50 border-r border-gray-100">{{ $row['beli']['total'] > 0 ? number_format($row['beli']['total']) : '-' }}</td>
                            {{-- Jual --}}
                            <td class="p-3 text-right text-gray-900 font-bold bg-gray-50/50">{{ $row['jual']['qty'] > 0 ? number_format($row['jual']['qty']) : '-' }}</td>
                            <td class="p-3 text-right text-gray-900 bg-gray-50/50 border-r border-gray-100">{{ $row['jual']['total'] > 0 ? number_format($row['jual']['total']) : '-' }}</td>
                            {{-- Akhir --}}
                            <td class="p-3 text-right font-bold text-gray-900 bg-blue-50/20">{{ number_format($row['akhir']['qty']) }}</td>
                            <td class="p-3 text-right text-gray-900 bg-blue-50/20">{{ number_format($row['akhir']['avgRate'], 2) }}</td>
                            <td class="p-3 text-right font-bold text-gray-900 bg-blue-50/20">{{ number_format($row['akhir']['valuation']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 text-gray-900 text-xs font-bold border-t border-gray-200">
                       <tr>
                            <td colspan="9" class="p-4 text-right uppercase tracking-wider">Total Valuasi Aset Valas Akhir:</td>
                            <td class="p-4 text-right bg-blue-100 text-gray-900">Rp {{ number_format($valuation['end']) }}</td>
                       </tr>
                   </tfoot>
                </table>
            </div>
        </div>

        {{-- TANDA TANGAN (Hanya Print) --}}
        <div class="print-only mt-8 flex justify-end break-inside-avoid">
            <div class="text-center w-64">
                <p class="text-sm font-medium">Ubud, {{ date('d F Y') }}</p>
                <p class="text-sm font-bold mt-1">Mengetahui,</p>
                <br><br><br>
                <div class="border-b border-black w-full mb-2"></div>
                <p class="text-xs">Direktur / Branch Manager</p>
            </div>
        </div>

    </div>
</div>

<script>
    function exportToExcel() {
        var month = "{{ $filterMonth }}";
        var year = "{{ $filterYear }}";
        var fileName = 'Laporan_Bulanan_' + month + '_' + year + '.xls';
        
        var summaryHtml = document.getElementById('summaryTable').outerHTML;
        // Tambahkan tabel Laba Rugi juga ke Excel
        var labaRugiHtml = document.getElementById('labaRugiTable').outerHTML;
        
        var bankTableEl = document.getElementById('bankTable');
        var bankHtml = bankTableEl ? bankTableEl.outerHTML : '';

        var mutasiHtml = document.getElementById('mutasiTable').outerHTML;
        
        var excelContent = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <style>
                    body { font-family: sans-serif; }
                    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
                    th, td { border: 1px solid #000000; padding: 5px; }
                    th { background-color: #f0f0f0; font-weight: bold; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    .bg-navy { background-color: #0A2647; color: white; }
                </style>
            </head>
            <body>
                <h2>LAPORAN MUTASI BULANAN</h2>
                <p>Periode: ${month}/${year}</p>
                <br>
                <h3>I. RINGKASAN ARUS KAS FISIK</h3>
                ${summaryHtml}
                <br>
                <h3>II. LAPORAN LABA RUGI (ESTIMASI)</h3>
                ${labaRugiHtml}
                <br>
                <h3>III. MUTASI REKENING BANK</h3>
                ${bankHtml}
                <br>
                <h3>IV. RINCIAN MUTASI VALAS</h3>
                ${mutasiHtml}
            </body>
            </html>
        `;

        var blob = new Blob([excelContent], {type: "application/vnd.ms-excel"});
        var link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = fileName;
        link.click();
    }
</script>
@endsection