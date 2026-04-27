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


$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

$payment_gateway_data = getPaymentGatewayData();

$PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
$GATEWAY_MODE  = $payment_gateway_data->fields['GATEWAY_MODE'];

$SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];

$AUTHORIZE_LOGIN_ID         = $payment_gateway_data->fields['LOGIN_ID']; //"4Y5pCy8Qr";
$AUTHORIZE_TRANSACTION_KEY     = $payment_gateway_data->fields['TRANSACTION_KEY']; //"4ke43FW8z3287HV5";
$AUTHORIZE_CLIENT_KEY         = $payment_gateway_data->fields['AUTHORIZE_CLIENT_KEY']; //"8ZkyJnT87uFztUz56B4PfgCe7yffEZA4TR5dv8ALjqk5u9mr6d8Nmt8KHyp8s9Ay";

$MERCHANT_ID            = $payment_gateway_data->fields['MERCHANT_ID'];
$API_KEY                = $payment_gateway_data->fields['API_KEY'];
$PUBLIC_API_KEY         = $payment_gateway_data->fields['PUBLIC_API_KEY'];


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

    .dataTables_filter {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .date-filter input {
        height: 30px;
    }

    .dataTables_filter input {
        width: 250px;
        /* change width as needed */
        height: 38px;
        /* change height */
        font-size: 13px;
        padding: 4px 10px;
    }

    #paymentRegisterTable_paginate {
        float: right;
    }

    .form-control-sm {
        font-size: 13px;
    }

    .dataTables_length label {
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .dataTables_length select {
        width: auto !important;
    }

    .pagination .page-item.active .page-link {
        background-color: #39b54a;
        border-color: #39b54a;
    }

    .page-link {
        color: #39b54a;
        font-size: 13px;
    }

    .page-link:hover {
        color: #1a1d23;
    }

    .btn-outline-edit:hover {
        background-color: #39b54a !important;
        color: #fff !important;
    }

    .view-btn-icon:hover {
        background-color: #39b54a !important;
        color: #fff !important;
    }

    .view-btn-icon.active {
        background-color: #39b54a !important;
        color: #fff !important;
    }

    table.dataTable thead th.sorting::after {
        content: "⇅";
        margin-left: 5px;
        font-size: 15px;
    }

    table.dataTable thead th.sorting_asc::after {
        content: "↑";
        margin-left: 5px;
    }

    table.dataTable thead th.sorting_desc::after {
        content: "↓";
        margin-left: 5px;
    }

    .btn.btn-secondary {
        padding: 5px 15px;
        font-size: 12px;
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
                        <button class="btn btn-outline-danger rounded-pill px-4" onclick="deleteThisCustomer(<?= $PK_USER ?>)">Delete</button>
                    </div>

                    <div class="row">
                        <div class="col-md-2 border-right-light pt-4">
                            <nav class="flex-column left-tabs">
                                <a class="sidebar-link profile-active active" data-toggle-target=".tab-content-1" href="#"><i class="bi bi-grid me-2"></i> Profile</a>
                                <a class="sidebar-link family-active" href="#" data-toggle-target=".tab-content-2"><i class="bi bi-people me-2"></i> Family</a>
                                <a class="sidebar-link enrollments-active" href="javascript:void(0);" onclick="loadEnrollment('normal')" data-toggle-target=".tab-content-3"><i class="bi bi-journal-text me-2"></i> Enrollments</a>
                                <a class="sidebar-link appointments-active" href="javascript:void(0);" onclick="getAppointmentList('normal')" data-toggle-target=".tab-content-4"><i class="bi bi-clock me-2"></i> Appointments</a>
                                <a class="sidebar-link payments-active" href="javascript:void(0);" onclick="getPaymentRegisterData()" data-toggle-target=".tab-content-5"><i class="bi bi-credit-card me-2"></i> Payments</a>
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
                                                    <div class="d-flex justify-content-between">
                                                        <strong><?= $comment_data->fields['FULL_NAME'] ?></strong>
                                                        <div class="d-flex gap-2" style="margin-right: 45%;">
                                                            <a href="javascript:;" onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><i class="fa fa-pencil" style="font-size: 16px;"></i></a>
                                                            <a href="javascript:;" onclick="deleteComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><i class="fa fa-trash" style="font-size: 16px;"></i></a>
                                                        </div>
                                                        <small class="text-muted"><?= date('m/d/Y', strtotime($comment_data->fields['COMMENT_DATE'])) ?></small>
                                                    </div>

                                                    <p class="mb-0"><?= $comment_data->fields['COMMENT'] ?></p>
                                                </div>
                                            <?php $comment_data->MoveNext();
                                                $i++;
                                            } ?>
                                            <a href="javascript:;" onclick="createUserComment();" class="add-btn"><i class="bi bi-plus"></i> Add New</a>
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
                                <div class="col-md-12 px-4 pt-4 pb-4" id="enrollment_list">


                                </div>
                            </div>


                            <div class="tab-content tab-content-4 row appointment-section">
                                <div class="col-md-12 px-4 pt-4 pb-4" id="appointment_area">

                                </div>
                            </div>


                            <div class="tab-content tab-content-5 row payments-section">
                                <div class="col-md-12 px-3 pt-4 pb-4" id="payment_list">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

<!--Verify Password Model-->
<div class="modal fade" id="verify_password_model" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="verify_password_form" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><b>Verify Password</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Enter your profile password</label>
                                <input type="password" id="verify_password" name="verify_password" class="form-control" placeholder="Password" required>
                                <p id="verify_password_error" style="color: red;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary cancel" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="card-button" class="btn btn-secondary" style="float: right;">Process</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!--Comment Model-->
<div class="modal fade" id="comment_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4><b id="comment_header">Add Comment</b></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="$('#comment_modal').modal('hide');"></button>
            </div>
            <form id="comment_add_edit_form" role="form" action="" method="post">
                <div class="modal-body">
                    <input type="hidden" name="FUNCTION_NAME" value="saveCommentData">
                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                    <input type="hidden" name="PK_COMMENT" id="PK_COMMENT" value="0">
                    <div class="p-20">
                        <div class="form-group">
                            <label class="form-label">Comments</label>
                            <textarea class="form-control" rows="10" name="COMMENT" id="COMMENT" required></textarea>
                        </div>
                        <!-- <div class="form-group" id="comment_active" style="display: none;">
                            <label class="form-label">Active</label>
                            <div>
                                <label><input type="radio" id="COMMENT_ACTIVE_1" name="ACTIVE" value="1">&nbsp;&nbsp;&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                <label><input type="radio" id="COMMENT_ACTIVE_0" name="ACTIVE" value="0">&nbsp;&nbsp;&nbsp;No</label>
                            </div>
                        </div> -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary cancel" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-secondary" style="float: right;">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!--Auto-pay Credit Card Modal-->
<div class="modal fade" id="credit_card_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4><b>Select Credit Card for Auto-Pay</b></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="$('#credit_card_modal').modal('hide');"></button>
            </div>
            <div class="modal-body">
                <div class="tab-pane" id="credit_card" role="tabpanel">
                    <div class="p-20">
                        <?php if ($PAYMENT_GATEWAY == null || $PAYMENT_GATEWAY == '') { ?>
                            <div class="alert alert-danger">
                                Payment Gateway is Not set Yet
                            </div>
                        <?php } else { ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="add_credit_card_div_auto_pay" style="display: none; width: 150%;">

                                    </div>
                                </div>
                            </div>

                            <div class="row" id="saved_credit_card_list_auto_pay" style="display: none; padding-left: 6%;">

                            </div>
                        <?php } ?>
                    </div>
                </div>
                <input type="hidden" name="AUTO_PAY_ENROLLMENT_ID" id="AUTO_PAY_ENROLLMENT_ID">
                <input type="hidden" name="AUTO_PAY_PAYMENT_METHOD_ID" id="AUTO_PAY_PAYMENT_METHOD_ID">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="$('#credit_card_modal').modal('hide');">Close</button>
                <button type="button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;" onclick="addEnrollmentAutoPayCreditCard()">Process</button>
            </div>
        </div>
    </div>
</div>



<!--Edit Billing Due Date Model-->
<div class="modal fade" id="billing_due_date_model" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="edit_due_date_form" method="post">
            <input type="hidden" name="PK_ENROLLMENT_LEDGER" id="PK_ENROLLMENT_LEDGER">
            <input type="hidden" name="old_due_date" id="old_due_date">
            <input type="hidden" name="edit_type" id="edit_type">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><b>Edit Due Date</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Due Date</label>
                                <input type="text" id="due_date" name="due_date" class="form-control datepicker-normal" placeholder="Due Date" autocomplete="off" required onkeydown="return false;">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Enter your profile password</label>
                                <input type="password" id="due_date_verify_password" name="due_date_verify_password" class="form-control" placeholder="Password" required>
                                <p id="due_date_verify_password_error" style="color: red;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary cancel" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="card-button" class="btn btn-secondary" style="float: right;">Process</button>
                </div>
            </div>
        </form>
    </div>
</div>


<!--Refund Model-->
<div class="modal fade" id="refund_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 450px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">How you want your money back?</label>
                            <div class="col-md-12">
                                <select class="form-control" required name="PK_PAYMENT_TYPE_REFUND" id="PK_PAYMENT_TYPE_REFUND" onchange="selectRefundType(this)">
                                    <option value="">Select</option>
                                    <?php
                                    $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_PAYMENT_TYPE']; ?>"><?= $row->fields['PAYMENT_TYPE'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row" id="check_payment" style="display: none;">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Check Number</label>
                                    <div class="col-md-12">
                                        <input type="text" name="REFUND_CHECK_NUMBER" id="REFUND_CHECK_NUMBER" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Check Date</label>
                                    <div class="col-md-12">
                                        <input type="text" name="REFUND_CHECK_DATE" id="REFUND_CHECK_DATE" class="form-control datepicker-normal">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="REFUND_AMOUNT">How much refund you want?</label>
                            <div class="col-md-12">
                                <input class="form-control" name="REFUND_AMOUNT" id="REFUND_AMOUNT" value="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" id="card-button" class="btn btn-secondary" style="float: right;" onclick="$('.trigger_this').trigger('click');">Process</button>
            </div>
        </div>
    </div>
</div>


<!--Confirm Model-->
<div class="modal fade" id="move_to_wallet_model" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 450px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <h5>Are you sure you want to move $<span id="move_amount">0.00</span> to wallet?</h5>
                            <input type="hidden" id="confirm_move" value="0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#confirm_move').val(1);$('.trigger_this').trigger('click');">Yes</button>
                <button type="button" class="btn btn-secondary cancel" onclick="$('#confirm_move').val(0);$('#move_to_wallet_model').modal('hide');">No</button>
            </div>
        </div>
    </div>
</div>


<?php require_once('../includes/footer.php'); ?>

<!--Payment Model-->
<?php include('includes/enrollment_payment_v2.php'); ?>

<!--Edit Appointment Model-->
<div class="modal fade" id="edit_appointment_modal" tabindex="-1" aria-hidden="true">

</div>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

<!-- JSZip (REQUIRED for Excel) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<!-- Excel button -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

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

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $('.datepicker-past').datepicker({
        format: 'mm/dd/yyyy',
        maxDate: 0,
        changeMonth: true,
        changeYear: true,
        yearRange: '1900:' + new Date().getFullYear(),
    });
</script>

<script>
    function deleteThisCustomer(PK_USER) {
        Swal.fire({
            title: "Are you sure?",
            text: "Deleting this profile will erase all data related to this person. Even previous numbers, reports, appointments and enrollments.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#verify_password_model').modal('show');
            } else {
                Swal.fire({
                    title: "Cancelled",
                    text: "Your imaginary file is safe :)",
                    icon: "error"
                });
            }
        });
    }

    $('#verify_password_form').on('submit', function(event) {
        event.preventDefault();
        let pk_user = <?= $PK_USER ?>;
        let password = $('#verify_password').val();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: {
                FUNCTION_NAME: 'deleteCustomerAfterVerify',
                pk_user: pk_user,
                PASSWORD: password
            },
            success: function(data) {
                $('#verify_password_error').slideUp();
                if (data == 1) {
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your file has been deleted.",
                        icon: "success",
                        timer: 3000,
                    }).then((result) => {
                        window.location.href = 'all_customers.php';
                    });
                } else {
                    $('#verify_password_error').text("Incorrect Password").slideDown();
                }
            }
        });
    });
