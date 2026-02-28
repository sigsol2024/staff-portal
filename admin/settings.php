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
        } elseif ($action === 'global_profile_edit') {
            if (!is_admin_role()) {
                $error = 'You do not have permission to change global settings.';
            } else {
                $enabled = !empty($_POST['staff_profile_edit_global_enabled']) ? '1' : '0';
                if (!set_portal_setting('staff_profile_edit_global_enabled', $enabled)) {
                    $error = 'Could not update global setting. Ensure database schema is up to date.';
                } else {
                    set_flash('success', $enabled === '1'
                        ? 'Global staff profile editing has been enabled.'
                        : 'Global staff profile editing has been disabled.');
                    header('Location: ' . BASE_URL . '/admin/settings.php');
                    exit;
                }
            }
        } elseif ($action === 'salary_settings') {
            if (!is_admin_role()) {
                $error = 'You do not have permission to change global settings.';
            } else {
                $pct_low_raw = trim($_POST['salary_allowance_percent_below_150k'] ?? '');
                $pct_high_raw = trim($_POST['salary_allowance_percent_150k_up'] ?? '');
                if ($pct_low_raw === '' || !is_numeric($pct_low_raw) || $pct_high_raw === '' || !is_numeric($pct_high_raw)) {
                    $error = 'Both allowance percentage fields must be numbers between 0 and 100.';
                } else {
                    $pct_low = (float) $pct_low_raw;
                    $pct_high = (float) $pct_high_raw;
                    if ($pct_low < 0 || $pct_low > 100 || $pct_high < 0 || $pct_high > 100) {
                        $error = 'Allowance percentages must be between 0 and 100.';
                    } else {
                        $low_str = rtrim(rtrim(number_format($pct_low, 2, '.', ''), '0'), '.');
                        $high_str = rtrim(rtrim(number_format($pct_high, 2, '.', ''), '0'), '.');
                        if ($low_str === '') $low_str = '0';
                        if ($high_str === '') $high_str = '0';
                        if (
                            !set_portal_setting('salary_allowance_percent_below_150k', $low_str) ||
                            !set_portal_setting('salary_allowance_percent_150k_up', $high_str)
                        ) {
                            $error = 'Could not update salary setting. Ensure database schema is up to date.';
                        } else {
                            set_flash('success', 'Salary allowance percentages have been updated.');
                            header('Location: ' . BASE_URL . '/admin/settings.php');
                            exit;
                        }
                    }
                }
            }
        }
    }
}

$flash = get_flash();
$global_edit_enabled = (get_portal_setting('staff_profile_edit_global_enabled', '1') ?? '1') === '1';
$salary_allowance_percent_below_150k = (float) (get_portal_setting('salary_allowance_percent_below_150k', '0') ?? '0');
$salary_allowance_percent_150k_up = (float) (get_portal_setting('salary_allowance_percent_150k_up', '0') ?? '0');
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

            <div class="card">
                <div class="card-header">
                    <h2>Global Staff Profile Editing</h2>
                </div>
                <p class="form-hint">When disabled, staff cannot access or edit their profile pages (dashboard edit button turns red and direct URLs redirect back).</p>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="global_profile_edit">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="staff_profile_edit_global_enabled" value="1" <?= $global_edit_enabled ? 'checked' : '' ?> <?= is_admin_role() ? '' : 'disabled' ?>>
                            Enable staff profile editing globally
                        </label>
                    </div>
                    <?php if (is_admin_role()): ?>
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Apply this change globally for all staff?');">Save</button>
                    <?php else: ?>
                        <p class="form-hint">Only full admins can change this setting.</p>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Salary Settings</h2>
                </div>
                <p class="form-hint">Set tiered allowance percentages used to auto-calculate Housing Allowance and Transport Allowance from Basic Salary. Gross Monthly Salary is auto-calculated as Basic + Housing + Transport.</p>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="salary_settings">
                    <div class="form-group">
                        <label for="salary_allowance_percent_below_150k">Allowance percentage (Basic salary below 150,000) (%) <span class="required">*</span></label>
                        <input
                            type="number"
                            id="salary_allowance_percent_below_150k"
                            name="salary_allowance_percent_below_150k"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.01"
                            required
                            value="<?= esc((string)$salary_allowance_percent_below_150k) ?>"
                            <?= is_admin_role() ? '' : 'disabled' ?>
                        >
                    </div>
                    <div class="form-group">
                        <label for="salary_allowance_percent_150k_up">Allowance percentage (Basic salary 150,000 and above) (%) <span class="required">*</span></label>
                        <input
                            type="number"
                            id="salary_allowance_percent_150k_up"
                            name="salary_allowance_percent_150k_up"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.01"
                            required
                            value="<?= esc((string)$salary_allowance_percent_150k_up) ?>"
                            <?= is_admin_role() ? '' : 'disabled' ?>
                        >
                        <span class="form-hint">Example: 25 means each allowance = Basic Salary × 25% for that tier.</span>
                    </div>
                    <?php if (is_admin_role()): ?>
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Update salary allowance percentage? This affects auto-calculation going forward.');">Save</button>
                    <?php else: ?>
                        <p class="form-hint">Only full admins can change this setting.</p>
                    <?php endif; ?>
                </form>
            </div>
        </main>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
