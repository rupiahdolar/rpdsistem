<?php

namespace App\Imports;

use App\Models\Transaction;
use App\Models\Shift;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionsImport implements ToModel, WithHeadingRow
{
    protected $branchId;
    protected $userId;
    protected $shiftId;

    public function __construct($branchId, $userId)
    {
        $this->branchId = $branchId;
        $this->userId   = $userId;

        // Cari atau buatkan Shift Khusus untuk penampung Import Data Lama
        $importShift = Shift::firstOrCreate(
            [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'status' => 'closed',
            ],
            [
                'start_time' => now(),
                'end_time' => now(),
                'start_cash' => 0,
                'expected_cash' => 0,
                'actual_cash' => 0,
            ]
        );

        $this->shiftId = $importShift->id;
    }

    public function model(array $row): ?\Illuminate\Database\Eloquent\Model
    {
        // Abaikan jika baris nama nasabah atau jumlah valas kosong
        if (empty($row['nama_nasabah']) || empty($row['jumlah_valas'])) {
            return null;
        }

        // Generate Transaction Code Unik
        do {
            $trxCode = 'IMP-' . strtoupper(Str::random(6));
        } while (Transaction::where('transaction_code', $trxCode)->exists());

        // Parse Tanggal Transaksi
        $transactionDate = now();
        if (!empty($row['tanggal'])) {
            try {
                $transactionDate = Carbon::parse($row['tanggal'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $transactionDate = now();
            }
        }

        // Conversi Tipe Transaksi (Beli/Jual)
        $type = strtolower($row['tipe_transaksi'] ?? 'buy');
        if ($type === 'beli') $type = 'buy';
        if ($type === 'jual') $type = 'sell';

        // Conversi Tipe Nasabah
        $customerType = strtoupper($row['tipe_nasabah'] ?? 'INDIVIDUAL');
        if (!in_array($customerType, ['INDIVIDUAL', 'CORPORATE'])) {
            $customerType = 'INDIVIDUAL';
        }

        // Hitung Total IDR
        $amountForeign = (float) $row['jumlah_valas'];
        $rate = (float) $row['rate'];
        $totalIdr = $amountForeign * $rate;

        return new Transaction([
            'transaction_code'     => $trxCode,
            'branch_id'            => $this->branchId,
            'user_id'              => $this->userId,
            'shift_id'             => $this->shiftId,
            'no_nota'              => !empty($row['no_nota']) ? strtoupper($row['no_nota']) : 'INV-OLD-' . strtoupper(Str::random(4)),
            
            // --- DATA NASABAH ---
            'customer_type'        => $customerType,
            'customer_name'        => strtoupper($row['nama_nasabah']),
            'customer_identity_no' => !empty($row['no_id']) ? strtoupper($row['no_id']) : null,
            'customer_id_type'     => !empty($row['tipe_id']) ? strtoupper($row['tipe_id']) : 'KTP',
            'customer_phone'       => $row['telepon'] ?? null,
            'customer_gender'      => !empty($row['jenis_kelamin']) ? strtoupper($row['jenis_kelamin']) : null,
            'customer_dob'         => !empty($row['tgl_lahir']) ? Carbon::parse($row['tgl_lahir'])->format('Y-m-d') : null,
            'customer_address'     => !empty($row['alamat']) ? strtoupper($row['alamat']) : null,
            'customer_job'         => !empty($row['pekerjaan']) ? strtoupper($row['pekerjaan']) : null,
            'customer_country'     => !empty($row['negara']) ? strtoupper($row['negara']) : 'INDONESIA',
            
            // --- DATA APU PPT ---
            'source_of_funds'      => !empty($row['sumber_dana']) ? strtoupper($row['sumber_dana']) : 'TABUNGAN',
            'transaction_purpose'  => !empty($row['tujuan_transaksi']) ? strtoupper($row['tujuan_transaksi']) : 'INVESTASI',

            // --- DATA TRANSAKSI ---
            'type'                 => $type,
            'currency'             => strtoupper($row['mata_uang']),
            'amount_foreign'       => $amountForeign,
            'rate'                 => $rate,
            'total_idr'            => $totalIdr,
            'payment_method'       => 'CASH',
            'created_at'           => $transactionDate,
            'updated_at'           => $transactionDate,
        ]);
    }
}