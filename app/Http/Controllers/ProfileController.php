<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Wyświetl profil użytkownika
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $user->load('account');

        return response()->json([
            'profile' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
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
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'account' => [
                    'balance' => $user->account->balance,
                    'formatted_balance' => $user->account->formatted_balance,
                    'currency' => $user->account->currency,
                    'account_type' => $user->account->account_type,
                    'opened_at' => $user->account->opened_at->format('Y-m-d'),
                ],
            ],
        ]);
    }

    /**
     * Aktualizuj dane profilu
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Walidacja
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:255',
            'postal_code' => 'sometimes|nullable|string|max:10',
            'country' => 'sometimes|nullable|string|max:255',
        ]);

        // Aktualizacja danych
        $user->update($validated);

        // Przeładowanie relacji
        $user->load('account');

        return response()->json([
            'message' => 'Profil został zaktualizowany pomyślnie',
            'profile' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
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
        ]);
    }

    /**
     * Zmiana hasła
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Sprawdzenie aktualnego hasła
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Aktualne hasło jest nieprawidłowe',
                'errors' => [
                    'current_password' => ['Podane hasło jest nieprawidłowe'],
                ],
            ], 422);
        }

        // Aktualizacja hasła
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Hasło zostało zmienione pomyślnie',
        ]);
    }

    /**
     * Usuń konto użytkownika
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        $user = $request->user();

        // Weryfikacja hasła
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Hasło jest nieprawidłowe',
                'errors' => [
                    'password' => ['Podane hasło jest nieprawidłowe'],
                ],
            ], 422);
        }

        // Sprawdzenie salda (opcjonalnie - można wymagać zerowego salda)
        if ($user->account->balance > 0) {
            return response()->json([
                'message' => 'Nie można usunąć konta z środkami. Proszę najpierw przelać wszystkie środki.',
                'errors' => [
                    'balance' => ['Saldo konta musi wynosić 0.00 PLN przed usunięciem'],
                ],
            ], 422);
        }

        // Usunięcie konta
        $user->delete();

        return response()->json([
            'message' => 'Konto zostało usunięte pomyślnie',
        ]);
    }
}
