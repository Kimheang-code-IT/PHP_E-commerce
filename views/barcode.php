<?php
// barcode.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Auth and order ID validation
if (empty($_SESSION['user_id']) || !isset($_GET['id'])) {
  header('Location: login.php');
  exit;
}

$orderId = (int)$_GET['id'];

// Fetch order items with barcodes
try {
  $stmt = $conn->prepare("
    SELECT p.barcode, p.name, oi.quantity
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
  ");
  $stmt->bind_param('i', $orderId);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
} catch (Exception $e) {
  die(json_encode(['success' => false, 'message' => 'Error fetching order items: ' . $e->getMessage()]));
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Order #<?= $orderId ?> Barcodes</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .barcode-input {
      width: 100%;
      padding: 8px;
      margin: 10px 0;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .result {
      margin: 10px 0;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      display: none;
    }
    .result.success {
      background: #e6ffed;
      border-color: #28a745;
    }
    .result.error {
      background: #ffe6e6;
      border-color: #dc3545;
    }
    .barcode-item {
      padding: 5px;
      margin: 5px 0;
      border-bottom: 1px solid #eee;
    }
    .barcode-item.highlight {
      background: #fff3cd;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <!-- Barcode Modal -->
  <div class="modal fade" id="barcodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Order #<?= $orderId ?> Barcodes</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="text" id="barcodeInput" class="barcode-input" placeholder="Scan or enter barcode" autofocus>
          <div id="scanResult" class="result"></div>
          <div id="barcodeContainer" class="d-flex flex-wrap">
            <?php if (empty($rows)): ?>
              <p>No items found for this order.</p>
            <?php else: ?>
              <?php foreach ($rows as $r): ?>
                <div class="barcode-item w-100" data-barcode="<?= htmlspecialchars($r['barcode']) ?>">
                  <strong><?= htmlspecialchars($r['barcode']) ?></strong>
                  – <?= htmlspecialchars($r['name']) ?>
                  (Qty: <?= (int)$r['quantity'] ?>)
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="modal-footer">          
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS and Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Order items from PHP
    const items = <?= json_encode($rows, JSON_HEX_APOS) ?>;
    
    // DOM elements
    const barcodeInput = document.getElementById('barcodeInput');
    const scanResult = document.getElementById('scanResult');
    const barcodeContainer = document.getElementById('barcodeContainer');
    
    // Show modal on page load
    window.addEventListener('DOMContentLoaded', () => {
      const modal = new bootstrap.Modal(document.getElementById('barcodeModal'), {
        backdrop: 'static',
        keyboard: false
      });
      modal.show();
      barcodeInput.focus();
      
      // Redirect to orderlist.php when modal is closed
      document.getElementById('barcodeModal').addEventListener('hidden.bs.modal', () => {
        window.location.href = 'orderlist.php';
      });
    });
    
    // Handle barcode input
    barcodeInput.addEventListener('input', (e) => {
      const barcode = e.target.value.trim();
      
      // Wait for Enter key or scanner newline
      if (e.inputType === 'insertText' && barcode.endsWith('\n')) {
        e.target.value = ''; // Clear input
        processBarcode(barcode);
      }
    });
    
    // Process scanned barcode
    function processBarcode(barcode) {
      // Reset previous highlights
      document.querySelectorAll('.barcode-item').forEach(item => {
        item.classList.remove('highlight');
      });
      
      // Find matching item
      const item = items.find(i => i.barcode === barcode);
      
      // Update result display
      if (item) {
        scanResult.style.display = 'block';
        scanResult.className = 'result success';
        scanResult.textContent = `Found: ${item.name} (Qty: ${item.quantity})`;
        
        // Highlight matching item
        const itemElement = barcodeContainer.querySelector(`.barcode-item[data-barcode="${barcode}"]`);
        if (itemElement) {
          itemElement.classList.add('highlight');
          itemElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      } else {
        scanResult.style.display = 'block';
        scanResult.className = 'result error';
        scanResult.textContent = `Barcode ${barcode} not found in this order`;
      }
      
      // Auto-hide result after 3 seconds
      setTimeout(() => {
        scanResult.style.display = 'none';
      }, 3000);
      
      // Refocus input
      barcodeInput.focus();
    }
    
    // Print function
    function printBarcodes() {
      const printContent = barcodeContainer.innerHTML;
      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
          <head>
            <title>Order #${<?= $orderId ?>} Barcodes</title>
            <style>
              body { font-family: Arial, sans-serif; padding: 20px; }
              .barcode-item { padding: 5px; margin: 5px 0; border-bottom: 1px solid #eee; }
            </style>
          </head>
          <body>
            <h2>Order #${<?= $orderId ?>} Barcodes</h2>
            ${printContent}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    }
  </script>
</body>
</html>