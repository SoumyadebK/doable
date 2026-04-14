<?php
require_once('../global/config.php');
require_once("../global/stripe-php-master/init.php");
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$user_role_condition = " AND PK_ROLES = 4";
if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

$PK_USER = $_GET['id'] ?? '';
$PK_USER_MASTER = $_GET['master_id'] ?? '';

$USER_NAME = '';
$FIRST_NAME = $_GET['FIRST_NAME'] ?? '';
$LAST_NAME = $_GET['LAST_NAME'] ?? '';
$CUSTOMER_ID = '';
$UNIQUE_ID = '';
$EMAIL_ID = $_GET['EMAIL_ID'] ?? '';
$USER_IMAGE = '';
$GENDER = '';
$DOB = '';
$ADDRESS = '';
$ADDRESS_1 = '';
$PK_COUNTRY = '';
$PK_STATES = '';
$CITY = '';
$ZIP = '';
$PHONE = $_GET['PHONE'] ?? '';
$NOTES = '';
$PASSWORD = '';
$ACTIVE = '';
$WHAT_PROMPTED_YOU_TO_INQUIRE = '';
$PK_SKILL_LEVEL = '';
$PK_INQUIRY_METHOD = '';
$INQUIRY_TAKER_ID = '';
$INQUIRY_DATE = '';
$PK_CUSTOMER_DETAILS = '';
$CALL_PREFERENCE = '';
$REMINDER_OPTION = '';
$SPECIAL_DATE_1 = '';
$DATE_NAME_1 = '';
$SPECIAL_DATE_2 = '';
$DATE_NAME_2 = '';
$ATTENDING_WITH = '';
$PARTNER_FIRST_NAME = '';
$PARTNER_LAST_NAME = '';
$PARTNER_PHONE = '';
$PARTNER_EMAIL = '';
$PARTNER_GENDER = '';
$PARTNER_DOB = '';
$INACTIVE_BY_ADMIN = '';
$CREATED_ON = '';

