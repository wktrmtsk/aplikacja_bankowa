@extends('layouts.app')

@section('title', 'Panel Pracownika - Dashboard')

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
    <h2>👔 Panel Pracownika</h2>
    <p style="color: #666;">Zarządzaj swoimi klientami i ich transakcjami</p>
</div>

<!-- Statystyki -->
<div class="grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;">
        <h4 style="color: rgba(255,255,255,0.9);">Moi klienci</h4>
        <div class="value" style="color: white;" id="clients-count">-</div>
    </div>

    <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
        <h4 style="color: rgba(255,255,255,0.9);">Aktywni</h4>
        <div class="value" style="color: white;" id="active-clients">-</div>
    </div>

    <div class="stat-card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
        <h4 style="color: rgba(255,255,255,0.9);">Zablokowani</h4>
        <div class="value" style="color: white;" id="blocked-clients">-</div>
    </div>

    <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
        <h4 style="color: rgba(255,255,255,0.9);">Łączne saldo</h4>
        <div class="value" style="color: white; font-size: 20px;" id="total-balance">-</div>
    </div>
</div>

<!-- Szybkie akcje -->
<div class="card" style="margin-top: 20px;">
    <h3>⚡ Szybkie akcje</h3>
    <div class="grid" style="grid-template-columns: repeat(4, 1fr); gap: 15px;">
        <a href="/employee/clients" class="action-button" style="background: #3b82f6;">
            <div class="action-icon">👥</div>
            <div class="action-text">Lista klientów</div>
        </a>
        <a href="/employee/deposit" class="action-button" style="background: #10b981;">
            <div class="action-icon">💵</div>
            <div class="action-text">Wpłata gotówki</div>
        </a>
        <a href="/employee/transfer" class="action-button" style="background: #8b5cf6;">
            <div class="action-icon">💸</div>
            <div class="action-text">Przelew dla klienta</div>
        </a>
        <a href="/employee/transactions" class="action-button" style="background: #f59e0b;">
            <div class="action-icon">📊</div>
            <div class="action-text">Historia transakcji</div>
        </a>
    </div>
</div>

<!-- Lista klientów -->
<div class="card" style="margin-top: 20px;">
    <h3>👥 Moi Klienci</h3>
    <div id="clients-list">
        <div class="loading">
            <div class="spinner"></div>
            Ładowanie klientów...
        </div>
    </div>
    <div style="margin-top: 15px; text-align: center;">
        <a href="/employee/clients" class="btn btn-primary">Zobacz wszystkich klientów</a>
    </div>
</div>

<style>
.stat-card {
    padding: 25px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stat-card h4 {
    font-size: 14px;
    margin-bottom: 10px;
}

.stat-card .value {
    font-size: 36px;
    font-weight: bold;
}

.action-button {
    display: block;
    padding: 30px 20px;
    border-radius: 10px;
    text-align: center;
    color: white;
    text-decoration: none;
    transition: transform 0.2s;
}

.action-button:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
}

.action-icon {
    font-size: 40px;
    margin-bottom: 10px;
}

.action-text {
    font-size: 16px;
    font-weight: 500;
}

.client-card {
    background: #f9fafb;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 4px solid #3b82f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.client-card:hover {
    background: #f3f4f6;
}
</style>
@endsection

@section('scripts')
<script>
    async function loadDashboard() {
        try {
            const userData = getUserData();
            if (userData) {
                document.getElementById('employee-name').textContent = userData.first_name;
            }

            // Załaduj statystyki
            const dashboardData = await apiRequest('/employee/dashboard');
            const stats = dashboardData.statistics;

            document.getElementById('clients-count').textContent = stats.my_clients_count;
            document.getElementById('active-clients').textContent = stats.active_clients;
            document.getElementById('blocked-clients').textContent = stats.blocked_clients;
            document.getElementById('total-balance').textContent = formatCurrency(stats.clients_total_balance);

            // Załaduj listę klientów (tylko 5 najnowszych)
            loadRecentClients();

        } catch (error) {
            console.error('Dashboard error:', error);
            if (error.message && error.message.includes('Brak uprawnień')) {
                alert('Nie masz uprawnień pracownika!');
                window.location.href = '/login';
            }
        }
    }

    async function loadRecentClients() {
        try {
            const response = await apiRequest('/employee/clients?per_page=5');
            displayRecentClients(response.clients);
        } catch (error) {
            console.error('Clients error:', error);
        }
    }

    function displayRecentClients(clients) {
        const container = document.getElementById('clients-list');

        if (clients.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #999;">Nie masz przypisanych klientów</p>';
            return;
        }

        let html = '';
        clients.forEach(client => {
            html += `
                <div class="client-card">
                    <div>
                        <strong>${client.full_name}</strong><br>
                        <small style="color: #666;">${client.email}</small>
                    </div>
                    <div style="text-align: right;">
                        <strong style="color: #10b981;">${formatCurrency(client.balance)}</strong><br>
                        <span class="badge ${client.status === 'active' ? 'badge-success' : 'badge-danger'}">
                            ${client.status}
                        </span>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
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

    // Załaduj dashboard
    document.addEventListener('DOMContentLoaded', function() {
        if (!isLoggedIn()) {
            alert('Musisz być zalogowany!');
            window.location.href = '/login';
            return;
        }
        
        loadDashboard();
    });
</script>
@endsection
