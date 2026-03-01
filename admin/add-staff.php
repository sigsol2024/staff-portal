<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

require_admin_login();
require_admin_only();

$error = '';
$salary_pcts = get_salary_percent_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
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
        $password = $_POST['password'] ?? '';
        $status = ($_POST['status'] ?? 'active') === 'suspended' ? 'suspended' : 'active';
        $marital_status = $t($_POST['marital_status'] ?? '');
        $employee_id = $t($_POST['employee_id'] ?? '');
        $department = $t($_POST['department'] ?? '');
        $role = $t($_POST['role'] ?? '');
        $employment_type = $t($_POST['employment_type'] ?? '');
        $confirmation_date = $t($_POST['confirmation_date'] ?? '');
        $reporting_manager = $t($_POST['reporting_manager'] ?? '');
        $work_location = $t($_POST['work_location'] ?? '');
        // These fields are no longer used (auto-calculated salary setup)
        $other_allowances = null;
        $overtime_rate = null;
        $bonus_commission_structure = null;
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
        $new_hire = isset($_POST['new_hire']) && $_POST['new_hire'] === '1' ? 1 : null;
        $salary_adjustment_notes = $t($_POST['salary_adjustment_notes'] ?? '');
        $promotion_role_change = $t($_POST['promotion_role_change'] ?? '');
        $bank_detail_update = $t($_POST['bank_detail_update'] ?? '');
        $decimal = function($v) { $v = trim($v ?? ''); return $v === '' ? null : (is_numeric($v) ? $v : null); };
        $basic_salary = $decimal($_POST['basic_salary'] ?? '');
        $housing_allowance = null;
        $transport_allowance = null;
        $telephone_allowance = null;
        $other_allowance = null;
        $gross_monthly_salary = null;
        if ($basic_salary !== null) {
            $breakdown = compute_salary_breakdown_from_basic((float) $basic_salary);
            $housing_allowance = $breakdown['housing_allowance'];
            $transport_allowance = $breakdown['transport_allowance'];
            $telephone_allowance = $breakdown['telephone_allowance'];
            $other_allowance = $breakdown['other_allowance'];
            $gross_monthly_salary = $breakdown['gross_monthly_salary'];
            $_POST['housing_allowance'] = $housing_allowance !== null ? number_format((float)$housing_allowance, 2, '.', '') : '';
            $_POST['transport_allowance'] = $transport_allowance !== null ? number_format((float)$transport_allowance, 2, '.', '') : '';
            $_POST['telephone_allowance'] = $telephone_allowance !== null ? number_format((float)$telephone_allowance, 2, '.', '') : '';
            $_POST['other_allowance'] = $other_allowance !== null ? number_format((float)$other_allowance, 2, '.', '') : '';
            $_POST['gross_monthly_salary'] = $gross_monthly_salary !== null ? number_format((float)$gross_monthly_salary, 2, '.', '') : '';
        }
        $exit_termination_date = $t($_POST['exit_termination_date'] ?? '') ?: null;

        $profile_image = null;
        if (!empty($_FILES['profile_image']['name'])) {
            $profile_image = handle_profile_upload($_FILES['profile_image']);
            if ($profile_image === false) {
                $error = 'Invalid profile image. Use JPG or PNG, max 2MB. Ensure uploads/profile_images/ is writable.';
            }
        }

        if (!$error && (empty($email) || empty($full_name) || empty($password))) {
            $error = 'Email, full name and password are required.';
        } elseif (!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (!$error && strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } elseif (!$error && (empty($department) || empty($role))) {
            $error = 'Department and role are required.';
        } elseif (!$error) {
            $stmt = $pdo->prepare("SELECT id FROM staff WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'This email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO staff (email, password, full_name, date_of_birth, date_joined, position, biography, phone_number, gender, address, profile_image, status,
                    marital_status, employee_id, department, role, employment_type, confirmation_date, reporting_manager, work_location,
                    basic_salary, housing_allowance, transport_allowance, telephone_allowance, other_allowance, other_allowances, gross_monthly_salary, overtime_rate, bonus_commission_structure,
                    bank_name, account_name, account_number, bvn,
                    tax_identification_number, pension_fund_administrator, pension_pin, nhf_number, nhis_hmo_provider, employee_contribution_percentages,
                    new_hire, exit_termination_date, salary_adjustment_notes, promotion_role_change, bank_detail_update)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?)
                ");
                try {
                    $stmt->execute([$email, $hash, $full_name, $date_of_birth, $date_joined, $position, $biography, $phone_number, $gender, $address, $profile_image, $status,
                        $marital_status, $employee_id, $department, $role, $employment_type, $confirmation_date, $reporting_manager, $work_location,
                        $basic_salary, $housing_allowance, $transport_allowance, $telephone_allowance, $other_allowance, $other_allowances, $gross_monthly_salary, $overtime_rate, $bonus_commission_structure,
                        $bank_name, $account_name, $account_number, $bvn,
                        $tax_identification_number, $pension_fund_administrator, $pension_pin, $nhf_number, $nhis_hmo_provider, $employee_contribution_percentages,
                        $new_hire, $exit_termination_date, $salary_adjustment_notes, $promotion_role_change, $bank_detail_update]);
                    $staff_id = (int) $pdo->lastInsertId();
                    $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action, staff_id) VALUES (?, ?, ?)");
                    $stmt->execute([current_admin_id(), 'add_staff', $staff_id]);
                    set_flash('success', 'Staff added successfully.');
                    header('Location: ' . BASE_URL . '/admin/view-staff.php?id=' . $staff_id);
                    exit;
                } catch (PDOException $e) {
                    $error = 'Failed to add staff. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Staff - Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/../includes/admin_layout.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>Add Staff</h1>
                <a href="<?= BASE_URL ?>/admin/staff-list.php" class="btn btn-primary">Back to List</a>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="form-group add-staff-avatar-row">
                        <label>Profile picture</label>
                        <div class="add-staff-avatar-wrap">
                            <img id="add-staff-preview" src="<?= BASE_URL ?>/assets/images/placeholder.svg" alt="Preview" class="profile-img-lg add-staff-preview-img">
                            <div class="add-staff-avatar-upload">
                                <input type="file" id="add_staff_profile_image" name="profile_image" accept="image/jpeg,image/jpg,image/png" class="form-control">
                                <span class="form-hint">JPG or PNG, max 2MB</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?= esc($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required
                               value="<?= esc($_POST['full_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password * (min <?= PASSWORD_MIN_LENGTH ?> chars)</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                               value="<?= esc($_POST['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_joined">Date Joined</label>
                        <input type="date" id="date_joined" name="date_joined" class="form-control"
                               value="<?= esc($_POST['date_joined'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">— Select —</option>
                            <option value="Male" <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($_POST['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="marital_status">Marital Status</label>
                        <select id="marital_status" name="marital_status" class="form-control">
                            <option value="">— Select —</option>
                            <option value="Single" <?= ($_POST['marital_status'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                            <option value="Married" <?= ($_POST['marital_status'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                            <option value="Divorced" <?= ($_POST['marital_status'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                            <option value="Widowed" <?= ($_POST['marital_status'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                            <option value="Other" <?= ($_POST['marital_status'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control"
                               value="<?= esc($_POST['phone_number'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="position">Job Title</label>
                        <input type="text" id="position" name="position" class="form-control"
                               value="<?= esc($_POST['position'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="employee_id">Employee ID</label>
                        <input type="text" id="employee_id" name="employee_id" class="form-control" value="<?= esc($_POST['employee_id'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="department">Department *</label>
                        <select id="department" name="department" class="form-control" required>
                            <option value="">— Select —</option>
                            <option value="Admin" <?= ($_POST['department'] ?? '') === 'Admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="Account" <?= ($_POST['department'] ?? '') === 'Account' ? 'selected' : '' ?>>Account</option>
                            <option value="Technical Services" <?= ($_POST['department'] ?? '') === 'Technical Services' ? 'selected' : '' ?>>Technical Services</option>
                            <option value="Software/Cloud Service" <?= ($_POST['department'] ?? '') === 'Software/Cloud Service' ? 'selected' : '' ?>>Software/Cloud Service</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="">— Select —</option>
                            <option value="Manager" <?= ($_POST['role'] ?? '') === 'Manager' ? 'selected' : '' ?>>Manager</option>
                            <option value="Supervisor" <?= ($_POST['role'] ?? '') === 'Supervisor' ? 'selected' : '' ?>>Supervisor</option>
                            <option value="Staff" <?= ($_POST['role'] ?? '') === 'Staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="Rank and file" <?= ($_POST['role'] ?? '') === 'Rank and file' ? 'selected' : '' ?>>Rank and file</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="employment_type">Employment Type</label>
                        <select id="employment_type" name="employment_type" class="form-control">
                            <option value="">— Select —</option>
                            <option value="Full-time" <?= ($_POST['employment_type'] ?? '') === 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                            <option value="Part-time" <?= ($_POST['employment_type'] ?? '') === 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                            <option value="Contract" <?= ($_POST['employment_type'] ?? '') === 'Contract' ? 'selected' : '' ?>>Contract</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="confirmation_date">Confirmation Date</label>
                        <input type="date" id="confirmation_date" name="confirmation_date" class="form-control" value="<?= esc($_POST['confirmation_date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="reporting_manager">Reporting Manager</label>
                        <input type="text" id="reporting_manager" name="reporting_manager" class="form-control" value="<?= esc($_POST['reporting_manager'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="work_location">Work Location</label>
                        <input type="text" id="work_location" name="work_location" class="form-control" value="<?= esc($_POST['work_location'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Residential Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?= esc($_POST['address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="biography">Biography</label>
                        <textarea id="biography" name="biography" class="form-control" rows="4"><?= esc($_POST['biography'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>

                    <h3 class="form-section-title">Salary &amp; Bank</h3>
                    <input type="hidden" id="salary_pct_basic" value="<?= esc((string)($salary_pcts['basic'] ?? 34)) ?>">
                    <input type="hidden" id="salary_pct_housing" value="<?= esc((string)($salary_pcts['housing'] ?? 16)) ?>">
                    <input type="hidden" id="salary_pct_transport" value="<?= esc((string)($salary_pcts['transport'] ?? 16)) ?>">
                    <input type="hidden" id="salary_pct_telephone" value="<?= esc((string)($salary_pcts['telephone'] ?? 16)) ?>">
                    <input type="hidden" id="salary_pct_other" value="<?= esc((string)($salary_pcts['other'] ?? 16)) ?>">
                    <div class="edit-staff-form-grid">
                        <div class="form-group">
                            <label for="basic_salary">Basic Salary</label>
                            <input type="number" id="basic_salary" name="basic_salary" class="form-control" step="0.01" min="0" value="<?= esc($_POST['basic_salary'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="housing_allowance">Housing Allowance</label>
                            <input type="number" id="housing_allowance" name="housing_allowance" class="form-control" step="0.01" min="0" readonly value="<?= esc($_POST['housing_allowance'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="transport_allowance">Transport Allowance</label>
                            <input type="number" id="transport_allowance" name="transport_allowance" class="form-control" step="0.01" min="0" readonly value="<?= esc($_POST['transport_allowance'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="telephone_allowance">Telephone Allowance</label>
                            <input type="number" id="telephone_allowance" name="telephone_allowance" class="form-control" step="0.01" min="0" readonly value="<?= esc($_POST['telephone_allowance'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="other_allowance">Other Allowance</label>
                            <input type="number" id="other_allowance" name="other_allowance" class="form-control" step="0.01" min="0" readonly value="<?= esc($_POST['other_allowance'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="gross_monthly_salary">Gross Monthly Salary</label>
                            <input type="number" id="gross_monthly_salary" name="gross_monthly_salary" class="form-control" step="0.01" min="0" readonly value="<?= esc($_POST['gross_monthly_salary'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="bank_name">Bank Name</label>
                            <input type="text" id="bank_name" name="bank_name" class="form-control" value="<?= esc($_POST['bank_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="account_name">Account Name</label>
                            <input type="text" id="account_name" name="account_name" class="form-control" value="<?= esc($_POST['account_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="account_number">Account Number</label>
                            <input type="text" id="account_number" name="account_number" class="form-control" value="<?= esc($_POST['account_number'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="bvn">BVN</label>
                            <input type="text" id="bvn" name="bvn" class="form-control" value="<?= esc($_POST['bvn'] ?? '') ?>">
                        </div>
                    </div>

                    <h3 class="form-section-title">Statutory &amp; Payroll</h3>
                    <div class="edit-staff-form-grid">
                        <div class="form-group">
                            <label for="tax_identification_number">TIN</label>
                            <input type="text" id="tax_identification_number" name="tax_identification_number" class="form-control" value="<?= esc($_POST['tax_identification_number'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="pension_fund_administrator">PFA</label>
                            <input type="text" id="pension_fund_administrator" name="pension_fund_administrator" class="form-control" value="<?= esc($_POST['pension_fund_administrator'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="pension_pin">Pension PIN</label>
                            <input type="text" id="pension_pin" name="pension_pin" class="form-control" value="<?= esc($_POST['pension_pin'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="nhf_number">NHF Number</label>
                            <input type="text" id="nhf_number" name="nhf_number" class="form-control" value="<?= esc($_POST['nhf_number'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="nhis_hmo_provider">NHIS/HMO Provider</label>
                            <input type="text" id="nhis_hmo_provider" name="nhis_hmo_provider" class="form-control" value="<?= esc($_POST['nhis_hmo_provider'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="employee_contribution_percentages">Contribution %</label>
                            <input type="text" id="employee_contribution_percentages" name="employee_contribution_percentages" class="form-control" value="<?= esc($_POST['employee_contribution_percentages'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="new_hire">New Hire</label>
                            <select id="new_hire" name="new_hire" class="form-control">
                                <option value="">—</option>
                                <option value="1" <?= ($_POST['new_hire'] ?? '') === '1' ? 'selected' : '' ?>>Yes</option>
                                <option value="0" <?= ($_POST['new_hire'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exit_termination_date">Exit/Termination Date</label>
                            <input type="date" id="exit_termination_date" name="exit_termination_date" class="form-control" value="<?= esc($_POST['exit_termination_date'] ?? '') ?>">
                        </div>
                        <div class="form-group form-group-full">
                            <label for="salary_adjustment_notes">Salary Adjustment Notes</label>
                            <textarea id="salary_adjustment_notes" name="salary_adjustment_notes" class="form-control" rows="1"><?= esc($_POST['salary_adjustment_notes'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="promotion_role_change">Promotion/Role Change</label>
                            <input type="text" id="promotion_role_change" name="promotion_role_change" class="form-control" value="<?= esc($_POST['promotion_role_change'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="bank_detail_update">Bank Detail Update</label>
                            <input type="text" id="bank_detail_update" name="bank_detail_update" class="form-control" value="<?= esc($_POST['bank_detail_update'] ?? '') ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Add Staff</button>
                </form>
            </div>
        </main>
    </div>
    <script>
        (function(){
            var inp = document.getElementById('add_staff_profile_image');
            var img = document.getElementById('add-staff-preview');
            if (inp && img) inp.addEventListener('change', function(){
                var f = this.files[0];
                if (f && f.type.match(/^image\/(jpeg|jpg|png)$/)) {
                    var r = new FileReader();
                    r.onload = function(){ img.src = r.result; };
                    r.readAsDataURL(f);
                } else if (!f) img.src = '<?= addslashes(BASE_URL) ?>/assets/images/placeholder.svg';
            });

            function syncSalaryFields() {
                var pctBasicEl = document.getElementById('salary_pct_basic');
                var pctHousingEl = document.getElementById('salary_pct_housing');
                var pctTransportEl = document.getElementById('salary_pct_transport');
                var pctTelephoneEl = document.getElementById('salary_pct_telephone');
                var pctOtherEl = document.getElementById('salary_pct_other');
                var basicEl = document.getElementById('basic_salary');
                var houseEl = document.getElementById('housing_allowance');
                var transEl = document.getElementById('transport_allowance');
                var telEl = document.getElementById('telephone_allowance');
                var otherEl = document.getElementById('other_allowance');
                var grossEl = document.getElementById('gross_monthly_salary');
                if (!basicEl || !houseEl || !transEl || !telEl || !otherEl || !grossEl) return;
                var pctBasic = pctBasicEl ? parseFloat(pctBasicEl.value || '0') : 0;
                var pctHousing = pctHousingEl ? parseFloat(pctHousingEl.value || '0') : 0;
                var pctTransport = pctTransportEl ? parseFloat(pctTransportEl.value || '0') : 0;
                var pctTelephone = pctTelephoneEl ? parseFloat(pctTelephoneEl.value || '0') : 0;
                var pctOther = pctOtherEl ? parseFloat(pctOtherEl.value || '0') : 0;
                if (!isFinite(pctBasic)) pctBasic = 0;
                if (!isFinite(pctHousing)) pctHousing = 0;
                if (!isFinite(pctTransport)) pctTransport = 0;
                if (!isFinite(pctTelephone)) pctTelephone = 0;
                if (!isFinite(pctOther)) pctOther = 0;
                pctBasic = Math.max(0, Math.min(100, pctBasic));
                pctHousing = Math.max(0, Math.min(100, pctHousing));
                pctTransport = Math.max(0, Math.min(100, pctTransport));
                pctTelephone = Math.max(0, Math.min(100, pctTelephone));
                pctOther = Math.max(0, Math.min(100, pctOther));
                var basic = parseFloat((basicEl.value || '').toString().replace(/,/g, ''));
                if (!isFinite(basic)) {
                    houseEl.value = '';
                    transEl.value = '';
                    telEl.value = '';
                    otherEl.value = '';
                    grossEl.value = '';
                    return;
                }
                if (!pctBasic) {
                    houseEl.value = '';
                    transEl.value = '';
                    telEl.value = '';
                    otherEl.value = '';
                    grossEl.value = '';
                    return;
                }
                var total = basic / (pctBasic / 100);
                var housing = Math.round((total * (pctHousing / 100)) * 100) / 100;
                var transport = Math.round((total * (pctTransport / 100)) * 100) / 100;
                var telephone = Math.round((total * (pctTelephone / 100)) * 100) / 100;
                var other = Math.round((total * (pctOther / 100)) * 100) / 100;
                var gross = Math.round(total * 100) / 100;
                houseEl.value = housing.toFixed(2);
                transEl.value = transport.toFixed(2);
                telEl.value = telephone.toFixed(2);
                otherEl.value = other.toFixed(2);
                grossEl.value = gross.toFixed(2);
            }
            var basicSalaryEl = document.getElementById('basic_salary');
            if (basicSalaryEl) basicSalaryEl.addEventListener('input', syncSalaryFields);
            syncSalaryFields();
        })();
    </script>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
