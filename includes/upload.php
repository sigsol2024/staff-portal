<?php
/**
 * Upload handling: profile image, CV, NIN document
 * Profile: JPG/PNG, max 2MB. CV/NIN: PDF or images, max 5MB.
 */

if (!defined('STAFF_PORTAL')) {
    die('Direct access not permitted');
}

define('ALLOWED_TYPES', ['image/jpeg', 'image/jpg', 'image/png']);
define('ALLOWED_CV_TYPES', ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']);
define('ALLOWED_NIN_TYPES', ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']);

/**
 * Validate and process profile image upload (passport photo)
 * Returns new filename on success, or false
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
 * Validate and process CV upload. PDF or JPG/PNG, max 5MB.
 * Returns new filename on success, or false.
 */
function handle_cv_upload(array $file): string|false
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_CV_TYPES)) {
        return false;
    }

    if ($file['size'] > UPLOAD_DOCUMENT_MAX_SIZE) {
        return false;
    }

    $ext = match ($mime) {
        'application/pdf' => 'pdf',
        'image/jpeg', 'image/jpg' => 'jpg',
        'image/png' => 'png',
        default => false,
    };
    if (!$ext) {
        return false;
    }

    if (!is_dir(UPLOAD_CV_PATH)) {
        mkdir(UPLOAD_CV_PATH, 0755, true);
    }

    $filename = 'cv_' . uniqid('', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], UPLOAD_CV_PATH . $filename)) {
        return $filename;
    }
    return false;
}

/**
 * Validate and process NIN (National Identification Number) document upload. PDF or JPG/PNG, max 5MB.
 * Returns new filename on success, or false.
 */
function handle_nin_document_upload(array $file): string|false
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_NIN_TYPES)) {
        return false;
    }

    if ($file['size'] > UPLOAD_DOCUMENT_MAX_SIZE) {
        return false;
    }

    $ext = match ($mime) {
        'application/pdf' => 'pdf',
        'image/jpeg', 'image/jpg' => 'jpg',
        'image/png' => 'png',
        default => false,
    };
    if (!$ext) {
        return false;
    }

    if (!is_dir(UPLOAD_NIN_PATH)) {
        mkdir(UPLOAD_NIN_PATH, 0755, true);
    }

    $filename = 'nin_' . uniqid('', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], UPLOAD_NIN_PATH . $filename)) {
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

/**
 * Delete CV file from server
 */
function delete_cv_file(?string $filename): bool
{
    if (empty($filename)) return true;
    $path = UPLOAD_CV_PATH . $filename;
    if (file_exists($path)) {
        return unlink($path);
    }
    return true;
}

/**
 * Delete NIN document from server
 */
function delete_nin_document(?string $filename): bool
{
    if (empty($filename)) return true;
    $path = UPLOAD_NIN_PATH . $filename;
    if (file_exists($path)) {
        return unlink($path);
    }
    return true;
}
