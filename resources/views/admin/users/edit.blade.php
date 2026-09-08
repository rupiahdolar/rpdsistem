@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    
    <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-primary mb-4 inline-flex items-center text-sm font-bold">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali
    </a>

    <div class="bg-white rounded-2xl shadow-lg border-t-4 border-yellow-500 p-8">
        <h1 class="text-2xl font-bold text-primary mb-6">Edit Data Kasir</h1>
        
        <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full border-gray-300 rounded-lg p-3 focus:ring-secondary uppercase shadow-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Username Login</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}" class="w-full border-gray-300 rounded-lg p-3 focus:ring-secondary shadow-sm" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border-gray-300 rounded-lg p-3 shadow-sm">
            </div>

            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mb-6">
                <h3 class="text-sm font-bold text-yellow-800 mb-2">Ubah Password (Opsional)</h3>
                <p class="text-xs text-yellow-700 mb-3">Kosongkan jika tidak ingin mengganti password.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="password" name="password" placeholder="Password Baru" class="w-full border-gray-300 rounded-lg p-2 text-sm">
                    <input type="password" name="password_confirmation" placeholder="Ulangi Password" class="w-full border-gray-300 rounded-lg p-2 text-sm">
                </div>
            </div>

            {{-- AKSES CABANG --}}
            <div class="mb-8 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <label class="block text-sm font-bold text-gray-800 mb-3">Penugasan Cabang</label>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($branches as $branch)
                    <label class="flex items-center space-x-3 p-3 border rounded-lg bg-white hover:bg-blue-50 cursor-pointer transition">
                        <input type="checkbox" name="branches[]" value="{{ $branch->id }}" 
                               class="w-5 h-5 text-secondary rounded focus:ring-secondary"
                               {{ in_array($branch->id, $selectedBranches) ? 'checked' : '' }}>
                        <span class="text-gray-700 font-medium">{{ $branch->name }}</span>
                    </label>
                    @endforeach
                </div>
                @error('branches') <p class="text-red-500 text-xs mt-2 font-bold">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full bg-yellow-500 text-primary font-bold py-3 rounded-lg hover:bg-yellow-400 transition shadow-md">
                SIMPAN PERUBAHAN
            </button>
        </form>
    </div>
</div>
@endsection