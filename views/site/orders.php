<?php
require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json');

try {
    // Validate input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    // Required fields validation
    $required = ['user_id', 'name', 'email', 'address', 'payment_method', 'items', 'total'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    if (!is_array($input['items']) || count($input['items']) === 0) {
        throw new Exception('Cart is empty');
    }

    $conn->begin_transaction();

    // 1. Create the order
    $stmt = $conn->prepare("
        INSERT INTO orders 
        (user_id, status, total_usd, payment_method) 
        VALUES (?, 'pending', ?, ?)
    ");
    $stmt->bind_param('sds', 
        $input['user_id'],
        $input['total'],
        $input['payment_method']
    );
    $stmt->execute();
    $orderId = $conn->insert_id;
    $stmt->close();

    // 2. Add order items and update stock
    foreach ($input['items'] as $item) {
        // Insert order item
        $stmt = $conn->prepare("
            INSERT INTO order_items 
            (order_id, product_id, quantity, unit_price, size, color) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iiddss', 
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['size'] ?? null,
            $item['color'] ?? null
        );
        $stmt->execute();
        $stmt->close();

        // Update product stock
        $conn->query("
            UPDATE products 
            SET stock_quantity = stock_quantity - {$item['quantity']} 
            WHERE id = {$item['product_id']}
        ");
    }

    // 3. Create payment record if not COD
    if ($input['payment_method'] !== 'cod') {
        $stmt = $conn->prepare("
            INSERT INTO payments 
            (order_id, amount_usd, method) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('ids', 
            $orderId,
            $input['total'],
            $input['payment_method']
        );
        $stmt->execute();
        $stmt->close();

        // Update order status to paid
        $conn->query("UPDATE orders SET status = 'paid' WHERE id = $orderId");
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Order placed successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}