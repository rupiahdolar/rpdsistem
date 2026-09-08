<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FixedAsset;
use App\Models\AssetDepreciation;
use App\Models\Branch;
use App\Models\JournalEntry; 
use App\Models\JournalItem;  
use App\Models\ChartOfAccount;
use App\Services\AccountingService; 
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    public function index()
    {
        $assets = FixedAsset::with('depreciations')->orderBy('purchase_date', 'desc')->get();
        $branches = Branch::all();
        
        // Ambil akun Kas / Bank untuk pilihan pembayaran
        $paymentAccounts = ChartOfAccount::where('type', 'ASSET')
                            ->where(function($q) {
                                $q->where('code', 'like', '1-10%') // Akun Kas & Bank
                                  ->orWhere('name', 'like', '%Kas%')
                                  ->orWhere('name', 'like', '%Bank%')
                                  ->orWhere('name', 'like', '%Vault%');
                            })
                            ->orderBy('code', 'asc')
                            ->get();

        return view('admin.accounting.assets.index', compact('assets', 'branches', 'paymentAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1',
            'residual_value' => 'nullable|numeric|min:0',
            'branch_id' => 'required|exists:branches,id',
            'payment_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        DB::beginTransaction();
        try {
            // 1. Simpan Data Aset Header
            $asset = FixedAsset::create([
                'name' => strtoupper($request->name),
                'serial_number' => $request->serial_number,
                'purchase_date' => $request->purchase_date,
                'purchase_cost' => $request->purchase_cost,
                'useful_life_months' => $request->useful_life_months,
                'residual_value' => $request->residual_value ?? 0,
                'book_value' => $request->purchase_cost, 
                'branch_id' => $request->branch_id,
                'status' => 'ACTIVE'
            ]);

            // 2. Hitung Penyusutan (Straight Line)
            $depreciableAmount = $asset->purchase_cost - $asset->residual_value;
            $monthlyDepreciation = round($depreciableAmount / $asset->useful_life_months, 2);

            // 3. Generate Array untuk Bulk Insert
            $schedule = [];
            $currentDate = Carbon::parse($asset->purchase_date);

            for ($i = 1; $i <= $asset->useful_life_months; $i++) {
                $depDate = $currentDate->copy()->addMonths($i)->endOfMonth();

                $schedule[] = [
                    'fixed_asset_id' => $asset->id,
                    'date' => $depDate->format('Y-m-d'),
                    'amount' => $monthlyDepreciation,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($schedule) > 0) {
                foreach (array_chunk($schedule, 500) as $chunk) {
                    AssetDepreciation::insert($chunk);
                }
            }

            // 4. Auto Jurnal dengan Parameter Sumber Dana
            if (class_exists(AccountingService::class)) {
                AccountingService::recordAssetPurchase($asset, $request->payment_account_id);
            }

            DB::commit();
            return back()->with('success', 'Aset Berhasil Disimpan & Dijurnalkan ke Neraca!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Gagal menyimpan aset: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1',
            'residual_value' => 'nullable|numeric|min:0',
            'branch_id' => 'required|exists:branches,id',
            'payment_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        DB::beginTransaction();
        try {
            $asset = FixedAsset::findOrFail($id);

            // 1. Update Data Aset
            $asset->update([
                'name' => strtoupper($request->name),
                'serial_number' => $request->serial_number,
                'purchase_date' => $request->purchase_date,
                'purchase_cost' => $request->purchase_cost,
                'useful_life_months' => $request->useful_life_months,
                'residual_value' => $request->residual_value ?? 0,
                'book_value' => $request->purchase_cost,
                'branch_id' => $request->branch_id,
            ]);

            // 2. Hapus Jadwal Penyusutan Lama & Buat Ulang Sesuai Umur/Harga Baru
            AssetDepreciation::where('fixed_asset_id', $asset->id)->delete();

            $depreciableAmount = $asset->purchase_cost - $asset->residual_value;
            $monthlyDepreciation = round($depreciableAmount / $asset->useful_life_months, 2);

            $schedule = [];
            $currentDate = Carbon::parse($asset->purchase_date);

            for ($i = 1; $i <= $asset->useful_life_months; $i++) {
                $depDate = $currentDate->copy()->addMonths($i)->endOfMonth();
                $schedule[] = [
                    'fixed_asset_id' => $asset->id,
                    'date' => $depDate->format('Y-m-d'),
                    'amount' => $monthlyDepreciation,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($schedule) > 0) {
                foreach (array_chunk($schedule, 500) as $chunk) {
                    AssetDepreciation::insert($chunk);
                }
            }

            // 3. Update Jurnal Akuntansi Pembelian Aset
            $refNo = 'AST-' . str_pad($asset->id, 6, '0', STR_PAD_LEFT);
            $journal = JournalEntry::where('reference_no', $refNo)->first();

            if ($journal) {
                // Hapus item jurnal lama
                JournalItem::where('journal_entry_id', $journal->id)->delete();
                
                // Update header jurnal
                $journal->update([
                    'date' => $asset->purchase_date,
                    'description' => "Pembelian Aset Tetap: " . $asset->name,
                    'branch_id' => $asset->branch_id,
                ]);

                // Tentukan Akun Aset
                $assetAccountCode = '1-2001';
                if (str_contains(strtoupper($asset->name), 'MOTOR') || str_contains(strtoupper($asset->name), 'MOBIL')) {
                    $assetAccountCode = '1-2002';
                }
                $accAsset = ChartOfAccount::where('code', $assetAccountCode)->first() ?? ChartOfAccount::where('code', '1-2001')->first();
                
                // Ambil Akun Kas Pengurang yang Baru
                $accKas = ChartOfAccount::find($request->payment_account_id);

                if ($accAsset && $accKas) {
                    JournalItem::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $accAsset->id,
                        'debit' => $asset->purchase_cost,
                        'credit' => 0,
                    ]);

                    JournalItem::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $accKas->id,
                        'debit' => 0,
                        'credit' => $asset->purchase_cost,
                    ]);
                }
            } else {
                if (class_exists(AccountingService::class)) {
                    AccountingService::recordAssetPurchase($asset, $request->payment_account_id);
                }
            }

            DB::commit();
            return back()->with('success', 'Data Aset & Jurnal Pembelian Berhasil Diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Gagal memperbarui aset: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $asset = FixedAsset::findOrFail($id);

        $refNo = 'AST-' . str_pad($asset->id, 6, '0', STR_PAD_LEFT);
        
        $journal = JournalEntry::where('reference_no', $refNo)->first();
        if ($journal) {
            JournalItem::where('journal_entry_id', $journal->id)->delete();
            $journal->delete();
        }

        $asset->delete(); 
        
        return back()->with('success', 'Data Aset & Jurnal Akuntansi Berhasil Dihapus.');
    }
}