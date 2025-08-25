<?php
// views/product.php

session_start();
require_once __DIR__ . '/../config/db.php';

// ——————————————————————————————————————————
// 1) Image‐upload helper
// ——————————————————————————————————————————
function handleImageUpload(string $fieldName): ?string
{
    if (
        empty($_FILES[$fieldName]) ||
        $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK
    ) {
        return null;
    }
    $tmp  = $_FILES[$fieldName]['tmp_name'];
    $orig = basename($_FILES[$fieldName]['name']);
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($ext, $allowed, true)) {
        return null;
    }
    $newName = uniqid('prod_', true) . ".$ext";
    $destDir = __DIR__ . '/../uploads/products/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    $destPath = $destDir . $newName;
    if (!move_uploaded_file($tmp, $destPath)) {
        return null;
    }
    return 'uploads/products/' . $newName;
}

// ——————————————————————————————————————————
// 2) Auth guard
// ——————————————————————————————————————————
$self = basename($_SERVER['PHP_SELF']);
if ($self !== 'login.php' && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// ——————————————————————————————————————————
// 3) Flash messages
// ——————————————————————————————————————————
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ——————————————————————————————————————————
// 4) Handle POST ⇒ PRG
// ——————————————————————————————————————————
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ADD PRODUCT
    if (isset($_POST['add'])) {
    $cat   = (int)($_POST['category_id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    $pu    = trim($_POST['purchase_usd'] ?? '');
    $stock = trim($_POST['stock_quantity'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $avail = isset($_POST['is_available']) ? 1 : 0;
    $img   = handleImageUpload('image') ?: '';

    // 1) Basic validation
    if ($cat === 0 || !$name || !$pu) {
        $_SESSION['flash_error'] = 'Category, name & price are required.';
    } elseif (!is_numeric($pu)) {
        $_SESSION['flash_error'] = 'Price must be numeric.';
    } else {
        // 2) Insert product
        $stmt = $conn->prepare("
            INSERT INTO products
              (category_id, image, name, description, price_usd, stock_quantity, is_active)
            VALUES (?,?,?,?,?,?,?)
        ");
        $stmt->bind_param('isssdii', $cat, $img, $name, $desc, $pu, $stock, $avail);

        if ($stmt->execute()) {
            // 3) Capture new product ID
            $newProductId = $stmt->insert_id;
            $stmt->close();

            // 4) Insert any meta fields
            if (!empty($_POST['meta']) && is_array($_POST['meta'])) {
                $mStmt = $conn->prepare("
                    INSERT INTO product_meta
                      (product_id, meta_key, meta_value)
                    VALUES (?,?,?)
                ");
                foreach ($_POST['meta'] as $key => $value) {
                    $cleanKey   = trim($key);
                    $cleanValue = trim($value);
                    if ($cleanKey !== '' && $cleanValue !== '') {
                        $mStmt->bind_param('iss', $newProductId, $cleanKey, $cleanValue);
                        $mStmt->execute();
                    }
                }
                $mStmt->close();
            }

            $_SESSION['flash_success'] = 'Product added.';
        } else {
            $_SESSION['flash_error'] = 'Add failed: ' . $stmt->error;
            $stmt->close();
        }
    }

    // 5) Redirect to avoid double‐submit
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

    // UPDATE PRODUCT
    elseif (isset($_POST['update'])) {
    $id     = (int)($_POST['id'] ?? 0);
    $cat    = (int)($_POST['category_id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');
    $pu     = trim($_POST['purchase_usd'] ?? '');
    $stock  = trim($_POST['stock_quantity'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $avail  = isset($_POST['is_available']) ? 1 : 0;
    $newImg = handleImageUpload('image');
    $img    = $newImg ?: trim($_POST['current_image'] ?? '');

    // Validation
    if ($id === 0 || $cat === 0 || !$name || !$pu) {
        $_SESSION['flash_error'] = 'Category, name & price are required.';
    } elseif (!is_numeric($pu)) {
        $_SESSION['flash_error'] = 'Price must be numeric.';
    } else {
        // 1) Update main product row
        $stmt = $conn->prepare("
            UPDATE products
               SET category_id    = ?,
                   image          = ?,
                   name           = ?,
                   description    = ?,
                   price_usd      = ?,
                   stock_quantity = ?,
                   is_active      = ?
             WHERE id = ?
        ");
        $stmt->bind_param('isssdiii', $cat, $img, $name, $desc, $pu, $stock, $avail, $id);

        if ($stmt->execute()) {
            $stmt->close();

            // 2) Remove all old meta for this product
            $del = $conn->prepare("
                DELETE FROM product_meta
                 WHERE product_id = ?
            ");
            $del->bind_param('i', $id);
            $del->execute();
            $del->close();

            // 3) Re-insert any submitted meta fields
            if (!empty($_POST['meta']) && is_array($_POST['meta'])) {
                $mStmt = $conn->prepare("
                    INSERT INTO product_meta
                      (product_id, meta_key, meta_value)
                    VALUES (?,?,?)
                ");
                foreach ($_POST['meta'] as $key => $value) {
                    $cleanKey   = trim($key);
                    $cleanValue = trim($value);
                    if ($cleanKey !== '' && $cleanValue !== '') {
                        $mStmt->bind_param('iss', $id, $cleanKey, $cleanValue);
                        $mStmt->execute();
                    }
                }
                $mStmt->close();
            }

            $_SESSION['flash_success'] = 'Product updated.';
        } else {
            $_SESSION['flash_error'] = 'Update failed: ' . $stmt->error;
            $stmt->close();
        }
    }

    // Redirect to avoid re-post
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

    // DELETE PRODUCT by BARCODE
    // ————————————————————————————————
    if (isset($_POST['delete_barcode'])) {
        $barcode = trim($_POST['delete_barcode']);

        // 1) Lookup the product’s internal ID
        $stmt = $conn->prepare("SELECT id FROM products WHERE Barcode = ? LIMIT 1");
        $stmt->bind_param('s', $barcode);
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $row = $res->fetch_assoc()) {
            $_SESSION['flash_error'] = "No product found with barcode {$barcode}.";
            $stmt->close();
        } else {
            $prodId = (int)$row['id'];
            $stmt->close();

            // 2) Delete any child rows first (to satisfy FK constraints)
            //    e.g. order_items, stock_additions, etc.
            $childTables = [
                'order_items'     => 'product_id',
                'stock_additions' => 'product_id',
                // add more as needed
            ];
            foreach ($childTables as $table => $fk) {
                $del = $conn->prepare("DELETE FROM {$table} WHERE {$fk} = ?");
                $del->bind_param('i', $prodId);
                $del->execute();
                $del->close();
            }

            // 3) Now delete the product itself
            $del = $conn->prepare("DELETE FROM products WHERE id = ?");
            $del->bind_param('i', $prodId);
            if ($del->execute()) {
                $_SESSION['flash_success'] = "Product {$barcode} deleted.";
            } else {
                $_SESSION['flash_error'] = "Delete failed: " . $del->error;
            }
            $del->close();
        }

        // Redirect to avoid form‐resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }


    // ADD STOCK
    elseif (isset($_POST['add_stock'])) {
        $pid = (int)$_POST['product_id'];
        $qty = (int)$_POST['stock_qty'];
        if ($qty > 0) {
            $stmt = $conn->prepare("
                INSERT INTO stock_additions (product_id, qty)
                VALUES (?,?)
            ");
            $stmt->bind_param('ii', $pid, $qty);
            $_SESSION['flash_success'] = $stmt->execute()
                ? "Added {$qty} to stock."
                : "Stock update failed: " . $stmt->error;
            $stmt->close();
        } else {
            $_SESSION['flash_error'] = "Please enter a positive quantity.";
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ——————————————————————————————————————————
// 5) Fetch lookup data + products + optional edit row
// ——————————————————————————————————————————
$categories = $conn
    ->query("SELECT id,name FROM categories ORDER BY name")
    ->fetch_all(MYSQLI_ASSOC);

$products = $conn
    ->query("
      SELECT
        ID,
        Barcode,
        Category,
        Image,
        `Name`,
        Description,
        `Price $`    AS purchase_usd,
        `Price ៛`   AS purchase_riel,
        `Stock In`  AS stock_in,
        `Stock Add` AS stock_add,
        `Total Stock` AS total_stock,
        Avail       AS avail
      FROM vw_product_overview
      ORDER BY ID DESC
    ")
    ->fetch_all(MYSQLI_ASSOC);

$editProduct = null;
if (!empty($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("
        SELECT id,category_id,image,name,description,price_usd,stock_quantity,is_active
          FROM products
         WHERE id = ? LIMIT 1
    ");
    $stmt->bind_param('i', $eid);
    $stmt->execute();
    $editProduct = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ——————————————————————————————————————————
// 6) Render page
// ——————————————————————————————————————————
$pageTitle = 'Products';
include __DIR__ . '/templates/header.php';
?>
<style>
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
    
</style>

<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <input id="searchBox" class="form-control form-control-sm w-50" placeholder="Search…">
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg"></i> Add Product
        </button>
    </div>

    <div class="table-responsive" style="max-height:440px; overflow-y:auto;">
        <table class="table table-bordered table-hover table-sm" style="font-size:0.7rem;">
            <thead class="sticky-top custom-header">
                <tr>
                    <th>No</th>
                    <th>Barcode</th>
                    <th>Category</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price&nbsp;$</th>
                    <th>Price&nbsp;៛</th>
                    <th>Stock&nbsp;In</th>
                    <th>Stock&nbsp;Add</th>
                    <th>Total&nbsp;Stock</th>
                    <th>Avail</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="13" class="text-center">No products found.</td>
                    </tr>
                <?php else: ?>
                    <?php $rowNum = count($products); ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= $rowNum-- ?></td>
                            <td><?= htmlspecialchars($p['Barcode']) ?></td>
                            <td><?= htmlspecialchars($p['Category']) ?></td>
                            <td>
                                <?php if ($p['Image']): ?>
                                    <img src="<?= BASE_URL . '/' . $p['Image'] ?>" style="height:30px;">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['Name']) ?></td>
                            <td>
                                <span class="desc-cell" data-bs-toggle="tooltip" title="<?= htmlspecialchars($p['Description']) ?>">
                                    <?= htmlspecialchars($p['Description']) ?>
                                </span>
                            </td>
                            <td class="text-end"><?= number_format($p['purchase_usd'], 2) ?></td>
                            <td class="text-end"><?= number_format($p['purchase_riel'], 0) ?></td>
                            <td class="text-end"><?= (int)$p['stock_in'] ?></td>
                            <td class="text-end"><?= (int)$p['stock_add'] ?></td>
                            <td class="text-end"><?= (int)$p['total_stock'] ?></td>
                            <td><?= $p['avail'] ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-success btn-stock"
                                    data-id="<?= $p['ID'] ?>"
                                    data-name="<?= htmlspecialchars($p['Name']) ?>">
                                    <i class="bi bi-plus-circle"></i>
                                </button>
                                <a class="btn btn-sm btn-primary" href="?edit_id=<?= $p['ID'] ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button class="btn btn-sm btn-danger btn-delete">
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

<!-- Stock Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Add Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="stockProductId">
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <input type="text" id="stockProductName" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity to add</label>
                        <input type="number" name="stock_qty" id="stockQty" min="1" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="add_stock" type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data">
        <div class="modal-header border-bottom-0">
          <h5 class="modal-title">Add Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <!-- CORE PRODUCT FIELDS -->
          <div class="row g-3">
            <!-- Category -->
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select name="category_id" class="form-select" required>
                <option value="">— Select —</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Name -->
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input name="name" class="form-control" required>
            </div>

            <!-- Image upload with preview -->
            <div class="col-md-6">
              <label class="form-label">Image</label>
              <div id="prodUploadBox" class="border rounded position-relative p-3 text-center"
                   style="cursor:pointer;min-height:150px;">
                <div id="prodUploadPrompt">
                  <i class="bi bi-cloud-arrow-up fs-1 text-secondary"></i>
                  <p class="text-secondary small">Click or drop image here</p>
                </div>
                <img id="prodUploadPreview" class="position-absolute top-0 start-0 w-100 h-100 d-none"
                     style="object-fit:contain;" alt="Preview">
                <input type="file" name="image" accept="image/*"
                       class="position-absolute top-0 start-0 w-100 h-100 opacity-0">
              </div>
            </div>

            <!-- Price USD -->
            <div class="col-md-3">
              <label class="form-label">Price (USD)</label>
              <input name="purchase_usd" class="form-control" placeholder="e.g. 9.99" required>
            </div>

            <!-- Stock Quantity -->
            <div class="col-md-3">
              <label class="form-label">Stock Qty</label>
              <input name="stock_quantity" class="form-control" type="number" min="0">
            </div>

            <!-- Active checkbox -->
            <div class="col-md-6 d-flex align-items-center">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="is_available" id="availAdd" checked>
                <label class="form-check-label" for="availAdd">Active</label>
              </div>
            </div>

            <!-- Description -->
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
          </div>

          <!-- COLLAPSIBLE ADDITIONAL META -->
          <div class="accordion mt-4" id="metaAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="metaHeading">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#metaCollapse"
                        aria-expanded="false" aria-controls="metaCollapse">
                  Additional Info (Weight, Dimensions, etc.)
                </button>
              </h2>
              <div id="metaCollapse" class="accordion-collapse collapse"
                   aria-labelledby="metaHeading" data-bs-parent="#metaAccordion">
                <div class="accordion-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Weight</label>
                      <input name="meta[Weight]" class="form-control" placeholder="e.g. 0.79 kg">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Dimensions</label>
                      <input name="meta[Dimensions]" class="form-control" placeholder="e.g. 110×33×100 cm">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Materials</label>
                      <input name="meta[Materials]" class="form-control" placeholder="e.g. 60% cotton">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Color</label>
                      <input name="meta[Color]" class="form-control" placeholder="e.g. Black, Blue…">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Size</label>
                      <input name="meta[Size]" class="form-control" placeholder="e.g. S, M, L">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- FOOTER -->
        <div class="modal-footer border-top-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button name="add" type="submit" class="btn btn-primary">Save Product</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- Edit Product Modal -->
<?php
// BEFORE the modal, fetch existing meta into an associative array:
$editMeta = [];
if ($editProduct) {
    $mstmt = $conn->prepare("
      SELECT meta_key, meta_value
        FROM product_meta
       WHERE product_id = ?
    ");
    $mstmt->bind_param('i', $editProduct['id']);
    $mstmt->execute();
    foreach ($mstmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $editMeta[$row['meta_key']] = $row['meta_value'];
    }
    $mstmt->close();
}
?>
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <?php if ($editProduct): ?>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
        <input type="hidden" name="current_image" value="<?= htmlspecialchars($editProduct['image']) ?>">

        <div class="modal-header border-bottom-0">
          <h5 class="modal-title">Edit Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <!-- Category -->
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select name="category_id" class="form-select" required>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= $c['id'] ?>"
                    <?= $editProduct['category_id']===$c['id']?'selected':''?>>
                    <?= htmlspecialchars($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Name -->
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input name="name" class="form-control"
                     value="<?= htmlspecialchars($editProduct['name']) ?>" required>
            </div>

            <!-- Image upload -->
            <div class="col-md-6">
              <label class="form-label">Image</label>
              <div id="editUploadBox" class="border rounded position-relative p-3 text-center"
                   style="cursor:pointer;min-height:150px;">
                <div id="editUploadPrompt" class="<?= $editProduct['image']?'d-none':'' ?>">
                  <i class="bi bi-cloud-arrow-up fs-1 text-secondary"></i>
                  <p class="text-secondary small">Click or drop image here</p>
                </div>
                <img id="editUploadPreview"
                     class="position-absolute top-0 start-0 w-100 h-100 <?= $editProduct['image']?'':'d-none' ?>"
                     style="object-fit:contain;"
                     src="<?= $editProduct['image']? BASE_URL.'/'.$editProduct['image'] : '' ?>"
                     alt="Preview">
                <input type="file" name="image" accept="image/*"
                       class="position-absolute top-0 start-0 w-100 h-100 opacity-0">
              </div>
            </div>

            <!-- Price USD -->
            <div class="col-md-3">
              <label class="form-label">Price (USD)</label>
              <input name="purchase_usd" class="form-control"
                     value="<?= htmlspecialchars($editProduct['price_usd']) ?>" required>
            </div>

            <!-- Stock Quantity -->
            <div class="col-md-3">
              <label class="form-label">Stock Qty</label>
              <input name="stock_quantity" type="number" min="0" class="form-control"
                     value="<?= htmlspecialchars($editProduct['stock_quantity']) ?>">
            </div>

            <!-- Active -->
            <div class="col-md-6 d-flex align-items-center">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="is_available" id="availEdit"
                       <?= $editProduct['is_active']?'checked':''?>>
                <label class="form-check-label" for="availEdit">Active</label>
              </div>
            </div>

            <!-- Description -->
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editProduct['description']) ?></textarea>
            </div>
          </div>

          <!-- Accordion for meta -->
          <div class="accordion mt-4" id="editMetaAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingEditMeta">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseEditMeta"
                        aria-expanded="false" aria-controls="collapseEditMeta">
                  Additional Info
                </button>
              </h2>
              <div id="collapseEditMeta" class="accordion-collapse collapse"
                   aria-labelledby="headingEditMeta" data-bs-parent="#editMetaAccordion">
                <div class="accordion-body">
                  <div class="row g-3">
                    <?php 
                    $fields = ['Weight','Dimensions','Materials','Color','Size'];
                    foreach ($fields as $key): 
                      $val = $editMeta[$key] ?? '';
                    ?>
                      <div class="col-md-6">
                        <label class="form-label"><?= $key ?></label>
                        <input name="meta[<?= $key ?>]" class="form-control"
                               value="<?= htmlspecialchars($val) ?>">
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div><!-- /.modal-body -->

        <div class="modal-footer border-top-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button name="update" type="submit" class="btn btn-primary">Update Product</button>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>



<!-- Bootstrap & SweetAlert2 + client‐side scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // 1) Live‐search by Barcode (col 1), Category (col 2) or Name (col 4)
    document.getElementById('searchBox').addEventListener('input', e => {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(tr => {
            const barcode = tr.cells[1].textContent.toLowerCase();
            const category = tr.cells[2].textContent.toLowerCase();
            const name = tr.cells[4].textContent.toLowerCase();
            tr.style.display = (barcode.includes(q) || category.includes(q) || name.includes(q)) ?
                '' : 'none';
        });
    });

    // 2) Delete confirmation
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', () => {
            const tr = btn.closest('tr');
            const barcode = tr.cells[1].textContent.trim();
            Swal.fire({
                title: `Delete product ${barcode}?`,
                text: 'This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete'
            }).then(res => {
                if (res.isConfirmed) {
                    const fd = new FormData();
                    fd.append('delete_barcode', barcode);
                    fetch(location.href, {
                            method: 'POST',
                            body: fd
                        })
                        .then(() => location.reload());
                }
            });
        });
    });


    // 3) Stock modal
    document.querySelectorAll('.btn-stock').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('stockProductId').value = btn.dataset.id;
            document.getElementById('stockProductName').value = btn.dataset.name;
            document.getElementById('stockQty').value = '';
            new bootstrap.Modal(document.getElementById('stockModal')).show();
        });
    });

    // 4) Auto‐open edit modal
    <?php if ($editProduct): ?>
        window.addEventListener('DOMContentLoaded', () => {
            new bootstrap.Modal(document.getElementById('editModal')).show();
        });
    <?php endif; ?>

    // 5) Flash alerts
    <?php if ($flash_success): ?>
        Swal.fire({
            icon: 'success',
            text: <?= json_encode($flash_success) ?>
        });
    <?php elseif ($flash_error): ?>
        Swal.fire({
            icon: 'error',
            text: <?= json_encode($flash_error) ?>
        });
    <?php endif; ?>
</script>
<script>
    /// grab elements
const box = document.getElementById('prodUploadBox');
const inp = document.getElementById('prodUploadInput');
const preview = document.getElementById('prodUploadPreview');
const prompt = document.getElementById('prodUploadPrompt');

// click on box (but not on the input itself)
box.addEventListener('click', () => inp.click());

// stop the parent handler when you really click the input
inp.addEventListener('click', e => e.stopPropagation());

// drag & drop
box.addEventListener('dragover', e => {
  e.preventDefault();
  box.classList.add('border-primary');
});
box.addEventListener('dragleave', () => {
  box.classList.remove('border-primary');
});
box.addEventListener('drop', e => {
  e.preventDefault();
  box.classList.remove('border-primary');
  inp.files = e.dataTransfer.files;
  showPreview();
});

// when file is picked
inp.addEventListener('change', showPreview);

function showPreview() {
  const f = inp.files[0];
  if (!f) return;
  const reader = new FileReader();
  reader.onload = e => {
    preview.src = e.target.result;
    preview.classList.remove('d-none');
    prompt.classList.add('d-none');
  };
  reader.readAsDataURL(f);
}

// edit‐modal upload
const eBox      = document.getElementById('editUploadBox');
const eInput    = document.getElementById('editUploadInput');
const ePreview  = document.getElementById('editUploadPreview');
const ePrompt   = document.getElementById('editUploadPrompt');

eBox.addEventListener('click', () => eInput.click());
eInput.addEventListener('click', e => e.stopPropagation());

eBox.addEventListener('dragover', e => {
  e.preventDefault();
  eBox.classList.add('border-primary');
});
eBox.addEventListener('dragleave', () => {
  eBox.classList.remove('border-primary');
});
eBox.addEventListener('drop', e => {
  e.preventDefault();
  eBox.classList.remove('border-primary');
  eInput.files = e.dataTransfer.files;
  editShowPreview();
});

eInput.addEventListener('change', editShowPreview);

function editShowPreview() {
  const f = eInput.files[0];
  if (!f) return;
  const reader = new FileReader();
  reader.onload = ev => {
    ePreview.src = ev.target.result;
    ePreview.classList.remove('d-none');
    ePrompt.classList.add('d-none');
  };
  reader.readAsDataURL(f);
}

</script>

<?php include __DIR__ . '/templates/footer.php'; ?>