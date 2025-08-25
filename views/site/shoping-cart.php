<?php
// views/site/shopping-cart.php

require_once dirname(__DIR__, 2) . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// ───── POST HANDLERS ─────
// 1) Clear entire cart:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
  unset($_SESSION['cart']);
  header('Location: shopping-cart.php');
  exit;
}

// 2) Update quantities:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantity']) && is_array($_POST['quantity'])) {
  $newCart = [];
  foreach ($_POST['quantity'] as $key => $qty) {
    list($pid, $size, $color) = explode('|', $key);
    $qty = max(1, (int)$qty);
    $newCart[] = [
      'product_id' => (int)$pid,
      'size'       => $size,
      'color'      => $color,
      'quantity'   => $qty,
    ];
  }
  $_SESSION['cart'] = $newCart;
  header('Location: shopping-cart.php');
  exit;
}

// ───── BUILD CART DATA ─────
// 1) group cart items
$raw = $_SESSION['cart'] ?? [];
$items = [];
foreach ($raw as $row) {
  $key = "{$row['product_id']}|{$row['size']}|{$row['color']}";
  if (!isset($items[$key])) {
    $items[$key] = [
      'product_id' => $row['product_id'],
      'size'       => $row['size'],
      'color'      => $row['color'],
      'quantity'   => 0,
    ];
  }
  $items[$key]['quantity'] += $row['quantity'];
}
$cartItems = array_values($items);

// 2) fetch products
$products = [];
if ($cartItems) {
  $ids = array_column($cartItems, 'product_id');
  $ph  = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $conn->prepare("
        SELECT id,name,price_usd,image
          FROM products
         WHERE id IN($ph)
    ");
  $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
  $stmt->execute();
  foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $p) {
    $products[$p['id']] = $p;
  }
  $stmt->close();
}

// 3) compute totals
$subtotal = 0;
foreach ($cartItems as $it) {
  if (isset($products[$it['product_id']])) {
    $p = $products[$it['product_id']];
    $subtotal += $p['price_usd'] * $it['quantity'];
  }
}
$taxRate   = 0;
if ($tr = $conn->query("SELECT rate FROM taxes LIMIT 1")->fetch_assoc()) {
  $taxRate = (float)$tr['rate'];
}
$taxAmount = $subtotal * $taxRate / 100;
$total     = $subtotal + $taxAmount;

// ───── RENDER ─────
$pageTitle = 'Shopping Cart';
include dirname(__DIR__) . '/includes/header.php';
?>
<div style="margin-top: 80px;" class="main">
  <div class="container my-5">
    <form method="post" class="row g-4">
      <!-- CART TABLE -->
      <div class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Product</th>
                    <th>Name</th>
                    <th class="text-end">Price</th>
                    <th>Size</th>
                    <th>Color</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Total</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($cartItems)): ?>
                    <tr>
                      <td colspan="8" class="text-center py-5">
                        <i class="bi bi-cart-x fs-1 text-muted"></i>
                        <p class="mt-3">Your cart is empty.</p>
                        <a href="<?= BASE_URL ?>/views/site/product.php" class="btn btn-secondary">
                          Continue Shopping
                        </a>
                      </td>

                    </tr>
                  <?php else: ?>
                    <?php foreach ($cartItems as $it):
                      $p = $products[$it['product_id']] ?? null;
                      if (!$p) continue;
                      $rowTotal = $p['price_usd'] * $it['quantity'];
                      $itemKey  = "{$it['product_id']}|{$it['size']}|{$it['color']}";
                    ?>
                      <tr data-key="<?= htmlspecialchars($itemKey) ?>"
                        data-price="<?= $p['price_usd'] ?>">
                        <td>
                          <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image']) ?>"
                            alt="" class="rounded" style="width:70px;height:50px;object-fit:cover">
                        </td>
                        <td class="align-middle"><?= htmlspecialchars($p['name']) ?></td>
                        <td class="text-end align-middle">$<?= number_format($p['price_usd'], 2) ?></td>
                        <td class="align-middle"><?= htmlspecialchars($it['size'] ?: '—') ?></td>
                        <td class="align-middle"><?= htmlspecialchars($it['color'] ?: '—') ?></td>
                        <td class="align-middle">
                          <div class="input-group input-group-sm justify-content-center" style="max-width:120px">
                            <button type="button" class="btn btn-outline-secondary minus">−</button>
                            <input type="number" name="quantity[<?= $itemKey ?>]" min="1"
                              value="<?= $it['quantity'] ?>" class="form-control qty-input text-center">
                            <button type="button" class="btn btn-outline-secondary plus">＋</button>
                          </div>
                        </td>
                        <td class="text-end align-middle fw-bold line-total">$<?= number_format($rowTotal, 2) ?></td>
                        <td>
                          <button style="margin-top: 10px;" type="button" class="remove-item btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php if ($cartItems): ?>
            <div class="card-footer d-flex justify-content-between">
              <button type="submit" name="clear_cart" class="btn btn-outline-danger">
                <i class="bi bi-trash me-2"></i>Clear Cart
              </button>
              <button type="button"
                class="btn btn-secondary"
                onclick="window.location='<?= BASE_URL ?>/views/site/product.php'">
                <i class="bi bi-arrow-repeat me-2"></i>Continue Shopping
              </button>

            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- SUMMARY -->
      <div class="col-lg-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Order Summary</h5>
            <hr>
            <dl class="row mb-3">
              <dt class="col-6">Subtotal:</dt>
              <dd class="col-6 text-end" id="js-subtotal">$<?= number_format($subtotal, 2) ?></dd>

              <dt class="col-6">Tax (<?= number_format($taxRate, 2) ?>%):</dt>
              <dd class="col-6 text-end" id="js-tax">$<?= number_format($taxAmount, 2) ?></dd>

              <dt class="col-6 fw-bold border-top mt-3 pt-2">Total:</dt>
              <dd class="col-6 text-end fw-bold border-top mt-3 pt-2" id="js-total">
                $<?= number_format($total, 2) ?>
              </dd>
            </dl>
            <a href="<?= BASE_URL ?>/views/site/checkout.php"
              class="btn btn-secondary w-100">Proceed to Checkout</a>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
