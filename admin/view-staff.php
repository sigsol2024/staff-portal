<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . '/admin/staff-list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$id]);
$staff = $stmt->fetch();
if (!$staff) {
    set_flash('error', 'Staff not found.');
    header('Location: ' . BASE_URL . '/admin/staff-list.php');
    exit;
}

$profile_img = staff_profile_image($staff['profile_image']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Staff - <?= esc($staff['full_name']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/../includes/admin_layout.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>View Staff</h1>
                <div style="display:flex;gap:0.5rem;">
                    <?php if ($staff['status'] === 'active'): ?>
                        <a href="<?= BASE_URL ?>/admin/export-pdf.php?id=<?= $id ?>&download=1" class="btn btn-primary" target="_blank">Download PDF</a>
                        <a href="<?= BASE_URL ?>/admin/export-csv.php?id=<?= $id ?>" class="btn btn-accent">Download CSV</a>
                    <?php endif; ?>
                    <?php if (is_admin_role()): ?>
                    <a href="<?= BASE_URL ?>/admin/edit-staff.php?id=<?= $id ?>" class="btn btn-accent">Edit</a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/admin/staff-list.php" class="btn btn-primary">Back to List</a>
                </div>
            </div>
            <div class="card view-staff-card">
                <div class="view-staff-profile">
                    <div class="view-staff-avatar-wrap">
                        <img src="<?= esc($profile_img) ?>" alt="Profile" class="profile-img-lg">
                        <?php if (!empty($staff['profile_image'])): ?>
                        <div class="document-actions">
                            <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=profile" class="btn btn-sm btn-primary" target="_blank" rel="noopener">View</a>
                            <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=profile&download=1" class="btn btn-sm btn-accent">Download</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="view-staff-details">
                        <div class="view-staff-header">
                            <h2 class="view-staff-name"><?= esc($staff['full_name']) ?></h2>
                            <span class="badge <?= status_badge_class($staff['status']) ?>"><?= esc(ucfirst($staff['status'])) ?></span>
                        </div>
                        <dl class="view-staff-meta">
                            <dt>Email</dt>
                            <dd><?= esc($staff['email']) ?></dd>
                            <dt>Phone</dt>
                            <dd><?= esc($staff['phone_number'] ?? '-') ?></dd>
                            <dt>Gender</dt>
                            <dd><?= esc($staff['gender'] ?? '-') ?></dd>
                            <dt>Marital status</dt>
                            <dd><?= esc($staff['marital_status'] ?? '-') ?></dd>
                            <dt>Job title</dt>
                            <dd><?= esc($staff['position'] ?? '-') ?></dd>
                            <dt>Date of birth</dt>
                            <dd><?= format_date($staff['date_of_birth']) ?></dd>
                            <dt>Date joined</dt>
                            <dd><?= format_date($staff['date_joined']) ?></dd>
                            <?php if (!empty($staff['address'])): ?>
                            <dt>Residential address</dt>
                            <dd><?= nl2br(esc($staff['address'])) ?></dd>
                            <?php endif; ?>
                        </dl>
                        <?php if (!empty($staff['biography'])): ?>
                            <div class="view-staff-bio">
                                <h3>Biography</h3>
                                <p><?= nl2br(esc($staff['biography'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php
            $has_employment = !empty($staff['employee_id']) || !empty($staff['department']) || !empty($staff['employment_type']) || !empty($staff['reporting_manager']) || !empty($staff['work_location']) || !empty($staff['confirmation_date']);
            $has_salary = isset($staff['basic_salary']) && $staff['basic_salary'] !== '' && $staff['basic_salary'] !== null;
            $has_bank = !empty($staff['bank_name']) || !empty($staff['account_number']);
            $has_statutory = !empty($staff['tax_identification_number']) || !empty($staff['pension_fund_administrator']) || !empty($staff['pension_pin']);
            if ($has_employment || $has_salary || $has_bank || $has_statutory):
            ?>
            <div class="card view-staff-card">
                <h3 class="form-section-title">Employment &amp; other details</h3>
                <dl class="view-staff-meta">
                    <?php if (!empty($staff['employee_id'])): ?><dt>Employee ID</dt><dd><?= esc($staff['employee_id']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['department'])): ?><dt>Department</dt><dd><?= esc($staff['department']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['employment_type'])): ?><dt>Employment type</dt><dd><?= esc($staff['employment_type']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['confirmation_date'])): ?><dt>Confirmation date</dt><dd><?= format_date($staff['confirmation_date']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['reporting_manager'])): ?><dt>Reporting manager</dt><dd><?= esc($staff['reporting_manager']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['work_location'])): ?><dt>Work location</dt><dd><?= esc($staff['work_location']) ?></dd><?php endif; ?>
                    <?php if ($has_salary): ?><dt>Basic salary</dt><dd><?= esc($staff['basic_salary']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['gross_monthly_salary'])): ?><dt>Gross monthly salary</dt><dd><?= esc($staff['gross_monthly_salary']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['bank_name'])): ?><dt>Bank name</dt><dd><?= esc($staff['bank_name']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['account_name'])): ?><dt>Account name</dt><dd><?= esc($staff['account_name']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['account_number'])): ?><dt>Account number</dt><dd><?= esc($staff['account_number']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['tax_identification_number'])): ?><dt>TIN</dt><dd><?= esc($staff['tax_identification_number']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['pension_fund_administrator'])): ?><dt>PFA</dt><dd><?= esc($staff['pension_fund_administrator']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['pension_pin'])): ?><dt>Pension PIN</dt><dd><?= esc($staff['pension_pin']) ?></dd><?php endif; ?>
                    <?php if (!empty($staff['nhis_hmo_provider'])): ?><dt>NHIS/HMO</dt><dd><?= esc($staff['nhis_hmo_provider']) ?></dd><?php endif; ?>
                </dl>
            </div>
            <?php endif; ?>

            <?php
            $has_docs = !empty($staff['profile_image']) || !empty($staff['cv_path']) || !empty($staff['nin_document_path']);
            if ($has_docs):
            ?>
            <div class="card view-staff-card">
                <h3 class="form-section-title">Documents</h3>
                <p class="form-hint">View or download documents uploaded by staff. Available to admin and manager.</p>
                <dl class="view-staff-meta view-staff-documents">
                    <?php if (!empty($staff['profile_image'])): ?>
                    <dt>Passport photograph</dt>
                    <dd>
                        <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=profile" class="btn btn-sm btn-primary" target="_blank" rel="noopener">View</a>
                        <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=profile&download=1" class="btn btn-sm btn-accent">Download</a>
                    </dd>
                    <?php endif; ?>
                    <?php if (!empty($staff['cv_path'])): ?>
                    <dt>CV</dt>
                    <dd>
                        <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=cv" class="btn btn-sm btn-primary" target="_blank" rel="noopener">View</a>
                        <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=cv&download=1" class="btn btn-sm btn-accent">Download</a>
                    </dd>
                    <?php endif; ?>
                    <?php if (!empty($staff['nin_document_path'])): ?>
                    <dt>NIN document</dt>
                    <dd>
                        <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=nin" class="btn btn-sm btn-primary" target="_blank" rel="noopener">View</a>
                        <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=nin&download=1" class="btn btn-sm btn-accent">Download</a>
                    </dd>
                    <?php endif; ?>
                </dl>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
