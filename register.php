<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

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
        $full_name = trim($_POST['full_name'] ?? '');
        $date_of_birth = trim($_POST['date_of_birth'] ?? '') ?: null;
        $date_joined = trim($_POST['date_joined'] ?? '') ?: null;
        $position = trim($_POST['position'] ?? '') ?: null;
        $biography = trim($_POST['biography'] ?? '') ?: null;
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($email) || empty($full_name) || empty($password)) {
            $error = 'Email, full name and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } elseif ($password !== $password_confirm) {
            $error = 'Passwords do not match.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM staff WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'This email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO staff (email, password, full_name, date_of_birth, date_joined, position, biography, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                try {
                    $stmt->execute([$email, $hash, $full_name, $date_of_birth, $date_joined, $position, $biography]);
                    header('Location: ' . BASE_URL . '/login.php?type=staff&registered=1');
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
    <title>Staff Registration</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card" style="max-width: 500px;">
            <h1>Staff Registration</h1>
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
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required
                           value="<?= esc($_POST['full_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                           value="<?= esc($_POST['date_of_birth'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="date_joined">Date Joined</label>
                    <input type="date" id="date_joined" name="date_joined" class="form-control"
                           value="<?= esc($_POST['date_joined'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="position">Position</label>
                    <input type="text" id="position" name="position" class="form-control"
                           value="<?= esc($_POST['position'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="biography">Biography</label>
                    <textarea id="biography" name="biography" class="form-control" rows="3"><?= esc($_POST['biography'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="password">Password * (min <?= PASSWORD_MIN_LENGTH ?> chars)</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm Password *</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">Register</button>
            </form>
            <div class="auth-links">
                <a href="<?= BASE_URL ?>/login.php?type=staff">Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html>
