<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];
if ($search) {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR position LIKE ?)";
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term]);
}
if ($status_filter === 'active' || $status_filter === 'suspended') {
    $where[] = "status = ?";
    $params[] = $status_filter;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count_sql = "SELECT COUNT(*) FROM staff $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$total_pages = max(1, (int) ceil($total / $per_page));

$sql = "SELECT * FROM staff $where_sql ORDER BY full_name ASC LIMIT " . (int) $per_page . " OFFSET " . (int) $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$staff_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff List - Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/../includes/admin_layout.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>Staff List</h1>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    <a href="<?= BASE_URL ?>/admin/export-pdf.php?download=1<?= $status_filter ? '&status=' . rawurlencode($status_filter) : '' ?>" class="btn btn-accent btn-sm" target="_blank">Download All (PDF)</a>
                    <a href="<?= BASE_URL ?>/admin/export-csv.php" class="btn btn-accent btn-sm">Download All (CSV)</a>
                    <?php if (is_admin_role()): ?>
                    <a href="<?= BASE_URL ?>/admin/add-staff.php" class="btn btn-primary">Add Staff</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="search-bar">
                <form method="GET" action="" style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                    <input type="text" name="search" class="form-control" placeholder="Search name, email, position..."
                           value="<?= esc($search) ?>" style="max-width:250px;">
                    <select name="status" class="form-control" style="max-width:120px;">
                        <option value="">All Status</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $status_filter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($staff_list)): ?>
                                <tr><td colspan="8">No staff found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($staff_list as $s): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= esc(staff_profile_image($s['profile_image'])) ?>" alt="" class="profile-img">
                                        </td>
                                        <td><?= esc($s['full_name']) ?></td>
                                        <td><?= esc($s['email']) ?></td>
                                        <td><?= esc($s['position'] ?? '-') ?></td>
                                        <td><span class="badge <?= status_badge_class($s['status']) ?>"><?= esc(ucfirst($s['status'])) ?></span></td>
                                        <td><?= format_date($s['date_joined']) ?></td>
                                        <td class="table-actions">
                                            <div class="dropdown-wrap">
                                                <button type="button" class="btn btn-dropdown" aria-label="Actions" onclick="var m=this.nextElementSibling; document.querySelectorAll('.dropdown-menu.open').forEach(function(x){if(x!==m)x.classList.remove('open');}); m.classList.toggle('open'); if(m.classList.contains('open')){var r=this.getBoundingClientRect(); m.style.top=(r.bottom+4)+'px'; m.style.left=Math.max(8,Math.min(r.right-140,r.left))+'px';}">&#8230;</button>
                                                <div class="dropdown-menu">
                                                    <a href="<?= BASE_URL ?>/admin/view-staff.php?id=<?= (int) $s['id'] ?>">View</a>
                                                    <?php if (is_admin_role()): ?>
                                                    <a href="<?= BASE_URL ?>/admin/edit-staff.php?id=<?= (int) $s['id'] ?>">Edit</a>
                                                    <?php endif; ?>
                                                    <?php if ($s['status'] === 'active'): ?>
                                                        <form method="POST" action="<?= BASE_URL ?>/admin/suspend-staff.php" onsubmit="return confirm('Suspend this staff?');">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                                            <button type="submit" class="dropdown-btn">Suspend</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" action="<?= BASE_URL ?>/admin/activate-staff.php">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                                            <button type="submit" class="dropdown-btn">Activate</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if (is_admin_role()): ?>
                                                    <form method="POST" action="<?= BASE_URL ?>/admin/delete-staff.php" onsubmit="return confirm('Delete this staff? Profile image will be removed.');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                                        <button type="submit" class="dropdown-btn dropdown-btn-danger">Delete</button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        $q = http_build_query(array_filter(['search' => $search ?: null, 'status' => $status_filter ?: null]));
                        $base = BASE_URL . '/admin/staff-list.php' . ($q ? '?' . $q . '&' : '?');
                        if ($page > 1): ?>
                            <a href="<?= $base ?>page=<?= $page - 1 ?>">Previous</a>
                        <?php endif;
                        for ($i = 1; $i <= $total_pages; $i++):
                            if ($i == $page): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="<?= $base ?>page=<?= $i ?>"><?= $i ?></a>
                            <?php endif;
                        endfor;
                        if ($page < $total_pages): ?>
                            <a href="<?= $base ?>page=<?= $page + 1 ?>">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
