<?php
// index.php - Landing Page
session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}

// Jika belum login, redirect ke login
header('Location: pages/login.php');
exit;
?>