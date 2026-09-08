<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Shift;
use App\Models\InitialCapital; 
use App\Models\Transaction;    
use App\Models\Expense; 
use App\Models\InternalMutation; // [WAJIB] Tambahkan Model Mutasi       
use Illuminate\Support\Facades\Auth;

class CashierController extends Controller
{
    // =================================================================
    // 1. HALAMAN UTAMA KASIR (GATEWAY)
    // =================================================================
    public function index()
    {
        $user = Auth::user();
        
        // Cek apakah user SUDAH punya shift OPEN?
        $existingShift = Shift::where('user_id', $user->id)
                        ->where('status', 'open')
                        ->first();

        if ($existingShift) {
            // Re-set session jaga-jaga kalau hilang (misal clear cache)
            session([
                'shift_id' => $existingShift->id,
                'branch_id' => $existingShift->branch_id,
                'selected_branch_id' => $existingShift->branch_id // Konsistensi
            ]);
            return redirect()->route('transaction.index');
        }

        // AUTO-SELECT CABANG: Jika kasir cuma punya 1 akses
        if ($user->role == 'cashier') {
            // Ambil dari relasi branches (pastikan user punya minimal 1)
            $userBranchCount = $user->branches->count();
            
            if ($userBranchCount == 1) {
                $branch = $user->branches->first();
                session(['selected_branch_id' => $branch->id]);
                return redirect()->route('cashier.shift.create');
            } elseif ($userBranchCount == 0) {
                 return abort(403, 'AKUN ANDA BELUM DIHUBUNGKAN KE CABANG MANAPUN. HUBUNGI ADMIN.');
            }
        }

        // Jika Admin/Owner/Kasir Multi-Cabang
        $branches = ($user->role == 'admin' || $user->role == 'owner') 
                    ? Branch::all() 
                    : $user->branches;

        return view('cashier.select-branch', compact('branches'));
    }

    // =================================================================
    // 2. PROSES PILIH CABANG
    // =================================================================
    public function storeBranch(Request $request)
    {
        $request->validate(['branch_id' => 'required|exists:branches,id']);
        session(['selected_branch_id' => $request->branch_id]);
        return redirect()->route('cashier.shift.create');
    }

    // =================================================================
    // 3. TAMPILKAN KONFIRMASI BUKA SHIFT
    // =================================================================
    public function createShift()
    {
        // Cek Shift Lagi (Double Protection)
        $existingShift = Shift::where('user_id', Auth::id())
                        ->where('status', 'open')
                        ->first();
        
        if ($existingShift) {
            return redirect()->route('transaction.index');
        }

        // Cek Session Branch
        if (!session()->has('selected_branch_id')) {
            return redirect()->route('cashier.index'); 
        }

        $branchId = session('selected_branch_id');
        $branch = Branch::find($branchId);

        if (!$branch) {
            session()->forget('selected_branch_id');
            return redirect()->route('cashier.index')->withErrors(['msg' => 'Cabang tidak valid.']);
        }

        // Hitung Saldo Real (Termasuk Mutasi & Pengeluaran)
        $currentSaldo = $this->calculateLiveBalance($branchId);

        return view('cashier.create-shift', compact('branch', 'currentSaldo'));
    }

    // =================================================================
    // 4. PROSES SIMPAN SHIFT BARU
    // =================================================================
    public function storeShift(Request $request)
    {
        // Cegah Double Submit
        $existingShift = Shift::where('user_id', Auth::id())->where('status', 'open')->first();
        if ($existingShift) {
            return redirect()->route('transaction.index');
        }

        $branchId = session('selected_branch_id');
        
        // Hitung ulang saldo di backend (Security)
        $currentSaldo = $this->calculateLiveBalance($branchId);

        // Buat Shift
        $shift = Shift::create([
            'user_id' => Auth::id(),
            'branch_id' => $branchId,
            'start_time' => now(),
            'start_cash' => $currentSaldo, 
            'expected_cash' => $currentSaldo, 
            'status' => 'open',
        ]);

        // Set Session
        session([
            'shift_id' => $shift->id,
            'branch_id' => $shift->branch_id,
            'branch_name' => $shift->branch->name ?? 'CABANG',
        ]);

        return redirect()->route('transaction.index')->with('success', 'Shift Dibuka! Selamat Bekerja.');
    }

    // =================================================================
    // 5. HELPER: HITUNG SALDO (FIXED LOGIC)
    // =================================================================
    private function calculateLiveBalance($branchId)
    {
        $today = date('Y-m-d'); // Sebenarnya saldo berjalan itu akumulasi selamanya, tapi start pointnya Modal Terakhir

        // 1. Ambil Modal Awal Terakhir (Checkpoint)
        $lastCapital = InitialCapital::where('branch_id', $branchId)
                        ->whereDate('date', '<=', $today) // Ambil yang paling baru (sebelum atau hari ini)
                        ->orderBy('date', 'desc')
                        ->orderBy('id', 'desc') // Jaga-jaga ada 2 modal di hari sama
                        ->first();

        // Start Point (Uang di Brankas saat bos kasih modal terakhir)
        $saldo = $lastCapital ? $lastCapital->amount : 0;
        $checkpointDate = $lastCapital ? $lastCapital->date : '2000-01-01'; // Default masa lalu

        // 2. Transaksi Tunai (HANYA CASH) - Sejak Checkpoint
        // Note: Pakai created_at >= checkpoint 00:00:00
        $transactions = Transaction::where('branch_id', $branchId)
                        ->where('created_at', '>=', $checkpointDate . ' 00:00:00')
                        ->where('payment_method', 'CASH') 
                        ->get(); 
        
        $cashIn = $transactions->where('type', 'sell')->sum('total_idr');
        $cashOut = $transactions->where('type', 'buy')->sum('total_idr');

        // 3. Biaya Operasional (Mengurangi Kas)
        $expenses = Expense::where('branch_id', $branchId)
                    ->where('date', '>=', $checkpointDate)
                    ->sum('amount');

        // 4. [FIXED] Mutasi Internal (Bank <-> Kas)
        // Ini SANGAT PENTING. Kalau bos ambil duit (Setor ke Bank), kasir harus tahu.
        // Kalau bos nambah duit (Tarik dari Bank), kasir juga harus tahu.
        
        // Ambil mutasi sejak checkpoint
        $mutations = InternalMutation::where('branch_id', $branchId)
                    ->where('transaction_date', '>=', $checkpointDate)
                    ->get();

        // a. Tarik Tunai dari Bank (Uang Masuk ke Kasir)
        $bankToCash = $mutations->where('type', 'bank_to_cash')->sum('amount');

        // b. Setor Tunai ke Bank (Uang Keluar dari Kasir)
        $cashToBank = $mutations->where('type', 'cash_to_bank')->sum('amount');

        // RUMUS AKHIR:
        // Saldo Awal + (Jual - Beli) - Biaya + (Terima Duit Bos - Setor Duit Bos)
        return $saldo + ($cashIn - $cashOut) - $expenses + ($bankToCash - $cashToBank);
    }
}