<?php
// views/includes/header.php

// 1) load DB + start session
require_once dirname(__DIR__, 2) . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// 2) auth helper (provides isLoggedIn(), getCurrentUser(), etc)
require_once dirname(__DIR__, 2) . '/config/auth.php';

// 3) enforce login on all pages that include this header
if (!isLoggedIn()) {
  header('Location: ' . BASE_URL . '/auth/login.php');
  exit;
}


// 2) alias for legacy code using $mysqli
$mysqli = $conn;

// 3) auth helper
require_once dirname(__DIR__, 2) . '/config/auth.php';

// 4) ensure BASE_URL
if (!defined('BASE_URL')) define('BASE_URL', '');

// helper to mark current menu item active
function isActive($path)
{
  return strpos($_SERVER['REQUEST_URI'], $path) !== false
    ? 'class="active-menu"'
    : '';
}

// ──────────────────────────────────────────────────────────────────────────
// build mini-cart data
// ──────────────────────────────────────────────────────────────────────────
$rawCart = $_SESSION['cart'] ?? [];

// group by product+size+color
$displayCart = [];
foreach ($rawCart as $item) {
  $pid   = $item['product_id'];
  $size  = $item['size']  ?: '--';
  $color = $item['color'] ?: '--';
  $key   = "{$pid}|{$size}|{$color}";

  if (!isset($displayCart[$key])) {
    $displayCart[$key] = [
      'key'         => $key,
      'product_id'  => $pid,
      'size'        => $item['size'],
      'color'       => $item['color'],
      'quantity'    => 0,
    ];
  }
  $displayCart[$key]['quantity'] += $item['quantity'];
}
$cartItems = array_values($displayCart);
$ids       = array_column($cartItems, 'product_id');

// fetch product details
$productsById = [];
if (count($ids)) {
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $conn->prepare("
      SELECT id, name, price_usd, image
        FROM products
       WHERE id IN ($placeholders)
    ");
  $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
  $stmt->execute();
  foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $p) {
    $productsById[$p['id']] = $p;
  }
  $stmt->close();
}

// badge count = sum of all quantities
$cartCount = array_sum(array_column($rawCart, 'quantity'));

// compute mini-cart total
$total = 0;
foreach ($cartItems as $item) {
  if (isset($productsById[$item['product_id']])) {
    $total += $productsById[$item['product_id']]['price_usd'] * $item['quantity'];
  }
}

