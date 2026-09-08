@extends('layouts.app')

@section('content')
{{-- 
    WRAPPER UTAMA DENGAN ALPINE.JS 
    truncateModalOpen: Mengontrol buka/tutup pop-up hapus database
--}}
<div x-data="{ truncateModalOpen: false }" class="min-h-screen bg-gray-50 pb-12">

    {{-- HEADER & TOMBOL RESET --}}
    <div class="bg-white p-4 shadow-sm border-b flex flex-wrap justify-between items-center rounded-xl mb-6 gap-4">
        <div class="flex items-center gap-3">
            <div class="bg-primary/10 p-2 rounded-lg text-primary">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 01-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <div>
                <h2 class="font-bold text-gray-800 text-lg">Database DTTOT</h2>
                <p class="text-xs text-gray-500">Daftar Terduga Teroris & Organisasi Teror (Sumber: Mabes Polri)</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            {{-- TOMBOL MEMBUKA MODAL RESET --}}
            <button type="button" 
                    @click="truncateModalOpen = true" 
                    class="bg-red-100 text-red-600 px-4 py-2 rounded-lg text-xs font-bold border border-red-200 hover:bg-red-600 hover:text-white transition flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                KOSONGKAN DATABASE (RESET)
            </button>

            {{-- INDIKATOR STATUS --}}
            <div class="hidden md:flex bg-green-50 text-green-700 px-3 py-2 rounded-lg text-xs font-bold border border-green-100 items-center gap-2">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                LIVE SYSTEM
            </div>
        </div>
    </div>

    {{-- BAGIAN 1: UPDATE DATABASE (UPLOAD EXCEL) --}}
    <div class="mb-8">
        <div class="bg-primary rounded-xl shadow-lg overflow-hidden relative">
            {{-- Decoration --}}
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            </div>
            
            <div class="p-6 flex flex-col md:flex-row items-center gap-6 relative z-10">
                <div class="flex-1 text-white">
                    <h3 class="font-bold text-secondary text-xl mb-2">Update Database DTTOT (Excel)</h3>
                    <p class="text-sm text-blue-100 leading-relaxed mb-4">
                        Upload file Excel resmi terbaru dari Mabes Polri (`.xlsx` / `.xls` / `.csv`).<br>
                        <span class="text-yellow-300 font-bold">Tips:</span> Sebaiknya tekan tombol <strong>"KOSONGKAN DATABASE"</strong> di atas terlebih dahulu sebelum mengupload data baru agar tidak terjadi duplikasi.
                    </p>
                    <div class="flex gap-2">
                        <span class="text-[10px] bg-white/20 px-2 py-1 rounded text-white border border-white/20">Format: .XLSX / .XLS / .CSV</span>
                        <span class="text-[10px] bg-white/20 px-2 py-1 rounded text-white border border-white/20">Max: 20MB</span>
                    </div>
                </div>

                <div class="w-full md:w-1/3 bg-white/5 p-4 rounded-lg border border-white/10 backdrop-blur-sm">
                    <form action="{{ route('compliance.dttot.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="relative group cursor-pointer mb-3">
                            <input type="file" name="excel_file" accept=".xlsx, .xls, .csv" required 
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20"
                                   onchange="document.getElementById('fileNameDisplay').innerText = this.files[0].name">
                            
                            <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-4 text-center group-hover:border-secondary transition shadow-sm">
                                <svg class="w-8 h-8 mx-auto text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p id="fileNameDisplay" class="text-sm font-bold text-gray-600 group-hover:text-secondary truncate">Klik untuk pilih Excel (.xlsx)</p>
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-secondary text-primary font-bold py-2.5 rounded-lg shadow-lg hover:bg-yellow-400 transition flex justify-center items-center gap-2 transform active:scale-[0.98]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Mulai Import Excel
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: DAFTAR TERDUGA (TABEL DATA EXCEL) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b flex flex-wrap justify-between items-center gap-4">
            <div class="flex items-center gap-2">
                <div class="bg-red-100 p-1.5 rounded text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800">Daftar Terduga DTTOT</h3>
                    <p class="text-xs text-gray-500">Total Data Tersimpan: <span class="font-bold text-primary">{{ $lists->total() }}</span></p>
                </div>
            </div>
            
            {{-- Search Function --}}
            <div class="relative w-full md:w-64">
                <form action="{{ route('compliance.dttot.index') }}" method="GET">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Cari Nama / Alias + Enter..." 
                           class="w-full pl-8 pr-4 py-2 rounded-lg border border-gray-300 text-sm focus:ring-primary focus:border-primary shadow-sm"
                           onkeypress="if(event.keyCode == 13) { this.form.submit(); }"> 
                    <button type="submit" class="absolute left-2.5 top-2.5 text-gray-400 hover:text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </form>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-primary text-white text-xs uppercase">
                    <tr>
                        <th class="p-3 text-center w-12">#</th>
                        <th class="p-3 w-64">Nama & Alias</th>
                        <th class="p-3 w-32">Kode / Tipe</th>
                        <th class="p-3 w-48">Tempat & Tgl Lahir</th>
                        <th class="p-3 w-32">Kewarganegaraan</th>
                        <th class="p-3 w-64">Alamat & Deskripsi</th>
                        <th class="p-3 text-center w-16">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lists as $index => $item)
                    <tr class="hover:bg-red-50 transition group align-top text-xs">
                        <td class="p-3 text-gray-500 text-center font-mono">{{ $loop->iteration + ($lists->currentPage() - 1) * $lists->perPage() }}</td>
                        
                        {{-- Nama & Alias --}}
                        <td class="p-3">
                            <span class="font-bold text-gray-900 text-sm block">{{ $item->name }}</span>
                        </td>

                        {{-- Kode Densus & Terduga --}}
                        <td class="p-3">
                            <span class="font-mono bg-blue-50 text-blue-700 px-2 py-0.5 rounded border border-blue-200 font-bold inline-block mb-1">
                                {{ $item->densus_code ?? '-' }}
                            </span>
                            <div>
                                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase {{ $item->entity_type == 'Korporasi' ? 'bg-purple-100 text-purple-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $item->entity_type ?? 'Orang' }}
                                </span>
                            </div>
                        </td>

                        {{-- TTL --}}
                        <td class="p-3 text-gray-600 leading-relaxed">
                            <div><strong>Tempat:</strong> {{ $item->birth_place ?? '-' }}</div>
                            <div><strong>Tgl Lahir:</strong> {{ $item->birth_date ?? '-' }}</div>
                        </td>

                        {{-- WN --}}
                        <td class="p-3 text-gray-600">
                            {{ $item->nationality ?? '-' }}
                        </td>

                        {{-- Alamat & Deskripsi --}}
                        <td class="p-3 text-gray-600 leading-relaxed">
                            @if($item->address)
                                <div class="mb-1"><strong>Alamat:</strong> {{ Str::limit($item->address, 100) }}</div>
                            @endif
                            <div class="max-h-20 overflow-y-auto text-[11px] text-gray-500">
                                {!! nl2br(e($item->description)) !!}
                            </div>
                        </td>

                        {{-- Aksi Hapus --}}
                        <td class="p-3 text-center">
                            <form action="{{ route('compliance.dttot.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus satu data ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600 transition p-1" title="Hapus Item">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center text-gray-400">
                            <div class="flex flex-col items-center justify-center py-8">
                                <div class="bg-gray-100 p-4 rounded-full mb-3">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 01-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>

                                @if(request('search'))
                                    <p class="font-bold text-gray-800 text-lg">Pencarian Tidak Ditemukan</p>
                                    <p class="text-sm text-gray-500 mt-1 mb-4">
                                        Tidak ada data terduga dengan nama/alias "<span class="font-bold text-red-500">{{ request('search') }}</span>"
                                    </p>
                                    <a href="{{ route('compliance.dttot.index') }}" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-yellow-500 transition shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Reset Pencarian
                                    </a>
                                @else
                                    <p class="font-bold text-gray-500 text-lg">Database DTTOT Kosong</p>
                                    <p class="text-sm text-gray-400 mt-1 max-w-sm mx-auto">
                                        Sistem aman bersih. Silakan upload file Excel baru jika ada update dari Mabes Polri.
                                    </p>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-t bg-gray-50">
            {{ $lists->links() }}
        </div>
    </div>

    {{-- MODAL KONFIRMASI TRUNCATE (RESET) --}}
    <div x-show="truncateModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        
        <div class="bg-white rounded-xl shadow-2xl p-6 max-w-sm w-full transform transition-all border-t-4 border-red-600"
             @click.away="truncateModalOpen = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100">
            
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4 animate-bounce">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h3 class="text-lg leading-6 font-bold text-gray-900">PERINGATAN KERAS!</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500 mb-2">
                        Anda yakin ingin <strong>MENGHAPUS SEMUA</strong> data DTTOT?
                    </p>
                    <p class="text-xs text-red-500 bg-red-50 p-2 rounded border border-red-100">
                        Tindakan ini tidak bisa dibatalkan. Lakukan hanya jika Anda ingin meng-update data baru dari Polri.
                    </p>
                </div>
            </div>

            {{-- TOMBOL AKSI --}}
            <div class="mt-6 flex gap-3">
                <button @click="truncateModalOpen = false" type="button" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:text-sm font-bold transition">
                    Batal
                </button>
                
                <form action="{{ route('compliance.dttot.truncate') }}" method="POST" class="w-full">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:text-sm font-bold transition transform active:scale-95">
                        YA, HAPUS SEMUA
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

{{-- SCRIPT SWEETALERT --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    @if(session('success'))
        Swal.fire({
            title: 'BERHASIL!',
            text: @json(session('success')),
            icon: 'success',
            confirmButtonText: 'OK, SIAP',
            confirmButtonColor: '#0A2647',
            width: '600px',
            padding: '2em',
        });
    @endif

    @if(session('error'))
        Swal.fire({
            title: 'GAGAL!',
            text: @json(session('error')),
            icon: 'error',
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#EF4444',
        });
    @endif

    @if($errors->any())
        Swal.fire({
            title: 'Perhatian',
            html: {!! json_encode(implode('<br>', $errors->all())) !!},
            icon: 'warning',
            confirmButtonText: 'Perbaiki',
            confirmButtonColor: '#f50b0b'
        });
    @endif
</script>
@endsection