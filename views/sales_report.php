<?php
// views/sales_report.php
session_start();
require_once __DIR__ . '/../config/db.php';

// 1) Auth guard
$self = basename($_SERVER['PHP_SELF']);
if ($self !== 'login.php' && empty($_SESSION['user_id'])) {
  header('Location: ../auth/login.php');
  exit;
}

$pageTitle = 'Sales Report';
include __DIR__ . '/templates/header.php';

// 2) Drill-down params
$window = $_GET['window'] ?? null;   // '24h','3d','7d','30d','1y'
$filter = $_GET['filter'] ?? null;

// 3) Time-series aggregates
$today = date('Y-m-d');
// a) Last 24h (hourly)
$stmt = $conn->prepare("
    SELECT HOUR(created_at) AS h, SUM(total_usd) AS total
      FROM orders
     WHERE status='completed'
       AND DATE(created_at)=?
     GROUP BY h
     ORDER BY h
");
$stmt->bind_param('s', $today);
$stmt->execute();
$hourly = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// b) Last 3,7,30 days (daily)
$last3  = $conn->query("
    SELECT DATE(created_at) AS d, SUM(total_usd) AS total
      FROM orders
     WHERE status='completed'
       AND created_at >= CURDATE() - INTERVAL 2 DAY
     GROUP BY d
     ORDER BY d
")->fetch_all(MYSQLI_ASSOC);
$last7  = $conn->query("
    SELECT DATE(created_at) AS d, SUM(total_usd) AS total
      FROM orders
     WHERE status='completed'
       AND created_at >= CURDATE() - INTERVAL 7 DAY
     GROUP BY d
     ORDER BY d
")->fetch_all(MYSQLI_ASSOC);
$last30 = $conn->query("
    SELECT DATE(created_at) AS d, SUM(total_usd) AS total
      FROM orders
     WHERE status='completed'
       AND created_at >= CURDATE() - INTERVAL 30 DAY
     GROUP BY d
     ORDER BY d
")->fetch_all(MYSQLI_ASSOC);

// c) Last 12 months (monthly)
$last12 = $conn->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS m, SUM(total_usd) AS total
      FROM orders
     WHERE status='completed'
       AND created_at >= CURDATE() - INTERVAL 1 YEAR
     GROUP BY m
     ORDER BY m
")->fetch_all(MYSQLI_ASSOC);

// 4) KPI metrics helper
function fetchMetrics($interval)
{
  global $conn, $today;
  if ($interval === '1 DAY') {
    $sql = "SELECT COUNT(*) AS cnt, SUM(total_usd) AS total
                  FROM orders
                 WHERE status='completed'
                   AND DATE(created_at)=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $today);
  } else {
    $sql = "SELECT COUNT(*) AS cnt, SUM(total_usd) AS total
                  FROM orders
                 WHERE status='completed'
                   AND created_at >= NOW() - INTERVAL {$interval}";
    $stmt = $conn->prepare($sql);
  }
  $stmt->execute();
  $m = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $m['avg'] = $m['cnt'] ? $m['total'] / $m['cnt'] : 0;
  return $m;
}
$metrics = [
  '1d'  => fetchMetrics('1 DAY'),
  '7d'  => fetchMetrics('7 DAY'),
  '30d' => fetchMetrics('30 DAY'),
  '1y'  => fetchMetrics('1 YEAR'),
];

// 5) Payment‐method breakdown (last 30d)
$pm = $conn->query("
    SELECT p.method, SUM(p.amount_usd) AS revenue
      FROM payments p
      JOIN orders o ON o.id = p.order_id
     WHERE o.status='completed'
       AND p.paid_at >= NOW() - INTERVAL 30 DAY
     GROUP BY p.method
")->fetch_all(MYSQLI_ASSOC);

// 6) Top‐5 Products helper
function fetchTopProducts($interval)
{
  global $conn;
  $sql = "
      SELECT pr.name,
             SUM(oi.quantity * oi.unit_price) AS revenue
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products pr   ON pr.id       = oi.product_id
       WHERE o.status='completed'
         AND o.created_at >= NOW() - INTERVAL {$interval}
       GROUP BY pr.id
       ORDER BY revenue DESC
       LIMIT 5
    ";
  return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}
$top1d  = fetchTopProducts('1 DAY');
$top7d  = fetchTopProducts('7 DAY');
$top30d = fetchTopProducts('1 MONTH');
$top1y  = fetchTopProducts('1 YEAR');

// 7) Top-5 Countries by shipping_country (last 30d)
$topCountries = $conn->query("
  SELECT shipping_country AS code,
         SUM(total_usd)      AS revenue
    FROM orders
   WHERE status='completed'
     AND created_at >= CURDATE() - INTERVAL 30 DAY
   GROUP BY shipping_country
   ORDER BY revenue DESC
   LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// 8) All completed orders + sparkline

$allCompleted = $conn->query("
  SELECT
    o.id,
    u.username,
    IF(p.method='online','Online','POS')     AS typorder,
    COUNT(oi.id)                             AS item_count,
    COALESCE(p.method,'cash')                AS pay_method,
    p.paid_at,
    COALESCE(p.amount_usd,0)                 AS amount_usd,
    o.status,
    o.created_at,
    GROUP_CONCAT(DISTINCT pr.barcode SEPARATOR ', ') AS barcodes
  FROM orders o
  LEFT JOIN users       u  ON u.id       = o.user_id
  LEFT JOIN payments    p  ON p.order_id = o.id
  LEFT JOIN order_items oi ON oi.order_id = o.id
  LEFT JOIN products    pr ON pr.id      = oi.product_id
  WHERE o.status='completed'
  GROUP BY o.id
  ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$map7 = array_column($last7, 'total', 'd');
$spark7 = [];
for ($i = 6; $i >= 0; $i--) {
  $day = date('Y-m-d', strtotime("-{$i} days"));
  $spark7[] = $map7[$day] ?? 0;
}
$spark7Str = implode(',', $spark7);

// 9) Monthly comparison (this vs last year)
$monthlyThis = $conn->query("
    SELECT MONTH(created_at) AS m, SUM(total_usd) AS total
      FROM orders
     WHERE status='completed'
       AND YEAR(created_at)=YEAR(CURDATE())
     GROUP BY m
")->fetch_all(MYSQLI_ASSOC);
$monthlyLast = $conn->query("
    SELECT MONTH(created_at) AS m, SUM(total_usd) AS total
      FROM orders
     WHERE status='completed'
       AND YEAR(created_at)=YEAR(CURDATE())-1
     GROUP BY m
")->fetch_all(MYSQLI_ASSOC);
$thisMap = array_column($monthlyThis, 'total', 'm');
$lastMap = array_column($monthlyLast, 'total', 'm');
$monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$thisYearData = $lastYearData = [];
for ($i = 1; $i <= 12; $i++) {
  $thisYearData[] = $thisMap[$i] ?? 0;
  $lastYearData[] = $lastMap[$i] ?? 0;
}

?>
<?php
// approximate centroids for common ISO2 codes



// build the 2 JS arrays: one for the "lines", one for the "scatter" points
$lines = $points = [];
foreach ($topCountries as $c) {
  $code = $c['code'];
  if (!isset($coords[$code])) continue;
  $dest = $coords[$code];
  $lines[]  = ['coords' => [$origin, $dest]];
  // value array: [lon, lat, revenue] so we can use tooltip on scatter
  $points[] = [
    'name'  => $code,
    'value' => [$dest[0], $dest[1], $c['revenue']]
  ];
}
?>

<style>
  body {
    background-color: #f8f9fa;
  }

  .orders-table {
    max-height: 400px;
    overflow-y: auto;
  }

  .chart-card {
    min-height: 260px;
  }

  .sparkline-cell {
    width: 120px;
    white-space: nowrap;
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

<div class="container-fluid mt-4">


  <!-- KPI Summary -->
  <div class="card-group mb-4">
    <?php foreach (['1d' => 'Last 1d', '7d' => 'Last 7d', '30d' => 'Last 30d', '1y' => 'Last 12m'] as $k => $lbl):
      $m = $metrics[$k];
    ?>
      <div class="card text-center me-2">
        <div class="card-body">
          <small class="text-muted"><?= $lbl ?></small>
          <h3 class="my-2">$<?= number_format($m['total'], 2) ?></h3>
          <p class="mb-1"><strong><?= $m['cnt'] ?></strong> Orders</p>
          <small class="text-muted">AOV $<?= number_format($m['avg'], 2) ?></small>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div style="margin-bottom: 20px; max-height: 200px;" class="card mt-2">
    <div class="card-header bg-white">
      <small class="text-muted">All Completed Orders</small>
    </div>
    <div class="table-responsive" style="max-height:200px; overflow-y:auto;">
      <table id="orderTable" class="table table-bordered table-hover table-sm" style="font-size:0.7rem;">
        <thead class="sticky-top custom-header">
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
          <?php
          $no = count($allCompleted);
          foreach ($allCompleted as $order):
            // safely pull each field with a default
            $username   = htmlspecialchars($order['username'] ?? '-');
            $type       = htmlspecialchars($order['typorder']  ?? 'POS');
            $qty        = (int)($order['item_count'] ?? 0);
            $method     = htmlspecialchars(ucfirst($order['pay_method'] ?? 'cash'));
            $paidAt     = !empty($order['paid_at'])
              ? date('Y-m-d H:i', strtotime($order['paid_at']))
              : '-';
            $amount     = number_format((float)($order['amount_usd'] ?? 0), 2);
            $status     = htmlspecialchars(ucfirst($order['status'] ?? ''));
            $created    = date('Y-m-d H:i', strtotime($order['created_at']));
            $barcodes   = htmlspecialchars($order['barcodes'] ?? '');
          ?>
            <tr data-id="<?= $order['id'] ?>">
              <td><?= $no-- ?></td>
              <td><?= $username ?></td>
              <td>
                <span class="badge bg-secondary"><?= $type ?></span>
              </td>
              <td><?= $qty ?></td>
              <td>
                <span class="badge bg-<?=
                                      $method === 'Cash' ? 'success' : ($method === 'Card' ? 'primary' : 'info')
                                      ?>">
                  <?= $method ?>
                </span>
              </td>
              <td><?= $paidAt ?></td>
              <td>$<?= $amount ?></td>
              <td>
                <span class="badge bg-<?=
                                      $status === 'Pending'   ? 'warning' : ($status === 'Paid'     ? 'success' : ($status === 'Shipped'  ? 'info'    : ($status === 'Completed' ? 'secondary' : 'dark')))
                                      ?>">
                  <?= $status ?>
                </span>
              </td>
              <td><?= $created ?></td>
              <td class="text-center">
                <button
                  class="btn btn-sm btn-secondary btn-barcodes"
                  data-barcodes="<?= htmlspecialchars($order['barcodes']) ?>">
                  <i class="bi bi-upc-scan"></i>
                </button>
              </td>
              <td class="text-center">
                <button
                  class="btn btn-sm btn-info btn-view"
                  data-id="<?= $order['id'] ?>">
                  <i class="bi bi-eye"></i>
                </button>
                <a href="print_invoice.php?id=<?= $order['id'] ?>"
                  target="_blank"
                  class="btn btn-sm btn-success">
                  <i class="bi bi-printer"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
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
  <div class="row">

    <!-- Left: Charts & Products -->
    <div class="col-lg-12">
      <div class="row g-3">
        <?php foreach (
          [
            'chart-24h' => 'Last 24h',
            'chart-3d' => 'Last 3 Days',
            'chart-7d' => 'Last 7 Days',
            'chart-30d' => 'Last 30 Days',
            'chart-1y' => 'Last 12 Months'
          ] as $id => $title
        ): ?>
          <div class="col-md-6">
            <div class="card chart-card h-100">
              <div class="card-header bg-white">
                <small class="text-muted"><?= $title ?></small>
              </div>
              <div class="card-body p-2">
                <canvas id="<?= $id ?>" style="height:160px;"></canvas>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- payment -->
        <div class="col-12 col-md-6">
          <div class="card chart-card h-100">
            <div class="card-header bg-white">
              <small class="text-muted">Payment Methods (30d)</small>
            </div>
            <div class="card-body d-flex justify-content-center align-items-center">
              <canvas id="paymentPie" style="height:180px;width:180px;"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Products -->
      <div class="mt-4">
        <h5>Top-5 Products by Revenue</h5>
        <div class="row g-3">
          <?php
          $prodSets = [
            ['prod-1d', 'Last 1 Day', $top1d],
            ['prod-7d', 'Last 7 Days', $top7d],
            ['prod-30d', 'Last 30 Days', $top30d],
            ['prod-1y', 'Last 12 Months', $top1y],
          ];
          foreach ($prodSets as $set):
            list($pid, $ttl, $pdata) = $set;
          ?>
            <div class="col-md-6">
              <div class="card h-100">
                <div class="card-header bg-white"><small class="text-muted"><?= $ttl ?></small></div>
                <div class="card-body p-2">
                  <canvas id="<?= $pid ?>" style="height:140px;"></canvas>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <!-- Monthly Comparison -->
    <div style="margin-top: 20px;" class="row mb-1">
      <div class="col-12">
        <div class="card chart-card">
          <div class="card-header bg-white">
            <small class="text-muted">Monthly Comparison (This Year vs Last Year)</small>
          </div>
          <div class="card-body p-3">
            <canvas id="monthlyComparison" style="height:200px;"></canvas>
          </div>
        </div>
      </div>
    </div>




  </div>
  <!-- jVectorMap -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/jqvmap@1.5.1/dist/jqvmap.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/jqvmap@1.5.1/dist/jquery.vmap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jqvmap@1.5.1/dist/maps/jquery.vmap.world.js"></script>

  <!-- Dependencies -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.3.0/dist/chartjs-plugin-annotation.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-sparklines/2.1.2/jquery.sparkline.min.js"></script>

  <script>
    const tbody = document.querySelector('#orderTable tbody');
    tbody.addEventListener('click', async e => {
      // 1) SCAN (barcode) button
      // 1) grab your modal, input, and container
      // keep track of the last-opened order
      let currentOrder = null;

      // modal + search input + container
      const barcodeModalEl = document.getElementById('barcodeModal');
      const barcodeInput = document.getElementById('barcodeInput');
      const barcodeContainer = document.getElementById('barcodeContainer');

      // whenever the barcode-modal opens, reset the search
      barcodeModalEl.addEventListener('show.bs.modal', () => {
        barcodeInput.value = '';
        Array.from(barcodeContainer.children).forEach(i => i.style.display = '');
      });

      // filter as you type
      barcodeInput.addEventListener('input', () => {
        const q = barcodeInput.value.trim().toLowerCase();
        Array.from(barcodeContainer.querySelectorAll('.barcode-item'))
          .forEach(item => {
            const code = item.querySelector('div').textContent.toLowerCase();
            item.style.display = (!q || code.includes(q)) ? '' : 'none';
          });
      });


      // 4) your existing click‐handler for the “scan” button stays almost the same,
      //    but now you don’t include the filtering logic inside it:

      tbody.addEventListener('click', async e => {
        const scanBtn = e.target.closest('.btn-barcodes');
        if (!scanBtn) return;

        // populate all the items
        const codes = scanBtn.dataset.barcodes.split(',').map(s => s.trim());
        barcodeContainer.innerHTML = '';
        codes.forEach(code => {
          const wrap = document.createElement('div');
          wrap.className = 'barcode-item';

          const img = document.createElement('img');
          img.src = `https://barcode.tec-it.com/barcode.ashx?data=${encodeURIComponent(code)}&code=Code128&unit=Fit&dpi=96`;
          img.alt = code;

          const lbl = document.createElement('div');
          lbl.textContent = code;

          wrap.appendChild(img);
          wrap.appendChild(lbl);
          barcodeContainer.appendChild(wrap);
        });

        // finally show the modal
        new bootstrap.Modal(barcodeModalEl).show();
      });


      // 2) VIEW (order details) button
      const viewBtn = e.target.closest('.btn-view');
      if (viewBtn) {
        const id = viewBtn.dataset.id;
        try {
          const res = await fetch(`get_order_details.php?id=${encodeURIComponent(id)}`);
          const js = await res.json();
          if (!js.success) throw new Error(js.message || 'Load failed');

          // populate table
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

          // update summary
          document.getElementById('sumSubtotal').textContent = '$' + js.subtotal.toFixed(2);
          document.getElementById('sumTax').textContent = '$' + js.tax.toFixed(2);
          document.getElementById('sumDiscount').textContent = '- $' + js.discount.toFixed(2);
          document.getElementById('sumTotalUsd').textContent = '$' + js.totalUsd.toFixed(2);
          document.getElementById('sumTotalKhr').textContent = '៛' + Math.round(js.totalKhr).toLocaleString();

          new bootstrap.Modal(document.getElementById('viewOrderModal')).show();
        } catch (err) {
          Swal.fire('Error', err.message, 'error');
        }
      }
    });



    // Chart helpers
    function drawLine(id, labels, data) {
      new Chart(document.getElementById(id).getContext('2d'), {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Sales (USD)',
            data,
            tension: 0.3,
            borderColor: '#4bc0c0',
            fill: false
          }]
        },
        options: {
          responsive: true,
          animation: {
            duration: 0
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    }

    function drawBar(id, labels, data) {
      new Chart(document.getElementById(id).getContext('2d'), {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Revenue (USD)',
            data,
            backgroundColor: 'rgba(54,162,235,0.6)',
            borderColor: 'rgba(54,162,235,1)',
            borderWidth: 1
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          animation: {
            duration: 0
          },
          scales: {
            x: {
              beginAtZero: true
            }
          }
        }
      });
    }

    // Prepare and draw time-series
    const hrsMap = <?= json_encode(array_column($hourly, 'total', 'h')) ?>;
    let hrsLabels = [],
      hrsData = [];
    for (let h = 0; h < 24; h++) {
      hrsLabels.push(String(h));
      hrsData.push(hrsMap[h] || 0);
    }
    const d3Map = <?= json_encode(array_column($last3, 'total', 'd')) ?>;
    const d7Map = <?= json_encode(array_column($last7, 'total', 'd')) ?>;
    const d30Map = <?= json_encode(array_column($last30, 'total', 'd')) ?>;
    const d1yMap = <?= json_encode(array_column($last12, 'total', 'm')) ?>;

    drawLine('chart-24h', hrsLabels, hrsData);
    drawLine('chart-3d', Object.keys(d3Map), Object.values(d3Map));
    drawLine('chart-7d', Object.keys(d7Map), Object.values(d7Map));
    drawLine('chart-30d', Object.keys(d30Map), Object.values(d30Map));
    drawLine('chart-1y', Object.keys(d1yMap), Object.values(d1yMap));

    // Payment pie
    const pmLabels = <?= json_encode(array_column($pm, 'method')) ?>;
    const pmData = <?= json_encode(array_column($pm, 'revenue')) ?>;
    new Chart(document.getElementById('paymentPie').getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: pmLabels,
        datasets: [{
          data: pmData,
          backgroundColor: ['#4bc0c0', '#36a2eb', '#ffcd56', '#e7e9ed']
        }]
      },
      options: {
        responsive: true,
        animation: {
          duration: 0
        }
      }
    });

    // Top products
    const top1dN = <?= json_encode(array_column($top1d, 'name')) ?>;
    const top1dR = <?= json_encode(array_column($top1d, 'revenue')) ?>;
    const top7dN = <?= json_encode(array_column($top7d, 'name')) ?>;
    const top7dR = <?= json_encode(array_column($top7d, 'revenue')) ?>;
    const top30N = <?= json_encode(array_column($top30d, 'name')) ?>;
    const top30R = <?= json_encode(array_column($top30d, 'revenue')) ?>;
    const top1yN = <?= json_encode(array_column($top1y, 'name')) ?>;
    const top1yR = <?= json_encode(array_column($top1y, 'revenue')) ?>;

    drawBar('prod-1d', top1dN, top1dR);
    drawBar('prod-7d', top7dN, top7dR);
    drawBar('prod-30d', top30N, top30R);
    drawBar('prod-1y', top1yN, top1yR);

    // Sparklines
    $('.sparkline').each(function() {
      let vals = $(this).data('values').toString().split(',').map(parseFloat);
      $(this).sparkline(vals, {
        type: 'line',
        width: '100px',
        height: '30px',
        lineColor: '#4bc0c0',
        fillColor: false,
        spotRadius: 0
      });
    });

    // Monthly Comparison
    const mLabels = <?= json_encode($monthlyLabels) ?>;
    const thisYr = <?= json_encode($thisYearData) ?>;
    const lastYr = <?= json_encode($lastYearData) ?>;

    new Chart(
      document.getElementById('monthlyComparison').getContext('2d'), {
        type: 'line',
        data: {
          labels: mLabels,
          datasets: [{
              label: 'This Year',
              data: thisYr,
              borderColor: '#36A2EB',
              backgroundColor: 'rgba(54,162,235,0.1)',
              pointRadius: 4,
              fill: true
            },
            {
              label: 'Last Year',
              data: lastYr,
              borderColor: '#FF9F40',
              backgroundColor: 'rgba(255,159,64,0.1)',
              pointRadius: 4,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          animation: {
            duration: 0
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: v => v >= 1000 ? '$' + (v / 1000) + 'k' : '$' + v
              }
            }
          },
          plugins: {
            annotation: {
              annotations: {
                juneLine: {
                  type: 'line',
                  xMin: 5,
                  xMax: 5,
                  borderColor: '#888',
                  borderDash: [6, 6],
                  label: {
                    content: ['50k'],
                    enabled: true,
                    position: 'start',
                    backgroundColor: '#6F42C1',
                    color: '#fff',
                    font: {
                      weight: 'bold'
                    },
                    yAdjust: -10
                  }
                }
              }
            }
          }
        }
      }
    );
  </script>

  <?php include __DIR__ . '/templates/footer.php'; ?>