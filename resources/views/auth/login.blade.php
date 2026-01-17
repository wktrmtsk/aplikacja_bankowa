@extends('layouts.app')

@section('title', 'Logowanie - Aplikacja Bankowa')

@section('content')
<div style="max-width: 450px; margin: 50px auto;">
    <div class="card">
        <h2 class="text-center">🏦 Zaloguj się</h2>
        
        <div id="error-message" class="alert alert-error hidden"></div>
        
        <form id="login-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="jan.kowalski@example.com">
            </div>

            <div class="form-group">
                <label for="password">Hasło</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Zaloguj się
            </button>
        </form>

        <p class="text-center" style="margin-top: 20px;">
            Nie masz konta? <a href="/register" class="text-link">Zarejestruj się</a>
        </p>

        <div class="info-box" style="margin-top: 20px;">
            <h3>📝 Dane testowe:</h3>
            <p><strong>Email:</strong> jan.kowalski@example.com</p>
            <p><strong>Hasło:</strong> password123</p>
            <p style="margin-top: 10px; font-size: 13px; color: #999;">
                (dostępne po uruchomieniu: php artisan db:seed)
            </p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const errorDiv = document.getElementById('error-message');
        const submitBtn = e.target.querySelector('button[type="submit"]');
        
        // Ukryj poprzednie błędy
        errorDiv.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Logowanie...';
        
        try {
            const response = await apiRequest('/login', 'POST', {
                email,
                password
            });
            
            // Zapisz token i dane użytkownika
            setToken(response.access_token);
            saveUserData(response.user);
            
            // Pobierz dane użytkownika z API aby sprawdzić role
            const meResponse = await apiRequest('/me', 'GET');
            
            // Sprawdź czy użytkownik ma rolę admin
            const isAdmin = meResponse.user.roles && meResponse.user.roles.some(role => role.name === 'admin');
            const isEmployee = meResponse.user.roles && meResponse.user.roles.some(role => role.name === 'employee');
            
            // Przekieruj do odpowiedniego dashboardu
            if (isAdmin) {
                window.location.href = '/admin/dashboard';
            } else if (isEmployee) {
                window.location.href = '/employee/dashboard';
            } else {
                window.location.href = '/dashboard';
            }
            
        } catch (error) {
            console.error('Login error:', error);
            errorDiv.textContent = error.message || 'Nieprawidłowy email lub hasło';
            errorDiv.classList.remove('hidden');
            
            submitBtn.disabled = false;
            submitBtn.textContent = 'Zaloguj się';
        }
    });

    // Jeśli już zalogowany, przekieruj do dashboardu
    if (isLoggedIn()) {
        window.location.href = '/dashboard';
    }
</script>
@endsection
