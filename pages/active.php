<?php
// pages/activate.php - Halaman Aktivasi Akun
require_once '../config.php';

$message = '';
$success = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $db = Database::getInstance()->getConnection();
    $token = $db->real_escape_string($token);
    
    $result = $db->query("SELECT user_id, expires_at FROM activation_tokens WHERE token = '$token'");
    
    if ($result->num_rows > 0) {
        $tokenData = $result->fetch_assoc();
        
        if (strtotime($tokenData['expires_at']) < time()) {
            $message = 'Token aktivasi sudah expired! Silakan registrasi ulang.';
            $success = false;
        } else {
            $userId = $tokenData['user_id'];
            $db->query("UPDATE users SET status = 'ACTIVE' WHERE id = $userId");
            $db->query("DELETE FROM activation_tokens WHERE token = '$token'");
            
            $userResult = $db->query("SELECT email FROM users WHERE id = $userId");
            $userData = $userResult->fetch_assoc();
            
            $message = 'Akun berhasil diaktivasi! Email: ' . $userData['email'];
            $success = true;
        }
    } else {
        $message = 'Token aktivasi tidak valid!';
        $success = false;
    }
} else {
    $message = 'Token aktivasi tidak ditemukan!';
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun - Warehouse Management</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <h1>✉️ Aktivasi Akun</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <p><strong>✅ <?php echo $message; ?></strong></p>
                    <p>Anda sekarang dapat login dengan email Anda.</p>
                </div>
                <a href="login.php" style="display: block; width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 20px;">
                    Ke Halaman Login
                </a>
            <?php else: ?>
                <div class="alert alert-error">
                    <p><strong>❌ <?php echo $message; ?></strong></p>
                </div>
                <a href="register.php" style="display: block; width: 100%; padding: 14px; background: #6c757d; color: white; text-align: center; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 20px;">
                    Registrasi Ulang
                </a>
            <?php endif; ?>
            
            <a href="login.php" class="link-button" style="margin-top: 15px;">Kembali ke Login</a>
        </div>
    </div>
</body>
</html>