<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CapitalController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\ComplianceCheckController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\InternalMutationController; 
use App\Http\Controllers\ClosingController;
use App\Http\Controllers\LedgerController; 
use App\Http\Controllers\AssetController;  
use App\Http\Controllers\JournalController;
use App\Http\Middleware\CheckRole; 
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes - FINAL FIXED v3 (Closing Route Fix & APU-PPT Fix)
|--------------------------------------------------------------------------
*/

// 1. ROOT (Penyortir Otomatis)
Route::get('/', function () {
    if (Auth::check()) {
        return in_array(Auth::user()->role, ['admin', 'owner']) 
            ? redirect()->route('admin.dashboard') 
            : redirect()->route('cashier.dashboard');
    }
    return redirect()->route('login');
});

// 2. AUTH (Tamu)
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

// 3. AREA YANG SUDAH LOGIN (SEMUA ROLE BISA AKSES)
Route::middleware(['auth'])->group(function () {
    
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    // --- A. FITUR PROFILE ---
    Route::get('/settings', [ProfileController::class, 'edit'])->name('settings.index'); 
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- B. FITUR BERSAMA ---
    
    // 1. Data Nasabah
    Route::get('/nasabah/data', [MenuController::class, 'nasabah'])->name('nasabah.index');
    Route::get('/nasabah/kyc', [MenuController::class, 'kyc'])->name('nasabah.kyc');
    Route::post('/nasabah/kyc/threshold-rate', [MenuController::class, 'updateThresholdRate'])->name('nasabah.kyc.threshold-rate');
    Route::get('/nasabah/assessment', [AssessmentController::class, 'index'])->name('nasabah.assessment');
    Route::get('/nasabah/edit/{id}', [MenuController::class, 'edit'])->name('nasabah.edit');
    Route::post('/nasabah/import', [TransactionController::class, 'importExcel'])->name('nasabah.import');
    Route::put('/nasabah/update/{id}', [MenuController::class, 'update'])->name('nasabah.update');
    Route::get('/nasabah/{id}/print', [App\Http\Controllers\MenuController::class, 'printStruk'])->name('nasabah.print');
    Route::delete('/nasabah-transaction/{id}', [MenuController::class, 'destroy'])->name('nasabah.destroy');
    Route::delete('/nasabah/destroy-nota/{id}', [App\Http\Controllers\MenuController::class, 'destroyNota'])->name('nasabah.destroy_nota');

    // 2. Laporan Mutasi
    Route::get('/mutasi/harian', [App\Http\Controllers\MutationController::class, 'daily'])->name('mutasi.harian');
    Route::get('/mutasi/bulanan', [App\Http\Controllers\MutationController::class, 'monthly'])->name('mutasi.bulanan');

    // 3. Keuangan Ringan (Biaya & Cashflow Harian)
    Route::prefix('laporan')->group(function() {
        Route::get('/biaya', [ReportController::class, 'biayaOperasional'])->name('keuangan.biaya');
        Route::post('/biaya/store', [ReportController::class, 'biayaStore'])->name('biaya.store');
        Route::put('/biaya/{id}', [ReportController::class, 'biayaUpdate'])->name('biaya.update');
        Route::delete('/biaya/{id}', [ReportController::class, 'biayaDestroy'])->name('biaya.destroy');
        
        Route::get('/cash-flow', [App\Http\Controllers\CashFlowController::class, 'index'])->name('reports.cashflow');
    });

    // 4. Mutasi Internal (Tarik/Setor Bank) - Shared Access
    Route::get('/internal-mutation', [InternalMutationController::class, 'index'])->name('internal-mutation.index');
    Route::post('/internal-mutation', [InternalMutationController::class, 'store'])->name('internal-mutation.store');
    Route::delete('/internal-mutation/{id}', [InternalMutationController::class, 'destroy'])->name('internal-mutation.destroy');

    // ====================================================
    // PERBAIKAN: MODUL PENGECEKAN THRESHOLD APU-PPT
    // Dipindah ke area publik (bawah Internal Mutation) agar Kasir bisa mengaksesnya
    // ====================================================
    Route::prefix('compliance-check')->name('compliance.check.')->group(function () {
        Route::get('/threshold/{customerId}', [ComplianceCheckController::class, 'checkCustomerThreshold'])->name('threshold');
        Route::get('/ltkt-warning', [ComplianceCheckController::class, 'showLtktWarningPage'])->name('ltkt.warning');
    });

    // ====================================================
    // 5. AREA KHUSUS ADMIN & OWNER
    // ====================================================
    Route::middleware([CheckRole::class.':admin,owner'])->group(function() {
        
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

        // MODULE: BRANCHES
        Route::prefix('admin/branches')->group(function() {
            Route::get('/', [AdminController::class, 'branchIndex'])->name('admin.branches.index'); 
            Route::get('/create', [AdminController::class, 'branchCreate'])->name('cabang.create');
            Route::post('/store', [AdminController::class, 'branchStore'])->name('cabang.store');
            Route::get('/edit/{id}', [AdminController::class, 'branchEdit'])->name('cabang.edit');
            Route::put('/update/{id}', [AdminController::class, 'branchUpdate'])->name('cabang.update');
            Route::delete('/destroy/{id}', [AdminController::class, 'branchDestroy'])->name('cabang.destroy');
        });

        // MODULE: USERS
        Route::prefix('admin/users')->group(function() {
            Route::get('/', [AdminController::class, 'userIndex'])->name('admin.users.index');
            Route::get('/create', [AdminController::class, 'userCreate'])->name('admin.users.create');
            Route::post('/store', [AdminController::class, 'userStore'])->name('admin.users.store');
            Route::get('/edit/{id}', [AdminController::class, 'userEdit'])->name('admin.users.edit');
            Route::put('/update/{id}', [AdminController::class, 'userUpdate'])->name('admin.users.update');
            Route::delete('/destroy/{id}', [AdminController::class, 'userDestroy'])->name('admin.users.destroy');
        });

        // MODULE: VALAS
        Route::prefix('admin/currencies')->group(function() {
            Route::get('/', [AdminController::class, 'currencyIndex'])->name('admin.currencies.index');
            Route::post('/store', [AdminController::class, 'currencyStore'])->name('admin.currencies.store');
            Route::put('/update/{id}', [AdminController::class, 'currencyUpdate'])->name('admin.currencies.update');
            Route::delete('/destroy/{id}', [AdminController::class, 'currencyDestroy'])->name('admin.currencies.destroy');
        });
        
        // MODULE: MODAL HARIAN
        Route::prefix('admin/capital')->group(function() {
            Route::get('/', [CapitalController::class, 'index'])->name('admin.capital.index');
            Route::get('/create', [CapitalController::class, 'create'])->name('admin.capital.create'); 
            Route::post('/store', [CapitalController::class, 'store'])->name('admin.capital.store');
            Route::delete('/{id}', [CapitalController::class, 'destroy'])->name('admin.capital.destroy'); 
        });

        // MODULE: AKUNTANSI
        Route::prefix('admin/accounting')->name('accounting.')->group(function() {
            
            // 1. COA
            Route::get('/coa', [AccountingController::class, 'coaIndex'])->name('coa.index');
            Route::post('/coa', [AccountingController::class, 'coaStore'])->name('coa.store');
            Route::put('/coa/{id}', [AccountingController::class, 'coaUpdate'])->name('coa.update');
            Route::delete('/coa/{id}', [AccountingController::class, 'coaDestroy'])->name('coa.destroy');
            
            // 2. ASET TETAP
            Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
            Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
            Route::put('/assets/{id}', [AssetController::class, 'update'])->name('assets.update');
            Route::delete('/assets/{id}', [AssetController::class, 'destroy'])->name('assets.destroy');
            
            // 3. JURNAL UMUM
            Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
            Route::get('/journals/create', [JournalController::class, 'create'])->name('journals.create');
            Route::post('/journals', [JournalController::class, 'store'])->name('journals.store');
            Route::delete('/journals/{id}', [JournalController::class, 'destroy'])->name('journals.destroy');

            // 4. TUTUP BUKU (CLOSING)
            Route::get('/closing', [ClosingController::class, 'index'])->name('closing.index');
            Route::post('/closing', [ClosingController::class, 'process'])->name('closing.process');
        });

        // MODULE: LAPORAN KEUANGAN LENGKAP
        Route::prefix('laporan')->name('keuangan.')->group(function() {
            Route::get('/laba-rugi', [ReportController::class, 'labaRugi'])->name('labarugi');
            Route::get('/ekuitas', [ReportController::class, 'ekuitas'])->name('ekuitas');
            Route::post('/ekuitas/store', [ReportController::class, 'ekuitasStore'])->name('ekuitas.store');
            Route::delete('/ekuitas/{id}', [ReportController::class, 'ekuitasDestroy'])->name('ekuitas.destroy');

            Route::get('/neraca', [ReportController::class, 'neraca'])->name('neraca');
            
            // BUKU BESAR
            Route::get('/buku-besar', [LedgerController::class, 'index'])->name('buku_besar');
        });

        // MODULE: APU-PPT (MANAJEMEN OLEH ADMIN)
        Route::prefix('admin/compliance')->name('compliance.')->group(function() {
            Route::get('/dttot', [ComplianceController::class, 'dttotIndex'])->name('dttot.index');
            Route::post('/dttot/import', [ComplianceController::class, 'dttotStore'])->name('dttot.import');
            Route::delete('/dttot/truncate', [ComplianceController::class, 'dttotTruncate'])->name('dttot.truncate');
            Route::delete('/dttot/{id}', [ComplianceController::class, 'dttotDestroy'])->name('dttot.destroy');
            
            Route::get('/ltkt', [ComplianceController::class, 'ltktIndex'])->name('ltkt.index');
            Route::get('/ltkm', [ComplianceController::class, 'ltkmIndex'])->name('ltkm.index');
            Route::post('/ltkm/store', [ComplianceController::class, 'ltkmStore'])->name('ltkm.store');
            Route::delete('/ltkm/{id}', [ComplianceController::class, 'ltkmDestroy'])->name('ltkm.destroy');
        });
    }); // <-- PENUTUP AREA ADMIN/OWNER

    // ====================================================
    // 6. AREA KHUSUS KASIR
    // ====================================================
    Route::middleware(['auth', CheckRole::class.':cashier'])->group(function() {
        
        Route::get('/cashier', [CashierController::class, 'index'])->name('cashier.dashboard');
        Route::post('/cashier/branch', [CashierController::class, 'storeBranch'])->name('cashier.store-branch');
        
        Route::get('/cashier/open-shift', [CashierController::class, 'createShift'])->name('cashier.shift.create'); 
        Route::post('/cashier/open-shift', [CashierController::class, 'storeShift'])->name('cashier.shift.store');  

        Route::prefix('transaction')->group(function() {
            Route::get('/', [TransactionController::class, 'index'])->name('transaction.index');
            Route::get('/search-customers', [TransactionController::class, 'searchCustomers'])->name('transaction.search.customers'); 
            Route::post('/store', [TransactionController::class, 'store'])->name('transaction.store');
            Route::post('/end-shift', [TransactionController::class, 'endShift'])->name('transaction.endShift'); 
            Route::delete('/{id}', [TransactionController::class, 'destroy'])->name('transaction.destroy');
            Route::get('/transactions/check-nota', [TransactionController::class, 'checkNoNota'])->name('transaction.checkNota');
        });
        
    });
});