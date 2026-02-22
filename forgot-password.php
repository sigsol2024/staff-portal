<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mail.php';

if (!empty($_SESSION['staff_id'])) {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $error = 'Please enter your email.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM staff WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + (TOKEN_EXPIRY_HOURS * 3600));

                $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
                $stmt->execute([$email]);

                $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$email, $token, $expires]);

                $reset_url = BASE_URL . '/reset-password.php?token=' . $token;
                $subject = 'Password Reset - Staff Portal';
                $content = "You requested a password reset. Use the button below to set a new password.\n\nThis link expires in " . TOKEN_EXPIRY_HOURS . " hour(s).\n\nIf you did not request this, ignore this email.";
                $sent = send_portal_email($email, $subject, 'Reset your password', $content, ['cta_url' => $reset_url, 'cta_text' => 'Reset password']);
                $success = $sent ? 'If that email exists, a reset link has been sent. Check your inbox.' : 'Email could not be sent. Check SMTP settings in config. Reset link: ' . $reset_url;
            } else {
                $success = 'If that email exists, a reset link has been sent.';
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
    <title>Forgot Password</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Forgot Password</h1>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= nl2br(esc($success)) ?></div>
            <?php else: ?>
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?= esc($_POST['email'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">Send Reset Link</button>
                </form>
            <?php endif; ?>
            <div class="auth-links">
                <a href="<?= BASE_URL ?>/login.php?type=staff">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
