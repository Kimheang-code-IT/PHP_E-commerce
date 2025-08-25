<?php
// C:\xampp\htdocs\Barcode\auth\forgot_password.php

require_once __DIR__ . '/../config/auth.php';

// If already logged in, redirect based on role
if (isLoggedIn()) {
    $user = getCurrentUser($conn);
    if ($user && in_array($user['role'], ['pos_user', 'admin'])) {
        header('Location: ' . BASE_URL . '/views/pos.php');
    } else {
        header('Location: ' . BASE_URL . '/index.php');
    }
    exit;
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POSBARCODE — Forgot Password</title>
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
        .input-group-text { background: #e9ecef; border: 1px solid #ced4da; }
        .btn-login { background: #0d6efd; color: #fff; }
        .btn-login:hover { background: #0b5ed7; }
        .forgot-link { font-size: 0.9rem; }
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

            <p class="text-center mb-4">Enter your email to reset your password</p>

            <form method="post" action="<?= BASE_URL ?>/auth/send_reset_code.php" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="mb-3">
                    <div class="input-group has-validation">
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="Email"
                            required
                            autofocus>
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                </div>
                <button type="submit" class="btn btn-login w-100">Send Reset Code</button>
            </form>

            <p class="text-center mt-3">
                <a href="<?= BASE_URL ?>/auth/login.php" class="forgot-link">← Back to Login</a>
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