<?php
// pages/forgot-password.php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Warehouse Management</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <h1>🔑 Lupa Password</h1>
            <p class="subtitle">Masukkan email Anda untuk reset password</p>
            
            <div id="forgotAlert"></div>
            
            <form id="forgotForm">
                <div class="form-group">
                    <label for="forgotEmail">Email</label>
                    <input type="email" id="forgotEmail" required>
                </div>
                <button type="submit">Kirim Link Reset</button>
                <a href="login.php" class="btn-secondary" style="display: block; text-align: center; margin-top: 10px; text-decoration: none; color: white; padding: 14px; border-radius: 8px;">Kembali ke Login</a>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '../api/';

        function showAlert(message, type) {
            const alertDiv = document.getElementById('forgotAlert');
            alertDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        }

        document.getElementById('forgotForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('forgotEmail').value;

            try {
                const response = await fetch(API_BASE + 'auth.php?action=forgot-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });

                const result = await response.json();

                if (result.success) {
                    let message = result.message;
                    if (result.reset_link) {
                        message += '<br><br><strong>Link Reset Password (Development Mode):</strong><br>';
                        message += `<a href="${result.reset_link}" target="_blank" style="color: #667eea; word-break: break-all;">${result.reset_link}</a>`;
                        message += '<br><br><small>Di production, link ini akan dikirim via email.</small>';
                    }
                    showAlert(message, 'success');
                    document.getElementById('forgotForm').reset();
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