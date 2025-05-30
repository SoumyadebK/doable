<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $upload_path;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

if (empty($_GET['id']))
    $title = "Add Enrollment";
else
    $title = "Edit Enrollment";

if (!empty($_GET['source']) && $_GET['source'] === 'customer') {
    $header = 'customer.php?id=' . $_GET['id_customer'] . '&master_id=' . $_GET['master_id_customer'] . '&tab=enrollment';
} else {
    $header = '';
}

$PK_ENROLLMENT_MASTER = 0;
$ENROLLMENT_NAME = '';
$ENROLLMENT_DATE = date('m/d/Y');
$PK_LOCATION = '';
$PK_PACKAGE = '';
$TOTAL = '';
$FINAL_AMOUNT = '';
$PK_AGREEMENT_TYPE = '';
$PK_DOCUMENT_LIBRARY = '';
$AGREEMENT_PDF_LINK = '';
$ENROLLMENT_BY_ID = $_SESSION['PK_USER'];
$ENROLLMENT_BY_PERCENTAGE = '';
$MEMO = '';
$ACTIVE = '';

$PK_ENROLLMENT_BILLING = '';
$BILLING_REF = '';
$BILLING_DATE = '';
$DOWN_PAYMENT = 0.00;
$BALANCE_PAYABLE = 0.00;
$PAYMENT_METHOD = '';
$PAYMENT_TERM = '';
$NUMBER_OF_PAYMENT = '';
$FIRST_DUE_DATE = '';
$INSTALLMENT_AMOUNT = '';

$PK_ENROLLMENT_PAYMENT = '';
$PK_PAYMENT_TYPE = '';
$AMOUNT = '';
$NAME = '';
$CARD_NUMBER = '';
$SECURITY_CODE = '';
$EXPIRY_DATE = '';
$CHECK_NUMBER = '';
$CHECK_DATE = '';
$NOTE = '';
$CHARGE_TYPE = '';

$PK_USER_MASTER = '';
if (!empty($_GET['master_id_customer'])) {
    $PK_USER_MASTER = $_GET['master_id_customer'];
    $user_location = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $PK_USER_MASTER);
    if ($user_location->RecordCount() > 0) {
        $PK_LOCATION = $user_location->fields['PK_LOCATION'];
    } else {
        $PK_LOCATION = 0;
    }
}

$months = '';
$day = '';
if (!empty($_GET['id'])) {
    $res = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_enrollments.php");
        exit;
    }
    $PK_ENROLLMENT_MASTER = $_GET['id'];
    $PK_USER_MASTER = $res->fields['PK_USER_MASTER'];
    $ENROLLMENT_NAME = $res->fields['ENROLLMENT_NAME'];
    $ENROLLMENT_DATE = date('m/d/Y', strtotime($res->fields['ENROLLMENT_DATE']));
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $PK_PACKAGE = $res->fields['PK_PACKAGE'];
    $CHARGE_TYPE = $res->fields['CHARGE_TYPE'];
    $EXPIRY_DATE = new DateTime($res->fields['EXPIRY_DATE']);
    $PK_AGREEMENT_TYPE = $res->fields['PK_AGREEMENT_TYPE'];
    $PK_DOCUMENT_LIBRARY = $res->fields['PK_DOCUMENT_LIBRARY'];
    $AGREEMENT_PDF_LINK = $res->fields['AGREEMENT_PDF_LINK'];
    $ENROLLMENT_BY_ID = $res->fields['ENROLLMENT_BY_ID'];
    $ENROLLMENT_BY_PERCENTAGE = $res->fields['ENROLLMENT_BY_PERCENTAGE'];
    $MEMO = $res->fields['MEMO'];
    $ACTIVE = $res->fields['ACTIVE'];

    $CREATED_ON = new DateTime($res->fields['CREATED_ON']);
    $interval = $EXPIRY_DATE->diff($CREATED_ON);
    $months = intval($interval->days / 30);

    $day = $EXPIRY_DATE->format('d');

    $billing_data = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER` = '$_GET[id]'");
    if ($billing_data->RecordCount() > 0) {
        $PK_ENROLLMENT_BILLING = $billing_data->fields['PK_ENROLLMENT_BILLING'];
        $BILLING_REF = $billing_data->fields['BILLING_REF'];
        $BILLING_DATE = $billing_data->fields['BILLING_DATE'];
        $DOWN_PAYMENT = $billing_data->fields['DOWN_PAYMENT'];
        $BALANCE_PAYABLE = $billing_data->fields['BALANCE_PAYABLE'];
        $PAYMENT_METHOD = $billing_data->fields['PAYMENT_METHOD'];
        $PAYMENT_TERM = $billing_data->fields['PAYMENT_TERM'];
        $NUMBER_OF_PAYMENT = $billing_data->fields['NUMBER_OF_PAYMENT'];
        $FIRST_DUE_DATE = $billing_data->fields['FIRST_DUE_DATE'];
        $INSTALLMENT_AMOUNT = $billing_data->fields['INSTALLMENT_AMOUNT'];
    }

    $payment_data = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_PAYMENT` WHERE `PK_ENROLLMENT_MASTER` = '$_GET[id]'");
    if ($payment_data->RecordCount() > 0) {
        $PK_ENROLLMENT_PAYMENT = $payment_data->fields['PK_ENROLLMENT_PAYMENT'];
        $PK_PAYMENT_TYPE = $payment_data->fields['PK_PAYMENT_TYPE'];
        $AMOUNT = $payment_data->fields['AMOUNT'];
        $NOTE = $payment_data->fields['NOTE'];
    }
}

