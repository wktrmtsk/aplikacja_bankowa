<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Dashboard - statystyki systemu
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_clients' => User::whereHas('roles', function($q) {
                $q->where('name', 'client');
            })->count(),
            'total_employees' => User::whereHas('roles', function($q) {
                $q->where('name', 'employee');
            })->count(),
            'total_transactions' => Transaction::count(),
            'total_transaction_value' => Transaction::sum('amount'),
            'active_users' => User::where('status', 'active')->count(),
            'blocked_users' => User::where('status', 'blocked')->count(),
            'total_balance' => DB::table('accounts')->sum('balance'),
        ];

        return response()->json([
            'statistics' => $stats,
        ]);
    }

    /**
     * Lista wszystkich użytkowników
     */
    public function users(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $role = $request->input('role'); // Filtr po roli
        $status = $request->input('status'); // Filtr po statusie
        $search = $request->input('search'); // Wyszukiwanie

        $query = User::with(['roles', 'account']);

        // Filtrowanie po roli
        if ($role) {
            $query->whereHas('roles', function($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Filtrowanie po statusie
        if ($status) {
            $query->where('status', $status);
        }

        // Wyszukiwanie
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($perPage);

        $formattedUsers = $users->map(function($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'account_number' => $user->account_number,
                'phone' => $user->phone,
                'status' => $user->status,
                'roles' => $user->roles->pluck('display_name'),
                'balance' => $user->account->balance ?? 0,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'users' => $formattedUsers,
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Szczegóły użytkownika
     */
    public function userDetails($id)
    {
        $user = User::with(['roles', 'account', 'sentTransactions', 'receivedTransactions'])
            ->findOrFail($id);

        $recentTransactions = Transaction::where('sender_id', $id)
            ->orWhere('recipient_id', $id)
            ->orderBy('executed_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'account_number' => $user->account_number,
                'phone' => $user->phone,
                'pesel' => $user->pesel,
                'birth_date' => $user->birth_date->format('Y-m-d'),
                'address' => $user->address,
                'city' => $user->city,
                'postal_code' => $user->postal_code,
                'country' => $user->country,
                'status' => $user->status,
                'roles' => $user->roles,
                'account' => [
                    'balance' => $user->account->balance,
                    'currency' => $user->account->currency,
                    'account_type' => $user->account->account_type,
                ],
                'statistics' => [
                    'sent_count' => $user->sentTransactions->count(),
                    'sent_amount' => $user->sentTransactions->sum('amount'),
                    'received_count' => $user->receivedTransactions->count(),
                    'received_amount' => $user->receivedTransactions->sum('amount'),
                ],
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ],
            'recent_transactions' => $recentTransactions,
        ]);
    }

    /**
     * Blokuj/Odblokuj użytkownika
     */
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);

        // Nie można zablokować admina
        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Nie można zablokować administratora',
            ], 403);
        }

        $newStatus = $user->status === 'active' ? 'blocked' : 'active';
        $user->update(['status' => $newStatus]);

        return response()->json([
            'message' => $newStatus === 'blocked' ? 'Użytkownik został zablokowany' : 'Użytkownik został odblokowany',
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'status' => $user->status,
            ],
        ]);
    }

    /**
     * Wszystkie transakcje w systemie
     */
    public function allTransactions(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $minAmount = $request->input('min_amount');
        $maxAmount = $request->input('max_amount');

        $query = Transaction::with(['sender', 'recipient'])->completed();

        // Filtry
        if ($dateFrom) {
            $query->where('executed_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('executed_at', '<=', $dateTo);
        }

        if ($minAmount) {
            $query->where('amount', '>=', $minAmount);
        }

        if ($maxAmount) {
            $query->where('amount', '<=', $maxAmount);
        }

        $transactions = $query->latest('executed_at')->paginate($perPage);

        $formattedTransactions = $transactions->map(function($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'sender' => [
                    'name' => $transaction->sender->full_name,
                    'account_number' => $transaction->sender_account_number,
                ],
                'recipient' => [
                    'name' => $transaction->recipient->full_name,
                    'account_number' => $transaction->recipient_account_number,
                ],
                'amount' => $transaction->amount,
                'formatted_amount' => $transaction->formatted_amount,
                'title' => $transaction->title,
                'status' => $transaction->status,
                'executed_at' => $transaction->executed_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'transactions' => $formattedTransactions,
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    /**
     * Statystyki transakcji
     */
    public function transactionStats()
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        $thisYear = now()->startOfYear();

        $stats = [
            'today' => [
                'count' => Transaction::where('executed_at', '>=', $today)->count(),
                'volume' => Transaction::where('executed_at', '>=', $today)->sum('amount'),
            ],
            'this_month' => [
                'count' => Transaction::where('executed_at', '>=', $thisMonth)->count(),
                'volume' => Transaction::where('executed_at', '>=', $thisMonth)->sum('amount'),
            ],
            'this_year' => [
                'count' => Transaction::where('executed_at', '>=', $thisYear)->count(),
                'volume' => Transaction::where('executed_at', '>=', $thisYear)->sum('amount'),
            ],
            'all_time' => [
                'count' => Transaction::count(),
                'volume' => Transaction::sum('amount'),
                'average' => Transaction::avg('amount'),
            ],
        ];

        return response()->json(['statistics' => $stats]);
    }

    // ========================================
    // ZARZĄDZANIE PRACOWNIKAMI
    // ========================================

    /**
     * Lista wszystkich pracowników
     */
    public function employees(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $query = User::whereHas('roles', function($q) {
            $q->where('name', 'employee');
        })->with(['account', 'clients']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = $query->latest()->paginate($perPage);

        $formattedEmployees = $employees->map(function($employee) {
            return [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'status' => $employee->status,
                'clients_count' => $employee->clients->count(),
                'balance' => $employee->account->balance ?? 0,
                'created_at' => $employee->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'employees' => $formattedEmployees,
            'pagination' => [
                'total' => $employees->total(),
                'per_page' => $employees->perPage(),
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
            ],
        ]);
    }

    /**
     * Utwórz nowego pracownika
     */
    public function createEmployee(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'pesel' => 'required|string|size:11|unique:users,pesel',
            'birth_date' => 'required|date',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['status'] = 'active';
        $validated['country'] = $validated['country'] ?? 'Polska';

        $employee = User::create($validated);
        $employee->assignRole('employee');

        return response()->json([
            'message' => 'Pracownik został utworzony pomyślnie',
            'employee' => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'account_number' => $employee->account_number,
            ],
        ], 201);
    }

    /**
     * Edytuj pracownika
     */
    public function updateEmployee(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        if (!$employee->isEmployee()) {
            return response()->json(['message' => 'Użytkownik nie jest pracownikiem'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:255',
            'postal_code' => 'sometimes|nullable|string|max:10',
        ]);

        $employee->update($validated);

        return response()->json([
            'message' => 'Pracownik został zaktualizowany',
            'employee' => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
            ],
        ]);
    }

    /**
     * Usuń pracownika
     */
    public function deleteEmployee($id)
    {
        $employee = User::findOrFail($id);

        if (!$employee->isEmployee()) {
            return response()->json(['message' => 'Użytkownik nie jest pracownikiem'], 403);
        }

        // Odepnij klientów przed usunięciem
        $employee->clients()->detach();
        
        $employee->delete();

        return response()->json([
            'message' => 'Pracownik został usunięty',
        ]);
    }

    /**
     * Przypisz klientów do pracownika
     */
    public function assignClients(Request $request, $employeeId)
    {
        $validated = $request->validate([
            'client_ids' => 'required|array',
            'client_ids.*' => 'exists:users,id',
        ]);

        $employee = User::findOrFail($employeeId);

        if (!$employee->isEmployee()) {
            return response()->json(['message' => 'Użytkownik nie jest pracownikiem'], 403);
        }

        // Sprawdź czy wszyscy są klientami
        $clients = User::whereIn('id', $validated['client_ids'])->get();
        
        foreach ($clients as $client) {
            if (!$client->isClient()) {
                return response()->json([
                    'message' => "Użytkownik {$client->full_name} nie jest klientem"
                ], 400);
            }
        }

        // Przypisz klientów (sync - zastępuje istniejące)
        $employee->clients()->sync($validated['client_ids']);

        return response()->json([
            'message' => 'Klienci zostali przypisani do pracownika',
            'assigned_count' => count($validated['client_ids']),
        ]);
    }

    /**
     * Odepnij klienta od pracownika
     */
    public function detachClient($employeeId, $clientId)
    {
        $employee = User::findOrFail($employeeId);
        $employee->clients()->detach($clientId);

        return response()->json([
            'message' => 'Klient został odłączony od pracownika',
        ]);
    }

    // ========================================
    // ZARZĄDZANIE KLIENTAMI
    // ========================================

    /**
     * Lista wszystkich klientów
     */
    public function clients(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $hasEmployee = $request->input('has_employee'); // true/false

        $query = User::whereHas('roles', function($q) {
            $q->where('name', 'client');
        })->with(['account', 'employees']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        if ($hasEmployee !== null) {
            if ($hasEmployee === 'true' || $hasEmployee === true) {
                $query->has('employees');
            } else {
                $query->doesntHave('employees');
            }
        }

        $clients = $query->latest()->paginate($perPage);

        $formattedClients = $clients->map(function($client) {
            return [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'account_number' => $client->account_number,
                'phone' => $client->phone,
                'status' => $client->status,
                'balance' => $client->account->balance ?? 0,
                'employees' => $client->employees->map(fn($e) => [
                    'id' => $e->id,
                    'name' => $e->full_name
                ]),
                'created_at' => $client->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'clients' => $formattedClients,
            'pagination' => [
                'total' => $clients->total(),
                'per_page' => $clients->perPage(),
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
            ],
        ]);
    }

    /**
     * Utwórz nowego klienta
     */
    public function createClient(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'pesel' => 'required|string|size:11|unique:users,pesel',
            'birth_date' => 'required|date',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'initial_balance' => 'nullable|numeric|min:0',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['status'] = 'active';
        $validated['country'] = $validated['country'] ?? 'Polska';

        $initialBalance = $validated['initial_balance'] ?? 0;
        unset($validated['initial_balance']);

        $client = User::create($validated);
        $client->assignRole('client');

        // Ustaw początkowe saldo
        if ($initialBalance > 0) {
            $client->account->update(['balance' => $initialBalance]);
        }

        return response()->json([
            'message' => 'Klient został utworzony pomyślnie',
            'client' => [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'account_number' => $client->account_number,
                'balance' => $client->account->balance,
            ],
        ], 201);
    }

    /**
     * Edytuj klienta
     */
    public function updateClient(Request $request, $id)
    {
        $client = User::findOrFail($id);

        if (!$client->isClient()) {
            return response()->json(['message' => 'Użytkownik nie jest klientem'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:255',
            'postal_code' => 'sometimes|nullable|string|max:10',
        ]);

        $client->update($validated);

        return response()->json([
            'message' => 'Klient został zaktualizowany',
            'client' => [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'email' => $client->email,
            ],
        ]);
    }

    /**
     * Usuń klienta
     */
    public function deleteClient($id)
    {
        $client = User::findOrFail($id);

        if (!$client->isClient()) {
            return response()->json(['message' => 'Użytkownik nie jest klientem'], 403);
        }

        // Sprawdź czy ma saldo
        if ($client->account && $client->account->balance > 0) {
            return response()->json([
                'message' => 'Nie można usunąć klienta z saldem większym niż 0',
                'balance' => $client->account->balance,
            ], 400);
        }

        $client->delete();

        return response()->json([
            'message' => 'Klient został usunięty',
        ]);
    }

    /**
     * Zmień saldo klienta (admin adjustment)
     */
    public function adjustBalance(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'description' => 'required|string|max:255',
        ]);

        $client = User::findOrFail($id);

        if (!$client->isClient()) {
            return response()->json(['message' => 'Użytkownik nie jest klientem'], 403);
        }

        $oldBalance = $client->account->balance;
        $newBalance = $oldBalance + $validated['amount'];

        if ($newBalance < 0) {
            return response()->json([
                'message' => 'Saldo nie może być ujemne',
            ], 400);
        }

        $client->account->update(['balance' => $newBalance]);

        return response()->json([
            'message' => 'Saldo zostało zaktualizowane',
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
            'adjustment' => $validated['amount'],
        ]);
    }

    // ========================================
    // RAPORTY I STATYSTYKI
    // ========================================

    /**
     * Raport miesięczny
     */
    public function monthlyReport(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $startDate = \Carbon\Carbon::parse($month)->startOfMonth();
        $endDate = \Carbon\Carbon::parse($month)->endOfMonth();

        $data = [
            'period' => $month,
            'new_users' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'new_clients' => User::whereHas('roles', fn($q) => $q->where('name', 'client'))
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'transactions' => Transaction::whereBetween('executed_at', [$startDate, $endDate])
                ->count(),
            'transaction_volume' => Transaction::whereBetween('executed_at', [$startDate, $endDate])
                ->sum('amount'),
            'active_users' => User::where('status', 'active')
                ->whereHas('sentTransactions', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('executed_at', [$startDate, $endDate]);
                })->count(),
        ];

        return response()->json(['report' => $data]);
    }

    /**
     * Klienci bez pracowników
     */
    public function clientsWithoutEmployees()
    {
        $clients = User::whereHas('roles', function($q) {
            $q->where('name', 'client');
        })->doesntHave('employees')
          ->with('account')
          ->get();

        $formatted = $clients->map(function($client) {
            return [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'account_number' => $client->account_number,
                'balance' => $client->account->balance,
                'created_at' => $client->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'clients' => $formatted,
            'count' => $formatted->count(),
        ]);
    }
}
