<?php
// api/user.php - API User Profile

require_once '../config.php';

requireAuth();

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

// GET USER PROFILE
if ($method === 'GET') {
    $result = $db->query("SELECT id, name, email, status, created_at FROM users WHERE id = $userId");
    
    if ($result->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'User tidak ditemukan'], 404);
    }
    
    $user = $result->fetch_assoc();
    
    jsonResponse([
        'success' => true,
        'data' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'status' => $user['status'],
            'created_at' => $user['created_at']
        ]
    ]);
}

// UPDATE USER PROFILE & CHANGE PASSWORD
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $action = $data['action'] ?? '';
    
    // UPDATE NAME
    if ($action === 'update-profile') {
        $name = $db->escape_string($data['name'] ?? '');
        
        if (empty($name)) {
            jsonResponse(['success' => false, 'message' => 'Nama harus diisi'], 400);
        }
        
        $sql = "UPDATE users SET name = '$name' WHERE id = $userId";
        
        if ($db->query($sql)) {
            $_SESSION['user_name'] = $name;
            
            $db->query("INSERT INTO activity_logs (user_id, action, description) VALUES ($userId, 'UPDATE_PROFILE', 'User mengupdate profil')");
            
            jsonResponse([
                'success' => true,
                'message' => 'Profil berhasil diupdate',
                'data' => ['name' => $name]
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Gagal mengupdate profil'], 500);
        }
    }
    
    // CHANGE PASSWORD
    if ($action === 'change-password') {
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword)) {
            jsonResponse(['success' => false, 'message' => 'Password lama dan baru harus diisi'], 400);
        }
        
        if (strlen($newPassword) < 6) {
            jsonResponse(['success' => false, 'message' => 'Password baru minimal 6 karakter'], 400);
        }
        
        $result = $db->query("SELECT password FROM users WHERE id = $userId");
        $user = $result->fetch_assoc();
        
        if (!verifyPassword($currentPassword, $user['password'])) {
            jsonResponse(['success' => false, 'message' => 'Password lama salah'], 400);
        }
        
        $hashedPassword = hashPassword($newPassword);
        $sql = "UPDATE users SET password = '$hashedPassword' WHERE id = $userId";
        
        if ($db->query($sql)) {
            $db->query("INSERT INTO activity_logs (user_id, action, description) VALUES ($userId, 'CHANGE_PASSWORD', 'User mengubah password')");
            
            jsonResponse([
                'success' => true,
                'message' => 'Password berhasil diubah'
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Gagal mengubah password'], 500);
        }
    }
    
    jsonResponse(['success' => false, 'message' => 'Action tidak valid'], 400);
}

jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
?>