if (!empty($_GET['id'])) {
    $res = $db->Execute("SELECT * FROM DOA_USERS WHERE IS_DELETED = 0 AND DOA_USERS.PK_USER = '$_GET[id]'");

    if ($res->RecordCount() == 0) {
        header("location:all_customers.php");
        exit;
    }
    $USER_NAME = $res->fields['USER_NAME'];
    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
    $CUSTOMER_ID = $res->fields['USER_ID'];
    $UNIQUE_ID = $res->fields['UNIQUE_ID'];
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
    $PASSWORD = $res->fields['PASSWORD'];
    $INACTIVE_BY_ADMIN = $res->fields['INACTIVE_BY_ADMIN'];
    $CREATE_LOGIN = $res->fields['CREATE_LOGIN'];
    $CREATED_ON = $res->fields['CREATED_ON'];

    $user_interest_other_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_INTEREST_OTHER_DATA` WHERE `PK_USER_MASTER` = '$_GET[master_id]'");
    if ($user_interest_other_data->RecordCount() > 0) {
        $WHAT_PROMPTED_YOU_TO_INQUIRE = $user_interest_other_data->fields['WHAT_PROMPTED_YOU_TO_INQUIRE'];
        $PK_SKILL_LEVEL = $user_interest_other_data->fields['PK_SKILL_LEVEL'];
        $PK_INQUIRY_METHOD = $user_interest_other_data->fields['PK_INQUIRY_METHOD'];
        $INQUIRY_TAKER_ID = $user_interest_other_data->fields['INQUIRY_TAKER_ID'];
        $INQUIRY_DATE = $user_interest_other_data->fields['INQUIRY_DATE'];
    }

    $customer_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = '$_GET[master_id]'");
    if ($customer_data->RecordCount() > 0) {
        $PK_CUSTOMER_DETAILS = $customer_data->fields['PK_CUSTOMER_DETAILS'];
        $CALL_PREFERENCE = $customer_data->fields['CALL_PREFERENCE'];
        $REMINDER_OPTION = $customer_data->fields['REMINDER_OPTION'];
        $ATTENDING_WITH = $customer_data->fields['ATTENDING_WITH'];
        $PARTNER_FIRST_NAME = $customer_data->fields['PARTNER_FIRST_NAME'];
        $PARTNER_LAST_NAME = $customer_data->fields['PARTNER_LAST_NAME'];
        $PARTNER_PHONE = $customer_data->fields['PARTNER_PHONE'];
        $PARTNER_EMAIL = $customer_data->fields['PARTNER_EMAIL'];
        $PARTNER_GENDER = $customer_data->fields['PARTNER_GENDER'];
        $PARTNER_DOB = $customer_data->fields['PARTNER_DOB'];
    }
}

$primary_location = $_GET['PK_LOCATION'] ?? 0;
if (!empty($_GET['master_id']) && $primary_location <= 0) {
    $selected_primary_location = $db->Execute("SELECT DOA_USER_MASTER.PRIMARY_LOCATION_ID, DOA_LOCATION.LOCATION_NAME FROM DOA_USER_MASTER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_USER_MASTER.PRIMARY_LOCATION_ID WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $_GET['master_id']);
    if ($selected_primary_location->RecordCount() > 0) {
        $primary_location = $selected_primary_location->fields['PRIMARY_LOCATION_ID'];
        $PRIMARY_LOCATION_NAME = $selected_primary_location->fields['LOCATION_NAME'];
    }
}


$TAB_PERMISSION_ARRAY = [];
$permission_data = $db->Execute("SELECT * FROM DOA_CUSTOMER_TAB WHERE PERMISSION = 1 AND PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ")");

while (!$permission_data->EOF) {
    $TAB_PERMISSION_ARRAY[] = $permission_data->fields['TAB_NAME'];
    $permission_data->MoveNext();
}

$title = $FIRST_NAME . " " . $LAST_NAME;

$CUSTOMER_NAME = $FIRST_NAME . " " . $LAST_NAME;
$customer = getProfileBadge($CUSTOMER_NAME);
$customer_initial = $customer['initials'];
$customer_color = $customer['color'];

?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php include 'layout/header.php'; ?>

<style>
    .sidebar-link {
        color: #6c757d;
        text-decoration: none;
        padding: 10px 20px;
        display: block;
        border-left: 3px solid transparent;
    }

    .sidebar-link.active {
        background-color: #f0f4f8;
        color: #39b54a;
        border-left-color: #39b54a;
        font-weight: 600;
    }

    .sidebar-link:hover {
        background-color: #f8f9fa;
        color: #39b54a;
    }

    .profile-card {
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        background: #fff;
        margin-bottom: 24px;
        padding: 24px;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .section-desc {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 20px;
    }

    .label {
        font-size: 0.8rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: capitalize;
    }

    .value {
        font-size: 0.9rem;
        margin-bottom: 15px;
    }

    .avatar-placeholder {
        width: 80px;
        height: 80px;
        background: #e9ecef;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }

    .btn-outline-edit {
        border: 1px solid #e0e0e0;
        color: #333;
        font-size: 0.85rem;
        padding: 5px 15px;
        border-radius: 20px;
    }

    .internal-note {
        font-size: 0.9rem;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 10px;
    }

    .add-btn {
        color: #39b54a;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .border-right-light {
        border-right: 1px solid #ddd;
    }

    .main-card {
        background: #fff;
        border: 1px solid #eef0f2;
        border-radius: 16px;
        padding: 24px;
        max-width: 700px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1a1d23;
        margin-bottom: 4px;
    }

    .section-desc {
        font-size: 0.9rem;
        color: #6c757d;
        margin-bottom: 20px;
    }

    /* Inner Family Member Card */
    .family-member-card {
        border: 1px solid #eef0f2;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
        transition: all 0.2s ease;
    }

    .family-member-card:hover {
        border-color: #dee2e6;
        background-color: #fafbfc;
    }

    .member-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2d3436;
        margin-bottom: 2px;
    }

    .member-role {
        font-size: 0.75rem;
        font-weight: 700;
        color: #808e9b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
    }

    .contact-info {
        font-size: 0.9rem;
        color: #636e72;
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .copy-icon {
        font-size: 0.85rem;
        color: #b2bec3;
        cursor: pointer;
        margin-left: 4px;
        transition: color 0.2s;
    }

    .copy-icon:hover {
        color: #39b54a;
    }

    .action-icons i {
        font-size: 1.1rem;
        color: #636e72;
        cursor: pointer;
        padding: 5px;
        transition: color 0.2s;
    }

    .action-icons i:hover {
        color: #1a1d23;
    }

    .add-family-btn {
        display: inline-flex;
        align-items: center;
        color: #39b54a;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        padding: 8px 12px;
        border-radius: 8px;
        transition: background 0.2s;
    }

    .add-family-btn:hover {
        background-color: #f0fff4;
    }

    .enrollment-container {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 30px;
        max-width: 1100px;
        margin: auto;
    }

    /* Balance Stats */
    .stat-label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a1a1a;
    }

    .stat-divider {
        border-left: 1px solid #eee;
        height: 50px;
        margin: 0 40px;
    }

    /* Tables */
    .table thead th {
        background-color: #f8f9fa;
        color: #6c757d;
        font-weight: 500;
        font-size: 0.85rem;
        border-bottom: none;
        padding: 12px 15px;
    }

    .table tbody td {
        vertical-align: middle;
        font-size: 0.9rem;
        padding: 15px;
        border-color: #f1f1f1;
    }

    /* Badges */
    .badge-service {
        padding: 4px 10px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-block;
        text-transform: uppercase;
    }

    .bg-pri {
        background-color: #e7f0ff;
        color: #39b54a;
    }

    .bg-grp {
        background-color: #fff0f5;
        color: #d63384;
    }

    .bg-ext {
        background-color: #f3f0ff;
        color: #6f42c1;
    }

    .bg-pty {
        background-color: #fff4e6;
        color: #fd7e14;
    }

    /* AutoPay Toggle */
    .form-switch .form-check-input {
        width: 2.5em;
        height: 1.25em;
        cursor: pointer;
    }

    .autopay-label {
        font-size: 0.85rem;
        color: #444;
        font-weight: 500;
    }

    .view-schedule {
        font-size: 0.85rem;
        color: #6c757d;
        text-decoration: none;
    }

    .view-schedule:hover {
        text-decoration: underline;
    }

    .appointment-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 24px;
        max-width: 1200px;
        margin: auto;
    }

    /* Table Styling */
    .table {
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table thead th {
        background-color: #f8f9fa;
        color: #6c757d;
        font-weight: 500;
        font-size: 0.85rem;
        border-bottom: 1px solid #eee;
        padding: 12px 15px;
    }

    .table tbody td {
        vertical-align: middle;
        padding: 15px;
        border-bottom: 1px solid #f1f1f1;
        font-size: 0.85rem;
        color: #333;
    }

    /* Date Sidebar Column */
    .date-col {
        background-color: #fff;
        border-right: 1px solid #eee !important;
        text-align: center;
        width: 70px;
    }

    .date-day {
        font-size: 0.75rem;
        color: #adb5bd;
        text-transform: capitalize;
        display: block;
    }

    .date-num {
        font-size: 1.5rem;
        font-weight: 700;
        color: #212529;
    }

    /* Status Badge */
    .status-scheduled {
        border: 1px solid #d1e7dd;
        background-color: #f8fffb;
        color: #39b54a;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
    }

    .status-scheduled i {
        font-size: 0.8rem;
        margin-right: 5px;
    }

    /* Provider Avatar */
    .avatar-sm {
        width: 24px;
        height: 24px;
        background-color: #ffeaa7;
        color: #d63031;
        font-size: 0.7rem;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        margin-right: 8px;
    }

    .text-truncate-custom {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .payments-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 30px;
        max-width: 1100px;
        margin: auto;
    }

    /* Summary Section */
    .summary-row {
        border-top: 1px dashed #dee2e6;
        border-bottom: 1px dashed #dee2e6;
        padding: 25px 0;
        margin: 25px 0;
    }

    .stat-label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #212529;
    }

    .stat-divider {
        border-left: 1px solid #eee;
        height: 60px;
        margin: 0 40px;
    }

    /* Toolbar */
    .search-input {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 8px 12px 8px 35px;
        font-size: 0.9rem;
        width: 320px;
    }

    .search-wrapper {
        position: relative;
    }

    .search-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
    }

    .btn-toolbar {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background: #fff;
        color: #495057;
        font-size: 0.85rem;
        font-weight: 500;
        padding: 8px 16px;
    }

    /* Table Styling */
    .table thead th {
        background-color: #f8f9fa;
        color: #6c757d;
        font-weight: 500;
        font-size: 0.85rem;
        padding: 12px 20px;
        border-bottom: none;
    }

    .table tbody td {
        padding: 18px 20px;
        font-size: 0.9rem;
        color: #333;
        border-bottom: 1px solid #f1f1f1;
    }

    .table-responsive {
        border: 1px solid #eee;
        border-radius: 8px;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        /* display: block; */
        display: flex;
    }

    #load-marker {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        min-height: 120px;
        margin: 24px auto;
        color: #344054;
    }

    #load-marker .loader-ring {
        width: 28px;
        height: 28px;
        border: 4px solid rgba(57, 181, 74, 0.24);
        border-top-color: #39b54a;
        border-radius: 50%;
        animation: loader-spin 0.85s linear infinite;
    }

    #load-marker .loader-text {
        font-size: 0.95rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        color: #252f3f;
    }

    @keyframes loader-spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 0px !important;">

            <div class="container-fluid mt-4">
                <div class="card-box" style="margin-top: 20px;">
                    <a href="all_customers.php" class="d-flex mb-3 px-3">
                        <i class="bi bi-chevron-left font-12"></i>
                        <h6 style="margin-top: 2px; margin-left: 10px;">Customers</h6>
                    </a>
                    <div class="d-flex justify-content-between align-items-center mb-0 pb-4 border-bottom px-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 50px; height: 50px; font-weight: bold; color: #fff !important; background-color: <?= $customer_color ?> !important;"><?= $customer_initial ?></div>
                            <h3 class="mb-0"><?= $CUSTOMER_NAME ?></h3>
                        </div>
                        <button class="btn btn-outline-danger rounded-pill px-4">Delete</button>
                    </div>

                    <div class="row">
                        <div class="col-md-2 border-right-light pt-4">
                            <nav class="flex-column left-tabs">
                                <a class="sidebar-link profile-active active" data-toggle-target=".tab-content-1" href="#"><i class="bi bi-grid me-2"></i> Profile</a>
                                <a class="sidebar-link family-active" href="#" data-toggle-target=".tab-content-2"><i class="bi bi-people me-2"></i> Family</a>
                                <a class="sidebar-link enrollments-active" href="javascript:void(0);" onclick="loadEnrollment('normal')" data-toggle-target=".tab-content-3"><i class="bi bi-journal-text me-2"></i> Enrollments</a>
                                <a class="sidebar-link appointments-active" href="#" data-toggle-target=".tab-content-4"><i class="bi bi-clock me-2"></i> Appointments</a>
                                <a class="sidebar-link payments-active" href="#" data-toggle-target=".tab-content-5"><i class="bi bi-credit-card me-2"></i> Payments</a>
                            </nav>
                        </div>
                        <div class="col-md-10 right-panel">
                            <div class="tab-content tab-content-1 active row profile-section">

                                <div class="col-md-8 pt-4">
                                    <div class="profile-card">
                                        <div class="d-flex justify-content-between border-bottom">
                                            <div>
                                                <div class="section-title">Personal Information</div>
                                                <div class="section-desc">Optional settings section description</div>
                                            </div>
                                            <a class="btn btn-outline-edit" style="height: min-content;" onclick="editPersonalInfo(<?= $PK_USER ?>)">Edit</a>
                                        </div>

                                        <div class="avatar-placeholder mt-3"><i class="bi bi-person-fill text-white fs-1"></i></div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="label">First Name</div>
                                                <div class="value"><?= $FIRST_NAME ?></div>

                                                <div class="label">Customer ID</div>
                                                <div class="value"><?= $CUSTOMER_ID == '' ? 'N/A' : $CUSTOMER_ID ?></div>

                                                <div class="label">Primary Location</div>
                                                <div class="value"><?= $PRIMARY_LOCATION_NAME ?></div>

                                                <div class="label">Phone</div>
                                                <div class="value"><?= $PHONE ?></div>

                                                <div class="label">Reminder Options</div>
                                                <div class="value"><?= $REMINDER_OPTION == '' ? 'N/A' : $REMINDER_OPTION ?></div>

                                                <div class="label">Status</div>
                                                <div class="value"><?= $ACTIVE == 1 ? 'Active' : 'Inactive' ?></div>
                                            </div>

                                            <div class="col-6">
                                                <div class="label">Last Name</div>
                                                <div class="value"><?= $LAST_NAME == '' ? 'N/A' : $LAST_NAME ?></div>

                                                <div class="label">Created On</div>
                                                <div class="value"><?= date('m/d/Y - h:i A', strtotime($CREATED_ON)) ?></div>

                                                <div class="label">Preferred Location</div>
                                                <div class="value"><?= $PRIMARY_LOCATION_NAME ?></div>

                                                <div class="label">Email</div>
                                                <div class="value"><?= $EMAIL_ID ?></div>

                                                <div class="label">Gender</div>
                                                <div class="value"><?= $GENDER == '' ? 'N/A' : $GENDER ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="profile-card">
                                        <div class="d-flex justify-content-between border-bottom">
                                            <div>
                                                <div class="section-title">Address Information</div>
                                                <div class="section-desc">Optional settings section description</div>
                                            </div>
                                            <a class="btn btn-outline-edit" style="height: min-content;">Edit</a>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-6">
                                                <div class="label">Address</div>
                                                <div class="value"><?= $ADDRESS == '' ? 'N/A' : $ADDRESS ?></div>

                                                <div class="label">City</div>
                                                <div class="value"><?= $CITY == '' ? 'N/A' : $CITY ?></div>

                                                <div class="label">Country</div>
                                                <div class="value"><?= $PK_COUNTRY == '' ? 'N/A' : $PK_COUNTRY ?></div>
                                            </div>
                                            <div class="col-6">
                                                <div class="label">Apt/Ste</div>
                                                <div class="value"><?= $ADDRESS_1 == '' ? 'N/A' : $ADDRESS_1 ?></div>

                                                <div class="label">State</div>
                                                <div class="value"><?= $PK_STATES == '' ? 'N/A' : $PK_STATES ?></div>

                                                <div class="label">Postal / Zip Code</div>
                                                <div class="value"><?= $ZIP == '' ? 'N/A' : $ZIP ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="profile-card">
                                        <div class="d-flex justify-content-between border-bottom">
                                            <div>
                                                <div class="section-title">Special Dates</div>
                                                <div class="section-desc">Optional settings section description</div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <?php
                                            $customer_special_date = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_SPECIAL_DATE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                                            if ($customer_special_date->RecordCount() > 0) {
                                                while (!$customer_special_date->EOF) { ?>
                                                    <div class="row mt-3" style="width: 95%; margin-left: auto; margin-right: auto;">
                                                        <div class="col-4">
                                                            <div class="label"><?= $customer_special_date->fields['DATE_NAME'] ?></div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="value"><?= date('m / d / Y', strtotime($customer_special_date->fields['SPECIAL_DATE'])) ?></div>
                                                        </div>
                                                        <div class="col-4 text-end">
                                                            <i class="bi bi-pencil me-3 cursor-pointer"></i>
                                                            <i class="bi bi-trash cursor-pointer"></i>
                                                        </div>
                                                    </div>
                                            <?php $customer_special_date->MoveNext();
                                                }
                                            } ?>
                                            <div class="mt-3">
                                                <a href="#" class="add-btn"><i class="bi bi-plus"></i> Add New</a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="profile-card">
                                        <div class="d-flex justify-content-between border-bottom">
                                            <div>
                                                <div class="section-title">Documents</div>
                                                <div class="section-desc">Optional settings section description</div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <?php
                                            if (!empty($_GET['id'])) {
                                                $user_doc_count = 0;
                                                $row = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_DOCUMENT WHERE PK_USER_MASTER = '$PK_USER_MASTER'");
                                                while (!$row->EOF) { ?>
                                                    <div class="d-flex justify-content-between align-items-center py-2 mt-3" style="width: 95%; margin-left: auto; margin-right: auto;">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-danger-subtle p-2 rounded me-3">
                                                                <i class="bi bi-file-earmark-pdf-fill text-danger fs-5"></i>
                                                            </div>
                                                            <div>
                                                                <a target="_blank" href="<?= $row->fields['FILE_PATH'] ?>">
                                                                    <div class="fw-semibold mb-0" style="font-size: 0.9rem;"><?= $row->fields['DOCUMENT_NAME'] ?></div>
                                                                </a>
                                                                <!-- <div class="text-muted" style="font-size: 0.75rem;">2.4 MB</div> -->
                                                            </div>
                                                        </div>
                                                        <div class="text-secondary">
                                                            <a href="javascript:;" onclick="removeUserDocument(this);">
                                                                <i class="bi bi-trash cursor-pointer"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php $row->MoveNext();
                                                    $user_doc_count++;
                                                } ?>
                                            <?php } ?>
                                            <div class="mt-3">
                                                <a href="#" class="add-btn"><i class="bi bi-plus"></i> Add New</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 pt-4">
                                    <div class="profile-card">
                                        <div class="section-title border-bottom pb-2">Internal Notes</div>
                                        <div class="mt-3">
                                            <?php
                                            $comment_data = $db->Execute("SELECT $account_database.DOA_COMMENT.PK_COMMENT, $account_database.DOA_COMMENT.COMMENT, $account_database.DOA_COMMENT.COMMENT_DATE, $account_database.DOA_COMMENT.ACTIVE, CONCAT($master_database.DOA_USERS.FIRST_NAME, ' ', $master_database.DOA_USERS.LAST_NAME) AS FULL_NAME FROM $account_database.`DOA_COMMENT` INNER JOIN $master_database.DOA_USERS ON $account_database.DOA_COMMENT.BY_PK_USER = $master_database.DOA_USERS.PK_USER WHERE $account_database.DOA_COMMENT.`FOR_PK_USER` = " . $PK_USER);
                                            $i = 1;
                                            while (!$comment_data->EOF) { ?>

                                                <div class="internal-note">
                                                    <div class="d-flex justify-content-between"><strong><?= $comment_data->fields['FULL_NAME'] ?></strong> <small class="text-muted"><?= date('m/d/Y', strtotime($comment_data->fields['COMMENT_DATE'])) ?></small></div>
                                                    <p class="mb-0"><?= $comment_data->fields['COMMENT'] ?></p>
                                                </div>
                                            <?php $comment_data->MoveNext();
                                                $i++;
                                            } ?>
                                            <a href="#" class="add-btn"><i class="bi bi-plus"></i> Add New</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-content tab-content-2 row family-section">
                                <div class="col-md-10 p-4">
                                    <div class="main-card mr-auto">
                                        <div class="section-title">Family Information</div>
                                        <div class="section-desc border-bottom pb-3">Optional settings section description</div>

                                        <div class="mt-4">
                                            <?php
                                            $family_member_details = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_DETAILS WHERE PK_CUSTOMER_PRIMARY = '$PK_CUSTOMER_DETAILS' AND IS_PRIMARY = 0");
                                            if ($PK_CUSTOMER_DETAILS > 0 && $family_member_details->RecordCount() > 0) {
                                                while (!$family_member_details->EOF) {
                                                    $relation = $db->Execute("SELECT * FROM DOA_RELATIONSHIP WHERE PK_RELATIONSHIP = " . $family_member_details->fields['PK_RELATIONSHIP']); ?>
                                                    <div class="family-member-card d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <div class="member-name"><?= $family_member_details->fields['FIRST_NAME'] ?> <?= $family_member_details->fields['LAST_NAME'] ?></div>
                                                            <div class="member-role"><?= $relation->fields['RELATIONSHIP'] ?></div>
                                                            <div class="contact-info">
                                                                <span><?= $family_member_details->fields['EMAIL'] ?> <i class="bi bi-copy copy-icon"></i></span>
                                                                <span><?= $family_member_details->fields['PHONE'] ?> <i class="bi bi-copy copy-icon"></i></span>
                                                            </div>
                                                        </div>
                                                        <div class="action-icons">
                                                            <i class="bi bi-pencil me-2"></i>
                                                            <i class="bi bi-trash"></i>
                                                        </div>
                                                    </div>
                                                <?php $family_member_details->MoveNext();
                                                }
                                            } else { ?>
                                                <div class="text-center py-5">
                                                    <i class="bi bi-people-fill fs-1 text-muted"></i>
                                                    <p class="text-muted mt-3">No family members added yet.</p>
                                                </div>
                                            <?php } ?>


                                        </div>

                                        <a href="#" class="add-family-btn mt-2">
                                            <i class="bi bi-plus-lg me-2"></i> Add Family
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-content tab-content-3 row enrollments-section">
                                <div class="col-md-12 px-3 pt-4 pb-4" id="enrollment_list">


                                </div>
                            </div>
                            <div class="tab-content tab-content-4 row appointment-section">
                                <div class="col-md-12 px-3 pt-4 pb-4">
                                    <div class="appointment-card">
                                        <div class="d-flex justify-content-between align-items-start mb-4">
                                            <div>
                                                <h5 class="fw-bold mb-1">Appointments</h5>
                                                <p class="text-muted small">Optional settings section description</p>
                                            </div>
                                            <button class="btn btn-light btn-sm border text-muted px-3 py-2" style="border-radius: 8px;">
                                                <i class="bi bi-plus"></i> New Appointment
                                            </button>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="border-end"></th>
                                                        <th>Appointment</th>
                                                        <th>Enrollment ID</th>
                                                        <th>Time <i class="bi bi-chevron-expand"></i></th>
                                                        <th>Service Provider <i class="bi bi-chevron-expand"></i></th>
                                                        <th>Status <i class="bi bi-chevron-expand"></i></th>
                                                        <th>Comments <i class="bi bi-chevron-expand"></i></th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td rowspan="2" class="date-col">
                                                            <span class="date-day">Mon</span>
                                                            <span class="date-num">7</span>
                                                        </td>
                                                        <td>Salsa Intermed...</td>
                                                        <td class="text-muted">-3 PRI: 50, GRP:...</td>
                                                        <td>9AM–10AM</td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <span class="avatar-sm">S</span> Sophia Williams
                                                            </div>
                                                        </td>
                                                        <td><span class="status-scheduled"><i class="bi bi-check-circle-fill"></i> Scheduled</span></td>
                                                        <td class="text-muted">Comments go he...</td>
                                                        <td><i class="bi bi-three-dots-vertical text-muted cursor-pointer"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Salsa Intermed...</td>
                                                        <td class="text-muted">-3 PRI: 50, GRP:...</td>
                                                        <td>9AM–10AM</td>
                                                        <td>
                                                            <div class="d-flex align-items-center"><span class="avatar-sm">S</span> Sophia Williams</div>
                                                        </td>
                                                        <td><span class="status-scheduled"><i class="bi bi-check-circle-fill"></i> Scheduled</span></td>
                                                        <td class="text-muted">Comments go he...</td>
                                                        <td><i class="bi bi-three-dots-vertical text-muted"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td rowspan="2" class="date-col border-top">
                                                            <span class="date-day">Tues</span>
                                                            <span class="date-num">8</span>
                                                        </td>
                                                        <td class="border-top">Salsa Intermed...</td>
                                                        <td class="text-muted border-top">-3 PRI: 50, GRP:...</td>
                                                        <td class="border-top">9AM–10AM</td>
                                                        <td class="border-top">
                                                            <div class="d-flex align-items-center">
                                                                <span class="avatar-sm">S</span> Sophia Williams
                                                            </div>
                                                        </td>
                                                        <td class="border-top"><span class="status-scheduled"><i class="bi bi-check-circle-fill"></i> Scheduled</span></td>
                                                        <td class="text-muted border-top">Comments go he...</td>
                                                        <td class="border-top"><i class="bi bi-three-dots-vertical text-muted"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Salsa Intermed...</td>
                                                        <td class="text-muted">-3 PRI: 50, GRP:...</td>
                                                        <td>9AM–10AM</td>
                                                        <td>
                                                            <div class="d-flex align-items-center"><span class="avatar-sm">S</span> Sophia Williams</div>
                                                        </td>
                                                        <td><span class="status-scheduled"><i class="bi bi-check-circle-fill"></i> Scheduled</span></td>
                                                        <td class="text-muted">Comments go he...</td>
                                                        <td><i class="bi bi-three-dots-vertical text-muted"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td rowspan="4" class="date-col border-top">
                                                            <span class="date-day">Wed</span>
                                                            <span class="date-num">9</span>
                                                        </td>
                                                        <td class="border-top">Salsa Intermed...</td>
                                                        <td class="text-muted border-top">-3 PRI: 50, GRP:...</td>
                                                        <td class="border-top">9AM–10AM</td>
                                                        <td class="border-top">
                                                            <div class="d-flex align-items-center">
                                                                <span class="avatar-sm">S</span> Sophia Williams
                                                            </div>
                                                        </td>
                                                        <td class="border-top"><span class="status-scheduled"><i class="bi bi-check-circle-fill"></i> Scheduled</span></td>
                                                        <td class="text-muted border-top">Comments go he...</td>
                                                        <td class="border-top"><i class="bi bi-three-dots-vertical text-muted"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Salsa Intermed...</td>
                                                        <td class="text-muted">-3 PRI: 50, GRP:...</td>
                                                        <td>9AM–10AM</td>
                                                        <td>
                                                            <div class="d-flex align-items-center"><span class="avatar-sm">S</span> Sophia Williams</div>
                                                        </td>
                                                        <td><span class="status-scheduled"><i class="bi bi-check-circle-fill"></i> Scheduled</span></td>
                                                        <td class="text-muted">Comments go he...</td>
                                                        <td><i class="bi bi-three-dots-vertical text-muted"></i></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-content tab-content-5 row payments-section">
                                <div class="col-md-12 px-3 pt-4 pb-4">
                                    <div class="payments-card">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="fw-bold mb-1">Payments</h5>
                                                <p class="text-muted small mb-0">Optional settings section description</p>
                                            </div>
                                            <button class="btn btn-light btn-sm border text-muted px-3 py-2" style="border-radius: 8px;">
                                                <i class="bi bi-plus"></i> New Payment
                                            </button>
                                        </div>

                                        <div class="summary-row d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <div class="stat-label">Total Payments</div>
                                                <div class="stat-value">$2,000.00</div>
                                            </div>
                                            <div class="stat-divider"></div>
                                            <div class="flex-grow-1">
                                                <div class="stat-label">Pending Payments</div>
                                                <div class="stat-value">$0.00</div>
                                            </div>
                                            <div class="stat-divider"></div>
                                            <div class="flex-grow-1">
                                                <div class="stat-label">Wallet Balance</div>
                                                <div class="stat-value">$100.00</div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div class="search-wrapper">
                                                <i class="bi bi-search"></i>
                                                <input type="text" class="search-input" placeholder="Search...">
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-toolbar"><i class="bi bi-filter-left me-1"></i> Filter</button>
                                                <button class="btn btn-toolbar"><i class="bi bi-download me-1"></i> Export to Excel</button>
                                            </div>
                                        </div>

                                        <div class="table-responsive  border-0">
                                            <table class="table mb-0 border-0">
                                                <thead>
                                                    <tr>
                                                        <th>Receipt</th>
                                                        <th>Date</th>
                                                        <th>Enrollment</th>
                                                        <th>Method</th>
                                                        <th>Memo</th>
                                                        <th>Paid <i class="bi bi-chevron-expand small"></i></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>D2-19</td>
                                                        <td>11/14/2025</td>
                                                        <td># -3</td>
                                                        <td>Cash</td>
                                                        <td>-</td>
                                                        <td>$1,600.00</td>
                                                    </tr>
                                                    <tr>
                                                        <td>D2-19</td>
                                                        <td>11/14/2025</td>
                                                        <td># -3</td>
                                                        <td>Cash</td>
                                                        <td>-</td>
                                                        <td>$1,600.00</td>
                                                    </tr>
                                                    <tr>
                                                        <td>D2-19</td>
                                                        <td>11/14/2025</td>
                                                        <td># -3</td>
                                                        <td>Cash</td>
                                                        <td>-</td>
                                                        <td>$1,600.00</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>


<?php require_once('../includes/footer.php'); ?>


<script>
    $('.sidebar-link').on('click', function(evt) {
        evt.preventDefault();

        // 1. Manage Sidebar Links: Remove active from all, add to the clicked one
        $('.sidebar-link').removeClass('active');
        $(this).addClass('active');

        // 2. Manage Tab Content: Hide all, then show the one matching the data-attribute
        var sel = $(this).data('toggle-target'); // Using .data() is cleaner jQuery style
        $('.tab-content').removeClass('active');
        $(sel).addClass('active');
    });
</script>

<script>
    function loadEnrollment(type) {
        enr_tab_type = type;
        page_count = 1;
        hasMore = true;
        loading = false;
        $("#enrollment_list").html('<div id="load-marker"><div class="loader-ring"></div><div class="loader-text">Loading Enrollments...</div></div>');

        // Load first page
        showEnrollmentList(page_count, enr_tab_type);

        if (observer) observer.disconnect();

        observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting && !loading && hasMore) {
                page_count++;
                showEnrollmentList(page_count, enr_tab_type);
            }
        }, {
            rootMargin: "300px",
            threshold: 0.1
        });

        observer.observe(document.querySelector("#load-marker"));
    }

    $(window).on("scroll", function() {
        if (!loading && hasMore && enr_tab_type != '') {
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200) {
                page_count++;
                showEnrollmentList(page_count, enr_tab_type);
            }
        }
    });


    var enr_tab_type = '';
    var page_count = 1;
    var loading = false;
    var hasMore = true;
    var observer;

    function showEnrollmentList(page, type) {
        enr_tab_type = type;
        let PK_USER_MASTER = <?= $PK_USER_MASTER ?>;
        let PK_USER = <?= $PK_USER ?>;

        loading = true;
        $("#load-marker").html('<div class="loader-ring"></div><div class="loader-text">Loading Enrollments...</div>');

        $.ajax({
            url: "partials/ajaxList/customer_enrollments.php",
            type: "GET",
            data: {
                search_text: '',
                page: page,
                type: type,
                pk_user: PK_USER,
                master_id: PK_USER_MASTER
            },
            cache: false,
            success: function(result) {
                $("#load-marker").text("No data available");
                if (result && result.trim() !== "") {
                    // Insert new content ABOVE the marker
                    $('#load-marker').before(result);
                    loading = false;
                } else {
                    // No more data
                    hasMore = false;
                    $("#load-marker").text("No more data");
                    if (observer) observer.disconnect();
                }
            },
            error: function() {
                loading = false;
                $("#load-marker").text("Error loading data");
            }
        });
    }
</script>

</html>