<?php
define('STAFF_PORTAL', true);
define('SKIP_HTTP_HEADERS', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$status_filter = $_GET['status'] ?? '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
    $stmt->execute([$id]);
    $rows = $stmt->fetchAll();
} else {
    if ($status_filter === 'active' || $status_filter === 'suspended') {
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE status = ? ORDER BY full_name");
        $stmt->execute([$status_filter]);
    } else {
        $stmt = $pdo->query("SELECT * FROM staff ORDER BY full_name");
    }
    $rows = $stmt->fetchAll();
}

$filename = $id ? 'staff_' . $id . '.csv' : 'staff_all.csv';
if ($status_filter) {
    $filename = 'staff_' . $status_filter . '.csv';
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Full Name', 'Email', 'Phone', 'Gender', 'Position', 'Date of Birth', 'Date Joined', 'Status', 'Address', 'Biography']);

foreach ($rows as $row) {
    fputcsv($out, [
        $row['id'],
        $row['full_name'],
        $row['email'],
        $row['phone_number'] ?? '',
        $row['gender'] ?? '',
        $row['position'] ?? '',
        $row['date_of_birth'] ?? '',
        $row['date_joined'] ?? '',
        $row['status'],
        $row['address'] ?? '',
        $row['biography'] ?? '',
    ]);
}
fclose($out);
exit;
