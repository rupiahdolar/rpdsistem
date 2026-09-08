<aside class="w-64 bg-primary border-r border-gray-200 hidden md:flex flex-col shadow-xl z-20 h-screen fixed inset-y-0 left-0 font-sans text-sm text-white transition-colors duration-300">
    
    {{-- BAGIAN ATAS: LOGO & JUDUL --}}
    <div class="flex-shrink-0 z-10 bg-primary">
        <div class="h-16 flex items-center px-6 border-b border-white/20 shadow-sm">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group">
                {{-- Logo --}}
                <img src="{{ asset('img/logo.png') }}" class="w-10 h-10 object-contain drop-shadow-sm transition-transform group-hover:scale-110" alt="Logo">
                {{-- Teks Nama PT --}}
                <span class="text-white font-bold text-lg tracking-wide uppercase">
                    RUPIAH DOLAR
                </span>
            </a>
        </div>
    </div>

    {{-- BAGIAN TENGAH: MENU NAVIGASI --}}
    <nav class="flex-1 px-3 mt-6 space-y-1 overflow-y-auto custom-scrollbar">
        
        {{-- 1. DASHBOARD (Hanya Admin/Owner) --}}
        @if(in_array(Auth::user()->role, ['admin', 'owner']))
        <a href="{{ route('admin.dashboard') }}" 
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.dashboard') ? 'bg-white text-primary font-bold shadow-md' : 'text-white hover:bg-black/10' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Dashboard
        </a>
        @endif

        {{-- 2. INPUT TRANSAKSI (Hanya Cashier) --}}
        @if(Auth::user()->role == 'cashier')
        <a href="{{ route('transaction.index') }}" 
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 group {{ request()->routeIs('transaction.index') ? 'bg-white text-primary font-bold shadow-md' : 'text-white hover:bg-black/10' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Input Transaksi
        </a>
        @endif

        {{-- LABEL SEPARATOR --}}
        <div class="pt-5 pb-2 pl-4 text-[11px] font-bold text-white/70 uppercase tracking-widest border-t border-white/10 mt-2">
            Operasional
        </div>

        {{-- 3. DATA NASABAH (Dropdown) --}}
        <div x-data="{ open: {{ request()->routeIs('nasabah.*') ? 'true' : 'false' }} }" class="mb-1">
            <button @click="open = !open" 
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 group focus:outline-none"
                    :class="open ? 'bg-black/10 text-white font-bold' : 'text-white hover:bg-black/10'">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>Data Nasabah</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180 text-white' : 'text-white/70'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            
            {{-- AREA DROPDOWN --}}
            <div x-show="open" class="bg-black/20 rounded-lg mt-1 py-1 overflow-hidden shadow-inner" style="display: none;">
                {{-- Link 1: Data Nasabah --}}
                <a href="{{ route('nasabah.index') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('nasabah.index') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">
                    Data Nasabah
                </a>
                
                {{-- Link 2: Analisis KYC --}}
                <a href="{{ route('nasabah.kyc') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('nasabah.kyc') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">
                    Analisis KYC
                </a>

                {{-- [BARU] Link 3: Assessment Risiko (APU-PPT) --}}
                <a href="{{ route('nasabah.assessment') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('nasabah.assessment') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">
                    Assessment Risiko
                </a>
            </div>
        </div>

        {{-- 4. LAPORAN MUTASI (Dropdown) --}}
        <div x-data="{ open: {{ request()->routeIs('mutasi.*') ? 'true' : 'false' }} }" class="mb-1">
            <button @click="open = !open" 
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 group focus:outline-none"
                    :class="open ? 'bg-black/10 text-white font-bold' : 'text-white hover:bg-black/10'">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span>Laporan Mutasi</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180 text-white' : 'text-white/70'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="bg-black/20 rounded-lg mt-1 py-1 overflow-hidden shadow-inner" style="display: none;">
                <a href="{{ route('mutasi.harian') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('mutasi.harian') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Mutasi Harian</a>
                <a href="{{ route('mutasi.bulanan') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('mutasi.bulanan') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Mutasi Bulanan</a>
            </div>
        </div>

        {{-- LABEL SEPARATOR --}}
        <div class="pt-5 pb-2 pl-4 text-[11px] font-bold text-white/70 uppercase tracking-widest border-t border-white/10 mt-2">
            Accounting & Finance
        </div>

        {{-- 5. AKUNTANSI & INPUT (Dropdown) --}}
        <div x-data="{ open: {{ request()->routeIs('keuangan.biaya') || request()->routeIs('reports.cashflow') || request()->routeIs('accounting.*') || request()->routeIs('internal-mutation.*') ? 'true' : 'false' }} }" class="mb-1">
            <button @click="open = !open" 
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 group focus:outline-none"
                    :class="open ? 'bg-black/10 text-white font-bold' : 'text-white hover:bg-black/10'">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span>Akuntansi & Input</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180 text-white' : 'text-white/70'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="bg-black/20 rounded-lg mt-1 py-1 overflow-hidden shadow-inner" style="display: none;">
                <a href="{{ route('keuangan.biaya') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('keuangan.biaya') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Biaya Operasional</a>
                <a href="{{ route('reports.cashflow') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('reports.cashflow') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Arus Kas</a>
                <a href="{{ route('internal-mutation.index') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('internal-mutation.*') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Tarik / Setor Bank</a>
                @if(in_array(Auth::user()->role, ['admin', 'owner']))
                    
                    <a href="{{ route('accounting.assets.index') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('accounting.assets.index') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Aset Tetap</a>
                    <a href="{{ route('accounting.journals.index') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('accounting.journals.index') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Jurnal Umum</a>
                    <a href="{{ route('accounting.coa.index') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('accounting.coa.index') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Chart of Accounts</a>
                    
                    {{-- Tutup Buku --}}
                    <a href="{{ route('accounting.closing.index') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('accounting.closing.index') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Tutup Buku</a>
                @endif
            </div>
        </div>

        {{-- 6. LAPORAN KEUANGAN (Admin/Owner Only) --}}
        @if(in_array(Auth::user()->role, ['admin', 'owner']))
        <div x-data="{ open: {{ request()->routeIs('keuangan.buku_besar') || request()->routeIs('keuangan.labarugi') || request()->routeIs('keuangan.neraca') || request()->routeIs('keuangan.ekuitas') ? 'true' : 'false' }} }" class="mb-1">
            <button @click="open = !open" 
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 group focus:outline-none"
                    :class="open ? 'bg-black/10 text-white font-bold' : 'text-white hover:bg-black/10'">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Laporan Keuangan</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180 text-white' : 'text-white/70'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="bg-black/20 rounded-lg mt-1 py-1 overflow-hidden shadow-inner" style="display: none;">
                <a href="{{ route('keuangan.buku_besar') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('keuangan.buku_besar') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Buku Besar</a>
                <a href="{{ route('keuangan.labarugi') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('keuangan.labarugi') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Laba / Rugi</a>
                <a href="{{ route('keuangan.ekuitas') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('keuangan.ekuitas') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Ekuitas</a>
                <a href="{{ route('keuangan.neraca') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('keuangan.neraca') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Neraca</a>
            </div>
        </div>
        @endif

        {{-- 7. APU & PPT --}}
        @if(in_array(Auth::user()->role, ['admin', 'owner']))
        <div x-data="{ open: {{ request()->routeIs('compliance.*') ? 'true' : 'false' }} }" class="mb-1">
            <button @click="open = !open" 
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 group focus:outline-none"
                    :class="open ? 'bg-black/10 text-white font-bold' : 'text-white hover:bg-black/10'">
                <div class="flex items-center gap-3">
                    {{-- Icon Shield untuk Kepatuhan --}}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span>APU-PPT & DTTOT</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180 text-white' : 'text-white/70'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="bg-black/20 rounded-lg mt-1 py-1 overflow-hidden shadow-inner" style="display: none;">
                <a href="{{ route('compliance.dttot.index') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('compliance.dttot.*') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Database DTTOT</a>
                <a href="{{ route('compliance.ltkt.index') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('compliance.ltkt.*') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Laporan LTKT (>500jt)</a>
                <a href="{{ route('compliance.ltkm.index') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('compliance.ltkm.*') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Laporan LTKM</a>
            </div>
        </div>
        @endif

        {{-- 8. ADMIN ZONE (Pengaturan) --}}
        @if(in_array(Auth::user()->role, ['admin', 'owner']))
        <div class="pt-5 pb-2 pl-4 text-[11px] font-bold text-white/70 uppercase tracking-widest border-t border-white/10 mt-2">
            Pengaturan Sistem
        </div>
        <div x-data="{ open: {{ request()->routeIs('admin.*') ? 'true' : 'false' }} }" class="mb-1">
             <button @click="open = !open" 
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 group focus:outline-none"
                    :class="open ? 'bg-black/10 text-white font-bold' : 'text-white hover:bg-black/10'">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Admin Zone</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180 text-white' : 'text-white/70'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="bg-black/20 rounded-lg mt-1 py-1 overflow-hidden shadow-inner" style="display: none;">
                <a href="{{ route('admin.capital.index') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('admin.capital.*') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Set Modal Awal</a>
                <a href="{{ route('admin.branches.index') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('admin.branches.*') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Kantor Cabang</a>
                <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 text-sm transition pl-12 {{ request()->routeIs('admin.users.*') ? 'text-white font-bold' : 'text-white/80 hover:text-white hover:bg-black/10' }}">Manajemen Account</a>
            </div>
        </div>
        @endif

        <div class="h-24"></div> 
    </nav>

    {{-- FOOTER: UBAH PASSWORD & LOGOUT --}}
    <div class="flex-shrink-0 p-4 border-t border-white/20 bg-primary z-10">
        <a href="{{ route('settings.index') }}" class="flex items-center px-4 py-2 mb-2 text-sm text-white/80 hover:text-white hover:bg-black/10 rounded-lg transition-all font-bold group">
            <svg class="w-4 h-4 mr-3 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Ubah Password
        </a>

        {{-- LOGIKA LOGOUT --}}
        @if(Auth::user()->role !== 'cashier')
            {{-- ADMIN/OWNER: Tombol Logout Biasa --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full bg-white hover:bg-gray-100 text-primary py-2.5 rounded-lg text-xs font-bold shadow-md tracking-wider uppercase transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Keluar Aplikasi
                </button>
            </form>
        @else
            {{-- KASIR: Tombol Logout Disembunyikan & Diganti Petunjuk --}}
            <div class="bg-black/20 rounded-lg p-3 text-center border border-white/10">
                <p class="text-[10px] text-white/80 leading-tight">
                    Untuk keluar, silakan lakukan <br>
                    <span class="font-bold text-white">TUTUP SHIFT</span> <br>
                    pada menu Input Transaksi.
                </p>
                <a href="{{ route('transaction.index') }}" class="mt-2 block w-full bg-white/10 hover:bg-white/20 text-white py-1.5 rounded text-[10px] font-bold transition">
                    Ke Input Transaksi →
                </a>
            </div>
        @endif
    </div>
</aside>