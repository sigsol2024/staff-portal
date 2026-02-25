<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

require_staff_login();

$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$_SESSION['staff_id']]);
$staff = $stmt->fetch();
if (!$staff) {
    header('Location: ' . BASE_URL . '/login.php?type=staff');
    exit;
}

$edit_enabled = (int) ($staff['profile_edit_enabled'] ?? 1);
$global_edit_enabled = (get_portal_setting('staff_profile_edit_global_enabled', '1') ?? '1') === '1';
if (!$global_edit_enabled || $edit_enabled !== 1) {
    set_flash('error', 'Profile editing has been disabled by admin. Please contact HR/Admin.');
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'profile';

        if ($action === 'profile') {
            $full_name = trim($_POST['full_name'] ?? '');
            $date_of_birth = trim($_POST['date_of_birth'] ?? '') ?: null;
            $date_joined = trim($_POST['date_joined'] ?? '') ?: null;
            $biography = trim($_POST['biography'] ?? '') ?: null;
            $phone_number = trim($_POST['phone_number'] ?? '') ?: null;
            $gender = trim($_POST['gender'] ?? '') ?: null;
            $address = trim($_POST['address'] ?? '') ?: null;
            $marital_status = trim($_POST['marital_status'] ?? '') ?: null;
            $employee_id = trim($_POST['employee_id'] ?? '') ?: null;
            $position = trim($_POST['position'] ?? '') ?: null;
            $department = trim($_POST['department'] ?? '') ?: null;
            $employment_type = trim($_POST['employment_type'] ?? '') ?: null;
            $reporting_manager = trim($_POST['reporting_manager'] ?? '') ?: null;
            $work_location = trim($_POST['work_location'] ?? '') ?: null;
            $confirmation_date = trim($_POST['confirmation_date'] ?? '') ?: null;

            $basic_salary = trim($_POST['basic_salary'] ?? '') ?: null;
            $housing_allowance = trim($_POST['housing_allowance'] ?? '') ?: null;
            $transport_allowance = trim($_POST['transport_allowance'] ?? '') ?: null;
            $other_allowances = trim($_POST['other_allowances'] ?? '') ?: null;
            $gross_monthly_salary = trim($_POST['gross_monthly_salary'] ?? '') ?: null;
            $overtime_rate = trim($_POST['overtime_rate'] ?? '') ?: null;
            $bonus_commission_structure = trim($_POST['bonus_commission_structure'] ?? '') ?: null;

            $bank_name = trim($_POST['bank_name'] ?? '') ?: null;
            $account_name = trim($_POST['account_name'] ?? '') ?: null;
            $account_number = trim($_POST['account_number'] ?? '') ?: null;
            $bvn = trim($_POST['bvn'] ?? '') ?: null;

            $tax_identification_number = trim($_POST['tax_identification_number'] ?? '') ?: null;
            $pension_fund_administrator = trim($_POST['pension_fund_administrator'] ?? '') ?: null;
            $pension_pin = trim($_POST['pension_pin'] ?? '') ?: null;
            $nhf_number = trim($_POST['nhf_number'] ?? '') ?: null;
            $nhis_hmo_provider = trim($_POST['nhis_hmo_provider'] ?? '') ?: null;
            $employee_contribution_percentages = trim($_POST['employee_contribution_percentages'] ?? '') ?: null;

            $new_hire = isset($_POST['new_hire']) && $_POST['new_hire'] !== '' ? (int) $_POST['new_hire'] : null;
            $exit_termination_date = trim($_POST['exit_termination_date'] ?? '') ?: null;
            $salary_adjustment_notes = trim($_POST['salary_adjustment_notes'] ?? '') ?: null;
            $promotion_role_change = trim($_POST['promotion_role_change'] ?? '') ?: null;
            $bank_detail_update = trim($_POST['bank_detail_update'] ?? '') ?: null;

            if (empty($full_name)) {
                $error = 'Full name is required.';
            } else {
                $sql = "UPDATE staff SET
                    full_name = ?, date_of_birth = ?, date_joined = ?, biography = ?, phone_number = ?, gender = ?, address = ?, marital_status = ?,
                    employee_id = ?, position = ?, department = ?, employment_type = ?, reporting_manager = ?, work_location = ?, confirmation_date = ?,
                    basic_salary = ?, housing_allowance = ?, transport_allowance = ?, other_allowances = ?, gross_monthly_salary = ?, overtime_rate = ?, bonus_commission_structure = ?,
                    bank_name = ?, account_name = ?, account_number = ?, bvn = ?,
                    tax_identification_number = ?, pension_fund_administrator = ?, pension_pin = ?, nhf_number = ?, nhis_hmo_provider = ?, employee_contribution_percentages = ?,
                    new_hire = ?, exit_termination_date = ?, salary_adjustment_notes = ?, promotion_role_change = ?, bank_detail_update = ?,
                    updated_at = NOW()
                    WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $full_name, $date_of_birth, $date_joined, $biography, $phone_number, $gender, $address, $marital_status,
                    $employee_id, $position, $department, $employment_type, $reporting_manager, $work_location, $confirmation_date,
                    $basic_salary, $housing_allowance, $transport_allowance, $other_allowances, $gross_monthly_salary, $overtime_rate, $bonus_commission_structure,
                    $bank_name, $account_name, $account_number, $bvn,
                    $tax_identification_number, $pension_fund_administrator, $pension_pin, $nhf_number, $nhis_hmo_provider, $employee_contribution_percentages,
                    $new_hire, $exit_termination_date, $salary_adjustment_notes, $promotion_role_change, $bank_detail_update,
                    $staff['id']
                ]);
                set_flash('success', 'Profile updated.');
                header('Location: ' . BASE_URL . '/user/profile.php');
                exit;
            }
        } elseif ($action === 'image') {
            if (!empty($_FILES['profile_image']['name'])) {
                $result = handle_profile_upload($_FILES['profile_image']);
                if ($result) {
                    if ($staff['profile_image']) {
                        delete_profile_image($staff['profile_image']);
                    }
                    $stmt = $pdo->prepare("UPDATE staff SET profile_image = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$result, $staff['id']]);
                    set_flash('success', 'Profile image updated.');
                    header('Location: ' . BASE_URL . '/user/profile.php');
                    exit;
                }
                $error = 'Invalid image. Use JPG or PNG, max 2MB.';
            }
        } elseif ($action === 'cv') {
            if (!empty($_FILES['cv_file']['name'])) {
                $result = handle_cv_upload($_FILES['cv_file']);
                if ($result) {
                    if (!empty($staff['cv_path'])) {
                        delete_cv_file($staff['cv_path']);
                    }
                    $stmt = $pdo->prepare("UPDATE staff SET cv_path = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$result, $staff['id']]);
                    set_flash('success', 'CV updated.');
                    header('Location: ' . BASE_URL . '/user/profile.php');
                    exit;
                }
                $error = 'Invalid CV. Use PDF or JPG/PNG, max 5MB.';
            } else {
                $error = 'Please select a CV file to upload.';
            }
        } elseif ($action === 'nin') {
            if (!empty($_FILES['nin_document']['name'])) {
                $result = handle_nin_document_upload($_FILES['nin_document']);
                if ($result) {
                    if (!empty($staff['nin_document_path'])) {
                        delete_nin_document($staff['nin_document_path']);
                    }
                    $stmt = $pdo->prepare("UPDATE staff SET nin_document_path = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$result, $staff['id']]);
                    set_flash('success', 'NIN document updated.');
                    header('Location: ' . BASE_URL . '/user/profile.php');
                    exit;
                }
                $error = 'Invalid NIN document. Use PDF or JPG/PNG, max 5MB.';
            } else {
                $error = 'Please select a NIN document to upload.';
            }
        }
    }
}

$profile_img = staff_profile_image($staff['profile_image']);
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Staff Portal</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/../includes/staff_layout.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>My Profile</h1>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                    <a href="<?= BASE_URL ?>/user/settings.php" class="btn btn-accent">Settings</a>
                </div>
            </div>
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['message']) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
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
                <div style="margin-top:1rem; display:flex; gap:1rem; flex-wrap:wrap;">
                    <form method="POST" enctype="multipart/form-data" style="flex:1; min-width:260px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="cv">
                        <div class="form-group">
                            <label for="cv_file">Upload new CV</label>
                            <input type="file" id="cv_file" name="cv_file" class="form-control" accept=".pdf,application/pdf,image/jpeg,image/jpg,image/png" required>
                            <p class="form-hint">PDF or JPG/PNG, max 5MB</p>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Upload CV</button>
                    </form>
                    <form method="POST" enctype="multipart/form-data" style="flex:1; min-width:260px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="nin">
                        <div class="form-group">
                            <label for="nin_document">Upload new NIN document</label>
                            <input type="file" id="nin_document" name="nin_document" class="form-control" accept=".pdf,application/pdf,image/jpeg,image/jpg,image/png" required>
                            <p class="form-hint">PDF or JPG/PNG, max 5MB</p>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Upload NIN</button>
                    </form>
                </div>
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

            <div class="card">
                <div class="card-header">
                    <h2>Profile Image</h2>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <img src="<?= esc($profile_img) ?>" alt="Profile" class="profile-img-lg">
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="image">
                        <div class="form-group">
                            <input type="file" name="profile_image" accept="image/jpeg,image/jpg,image/png" required>
                            <p class="form-hint">JPG or PNG, max 2MB</p>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Profile Details</h2>
                </div>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profile">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required
                               value="<?= esc($staff['full_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                               value="<?= esc($staff['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_joined">Employment start date</label>
                        <input type="date" id="date_joined" name="date_joined" class="form-control"
                               value="<?= esc($staff['date_joined'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">— Select —</option>
                            <option value="Male" <?= ($staff['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($staff['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($staff['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="marital_status">Marital status</label>
                        <select id="marital_status" name="marital_status" class="form-control">
                            <option value="">— Select —</option>
                            <option value="Single" <?= ($staff['marital_status'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                            <option value="Married" <?= ($staff['marital_status'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                            <option value="Divorced" <?= ($staff['marital_status'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                            <option value="Widowed" <?= ($staff['marital_status'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                            <option value="Other" <?= ($staff['marital_status'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone number</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control"
                               value="<?= esc($staff['phone_number'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Residential address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?= esc($staff['address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="biography">Biography</label>
                        <textarea id="biography" name="biography" class="form-control" rows="4"><?= esc($staff['biography'] ?? '') ?></textarea>
                    </div>
                    <h3 class="form-section-title" style="margin-top:1rem;">Employment Details</h3>
                    <div class="form-group">
                        <label for="employee_id">Employee ID</label>
                        <input type="text" id="employee_id" name="employee_id" class="form-control" value="<?= esc($staff['employee_id'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="position">Job title</label>
                        <input type="text" id="position" name="position" class="form-control" value="<?= esc($staff['position'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" class="form-control" value="<?= esc($staff['department'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="employment_type">Employment type</label>
                        <select id="employment_type" name="employment_type" class="form-control">
                            <option value="">— Select —</option>
                            <option value="Full-time" <?= ($staff['employment_type'] ?? '') === 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                            <option value="Part-time" <?= ($staff['employment_type'] ?? '') === 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                            <option value="Contract" <?= ($staff['employment_type'] ?? '') === 'Contract' ? 'selected' : '' ?>>Contract</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="confirmation_date">Confirmation date</label>
                        <input type="date" id="confirmation_date" name="confirmation_date" class="form-control" value="<?= esc($staff['confirmation_date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="reporting_manager">Reporting manager</label>
                        <input type="text" id="reporting_manager" name="reporting_manager" class="form-control" value="<?= esc($staff['reporting_manager'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="work_location">Work location</label>
                        <input type="text" id="work_location" name="work_location" class="form-control" value="<?= esc($staff['work_location'] ?? '') ?>">
                    </div>

                    <h3 class="form-section-title" style="margin-top:1rem;">Salary Structure</h3>
                    <div class="form-group">
                        <label for="basic_salary">Basic salary</label>
                        <input type="number" step="0.01" min="0" id="basic_salary" name="basic_salary" class="form-control" value="<?= esc($staff['basic_salary'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="housing_allowance">Housing allowance</label>
                        <input type="number" step="0.01" min="0" id="housing_allowance" name="housing_allowance" class="form-control" value="<?= esc($staff['housing_allowance'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="transport_allowance">Transport allowance</label>
                        <input type="number" step="0.01" min="0" id="transport_allowance" name="transport_allowance" class="form-control" value="<?= esc($staff['transport_allowance'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="other_allowances">Other allowances</label>
                        <textarea id="other_allowances" name="other_allowances" class="form-control" rows="2"><?= esc($staff['other_allowances'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="gross_monthly_salary">Gross monthly salary</label>
                        <input type="number" step="0.01" min="0" id="gross_monthly_salary" name="gross_monthly_salary" class="form-control" value="<?= esc($staff['gross_monthly_salary'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="overtime_rate">Overtime rate</label>
                        <input type="text" id="overtime_rate" name="overtime_rate" class="form-control" value="<?= esc($staff['overtime_rate'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="bonus_commission_structure">Bonus/Commission structure</label>
                        <textarea id="bonus_commission_structure" name="bonus_commission_structure" class="form-control" rows="2"><?= esc($staff['bonus_commission_structure'] ?? '') ?></textarea>
                    </div>

                    <h3 class="form-section-title" style="margin-top:1rem;">Bank Details</h3>
                    <div class="form-group">
                        <label for="bank_name">Bank name</label>
                        <input type="text" id="bank_name" name="bank_name" class="form-control" value="<?= esc($staff['bank_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="account_name">Account name</label>
                        <input type="text" id="account_name" name="account_name" class="form-control" value="<?= esc($staff['account_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="account_number">Account number</label>
                        <input type="text" id="account_number" name="account_number" class="form-control" value="<?= esc($staff['account_number'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="bvn">BVN</label>
                        <input type="text" id="bvn" name="bvn" class="form-control" value="<?= esc($staff['bvn'] ?? '') ?>">
                    </div>

                    <h3 class="form-section-title" style="margin-top:1rem;">Statutory &amp; Compliance</h3>
                    <div class="form-group">
                        <label for="tax_identification_number">TIN</label>
                        <input type="text" id="tax_identification_number" name="tax_identification_number" class="form-control" value="<?= esc($staff['tax_identification_number'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="pension_fund_administrator">PFA</label>
                        <input type="text" id="pension_fund_administrator" name="pension_fund_administrator" class="form-control" value="<?= esc($staff['pension_fund_administrator'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="pension_pin">Pension PIN</label>
                        <input type="text" id="pension_pin" name="pension_pin" class="form-control" value="<?= esc($staff['pension_pin'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="nhf_number">NHF number</label>
                        <input type="text" id="nhf_number" name="nhf_number" class="form-control" value="<?= esc($staff['nhf_number'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="nhis_hmo_provider">NHIS/HMO provider</label>
                        <input type="text" id="nhis_hmo_provider" name="nhis_hmo_provider" class="form-control" value="<?= esc($staff['nhis_hmo_provider'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="employee_contribution_percentages">Employee contribution percentages</label>
                        <input type="text" id="employee_contribution_percentages" name="employee_contribution_percentages" class="form-control" value="<?= esc($staff['employee_contribution_percentages'] ?? '') ?>">
                    </div>

                    <h3 class="form-section-title" style="margin-top:1rem;">Payroll Changes</h3>
                    <div class="form-group">
                        <label for="new_hire">New hire</label>
                        <select id="new_hire" name="new_hire" class="form-control">
                            <option value="">— Select —</option>
                            <option value="1" <?= isset($staff['new_hire']) && (int)$staff['new_hire'] === 1 ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= isset($staff['new_hire']) && (int)$staff['new_hire'] === 0 ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="exit_termination_date">Exit/Termination date</label>
                        <input type="date" id="exit_termination_date" name="exit_termination_date" class="form-control" value="<?= esc($staff['exit_termination_date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="salary_adjustment_notes">Salary adjustment notes</label>
                        <textarea id="salary_adjustment_notes" name="salary_adjustment_notes" class="form-control" rows="2"><?= esc($staff['salary_adjustment_notes'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="promotion_role_change">Promotion/Role change</label>
                        <input type="text" id="promotion_role_change" name="promotion_role_change" class="form-control" value="<?= esc($staff['promotion_role_change'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="bank_detail_update">Bank detail update</label>
                        <input type="text" id="bank_detail_update" name="bank_detail_update" class="form-control" value="<?= esc($staff['bank_detail_update'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </form>
            </div>
        </main>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
