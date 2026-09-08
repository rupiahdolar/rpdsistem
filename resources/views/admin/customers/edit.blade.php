@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto pb-20">
    
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Transaksi (Satu Nota)</h1>
            <p class="text-sm text-gray-500">Mengedit data nasabah & seluruh item valas dalam nota ini.</p>
        </div>
        <a href="{{ route('nasabah.index') }}" class="text-gray-500 hover:text-primary font-bold text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>
    </div>

    <form action="{{ route('nasabah.update', $transaction->id) }}" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @csrf
        @method('PUT')

        {{-- INFO HEADER NOTA --}}
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div class="w-1/2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">No. Nota (Sama untuk semua item)</label>
                    <input type="text" name="no_nota" 
                           value="{{ old('no_nota', $transaction->no_nota) }}" 
                           oninput="this.value = this.value.toUpperCase()"
                           class="w-full md:w-2/3 border-gray-300 rounded p-2 text-lg font-mono font-bold text-primary focus:ring-primary focus:border-primary uppercase shadow-sm"
                           required>
                </div>
                <div class="text-right w-1/3">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Transaksi</label>
                    <input type="datetime-local" name="created_at" 
                        value="{{ old('created_at', $transaction->created_at->format('Y-m-d\TH:i')) }}" 
                        class="w-full border-gray-300 rounded p-2 text-sm font-bold text-gray-700 focus:ring-primary focus:border-primary shadow-sm text-right"
                        required>
                </div>
            </div>
        </div>

        {{-- AREA FORM DATA NASABAH (BERLAKU UNTUK SEMUA ITEM) --}}
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6" 
             x-data="{ 
                custType: '{{ $transaction->customer_type ?? 'INDIVIDUAL' }}',
                paymentMethod: '{{ $transaction->payment_method }}'
             }">
            
            {{-- DATA IDENTITAS --}}
            <div class="md:col-span-2">
                <h3 class="font-bold text-primary border-b border-gray-100 pb-2 mb-4 text-sm uppercase">Data Identitas Nasabah</h3>
                
                {{-- Pilihan Tipe Nasabah --}}
                <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 mb-4 inline-block">
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="customer_type" value="INDIVIDUAL" x-model="custType" class="w-4 h-4 text-primary focus:ring-primary">
                            <span class="text-sm font-bold text-gray-700">PERORANGAN</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="customer_type" value="CORPORATE" x-model="custType" class="w-4 h-4 text-primary focus:ring-primary">
                            <span class="text-sm font-bold text-gray-700">KORPORASI (PT/CV)</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="md:col-span-1">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">
                    <span x-text="custType == 'INDIVIDUAL' ? 'Nama Nasabah' : 'Nama Perusahaan'"></span>
                </label>
                <input type="text" name="customer_name" value="{{ old('customer_name', $transaction->customer_name) }}" class="w-full border-gray-300 rounded p-2 text-sm font-bold focus:ring-primary focus:border-primary uppercase" required>
            </div>

            <div class="md:col-span-1">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Negara Asal</label>
                <input type="text" name="customer_country" value="{{ old('customer_country', $transaction->customer_country) }}" class="w-full border-gray-300 rounded p-2 text-sm font-bold focus:ring-primary focus:border-primary uppercase" required>
            </div>

            <div class="md:col-span-1 grid grid-cols-3 gap-2">
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipe ID</label>
                    <select name="customer_id_type" class="w-full border-gray-300 rounded p-2 text-sm">
                        <template x-if="custType == 'INDIVIDUAL'">
                            <optgroup label="Perorangan">
                                <option value="KTP" {{ $transaction->customer_id_type == 'KTP' ? 'selected' : '' }}>KTP</option>
                                <option value="PASPOR" {{ $transaction->customer_id_type == 'PASPOR' ? 'selected' : '' }}>PASPOR</option>
                                <option value="SIM" {{ $transaction->customer_id_type == 'SIM' ? 'selected' : '' }}>SIM</option>
                                <option value="KITAS" {{ $transaction->customer_id_type == 'KITAS' ? 'selected' : '' }}>KITAS</option>
                                <option value="Lainnya" {{ $transaction->customer_id_type == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                            </optgroup>
                        </template>
                        <template x-if="custType == 'CORPORATE'">
                            <optgroup label="Korporasi">
                                <option value="KPmIU" {{ $transaction->customer_id_type == 'KPmIU' ? 'selected' : '' }}>KPmIU</option>
                                <option value="NIB" {{ $transaction->customer_id_type == 'NIB' ? 'selected' : '' }}>NIB</option>
                                <option value="NPWP" {{ $transaction->customer_id_type == 'NPWP' ? 'selected' : '' }}>NPWP</option>
                                <option value="SK" {{ $transaction->customer_id_type == 'SK' ? 'selected' : '' }}>SK KEMENKUMHAM</option>
                            </optgroup>
                        </template>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">No. Identitas</label>
                    <input type="text" name="customer_identity_no" value="{{ old('customer_identity_no', $transaction->customer_identity_no) }}" class="w-full border-gray-300 rounded p-2 text-sm font-mono uppercase" required>
                </div>
            </div>

            <div class="md:col-span-1 grid grid-cols-2 gap-2">
                {{-- Gender: Hide if Corporate --}}
                <div x-show="custType == 'INDIVIDUAL'">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jenis Kelamin</label>
                    <select name="customer_gender" class="w-full border-gray-300 rounded p-2 text-sm">
                        <option value="L" {{ $transaction->customer_gender == 'L' ? 'selected' : '' }}>Laki-Laki</option>
                        <option value="P" {{ $transaction->customer_gender == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
                <div :class="custType == 'CORPORATE' ? 'col-span-2' : ''">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">
                        <span x-text="custType == 'INDIVIDUAL' ? 'Tgl Lahir' : 'Tgl Pendirian'"></span>
                    </label>
                    <input type="date" name="customer_dob" 
                           value="{{ old('customer_dob', $transaction->customer_dob ? \Carbon\Carbon::parse($transaction->customer_dob)->format('Y-m-d') : '') }}" 
                           class="w-full border-gray-300 rounded p-2 text-sm text-gray-600">
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Alamat Lengkap</label>
                <input type="text" name="customer_address" value="{{ old('customer_address', $transaction->customer_address) }}" class="w-full border-gray-300 rounded p-2 text-sm uppercase" required>
            </div>

            {{-- DATA PENGURUS (Khusus Korporasi) --}}
            <div class="md:col-span-2 bg-gray-100 p-4 rounded border border-gray-300" x-show="custType == 'CORPORATE'" style="display: none;">
                <h4 class="font-bold text-xs text-gray-800 mb-3 border-b border-gray-300 pb-2 uppercase">Data Pengurus / Kuasa (PIC)</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nama Pengurus</label>
                        <input type="text" name="representative_name" value="{{ old('representative_name', $transaction->representative_name) }}" class="w-full border-gray-300 rounded p-2 text-sm font-bold uppercase">
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tipe ID</label>
                            <select name="representative_id_type" class="w-full border-gray-300 rounded p-2 text-sm">
                                <option value="KTP" {{ $transaction->representative_id_type == 'KTP' ? 'selected' : '' }}>KTP</option>
                                <option value="PASPOR" {{ $transaction->representative_id_type == 'PASPOR' ? 'selected' : '' }}>PASPOR</option>
                                <option value="SIM" {{ $transaction->representative_id_type == 'SIM' ? 'selected' : '' }}>SIM</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">No. ID Pengurus</label>
                            <input type="text" name="representative_id_no" value="{{ old('representative_id_no', $transaction->representative_id_no) }}" class="w-full border-gray-300 rounded p-2 text-sm font-bold uppercase">
                        </div>
                    </div>
                </div>
            </div>

            {{-- APU PPT --}}
            <div class="md:col-span-2 mt-2">
                <h3 class="font-bold text-primary border-b border-gray-100 pb-2 mb-4 text-sm uppercase">Data Pekerjaan & APU-PPT</h3>
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">
                        <span x-text="custType == 'INDIVIDUAL' ? 'Pekerjaan' : 'Bidang Usaha'"></span>
                    </label>
                    <input type="text" name="customer_job" value="{{ old('customer_job', $transaction->customer_job) }}" class="w-full border-gray-300 rounded p-2 text-sm uppercase" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sumber Dana</label>
                    <input type="text" name="source_of_funds" value="{{ old('source_of_funds', $transaction->source_of_funds) }}" class="w-full border-gray-300 rounded p-2 text-sm uppercase" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tujuan Transaksi</label>
                    <input type="text" name="transaction_purpose" value="{{ old('transaction_purpose', $transaction->transaction_purpose) }}" class="w-full border-gray-300 rounded p-2 text-sm uppercase" required>
                </div>
            </div>

            {{-- DATA KEUANGAN (LOOPING ITEMS) --}}
            <div class="md:col-span-2 mt-6">
                <h3 class="font-bold text-primary border-b border-gray-100 pb-2 mb-4 text-sm uppercase">Data Keuangan (Item Valas)</h3>
            </div>
            
            {{-- TIPE TRANSAKSI (Dikembalikan seperti asli) --}}
            <div class="md:col-span-2 mb-2">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipe Transaksi (Berlaku Semua Item)</label>
                <select name="type" class="w-full md:w-1/3 border-gray-300 rounded p-2 text-sm font-bold">
                    <option value="buy" {{ $transaction->type == 'buy' ? 'selected' : '' }}>BELI (Masuk)</option>
                    <option value="sell" {{ $transaction->type == 'sell' ? 'selected' : '' }}>JUAL (Keluar)</option>
                </select>
            </div>

            <div class="md:col-span-2 space-y-3">
                
                {{-- LOOPING TRANSAKSI (MULTIPLE ROWS) --}}
                @foreach($transactions as $index => $item)
                <div class="bg-yellow-50 p-4 rounded border border-yellow-200 relative" 
                     x-data="{ 
                        qty: {{ $item->amount_foreign }}, 
                        rate: {{ $item->rate }} 
                     }">
                    
                    {{-- ID Hidden untuk identifikasi saat update --}}
                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        
                        {{-- Mata Uang --}}
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Mata Uang</label>
                            <select name="items[{{ $index }}][currency]" class="w-full border-gray-300 rounded p-2 text-sm font-bold bg-white">
                                @foreach($currencies as $curr)
                                    <option value="{{ $curr->code }}" {{ $item->currency == $curr->code ? 'selected' : '' }}>{{ $curr->code }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Jumlah --}}
                        <div class="md:col-span-3">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Jumlah Asing</label>
                            <input type="number" step="0.01" 
                                   name="items[{{ $index }}][amount_foreign]" 
                                   x-model="qty"
                                   class="w-full border-gray-300 rounded p-2 text-sm font-bold text-right" required>
                        </div>

                        {{-- Rate --}}
                        <div class="md:col-span-3">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Rate (Kurs)</label>
                            <input type="number" step="1" 
                                   name="items[{{ $index }}][rate]" 
                                   x-model="rate"
                                   class="w-full border-gray-300 rounded p-2 text-sm font-bold text-right" required>
                        </div>

                        {{-- Total Live Calculation --}}
                        <div class="md:col-span-4 text-right">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Estimasi IDR</label>
                            <div class="text-primary font-bold font-mono text-lg bg-white/50 px-2 py-1 rounded border border-transparent">
                                Rp <span x-text="new Intl.NumberFormat('id-ID').format(qty * rate)"></span>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

            </div>

            <div class="md:col-span-2 mt-4 border-t border-gray-100 pt-4">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Metode Pembayaran (Berlaku Semua Item)</label>
                <div class="flex flex-col md:flex-row gap-4">
                    <select name="payment_method" x-model="paymentMethod" class="border-gray-300 rounded p-2 text-sm font-bold w-full md:w-1/3">
                        <option value="CASH">TUNAI (CASH)</option>
                        <option value="TRANSFER">TRANSFER BANK</option>
                    </select>

                    <select name="bank_account_id" x-show="paymentMethod == 'TRANSFER'" class="border-gray-300 rounded p-2 text-sm w-full md:w-2/3">
                        <option value="">-- PILIH BANK --</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}" {{ $transaction->bank_account_id == $bank->id ? 'selected' : '' }}>{{ $bank->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

        </div>

        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <a href="{{ route('nasabah.index') }}" class="px-4 py-2 border rounded text-gray-600 font-bold text-sm hover:bg-gray-100">Batal</a>
            <button type="submit" class="px-6 py-2 bg-primary text-white font-bold rounded text-sm hover:opacity-90 shadow">
                SIMPAN SEMUA PERUBAHAN
            </button>
        </div>
    </form>
</div>
@endsection