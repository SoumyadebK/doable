<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $upload_path;

$title = "My Profile";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$err_msg = '';
$success_msg = '';
$PK_USER = $_SESSION['PK_USER'];
if (!empty($_POST)) {
    if ($_POST['FORM_TYPE'] == 'change_password_form') {
        if ($_POST['NEW_PASSWORD'] == $_POST['CONFIRM_NEW_PASSWORD']) {
            $result = $db->Execute("SELECT PASSWORD FROM `DOA_USERS` WHERE PK_USER = '$_SESSION[PK_USER]'");
            if ($result->RecordCount() > 0) {
                if (password_verify($_POST['OLD_PASSWORD'], $result->fields['PASSWORD'])) {
                    $USER_DATA['PASSWORD'] = password_hash($_POST['NEW_PASSWORD'], PASSWORD_DEFAULT);
                    db_perform('DOA_USERS', $USER_DATA, 'update', " PK_USER =  '$_SESSION[PK_USER]'");
                    $success_msg = "Password Changed Successfully.";
                } else {
                    $err_msg = 'Old Password is Wrong.';
                }
            }
        } else {
            $err_msg = 'Password and Confirm Password Not Matched.';
        }
    } else {
        if ($_FILES['USER_IMAGE']['name'] != '') {
            if (!file_exists('../' . $upload_path . '/user_image/')) {
                mkdir('../' . $upload_path . '/user_image/', 0777, true);
                chmod('../' . $upload_path . '/user_image/', 0777);
            }

            $USER_DATA = [];
            $extn = explode(".", $_FILES['USER_IMAGE']['name']);
            $iindex = count($extn) - 1;
            $rand_string = time() . "-" . rand(100000, 999999);
            $file11 = 'user_image_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
            $extension = strtolower($extn[$iindex]);

            if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
                $image_path = '../' . $upload_path . '/user_image/' . $file11;
                move_uploaded_file($_FILES['USER_IMAGE']['tmp_name'], $image_path);
                $USER_DATA['USER_IMAGE'] = $image_path;
            }
        }
        $USER_DATA['PHONE'] = $_POST['PHONE'];
        $USER_DATA['GENDER'] = $_POST['GENDER'];
        $USER_DATA['DOB'] = date('Y-m-d', strtotime($_POST['DOB']));
        $USER_DATA['ADDRESS'] = $_POST['ADDRESS'];
        $USER_DATA['ADDRESS_1'] = $_POST['ADDRESS_1'];
        $USER_DATA['PK_COUNTRY'] = $_POST['PK_COUNTRY'];
        $USER_DATA['PK_STATES'] = $_POST['PK_STATES'];
        $USER_DATA['CITY'] = $_POST['CITY'];
        $USER_DATA['ZIP'] = $_POST['ZIP'];
        $USER_DATA['NOTES'] = $_POST['NOTES'];
        db_perform('DOA_USERS', $USER_DATA, 'update', " PK_USER = " . $PK_USER);

        $USER_DATA_ACCOUNT['PHONE'] = $_POST['PHONE'];
        db_perform_account('DOA_USERS', $USER_DATA_ACCOUNT, 'update', " PK_USER_MASTER_DB = " . $PK_USER);
    }
}

$res = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = " . $PK_USER);

if ($res->RecordCount() == 0) {
    header("location:../login.php");
    exit;
}

$selected_roles_row = $db->Execute("SELECT DOA_ROLES.ROLES FROM `DOA_USER_ROLES` LEFT JOIN DOA_ROLES ON DOA_USER_ROLES.PK_ROLES = DOA_ROLES.PK_ROLES WHERE `PK_USER` = '$PK_USER'");
$selected_roles = [];
while (!$selected_roles_row->EOF) {
    $selected_roles[] = $selected_roles_row->fields['ROLES'];
    $selected_roles_row->MoveNext();
}

