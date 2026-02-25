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
if ($edit_enabled !== 1) {
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
            $biography = trim($_POST['biography'] ?? '') ?: null;
            $phone_number = trim($_POST['phone_number'] ?? '') ?: null;
            $gender = trim($_POST['gender'] ?? '') ?: null;
            $address = trim($_POST['address'] ?? '') ?: null;
            $marital_status = trim($_POST['marital_status'] ?? '') ?: null;

            if (empty($full_name)) {
                $error = 'Full name is required.';
            } else {
                $stmt = $pdo->prepare("UPDATE staff SET full_name = ?, date_of_birth = ?, biography = ?, phone_number = ?, gender = ?, address = ?, marital_status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$full_name, $date_of_birth, $biography, $phone_number, $gender, $address, $marital_status, $staff['id']]);
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
                    <p class="form-hint">Email, position, date joined cannot be changed by staff. Contact admin.</p>
                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </form>
            </div>
        </main>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
