<?php
// views/pos.php
session_start();
require_once __DIR__ . '/../config/db.php';


// --- Auth guard as before ---
if (empty($_SESSION['user_id'])) {
  header('Location: ../auth/login.php');
  exit;
}

// --- detect view/edit modes ---
$mode    = $_GET['mode'] ?? null;            // null | 'view' | 'edit'
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$initialCart = [];

if (($mode === 'view' || $mode === 'edit') && $orderId) {
  // Pull order items + product info
  $stmt = $conn->prepare("
    SELECT
      oi.product_id    AS id,
      p.name           AS name,
      p.price_usd      AS price,
      oi.quantity      AS qty
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
  ");
  $stmt->bind_param('i', $orderId);
  $stmt->execute();
  $initialCart = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}
// Fetch active tax rate
$taxRow = $conn
  ->query("SELECT rate FROM taxes WHERE is_active=1 ORDER BY id DESC LIMIT 1")
  ->fetch_assoc();
$taxRate = isset($taxRow['rate'])
  ? floatval($taxRow['rate']) / 100
  : 0.0;

// 2) Fetch Categories & Products
$categories = $conn
  ->query("SELECT id,name FROM categories ORDER BY name")
  ->fetch_all(MYSQLI_ASSOC);

// new: compute total stock = stock_quantity + SUM(qty) from stock_additions
// 2) Fetch Categories & Products (only those with stock > 0)
$products = $conn
  ->query("
    SELECT 
      p.id,
      p.name,
      p.image,
      p.price_usd AS price,
      (p.stock_quantity + COALESCE(sa.total_add,0)) AS stock,
      p.category_id
    FROM products p
    LEFT JOIN (
      SELECT product_id, SUM(qty) AS total_add
        FROM stock_additions
       GROUP BY product_id
    ) sa ON sa.product_id = p.id
    WHERE p.is_active = 1
      AND (p.stock_quantity + COALESCE(sa.total_add,0)) > 0
    ORDER BY p.name
  ")
  ->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Point of Sale';
include __DIR__ . '/templates/header.php';
?>

<style>
  /* full‐height flex container */
  .d-flex.vh-100 {
    height: 100vh;
  }

  /* ─── LEFT PANEL ─── */
  .left-panel {
    width: 70%;
    display: flex;
    flex-direction: column;
  }

  .menu-header {
    padding: 5px 20px;
    background: #f0f0f0;
    border-bottom: 1px solid #ccc;
    font-size: 1rem;
    font-weight: bold;
  }

  /* horizontally scrollable category bar */
  .category-bar {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
    padding: 10px 15px;
    background: #fff;
    border-bottom: 1px solid #ddd;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .category-bar::-webkit-scrollbar {
    display: none;
  }

  .category-bar {
    -ms-overflow-style: none;
    scrollbar-width: none;
  }

  .btn-cat {
    padding: 5px 12px;
    background: transparent;
    border: none;
    color: #333;
    font-size: .9rem;
    cursor: pointer;
    position: relative;
  }

  .btn-cat.active {
    font-weight: bold;
    color: #007bff;
  }

  .btn-cat.active::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 0;
    width: 100%;
    height: 2px;
    background: #007bff;
  }

  /* 3-column product grid */
  .product-grid {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    overflow-y: auto;
    /* allow vertical scroll */
    /* hide the native scrollbar everywhere: */
    -ms-overflow-style: none;
    /* IE11 */
    scrollbar-width: none;

  }

  .product-card {
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    transition: transform .1s;
  }

  .product-card:hover {
    transform: translateY(-4px);
  }

  .product-card img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-bottom: 1px solid #eee;
  }

  .product-card .info {
    padding: 10px;
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  .product-card .name {
    font-size: 1rem;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .product-card .price {
    font-size: 1.1rem;
    font-weight: bold;
    color: #007bff;
  }

  .product-card .stock {
    font-size: .85rem;
    color: #555;
    margin-top: 4px;
  }

  /* ─── RIGHT CART PANEL ─── */
  .cart-panel {
    width: 30%;
    background: #f9f9f9;
    border-left: 1px solid #ddd;
    display: flex;
    flex-direction: column;
    font-size: 12px;
  }

  .cart-header {
    background: #fff;
    display: flex;
    border-bottom: 1px solid #ccc;
  }

  .cart-header .tab {
    flex: 1;
    text-align: center;
    padding: 5px 0;
    cursor: pointer;
    font-weight: 600;
    color: #555;
    background: transparent;
    border: none;
  }

  .cart-header .tab.active {
    background: #c4c7ccff;
    color: #000;
  }

  .cart-body {
    flex: 1;
    padding: 5px;
    overflow-y: auto;
    /* allow vertical scrolling */
    /* hide scrollbar everywhere: */
    -ms-overflow-style: none;
    /* IE11 */
    scrollbar-width: none;
    /* Firefox */

    /* whatever max height you need */
  }

  /* Chrome, Safari, and Opera */
  .cart-body::-webkit-scrollbar {
    display: none;
  }


  .empty-cart {
    text-align: center;
    color: #6c757d;
    padding: 20px;
  }

  /* single-line cart item */
  .cart-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 5px;
    border-bottom: 1px solid #eee;
    gap: .8rem;
    font-size: 12px;
    -ms-overflow-style: none;
    /* IE11 */
    scrollbar-width: none;
    scroll-behavior: none;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }

  .cart-item .item-left {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex: 1;
  }

  .cart-item .item-left .name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
  }

  .cart-item .price {
    min-width: 60px;
    text-align: right;
    font-weight: 600;
  }

  .cart-item .remove-btn {
    background: transparent;
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 0 .25rem;
    font-size: 12px;
  }

  .cart-footer {
    padding: 15px;
    background: #fff;
    border-top: 1px solid #ccc;
  }

  .cart-footer .summary .line {
    display: flex;
    justify-content: space-between;
    margin: 4px 0;
  }

  .cart-footer .summary .total {
    font-size: 1rem;
    font-weight: 700;
    margin-top: 6px;
    margin-bottom: 5px;
    padding-top: 6px;
    border-top: 1px dashed #ddd;

  }

  .payment-methods {
    display: flex;
    justify-content: space-between;
    margin: 0;
  }

  .payment-methods button {
    flex: 1;
    margin: 0 2px;
    padding: 3px 0;
    border: 1px solid #ddd;
    background: #f8f9fa;
    cursor: pointer;
    border-radius: 4px;
  }

  .payment-methods button.active {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
  }

  .card-id {
    display: none;
    margin: 10px 0;
    padding: 5px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 100%;
  }

  .place-order {
    width: 100%;
    padding: 5px 0;
    background: #28a745;
    color: #fff;
    border: none;
    font-size: 1rem;
    cursor: pointer;
    border-radius: 4px;
    margin-top: 10px;
    font-weight: 600;
  }

  .view-mode .cart-item button,
  .view-mode .remove-btn {
    display: none !important;
  }
