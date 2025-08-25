<?php
// C:\xampp\htdocs\Barcode\views\category.php

require_once __DIR__ . '/../config/db.php';

// ——————————————————————————————————————————
// Image‐upload helper
// ——————————————————————————————————————————
function handleImageUpload(string $fieldName): ?string
{
  if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
    return null;
  }
  $tmp  = $_FILES[$fieldName]['tmp_name'];
  $orig = basename($_FILES[$fieldName]['name']);
  $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
  $allowed = ['jpg', 'jpeg', 'png', 'gif'];
  if (!in_array($ext, $allowed, true)) {
    return null;
  }
  $newName = uniqid('cat_', true) . ".$ext";
  $destDir = __DIR__ . '/../uploads/categories/';
  if (!is_dir($destDir)) mkdir($destDir, 0755, true);
  $destPath = $destDir . $newName;
  if (!move_uploaded_file($tmp, $destPath)) {
    return null;
  }
  return 'uploads/categories/' . $newName;
}

// ——————————————————————————————————————————
// Handle Add / Update / Delete
// ——————————————————————————————————————————
$error   = '';
$success = '';

// Are we editing?
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editCat = null;
if ($editId) {
  $stmt = $conn->prepare("SELECT * FROM categories WHERE id=?");
  $stmt->bind_param('i', $editId);
  $stmt->execute();
  $editCat = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

// 1) Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
  $n = trim($_POST['name'] ?? '');
  $d = trim($_POST['description'] ?? '');
  if ($n === '') {
    $error = 'Name is required.';
  } else {
    $img = handleImageUpload('image') ?: '';
    $ins = $conn->prepare("
      INSERT INTO categories (name,image,description)
      VALUES(?,?,?)
    ");
    $ins->bind_param('sss', $n, $img, $d);
    try {
      $ins->execute();
      $success = 'Category added.';
    } catch (mysqli_sql_exception $e) {
      $error = $e->getCode() === 1062
        ? 'A category with that name already exists.'
        : 'Insert failed.';
    }
    $ins->close();
  }
  header('Location: ' . $_SERVER['PHP_SELF'] . '?added=' . urlencode($success ?: $error));
  exit;
}

// 2) Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
  $id = (int)$_POST['edit_id'];
  $n  = trim($_POST['name'] ?? '');
  $d  = trim($_POST['description'] ?? '');
  if ($n === '') {
    $error = 'Name is required.';
  } else {
    $new = handleImageUpload('image');
    $img = $new ?: trim($_POST['edit_image'] ?? '');
    $upd = $conn->prepare("
      UPDATE categories
         SET name=?,description=?,image=?,updated_at=NOW()
       WHERE id=?
    ");
    $upd->bind_param('sssi', $n, $d, $img, $id);
    try {
      $upd->execute();
      $success = 'Category updated.';
    } catch (mysqli_sql_exception $e) {
      $error = $e->getCode() === 1062
        ? 'That category name is already in use.'
        : 'Update failed.';
    }
    $upd->close();
  }
  header('Location: ' . $_SERVER['PHP_SELF'] . '?updated=' . urlencode($success ?: $error));
  exit;
}

// 3) Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
  $id = (int)$_POST['delete_id'];
  $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
  $stmt->bind_param('i', $id);
  $ok = $stmt->execute();
  $stmt->close();
  $success = $ok ? 'Category deleted.' : 'Delete failed.';
  header('Location: ' . $_SERVER['PHP_SELF'] . '?deleted=' . urlencode($success));
  exit;
}

