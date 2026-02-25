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
define('DB_NAME', 'sigsol_sigsolportal');
define('DB_USER', 'sigsol_sigsolportal');
define('DB_PASS', 'sigsol_sigsolportal');
define('DB_CHARSET', 'utf8mb4');

// Base URL - Auto-detected from current request (no trailing slash)
// Uses whatever domain and path the site is actually installed on
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
$rootPath = str_replace('\\', '/', realpath(dirname(__DIR__)));
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? $rootPath ?: getcwd()));
if ($rootPath !== false && $docRoot !== false && str_starts_with($rootPath, $docRoot)) {
    $basePath = trim(str_replace($docRoot, '', $rootPath), '/');
} else {
    // Fallback: walk up from SCRIPT_NAME to portal root (handles admin/, user/ subfolders)
    $path = $_SERVER['SCRIPT_NAME'] ?? '';
    while ($path && preg_match('#/(admin|user|database)(/|$)#', $path)) {
        $path = dirname($path);
    }
    $basePath = trim($path ?: '', '/');
}
$basePath = $basePath !== '' ? '/' . $basePath : '';
define('BASE_URL', $protocol . '://' . $host . $basePath);

// Path constants
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/profile_images/');
define('UPLOAD_URL', BASE_URL . '/uploads/profile_images/');
define('UPLOAD_CV_PATH', ROOT_PATH . '/uploads/cv/');
define('UPLOAD_NIN_PATH', ROOT_PATH . '/uploads/nin_documents/');

// Security constants
define('SESSION_LIFETIME', 1800);      // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCK_DURATION', 900);          // 15 minutes
define('PASSWORD_MIN_LENGTH', 8);
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024);  // 2MB for profile images
define('UPLOAD_DOCUMENT_MAX_SIZE', 5 * 1024 * 1024);  // 5MB for CV and NIN documents
define('TOKEN_EXPIRY_HOURS', 1);

// SMTP / Mail (PHPMailer)
define('MAIL_ENABLED', true);  // Set false to disable sending (e.g. dev)
define('SMTP_HOST', 'server1.signaturewebhosting.space');       // e.g. smtp.gmail.com, mail.yourdomain.com
define('SMTP_PORT', 465);                        // 587 for TLS, 465 for SSL, 25 for plain
define('SMTP_ENCRYPTION', 'ssl');               // 'tls', 'ssl', or ''
define('SMTP_USERNAME', 'portal@signature-solutions.com');                     // SMTP auth username (often same as from address)
define('SMTP_PASSWORD', 'Sigsol1234!');                     // SMTP auth password or app password
define('MAIL_FROM_ADDRESS', 'portal@signature-solutions.com');
define('MAIL_FROM_NAME', 'Staff Portal');
define('OTP_EXPIRY_MINUTES', 10);                // 6-digit OTP and verification code validity

// Security headers (skipped for download scripts: PDF/CSV export)
if (!defined('SKIP_HTTP_HEADERS')) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

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
