<?php
// config/db.php

// Only start session if none exists yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', '/coffee-shop');

$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbname = 'ecommerce';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// *** Alias for legacy code ***
$mysqli = $conn;
