@extends('layouts.app')

@section('title', 'Zarządzanie Klientami')

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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>👥 Zarządzanie Klientami</h2>
        <button class="btn btn-primary" onclick="showAddClientModal()">
            ➕ Dodaj klienta
        </button>
    </div>

    <!-- Filtry -->
    <div class="grid" style="grid-template-columns: 2fr 1fr; margin-bottom: 20px;">
        <div class="form-group">
            <input type="text" id="search" placeholder="Szukaj klienta (imię, email, numer konta)..." onkeyup="searchClients()">
        </div>
        <div class="form-group">
            <select id="filter-employee" onchange="loadClients(1)">
                <option value="">Wszyscy klienci</option>
                <option value="true">Mają pracownika</option>
                <option value="false">Bez pracownika</option>
            </select>
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
</div>

<!-- Modal dodawania klienta -->
<div id="add-client-modal" class="modal hidden">
    <div class="modal-content">
        <h2>Dodaj nowego klienta</h2>
        
        <div id="modal-error" class="alert alert-error hidden"></div>
        <div id="modal-success" class="alert alert-success hidden"></div>
        
        <form id="add-client-form">
            <div class="grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Imię *</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Nazwisko *</label>
                    <input type="text" name="last_name" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Hasło *</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                <div class="form-group">
                    <label>Telefon</label>
                    <input type="tel" name="phone">
                </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>PESEL *</label>
                    <input type="text" name="pesel" required maxlength="11" pattern="[0-9]{11}">
                </div>
                <div class="form-group">
                    <label>Data urodzenia *</label>
                    <input type="date" name="birth_date" required>
                </div>
            </div>

            <div class="form-group">
                <label>Początkowe saldo (PLN)</label>
                <input type="number" name="initial_balance" step="0.01" min="0" value="0">
            </div>

            <div class="form-group">
                <label>Adres</label>
                <input type="text" name="address">
            </div>

            <div class="grid" style="grid-template-columns: 2fr 1fr;">
                <div class="form-group">
                    <label>Miasto</label>
                    <input type="text" name="city">
                </div>
                <div class="form-group">
                    <label>Kod pocztowy</label>
                    <input type="text" name="postal_code">
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Utwórz klienta</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Anuluj</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal korekty salda -->
<div id="adjust-balance-modal" class="modal hidden">
    <div class="modal-content" style="max-width: 400px;">
        <h2>Korekta salda</h2>
        
        <div id="adjust-error" class="alert alert-error hidden"></div>
        <div id="adjust-success" class="alert alert-success hidden"></div>
        
        <form id="adjust-balance-form">
            <input type="hidden" id="adjust-client-id">
            
            <p>Klient: <strong id="adjust-client-name"></strong></p>
            <p>Obecne saldo: <strong id="adjust-current-balance"></strong></p>
            
            <div class="form-group">
                <label>Kwota korekty (PLN) *</label>
                <input type="number" name="amount" step="0.01" required placeholder="np. 100 lub -50">
                <small style="color: #666;">Użyj wartości ujemnej aby odjąć</small>
            </div>

            <div class="form-group">
                <label>Opis korekty *</label>
                <input type="text" name="description" required placeholder="np. Korekta błędu systemowego">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Zatwierdź</button>
                <button type="button" class="btn btn-secondary" onclick="closeAdjustModal()">Anuluj</button>
            </div>
        </form>
    </div>
</div>

<style>
.hidden {
    display: none !important;
}

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

.client-card {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #10b981;
}

