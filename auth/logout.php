<?php
// C:\xampp\htdocs\Barcode\auth\logout.php

require_once __DIR__ . '/../config/auth.php';

// Clear remember_me cookie and token
if (isset($_COOKIE['remember_me'])) {
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = ?");
    $stmt->bind_param('s', $_COOKIE['remember_me']);
    $stmt->execute();
    $stmt->close();
    setcookie('remember_me', '', time() - 3600, '/', '', true, true);
}

// Use auth.php's logout function
logout();
?>