<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_staff_login();

$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$_SESSION['staff_id']]);
$staff = $stmt->fetch();
if (!$staff) {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php?type=staff');
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
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/../includes/staff_layout.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>Dashboard</h1>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                    <?php if ($edit_enabled === 1): ?>
                        <a href="<?= BASE_URL ?>/user/profile.php" class="btn btn-accent">Edit Profile</a>
                    <?php else: ?>
                        <span class="btn btn-danger" title="Profile editing has been disabled by admin.">Profile editing disabled</span>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/user/settings.php" class="btn btn-primary">Settings</a>
                </div>
            </div>
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['message']) ?></div>
            <?php endif; ?>
            <div class="card view-staff-card">
                <div class="view-staff-profile">
                    <div class="view-staff-avatar-wrap">
                        <img src="<?= esc($profile_img) ?>" alt="Profile" class="profile-img-lg">
                        <div class="document-actions">
                            <?php if (!empty($staff['profile_image'])): ?>
                                <a href="<?= BASE_URL ?>/user/view-document.php?type=profile" class="btn btn-sm btn-primary" target="_blank" rel="noopener">View photo</a>
                                <a href="<?= BASE_URL ?>/user/view-document.php?type=profile&download=1" class="btn btn-sm btn-accent">Download</a>
                            <?php else: ?>
                                <span class="badge badge-warning">No photo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="view-staff-details">
                        <div class="view-staff-header">
                            <h2 class="view-staff-name"><?= esc($staff['full_name']) ?></h2>
                            <span class="badge <?= status_badge_class($staff['status']) ?>"><?= esc(ucfirst($staff['status'])) ?></span>
                        </div>
                        <dl class="view-staff-meta">
                            <dt>Email</dt><dd><?= esc($staff['email'] ?? '-') ?></dd>
                            <dt>Phone</dt><dd><?= esc($staff['phone_number'] ?? '-') ?></dd>
                            <dt>Gender</dt><dd><?= esc($staff['gender'] ?? '-') ?></dd>
                            <dt>Marital status</dt><dd><?= esc($staff['marital_status'] ?? '-') ?></dd>
                            <dt>Date of birth</dt><dd><?= format_date($staff['date_of_birth']) ?></dd>
                            <dt>Residential address</dt><dd><?= !empty($staff['address']) ? nl2br(esc($staff['address'])) : '-' ?></dd>
                            <dt>Biography</dt><dd><?= !empty($staff['biography']) ? nl2br(esc($staff['biography'])) : '-' ?></dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="card view-staff-card">
                <h3 class="form-section-title">Employment Details</h3>
                <dl class="view-staff-meta">
                    <dt>Employee ID</dt><dd><?= esc($staff['employee_id'] ?? '-') ?></dd>
                    <dt>Job title</dt><dd><?= esc($staff['position'] ?? '-') ?></dd>
                    <dt>Department</dt><dd><?= esc($staff['department'] ?? '-') ?></dd>
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
                    <dt>Other allowances</dt><dd><?= !empty($staff['other_allowances']) ? nl2br(esc($staff['other_allowances'])) : '-' ?></dd>
                    <dt>Gross monthly salary</dt><dd><?= esc($staff['gross_monthly_salary'] ?? '-') ?></dd>
                    <dt>Overtime rate</dt><dd><?= esc($staff['overtime_rate'] ?? '-') ?></dd>
                    <dt>Bonus/Commission structure</dt><dd><?= !empty($staff['bonus_commission_structure']) ? nl2br(esc($staff['bonus_commission_structure'])) : '-' ?></dd>
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
                <dl class="view-staff-meta view-staff-documents">
                    <dt>CV</dt>
                    <dd>
                        <?php if (!empty($staff['cv_path'])): ?>
                            <a href="<?= BASE_URL ?>/user/view-document.php?type=cv" class="btn btn-sm btn-primary" target="_blank" rel="noopener">View</a>
                            <a href="<?= BASE_URL ?>/user/view-document.php?type=cv&download=1" class="btn btn-sm btn-accent">Download</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </dd>
                    <dt>NIN document</dt>
                    <dd>
                        <?php if (!empty($staff['nin_document_path'])): ?>
                            <a href="<?= BASE_URL ?>/user/view-document.php?type=nin" class="btn btn-sm btn-primary" target="_blank" rel="noopener">View</a>
                            <a href="<?= BASE_URL ?>/user/view-document.php?type=nin&download=1" class="btn btn-sm btn-accent">Download</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>

            <div class="card view-staff-card">
                <h3 class="form-section-title">Statutory &amp; Compliance</h3>
                <dl class="view-staff-meta">
                    <dt>TIN</dt><dd><?= esc($staff['tax_identification_number'] ?? '-') ?></dd>
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
                </dl>
            </div>
        </main>
    </div>
</body>
</html>