<style>
  .border-top {
    border-top: 1.2px dashed #ddd;
    margin-top: 10px;
    margin-bottom: 10px;
  }
</style>
<!-- Bootstrap & JS logic -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const taxRate = <?= json_encode($taxRate) ?>;
    const removeUrl = '<?= BASE_URL ?>/views/site/cart/remove.php';
    const fmt = n => '$' + parseFloat(n).toFixed(2);

    function recalcTotals() {
      let sub = 0;
      document.querySelectorAll('tr[data-price]').forEach(row => {
        const price = parseFloat(row.dataset.price) || 0;
        const inp = row.querySelector('input.qty-input');
        const qty = parseInt(inp.value) || 0;
        const line = price * qty;
        sub += line;
        row.querySelector('.line-total').textContent = fmt(line);
      });

      const tax = sub * taxRate / 100;
      const total = sub + tax;

      document.getElementById('js-subtotal').textContent = fmt(sub);
      document.getElementById('js-tax').textContent = fmt(tax);
      document.getElementById('js-total').textContent = fmt(total);
    }

    // 1) Delegate +/– buttons
    document.querySelector('table').addEventListener('click', e => {
      if (e.target.matches('.minus, .plus')) {
        const isMinus = e.target.classList.contains('minus');
        const inp = isMinus ?
          e.target.nextElementSibling :
          e.target.previousElementSibling;
        let val = parseInt(inp.value) || 1;
        inp.value = isMinus ?
          Math.max(1, val - 1) :
          val + 1;
        recalcTotals();
      }
    });

    // 2) Quantity inputs
    document.querySelectorAll('input.qty-input').forEach(inp => {
      inp.addEventListener('change', recalcTotals);
    });

    // 3) Delegate remove-item clicks
    document.querySelector('table tbody')
      .addEventListener('click', async e => {
        const btn = e.target.closest('.remove-item');
        if (!btn) return;

        const row = btn.closest('tr[data-key]');
        const key = row.dataset.key;
        if (!key) return;

        if (!confirm('Remove this item?')) return;

        try {
          const resp = await fetch(removeUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
              key
            })
          });
          const json = await resp.json();
          if (!json.success) throw new Error(json.message || 'Remove failed');

          // 3a) remove row
          row.remove();

          // 3b) recalc remaining
          recalcTotals();

          // 3c) update any cart‐count badge
          const badge = document.querySelector('.badge-cart-count');
          if (badge) badge.textContent = json.count;

          // 3d) if now empty, reload to show the “empty cart” state
          if (json.count === 0) {
            window.location.reload();
          }
        } catch (err) {
          alert(err.message);
        }
      });

    // initial totals
    recalcTotals();
  });
</script>