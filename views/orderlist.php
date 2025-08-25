<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Only allow logged-in users
if (empty($_SESSION['user_id'])) {
  header('Location: ../auth/login.php');
  exit;
}

// Fetch order list (exclude cancelled orders)
$sql = "
SELECT
  o.id,
  u.username,
  u.email,
  o.status,
  o.total_usd,
  o.created_at,
  COALESCE(p.method,'N/A')       AS pay_method,
  p.paid_at,
  COALESCE(p.amount_usd,0)       AS amount_usd,
  GROUP_CONCAT(DISTINCT pr.barcode SEPARATOR ', ') AS barcodes,
  COUNT(oi.id)                   AS item_count,
  IF(p.method='online','online','pos') AS typorder
FROM orders o
LEFT JOIN users       u  ON o.user_id    = u.id
LEFT JOIN payments    p  ON p.order_id   = o.id
LEFT JOIN order_items oi ON oi.order_id  = o.id
LEFT JOIN products    pr ON pr.id        = oi.product_id
WHERE o.status NOT IN ('cancelled','completed')
GROUP BY o.id
ORDER BY o.created_at DESC
";

$orders = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Allowed status transitions (no "cancelled")
$transitions = [
  'pending'   => ['paid'    => 'Mark Paid'],
  'paid'      => ['shipped' => 'Ship Order'],
  'shipped'   => ['completed' => 'Complete Order'],
  'completed' => []
];

$n = count($orders);

include __DIR__ . '/templates/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= $pageTitle ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .table-wrapper {
      max-height: 400px;
      overflow-y: auto;
    }

    .table-wrapper thead th {
      position: sticky;
      top: 0;
      z-index: 2;
      background-color: #34495e;
      color: #fff;
      text-align: center;
    }

    .table-sm tbody td {
      font-size: 0.8rem;
    }

    .summary-card {
      background: #f9f9f9;
      border: 1px solid #e0e0e0;
    }

    .summary-card .card-title {
      font-size: 14px;
    }

    .summary-list li {
      font-size: 13px;
      margin-bottom: 4px;
    }

    .summary-list li span:last-child {
      min-width: 60px;
      text-align: right;
      display: inline-block;
    }

    .desc-cell {
      display: inline-block;
      max-width: 5rem;
      height: 1rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .custom-header th {
      background-color: #34495e !important;
      color: #fff !important;
      text-align: center;
    }

    #barcodeContainer {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 1rem;
    }

    #barcodeContainer .barcode-item {
      text-align: center;
    }

    #barcodeContainer .barcode-item img {
      max-width: 100%;
      height: auto;
      margin-bottom: 0.25rem;
    }

    #barcodeContainer .barcode-item div {
      font-size: 0.75rem;
      word-break: break-all;
    }
  </style>
</head>

