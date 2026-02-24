<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();
require_admin_only();

$id = (int) ($_POST['id'] ?? 0);
if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid request.');
    header('Location: ' . BASE_URL . '/admin/admins.php');
    exit;
}

if ($id === current_admin_id()) {
    set_flash('error', 'You cannot delete your own account.');
    header('Location: ' . BASE_URL . '/admin/admins.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM admins WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    set_flash('error', 'Account not found.');
    header('Location: ' . BASE_URL . '/admin/admins.php');
    exit;
}

$pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]);
set_flash('success', 'Account deleted.');
header('Location: ' . BASE_URL . '/admin/admins.php');
exit;
