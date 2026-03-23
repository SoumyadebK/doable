<?php
require_once('../global/config.php');
require_once("../global/stripe-php-master/init.php");
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$userType = "Customers";

$status_check = empty($_GET['status']) ? 'active' : $_GET['status'];
if ($status_check == 'active') {
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

$user_role_condition = " AND PK_ROLES = 4";
if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

$CREATE_LOGIN = 0;
$user_doc_count = 0;

if (empty($_GET['id'])) {
    $title = "Add " . $userType;
    $header = '';
} else {
    $title = "Edit " . $userType;
    $header = 'customer.php?id=' . $_GET['id'] . '&master_id=' . $_GET['master_id'] . '&tab=enrollment';
}

$PK_USER = $_GET['id'] ?? '';
$PK_USER_MASTER = $_GET['master_id'] ?? '';



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

/* $PK_ENROLLMENT_MASTER_ARRAY = [];
$not_billed_enrollment = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_MASTER WHERE NOT EXISTS(SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_BILLING WHERE DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER )");
if ($not_billed_enrollment->RecordCount() > 0) {
    while (!$not_billed_enrollment->EOF) {
        $PK_ENROLLMENT_MASTER_ARRAY[] = $not_billed_enrollment->fields['PK_ENROLLMENT_MASTER'];
        addEnrollmentLogData($not_billed_enrollment->fields['PK_ENROLLMENT_MASTER'], 'Deleted', 'Enrollment deleted from Customer');
        $not_billed_enrollment->MoveNext();
    }

    $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` IN (" . implode(',', $PK_ENROLLMENT_MASTER_ARRAY) . ")");
    $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` IN (" . implode(',', $PK_ENROLLMENT_MASTER_ARRAY) . ")");
} */

$title = $FIRST_NAME . " " . $LAST_NAME;

$CUSTOMER_NAME = $FIRST_NAME . " " . $LAST_NAME;
$customer = getProfileBadge($CUSTOMER_NAME);
$customer_initial = $customer['initials'];
$customer_color = $customer['color'];

if ($PK_USER_MASTER > 0) {
    makeExpiryEnrollmentComplete($PK_USER_MASTER);
    makeMiscComplete($PK_USER_MASTER);
    makeDroppedCancelled($PK_USER_MASTER);
    checkAllEnrollmentStatus($PK_USER_MASTER);
    //markAdhocAppointmentNormal(24013);
    //markEnrollmentComplete(9850);
}
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
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 0px !important;">

            <div class="container-fluid mt-4">
                <div class="card-box" style="margin-top: 20px;">
                    <div class="d-flex mb-3 px-3"><i class="bi bi-chevron-left font-12"></i>
                        <h6 class="mx-3">Customers</h6>
                    </div>
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
                                <a class="sidebar-link enrollments-active" href="#" data-toggle-target=".tab-content-3"><i class="bi bi-journal-text me-2"></i> Enrollments</a>
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
                                            <form>
                                                <!-- <button class="btn btn-outline-edit h-100 save-button">Save</button>
                        <button class="btn btn-outline-edit h-100 cancel-button">Cancel</button>
                    <button class="btn btn-outline-edit h-100 edit-button">Edit</button> -->
                                                <a class="btn btn-outline-edit save-button">Save</a>
                                                <a class="btn btn-outline-edit cancel-button">Cancel</a>
                                                <a class="btn btn-outline-edit edit-button">Edit</a>
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
                                            </div>
                                        </div>
                                        </form>
                                    </div>

                                    <div class="profile-card">
                                        <div class="d-flex justify-content-between border-bottom">
                                            <div>
                                                <div class="section-title">Address Information</div>
                                                <div class="section-desc">Optional settings section description</div>
                                            </div>
                                            <!-- <button class="btn btn-outline-edit">Edit</button> -->
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
                                            <div class="family-member-card d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="member-name">Daniel Williams</div>
                                                    <div class="member-role">Husband</div>
                                                    <div class="contact-info">
                                                        <span>danielwilliams@email.com <i class="bi bi-copy copy-icon"></i></span>
                                                        <span>310-123-4567 <i class="bi bi-copy copy-icon"></i></span>
                                                    </div>
                                                </div>
                                                <div class="action-icons">
                                                    <i class="bi bi-pencil me-2"></i>
                                                    <i class="bi bi-trash"></i>
                                                </div>
                                            </div>

                                            <div class="family-member-card d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="member-name">Maggie Williams</div>
                                                    <div class="member-role">Daughter</div>
                                                </div>
                                                <div class="action-icons">
                                                    <i class="bi bi-pencil me-2"></i>
                                                    <i class="bi bi-trash"></i>
                                                </div>
                                            </div>

                                            <div class="family-member-card d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="member-name">Cheryl Rockefeller</div>
                                                    <div class="member-role">Friend</div>
                                                    <div class="contact-info">
                                                        <span>cheryl@email.com <i class="bi bi-copy copy-icon"></i></span>
                                                        <span>310-123-4567 <i class="bi bi-copy copy-icon"></i></span>
                                                    </div>
                                                </div>
                                                <div class="action-icons">
                                                    <i class="bi bi-pencil me-2"></i>
                                                    <i class="bi bi-trash"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <a href="#" class="add-family-btn mt-2">
                                            <i class="bi bi-plus-lg me-2"></i> Add Family
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-content tab-content-3 row enrollments-section">
                                <div class="col-md-12 px-3 pt-4 pb-4">
                                    <div class="enrollment-container">
                                        <h4 class="fw-bold mb-1">Enrollments</h4>
                                        <p class="text-muted mb-4 small">Optional settings section description</p>

                                        <div class="d-flex align-items-center border-top border-bottom py-4 mb-4">
                                            <div class="flex-grow-1">
                                                <div class="stat-label">Total Balance</div>
                                                <div class="stat-value">$2,000.00</div>
                                            </div>
                                            <div class="stat-divider"></div>
                                            <div class="flex-grow-1">
                                                <div class="stat-label">Miscellaneous Balance</div>
                                                <div class="stat-value">$0.00</div>
                                            </div>
                                            <div class="stat-divider"></div>
                                            <div class="flex-grow-1">
                                                <div class="stat-label">Wallet Balance</div>
                                                <div class="stat-value">$100.00</div>
                                            </div>
                                        </div>

                                        <h6 class="fw-bold mb-3">List of Pending Services</h6>
                                        <div class="table-responsive mb-5">
                                            <table class="table border-0">
                                                <thead>
                                                    <tr>
                                                        <th>Service Code</th>
                                                        <th>Enroll</th>
                                                        <th>Used</th>
                                                        <th>Scheduled</th>
                                                        <th>Remain</th>
                                                        <th>Balance</th>
                                                        <th>Paid <i class="bi bi-caret-up-fill small"></i></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><span class="badge-service bg-pri">PRI</span></td>
                                                        <td>52</td>
                                                        <td>0</td>
                                                        <td>6</td>
                                                        <td>46</td>
                                                        <td>19</td>
                                                        <td>$1,600.00</td>
                                                    </tr>
                                                    <tr>
                                                        <td><span class="badge-service bg-grp">GRP</span></td>
                                                        <td>52</td>
                                                        <td>0</td>
                                                        <td>6</td>
                                                        <td>46</td>
                                                        <td>19</td>
                                                        <td>$1,600.00</td>
                                                    </tr>
                                                    <tr>
                                                        <td><span class="badge-service bg-ext">EXT</span></td>
                                                        <td>52</td>
                                                        <td>0</td>
                                                        <td>6</td>
                                                        <td>46</td>
                                                        <td>19</td>
                                                        <td>$1,600.00</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold mb-0">Demo 2 | -3 PRI || GRP || PTY <span class="text-muted fw-normal ms-2">11/14/2025</span></h6>
                                            <a href="#" class="view-schedule text-primary">View Payment Schedule</a>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Service Code</th>
                                                        <th>Enrolled</th>
                                                        <th>Used</th>
                                                        <th>Scheduled</th>
                                                        <th>Balance</th>
                                                        <th>Paid</th>
                                                        <th>Service Credit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><span class="badge-service bg-pri">PRI</span></td>
                                                        <td>52</td>
                                                        <td>0</td>
                                                        <td>6</td>
                                                        <td>19</td>
                                                        <td>17.00</td>
                                                        <td>17.00</td>
                                                    </tr>
                                                    <tr>
                                                        <td><span class="badge-service bg-grp">GRP</span></td>
                                                        <td>52</td>
                                                        <td>0</td>
                                                        <td>6</td>
                                                        <td>19</td>
                                                        <td>0</td>
                                                        <td>25.00</td>
                                                    </tr>
                                                    <tr>
                                                        <td><span class="badge-service bg-pty">PTY</span></td>
                                                        <td>52</td>
                                                        <td>0</td>
                                                        <td>6</td>
                                                        <td>19</td>
                                                        <td>0</td>
                                                        <td>15.00</td>
                                                    </tr>
                                                </tbody>
                                                <tfoot class="border-top-0">
                                                    <tr class="fw-bold">
                                                        <td>Amount</td>
                                                        <td>4,250.00</td>
                                                        <td>0.00</td>
                                                        <td>340.00</td>
                                                        <td>2,750.00</td>
                                                        <td>$1,500.00</td>
                                                        <td>1,500.00</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                        <div class="d-flex justify-content-end align-items-center mt-3">
                                            <div class="form-check form-switch d-flex align-items-center">
                                                <input class="form-check-input me-2" type="checkbox" role="switch" id="autoPaySwitch">
                                                <label class="form-check-label autopay-label" for="autoPaySwitch">AutoPay</label>
                                            </div>
                                        </div>
                                    </div>
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
    // click edit btn
    $(window).on("load", function() {

        $('.save-button').on('click', save_onclick);
        $('.cancel-button').on('click', cancel_onclick);
        $('.edit-button').on('click', edit_onclick);

        $('.save-button, .cancel-button').hide();
    });

    function edit_onclick() {
        setFormMode($(this).closest("form"), 'edit');
    }

    function cancel_onclick() {
        setFormMode($(this).closest("form"), 'view');

        //TODO: Undo input changes?
    }

    function save_onclick() {
        setFormMode($(this).closest("form"), 'view');

        //TODO: Send data to server?
    }


    function setFormMode($form, mode) {
        switch (mode) {
            case 'view':
                $form.find('.save-button, .cancel-button').hide();
                $form.find('.edit-button').show();
                $('.show-edit').addClass('d-none');
                $('.hide-edit').removeClass('d-none');
                $form.find("input, select").prop("disabled", true);
                break;
            case 'edit':
                $form.find('.save-button, .cancel-button').show();
                $form.find('.edit-button').hide();
                $('.hide-edit').addClass('d-none');
                $('.show-edit').removeClass('d-none');
                $form.find("input, select").prop("disabled", false);
                break;
        }
    }
    // end

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
    let PK_USER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);
    let PK_USER_MASTER = parseInt(<?= empty($_GET['master_id']) ? 0 : $_GET['master_id'] ?>);

    function createUserComment() {
        $('#comment_header').text("Add Comment");
        $('#PK_COMMENT').val(0);
        $('#COMMENT').val('');
        $('#COMMENT_DATE').val('');
        $('#comment_active').hide();
        openCommentModel();
    }

    function createEnrollment() {
        $('#enrollment_header').text("Add Enrollment");
        openEnrollmentModel();
    }

    function createNewAppointment() {
        $('#appointment_header').text("Add Appointment");
        openAppointmentModel();
    }

    function viewPaymentList() {
        $('#payment_header').text("Add Payment");
        openPaymentListModel();
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
        $('#commentModal').modal('show');
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
                window.location.href = `customer.php?id=${PK_USER}&master_id=${PK_USER_MASTER}&on_tab=comments`;
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
                    window.location.href = `customer.php?id=${PK_USER}&master_id=${PK_USER_MASTER}&on_tab=comments`;
                }
            });
        }
    }

    $('.multi_sumo_select').SumoSelect({
        placeholder: 'Select Location',
        selectAll: true
    });

    $(document).ready(function() {
        let tab_link = <?= empty($_GET['tab']) ? 0 : $_GET['tab'] ?>;
        fetch_state(<?php echo $PK_COUNTRY; ?>);
        if (tab_link.id == 'profile') {
            $('#profile_tab_link')[0].click();
        }
        if (tab_link.id == 'enrollment') {
            $('#enrollment_tab_link')[0].click();
        }
        if (tab_link.id == 'appointment') {
            $('#appointment_tab_link')[0].click();
        }
        if (tab_link.id == 'billing') {
            $('#billing_tab_link')[0].click();
        }
        if (tab_link.id == 'comments') {
            $('#comment_tab_link')[0].click();
        }
        if (tab_link.id == 'credit_card') {
            $('#credit_card_tab_link')[0].click();
        }
        if (tab_link.id == 'wallet') {
            $('#wallet_tab_link')[0].click();
        }
        let on_tab_link = <?= empty($_GET['on_tab']) ? 0 : $_GET['on_tab'] ?>;
        if (on_tab_link.id == 'comments') {
            $('#comment_tab_link')[0].click();
        }
    });

    function fetch_state(PK_COUNTRY) {
        jQuery(document).ready(function() {
            let data = "PK_COUNTRY=" + PK_COUNTRY + "&PK_STATES=<?= $PK_STATES; ?>";
            let value = $.ajax({
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

    function selectRefundType(param) {
        let paymentType = parseInt($(param).val());
        if (paymentType === 2) {
            $(param).closest('.modal-body').find('#check_payment').slideDown();
        } else {
            $(param).closest('.modal-body').find('#check_payment').slideUp();
        }
    }
</script>
<script>
    function togglePasswordVisibility() {
        let passwordInput = document.getElementById("PASSWORD");
        if (passwordInput.type === "password") {
            passwordInput.type = "text"; // Show password
        } else {
            passwordInput.type = "password"; // Hide password
        }
    }

    function toggleConfirmPasswordVisibility() {
        let passwordInput = document.getElementById("CONFIRM_PASSWORD");
        if (passwordInput.type === "password") {
            passwordInput.type = "text"; // Show password
        } else {
            passwordInput.type = "password"; // Hide password
        }
    }


    function isGood(password) {
        let password_strength = document.getElementById("password-text");

        if (password.length == 0) {
            password_strength.innerHTML = "";
            return;
        }
        //Regular Expressions.
        let regex = new Array();
        regex.push("[A-Z]"); //Uppercase Alphabet.
        regex.push("[a-z]"); //Lowercase Alphabet.
        regex.push("[0-9]"); //Digit.
        regex.push("[$@$!%*#?&]"); //Special Character.
        let passed = 0;
        //Validate for each Regular Expression.
        for (let i = 0; i < regex.length; i++) {
            if (new RegExp(regex[i]).test(password)) {
                passed++;
            }
        }
        //Display status.
        let strength = "";
        switch (passed) {
            case 0:
            case 1:
            case 2:
                strength = "<small class='progress-bar bg-danger' style='width: 50%'>Weak</small>";
                $('#password_note').slideDown();
                $('#password_strength').val(0);
                break;
            case 3:
                strength = "<small class='progress-bar bg-warning' style='width: 60%'>Medium</small>";
                $('#password_note').slideDown();
                $('#password_strength').val(0);
                break;
            case 4:
                strength = "<small class='progress-bar bg-success' style='width: 100%'>Strong</small>";
                $('#password_note').slideUp();
                $('#password_strength').val(1);
                break;

        }
        // alert(strength);
        password_strength.innerHTML = strength;
    }

    function ValidateUsername() {
        let username = document.getElementById("USER_NAME").value;
        let lblError = document.getElementById("lblError");
        lblError.innerHTML = "";
        let expr = /^[a-zA-Z0-9_]{8,20}$/;
        if (!expr.test(username)) {
            lblError.innerHTML = "Only Alphabets, Numbers and Underscore and between 8 to 20 characters.";
        } else {
            lblError.innerHTML = "";
        }
    }

    $(document).on('click', '#cancel_button', function() {
        window.location.href = 'all_customers.php';
    });

    $(document).on('change', '.engagement_terms', function() {
        if ($(this).is(':checked')) {
            $(this).closest('.col-1').next().slideDown();
        } else {
            $(this).closest('.col-1').next().slideUp();
        }
    });

    function createLogin(param) {
        if ($(param).is(':checked')) {
            $('#login_info_tab').show();
            $('#phone_label').text('* (Please type your phone number)');
            $('#PHONE').prop('required', true);
            $('#email_label').text('* (Please type your email id)');
            $('#EMAIL_ID').prop('required', true);
            $('#submit_button').hide();
            $('#next_button_interest').hide();
            $('#next_button').show();
        } else {
            $('#login_info_tab').hide();
            $('#phone_label').text('');
            $('#PHONE').prop('required', false);
            $('#email_label').text('');
            $('#EMAIL_ID').prop('required', false);
            $('#submit_button').show();
            $('#next_button_interest').show();
            $('#next_button').hide();
        }
    }

    let counter = parseInt(<?= $user_doc_count ?>);

    function addMoreUserDocument() {
        $('#append_user_document').append(`<div class="row">
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <label class="form-label">Document Name</label>
                                                        <input type="text" name="DOCUMENT_NAME[]" class="form-control" placeholder="Enter Document Name">
                                                    </div>
                                                </div>
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <label class="form-label">Document File</label>
                                                        <input type="file" name="FILE_PATH[]" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <div class="form-group" style="margin-top: 30px;">
                                                        <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                              </div>`);
        counter++;
    }

    function removeUserDocument(param) {
        $(param).closest('.row').remove();
        counter--;
    }

    function goLoginInfo() {
        let element = $('#profile').find('input');
        let count = element.length;
        element.each(function() {
            if ($(this).prop('required') && ($(this).val() === '')) {
                $(this).focus();
                return false;
            }
            count--;
            if (count === 0) {
                $('#login_info_tab_link')[0].click();
            }
        });
    }

    function goInterest() {
        let element = $('#profile').find('input');
        let count = element.length;
        element.each(function() {
            if ($(this).prop('required') && ($(this).val() === '')) {
                $(this).focus();
                return false;
            }
            count--;
            if (count === 0) {
                $('#interest_tab_link')[0].click();
            }
        });
    }

    function removeThis(param) {
        $(param).closest('.row').remove();
    }

    function addMorePhone() {
        $('#add_more_phone').append(`<div class="row">
                                            <div class="col-9">
                                                <div class="form-group">
                                                    <label class="form-label">Phone</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="CUSTOMER_PHONE[]" class="form-control" placeholder="Enter Phone Number">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-2" style="padding-top: 25px;">
                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                        </div>`);
    }

    function addMoreEmail() {
        $('#add_more_email').append(`<div class="row">
                                            <div class="col-9">
                                                <div class="form-group">
                                                    <label class="col-md-12">Email</label>
                                                    <div class="col-md-12">
                                                        <input type="email" name="CUSTOMER_EMAIL[]" class="form-control" placeholder="Enter Email Address">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-2" style="padding-top: 25px;">
                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                         </div>`);
    }

    function addMoreSpecialDays(param) {
        $(param).closest('.row').next('.add_more_special_days').append(`<div class="row">
                                                    <div class="col-5">
                                                        <div class="form-group">
                                                            <label class="form-label">Special Date</label>
                                                            <div class="col-md-12">
                                                                <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="CUSTOMER_SPECIAL_DATE[]">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-5">
                                                        <div class="form-group">
                                                            <label class="form-label">Date Name</label>
                                                            <div class="col-md-12">
                                                                <input type="text" class="form-control" name="CUSTOMER_SPECIAL_DATE_NAME[]">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-2" style="padding-top: 25px;">
                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>`);
    }

    let family_special_day_count = parseInt(<?= ($family_member_count == 0) ? 0 : ($family_member_count - 1) ?>);

    function addMoreFamilyMember() {
        family_special_day_count++;
        $('#add_more_family_member').append(`<div class="row family_member" style="padding: 35px; margin-top: -60px;"">
                                                    <div class="row">
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <label class="form-label">First Name<span class="text-danger">*</span></label>
                                                                <div class="col-md-12">
                                                                    <input type="text" name="FAMILY_FIRST_NAME[]" class="form-control" placeholder="Enter First Name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <label class="form-label">Last Name</label>
                                                                <div class="col-md-12">
                                                                    <input type="text" name="FAMILY_LAST_NAME[]" class="form-control" placeholder="Enter Last Name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <label class="form-label">Relationship</label>
                                                                <div class="col-md-12 customer-select">
                                                                    <select class="form-control" name="PK_RELATIONSHIP[]">
                                                                        <option>Select Relationship</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT * FROM DOA_RELATIONSHIP WHERE ACTIVE = 1");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_RELATIONSHIP']; ?>"><?= $row->fields['RELATIONSHIP'] ?></option>
                                                                        <?php $row->MoveNext();
                                                                        } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="$(this).closest('.row').next().slideToggle();"><i class="ti-arrow-circle-down"></i> More Info</a>
                                                        </div>
                                                        <div class="col-1">
                                                            <a href="javascript:;" class="btn btn-danger waves-effect waves-light text-white" style="margin-top: 30px;" onclick="removeThisFamilyMember(this);"><b><i class="ti-trash"></i></b></a>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <div class="form-group">
                                                                    <label class="form-label">Phone</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="FAMILY_PHONE[]" class="form-control" placeholder="Enter Phone Number">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-5">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">Email</label>
                                                                    <div class="col-md-12">
                                                                        <input type="email" name="FAMILY_EMAIL[]" class="form-control" placeholder="Enter Email Address">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Gender</label>
                                                                    <div class="customer-select">
                                                                    <select class="form-control" name="FAMILY_GENDER[]">
                                                                        <option>Select Gender</option>
                                                                        <option value="Male" <?php if ($GENDER == "Male") echo 'selected = "selected"'; ?>>Male</option>
                                                                        <option value="Female" <?php if ($GENDER == "Female") echo 'selected = "selected"'; ?>>Female</option>
                                                                        <option value="Other" <?php if ($GENDER == "Other") echo 'selected = "selected"'; ?>>Other</option>
                                                                    </select>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Date of Birth</label>
                                                                    <input type="text" class="form-control datepicker-past" name="FAMILY_DOB[]">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-2" style="margin-left: 80%">
                                                                <div class="form-group">
                                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 15px;" data-counter="${family_special_day_count}" onclick="addMoreSpecialDaysFamily(this);"><i class="ti-plus"></i> New</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="add_more_special_days">
                                                            <div class="row">
                                                                <div class="col-5">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Special Date</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[${family_special_day_count}][]">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-5">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Date Name</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[${family_special_day_count}][]">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-2" style="padding-top: 25px;">
                                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>`);
    }


    function addMoreSpecialDaysFamily(param) {
        let data_counter = $(param).data('counter');
        $(param).closest('.row').next('.add_more_special_days').append(`<div class="row">
                                                                                <div class="col-5">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Special Date</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[${data_counter}][]">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-5">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Date Name</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[${data_counter}][]">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-2" style="padding-top: 25px;">
                                                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                                </div>
                                                                            </div>`);
    }

    function removeThisFamilyMember(param) {
        family_special_day_count--;
        $(param).closest('.family_member').remove();
    }

    function selectThisPrimaryLocation(param) {
        let primary_location = $(param).val();
        let selected_location = $('#selected_location').val();
        $.ajax({
            url: "ajax/get_all_locations.php",
            type: 'GET',
            data: {
                primary_location: primary_location,
                selected_location: selected_location
            },
            success: function(data) {
                $('#PK_LOCATION_MULTIPLE').empty().append(data);
                $('#PK_LOCATION_MULTIPLE')[0].sumo.reload();
            }
        });
    }

    $(document).on('submit', '#profile_form', function(event) {
        event.preventDefault();
        let form_data = new FormData($('#profile_form')[0]); //$('#profile_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            dataType: 'JSON',
            success: function(data) {
                console.log(data);
                $('.PK_USER').val(data.PK_USER);
                $('.PK_USER_MASTER').val(data.PK_USER_MASTER);
                $('.PK_CUSTOMER_DETAILS').val(data.PK_CUSTOMER_DETAILS);
                if (PK_USER == 0) {
                    if ($('#CREATE_LOGIN').is(':checked')) {
                        $('#login_info_tab_link')[0].click();
                    } else {
                        $('#family_tab_link')[0].click();
                    }
                } else {
                    let PK_USER = $('.PK_USER').val();
                    let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                    window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER;
                }
            }
        });
    });

    $(document).on('submit', '#login_form', function(event) {
        event.preventDefault();
        let PASSWORD = $('#PASSWORD').val();
        let CONFIRM_PASSWORD = $('#CONFIRM_PASSWORD').val();
        let password_strength = $('#password_strength').val();
        if (password_strength == 0) {
            $('#password_error').text('Password is not strong enough');
            return false;
        } else {
            $('#password_error').text('');
            if (PASSWORD === CONFIRM_PASSWORD) {
                let SAVED_OLD_PASSWORD = $('#SAVED_OLD_PASSWORD').val();
                let OLD_PASSWORD = $('#OLD_PASSWORD').val();
                if (SAVED_OLD_PASSWORD) {
                    $.ajax({
                        url: "ajax/check_old_password.php",
                        type: 'POST',
                        data: {
                            ENTERED_PASSWORD: OLD_PASSWORD,
                            SAVED_PASSWORD: SAVED_OLD_PASSWORD
                        },
                        success: function(data) {
                            if (data == 0) {
                                $('#password_error').text('Old Password not matched');
                            } else {
                                let form_data = $('#login_form').serialize();
                                $.ajax({
                                    url: "ajax/AjaxFunctions.php",
                                    type: 'POST',
                                    data: form_data,
                                    success: function(data) {
                                        $('.PK_USER').val(data);
                                        if (PK_USER == 0) {
                                            $('#family_tab_link')[0].click();
                                        } else {
                                            let PK_USER = $('.PK_USER').val();
                                            let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                                            window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER;
                                        }
                                    }
                                });
                            }
                        }
                    });
                } else {
                    let form_data = $('#login_form').serialize();
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: form_data,
                        success: function(data) {
                            $('.PK_USER').val(data);
                            if (PK_USER == 0) {
                                $('#family_tab_link')[0].click();
                            } else {
                                let PK_USER = $('.PK_USER').val();
                                let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                                window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER;
                            }
                        }
                    });
                }
            } else {
                $('#password_error').text('Password and Confirm Password not matched');
            }
        }
    });

    $(document).on('submit', '#family_form', function(event) {
        event.preventDefault();
        let form_data = $('#family_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            success: function(data) {
                let PK_USER = $('.PK_USER').val();
                let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER;
            }
        });
    });

    $(document).on('submit', '#interest_form', function(event) {
        event.preventDefault();
        let form_data = $('#interest_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            success: function(data) {
                let PK_USER = $('.PK_USER').val();
                let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER;
            }
        });
    });

    $(document).on('submit', '#document_form', function(event) {
        event.preventDefault();
        let form_data = new FormData($('#document_form')[0]); //$('#document_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            success: function(data) {
                let PK_USER = $('.PK_USER').val();
                let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER;
            }
        });
    });
