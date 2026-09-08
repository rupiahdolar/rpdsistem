@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto pb-20">

    {{-- STYLE KHUSUS CETAK --}}
    <style>
        @media print {
            @page { size: A4 portrait; margin: 20mm; }
            body { 
                background: white !important; 
                font-family: 'Times New Roman', serif; 
                color: black !important; 
            }
            /* Sembunyikan elemen web */
            aside, nav, .no-print, header, footer, .input-section, form, button { 
                display: none !important; 
            }
            /* Layout Cetak */
            .print-header { 
                display: block !important; 
                text-align: center; 
                border-bottom: 3px double #000; 
                margin-bottom: 20px; 
                padding-bottom: 10px;
            }
            .laporan-section { 
                width: 100% !important; 
                border: none !important; 
                box-shadow: none !important; 
                padding: 0 !important;
                margin: 0 !important;
            }
            .bg-gray-50, .bg-blue-50, .bg-white, .bg-primary { background-color: transparent !important; }
            .grid { display: block !important; }
            .lg\:col-span-2 { width: 100% !important; }
            
            /* Warna Text Cetak Hitam */
            .text-red-600, .text-green-600, .text-primary, .text-white { color: black !important; }
            .bg-accent { background-color: transparent !important; color: black !important; border-top: 2px solid black; border-bottom: 2px solid black; }
        }
        .print-header { display: none; }
    </style>

    {{-- 1. HEADER HALAMAN & FILTER (WEB ONLY) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 no-print">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            
            {{-- JUDUL --}}
            <div>
                <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2 border-l-4 border-primary pl-3">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    Perubahan Ekuitas
                </h2>
                <p class="text-xs text-gray-500 mt-1 pl-3.5 font-bold uppercase tracking-wide">Statement of Changes in Equity</p>
            </div>
            
            {{-- FILTER --}}
            <form action="{{ route('keuangan.ekuitas') }}" method="GET" class="flex items-center gap-3 bg-gray-50 p-1.5 rounded-lg border border-gray-200">
                <select name="month" class="bg-transparent border-none text-sm font-bold text-gray-700 h-9 cursor-pointer focus:ring-0" onchange="this.form.submit()">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endforeach
                </select>
                <div class="w-px h-6 bg-gray-300"></div>
                <select name="year" class="bg-transparent border-none text-sm font-bold text-gray-700 h-9 cursor-pointer focus:ring-0" onchange="this.form.submit()">
                    @foreach(range(date('Y')-2, date('Y')+2) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                
                <button type="button" onclick="window.print()" class="bg-primary hover:opacity-90 text-white px-4 py-2 rounded-md shadow transition flex items-center gap-2 ml-2 text-xs font-bold uppercase tracking-wider transform active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    PRINT
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- BAGIAN 1: INPUT DATA (KIRI) - HILANG SAAT PRINT --}}
        <div class="lg:col-span-1 input-section space-y-8 no-print">
            
            {{-- FORM INPUT --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-primary px-6 py-4 border-b border-white/10">
                    <h3 class="font-bold text-white text-sm uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Input Mutasi Modal
                    </h3>
                </div>
                <form action="{{ route('keuangan.ekuitas.store') }}" method="POST" class="p-6 space-y-5">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase">Tanggal Transaksi</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-primary focus:border-primary font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase">Jenis Transaksi</label>
                        <select name="type" class="w-full border-gray-300 rounded-lg p-2.5 text-sm font-bold text-gray-800 bg-gray-50 focus:ring-primary focus:border-primary">
                            <option value="PRIVE">AMBILAN PRIBADI (DIVIDEN/PRIVE)</option>
                            <option value="SETOR_MODAL">SETORAN MODAL TAMBAHAN</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase">Keterangan</label>
                        <input type="text" name="description" class="w-full border-gray-300 rounded-lg p-2.5 text-sm uppercase focus:ring-primary focus:border-primary font-medium" placeholder="CTH: PRIVE BULAN JANUARI">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase">Nominal (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-500 font-bold text-sm">Rp</span>
                            <input type="number" name="amount" class="w-full pl-10 border-gray-300 rounded-lg p-2.5 text-sm font-bold font-mono focus:ring-primary focus:border-primary" placeholder="0">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-gray-800 text-white font-bold py-3 rounded-lg hover:bg-black transition shadow-lg text-xs uppercase tracking-widest transform active:scale-95">
                        SIMPAN DATA
                    </button>
                </form>
            </div>

            {{-- TABEL RIWAYAT KECIL --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    Riwayat Mutasi Bulan Ini
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <tbody class="divide-y divide-gray-100">
                            @forelse($mutations as $m)
                            <tr class="hover:bg-blue-50/20 transition group">
                                <td class="p-4">
                                    <span class="font-bold block uppercase {{ $m->type == 'PRIVE' ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $m->type == 'PRIVE' ? 'PRIVE (AMBILAN)' : 'SETOR MODAL' }}
                                    </span>
                                    <span class="text-gray-400 font-mono text-[10px]">{{ \Carbon\Carbon::parse($m->date)->format('d/m/Y') }}</span>
                                </td>
                                <td class="p-4 text-right font-mono font-bold text-gray-700">
                                    Rp {{ number_format($m->amount) }}
                                </td>
                                <td class="p-4 text-center w-10">
                                    <form action="{{ route('keuangan.ekuitas.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Hapus data ini?');">
                                        @csrf @method('DELETE')
                                        <button class="text-gray-300 hover:text-red-600 font-bold transition text-xl leading-none" title="Hapus">&times;</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="p-8 text-center text-gray-400 italic">Belum ada data mutasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- BAGIAN 2: LAPORAN (KANAN) --}}
        <div class="lg:col-span-2 laporan-section bg-white p-8 rounded-xl shadow-sm border border-gray-200" id="printableArea">
            
            {{-- HEADER CETAK --}}
            <div class="print-header">
                <h1 class="text-xl font-bold uppercase tracking-widest">PT. Rupiah Dollar Valuta</h1>
                <h2 class="text-lg font-bold mt-2">LAPORAN PERUBAHAN EKUITAS</h2>
                <p class="text-xs mt-1">Periode: {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</p>
            </div>

            {{-- HEADER WEB --}}
            <div class="no-print mb-10 border-b border-gray-100 pb-6 text-center">
                <h2 class="text-xl font-bold text-gray-800 uppercase tracking-widest">Laporan Perubahan Modal</h2>
                <h3 class="text-lg font-bold text-primary uppercase tracking-wide mt-1">PT. Rupiah Dollar Valuta</h3>
                <p class="text-xs text-gray-500 font-bold mt-2 bg-gray-100 inline-block px-3 py-1 rounded-full uppercase tracking-wider">
                    PERIODE: {{ strtoupper(date('F Y', mktime(0, 0, 0, $month, 1, $year))) }}
                </p>
            </div>

            <div class="text-gray-800 text-sm md:text-base font-serif space-y-8">
                
                {{-- MODAL AWAL --}}
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <span class="font-bold text-gray-600 uppercase text-xs tracking-wider">Modal Awal (1 {{ date('M Y', mktime(0, 0, 0, $month, 1, $year)) }})</span>
                    <span class="font-mono font-bold text-xl text-gray-800">Rp {{ number_format($modalAwal) }}</span>
                </div>

                {{-- PENAMBAHAN --}}
                <div class="pl-6 border-l-4 border-green-100 rounded-l-sm">
                    <h4 class="font-bold text-green-700 text-xs mb-3 uppercase tracking-widest flex items-center gap-2">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span> Penambahan
                    </h4>
                    <div class="flex justify-between items-center py-1.5 border-b border-dashed border-gray-100">
                        <span class="text-gray-600">Laba Bersih Tahun Berjalan</span>
                        <span class="font-mono text-gray-800">{{ number_format($labaBersihBulanIni) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-gray-600">Setoran Modal Tambahan</span>
                        <span class="font-mono text-gray-800">{{ number_format($totalSetor) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3 mt-2 bg-green-50/50 px-4 rounded-lg">
                        <span class="font-bold text-green-800 text-xs uppercase tracking-wide">Total Penambahan</span>
                        <span class="font-mono font-bold text-green-700 text-lg">Rp {{ number_format($labaBersihBulanIni + $totalSetor) }}</span>
                    </div>
                </div>

                {{-- PENGURANGAN --}}
                <div class="pl-6 border-l-4 border-red-100 rounded-l-sm">
                    <h4 class="font-bold text-red-700 text-xs mb-3 uppercase tracking-widest flex items-center gap-2">
                         <span class="w-2 h-2 bg-red-500 rounded-full"></span> Pengurangan
                    </h4>
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-gray-600">Pengambilan Pribadi (Prive/Dividen)</span>
                        <span class="font-mono text-gray-800">({{ number_format($totalPrive) }})</span>
                    </div>
                    <div class="flex justify-between items-center py-3 mt-2 bg-red-50/50 px-4 rounded-lg">
                        <span class="font-bold text-red-800 text-xs uppercase tracking-wide">Total Pengurangan</span>
                        <span class="font-mono font-bold text-red-600 text-lg">(Rp {{ number_format($totalPrive) }})</span>
                    </div>
                </div>

                {{-- MODAL AKHIR (Primary Style) --}}
                <div class="mt-10 p-6 rounded-xl bg-primary text-white bg-accent shadow-lg shadow-blue-200 relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-white/10 rounded-full -mr-8 -mt-8 blur-2xl"></div>
                    <div class="flex justify-between items-center relative z-10">
                        <div>
                            <h3 class="text-xl font-bold uppercase tracking-widest">Modal Akhir</h3>
                            <p class="text-[11px] uppercase opacity-80 mt-1 font-medium">Per {{ date('t F Y', mktime(0, 0, 0, $month, 1, $year)) }}</p>
                        </div>
                        <div class="text-right">
                            <span class="block text-3xl font-mono font-bold drop-shadow-sm">
                                Rp {{ number_format($modalAkhir) }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- TANDA TANGAN (PRINT ONLY) --}}
                <div class="mt-16 hidden print:flex justify-end text-center break-inside-avoid pt-10">
                    <div class="w-64">
                        <p class="mb-1 text-xs">Denpasar, {{ date('t F Y', mktime(0, 0, 0, $month, 1, $year)) }}</p>
                        <p class="font-bold mb-20 text-xs uppercase">Disetujui Oleh,</p>
                        <p class="font-bold border-t border-black pt-1 text-xs uppercase">Pemilik / Direktur</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection