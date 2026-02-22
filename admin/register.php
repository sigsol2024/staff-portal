<?php
/**
 * TEMPORARY Admin Registration Page
 * Use this to create your first admin account when setting up the portal.
 * DELETE THIS FILE after creating your admin account for security.
 */
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } elseif ($password !== $password_confirm) {
            $error = 'Passwords do not match.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'This email is already registered as admin.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $stmt = $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
                    $stmt->execute([$email, $hash]);
                    set_flash('success', 'Admin account created. Please login.');
                    header('Location: ' . BASE_URL . '/admin/login.php');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Registration failed. Please try again.';
                }
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
    <title>Create Admin Account - Staff Portal</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Create Admin Account</h1>
            <p class="form-hint" style="margin-bottom:1rem;padding:0.5rem;background:#fff3cd;border-radius:4px;font-size:0.85rem;">
                <strong>Temporary setup page.</strong> Delete admin/register.php after creating your admin account.
            </p>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" required
                           value="<?= esc($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password * (min <?= PASSWORD_MIN_LENGTH ?> chars)</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm Password *</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">Create Admin Account</button>
            </form>
            <div class="auth-links">
                <a href="<?= BASE_URL ?>/admin/login.php">Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html>
