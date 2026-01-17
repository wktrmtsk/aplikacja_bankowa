<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
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

    // === PANEL ADMINISTRATORA ===
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        // Dashboard i statystyki
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/transactions/stats', [AdminController::class, 'transactionStats']);
        Route::get('/monthly-report', [AdminController::class, 'monthlyReport']);
        
        // Wszyscy użytkownicy
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{id}', [AdminController::class, 'userDetails']);
        Route::post('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
        
        // Zarządzanie pracownikami
        Route::get('/employees', [AdminController::class, 'employees']);
        Route::post('/employees', [AdminController::class, 'createEmployee']);
        Route::put('/employees/{id}', [AdminController::class, 'updateEmployee']);
        Route::delete('/employees/{id}', [AdminController::class, 'deleteEmployee']);
        Route::post('/employees/{id}/assign-clients', [AdminController::class, 'assignClients']);
        Route::delete('/employees/{employeeId}/clients/{clientId}', [AdminController::class, 'detachClient']);
        
        // Zarządzanie klientami
        Route::get('/clients', [AdminController::class, 'clients']);
        Route::post('/clients', [AdminController::class, 'createClient']);
        Route::put('/clients/{id}', [AdminController::class, 'updateClient']);
        Route::delete('/clients/{id}', [AdminController::class, 'deleteClient']);
        Route::post('/clients/{id}/adjust-balance', [AdminController::class, 'adjustBalance']);
        Route::get('/clients-without-employees', [AdminController::class, 'clientsWithoutEmployees']);
        
        // Wszystkie transakcje
        Route::get('/transactions', [AdminController::class, 'allTransactions']);
    });

    // === PANEL PRACOWNIKA ===
    Route::prefix('employee')->middleware('role:employee')->group(function () {
        Route::get('/dashboard', [EmployeeController::class, 'dashboard']);
        Route::get('/clients', [EmployeeController::class, 'myClients']);
        Route::get('/clients/{id}', [EmployeeController::class, 'clientDetails']);
        Route::put('/clients/{id}', [EmployeeController::class, 'updateClient']);
        Route::post('/clients/{id}/toggle-status', [EmployeeController::class, 'toggleClientStatus']);
        Route::get('/transactions', [EmployeeController::class, 'clientTransactions']);
        
        // Wpłaty i przelewy dla klientów
        Route::post('/clients/{id}/deposit', [EmployeeController::class, 'depositForClient']);
        Route::post('/clients/{id}/transfer', [EmployeeController::class, 'transferForClient']);
    });
});

// Fallback dla nieistniejących tras
Route::fallback(function () {
    return response()->json([
        'message' => 'Endpoint nie został znaleziony',
    ], 404);
});
