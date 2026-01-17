@extends('layouts.app')

@section('title', 'Wpłata gotówki - Pracownik')

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
    <h2>💵 Wpłata gotówki na konto klienta</h2>
    <p style="color: #666;">Przyjmij wpłatę gotówkową i dodaj środki na konto klienta</p>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Formularz wpłaty -->
    <div class="card">
        <h3>Formularz wpłaty</h3>
        
        <form id="deposit-form">
            <div class="form-group">
                <label>Wybierz klienta *</label>
                <select id="client-select" required>
                    <option value="">-- Wybierz klienta --</option>
                </select>
            </div>

            <div id="client-info" class="hidden" style="background: #f0f9ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="margin-top: 0;">Informacje o kliencie</h4>
                <p><strong>Imię i nazwisko:</strong> <span id="client-name">-</span></p>
                <p><strong>Nr konta:</strong> <span id="client-account">-</span></p>
                <p><strong>Obecne saldo:</strong> <span id="client-balance" style="color: #10b981; font-weight: bold;">-</span></p>
            </div>

            <div class="form-group">
                <label>Kwota wpłaty (PLN) *</label>
                <input type="number" id="amount" step="0.01" min="0.01" required placeholder="0.00">
            </div>

            <div class="form-group">
                <label>Opis wpłaty *</label>
                <input type="text" id="description" required placeholder="np. Wpłata gotówki w oddziale">
            </div>

            <div class="alert alert-info">
                <strong>ℹ️ Informacja:</strong> Wpłata zostanie natychmiast dodana na konto klienta. Operacja jest nieodwracalna.
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                💵 Zrealizuj wpłatę
            </button>
        </form>
    </div>

    <!-- Historia wpłat -->
    <div class="card">
        <h3>📋 Ostatnie wpłaty</h3>
        <div id="recent-deposits">
            <div class="loading">
                <div class="spinner"></div>
                Ładowanie...
            </div>
        </div>
    </div>
</div>

<style>
.deposit-item {
    background: #f9fafb;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 4px solid #10b981;
}

.deposit-item h4 {
    margin: 0 0 5px 0;
    color: #333;
}

.deposit-item p {
    margin: 3px 0;
    font-size: 14px;
    color: #666;
}

.deposit-amount {
    font-size: 20px;
    font-weight: bold;
    color: #10b981;
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

    document.getElementById('deposit-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!selectedClient) {
            showAlert('Wybierz klienta', 'error');
            return;
        }
        
        const amount = parseFloat(document.getElementById('amount').value);
        const description = document.getElementById('description').value;
        
        if (amount <= 0) {
            showAlert('Kwota musi być większa od 0', 'error');
            return;
        }
        
        if (!confirm(`Czy na pewno chcesz wpłacić ${formatCurrency(amount)} na konto ${selectedClient.full_name}?`)) {
            return;
        }
        
        try {
            const response = await apiRequest(`/employee/clients/${selectedClient.id}/deposit`, 'POST', {
                amount: amount,
                description: description
            });
            
            showAlert(response.message, 'success');
            
            // Zaktualizuj saldo klienta
            selectedClient.balance = response.deposit.new_balance;
            document.getElementById('client-balance').textContent = formatCurrency(selectedClient.balance);
            
            // Wyczyść formularz
            document.getElementById('amount').value = '';
            document.getElementById('description').value = '';
            
            // Odśwież historię
            loadRecentDeposits();
            
        } catch (error) {
            showAlert(error.message || 'Błąd podczas wpłaty', 'error');
        }
    });

    async function loadRecentDeposits() {
        // To jest uproszczona wersja - w praktyce potrzebowałbyś osobnej tabeli dla wpłat
        // Na razie pokazujemy placeholder
        const container = document.getElementById('recent-deposits');
        container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Historia wpłat będzie dostępna wkrótce</p>';
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
        loadRecentDeposits();
        
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
