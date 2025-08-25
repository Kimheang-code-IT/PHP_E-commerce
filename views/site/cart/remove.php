<?php
// views/site/cart/remove.php
if (session_status() === PHP_SESSION_NONE) session_start();

// only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit;
}

$key = $_POST['key'] ?? '';
if ($key === '' || !isset($_SESSION['cart'][$key])) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'Invalid item key']);
  exit;
}

// remove it
unset($_SESSION['cart'][$key]);

// recompute total
$total = 0;
foreach ($_SESSION['cart'] as $row) {
  $price = floatval($row['price_usd'] ?? 0);
  $qty   = intval($row['quantity'] ?? 0);
  $total += $price * $qty;
}

header('Content-Type: application/json');
echo json_encode([
  'success'   => true,
  'new_total' => number_format($total,2),
  'count'     => count($_SESSION['cart'])
]);
