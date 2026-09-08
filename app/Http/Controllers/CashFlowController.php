<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JournalItem;
use App\Models\ChartOfAccount;
use App\Models\Branch;
use App\Models\Transaction;      
use App\Models\Expense;          
use App\Models\InitialCapital;   
use App\Models\InternalMutation; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashFlowController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // 1. FILTER TANGGAL (HARIAN)
        $filterDate = $request->input('date', date('Y-m-d'));
        
        // 2. LOGIKA HAK AKSES CABANG
        $branchId = null;
        $branchName = 'SEMUA CABANG';
        $isRestricted = false;

        if ($user->role == 'cashier') {
            $branchId = $user->branch_id;
            if (!$branchId) {
                $firstBranch = $user->branches->first();
                $branchId = $firstBranch ? $firstBranch->id : -1;
            }
            $isRestricted = true; 
            $b = Branch::find($branchId);
            $branchName = $b ? $b->name : 'TIDAK ADA CABANG';
        } else {
            $branchId = $request->input('branch_id');
            $isRestricted = false; 
            if ($branchId) {
                $b = Branch::find($branchId);
                $branchName = $b ? $b->name : '-';
            }
        }

        // 3. CARI AKUN KAS (1-1002)
        $kasAccount = ChartOfAccount::where('code', '1-1002')->first();
        if (!$kasAccount) {
            return back()->withErrors(['msg' => 'Akun Kas Kasir (1-1002) tidak ditemukan di COA.']);
        }

        // ==================================================================================
        // 4. HITUNG SALDO AWAL (OPTIMIZED SQL AGGREGATION)
        // ==================================================================================
        
        // Tentukan daftar cabang yang akan dihitung
        if ($branchId && $branchId != -1) {
            $targetBranchIds = [$branchId];
        } else {
            $targetBranchIds = Branch::pluck('id')->toArray();
        }

        $saldoAwal = 0;

        foreach ($targetBranchIds as $bId) {
            // A. Cari Modal Awal Terakhir sebelum/pada tanggal filter
            $lastCap = InitialCapital::where('branch_id', $bId)
                        ->whereDate('date', '<', $filterDate) // Sebelum hari ini
                        ->orderBy('date', 'desc')
                        ->first();
            
            // Start Point
            $startCash = $lastCap ? $lastCap->amount : 0;
            $dateCheckpoint = $lastCap ? $lastCap->date : '2000-01-01'; // Fallback aman

            // [OPTIMASI 1] Gabungkan Query Transaksi (Cash In & Cash Out) jadi 1 Query
            $trxSummary = Transaction::selectRaw("
                            SUM(CASE WHEN type = 'sell' THEN total_idr ELSE 0 END) as total_in,
                            SUM(CASE WHEN type = 'buy' THEN total_idr ELSE 0 END) as total_out
                        ")
                        ->where('branch_id', $bId)
                        ->where('created_at', '>=', $dateCheckpoint . ' 00:00:00')
                        ->where('created_at', '<', $filterDate . ' 00:00:00') // Sampai sebelum hari ini
                        ->where('payment_method', 'CASH') // Hanya Tunai
                        ->first();

            $cashIn  = $trxSummary->total_in ?? 0;
            $cashOut = $trxSummary->total_out ?? 0;

            // [OPTIMASI 2] Biaya Operasional (Simple Sum)
            $expenses = Expense::where('branch_id', $bId)
                        ->where('date', '>=', $dateCheckpoint)
                        ->where('date', '<', $filterDate)
                        ->sum('amount');

            // [OPTIMASI 3] Mutasi Internal (Gabung In/Out)
            $mutSummary = InternalMutation::selectRaw("
                            SUM(CASE WHEN type = 'bank_to_cash' THEN amount ELSE 0 END) as bank_to_cash,
                            SUM(CASE WHEN type = 'cash_to_bank' THEN amount ELSE 0 END) as cash_to_bank
                        ")
                        ->where('branch_id', $bId)
                        ->where('transaction_date', '>=', $dateCheckpoint)
                        ->where('transaction_date', '<', $filterDate)
                        ->first();
            
            $bankToCash = $mutSummary->bank_to_cash ?? 0;
            $cashToBank = $mutSummary->cash_to_bank ?? 0;

            // C. Total Saldo Awal Cabang Ini
            $branchStartBalance = $startCash + $cashIn - $cashOut - $expenses + $bankToCash - $cashToBank;
            
            // Akumulasi ke Total Global
            $saldoAwal += $branchStartBalance;
        }

        // ==================================================================================
        // 5. AMBIL MUTASI HARI INI (OPTIMIZED JOIN)
        // ==================================================================================
        
        // Kita gunakan JOIN langsung untuk performa lebih baik daripada whereHas
        $query = JournalItem::join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_items.account_id', $kasAccount->id)
            ->whereDate('journal_entries.date', $filterDate);

        // Filter Cabang (Jika ada)
        if ($branchId && $branchId != -1) {
            $query->where('journal_entries.branch_id', $branchId);
        }

        $mutasi = $query->select(
                'journal_items.debit',
                'journal_items.credit',
                'journal_entries.reference_no as trx_ref',
                'journal_entries.description as trx_desc',
                'journal_entries.created_at as entry_created_at'
            )
            ->orderBy('journal_entries.created_at', 'asc') // Urut kronologis
            ->get();

        // 6. SUSUN DATA (Running Balance)
        // Proses ini ringan karena dilakukan di RAM hanya untuk data hari ini (bukan ribuan data sejarah)
        $data = [];
        $currentBalance = $saldoAwal;

        foreach ($mutasi as $item) {
            $masuk = $item->debit;
            $keluar = $item->credit;

            $currentBalance += ($masuk - $keluar);

            $data[] = [
                'time' => date('H:i', strtotime($item->entry_created_at)),
                'ref'  => $item->trx_ref,
                'desc' => $item->trx_desc,
                'in'   => $masuk,
                'out'  => $keluar,
                'balance' => $currentBalance
            ];
        }

        // List cabang untuk dropdown Admin
        $branches = Branch::all();

        return view('admin.reports.cashflow', compact(
            'data', 'saldoAwal', 'currentBalance', 
            'filterDate', 'branchId', 'branchName', 
            'isRestricted', 'branches'
        ));
    }
}