$USER_NAME = $res->fields['USER_NAME'];
$FIRST_NAME = $res->fields['FIRST_NAME'];
$LAST_NAME = $res->fields['LAST_NAME'];
$EMAIL_ID = $res->fields['EMAIL_ID'];
$USER_IMAGE = $res->fields['USER_IMAGE'];
$GENDER = $res->fields['GENDER'];
$DOB = $res->fields['DOB'];
$ADDRESS = $res->fields['ADDRESS'];
$ADDRESS_1 = $res->fields['ADDRESS_1'];
$PK_COUNTRY = $res->fields['PK_COUNTRY'];
$PK_STATES = $res->fields['PK_STATES'];
$CITY = $res->fields['CITY'];
$ZIP = $res->fields['ZIP'];
$PHONE = $res->fields['PHONE'];
$NOTES = $res->fields['NOTES'];
$ACTIVE = $res->fields['ACTIVE'];
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
    :root {
        --primary-color: #39B54A;
        --primary-light: #5DCB6E;
        --primary-dark: #2D8F3B;
        --primary-rgb: 57, 181, 74;
        --success-color: #39B54A;
        --warning-color: #F59E0B;
        --danger-color: #EF4444;
        --gray-50: #F9FAFB;
        --gray-100: #F3F4F6;
        --gray-200: #E5E7EB;
        --gray-300: #D1D5DB;
        --gray-400: #9CA3AF;
        --gray-500: #6B7280;
        --gray-600: #4B5563;
        --gray-700: #374151;
        --gray-800: #1F2937;
        --gray-900: #111827;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --radius: 12px;
        --radius-sm: 8px;
        --radius-lg: 16px;
        --radius-pill: 50px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        background: var(--gray-50);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .page-wrapper {
        padding-top: 0px !important;
        background: var(--gray-50);
    }

    /* Breadcrumb */
    .breadcrumb-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .breadcrumb-wrapper h4 {
        font-size: 24px;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
        letter-spacing: -0.025em;
    }

    .breadcrumb-wrapper h4 i {
        color: var(--primary-color);
        margin-right: 10px;
    }

    .breadcrumb-nav {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-500);
    }

    .breadcrumb-nav a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .breadcrumb-nav a:hover {
        color: var(--primary-dark);
    }

    .breadcrumb-nav .separator {
        color: var(--gray-300);
    }

    .breadcrumb-nav .current {
        color: var(--gray-700);
        font-weight: 500;
    }

    /* Card */
    .card-modern {
        background: #ffffff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }

    .card-modern:hover {
        box-shadow: var(--shadow-md);
    }

    .card-modern .card-header {
        padding: 20px 24px;
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .card-modern .card-header h5 {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .card-modern .card-header h5 i {
        color: var(--primary-color);
    }

    .card-modern .card-body {
        padding: 28px 32px;
    }

    @media (max-width: 768px) {
        .card-modern .card-body {
            padding: 20px;
        }

        .container-fluid {
            padding: 16px !important;
        }
    }

    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 500;
    }

    .status-indicator.active {
        background: #D1FAE5;
        color: #065F46;
    }

    .status-indicator.inactive {
        background: #FEE2E2;
        color: #991B1B;
    }

    .status-indicator i {
        font-size: 8px;
    }

    /* Profile Header */
    .profile-header {
        display: flex;
        align-items: center;
        gap: 24px;
        padding: 16px 20px;
        background: var(--gray-50);
        border-radius: var(--radius-sm);
        border: 1px solid var(--gray-200);
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .profile-header .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid var(--primary-color);
        flex-shrink: 0;
        background: var(--gray-200);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .profile-header .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-header .profile-avatar .avatar-placeholder {
        font-size: 32px;
        color: var(--gray-400);
    }

    .profile-header .profile-info {
        flex: 1;
        display: flex;
        flex-wrap: wrap;
        gap: 8px 24px;
    }

    .profile-header .profile-info .info-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        color: var(--gray-600);
    }

    .profile-header .profile-info .info-item i {
        color: var(--primary-color);
        width: 18px;
        text-align: center;
    }

    .profile-header .profile-info .info-item .label {
        font-weight: 500;
        color: var(--gray-500);
    }

    .profile-header .profile-info .info-item .value {
        color: var(--gray-800);
        font-weight: 500;
    }

    .profile-header .profile-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .profile-header .profile-info {
            justify-content: center;
        }

        .profile-header .profile-actions {
            justify-content: center;
        }
    }

    /* Form */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px 24px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-group-modern {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group-modern .form-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--gray-700);
        letter-spacing: 0.01em;
    }

    .form-group-modern .form-label .required {
        color: var(--danger-color);
        margin-left: 2px;
    }

    .form-control-modern {
        width: 100%;
        padding: 10px 14px;
        font-size: 14px;
        color: var(--gray-800);
        background: #fff;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        transition: all 0.2s ease;
        outline: none;
        font-family: inherit;
    }

    .form-control-modern:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .form-control-modern:hover {
        border-color: var(--gray-300);
    }

    .form-control-modern::placeholder {
        color: var(--gray-400);
        font-size: 13px;
    }

    .form-control-modern.is-invalid {
        border-color: var(--danger-color);
    }

    .form-control-modern.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    textarea.form-control-modern {
        min-height: 80px;
        resize: vertical;
    }

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    /* Buttons */
    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 28px;
        font-size: 14px;
        font-weight: 500;
        border: none;
        border-radius: var(--radius-pill);
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-family: inherit;
        line-height: 1.5;
    }

    .btn-modern-primary {
        background: var(--primary-color);
        color: #fff;
    }

    .btn-modern-primary:hover {
        background: var(--primary-dark);
        box-shadow: var(--shadow-md);
        transform: translateY(-1px);
        color: #fff;
    }

    .btn-modern-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-modern-secondary {
        background: var(--gray-100);
        color: var(--gray-700);
    }

    .btn-modern-secondary:hover {
        background: var(--gray-200);
        color: var(--gray-800);
    }

    .btn-modern-outline {
        background: transparent;
        color: var(--primary-color);
        border: 1.5px solid var(--primary-color);
    }

    .btn-modern-outline:hover {
        background: var(--primary-color);
        color: #fff;
    }

    .btn-modern-sm {
        padding: 6px 16px;
        font-size: 13px;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px solid var(--gray-200);
        flex-wrap: wrap;
    }

    @media (max-width: 640px) {
        .form-actions {
            flex-direction: column;
        }

        .form-actions .btn-modern {
            width: 100%;
            justify-content: center;
        }

        .breadcrumb-wrapper {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    /* Password Change Section */
    .password-section {
        margin-top: 16px;
        padding: 20px 24px;
        background: var(--gray-50);
        border-radius: var(--radius-sm);
        border: 1px solid var(--gray-200);
    }

    .password-section .section-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .password-section .section-title i {
        color: var(--primary-color);
    }

    /* Image Preview */
    .image-preview {
        width: 120px;
        height: 120px;
        border-radius: var(--radius-sm);
        overflow: hidden;
        border: 2px solid var(--gray-200);
        margin-top: 8px;
        background: var(--gray-100);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .image-preview .placeholder-icon {
        font-size: 40px;
        color: var(--gray-400);
    }

    .image-preview a {
        display: block;
        width: 100%;
        height: 100%;
    }

    /* Alert */
    .alert-modern {
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        font-size: 14px;
        margin: 12px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-modern.success {
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #A7F3D0;
    }

    .alert-modern.error {
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FCA5A5;
    }

    .alert-modern i {
        font-size: 18px;
    }

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
    }

    .full-width {
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .full-width {
            grid-column: 1;
        }
    }

    .mt-10 {
        margin-top: 10px;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid" style="margin-top: 22px !important;">
                <!-- Main Content -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card-modern">
                            <div class="card-header">
                                <h5>
                                    <i class="fas fa-user-cog"></i>
                                    My Profile
                                </h5>
                                <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                    <i class="fas fa-circle" style="color: <?= ($ACTIVE == 1) ? '#39B54A' : '#EF4444' ?>; font-size: 10px; margin-right: 6px;"></i>
                                    <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <!-- Profile Header -->
                                <div class="profile-header">
                                    <div class="profile-avatar">
                                        <?php if ($USER_IMAGE != ''): ?>
                                            <img id="profile-img" src="<?= htmlspecialchars($USER_IMAGE) ?>" alt="Profile Image">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="profile-info">
                                        <div class="info-item">
                                            <i class="fas fa-user"></i>
                                            <span class="label">Name:</span>
                                            <span class="value"><?= htmlspecialchars($FIRST_NAME . ' ' . $LAST_NAME) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-envelope"></i>
                                            <span class="label">Email:</span>
                                            <span class="value"><?= htmlspecialchars($EMAIL_ID) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-user-tag"></i>
                                            <span class="label">Role:</span>
                                            <span class="value"><?= htmlspecialchars(implode(', ', $selected_roles)) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-user-circle"></i>
                                            <span class="label">Username:</span>
                                            <span class="value"><?= htmlspecialchars($USER_NAME) ?></span>
                                        </div>
                                    </div>
                                    <div class="profile-actions">
                                        <button class="btn-modern btn-modern-primary btn-modern-sm" onclick="$('#change_password_div').slideToggle();">
                                            <i class="fas fa-key"></i> Change Password
                                        </button>
                                    </div>
                                </div>

                                <!-- Success/Error Messages -->
                                <?php if ($success_msg): ?>
                                    <div class="alert-modern success">
                                        <i class="fas fa-check-circle"></i>
                                        <span><?= htmlspecialchars($success_msg); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($err_msg): ?>
                                    <div class="alert-modern error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span><?= htmlspecialchars($err_msg); ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Change Password Section -->
                                <div id="change_password_div" class="password-section" style="display: <?= ($err_msg) ? 'block' : 'none' ?>;">
                                    <div class="section-title">
                                        <i class="fas fa-lock"></i> Change Password
                                    </div>
                                    <form class="form-material" action="" method="post">
                                        <input type="hidden" name="FORM_TYPE" value="change_password_form">
                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Old Password <span class="required">*</span></label>
                                                <input type="password" name="OLD_PASSWORD" class="form-control-modern" required>
                                            </div>
                                            <div class="form-group-modern">
                                                <label class="form-label">New Password <span class="required">*</span></label>
                                                <input type="password" name="NEW_PASSWORD" class="form-control-modern" required>
                                            </div>
                                            <div class="form-group-modern">
                                                <label class="form-label">Confirm New Password <span class="required">*</span></label>
                                                <input type="password" name="CONFIRM_NEW_PASSWORD" class="form-control-modern" required>
                                            </div>
                                            <div class="form-group-modern" style="justify-content: flex-end;">
                                                <button type="submit" class="btn-modern btn-modern-primary">
                                                    <i class="fas fa-save"></i> Change Password
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Profile Form -->
                                <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data" style="margin-top: 20px;">
                                    <input type="hidden" name="FORM_TYPE" value="profile_form">

                                    <div class="form-grid">
                                        <!-- Gender -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Gender</label>
                                            <select class="form-control-modern" id="GENDER" name="GENDER">
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?php if ($GENDER == "Male") echo 'selected = "selected"'; ?>>Male</option>
                                                <option value="Female" <?php if ($GENDER == "Female") echo 'selected = "selected"'; ?>>Female</option>
                                                <option value="Other" <?php if ($GENDER == "Other") echo 'selected = "selected"'; ?>>Other</option>
                                            </select>
                                            <div class="form-helper">Select your gender</div>
                                        </div>

                                        <!-- Date of Birth -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Date of Birth</label>
                                            <input type="text" class="form-control-modern datepicker-past" id="DOB" name="DOB" placeholder="mm/dd/yyyy" value="<?= ($DOB) ? date('m/d/Y', strtotime($DOB)) : '' ?>">
                                            <div class="form-helper">Your date of birth</div>
                                        </div>

                                        <!-- Address -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Address</label>
                                            <input type="text" id="ADDRESS" name="ADDRESS" class="form-control-modern" placeholder="Enter Address" value="<?php echo htmlspecialchars($ADDRESS) ?>">
                                            <div class="form-helper">Street address</div>
                                        </div>

                                        <!-- Apt/Ste -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Apt/Ste</label>
                                            <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control-modern" placeholder="Enter Apartment or Suite" value="<?php echo htmlspecialchars($ADDRESS_1) ?>">
                                            <div class="form-helper">Apartment, suite, unit, etc.</div>
                                        </div>

                                        <!-- Country -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Country <span class="required">*</span></label>
                                            <select class="form-control-modern" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
                                                <option value="">Select Country</option>
                                                <?php
                                                $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_COUNTRY']; ?>" <?= ($row->fields['PK_COUNTRY'] == $PK_COUNTRY) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['COUNTRY_NAME']) ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                            <div class="form-helper">Select your country</div>
                                        </div>

                                        <!-- State -->
                                        <div class="form-group-modern">
                                            <label class="form-label">State <span class="required">*</span></label>
                                            <div id="State_div"></div>
                                            <div class="form-helper">Select your state</div>
                                        </div>

                                        <!-- City -->
                                        <div class="form-group-modern">
                                            <label class="form-label">City</label>
                                            <input type="text" id="CITY" name="CITY" class="form-control-modern" placeholder="Enter your city" value="<?php echo htmlspecialchars($CITY) ?>">
                                            <div class="form-helper">Your city</div>
                                        </div>

                                        <!-- Zip -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Postal / Zip Code</label>
                                            <input type="text" id="ZIP" name="ZIP" class="form-control-modern" placeholder="Enter Postal / Zip Code" value="<?php echo htmlspecialchars($ZIP) ?>">
                                            <div class="form-helper">Postal or zip code</div>
                                        </div>

                                        <!-- Phone -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Phone</label>
                                            <input type="text" id="PHONE" name="PHONE" class="form-control-modern" placeholder="Enter Phone No." value="<?php echo htmlspecialchars($PHONE) ?>">
                                            <div class="form-helper">Contact phone number</div>
                                        </div>

                                        <!-- Image Upload -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Profile Image</label>
                                            <input type="file" name="USER_IMAGE" id="USER_IMAGE" class="form-control-modern" onchange="previewFile(this)" accept="image/*">
                                            <div class="form-helper">Upload a profile image (JPG, PNG, GIF)</div>
                                        </div>

                                        <!-- Image Preview -->
                                        <div class="form-group-modern" style="grid-column: 1 / -1;">
                                            <?php if ($USER_IMAGE != ''): ?>
                                                <div class="image-preview">
                                                    <a class="fancybox" href="<?php echo htmlspecialchars($USER_IMAGE); ?>" data-fancybox-group="gallery">
                                                        <img id="profile-img-preview" src="<?php echo htmlspecialchars($USER_IMAGE); ?>" alt="Profile Image">
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="image-preview">
                                                    <div class="placeholder-icon">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Remarks -->
                                        <div class="form-group-modern full-width">
                                            <label class="form-label">Remarks</label>
                                            <textarea class="form-control-modern" rows="3" id="NOTES" name="NOTES" placeholder="Add any remarks"><?php echo htmlspecialchars($NOTES) ?></textarea>
                                            <div class="form-helper">Additional notes or remarks</div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="form-actions">
                                        <button type="submit" class="btn-modern btn-modern-primary">
                                            <i class="fas fa-save"></i> Update Profile
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script>
        // Date picker
        $('.datepicker-past').datepicker({
            format: 'mm/dd/yyyy',
            maxDate: 0,
            changeMonth: true,
            changeYear: true,
            yearRange: '1900:' + new Date().getFullYear(),
        });

        $(document).ready(function() {
            fetch_state(<?php echo $PK_COUNTRY; ?>);
        });

        function fetch_state(PK_COUNTRY) {
            jQuery(document).ready(function($) {
                var data = "PK_COUNTRY=" + PK_COUNTRY + "&PK_STATES=<?= $PK_STATES; ?>";
                var value = $.ajax({
                    url: "ajax/state.php",
                    type: "POST",
                    data: data,
                    async: false,
                    cache: false,
                    success: function(result) {
                        document.getElementById('State_div').innerHTML = result;
                    }
                }).responseText;
            });
        }

        // Image preview
        function previewFile(input) {
            let file = $("#USER_IMAGE").get(0).files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function() {
                    $("#profile-img-preview").attr("src", reader.result);
                }
                reader.readAsDataURL(file);
            }
        }

        // Password strength indicator
        function isGood(password) {
            var password_strength = document.getElementById("password-text");

            if (password.length == 0) {
                password_strength.innerHTML = "";
                return;
            }

            var regex = new Array();
            regex.push("[A-Z]");
            regex.push("[a-z]");
            regex.push("[0-9]");
            regex.push("[$@$!%*#?&]");

            var passed = 0;

            for (var i = 0; i < regex.length; i++) {
                if (new RegExp(regex[i]).test(password)) {
                    passed++;
                }
            }

            var strength = "";
            switch (passed) {
                case 0:
                case 1:
                case 2:
                    strength = "<small class='progress-bar bg-danger' style='width: 50%'>Weak</small>";
                    break;
                case 3:
                    strength = "<small class='progress-bar bg-warning' style='width: 60%'>Medium</small>";
                    break;
                case 4:
                    strength = "<small class='progress-bar bg-success' style='width: 100%'>Strong</small>";
                    break;
            }
            password_strength.innerHTML = strength;
        }

        // Toggle password section
        $('#change_password_div').on('click', function() {
            $(this).slideToggle();
        });
    </script>
</body>

</html>