.client-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.client-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
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
            const hasEmployee = document.getElementById('filter-employee').value;
            
            let url = `/admin/clients?page=${page}&per_page=10`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (hasEmployee) url += `&has_employee=${hasEmployee}`;

            console.log('Fetching clients from:', url);
            const response = await apiRequest(url);
            console.log('Response:', response);
            displayClients(response.clients, response.pagination);

        } catch (error) {
            console.error('Error details:', error);
            
            let errorMessage = 'Błąd ładowania klientów';
            if (error.message && error.message.includes('Brak uprawnień')) {
                errorMessage = 'Brak uprawnień administratora! Zaloguj się jako admin.';
                setTimeout(() => window.location.href = '/login', 2000);
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            container.innerHTML = `<p style="text-align: center; color: #ef4444; padding: 40px;">${errorMessage}</p>`;
        }
    }

    function displayClients(clients, pagination) {
        const container = document.getElementById('clients-list');

        if (clients.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">Brak klientów</p>';
            return;
        }

        let html = '';
        clients.forEach(client => {
            const employeeNames = client.employees.map(e => e.name).join(', ') || 'Brak';
            
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
                    <div class="grid" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div>
                            <small style="color: #666;">Telefon:</small><br>
                            <strong>${client.phone || '-'}</strong>
                        </div>
                        <div>
                            <small style="color: #666;">Saldo:</small><br>
                            <strong style="color: #10b981;">${formatCurrency(client.balance)}</strong>
                        </div>
                        <div>
                            <small style="color: #666;">Pracownik:</small><br>
                            <strong>${employeeNames}</strong>
                        </div>
                    </div>
                    <div class="client-actions" style="margin-top: 15px;">
                        <button class="btn btn-secondary btn-sm" onclick="viewClient(${client.id})">
                            👁️ Szczegóły
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="showAdjustBalance(${client.id}, '${client.full_name}', ${client.balance})">
                            💰 Korekta salda
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="deleteClient(${client.id}, '${client.full_name}', ${client.balance})">
                            🗑️ Usuń
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

    function showAddClientModal() {
        document.getElementById('add-client-modal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('add-client-modal').classList.add('hidden');
        document.getElementById('add-client-form').reset();
        document.getElementById('modal-error').classList.add('hidden');
        document.getElementById('modal-success').classList.add('hidden');
    }

    document.getElementById('add-client-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        const errorDiv = document.getElementById('modal-error');
        const successDiv = document.getElementById('modal-success');
        
        errorDiv.classList.add('hidden');
        successDiv.classList.add('hidden');
        
        try {
            const response = await apiRequest('/admin/clients', 'POST', data);
            
            successDiv.textContent = response.message + ' (Nr konta: ' + response.client.account_number + ')';
            successDiv.classList.remove('hidden');
            
            setTimeout(() => {
                closeModal();
                loadClients(currentPage);
            }, 2000);
            
        } catch (error) {
            let errorMessage = 'Wystąpił błąd';
            if (error.errors) {
                errorMessage = Object.values(error.errors).flat().join(', ');
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            errorDiv.textContent = errorMessage;
            errorDiv.classList.remove('hidden');
        }
    });

    function showAdjustBalance(clientId, clientName, currentBalance) {
        document.getElementById('adjust-client-id').value = clientId;
        document.getElementById('adjust-client-name').textContent = clientName;
        document.getElementById('adjust-current-balance').textContent = formatCurrency(currentBalance);
        document.getElementById('adjust-balance-modal').classList.remove('hidden');
    }

    function closeAdjustModal() {
        document.getElementById('adjust-balance-modal').classList.add('hidden');
        document.getElementById('adjust-balance-form').reset();
        document.getElementById('adjust-error').classList.add('hidden');
        document.getElementById('adjust-success').classList.add('hidden');
    }

    document.getElementById('adjust-balance-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const clientId = document.getElementById('adjust-client-id').value;
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        const errorDiv = document.getElementById('adjust-error');
        const successDiv = document.getElementById('adjust-success');
        
        errorDiv.classList.add('hidden');
        successDiv.classList.add('hidden');
        
        try {
            const response = await apiRequest(`/admin/clients/${clientId}/adjust-balance`, 'POST', data);
            
            successDiv.textContent = `Saldo zmienione: ${formatCurrency(response.old_balance)} → ${formatCurrency(response.new_balance)}`;
            successDiv.classList.remove('hidden');
            
            setTimeout(() => {
                closeAdjustModal();
                loadClients(currentPage);
            }, 1500);
            
        } catch (error) {
            errorDiv.textContent = error.message || 'Błąd podczas korekty salda';
            errorDiv.classList.remove('hidden');
        }
    });

    async function deleteClient(id, name, balance) {
        if (balance > 0) {
            alert(`Nie można usunąć klienta z saldem ${formatCurrency(balance)}. Najpierw wyzeruj saldo.`);
            return;
        }
        
        if (!confirm(`Czy na pewno chcesz usunąć klienta: ${name}?`)) return;
        
        try {
            await apiRequest(`/admin/clients/${id}`, 'DELETE');
            showAlert('Klient został usunięty', 'success');
            loadClients(currentPage);
        } catch (error) {
            showAlert(error.message || 'Błąd podczas usuwania', 'error');
        }
    }

    function viewClient(id) {
        window.location.href = `/admin/users/${id}`;
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
        // Sprawdź czy użytkownik jest zalogowany
        if (!isLoggedIn()) {
            alert('Musisz być zalogowany!');
            window.location.href = '/login';
            return;
        }
        
        // Sprawdź dane użytkownika
        const userData = getUserData();
        console.log('User data:', userData);
        
        if (userData && userData.first_name) {
            document.getElementById('admin-name').textContent = userData.first_name;
        }
        
        // Załaduj klientów
        loadClients();
    });
</script>
@endsection
