@extends('layouts.app')

@section('content')
<div class="flex flex-col h-full pb-20">

    {{-- STYLE KHUSUS PRINT & EXPORT --}}
    <style>
        /* --- CSS KHUSUS PRINT (PDF) --- */
        @media print {
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
            body { 
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: white !important;
                font-family: sans-serif;
            }

            /* Sembunyikan elemen navigasi, header web, filter, & tombol */
            aside, nav, header, footer, .no-print, form, .btn-action, .screen-only, button, select, input { 
                display: none !important; 
            }
            
            /* Munculkan elemen khusus print */
            .print-only { display: block !important; }

            /* Reset Layout */
            .flex-col, .overflow-hidden, .card-box, .main-container { 
                display: block !important;
                overflow: visible !important; 
                height: auto !important;
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Header Laporan Print (PENGGANTI FILTER) */
            .print-header { 
                display: block !important; 
                text-align: center; 
                margin-bottom: 20px; 
                border-bottom: 2px solid #000; 
                padding-bottom: 10px; 
            }

            /* TABEL PRINT */
            table { 
                width: 100% !important; 
                border-collapse: collapse !important; 
                font-size: 11px !important; 
                margin-bottom: 20px !important;
            }
            th, td { 
                border: 1px solid #000 !important; 
                padding: 6px !important; 
                color: #000 !important; 
                vertical-align: top;
                background: white !important;
            }
            thead { display: table-header-group; } 
            tr { page-break-inside: avoid; }
            
            /* Warna Header Cetak */
            .bg-header-print { background-color: #f0f0f0 !important; font-weight: bold; text-align: center; }
        }
        
        /* Helper Classes untuk Layar */
        .print-header { display: none; }
        .print-only { display: none; }
        
        /* Custom Table Border untuk Web */
        .table-bordered th, .table-bordered td {
            border: 1px solid #e2e8f0;
        }
    </style>

    {{-- 1. HEADER HALAMAN & FILTER (WEB ONLY) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 no-print">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            
            {{-- JUDUL --}}
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Biaya Operasional
                </h2>
                <p class="text-xs text-gray-500 mt-1">Pencatatan pengeluaran rutin dan insidental.</p>
            </div>
            
            {{-- TOMBOL ACTION UTAMA --}}
            <div class="flex gap-2">
                <button onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow transition flex items-center gap-2 h-10">
                    <span class="text-xs font-bold">XLS</span>
                </button>
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition flex items-center gap-2 h-10">
                    <span class="text-xs font-bold">PDF</span>
                </button>
                <button onclick="document.getElementById('modalBiaya').classList.remove('hidden')" 
                        class="bg-primary hover:opacity-90 text-white px-4 py-2 rounded-lg shadow transition flex items-center gap-2 h-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span class="text-xs font-bold">TAMBAH</span>
                </button>
            </div>
        </div>

        {{-- FILTER BARIS KEDUA --}}
        <div class="mt-6 pt-4 border-t border-gray-100">
            <form action="{{ route('keuangan.biaya') }}" method="GET" class="flex flex-wrap items-end gap-3 justify-end">
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

                {{-- SUMMARY TOTAL --}}
                <div class="ml-auto bg-orange-50 px-4 py-2 rounded-lg border border-orange-100 text-right min-w-[150px]">
                    <span class="text-[10px] text-orange-500 font-bold block uppercase tracking-wider">TOTAL PENGELUARAN</span>
                    <span class="text-lg font-mono font-bold text-orange-700">Rp {{ number_format($total ?? 0) }}</span>
                </div>
            </form>
        </div>
    </div>

    {{-- KONTEN UTAMA --}}
    <div id="printableArea">
        
        {{-- 2. HEADER CETAK (KHUSUS PRINT) --}}
        <div class="print-header">
            <h1 class="text-xl font-bold uppercase">PT. Rupiah Dollar Valuta</h1>
            <h2 class="text-lg font-bold">LAPORAN BIAYA OPERASIONAL</h2>
            <p class="text-xs">
                Periode: {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
                @if(Auth::user()->role == 'admin' && request('branch_id')) 
                    | Cabang: {{ \App\Models\Branch::find(request('branch_id'))->name }} 
                @endif
            </p>
        </div>

        {{-- 3. TABEL WEB (Clean White Style) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden screen-only">
            <table class="w-full text-sm text-left">
                {{-- Header Putih dengan Garis Biru --}}
                <thead class="bg-white text-primary border-b-2 border-primary uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Nama Pengeluaran</th>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4">Keterangan</th>
                        @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
                        <th class="px-6 py-4">Cabang</th>
                        @endif
                        <th class="px-6 py-4 text-right">Jumlah (IDR)</th>
                        <th class="px-6 py-4 text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($expenses as $cost)
                    <tr class="hover:bg-blue-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-mono font-bold text-xs">
                            {{ \Carbon\Carbon::parse($cost->date)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-800">
                            {{ $cost->name }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded text-[10px] font-bold uppercase border border-gray-200 inline-block">
                                {{ $cost->category }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 italic text-xs">
                            {{ $cost->description ?? '-' }}
                        </td>
                        @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
                        <td class="px-6 py-4 text-gray-500 text-xs font-bold">
                            {{ $cost->branch->name ?? 'PUSAT' }}
                        </td>
                        @endif
                        <td class="px-6 py-4 text-right font-mono font-bold text-gray-900">
                            Rp {{ number_format($cost->amount) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" onclick="editBiaya('{{ $cost->id }}', '{{ $cost->date }}', '{{ addslashes($cost->name) }}', '{{ $cost->category }}', '{{ $cost->amount }}', '{{ addslashes($cost->description) }}')" class="text-yellow-500 hover:text-yellow-600 bg-yellow-50 p-1.5 rounded hover:bg-yellow-100 transition" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <form action="{{ route('biaya.destroy', $cost->id) }}" method="POST" onsubmit="return confirm('Hapus data?');" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-600 bg-red-50 p-1.5 rounded hover:bg-red-100 transition" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="p-8 text-center text-gray-400">Data Kosong.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 font-bold border-t border-gray-200">
                    <tr>
                        <td colspan="{{ Auth::user()->role == 'admin' ? '5' : '4' }}" class="px-6 py-4 text-right uppercase text-xs text-gray-500 tracking-wider">Total Periode Ini</td>
                        <td class="px-6 py-4 text-right font-mono text-lg text-primary">Rp {{ number_format($total ?? 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- 4. TABEL CETAK / EXPORT (Hidden di Layar - Bersih) --}}
        <div class="print-only">
            <table class="w-full text-sm text-left table-bordered" id="exportTable">
                <thead class="bg-header-print">
                    <tr>
                        <th class="p-2">Tanggal</th>
                        <th class="p-2">Nama Pengeluaran</th>
                        <th class="p-2">Kategori</th>
                        <th class="p-2">Keterangan</th>
                        @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
                        <th class="p-2">Cabang</th>
                        @endif
                        <th class="p-2 text-right">Jumlah (IDR)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $cost)
                    <tr>
                        <td class="p-2 text-center">{{ \Carbon\Carbon::parse($cost->date)->format('d/m/Y') }}</td>
                        <td class="p-2">{{ $cost->name }}</td>
                        <td class="p-2 text-center">{{ $cost->category }}</td>
                        <td class="p-2">{{ $cost->description ?? '-' }}</td>
                        @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
                        <td class="p-2 text-center">{{ $cost->branch->name ?? 'PUSAT' }}</td>
                        @endif
                        <td class="p-2 text-right">Rp {{ number_format($cost->amount) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ Auth::user()->role == 'admin' ? '6' : '5' }}" class="p-2 text-center">Data Kosong.</td></tr>
                    @endforelse
                </tbody>
                <tfoot style="border-top: 2px solid black; font-weight: bold; background-color: #f0f0f0;">
                    <tr>
                        <td colspan="{{ Auth::user()->role == 'admin' ? '5' : '4' }}" class="p-2 text-right uppercase">TOTAL PENGELUARAN</td>
                        <td class="p-2 text-right">Rp {{ number_format($total ?? 0) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>

    {{-- MODAL INPUT & EDIT (Header Biru) --}}
    <div id="modalBiaya" class="fixed inset-0 bg-black/50 z-[99] hidden flex items-center justify-center backdrop-blur-sm no-print">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden transform transition-all">
            {{-- Header Biru --}}
            <div class="bg-primary p-4 flex justify-between items-center">
                <h3 class="font-bold text-white text-sm uppercase tracking-wide">Input Biaya Baru</h3>
                <button onclick="document.getElementById('modalBiaya').classList.add('hidden')" class="text-white hover:text-gray-200 text-lg">&times;</button>
            </div>
            <form action="{{ route('biaya.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                @if(Auth::user()->role == 'admin' || Auth::user()->role == 'owner')
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Cabang</label>
                        <select name="branch_id" class="w-full border-gray-300 rounded-lg p-2.5 text-sm uppercase focus:ring-primary focus:border-primary">
                            @if(Auth::user()->branch_id)
                                <option value="{{ Auth::user()->branch_id }}" selected>{{ Auth::user()->branches->first()->name ?? 'CABANG SAYA' }}</option>
                                <option disabled>----------------</option>
                            @else
                                <option value="" disabled selected>-- PILIH CABANG --</option>
                            @endif

                            @foreach(\App\Models\Branch::all() as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                </div>
                @endif
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nama Pengeluaran</label>
                    <input type="text" name="name" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm uppercase focus:ring-primary focus:border-primary" placeholder="CONTOH: BAYAR LISTRIK BULANAN">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Kategori</label>
                        <select name="category" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm font-bold text-gray-700 focus:ring-primary focus:border-primary">
                            <option value="" disabled selected>-- PILIH --</option>
                            <option value="GAJI">GAJI & TUNJANGAN</option>
                            <option value="LISTRIK">LISTRIK / AIR / WIFI</option>
                            <option value="SEWA">SEWA TEMPAT</option>
                            <option value="ATK">ATK / PERLENGKAPAN</option>
                            <option value="MAINTENANCE">PEMELIHARAAN</option>
                            <option value="TRANSPORT">TRANSPORT / BBM</option>
                            <option value="PAJAK">PAJAK / LEGALITAS</option>
                            <option value="ENTERTAINMENT">JAMUAN TAMU</option>
                            <option value="SEMBAHYANG">SEMBAHYANG (BANTEN)</option>
                            <option value="LAINNYA">LAIN-LAIN</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Jumlah (Rp)</label>
                        <input type="number" name="amount" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm text-right focus:ring-primary focus:border-primary" placeholder="0">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan (Opsional)</label>
                    <textarea name="description" rows="2" class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-primary focus:border-primary"></textarea>
                </div>
                <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:opacity-90 transition text-sm uppercase shadow-lg transform active:scale-95">SIMPAN DATA</button>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="modalEditBiaya" class="fixed inset-0 bg-black/50 z-[99] hidden flex items-center justify-center backdrop-blur-sm no-print">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden transform transition-all">
            <div class="bg-yellow-500 p-4 flex justify-between items-center">
                <h3 class="font-bold text-white text-sm uppercase tracking-wide">Edit Data Biaya</h3>
                <button type="button" onclick="document.getElementById('modalEditBiaya').classList.add('hidden')" class="text-white font-bold text-lg">&times;</button>
            </div>
            
            <form id="formEditBiaya" action="" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT') 
                
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal</label>
                    <input type="date" id="edit_date" name="date" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-yellow-500 focus:border-yellow-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nama Pengeluaran</label>
                    <input type="text" id="edit_name" name="name" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm uppercase focus:ring-yellow-500 focus:border-yellow-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Kategori</label>
                        <select id="edit_category" name="category" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="GAJI">GAJI & TUNJANGAN</option>
                            <option value="LISTRIK">LISTRIK / AIR / WIFI</option>
                            <option value="SEWA">SEWA TEMPAT</option>
                            <option value="ATK">ATK / PERLENGKAPAN</option>
                            <option value="MAINTENANCE">PEMELIHARAAN</option>
                            <option value="TRANSPORT">TRANSPORT / BBM</option>
                            <option value="PAJAK">PAJAK / LEGALITAS</option>
                            <option value="ENTERTAINMENT">JAMUAN TAMU</option>
                            <option value="SEMBAHYANG">SEMBAHYANG (BANTEN)</option>
                            <option value="LAINNYA">LAIN-LAIN</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Jumlah (Rp)</label>
                        <input type="number" id="edit_amount" name="amount" required class="w-full border-gray-300 rounded-lg p-2.5 text-sm text-right focus:ring-yellow-500 focus:border-yellow-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan</label>
                    <textarea id="edit_description" name="description" rows="2" class="w-full border-gray-300 rounded-lg p-2.5 text-sm focus:ring-yellow-500 focus:border-yellow-500"></textarea>
                </div>
                <button type="submit" class="w-full bg-yellow-500 text-white font-bold py-3 rounded-lg hover:bg-yellow-600 transition text-sm uppercase shadow-lg">UPDATE PERUBAHAN</button>
            </form>
        </div>
    </div>

</div>

<script>
    // FUNGSI EDIT
    function editBiaya(id, date, name, category, amount, description) {
        document.getElementById('modalEditBiaya').classList.remove('hidden');
        let url = "{{ route('biaya.update', ':id') }}";
        url = url.replace(':id', id);
        document.getElementById('formEditBiaya').action = url;
        document.getElementById('edit_date').value = date;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_category').value = category;
        document.getElementById('edit_amount').value = amount;
        document.getElementById('edit_description').value = description;
    }

    // FUNGSI EXPORT EXCEL
    function exportToExcel() {
        var month = "{{ $month }}";
        var year = "{{ $year }}";
        var fileName = 'Laporan_Biaya_' + month + '_' + year + '.xls';
        
        // Ambil tabel khusus (exportTable) yang bersih
        var tableHtml = document.getElementById('exportTable').outerHTML;
        
        var excelContent = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <style>
                    body { font-family: sans-serif; }
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #000000; padding: 5px; }
                    th { background-color: #f0f0f0; text-align: center; font-weight: bold; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                </style>
            </head>
            <body>
                <h2>LAPORAN BIAYA OPERASIONAL</h2>
                <p>Periode: ${month}/${year}</p>
                <br>
                ${tableHtml}
            </body>
            </html>
        `;

        var blob = new Blob([excelContent], {type: "application/vnd.ms-excel"});
        var link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = fileName;
        link.click();
    }
</script>
@endsection