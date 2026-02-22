<?php
/**
 * Staff export as PDF – client-side generation (html2pdf), like Cosmopolitan Bank receipt.
 * No server-side PDF library required. Opens a printable page; user clicks "Download PDF".
 */
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
    $title = count($rows) ? esc($rows[0]['full_name']) . ' – Staff Export' : 'Staff Export';
} else {
    if ($status_filter === 'active' || $status_filter === 'suspended') {
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE status = ? ORDER BY full_name");
        $stmt->execute([$status_filter]);
    } else {
        $stmt = $pdo->query("SELECT * FROM staff ORDER BY full_name");
    }
    $rows = $stmt->fetchAll();
    $title = $status_filter ? ucfirst($status_filter) . ' staff export' : 'All staff export';
}

$filename_base = 'staff_export_' . ($id ? $id : ($status_filter ?: 'all')) . '_' . date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Staff Portal</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; background: #F4F6F9; color: #0A1F44; padding: 24px; }
        .pdf-export-actions { margin-bottom: 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .pdf-export-actions a { color: #0A1F44; text-decoration: none; }
        .pdf-export-actions a:hover { color: #86c93e; }
        .btn-download { display: inline-flex; align-items: center; gap: 8px; background: #86c93e; color: #0A1F44; font-weight: 600; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; }
        .btn-download:hover { background: #b8d600; }
        .btn-download:disabled { opacity: 0.7; cursor: not-allowed; }
        #pdf-content { background: #fff; max-width: 210mm; margin: 0 auto; box-shadow: 0 2px 8px rgba(10,31,68,0.08); border-radius: 8px; overflow: hidden; }
        .pdf-header { background: #0A1F44; color: #fff; padding: 16px 24px; font-weight: 600; font-size: 18px; }
        .pdf-header-bar { height: 4px; background: #86c93e; }
        .pdf-body { padding: 24px; font-size: 14px; line-height: 1.5; }
        .pdf-body table { width: 100%; border-collapse: collapse; }
        .pdf-body th, .pdf-body td { border: 1px solid #e5e7eb; padding: 8px 10px; text-align: left; }
        .pdf-body th { background: #F4F6F9; font-weight: 600; }
        .pdf-body .single-staff dl { margin: 0; }
        .pdf-body .single-staff dt { font-weight: 600; color: #6B7280; margin-top: 12px; margin-bottom: 4px; }
        .pdf-body .single-staff dd { margin: 0; }
        .pdf-footer { padding: 12px 24px; border-top: 1px solid #F4F6F9; font-size: 12px; color: #6B7280; }
    </style>
</head>
<body>
    <div class="pdf-export-actions">
        <button type="button" class="btn-download" id="btn-download-pdf">
            Download PDF
        </button>
        <?php if ($id && count($rows)): ?>
            <a href="<?= BASE_URL ?>/admin/view-staff.php?id=<?= (int) $id ?>">← Back to staff</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/admin/staff-list.php">← Back to list</a>
        <?php endif; ?>
    </div>

    <div id="pdf-content">
        <div class="pdf-header">Staff Management Portal – Staff Export</div>
        <div class="pdf-header-bar"></div>
        <div class="pdf-body">
            <?php if (empty($rows)): ?>
                <p>No staff records.</p>
            <?php elseif (count($rows) === 1): ?>
                <?php $r = $rows[0]; ?>
                <div class="single-staff">
                    <dl>
                        <dt>Name</dt><dd><?= esc($r['full_name'] ?? '') ?></dd>
                        <dt>Email</dt><dd><?= esc($r['email'] ?? '') ?></dd>
                        <dt>Phone</dt><dd><?= esc($r['phone_number'] ?? '-') ?></dd>
                        <dt>Position</dt><dd><?= esc($r['position'] ?? '-') ?></dd>
                        <dt>Date of birth</dt><dd><?= format_date($r['date_of_birth'] ?? null) ?></dd>
                        <dt>Date joined</dt><dd><?= format_date($r['date_joined'] ?? null) ?></dd>
                        <dt>Status</dt><dd><?= esc(ucfirst($r['status'] ?? '')) ?></dd>
                    </dl>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th><th>Email</th><th>Position</th><th>DOB</th><th>Joined</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= esc($r['full_name'] ?? '') ?></td>
                            <td><?= esc($r['email'] ?? '') ?></td>
                            <td><?= esc($r['position'] ?? '-') ?></td>
                            <td><?= format_date($r['date_of_birth'] ?? null) ?></td>
                            <td><?= format_date($r['date_joined'] ?? null) ?></td>
                            <td><?= esc(ucfirst($r['status'] ?? '')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div class="pdf-footer">Generated <?= date('Y-m-d H:i') ?> | Staff Management Portal</div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" crossorigin="anonymous"></script>
    <script>
    (function() {
        var btn = document.getElementById('btn-download-pdf');
        var content = document.getElementById('pdf-content');
        if (!btn || !content) return;

        btn.addEventListener('click', function() {
            var originalHtml = btn.innerHTML;
            btn.innerHTML = 'Generating PDF…';
            btn.disabled = true;

            var options = {
                margin: [10, 10, 10, 10],
                filename: '<?= preg_replace('/[^a-z0-9_\-]/i', '_', $filename_base) ?>.pdf',
                image: { type: 'jpeg', quality: 0.92 },
                html2canvas: { scale: 1.5, useCORS: true, backgroundColor: '#ffffff', logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(options).from(content).save().then(function() {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }).catch(function(err) {
                console.error(err);
                alert('Failed to generate PDF. Please try again.');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
        });
    })();
    </script>
</body>
</html>
