<?php
// views/site/create-post.php

// 1) bootstrap and header
include __DIR__ . '/../includes/header.php';   // starts session, sets $mysqli, defines BASE_URL

$error       = '';
$justCreated = false;
$redirectUrl = BASE_URL . '/views/site/blog.php';

// 2) handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title']   ?? '');
    $body         = trim($_POST['body']    ?? '');
    $author       = trim($_POST['author']  ?? '');
    $published_at = date('Y-m-d H:i:s');

    // basic validation
    if ($title === '' || $body === '' || $author === '') {
        $error = 'All fields except image are required.';
    } else {
        // handle upload
        $imagePath = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/posts/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = time() . '_' . basename($_FILES['image']['name']);
            $target   = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imagePath = 'uploads/posts/' . $filename;
            } else {
                $error = 'Failed to move uploaded file.';
            }
        }

        // insert into DB
        if (empty($error)) {
            $sql = "INSERT INTO posts (title, body, image, published_at, author)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                die('Prepare failed: ' . $mysqli->error);
            }

            $stmt->bind_param(
                'sssss',
                $title,
                $body,
                $imagePath,
                $published_at,
                $author
            );

            if ($stmt->execute()) {
                // flag success so we can show SweetAlert after the HTML renders
                $justCreated = true;
            } else {
                $error = 'Database error: ' . htmlspecialchars($stmt->error);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create New Blog Post</title>
    <!-- Bootstrap CSS -->
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background: #eef2f3;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .form-container {
            max-width: 600px;
            margin: 80px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            font-size: 24px;
            margin-bottom: 30px;
            color: #333;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            outline: none;
        }

        .upload-dropzone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px;
            border: 2px dashed #ccc;
            border-radius: 8px;
            background: #fafafa;
            transition: background 0.3s, border-color 0.3s;
            cursor: pointer;
        }

        .upload-dropzone:hover {
            background: #f5f5f5;
            border-color: #667eea;
        }

        .upload-dropzone p {
            margin: 8px 0;
            color: #777;
            font-size: 14px;
        }

        .upload-dropzone img.preview {
            max-width: 100%;
            max-height: 200px;
            margin-top: 12px;
            border-radius: 6px;
        }

        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-secondary {
            background: #ccc;
            color: #333;
        }

        .btn-secondary:hover {
            background: #b3b3b3;
        }

        .btn-primary {
            background: #667eea;
            color: #fff;
        }

        .btn-primary:hover {
            background: #556cd1;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>Create New Blog Post</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <!-- Title -->
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required
                    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                    class="form-control" placeholder="Enter post title">
            </div>
            <!-- Body -->
            <div class="form-group">
                <label>Body</label>
                <textarea name="body" rows="6" required
                    class="form-control"
                    placeholder="Write your content here..."><?= htmlspecialchars($_POST['body'] ?? '') ?></textarea>
            </div>
            <!-- Image upload -->
            <div class="form-group">
                <label>Image (optional)</label>
                <div id="dropzone" class="upload-dropzone">
                    <p>Drag &amp; drop or click to upload</p>
                    <img id="preview" class="preview d-none" alt="Preview">
                </div>
                <input type="file" name="image" id="imageInput" accept="image/*" hidden>
            </div>
            <!-- Author -->
            <div class="form-group">
                <label>Author</label>
                <input type="text" name="author" required
                    value="<?= htmlspecialchars($_POST['author'] ?? '') ?>"
                    class="form-control" placeholder="Your name">
            </div>
            <!-- Buttons -->
            <div class="btn-group d-flex justify-content-between mt-4">
                <a href="<?= $redirectUrl ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Publish</button>
            </div>
        </form>
    </div>

    <script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dropzone + preview
        const dropzone = document.getElementById('dropzone');
        const imageInput = document.getElementById('imageInput');
        const preview = document.getElementById('preview');

        dropzone.addEventListener('click', () => imageInput.click());
        dropzone.addEventListener('dragover', e => {
            e.preventDefault();
            dropzone.classList.add('hover');
        });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('hover'));
        dropzone.addEventListener('drop', e => {
            e.preventDefault();
            dropzone.classList.remove('hover');
            const file = e.dataTransfer.files[0];
            imageInput.files = e.dataTransfer.files;
            showPreview(file);
        });
        imageInput.addEventListener('change', () => showPreview(imageInput.files[0]));

        function showPreview(file) {
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }
    </script>

    <?php if ($justCreated): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Published!',
                text: 'Your new post has been created.',
                confirmButtonText: 'Back to Blog'
            }).then(() => {
                window.location = '<?= $redirectUrl ?>';
            });
        </script>
    <?php endif; ?>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>