</script>

<!-- All function related to comment -->
<script>
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

    function openCommentModel() {
        $('#comment_modal').modal('show');
    }

    $(document).on('submit', '#comment_add_edit_form', function(event) {
        event.preventDefault();
        let form_data = new FormData($('#comment_add_edit_form')[0]); //$('#document_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            success: function(data) {
                window.location.reload();
            }
        });
    });

    function deleteComment(PK_COMMENT) {
        Swal.fire({
            title: "Are you sure?",
            text: "Deleting this comment cannot be undone.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deleteCommentData',
                        PK_COMMENT: PK_COMMENT
                    },
                    success: function(data) {
                        window.location.reload();
                    }
                });
            }
        });
    }
</script>

<!-- All function related to enrollment -->
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

    function showEnrollmentDetails(param, PK_USER, PK_USER_MASTER, PK_ENROLLMENT_MASTER, ENROLLMENT_ID, type, details) {
        $.ajax({
            url: "partials/ajaxList/customer_enrollment_details.php",
            type: "GET",
            data: {
                PK_USER: PK_USER,
                PK_USER_MASTER: PK_USER_MASTER,
                PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                ENROLLMENT_ID: ENROLLMENT_ID,
                type: type
            },
            async: false,
            cache: false,
            success: function(result) {
                $(param).closest('.enrollment_div').find('.enrollment_details').html(result).slideToggle();
            }
        });
    }

    function openReceipt(PK_ENROLLMENT_MASTER, RECEIPT_NUMBER) {
        let RECEIPT_NUMBER_ARRAY = RECEIPT_NUMBER.split(',');
        for (let i = 0; i < RECEIPT_NUMBER_ARRAY.length; i++) {
            window.open('generate_receipt_pdf.php?master_id=' + PK_ENROLLMENT_MASTER + '&receipt=' + RECEIPT_NUMBER_ARRAY[i], '_blank');
        }
    }

    function changeEnrollmentAutoPay(PK_ENROLLMENT_MASTER) {
        var checkbox = event.target;
        var isRecipient = checkbox.checked ? 1 : 0;

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: {
                FUNCTION_NAME: 'changeEnrollmentAutoPay',
                PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                ACTIVE_AUTO_PAY: isRecipient
            },
            success: function(data) {

            }
        });
    }

    function addEnrollmentAutoPay(PK_ENROLLMENT_MASTER) {
        $('#AUTO_PAY_ENROLLMENT_ID').val(PK_ENROLLMENT_MASTER);
        getSavedCreditCardListAutoPay();
    }

    function getSavedCreditCardListAutoPay() {
        let PK_USER_MASTER = <?= $PK_USER_MASTER ?>;
        $('#credit_card_modal').modal('show');
        $.ajax({
            url: "ajax/get_credit_card_list.php",
            type: 'POST',
            data: {
                PK_USER_MASTER: PK_USER_MASTER,
                call_from: 'enrollment_auto_pay'
            },
            success: function(data) {
                $('#saved_credit_card_list_auto_pay').slideDown().html(data);
                addCreditCardAutoPay();
            }
        });
    }

    function selectAutoPayCreditCard(param) {
        let payment_id = $(param).attr('id');

        $('.credit-card-div').css("opacity", "1");
        $(param).css("opacity", "0.6");

        $('#AUTO_PAY_PAYMENT_METHOD_ID').val(payment_id);
    }

    function addCreditCardAutoPay() {
        let PK_USER = <?= $PK_USER ?>;
        let PK_USER_MASTER = <?= $PK_USER_MASTER ?>;
        $.ajax({
            url: "includes/save_credit_card.php",
            type: 'POST',
            data: {
                PK_USER: PK_USER,
                PK_USER_MASTER: PK_USER_MASTER,
                call_from: 'enrollment_auto_pay'
            },
            success: function(data) {
                $('#add_credit_card_div_auto_pay').slideDown().html(data);
                addCreditCard();
            }
        });
    }

    function addEnrollmentAutoPayCreditCard() {
        let PK_ENROLLMENT_MASTER = $('#AUTO_PAY_ENROLLMENT_ID').val();
        let PAYMENT_METHOD_ID = $('#AUTO_PAY_PAYMENT_METHOD_ID').val();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: {
                FUNCTION_NAME: 'addEnrollmentAutoPay',
                PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                PAYMENT_METHOD_ID: PAYMENT_METHOD_ID
            },
            success: function(data) {
                if (data == 1) {
                    Swal.fire({
                        title: "Success!",
                        text: "Auto Pay Added for this Enrollment.",
                        icon: "success",
                        timer: 2000,
                    }).then((result) => {
                        //window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=enrollment';
                    });
                } else {
                    Swal.fire({
                        title: "Error!",
                        text: "Something went wrong, please try again.",
                        icon: "error",
                        timer: 3000,
                    });
                }
            }
        });
    }









    function toggleEnrollmentCheckboxes(PK_ENROLLMENT_MASTER) {
        let toggleCheckbox = document.getElementById('toggleEnrollment_' + PK_ENROLLMENT_MASTER);
        let childCheckboxes = document.getElementsByClassName('PAYMENT_CHECKBOX_' + PK_ENROLLMENT_MASTER);
        let payNow = document.getElementById('payNow');

        // If the toggle checkbox is checked, uncheck all child checkboxes
        if (toggleCheckbox.checked) {
            for (let i = 0; i < childCheckboxes.length; i++) {
                childCheckboxes[i].checked = true;
                payNow.disabled = true;
            }
        } else {
            for (let i = 0; i < childCheckboxes.length; i++) {
                childCheckboxes[i].checked = false;
                payNow.disabled = false;
            }
        }
    }

    $(document).on('change', '.pay_now_check', function() {
        if ($('.pay_now_check').is(':checked')) {
            $('.pay_selected_btn').prop('disabled', false);
            $('.pay_now_button').prop('disabled', true);
        } else {
            $('.pay_selected_btn').prop('disabled', true);
            $('.pay_now_button').prop('disabled', false);
        }
    });






    function payNow(PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BILLED_AMOUNT, ENROLLMENT_ID) {
        $('.partial_payment').show();
        $('#PARTIAL_PAYMENT').prop('checked', false);
        $('.partial_payment_div').slideUp();

        $('.PAYMENT_TYPE').val('');
        $('#remaining_amount_div').slideUp();

        $('#enrollment_number').text(ENROLLMENT_ID);
        $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
        $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#ACTUAL_AMOUNT').val(BILLED_AMOUNT);
        $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
        //$('#payment_confirmation_form_div_customer').slideDown();
        //openPaymentModel();
        $('#enrollment_payment_modal').modal('show');
    }

    function paySelected(PK_ENROLLMENT_MASTER, ENROLLMENT_ID) {
        $('.partial_payment').hide();
        $('#PARTIAL_PAYMENT').prop('checked', false);
        $('.partial_payment_div').slideUp();

        $('.PAYMENT_TYPE').val('');
        $('#remaining_amount_div').slideUp();

        let BILLED_AMOUNT = [];
        let PK_ENROLLMENT_LEDGER = [];

        $(".PAYMENT_CHECKBOX_" + PK_ENROLLMENT_MASTER + ":checked").each(function() {
            BILLED_AMOUNT.push(parseFloat($(this).data('billed_amount')));
            PK_ENROLLMENT_LEDGER.push($(this).val());
        });

        console.log(BILLED_AMOUNT);

        let TOTAL = BILLED_AMOUNT.reduce(getSum, 0);

        function getSum(total, num) {
            return total + num;
        }

        $('#enrollment_number').text(ENROLLMENT_ID);
        $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
        $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#ACTUAL_AMOUNT').val(parseFloat(TOTAL).toFixed(2));
        $('#AMOUNT_TO_PAY').val(parseFloat(TOTAL).toFixed(2));
        //$('#payment_confirmation_form_div_customer').slideDown();
        //openPaymentModel();
        $('#enrollment_payment_modal').modal('show');
    }







    function moveToWallet(param, PK_ENROLLMENT_PAYMENT, PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, PK_USER_MASTER, BALANCE, ENROLLMENT_TYPE, TRANSACTION_TYPE, PAYMENT_COUNTER) {
        let PK_PAYMENT_TYPE = $('#refund_modal #PK_PAYMENT_TYPE_REFUND').val();
        let confirm_move = $('#confirm_move').val();
        if (TRANSACTION_TYPE == 'Refund' && PK_PAYMENT_TYPE == 0) {
            $('.trigger_this').removeClass('trigger_this');
            $(param).addClass('trigger_this');
            $('#REFUND_AMOUNT').val(BALANCE);
            $('#refund_modal').modal('show');
        } else {
            if (TRANSACTION_TYPE == 'Move' && confirm_move == 0) {
                $('.trigger_this').removeClass('trigger_this');
                $(param).addClass('trigger_this');
                $('#move_amount').text(parseFloat(BALANCE).toFixed(2));
                $('#move_to_wallet_model').modal('show');
            } else {
                let REFUND_AMOUNT = $('#REFUND_AMOUNT').val();
                if (REFUND_AMOUNT > BALANCE) {
                    alert("Refund amount can't be grater then balance");
                    $('#REFUND_AMOUNT').val(BALANCE);
                } else {
                    let REFUND_CHECK_NUMBER = $('#REFUND_CHECK_NUMBER').val();
                    let REFUND_CHECK_DATE = $('#REFUND_CHECK_DATE').val();
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'moveToWallet',
                            PK_ENROLLMENT_PAYMENT: PK_ENROLLMENT_PAYMENT,
                            PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                            PK_ENROLLMENT_LEDGER: PK_ENROLLMENT_LEDGER,
                            PK_USER_MASTER: PK_USER_MASTER,
                            BALANCE: BALANCE,
                            REFUND_AMOUNT: REFUND_AMOUNT,
                            ENROLLMENT_TYPE: ENROLLMENT_TYPE,
                            TRANSACTION_TYPE: TRANSACTION_TYPE,
                            PK_PAYMENT_TYPE: PK_PAYMENT_TYPE,
                            REFUND_CHECK_NUMBER: REFUND_CHECK_NUMBER,
                            REFUND_CHECK_DATE: REFUND_CHECK_DATE
                        },
                        success: function(data) {
                            if (data == 1) {
                                window.location.reload();
                            } else {
                                alert(data);
                            }
                        }
                    });
                }
            }
        }
    }











    function editBillingDueDate(param, PK_ENROLLMENT_LEDGER, DUE_DATE, TYPE) {
        $('#PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#old_due_date').val(DUE_DATE);
        $('#due_date').val(DUE_DATE);
        $('#edit_type').val(TYPE);
        $('.trigger_this_enr_details').removeClass('trigger_this_enr_details');
        $(param).closest('.enrollment-container').find('.show_enrollment_details_button').addClass('trigger_this_enr_details');
        $('#billing_due_date_model').modal('show');
    }

    $('#edit_due_date_form').on('submit', function(event) {
        event.preventDefault();

        let PK_ENROLLMENT_LEDGER = $('#PK_ENROLLMENT_LEDGER').val();
        let old_due_date = $('#old_due_date').val();
        let due_date = $('#due_date').val();
        let edit_type = $('#edit_type').val();
        let due_date_verify_password = $('#due_date_verify_password').val();

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: {
                FUNCTION_NAME: 'updateBillingDueDate',
                PK_ENROLLMENT_LEDGER: PK_ENROLLMENT_LEDGER,
                old_due_date: old_due_date,
                due_date: due_date,
                edit_type: edit_type,
                due_date_verify_password: due_date_verify_password
            },
            success: function(data) {
                $('#due_date_verify_password_error').slideUp();
                if (data == 1) {
                    Swal.fire({
                        title: "Updated!",
                        text: "Due Date is Updated.",
                        icon: "success",
                        timer: 3000,
                    }).then((result) => {
                        $('#billing_due_date_model').modal('hide');
                        $('.trigger_this_enr_details').closest('.enrollment_div').find('.enrollment_details').slideToggle();
                        $('.trigger_this_enr_details').click();
                    });
                } else {
                    $('#due_date_verify_password_error').text("Incorrect Password").slideDown();
                }
            }
        });
    });

    function getEditHistory(param, PK_ENROLLMENT_LEDGER, type) {
        $.ajax({
            url: "includes/get_update_history.php",
            type: 'GET',
            data: {
                PK_ENROLLMENT_LEDGER: PK_ENROLLMENT_LEDGER,
                CLASS: type,
                FIELD_NAME: 'DUE_DATE'
            },
            success: function(data) {
                $(param).popover({
                    title: 'Due Date Update Details',
                    placement: 'top',
                    trigger: 'hover',
                    content: data,
                    container: 'body',
                    html: true,
                }).popover('show');
            }
        });
    }

    function deletePayment(PK_ENROLLMENT_PAYMENT, PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BALANCE) {
        Swal.fire({
            title: "Are you sure you want to delete this payment?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deletePayment',
                        PK_ENROLLMENT_PAYMENT: PK_ENROLLMENT_PAYMENT,
                        PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                        PK_ENROLLMENT_LEDGER: PK_ENROLLMENT_LEDGER,
                        BALANCE: BALANCE
                    },
                    success: function(data) {
                        if (data == 1) {
                            window.location.reload();
                        } else {
                            alert(data);
                        }
                    }
                });
            }
        });
    }
