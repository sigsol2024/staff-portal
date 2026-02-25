<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_only();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/staff-list.php');
    exit;
}

if (!validate_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid request. Please try again.');
    header('Location: ' . BASE_URL . '/admin/staff-list.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$enabled = isset($_POST['enabled']) ? (int) $_POST['enabled'] : 1;
$enabled = $enabled === 1 ? 1 : 0;

if (!$id) {
    set_flash('error', 'Invalid staff ID.');
    header('Location: ' . BASE_URL . '/admin/staff-list.php');
    exit;
}

$stmt = $pdo->prepare("UPDATE staff SET profile_edit_enabled = ?, updated_at = NOW() WHERE id = ?");
$stmt->execute([$enabled, $id]);

set_flash('success', $enabled ? 'Profile editing enabled for this staff.' : 'Profile editing disabled for this staff.');
header('Location: ' . BASE_URL . '/admin/view-staff.php?id=' . $id);
exit;

