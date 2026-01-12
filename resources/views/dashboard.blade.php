@extends('layouts.app')

@section('title', 'Panel główny - Aplikacja Bankowa')

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

<div class="balance-display">
    <h3>Dostępne środki</h3>
    <div class="amount" id="balance-amount">Ładowanie...</div>
    <p id="account-number" style="margin-top: 10px; opacity: 0.9;"></p>
</div>

<div class="grid">
    <div class="stat-card">
        <h4>Wysłane przelewy</h4>
        <div class="value" id="sent-count">-</div>
        <p style="color: #666; font-size: 14px; margin-top: 5px;">
            <span id="sent-amount">-</span>
        </p>
    </div>

    <div class="stat-card">
        <h4>Otrzymane przelewy</h4>
        <div class="value" id="received-count">-</div>
        <p style="color: #666; font-size: 14px; margin-top: 5px;">
            <span id="received-amount">-</span>
        </p>
    </div>

    <div class="stat-card">
        <h4>Wszystkie transakcje</h4>
        <div class="value" id="total-count">-</div>
        <p style="color: #666; font-size: 14px; margin-top: 5px;">
            Status konta: <span id="account-status">-</span>
        </p>
    </div>
</div>

<div class="card">
    <h2>Ostatnie transakcje</h2>
    <div id="recent-transactions">
        <div class="loading">
            <div class="spinner"></div>
            Ładowanie transakcji...
        </div>
    </div>
    <div class="text-center" style="margin-top: 20px;">
        <a href="/transactions" class="btn btn-secondary">Zobacz wszystkie transakcje</a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function loadDashboard() {
        try {
            // Pobierz dane użytkownika
            const userData = getUserData();
            if (userData) {
                document.getElementById('user-name').textContent = userData.first_name + ' ' + userData.last_name;
            }

            // Pobierz aktualne saldo
            const accountData = await apiRequest('/account/balance');
            document.getElementById('balance-amount').textContent = formatCurrency(accountData.balance.amount);
            
            // Pobierz pełne dane konta dla numeru
            const fullAccount = await apiRequest('/account');
            document.getElementById('account-number').textContent = 'Nr konta: ' + fullAccount.account.account_number;
            document.getElementById('account-status').textContent = fullAccount.account.status;

            // Pobierz statystyki
            const stats = await apiRequest('/transactions/statistics');
            document.getElementById('sent-count').textContent = stats.statistics.total_sent.count;
            document.getElementById('sent-amount').textContent = stats.statistics.total_sent.formatted_amount;
            document.getElementById('received-count').textContent = stats.statistics.total_received.count;
            document.getElementById('received-amount').textContent = stats.statistics.total_received.formatted_amount;
            document.getElementById('total-count').textContent = 
                stats.statistics.total_sent.count + stats.statistics.total_received.count;

            // Pobierz ostatnie 5 transakcji
            const transactions = await apiRequest('/transactions?per_page=5');
            displayRecentTransactions(transactions.transactions);

        } catch (error) {
            console.error('Dashboard load error:', error);
            if (error.message === 'Unauthenticated.') {
                removeToken();
                window.location.href = '/login';
            }
        }
    }

    function displayRecentTransactions(transactions) {
        const container = document.getElementById('recent-transactions');
        
        if (transactions.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">Brak transakcji</p>';
            return;
        }

        let html = '<table><thead><tr>';
        html += '<th>Data</th>';
        html += '<th>Kontrahent</th>';
        html += '<th>Tytuł</th>';
        html += '<th>Kwota</th>';
        html += '<th>Status</th>';
        html += '</tr></thead><tbody>';

        transactions.forEach(transaction => {
            html += '<tr>';
            html += `<td>${formatDate(transaction.executed_at)}</td>`;
            html += `<td>${transaction.counterparty.name}</td>`;
            html += `<td>${transaction.title}</td>`;
            html += `<td style="font-weight: bold; color: ${transaction.type === 'sent' ? '#ef4444' : '#10b981'}">
                        ${transaction.formatted_amount}
                     </td>`;
            html += `<td><span class="badge badge-success">${transaction.status}</span></td>`;
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function logout() {
        if (confirm('Czy na pewno chcesz się wylogować?')) {
            apiRequest('/logout', 'POST')
                .then(() => {
                    removeToken();
                    window.location.href = '/login';
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    removeToken();
                    window.location.href = '/login';
                });
        }
    }

    // Załaduj dane przy starcie
    loadDashboard();
</script>
@endsection
