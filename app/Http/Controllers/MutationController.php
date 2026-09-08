<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\InitialCapital;
use App\Models\User;
use App\Models\ChartOfAccount;
use App\Models\InternalMutation;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; 

class MutationController extends Controller
{
    /**
     * 1. MUTASI HARIAN (Daily Mutation)
     * Logika: MAJU (Forward). 
     * Mengambil saldo s/d kemarin, lalu ditambah transaksi hari ini.
     */
    public function daily(Request $request)
    {
        $user = Auth::user();
        
        // --- 1. AMBIL PARAMETER FILTER TANGGAL & SHIFT ---
        $startDate  = $request->input('start_date', $request->input('date', date('Y-m-d')));
        $startShift = $request->input('start_shift', 'all'); // Default 'all'
        $endDate    = $request->input('end_date', $request->input('date', date('Y-m-d')));
        $endShift   = $request->input('end_shift', 'all');   // Default 'all'

        // Tentukan Jam Berdasarkan Shift
        if ($startShift === 'all' || $startShift == 1) {
            $startTime = '00:00:00';
        } else {
            $startTime = '12:00:00'; // Jika memilih Shift 2
        }

        if ($endShift === 'all' || $endShift == 2) {
            $endTime = '23:59:59';
        } else {
            $endTime = '11:59:59'; // Jika memilih Shift 1
        }

        $startDatetime = $startDate . ' ' . $startTime;
        $endDatetime   = $endDate . ' ' . $endTime;

        $branches = Branch::select('id', 'name')->get();
        
        // --- 2. CEK HAK AKSES CABANG ---
        if ($user->role === 'cashier') {
            $branchId = $user->branch_id;
            if (!$branchId) {
                $firstBranch = $user->branches->first();
                $branchId = $firstBranch ? $firstBranch->id : null;
            }
            if (!$branchId) $branchId = -1;
        } else {
            $branchId = $request->input('branch_id');
        }

        $userId = $request->input('user_id');

        // Filter User (Kasir yang bertugas dalam rentang jam & tanggal ini)
        $activeCashiers = [];
        if ($user->role === 'admin' || $user->role === 'owner') {
            $cashierIds = Transaction::whereBetween('created_at', [$startDatetime, $endDatetime])
                            ->when($branchId && $branchId != -1, fn($q) => $q->where('branch_id', $branchId))
                            ->distinct()->pluck('user_id');
            $activeCashiers = User::select('id', 'name')->whereIn('id', $cashierIds)->get();
        }

        // --- 3. HITUNG SALDO AWAL (REAL FISIK) ---
        // Mengambil saldo awal di tanggal mulai ($startDate)
        $startData = $this->getAccumulatedStart($startDate, $branchId);
        $startCash = $startData['cash'];
        $checkpointStocks = $startData['stocks'];
        $checkpointDate = $startDate;

        // --- 4. TRANSAKSI HARIAN (BERDASARKAN RENTANG JAM & TANGGAL) ---
        $qTrx = Transaction::whereBetween('created_at', [$startDatetime, $endDatetime]);
        if($branchId && $branchId != -1) $qTrx->where('branch_id', $branchId);
        if ($userId) $qTrx->where('user_id', $userId);
        
        $trxAll = $qTrx->get(); 
        
        // A. Data Tampilan (Volume)
        $displayPembelian = $trxAll->where('type', 'buy')->sum('total_idr'); 
        $displayPenjualan = $trxAll->where('type', 'sell')->sum('total_idr');
        
        // B. Data Real Cash (Hanya Tunai untuk Saldo Fisik)
        $realCashBuy  = $trxAll->where('type', 'buy')->where('payment_method', 'CASH')->sum('total_idr');
        $realCashSell = $trxAll->where('type', 'sell')->where('payment_method', 'CASH')->sum('total_idr');
        
        // C. Biaya & Mutasi (Berdasarkan Rentang Tanggal)
        $qCost = Expense::whereBetween('date', [$startDate, $endDate]);
        if($branchId && $branchId != -1) $qCost->where('branch_id', $branchId);
        $expense = $qCost->sum('amount');

        $qMut = InternalMutation::whereBetween('transaction_date', [$startDate, $endDate]);
        if($branchId && $branchId != -1) $qMut->where('branch_id', $branchId);
        
        $bankToCash = (clone $qMut)->where('type', 'bank_to_cash')->sum('amount'); // Masuk Kas
        $cashToBank = (clone $qMut)->where('type', 'cash_to_bank')->sum('amount'); // Keluar Kas

        // --- 5. HITUNG SALDO AKHIR REAL ---
        $totalCashIn  = $realCashSell + $bankToCash;
        $totalCashOut = $realCashBuy + $cashToBank + $expense;
        
        $endCash = $startCash + $totalCashIn - $totalCashOut;

        // --- 6. MUTASI BANK & VALAS ---
        $bankReport = $this->calculateBankMutation($startDate, $endDate, $branchId, $userId);
        
        $totalBankStart = collect($bankReport)->sum('start');
        $totalBankIn    = collect($bankReport)->sum('in');
        $totalBankOut   = collect($bankReport)->sum('out');
        $totalBankEnd   = collect($bankReport)->sum('end');

        $currencyReport = $this->calculateForexMutation($trxAll, $checkpointStocks, $checkpointDate, $startDate, $endDate, $branchId);
        $valEndTotal = collect($currencyReport)->sum('akhir.valuation');
        $valStartTotal = collect($currencyReport)->sum('awal.total');

        // --- 7. PACKING DATA KE VIEW ---
        $brankas = [
            'start' => $startCash, 
            'in' => $displayPembelian, 
            'out' => $displayPenjualan, 
            'expense' => $expense, 
            'end' => $endCash,
            'start_asset' => $startCash + $totalBankStart + $valStartTotal, 
            'end_asset' => $endCash + $totalBankEnd + $valEndTotal, 
            'end_valas' => $valEndTotal, 
            'end_bank' => $totalBankEnd
        ];
        $valuation = ['start' => $valStartTotal, 'end' => $valEndTotal];

        $filterDate = $startDate; // Fallback variabel untuk kompatibilitas view

        return view('admin.reports.harian', compact(
            'startDate', 'startShift', 'endDate', 'endShift', 'filterDate',
            'branchId', 'branches', 'userId', 'activeCashiers',
            'brankas', 'valuation', 'currencyReport', 
            'bankReport', 'totalBankStart', 'totalBankIn', 'totalBankOut', 'totalBankEnd'
        )); 
    }

