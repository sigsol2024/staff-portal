<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['staff_id'])) {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $date_of_birth = trim($_POST['date_of_birth'] ?? '') ?: null;
        $date_joined = trim($_POST['date_joined'] ?? '') ?: null;
        $position = trim($_POST['position'] ?? '') ?: null;
        $gender = trim($_POST['gender'] ?? '') ?: null;
        $phone_number = trim($_POST['phone_number'] ?? '') ?: null;
        $address = trim($_POST['address'] ?? '') ?: null;
        $biography = trim($_POST['biography'] ?? '') ?: null;

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
                    INSERT INTO staff (email, password, full_name, date_of_birth, date_joined, position, biography, phone_number, gender, address, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                try {
                    $stmt->execute([$email, $hash, $full_name, $date_of_birth, $date_joined, $position, $biography, $phone_number, $gender, $address]);
                    header('Location: ' . BASE_URL . '/login.php?type=staff&registered=1');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

$post = $_POST ?? [];
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
        <div class="auth-card multistep-card">
            <h1>Staff Registration</h1>
            <div class="multistep-progress">
                <span class="step-dot active" data-step="1">1</span>
                <span class="step-line"></span>
                <span class="step-dot" data-step="2">2</span>
                <span class="step-line"></span>
                <span class="step-dot" data-step="3">3</span>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="" id="register-form">
                <?= csrf_field() ?>

                <div class="multistep-panel active" data-step="1">
                    <h2 class="step-title">Account</h2>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?= esc($post['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password * (min <?= PASSWORD_MIN_LENGTH ?> chars)</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Confirm Password *</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                    </div>
                    <button type="button" class="btn btn-primary btn-next" data-next="2" style="width:100%;">Next</button>
                </div>

                <div class="multistep-panel" data-step="2">
                    <h2 class="step-title">Personal &amp; Work</h2>
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required
                               value="<?= esc($post['full_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">— Select —</option>
                            <option value="Male" <?= ($post['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($post['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($post['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control"
                               value="<?= esc($post['phone_number'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                               value="<?= esc($post['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_joined">Date Joined</label>
                        <input type="date" id="date_joined" name="date_joined" class="form-control"
                               value="<?= esc($post['date_joined'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" class="form-control"
                               value="<?= esc($post['position'] ?? '') ?>">
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-primary btn-prev" data-prev="1">Previous</button>
                        <button type="button" class="btn btn-primary btn-next" data-next="3">Next</button>
                    </div>
                </div>

                <div class="multistep-panel" data-step="3">
                    <h2 class="step-title">Address &amp; Bio</h2>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?= esc($post['address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="biography">Biography</label>
                        <textarea id="biography" name="biography" class="form-control" rows="3"><?= esc($post['biography'] ?? '') ?></textarea>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-primary btn-prev" data-prev="2">Previous</button>
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>
                </div>
            </form>
            <div class="auth-links">
                <a href="<?= BASE_URL ?>/login.php?type=staff">Already have an account? Login</a>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var form = document.getElementById('register-form');
        var panels = form.querySelectorAll('.multistep-panel');
        var dots = document.querySelectorAll('.step-dot');

        function showStep(step) {
            step = parseInt(step, 10);
            panels.forEach(function(p) {
                p.classList.toggle('active', parseInt(p.getAttribute('data-step'), 10) === step);
            });
            dots.forEach(function(d) {
                var n = parseInt(d.getAttribute('data-step'), 10);
                d.classList.toggle('active', n === step);
                d.classList.toggle('done', n < step);
            });
        }

        form.querySelectorAll('.btn-next').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var panel = this.closest('.multistep-panel');
                var inputs = panel.querySelectorAll('input[required], select[required]');
                var valid = true;
                inputs.forEach(function(inp) {
                    if (!inp.value.trim()) { valid = false; inp.reportValidity && inp.reportValidity(); }
                });
                if (valid) showStep(this.getAttribute('data-next'));
            });
        });
        form.querySelectorAll('.btn-prev').forEach(function(btn) {
            btn.addEventListener('click', function() {
                showStep(this.getAttribute('data-prev'));
            });
        });
    })();
    </script>
</body>
</html>
