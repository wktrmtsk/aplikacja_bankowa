<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Dashboard pracownika
     */
    public function dashboard(Request $request)
    {
        $employee = $request->user();
        
        $stats = [
            'my_clients_count' => $employee->clients()->count(),
            'active_clients' => $employee->clients()->where('status', 'active')->count(),
            'blocked_clients' => $employee->clients()->where('status', 'blocked')->count(),
            'clients_total_balance' => $employee->clients()
                ->join('accounts', 'users.id', '=', 'accounts.user_id')
                ->sum('accounts.balance'),
        ];

        return response()->json([
            'employee' => [
                'name' => $employee->full_name,
                'email' => $employee->email,
            ],
            'statistics' => $stats,
        ]);
    }

    /**
     * Lista klientów przypisanych do pracownika
     */
    public function myClients(Request $request)
    {
        $employee = $request->user();
        $perPage = $request->input('per_page', 15);
        $status = $request->input('status');
        $search = $request->input('search');

        $query = $employee->clients()->with('account');

        // Filtrowanie po statusie
        if ($status) {
            $query->where('status', $status);
        }

        // Wyszukiwanie
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
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
                'balance' => $client->account->balance,
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
     * Szczegóły klienta
     */
    public function clientDetails(Request $request, $id)
    {
        $employee = $request->user();
        
        // Sprawdź czy klient jest przypisany do tego pracownika
        $client = $employee->clients()->with(['account', 'sentTransactions', 'receivedTransactions'])
            ->findOrFail($id);

        $recentTransactions = Transaction::where('sender_id', $id)
            ->orWhere('recipient_id', $id)
            ->orderBy('executed_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'client' => [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'email' => $client->email,
                'account_number' => $client->account_number,
                'phone' => $client->phone,
                'pesel' => $client->pesel,
                'birth_date' => $client->birth_date->format('Y-m-d'),
                'address' => $client->address,
                'city' => $client->city,
                'postal_code' => $client->postal_code,
                'status' => $client->status,
                'account' => [
                    'balance' => $client->account->balance,
                    'currency' => $client->account->currency,
                ],
                'statistics' => [
                    'sent_count' => $client->sentTransactions->count(),
                    'sent_amount' => $client->sentTransactions->sum('amount'),
                    'received_count' => $client->receivedTransactions->count(),
                    'received_amount' => $client->receivedTransactions->sum('amount'),
                ],
            ],
            'recent_transactions' => $recentTransactions,
        ]);
    }

    /**
     * Aktualizuj dane klienta
     */
    public function updateClient(Request $request, $id)
    {
        $employee = $request->user();
        
        // Sprawdź czy klient jest przypisany do tego pracownika
        $client = $employee->clients()->findOrFail($id);

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
            'message' => 'Dane klienta zostały zaktualizowane',
            'client' => [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
            ],
        ]);
    }

    /**
     * Blokuj/Odblokuj klienta
     */
    public function toggleClientStatus(Request $request, $id)
    {
        $employee = $request->user();
        
        // Sprawdź czy klient jest przypisany do tego pracownika
        $client = $employee->clients()->findOrFail($id);

        $newStatus = $client->status === 'active' ? 'blocked' : 'active';
        $client->update(['status' => $newStatus]);

        return response()->json([
            'message' => $newStatus === 'blocked' ? 'Klient został zablokowany' : 'Klient został odblokowany',
            'client' => [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'status' => $client->status,
            ],
        ]);
    }

    /**
     * Transakcje klientów pracownika
     */
    public function clientTransactions(Request $request)
    {
        $employee = $request->user();
        $perPage = $request->input('per_page', 20);
        $clientId = $request->input('client_id');

        $clientIds = $employee->clients()->pluck('users.id');

        $query = Transaction::with(['sender', 'recipient'])
            ->where(function($q) use ($clientIds) {
                $q->whereIn('sender_id', $clientIds)
                  ->orWhereIn('recipient_id', $clientIds);
            })
            ->completed();

        // Filtr po konkretnym kliencie
        if ($clientId && $clientIds->contains($clientId)) {
            $query->where(function($q) use ($clientId) {
                $q->where('sender_id', $clientId)
                  ->orWhere('recipient_id', $clientId);
            });
        }

        $transactions = $query->latest('executed_at')->paginate($perPage);

        $formattedTransactions = $transactions->map(function($transaction) use ($clientIds) {
            $isMySender = $clientIds->contains($transaction->sender_id);
            $isMyRecipient = $clientIds->contains($transaction->recipient_id);

            return [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'sender' => [
                    'name' => $transaction->sender->full_name,
                    'is_my_client' => $isMySender,
                ],
                'recipient' => [
                    'name' => $transaction->recipient->full_name,
                    'is_my_client' => $isMyRecipient,
                ],
                'amount' => $transaction->amount,
                'title' => $transaction->title,
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
     * Wpłata środków na konto klienta
     */
    public function depositForClient(Request $request, $clientId)
    {
        $employee = $request->user();
        
        // Sprawdź czy klient jest przypisany do pracownika
        $client = $employee->clients()->findOrFail($clientId);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $oldBalance = $client->account->balance;
        $newBalance = $oldBalance + $validated['amount'];

        $client->account->update(['balance' => $newBalance]);

        // Zapisz w logach/historii - możesz dodać specjalną tabelę dla wpłat
        \Log::info("Employee deposit", [
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'client_id' => $client->id,
            'client_name' => $client->full_name,
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
        ]);

        return response()->json([
            'message' => 'Wpłata została zrealizowana pomyślnie',
            'client' => [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'account_number' => $client->account_number,
            ],
            'deposit' => [
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
            ],
        ]);
    }

    /**
     * Przelew w imieniu klienta
     */
    public function transferForClient(Request $request, $clientId)
    {
        $employee = $request->user();
        
        // Sprawdź czy klient jest przypisany do pracownika
        $client = $employee->clients()->findOrFail($clientId);

        $validated = $request->validate([
            'recipient_account_number' => 'required|string|size:28',
            'amount' => 'required|numeric|min:0.01',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        // Znajdź odbiorcę
        $recipient = User::where('account_number', $validated['recipient_account_number'])->first();
        
        if (!$recipient) {
            return response()->json([
                'message' => 'Odbiorca o podanym numerze konta nie został znaleziony',
            ], 404);
        }

        if ($recipient->id === $client->id) {
            return response()->json([
                'message' => 'Nie można wykonać przelewu na własne konto',
            ], 400);
        }

        // Sprawdź saldo
        if ($client->account->balance < $validated['amount']) {
            return response()->json([
                'message' => 'Niewystarczające środki na koncie klienta',
                'current_balance' => $client->account->balance,
                'required_amount' => $validated['amount'],
            ], 400);
        }

        // Sprawdź status klienta
        if ($client->status !== 'active') {
            return response()->json([
                'message' => 'Konto klienta jest zablokowane',
            ], 403);
        }

        // Wykonaj przelew
        $senderAccount = $client->account;
        $recipientAccount = $recipient->account;

        $senderBalanceBefore = $senderAccount->balance;
        $recipientBalanceBefore = $recipientAccount->balance;

        // Odejmij środki od nadawcy
        $senderAccount->deductFunds($validated['amount']);

        // Dodaj środki odbiorcy
        $recipientAccount->addFunds($validated['amount']);

        // Utwórz rekord transakcji
        $transaction = Transaction::create([
            'sender_id' => $client->id,
            'sender_account_number' => $client->account_number,
            'recipient_id' => $recipient->id,
            'recipient_account_number' => $recipient->account_number,
            'amount' => $validated['amount'],
            'currency' => 'PLN',
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'completed',
            'type' => 'internal',
            'sender_balance_before' => $senderBalanceBefore,
            'sender_balance_after' => $senderAccount->balance,
            'recipient_balance_before' => $recipientBalanceBefore,
            'recipient_balance_after' => $recipientAccount->balance,
            'executed_at' => now(),
        ]);

        // Zapisz w logach kto wykonał przelew
        \Log::info("Employee transfer", [
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'transaction_id' => $transaction->id,
            'client_id' => $client->id,
            'client_name' => $client->full_name,
        ]);

        return response()->json([
            'message' => 'Przelew został zrealizowany pomyślnie',
            'transaction' => [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'amount' => $transaction->formatted_amount,
                'sender' => [
                    'name' => $client->full_name,
                    'account_number' => $client->account_number,
                    'new_balance' => $senderAccount->balance,
                ],
                'recipient' => [
                    'name' => $recipient->full_name,
                    'account_number' => $recipient->account_number,
                ],
                'executed_at' => $transaction->executed_at->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }
}
