<?php
/**
 * Session security helpers
 * Regenerate session ID after login to prevent fixation
 */

if (!defined('STAFF_PORTAL')) {
    die('Direct access not permitted');
}

/**
 * Regenerate session ID (call after successful login)
 */
function regenerate_session(): void
{
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();
}
