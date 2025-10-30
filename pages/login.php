<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN MANAJEMEN GUDANG </title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo-container">
                <i class="fas fa-warehouse fa-3x"></i>
                <h1>Warehouse Management</h1>
            </div>
            <div id="loginAlert"></div>
            <form id="loginForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-container">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" placeholder="Masukkan email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" placeholder="Masukkan password" required>
                    </div>
                </div>
                <div class="form-group remember-me">
                    <label class="checkbox-label">
                        <input type="checkbox" id="rememberMe">
                        <span class="checkmark"></span>
                        Ingat saya
                    </label>
                </div>
                <button type="submit">Masuk Sekarang</button>
                <div class="link-container">
                    <a href="forgot_password.php" class="link-button">🔑 Lupa password?</a>
                    <a href="register.php" class="link-button register-link">📝 Daftar Akun Baru</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '../api/';
        const loginForm = document.getElementById('loginForm');
        const loginAlert = document.getElementById('loginAlert');

        function showAlert(message, type = 'error') {
            loginAlert.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        }

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();

            try {
                const response = await fetch(API_BASE + 'auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const result = await response.json();

                if (result.success) {
                    showAlert('Login berhasil! Mengarahkan...', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (err) {
                showAlert('Terjadi kesalahan koneksi ke server', 'error');
            }
        });
    </script>
</body>
</html>
