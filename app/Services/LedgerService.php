<?php

namespace App\Services;

use App\Models\JournalItem;
use App\Models\ChartOfAccount; // [WAJIB] Import Model COA
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Ambil Data Buku Besar untuk Satu Akun
     * (Fixed: Sekarang sudah membaca Saldo Awal dari Master COA)
     */
    public function getAccountLedger($accountId, $startDate, $endDate)
    {
        // 0. [BARU] Ambil Saldo Awal Bawaan dari Master COA (Setup Awal)
        $account = ChartOfAccount::find($accountId);
        $masterOpeningBalance = $account ? $account->opening_balance : 0;

        // 1. Hitung Mutasi Jurnal SEBELUM Start Date (Opening Journal)
        // Ambil semua transaksi jurnal yang terjadi sebelum tanggal filter
        $openingJournalData = JournalItem::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($startDate) {
                $q->where('date', '<', $startDate);
            })
            ->select(DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(credit) as total_credit'))
            ->first();

        // Hitung Net Mutasi dari Jurnal Masa Lalu
        // Rumus Standar Ledger: Saldo = Debit - Kredit (Nanti disesuaikan sifat akun di view)
        $journalOpening = ($openingJournalData->total_debit ?? 0) - ($openingJournalData->total_credit ?? 0);

        // [FIX] Saldo Awal Total = Saldo Master + Saldo Jurnal Lalu
        $finalOpeningBalance = $masterOpeningBalance + $journalOpening;

        // 2. Ambil Transaksi Periode Ini
        $transactions = JournalItem::with(['journalEntry'])
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.date', 'asc') // Urutkan tanggal
            ->orderBy('journal_entries.created_at', 'asc') // Urutkan jam input
            ->select('journal_items.*') // Ambil kolom item saja agar bersih
            ->get();

        // 3. Hitung Saldo Berjalan (Running Balance)
        $data = [];
        $currentBalance = $finalOpeningBalance; // [FIX] Start dari Saldo Awal Gabungan
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($transactions as $trx) {
            $debit = $trx->debit;
            $credit = $trx->credit;

            // Rumus Saldo Berjalan: Saldo Lalu + Debit - Kredit
            $currentBalance += ($debit - $credit);

            $trx->running_balance = $currentBalance;
            $data[] = $trx;

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        return [
            'opening_balance' => $finalOpeningBalance, // [FIX] Mengembalikan nilai yang benar
            'transactions' => $data,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'ending_balance' => $currentBalance
        ];
    }
}