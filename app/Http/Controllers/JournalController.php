<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\ChartOfAccount;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class JournalController extends Controller
{
    /**
     * MENAMPILKAN RIWAYAT JURNAL (DENGAN FILTER)
     */
    public function index(Request $request)
    {
        // 1. Ambil Parameter Filter
        $startDate = $request->input('start_date', date('Y-m-01')); // Default: Awal Bulan
        $endDate   = $request->input('end_date', date('Y-m-t'));   // Default: Akhir Bulan
        $branchId  = $request->input('branch_id');
        $userId    = $request->input('user_id');

        // 2. Query Dasar
        $query = JournalEntry::with(['items.chartOfAccount', 'branch', 'user'])
                    ->whereBetween('date', [$startDate, $endDate]);

        // 3. Logika Filter Cabang (Role Based)
        $user = Auth::user();

        if ($user->role == 'admin' || $user->role == 'owner') {
            // Admin/Owner boleh pilih cabang
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        } elseif ($user->role == 'cashier') {
            // Kasir dipaksa hanya melihat cabangnya sendiri
            $query->where('branch_id', $user->branch_id);
        }

        // 4. Filter User Pembuat (Opsional)
        if ($userId) {
            $query->where('created_by', $userId);
        }

        // 5. Eksekusi Query (Urutkan Terbaru)
        $entries = $query->orderBy('date', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->paginate(20)
                         ->withQueryString(); 

        // 6. Data Pendukung untuk Dropdown Filter
        $branches = Branch::all();
        $users = User::whereIn('role', ['admin', 'cashier'])->orderBy('name')->get();

        return view('admin.accounting.journal.index', compact(
            'entries', 'branches', 'users', 
            'startDate', 'endDate', 'branchId', 'userId'
        ));
    }

    /**
     * FORM INPUT JURNAL BARU MANUAL
     */
    public function create()
    {
        // Ambil semua akun untuk dropdown
        $accounts = ChartOfAccount::orderBy('code')->get();
        
        return view('admin.accounting.journal.create', compact('accounts'));
    }

    /**
     * SIMPAN JURNAL MANUAL
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'details' => 'required|array|min:2', // Minimal ada 2 baris (Debit & Kredit)
            'details.*.account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.credit' => 'required|numeric|min:0',
        ]);

        // Validasi Balance
        $totalDebit = collect($request->details)->sum('debit');
        $totalCredit = collect($request->details)->sum('credit');

        if (abs($totalDebit - $totalCredit) > 1) { 
            return back()->withInput()->withErrors(['msg' => 'Jurnal TIDAK SEIMBANG (Balance)! Total Debit: ' . number_format($totalDebit) . ', Total Kredit: ' . number_format($totalCredit)]);
        }

        if ($totalDebit == 0) {
            return back()->withInput()->withErrors(['msg' => 'Nominal tidak boleh nol!']);
        }

        DB::transaction(function () use ($request) {
            $user = Auth::user();

            // 1. Buat Header
            $entry = JournalEntry::create([
                'date' => $request->date,
                'reference_no' => 'JU-' . date('ymd') . '-' . Str::random(4),
                'description' => strtoupper($request->description),
                'branch_id' => $user->branch_id, 
                'created_by' => $user->id,
            ]);

            // 2. Buat Detail Items
            foreach ($request->details as $item) {
                if ($item['debit'] > 0 || $item['credit'] > 0) {
                    JournalItem::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $item['account_id'],
                        'debit' => $item['debit'],
                        'credit' => $item['credit'],
                    ]);
                }
            }
        });

        return redirect()->route('accounting.journals.index')->with('success', 'Jurnal Umum Berhasil Disimpan.');
    }

    /**
     * HAPUS JURNAL
     */
    public function destroy($id)
    {
        $entry = JournalEntry::findOrFail($id);
        
        // Hapus detail items dulu
        JournalItem::where('journal_entry_id', $entry->id)->delete();
        
        // Hapus Header
        $entry->delete(); 
        
        return back()->with('success', 'Data Jurnal & Buku Besar Berhasil Dihapus.');
    }

    /**
     * DETEKSI JURNAL HANTU (YANG TIDAK PUNYA TRANSAKSI ASLI)
     */
    public function ghosts()
    {
        // [PERINGATAN] Jika transaksi > 50rb, pluck() bisa berat. 
        // Tapi untuk fitur maintenance ini masih bisa ditoleransi dibanding sync.
        
        // 1. Ambil semua No Nota yang SAH dari tabel Transaksi
        $notaSah = \App\Models\Transaction::pluck('no_nota')->toArray();

        // 2. Cari Jurnal yang MENYAMAR jadi Transaksi tapi GAK ADA di daftar sah
        $hantu = JournalEntry::where(function($q) {
                        $q->where('reference_no', 'LIKE', 'TRX%')
                          ->orWhere('reference_no', 'LIKE', 'MR%')
                          ->orWhere('reference_no', 'LIKE', 'W%')
                          ->orWhere('reference_no', 'LIKE', 'SL%')
                          ->orWhere('reference_no', 'LIKE', 'RC%')
                          ->orWhere('reference_no', 'LIKE', 'AS%')
                          ->orWhere('reference_no', 'LIKE', '0000%'); 
                  })
                  ->whereNotIn('reference_no', $notaSah) 
                  ->orderBy('date', 'desc')
                  ->get();

        return view('admin.accounting.journal.ghosts', compact('hantu'));
    }

    /**
     * TOMBOL PEMUSNAH MASSAL
     */
    public function purgeGhosts(Request $request)
    {
        // Ambil ID yang mau dihapus dari form
        $ids = explode(',', $request->ids);

        if (count($ids) > 0) {
            // Hapus Item Jurnal dulu (Anaknya)
            JournalItem::whereIn('journal_entry_id', $ids)->delete();
            
            // Hapus Header Jurnal (Bapaknya)
            JournalEntry::whereIn('id', $ids)->delete();

            return back()->with('success', count($ids) . ' Data Hantu BERHASIL dimusnahkan selamanya!');
        }

        return back()->withErrors(['msg' => 'Tidak ada data yang dipilih.']);
    }

    /**
     * SINKRONISASI DARURAT (RECOVERY)
     * [OPTIMIZED] Menggunakan Chunk agar tidak Crash Memory
     */
    public function syncTransactionsToJournal()
    {
        // --- KONFIGURASI AKUN ---
        $idKasKasir      = 2;   
        $idPersediaan    = 5;   
        $idPendapatan    = 18;  
        
        $counter = 0;

        DB::beginTransaction();
        try {
            // [OPTIMASI MEMORY]
            // Jangan pakai all(), tapi pakai chunk(100).
            // Ini akan memproses 100 data, lalu membuang memori, ambil 100 lagi, dst.
            // Aman untuk puluhan ribu data.
            \App\Models\Transaction::chunk(100, function ($transactions) use (&$counter, $idKasKasir, $idPersediaan, $idPendapatan) {
                
                foreach ($transactions as $trx) {
                    
                    // [RAHASIA V4]
                    // Format Ref: NO_NOTA + "-" + ID_TRANSAKSI
                    $uniqueRef = $trx->no_nota . '-' . $trx->id;

                    // Cek apakah data unik ini sudah ada?
                    // Note: Query dalam loop (N+1) masih terjadi tapi karena chunk, 
                    // memory PHP tidak akan meledak.
                    $exists = JournalEntry::where('reference_no', $uniqueRef)->exists();

                    if (!$exists) {
                        
                        // LOGIKA BANK: Jika Transfer & ada ID Bank (3/4), pakai itu. Jika tidak, Kas (2).
                        $akunKeuanganId = $trx->bank_account_id ? $trx->bank_account_id : $idKasKasir;

                        $debitAccountId  = null; 
                        $creditAccountId = null;
                        $desc = "";

                        if ($trx->type == 'buy') {
                            $debitAccountId  = $idPersediaan;
                            $creditAccountId = $akunKeuanganId;
                            $desc = "Beli Valas";

                        } elseif ($trx->type == 'sell') {
                            $debitAccountId  = $akunKeuanganId;
                            $creditAccountId = $idPendapatan;
                            $desc = "Jual Valas";
                        }

                        if ($debitAccountId && $creditAccountId) {
                            
                            // Header
                            $journal = JournalEntry::create([
                                'date'          => $trx->created_at,
                                'reference_no'  => $uniqueRef, // Pakai Ref Unik
                                'description'   => $desc . ' - ' . ($trx->customer_name ?? 'Umum'),
                                'branch_id'     => $trx->branch_id ?? 1, 
                                'created_by'    => $trx->user_id ?? 1,
                                'created_at'    => $trx->created_at,
                                'updated_at'    => $trx->updated_at,
                            ]);

                            // Debit
                            JournalItem::create([
                                'journal_entry_id' => $journal->id,
                                'account_id'       => $debitAccountId,
                                'debit'            => $trx->total_idr,
                                'credit'           => 0,
                            ]);

                            // Kredit
                            JournalItem::create([
                                'journal_entry_id' => $journal->id,
                                'account_id'       => $creditAccountId,
                                'debit'            => 0,
                                'credit'           => $trx->total_idr,
                            ]);

                            $counter++;
                        }
                    }
                }
            });
            
            DB::commit();
            return "SEMPURNA! Proses Recovery Selesai. $counter Jurnal Baru Berhasil Dibuat.";

        } catch (\Exception $e) {
            DB::rollBack();
            return "GAGAL: " . $e->getMessage();
        }
    }
}