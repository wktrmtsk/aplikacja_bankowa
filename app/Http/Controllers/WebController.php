<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebController extends Controller
{
    /**
     * Strona główna - przekierowanie do logowania
     */
    public function index()
    {
        return redirect()->route('login.view');
    }

    /**
     * Widok logowania
     */
    public function loginView()
    {
        return view('auth.login');
    }

    /**
     * Widok rejestracji
     */
    public function registerView()
    {
        return view('auth.register');
    }

    /**
     * Dashboard - panel główny
     */
    public function dashboard()
    {
        return view('dashboard');
    }

    /**
     * Widok profilu
     */
    public function profile()
    {
        return view('profile');
    }

    /**
     * Widok przelewów
     */
    public function transfer()
    {
        return view('transfer');
    }

    /**
     * Widok historii transakcji
     */
    public function transactions()
    {
        return view('transactions');
    }
}
