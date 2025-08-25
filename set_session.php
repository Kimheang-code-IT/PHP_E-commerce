<?php
// C:\xampp\htdocs\coffee-shop\set_session.php
session_start();
require_once __DIR__ . '/config/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $data = json_decode(file_get_contents('php://input'), true);
    $_SESSION['cart'] = json_decode($data['cart'], true);
    $_SESSION['order_type'] = $data['order_type'];
    $_SESSION['payment_method'] = $data['payment_method'];
    if (isset($data['card_id'])) $_SESSION['card_id'] = $data['card_id'];
    if (isset($data['customer_username'])) $_SESSION['customer_username'] = $data['customer_username'];
    if (isset($data['phone_number'])) $_SESSION['phone_number'] = $data['phone_number'];
    if (isset($data['location'])) $_SESSION['location'] = $data['location'];
}
?>