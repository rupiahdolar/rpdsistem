@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <div class="p-3 bg-primary text-white rounded-xl shadow-lg">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Pengaturan Akun</h1>
            <p class="text-gray-500 text-sm">Perbarui profil dan password Anda</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        {{-- KARTU INFO USER --}}
        <div class="md:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center">
                <div class="w-24 h-24 bg-gradient-to-br from-primary to-blue-800 text-white rounded-full flex items-center justify-center text-4xl font-bold mx-auto mb-4 shadow-lg">
                    {{ substr($user->name, 0, 1) }}
                </div>
                <h2 class="text-xl font-bold text-gray-800">{{ $user->name }}</h2>
                <span class="inline-block mt-2 px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-bold uppercase border border-yellow-200">
                    {{ $user->role }}
                </span>
                <p class="mt-4 text-sm text-gray-400">
                    Username: <span class="font-mono text-gray-600 font-bold">{{ $user->username }}</span>
                </p>
                <div class="mt-6 pt-6 border-t border-gray-100 text-xs text-gray-400">
                    Bergabung sejak {{ $user->created_at->format('d M Y') }}
                </div>
            </div>
        </div>

        {{-- FORM UPDATE --}}
        <div class="md:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <h3 class="font-bold text-lg text-primary mb-4 border-b pb-2">Data Profil</h3>
                    
                    <div class="mb-5">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full border-gray-300 rounded-lg p-3 uppercase focus:ring-primary focus:border-primary transition shadow-sm" required>
                        @error('name') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-8">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Email (Opsional)</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border-gray-300 rounded-lg p-3 focus:ring-primary focus:border-primary transition shadow-sm">
                        @error('email') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    <h3 class="font-bold text-lg text-primary mb-4 border-b pb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Ganti Password
                    </h3>
                    
                    <div class="bg-blue-50 p-5 rounded-xl border border-blue-100 mb-6">
                        <p class="text-xs text-blue-600 mb-4 font-bold flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Kosongkan jika tidak ingin mengubah password.
                        </p>

                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Password Lama (Wajib jika ganti password)</label>
                            <input type="password" name="current_password" class="w-full border-gray-300 rounded-lg p-3 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('current_password') <p class="text-red-500 text-xs mt-1 font-bold animate-pulse">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Password Baru</label>
                                <input type="password" name="password" class="w-full border-gray-300 rounded-lg p-3 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                @error('password') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Ulangi Password Baru</label>
                                <input type="password" name="password_confirmation" class="w-full border-gray-300 rounded-lg p-3 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-primary text-white font-bold py-3 px-8 rounded-lg hover:bg-blue-900 transition shadow-lg transform hover:-translate-y-1">
                            SIMPAN PERUBAHAN
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection