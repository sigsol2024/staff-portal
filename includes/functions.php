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
