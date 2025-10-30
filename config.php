<?php
// config.php - Konfigurasi Database dan Aplikasi

// Pengaturan Error Reporting (Development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pengaturan Timezone
date_default_timezone_set('Asia/Jakarta');

// ============================================
// KONFIGURASI DATABASE
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'warehouse_management');

// ============================================
// KONFIGURASI EMAIL (SMTP)
// ============================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Warehouse Management System');

// ============================================
// KONFIGURASI APLIKASI
// ============================================
define('BASE_URL', 'http://localhost/warehouse-management/');
define('SECRET_KEY', 'uts-warehouse-secret-key-2024');

// ============================================
// Session Configuration
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// KONEKSI DATABASE (Singleton Pattern)
// ============================================
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function escape($value) {
        return $this->conn->real_escape_string($value);
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function sendEmail($to, $subject, $body) {
    // Mode Development: Log ke file
    $logFile = __DIR__ . '/email_logs.txt';
    $emailLog = "\n\n=== EMAIL SENT ===\n";
    $emailLog .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $emailLog .= "To: $to\n";
    $emailLog .= "Subject: $subject\n";
    $emailLog .= "Body:\n$body\n";
    $emailLog .= "==================\n";
    
    file_put_contents($logFile, $emailLog, FILE_APPEND);
    
    return true;
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}

function cors() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Enable CORS
cors();
?>