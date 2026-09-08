@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto pb-20">

    {{-- STYLE KHUSUS PRINT & EXPORT --}}
    <style>
        /* --- CSS KHUSUS PRINT (PDF) --- */
        @media print {
            @page {
                size: A4 portrait;
                margin: 20mm;
            }
            body { 
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: white !important;
                font-family: 'Times New Roman', serif;
                color: black !important;
            }

            /* Sembunyikan elemen navigasi & web-only */
            nav, aside, header, footer, .no-print, form, .btn-action, .screen-only { 
                display: none !important; 
            }
            
            /* Munculkan elemen khusus print */
            .print-only { display: block !important; }

            /* Reset Layout */
            .flex-col, .overflow-hidden, .card-box, .main-container { 
                display: block !important;
                overflow: visible !important; 
                height: auto !important;
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            /* Header Laporan Print */
            .print-header { 
                display: block !important; 
                text-align: center; 
                margin-bottom: 25px; 
                border-bottom: 3px double #000; 
                padding-bottom: 10px; 
            }

            /* TABEL PRINT STANDARD */
            table.report-table { 
                width: 100% !important; 
                border-collapse: collapse !important; 
                font-size: 12px !important; 
                margin-bottom: 20px !important;
            }
            table.report-table td { 
                padding: 4px 8px !important; 
                vertical-align: top;
            }
            
            /* Styling Baris Khusus */
            .section-title { font-weight: bold; text-transform: uppercase; padding-top: 15px !important; text-decoration: underline; }
            .total-row { font-weight: bold; border-top: 1px solid black; }
            .grand-total { font-weight: bold; border-top: 2px solid black; border-bottom: 2px double black; font-size: 14px; padding-top: 5px; padding-bottom: 5px; }
            .indent { padding-left: 25px !important; }
            
            /* Tanda Tangan */
            .signature-section {
                margin-top: 50px;
                display: flex !important;
                justify-content: space-between;
                break-inside: avoid;
            }
        }
        
        /* Helper Classes */
        .print-header { display: none; }
        .print-only { display: none; }
    </style>

    {{-- 1. HEADER HALAMAN & FILTER (WEB ONLY) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 no-print">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
            
            {{-- JUDUL --}}
            <div>
                <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2 border-l-4 border-primary pl-3">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Laporan Laba Rugi
                </h2>
                <p class="text-xs text-gray-500 mt-1 pl-3.5 font-bold uppercase tracking-wide">Analisis kinerja keuangan perusahaan.</p>
            </div>

            {{-- TOMBOL ACTION --}}
            <div class="flex gap-2">
                <button onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow transition flex items-center gap-2 text-xs font-bold uppercase tracking-wider transform active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export XLS
                </button>
                <button onclick="window.print()" class="bg-primary hover:opacity-90 text-white px-4 py-2 rounded-lg shadow transition flex items-center gap-2 text-xs font-bold uppercase tracking-wider transform active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Cetak PDF
                </button>
            </div>
        </div>

        {{-- FILTER FORM --}}
        <div class="pt-4 border-t border-gray-100">
            <form action="{{ route('keuangan.labarugi') }}" method="GET" class="flex flex-wrap items-center gap-3 justify-end">
                @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
                <div class="w-full md:w-auto">
                    <select name="branch_id" class="w-full border-gray-300 bg-gray-50 rounded-lg text-sm font-bold text-gray-700 h-10 cursor-pointer focus:ring-primary focus:border-primary" onchange="this.form.submit()">
                        <option value="">-- SEMUA CABANG --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <select name="month" class="border border-gray-300 rounded-lg px-3 text-sm font-bold text-gray-700 h-10 cursor-pointer focus:ring-primary focus:border-primary" onchange="this.form.submit()">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endforeach
                </select>

                <select name="year" class="border border-gray-300 rounded-lg px-3 text-sm font-bold text-gray-700 h-10 cursor-pointer focus:ring-primary focus:border-primary" onchange="this.form.submit()">
                    @foreach(range(date('Y')-2, date('Y')+2) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- KONTEN UTAMA --}}
    <div id="printableArea" class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 mx-4 md:mx-0">
        
        {{-- 3. HEADER CETAK (KHUSUS PRINT) --}}
        <div class="print-header">
            <h1 class="text-2xl font-bold uppercase">PT. Rupiah Dollar Valuta</h1>
            <h2 class="text-lg font-bold mt-1">LAPORAN LABA RUGI (INCOME STATEMENT)</h2>
            <p class="text-xs">
                Periode: {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
                @if(Auth::user()->role == 'admin' && request('branch_id')) 
                    | Cabang: {{ $branches->where('id', request('branch_id'))->first()->name ?? '-' }} 
                @endif
            </p>
        </div>

        {{-- JUDUL HALAMAN DI WEB (CLEAN STYLE) --}}
        <div class="text-center mb-10 border-b border-gray-200 pb-6 no-print">
            <h2 class="text-2xl font-bold text-gray-800 uppercase tracking-widest">PT. Rupiah Dollar Valuta</h2>
            <h3 class="text-xl font-bold text-primary mt-1 uppercase tracking-wide">Laporan Laba Rugi</h3>
            <p class="text-xs text-gray-500 font-bold mt-2 bg-gray-100 inline-block px-4 py-1.5 rounded-full uppercase tracking-wider">
                PERIODE: {{ strtoupper(date('F Y', mktime(0, 0, 0, $month, 1, $year))) }}
            </p>
        </div>

        {{-- 4. FORMAT LAPORAN (TAMPILAN WEB) --}}
        <div class="screen-only no-print space-y-8 max-w-4xl mx-auto">
            
            {{-- 1. PENDAPATAN --}}
            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 flex justify-between items-center">
                    <h4 class="font-bold text-sm text-gray-700 uppercase tracking-widest">1. Pendapatan Usaha</h4>
                </div>
                <div class="p-6 bg-white">
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-gray-600 font-bold uppercase">Penjualan Valas (Omzet)</span>
                        <span class="font-mono font-bold text-green-700 text-lg">Rp {{ number_format($totalPenjualan) }}</span>
                    </div>
                </div>
            </div>

            {{-- 2. HPP --}}
            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 flex justify-between items-center">
                    <h4 class="font-bold text-sm text-gray-700 uppercase tracking-widest">2. Harga Pokok Penjualan (HPP)</h4>
                </div>
                <div class="p-6 bg-white text-sm space-y-3">
                    <div class="flex justify-between text-gray-500">
                        <span>Persediaan Awal</span>
                        <span class="font-mono">{{ number_format($valuasiAwalBulan) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>+ Pembelian Valas</span>
                        <span class="font-mono">{{ number_format($totalPembelian) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500 border-b border-dashed border-gray-200 pb-3">
                        <span>- Persediaan Akhir</span>
                        <span class="font-mono">({{ number_format($nilaiStokAkhir) }})</span>
                    </div>
                    <div class="flex justify-between font-bold text-red-600 pt-2 text-base">
                        <span>TOTAL HPP</span>
                        <span class="font-mono">({{ number_format($valuasiAwalBulan + $totalPembelian - $nilaiStokAkhir) }})</span>
                    </div>
                </div>
            </div>

            {{-- LABA KOTOR --}}
            <div class="flex justify-between py-4 px-6 bg-blue-50 border border-blue-100 rounded-xl text-blue-900 font-bold items-center shadow-sm">
                <span class="uppercase text-sm tracking-widest">Laba Kotor (Gross Profit)</span>
                <span class="font-mono text-xl">Rp {{ number_format($grossProfit) }}</span>
            </div>

            {{-- 3. BEBAN OPERASIONAL --}}
            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 flex justify-between items-center">
                    <h4 class="font-bold text-sm text-gray-700 uppercase tracking-widest">3. Beban Operasional</h4>
                </div>
                <div class="p-6 bg-white text-sm space-y-3">
                    @foreach($expenses as $exp)
                    <div class="flex justify-between text-gray-600 border-b border-gray-50 pb-2 last:border-0 last:pb-0">
                        <span>Beban {{ ucwords(strtolower($exp->category)) }}</span>
                        <span class="font-mono">{{ number_format($exp->total) }}</span>
                    </div>
                    @endforeach
                    <div class="flex justify-between font-bold text-gray-800 pt-4 border-t border-gray-200 mt-2 text-base">
                        <span>TOTAL BEBAN</span>
                        <span class="font-mono text-red-600">({{ number_format($totalBeban) }})</span>
                    </div>
                </div>
            </div>

            {{-- LABA BERSIH (Primary Blue) --}}
            <div class="relative overflow-hidden flex justify-between py-6 px-8 bg-primary text-white rounded-xl shadow-lg shadow-blue-200 items-center">
                <div class="absolute right-0 top-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                <span class="uppercase font-bold text-lg tracking-widest relative z-10">LABA / RUGI BERSIH</span>
                <span class="font-mono font-bold text-3xl bg-black/20 px-6 py-2 rounded-lg relative z-10 shadow-inner">
                    Rp {{ number_format($netProfit) }}
                </span>
            </div>
        </div>


        {{-- 5. FORMAT LAPORAN (PRINT & EXCEL ONLY) --}}
        <div class="print-only">
            <table class="report-table" id="exportTable">
                <tbody>
                    {{-- 1. PENDAPATAN --}}
                    <tr>
                        <td colspan="2" class="section-title">1. PENDAPATAN USAHA</td>
                    </tr>
                    <tr>
                        <td class="indent">Penjualan Valas (Omzet)</td>
                        <td class="text-right">{{ number_format($totalPenjualan) }}</td>
                    </tr>
                    <tr>
                        <td class="total-row">Total Pendapatan</td>
                        <td class="text-right total-row">{{ number_format($totalPenjualan) }}</td>
                    </tr>
                    <tr><td colspan="2">&nbsp;</td></tr>

                    {{-- 2. HPP --}}
                    <tr>
                        <td colspan="2" class="section-title">2. HARGA POKOK PENJUALAN (HPP)</td>
                    </tr>
                    <tr>
                        <td class="indent">Persediaan Awal</td>
                        <td class="text-right">{{ number_format($valuasiAwalBulan) }}</td>
                    </tr>
                    <tr>
                        <td class="indent">Pembelian Valas (+)</td>
                        <td class="text-right">{{ number_format($totalPembelian) }}</td>
                    </tr>
                    <tr>
                        <td class="indent">Tersedia untuk Dijual</td>
                        <td class="text-right font-bold">{{ number_format($valuasiAwalBulan + $totalPembelian) }}</td>
                    </tr>
                    <tr>
                        <td class="indent">Persediaan Akhir (-)</td>
                        <td class="text-right">({{ number_format($nilaiStokAkhir) }})</td>
                    </tr>
                    <tr>
                        <td class="total-row">Total HPP</td>
                        <td class="text-right total-row">({{ number_format($valuasiAwalBulan + $totalPembelian - $nilaiStokAkhir) }})</td>
                    </tr>
                    <tr><td colspan="2">&nbsp;</td></tr>

                    {{-- LABA KOTOR --}}
                    <tr>
                        <td class="total-row" style="background-color: #f0f0f0;">LABA KOTOR (GROSS PROFIT)</td>
                        <td class="text-right total-row" style="background-color: #f0f0f0;">{{ number_format($grossProfit) }}</td>
                    </tr>
                    <tr><td colspan="2">&nbsp;</td></tr>

                    {{-- 3. BEBAN --}}
                    <tr>
                        <td colspan="2" class="section-title">3. BEBAN OPERASIONAL</td>
                    </tr>
                    @foreach($expenses as $exp)
                    <tr>
                        <td class="indent">Beban {{ $exp->category }}</td>
                        <td class="text-right">{{ number_format($exp->total) }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td class="total-row">Total Beban Operasional</td>
                        <td class="text-right total-row">({{ number_format($totalBeban) }})</td>
                    </tr>
                    <tr><td colspan="2">&nbsp;</td></tr>

                    {{-- LABA BERSIH --}}
                    <tr>
                        <td class="grand-total">LABA / RUGI BERSIH</td>
                        <td class="text-right grand-total">{{ number_format($netProfit) }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- TANDA TANGAN (Print Only) --}}
            <div class="signature-section px-10">
                <div style="text-align: center; width: 200px;">
                    <p style="margin-bottom: 80px;">Disiapkan Oleh,</p>
                    <p style="border-top: 1px solid black; padding-top: 5px; font-weight: bold;">Accounting</p>
                </div>
                <div style="text-align: center; width: 200px;">
                    <p style="margin-bottom: 80px;">Disetujui Oleh,</p>
                    <p style="border-top: 1px solid black; padding-top: 5px; font-weight: bold;">Direktur Utama</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function exportToExcel() {
        var month = "{{ $month }}";
        var year = "{{ $year }}";
        var fileName = 'Laba_Rugi_' + month + '_' + year + '.xls';
        
        // Ambil tabel khusus (exportTable)
        var tableHtml = document.getElementById('exportTable').outerHTML;
        
        var excelContent = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <style>
                    body { font-family: 'Times New Roman', serif; }
                    table { border-collapse: collapse; width: 100%; }
                    td { padding: 5px; }
                    .text-right { text-align: right; }
                    .font-bold { font-weight: bold; }
                    .section-title { font-weight: bold; text-decoration: underline; }
                    .total-row { border-top: 1px solid black; font-weight: bold; }
                    .grand-total { border-top: 2px solid black; border-bottom: 2px double black; font-weight: bold; font-size: 14px; }
                </style>
            </head>
            <body>
                <h2 style="text-align:center">LAPORAN LABA RUGI</h2>
                <p style="text-align:center">Periode: ${month}/${year}</p>
                <br>
                ${tableHtml}
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