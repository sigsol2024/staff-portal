<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['staff_id'])) {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

$email = trim($_GET['email'] ?? '');
$error = '';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
        if (strlen($code) !== 6) {
            $error = 'Enter the 6-digit code from your email.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM verification_codes WHERE email = ? AND type = 'registration' AND code = ? AND expires_at > NOW()");
            $stmt->execute([$email, $code]);
            if (!$stmt->fetch()) {
                $error = 'Invalid or expired code. Request a new one by registering again.';
            } else {
                $pdo->prepare("UPDATE staff SET email_verified = 1 WHERE email = ?")->execute([$email]);
                $pdo->prepare("DELETE FROM verification_codes WHERE email = ? AND type = 'registration'")->execute([$email]);
                set_flash('success', 'Email verified. You can now log in.');
                header('Location: ' . BASE_URL . '/login.php?type=staff&verified=1');
                exit;
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
    <title>Verify Email - Staff Portal</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Verify your email</h1>
            <p class="form-hint">We sent a 6-digit code to <?= esc($email) ?></p>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="code">Verification code</label>
                    <input type="text" id="code" name="code" class="form-control" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="000000" required autocomplete="one-time-code">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">Verify</button>
            </form>
            <div class="auth-links">
                <a href="<?= BASE_URL ?>/login.php?type=staff">Back to Login</a>
            </div>
        </div>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
