@extends('layouts.app')

@section('content')
<div class="flex flex-col h-full bg-gray-50 pb-20">

    {{-- STYLE KHUSUS PRINT --}}
    <style>
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body { 
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: white !important;
                font-family: 'Times New Roman', serif;
                color: black !important;
            }
            nav, aside, header, footer, .no-print, form, .btn-action, .screen-only, button, select { 
                display: none !important; 
            }
            .print-only { display: block !important; }
            
            .print-header { 
                display: block !important; 
                text-align: center; 
                margin-bottom: 20px; 
                border-bottom: 3px double #000; 
                padding-bottom: 10px; 
            }
            
            table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 20px; }
            th, td { border: 1px solid #000; padding: 4px; vertical-align: top; }
            th { background-color: #f0f0f0 !important; font-weight: bold; text-align: center; }
            
            /* Warna Badge dihilangkan saat print, ganti jadi teks biasa bold */
            .badge-print { border: none !important; background: none !important; color: black !important; font-weight: bold; padding: 0 !important; }
        }
        .print-header { display: none; }
        .print-only { display: none; }
    </style>

    {{-- HEADER HALAMAN (WEB) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6 no-print">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            
            {{-- JUDUL --}}
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2 border-l-4 border-[#fc3858] pl-3">
                    <svg class="w-6 h-6 text-[#fc3858]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Laporan LTKT (>100 Juta)
                </h2>
                <p class="text-xs text-gray-500 mt-1 pl-3.5 font-bold uppercase tracking-wide">Laporan Transaksi Keuangan Tunai (Cash Transaction Report)</p>
            </div>

            {{-- FILTER & PRINT --}}
            <div class="flex gap-2 items-center">
                <form action="{{ route('compliance.ltkt.index') }}" method="GET" class="flex items-center gap-2 bg-gray-50 p-1 rounded border border-gray-200">
                    <select name="month" class="bg-transparent border-none text-xs font-bold text-gray-700 focus:ring-0 cursor-pointer h-8" onchange="this.form.submit()">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endforeach
                    </select>
                    <div class="w-px h-5 bg-gray-300"></div>
                    <select name="year" class="bg-transparent border-none text-xs font-bold text-gray-700 focus:ring-0 cursor-pointer h-8" onchange="this.form.submit()">
                        @foreach(range(date('Y')-2, date('Y')+2) as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
                
                <button onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded shadow hover:bg-black transition flex items-center gap-2 text-xs font-bold uppercase tracking-wider h-[38px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    CETAK
                </button>
            </div>
        </div>
    </div>

    {{-- KONTEN UTAMA --}}
    <div id="printableArea">
        
        {{-- HEADER CETAK (KHUSUS PRINT) --}}
        <div class="print-header">
            <h1 class="text-xl font-bold uppercase">PT. Rupiah Dollar Valuta</h1>
            <h2 class="text-lg font-bold mt-1">LAPORAN TRANSAKSI KEUANGAN TUNAI (LTKT)</h2>
            <p class="text-sm">Periode: {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</p>
        </div>

        {{-- ALERT STATUS (HANYA DI LAYAR) --}}
        <div class="no-print mb-6">
            @if(count($singleTrx) > 0 || count($dailyAccumulation) > 0)
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm flex items-start gap-3">
                <svg class="w-6 h-6 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div>
                    <h3 class="font-bold text-red-800 text-sm uppercase">Perhatian: Wajib Lapor PPATK</h3>
                    <p class="text-xs text-red-700 mt-1">
                        Ditemukan <b>{{ count($singleTrx) + count($dailyAccumulation) }}</b> nasabah/transaksi yang melebihi ambang batas Rp 100.000.000. Segera laporkan melalui aplikasi GRIPS.
                    </p>
                </div>
            </div>
            @else
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm flex items-center gap-3">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <h3 class="font-bold text-green-800 text-sm uppercase">NIHIL</h3>
                    <p class="text-xs text-green-700">Tidak ada transaksi tunai di atas Rp 500 Juta pada periode ini.</p>
                </div>
            </div>
            @endif
        </div>

        {{-- TABEL UTAMA --}}
        <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden card-box">
            
            {{-- Header Tabel di Web --}}
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center no-print">
                <h3 class="font-bold text-gray-700 text-xs uppercase tracking-wider">Rincian Transaksi</h3>
                <span class="text-[10px] bg-gray-200 text-gray-600 px-2 py-1 rounded font-bold uppercase">Periode: {{ date('M Y', mktime(0, 0, 0, $month, 1, $year)) }}</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    {{-- Header Table Merah (Standard Government) --}}
                    <thead class="bg-[#fc3858] text-white text-xs uppercase font-semibold tracking-wide">
                        <tr>
                            <th class="p-3 border-r border-white/20 w-32">Tanggal</th>
                            <th class="p-3 border-r border-white/20">Identitas Nasabah</th>
                            <th class="p-3 border-r border-white/20">Profil & Sumber Dana</th>
                            <th class="p-3 border-r border-white/20 text-center">Pemicu Laporan</th>
                            <th class="p-3 text-right">Total Transaksi (IDR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        
                        {{-- 1. TRANSAKSI TUNGGAL > 500JT --}}
                        @foreach($singleTrx as $trx)
                        <tr class="hover:bg-orange-50/50 transition bg-white">
                            <td class="p-3 align-top border-r border-gray-100">
                                <span class="font-bold text-gray-800">{{ $trx->created_at->format('d/m/Y') }}</span><br>
                                <span class="text-[10px] text-gray-400 font-mono">{{ $trx->transaction_code }}</span>
                            </td>
                            <td class="p-3 align-top border-r border-gray-100">
                                <div class="font-bold text-gray-800 uppercase">{{ $trx->customer_name }}</div>
                                <div class="text-[10px] text-gray-500 font-mono mt-0.5">ID: {{ $trx->customer_identity_no }}</div>
                                <div class="text-[10px] text-gray-500 mt-0.5 truncate max-w-[200px]">{{ $trx->customer_address ?? '-' }}</div>
                            </td>
                            <td class="p-3 align-top border-r border-gray-100">
                                <div class="space-y-0.5">
                                    <div class="flex"><span class="w-20 text-gray-400">Pekerjaan</span><span class="font-medium">: {{ $trx->customer_job ?? '-' }}</span></div>
                                    <div class="flex"><span class="w-20 text-gray-400">Sumber Dana</span><span class="font-medium">: {{ $trx->source_of_funds ?? '-' }}</span></div>
                                    <div class="flex"><span class="w-20 text-gray-400">Tujuan</span><span class="font-medium">: {{ $trx->transaction_purpose ?? '-' }}</span></div>
                                </div>
                            </td>
                            <td class="p-3 align-top text-center border-r border-gray-100">
                                <span class="badge-print bg-orange-100 text-orange-700 px-2 py-1 rounded text-[10px] font-bold border border-orange-200 inline-block mb-1">
                                    SINGLE > 500JT
                                </span>
                            </td>
                            <td class="p-3 align-top text-right">
                                <div class="font-bold text-sm text-gray-900 font-mono">Rp {{ number_format($trx->total_idr) }}</div>
                                <div class="text-[10px] text-gray-400 font-mono mt-0.5">
                                    ({{ number_format($trx->amount_foreign) }} {{ $trx->currency }})
                                </div>
                            </td>
                        </tr>
                        @endforeach

                        {{-- 2. AKUMULASI HARIAN > 500JT --}}
                        @foreach($dailyAccumulation as $row)
                        <tr class="hover:bg-purple-50/50 transition bg-purple-50/10">
                            <td class="p-3 align-top border-r border-gray-100">
                                <span class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($row->trx_date)->format('d/m/Y') }}</span>
                            </td>
                            <td class="p-3 align-top border-r border-gray-100">
                                <div class="font-bold text-purple-900 uppercase">{{ $row->customer_name }}</div>
                                <div class="text-[10px] text-gray-500 font-mono mt-0.5">ID: {{ $row->customer_identity_no }}</div>
                                <div class="text-[10px] text-purple-600 italic mt-1 font-bold">Accumulated Transaction</div>
                            </td>
                            <td class="p-3 align-top border-r border-gray-100">
                                <div class="text-[10px] text-gray-500 italic">
                                    Akumulasi dari beberapa transaksi tunai dalam satu hari yang sama (Structuring Indication).
                                </div>
                            </td>
                            <td class="p-3 align-top text-center border-r border-gray-100">
                                <span class="badge-print bg-purple-100 text-purple-700 px-2 py-1 rounded text-[10px] font-bold border border-purple-200 inline-block mb-1">
                                    AKUMULASI ({{ $row->freq }}x)
                                </span>
                            </td>
                            <td class="p-3 align-top text-right bg-purple-50/20">
                                <div class="font-bold text-sm text-purple-800 font-mono">Rp {{ number_format($row->total_daily) }}</div>
                            </td>
                        </tr>
                        @endforeach

                        {{-- JIKA KOSONG --}}
                        @if(count($singleTrx) == 0 && count($dailyAccumulation) == 0)
                        <tr>
                            <td colspan="5" class="p-10 text-center text-gray-400 bg-white">
                                <div class="flex flex-col items-center">
                                    <svg class="w-10 h-10 text-gray-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="text-xs font-bold uppercase">Tidak Ada Laporan LTKT</span>
                                </div>
                            </td>
                        </tr>
                        @endif

                    </tbody>
                </table>
            </div>
        </div>

        {{-- FOOTER / TANDA TANGAN (KHUSUS PRINT) --}}
        <div class="print-only mt-10 px-10">
             <div class="flex justify-between text-sm">
                <div class="text-center w-64">
                    <p class="mb-16">Disiapkan Oleh,</p>
                    <p class="border-t border-black pt-1 font-bold">Petugas Kepatuhan</p>
                </div>
                <div class="text-center w-64">
                    <p class="mb-16">Mengetahui,</p>
                    <p class="border-t border-black pt-1 font-bold">Direktur Utama</p>
                </div>
             </div>
        </div>

    </div>

    {{-- KETERANGAN (WEB ONLY) --}}
    <div class="mx-4 mt-6 text-[10px] text-gray-500 bg-white p-4 rounded-lg border border-gray-200 shadow-sm no-print">
        <h4 class="font-bold text-gray-700 mb-2 uppercase tracking-wide border-b border-gray-100 pb-1">Catatan Kepatuhan:</h4>
        <ul class="list-disc pl-4 space-y-1">
            <li>Laporan LTKT wajib disampaikan ke PPATK paling lambat 14 hari kerja setelah tanggal transaksi.</li>
            <li>Transaksi yang dipecah-pecah (Structuring) dalam satu hari yang totalnya > 500 Juta tetap wajib lapor.</li>
            <li>Pastikan data identitas nasabah (KTP/Paspor) sudah lengkap dan valid sebelum pelaporan.</li>
        </ul>
    </div>

</div>
@endsection