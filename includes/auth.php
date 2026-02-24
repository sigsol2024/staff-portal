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

/**
 * Get current admin role: 'admin' or 'manager'. Managers cannot edit/create staff or create admin/manager.
 * Loads from DB once if not in session.
 */
function get_current_admin_role(): string
{
    if (isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['admin', 'manager'], true)) {
        return $_SESSION['admin_role'];
    }
    $id = current_admin_id();
    if (!$id) {
        return 'admin';
    }
    $pdo = $GLOBALS['pdo'] ?? null;
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            $role = isset($row['role']) && in_array($row['role'], ['admin', 'manager'], true) ? $row['role'] : 'admin';
            $_SESSION['admin_role'] = $role;
            return $role;
        } catch (Throwable $e) {
            return 'admin';
        }
    }
    return 'admin';
}

/**
 * True if current user is full admin (can edit staff, add staff, manage accounts).
 */
function is_admin_role(): bool
{
    return get_current_admin_role() === 'admin';
}

/**
 * Require full admin role. Redirects managers to dashboard with error.
 */
function require_admin_only(): void
{
    require_admin_login();
    if (get_current_admin_role() !== 'admin') {
        set_flash('error', 'You do not have permission to perform this action.');
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        exit;
    }
}
