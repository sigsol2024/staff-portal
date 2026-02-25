<?php
define('STAFF_PORTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/upload.php';
require_once __DIR__ . '/includes/mail.php';

if (!empty($_SESSION['staff_id'])) {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($_POST['declaration_accepted'])) {
        $error = 'You must confirm that the information is accurate and complete.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        $t = function($v) { $v = trim($v ?? ''); return $v === '' ? null : $v; };
        $date_of_birth = $t($_POST['date_of_birth'] ?? '') ?: null;
        $date_joined = $t($_POST['date_joined'] ?? '') ?: null;
        $confirmation_date = null;
        $exit_termination_date = $t($_POST['exit_termination_date'] ?? '') ?: null;
        $position = $t($_POST['position'] ?? '');
        $gender = $t($_POST['gender'] ?? '');
        $phone_number = $t($_POST['phone_number'] ?? '');
        $address = $t($_POST['address'] ?? '');
        $biography = $t($_POST['biography'] ?? '');
        $marital_status = $t($_POST['marital_status'] ?? '');
        $employee_id = $t($_POST['employee_id'] ?? '');
        $department = $t($_POST['department'] ?? '');
        $employment_type = $t($_POST['employment_type'] ?? '');
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
        $new_hire = isset($_POST['new_hire']) && $_POST['new_hire'] === '1' ? 1 : null;
        $salary_adjustment_notes = $t($_POST['salary_adjustment_notes'] ?? '');
        $promotion_role_change = $t($_POST['promotion_role_change'] ?? '');
        $bank_detail_update = null;

        $decimal = function($v) { $v = trim($v ?? ''); return $v === '' ? null : (is_numeric($v) ? $v : null); };
        $basic_salary = $decimal($_POST['basic_salary'] ?? '');
        $housing_allowance = $decimal($_POST['housing_allowance'] ?? '');
        $transport_allowance = $decimal($_POST['transport_allowance'] ?? '');
        $gross_monthly_salary = $decimal($_POST['gross_monthly_salary'] ?? '');

        $bvn_confirm = $t($_POST['bvn_confirm'] ?? '');
        if (empty($email) || empty($full_name) || empty($password)) {
            $error = 'Email, full name and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } elseif ($password !== $password_confirm) {
            $error = 'Passwords do not match.';
        } elseif (empty($gender) || empty($date_of_birth) || empty($address) || empty($phone_number) || empty($marital_status)) {
            $error = 'Gender, date of birth, residential address, phone number and marital status are required.';
        } elseif (empty($department) || empty($employment_type) || empty($date_joined)) {
            $error = 'Department, employment type and start date are required.';
        } elseif ($basic_salary === null || $basic_salary === '') {
            $error = 'Basic salary is required.';
        } elseif (empty($bank_name) || empty($account_name) || empty($account_number) || empty($bvn)) {
            $error = 'Bank name, account name, account number and BVN are required.';
        } elseif (strlen($bvn) !== 11 || !ctype_digit($bvn)) {
            $error = 'BVN must be exactly 11 digits.';
        } elseif ($bvn !== $bvn_confirm) {
            $error = 'BVN and Confirm BVN do not match.';
        } elseif (empty($_FILES['cv_file']['name']) || $_FILES['cv_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'CV upload is required.';
        } elseif (empty($_FILES['nin_document']['name']) || $_FILES['nin_document']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'NIN (National Identification Number) document upload is required.';
        } elseif (empty($_FILES['profile_image']['name']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Passport photograph (profile picture) is required.';
        } else {
            $cv_path = handle_cv_upload($_FILES['cv_file']);
            $nin_path = handle_nin_document_upload($_FILES['nin_document']);
            $profile_image = handle_profile_upload($_FILES['profile_image']);
            if ($cv_path === false) {
                $error = 'Invalid CV. Use PDF or JPG/PNG, max 5MB.';
            } elseif ($nin_path === false) {
                $error = 'Invalid NIN document. Use PDF or JPG/PNG, max 5MB.';
            } elseif ($profile_image === false) {
                $error = 'Invalid passport photo. Use JPG or PNG, max 2MB.';
            } else {
            $stmt = $pdo->prepare("SELECT id FROM staff WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'This email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO staff (email, password, full_name, date_of_birth, date_joined, position, biography, phone_number, gender, address, profile_image, cv_path, nin_document_path, status,
                    marital_status, employee_id, department, employment_type, confirmation_date, reporting_manager, work_location,
                    basic_salary, housing_allowance, transport_allowance, other_allowances, gross_monthly_salary, overtime_rate, bonus_commission_structure,
                    bank_name, account_name, account_number, bvn,
                    tax_identification_number, pension_fund_administrator, pension_pin, nhf_number, nhis_hmo_provider, employee_contribution_percentages,
                    new_hire, exit_termination_date, salary_adjustment_notes, promotion_role_change, bank_detail_update, declaration_accepted, email_verified)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active',
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, 1, 0)";
                $stmt = $pdo->prepare($sql);
                try {
                    $stmt->execute([
                        $email, $hash, $full_name, $date_of_birth, $date_joined, $position, $biography, $phone_number, $gender, $address, $profile_image, $cv_path, $nin_path,
                        $marital_status, $employee_id, $department, $employment_type, $confirmation_date, $reporting_manager, $work_location,
                        $basic_salary, $housing_allowance, $transport_allowance, $other_allowances, $gross_monthly_salary, $overtime_rate, $bonus_commission_structure,
                        $bank_name, $account_name, $account_number, $bvn,
                        $tax_identification_number, $pension_fund_administrator, $pension_pin, $nhf_number, $nhis_hmo_provider, $employee_contribution_percentages,
                        $new_hire, $exit_termination_date, $salary_adjustment_notes, $promotion_role_change, $bank_detail_update
                    ]);
                    $code = (string) random_int(100000, 999999);
                    $expires = date('Y-m-d H:i:s', time() + (OTP_EXPIRY_MINUTES * 60));
                    $pdo->prepare("DELETE FROM verification_codes WHERE email = ? AND type = 'registration'")->execute([$email]);
                    $pdo->prepare("INSERT INTO verification_codes (email, code, type, expires_at) VALUES (?, ?, 'registration', ?)")->execute([$email, $code, $expires]);
                    $subject = 'Verify your email - Staff Portal';
                    $content = "Use the code below to verify your email. It expires in " . OTP_EXPIRY_MINUTES . " minutes.\n\nIf you did not register, ignore this email.";
                    send_portal_email($email, $subject, 'Verify your email', $content, ['code' => $code]);
                    header('Location: ' . BASE_URL . '/verify-email.php?email=' . rawurlencode($email));
                    exit;
                } catch (PDOException $e) {
                    $error = 'Registration failed. Please try again. Ensure the database schema is up to date (run database/migrations/001_update_staff_table.sql).';
                    delete_profile_image($profile_image);
                    delete_cv_file($cv_path);
                    delete_nin_document($nin_path);
                }
            }
            }
        }
    }
}