</script>

<!-- All function related to appointment -->
<script>
    function editThisAppointment(PK_APPOINTMENT_MASTER, PK_USER, PK_USER_MASTER) {
        $.ajax({
            url: "includes/edit_appointment_details.php",
            type: 'GET',
            data: {
                PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER,
                PK_USER: PK_USER,
                PK_USER_MASTER: PK_USER_MASTER
            },
            success: function(data) {
                $('#edit_appointment_modal').html(data).modal('show');
            }
        });
    }


    function getAppointmentList(type) {
        let PK_USER_MASTER = <?= $PK_USER_MASTER ?>;
        $.ajax({
            url: "partials/ajaxList/customer_appointment.php",
            type: "GET",
            data: {
                master_id: PK_USER_MASTER,
                type: type,
                source: 'customer_page'
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#appointment_area').html(result);
            }
        });
        window.scrollTo(0, 0);
    }
</script>

<!-- All function related to Payment -->
<script>
    function getPaymentRegisterData() {
        let PK_USER_MASTER = <?= $PK_USER_MASTER ?>;

        $.ajax({
            url: "partials/ajaxList/customer_payment.php",
            type: "GET",
            data: {
                master_id: PK_USER_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {

                // Insert HTML
                $('#payment_list').html(result);

                // Destroy existing DataTable if exists
                if ($.fn.DataTable.isDataTable('#paymentRegisterTable')) {
                    $('#paymentRegisterTable').DataTable().clear().destroy();
                    $('#paymentRegisterTable').off();
                }

                // Remove old filters
                $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                    return fn.name !== "dateRangeFilter";
                });

                // Initialize DataTable
                let table = $('#paymentRegisterTable').DataTable({
                    order: [
                        [1, 'desc']
                    ],
                    columnDefs: [{
                        type: 'date',
                        targets: 1
                    }],
                    dom: "<'row mb-2'<'col-md-6 d-flex align-items-center gap-2 mb-3'f<'date-filter'>>" +
                        "<'col-md-6 d-flex justify-content-end align-items-center table-actions mb-3'>>" +
                        "rt" +
                        "<'row mt-2'<'col-md-6'l><'col-md-6 text-end'p>>",

                    language: {
                        search: "", // ✅ removes "Search:" text
                        searchPlaceholder: "Search..." // ✅ placeholder text
                    },

                    buttons: [{
                        extend: 'excelHtml5',
                        text: 'Export to Excel',
                        title: 'Payment Register',
                        exportOptions: {
                            columns: ':visible'
                        }
                    }],

                    ordering: true
                });

                // Add Date Filter beside search
                $("div.date-filter").html(`
                                            <div class="d-flex align-items-center gap-3 ms-4">
                                                <input type="text" id="START_DATE" class="form-control form-control-sm" placeholder="From Date" style="width:200px; height:38px;">
                                                <span>to</span>
                                                <input type="text" id="END_DATE" class="form-control form-control-sm" placeholder="To Date" style="width:200px; height:38px;">
                                            </div>
                                        `);

                $("div.table-actions").html(`
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-toolbar btn-outline-edit" id="btnExport">
                                                    <i class="bi bi-download me-1"></i> Export to Excel
                                                </button>
                                            </div>
                                        `);

                // Initialize Datepickers
                $("#START_DATE").datepicker({
                    numberOfMonths: 1,
                    dateFormat: "yy-mm-dd",
                    onSelect: function(selected) {
                        $("#END_DATE").datepicker("option", "minDate", selected);
                        table.draw();
                    }
                });

                $("#END_DATE").datepicker({
                    numberOfMonths: 1,
                    dateFormat: "yy-mm-dd",
                    onSelect: function(selected) {
                        $("#START_DATE").datepicker("option", "maxDate", selected);
                        table.draw();
                    }
                });

                // Custom Date Range Filter
                function dateRangeFilter(settings, data, dataIndex) {
                    let min = $('#START_DATE').val();
                    let max = $('#END_DATE').val();
                    let date = data[1]; // ✅ correct column index

                    if (!date) return true;

                    let tableDate = new Date(date);

                    if (
                        (min === "" && max === "") ||
                        (min === "" && tableDate <= new Date(max)) ||
                        (new Date(min) <= tableDate && max === "") ||
                        (new Date(min) <= tableDate && tableDate <= new Date(max))
                    ) {
                        return true;
                    }
                    return false;
                }

                dateRangeFilter.name = "dateRangeFilter";
                $.fn.dataTable.ext.search.push(dateRangeFilter);

                // Trigger filtering on input change
                $('#START_DATE, #END_DATE').on('change keyup', function() {
                    table.draw();
                });

                // Export to Excel on button click
                $('#btnExport').on('click', function() {
                    table.button('.buttons-excel').trigger();
                });
            }
        });

        window.scrollTo(0, 0);
    }
</script>

</html>