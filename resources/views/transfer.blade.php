@extends('layouts.app')

@section('title', 'Nowy przelew - Aplikacja Bankowa')

@section('content')
<div class="navbar">
    <div class="navbar-brand">🏦 Bank App</div>
    <div class="navbar-menu">
        <span id="user-name"></span>
        <a href="/dashboard">Dashboard</a>
        <a href="/profile">Profil</a>
        <a href="/transfer">Nowy przelew</a>
        <a href="/transactions">Historia</a>
        <button class="logout-btn" onclick="logout()">Wyloguj</button>
    </div>
</div>

<div style="max-width: 700px; margin: 0 auto;">
    <div class="balance-display" style="margin-bottom: 20px;">
        <h3>Dostępne środki</h3>
        <div class="amount" id="balance-amount">Ładowanie...</div>
    </div>

    <div class="card">
        <h2>💸 Nowy przelew</h2>
        
        <div id="success-message" class="alert alert-success hidden"></div>
        <div id="error-message" class="alert alert-error hidden"></div>
        
        <form id="transfer-form">
            <div class="form-group">
                <label for="recipient_account_number">Numer konta odbiorcy *</label>
                <input 
                    type="text" 
                    id="recipient_account_number" 
                    name="recipient_account_number" 
                    required
                    maxlength="28"

                >
                <small style="color: #666;">Format: PL + 24 cyfry (np. PL12345678901234567890123456)</small>
            </div>

            <div class="form-group">
                <label for="amount">Kwota (PLN) *</label>
                <input 
                    type="number" 
                    id="amount" 
                    name="amount" 
                    required 
                    min="0.01" 
                    step="0.01"
                    placeholder="100.00"
                >
            </div>

            <div class="form-group">
                <label for="title">Tytuł przelewu *</label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    required
                    maxlength="255"
                    placeholder="Za zakupy"
                >
            </div>

            <div class="form-group">
                <label for="description">Dodatkowy opis</label>
                <textarea 
                    id="description" 
                    name="description" 
                    rows="3"
                    maxlength="1000"
                    placeholder="Opcjonalny opis przelewu..."
                ></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Wykonaj przelew
            </button>
        </form>
    </div>

    <div class="info-box" style="margin-top: 20px;">
        <h3>ℹ️ Informacje o przelewach</h3>
        <ul style="margin-left: 20px; line-height: 1.8;">
            <li>Przelewy są realizowane natychmiast</li>
            <li>Minimalna kwota przelewu: 0.01 PLN</li>
            <li>Sprawdź dokładnie numer konta odbiorcy przed wykonaniem przelewu</li>
            <li>Nie można wykonać przelewu na własne konto</li>
        </ul>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2>📋 Numery kont użytkowników testowych</h2>
        <div id="test-accounts">
            <div class="loading">
                <div class="spinner"></div>
                Ładowanie...
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function loadTransferPage() {
        try {
            // Pobierz saldo
            const accountData = await apiRequest('/account/balance');
            document.getElementById('balance-amount').textContent = formatCurrency(accountData.balance.amount);

            // Pobierz dane użytkownika
            const userData = getUserData();
            if (userData) {
                document.getElementById('user-name').textContent = userData.first_name + ' ' + userData.last_name;
            }

        } catch (error) {
            console.error('Load error:', error);
        }
    }

    // Formularz przelewu
    document.getElementById('transfer-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = {
            recipient_account_number: document.getElementById('recipient_account_number').value,
            amount: parseFloat(document.getElementById('amount').value),
            title: document.getElementById('title').value,
            description: document.getElementById('description').value || null,
        };
        
        const successDiv = document.getElementById('success-message');
        const errorDiv = document.getElementById('error-message');
        const submitBtn = e.target.querySelector('button[type="submit"]');
        
        successDiv.classList.add('hidden');
        errorDiv.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Przetwarzanie...';
        
        try {
            const response = await apiRequest('/transactions/transfer', 'POST', formData);
            
            successDiv.innerHTML = `
                <strong>Przelew wykonany pomyślnie!</strong><br>
                Numer transakcji: ${response.transaction.transaction_number}<br>
                Kwota: ${response.transaction.formatted_amount}<br>
                Odbiorca: ${response.transaction.recipient.name}<br>
                Nowe saldo: ${formatCurrency(response.transaction.balance_after)}
            `;
            successDiv.classList.remove('hidden');
            
            // Wyczyść formularz
            e.target.reset();
            
            // Odśwież saldo
            loadTransferPage();
            
            submitBtn.disabled = false;
            submitBtn.textContent = 'Wykonaj przelew';
            
            // Przewiń do sukcesu
            successDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
        } catch (error) {
            console.error('Transfer error:', error);
            
            let errorMessage = 'Wystąpił błąd podczas wykonywania przelewu.';
            
            if (error.errors) {
                errorMessage = Object.values(error.errors).flat().join('<br>');
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            errorDiv.innerHTML = errorMessage;
            errorDiv.classList.remove('hidden');
            
            submitBtn.disabled = false;
            submitBtn.textContent = 'Wykonaj przelew';
            
            // Przewiń do błędu
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    function logout() {
        if (confirm('Czy na pewno chcesz się wylogować?')) {
            apiRequest('/logout', 'POST')
                .then(() => {
                    removeToken();
                    window.location.href = '/login';
                })
                .catch(() => {
                    removeToken();
                    window.location.href = '/login';
                });
        }
    }

    // Załaduj stronę
    loadTransferPage();
</script>
@endsection
