<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // ============================================================
        // 1. ANALISIS TRANSAKSI KUPVA BB (Nominal Rp)
        // ============================================================
        $targetCurrencies = [
            'USD', 'SGD', 'EUR', 'AUD', 'MYR', 'JPY', 
            'CNY', 'HKD', 'THB', 'SAR', 'GBP'
        ];

        $rawCurrencyData = Transaction::select('currency', DB::raw('SUM(total_idr) as total_nominal'))
            ->whereYear('created_at', $year)
            ->groupBy('currency')
            ->pluck('total_nominal', 'currency')
            ->toArray();

        $currencyReport = [];
        $totalCurrencyNominal = 0;

        foreach ($targetCurrencies as $code) {
            $val = $rawCurrencyData[$code] ?? 0;
            $currencyReport[$code] = $val;
            $totalCurrencyNominal += $val;
            unset($rawCurrencyData[$code]);
        }
        
        $othersNominal = array_sum($rawCurrencyData);
        $currencyReport['Lainnya'] = $othersNominal;
        $totalCurrencyNominal += $othersNominal;


        // ============================================================
        // 2. MITRA KERJA SAMA BERDASARKAN NEGARA (Jumlah Orang)
        // ============================================================
        $rawCountryData = Transaction::select('customer_country', DB::raw('COUNT(DISTINCT customer_identity_no) as total_people'))
            ->whereYear('created_at', $year)
            ->whereNotNull('customer_country')
            ->groupBy('customer_country')
            ->get();

        $countryReport = [
            'Domestik' => 0,
            'Asing Australia' => 0,
            'Asing Amerika' => 0,
            'Asing Jepang' => 0,
            'Asing Lainnya' => 0
        ];

        foreach ($rawCountryData as $row) {
            $c = strtoupper(trim($row->customer_country));
            $val = $row->total_people;

            if ($c === 'INDONESIA' || $c === 'IDN') {
                $countryReport['Domestik'] += $val;
            } elseif (str_contains($c, 'AUSTRALIA')) {
                $countryReport['Asing Australia'] += $val;
            } elseif (str_contains($c, 'USA') || str_contains($c, 'AMERICA') || str_contains($c, 'UNITED STATES')) {
                $countryReport['Asing Amerika'] += $val;
            } elseif (str_contains($c, 'JAPAN') || str_contains($c, 'JEPANG')) {
                $countryReport['Asing Jepang'] += $val;
            } else {
                $countryReport['Asing Lainnya'] += $val;
            }
        }
        $totalPeopleCountry = array_sum($countryReport);


        // ============================================================
        // 3. PROFIL PEKERJAAN NASABAH (SESUAI LAMPIRAN GAMBAR)
        // ============================================================
        $rawJobData = Transaction::select('customer_job', DB::raw('COUNT(DISTINCT customer_identity_no) as total_people'))
            ->whereYear('created_at', $year)
            ->where('customer_type', 'INDIVIDUAL') // HANYA INDIVIDU
            ->groupBy('customer_job')
            ->get();

        // Inisialisasi Kategori Lengkap Sesuai Gambar
        $jobReport = [
            'Pejabat Negara' => 0,
            'Wirausaha/Wiraswasta' => 0,
            'Karyawan Swasta' => 0,
            'Pegawai Negeri Sipil (PNS)' => 0,
            'Profesi Keuangan Lainnya' => 0,
            'Profesional' => 0,
            'Pengurus/Pegawai BUMN/BUMD' => 0,
            'Ibu Rumah Tangga' => 0,
            'TNI' => 0,
            'Polri' => 0,
            'Pelajar/Mahasiswa' => 0,
            'Pengurus Yayasan/Lembaga Hukum' => 0,
            'Artis/Content Creator/Kreatif' => 0,
            'Pengajar' => 0,
            'Pengurus Ormas/LSM' => 0,
            'Sopir' => 0,
            'Asisten Rumah Tangga (ART)' => 0,
            'Buruh' => 0,
            'Tenaga Keamanan' => 0,
            'Atlet/Olahragawan' => 0,
            'Tokoh Agama' => 0,
            'Pengurus Partai Politik' => 0,
            'Lainnya' => 0
        ];

        foreach ($rawJobData as $row) {
            $j = strtoupper(trim($row->customer_job));
            $val = $row->total_people;

            // --- LOGIKA DETEKSI KATA KUNCI YANG LEBIH CERDAS ---

            if (str_contains($j, 'PEJABAT') || str_contains($j, 'DPR') || str_contains($j, 'BUPATI') || str_contains($j, 'WALIKOTA') || str_contains($j, 'MENTERI')) {
                $jobReport['Pejabat Negara'] += $val;
            } 
            elseif (str_contains($j, 'WIRA') || str_contains($j, 'DAGANG') || str_contains($j, 'BISNIS') || str_contains($j, 'OWNER') || str_contains($j, 'PEDAGANG')) {
                $jobReport['Wirausaha/Wiraswasta'] += $val;
            } 
            elseif (str_contains($j, 'SWASTA') || str_contains($j, 'KARYAWAN') || str_contains($j, 'STAFF') || str_contains($j, 'MANAGER') || str_contains($j, 'ADMIN')) {
                $jobReport['Karyawan Swasta'] += $val;
            } 
            elseif (str_contains($j, 'PNS') || str_contains($j, 'ASN') || str_contains($j, 'NEGERI') || str_contains($j, 'PEMDA')) {
                $jobReport['Pegawai Negeri Sipil (PNS)'] += $val;
            } 
            elseif (str_contains($j, 'BANK') || str_contains($j, 'AKUNTAN') || str_contains($j, 'FINANCE') || str_contains($j, 'AUDITOR')) {
                $jobReport['Profesi Keuangan Lainnya'] += $val;
            } 
            elseif (str_contains($j, 'DOKTER') || str_contains($j, 'PENGACARA') || str_contains($j, 'NOTARIS') || str_contains($j, 'ARSITEK') || str_contains($j, 'KONSULTAN')) {
                $jobReport['Profesional'] += $val;
            } 
            elseif (str_contains($j, 'BUMN') || str_contains($j, 'BUMD') || str_contains($j, 'PERTAMINA') || str_contains($j, 'PLN')) {
                $jobReport['Pengurus/Pegawai BUMN/BUMD'] += $val;
            } 
            elseif (str_contains($j, 'RUMAH TANGGA') || str_contains($j, 'IRT')) {
                $jobReport['Ibu Rumah Tangga'] += $val;
            } 
            elseif (str_contains($j, 'TNI') || str_contains($j, 'TENTARA') || str_contains($j, 'ABRI')) {
                $jobReport['TNI'] += $val;
            } 
            elseif (str_contains($j, 'POLRI') || str_contains($j, 'POLISI') || str_contains($j, 'BRIMOB')) {
                $jobReport['Polri'] += $val;
            } 
            elseif (str_contains($j, 'PELAJAR') || str_contains($j, 'MAHASISWA') || str_contains($j, 'SISWA')) {
                $jobReport['Pelajar/Mahasiswa'] += $val;
            } 
            elseif (str_contains($j, 'YAYASAN') || str_contains($j, 'LEMBAGA')) {
                $jobReport['Pengurus Yayasan/Lembaga Hukum'] += $val;
            } 
            elseif (str_contains($j, 'ARTIS') || str_contains($j, 'CONTENT') || str_contains($j, 'SENIMAN') || str_contains($j, 'YOUTUBER') || str_contains($j, 'DESAINER')) {
                $jobReport['Artis/Content Creator/Kreatif'] += $val;
            } 
            elseif (str_contains($j, 'GURU') || str_contains($j, 'DOSEN') || str_contains($j, 'PENGAJAR') || str_contains($j, 'INSTRUKTUR')) {
                $jobReport['Pengajar'] += $val;
            } 
            elseif (str_contains($j, 'LSM') || str_contains($j, 'ORMAS') || str_contains($j, 'NGO')) {
                $jobReport['Pengurus Ormas/LSM'] += $val;
            } 
            elseif (str_contains($j, 'SOPIR') || str_contains($j, 'DRIVER') || str_contains($j, 'SUPIR')) {
                $jobReport['Sopir'] += $val;
            } 
            elseif (str_contains($j, 'ART') || str_contains($j, 'PEMBANTU') || str_contains($j, 'ASISTEN RUMAH')) {
                $jobReport['Asisten Rumah Tangga (ART)'] += $val;
            } 
            elseif (str_contains($j, 'BURUH') || str_contains($j, 'TUKANG') || str_contains($j, 'PEKERJA LEPAS')) {
                $jobReport['Buruh'] += $val;
            } 
            elseif (str_contains($j, 'SATPAM') || str_contains($j, 'SECURITY') || str_contains($j, 'KEAMANAN') || str_contains($j, 'HANSIB')) {
                $jobReport['Tenaga Keamanan'] += $val;
            } 
            elseif (str_contains($j, 'ATLET') || str_contains($j, 'OLAHRAGA')) {
                $jobReport['Atlet/Olahragawan'] += $val;
            } 
            elseif (str_contains($j, 'USTAD') || str_contains($j, 'PENDETA') || str_contains($j, 'AGAMA') || str_contains($j, 'KYAI')) {
                $jobReport['Tokoh Agama'] += $val;
            } 
            elseif (str_contains($j, 'PARTAI') || str_contains($j, 'POLITIK') || str_contains($j, 'CALEG')) {
                $jobReport['Pengurus Partai Politik'] += $val;
            } 
            else {
                $jobReport['Lainnya'] += $val;
            }
        }
        $totalPeopleJob = array_sum($jobReport);


        // ============================================================
        // 4. PENGGUNA JASA BADAN USAHA (HANYA KORPORASI)
        // ============================================================
        $rawCorpData = Transaction::select('customer_name', DB::raw('COUNT(DISTINCT customer_identity_no) as total_entity'))
            ->whereYear('created_at', $year)
            ->where('customer_type', 'CORPORATE') // Filter Kunci
            ->groupBy('customer_name')
            ->get();

        $corporateReport = [
            'Perseroan Terbatas (PT/BUMN)' => 0,
            'Persekutuan Komanditer (CV)' => 0,
            'Koperasi' => 0,
            'Yayasan' => 0,
            'Perkumpulan/Lainnya' => 0
        ];

        foreach ($rawCorpData as $row) {
            $name = strtoupper($row->customer_name);
            $val = $row->total_entity;
            
            // LOGIKA DETEKSI BADAN USAHA
            if (str_contains($name, 'PT ') || str_contains($name, 'PT.') || str_contains($name, 'PERSERO') || str_contains($name, 'TBK')) {
                $corporateReport['Perseroan Terbatas (PT/BUMN)'] += $val;
            } elseif (str_contains($name, 'CV ') || str_contains($name, 'CV.') || str_contains($name, 'KOMANDITER')) {
                $corporateReport['Persekutuan Komanditer (CV)'] += $val;
            } elseif (str_contains($name, 'KOPERASI') || str_contains($name, 'KUD ') || str_contains($name, 'KSP ')) {
                $corporateReport['Koperasi'] += $val;
            } elseif (str_contains($name, 'YAYASAN')) {
                $corporateReport['Yayasan'] += $val;
            } else {
                $corporateReport['Perkumpulan/Lainnya'] += $val;
            }
        }
        $totalCorporate = array_sum($corporateReport);

        // RETURN VIEW
        return view('admin.customers.assessment', compact(
            'year',
            'currencyReport', 'totalCurrencyNominal',
            'countryReport', 'totalPeopleCountry',
            'jobReport', 'totalPeopleJob',
            'corporateReport', 'totalCorporate'
        ));
    }
}