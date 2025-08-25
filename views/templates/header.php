<?php
// C:\xampp\htdocs\Barcode\views\templates\header.php

// 1) Start session + protect pages (except login.php)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
$self = basename($_SERVER['PHP_SELF']);
if ($self !== 'login.php' && empty($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . '/auth/login.php');
  exit;
}

// 2) Grab user info
$username = $_SESSION['username'] ?? 'Guest';

// 3) Navigation items (no logout here)
$navItems = [
  'dashboard.php'       => ['bi-speedometer2', 'Dashboard'],
  'category.php'        => ['bi-tags',        'Category'],
  'product.php'         => ['bi-box-seam',    'Product'],
  'pos.php'             => ['bi-cart4',       'POS'],
  'orderlist.php'       => ['bi-list',        'OrderList'],
  'sales_report.php'    => ['bi-bar-chart',   'Sales Report'],
  'tax.php'             => ['bi-percent',     'Tax'],
  
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($pageTitle ?? 'POS Barcode') ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    html,
    body {
      height: 100%;
      margin: 0;
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      width: 220px;
      height: 100%;
      background: #2c3e50;
      color: #ecf0f1;
      display: flex;
      flex-direction: column;
      transition: transform 0.3s ease;
      z-index: 1001;
    }

    @media (max-width: 991.98px) {
      .sidebar {
        transform: translateX(-220px);
      }

      .sidebar.active {
        transform: translateX(0);
      }
    }

    .sidebar-header {
      flex: 0 0 auto;
      display: flex;
      align-items: center;
      padding: .6rem;
      border-bottom: 1px solid rgb(255, 255, 255);
      border-right: 1px solid rgb(255, 255, 255);
    }

    .sidebar-header i {
      font-size: 1.5rem;
      margin-right: .5rem;
      padding-left: 25px;
    }

    .sidebar-header h4 {
      margin: 5px;
      font-size: 1rem;
    }

    .user {
      flex: 0 0 auto;
      display: flex;
      align-items: center;
      padding: .6rem;
      margin-top: 10px;
      margin-bottom: 10px;
    }

    .user-avatar-icon {
      font-size: 2.5rem;
      /* size of the icon */
      color: #6c757d;
      margin-top: -10px;
      margin-bottom: -10px;
      /* a gray tone—you can pick any color */
    }



    .user p {
      margin: 0;
      font-size: .9rem;
    }

    .bar {
      flex: 1 1 auto;
      overflow-y: auto;
    }

    .bar .nav-link {
      color: #bdc3c7;
      padding: .6rem 1rem;
      display: flex;
      align-items: center;
    }

    .bar .nav-link i {
      margin-right: .5rem;
    }

    .bar .nav-link.active,
    .bar .nav-link:hover {
      background: #34495e;
      color: #ecf0f1;
      text-decoration: none;
    }

    /* Topbar */
    header.topbar {
      position: fixed;
      top: 0;
      left: 220px;
      right: 0;
      height: 56px;
      background: #2c3e50;
      box-shadow: #2c3e50;
      display: flex;
      flex-direction: row;
      justify-content: space-between;
      align-items: center;
      padding: 0 1rem;
      z-index: 1000;
      transition: left 0.3s ease;
    }

    @media (max-width: 991.98px) {
      header.topbar {
        left: 0;
      }
    }

    .right {
      display: flex;
      flex-direction: row;
    }

    /* search at left */
    .topbar .search-box .input-group {
      height: 20px;
      width: 350px;
      margin-bottom: 10px;
    }

    @media (max-width: 767.98px) {
      .topbar .search-box .input-group {
        width: 100%;
        max-width: 250px;
      }
    }

    @media (max-width: 575.98px) {
      .topbar .search-box .input-group {
        max-width: 200px;
      }
    }

    /* logout at bottom right */
    .logout-btn,
    .Messages,
    .Notifications {
      padding: 0 10px !important;
    }

    
    /* Main content: scrollable */
    .main-content {
      position: absolute;
      top: 56px;
      /* height of your topbar */
      left: 220px;
      /* width of your sidebar */
      right: 0;
      bottom: 0;
      padding: 0;
      background: #ecf0f1;
      overflow-y: auto;
      /* enable vertical scrolling */
      -webkit-overflow-scrolling: touch;
      /* smooth scrolling on iOS */
      transition: left 0.3s ease;
    }

    @media (max-width: 991.98px) {
      .main-content {
        left: 0;
      }
    }


    /* Overlay for mobile sidebar */
    .overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
    }

    @media (max-width: 991.98px) {
      .overlay.active {
        display: block;
      }
    }

    /* Responsive right header for mobile */
    @media (max-width: 991.98px) {

      .Messages button,
      .Notifications button,
      .logout-btn a {
        padding: 0 5px !important;
        font-size: 0;
        /* Hide text */
      }

      .Messages button i,
      .Notifications button i,
      .logout-btn a i {
        font-size: 1rem;
        /* Keep icon size */
        margin-right: 0;
        /* Remove margin for tighter spacing */
      }

      .Messages button .badge,
      .Notifications button .badge {
        position: relative;
        top: -10px;
        left: -5px;
        font-size: 0.6rem;
      }

      .Messages button::before,
      .Notifications button::before,
      .logout-btn a::before {
        content: none;
        /* Remove text content */
      }

      .Messages button .bi-envelope,
      .Notifications button .bi-bell,
      .logout-btn a .bi-box-arrow-right {
        display: inline-block;
        /* Ensure icons are visible */
      }
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

  <!-- SIDEBAR -->
  <nav class="sidebar">
    <div class="sidebar-header">
      <i class="bi-upc-scan text-white"></i>
      <h4>POS BARCODE</h4>
    </div>
    <div class="user d-flex align-items-center">
      <!-- replace <img> with a Bootstrap person-circle icon -->
      <i class="bi bi-person-circle user-avatar-icon me-2"></i>
      <p style="font-size:20px; color: #ecf0f1;" class="mb-0"><?= htmlspecialchars($username) ?></p>
    </div>
    <div class="bar">
      <ul class="nav flex-column">
        <?php foreach ($navItems as $file => [$icon, $label]):
          $active = ($self === $file) ? 'active' : '';
        ?>
          <li class="nav-item border-bottom border-dark">
            <a href="<?= BASE_URL ?>/views/<?= $file ?>"
              class="nav-link <?= $active ?>">
              <i class="<?= $icon ?> text-white"></i>
              <span><?= $label ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="sidebar-footer text-center text-white" style="font-size:.8rem; ">
      © <?= date('Y') ?> Grounp G6 AUB BATCH 08
  </nav>

  <!-- OVERLAY FOR MOBILE -->
  <div class="overlay" id="overlay"></div>

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="left d-flex align-items-center">
      <!-- Hamburger menu for mobile -->
      <button class="btn btn-outline-light d-lg-none me-2" id="sidebarToggle">
        <i class="bi-list"></i>
      </button>
      <div class="search-box">
        <div class="input-group input-group-sm">
          <input type="search" class="form-control" placeholder="Search…">
          <button class="btn btn-outline-secondary"><i class="bi-search"></i></button>
        </div>
      </div>
    </div>
    <div class="right d-flex align-items-center">
      <!-- Messages -->
      <div class="Messages">
        <button class="btn btn-sm btn-outline-light" title="Messages">
          <i class="bi-envelope"></i> 
          <span class="badge bg-danger">3</span>
        </button>
      </div>
      <!-- Notifications -->
      <div class="Notifications">
        <button class="btn btn-sm btn-outline-light" title="Notifications">
          <i class="bi-bell"></i> 
          <span class="badge bg-danger">3</span>
        </button>
      </div>
      <div class="logout-btn">
        <a href="<?= BASE_URL ?>/auth/logout.php"
          class="btn btn-sm btn-outline-light">
          <i class="bi-box-arrow-right"></i> Logout
        </a>
      </div>
    </div>
  </header>

  <!-- MAIN CONTENT START -->
  <div class="main-content">