<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Services\LedgerService;

class LedgerController extends Controller
{
    protected $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function index(Request $request)
    {
        // Filter Default
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-d'));
        $accountId = $request->input('account_id');

        // Ambil Daftar Akun untuk Dropdown
        $accounts = ChartOfAccount::orderBy('code', 'asc')->get();
        $selectedAccount = null;
        $ledgerData = null;

        // Jika User sudah memilih Akun, jalankan Service
        if ($accountId) {
            $selectedAccount = $accounts->where('id', $accountId)->first();
            
            if ($selectedAccount) {
                $ledgerData = $this->ledgerService->getAccountLedger($accountId, $startDate, $endDate);
            }
        }

        return view('admin.accounting.ledger.index', compact(
            'accounts', 'selectedAccount', 'ledgerData', 'startDate', 'endDate', 'accountId'
        ));
    }
}