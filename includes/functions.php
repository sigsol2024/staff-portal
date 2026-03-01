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

/**
 * Salary percentage settings (per component).
 * Percentages are portions of total gross salary and should sum to ~100.
 */
function get_salary_percent_settings(): array
{
    $defaults = [
        'basic' => 34.0,
        'housing' => 16.0,
        'transport' => 16.0,
        'telephone' => 16.0,
        'other' => 16.0,
    ];

    $out = [];
    foreach ($defaults as $key => $def) {
        $raw = get_portal_setting('salary_pct_' . $key, (string) $def);
        $pct = is_numeric($raw) ? (float) $raw : (float) $def;
        $pct = max(0.0, min(100.0, $pct));
        $out[$key] = $pct;
    }

    return $out;
}

/**
 * Compute salary breakdown from Basic Salary using configured percentages.
 * If basic% is 0, returns empty values.
 */
function compute_salary_breakdown_from_basic(?float $basic_salary): array
{
    $p = get_salary_percent_settings();
    $basic_pct = (float) ($p['basic'] ?? 0.0);
    if ($basic_salary === null || $basic_salary < 0 || $basic_pct <= 0) {
        return [
            'basic_salary' => null,
            'housing_allowance' => null,
            'transport_allowance' => null,
            'telephone_allowance' => null,
            'other_allowance' => null,
            'gross_monthly_salary' => null,
        ];
    }

    $total = (float) $basic_salary / ($basic_pct / 100.0);
    $round2 = function(float $v): float { return round($v, 2); };

    $housing = $round2($total * ((float) ($p['housing'] ?? 0.0) / 100.0));
    $transport = $round2($total * ((float) ($p['transport'] ?? 0.0) / 100.0));
    $telephone = $round2($total * ((float) ($p['telephone'] ?? 0.0) / 100.0));
    $other = $round2($total * ((float) ($p['other'] ?? 0.0) / 100.0));
    $gross = $round2($total);

    return [
        'basic_salary' => $round2((float) $basic_salary),
        'housing_allowance' => $housing,
        'transport_allowance' => $transport,
        'telephone_allowance' => $telephone,
        'other_allowance' => $other,
        'gross_monthly_salary' => $gross,
    ];
}
