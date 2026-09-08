@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto pb-20" x-data="journalHandler()">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2 border-l-4 border-primary pl-3">
                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Buat Jurnal Umum
            </h1>
            <p class="text-xs text-gray-500 mt-1 pl-3.5">Input jurnal manual untuk penyesuaian atau koreksi akuntansi.</p>
        </div>
        
        <a href="{{ route('accounting.journals.index') }}" class="text-gray-500 hover:text-primary font-bold text-xs uppercase tracking-wider flex items-center gap-1 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Riwayat
        </a>
    </div>

    {{-- ALERT ERROR --}}
    @if($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-bold mb-1">Terjadi Kesalahan:</p>
            <ul class="list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('accounting.journals.store') }}" method="POST" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
        @csrf
        
        {{-- INFO HEADER TRANSAKSI --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Tanggal Transaksi</label>
                <input type="date" name="date" value="{{ date('Y-m-d') }}" class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-primary focus:border-primary" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Keterangan / Deskripsi</label>
                <input type="text" name="description" class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-primary focus:border-primary uppercase font-medium" placeholder="Contoh: SETORAN MODAL AWAL" required>
            </div>
        </div>

        {{-- TABEL INPUT DETAIL --}}
        <div class="border border-gray-200 rounded-lg overflow-hidden mb-6">
            <table class="w-full text-sm text-left">
                {{-- Header Tabel Biru --}}
                <thead class="bg-primary text-white font-bold uppercase text-xs">
                    <tr>
                        <th class="p-3 w-5/12 border-r border-white/20">Nama Akun (COA)</th>
                        <th class="p-3 text-right border-r border-white/20">Debit (Rp)</th>
                        <th class="p-3 text-right border-r border-white/20">Kredit (Rp)</th>
                        <th class="p-3 w-10 text-center">#</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(row, index) in rows" :key="index">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-2 border-r border-gray-100">
                                <select :name="'details['+index+'][account_id]'" x-model="row.account_id" class="w-full border border-gray-300 rounded p-2 text-sm font-bold text-gray-700 focus:border-primary focus:ring-primary" required>
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-2 border-r border-gray-100">
                                <input type="number" :name="'details['+index+'][debit]'" x-model="row.debit" @input="calculateTotals()" class="w-full border border-gray-300 rounded p-2 text-sm text-right font-mono focus:border-primary focus:ring-primary" placeholder="0">
                            </td>
                            <td class="p-2 border-r border-gray-100">
                                <input type="number" :name="'details['+index+'][credit]'" x-model="row.credit" @input="calculateTotals()" class="w-full border border-gray-300 rounded p-2 text-sm text-right font-mono focus:border-primary focus:ring-primary" placeholder="0">
                            </td>
                            <td class="p-2 text-center bg-gray-50">
                                <button type="button" @click="removeRow(index)" class="text-gray-400 hover:text-red-600 font-bold text-lg transition" title="Hapus Baris">
                                    &times;
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-gray-50 font-bold border-t border-gray-200">
                    <tr>
                        <td class="p-3 text-right text-gray-600 uppercase text-xs">TOTAL JURNAL:</td>
                        <td class="p-3 text-right font-mono text-sm border-r border-gray-200" :class="totalDebit == totalCredit ? 'text-green-700' : 'text-red-600'">
                            <span x-text="formatRupiah(totalDebit)"></span>
                        </td>
                        <td class="p-3 text-right font-mono text-sm border-r border-gray-200" :class="totalDebit == totalCredit ? 'text-green-700' : 'text-red-600'">
                            <span x-text="formatRupiah(totalCredit)"></span>
                        </td>
                        <td class="bg-gray-200"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- TOMBOL AKSI & INDIKATOR BALANCE --}}
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <button type="button" @click="addRow()" class="w-full md:w-auto bg-gray-100 text-gray-700 border border-gray-300 px-4 py-2.5 rounded-lg font-bold hover:bg-gray-200 text-xs uppercase flex items-center justify-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                TAMBAH BARIS
            </button>

            <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto items-center">
                 {{-- Indikator Balance --}}
                <div x-show="totalDebit > 0 && totalDebit == totalCredit" class="flex items-center text-green-700 font-bold text-xs bg-green-50 px-4 py-2.5 rounded-lg border border-green-200 w-full md:w-auto justify-center uppercase tracking-wide">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    DATA SEIMBANG (BALANCE)
                </div>
                <div x-show="totalDebit != totalCredit" class="flex items-center text-red-600 font-bold text-xs bg-red-50 px-4 py-2.5 rounded-lg border border-red-200 w-full md:w-auto justify-center uppercase tracking-wide animate-pulse">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    TIDAK SEIMBANG (Selisih: <span x-text="formatRupiah(Math.abs(totalDebit - totalCredit))" class="ml-1"></span>)
                </div>

                {{-- TOMBOL SIMPAN BIRU --}}
                <button type="submit" :disabled="totalDebit != totalCredit || totalDebit == 0" class="w-full md:w-auto bg-primary text-white px-6 py-2.5 rounded-lg font-bold hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg transition text-xs uppercase tracking-wider transform active:scale-95">
                    SIMPAN JURNAL
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function journalHandler() {
        return {
            rows: [
                { account_id: '', debit: 0, credit: 0 },
                { account_id: '', debit: 0, credit: 0 }
            ],
            totalDebit: 0,
            totalCredit: 0,

            addRow() {
                this.rows.push({ account_id: '', debit: 0, credit: 0 });
            },
            removeRow(index) {
                if (this.rows.length > 2) {
                    this.rows.splice(index, 1);
                    this.calculateTotals();
                } else {
                    alert("Jurnal minimal terdiri dari 2 baris (Debit & Kredit).");
                }
            },
            calculateTotals() {
                this.totalDebit = this.rows.reduce((sum, row) => sum + (parseFloat(row.debit) || 0), 0);
                this.totalCredit = this.rows.reduce((sum, row) => sum + (parseFloat(row.credit) || 0), 0);
            },
            formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
            }
        }
    }
</script>
@endsection