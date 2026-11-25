<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\DeletedTransaction;
use App\Models\EditedTransaction; // [TAMBAHAN MODEL]
use App\Models\Branch;
use App\Models\User;
use App\Models\Currency;       
use App\Models\ChartOfAccount; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingService;

class MenuController extends Controller
{
    /**
     * 1. INDEX: MENAMPILKAN DATA NASABAH (OPTIMIZED & SAFE DATE)
     */
    public function nasabah(Request $request)
    {
        // [TAMBAHAN PENTING] Set Timezone agar 'Hari Ini' sesuai WITA
        date_default_timezone_set('Asia/Makassar'); 

        $user = Auth::user();
        $startDate  = $request->input('start_date', date('Y-m-d')); 
        $endDate    = $request->input('end_date', date('Y-m-d'));
        
        $search     = $request->input('search');
        $sort       = $request->input('sort', 'latest'); 
        $branchId   = $request->input('branch_id');
        $userId     = $request->input('user_id');

        // Query Utama (Eager Loading untuk performa)
        $query = Transaction::query()
            ->with(['branch:id,name', 'user:id,name']) 
            ->select(
                'id', 'transaction_code', 'branch_id', 'user_id', 'created_at',
                'no_nota', 'type', 'currency', 'amount_foreign', 'rate', 'total_idr',
                'payment_method', 'bank_account_id',
                'customer_type', 'representative_name', 'representative_id_type', 'representative_id_no',   
                'customer_name', 'customer_gender', 'customer_dob', 
                'customer_identity_no', 'customer_id_type', 
                'customer_address', 'customer_country', 'customer_job',
                'source_of_funds', 'transaction_purpose'
            );

        // --- 2. FILTER WAJIB (TANGGAL) ---
        // Kita HAPUS logika "if has filter_submit". 
        // Sekarang query SELALU dibatasi tanggal agar memori aman.
        $query->whereDate('created_at', '>=', $startDate)
              ->whereDate('created_at', '<=', $endDate);

        // --- 3. FILTER ROLE ---
        if ($user->role === 'owner') {
            $query->when($branchId, fn($q) => $q->where('branch_id', $branchId));
            $query->when($userId, fn($q) => $q->where('user_id', $userId));
        } elseif ($user->role === 'admin') {
             $query->when($branchId, fn($q) => $q->where('branch_id', $branchId));
        } else {
            $myBranchIds = $user->branches->pluck('id')->toArray();
            $activeBranchId = session('branch_id');

            if ($activeBranchId && in_array($activeBranchId, $myBranchIds)) {
                $query->where('branch_id', $activeBranchId);
            } else {
                $query->whereIn('branch_id', $myBranchIds);
            }
        }

        // --- 4. FILTER PENCARIAN ---
        $query->when($search, function($q) use ($search) {
            $q->where(function($sub) use ($search) {
                $sub->where('customer_name', 'LIKE', "%{$search}%")
                    ->orWhere('customer_identity_no', 'LIKE', "%{$search}%")
                    ->orWhere('no_nota', 'LIKE', "%{$search}%")
                    ->orWhere('representative_name', 'LIKE', "%{$search}%");
            });
        });

        // --- 5. SORTIR ---
        if ($sort == 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // --- 6. EXPORT (JIKA DIPINTA) ---
        if ($request->has('export')) {
            // Kita kirim query yang SUDAH DIFILTER TANGGAL ke fungsi export
            return $this->processExport($query, $request->export);
        }

        // --- 7. PAGINASI (LIMIT DATA TAMPIL) ---
        // paginate(20) memastikan hanya 20 baris yang diambil ke RAM untuk ditampilkan
        $transactions = $query->paginate(20)->withQueryString();
        
        $branches = Branch::select('id', 'name')->get();
        $activeCashiers = [];
        if ($user->role === 'owner' || $user->role === 'admin') {
            $activeCashiers = User::where('role', '!=', 'owner')->select('id', 'name')->get();
        }

        // --- AMBIL DATA RIWAYAT HAPUS ---
        $deletedTransactions = DeletedTransaction::orderBy('created_at', 'desc')
                                ->paginate(10, ['*'], 'deleted_page');

        // --- [TAMBAHAN] AMBIL DATA RIWAYAT EDIT ---
        $editedTransactions = EditedTransaction::orderBy('created_at', 'desc')
                                ->paginate(10, ['*'], 'edited_page');

        return view('admin.customers.index', compact(
            'transactions', 'branches', 'activeCashiers',
            'startDate', 'endDate', 'search', 'sort', 'branchId', 'userId',
            'deletedTransactions', 'editedTransactions'
        ));
    }

    /**
     * 2. HALAMAN EDIT (REVISI: LOAD PER NOTA)
     */
    public function edit($id)
    {
        if (!in_array(auth()->user()->role, ['owner', 'admin', 'cashier'])) {
            return back()->with('error', 'Akses Ditolak.');
        }

        // 1. Ambil transaksi pemicu (yang diklik)
        $triggerTrx = Transaction::findOrFail($id);

        // 2. Ambil SEMUA transaksi dalam satu Nota yang sama
        $transactions = Transaction::where('no_nota', $triggerTrx->no_nota)->get();
        
        // 3. Ambil data pertama sebagai perwakilan Header (Nasabah)
        $transaction = $transactions->first();

        $currencies = Currency::where('is_active', 1)->get();
        $bankAccounts = ChartOfAccount::where('type', 'ASSET')->where('name', 'LIKE', '%BANK%')->get();

        // Kirim $transactions (jamak) ke view untuk di-looping
        return view('admin.customers.edit', compact('transaction', 'transactions', 'currencies', 'bankAccounts'));
    }

    /**
     * 3. UPDATE TRANSAKSI (REVISI: UPDATE BATCH / PER NOTA)
     */
    public function update(Request $request, $id)
    {
        // Mengizinkan Owner, Admin, dan Kasir untuk Update
        if (!in_array(auth()->user()->role, ['owner', 'admin', 'cashier'])) {
            return back()->with('error', 'Akses Ditolak.');
        }

        // --- VALIDASI ---
        $rules = [
            // Validasi Header (Nasabah)
            'created_at'    => 'required|date',
            'no_nota'       => 'required|string', 
            'customer_name' => 'required|string',
            'customer_type' => 'required|in:INDIVIDUAL,CORPORATE',
            'customer_dob'  => 'nullable|date',
            
            // Validasi Items (Valas) - Karena dikirim dalam bentuk Array
            'items'                 => 'required|array',
            'items.*.id'            => 'required|exists:transactions,id',
            'items.*.currency'      => 'required|exists:currencies,code',
            'items.*.amount_foreign'=> 'required|numeric|min:0',
            'items.*.rate'          => 'required|numeric|min:1',
        ];

        if ($request->customer_type == 'CORPORATE') {
            $rules['representative_name'] = 'required|string';
            $rules['representative_id_no'] = 'required|string';
        }

        $request->validate($rules);

        $currentUser = auth()->user();

        DB::beginTransaction();
        try {
            // Ambil referensi dari ID URL untuk mengetahui No Nota Asli sebelum diedit
            $refTrx = Transaction::findOrFail($id);
            $oldNota = $refTrx->no_nota;

            // Ambil semua transaksi yang terkait dengan nota lama tersebut
            $existingTransactions = Transaction::where('no_nota', $oldNota)->get();

            // Variabel untuk mencatat statistik perubahan sebelum/sesudah
            $oldTotalSum = $existingTransactions->sum('total_idr');
            $newTotalSum = 0;
            $changesArr = [];

            // Looping update setiap baris transaksi
            foreach ($existingTransactions as $trx) {
                
                // Cari data item yang sesuai di input form berdasarkan ID
                // Input form 'items' berbentuk array: items[0][id], items[0][amount], dll.
                $inputItem = collect($request->items)->firstWhere('id', $trx->id);

                if ($inputItem) {
                    // Hitung total baru
                    $newTotalIDR = $inputItem['amount_foreign'] * $inputItem['rate'];
                    $newTotalSum += $newTotalIDR;

                    // Cek jika ada perubahan nominal valas atau rate
                    if ($trx->amount_foreign != $inputItem['amount_foreign'] || $trx->rate != $inputItem['rate']) {
                        $changesArr[] = "{$trx->currency}: Valas ({$trx->amount_foreign} -> {$inputItem['amount_foreign']}), Rate ({$trx->rate} -> {$inputItem['rate']})";
                    }

                    // 1. Update Data Header (Disamakan semua baris)
                    $trx->fill([
                        'created_at'          => $request->created_at,
                        'no_nota'             => strtoupper($request->no_nota),
                        'customer_type'       => $request->customer_type, 
                        'customer_name'       => strtoupper($request->customer_name),
                        'customer_identity_no'=> strtoupper($request->customer_identity_no),
                        'customer_id_type'    => strtoupper($request->customer_id_type),
                        'customer_gender'     => $request->customer_type == 'INDIVIDUAL' ? $request->customer_gender : null,
                        'customer_dob'        => $request->customer_dob,
                        'representative_name'    => $request->customer_type == 'CORPORATE' ? strtoupper($request->representative_name) : null,
                        'representative_id_type' => $request->customer_type == 'CORPORATE' ? strtoupper($request->representative_id_type) : null,
                        'representative_id_no'   => $request->customer_type == 'CORPORATE' ? strtoupper($request->representative_id_no) : null,
                        'customer_address'    => strtoupper($request->customer_address),
                        'customer_country'    => strtoupper($request->customer_country),
                        'customer_job'        => strtoupper($request->customer_job),
                        'source_of_funds'     => strtoupper($request->source_of_funds),
                        'transaction_purpose' => strtoupper($request->transaction_purpose),
                        'payment_method'      => $request->payment_method,
                        'bank_account_id'     => ($request->payment_method == 'TRANSFER') ? $request->bank_account_id : null,
                        'type'                => $request->type, // Asumsi 1 Nota tipe sama (Jual semua / Beli semua)
                        
                        // 2. Update Data Item (Spesifik per baris)
                        'currency'            => $inputItem['currency'],
                        'amount_foreign'      => $inputItem['amount_foreign'],
                        'rate'                => $inputItem['rate'],
                        'total_idr'           => $newTotalIDR
                    ]);

                    $trx->save();

                    // 3. Reset & Catat Ulang Jurnal
                    AccountingService::deleteTransactionJournal($trx->transaction_code);
                    AccountingService::recordTransaction($trx);
                }
            }

            // --- [TAMBAHAN LOG] CATAT RIWAYAT EDIT HANYA JIKA BUKAN OWNER ---
            if ($currentUser->role !== 'owner') {
                $summaryText = count($changesArr) > 0 
                    ? implode(' | ', $changesArr) 
                    : 'Pembaruan data identitas/header nasabah';

                EditedTransaction::create([
                    'no_nota'              => strtoupper($request->no_nota),
                    'transaction_code'     => $refTrx->transaction_code,
                    'customer_name'        => strtoupper($request->customer_name),
                    'customer_identity_no' => strtoupper($request->customer_identity_no),
                    'changes_summary'      => $summaryText,
                    'old_total_idr'        => $oldTotalSum,
                    'new_total_idr'        => $newTotalSum,
                    'edited_by_name'       => $currentUser->name,
                    'edited_by_role'       => $currentUser->role,
                    'transaction_date'     => $request->created_at,
                ]);
            }

            DB::commit();
            return redirect()->route('nasabah.index')->with('success', 'Seluruh data dalam Nota berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    /**
     * 4. HAPUS DATA
     */
    public function destroy($id)
    {
        $trx = Transaction::findOrFail($id);
        $user = auth()->user();

        // 1. Jika Role Admin atau Kasir -> Catat ke Riwayat Penghapusan
        if ($user->role !== 'owner') {
            DeletedTransaction::create([
                'no_nota'              => $trx->no_nota,
                'transaction_code'     => $trx->transaction_code,
                'customer_name'        => $trx->customer_name,
                'customer_identity_no' => $trx->customer_identity_no,
                'currency'             => $trx->currency,
                'amount_foreign'       => $trx->amount_foreign,
                'rate'                 => $trx->rate,
                'total_idr'            => $trx->total_idr,
                'type'                 => $trx->type,
                'deleted_by_name'      => $user->name,
                'deleted_by_role'      => $user->role,
                'deletion_type'        => 'ITEM',
                'transaction_date'     => $trx->created_at,
            ]);
        }

        // 2. Hapus Jurnal & Data Transaksi Asli
        AccountingService::deleteTransactionJournal($trx->transaction_code);
        $trx->delete();

        return back()->with('success', 'Data item transaksi berhasil dihapus.');
    }

    /**
     * Hapus Seluruh Item dalam Satu Nota
     */
    public function destroyNota($id)
    {
        $trxTarget = Transaction::findOrFail($id);
        $allTrxInNota = Transaction::where('no_nota', $trxTarget->no_nota)->get();
        $user = auth()->user();

        DB::beginTransaction();
        try {
            foreach ($allTrxInNota as $trx) {
                // 1. Jika Role Admin atau Kasir -> Catat ke Riwayat Penghapusan
                if ($user->role !== 'owner') {
                    DeletedTransaction::create([
                        'no_nota'              => $trx->no_nota,
                        'transaction_code'     => $trx->transaction_code,
                        'customer_name'        => $trx->customer_name,
                        'customer_identity_no' => $trx->customer_identity_no,
                        'currency'             => $trx->currency,
                        'amount_foreign'       => $trx->amount_foreign,
                        'rate'                 => $trx->rate,
                        'total_idr'            => $trx->total_idr,
                        'type'                 => $trx->type,
                        'deleted_by_name'      => $user->name,
                        'deleted_by_role'      => $user->role,
                        'deletion_type'        => 'FULL_NOTA',
                        'transaction_date'     => $trx->created_at,
                    ]);
                }

                // 2. Hapus Jurnal & Transaksi
                AccountingService::deleteTransactionJournal($trx->transaction_code);
                $trx->delete();
            }

            DB::commit();
            return back()->with('success', 'Seluruh transaksi pada Nota ' . $trxTarget->no_nota . ' berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus nota: ' . $e->getMessage());
        }
    }

    /**
     * [TAMBAHAN] HAPUS PERMANEN BARIS RIWAYAT HAPUS (KHUSUS OWNER)
     */
    public function destroyPermanentDeleted($id)
    {
        if (auth()->user()->role !== 'owner') {
            return back()->with('error', 'Akses Ditolak. Hanya Owner yang dapat menghapus riwayat.');
        }

        $log = DeletedTransaction::findOrFail($id);
        $log->delete();

        return back()->with('success', 'Baris riwayat hapus berhasil dihapus permanen.');
    }

    /**
     * [TAMBAHAN] HAPUS PERMANEN BARIS RIWAYAT EDIT (KHUSUS OWNER)
     */
    public function destroyPermanentEdited($id)
    {
        if (auth()->user()->role !== 'owner') {
            return back()->with('error', 'Akses Ditolak. Hanya Owner yang dapat menghapus riwayat.');
        }

        $log = EditedTransaction::findOrFail($id);
        $log->delete();

        return back()->with('success', 'Baris riwayat edit berhasil dihapus permanen.');
    }

    /**
     * 5. EXPORT (CSV LENGKAP) - [MEMORY SAFE MODE]
     * Menggunakan cursor() agar bisa export jutaan data tanpa RAM meledak.
     */
    private function processExport($query, $type)
    {
        // PDF tetap dibatasi
        if ($type == 'pdf') {
            $data = $query->orderBy('created_at', 'desc')->limit(500)->get(); 
            return view('admin.customers.print_nasabah', compact('data'));
        }

        if ($type == 'excel') {
            $fileName = 'Data_Nasabah_' . date('d-m-Y_His') . '.csv';
            $headers = [
                "Content-type"        => "text/csv; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=$fileName",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            // --- HEADER DISAMAKAN 100% DENGAN ATURAN IMPORT ---
            $columns = [
                'no_nota',
                'tanggal',
                'tipe_transaksi',
                'mata_uang',
                'jumlah_valas',
                'rate',
                'nama_nasabah',
                'tipe_nasabah',
                'tipe_id',
                'no_id',
                'jenis_kelamin',
                'tgl_lahir',
                'telepon',
                'alamat',
                'pekerjaan',
                'negara',
                'sumber_dana',
                'tujuan_transaksi'
            ];

            // Streaming Data (Cursor)
            $callback = function() use($query, $columns) {
                $file = fopen('php://output', 'w');
                
                // Tambahkan BOM agar karakter terbaca rapi di Excel
                fputs($file, "\xEF\xBB\xBF");
                fputcsv($file, $columns);

                foreach ($query->orderBy('created_at', 'desc')->cursor() as $row) {
                    // Conversi Nilai Tipe Transaksi
                    $tipeTransaksi = in_array(strtoupper($row->type), ['BUY', 'BELI']) ? 'BELI' : 'JUAL';

                    // Conversi Nilai Tipe Nasabah
                    $tipeNasabah = strtoupper($row->customer_type ?? 'INDIVIDUAL');
                    if ($tipeNasabah === 'PERORANGAN') {
                        $tipeNasabah = 'INDIVIDUAL';
                    } elseif ($tipeNasabah === 'KORPORASI') {
                        $tipeNasabah = 'CORPORATE';
                    }

                    fputcsv($file, [
                        $row->no_nota,
                        $row->created_at ? $row->created_at->format('Y-m-d') : '',
                        $tipeTransaksi,
                        strtoupper($row->currency),
                        $row->amount_foreign,
                        $row->rate,
                        strtoupper($row->customer_name),
                        $tipeNasabah,
                        strtoupper($row->customer_id_type ?? 'KTP'),
                        $row->customer_identity_no,
                        $row->customer_gender,
                        $row->customer_dob,
                        $row->customer_phone ?? null,
                        $row->customer_address,
                        $row->customer_job,
                        strtoupper($row->customer_country ?? 'INDONESIA'),
                        $row->source_of_funds,
                        $row->transaction_purpose
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }
    }

    /**
     * 6. KYC
     */
    public function kyc(Request $request) 
    {
        date_default_timezone_set('Asia/Makassar');
        $user = Auth::user();

        // 1. Ambil Input Rentang Tanggal (Default Hari Ini, tetap support parameter 'date' lama)
        $defaultDate = $request->input('date', date('Y-m-d'));
        $startDate   = $request->input('start_date', $defaultDate);
        $endDate     = $request->input('end_date', $defaultDate);
        $search      = $request->input('search');
        $riskFilter  = $request->input('risk'); // Opsional filter HIGH / MEDIUM / LOW
        $branchId    = $request->input('branch_id');

        // Pastikan startDate <= endDate
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // 2. Siapkan Data Cabang untuk Dropdown
        $branches = [];
        if ($user->role === 'owner' || $user->role === 'admin') {
            $branches = Branch::select('id', 'name')->get();
        }

        // 3. Query Aggregat Index-Friendly (Group by No Identitas agar tidak pecah baris)
        $query = Transaction::select(
                        'customer_identity_no',
                        DB::raw('MAX(customer_name) as customer_name'),
                        DB::raw('MAX(customer_country) as customer_country'),
                        DB::raw('MAX(customer_address) as customer_address'),
                        DB::raw('MAX(customer_job) as customer_job'),
                        DB::raw('COUNT(*) as freq'),
                        DB::raw('SUM(total_idr) as total_volume')
                    )
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->whereNotNull('customer_name')
                    ->where('customer_identity_no', '!=', '');

        // 4. Filter Cabang (Sesuai Role)
        if ($user->role === 'owner' || $user->role === 'admin') {
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        } else {
            $myBranchIds = $user->branches->pluck('id')->toArray();
            $query->whereIn('branch_id', $myBranchIds);
        }

        // 5. Filter Pencarian Nama / No ID (jika diisi)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_identity_no', 'LIKE', "%{$search}%");
            });
        }

        // 6. Eksekusi Query Aggregat
        $allKycData = $query->groupBy('customer_identity_no')
                            ->orderByDesc('total_volume')
                            ->get();

        // 7. Analisis Risiko & Hitung Statistik Summary Sekaligus (1x Loop)
        $usdRate         = self::getThresholdRate();
        $dynamicLimitIDR = 10000 * $usdRate;
        $ltktLimitIDR    = 500000000;

        $summary = ['LOW' => 0, 'MEDIUM' => 0, 'HIGH' => 0];

        $allKycData->each(function ($row) use ($dynamicLimitIDR, $ltktLimitIDR, &$summary) {
            if ($row->total_volume >= $ltktLimitIDR) {
                $row->risk_level = 'HIGH';
                $row->risk_color = 'red';
                $row->action     = 'Wajib Lapor LTKT (≥ Rp 500Jt)';
            } elseif ($row->freq >= 5) {
                $row->risk_level = 'HIGH';
                $row->risk_color = 'red';
                $row->action     = 'Indikasi Structuring (Analisis TKM)';
            } elseif ($row->total_volume >= $dynamicLimitIDR || $row->freq >= 3) {
                $row->risk_level = 'MEDIUM';
                $row->risk_color = 'yellow';
                $row->action     = 'Pantau Dokumen & Profil (EDD)';
            } else {
                $row->risk_level = 'LOW';
                $row->risk_color = 'green';
                $row->action     = 'Wajar';
            }

            $summary[$row->risk_level]++;
        });

        // Filter berdasarkan kartu risiko jika diklik
        $filteredKyc = $riskFilter 
            ? $allKycData->where('risk_level', $riskFilter)->values() 
            : $allKycData;

        // 8. Paginasi Manual di Collection (Max 50 baris per halaman agar DOM ringan)
        $perPage     = 50;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $kycData     = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredKyc->forPage($currentPage, $perPage)->values(),
            $filteredKyc->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.customers.kyc', compact(
            'startDate', 'endDate', 'search', 'riskFilter',
            'branchId', 'branches', 'kycData', 'summary',
            'usdRate', 'dynamicLimitIDR'
        ));
    }

    /**
     * 7. PRINT STRUK ULANG (THERMAL)
     */
    public function printStruk($id)
    {
        if (!in_array(auth()->user()->role, ['owner', 'admin', 'cashier'])) {
            abort(403);
        }
        
        $trxTrigger = Transaction::findOrFail($id);
        $transactions = Transaction::with(['branch', 'user'])
                        ->where('no_nota', $trxTrigger->no_nota)
                        ->orderBy('id', 'desc')
                        ->get();

        $transaction = $transactions->first();

        return view('admin.customers.print_struk', compact('transaction', 'transactions'));
    }
    
    public static function getThresholdRate()
    {
        $setting = DB::table('settings')->where('key', 'threshold_usd_rate')->first();
        return $setting ? (float)$setting->value : 15000;
    }

    public function updateThresholdRate(Request $request)
    {
        if (!in_array(Auth::user()->role, ['admin', 'owner'])) {
            return back()->with('error', 'Akses Ditolak.');
        }

        $request->validate([
            'threshold_usd_rate' => 'required|numeric|min:1000'
        ]);

        DB::table('settings')->updateOrInsert(
            ['key' => 'threshold_usd_rate'],
            ['value' => $request->threshold_usd_rate, 'updated_at' => now()]
        );

        return back()->with('success', 'Kurs acuan threshold APU-PPT berhasil diperbarui.');
    }
}