<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tworzenie użytkowników testowych
        
        // Użytkownik 1 - Jan Kowalski
        $user1 = User::create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'email' => 'jan.kowalski@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+48123456789',
            'pesel' => '90010112345',
            'birth_date' => '1990-01-01',
            'address' => 'ul. Kwiatowa 15',
            'city' => 'Warszawa',
            'postal_code' => '00-001',
            'country' => 'Polska',
            'status' => 'active',
        ]);

        // Dodaj środki na konto
        $user1->account->update(['balance' => 5000.00]);

        // Użytkownik 2 - Anna Nowak
        $user2 = User::create([
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna.nowak@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+48987654321',
            'pesel' => '85050556789',
            'birth_date' => '1985-05-05',
            'address' => 'ul. Słoneczna 22',
            'city' => 'Kraków',
            'postal_code' => '30-001',
            'country' => 'Polska',
            'status' => 'active',
        ]);

        $user2->account->update(['balance' => 3000.00]);

        // Użytkownik 3 - Piotr Wiśniewski
        $user3 = User::create([
            'first_name' => 'Piotr',
            'last_name' => 'Wiśniewski',
            'email' => 'piotr.wisniewski@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+48555666777',
            'pesel' => '92121298765',
            'birth_date' => '1992-12-12',
            'address' => 'ul. Zielona 8',
            'city' => 'Gdańsk',
            'postal_code' => '80-001',
            'country' => 'Polska',
            'status' => 'active',
        ]);

        $user3->account->update(['balance' => 10000.00]);

        // Tworzenie przykładowych transakcji
        
        // Transakcja 1: Jan -> Anna
        $this->createTransaction($user1, $user2, 500.00, 'Za zakupy', 'Zwrot za wspólne zakupy');

        // Transakcja 2: Anna -> Piotr
        $this->createTransaction($user2, $user3, 200.00, 'Czynsz', 'Wpłata za czynsz');

        // Transakcja 3: Piotr -> Jan
        $this->createTransaction($user3, $user1, 1000.00, 'Pożyczka', 'Zwrot pożyczki');

        // Transakcja 4: Jan -> Piotr
        $this->createTransaction($user1, $user3, 150.00, 'Prezent', 'Prezent urodzinowy');

        echo "✅ Seedowanie zakończone!\n\n";
        echo "📧 Użytkownicy testowi:\n";
        echo "1. jan.kowalski@example.com (hasło: password123) - Saldo: {$user1->account->fresh()->balance} PLN\n";
        echo "2. anna.nowak@example.com (hasło: password123) - Saldo: {$user2->account->fresh()->balance} PLN\n";
        echo "3. piotr.wisniewski@example.com (hasło: password123) - Saldo: {$user3->account->fresh()->balance} PLN\n\n";
        echo "🏦 Numery kont:\n";
        echo "1. Jan Kowalski: {$user1->account_number}\n";
        echo "2. Anna Nowak: {$user2->account_number}\n";
        echo "3. Piotr Wiśniewski: {$user3->account_number}\n";
    }

    /**
     * Pomocnicza metoda do tworzenia transakcji
     */
    private function createTransaction($sender, $recipient, $amount, $title, $description = null)
    {
        $senderAccount = $sender->account;
        $recipientAccount = $recipient->account;

        $senderBalanceBefore = $senderAccount->balance;
        $recipientBalanceBefore = $recipientAccount->balance;

        $senderAccount->deductFunds($amount);
        $recipientAccount->addFunds($amount);

        Transaction::create([
            'sender_id' => $sender->id,
            'sender_account_number' => $sender->account_number,
            'recipient_id' => $recipient->id,
            'recipient_account_number' => $recipient->account_number,
            'amount' => $amount,
            'currency' => 'PLN',
            'title' => $title,
            'description' => $description,
            'status' => 'completed',
            'type' => 'internal',
            'sender_balance_before' => $senderBalanceBefore,
            'sender_balance_after' => $senderAccount->balance,
            'recipient_balance_before' => $recipientBalanceBefore,
            'recipient_balance_after' => $recipientAccount->balance,
            'executed_at' => now(),
        ]);
    }
}
