@extends('layouts.app')

@section('title', 'Przelew dla klienta - Pracownik')

@section('content')
<div class="navbar">
    <div class="navbar-brand">🏦 Bank App - PRACOWNIK</div>
    <div class="navbar-menu">
        <span id="employee-name" style="color: #3b82f6; font-weight: bold;">Pracownik</span>
        <a href="/employee/dashboard">Dashboard</a>
        <a href="/employee/clients">Moi Klienci</a>
        <a href="/employee/deposit">Wpłaty</a>
        <a href="/employee/transfer">Przelewy</a>
        <a href="/employee/transactions">Transakcje</a>
        <button class="logout-btn" onclick="logout()">Wyloguj</button>
    </div>
</div>

<div class="card">
    <h2>💸 Przelew w imieniu klienta</h2>
    <p style="color: #666;">Wykonaj przelew z konta klienta na wskazane konto</p>
</div>

<div class="card">
    <h3>Formularz przelewu</h3>
    
    <form id="transfer-form">
        <!-- Wybór klienta -->
        <div class="form-group">
            <label>Wybierz klienta (nadawca) *</label>
            <select id="client-select" required>
                <option value="">-- Wybierz klienta --</option>
            </select>
        </div>

        <div id="client-info" class="hidden" style="background: #f0f9ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin-top: 0;">Konto nadawcy</h4>
            <p><strong>Klient:</strong> <span id="client-name">-</span></p>
            <p><strong>Nr konta:</strong> <span id="client-account">-</span></p>
            <p><strong>Dostępne saldo:</strong> <span id="client-balance" style="color: #10b981; font-weight: bold;">-</span></p>
        </div>

        <!-- Dane odbiorcy -->
        <div class="form-group">
            <label>Numer konta odbiorcy *</label>
            <input type="text" id="recipient-account" maxlength="28" required placeholder="PL00000000000000000000000000">
            <small style="color: #666;">Format: PL + 26 cyfr (28 znaków)</small>
        </div>

        <div class="form-group">
            <label>Kwota przelewu (PLN) *</label>
            <input type="number" id="amount" step="0.01" min="0.01" required placeholder="0.00">
        </div>

        <div class="form-group">
            <label>Tytuł przelewu *</label>
            <input type="text" id="title" required placeholder="np. Opłata za usługi">
        </div>

        <div class="form-group">
            <label>Dodatkowy opis (opcjonalnie)</label>
            <textarea id="description" rows="3" placeholder="Dodatkowe informacje o przelewie"></textarea>
        </div>

        <div class="alert alert-warning">
            <strong>⚠️ Uwaga:</strong> Przelew zostanie natychmiast zrealizowany i nie może być anulowany. Sprawdź dokładnie wszystkie dane.
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">
            💸 Wykonaj przelew
        </button>
    </form>
</div>

<!-- Podsumowanie przelewu (modal) -->
<div id="summary-modal" class="modal hidden">
    <div class="modal-content">
        <h2>✅ Przelew zrealizowany pomyślnie!</h2>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Podsumowanie przelewu</h3>
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <p><strong>Nadawca:</strong></p>
                    <p id="summary-sender">-</p>
                </div>
                <div>
                    <p><strong>Odbiorca:</strong></p>
                    <p id="summary-recipient">-</p>
                </div>
                <div>
                    <p><strong>Kwota:</strong></p>
                    <p id="summary-amount" style="font-size: 24px; color: #10b981; font-weight: bold;">-</p>
                </div>
                <div>
                    <p><strong>Nowe saldo nadawcy:</strong></p>
                    <p id="summary-balance" style="font-size: 20px; font-weight: bold;">-</p>
                </div>
            </div>
            <p style="margin-top: 15px;"><strong>Nr transakcji:</strong> <span id="summary-transaction">-</span></p>
        </div>

        <div style="display: flex; gap: 10px;">
            <button class="btn btn-primary" onclick="closeSummary()">OK</button>
            <button class="btn btn-secondary" onclick="newTransfer()">Nowy przelew</button>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 10px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.hidden {
    display: none !important;
}
</style>
@endsection

