<?php
require_once __DIR__ . '/../config/db.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (empty($data['user_id']) || empty($data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid request data']));
}

try {
    $conn->begin_transaction();
    
    // Create the order
    $stmt = $conn->prepare("
        INSERT INTO orders 
        (user_id, status, total_usd, payment_method) 
        VALUES (?, 'pending', ?, ?)
    ");
    $stmt->bind_param('sds', 
        $data['user_id'],
        $data['total'],
        $data['payment_method']
    );
    $stmt->execute();
    $orderId = $conn->insert_id;
    $stmt->close();
    
    // Add order items
    foreach ($data['items'] as $item) {
        $stmt = $conn->prepare("
            INSERT INTO order_items 
            (order_id, product_id, quantity, unit_price) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('iiid', 
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price']
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
    
    // Create payment record if paid
    if ($data['payment_method'] !== 'cod') {
        $stmt = $conn->prepare("
            INSERT INTO payments 
            (order_id, amount_usd, method) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('ids', 
            $orderId,
            $data['total'],
            $data['payment_method']
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Order failed: ' . $e->getMessage()
    ]);
}