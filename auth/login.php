<?php
// C:\xampp\htdocs\Barcode\auth\login.php

require_once __DIR__ . '/../config/auth.php';

// ── 1) Already logged in? ──────────────────────────────────────
if (isLoggedIn()) {
    // we store role_id into session below, so pull it back out here
    $roleId = $_SESSION['user_role_id'] ?? 0;

    // role_id 1 (admin) or 2 (saler) → POS dashboard
    if ($roleId === 1 || $roleId === 2) {
        header('Location: ' . BASE_URL . '/views/pos.php');
    } else {
        // all other roles (e.g. customer) → front page
        header('Location: ' . BASE_URL . '/views/site/index.php');
    }
    exit;
}

// ── 2) Show login form or handle POST ─────────────────────────
$error      = '';
$email      = '';
$csrf_token = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid CSRF token.');
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }

    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    // Simple server-side validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
    } elseif (empty($password)) {
        flash('error', 'Please enter a password.');
    } else {
        // Fetch user by email, *including* role_id
        $stmt = $conn->prepare("
            SELECT u.id
                 , u.email
                 , u.password
                 , u.role_id
              FROM users u
             WHERE u.email = ?
             LIMIT 1
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            // Persist both your id/email *and* role_id
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user_email']    = $user['email'];
            $_SESSION['user_role_id']  = (int)$user['role_id'];

            // Handle “Remember Me”
            if ($remember_me) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_me', $token, time() + 30 * 24 * 3600, '/', '', true, true);
                $u = $conn->prepare("UPDATE users SET remember_token=? WHERE id=?");
                $u->bind_param('si', $token, $user['id']);
                $u->execute();
                $u->close();
            }

            flash('success', 'Logged in successfully!');

            // Redirect based on the numeric role_id
            if ($_SESSION['user_role_id'] === 1 || $_SESSION['user_role_id'] === 2) {
                header('Location: ' . BASE_URL . '/views/pos.php');
            } else {
                header('Location: ' . BASE_URL . '/views/site/index.php');
            }
            exit;
        } else {
            flash('error', $user ? 'Incorrect password.' : 'Account not found.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>POSBARCODE — Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
        }

        .login-card {
            max-width: 400px;
            margin: 5% auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .login-header {
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .login-header h1 {
            margin: 0;
            font-size: 1.75rem;
        }

        .login-body {
            padding: 1.5rem;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #0d6efd;
        }

        .input-group-text {
            background: #e9ecef;
            cursor: pointer;
        }

        .btn-login {
            background: #0d6efd;
            color: #fff;
        }

        .btn-login:hover {
            background: #0b5ed7;
        }

        .forgot-link {
            font-size: .9rem;
        }

        .invalid-feedback {
            display: none;
        }

        .was-validated .form-control:invalid~.invalid-feedback {
            display: block;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <h1>POS<span style="font-weight:400;">BARCODE</span></h1>
        </div>
        <div class="login-body">
            <?php if ($err = flash('error')): ?>
                <div class="alert alert-danger"><?= $err ?></div>
            <?php endif; ?>
            <?php if ($ok = flash('success')): ?>
                <div class="alert alert-success"><?= $ok ?></div>
            <?php endif; ?>

            <p class="text-center mb-4">Sign in to start your session</p>

            <form method="post" action="<?= BASE_URL ?>/auth/login.php" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="mb-3">
                    <div class="input-group has-validation">
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="Email"
                            required
                            autofocus
                            value="<?= htmlspecialchars($email) ?>">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-group has-validation" id="passwordGroup">
                        <input
                            type="password"
                            name="password"
                            id="passwordInput"
                            class="form-control"
                            placeholder="Password"
                            required>
                        <span class="input-group-text" id="togglePassword">
                            <i class="bi bi-eye-slash" id="toggleIcon"></i>
                        </span>
                        <div class="invalid-feedback">Please enter a password.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="remember_me" id="rememberMe" class="form-check-input">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                    <a href="<?= BASE_URL ?>/auth/forgot_password.php" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-login w-100">Login</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            'use strict';
            const form = document.querySelector('.needs-validation');
            form.addEventListener('submit', e => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);

            document.getElementById('togglePassword').addEventListener('click', function() {
                const inp = document.getElementById('passwordInput'),
                    ic = document.getElementById('toggleIcon');
                if (inp.type === 'password') {
                    inp.type = 'text';
                    ic.classList.replace('bi-eye-slash', 'bi-eye');
                } else {
                    inp.type = 'password';
                    ic.classList.replace('bi-eye', 'bi-eye-slash');
                }
            });
        })();
    </script>
</body>

</html>