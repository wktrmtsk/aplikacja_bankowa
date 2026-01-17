@extends('layouts.app')

@section('title', 'Raporty - Admin')

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
    <h2>📊 Raporty i Analizy</h2>
    <p style="color: #666;">Szczegółowe raporty dotyczące działania systemu bankowego</p>
</div>

<!-- Raport miesięczny -->
<div class="card">
    <h3>📅 Raport Miesięczny</h3>
    <div class="form-group" style="max-width: 300px;">
        <label>Wybierz miesiąc</label>
        <input type="month" id="report-month" value="">
    </div>
    <button class="btn btn-primary" onclick="generateMonthlyReport()">Generuj raport</button>
    
    <div id="monthly-report" style="margin-top: 20px;"></div>
</div>

<!-- Klienci bez pracowników -->
<div class="card">
    <h3>👥 Klienci bez przypisanych pracowników</h3>
    <button class="btn btn-primary" onclick="loadClientsWithoutEmployees()">Pokaż listę</button>
    
    <div id="clients-without-employees" style="margin-top: 20px;"></div>
</div>

<!-- Statystyki użytkowników -->
<div class="card">
    <h3>📈 Statystyki Użytkowników</h3>
    <div class="grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-box">
            <h4>Wszyscy użytkownicy</h4>
            <div class="big-number" id="total-users">-</div>
        </div>
        <div class="stat-box">
            <h4>Aktywni</h4>
            <div class="big-number" style="color: #10b981;" id="active-users">-</div>
        </div>
        <div class="stat-box">
            <h4>Zablokowani</h4>
            <div class="big-number" style="color: #ef4444;" id="blocked-users">-</div>
        </div>
    </div>
</div>

<!-- Statystyki transakcji -->
<div class="card">
    <h3>💸 Podsumowanie Transakcji</h3>
    <div id="transaction-summary">
        <div class="loading">
            <div class="spinner"></div>
            Ładowanie...
        </div>
    </div>
</div>

<style>
.stat-box {
    background: #f9fafb;
    padding: 25px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid #e5e7eb;
}

.stat-box h4 {
    color: #666;
    font-size: 14px;
    margin-bottom: 15px;
}

.big-number {
    font-size: 42px;
    font-weight: bold;
    color: #667eea;
}

.report-section {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

.report-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 15px;
}

.report-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: white;
    border-radius: 5px;
}

.report-item label {
    color: #666;
    font-weight: 500;
}

.report-item .value {
    font-weight: bold;
    color: #667eea;
}
</style>
@endsection

@section('scripts')
<script>
    // Ustaw domyślny miesiąc na obecny
    const now = new Date();
    const monthInput = document.getElementById('report-month');
    monthInput.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;

    async function loadDashboardStats() {
        try {
            const response = await apiRequest('/admin/dashboard');
            const stats = response.statistics;
            
            document.getElementById('total-users').textContent = stats.total_users;
            document.getElementById('active-users').textContent = stats.active_users;
            document.getElementById('blocked-users').textContent = stats.blocked_users;
            
        } catch (error) {
            console.error('Stats error:', error);
        }
    }

    async function loadTransactionSummary() {
        try {
            const response = await apiRequest('/admin/transactions/stats');
            const stats = response.statistics;
            
            const container = document.getElementById('transaction-summary');
            
            let html = '<div class="report-grid">';
            
            html += `
                <div class="report-item">
                    <label>Wszystkie transakcje:</label>
                    <span class="value">${stats.all_time.count}</span>
                </div>
                <div class="report-item">
                    <label>Łączny wolumen:</label>
                    <span class="value">${formatCurrency(stats.all_time.volume)}</span>
                </div>
                <div class="report-item">
                    <label>Transakcje dzisiaj:</label>
                    <span class="value">${stats.today.count}</span>
                </div>
                <div class="report-item">
                    <label>Wolumen dzisiaj:</label>
                    <span class="value">${formatCurrency(stats.today.volume)}</span>
                </div>
                <div class="report-item">
                    <label>Transakcje w tym miesiącu:</label>
                    <span class="value">${stats.this_month.count}</span>
                </div>
                <div class="report-item">
                    <label>Wolumen w tym miesiącu:</label>
                    <span class="value">${formatCurrency(stats.this_month.volume)}</span>
                </div>
                <div class="report-item">
                    <label>Średnia transakcja:</label>
                    <span class="value">${formatCurrency(stats.all_time.average || 0)}</span>
                </div>
                <div class="report-item">
                    <label>Transakcje w tym roku:</label>
                    <span class="value">${stats.this_year.count}</span>
                </div>
            `;
            
            html += '</div>';
            container.innerHTML = html;
            
        } catch (error) {
            console.error('Transaction summary error:', error);
        }
    }

    async function generateMonthlyReport() {
        const month = document.getElementById('report-month').value;
        
        if (!month) {
            alert('Wybierz miesiąc!');
            return;
        }
        
        const container = document.getElementById('monthly-report');
        showLoading(container);
        
        try {
            const response = await apiRequest(`/admin/monthly-report?month=${month}`);
            const report = response.report;
            
            let html = '<div class="report-section">';
            html += `<h4>Raport za ${month}</h4>`;
            html += '<div class="report-grid">';
            
            html += `
                <div class="report-item">
                    <label>Nowi użytkownicy:</label>
                    <span class="value">${report.new_users}</span>
                </div>
                <div class="report-item">
                    <label>Nowi klienci:</label>
                    <span class="value">${report.new_clients}</span>
                </div>
                <div class="report-item">
                    <label>Liczba transakcji:</label>
                    <span class="value">${report.transactions}</span>
                </div>
                <div class="report-item">
                    <label>Wolumen transakcji:</label>
                    <span class="value">${formatCurrency(report.transaction_volume)}</span>
                </div>
                <div class="report-item">
                    <label>Aktywni użytkownicy:</label>
                    <span class="value">${report.active_users}</span>
                </div>
            `;
            
            html += '</div></div>';
            container.innerHTML = html;
            
        } catch (error) {
            console.error('Monthly report error:', error);
            container.innerHTML = '<p style="color: #ef4444;">Błąd generowania raportu</p>';
        }
    }

    async function loadClientsWithoutEmployees() {
        const container = document.getElementById('clients-without-employees');
        showLoading(container);
        
        try {
            const response = await apiRequest('/admin/clients-without-employees');
            const clients = response.clients;
            
            if (clients.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #10b981; padding: 20px;">✅ Wszyscy klienci mają przypisanych pracowników!</p>';
                return;
            }
            
            let html = `<p style="color: #ef4444; margin-bottom: 15px;">⚠️ Znaleziono ${clients.length} klientów bez pracowników:</p>`;
            html += '<table>';
            html += '<thead><tr><th>Imię i nazwisko</th><th>Email</th><th>Nr konta</th><th>Saldo</th><th>Data rejestracji</th></tr></thead>';
            html += '<tbody>';
            
            clients.forEach(client => {
                html += '<tr>';
                html += `<td>${client.full_name}</td>`;
                html += `<td>${client.email}</td>`;
                html += `<td><small>${client.account_number}</small></td>`;
                html += `<td>${formatCurrency(client.balance)}</td>`;
                html += `<td>${formatDate(client.created_at)}</td>`;
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
            
        } catch (error) {
            console.error('Clients without employees error:', error);
            container.innerHTML = '<p style="color: #ef4444;">Błąd ładowania danych</p>';
        }
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
            document.getElementById('admin-name').textContent = userData.first_name;
        }
        
        loadDashboardStats();
        loadTransactionSummary();
    });
</script>
@endsection
