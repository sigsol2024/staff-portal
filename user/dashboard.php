<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_staff_login();

$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$_SESSION['staff_id']]);
$staff = $stmt->fetch();
if (!$staff) {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php?type=staff');
    exit;
}

$profile_img = staff_profile_image($staff['profile_image']);
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-header">Staff Portal</div>
            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>/user/dashboard.php" class="active">Dashboard</a>
                <a href="<?= BASE_URL ?>/user/profile.php">Profile</a>
                <a href="<?= BASE_URL ?>/user/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <h1>Dashboard</h1>
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['message']) ?></div>
            <?php endif; ?>
            <div class="card">
                <div class="card-header">
                    <h2>Profile Summary</h2>
                    <span class="badge <?= status_badge_class($staff['status']) ?>"><?= esc(ucfirst($staff['status'])) ?></span>
                </div>
                <div style="display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap;">
                    <img src="<?= esc($profile_img) ?>" alt="Profile" class="profile-img-lg">
                    <div>
                        <p><strong><?= esc($staff['full_name']) ?></strong></p>
                        <p><?= esc($staff['email']) ?></p>
                        <p>Position: <?= esc($staff['position'] ?? '-') ?></p>
                        <p>Date Joined: <?= format_date($staff['date_joined']) ?></p>
                        <a href="<?= BASE_URL ?>/user/profile.php" class="btn btn-primary" style="margin-top: 1rem;">Edit Profile</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