    /**
     * 2. MUTASI BULANAN (Monthly Mutation)
     * Logika: MUNDUR (Back-Calculation).
     * Hitung Saldo Akhir Real (berdasarkan Modal Awal Terbaru) -> Tarik mundur ke Saldo Awal.
     * Ini MENJAMIN data sinkron walau ada Reset Modal di tengah bulan.
     */
    public function monthly(Request $request)
    {
        $month = $request->input('month', date('m'));
        $year  = $request->input('year', date('Y'));
        $user = Auth::user();
        
        $branches = Branch::select('id', 'name')->get();
        
        if ($user->role === 'cashier') {
            $branchId = $user->branch_id;
            if (!$branchId) {
                $firstBranch = $user->branches->first();
                $branchId = $firstBranch ? $firstBranch->id : null;
            }
            if (!$branchId) $branchId = -1;
        } else {
            $branchId = $request->input('branch_id'); 
        }
        
        $userId = $request->input('user_id'); 
        
        // Range Tanggal: Tgl 1 s/d Akhir Bulan
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth()->endOfDay();

        // ---------------------------------------------------------------------
        // STEP 1: HITUNG SALDO AKHIR REAL (THE TRUTH)
        // Kita hitung saldo per "Besok Pagi" (Awal bulan depan) untuk mendapatkan
        // saldo akhir bulan ini yang paling akurat, termasuk jika ada reset modal di tgl 5/6.
        // ---------------------------------------------------------------------
        $endData = $this->getAccumulatedStart($endDate->copy()->addDay()->format('Y-m-d'), $branchId);
        $realEndCash = $endData['cash'];
        $realEndStocks = $endData['stocks'];

        // ---------------------------------------------------------------------
        // STEP 2: HITUNG FLOW (PERGERAKAN) SELAMA BULAN INI (OPTIMIZED SQL)
        // ---------------------------------------------------------------------
        
        // A. Transaksi (Digunakan untuk Volume & Valas)
        $qTrx = Transaction::whereBetween('created_at', [$startDate, $endDate]);
        if($branchId && $branchId != -1) $qTrx->where('branch_id', $branchId);
        $trxAll = $qTrx->get();
        
        // B. Arus Kas Real (Tunai Only - SQL Optimized)
        // Menggunakan SQL Sum agar tidak membebani PHP looping
        $realCashBuy = Transaction::whereBetween('created_at', [$startDate, $endDate])
                        ->when($branchId && $branchId != -1, fn($q) => $q->where('branch_id', $branchId))
                        ->where('type', 'buy')->where('payment_method', 'CASH')->sum('total_idr');

        $realCashSell = Transaction::whereBetween('created_at', [$startDate, $endDate])
                        ->when($branchId && $branchId != -1, fn($q) => $q->where('branch_id', $branchId))
                        ->where('type', 'sell')->where('payment_method', 'CASH')->sum('total_idr');
        
        // C. Biaya & Mutasi Internal (SQL Optimized)
        $qCost = Expense::whereBetween('date', [$startDate, $endDate]);
        if($branchId && $branchId != -1) $qCost->where('branch_id', $branchId);
        $expense = $qCost->sum('amount'); 

        $qMut = InternalMutation::whereBetween('transaction_date', [$startDate, $endDate]);
        if($branchId && $branchId != -1) $qMut->where('branch_id', $branchId);
        
        $bankToCash = (clone $qMut)->where('type', 'bank_to_cash')->sum('amount'); 
        $cashToBank = (clone $qMut)->where('type', 'cash_to_bank')->sum('amount'); 

        // Total Flow Kas Fisik
        $totalCashIn  = $realCashSell + $bankToCash; // Uang Masuk
        $totalCashOut = $realCashBuy + $cashToBank + $expense; // Uang Keluar

        // ---------------------------------------------------------------------
        // STEP 3: BACK-CALCULATION (HITUNG MUNDUR SALDO AWAL)
        // Rumus: Awal = Akhir - Masuk + Keluar
        // Ini memastikan matematika tabel selalu benar & sinkron dengan reset modal.
        // ---------------------------------------------------------------------
        $derivedStartCash = $realEndCash - $totalCashIn + $totalCashOut;

        // Data Tampilan (Volume Bisnis)
        $displayPembelian = $trxAll->where('type', 'buy')->sum('total_idr'); 
        $displayPenjualan = $trxAll->where('type', 'sell')->sum('total_idr');

        // ---------------------------------------------------------------------
        // STEP 4: MUTASI BANK
        // ---------------------------------------------------------------------
        $bankReport = $this->calculateBankMutation($startDate, $endDate, $branchId, $userId);
        $totalBankStart = collect($bankReport)->sum('start');
        $totalBankIn    = collect($bankReport)->sum('in');
        $totalBankOut   = collect($bankReport)->sum('out');
        $totalBankEnd   = collect($bankReport)->sum('end');

        // ---------------------------------------------------------------------
        // STEP 5: VALAS (DENGAN KOREKSI STOK AKHIR)
        // ---------------------------------------------------------------------
        // Kita hitung flow normal dulu menggunakan Helper.
        // Checkpoint pakai Tgl 1 agar flow Beli/Jual tercatat lengkap.
        $startDataDummy = $this->getAccumulatedStart($startDate->format('Y-m-d'), $branchId);
        $dummyStocks = $startDataDummy['stocks'];
        
        $tempCurrencyReport = $this->calculateForexMutation($trxAll, $dummyStocks, $startDate->format('Y-m-d'), $startDate, $endDate, $branchId);
        
        // Patching/Koreksi Laporan Valas
        $currencyReport = [];
        $valEndTotal = 0;
        $valStartTotal = 0;

        foreach ($tempCurrencyReport as $row) {
            $code = $row['currency'];
            
            // Ambil Stok Akhir Real dari Step 1 (DB)
            $realQtyEnd = $realEndStocks[$code]['qty'] ?? 0;
            $realRateEnd = $realEndStocks[$code]['rate'] ?? 0;
            $realValEnd = $realQtyEnd * $realRateEnd;

            // Ambil Flow Beli/Jual dari perhitungan normal
            $qtyBeli = $row['beli']['qty'];
            $qtyJual = $row['jual']['qty'];
            
            // Hitung Mundur Stok Awal (Qty)
            $derivedQtyStart = $realQtyEnd - $qtyBeli + $qtyJual;
            
            // Perkiraan Valuasi Awal
            $rateStart = $row['awal']['rate']; 
            if ($rateStart == 0 && $derivedQtyStart > 0) $rateStart = $realRateEnd;
            $derivedValStart = $derivedQtyStart * $rateStart;

            // Update Row dengan data hasil hitung mundur
            $row['awal']['qty'] = $derivedQtyStart;
            $row['awal']['total'] = $derivedValStart;
            
            $row['akhir']['qty'] = $realQtyEnd;
            $row['akhir']['avgRate'] = $realRateEnd;
            $row['akhir']['valuation'] = $realValEnd;

            $currencyReport[] = $row;
            
            $valEndTotal += $realValEnd;
            $valStartTotal += $derivedValStart;
        }

        // Data Ringkasan Final
        $brankas = [
            'start' => $derivedStartCash, // Hasil Hitung Mundur
            'in' => $displayPembelian, 
            'out' => $displayPenjualan, 
            'expense' => $expense, 
            'end' => $realEndCash,        // Hasil Cek Fisik Terbaru (Real)
            
            'start_asset' => $derivedStartCash + $totalBankStart + $valStartTotal,
            'end_asset' => $realEndCash + $totalBankEnd + $valEndTotal,
            'end_valas' => $valEndTotal, 
            'end_bank' => $totalBankEnd
        ];
        $valuation = ['start' => $valStartTotal, 'end' => $valEndTotal];

        $filterMonth = $month; $filterYear = $year;
        return view('admin.reports.bulanan', compact(
            'month', 'year', 'filterMonth', 'filterYear', 'branches', 'branchId',
            'brankas', 'valuation', 'currencyReport',
            'bankReport', 'totalBankStart', 'totalBankIn', 'totalBankOut', 'totalBankEnd'
        ));
    }

