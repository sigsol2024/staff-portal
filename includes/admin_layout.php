<?php
/**
 * Admin layout sidebar - call at start of admin pages after require_admin_login
 */
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="sidebar">
    <div class="sidebar-header">Admin Portal</div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= BASE_URL ?>/admin/staff-list.php" class="<?= in_array($current_page, ['staff-list', 'view-staff', 'edit-staff']) ? 'active' : '' ?>">Staff List</a>
        <a href="<?= BASE_URL ?>/admin/settings.php" class="<?= $current_page === 'settings' ? 'active' : '' ?>">Settings</a>
        <a href="<?= BASE_URL ?>/admin/logout.php" class="sidebar-logout">Logout</a>
    </nav>
</aside>
