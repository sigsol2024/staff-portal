<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

require_admin_login();
require_admin_only();

$id = (int) ($_POST['id'] ?? 0);
if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['csrf_token'] ?? '')) {
    header('Location: ' . BASE_URL . '/admin/staff-list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT profile_image FROM staff WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    set_flash('error', 'Staff not found.');
    header('Location: ' . BASE_URL . '/admin/staff-list.php');
    exit;
}

delete_profile_image($row['profile_image']);

$stmt = $pdo->prepare("DELETE FROM staff WHERE id = ?");
$stmt->execute([$id]);

$stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action, staff_id) VALUES (?, 'delete', ?)");
$stmt->execute([current_admin_id(), $id]);

set_flash('success', 'Staff deleted.');
header('Location: ' . BASE_URL . '/admin/staff-list.php');
exit;
