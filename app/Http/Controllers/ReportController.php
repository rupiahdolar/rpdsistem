<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Expense;         
use App\Models\InitialCapital;
use App\Models\EquityMutation;
use App\Models\InternalMutation; 
use App\Models\ChartOfAccount;
use App\Models\JournalItem;
use App\Models\User; 
use App\Models\AssetDepreciation; 
use App\Services\AccountingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{

    // =======================================================================
    // BAGIAN 2: BIAYA OPERASIONAL (OPTIMIZED)
    // =======================================================================

    public function biayaOperasional(Request $request) {
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $date = $request->input('date', date('Y-m-d'));
        
        $user = Auth::user();
        
        if ($user->role === 'admin' || $user->role === 'owner') {
            $branchId = $request->input('branch_id'); 
        } else {
            $branchId = session('branch_id');
            if (!$branchId) {
                $branchId = $user->branches->first()->id ?? null;
            }
        }

        $query = Expense::whereMonth('date', $month)->whereYear('date', $year);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $expenses = $query->with('branch:id,name') 
                          ->select('id', 'branch_id', 'name', 'amount', 'date', 'category', 'description')
                          ->orderBy('date', 'desc')
                          ->get();
                          
        $total = $query->sum('amount'); 
        
        return view('admin.reports.operational_cost', compact('expenses', 'month', 'year', 'date', 'total'));
    }

    public function biayaStore(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);

        $user = Auth::user();
        $branchId = null;

        if ($user->role === 'admin' || $user->role === 'owner') {
            $branchId = $request->branch_id;
        } else {
            $branchId = session('branch_id');
            if (!$branchId) {
                $branchId = $user->branches->first()->id ?? null;
            }
        }

        if (!$branchId) {
            return back()->with('error', 'Gagal: Lokasi Cabang tidak terdeteksi. Silakan Login Ulang atau Pilih Cabang.');
        }

        $expense = Expense::create([
            'branch_id' => $branchId,
            'created_by' => $user->id,
            'date' => $request->date,
            'name' => strtoupper($request->name),
            'category' => $request->category,
            'amount' => $request->amount,
            'description' => $request->description ?? ($request->name . ' (' . $request->category . ')'),
        ]);

        AccountingService::recordExpense($expense);

        return back()->with('success', 'Biaya Operasional berhasil disimpan di Cabang Aktif.');
    }

    public function biayaUpdate(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);
        
        if (Auth::user()->role == 'cashier' && $expense->branch_id != session('branch_id')) {
            return back()->with('error', 'Akses Ditolak: Data ini milik cabang lain.');
        }
        
        $request->validate([
            'date' => 'required|date',
            'name' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $expense->update([
            'date' => $request->date,
            'name' => strtoupper($request->name),
            'category' => $request->category,
            'amount' => $request->amount,
            'description' => $request->name . ' (' . $request->category . ')', 
        ]);

        AccountingService::updateExpenseJournal($expense);

        return back()->with('success', 'Data Biaya berhasil diperbarui.');
    }

    public function biayaDestroy($id)
    {
        $expense = Expense::findOrFail($id);

        if (Auth::user()->role == 'cashier' && $expense->branch_id != session('branch_id')) {
            return back()->with('error', 'Akses Ditolak: Data ini milik cabang lain.');
        }

        AccountingService::deleteExpenseJournal($expense->id); 
        $expense->delete();

        return back()->with('success', 'Biaya Operasional dihapus.');
    }

    // =======================================================================
    // BAGIAN 3: LAPORAN KEUANGAN (SINKRON 100% DGN MUTASI BULANAN)
    // =======================================================================

    public function labaRugi(Request $request)
    {
        $month = $request->input('month', date('m'));
        $year  = $request->input('year', date('Y'));
        
        $user = Auth::user();
        $branches = Branch::select('id', 'name')->get(); 
        
        $branchId = ($user->role === 'admin' || $user->role === 'owner') 
                    ? $request->input('branch_id') 
                    : ($user->branches->first()->id ?? null);
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth()->endOfDay();

        $qTrx = Transaction::whereBetween('created_at', [$startDate, $endDate]);
        if($branchId) $qTrx->where('branch_id', $branchId);
        $trxAll = $qTrx->get();
        
        $totalPenjualan = $trxAll->where('type', 'sell')->sum('total_idr');
        $totalPembelian = $trxAll->where('type', 'buy')->sum('total_idr');
        
        // 2. HITUNG BEBAN
        $expenses = Expense::select('category', DB::raw('SUM(amount) as total'))
                        ->whereMonth('date', $month)
                        ->whereYear('date', $year)
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                        ->groupBy('category')
                        ->get();
        $totalBeban = $expenses->sum('total');

        // BEBAN PENYUSUTAN ASET
        $depreciation = AssetDepreciation::whereMonth('date', $month)
                        ->whereYear('date', $year)
                        ->whereHas('asset', function($q) use ($branchId) {
                            if ($branchId) $q->where('branch_id', $branchId);
                        })->sum('amount');

        if ($depreciation > 0) {
            $expenses->push((object)[
                'category' => 'BEBAN PENYUSUTAN ASET',
                'total' => $depreciation
            ]);
            $totalBeban += $depreciation;
        }

        // 3. LOGIKA HPP SINKRON 100% DENGAN MUTASI BULANAN
        $endData = $this->getAccumulatedStart($endDate->copy()->addDay()->format('Y-m-d'), $branchId);
        $realEndStocks = $endData['stocks'];

        $startDataDummy = $this->getAccumulatedStart($startDate->format('Y-m-d'), $branchId);
        $dummyStocks = $startDataDummy['stocks'];

        $tempCurrencyReport = $this->calculateForexMutation($trxAll, $dummyStocks, $startDate->format('Y-m-d'), $startDate, $endDate, $branchId);

        $valuasiAwalBulan = 0;
        $nilaiStokAkhir = 0;

        foreach ($tempCurrencyReport as $row) {
            $code = $row['currency'];
            $realQtyEnd = $realEndStocks[$code]['qty'] ?? 0;
            $realRateEnd = $realEndStocks[$code]['rate'] ?? 0;
            $realValEnd = $realQtyEnd * $realRateEnd;

            $qtyBeli = $row['beli']['qty']; $qtyJual = $row['jual']['qty'];
            $derivedQtyStart = $realQtyEnd - $qtyBeli + $qtyJual;
            $rateStart = $row['awal']['rate']; 
            
            if ($rateStart == 0 && $derivedQtyStart > 0) {
                if ($realRateEnd > 0) $rateStart = $realRateEnd;
                elseif ($qtyBeli > 0) $rateStart = $row['beli']['total'] / $qtyBeli; 
                elseif ($qtyJual > 0) $rateStart = $row['jual']['total'] / $qtyJual; 
            }
            
            $derivedValStart = $derivedQtyStart * $rateStart;

            $valuasiAwalBulan += $derivedValStart;
            $nilaiStokAkhir += $realValEnd;
        }

        $valuasiAwalBulan += $derivedValStart;
        $nilaiStokAkhir += $realValEnd;

        // Cek apakah sudah ada Jurnal HPP hasil Tutup Buku pada periode ini
        $hppJournalItem = JournalItem::whereHas('chartOfAccount', fn($q) => $q->where('code', '5-1001'))
            ->whereHas('journalEntry', function($q) use ($month, $year, $branchId) {
                $q->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->when($branchId, fn($sq) => $sq->where('branch_id', $branchId));
            })->first();

        if ($hppJournalItem) {
            // Jika Tutup Buku sudah dijalankan, gunakan angka HPP riil yang tercatat di Jurnal
            $hpp = $hppJournalItem->debit - $hppJournalItem->credit;
        } else {
            // Jika belum Tutup Buku, gunakan HPP estimasi hasil kalkulasi sistem
            $hpp = $valuasiAwalBulan + $totalPembelian - $nilaiStokAkhir;
        }

        $grossProfit = $totalPenjualan - $hpp;
        $netProfit = $grossProfit - $totalBeban;

        $revenues = collect([]); 
        
        return view('admin.reports.profit_loss', compact(
            'month', 'year', 'branches', 
            'totalPenjualan', 'totalPembelian', 'totalBeban', 
            'valuasiAwalBulan', 'nilaiStokAkhir', 'grossProfit', 'netProfit',
            'expenses', 'revenues'
        ));
    }

   public function neraca(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $date = \Carbon\Carbon::createFromDate($year, 12, 31)->endOfDay(); 

        $accounts = ChartOfAccount::whereIn('type', ['ASSET', 'LIABILITY', 'EQUITY'])
            ->withSum(['journalItems as debit_total' => function($q) use ($date) {
                $q->whereHas('journalEntry', fn($je) => $je->whereDate('date', '<=', $date));
            }], 'debit')
            ->withSum(['journalItems as credit_total' => function($q) use ($date) {
                $q->whereHas('journalEntry', fn($je) => $je->whereDate('date', '<=', $date));
            }], 'credit')
            ->get();

        // ---------------------------------------------------------------------
        // 1. SINKRONISASI AKTIVA (ASSET) DENGAN PERSEDIAAN VALAS RIIL
        // ---------------------------------------------------------------------
        $assets = $accounts->where('type', 'ASSET');
        $totalAssets = 0;

        // Ambil valuasi persediaan valas riil per tanggal neraca
        $endData = $this->getAccumulatedStart($date->format('Y-m-d'), null);
        $realInventoryValuation = 0;
        foreach ($endData['stocks'] as $stk) {
            if (($stk['qty'] ?? 0) > 0) {
                $realInventoryValuation += (($stk['qty'] ?? 0) * ($stk['rate'] ?? 0));
            }
        }

        foreach ($assets as $acc) {
            $debit = $acc->debit_total ?? 0;
            $credit = $acc->credit_total ?? 0;
            
            if ($acc->normal_balance == 'DEBIT') {
                $acc->balance = ($acc->opening_balance + $debit) - $credit;
            } else { 
                $acc->balance = ($acc->opening_balance + $credit - $debit) * -1; 
            }

            // PERBAIKAN A: Paksa nilai akun Persediaan Valuta Asing sesuai stok riil
            if ($acc->code == '1-1010' || str_contains(strtolower($acc->name), 'persediaan valuta asing')) {
                $acc->balance = $realInventoryValuation;
            }

            $totalAssets += $acc->balance;
        }

        // ---------------------------------------------------------------------
        // 2. KEWAJIBAN & EKUITAS
        // ---------------------------------------------------------------------
        $liabilities = $accounts->where('type', 'LIABILITY');
        $totalLiabilities = 0;
        foreach ($liabilities as $acc) {
            $debit = $acc->debit_total ?? 0;
            $credit = $acc->credit_total ?? 0;
            
            $acc->balance = ($acc->opening_balance + $credit) - $debit;
            $totalLiabilities += $acc->balance;
        }

        $equities = $accounts->where('type', 'EQUITY');
        $totalEquity = 0;
        foreach ($equities as $acc) {
            $debit = $acc->debit_total ?? 0;
            $credit = $acc->credit_total ?? 0;
            
            $acc->balance = ($acc->opening_balance + $credit) - $debit;
            $totalEquity += $acc->balance;
        }

        // ---------------------------------------------------------------------
        // 3. PERBAIKAN B: KALKULASI LABA BERSIH TAHUN BERJALAN (SINKRON DENGAN HPP)
        // ---------------------------------------------------------------------
        $trxAll = Transaction::whereDate('created_at', '<=', $date)->get();
        $totalPenjualan = $trxAll->where('type', 'sell')->sum('total_idr');
        $totalPembelian = $trxAll->where('type', 'buy')->sum('total_idr');

        // HPP = Total Pembelian - Persediaan Akhir
        $hpp = $totalPembelian - $realInventoryValuation;
        $grossProfit = $totalPenjualan - $hpp;

        // Total Beban dari Jurnal
        $expenseTotal = JournalItem::whereHas('chartOfAccount', fn($q) => $q->where('type', 'EXPENSE'))
                        ->whereHas('journalEntry', fn($q) => $q->whereDate('date', '<=', $date))
                        ->sum(DB::raw('debit - credit'));

        // Laba Bersih = Gross Profit - Beban
        $currentEarnings = $grossProfit - $expenseTotal;

        $totalPasiva = $totalLiabilities + $totalEquity + $currentEarnings;

        return view('admin.reports.balance_sheet', compact(
            'year', 'date', 
            'assets', 'totalAssets', 
            'liabilities', 'totalLiabilities', 
            'equities', 'totalEquity', 
            'currentEarnings', 'totalPasiva'
        ));
    }

    public function ekuitas(Request $request) 
    {
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        
        $lastCapital = InitialCapital::orderBy('date', 'desc')->first();
        $modalAwal = $lastCapital ? $lastCapital->amount : 0;

        $startDate = "$year-$month-01";
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');
        
        $revenue = JournalItem::whereHas('chartOfAccount', fn($q)=>$q->where('type','REVENUE'))
                    ->whereHas('journalEntry', fn($q)=>$q->whereBetween('date', [$startDate, $endDate]))
                    ->sum(DB::raw('credit - debit'));
                    
        $expense = JournalItem::whereHas('chartOfAccount', fn($q)=>$q->where('type','EXPENSE'))
                    ->whereHas('journalEntry', fn($q)=>$q->whereBetween('date', [$startDate, $endDate]))
                    ->sum(DB::raw('debit - credit'));

        $labaBersihBulanIni = $revenue - $expense;

        $mutations = EquityMutation::whereMonth('date', $month)->whereYear('date', $year)->get();
        $totalSetor = $mutations->where('type', 'SETOR_MODAL')->sum('amount');
        $totalPrive = $mutations->where('type', 'PRIVE')->sum('amount');

        $modalAkhir = $modalAwal + $labaBersihBulanIni + $totalSetor - $totalPrive;
        
        return view('admin.reports.equity', compact(
            'mutations', 'month', 'year', 
            'modalAwal', 'labaBersihBulanIni', 
            'totalSetor', 'totalPrive', 'modalAkhir'
        ));
    }

    public function ekuitasStore(Request $request) {
        $request->validate([
            'date' => 'required|date',
            'type' => 'required',
            'amount' => 'required|numeric',
            'description' => 'nullable|string'
        ]);
        
        $mutation = EquityMutation::create($request->all());
        AccountingService::recordEquityMutation($mutation);

        return back()->with('success', 'Mutasi Ekuitas Berhasil Disimpan & Dijurnalkan');
    }

    public function ekuitasDestroy($id) {
        $mutation = EquityMutation::findOrFail($id);
        AccountingService::deleteEquityMutationJournal($mutation->id);
        $mutation->delete();
        return back()->with('success', 'Data berhasil dihapus dan jurnal dibatalkan.');
    }

    // =========================================================================
    // HELPER FUNCTIONS (Diambil dari Mutasi Bulanan)
    // =========================================================================

    private function getBankAccounts() {
        return ChartOfAccount::where('type', 'ASSET')
            ->where(function($q) {
                $q->where('name', 'LIKE', '%BANK%')->orWhere('name', 'LIKE', '%BCA%')->orWhere('name', 'LIKE', '%MANDIRI%')->orWhere('name', 'LIKE', '%BNI%')->orWhere('name', 'LIKE', '%BRI%');
            })->get();
    }

    private function getAccumulatedStart($targetDate, $branchId) {
        if ($branchId && $branchId != -1) { $branchIds = [$branchId]; } else { $branchIds = Branch::pluck('id')->toArray(); }
        $allCurrencies = Currency::where('is_active', 1)->get();
        
        $totalCash = 0; $totalStocks = []; 
        $globalDateCash = '2000-01-01'; 

        foreach ($branchIds as $bId) {
            $lastCap = InitialCapital::where('branch_id', $bId)->whereDate('date', '<=', $targetDate)->orderBy('date', 'desc')->first();
            $startCash = $lastCap ? $lastCap->amount : 0;
            $dateCash  = $lastCap ? $lastCap->date : '2000-01-01';

            if ($dateCash < $globalDateCash || $globalDateCash == '2000-01-01') {
                $globalDateCash = $dateCash;
            }

            $cashIn = Transaction::where('branch_id', $bId)->where('created_at', '>=', $dateCash . ' 00:00:00')->where('created_at', '<', $targetDate . ' 00:00:00')->where('payment_method', 'CASH')->where('type', 'sell')->sum('total_idr');
            $cashOut = Transaction::where('branch_id', $bId)->where('created_at', '>=', $dateCash . ' 00:00:00')->where('created_at', '<', $targetDate . ' 00:00:00')->where('payment_method', 'CASH')->where('type', 'buy')->sum('total_idr');
            
            $expenses = Expense::where('branch_id', $bId)->where('date', '>=', $dateCash)->where('date', '<', $targetDate)->sum('amount');
            $qMut = InternalMutation::where('branch_id', $bId)->where('transaction_date', '>=', $dateCash)->where('transaction_date', '<', $targetDate);
            $bankToCash = (clone $qMut)->where('type', 'bank_to_cash')->sum('amount');
            $cashToBank = (clone $qMut)->where('type', 'cash_to_bank')->sum('amount');
            
            $branchEndCash = $startCash + $cashIn - $cashOut - $expenses + $bankToCash - $cashToBank;
            $totalCash += $branchEndCash;

            foreach ($allCurrencies as $curr) {
                $code = $curr->code;
                $sData = $capStocks[$code] ?? ['qty' => 0, 'rate' => 0];
                $qty = $sData['qty'];
                $avgRate = $sData['rate'];
                
                $trxGap = Transaction::where('branch_id', $bId)->where('currency', $code)->where('created_at', '>=', $dateCash . ' 00:00:00')->where('created_at', '<', $targetDate . ' 00:00:00')->orderBy('created_at')->get(); 
                
                foreach ($trxGap as $t) {
                    if ($t->type == 'buy') {
                        if ($qty <= 0) {
                             if ($t->amount_foreign != 0) $avgRate = $t->total_idr / $t->amount_foreign;
                             $qty += $t->amount_foreign;
                        } else {
                             $valBefore = $qty * $avgRate; 
                             $valNew = $t->total_idr; 
                             $qty += $t->amount_foreign;
                             if ($qty > 0) $avgRate = ($valBefore + $valNew) / $qty; else $avgRate = 0;
                        }
                    } else { $qty -= $t->amount_foreign; }
                }
                if (!isset($totalStocks[$code])) $totalStocks[$code] = ['qty'=>0, 'val'=>0];
                $totalStocks[$code]['qty'] += $qty; $totalStocks[$code]['val'] += ($qty * $avgRate);
            }
        }

        // [PERBAIKAN] Hapus pencarian account_id yang menyebabkan error 1054
        $qEqMut = EquityMutation::where('date', '>=', $globalDateCash)
                    ->where('date', '<', $targetDate);
        
        $eqSetorKas = (clone $qEqMut)->where('type', 'SETOR_MODAL')->sum('amount');
        $eqPriveKas = (clone $qEqMut)->where('type', 'PRIVE')->sum('amount');
        
        $totalCash += ($eqSetorKas - $eqPriveKas);
        
        $finalStocks = [];
        foreach ($totalStocks as $code => $dt) {
            // JIKA STOK HABIS (0 ATAU KURANG), PAKSA KEDUANYA JADI 0
            if ($dt['qty'] <= 0) {
                $finalStocks[$code] = ['qty' => 0, 'rate' => 0];
            } else {
                $finalStocks[$code] = [
                    'qty'  => $dt['qty'], 
                    'rate' => ($dt['qty'] != 0) ? ($dt['val'] / $dt['qty']) : 0
                ];
            }
        }
        return ['cash' => $totalCash, 'stocks' => $finalStocks];
    }

    private function calculateForexMutation($transactions, $checkpointStocks, $checkpointDate, $periodStart, $periodEnd, $branchId) {
        $currencies = Currency::where('is_active', 1)->orderBy('id', 'asc')->get();
        $report = [];

        foreach($currencies as $curr) {
            $code = $curr->code;
            $stockData = $checkpointStocks[$code] ?? ['qty' => 0, 'rate' => 0];
            $qtyAwal = $stockData['qty'];
            $rateModal = $stockData['rate'];

            if (Carbon::parse($periodStart)->format('Y-m-d') > $checkpointDate) {
                // Filter 'status' => 'done' DIHAPUS
                $prevStockTrx = Transaction::where('currency', $code)->whereDate('created_at', '>=', $checkpointDate)->where('created_at', '<', $periodStart);
                if($branchId && $branchId != -1) $prevStockTrx->where('branch_id', $branchId);
                $prevTrxGet = $prevStockTrx->get(); 
                foreach($prevTrxGet as $pTrx) {
                    if ($pTrx->type == 'buy') {
                         if ($qtyAwal <= 0) {
                             if ($pTrx->amount_foreign != 0) $rateModal = $pTrx->total_idr / $pTrx->amount_foreign;
                             $qtyAwal += $pTrx->amount_foreign;
                         } else {
                             $totalVal = ($qtyAwal * $rateModal) + $pTrx->total_idr; 
                             $totalQty = $qtyAwal + $pTrx->amount_foreign;
                             if ($totalQty > 0) $rateModal = $totalVal / $totalQty; 
                             $qtyAwal += $pTrx->amount_foreign;
                         }
                    } elseif ($pTrx->type == 'sell') { $qtyAwal -= $pTrx->amount_foreign; }
                }
            }

            $valStartItem = $qtyAwal * $rateModal;
            $trxCurr = $transactions->where('currency', $code)->sortBy('created_at');
            $beliQtyTotal = 0; $beliIdrTotal = 0; $jualQtyTotal = 0; $jualIdrTotal = 0;
            $runningQty = $qtyAwal; $runningRate = $rateModal;

            foreach ($trxCurr as $trx) {
                if ($trx->type === 'buy') {
                    if ($runningQty <= 0) {
                        if ($trx->amount_foreign != 0) {
                            $runningRate = $trx->total_idr / $trx->amount_foreign;
                        }
                    } else {
                        $oldValuation = $runningQty * $runningRate; 
                        $newBuyVal = $trx->total_idr; 
                        $totalQty = $runningQty + $trx->amount_foreign;
                        
                        if ($totalQty != 0) { 
                            $runningRate = ($oldValuation + $newBuyVal) / $totalQty; 
                        } 
                    }

                    $runningQty += $trx->amount_foreign; 
                    $beliQtyTotal += $trx->amount_foreign; 
                    $beliIdrTotal += $trx->total_idr;
                    
                } elseif ($trx->type === 'sell') {
                    $runningQty -= $trx->amount_foreign; 
                    $jualQtyTotal += $trx->amount_foreign; 
                    $jualIdrTotal += $trx->total_idr;
                }
            }
            $qtyAkhir = $runningQty; 
            
            // JIKA STOK KAS VALAS SEKARANG HABIS/KOSONG, PAKSA VALUASI KURS DAN IDR JADI 0
            if ($qtyAkhir <= 0) {
                $qtyAkhir = 0;
                $avgRateAkhir = 0;
                $valEndItem = 0;
            } else {
                $avgRateAkhir = $runningRate; 
                $valEndItem = $qtyAkhir * $avgRateAkhir;
            }

            $report[] = [
                'currency' => $code,
                'awal'     => ['qty' => $qtyAwal, 'rate' => $rateModal, 'total' => $valStartItem],
                'beli'     => ['qty' => $beliQtyTotal, 'total' => $beliIdrTotal],
                'jual'     => ['qty' => $jualQtyTotal, 'total' => $jualIdrTotal],
                'akhir'    => ['qty' => $qtyAkhir, 'avgRate' => $avgRateAkhir, 'valuation' => $valEndItem]
            ];
        }
        return $report;
    }
}