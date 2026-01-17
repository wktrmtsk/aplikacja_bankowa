@extends('layouts.app')

@section('title', 'Zarządzanie Pracownikami')

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
        <h2>👔 Zarządzanie Pracownikami</h2>
        <button class="btn btn-primary" onclick="showAddEmployeeModal()">
            ➕ Dodaj pracownika
        </button>
    </div>

    <!-- Wyszukiwanie -->
    <div class="form-group">
        <input type="text" id="search" placeholder="Szukaj pracownika..." onkeyup="searchEmployees()">
    </div>

    <!-- Lista pracowników -->
    <div id="employees-list">
        <div class="loading">
            <div class="spinner"></div>
            Ładowanie pracowników...
        </div>
    </div>

    <!-- Paginacja -->
    <div id="pagination"></div>
</div>

<!-- Modal dodawania pracownika -->
<div id="add-employee-modal" class="modal hidden">
    <div class="modal-content">
        <h2>Dodaj nowego pracownika</h2>
        
        <div id="modal-error" class="alert alert-error hidden"></div>
        <div id="modal-success" class="alert alert-success hidden"></div>
        
        <form id="add-employee-form">
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
                <button type="submit" class="btn btn-primary">Utwórz pracownika</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Anuluj</button>
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

.employee-card {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #667eea;
}

.employee-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.employee-actions {
    display: flex;
    gap: 10px;
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

    async function loadEmployees(page = 1) {
        currentPage = page;
        const container = document.getElementById('employees-list');
        showLoading(container);

        try {
            const search = document.getElementById('search').value;
            let url = `/admin/employees?page=${page}&per_page=10`;
            if (search) url += `&search=${encodeURIComponent(search)}`;

            console.log('Fetching employees from:', url);
            const response = await apiRequest(url);
            console.log('Response:', response);
            displayEmployees(response.employees, response.pagination);

        } catch (error) {
            console.error('Error details:', error);
            
            let errorMessage = 'Błąd ładowania pracowników';
            if (error.message && error.message.includes('Brak uprawnień')) {
                errorMessage = 'Brak uprawnień administratora! Zaloguj się jako admin.';
                setTimeout(() => window.location.href = '/login', 2000);
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            container.innerHTML = `<p style="text-align: center; color: #ef4444; padding: 40px;">${errorMessage}</p>`;
        }
    }

    function displayEmployees(employees, pagination) {
        const container = document.getElementById('employees-list');

        if (employees.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">Brak pracowników</p>';
            return;
        }

        let html = '';
        employees.forEach(emp => {
            html += `
                <div class="employee-card">
                    <div class="employee-header">
                        <div>
                            <h3 style="margin: 0;">${emp.full_name}</h3>
                            <p style="color: #666; margin: 5px 0;">${emp.email}</p>
                        </div>
                        <div>
                            <span class="badge ${emp.status === 'active' ? 'badge-success' : 'badge-danger'}">
                                ${emp.status}
                            </span>
                        </div>
                    </div>
                    <div class="grid" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div>
                            <small style="color: #666;">Telefon:</small><br>
                            <strong>${emp.phone || '-'}</strong>
                        </div>
                        <div>
                            <small style="color: #666;">Klientów:</small><br>
                            <strong>${emp.clients_count}</strong>
                        </div>
                        <div>
                            <small style="color: #666;">Saldo:</small><br>
                            <strong>${formatCurrency(emp.balance)}</strong>
                        </div>
                    </div>
                    <div class="employee-actions" style="margin-top: 15px;">
                        <button class="btn btn-secondary btn-sm" onclick="viewEmployee(${emp.id})">
                            👁️ Szczegóły
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="assignClients(${emp.id})">
                            👥 Przypisz klientów
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="deleteEmployee(${emp.id}, '${emp.full_name}')">
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
            html += `<button class="btn btn-secondary" onclick="loadEmployees(${pagination.current_page - 1})">← Poprzednia</button>`;
        }

        html += `<span style="padding: 10px;">Strona ${pagination.current_page} z ${pagination.last_page}</span>`;

        if (pagination.current_page < pagination.last_page) {
            html += `<button class="btn btn-secondary" onclick="loadEmployees(${pagination.current_page + 1})">Następna →</button>`;
        }

        html += '</div>';
        container.innerHTML = html;
    }

    function searchEmployees() {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => loadEmployees(1), 500);
    }

    function showAddEmployeeModal() {
        document.getElementById('add-employee-modal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('add-employee-modal').classList.add('hidden');
        document.getElementById('add-employee-form').reset();
        document.getElementById('modal-error').classList.add('hidden');
        document.getElementById('modal-success').classList.add('hidden');
    }

    document.getElementById('add-employee-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        const errorDiv = document.getElementById('modal-error');
        const successDiv = document.getElementById('modal-success');
        
        errorDiv.classList.add('hidden');
        successDiv.classList.add('hidden');
        
        try {
            const response = await apiRequest('/admin/employees', 'POST', data);
            
            successDiv.textContent = response.message;
            successDiv.classList.remove('hidden');
            
            setTimeout(() => {
                closeModal();
                loadEmployees(currentPage);
            }, 1500);
            
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

    async function deleteEmployee(id, name) {
        if (!confirm(`Czy na pewno chcesz usunąć pracownika: ${name}?`)) return;
        
        try {
            await apiRequest(`/admin/employees/${id}`, 'DELETE');
            showAlert('Pracownik został usunięty', 'success');
            loadEmployees(currentPage);
        } catch (error) {
            showAlert(error.message || 'Błąd podczas usuwania', 'error');
        }
    }

    function viewEmployee(id) {
        window.location.href = `/admin/users/${id}`;
    }

    function assignClients(employeeId) {
        // TODO: Implementacja przypisywania klientów
        alert('Funkcja przypisywania klientów - do zaimplementowania w następnej wersji');
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

    // Załaduj pracowników przy starcie
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
        
        // Załaduj pracowników
        loadEmployees();
    });
</script>
@endsection
