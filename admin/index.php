<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
header('Location: ' . BASE_URL . '/admin/dashboard.php');
exit;
