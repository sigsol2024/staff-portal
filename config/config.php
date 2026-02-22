<?php
/**
 * Staff Management Portal - Configuration
 * Update database credentials for your shared hosting environment
 */

// Prevent direct access
if (!defined('STAFF_PORTAL')) {
    define('STAFF_PORTAL', true);
}

// Error reporting (disable display in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'staff_portal');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_CHARSET', 'utf8mb4');

// Base URL - Update for your domain (no trailing slash)
define('BASE_URL', 'https://yourdomain.com/staff-portal');

// Path constants
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/profile_images/');
define('UPLOAD_URL', BASE_URL . '/uploads/profile_images/');

// Security constants
define('SESSION_LIFETIME', 1800);      // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCK_DURATION', 900);          // 15 minutes
define('PASSWORD_MIN_LENGTH', 8);
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024);  // 2MB
define('TOKEN_EXPIRY_HOURS', 1);

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// PDO connection
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die('Database connection failed. Please check config/config.php');
}

/**
 * Generate CSRF token
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output CSRF hidden field for forms
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Validate CSRF token
 */
function validate_csrf(?string $token): bool
{
    return $token && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Escape output for HTML
 */
function esc(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
