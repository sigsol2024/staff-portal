<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

require_admin_login();
require_admin_only();

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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'profile';

        if ($action === 'profile') {
            $t = function($v) { $v = trim($v ?? ''); return $v === '' ? null : $v; };
            $email = trim($_POST['email'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $date_of_birth = $t($_POST['date_of_birth'] ?? '');
            $date_joined = $t($_POST['date_joined'] ?? '');
            $position = $t($_POST['position'] ?? '');
            $biography = $t($_POST['biography'] ?? '');
            $phone_number = $t($_POST['phone_number'] ?? '');
            $gender = $t($_POST['gender'] ?? '');
            $address = $t($_POST['address'] ?? '');
            $status = ($_POST['status'] ?? 'active') === 'suspended' ? 'suspended' : 'active';
            $marital_status = $t($_POST['marital_status'] ?? '');
            $employee_id = $t($_POST['employee_id'] ?? '');
            $department = $t($_POST['department'] ?? '');
            $employment_type = $t($_POST['employment_type'] ?? '');
            $confirmation_date = $t($_POST['confirmation_date'] ?? '');
            $reporting_manager = $t($_POST['reporting_manager'] ?? '');
            $work_location = $t($_POST['work_location'] ?? '');
            $other_allowances = $t($_POST['other_allowances'] ?? '');
            $overtime_rate = $t($_POST['overtime_rate'] ?? '');
            $bonus_commission_structure = $t($_POST['bonus_commission_structure'] ?? '');
            $bank_name = $t($_POST['bank_name'] ?? '');
            $account_name = $t($_POST['account_name'] ?? '');
            $account_number = $t($_POST['account_number'] ?? '');
            $bvn = $t($_POST['bvn'] ?? '');
            $tax_identification_number = $t($_POST['tax_identification_number'] ?? '');
            $pension_fund_administrator = $t($_POST['pension_fund_administrator'] ?? '');
            $pension_pin = $t($_POST['pension_pin'] ?? '');
            $nhf_number = $t($_POST['nhf_number'] ?? '');
            $nhis_hmo_provider = $t($_POST['nhis_hmo_provider'] ?? '');
            $employee_contribution_percentages = $t($_POST['employee_contribution_percentages'] ?? '');
            $new_hire = isset($_POST['new_hire']) && $_POST['new_hire'] === '1' ? 1 : (isset($_POST['new_hire']) && $_POST['new_hire'] === '0' ? 0 : null);
            $salary_adjustment_notes = $t($_POST['salary_adjustment_notes'] ?? '');
            $promotion_role_change = $t($_POST['promotion_role_change'] ?? '');
            $bank_detail_update = $t($_POST['bank_detail_update'] ?? '');
            $exit_termination_date = $t($_POST['exit_termination_date'] ?? '') ?: null;
            $decimal = function($v) { $v = trim($v ?? ''); return $v === '' ? null : (is_numeric($v) ? $v : null); };
            $basic_salary = $decimal($_POST['basic_salary'] ?? '');
            $housing_allowance = $decimal($_POST['housing_allowance'] ?? '');
            $transport_allowance = $decimal($_POST['transport_allowance'] ?? '');
            $gross_monthly_salary = $decimal($_POST['gross_monthly_salary'] ?? '');

            if (empty($email) || empty($full_name)) {
                $error = 'Email and full name are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address.';
            } else {
                $stmt = $pdo->prepare("SELECT id FROM staff WHERE email = ? AND id != ?");
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) {
                    $error = 'Email already in use.';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE staff SET email = ?, full_name = ?, date_of_birth = ?, date_joined = ?, position = ?, biography = ?, phone_number = ?, gender = ?, address = ?, status = ?,
                        marital_status = ?, employee_id = ?, department = ?, employment_type = ?, confirmation_date = ?, reporting_manager = ?, work_location = ?,
                        basic_salary = ?, housing_allowance = ?, transport_allowance = ?, other_allowances = ?, gross_monthly_salary = ?, overtime_rate = ?, bonus_commission_structure = ?,
                        bank_name = ?, account_name = ?, account_number = ?, bvn = ?,
                        tax_identification_number = ?, pension_fund_administrator = ?, pension_pin = ?, nhf_number = ?, nhis_hmo_provider = ?, employee_contribution_percentages = ?,
                        new_hire = ?, exit_termination_date = ?, salary_adjustment_notes = ?, promotion_role_change = ?, bank_detail_update = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$email, $full_name, $date_of_birth, $date_joined, $position, $biography, $phone_number, $gender, $address, $status,
                        $marital_status, $employee_id, $department, $employment_type, $confirmation_date, $reporting_manager, $work_location,
                        $basic_salary, $housing_allowance, $transport_allowance, $other_allowances, $gross_monthly_salary, $overtime_rate, $bonus_commission_structure,
                        $bank_name, $account_name, $account_number, $bvn,
                        $tax_identification_number, $pension_fund_administrator, $pension_pin, $nhf_number, $nhis_hmo_provider, $employee_contribution_percentages,
                        $new_hire, $exit_termination_date, $salary_adjustment_notes, $promotion_role_change, $bank_detail_update, $id]);
                    set_flash('success', 'Staff updated.');
                    header('Location: ' . BASE_URL . '/admin/view-staff.php?id=' . $id);
                    exit;
                }
            }
        } elseif ($action === 'image') {
            if (!empty($_FILES['profile_image']['name'])) {
                $result = handle_profile_upload($_FILES['profile_image']);
                if ($result) {
                    if ($staff['profile_image']) {
                        delete_profile_image($staff['profile_image']);
                    }
                    $stmt = $pdo->prepare("UPDATE staff SET profile_image = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$result, $id]);
                    set_flash('success', 'Profile image updated.');
                    header('Location: ' . BASE_URL . '/admin/edit-staff.php?id=' . $id);
                    exit;
                }
                $error = 'Invalid image. Use JPG or PNG, max 2MB. Ensure uploads/profile_images/ is writable.';
            }
        } elseif ($action === 'password') {
            $password = $_POST['new_password'] ?? '';
            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE staff SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hash, $id]);
                set_flash('success', 'Password changed.');
                header('Location: ' . BASE_URL . '/admin/edit-staff.php?id=' . $id);
                exit;
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
    <title>Edit Staff - Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/../includes/admin_layout.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>Edit Staff</h1>
                <div class="page-header-actions">
                    <a href="<?= BASE_URL ?>/admin/view-staff.php?id=<?= $id ?>" class="btn btn-primary">View profile</a>
                    <a href="<?= BASE_URL ?>/admin/staff-list.php" class="btn btn-accent">Back to list</a>
                </div>
            </div>
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['message']) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="edit-staff-hero">
                    <div class="edit-staff-avatar-wrap">
                        <img id="edit-staff-preview" src="<?= esc($profile_img) ?>" alt="<?= esc($staff['full_name']) ?>" class="profile-img-lg">
                        <form method="POST" enctype="multipart/form-data" class="edit-staff-image-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="image">
                            <div class="form-group">
                                <label for="profile_image">New photo</label>
                                <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/jpg,image/png" required class="form-control">
                                <span class="form-hint">JPG or PNG, max 2MB. Preview updates when you select a file.</span>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                        </form>
                    </div>
                    <div class="edit-staff-hero-details">
                        <div class="edit-staff-hero-title">
                            <h2 class="edit-staff-hero-name"><?= esc($staff['full_name']) ?></h2>
                            <span class="badge <?= status_badge_class($staff['status']) ?>"><?= esc(ucfirst($staff['status'])) ?></span>
                        </div>
                        <p class="edit-staff-hero-meta"><?= esc($staff['email']) ?> · <?= esc($staff['position'] ?? '—') ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Profile details</h2>
                </div>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profile">
                    <div class="edit-staff-form-grid">
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" required
                                   value="<?= esc($staff['email']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="full_name">Full name <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" class="form-control" required
                                   value="<?= esc($staff['full_name']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth">Date of birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                                   value="<?= esc($staff['date_of_birth'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_joined">Date joined</label>
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
                            <label for="position">Job title</label>
                            <input type="text" id="position" name="position" class="form-control"
                                   value="<?= esc($staff['position'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="employee_id">Employee ID</label>
                            <input type="text" id="employee_id" name="employee_id" class="form-control" value="<?= esc($staff['employee_id'] ?? '') ?>">
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
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="active" <?= $staff['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="suspended" <?= $staff['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="form-group form-group-full">
                            <label for="address">Residential address</label>
                            <textarea id="address" name="address" class="form-control" rows="3" placeholder="Street, city, country"><?= esc($staff['address'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group form-group-full">
                            <label for="biography">Biography</label>
                            <textarea id="biography" name="biography" class="form-control" rows="4" placeholder="Short bio or role description"><?= esc($staff['biography'] ?? '') ?></textarea>
                        </div>

                        <h3 class="form-section-title">Salary &amp; Bank</h3>
                        <div class="form-group">
                            <label for="basic_salary">Basic salary</label>
                            <input type="number" id="basic_salary" name="basic_salary" class="form-control" step="0.01" min="0" value="<?= esc($staff['basic_salary'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="housing_allowance">Housing allowance</label>
                            <input type="number" id="housing_allowance" name="housing_allowance" class="form-control" step="0.01" min="0" value="<?= esc($staff['housing_allowance'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="transport_allowance">Transport allowance</label>
                            <input type="number" id="transport_allowance" name="transport_allowance" class="form-control" step="0.01" min="0" value="<?= esc($staff['transport_allowance'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="gross_monthly_salary">Gross monthly salary</label>
                            <input type="number" id="gross_monthly_salary" name="gross_monthly_salary" class="form-control" step="0.01" min="0" value="<?= esc($staff['gross_monthly_salary'] ?? '') ?>">
                        </div>
                        <div class="form-group form-group-full">
                            <label for="other_allowances">Other allowances</label>
                            <textarea id="other_allowances" name="other_allowances" class="form-control" rows="1"><?= esc($staff['other_allowances'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="overtime_rate">Overtime rate</label>
                            <input type="text" id="overtime_rate" name="overtime_rate" class="form-control" value="<?= esc($staff['overtime_rate'] ?? '') ?>">
                        </div>
                        <div class="form-group form-group-full">
                            <label for="bonus_commission_structure">Bonus/Commission structure</label>
                            <textarea id="bonus_commission_structure" name="bonus_commission_structure" class="form-control" rows="1"><?= esc($staff['bonus_commission_structure'] ?? '') ?></textarea>
                        </div>
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

                        <h3 class="form-section-title">Statutory &amp; Payroll</h3>
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
                            <label for="employee_contribution_percentages">Contribution %</label>
                            <input type="text" id="employee_contribution_percentages" name="employee_contribution_percentages" class="form-control" value="<?= esc($staff['employee_contribution_percentages'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="new_hire">New hire</label>
                            <select id="new_hire" name="new_hire" class="form-control">
                                <option value="">—</option>
                                <option value="1" <?= (string)($staff['new_hire'] ?? '') === '1' ? 'selected' : '' ?>>Yes</option>
                                <option value="0" <?= (string)($staff['new_hire'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exit_termination_date">Exit/Termination date</label>
                            <input type="date" id="exit_termination_date" name="exit_termination_date" class="form-control" value="<?= esc($staff['exit_termination_date'] ?? '') ?>">
                        </div>
                        <div class="form-group form-group-full">
                            <label for="salary_adjustment_notes">Salary adjustment notes</label>
                            <textarea id="salary_adjustment_notes" name="salary_adjustment_notes" class="form-control" rows="1"><?= esc($staff['salary_adjustment_notes'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="promotion_role_change">Promotion/Role change</label>
                            <input type="text" id="promotion_role_change" name="promotion_role_change" class="form-control" value="<?= esc($staff['promotion_role_change'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="bank_detail_update">Bank detail update</label>
                            <input type="text" id="bank_detail_update" name="bank_detail_update" class="form-control" value="<?= esc($staff['bank_detail_update'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="edit-staff-form-actions">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                        <a href="<?= BASE_URL ?>/admin/view-staff.php?id=<?= $id ?>" class="btn btn-accent">Cancel</a>
                    </div>
                </form>
            </div>

            <div class="card edit-staff-password-card">
                <div class="card-header">
                    <h2>Change password</h2>
                </div>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">
                    <div class="form-group">
                        <label for="new_password">New password <span class="form-hint">(min <?= PASSWORD_MIN_LENGTH ?> characters)</span></label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="<?= PASSWORD_MIN_LENGTH ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Change password</button>
                </form>
            </div>
        </main>
    </div>
    <script>
        (function(){
            var inp = document.getElementById('profile_image');
            var img = document.getElementById('edit-staff-preview');
            var defSrc = '<?= addslashes($profile_img) ?>';
            if (inp && img) inp.addEventListener('change', function(){
                var f = this.files[0];
                if (f && f.type.match(/^image\/(jpeg|jpg|png)$/)) {
                    var r = new FileReader();
                    r.onload = function(){ img.src = r.result; };
                    r.readAsDataURL(f);
                } else if (!f) img.src = defSrc;
            });
        })();
    </script>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
