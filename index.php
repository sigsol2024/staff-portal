<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/config/config.php';

// Redirect logged-in users
if (!empty($_SESSION['staff_id'])) {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

header('Location: ' . BASE_URL . '/login.php?type=staff');
exit;
