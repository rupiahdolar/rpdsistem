@extends('layouts.app')

@section('content')
<div class="flex flex-col h-full pb-20">

    {{-- HEADER HALAMAN --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            
            {{-- JUDUL --}}
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2 border-l-4 border-[#fc3858] pl-3">
                    <svg class="w-6 h-6 text-[#fc3858]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Laporan LTKM
                </h2>
                <p class="text-xs text-gray-500 mt-1 pl-3.5 font-bold uppercase tracking-wide">Laporan Transaksi Keuangan Mencurigakan (Suspicious Transaction Report)</p>
            </div>

            {{-- TOMBOL BUAT MANUAL --}}
            <button onclick="document.getElementById('modalLTKM').classList.remove('hidden')" class="bg-[#fc3858] text-white px-4 py-2 rounded shadow hover:bg-red-700 transition flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Buat Laporan Baru
            </button>
        </div>
    </div>

    {{-- INFO SINGKAT --}}
    <div class="mb-6 bg-blue-50 border-l-4 border-blue-600 p-4 rounded shadow-sm flex items-start gap-3">
        <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <div>
            <h4 class="font-bold text-blue-800 text-xs uppercase tracking-wide">Informasi Kepatuhan</h4>
            <p class="text-xs text-blue-700 mt-1">
                Laporan ini berisi daftar nasabah yang terindikasi melakukan transaksi mencurigakan sesuai kriteria PPATK. 
                Data ini bersifat <strong>RAHASIA</strong>.
            </p>
        </div>
    </div>

    {{-- DAFTAR LAPORAN (GRID CARD) --}}
    <div class="grid grid-cols-1 gap-4">
        @forelse($reports as $report)
        <div class="bg-white rounded shadow-sm border border-gray-200 border-l-4 border-l-red-500 p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:shadow-md transition">
            
            {{-- Bagian Kiri: Info Utama --}}
            <div class="flex-1 w-full">
                <div class="flex items-center justify-between md:justify-start gap-3 mb-2">
                    <h3 class="font-bold text-gray-800 text-base uppercase tracking-wide">{{ $report->customer_name }}</h3>
                    <span class="bg-red-100 text-red-600 text-[10px] px-2 py-0.5 rounded border border-red-200 font-bold uppercase tracking-wider">Suspicious</span>
                </div>
                
                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 mb-3">
                    <span class="flex items-center gap-1 font-mono bg-gray-100 px-2 py-1 rounded border border-gray-200">
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                        ID: {{ $report->identity_no }}
                    </span>
                    <span class="text-gray-300">|</span>
                    <span>
                        Pelapor: <strong>{{ $report->user->name ?? 'System' }}</strong>
                    </span>
                </div>

                <div class="bg-yellow-50 p-3 rounded border border-yellow-200 text-xs text-gray-800 italic relative">
                    <svg class="w-4 h-4 text-yellow-500 absolute top-3 left-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                    <span class="pl-6 block text-justify font-medium">"{{ $report->suspicious_reason }}"</span>
                </div>
            </div>
            
            {{-- Bagian Kanan: Status, Tanggal & Tombol Aksi --}}
            <div class="text-right flex flex-col items-end min-w-[160px] md:border-l md:pl-4 border-gray-100 w-full md:w-auto">
                <span class="text-[9px] font-bold text-gray-400 uppercase mb-1 tracking-wider">Waktu Laporan</span>
                <span class="text-xs text-gray-700 font-mono font-bold mb-3 bg-gray-50 px-2 py-1 rounded border border-gray-200">
                    {{ \Carbon\Carbon::parse($report->created_at)->format('d/m/Y H:i') }}
                </span>
                
                <div class="flex items-center gap-2 mb-2">
                    @if($report->status == 'PENDING')
                        <span class="bg-yellow-100 text-yellow-700 px-3 py-1.5 rounded text-[10px] font-bold border border-yellow-200 flex items-center gap-1 uppercase tracking-wide">
                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full animate-pulse"></span> Menunggu Review
                        </span>
                    @else
                        <span class="bg-green-100 text-green-700 px-3 py-1.5 rounded text-[10px] font-bold border border-green-200 flex items-center gap-1 uppercase tracking-wide">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Sudah Dilaporkan
                        </span>
                    @endif
                </div>

                {{-- TOMBOL HAPUS --}}
                <form action="{{ route('compliance.ltkm.destroy', $report->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-bold uppercase tracking-wider flex items-center gap-1 transition mt-1 p-1 hover:bg-red-50 rounded">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Hapus Laporan
                    </button>
                </form>
            </div>

        </div>
        @empty
        {{-- State Kosong --}}
        <div class="bg-white rounded-lg shadow-sm p-12 text-center border border-gray-200">
            <div class="inline-block p-4 rounded-full bg-green-50 text-green-500 mb-4">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="font-bold text-gray-800 text-lg mb-1">Tidak Ada Laporan</h3>
            <p class="text-gray-500 text-xs">Belum ada transaksi yang ditandai mencurigakan.</p>
        </div>
        @endforelse
    </div>

    {{-- MODAL FORM --}}
    <div id="modalLTKM" class="fixed inset-0 bg-black/60 z-[99] hidden flex items-center justify-center backdrop-blur-sm transition-opacity" onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg mx-4 overflow-hidden transform transition-all">
            
            {{-- Header Modal --}}
            <div class="bg-[#fc3858] p-4 flex justify-between items-center border-b border-red-700">
                <h3 class="font-bold text-white text-sm uppercase tracking-wide flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Input Laporan Manual
                </h3>
                <button onclick="document.getElementById('modalLTKM').classList.add('hidden')" class="text-white/80 hover:text-white transition font-bold text-lg">
                    &times;
                </button>
            </div>
            
            {{-- Form --}}
            <form action="{{ route('compliance.ltkm.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Nama Nasabah / Terduga <span class="text-red-500">*</span></label>
                    <input type="text" name="customer_name" required class="w-full border-gray-300 rounded p-2.5 text-sm focus:ring-[#fc3858] focus:border-[#fc3858] uppercase font-bold bg-gray-50 focus:bg-white transition" placeholder="NAMA LENGKAP">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">No. Identitas (Opsional)</label>
                    <input type="text" name="identity_no" class="w-full border-gray-300 rounded p-2.5 text-sm focus:ring-[#fc3858] focus:border-[#fc3858] font-mono uppercase" placeholder="KTP / PASPOR">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Indikasi Mencurigakan <span class="text-red-500">*</span></label>
                    <textarea name="suspicious_reason" rows="4" required class="w-full border-gray-300 rounded p-2.5 text-sm focus:ring-[#fc3858] focus:border-[#fc3858]" placeholder="Jelaskan detail perilaku, pola transaksi, atau profil yang tidak sesuai..."></textarea>
                </div>

                <div class="bg-yellow-50 p-3 rounded border border-yellow-200 text-[10px] text-yellow-800 flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                    <p><span class="font-bold">PERINGATAN:</span> Laporan ini bersifat rahasia (Anti-Tipping Off). Dilarang memberitahukan kepada nasabah bahwa transaksi mereka sedang dilaporkan.</p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-[#fc3858] text-white font-bold py-3 rounded shadow hover:bg-red-700 transition transform active:scale-[0.98] text-xs uppercase tracking-widest">
                        SIMPAN LAPORAN
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection