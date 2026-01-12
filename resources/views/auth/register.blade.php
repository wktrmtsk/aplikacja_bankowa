@extends('layouts.app')

@section('title', 'Rejestracja - Aplikacja Bankowa')

@section('content')
<div style="max-width: 600px; margin: 50px auto;">
    <div class="card">
        <h2 class="text-center">🏦 Zarejestruj się</h2>
        
        <div id="error-message" class="alert alert-error hidden"></div>
        <div id="success-message" class="alert alert-success hidden"></div>
        
        <form id="register-form">
            <div class="grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label for="first_name">Imię *</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Nazwisko *</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label for="password">Hasło *</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Potwierdź hasło *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8">
                </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label for="pesel">PESEL *</label>
                    <input type="text" id="pesel" name="pesel" required maxlength="11" pattern="[0-9]{11}">
                </div>

                <div class="form-group">
                    <label for="birth_date">Data urodzenia *</label>
                    <input type="date" id="birth_date" name="birth_date" required>
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Telefon</label>
                <input type="tel" id="phone" name="phone" placeholder="+48123456789">
            </div>

            <div class="form-group">
                <label for="address">Adres</label>
                <input type="text" id="address" name="address" placeholder="ul. Kwiatowa 15">
            </div>

            <div class="grid" style="grid-template-columns: 2fr 1fr;">
                <div class="form-group">
                    <label for="city">Miasto</label>
                    <input type="text" id="city" name="city" placeholder="Warszawa">
                </div>

                <div class="form-group">
                    <label for="postal_code">Kod pocztowy</label>
                    <input type="text" id="postal_code" name="postal_code" placeholder="00-001">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Zarejestruj się
            </button>
        </form>

        <p class="text-center" style="margin-top: 20px;">
            Masz już konto? <a href="/login" class="text-link">Zaloguj się</a>
        </p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('register-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = {
            first_name: document.getElementById('first_name').value,
            last_name: document.getElementById('last_name').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            password_confirmation: document.getElementById('password_confirmation').value,
            pesel: document.getElementById('pesel').value,
            birth_date: document.getElementById('birth_date').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value,
            city: document.getElementById('city').value,
            postal_code: document.getElementById('postal_code').value,
            country: 'Polska'
        };
        
        const errorDiv = document.getElementById('error-message');
        const successDiv = document.getElementById('success-message');
        const submitBtn = e.target.querySelector('button[type="submit"]');
        
        // Ukryj poprzednie komunikaty
        errorDiv.classList.add('hidden');
        successDiv.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Rejestracja...';
        
        try {
            const response = await apiRequest('/register', 'POST', formData);
            
            // Zapisz token i dane użytkownika
            setToken(response.access_token);
            saveUserData(response.user);
            
            successDiv.textContent = 'Rejestracja zakończona pomyślnie! Przekierowanie...';
            successDiv.classList.remove('hidden');
            
            // Przekieruj do dashboardu po 2 sekundach
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 2000);
            
        } catch (error) {
            console.error('Registration error:', error);
            
            let errorMessage = 'Wystąpił błąd podczas rejestracji.';
            
            if (error.errors) {
                errorMessage = Object.values(error.errors).flat().join('. ');
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            errorDiv.textContent = errorMessage;
            errorDiv.classList.remove('hidden');
            
            submitBtn.disabled = false;
            submitBtn.textContent = 'Zarejestruj się';
        }
    });

    // Jeśli już zalogowany, przekieruj do dashboardu
    if (isLoggedIn()) {
        window.location.href = '/dashboard';
    }
</script>
@endsection
