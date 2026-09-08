<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\DttotList;
use App\Models\SuspiciousReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Smalot\PdfParser\Parser;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ComplianceController extends Controller
{
    // =========================================================================
    // 1. DTTOT (DAFTAR TERDUGA TERORIS & ORGANISASI TERORIS)
    // =========================================================================
    
    public function dttotIndex(Request $request)
    {
        $query = DttotList::query();

        // Fitur Pencarian Cepat
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('description', 'LIKE', '%' . $search . '%')
                  ->orWhere('birth_info', 'LIKE', '%' . $search . '%');
            });
        }

        // Pagination 50 data per halaman agar ringan
        $lists = $query->orderBy('created_at', 'desc')->paginate(50);
        $lists->appends(['search' => $request->search]);

        return view('admin.compliance.dttot', compact('lists'));
    }

    public function dttotStore(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv|max:20480',
        ]);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            // Ambil header baris ke-1 untuk pencocokan kolom
            $header = array_shift($rows); 

            $insertData = [];
            $now = now();

            foreach ($rows as $row) {
                $name = trim($row['A'] ?? ''); // Kolom Nama
                if (empty($name) || $name === 'Nama') continue;

                // Format tanggal lahir jika terbaca sebagai object/datetime
                $rawDob = $row['F'] ?? null;
                if ($rawDob instanceof \DateTime) {
                    $rawDob = $rawDob->format('Y-m-d');
                }

                $insertData[] = [
                    'name'         => strtoupper($name),
                    'description'  => $row['B'] ?? null,
                    'entity_type'  => $row['C'] ?? 'Orang',
                    'densus_code'  => $row['D'] ?? null,
                    'birth_place'  => $row['E'] ?? null,
                    'birth_date'   => $rawDob,
                    'nationality'  => $row['G'] ?? null,
                    'address'      => $row['H'] ?? null,
                    'source_doc'   => $file->getClientOriginalName(),
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }

            // Bulk insert batch 100 data agar eksekusi SQL lebih cepat
            foreach (array_chunk($insertData, 100) as $chunk) {
                DttotList::insert($chunk);
            }

            $count = count($insertData);
            return back()->with('success', "BERHASIL! {$count} Data DTTOT dari Excel berhasil dimasukkan ke database.");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal Memproses Excel: ' . $e->getMessage());
        }
    }

    public function dttotTruncate()
    {
        try {
            DttotList::truncate();
            return back()->with('success', 'DATABASE DTTOT BERHASIL DIKOSONGKAN (RESET).');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal reset database: ' . $e->getMessage());
        }
    }

    public function dttotDestroy($id)
    {
        DttotList::findOrFail($id)->delete();
        return back()->with('success', 'Data DTTOT berhasil dihapus.');
    }

    // =========================================================================
    // 2. LTKT (LAPORAN TRANSAKSI KEUANGAN TUNAI) - DIATAS 500 JUTA
    // =========================================================================
    
    public function ltktIndex(Request $request)
    {
        // Default: Bulan Ini
        $month = $request->input('month', date('m'));
        $year  = $request->input('year', date('Y'));
        
        // Ambang Batas Laporan (Rp 500.000.000)
        $threshold = 500000000;

        // A. Single Transaction > 500 Juta
        $singleTrx = Transaction::with('branch')
                        ->whereMonth('created_at', $month)
                        ->whereYear('created_at', $year)
                        ->where('total_idr', '>=', $threshold) 
                        ->orderBy('total_idr', 'desc')
                        ->get();

        // B. Akumulasi Harian > 500 Juta (Structuring)
        $dailyAccumulation = Transaction::select(
                                'customer_name', 
                                'customer_identity_no', 
                                DB::raw('DATE(created_at) as trx_date'), 
                                DB::raw('SUM(total_idr) as total_daily'),
                                DB::raw('COUNT(*) as freq'),
                                DB::raw('MAX(branch_id) as branch_id')
                            )
                            ->whereMonth('created_at', $month)
                            ->whereYear('created_at', $year)
                            ->groupBy('customer_identity_no', 'customer_name', DB::raw('DATE(created_at)'))
                            ->having('total_daily', '>=', $threshold)
                            ->having('freq', '>', 1)
                            ->get();

        // C. Profil Risiko Tinggi (Top 10 Volume Transaksi Bulan Ini)
        $highRiskProfiles = Transaction::select(
                                'customer_name', 
                                'customer_identity_no',
                                DB::raw('SUM(total_idr) as total_volume'),
                                DB::raw('COUNT(*) as total_freq'),
                                DB::raw('MAX(created_at) as last_trx')
                            )
                            ->whereMonth('created_at', $month)
                            ->whereYear('created_at', $year)
                            ->groupBy('customer_identity_no', 'customer_name')
                            ->orderBy('total_volume', 'desc')
                            ->limit(10)
                            ->get();

        return view('admin.compliance.ltkt', compact(
            'singleTrx', 'dailyAccumulation', 'highRiskProfiles', 
            'month', 'year'
        ));
    }

    // =========================================================================
    // 3. LTKM (LAPORAN TRANSAKSI KEUANGAN MENCURIGAKAN)
    // =========================================================================
    
    public function ltkmIndex()
    {
        $reports = SuspiciousReport::with('user')->orderBy('created_at', 'desc')->get();
        return view('admin.compliance.ltkm', compact('reports'));
    }

    public function ltkmStore(Request $request)
    {
        $request->validate([
            'customer_name' => 'required',
            'suspicious_reason' => 'required',
        ]);

        SuspiciousReport::create([
            'customer_name' => strtoupper($request->customer_name),
            'identity_no' => $request->identity_no ?? '-',
            'suspicious_reason' => $request->suspicious_reason,
            'status' => 'PENDING',
            'reported_by' => Auth::id()
        ]);

        return back()->with('success', 'Laporan Transaksi Mencurigakan (LTKM) Berhasil Dibuat. Segera proses ke PPATK.');
    }
    
    public function ltkmDestroy($id)
    {
        try {
            $report = SuspiciousReport::findOrFail($id);
            $report->delete();

            return back()->with('success', 'Laporan LTKM berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus laporan: ' . $e->getMessage());
        }
    }
}