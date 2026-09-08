@extends('layouts.app')

@section('content')
<div class="flex flex-col h-full pb-20" x-data="{ modalOpen: false, editModalOpen: false, editData: {} }">

    {{-- HEADER HALAMAN --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 v5m-4 0h4"></path></svg>
                    Aset Tetap & Inventaris
                </h2>
                <p class="text-xs text-gray-500 mt-1 uppercase font-bold tracking-wide">
                    Manajemen aset perusahaan dan perhitungan penyusutan otomatis.
                </p>
            </div>
            
            <button @click="modalOpen = true" class="bg-primary hover:opacity-90 text-white px-5 py-2.5 rounded-lg shadow-lg transition flex items-center gap-2 h-10 text-xs font-bold uppercase tracking-wider transform active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Tambah Aset
            </button>
        </div>
    </div>

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span class="text-sm font-bold">{{ session('success') }}</span>
        </div>
        <button @click="show = false" class="text-green-700 hover:text-green-900 font-bold text-lg">&times;</button>
    </div>
    @endif

    {{-- TABEL DATA --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-white text-primary font-bold text-xs uppercase border-b-2 border-primary tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Nama Aset</th>
                        <th class="px-6 py-4">Tgl Beli</th>
                        <th class="px-6 py-4 text-right">Harga Perolehan</th>
                        <th class="px-6 py-4 text-center">Umur (Bln)</th>
                        <th class="px-6 py-4 text-right">Penyusutan/Bulan</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($assets as $asset)
                    <tr class="hover:bg-blue-50 transition duration-150">
                        <td class="px-6 py-4 border-r border-gray-100">
                            <div class="font-bold text-gray-800 uppercase">{{ $asset->name }}</div>
                            <div class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $asset->serial_number ?? '-' }}</div>
                            <span class="text-[9px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded border border-gray-200 mt-1 inline-block uppercase font-bold tracking-wider">
                                {{ $asset->branch->name ?? 'PUSAT' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 border-r border-gray-100 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($asset->purchase_date)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono font-bold text-gray-900 border-r border-gray-100">
                            Rp {{ number_format($asset->purchase_cost) }}
                        </td>
                        <td class="px-6 py-4 text-center border-r border-gray-100 font-bold text-gray-600">
                            {{ $asset->useful_life_months }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-gray-900 border-r border-gray-100 font-bold bg-primary/5">
                            @php
                                $residual = $asset->residual_value ?? 0;
                                $dep = ($asset->purchase_cost - $residual) / $asset->useful_life_months;
                            @endphp
                            Rp {{ number_format($dep) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center items-center gap-2">
                                {{-- TOMBOL EDIT --}}
                                <button @click="editModalOpen = true; editData = {
                                    id: '{{ $asset->id }}',
                                    name: '{{ $asset->name }}',
                                    serial_number: '{{ $asset->serial_number }}',
                                    purchase_date: '{{ $asset->purchase_date }}',
                                    purchase_cost: '{{ $asset->purchase_cost }}',
                                    useful_life_months: '{{ $asset->useful_life_months }}',
                                    branch_id: '{{ $asset->branch_id }}'
                                }" class="text-blue-500 hover:text-white hover:bg-blue-600 p-2 rounded-lg transition border border-blue-200" title="Edit Aset">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>

                                {{-- TOMBOL HAPUS --}}
                                <form action="{{ route('accounting.assets.destroy', $asset->id) }}" method="POST" onsubmit="return confirm('Hapus aset ini? History penyusutan juga akan terhapus.');">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-white hover:bg-red-600 p-2 rounded-lg transition border border-red-200" title="Hapus Aset">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-400 bg-gray-50 italic">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                <p class="text-sm">Belum ada data aset tercatat.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL TAMBAH ASET --}}
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" style="display: none;">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden" @click.away="modalOpen = false">
            <div class="bg-primary p-4 flex justify-between items-center">
                <h3 class="font-bold text-white text-sm uppercase tracking-wide">Catat Aset Baru</h3>
                <button @click="modalOpen = false" class="text-white text-lg font-bold">&times;</button>
            </div>
            
            <form action="{{ route('accounting.assets.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nama Aset</label>
                    <input type="text" name="name" class="w-full border-gray-300 rounded-lg p-2.5 text-sm uppercase font-bold focus:ring-primary focus:border-primary" placeholder="Cth: MOTOR HONDA BEAT" required>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Beli</label>
                        <input type="date" name="purchase_date" class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-primary focus:border-primary" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Lokasi Aset</label>
                        <select name="branch_id" class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-primary focus:border-primary" required>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Harga Beli (Rp)</label>
                        <input type="number" name="purchase_cost" class="w-full border-gray-300 rounded-lg p-2.5 text-sm font-mono text-right focus:ring-primary focus:border-primary" placeholder="0" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Umur (Bulan)</label>
                        <input type="number" name="useful_life_months" class="w-full border-gray-300 rounded-lg p-2.5 text-sm text-center focus:ring-primary focus:border-primary" placeholder="Cth: 48" required>
                    </div>
                </div>

                {{-- TAMBAHAN: DROPDOWN SUMBER DANA --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Sumber Dana (Potong Dari)</label>
                    <select name="payment_account_id" class="w-full border-gray-300 rounded-lg p-2.5 text-sm font-bold focus:ring-primary focus:border-primary" required>
                        <option value="">-- Pilih Akun Kas / Bank --</option>
                        @foreach($paymentAccounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-4 border-t border-gray-100 mt-2">
                    <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:opacity-90 transition shadow-lg text-xs uppercase tracking-widest">
                        Simpan & Hitung Penyusutan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT ASET --}}
    <div x-show="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" style="display: none;">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden" @click.away="editModalOpen = false">
            <div class="bg-blue-600 p-4 flex justify-between items-center">
                <h3 class="font-bold text-white text-sm uppercase tracking-wide">Edit Data Aset</h3>
                <button @click="editModalOpen = false" class="text-white text-lg font-bold">&times;</button>
            </div>
            
            <form :action="'/admin/accounting/assets/' + editData.id" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nama Aset</label>
                    <input type="text" name="name" x-model="editData.name" class="w-full border-gray-300 rounded-lg p-2.5 text-sm uppercase font-bold focus:ring-primary focus:border-primary" required>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Beli</label>
                        <input type="date" name="purchase_date" x-model="editData.purchase_date" class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-primary focus:border-primary" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Lokasi Aset</label>
                        <select name="branch_id" x-model="editData.branch_id" class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-primary focus:border-primary" required>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Harga Beli (Rp)</label>
                        <input type="number" name="purchase_cost" x-model="editData.purchase_cost" class="w-full border-gray-300 rounded-lg p-2.5 text-sm font-mono text-right focus:ring-primary focus:border-primary" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Umur (Bulan)</label>
                        <input type="number" name="useful_life_months" x-model="editData.useful_life_months" class="w-full border-gray-300 rounded-lg p-2.5 text-sm text-center focus:ring-primary focus:border-primary" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Sumber Dana Pembayaran</label>
                    <select name="payment_account_id" class="w-full border-gray-300 rounded-lg p-2.5 text-sm font-bold focus:ring-primary focus:border-primary" required>
                        <option value="">-- Pilih Akun Kas / Bank --</option>
                        @foreach($paymentAccounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-4 border-t border-gray-100 mt-2">
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:opacity-90 transition shadow-lg text-xs uppercase tracking-widest">
                        Update Aset & Jurnal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection