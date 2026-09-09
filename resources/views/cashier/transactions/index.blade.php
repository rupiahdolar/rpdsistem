@extends('layouts.app')

@section('content')
    {{-- ================================================================= --}}
    {{-- STYLE KHUSUS NOTA (PRINT) --}}
    {{-- ================================================================= --}}
    <style>
        /* Sembunyikan tampilan print saat mode web biasa */
        #receipt-area { display: none; }

        @media print {
            /* 1. RESET GLOBAL: Hapus elemen web, Tampilkan struk */
            body {
                margin: 0;
                padding: 0;
            }

            body * { visibility: hidden; height: 0; overflow: hidden; }
            
            /* 2. SETTING KERTAS: Auto (Ikut Driver) & Lebar Penuh */
            @page { 
                size: auto;   
                margin: 0; 
            }

            /* 3. PENGATURAN AREA STRUK */
            #receipt-area, #receipt-area * { 
                visibility: visible; 
                height: auto;
                color: black !important;
                font-weight: normal !important; 
            }

            #receipt-area {
                display: block !important;
                position: absolute; 
                left: 0; 
                top: 0;
                width: 72mm;
                padding-top: 5px;
                padding-right: 2mm; 
                font-family: Arial, Helvetica, 'FontA11', 'FontB11', 'Control', sans-serif !important; 
                font-size: 9pt;  
                line-height: 1.15; 
                letter-spacing: 0px;
            }
            #receipt-area table, 
            #receipt-area td, 
            #receipt-area th, 
            #receipt-area div,
            #receipt-area span {
                font-family: inherit !important;
            }

            /* 4. HEADER & INFO */
            .receipt-header { 
                text-align: center; 
                margin-bottom: 5px; 
                border-bottom: 1px dashed #000; 
                padding-bottom: 5px; 
            }
            
            .receipt-title { 
                font-size: 11pt;
                margin: 0; 
                text-transform: uppercase;
            }

            .receipt-info { margin-bottom: 5px; }

            /* 5. TABEL TRANSAKSI */
            .receipt-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 5px; 
            }
            
            .receipt-table th { 
                text-align: right; 
                border-bottom: 1px dashed #000; 
                border-top: 1px dashed #000;
                padding: 3px 0;
                font-size: 8pt;
            }
            .receipt-table th:first-child { text-align: left; }

            .receipt-table td { 
                padding: 1px 0; 
                text-align: right;
                vertical-align: top;
                font-size: 8pt;
            }
            .receipt-table td:first-child { text-align: left; }
            
            /* 6. TOTAL & FOOTER */
            .dashed-top { 
                border-top: 1px dashed #000; 
                padding-top: 5px; 
                margin-top: 5px;
            }
            
            .sign-area {
                margin-top: 15px; 
                width: 100%;
                font-size: 8pt;
                clear: both;
            }

            .sign-box { 
                text-align: center; 
                width: 48%; 
                display: inline-block;
                vertical-align: top;
            }
            
            .sign-line {
                border-top: 1px solid #000; 
                width: 100%; 
                margin: 55px auto 0 auto; 
            }
            
            .receipt-footer { 
                text-align: center; 
                margin-top: 10px; 
                font-size: 8pt; 
                font-style: italic;
            }
        }
    </style>

    {{-- LOGIKA DATA NOTA (PHP) --}}
    @php
        $lastTrxGroup = collect([]);
        $printNota = null;
        
        if(session('transaction_success') && isset($todayTransactions) && $todayTransactions->isNotEmpty()){
            $lastNota = $todayTransactions->first()->no_nota;
            $printNota = $lastNota;
            $lastTrxGroup = $todayTransactions->where('no_nota', $lastNota);
            $trxHeader = $lastTrxGroup->first(); 
        }
    @endphp

    {{-- AREA NOTA (HANYA MUNCUL SAAT PRINT) --}}
    <div id="receipt-area">
        @if($printNota && $trxHeader)
        <div style="padding: 0 2px;">
            <div class="receipt-header">
                <div class="receipt-title" style="font-weight: bold; font-size: 12pt;">PT. Rupiah Dollar Valuta</div>
                <div class="receipt-title">{{ $trxHeader->branch->name ?? 'MONEY CHANGER' }}</div>
                <div style="font-size: 8pt;">{{ $trxHeader->branch->address ?? 'Alamat Cabang' }}</div>
            </div>

            <div class="receipt-info" style="display: flex; justify-content: space-between; border-bottom: 1px dashed #000; padding-bottom: 3px;">
                <div>No: {{ $trxHeader->no_nota }}</div>
                <div>{{ $trxHeader->created_at->format('d/m/Y H:i') }}</div>
            </div>

            <div class="receipt-info" style="margin-top: 5px;">
                <table style="width: 100%;">
                    <tr>
                        <td width="15%">Name</td>
                        <td width="2%">:</td>
                        <td>{{ \Illuminate\Support\Str::limit($trxHeader->customer_name, 25) }}</td>
                    </tr>
                    <tr>
                        <td>ID No</td>
                        <td>:</td>
                        <td>{{ $trxHeader->customer_identity_no }}</td>
                    </tr>
                    @if($trxHeader->customer_type == 'CORPORATE' && $trxHeader->representative_name)
                    <tr>
                        <td>PIC</td>
                        <td>:</td>
                        <td>{{ $trxHeader->representative_name }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Addr</td>
                        <td>:</td>
                        <td>{{ \Illuminate\Support\Str::limit($trxHeader->customer_address, 30) }}</td>
                    </tr>
                    <tr>
                        <td>Ctry</td>
                        <td>:</td>
                        <td>{{ $trxHeader->customer_country }}</td>
                    </tr>
                    <tr>
                        <td style="padding-top: 5px;">TYPE</td>
                        <td style="padding-top: 5px;">:</td>
                        <td style="padding-top: 5px;">
                            <span style="text-transform: uppercase;">
                                {{ $trxHeader->type == 'buy' ? 'PEMBELIAN (BUY)' : 'PENJUALAN (SELL)' }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <table class="receipt-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">CURR</th>
                        <th style="width: 25%;">AMOUNT</th>
                        <th style="width: 25%;">RATE</th>
                        <th style="width: 35%;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grandTotal = 0; @endphp
                    @foreach($lastTrxGroup as $item)
                    @php $grandTotal += $item->total_idr; @endphp
                    <tr>
                        <td>{{ $item->currency }}</td>
                        <td>{{ number_format($item->amount_foreign, 0) }}</td> 
                        <td>{{ number_format($item->rate, 0) }}</td>
                        <td>{{ number_format($item->total_idr, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="dashed-top">
                <div style="display: flex; justify-content: space-between; font-size: 9pt;">
                    <span>TOTAL IDR</span>
                    <span>Rp {{ number_format($grandTotal, 0) }}</span>
                </div>
                <div style="font-size: 8pt; margin-top: 2px;">
                    Metode: {{ $trxHeader->payment_method }}
                </div>
            </div>

            <div class="sign-area">
                <div class="sign-box">
                    <div>Customer</div> 
                    <div class="sign-line">{{ \Illuminate\Support\Str::limit($trxHeader->customer_name, 15) }}</div>
                </div>

                <div class="sign-box">
                    <div>Customer Service</div> 
                    <div class="sign-line">{{ Auth::user()->name }}</div>
                </div>
            </div>

            <div class="receipt-footer">
                "This transaction was conducted below the threshold / equivalent to USD 10,000"
            </div>
            <div class="receipt-footer">
                "Please recount your money before leaving the outlet/office. No complaints or claims regarding a shortfall will be entertained after leaving the counter."
            </div>
        </div>
        @endif
    </div>

    {{-- ================================================================= --}}
    {{-- BAGIAN 1: MODAL & NOTIFIKASI --}}
    {{-- ================================================================= --}}

    {{-- MODAL TUTUP SHIFT --}}
    <div id="endShiftModal" class="fixed inset-0 z-[999] bg-black/50 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-sm w-full p-6 text-center transform transition-all border-t-4 border-red-600">
            <h2 class="text-lg font-bold text-gray-800 mb-2">Konfirmasi Tutup Shift</h2>
            <p class="text-xs text-gray-500 mb-6 px-4">
                Yakin ingin mengakhiri sesi ini? <br>
                Pastikan uang fisik sudah sesuai dengan sistem.
            </p>
            <div class="flex gap-3">
                <button type="button" onclick="closeEndShiftModal()" class="flex-1 py-2.5 rounded border border-gray-300 text-gray-700 text-xs font-bold hover:bg-gray-50 transition">BATAL</button>
                <button type="button" onclick="document.getElementById('endShiftForm').submit()" class="flex-1 py-2.5 rounded bg-red-600 text-white text-xs font-bold hover:bg-red-700 shadow transition">YA, TUTUP</button>
            </div>
        </div>
    </div>

    {{-- DTTOT BLOCK --}}
    @if(session('dttot_block'))
    <div class="fixed inset-0 z-[999] bg-red-900/90 backdrop-blur-sm flex items-center justify-center p-4 animate-pulse">
        <div class="bg-white rounded-lg shadow-2xl max-w-lg w-full overflow-hidden border-4 border-red-600 text-center p-8">
            <h1 class="text-2xl font-black text-red-600 uppercase tracking-widest mb-2">TRANSAKSI DITOLAK!</h1>
            <p class="text-gray-600 text-sm mb-4">Nasabah terdaftar dalam Database Teroris (DTTOT).</p>
            <h2 class="text-xl font-bold text-gray-800 uppercase mb-6 bg-gray-100 p-2 rounded">{{ session('dttot_block')['name'] }}</h2>
            <a href="{{ route('transaction.index') }}" class="block w-full bg-gray-800 hover:bg-black text-white font-bold py-3 rounded text-sm transition">KEMBALI</a>
        </div>
    </div>
    @endif

    {{-- DTTOT WARNING POPUP --}}
    @if(session('dttot_warning'))
    <div class="fixed inset-0 z-[999] bg-yellow-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden border-t-8 border-yellow-500 p-6 text-left">
            <div class="flex items-center gap-3 text-yellow-600 mb-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <h2 class="text-lg font-bold uppercase tracking-wide">Peringatan Kemiripan Nama!</h2>
            </div>

            <p class="text-xs text-gray-600 mb-4">
                Nama nasabah <b class="text-gray-900 uppercase">"{{ session('dttot_warning')['name'] }}"</b> memiliki kemiripan dengan daftar DTTOT, namun <b>Tanggal Lahir / No. ID tidak cocok</b>.
            </p>

            <div class="bg-gray-50 border border-gray-200 rounded p-3 text-xs mb-4 max-h-36 overflow-y-auto">
                <span class="font-bold text-gray-700 block mb-1">Data Terkait di Database DTTOT:</span>
                <ul class="list-disc pl-4 text-gray-600 space-y-1">
                    @foreach(session('dttot_warning')['matches'] as $match)
                        <li>
                            <strong class="text-red-600">{{ $match->name }}</strong> 
                            <br><span class="text-[10px] text-gray-500">{{ $match->birth_info ?? 'Info lahir tidak tersedia' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="text-xs font-bold text-red-600 mb-4">
                ⚠️ Silakan periksa KTP/Paspor fisik nasabah! Apakah Anda yakin nasabah ini BUKAN orang yang ada di daftar DTTOT di atas?
            </p>

            <div class="flex gap-2">
                <a href="{{ route('transaction.index') }}" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2.5 rounded text-xs text-center transition">
                    BATALKAN
                </a>
                <button type="button" onclick="submitOverrideDttot()" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 rounded text-xs shadow transition">
                    BERBEDA (LANJUT TRANSAKSI)
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- SUCCESS PRINT POPUP --}}
    @if(session('transaction_success'))
    <div id="printConfirmModal" class="fixed inset-0 z-[999] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6 text-center border-t-4 border-green-600">
            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-base font-bold text-gray-800 mb-1">Transaksi Berhasil!</h3>
            <p class="text-xs text-gray-500 mb-6">{{ session('transaction_success') }}</p>
            
            <div class="flex gap-2">
                <button type="button" onclick="closePrintModal()" class="flex-1 py-2.5 rounded border border-gray-300 text-gray-700 text-xs font-bold hover:bg-gray-50 transition">
                    BATAL / SELESAI
                </button>
                <button type="button" onclick="doPrint()" class="flex-1 py-2.5 rounded bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 shadow transition flex items-center justify-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    PRINT STRUK
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================= --}}
    {{-- BAGIAN 2: HEADER & FORM --}}
    {{-- ================================================================= --}}
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6 pb-4 border-b border-gray-200 no-print">
        <div>
            <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2 border-l-4 border-primary pl-3">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                Input Transaksi
            </h2>
            <p class="text-xs text-gray-500 mt-1 pl-3.5">Kasir: {{ Auth::user()->name }} | {{ session('branch_name') ?? 'PUSAT' }}</p>
        </div>

        <div class="flex items-center gap-3">
            <div>
                <input type="date" 
                    value="{{ date('Y-m-d') }}" 
                    onchange="document.getElementById('hiddenDate').value = this.value"
                    class="border border-gray-300 rounded p-1.5 text-xs font-bold text-gray-700 bg-white shadow-sm cursor-pointer h-9">
            </div>
            <form action="{{ route('transaction.endShift') }}" method="POST" id="endShiftForm">
                @csrf
                <button type="button" onclick="openEndShiftModal()" class="bg-gray-800 text-white px-4 py-2 rounded text-xs font-bold hover:bg-black transition shadow h-9 flex items-center gap-2">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    TUTUP SHIFT
                </button>
            </form>
        </div>
    </div>

    {{-- FORMULIR UTAMA --}}
    <form action="{{ route('transaction.store') }}" method="POST" id="transactionForm" class="no-print">
        @csrf
        <input type="hidden" name="dttot_override" id="dttotOverrideInput" value="0">
        <input type="hidden" name="transaction_date" id="hiddenDate" value="{{ date('Y-m-d') }}">
            
        {{-- CARD 1: DATA NASABAH (STEP 1) --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                <span class="bg-primary text-white text-[10px] font-bold px-2 py-0.5 rounded">STEP 1</span>
                <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide">Data Nasabah (KYC)</h3>
            </div>

            <div class="p-0" x-data="{ custType: '{{ old('customer_type', 'INDIVIDUAL') }}', ...customerAutocomplete() }">
                
                <table class="w-full text-sm text-left border-collapse">
                    {{-- BARIS 1: TIPE NASABAH --}}
                    <tr class="border-b border-gray-200">
                        <td class="bg-gray-100 p-3 w-48 font-bold text-gray-600 uppercase text-xs border-r border-gray-200">
                            Tipe Nasabah <span class="text-red-500">*</span>
                        </td>
                        <td class="p-3">
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="customer_type" value="INDIVIDUAL" x-model="custType" class="w-4 h-4 text-primary focus:ring-primary">
                                    <span class="text-xs font-bold text-gray-700">PERORANGAN (Individu)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="customer_type" value="CORPORATE" x-model="custType" class="w-4 h-4 text-primary focus:ring-primary">
                                    <span class="text-xs font-bold text-gray-700">KORPORASI (PT/CV)</span>
                                </label>
                            </div>
                        </td>
                    </tr>

                    {{-- BARIS 2: NO NOTA --}}
                    <tr class="border-b border-gray-200">
                        <td class="bg-gray-100 p-3 font-bold text-gray-600 uppercase text-xs border-r border-gray-200">
                            No. Nota
                        </td>
                        <td class="p-3">
                            <input type="text" name="no_nota" 
                                value="{{ old('no_nota') }}" 
                                oninput="this.value = this.value.toUpperCase()"
                                class="w-full md:w-1/3 border-gray-300 rounded p-1.5 text-xs font-bold font-mono bg-yellow-50 uppercase focus:ring-primary focus:border-primary" 
                                placeholder="AUTO (KOSONGKAN JIKA BARU)">
                            <span class="text-[10px] text-gray-400 ml-2 italic">*Otomatis Kapital</span>
                        </td>
                    </tr>

                    {{-- BARIS NOMOR HP (OPSIONAL DENGAN AUTOCOMPLETE) --}}
                    <tr class="border-b border-gray-200 bg-gray-50/50">
                        <td class="bg-gray-100 p-3 font-bold text-gray-600 uppercase text-xs border-r border-gray-200">
                            No. HP / WA <span class="text-gray-400 font-normal">(Opsional)</span>
                        </td>
                        <td class="p-3 relative">
                            <input type="text" name="customer_phone" 
                                x-model="searchPhone" 
                                @input.debounce.300ms="fetchCustomers(searchPhone, 'phone')" 
                                @click.away="openPhone = false" 
                                autocomplete="off" 
                                class="w-full md:w-1/2 border-gray-300 rounded p-1.5 text-xs font-bold font-mono focus:ring-primary focus:border-primary" 
                                placeholder="KETIK NO HP UNTUK CARI (OPSIONAL)...">
                            
                            {{-- DROPDOWN AUTOCOMPLETE NO HP --}}
                            <div x-show="openPhone && filteredListPhone.length > 0" class="absolute z-50 bg-white w-full max-w-lg border border-gray-200 rounded shadow-xl mt-1 max-h-40 overflow-y-auto" style="display: none;">
                                <ul>
                                    <template x-for="(cust, index) in filteredListPhone" :key="index">
                                        <li @click="selectCustomer(cust)" class="p-2 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition">
                                            <div class="flex justify-between items-center">
                                                <span class="font-bold text-blue-600 font-mono text-xs" x-text="cust.customer_phone || 'TANPA NO HP'"></span>
                                                <span class="font-bold text-gray-800 text-xs uppercase" x-text="cust.customer_name"></span>
                                            </div>
                                            <div class="text-[10px] text-gray-500 font-mono mt-0.5">
                                                ID: <span x-text="cust.customer_identity_no"></span>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    {{-- BARIS 3: NAMA NASABAH --}}
                    <tr class="border-b border-gray-200">
                        <td class="bg-gray-100 p-3 font-bold text-gray-600 uppercase text-xs border-r border-gray-200">
                            <span x-text="custType == 'INDIVIDUAL' ? 'Nama Lengkap' : 'Nama Perusahaan'"></span> <span class="text-red-500">*</span>
                        </td>
                        <td class="p-3 relative">
                            <input type="text" name="customer_name" 
                                   x-model="search" 
                                   @input.debounce.300ms="fetchCustomers(search, 'name')"
                                   @click.away="open = false" 
                                   autocomplete="off" 
                                   class="w-full border-gray-300 rounded p-1.5 text-xs font-bold uppercase focus:ring-primary focus:border-primary" 
                                   placeholder="KETIK NAMA UNTUK CARI..." required>
                            
                            <div x-show="open && filteredList.length > 0" class="absolute z-50 bg-white w-full max-w-lg border border-gray-200 rounded shadow-xl mt-1 max-h-40 overflow-y-auto" style="display: none;">
                                <ul>
                                    <template x-for="(cust, index) in filteredList" :key="index">
                                        <li @click="selectCustomer(cust)" class="p-2 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition">
                                            <div class="font-bold text-gray-800 text-xs uppercase" x-text="cust.customer_name"></div>
                                            <div class="text-[10px] text-gray-500 font-mono">
                                                <span x-text="cust.customer_identity_no"></span>
                                                <span x-text="cust.customer_type == 'CORPORATE' ? '(KORP)' : ''" class="font-bold text-blue-600"></span>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    {{-- BARIS 4: IDENTITAS --}}
                    <tr class="border-b border-gray-200">
                        <td class="bg-gray-100 p-3 font-bold text-gray-600 uppercase text-xs border-r border-gray-200">
                            Identitas (KTP/Pass) <span class="text-red-500">*</span>
                        </td>
                        <td class="p-3">
                            <div class="flex gap-2">
                                <select name="customer_id_type" x-model="formData.type" class="w-24 border-gray-300 rounded p-1.5 text-xs font-bold focus:ring-primary focus:border-primary">
                                    <template x-if="custType == 'INDIVIDUAL'">
                                        <optgroup label="Perorangan">
                                            <option value="KTP">KTP</option>
                                            <option value="PASPOR">PASPOR</option>
                                            <option value="SIM">SIM</option>
                                            <option value="KITAS">KITAS</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </optgroup>
                                    </template>
                                    <template x-if="custType == 'CORPORATE'">
                                        <optgroup label="Korporasi">
                                            <option value="KPmIU">KPmIU</option>
                                            <option value="NIB">NIB</option>
                                            <option value="NPWP">NPWP</option>
                                            <option value="SK">SK MENKUMHAM</option>
                                        </optgroup>
                                    </template>
                                </select>
                                <input type="text" name="customer_identity_no" x-model="formData.identity" class="flex-1 border-gray-300 rounded p-1.5 text-xs font-bold font-mono uppercase focus:ring-primary focus:border-primary" placeholder="NOMOR IDENTITAS" required>
                            </div>
                        </td>
                    </tr>

                    {{-- BARIS 5: GENDER & TANGGAL LAHIR --}}
                    <tr class="border-b border-gray-200">
                        <td class="bg-gray-100 p-3 font-bold text-gray-600 uppercase text-xs border-r border-gray-200">
                            Detail Personal
                        </td>
                        <td class="p-3">
                            <div class="grid grid-cols-2 gap-4">
                                <div x-show="custType == 'INDIVIDUAL'" class="flex items-center gap-2 border-r border-gray-200 pr-4">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Gender:</span>
                                    <div class="flex gap-3">
                                        <label class="flex items-center gap-1 cursor-pointer">
                                            <input type="radio" name="customer_gender" value="L" x-model="formData.gender" class="w-3 h-3 text-primary">
                                            <span class="text-xs font-bold text-gray-700">PRIA</span>
                                        </label>
                                        <label class="flex items-center gap-1 cursor-pointer">
                                            <input type="radio" name="customer_gender" value="P" x-model="formData.gender" class="w-3 h-3 text-primary">
                                            <span class="text-xs font-bold text-gray-700">WANITA</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase whitespace-nowrap" x-text="custType == 'INDIVIDUAL' ? 'Tgl Lahir (Opsional):' : 'Tgl Berdiri (Opsional):'"></span>
                                    <input type="date" name="customer_dob" x-model="formData.dob" class="border-gray-300 rounded p-1 text-xs font-bold uppercase focus:ring-primary focus:border-primary w-full text-gray-600">
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- BARIS 6: PEKERJAAN & NEGARA --}}
                    <tr class="border-b border-gray-200">
                        <td class="bg-gray-100 p-3 font-bold text-gray-600 uppercase text-xs border-r border-gray-200">
                            Info Tambahan
                        </td>
                        <td class="p-3">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase mb-0.5">
                                        <span x-text="custType == 'INDIVIDUAL' ? 'Pekerjaan' : 'Bidang Usaha'"></span>
                                    </label>
                                    <input type="text" name="customer_job" x-model="formData.job" class="w-full border-gray-300 rounded p-1.5 text-xs font-bold uppercase focus:ring-primary focus:border-primary" required>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase mb-0.5">Negara Asal</label>
                                    <input type="text" name="customer_country" x-model="formData.country" class="w-full border-gray-300 rounded p-1.5 text-xs font-bold uppercase focus:ring-primary focus:border-primary" required>
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- BARIS 7: ALAMAT LENGKAP --}}
                    <tr class="border-b border-gray-200">
                        <td class="bg-gray-100 p-3 font-bold text-gray-600 uppercase text-xs border-r border-gray-200">
                            Alamat Lengkap <span class="text-red-500">*</span>
                        </td>
                        <td class="p-3">
                            <input type="text" name="customer_address" x-model="formData.address" class="w-full border-gray-300 rounded p-1.5 text-xs font-bold uppercase focus:ring-primary focus:border-primary" placeholder="JALAN, NOMOR, KOTA" required>
                        </td>
                    </tr>

                    {{-- BARIS 8: APU PPT --}}
                    <tr class="border-b border-gray-200 bg-yellow-50/30" x-data="{ sourceOther: false, purposeOther: false }">
                        <td class="bg-yellow-100/50 p-3 font-bold text-yellow-800 uppercase text-xs border-r border-yellow-200">
                            Data APU-PPT
                        </td>
                        <td class="p-3">
                            <div class="grid grid-cols-2 gap-4">
                                {{-- SUMBER DANA --}}
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase mb-0.5">Sumber Dana</label>
                                    <select name="source_of_funds" 
                                            x-model="formData.source" 
                                            @change="sourceOther = ($event.target.value === 'LAINNYA')"
                                            class="w-full border-gray-300 rounded p-1.5 text-xs font-bold bg-white focus:ring-primary focus:border-primary" required>
                                        <option value="">-- PILIH --</option>
                                        <option value="GAJI">GAJI / UPAH</option>
                                        <option value="HASIL USAHA">HASIL USAHA</option>
                                        <option value="WARISAN">WARISAN</option>
                                        <option value="TABUNGAN">TABUNGAN</option>
                                        <option value="JUAL ASET">PENJUALAN ASET</option>
                                        <option value="LAINNYA">LAINNYA</option>
                                    </select>
                                    
                                    {{-- INPUT MANUAL SUMBER DANA --}}
                                    <input type="text" 
                                           name="source_of_funds_custom" 
                                           x-show="sourceOther || formData.source === 'LAINNYA'" 
                                           x-effect="if (formData.source === 'LAINNYA') sourceOther = true"
                                           class="w-full border-gray-300 rounded p-1.5 text-xs font-bold uppercase mt-2 border-yellow-400 bg-yellow-50 focus:ring-primary focus:border-primary" 
                                           placeholder="KETIK SUMBER DANA LAINNYA..."
                                           :required="formData.source === 'LAINNYA'">
                                </div>

                                {{-- TUJUAN TRANSAKSI --}}
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase mb-0.5">Tujuan Transaksi</label>
                                    <select name="transaction_purpose" 
                                            x-model="formData.purpose" 
                                            @change="purposeOther = ($event.target.value === 'LAINNYA')"
                                            class="w-full border-gray-300 rounded p-1.5 text-xs font-bold bg-white focus:ring-primary focus:border-primary" required>
                                        <option value="">-- PILIH --</option>
                                        <option value="LIVING COST">BIAYA HIDUP</option>
                                        <option value="BISNIS">BISNIS</option>
                                        <option value="WISATA">WISATA / TRAVEL</option>
                                        <option value="PENDIDIKAN">PENDIDIKAN</option>
                                        <option value="INVESTASI">INVESTASI</option>
                                        <option value="LAINNYA">LAINNYA</option>
                                    </select>

                                    {{-- INPUT MANUAL TUJUAN TRANSAKSI --}}
                                    <input type="text" 
                                           name="transaction_purpose_custom" 
                                           x-show="purposeOther || formData.purpose === 'LAINNYA'" 
                                           x-effect="if (formData.purpose === 'LAINNYA') purposeOther = true"
                                           class="w-full border-gray-300 rounded p-1.5 text-xs font-bold uppercase mt-2 border-yellow-400 bg-yellow-50 focus:ring-primary focus:border-primary" 
                                           placeholder="KETIK TUJUAN TRANSAKSI LAINNYA..."
                                           :required="formData.purpose === 'LAINNYA'">
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- BARIS 9: PIC (KHUSUS KORPORASI) --}}
                    <tr x-show="custType == 'CORPORATE'" style="display: none;" class="bg-blue-50/30">
                        <td class="bg-blue-100/50 p-3 font-bold text-blue-800 uppercase text-xs border-r border-blue-200">
                            Data Pengurus (PIC)
                        </td>
                        <td class="p-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-blue-400 uppercase mb-0.5">Nama Pengurus</label>
                                    <input type="text" name="representative_name" x-model="formData.pic_name" class="w-full border-gray-300 rounded p-1.5 text-xs font-bold uppercase" placeholder="NAMA SESUAI ID">
                                </div>
                                <div class="flex gap-2">
                                    <div class="w-20">
                                        <label class="block text-[9px] font-bold text-blue-400 uppercase mb-0.5">Tipe ID</label>
                                        <select name="representative_id_type" x-model="formData.pic_id_type" class="w-full border-gray-300 rounded p-1.5 text-xs font-bold">
                                            <option value="KTP">KTP</option>
                                            <option value="PASPOR">PASPOR</option>
                                            <option value="SIM">SIM</option>
                                        </select>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-[9px] font-bold text-blue-400 uppercase mb-0.5">No. ID</label>
                                        <input type="text" name="representative_id_no" x-model="formData.pic_id_no" class="w-full border-gray-300 rounded p-1.5 text-xs font-bold uppercase" placeholder="NOMOR ID">
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>

                </table>
            </div>
        </div>

        {{-- CARD 2: RINCIAN VALAS (STEP 2) --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="bg-primary text-white text-[10px] font-bold px-2 py-0.5 rounded">STEP 2</span>
                    <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide">Rincian Mata Uang</h3>
                </div>
                <button type="button" onclick="addRow()" class="bg-gray-800 text-white px-3 py-1.5 rounded shadow hover:bg-black transition text-xs font-bold uppercase tracking-wider flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Baris
                </button>
            </div>
            
            <div class="px-4 py-3 bg-yellow-50 border-b border-yellow-100 flex items-center">
                <label class="text-xs font-bold text-gray-500 uppercase mr-2">JENIS TRANSAKSI</label>
                <select id="globalType" onchange="updateAllRowsType()" class="border-gray-300 rounded p-1.5 text-sm font-bold w-48 focus:ring-primary focus:border-primary">
                    <option value="buy" selected>BELI</option>
                    <option value="sell">JUAL</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-primary text-white font-semibold text-xs uppercase tracking-wide">
                        <tr>
                            <th class="p-3">Currency</th>
                            <th class="p-3 text-right">Amount</th>
                            <th class="p-3 text-right">Rate</th>
                            <th class="p-3 text-right">Total</th>
                            <th class="p-3 text-center w-16">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody" class="divide-y divide-gray-100">
                        <tr class="bg-white group hover:bg-gray-50 transition">
                            <input type="hidden" name="items[0][type]" class="item-type" value="buy">

                            <td class="p-2 align-top">
                                <input list="currencyOptions" name="items[0][currency_code]" value="{{ old('items.0.currency_code') }}" class="w-full p-2 border border-gray-300 rounded font-bold text-gray-800 uppercase text-xs focus:border-primary focus:ring-primary outline-none h-9" placeholder="KODE" required>
                            </td>
                            <td class="p-2 align-top">
                                <input type="text" inputmode="numeric" name="items[0][amount_foreign]" value="{{ old('items.0.amount_foreign') }}" placeholder="0" class="w-full p-2 border border-gray-300 rounded text-right font-mono text-xs font-bold focus:border-primary focus:ring-primary outline-none h-9" onkeyup="formatNumber(this)" required>
                            </td>
                            <td class="p-2 align-top">
                                <input type="number" step="any" name="items[0][rate]" value="{{ old('items.0.rate') }}" placeholder="0" class="w-full p-2 border border-gray-300 rounded text-right font-mono text-xs focus:border-primary focus:ring-primary outline-none h-9" oninput="calculateRow(this)" required>
                            </td>
                            <td class="p-2 align-top">
                                <input type="text" readonly class="w-full bg-gray-50 border border-gray-200 rounded text-right font-bold text-gray-900 total-display text-xs px-2 py-2 font-mono outline-none h-9" value="0">
                            </td>
                            <td class="p-2 text-center align-top">
                                <button type="button" onclick="removeRow(this)" class="bg-gray-100 text-gray-400 p-1.5 rounded hover:bg-red-50 hover:text-red-500 transition h-8 w-8 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="3" class="p-3 text-right font-bold text-gray-600 uppercase text-xs">Total Transaksi (IDR)</td>
                            <td class="p-3 text-right"><span id="grandTotal" class="text-lg font-bold text-primary font-mono">Rp 0</span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- CARD 3: METODE PEMBAYARAN --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mt-6 relative overflow-hidden" x-data="{ method: 'CASH' }">
            <div class="absolute top-0 left-0 w-1 h-full bg-green-600"></div>
            <h3 class="font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2 text-lg">
                3. Pembayaran
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Metode Pembayaran</label>
                    <div class="flex gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="CASH" class="peer sr-only" x-model="method">
                            <div class="px-6 py-3 rounded-lg border-2 border-gray-200 text-gray-500 font-bold peer-checked:border-green-600 peer-checked:text-green-600 peer-checked:bg-green-50 transition flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                TUNAI (CASH)
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="TRANSFER" class="peer sr-only" x-model="method">
                            <div class="px-6 py-3 rounded-lg border-2 border-gray-200 text-gray-500 font-bold peer-checked:border-blue-600 peer-checked:text-blue-600 peer-checked:bg-blue-50 transition flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                TRANSFER BANK
                            </div>
                        </label>
                    </div>
                </div>

                <div x-show="method == 'TRANSFER'" style="display: none;">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Pilih Rekening Bank <span class="text-red-500">*</span></label>
                    <select name="bank_account_id" class="w-full p-3 border border-gray-300 rounded-lg font-bold text-gray-700 focus:ring-blue-600 focus:border-blue-600">
                        <option value="">-- PILIH BANK TUJUAN --</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="mt-6 pb-10">
            <button type="submit" class="w-full bg-primary text-white font-bold py-3.5 rounded-lg shadow-lg hover:opacity-90 transition transform active:scale-[0.99] text-sm uppercase tracking-widest flex justify-center items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                PROSES TRANSAKSI SEKARANG
            </button>
        </div>
    </form>
    
    <datalist id="currencyOptions">
        @foreach($currencies as $curr)
            <option value="{{ $curr->code }}">{{ $curr->name }}</option>
        @endforeach
    </datalist>

    {{-- ================================================================= --}}
    {{-- SCRIPT JAVASCRIPT UTAMA (CLEAN & BERSIH DUPLIKASI) --}}
    {{-- ================================================================= --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let rowCount = 1;

        // Modal Handler
        function openEndShiftModal() {
            document.getElementById('endShiftModal')?.classList.remove('hidden');
        }
        function closeEndShiftModal() {
            document.getElementById('endShiftModal')?.classList.add('hidden');
        }
        function closePrintModal() {
            document.getElementById('printConfirmModal')?.remove();
        }
        function doPrint() {
            window.print();
            closePrintModal();
        }

        // DTTOT Override Submit
        function submitOverrideDttot() {
            var form = document.getElementById('transactionForm');
            var overrideInput = document.getElementById('dttotOverrideInput');
            
            if (overrideInput) overrideInput.value = '1';
            
            var inputs = form.querySelectorAll('input[name*="amount_foreign"]');
            inputs.forEach(function(input) {
                input.value = parseNumber(input.value); 
            });

            form.setAttribute('novalidate', 'novalidate');
            form.submit();
        }

        // Formatting & Calculations
        function formatNumber(input) {
            let value = input.value.replace(/\D/g, '');
            if (value !== '') value = new Intl.NumberFormat('id-ID').format(value);
            input.value = value;
            calculateRow(input); 
        }

        function parseNumber(str) {
            if (!str) return 0;
            // Jika input sudah berupa angka tunggal tanpa titik ribuan, langsung kembalikan float
            if (typeof str === 'number') return str;
            return parseFloat(str.toString().replace(/\./g, '').replace(/,/g, '.')) || 0;
        }

        function calculateRow(element) {
            const row = element.closest('tr');
            let amount = parseNumber(row.querySelector('input[name*="[amount_foreign]"]').value);
            let rateInput = row.querySelector('input[name*="[rate]"]');
            let rate = parseFloat(rateInput.value) || 0;
            
            let total = amount * rate;
            row.querySelector('.total-display').value = new Intl.NumberFormat('id-ID').format(total);
            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            let grandTotal = 0;
            document.querySelectorAll('#tableBody tr').forEach(row => {
                let amount = parseNumber(row.querySelector('input[name*="[amount_foreign]"]').value);
                let rateInput = row.querySelector('input[name*="[rate]"]');
                let rate = parseFloat(rateInput.value) || 0;
                grandTotal += (amount * rate);
            });
            document.getElementById('grandTotal').innerText = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(grandTotal);
        }

        // Table Rows Management
        function addRow() {
            const tbody = document.getElementById('tableBody');
            const newRow = document.createElement('tr');
            newRow.className = "bg-white border-t border-gray-100 group hover:bg-gray-50 transition";
            
            let currentType = document.getElementById('globalType').value;

            newRow.innerHTML = `
                <input type="hidden" name="items[${rowCount}][type]" class="item-type" value="${currentType}">

                <td class="p-2 align-top">
                     <input list="currencyOptions" name="items[${rowCount}][currency_code]" class="w-full p-2 border border-gray-300 rounded font-bold text-gray-800 uppercase text-xs focus:border-primary focus:ring-primary outline-none h-9" placeholder="KODE" required>
                </td>
                <td class="p-2 align-top">
                    <input type="text" inputmode="numeric" name="items[${rowCount}][amount_foreign]" placeholder="0" class="w-full p-2 border border-gray-300 rounded text-right font-mono text-xs font-bold focus:border-primary focus:ring-primary outline-none h-9" onkeyup="formatNumber(this)" required>
                </td>
                <td class="p-2 align-top">
                    <input type="number" step="any" name="items[${rowCount}][rate]" placeholder="0" class="w-full p-2 border border-gray-300 rounded text-right font-mono text-xs focus:border-primary focus:ring-primary outline-none h-9" oninput="calculateRow(this)" required>
                </td>
                <td class="p-2 align-top">
                    <input type="text" readonly class="w-full bg-gray-50 border border-gray-200 rounded text-right font-bold text-gray-900 total-display text-xs px-2 py-2 font-mono outline-none h-9" value="0">
                </td>
                <td class="p-2 text-center align-top">
                    <button type="button" onclick="removeRow(this)" class="bg-gray-100 text-gray-400 p-2 rounded hover:bg-red-50 hover:text-red-500 transition h-9 w-9 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </td>
            `;
            tbody.appendChild(newRow);
            rowCount++;
        }

        function updateAllRowsType() {
            let type = document.getElementById('globalType').value;
            document.querySelectorAll('.item-type').forEach(input => {
                input.value = type;
            });
        }

        function removeRow(btn) {
            const row = btn.closest('tr');
            if (document.getElementById('tableBody').rows.length > 1) {
                row.remove();
                calculateGrandTotal();
            } else {
                alert("Minimal harus ada satu baris transaksi!");
            }
        }

        // Autocomplete Logic
        function customerAutocomplete() {
        return {
            custType: '{{ old("customer_type", "INDIVIDUAL") }}',
            search: '{{ old("customer_name") }}',
            searchPhone: '{{ old("customer_phone") }}', // <-- Properti Baru untuk No HP
            open: false,
            openPhone: false, // <-- Status Pop-up No HP
            filteredList: [],
            filteredListPhone: [], // <-- Hasil Pencarian No HP
            isThresholdExceeded: false,
            formData: {
                type: '{{ old("customer_id_type", "KTP") }}', 
                identity: '{{ old("customer_identity_no") }}', 
                address: '{{ old("customer_address") }}', 
                job: '{{ old("customer_job") }}', 
                country: '{{ old("customer_country") }}',
                gender: '{{ old("customer_gender") }}', 
                dob: '{{ old("customer_dob") }}',
                pic_name: '{{ old("representative_name") }}', 
                pic_id_type: '{{ old("representative_id_type", "KTP") }}', 
                pic_id_no: '{{ old("representative_id_no") }}',
                source: '{{ old("source_of_funds") }}', 
                purpose: '{{ old("transaction_purpose") }}'
            },
            async fetchCustomers(query = null, target = 'name') {
                let keyword = query !== null ? query : (target === 'name' ? this.search : this.searchPhone);

                if (!keyword || keyword.length < 2) { 
                    if (target === 'name') this.open = false;
                    if (target === 'phone') this.openPhone = false;
                    return; 
                }
                try {
                    // Tambahkan parameter &type=${target} pada URL fetch
                    let response = await fetch(`{{ route('transaction.search.customers') }}?q=${encodeURIComponent(keyword)}&type=${target}`);
                    let data = await response.json();
                    
                    if (target === 'name') {
                        this.filteredList = data;
                        this.open = data.length > 0;
                    } else {
                        this.filteredListPhone = data;
                        this.openPhone = data.length > 0;
                    }
                } catch (error) {
                    console.error('Gagal mengambil data:', error);
                }
            },
            selectCustomer(c) {
                this.search = c.customer_name || '';
                if (c.customer_phone && c.customer_phone.trim() !== '') {
                    this.searchPhone = c.customer_phone;
                }

                this.custType = c.customer_type || 'INDIVIDUAL';
                this.formData.type = c.customer_id_type || 'KTP';
                this.formData.identity = c.customer_identity_no || '';
                this.formData.address = c.customer_address || '';
                this.formData.job = c.customer_job || '';
                this.formData.country = c.customer_country || '';
                this.formData.gender = c.customer_gender || '';
                this.formData.dob = c.customer_dob || '';
                this.formData.source = c.source_of_funds || '';
                this.formData.purpose = c.transaction_purpose || '';
                this.formData.pic_name = c.representative_name || '';
                this.formData.pic_id_type = c.representative_id_type || 'KTP';
                this.formData.pic_id_no = c.representative_id_no || '';
                
                this.open = false;
                this.openPhone = false;
                
                if (c.customer_identity_no) {
                    checkApuPptThreshold(c.customer_identity_no);
                }
            }
        };
    }

        // Pengecekan Compliance APU-PPT Threshold
        let globalThresholdExceeded = false;

// Pengecekan Compliance APU-PPT Threshold
        function checkApuPptThreshold(identityNo) {
            if (!identityNo || identityNo.trim().length < 3) return;

            // Ambil tipe transaksi aktif (buy / sell)
            let currentType = document.getElementById('globalType').value;
            if (currentType !== 'sell') return; // Abaikan jika bukan tipe JUAL

            let currentTotalAmount = 0;
            document.querySelectorAll('#tableBody tr').forEach(row => {
                let amountInput = row.querySelector('input[name*="[amount_foreign]"]');
                let rateInput = row.querySelector('input[name*="[rate]"]');
                let amount = amountInput ? parseNumber(amountInput.value) : 0;
                let rate = rateInput ? parseFloat(rateInput.value) || 0 : 0;
                currentTotalAmount += (amount * rate);
            });

            // Kirim parameter type=sell ke backend
            fetch(`/compliance-check/threshold/${encodeURIComponent(identityNo.trim())}?amount=${currentTotalAmount}&type=${currentType}`)
                .then(response => response.json())
                .then(res => {
                    if (res.status === 'success' && res.data.is_exceeded) {
                        Swal.fire({
                            title: 'ALERT APU-PPT (LTKT)!',
                            html: `
                                <div class="text-left text-xs space-y-2">
                                    <p class="text-red-600 font-bold text-sm">⚠️ Nasabah melampaui Threshold Kumulatif USD 10.000 (Rp ${new Intl.NumberFormat('id-ID').format(res.data.dynamic_limit)}) bulan ini!</p>
                                    <hr>
                                    <p>• Total Setelah Transaksi Ini: <b>Rp ${new Intl.NumberFormat('id-ID').format(res.data.projected_total)}</b></p>
                                </div>
                            `,
                            icon: 'warning',
                            confirmButtonText: 'SAYA MENGERTI',
                            confirmButtonColor: '#DC2626'
                        });
                    }
                });
        }

        // Init Single Listener
        // Init Single Listener
        document.addEventListener('DOMContentLoaded', function() {
            // Format angka awal saat reload
            document.querySelectorAll('#tableBody tr').forEach(row => {
                let amountInput = row.querySelector('input[name*="[amount_foreign]"]');
                if (amountInput && amountInput.value) {
                    formatNumber(amountInput);
                }
            });

            // Event Blur Identitas (Tetap jalan untuk check latar belakang)
            let identityInput = document.querySelector('input[name="customer_identity_no"]');
            if (identityInput) {
                identityInput.addEventListener('blur', function() {
                    checkApuPptThreshold(this.value.trim());
                });
            }

            // SCRIPT SUBMIT FORM TERBARU (SEQUENTIAL CHECK: DTTOT -> THRESHOLD -> PEP)
            const form = document.getElementById('transactionForm');
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault(); // Tahan pengiriman bawaan browser

                    // Ambil Variabel Form
                    let customerName = document.querySelector('input[name="customer_name"]')?.value || '';
                    let idNo = document.querySelector('input[name="customer_identity_no"]')?.value || '';
                    let pekerjaan = document.querySelector('input[name="customer_job"]')?.value || '';
                    let currentType = document.getElementById('globalType').value;

                    // Hitung Grand Total IDR Transaksi
                    let currentTotalAmount = 0;
                    document.querySelectorAll('#tableBody tr').forEach(row => {
                        let amountInput = row.querySelector('input[name*="[amount_foreign]"]');
                        let rateInput = row.querySelector('input[name*="[rate]"]');
                        let amount = amountInput ? parseNumber(amountInput.value) : 0;
                        let rate = rateInput ? parseFloat(rateInput.value) || 0 : 0;
                        currentTotalAmount += (amount * rate);
                    });

                    // -----------------------------------------------------------------
                    // ANIMASI POP-UP INITIAL: Pengecekan Sistem
                    // -----------------------------------------------------------------
                    Swal.fire({
                        title: 'Memeriksa Transaksi...',
                        html: 'Sistem sedang memverifikasi DTTOT, Threshold (LTKT), dan Profil PEP.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => { 
                            Swal.showLoading(); 
                        }
                    });

                    // Trik delay 1.5 detik agar animasi pengecekan terasa alami oleh kasir
                    await new Promise(resolve => setTimeout(resolve, 1500));

                    try {
                        // -------------------------------------------------------------
                        // STEP 1: Pengecekan DTTOT
                        // -------------------------------------------------------------
                        if (customerName.trim().length >= 2) {
                            let dttotResponse = await fetch(`{{ route('transaction.search.customers') }}?q=${encodeURIComponent(customerName)}&type=name`);
                            // Catatan: Jika Anda memiliki API khusus DTTOT Match, ganti endpoint fetch di atas ke endpoint DTTOT Anda
                        }

                        // -------------------------------------------------------------
                        // STEP 2: Pengecekan Threshold Kumulatif / LTKT (>= USD 10.000)
                        // -------------------------------------------------------------
                        if (idNo.length >= 3 && currentType === 'sell') {
                            let fetchUrl = `{{ url('/compliance-check/threshold') }}/${encodeURIComponent(idNo)}?amount=${currentTotalAmount}&type=${currentType}`;
                            let response = await fetch(fetchUrl);
                            
                            if (response.ok) {
                                let res = await response.json();
                                
                                if (res.status === 'success' && res.data.is_exceeded) {
                                    Swal.close(); // Tutup loading
                                    
                                    let thresholdAlert = await Swal.fire({
                                        icon: 'warning',
                                        title: 'Peringatan Threshold (LTKT)',
                                        html: `
                                            <div class="text-left text-xs space-y-2">
                                                <p class="text-red-600 font-bold">⚠️ Total akumulasi transaksi nasabah telah mencapai/melebihi USD 10.000 bulan ini!</p>
                                                <p>Total Transaksi: <b>Rp ${new Intl.NumberFormat('id-ID').format(res.data.projected_total)}</b></p>
                                                <hr>
                                                <p class="font-bold text-gray-700">Harap minta dan periksa dokumen pendukung fisik dari nasabah:</p>
                                                <ul class="list-disc pl-4 text-gray-600">
                                                    <li>NPWP / Surat Pernyataan</li>
                                                    <li>Dokumen Bukti Sumber Dana</li>
                                                </ul>
                                                <p class="pt-2 italic">Apakah dokumen fisik sudah lengkap dan diverifikasi?</p>
                                            </div>
                                        `,
                                        showCancelButton: true,
                                        confirmButtonText: 'Ya, Sudah Lengkap',
                                        cancelButtonText: 'Batal Transaksi',
                                        confirmButtonColor: '#3085d6',
                                        cancelButtonColor: '#d33',
                                        allowOutsideClick: false
                                    });

                                    if (!thresholdAlert.isConfirmed) {
                                        return false; // Kasir membatalkan
                                    }
                                }
                            }
                        }

                        // -------------------------------------------------------------
                        // STEP 3: Pengecekan Profil PEP (Politically Exposed Person)
                        // -------------------------------------------------------------
                        const pepKeywords = ['pns', 'pejabat', 'tni', 'polri', 'dpr', 'dprd', 'bumn', 'pemerintah', 'menteri', 'bupati', 'walikota', 'gubernur', 'jaksa', 'hakim'];
                        let isPep = pepKeywords.some(keyword => pekerjaan.toLowerCase().includes(keyword));

                        if (isPep) {
                            Swal.close(); // Tutup loading jika aktif
                            
                            let pepAlert = await Swal.fire({
                                icon: 'info',
                                title: 'Peringatan Profil PEP (High Risk)',
                                html: `
                                    <div class="text-left text-xs space-y-2">
                                        <p class="text-blue-600 font-bold">Pekerjaan nasabah ("${pekerjaan.toUpperCase()}") terindikasi sebagai PEP / High Risk Profile.</p>
                                        <hr>
                                        <p class="text-gray-700">Pastikan kolom <b>Sumber Dana</b> dan <b>Tujuan Transaksi</b> telah terisi dengan jelas sesuai prosedur EDD.</p>
                                    </div>
                                `,
                                showCancelButton: true,
                                confirmButtonText: 'Lanjutkan Transaksi',
                                cancelButtonText: 'Cek Kembali Form',
                                confirmButtonColor: '#0284c7',
                                cancelButtonColor: '#6b7280',
                                allowOutsideClick: false
                            });

                            if (!pepAlert.isConfirmed) {
                                return false; // Kasir memilih kembali untuk cek form
                            }
                        }

                    } catch(err) {
                        console.error('Gagal saat validasi ke server:', err);
                        Swal.fire('Error Koneksi', 'Gagal menghubungi server untuk verifikasi compliance.', 'error');
                        return false;
                    }

                    // -------------------------------------------------------------
                    // STEP 4: SUBMIT FORM (Bersihkan Format Ribuan Sebelum Dikirim)
                    // -------------------------------------------------------------
                    let inputs = form.querySelectorAll('input[name*="amount_foreign"]');
                    inputs.forEach(input => {
                        input.value = parseNumber(input.value); 
                    });
                    
                    Swal.close(); 
                    form.submit(); // Kirim ke TransactionController@store
                });
            }
        });
    </script>
@endsection