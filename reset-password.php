<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['staff_id'])) {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

$token = trim($_GET['token'] ?? '');
$error = '';
$row = null;

if (empty($token)) {
    $error = 'Invalid or expired reset link.';
    $tokenInvalid = true;
} else {
    $stmt = $pdo->prepare("SELECT email FROM password_reset_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    $tokenInvalid = !$row;
    if ($tokenInvalid) {
        $error = 'Invalid or expired reset link.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$tokenInvalid) {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } elseif ($password !== $password_confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE staff SET password = ? WHERE email = ?");
            $stmt->execute([$hash, $row['email']]);

            $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
            $stmt->execute([$token]);

            set_flash('success', 'Password reset successfully. Please login.');
            header('Location: ' . BASE_URL . '/login.php?type=staff');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Reset Password</h1>
            <?php if ($tokenInvalid): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
                <div class="auth-links">
                    <a href="<?= BASE_URL ?>/forgot-password.php">Request new link</a> |
                    <a href="<?= BASE_URL ?>/login.php?type=staff">Back to Login</a>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= esc($token) ?>">
                    <div class="form-group">
                        <label for="password">New Password (min <?= PASSWORD_MIN_LENGTH ?> chars)</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Confirm Password</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">Reset Password</button>
                </form>
            <?php else: ?>
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="password">New Password (min <?= PASSWORD_MIN_LENGTH ?> chars)</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Confirm Password</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">Reset Password</button>
                </form>
            <?php endif; ?>
            <div class="auth-links">
                <a href="<?= BASE_URL ?>/login.php?type=staff">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
