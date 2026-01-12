<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankingApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test rejestracji użytkownika
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'email' => 'jan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'pesel' => '90010112345',
            'birth_date' => '1990-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'account_number',
                    'account' => [
                        'balance',
                        'currency',
                    ],
                ],
                'access_token',
                'token_type',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jan@example.com',
        ]);
    }

    /**
     * Test logowania użytkownika
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'access_token',
                'token_type',
            ]);
    }

    /**
     * Test nieprawidłowego logowania
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test pobrania profilu zalogowanego użytkownika
     */
    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'profile' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'account_number',
                    'account',
                ],
            ]);
    }

    /**
     * Test aktualizacji profilu
     */
    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/profile', [
            'first_name' => 'Nowe',
            'last_name' => 'Nazwisko',
            'phone' => '+48999888777',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Nowe',
            'last_name' => 'Nazwisko',
            'phone' => '+48999888777',
        ]);
    }

    /**
     * Test wykonania przelewu
     */
    public function test_user_can_make_transfer(): void
    {
        // Tworzenie nadawcy z środkami
        $sender = User::factory()->create();
        $sender->account->update(['balance' => 1000.00]);
        $token = $sender->createToken('test-token')->plainTextToken;

        // Tworzenie odbiorcy
        $recipient = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/transactions/transfer', [
            'recipient_account_number' => $recipient->account_number,
            'amount' => 500.00,
            'title' => 'Test transfer',
            'description' => 'Test description',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'transaction' => [
                    'id',
                    'transaction_number',
                    'sender',
                    'recipient',
                    'amount',
                    'title',
                ],
            ]);

        // Sprawdzenie salda nadawcy
        $sender->account->refresh();
        $this->assertEquals(500.00, $sender->account->balance);

        // Sprawdzenie salda odbiorcy
        $recipient->account->refresh();
        $this->assertEquals(500.00, $recipient->account->balance);

        // Sprawdzenie transakcji w bazie
        $this->assertDatabaseHas('transactions', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'amount' => 500.00,
            'status' => 'completed',
        ]);
    }

    /**
     * Test przelewu z niewystarczającymi środkami
     */
    public function test_user_cannot_transfer_with_insufficient_funds(): void
    {
        $sender = User::factory()->create();
        $sender->account->update(['balance' => 100.00]);
        $token = $sender->createToken('test-token')->plainTextToken;

        $recipient = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/transactions/transfer', [
            'recipient_account_number' => $recipient->account_number,
            'amount' => 500.00,
            'title' => 'Test transfer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /**
     * Test przelewu na własne konto
     */
    public function test_user_cannot_transfer_to_own_account(): void
    {
        $user = User::factory()->create();
        $user->account->update(['balance' => 1000.00]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/transactions/transfer', [
            'recipient_account_number' => $user->account_number,
            'amount' => 100.00,
            'title' => 'Test transfer',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test pobrania historii transakcji
     */
    public function test_user_can_get_transaction_history(): void
    {
        $user = User::factory()->create();
        $user->account->update(['balance' => 1000.00]);
        $token = $user->createToken('test-token')->plainTextToken;

        $recipient = User::factory()->create();

        // Wykonanie kilku transakcji
        Transaction::create([
            'sender_id' => $user->id,
            'sender_account_number' => $user->account_number,
            'recipient_id' => $recipient->id,
            'recipient_account_number' => $recipient->account_number,
            'amount' => 100.00,
            'title' => 'Test 1',
            'status' => 'completed',
            'sender_balance_before' => 1000.00,
            'sender_balance_after' => 900.00,
            'recipient_balance_before' => 0.00,
            'recipient_balance_after' => 100.00,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'transactions' => [
                    '*' => [
                        'id',
                        'transaction_number',
                        'type',
                        'counterparty',
                        'amount',
                        'title',
                        'executed_at',
                    ],
                ],
                'pagination',
            ]);
    }

    /**
     * Test sprawdzenia salda
     */
    public function test_user_can_check_balance(): void
    {
        $user = User::factory()->create();
        $user->account->update(['balance' => 1500.00]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/account/balance');

        $response->assertStatus(200)
            ->assertJson([
                'balance' => [
                    'amount' => '1500.00',
                    'currency' => 'PLN',
                ],
            ]);
    }

    /**
     * Test zmiany hasła
     */
    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/profile/change-password', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);

        // Sprawdzenie czy nowe hasło działa
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'newpassword123',
        ]);

        $loginResponse->assertStatus(200);
    }

    /**
     * Test wylogowania
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200);

        // Sprawdzenie czy token został usunięty
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    /**
     * Test dostępu bez autoryzacji
     */
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/profile');
        $response->assertStatus(401);

        $response = $this->getJson('/api/account/balance');
        $response->assertStatus(401);

        $response = $this->getJson('/api/transactions');
        $response->assertStatus(401);
    }

    /**
     * Test walidacji danych rejestracji
     */
    public function test_registration_validation(): void
    {
        $response = $this->postJson('/api/register', [
            'first_name' => '',
            'email' => 'invalid-email',
            'password' => '123', // za krótkie
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'email', 'password']);
    }
}
