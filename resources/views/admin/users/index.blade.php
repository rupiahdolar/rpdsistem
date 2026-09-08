@extends('layouts.app')

@section('content')
<div x-data="{ 
    deleteModalOpen: false, 
    deleteUrl: '' 
}" class="max-w-6xl mx-auto pb-20">
    
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-primary flex items-center gap-2">
                <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Manajemen Pengguna
            </h1>
            <p class="text-gray-500 text-sm mt-1">Kelola akun Admin dan Staff Kasir</p>
        </div>
        
        <a href="{{ route('admin.users.create') }}" class="bg-secondary text-primary px-5 py-2.5 rounded-lg font-bold hover:brightness-110 shadow-lg transition transform hover:-translate-y-1 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            Tambah Pengguna Baru
        </a>
    </div>

    {{-- ALERT SUKSES --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
         class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-md flex justify-between items-center">
        <div class="flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-bold">{{ session('success') }}</span>
        </div>
        <button @click="show = false" class="text-green-700 hover:text-green-900 font-bold">✕</button>
    </div>
    @endif

    {{-- TABEL USER --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-bold">
                    <tr>
                        <th class="p-4 border-b">Nama Pengguna</th>
                        <th class="p-4 border-b">Username</th>
                        <th class="p-4 border-b text-center">Role (Jabatan)</th>
                        <th class="p-4 border-b">Akses Lokasi</th>
                        <th class="p-4 border-b text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($users as $user)
                        @php
                            $isAdmin = ($user->role === 'admin');
                            // Bedakan warna baris: Admin sedikit biru, Kasir putih
                            $rowClass = $isAdmin ? 'bg-blue-50/40 hover:bg-blue-50' : 'bg-white hover:bg-gray-50';
                        @endphp

                    <tr class="{{ $rowClass }} transition group">
                        
                        {{-- 1. NAMA & EMAIL --}}
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white shadow-sm border-2 
                                    {{ $isAdmin ? 'bg-blue-600 border-blue-200' : 'bg-green-500 border-green-200' }}">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $user->email ?? 'Tanpa Email' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- 2. USERNAME --}}
                        <td class="p-4 font-mono font-bold text-gray-600">
                            {{ $user->username }}
                        </td>

                        {{-- 3. ROLE --}}
                        <td class="p-4 text-center">
                            @if($isAdmin)
                                <span class="bg-blue-100 text-blue-700 py-1 px-3 rounded-full text-[10px] uppercase font-bold tracking-wider border border-blue-200 shadow-sm">
                                    ADMIN
                                </span>
                            @else
                                <span class="bg-green-100 text-green-700 py-1 px-3 rounded-full text-[10px] uppercase font-bold tracking-wider border border-green-200">
                                    STAFF KASIR
                                </span>
                            @endif
                        </td>

                        {{-- 4. AKSES CABANG --}}
                        <td class="p-4">
                            @if($isAdmin)
                                <span class="text-xs font-bold text-blue-600 italic flex items-center gap-1 bg-blue-50 px-2 py-1 rounded w-fit">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Akses Global
                                </span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->branches as $branch)
                                        <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-[10px] font-bold border border-gray-200">
                                            {{ $branch->name }}
                                        </span>
                                    @empty
                                        <span class="text-red-400 text-xs italic">⚠ Belum ditugaskan</span>
                                    @endforelse
                                </div>
                            @endif
                        </td>

                        {{-- 5. AKSI --}}
                        <td class="p-4 text-center">
                            <div class="flex justify-center items-center gap-2">
                                
                                {{-- EDIT: Admin bisa edit Kasir & Diri Sendiri --}}
                                @php
                                    $canEdit = (Auth::user()->role === 'admin' && $user->role === 'cashier') || (Auth::id() === $user->id);
                                @endphp

                                @if($canEdit)
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="text-yellow-600 hover:text-white hover:bg-yellow-500 bg-yellow-50 p-2 rounded-lg transition border border-yellow-200 shadow-sm" title="Edit Data">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </a>
                                @else
                                    <span class="text-gray-300 bg-gray-50 p-2 rounded-lg cursor-not-allowed" title="Akses Dibatasi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    </span>
                                @endif

                                {{-- HAPUS: Admin hanya bisa hapus Kasir (Bukan sesama Admin, Bukan diri sendiri) --}}
                                @if(Auth::user()->role === 'admin' && $user->role === 'cashier' && $user->id !== Auth::id())
                                    <button 
                                        @click="deleteModalOpen = true; deleteUrl = '{{ route('admin.users.destroy', $user->id) }}'"
                                        class="text-red-600 hover:text-white hover:bg-red-600 bg-red-50 p-2 rounded-lg transition border border-red-200 shadow-sm" 
                                        title="Hapus Pengguna">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                @else
                                    <span class="text-gray-300 bg-gray-50 p-2 rounded-lg cursor-not-allowed">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-400 bg-gray-50">Belum ada data pengguna.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    {{-- MODAL HAPUS --}}
    <div x-show="deleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white rounded-xl shadow-2xl p-6 max-w-sm w-full border-t-4 border-red-600" @click.away="deleteModalOpen = false">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4 animate-bounce">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Hapus Pengguna Ini?</h3>
                <p class="text-sm text-gray-500">Akses login dicabut permanen.</p>
            </div>
            <div class="mt-6 flex gap-3">
                <button @click="deleteModalOpen = false" type="button" class="w-full rounded-lg border border-gray-300 px-4 py-2 bg-white font-bold text-gray-700 hover:bg-gray-50">BATAL</button>
                <form :action="deleteUrl" method="POST" class="w-full">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2 font-bold text-white hover:bg-red-700">YA, HAPUS</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection