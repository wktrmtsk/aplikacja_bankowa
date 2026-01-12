@extends('layouts.app')

@section('title', 'Historia transakcji - Aplikacja Bankowa')

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

<div class="card">
    <h2>📜 Historia transakcji</h2>
    
    <!-- Filtry -->
    <div class="grid" style="margin-bottom: 20px;">
        <div class="form-group">
            <label for="filter-type">Typ transakcji</label>
            <select id="filter-type" onchange="loadTransactions()">
                <option value="">Wszystkie</option>
                <option value="sent">Wysłane</option>
                <option value="received">Otrzymane</option>
            </select>
        </div>

        <div class="form-group">
            <label for="filter-sort">Sortowanie</label>
            <select id="filter-sort" onchange="loadTransactions()">
                <option value="desc">Najnowsze pierwsze</option>
                <option value="asc">Najstarsze pierwsze</option>
            </select>
        </div>

        <div class="form-group">
            <label for="per-page">Wyników na stronę</label>
            <select id="per-page" onchange="loadTransactions()">
                <option value="10">10</option>
                <option value="15" selected>15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <!-- Statystyki -->
    <div class="grid" style="margin-bottom: 30px;">
        <div class="info-box">
            <h4 style="color: #ef4444; margin-bottom: 5px;">Wysłane</h4>
            <p style="font-size: 20px; font-weight: bold;" id="stat-sent">-</p>
        </div>
        <div class="info-box">
            <h4 style="color: #10b981; margin-bottom: 5px;">Otrzymane</h4>
            <p style="font-size: 20px; font-weight: bold;" id="stat-received">-</p>
        </div>
        <div class="info-box">
            <h4 style="color: #667eea; margin-bottom: 5px;">Saldo</h4>
            <p style="font-size: 20px; font-weight: bold;" id="stat-balance">-</p>
        </div>
    </div>

    <!-- Tabela transakcji -->
    <div id="transactions-table">
        <div class="loading">
            <div class="spinner"></div>
            Ładowanie transakcji...
        </div>
    </div>

    <!-- Paginacja -->
    <div id="pagination" style="margin-top: 20px; text-align: center;"></div>
</div>
@endsection

@section('scripts')
<script>
    let currentPage = 1;

    async function loadTransactions(page = 1) {
        currentPage = page;
        
        const type = document.getElementById('filter-type').value;
        const sortOrder = document.getElementById('filter-sort').value;
        const perPage = document.getElementById('per-page').value;
        
        const container = document.getElementById('transactions-table');
        showLoading(container);
        
        try {
            // Buduj URL z parametrami
            let url = `/transactions?page=${page}&per_page=${perPage}&sort_order=${sortOrder}`;
            if (type) {
                url += `&type=${type}`;
            }
            
            const response = await apiRequest(url);
            
            displayTransactions(response.transactions, response.pagination);
            
        } catch (error) {
            console.error('Transactions load error:', error);
            container.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 40px;">Błąd ładowania transakcji</p>';
        }
    }

    async function loadStatistics() {
        try {
            const stats = await apiRequest('/transactions/statistics');
            
            document.getElementById('stat-sent').textContent = 
                stats.statistics.total_sent.formatted_amount + ' (' + stats.statistics.total_sent.count + ' transakcji)';
            
            document.getElementById('stat-received').textContent = 
                stats.statistics.total_received.formatted_amount + ' (' + stats.statistics.total_received.count + ' transakcji)';
            
            document.getElementById('stat-balance').textContent = 
                stats.statistics.formatted_balance;
            
            // Pobierz dane użytkownika
            const userData = getUserData();
            if (userData) {
                document.getElementById('user-name').textContent = userData.first_name + ' ' + userData.last_name;
            }
            
        } catch (error) {
            console.error('Statistics load error:', error);
        }
    }

    function displayTransactions(transactions, pagination) {
        const container = document.getElementById('transactions-table');
        
        if (transactions.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">Brak transakcji do wyświetlenia</p>';
            document.getElementById('pagination').innerHTML = '';
            return;
        }

        let html = '<table><thead><tr>';
        html += '<th>Data</th>';
        html += '<th>Typ</th>';
        html += '<th>Kontrahent</th>';
        html += '<th>Tytuł</th>';
        html += '<th>Kwota</th>';
        html += '<th>Saldo po</th>';
        html += '<th>Status</th>';
        html += '</tr></thead><tbody>';

        transactions.forEach(transaction => {
            const isSent = transaction.type === 'sent';
            
            html += '<tr>';
            html += `<td>${formatDate(transaction.executed_at)}</td>`;
            html += `<td><span class="badge ${isSent ? 'badge-danger' : 'badge-success'}">
                        ${isSent ? '↑ Wysłane' : '↓ Otrzymane'}
                     </span></td>`;
            html += `<td>
                        <strong>${transaction.counterparty.name}</strong><br>
                        <small style="color: #999;">${transaction.counterparty.account_number}</small>
                     </td>`;
            html += `<td>${transaction.title || '-'}</td>`;
            html += `<td style="font-weight: bold; color: ${isSent ? '#ef4444' : '#10b981'};">
                        ${transaction.formatted_amount}
                     </td>`;
            html += `<td>${formatCurrency(transaction.balance_after)}</td>`;
            html += `<td><span class="badge badge-success">${transaction.status}</span></td>`;
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;

        // Wyświetl paginację
        displayPagination(pagination);
    }

    function displayPagination(pagination) {
        const container = document.getElementById('pagination');
        
        if (pagination.last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<div style="display: flex; gap: 10px; justify-content: center; align-items: center;">';
        
        // Przycisk poprzednia strona
        if (pagination.current_page > 1) {
            html += `<button class="btn btn-secondary" onclick="loadTransactions(${pagination.current_page - 1})">
                        ← Poprzednia
                     </button>`;
        }

        // Informacja o stronie
        html += `<span style="padding: 0 20px;">
                    Strona ${pagination.current_page} z ${pagination.last_page}
                 </span>`;

        // Przycisk następna strona
        if (pagination.current_page < pagination.last_page) {
            html += `<button class="btn btn-secondary" onclick="loadTransactions(${pagination.current_page + 1})">
                        Następna →
                     </button>`;
        }

        html += '</div>';
        html += `<p style="text-align: center; color: #999; margin-top: 10px; font-size: 14px;">
                    Wyświetlono ${pagination.from || 0}-${pagination.to || 0} z ${pagination.total} transakcji
                 </p>`;

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

    // Załaduj dane przy starcie
    loadStatistics();
    loadTransactions();
</script>
@endsection
