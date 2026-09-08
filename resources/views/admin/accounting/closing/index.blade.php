@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto pb-20">

    {{-- 1. HEADER HALAMAN --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2 border-l-4 border-primary pl-3">
                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Tutup Buku Bulanan (Monthly Closing)
            </h1>
            <p class="text-xs text-gray-500 mt-1 pl-3.5 font-bold uppercase tracking-wide">
                Proses Akhir Bulan: Hitung HPP & Penyusutan Aset
            </p>
        </div>
    </div>

    {{-- ALERT MESSAGES --}}
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-6 rounded-xl shadow-sm mb-6 flex items-start gap-4">
            <div class="bg-green-100 p-2 rounded-full text-green-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h4 class="font-bold text-lg mb-1">Sukses!</h4>
                <p class="whitespace-pre-line text-sm font-medium">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-6 rounded-xl shadow-sm mb-6 flex items-start gap-4">
            <div class="bg-red-100 p-2 rounded-full text-red-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h4 class="font-bold text-lg mb-1">Gagal Memproses:</h4>
                <ul class="list-disc pl-5 text-sm font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- KOLOM KIRI: FORM EKSEKUSI --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden relative">
                {{-- Hiasan Header --}}
                <div class="h-2 bg-primary w-full"></div>
                
                <div class="p-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <span class="bg-blue-100 text-primary w-8 h-8 flex items-center justify-center rounded-lg text-sm font-bold">1</span>
                        Pilih Periode
                    </h3>

                    <form action="{{ route('accounting.closing.process') }}" method="POST" onsubmit="return confirm('PERINGATAN: \n\nSistem akan membuat Jurnal Penyesuaian untuk HPP dan Penyusutan pada periode ini.\nPastikan semua transaksi jual/beli bulan ini sudah selesai diinput.\n\nLanjutkan proses?');">
                        @csrf
                        
                        {{-- PILIH CABANG --}}
                        <div class="mb-5">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Kantor Cabang</label>
                            <select name="branch_id" class="w-full border-gray-300 rounded-lg p-3 text-sm focus:ring-primary focus:border-primary font-bold text-gray-700 bg-gray-50 cursor-pointer">
                                <option value="">-- SEMUA CABANG (GLOBAL) --</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1 italic">*Pilih global jika ingin menutup buku seluruh cabang sekaligus.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-8">
                            {{-- BULAN --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Bulan</label>
                                <select name="month" class="w-full border-gray-300 rounded-lg p-3 text-sm focus:ring-primary focus:border-primary font-bold text-gray-700 cursor-pointer">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            {{-- TAHUN --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Tahun</label>
                                <select name="year" class="w-full border-gray-300 rounded-lg p-3 text-sm focus:ring-primary focus:border-primary font-bold text-gray-700 cursor-pointer">
                                    @foreach(range(date('Y')-2, date('Y')) as $y)
                                        <option value="{{ $y }}" {{ date('Y') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-blue-600 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-xl transition transform active:scale-95 flex items-center justify-center gap-2 group">
                            <svg class="w-5 h-5 group-hover:animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            PROSES TUTUP BUKU
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: PENJELASAN SISTEM --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 h-full">
                <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <span class="bg-yellow-100 text-yellow-600 w-8 h-8 flex items-center justify-center rounded-lg text-sm font-bold">?</span>
                    Apa yang terjadi saat tombol diklik?
                </h3>

                <div class="space-y-6">
                    {{-- 1. HPP --}}
                    <div class="flex gap-4">
                        <div class="bg-blue-50 text-primary w-12 h-12 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">1. Perhitungan HPP Otomatis</h4>
                            <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                                Sistem akan menghitung total modal barang (Valas) yang terjual bulan ini menggunakan metode rata-rata (Average Rate).
                                Kemudian sistem membuat jurnal:
                            </p>
                            <div class="mt-2 bg-gray-50 p-3 rounded border border-gray-200 text-xs font-mono font-bold text-gray-700">
                                (Dr) Beban Pokok Pendapatan (HPP) <br>
                                &nbsp;&nbsp;&nbsp;&nbsp;(Cr) Persediaan Valas
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 my-2"></div>

                    {{-- 2. DEPRESIASI --}}
                    <div class="flex gap-4">
                        <div class="bg-purple-50 text-purple-600 w-12 h-12 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">2. Penyusutan Aset Tetap</h4>
                            <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                                Sistem mengecek semua aset kantor (Kendaraan, Inventaris) yang masih aktif, lalu menghitung penurunan nilainya bulan ini.
                            </p>
                            <div class="mt-2 bg-gray-50 p-3 rounded border border-gray-200 text-xs font-mono font-bold text-gray-700">
                                (Dr) Beban Penyusutan Aset <br>
                                &nbsp;&nbsp;&nbsp;&nbsp;(Cr) Akumulasi Penyusutan
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 my-2"></div>

                    {{-- INFO --}}
                    <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg flex gap-3 items-start">
                        <svg class="w-5 h-5 text-yellow-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-xs text-yellow-800 font-medium leading-relaxed">
                            <strong>Catatan:</strong> Proses ini aman dilakukan berulang kali. Jika Anda menekan tombol "Proses" dua kali di bulan yang sama, sistem akan otomatis menghapus jurnal penyesuaian yang lama dan menggantinya dengan yang baru (Re-calculate).
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection