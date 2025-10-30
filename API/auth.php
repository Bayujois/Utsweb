<?php
// api/auth.php - API Autentikasi

require_once '../config.php';

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// REGISTER
if ($method === 'POST' && $action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = $db->escape_string($data['name'] ?? '');
    $email = $db->escape_string(strtolower($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Semua field harus diisi'], 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Format email tidak valid'], 400);
    }
    
    if (strlen($password) < 6) {
        jsonResponse(['success' => false, 'message' => 'Password minimal 6 karakter'], 400);
    }
    
    $checkEmail = $db->query("SELECT id FROM users WHERE email = '$email'");
    if ($checkEmail->num_rows > 0) {
        jsonResponse(['success' => false, 'message' => 'Email sudah terdaftar'], 400);
    }
    
    $hashedPassword = hashPassword($password);
    $sql = "INSERT INTO users (name, email, password, status) VALUES ('$name', '$email', '$hashedPassword', 'INACTIVE')";
    
    if ($db->query($sql)) {
        $userId = $db->insert_id;
        
        $token = generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $db->query("INSERT INTO activation_tokens (user_id, token, expires_at) VALUES ($userId, '$token', '$expiresAt')");
        
        $activationLink = BASE_URL . "API/auth.php?action=activate&token=$token";
        $subject = "Aktivasi Akun manajemen gudang";
        $body = "Halo $name, klik link berikut untuk aktivasi: $activationLink";
        
        sendEmail($email, $subject, $body);
        
        jsonResponse([
            'success' => true, 
            'message' => 'Registrasi berhasil! Cek email untuk aktivasi akun.',
            'activation_link' => $activationLink
        ], 201);
    } else {
        jsonResponse(['success' => false, 'message' => 'Gagal registrasi'], 500);
    }
}

// ACTIVATE ACCOUNT (✅ Sudah Diperbaiki)
if ($method === 'GET' && $action === 'activate') {
    $token = $db->escape_string($_GET['token'] ?? '');

    if (empty($token)) {
        header("Location: " . BASE_URL . "pages/register.php?error=invalid_token");
        exit;
    }

    $result = $db->query("SELECT user_id, expires_at FROM activation_tokens WHERE token = '$token'");

    if ($result->num_rows === 0) {
        header("Location: " . BASE_URL . "pages/register.php?error=token_not_found");
        exit;
    }

    $tokenData = $result->fetch_assoc();

    if (strtotime($tokenData['expires_at']) < time()) {
        $db->query("DELETE FROM activation_tokens WHERE token = '$token'");
        header("Location: " . BASE_URL . "pages/register.php?error=token_expired");
        exit;
    }

    $userId = $tokenData['user_id'];
    $db->query("UPDATE users SET status = 'ACTIVE' WHERE id = $userId");
    $db->query("DELETE FROM activation_tokens WHERE token = '$token'");

    header("Location: " . BASE_URL . "pages/login.php?activated=success");
    exit;
}

// LOGIN
if ($method === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = $db->escape_string(strtolower($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Email dan password harus diisi'], 400);
    }
    
    $result = $db->query("SELECT id, name, email, password, status FROM users WHERE email = '$email'");
    
    if ($result->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Email atau password salah'], 401);
    }
    
    $user = $result->fetch_assoc();
    
    if (!verifyPassword($password, $user['password'])) {
        jsonResponse(['success' => false, 'message' => 'Email atau password salah'], 401);
    }
    
    if ($user['status'] !== 'ACTIVE') {
        jsonResponse(['success' => false, 'message' => 'Akun belum diaktivasi! Cek email Anda.'], 403);
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    
    $db->query("INSERT INTO activity_logs (user_id, action, description) VALUES ({$user['id']}, 'LOGIN', 'User login')");
    
    jsonResponse([
        'success' => true,
        'message' => 'Login berhasil',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ]
    ]);
}

// LOGOUT
if ($method === 'POST' && $action === 'logout') {
    requireAuth();
    
    $userId = $_SESSION['user_id'];
    $db->query("INSERT INTO activity_logs (user_id, action, description) VALUES ($userId, 'LOGOUT', 'User logout')");
    
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Logout berhasil']);
}

// FORGOT PASSWORD
if ($method === 'POST' && $action === 'forgot-password') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = $db->escape_string(strtolower($data['email'] ?? ''));
    
    if (empty($email)) {
        jsonResponse(['success' => false, 'message' => 'Email harus diisi'], 400);
    }
    
    $result = $db->query("SELECT id, name FROM users WHERE email = '$email'");
    
    if ($result->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Email tidak terdaftar'], 404);
    }
    
    $user = $result->fetch_assoc();
    
    $token = generateToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $db->query("DELETE FROM reset_password_tokens WHERE user_id = {$user['id']}");
    $db->query("INSERT INTO reset_password_tokens (user_id, token, expires_at) VALUES ({$user['id']}, '$token', '$expiresAt')");
    
    $resetLink = BASE_URL . "pages/reset_password.php?token=$token";
    $subject = "Reset Password Warehouse Management";
    $body = "Halo {$user['name']}, klik link berikut untuk reset password: $resetLink";
    
    sendEmail($email, $subject, $body);
    
    jsonResponse([
        'success' => true, 
        'message' => 'Link reset password telah dikirim ke email Anda.',
        'reset_link' => $resetLink
    ]);
}

// RESET PASSWORD
if ($method === 'POST' && $action === 'reset-password') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $token = $db->escape_string($data['token'] ?? '');
    $newPassword = $data['password'] ?? '';
    
    if (empty($token) || empty($newPassword)) {
        jsonResponse(['success' => false, 'message' => 'Token dan password harus diisi'], 400);
    }
    
    if (strlen($newPassword) < 6) {
        jsonResponse(['success' => false, 'message' => 'Password minimal 6 karakter'], 400);
    }
    
    $result = $db->query("SELECT user_id, expires_at FROM reset_password_tokens WHERE token = '$token'");
    
    if ($result->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Token tidak valid'], 400);
    }
    
    $tokenData = $result->fetch_assoc();
    
    if (strtotime($tokenData['expires_at']) < time()) {
        jsonResponse(['success' => false, 'message' => 'Token sudah expired'], 400);
    }
    
    $hashedPassword = hashPassword($newPassword);
    $userId = $tokenData['user_id'];
    $db->query("UPDATE users SET password = '$hashedPassword' WHERE id = $userId");
    $db->query("DELETE FROM reset_password_tokens WHERE token = '$token'");
    $db->query("INSERT INTO activity_logs (user_id, action, description) VALUES ($userId, 'RESET_PASSWORD', 'Password berhasil direset')");
    
    jsonResponse(['success' => true, 'message' => 'Password berhasil direset! Silakan login.']);
}

// CHECK SESSION
if ($method === 'GET' && $action === 'check') {
    if (isset($_SESSION['user_id'])) {
        jsonResponse([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email']
            ]
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
    }
}

jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
?>
