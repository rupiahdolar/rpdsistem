@extends('layouts.app')

@section('content')
<div class="flex flex-col h-full bg-gray-50 pb-20">

    {{-- STYLE KHUSUS PRINT --}}
    <style>
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body { 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
                background: white !important; 
                font-family: sans-serif; 
            }
            aside, nav, .no-print, form, .btn-action, .form-card { display: none !important; }
            .flex-col, .overflow-hidden, .card-box { 
                display: block !important; 
                overflow: visible !important; 
                height: auto !important; 
                border: none !important; 
                box-shadow: none !important; 
                margin: 0 !important; 
            }
            .print-header { 
                display: block !important; 
                text-align: center; 
                margin-bottom: 20px; 
                border-bottom: 2px solid #000; 
                padding-bottom: 10px; 
            }
            table { width: 100% !important; border-collapse: collapse !important; font-size: 11px !important; margin-bottom: 20px !important; }
            th, td { border: 1px solid #000 !important; padding: 6px !important; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            
            * { color: #000 !important; text-shadow: none !important; }
            .bg-primary, .bg-blue-50, .bg-green-50, .bg-red-50, .bg-yellow-50, .bg-gray-50, .bg-primary\/5, .bg-blue-50\/30 {
                background-color: transparent !important;
            }
            tr.bg-primary, tfoot {
                border-top: 2px solid #000 !important;
                font-weight: bold !important;
            }
        }
        .print-header { display: none; }
    </style>

    {{-- BAGIAN 1: FORMULIR INPUT MUTASI (NO PRINT) --}}
    <div class="no-print px-6 pt-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    Mutasi & Rekening Koran
                </h1>
                <p class="text-gray-500 text-sm mt-1">Input perpindahan dana internal & Monitor mutasi bank.</p>
            </div>

            @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-2 rounded shadow-sm flex items-center gap-2 animate-fade-in-down">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="font-bold text-sm">{{ session('success') }}</span>
            </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden form-card">
            <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 flex justify-between items-center cursor-pointer" onclick="document.getElementById('formBody').classList.toggle('hidden')">
                <h3 class="font-bold text-gray-800 flex items-center gap-2 text-sm uppercase tracking-wide">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Formulir Perpindahan Dana (Input Manual)
                </h3>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>
            
            <div id="formBody" class="p-6 transition-all duration-300">
                <form action="{{ route('internal-mutation.store') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Tanggal</label>
                            <input type="date" name="date" value="{{ date('Y-m-d') }}" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-sm font-bold" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jenis Transaksi</label>
                            <select name="type" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-sm font-bold text-gray-800" required>
                                <option value="bank_to_cash">Tarik Tunai (Bank -> Kas Fisik)</option>
                                <option value="cash_to_bank">Setor Tunai (Kas Fisik -> Bank)</option>
                            </select>
                        </div>
                        
                        {{-- LOGIK TAMPILAN CABANG DI FORM INPUT --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Cabang (Kas Fisik)</label>
                            @if($isRestricted)
                                {{-- Jika KASIR: Tampilkan nama cabang tapi disabled --}}
                                <input type="hidden" name="branch_id" value="{{ Auth::user()->branch_id ?? Auth::user()->branches->first()->id }}">
                                <input type="text" value="{{ Auth::user()->branch->name ?? 'CABANG SAYA' }}" class="w-full border-gray-200 bg-gray-100 rounded-lg text-sm text-gray-500 cursor-not-allowed" disabled>
                            @else
                                {{-- Jika ADMIN: Dropdown Bebas --}}
                                <select name="branch_id" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-sm" required>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Akun Bank</label>
                            <select name="bank_account_id" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-sm" required>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->code }} - {{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Keterangan (Opsional)</label>
                            <input type="text" name="description" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-sm" placeholder="Contoh: Restock kasir pagi...">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nominal (IDR)</label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <span class="absolute left-3 top-2.5 text-gray-500 font-bold">Rp</span>
                                    <input type="number" name="amount" class="w-full pl-10 border-gray-300 rounded-lg focus:ring-primary focus:border-primary font-mono font-bold text-gray-800" placeholder="0" min="1" required>
                                </div>
                                <button type="submit" class="bg-primary hover:opacity-90 text-white font-bold px-6 rounded-lg shadow-md transition text-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    SIMPAN
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: FILTER & LAPORAN --}}
    <div class="px-6 pb-6 flex-1">
        
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 no-print">
            <form action="{{ route('internal-mutation.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                
                {{-- PILIH CABANG (HANYA MUNCUL UNTUK ADMIN/OWNER) --}}
                @if(!$isRestricted)
                <div class="w-full md:w-1/4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pilih Cabang</label>
                    <select name="branch_id" class="w-full border-gray-300 rounded-lg text-sm font-bold text-gray-700 focus:ring-primary focus:border-primary h-10" onchange="this.form.submit()">
                        <option value="">-- SEMUA CABANG --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pilih Rekening Bank</label>
                    <select name="bank_id" class="w-full border-gray-300 rounded-lg text-sm font-bold text-primary focus:ring-primary focus:border-primary h-10" onchange="this.form.submit()">
                        <option value="" disabled {{ !$bankId ? 'selected' : '' }}>-- Pilih Bank --</option>
                        @foreach($banks as $b)
                            <option value="{{ $b->id }}" {{ $bankId == $b->id ? 'selected' : '' }}>{{ $b->code }} - {{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="border-gray-300 rounded-lg text-sm h-10">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="border-gray-300 rounded-lg text-sm h-10">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg h-10 font-bold text-sm border border-gray-300 transition">
                        Filter
                    </button>
                    <button type="button" onclick="window.print()" class="bg-primary hover:opacity-90 text-white px-4 py-2 rounded-lg h-10 font-bold text-sm shadow-md flex items-center gap-2 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Cetak PDF
                    </button>
                </div>
            </form>
        </div>

        {{-- AREA PRINTABLE --}}
        <div id="printableArea" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-box">
            
            <div class="print-header pt-4">
                {{-- JUDUL KOP SURAT DINAMIS --}}
                <h1 class="text-2xl font-bold uppercase">
                    @if(isset($branchName) && $branchName != 'SEMUA CABANG')
                        {{ $branchName }}
                    @else
                        PT. CILI BALI LESTARI
                    @endif
                </h1>
                
                <p class="text-sm">Authorized Money Changer</p>
                <hr class="my-2 border-black">
                <h2 class="text-xl font-bold mt-2">REKENING KORAN / MUTASI BANK</h2>
                
                @if($selectedBank)
                    <p class="text-sm font-bold uppercase">{{ $selectedBank->name }}</p>
                @endif
                
                {{-- Jika menampilkan semua cabang, beri keterangan --}}
                @if(!isset($branchName) || $branchName == 'SEMUA CABANG')
                    <p class="text-sm font-bold uppercase mt-1">DATA: SEMUA CABANG</p>
                @endif
                
                <p class="text-xs">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
            </div>

            <div class="px-6 py-4 border-b border-gray-100 bg-white no-print flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide flex items-center gap-2">
                    <span class="w-2 h-6 bg-green-500 rounded-sm"></span>
                    Riwayat Transaksi Bank
                </h3>
                @if($selectedBank)
                    <div class="text-right">
                        <span class="text-xs text-gray-500 block">Akun Terpilih:</span>
                        <span class="font-bold text-primary">{{ $selectedBank->name }}</span>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 uppercase text-xs font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-3 text-center w-24">Tanggal</th>
                            <th class="px-6 py-3">Keterangan</th>
                            <th class="px-6 py-3 text-center">Tipe</th>
                            <th class="px-6 py-3 text-right text-green-700">Masuk (Debit)</th>
                            <th class="px-6 py-3 text-right text-red-700">Keluar (Kredit)</th>
                            <th class="px-6 py-3 text-right text-primary bg-blue-50">Saldo</th>
                            {{-- KOLOM BARU UNTUK AKSI (TIDAK DIPRINT) --}}
                            <th class="px-6 py-3 text-center no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @if(!$bankId)
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
                                    <p>Silakan pilih Bank terlebih dahulu pada filter di atas.</p>
                                </td>
                            </tr>
                        @else
                            {{-- BARIS SALDO AWAL --}}
                            <tr class="bg-yellow-50 font-bold text-gray-700">
                                <td class="px-6 py-3 text-center font-mono text-xs">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</td>
                                <td class="px-6 py-3" colspan="4">SALDO AWAL (OPENING BALANCE)</td>
                                <td class="px-6 py-3 text-right font-mono bg-yellow-100/50">Rp {{ number_format($openingBalance) }}</td>
                                <td class="px-6 py-3 no-print"></td>
                            </tr>

                            {{-- LOOP HISTORY --}}
                            @php $runningBalance = $openingBalance; @endphp
                            
                            @forelse($history as $item)
                                @php 
                                    // [PERBAIKAN] Ubah Array Access $item['...'] menjadi Object Access $item->...
                                    $runningBalance = $runningBalance + $item->debit - $item->credit;
                                @endphp
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-3 text-center font-mono text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}
                                        <div class="text-[9px] text-gray-400 no-print">{{ \Carbon\Carbon::parse($item->created_at)->format('H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-3">
                                        <div class="font-bold text-gray-700 text-xs">{{ $item->description }}</div>
                                        <div class="text-[10px] text-gray-400 uppercase tracking-wider mt-0.5">Ref: {{ $item->source }}</div>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <span class="px-2 py-1 rounded text-[10px] font-bold border 
                                            {{ $item->debit > 0 ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                                            {{ $item->type_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right font-mono text-green-600 font-bold text-xs">
                                        {{ $item->debit > 0 ? number_format($item->debit) : '-' }}
                                    </td>
                                    <td class="px-6 py-3 text-right font-mono text-red-600 font-bold text-xs">
                                        {{ $item->credit > 0 ? number_format($item->credit) : '-' }}
                                    </td>
                                    <td class="px-6 py-3 text-right font-mono font-bold text-primary bg-blue-50/30 text-xs">
                                        Rp {{ number_format($runningBalance) }}
                                    </td>
                                    
                                    {{-- KOLOM AKSI (LOGIKA HAPUS) --}}
                                    <td class="px-6 py-3 text-center no-print">
                                        @if(isset($item->source) && $item->source == 'INTERNAL')
                                            <form action="{{ route('internal-mutation.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus mutasi ini? Jurnal juga akan terhapus.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 p-1 bg-red-50 hover:bg-red-100 rounded" title="Hapus Mutasi">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-[10px] text-gray-300 italic">Auto</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-400 italic">
                                        Tidak ada transaksi pada periode ini.
                                    </td>
                                </tr>
                            @endforelse

                            {{-- BARIS SALDO AKHIR --}}
                            <tr class="bg-primary text-white font-bold border-t-2 border-gray-300">
                                <td class="px-6 py-3 text-center" colspan="5">SALDO AKHIR PERIODE INI</td>
                                <td class="px-6 py-3 text-right font-mono">Rp {{ number_format($runningBalance) }}</td>
                                <td class="px-6 py-3 no-print"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection