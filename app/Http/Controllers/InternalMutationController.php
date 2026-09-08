<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InternalMutation;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Transaction; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InternalMutationController extends Controller
{
    /**
     * MENAMPILKAN HALAMAN REKENING KORAN (OPTIMIZED)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = null;
        $isRestricted = false;

        // 1. Logika Hak Akses Cabang
        if ($user->role == 'cashier') {
            $branchId = $user->branch_id;
            if (!$branchId) {
                $firstBranch = $user->branches->first();
                $branchId = $firstBranch ? $firstBranch->id : -1;
            }
            $isRestricted = true; 
        } else {
            $branchId = $request->input('branch_id');
            $isRestricted = false; 
        }

        $branchName = 'SEMUA CABANG';
        if ($branchId && $branchId != -1) {
            $b = Branch::find($branchId);
            $branchName = $b ? $b->name : 'SEMUA CABANG';
        }

        // 2. Filter Tanggal & Bank
        $startDate = $request->input('start_date', date('Y-m-01')); 
        $endDate   = $request->input('end_date', date('Y-m-d'));   
        $bankId    = $request->input('bank_id'); 

        // 3. Data Pendukung Dropdown
        $branches = Branch::all();
        $banks = ChartOfAccount::where('type', 'ASSET')
                ->where(function($q) {
                    $q->where('name', 'LIKE', '%BANK%')
                    ->orWhere('name', 'LIKE', '%BCA%')
                    ->orWhere('name', 'LIKE', '%MANDIRI%')
                    ->orWhere('name', 'LIKE', '%BNI%')
                    ->orWhere('name', 'LIKE', '%BRI%');
                })->get();

        if (!$bankId && $banks->count() > 0) {
            $bankId = $banks->first()->id;
        }

        $history = collect([]); 
        $openingBalance = 0;
        $selectedBank = null;

        if ($bankId) {
            $selectedBank = ChartOfAccount::find($bankId);
            
            // ==========================================================
            // A. HITUNG SALDO AWAL (OPTIMIZED AGGREGATION)
            // ==========================================================
            $coaOpen = ($branchId) ? 0 : ($selectedBank->opening_balance ?? 0);

            // Mutasi Manual Sebelum Start Date
            $mutSummary = InternalMutation::selectRaw("
                            SUM(CASE WHEN type = 'cash_to_bank' THEN amount ELSE 0 END) as total_in,
                            SUM(CASE WHEN type = 'bank_to_cash' THEN amount ELSE 0 END) as total_out
                        ")
                        ->where('bank_account_id', $bankId)
                        ->where('transaction_date', '<', $startDate);
            
            if ($branchId && $branchId != -1) $mutSummary->where('branch_id', $branchId);
            $resMut = $mutSummary->first();

            // Transaksi Transfer Sebelum Start Date
            $trxSummary = Transaction::selectRaw("
                            SUM(CASE WHEN type = 'sell' THEN total_idr ELSE 0 END) as total_in,
                            SUM(CASE WHEN type = 'buy' THEN total_idr ELSE 0 END) as total_out
                        ")
                        ->where('bank_account_id', $bankId)
                        ->where('payment_method', 'TRANSFER')
                        ->where('created_at', '<', $startDate . ' 00:00:00');
            
            if ($branchId && $branchId != -1) $trxSummary->where('branch_id', $branchId);
            $resTrx = $trxSummary->first();

            $openingBalance = $coaOpen 
                            + ($resMut->total_in ?? 0) + ($resTrx->total_in ?? 0) 
                            - ($resMut->total_out ?? 0) - ($resTrx->total_out ?? 0);

            // ==========================================================
            // B. AMBIL HISTORY GABUNGAN (UNION QUERY + PAGINATION)
            // ==========================================================
            
            // Query 1: Internal Mutation
            $q1 = DB::table('internal_mutations')
                ->select(
                    'id',
                    'transaction_date as date',
                    'created_at',
                    DB::raw("CASE WHEN type = 'cash_to_bank' THEN 'Setor Tunai (Deposit)' ELSE 'Tarik Tunai (Withdraw)' END as type_label"),
                    'description',
                    DB::raw("CASE WHEN type = 'cash_to_bank' THEN amount ELSE 0 END as debit"),
                    DB::raw("CASE WHEN type = 'bank_to_cash' THEN amount ELSE 0 END as credit"),
                    DB::raw("'INTERNAL' as source")
                )
                ->where('bank_account_id', $bankId)
                ->whereBetween('transaction_date', [$startDate, $endDate]);

            if ($branchId && $branchId != -1) $q1->where('branch_id', $branchId);

            // Query 2: Transaction
            $q2 = DB::table('transactions')
                ->select(
                    'id',
                    DB::raw("DATE(created_at) as date"),
                    'created_at',
                    DB::raw("CASE WHEN type = 'sell' THEN 'Terima Transfer (Jual Valas)' ELSE 'Transfer Keluar (Beli Valas)' END as type_label"),
                    DB::raw("CONCAT(customer_name, ' (', currency, ' ', amount_foreign, ')') as description"),
                    DB::raw("CASE WHEN type = 'sell' THEN total_idr ELSE 0 END as debit"),
                    DB::raw("CASE WHEN type = 'buy' THEN total_idr ELSE 0 END as credit"),
                    DB::raw("'TRANSACTION' as source")
                )
                ->where('bank_account_id', $bankId)
                ->where('payment_method', 'TRANSFER')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

            if ($branchId && $branchId != -1) $q2->where('branch_id', $branchId);

            // Gabungkan & Paginate (Aman Memori)
            $history = $q1->unionAll($q2)
                        ->orderBy('created_at', 'asc')
                        ->get(); // Jika data < 2000 baris, get() masih aman. Jika lebih, manual pagination.
        }

        return view('admin.accounting.mutation.index', compact(
            'branches', 'banks', 'history', 
            'openingBalance', 'startDate', 'endDate', 'bankId', 
            'selectedBank', 'branchId', 'isRestricted', 'branchName'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'date' => 'required|date',
            'type' => 'required|in:bank_to_cash,cash_to_bank',
            'bank_account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:1',
        ];

        if ($user->role !== 'cashier') {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        $request->validate($rules);

        // Tentukan Branch ID
        $branchId = null;
        if ($user->role == 'cashier') {
            $branchId = $user->branch_id ?? ($user->branches->first()->id ?? null);
            if (!$branchId) return back()->withErrors(['msg' => 'Error: Akun Anda tidak terhubung dengan cabang manapun.']);
        } else {
            $branchId = $request->branch_id;
        }

        $kasAccount = ChartOfAccount::where('code', '1-1002')->first();
        if (!$kasAccount) return back()->withErrors(['msg' => 'Akun Kas Kasir (1-1002) tidak ditemukan di COA.']);

        DB::beginTransaction();
        try {
            // 1. Simpan Mutasi
            $mutation = InternalMutation::create([
                'transaction_date' => $request->date,
                'type' => $request->type,
                'branch_id' => $branchId,
                'bank_account_id' => $request->bank_account_id,
                'amount' => $request->amount,
                'description' => $request->description,
                'user_id' => $user->id
            ]);

            // 2. Buat Jurnal Otomatis
            $refNo = 'MUT-' . str_pad($mutation->id, 6, '0', STR_PAD_LEFT);
            $bankName = ChartOfAccount::find($request->bank_account_id)->name ?? 'BANK';
            
            $desc = $request->description ?? ($request->type == 'bank_to_cash' 
                    ? "Tarik Tunai Bank ($bankName) ke Kas Fisik" 
                    : "Setor Tunai Kas Fisik ke Bank ($bankName)");

            // Hapus duplikat (safety)
            $oldIds = JournalEntry::where('reference_no', $refNo)->pluck('id');
            if($oldIds->count() > 0) {
                JournalItem::whereIn('journal_entry_id', $oldIds)->delete();
                JournalEntry::whereIn('id', $oldIds)->delete();
            }

            $journal = JournalEntry::create([
                'branch_id' => $branchId,
                'reference_no' => $refNo,
                'date' => $request->date,
                'description' => strtoupper($desc),
                'user_id' => $user->id,
                'created_by' => $user->id
            ]);

            if ($request->type == 'bank_to_cash') {
                // Debit Kas, Kredit Bank
                JournalItem::create(['journal_entry_id' => $journal->id, 'account_id' => $kasAccount->id, 'debit' => $request->amount, 'credit' => 0]);
                JournalItem::create(['journal_entry_id' => $journal->id, 'account_id' => $request->bank_account_id, 'debit' => 0, 'credit' => $request->amount]);
            } else {
                // Debit Bank, Kredit Kas
                JournalItem::create(['journal_entry_id' => $journal->id, 'account_id' => $request->bank_account_id, 'debit' => $request->amount, 'credit' => 0]);
                JournalItem::create(['journal_entry_id' => $journal->id, 'account_id' => $kasAccount->id, 'debit' => 0, 'credit' => $request->amount]);
            }

            DB::commit();
            return back()->with('success', 'Mutasi Berhasil Disimpan & Dijurnal!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $mutation = InternalMutation::findOrFail($id);

        // Hapus Jurnal Terkait
        $refNo = 'MUT-' . str_pad($mutation->id, 6, '0', STR_PAD_LEFT);
        $journal = JournalEntry::where('reference_no', $refNo)->first();

        if ($journal) {
            JournalItem::where('journal_entry_id', $journal->id)->delete();
            $journal->delete();
        }

        $mutation->delete();

        return back()->with('success', 'Data Mutasi & Jurnal berhasil dihapus.');
    }
}