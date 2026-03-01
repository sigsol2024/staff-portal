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
$flash = get_flash();
$edit_enabled = (int) ($staff['profile_edit_enabled'] ?? 1);
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
                    <form method="POST" action="<?= BASE_URL ?>/admin/toggle-profile-edit.php" style="margin:0; display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$id ?>">
                        <?php if ($edit_enabled === 1): ?>
                            <input type="hidden" name="enabled" value="0">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Disable profile editing for this staff? They will not be able to access the profile page.');">Disable profile editing</button>
                        <?php else: ?>
                            <input type="hidden" name="enabled" value="1">
                            <button type="submit" class="btn btn-accent">Enable profile editing</button>
                        <?php endif; ?>
                    </form>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/admin/staff-list.php" class="btn btn-primary">Back to List</a>
                </div>
            </div>
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['message']) ?></div>
            <?php endif; ?>
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
                            <dt>Profile editing</dt>
                            <dd>
                                <?php if ($edit_enabled === 1): ?>
                                    <span class="badge badge-success">Enabled</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Disabled</span>
                                <?php endif; ?>
                            </dd>
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
                            <dt>Residential address</dt>
                            <dd><?= !empty($staff['address']) ? nl2br(esc($staff['address'])) : '-' ?></dd>
                            <dt>Biography</dt>
                            <dd><?= !empty($staff['biography']) ? nl2br(esc($staff['biography'])) : '-' ?></dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="card view-staff-card">
                <h3 class="form-section-title">Employment Details</h3>
                <dl class="view-staff-meta">
                    <dt>Employee ID</dt><dd><?= esc($staff['employee_id'] ?? '-') ?></dd>
                    <dt>Department</dt><dd><?= esc($staff['department'] ?? '-') ?></dd>
                    <dt>Role</dt><dd><?= esc($staff['role'] ?? '-') ?></dd>
                    <dt>Employment type</dt><dd><?= esc($staff['employment_type'] ?? '-') ?></dd>
                    <dt>Start date</dt><dd><?= format_date($staff['date_joined']) ?></dd>
                    <dt>Confirmation date</dt><dd><?= format_date($staff['confirmation_date']) ?></dd>
                    <dt>Reporting manager</dt><dd><?= esc($staff['reporting_manager'] ?? '-') ?></dd>
                    <dt>Work location</dt><dd><?= esc($staff['work_location'] ?? '-') ?></dd>
                </dl>
            </div>

            <div class="card view-staff-card">
                <h3 class="form-section-title">Salary Structure</h3>
                <dl class="view-staff-meta">
                    <dt>Basic salary</dt><dd><?= esc($staff['basic_salary'] ?? '-') ?></dd>
                    <dt>Housing allowance</dt><dd><?= esc($staff['housing_allowance'] ?? '-') ?></dd>
                    <dt>Transport allowance</dt><dd><?= esc($staff['transport_allowance'] ?? '-') ?></dd>
                    <dt>Telephone allowance</dt><dd><?= esc($staff['telephone_allowance'] ?? '-') ?></dd>
                    <dt>Other allowance</dt><dd><?= esc($staff['other_allowance'] ?? '-') ?></dd>
                    <dt>Gross monthly salary</dt><dd><?= esc($staff['gross_monthly_salary'] ?? '-') ?></dd>
                </dl>
            </div>

            <div class="card view-staff-card">
                <h3 class="form-section-title">Bank Details</h3>
                <dl class="view-staff-meta">
                    <dt>Bank name</dt><dd><?= esc($staff['bank_name'] ?? '-') ?></dd>
                    <dt>Account name</dt><dd><?= esc($staff['account_name'] ?? '-') ?></dd>
                    <dt>Account number</dt><dd><?= esc($staff['account_number'] ?? '-') ?></dd>
                    <dt>BVN</dt><dd><?= esc($staff['bvn'] ?? '-') ?></dd>
                </dl>
            </div>

            <div class="card view-staff-card">
                <h3 class="form-section-title">Documents</h3>
                <p class="form-hint">View or download documents uploaded by staff. Available to admin and manager.</p>
                <dl class="view-staff-meta view-staff-documents">
                    <dt>Passport photograph</dt>
                    <dd>
                        <?php if (!empty($staff['profile_image'])): ?>
                            <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=profile" class="btn btn-sm btn-primary" target="_blank" rel="noopener">View</a>
                            <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=profile&download=1" class="btn btn-sm btn-accent">Download</a>
                        <?php else: ?>-<?php endif; ?>
                    </dd>
                    <dt>CV</dt>
                    <dd>
                        <?php if (!empty($staff['cv_path'])): ?>
                            <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=cv" class="btn btn-sm btn-primary" target="_blank" rel="noopener">View</a>
                            <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=cv&download=1" class="btn btn-sm btn-accent">Download</a>
                        <?php else: ?>-<?php endif; ?>
                    </dd>
                    <dt>NIN document</dt>
                    <dd>
                        <?php if (!empty($staff['nin_document_path'])): ?>
                            <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=nin" class="btn btn-sm btn-primary" target="_blank" rel="noopener">View</a>
                            <a href="<?= BASE_URL ?>/admin/view-document.php?id=<?= (int)$id ?>&type=nin&download=1" class="btn btn-sm btn-accent">Download</a>
                        <?php else: ?>-<?php endif; ?>
                    </dd>
                </dl>
            </div>

            <div class="card view-staff-card">
                <h3 class="form-section-title">Statutory &amp; Compliance</h3>
                <dl class="view-staff-meta">
                    <dt>TIN</dt><dd><?= esc($staff['tax_identification_number'] ?? '-') ?></dd>
                    <dt>LIRS Tax ID</dt><dd><?= esc($staff['lirs_tax_id'] ?? '-') ?></dd>
                    <dt>PFA</dt><dd><?= esc($staff['pension_fund_administrator'] ?? '-') ?></dd>
                    <dt>Pension PIN</dt><dd><?= esc($staff['pension_pin'] ?? '-') ?></dd>
                    <dt>NHF number</dt><dd><?= esc($staff['nhf_number'] ?? '-') ?></dd>
                    <dt>NHIS/HMO provider</dt><dd><?= esc($staff['nhis_hmo_provider'] ?? '-') ?></dd>
                    <dt>Employee contribution %</dt><dd><?= esc($staff['employee_contribution_percentages'] ?? '-') ?></dd>
                </dl>
            </div>

            <div class="card view-staff-card">
                <h3 class="form-section-title">Payroll Changes</h3>
                <dl class="view-staff-meta">
                    <dt>New hire</dt><dd><?= isset($staff['new_hire']) ? ((int)$staff['new_hire'] === 1 ? 'Yes' : 'No') : '-' ?></dd>
                    <dt>Exit/Termination date</dt><dd><?= format_date($staff['exit_termination_date']) ?></dd>
                    <dt>Salary adjustment notes</dt><dd><?= !empty($staff['salary_adjustment_notes']) ? nl2br(esc($staff['salary_adjustment_notes'])) : '-' ?></dd>
                    <dt>Promotion/Role change</dt><dd><?= !empty($staff['promotion_role_change']) ? nl2br(esc($staff['promotion_role_change'])) : '-' ?></dd>
                    <dt>Bank detail update</dt><dd><?= !empty($staff['bank_detail_update']) ? nl2br(esc($staff['bank_detail_update'])) : '-' ?></dd>
                </dl>
            </div>
        </main>
    </div>
</body>
</html>
