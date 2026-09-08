<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AccountingService;
use App\Models\Branch;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClosingController extends Controller
{
    public function index()
    {
        $branches = Branch::all();
        $defaultMonth = date('n', strtotime('last month'));
        $defaultYear = date('Y', strtotime('last month'));
        
        return view('admin.accounting.closing.index', compact('branches', 'defaultMonth', 'defaultYear'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2020',
        ]);

        $month = $request->month;
        $year = $request->year;
        $branchId = $request->branch_id; 

        DB::beginTransaction();
        try {
            // PERBAIKAN 1: Cari ref_no atau pattern deskripsi yang persis dengan AccountingService
            $bId = $branchId ?? 1;
            
            // Hapus Jurnal HPP Lama berdasarkan Reference No atau Deskripsi yang Tepat
            $oldHPP = JournalEntry::where(function($q) use ($year, $month, $bId) {
                $q->where('reference_no', "CLS-HPP-$year-$month-B$bId")
                  ->orWhere('description', 'LIKE', "%Penyesuaian HPP & Stok Valas Periode $month/$year%");
            });
            
            if ($branchId) $oldHPP->where('branch_id', $branchId);
            
            $oldHPPIds = $oldHPP->pluck('id');
            if ($oldHPPIds->count() > 0) {
                JournalItem::whereIn('journal_entry_id', $oldHPPIds)->delete();
                JournalEntry::whereIn('id', $oldHPPIds)->delete();
            }

            // Hapus Jurnal Penyusutan Lama
            $oldDep = JournalEntry::where(function($q) use ($year, $month, $bId) {
                $q->where('reference_no', "CLS-DEP-$year-$month-B$bId")
                  ->orWhere('description', 'LIKE', "%Beban Penyusutan Aset Periode $month/$year%");
            });

            if ($branchId) $oldDep->where('branch_id', $branchId);
            
            $oldDepIds = $oldDep->pluck('id');
            if ($oldDepIds->count() > 0) {
                JournalItem::whereIn('journal_entry_id', $oldDepIds)->delete();
                JournalEntry::whereIn('id', $oldDepIds)->delete();
            }

            // GENERATE JURNAL BARU
            $hppAmount = AccountingService::generateMonthlyHPPJournal($month, $year, $branchId);
            $depAmount = AccountingService::generateMonthlyDepreciation($month, $year, $branchId);

            DB::commit();

            return back()->with('success', "Tutup Buku Periode $month/$year SELESAI! \n" .
                "Data lama (jika ada) telah diperbarui. \n" .
                "Jurnal HPP: Rp " . number_format($hppAmount) . "\n" .
                "Jurnal Penyusutan: Rp " . number_format($depAmount));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Gagal Tutup Buku: ' . $e->getMessage()]);
        }
    }
}