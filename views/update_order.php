<?php
// views/update_order.php

// suppress any prior output
if (ob_get_length()) ob_clean();

// show errors while debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// JSON response header
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../config/db.php';

try {
    // auth guard
    if (empty($_SESSION['user_id'])) {
        throw new Exception('Not authenticated', 401);
    }

    // get order ID from query string or request body
    $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$orderId) {
        throw new Exception('Missing or invalid order ID', 400);
    }

    // parse JSON body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON payload', 400);
    }
    
    if (empty($data['items'])) {
        throw new Exception('Invalid payload: no items', 400);
    }

    $items = $data['items'];
    $method = $data['payment_method'] ?? 'cash';
    $totalUsd = (float)($data['total'] ?? 0);
    $status = in_array($method, ['card', 'online']) ? 'paid' : 'pending';

    // begin transaction
    $conn->begin_transaction();

    // 1. Verify order exists and belongs to user
    $checkStmt = $conn->prepare("
        SELECT id FROM orders 
        WHERE id = ? AND user_id = ?
        FOR UPDATE
    ");
    $checkStmt->bind_param('ii', $orderId, $_SESSION['user_id']);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows === 0) {
        throw new Exception('Order not found or access denied', 404);
    }
    $checkStmt->close();

    // 2. Update order header
    $updateOrder = $conn->prepare("
        UPDATE orders
        SET status = ?, total_usd = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $updateOrder->bind_param('sdi', $status, $totalUsd, $orderId);
    $updateOrder->execute();
    
    if ($updateOrder->affected_rows === 0) {
        throw new Exception('Failed to update order', 500);
    }
    $updateOrder->close();

    // 3. Delete existing line items
    $deleteItems = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $deleteItems->bind_param('i', $orderId);
    $deleteItems->execute();
    $deleteItems->close();

    // 4. Insert new line items
    $itemStmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, unit_price)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($items as $it) {
        if (empty($it['id']) || empty($it['qty']) || empty($it['price'])) {
            throw new Exception('Invalid item data', 400);
        }

        $prodId = (int)$it['id'];
        $qty = (int)$it['qty'];
        $price = (float)$it['price'];

        $itemStmt->bind_param('iiid', $orderId, $prodId, $qty, $price);
        $itemStmt->execute();
        
        // Verify product exists and has sufficient stock?
        // You may want to add this validation
    }
    $itemStmt->close();

    // 5. Update payment record
    $deletePayment = $conn->prepare("DELETE FROM payments WHERE order_id = ?");
    $deletePayment->bind_param('i', $orderId);
    $deletePayment->execute();
    $deletePayment->close();

    $payStmt = $conn->prepare("
        INSERT INTO payments (order_id, amount_usd, method)
        VALUES (?, ?, ?)
    ");
    $payStmt->bind_param('ids', $orderId, $totalUsd, $method);
    $payStmt->execute();
    $payStmt->close();

    // commit & respond
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Order updated successfully'
    ]);
    
} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 500);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}