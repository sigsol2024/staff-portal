<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';

$type = $_GET['type'] ?? 'staff';
$isStaff = ($type === 'staff');

// Redirect if already logged in
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}
if ($isStaff && !empty($_SESSION['staff_id'])) {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

$error = '';
$success = isset($_GET['registered']) ? 'Registration successful. Please login.' : '';
$flash = get_flash();
if ($flash && $flash['type'] === 'success') {
    $success = $flash['message'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter email and password.';
        } else {
            if ($isStaff) {
                // Staff login
                $stmt = $pdo->prepare("SELECT * FROM staff WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if (!$user) {
                    $error = 'Invalid email or password.';
                } elseif ($user['status'] === 'suspended') {
                    $error = 'Your account has been suspended. Contact administrator.';
                } elseif ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $error = 'Account locked. Try again later.';
                } elseif (!password_verify($password, $user['password'])) {
                    $failed = (int) $user['failed_attempts'] + 1;
                    $locked = ($failed >= MAX_LOGIN_ATTEMPTS) ? date('Y-m-d H:i:s', time() + LOCK_DURATION) : null;
                    $stmt = $pdo->prepare("UPDATE staff SET failed_attempts = ?, locked_until = ? WHERE id = ?");
                    $stmt->execute([$failed, $locked, $user['id']]);
                    $error = $failed >= MAX_LOGIN_ATTEMPTS
                        ? 'Account locked for 15 minutes after too many failed attempts.'
                        : 'Invalid email or password.';
                } else {
                    $stmt = $pdo->prepare("UPDATE staff SET failed_attempts = 0, locked_until = NULL WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $_SESSION['staff_id'] = $user['id'];
                    regenerate_session();
                    header('Location: ' . ($_SESSION['redirect_after_login'] ?? BASE_URL . '/user/dashboard.php'));
                    unset($_SESSION['redirect_after_login']);
                    exit;
                }
            } else {
                // Admin login
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
                $stmt->execute([$email]);
                $admin = $stmt->fetch();

                if (!$admin || !password_verify($password, $admin['password'])) {
                    $error = 'Invalid email or password.';
                } else {
                    $_SESSION['admin_id'] = $admin['id'];
                    regenerate_session();
                    header('Location: ' . ($_SESSION['redirect_after_login'] ?? BASE_URL . '/admin/dashboard.php'));
                    unset($_SESSION['redirect_after_login']);
                    exit;
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
    <title>Login - <?= $isStaff ? 'Staff' : 'Admin' ?> Portal</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1><?= $isStaff ? 'Staff Login' : 'Admin Login' ?></h1>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= esc($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required
                           value="<?= esc($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">Login</button>
            </form>
            <div class="auth-links">
                <?php if ($isStaff): ?>
                    <a href="<?= BASE_URL ?>/register.php">Register</a> |
                    <a href="<?= BASE_URL ?>/forgot-password.php">Forgot Password</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php?type=staff">Staff Login</a> |
                    <a href="<?= BASE_URL ?>/admin/register.php">Create Admin Account</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
