<?php
/**
 * Helper functions
 */

if (!defined('STAFF_PORTAL')) {
    die('Direct access not permitted');
}

/**
 * Set flash message
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get profile image URL or default placeholder
 */
function staff_profile_image(?string $filename): string
{
    if ($filename && file_exists(UPLOAD_PATH . $filename)) {
        return UPLOAD_URL . $filename;
    }
    return BASE_URL . '/assets/images/placeholder.svg';
}

/**
 * Format date for display
 */
function format_date(?string $date, string $format = 'M j, Y'): string
{
    if (empty($date)) return '-';
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt ? $dt->format($format) : $date;
}

/**
 * Get status badge class
 */
function status_badge_class(string $status): string
{
    return $status === 'active' ? 'badge-success' : 'badge-danger';
}

/**
 * Redirect with flash
 */
function redirect_with(string $url, string $type, string $message): void
{
    set_flash($type, $message);
    header('Location: ' . $url);
    exit;
}

/**
 * Read a portal setting from DB (portal_settings table).
 * Returns $default if missing or DB unavailable.
 */
function get_portal_setting(string $key, ?string $default = null): ?string
{
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo) return $default;
    try {
        $stmt = $pdo->prepare("SELECT `value` FROM portal_settings WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) return $default;
        return $row['value'] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}

/**
 * Upsert a portal setting into DB (portal_settings table).
 */
function set_portal_setting(string $key, string $value): bool
{
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare("INSERT INTO portal_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW()");
        return (bool) $stmt->execute([$key, $value]);
    } catch (Throwable $e) {
        return false;
    }
}
