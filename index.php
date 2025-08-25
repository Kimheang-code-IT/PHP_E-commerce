<?php
// C:\xampp\htdocs\Barcode\index.php
require_once __DIR__ . '/config/db.php';
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
header('Location: ' . BASE_URL . '/views/dashboard.php');
exit;
