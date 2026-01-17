@extends('layouts.app')

@section('title', 'Panel Administratora - Dashboard')

@section('content')
<div class="navbar">
    <div class="navbar-brand">🏦 Bank App - ADMIN</div>
    <div class="navbar-menu">
        <span id="admin-name" style="color: #ef4444; font-weight: bold;">Admin</span>
        <a href="/admin/dashboard">Dashboard</a>
        <a href="/admin/employees">Pracownicy</a>
        <a href="/admin/clients">Klienci</a>
        <a href="/admin/all-transactions">Transakcje</a>
        <a href="/admin/reports">Raporty</a>
        <button class="logout-btn" onclick="logout()">Wyloguj</button>
    </div>
</div>

<div class="card">
    <h2>📊 Panel Administratora</h2>
    <p style="color: #666;">Witaj w panelu zarządzania systemem bankowym</p>
</div>
<style>
.hidden {
    display: none !important; 
}
</style>
<!-- Główne statystyki -->
<div class="grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h4 style="color: rgba(255,255,255,0.9);">Wszyscy użytkownicy</h4>
        <div class="value" style="color: white;" id="total-users">-</div>
    </div>

    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
        <h4 style="color: rgba(255,255,255,0.9);">Klienci</h4>
        <div class="value" style="color: white;" id="total-clients">-</div>
    </div>

    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
        <h4 style="color: rgba(255,255,255,0.9);">Pracownicy</h4>
        <div class="value" style="color: white;" id="total-employees">-</div>
    </div>

    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
        <h4 style="color: rgba(255,255,255,0.9);">Aktywni</h4>
        <div class="value" style="color: white;" id="active-users">-</div>
    </div>
</div>

<!-- Statystyki transakcji -->
<div class="card" style="margin-top: 20px;">
    <h2>💸 Statystyki Transakcji</h2>
    <div class="grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="info-box">
            <h4>Wszystkie transakcje</h4>
            <p style="font-size: 24px; font-weight: bold;" id="all-transactions">-</p>
            <p style="color: #10b981; font-size: 18px;" id="all-volume">-</p>
        </div>

        <div class="info-box">
            <h4>Transakcje w tym miesiącu</h4>
            <p style="font-size: 24px; font-weight: bold;" id="month-transactions">-</p>
            <p style="color: #10b981; font-size: 18px;" id="month-volume">-</p>
        </div>

        <div class="info-box">
            <h4>Dzisiaj</h4>
            <p style="font-size: 24px; font-weight: bold;" id="today-transactions">-</p>
            <p style="color: #10b981; font-size: 18px;" id="today-volume">-</p>
        </div>
    </div>
</div>

<!-- Saldo systemu -->
<div class="grid" style="grid-template-columns: 2fr 1fr; margin-top: 20px;">
    <div class="card">
        <h2>💰 Finanse Systemu</h2>
        <div class="balance-display">
            <h3>Łączne saldo wszystkich kont</h3>
            <div class="amount" id="total-balance">Ładowanie...</div>
        </div>
        <div class="grid" style="grid-template-columns: 1fr 1fr;">
            <div class="info-box">
                <h4>Średnia transakcja</h4>
                <p style="font-size: 20px; font-weight: bold;" id="avg-transaction">-</p>
            </div>
            <div class="info-box">
                <h4>Zablokowanych kont</h4>
                <p style="font-size: 20px; font-weight: bold; color: #ef4444;" id="blocked-users">-</p>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>⚡ Szybkie akcje</h2>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="/admin/employees/new" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                ➕ Dodaj pracownika
            </a>
            <a href="/admin/clients/new" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                ➕ Dodaj klienta
            </a>
            <a href="/admin/reports" class="btn btn-secondary" style="text-align: center; text-decoration: none;">
                📊 Raporty
            </a>
            <a href="/admin/all-transactions" class="btn btn-secondary" style="text-align: center; text-decoration: none;">
                💸 Wszystkie transakcje
            </a>
        </div>
    </div>
</div>

<!-- Ostatnia aktywność -->
<div class="card" style="margin-top: 20px;">
    <h2>🕐 Ostatnie transakcje</h2>
    <div id="recent-activity">
        <div class="loading">
            <div class="spinner"></div>
            Ładowanie...
        </div>
    </div>
    
</div>
@endsection

@section('scripts')
<script>
    async function loadAdminDashboard() {
        try {
            const userData = getUserData();
            if (userData) {
                document.getElementById('admin-name').textContent = userData.first_name;
            }

            // Załaduj główne statystyki
            const dashboardData = await apiRequest('/admin/dashboard');
            const stats = dashboardData.statistics;

            document.getElementById('total-users').textContent = stats.total_users;
            document.getElementById('total-clients').textContent = stats.total_clients;
            document.getElementById('total-employees').textContent = stats.total_employees;
            document.getElementById('active-users').textContent = stats.active_users;
            document.getElementById('blocked-users').textContent = stats.blocked_users;
            document.getElementById('total-balance').textContent = formatCurrency(stats.total_balance);

            // Załaduj statystyki transakcji
            const transStats = await apiRequest('/admin/transactions/stats');
            const tStats = transStats.statistics;

            document.getElementById('all-transactions').textContent = tStats.all_time.count;
            document.getElementById('all-volume').textContent = formatCurrency(tStats.all_time.volume);
            document.getElementById('avg-transaction').textContent = formatCurrency(tStats.all_time.average || 0);

            document.getElementById('month-transactions').textContent = tStats.this_month.count;
            document.getElementById('month-volume').textContent = formatCurrency(tStats.this_month.volume);

            document.getElementById('today-transactions').textContent = tStats.today.count;
            document.getElementById('today-volume').textContent = formatCurrency(tStats.today.volume);

            // Załaduj ostatnie transakcje
            const transactions = await apiRequest('/admin/transactions?per_page=5');
            displayRecentActivity(transactions.transactions);

        } catch (error) {
            console.error('Dashboard load error:', error);
            if (error.message && error.message.includes('Brak uprawnień')) {
                alert('Nie masz uprawnień administratora!');
                window.location.href = '/dashboard';
            }
        }
    }

    function displayRecentActivity(transactions) {
        const container = document.getElementById('recent-activity');

        if (transactions.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #999;">Brak transakcji</p>';
            return;
        }

        let html = '<table><thead><tr>';
        html += '<th>Data</th>';
        html += '<th>Nadawca</th>';
        html += '<th>Odbiorca</th>';
        html += '<th>Kwota</th>';
        html += '<th>Status</th>';
        html += '</tr></thead><tbody>';

        transactions.forEach(t => {
            html += '<tr>';
            html += `<td>${formatDate(t.executed_at)}</td>`;
            html += `<td>${t.sender.name}</td>`;
            html += `<td>${t.recipient.name}</td>`;
            html += `<td style="font-weight: bold; color: #10b981;">${t.formatted_amount}</td>`;
            html += `<td><span class="badge badge-success">${t.status}</span></td>`;
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
                .catch(() => {
                    removeToken();
                    window.location.href = '/login';
                });
        }
    }

    // Załaduj dashboard
    loadAdminDashboard();

</script>

@endsection
