<?php
// C:\xampp\htdocs\coffee-shop\views\dashboard.php

// 1) Auth guard & DB
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
$self = basename($_SERVER['PHP_SELF']);
if ($self !== 'login.php' && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

try {
    // — Total users
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM `users`");
    $stmt->execute();
    $userCnt = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    // — Total categories
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM `categories`");
    $stmt->execute();
    $catCnt = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    // — Total products
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM `products`");
    $stmt->execute();
    $prodCnt = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    // — Total completed orders
    $stmt = $conn->prepare("
      SELECT COUNT(*) AS cnt
        FROM `orders`
       WHERE `status` = 'completed'
    ");
    $stmt->execute();
    $orderCnt = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    // — Total revenue (sum of price_real on completed orders)
    $stmt = $conn->prepare("
      SELECT COALESCE(SUM(`price_real`),0) AS revenue
        FROM `orders`
       WHERE `status` = 'completed'
    ");
    $stmt->execute();
    $revenue = $stmt->get_result()->fetch_assoc()['revenue'];
    $stmt->close();

    // — Selected date (from GET or default to today)
    $selectedDate = $_GET['date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
        $selectedDate = date('Y-m-d');
    }

    // — Sales on selected date
    $stmt = $conn->prepare("
      SELECT
        COUNT(*)             AS cnt,
        COALESCE(SUM(price_real),0) AS sum
      FROM `orders`
      WHERE `status` = 'completed'
        AND DATE(created_at) = ?
    ");
    $stmt->bind_param('s', $selectedDate);
    $stmt->execute();
    $today = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // — Last 7 days sales for chart
    $stmt = $conn->prepare("
      SELECT
        DATE(created_at) AS date,
        COALESCE(SUM(price_real),0) AS sum
      FROM `orders`
      WHERE `status` = 'completed'
        AND DATE(created_at)
            BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()
      GROUP BY DATE(created_at)
      ORDER BY DATE(created_at) ASC
    ");
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Build chart labels/data
    $chartLabels = [];
    $chartData   = [];
    $todayDT = new DateTime();
    for ($i = 7; $i >= 0; $i--) {
        $dt = (clone $todayDT)->modify("-{$i} days");
        $chartLabels[] = $i === 0
                      ? 'Today'
                      : ($i === 1 ? 'Yesterday' : "{$i}d ago");
        $chartData[] = 0.0;
    }
    foreach ($rows as $r) {
        $diff = (new DateTime($r['date']))->diff($todayDT)->days;
        if ($diff <= 7) {
            $chartData[7 - $diff] = (float)$r['sum'];
        }
    }

    // — Days with sales for calendar (last 30 days)
    $stmt = $conn->prepare("
      SELECT
        DATE(created_at) AS date,
        COUNT(*)         AS order_count
      FROM `orders`
      WHERE `status` = 'completed'
        AND DATE(created_at)
            BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
      GROUP BY DATE(created_at)
    ");
    $stmt->execute();
    $salesDaysRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $salesDays = [];
    foreach ($salesDaysRows as $r) {
        $salesDays[$r['date']] = $r['order_count'];
    }

} catch (Exception $e) {
    error_log("Dashboard DB error: " . $e->getMessage());
    die("An error occurred while fetching data.");
}

$pageTitle = 'Dashboard';
include __DIR__ . '/templates/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="container-fluid">
  <div class="row gx-3 gy-3 mt-3">
    <!-- Users -->
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card text-white bg-primary h-100">
        <div class="card-body">
          <small>Users</small>
          <h2 class="mb-0"><?= number_format($userCnt) ?></h2>
        </div>
        <div class="card-footer bg-transparent">
          <a href="registration.php" class="text-white small">Manage Users →</a>
        </div>
      </div>
    </div>
    <!-- Categories -->
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card text-white bg-success h-100">
        <div class="card-body">
          <small>Categories</small>
          <h2 class="mb-0"><?= number_format($catCnt) ?></h2>
        </div>
        <div class="card-footer bg-transparent">
          <a href="category.php" class="text-white small">Manage Categories →</a>
        </div>
      </div>
    </div>
    <!-- Products -->
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card text-white bg-warning h-100">
        <div class="card-body">
          <small>Products</small>
          <h2 class="mb-0"><?= number_format($prodCnt) ?></h2>
        </div>
        <div class="card-footer bg-transparent">
          <a href="product.php" class="text-white small">Manage Products →</a>
        </div>
      </div>
    </div>
    <!-- Orders -->
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card text-white bg-danger h-100">
        <div class="card-body">
          <small>Completed Orders</small>
          <h2 class="mb-0"><?= number_format($orderCnt) ?></h2>
        </div>
        <div class="card-footer bg-transparent">
          <a href="orderlist.php" class="text-white small">View Orders →</a>
        </div>
      </div>
    </div>
    <!-- Revenue -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100">
        <div class="card-body">
          <small class="text-secondary">Total Revenue</small>
          <h3>Rs <?= number_format($revenue,2) ?></h3>
        </div>
      </div>
    </div>
    <!-- Sales on Selected Date -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100">
        <div class="card-body">
          <small class="text-secondary">Sales on <?= htmlspecialchars($selectedDate) ?></small>
          <h4><?= number_format($today['cnt']) ?> orders</h4>
          <h5>Rs <?= number_format($today['sum'],2) ?></h5>
        </div>
      </div>
    </div>
    <!-- Live Clock -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100">
        <div class="card-body text-center">
          <small class="text-secondary">Current Time</small>
          <div id="live-clock" class="h2 fw-bold">--:--:--</div>
        </div>
      </div>
    </div>
    <!-- Calendar -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100 bg-gradient p-2">
        <small class="text-white ps-2 pt-1">Select Date</small>
        <div id="dashboard-calendar" class="bg-white m-2 p-2 rounded"></div>
      </div>
    </div>
    <!-- Sales Trend Chart -->
    <div class="col-12 col-lg-8">
      <div class="card h-100">
        <div class="card-body">
          <small class="text-secondary">Sales Trend (Last 8 Days)</small>
          <canvas id="salesChart" style="height:250px;"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Live Clock
function updateClock(){
  document.getElementById('live-clock')
          .textContent = new Date()
            .toLocaleTimeString('en-GB',{hour12:false});
}
setInterval(updateClock,1000);
updateClock();

// Calendar
const salesDays = <?= json_encode($salesDays) ?>;
flatpickr("#dashboard-calendar", {
  inline: true,
  defaultDate: '<?= $selectedDate ?>',
  disableMobile: true,
  onChange: dates => {
    location.href = 'dashboard.php?date=' + dates[0].toISOString().slice(0,10);
  },
  onDayCreate: (dObj, dStr, fp, dayElem) => {
    const ds = dayElem.dateObj.toISOString().split('T')[0];
    if (salesDays[ds]) {
      dayElem.classList.add('has-sales');
      if (salesDays[ds] > 5) dayElem.classList.add('has-sales-high');
    }
  }
});

// Sales Chart
new Chart(document.getElementById('salesChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($chartLabels) ?>,
    datasets: [{
      data: <?= json_encode($chartData) ?>,
      fill: true,
      tension: 0.4,
      borderColor: '#4bc0c0',
      backgroundColor: 'rgba(75,192,192,0.2)',
      pointRadius: 4
    }]
  },
  options: {
    scales: {
      y: { beginAtZero: true, ticks: { callback: v => 'Rs ' + v } }
    },
    plugins: { legend: { display: false } }
  }
});
</script>
