<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';

$email = $_SESSION['pending_otp_email'] ?? '';
$user_type = $_SESSION['pending_otp_type'] ?? '';
$user_id = $_SESSION['pending_otp_id'] ?? null;

if (!$email || !$user_type || $user_id === null) {
    header('Location: ' . BASE_URL . '/login.php?type=' . ($user_type === 'admin' ? 'admin' : 'staff'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
        if (strlen($code) !== 6) {
            $error = 'Enter the 6-digit code from your email.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM verification_codes WHERE email = ? AND type = 'login_otp' AND user_type = ? AND code = ? AND expires_at > NOW()");
            $stmt->execute([$email, $user_type, $code]);
            if (!$stmt->fetch()) {
                $error = 'Invalid or expired code. Please log in again to receive a new code.';
            } else {
                $pdo->prepare("DELETE FROM verification_codes WHERE email = ? AND type = 'login_otp' AND user_type = ?")->execute([$email, $user_type]);
                if ($user_type === 'admin') {
                    $_SESSION['admin_id'] = (int) $user_id;
                } else {
                    $_SESSION['staff_id'] = (int) $user_id;
                }
                unset($_SESSION['pending_otp_email'], $_SESSION['pending_otp_type'], $_SESSION['pending_otp_id']);
                regenerate_session();
                $redirect = $user_type === 'admin'
                    ? ($_SESSION['redirect_after_login'] ?? BASE_URL . '/admin/dashboard.php')
                    : ($_SESSION['redirect_after_login'] ?? BASE_URL . '/user/dashboard.php');
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
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
    <title>Enter verification code - <?= $user_type === 'admin' ? 'Admin' : 'Staff' ?> Portal</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Check your email</h1>
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
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">Continue</button>
            </form>
            <div class="auth-links">
                <a href="<?= BASE_URL ?>/login.php?type=<?= $user_type === 'admin' ? 'admin' : 'staff' ?>">Back to Login</a>
            </div>
        </div>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
