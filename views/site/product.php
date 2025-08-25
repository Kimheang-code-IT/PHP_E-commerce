<?php
// views/site/product.php

// 1) Page metadata
$pageTitle = 'Products';

// 2) Shared header include (session, $mysqli, BASE_URL, head…)
include __DIR__ . '/../includes/header.php';

// —————————————————————————————————————————————
// 3) Read incoming filters
$search = trim($_GET['search'] ?? '');
$catId  = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$sort   = in_array($_GET['sort'] ?? '', ['newest', 'oldest'])
  ? $_GET['sort']
  : 'newest';

// 4) Helper to rebuild query strings
function buildQuery(array $over = [])
{
  $q = $_GET;
  foreach ($over as $k => $v) {
    if ($v === null) unset($q[$k]);
    else             $q[$k] = $v;
  }
  return http_build_query($q);
}

// 5) Fetch categories
$cats = $mysqli->query("SELECT id, name FROM categories ORDER BY name");

// 6) Build WHERE + ORDER
$where = ['p.is_active = 1'];
$params = [];
$types = '';

if ($catId > 0) {
  $where[]   = 'p.category_id = ?';
  $types    .= 'i';
  $params[]  = $catId;
}
if ($search !== '') {
  $where[]   = 'p.name LIKE ?';
  $types    .= 's';
  $params[]  = "%{$search}%";
}
$orderBy = $sort === 'oldest'
  ? 'p.created_at ASC'
  : 'p.created_at DESC';

// 7) Query products
$sql = "
  SELECT p.id, p.name, p.price_usd, p.image, c.id AS cat_id
    FROM products p
    JOIN categories c ON c.id = p.category_id
   WHERE " . implode(' AND ', $where) . "
   ORDER BY {$orderBy}
";
$stmt = $mysqli->prepare($sql);
if ($params) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$prods = $stmt->get_result();
$stmt->close();
?>

