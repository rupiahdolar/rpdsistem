<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DebtPayment;
use App\Models\LoanReceipt;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Auth;

class DebtPaymentController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        
        $user = Auth::user();
        
        $branchId = ($user->role === 'admin' || $user->role === 'owner') 
                    ? $request->input('branch_id') 
                    : ($user->branches->first()->id ?? session('branch_id'));

        // 1. Ambil data Pembayaran Utang (Cicilan)
        $query = DebtPayment::with(['branch', 'liabilityAccount', 'paymentAccount', 'user'])
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $payments = $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->get();
        $totalAmount = $payments->sum('amount');
        $totalInterest = $payments->sum('interest_amount');

        // [BARU] 2. Ambil data Penerimaan Pinjaman Baru
        $loanQuery = LoanReceipt::with(['branch', 'liabilityAccount', 'cashAccount', 'user'])
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year);
        
        if ($branchId) {
            $loanQuery->where('branch_id', $branchId);
        }
        
        $loans = $loanQuery->orderBy('date', 'desc')->orderBy('created_at', 'desc')->get();
        $totalLoans = $loans->sum('amount');

        // Ambil akun jenis LIABILITY (Utang) untuk pilihan form
        $liabilityAccounts = ChartOfAccount::where('type', 'LIABILITY')->orderBy('code')->get();
        
        // Ambil akun jenis ASSET (Kas/Bank) untuk sumber pembayaran / penerimaan
        $cashAccounts = ChartOfAccount::where('type', 'ASSET')
            ->where(function($q) {
                $q->where('code', 'like', '1-10%')
                  ->orWhere('name', 'LIKE', '%BANK%')
                  ->orWhere('name', 'LIKE', '%KAS%');
            })->orderBy('code')->get();

        return view('admin.accounting.debt_payment.index', compact(
            'payments', 'loans', 'month', 'year', 'branchId', 
            'totalAmount', 'totalInterest', 'totalLoans', 
            'liabilityAccounts', 'cashAccounts'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'liability_account_id' => 'required|exists:chart_of_accounts,id',
            'payment_account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:1',
            'interest_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $user = Auth::user();
        $branchId = null;

        if ($user->role === 'admin' || $user->role === 'owner') {
            $branchId = $request->branch_id ?? $user->branches->first()->id ?? 1;
        } else {
            $branchId = session('branch_id') ?? $user->branches->first()->id ?? 1;
        }

        $payment = DebtPayment::create([
            'branch_id' => $branchId,
            'date' => $request->date,
            'liability_account_id' => $request->liability_account_id,
            'payment_account_id' => $request->payment_account_id,
            'amount' => $request->amount,
            'interest_amount' => $request->interest_amount ?? 0,
            'description' => strtoupper($request->description),
            'created_by' => $user->id,
        ]);

        // Panggil service jurnal otomatis pembayaran utang
        AccountingService::recordDebtPayment($payment);

        return back()->with('success', 'Pembayaran Utang berhasil dicatat & terhubung ke Laporan.');
    }

    public function destroy($id)
    {
        $payment = DebtPayment::findOrFail($id);

        // Hapus jurnal terkait pembayaran
        AccountingService::deleteDebtPaymentJournal($payment->id);
        
        // Hapus data pembayaran
        $payment->delete();

        return back()->with('success', 'Data Pembayaran Utang berhasil dihapus.');
    }

    // =========================================================================
    // [BARU] METHOD UNTUK PENERIMAAN PINJAMAN BARU
    // =========================================================================

    public function storeLoan(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'liability_account_id' => 'required|exists:chart_of_accounts,id',
            'cash_account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
        ]);

        $user = Auth::user();
        $branchId = null;

        if ($user->role === 'admin' || $user->role === 'owner') {
            $branchId = $request->branch_id ?? $user->branches->first()->id ?? 1;
        } else {
            $branchId = session('branch_id') ?? $user->branches->first()->id ?? 1;
        }

        $loan = LoanReceipt::create([
            'branch_id' => $branchId,
            'date' => $request->date,
            'liability_account_id' => $request->liability_account_id,
            'cash_account_id' => $request->cash_account_id,
            'amount' => $request->amount,
            'description' => strtoupper($request->description),
            'created_by' => $user->id,
        ]);

        // Panggil service jurnal otomatis penerimaan pinjaman
        AccountingService::recordLoanReceipt($loan);

        return back()->with('success', 'Penerimaan Pinjaman Baru berhasil dicatat & dijurnalkan.');
    }

    public function destroyLoan($id)
    {
        $loan = LoanReceipt::findOrFail($id);

        // Hapus jurnal terkait penerimaan pinjaman
        AccountingService::deleteLoanReceiptJournal($loan->id);
        
        // Hapus data pinjaman
        $loan->delete();

        return back()->with('success', 'Data Penerimaan Pinjaman berhasil dihapus.');
    }
}