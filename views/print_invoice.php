<?php
// views/print_invoice.php
session_start();
require_once __DIR__ . '/../config/db.php';

// 1) Auth guard
if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// 2) Validate order ID
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$orderId) {
    echo 'Invalid order ID';
    exit;
}

// 3) Fetch order header + customer + rate info
$sql = "
  SELECT
    o.id,
    o.created_at,
    o.total_usd,
    o.change_rate,
    o.price_real,
    u.username AS cust_name,
    u.email    AS cust_email
  FROM orders o
  JOIN users  u ON u.id = o.user_id
  WHERE o.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$order) {
    echo 'Order not found';
    exit;
}

// 4) Fetch line‐items
$sql = "
  SELECT
    p.name       AS product_name,
    oi.quantity,
    oi.unit_price
  FROM order_items oi
  JOIN products     p ON p.id = oi.product_id
  WHERE oi.order_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 5) Fetch payment info
$sql = "
  SELECT method, amount_usd, paid_at
  FROM payments
  WHERE order_id = ?
  ORDER BY id DESC
  LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

// 6) Calculate breakdown
$subtotal = 0;
foreach ($items as $it) {
    $subtotal += $it['quantity'] * $it['unit_price'];
}
$tax       = $subtotal * 0.10;
$discount  = 0.00; // adjust as needed
$totalUsd  = (float)$order['total_usd'];
$totalKhr  = (float)$order['price_real'];

// 7) QR code URL
$websiteUrl = defined('BASE_URL')
    ? rtrim(BASE_URL, '/')
    : 'http://' . $_SERVER['HTTP_HOST'];

// 7b) Build a payment URL (you can point to your real payment endpoint)
$paymentUrl = (defined('BASE_URL')
    ? rtrim(BASE_URL, '/')
    : 'http://' . $_SERVER['HTTP_HOST'])
    . '/pay.php?order=' . $orderId;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= htmlspecialchars($order['id']) ?></title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <style>
        @page {
            size: A5;
            margin: 8mm;
        }

        body {
            font-size: 12px;
            font-family: Helvetica, Arial, sans-serif;
            color: #333;
        }

        .invoice-box {
            width: 100%;
            margin: 0 auto;
            padding: 10px;
            border: 1px solid #eee;
            box-shadow: none;
        }

        /* Make product‐row text really small */
        .invoice-box table.table th,
        .invoice-box table.table td {
            font-size: 10px;
            padding: 4px;
        }

        /* Totals lines just a touch bigger (still small) */
        .totals-table th,
        .totals-table td {
            font-size: 11px;
            padding: 4px;
        }

        /* Footer tiny */
        footer {
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            text-align: center;
            font-size: 10px;
        }

        .no-print {
            margin-top: 15px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

        }

        .text-end1 {
            border: none;
            display: flex;
            align-items: end;
            flex-direction: row;
        }

        /* Totals table: no outer borders */
        .totals-table {
            border: none !important;
        }

        .totals-table th,
        .totals-table td {
            border: none !important;
            padding: 4px;
        }

        /* First three rows (subtotal, tax, discount) remain border-less */
        .totals-table tr:nth-child(1) th,
        .totals-table tr:nth-child(1) td,
        .totals-table tr:nth-child(2) th,
        .totals-table tr:nth-child(2) td,
        .totals-table tr:nth-child(3) th,
        .totals-table tr:nth-child(3) td {
            /* already border:none */
        }

        /* Dashed top border before the USD total row */
        .totals-table tr:nth-child(4) th,
        .totals-table tr:nth-child(4) td {
            border-top: 1px dashed #333 !important;
        }

        /* And similarly before the KHR total row (if you want the same) */
        .totals-table tr:nth-child(5) th,
        .totals-table tr:nth-child(5) td {
            border-top: 1px dashed #333 !important;
        }

        /* QR row: two small codes inline */
        .qr-row img {
            width: 50px;
            height: 50px;
        }
    </style>
</head>

