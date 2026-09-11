<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\ComplaintController;
use App\Http\Controllers\Api\v1\FinanceCategoryController;
use App\Http\Controllers\Api\v1\LogisticsController;
use App\Http\Controllers\Api\v1\MemberCategoryController;
use App\Http\Controllers\Api\v1\MemberController;
use App\Http\Controllers\Api\v1\PickupController;
use App\Http\Controllers\Api\v1\ReportController;
use App\Http\Controllers\Api\v1\SavingsController;
use App\Http\Controllers\Api\v1\ShuController;
use App\Http\Controllers\Api\v1\TransactionController;
use App\Http\Controllers\Api\v1\TrashCategoryController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Fallback 401 JSON untuk nama route 'login' — API-only app tidak punya halaman
    // login web; tanpa ini request tanpa token dari kode lama/skeleton lama
    // meledak jadi "Route [login] not defined" (500) alih-alih 401.
    Route::get('/login', fn () => response()->json([
        'message' => 'Unauthenticated or invalid token.',
        'errors' => null,
    ], 401))->name('login');

    Route::prefix('auth')->group(function () {
        // Public endpoints
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('register-member', [AuthController::class, 'registerMember'])->middleware('throttle:5,1');

        // Authenticated endpoints
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::put('change-password', [AuthController::class, 'changePassword']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    // Manajemen pengguna (pengurus/petugas/anggota) — kelola dari portal pengurus
    Route::prefix('users')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('role:pengurus');
        Route::post('/', [UserController::class, 'store'])->middleware('role:pengurus');
        Route::get('/{id}', [UserController::class, 'show'])->middleware('role:pengurus');
        Route::put('/{id}', [UserController::class, 'update'])->middleware('role:pengurus');
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('role:pengurus');
    });

    // Master data keanggotaan
    Route::prefix('member-categories')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [MemberCategoryController::class, 'index'])->middleware('role:pengurus,petugas');
        Route::post('/', [MemberCategoryController::class, 'store'])->middleware('role:pengurus');
    });

    Route::prefix('members')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [MemberController::class, 'index'])->middleware('role:pengurus,petugas');
        Route::post('/', [MemberController::class, 'store'])->middleware('role:pengurus');
        // Own-check untuk role anggota ada di controller (show)
        Route::get('/{id}', [MemberController::class, 'show'])->middleware('role:pengurus,petugas,anggota');
        Route::put('/{id}', [MemberController::class, 'update'])->middleware('role:pengurus');
        Route::patch('/{id}/status', [MemberController::class, 'updateStatus'])->middleware('role:pengurus');
    });

    // Katalog sampah & manajemen harga
    Route::prefix('trash-categories')->group(function () {
        // Publik: katalog & papan harga
        Route::get('/', [TrashCategoryController::class, 'index']);
        Route::get('/board', [TrashCategoryController::class, 'board']);

        Route::post('/', [TrashCategoryController::class, 'store'])->middleware(['auth:sanctum', 'role:pengurus']);
        Route::get('/{id}', [TrashCategoryController::class, 'show'])->middleware('auth:sanctum');
        Route::put('/{id}', [TrashCategoryController::class, 'update'])->middleware(['auth:sanctum', 'role:pengurus']);
        Route::patch('/{id}/price', [TrashCategoryController::class, 'updatePrice'])->middleware(['auth:sanctum', 'role:pengurus']);
        Route::get('/{id}/price-history', [TrashCategoryController::class, 'priceHistory'])->middleware(['auth:sanctum', 'role:pengurus']);
    });

    // Operasional bank sampah: penjemputan & timbang
    Route::prefix('pickups')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [PickupController::class, 'index'])->middleware('role:pengurus,petugas,anggota');
        Route::post('/', [PickupController::class, 'store'])->middleware('role:pengurus,petugas,anggota');
        Route::post('/{id}/weigh-items', [PickupController::class, 'weighItems'])->middleware('role:pengurus,petugas');
        Route::post('/{id}/photo', [PickupController::class, 'uploadPhoto'])->middleware('role:pengurus,petugas');
        // Own-check untuk role anggota ada di controller (show/receipt)
        Route::get('/{id}', [PickupController::class, 'show'])->middleware('role:pengurus,petugas,anggota');
        Route::get('/{id}/receipt', [PickupController::class, 'receipt'])->middleware('role:pengurus,petugas,anggota');
        Route::patch('/{id}/cancel', [PickupController::class, 'cancel'])->middleware('role:pengurus');
        // Plotting petugas: tugaskan petugas ke tiket penjemputan (pengurus)
        Route::patch('/{id}/assign', [PickupController::class, 'assign'])->middleware('role:pengurus');
    });

    // Pengaduan & komplain nota timbang
    Route::prefix('complaints')->middleware('auth:sanctum')->group(function () {
        // Anggota dapat submit & melihat miliknya (own-check di controller)
        Route::get('/', [ComplaintController::class, 'index'])->middleware('role:pengurus,petugas,anggota');
        Route::post('/', [ComplaintController::class, 'store'])->middleware('role:pengurus,petugas,anggota');
        Route::get('/{id}', [ComplaintController::class, 'show'])->middleware('role:pengurus,petugas,anggota');
        Route::patch('/{id}/resolve', [ComplaintController::class, 'resolve'])->middleware('role:pengurus');
    });

    // Keuangan koperasi, simpanan & billing rutin
    Route::prefix('finance-categories')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [FinanceCategoryController::class, 'index'])->middleware('role:pengurus');
        Route::post('/', [FinanceCategoryController::class, 'store'])->middleware('role:pengurus');
    });

    Route::prefix('savings')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [SavingsController::class, 'index'])->middleware('role:pengurus,anggota');
        Route::post('/pay', [SavingsController::class, 'pay'])->middleware('role:pengurus');
        Route::get('/billing-status', [SavingsController::class, 'billingStatus'])->middleware('role:pengurus,anggota');
        Route::get('/wajib-overview', [SavingsController::class, 'wajibOverview'])->middleware('role:pengurus');
        Route::post('/generate-monthly-billing', [SavingsController::class, 'generateMonthlyBilling'])->middleware('role:pengurus');
    });

    Route::prefix('transactions')->middleware(['auth:sanctum', 'role:pengurus'])->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/', [TransactionController::class, 'store']);
    });

    // Dompet saldo anggota & penarikan tunai
    Route::prefix('wallet')->middleware('auth:sanctum')->group(function () {
        // Anggota: dompet sendiri (own-check di controller); pengurus: boleh query member
        Route::get('/summary', [WalletController::class, 'summary'])->middleware('role:pengurus,anggota');
        Route::get('/mutations', [WalletController::class, 'mutations'])->middleware('role:pengurus,anggota');
        Route::post('/withdraw', [WalletController::class, 'withdraw'])->middleware('role:anggota');
        // Penarikan tunai oleh petugas/pengurus (saldo langsung terpotong, alur.md)
        Route::post('/withdraw-cash', [WalletController::class, 'withdrawCash'])->middleware('role:pengurus,petugas');
        Route::get('/withdraw-requests', [WalletController::class, 'withdrawRequests'])->middleware('role:pengurus');
        Route::patch('/withdraw-requests/{id}/approve', [WalletController::class, 'approveWithdrawal'])->middleware('role:pengurus');
    });

    // Mesin kalkulasi & pembagian SHU tahunan
    Route::prefix('shu')->middleware('auth:sanctum')->group(function () {
        Route::get('/periods', [ShuController::class, 'periods'])->middleware('role:pengurus');
        Route::post('/simulate', [ShuController::class, 'simulate'])->middleware('role:pengurus');
        Route::patch('/drafts/{id}', [ShuController::class, 'updateDraft'])->middleware('role:pengurus');
        Route::post('/publish', [ShuController::class, 'publish'])->middleware('role:pengurus');
        Route::get('/my-history', [ShuController::class, 'myHistory'])->middleware('role:anggota');
    });

    // Logistik wilayah & rekapitulasi pengangkutan
    Route::prefix('logistics/routes')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [LogisticsController::class, 'index'])->middleware('role:pengurus,petugas');
        Route::post('/', [LogisticsController::class, 'store'])->middleware('role:pengurus,petugas');
    });

    // Laporan eksekutif & analitik
    Route::prefix('reports')->middleware(['auth:sanctum', 'role:pengurus'])->group(function () {
        Route::get('/dashboard-stats', [ReportController::class, 'dashboardStats']);
        Route::get('/financial', [ReportController::class, 'financial']);
        Route::get('/trash-volume', [ReportController::class, 'trashVolume']);
        Route::get('/export', [ReportController::class, 'export']);
    });
});
