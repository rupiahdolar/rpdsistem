@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-primary flex items-center gap-2">
                <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Set Modal Awal Harian
            </h1>
            <p class="text-gray-500 text-sm">Input Saldo Kas Rupiah & Stok Awal Valas untuk memulai operasional.</p>
        </div>
    </div>

    {{-- BAGIAN 1: FILTER CABANG SAJA (Lebih Bersih) --}}
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-6">
        <form action="{{ route('admin.capital.index') }}" method="GET">
            {{-- Pertahankan tanggal saat ganti cabang agar tidak reset ke hari ini --}}
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            
            <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Pilih Kantor Cabang</label>
            <div class="relative">
                <select name="branch_id" class="block w-full pl-4 pr-10 py-3 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-lg shadow-sm" onchange="this.form.submit()">
                    <option value="" disabled {{ !$selectedBranch ? 'selected' : '' }}>-- Silakan Pilih Cabang --</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $selectedBranch == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </form>
    </div>

    @if($selectedBranch)
    <form action="{{ route('admin.capital.store') }}" method="POST">
        @csrf
        {{-- Input Hidden Cabang (Wajib ada untuk dikirim ke Controller) --}}
        <input type="hidden" name="branch_id" value="{{ $selectedBranch }}">

        {{-- ALERT JIKA DATA SUDAH ADA (MODE UPDATE) --}}
        @if($existingCapital)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 shadow-sm rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-bold text-yellow-800">DATA SUDAH TERSEDIA</h3>
                        <p class="text-xs text-yellow-700 mt-1">
                            Anda sedang melihat data Modal Awal tanggal <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d-m-Y') }}</strong>.
                            <br>Menyimpan data ini akan <strong>MENG-UPDATE (MENIMPA)</strong> data lama dan memperbarui jurnal akuntansi.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- KOLOM KIRI: TANGGAL & RUPIAH --}}
            <div class="lg:col-span-1 space-y-6">
                
                {{-- BAGIAN BARU: INPUT TANGGAL (PINDAH KE SINI) --}}
                <div class="bg-white rounded-xl shadow p-6 border border-gray-200">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal Modal Awal</label>
                    <input type="date" name="date" id="datePicker"
                           value="{{ $selectedDate }}" 
                           class="w-full border-gray-300 rounded-lg p-2.5 font-bold text-gray-800 focus:ring-primary focus:border-primary shadow-sm"
                           required>
                    <p class="text-[10px] text-gray-400 mt-2 italic">
                        *Mengganti tanggal akan memuat ulang halaman untuk pengecekan data.
                    </p>
                </div>

                {{-- MODAL RUPIAH --}}
                <div class="bg-white rounded-xl shadow-lg border-t-4 border-secondary p-6 sticky top-6">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span class="bg-green-100 text-green-700 p-1 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></span>
                        1. Modal Rupiah (IDR)
                    </h3>
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-600 mb-1">Saldo Awal Brankas</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-500 font-bold">Rp</span>
                            <input type="number" name="amount_idr" 
                                   value="{{ $existingCapital ? $existingCapital->amount : 0 }}" 
                                   class="w-full border-gray-300 rounded-lg pl-10 p-2.5 font-mono font-bold text-lg text-gray-800 focus:ring-secondary focus:border-secondary" 
                                   placeholder="0">
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 mt-4">
                        <button type="submit" class="w-full {{ $existingCapital ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-primary hover:bg-blue-900' }} text-white font-bold py-3 rounded-lg transition shadow-lg transform hover:-translate-y-1 flex justify-center items-center gap-2">
                            @if($existingCapital)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                UPDATE DATA MODAL
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                SIMPAN DATA BARU
                            @endif
                        </button>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: STOK VALAS --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="font-bold text-gray-700 flex items-center gap-2">
                            <span class="bg-blue-100 text-blue-700 p-1 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg></span>
                            2. Stok Awal Valas (Fisik)
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-100 text-gray-600 text-xs uppercase border-b border-gray-200">
                                <tr>
                                    <th class="p-3 w-20 text-center">Mata Uang</th>
                                    <th class="p-3 w-40 text-center">Qty (Lembar)</th>
                                    <th class="p-3 w-40 text-center">Rate Modal</th>
                                    <th class="p-3 text-right">Total Valuasi (IDR)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($currencies as $curr)
                                    @php
                                        $stockData = $existingStocks->get($curr->code);
                                        $qty = $stockData ? $stockData->amount : 0;
                                        $rate = $stockData ? $stockData->average_rate : 0;
                                    @endphp
                                <tr class="hover:bg-blue-50/30 transition group">
                                    <td class="p-3 text-center font-bold bg-gray-50 text-gray-800 border-r group-hover:bg-blue-100/50 transition">
                                        {{ $curr->code }}
                                    </td>
                                    <td class="p-3">
                                        <input type="number" step="0.01" 
                                               name="stocks[{{ $curr->code }}][qty]" 
                                               value="{{ $qty }}" 
                                               class="w-full text-center border-gray-300 rounded focus:ring-secondary focus:border-secondary font-mono input-qty bg-white focus:bg-white"
                                               data-code="{{ $curr->code }}">
                                    </td>
                                    <td class="p-3">
                                        <input type="number" step="0.01" 
                                               name="stocks[{{ $curr->code }}][rate]" 
                                               value="{{ $rate }}" 
                                               class="w-full text-center border-gray-300 rounded focus:ring-secondary focus:border-secondary font-mono input-rate bg-white focus:bg-white"
                                               data-code="{{ $curr->code }}">
                                    </td>
                                    <td class="p-3 text-right font-bold text-gray-600 font-mono total-row" id="total-{{ $curr->code }}">
                                        Rp {{ number_format($qty * $rate, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </form>
    
    {{-- SCRIPT PENTING: RELOAD SAAT GANTI TANGGAL & HITUNG TOTAL --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // 1. LOGIKA GANTI TANGGAL -> RELOAD HALAMAN
            // Ini kunci agar alert kuning berfungsi!
            const datePicker = document.getElementById('datePicker');
            const branchId = "{{ $selectedBranch }}";

            datePicker.addEventListener('change', function() {
                const newDate = this.value;
                if(branchId && newDate) {
                    // Tampilkan indikator loading (opsional, biar user tau sedang proses)
                    this.classList.add('opacity-50', 'cursor-wait');
                    // Reload halaman dengan parameter tanggal baru
                    window.location.href = "{{ route('admin.capital.index') }}" + "?branch_id=" + branchId + "&date=" + newDate;
                }
            });

            // 2. LOGIKA HITUNG VALAS (SAMA SEPERTI SEBELUMNYA)
            const inputs = document.querySelectorAll('.input-qty, .input-rate');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    const code = this.dataset.code;
                    const qtyInput = document.querySelector(`input[name="stocks[${code}][qty]"]`);
                    const rateInput = document.querySelector(`input[name="stocks[${code}][rate]"]`);
                    const totalEl = document.getElementById(`total-${code}`);
                    
                    const qty = parseFloat(qtyInput.value) || 0;
                    const rate = parseFloat(rateInput.value) || 0;
                    const total = qty * rate;
                    
                    totalEl.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
                });
            });
        });
    </script>

    @else
        {{-- Tampilan Kosong Jika Belum Pilih Cabang --}}
        <div class="flex flex-col items-center justify-center py-20 bg-white rounded-xl border-2 border-dashed border-gray-200">
            <div class="bg-blue-50 p-4 rounded-full mb-4">
                <svg class="w-10 h-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-700">Pilih Kantor Cabang</h3>
            <p class="text-gray-400 text-sm mt-1 max-w-sm text-center">Silakan pilih kantor cabang pada menu di atas untuk mulai mengatur Modal Awal & Stok Valas.</p>
        </div>
    @endif

</div>
@endsection