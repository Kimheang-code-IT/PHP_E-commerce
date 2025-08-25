<?php
// views/site/send-contact.php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1) Grab & sanitize
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$msg   = trim($_POST['msg'] ?? '');

if (!$email || $msg === '') {
    $_SESSION['contact_error'] = 'Please enter a valid email and message.';
    header('Location: contact.php');
    exit;
}

// 2) Load Composer autoloader (adjust path if you installed PHPMailer manually)
require __DIR__ . '/../../vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // SMTP setup
    $mail->isSMTP();
    $mail->SMTPAuth   = true;
    $mail->Host       = 'smtp.gmail.com';
    $mail->Port       = 587;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Username   = 'heang015873174@gmail.com';     // your Gmail
    $mail->Password   = 'qtbbunolnqrlehwk';                // use an App Password!

    // From / To
    $mail->setFrom($email);
    $mail->addAddress('heang015873174@gmail.com');               // your inbox

    // Content
    $mail->Subject = 'New contact from website';
    $mail->Body    = "From: $email\n\nMessage:\n$msg";

    $mail->send();
    $_SESSION['contact_success'] = 'Thanks! Your message has been sent.';
} catch (Exception $e) {
    error_log('Mail error: ' . $mail->ErrorInfo);
    $_SESSION['contact_error'] = 'Sorry, we couldn’t send your message right now.';
}

header('Location: contact.php');
exit;
