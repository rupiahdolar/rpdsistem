<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    // MENAMPILKAN DAFTAR AKUN (COA)
    public function coaIndex()
    {
        // Kelompokkan akun berdasarkan Tipe agar rapi di tabel
        $accounts = ChartOfAccount::orderBy('code')->get()->groupBy('type');
        
        return view('admin.accounting.coa', compact('accounts'));
    }

    // SIMPAN AKUN BARU
    public function coaStore(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:chart_of_accounts,code',
            'name' => 'required|string',
            'type' => 'required|in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE',
            'normal_balance' => 'required|in:DEBIT,CREDIT',
            'opening_balance' => 'nullable|numeric', // [BARU] Validasi Saldo Awal
        ]);

        ChartOfAccount::create([
            'code' => $request->code,
            'name' => strtoupper($request->name),
            'type' => $request->type,
            'normal_balance' => $request->normal_balance,
            'opening_balance' => $request->opening_balance ?? 0, // [BARU] Simpan Saldo Awal
            'is_locked' => false 
        ]);

        return back()->with('success', 'Akun Baru Berhasil Ditambahkan');
    }

    // UPDATE AKUN
    public function coaUpdate(Request $request, $id)
    {
        $account = ChartOfAccount::findOrFail($id);

        $request->validate([
            'code' => 'required|unique:chart_of_accounts,code,'.$id,
            'name' => 'required|string',
            'opening_balance' => 'nullable|numeric', // [BARU] Validasi update
        ]);

        // Data dasar yang akan diupdate
        $data = [
            'name' => strtoupper($request->name),
            'opening_balance' => $request->opening_balance ?? 0, // [BARU] Update Saldo Awal
        ];

        // Jika akun tidak terkunci, boleh update kode & tipe
        if (!$account->is_locked) {
            $data['code'] = $request->code;
            $data['type'] = $request->type;
            $data['normal_balance'] = $request->normal_balance;
        }

        $account->update($data);

        return back()->with('success', 'Data Akun Berhasil Diperbarui');
    }

    // HAPUS AKUN
    public function coaDestroy($id)
    {
        $account = ChartOfAccount::findOrFail($id);

        if ($account->is_locked) {
            return back()->with('error', 'Akun Sistem (Bawaan) tidak boleh dihapus!');
        }

        // Cek Keamanan: Jangan hapus jika sudah dipakai di Jurnal atau Transaksi
        $isUsedInJournal = \App\Models\JournalItem::where('account_id', $id)->exists();
        $isUsedInTransaction = \App\Models\Transaction::where('bank_account_id', $id)->exists();

        if ($isUsedInJournal || $isUsedInTransaction) {
            return back()->withErrors(['msg' => 'Gagal! Akun ini sudah memiliki riwayat transaksi. Tidak bisa dihapus.']);
        }

        $account->delete();
        return back()->with('success', 'Akun Berhasil Dihapus');
    }
}