<body>

    <div class="invoice-box">
        <!-- views/print_invoice.php -->
        <div
            class="d-flex justify-content-between align-items-center mb-3"
            style="border-bottom:2px solid #333; padding-bottom:8px;"

            <!-- Logo + Shop Name -->
            <div class="d-flex align-items-center">
                <!-- replace with the actual path to your logo -->
                <img
                    src="<?= BASE_URL ?>../uploads/image.png"
                    alt="My Shop Logo"
                    style="height:48px; margin-right:12px;">
                <div class="title" style="font-size:18px; color:#333;">
                    <strong>My Shop Name</strong>
                </div>
            </div>

            <!-- Invoice meta -->
            <div class="text-end" style="font-size:12px; color:#555;">
                <div style="font-weight:600; font-size:16px; margin-bottom:4px;">INVOICE</div>
                <div>#<?= htmlspecialchars($order['id']) ?></div>
                <div><?= date('Y-m-d', strtotime($order['created_at'])) ?></div>
            </div>
        </div>


        <div class="row mb-3">
            <div class="col-6">
                <strong>Billed To:</strong><br>
                <?= htmlspecialchars($order['cust_name']) ?><br>
                <?= htmlspecialchars($order['cust_email']) ?>
            </div>
            <div class="col-6 text-end">
                <strong>Payment:</strong><br>
                Method: <?= htmlspecialchars($payment['method'] ?? 'N/A') ?><br>
                Paid: <?= $payment['paid_at']
                            ? date('Y-m-d H:i', strtotime($payment['paid_at']))
                            : '—' ?><br>
                Amount: $<?= number_format($payment['amount_usd'] ?? 0, 2) ?>
            </div>
        </div>

        <table class="table table-bordered mb-2" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th style="width:5%; align-items: center; ">#</th>
                    <th style="width:55%; align-items: center;">Product</th>
                    <th style="width:10%; align-items: center;" class="text-end">Qty</th>
                    <th style="width:15%; align-items: center;" class="text-end">Unit Price</th>
                    <th style="width:15%; align-items: center;" class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1;
                foreach ($items as $it): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($it['product_name']) ?></td>
                        <td class="text-end"><?= (int)$it['quantity'] ?></td>
                        <td class="text-end">$<?= number_format($it['unit_price'], 2) ?></td>
                        <td class="text-end">
                            $<?= number_format($it['quantity'] * $it['unit_price'], 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="table totals-table mb-2">
            <tr>
                <th class="text-end">Subtotal (USD):</th>
                <td class="text-end">$<?= number_format($subtotal, 2) ?></td>
            </tr>
            <tr>
                <th class="text-end">Tax (10%):</th>
                <td class="text-end">$<?= number_format($tax, 2) ?></td>
            </tr>
            <tr>
                <th class="text-end">Discount:</th>
                <td class="text-end">- $<?= number_format($discount, 2) ?></td>
            </tr>
            <tr>
                <th class="text-end"><strong>Total (USD):</strong></th>
                <td class="text-end"><strong>$<?= number_format($totalUsd, 2) ?></strong></td>
            </tr>
            <tr>
                <th class="text-end"><strong>Total (KHR):</strong></th>
                <td class="text-end"><strong>៛<?= number_format($totalKhr, 0) ?></strong></td>
            </tr>
        </table>


        <div class="d-flex justify-content-center align-items-start mb-3 qr-row">
            <div class="text-center me-3">
                <!-- Caption first -->
                <div style="font-size:10px; margin-bottom:4px; font-weight: bold;">Visit our Website</div>
                <!-- Then the QR image -->
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode($websiteUrl) ?>&size=50x50"
                    alt="Website QR">
            </div>
            <div class="text-center">
                <div style="font-size: 10px; margin-bottom: 4px; font-weight: bold;">Pay Invoice</div>

                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode($paymentUrl) ?>&size=50x50"
                    alt="Payment QR">
            </div>
        </div>



        <div class="text-center no-print">
            <button onclick="window.print()" class="btn btn-sm btn-primary">
                <i class="bi bi-printer"></i> Print Invoice
            </button>
            <a href="orderlist.php" class="btn btn-sm btn-secondary">← Back to Orders</a>
        </div>

        <footer>
            <div>Email: support@myshop.com</div>
            <div>Phone: +1 (555) 123-4567</div>
            <div>Address: 123 Market St, Cityville</div>
        </footer>
    </div>

    <!-- Bootstrap icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
        rel="stylesheet" />
</body>

</html>