<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;

class ComplianceService
{
    /**
     * Mengecek status threshold untuk halaman LTKT Warning
     */
    public function checkThresholdStatus($customerId, $amount = 0)
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $historicalTotal = Transaction::where('customer_identity_no', $customerId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_idr');

        $projectedTotal = $historicalTotal + $amount;
        $limitThreshold = 150000000;

        return [
            'customer_identity_no' => $customerId,
            'current_total'        => $historicalTotal,
            'projected_total'      => $projectedTotal,
            'is_exceeded'          => $projectedTotal >= $limitThreshold,
            'is_warning'           => $projectedTotal >= ($limitThreshold * 0.8) && $projectedTotal < $limitThreshold,
            'remaining_limit'      => max(0, $limitThreshold - $historicalTotal),
        ];
    }
}