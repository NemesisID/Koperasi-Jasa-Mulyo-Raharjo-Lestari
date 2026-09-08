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

    // Manajemen pengguna internal (ketua/pengurus/petugas) — kelola dari portal pengurus
    Route::prefix('users')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('role:ketua,pengurus');
        Route::post('/', [UserController::class, 'store'])->middleware('role:ketua,pengurus');
        Route::get('/{id}', [UserController::class, 'show'])->middleware('role:ketua,pengurus');
        Route::put('/{id}', [UserController::class, 'update'])->middleware('role:ketua,pengurus');
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('role:ketua,pengurus');
    });

    // Master data keanggotaan
    Route::prefix('member-categories')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [MemberCategoryController::class, 'index'])->middleware('role:ketua,pengurus,petugas');
        Route::post('/', [MemberCategoryController::class, 'store'])->middleware('role:ketua,pengurus');
    });

    Route::prefix('members')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [MemberController::class, 'index'])->middleware('role:ketua,pengurus,petugas');
        Route::post('/', [MemberController::class, 'store'])->middleware('role:ketua,pengurus');
        // Own-check untuk role anggota ada di controller (show)
        Route::get('/{id}', [MemberController::class, 'show'])->middleware('role:ketua,pengurus,petugas,anggota');
        Route::put('/{id}', [MemberController::class, 'update'])->middleware('role:ketua,pengurus');
        Route::patch('/{id}/status', [MemberController::class, 'updateStatus'])->middleware('role:ketua,pengurus');
    });

    // Katalog sampah & manajemen harga
    Route::prefix('trash-categories')->group(function () {
        // Publik: katalog & papan harga
        Route::get('/', [TrashCategoryController::class, 'index']);
        Route::get('/board', [TrashCategoryController::class, 'board']);

        Route::post('/', [TrashCategoryController::class, 'store'])->middleware(['auth:sanctum', 'role:ketua,pengurus']);
        Route::get('/{id}', [TrashCategoryController::class, 'show'])->middleware('auth:sanctum');
        Route::put('/{id}', [TrashCategoryController::class, 'update'])->middleware(['auth:sanctum', 'role:ketua,pengurus']);
        Route::patch('/{id}/price', [TrashCategoryController::class, 'updatePrice'])->middleware(['auth:sanctum', 'role:ketua,pengurus']);
        Route::get('/{id}/price-history', [TrashCategoryController::class, 'priceHistory'])->middleware(['auth:sanctum', 'role:ketua,pengurus']);
    });

    // Operasional bank sampah: penjemputan & timbang
    Route::prefix('pickups')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [PickupController::class, 'index'])->middleware('role:ketua,pengurus,petugas,anggota');
        Route::post('/', [PickupController::class, 'store'])->middleware('role:ketua,pengurus,petugas,anggota');
        Route::post('/{id}/weigh-items', [PickupController::class, 'weighItems'])->middleware('role:ketua,pengurus,petugas');
        // Own-check untuk role anggota ada di controller (show/receipt)
        Route::get('/{id}', [PickupController::class, 'show'])->middleware('role:ketua,pengurus,petugas,anggota');
        Route::get('/{id}/receipt', [PickupController::class, 'receipt'])->middleware('role:ketua,pengurus,petugas,anggota');
        Route::patch('/{id}/cancel', [PickupController::class, 'cancel'])->middleware('role:ketua,pengurus');
    });

    // Pengaduan & komplain nota timbang
    Route::prefix('complaints')->middleware('auth:sanctum')->group(function () {
        // Anggota dapat submit & melihat miliknya (own-check di controller)
        Route::get('/', [ComplaintController::class, 'index'])->middleware('role:ketua,pengurus,petugas,anggota');
        Route::post('/', [ComplaintController::class, 'store'])->middleware('role:ketua,pengurus,petugas,anggota');
        Route::get('/{id}', [ComplaintController::class, 'show'])->middleware('role:ketua,pengurus,petugas,anggota');
        Route::patch('/{id}/resolve', [ComplaintController::class, 'resolve'])->middleware('role:ketua,pengurus');
    });

    // Keuangan koperasi, simpanan & billing rutin
    Route::prefix('finance-categories')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [FinanceCategoryController::class, 'index'])->middleware('role:ketua,pengurus');
        Route::post('/', [FinanceCategoryController::class, 'store'])->middleware('role:ketua');
    });

    Route::prefix('savings')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [SavingsController::class, 'index'])->middleware('role:ketua,pengurus,anggota');
        Route::post('/pay', [SavingsController::class, 'pay'])->middleware('role:ketua,pengurus');
        Route::get('/billing-status', [SavingsController::class, 'billingStatus'])->middleware('role:ketua,pengurus,anggota');
        Route::get('/wajib-overview', [SavingsController::class, 'wajibOverview'])->middleware('role:ketua,pengurus');
        Route::post('/generate-monthly-billing', [SavingsController::class, 'generateMonthlyBilling'])->middleware('role:ketua,pengurus');
    });

    Route::prefix('transactions')->middleware(['auth:sanctum', 'role:ketua,pengurus'])->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/', [TransactionController::class, 'store']);
    });

    // Dompet saldo anggota & penarikan tunai
    Route::prefix('wallet')->middleware('auth:sanctum')->group(function () {
        // Anggota: dompet sendiri (own-check di controller); pengurus: boleh query member
        Route::get('/summary', [WalletController::class, 'summary'])->middleware('role:ketua,pengurus,anggota');
        Route::get('/mutations', [WalletController::class, 'mutations'])->middleware('role:ketua,pengurus,anggota');
        Route::post('/withdraw', [WalletController::class, 'withdraw'])->middleware('role:anggota');
        Route::get('/withdraw-requests', [WalletController::class, 'withdrawRequests'])->middleware('role:ketua,pengurus');
        Route::patch('/withdraw-requests/{id}/approve', [WalletController::class, 'approveWithdrawal'])->middleware('role:ketua,pengurus');
    });

    // Mesin kalkulasi & pembagian SHU tahunan
    Route::prefix('shu')->middleware('auth:sanctum')->group(function () {
        Route::get('/periods', [ShuController::class, 'periods'])->middleware('role:ketua,pengurus');
        Route::post('/simulate', [ShuController::class, 'simulate'])->middleware('role:ketua,pengurus');
        Route::post('/publish', [ShuController::class, 'publish'])->middleware('role:ketua');
        Route::get('/my-history', [ShuController::class, 'myHistory'])->middleware('role:anggota');
    });

    // Logistik wilayah & rekapitulasi pengangkutan
    Route::prefix('logistics/routes')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [LogisticsController::class, 'index'])->middleware('role:ketua,pengurus,petugas');
        Route::post('/', [LogisticsController::class, 'store'])->middleware('role:ketua,pengurus,petugas');
    });

    // Laporan eksekutif & analitik
    Route::prefix('reports')->middleware(['auth:sanctum', 'role:ketua,pengurus'])->group(function () {
        Route::get('/dashboard-stats', [ReportController::class, 'dashboardStats']);
        Route::get('/financial', [ReportController::class, 'financial']);
        Route::get('/trash-volume', [ReportController::class, 'trashVolume']);
        Route::get('/export', [ReportController::class, 'export']);
    });
});

