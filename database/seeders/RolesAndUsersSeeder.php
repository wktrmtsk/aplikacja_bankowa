<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ===================================
        // 1. TWORZENIE RÓL
        // ===================================
        
        $clientRole = Role::create([
            'name' => 'client',
            'display_name' => 'Klient',
            'description' => 'Zwykły użytkownik banku - klient'
        ]);

        $employeeRole = Role::create([
            'name' => 'employee',
            'display_name' => 'Pracownik',
            'description' => 'Pracownik banku - zarządza klientami'
        ]);

        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Administrator systemu - pełny dostęp'
        ]);

        echo "✅ Role utworzone\n\n";

        // ===================================
        // 2. ADMINISTRATOR
        // ===================================
        
        $admin = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@bank.pl',
            'password' => Hash::make('admin123'),
            'phone' => '+48111222333',
            'pesel' => '80010100001',
            'birth_date' => '1980-01-01',
            'address' => 'ul. Centralna 1',
            'city' => 'Warszawa',
            'postal_code' => '00-001',
            'country' => 'Polska',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');
        $admin->account->update(['balance' => 1000000.00]); // Admin ma dużo pieniędzy :)

        echo "✅ Administrator utworzony: admin@bank.pl / admin123\n";

        // ===================================
        // 3. PRACOWNICY
        // ===================================
        
        $employee1 = User::create([
            'first_name' => 'Małgorzata',
            'last_name' => 'Kowalska',
            'email' => 'pracownik1@bank.pl',
            'password' => Hash::make('pracownik123'),
            'phone' => '+48222333444',
            'pesel' => '85050500002',
            'birth_date' => '1985-05-05',
            'address' => 'ul. Bankowa 10',
            'city' => 'Kraków',
            'postal_code' => '30-001',
            'country' => 'Polska',
            'status' => 'active',
        ]);
        $employee1->assignRole('employee');
        $employee1->account->update(['balance' => 50000.00]);

        $employee2 = User::create([
            'first_name' => 'Tomasz',
            'last_name' => 'Nowak',
            'email' => 'pracownik2@bank.pl',
            'password' => Hash::make('pracownik123'),
            'phone' => '+48333444555',
            'pesel' => '90101000003',
            'birth_date' => '1990-10-10',
            'address' => 'ul. Pracownicza 5',
            'city' => 'Gdańsk',
            'postal_code' => '80-001',
            'country' => 'Polska',
            'status' => 'active',
        ]);
        $employee2->assignRole('employee');
        $employee2->account->update(['balance' => 45000.00]);

        echo "✅ Pracownicy utworzeni:\n";
        echo "   - pracownik1@bank.pl / pracownik123\n";
        echo "   - pracownik2@bank.pl / pracownik123\n";

        // ===================================
        // 4. KLIENCI
        // ===================================
        
        $client1 = User::create([
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
        $client1->assignRole('client');
        $client1->account->update(['balance' => 5000.00]);

        $client2 = User::create([
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
        $client2->assignRole('client');
        $client2->account->update(['balance' => 3000.00]);

        $client3 = User::create([
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
        $client3->assignRole('client');
        $client3->account->update(['balance' => 10000.00]);

        $client4 = User::create([
            'first_name' => 'Maria',
            'last_name' => 'Zielińska',
            'email' => 'maria.zielinska@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+48666777888',
            'pesel' => '88030388888',
            'birth_date' => '1988-03-03',
            'address' => 'ul. Główna 30',
            'city' => 'Poznań',
            'postal_code' => '60-001',
            'country' => 'Polska',
            'status' => 'active',
        ]);
        $client4->assignRole('client');
        $client4->account->update(['balance' => 7500.00]);

        echo "✅ Klienci utworzeni (hasło dla wszystkich: password123)\n";

        // ===================================
        // 5. PRZYPISANIE KLIENTÓW DO PRACOWNIKÓW
        // ===================================
        
        // Pracownik 1 zarządza klientami 1 i 2
        $employee1->clients()->attach([$client1->id, $client2->id]);
        
        // Pracownik 2 zarządza klientami 3 i 4
        $employee2->clients()->attach([$client3->id, $client4->id]);

        echo "✅ Klienci przypisani do pracowników\n";

        // ===================================
        // 6. PRZYKŁADOWE TRANSAKCJE
        // ===================================
        
        $this->createTransaction($client1, $client2, 500.00, 'Za zakupy', 'Zwrot za wspólne zakupy');
        $this->createTransaction($client2, $client3, 200.00, 'Czynsz', 'Wpłata za czynsz');
        $this->createTransaction($client3, $client1, 1000.00, 'Pożyczka', 'Zwrot pożyczki');
        $this->createTransaction($client1, $client4, 150.00, 'Prezent', 'Prezent urodzinowy');
        $this->createTransaction($client4, $client2, 300.00, 'Za usługę', 'Zapłata za usługę');

        echo "✅ Przykładowe transakcje utworzone\n\n";

        // ===================================
        // PODSUMOWANIE
        // ===================================
        
        echo "====================================\n";
        echo "📊 PODSUMOWANIE SEEDOWANIA\n";
        echo "====================================\n\n";
        
        echo "🔑 DANE LOGOWANIA:\n\n";
        
        echo "ADMINISTRATOR:\n";
        echo "Email: admin@bank.pl\n";
        echo "Hasło: admin123\n";
        echo "Saldo: 1,000,000.00 PLN\n\n";
        
        echo "PRACOWNICY:\n";
        echo "1. pracownik1@bank.pl / pracownik123 (zarządza: Jan, Anna)\n";
        echo "2. pracownik2@bank.pl / pracownik123 (zarządza: Piotr, Maria)\n\n";
        
        echo "KLIENCI:\n";
        echo "1. jan.kowalski@example.com / password123 (Saldo: " . $client1->account->fresh()->balance . " PLN)\n";
        echo "2. anna.nowak@example.com / password123 (Saldo: " . $client2->account->fresh()->balance . " PLN)\n";
        echo "3. piotr.wisniewski@example.com / password123 (Saldo: " . $client3->account->fresh()->balance . " PLN)\n";
        echo "4. maria.zielinska@example.com / password123 (Saldo: " . $client4->account->fresh()->balance . " PLN)\n\n";
        
        echo "🏦 NUMERY KONT:\n";
        echo "Admin: {$admin->account_number}\n";
        echo "Pracownik 1: {$employee1->account_number}\n";
        echo "Pracownik 2: {$employee2->account_number}\n";
        echo "Jan: {$client1->account_number}\n";
        echo "Anna: {$client2->account_number}\n";
        echo "Piotr: {$client3->account_number}\n";
        echo "Maria: {$client4->account_number}\n";
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