<body class="bg-light">
  <div class="container my-4" style="font-size:12px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex">
        <input style="width:400px" type="text" id="searchBox" class="form-control me-2" placeholder="Search…">
        <select style="width:200px" id="statusFilter" class="form-select">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
          <option value="shipped">Shipped</option>
          <option value="completed">Completed</option>
        </select>
      </div>
      <a href="pos.php" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> New Order
      </a>
    </div>

    <div class="table-responsive table-wrapper">
      <table id="orderTable" class="table table-bordered table-hover table-sm">
        <thead>
          <tr>
            <th>No</th>
            <th>Username</th>
            <th>Type</th>
            <th>Quantity</th>
            <th>Method</th>
            <th>Paid At</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Barcodes</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order):
            $current = $order['status'];
            $next    = $transitions[$current] ?? [];
          ?>
            <tr data-id="<?= $order['id'] ?>">
              <td><?= $n-- ?></td>
              <td><?= htmlspecialchars($order['username'] ?: '-') ?></td>
              <td>
                <span class="badge <?= $order['typorder'] == 'online' ? 'bg-info' : 'bg-secondary' ?>">
                  <?= strtoupper($order['typorder']) ?>
                </span>
              </td>
              <td><?= $order['item_count'] ?></td>
              <td>
                <span class="badge bg-<?=
                                      $order['pay_method'] == 'cash' ? 'success' : ($order['pay_method'] == 'card' ? 'primary' : 'secondary')
                                      ?>">
                  <?= ucfirst($order['pay_method']) ?>
                </span>
              </td>
              <td><?= $order['paid_at']
                    ? date('Y-m-d H:i', strtotime($order['paid_at']))
                    : '-' ?></td>
              <td>$<?= number_format($order['amount_usd'], 2) ?></td>
              <td>
                <span class="badge bg-<?=
                                      $current == 'pending'   ? 'warning' : ($current == 'paid'     ? 'success' : ($current == 'shipped'  ? 'info'    : ($current == 'completed' ? 'secondary' : 'secondary')))
                                      ?>">
                  <?= ucfirst($current) ?>
                </span>
              </td>
              <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
              <td class="text-center">
                <button class="btn btn-sm btn-secondary btn-barcodes"
                  data-barcodes="<?= htmlspecialchars($order['barcodes']) ?>">
                  <i class="bi bi-upc-scan"></i>
                </button>
              </td>
              <td class="text-center">
                <!-- Always: View -->
                <button class="btn btn-sm btn-info btn-view mx-1" data-id="<?= $order['id'] ?>">
                  <i class="bi bi-eye"></i>
                </button>

                <?php if ($current !== 'completed'): ?>
                  <!-- Status-change buttons -->
                  <?php foreach ($next as $to => $label):
                    $iconMap  = ['paid' => 'bi-cash-stack', 'shipped' => 'bi-truck', 'completed' => 'bi-check-circle'];
                    $colorMap = ['paid' => 'btn-success', 'shipped' => 'btn-info', 'completed' => 'btn-primary'];
                  ?>
                    <button class="btn btn-sm <?= $colorMap[$to] ?> btn-change-status mx-1"
                      data-id="<?= $order['id'] ?>"
                      data-status="<?= $to ?>"
                      title="<?= $label ?>">
                      <i class="<?= $iconMap[$to] ?> text-white"></i>
                    </button>
                  <?php endforeach; ?>

                  <!-- Edit -->
                  <a href="pos.php?mode=edit&id=<?= $order['id'] ?>"
                    class="btn btn-sm btn-warning mx-1"
                    title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                <?php endif; ?>

                <!-- Delete -->
                <button class="btn btn-sm btn-danger btn-delete mx-1">
                  <i class="bi bi-trash"></i>
                </button>
                <!-- Print -->
                <a href="print_invoice.php?id=<?= $order['id'] ?>" target="_blank"
                  class="btn btn-sm btn-success mx-1" title="Print">
                  <i class="bi bi-printer"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title">Order Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="table-responsive col-md-8" style="max-height:250px; overflow-y:auto;">
                <table id="orderTable" class="table table-bordered table-hover table-sm" style="font-size:0.7rem;">
                  <thead style="font-size: 12px;" class="sticky-top custom-header">
                    <tr>
                      <th>No</th>
                      <th>Product</th>
                      <th class="text-end">Qty</th>
                      <th class="text-end">Unit Price</th>
                      <th class="text-end">Line Total</th>
                    </tr>
                  </thead>
                  <tbody id="viewOrderTable"></tbody>
                </table>
              </div>
              <div class="col-md-4">
                <div class="card summary-card">
                  <div class="card-body p-3">
                    <h6 class="card-title mb-3">Summary</h6>
                    <ul class="list-unstyled summary-list mb-3">
                      <li class="d-flex justify-content-between">
                        <span>Subtotal</span><span id="sumSubtotal">$0.00</span>
                      </li>
                      <li class="d-flex justify-content-between">
                        <span>Tax (10%)</span><span id="sumTax">$0.00</span>
                      </li>
                      <li class="d-flex justify-content-between">
                        <span>Discount</span><span id="sumDiscount">- $0.00</span>
                      </li>
                    </ul>
                    <hr class="my-2">
                    <ul class="list-unstyled summary-list mb-0">
                      <li class="d-flex justify-content-between fw-bold">
                        <span>Total (USD)</span><span id="sumTotalUsd">$0.00</span>
                      </li>
                      <li class="d-flex justify-content-between fw-bold">
                        <span>Total (KHR)</span><span id="sumTotalKhr">៛0</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0">
            <button id="btn-complete-order" class="btn btn-success">Confirm Complete Order</button>
            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Barcode Modal -->
    <div class="modal fade" id="barcodeModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable modal-sm">
        <div style="width: 500px;" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Order Barcodes</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="text" id="barcodeInput" class="form-control mb-2" placeholder="Scan or enter barcode" autofocus>
            <div id="scanResult" class="result mb-2"></div>
            <div id="barcodeContainer"></div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-primary" onclick="printBarcodes()">Print</button>

            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    let currentViewOrderId = null;
    let currentOrder = null;
    function printBarcodes() {
  if (!currentOrder) return;
  const w = window.open('', '_blank');
  w.document.write(`
    <html><head><title>Order #${currentOrder.orderId}</title>
      <style>
        body { font-family: Arial, sans-serif; margin:20px }
        h1 { margin-bottom:.5em }
        section { margin-bottom:1.2em }
        .barcodes { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem }
        .barcode-item { text-align:center }
        .barcode-item img { max-width:100% }
        .barcode-item div { font-size:.8em; word-break:break-all; margin-top:.3em }
      </style>
    </head><body>
      <h1>Order #${currentOrder.orderId}</h1>
      <section>
        <strong>Customer:</strong><br>
        ${currentOrder.customer.name}<br>
        ${currentOrder.customer.phone}<br>
        ${currentOrder.customer.email}
      </section>
      <section>
        <strong>Products:</strong>
        <ul>
          ${currentOrder.items.map(i=>
            `<li>${i.name} × ${i.qty}</li>`
          ).join('')}
        </ul>
      </section>
      <section class="barcodes">
        ${currentOrder.barcodes.map(code=>`
          <div class="barcode-item">
            <img 
              src="https://barcode.tec-it.com/barcode.ashx?
                   data=${encodeURIComponent(code)}&
                   code=Code128&
                   unit=Fit&
                   dpi=96" 
              alt="${code}">
            <div>${code}</div>
          </div>
        `).join('')}
      </section>
    </body></html>
  `);
  w.document.close();
  w.onload = ()=>{ w.print(); w.close(); };
}
                  
    // Delegate all “View” clicks
    document.querySelector('#orderTable tbody').addEventListener('click', async e => {
      const btn = e.target.closest('.btn-view');
      if (!btn) return;

      currentViewOrderId = btn.dataset.id || btn.closest('tr').dataset.id;
      if (!currentViewOrderId) return Swal.fire('Error', 'No order ID', 'error');

      try {
        const res = await fetch(`get_order_details.php?id=${encodeURIComponent(currentViewOrderId)}`);
        const js = await res.json();
        if (!js.success) throw new Error(js.message || 'Unknown error');

        // populate items
        const tb = document.getElementById('viewOrderTable');
        tb.innerHTML = '';
        js.items.forEach(it => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${it.name}</td>
            <td class="text-end">${it.qty}</td>
            <td class="text-end">$${it.price.toFixed(2)}</td>
            <td class="text-end">$${it.total.toFixed(2)}</td>
          `;
          tb.appendChild(tr);
        });

        // summary
        document.getElementById('sumSubtotal').textContent = '$' + js.subtotal.toFixed(2);
        document.getElementById('sumTax').textContent = '$' + js.tax.toFixed(2);
        document.getElementById('sumDiscount').textContent = '- $' + js.discount.toFixed(2);
        document.getElementById('sumTotalUsd').textContent = '$' + js.totalUsd.toFixed(2);
        document.getElementById('sumTotalKhr').textContent = '៛' + Math.round(js.totalKhr).toLocaleString();

        // only show “Complete” if shipped
        document.getElementById('btn-complete-order').style.display =
          js.status === 'shipped' ? '' : 'none';

        // show modal and reload on close
        const modalEl = document.getElementById('viewOrderModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        modalEl.addEventListener('hidden.bs.modal', () => location.reload(), {
          once: true
        });

      } catch (err) {
        Swal.fire('Error', err.message, 'error');
      }
    });

    // Handle “Confirm Complete Order”
    document.getElementById('btn-complete-order').addEventListener('click', async () => {
      if (!currentViewOrderId) return Swal.fire('Error', 'No order selected', 'error');
      const {
        value: ok
      } = await Swal.fire({
        title: `Complete order #${currentViewOrderId}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, complete it'
      });
      if (!ok) return;

      try {
        const res = await fetch('update_order_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: new URLSearchParams({
            id: currentViewOrderId,
            status: 'completed'
          })
        });
        const js = await res.json();
        if (!js.success) throw new Error(js.msg || 'Unknown error');

        Swal.fire('Done!', 'Order has been marked completed.', 'success')
          .then(() => location.reload());
      } catch (err) {
        Swal.fire('Error', err.message, 'error');
      }
    });

    // Status‐change and delete handlers (auto-reload on success)
    document.querySelectorAll('.btn-change-status').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id,
          status = btn.dataset.status;
        const {
          value: ok
        } = await Swal.fire({
          title: `Move order #${id} → ${status}?`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes'
        });
        if (!ok) return;

        fetch('update_order_status.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
              id,
              status
            })
          })
          .then(r => r.json())
          .then(js => {
            if (js.success) {
              Swal.fire('Done!', 'Status updated', 'success').then(() => location.reload());
            } else {
              Swal.fire('Error', js.msg, 'error');
            }
          })
          .catch(() => Swal.fire('Error', 'Network error', 'error'));
      });
    });

    document.querySelector('#orderTable tbody').addEventListener('click', async e => {
      const btn = e.target.closest('.btn-delete');
      if (!btn) return;
      const tr = btn.closest('tr'),
        id = tr.dataset.id;
      const {
        value: ok
      } = await Swal.fire({
        title: `Delete order #${id}?`,
        text: "This cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!"
      });
      if (!ok) return;
      fetch('delete_order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: new URLSearchParams({
            id
          })
        })
        .then(r => r.json())
        .then(js => {
          if (js.success) {
            Swal.fire('Deleted!', 'Order has been deleted.', 'success')
              .then(() => location.reload());
          } else {
            Swal.fire('Error', js.message, 'error');
          }
        })
        .catch(() => Swal.fire('Error', 'Network error', 'error'));
    });

    // VIEW ORDER via AJAX + modal
    // delegate "view" clicks inside the tbody
    document
      .querySelector('#orderTable tbody')
      .addEventListener('click', async e => {
        const btn = e.target.closest('.btn-view');
        if (!btn) return;

        // first try data-id on the button, then fallback to the row’s data-id
        const id = btn.dataset.id || btn.closest('tr').dataset.id;
        if (!id) return Swal.fire('Error', 'No order ID found', 'error');

        try {
          const res = await fetch(`get_order_details.php?id=${encodeURIComponent(id)}`);
          const js = await res.json();

          if (!js.success) {
            throw new Error(js.message || 'Unknown error');
          }

          // populate the modal…
          const tb = document.getElementById('viewOrderTable');
          tb.innerHTML = '';
          const items = js.items;
          const total = items.length;
          items.forEach((it, idx) => {
            const tr = document.createElement('tr');
            const rowNum = total - idx;
            tr.innerHTML = `
            <td>${rowNum}</td>
            <td>${it.name}</td>
            <td class="text-end">${it.qty}</td>
            <td class="text-end">$${it.price.toFixed(2)}</td>
            <td class="text-end">$${it.total.toFixed(2)}</td>
  `;
            tb.appendChild(tr);
          });
          document.getElementById('sumSubtotal').textContent = '$' + js.subtotal.toFixed(2);
          document.getElementById('sumTax').textContent = '$' + js.tax.toFixed(2);
          document.getElementById('sumDiscount').textContent = '- $' + js.discount.toFixed(2);
          document.getElementById('sumTotalUsd').textContent = '$' + js.totalUsd.toFixed(2);
          document.getElementById('sumTotalKhr').textContent = '៛' + Math.round(js.totalKhr).toLocaleString();

          new bootstrap.Modal(document.getElementById('viewOrderModal')).show();
          document.getElementById('viewOrderModal')
            .addEventListener('hidden.bs.modal', () => {
              location.reload();
            });
        } catch (err) {
          Swal.fire('Error', err.message, 'error');
        }
      });


    const searchBox = document.getElementById('searchBox');
    const statusFilter = document.getElementById('statusFilter');
    const rows = document.querySelectorAll('#orderTable tbody tr');

    function applyFilters() {
      const q = searchBox.value.toLowerCase();
      const stat = statusFilter.value; // "" or "pending" or "paid"...

      rows.forEach(tr => {
        const textMatch = tr.textContent.toLowerCase().includes(q);

        // find the status cell (8th <td>)
        const cell = tr.children[7];
        const rowStatus = cell.textContent.trim().toLowerCase();

        const statusMatch = !stat || rowStatus === stat;

        tr.style.display = (textMatch && statusMatch) ? '' : 'none';
      });
    }

    searchBox.addEventListener('input', applyFilters);
    statusFilter.addEventListener('change', applyFilters);

    // Run once on page load in case you want the default filter applied immediately:
    applyFilters();


    // DELETE with SweetAlert
    document.querySelector('#orderTable tbody').addEventListener('click', async e => {
      const btn = e.target.closest('.btn-delete');
      if (!btn) return;
      const tr = btn.closest('tr'),
        id = tr.dataset.id;
      const {
        value: ok
      } = await Swal.fire({
        title: `Delete order #${id}?`,
        text: "This cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "Cancel"
      });
      if (!ok) return;
      fetch('delete_order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: new URLSearchParams({
            id
          })
        })
        .then(r => r.json())
        .then(js => {
          if (js.success) {
            tr.remove();
            Swal.fire('Deleted!', 'Order has been deleted.', 'success');
          } else Swal.fire('Error', js.message, 'error');
        })
        .catch(_ => Swal.fire('Error', 'Network error', 'error'));
    });

    // BARCODE modal
    document.querySelectorAll('.btn-barcodes').forEach(btn => {
      btn.addEventListener('click', () => {
        const codes = btn.dataset.barcodes.split(',').map(s => s.trim());
        const container = document.getElementById('barcodeContainer');
        container.innerHTML = '';

        codes.forEach(code => {
          const wrap = document.createElement('div');
          wrap.className = 'barcode-item'; // <-- was "text-center m-2"

          const img = document.createElement('img');
          img.src = `https://barcode.tec-it.com/barcode.ashx?data=${encodeURIComponent(code)}&code=Code128&unit=Fit&dpi=96`;
          img.alt = code;
          wrap.appendChild(img);

          const lbl = document.createElement('div');
          lbl.textContent = code;
          wrap.appendChild(lbl);

          container.appendChild(wrap);
        });

        new bootstrap.Modal(document.getElementById('barcodeModal')).show();
      });
    });
    // after you’ve wired up the barcode-button click handler:

    // grab once
    const barcodeInput = document.getElementById('barcodeInput');
    const barcodeItems = () => Array.from(
      document.querySelectorAll('#barcodeContainer .barcode-item')
    );

    // on every keystroke…
    barcodeInput.addEventListener('input', () => {
      const q = barcodeInput.value.trim();
      // if empty, show all
      if (!q) {
        barcodeItems().forEach(item => item.style.display = '');
        return;
      }
      // otherwise, filter
      barcodeItems().forEach(item => {
        const code = item.querySelector('div').textContent;
        // case-insensitive substring match
        item.style.display = code.toLowerCase().includes(q.toLowerCase()) ?
          '' :
          'none';
      });
    });
  </script>
</body>

</html>
<?php include __DIR__ . '/templates/footer.php'; ?>