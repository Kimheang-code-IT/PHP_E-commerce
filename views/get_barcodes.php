<?php
// get_barcodes.php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || !isset($_GET['id'])) {
  echo json_encode(['success' => false, 'message' => 'Not authenticated or invalid order ID']);
  exit;
}

$orderId = (int)$_GET['id'];

try {
  $stmt = $conn->prepare("
    SELECT p.barcode, p.name, oi.quantity
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
  ");
  $stmt->bind_param('i', $orderId);
  $stmt->execute();
  $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  echo json_encode(['success' => true, 'items' => $items]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error fetching order items: ' . $e->getMessage()]);
}