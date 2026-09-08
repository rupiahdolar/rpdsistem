<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\User;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\InitialCapital;
use App\Models\Expense;
use App\Models\InternalMutation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    // ==========================================
    // 0. DASHBOARD UTAMA (EXECUTIVE DASHBOARD) - [OPTIMIZED]
    // ==========================================
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // 1. Tentukan Target Cabang (Logic Multi-Branch) - [TIDAK DIUBAH]
        $branches = Branch::all();
        $targetBranchIds = [];
        $branchName = 'SEMUA CABANG';
        $branchId = $request->input('branch_id'); 

        // Logika Hak Akses
        if ($user->role == 'cashier') {
            $myBranchId = $user->branch_id ?? ($user->branches->first()->id ?? null);
            if (!$myBranchId) {
                $branchId = -1;
                $targetBranchIds = [];
                $branchName = 'TIDAK ADA AKSES';
            } else {
                $branchId = $myBranchId;
                $targetBranchIds = [$myBranchId];
                $branchName = Branch::find($myBranchId)->name ?? '-';
            }
        } else {
            // Admin/Owner
            if ($branchId) {
                $targetBranchIds = [$branchId];
                $branchName = Branch::find($branchId)->name ?? '-';
            } else {
                $targetBranchIds = Branch::pluck('id')->toArray();
                $branchId = ''; 
            }
        }

        // 2. Ringkasan Harian (Today Stats) - [OPTIMIZED]
        $today = date('Y-m-d');
        
        // Menggunakan SQL Aggregate langsung, tidak perlu get() lalu count()
        $stats = Transaction::selectRaw('COUNT(*) as count, SUM(CASE WHEN type="buy" THEN total_idr ELSE 0 END) as buy_idr, SUM(CASE WHEN type="sell" THEN total_idr ELSE 0 END) as sell_idr')
            ->whereDate('created_at', $today)
            ->whereIn('branch_id', $targetBranchIds)
            ->first();

        $todayStats = [
            'count' => $stats->count ?? 0,
            'buy_idr' => $stats->buy_idr ?? 0,
            'sell_idr' => $stats->sell_idr ?? 0,
        ];

        // 3. Hitung Stok Valas Real-time (AKUMULASI PER CABANG) - [HEAVILY OPTIMIZED]
        
        $customOrder = ['USD', 'AUD', 'EUR', 'GBP', 'CHF', 'JPY', 'SGD', 'CAD', 'MYR', 'NZD', 'HKD', 'CNY', 'BND', 'SAR', 'AED', 'THB', 'PHP', 'SEK', 'NOK', 'DKK', 'KRW', 'TWD'];
        $allCurrencies = Currency::where('is_active', 1)->get();
        
        $aggregatedStocks = []; 
        foreach ($allCurrencies as $c) {
            $aggregatedStocks[$c->code] = ['name' => $c->name, 'qty' => 0, 'valuation' => 0];
        }

        // Looping Setiap Cabang Target
        foreach ($targetBranchIds as $bId) {
            // Ambil Modal Awal Terakhir Cabang Ini
            $lastCap = InitialCapital::where('branch_id', $bId)->orderBy('date', 'desc')->first();
            $capDate = $lastCap ? $lastCap->date : '2000-01-01';
            
            $capStocks = ($lastCap && $lastCap->forex_stocks) 
                ? (is_string($lastCap->forex_stocks) ? json_decode($lastCap->forex_stocks, true) : $lastCap->forex_stocks)
                : [];

            // [OPTIMASI SQL] Ambil Summary Transaksi langsung via SQL Group By
            // Tidak mengambil raw data transaksi (ribuan baris), hanya mengambil rekapnya (puluhan baris)
            $trxSums = Transaction::where('branch_id', $bId)
                        ->where('created_at', '>=', $capDate . ' 00:00:00')
                        ->selectRaw('currency, type, SUM(amount_foreign) as total_qty, SUM(total_idr) as total_val')
                        ->groupBy('currency', 'type')
                        ->get(); // Hasilnya kecil, aman di memori

            // Hitung Saldo Akhir Cabang Ini
            foreach ($allCurrencies as $curr) {
                $code = $curr->code;
                
                $stockData = $capStocks[$code] ?? []; 
                $startQty = $stockData['qty'] ?? 0;   
                $avgRate  = $stockData['rate'] ?? 0;  
                
                // Ambil data dari Collection Summary (Tanpa Query Lagi)
                $buyRow = $trxSums->where('currency', $code)->where('type', 'buy')->first();
                $sellRow = $trxSums->where('currency', $code)->where('type', 'sell')->first();

                $buyQty = $buyRow ? $buyRow->total_qty : 0;
                $buyVal = $buyRow ? $buyRow->total_val : 0;
                $sellQty = $sellRow ? $sellRow->total_qty : 0;

                $currQty = $startQty + $buyQty - $sellQty;

                // Hitung Valuasi
                $totalQtyIn = $startQty + $buyQty;
                $totalValIn = ($startQty * $avgRate) + $buyVal;
                
                $currentBranchAvgRate = ($totalQtyIn > 0) ? ($totalValIn / $totalQtyIn) : $avgRate;
                $currentBranchValuation = $currQty * $currentBranchAvgRate;

                // Masukkan ke Wadah Global
                $aggregatedStocks[$code]['qty'] += $currQty;
                $aggregatedStocks[$code]['valuation'] += $currentBranchValuation;
            }
        }

        // Finalisasi Data Stok (Sorting & Formatting)
        $liveStocks = [];
        $totalValuation = 0;
        
        $sortedCurrencies = $allCurrencies->sortBy(function($model) use ($customOrder) {
            $pos = array_search($model->code, $customOrder);
            return $pos === false ? 999 : $pos;
        });

        foreach ($sortedCurrencies as $curr) {
            $data = $aggregatedStocks[$curr->code];
            $qty = $data['qty'];
            $val = $data['valuation'];
            
            // Hindari devision by zero
            $globalAvgRate = ($qty != 0) ? ($val / $qty) : 0;
            
            $liveStocks[] = [
                'code' => $curr->code,
                'name' => $data['name'],
                'qty' => $qty,
                'avg_rate' => $globalAvgRate,
                'valuation' => $val
            ];
            $totalValuation += $val;
        }

        // 4. Hitung Saldo Kas Fisik (Rupiah) - [OPTIMIZED]
        $currentCash = 0;
        foreach ($targetBranchIds as $bId) {
            $lastCap = InitialCapital::where('branch_id', $bId)->orderBy('date', 'desc')->first();
            $startCash = $lastCap ? $lastCap->amount : 0;
            $dateCash = $lastCap ? $lastCap->date : '2000-01-01';
            
            // Gunakan SUM langsung di SQL, jangan load data
            $trxCash = Transaction::selectRaw('SUM(CASE WHEN type="sell" THEN total_idr ELSE 0 END) as cash_in, SUM(CASE WHEN type="buy" THEN total_idr ELSE 0 END) as cash_out')
                ->where('branch_id', $bId)
                ->where('created_at', '>=', $dateCash . ' 00:00:00')
                ->first();
            
            $cashIn = $trxCash->cash_in ?? 0;
            $cashOut = $trxCash->cash_out ?? 0;

            $expenses = Expense::where('branch_id', $bId)->where('date', '>=', $dateCash)->sum('amount');
            
            $mutations = InternalMutation::selectRaw('type, SUM(amount) as total')
                ->where('branch_id', $bId)
                ->where('transaction_date', '>=', $dateCash)
                ->groupBy('type')
                ->pluck('total', 'type');

            $bankToCash = $mutations['bank_to_cash'] ?? 0;
            $cashToBank = $mutations['cash_to_bank'] ?? 0;
            
            $currentCash += ($startCash + $cashIn - $cashOut - $expenses + $bankToCash - $cashToBank);
        }

        // 5. Data Grafik - [OPTIMIZED]
        // Menggunakan 1 Query untuk 7 hari (Solving N+1 Problem)
        $chartData = ['labels' => [], 'buy' => [], 'sell' => []];
        $startDate = Carbon::now()->subDays(6)->startOfDay(); // 7 hari terakhir (termasuk hari ini)
        
        $rawChart = Transaction::whereIn('branch_id', $targetBranchIds)
                    ->where('created_at', '>=', $startDate)
                    ->selectRaw('DATE(created_at) as date, type, SUM(total_idr) as total')
                    ->groupBy('date', 'type')
                    ->get();

        // Mapping Data ke Array 7 Hari
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartData['labels'][] = Carbon::parse($d)->format('d M');
            
            // Ambil dari hasil query kolektif, bukan query ulang
            $dayBuy = $rawChart->where('date', $d)->where('type', 'buy')->first();
            $daySell = $rawChart->where('date', $d)->where('type', 'sell')->first();

            $chartData['buy'][] = $dayBuy ? $dayBuy->total : 0;
            $chartData['sell'][] = $daySell ? $daySell->total : 0;
        }

        return view('admin.dashboard', compact(
            'branches', 'branchId', 'branchName',
            'todayStats', 'liveStocks', 'currentCash', 'totalValuation',
            'chartData'
        )); 
    }

    // ==========================================
    // MODULE 1: MANAJEMEN KANTOR (BRANCHES) - [TIDAK ADA PERUBAHAN]
    // ==========================================
    public function branchIndex()
    {
        $branches = Branch::withCount('users')->orderBy('created_at', 'desc')->get();
        return view('admin.branches.index', compact('branches'));
    }

    public function branchCreate()
    {
        return view('admin.branches.create');
    }

    public function branchStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:branches,name',
            'address' => 'required|string|max:500',
        ]);

        Branch::create([
            'name' => strtoupper($request->name),
            'address' => strtoupper($request->address),
        ]);

        return redirect()->route('admin.branches.index')
                         ->with('success', 'Kantor Cabang Baru Berhasil Ditambahkan!');
    }

    public function branchEdit($id)
    {
        $branch = Branch::findOrFail($id);
        return view('admin.branches.edit', compact('branch'));
    }

    public function branchUpdate(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:branches,name,'.$branch->id,
            'address' => 'required|string|max:500',
        ]);

        $branch->update([
            'name' => strtoupper($request->name),
            'address' => strtoupper($request->address),
        ]);

        return redirect()->route('admin.branches.index')
                         ->with('success', 'Data Kantor Cabang Diperbarui!');
    }

    public function branchDestroy($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->delete();

        return redirect()->route('admin.branches.index')
                         ->with('success', 'Kantor Cabang Telah Dihapus.');
    }

    // ==========================================
    // MODULE 2: MANAJEMEN PENGGUNA (USERS) - [TIDAK ADA PERUBAHAN]
    // ==========================================
    public function userIndex()
    {
        $users = User::where('role', '!=', 'owner')
                    ->orderByRaw("FIELD(role, 'admin', 'cashier')")
                    ->orderBy('name', 'asc')
                    ->get();

        return view('admin.users.index', compact('users'));
    }

    public function userCreate()
    {
        $branches = Branch::all();
        return view('admin.users.create', compact('branches'));
    }

    public function userStore(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', 'in:admin,cashier'], 
            'branches' => $request->role === 'cashier' ? ['required', 'array'] : ['nullable'],
        ]);

        $user = new User();
        $user->name = strtoupper($request->name);
        $user->username = $request->username;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role = $request->role; 
        
        if ($request->role === 'cashier' && $request->has('branches')) {
            $user->branch_id = $request->branches[0]; 
        }

        $user->save();

        if ($request->role === 'cashier' && $request->has('branches')) {
            $user->branches()->sync($request->branches);
        }

        return redirect()->route('admin.users.index')
                         ->with('success', 'Pengguna Baru Berhasil Dibuat.');
    }
    
    public function userEdit($id)
    {
        $user = User::with('branches')->findOrFail($id);
        $currentUser = Auth::user();

        if ($user->role === 'owner' && $currentUser->role !== 'owner') {
             return redirect()->route('admin.users.index')->with('success', 'Akses Ditolak: Anda tidak bisa mengedit data Owner.');
        }

        if ($currentUser->role === 'admin' && $user->role === 'admin' && $user->id !== $currentUser->id) {
            return redirect()->route('admin.users.index')->with('success', 'Akses Ditolak: Sesama Admin tidak boleh saling mengedit.');
        }

        $branches = Branch::all();
        $selectedBranches = $user->branches->pluck('id')->toArray();

        return view('admin.users.edit', compact('user', 'branches', 'selectedBranches'));
    }

    public function userUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $currentUser = Auth::user();

        if ($user->id == 1 && $currentUser->id != 1) {
            return back()->withErrors(['email' => 'ANDA TIDAK MEMILIKI AKSES UNTUK MENGUBAH DATA OWNER (SUPER ADMIN)!']);
        }
        if ($currentUser->role === 'admin' && $user->role === 'admin' && $user->id !== $currentUser->id) {
             return back()->withErrors(['msg' => 'Akses Ditolak: Sesama Admin tidak boleh saling mengubah data.']);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$id],
            'email' => ['nullable', 'email', 'unique:users,email,'.$id],
        ]);

        $dataToUpdate = [
            'name' => strtoupper($request->name),
            'username' => $request->username,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Rules\Password::defaults()],
            ]);
            $dataToUpdate['password'] = Hash::make($request->password);
        }

        $user->update($dataToUpdate);

        if ($request->has('branches')) {
            $user->branches()->sync($request->branches);
            $mainBranch = $request->branches[0]; 
            $user->branch_id = $mainBranch;
            $user->save(); 
        } else {
            $user->branches()->detach(); 
            $user->branch_id = null;
            $user->save();
        }

        return redirect()->route('admin.users.index')
                         ->with('success', 'Data Pengguna & Lokasi Cabang Berhasil Diperbarui!');
    }

    public function userDestroy($id)
    {
        $user = User::findOrFail($id);
        $currentUser = Auth::user();

        if ($currentUser->id == $id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri saat sedang login.');
        }

        if ($id == 1 || $user->role === 'owner') {
            return back()->with('error', 'PELANGGARAN: Akun SUPER ADMIN (Owner) tidak bisa dihapus oleh siapapun!');
        }
        
        if ($currentUser->role === 'admin' && $user->role === 'admin') {
             return back()->with('error', 'Akses Ditolak: Admin tidak boleh menghapus sesama Admin.');
        }

        $targetEmail = 'moneychangerdinar@gmail.com'; 
        $ownerAccount = User::where('email', $targetEmail)->first();

        if ($ownerAccount) {
            $newOwnerId = $ownerAccount->id;
            $newOwnerName = $ownerAccount->name . " (Owner)";
        } else {
            $newOwnerId = 1; 
            $newOwnerName = 'OWNER (Default ID 1)';
        }

        DB::beginTransaction();
        try {
            Transaction::where('user_id', $user->id)
                ->update([
                    'user_id' => $newOwnerId,
                    'customer_name' => DB::raw("CONCAT(customer_name, ' (Ex: " . $user->name . ")')")
                ]);

            \App\Models\Shift::where('user_id', $user->id)
                ->update(['user_id' => $newOwnerId]);

            $user->branches()->detach();
            $user->delete();

            DB::commit();

            return redirect()->route('admin.users.index')
                             ->with('success', 'Pegawai BERHASIL DIHAPUS. Seluruh data transaksi & shift telah diamankan ke akun: ' . $newOwnerName);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus user: ' . $e->getMessage());
        }
    }

    // ==========================================
    // MODULE 3: MASTER MATA UANG (CURRENCIES) - [TIDAK ADA PERUBAHAN]
    // ==========================================
    public function currencyIndex()
    {
        $currencies = Currency::orderBy('code', 'asc')->get();
        return view('admin.currencies.index', compact('currencies'));
    }

    public function currencyStore(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:currencies',
            'name' => 'required|string|max:100',
        ]);

        Currency::create([
            'code' => strtoupper($request->code),
            'name' => strtoupper($request->name),
            'is_active' => true,
        ]);

        return redirect()->route('admin.currencies.index')
                         ->with('success', 'Mata Uang Baru Ditambahkan!');
    }

    public function currencyUpdate(Request $request, $id)
    {
        $currency = Currency::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:10|unique:currencies,code,'.$id,
            'name' => 'required|string|max:100',
        ]);

        $currency->update([
            'code' => strtoupper($request->code),
            'name' => strtoupper($request->name),
            'is_active' => $request->has('is_active') ? 1 : 0, 
        ]);

        return redirect()->route('admin.currencies.index')
                         ->with('success', 'Data Mata Uang Diperbarui!');
    }

    public function currencyDestroy($id)
    {
        $currency = Currency::findOrFail($id);
        $currency->delete();

        return redirect()->route('admin.currencies.index')
                         ->with('success', 'Mata Uang Dihapus.');
    }
}