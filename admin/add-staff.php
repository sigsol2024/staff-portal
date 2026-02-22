<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$error = '';

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
        $phone_number = trim($_POST['phone_number'] ?? '') ?: null;
        $gender = trim($_POST['gender'] ?? '') ?: null;
        $address = trim($_POST['address'] ?? '') ?: null;
        $password = $_POST['password'] ?? '';
        $status = ($_POST['status'] ?? 'active') === 'suspended' ? 'suspended' : 'active';

        if (empty($email) || empty($full_name) || empty($password)) {
            $error = 'Email, full name and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM staff WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'This email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO staff (email, password, full_name, date_of_birth, date_joined, position, biography, phone_number, gender, address, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                try {
                    $stmt->execute([$email, $hash, $full_name, $date_of_birth, $date_joined, $position, $biography, $phone_number, $gender, $address, $status]);
                    $staff_id = (int) $pdo->lastInsertId();
                    $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action, staff_id) VALUES (?, ?, ?)");
                    $stmt->execute([current_admin_id(), 'add_staff', $staff_id]);
                    set_flash('success', 'Staff added successfully.');
                    header('Location: ' . BASE_URL . '/admin/view-staff.php?id=' . $staff_id);
                    exit;
                } catch (PDOException $e) {
                    $error = 'Failed to add staff. Please try again.';
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
    <title>Add Staff - Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/../includes/admin_layout.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>Add Staff</h1>
                <a href="<?= BASE_URL ?>/admin/staff-list.php" class="btn btn-primary">Back to List</a>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>
            <div class="card">
                <form method="POST">
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
                        <label for="password">Password * (min <?= PASSWORD_MIN_LENGTH ?> chars)</label>
                        <input type="password" id="password" name="password" class="form-control" required>
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
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">— Select —</option>
                            <option value="Male" <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($_POST['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control"
                               value="<?= esc($_POST['phone_number'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" class="form-control"
                               value="<?= esc($_POST['position'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?= esc($_POST['address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="biography">Biography</label>
                        <textarea id="biography" name="biography" class="form-control" rows="4"><?= esc($_POST['biography'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Staff</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
