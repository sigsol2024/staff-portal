<?php
/**
 * Authentication helpers
 * Call require_staff_login() or require_admin_login() at top of protected pages
 */

if (!defined('STAFF_PORTAL')) {
    die('Direct access not permitted');
}

/**
 * Require staff to be logged in
 * Redirects to staff login if not authenticated
 */
function require_staff_login(): void
{
    check_session_timeout();
    if (empty($_SESSION['staff_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . BASE_URL . '/login.php?type=staff');
        exit;
    }
}

/**
 * Require admin to be logged in
 * Redirects to admin login if not authenticated
 */
function require_admin_login(): void
{
    check_session_timeout();
    if (empty($_SESSION['admin_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

/**
 * Check session timeout (30 minutes)
 */
function check_session_timeout(): void
{
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        session_start();
        return;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Get current staff ID
 */
function current_staff_id(): ?int
{
    return isset($_SESSION['staff_id']) ? (int) $_SESSION['staff_id'] : null;
}

/**
 * Get current admin ID
 */
function current_admin_id(): ?int
{
    return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
}
