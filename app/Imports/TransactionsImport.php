<?php

namespace App\Imports;

use App\Models\Transaction;
use App\Models\Shift;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date; // 1. TAMBAHKAN IMPORT INI

class TransactionsImport implements ToModel, WithHeadingRow
{
    protected $branchId;
    protected $userId;
    protected $shiftId;

    public function __construct($branchId, $userId)
    {
        $this->branchId = $branchId;
        $this->userId   = $userId;

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
        if (empty($row['nama_nasabah']) || empty($row['jumlah_valas'])) {
            return null;
        }

        do {
            $trxCode = 'IMP-' . strtoupper(Str::random(6));
        } while (Transaction::where('transaction_code', $trxCode)->exists());

        // --- 2. PARSE TANGGAL TRANSAKSI DENGAN AMAN ---
        $transactionDate = now();
        if (!empty($row['tanggal'])) {
            try {
                if (is_numeric($row['tanggal'])) {
                    // Jika format di Excel berupa Serial Date
                    $transactionDate = Carbon::instance(Date::excelToDateTimeObject($row['tanggal']))->format('Y-m-d H:i:s');
                } else {
                    // Jika format di Excel berupa string biasa
                    $transactionDate = Carbon::parse($row['tanggal'])->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                $transactionDate = now();
            }
        }

        // --- 3. PARSE TANGGAL LAHIR DENGAN AMAN ---
        $customerDob = null;
        if (!empty($row['tgl_lahir'])) {
            try {
                if (is_numeric($row['tgl_lahir'])) {
                    $customerDob = Carbon::instance(Date::excelToDateTimeObject($row['tgl_lahir']))->format('Y-m-d');
                } else {
                    $customerDob = Carbon::parse($row['tgl_lahir'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                $customerDob = null;
            }
        }

        $type = strtolower($row['tipe_transaksi'] ?? 'buy');
        if ($type === 'beli') $type = 'buy';
        if ($type === 'jual') $type = 'sell';

        $customerType = strtoupper($row['tipe_nasabah'] ?? 'INDIVIDUAL');
        if (!in_array($customerType, ['INDIVIDUAL', 'CORPORATE'])) {
            $customerType = 'INDIVIDUAL';
        }

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
            'customer_dob'         => $customerDob,
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