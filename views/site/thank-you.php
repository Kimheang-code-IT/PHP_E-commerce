<?php
require_once __DIR__ . '/../config/db.php';
include dirname(__DIR__) . '/includes/header.php';

$orderId = $_GET['order_id'] ?? 0;
?>

<div class="container my-5 text-center">
    <div class="card shadow-sm mx-auto" style="max-width: 600px;">
        <div class="card-body p-5">
            <i class="bi bi-check-circle-fill text-success display-1 mb-4"></i>
            <h2 class="mb-3">Thank You for Your Order!</h2>
            <p class="lead mb-4">Your order has been placed successfully.</p>
            
            <?php if ($orderId): ?>
            <div class="alert alert-info">
                <strong>Order #:</strong> <?= htmlspecialchars($orderId) ?>
            </div>
            <?php endif; ?>
            
            <p>We've sent a confirmation email with your order details.</p>
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="/products" class="btn btn-primary">
                    <i class="bi bi-cart-fill me-2"></i> Continue Shopping
                </a>
                <a href="/profile" class="btn btn-outline-secondary">
                    <i class="bi bi-person-fill me-2"></i> View Orders
                </a>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>