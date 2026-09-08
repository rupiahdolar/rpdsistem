@extends('layouts.app')

@section('content')
<div class="flex flex-col h-full bg-gray-50 pb-20">
    
    {{-- STYLE KHUSUS PRINT & TAMPILAN --}}
    <style>
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
                background: white !important; 
                font-family: sans-serif; 
            }
            /* Sembunyikan elemen UI saat print */
            aside, nav, .no-print, form, .btn-action, .form-card { display: none !important; }
            
            .flex-col, .overflow-hidden, .card-box { 
                display: block !important; 
                overflow: visible !important; 
                height: auto !important; 
                border: none !important; 
                box-shadow: none !important; 
                margin: 0 !important; 
            }
            .print-header { 
                display: block !important; 
                text-align: center; 
                margin-bottom: 20px; 
                border-bottom: 2px solid #000; 
                padding-bottom: 10px; 
            }
            
            table { width: 100% !important; border-collapse: collapse !important; font-size: 11px !important; margin-bottom: 20px !important; }
            th, td { border: 1px solid #000 !important; padding: 6px !important; }
            
            * { color: #000 !important; text-shadow: none !important; }
            .bg-primary, .bg-blue-50, .bg-green-50, .bg-red-50, .bg-yellow-50, .bg-gray-50 {
                background-color: transparent !important;
            }
            tr.final-row { border-top: 2px solid #000 !important; font-weight: bold !important; }
        }
        .print-header { display: none; }
    </style>

    {{-- BAGIAN FILTER (TAMPIL DI LAYAR SAJA) --}}
    <div class="no-print px-6 pt-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            
            {{-- JUDUL --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Arus Kas Harian (Fisik)
                </h1>
                <p class="text-gray-500 text-sm mt-1">Detail keluar masuk uang tunai per hari.</p>
            </div>

            {{-- FORM FILTER --}}
            <div class="bg-white p-2 rounded-xl shadow-sm border border-gray-200">
                <form action="{{ route('reports.cashflow') }}" method="GET" class="flex items-center gap-2">
                    
                    {{-- 1. PILIH TANGGAL --}}
                    <div>
                        <input type="date" name="date" value="{{ $filterDate }}" class="border-gray-300 rounded-lg text-sm font-bold text-gray-700 h-10 focus:ring-primary focus:border-primary shadow-sm" onchange="this.form.submit()">
                    </div>

                    {{-- 2. PILIH CABANG --}}
                    @if(!$isRestricted)
                        {{-- ADMIN/OWNER: Dropdown --}}
                        <div>
                            <select name="branch_id" class="border-gray-300 rounded-lg text-sm font-bold text-gray-700 h-10 focus:ring-primary focus:border-primary bg-white cursor-pointer w-48" onchange="this.form.submit()">
                                <option value="">-- SEMUA CABANG --</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ (isset($branchId) && $branchId == $b->id) ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        {{-- KASIR: Label Mati --}}
                        <div class="bg-gray-100 border border-gray-300 rounded-lg px-4 h-10 flex items-center text-sm font-bold text-gray-600">
                            {{ $branchName }}
                        </div>
                    @endif

                    {{-- 3. TOMBOL --}}
                    <div class="flex gap-2 ml-2">
                        <button type="button" onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white px-4 h-10 rounded-lg shadow flex items-center gap-2 transition" title="Download Excel">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            <span class="text-xs font-bold hidden md:inline">XLS</span>
                        </button>
                        <button type="button" onclick="window.print()" class="bg-primary hover:opacity-90 text-white px-4 h-10 rounded-lg shadow flex items-center gap-2 transition" title="Cetak PDF">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            <span class="text-xs font-bold hidden md:inline">PDF</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- AREA PRINTABLE --}}
    <div class="px-6 pb-6 flex-1">
        <div id="printableArea" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-box">
            
            {{-- HEADER CETAK (PDF ONLY) --}}
            <div class="print-header pt-4">
                {{-- [DINAMIS] TAMPILKAN NAMA CABANG SEBAGAI KOP SURAT --}}
                <h1 class="text-2xl font-bold uppercase">
                    @if(isset($branchName) && $branchName != 'SEMUA CABANG')
                        {{ $branchName }}
                    @else
                        PT. Rupiah Dollar Valuta
                    @endif
                </h1>
                
                <p class="text-sm">Authorized Money Changer</p>
                <hr class="my-2 border-black">
                <h2 class="text-xl font-bold mt-2">ARUS KAS HARIAN</h2>
                
                {{-- Jika menampilkan semua cabang, beri keterangan --}}
                @if(!isset($branchName) || $branchName == 'SEMUA CABANG')
                    <p class="text-sm font-bold uppercase">REKAP GABUNGAN (SEMUA CABANG)</p>
                @endif
                
                <p class="text-xs">Tanggal: {{ \Carbon\Carbon::parse($filterDate)->isoFormat('dddd, D MMMM Y') }}</p>
            </div>

            {{-- HEADER TABEL (LAYAR ONLY) --}}
            <div class="px-6 py-4 border-b border-gray-100 bg-white no-print flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide flex items-center gap-2">
                        <span class="w-2 h-6 bg-primary rounded-sm"></span>
                        Riwayat Transaksi: {{ \Carbon\Carbon::parse($filterDate)->format('d/m/Y') }}
                    </h3>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-500 block">Akun Kas:</span>
                    <span class="font-bold text-primary">1-1002 (IDR Fisik)</span>
                </div>
            </div>

            {{-- TABEL DATA --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse" id="cashTable">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 uppercase text-xs font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-3 text-center w-24">Jam</th>
                            <th class="px-6 py-3 text-center w-24">No. Ref</th>
                            <th class="px-6 py-3">Keterangan</th>
                            <th class="px-6 py-3 text-right text-green-700">Masuk (Debit)</th>
                            <th class="px-6 py-3 text-right text-red-700">Keluar (Kredit)</th>
                            <th class="px-6 py-3 text-right text-primary bg-blue-50">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {{-- BARIS 1: SALDO AWAL --}}
                        <tr class="bg-yellow-50 font-bold text-gray-700">
                            <td class="px-6 py-3 text-center font-mono text-xs">-</td>
                            <td class="px-6 py-3 text-center">-</td>
                            <td class="px-6 py-3 uppercase">SALDO AWAL (SISA KEMARIN)</td>
                            <td class="px-6 py-3 text-center">-</td>
                            <td class="px-6 py-3 text-center">-</td>
                            <td class="px-6 py-3 text-right font-mono bg-yellow-100/50">Rp {{ number_format($saldoAwal) }}</td>
                        </tr>

                        {{-- LOOP DATA TRANSAKSI --}}
                        @forelse($data as $row)
                        <tr class="hover:bg-gray-50 transition duration-150 text-xs">
                            <td class="px-6 py-3 text-center font-mono text-gray-500">
                                {{ $row['time'] }}
                            </td>
                            <td class="px-6 py-3 text-center font-mono text-gray-500">
                                {{ $row['ref'] }}
                            </td>
                            <td class="px-6 py-3">
                                <span class="font-bold text-gray-700">{{ $row['desc'] }}</span>
                                {{-- Label Kategori (Hanya Tampil di Layar) --}}
                                @if(str_contains($row['ref'], 'EXP')) 
                                    <span class="no-print ml-1 text-[9px] px-1 rounded bg-red-100 text-red-600 border border-red-200">BIAYA</span>
                                @endif
                                @if(str_contains($row['ref'], 'MUT')) 
                                    <span class="no-print ml-1 text-[9px] px-1 rounded bg-blue-100 text-blue-600 border border-blue-200">MUTASI</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right font-mono text-green-600 font-bold">
                                {{ $row['in'] > 0 ? number_format($row['in']) : '-' }}
                            </td>
                            <td class="px-6 py-3 text-right font-mono text-red-600 font-bold">
                                {{ $row['out'] > 0 ? number_format($row['out']) : '-' }}
                            </td>
                            <td class="px-6 py-3 text-right font-mono font-bold text-primary bg-blue-50/20">
                                Rp {{ number_format($row['balance']) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400 italic">
                                Tidak ada pergerakan kas pada tanggal ini.
                            </td>
                        </tr>
                        @endforelse

                        {{-- BARIS TERAKHIR: SALDO AKHIR --}}
                        <tr class="final-row bg-primary text-white font-bold border-t-2 border-gray-300">
                            <td class="px-6 py-3 text-center uppercase" colspan="5">SALDO AKHIR HARI INI</td>
                            <td class="px-6 py-3 text-right font-mono">Rp {{ number_format($currentBalance) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function exportToExcel() {
        var date = "{{ $filterDate }}";
        var fileName = 'Laporan_Arus_Kas_' + date + '.xls';
        
        // Ambil elemen tabel
        var tableHtml = document.getElementById('cashTable').outerHTML;
        
        var excelContent = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <style>
                    body { font-family: sans-serif; }
                    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
                    th, td { border: 1px solid #000000; padding: 5px; }
                    .text-center { text-align: center; }
                    .text-right { text-align: right; }
                    th { background-color: #f0f0f0; font-weight: bold; }
                </style>
            </head>
            <body>
                <h2>LAPORAN ARUS KAS (CASH FLOW)</h2>
                <p>Tanggal: ${date}</p>
                <p>Kantor: {{ $branchName }}</p>
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