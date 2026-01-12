<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Wyświetl szczegóły konta
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $account = $user->account;

        return response()->json([
            'account' => [
                'id' => $account->id,
                'account_number' => $user->account_number,
                'owner' => [
                    'name' => $user->full_name,
                    'email' => $user->email,
                ],
                'balance' => $account->balance,
                'formatted_balance' => $account->formatted_balance,
                'currency' => $account->currency,
                'account_type' => $account->account_type,
                'status' => $user->status,
                'opened_at' => $account->opened_at->format('Y-m-d H:i:s'),
                'created_at' => $account->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Sprawdź saldo
     */
    public function balance(Request $request)
    {
        $account = $request->user()->account;

        return response()->json([
            'balance' => [
                'amount' => $account->balance,
                'formatted' => $account->formatted_balance,
                'currency' => $account->currency,
            ],
        ]);
    }

    /**
     * Historia salda (salda po każdej transakcji)
     */
    public function balanceHistory(Request $request)
    {
        $user = $request->user();
        
        $transactions = \App\Models\Transaction::forUser($user->id)
            ->completed()
            ->orderBy('executed_at', 'desc')
            ->limit(50)
            ->get();

        $balanceHistory = $transactions->map(function ($transaction) use ($user) {
            $isSent = $transaction->sender_id === $user->id;
            
            return [
                'date' => $transaction->executed_at->format('Y-m-d H:i:s'),
                'transaction_number' => $transaction->transaction_number,
                'description' => $transaction->title,
                'change' => $isSent ? -$transaction->amount : $transaction->amount,
                'balance_after' => $isSent ? $transaction->sender_balance_after : $transaction->recipient_balance_after,
            ];
        });

        return response()->json([
            'balance_history' => $balanceHistory,
            'current_balance' => $user->account->balance,
        ]);
    }
}
