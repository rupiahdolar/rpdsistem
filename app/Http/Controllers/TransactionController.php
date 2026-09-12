<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Imports\TransactionsImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Currency;
use App\Models\Shift;          
use App\Models\DttotList;
use App\Models\Expense;
use App\Models\ChartOfAccount; 
use App\Services\AccountingService;
use App\Services\ComplianceService; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * Menampilkan Halaman Input Transaksi
     */
    public function index()
    {
        date_default_timezone_set('Asia/Makassar');
        // 1. Ambil Mata Uang yang AKTIF
        $currencies = Currency::where('is_active', 1)->orderBy('code', 'asc')->get(); 
        
        // 2. Ambil Daftar Akun Bank (Untuk Dropdown Transfer)
        $bankAccounts = ChartOfAccount::where('type', 'ASSET')
                        ->where(function($q) {
                            $q->where('name', 'LIKE', '%BANK%')
                              ->orWhere('name', 'LIKE', '%BCA%')
                              ->orWhere('name', 'LIKE', '%MANDIRI%')
                              ->orWhere('name', 'LIKE', '%BNI%')
                              ->orWhere('name', 'LIKE', '%BRI%');
                        })
                        ->get();

        // --- LOGIKA SESI SHIFT (UTUH) ---
        $shiftId = session('shift_id');
        
        // Restore sesi jika hilang
        if (!$shiftId) {
            $lastShift = Shift::where('user_id', Auth::id())
                            ->where('status', 'open') 
                            ->latest('start_time')
                            ->first();

            if ($lastShift) {
                session([
                    'shift_id' => $lastShift->id,
                    'branch_id' => $lastShift->branch_id,
                    'branch_name' => $lastShift->branch->name ?? 'CABANG',
                    'selected_branch_id' => $lastShift->branch_id
                ]);
                $shiftId = $lastShift->id;
            } else {
                return redirect()->route('cashier.dashboard');
            }
        }
        // -----------------------------------------------------------------------

        // 3. Ambil Histori Transaksi Hari Ini
        $todayTransactions = Transaction::select(
                'id', 'shift_id', 'branch_id', 'no_nota', 'transaction_code', 
                'customer_name', 'customer_country', 'customer_address', 
                'customer_identity_no', 'customer_id_type', 
                'customer_type', 'representative_name', 
                'type', 'currency', 'amount_foreign', 'rate', 'total_idr', 'created_at',
                'payment_method'
            )
            ->with('branch:id,name,address') 
            ->where('shift_id', $shiftId)
            ->orderBy('id', 'desc') 
            ->get();

        // 4. Data Pelanggan Lama (OPTIMASI MEMORI & TIME OUT)
        // DULU: Memuat semua data (bikin berat & crash).
        // SEKARANG: Dikosongkan di awal load. Pencarian dilakukan via AJAX ke method 'searchCustomers'.
        $existingCustomers = []; 

        $sessionData = session()->all(); 

        return view('cashier.transactions.index', compact('currencies', 'sessionData', 'todayTransactions', 'existingCustomers', 'bankAccounts'));
    }

    /**
     * [BARU] API Pencarian Nasabah (AJAX)
     * Menggantikan beban loading di awal. Dipanggil saat mengetik nama.
     */
    public function searchCustomers(Request $request)
    {
        $keyword = $request->input('q');
        $searchType = $request->input('type', 'name'); // 'name' atau 'phone'

        if (strlen($keyword) < 2) {
            return response()->json([]);
        }

        $query = Transaction::select(
                        'customer_name', 'customer_identity_no', 'customer_id_type', 
                        'customer_phone', 'customer_address', 'customer_job', 'customer_country',
                        'customer_type', 'customer_gender', 'customer_dob',
                        'representative_name', 'representative_id_type', 'representative_id_no',
                        'source_of_funds', 'transaction_purpose'
                    )
                    ->whereNotNull('customer_identity_no');

        // JIKA PENCARIAN DARI KOLOM NO HP
        if ($searchType === 'phone') {
            $query->whereNotNull('customer_phone')
                ->where('customer_phone', '!=', '')
                ->where('customer_phone', 'LIKE', "%{$keyword}%");
        } 
        // JIKA PENCARIAN DARI KOLOM NAMA / NIK
        else {
            $query->where(function($q) use ($keyword) {
                $q->where('customer_name', 'LIKE', "%{$keyword}%")
                ->orWhere('customer_identity_no', 'LIKE', "%{$keyword}%");
            });
        }

        $customers = $query->orderBy('created_at', 'desc')
                        ->limit(30)
                        ->get()
                        ->unique('customer_identity_no')
                        ->values();

        return response()->json($customers);
    }

    /**
     * Proses Simpan Transaksi
     */
    public function store(Request $request, ComplianceService $complianceService)
    {
        // [FIX TIMEZONE] Paksa waktu server jadi WITA
        date_default_timezone_set('Asia/Makassar');

        // Sanitize input amount_foreign jika masih membawa string titik/koma dari JS
        if ($request->has('items') && is_array($request->items)) {
            $items = $request->items;
            foreach ($items as $key => $item) {
                // Bersihkan amount_foreign
                if (isset($item['amount_foreign'])) {
                    $items[$key]['amount_foreign'] = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $item['amount_foreign']));
                }
                // Bersihkan rate (Ubah koma jadi titik desimal)
                if (isset($item['rate'])) {
                    $items[$key]['rate'] = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $item['rate']));
                }
            }
            $request->merge(['items' => $items]);
        }

        // 1. VALIDASI INPUT
        $rules = [
            'items' => 'required|array|min:1',
            'items.*.currency_code' => 'required|exists:currencies,code',
            'items.*.amount_foreign' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:1',
            'items.*.type' => 'required|in:buy,sell',
            
            'customer_name' => 'required|string',
            'customer_identity_no' => 'required|string',
            'customer_id_type' => 'required|string',
            'customer_phone' => 'nullable|string',
            'customer_job' => 'required|string', // <-- PENYESUAIAN: Wajib untuk Deteksi PEP
            
            'customer_type' => 'required|in:INDIVIDUAL,CORPORATE',
            'customer_dob'  => 'nullable|date', 
            
            'source_of_funds' => 'required|string',
            'source_of_funds_custom' => 'required_if:source_of_funds,LAINNYA|nullable|string',
            'transaction_purpose' => 'required|string',
            'transaction_purpose_custom' => 'required_if:transaction_purpose,LAINNYA|nullable|string',
            'payment_method' => 'required|in:CASH,TRANSFER',
            'bank_account_id' => 'required_if:payment_method,TRANSFER',
        ];

        // Validasi Tambahan: Korporasi WAJIB isi Pengurus
        if ($request->customer_type == 'CORPORATE') {
            $rules['representative_name'] = 'required|string';
            $rules['representative_id_no'] = 'required|string';
        } 
        // Validasi Tambahan: Perorangan WAJIB isi Gender
        else {
            $rules['customer_gender'] = 'required|in:L,P';
        }

        $request->validate($rules);

        $finalSourceOfFunds = ($request->source_of_funds === 'LAINNYA' && $request->filled('source_of_funds_custom'))
            ? $request->source_of_funds_custom
            : $request->source_of_funds;

        $finalTransactionPurpose = ($request->transaction_purpose === 'LAINNYA' && $request->filled('transaction_purpose_custom'))
            ? $request->transaction_purpose_custom
            : $request->transaction_purpose;

        // 2. CEK DTTOT (TERORIS)
        $cleanName = strtoupper(trim($request->customer_name));
        $inputDob  = $request->customer_dob;
        $inputNoId = strtoupper(trim($request->customer_identity_no));

        // Cek apakah kasir menekan tombol "Berbeda (Lanjut Transaksi)"
        $isOverridden = $request->has('dttot_override') && $request->dttot_override == '1';

        if (!$isOverridden) {
            try {
                // Panggil Engine Pengecekan Pintar
                $dttotCheck = $this->checkDttotMatch($cleanName, $inputDob, $inputNoId);

                // A. BLOKIR TOTAL (Jika Nama SAMA & Tanggal Lahir / NIK Sama Persis)
                if ($dttotCheck['is_block']) {
                    return back()->with('dttot_block', [
                        'name'   => $cleanName,
                        'match'  => $dttotCheck['block_data']->name,
                        'source' => $dttotCheck['block_data']->source_doc ?? 'Database PPATK / Polri'
                    ])->withInput();
                }

                // B. WARNING POPUP (Jika Kemiripan Nama >= 85% tapi belum pasti)
                if ($dttotCheck['is_warning']) {
                    return back()->with('dttot_warning', [
                        'name'        => $cleanName,
                        'matches'     => $dttotCheck['matches'],
                        'customer_dob'=> $inputDob ?? 'TIDAK DIISI',
                        'customer_id' => $inputNoId
                    ])->withInput();
                }

            } catch (\Exception $e) {
                \Log::error('DTTOT Check Error: ' . $e->getMessage());
            }
        }

        // 3. DETEKSI PEP (POLITICALLY EXPOSED PERSON) UNTUK RECORD DATABASE
        $pepKeywords = ['PNS', 'PEJABAT', 'TNI', 'POLRI', 'DPR', 'DPRD', 'BUMN', 'PEMERINTAH', 'MENTERI', 'BUPATI', 'WALIKOTA', 'GUBERNUR', 'JAKSA', 'HAKIM'];
        $cleanJob = strtoupper(trim($request->customer_job));
        $isPep = 0;
        foreach ($pepKeywords as $keyword) {
            if (strpos($cleanJob, $keyword) !== false) {
                $isPep = 1;
                break;
            }
        }

        // 4. GENERATE NO NOTA
        $noNota = $request->no_nota ? strtoupper($request->no_nota) : 'INV-' . strtoupper(Str::random(6));

        // FIX TANGGAL & JAM
        $customDate = $request->transaction_date 
            ? $request->transaction_date . ' ' . date('H:i:s') 
            : date('Y-m-d H:i:s');

        DB::beginTransaction();
        try {
            // Cari Shift Kasir yang BENAR-BENAR SEDANG OPEN
            $activeShift = Shift::where('user_id', Auth::id())
                            ->where('branch_id', session('branch_id')) 
                            ->where('status', 'open')
                            ->latest('id')
                            ->first();

            if (!$activeShift) {
                return back()->with('error', 'ERROR: Shift Belum Dibuka! Silakan kembali ke Dashboard dan klik "Open Shift".');
            }

            $shiftId = $activeShift->id;
            $branchId = $activeShift->branch_id; 
            $userId = Auth::id();
            $totalIDRAll = 0;

            // LOGIKA CHECK THRESHOLD APU-PPT (USD 10.000 / LTKT)
            $inputCustomerIdentity = strtoupper(trim($request->customer_identity_no));
            
            $currentTransactionTotalIDR = 0;
            foreach ($request->items as $item) {
                $currentTransactionTotalIDR += ($item['amount_foreign'] * $item['rate']);
            }

            // Cek status akumulasi bulanan nasabah via ComplianceService
            $compliance = $complianceService->checkThresholdStatus($inputCustomerIdentity, $currentTransactionTotalIDR);
            $isLtktTransaction = $compliance['is_exceeded'] ? 1 : 0;

            foreach ($request->items as $item) {
                $totalIDR = $item['amount_foreign'] * $item['rate'];
                $totalIDRAll += $totalIDR;

                do { $trxCode = 'TRX-' . strtoupper(Str::random(6)); } 
                while (Transaction::where('transaction_code', $trxCode)->exists());

                // SIMPAN KE DATABASE
                $transactionData = [
                    'transaction_code' => $trxCode,
                    'branch_id' => $branchId, 
                    'user_id' => $userId,
                    'shift_id' => $shiftId,   
                    'type' => $item['type'],
                    'currency' => strtoupper($item['currency_code']), 
                    'amount_foreign' => $item['amount_foreign'],
                    'rate' => $item['rate'],
                    'total_idr' => $totalIDR,
                    'is_ltkt' => $isLtktTransaction,
                    'no_nota' => $noNota,
                    
                    // --- DATA NASABAH ---
                    'customer_type' => $request->customer_type, 
                    'customer_name' => strtoupper($request->customer_name),
                    'customer_identity_no' => strtoupper($request->customer_identity_no),
                    'customer_id_type' => strtoupper($request->customer_id_type),
                    'customer_phone' => $request->customer_phone,
                    
                    'customer_gender' => $request->customer_type == 'INDIVIDUAL' ? $request->customer_gender : null,
                    'customer_dob' => $request->customer_dob,
                    
                    'representative_name' => $request->customer_type == 'CORPORATE' ? strtoupper($request->representative_name) : null,
                    'representative_id_type' => $request->customer_type == 'CORPORATE' ? strtoupper($request->representative_id_type) : null,
                    'representative_id_no' => $request->customer_type == 'CORPORATE' ? strtoupper($request->representative_id_no) : null,

                    'customer_address' => strtoupper($request->customer_address),
                    'customer_job' => $cleanJob,
                    'customer_country' => strtoupper($request->customer_country),
                    'source_of_funds' => strtoupper($finalSourceOfFunds),
                    'transaction_purpose' => strtoupper($finalTransactionPurpose),
                    
                    'payment_method' => $request->payment_method,
                    'bank_account_id' => ($request->payment_method == 'TRANSFER') ? $request->bank_account_id : null,
                ];

                // Jika di tabel transactions ada kolom is_pep, tambahkan nilainya
                if (\Schema::hasColumn('transactions', 'is_pep')) {
                    $transactionData['is_pep'] = $isPep;
                }

                $transaction = Transaction::create($transactionData);

                // UPDATE TANGGAL SECARA PAKSA (HARD UPDATE)
                DB::table('transactions')
                    ->where('id', $transaction->id)
                    ->update(['created_at' => $customDate]);

                $transaction = Transaction::find($transaction->id);

                AccountingService::recordTransaction($transaction);
            }

            DB::commit();
            return redirect()->route('transaction.index')
                             ->with('transaction_success', 'Transaksi Berhasil. Total: Rp ' . number_format($totalIDRAll));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Error: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Hapus Transaksi (Owner)
     */
    public function destroy($id)
    {
        $trx = Transaction::findOrFail($id);
        if (auth()->user()->role !== 'owner') {
             return back()->withErrors(['msg' => 'Akses Ditolak.']);
        }
        AccountingService::deleteTransactionJournal($trx->transaction_code);
        $trx->delete();
        return back()->with('success', 'Data dihapus.');
    }

    /**
     * PROSES TUTUP SHIFT (END SHIFT)
     */
    public function endShift(Request $request)
    {
        $shiftId = session('shift_id'); 
        if ($shiftId) {
            $currentShift = Shift::find($shiftId);
            if ($currentShift && $currentShift->status == 'open') {
                $startCash = $currentShift->start_cash; 
                $transactions = Transaction::where('shift_id', $currentShift->id)->where('payment_method', 'CASH')->get();
                $cashIn  = $transactions->where('type', 'sell')->sum('total_idr');
                $cashOut = $transactions->where('type', 'buy')->sum('total_idr');
                
                $expenses = 0;
                if (class_exists('App\Models\Expense')) {
                     $expenses = \App\Models\Expense::where('branch_id', $currentShift->branch_id)
                                ->whereBetween('created_at', [$currentShift->start_time, now()])
                                ->sum('amount');
                }
                $expectedCash = $startCash + $cashIn - $cashOut - $expenses;
                $currentShift->update([
                    'end_time' => now(),
                    'expected_cash' => $expectedCash, 
                    'actual_cash' => $expectedCash,   
                    'status' => 'closed'              
                ]);
            }
            session()->forget(['shift_id', 'branch_id', 'branch_name', 'selected_branch_id']);
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'Shift ditutup.');
    }

    /**
     * [BARU] Helper Engine Pengecekan DTTOT Pintar
     * Memecah alias, mengecek persentase kemiripan nama, dan cross-check tanggal lahir/ID.
     */
    private function checkDttotMatch(string $inputName, ?string $inputDob = null, ?string $inputNoId = null): array
    {
        $cleanInput = strtoupper(trim($inputName));

        // 1. Kata kurang dari 4 karakter langsung lolos
        if (strlen($cleanInput) < 4) {
            return [
                'is_block' => false,
                'is_warning' => false,
                'matches' => collect([])
            ];
        }

        $firstWord = explode(' ', $cleanInput)[0];
        if (strlen($firstWord) < 3) {
            $firstWord = $cleanInput;
        }

        // Cari kandidat DTTOT dari database
        $candidates = DttotList::where('name', 'LIKE', "%{$firstWord}%")
                                ->orWhere('description', 'LIKE', "%{$cleanInput}%")
                                ->get();

        if ($candidates->isEmpty()) {
            return [
                'is_block' => false,
                'is_warning' => false,
                'matches' => collect([])
            ];
        }

        $warningMatches = collect();
        $exactMatchData = null;
        $isExactMatch = false;

        foreach ($candidates as $dttot) {
            $aliases = explode(' ALIAS ', strtoupper($dttot->name));

            $maxSimilarity = 0;
            foreach ($aliases as $alias) {
                $alias = trim($alias);
                if (empty($alias)) continue;

                similar_text($cleanInput, $alias, $percent);
                if ($percent > $maxSimilarity) {
                    $maxSimilarity = $percent;
                }
            }

            // Jika kemiripan nama di bawah 85%, abaikan
            if ($maxSimilarity < 85) continue;

            $hasDttotDob = !empty($dttot->birth_date);
            $hasDttotId  = !empty($dttot->description);

            $dobMatched = $hasDttotDob && !empty($inputDob) && (strpos($dttot->birth_date, $inputDob) !== false || $dttot->birth_date == $inputDob);
            $idMatched  = $hasDttotId && !empty($inputNoId) && (strpos(strtoupper($dttot->description), strtoupper($inputNoId)) !== false);

            // 1. BLOKIR MERAH: Jika Nama Mirip (>=85%) DAN Data Pendukung (ID/DOB) terbukti KLOP
            if ($maxSimilarity >= 85 && ($dobMatched || $idMatched)) {
                $isExactMatch = true;
                $exactMatchData = $dttot;
                break; 
            }

            // 2. PROVEN FALSE POSITIVE: Jika DTTOT punya tanggal lahir, kasir mengisi tanggal lahir, dan TERBUKTI BEDA mutlak
            if ($hasDttotDob && !empty($inputDob) && !$dobMatched) {
                continue; // Lewati karena terbukti orang yang berbeda
            }

            // 3. WARNING KUNING (PENGAMANAN UTAMA DATA TANPA ID/DOB): 
            // Jika nama mirip (>=85%) TETAPI database DTTOT tidak punya info DOB/ID untuk diverifikasi,
            // sistem tidak akan membiarkannya lolos tanpa suara, melainkan memunculkan Warning Popup agar kasir mengecek fisik KTP.
            $warningMatches->push($dttot);
        }

        return [
            'is_block'   => $isExactMatch,
            'block_data' => $exactMatchData,
            'is_warning' => $warningMatches->count() > 0 && !$isExactMatch,
            'matches'    => $warningMatches
        ];
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls,csv|max:10240', // Maks 10MB
            'branch_id'  => 'required|exists:branches,id'
        ]);

        try {
            $branchId = $request->branch_id;
            $userId   = auth()->id();

            Excel::import(new \App\Imports\TransactionsImport($branchId, $userId), $request->file('file_excel'));

            return back()->with('success', 'Berhasil mengimpor data transaksi & nasabah lama dari Excel!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }
     
    public function checkNoNota(Request $request)
    {
        $noNota = $request->query('no_nota');
        
        if (!$noNota) {
            return response()->json(['exists' => false]);
        }

        $exists = Transaction::where('no_nota', $noNota)->first();

        if ($exists) {
            return response()->json([
                'exists' => true,
                'date'   => Carbon::parse($exists->created_at)->format('d/m/Y H:i')
            ]);
        }

        return response()->json(['exists' => false]);
    }
}