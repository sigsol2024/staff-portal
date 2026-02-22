<?php
/**
 * Staff export as PDF – client-side (html2pdf).
 * One-click: open with ?download=1 to auto-trigger PDF download. No second click.
 * Single staff = one card with photo + details. List = one card per staff with photo.
 */
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$status_filter = $_GET['status'] ?? '';
$auto_download = isset($_GET['download']) && $_GET['download'] === '1';

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
$is_single = count($rows) === 1;
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
        .pdf-footer { padding: 12px 24px; border-top: 1px solid #F4F6F9; font-size: 12px; color: #6B7280; }
        /* Single staff card */
        .pdf-single-card { display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap; }
        .pdf-single-card .pdf-photo { flex-shrink: 0; width: 120px; height: 120px; border-radius: 8px; overflow: hidden; background: #F4F6F9; }
        .pdf-single-card .pdf-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .pdf-single-card .pdf-details { flex: 1; min-width: 200px; }
        .pdf-single-card .pdf-name { font-size: 20px; font-weight: 700; color: #0A1F44; margin: 0 0 8px 0; }
        .pdf-single-card .pdf-meta { margin: 0; }
        .pdf-single-card .pdf-meta dt { font-weight: 600; color: #6B7280; margin-top: 10px; margin-bottom: 2px; font-size: 12px; }
        .pdf-single-card .pdf-meta dd { margin: 0; }
        .pdf-single-card .pdf-badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; margin-bottom: 8px; }
        .pdf-single-card .pdf-badge.active { background: #d1fae8; color: #065f46; }
        .pdf-single-card .pdf-badge.suspended { background: #fee2e2; color: #991b1b; }
        /* List: one card per staff */
        .pdf-staff-cards { display: grid; gap: 16px; }
        .pdf-staff-card { display: flex; gap: 16px; align-items: center; padding: 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fafafa; }
        .pdf-staff-card .pdf-photo { width: 56px; height: 56px; border-radius: 8px; overflow: hidden; background: #F4F6F9; flex-shrink: 0; }
        .pdf-staff-card .pdf-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .pdf-staff-card .pdf-info { flex: 1; min-width: 0; }
        .pdf-staff-card .pdf-name { font-weight: 700; color: #0A1F44; margin: 0 0 4px 0; font-size: 15px; }
        .pdf-staff-card .pdf-email { color: #6B7280; font-size: 13px; margin: 0 0 2px 0; }
        .pdf-staff-card .pdf-role { font-size: 13px; margin: 0; }
        .pdf-staff-card .pdf-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-top: 4px; }
        .pdf-staff-card .pdf-badge.active { background: #d1fae8; color: #065f46; }
        .pdf-staff-card .pdf-badge.suspended { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="pdf-export-actions">
        <button type="button" class="btn-download" id="btn-download-pdf">Download PDF</button>
        <?php if ($id && count($rows)): ?>
            <a href="<?= BASE_URL ?>/admin/view-staff.php?id=<?= (int) $id ?>">← Back to staff</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/admin/staff-list.php">← Back to list</a>
        <?php endif; ?>
    </div>

    <div id="pdf-content">
        <div class="pdf-header"><?= $is_single ? 'Staff profile' : 'Staff list' ?> – Staff Management Portal</div>
        <div class="pdf-header-bar"></div>
        <div class="pdf-body">
            <?php if (empty($rows)): ?>
                <p>No staff records.</p>
            <?php elseif ($is_single): ?>
                <?php $r = $rows[0]; $img = staff_profile_image($r['profile_image'] ?? null); ?>
                <div class="pdf-single-card">
                    <div class="pdf-photo">
                        <img src="<?= esc($img) ?>" alt="">
                    </div>
                    <div class="pdf-details">
                        <span class="pdf-badge <?= ($r['status'] ?? '') === 'active' ? 'active' : 'suspended' ?>"><?= esc(ucfirst($r['status'] ?? '')) ?></span>
                        <h2 class="pdf-name"><?= esc($r['full_name'] ?? '') ?></h2>
                        <dl class="pdf-meta">
                            <dt>Email</dt><dd><?= esc($r['email'] ?? '') ?></dd>
                            <dt>Phone</dt><dd><?= esc($r['phone_number'] ?? '-') ?></dd>
                            <dt>Position</dt><dd><?= esc($r['position'] ?? '-') ?></dd>
                            <dt>Date of birth</dt><dd><?= format_date($r['date_of_birth'] ?? null) ?></dd>
                            <dt>Date joined</dt><dd><?= format_date($r['date_joined'] ?? null) ?></dd>
                            <?php if (!empty($r['department'])): ?><dt>Department</dt><dd><?= esc($r['department']) ?></dd><?php endif; ?>
                            <?php if (!empty($r['address'])): ?><dt>Address</dt><dd><?= esc($r['address']) ?></dd><?php endif; ?>
                        </dl>
                    </div>
                </div>
            <?php else: ?>
                <div class="pdf-staff-cards">
                    <?php foreach ($rows as $r): $img = staff_profile_image($r['profile_image'] ?? null); ?>
                    <div class="pdf-staff-card">
                        <div class="pdf-photo">
                            <img src="<?= esc($img) ?>" alt="">
                        </div>
                        <div class="pdf-info">
                            <h3 class="pdf-name"><?= esc($r['full_name'] ?? '') ?></h3>
                            <p class="pdf-email"><?= esc($r['email'] ?? '') ?></p>
                            <p class="pdf-role"><?= esc($r['position'] ?? '-') ?> · <?= format_date($r['date_joined'] ?? null) ?></p>
                            <span class="pdf-badge <?= ($r['status'] ?? '') === 'active' ? 'active' : 'suspended' ?>"><?= esc(ucfirst($r['status'] ?? '')) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="pdf-footer">Generated <?= date('Y-m-d H:i') ?> | Staff Management Portal</div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" crossorigin="anonymous"></script>
    <script>
    (function() {
        var btn = document.getElementById('btn-download-pdf');
        var content = document.getElementById('pdf-content');
        var filename = '<?= preg_replace('/[^a-z0-9_\-]/i', '_', $filename_base) ?>.pdf';
        var autoDownload = <?= $auto_download ? 'true' : 'false' ?>;

        function runPdf() {
            if (!content) return;
            if (btn) { btn.innerHTML = 'Generating PDF…'; btn.disabled = true; }
            var options = {
                margin: [10, 10, 10, 10],
                filename: filename,
                image: { type: 'jpeg', quality: 0.92 },
                html2canvas: { scale: 1.5, useCORS: true, allowTaint: true, backgroundColor: '#ffffff', logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(options).from(content).save().then(function() {
                if (btn) { btn.innerHTML = 'Download PDF'; btn.disabled = false; }
            }).catch(function(err) {
                console.error(err);
                if (btn) { btn.innerHTML = 'Download PDF'; btn.disabled = false; }
                alert('Failed to generate PDF. Please try again.');
            });
        }

        if (btn) btn.addEventListener('click', runPdf);

        if (autoDownload) {
            setTimeout(runPdf, 800);
        }
    })();
    </script>
</body>
</html>
