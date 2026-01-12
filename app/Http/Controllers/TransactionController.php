<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Wykonaj przelew
     */
    public function transfer(Request $request)
    {
        // Walidacja
        $validated = $request->validate([
            'recipient_account_number' => 'required|string|size:28|exists:users,account_number',
            'amount' => 'required|numeric|min:0.01',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $sender = $request->user();
        $senderAccount = $sender->account;

        // Sprawdzenie czy nie wysyła do samego siebie
        if ($sender->account_number === $validated['recipient_account_number']) {
            return response()->json([
                'message' => 'Nie możesz wykonać przelewu na swoje konto',
                'errors' => [
                    'recipient_account_number' => ['Nie można wykonać przelewu na własne konto'],
                ],
            ], 422);
        }

        // Znalezienie odbiorcy
        $recipient = User::where('account_number', $validated['recipient_account_number'])->first();
        
        if (!$recipient) {
            return response()->json([
                'message' => 'Nie znaleziono odbiorcy',
                'errors' => [
                    'recipient_account_number' => ['Podany numer konta nie istnieje'],
                ],
            ], 404);
        }

        // Sprawdzenie statusu odbiorcy
        if (!$recipient->isActive()) {
            return response()->json([
                'message' => 'Konto odbiorcy jest zablokowane',
                'errors' => [
                    'recipient_account_number' => ['Konto odbiorcy jest nieaktywne'],
                ],
            ], 422);
        }

        $recipientAccount = $recipient->account;
        $amount = $validated['amount'];

        // Sprawdzenie środków
        if (!$senderAccount->hasSufficientFunds($amount)) {
            return response()->json([
                'message' => 'Niewystarczające środki na koncie',
                'errors' => [
                    'amount' => ['Brak wystarczających środków do wykonania przelewu'],
                ],
                'current_balance' => $senderAccount->balance,
                'required_amount' => $amount,
            ], 422);
        }

        // Wykonanie transakcji w ramach transakcji bazodanowej
        DB::beginTransaction();

        try {
            // Zapisanie sald przed transakcją
            $senderBalanceBefore = $senderAccount->balance;
            $recipientBalanceBefore = $recipientAccount->balance;

            // Operacje na kontach
            $senderAccount->deductFunds($amount);
            $recipientAccount->addFunds($amount);

            // Utworzenie rekordu transakcji
            $transaction = Transaction::create([
                'sender_id' => $sender->id,
                'sender_account_number' => $sender->account_number,
                'recipient_id' => $recipient->id,
                'recipient_account_number' => $recipient->account_number,
                'amount' => $amount,
                'currency' => 'PLN',
                'title' => $validated['title'] ?? 'Przelew',
                'description' => $validated['description'] ?? null,
                'status' => 'completed',
                'type' => 'internal',
                'sender_balance_before' => $senderBalanceBefore,
                'sender_balance_after' => $senderAccount->balance,
                'recipient_balance_before' => $recipientBalanceBefore,
                'recipient_balance_after' => $recipientAccount->balance,
                'executed_at' => now(),
            ]);

            DB::commit();

            // Wczytanie relacji
            $transaction->load(['sender', 'recipient']);

            return response()->json([
                'message' => 'Przelew wykonany pomyślnie',
                'transaction' => [
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
                    'description' => $transaction->description,
                    'status' => $transaction->status,
                    'executed_at' => $transaction->executed_at->format('Y-m-d H:i:s'),
                    'balance_after' => $senderAccount->balance,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Wystąpił błąd podczas przelewu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Historia transakcji użytkownika
     */
    public function history(Request $request)
    {
        $user = $request->user();

        // Parametry paginacji
        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'executed_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Filtrowanie
        $query = Transaction::forUser($user->id)
            ->completed()
            ->with(['sender', 'recipient']);

        // Filtr po typie (wysłane/otrzymane)
        if ($request->has('type')) {
            if ($request->type === 'sent') {
                $query->where('sender_id', $user->id);
            } elseif ($request->type === 'received') {
                $query->where('recipient_id', $user->id);
            }
        }

        // Filtr po dacie
        if ($request->has('date_from')) {
            $query->where('executed_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('executed_at', '<=', $request->date_to);
        }

        // Sortowanie
        $query->orderBy($sortBy, $sortOrder);

        // Paginacja
        $transactions = $query->paginate($perPage);

        // Formatowanie wyników
        $formattedTransactions = $transactions->map(function ($transaction) use ($user) {
            $isSent = $transaction->sender_id === $user->id;

            return [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'type' => $isSent ? 'sent' : 'received',
                'counterparty' => [
                    'name' => $isSent ? $transaction->recipient->full_name : $transaction->sender->full_name,
                    'account_number' => $isSent ? $transaction->recipient_account_number : $transaction->sender_account_number,
                ],
                'amount' => $transaction->amount,
                'formatted_amount' => ($isSent ? '-' : '+') . ' ' . $transaction->formatted_amount,
                'title' => $transaction->title,
                'description' => $transaction->description,
                'status' => $transaction->status,
                'balance_before' => $isSent ? $transaction->sender_balance_before : $transaction->recipient_balance_before,
                'balance_after' => $isSent ? $transaction->sender_balance_after : $transaction->recipient_balance_after,
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
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
        ]);
    }

    /**
     * Szczegóły pojedynczej transakcji
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $transaction = Transaction::with(['sender', 'recipient'])
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->findOrFail($id);

        $isSent = $transaction->sender_id === $user->id;

        return response()->json([
            'transaction' => [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'type' => $isSent ? 'sent' : 'received',
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
                'currency' => $transaction->currency,
                'title' => $transaction->title,
                'description' => $transaction->description,
                'status' => $transaction->status,
                'balance_before' => $isSent ? $transaction->sender_balance_before : $transaction->recipient_balance_before,
                'balance_after' => $isSent ? $transaction->sender_balance_after : $transaction->recipient_balance_after,
                'executed_at' => $transaction->executed_at->format('Y-m-d H:i:s'),
                'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Statystyki transakcji
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        $sentTransactions = Transaction::where('sender_id', $user->id)
            ->completed()
            ->get();

        $receivedTransactions = Transaction::where('recipient_id', $user->id)
            ->completed()
            ->get();

        return response()->json([
            'statistics' => [
                'total_sent' => [
                    'count' => $sentTransactions->count(),
                    'amount' => $sentTransactions->sum('amount'),
                    'formatted_amount' => number_format($sentTransactions->sum('amount'), 2, ',', ' ') . ' PLN',
                ],
                'total_received' => [
                    'count' => $receivedTransactions->count(),
                    'amount' => $receivedTransactions->sum('amount'),
                    'formatted_amount' => number_format($receivedTransactions->sum('amount'), 2, ',', ' ') . ' PLN',
                ],
                'current_balance' => $user->account->balance,
                'formatted_balance' => $user->account->formatted_balance,
            ],
        ]);
    }
}