// unread comments
$unreadComments = 0;
if (!empty($_SESSION['user']['id'])) {
  $uid = (int)$_SESSION['user']['id'];
  $row = $mysqli
    ->query("SELECT COUNT(*) FROM comments WHERE user_id = {$uid} AND is_read = 0")
    ->fetch_row();
  $unreadComments = (int)($row[0] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'Home') ?></title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/icons/favicon.png" />
  <!-- your existing CSS includes… -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fonts/font-awesome-4.7.0/css/font-awesome.min.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fonts/iconic/css/material-design-iconic-font.min.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/animate/animate.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/css-hamburgers/hamburgers.min.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/animsition/css/animsition.min.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/select2/select2.min.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/daterangepicker/daterangepicker.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/slick/slick.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/MagnificPopup/magnific-popup.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/perfect-scrollbar/perfect-scrollbar.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/util.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- in includes/header.php, for example -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
    rel="stylesheet" />

  <style>
    .container{
      width: 100%;
    }
    .cart-thumb {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 4px;
    }

    .position-relative {
      position: relative;
    }

    .position-absolute {
      position: absolute;
    }

    .badge {
      background: #e74c3c;
      color: #fff;
      border-radius: 999px;
      padding: .25em .5em;
      font-size: .65rem;
    }

    .top-0 {
      top: 0;
    }

    .start-100 {
      left: 100%;
    }

    .translate-middle {
      transform: translate(-50%, -50%);
    }



    .wrap-menu-desktop {
      position: fixed;
      background: transparent;
      /* initial transparent background */
      z-index: 1000;
      /* float above everything else */
      transition: background-color .3s, box-shadow .3s;
    }

    /* once you scroll down, we add a white bg + shadow */
    .wrap-menu-desktop.scrolled {
      background-color: #fff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    /* make sure the body (or your main wrapper) has enough top-padding
   so your fixed nav doesn’t cover content */
  </style>
</head>

<body class="<?= $bodyClass ?? 'animsition' ?>">

  <header>


    <!-- right side -->
    <div class="top-bar">
      <div class="content-topbar flex-sb-m h-full container">
        <div class="left-top-bar">
          Free shipping for orders over $100
        </div>

        <div class="right-top-bar flex-w h-full">
          <a href="<?= BASE_URL ?>/views/site/faq.php" class="flex-c-m trans-04 p-lr-25">
            Help &amp; FAQs
          </a>

          <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/views/site/profile.php" class="flex-c-m trans-04 p-lr-25">
              <i class="bi bi-person-circle"></i>
              <?= htmlspecialchars($_SESSION['user_username'] ?? $_SESSION['user_email']) ?>
            </a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="flex-c-m trans-04 p-lr-25">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a>
          <?php else: ?>
            <a href="<?= BASE_URL ?>/views/site/login.php" class="flex-c-m trans-04 p-lr-25">
              <i class="bi bi-box-arrow-in-right"></i> Login
            </a>
            <a href="<?= BASE_URL ?>/views/site/register.php" class="flex-c-m trans-04 p-lr-25">
              <i class="bi bi-pencil-square"></i> Register
            </a>
          <?php endif; ?>

          <a href="#" class="flex-c-m trans-04 p-lr-25">EN</a>
          <a href="#" class="flex-c-m trans-04 p-lr-25">USD</a>
        </div>
      </div>
    </div>
    </div>
    <!-- Main nav -->
    <div class="wrap-menu-desktop">
      <nav class="limiter-menu-desktop container">
        <a href="<?= BASE_URL ?>/index.php" class="logo">
          <img src="<?= BASE_URL ?>/assets/images/icons/logo-01.png" alt="Logo">
        </a>

        <div class="menu-desktop">
          <ul class="main-menu">
            <li <?= isActive('/views/site/index.php') ?>><a href="<?= BASE_URL ?>/views/site/index.php">Home</a></li>
            <li <?= isActive('/views/site/product.php') ?>>
              <a href="<?= BASE_URL ?>/views/site/product.php">Shop</a>
              <ul class="sub-menu">
                <?php
                $cats = $mysqli->query("SELECT id,name FROM categories ORDER BY name");
                while ($c = $cats->fetch_assoc()):
                ?>
                  <li>
                    <a href="<?= BASE_URL ?>/views/site/product.php?cat_id=<?= $c['id'] ?>">
                      <?= htmlspecialchars($c['name']) ?>
                    </a>
                  </li>
                <?php endwhile; ?>
              </ul>
            </li>
            <li <?= isActive('/views/site/blog.php') ?>><a href="<?= BASE_URL ?>/views/site/blog.php">Blog</a></li>
            <li <?= isActive('/views/site/about.php') ?>><a href="<?= BASE_URL ?>/views/site/about.php">About</a></li>
            <li <?= isActive('/views/site/contact.php') ?>><a href="<?= BASE_URL ?>/views/site/contact.php">Contact</a></li>
          </ul>
        </div>

        <div class="wrap-icon-header flex-w flex-r-m">
          <!-- Search -->
          <div class="icon-header-item js-show-modal-search p-l-22 p-r-11">
            <i class="zmdi zmdi-search"></i>
          </div>

          <!-- Cart (always shows “0” if empty) -->
          <div class="icon-header-item js-show-cart position-relative p-l-22 p-r-11">
            <i class="zmdi zmdi-shopping-cart"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge">
              <?= $cartCount ?>
            </span>
          </div>

          <!-- Comments -->
          <a href="<?= BASE_URL ?>/views/site/create-post.php"
            class="icon-header-item position-relative p-l-22 p-r-11 text-decoration-none">
            <i class="zmdi zmdi-comment"></i>
            <?php if ($unreadComments > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge bg-warning">
                <?= $unreadComments ?>
              </span>
            <?php endif; ?>
          </a>

        </div>
      </nav>
    </div>
    </div>

    <!-- Modal Search -->
    <div class="modal-search-header flex-c-m trans-04 js-hide-modal-search">
      <div class="container-search-header">
        <button type="button" class="flex-c-m btn-hide-modal-search trans-04 js-hide-modal-search">
          <img src="<?= BASE_URL ?>/assets/images/icons/icon-close2.png" alt="Close search">
        </button>
        <form action="<?= BASE_URL ?>/views/site/search.php" method="get" class="wrap-search-header flex-w p-l-15">
          <button type="submit" class="flex-c-m trans-04"><i class="zmdi zmdi-search"></i></button>
          <input class="plh3" type="text" name="q" placeholder="Search products…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        </form>
      </div>
    </div>

    <!-- Mini-Cart -->
    <!-- Mini-Cart -->
    <div class="wrap-header-cart js-panel-cart">
      <div class="s-full js-hide-cart"></div>
      <div class="header-cart flex-col-l p-l-65 p-r-25">
        <div class="header-cart-title flex-w flex-sb-m p-b-8">
          <span class="mtext-103 cl2">Your Cart</span>
          <div class="fs-35 lh-10 cl2 p-lr-5 pointer hov-cl1 trans-04 js-hide-cart">
            <i class="zmdi zmdi-close"></i>
          </div>
        </div>

        <div class="header-cart-content flex-w js-pscroll">
          <ul class="header-cart-wrapitem w-full">
            <?php if (empty($cartItems)): ?>
              <li class="p-tb-20 txt-center w-full">Your cart is empty.</li>
            <?php else: ?>
              <?php foreach ($cartItems as $item):
                $p      = $productsById[$item['product_id']] ?? null;
                if (!$p) continue;
              ?>
                <li
                  class="header-cart-item flex-w flex-t m-b-12"
                  data-key="<?= htmlspecialchars($item['key']) ?>">
                  <div class="header-cart-item-img">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['image']) ?>"
                      class="cart-thumb"
                      alt="<?= htmlspecialchars($p['name']) ?>">
                  </div>
                  <div class="header-cart-item-txt p-t-8 flex-grow-1">
                    <a href="<?= BASE_URL ?>/views/site/product-detail.php?id=<?= $p['id'] ?>"
                      class="header-cart-item-name m-b-6 hov-cl1 trans-04">
                      <?= htmlspecialchars($p['name']) ?>
                    </a>
                    <small class="d-block stext-102 cl6">
                      Size: <?= htmlspecialchars($item['size'] ?: '--') ?>,
                      Color: <?= htmlspecialchars($item['color'] ?: '--') ?>
                    </small>
                    <span class="header-cart-item-info">
                      <?= $item['quantity'] ?> × $<?= number_format($p['price_usd'], 2) ?>
                    </span>
                  </div>
                  <button type="button"
                    class="btn btn-sm btn-link text-danger p-0 js-remove"
                    title="Remove">&times;
                  </button>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>

          <?php if (!empty($cartItems)): ?>
            <div class="header-cart-total w-full p-tb-40">
              Total: $<span id="cartTotal"><?= number_format($total, 2) ?></span>
            </div>
            <div class="header-cart-buttons flex-w w-full">
              <a href="<?= BASE_URL ?>/views/site/shoping-cart.php"
                class="flex-c-m stext-101 cl0 size-107 bg3 bor2 hov-btn3
                        p-lr-15 trans-04 m-r-8 m-b-10">
                View Cart
              </a>
              <a href="<?= BASE_URL ?>/views/site/checkout.php"
                class="flex-c-m stext-101 cl0 size-107 bg3 bor2 hov-btn3
                        p-lr-15 trans-04 m-b-10">
                Check Out
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <script>
      const REMOVE_URL = '<?= BASE_URL ?>/views/site/cart/remove.php';

      document.querySelectorAll('.js-remove').forEach(btn => {
        btn.addEventListener('click', e => {
          const li = btn.closest('li.header-cart-item');
          const key = li.dataset.key;
          if (!key) return;

          fetch(REMOVE_URL, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: new URLSearchParams({
                key
              })
            })
            .then(r => r.json())
            .then(json => {
              if (!json.success) {
                return alert(json.message || 'Could not remove item');
              }
              // 1) remove the item row
              li.remove();

              // 2) update the total
              document.getElementById('cartTotal').textContent = json.new_total;

              // 3) if cart is empty, show empty state
              if (json.count === 0) {
                document.querySelector('.header-cart-wrapitem')
                  .innerHTML = '<li class="p-tb-20 txt-center w-full">Your cart is empty.</li>';
                document.querySelector('.header-cart-total')?.remove();
                document.querySelector('.header-cart-buttons')?.remove();

                // … after li.remove() …
                window.location.reload();

              }
            })
            .catch(() => alert('Network error, please try again.'));
        });
      });
    </script>
    <!-- somewhere after your </nav> but before </body> -->
    <script>
      const menu = document.querySelector('.wrap-menu-desktop');
      window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
          menu.classList.add('scrolled');
        } else {
          menu.classList.remove('scrolled');
        }
      });
    </script>

    <style>
      /* ------------------
   [ 1. Core & Body ]
   ------------------ */
      body.dark-mode {
        background-color: #18191a !important;
        color: #a9a9a9ff;
      }

      /* ------------------
   [ 2. Headers & Nav ]
   ------------------ */
      .dark-mode .top-bar {
        background-color: #3b3737ff;
        border-bottom: 1px solid #333;
      }

      .dark-mode .wrap-menu-desktop.scrolled {
        background-color: #242526;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      }

      .dark-mode .main-menu>li>a {
        color: #525454ff;
      }

      .dark-mode .main-menu>li>a:hover {
        color: #003362ff;
        /* Or your theme's primary highlight color */
      }

      .dark-mode .logo img {
        color: #525454ff;
        /* Invert logo colors or switch to a dark-mode version */
        filter: brightness(0) invert(1);
      }

      /* ------------------
   [ 3. Text & Links ]
   ------------------ */
      .dark-mode a {
        color: #ffffffff;
        /* A more readable link color for dark backgrounds */
      }

      .dark-mode a:hover {
        color: #a2c3fa;
      }

      /* Override your theme's specific text color classes */
      .dark-mode .cl2,
      .dark-mode .mtext-103 {
        color: #e4e6eb !important;
      }

      .dark-mode .stext-102,
      .dark-mode .cl6 {
        color: #ffffffff !important;
        /* Muted text */
      }

      /* ------------------
   [ 4. Components: Cards, Modals, Forms ]
   ------------------ */
      .dark-mode .card,
      .dark-mode .modal-content,
      .dark-mode .header-cart,
      .dark-mode .modal-search-header {
        background-color: #525454ff;
        color: #e4e6eb;
        border: 1px solid #333;
      }

      .dark-mode .card-header,
      .dark-mode .header-cart-title {
        background-color: #50555aff;
        border-bottom: 1px solid #333;
      }

      /* Forms */
      .dark-mode .form-control,
      .dark-mode .form-select {
        background-color: #6c7279ff;
        color: #413f3fff;
        border: 1px solid #555;
      }

      .dark-mode .form-control:focus {
        background-color: #3a3b3c;
        color: #e4e6eb;
        border-color: #8ab4f8;
        box-shadow: none;
      }

      .dark-mode .form-control::placeholder,
      .dark-mode .plh3::placeholder {
        color: #000000ff;
      }

      /* Buttons */
      .dark-mode .bg3.hov-btn3:hover {
        background-color: #222323ff;
        /* Darker hover for buttons */
      }

      /* List Groups & Tables */
      .dark-mode .list-group-item {
        background-color: #242526;
        border-color: #333;
      }

      .dark-mode .table {
        color: #e4e6eb;
      }

      .dark-mode .table-hover tbody tr:hover {
        color: #e4e6eb;
        background-color: rgba(255, 255, 255, 0.075);
      }

      /* Badges */
      .dark-mode .badge.bg-secondary {
        background-color: #555 !important;
        color: #fff !important;
      }
    </style>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const root = document.documentElement;
        const darkToggle = document.getElementById('darkModeToggle');
        const darkModeStylesheet = document.getElementById('dark-mode-stylesheet');
        const langSelect = document.getElementById('languageSelect');

        // --- Dark Mode Logic ---
        const enableDarkMode = () => {
          root.classList.add('dark-mode');
          darkModeStylesheet.disabled = false;
          if (darkToggle) darkToggle.checked = true;
          localStorage.setItem('darkMode', 'enabled');
        };

        const disableDarkMode = () => {
          root.classList.remove('dark-mode');
          darkModeStylesheet.disabled = true;
          if (darkToggle) darkToggle.checked = false;
          localStorage.setItem('darkMode', 'disabled');
        };

        // Check for saved preference on page load
        if (localStorage.getItem('darkMode') === 'enabled') {
          enableDarkMode();
        }

        // Add event listener to the toggle (if it exists on the page)
        if (darkToggle) {
          darkToggle.addEventListener('change', () => {
            if (darkToggle.checked) {
              enableDarkMode();
            } else {
              disableDarkMode();
            }
          });
        }


        // --- Language Persistence Logic (no changes needed here) ---
        if (langSelect) {
          const savedLang = localStorage.getItem('lang') || navigator.language.split('-')[0];
          if ([...langSelect.options].some(o => o.value === savedLang)) {
            langSelect.value = savedLang;
            document.documentElement.lang = savedLang;
          }
          langSelect.addEventListener('change', () => {
            document.documentElement.lang = langSelect.value;
            localStorage.setItem('lang', langSelect.value);
          });
        }
      });
    </script>


  </header>