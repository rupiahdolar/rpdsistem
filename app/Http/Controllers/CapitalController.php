<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InitialCapital;
use App\Models\Branch;
use App\Models\Currency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // [PENTING] Tambahan baru buat transaksi aman
use App\Models\JournalEntry;
use App\Models\JournalItem;

class CapitalController extends Controller
{
    /**
     * HALAMAN UTAMA (Tidak ada perubahan logika dari yang lama)
     */
    public function index(Request $request)
    {
        $branches = Branch::all();
        
        // Urutan Mata Uang Custom
        $customOrder = ['USD', 'AUD', 'EUR', 'GBP', 'CHF', 'JPY', 'SGD', 'CAD', 'MYR', 'NZD', 'HKD', 'CNY', 'BND', 'SAR', 'AED', 'THB', 'PHP', 'SEK', 'NOK', 'DKK', 'KRW', 'TWD'];
        $allCurrencies = Currency::where('is_active', 1)->get();
        $currencies = $allCurrencies->sortBy(function($model) use ($customOrder) {
            $pos = array_search($model->code, $customOrder);
            return $pos === false ? 999 : $pos;
        });

        $selectedDate = $request->input('date', date('Y-m-d'));
        $selectedBranch = $request->input('branch_id');

        $existingCapital = null;
        $existingStocks = collect([]); 

        if ($selectedBranch) {
            $existingCapital = InitialCapital::where('date', $selectedDate)
                                ->where('branch_id', $selectedBranch)
                                ->first();
            
            if ($existingCapital && $existingCapital->forex_stocks) {
                $stocksData = is_string($existingCapital->forex_stocks) 
                    ? json_decode($existingCapital->forex_stocks, true) 
                    : $existingCapital->forex_stocks;

                $existingStocks = collect($stocksData)->map(function($item) {
                    return (object) [
                        'amount' => $item['qty'] ?? 0,
                        'average_rate' => $item['rate'] ?? 0 
                    ];
                });
            }
        }

        return view('admin.capital.index', compact('branches', 'currencies', 'selectedDate', 'selectedBranch', 'existingCapital', 'existingStocks'));
    }

