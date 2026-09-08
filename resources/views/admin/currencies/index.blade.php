@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-primary flex items-center gap-2">
                <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Master Mata Uang
            </h1>
            <p class="text-sm text-gray-500 mt-1">Daftar valuta asing yang tersedia untuk transaksi.</p>
        </div>
        
        <a href="{{ route('admin.currencies.create') }}" class="bg-primary hover:bg-primaryLight text-secondary font-bold py-2 px-6 rounded-lg shadow-md transition transform hover:scale-105 flex items-center gap-2">
            + Tambah Valas
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
        <table class="w-full text-left border-collapse">
            <thead class="bg-primary text-white text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center w-20">Kode</th>
                    <th class="px-6 py-4 font-semibold">Nama Mata Uang</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($currencies as $currency)
                <tr class="hover:bg-yellow-50/50 transition duration-150">
                    <td class="px-6 py-4 text-center">
                        <span class="bg-green-100 text-green-800 text-sm font-bold px-3 py-1 rounded-full border border-green-200">
                            {{ $currency->code }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-bold text-gray-700">
                        {{ $currency->name }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center gap-4">
                            <a href="{{ route('admin.currencies.edit', $currency->id) }}" class="text-blue-600 hover:text-blue-800 font-bold text-sm">Edit</a>
                            
                            <form action="{{ route('admin.currencies.destroy', $currency->id) }}" method="POST" onsubmit="return confirm('Hapus mata uang {{ $currency->code }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                        Belum ada mata uang. Silahkan tambah data.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection