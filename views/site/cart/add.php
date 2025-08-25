<?php
// views/site/cart/add.php
session_start();

// 1) Grab + validate
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity  = isset($_POST['quantity'])   ? max(1, (int)$_POST['quantity']) : 1;
$size      = trim($_POST['size']   ?? '');
$color     = trim($_POST['color']  ?? '');

// require a valid product
if ($productId < 1) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid product']);
    exit;
}

// 2) Build key and init
$key = $productId . '|' . ($size ?: '--') . '|' . ($color ?: '--');
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 3) Add or increment
if (isset($_SESSION['cart'][$key])) {
    $_SESSION['cart'][$key]['quantity'] += $quantity;
} else {
    $_SESSION['cart'][$key] = [
        'product_id' => $productId,
        'size'       => $size,
        'color'      => $color,
        'quantity'   => $quantity,
    ];
}

// 4) Return JSON success
header('Content-Type: application/json');
echo json_encode([
  'success'    => true,
  'product_id' => $productId,
  'quantity'   => $_SESSION['cart'][$key]['quantity'],
]);
exit;
