<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Perhatikan '...$roles' (Titik Tiga) -> Ini WAJIB agar bisa baca 'admin,owner'
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Cek Login
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        // 2. CEK SAKTI: Apakah role user ada di daftar yang diizinkan?
        // Contoh: Apakah 'owner' ada di dalam ['admin', 'owner']? -> YA!
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // 3. JIKA DITOLAK, LEMPAR SESUAI HAKNYA
        // Jangan asal lempar biar gak looping
        
        if ($user->role === 'cashier') {
            return redirect()->route('cashier.dashboard');
        } 
        
        if ($user->role === 'admin' || $user->role === 'owner') {
            return redirect()->route('admin.dashboard');
        }

        // Default (Misal user aneh/hacker) -> Logout
        return redirect('logout');
    }
}