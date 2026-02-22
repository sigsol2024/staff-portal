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
            <div class="card view-staff-card">
                <div class="view-staff-profile">
                    <div class="view-staff-avatar-wrap">
                        <img src="<?= esc($profile_img) ?>" alt="Profile" class="profile-img-lg">
                    </div>
                    <div class="view-staff-details">
                        <div class="view-staff-header">
                            <h2 class="view-staff-name"><?= esc($staff['full_name']) ?></h2>
                            <span class="badge <?= status_badge_class($staff['status']) ?>"><?= esc(ucfirst($staff['status'])) ?></span>
                        </div>
                        <dl class="view-staff-meta">
                            <dt>Email</dt>
                            <dd><?= esc($staff['email']) ?></dd>
                            <dt>Phone</dt>
                            <dd><?= esc($staff['phone_number'] ?? '-') ?></dd>
                            <dt>Gender</dt>
                            <dd><?= esc($staff['gender'] ?? '-') ?></dd>
                            <dt>Position</dt>
                            <dd><?= esc($staff['position'] ?? '-') ?></dd>
                            <dt>Date of Birth</dt>
                            <dd><?= format_date($staff['date_of_birth']) ?></dd>
                            <dt>Date Joined</dt>
                            <dd><?= format_date($staff['date_joined']) ?></dd>
                            <?php if (!empty($staff['address'])): ?>
                            <dt>Address</dt>
                            <dd><?= nl2br(esc($staff['address'])) ?></dd>
                            <?php endif; ?>
                        </dl>
                        <?php if (!empty($staff['biography'])): ?>
                            <div class="view-staff-bio">
                                <h3>Biography</h3>
                                <p><?= nl2br(esc($staff['biography'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
