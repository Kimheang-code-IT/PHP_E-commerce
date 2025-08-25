<?php
// C:\xampp\htdocs\Barcode\auth\reset_with_code.php

require_once __DIR__ . '/../config/auth.php';
date_default_timezone_set('Asia/Phnom_Penh');

$reset_email = $_SESSION['reset_email'] ?? '';
$errors = [];
$csrf_token = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }

    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (!$email || !$code || !$newPassword || !$confirmPassword) {
        $errors[] = 'All fields are required.';
    }
    if ($email !== $reset_email) {
        $errors[] = 'Invalid email. Please start the reset process again.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $newPassword)) {
        $errors[] = 'Password must be at least 8 characters long and include at least one letter and one number.';
    }

    // Verify code and expiry
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
        $user = $res->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $errors[] = 'Email not found.';
        } else {
            if ($user['reset_token'] !== $code) {
                $errors[] = 'Invalid reset code.';
            } elseif (strtotime($user['reset_expires']) < time()) {
                $errors[] = 'Reset code has expired.';
            }
        }
    }

    // Update password
    if (empty($errors)) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $conn->prepare("
            UPDATE users
            SET password = ?, reset_token = NULL, reset_expires = NULL
            WHERE email = ?
        ");
        $upd->bind_param('ss', $hashed, $email);
        if ($upd->execute()) {
            unset($_SESSION['reset_email'], $_SESSION['reset_expires']);
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
    <title>POSBARCODE — Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .login-card { max-width: 400px; margin: 5% auto; background: #fff; border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .login-header { padding: 1rem; text-align: center; border-bottom: 1px solid #eee; }
        .login-header h1 { margin: 0; font-size: 1.75rem; font-weight: 700; }
        .login-body { padding: 1.5rem; }
        .form-control:focus { box-shadow: none; border-color: #0d6efd; }
        .btn-login { background: #0d6efd; color: #fff; }
        .btn-login:hover { background: #0b5ed7; }
        .invalid-feedback { display: none; }
        .was-validated .form-control:invalid ~ .invalid-feedback { display: block; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h1>POS<span style="font-weight:400;">BARCODE</span></h1>
        </div>
        <div class="login-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($success = flash('success')): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <h5 class="mb-4 text-center"><i class="bi bi-arrow-clockwise"></i> Reset Password</h5>

            <form action="<?= BASE_URL ?>/auth/reset_with_code.php" method="post" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">Your Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="name@example.com"
                        required
                        value="<?= htmlspecialchars($reset_email) ?>"
                        readonly>
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
                        value="<?= htmlspecialchars($_POST['code'] ?? '') ?>">
                    <div class="invalid-feedback">Please enter a 6-digit code.</div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="••••••••"
                        pattern="(?=.*[A-Za-z])(?=.*\d).{8,}"
                        title="Password must be at least 8 characters long and include at least one letter and one number."
                        required>
                    <div class="invalid-feedback">Password must be at least 8 characters long and include at least one letter and one number.</div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="••••••••"
                        required>
                    <div class="invalid-feedback">Please confirm your password.</div>
                </div>
                <button type="submit" class="btn btn-login w-100">Update Password</button>
            </form>

            <p class="text-center mt-3">
                <a href="<?= BASE_URL ?>/auth/login.php"><i class="bi bi-arrow-left"></i> Back to Login</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            'use strict';
            const form = document.querySelector('.needs-validation');
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        })();
    </script>
</body>
</html>
?>