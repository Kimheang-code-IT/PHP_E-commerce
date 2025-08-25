<?php
// views/site/blog-detail.php

// 1) Page metadata
$pageTitle = 'Blog Detail';

// 2) Shared header
include __DIR__ . '/../includes/header.php';

// 3) Get & validate post ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
  header('Location: blog.php');
  exit;
}

// 4) Fetch main post
$stmt = $mysqli->prepare("
    SELECT id, title, body, image, published_at, author
      FROM posts
     WHERE id = ?
     LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
  echo '<div class="alert alert-warning">Post not found.</div>';
  include __DIR__ . '/../includes/footer.php';
  exit;
}

// 5) Fetch 5 most recent posts (for the sidebar)
$rstmt = $mysqli->prepare("
    SELECT id, title, image
      FROM posts
  ORDER BY published_at DESC
     LIMIT 5
");
$rstmt->execute();
$recent = $rstmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rstmt->close();

// 6) Prep main post data
$dt     = new DateTime($post['published_at']);
$imgUrl = $post['image']
  ? BASE_URL . '/' . ltrim($post['image'], '/')
  : BASE_URL . '/assets/images/default-blog.jpg';
?>
<style>
  /* sticky search/sort widget */
  .sidebar-widget {
    position: sticky;
    top: 100px;
    border-radius: .5rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, .1);
  }

  .sidebar-widget .card-header {
    background: #f8f9fa;
    font-weight: 600;
  }
</style>
<div style="margin-top: 80px;" class="main">

  <!-- breadcrumb -->
  <div class="container">
    <div class="bread-crumb flex-w p-l-25 p-r-15 p-t-30 p-lr-0-lg">
      <a href="<?= BASE_URL ?>/index.php" class="stext-109 cl8 hov-cl1 trans-04">
        Home <i class="fa fa-angle-right m-l-9 m-r-10"></i>
      </a>
      <a href="<?= BASE_URL ?>/views/site/index.php" class="stext-109 cl8 hov-cl1 trans-04">
        Blog <i class="fa fa-angle-right m-l-9 m-r-10"></i>
      </a>
      <span class="stext-109 cl4"><?= htmlspecialchars($post['title']) ?></span>
    </div>
  </div>

  <!-- Content page -->
  <section class="bg0 p-t-52 p-b-20">
    <div class="container">
      <div class="row">

        <!-- POST CONTENT -->
        <div class="col-md-8 col-lg-9 p-b-80">
          <div class="p-r-45 p-r-0-lg">

            <!-- Image + Date -->
            <div class="wrap-pic-w how-pos5-parent">
              <img src="<?= htmlspecialchars($imgUrl) ?>"
                alt="<?= htmlspecialchars($post['title']) ?>"
                class="img-fluid w-100">
              <div class="flex-col-c-m size-123 bg9 how-pos5">
                <span class="ltext-107 cl2 txt-center"><?= $dt->format('d') ?></span>
                <span class="stext-109 cl3 txt-center"><?= $dt->format('M Y') ?></span>
              </div>
            </div>

            <!-- Title, Meta, Body -->
            <div class="p-t-32">
              <span class="flex-w flex-m stext-111 cl2 p-b-19">
                <span><span class="cl4">By</span> <?= htmlspecialchars($post['author']) ?></span>
                <span class="cl12 m-l-4 m-r-6">|</span>
                <span><?= $dt->format('d M, Y') ?></span>
              </span>
              <h4 class="ltext-109 cl2 p-b-28"><?= htmlspecialchars($post['title']) ?></h4>
              <div class="stext-117 cl6 p-b-26">
                <?= nl2br(htmlspecialchars($post['body'])) ?>
              </div>
            </div>

            <!-- (You can keep your comments section here) -->

          </div>
        </div>

        <!-- SIDEBAR -->
        <div class="col-md-4 col-lg-3 p-b-80">
          <!-- Recent Posts with Thumbnails -->
          <div class="card mb-4 sidebar-widget">
            <h5 class="card-header">Recent Posts</h5>
            <div class="list-group list-group-flush">
              <?php foreach ($recent as $r):
                $thumb = $r['image']
                  ? BASE_URL . '/' . ltrim($r['image'], '/')
                  : BASE_URL . '/assets/images/default-blog.jpg';
              ?>
                <a href="<?= BASE_URL ?>/views/site/blog-detail.php?id=<?= $r['id'] ?>"
                  class="list-group-item list-group-item-action d-flex align-items-center">
                  <img src="<?= htmlspecialchars($thumb) ?>"
                    alt="<?= htmlspecialchars($r['title']) ?>"
                    class="rounded mr-2"
                    style="width:40px; height:40px; object-fit:cover;">
                  <span><?= htmlspecialchars($r['title']) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- ...any other sidebar widgets...-->

        </div>
      </div>
    </div>
  </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>