    // =========================================================================
    // HELPER FUNCTIONS (Fungsi Penunjang)
    // =========================================================================

    private function getBankAccounts() {
        return ChartOfAccount::where('type', 'ASSET')
            ->where(function($q) {
                $q->where('name', 'LIKE', '%BANK%')->orWhere('name', 'LIKE', '%BCA%')->orWhere('name', 'LIKE', '%MANDIRI%')->orWhere('name', 'LIKE', '%BNI%')->orWhere('name', 'LIKE', '%BRI%');
            })->get();
    }

    private function calculateBankMutation($startDate, $endDate, $branchId, $userId) {
        $startStr = $startDate instanceof Carbon ? $startDate->format('Y-m-d') : $startDate;
        $endStr   = $endDate instanceof Carbon ? $endDate->format('Y-m-d') : $endDate;
        
        $bankAccounts = $this->getBankAccounts();
        $bankIds = $bankAccounts->pluck('id')->toArray();

        $allBankTrx = Transaction::whereIn('bank_account_id', $bankIds)
                        ->where('payment_method', 'TRANSFER')
                        ->when($branchId && $branchId != -1, fn($q) => $q->where('branch_id', $branchId))
                        ->when($userId, fn($q) => $q->where('user_id', $userId)) 
                        ->get();

        $allBankMut = InternalMutation::whereIn('bank_account_id', $bankIds)
                        ->when($branchId && $branchId != -1, fn($q) => $q->where('branch_id', $branchId))
                        ->get();

        $bankReport = [];

        foreach ($bankAccounts as $bank) {
            $manualOpening = $bank->opening_balance ?? 0;

            $histTrxSell = $allBankTrx->where('bank_account_id', $bank->id)->where('created_at', '<', $startStr . ' 00:00:00')->where('type', 'sell')->sum('total_idr');
            $histTrxBuy = $allBankTrx->where('bank_account_id', $bank->id)->where('created_at', '<', $startStr . ' 00:00:00')->where('type', 'buy')->sum('total_idr');
            $histMutIn = $allBankMut->where('bank_account_id', $bank->id)->where('transaction_date', '<', $startStr)->where('type', 'cash_to_bank')->sum('amount'); 
            $histMutOut = $allBankMut->where('bank_account_id', $bank->id)->where('transaction_date', '<', $startStr)->where('type', 'bank_to_cash')->sum('amount');

            $bankStart = $manualOpening + ($histTrxSell - $histTrxBuy) + ($histMutIn - $histMutOut);

            $todayTrx = $allBankTrx->where('bank_account_id', $bank->id)->filter(fn($i) => $i->created_at >= $startStr . ' 00:00:00' && $i->created_at <= $endStr . ' 23:59:59');
            $bankInTrx = $todayTrx->where('type', 'sell')->sum('total_idr');
            $bankOutTrx = $todayTrx->where('type', 'buy')->sum('total_idr');

            $todayMut = $allBankMut->where('bank_account_id', $bank->id)->filter(function ($item) use ($startStr, $endStr) { $d = $item->transaction_date; return $d >= $startStr && $d <= $endStr; });
            $bankInMut = $todayMut->where('type', 'cash_to_bank')->sum('amount');
            $bankOutMut = $todayMut->where('type', 'bank_to_cash')->sum('amount');

            $totalInBank = $bankInTrx + $bankInMut;
            $totalOutBank = $bankOutTrx + $bankOutMut;
            $bankEnd = $bankStart + $totalInBank - $totalOutBank;

            $bankReport[] = ['name' => $bank->name, 'start' => $bankStart, 'in' => $totalInBank, 'out' => $totalOutBank, 'end' => $bankEnd];
        }
        return $bankReport;
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
                $prevStockTrx = Transaction::where('currency', $code)->whereDate('created_at', '>=', $checkpointDate)->where('created_at', '<', $periodStart);
                if($branchId && $branchId != -1) $prevStockTrx->where('branch_id', $branchId);
                $prevTrxGet = $prevStockTrx->get(); 
                foreach($prevTrxGet as $pTrx) {
                    if ($pTrx->type == 'buy') {
                        $totalVal = ($qtyAwal * $rateModal) + $pTrx->total_idr; $totalQty = $qtyAwal + $pTrx->amount_foreign;
                        if ($totalQty > 0) $rateModal = $totalVal / $totalQty; $qtyAwal += $pTrx->amount_foreign;
                    } elseif ($pTrx->type == 'sell') { $qtyAwal -= $pTrx->amount_foreign; }
                }
            }

