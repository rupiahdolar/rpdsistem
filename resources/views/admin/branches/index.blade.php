@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-primary">Data Kantor Cabang</h1>
            <p class="text-gray-500 text-sm">Kelola lokasi outlet Money Changer</p>
        </div>
        <a href="{{ route('cabang.create') }}" class="bg-secondary text-primary px-4 py-2 rounded-lg font-bold hover:brightness-110 shadow-lg transition transform hover:-translate-y-1">
            + Tambah Cabang
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-bold">
                <tr>
                    {{-- [BARU] KOLOM ID DITAMBAHKAN --}}
                    <th class="p-4 border-b text-center w-16">ID</th>
                    
                    <th class="p-4 border-b">Nama Kantor</th>
                    <th class="p-4 border-b">Alamat</th>
                    <th class="p-4 border-b text-center">Jml Kasir</th>
                    <th class="p-4 border-b text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($branches as $branch)
                <tr class="hover:bg-blue-50/50 transition">
                    
                    {{-- [BARU] TAMPILKAN ID --}}
                    <td class="p-4 text-center font-mono font-bold text-gray-500 bg-gray-50">
                        {{ $branch->id }}
                    </td>

                    <td class="p-4 font-bold text-gray-800">{{ $branch->name }}</td>
                    <td class="p-4 text-sm text-gray-600 max-w-md truncate">{{ $branch->address }}</td>
                    <td class="p-4 text-center">
                        <span class="bg-blue-100 text-blue-700 py-1 px-3 rounded-full text-xs font-bold">
                            {{ $branch->users_count }} Staff
                        </span>
                    </td>
                    <td class="p-4 text-center flex justify-center gap-2">
                        <a href="{{ route('cabang.edit', $branch->id) }}" class="text-yellow-600 hover:text-yellow-700 bg-yellow-50 hover:bg-yellow-100 p-2 rounded-lg transition" title="Edit Data">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        </a>
                        <form action="{{ route('cabang.destroy', $branch->id) }}" method="POST" onsubmit="return confirm('Yakin hapus cabang ini? Semua data transaksi cabang ini akan ikut terhapus!');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition" title="Hapus Permanen">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    {{-- UPDATE COLSPAN MENJADI 5 (Karena kolom nambah 1) --}}
                    <td colspan="5" class="p-8 text-center text-gray-400">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            <p>Belum ada kantor cabang.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection