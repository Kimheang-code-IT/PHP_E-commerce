<?php
// views/site/checkout.php

require_once dirname(__DIR__,2) . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// ───── DETERMINE USER_ID ─────
$userId = $_SESSION['user']['id'] ?? 'US0000000001';

// ───── PULL PROFILE (ONLY IF REAL USER) ─────
$nameDefault = $emailDefault = $addressDefault = '';

if (isset($_SESSION['user']['id'])) {
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $userInfo = $result->fetch_assoc();
            $result->free();
            $nameDefault = htmlspecialchars($userInfo['username'] ?? '');
            $emailDefault = htmlspecialchars($userInfo['email'] ?? '');
        }
        $stmt->close();
    }
}

// ───── BUILD CART WITH VALIDATION ─────
$cart = [];
$raw = $_SESSION['cart'] ?? [];

foreach ($raw as $row) {
    if (!isset($row['product_id']) || !is_numeric($row['product_id'])) continue;
    
    $key = $row['product_id'] . '|' . ($row['size'] ?? '') . '|' . ($row['color'] ?? '');
    if (!isset($grouped[$key])) {
        $grouped[$key] = [
            'product_id' => (int)$row['product_id'],
            'size' => $row['size'] ?? null,
            'color' => $row['color'] ?? null,
            'quantity' => 0
        ];
    }
    $grouped[$key]['quantity'] += (int)($row['quantity'] ?? 1);
}
$cart = array_values($grouped ?? []);

// ───── FETCH PRODUCT DATA WITH STOCK INFO ─────
$products = [];
if (!empty($cart)) {
    $ids = array_column($cart, 'product_id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    
    $stmt = $conn->prepare("
        SELECT 
            p.id, 
            p.name, 
            p.price_usd,
            (p.stock_quantity + IFNULL((
                SELECT SUM(qty) FROM stock_additions 
                WHERE product_id = p.id
            ), 0)) AS available_stock
        FROM products p
        WHERE p.id IN($placeholders)
    ");
    
    if ($stmt) {
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            while ($p = $result->fetch_assoc()) {
                $products[$p['id']] = $p;
            }
            $result->free();
        }
        $stmt->close();
    }
}

// ───── VALIDATE CART ITEMS AND CALCULATE TOTALS ─────
$validCart = [];
$subtotal = 0;

foreach ($cart as $item) {
    if (!isset($products[$item['product_id']])) continue;
    
    $product = $products[$item['product_id']];
    $quantity = max(1, min($item['quantity'], $product['available_stock']));
    
    $validCart[] = [
        'product_id' => $item['product_id'],
        'name' => $product['name'],
        'price_usd' => $product['price_usd'],
        'quantity' => $quantity,
        'size' => $item['size'],
        'color' => $item['color']
    ];
    
    $subtotal += $product['price_usd'] * $quantity;
}

// Update session with validated cart
$_SESSION['cart'] = $validCart;
$cart = $validCart;

// ───── CALCULATE TAXES AND TOTALS ─────
$taxRate = 0;
$taxRow = $conn->query("SELECT rate FROM taxes WHERE is_active=1 ORDER BY id DESC LIMIT 1");
if ($taxRow) {
    $taxData = $taxRow->fetch_assoc();
    if ($taxData) {
        $taxRate = floatval($taxData['rate']) / 100;
    }
    $taxRow->free();
}
$taxAmt = $subtotal * $taxRate;
$total = $subtotal + $taxAmt;

// ───── RENDER PAGE ─────
$pageTitle = 'Checkout';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container my-5">
  <h2 class="mb-4">Checkout</h2>
  <div id="alertArea"></div>
  
  <?php if (empty($cart)): ?>
    <div class="alert alert-warning">
      Your cart is empty. <a href="/products">Continue shopping</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <!-- CART SUMMARY -->
      <div class="col-lg-6">
        <div class="card shadow-sm">
          <div class="card-header bg-light"><strong>Your Cart</strong></div>
          <ul class="list-group list-group-flush">
            <?php foreach($cart as $item): ?>
              <li class="list-group-item d-flex justify-content-between">
                <div>
                  <?= htmlspecialchars($item['name']) ?>
                  <?php if ($item['size']): ?>
                    <span class="text-muted">(Size: <?= htmlspecialchars($item['size']) ?>)</span>
                  <?php endif; ?>
                  <?php if ($item['color']): ?>
                    <span class="text-muted">(Color: <?= htmlspecialchars($item['color']) ?>)</span>
                  <?php endif; ?>
                  × <?= $item['quantity'] ?>
                </div>
                <span>$<?= number_format($item['price_usd'] * $item['quantity'], 2) ?></span>
              </li>
            <?php endforeach; ?>
            
            <li class="list-group-item d-flex justify-content-between">
              <strong>Subtotal</strong>
              <strong>$<?= number_format($subtotal, 2) ?></strong>
            </li>
            <li class="list-group-item d-flex justify-content-between">
              <strong>Tax (<?= number_format($taxRate*100, 2) ?>%)</strong>
              <strong>$<?= number_format($taxAmt, 2) ?></strong>
            </li>
            <li class="list-group-item d-flex justify-content-between">
              <strong>Total</strong>
              <strong id="totalDisplay">$<?= number_format($total, 2) ?></strong>
            </li>
          </ul>
        </div>
      </div>

      <!-- SHIPPING & PAYMENT -->
      <div class="col-lg-6">
        <div class="card shadow-sm">
          <div class="card-header bg-light"><strong>Shipping & Payment</strong></div>
          <div class="card-body">
            <form id="checkoutForm" class="needs-validation" novalidate>
              <div class="mb-3">
                <label for="inpName" class="form-label">Full Name</label>
                <input id="inpName" name="name" type="text" class="form-control" required
                       value="<?= $nameDefault ?>">
                <div class="invalid-feedback">Please enter your name</div>
              </div>
              
              <div class="mb-3">
                <label for="inpEmail" class="form-label">Email</label>
                <input id="inpEmail" name="email" type="email" class="form-control" required
                       value="<?= $emailDefault ?>">
                <div class="invalid-feedback">Please enter a valid email</div>
              </div>
              
              <div class="mb-3">
                <label for="inpAddr" class="form-label">Shipping Address</label>
                <textarea id="inpAddr" name="address" rows="2" class="form-control" required><?= $addressDefault ?></textarea>
                <div class="invalid-feedback">Please enter your shipping address</div>
              </div>
              
              <div class="mb-3">
                <label for="selPay" class="form-label">Payment Method</label>
                <select id="selPay" name="payment_method" class="form-select" required>
                  <option value="cod">Cash on Delivery</option>
                  <option value="card">Credit/Debit Card</option>
                  <option value="online">Online Payment</option>
                </select>
              </div>
              
              <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">
              
              <button id="btnPlace" class="btn btn-primary w-100 py-2" type="submit">
                <span id="btnText">Complete Order</span>
                <span id="btnSpin" class="spinner-border spinner-border-sm ms-2 d-none" 
                      role="status" aria-hidden="true"></span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('checkoutForm');
  const btn = document.getElementById('btnPlace');
  const btnText = document.getElementById('btnText');
  const btnSpin = document.getElementById('btnSpin');
  const alertArea = document.getElementById('alertArea');
  
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      form.classList.add('was-validated');
      if (!form.checkValidity()) return;
      
      // Prepare order data
      const orderData = {
        user_id: form.user_id.value,
        name: form.name.value.trim(),
        email: form.email.value.trim(),
        address: form.address.value.trim(),
        payment_method: form.payment_method.value,
        items: <?= json_encode(array_map(function($item) {
          return [
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['price_usd'],
            'size' => $item['size'],
            'color' => $item['color']
          ];
        }, $cart)) ?>,
        subtotal: <?= $subtotal ?>,
        tax: <?= $taxAmt ?>,
        total: <?= $total ?>
      };
      
      // Disable button and show spinner
      btn.disabled = true;
      btnText.textContent = 'Processing...';
      btnSpin.classList.remove('d-none');
      alertArea.innerHTML = '';
      
      try {
    const response = await fetch('place_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(orderData)
    });
    
    // First check if response is JSON
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
        const text = await response.text();
        throw new Error(`Expected JSON but got: ${text.substring(0, 100)}`);
    }
    
    const result = await response.json();
        
        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Order processing failed');
        }
        
        // Clear cart and redirect to thank you page
        <?php unset($_SESSION['cart']); ?>
        window.location.href = `/thank-you.php?order_id=${result.order_id}`;
        
      } catch (error) {
        alertArea.innerHTML = `
          <div class="alert alert-danger alert-dismissible fade show">
            <strong>Error:</strong> ${error.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        `;
      } finally {
        btn.disabled = false;
        btnText.textContent = 'Complete Order';
        btnSpin.classList.add('d-none');
      }
    });
  }
});
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>