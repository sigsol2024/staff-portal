<?php
/**
 * View or download staff documents (profile image, CV, NIN document).
 * Accessible by both admin and manager.
 */
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_login();

$id = (int) ($_GET['id'] ?? 0);
$type = $_GET['type'] ?? '';
$download = !empty($_GET['download']);

if (!$id || !in_array($type, ['profile', 'cv', 'nin'], true)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request.');
}

$stmt = $pdo->prepare("SELECT full_name, profile_image, cv_path, nin_document_path FROM staff WHERE id = ?");
$stmt->execute([$id]);
$staff = $stmt->fetch();
if (!$staff) {
    header('HTTP/1.1 404 Not Found');
    exit('Staff not found.');
}

$filename = null;
$filepath = null;
$mime = 'application/octet-stream';
$displayName = '';

switch ($type) {
    case 'profile':
        $filename = $staff['profile_image'];
        if ($filename) {
            $filepath = UPLOAD_PATH . $filename;
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
            $displayName = 'profile-photo-' . preg_replace('/[^a-z0-9_-]/i', '-', $staff['full_name']) . '.' . $ext;
        }
        break;
    case 'cv':
        $filename = $staff['cv_path'];
        if ($filename) {
            $filepath = UPLOAD_CV_PATH . $filename;
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mime = $ext === 'pdf' ? 'application/pdf' : ($ext === 'png' ? 'image/png' : 'image/jpeg');
            $displayName = 'cv-' . preg_replace('/[^a-z0-9_-]/i', '-', $staff['full_name']) . '.' . $ext;
        }
        break;
    case 'nin':
        $filename = $staff['nin_document_path'];
        if ($filename) {
            $filepath = UPLOAD_NIN_PATH . $filename;
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mime = $ext === 'pdf' ? 'application/pdf' : ($ext === 'png' ? 'image/png' : 'image/jpeg');
            $displayName = 'nin-document-' . preg_replace('/[^a-z0-9_-]/i', '-', $staff['full_name']) . '.' . $ext;
        }
        break;
}

if (!$filename || !$filepath || !file_exists($filepath) || !is_readable($filepath)) {
    header('HTTP/1.1 404 Not Found');
    exit('Document not found.');
}

$disposition = $download ? 'attachment' : 'inline';
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '\\"', $displayName) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, max-age=3600');
readfile($filepath);
exit;
