<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_login();

$id = (int) ($_POST['id'] ?? 0);
if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['csrf_token'] ?? '')) {
    header('Location: ' . BASE_URL . '/admin/staff-list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM staff WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    header('Location: ' . BASE_URL . '/admin/staff-list.php');
    exit;
}

$stmt = $pdo->prepare("UPDATE staff SET status = 'active', updated_at = NOW() WHERE id = ?");
$stmt->execute([$id]);

$stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action, staff_id) VALUES (?, 'activate', ?)");
$stmt->execute([current_admin_id(), $id]);

require_once __DIR__ . '/../includes/functions.php';
set_flash('success', 'Staff activated.');
header('Location: ' . BASE_URL . '/admin/staff-list.php');
exit;
