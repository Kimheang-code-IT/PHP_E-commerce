<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// Only allow logged-in users
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate order ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Fetch line items
$stmt = $conn->prepare("
SELECT 
    pr.name,    
    oi.quantity    AS qty,
    oi.unit_price  AS price,
    (oi.quantity * oi.unit_price) AS total
  FROM order_items oi
  JOIN products pr ON pr.id = oi.product_id
 WHERE oi.order_id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

$items    = [];
$subtotal = 0.0;
while ($row = $result->fetch_assoc()) {
    $lineTotal = $row['qty'] * $row['price'];
    $items[]   = [
        'name'  => $row['name'],
        'qty'   => (int)$row['qty'],
        'price' => (float)$row['price'],
        'total' => $lineTotal
    ];
    $subtotal += $lineTotal;
}
$stmt->close();

// ── NEW: collect barcodes for each product in this order ─────────
$barcodeStmt = $conn->prepare("
  SELECT pr.barcode
    FROM order_items oi
    JOIN products pr ON pr.id = oi.product_id
   WHERE oi.order_id = ?
");
$barcodeStmt->bind_param('i', $id);
$barcodeStmt->execute();
$barcodeRes = $barcodeStmt->get_result();

$barcodes = [];
while ($b = $barcodeRes->fetch_assoc()) {
    $barcodes[] = $b['barcode'];
}
$barcodeStmt->close();

// Compute tax, discount, totals
$tax      = round($subtotal * 0.10, 2);
$discount = 0.00;
$totalUsd = round($subtotal + $tax - $discount, 2);

// Compute local-currency total
$row      = $conn->query("SELECT change_rate FROM orders WHERE id = {$id} LIMIT 1")->fetch_assoc();
$rate     = isset($row['change_rate']) ? (float)$row['change_rate'] : 4100.00;
$totalKhr = round($totalUsd * $rate);

// ── NEW: fetch current status ────────────────────────────────
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$status = $stmt->get_result()->fetch_assoc()['status'];
$stmt->close();

// Return JSON
echo json_encode([
    'success'   => true,
    'orderId'   => $id,          // ← NEW
    'status'    => $status,
    'items'     => $items,
    'barcodes'  => $barcodes,    // ← NEW
    'subtotal'  => $subtotal,
    'tax'       => $tax,
    'discount'  => $discount,
    'totalUsd'  => $totalUsd,
    'totalKhr'  => $totalKhr
]);
