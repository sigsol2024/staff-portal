<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

require_staff_login();

$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$_SESSION['staff_id']]);
$staff = $stmt->fetch();
if (!$staff) {
    header('Location: ' . BASE_URL . '/login.php?type=staff');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'profile';

        if ($action === 'profile') {
            $full_name = trim($_POST['full_name'] ?? '');
            $date_of_birth = trim($_POST['date_of_birth'] ?? '') ?: null;
            $biography = trim($_POST['biography'] ?? '') ?: null;
            $phone_number = trim($_POST['phone_number'] ?? '') ?: null;
            $gender = trim($_POST['gender'] ?? '') ?: null;
            $address = trim($_POST['address'] ?? '') ?: null;
            $marital_status = trim($_POST['marital_status'] ?? '') ?: null;

            if (empty($full_name)) {
                $error = 'Full name is required.';
            } else {
                $stmt = $pdo->prepare("UPDATE staff SET full_name = ?, date_of_birth = ?, biography = ?, phone_number = ?, gender = ?, address = ?, marital_status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$full_name, $date_of_birth, $biography, $phone_number, $gender, $address, $marital_status, $staff['id']]);
                set_flash('success', 'Profile updated.');
                header('Location: ' . BASE_URL . '/user/profile.php');
                exit;
            }
        } elseif ($action === 'image') {
            if (!empty($_FILES['profile_image']['name'])) {
                $result = handle_profile_upload($_FILES['profile_image']);
                if ($result) {
                    if ($staff['profile_image']) {
                        delete_profile_image($staff['profile_image']);
                    }
                    $stmt = $pdo->prepare("UPDATE staff SET profile_image = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$result, $staff['id']]);
                    set_flash('success', 'Profile image updated.');
                    header('Location: ' . BASE_URL . '/user/profile.php');
                    exit;
                }
                $error = 'Invalid image. Use JPG or PNG, max 2MB.';
            }
        } elseif ($action === 'password') {
            $old = $_POST['old_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (!password_verify($old, $staff['password'])) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($new) < PASSWORD_MIN_LENGTH) {
                $error = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
            } elseif ($new !== $confirm) {
                $error = 'New passwords do not match.';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE staff SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hash, $staff['id']]);
                set_flash('success', 'Password changed.');
                header('Location: ' . BASE_URL . '/user/profile.php');
                exit;
            }
        }
    }
}

$profile_img = staff_profile_image($staff['profile_image']);
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Staff Portal</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-header">Staff Portal</div>
            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a>
                <a href="<?= BASE_URL ?>/user/profile.php" class="active">Profile</a>
                <a href="<?= BASE_URL ?>/user/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <h1>Profile Management</h1>
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['message']) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Profile Image</h2>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <img src="<?= esc($profile_img) ?>" alt="Profile" class="profile-img-lg">
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="image">
                        <div class="form-group">
                            <input type="file" name="profile_image" accept="image/jpeg,image/jpg,image/png" required>
                            <p class="form-hint">JPG or PNG, max 2MB</p>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Profile Details</h2>
                </div>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profile">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required
                               value="<?= esc($staff['full_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                               value="<?= esc($staff['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">— Select —</option>
                            <option value="Male" <?= ($staff['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($staff['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($staff['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="marital_status">Marital status</label>
                        <select id="marital_status" name="marital_status" class="form-control">
                            <option value="">— Select —</option>
                            <option value="Single" <?= ($staff['marital_status'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                            <option value="Married" <?= ($staff['marital_status'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                            <option value="Divorced" <?= ($staff['marital_status'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                            <option value="Widowed" <?= ($staff['marital_status'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                            <option value="Other" <?= ($staff['marital_status'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone number</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control"
                               value="<?= esc($staff['phone_number'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Residential address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?= esc($staff['address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="biography">Biography</label>
                        <textarea id="biography" name="biography" class="form-control" rows="4"><?= esc($staff['biography'] ?? '') ?></textarea>
                    </div>
                    <p class="form-hint">Email, position, date joined cannot be changed by staff. Contact admin.</p>
                    <button type="submit" class="btn btn-primary">Save Profile</button>
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
</body>
</html>
