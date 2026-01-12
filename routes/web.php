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
});
