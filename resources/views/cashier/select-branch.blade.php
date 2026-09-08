<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Lokasi Kerja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#fc3301',   /* Navy Blue */
                        secondary: '#f7faf7', /* Gold */
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        /* Pattern Halus untuk Background */
        .bg-pattern {
            background-color: #fcfcfc;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23123055' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="bg-pattern font-sans h-screen flex flex-col items-center justify-center relative overflow-hidden text-gray-800">

    {{-- Efek Cahaya di Background (Agar tidak flat) --}}
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full bg-gradient-to-b from-transparent via-primary/50 to-primary pointer-events-none"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="z-10 w-full max-w-6xl px-6">
        
        {{-- Header Info --}}
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center p-3 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl mb-6">
                <div class="w-12 h-12 bg-secondary text-primary rounded-xl flex items-center justify-center font-extrabold text-xl shadow-inner">
                    RP$
                </div>
            </div>
            <h1 class="text-4xl font-bold text-white mb-3 tracking-tight drop-shadow-md">
                Selamat Datang, <span class="text-secondary">{{ Auth::user()->name }}</span>
            </h1>
            <p class="text-blue-200 text-lg font-light">
                Silakan pilih lokasi kantor cabang untuk memulai sesi kasir.
            </p>
        </div>

        {{-- Grid Pilihan Cabang (Scrollable jika cabang banyak) --}}
        <div class="max-h-[60vh] overflow-y-auto custom-scrollbar p-2">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-6">
                @foreach($branches as $branch)
                <form action="{{ route('cashier.store-branch') }}" method="POST" class="h-full">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                    
                    <button type="submit" class="w-full h-full group bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl hover:shadow-secondary/20 transition-all duration-300 transform hover:-translate-y-2 border-2 border-transparent hover:border-secondary text-left flex flex-col justify-between relative overflow-hidden">
                        
                        {{-- Dekorasi Circle Halus di dalam kartu --}}
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-gray-50 rounded-full group-hover:bg-yellow-50 transition-colors"></div>

                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-14 h-14 bg-blue-50 text-primary rounded-2xl flex items-center justify-center group-hover:bg-primary group-hover:text-secondary transition-all duration-300 shadow-sm">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                </div>
                                {{-- Status Indicator (Opsional: Hijau artinya ready) --}}
                                <span class="w-3 h-3 bg-green-500 rounded-full shadow-lg shadow-green-500/50"></span>
                            </div>
                            
                            <h3 class="text-xl font-bold text-gray-800 group-hover:text-primary mb-1 transition-colors">{{ $branch->name }}</h3>
                            <p class="text-sm text-gray-500 group-hover:text-gray-600 line-clamp-2 leading-relaxed">
                                {{ $branch->address ?? 'Lokasi Cabang Utama' }}
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between text-sm font-bold text-gray-400 group-hover:text-secondary transition-colors relative z-10">
                            <span>Buka Kasir</span>
                            <div class="bg-gray-50 p-2 rounded-full group-hover:bg-secondary group-hover:text-primary transition-all">
                                <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </div>
                        </div>
                    </button>
                </form>
                @endforeach
            </div>
        </div>

        {{-- Tombol Logout --}}
        <div class="mt-12 text-center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-blue-200/60 hover:text-white text-sm font-semibold flex items-center justify-center gap-2 mx-auto transition-colors px-4 py-2 rounded-lg hover:bg-white/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Bukan Akun Anda? Logout
                </button>
            </form>
        </div>

        {{-- Copyright --}}
        <div class="mt-8 text-center text-[10px] text-blue-200/30">
            &copy; {{ date('Y') }} Powered by X-POSE
        </div>

    </div>

</body>
</html>