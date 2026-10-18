@extends('layouts.app')

@section('content')
<div class="flex flex-col h-full pb-20">

    {{-- HEADER HALAMAN & FILTER --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    Manajemen Utang & Pinjaman
                </h2>
                <p class="text-xs text-gray-500 mt-1">Pusat pencatatan penerimaan pinjaman baru serta cicilan/pelunasan utang.</p>
            </div>
            
            <div class="flex gap-2">
                {{-- TOMBOL TERIMA PINJAMAN BARU --}}
                <button onclick="document.getElementById('modalLoanReceipt').classList.remove('hidden')" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow transition flex items-center gap-2 h-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span class="text-xs font-bold uppercase">TERIMA PINJAMAN</span>
                </button>

                {{-- TOMBOL PEMBAYARAN UTANG --}}
                <button onclick="document.getElementById('modalDebtPayment').classList.remove('hidden')" 
                        class="bg-primary hover:opacity-90 text-white px-4 py-2 rounded-lg shadow transition flex items-center gap-2 h-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span class="text-xs font-bold uppercase">TAMBAH PEMBAYARAN</span>
                </button>
            </div>
        </div>

        {{-- FILTER BARIS KEDUA --}}
        <div class="mt-6 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-4">
            <form action="{{ route('accounting.debt_payments.index') }}" method="GET" class="flex flex-wrap items-end gap-3 w-full md:w-auto">
                @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
                <div class="w-full md:w-auto">
                    <select name="branch_id" class="w-full border-gray-300 rounded-lg text-xs font-bold text-gray-700 h-10 focus:ring-primary focus:border-primary cursor-pointer" onchange="this.form.submit()">
                        <option value="">-- SEMUA CABANG --</option>
                        @foreach(\App\Models\Branch::all() as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="w-1/3 md:w-auto">
                    <select name="month" class="w-full border-gray-300 rounded-lg text-xs font-bold text-gray-700 h-10 focus:ring-primary focus:border-primary cursor-pointer" onchange="this.form.submit()">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-1/4 md:w-auto">
                    <select name="year" class="w-full border-gray-300 rounded-lg text-xs font-bold text-gray-700 h-10 focus:ring-primary focus:border-primary cursor-pointer" onchange="this.form.submit()">
                        @foreach(range(date('Y')-2, date('Y')+2) as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            {{-- SUMMARY CARDS --}}
            <div class="flex gap-3">
                <div class="bg-green-50 px-4 py-2 rounded-lg border border-green-100 text-right">
                    <span class="text-[10px] text-green-600 font-bold block uppercase tracking-wider">TOTAL PINJAMAN MASUK</span>
                    <span class="text-base font-mono font-bold text-green-700">Rp {{ number_format($totalLoans ?? 0) }}</span>
                </div>
                <div class="bg-blue-50 px-4 py-2 rounded-lg border border-blue-100 text-right">
                    <span class="text-[10px] text-blue-500 font-bold block uppercase tracking-wider">TOTAL PEMBAYARAN</span>
                    <span class="text-base font-mono font-bold text-blue-700">Rp {{ number_format($totalAmount ?? 0) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- =================================================================== --}}
    {{-- BAGIAN 1: TABEL PENERIMAAN PINJAMAN BARU --}}
    {{-- =================================================================== --}}
    <div class="mb-8">
        <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wider mb-3 flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-green-600"></span> Riwayat Penerimaan Pinjaman Baru
        </h3>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
            <table class="w-full text-sm text-left min-w-[700px]">
                <thead class="bg-gray-50 text-green-700 border-b-2 border-green-600 uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-3 whitespace-nowrap">Tanggal</th>
                        <th class="px-6 py-3">Akun Utang (Kredit)</th>
                        <th class="px-6 py-3">Kas / Bank Tujuan Masuk</th>
                        <th class="px-6 py-3">Keterangan</th>
                        <th class="px-6 py-3 text-right">Jumlah (IDR)</th>
                        <th class="px-6 py-3 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($loans as $loan)
                    <tr class="hover:bg-green-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-mono font-bold text-xs">
                            {{ \Carbon\Carbon::parse($loan->date)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-800">
                            {{ $loan->liabilityAccount->code ?? '' }} - {{ $loan->liabilityAccount->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-gray-700 text-xs font-bold">
                            {{ $loan->cashAccount->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-gray-600 italic text-xs">
                            {{ $loan->description ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono font-bold text-green-700 whitespace-nowrap">
                            Rp {{ number_format($loan->amount) }}
                        </td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <form action="{{ route('accounting.loan_receipts.destroy', $loan->id) }}" method="POST" onsubmit="return confirm('Hapus data penerimaan pinjaman ini? Jurnal terkait akan otomatis dibatalkan.');" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-600 bg-red-50 p-1.5 rounded hover:bg-red-100 transition shrink-0" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="p-6 text-center text-gray-400 italic text-xs">Belum ada penerimaan pinjaman baru pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- =================================================================== --}}
    {{-- BAGIAN 2: TABEL PEMBAYARAN / CICILAN UTANG --}}
    {{-- =================================================================== --}}
    <div>
        <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wider mb-3 flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-primary"></span> Riwayat Pembayaran / Cicilan Utang
        </h3>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
            <table class="w-full text-sm text-left min-w-[700px]">
                <thead class="bg-white text-primary border-b-2 border-primary uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4 whitespace-nowrap">Tanggal</th>
                        <th class="px-6 py-4">Akun Utang (yang Dibayar)</th>
                        <th class="px-6 py-4">Sumber Kas / Bank</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4 text-right">Pokok (IDR)</th>
                        <th class="px-6 py-4 text-right">Bunga (IDR)</th>
                        <th class="px-6 py-4 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $pay)
                    <tr class="hover:bg-blue-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-mono font-bold text-xs">
                            {{ \Carbon\Carbon::parse($pay->date)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-800">
                            {{ $pay->liabilityAccount->code ?? '' }} - {{ $pay->liabilityAccount->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-gray-700 text-xs font-bold">
                            {{ $pay->paymentAccount->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-gray-600 italic text-xs">
                            {{ $pay->description ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono font-bold text-gray-900 whitespace-nowrap">
                            Rp {{ number_format($pay->amount) }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono font-bold text-orange-600 whitespace-nowrap">
                            Rp {{ number_format($pay->interest_amount) }}
                        </td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <form action="{{ route('accounting.debt_payments.destroy', $pay->id) }}" method="POST" onsubmit="return confirm('Hapus data pembayaran utang ini? Jurnal terkait akan otomatis dibatalkan.');" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-600 bg-red-50 p-1.5 rounded hover:bg-red-100 transition shrink-0" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="p-8 text-center text-gray-400">Belum ada data pembayaran utang pada periode ini.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 font-bold border-t border-gray-200">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-right uppercase text-xs text-gray-500 tracking-wider">Total Periode Ini</td>
                        <td class="px-6 py-4 text-right font-mono text-primary">Rp {{ number_format($totalAmount ?? 0) }}</td>
                        <td class="px-6 py-4 text-right font-mono text-orange-600">Rp {{ number_format($totalInterest ?? 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>


    {{-- =================================================================== --}}
    {{-- MODAL 1: FORM TERIMA PINJAMAN BARU --}}
    {{-- =================================================================== --}}
    <div id="modalLoanReceipt" class="fixed inset-0 bg-black/50 z-[99] hidden flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden transform transition-all">
            <div class="bg-green-600 p-4 flex justify-between items-center">
                <h3 class="font-bold text-white text-sm uppercase tracking-wide">Form Penerimaan Pinjaman Baru</h3>
                <button onclick="document.getElementById('modalLoanReceipt').classList.add('hidden')" class="text-white hover:text-gray-200 text-lg">&times;</button>
            </div>
            
            <form action="{{ route('accounting.loan_receipts.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Terima</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-green-600 focus:border-green-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Pilih Akun Utang (Liability)</label>
                    <select name="liability_account_id" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm font-bold text-gray-700 focus:ring-green-600 focus:border-green-600">
                        <option value="" disabled selected>-- PILIH AKUN UTANG --</option>
                        @foreach($liabilityAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-gray-400 mt-1 block">Utang akan bertambah di sisi Kredit.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Kas / Bank Tujuan Masuk Dana</label>
                    <select name="cash_account_id" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm font-bold text-gray-700 focus:ring-green-600 focus:border-green-600">
                        <option value="" disabled selected>-- PILIH KAS / BANK --</option>
                        @foreach($cashAccounts as $cash)
                            <option value="{{ $cash->id }}">{{ $cash->code }} - {{ $cash->name }}</option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-gray-400 mt-1 block">Kas/Bank akan bertambah di sisi Debit.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Jumlah Pinjaman (Rp)</label>
                    <input type="number" name="amount" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm text-right focus:ring-green-600 focus:border-green-600 font-mono font-bold" placeholder="0">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan / Catatan</label>
                    <textarea name="description" rows="2" class="w-full border-gray-300 rounded-lg p-2.5 text-sm uppercase focus:ring-green-600 focus:border-green-600" placeholder="CONTOH: PINJAMAN MODAL USAHA DARI PT XYZ"></textarea>
                </div>

                <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700 transition text-sm uppercase shadow-lg transform active:scale-95">SIMPAN & JURNAL OTOMATIS</button>
            </form>
        </div>
    </div>


    {{-- =================================================================== --}}
    {{-- MODAL 2: FORM PEMBAYARAN / CICILAN UTANG --}}
    {{-- =================================================================== --}}
    <div id="modalDebtPayment" class="fixed inset-0 bg-black/50 z-[99] hidden flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden transform transition-all">
            <div class="bg-primary p-4 flex justify-between items-center">
                <h3 class="font-bold text-white text-sm uppercase tracking-wide">Form Pembayaran Utang</h3>
                <button onclick="document.getElementById('modalDebtPayment').classList.add('hidden')" class="text-white hover:text-gray-200 text-lg">&times;</button>
            </div>
            
            <form action="{{ route('accounting.debt_payments.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Pembayaran</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Pilih Akun Utang yang Dibayar (Liability)</label>
                    <select name="liability_account_id" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm font-bold text-gray-700 focus:ring-primary focus:border-primary">
                        <option value="" disabled selected>-- PILIH AKUN UTANG --</option>
                        @foreach($liabilityAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-gray-400 mt-1 block">Contoh: Utang Usaha, Utang Bank, Utang Lainnya.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Sumber Kas / Bank Pembayaran</label>
                    <select name="payment_account_id" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm font-bold text-gray-700 focus:ring-primary focus:border-primary">
                        <option value="" disabled selected>-- PILIH SUMBER DANA --</option>
                        @foreach($cashAccounts as $cash)
                            <option value="{{ $cash->id }}">{{ $cash->code }} - {{ $cash->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Jumlah Pokok (Rp)</label>
                        <input type="number" name="amount" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm text-right focus:ring-primary focus:border-primary font-mono font-bold" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Beban Bunga (Rp)</label>
                        <input type="number" name="interest_amount" value="0" class="w-full border-gray-300 rounded-lg p-2.5 text-sm text-right focus:ring-primary focus:border-primary font-mono font-bold" placeholder="0">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan / Catatan</label>
                    <textarea name="description" rows="2" class="w-full border-gray-300 rounded-lg p-2.5 text-sm uppercase focus:ring-primary focus:border-primary" placeholder="CONTOH: CICILAN KE-3 UTANG BANK BCA"></textarea>
                </div>

                <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:opacity-90 transition text-sm uppercase shadow-lg transform active:scale-95">SIMPAN & JURNAL OTOMATIS</button>
            </form>
        </div>
    </div>

</div>
@endsection