</style>

<div style="max-height: 495px;" class="d-flex">
  <!-- LEFT PANEL -->
  <div class="left-panel">
    <div class="menu-header">Menu</div>
    <div class="category-bar" id="catBar">
      <button class="btn-cat active" data-cat="all">
        All (<?= count($products) ?>)
      </button>
      <?php foreach ($categories as $c):
        $cnt = count(array_filter($products, fn($p) => $p['category_id'] === $c['id']));
      ?>
        <button class="btn-cat" data-cat="<?= $c['id'] ?>">
          <?= htmlspecialchars($c['name']) ?> (<?= $cnt ?>)
        </button>
      <?php endforeach; ?>
    </div>

    <div class="product-grid" id="prodGrid">
      <?php foreach ($products as $p): ?>
        <?php
        // Determine the correct URL for the img src
        if (str_starts_with($p['image'], 'uploads/')) {
          // Already has the uploads/ prefix
          $imgUrl = BASE_URL . '/' . $p['image'];
        } else {
          // Just a filename
          $imgUrl = BASE_URL . '/uploads/products/' . rawurlencode($p['image']);
        }
        ?>
        <div class="product-card"
          data-cat="<?= $p['category_id'] ?>"
          data-prod='<?= json_encode($p, JSON_HEX_APOS) ?>'>
          <img
            src="<?= htmlspecialchars($imgUrl) ?>"
            alt="<?= htmlspecialchars($p['name']) ?>"
            class="img-fluid" />

          <div class="info">
            <div class="name"><?= htmlspecialchars($p['name']) ?></div>
            <div class="price">$<?= number_format($p['price'], 2) ?></div>
            <div class="stock"><?= (int)$p['stock'] ?> in stock</div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>


  </div>

  <!-- RIGHT CART PANEL -->
  <div class="cart-panel">
    <div class="cart-header">
      <button class="tab active" data-tab="order">Order Item</button>

    </div>
    <div class="cart-body" id="cartBody">
      <p class="empty-cart">No items in cart</p>
    </div>
    <div class="cart-footer">
      <div class="summary">
        <div class="line"><span>Sub Total :</span><span id="subTxt">$0.00</span></div>
        <div class="line">
          <span>Tax (<span id="taxRateText"><?= number_format($taxRate * 100, 2) ?></span>%) :</span>
          <span id="taxTxt">$0.00</span>
        </div>
        <div class="line d-flex align-items-center">
          <label for="discInput" class="me-auto mb-0">Discount (%) :</label>
          <input
            id="discInput"
            type="number"
            min="0"
            max="100"
            step="0.01"
            value="0.00"
            class="form-control form-control-sm"
            style="width:80px; text-align:right;">
        </div>


        <div class="line total"><span>Total Price :</span><span id="totTxt">$0.00</span></div>
      </div>
      <div class="payment-methods">
        <button class="active" data-pay="cash">Cash</button>
        <button data-pay="card">Card</button>
        <button data-pay="online">Online</button>
      </div>
      <input type="text" id="cardId" class="card-id" placeholder="Card ID">
      <button class="place-order" id="orderBtn">Order</button>
    </div>
  </div>
