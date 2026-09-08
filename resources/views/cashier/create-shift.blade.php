<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Shift</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0A2647',   
                        secondary: '#D4AF37', 
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        .bg-pattern {
            background-color: #0A2647;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23123055' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="bg-pattern font-sans h-screen flex flex-col items-center justify-center relative overflow-hidden text-gray-800">

    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full bg-gradient-to-b from-transparent via-primary/50 to-primary pointer-events-none"></div>

    <div class="z-10 w-full max-w-md px-6">
        
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-white/10 relative">
            
            {{-- Header --}}
            <div class="bg-gradient-to-r from-primary to-blue-900 p-8 text-center relative overflow-hidden">
                <h2 class="text-2xl font-bold text-white mb-2 relative z-10 tracking-tight">MULAI SHIFT BARU</h2>
                <div class="inline-flex items-center gap-2 bg-black/20 px-3 py-1 rounded-full text-blue-100 text-xs font-semibold relative z-10 border border-white/10">
                    {{ $branch->name }}
                </div>
            </div>

            {{-- Body --}}
            <div class="p-8 text-center">
                <p class="text-gray-500 text-sm font-bold uppercase mb-4">Saldo Sistem Saat Ini</p>
                
                {{-- Display Saldo Besar --}}
                <div class="bg-blue-50 border-2 border-blue-100 rounded-2xl p-6 mb-8">
                    <span class="block text-4xl font-black text-primary tracking-tight">
                        Rp {{ number_format($currentSaldo, 0, ',', '.') }}
                    </span>
                    <span class="text-xs text-blue-400 font-semibold mt-1 block">
                        (Modal Awal + Transaksi Hari Ini)
                    </span>
                </div>

                {{-- Tombol Konfirmasi --}}
                <form action="{{ route('cashier.shift.store') }}" method="POST">
                    @csrf
                    {{-- Input Hidden tidak perlu lagi karena dihitung ulang di backend --}}
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-secondary to-yellow-500 hover:from-yellow-400 hover:to-yellow-500 text-primary font-bold py-4 rounded-xl shadow-lg shadow-yellow-500/30 transform transition-all duration-300 hover:-translate-y-1 hover:shadow-xl flex items-center justify-center gap-2 group">
                        <span>KONFIRMASI & BUKA KASIR</span>
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </button>
                </form>

                <p class="text-[10px] text-gray-400 mt-4 leading-relaxed">
                    Dengan menekan tombol di atas, Anda menyatakan siap bertugas dan bertanggung jawab atas pencatatan transaksi selanjutnya.
                </p>
            </div>

            {{-- Footer --}}
            <div class="bg-gray-50 p-4 text-center border-t border-gray-100">
                <a href="{{ route('cashier.dashboard') }}" class="text-xs text-gray-400 hover:text-red-500 font-bold transition flex items-center justify-center gap-1">
                    &larr; Ganti Kantor Cabang
                </a>
            </div>
        </div>
    </div>
</body>
</html>