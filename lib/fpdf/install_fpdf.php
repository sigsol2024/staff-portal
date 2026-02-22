<?php
/**
 * One-time FPDF installer. Run this in browser to download FPDF.
 * Delete this file after FPDF is installed.
 */
// Use FPDF 1.86 (original API); master branch is FPDF 2.x and incompatible
$url = 'https://raw.githubusercontent.com/Setasign/FPDF/1.86/fpdf.php';
$dest = dirname(__FILE__) . '/fpdf.php';

if (file_exists($dest)) {
    die('FPDF already installed. To reinstall (e.g. switch to 1.86), delete lib/fpdf/fpdf.php first, then run this again.');
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