</script>

<script>
    $('#NAME').SumoSelect({
        placeholder: 'Select Customer',
        search: true,
        searchText: 'Search...'
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

    function confirmComplete(param) {
        let conf = confirm("Do you want to mark this appointment as completed?");
        if (conf) {
            let PK_APPOINTMENT_MASTER = $(param).data('id');
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'markAppointmentCompleted',
                    PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                },
                success: function(data) {
                    if (data == 1) {
                        $(param).closest('td').html('<span class="status-box" style="background-color: #ff0019">Completed</span>');
                    } else {
                        alert("Something wrong");
                    }
                }
            });
        }
    }


    var enr_tab_type = '';
    var page_count = 1;
    var loading = false;
    var hasMore = true;
    var observer;

    function showEnrollmentList(page, type) {
        enr_tab_type = type;
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        let PK_USER = $('.PK_USER').val();

        loading = true;
        $("#load-marker").text("Loading...");

        $.ajax({
            url: "pagination/enrollment.php",
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

    // Setup observer only once
    function enrollmentLoadMore(type) {
        enr_tab_type = type;
        page_count = 1;
        hasMore = true;
        loading = false;
        $("#enrollment_list").html('<div id="load-marker" style="text-align:center; padding:10px;">Loading <i class="fas fa-spinner fa-pulse" style="font-size: 15px;"></i></div>');

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



    /* var enr_tab_type = '';
    var page_count = 1;

    function showEnrollmentList(page, type) {
        enr_tab_type = type;
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        $.ajax({
            url: "pagination/enrollment.php",
            type: "GET",
            data: {
                search_text: '',
                page: page,
                type: type,
                pk_user: PK_USER,
                master_id: PK_USER_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                if (page > 1) {
                    $('#enrollment_list').append(result);
                } else {
                    $('#enrollment_list').html(result);
                }
            }
        });
        //window.scrollTo(0, 0);
    }

    let loading = false;
    $(window).scroll(function() {
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 100 && !loading && enr_tab_type != '') {
            page_count++;
            alert(page_count);
            showEnrollmentList(page_count, enr_tab_type);
        }
    }); */

    function getPaymentRegisterData() {
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();

        $.ajax({
            url: "pagination/payment_register.php",
            type: "GET",
            data: {
                master_id: PK_USER_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                // Replace HTML
                $('#payment_register_list').html(result);

                // Destroy existing DataTable if exists
                if ($.fn.DataTable.isDataTable('#paymentRegisterTable')) {
                    $('#paymentRegisterTable').DataTable().clear().destroy();
                    $('#paymentRegisterTable').off(); // remove old events
                }

                // Remove old custom filters to avoid stacking
                $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                    return fn.name !== "dateRangeFilter";
                });

                let table = $('#paymentRegisterTable').DataTable({
                    order: [
                        [0, 'desc']
                    ],
                    columnDefs: [{
                        type: 'date',
                        targets: 0
                    }],
                    dom: '<"d-flex justify-content-between align-items-center"l<"date-filter">fB>rtip',
                    buttons: [{
                        extend: 'excelHtml5',
                        text: 'Export to Excel',
                        title: 'Payment Register',
                        exportOptions: {
                            columns: ':visible'
                        }
                    }]
                });

                $("div.date-filter").html(`
                    <div class="input-group">
                        <input type="text" id="START_DATE" class="form-control form-control-sm" placeholder="From Date">
                        <input type="text" id="END_DATE" class="form-control form-control-sm ms-2" placeholder="To Date">
                    </div>
                `);

                // Init datepickers
                $("#START_DATE").datepicker({
                    numberOfMonths: 1,
                    onSelect: function(selected) {
                        $("#END_DATE").datepicker("option", "minDate", selected);
                        table.draw();
                    }
                });

                $("#END_DATE").datepicker({
                    numberOfMonths: 1,
                    onSelect: function(selected) {
                        $("#START_DATE").datepicker("option", "maxDate", selected);
                        table.draw();
                    }
                });

                // Custom filtering function for date range (named for easy removal)
                function dateRangeFilter(settings, data, dataIndex) {
                    var min = $('#START_DATE').val();
                    var max = $('#END_DATE').val();
                    var date = data[0]; // first column

                    if (!date) return true;

                    var tableDate = new Date(date);

                    if ((min === "" && max === "") ||
                        (min === "" && tableDate <= new Date(max)) ||
                        (new Date(min) <= tableDate && max === "") ||
                        (new Date(min) <= tableDate && tableDate <= new Date(max))) {
                        return true;
                    }
                    return false;
                }
                dateRangeFilter.name = "dateRangeFilter"; // label it
                $.fn.dataTable.ext.search.push(dateRangeFilter);

                // Event listener for inputs
                $('#START_DATE, #END_DATE').on('change keyup', function() {
                    table.draw();
                });
            }
        });

        window.scrollTo(0, 0);
    }



    function openReceipt(PK_ENROLLMENT_MASTER, RECEIPT_NUMBER) {
        let RECEIPT_NUMBER_ARRAY = RECEIPT_NUMBER.split(',');
        for (let i = 0; i < RECEIPT_NUMBER_ARRAY.length; i++) {
            window.open('generate_receipt_pdf.php?master_id=' + PK_ENROLLMENT_MASTER + '&receipt=' + RECEIPT_NUMBER_ARRAY[i], '_blank');
        }
    }

    function showAgreementDocument() {
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        $.ajax({
            url: "pagination/agreement_document.php",
            type: "GET",
            data: {
                master_id: PK_USER_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#agreement_document').html(result);
            }
        });
        window.scrollTo(0, 0);
    }

    /*function showCompletedEnrollmentList(page) {
    let PK_USER_MASTER=$('.PK_USER_MASTER').val();
    $.ajax({
    url: "pagination/completed_enrollments.php",
    type: "GET",
    data: {search_text:'', page:page, master_id:PK_USER_MASTER},
    async: false,
    cache: false,
    success: function (result) {
    $('#completed_enrollment_list').html(result)
    }
    });
    window.scrollTo(0,0);
    }*/

    function showAppointment(page, type) {
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        $.ajax({
            url: "pagination/appointment.php",
            type: "GET",
            data: {
                search_text: '',
                page: page,
                master_id: PK_USER_MASTER,
                type: type
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#appointment_list').html(result)
                if (type === 'unposted') {
                    $('#unposted').hide()
                    $('#posted').show();
                    $('#canceled').show();
                    $('#posted_list').hide();
                    $('#unposted_list').show();
                    $('#canceled_list').hide();
                } else {
                    if (type === 'posted') {
                        $('#posted').hide();
                        $('#unposted').show();
                        $('#canceled').show();
                        $('#unposted_list').hide();
                        $('#posted_list').show();
                        $('#canceled_list').hide();
                    } else {
                        if (type === 'cancelled') {
                            $('#posted').show();
                            $('#unposted').hide();
                            $('#unposted_list').hide();
                            $('#posted_list').hide();
                            $('#canceled_list').show();
                        } else {
                            $('#unposted').hide()
                            $('#posted').show();
                            $('#canceled').show();
                            $('#posted_list').hide();
                            $('#unposted_list').show();
                            $('#canceled_list').hide();
                        }
                    }
                }
            }
        });
        //window.scrollTo(0, 0);
    }

    function showDemoAppointment(page) {
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        $.ajax({
            url: "pagination/demo_appointment.php",
            type: "GET",
            data: {
                search_text: '',
                page: page,
                master_id: PK_USER_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#demo_appointment_list').html(result)
            }
        });
        window.scrollTo(0, 0);
    }

    function showBillingList(page) {
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        $.ajax({
            url: "pagination/billing.php",
            type: "GET",
            data: {
                search_text: '',
                page: page,
                master_id: PK_USER_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#billing_list').html(result)
            }
        });
        window.scrollTo(0, 0);
    }

    function showLedgerList(page) {
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        $.ajax({
            url: "pagination/ledger.php",
            type: "GET",
            data: {
                search_text: '',
                page: page,
                master_id: PK_USER_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#ledger_list').html(result)
            }
        });
        window.scrollTo(0, 0);
    }

    function editpage(param) {
        var id = $(param).val();
        var master_id = $(param).find(':selected').data('master_id');
        window.location.href = "customer.php?id=" + id + "&master_id=" + master_id;

    }
</script>
<script>
    $(document).ready(function() {
        $('#CUSTOMER_ID').on('blur', function() {
            const CUSTOMER_ID = $(this).val().trim();
            let PK_USER = $('.PK_USER').val()
            if (CUSTOMER_ID != '' && PK_USER == '') {
                $.ajax({
                    url: 'ajax/username_checker.php',
                    type: 'post',
                    data: {
                        CUSTOMER_ID: CUSTOMER_ID,
                        PK_USER: PK_USER
                    },
                    success: function(response) {
                        $('#uname_result').html(response);
                        if (response == '') {
                            $('#submit').removeAttr('disabled')
                        } else {
                            $('#submit').attr('disabled', 'disabled')
                        }
                    }
                });
            } else {
                $("#uname_result").html("");
            }
        });
    });
</script>

<script>
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
</script>

<script>
    function openWalletModel() {
        $('#wallet_payment_model').modal('show');
    }
</script>

<script>
    function ConfirmPosted(PK_APPOINTMENT_MASTER) {
        var conf = confirm("Are you sure you want to Post it?");
        if (conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'updateAppointmentData',
                    PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                },
                success: function(data) {
                    window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=enrollment';
                }
            });
        }
    }

    function ConfirmUnposted(PK_APPOINTMENT_MASTER) {
        var conf = confirm("Are you sure you want to Unpost it?");
        if (conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'updateAppointmentDataUnpost',
                    PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                },
                success: function(data) {
                    window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=enrollment';
                }
            });
        }
    }

    function getSavedCreditCardList() {
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        $.ajax({
            url: "ajax/get_credit_card_list.php",
            type: 'POST',
            data: {
                PK_USER_MASTER: PK_USER_MASTER,
                call_from: 'customer_credit_card'
            },
            success: function(data) {
                $('#saved_credit_card_list').slideDown().html(data);
                $('#credit_card_loader').hide();
                saveCreditCard();
            }
        });
    }

    function saveCreditCard() {
        let PK_USER = $('.PK_USER').val();
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        $.ajax({
            url: "includes/save_credit_card.php",
            type: 'POST',
            data: {
                PK_USER: PK_USER,
                PK_USER_MASTER: PK_USER_MASTER
            },
            success: function(data) {
                $('#add_credit_card_div').slideDown().html(data);
            }
        });
    }

    function getWalletDetails() {
        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
        $.ajax({
            url: "ajax/get_wallet_details.php",
            type: 'POST',
            data: {
                PK_USER_MASTER: PK_USER_MASTER
            },
            success: function(data) {
                $('#wallet_details').slideDown().html(data);
            }
        });
    }

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
                                window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=wallet';
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

    $('#verify_password_form').on('submit', function(event) {
        event.preventDefault();
        let pk_user = $('.PK_USER').val();
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
                        enrollmentLoadMore('normal');
                    });
                } else {
                    $('#due_date_verify_password_error').text("Incorrect Password").slideDown();
                }
            }
        });
    });
