<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

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

$profile_img = staff_profile_image($staff['profile_image']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Staff - <?= esc($staff['full_name']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/../includes/admin_layout.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>View Staff</h1>
                <div style="display:flex;gap:0.5rem;">
                    <?php if ($staff['status'] === 'active'): ?>
                        <a href="<?= BASE_URL ?>/admin/export-pdf.php?id=<?= $id ?>" class="btn btn-primary" target="_blank">Download PDF</a>
                        <a href="<?= BASE_URL ?>/admin/export-csv.php?id=<?= $id ?>" class="btn btn-accent">Download CSV</a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/admin/edit-staff.php?id=<?= $id ?>" class="btn btn-accent">Edit</a>
                    <a href="<?= BASE_URL ?>/admin/staff-list.php" class="btn btn-primary">Back to List</a>
                </div>
            </div>
            <div class="card">
                <div style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:flex-start;">
                    <img src="<?= esc($profile_img) ?>" alt="Profile" class="profile-img-lg">
                    <div style="flex:1;">
                        <h2><?= esc($staff['full_name']) ?></h2>
                        <span class="badge <?= status_badge_class($staff['status']) ?>"><?= esc(ucfirst($staff['status'])) ?></span>
                        <table class="table" style="margin-top:1rem;width:auto;">
                            <tr><th>Email</th><td><?= esc($staff['email']) ?></td></tr>
                            <tr><th>Position</th><td><?= esc($staff['position'] ?? '-') ?></td></tr>
                            <tr><th>Date of Birth</th><td><?= format_date($staff['date_of_birth']) ?></td></tr>
                            <tr><th>Date Joined</th><td><?= format_date($staff['date_joined']) ?></td></tr>
                        </table>
                        <?php if (!empty($staff['biography'])): ?>
                            <h3 style="margin-top:1rem;">Biography</h3>
                            <p><?= nl2br(esc($staff['biography'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
