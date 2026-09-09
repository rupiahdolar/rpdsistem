<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\DeletedTransaction;
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

        // --- SISIPKAN BARIS INI UNTUK MENGAMBIL DATA RIWAYAT HAPUS ---
        $deletedTransactions = DeletedTransaction::orderBy('created_at', 'desc')
                                ->paginate(10, ['*'], 'deleted_page');

        return view('admin.customers.index', compact(
            'transactions', 'branches', 'activeCashiers',
            'startDate', 'endDate', 'search', 'sort', 'branchId', 'userId',
            'deletedTransactions'
        ));
    }

    /**
     * 2. HALAMAN EDIT (REVISI: LOAD PER NOTA)
     */
    public function edit($id)
    {
        if (auth()->user()->role !== 'owner') {
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
        if (auth()->user()->role !== 'owner') {
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

        DB::beginTransaction();
        try {
            // Ambil referensi dari ID URL untuk mengetahui No Nota Asli sebelum diedit
            $refTrx = Transaction::findOrFail($id);
            $oldNota = $refTrx->no_nota;

            // Ambil semua transaksi yang terkait dengan nota lama tersebut
            $existingTransactions = Transaction::where('no_nota', $oldNota)->get();

            // Looping update setiap baris transaksi
            foreach ($existingTransactions as $trx) {
                
                // Cari data item yang sesuai di input form berdasarkan ID
                // Input form 'items' berbentuk array: items[0][id], items[0][amount], dll.
                $inputItem = collect($request->items)->firstWhere('id', $trx->id);

                if ($inputItem) {
                    // Hitung total baru
                    $newTotalIDR = $inputItem['amount_foreign'] * $inputItem['rate'];

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
        $user = Auth::user();

        // 1. Ambil Input Tanggal (Default Hari Ini) & Cabang
        $date = $request->input('date', date('Y-m-d'));
        $branchId = $request->input('branch_id');

        // 2. Siapkan Data Cabang untuk Dropdown
        $branches = [];
        if ($user->role === 'owner' || $user->role === 'admin') {
            $branches = Branch::all();
        }

        // 3. Query Aggregat (Kelompokkan by Nasabah berdasarkan NIK/Identitas agar akurat)
        $query = Transaction::select(
                        'customer_name', 'customer_identity_no', 'customer_country', 
                        'customer_address', 'customer_job',
                        DB::raw('COUNT(*) as freq'), 
                        DB::raw('SUM(total_idr) as total_volume')
                    )
                    ->whereDate('created_at', $date) // Filter Harian
                    ->whereNotNull('customer_name');

        // 4. Filter Cabang (Sesuai Role)
        if ($user->role === 'owner' || $user->role === 'admin') {
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        } else {
            $myBranchIds = $user->branches->pluck('id')->toArray();
            $query->whereIn('branch_id', $myBranchIds);
        }

        // 5. Eksekusi Query
        $kycData = $query->groupBy('customer_identity_no', 'customer_name', 'customer_country', 'customer_address', 'customer_job')
                    ->orderByDesc('total_volume') 
                    ->get();

        // 6. Analisis Risiko (Kombinasi Threshold USD 10.000 & Regulasi LTKT Rp 500 Juta)
        $usdRate = self::getThresholdRate(); // Tetap mengambil rate acuan yang kamu simpan
        $dynamicLimitIDR = 10000 * $usdRate;  // Limit Rupiah untuk USD 10.000 (Medium Risk)
        $ltktLimitIDR    = 500000000;         // Limit Resmi PPATK Rp 500 Juta (High Risk - LTKT)

        $kycData->map(function ($row) use ($dynamicLimitIDR, $ltktLimitIDR) {
            $riskLevel = 'LOW'; 
            $riskColor = 'green'; 
            $action    = 'Wajar';
            
            // Kategori 1: HIGH RISK
            if ($row->total_volume >= $ltktLimitIDR) {
                $riskLevel = 'HIGH'; 
                $riskColor = 'red'; 
                $action    = 'Wajib Lapor LTKT (≥ Rp 500Jt)';
            } elseif ($row->freq >= 5) {
                $riskLevel = 'HIGH'; 
                $riskColor = 'red'; 
                $action    = 'Indikasi Structuring (Analisis TKM)';
            } 
            // Kategori 2: MEDIUM RISK (Pemicu dari Pengaturan Kurs USD 10.000 atau Frekuensi >= 3x)
            elseif ($row->total_volume >= $dynamicLimitIDR || $row->freq >= 3) {
                $riskLevel = 'MEDIUM'; 
                $riskColor = 'yellow'; 
                $action    = 'Pantau Dokumen & Profil (EDD)';
            }
            
            $row->risk_level = $riskLevel; 
            $row->risk_color = $riskColor; 
            $row->action     = $action;
            return $row;
        });

        return view('admin.customers.kyc', compact('date', 'branchId', 'branches', 'kycData', 'usdRate', 'dynamicLimitIDR'));
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