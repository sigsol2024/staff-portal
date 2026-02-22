<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mail.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            $error = 'Please enter an email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (!defined('MAIL_ENABLED') || !MAIL_ENABLED) {
            $error = 'Email is disabled in configuration. Set MAIL_ENABLED to true in config/config.php.';
        } else {
            $subject = 'Staff Portal – Test Email';
            $content = "This is a test email from the Staff Portal.\n\nIf you received this, the email configuration is working correctly.\n\nSent at: " . date('Y-m-d H:i:s');
            $sent = send_portal_email($email, $subject, 'Test email', $content, []);
            if ($sent) {
                $success = 'Test email sent to ' . esc($email) . '. Check the inbox (and spam folder).';
            } else {
                $error = 'Failed to send. Check SMTP settings in config (host, port, username, password, encryption).';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email Configuration - Staff Portal</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Test email configuration</h1>
            <p class="form-hint">Enter an email address to send a test message and verify SMTP settings.</p>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= esc($_POST['email'] ?? '') ?>" placeholder="you@example.com" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">Send test email</button>
            </form>
            <div class="auth-links">
                <a href="<?= BASE_URL ?>/login.php">Back to Login</a>
            </div>
        </div>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
