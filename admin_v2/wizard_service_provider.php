<?php
require_once('../global/config.php');
global $db;

$title = "Add Service Provider";

$userType = "Users";
$user_role_condition = " AND PK_ROLES IN(2,3,5,6,7,8,9,10)";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$CREATE_LOGIN = 0;
$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

$PK_USER = '';
$PK_CUSTOMER_DETAILS = '';
$USER_NAME = '';
$FIRST_NAME = '';
$LAST_NAME = '';
$EMAIL_ID = '';
$DISPLAY_ORDER = '';
$USER_IMAGE = '';
$GENDER = '';
$DOB = '';
$ADDRESS = '';
$ADDRESS_1 = '';
$PK_COUNTRY = '';
$PK_STATES = '';
$CITY = '';
$ZIP = '';
$PHONE = '';
$PK_LOCATION = '';
$USER_TITLE = '';
$NOTES = '';
$PASSWORD = '';
$ACTIVE = '';
$INACTIVE_BY_ADMIN = '';
$CAN_EDIT_ENROLLMENT = '';
$TICKET_SYSTEM_ACCESS = '';
$APPEAR_IN_CALENDAR = 0;

$selected_roles = array();
if (!empty($_GET['id'])) {
    $res = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_users.php");
        exit;
    }
    $PK_USER = $res->fields['PK_USER'];
    $USER_NAME = $res->fields['USER_NAME'];
    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
    $EMAIL_ID = $res->fields['EMAIL_ID'];
    $DISPLAY_ORDER = $res->fields['DISPLAY_ORDER'];
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
    $PASSWORD = $res->fields['PASSWORD'];
    $INACTIVE_BY_ADMIN = $res->fields['INACTIVE_BY_ADMIN'];
    $CAN_EDIT_ENROLLMENT = $res->fields['CAN_EDIT_ENROLLMENT'];
    $CREATE_LOGIN = $res->fields['CREATE_LOGIN'];
    $APPEAR_IN_CALENDAR = $res->fields['APPEAR_IN_CALENDAR'];
    $TICKET_SYSTEM_ACCESS = $res->fields['TICKET_SYSTEM_ACCESS'];

    $PK_USER = $_GET['id'];
    $selected_roles_row = $db->Execute("SELECT PK_ROLES FROM `DOA_USER_ROLES` WHERE `PK_USER` = '$PK_USER'");
    while (!$selected_roles_row->EOF) {
        $selected_roles[] = $selected_roles_row->fields['PK_ROLES'];
        $selected_roles_row->MoveNext();
    }
}

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'service_provider'");
if ($help->RecordCount() > 0) {
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet" />
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

    /* Layout */
    .main-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 24px;
    }

    @media (max-width: 1200px) {
        .main-grid {
            grid-template-columns: 1fr;
        }

        .container-fluid {
            padding: 16px !important;
        }
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

    .card-modern .card-header .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 500;
    }

    .card-modern .card-header .status-indicator.active {
        background: #D1FAE5;
        color: #065F46;
    }

    .card-modern .card-header .status-indicator.inactive {
        background: #FEE2E2;
        color: #991B1B;
    }

    .card-modern .card-header .status-indicator i {
        font-size: 8px;
        margin-right: 0;
    }

    .card-modern .card-body {
        padding: 28px 32px;
    }

    @media (max-width: 768px) {
        .card-modern .card-body {
            padding: 20px;
        }
    }

    /* Tabs */
    .tabs-modern {
        display: flex;
        gap: 8px;
        border-bottom: 1px solid var(--gray-200);
        padding: 0 32px;
        background: #fff;
        overflow-x: auto;
    }

    @media (max-width: 768px) {
        .tabs-modern {
            padding: 0 20px;
        }
    }

    .tabs-modern .tab-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 16px 20px;
        font-size: 14px;
        font-weight: 500;
        color: var(--gray-500);
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        white-space: nowrap;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .tabs-modern .tab-link i {
        font-size: 16px;
    }

    .tabs-modern .tab-link:hover {
        color: var(--gray-700);
        background: var(--gray-50);
    }

    .tabs-modern .tab-link.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        background: transparent;
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

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 2px;
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

    .form-control-modern[readonly] {
        background: var(--gray-50);
        cursor: not-allowed;
    }

    .form-control-modern.is-invalid {
        border-color: var(--danger-color);
    }

    textarea.form-control-modern {
        resize: vertical;
        min-height: 88px;
    }

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    /* Input group with prefix */
    .input-group-modern {
        display: flex;
        align-items: stretch;
    }

    .input-group-modern .input-prefix {
        display: flex;
        align-items: center;
        padding: 0 14px;
        background: var(--gray-100);
        border: 1.5px solid var(--gray-200);
        border-right: none;
        border-top-left-radius: var(--radius-sm);
        border-bottom-left-radius: var(--radius-sm);
        font-size: 14px;
        color: var(--gray-600);
    }

    .input-group-modern .form-control-modern {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    /* Radio */
    .radio-group-modern {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        padding-top: 4px;
    }

    .radio-group-modern .radio-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
    }

    .radio-group-modern .radio-item input[type="radio"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
        cursor: pointer;
        flex-shrink: 0;
    }

    /* Checkbox */
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
    }

    .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
        cursor: pointer;
        flex-shrink: 0;
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

    .btn-modern-danger {
        background: #FEE2E2;
        color: #991B1B;
        padding: 10px 16px;
    }

    .btn-modern-danger:hover {
        background: #FCA5A5;
        color: #7F1D1D;
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

    /* Section Header */
    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        background: var(--gray-50);
        border-radius: var(--radius-sm);
        margin: 24px 0 20px 0;
        border: 1px solid var(--gray-200);
        grid-column: 1 / -1;
    }

    .section-header:first-child {
        margin-top: 0;
    }

    .section-header i {
        color: var(--primary-color);
        font-size: 18px;
    }

    .section-header span {
        font-size: 14px;
        font-weight: 600;
        color: var(--gray-700);
    }

    /* Profile image preview */
    .image-preview {
        width: 120px;
        height: 120px;
        border-radius: var(--radius-sm);
        object-fit: cover;
        border: 1.5px solid var(--gray-200);
        margin-top: 8px;
    }

    /* Password strength */
    .password-strength-bar {
        height: 6px;
        border-radius: 50px;
        background: var(--gray-200);
        overflow: hidden;
        margin-top: 6px;
    }

    .password-strength-bar .bar-fill {
        height: 100%;
        border-radius: 50px;
        transition: all 0.2s ease;
    }

    .password-strength-text {
        font-size: 12px;
        font-weight: 500;
        margin-top: 4px;
    }

    /* Engagement terms / rate rows */
    .rate-row {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        gap: 16px;
        align-items: center;
        padding: 12px 16px;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
        margin-bottom: 10px;
        background: #fff;
    }

    @media (max-width: 640px) {
        .rate-row {
            grid-template-columns: 1fr;
        }
    }

    .rate-row .rate-name {
        font-size: 14px;
        font-weight: 500;
        color: var(--gray-700);
    }

    .rate-row .rate-input-wrap {
        display: flex;
        justify-content: flex-end;
    }

    .rate-row .rate-input-wrap .input-group-modern {
        width: 160px;
    }

    /* Document rows */
    .document-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 16px;
        align-items: end;
        padding: 16px;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
        margin-bottom: 12px;
        background: var(--gray-50);
    }

    @media (max-width: 768px) {
        .document-row {
            grid-template-columns: 1fr;
        }
    }

    /* Service commission rows */
    .commission-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 16px;
        align-items: end;
        padding: 12px 16px;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
        margin-bottom: 10px;
        background: #fff;
    }

    @media (max-width: 640px) {
        .commission-row {
            grid-template-columns: 1fr;
        }
    }

    /* Table */
    .table-modern {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .table-modern thead th {
        text-align: left;
        padding: 12px 16px;
        background: var(--gray-50);
        color: var(--gray-500);
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--gray-200);
    }

    .table-modern tbody td {
        padding: 14px 16px;
        color: var(--gray-700);
        border-bottom: 1px solid var(--gray-100);
        vertical-align: middle;
    }

    .table-modern tbody tr:hover {
        background: var(--gray-50);
    }

    .table-modern .action-icon {
        color: var(--gray-400);
        font-size: 16px;
        margin-right: 8px;
        transition: color 0.2s;
    }

    .table-modern .action-icon:hover {
        color: var(--primary-color);
    }

    .table-modern .action-icon.danger:hover {
        color: var(--danger-color);
    }

    /* Status dot */
    .status-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .status-dot.green {
        background: var(--success-color);
    }

    .status-dot.red {
        background: var(--danger-color);
    }

    /* Help Card */
    .help-card {
        background: linear-gradient(135deg, #F0FDF4 0%, #ECFDF5 100%);
        border: 1px solid #A7F3D0;
        border-radius: var(--radius-lg);
        padding: 24px;
        position: sticky;
        top: 24px;
    }

    .help-card .help-icon {
        width: 48px;
        height: 48px;
        background: var(--primary-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 20px;
        margin-bottom: 16px;
    }

    .help-card h5 {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0 0 12px 0;
    }

    .help-card p {
        font-size: 14px;
        color: var(--gray-600);
        line-height: 1.6;
        margin: 0;
    }

    .full-width {
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .full-width {
            grid-column: 1;
        }
    }

    /* SumoSelect overrides */
    .SumoSelect>.CaptionCont {
        border: 1.5px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        padding: 10px 14px !important;
        background: #fff !important;
        font-size: 14px !important;
        font-family: inherit !important;
        color: var(--gray-800) !important;
        min-height: 44px;
        box-shadow: none !important;
    }

    .SumoSelect>.CaptionCont>span.placeholder {
        color: var(--gray-400) !important;
        font-style: normal !important;
    }

    .SumoSelect:focus>.CaptionCont,
    .SumoSelect.open>.CaptionCont {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1) !important;
    }

    .SumoSelect>.optWrapper {
        border: 1.5px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        margin-top: 4px !important;
        box-shadow: var(--shadow-md) !important;
        font-family: inherit !important;
    }

    .SumoSelect>.optWrapper>.options>li {
        font-size: 14px !important;
        padding: 10px 14px !important;
        color: var(--gray-700) !important;
    }

    .SumoSelect>.optWrapper>.options>li.selected {
        background: #F0FDF4 !important;
        color: var(--primary-dark) !important;
    }

    /* Modal */
    .modal-modern {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(17, 24, 39, 0.5);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-modern.show {
        display: flex;
    }

    .modal-modern .modal-content-modern {
        background: #fff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        width: 100%;
        max-width: 560px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 28px;
    }

    .modal-modern .modal-header-modern {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-modern .modal-header-modern h4 {
        font-size: 18px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
    }

    .modal-modern .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        color: var(--gray-400);
        cursor: pointer;
        line-height: 1;
        padding: 0;
    }

    .modal-modern .modal-close:hover {
        color: var(--gray-700);
    }

    /* Div inactive warning */
    .div_inactive {
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        background: #FEE2E2;
        border: 1px solid #FCA5A5;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">

                <!-- Main Grid -->
                <div class="main-grid">
                    <!-- Main Content -->
                    <div class="card-modern">
                        <div class="card-header">
                            <h5>
                                <i class="fas fa-user-tie"></i>
                                <?= !empty($_GET['id']) ? htmlspecialchars($FIRST_NAME . ' ' . $LAST_NAME) : 'New Service Provider' ?>
                            </h5>
                            <?php if (!empty($_GET['id'])): ?>
                                <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                    <i class="fas fa-circle"></i>
                                    <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Tabs -->
                        <ul class="nav tabs-modern" role="tablist">
                            <li>
                                <a class="tab-link active" data-bs-toggle="tab" id="profile_tab_link" href="#profile" role="tab">
                                    <i class="fas fa-id-badge"></i> Profile
                                </a>
                            </li>
                            <li id="login_info_tab" style="display: <?= ($CREATE_LOGIN == 1) ? '' : 'none' ?>">
                                <a class="tab-link" id="login_info_tab_link" onclick="goToLoginTab()" data-bs-toggle="tab" href="#login" role="tab">
                                    <i class="fas fa-lock"></i> Login Info
                                </a>
                            </li>
                            <li>
                                <a class="tab-link" id="rates_tab_link" data-bs-toggle="tab" href="#rates" role="tab">
                                    <i class="fas fa-dollar-sign"></i> Rates
                                </a>
                            </li>
                            <li>
                                <a class="tab-link" id="service_tab_link" onclick="getLocationHours()" data-bs-toggle="tab" href="#service" role="tab">
                                    <i class="fas fa-server"></i> Service
                                </a>
                            </li>
                            <li>
                                <a class="tab-link" data-bs-toggle="tab" href="#documents" id="document_tab_link" role="tab">
                                    <i class="fas fa-file-alt"></i> Documents
                                </a>
                            </li>
                            <li>
                                <a class="tab-link" id="comment_tab_link" data-bs-toggle="tab" href="#comments" role="tab">
                                    <i class="fas fa-comment"></i> Comments
                                </a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">

                            <!-- ================= PROFILE TAB ================= -->
                            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                <div class="card-body">
                                    <form class="form-material form-horizontal" id="profile_form">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveProfileData">
                                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                        <input type="hidden" class="TYPE" name="TYPE" value="1">

                                        <!-- Section: Personal Information -->
                                        <div class="section-header">
                                            <i class="fas fa-user"></i>
                                            <span>Personal Information</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">First Name <span class="required">*</span></label>
                                                <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control-modern" placeholder="Enter First Name" required value="<?= htmlspecialchars($FIRST_NAME) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control-modern" placeholder="Enter Last Name" value="<?= htmlspecialchars($LAST_NAME) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Phone <span class="required">*</span></label>
                                                <input type="text" id="PHONE" name="PHONE" class="form-control-modern" placeholder="Enter Phone Number" value="<?php echo htmlspecialchars($PHONE) ?>" required>
                                                <div id="phone_result"></div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Email <span class="required">*</span></label>
                                                <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control-modern" placeholder="Enter Email Address" value="<?= htmlspecialchars($EMAIL_ID) ?>" required>
                                                <div id="email_result"></div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Gender</label>
                                                <select class="form-control-modern" id="GENDER" name="GENDER">
                                                    <option>Select Gender</option>
                                                    <option value="Male" <?php if ($GENDER == "Male") echo 'selected'; ?>>Male</option>
                                                    <option value="Female" <?php if ($GENDER == "Female") echo 'selected'; ?>>Female</option>
                                                    <option value="Other" <?php if ($GENDER == "Other") echo 'selected'; ?>>Other</option>
                                                </select>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Date of Birth</label>
                                                <input type="text" class="form-control-modern datepicker-past" id="DOB" name="DOB" value="<?= ($DOB) ? date('m/d/Y', strtotime($DOB)) : '' ?>">
                                            </div>

                                            <div class="form-group-modern full-width">
                                                <label class="form-label">Roles <span class="required">*</span></label>
                                                <div class="multiselect-box">
                                                    <select class="multi_sumo_select_roles" name="PK_ROLES[]" id="PK_ROLES" onchange="showServiceProviderTabs(this)" required multiple>
                                                        <?php
                                                        $row = $db->Execute("SELECT PK_ROLES, ROLES FROM DOA_ROLES WHERE ACTIVE='1' " . $user_role_condition . " ORDER BY SORT_ORDER");
                                                        while (!$row->EOF) {
                                                            $isSelected = in_array($row->fields['PK_ROLES'], $selected_roles) || $row->fields['PK_ROLES'] == 5;
                                                        ?>
                                                            <option value="<?php echo $row->fields['PK_ROLES']; ?>" <?= $isSelected ? "selected" : "" ?>>
                                                                <?= htmlspecialchars($row->fields['ROLES']) ?>
                                                            </option>
                                                        <?php $row->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Display Order</label>
                                                <input type="text" id="DISPLAY_ORDER" name="DISPLAY_ORDER" class="form-control-modern" placeholder="Enter Display Order" value="<?= htmlspecialchars($DISPLAY_ORDER) ?>">
                                            </div>
                                        </div>

                                        <!-- Section: Preferences -->
                                        <div class="section-header">
                                            <i class="fas fa-cog"></i>
                                            <span>Preferences</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Options</label>
                                                <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                                                    <label class="checkbox-item">
                                                        <input type="checkbox" id="CREATE_LOGIN" name="CREATE_LOGIN" <?= ($CREATE_LOGIN == 1) ? 'checked' : '' ?> onchange="createLogin(this);">
                                                        Create Login
                                                    </label>
                                                    <label class="checkbox-item">
                                                        <input type="checkbox" id="APPEAR_IN_CALENDAR" name="APPEAR_IN_CALENDAR" checked>
                                                        Appear In Calendar
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Section: Address -->
                                        <div class="section-header">
                                            <i class="fas fa-map-pin"></i>
                                            <span>Address</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Address</label>
                                                <input type="text" id="ADDRESS" name="ADDRESS" class="form-control-modern" placeholder="Enter Address" value="<?php echo htmlspecialchars($ADDRESS) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Apt/Ste</label>
                                                <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control-modern" placeholder="Enter Address" value="<?php echo htmlspecialchars($ADDRESS_1) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Country <span class="required">*</span></label>
                                                <select class="form-control-modern" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
                                                    <option>Select Country</option>
                                                    <?php
                                                    $row = $db->Execute("SELECT PK_COUNTRY, COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_COUNTRY']; ?>" <?= ($row->fields['PK_COUNTRY'] == $PK_COUNTRY) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['COUNTRY_NAME']) ?></option>
                                                    <?php $row->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">State <span class="required">*</span></label>
                                                <div id="State_div"></div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">City</label>
                                                <input type="text" id="CITY" name="CITY" class="form-control-modern" placeholder="Enter your city" value="<?php echo htmlspecialchars($CITY) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Postal / Zip Code</label>
                                                <input type="text" id="ZIP" name="ZIP" class="form-control-modern" placeholder="Enter Postal / Zip Code" value="<?php echo htmlspecialchars($ZIP) ?>">
                                            </div>

                                            <div class="form-group-modern full-width">
                                                <label class="form-label">Location <span class="required">*</span></label>
                                                <div class="multiselect-box">
                                                    <select class="multi_sumo_select_location" name="PK_USER_LOCATION[]" id="PK_LOCATION_MULTIPLE" multiple required>
                                                        <?php
                                                        $selected_location = [];
                                                        if (!empty($_GET['id'])) {
                                                            $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = '$_GET[id]'");
                                                            while (!$selected_location_row->EOF) {
                                                                $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                                                $selected_location_row->MoveNext();
                                                            }
                                                        }
                                                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= in_array($row->fields['PK_LOCATION'], $selected_location) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['LOCATION_NAME']) ?></option>
                                                        <?php $row->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Section: Notes & Photo -->
                                        <div class="section-header">
                                            <i class="fas fa-sticky-note"></i>
                                            <span>Notes &amp; Photo</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern full-width">
                                                <label class="form-label">Remarks</label>
                                                <textarea class="form-control-modern" rows="3" id="NOTES" name="NOTES"><?php echo htmlspecialchars($NOTES) ?></textarea>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Photo</label>
                                                <input type="file" name="USER_IMAGE" id="USER_IMAGE" class="form-control-modern">
                                                <?php if ($USER_IMAGE != '') { ?>
                                                    <a class="fancybox" href="<?php echo $USER_IMAGE; ?>" data-fancybox-group="gallery">
                                                        <img src="<?php echo $USER_IMAGE; ?>" class="image-preview" />
                                                    </a>
                                                <?php } ?>
                                            </div>
                                        </div>

                                        <!-- Section: Status -->
                                        <?php if (!empty($_GET['id'])) { ?>
                                            <div class="section-header">
                                                <i class="fas fa-toggle-on"></i>
                                                <span>Status</span>
                                            </div>

                                            <div class="form-group-modern <?= ($INACTIVE_BY_ADMIN == 1) ? 'div_inactive' : '' ?>">
                                                <label class="form-label">Active</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <? if ($ACTIVE == 1) echo 'checked="checked"'; ?>>
                                                        Yes
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <? if ($ACTIVE == 0) echo 'checked="checked"'; ?>>
                                                        No
                                                    </label>
                                                </div>
                                            </div>
                                        <? } ?>

                                        <!-- Form Actions -->
                                        <div class="form-actions">
                                            <button type="submit" class="btn-modern btn-modern-primary">
                                                <i class="fas fa-<?= empty($_GET['id']) ? 'arrow-right' : 'save' ?>"></i>
                                                <?= empty($_GET['id']) ? 'Continue' : 'Save' ?>
                                            </button>
                                            <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ================= LOGIN INFO TAB ================= -->
                            <div class="tab-pane fade" id="login" role="tabpanel">
                                <div class="card-body">
                                    <form class="form-material form-horizontal" id="login_form">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveLoginData">
                                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                        <input type="hidden" class="TYPE" name="TYPE" value="1">

                                        <div class="section-header">
                                            <i class="fas fa-key"></i>
                                            <span>Login Credentials</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern full-width">
                                                <label class="form-label">User Email <span class="required">*</span></label>
                                                <input type="text" id="EMAIL_ID" name="EMAIL_ID" class="form-control-modern" placeholder="Enter Email" value="<?= htmlspecialchars($EMAIL_ID) ?>" required readonly>
                                            </div>
                                        </div>

                                        <!-- Section: Permissions -->
                                        <div class="section-header">
                                            <i class="fas fa-shield-alt"></i>
                                            <span>Permissions</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Can Edit Enrollment?</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" name="CAN_EDIT_ENROLLMENT" id="CAN_EDIT_ENROLLMENT_YES" value="1" <? if ($CAN_EDIT_ENROLLMENT == 1) echo 'checked="checked"'; ?>>
                                                        Yes
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" name="CAN_EDIT_ENROLLMENT" id="CAN_EDIT_ENROLLMENT_NO" value="0" <? if ($CAN_EDIT_ENROLLMENT == 0) echo 'checked="checked"'; ?>>
                                                        No
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Support Tickets</label>
                                                <label class="checkbox-item">
                                                    <input type="checkbox" id="TICKET_SYSTEM_ACCESS" name="TICKET_SYSTEM_ACCESS" value="1" <? if ($TICKET_SYSTEM_ACCESS == 1) echo "checked"; ?>>
                                                    Can Create Support Tickets
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Section: Password -->
                                        <div class="section-header">
                                            <i class="fas fa-lock"></i>
                                            <span>Password</span>
                                        </div>

                                        <?php if (empty($_GET['id']) || $PASSWORD == '') { ?>
                                            <div class="form-grid">
                                                <div class="form-group-modern">
                                                    <label class="form-label">Password</label>
                                                    <input type="text" class="form-control-modern" placeholder="Password" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)">
                                                </div>
                                                <div class="form-group-modern">
                                                    <label class="form-label">Confirm Password</label>
                                                    <input type="text" class="form-control-modern" placeholder="Confirm Password" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)">
                                                </div>
                                                <div class="form-group-modern full-width">
                                                    <b id="password_error" style="color: var(--danger-color);"></b>
                                                    <div class="password-strength-bar">
                                                        <div class="bar-fill" id="password_bar" style="width: 0%;"></div>
                                                    </div>
                                                    <small id="password-text" class="password-strength-text"></small>
                                                </div>
                                            </div>
                                        <?php } else { ?>
                                            <div class="form-grid">
                                                <div class="form-group-modern full-width">
                                                    <div id="change_password_div" style="display: none;">
                                                        <div class="form-grid">
                                                            <div class="form-group-modern">
                                                                <label class="form-label">New Password</label>
                                                                <input type="password" name="PASSWORD" class="form-control-modern" id="PASSWORD">
                                                            </div>
                                                            <div class="form-group-modern">
                                                                <label class="form-label">Confirm New Password</label>
                                                                <input type="password" name="CONFIRM_PASSWORD" class="form-control-modern" id="CONFIRM_PASSWORD">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <b id="password_error" style="color: var(--danger-color);"></b>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Form Actions -->
                                        <div class="form-actions">
                                            <button type="submit" class="btn-modern btn-modern-primary">
                                                <i class="fas fa-<?= empty($_GET['id']) ? 'arrow-right' : 'save' ?>"></i>
                                                <?= empty($_GET['id']) ? 'Continue' : 'Save' ?>
                                            </button>
                                            <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ================= RATES TAB ================= -->
                            <div class="tab-pane fade" id="rates" role="tabpanel">
                                <div class="card-body">
                                    <form id="engagement_form">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveEngagementData">
                                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                        <input type="hidden" class="TYPE" name="TYPE" value="3">

                                        <!-- Section: Engagement Terms -->
                                        <div class="section-header" style="margin-top: 0;">
                                            <i class="fas fa-handshake"></i>
                                            <span>Engagement Terms</span>
                                        </div>

                                        <?php
                                        $i = 0;
                                        if (!empty($_GET['id'])) {
                                            $row = $db->Execute("SELECT DOA_RATE_TYPE.PK_RATE_TYPE, DOA_RATE_TYPE.RATE_NAME, DOA_RATE_TYPE.PRICE_TYPE, DOA_USER_RATE.RATE, DOA_USER_RATE.ACTIVE FROM DOA_RATE_TYPE LEFT JOIN $account_database.DOA_USER_RATE AS DOA_USER_RATE ON DOA_RATE_TYPE.PK_RATE_TYPE = DOA_USER_RATE.PK_RATE_TYPE WHERE DOA_RATE_TYPE.ACTIVE = 1 AND DOA_USER_RATE.PK_USER = '$_GET[id]' ORDER BY DOA_RATE_TYPE.PK_RATE_TYPE ASC");
                                            if ($row->RecordCount() > 0) {
                                                while (!$row->EOF) { ?>
                                                    <div class="rate-row">
                                                        <label class="rate-name" for="rate_<?= $row->fields['PK_RATE_TYPE'] ?>"><?= htmlspecialchars($row->fields['RATE_NAME']) ?></label>
                                                        <div>
                                                            <input type="hidden" name="PK_RATE_TYPE[]" value="<?= $row->fields['PK_RATE_TYPE'] ?>">
                                                            <input type="checkbox" class="engagement_terms" name="PK_RATE_TYPE_ACTIVE[<?= $i ?>]" id="<?= $row->fields['PK_RATE_TYPE'] ?>" value="1" <?= (is_null($row->fields['ACTIVE']) || $row->fields['ACTIVE'] == 0) ? '' : 'checked' ?>>
                                                        </div>
                                                        <div class="rate-input-wrap">
                                                            <div class="input-group-modern">
                                                                <?php if ($row->fields['PRICE_TYPE'] == 1) { ?>
                                                                    <span class="input-prefix"><?= $currency ?></span>
                                                                <?php } else { ?>
                                                                    <span class="input-prefix">%</span>
                                                                <?php } ?>
                                                                <input type="text" class="form-control-modern" oninput="setFormat(this)" name="RATE[]" value="<?= htmlspecialchars($row->fields['RATE']) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                                    $i++;
                                                    $row->MoveNext();
                                                }
                                            } else {
                                                $row = $db->Execute("SELECT * FROM DOA_RATE_TYPE WHERE ACTIVE = 1 ORDER BY PK_RATE_TYPE ASC");
                                                while (!$row->EOF) { ?>
                                                    <div class="rate-row">
                                                        <label class="rate-name" for="rate_<?= $row->fields['PK_RATE_TYPE'] ?>"><?= htmlspecialchars($row->fields['RATE_NAME']) ?></label>
                                                        <div>
                                                            <input type="hidden" name="PK_RATE_TYPE[]" value="<?= $row->fields['PK_RATE_TYPE'] ?>">
                                                            <input type="checkbox" class="engagement_terms" name="PK_RATE_TYPE_ACTIVE[<?= $i ?>]" id="<?= $row->fields['PK_RATE_TYPE'] ?>" value="1">
                                                        </div>
                                                        <div class="rate-input-wrap">
                                                            <div class="input-group-modern">
                                                                <?php if ($row->fields['PRICE_TYPE'] == 1) { ?>
                                                                    <span class="input-prefix"><?= $currency ?></span>
                                                                <?php } else { ?>
                                                                    <span class="input-prefix">%</span>
                                                                <?php } ?>
                                                                <input type="text" class="form-control-modern" name="RATE[]">
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                                    $i++;
                                                    $row->MoveNext();
                                                }
                                            }
                                        } else {
                                            $row = $db->Execute("SELECT * FROM DOA_RATE_TYPE WHERE ACTIVE = 1 ORDER BY PK_RATE_TYPE ASC");
                                            while (!$row->EOF) { ?>
                                                <div class="rate-row">
                                                    <label class="rate-name" for="rate_<?= $row->fields['PK_RATE_TYPE'] ?>"><?= htmlspecialchars($row->fields['RATE_NAME']) ?></label>
                                                    <div>
                                                        <input type="hidden" name="PK_RATE_TYPE[]" value="<?= $row->fields['PK_RATE_TYPE'] ?>">
                                                        <input type="checkbox" class="engagement_terms" name="PK_RATE_TYPE_ACTIVE[<?= $i ?>]" id="<?= $row->fields['PK_RATE_TYPE'] ?>" value="1">
                                                    </div>
                                                    <div class="rate-input-wrap">
                                                        <div class="input-group-modern">
                                                            <?php if ($row->fields['PRICE_TYPE'] == 1) { ?>
                                                                <span class="input-prefix"><?= $currency ?></span>
                                                            <?php } else { ?>
                                                                <span class="input-prefix">%</span>
                                                            <?php } ?>
                                                            <input type="text" class="form-control-modern" name="RATE[]">
                                                        </div>
                                                    </div>
                                                </div>
                                        <?php
                                                $i++;
                                                $row->MoveNext();
                                            }
                                        } ?>

                                        <!-- Section: Service Based Terms -->
                                        <div class="section-header">
                                            <i class="fas fa-concierge-bell"></i>
                                            <span>Service Based Terms</span>
                                            <a href="javascript:;" class="btn-modern btn-modern-secondary" style="margin-left: auto; padding: 6px 16px; font-size: 13px;" onclick="addMoreCodeCommission()">
                                                <i class="fas fa-plus"></i> New
                                            </a>
                                        </div>

                                        <?php
                                        if (!empty($_GET['id'])) {
                                            $code_commission_data = $db_account->Execute("SELECT * FROM DOA_SERVICE_COMMISSION WHERE PK_USER = " . $_GET['id']);
                                            while (!$code_commission_data->EOF) { ?>
                                                <div class="commission-row">
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Service</label>
                                                        <select class="form-control-modern" name="PK_SERVICE_MASTER[]">
                                                            <option value="">Select Service</option>
                                                            <?php
                                                            $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE ACTIVE = 1 AND IS_DELETED = 0");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>" <?= ($code_commission_data->fields['PK_SERVICE_MASTER'] == $row->fields['PK_SERVICE_MASTER']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['SERVICE_NAME']) ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Amount</label>
                                                        <input type="text" class="form-control-modern" name="COMMISSION_AMOUNT[]" value="<?= htmlspecialchars($code_commission_data->fields['COMMISSION_AMOUNT']) ?>">
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn-modern btn-modern-danger" onclick="removeThis(this);">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php $code_commission_data->MoveNext();
                                            } ?>
                                        <?php } else { ?>
                                            <div class="commission-row">
                                                <div class="form-group-modern">
                                                    <label class="form-label">Service</label>
                                                    <select class="form-control-modern" name="PK_SERVICE_MASTER[]">
                                                        <option value="">Select Service</option>
                                                        <?php
                                                        $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE ACTIVE = 1 AND IS_DELETED = 0");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= htmlspecialchars($row->fields['SERVICE_NAME']) ?></option>
                                                        <?php $row->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                                <div class="form-group-modern">
                                                    <label class="form-label">Amount</label>
                                                    <input type="text" class="form-control-modern" name="COMMISSION_AMOUNT[]">
                                                </div>
                                                <div>
                                                    <button type="button" class="btn-modern btn-modern-danger" onclick="removeThis(this);">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <div id="add_more_code_commission"></div>

                                        <!-- Form Actions -->
                                        <div class="form-actions">
                                            <button type="submit" class="btn-modern btn-modern-primary">
                                                <i class="fas fa-<?= empty($_GET['id']) ? 'arrow-right' : 'save' ?>"></i>
                                                <?= empty($_GET['id']) ? 'Continue' : 'Save' ?>
                                            </button>
                                            <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ================= SERVICE TAB ================= -->
                            <div class="tab-pane fade" id="service" role="tabpanel">
                                <div class="card-body">
                                    <form id="location_hour_form">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveLocationHourData">
                                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                        <input type="hidden" class="TYPE" name="TYPE" value="3">

                                        <div class="section-header" style="margin-top: 0;">
                                            <i class="fas fa-clock"></i>
                                            <span>Location Hours</span>
                                        </div>

                                        <div id="location_details_div">
                                            <!-- populated via AJAX -->
                                        </div>

                                        <div class="form-actions">
                                            <button type="submit" class="btn-modern btn-modern-primary">
                                                <i class="fas fa-<?= empty($_GET['id']) ? 'arrow-right' : 'save' ?>"></i>
                                                <?= empty($_GET['id']) ? 'Continue' : 'Save' ?>
                                            </button>
                                            <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ================= DOCUMENTS TAB ================= -->
                            <div class="tab-pane fade" id="documents" role="tabpanel">
                                <div class="card-body">
                                    <form id="document_form">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveUserDocumentData">
                                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                        <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?= $PK_CUSTOMER_DETAILS ?>">
                                        <input type="hidden" class="TYPE" name="TYPE" value="2">

                                        <div class="section-header" style="margin-top: 0;">
                                            <i class="fas fa-file-alt"></i>
                                            <span>Documents</span>
                                        </div>

                                        <div id="append_user_document">
                                            <?php
                                            if (!empty($_GET['id'])) {
                                                $user_doc_count = 0;
                                                $row = $db_account->Execute("SELECT * FROM DOA_USER_DOCUMENT WHERE PK_USER = '$PK_USER'");
                                                while (!$row->EOF) { ?>
                                                    <div class="document-row">
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Document Name</label>
                                                            <input type="text" name="DOCUMENT_NAME[]" class="form-control-modern" placeholder="Enter Document Name" value="<?= htmlspecialchars($row->fields['DOCUMENT_NAME']) ?>">
                                                        </div>
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Document File</label>
                                                            <input type="file" name="FILE_PATH[]" class="form-control-modern">
                                                            <a target="_blank" href="<?= $row->fields['FILE_PATH'] ?>" style="font-size: 12px; color: var(--primary-color); margin-top: 4px;">
                                                                <i class="fas fa-external-link-alt"></i> View current file
                                                            </a>
                                                            <input type="hidden" name="FILE_PATH_URL[]" value="<?= $row->fields['FILE_PATH'] ?>">
                                                        </div>
                                                        <div>
                                                            <button type="button" class="btn-modern btn-modern-danger" onclick="removeUserDocument(this);">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php $row->MoveNext();
                                                    $user_doc_count++;
                                                } ?>
                                            <?php } else {
                                                $user_doc_count = 1; ?>
                                                <div class="document-row">
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Document Name</label>
                                                        <input type="text" name="DOCUMENT_NAME[]" class="form-control-modern" placeholder="Enter Document Name">
                                                    </div>
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Document File</label>
                                                        <input type="file" name="FILE_PATH[]" class="form-control-modern">
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn-modern btn-modern-danger" onclick="removeUserDocument(this);">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>

                                        <div style="text-align: right; margin-top: 12px;">
                                            <button type="button" class="btn-modern btn-modern-secondary" onclick="addMoreUserDocument();">
                                                <i class="fas fa-plus"></i> New
                                            </button>
                                        </div>

                                        <div class="form-actions">
                                            <button type="submit" class="btn-modern btn-modern-primary">
                                                <i class="fas fa-<?= empty($_GET['id']) ? 'arrow-right' : 'save' ?>"></i>
                                                <?= empty($_GET['id']) ? 'Continue' : 'Save' ?>
                                            </button>
                                            <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ================= COMMENTS TAB ================= -->
                            <div class="tab-pane fade" id="comments" role="tabpanel">
                                <div class="card-body">
                                    <div class="section-header" style="margin-top: 0;">
                                        <i class="fas fa-comment"></i>
                                        <span>Comments</span>
                                        <button type="button" class="btn-modern btn-modern-primary" style="margin-left: auto; padding: 8px 20px; font-size: 13px;" onclick="createUserComment();">
                                            <i class="fas fa-plus"></i> Create New
                                        </button>
                                    </div>

                                    <div style="overflow-x: auto;">
                                        <table class="table-modern" id="myTable">
                                            <thead>
                                                <tr>
                                                    <th>Commented Date</th>
                                                    <th>Commented User</th>
                                                    <th>Comment</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $comment_data = $db_account->Execute("SELECT DOA_COMMENT.PK_COMMENT, DOA_COMMENT.COMMENT, DOA_COMMENT.COMMENT_DATE, DOA_COMMENT.ACTIVE, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS FULL_NAME FROM `DOA_COMMENT` INNER JOIN DOA_USERS ON DOA_COMMENT.BY_PK_USER = DOA_USERS.PK_USER WHERE `FOR_PK_USER` = " . $PK_USER);
                                                $i = 1;
                                                while (!$comment_data->EOF) { ?>
                                                    <tr>
                                                        <td onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><?= date('m/d/Y', strtotime($comment_data->fields['COMMENT_DATE'])) ?></td>
                                                        <td onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><?= htmlspecialchars($comment_data->fields['FULL_NAME']) ?></td>
                                                        <td onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><?= htmlspecialchars($comment_data->fields['COMMENT']) ?></td>
                                                        <td>
                                                            <a href="javascript:;" class="action-icon" onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><i class="fas fa-pen"></i></a>
                                                            <a href="javascript:;" class="action-icon danger" onclick="deleteComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><i class="fas fa-trash"></i></a>
                                                            <?php if ($comment_data->fields['ACTIVE'] == 1) { ?>
                                                                <span class="status-dot green"></span>
                                                            <?php } else { ?>
                                                                <span class="status-dot red"></span>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                <?php $comment_data->MoveNext();
                                                    $i++;
                                                } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Help Sidebar -->
                    <div>
                        <div class="help-card">
                            <div class="help-icon">
                                <i class="fas fa-question"></i>
                            </div>
                            <h5><?= htmlspecialchars($help_title) ?></h5>
                            <p><?= htmlspecialchars($help_description) ?></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Comment Modal -->
    <div id="commentModel" class="modal-modern">
        <div class="modal-content-modern">
            <div class="modal-header-modern">
                <h4 id="comment_header">Add Comment</h4>
                <button type="button" class="modal-close close_comment_model">&times;</button>
            </div>
            <form id="comment_add_edit_form" role="form" action="" method="post">
                <input type="hidden" name="FUNCTION_NAME" value="saveCommentData">
                <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                <input type="hidden" name="PK_COMMENT" id="PK_COMMENT" value="0">

                <div class="form-group-modern" style="margin-bottom: 16px;">
                    <label class="form-label">Comments</label>
                    <textarea class="form-control-modern" rows="6" name="COMMENT" id="COMMENT" required></textarea>
                </div>

                <div class="form-group-modern" style="margin-bottom: 16px;">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control-modern" name="COMMENT_DATE" id="COMMENT_DATE" required>
                </div>

                <div class="form-group-modern" id="comment_active" style="display: none; margin-bottom: 16px;">
                    <label class="form-label">Active</label>
                    <div class="radio-group-modern">
                        <label class="radio-item">
                            <input type="radio" id="COMMENT_ACTIVE_1" name="ACTIVE" value="1"> Yes
                        </label>
                        <label class="radio-item">
                            <input type="radio" id="COMMENT_ACTIVE_0" name="ACTIVE" value="0"> No
                        </label>
                    </div>
                </div>

                <div class="form-actions" style="margin-top: 20px;">
                    <button type="submit" class="btn-modern btn-modern-primary">
                        <i class="fas fa-save"></i> Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>
    <script>
        $('.datepicker-past').datepicker({
            format: 'mm/dd/yyyy',
            maxDate: 0,
            changeMonth: true,
            changeYear: true,
            yearRange: '1900:' + new Date().getFullYear(),
        });

        $('#NAME').SumoSelect({
            placeholder: 'Select User',
            search: true,
            searchText: 'Search...'
        });
        $('.multi_sumo_select_location').SumoSelect({
            placeholder: 'Select Location',
            selectAll: true
        });
        $('.multi_sumo_select_roles').SumoSelect({
            placeholder: 'Select Roles',
            selectAll: true
        });
        $('.multi_sumo_select_services').SumoSelect({
            placeholder: 'Select Services',
            selectAll: true
        });

        function editpage(param) {
            var id = $(param).val();
            window.location.href = "user.php?id=" + id;
        }

        $(document).ready(function() {
            fetch_state(<?php echo $PK_COUNTRY; ?>);
        });

        function fetch_state(PK_COUNTRY) {
            let data = "PK_COUNTRY=" + PK_COUNTRY + "&PK_STATES=<?= $PK_STATES; ?>";
            $.ajax({
                url: "ajax/state.php",
                type: "POST",
                data: data,
                async: false,
                cache: false,
                success: function(result) {
                    document.getElementById('State_div').innerHTML = result;
                }
            });
        }

        function setFormat(param) {
            if ($(param).val() != "") {
                $(param).val(parseFloat($(param).val().replace(/,/g, ""))
                    .toString()
                    .replace(/\B(?=(\d{3})+(?!\d))/g, ","));
            }
        }

        $(document).on('focus', '.time-picker', function() {
            let minTime = $(this).closest('.form-group').find('.minTime').val();
            let maxTime = $(this).closest('.form-group').find('.maxTime').val();
            $(this).timepicker({
                timeFormat: 'hh:mm p',
                interval: 30,
                dynamic: false,
                dropdown: true,
                scrollbar: true,
                minTime: minTime,
                maxTime: maxTime
            });
        });

        function closeThisDay(param) {
            if ($(param).is(':checked')) {
                $(param).closest('.row').find('.time-input').val('');
                $(param).closest('.row').find('.time-input').css('pointer-events', 'none');
            } else {
                $(param).closest('.row').find('.time-input').css('pointer-events', '');
            }
        }
    </script>
    <script>
        let PK_USER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);
        let NEW_USER = parseInt(<?= empty($_GET['id']) ? true : false ?>);

        function isGood(password) {
            let password_strength = document.getElementById("password-text");
            let password_bar = document.getElementById("password_bar");

            if (password.length == 0) {
                password_strength.innerHTML = "";
                if (password_bar) password_bar.style.width = '0%';
                return;
            }
            let regex = new Array();
            regex.push("[A-Z]");
            regex.push("[a-z]");
            regex.push("[0-9]");
            regex.push("[$@$!%*#?&]");
            let passed = 0;
            for (let i = 0; i < regex.length; i++) {
                if (new RegExp(regex[i]).test(password)) {
                    passed++;
                }
            }
            let strength = "";
            let width = "0%";
            let color = "var(--danger-color)";
            switch (passed) {
                case 0:
                case 1:
                case 2:
                    strength = "<small style='color: var(--danger-color);'>Weak</small>";
                    width = "50%";
                    color = "var(--danger-color)";
                    break;
                case 3:
                    strength = "<small style='color: var(--warning-color);'>Medium</small>";
                    width = "60%";
                    color = "var(--warning-color)";
                    break;
                case 4:
                    strength = "<small style='color: var(--success-color);'>Strong</small>";
                    width = "100%";
                    color = "var(--success-color)";
                    break;
            }
            password_strength.innerHTML = strength;
            if (password_bar) {
                password_bar.style.width = width;
                password_bar.style.background = color;
            }
        }

        $(document).on('click', '#cancel_button', function() {
            window.location.href = 'all_users.php';
        });

        function createLogin(param) {
            if ($(param).is(':checked')) {
                $('#login_info_tab').show();
                $('#PHONE').prop('required', true);
                $('#EMAIL_ID').prop('required', true);
                $('#submit_button').hide();
                $('#next_button_interest').hide();
                $('#next_button').show();
            } else {
                $('#login_info_tab').hide();
                $('#PHONE').prop('required', false);
                $('#EMAIL_ID').prop('required', false);
                $('#submit_button').show();
                $('#next_button_interest').show();
                $('#next_button').hide();
            }
        }

        function showServiceProviderTabs(param) {
            const selectedRoles = Array.from(param.selectedOptions).map(opt => opt.value);

            if (!selectedRoles.includes('5')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Required Role Missing',
                    html: 'First must be a <b>Service Provider</b> <i class="fas fa-exclamation-circle text-danger ms-1"></i>',
                    confirmButtonColor: '#3085d6',
                }).then(() => {
                    $(param).val('5');
                    $(param)[0].sumo.reload();
                    $(param).css('border-color', '#dc3545');
                    setTimeout(() => $(param).css('border-color', ''), 2000);
                });
            }
        }

        let counter = parseInt(<?= $user_doc_count ?>);

        function addMoreUserDocument() {
            $('#append_user_document').append(`<div class="document-row">
                                                <div class="form-group-modern">
                                                    <label class="form-label">Document Name</label>
                                                    <input type="text" name="DOCUMENT_NAME[]" class="form-control-modern" placeholder="Enter Document Name">
                                                </div>
                                                <div class="form-group-modern">
                                                    <label class="form-label">Document File</label>
                                                    <input type="file" name="FILE_PATH[]" class="form-control-modern">
                                                </div>
                                                <div>
                                                    <button type="button" class="btn-modern btn-modern-danger" onclick="removeUserDocument(this);">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                              </div>`);
            counter++;
        }

        function removeUserDocument(param) {
            $(param).closest('.document-row').remove();
            counter--;
        }

        $(document).on('submit', '#profile_form', function(event) {
            event.preventDefault();
            let PK_USER = $('.PK_USER').val();
            const PHONE = $('#PHONE').val().trim();
            const EMAIL_ID = $('#EMAIL_ID').val().trim();
            if (PHONE != '') {
                $.ajax({
                    url: 'ajax/username_checker.php',
                    type: 'post',
                    data: {
                        PHONE: PHONE
                    },
                    success: function(response) {
                        if (response && PK_USER == 0) {
                            $('#phone_result').html(response);
                        } else {
                            $('#phone_result').html('');
                            if (EMAIL_ID != '') {
                                $.ajax({
                                    url: 'ajax/username_checker.php',
                                    type: 'post',
                                    data: {
                                        EMAIL_ID: EMAIL_ID
                                    },
                                    success: function(response) {
                                        if (response && PK_USER == 0) {
                                            $('#email_result').html(response);
                                        } else {
                                            $('#email_result').html('');
                                            let form_data = new FormData($('#profile_form')[0]);
                                            $.ajax({
                                                url: "ajax/AjaxFunctions.php",
                                                type: 'POST',
                                                data: form_data,
                                                processData: false,
                                                contentType: false,
                                                dataType: 'JSON',
                                                success: function(data) {
                                                    $('.PK_USER').val(data.PK_USER);
                                                    $('.PK_CUSTOMER_DETAILS').val(data.PK_CUSTOMER_DETAILS);
                                                    if (PK_USER == 0 || NEW_USER) {
                                                        if ($('#CREATE_LOGIN').is(':checked')) {
                                                            $('#login_info_tab_link')[0].click();
                                                        } else {
                                                            if ($('#PK_ROLES').val().indexOf('5') !== -1) {
                                                                $('#rates_tab_link')[0].click();
                                                            } else {
                                                                $('#document_tab_link')[0].click();
                                                            }
                                                        }
                                                    } else {
                                                        window.location.href = 'all_users.php';
                                                    }
                                                }
                                            });
                                        }
                                    }
                                });
                            }
                        }
                    }
                });
            }
        });

        const phone = document.getElementById("PHONE");
        if (phone.value !== "") {
            phone.setAttribute("readonly", "readonly");
        }

        const email_id = document.getElementById("EMAIL_ID");
        if (email_id.value !== "") {
            email_id.setAttribute("readonly", "readonly");
        }

        function goToLoginTab() {
            let PK_USER = $('.PK_USER').val();
            if (!PK_USER) {
                alert('Please fill up the profile and click next.');
                $('#profile_tab_link')[0].click();
            }
        }

        $(document).on('submit', '#login_form', function(event) {
            event.preventDefault();
            let PK_USER = $('.PK_USER').val();
            let PASSWORD = $('#PASSWORD').val();
            let CONFIRM_PASSWORD = $('#CONFIRM_PASSWORD').val();
            if (PASSWORD === CONFIRM_PASSWORD) {
                let form_data = $('#login_form').serialize();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: form_data,
                    success: function(data) {
                        if (PK_USER == 0 || NEW_USER) {
                            if ($('#PK_ROLES').val().indexOf('5') !== -1) {
                                $('#rates_tab_link')[0].click();
                            } else {
                                $('#document_tab_link')[0].click();
                            }
                        } else {
                            window.location.href = 'all_users.php';
                        }
                    }
                });
            } else {
                $('#password_error').text('Password and Confirm Password not matched');
            }
        });

        function addMoreCodeCommission() {
            $('#add_more_code_commission').append(`<div class="commission-row">
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Service</label>
                                                        <select class="form-control-modern" name="PK_SERVICE_MASTER[]">
                                                            <option value="">Select Service</option>
                                                            <?php
                                                            $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE ACTIVE = 1 AND IS_DELETED = 0");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= htmlspecialchars($row->fields['SERVICE_NAME']) ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Amount</label>
                                                        <input type="text" class="form-control-modern" name="COMMISSION_AMOUNT[]">
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn-modern btn-modern-danger" onclick="removeThis(this);">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>`);
        }

        function removeThis(param) {
            $(param).closest('.commission-row').remove();
        }

        $(document).on('submit', '#engagement_form', function(event) {
            event.preventDefault();
            let PK_USER = $('.PK_USER').val();
            let form_data = $('#engagement_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success: function(data) {
                    if (PK_USER == 0 || NEW_USER) {
                        if ($('#PK_ROLES').val().indexOf('5') !== -1) {
                            $('#service_tab_link')[0].click();
                        } else {
                            $('#document_tab_link')[0].click();
                        }
                    } else {
                        window.location.href = 'all_users.php';
                    }
                }
            });
        });

        function getLocationHours() {
            let PK_USER = $('.PK_USER').val();
            $.ajax({
                url: "ajax/get_location_hours.php",
                type: 'POST',
                data: {
                    PK_USER: PK_USER
                },
                success: function(data) {
                    $('#location_details_div').html(data);
                }
            });
        }

        $(document).on('submit', '#location_hour_form', function(event) {
            event.preventDefault();
            let form_data = $('#location_hour_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success: function(data) {
                    window.location.href = 'all_users.php';
                }
            });
        });

        $(document).on('submit', '#document_form', function(event) {
            event.preventDefault();
            let form_data = new FormData($('#document_form')[0]);
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                success: function(data) {
                    window.location.href = 'all_users.php';
                }
            });
        });
    </script>

    <script>
        let comment_model = document.getElementById("commentModel");
        let comment_span = document.getElementsByClassName("close_comment_model")[0];

        function openCommentModel() {
            comment_model.classList.add('show');
        }
        comment_span.onclick = function() {
            comment_model.classList.remove('show');
        }
        window.onclick = function(event) {
            if (event.target == comment_model) {
                comment_model.classList.remove('show');
            }
        }

        function createUserComment() {
            $('#comment_header').text("Add Comment");
            $('#PK_COMMENT').val(0);
            $('#COMMENT').val('');
            $('#COMMENT_DATE').val('');
            $('#comment_active').hide();
            openCommentModel();
        }

        function editComment(PK_COMMENT) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                dataType: 'JSON',
                data: {
                    FUNCTION_NAME: 'getEditCommentData',
                    PK_COMMENT: PK_COMMENT
                },
                success: function(data) {
                    $('#comment_header').text("Edit Comment");
                    $('#PK_COMMENT').val(data.fields.PK_COMMENT);
                    $('#COMMENT').val(data.fields.COMMENT);
                    $('#COMMENT_DATE').val(data.fields.COMMENT_DATE);
                    $('#COMMENT_ACTIVE_' + data.fields.ACTIVE).prop('checked', true);
                    $('#comment_active').show();
                    openCommentModel();
                }
            });
        }

        $(document).on('submit', '#comment_add_edit_form', function(event) {
            event.preventDefault();
            let form_data = new FormData($('#comment_add_edit_form')[0]);
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                success: function(data) {
                    window.location.href = `user.php?id=${PK_USER}&on_tab=comments`;
                }
            });
        });

        function deleteComment(PK_COMMENT) {
            let conf = confirm("Are you sure you want to delete?");
            if (conf) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deleteCommentData',
                        PK_COMMENT: PK_COMMENT
                    },
                    success: function(data) {
                        window.location.href = `user.php?id=${PK_USER}&on_tab=comments`;
                    }
                });
            }
        }
    </script>

    <script>
        $(document).ready(function() {
            $('#USER_NAME').on('blur', function() {
                const USER_NAME = $(this).val().trim();
                if (USER_NAME != '') {
                    $.ajax({
                        url: 'ajax/username_checker.php',
                        type: 'post',
                        data: {
                            USER_NAME: USER_NAME
                        },
                        success: function(response) {
                            $('#uname_result').html(response);
                        }
                    });
                } else {
                    $("#uname_result").html("");
                }
            });
        });

        $(document).on('change', '.engagement_terms', function() {
            if ($(this).attr('id') == 4 && $(this).is(':checked')) {
                $('#5').prop('checked', false);
            }
            if ($(this).attr('id') == 5 && $(this).is(':checked')) {
                $('#4').prop('checked', false);
            }
        });

        document.getElementById('PK_ROLES').addEventListener('change', function() {
            this.style.border = '';
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#APPEAR_IN_CALENDAR').change(function() {
                if (!this.checked) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Required Setting',
                        html: '<b>Appear In Calendar</b> must remain checked <i class="fas fa-exclamation-circle text-danger ms-1"></i>',
                        confirmButtonColor: '#3085d6',
                    });
                    this.checked = true;
                    $(this).css('border-color', 'red');
                    setTimeout(() => $(this).css('border-color', ''), 1000);
                }
            });
        });
    </script>
</body>

</html>