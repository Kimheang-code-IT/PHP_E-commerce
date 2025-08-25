<?php
// views/delete_order.php
if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

// 1) Bootstrap + strict errors
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-errors.log');

session_start();
require_once __DIR__ . '/../config/db.php';

// 2) Auth guard
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit;
}

// 3) Validate order ID
$orderId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Missing or invalid order ID']);
    exit;
}

// 4) Helper to restore stock
function restoreStock(int $orderId): void {
    global $conn;
    // Fetch all line-items for this order
    $q = $conn->prepare("
      SELECT product_id, quantity
        FROM order_items
       WHERE order_id = ?
    ");
    $q->bind_param('i', $orderId);
    $q->execute();
    $items = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();

    // Put each quantity back into the product's stock
    $u = $conn->prepare("
      UPDATE products
         SET stock_quantity = stock_quantity + ?
       WHERE id = ?
    ");
    foreach ($items as $it) {
        $delta = (int)$it['quantity'];
        $u->bind_param('ii', $delta, $it['product_id']);
        $u->execute();
    }
    $u->close();
}

try {
    // 5) Begin transaction
    $conn->begin_transaction();

    // 6) Restore stock
    restoreStock($orderId);

    // 7) Delete payments for this order
    $conn->query("DELETE FROM payments WHERE order_id = $orderId");

    // 8) Delete order_items (if your FK is not ON DELETE CASCADE)
    $conn->query("DELETE FROM order_items WHERE order_id = $orderId");

    // 9) Finally delete the order itself
    if (! $conn->query("DELETE FROM orders WHERE id = $orderId")) {
        throw new RuntimeException("Failed to delete order: ".$conn->error);
    }

    // 10) Commit
    $conn->commit();

    echo json_encode(['success'=>true]);

} catch (Throwable $e) {
    // Rollback on error
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode([
      'success' => false,
      'message' => $e->getMessage()
    ]);
}
