<?php
// deducts (or restores) stock for every item on a given order
function adjustStock(int $orderId, int $multiplier = -1): void
{
    global $conn;

    // fetch quantities
    $q = $conn->prepare("
      SELECT product_id, quantity
        FROM order_items
       WHERE order_id = ?
    ");
    $q->bind_param('i', $orderId);
    $q->execute();
    $items = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();

    // update stock
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
