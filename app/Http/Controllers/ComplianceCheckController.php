<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ComplianceService;
use App\Models\Transaction; 
use Carbon\Carbon;          
use Illuminate\Support\Facades\DB;

class ComplianceCheckController extends Controller
{
    protected ComplianceService $complianceService;

    public function __construct(ComplianceService $complianceService)
    {
        $this->complianceService = $complianceService;
    }

    /**
     * Endpoint API untuk mengecek limit threshold nasabah secara real-time[cite: 6]
     */
    public function checkCustomerThreshold(Request $request, $customerId)
    {
        $currentAmount = (float) $request->get('amount', 0);
        $transactionType = $request->get('type', 'sell'); 
        
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // [PERBAIKAN] Akumulasi historis khusus untuk transaksi JUAL (sell) saja
        // agar transaksi saat nasabah menjual ke kita (buy) tidak ikut menjerat limit pembelian mereka
        $historicalTotal = Transaction::where('customer_identity_no', $customerId)
            ->where('type', 'sell') // <-- HANYA MENGHITUNG RIWAYAT PENJUALAN KITA KE NASABAH
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_idr');

        $projectedTotal = $historicalTotal + $currentAmount;
        
        $usdRate = DB::table('settings')->where('key', 'threshold_usd_rate')->value('value') ?? 15000;
        $limitThreshold = 10000 * (float) $usdRate;

        $isExceeded = ($transactionType === 'sell') ? ($projectedTotal >= $limitThreshold) : false;

        return response()->json([
            'status' => 'success',
            'data' => [
                'customer_identity_no' => $customerId,
                'current_total'        => (float) $historicalTotal,
                'projected_total'      => (float) $projectedTotal,
                'is_exceeded'          => $isExceeded,
                'is_warning'           => ($transactionType === 'sell') ? ($projectedTotal >= ($limitThreshold * 0.8) && $projectedTotal < $limitThreshold) : false,
                'remaining_limit'      => max(0, $limitThreshold - $historicalTotal),
                'dynamic_limit'        => (float) $limitThreshold,
            ]
        ]);
    }

    /**
     * Halaman Khusus Verifikasi LTKT[cite: 6]
     */
    public function showLtktWarningPage(Request $request)
    {
        $customerId = $request->query('customer_id');
        $amount = $request->query('amount', 0);

        $complianceData = $this->complianceService->checkThresholdStatus($customerId, (float)$amount);

        return view('admin.compliance.ltkt_warning', compact('complianceData'));
    }
}