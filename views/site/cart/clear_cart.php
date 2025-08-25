<?php
// views/site/cart/clear_cart.php
if (session_status() === PHP_SESSION_NONE) session_start();

// clear entire cart
$_SESSION['cart'] = [];

// return JSON
header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit;
