@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto pb-20">
    
    {{-- HEADER UTAMA & FILTER --}}
    <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-gray-500 text-sm mt-1">
                Selamat Datang, <span class="font-bold text-primary">{{ Auth::user()->name }}</span>
            </p>
        </div>

        {{-- FILTER CABANG REAL-TIME --}}
        <div class="bg-white p-2 rounded-xl shadow-sm border border-gray-200">
            <form action="{{ route('admin.dashboard') }}" method="GET" class="flex items-center gap-2">
                <label class="text-xs font-bold text-gray-400 uppercase ml-2">Lokasi:</label>
                <select name="branch_id" onchange="this.form.submit()" class="border-none bg-gray-50 rounded-lg text-sm font-bold text-primary py-2 pl-3 pr-8 focus:ring-0 cursor-pointer">
                    <option value="">SEMUA CABANG</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>
                            CABANG {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- BARIS 1: KARTU RINGKASAN ASET (BIG CARDS) --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        
        {{-- KAS RUPIAH (LIVE) --}}
        <div class="bg-primary rounded-xl p-6 text-white shadow-lg shadow-primary/20 relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-white/80 text-xs font-bold uppercase tracking-wider mb-1">Saldo Kas Fisik (IDR)</p>
                <h2 class="text-2xl font-bold font-mono">Rp {{ number_format($currentCash) }}</h2>
                <div class="mt-4 text-xs bg-white/20 inline-block px-2 py-1 rounded text-white border border-white/20">
                    Update: Real-time
                </div>
            </div>
            <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-4 translate-y-4 text-white">
                <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.15-1.46-3.27-3.4h1.96c.1 1.05 1.18 1.91 2.53 1.91 1.38 0 2.66-.84 2.66-2.12 0-1.27-1.03-1.63-2.66-2.02l-.54-.13c-2.48-.6-4.18-1.62-4.18-3.71 0-1.73 1.24-3.2 2.91-3.66V3h2.67v1.9c1.71.49 2.94 1.76 3.09 3.29h-1.9c-.12-.71-.93-1.66-2.43-1.66-1.21 0-2.38.74-2.38 1.95 0 1.2.98 1.63 2.59 2.01l.55.13c2.61.64 4.35 1.77 4.35 3.98.01 1.94-1.6 3.48-3.29 3.89z"></path></svg>
            </div>
        </div>

        {{-- VALUASI ASET VALAS (ESTIMASI) --}}
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 relative">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Estimasi Aset Valas</p>
                    <h2 class="text-2xl font-bold text-gray-800 font-mono">Rp {{ number_format($totalValuation) }}</h2>
                </div>
                <div class="p-2 bg-yellow-50 text-yellow-600 rounded-lg border border-yellow-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-4">Berdasarkan stok & rate rata-rata.</p>
        </div>

        {{-- TRANSAKSI HARI INI --}}
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Transaksi Hari Ini</p>
                    <h2 class="text-2xl font-bold text-gray-800">{{ number_format($todayStats['count']) }} <span class="text-sm text-gray-400 font-normal">Nota</span></h2>
                </div>
                <div class="p-2 bg-green-50 text-green-600 rounded-lg border border-green-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
            </div>
            <div class="flex gap-4 mt-4 text-xs">
                <div class="text-green-600 font-bold bg-green-50 px-2 py-1 rounded">In: {{ number_format($todayStats['sell_idr']) }}</div>
                <div class="text-red-500 font-bold bg-red-50 px-2 py-1 rounded">Out: {{ number_format($todayStats['buy_idr']) }}</div>
            </div>
        </div>

        {{-- TOMBOL AKSI CEPAT --}}
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 flex flex-col justify-center gap-3">
            <a href="{{ route('keuangan.biaya') }}" class="w-full bg-gray-50 hover:bg-gray-100 text-gray-700 font-bold py-2 px-4 rounded-lg text-sm text-center transition flex items-center justify-center gap-2 border border-gray-200">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Input Biaya
            </a>
            <a href="{{ route('keuangan.labarugi') }}" class="w-full bg-gray-50 hover:bg-gray-100 text-gray-700 font-bold py-2 px-4 rounded-lg text-sm text-center transition flex items-center justify-center gap-2 border border-gray-200">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                Cek Laba Rugi
            </a>
        </div>
    </div>

    {{-- BARIS 2: TABEL STOK & GRAFIK --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- TABEL LIVE STOCK (MODIFIKASI: HAPUS SCROLL, BIARKAN MEMANJANG) --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-fit">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Posisi Stok Valas (Live)</h3>
                    <p class="text-xs text-gray-500">Stok real-time di {{ $branchName }}</p>
                </div>
                <a href="{{ route('mutasi.harian') }}" class="text-xs font-bold text-primary hover:text-primary/80 hover:underline">Lihat Detail Mutasi &rarr;</a>
            </div>
            
            {{-- HAPUS: max-h-[400px] overflow-y-auto --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase text-xs">
                        <tr>
                            <th class="p-4">Mata Uang</th>
                            <th class="p-4 text-right">Qty Tersedia</th>
                            <th class="p-4 text-right">Avg Rate (IDR)</th>
                            <th class="p-4 text-right text-primary">Valuasi (IDR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($liveStocks as $stock)
                        <tr class="hover:bg-blue-50 transition duration-150">
                            <td class="p-4 font-bold text-gray-700">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold border border-primary/20">
                                        {{ $stock['code'] }}
                                    </span>
                                    <span>{{ $stock['name'] }}</span>
                                </div>
                            </td>
                            <td class="p-4 text-right font-mono font-bold">{{ number_format($stock['qty']) }}</td>
                            <td class="p-4 text-right font-mono text-gray-500">{{ number_format($stock['avg_rate'], 2) }}</td>
                            <td class="p-4 text-right font-mono font-bold text-primary">{{ number_format($stock['valuation']) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-gray-400 italic">Belum ada stok valas tersedia di cabang ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- GRAFIK GRAFIK (KOLOM KANAN - LEBAR 1) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col h-fit sticky top-6">
            <h3 class="font-bold text-gray-800 text-lg mb-4">Tren 7 Hari Terakhir</h3>
            <div class="flex-1 relative" style="min-height: 300px;">
                <canvas id="trendChart"></canvas>
            </div>
            <div class="mt-4 flex justify-center gap-4 text-xs font-bold">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-500"></span> Pembelian (Out)
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-primary"></span> Penjualan (In)
                </div>
            </div>
        </div>
    </div>

</div>

{{-- SCRIPT CHART JS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('trendChart').getContext('2d');
    const primaryColor = '#1e3a8a'; 

    const labels = {!! json_encode($chartData['labels']) !!};
    const buyData = {!! json_encode($chartData['buy']) !!};
    const sellData = {!! json_encode($chartData['sell']) !!};

    const trendChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pembelian (Out)',
                    data: buyData,
                    backgroundColor: '#ef4444', 
                    borderRadius: 4,
                },
                {
                    label: 'Penjualan (In)',
                    data: sellData,
                    backgroundColor: primaryColor, 
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { display: true, color: '#f3f4f6' },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value / 1000000).toFixed(0) + 'jt'; 
                        },
                        font: { size: 10 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                }
            }
        }
    });
</script>
@endsection