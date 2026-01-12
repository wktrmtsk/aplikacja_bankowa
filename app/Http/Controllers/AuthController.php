<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Rejestracja nowego użytkownika
     */
    public function register(Request $request)
    {
        // Walidacja danych wejściowych
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'pesel' => 'required|string|size:11|unique:users',
            'birth_date' => 'required|date|before:today',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:255',
        ]);

        // Tworzenie użytkownika
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'pesel' => $validated['pesel'],
            'birth_date' => $validated['birth_date'],
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'country' => $validated['country'] ?? 'Polska',
            'status' => 'active',
        ]);

        // Wczytanie relacji konta
        $user->load('account');

        // Generowanie tokenu API (jeśli używasz Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Rejestracja zakończona pomyślnie',
            'user' => [
                'id' => $user->id,
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
                'account' => [
                    'balance' => $user->account->balance,
                    'currency' => $user->account->currency,
                ],
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Logowanie użytkownika
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Sprawdzenie danych logowania
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Podane dane logowania są nieprawidłowe.'],
            ]);
        }

        $user = Auth::user();

        // Sprawdzenie statusu konta
        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Twoje konto zostało zablokowane.'],
            ]);
        }

        // Wczytanie relacji
        $user->load('account');

        // Generowanie tokenu
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Zalogowano pomyślnie',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'account_number' => $user->account_number,
                'account' => [
                    'balance' => $user->account->balance,
                    'currency' => $user->account->currency,
                ],
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Wylogowanie użytkownika
     */
    public function logout(Request $request)
    {
        // Usunięcie aktualnego tokenu
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Wylogowano pomyślnie',
        ]);
    }

    /**
     * Pobranie aktualnie zalogowanego użytkownika
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('account');

        return response()->json([
            'user' => [
                'id' => $user->id,
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
                'account' => [
                    'balance' => $user->account->balance,
                    'currency' => $user->account->currency,
                    'account_type' => $user->account->account_type,
                ],
            ],
        ]);
    }
}
