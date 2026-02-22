<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/mail.php';

$email = $_SESSION['pending_otp_email'] ?? '';
$user_type = $_SESSION['pending_otp_type'] ?? '';
$user_id = $_SESSION['pending_otp_id'] ?? null;

if (!$email || !$user_type || $user_id === null) {
    header('Location: ' . BASE_URL . '/login.php?type=' . ($user_type === 'admin' ? 'admin' : 'staff'));
    exit;
}

$error = '';
$resent = isset($_GET['resent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend']) && validate_csrf($_POST['csrf_token'] ?? '')) {
        $code = (string) random_int(100000, 999999);
        $expires = date('Y-m-d H:i:s', time() + (OTP_EXPIRY_MINUTES * 60));
        $pdo->prepare("DELETE FROM verification_codes WHERE email = ? AND type = 'login_otp' AND user_type = ?")->execute([$email, $user_type]);
        $pdo->prepare("INSERT INTO verification_codes (email, code, type, user_type, expires_at) VALUES (?, ?, 'login_otp', ?, ?)")->execute([$email, $code, $user_type, $expires]);
        $subject = $user_type === 'admin' ? 'Your login code - Admin Portal' : 'Your login code - Staff Portal';
        $content = "Use the code below to sign in. It expires in " . OTP_EXPIRY_MINUTES . " minutes.";
        send_portal_email($email, $subject, 'Your login code', $content, ['code' => $code]);
        header('Location: ' . BASE_URL . '/otp-verify.php?resent=1');
        exit;
    }
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
            <?php if ($resent): ?>
                <div class="alert alert-success" style="margin-bottom:1rem;">A new code has been sent to your email.</div>
            <?php endif; ?>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="code">Verification code</label>
                    <input type="text" id="code" name="code" class="form-control" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="000000" required autocomplete="one-time-code">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">Continue</button>
            </form>
            <form method="POST" action="" id="resend-otp-form" style="margin-top:0.75rem;">
                <?= csrf_field() ?>
                <button type="submit" name="resend" value="1" id="resend-otp-btn" class="btn btn-secondary" style="width:100%;" disabled>Resend code (<span id="resend-otp-count">60</span>s)</button>
            </form>
            <div class="auth-links">
                <a href="<?= BASE_URL ?>/login.php?type=<?= $user_type === 'admin' ? 'admin' : 'staff' ?>">Back to Login</a>
            </div>
        </div>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
    <script>
    (function() {
        var btn = document.getElementById('resend-otp-btn');
        var countEl = document.getElementById('resend-otp-count');
        if (!btn || !countEl) return;
        var sec = 60;
        function tick() {
            if (sec > 0) {
                countEl.textContent = sec;
                btn.disabled = true;
                sec--;
                setTimeout(tick, 1000);
            } else {
                btn.disabled = false;
                btn.innerHTML = 'Resend code';
            }
        }
        tick();
    })();
    </script>
</body>
</html>
