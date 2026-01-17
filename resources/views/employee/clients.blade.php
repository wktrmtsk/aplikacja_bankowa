@extends('layouts.app')

@section('title', 'Moi Klienci - Pracownik')

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
    <h2>👥 Moi Klienci</h2>
    
    <!-- Filtry -->
    <div class="grid" style="grid-template-columns: 2fr 1fr; margin-top: 20px;">
        <div class="form-group">
            <input type="text" id="search" placeholder="Szukaj klienta..." onkeyup="searchClients()">
        </div>
        <div class="form-group">
            <select id="filter-status" onchange="loadClients(1)">
                <option value="">Wszyscy</option>
                <option value="active">Aktywni</option>
                <option value="blocked">Zablokowani</option>
            </select>
        </div>
    </div>
</div>

<!-- Lista klientów -->
<div id="clients-list">
    <div class="loading">
        <div class="spinner"></div>
        Ładowanie klientów...
    </div>
</div>

<!-- Paginacja -->
<div id="pagination"></div>

<style>
.client-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #3b82f6;
}

.client-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.client-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.btn-sm {
    padding: 8px 15px;
    font-size: 14px;
}
</style>
@endsection

@section('scripts')
<script>
    let currentPage = 1;

    async function loadClients(page = 1) {
        currentPage = page;
        const container = document.getElementById('clients-list');
        showLoading(container);

        try {
            const search = document.getElementById('search').value;
            const status = document.getElementById('filter-status').value;
            
            let url = `/employee/clients?page=${page}&per_page=10`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (status) url += `&status=${status}`;

            const response = await apiRequest(url);
            displayClients(response.clients, response.pagination);

        } catch (error) {
            console.error('Error:', error);
            container.innerHTML = '<div class="card"><p style="text-align: center; color: #ef4444;">Błąd ładowania klientów</p></div>';
        }
    }

    function displayClients(clients, pagination) {
        const container = document.getElementById('clients-list');

        if (clients.length === 0) {
            container.innerHTML = '<div class="card"><p style="text-align: center; color: #999; padding: 40px;">Nie masz przypisanych klientów</p></div>';
            return;
        }

        let html = '';
        clients.forEach(client => {
            html += `
                <div class="client-card">
                    <div class="client-header">
                        <div>
                            <h3 style="margin: 0;">${client.full_name}</h3>
                            <p style="color: #666; margin: 5px 0;">${client.email}</p>
                            <p style="color: #999; font-size: 13px;">${client.account_number}</p>
                        </div>
                        <div>
                            <span class="badge ${client.status === 'active' ? 'badge-success' : 'badge-danger'}">
                                ${client.status}
                            </span>
                        </div>
                    </div>
                    <div class="grid" style="grid-template-columns: repeat(3, 1fr);">
                        <div>
                            <small style="color: #666;">Telefon:</small><br>
                            <strong>${client.phone || '-'}</strong>
                        </div>
                        <div>
                            <small style="color: #666;">Saldo:</small><br>
                            <strong style="color: #10b981;">${formatCurrency(client.balance)}</strong>
                        </div>
                        <div>
                            <small style="color: #666;">Data rejestracji:</small><br>
                            <strong>${formatDate(client.created_at)}</strong>
                        </div>
                    </div>
                    <div class="client-actions">
                        <a href="/employee/clients/${client.id}" class="btn btn-primary btn-sm">
                            👁️ Szczegóły
                        </a>
                        <button class="btn btn-secondary btn-sm" onclick="quickDeposit(${client.id}, '${client.full_name}')">
                            💵 Wpłata
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="quickTransfer(${client.id}, '${client.full_name}')">
                            💸 Przelew
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="toggleStatus(${client.id}, '${client.full_name}', '${client.status}')">
                            ${client.status === 'active' ? '🔒 Zablokuj' : '🔓 Odblokuj'}
                        </button>
                    </div>
                </div>
            `;
        });

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
            html += `<button class="btn btn-secondary" onclick="loadClients(${pagination.current_page - 1})">← Poprzednia</button>`;
        }

        html += `<span style="padding: 10px;">Strona ${pagination.current_page} z ${pagination.last_page}</span>`;

        if (pagination.current_page < pagination.last_page) {
            html += `<button class="btn btn-secondary" onclick="loadClients(${pagination.current_page + 1})">Następna →</button>`;
        }

        html += '</div>';
        container.innerHTML = html;
    }

    function searchClients() {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => loadClients(1), 500);
    }

    function quickDeposit(clientId, clientName) {
        window.location.href = `/employee/deposit?client_id=${clientId}`;
    }

    function quickTransfer(clientId, clientName) {
        window.location.href = `/employee/transfer?client_id=${clientId}`;
    }

    async function toggleStatus(clientId, clientName, currentStatus) {
        const action = currentStatus === 'active' ? 'zablokować' : 'odblokować';
        
        if (!confirm(`Czy na pewno chcesz ${action} klienta: ${clientName}?`)) {
            return;
        }
        
        try {
            const response = await apiRequest(`/employee/clients/${clientId}/toggle-status`, 'POST');
            showAlert(response.message, 'success');
            loadClients(currentPage);
        } catch (error) {
            showAlert(error.message || 'Błąd podczas zmiany statusu', 'error');
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

    // Załaduj klientów przy starcie
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
    });
</script>
@endsection
