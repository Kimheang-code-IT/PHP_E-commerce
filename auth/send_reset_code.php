<?php
// C:\xampp\htdocs\Barcode\auth\send_reset_code.php

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Phnom_Penh');

// AJAX detection
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $msg = 'Invalid CSRF token.';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
    } else {
        flash('error', $msg);
        header('Location: ' . BASE_URL . '/auth/forgot_password.php');
    }
    exit;
}

$email = trim($_POST['email'] ?? '');
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $msg = 'Please enter a valid email address.';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
    } else {
        flash('error', $msg);
        header('Location: ' . BASE_URL . '/auth/forgot_password.php');
    }
    exit;
}

// Check rate limit (max 3 attempts per hour)
$stmt = $conn->prepare("
    SELECT COUNT(*) as attempts
    FROM users
    WHERE email = ? AND reset_expires > NOW() - INTERVAL 1 HOUR
");
$stmt->bind_param('s', $email);
$stmt->execute();
$attempts = $stmt->get_result()->fetch_assoc()['attempts'];
$stmt->close();

if ($attempts >= 3) {
    $msg = 'Too many reset attempts. Please try again later.';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
    } else {
        flash('error', $msg);
        header('Location: ' . BASE_URL . '/auth/forgot_password.php');
    }
    exit;
}

// Lookup user
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    $msg = 'Email not found.';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
    } else {
        flash('error', $msg);
        header('Location: ' . BASE_URL . '/auth/forgot_password.php');
    }
    exit;
}

$userId = $user['id'];
$code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Store reset code
$upd = $conn->prepare("
    UPDATE users
    SET reset_token = ?, reset_expires = ?
    WHERE id = ?
");
$upd->bind_param('ssi', $code, $expiry, $userId);
$upd->execute();
$upd->close();

// Store email in session
$_SESSION['reset_email'] = $email;

// Send email
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = getenv('SMTP_USERNAME') ?: 'heang015873174@gmail.com';
    $mail->Password = getenv('SMTP_PASSWORD') ?: 'qtbbunolnqrlehwk';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom($mail->Username, 'POSBARCODE Support');
    $mail->addAddress($email);
    $mail->Subject = 'Your Password Reset Code';
    $mail->Body = "Hello,\n\nYour 6-digit reset code is: $code\nIt expires at $expiry (Asia/Phnom_Penh)\n\nIf you didn’t request this, please ignore this email.";

    $mail->send();

    $msg = 'A reset code has been sent to your email.';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } else {
        flash('success', $msg);
        header('Location: ' . BASE_URL . '/auth/code_sent.php');
    }
    exit;
} catch (Exception $e) {
    $msg = 'Failed to send email: ' . $e->getMessage();
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
    } else {
        flash('error', $msg);
        header('Location: ' . BASE_URL . '/auth/forgot_password.php');
    }
    exit;
}
?>