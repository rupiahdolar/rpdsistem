@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto pb-20">
    
    {{-- 1. HEADER HALAMAN --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            {{-- Judul dengan Border Kiri Tebal (Style Baru) --}}
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2 border-l-4 border-primary pl-3">
                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Master Chart of Accounts (COA)
            </h1>
            <p class="text-xs text-gray-500 mt-1 pl-3.5 font-bold uppercase tracking-wide">Pengaturan Akun & Saldo Awal</p>
        </div>
        
        {{-- TOMBOL TAMBAH: Primary Blue --}}
        <button onclick="document.getElementById('modalCreate').classList.remove('hidden')" class="bg-primary hover:opacity-90 text-white px-5 py-2.5 rounded-lg shadow-lg transition flex items-center gap-2 text-xs font-bold uppercase tracking-wider transform active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            TAMBAH AKUN
        </button>
    </div>

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
    @endif

    {{-- ALERT ERROR --}}
    @if($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
            <ul class="list-disc pl-5 text-sm font-medium">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- TABEL DATA (Clean White Style) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-sm text-left">
            {{-- HEADER TABEL: Putih dengan Text Primary --}}
            <thead class="bg-white text-primary font-bold uppercase text-xs border-b-2 border-primary">
                <tr>
                    <th class="p-4 w-32 border-r border-gray-100">Kode</th>
                    <th class="p-4 border-r border-gray-100">Nama Akun</th>
                    <th class="p-4 w-32 border-r border-gray-100">Tipe</th>
                    <th class="p-4 w-48 text-right border-r border-gray-100">Saldo Awal (Setup)</th>
                    <th class="p-4 w-32 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($accounts as $type => $groupAccounts)
                    {{-- GROUP HEADER --}}
                    <tr class="bg-gray-50 border-t border-b border-gray-200">
                        <td colspan="5" class="px-4 py-2 font-bold text-gray-500 uppercase tracking-widest text-[10px]">
                            {{ $type }}
                        </td>
                    </tr>
                    
                    @foreach($groupAccounts as $acc)
                    <tr class="hover:bg-blue-50/20 transition group">
                        {{-- KODE AKUN --}}
                        <td class="px-4 py-3 border-r border-gray-100">
                            <span class="font-mono font-bold text-primary bg-blue-50 px-2 py-1 rounded text-xs border border-blue-100">
                                {{ $acc->code }}
                            </span>
                        </td>
                        
                        {{-- NAMA AKUN --}}
                        <td class="px-4 py-3 font-bold text-gray-700 group-hover:text-primary transition border-r border-gray-100 uppercase text-xs">
                            {{ $acc->name }}
                        </td>
                        
                        {{-- TIPE --}}
                        <td class="px-4 py-3 border-r border-gray-100">
                            <span class="px-2 py-1 rounded text-[10px] font-bold border bg-gray-50 text-gray-600 border-gray-200 uppercase">
                                {{ $acc->type }}
                            </span>
                        </td>
                        
                        {{-- SALDO AWAL --}}
                        <td class="px-4 py-3 text-right font-mono font-bold text-gray-700 border-r border-gray-100 text-xs">
                            @if($acc->opening_balance != 0)
                                {{ number_format($acc->opening_balance) }}
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>
                        
                        {{-- AKSI --}}
                        <td class="px-4 py-3 text-center flex justify-center gap-2">
                            <button onclick="editAccount({{ $acc }})" class="text-gray-400 hover:text-primary bg-gray-50 p-1.5 rounded hover:bg-blue-50 transition border border-gray-200 hover:border-blue-200" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            
                            @if(!$acc->is_locked)
                            <form action="{{ route('accounting.coa.destroy', $acc->id) }}" method="POST" onsubmit="return confirm('Yakin hapus akun ini?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600 bg-gray-50 p-1.5 rounded hover:bg-red-50 transition border border-gray-200 hover:border-red-200" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- MODAL CREATE --}}
<div id="modalCreate" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all scale-100 border border-gray-200">
        {{-- HEADER MODAL: Primary Blue --}}
        <div class="bg-primary px-6 py-4 border-b border-white/10 flex justify-between items-center">
            <h3 class="text-white font-bold text-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Tambah Akun Baru
            </h3>
            <button onclick="document.getElementById('modalCreate').classList.add('hidden')" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
        </div>
        
        <form action="{{ route('accounting.coa.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Tipe Akun</label>
                    <select name="type" class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary font-medium" required>
                        <option value="ASSET">HARTA (ASSET)</option>
                        <option value="LIABILITY">KEWAJIBAN (HUTANG)</option>
                        <option value="EQUITY">MODAL (EQUITY)</option>
                        <option value="REVENUE">PENDAPATAN (REVENUE)</option>
                        <option value="EXPENSE">BEBAN (EXPENSE)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Posisi Normal</label>
                    <select name="normal_balance" class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary font-medium" required>
                        <option value="DEBIT">DEBIT (Bertambah di Debit)</option>
                        <option value="CREDIT">KREDIT (Bertambah di Kredit)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Kode Akun</label>
                    <input type="text" name="code" class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary font-mono font-bold" placeholder="1-1001" required>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Nama Akun</label>
                    <input type="text" name="name" class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary font-bold uppercase" placeholder="Contoh: KAS BESAR" required>
                </div>
            </div>

            {{-- INPUT SALDO AWAL --}}
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mt-2">
                <label class="block text-xs font-bold text-yellow-800 mb-1 uppercase">Saldo Awal (Opening Balance)</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-yellow-600 font-bold text-sm">Rp</span>
                    <input type="number" name="opening_balance" class="w-full pl-10 border-yellow-300 rounded-lg text-sm focus:ring-yellow-500 focus:border-yellow-500 font-bold text-gray-700" placeholder="0">
                </div>
                <p class="text-[10px] text-yellow-700 mt-1 font-medium">*Isi nominal uang yang ada saat ini (Hanya untuk akun Harta/Utang/Modal).</p>
            </div>

            <div class="mt-6 flex justify-end pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modalCreate').classList.add('hidden')" class="mr-3 px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-800 transition uppercase tracking-wide">Batal</button>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg font-bold hover:opacity-90 transition shadow-lg text-sm uppercase tracking-wider">Simpan Akun</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT --}}
<div id="modalEdit" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all scale-100 border border-gray-200">
        {{-- HEADER MODAL EDIT: Primary Blue (Seragam) --}}
        <div class="bg-primary px-6 py-4 border-b border-white/10 flex justify-between items-center">
            <h3 class="text-white font-bold text-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Edit Akun
            </h3>
            <button onclick="document.getElementById('modalEdit').classList.add('hidden')" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
        </div>
        
        <form id="formEdit" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            
            <div id="lockedMessage" class="hidden bg-red-50 text-red-600 text-xs font-bold p-3 rounded border border-red-100 mb-2 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                <span>AKUN SISTEM (LOCKED): Kode & Tipe tidak dapat diubah.</span>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Tipe Akun</label>
                    <select id="editType" name="type" class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary font-medium">
                        <option value="ASSET">HARTA (ASSET)</option>
                        <option value="LIABILITY">KEWAJIBAN (HUTANG)</option>
                        <option value="EQUITY">MODAL (EQUITY)</option>
                        <option value="REVENUE">PENDAPATAN (REVENUE)</option>
                        <option value="EXPENSE">BEBAN (EXPENSE)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Posisi Normal</label>
                    <select id="editNormal" name="normal_balance" class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary font-medium">
                        <option value="DEBIT">DEBIT</option>
                        <option value="CREDIT">KREDIT</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Kode Akun</label>
                    <input type="text" id="editCode" name="code" class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary font-mono font-bold" required>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Nama Akun</label>
                    <input type="text" id="editName" name="name" class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary font-bold uppercase" required>
                </div>
            </div>

            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mt-2">
                <label class="block text-xs font-bold text-yellow-800 mb-1 uppercase">Saldo Awal (Opening Balance)</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-yellow-600 font-bold text-sm">Rp</span>
                    <input type="number" id="editOpening" name="opening_balance" class="w-full pl-10 border-yellow-300 rounded-lg text-sm focus:ring-yellow-500 focus:border-yellow-500 font-bold text-gray-700">
                </div>
            </div>

            <div class="mt-6 flex justify-end pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="mr-3 px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-800 transition uppercase tracking-wide">Batal</button>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg font-bold hover:opacity-90 transition shadow-lg text-sm uppercase tracking-wider">Update Akun</button>
            </div>
        </form>
    </div>
</div>

<script>
    // JS TIDAK DIUBAH SAMA SEKALI, HANYA STYLE TAMPILAN
    function editAccount(acc) {
        document.getElementById('modalEdit').classList.remove('hidden');
        document.getElementById('formEdit').action = "/admin/accounting/coa/" + acc.id;
        
        document.getElementById('editCode').value = acc.code;
        document.getElementById('editName').value = acc.name;
        document.getElementById('editType').value = acc.type;
        document.getElementById('editNormal').value = acc.normal_balance;
        document.getElementById('editOpening').value = acc.opening_balance;

        if(acc.is_locked) {
            document.getElementById('editCode').readOnly = true;
            document.getElementById('editCode').classList.add('bg-gray-100', 'text-gray-500');
            document.getElementById('editType').disabled = true; 
            document.getElementById('lockedMessage').classList.remove('hidden');
        } else {
            document.getElementById('editCode').readOnly = false;
            document.getElementById('editCode').classList.remove('bg-gray-100', 'text-gray-500');
            document.getElementById('editType').disabled = false;
            document.getElementById('lockedMessage').classList.add('hidden');
        }
    }
</script>
@endsection