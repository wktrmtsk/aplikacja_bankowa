@extends('layouts.app')

@section('title', 'Transakcje klientów - Pracownik')

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
    <h2>💸 Transakcje moich klientów</h2>
    
    <!-- Filtr po kliencie -->
    <div class="form-group" style="max-width: 400px; margin-top: 20px;">
        <label>Filtruj po kliencie</label>
        <select id="client-filter" onchange="loadTransactions(1)">
            <option value="">Wszyscy klienci</option>
        </select>
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
}

.transaction-row:hover {
    background: #f3f4f6;
}

.my-client {
    background: #dbeafe;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    color: #1e40af;
    font-weight: 500;
}
</style>
@endsection

@section('scripts')
<script>
    let currentPage = 1;
    let clients = [];

    async function loadClients() {
        try {
            const response = await apiRequest('/employee/clients?per_page=100');
            clients = response.clients;
            
            const select = document.getElementById('client-filter');
            clients.forEach(client => {
                const option = document.createElement('option');
                option.value = client.id;
                option.textContent = client.full_name;
                select.appendChild(option);
            });
            
        } catch (error) {
            console.error('Error loading clients:', error);
        }
    }

    async function loadTransactions(page = 1) {
        currentPage = page;
        const container = document.getElementById('transactions-list');
        showLoading(container);

        try {
            const clientId = document.getElementById('client-filter').value;
            
            let url = `/employee/transactions?page=${page}&per_page=15`;
            if (clientId) url += `&client_id=${clientId}`;

            console.log('Fetching transactions from:', url);
            const response = await apiRequest(url);
            console.log('Response:', response);
            
            displayTransactions(response.transactions, response.pagination);

        } catch (error) {
            console.error('Error details:', error);
            
            let errorMessage = 'Błąd ładowania transakcji';
            if (error.message && error.message.includes('Brak uprawnień')) {
                errorMessage = 'Brak uprawnień pracownika!';
                setTimeout(() => window.location.href = '/login', 2000);
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            container.innerHTML = `<p style="text-align: center; color: #ef4444; padding: 40px;">${errorMessage}</p>`;
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
        html += '</tr></thead><tbody>';

        transactions.forEach(t => {
            html += '<tr>';
            html += `<td>${formatDate(t.executed_at)}</td>`;
            html += `<td><small>${t.transaction_number}</small></td>`;
            html += `<td>
                <strong>${t.sender.name}</strong>
                ${t.sender.is_my_client ? '<br><span class="my-client">Mój klient</span>' : ''}
            </td>`;
            html += `<td>
                <strong>${t.recipient.name}</strong>
                ${t.recipient.is_my_client ? '<br><span class="my-client">Mój klient</span>' : ''}
            </td>`;
            html += `<td style="font-weight: bold; color: #10b981;">${formatCurrency(t.amount)} PLN</td>`;
            html += `<td>${t.title}</td>`;
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
        loadTransactions();
    });
</script>
@endsection
