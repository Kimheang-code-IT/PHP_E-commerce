<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// 1) must be logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'msg'=>'Unauthorized']);
    exit;
}

$id     = $_POST['id']     ?? '';
$status = $_POST['status'] ?? '';

// 2) define valid transitions
$valid = [
  'pending'   => ['paid','cancelled'],
  'paid'      => ['shipped','cancelled'],
  'shipped'   => ['completed'],
  'completed' => [],
  'cancelled' => []
];

// 3) fetch current status
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->bind_param('i',$id);
$stmt->execute();
$stmt->bind_result($current);
if (!$stmt->fetch()) {
    echo json_encode(['success'=>false,'msg'=>'Order not found']);
    exit;
}
$stmt->close();

// 4) validate transition
if (!isset($valid[$current]) || !in_array($status, $valid[$current])) {
    echo json_encode([
      'success'=>false,
      'msg'=>"Cannot change status from “{$current}” to “{$status}”"
    ]);
    exit;
}

// 5) perform update
$upd = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$upd->bind_param('si',$status,$id);
if ($upd->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'msg'=>'Database error']);
}
$upd->close();
