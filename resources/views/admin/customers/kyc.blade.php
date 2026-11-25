@extends('layouts.app')

@section('content')
<div class="flex flex-col min-h-screen pb-20"> 
    
    {{-- FORM PENGATURAN KURS THRESHOLD APU-PPT (Admin/Owner) --}}
    @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 shadow-sm">
        <form action="{{ route('nasabah.kyc.threshold-rate') }}" method="POST" class="flex flex-col md:flex-row items-center justify-between gap-4">
            @csrf
            <div class="flex items-center gap-3">
                <div>
                    <h4 class="font-bold text-blue-900 text-xs uppercase tracking-wider">Pengaturan Kurs Acuan Threshold APU-PPT (USD 10.000)</h4>
                    <p class="text-xs text-blue-700 mt-0.5">
                        Batas IDR Saat Ini: <b class="text-gray-900">Rp {{ number_format($dynamicLimitIDR ?? 150000000, 0, ',', '.') }}</b> 
                        <span class="text-[10px] text-gray-500">(Rate: Rp {{ number_format($usdRate ?? 15000, 0, ',', '.') }}/USD)</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 w-full md:w-auto">
                <input type="number" step="any" name="threshold_usd_rate" value="{{ $usdRate ?? 15000 }}" class="border-gray-300 rounded-lg p-2 text-xs font-bold w-full md:w-44 bg-white shadow-sm" required>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-xs font-bold">SIMPAN RATE</button>
            </div>
        </form>
    </div>
    @endif

    {{-- HEADER & FILTER SECTION --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            
            {{-- JUDUL HALAMAN --}}
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Analisis KYC Nasabah
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Periode: <b class="text-gray-700">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</b> 
                    s/d <b class="text-gray-700">{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</b>
                </p>

                {{-- TOMBOL PRESET CEPAT --}}
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <a href="{{ route('nasabah.kyc', ['start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d'), 'branch_id' => $branchId]) }}" 
                       class="px-2.5 py-1 rounded-md text-[11px] font-bold border transition {{ ($startDate == date('Y-m-d') && $endDate == date('Y-m-d')) ? 'bg-primary text-white border-primary' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('nasabah.kyc', ['start_date' => now()->subDays(6)->format('Y-m-d'), 'end_date' => date('Y-m-d'), 'branch_id' => $branchId]) }}" 
                       class="px-2.5 py-1 rounded-md text-[11px] font-bold border transition {{ ($startDate == now()->subDays(6)->format('Y-m-d') && $endDate == date('Y-m-d')) ? 'bg-primary text-white border-primary' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">
                        7 Hari Terakhir
                    </a>
                    <a href="{{ route('nasabah.kyc', ['start_date' => date('Y-m-01'), 'end_date' => date('Y-m-t'), 'branch_id' => $branchId]) }}" 
                       class="px-2.5 py-1 rounded-md text-[11px] font-bold border transition {{ ($startDate == date('Y-m-01') && $endDate == date('Y-m-t')) ? 'bg-primary text-white border-primary' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">
                        Bulan Ini
                    </a>
                </div>
            </div>
            
            {{-- FORM FILTER RENTANG TANGGAL --}}
            <form action="{{ route('nasabah.kyc') }}" method="GET" class="flex flex-wrap items-center gap-2 justify-end w-full lg:w-auto">
                <input type="hidden" name="risk" value="{{ $riskFilter }}">

                {{-- Cari Nama / Identitas --}}
                <div>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari Nama / No. ID..." class="border-gray-300 rounded-lg text-xs font-medium text-gray-700 focus:ring-primary focus:border-primary bg-white h-10 w-44">
                </div>

                {{-- Pilihan Cabang (Hanya Admin/Owner) --}}
                @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
                    <div>
                        <select name="branch_id" class="border-gray-300 rounded-lg text-xs font-bold text-gray-700 focus:ring-primary focus:border-primary bg-white cursor-pointer h-10">
                            <option value="">-- SEMUA CABANG --</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (isset($branchId) && $branchId == $branch->id) ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Tanggal Mulai --}}
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold text-gray-400 uppercase">Dari</span>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="border-gray-300 rounded-lg text-xs font-bold text-gray-700 focus:ring-primary focus:border-primary bg-white shadow-sm cursor-pointer h-10">
                </div>

                {{-- Tanggal Akhir --}}
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold text-gray-400 uppercase">Sampai</span>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="border-gray-300 rounded-lg text-xs font-bold text-gray-700 focus:ring-primary focus:border-primary bg-white shadow-sm cursor-pointer h-10">
                </div>

                <button type="submit" class="bg-primary text-white px-4 h-10 rounded-lg text-xs font-bold hover:bg-opacity-90 shadow-sm transition cursor-pointer">
                    Filter
                </button>
            </form>
        </div>
    </div>

    {{-- SUMMARY CARDS (BISA DIKLIK UNTUK FILTER LEVEL RISIKO) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- LOW RISK --}}
        <a href="{{ route('nasabah.kyc', array_merge(request()->query(), ['risk' => $riskFilter === 'LOW' ? null : 'LOW', 'page' => 1])) }}" 
           class="bg-white border-l-4 border-green-500 p-4 rounded-xl shadow-sm flex items-center justify-between transition hover:shadow-md {{ $riskFilter === 'LOW' ? 'ring-2 ring-green-400' : '' }}">
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Risiko Rendah</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ number_format($summary['LOW']) }} <span class="text-xs font-normal text-gray-400">Nasabah</span></h3>
            </div>
            <div class="p-3 bg-green-50 rounded-full text-green-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </a>

        {{-- MEDIUM RISK --}}
        <a href="{{ route('nasabah.kyc', array_merge(request()->query(), ['risk' => $riskFilter === 'MEDIUM' ? null : 'MEDIUM', 'page' => 1])) }}" 
           class="bg-white border-l-4 border-yellow-500 p-4 rounded-xl shadow-sm flex items-center justify-between transition hover:shadow-md {{ $riskFilter === 'MEDIUM' ? 'ring-2 ring-yellow-400' : '' }}">
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Risiko Menengah</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ number_format($summary['MEDIUM']) }} <span class="text-xs font-normal text-gray-400">Nasabah</span></h3>
            </div>
            <div class="p-3 bg-yellow-50 rounded-full text-yellow-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
        </a>

        {{-- HIGH RISK --}}
        <a href="{{ route('nasabah.kyc', array_merge(request()->query(), ['risk' => $riskFilter === 'HIGH' ? null : 'HIGH', 'page' => 1])) }}" 
           class="bg-white border-l-4 border-red-500 p-4 rounded-xl shadow-sm flex items-center justify-between transition hover:shadow-md {{ $riskFilter === 'HIGH' ? 'ring-2 ring-red-400' : '' }}">
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Risiko Tinggi</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ number_format($summary['HIGH']) }} <span class="text-xs font-normal text-gray-400">Nasabah</span></h3>
            </div>
            <div class="p-3 bg-red-50 rounded-full text-red-600 animate-pulse">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </a>
    </div>

    {{-- TABEL DATA NASABAH --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- BAR KONTROL GESER HORIZONTAL DI ATAS TABEL --}}
        <div id="kycScrollToolbar" class="px-4 py-2.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" id="btnScrollLeft"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-white text-gray-700 border border-gray-300 hover:bg-gray-100 shadow-sm transition cursor-pointer"
                    title="Geser tabel ke kiri">
                    <span>&larr;</span>
                    <span>Kiri</span>
                </button>
                <button type="button" id="btnScrollRight"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-primary text-white hover:bg-opacity-90 shadow-sm transition cursor-pointer"
                    title="Geser tabel ke kanan">
                    <span>Kanan</span>
                    <span>&rarr;</span>
                </button>
            </div>

            <div id="kycTopScroll" class="flex-1 overflow-x-auto h-4 bg-gray-200/70 rounded">
                <div id="kycTopScrollInner" class="h-1"></div>
            </div>

            <span class="text-[11px] text-gray-400 font-medium hidden sm:inline shrink-0">
                Geser horizontal
            </span>
        </div>

        <div id="kycTableWrapper" class="overflow-x-auto">
            <table id="kycTable" class="w-full text-left border-collapse whitespace-nowrap">
                <thead class="bg-white text-primary border-b-2 border-primary uppercase text-xs tracking-wider font-bold">
                    <tr>
                        <th class="px-6 py-4 text-center sticky left-0 bg-white z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)]">Aksi</th>
                        <th class="px-6 py-4">Nama Nasabah</th>
                        <th class="px-6 py-4">Identitas</th>
                        <th class="px-6 py-4">Warga Negara</th>
                        <th class="px-6 py-4">Alamat & Pekerjaan</th>
                        <th class="px-6 py-4 text-center">Freq (Periode)</th>
                        <th class="px-6 py-4 text-right">Volume (IDR)</th>
                        <th class="px-6 py-4 text-center">Status Risiko</th>
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-gray-100 text-xs text-gray-700">
                    @forelse($kycData as $row)
                    @php
                        $rowBg = $row->risk_level == 'HIGH' ? 'bg-red-50 group-hover:bg-red-100' : 'bg-white group-hover:bg-blue-50';
                    @endphp
                    <tr class="group transition duration-150 {{ $row->risk_level == 'HIGH' ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-blue-50' }}">
                        
                        {{-- Tombol Aksi Tampilkan (Sticky di Kiri) --}}
                        <td class="px-6 py-4 text-center sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] {{ $rowBg }}">
                            <a href="{{ route('nasabah.index', [
                                'search'     => $row->customer_identity_no, 
                                'start_date' => $startDate, 
                                'end_date'   => $endDate, 
                                'branch_id'  => $branchId
                            ]) }}" 
                            class="inline-flex items-center px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-bold hover:bg-opacity-90 shadow-sm transition gap-1"
                            title="Tampilkan semua transaksi nasabah ini pada periode terpilih">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 9 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Tampilkan
                            </a>
                        </td>

                        {{-- 1. Nama --}}
                        <td class="px-6 py-4 font-bold uppercase text-gray-800">
                            {{ $row->customer_name }}
                        </td>

                        {{-- 2. No ID --}}
                        <td class="px-6 py-4 font-mono text-gray-600">
                            {{ $row->customer_identity_no }}
                        </td>

                        {{-- 3. Warga Negara --}}
                        <td class="px-6 py-4 text-gray-600">
                            {{ $row->customer_country ?? '-' }}
                        </td>

                        {{-- 4. Alamat & Pekerjaan --}}
                        <td class="px-6 py-4 max-w-[250px]">
                            <div class="truncate font-medium text-gray-800" title="{{ $row->customer_address }}">
                                {{ $row->customer_address ?? '-' }}
                            </div>
                            <div class="text-[10px] text-gray-500 truncate mt-0.5 uppercase">
                                {{ $row->customer_job ?? '-' }}
                            </div>
                        </td>

                        {{-- 5. Frekuensi --}}
                        <td class="px-6 py-4 text-center">
                            <span class="inline-block bg-gray-100 px-2.5 py-1 rounded text-gray-700 font-bold border border-gray-200">
                                {{ $row->freq }}x
                            </span>
                        </td>

                        {{-- 6. Volume --}}
                        <td class="px-6 py-4 text-right font-mono font-bold text-gray-900">
                            Rp {{ number_format($row->total_volume) }}
                        </td>

                        {{-- 7. Status Risiko --}}
                        <td class="px-6 py-4 text-center">
                            @if($row->risk_level == 'HIGH')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-red-600 rounded-full animate-pulse"></span>
                                    HIGH RISK
                                </span>
                                <div class="text-[9px] text-red-600 font-bold mt-1 uppercase tracking-wide">{{ $row->action }}</div>
                            @elseif($row->risk_level == 'MEDIUM')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-yellow-500 rounded-full"></span>
                                    MEDIUM
                                </span>
                                <div class="text-[9px] text-yellow-600 mt-1 uppercase tracking-wide">{{ $row->action }}</div>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800 border border-green-200">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>
                                    LOW RISK
                                </span>
                            @endif
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-10 text-center text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-10 h-10 text-gray-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-sm font-medium">Tidak ada transaksi pada periode ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINASI --}}
        @if($kycData->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            {{ $kycData->links() }}
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('kycTableWrapper');
    const table = document.getElementById('kycTable');
    const topScroll = document.getElementById('kycTopScroll');
    const topScrollInner = document.getElementById('kycTopScrollInner');
    const toolbar = document.getElementById('kycScrollToolbar');
    const btnLeft = document.getElementById('btnScrollLeft');
    const btnRight = document.getElementById('btnScrollRight');

    if (!wrapper || !table || !topScroll || !topScrollInner) return;

    function syncScrollbarWidth() {
        topScrollInner.style.width = table.scrollWidth + 'px';
        const hasOverflow = table.scrollWidth > wrapper.clientWidth + 2;
        if (toolbar) {
            toolbar.classList.toggle('hidden', !hasOverflow);
        }
    }

    let isSyncing = false;
    topScroll.addEventListener('scroll', function () {
        if (isSyncing) return;
        isSyncing = true;
        wrapper.scrollLeft = topScroll.scrollLeft;
        requestAnimationFrame(() => { isSyncing = false; });
    });

    wrapper.addEventListener('scroll', function () {
        if (isSyncing) return;
        isSyncing = true;
        topScroll.scrollLeft = wrapper.scrollLeft;
        requestAnimationFrame(() => { isSyncing = false; });
    });

    if (btnLeft) {
        btnLeft.addEventListener('click', function () {
            wrapper.scrollBy({ left: -320, behavior: 'smooth' });
        });
    }

    if (btnRight) {
        btnRight.addEventListener('click', function () {
            wrapper.scrollBy({ left: 320, behavior: 'smooth' });
        });
    }

    syncScrollbarWidth();
    window.addEventListener('resize', syncScrollbarWidth);
});
</script>
@endsection
```[cite: 9, 11]

<ElicitationsGroup message="Langkah lanjutan jika ingin menyesuaikan halaman ini lebih jauh:">
  <Elicitation label="Export CSV rentang KYC" query="Tolong tambahkan tombol export CSV di halaman Analisis KYC sesuai filter tanggal dan cabang yang sedang aktif."/>
  <Elicitation label="Cek query bulanan KYC" query="Apakah method kyc di MenuController perlu dioptimasi lagi jika rentang tanggal 1 bulan punya puluhan ribu transaksi?"/>
</ElicitationsGroup>