            $valStartItem = $qtyAwal * $rateModal;
            $trxCurr = $transactions->where('currency', $code)->sortBy('created_at');
            $beliQtyTotal = 0; $beliIdrTotal = 0; $jualQtyTotal = 0; $jualIdrTotal = 0;
            $runningQty = $qtyAwal; $runningRate = $rateModal;

            foreach ($trxCurr as $trx) {
                if ($trx->type === 'buy') {
                    $oldValuation = $runningQty * $runningRate; $newBuyVal = $trx->total_idr; $totalQty = $runningQty + $trx->amount_foreign;
                    if ($totalQty != 0) { $runningRate = ($oldValuation + $newBuyVal) / $totalQty; } else { $runningRate = 0; }
                    $runningQty += $trx->amount_foreign; $beliQtyTotal += $trx->amount_foreign; $beliIdrTotal += $trx->total_idr;
                } elseif ($trx->type === 'sell') {
                    $runningQty -= $trx->amount_foreign; $jualQtyTotal += $trx->amount_foreign; $jualIdrTotal += $trx->total_idr;
                }
            }
            $qtyAkhir = $runningQty; $avgRateAkhir = $runningRate; $valEndItem = $qtyAkhir * $avgRateAkhir;
            $profit = ($valEndItem + $jualIdrTotal) - ($valStartItem + $beliIdrTotal);

