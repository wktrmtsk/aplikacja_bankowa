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

    /**
     * Panel administratora - Dashboard
     */
    public function adminDashboard()
    {
        return view('admin.dashboard');
    }

    /**
     * Panel administratora - Pracownicy
     */
    public function adminEmployees()
    {
        return view('admin.employees');
    }

    /**
     * Panel administratora - Klienci
     */
    public function adminClients()
    {
        return view('admin.clients');
    }

    /**
     * Panel administratora - Transakcje
     */
    public function adminTransactions()
    {
        return view('admin.transactions');
    }

    /**
     * Panel administratora - Raporty
     */
    public function adminReports()
    {
        return view('admin.reports');
    }

    /**
     * Panel pracownika - Dashboard
     */
    public function employeeDashboard()
    {
        return view('employee.dashboard');
    }

    /**
     * Panel pracownika - Klienci
     */
    public function employeeClients()
    {
        return view('employee.clients');
    }

    /**
     * Panel pracownika - Szczegóły klienta
     */
    public function employeeClientDetails($id)
    {
        return view('employee.client-details', ['clientId' => $id]);
    }

    /**
     * Panel pracownika - Wpłaty
     */
    public function employeeDeposit()
    {
        return view('employee.deposit');
    }

    /**
     * Panel pracownika - Przelewy
     */
    public function employeeTransfer()
    {
        return view('employee.transfer');
    }

    /**
     * Panel pracownika - Transakcje
     */
    public function employeeTransactions()
    {
        return view('employee.transactions');
    }
}
