<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$status_filter = $_GET['status'] ?? '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
    $stmt->execute([$id]);
    $rows = $stmt->fetchAll();
} else {
    if ($status_filter === 'active' || $status_filter === 'suspended') {
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE status = ? ORDER BY full_name");
        $stmt->execute([$status_filter]);
    } else {
        $stmt = $pdo->query("SELECT * FROM staff ORDER BY full_name");
    }
    $rows = $stmt->fetchAll();
}

$fpdf_path = ROOT_PATH . '/lib/fpdf/fpdf.php';
if (!file_exists($fpdf_path)) {
    $install_url = BASE_URL . '/lib/fpdf/install_fpdf.php';
    set_flash('error', 'PDF export requires FPDF. Visit ' . $install_url . ' to install, or manually download from https://www.fpdf.org/ and place fpdf.php in lib/fpdf/');
    header('Location: ' . BASE_URL . '/admin/staff-list.php');
    exit;
}

require_once $fpdf_path;

class StaffPDF extends FPDF
{
    public function Header()
    {
        $this->SetFillColor(10, 31, 68); // Navy
        $this->Rect(0, 0, $this->w, 20, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Staff Management Portal - Staff Export', 0, 1, 'L');
        $this->SetFillColor(134, 201, 62); // Accent #86c93e
        $this->Rect(0, 20, $this->w, 4, 'F');
        $this->SetY(30);
        $this->SetTextColor(0, 0, 0);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Generated: ' . date('Y-m-d H:i') . ' | Staff Management Portal', 0, 0, 'C');
    }
}

$pdf = new StaffPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 9);

if (empty($rows)) {
    $pdf->Cell(0, 8, 'No staff records.', 0, 1);
} else {
    $w = [40, 50, 35, 25, 25, 20];
    $headers = ['Name', 'Email', 'Position', 'DOB', 'Joined', 'Status'];
    foreach ($headers as $i => $h) {
        $pdf->Cell($w[$i], 7, $h, 1, 0, 'B');
    }
    $pdf->Ln();
    foreach ($rows as $row) {
        $pdf->Cell($w[0], 7, substr($row['full_name'], 0, 25), 1, 0);
        $pdf->Cell($w[1], 7, substr($row['email'], 0, 30), 1, 0);
        $pdf->Cell($w[2], 7, substr($row['position'] ?? '-', 0, 20), 1, 0);
        $pdf->Cell($w[3], 7, format_date($row['date_of_birth']), 1, 0);
        $pdf->Cell($w[4], 7, format_date($row['date_joined']), 1, 0);
        $pdf->Cell($w[5], 7, ucfirst($row['status']), 1, 0);
        $pdf->Ln();
    }
}

$filename = 'staff_export_' . ($id ? $id : 'all') . '_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $filename);
exit;
