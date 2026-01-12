@extends('layouts.app')

@section('title', 'Profil - Aplikacja Bankowa')

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

<div class="grid">
    <!-- Dane osobowe -->
    <div class="card">
        <h2>👤 Dane osobowe</h2>
        
        <div id="success-message" class="alert alert-success hidden"></div>
        <div id="error-message" class="alert alert-error hidden"></div>
        
        <form id="profile-form">
            <div class="form-group">
                <label for="first_name">Imię</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>

            <div class="form-group">
                <label for="last_name">Nazwisko</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="phone">Telefon</label>
                <input type="tel" id="phone" name="phone">
            </div>

            <div class="form-group">
                <label for="address">Adres</label>
                <input type="text" id="address" name="address">
            </div>

            <div class="form-group">
                <label for="city">Miasto</label>
                <input type="text" id="city" name="city">
            </div>

            <div class="form-group">
                <label for="postal_code">Kod pocztowy</label>
                <input type="text" id="postal_code" name="postal_code">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Zapisz zmiany
            </button>
        </form>
    </div>

    <!-- Informacje o koncie -->
    <div>
        <div class="card">
            <h2>🏦 Informacje o koncie</h2>
            <div id="account-info">
                <div class="loading">
                    <div class="spinner"></div>
                    Ładowanie...
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h2>🔒 Zmiana hasła</h2>
            
            <div id="password-success" class="alert alert-success hidden"></div>
            <div id="password-error" class="alert alert-error hidden"></div>
            
            <form id="password-form">
                <div class="form-group">
                    <label for="current_password">Aktualne hasło</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">Nowe hasło</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                </div>

                <div class="form-group">
                    <label for="new_password_confirmation">Potwierdź nowe hasło</label>
                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" required minlength="8">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Zmień hasło
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function loadProfile() {
        try {
            const data = await apiRequest('/profile');
            const profile = data.profile;

            // Wypełnij formularz
            document.getElementById('first_name').value = profile.first_name;
            document.getElementById('last_name').value = profile.last_name;
            document.getElementById('email').value = profile.email;
            document.getElementById('phone').value = profile.phone || '';
            document.getElementById('address').value = profile.address || '';
            document.getElementById('city').value = profile.city || '';
            document.getElementById('postal_code').value = profile.postal_code || '';

            // Wyświetl nazwisko w navbar
            document.getElementById('user-name').textContent = profile.full_name;

            // Wyświetl informacje o koncie
            displayAccountInfo(profile);

        } catch (error) {
            console.error('Profile load error:', error);
        }
    }

    function displayAccountInfo(profile) {
        const container = document.getElementById('account-info');
        
        const html = `
            <div class="info-box">
                <p><strong>Numer konta:</strong><br>${profile.account_number}</p>
            </div>
            <div class="info-box">
                <p><strong>PESEL:</strong> ${profile.pesel}</p>
                <p><strong>Data urodzenia:</strong> ${profile.birth_date}</p>
            </div>
            <div class="info-box">
                <p><strong>Saldo:</strong><br>
                   <span style="font-size: 24px; font-weight: bold; color: #667eea;">
                       ${profile.account.formatted_balance}
                   </span>
                </p>
                <p><strong>Typ konta:</strong> ${profile.account.account_type}</p>
                <p><strong>Data otwarcia:</strong> ${profile.account.opened_at}</p>
            </div>
            <div class="info-box">
                <p><strong>Status konta:</strong> 
                   <span class="badge badge-success">${profile.status}</span>
                </p>
            </div>
        `;
        
        container.innerHTML = html;
    }

    // Aktualizacja profilu
    document.getElementById('profile-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = {
            first_name: document.getElementById('first_name').value,
            last_name: document.getElementById('last_name').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value,
            city: document.getElementById('city').value,
            postal_code: document.getElementById('postal_code').value,
        };
        
        const successDiv = document.getElementById('success-message');
        const errorDiv = document.getElementById('error-message');
        const submitBtn = e.target.querySelector('button[type="submit"]');
        
        successDiv.classList.add('hidden');
        errorDiv.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Zapisywanie...';
        
        try {
            const response = await apiRequest('/profile', 'PUT', formData);
            
            // Zaktualizuj zapisane dane
            saveUserData(response.profile);
            
            successDiv.textContent = 'Profil został zaktualizowany pomyślnie!';
            successDiv.classList.remove('hidden');
            
            submitBtn.disabled = false;
            submitBtn.textContent = 'Zapisz zmiany';
            
            // Odśwież dane
            loadProfile();
            
        } catch (error) {
            console.error('Update error:', error);
            
            let errorMessage = 'Wystąpił błąd podczas aktualizacji.';
            if (error.errors) {
                errorMessage = Object.values(error.errors).flat().join('. ');
            }
            
            errorDiv.textContent = errorMessage;
            errorDiv.classList.remove('hidden');
            
            submitBtn.disabled = false;
            submitBtn.textContent = 'Zapisz zmiany';
        }
    });

    // Zmiana hasła
    document.getElementById('password-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = {
            current_password: document.getElementById('current_password').value,
            new_password: document.getElementById('new_password').value,
            new_password_confirmation: document.getElementById('new_password_confirmation').value,
        };
        
        const successDiv = document.getElementById('password-success');
        const errorDiv = document.getElementById('password-error');
        const submitBtn = e.target.querySelector('button[type="submit"]');
        
        successDiv.classList.add('hidden');
        errorDiv.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Zmiana hasła...';
        
        try {
            await apiRequest('/profile/change-password', 'POST', formData);
            
            successDiv.textContent = 'Hasło zostało zmienione pomyślnie!';
            successDiv.classList.remove('hidden');
            
            // Wyczyść formularz
            e.target.reset();
            
            submitBtn.disabled = false;
            submitBtn.textContent = 'Zmień hasło';
            
        } catch (error) {
            console.error('Password change error:', error);
            
            let errorMessage = 'Wystąpił błąd podczas zmiany hasła.';
            if (error.errors) {
                errorMessage = Object.values(error.errors).flat().join('. ');
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            errorDiv.textContent = errorMessage;
            errorDiv.classList.remove('hidden');
            
            submitBtn.disabled = false;
            submitBtn.textContent = 'Zmień hasło';
        }
    });

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

    // Załaduj profil przy starcie
    loadProfile();
</script>
@endsection
