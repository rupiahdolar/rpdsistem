@extends('layouts.app')

@section('content')
<div class="flex flex-col h-full bg-gray-50 pb-20">
    
    {{-- STYLE KHUSUS PRINT --}}
    <style>
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; background: white !important; font-family: sans-serif; }
            aside, nav, .no-print, form, .btn-action { display: none !important; }
            .flex-col, .overflow-hidden, .card-box { display: block !important; overflow: visible !important; height: auto !important; border: none !important; box-shadow: none !important; margin: 0 !important; }
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            table { width: 100% !important; border-collapse: collapse !important; font-size: 11px !important; margin-bottom: 20px !important; }
            th, td { border: 1px solid #000 !important; padding: 6px !important; color: #000 !important; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            .text-green-600, .text-green-700, .text-red-600, .text-red-700 { color: #000 !important; }
            .bg-primary { background-color: #eee !important; color: #000 !important; }
        }
        .print-header { display: none; }
    </style>

    {{-- HEADER & FILTER SECTION --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8 no-print">
        <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
            
            {{-- JUDUL HALAMAN --}}
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Laporan Mutasi Harian
                </h2>
                <p class="text-xs text-gray-500 mt-1">Rekapitulasi transaksi tunai, bank, dan pergerakan valuta asing.</p>
            </div>

            {{-- FORM FILTER --}}
            <form action="{{ route('mutasi.harian') }}" method="GET" class="flex flex-wrap items-center gap-3 justify-end w-full lg:w-auto">
                <div class="text-right border-r border-gray-200 pr-4 mr-1 hidden xl:block">
                    <span class="block text-[10px] text-gray-400 font-bold uppercase tracking-wider">Operator</span>
                    <span class="block text-xs font-bold text-gray-700">{{ Auth::user()->name }}</span>
                </div>

                {{-- FILTER MULAI (TANGGAL & SHIFT) --}}
                <div class="flex items-center gap-1 bg-gray-50 p-1.5 rounded-lg border border-gray-200">
                    <span class="text-[11px] font-bold text-gray-500 px-1">Mulai:</span>
                    <input type="date" name="start_date" value="{{ request('start_date', $startDate ?? date('Y-m-d')) }}" class="border-gray-300 rounded-md text-xs font-bold text-gray-700 focus:ring-primary focus:border-primary bg-white shadow-sm cursor-pointer h-9 px-2">
                    <select name="start_shift" class="border-gray-300 rounded-md text-xs font-bold text-gray-700 focus:ring-primary focus:border-primary bg-white cursor-pointer h-9 px-2">
                        <option value="all" {{ request('start_shift', $startShift ?? 'all') == 'all' ? 'selected' : '' }}>Semua Shift</option>
                        <option value="1" {{ request('start_shift', $startShift ?? 'all') == '1' ? 'selected' : '' }}>Shift 1 (00:00 - 11:59)</option>
                        <option value="2" {{ request('start_shift', $startShift ?? 'all') == '2' ? 'selected' : '' }}>Shift 2 (12:00 - 23:59)</option>
                    </select>
                </div>

                {{-- FILTER SAMPAI (TANGGAL & SHIFT) --}}
                <div class="flex items-center gap-1 bg-gray-50 p-1.5 rounded-lg border border-gray-200">
                    <span class="text-[11px] font-bold text-gray-500 px-1">Sampai:</span>
                    <input type="date" name="end_date" value="{{ request('end_date', $endDate ?? date('Y-m-d')) }}" class="border-gray-300 rounded-md text-xs font-bold text-gray-700 focus:ring-primary focus:border-primary bg-white shadow-sm cursor-pointer h-9 px-2">
                    <select name="end_shift" class="border-gray-300 rounded-md text-xs font-bold text-gray-700 focus:ring-primary focus:border-primary bg-white cursor-pointer h-9 px-2">
                        <option value="all" {{ request('end_shift', $endShift ?? 'all') == 'all' ? 'selected' : '' }}>Semua Shift</option>
                        <option value="1" {{ request('end_shift', $endShift ?? 'all') == '1' ? 'selected' : '' }}>Shift 1 (00:00 - 11:59)</option>
                        <option value="2" {{ request('end_shift', $endShift ?? 'all') == '2' ? 'selected' : '' }}>Shift 2 (12:00 - 23:59)</option>
                    </select>
                </div>

                {{-- DROPDOWN CABANG (KHUSUS ADMIN & OWNER) --}}
                @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
                    <div>
                        <select name="branch_id" class="border-gray-300 rounded-lg text-xs font-bold text-gray-700 focus:ring-primary focus:border-primary bg-white cursor-pointer h-10 px-3">
                            <option value="">-- SEMUA CABANG --</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id', $branchId ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                
                {{-- TOMBOL ACTION --}}
                <div class="flex items-center gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition shadow flex items-center gap-1.5 h-10" title="Tampilkan Data Filter">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <span class="text-xs font-bold">Tampilkan</span>
                    </button>

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

    {{-- AREA PRINT --}}
    <div id="printableArea" class="space-y-8">
        
        <div class="print-header">
            <h1 class="text-2xl font-bold uppercase">PT. Rupiah Dollar Valuta</h1>
            <p class="text-sm">Authorized Money Changer</p>
            <hr class="my-2 border-black">
            <h2 class="text-xl font-bold mt-2">LAPORAN MUTASI HARIAN</h2>
            <p class="text-sm">
                Periode: {{ \Carbon\Carbon::parse(request('start_date', $startDate ?? date('Y-m-d')))->isoFormat('D MMMM Y') }} (Shift {{ request('start_shift', 1) }}) 
                s/d {{ \Carbon\Carbon::parse(request('end_date', $endDate ?? date('Y-m-d')))->isoFormat('D MMMM Y') }} (Shift {{ request('end_shift', 2) }})
            </p>
        </div>

        {{-- BAGIAN 1: RINGKASAN KAS FISIK --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-box">
            <div class="px-6 py-4 border-b border-gray-100 bg-white no-print">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide flex items-center gap-2">
                    <span class="w-2 h-6 bg-primary rounded-sm"></span>
                    I. Ringkasan Kas Fisik (Tunai)
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse" id="summaryTable">
                    <thead class="bg-white text-primary border-b-2 border-primary uppercase text-xs font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 text-left">Modal Awal</th>
                            <th class="px-6 py-4 text-left text-green-700">Pembelian</th>
                            <th class="px-6 py-4 text-left text-red-700">Penjualan</th>
                            <th class="px-6 py-4 text-left text-orange-600">Biaya Ops</th>
                            <th class="px-6 py-4 text-left bg-gray-50 text-gray-800">Saldo Akhir</th>
                            <th class="px-6 py-4 text-left bg-blue-50 text-primary">Total Aset</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="text-left font-mono font-bold text-gray-800">
                            <td class="px-6 py-5 align-middle">Rp {{ number_format($brankas['start'] ?? 0) }}</td>
                            <td class="px-6 py-5 align-middle text-green-600">Rp {{ number_format($brankas['in'] ?? 0) }}</td>
                            <td class="px-6 py-5 align-middle text-red-600">Rp {{ number_format($brankas['out'] ?? 0) }}</td>
                            <td class="px-6 py-5 align-middle text-orange-600">Rp {{ number_format($brankas['expense'] ?? 0) }}</td>
                            <td class="px-6 py-5 align-middle bg-gray-50 border-x border-gray-100">Rp {{ number_format($brankas['end'] ?? 0) }}</td>
                            
                            <td class="px-6 py-5 align-middle bg-blue-50 text-primary border-l border-blue-100">
                                <div class="flex flex-col items-start justify-center w-full">
                                    <span class="text-lg font-black block mb-2">Rp {{ number_format($brankas['end_asset'] ?? 0) }}</span>
                                    
                                    <div class="text-xs font-bold text-gray-900 leading-snug block no-print text-left space-y-1">
                                        <div class="flex gap-2">
                                            <span class="w-12 text-gray-500">Tunai</span> 
                                            <span>: Rp {{ number_format($brankas['end'] ?? 0) }}</span>
                                        </div>
                                        <div class="flex gap-2">
                                            <span class="w-12 text-gray-500">Bank</span> 
                                            <span>: Rp {{ number_format($brankas['end_bank'] ?? 0) }}</span>
                                        </div>
                                        <div class="flex gap-2">
                                            <span class="w-12 text-gray-500">Valas</span> 
                                            <span>: Rp {{ number_format($brankas['end_valas'] ?? 0) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- BAGIAN 2: MUTASI BANK --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-box">
            <div class="px-6 py-4 border-b border-gray-100 bg-white no-print">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide flex items-center gap-2">
                    <span class="w-2 h-6 bg-blue-400 rounded-sm"></span>
                    II. Mutasi Rekening Bank
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse" id="bankTable">
                    <thead class="bg-white text-primary border-b-2 border-primary uppercase text-xs font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 text-left">Nama Bank</th>
                            <th class="px-6 py-4 text-center">Saldo Awal</th>
                            <th class="px-6 py-4 text-center text-green-700">Masuk</th>
                            <th class="px-6 py-4 text-center text-red-700">Keluar</th>
                            <th class="px-6 py-4 text-center font-black">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($bankReport as $bank)
                        <tr class="text-center font-mono text-xs hover:bg-blue-50 transition duration-150">
                            <td class="px-6 py-3 text-left font-bold text-gray-700">{{ $bank['name'] }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ number_format($bank['start']) }}</td>
                            <td class="px-6 py-3 text-green-600 font-bold">{{ $bank['in'] > 0 ? number_format($bank['in']) : '-' }}</td>
                            <td class="px-6 py-3 text-red-600 font-bold">{{ $bank['out'] > 0 ? number_format($bank['out']) : '-' }}</td>
                            <td class="px-6 py-3 font-bold text-primary bg-primary/5">{{ number_format($bank['end']) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">Belum ada data rekening bank.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 text-xs font-bold border-t border-gray-200">
                        <tr class="text-center">
                            <td class="px-6 py-3 text-right uppercase text-gray-600">TOTAL BANK:</td>
                            <td class="px-6 py-3 text-gray-600">{{ number_format($totalBankStart ?? 0) }}</td>
                            <td class="px-6 py-3 text-green-700">{{ number_format($totalBankIn ?? 0) }}</td>
                            <td class="px-6 py-3 text-red-700">{{ number_format($totalBankOut ?? 0) }}</td>
                            <td class="px-6 py-3 bg-blue-100 text-primary">{{ number_format($totalBankEnd ?? 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- BAGIAN 3: TABEL VALAS --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-box">
             <div class="px-6 py-4 border-b border-gray-100 bg-white no-print">
                 <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide flex items-center gap-2">
                    <span class="w-2 h-6 bg-green-500 rounded-sm"></span>
                    III. Rekapitulasi Valuta Asing
                 </h3>
             </div>
             
             <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse" id="valasTable">
                   <thead>
                       <tr class="bg-white text-gray-800 text-[10px] uppercase font-bold text-center border-b border-gray-200">
                           <th class="p-2 border-r border-gray-100 bg-white sticky left-0 z-10 text-primary">MATA UANG</th>
                           <th class="p-2 border-r border-gray-100 text-gray-400" colspan="2">STOK AWAL</th>
                           <th class="p-2 border-r border-gray-100 text-green-700 bg-green-50/30" colspan="2">Pembelian</th>
                           <th class="p-2 border-r border-gray-100 text-red-700 bg-red-50/30" colspan="2">Penjualan</th>
                           <th class="p-2 text-primary bg-blue-50/30" colspan="3">STOK AKHIR</th>
                       </tr>
                       <tr class="bg-white text-gray-500 text-[10px] font-bold text-center border-b-2 border-primary tracking-wider">
                           <th class="p-2 bg-white sticky left-0 z-10 border-r border-gray-100 text-primary">KODE</th>
                           <th class="p-2 border-r border-gray-100">QTY</th> <th class="p-2 border-r border-gray-100">IDR</th>
                           <th class="p-2 text-green-600 bg-green-50/30">QTY</th> <th class="p-2 border-r border-gray-100 text-green-600 bg-green-50/30">IDR</th>
                           <th class="p-2 text-red-600 bg-red-50/30">QTY</th> <th class="p-2 border-r border-gray-100 text-red-600 bg-red-50/30">IDR</th>
                           <th class="p-2 text-primary bg-blue-50/30">QTY</th> <th class="p-2 text-primary bg-blue-50/30">RATA-RATA</th> <th class="p-2 text-primary bg-blue-50/30">VALUASI</th>
                       </tr>
                   </thead>
                   <tbody class="divide-y divide-gray-100">
                       @foreach($currencyReport as $row)
                       <tr class="text-center font-mono text-xs hover:bg-yellow-50 transition duration-150 group">
                           <td class="p-2 font-bold text-left bg-white sticky left-0 group-hover:bg-yellow-50 text-gray-900 border-r border-gray-100">
                               {{ $row['currency'] }}
                           </td>
                           <td class="p-2 text-gray-900">{{ number_format($row['awal']['qty']) }}</td>
                           <td class="p-2 text-gray-900 border-r border-gray-100">{{ number_format($row['awal']['total']) }}</td>
                           <td class="p-2 text-gray-900 font-bold bg-green-50/10">{{ $row['beli']['qty'] > 0 ? number_format($row['beli']['qty']) : '-' }}</td>
                           <td class="p-2 text-gray-900 bg-green-50/10 border-r border-gray-100">{{ $row['beli']['total'] > 0 ? number_format($row['beli']['total']) : '-' }}</td>
                           <td class="p-2 text-gray-900 font-bold bg-red-50/10">{{ $row['jual']['qty'] > 0 ? number_format($row['jual']['qty']) : '-' }}</td>
                           <td class="p-2 text-gray-900 bg-red-50/10 border-r border-gray-100">{{ $row['jual']['total'] > 0 ? number_format($row['jual']['total']) : '-' }}</td>
                           <td class="p-2 font-bold text-gray-900 bg-blue-50/20">{{ number_format($row['akhir']['qty']) }}</td>
                           <td class="p-2 text-gray-900 bg-blue-50/20">{{ number_format($row['akhir']['avgRate'], 2) }}</td>
                           <td class="p-2 font-bold text-gray-900 bg-blue-50/20">{{ number_format($row['akhir']['valuation']) }}</td>
                       </tr>
                       @endforeach
                   </tbody>
                   <tfoot class="bg-white border-t-2 border-primary text-xs font-bold">
                       <tr>
                            <td colspan="9" class="px-6 py-4 text-right uppercase text-gray-500">Total Valuasi Aset Valas:</td>
                            <td class="px-6 py-4 text-center text-gray-900 bg-blue-50">Rp {{ number_format($valuation['end'] ?? 0) }}</td>
                       </tr>
                   </tfoot>
                </table>
             </div>
        </div>
    </div>
</div>

<script>
    function exportToExcel() {
        var startDate = "{{ request('start_date', $startDate ?? date('Y-m-d')) }}";
        var endDate = "{{ request('end_date', $endDate ?? date('Y-m-d')) }}";
        var fileName = 'Laporan_Mutasi_' + startDate + '_sd_' + endDate + '.xls';
        
        var summaryHtml = document.getElementById('summaryTable').outerHTML;
        var bankHtml = document.getElementById('bankTable').outerHTML;
        var valasHtml = document.getElementById('valasTable').outerHTML;
        
        var excelContent = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <style>
                    body { font-family: sans-serif; }
                    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
                    th, td { border: 1px solid #000000; padding: 5px; text-align: center; }
                    .text-left { text-align: left; }
                </style>
            </head>
            <body>
                <h2>LAPORAN MUTASI HARIAN</h2>
                <p>Periode: ${startDate} s/d ${endDate}</p>
                <br>
                <h3>I. RINGKASAN KAS FISIK</h3>
                ${summaryHtml}
                <br>
                <h3>II. MUTASI REKENING BANK</h3>
                ${bankHtml}
                <br>
                <h3>III. REKAPITULASI VALAS</h3>
                ${valasHtml}
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