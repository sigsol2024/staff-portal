<?php
/**
 * One-time FPDF installer. Run this in browser to download FPDF.
 * Delete this file after FPDF is installed.
 */
$url = 'https://raw.githubusercontent.com/Setasign/FPDF/master/fpdf.php';
$dest = dirname(__FILE__) . '/fpdf.php';

if (file_exists($dest)) {
    die('FPDF already installed. Delete this file.');
}

$ctx = stream_context_create(['http' => ['timeout' => 30]]);
$content = @file_get_contents($url, false, $ctx);

if ($content === false || strpos($content, 'class FPDF') === false) {
    die('Could not download FPDF. Please manually download from https://www.fpdf.org/ and place fpdf.php in lib/fpdf/');
}

if (file_put_contents($dest, $content) === false) {
    die('Could not write fpdf.php. Check folder permissions.');
}

echo 'FPDF installed successfully. Delete this file (lib/fpdf/install_fpdf.php) for security.';
