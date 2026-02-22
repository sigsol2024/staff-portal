<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();
if (!$admin) {
    session_destroy();
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'email') {
            $new_email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($new_email) || empty($password)) {
                $error = 'Email and current password are required.';
            } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address.';
            } elseif (!password_verify($password, $admin['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
                $stmt->execute([$new_email, $admin['id']]);
                if ($stmt->fetch()) {
                    $error = 'Email already in use.';
                } else {
                    $stmt = $pdo->prepare("UPDATE admins SET email = ? WHERE id = ?");
                    $stmt->execute([$new_email, $admin['id']]);
                    set_flash('success', 'Email updated successfully.');
                    header('Location: ' . BASE_URL . '/admin/settings.php');
                    exit;
                }
            }
        } elseif ($action === 'password') {
            $old_password = $_POST['old_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (empty($old_password) || empty($new_password) || empty($confirm)) {
                $error = 'All password fields are required.';
            } elseif (!password_verify($old_password, $admin['password'])) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
                $error = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
            } elseif ($new_password !== $confirm) {
                $error = 'New passwords do not match.';
            } else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $admin['id']]);
                set_flash('success', 'Password changed successfully.');
                header('Location: ' . BASE_URL . '/admin/settings.php');
                exit;
            }
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/../includes/admin_layout.php'; ?>
        <main class="main-content">
            <h1>Admin Settings</h1>
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['message']) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Change Email</h2>
                </div>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="email">
                    <div class="form-group">
                        <label for="email">New Email *</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?= esc($admin['email']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Current Password * (verification)</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Email</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Change Password</h2>
                </div>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">
                    <div class="form-group">
                        <label for="old_password">Current Password *</label>
                        <input type="password" id="old_password" name="old_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password * (min <?= PASSWORD_MIN_LENGTH ?> chars)</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </main>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