    /**
     * PROSES SIMPAN (Logika Jurnal Diperbaiki Total)
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'branch_id' => 'required|exists:branches,id',
            'amount_idr' => 'required|numeric|min:0',
            'stocks' => 'array',
        ]);

        // 1. Format stok valas ke JSON
        $formattedStocks = [];
        if ($request->has('stocks')) {
            foreach ($request->stocks as $code => $data) {
                $qty = isset($data['qty']) ? (float) $data['qty'] : 0;
                $rate = isset($data['rate']) ? (float) $data['rate'] : 0;
                
                $formattedStocks[$code] = [
                    'qty' => $qty,
                    'rate' => $rate
                ];
            }
        }

        DB::beginTransaction(); // Mulai Transaksi Database Aman
        try {
            // 2. SIMPAN KE DATABASE (InitialCapital) - Sama seperti lama
            $capital = InitialCapital::updateOrCreate(
                [
                    'date' => $request->date,
                    'branch_id' => $request->branch_id
                ],
                [
                    'amount' => $request->amount_idr,
                    'forex_stocks' => $formattedStocks, 
                    'description' => 'Setoran Modal Awal Harian',
                    'user_id' => Auth::id()
                ]
            );

            // ====================================================
            // 3. LOGIKA JURNAL OTOMATIS (MANDIRI & ANTI DUPLIKAT)
            // ====================================================
            
            // A. Tentukan Reference No (Kita dukung 2 format agar aman)
            // Format Baru (Simple): CAP-1 (Sesuai Script Recovery)
            $refNoSimple = 'CAP-' . $capital->id;
            
            // Format Lama (Padding): CAP-000001 (Sesuai AccountingService lama)
            $refNoPad = 'CAP-' . str_pad($capital->id, 6, '0', STR_PAD_LEFT);

            // B. Hapus Jurnal Lama (Cek kedua format biar tidak duplikat)
            // Ini akan menghapus jejak lama baik yang format CAP-1 maupun CAP-000001
            $oldIds = JournalEntry::whereIn('reference_no', [$refNoSimple, $refNoPad])->pluck('id');
            if ($oldIds->count() > 0) {
                JournalItem::whereIn('journal_entry_id', $oldIds)->delete(); // Hapus Item
                JournalEntry::whereIn('id', $oldIds)->delete(); // Hapus Header
            }

            // C. Hitung Total Nilai Valas (Parsir JSON)
            $totalNilaiValas = 0;
            foreach ($formattedStocks as $code => $data) {
                $qty = isset($data['qty']) ? floatval($data['qty']) : 0;
                $rate = isset($data['rate']) ? floatval($data['rate']) : 0;
                $totalNilaiValas += ($qty * $rate);
            }

            $nilaiRupiah = floatval($request->amount_idr);
            $totalModal = $totalNilaiValas + $nilaiRupiah;

            // D. Buat Jurnal Baru (Jika ada nilainya)
            if ($totalModal > 0) {
                // Header Jurnal (Pakai format Simple biar konsisten sama recovery)
                $journal = JournalEntry::create([
                    'date'          => $request->date,
                    'reference_no'  => $refNoSimple, 
                    'description'   => 'Setor Modal Awal (Valas & IDR)',
                    'branch_id'     => $request->branch_id,
                    'created_by'    => Auth::id(),
                ]);

                // ID AKUN (HARDCODE SESUAI DATABASE ANDA BIAR PASTI BENAR)
                $idKasKasir     = 2;   // Kas Kasir
                $idPersediaan   = 5;   // Persediaan Valas
                $idModalDisetor = 14;  // Modal Disetor

                // DEBIT 1: PERSEDIAAN VALAS (Sesuai hitungan JSON)
                if ($totalNilaiValas > 0) {
                    JournalItem::create([
                        'journal_entry_id' => $journal->id,
                        'account_id'       => $idPersediaan, 
                        'debit'            => $totalNilaiValas,
                        'credit'           => 0,
                    ]);
                }

                // DEBIT 2: KAS KASIR (Jika ada setoran Rupiah)
                if ($nilaiRupiah > 0) {
                    JournalItem::create([
                        'journal_entry_id' => $journal->id,
                        'account_id'       => $idKasKasir, 
                        'debit'            => $nilaiRupiah,
                        'credit'           => 0,
                    ]);
                }

                // KREDIT: MODAL DISETOR (Total Keduanya)
                JournalItem::create([
                    'journal_entry_id' => $journal->id,
                    'account_id'       => $idModalDisetor, 
                    'debit'            => 0,
                    'credit'           => $totalModal,
                ]);
            }

            DB::commit();
            
            return redirect()->route('admin.capital.index', [
                'date' => $request->date, 
                'branch_id' => $request->branch_id
            ])->with('success', 'Data Modal Disimpan & Buku Besar Telah Diupdate Otomatis!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }
    
    /**
     * PROSES HAPUS (Logika Hapus Jurnal Diperbaiki)
     */
    public function destroy($id)
    {
        $capital = InitialCapital::findOrFail($id);

        // Hapus Jurnal (Cek 2 format referensi sekaligus)
        $refNoSimple = 'CAP-' . $capital->id;
        $refNoPad = 'CAP-' . str_pad($capital->id, 6, '0', STR_PAD_LEFT);

        $oldIds = JournalEntry::whereIn('reference_no', [$refNoSimple, $refNoPad])->pluck('id');
        
        if ($oldIds->count() > 0) {
            JournalItem::whereIn('journal_entry_id', $oldIds)->delete();
            JournalEntry::whereIn('id', $oldIds)->delete();
        }

        $capital->delete();

        return back()->with('success', 'Data Modal & Jurnal Akuntansi berhasil dihapus.');
    }
}