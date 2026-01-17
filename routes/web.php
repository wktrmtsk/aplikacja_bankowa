<?php

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Strona główna
Route::get('/', [WebController::class, 'index'])->name('home');

// Autentykacja
Route::get('/login', [WebController::class, 'loginView'])->name('login.view');
Route::get('/register', [WebController::class, 'registerView'])->name('register.view');

// Chronione widoki (wymagają zalogowania)
Route::middleware(['web'])->group(function () {
    Route::get('/dashboard', [WebController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [WebController::class, 'profile'])->name('profile');
    Route::get('/transfer', [WebController::class, 'transfer'])->name('transfer');
    Route::get('/transactions', [WebController::class, 'transactions'])->name('transactions');
    
    // Panel Administratora
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [WebController::class, 'adminDashboard'])->name('admin.dashboard');
        Route::get('/employees', [WebController::class, 'adminEmployees'])->name('admin.employees');
        Route::get('/clients', [WebController::class, 'adminClients'])->name('admin.clients');
        Route::get('/all-transactions', [WebController::class, 'adminTransactions'])->name('admin.transactions');
        Route::get('/reports', [WebController::class, 'adminReports'])->name('admin.reports');
    });
    
    // Panel Pracownika
    Route::prefix('employee')->group(function () {
        Route::get('/dashboard', [WebController::class, 'employeeDashboard'])->name('employee.dashboard');
        Route::get('/clients', [WebController::class, 'employeeClients'])->name('employee.clients');
        Route::get('/clients/{id}', [WebController::class, 'employeeClientDetails'])->name('employee.client.details');
        Route::get('/deposit', [WebController::class, 'employeeDeposit'])->name('employee.deposit');
        Route::get('/transfer', [WebController::class, 'employeeTransfer'])->name('employee.transfer');
        Route::get('/transactions', [WebController::class, 'employeeTransactions'])->name('employee.transactions');
    });
});
