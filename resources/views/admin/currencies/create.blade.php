@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 p-6 flex justify-center">
    <div class="w-full max-w-lg">
        <div class="bg-white rounded-xl shadow-lg border-t-4 border-secondary p-8">
            <h2 class="text-2xl font-bold text-primary mb-6">Tambah Mata Uang</h2>
            
            <form action="{{ route('admin.currencies.store') }}" method="POST">
                @csrf
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kode Mata Uang (ISO Code)</label>
                        <input type="text" name="code" class="w-full border border-gray-300 p-3 rounded-lg focus:ring-secondary focus:border-secondary uppercase font-mono text-lg" placeholder="USD" maxlength="5" required>
                        <p class="text-xs text-gray-400 mt-1">Contoh: USD, SGD, MYR, JPY</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nama Mata Uang</label>
                        <input type="text" name="name" class="w-full border border-gray-300 p-3 rounded-lg focus:ring-secondary focus:border-secondary uppercase" placeholder="DOLLAR AMERIKA" required>
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-6 mt-4 border-t border-gray-100">
                    <a href="{{ route('admin.currencies.index') }}" class="px-6 py-2 text-gray-600 font-bold hover:bg-gray-100 rounded-lg">Batal</a>
                    <button type="submit" class="px-6 py-2 bg-primary text-secondary font-bold rounded-lg shadow hover:bg-primaryLight">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection