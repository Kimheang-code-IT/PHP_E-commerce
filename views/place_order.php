<?php
// place_order.php — JSON‐only endpoint that also deducts stock

if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/../logs/php-errors.log');

session_start();
try {
    require_once __DIR__ . '/../config/db.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Database config error']);
    exit;
}

// helper to adjust stock
function adjustStock(int $orderId, int $multiplier = -1): void {
    global $conn;
    $q = $conn->prepare("
      SELECT product_id, quantity
        FROM order_items
       WHERE order_id = ?
    ");
    $q->bind_param('i', $orderId);
    $q->execute();
    $items = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();

    $u = $conn->prepare("
      UPDATE products
         SET stock_quantity = GREATEST(stock_quantity + ?, 0)
       WHERE id = ?
    ");
    foreach ($items as $it) {
        $delta = $multiplier * (int)$it['quantity'];
        $u->bind_param('ii', $delta, $it['product_id']);
        $u->execute();
    }
    $u->close();
}

try {
    if (empty($_SESSION['user_id'])) {
        throw new RuntimeException('Not authenticated', 401);
    }

    $raw = file_get_contents('php://input');
    if (!$raw) throw new RuntimeException('Empty request body', 400);
    $in = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Invalid JSON: '.json_last_error_msg(), 400);
    }
    if (empty($in['items']) || !is_array($in['items'])) {
        throw new RuntimeException('No items to order', 400);
    }

    $items  = $in['items'];
    $method = $in['payment_method'] ?? 'cash';
    $total  = (float)($in['total'] ?? 0.0);
    $status = in_array($method, ['card','online']) ? 'paid' : 'pending';

    $conn->begin_transaction();

    // 1) create order (note: no payment_method column here)
    // right: 's' treats user_id as string
    $stmt = $conn->prepare("
    INSERT INTO orders
        (user_id, status, total_usd, created_at)
    VALUES (?,?,?,NOW())
    ");
    $stmt->bind_param('ssd',
    $_SESSION['user_id'],
    $status,
    $total
    );

    if (!$stmt->execute()) {
        throw new RuntimeException('Failed to create order', 500);
    }
    $orderId = $stmt->insert_id;
    $stmt->close();

    // 2) insert order_items
    $li = $conn->prepare("
      INSERT INTO order_items
        (order_id, product_id, quantity, unit_price)
      VALUES (?,?,?,?)
    ");
    foreach ($items as $it) {
        if (empty($it['id']) || empty($it['qty']) || !isset($it['price'])) {
            throw new RuntimeException('Invalid item format', 400);
        }
        $li->bind_param('iiid',
          $orderId,
          $it['id'],
          $it['qty'],
          $it['price']
        );
        if (!$li->execute()) {
            throw new RuntimeException('Failed to insert line item', 500);
        }
    }
    $li->close();

    // 3) record payment (this table does have a method column)
    $p = $conn->prepare("
      INSERT INTO payments
        (order_id, amount_usd, method, paid_at)
      VALUES (?,?,?,NOW())
    ");
    $p->bind_param('ids',
      $orderId,
      $total,
      $method
    );
    if (!$p->execute()) {
        throw new RuntimeException('Failed to record payment', 500);
    }
    $p->close();

    // 4) adjust stock
    adjustStock($orderId, -1);

    $conn->commit();

    echo json_encode([
      'success'  => true,
      'order_id' => $orderId
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($conn) && $conn->in_transaction) {
        $conn->rollback();
    }
    $code = $e->getCode();
    if ($code < 400 || $code > 599) $code = 500;
    http_response_code($code);
    echo json_encode([
      'success' => false,
      'message' => $e->getMessage()
    ]);
    exit;
}
