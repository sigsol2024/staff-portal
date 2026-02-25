<?php
/**
 * Staff layout sidebar - include on staff pages after require_staff_login()
 */
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="sidebar">
    <div class="sidebar-header">Staff Portal</div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/user/dashboard.php" class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= BASE_URL ?>/user/settings.php" class="<?= $current_page === 'settings' ? 'active' : '' ?>">Settings</a>
        <a href="<?= BASE_URL ?>/user/logout.php" class="sidebar-logout">Logout</a>
    </nav>
</aside>
