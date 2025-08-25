<?php
// C:\xampp\htdocs\coffee-shop\config\auth.php

// 1) Start session (if not already)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2) Pull in DB config (defines $conn and BASE_URL)
require_once __DIR__ . '/db.php';

/**
 * flash(): Set or retrieve flash messages for session-based notifications
 * @param string $key The flash message key (e.g., 'success', 'error')
 * @param string|null $msg The message to set; if null, retrieve and clear
 * @param bool $htmlSafe Whether to escape output for HTML safety
 * @return string|null The retrieved message or null if setting
 */
function flash(string $key, string $msg = null, bool $htmlSafe = true): ?string {
    if ($msg === null) {
        $message = $_SESSION['flash'][$key] ?? '';
        unset($_SESSION['flash'][$key]);
        return $htmlSafe ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : $message;
    }
    $_SESSION['flash'][$key] = $msg;
    return null;
}

/**
 * protect(): Redirect to login if user is not authenticated or lacks required role
 * @param array $allowedRoles Array of allowed role names (e.g., ['pos_user', 'admin'])
 * @return bool True if access is granted, otherwise redirects and exits
 */
function protect(array $allowedRoles = []): bool {
    if (empty($_SESSION['user_id'])) {
        flash('error', 'Please log in to access this page.');
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }

    if (!empty($allowedRoles)) {
        $userRole = $_SESSION['user_role'] ?? '';
        if (!in_array($userRole, $allowedRoles, true)) {
            flash('error', 'You do not have permission to access this page.');
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
    return true;
}

/**
 * generateCsrfToken(): Generate and store a CSRF token in session
 * @return string The generated CSRF token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            error_log("CSRF Token Generation Error: " . $e->getMessage());
            die("Security error: Unable to generate CSRF token.");
        }
    }
    return $_SESSION['csrf_token'];
}

/**
 * validateCsrfToken(): Validate a provided CSRF token
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken(string $token): bool {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return !empty($token) && hash_equals($sessionToken, $token);
}

/**
 * logout(): Clear session and redirect to login
 * @param string|null $redirectUrl URL to redirect after logout (defaults to login.php)
 */
function logout(?string $redirectUrl = null): void {
    session_unset();
    session_destroy();
    session_start(); // Restart session for flash messages
    flash('success', 'You have been logged out.');
    header('Location: ' . ($redirectUrl ?? BASE_URL . '/auth/login.php'));
    exit;
}

/**
 * getCurrentUser(): Fetch details of the currently logged-in user
 * @param mysqli $conn Database connection
 * @return array|null User data or null if not found
 */
function getCurrentUser(mysqli $conn): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    try {
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.email, r.name AS role
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    } catch (Exception $e) {
        error_log("getCurrentUser Error: " . $e->getMessage());
        return null;
    }
}

/**
 * isLoggedIn(): Check if a user is authenticated
 * @return bool True if user_id is set in session
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

// Ensure CSRF token is generated for protected pages
if (isLoggedIn()) {
    generateCsrfToken();
}

// Error handling for session issues
if (!isset($_SESSION)) {
    error_log("Session not properly initialized in auth.php");
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
?>