$post = $_POST ?? [];
$esc = function($key, $default = '') { return htmlspecialchars($post[$key] ?? $default, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Registration</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card multistep-card multistep-card-wide">
            <h1>Staff Registration</h1>
            <div class="multistep-progress multistep-progress-9">
                <span class="step-dot active" data-step="1">1</span>
                <span class="step-line"></span>
                <span class="step-dot" data-step="2">2</span>
                <span class="step-line"></span>
                <span class="step-dot" data-step="3">3</span>
                <span class="step-line"></span>
                <span class="step-dot" data-step="4">4</span>
                <span class="step-line"></span>
                <span class="step-dot" data-step="5">5</span>
                <span class="step-line"></span>
                <span class="step-dot" data-step="6">6</span>
                <span class="step-line"></span>
                <span class="step-dot" data-step="7">7</span>
                <span class="step-line"></span>
                <span class="step-dot" data-step="8">8</span>
                <span class="step-line"></span>
                <span class="step-dot" data-step="9">9</span>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="" id="register-form" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="multistep-panel active" data-step="1">
                    <h2 class="step-title">1. Account</h2>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" required value="<?= $esc('email') ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password * (min <?= PASSWORD_MIN_LENGTH ?> chars)</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Confirm Password *</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                    </div>
                    <button type="button" class="btn btn-primary btn-next" data-next="2" style="width:100%;">Next</button>
                </div>

                <div class="multistep-panel" data-step="2">
                    <h2 class="step-title">2. Employee Personal Information</h2>
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required value="<?= $esc('full_name') ?>">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value="">— Select —</option>
                            <option value="Male" <?= ($post['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($post['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($post['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth *</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" required value="<?= $esc('date_of_birth') ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Residential Address *</label>
                        <textarea id="address" name="address" class="form-control" rows="2" required><?= $esc('address') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number *</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control" required value="<?= $esc('phone_number') ?>">
                    </div>
                    <div class="form-group">
                        <label for="marital_status">Marital Status *</label>
                        <select id="marital_status" name="marital_status" class="form-control" required>
                            <option value="">— Select —</option>
                            <option value="Single" <?= ($post['marital_status'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                            <option value="Married" <?= ($post['marital_status'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                            <option value="Divorced" <?= ($post['marital_status'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                            <option value="Widowed" <?= ($post['marital_status'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                            <option value="Other" <?= ($post['marital_status'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-primary btn-prev" data-prev="1">Previous</button>
                        <button type="button" class="btn btn-primary btn-next" data-next="3">Next</button>
                    </div>
                </div>

                <div class="multistep-panel" data-step="3">
                    <h2 class="step-title">3. Employment Details</h2>
                    <div class="form-group">
                        <label for="employee_id">Employee ID (if applicable)</label>
                        <input type="text" id="employee_id" name="employee_id" class="form-control" value="<?= $esc('employee_id') ?>">
                    </div>
                    <div class="form-group">
                        <label for="position">Job Title</label>
                        <input type="text" id="position" name="position" class="form-control" value="<?= $esc('position') ?>">
                    </div>
                    <div class="form-group">
                        <label for="department">Department *</label>
                        <input type="text" id="department" name="department" class="form-control" required value="<?= $esc('department') ?>">
                    </div>
                    <div class="form-group">
                        <label for="employment_type">Employment Type *</label>
                        <select id="employment_type" name="employment_type" class="form-control" required>
                            <option value="">— Select —</option>
                            <option value="Full-time" <?= ($post['employment_type'] ?? '') === 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                            <option value="Part-time" <?= ($post['employment_type'] ?? '') === 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                            <option value="Contract" <?= ($post['employment_type'] ?? '') === 'Contract' ? 'selected' : '' ?>>Contract</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_joined">Employment Start Date *</label>
                        <input type="date" id="date_joined" name="date_joined" class="form-control" required value="<?= $esc('date_joined') ?>">
                    </div>
                    <div class="form-group">
                        <label for="reporting_manager">Reporting Manager</label>
                        <input type="text" id="reporting_manager" name="reporting_manager" class="form-control" value="<?= $esc('reporting_manager') ?>">
                    </div>
                    <div class="form-group">
                        <label for="work_location">Work Location</label>
                        <input type="text" id="work_location" name="work_location" class="form-control" value="<?= $esc('work_location') ?>">
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-primary btn-prev" data-prev="2">Previous</button>
                        <button type="button" class="btn btn-primary btn-next" data-next="4">Next</button>
                    </div>
                </div>

                <div class="multistep-panel" data-step="4">
                    <h2 class="step-title">4. Salary Structure</h2>
                    <div class="form-group">
                        <label for="basic_salary">Basic Salary *</label>
                        <input type="number" id="basic_salary" name="basic_salary" class="form-control" step="0.01" min="0" placeholder="0.00" required value="<?= $esc('basic_salary') ?>">
                    </div>
                    <div class="form-group">
                        <label for="housing_allowance">Housing Allowance</label>
                        <input type="number" id="housing_allowance" name="housing_allowance" class="form-control" step="0.01" min="0" value="<?= $esc('housing_allowance') ?>">
                    </div>
                    <div class="form-group">
                        <label for="transport_allowance">Transport Allowance</label>
                        <input type="number" id="transport_allowance" name="transport_allowance" class="form-control" step="0.01" min="0" value="<?= $esc('transport_allowance') ?>">
                    </div>
                    <div class="form-group">
                        <label for="other_allowances">Other Allowances (specify)</label>
                        <textarea id="other_allowances" name="other_allowances" class="form-control" rows="2"><?= $esc('other_allowances') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="gross_monthly_salary">Gross Monthly Salary</label>
                        <input type="number" id="gross_monthly_salary" name="gross_monthly_salary" class="form-control" step="0.01" min="0" value="<?= $esc('gross_monthly_salary') ?>">
                    </div>
                    <div class="form-group">
                        <label for="overtime_rate">Overtime Rate (if applicable)</label>
                        <input type="text" id="overtime_rate" name="overtime_rate" class="form-control" value="<?= $esc('overtime_rate') ?>">
                    </div>
                    <div class="form-group">
                        <label for="bonus_commission_structure">Bonus/Commission Structure (if applicable)</label>
                        <textarea id="bonus_commission_structure" name="bonus_commission_structure" class="form-control" rows="2"><?= $esc('bonus_commission_structure') ?></textarea>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-primary btn-prev" data-prev="3">Previous</button>
                        <button type="button" class="btn btn-primary btn-next" data-next="5">Next</button>
                    </div>
                </div>

                <div class="multistep-panel" data-step="5">
                    <h2 class="step-title">5. Bank Details</h2>
                    <div class="form-group">
                        <label for="bank_name">Bank Name *</label>
                        <input type="text" id="bank_name" name="bank_name" class="form-control" required value="<?= $esc('bank_name') ?>">
                    </div>
                    <div class="form-group">
                        <label for="account_name">Account Name *</label>
                        <input type="text" id="account_name" name="account_name" class="form-control" required value="<?= $esc('account_name') ?>">
                    </div>
                    <div class="form-group">
                        <label for="account_number">Account Number *</label>
                        <input type="text" id="account_number" name="account_number" class="form-control" required value="<?= $esc('account_number') ?>">
                    </div>
                    <div class="form-group">
                        <label for="bvn">BVN *</label>
                        <input type="text" id="bvn" name="bvn" class="form-control" maxlength="11" pattern="[0-9]{11}" title="BVN must be 11 digits" required value="<?= $esc('bvn') ?>">
                    </div>
                    <div class="form-group">
                        <label for="bvn_confirm">Confirm BVN *</label>
                        <input type="text" id="bvn_confirm" name="bvn_confirm" class="form-control" maxlength="11" pattern="[0-9]{11}" title="BVN must be 11 digits" required value="<?= $esc('bvn_confirm') ?>">
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-primary btn-prev" data-prev="4">Previous</button>
                        <button type="button" class="btn btn-primary btn-next" data-next="6">Next</button>
                    </div>
                </div>

                <div class="multistep-panel" data-step="6">
                    <h2 class="step-title">6. Documents &amp; Photo</h2>
                    <p class="form-hint">Upload your CV, NIN document, and passport photograph. All three are required.</p>
                    <div class="form-group">
                        <label for="cv_file">CV (Curriculum Vitae) *</label>
                        <input type="file" id="cv_file" name="cv_file" class="form-control" accept=".pdf,application/pdf,image/jpeg,image/jpg,image/png" required>
                        <small class="form-text">PDF or JPG/PNG, max 5MB</small>
                    </div>
                    <div class="form-group">
                        <label for="nin_document">NIN (National Identification Number) Document *</label>
                        <input type="file" id="nin_document" name="nin_document" class="form-control" accept=".pdf,application/pdf,image/jpeg,image/jpg,image/png" required>
                        <small class="form-text">Upload a scan or photo of your NIN slip/card. PDF or JPG/PNG, max 5MB</small>
                    </div>
                    <div class="form-group">
                        <label for="profile_image">Passport Photograph (Profile Picture) *</label>
                        <div class="upload-photo-wrap">
                            <div class="register-profile-preview-box">
                                <img id="register-profile-preview" src="<?= BASE_URL ?>/assets/images/placeholder.svg" alt="Preview" class="register-profile-preview-img">
                            </div>
                            <input type="file" id="profile_image" name="profile_image" class="form-control" accept="image/jpeg,image/jpg,image/png" required>
                        </div>
                        <small class="form-text">JPG or PNG, max 2MB. This will be used as your profile picture.</small>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-primary btn-prev" data-prev="5">Previous</button>
                        <button type="button" class="btn btn-primary btn-next" data-next="7">Next</button>
                    </div>
                </div>

                <div class="multistep-panel" data-step="7">
                    <h2 class="step-title">7. Statutory &amp; Compliance Information</h2>
                    <div class="form-group">
                        <label for="tax_identification_number">Tax Identification Number (TIN)</label>
                        <input type="text" id="tax_identification_number" name="tax_identification_number" class="form-control" value="<?= $esc('tax_identification_number') ?>">
                    </div>
                    <div class="form-group">
                        <label for="pension_fund_administrator">Pension Fund Administrator (PFA)</label>
                        <input type="text" id="pension_fund_administrator" name="pension_fund_administrator" class="form-control" value="<?= $esc('pension_fund_administrator') ?>">
                    </div>
                    <div class="form-group">
                        <label for="pension_pin">Pension PIN</label>
                        <input type="text" id="pension_pin" name="pension_pin" class="form-control" value="<?= $esc('pension_pin') ?>">
                    </div>
                    <div class="form-group">
                        <label for="nhf_number">NHF Number (if applicable)</label>
                        <input type="text" id="nhf_number" name="nhf_number" class="form-control" value="<?= $esc('nhf_number') ?>">
                    </div>
                    <div class="form-group">
                        <label for="nhis_hmo_provider">NHIS/HMO Provider</label>
                        <input type="text" id="nhis_hmo_provider" name="nhis_hmo_provider" class="form-control" value="<?= $esc('nhis_hmo_provider') ?>">
                    </div>
                    <div class="form-group">
                        <label for="employee_contribution_percentages">Employee Contribution Percentages (where applicable)</label>
                        <input type="text" id="employee_contribution_percentages" name="employee_contribution_percentages" class="form-control" value="<?= $esc('employee_contribution_percentages') ?>">
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-primary btn-prev" data-prev="6">Previous</button>
                        <button type="button" class="btn btn-primary btn-next" data-next="8">Next</button>
                    </div>
                </div>

                <div class="multistep-panel" data-step="8" id="preview-panel">
                    <h2 class="step-title">8. Review Your Information</h2>
                    <p class="form-hint">Please review all details below before proceeding to submit.</p>
                    <div id="preview-content" class="preview-summary"></div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-primary btn-prev" data-prev="7">Previous</button>
                        <button type="button" class="btn btn-primary btn-next" data-next="9">Confirm &amp; Continue</button>
                    </div>
                </div>

                <div class="multistep-panel" data-step="9">
                    <h2 class="step-title">9. Declaration</h2>
                    <div class="form-group">
                        <label for="new_hire">New Hire</label>
                        <select id="new_hire" name="new_hire" class="form-control">
                            <option value="">— Select —</option>
                            <option value="1" <?= ($post['new_hire'] ?? '') === '1' ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= ($post['new_hire'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="exit_termination_date">Exit/Termination (Effective Date)</label>
                        <input type="date" id="exit_termination_date" name="exit_termination_date" class="form-control" value="<?= $esc('exit_termination_date') ?>">
                    </div>
                    <div class="form-group">
                        <label for="salary_adjustment_notes">Salary Adjustment (Old vs New Salary)</label>
                        <textarea id="salary_adjustment_notes" name="salary_adjustment_notes" class="form-control" rows="2"><?= $esc('salary_adjustment_notes') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="promotion_role_change">Promotion/Role Change</label>
                        <input type="text" id="promotion_role_change" name="promotion_role_change" class="form-control" value="<?= $esc('promotion_role_change') ?>">
                    </div>
                    <div class="form-group declaration-box">
                        <p class="declaration-text">I confirm that the above information is accurate and complete.</p>
                        <label class="declaration-label">
                            <input type="checkbox" name="declaration_accepted" value="1" <?= !empty($post['declaration_accepted']) ? 'checked' : '' ?> required>
                            I agree
                        </label>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-primary btn-prev" data-prev="8">Previous</button>
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>
                </div>
            </form>
            <div class="auth-links">
                <a href="<?= BASE_URL ?>/login.php?type=staff">Already have an account? Login</a>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var form = document.getElementById('register-form');
        var panels = form.querySelectorAll('.multistep-panel');
        var dots = form.closest('.multistep-card').querySelectorAll('.step-dot');

        function getFormValue(name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (!el) return '';
            if (el.type === 'checkbox') return el.checked ? (el.value || 'Yes') : '';
            return (el.value || '').trim();
        }

        function getSelectLabel(name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (!el || el.tagName !== 'SELECT') return getFormValue(name);
            var opt = el.options[el.selectedIndex];
            return opt ? opt.text : '';
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function updatePreview() {
            var sections = [
                { title: 'Account', fields: [
                    { label: 'Email', value: getFormValue('email') },
                    { label: 'Full Name', value: getFormValue('full_name') }
                ]},
                { title: 'Personal Information', fields: [
                    { label: 'Gender', value: getSelectLabel('gender') },
                    { label: 'Date of Birth', value: getFormValue('date_of_birth') },
                    { label: 'Residential Address', value: getFormValue('address') },
                    { label: 'Phone Number', value: getFormValue('phone_number') },
                    { label: 'Marital Status', value: getSelectLabel('marital_status') }
                ]},
                { title: 'Employment Details', fields: [
                    { label: 'Employee ID', value: getFormValue('employee_id') },
                    { label: 'Job Title', value: getFormValue('position') },
                    { label: 'Department', value: getFormValue('department') },
                    { label: 'Employment Type', value: getSelectLabel('employment_type') },
                    { label: 'Start Date', value: getFormValue('date_joined') },
                    { label: 'Reporting Manager', value: getFormValue('reporting_manager') },
                    { label: 'Work Location', value: getFormValue('work_location') }
                ]},
                { title: 'Salary', fields: [
                    { label: 'Basic Salary', value: getFormValue('basic_salary') },
                    { label: 'Housing Allowance', value: getFormValue('housing_allowance') },
                    { label: 'Transport Allowance', value: getFormValue('transport_allowance') },
                    { label: 'Gross Monthly Salary', value: getFormValue('gross_monthly_salary') }
                ]},
                { title: 'Bank Details', fields: [
                    { label: 'Bank Name', value: getFormValue('bank_name') },
                    { label: 'Account Name', value: getFormValue('account_name') },
                    { label: 'Account Number', value: getFormValue('account_number') },
                    { label: 'BVN', value: getFormValue('bvn') }
                ]},
                { title: 'Documents & Photo', fields: (function() {
                    var cv = form.querySelector('[name="cv_file"]');
                    var nin = form.querySelector('[name="nin_document"]');
                    var photo = form.querySelector('[name="profile_image"]');
                    var arr = [];
                    if (cv && cv.files && cv.files[0]) arr.push({ label: 'CV', value: cv.files[0].name });
                    if (nin && nin.files && nin.files[0]) arr.push({ label: 'NIN Document', value: nin.files[0].name });
                    if (photo && photo.files && photo.files[0]) arr.push({ label: 'Passport Photo', value: photo.files[0].name });
                    return arr;
                })() },
                { title: 'Statutory & Compliance', fields: [
                    { label: 'TIN', value: getFormValue('tax_identification_number') },
                    { label: 'PFA', value: getFormValue('pension_fund_administrator') },
                    { label: 'Pension PIN', value: getFormValue('pension_pin') },
                    { label: 'NHIS/HMO', value: getFormValue('nhis_hmo_provider') }
                ]},
                { title: 'Payroll', fields: [
                    { label: 'New Hire', value: getSelectLabel('new_hire') },
                    { label: 'Exit/Termination Date', value: getFormValue('exit_termination_date') },
                    { label: 'Promotion/Role Change', value: getFormValue('promotion_role_change') }
                ]}
            ];
            var html = '';
            sections.forEach(function(s) {
                html += '<div class="preview-section"><h3 class="preview-section-title">' + escapeHtml(s.title || '') + '</h3><dl class="preview-dl">';
                s.fields.forEach(function(f) {
                    if (f.value) html += '<dt>' + escapeHtml(f.label) + '</dt><dd>' + escapeHtml(f.value) + '</dd>';
                });
                html += '</dl></div>';
            });
            var wrap = form.querySelector('#preview-content');
            if (wrap) wrap.innerHTML = html || '<p>No data to display.</p>';
        }

        function showStep(step) {
            step = parseInt(step, 10);
            if (step === 8) updatePreview();
            panels.forEach(function(p) {
                p.classList.toggle('active', parseInt(p.getAttribute('data-step'), 10) === step);
            });
            dots.forEach(function(d) {
                var n = parseInt(d.getAttribute('data-step'), 10);
                d.classList.toggle('active', n === step);
                d.classList.toggle('done', n < step);
            });
        }

        var passwordEl = form.querySelector('[name="password"]');
        var passwordConfirmEl = form.querySelector('[name="password_confirm"]');
        function syncPasswordValidity() {
            if (!passwordEl || !passwordConfirmEl) return;
            var p1 = (passwordEl.value || '');
            var p2 = (passwordConfirmEl.value || '');
            if (p2 && p1 !== p2) {
                passwordConfirmEl.setCustomValidity('Passwords do not match.');
            } else {
                passwordConfirmEl.setCustomValidity('');
            }
        }
        if (passwordEl) passwordEl.addEventListener('input', syncPasswordValidity);
        if (passwordConfirmEl) passwordConfirmEl.addEventListener('input', syncPasswordValidity);

        var bvnEl = form.querySelector('[name="bvn"]');
        var bvnConfirmEl = form.querySelector('[name="bvn_confirm"]');
        function syncBvnValidity() {
            if (!bvnEl || !bvnConfirmEl) return;
            var bvn = (bvnEl.value || '').trim();
            var bvn2 = (bvnConfirmEl.value || '').trim();
            if (bvn2 && bvn !== bvn2) {
                bvnConfirmEl.setCustomValidity('BVN and Confirm BVN do not match.');
            } else {
                bvnConfirmEl.setCustomValidity('');
            }
        }
        if (bvnEl) bvnEl.addEventListener('input', syncBvnValidity);
        if (bvnConfirmEl) bvnConfirmEl.addEventListener('input', syncBvnValidity);

        function validatePanel(panel) {
            if (!panel) return true;
            syncPasswordValidity();
            syncBvnValidity();
            var fields = panel.querySelectorAll('input, select, textarea');
            for (var i = 0; i < fields.length; i++) {
                var el = fields[i];
                if (el.disabled) continue;
                if (!el.checkValidity()) {
                    if (el.scrollIntoView) el.scrollIntoView({ block: 'center' });
                    try { el.focus({ preventScroll: true }); } catch (e) { try { el.focus(); } catch (e2) {} }
                    if (el.reportValidity) el.reportValidity();
                    return false;
                }
            }
            return true;
        }

        form.querySelectorAll('.btn-next').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var panel = this.closest('.multistep-panel');
                if (validatePanel(panel)) showStep(this.getAttribute('data-next'));
            });
        });
        form.querySelectorAll('.btn-prev').forEach(function(btn) {
            btn.addEventListener('click', function() {
                showStep(this.getAttribute('data-prev'));
            });
        });

        form.addEventListener('submit', function(e) {
            syncBvnValidity();
            if (form.checkValidity()) return;
            e.preventDefault();
            var firstInvalid = form.querySelector(':invalid');
            if (!firstInvalid) return;
            var invalidPanel = firstInvalid.closest('.multistep-panel');
            if (invalidPanel) {
                showStep(invalidPanel.getAttribute('data-step'));
            }
            setTimeout(function() {
                if (firstInvalid.scrollIntoView) firstInvalid.scrollIntoView({ block: 'center' });
                try { firstInvalid.focus({ preventScroll: true }); } catch (e2) { try { firstInvalid.focus(); } catch (e3) {} }
                if (firstInvalid.reportValidity) firstInvalid.reportValidity();
            }, 0);
        });

        var profileInput = form.querySelector('[name="profile_image"]');
        var profilePreview = document.getElementById('register-profile-preview');
        var placeholderSrc = profilePreview ? profilePreview.src : '';
        if (profileInput && profilePreview) {
            profileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    var r = new FileReader();
                    r.onload = function(e) { profilePreview.src = e.target.result; };
                    r.readAsDataURL(this.files[0]);
                } else {
                    profilePreview.src = placeholderSrc;
                }
            });
        }
    })();
    </script>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