            $report[] = [
                'currency' => $code,
                'awal'     => ['qty' => $qtyAwal, 'rate' => $rateModal, 'total' => $valStartItem],
                'beli'     => ['qty' => $beliQtyTotal, 'total' => $beliIdrTotal],
                'jual'     => ['qty' => $jualQtyTotal, 'total' => $jualIdrTotal],
                'akhir'    => ['qty' => $qtyAkhir, 'avgRate' => $avgRateAkhir, 'valuation' => $valEndItem],
                'profit_gross' => $profit
            ];
        }
        return $report;
    }

    /**
     * [OPTIMIZED] FUNGSI HITUNG SALDO AWAL (GABUNGAN SEMUA CABANG)
     * Menggunakan SQL Sum untuk meringankan beban RAM.
     * Mencari modal awal terakhir dan mengakumulasi transaksi s/d Target Date.
     */
    private function getAccumulatedStart($targetDate, $branchId) {
        if ($branchId && $branchId != -1) { $branchIds = [$branchId]; } else { $branchIds = Branch::pluck('id')->toArray(); }
        $allCurrencies = Currency::where('is_active', 1)->get();
        $totalCash = 0; $totalStocks = []; 

        foreach ($branchIds as $bId) {
            $lastCap = InitialCapital::where('branch_id', $bId)->whereDate('date', '<=', $targetDate)->orderBy('date', 'desc')->first();
            $startCash = $lastCap ? $lastCap->amount : 0;
            $dateCash  = $lastCap ? $lastCap->date : '2000-01-01';
            $capStocks = ($lastCap && $lastCap->forex_stocks) ? (is_string($lastCap->forex_stocks) ? json_decode($lastCap->forex_stocks, true) : $lastCap->forex_stocks) : [];

            // OPTIMIZED SQL QUERY (SUM)
            $cashIn = Transaction::where('branch_id', $bId)->where('created_at', '>=', $dateCash . ' 00:00:00')->where('created_at', '<', $targetDate . ' 00:00:00')->where('payment_method', 'CASH')->where('type', 'sell')->sum('total_idr');
            $cashOut = Transaction::where('branch_id', $bId)->where('created_at', '>=', $dateCash . ' 00:00:00')->where('created_at', '<', $targetDate . ' 00:00:00')->where('payment_method', 'CASH')->where('type', 'buy')->sum('total_idr');
            $expenses = Expense::where('branch_id', $bId)->where('date', '>=', $dateCash)->where('date', '<', $targetDate)->sum('amount');
            $qMut = InternalMutation::where('branch_id', $bId)->where('transaction_date', '>=', $dateCash)->where('transaction_date', '<', $targetDate);
            $bankToCash = (clone $qMut)->where('type', 'bank_to_cash')->sum('amount');
            $cashToBank = (clone $qMut)->where('type', 'cash_to_bank')->sum('amount');
            
            $branchEndCash = $startCash + $cashIn - $cashOut - $expenses + $bankToCash - $cashToBank;
            $totalCash += $branchEndCash;

            // Untuk Valas, tetap butuh loop karena Moving Average butuh urutan
            foreach ($allCurrencies as $curr) {
                $code = $curr->code;
                $sData = $capStocks[$code] ?? ['qty' => 0, 'rate' => 0];
                $qty = $sData['qty'];
                $avgRate = $sData['rate'];
                
                // Ambil gap transaksi
                $trxGap = Transaction::where('branch_id', $bId)->where('currency', $code)->where('created_at', '>=', $dateCash . ' 00:00:00')->where('created_at', '<', $targetDate . ' 00:00:00')->orderBy('created_at')->get(); 
                
                foreach ($trxGap as $t) {
                    if ($t->type == 'buy') {
                        $valBefore = $qty * $avgRate; $valNew = $t->total_idr; $qty += $t->amount_foreign;
                        if ($qty > 0) $avgRate = ($valBefore + $valNew) / $qty; else $avgRate = 0;
                    } else { $qty -= $t->amount_foreign; }
                }
                if (!isset($totalStocks[$code])) $totalStocks[$code] = ['qty'=>0, 'val'=>0];
                $totalStocks[$code]['qty'] += $qty; $totalStocks[$code]['val'] += ($qty * $avgRate);
            }
        }
        
        // Finalisasi Stok Gabungan
        $finalStocks = [];
        foreach ($totalStocks as $code => $dt) {
            $finalStocks[$code] = ['qty' => $dt['qty'], 'rate' => ($dt['qty'] != 0) ? ($dt['val'] / $dt['qty']) : 0];
        }
        return ['cash' => $totalCash, 'stocks' => $finalStocks];
    }
}