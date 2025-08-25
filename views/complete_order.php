<?php
// complete_order.php
session_start();
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
  echo json_encode(['success'=>false,'message'=>'Invalid order ID']);
  exit;
}

// for example, mark `status='paid'` or `status='completed'`
$stmt = $conn->prepare("UPDATE orders SET status='paid' WHERE id=?");
$stmt->bind_param('i',$id);
if ($stmt->execute()) {
  echo json_encode(['success'=>true]);
} else {
  echo json_encode(['success'=>false,'message'=>$stmt->error]);
}
