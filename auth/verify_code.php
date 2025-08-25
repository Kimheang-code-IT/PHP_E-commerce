<?php
// C:\xampp\htdocs\coffee-shop\auth\reset_with_code.php

require_once __DIR__ . '/../config/auth.php';  // sets $conn, BASE_URL, flash(), session_start()
date_default_timezone_set('Asia/Phnom_Penh');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email           = trim($_POST['email'] ?? '');
    $code            = trim($_POST['code'] ?? '');
    $newPassword     = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // 1) Basic validation
    if (!$email || !$code || !$newPassword || !$confirmPassword) {
        $errors[] = 'All fields are required.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // 2) Verify code & expiry
    if (empty($errors)) {
        $stmt = $conn->prepare("
            SELECT reset_token, reset_expires
              FROM users
             WHERE email = ?
             LIMIT 1
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();

        if (! $user = $res->fetch_assoc()) {
            $errors[] = 'Email not found.';
        } else {
            if ($user['reset_token'] !== $code) {
                $errors[] = 'Invalid reset code.';
            } elseif (strtotime($user['reset_expires']) < time()) {
                $errors[] = 'Reset code has expired.';
            }
        }
    }

    // 3) Update password
    if (empty($errors)) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $conn->prepare("
            UPDATE users
               SET password      = ?,
                   reset_token   = NULL,
                   reset_expires = NULL
             WHERE email = ?
        ");
        $upd->bind_param('ss', $hashed, $email);
        if ($upd->execute()) {
            flash('success', 'Your password has been updated. Please log in.');
            header('Location: ' . BASE_URL . '/auth/login.php');
            exit;
        } else {
            $errors[] = 'Database error: could not update password.';
        }
        $upd->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password with Code</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; }
    .auth-container { max-width: 400px; margin: 5% auto; }
    .auth-card { border: none; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .auth-card .card-header { background: #fff; border-bottom: 1px solid #eee; text-align: center; padding: 1rem; }
    .auth-card .logo { font-size: 1.75rem; font-weight: 700; }
    .auth-card .card-body { padding: 2rem; }
    .btn-primary { background: #0d6efd; border-color: #0d6efd; }
    .btn-primary:hover { background: #0b5ed7; }
  </style>
</head>
<body>
  <div class="auth-container">
    <div class="card auth-card">
      <div class="card-header">
        <div class="logo">POS<span style="font-weight:400;">BARCODE</span></div>
      </div>
      <div class="card-body">
        <h5 class="mb-4 text-center">
          <i class="bi bi-arrow-clockwise"></i> Reset Password
        </h5>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/auth/reset_with_code.php" method="post">
          <div class="mb-3">
            <label for="email" class="form-label">Your Email</label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control"
              placeholder="name@example.com"
              required
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            >
          </div>
          <div class="mb-3">
            <label for="code" class="form-label">6-Digit Code</label>
            <input
              type="text"
              id="code"
              name="code"
              class="form-control"
              placeholder="123456"
              pattern="\d{6}"
              title="Enter the 6-digit code"
              required
              value="<?= htmlspecialchars($_POST['code'] ?? '') ?>"
            >
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              placeholder="••••••••"
              required
            >
          </div>
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input
              type="password"
              id="confirm_password"
              name="confirm_password"
              class="form-control"
              placeholder="••••••••"
              required
            >
          </div>
          <button type="submit" class="btn btn-primary w-100">
            Update Password
          </button>
        </form>

        <p class="text-center mt-3">
          <a href="<?= BASE_URL ?>/auth/login.php">
            <i class="bi bi-arrow-left"></i> Back to Login
          </a>
        </p>
      </div>
    </div>
  </div>

  <!-- Bootstrap Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
