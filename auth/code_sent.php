<?php
// C:\xampp\htdocs\Barcode\auth\code_sent.php

require_once __DIR__ . '/../config/auth.php';

$reset_email = $_SESSION['reset_email'] ?? '';
if (empty($reset_email)) {
    flash('error', 'No reset request found. Please start over.');
    header('Location: ' . BASE_URL . '/auth/forgot_password.php');
    exit;
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POSBARCODE — Check Your Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .login-card { max-width: 400px; margin: 5% auto; background: #fff; border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .login-header { padding: 1rem; text-align: center; border-bottom: 1px solid #eee; }
        .login-header h1 { margin: 0; font-size: 1.75rem; font-weight: 700; }
        .login-body { padding: 1.5rem; text-align: center; }
        .btn-login { background: #0d6efd; color: #fff; }
        .btn-login:hover { background: #0b5ed7; }
        .btn-outline-primary:hover { background: #0b5ed7; color: #fff; }
        .mt-2 { margin-top: .5rem !important; }
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
            <?php if ($error = flash('error')): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success = flash('success')): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <h4 class="mb-3"><i class="bi bi-envelope"></i> Check Your Email</h4>
            <p>A <strong>6-digit reset code</strong> has been sent to <strong><?= htmlspecialchars($reset_email) ?></strong>.<br>
               It will expire in <strong>10 minutes</strong>.</p>

            <a href="<?= BASE_URL ?>/auth/reset_with_code.php" class="btn btn-login w-100 mt-2">Enter Reset Code</a>

            <form method="post" action="<?= BASE_URL ?>/auth/send_reset_code.php" class="mt-2 needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($reset_email) ?>">
                <button type="submit" class="btn btn-outline-primary w-100">Resend Code</button>
            </form>

            <p class="text-center mt-3">
                <a href="<?= BASE_URL ?>/auth/login.php">← Back to Login</a>
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