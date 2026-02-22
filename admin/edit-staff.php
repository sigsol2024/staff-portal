<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

require_admin_login();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . '/admin/staff-list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$id]);
$staff = $stmt->fetch();
if (!$staff) {
    set_flash('error', 'Staff not found.');
    header('Location: ' . BASE_URL . '/admin/staff-list.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'profile';

        if ($action === 'profile') {
            $email = trim($_POST['email'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $date_of_birth = trim($_POST['date_of_birth'] ?? '') ?: null;
            $date_joined = trim($_POST['date_joined'] ?? '') ?: null;
            $position = trim($_POST['position'] ?? '') ?: null;
            $biography = trim($_POST['biography'] ?? '') ?: null;
            $phone_number = trim($_POST['phone_number'] ?? '') ?: null;
            $gender = trim($_POST['gender'] ?? '') ?: null;
            $address = trim($_POST['address'] ?? '') ?: null;
            $status = ($_POST['status'] ?? 'active') === 'suspended' ? 'suspended' : 'active';

            if (empty($email) || empty($full_name)) {
                $error = 'Email and full name are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address.';
            } else {
                $stmt = $pdo->prepare("SELECT id FROM staff WHERE email = ? AND id != ?");
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) {
                    $error = 'Email already in use.';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE staff SET email = ?, full_name = ?, date_of_birth = ?, date_joined = ?, position = ?, biography = ?, phone_number = ?, gender = ?, address = ?, status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$email, $full_name, $date_of_birth, $date_joined, $position, $biography, $phone_number, $gender, $address, $status, $id]);
                    set_flash('success', 'Staff updated.');
                    header('Location: ' . BASE_URL . '/admin/view-staff.php?id=' . $id);
                    exit;
                }
            }
        } elseif ($action === 'image') {
            if (!empty($_FILES['profile_image']['name'])) {
                $result = handle_profile_upload($_FILES['profile_image']);
                if ($result) {
                    if ($staff['profile_image']) {
                        delete_profile_image($staff['profile_image']);
                    }
                    $stmt = $pdo->prepare("UPDATE staff SET profile_image = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$result, $id]);
                    set_flash('success', 'Profile image updated.');
                    header('Location: ' . BASE_URL . '/admin/edit-staff.php?id=' . $id);
                    exit;
                }
                $error = 'Invalid image. Use JPG or PNG, max 2MB.';
            }
        } elseif ($action === 'password') {
            $password = $_POST['new_password'] ?? '';
            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE staff SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hash, $id]);
                set_flash('success', 'Password changed.');
                header('Location: ' . BASE_URL . '/admin/edit-staff.php?id=' . $id);
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
    <title>Edit Staff - Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/../includes/admin_layout.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>Edit Staff</h1>
                <a href="<?= BASE_URL ?>/admin/view-staff.php?id=<?= $id ?>" class="btn btn-primary">View</a>
                <a href="<?= BASE_URL ?>/admin/staff-list.php" class="btn btn-primary">Back to List</a>
            </div>
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['message']) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>

            <div class="card edit-staff-card">
                <div class="view-staff-profile">
                    <div class="view-staff-avatar-wrap">
                        <img src="<?= esc($profile_img) ?>" alt="Profile" class="profile-img-lg">
                        <form method="POST" enctype="multipart/form-data" class="edit-staff-image-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="image">
                            <input type="file" name="profile_image" accept="image/jpeg,image/jpg,image/png" required class="form-control" style="margin-top:0.75rem;max-width:200px;">
                            <button type="submit" class="btn btn-primary btn-sm" style="margin-top:0.5rem;">Upload</button>
                        </form>
                    </div>
                    <div class="view-staff-details" style="flex:1;">
                        <div class="view-staff-header">
                            <h2 class="view-staff-name"><?= esc($staff['full_name']) ?></h2>
                            <span class="badge <?= status_badge_class($staff['status']) ?>"><?= esc(ucfirst($staff['status'])) ?></span>
                        </div>
                    </div>
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
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?= esc($staff['email']) ?>">
                    </div>
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
                        <label for="date_joined">Date Joined</label>
                        <input type="date" id="date_joined" name="date_joined" class="form-control"
                               value="<?= esc($staff['date_joined'] ?? '') ?>">
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
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control"
                               value="<?= esc($staff['phone_number'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" class="form-control"
                               value="<?= esc($staff['position'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?= esc($staff['address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="biography">Biography</label>
                        <textarea id="biography" name="biography" class="form-control" rows="4"><?= esc($staff['biography'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?= $staff['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="suspended" <?= $staff['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
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
                        <label for="new_password">New Password (min <?= PASSWORD_MIN_LENGTH ?> chars)</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
