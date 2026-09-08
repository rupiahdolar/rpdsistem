<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
    <title>PT. Rupiah Dollar Valuta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#fc3301', 
                        secondary: '#fc3301',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen font-sans">

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border-t-4 border-primary">
        
        <div class="text-center mb-8">
            {{-- LOGO --}}
            <div class="flex justify-center mb-4">
                <img src="{{ asset('img/logo.png') }}" alt="Logo" class="h-20 w-auto object-contain drop-shadow-md">
            </div>
            
            <h1 class="text-2xl font-bold text-gray-800">PT. Rupiah Dollar Valuta</h1>
            <p class="text-sm text-gray-500 mt-1">Money Changer Pos System - X-POSE</p>
        </div>

        <form action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 text-sm rounded mb-4">
                    <p class="font-bold">Login Gagal!</p>
                    <p>Username atau Password salah.</p>
                </div>
            @endif

            {{-- 1. INPUT EMAIL/USERNAME (ICON DI KANAN) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email/Username</label>
                <div class="relative">
                    {{-- Input: Padding Kiri Kecil (pl-4), Padding Kanan Besar (pr-10) untuk Icon --}}
                    <input type="text" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full pl-4 pr-10 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" 
                        placeholder="Email or Username">
                    
                    {{-- Icon User: Posisi Absolute di KANAN (right-0) --}}
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- 2. INPUT PASSWORD (ICON MATA DI KANAN) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="passwordInput" required autocomplete="current-password"
                        class="w-full pl-4 pr-12 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" 
                        placeholder="••••••••">
                    
                    <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-primary focus:outline-none">
                        <svg id="eyeIcon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg id="eyeSlashIcon" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="form-checkbox text-primary h-4 w-4 rounded border-gray-300 focus:ring-primary">
                    <span class="ml-2 text-sm text-gray-600">Ingat Saya</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:opacity-90 transition transform active:scale-95 shadow-md">
                MASUK
            </button>

        </form>

        <div class="text-center mt-6 text-xs text-gray-400">
            &copy; 2026 Powered by X-POSE
        </div>

    </div>

    {{-- SCRIPT TOGGLE PASSWORD --}}
    <script>
        function togglePassword() {
            var input = document.getElementById("passwordInput");
            var eyeIcon = document.getElementById("eyeIcon");
            var eyeSlashIcon = document.getElementById("eyeSlashIcon");

            if (input.type === "password") {
                input.type = "text";
                eyeIcon.classList.add("hidden");
                eyeSlashIcon.classList.remove("hidden");
            } else {
                input.type = "password";
                eyeIcon.classList.remove("hidden");
                eyeSlashIcon.classList.add("hidden");
            }
        }
    </script>

</body>
</html>