<?php
/**
 * Profile image upload handling
 * JPG/PNG only, max 2MB
 */

if (!defined('STAFF_PORTAL')) {
    die('Direct access not permitted');
}

define('ALLOWED_TYPES', ['image/jpeg', 'image/jpg', 'image/png']);

/**
 * Validate and process profile image upload
 * Returns new filename on success, or error message string
 */
function handle_profile_upload(array $file): string|false
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_TYPES)) {
        return false;
    }

    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return false;
    }

    $ext = match ($mime) {
        'image/jpeg', 'image/jpg' => 'jpg',
        'image/png' => 'png',
        default => false,
    };

    if (!$ext) {
        return false;
    }

    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }

    $filename = uniqid('', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
    if (!str_ends_with(strtolower($filename), '.jpg') && !str_ends_with(strtolower($filename), '.png')) {
        $filename .= '.' . $ext;
    }

    if (move_uploaded_file($file['tmp_name'], UPLOAD_PATH . $filename)) {
        return $filename;
    }

    return false;
}

/**
 * Delete profile image from server
 */
function delete_profile_image(?string $filename): bool
{
    if (empty($filename)) return true;
    $path = UPLOAD_PATH . $filename;
    if (file_exists($path)) {
        return unlink($path);
    }
    return true;
}
