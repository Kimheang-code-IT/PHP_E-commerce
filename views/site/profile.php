<?php
// views/site/profile.php

require_once dirname(__DIR__, 2) . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/config/auth.php';

// 1) enforce login
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// 2) fetch full user info with additional profile data
// 2) fetch full user info with additional profile data
$uid = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT 
        id,
        username, 
        email, 
        created_at,
        NULL as first_name,  -- These fields don't exist in your schema
        NULL as last_name,    -- So we set them to NULL
        NULL as phone,
        NULL as avatar,
        NULL as bio,
        NULL as location
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param('s', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 3) fetch this user's orders with more details
// 3) fetch this user's orders with more details
$stmt = $conn->prepare("
    SELECT 
        o.id,
        o.total_usd,
        o.status,
        o.created_at,
        COUNT(oi.id) AS item_count,
        GROUP_CONCAT(DISTINCT p.name ORDER BY oi.id SEPARATOR ', ') AS product_names
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->bind_param('s', $uid);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();



$pageTitle = 'My Profile';
include dirname(__DIR__) . '/includes/header.php';
?>

<div style="margin-top: 80px;" class="main">
    <!-- Profile Header with Hero Section -->
    <div class="profile-hero bg-gradient-primary text-white pb-5">
        <div class="container">
            <div class="row align-items-center py-4">
                <div class="col-md-auto text-center text-md-start">
                    <div class="avatar-upload position-relative d-inline-block">
                        <div class="avatar-preview rounded-circle overflow-hidden" style="width: 120px; height: 120px; background-color: rgba(255,255,255,0.1);">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i class="bi bi-person-fill display-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md mt-4 mt-md-0">
                    <h1 class="display-5 fw-bold mb-2">
                        <?= htmlspecialchars($user['username']) ?>
                    </h1>
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <div class="d-flex align-items-center">
                            <i  class="bi bi-envelope-fill me-5"></i>
                            <?= htmlspecialchars($user['email']) ?>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-light text-dark rounded-pill">
                            <i class="bi bi-star-fill text-warning me-10"></i>
                            Member since <?= date('F Y', strtotime($user['created_at'])) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row g-4">
            <!-- Left Sidebar -->
            <div class="col-lg-3">
                <div class="card shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush rounded">
                            <a href="#account" class="list-group-item list-group-item-action active" data-bs-toggle="pill">
                                <i class="bi bi-person-fill me-2"></i> Profile Overview
                            </a>
                            <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                                <i class="bi bi-bag-fill me-2"></i> My Orders
                                <span class="badge bg-primary rounded-pill float-end"><?= count($orders) ?></span>
                            </a>
                            <a href="#wishlist" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                                <i class="bi bi-heart-fill me-2"></i> Wishlist
                                <span class="badge bg-primary rounded-pill float-end">0</span>
                            </a>
                            <a href="#settings" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                                <i class="bi bi-gear-fill me-2"></i> Account Settings
                            </a>
                            <a href="<?= BASE_URL ?>/auth/logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0">Your Stats</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <i class="bi bi-bag-check-fill text-primary me-2"></i>
                                <span class="text-muted">Orders</span>
                            </div>
                            <span class="fw-bold"><?= count($orders) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <i class="bi bi-star-fill text-warning me-2"></i>
                                <span class="text-muted">Reviews</span>
                            </div>
                            <span class="fw-bold">5</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-heart-fill text-danger me-2"></i>
                                <span class="text-muted">Wishlist</span>
                            </div>
                            <span class="fw-bold">12</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Content Area -->
            <div class="col-lg-9">
                <div class="tab-content">
                    <!-- Profile Overview Tab -->
                    <div class="tab-pane fade show active" id="account">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Profile Information</h5>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    <i class="bi bi-pencil-fill me-1"></i> Edit
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <h6 class="text-muted mb-3">Account Details</h6>
                                        <div class="mb-3">
                                            <div class="text-muted small">Username</div>
                                            <div class="fw-bold"><?= htmlspecialchars($user['username']) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <h6 class="text-muted mb-3">Contact Information</h6>
                                        <div class="mb-3">
                                            <div class="text-muted small">Email Address</div>
                                            <div class="fw-bold"><?= htmlspecialchars($user['email']) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <div class="text-muted small">Member Since</div>
                                    <div class="fw-bold"><?= date('F j, Y', strtotime($user['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-badge bg-primary">
                                            <i class="bi bi-bag-check"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">Order #3425 placed</h6>
                                            <p class="text-muted small mb-1">2 days ago</p>
                                            <p class="small">Your order has been confirmed and is being processed</p>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-badge bg-success">
                                            <i class="bi bi-star-fill"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">Left a review</h6>
                                            <p class="text-muted small mb-1">1 week ago</p>
                                            <p class="small">You reviewed "Premium Wireless Headphones"</p>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-badge bg-info">
                                            <i class="bi bi-heart-fill"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">Added to wishlist</h6>
                                            <p class="text-muted small mb-1">2 weeks ago</p>
                                            <p class="small">"Smart Watch Pro" was added to your wishlist</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Tab -->
                    <div class="tab-pane fade" id="orders">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Order History</h5>
                                    <div>
                                        <button class="btn btn-sm btn-outline-secondary me-2">
                                            <i class="bi bi-download me-1"></i> Export
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-funnel-fill me-1"></i> Filter
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($orders)): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-bag-x display-4 text-muted mb-3"></i>
                                        <h5 class="mb-3">No orders yet</h5>
                                        <p class="text-muted mb-4">You haven't placed any orders with us yet.</p>
                                        <button type="button"
                                            class="btn btn-secondary"
                                            onclick="window.location='<?= BASE_URL ?>/views/site/product.php'">
                                            <i class="bi bi-arrow-repeat me-2"></i>Continue Shopping
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Date</th>
                                                    <th>Items</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td class="fw-bold">#<?= $order['id'] ?></td>
                                                        <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-shrink-0 me-2">
                                                                    <span class="badge bg-light text-dark rounded-pill"><?= $order['item_count'] ?></span>
                                                                </div>
                                                                <div class="flex-grow-1 text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($order['product_names']) ?>">
                                                                    <?= htmlspecialchars($order['product_names']) ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="fw-bold">$<?= number_format($order['total_usd'], 2) ?></td>
                                                        <td>
                                                            <?php
                                                            // Change this in your orders table row:
                                                            $statusClass = [
                                                                'pending' => 'warning',
                                                                'paid' => 'info',
                                                                'shipped' => 'primary',
                                                                'completed' => 'success',
                                                                'cancelled' => 'danger'
                                                            ][strtolower($order['status'])] ?? 'secondary';
                                                            ?>
                                                            <span class="badge bg-<?= $statusClass ?> rounded-pill">
                                                                <?= ucfirst($order['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            <a href="<?= BASE_URL ?>/order-details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-eye-fill me-1"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="text-muted small">
                                            Showing 1 to <?= min(10, count($orders)) ?> of <?= count($orders) ?> orders
                                        </div>
                                        <nav>
                                            <ul class="pagination pagination-sm mb-0">
                                                <li class="page-item disabled">
                                                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                                                </li>
                                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                                <li class="page-item">
                                                    <a class="page-link" href="#">Next</a>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Addresses Tab -->


                    <!-- Wishlist Tab -->
                    <div class="tab-pane fade" id="wishlist">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Your Wishlist</h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary me-2">
                                        <i class="bi bi-share-fill me-1"></i> Share
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash-fill me-1"></i> Clear All
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="text-center py-5">
                                    <i class="bi bi-heart display-4 text-muted mb-3"></i>
                                    <h5 class="mb-3">Your wishlist is empty</h5>
                                    <p class="text-muted mb-4">Save items you love for easy access later.</p>
                                    <button type="button"
                                        class="btn btn-secondary"
                                        onclick="window.location='<?= BASE_URL ?>/views/site/product.php'">
                                        <i class="bi bi-arrow-repeat me-2"></i>Continue Shopping
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div class="tab-pane fade" id="settings">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Account Settings</h5>
                            </div>
                            <div class="card-body">
                                <form id="settingsForm">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="card h-100 border-0 shadow-sm">
                                                <div class="card-body">
                                                    <h6 class="mb-3">Personal Information</h6>
                                                    <div class="mb-3">
                                                        <label for="username" class="form-label">Username</label>
                                                        <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Email Address</label>
                                                        <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($user['email']) ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="phone" class="form-label">Phone Number</label>
                                                        <input type="tel" class="form-control" id="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card h-100 border-0 shadow-sm">
                                                <div class="card-body">
                                                    <h6 class="mb-3">Preferences</h6>
                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" type="checkbox" id="darkModeToggle">
                                                        <label class="form-check-label" for="darkModeToggle">Dark Mode</label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="languageSelect" class="form-label">Language</label>
                                                        <select id="languageSelect" class="form-select">
                                                            <option value="en">English</option>
                                                            <option value="km">Khmer (ភាសាខ្មែរ)</option>
                                                            <option value="zh">Chinese (中文)</option>
                                                            <option value="es">Spanish (Español)</option>
                                                            <option value="fr">French (Français)</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="currencySelect" class="form-label">Currency</label>
                                                        <select id="currencySelect" class="form-select">
                                                            <option value="USD">US Dollar (USD)</option>
                                                            <option value="EUR">Euro (EUR)</option>
                                                            <option value="KHR">Cambodian Riel (KHR)</option>
                                                            <option value="CNY">Chinese Yuan (CNY)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-body">
                                                    <h6 class="mb-3">Security</h6>
                                                    <div class="alert alert-warning">
                                                        <i class="bi bi-shield-lock-fill me-2"></i>
                                                        Last password change: 3 months ago
                                                        <a href="#changePassword" class="alert-link float-end" data-bs-toggle="modal">Change Password</a>
                                                    </div>
                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" type="checkbox" id="twoFactorToggle" checked>
                                                        <label class="form-check-label" for="twoFactorToggle">Two-Factor Authentication</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="button" class="btn btn-outline-secondary me-3">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="profileForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="editEmail" value="<?= htmlspecialchars($user['email']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="editUsername" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                            <div class="form-text">Username cannot be changed</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePassword" tabindex="-1" aria-labelledby="changePasswordLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="passwordForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" required>
                            <div class="form-text">Minimum 8 characters with at least one number and one letter</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAddressModalLabel">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addAddressForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="addressType" class="form-label">Address Type</label>
                            <select class="form-select" id="addressType">
                                <option value="home">Home</option>
                                <option value="work">Work</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="addressLine1" class="form-label">Address Line 1</label>
                            <input type="text" class="form-control" id="addressLine1" required>
                        </div>
                        <div class="mb-3">
                            <label for="addressLine2" class="form-label">Address Line 2 (Optional)</label>
                            <input type="text" class="form-control" id="addressLine2">
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="state" required>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label for="postalCode" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postalCode" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <select class="form-select" id="country" required>
                                    <option value="Cambodia">Cambodia</option>
                                    <option value="United States">United States</option>
                                    <option value="China">China</option>
                                    <option value="France">France</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="setAsDefault">
                            <label class="form-check-label" for="setAsDefault">Set as default shipping address</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Address Modal -->
    <div class="modal fade" id="editAddressModal" tabindex="-1" aria-labelledby="editAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAddressModalLabel">Edit Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editAddressForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editAddressType" class="form-label">Address Type</label>
                            <select class="form-select" id="editAddressType">
                                <option value="home">Home</option>
                                <option value="work">Work</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editAddressLine1" class="form-label">Address Line 1</label>
                            <input type="text" class="form-control" id="editAddressLine1" required>
                        </div>
                        <div class="mb-3">
                            <label for="editAddressLine2" class="form-label">Address Line 2 (Optional)</label>
                            <input type="text" class="form-control" id="editAddressLine2">
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label for="editCity" class="form-label">City</label>
                                <input type="text" class="form-control" id="editCity" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editState" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="editState" required>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label for="editPostalCode" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="editPostalCode" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editCountry" class="form-label">Country</label>
                                <select class="form-select" id="editCountry" required>
                                    <option value="Cambodia">Cambodia</option>
                                    <option value="United States">United States</option>
                                    <option value="China">China</option>
                                    <option value="France">France</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="editSetAsDefault">
                            <label class="form-check-label" for="editSetAsDefault">Set as default shipping address</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Avatar Upload Modal -->
    <div class="modal fade" id="avatarModal" tabindex="-1" aria-labelledby="avatarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="avatarModalLabel">Update Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="avatarForm">
                    <div class="modal-body text-center">
                        <div class="avatar-upload-preview mb-4 mx-auto">
                            <div class="avatar-preview rounded-circle overflow-hidden mx-auto" style="width: 150px; height: 150px; background-color: #f8f9fa;">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Current profile picture" class="img-fluid h-100 w-100 object-fit-cover" id="avatarPreview">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100">
                                        <i class="bi bi-person-fill display-3 text-muted" id="avatarPreviewIcon"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control" id="avatarUpload" accept="image/*">
                            <div class="form-text">JPG, GIF or PNG. Max size 2MB</div>
                        </div>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-outline-danger" id="removeAvatarBtn">
                                <i class="bi bi-trash-fill me-1"></i> Remove
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="cropAvatarBtn" disabled>
                                <i class="bi bi-crop me-1"></i> Crop
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<style>
    /* Profile Hero Section */
    .profile-hero {
        background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
        border-radius: 0 0 20px 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    /* Avatar Upload */
    .avatar-upload {
        transition: all 0.3s ease;
    }

    .avatar-upload:hover {
        transform: scale(1.05);
    }

    /* Sidebar Navigation */
    .list-group-item.active {
        background-color: #4e54c8;
        border-color: #4e54c8;
    }

    /* Timeline for Recent Activity */
    .timeline {
        position: relative;
        padding-left: 50px;
    }

    .timeline:before {
        content: '';
        position: absolute;
        left: 20px;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #e9ecef;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }

    .timeline-badge {
        position: absolute;
        left: -50px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        box-shadow: 0 0 0 4px white;
    }

    .timeline-content {
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 6px;
    }

    /* Status Badges */
    .badge.bg-warning {
        background-color: #ffc107 !important;
    }

    .badge.bg-info {
        background-color: #0dcaf0 !important;
    }

    .badge.bg-primary {
        background-color: #4e54c8 !important;
    }

    .badge.bg-success {
        background-color: #198754 !important;
    }

    .badge.bg-danger {
        background-color: #dc3545 !important;
    }

    /* Card Hover Effects */
    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }

    /* Dark Mode Support */
    .dark-mode .profile-hero {
        background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
    }

    .dark-mode .card {
        background-color: #1e1e1e;
        border-color: #2d2d2d;
    }

    .dark-mode .card-header {
        background-color: #252525;
        border-color: #2d2d2d;
    }

    .dark-mode .list-group-item {
        background-color: #1e1e1e;
        border-color: #2d2d2d;
        color: #e0e0e0;
    }

    .dark-mode .list-group-item.active {
        background-color: #4e54c8;
    }

    .dark-mode .timeline-content {
        background-color: #252525;
        color: #e0e0e0;
    }

    .dark-mode .timeline:before {
        background-color: #2d2d2d;
    }

    .dark-mode .table {
        color: #e0e0e0;
    }

    .dark-mode .table thead th {
        background-color: #252525;
        border-color: #2d2d2d;
    }

    .dark-mode .table tbody tr {
        background-color: #1e1e1e;
    }

    .dark-mode .table-hover tbody tr:hover {
        background-color: #252525;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dark Mode Toggle
        const darkToggle = document.getElementById('darkModeToggle');
        const root = document.documentElement;

        if (localStorage.getItem('darkMode') === 'enabled') {
            root.classList.add('dark-mode');
            darkToggle.checked = true;
        }

        darkToggle.addEventListener('change', function() {
            if (this.checked) {
                root.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                root.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });

        // Language Selector
        const langSelect = document.getElementById('languageSelect');
        const savedLang = localStorage.getItem('lang') || navigator.language.split('-')[0];

        if ([...langSelect.options].some(o => o.value === savedLang)) {
            langSelect.value = savedLang;
            document.documentElement.lang = savedLang;
        }

        langSelect.addEventListener('change', function() {
            document.documentElement.lang = this.value;
            localStorage.setItem('lang', this.value);
        });

        // Avatar Upload Preview
        const avatarUpload = document.getElementById('avatarUpload');
        const avatarPreview = document.getElementById('avatarPreview');
        const avatarPreviewIcon = document.getElementById('avatarPreviewIcon');

        avatarUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    if (avatarPreview) {
                        avatarPreview.src = event.target.result;
                    } else {
                        avatarPreviewIcon.style.display = 'none';
                        const img = document.createElement('img');
                        img.src = event.target.result;
                        img.className = 'img-fluid h-100 w-100 object-fit-cover';
                        img.id = 'avatarPreview';
                        avatarPreviewIcon.parentElement.appendChild(img);
                    }
                    document.getElementById('cropAvatarBtn').disabled = false;
                };
                reader.readAsDataURL(file);
            }
        });

        // Remove Avatar
        document.getElementById('removeAvatarBtn').addEventListener('click', function() {
            if (avatarPreview) {
                avatarPreview.remove();
            }
            avatarPreviewIcon.style.display = 'flex';
            avatarUpload.value = '';
            document.getElementById('cropAvatarBtn').disabled = true;
        });

        // Form Submissions (simulated)
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                // Simulate form submission
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...';
                submitBtn.disabled = true;

                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;

                    // Show success toast
                    const toast = new bootstrap.Toast(document.getElementById('successToast'));
                    toast.show();

                    // Close modal if this is a modal form
                    const modal = this.closest('.modal');
                    if (modal) {
                        bootstrap.Modal.getInstance(modal).hide();
                    }
                }, 1500);
            });
        });

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>