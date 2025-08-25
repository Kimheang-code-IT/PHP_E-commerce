<?php
// views/tax.php

session_start();
require_once __DIR__ . '/../config/db.php';

// ──────────────────────────────────────────────────────────────────────────
// 1) Protect page
// ──────────────────────────────────────────────────────────────────────────
$self = basename($_SERVER['PHP_SELF']);
if ($self !== 'login.php' && empty($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . '/auth/login.php');
  exit;
}

// ──────────────────────────────────────────────────────────────────────────
// 2) Handle POST (Add / Update / Delete)
// ──────────────────────────────────────────────────────────────────────────
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add'])) {
    // ADD
    $name      = trim($_POST['name'] ?? '');
    $rate      = trim($_POST['rate'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    if ($name === '' || $rate === '') {
      $error = 'Name and rate are required.';
    } elseif (!is_numeric($rate)) {
      $error = 'Rate must be numeric.';
    } else {
      $stmt = $conn->prepare("
        INSERT INTO taxes (name, rate, is_active, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
      ");
      $stmt->bind_param('sdi', $name, $rate, $is_active);
      $success = $stmt->execute()
        ? 'Tax added.'
        : 'Insert failed: ' . $stmt->error;
      $stmt->close();
    }
  }
  elseif (isset($_POST['update'])) {
  $id        = (int)$_POST['edit_id'];
  $name      = trim($_POST['edit_name']  ?? '');
  $rate      = trim($_POST['edit_rate']  ?? '');
  // ** read the actual value, not just isset() **
  $is_active = (int) ($_POST['edit_is_active'] ?? 0);

  if ($name === '' || $rate === '') {
    $error = 'Name and rate are required.';
  } elseif (!is_numeric($rate)) {
    $error = 'Rate must be numeric.';
  } else {
    $stmt = $conn->prepare("
      UPDATE taxes
         SET name       = ?,
             rate       = ?,
             is_active  = ?,
             updated_at = NOW()
       WHERE id = ?
    ");
    $stmt->bind_param('sdii', $name, $rate, $is_active, $id);
    $success = $stmt->execute()
      ? 'Tax updated.'
      : 'Update failed: ' . $stmt->error;
    $stmt->close();
  }
}
  elseif (isset($_POST['delete_id'])) {
    // DELETE
    $del = (int)$_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM taxes WHERE id = ?");
    $stmt->bind_param('i', $del);
    $success = $stmt->execute()
      ? 'Tax deleted.'
      : 'Delete failed: ' . $stmt->error;
    $stmt->close();
  }
}

// ──────────────────────────────────────────────────────────────────────────
// 3) Fetch all taxes (including timestamps)
// ──────────────────────────────────────────────────────────────────────────
$res = $conn->query("
  SELECT id, name, rate, is_active, created_at, updated_at
    FROM taxes
   ORDER BY id DESC
");
$taxes = $res->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manage Taxes';
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
</style>

<div class="container-fluid mt-2" style="font-size:12px">
  <div class="row gx-2 gy-2">

    <!-- Add Form -->
    <div class="col-md-3">
      <div class="card shadow-sm mb-4">
        <div class="card-header text-white" style="background:#34495e">
          <h5 class="mb-0">Add Tax</h5>
        </div>
        <div class="card-body">
          <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
          <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input name="name" class="form-control" placeholder="Tax name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Rate (%) <span class="text-danger">*</span></label>
              <input name="rate" type="number" step="0.01" class="form-control" placeholder="e.g. 7.50" required>
            </div>
            <div class="form-check mb-3">
              <input name="is_active" type="checkbox" class="form-check-input" id="activeAdd" checked>
              <label for="activeAdd" class="form-check-label">Active</label>
            </div>
            <button name="add" class="btn btn-sm text-white w-100" style="background:#305557">
              Add Tax
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Search & Table -->
    <div class="col-md-9">
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <input id="searchBox" class="form-control mb-3" placeholder="Search by name…">

          <div class="table-container">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Rate</th>
                  <th>Active</th>
                  <th>Created</th>
                  <th>Updated</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($taxes)): ?>
                  <tr><td colspan="7" class="text-center">No taxes found.</td></tr>
                <?php else: foreach ($taxes as $t): ?>
                  <tr data-id="<?= $t['id'] ?>"
                      data-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>"
                      data-rate="<?= $t['rate'] ?>"
                      data-active="<?= $t['is_active'] ?>">
                    <td><?= $t['id'] ?></td>
                    <td class="col-name"><?= htmlspecialchars($t['name']) ?></td>
                    <td><?= number_format($t['rate'], 2) ?></td>
                    <td class="text-center">
                      <input type="checkbox" disabled <?= $t['is_active'] ? 'checked' : '' ?>>
                    </td>
                    <td><?= htmlspecialchars($t['created_at']) ?></td>
                    <td><?= htmlspecialchars($t['updated_at']) ?></td>
                    <td class="text-center">
                      <button style="font-size: 10px;" class="btn btn-sm btn-primary btn-edit"><i class="bi bi-pencil"></i></button>
                      <button style="font-size: 10px;"
                        class="btn btn-sm btn-danger btn-delete"
                        data-id="<?= $t['id'] ?>"
                        data-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Live search
  document.querySelector('#searchBox').addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(row => {
      const name = row.querySelector('.col-name').textContent.toLowerCase();
      row.style.display = name.includes(q) ? '' : 'none';
    });
  });

  // Delete confirmation
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', () => {
      const id   = btn.dataset.id;
      const name = btn.dataset.name;
      Swal.fire({
        title: `Delete "${name}"?`,
        text: "This cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete'
      }).then(res => {
        if (res.isConfirmed) {
          const f = document.createElement('form');
          f.method = 'post';
          f.innerHTML = `<input type="hidden" name="delete_id" value="${id}">`;
          document.body.appendChild(f);
          f.submit();
        }
      });
    });
  });

  // Inline edit
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      const row  = btn.closest('tr');
      const id   = row.dataset.id;
      const name = row.dataset.name;
      const rate = row.dataset.rate;
      const act  = row.dataset.active == '1';

      row.classList.add('text-nowrap');
      row.innerHTML = `
        <td>${id}<input type="hidden" name="edit_id" value="${id}"></td>
        <td><input name="edit_name" class="form-control form-control-sm" value="${name}"></td>
        <td><input name="edit_rate" type="number" step="0.01" class="form-control form-control-sm" value="${rate}"></td>
        <td class="text-center">
          <input name="edit_is_active" type="checkbox" class="form-check-input" ${act?'checked':''}>
        </td>
        <td>${row.cells[4].textContent}</td>
        <td>${row.cells[5].textContent}</td>
        <td class="text-center">
          <button class="btn btn-sm btn-success btn-save"><i class="bi bi-check-lg"></i></button>
          <button class="btn btn-sm btn-secondary btn-cancel"><i class="bi bi-x-lg"></i></button>
        </td>
      `;

      row.querySelector('.btn-cancel').onclick = () => location.reload();
      row.querySelector('.btn-save').onclick = () => {
        const f = document.createElement('form');
        f.method = 'post';
        f.innerHTML = `
          <input type="hidden" name="update" value="1">
          <input type="hidden" name="edit_id" value="${id}">
          <input type="hidden" name="edit_name" value="${row.querySelector('[name=edit_name]').value}">
          <input type="hidden" name="edit_rate" value="${row.querySelector('[name=edit_rate]').value}">
          <input type="hidden" name="edit_is_active" value="${row.querySelector('[name=edit_is_active]').checked?1:0}">
        `;
        document.body.appendChild(f);
        f.submit();
      };
    });
  });
</script>
