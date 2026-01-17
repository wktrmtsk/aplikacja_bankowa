@extends('layouts.app')

@section('title', 'Wszystkie Transakcje - Admin')

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
    <h2>💸 Wszystkie Transakcje w Systemie</h2>
    
    <!-- Filtry -->
    <div class="grid" style="grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 20px;">
        <div class="form-group">
            <label>Data od</label>
            <input type="date" id="date-from">
        </div>
        <div class="form-group">
            <label>Data do</label>
            <input type="date" id="date-to">
        </div>
        <div class="form-group">
            <label>Minimalna kwota</label>
            <input type="number" id="min-amount" placeholder="0.00" step="0.01">
        </div>
        <div class="form-group">
            <label>Maksymalna kwota</label>
            <input type="number" id="max-amount" placeholder="10000.00" step="0.01">
        </div>
    </div>
    
    <div style="margin-top: 15px; display: flex; gap: 10px;">
        <button class="btn btn-primary" onclick="applyFilters()">🔍 Filtruj</button>
        <button class="btn btn-secondary" onclick="clearFilters()">✖️ Wyczyść filtry</button>
    </div>
</div>

<!-- Statystyki -->
<div class="card">
    <h3>📊 Statystyki</h3>
    <div class="grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="stat-card">
            <h4>Wszystkie</h4>
            <div class="value" id="total-count">-</div>
            <small id="total-volume">-</small>
        </div>
        <div class="stat-card">
            <h4>Dzisiaj</h4>
            <div class="value" id="today-count">-</div>
            <small id="today-volume">-</small>
        </div>
        <div class="stat-card">
            <h4>Ten miesiąc</h4>
            <div class="value" id="month-count">-</div>
            <small id="month-volume">-</small>
        </div>
        <div class="stat-card">
            <h4>Średnia</h4>
            <div class="value" id="avg-amount">-</div>
            <small>na transakcję</small>
        </div>
    </div>
</div>

<!-- Lista transakcji -->
<div class="card">
    <h3>Lista transakcji</h3>
    <div id="transactions-list">
        <div class="loading">
            <div class="spinner"></div>
            Ładowanie transakcji...
        </div>
    </div>
    
    <!-- Paginacja -->
    <div id="pagination"></div>
</div>

<style>
.transaction-row {
    background: #f9fafb;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 4px solid #667eea;
}

.transaction-row:hover {
    background: #f3f4f6;
}

.transaction-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h4 {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
}

.stat-card .value {
    font-size: 28px;
    font-weight: bold;
    color: #667eea;
}

.stat-card small {
    color: #999;
    font-size: 12px;
}
</style>
@endsection

@section('scripts')
<script>
    let currentPage = 1;
    let currentFilters = {};

    async function loadTransactions(page = 1) {
        currentPage = page;
        const container = document.getElementById('transactions-list');
        showLoading(container);

        try {
            let url = `/admin/transactions?page=${page}&per_page=15`;
            
            // Dodaj filtry
            if (currentFilters.date_from) url += `&date_from=${currentFilters.date_from}`;
            if (currentFilters.date_to) url += `&date_to=${currentFilters.date_to}`;
            if (currentFilters.min_amount) url += `&min_amount=${currentFilters.min_amount}`;
            if (currentFilters.max_amount) url += `&max_amount=${currentFilters.max_amount}`;

            console.log('Fetching transactions from:', url);
            const response = await apiRequest(url);
            console.log('Response:', response);
            
            displayTransactions(response.transactions, response.pagination);

        } catch (error) {
            console.error('Error details:', error);
            
            let errorMessage = 'Błąd ładowania transakcji';
            if (error.message && error.message.includes('Brak uprawnień')) {
                errorMessage = 'Brak uprawnień administratora!';
                setTimeout(() => window.location.href = '/login', 2000);
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            container.innerHTML = `<p style="text-align: center; color: #ef4444; padding: 40px;">${errorMessage}</p>`;
        }
    }

    async function loadStats() {
        try {
            const stats = await apiRequest('/admin/transactions/stats');
            const s = stats.statistics;
            
            document.getElementById('total-count').textContent = s.all_time.count;
            document.getElementById('total-volume').textContent = formatCurrency(s.all_time.volume);
            document.getElementById('avg-amount').textContent = formatCurrency(s.all_time.average || 0);
            
            document.getElementById('today-count').textContent = s.today.count;
            document.getElementById('today-volume').textContent = formatCurrency(s.today.volume);
            
            document.getElementById('month-count').textContent = s.this_month.count;
            document.getElementById('month-volume').textContent = formatCurrency(s.this_month.volume);
            
        } catch (error) {
            console.error('Stats error:', error);
        }
    }

    function displayTransactions(transactions, pagination) {
        const container = document.getElementById('transactions-list');

        if (transactions.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">Brak transakcji</p>';
            return;
        }

        let html = '<table>';
        html += '<thead><tr>';
        html += '<th>Data</th>';
        html += '<th>Nr transakcji</th>';
        html += '<th>Nadawca</th>';
        html += '<th>Odbiorca</th>';
        html += '<th>Kwota</th>';
        html += '<th>Tytuł</th>';
        html += '<th>Status</th>';
        html += '</tr></thead><tbody>';

        transactions.forEach(t => {
            html += '<tr>';
            html += `<td>${formatDate(t.executed_at)}</td>`;
            html += `<td><small>${t.transaction_number}</small></td>`;
            html += `<td><strong>${t.sender.name}</strong><br><small>${t.sender.account_number}</small></td>`;
            html += `<td><strong>${t.recipient.name}</strong><br><small>${t.recipient.account_number}</small></td>`;
            html += `<td style="font-weight: bold; color: #10b981;">${t.formatted_amount}</td>`;
            html += `<td>${t.title}</td>`;
            html += `<td><span class="badge badge-success">${t.status}</span></td>`;
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
        displayPagination(pagination);
    }

    function displayPagination(pagination) {
        const container = document.getElementById('pagination');
        
        if (pagination.last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">';
        
        if (pagination.current_page > 1) {
            html += `<button class="btn btn-secondary" onclick="loadTransactions(${pagination.current_page - 1})">← Poprzednia</button>`;
        }

        html += `<span style="padding: 10px;">Strona ${pagination.current_page} z ${pagination.last_page}</span>`;

        if (pagination.current_page < pagination.last_page) {
            html += `<button class="btn btn-secondary" onclick="loadTransactions(${pagination.current_page + 1})">Następna →</button>`;
        }

        html += '</div>';
        container.innerHTML = html;
    }

    function applyFilters() {
        currentFilters = {
            date_from: document.getElementById('date-from').value,
            date_to: document.getElementById('date-to').value,
            min_amount: document.getElementById('min-amount').value,
            max_amount: document.getElementById('max-amount').value,
        };
        
        loadTransactions(1);
    }

    function clearFilters() {
        document.getElementById('date-from').value = '';
        document.getElementById('date-to').value = '';
        document.getElementById('min-amount').value = '';
        document.getElementById('max-amount').value = '';
        currentFilters = {};
        loadTransactions(1);
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
        
        loadTransactions();
        loadStats();
    });
</script>
@endsection