<section style="margin-top: 60px;" class="bg0 p-t-23 p-b-10">
  <div class="container">

    <!-- Filter & Search Toggles -->
    <div class="flex-w flex-sb-m p-b-5">

      <!-- Category filters -->
      <div class="flex-w flex-l-m filter-tope-group m-tb-10">
        <a href="?<?= buildQuery(['cat_id' => null]) ?>"
          class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5 <?= $catId === 0 ? 'how-active1' : '' ?>">
          All
        </a>
        <?php while ($c = $cats->fetch_assoc()): ?>
          <a href="?<?= buildQuery(['cat_id' => $c['id']]) ?>"
            class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5 <?= $catId === $c['id'] ? 'how-active1' : '' ?>">
            <?= htmlspecialchars($c['name']) ?>
          </a>
        <?php endwhile; ?>
      </div>

      <!-- Toggle Buttons -->
      <div class="flex-w flex-c-m m-tb-10">
        <div class="flex-c-m stext-106 cl6 size-104 bor4 pointer hov-btn3 trans-04 m-r-8 m-tb-4 js-show-filter">
          <i class="icon-filter cl2 m-r-6 fs-15 zmdi zmdi-filter-list"></i>
          <i class="icon-close-filter cl2 m-r-6 fs-15 zmdi zmdi-close dis-none"></i>
          Sort
        </div>
        <div class="flex-c-m stext-106 cl6 size-105 bor4 pointer hov-btn3 trans-04 m-tb-4 js-show-search">
          <i class="icon-search cl2 m-r-6 fs-15 zmdi zmdi-search"></i>
          <i class="icon-close-search cl2 m-r-6 fs-15 zmdi zmdi-close dis-none"></i>
          Search
        </div>
      </div>

      <!-- Search input -->
      <div class="dis-none panel-search w-full p-t-10 p-b-15">
        <form method="get" class="bor8 dis-flex p-l-15">
          <button class="size-113 flex-c-m fs-16 cl2 hov-cl1 trans-04">
            <i class="zmdi zmdi-search"></i>
          </button>
          <input type="text"
            name="search"
            class="mtext-107 cl2 size-114 plh2 p-r-15"
            placeholder="Search Products"
            value="<?= htmlspecialchars($search) ?>">
          <!-- preserve other filters -->
          <?php if ($catId): ?><input type="hidden" name="cat_id" value="<?= $catId ?>"><?php endif; ?>
          <?php if ($sort): ?><input type="hidden" name="sort" value="<?= $sort  ?>"><?php endif; ?>
        </form>
      </div>

      <!-- Sort panel -->
      <div class="dis-none panel-filter w-full p-t-10">
        <div class="wrap-filter flex-w bg6 w-full p-lr-40 p-t-27 p-lr-15-sm">
          <div class="filter-col1 p-r-15 p-b-27">
            <div class="mtext-102 cl2 p-b-15">Sort By</div>
            <ul>
              <?php foreach (['newest' => 'Newest', 'oldest' => 'Oldest'] as $k => $label): ?>
                <li class="p-b-6">
                  <a href="?<?= buildQuery(['sort' => $k]) ?>"
                    class="filter-link stext-106 trans-04 <?= $sort === $k ? 'filter-link-active' : '' ?>">
                    <?= $label ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Product grid -->
    <div class="row isotope-grid">
      <?php while ($p = $prods->fetch_assoc()): ?>
        <div class="col-sm-6 col-md-4 col-lg-3 p-b-35 isotope-item cat-<?= $p['cat_id'] ?>">
          <div class="block2">
            <div class="block2-pic hov-img0">
              <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image']) ?>"
                alt="<?= htmlspecialchars($p['name']) ?>">
              <a href="<?= BASE_URL ?>/views/site/product-detail.php?id=<?= $p['id'] ?>"
                class="block2-btn flex-c-m stext-103 cl2 size-102 bg0 bor2 hov-btn1 p-lr-15 trans-04">
                Quick View
              </a>
            </div>
            <div class="block2-txt flex-w flex-t p-t-14">
              <div class="block2-txt-child1 flex-col-l">
                <a href="<?= BASE_URL ?>/views/site/product-detail.php?id=<?= $p['id'] ?>"
                  class="stext-104 cl4 hov-cl1 trans-04 js-name-b2 p-b-6">
                  <?= htmlspecialchars($p['name']) ?>
                </a>
                <span class="stext-105 cl3">
                  $<?= number_format($p['price_usd'], 2) ?>
                </span>
              </div>
              <div class="block2-txt-child2 flex-r p-t-3">
                <a href="#" class="btn-addwish-b2 dis-block pos-relative js-addwish-b2">
                  <img class="icon-heart1 dis-block trans-04"
                    src="<?= BASE_URL ?>/assets/images/icons/icon-heart-01.png" alt="">
                  <img class="icon-heart2 dis-block trans-04 ab-t-l"
                    src="<?= BASE_URL ?>/assets/images/icons/icon-heart-02.png" alt="">
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

    <!-- Load more -->
    <div class="flex-c-m flex-w w-full p-t-45">
      <a href="<?= BASE_URL ?>/views/site/product.php"
        class="flex-c-m stext-101 cl5 size-103 bg2 bor1 hov-btn1 p-lr-15 trans-04">
        Load More
      </a>
    </div>

  </div>
</section>

<!-- Isotope + toggle scripts stay exactly the same -->
<script>
  jQuery(function($) {
    var $grid = $('.isotope-grid').isotope({
      itemSelector: '.isotope-item',
      layoutMode: 'fitRows'
    });

    $('.js-show-filter').on('click', function() {
      $('.panel-filter').slideToggle(200);
      $(this).find('.icon-filter,.icon-close-filter').toggleClass('dis-none');
    });
    $('.js-show-search').on('click', function() {
      $('.panel-search').slideToggle(200);
      $(this).find('.icon-search,.icon-close-search').toggleClass('dis-none');
    });
  });
</script>

<?php
// 3) Shared footer include
include __DIR__ . '/../includes/footer.php';
?>