<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\MemberCategoryController;
use App\Http\Controllers\Api\v1\MemberController;
use App\Http\Controllers\Api\v1\PickupController;
use App\Http\Controllers\Api\v1\TrashCategoryController;
use App\Http\Controllers\Api\v1\UserController;
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

    // Manajemen pengguna internal (ketua/pengurus/petugas)
    Route::prefix('users')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('role:ketua,pengurus');
        Route::post('/', [UserController::class, 'store'])->middleware('role:ketua');
        Route::get('/{id}', [UserController::class, 'show'])->middleware('role:ketua,pengurus');
        Route::put('/{id}', [UserController::class, 'update'])->middleware('role:ketua');
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('role:ketua');
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
        Route::get('/', [PickupController::class, 'index'])->middleware('role:ketua,pengurus,petugas');
        Route::post('/', [PickupController::class, 'store'])->middleware('role:ketua,pengurus,petugas,anggota');
        Route::post('/{id}/weigh-items', [PickupController::class, 'weighItems'])->middleware('role:ketua,pengurus,petugas');
        // Own-check untuk role anggota ada di controller (show/receipt)
        Route::get('/{id}', [PickupController::class, 'show'])->middleware('role:ketua,pengurus,petugas,anggota');
        Route::get('/{id}/receipt', [PickupController::class, 'receipt'])->middleware('role:ketua,pengurus,petugas,anggota');
        Route::patch('/{id}/cancel', [PickupController::class, 'cancel'])->middleware('role:ketua,pengurus');
    });
});

