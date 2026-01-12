<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 20)->unique(); // Unikalny numer transakcji
            
            // Nadawca
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->string('sender_account_number', 28); // POPRAWIONE: 28 znaków
            
            // Odbiorca
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->string('recipient_account_number', 28); // POPRAWIONE: 28 znaków
            
            // Szczegóły transakcji
            $table->decimal('amount', 15, 2); // Kwota
            $table->string('currency', 3)->default('PLN');
            $table->string('title')->nullable(); // Tytuł przelewu
            $table->text('description')->nullable(); // Dodatkowy opis
            
            // Status i typ
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->enum('type', ['internal', 'external'])->default('internal');
            
            // Balanse przed i po
            $table->decimal('sender_balance_before', 15, 2);
            $table->decimal('sender_balance_after', 15, 2);
            $table->decimal('recipient_balance_before', 15, 2);
            $table->decimal('recipient_balance_after', 15, 2);
            
            $table->timestamp('executed_at')->useCurrent();
            $table->timestamps();
            
            // Indeksy dla szybszego wyszukiwania
            $table->index('sender_id');
            $table->index('recipient_id');
            $table->index('transaction_number');
            $table->index('executed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
