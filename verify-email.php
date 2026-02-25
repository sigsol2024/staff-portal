<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/mail.php';

if (!empty($_SESSION['staff_id'])) {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

$email = trim($_GET['email'] ?? '');
$error = '';
$resent = isset($_GET['resent']);

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend']) && validate_csrf($_POST['csrf_token'] ?? '')) {
        $code = (string) random_int(100000, 999999);
        $expires = date('Y-m-d H:i:s', time() + (OTP_EXPIRY_MINUTES * 60));
        $pdo->prepare("DELETE FROM verification_codes WHERE email = ? AND type = 'registration'")->execute([$email]);
        $pdo->prepare("INSERT INTO verification_codes (email, code, type, user_type, expires_at) VALUES (?, ?, 'registration', NULL, ?)")->execute([$email, $code, $expires]);
        $subject = 'Verify your email - Staff Portal';
        $content = "Use the code below to verify your email. It expires in " . OTP_EXPIRY_MINUTES . " minutes.\n\nIf you did not register, ignore this email.";
        send_portal_email($email, $subject, 'Verify your email', $content, ['code' => $code]);
        header('Location: ' . BASE_URL . '/verify-email.php?email=' . rawurlencode($email) . '&resent=1');
        exit;
    }
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

                $stmt = $pdo->prepare("SELECT id FROM staff WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $row = $stmt->fetch();
                if (!$row) {
                    set_flash('success', 'Email verified. Please log in.');
                    header('Location: ' . BASE_URL . '/login.php?type=staff&verified=1');
                    exit;
                }

                $_SESSION['staff_id'] = (int) $row['id'];
                regenerate_session();
                set_flash('success', 'Email verified. Welcome!');
                header('Location: ' . BASE_URL . '/user/dashboard.php');
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
            <?php if ($resent): ?>
                <div class="alert alert-success" style="margin-bottom:1rem;">A new code has been sent to your email.</div>
            <?php endif; ?>
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
            <form method="POST" action="" id="resend-otp-form" style="margin-top:0.75rem;">
                <?= csrf_field() ?>
                <button type="submit" name="resend" value="1" id="resend-otp-btn" class="btn btn-secondary" style="width:100%;" disabled>Resend code (<span id="resend-otp-count">60</span>s)</button>
            </form>
            <div class="auth-links">
                <a href="<?= BASE_URL ?>/login.php?type=staff">Back to Login</a>
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
