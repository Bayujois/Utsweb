<?php
// pages/reset-password.php - Reset Password
require_once '../config.php';

$token = $_GET['token'] ?? '';
$validToken = false;
$email = '';

if (!empty($token)) {
    $db = Database::getInstance()->getConnection();
    $token_escaped = $db->real_escape_string($token);
    
    $result = $db->query("SELECT user_id, expires_at FROM reset_password_tokens WHERE token = '$token_escaped'");
    
    if ($result->num_rows > 0) {
        $tokenData = $result->fetch_assoc();
        
        if (strtotime($tokenData['expires_at']) >= time()) {
            $validToken = true;
            
            $userResult = $db->query("SELECT email FROM users WHERE id = {$tokenData['user_id']}");
            $userData = $userResult->fetch_assoc();
            $email = $userData['email'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - MANAJEMEN GUDANG</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <h1>🔒 Reset Password</h1>
            
            <?php if ($validToken): ?>
                <p class="subtitle">Buat password baru untuk: <strong><?php echo $email; ?></strong></p>
                
                <div id="resetAlert"></div>
                
                <form id="resetForm">
                    <input type="hidden" id="resetToken" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label for="newPassword">Password Baru</label>
                        <input type="password" id="newPassword" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirmNewPassword">Konfirmasi Password Baru</label>
                        <input type="password" id="confirmNewPassword" required>
                    </div>
                    <button type="submit">Reset Password</button>
                </form>
            <?php else: ?>
                <div class="alert alert-error">
                    <p><strong>❌ Token reset password tidak valid atau sudah expired!</strong></p>
                </div>
                <a href="forgot_password.php" style="display: block; width: 100%; padding: 14px; background: #6c757d; color: white; text-align: center; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 20px;">
                    Request Link Baru
                </a>
            <?php endif; ?>
            
            <a href="login.php" class="link-button" style="margin-top: 15px;">Kembali ke Login</a>
        </div>
    </div>

    <script>
        const API_BASE = '../api/';

        function showAlert(message, type) {
            const alertDiv = document.getElementById('resetAlert');
            alertDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        }

        document.getElementById('resetForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const token = document.getElementById('resetToken').value;
            const password = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmNewPassword').value;

            if (password !== confirmPassword) {
                showAlert('Password tidak cocok!', 'error');
                return;
            }

            try {
                const response = await fetch(API_BASE + 'auth.php?action=reset-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token, password })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(result.message + ' Redirecting...', 'success');
                    setTimeout(() => { window.location.href = 'login.php'; }, 2000);
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