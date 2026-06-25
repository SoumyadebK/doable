<?php
require_once('../global/config.php');
require_once("../global/stripe-php-master/init.php");

use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;

require_once('../global/authorizenet/autoload.php');

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

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

$interval = $db->Execute("SELECT MIN(TIME_SLOT_INTERVAL) AS TIME_SLOT_INTERVAL FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ")");
if ($interval->fields['TIME_SLOT_INTERVAL'] == "00:00:00") {
    $INTERVAL = "00:15:00";
} else {
    $INTERVAL = $interval->fields['TIME_SLOT_INTERVAL'];
}

if ($PK_USER_MASTER > 0) {
    makeExpiryEnrollmentComplete($PK_USER_MASTER);
    makeMiscComplete($PK_USER_MASTER);
    makeDroppedCancelled($PK_USER_MASTER);
    checkAllEnrollmentStatus($PK_USER_MASTER);
    //markAdhocAppointmentNormal(63572);
    //markEnrollmentComplete(9850);
}

?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php include 'layout/header.php'; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/themify-icons/1.0.1/css/themify-icons.css">

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

    .sidebar-item {
        position: relative;
    }

    .submenu-toggle-arrow {
        display: inline-block;
        transition: transform 0.3s ease;
        transform: rotate(0deg);
    }

    .sidebar-item:hover .submenu-toggle-arrow {
        transform: rotate(90deg);
    }

    .sidebar-submenu {
        position: absolute;
        left: 30px;
        top: 100%;
        width: 90%;
        background-color: #fff;
        border-radius: 8px;
        z-index: 99;
        margin-top: 0px;
        opacity: 0;
        max-height: 0;
        overflow: hidden;
        transition: opacity 1s ease, max-height 1s ease;
        visibility: hidden;
    }

    .sidebar-item:hover .sidebar-submenu,
    .sidebar-submenu:hover {
        opacity: 1;
        max-height: 500px;
        visibility: visible;
    }

    .sidebar-submenu-item {
        display: block;
        padding: 10px 16px;
        color: #6c757d;
        text-decoration: none;
        font-size: 0.92rem;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .sidebar-submenu-item:hover,
    .sidebar-submenu-item.active {
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
        width: 100px;
        height: 100px;
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
        padding: 15px 30px;
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
        line-height: 25px;
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
        margin-bottom: 5px;
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
        padding: 10px 20px;
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
                    <a href="all_customers.php" class="d-flex mb-2 px-3">
                        <i class="bi bi-chevron-left font-12"></i>
                        <h6 style="margin-top: 2px; margin-left: 10px;">Customers</h6>
                    </a>
                    <div class="d-flex justify-content-between align-items-center mb-0 pb-2 border-bottom px-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 50px; height: 50px; font-weight: bold; color: #fff !important; background-color: <?= $customer_color ?> !important;"><?= $customer_initial ?></div>
                            <h3 class="mb-0"><?= $CUSTOMER_NAME ?></h3>
                        </div>
                        <button class="btn btn-outline-danger rounded-pill px-4" onclick="deleteThisCustomer(<?= $PK_USER ?>)">Delete</button>
                    </div>

                    <div class="row">
                        <div class="col-md-2 border-right-light pt-2">
                            <nav class="flex-column left-tabs">
                                <a class="sidebar-link profile-active active" data-toggle-target=".tab-content-1" href="#"><i class="bi bi-grid me-2"></i> Profile</a>
                                <a class="sidebar-link family-active" href="javascript:void(0);" data-toggle-target=".tab-content-2"><i class="bi bi-people me-2"></i> Family</a>
                                <a class="sidebar-link enrollments-active" href="javascript:void(0);" onclick="loadEnrollment('normal')" data-toggle-target=".tab-content-3"><i class="bi bi-journal-text me-2"></i> Enrollments</a>
                                <a class="sidebar-link appointments-active" href="javascript:void(0);" onclick="getAppointmentList('normal')" data-toggle-target=".tab-content-4"><i class="bi bi-clock me-2"></i> Appointments</a>
                                <div class="sidebar-item">
                                    <a class="sidebar-link payments-active" href="javascript:void(0);" onclick="getPaymentRegisterData()" data-toggle-target=".tab-content-5"><i class="bi bi-card-checklist me-2"></i> Payments <i class="bi bi-chevron-right submenu-toggle-arrow ms-2" style="font-size: 0.8rem;"></i></a>
                                    <div class="sidebar-submenu">
                                        <a href="javascript:void(0);" class="sidebar-submenu-item" data-view-type="credit_card" onclick="showPaymentsSubTab('credit_card', this)"><i class="bi bi-credit-card me-2"></i> Credit Card</a>
                                        <a href="javascript:void(0);" class="sidebar-submenu-item" data-view-type="wallet" onclick="showPaymentsSubTab('wallet', this)"><i class="bi bi-wallet me-2"></i> Wallet</a>
                                    </div>
                                </div>
                            </nav>
                        </div>
                        <div class="col-md-10 right-panel">
                            <div class="tab-content tab-content-1 active row profile-section">

                                <div class="col-md-8 pt-4">
                                    <div id="user_edit_information">
                                        <div class="profile-card">

                                            <div class="d-flex justify-content-between border-bottom align-items-center">
                                                <div>
                                                    <div class="section-title">Personal Information</div>
                                                    <div class="section-desc">Optional settings section description</div>
                                                </div>

                                                <div class="d-flex gap-2 align-items-center">
                                                    <a href="javascript:;" class="btn btn-outline-edit" style="height: min-content;" onclick="editPersonalInfo(<?= $PK_USER ?>, <?= $PK_USER_MASTER ?>)">Edit</a>
                                                </div>
                                            </div>

                                            <div class="avatar-placeholder mt-3">
                                                <?php if ($USER_IMAGE != '') { ?>
                                                    <a class="fancybox" href="<?php echo $USER_IMAGE; ?>" data-fancybox-group="gallery">
                                                        <img src="<?php echo $USER_IMAGE; ?>" style="width:100px; height:100px; border: 2px solid #ccc; border-radius: 50%; " />
                                                    </a><?php } else { ?>
                                                    <i class="bi bi-person-fill text-white fs-1"></i>
                                                <?php } ?>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="label">First Name</div>
                                                    <div class="value">
                                                        <?= $FIRST_NAME ?>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="label">Last Name</div>
                                                    <div class="value">
                                                        <?= $LAST_NAME ?>
                                                    </div>
                                                </div>

                                                <div class="col-6">
                                                    <div class="label">Customer ID</div>
                                                    <div class="value">
                                                        <?= $CUSTOMER_ID ?>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="label">Created On</div>
                                                    <div class="value">
                                                        <?= date('m / d / Y', strtotime($CREATED_ON)) ?>
                                                    </div>
                                                </div>

                                                <div class="col-6">
                                                    <div class="label">Primary Location</div>
                                                    <div class="value">
                                                        <?= $PRIMARY_LOCATION_NAME ?>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="label">Preferred Location</div>
                                                    <div class="value">
                                                        <?php
                                                        $selected_location = [];
                                                        $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = " . $PK_USER);
                                                        while (!$selected_location_row->EOF) {
                                                            $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                                            $selected_location_row->MoveNext();
                                                        }

                                                        $location_data = $db->Execute("SELECT * FROM DOA_LOCATION WHERE PK_LOCATION IN (" . implode(",", $selected_location) . ")");
                                                        $location_name_arr = [];
                                                        while (!$location_data->EOF) {
                                                            $location_name_arr[] = $location_data->fields['LOCATION_NAME'];
                                                            $location_data->MoveNext();
                                                        }
                                                        echo implode(", ", $location_name_arr) ?>
                                                    </div>
                                                </div>


                                                <div class="col-6">
                                                    <div class="label">Phone</div>
                                                    <div class="value">
                                                        <?= formatPhone($PHONE) ?>
                                                        <?php
                                                        $customer_phone = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PHONE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                                                        while (!$customer_phone->EOF) {
                                                            echo '<br>' . formatPhone($customer_phone->fields['PHONE']);
                                                            $customer_phone->MoveNext();
                                                        } ?>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="label">Email</div>
                                                    <div class="value">
                                                        <?= $EMAIL_ID ?>
                                                        <?php
                                                        $customer_email = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_EMAIL WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                                                        while (!$customer_email->EOF) {
                                                            echo '<br>' . $customer_email->fields['EMAIL'];
                                                            $customer_email->MoveNext();
                                                        } ?>
                                                    </div>
                                                </div>

                                                <div class="col-6">
                                                    <div class="label">Reminder Options</div>
                                                    <div class="value">
                                                        <?= $REMINDER_OPTION ?>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="label">Tag</div>
                                                    <div class="value">
                                                        <?php
                                                        $selected_tag = [];
                                                        $selected_tag_row = $db_account->Execute("SELECT `PK_TAG` FROM `DOA_USER_TAG` WHERE `PK_USER_MASTER` = " . $PK_USER_MASTER);
                                                        while (!$selected_tag_row->EOF) {
                                                            $selected_tag[] = $selected_tag_row->fields['PK_TAG'];
                                                            $selected_tag_row->MoveNext();
                                                        }

                                                        $tag_data = $db_account->Execute("SELECT * FROM DOA_TAG WHERE PK_TAG IN (" . implode(",", $selected_tag) . ")");
                                                        $tag_name_arr = [];
                                                        while (!$tag_data->EOF) {
                                                            $tag_name_arr[] = $tag_data->fields['TAG_NAME'];
                                                            $tag_data->MoveNext();
                                                        }
                                                        echo implode(", ", $tag_name_arr) ?>
                                                    </div>
                                                </div>

                                                <div class="col-6">
                                                    <div class="label">Gender</div>
                                                    <div class="value">
                                                        <?= $GENDER ?>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="label">Date of Birth</div>
                                                    <div class="value">
                                                        <?= ($DOB == '' || $DOB == '0000-00-00' || $DOB == '1969-12-31') ? '' : date('m / d / Y', strtotime($DOB)) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <div id="user_edit_address">
                                        <div class="profile-card">
                                            <div class="d-flex justify-content-between border-bottom align-items-center">
                                                <div>
                                                    <div class="section-title">Address Information</div>
                                                    <div class="section-desc">Optional settings section description</div>
                                                </div>

                                                <div class="d-flex gap-2 align-items-center">
                                                    <a href="javascript:;" class="btn btn-outline-edit" style="height: min-content;" onclick="editAddress(<?= $PK_USER ?>, <?= $PK_USER_MASTER ?>)">Edit</a>
                                                </div>
                                            </div>

                                            <div class="row mt-3">
                                                <div class="col-6">
                                                    <div class="label">Address</div>
                                                    <div class="value">
                                                        <?= $ADDRESS ?>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="label">Apt/Ste</div>
                                                    <div class="value">
                                                        <?= $ADDRESS_1 ?>
                                                    </div>
                                                </div>

                                                <div class="col-6">
                                                    <div class="label">Country</div>
                                                    <div class="value">
                                                        <?php
                                                        $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE PK_COUNTRY = " . $PK_COUNTRY);
                                                        echo $row->fields['COUNTRY_NAME'];
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="label">State</div>
                                                    <div class="value">
                                                        <?php
                                                        $row = $db->Execute("SELECT PK_STATES, STATE_NAME FROM DOA_STATES WHERE PK_COUNTRY = " . $PK_COUNTRY . " AND PK_STATES = " . $PK_STATES);
                                                        echo $row->fields['STATE_NAME'];
                                                        ?>
                                                    </div>
                                                </div>

                                                <div class="col-6">
                                                    <div class="label">City</div>
                                                    <div class="value">
                                                        <?= $CITY ?>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="label">Postal / Zip Code</div>
                                                    <div class="value">
                                                        <?= $ZIP ?>
                                                    </div>
                                                </div>
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
                                            $customer_special_date = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_SPECIAL_DATE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS' AND SPECIAL_DATE IS NOT NULL AND SPECIAL_DATE != '0000-00-00' AND SPECIAL_DATE != ''");
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
                                                            <a href="javascript:;" onclick="editSpecialDate('<?= $customer_special_date->fields['PK_CUSTOMER_SPECIAL_DATE'] ?>', '<?= $customer_special_date->fields['DATE_NAME'] ?>', '<?= date('m/d/Y', strtotime($customer_special_date->fields['SPECIAL_DATE'])) ?>');"><i class="fa fa-pencil" style="font-size: 20px;"></i></a>
                                                            <a href="javascript:;" onclick="deleteSpecialDate('<?= $customer_special_date->fields['PK_CUSTOMER_SPECIAL_DATE'] ?>');" style="margin-left: 15px;"><i class="fa fa-trash" style="font-size: 20px;"></i></a>
                                                        </div>
                                                    </div>
                                            <?php $customer_special_date->MoveNext();
                                                }
                                            } ?>
                                            <div class="mt-3">
                                                <a href="javascript:;" onclick="addSpecialDate();" class="add-btn"><i class="bi bi-plus"></i> Add New</a>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="partner_information">
                                        <div class="profile-card">
                                            <div class="border-bottom pb-3">
                                                <div class="row">
                                                    <div class="col-6 section-title">Will you be attending your lessons</div>

                                                    <div class="col-2 section-title">
                                                        <label><input type="radio" name="ATTENDING_WITH" class="form-check-inline" onclick="handleAttendingWithChange(this)" value="Solo" <?= (($ATTENDING_WITH == '') ? 'checked' : (($ATTENDING_WITH == 'Solo') ? 'checked' : '')) ?>> Solo</label>
                                                    </div>
                                                    <div class="col-3 section-title">
                                                        <label><input type="radio" name="ATTENDING_WITH" class="form-check-inline" onclick="handleAttendingWithChange(this)" value="With a Partner" <?= (($ATTENDING_WITH == 'With a Partner') ? 'checked' : '') ?>> With a Partner</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mt-3" id="partner_details" style="display: <?= (($ATTENDING_WITH == 'With a Partner') ? '' : 'none') ?>;">
                                                <div class="col-12">
                                                    <?php if (!empty($PARTNER_FIRST_NAME) || !empty($PARTNER_LAST_NAME) || !empty($PARTNER_PHONE) || !empty($PARTNER_EMAIL)) { ?>
                                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                                            <div>
                                                                <strong><?= $PARTNER_FIRST_NAME ?> <?= $PARTNER_LAST_NAME ?></strong>
                                                                <?php if (!empty($PARTNER_PHONE) || !empty($PARTNER_EMAIL) || !empty($PARTNER_GENDER) || !empty($PARTNER_DOB)) { ?>
                                                                    <div class="text-muted small mt-1">
                                                                        <?php if (!empty($PARTNER_PHONE)) { ?>
                                                                            📞 <?= formatPhone($PARTNER_PHONE) ?>
                                                                        <?php } ?>
                                                                        <?php if (!empty($PARTNER_GENDER)) { ?>
                                                                            <?= (!empty($PARTNER_PHONE) ? ' | ' : '') . $PARTNER_GENDER ?>
                                                                        <?php } ?>
                                                                        <?php if (!empty($PARTNER_DOB) && $PARTNER_DOB != '0000-00-00' && $PARTNER_DOB != '1969-12-31') { ?>
                                                                            <?= (!empty($PARTNER_PHONE) || !empty($PARTNER_GENDER) ? ' | ' : '') . '🎂 ' . date('m/d/Y', strtotime($PARTNER_DOB)) ?>
                                                                        <?php } ?>
                                                                        <?php if (!empty($PARTNER_EMAIL)) { ?>
                                                                            <?= (!empty($PARTNER_PHONE) || !empty($PARTNER_GENDER) || (!empty($PARTNER_DOB) && $PARTNER_DOB != '0000-00-00' && $PARTNER_DOB != '1969-12-31') ? ' | ' : '') . '✉️ ' . $PARTNER_EMAIL ?>
                                                                        <?php } ?>
                                                                    </div>
                                                                <?php } ?>
                                                            </div>
                                                            <div>
                                                                <a href="javascript:;" class="btn btn-sm btn-outline-edit" onclick="openPartnerModal()">
                                                                    <i class="bi bi-pencil"></i> Edit
                                                                </a>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="text-center p-3 text-muted">
                                                            No partner information added yet.
                                                            <a href="javascript:;" onclick="openPartnerModal()">Add partner</a>
                                                        </div>
                                                    <?php } ?>
                                                </div>
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
                                        <div class="mt-3" style="max-height: 300px; overflow-y: auto;">
                                            <?php
                                            if (!empty($_GET['id'])) {
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
                                                            <a href="javascript:;" onclick="deleteCustomerDocument('<?= $row->fields['PK_CUSTOMER_DOCUMENT'] ?>');">
                                                                <i class="fa fa-trash" style="font-size: 20px;"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php $row->MoveNext();
                                                } ?>
                                            <?php } ?>

                                            <?php
                                            $res = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_USER_MASTER` = " . $PK_USER_MASTER);

                                            // Convert the relative path to an absolute filesystem path
                                            $filesystem_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $upload_path . '/enrollment_pdf/';

                                            while (!$res->EOF) {
                                                $file_path = $filesystem_path . $res->fields['AGREEMENT_PDF_LINK'];
                                                if ($res->fields['AGREEMENT_PDF_LINK'] != NULL && $res->fields['AGREEMENT_PDF_LINK'] != '') { ?>
                                                    <div class="d-flex justify-content-between align-items-center py-2 mt-3" style="width: 95%; margin-left: auto; margin-right: auto;">
                                                        <div class="d-flex align-items-center">
                                                            <?php if (file_exists($file_path)) { ?>
                                                                <div class="bg-danger-subtle p-2 rounded me-3">
                                                                    <i class="bi bi-file-earmark-pdf-fill text-danger fs-5"></i>
                                                                </div>
                                                                <div>
                                                                    <a href="../<?= $upload_path ?>/enrollment_pdf/<?= $res->fields['AGREEMENT_PDF_LINK'] ?>" target="_blank">
                                                                        <div class="fw-semibold mb-0" style="font-size: 0.9rem;"><?= $res->fields['ENROLLMENT_ID'] ?> (View Agreement)</div>
                                                                    </a>
                                                                </div>
                                                            <?php } else { ?>
                                                                <div class="bg-light p-2 rounded me-3">
                                                                    <i class="bi bi-file-earmark-pdf-fill text-muted fs-5"></i>
                                                                </div>
                                                                <div>
                                                                    <a href="javascript:">
                                                                        <div class="fw-semibold mb-0" style="font-size: 0.9rem;"><?= $res->fields['ENROLLMENT_ID'] ?> (Not Available)</div>
                                                                    </a>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                            <?php }
                                                $res->MoveNext();
                                            } ?>
                                        </div>

                                        <div class="mt-3">
                                            <a href="javascript:;" onclick="addCustomerDocument();" class="add-btn"><i class="bi bi-plus"></i> Add New</a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 pt-4">
                                    <div class="profile-card">
                                        <div class="section-title border-bottom pb-2">Internal Notes</div>
                                        <div class="mt-3" style="max-height: 600px; overflow-y: auto;" id="customer_comments_area">
                                            <?php
                                            $comment_data = $db->Execute("SELECT $account_database.DOA_COMMENT.PK_COMMENT, $account_database.DOA_COMMENT.COMMENT, $account_database.DOA_COMMENT.COMMENT_DATE, $account_database.DOA_COMMENT.ACTIVE, CONCAT($master_database.DOA_USERS.FIRST_NAME, ' ', $master_database.DOA_USERS.LAST_NAME) AS FULL_NAME FROM $account_database.`DOA_COMMENT` INNER JOIN $master_database.DOA_USERS ON $account_database.DOA_COMMENT.BY_PK_USER = $master_database.DOA_USERS.PK_USER WHERE $account_database.DOA_COMMENT.`FOR_PK_USER` = " . $PK_USER . " ORDER BY $account_database.DOA_COMMENT.COMMENT_DATE DESC");
                                            $i = 1;
                                            while (!$comment_data->EOF) { ?>
                                                <div class="internal-note">
                                                    <div class="d-flex justify-content-between">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <strong><?= $comment_data->fields['FULL_NAME'] ?></strong>
                                                            <a href="javascript:;" onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><i class="fa fa-pencil" style="font-size: 16px; margin-left: 10px;"></i></a>
                                                            <a href="javascript:;" onclick="deleteComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><i class="fa fa-trash" style="font-size: 16px;"></i></a>
                                                        </div>
                                                        <small class="text-muted"><?= date('m/d/Y', strtotime($comment_data->fields['COMMENT_DATE'])) ?></small>
                                                    </div>

                                                    <p class="mb-0"><?= $comment_data->fields['COMMENT'] ?></p>
                                                </div>
                                            <?php $comment_data->MoveNext();
                                                $i++;
                                            } ?>
                                        </div>
                                        <a href="javascript:;" onclick="createUserComment();" class="add-btn"><i class="bi bi-plus"></i> Add New</a>
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
                                                                <span><?= formatPhone($family_member_details->fields['PHONE']) ?> <i class="bi bi-copy copy-icon"></i></span>
                                                            </div>
                                                        </div>
                                                        <div class="gap-2 d-flex">
                                                            <a href="javascript:;"><i class="fa fa-pencil" style="font-size: 20px;"></i></a>
                                                            <a href="javascript:;" onclick="deleteFamilyMember('<?= $family_member_details->fields['PK_CUSTOMER_DETAILS'] ?>');"><i class="fa fa-trash" style="font-size: 20px;"></i></a>
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

                                        <a href="javascript:void(0)" onclick="addNewFamilyMember()" class="add-family-btn mt-2">
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

                            <div class="tab-content tab-content-6 row payments-section" id="credit_card" role="tabpanel">
                                <div class="col-md-12 px-3 pt-4 pb-4">
                                    <div class="payments-card">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="fw-bold mb-1">Credit Card
                                                    <div id="credit_card_loader" class="spinner-border text-success" role="status" style="width: 20px; height: 20px;">
                                                        <span class=" sr-only">Loading...</span>
                                                    </div>
                                                </h5>
                                                <p class="text-muted small mb-0">Optional settings section description</p>
                                            </div>
                                            <button class="btn btn-light btn-sm btn-outline-edit border text-muted px-3 py-2" style="border-radius: 8px;" onclick="addCreditCard()">
                                                <i class="bi bi-plus"></i> Add Credit Card
                                            </button>
                                        </div>

                                        <?php if ($PAYMENT_GATEWAY == null || $PAYMENT_GATEWAY == '') { ?>
                                            <div class="alert alert-danger">
                                                Payment Gateway is Not set Yet
                                            </div>
                                        <?php } else { ?>
                                            <div class="row mt-4">
                                                <div class="col-md-12">
                                                    <div id="add_credit_card_div" style="display: none;">

                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mt-4" id="saved_credit_card_list" style="display: none;">

                                            </div>
                                        <?php } ?>
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

<!--Special Date Model-->
<div class="modal fade" id="special_date_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4><b id="special_date_header">Add Special Date</b></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="$('#special_date_modal').modal('hide');"></button>
            </div>
            <form id="special_date_add_edit_form" role="form" action="" method="post">
                <div class="modal-body">
                    <input type="hidden" name="FUNCTION_NAME" value="saveSpecialDateData">
                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                    <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?= $PK_CUSTOMER_DETAILS ?>">
                    <input type="hidden" name="PK_CUSTOMER_SPECIAL_DATE" id="PK_CUSTOMER_SPECIAL_DATE" value="0">
                    <div class="p-20">
                        <div class="form-group">
                            <label class="form-label">Date Title</label>
                            <input class="form-control" rows="10" name="CUSTOMER_SPECIAL_DATE_NAME" id="CUSTOMER_SPECIAL_DATE_NAME" required>
                        </div>
                        <div class="form-group mt-3">
                            <label class="form-label">Special Date</label>
                            <input class="form-control datepicker-normal" rows="10" name="CUSTOMER_SPECIAL_DATE" id="CUSTOMER_SPECIAL_DATE" required>
                        </div>
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

<!--Partner Modal-->
<div class="modal fade" id="partner_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4><b id="partner_header">Partner Information</b></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="$('#partner_modal').modal('hide');"></button>
            </div>
            <form id="partner_add_edit_form" role="form" action="" method="post">
                <div class="modal-body">
                    <input type="hidden" name="FUNCTION_NAME" value="savePartnerData">
                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                    <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?= $PK_USER_MASTER ?>">
                    <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?= $PK_CUSTOMER_DETAILS ?>">
                    <div class="p-20">
                        <div class="form-group">
                            <label class="form-label">Partner's First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Partner's First Name" name="PARTNER_FIRST_NAME" id="PARTNER_FIRST_NAME_MODAL" value="<?= $PARTNER_FIRST_NAME ?>" required>
                        </div>
                        <div class="form-group mt-3">
                            <label class="form-label">Partner's Last Name</label>
                            <input type="text" class="form-control" placeholder="Enter Partner's Last Name" name="PARTNER_LAST_NAME" id="PARTNER_LAST_NAME_MODAL" value="<?= $PARTNER_LAST_NAME ?>">
                        </div>
                        <div class="form-group mt-3">
                            <label class="form-label">Partner's Phone</label>
                            <input type="text" class="form-control format_phone_number" placeholder="Enter Partner's Phone" name="PARTNER_PHONE" id="PARTNER_PHONE_MODAL" value="<?= $PARTNER_PHONE ?>">
                        </div>
                        <div class="form-group mt-3">
                            <label class="form-label">Partner's Email</label>
                            <input type="email" class="form-control" placeholder="Enter Partner's Email" name="PARTNER_EMAIL" id="PARTNER_EMAIL_MODAL" value="<?= $PARTNER_EMAIL ?>">
                        </div>
                        <div class="form-group mt-3">
                            <label class="form-label">Partner's Gender</label>
                            <select class="form-control" name="PARTNER_GENDER" id="PARTNER_GENDER_MODAL">
                                <option value="">Select Gender</option>
                                <option value="Male" <?= (($PARTNER_GENDER == 'Male') ? 'selected' : '') ?>>Male</option>
                                <option value="Female" <?= (($PARTNER_GENDER == 'Female') ? 'selected' : '') ?>>Female</option>
                                <option value="Other" <?= (($PARTNER_GENDER == 'Other') ? 'selected' : '') ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group mt-3">
                            <label class="form-label">Partner's Date of Birth</label>
                            <input type="text" class="form-control datepicker-past" name="PARTNER_DOB" id="PARTNER_DOB_MODAL" value="<?= ($PARTNER_DOB == '' || $PARTNER_DOB == '0000-00-00' || $PARTNER_DOB == '1969-12-31') ? '' : date('m/d/Y', strtotime($PARTNER_DOB)) ?>">
                        </div>
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

<!--Document Model-->
<div class="modal fade" id="document_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4><b id="document_header">Add Document</b></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="$('#document_modal').modal('hide');"></button>
            </div>
            <form id="document_add_edit_form" role="form" action="" method="post">
                <div class="modal-body">
                    <input type="hidden" name="FUNCTION_NAME" value="saveDocumentData">
                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                    <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?= $PK_USER_MASTER ?>">
                    <input type="hidden" name="PK_CUSTOMER_DOCUMENT" id="PK_CUSTOMER_DOCUMENT" value="0">
                    <div class="p-20">
                        <div class="form-group">
                            <label class="form-label">Document Name</label>
                            <input type="text" class="form-control" rows="10" name="DOCUMENT_NAME[]" id="DOCUMENT_NAME" required>
                        </div>
                        <div class="form-group mt-3">
                            <label class="form-label">Document File</label>
                            <input type="file" class="form-control" rows="10" name="FILE_PATH[]" id="FILE_PATH" required>
                        </div>
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


<!--Family Member Model-->
<div class="modal fade" id="family_member_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4><b id="family_member_header">Add Family Member</b></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="$('#family_member_modal').modal('hide');"></button>
            </div>
            <form id="family_member_add_edit_form" role="form" action="" method="post">
                <div class="modal-body">
                    <input type="hidden" name="FUNCTION_NAME" value="saveFamilyMemberData">
                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                    <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?= $PK_USER_MASTER ?>">
                    <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?= $PK_CUSTOMER_DETAILS ?>">
                    <div class="p-20">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="FAMILY_FIRST_NAME" id="FAMILY_FIRST_NAME" required>
                        </div>
                        <div class="form-group mt-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="FAMILY_LAST_NAME" id="FAMILY_LAST_NAME" required>
                        </div>
                        <div class="form-group mt-3">
                            <label class="form-label">Relationship</label>
                            <select class="form-control" name="PK_RELATIONSHIP" id="PK_RELATIONSHIP" required>
                                <option>Select Relationship</option>
                                <?php
                                $row = $db->Execute("SELECT * FROM DOA_RELATIONSHIP WHERE ACTIVE = 1");
                                while (!$row->EOF) { ?>
                                    <option value="<?php echo $row->fields['PK_RELATIONSHIP']; ?>"><?= $row->fields['RELATIONSHIP'] ?></option>
                                <?php $row->MoveNext();
                                } ?>
                            </select>
                        </div>
                        <div class="form-group mt-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="FAMILY_EMAIL" id="FAMILY_EMAIL">
                        </div>
                        <div class="form-group mt-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control format_phone_number" name="FAMILY_PHONE" id="FAMILY_PHONE">
                        </div>
                        <!-- <div class="form-group mt-3">
                            <label class="form-label">Gender</label>
                            <select class="form-control" name="FAMILY_GENDER" id="FAMILY_GENDER">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
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
                <button type="button" class="btn btn-secondary cancel" data-bs-dismiss="modal" onclick="$('#credit_card_modal').modal('hide');">Close</button>
                <button type="button" class="btn btn-secondary" style="float: right;" onclick="addEnrollmentAutoPayCreditCard()">Process</button>
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
                                <input type="text" id="due_date" name="due_date" class="form-control datepicker-normal" placeholder="Due Date" autocomplete="off" required>
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

<!--Refund Modal-->
<div class="modal fade" id="refund_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 500px;">
        <form class="p-20" id="refund_form">
            <input type="hidden" name="PK_ENROLLMENT_LEDGER" class="PK_ENROLLMENT_LEDGER">
            <input type="hidden" name="PK_ENROLLMENT_PAYMENT" class="PK_ENROLLMENT_PAYMENT">
            <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER">
            <input type="hidden" name="PK_USER_MASTER" class="PK_USER_MASTER">
            <input type="hidden" name="TOTAL_NEGATIVE_BALANCE" value="0">
            <input type="hidden" name="CANCEL_FUTURE_APPOINTMENT" value="0">
            <input type="hidden" name="SOURCE" value="REFUND_MODAL">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group  mb-4">
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

                            <div class="row  mb-4 check_payment" style="display: none;">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="form-label">Check Number</label>
                                        <div class="col-md-12">
                                            <input type="text" name="REFUND_CHECK_NUMBER" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="form-label">Check Date</label>
                                        <div class="col-md-12">
                                            <input type="text" name="REFUND_CHECK_DATE" class="form-control datepicker-normal">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="REFUND_AMOUNT">How much refund you want?</label>
                                <div class="col-md-12">
                                    <input class="form-control" name="TOTAL_POSITIVE_BALANCE" id="REFUND_AMOUNT" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="SUBMIT" value="Submit">
                    <button type="submit" class="btn btn-secondary" style="float: right;">Process</button>
                </div>
            </div>
        </form>
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

<!-- Cancel enrollment modal -->
<div class="modal fade" id="enrollment_cancel_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 700px !important;">
        <form class="p-20" id="cancel_enrollment_form">
            <input type="hidden" name="SOURCE" value="CANCEL_MODAL">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><b>Cancel Enrollment</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-body">

                            <div id="step_1">
                                <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER">
                                <input type="hidden" name="PK_USER_MASTER" class="PK_USER_MASTER">
                                <div class="form-group mb-4">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>Cancel All Future Appointments for <span class="enrollment_title"></span>? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" id="CANCEL_FUTURE_APPOINTMENT_1" value="1" checked /></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mb-4">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>Cancel Only Unpaid Future Appointments for <span class="enrollment_title"></span>? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" id="CANCEL_FUTURE_APPOINTMENT_2" value="2" /></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mb-4">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>Move Future Appointments As Ad-Hoc for <span class="enrollment_title"></span>? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" id="CANCEL_FUTURE_APPOINTMENT_3" value="3" /></label>
                                        </div>
                                    </div>
                                </div>
                                <a href="javascript:" class="btn btn-secondary" style="float: right;" onclick="$('#step_1').hide();$('#step_2').show();">Continue</a>
                            </div>

                            <div id="step_2" style="display: none;">
                                <div class="form-group mb-4">
                                    <div class="row">
                                        <div class="col-md-10">
                                            <label>Use available credits to pay pending balances?</label>
                                        </div>
                                        <div class="col-md-2">
                                            <label><input type="radio" name="USE_AVAILABLE_CREDIT" value="1" checked />&nbsp;Yes</label>&nbsp;&nbsp;
                                            <!--<label><input type="radio" name="USE_AVAILABLE_CREDIT" value="0"/>&nbsp;No</label>-->
                                        </div>
                                    </div>
                                </div>
                                <a href="javascript:" class="btn btn-secondary  next" style="float: right;" onclick="$('#step_2').hide();$('#step_3').show();showEnrollmentServiceDetails();">Continue</a>
                                <a href="javascript:" class="btn btn-secondary cancel prev" style="*float: right;" onclick="$('#step_2').hide();$('#step_1').show();">Go Back</a>
                            </div>

                            <div id="step_3" style="display: none;">
                                <div id="enrollment_service_details">

                                </div>
                                <!-- <div class="form-group mb-2 negative_balance_div" style="display: none;">
                                    <label class="form-label">How you want to your pay?</label>
                                    <div class="col-md-8">
                                        <select class="form-control" name="PK_PAYMENT_TYPE" id="PK_PAYMENT_TYPE" onchange="selectRefundType(this)">
                                            <option value="">Select</option>
                                            <?php
                                            $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
                                            while (!$row->EOF) { ?>
                                                <option value="<?php echo $row->fields['PK_PAYMENT_TYPE']; ?>"><?= $row->fields['PAYMENT_TYPE'] ?></option>
                                            <?php $row->MoveNext();
                                            } ?>
                                        </select>
                                    </div>
                                </div> -->
                                <div class="form-group mb-4 negative_balance_div" style="display: none;">
                                    <div class="row">
                                        <b>Note: Please pay $<span id="total_negative_balance"></span> to cancel your enrollment.</b>
                                    </div>
                                </div>

                                <div class="form-group mb-2 credit_balance_div" style="display: none;">
                                    <label class="form-label">Refund Method?</label>
                                    <div class="col-md-8">
                                        <select class="form-control" name="PK_PAYMENT_TYPE_REFUND" id="PK_PAYMENT_TYPE_REFUND" onchange="selectRefundType(this)">
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
                                <div class="form-group mb-4 credit_balance_div" style="display: none;">
                                    <div class="row">
                                        <b>Note: Credit balance $<span id="total_credit_balance"></span> will be moved to Wallet.</b>
                                    </div>
                                </div>

                                <div class="row mb-4 check_payment" style="display: none;">
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

                                <input type="hidden" name="SUBMIT" id="SUBMIT">
                                <button type="submit" class="btn btn-secondary" id="cancel_and_store_btn" onclick="$('#SUBMIT').val('Cancel and Store Info only');" style="float: right;">Cancel and Store Info only <span><i class="fa fa-info-circle"></i></span></button>
                                <button type="submit" class="btn btn-secondary" onclick="$('#SUBMIT').val('Submit');" style="float: right; margin-right: 5px;">Submit</button>
                                <a href="javascript:" class="btn btn-secondary" onclick="$('#step_3').hide();$('#step_2').show();">Go Back</a>

                            </div>

                        </div>
                    </div>
                </div>
                <!--<div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Submit</button>
                </div>-->
            </div>
        </form>
    </div>
</div>

<!-- Delete Enrollment Modal -->
<div class="modal fade" id="delete_enrollment_model" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="delete_enrollment_form" method="post">
            <input type="hidden" name="FUNCTION_NAME" value="deleteActiveEnrollmentData">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><b>Delete Enrollment</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <input type="hidden" name="PK_ENROLLMENT_MASTER" id="DELETE_ENROLLMENT_ID">
                <div class="modal-body">
                    <div class="row p-20">
                        <div>
                            <label><input type="radio" id="delete_type_1" name="delete_type" value="1" checked>&nbsp;&nbsp;&nbsp;Delete All Appointment</label><br><br>
                            <label><input type="radio" id="delete_type_0" name="delete_type" value="0">&nbsp;&nbsp;&nbsp;Move Appointment to Ad-Hoc</label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="card-button" class="btn btn-secondary" style="float: right;">Process</button>
                </div>
            </div>
        </form>
    </div>
</div>



<!--Refund Money From Wallet Modal-->
<div class="modal fade" id="refund_from_wallet_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 500px;">
        <form class="p-20" id="refund_from_wallet">
            <input type="hidden" name="PK_CUSTOMER_WALLET" id="PK_CUSTOMER_WALLET">
            <input type="hidden" name="ORIGINAL_REFUND_AMOUNT" id="ORIGINAL_REFUND_AMOUNT">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group  mb-4">
                                <label class="form-label">How you want your money back?</label>
                                <div class="col-md-12">
                                    <select class="form-control" required name="PK_PAYMENT_TYPE_WALLET_REFUND" id="PK_PAYMENT_TYPE_WALLET_REFUND" onchange="selectRefundType(this)">
                                        <option value="">Select</option>
                                        <?php
                                        $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE PK_PAYMENT_TYPE != 7 AND ACTIVE = 1");
                                        while (!$row->EOF) { ?>
                                            <option value="<?php echo $row->fields['PK_PAYMENT_TYPE']; ?>"><?= $row->fields['PAYMENT_TYPE'] ?></option>
                                        <?php $row->MoveNext();
                                        } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row  mb-4 check_payment" style="display: none;">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="form-label">Check Number</label>
                                        <div class="col-md-12">
                                            <input type="text" name="REFUND_CHECK_NUMBER" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="form-label">Check Date</label>
                                        <div class="col-md-12">
                                            <input type="text" name="REFUND_CHECK_DATE" class="form-control datepicker-normal">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="WALLET_REFUND_AMOUNT">How much refund you want?</label>
                                <div class="col-md-12">
                                    <input class="form-control" name="WALLET_REFUND_AMOUNT" id="WALLET_REFUND_AMOUNT" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="SUBMIT" value="Submit">
                    <button type="submit" class="btn btn-secondary" style="float: right;">Process</button>
                </div>
            </div>
        </form>
    </div>
</div>


<?php require_once('../includes/footer.php'); ?>

<?php include 'partials/create_appointment_modal.php'; ?>

<?php include 'partials/create_enrollment_modal.php'; ?>

<!--Payment Model-->
<?php include('includes/enrollment_payment_v2.php'); ?>

<!--Wallet Payment-->
<?php include('includes/add_money_to_wallet.php'); ?>

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
    $(document).ready(function() {
        $(".btn-available").click(function() {
            $(this).toggleClass("active");
            $(".slot_div").toggle();
        });

        $('#openDrawer').click(function() {
            $('#sideDrawer, .overlay').addClass('active');
            $('.group_class_tab').show();
            $('.to_do_tab').show();
            $('#slot_time').val('');
        });

        $('#closeDrawer, .overlay').click(function() {
            $('#sideDrawer, .overlay').removeClass('active');
        });

        $('#closeDrawer4, .overlay').click(function() {
            $('#sideDrawer4, .overlay').removeClass('active');
        });
    });


    $(document).ready(function() {
        var hash = window.location.hash;
        if (hash) {
            var target = '';
            var callFunction = null;
            if (hash === '#profile') target = '.tab-content-1';
            else if (hash === '#family') target = '.tab-content-2';
            else if (hash === '#enrollments') {
                target = '.tab-content-3';
                callFunction = function() {
                    loadEnrollment('normal');
                };
            } else if (hash === '#appointments') {
                target = '.tab-content-4';
                callFunction = function() {
                    getAppointmentList('normal');
                };
            } else if (hash === '#payment_register') {
                target = '.tab-content-5';
                callFunction = function() {
                    getPaymentRegisterData();
                };
            } else if (hash === '#wallet_payments') {
                target = '.tab-content-5';
                callFunction = function() {
                    getWalletDetails();
                };
            } else if (hash === '#credit_card') {
                target = '.tab-content-6';
                callFunction = function() {
                    getSavedCreditCardList();
                };
            }

            if (target) {
                $('.sidebar-link').removeClass('active');
                $('.sidebar-link[data-toggle-target="' + target + '"]').addClass('active');
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
                if (callFunction) callFunction();
            }
        }
    });

    $('.sidebar-link').on('click', function(evt) {
        evt.preventDefault();

        // 1. Manage Sidebar Links: Remove active from all, add to the clicked one
        $('.sidebar-link').removeClass('active');
        $(this).addClass('active');

        // 2. Manage Tab Content: Hide all, then show the one matching the data-attribute
        var sel = $(this).data('toggle-target'); // Using .data() is cleaner jQuery style
        $('.tab-content').removeClass('active');
        $(sel).addClass('active');

        // 3. Set hash for URL
        var hash = '';
        if (sel === '.tab-content-1') hash = '#profile';
        else if (sel === '.tab-content-2') hash = '#family';
        else if (sel === '.tab-content-3') hash = '#enrollments';
        else if (sel === '.tab-content-4') hash = '#appointments';
        //else if (sel === '.tab-content-5') hash = '#payments';
        if (hash) window.location.hash = hash;
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


<!-- All function related to customer details edit -->
<script>
    function formatPhoneNumber(input) {
        let digits = input.value.replace(/\D/g, '');
        if (digits.length > 10) {
            digits = digits.slice(0, 10);
        }
        let formatted = digits;

        if (digits.length <= 3) {
            formatted = digits;
        } else if (digits.length <= 6) {
            formatted = `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
        } else {
            formatted = `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
        }

        input.value = formatted;
    }

    $(document).on('input', '.format_phone_number', function() {
        formatPhoneNumber(this);
    });

    function editPersonalInfo(PK_USER, PK_USER_MASTER) {
        $.ajax({
            url: "partials/edit_customer_details.php",
            type: 'POST',
            data: {
                PK_USER: PK_USER,
                PK_USER_MASTER: PK_USER_MASTER
            },
            success: function(data) {
                $('#user_edit_information').html(data);
            }
        });
    }

    function editAddress(PK_USER, PK_USER_MASTER) {
        $.ajax({
            url: "partials/edit_customer_address.php",
            type: 'POST',
            data: {
                PK_USER: PK_USER,
                PK_USER_MASTER: PK_USER_MASTER
            },
            success: function(data) {
                $('#user_edit_address').html(data);
            }
        });
    }

    function editPartner(PK_USER, PK_USER_MASTER) {
        openPartnerModal();
    }
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

<!-- All function related to special date -->
<script>
    function openSpecialDateModal() {
        $('#special_date_modal').modal('show');
    }

    function addSpecialDate() {
        $('#special_date_header').text("Add Comment");
        $('#PK_CUSTOMER_SPECIAL_DATE').val(0);
        $('#CUSTOMER_SPECIAL_DATE_NAME').val('');
        $('#CUSTOMER_SPECIAL_DATE').val('');
        openSpecialDateModal();
    }

    function editSpecialDate(PK_CUSTOMER_SPECIAL_DATE, CUSTOMER_SPECIAL_DATE_NAME, CUSTOMER_SPECIAL_DATE) {
        $('#special_date_header').text("Edit Comment");
        $('#PK_CUSTOMER_SPECIAL_DATE').val(PK_CUSTOMER_SPECIAL_DATE);
        $('#CUSTOMER_SPECIAL_DATE_NAME').val(CUSTOMER_SPECIAL_DATE_NAME);
        $('#CUSTOMER_SPECIAL_DATE').val(CUSTOMER_SPECIAL_DATE);
        openSpecialDateModal();
    }

    $(document).on('submit', '#special_date_add_edit_form', function(event) {
        event.preventDefault();
        let form_data = new FormData($('#special_date_add_edit_form')[0]); //$('#document_form').serialize();
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

    function deleteSpecialDate(PK_CUSTOMER_SPECIAL_DATE) {
        Swal.fire({
            title: "Are you sure?",
            text: "Deleting this date cannot be undone.",
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
                        FUNCTION_NAME: 'deleteSpecialDate',
                        PK_CUSTOMER_SPECIAL_DATE: PK_CUSTOMER_SPECIAL_DATE
                    },
                    success: function(data) {
                        window.location.reload();
                    }
                });
            }
        });
    }
</script>

<!-- All function related to document -->
<script>
    function openDocumentAddModal() {
        $('#document_modal').modal('show');
    }

    function addCustomerDocument() {
        $('#document_header').text("Add Document");
        $('#PK_CUSTOMER_DOCUMENT').val(0);
        $('#DOCUMENT_NAME').val('');
        $('#FILE_PATH').val('');
        openDocumentAddModal();
    }

    $(document).on('submit', '#document_add_edit_form', function(event) {
        event.preventDefault();
        let form_data = new FormData($('#document_add_edit_form')[0]); //$('#document_form').serialize();
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

    function deleteCustomerDocument(PK_CUSTOMER_DOCUMENT) {
        Swal.fire({
            title: "Are you sure?",
            text: "Deleting this document cannot be undone.",
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
                        FUNCTION_NAME: 'deleteCustomerDocument',
                        PK_CUSTOMER_DOCUMENT: PK_CUSTOMER_DOCUMENT
                    },
                    success: function(data) {
                        window.location.reload();
                    }
                });
            }
        });
    }
</script>

<!-- All function related to comment -->
<script>
    function openCommentModel() {
        $('#comment_modal').modal('show');
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

<!-- All function related to Family Member -->
<script>
    function openFamilyMemberModel() {
        $('#family_member_modal').modal('show');
    }

    function addNewFamilyMember() {
        $('#family_member_header').text("Add Family Member");
        $('#FAMILY_FIRST_NAME').val('');
        $('#FAMILY_LAST_NAME').val('');
        $('#FAMILY_EMAIL').val('');
        $('#FAMILY_PHONE').val('');
        $('#PK_RELATIONSHIP').val('');
        openFamilyMemberModel();
    }

    function editFamilyMember(PK_FAMILY_MEMBER) {
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            dataType: 'JSON',
            data: {
                FUNCTION_NAME: 'getEditFamilyMemberData',
                PK_FAMILY_MEMBER: PK_FAMILY_MEMBER
            },
            success: function(data) {
                $('#family_member_header').text("Edit Family Member");
                $('#PK_FAMILY_MEMBER').val(data.fields.PK_FAMILY_MEMBER);
                $('#FAMILY_MEMBER_NAME').val(data.fields.FAMILY_MEMBER_NAME);
                $('#family_member_active').show();
                openFamilyMemberModel();
            }
        });
    }

    $(document).on('submit', '#family_member_add_edit_form', function(event) {
        event.preventDefault();
        let form_data = new FormData($('#family_member_add_edit_form')[0]); //$('#document_form').serialize();
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

    function deleteFamilyMember(PK_CUSTOMER_DETAILS) {
        Swal.fire({
            title: "Are you sure?",
            text: "Deleting this family member cannot be undone.",
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
                        FUNCTION_NAME: 'deleteFamilyMemberData',
                        PK_CUSTOMER_DETAILS: PK_CUSTOMER_DETAILS
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
    function createCustomerEnrollment() {
        let PK_USER_MASTER = '<?= $PK_USER_MASTER ?>';
        $('#sideDrawer4, .overlay4').addClass('active');
        $('#enrollment_form #PK_USER_MASTER').SumoSelect();
        $('#enrollment_form #PK_USER_MASTER').val(PK_USER_MASTER);
        $('#enrollment_form #PK_USER_MASTER')[0].sumo.reload();
        $('#enrollment_form #PK_USER_MASTER').trigger('change');
    }

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

    /* function showEnrollmentDetails(param, PK_USER, PK_USER_MASTER, PK_ENROLLMENT_MASTER, ENROLLMENT_ID, type, details) {
        $(param).html(`<span class="d-flex align-items-center gap-2">
                            View Payment Schedule
                            <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                        </span>`);
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
            cache: false,
            success: function(result) {
                $(param).closest('.enrollment_div').find('.enrollment_details').html(result).slideToggle();
                $(param).html(`View Payment Schedule`);
            }
        });
    } */

    function showEnrollmentDetails(param, PK_USER, PK_USER_MASTER, PK_ENROLLMENT_MASTER, ENROLLMENT_ID, type, details) {
        let enrollmentDetails = $(param).closest('.enrollment_div').find('.enrollment_details');

        // Check if table already loaded
        if (enrollmentDetails.find('#myTable').length > 0) {
            enrollmentDetails.slideToggle();
            return;
        }

        // Show loader
        $(param).html(`<span class="d-flex align-items-center gap-2">
                        View Payment Schedule
                        <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                    </span>`);

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
            cache: false,
            success: function(result) {
                enrollmentDetails.html(result).slideDown();
                $(param).html(`View Payment Schedule`);
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
            $('#refund_modal').modal('show');

            $('#refund_modal  #REFUND_AMOUNT').val(BALANCE);
            $('#refund_modal .PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
            $('#refund_modal .PK_USER_MASTER').val(PK_USER_MASTER);
            $('#refund_modal .PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
            $('#refund_modal .PK_ENROLLMENT_PAYMENT').val(PK_ENROLLMENT_PAYMENT);
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
        $(param).closest('.enrollment_div').find('.enrollment_details').html('');
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

    function loadCreateAppointmentModal() {
        let PK_USER_MASTER = <?= $PK_USER_MASTER ?>;
        $('#sideDrawer, .overlay').addClass('active');

        $('#myTabContent').show();

        $('#create_appointment_form #SELECTED_CUSTOMER_ID').SumoSelect();
        $('#create_appointment_form #SELECTED_CUSTOMER_ID').val(PK_USER_MASTER);
        $('#create_appointment_form #SELECTED_CUSTOMER_ID')[0].sumo.reload();
        $('#create_appointment_form #SELECTED_CUSTOMER_ID').trigger('change');
        $('.group_class_tab').hide();
        $('.to_do_tab').hide();

        $('#create_record_only_form #SELECTED_CUSTOMER_ID').SumoSelect();
        $('#create_record_only_form #SELECTED_CUSTOMER_ID').val(PK_USER_MASTER);
        $('#create_record_only_form #SELECTED_CUSTOMER_ID')[0].sumo.reload();
    }
</script>


<!-- All function related to Payment -->
<script>
    function getPaymentRegisterData(viewType = '') {
        let PK_USER_MASTER = <?= $PK_USER_MASTER ?>;

        $.ajax({
            url: "partials/ajaxList/customer_payment.php",
            type: "GET",
            data: {
                master_id: PK_USER_MASTER,
                view_type: viewType
            },
            async: false,
            cache: false,
            success: function(result) {

                // Insert HTML
                $('#payment_list').html(result);

                // Highlight any selected payments submenu item when content loads
                if (viewType) {
                    $('.sidebar-submenu-item').removeClass('active');
                    $('.sidebar-submenu-item[data-view-type="' + viewType + '"]').addClass('active');
                }

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
                    dateFormat: "mm/dd/yy",
                    onSelect: function(selected) {
                        $("#END_DATE").datepicker("option", "minDate", selected);
                        table.draw();
                    }
                });

                $("#END_DATE").datepicker({
                    numberOfMonths: 1,
                    dateFormat: "mm/dd/yy",
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

        window.location.hash = '#payment_register';
        window.scrollTo(0, 0);
    }

    function showPaymentsSubTab(viewType, element) {
        $('.sidebar-link').removeClass('active');
        $('.sidebar-link.payments-active').addClass('active');

        $('.sidebar-submenu-item').removeClass('active');
        $(element).addClass('active');

        $('.tab-content').removeClass('active');

        if (viewType == 'credit_card') {
            $('.tab-content-6').addClass('active');
            getSavedCreditCardList();
        } else {
            $('.tab-content-5').addClass('active');
            getWalletDetails();
        }
    }
</script>


<!-- All function related to credit card -->
<script>
    function getSavedCreditCardList() {
        let PK_USER_MASTER = <?= $PK_USER_MASTER ?>;
        $.ajax({
            url: "partials/ajaxList/customer_credit_card_details.php",
            type: 'POST',
            data: {
                PK_USER_MASTER: PK_USER_MASTER,
                call_from: 'customer_credit_card'
            },
            success: function(result) {
                $('#saved_credit_card_list').slideDown().html(result);
                $('#credit_card_loader').hide();
                saveCreditCard();
            }
        });
        window.location.hash = '#credit_card';
    }

    function saveCreditCard() {
        let PK_USER = <?= $PK_USER ?>;
        let PK_USER_MASTER = <?= $PK_USER_MASTER ?>;
        $.ajax({
            url: "partials/ajaxList/customer_save_credit_card.php",
            type: 'POST',
            data: {
                PK_USER: PK_USER,
                PK_USER_MASTER: PK_USER_MASTER
            },
            success: function(result) {
                $('#add_credit_card_div').slideDown().html(result);
            }
        });
    }

    function deleteThisCreditCard(card_id) {
        Swal.fire({
            title: "Are you sure?",
            text: "Deleting this credit card will erase all data related to this card.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                let PK_USER = <?= $PK_USER ?>;
                let PK_USER_MASTER = <?= $PK_USER_MASTER ?>;
                $.ajax({
                    url: "includes/process_delete_credit_card.php",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        'card_id': card_id,
                        'PK_USER': PK_USER
                    },
                    success: function(data) {
                        if (data.STATUS) {
                            $('#delete_message').html(`<p class="alert alert-success">Credit Card Deleted, Page will refresh automatically.</p>`);
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        } else {
                            $('#delete_message').html(`<p class="alert alert-danger">` + data.MESSAGE + `</p>`);
                        }
                    }
                });
            }
        });
    }
</script>


<!-- All function related to wallet payment -->
<script>
    function getWalletDetails() {
        let PK_USER_MASTER = <?= $PK_USER_MASTER ?>;
        $.ajax({
            url: "partials/ajaxList/customer_wallet_details.php",
            type: 'POST',
            data: {
                PK_USER_MASTER: PK_USER_MASTER
            },
            success: function(result) {
                $('#payment_list').html(result);
            }
        });
        window.location.hash = '#wallet_payments';
    }

    function deleteWalletPayment(PK_CUSTOMER_WALLET) {
        Swal.fire({
            title: "Are you sure?",
            text: "Deleting this wallet payment will erase all data related to this payment.",
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
                        FUNCTION_NAME: 'deleteWalletPayment',
                        PK_CUSTOMER_WALLET: PK_CUSTOMER_WALLET
                    },
                    success: function(data) {
                        if (data == 1) {
                            Swal.fire({
                                title: "Deleted!",
                                text: "Wallet Payment Deleted.",
                                icon: "success",
                                timer: 3000,
                            }).then((result) => {
                                getWalletDetails();
                            });
                        } else {
                            Swal.fire({
                                title: "Error!",
                                text: "You already used this wallet amount in enrollment, so you can't delete it.",
                                icon: "warning",
                                timer: 3000,
                            });
                        }
                    }
                });
            }
        });
    }

    function openWalletModel() {
        $('#wallet_payment_model').modal('show');
    }

    function refundWalletData(PK_CUSTOMER_WALLET, BALANCE_LEFT) {
        $('#refund_from_wallet_modal').modal('show');
        $('#PK_CUSTOMER_WALLET').val(PK_CUSTOMER_WALLET);
        $('#ORIGINAL_REFUND_AMOUNT').val(BALANCE_LEFT);
        $('#WALLET_REFUND_AMOUNT').val(BALANCE_LEFT);
    }

    $('#refund_from_wallet').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            type: 'POST',
            url: 'includes/process_refund_wallet.php',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'error') {
                    Swal.fire({
                        title: "Refund Failed!",
                        text: response.message,
                        icon: "error",
                        timer: 3000,
                    });
                } else {
                    Swal.fire({
                        title: "Refund Processed!",
                        text: "The refund has been processed successfully.",
                        icon: "success",
                        timer: 3000,
                    }).then((res) => {
                        window.location.reload();
                    });
                }
            },
            error: function() {
                alert('An error occurred while processing the refund.');
            }
        });
    });
</script>

<!-- Cancel enrollment related functions -->
<script>
    function cancelEnrollment(PK_ENROLLMENT_MASTER, PK_USER_MASTER, enrollment_title) {
        $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
        $('.PK_USER_MASTER').val(PK_USER_MASTER);
        $('.enrollment_title').text(enrollment_title);
        $('#CANCEL_FUTURE_APPOINTMENT_3').prop('checked', false);
        $('#CANCEL_FUTURE_APPOINTMENT_2').prop('checked', false);
        $('#CANCEL_FUTURE_APPOINTMENT_1').prop('checked', true);
        $('#step_3').hide();
        $('#step_2').hide();
        $('#step_1').show();
        $('#enrollment_cancel_modal').modal('show');
    }

    function selectRefundType(param) {
        let paymentType = parseInt($(param).val());
        if (paymentType === 2) {
            $(param).closest('.modal-body').find('.check_payment').slideDown();
        } else {
            $(param).closest('.modal-body').find('.check_payment').slideUp();
        }
    }

    function showEnrollmentServiceDetails() {
        let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
        let USE_AVAILABLE_CREDIT = $('input[name="USE_AVAILABLE_CREDIT"]:checked').val();
        let CANCEL_FUTURE_APPOINTMENT = $('input[name="CANCEL_FUTURE_APPOINTMENT"]:checked').val();
        $.ajax({
            url: "includes/enrollment_service_details.php",
            type: 'GET',
            data: {
                PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                USE_AVAILABLE_CREDIT: USE_AVAILABLE_CREDIT,
                CANCEL_FUTURE_APPOINTMENT: CANCEL_FUTURE_APPOINTMENT
            },
            success: function(data) {
                $('#enrollment_service_details').html(data);
                $('.negative_balance_div').slideUp();
                $('.credit_balance_div').slideUp();

                let TOTAL_POSITIVE_BALANCE = parseFloat($('#TOTAL_POSITIVE_BALANCE').val());
                let TOTAL_NEGATIVE_BALANCE = parseFloat($('#TOTAL_NEGATIVE_BALANCE').val());

                if (USE_AVAILABLE_CREDIT == 1) {
                    TOTAL_POSITIVE_BALANCE += TOTAL_NEGATIVE_BALANCE;
                    TOTAL_NEGATIVE_BALANCE = TOTAL_POSITIVE_BALANCE;
                }

                if (TOTAL_POSITIVE_BALANCE > 0) {
                    $('.credit_balance_div').slideDown();
                    $('#total_credit_balance').text(parseFloat(TOTAL_POSITIVE_BALANCE).toFixed(2));
                    $('#cancel_and_store_btn').attr('title', 'Cancels the enrollment but keeps it in the Active tab so the remaining credit can be refunded or moved to the wallet later.');
                }
                if (TOTAL_NEGATIVE_BALANCE < 0) {
                    $('.negative_balance_div').slideDown();
                    $('#total_negative_balance').text(Math.abs(parseFloat(TOTAL_NEGATIVE_BALANCE).toFixed(2)));
                    $('#cancel_and_store_btn').attr('title', 'Cancels the enrollment but keeps it in the Active tab so the outstanding balance can be collected later.');
                }
            }
        });
    }

    $(document).on('submit', '#cancel_enrollment_form', function(event) {
        event.preventDefault();
        let form_data = new FormData($('#cancel_enrollment_form')[0]);
        $.ajax({
            url: "includes/cancel_customer_enrollment.php",
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(result) {
                let response = result;
                if (response.STATUS == 'Billing') {
                    $('#enrollment_cancel_modal').modal('hide');
                    let PK_ENROLLMENT_LEDGER = response.PK_ENROLLMENT_LEDGER;
                    let BILLED_AMOUNT = response.BILLED_AMOUNT;
                    let PK_ENROLLMENT_MASTER = response.PK_ENROLLMENT_MASTER;
                    payNow(PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BILLED_AMOUNT, '')
                } else {
                    Swal.fire({
                        title: "Enrollment Cancelled!",
                        text: "The enrollment has been cancelled successfully.",
                        icon: "success",
                        timer: 3000,
                    }).then((res) => {
                        window.location.reload();
                    });
                }
            }
        });
    });

    $(document).on('submit', '#refund_form', function(event) {
        event.preventDefault();
        let form_data = new FormData($('#refund_form')[0]);
        $.ajax({
            url: "includes/cancel_customer_enrollment.php",
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                window.location.reload();
            }
        });
    });

    function openDeleteEnrollmentModal(PK_ENROLLMENT_MASTER) {
        Swal.fire({
            title: "Are you sure you want to delete this enrollment?",
            text: "This action cannot be undone.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#delete_enrollment_model').modal('show');
                $('#DELETE_ENROLLMENT_ID').val(PK_ENROLLMENT_MASTER);
            }
        });
    }

    $(document).on('submit', '#delete_enrollment_form', function(event) {
        event.preventDefault();
        let form_data = new FormData($('#delete_enrollment_form')[0]);
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
</script>

<!-- Partner's information -->
<script>
    // Partner related functions
    function handleAttendingWithChange(element) {
        var isChecked = $(element).is(':checked');
        var value = $(element).val();

        if (value === 'Solo') {
            if (isChecked) {
                // Check if there's existing partner data
                var hasPartnerData = checkIfPartnerDataExists();

                if (hasPartnerData) {
                    // Ask user if they want to clear partner data
                    Swal.fire({
                        title: "Clear Partner Information?",
                        text: "Selecting 'Solo' will remove all partner information. Are you sure?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, clear it!",
                        cancelButtonText: "Cancel"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Clear partner data and update UI
                            clearPartnerData();
                            $('#partner_details').slideUp();
                            updatePartnerDisplay();
                        } else {
                            // Re-check the "With a Partner" radio button
                            $('input[name="ATTENDING_WITH"][value="With a Partner"]').prop('checked', true);
                            $('#partner_details').slideDown();
                        }
                    });
                } else {
                    // No partner data, just hide the section
                    $('#partner_details').slideUp();
                }
            } else {
                $('#partner_details').slideDown();
            }
        } else if (value === 'With a Partner') {
            if (isChecked) {
                // Check if there's existing partner data
                var hasPartnerData = checkIfPartnerDataExists();

                if (hasPartnerData) {
                    // Show existing partner data without opening modal
                    $('#partner_details').slideDown();
                    updatePartnerDisplay();
                } else {
                    // No partner data, open modal to add new partner
                    openPartnerModal();
                }
            } else {
                $('#partner_details').slideUp();
            }
        }
    }

    function checkIfPartnerDataExists() {
        var firstName = $('input[name="PARTNER_FIRST_NAME"]').val() || '';
        var lastName = $('input[name="PARTNER_LAST_NAME"]').val() || '';
        var phone = $('input[name="PARTNER_PHONE"]').val() || '';
        var email = $('input[name="PARTNER_EMAIL"]').val() || '';

        // Check if any partner field has data
        return (firstName.trim() !== '' || lastName.trim() !== '' ||
            phone.trim() !== '' || email.trim() !== '');
    }

    function clearPartnerData() {
        // Clear all partner fields in the main form
        $('input[name="PARTNER_FIRST_NAME"]').val('');
        $('input[name="PARTNER_LAST_NAME"]').val('');
        $('input[name="PARTNER_PHONE"]').val('');
        $('input[name="PARTNER_EMAIL"]').val('');
        $('select[name="PARTNER_GENDER"]').val('');
        $('input[name="PARTNER_DOB"]').val('');

        // Also clear the ATTENDING_WITH in database via AJAX
        let PK_USER_MASTER = '<?= $PK_USER_MASTER ?>';
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: {
                FUNCTION_NAME: 'clearPartnerData',
                PK_USER_MASTER: PK_USER_MASTER
            },
            success: function(data) {
                // Update the display
                updatePartnerDisplay();
            }
        });
    }

    function openPartnerModal() {
        // Set the radio button to "With a Partner" if not already selected
        $('input[name="ATTENDING_WITH"][value="With a Partner"]').prop('checked', true);

        // Populate modal fields with current values
        $('#PARTNER_FIRST_NAME_MODAL').val($('input[name="PARTNER_FIRST_NAME"]').val() || '<?= $PARTNER_FIRST_NAME ?>');
        $('#PARTNER_LAST_NAME_MODAL').val($('input[name="PARTNER_LAST_NAME"]').val() || '<?= $PARTNER_LAST_NAME ?>');
        $('#PARTNER_PHONE_MODAL').val($('input[name="PARTNER_PHONE"]').val() || '<?= $PARTNER_PHONE ?>');
        $('#PARTNER_EMAIL_MODAL').val($('input[name="PARTNER_EMAIL"]').val() || '<?= $PARTNER_EMAIL ?>');
        $('#PARTNER_GENDER_MODAL').val($('select[name="PARTNER_GENDER"]').val() || '<?= $PARTNER_GENDER ?>');
        $('#PARTNER_DOB_MODAL').val($('input[name="PARTNER_DOB"]').val() || '<?= ($PARTNER_DOB == '' || $PARTNER_DOB == '0000-00-00' || $PARTNER_DOB == '1969-12-31') ? '' : date('m/d/Y', strtotime($PARTNER_DOB)) ?>');

        $('#partner_modal').modal('show');
    }

    function updatePartnerDisplay() {
        var firstName = $('input[name="PARTNER_FIRST_NAME"]').val() || '';
        var lastName = $('input[name="PARTNER_LAST_NAME"]').val() || '';
        var phone = $('input[name="PARTNER_PHONE"]').val() || '';
        var email = $('input[name="PARTNER_EMAIL"]').val() || '';
        var gender = $('select[name="PARTNER_GENDER"]').val() || '';
        var dob = $('input[name="PARTNER_DOB"]').val() || '';

        var displayHtml = '';

        if (firstName.trim() !== '' || lastName.trim() !== '' || phone.trim() !== '' || email.trim() !== '') {
            displayHtml = '<div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">';
            displayHtml += '<div><strong>' + firstName + ' ' + lastName + '</strong>';
            if (phone || email || gender || dob) {
                displayHtml += '<div class="text-muted small mt-1">';
                if (phone) displayHtml += '📞 ' + phone + ' ';
                if (gender) displayHtml += ' | ' + gender + ' ';
                if (dob) displayHtml += ' | 🎂 ' + dob + ' ';
                if (email) displayHtml += ' | ✉️ ' + email;
                displayHtml += '</div>';
            }
            displayHtml += '</div>';
            displayHtml += '<div><a href="javascript:;" class="btn btn-sm btn-outline-edit" onclick="openPartnerModal()"><i class="bi bi-pencil"></i> Edit</a></div>';
            displayHtml += '</div>';
        } else {
            displayHtml = '<div class="text-center p-3 text-muted">No partner information added yet. <a href="javascript:;" onclick="openPartnerModal()">Add partner</a></div>';
        }

        $('#partner_details .col-12').html(displayHtml);
    }

    $(document).on('submit', '#partner_add_edit_form', function(event) {
        event.preventDefault();

        // Update the main form fields with modal values
        $('input[name="PARTNER_FIRST_NAME"]').val($('#PARTNER_FIRST_NAME_MODAL').val());
        $('input[name="PARTNER_LAST_NAME"]').val($('#PARTNER_LAST_NAME_MODAL').val());
        $('input[name="PARTNER_PHONE"]').val($('#PARTNER_PHONE_MODAL').val());
        $('input[name="PARTNER_EMAIL"]').val($('#PARTNER_EMAIL_MODAL').val());
        $('select[name="PARTNER_GENDER"]').val($('#PARTNER_GENDER_MODAL').val());
        $('input[name="PARTNER_DOB"]').val($('#PARTNER_DOB_MODAL').val());

        // Show the partner details section
        $('#partner_details').slideDown();

        // Set the radio button
        $('input[name="ATTENDING_WITH"][value="With a Partner"]').prop('checked', true);

        // Now submit the form via AJAX to save the data
        let form_data = new FormData($('#partner_add_edit_form')[0]);
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            success: function(data) {
                $('#partner_modal').modal('hide');
                // Update the display in the partner details section
                updatePartnerDisplay();
                Swal.fire({
                    title: "Success!",
                    text: "Partner information saved successfully.",
                    icon: "success",
                    timer: 2000,
                });
            },
            error: function() {
                Swal.fire({
                    title: "Error!",
                    text: "Something went wrong. Please try again.",
                    icon: "error",
                    timer: 3000,
                });
            }
        });
    });
</script>

</html>