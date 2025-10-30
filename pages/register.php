<?php
// pages/register.php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - MANAJEMEN GUDANG </title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <h1>📝 Registrasi Admin Gudang</h1>
            <p class="subtitle">Buat akun baru Anda</p>
            
            <div id="registerAlert"></div>
            
            <form id="registerForm">
                <div class="form-group">
                    <label for="regName">Nama Lengkap</label>
                    <input type="text" id="regName" required>
                </div>
                <div class="form-group">
                    <label for="regEmail">Email</label>
                    <input type="email" id="regEmail" required>
                </div>
                <div class="form-group">
                    <label for="regPassword">Password</label>
                    <input type="password" id="regPassword" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="regConfirmPassword">Konfirmasi Password</label>
                    <input type="password" id="regConfirmPassword" required>
                </div>
                <button type="submit">Daftar</button>
                <a href="login.php" class="btn-secondary" style="display: block; text-align: center; margin-top: 10px; text-decoration: none; color: white; padding: 14px; border-radius: 8px;">Kembali ke Login</a>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '../api/';

        function showAlert(message, type) {
            const alertDiv = document.getElementById('registerAlert');
            alertDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        }

        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const name = document.getElementById('regName').value;
            const email = document.getElementById('regEmail').value;
            const password = document.getElementById('regPassword').value;
            const confirmPassword = document.getElementById('regConfirmPassword').value;

            if (password !== confirmPassword) {
                showAlert('Password tidak cocok!', 'error');
                return;
            }

            try {
                const response = await fetch(API_BASE + 'auth.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, email, password })
                });

                const result = await response.json();

                if (result.success) {
                    let message = result.message;
                    if (result.activation_link) {
                        message += '<br><br><strong>Link Aktivasi (Development Mode):</strong><br>';
                        message += `<a href="${result.activation_link}" target="_blank" style="color: #667eea; word-break: break-all;">${result.activation_link}</a>`;
                        message += '<br><br><small>Di production, link ini akan dikirim via email.</small>';
                    }
                    showAlert(message, 'success');
                    document.getElementById('registerForm').reset();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Terjadi kesalahan koneksi', 'error');
            }
        });
    </script>
</body>
</html>