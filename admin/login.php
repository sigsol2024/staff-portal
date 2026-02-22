<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
if (!empty($_SESSION['staff_id'])) {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}
$_GET['type'] = 'admin';
require_once __DIR__ . '/../login.php';
