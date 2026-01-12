<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Aplikacja Bankowa')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .navbar {
            background: white;
            padding: 15px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .navbar-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .navbar-menu a {
            color: #333;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .navbar-menu a:hover {
            background: #f0f0f0;
        }

        .navbar-menu .logout-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .navbar-menu .logout-btn:hover {
            background: #dc2626;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }

        .info-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            margin-bottom: 20px;
        }

        .info-box h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .info-box p {
            color: #666;
            line-height: 1.6;
        }

        .balance-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }

        .balance-display h3 {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .balance-display .amount {
            font-size: 48px;
            font-weight: bold;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-card h4 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e5e7eb;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        table tr:hover {
            background: #f9fafb;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .text-center {
            text-align: center;
        }

        .text-link {
            color: #667eea;
            text-decoration: none;
        }

        .text-link:hover {
            text-decoration: underline;
        }

        .hidden {
            display: none;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="container">
        @yield('content')
    </div>

    <script>
        // Funkcje pomocnicze
        const API_BASE_URL = '/api';

        function getToken() {
            return localStorage.getItem('auth_token');
        }

        function setToken(token) {
            localStorage.setItem('auth_token', token);
        }

        function removeToken() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_data');
        }

        function saveUserData(user) {
            localStorage.setItem('user_data', JSON.stringify(user));
        }

        function getUserData() {
            const data = localStorage.getItem('user_data');
            return data ? JSON.parse(data) : null;
        }

        function isLoggedIn() {
            return !!getToken();
        }

        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => alertDiv.remove(), 5000);
        }

        function showLoading(element) {
            element.innerHTML = '<div class="loading"><div class="spinner"></div>Ładowanie...</div>';
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('pl-PL', {
                style: 'currency',
                currency: 'PLN'
            }).format(amount);
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleString('pl-PL');
        }

        async function apiRequest(endpoint, method = 'GET', data = null) {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                }
            };

            const token = getToken();
            if (token) {
                options.headers['Authorization'] = `Bearer ${token}`;
            }

            if (data && method !== 'GET') {
                options.body = JSON.stringify(data);
            }

            try {
                const response = await fetch(API_BASE_URL + endpoint, options);
                const result = await response.json();

                if (!response.ok) {
                    throw result;
                }

                return result;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        }

        // Sprawdzenie czy użytkownik jest zalogowany na chronionej stronie
        function checkAuth() {
            const publicPages = ['/login', '/register'];
            const currentPath = window.location.pathname;
            
            if (!publicPages.includes(currentPath) && !isLoggedIn()) {
                window.location.href = '/login';
            }
        }

        // Uruchom przy ładowaniu strony
        document.addEventListener('DOMContentLoaded', () => {
            checkAuth();
        });
    </script>

    @yield('scripts')
</body>
</html>
