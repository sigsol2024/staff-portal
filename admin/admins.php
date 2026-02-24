<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();
require_admin_only();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = strtolower(trim($_POST['role'] ?? 'admin'));
        if ($role !== 'manager') {
            $role = 'admin';
        }

        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (email, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$email, $hash, $role]);
                set_flash('success', ($role === 'admin' ? 'Admin' : 'Manager') . ' account created. They can log in at the admin login page.');
                header('Location: ' . BASE_URL . '/admin/admins.php');
                exit;
            }
        }
    }
}

$stmt = $pdo->query("SELECT id, email, role, created_at FROM admins ORDER BY role ASC, email ASC");
$admins_list = $stmt->fetchAll();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage accounts - Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/../includes/admin_layout.php'; ?>
        <main class="main-content">
            <h1>Manage accounts</h1>
            <p class="form-hint">Create admin or manager accounts. Managers can view staff and suspend/activate but cannot add, edit, or delete staff, or create accounts.</p>
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['message']) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Create account</h2>
                </div>
                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?= esc($_POST['email'] ?? '') ?>" placeholder="user@example.com">
                    </div>
                    <div class="form-group">
                        <label for="password">Password * (min <?= PASSWORD_MIN_LENGTH ?> characters)</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="manager" <?= ($_POST['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager</option>
                        </select>
                        <small class="form-hint">Admin: full access. Manager: view staff, export, suspend/activate only.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Create account</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Admin &amp; manager accounts</h2>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins_list)): ?>
                                <tr><td colspan="4">No accounts yet.</td></tr>
                            <?php else: ?>
                                <?php $current_id = current_admin_id(); foreach ($admins_list as $a): ?>
                                <tr>
                                    <td><?= esc($a['email']) ?></td>
                                    <td><span class="badge <?= ($a['role'] ?? 'admin') === 'admin' ? 'badge-success' : 'badge-warning' ?>"><?= esc(ucfirst($a['role'] ?? 'admin')) ?></span></td>
                                    <td><?= date('M j, Y', strtotime($a['created_at'])) ?></td>
                                    <td>
                                        <?php if ((int) $a['id'] !== $current_id): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/delete-admin.php" style="display:inline;" onsubmit="return confirm('Remove this admin/manager account? They will no longer be able to log in.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                        <?php else: ?>
                                        <span class="form-hint">(you)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