// Fetch all
$res = $conn->query("
  SELECT id,name,image,description,products_count,created_at,updated_at
    FROM categories ORDER BY id DESC
");
$categories = $res->fetch_all(MYSQLI_ASSOC);

// Grab any flash from query string
$flashMsg = '';
if (isset($_GET['added']))   $flashMsg = $_GET['added'];
if (isset($_GET['updated'])) $flashMsg = $_GET['updated'];
if (isset($_GET['deleted'])) $flashMsg = $_GET['deleted'];

$pageTitle = 'Categories';
include __DIR__ . '/templates/header.php';
?>
<style>
  .table-container {
    max-height: 380px;
    overflow: auto;
  }

  .table-container thead th {
    position: sticky;
    top: 0;
    background: #34495e;
    color: #fff;
  }

  .col-desc {
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
</style>

<div class="container-fluid mt-2">
  <div class="row gx-2 gy-2">

    <!-- Add / Edit Form -->
    <div class="col-md-4">
      <div class="card shadow-sm mb-4" style="height:468px;font-size:10px">
        <div class="card-header text-white" style="background:#34495e">
          <h5 class="mb-0">
            <?= $editCat ? 'Edit Category' : 'Add Category' ?>
          </h5>
        </div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data">
            <?php if ($error && ($editId ? isset($_GET['updated']) : isset($_GET['added']))): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <div class="mb-2" style="font-size:14px">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input name="name" class="form-control"
                value="<?= $editCat ? htmlspecialchars($editCat['name']) : '' ?>"
                placeholder="Category name" required>
            </div>
            <div class="mb-2">
              <label class="form-label" style="font-size:14px">Description</label>
              <textarea name="description" class="form-control" rows="3"><?= $editCat ? htmlspecialchars($editCat['description']) : '' ?></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label" style="font-size:14px">Image</label>
              <div id="uploadBox" class="position-relative border rounded"
                style="border:2px dashed #ced4da;cursor:pointer;
                          width:100%;min-height:130px;
                          display:flex;align-items:center;justify-content:center;
                          overflow:hidden">
                <div id="uploadPrompt" class="text-center">
                  <i class="bi bi-cloud-arrow-up" style="font-size:2rem;color:#6c757d"></i>
                  <p class="m-0 text-secondary">Drag &amp; drop or click to select</p>
                </div>
                <img id="uploadPreview" src="#"
                  class="position-absolute top-0 start-0 w-100 h-100 d-none"
                  style="object-fit:contain">
                <input type="file" name="image" id="uploadInput"
                  accept="image/*"
                  class="position-absolute top-0 start-0 w-100 h-100 opacity-0">
              </div>
              <?php if ($editCat && $editCat['image']): ?>
                <input type="hidden" name="edit_image" value="<?= $editCat['image'] ?>">
              <?php endif; ?>
            </div>
            <?php if ($editCat): ?>
              <input type="hidden" name="edit_id" value="<?= $editCat['id'] ?>">

              <div class="d-flex gap-2">
                <button
                  name="update"
                  class="btn text-white flex-fill"
                  style="background:#305557">
                  Update 
                </button>

                <a
                  href="<?= $_SERVER['PHP_SELF'] ?>"
                  class="btn btn-secondary flex-fill">
                  Cancel
                </a>
              </div>

            <?php else: ?>

              <button
                name="add"
                class="btn w-100 text-white"
                style="background:#305557">
                Add Category
              </button>

            <?php endif; ?>

          </form>
        </div>
      </div>
    </div>

    <!-- Search & Table -->
    <div class="col-md-8">
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <input id="searchBox" class="form-control mb-3"
            placeholder="Search by name…">
          <div class="table-container">
            <table class="table table-bordered table-hover" style="font-size:10px">
              <thead>
                <tr>
                  <th style="width:10px">ID</th>
                  <th>Name</th>
                  <th>Image</th>
                  <th>Description</th>
                  <th>Count</th>
                  <th>Created</th>
                  <th>Updated</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($categories)): ?>
                  <tr>
                    <td colspan="8" class="text-center">No categories.</td>
                  </tr>
                  <?php else: foreach ($categories as $c): ?>
                    <tr>
                      <td><?= $c['id'] ?></td>
                      <td class="col-name"><?= htmlspecialchars($c['name']) ?></td>
                      <td>
                        <?php if ($c['image']): ?>
                          <img src="<?= BASE_URL . '/' . $c['image'] ?>"
                            style="height:20px">
                          <?php else: ?>&mdash;<?php endif; ?>
                      </td>
                      <td class="col-desc"><?= htmlspecialchars($c['description']) ?></td>
                      <td><?= $c['products_count'] ?></td>
                      <td><?= date('Y-m-d H:i', strtotime($c['created_at'])) ?></td>
                      <td><?= date('Y-m-d H:i', strtotime($c['updated_at'])) ?></td>
                      <td class="text-center">
                        <a href="?edit=<?= $c['id'] ?>"
                          class="btn btn-sm btn-primary" style="font-size:10px">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <button class="btn btn-sm btn-danger btn-del" style="font-size:10px"
                          data-id="<?= $c['id'] ?>"
                          data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>">
                          <i class="bi bi-trash"></i>
                        </button>
                      </td>
                    </tr>
                <?php endforeach;
                endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>

<script>
  // Show a flash via SweetAlert
  <?php if ($flashMsg): ?>
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: '<?= strpos($flashMsg, 'failed') === false ? 'success' : 'error' ?>',
      title: <?= json_encode($flashMsg) ?>,
      showConfirmButton: false,
      timer: 2000
    });
  <?php endif; ?>

  // Live search
  document.getElementById('searchBox').addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(r => {
      r.style.display = r.querySelector('.col-name').textContent
        .toLowerCase().includes(q) ? '' : 'none';
    });
  });

  // Delete buttons → SweetAlert confirm + POST
  document.querySelectorAll('.btn-del').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      const name = btn.dataset.name;
      Swal.fire({
        title: `Delete “${name}”?`,
        text: "This cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete'
      }).then(res => {
        if (!res.isConfirmed) return;
        // post a small form
        const f = document.createElement('form');
        f.method = 'POST';
        f.style.display = 'none';
        f.innerHTML = `
        <input type="hidden" name="delete_id" value="${id}">
      `;
        document.body.appendChild(f);
        f.submit();
      });
    });
  });

  // Image preview for Add/Edit form
  const box = document.getElementById('uploadBox'),
    input = document.getElementById('uploadInput'),
    prev = document.getElementById('uploadPreview'),
    prompt = document.getElementById('uploadPrompt');

  // If we're editing and there's already an image, show it in the uploadBox
  <?php if ($editCat && $editCat['image']): ?>
    prev.src = <?= json_encode(BASE_URL . '/' . $editCat['image']) ?>;
    prev.classList.remove('d-none');
    prompt.classList.add('d-none');
  <?php endif; ?>

  input.addEventListener('change', showPreview);
  box.addEventListener('drop', e => {
    e.preventDefault();
    input.files = e.dataTransfer.files;
    showPreview();
  });
  box.addEventListener('dragover', e => e.preventDefault());

  function showPreview() {
    const f = input.files[0];
    if (!f) return;
    const r = new FileReader();
    r.onload = e => {
      prev.src = e.target.result;
      prev.classList.remove('d-none');
      prompt.classList.add('d-none');
    };
    r.readAsDataURL(f);
  }
</script>