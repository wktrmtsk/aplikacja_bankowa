<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Trasy API dla aplikacji bankowej
|
*/

// Publiczne trasy (bez autentykacji)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Chronione trasy (wymagają autentykacji)
Route::middleware('auth:sanctum')->group(function () {
    
    // === AUTENTYKACJA ===
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // === PROFIL UŻYTKOWNIKA ===
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('/change-password', [ProfileController::class, 'changePassword']);
        Route::delete('/', [ProfileController::class, 'destroy']);
    });

    // === KONTO BANKOWE ===
    Route::prefix('account')->group(function () {
        Route::get('/', [AccountController::class, 'show']);
        Route::get('/balance', [AccountController::class, 'balance']);
        Route::get('/balance-history', [AccountController::class, 'balanceHistory']);
    });

    // === TRANSAKCJE / PRZELEWY ===
    Route::prefix('transactions')->group(function () {
        Route::post('/transfer', [TransactionController::class, 'transfer']);
        Route::get('/', [TransactionController::class, 'history']);
        Route::get('/statistics', [TransactionController::class, 'statistics']);
        Route::get('/{id}', [TransactionController::class, 'show']);
    });
});

// Fallback dla nieistniejących tras
Route::fallback(function () {
    return response()->json([
        'message' => 'Endpoint nie został znaleziony',
    ], 404);
});