</script>


<script>
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
                let PK_USER = $('#PK_USER').val();
                let PK_USER_MASTER = $('#PK_USER_MASTER').val();
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
                                window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=credit_card';
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

<script>
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
                        window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=enrollment';
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
</script>



<!-- JavaScript for Popup -->
<script>
    function showPopup(type, src) {
        let popup = document.getElementById("mediaPopup");
        let image = document.getElementById("popupImage");
        let video = document.getElementById("popupVideo");
        let videoSource = document.getElementById("popupVideoSource");

        if (type === 'image') {
            image.src = src;
            image.style.display = "block";
            video.style.display = "none";
        } else if (type === 'video') {
            videoSource.src = src;
            video.load();
            video.style.display = "block";
            image.style.display = "none";
        }

        popup.style.display = "flex";

        // Add event listener to detect ESC key press
        document.addEventListener("keydown", escClose);
    }

    function closePopup() {
        document.getElementById("mediaPopup").style.display = "none";
        document.removeEventListener("keydown", escClose); // Remove listener when popup is closed
    }

    // Function to detect ESC key press and close the popup
    function escClose(event) {
        if (event.key === "Escape") {
            closePopup();
        }
    }

    // Disable right-click on images and videos
    document.addEventListener("contextmenu", function(event) {
        let target = event.target;
        if (target.tagName === "IMG" || target.tagName === "VIDEO") {
            event.preventDefault(); // Prevent right-click menu
        }
    });

    // Optional: Disable right-click for the whole page
    // Uncomment the line below if you want to block right-click everywhere
    // document.addEventListener("contextmenu", (event) => event.preventDefault());
</script>

</html>