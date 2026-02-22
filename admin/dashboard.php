<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$total = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
$active = $pdo->query("SELECT COUNT(*) FROM staff WHERE status = 'active'")->fetchColumn();
$suspended = $pdo->query("SELECT COUNT(*) FROM staff WHERE status = 'suspended'")->fetchColumn();

$stmt = $pdo->query("SELECT id, full_name, email, position, date_joined, status FROM staff ORDER BY date_joined DESC, created_at DESC LIMIT 5");
$recent = $stmt->fetchAll();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/../includes/admin_layout.php'; ?>
        <main class="main-content">
            <h1>Admin Dashboard</h1>
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['message']) ?></div>
            <?php endif; ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">S</div>
                    <p class="stat-value"><?= (int) $total ?></p>
                    <p class="stat-label">Total Staff</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">A</div>
                    <p class="stat-value"><?= (int) $active ?></p>
                    <p class="stat-label">Active Staff</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">X</div>
                    <p class="stat-value"><?= (int) $suspended ?></p>
                    <p class="stat-label">Suspended</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">+</div>
                    <p class="stat-value"><?= count($recent) ?></p>
                    <p class="stat-label">Recent Joins</p>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2>Recent Joins</h2>
                    <a href="<?= BASE_URL ?>/admin/staff-list.php" class="btn btn-primary btn-sm">View All</a>
                </div>
                <?php if (empty($recent)): ?>
                    <p>No staff yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Position</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent as $s): ?>
                                    <tr>
                                        <td><?= esc($s['full_name']) ?></td>
                                        <td><?= esc($s['email']) ?></td>
                                        <td><?= esc($s['position'] ?? '-') ?></td>
                                        <td><?= format_date($s['date_joined']) ?></td>
                                        <td><span class="badge <?= status_badge_class($s['status']) ?>"><?= esc(ucfirst($s['status'])) ?></span></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/admin/view-staff.php?id=<?= (int) $s['id'] ?>" class="btn btn-primary btn-sm">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
