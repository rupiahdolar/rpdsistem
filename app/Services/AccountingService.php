<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Expense;
use App\Models\InitialCapital;
use App\Models\EquityMutation;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use App\Models\AssetDepreciation;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AccountingService
{
    /**
     * PETA AKUN (MAPPING)
     * Sesuaikan kode ini dengan Seed di migration create_accounting_tables.php
     */
    const ACC_KAS_BESAR       = '1-1001'; // Kas Vault/Brankas Utama
    const ACC_KAS_KASIR       = '1-1002'; // Kas Laci (Modal Kerja Harian)
    const ACC_PERSEDIAAN_VALAS= '1-1010';
    const ACC_AKUMULASI_PENY  = '1-2099'; // [BARU] Akumulasi Penyusutan (Kontra Aset)

    const ACC_MODAL_DISETOR   = '3-1001';
    const ACC_PRIVE           = '3-1002'; // Akun Prive
    
    const ACC_PENDAPATAN_JUAL = '4-1001';
    
    const ACC_HPP             = '5-1001'; // [BARU] Beban HPP
    const ACC_BEBAN_PENYUSUTAN= '6-2001'; // [BARU] Beban Penyusutan Aset
    const ACC_BEBAN_LAIN      = '6-1099';

    /**
     * 1. CATAT TRANSAKSI JUAL/BELI VALAS (Updated: Support Transfer)
     */
    public static function recordTransaction($transaction)
    {
        try {
            // A. Buat Header Jurnal
            $entry = JournalEntry::create([
                'date' => $transaction->created_at,
                'reference_no' => $transaction->transaction_code,
                'description' => "Transaksi " . ($transaction->type == 'buy' ? 'Beli' : 'Jual') . " " . $transaction->currency . " (" . $transaction->payment_method . ")",
                'branch_id' => $transaction->branch_id,
                'created_by' => $transaction->user_id,
            ]);

            // B. Tentukan Akun Kas (Uang Fisik atau Bank?)
            $accKas = null;
            if ($transaction->payment_method == 'TRANSFER' && $transaction->bank_account_id) {
                // Jika Transfer, ambil akun dari inputan
                $accKas = ChartOfAccount::find($transaction->bank_account_id);
            } else {
                // Jika Cash, pakai akun Kas Kasir (1-1002)
                $accKas = ChartOfAccount::where('code', self::ACC_KAS_KASIR)->first();
            }

            $accStokValas = ChartOfAccount::where('code', self::ACC_PERSEDIAAN_VALAS)->first();
            $accPendapatan = ChartOfAccount::where('code', self::ACC_PENDAPATAN_JUAL)->first();

            // Pastikan akun-akun penting ada
            if (!$accKas || !$accStokValas) {
                Log::error("Akun Kas/Valas tidak ditemukan di COA.");
                return;
            }

            // C. Logika Debit/Kredit
            if ($transaction->type == 'buy') {
                // BELI VALAS:
                // Debit: Persediaan Valas (Bertambah)
                // Kredit: Kas/Bank (Berkurang - Uang Keluar)
                
                self::insertItem($entry->id, $accStokValas->id, $transaction->total_idr, 0);
                self::insertItem($entry->id, $accKas->id, 0, $transaction->total_idr);

            } else {
                // JUAL VALAS:
                // Debit: Kas/Bank (Bertambah - Uang Masuk)
                // Kredit: Pendapatan Penjualan (Omzet)
                
                // Note: HPP nanti dihitung di akhir bulan (Metode Periodik/Hybrid)
                // Di sini kita catat penjualan kotor dulu sebagai pendapatan
                
                self::insertItem($entry->id, $accKas->id, $transaction->total_idr, 0);
                
                if ($accPendapatan) {
                    self::insertItem($entry->id, $accPendapatan->id, 0, $transaction->total_idr);
                }
            }

        } catch (\Exception $e) {
            Log::error("Gagal menjurnal transaksi: " . $e->getMessage());
        }
    }

    /**
     * HAPUS JURNAL TRANSAKSI (Dipanggil saat Hapus Data Nasabah/Transaksi)
     */
    public static function deleteTransactionJournal($trxCode)
    {
        // Cari Jurnal berdasarkan Ref No (TRX-XXXX)
        $journal = JournalEntry::where('reference_no', $trxCode)->first();
        
        if ($journal) {
            // Hapus Detail Item (Debit/Kredit)
            JournalItem::where('journal_entry_id', $journal->id)->delete();
            // Hapus Header Jurnal
            $journal->delete();
        }
    }

    /**
     * 2. CATAT BIAYA OPERASIONAL (Direct Mapping)
     */
    public static function recordExpense($expense)
    {
        try {
            // MAPPING PASTI (Hardcode sesuai Value di Dropdown HTML)
            $code = match ($expense->category) {
                'GAJI'        => '6-1001', // Beban Gaji
                'LISTRIK'     => '6-1002', // Listrik, Air, Wifi
                'SEWA'        => '6-1003', // Sewa Kantor
                'ATK'         => '6-1004', // ATK
                'MAINTENANCE' => '6-1005', // Pemeliharaan
                'TRANSPORT'   => '6-1006', // BBM & Transport
                'PAJAK'       => '6-1008', // Pajak
                'ENTERTAINMENT'=> '6-1009',// Entertainment
                'SEMBAHYANG'  => '6-1010', // Sembahyang
                default       => '6-1099'  // LAINNYA
            };

            // 1. Cari Akun Beban
            $expenseAccount = ChartOfAccount::where('code', $code)->first();
            
            // Safety: Jika akun terhapus, masukkan ke Lain-lain
            if (!$expenseAccount) {
                $expenseAccount = ChartOfAccount::where('code', self::ACC_BEBAN_LAIN)->first();
            }

            // 2. Cari Akun Kas (Sumber Dana = KAS KASIR)
            $creditAccount = ChartOfAccount::where('code', self::ACC_KAS_KASIR)->first(); 

            if ($expenseAccount && $creditAccount) {
                $entry = JournalEntry::create([
                    'date' => $expense->date,
                    'reference_no' => 'EXP-' . str_pad($expense->id, 6, '0', STR_PAD_LEFT),
                    'description' => $expense->name . ' (' . $expense->category . ')',
                    'branch_id' => $expense->branch_id,
                    'created_by' => $expense->created_by,
                ]);

                // Debit: Beban Sesuai Kategori
                self::insertItem($entry->id, $expenseAccount->id, $expense->amount, 0);
                
                // Kredit: Kas Kasir (Mengurangi Laci)
                self::insertItem($entry->id, $creditAccount->id, 0, $expense->amount);
            }
        } catch (\Exception $e) {
            Log::error("Gagal menjurnal biaya: " . $e->getMessage());
        }
    }

    /**
     * UPDATE JURNAL BIAYA (Saat user edit biaya)
     */
    public static function updateExpenseJournal($expense)
    {
        $refNo = 'EXP-' . str_pad($expense->id, 6, '0', STR_PAD_LEFT);
        self::deleteExpenseJournal($refNo); // Hapus jurnal lama
        self::recordExpense($expense);      // Buat jurnal baru dengan angka baru
    }

    /**
     * HAPUS JURNAL BIAYA (Saat user hapus biaya)
     */
    public static function deleteExpenseJournal($referenceNoOrId)
    {
        // Deteksi apakah input berupa ID (angka) atau RefNo (String)
        $ref = is_numeric($referenceNoOrId) ? 'EXP-' . str_pad($referenceNoOrId, 6, '0', STR_PAD_LEFT) : $referenceNoOrId;
        
        $entry = JournalEntry::where('reference_no', $ref)->first();
        if ($entry) {
            // Hapus detail items dulu
            JournalItem::where('journal_entry_id', $entry->id)->delete();
            // Hapus header
            $entry->delete();
        }
    }

    /**
     * 3. CATAT MODAL AWAL HARIAN (Dipanggil dari CapitalController)
     */
    public static function recordCapital(InitialCapital $capital)
    {
        // [BARU] Bungkus dengan DB Transaction agar aman
        DB::transaction(function () use ($capital) {
            try {
                // A. Buat/Update Header Jurnal
                // Ref No: CAP-000XXX (Sesuai ID Modal)
                $entry = JournalEntry::updateOrCreate(
                    ['reference_no' => 'CAP-' . str_pad($capital->id, 6, '0', STR_PAD_LEFT)],
                    [
                        'date' => $capital->date,
                        'description' => "Setoran Modal Awal Harian",
                        'branch_id' => $capital->branch_id,
                        'created_by' => $capital->user_id,
                    ]
                );

                // [PENTING] Hapus item jurnal lama sebelum input yang baru
                // Ini mencegah double entry saat update modal (data lama dihapus dulu)
                JournalItem::where('journal_entry_id', $entry->id)->delete();

                // Ambil ID Akun
                $accKasKasir    = ChartOfAccount::where('code', self::ACC_KAS_KASIR)->first()->id; 
                $accPersediaan  = ChartOfAccount::where('code', self::ACC_PERSEDIAAN_VALAS)->first()->id;
                $accModal       = ChartOfAccount::where('code', self::ACC_MODAL_DISETOR)->first()->id;

                // B. PROSES MODAL RUPIAH
                if ($capital->amount > 0) {
                    self::insertItem($entry->id, $accKasKasir, $capital->amount, 0);
                    self::insertItem($entry->id, $accModal, 0, $capital->amount);
                }

                // C. PROSES MODAL VALAS (Stok)
                if (!empty($capital->forex_stocks)) {
                    // Handle jika data tersimpan sebagai String JSON atau Array
                    $stocks = is_string($capital->forex_stocks) ? json_decode($capital->forex_stocks, true) : $capital->forex_stocks;

                    if (is_array($stocks)) {
                        foreach ($stocks as $currency => $data) {
                            // [UPDATE] Cast ke float untuk keamanan kalkulasi
                            $qty = isset($data['qty']) ? (float)$data['qty'] : 0;
                            $rate = isset($data['rate']) ? (float)$data['rate'] : 0;
                            
                            if ($qty > 0 && $rate > 0) {
                                $totalValuation = $qty * $rate; // Nilai Rupiah dari Valas

                                // Debit: PERSEDIAAN VALAS
                                // Kredit: MODAL DISETOR
                                self::insertItem($entry->id, $accPersediaan, $totalValuation, 0);
                                self::insertItem($entry->id, $accModal, 0, $totalValuation);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Gagal menjurnal modal: " . $e->getMessage());
                throw $e; // Wajib throw agar transaksi dibatalkan (Rollback) jika error
            }
        });
    }

    /**
     * 4. CATAT TUTUP SHIFT (Opsional, jika uang disetor ke Vault)
     */
    public static function recordEndOfShift($shift, $amount)
    {
        try {
            // Validasi: Jangan jurnal kalau nominal 0
            if ($amount <= 0) return;

            $entry = JournalEntry::create([
                'date' => now(),
                'reference_no' => 'EOS-' . str_pad($shift->id, 6, '0', STR_PAD_LEFT), // EOS = End Of Shift
                'description' => "Setoran Tutup Shift Kasir (Ke Vault)",
                'branch_id' => $shift->branch_id,
                'created_by' => $shift->user_id,
            ]);

            $accKasKasir = ChartOfAccount::where('code', self::ACC_KAS_KASIR)->first()->id;
            $accKasBesar = ChartOfAccount::where('code', self::ACC_KAS_BESAR)->first()->id;

            // Debit: Kas Besar (Uang Masuk Brankas)
            self::insertItem($entry->id, $accKasBesar, $amount, 0);

            // Kredit: Kas Kasir (Uang Keluar Laci)
            self::insertItem($entry->id, $accKasKasir, 0, $amount);

        } catch (\Exception $e) {
            Log::error("Gagal menjurnal tutup shift: " . $e->getMessage());
        }
    }

    /**
     * 5. CATAT PEMBELIAN ASET TETAP (DIPERBAIKI)
     */
    public static function recordAssetPurchase($asset, $paymentAccountId = null)
    {
        try {
            $entry = JournalEntry::create([
                'date' => $asset->purchase_date,
                'reference_no' => 'AST-' . str_pad($asset->id, 6, '0', STR_PAD_LEFT),
                'description' => "Pembelian Aset Tetap: " . $asset->name,
                'branch_id' => $asset->branch_id,
                'created_by' => auth()->id(), 
            ]);

            // Mapping Akun Aset
            $assetAccountCode = '1-2001'; // Default: Inventaris Kantor
            if (str_contains(strtoupper($asset->name), 'MOTOR') || str_contains(strtoupper($asset->name), 'MOBIL')) {
                $assetAccountCode = '1-2002'; // Kendaraan
            }

            $accAsset = ChartOfAccount::where('code', $assetAccountCode)->first();
            if (!$accAsset) $accAsset = ChartOfAccount::where('code', '1-2001')->first();

            // Tentukan Akun Kas Pengurang
            if ($paymentAccountId) {
                $accKas = ChartOfAccount::find($paymentAccountId);
            } else {
                // Fallback jika null: Default Kas Besar (Vault)
                $accKas = ChartOfAccount::where('code', self::ACC_KAS_BESAR)->first();
            }

            if ($accAsset && $accKas) {
                // Debit: Aset Tetap
                self::insertItem($entry->id, $accAsset->id, $asset->purchase_cost, 0);
                // Kredit: Kas yang dipilih (Kas Besar, Kas Kasir, Bank, dll)
                self::insertItem($entry->id, $accKas->id, 0, $asset->purchase_cost);
            }

        } catch (\Exception $e) {
            Log::error("Gagal menjurnal aset: " . $e->getMessage());
        }
    }

    /**
     * 6. CATAT MUTASI EKUITAS (PRIVE / SETOR TAMBAHAN)
     * Agar Neraca Balance saat Owner ambil uang atau tambah modal.
     */
    public static function recordEquityMutation($mutation)
    {
        try {
            $refNo = 'EQT-' . str_pad($mutation->id, 6, '0', STR_PAD_LEFT);
            $desc  = ($mutation->type == 'PRIVE' ? 'Pengambilan Prive: ' : 'Setoran Modal Tambahan: ') . $mutation->description;

            // Buat Header Jurnal
            $entry = JournalEntry::updateOrCreate(
                ['reference_no' => $refNo],
                [
                    'date' => $mutation->date,
                    'description' => $desc,
                    'branch_id' => auth()->user()->branch_id ?? 1, // Default ke 1 jika null
                    'created_by' => auth()->id(),
                ]
            );

            // Bersihkan item lama (jika update)
            JournalItem::where('journal_entry_id', $entry->id)->delete();

            // Mapping Akun
            $accKas    = ChartOfAccount::where('code', self::ACC_KAS_BESAR)->first(); 
            $accModal  = ChartOfAccount::where('code', self::ACC_MODAL_DISETOR)->first();
            $accPrive  = ChartOfAccount::where('code', self::ACC_PRIVE)->first() ?? $accModal;

            if (!$accKas || !$accModal) return;

            if ($mutation->type == 'PRIVE') {
                // LOGIKA PRIVE:
                // Debit: Modal/Prive (Mengurangi Ekuitas)
                // Kredit: Kas Besar (Uang Keluar)
                self::insertItem($entry->id, $accPrive->id, $mutation->amount, 0);
                self::insertItem($entry->id, $accKas->id, 0, $mutation->amount);

            } else {
                // LOGIKA SETOR MODAL TAMBAHAN:
                // Debit: Kas Besar (Uang Masuk)
                // Kredit: Modal (Menambah Ekuitas)
                self::insertItem($entry->id, $accKas->id, $mutation->amount, 0);
                self::insertItem($entry->id, $accModal->id, 0, $mutation->amount);
            }

        } catch (\Exception $e) {
            Log::error("Gagal menjurnal ekuitas: " . $e->getMessage());
        }
    }

    /**
     * Hapus Jurnal Ekuitas (Saat data dihapus dari ReportController)
     */
    public static function deleteEquityMutationJournal($mutationId)
    {
        $refNo = 'EQT-' . str_pad($mutationId, 6, '0', STR_PAD_LEFT);
        $entry = JournalEntry::where('reference_no', $refNo)->first();
        
        if ($entry) {
            JournalItem::where('journal_entry_id', $entry->id)->delete();
            $entry->delete();
        }
    }

    /**
     * 7. [BARU] HITUNG & JURNAL HPP BULANAN (TUTUP BUKU - DIPERBAIKI)
     * Dipanggil oleh ClosingController di Akhir Bulan
     */
    public static function generateMonthlyHPPJournal($month, $year, $branchId = null)
    {
        $hpp = 0;

        try {
            // Diubah dari 3 menjadi 1 (Kantor Pusat)
            $bId = 1; 

            $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $endDate   = $startDate->copy()->endOfMonth()->endOfDay();

            // 1. Ambil Modal Terakhir (Checkpoint) secara global
            $lastCapital = InitialCapital::whereDate('date', '<=', $endDate)
                        ->orderBy('date', 'desc')->first();
            
            $checkpointDate = $lastCapital ? $lastCapital->date : '2000-01-01';
            $checkpointStocks = ($lastCapital && $lastCapital->forex_stocks) ? $lastCapital->forex_stocks : [];

            // 2. Hitung Pembelian Bulan Ini (Global seluruh transaksi)
            $qPurchases = Transaction::whereMonth('created_at', $month)
                        ->whereYear('created_at', $year)
                        ->where('type', 'buy');
            $totalPembelian = $qPurchases->sum('total_idr');

            // 3. Hitung Valuasi Awal & Akhir
            $valuasiAwalBulan = 0;
            $nilaiStokAkhir = 0;
            $currencies = Currency::where('is_active', 1)->get();

            // Pre-fetch Transactions untuk optimasi (Global)
            $allTrx = Transaction::whereDate('created_at', '>=', $checkpointDate)
                        ->whereDate('created_at', '<=', $endDate)
                        ->get();

            foreach($currencies as $curr){
                $code = $curr->code;
                $stockData = $checkpointStocks[$code] ?? ['qty' => 0, 'rate' => 0];
                $qty = $stockData['qty'];
                $rate = $stockData['rate'];

                // Roll forward dari Checkpoint sampai Akhir Bulan
                $trxCurr = $allTrx->where('currency', $code);
                
                // Fase 1: Checkpoint -> Awal Bulan (Untuk Valuasi Awal)
                $gapTrx = $trxCurr->filter(fn($t) => $t->created_at < $startDate);
                foreach($gapTrx as $t) {
                    if($t->type == 'buy') {
                        $newVal = ($qty * $rate) + $t->total_idr;
                        $qty += $t->amount_foreign;
                        if($qty > 0) $rate = $newVal / $qty;
                    } else {
                        $qty -= $t->amount_foreign;
                    }
                }
                $valuasiAwalBulan += ($qty * $rate);

                // Fase 2: Awal Bulan -> Akhir Bulan (Untuk Valuasi Akhir)
                $monthTrx = $trxCurr->filter(fn($t) => $t->created_at >= $startDate && $t->created_at <= $endDate);
                foreach($monthTrx as $t) {
                    if($t->type == 'buy') {
                        $newVal = ($qty * $rate) + $t->total_idr;
                        $qty += $t->amount_foreign;
                        if($qty > 0) $rate = $newVal / $qty;
                    } else {
                        $qty -= $t->amount_foreign;
                    }
                }
                $nilaiStokAkhir += ($qty * $rate);
            }

            // RUMUS HPP
            $hpp = ($valuasiAwalBulan + $totalPembelian) - $nilaiStokAkhir;

            // B. BUAT JURNAL PENYESUAIAN HPP (General / Pusat ID 3)
            $refNo = "CLS-HPP-$year-$month-GENERAL"; 
            
            $entry = JournalEntry::updateOrCreate(
                ['reference_no' => $refNo],
                [
                    'date' => $endDate, 
                    'description' => "Penyesuaian HPP & Stok Valas Konsolidasi Periode $month/$year",
                    'branch_id' => $bId,
                    'created_by' => auth()->id() ?? 1,
                ]
            );

            JournalItem::where('journal_entry_id', $entry->id)->delete(); 

            $accHPP = ChartOfAccount::where('code', self::ACC_HPP)->first();
            $accPersediaan = ChartOfAccount::where('code', self::ACC_PERSEDIAAN_VALAS)->first();

            Log::info("DEBUG HPP General - Cabang ID: $bId | Valuasi Awal: " . $valuasiAwalBulan);
            Log::info("DEBUG HPP General - Total Pembelian: " . $totalPembelian);
            Log::info("DEBUG HPP General - Nilai Stok Akhir: " . $nilaiStokAkhir);
            Log::info("DEBUG HPP General - Hasil HPP: " . $hpp);

            // PERBAIKAN 3: Dukung penyesuaian jika nilai $hpp bernilai minus
            if ($accHPP && $accPersediaan && $hpp != 0) {
                if ($hpp > 0) {
                    self::insertItem($entry->id, $accHPP->id, $hpp, 0);
                    self::insertItem($entry->id, $accPersediaan->id, 0, $hpp);
                } else {
                    $absHpp = abs($hpp);
                    self::insertItem($entry->id, $accPersediaan->id, $absHpp, 0);
                    self::insertItem($entry->id, $accHPP->id, 0, $absHpp);
                }
            }

        } catch (\Exception $e) {
            Log::error("Gagal generate HPP General: " . $e->getMessage());
        }

        // PERBAIKAN 4: Kembalikan nilai $hpp dengan aman di luar try-catch
        return $hpp;
    }

    /**
     * 8. [BARU] JURNAL PENYUSUTAN ASET BULANAN (DIPERBAIKI)
     * Dipanggil oleh ClosingController di Akhir Bulan
     */
    public static function generateMonthlyDepreciation($month, $year, $branchId = null)
    {
        try {
            // Diubah dari 3 menjadi 1 (Kantor Pusat)
            $bId = 1;

            $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $endDate   = $startDate->copy()->endOfMonth()->endOfDay();

            // Ambil seluruh depresiasi aset secara konsolidasi (tanpa filter cabang spesifik)
            $depreciations = AssetDepreciation::whereBetween('date', [$startDate, $endDate])->get();

            if ($depreciations->isEmpty()) return 0;

            $totalDepreciation = $depreciations->sum('amount');

            // RefNo dibuat general/konsolidasi
            $refNo = "CLS-DEP-$year-$month-GENERAL";

            $entry = JournalEntry::updateOrCreate(
                ['reference_no' => $refNo],
                [
                    'date' => $endDate,
                    'description' => "Beban Penyusutan Aset Konsolidasi Periode $month/$year",
                    'branch_id' => $bId,
                    'created_by' => auth()->id() ?? 1,
                ]
            );

            JournalItem::where('journal_entry_id', $entry->id)->delete();

            $accBebanPeny = ChartOfAccount::where('code', self::ACC_BEBAN_PENYUSUTAN)->first() 
                        ?? ChartOfAccount::where('code', self::ACC_BEBAN_LAIN)->first();
            
            $accAkumulasi = ChartOfAccount::where('code', self::ACC_AKUMULASI_PENY)->first();

            if (!$accAkumulasi) {
                Log::error("Penyusutan Gagal General: Akun Akumulasi Penyusutan (" . self::ACC_AKUMULASI_PENY . ") tidak ditemukan.");
                return 0;
            }

            if ($accBebanPeny && $accAkumulasi) {
                // Debit: Beban Penyusutan
                self::insertItem($entry->id, $accBebanPeny->id, $totalDepreciation, 0);
                // Kredit: Akumulasi Penyusutan
                self::insertItem($entry->id, $accAkumulasi->id, 0, $totalDepreciation);
            }
            
            return $totalDepreciation;

        } catch (\Exception $e) {
            Log::error("Gagal generate Depresiasi General: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * HELPER: Fungsi Insert Item Jurnal agar kode rapi
     */
    private static function insertItem($journalId, $accountId, $debit, $credit)
    {
        JournalItem::create([
            'journal_entry_id' => $journalId,
            'account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
        ]);
    }
}