@section('scripts')
<script>
    let clients = [];
    let selectedClient = null;

    async function loadClients() {
        try {
            const response = await apiRequest('/employee/clients?per_page=100');
            clients = response.clients;
            
            const select = document.getElementById('client-select');
            clients.forEach(client => {
                const option = document.createElement('option');
                option.value = client.id;
                option.textContent = `${client.full_name} (${formatCurrency(client.balance)})`;
                select.appendChild(option);
            });
            
        } catch (error) {
            console.error('Error loading clients:', error);
            showAlert('Błąd ładowania klientów', 'error');
        }
    }

    document.getElementById('client-select').addEventListener('change', function() {
        const clientId = parseInt(this.value);
        selectedClient = clients.find(c => c.id === clientId);
        
        if (selectedClient) {
            document.getElementById('client-info').classList.remove('hidden');
            document.getElementById('client-name').textContent = selectedClient.full_name;
            document.getElementById('client-account').textContent = selectedClient.account_number;
            document.getElementById('client-balance').textContent = formatCurrency(selectedClient.balance);
        } else {
            document.getElementById('client-info').classList.add('hidden');
        }
    });

    document.getElementById('transfer-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!selectedClient) {
            showAlert('Wybierz klienta', 'error');
            return;
        }
        
        const recipientAccount = document.getElementById('recipient-account').value;
        const amount = parseFloat(document.getElementById('amount').value);
        const title = document.getElementById('title').value;
        const description = document.getElementById('description').value;
        
        // Walidacja
        if (recipientAccount.length !== 28) {
            showAlert('Numer konta musi mieć 28 znaków', 'error');
            return;
        }
        
        if (amount <= 0) {
            showAlert('Kwota musi być większa od 0', 'error');
            return;
        }
        
        if (amount > selectedClient.balance) {
            showAlert(`Niewystarczające środki. Dostępne: ${formatCurrency(selectedClient.balance)}`, 'error');
            return;
        }
        
        if (!confirm(`Czy na pewno chcesz wykonać przelew ${formatCurrency(amount)} z konta ${selectedClient.full_name}?`)) {
            return;
        }
        
        try {
            const response = await apiRequest(`/employee/clients/${selectedClient.id}/transfer`, 'POST', {
                recipient_account_number: recipientAccount,
                amount: amount,
                title: title,
                description: description
            });
            
            // Pokaż podsumowanie
            document.getElementById('summary-sender').textContent = response.transaction.sender.name;
            document.getElementById('summary-recipient').textContent = response.transaction.recipient.name;
            document.getElementById('summary-amount').textContent = response.transaction.amount;
            document.getElementById('summary-balance').textContent = formatCurrency(response.transaction.sender.new_balance);
            document.getElementById('summary-transaction').textContent = response.transaction.transaction_number;
            
            document.getElementById('summary-modal').classList.remove('hidden');
            
            // Zaktualizuj saldo klienta
            selectedClient.balance = response.transaction.sender.new_balance;
            document.getElementById('client-balance').textContent = formatCurrency(selectedClient.balance);
            
        } catch (error) {
            showAlert(error.message || 'Błąd podczas przelewu', 'error');
        }
    });

    function closeSummary() {
        document.getElementById('summary-modal').classList.add('hidden');
    }

    function newTransfer() {
        document.getElementById('summary-modal').classList.add('hidden');
        document.getElementById('transfer-form').reset();
        document.getElementById('client-info').classList.add('hidden');
        selectedClient = null;
    }

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

    // Załaduj dane przy starcie
    document.addEventListener('DOMContentLoaded', function() {
        if (!isLoggedIn()) {
            alert('Musisz być zalogowany!');
            window.location.href = '/login';
            return;
        }
        
        const userData = getUserData();
        if (userData && userData.first_name) {
            document.getElementById('employee-name').textContent = userData.first_name;
        }
        
        loadClients();
        
        // Sprawdź czy jest client_id w URL
        const urlParams = new URLSearchParams(window.location.search);
        const clientId = urlParams.get('client_id');
        if (clientId) {
            setTimeout(() => {
                document.getElementById('client-select').value = clientId;
                document.getElementById('client-select').dispatchEvent(new Event('change'));
            }, 500);
        }
    });
</script>
@endsection