$user_payment_gateway = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER, DOA_LOCATION.PAYMENT_GATEWAY_TYPE, DOA_LOCATION.SECRET_KEY, DOA_LOCATION.PUBLISHABLE_KEY, DOA_LOCATION.ACCESS_TOKEN, DOA_LOCATION.APP_ID, DOA_LOCATION.LOCATION_ID, DOA_LOCATION.LOGIN_ID, DOA_LOCATION.TRANSACTION_KEY, DOA_LOCATION.AUTHORIZE_CLIENT_KEY FROM DOA_LOCATION INNER JOIN DOA_USER_MASTER ON DOA_LOCATION.PK_LOCATION = DOA_USER_MASTER.PRIMARY_LOCATION_ID WHERE DOA_USER_MASTER.PK_USER_MASTER = '$PK_USER_MASTER'");
if ($user_payment_gateway->RecordCount() > 0) {
    $PAYMENT_GATEWAY = $user_payment_gateway->fields['PAYMENT_GATEWAY_TYPE'];
    $SQUARE_APP_ID = $user_payment_gateway->fields['APP_ID'];
    $SQUARE_LOCATION_ID = $user_payment_gateway->fields['LOCATION_ID'];
    $ACCESS_TOKEN = $user_payment_gateway->fields['ACCESS_TOKEN'];
    $PUBLISHABLE_KEY = $user_payment_gateway->fields['PUBLISHABLE_KEY'];
    $SECRET_KEY = $user_payment_gateway->fields['SECRET_KEY'];
    $LOGIN_ID = $user_payment_gateway->fields['LOGIN_ID'];
    $TRANSACTION_KEY = $user_payment_gateway->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY = $user_payment_gateway->fields['AUTHORIZE_CLIENT_KEY'];
} else {
    $account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
    $PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];
    $SQUARE_APP_ID             = $account_data->fields['APP_ID'];
    $SQUARE_LOCATION_ID     = $account_data->fields['LOCATION_ID'];
    $ACCESS_TOKEN             = $account_data->fields['ACCESS_TOKEN'];
    $PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];
    $SECRET_KEY = $account_data->fields['SECRET_KEY'];
    $LOGIN_ID = $account_data->fields['LOGIN_ID'];
    $TRANSACTION_KEY = $account_data->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY = $account_data->fields['AUTHORIZE_CLIENT_KEY'];
}

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

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>
<link href="https://fonts.googleapis.com/css2?family=PT+Mono&display=swap" rel="stylesheet">

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <div class="container-fluid body_content">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="all_enrollments.php">All Enrollments</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="active"> <a class="nav-link active" data-bs-toggle="tab" id="enrollment_link" href="#enrollment" role="tab"><span class="hidden-sm-up"><i class="ti-pencil-alt"></i></span> <span class="hidden-xs-down">Enrollment</span></a> </li>
                                    <li> <a class="nav-link" data-bs-toggle="tab" id="billing_link" href="#billing" role="tab" onclick="goToPaymentTab()"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>
                                    <li> <a class="nav-link" data-bs-toggle="tab" id="ledger_link" href="#ledger" role="tab" onclick="goToLedgerTab()"><span class="hidden-sm-up"><i class="ti-book"></i></span> <span class="hidden-xs-down">Ledger</span></a> </li>
                                    <?php if (!empty($_GET['id'])) { ?>
                                        <li> <a class="nav-link" data-bs-toggle="tab" id="history_link" href="#history" role="tab"><span class="hidden-sm-up"><i class="ti-book"></i></span> <span class="hidden-xs-down">History</span></a> </li>
                                        <?php if ($AGREEMENT_PDF_LINK != '' && $AGREEMENT_PDF_LINK != null) { ?>
                                            <li> <a class="nav-link" href="../<?= $upload_path ?>/enrollment_pdf/<?= $AGREEMENT_PDF_LINK ?>" target="_blank"><span class="hidden-sm-up"><i class="ti-file"></i></span> <span class="hidden-xs-down">PDF Agreement</span></a> </li>
                                        <?php } ?>
                                    <?php } ?>
                                </ul>


                                <!-- Enrollment Tab panes -->
                                <div class="tab-content tabcontent-border" style="margin-bottom: -35px">
                                    <div class="tab-pane active" id="enrollment" role="tabpanel">
                                        <form class="form-material form-horizontal" id="enrollment_form">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveEnrollmentData">
                                            <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER" value="<?= (empty($_GET['id'])) ? '' : $_GET['id'] ?>">
                                            <div class="p-20" style="margin-top: -10px">
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div>
                                                            <label class="form-label">Customer<span class="text-danger">*</span></label><br>
                                                            <select required name="PK_USER_MASTER" id="PK_USER_MASTER" onchange="selectThisCustomer(this);">
                                                                <option>Select Customer</option>
                                                                <?php
                                                                $row = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER, DOA_USER_MASTER.PRIMARY_LOCATION_ID FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE (DOA_USER_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") OR DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (" . $DEFAULT_LOCATION_ID . ")) AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.FIRST_NAME ASC");
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_USER_MASTER']; ?>" data-customer_id="<?= $row->fields['PK_USER_MASTER'] ?>" data-pk_user="<?= $row->fields['PK_USER'] ?>" data-location_id="<?= ((empty($_GET['id'])) ? $row->fields['PRIMARY_LOCATION_ID'] : $PK_LOCATION) ?>" data-customer_name="<?= $row->fields['NAME'] ?>" <?= ($PK_USER_MASTER == $row->fields['PK_USER_MASTER']) ? 'selected' : '' ?>><?= $row->fields['NAME'] . ' (' . $row->fields['USER_NAME'] . ')' . ' (' . $row->fields['PHONE'] . ')' . ' (' . $row->fields['EMAIL_ID'] . ')' ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">Location<span class="text-danger">*</span></label>
                                                            <select class="form-control" required name="PK_LOCATION" id="PK_LOCATION" onchange="showEnrollmentInstructor();">
                                                                <option value="">Select Location</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">Enrollment Name</label>
                                                            <input type="text" id="ENROLLMENT_NAME" name="ENROLLMENT_NAME" class="form-control" placeholder="Enter Enrollment Name" value="<?= $ENROLLMENT_NAME ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">Enrollment Date</label>
                                                            <input type="text" id="ENROLLMENT_DATE" name="ENROLLMENT_DATE" class="form-control datepicker-normal" placeholder="Enter Enrollment Date" value="<?= $ENROLLMENT_DATE ?>" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row <?= ($PK_ENROLLMENT_MASTER > 0) ? 'disabled_div' : '' ?>" style="margin-top: -15px">
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="form-label">Packages</label>
                                                            <select class="form-control PK_PACKAGE" name="PK_PACKAGE" id="PK_PACKAGE" onchange="selectThisPackage(this)">
                                                                <option value="">Select Package</option>
                                                                <?php
                                                                $row = $db_account->Execute("SELECT DISTINCT DOA_PACKAGE.PK_PACKAGE, DOA_PACKAGE.PACKAGE_NAME, DOA_PACKAGE.EXPIRY_DATE FROM DOA_PACKAGE LEFT JOIN DOA_PACKAGE_LOCATION ON DOA_PACKAGE.PK_PACKAGE = DOA_PACKAGE_LOCATION.PK_PACKAGE WHERE DOA_PACKAGE_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 ORDER BY SORT_ORDER ASC");
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_PACKAGE']; ?>" data-expiry_date="<?= $row->fields['EXPIRY_DATE'] ?>" <?= ($row->fields['PK_PACKAGE'] == $PK_PACKAGE) ? 'selected' : '' ?>><?= $row->fields['PACKAGE_NAME'] ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <?php
                                                    $payment_gateway_type = $db->Execute("SELECT PAYMENT_GATEWAY_TYPE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER=" . $_SESSION['PK_ACCOUNT_MASTER']);
                                                    if ($payment_gateway_type->RecordCount() > 0) { ?>
                                                        <div class="col-4 m-t-15">
                                                            <label class="m-l-40" for="Session"><input type="checkbox" id="Session" name="CHARGE_TYPE" class="form-check-inline charge_type" value="Session" <?= ($CHARGE_TYPE == 'Session') ? 'checked' : '' ?> onchange="chargeBySessions(this);">Charge by sessions</label>
                                                            <label class="m-l-40" for="Membership"><input type="checkbox" id="Membership" name="CHARGE_TYPE" class="form-check-inline charge_type" value="Membership" <?= ($CHARGE_TYPE == 'Membership') ? 'checked' : '' ?> onchange="chargeBySessions(this);">Membership</label>
                                                        </div>
                                                    <?php } ?>
                                                    <div class="col-4">
                                                        <div class="form-group session_base" style="display: <?php echo ($CHARGE_TYPE == 'Session') ? ' ' : 'none' ?>">
                                                            <label class="form-label">Expiration Date</label>
                                                            <select class="form-control" name="EXPIRY_DATE" id="EXPIRY_DATE">
                                                                <option value="">Select Expiration Date</option>
                                                                <option value="1" <?= ($months == 1) ? 'selected' : '' ?>>30 days</option>
                                                                <option value="2" <?= ($months == 2) ? 'selected' : '' ?>>60 days</option>
                                                                <option value="3" <?= ($months == 3) ? 'selected' : '' ?>>90 days</option>
                                                                <option value="6" <?= ($months == 6) ? 'selected' : '' ?>>180 days</option>
                                                                <option value="12" <?= ($months == 12) ? 'selected' : '' ?>>365 days</option>
                                                            </select>
                                                        </div>

                                                        <div class="form-group member_base" style="display: <?php echo ($CHARGE_TYPE == 'Membership') ? ' ' : 'none' ?>">
                                                            <label class="form-label">Auto Renewal</label>
                                                            <select class="form-control" name="EXPIRY_DATE" id="EXPIRY_DATE">
                                                                <option value="">Select Auto Renewal</option>
                                                                <option value="1" <?= ($day == 1) ? 'selected' : '' ?>>1st of every month</option>
                                                                <option value="15" <?= ($day == 15) ? 'selected' : '' ?>>15th of every month</option>
                                                                <option value="0" <?= ($day != 1 && $day != 15) ? 'selected' : '' ?>>Same as created date</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="card-body" style="margin-top: -15px">
                                                    <div class="row">
                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <label class="form-label">Services</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Service Codes</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <label class="form-label">Service Details</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Number of Sessions</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Price Per Sessions</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Total</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Discount Type</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Discount</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Final Amount</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <?php
                                                    $total = 0;
                                                    if (!empty($_GET['id'])) {
                                                        $enrollment_service_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = '$_GET[id]'");
                                                        while (!$enrollment_service_data->EOF) {
                                                            $total += $enrollment_service_data->fields['FINAL_AMOUNT']; ?>
                                                            <div class="row <?= ($PK_ENROLLMENT_MASTER > 0) ? 'disabled_div' : '' ?>">
                                                                <div class="col-2">
                                                                    <div class="form-group">
                                                                        <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                                            <option>Select Service</option>
                                                                            <?php
                                                                            $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` JOIN DOA_SERVICE_LOCATION ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_LOCATION.PK_SERVICE_MASTER WHERE DOA_SERVICE_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND ACTIVE = 1 AND IS_DELETED = 0 ORDER BY DOA_SERVICE_MASTER.SERVICE_NAME ASC");
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>" <?= ($row->fields['PK_SERVICE_MASTER'] == $enrollment_service_data->fields['PK_SERVICE_MASTER']) ? 'selected' : '' ?>><?= $row->fields['SERVICE_NAME'] ?></option>
                                                                            <?php $row->MoveNext();
                                                                            } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                                            <?php
                                                                            $row = $db_account->Execute("SELECT * FROM `DOA_SERVICE_CODE` WHERE `PK_SERVICE_MASTER` = " . $enrollment_service_data->fields['PK_SERVICE_MASTER']);
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_SERVICE_CODE']; ?>" data-details="<?= $row->fields['DESCRIPTION'] ?>" data-price="<?= $row->fields['PRICE'] ?>" <?= ($row->fields['PK_SERVICE_CODE'] == $enrollment_service_data->fields['PK_SERVICE_CODE']) ? 'selected' : '' ?>><?= $row->fields['SERVICE_CODE'] ?></option>
                                                                            <?php $row->MoveNext();
                                                                            } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-2">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" value="<?= $enrollment_service_data->fields['SERVICE_DETAILS'] ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" value="<?= $enrollment_service_data->fields['NUMBER_OF_SESSION'] ?>" onkeyup="calculateServiceTotal(this)">
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" value="<?= ($enrollment_service_data->fields['TOTAL'] / $enrollment_service_data->fields['NUMBER_OF_SESSION']) ?>" onkeyup="calculateServiceTotal(this)">
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control TOTAL" name="TOTAL[]" value="<?= $enrollment_service_data->fields['TOTAL'] ?>" onkeyup="calculateServiceTotal(this)" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                                                                            <option value="">Select</option>
                                                                            <option value="1" <?= ($enrollment_service_data->fields['DISCOUNT_TYPE'] == 1) ? 'selected' : '' ?>>Fixed</option>
                                                                            <option value="2" <?= ($enrollment_service_data->fields['DISCOUNT_TYPE'] == 2) ? 'selected' : '' ?>>Percent</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" value="<?= $enrollment_service_data->fields['DISCOUNT'] ?>" onkeyup="calculateServiceTotal(this)">
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" value="<?= $enrollment_service_data->fields['FINAL_AMOUNT'] ?>" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1" style="width: 5%;">
                                                                    <div class="form-group">
                                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php $enrollment_service_data->MoveNext();
                                                        } ?>
                                                    <?php } else { ?>
                                                        <div class="row individual_service_div">
                                                            <div class="col-2">
                                                                <div class="form-group">
                                                                    <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                                        <option>Select</option>
                                                                        <?php
                                                                        $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` JOIN DOA_SERVICE_LOCATION ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_LOCATION.PK_SERVICE_MASTER WHERE DOA_SERVICE_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND IS_DELETED = 0 ORDER BY DOA_SERVICE_MASTER.SERVICE_NAME ASC");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= $row->fields['SERVICE_NAME'] ?></option>
                                                                        <?php $row->MoveNext();
                                                                        } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                                        <option value="">Select</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-2">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]">
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)">
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);">
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control TOTAL" name="TOTAL[]" onkeyup="calculateServiceTotal(this)" readonly>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                                                                        <option value="">Select</option>
                                                                        <option value="1">Fixed</option>
                                                                        <option value="2">Percent</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" onkeyup="calculateServiceTotal(this)">
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" readonly>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>

                                                    <div id="append_service_div">

                                                    </div>
                                                </div>

                                                <div class="col-3 <?= ($PK_ENROLLMENT_MASTER > 0) ? 'disabled_div' : '' ?>" style="margin-left: 75%; margin-top: -40px;">
                                                    <div class="form-group">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <label class="form-label" style="float: right; margin-top: 10px;">Total</label>
                                                            </div>
                                                            <div class="col-md-8">
                                                                <input type="text" class="form-control TOTAL_AMOUNT" value="<?= number_format((float)$total, 2, '.', ''); ?>" readonly style="width: 44%;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row add_more <?= ($PK_ENROLLMENT_MASTER > 0) ? 'disabled_div' : '' ?>" style="margin-top: -15px">
                                                    <div class="col-12">
                                                        <div class="form-group" style="float: right; display: <?= $CHARGE_TYPE == 'Session' ? 'none' : '' ?>">
                                                            <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addMoreServices();">Add More</a>
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="row" style="margin-top: -15px">
                                                    <!--<div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">Agreement Type<span class="text-danger">*</span></label>
                                                        <select class="form-control" required name="PK_AGREEMENT_TYPE" id="PK_AGREEMENT_TYPE">
                                                            <option value="">Select Agreement Type</option>
                                                            <?php
                                                            /*                                                            $row = $db->Execute("SELECT PK_AGREEMENT_TYPE, AGREEMENT_TYPE FROM DOA_AGREEMENT_TYPE WHERE ACTIVE = 1 ORDER BY PK_AGREEMENT_TYPE");
                                                            while (!$row->EOF) { */ ?>
                                                                <option value="<?php /*echo $row->fields['PK_AGREEMENT_TYPE'];*/ ?>" <?php /*=($PK_AGREEMENT_TYPE == $row->fields['PK_AGREEMENT_TYPE'])?'selected':''*/ ?>><?php /*=$row->fields['AGREEMENT_TYPE']*/ ?></option>
                                                                <?php /*$row->MoveNext(); } */ ?>
                                                        </select>
                                                    </div>
                                                </div>-->
                                                    <div class="col-2">
                                                        <div class="form-group">
                                                            <label class="form-label">Agreement Template<span class="text-danger">*</span></label>
                                                            <select class="form-control" required name="PK_DOCUMENT_LIBRARY" id="PK_DOCUMENT_LIBRARY">
                                                                <option value="">Select Agreement Template</option>
                                                                <?php
                                                                $row = $db_account->Execute("SELECT PK_DOCUMENT_LIBRARY, DOCUMENT_NAME FROM DOA_DOCUMENT_LIBRARY WHERE ACTIVE = 1 ORDER BY PK_DOCUMENT_LIBRARY");
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_DOCUMENT_LIBRARY']; ?>" <?= ($PK_DOCUMENT_LIBRARY == $row->fields['PK_DOCUMENT_LIBRARY']) ? 'selected' : '' ?>><?= $row->fields['DOCUMENT_NAME'] ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </select>
                                                            <?php /*if ($AGREEMENT_PDF_LINK != '' && $AGREEMENT_PDF_LINK != null) { */ ?><!--
                                                            <a href="../<?php /*=$upload_path*/ ?>/enrollment_pdf/<?php /*=$AGREEMENT_PDF_LINK*/ ?>" target="_blank">View Agreement</a>
                                                        --><?php /*} */ ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-2">
                                                        <div class="form-group">
                                                            <label class="form-label">Enrollment By<span class="text-danger">*</span></label>
                                                            <select class="form-control" required name="ENROLLMENT_BY_ID" id="ENROLLMENT_BY_ID">
                                                                <option value="">Select</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-2">
                                                        <div class="form-group">
                                                            <label class="form-label">Percentage<span class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="input-group" style="margin-top: -25px">
                                                            <input type="text" class="form-control ENROLLMENT_BY_PERCENTAGE" name="ENROLLMENT_BY_PERCENTAGE" value="<?= $ENROLLMENT_BY_PERCENTAGE ?>">
                                                            <span class="form-control input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="row">
                                                            <div class="col-4">
                                                                <div class="form-group">
                                                                    <label class="form-label"><?= $service_provider_title ?></label>
                                                                </div>
                                                            </div>
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <label class="form-label">Percentage</label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <?php
                                                        if (!empty($_GET['id'])) {
                                                            $enrollment_service_provider_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE_PROVIDER WHERE PK_ENROLLMENT_MASTER = '$_GET[id]'");
                                                            while (!$enrollment_service_provider_data->EOF) { ?>
                                                                <div class="row individual_service_provider_div" style="margin-top: -25px">
                                                                    <div class="row">
                                                                        <div class="col-4">
                                                                            <div class="form-group">
                                                                                <select class="form-control SERVICE_PROVIDER_ID" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID">
                                                                                    <option value="">Select</option>
                                                                                    <?php
                                                                                    $row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY FIRST_NAME");
                                                                                    while (!$row->EOF) { ?>
                                                                                        <option value="<?php echo $row->fields['PK_USER']; ?>" <?= ($row->fields['PK_USER'] == $enrollment_service_provider_data->fields['SERVICE_PROVIDER_ID']) ? 'selected' : '' ?>><?= $row->fields['NAME'] ?></option>
                                                                                    <?php $row->MoveNext();
                                                                                    } ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-4">
                                                                            <div class="input-group">
                                                                                <input type="text" class="form-control SERVICE_PROVIDER_PERCENTAGE" name="SERVICE_PROVIDER_PERCENTAGE[]" value="<?= number_format((float)$enrollment_service_provider_data->fields['SERVICE_PROVIDER_PERCENTAGE'], 2, '.', '') ?>">
                                                                                <span class="form-control input-group-text">%</span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-2">
                                                                            <div class="form-group">
                                                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php $enrollment_service_provider_data->MoveNext();
                                                            } ?>
                                                        <?php } else { ?>
                                                            <div class="row individual_service_provider_div" style="margin-top: -25px">
                                                                <div class="col-4">
                                                                    <div class="form-group">
                                                                        <select class="form-control SERVICE_PROVIDER_ID" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID">
                                                                            <option value=" ">Select</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-4">
                                                                    <div class="input-group">
                                                                        <input type="text" class="form-control SERVICE_PROVIDER_PERCENTAGE" name="SERVICE_PROVIDER_PERCENTAGE[]">
                                                                        <span class="form-control input-group-text">%</span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                    </div>
                                                                </div>
                                                                <div class="col-2">
                                                                    <div class="form-group" style="float: left;">
                                                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addMoreServiceProviders();">Add More</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>

                                                        <div id="append_service_provider_div">

                                                        </div>
                                                    </div>

                                                </div>

                                                <!--<div class="card-body">
                                                <div class="row">
                                                    <div class="col-2">
                                                        <div class="form-group">
                                                            <label class="form-label"><?php /*=$service_provider_title*/ ?></label>
                                                        </div>
                                                    </div>
                                                    <div class="col-2">
                                                        <div class="form-group">
                                                            <label class="form-label">Percentage</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-1">
                                                        <div class="form-group" style="float: right">
                                                            <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addMoreServiceProviders();">Add More</a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php
                                                /*                                                if(!empty($_GET['id'])) {
                                                $enrollment_service_provider_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE_PROVIDER WHERE PK_ENROLLMENT_MASTER = '$_GET[id]'");
                                                while (!$enrollment_service_provider_data->EOF) { */ ?>
                                                    <div class="row individual_service_provider_div">
                                                        <div class="row">
                                                            <div class="col-2">
                                                                <div class="form-group">
                                                                    <select class="form-control SERVICE_PROVIDER_ID" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID">
                                                                        <option value="">Select</option>
                                                                        <?php
                                                                        /*                                                                        $row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY FIRST_NAME");
                                                                        while (!$row->EOF) { */ ?>
                                                                            <option value="<?php /*echo $row->fields['PK_USER'];*/ ?>" <?php /*=($row->fields['PK_USER'] == $enrollment_service_provider_data->fields['SERVICE_PROVIDER_ID'])?'selected':''*/ ?>><?php /*=$row->fields['NAME']*/ ?></option>
                                                                        <?php /*$row->MoveNext(); } */ ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-2">
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control SERVICE_PROVIDER_PERCENTAGE" name="SERVICE_PROVIDER_PERCENTAGE[]" value="<?php /*=number_format((float)$enrollment_service_provider_data->fields['SERVICE_PROVIDER_PERCENTAGE'], 2, '.', '')*/ ?>">
                                                                    <span class="form-control input-group-text">%</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php /*$enrollment_service_provider_data->MoveNext(); } */ ?>
                                                <?php /*} else { */ ?>
                                                    <div class="row individual_service_provider_div">
                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <select class="form-control SERVICE_PROVIDER_ID" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID">
                                                                    <option value=" ">Select</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="input-group">
                                                                <input type="text" class="form-control SERVICE_PROVIDER_PERCENTAGE" name="SERVICE_PROVIDER_PERCENTAGE[]">
                                                                <span class="form-control input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php /*} */ ?>

                                                <div id="append_service_provider_div">

                                                </div>
                                            </div>-->

                                                <div class="row" style="margin-top: -15px">
                                                    <div class="col-8">
                                                        <div class="form-group">
                                                            <label class="form-label">Memo</label>
                                                            <textarea class="form-control" name="MEMO" rows="1"><?= $MEMO ?></textarea>
                                                        </div>
                                                    </div>
                                                    <!--<div class="col-7">
                                                    <div class="form-group" style="float: right; margin-top: -15px">
                                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addMoreServiceProviders();">Add More</a>
                                                    </div>
                                                </div>-->
                                                </div>

                                                <?php if (!empty($_GET['id'])) { ?>
                                                    <div class="row" style="margin-bottom: 15px;">
                                                        <div class="col-6">
                                                            <div class="col-md-2">
                                                                <label>Active</label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>

                                                <?php if ($_SESSION['PK_ROLES'] != 5) { ?>
                                                    <div class="form-group" style="margin-top: -15px">
                                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?= ($PK_ENROLLMENT_MASTER > 0) ? 'Save' : 'Continue' ?></button>
                                                        <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </form>
                                    </div>

                                    <!--Confirm Model-->
                                    <div class="modal fade" id="confirm_modal" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <div>
                                                                <input type="hidden" id="is_confirm" value="0">
                                                                <label>Are you sure you want to proceed without selecting <?= $service_provider_title ?> ?</label>
                                                                <button type="button" class="btn btn-info waves-effect waves-light m-l-20 text-white" onclick="$('#is_confirm').val(1); $('#enrollment_form').submit();">Yes</button>
                                                                <button type="button" class="btn btn-danger waves-effect waves-light m-l-10 text-white" data-bs-dismiss="modal" aria-label="No">No</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!--Billing Tab-->
                                    <div class="tab-pane <?= ($PK_ENROLLMENT_BILLING > 0) ? 'disabled_div' : '' ?>" id="billing" role="tabpanel">
                                        <div class="card">
                                            <div class="card-body">
                                                <form class="form-material form-horizontal" id="billing_form">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveEnrollmentBillingData">
                                                    <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER" value="<?= (empty($_GET['id'])) ? '' : $_GET['id'] ?>">
                                                    <input type="hidden" name="PK_ENROLLMENT_BILLING" class="PK_ENROLLMENT_BILLING" value="<?= $PK_ENROLLMENT_BILLING ?>">
                                                    <div class="p-20" style="margin-top: -30px; margin-bottom: -30px">
                                                        <div class="row" id="payment_tab_div">
                                                            <!--Data coming from ajax-->
                                                        </div>

                                                        <div class="row" style="margin-top: -55px;">
                                                            <h4><b>Payment Plans</b></h4>
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Billing Ref #</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="BILLING_REF" id="BILLING_REF" class="form-control" value="<?= $BILLING_REF ?>">
                                                                    </div>
                                                                </div>
                                                            </div>


                                                            <div class="row">
                                                                <div class="col-6" style="margin-top: -15px">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Payment Method</label>
                                                                        <div class="col-md-12">
                                                                            <div class="row">
                                                                                <div class="col-md-3 one_time">
                                                                                    <label><input type="radio" class="form-check-inline PAYMENT_METHOD" name="PAYMENT_METHOD" value="One Time" <?= ($PAYMENT_METHOD == 'One Time') ? 'checked' : '' ?> required>One Time</label>
                                                                                </div>
                                                                                <div class="col-md-4 payment_plans">
                                                                                    <label><input type="radio" class="form-check-inline PAYMENT_METHOD" name="PAYMENT_METHOD" value="Payment Plans" <?= ($PAYMENT_METHOD == 'Payment Plans') ? 'checked' : '' ?> required>Payment Plans</label>
                                                                                </div>
                                                                                <div class="col-md-5 flexible_payments">
                                                                                    <label><input type="radio" class="form-check-inline PAYMENT_METHOD" name="PAYMENT_METHOD" value="Flexible Payments" <?= ($PAYMENT_METHOD == 'Flexible Payments') ? 'checked' : '' ?> required>Flexible Payments</label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <!--<div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Amount</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" id="AMOUNT_SHOW" value="<?php /*=$INSTALLMENT_AMOUNT*/ ?>" class="form-control">
                                                                    </div>
                                                                </div>
                                                            </div>-->
                                                            </div>
                                                            <div class="row" style="margin-top: -15px">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Billing Date</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="BILLING_DATE" id="BILLING_DATE" value="<?= ($BILLING_DATE == '') ? date('m/d/Y') : date('m/d/Y', strtotime($BILLING_DATE)) ?>" class="form-control datepicker-normal">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-3" id="down_payment_div" style="display: <?= ($PAYMENT_METHOD == 'One Time') ? 'none' : '' ?>">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Down Payment</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="DOWN_PAYMENT" id="DOWN_PAYMENT" value="<?= $DOWN_PAYMENT ?>" class="form-control" onkeyup="calculatePayment()">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Balance Payable</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="BALANCE_PAYABLE" id="BALANCE_PAYABLE" value="<?= $BALANCE_PAYABLE ?>" class="form-control" readonly>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row payment_method_div" id="payment_plans_div" style="display: <?= ($PAYMENT_METHOD == 'Payment Plans') ? '' : 'none' ?>;">
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Payment Term</label>
                                                                        <div class="col-md-12">
                                                                            <select class="form-control" name="PAYMENT_TERM" id="PAYMENT_TERM">
                                                                                <option value="">Select</option>
                                                                                <option value="Monthly" <?= ($PAYMENT_TERM == 'Monthly') ? 'selected' : '' ?>>Monthly</option>
                                                                                <option value="Quarterly" <?= ($PAYMENT_TERM == 'Quarterly') ? 'selected' : '' ?>>Quarterly</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Number of Payments</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="NUMBER_OF_PAYMENT" id="NUMBER_OF_PAYMENT" value="<?= $NUMBER_OF_PAYMENT ?>" class="form-control" onkeyup="calculatePaymentPlans();">
                                                                        </div>
                                                                        <p id="number_of_payment_error" style="color: red; display: none; font-size: 10px;">This value should be a whole number. Please correct</p>
                                                                    </div>
                                                                </div>
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">First Scheduled Payment Date</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="FIRST_DUE_DATE" id="FIRST_DUE_DATE" value="<?= ($FIRST_DUE_DATE) ? date('m/d/Y', strtotime($FIRST_DUE_DATE)) : '' ?>" class="form-control datepicker-future">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Installment Amount</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="INSTALLMENT_AMOUNT" id="INSTALLMENT_AMOUNT" value="<?= $INSTALLMENT_AMOUNT ?>" class="form-control" onkeyup="calculateNumberOfPayment(this)">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row payment_method_div" id="flexible_plans_div" style="display: <?= ($PAYMENT_METHOD == 'Flexible Payments') ? '' : 'none' ?>">
                                                                <div class="row">
                                                                    <div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Next Payment Dates</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Amount</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-3" style="margin-top: -30px;">
                                                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="addMorePayments();">Add More</a>
                                                                    </div>
                                                                </div>
                                                                <?php
                                                                if (!empty($_GET['id'])) {
                                                                    $i = 0;
                                                                    $flexible_payment_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_LEDGER WHERE TRANSACTION_TYPE = 'Billing' AND PK_ENROLLMENT_MASTER = '$_GET[id]'");
                                                                    while (!$flexible_payment_data->EOF) {
                                                                        if ($DOWN_PAYMENT > 0 && $i > 0) { ?>
                                                                            <div class="row">
                                                                                <div class="col-3">
                                                                                    <div class="form-group">
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control datepicker-future" value="<?= ($flexible_payment_data->fields['DUE_DATE']) ? date('m/d/Y', strtotime($flexible_payment_data->fields['DUE_DATE'])) : '' ?>" required>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-3">
                                                                                    <div class="form-group">
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control FLEXIBLE_PAYMENT_AMOUNT" value="<?= $flexible_payment_data->fields['BILLED_AMOUNT'] ?>" required>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-3" style="padding-top: 5px;">
                                                                                    <a href="javascript:;" onclick="removeThisAmount(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                                </div>
                                                                            </div>
                                                                    <?php }
                                                                        $i++;
                                                                        $flexible_payment_data->MoveNext();
                                                                    } ?>
                                                                <?php } else { ?>
                                                                    <div class="row">
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control datepicker-future">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control FLEXIBLE_PAYMENT_AMOUNT" onkeyup="calculateBalancePayable(this);">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-3" style="padding-top: 5px;">
                                                                            <a href="javascript:;" onclick="removeThisAmount(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                        </div>
                                                                    </div>
                                                                <?php } ?>
                                                            </div>
                                                        </div>


                                                        <?php if ($PK_ENROLLMENT_BILLING == '') { ?>
                                                            <div class="form-group">
                                                                <a class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: none;" onclick="$('#enrollment_link')[0].click();">Back</a>
                                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: none;">Save & Continue</button>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!--Ledger Tab-->
                                    <div class="tab-pane" id="ledger" role="tabpanel">
                                        <div class="p-20">
                                            <div class="row">
                                                <h4><b>Billing Details</b></h4>
                                                <table id="myTable" class="table table-striped border">
                                                    <thead>
                                                        <tr>
                                                            <th>Due Date</th>
                                                            <th>Transaction Type</th>
                                                            <th>Billed Amount</th>
                                                            <th>Paid Amount</th>
                                                            <th>Balance</th>
                                                            <th>Payment Type</th>
                                                            <th>Description</th>
                                                            <th>Paid</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <?php
                                                        $billed_amount = 0;
                                                        $balance = 0;
                                                        $billing_details = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_LEDGER WHERE PK_ENROLLMENT_MASTER = " . $_GET['id'] . " AND ENROLLMENT_LEDGER_PARENT = 0 ORDER BY DUE_DATE ASC, PK_ENROLLMENT_LEDGER ASC");
                                                        while (!$billing_details->EOF) {
                                                            $billed_amount = $billing_details->fields['BILLED_AMOUNT'];
                                                            $balance = ($billing_details->fields['BILLED_AMOUNT'] + $balance);
                                                        ?>
                                                            <tr>
                                                                <td><?= date('m/d/Y', strtotime($billing_details->fields['DUE_DATE'])) ?></td>
                                                                <td><?= $billing_details->fields['TRANSACTION_TYPE'] ?></td>
                                                                <td><?= $billing_details->fields['BILLED_AMOUNT'] ?></td>
                                                                <td></td>
                                                                <td><?= number_format((float)$balance, 2, '.', '') ?></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td><?= (($billing_details->fields['TRANSACTION_TYPE'] == 'Billing') ? (($billing_details->fields['IS_PAID'] == 1) ? 'YES' : 'NO') : '') ?></td>
                                                                <td>
                                                                    <?php if ($billing_details->fields['IS_PAID'] == 0 && $billing_details->fields['STATUS'] == 'A') { ?>
                                                                        <a href="javascript:" class="btn btn-info waves-effect waves-light m-r-10 text-white myBtn" onclick="payNow(<?= $billing_details->fields['PK_ENROLLMENT_LEDGER'] ?>, <?= $billing_details->fields['BILLED_AMOUNT'] ?>);">Pay Now</a>
                                                                    <?php } ?>
                                                                </td>

                                                            </tr>
                                                            <?php
                                                            $payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.*, DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE PK_ENROLLMENT_LEDGER = " . $billing_details->fields['PK_ENROLLMENT_LEDGER']);
                                                            if ($payment_details->RecordCount() > 0) {
                                                                while (!$payment_details->EOF) {
                                                                    $PK_ENROLLMENT_MASTER = $payment_details->fields['PK_ENROLLMENT_MASTER'];
                                                                    $PK_ENROLLMENT_LEDGER = $payment_details->fields['PK_ENROLLMENT_LEDGER'];
                                                                    if ($payment_details->fields['TYPE'] == 'Payment' && $payment_details->fields['IS_REFUNDED'] == 0) {
                                                                        $balance -= $payment_details->fields['AMOUNT'];
                                                                    }

                                                                    if ($payment_details->fields['TYPE'] == 'Move') {
                                                                        $payment_type = 'Wallet';
                                                                    } elseif ($payment_details->fields['PK_PAYMENT_TYPE'] == '2') {
                                                                        $payment_info = json_decode($payment_details->fields['PAYMENT_INFO']);
                                                                        $payment_type = $payment_details->fields['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                                                                    } elseif (in_array($payment_details->fields['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                                                                        $payment_info = json_decode($payment_details->fields['PAYMENT_INFO']);
                                                                        $payment_type = $payment_details->fields['PAYMENT_TYPE'] . " # " . ((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
                                                                    } elseif ($payment_details->fields['PK_PAYMENT_TYPE'] == '7') {
                                                                        $receipt_number_array = explode(',', $payment_details->fields['RECEIPT_NUMBER']);
                                                                        $payment_type_array = [];
                                                                        foreach ($receipt_number_array as $receipt_number) {
                                                                            $receipt_payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER = '$receipt_number'");
                                                                            if ($receipt_payment_details->fields['PK_PAYMENT_TYPE'] == '2') {
                                                                                $payment_info = json_decode($receipt_payment_details->fields['PAYMENT_INFO']);
                                                                                $payment_type_array[] = $receipt_payment_details->fields['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                                                                            } else {
                                                                                $payment_type_array[] = $receipt_payment_details->fields['PAYMENT_TYPE'];
                                                                            }
                                                                        }
                                                                        $payment_type = implode(', ', $payment_type_array);
                                                                    } else {
                                                                        $payment_type = $payment_details->fields['PAYMENT_TYPE'];
                                                                    } ?>
                                                                    <tr style="color: <?= ($payment_details->fields['IS_PAID'] == 2) ? 'green' : '' ?>">
                                                                        <td><?= date('m/d/Y', strtotime($payment_details->fields['PAYMENT_DATE'])) ?></td>
                                                                        <td><?= $payment_details->fields['TYPE'] ?></td>
                                                                        <td></td>
                                                                        <td style="text-align: right;"><?= $payment_details->fields['AMOUNT'] ?></td>
                                                                        <td></td>
                                                                        <td style="text-align: center;"><?= $payment_type ?></td>
                                                                        <td style="text-align: center;"><?= $payment_details->fields['NOTE'] ?></td>
                                                                        <td><?= (($payment_details->fields['TYPE'] == 'Billing') ? (($payment_details->fields['IS_PAID'] == 1) ? 'YES' : 'NO') : '') ?></td>
                                                                        <td>
                                                                            <a onclick="openReceipt(<?= $PK_ENROLLMENT_MASTER ?>, '<?= $payment_details->fields['RECEIPT_NUMBER'] ?>')" href="javascript:">Receipt</a>
                                                                        </td>
                                                                    </tr>
                                                        <?php $payment_details->MoveNext();
                                                                }
                                                            }
                                                            $billing_details->MoveNext();
                                                        } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!--History Tab-->
                                    <?php if (!empty($_GET['id'])) { ?>
                                        <div class="tab-pane" id="history" role="tabpanel">
                                            <div class="p-20">
                                                <div class="row">
                                                    <table id="myTable" class="table table-striped border">
                                                        <thead>
                                                            <tr>
                                                                <th>Field Name</th>
                                                                <th>From</th>
                                                                <th>To</th>
                                                                <th>Update By</th>
                                                                <th>Time</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $row = $db->Execute("SELECT $account_database.DOA_UPDATE_HISTORY.*, $master_database.DOA_USERS.FIRST_NAME, $master_database.DOA_USERS.LAST_NAME FROM $account_database.DOA_UPDATE_HISTORY INNER JOIN $master_database.DOA_USERS ON $account_database.DOA_UPDATE_HISTORY.EDITED_BY = $master_database.DOA_USERS.PK_USER WHERE $account_database.DOA_UPDATE_HISTORY.CLASS = 'enrollment' AND $account_database.DOA_UPDATE_HISTORY.PRIMARY_KEY = " . $_GET['id'] . " ORDER BY $account_database.DOA_UPDATE_HISTORY.PK_UPDATE_HISTORY DESC");
                                                            while (!$row->EOF) { ?>
                                                                <tr>
                                                                    <td><?= $row->fields['FIELD_NAME'] ?></td>
                                                                    <td><?= $row->fields['FROM_VALUE'] ?></td>
                                                                    <td><?= $row->fields['TO_VALUE'] ?></td>
                                                                    <td><?= $row->fields['FIRST_NAME'] . " " . $row->fields['LAST_NAME'] ?></td>
                                                                    <td><?= $row->fields['EDITED_ON'] ?></td>
                                                                </tr>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
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

    <?php require_once('../includes/footer.php'); ?>

    <!--Payment Model-->
    <?php include('includes/enrollment_payment.php'); ?>


    <script src='https://unpkg.com/popper.js/dist/umd/popper.min.js'></script>
    <script src='https://unpkg.com/tooltip.js/dist/umd/tooltip.min.js'></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#PK_USER_MASTER').trigger("change");
        });

        let ENROLLMENT_BY_ID = parseInt(<?= $ENROLLMENT_BY_ID ?>);

        const appId = '<?= $SQUARE_APP_ID ?>';
        const locationId = '<?= $SQUARE_LOCATION_ID ?>';

        async function initializeCard(payments) {
            if (document.getElementById("card-container") !== null) {
                const card = await payments.card();
                await card.attach('#card-container');
                return card;
            } else {
                return false;
            }
        }

        async function createPayment(token) {
            document.getElementById('sourceId').value = token;
            $('#payment_confirmation_form').submit();

            /*const body = JSON.stringify({
              locationId,
              sourceId: token,
            });

            const paymentResponse = await fetch('payment.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body,
            });

            if (paymentResponse.ok) {
              return paymentResponse.json();
            }

            const errorBody = await paymentResponse.text();
            throw new Error(errorBody);*/

        }

        /*async function tokenize(paymentMethod) {
            const tokenResult = await paymentMethod.tokenize();
            if (tokenResult.status === 'OK') {
                return tokenResult.token;
            } else {
                let errorMessage = `Tokenization failed with status: ${tokenResult.status}`;
                if (tokenResult.errors) {
                    errorMessage += ` and errors: ${JSON.stringify(
                        tokenResult.errors
                    )}`;
                }

                throw new Error(errorMessage);
            }
        }

        // status is either SUCCESS or FAILURE;
        function displayPaymentResults(status) {
            if (document.getElementById("payment-status-container") !== null) {
                const statusContainer = document.getElementById(
                    'payment-status-container'
                );
            } else {
                return false;
            }
            if (status === 'SUCCESS') {
                statusContainer.classList.remove('is-failure');
                statusContainer.classList.add('is-success');
            } else {
                statusContainer.classList.remove('is-success');
                statusContainer.classList.add('is-failure');
            }

            statusContainer.style.visibility = 'visible';
        }

        document.addEventListener('DOMContentLoaded', async function () {
            if (!window.Square) {
                throw new Error('Square.js failed to load properly');
            }

            let payments;
            try {
                payments = window.Square.payments(appId, locationId);
            } catch {
                if (document.getElementById("payment-status-container") !== null) {
                    const statusContainer = document.getElementById(
                        'payment-status-container'
                    );
                } else {
                    return false;
                }
                statusContainer.className = 'missing-credentials';
                statusContainer.style.visibility = 'visible';
                return;
            }

            let card;
            try {
                card = await initializeCard(payments);
            } catch (e) {
                console.error('Initializing Card failed', e);
                return;
            }

            // Checkpoint 2.
            async function handlePaymentMethodSubmission(event, paymentMethod) {
                event.preventDefault();

                try {
                    // disable the submit button as we await tokenization and make a payment request.
                    cardButton.disabled = true;
                    const token = await tokenize(paymentMethod);
                    const paymentResults = await createPayment(token);
                    displayPaymentResults('SUCCESS');

                    console.debug('Payment Success', paymentResults);
                } catch (e) {
                    cardButton.disabled = false;
                    displayPaymentResults('FAILURE');
                    console.error(e.message);
                }
            }

            const cardButton = document.getElementById('card-button');
            cardButton.addEventListener('click', async function (event) {
                await handlePaymentMethodSubmission(event, card);
            });
        });*/
    </script>

    <script>
        let PK_ENROLLMENT_MASTER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);

        $('#PK_USER_MASTER').SumoSelect({
            placeholder: 'Select Customer',
            search: true,
            searchText: 'Search...'
        });

        $('.datepicker-future').datepicker({
            format: 'mm/dd/yyyy',
            minDate: 0
        });

        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });


        function selectThisCustomer(param) {
            let location_id = $(param).find(':selected').data('location_id');
            let PK_USER = $(param).find(':selected').data('pk_user');
            $('#PK_LOCATION').val(location_id);
            $.ajax({
                url: "ajax/get_locations.php",
                type: "POST",
                data: {
                    PK_USER: PK_USER,
                    LOCATION_ID: location_id
                },
                async: false,
                cache: false,
                success: function(result) {
                    $('#PK_LOCATION').empty().append(result);
                    if (PK_ENROLLMENT_MASTER == 0) {
                        showEnrollmentInstructor();
                    }
                    showEnrollmentBy();
                }
            });
        }

        function showEnrollmentBy() {
            let location_id = $('#PK_LOCATION').val();
            $.ajax({
                url: "ajax/get_enrollment_by.php",
                type: "POST",
                data: {
                    LOCATION_ID: location_id
                },
                async: false,
                cache: false,
                success: function(result) {
                    $('#ENROLLMENT_BY_ID').empty().append(result);
                    if (PK_ENROLLMENT_MASTER > 0) {
                        $('#ENROLLMENT_BY_ID').val(ENROLLMENT_BY_ID);
                    }
                }
            });
        }

        function showEnrollmentInstructor() {
            let location_id = $('#PK_LOCATION').val();
            $.ajax({
                url: "ajax/get_instructor.php",
                type: "POST",
                data: {
                    LOCATION_ID: location_id
                },
                async: false,
                cache: false,
                success: function(result) {
                    $('.SERVICE_PROVIDER_ID').empty().append(result);
                }
            });
        }

        function addMoreServices() {
            let charge_type = $('.charge_type:checked').val();
            if (charge_type === 'Membership') {
                var value = "XX";
                var type = "readonly";
                var total = "";
            } else {
                var value = "";
                var type = "";
                var total = "readonly";
            }


            $('#append_service_div').append(`<div class="row individual_service_div">
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                        <option>Select</option>
                                                        <?php
                                                        $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` JOIN DOA_SERVICE_LOCATION ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_LOCATION.PK_SERVICE_MASTER WHERE DOA_SERVICE_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND IS_DELETED = 0");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= $row->fields['SERVICE_NAME'] ?></option>
                                                        <?php $row->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                        <option value="">Select</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" >
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control NUMBER_OF_SESSION" value="${value}" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)" ${type}>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control PRICE_PER_SESSION" value="${value}" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);" ${type}>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control TOTAL" name="TOTAL[]" onkeyup="calculateServiceTotal(this)" ${total}>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                                                        <option value="">Select</option>
                                                        <option value="1">Fixed</option>
                                                        <option value="2">Percent</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" onkeyup="calculateServiceTotal(this)">
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" readonly>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>`);
        }

        function addMoreServiceProviders() {
            $('#append_service_provider_div').append(`<div class="row individual_service_provider_div" style="margin_top: -25px">
                                                        <div class="col-4">
                                                            <div class="form-group">
                                                                <select class="form-control SERVICE_PROVIDER_ID" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID">
                                                                    <option value="">Select</option>
                                                                    <?php
                                                                    $row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY FIRST_NAME");
                                                                    while (!$row->EOF) { ?>
                                                                        <option value="<?php echo $row->fields['PK_USER']; ?>"><?= $row->fields['NAME'] ?></option>
                                                                        <?php $row->MoveNext();
                                                                    } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <input type="text" class="form-control SERVICE_PROVIDER_PERCENTAGE" name="SERVICE_PROVIDER_PERCENTAGE[]">
                                                                <span class="form-control input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                        <div class="form-group">
                                                            <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                        </div>
                                                    </div>
                                                </div>`);
            showEnrollmentInstructor();
        }

        function removeThis(param) {
            $(param).closest('.row').remove();
        }

        function removeThisAmount(param) {
            $(param).closest('.row').remove();
            let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
            let total_flexible_payment = 0;
            $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
                total_flexible_payment += parseFloat($(this).val());
            });
            total_flexible_payment = isNaN(total_flexible_payment) ? 0 : total_flexible_payment;
            $('#BALANCE_PAYABLE').val(parseFloat(total_bill - total_flexible_payment).toFixed(2));
        }

        function selectThisServiceCode(param) {
            let service_details = $(param).find(':selected').data('details');
            let price = $(param).find(':selected').data('price');

            let charge_type = $('.charge_type:checked').val();
            if (charge_type === 'Membership') {
                $(param).closest('.row').find('.SERVICE_DETAILS').val(service_details);
                $(param).closest('.row').find('.PRICE_PER_SESSION').val("XX");
            } else {
                $(param).closest('.row').find('.SERVICE_DETAILS').val(service_details);
                $(param).closest('.row').find('.PRICE_PER_SESSION').val(price);
            }

            calculateServiceTotal(param);
        }

        function selectThisService(param) {
            let PK_SERVICE_MASTER = $(param).val();
            $.ajax({
                url: "ajax/get_service_codes.php",
                type: "POST",
                data: {
                    PK_SERVICE_MASTER: PK_SERVICE_MASTER
                },
                async: false,
                cache: false,
                success: function(result) {
                    $(param).closest('.row').find('.PK_SERVICE_CODE').empty();
                    $(param).closest('.row').find('.PK_SERVICE_CODE').append(result);
                }
            });
        }

        function selectThisPackage(param) {
            let PK_PACKAGE = $(param).val();
            let EXPIRY_DATE = $(param).find(':selected').data('expiry_date');
            if (PK_PACKAGE) {
                $.ajax({
                    url: "ajax/get_packages.php",
                    type: "POST",
                    data: {
                        PK_PACKAGE: PK_PACKAGE
                    },
                    async: false,
                    cache: false,
                    success: function(result) {
                        $('.individual_service_div').remove();
                        $('#append_service_div').html(result);

                        let TOTAL_AMOUNT = 0;
                        $(param).closest('#enrollment_form').find('.FINAL_AMOUNT').each(function() {
                            TOTAL_AMOUNT += parseFloat($(this).val());
                        });
                        $('.TOTAL_AMOUNT').val(TOTAL_AMOUNT.toFixed(2));
                        $('#EXPIRY_DATE').val(EXPIRY_DATE / 30);
                    }
                });
            } else {
                $('.package_div').remove();
                addMoreServices();
            }
        }

        function chargeBySessions(param) {
            if ($(param).is(':checked') && ($(param).val() === 'Session' || $(param).val() === 'Membership')) {
                if ($(param).val() === 'Session') {
                    $('#Membership').prop('checked', false);
                    $('.NUMBER_OF_SESSION').prop('readonly', false);
                    $('.NUMBER_OF_SESSION').val('').css('pointer-events', 'none').trigger('change');
                    $('.PRICE_PER_SESSION').prop('readonly', false);
                    $('.PRICE_PER_SESSION').val('').css('pointer-events', 'none').trigger('change');
                    $('.TOTAL').prop('readonly', true);
                    $('.add_more').hide();
                    $('.session_base').show();
                    $('.member_base').hide();
                } else {
                    $('#Session').prop('checked', false);
                    $('.NUMBER_OF_SESSION').prop('readonly', true);
                    $('.NUMBER_OF_SESSION').val('XX').css('pointer-events', 'none').trigger('change');
                    $('.PRICE_PER_SESSION').prop('readonly', true);
                    $('.PRICE_PER_SESSION').val('XX').css('pointer-events', 'none').trigger('change');
                    $('.TOTAL').prop('readonly', false);
                    $('.add_more').show();
                    $('.session_base').hide();
                    $('.member_base').show();
                }
                $('#BILLING_DATE').prop('readonly', true).css("pointer-events", "none");
                $('.one_time').show();
                $('.payment_plans').hide();
                $('.flexible_payments').hide();
                document.querySelector("input[name='PAYMENT_METHOD'][value='One Time']").checked = true;
                $('#down_payment_div').slideUp();
                $('#AMOUNT_TO_PAY').prop('readonly', true);
                $('.partial_payment').hide();
                $('.ENROLLMENT_PAYMENT_TYPE').val(1).css('pointer-events', 'none').trigger('change');
                $('#save_card_on_file_div').show();
            } else {
                $('.session_base').show();
                $('.member_base').hide();

                $('.add_more').show();
                $('#BILLING_DATE').prop('readonly', false).css("pointer-events", "auto");
                $('.one_time').show();
                $('.payment_plans').show();
                $('.flexible_payments').show();
                document.querySelector("input[name='PAYMENT_METHOD'][value='One Time']").checked = false;
                $('#down_payment_div').slideDown();
                $('#AMOUNT_TO_PAY').prop('readonly', false);
                $('.ENROLLMENT_PAYMENT_TYPE').css('pointer-events', 'auto');
                $('.partial_payment').show();
                $('#save_card_on_file_div').hide();
            }
        }

        function calculateServiceTotal(param) {
            let charge_type = $('.charge_type:checked').val();
            let TOTAL = 0;

            if (charge_type === 'Membership') {
                TOTAL = ($(param).closest('.row').find('.TOTAL').val() == '') ? 0 : $(param).closest('.row').find('.TOTAL').val();
            } else {
                let number_of_session = ($(param).closest('.row').find('.NUMBER_OF_SESSION').val() == '') ? 0 : $(param).closest('.row').find('.NUMBER_OF_SESSION').val();
                let service_price = ($(param).closest('.row').find('.PRICE_PER_SESSION').val()) ?? 0;
                TOTAL = parseFloat(number_of_session) * parseFloat(service_price);
                $(param).closest('.row').find('.TOTAL').val(parseFloat(TOTAL).toFixed(2));
            }

            let DISCOUNT = ($(param).closest('.row').find('.DISCOUNT').val()) ?? 0;
            let DISCOUNT_TYPE = ($(param).closest('.row').find('.DISCOUNT_TYPE').val()) ?? 0;
            let FINAL_AMOUNT = parseFloat(TOTAL);
            if (DISCOUNT_TYPE == 1) {
                FINAL_AMOUNT = parseFloat(TOTAL - DISCOUNT);
            } else {
                if (DISCOUNT_TYPE == 2) {
                    FINAL_AMOUNT = parseFloat(TOTAL - (TOTAL * (DISCOUNT / 100)));
                }
            }
            $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));

            let TOTAL_AMOUNT = 0;
            $(param).closest('#enrollment_form').find('.FINAL_AMOUNT').each(function() {
                TOTAL_AMOUNT += parseFloat($(this).val());
            });
            $('.TOTAL_AMOUNT').val(TOTAL_AMOUNT.toFixed(2));
        }

        $(document).on('click', '#cancel_button', function() {
            window.location.href = 'all_enrollments.php'
        });

        function addMorePayments() {
            let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
            let down_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
            let total_flexible_payment = 0;
            $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
                total_flexible_payment += parseFloat($(this).val());
            });
            if ((total_flexible_payment + down_payment) < total_bill) {
                $('#flexible_plans_div').append(`<div class="row">
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control datepicker-future" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control FLEXIBLE_PAYMENT_AMOUNT" onkeyup="calculateBalancePayable(this)" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3" style="padding-top: 5px;">
                                                <a href="javascript:;" onclick="removeThisAmount(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                        </div>`);
                $('.datepicker-future').datepicker({
                    format: 'mm/dd/yyyy',
                    minDate: 0
                });
            } else {
                alert('Total Bill Amount Exceed');
            }
        }

        $(document).on('submit', '#enrollment_form', function(event) {
            event.preventDefault();
            let service_provider = $('#SERVICE_PROVIDER_ID').val();
            let is_confirm = $('#is_confirm').val();
            if (service_provider == '' && is_confirm == 0) {
                $('#confirm_modal').modal('show');
            } else {
                $('#confirm_modal').modal('hide');
                let form_data = $('#enrollment_form').serialize();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: form_data,
                    dataType: 'json',
                    success: function(data) {
                        if (PK_ENROLLMENT_MASTER > 0) {
                            window.location.reload();
                        } else {
                            $('.PK_ENROLLMENT_MASTER').val(data.PK_ENROLLMENT_MASTER);
                            $('#billing_link')[0].click();
                        }
                    }
                });
            }
        });

        function goToPaymentTab() {
            let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
            if (PK_ENROLLMENT_MASTER) {
                $.ajax({
                    url: "ajax/show_payment_tab.php",
                    type: 'POST',
                    data: {
                        PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER
                    },
                    success: function(data) {
                        $('#payment_tab_div').html(data);
                        $('#AMOUNT_SHOW').val($('.TOTAL_AMOUNT').val());
                        calculatePayment();
                    }
                });
            } else {
                alert('Please fill up the enrollment form first');
                $('#enrollment_link')[0].click();
            }
        }

        function goToLedgerTab() {
            let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
            if (!PK_ENROLLMENT_MASTER) {
                alert('Please fill up the enrollment form first');
                $('#enrollment_link')[0].click();
            }
        }

        function calculateDiscount(param) {
            let DISCOUNT = $(param).closest('.row').find('.DISCOUNT').val();
            let DISCOUNT_TYPE = $(param).closest('.row').find('.DISCOUNT_TYPE').val();
            let TOTAL = $(param).closest('.row').find('.TOTAL').val();

            if (DISCOUNT_TYPE == 1) {
                let FINAL_AMOUNT = parseFloat(TOTAL - DISCOUNT);
                $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));
            } else {
                if (DISCOUNT_TYPE == 2) {
                    let FINAL_AMOUNT = parseFloat(TOTAL - (TOTAL * (DISCOUNT / 100)));
                    $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));
                }
            }
            let TOTAL_AMOUNT = 0;
            $(param).closest('#payment_tab_div').find('.FINAL_AMOUNT').each(function() {
                TOTAL_AMOUNT += parseFloat($(this).val());
            });
            $('#total_bill').val(parseFloat(TOTAL_AMOUNT).toFixed(2));
            $('#BALANCE_PAYABLE').val(parseFloat(TOTAL_AMOUNT).toFixed(2));
        }

        function calculatePayment() {
            let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
            let down_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
            let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
            $('#BALANCE_PAYABLE').val(parseFloat(total_bill - down_payment).toFixed(2));
            calculatePaymentPlans();
        }

        $(document).on('change', '.PAYMENT_METHOD', function() {
            $('.payment_method_div').slideUp();
            $('#down_payment_div').slideDown();
            $('#FIRST_DUE_DATE').prop('required', false);
            //$('#IS_ONE_TIME_PAY').val(0);
            if ($(this).val() == 'One Time') {
                let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
                $('#DOWN_PAYMENT').val(0.00);
                $('#BALANCE_PAYABLE').val(total_bill.toFixed(2));
                $('#down_payment_div').slideUp();
                $('#ACTUAL_AMOUNT').val(total_bill.toFixed(2));
                $('#AMOUNT_TO_PAY').val(total_bill.toFixed(2));
                //$('#payment_confirmation_form_div').slideDown();
                //$('#IS_ONE_TIME_PAY').val(1);
                $('#PAYMENT_BILLING_REF').val($('#BILLING_REF').val());
                $('#PAYMENT_BILLING_DATE').val($('#BILLING_DATE').val());
                //$('#enrollment_payment_modal').modal('show');
            }
            if ($(this).val() == 'Payment Plans') {
                $('#FIRST_DUE_DATE').prop('required', true);
                $('#payment_plans_div').slideDown();
            }
            if ($(this).val() == 'Flexible Payments') {
                $('#flexible_plans_div').slideDown();
                let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
                $('#DOWN_PAYMENT').val(0.00);
                $('#BALANCE_PAYABLE').val(total_bill.toFixed(2));
                $('#down_payment_div').slideDown();
                $('#ACTUAL_AMOUNT').val(total_bill.toFixed(2));
                $('#AMOUNT_TO_PAY').val(total_bill.toFixed(2));
                //$('#payment_confirmation_form_div').slideDown();
                //$('#enrollment_payment_modal').modal('show');
            }
        });

        function calculateBalancePayable() {
            let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
            let total_flexible_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
            $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
                total_flexible_payment += parseFloat($(this).val());
            });
            total_flexible_payment = isNaN(total_flexible_payment) ? 0 : total_flexible_payment;
            $('#BALANCE_PAYABLE').val(parseFloat(total_bill - total_flexible_payment).toFixed(2));
        }

        function calculatePaymentPlans() {
            let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
            let NUMBER_OF_PAYMENT = parseInt(($('#NUMBER_OF_PAYMENT').val()) ? $('#NUMBER_OF_PAYMENT').val() : 1);
            $('#INSTALLMENT_AMOUNT').val(parseFloat(balance_payable / NUMBER_OF_PAYMENT).toFixed(2));
        }

        function calculateNumberOfPayment(param) {
            let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
            let entered_amount = $(param).val();
            let number_of_payment = balance_payable / entered_amount;
            $('#NUMBER_OF_PAYMENT').val(number_of_payment);
            if (Number.isInteger(number_of_payment)) {
                $('#number_of_payment_error').hide();
            } else {
                $('#number_of_payment_error').show();
            }
        }

        $(document).on('submit', '#billing_form', function(event) {
            event.preventDefault();
            let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
            let down_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
            let total_flexible_payment = 0;
            $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
                total_flexible_payment += parseFloat($(this).val());
            });
            total_flexible_payment = isNaN(total_flexible_payment) ? 0 : total_flexible_payment;
            if ((total_flexible_payment + down_payment) <= total_bill) {
                let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
                let payment_method = $('.PAYMENT_METHOD:checked').val();
                if (payment_method == 'Flexible Payments' && balance_payable > 0) {
                    swal("Balance Payable!", "Balance Payable must be 0", "error");
                } else {
                    let number_of_payment = $('#NUMBER_OF_PAYMENT').val();
                    if (Number.isInteger(Number(number_of_payment))) {
                        let form_data = $('#billing_form').serialize();
                        $.ajax({
                            url: "ajax/AjaxFunctions.php",
                            type: 'POST',
                            data: form_data,
                            dataType: 'json',
                            success: function(data) {
                                $('.PK_ENROLLMENT_BILLING').val(data.PK_ENROLLMENT_BILLING);
                                $('.PK_ENROLLMENT_LEDGER').val(data.PK_ENROLLMENT_LEDGER);
                                let payment_method = $('.PAYMENT_METHOD:checked').val();
                                let down_payment = parseFloat($('#DOWN_PAYMENT').val());
                                let today = new Date().getTime();
                                let firstPaymentDate = new Date($('#FIRST_DUE_DATE').val()).getTime();
                                let billingDate = new Date($('#BILLING_DATE').val()).getTime();

                                //alert((today.getDate() + '/' + today.getMonth() + '/' + today.getFullYear() >= billingDate.getDate() + '/' + billingDate.getMonth() + '/' + billingDate.getFullYear()));

                                //console.log($('.PAYMENT_METHOD:checked').val(), today.getDate() + '/' + today.getMonth() + '/' + today.getFullYear(), billingDate.getDate() + '/' + billingDate.getMonth() + '/' + billingDate.getFullYear());

                                if (((down_payment > 0) && (today >= billingDate)) || ((payment_method === 'One Time') && (today >= billingDate)) || ((payment_method === 'Payment Plans') && (today >= firstPaymentDate))) {
                                    if (payment_method === 'One Time') {
                                        let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
                                        $('#AMOUNT_TO_PAY').val(balance_payable.toFixed(2));
                                        $('#ACTUAL_AMOUNT').val(balance_payable.toFixed(2));
                                    } else {
                                        if (down_payment > 0) {
                                            $('#AMOUNT_TO_PAY').val(down_payment.toFixed(2));
                                            $('#ACTUAL_AMOUNT').val(down_payment.toFixed(2));
                                        } else {
                                            if ((payment_method === 'Payment Plans') && (today >= firstPaymentDate)) {
                                                let installment_amount = parseFloat(($('#INSTALLMENT_AMOUNT').val()) ? $('#INSTALLMENT_AMOUNT').val() : 0);
                                                $('#AMOUNT_TO_PAY').val(installment_amount.toFixed(2));
                                                $('#ACTUAL_AMOUNT').val(installment_amount.toFixed(2));
                                            }
                                        }
                                    }
                                    $('#enrollment_payment_modal').modal('show');
                                } else {
                                    let header = '<?= $header ?>';
                                    if (header) {
                                        window.location.href = header;
                                    } else {
                                        let PK_USER = $('#PK_USER_MASTER').find(':selected').data('pk_user');
                                        let PK_USER_MASTER = $('#PK_USER_MASTER').find(':selected').data('customer_id');
                                        window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=enrollment';
                                    }
                                }
                            }
                        });
                    } else {
                        $('#number_of_payment_error').slideUp();
                        $('#number_of_payment_error').slideDown();
                    }
                }
            } else {
                alert('Total Bill Amount Exceed');
            }
        });

        /*$(document).on('submit', '#payment_confirmation_form', function (event) {
            event.preventDefault();
            let form_data = $('#payment_confirmation_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success:function (data) {
                    //window.location.href='all_enrollments.php';
                }
            });
        });*/

        function payNow(PK_ENROLLMENT_LEDGER, BILLED_AMOUNT) {
            $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
            $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
            $('#ACTUAL_AMOUNT').val(BILLED_AMOUNT);
            $('#payment_confirmation_form_div').slideDown();
            $('#PK_PAYMENT_TYPE').val('');
            $('.payment_type_div').slideUp();
            $('#wallet_balance_div').slideUp();
            $('#remaining_amount_div').slideUp();
            $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
            $('#enrollment_payment_modal').modal('show');
        }

        function openReceipt(PK_ENROLLMENT_MASTER, RECEIPT_NUMBER) {
            let RECEIPT_NUMBER_ARRAY = RECEIPT_NUMBER.split(',');
            for (let i = 0; i < RECEIPT_NUMBER_ARRAY.length; i++) {
                window.open('generate_receipt_pdf.php?master_id=' + PK_ENROLLMENT_MASTER + '&receipt=' + RECEIPT_NUMBER_ARRAY[i], '_blank');
            }
        }
    </script>

</body>

</html>