</div>

<script>
  // ── grab the DOM elements we need ──
// at the top, once:
const subTxt    = document.getElementById('subTxt');
const taxTxt    = document.getElementById('taxTxt');
const totTxt    = document.getElementById('totTxt');
const discInput = document.getElementById('discInput');






  // ── mode constants injected by PHP ──
  const MODE = '<?= $mode ?>'; // '', 'view' or 'edit'
  const ORDER_ID = <?= $orderId ?: 'null' ?>;
  const INITIAL_CART = <?= json_encode($initialCart, JSON_HEX_APOS) ?>;

  // ── application state ──
  let cart = [],
    payMethod = 'cash';

  // ── render the cart on screen ──
  function renderCart() {
    const cb = document.getElementById('cartBody');
    cb.innerHTML = '';
    if (!cart.length) {
      cb.innerHTML = '<p class="empty-cart">No items in cart</p>';
      updateTotals();
      return;
    }
    cart.forEach((it, i) => {
      const div = document.createElement('div');
      div.className = 'cart-item';
      div.innerHTML = `
        <div class="item-left">
          <span class="name">${it.name}</span>
          <button onclick="chgQty(${i},-1)" ${it.qty <= 1 ? 'disabled' : ''}>–</button>
          <span>${it.qty}</span>
          <button onclick="chgQty(${i},1)">+</button>
        </div>
        <div class="price">$${(it.price * it.qty).toFixed(2)}</div>
        <button class="remove-btn" onclick="rmItem(${i})">
          <i class="bi bi-trash"></i>
        </button>`;
      cb.appendChild(div);
    });
    updateTotals();
  }

  // ── cart operations ──
  function addToCart(p) {
    const idx = cart.findIndex(x => x.id === p.id);
    const newQty = idx > -1 ? cart[idx].qty + 1 : 1;
    if (newQty > p.stock) {
      alert(`Only ${p.stock} ${p.name} in stock`);
      return;
    }
    if (idx > -1) cart[idx].qty = newQty;
    else cart.push({
      ...p,
      qty: 1
    });
    renderCart();
  }

  function chgQty(i, d) {
    const newQty = cart[i].qty + d;
    if (newQty < 1) return; // Prevent negative quantities
    if (newQty > cart[i].stock) {
      alert(`Only ${cart[i].stock} ${cart[i].name} in stock`);
      return;
    }
    cart[i].qty = newQty;
    renderCart();
  }

  function rmItem(i) {
    cart.splice(i, 1);
    renderCart();
  }

  // ── recalc totals ──
  const TAX_RATE = <?= json_encode($taxRate, JSON_NUMERIC_CHECK) ?>;

  function updateTotals() {
  // 1) Subtotal
  const sub = cart.reduce((sum, it) => sum + it.price * it.qty, 0);

  // 2) Tax
  const tax = sub * TAX_RATE;

  // 3) Discount as percent
  let pct = parseFloat(discInput.value);
  if (isNaN(pct) || pct < 0) pct = 0;
  if (pct > 100) pct = 100;
  discInput.value = pct.toFixed(2);

  // 4) Compute discount _amount_
  const discAmt = sub * (pct / 100);

  // 5) Final total
  const tot = sub + tax - discAmt;

  // 6) Paint the UI
  subTxt.textContent = '$' + sub.toFixed(2);
  taxTxt.textContent = '$' + tax.toFixed(2);
  totTxt.textContent = '$' + tot.toFixed(2);
}



  // ── category filter ──
  document.querySelectorAll('.btn-cat').forEach(btn => {
    btn.onclick = () => {
      document.querySelectorAll('.btn-cat').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const cat = btn.dataset.cat;
      document.querySelectorAll('.product-card')
        .forEach(c => c.style.display = (cat === 'all' || c.dataset.cat === cat) ? '' : 'none');
    };
  });

  // ── product click adds to cart ──
  document.querySelectorAll('.product-card').forEach(card => {
    const p = JSON.parse(card.dataset.prod);
    card.onclick = () => addToCart(p);
  });

  // ── payment selector ──
  document.querySelectorAll('.payment-methods button').forEach(btn => {
    btn.onclick = () => {
      document.querySelectorAll('.payment-methods button')
        .forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      payMethod = btn.dataset.pay;
      cardIdInput.style.display = (payMethod === 'card') ? 'block' : 'none';
    };
  });

  // ── default order-button handler (for new orders) ──
  async function placeOrder() {
    try {
      const resp = await fetch('place_order.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          items: cart,
          total: parseFloat(totTxt.textContent.slice(1)),
          payment_method: payMethod
        })
      });
      const json = await resp.json(); // now never “Unexpected end of JSON input”
      if (!json.success) throw new Error(json.message);
      Swal.fire('Success', 'Order #' + json.order_id + ' placed', 'success')
        .then(() => window.location.href = 'orderlist.php');
    } catch (err) {
      Swal.fire('Error', err.message, 'error');
      console.error(err);
    }
  }

  function placeOrder() {
    if (!cart.length) {
      Swal.fire('Error', 'Cart is empty', 'error');
      return;
    }

    if (payMethod === 'card' && !cardIdInput.value.trim()) {
      Swal.fire('Error', 'Please enter Card ID', 'error');
      return;
    }

    const payload = {
      items: cart.map(item => ({
        id: item.id,
        qty: item.qty,
        price: item.price
      })),
      payment_method: payMethod,
      total: parseFloat(totTxt.textContent.slice(1))
    };

    fetch('place_order.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(response => {
        if (!response.ok) {
          return response.json().then(err => {
            throw new Error(err.message || 'Server error');
          });
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          Swal.fire({
            title: 'Success!',
            text: `Order #${data.order_id} placed`,
            icon: 'success'
          }).then(() => {
            window.location.href = 'orderlist.php';
          });
        } else {
          throw new Error(data.message || 'Order failed');
        }
      })
      .catch(error => {
        Swal.fire('Error', error.message, 'error');
        console.error('Order error:', error);
      });
  }
  discInput.addEventListener('input', updateTotals);
  updateTotals();
  // ── on page load, handle view/edit modes ──
  window.addEventListener('DOMContentLoaded', () => {
    // Always wire up default behavior first…
    document.getElementById('orderBtn').onclick = placeOrder;

    if (MODE === 'view' || MODE === 'edit') {
      // 1) preload cart from server
      cart = INITIAL_CART.map(it => ({
        id: it.id,
        name: it.name,
        price: it.price,
        qty: it.qty
      }));
      renderCart();

      // 2) disable product clicks & hide controls in VIEW
      if (MODE === 'view') {
        document.querySelectorAll('.product-card')
          .forEach(c => c.onclick = null);
        document.body.classList.add('view-mode');
        document.querySelector('.payment-methods').style.display = 'none';
        document.getElementById('orderBtn').style.display = 'none';
      }

      // 3) add “Back to Orders” link
      const back = document.createElement('a');
      back.href = 'orderlist.php';
      back.className = 'btn btn-secondary mt-2';
      back.textContent = '← Back to Orders';
      document.querySelector('.cart-footer').appendChild(back);

      // 4) in EDIT mode, repurpose the button
      // In the DOMContentLoaded event listener, update the edit mode section:
      if (MODE === 'edit') {
        const btn = document.getElementById('orderBtn');
        btn.textContent = 'Update Order';
        btn.onclick = async () => {
          if (!cart.length) return alert('Cart is empty');

          try {
            const payload = {
              items: cart.map(i => ({
                id: i.id,
                qty: i.qty,
                price: i.price
              })),
              payment_method: payMethod,
              subtotal: parseFloat(subTxt.textContent.slice(1)),
              tax: parseFloat(taxTxt.textContent.slice(1)),
              discount_pct: parseFloat(discInput.value), // ← new
              discount_amt: parseFloat((sub * (parseFloat(discInput.value) / 100)).toFixed(2)),
              total: parseFloat(totTxt.textContent.slice(1))
            };


            const response = await fetch(`update_order.php?id=${ORDER_ID}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!response.ok) {
              throw new Error(result.message || 'Failed to update order');
            }

            if (result.success) {
              Swal.fire({
                title: 'Success!',
                text: 'Order updated successfully',
                icon: 'success'
              }).then(() => {
                window.location.href = 'orderlist.php';
              });
            } else {
              throw new Error(result.message || 'Failed to update order');
            }
          } catch (error) {
            Swal.fire('Error', error.message, 'error');
            console.error('Update error:', error);
          }
        };
      }
    }
  });
</script>


<?php include __DIR__ . '/templates/footer.php'; ?>