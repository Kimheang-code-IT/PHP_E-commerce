<?php
// views/site/index.php

// 1) Page metadata
$pageTitle = 'Blog';

// 2) Shared header (starts session, defines $mysqli, BASE_URL, outputs <head>…)
include __DIR__ . '/../includes/header.php';

// 3) Grab filters
$search = trim($_GET['search'] ?? '');
$sort   = $_GET['sort']   ?? 'newest';

// 4) Build WHERE clause
$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = 'title LIKE ?';
    $params[] = "%{$search}%";
    $types   .= 's';
}

// 5) Decide ORDER BY
switch ($sort) {
    case 'oldest':
        $orderBy = 'published_at ASC';
        break;
    case 'title_az':
        $orderBy = 'title ASC';
        break;
    case 'title_za':
        $orderBy = 'title DESC';
        break;
    default:
        $orderBy = 'published_at DESC';
}

// 6) Query for page posts
$sql = "
    SELECT id, title, body, image, published_at, author
      FROM posts
    " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
    ORDER BY {$orderBy}
    LIMIT 10
";
$stmt = $mysqli->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$rstmt = $mysqli->prepare("
    SELECT id, title, image
      FROM posts
  ORDER BY published_at DESC
     LIMIT 5
");
$rstmt->execute();
$recent = $rstmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rstmt->close();
?>

<style>
    .main {
        margin-top: 80px;
    }

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

<div class="main">
    <!-- Hero -->
    <section class="bg-img1 txt-center p-lr-15 p-tb-92"
        style="background-image:url('<?= BASE_URL ?>/assets/images/bg-02.jpg');">
        <h2 class="ltext-105 cl0"><?= htmlspecialchars($pageTitle) ?></h2>
    </section>

    <!-- Content + Sidebar -->
    <section class="bg0 p-t-62 p-b-60">
        <div class="container">
            <div class="row">

                <!-- POSTS (8 cols) -->
                <div class="col-md-8 col-lg-9 p-b-80">
                    <div class="p-r-45 p-r-0-lg">
                        <?php foreach ($posts as $post):
                            $dt      = new DateTime($post['published_at']);
                            $excerpt = nl2br(htmlspecialchars(substr($post['body'], 0, 200))) . '…';
                            $imgUrl  = $post['image']
                                ? BASE_URL . '/' . ltrim($post['image'], '/')
                                : BASE_URL . '/assets/images/default-blog.jpg';
                        ?>
                            <div class="p-b-63">
                                <a href="<?= BASE_URL ?>/views/site/blog-detail.php?id=<?= $post['id'] ?>"
                                    class="hov-img0 how-pos5-parent">
                                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="" class="img-fluid w-100">
                                    <div class="flex-col-c-m size-123 bg9 how-pos5">
                                        <span class="ltext-107 cl2"><?= $dt->format('d') ?></span>
                                        <span class="stext-109 cl3"><?= $dt->format('M Y') ?></span>
                                    </div>
                                </a>
                                <div class="p-t-32">
                                    <h4 class="p-b-15">
                                        <a href="<?= BASE_URL ?>/views/site/blog-detail.php?id=<?= $post['id'] ?>"
                                            class="ltext-108 cl2 hov-cl1 trans-04">
                                            <?= htmlspecialchars($post['title']) ?>
                                        </a>
                                    </h4>
                                    <p class="stext-117 cl6"><?= $excerpt ?></p>
                                    <div class="flex-w flex-sb-m p-t-18">
                                        <span class="stext-111 cl2">
                                            <span class="cl4">By</span> <?= htmlspecialchars($post['author']) ?>
                                        </span>
                                        <a href="<?= BASE_URL ?>/views/site/blog-detail.php?id=<?= $post['id'] ?>"
                                            class="stext-101 cl2 hov-cl1 trans-04">
                                            Continue Reading
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SIDEBAR (4 cols) -->
                <div class="col-md-4 col-lg-3 p-b-80">
                    <!-- Search/Sort widget -->
                    <div class="card mb-4 sidebar-widget">
                        <h5 class="card-header">Search &amp; Sort</h5>
                        <div class="card-body">
                            <form method="get" action="">
                                <div class="input-group mb-3">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Search…" value="<?= htmlspecialchars($search) ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="submit">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-8">
                                        <select name="sort" class="form-control">
                                            <option value="newest" <?= $sort === 'newest'   ? 'selected' : '' ?>>Newest</option>
                                            <option value="oldest" <?= $sort === 'oldest'   ? 'selected' : '' ?>>Oldest</option>
                                            <option value="title_az" <?= $sort === 'title_az' ? 'selected' : '' ?>>A→Z</option>
                                            <option value="title_za" <?= $sort === 'title_za' ? 'selected' : '' ?>>Z→A</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <button class="btn btn-primary btn-block" type="submit">Apply</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Recent Posts widget -->
                    <div class="card mb-4 sidebar-widget">
  <h5 class="card-header">Recent Posts</h5>
  <div class="list-group list-group-flush">
    <?php foreach ($recent as $r):
      // build a thumbnail URL, falling back to a default
      $imgUrl = $r['image']
        ? BASE_URL . '/' . ltrim($r['image'], '/')
        : BASE_URL . '/assets/images/default-blog.jpg';
    ?>
      <a href="<?= BASE_URL ?>/views/site/blog-detail.php?id=<?= $r['id'] ?>"
         class="list-group-item list-group-item-action d-flex align-items-center">
        <img src="<?= htmlspecialchars($imgUrl) ?>"
             alt="<?= htmlspecialchars($r['title']) ?>"
             class="rounded mr-2"
             style="width:40px; height:40px; object-fit:cover;">
        <span><?= htmlspecialchars($r['title']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

                </div>

            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>