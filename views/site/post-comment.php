<?php
// views/site/post-comment.php
session_start();

// include your DB & config
require __DIR__ . '/../../config/db.php'; // defines $mysqli and BASE_URL

$post_id = (int)($_POST['post_id'] ?? 0);
$name     = trim($_POST['name']    ?? '');
$email    = trim($_POST['email']   ?? '');
$content  = trim($_POST['content'] ?? '');

if ($post_id < 1 || $name === '' || $email === '' || $content === '') {
    // you could set a flash message here
    header("Location: " . BASE_URL . "/views/site/blog-detail.php?id=$post_id#comments");
    exit;
}

$stmt = $mysqli->prepare("
  INSERT INTO comments (post_id, name, email, content, created_at)
  VALUES (?, ?, ?, ?, NOW())
");
$stmt->bind_param('isss', $post_id, $name, $email, $content);
$stmt->execute();
$stmt->close();

header("Location: " . BASE_URL . "/views/site/blog-detail.php?id=$post_id#comments");
exit;
