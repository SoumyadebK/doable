<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "PAYMENTS MADE REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$week_number = $_GET['week_number'];
$YEAR = date('Y');

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));

$payment_date = " AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
$enrollment_date = " AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
$appointment_date = " AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';
if (preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $business_name)) {
    $business_name = '';
} else {
    $business_name = '' . $business_name;
}

if ($type === 'export') {
    $access_token = getAccessToken();
    $authorization = "Authorization: Bearer " . $access_token;

    $line_item = [];

    $staff_data = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.DISPLAY_ORDER ASC");
    while (!$staff_data->EOF) {
        $staff_member = getStaffCode($authorization, $staff_data->fields['FIRST_NAME'], $staff_data->fields['LAST_NAME']);
        $staff_type = 'INSTRUCTOR';
        $number_guests = 0;

        $private_data = $db_account->Execute("SELECT SUM(DOA_SCHEDULING_CODE.UNIT) AS PRIVATE_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC') AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_CHARGED = 1 " . $appointment_date . " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $staff_data->fields['PK_USER']);
        $private_lessons = $private_data->fields['PRIVATE_COUNT'] ?? 0;

        $group_data = $db_account->Execute("SELECT SUM(DOA_SCHEDULING_CODE.UNIT) AS GROUP_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_CHARGED = 1 " . $appointment_date . " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $staff_data->fields['PK_USER']);
        $number_in_class = $group_data->fields['GROUP_COUNT'] ?? 0;

        $dor_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'DOR' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $staff_data->fields['PK_USER'] . " $enrollment_date");
        $dor_sanct_competition = $dor_misc_data->fields['MISC_TOTAL'] ?? 0;

        $showcase_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'SHOWCASE' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $staff_data->fields['PK_USER'] . " $enrollment_date");
        $showcase_medal_ball = $showcase_misc_data->fields['MISC_TOTAL'] ?? 0;

        $general_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'GENERAL' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $staff_data->fields['PK_USER'] . " $enrollment_date");
        $party_time_non_unit = $general_misc_data->fields['MISC_TOTAL'] ?? 0;

        $interview_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS INTERVIEW_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE NOT IN (13,16) AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $staff_data->fields['PK_USER'] . " $enrollment_date");
        $interview_department = $interview_data->fields['INTERVIEW_TOTAL'] ?? 0;

        $renewal_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS RENEWAL_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 13 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $staff_data->fields['PK_USER'] . " $enrollment_date");
        $renewal_department = $renewal_data->fields['RENEWAL_TOTAL'] ?? 0;

        $line_item[] = array(
            "staff_member" => $staff_member,
            "staff_type" => $staff_type,
            "number_guests" => $number_guests,
            "private_lessons" => $private_lessons,
            "number_in_class" => $number_in_class,
            "dor_sanct_competition" => $dor_sanct_competition,
            "showcase_medal_ball" => $showcase_medal_ball,
            "party_time_non_unit" => $party_time_non_unit,
            "interview_department" => $interview_department,
            "renewal_department" => $renewal_department,
        );

        $staff_data->MoveNext();
    }

    $data = [
        'type' => 'staff_performance',
        'prepared_by' => $user_data->fields['FIRST_NAME'] . ' ' . $user_data->fields['LAST_NAME'],
        'week_number' => $week_number,
        'week_year' => $YEAR,
        'line_items' => $line_item,
    ];

    $url = constant('ami_api_url') . '/api/v1/reports';
    $post_data = callArturMurrayApi($url, $data, $authorization);

    //pre_r(json_decode($post_data));
}

$location_name = '';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$totalResults = count($resultsArray);
$concatenatedResults = "";
foreach ($resultsArray as $key => $result) {
    $concatenatedResults .= $result;
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

$executive_data = $db_account->Execute("SELECT DISTINCT(ENROLLMENT_BY_ID) AS ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_MASTER > 0 $enrollment_date");
$executive_id = [];
while (!$executive_data->EOF) {
    $executive_id[] = $executive_data->fields['ENROLLMENT_BY_ID'];
    $executive_data->MoveNext();
}

if (!empty($_GET['START_DATE'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = 'payments_made_report';
    $WEEK_NUMBER = explode(' ', $_GET['WEEK_NUMBER'])[2];
    $START_DATE = date('Y-m-d', strtotime($_GET['START_DATE']));
    $END_DATE = date('Y-m-d', strtotime($_GET['END_DATE']));
    $PK_USER = empty($_GET['PK_USER']) ? 0 : $_GET['PK_USER'];
    $include_no_provider = isset($_GET['include_no_provider']) ? 1 : 0;

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
    } else {
        header('location:payments_made_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">

<style>
    a {
        color: #690C24;
        text-decoration: none;
        font-size: 14px;
    }

    .btn {
        border: 0;
        color: #fff;
        border-radius: 50rem;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }

    input.form-control,
    select.form-control,
    textarea.form-control {
        border-radius: 0.375rem !important;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 1px !important;">
            <div class="container-fluid" style="padding: 10px 20px 0 20px; margin-top: 0px;">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                                <li class="breadcrumb-item active"><a href="customer_summary_report.php"><?= $title ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 align-self-center">
                        <div class="card">
                            <div class="card-body" style="padding-bottom: 0px !important;">
                                <form class="form-material form-horizontal" action="" method="get" id="reportForm">
                                    <input type="hidden" name="start_date" id="start_date">
                                    <input type="hidden" name="end_date" id="end_date">
                                    <div class="row justify-content-start">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" placeholder="Start Date" value="<?= !empty($_GET['start_date']) ? date('m/d/Y', strtotime($_GET['start_date'])) : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" placeholder="End Date" value="<?= !empty($_GET['end_date']) ? date('m/d/Y', strtotime($_GET['end_date'])) : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <?php if (in_array('Reports Create', $PERMISSION_ARRAY)) { ?>
                                                <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                                <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info" style="background-color: #39B54A !important;">
                                                <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <?php } ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                if ($type === 'export') {
                    echo "<h3>Data export to Arthur Murray API Successfully</h3>";
                } else { ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div>
                                        <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                        <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-bordered" data-page-length='50'>
                                            <thead>
                                                <tr>
                                                    <th style="width:45%; text-align: center; vertical-align:auto; font-weight: bold" colspan="10"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= $concatenatedResults ?></th>
                                                    <th style="width:55%; text-align: center; font-weight: bold" colspan="9">(<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</th>
                                                </tr>
                                                <tr>
                                                    <th style="width:8%; text-align: center">Payment Date</th>
                                                    <th style="width:9%; text-align: center">Payment Amount</th>
                                                    <th style="width:9%; text-align: center">Enrollment Payment</th>
                                                    <th style="width:8%; text-align: center">Tip</th>
                                                    <th style="width:9%; text-align: center">Payment Title</th>
                                                    <th style="width:9%; text-align: center">Payment Method</th>
                                                    <th style="width:9%; text-align: center">Card Type</th>
                                                    <th style="width:8%; text-align: center">Receipt</th>
                                                    <th style="width:9%; text-align: center">Memo</th>
                                                    <th style="width:10%; text-align: center">Client</th>
                                                    <th style="width:10%; text-align: center">Enrollment Name</th>
                                                    <th style="width:10%; text-align: center">Enrollment Date</th>
                                                    <th style="width:10%; text-align: center">Enrollment Type</th>
                                                    <th style="width:10%; text-align: center">Enrollment Cost</th>
                                                    <th style="width:10%; text-align: center">Enrollment Balance</th>
                                                    <th style="width:8%; text-align: center">Closer</th>
                                                    <th style="width:8%; text-align: center">Teacher1</th>
                                                    <th style="width:8%; text-align: center">Teacher2</th>
                                                    <th style="width:8%; text-align: center">Teacher3</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Get all payments first and separate regular payments from refunds
                                                $all_payments = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.TYPE, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.MISC_ID, ENROLLMENT_DATE, ENROLLMENT_TYPE, TOTAL_AMOUNT, ENROLLMENT_BY_ID, COALESCE(DOA_ENROLLMENT_TIP.TIP_AMOUNT, 0) AS TIP_AMOUNT FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_TIP ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_PAYMENT = DOA_ENROLLMENT_TIP.PK_ENROLLMENT_PAYMENT WHERE DOA_USERS.IS_DELETED =0 AND IS_REFUNDED = 0 AND DOA_ENROLLMENT_PAYMENT.NOT_EXPORT_TO_AMI = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $payment_date . " GROUP BY DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_PAYMENT ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC");

                                                // Get gift certificate payments (both active and refunded)
                                                $gift_payments = $db_account->Execute("SELECT
                                                    DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_PAYMENT,
                                                    DOA_ENROLLMENT_PAYMENT.PK_GIFT_CERTIFICATE_MASTER,
                                                    DOA_PAYMENT_TYPE.PAYMENT_TYPE,
                                                    DOA_ENROLLMENT_PAYMENT.TYPE,
                                                    DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE,
                                                    DOA_ENROLLMENT_PAYMENT.AMOUNT,
                                                    DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO,
                                                    DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE,
                                                    DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER,
                                                    DOA_ENROLLMENT_PAYMENT.IS_REFUNDED,
                                                    DOA_PAYMENT_TYPE.PAYMENT_TYPE AS PAYMENT_TYPE_NAME,
                                                    NULL AS ENROLLMENT_NAME,
                                                    NULL AS ENROLLMENT_ID,
                                                    NULL AS MISC_ID,
                                                    NULL AS ENROLLMENT_DATE,
                                                    NULL AS ENROLLMENT_TYPE,
                                                    NULL AS TOTAL_AMOUNT,
                                                    NULL AS ENROLLMENT_BY_ID,
                                                    NULL AS PK_USER_MASTER,
                                                    NULL AS CLIENT,
                                                    0 AS TIP_AMOUNT
                                                FROM
                                                    DOA_ENROLLMENT_PAYMENT
                                                INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE
                                                ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE
                                                LEFT JOIN DOA_GIFT_CERTIFICATE_MASTER AS DOA_GIFT_CERTIFICATE_MASTER
                                                ON DOA_ENROLLMENT_PAYMENT.PK_GIFT_CERTIFICATE_MASTER = DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER
                                                WHERE (DOA_ENROLLMENT_PAYMENT.TYPE = 'Gift Certificate' OR DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund Gift Certificate')
                                                AND DOA_ENROLLMENT_PAYMENT.NOT_EXPORT_TO_AMI = 0 
                                                AND DOA_ENROLLMENT_PAYMENT.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
                                                " . $payment_date . " 
                                                ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC");

                                                // Separate regular payments and refunds
                                                $regular_payments = [];
                                                $refund_payments = [];

                                                // Process regular payments and refunds
                                                while (!$all_payments->EOF) {
                                                    if ($all_payments->fields['TYPE'] == 'Refund') {
                                                        $refund_payments[] = $all_payments->fields;
                                                    }
                                                    if ($all_payments->fields['TYPE'] == 'Payment') {
                                                        $regular_payments[] = $all_payments->fields;
                                                    }
                                                    $all_payments->MoveNext();
                                                }

                                                // Process gift certificate payments based on IS_REFUNDED flag
                                                while (!$gift_payments->EOF) {
                                                    $gift_data = $gift_payments->fields;

                                                    // Check if this gift certificate payment has been refunded
                                                    if ($gift_data['TYPE'] == 'Refund Gift Certificate') {
                                                        $refund_payments[] = $gift_data;
                                                    } else {
                                                        $regular_payments[] = $gift_data;
                                                    }
                                                    $gift_payments->MoveNext();
                                                }

                                                // Get wallet payments
                                                $total_wallet = 0;
                                                $wallet_payments = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.*, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, DOA_PAYMENT_TYPE.PAYMENT_TYPE, DOA_CUSTOMER_WALLET.BALANCE_LEFT, 0 AS TIP_AMOUNT FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_CUSTOMER_WALLET ON DOA_ENROLLMENT_PAYMENT.PK_CUSTOMER_WALLET = DOA_CUSTOMER_WALLET.PK_CUSTOMER_WALLET LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_CUSTOMER_WALLET.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE = DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.TYPE = 'Wallet' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO != 'Gift Certificate' AND DOA_ENROLLMENT_PAYMENT.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC");
                                                ?>

                                                <!-- Display wallet payments first -->
                                                <?php while (!$wallet_payments->EOF) {
                                                    $total_wallet += $wallet_payments->fields['AMOUNT'];
                                                    if ($wallet_payments->fields['BALANCE_LEFT'] > 0) {
                                                ?>
                                                        <tr>
                                                            <td style="text-align: center"><?= date('m/d/Y', strtotime($wallet_payments->fields['PAYMENT_DATE'])) ?></td>
                                                            <td style="text-align: right">$<?= number_format($wallet_payments->fields['BALANCE_LEFT'], 2) ?></td>
                                                            <td style="text-align: right">$<?= number_format($wallet_payments->fields['BALANCE_LEFT'], 2) ?></td>
                                                            <td style="text-align: right">$0.00</td>
                                                            <td style="text-align: center">Wallet</td>
                                                            <td style="text-align: center"><?= $wallet_payments->fields['PAYMENT_TYPE'] ?></td>
                                                            <td style="text-align: center">-</td>
                                                            <td style="text-align: center"><?= $wallet_payments->fields['RECEIPT_NUMBER'] ?></td>
                                                            <td style="text-align: center"><?= $wallet_payments->fields['MEMO'] ?? '-' ?></td>
                                                            <td style="text-align: center"><?= $wallet_payments->fields['CLIENT'] ?></td>
                                                            <td style="text-align: center">-</td>
                                                            <td style="text-align: center">-</td>
                                                            <td style="text-align: center">-</td>
                                                            <td style="text-align: right">-</td>
                                                            <td style="text-align: right">-</td>
                                                            <td style="text-align: center">-</td>
                                                            <td style="text-align: center">-</td>
                                                            <td style="text-align: center">-</td>
                                                            <td style="text-align: center">-</td>
                                                        </tr>
                                                <?php
                                                    }
                                                    $wallet_payments->MoveNext();
                                                } ?>

                                                <!-- Display regular payments -->
                                                <?php
                                                $i = 1;
                                                $total_amount = 0;
                                                $total_refund = 0;
                                                $total_tips = 0;
                                                $total_refund_tips = 0;

                                                foreach ($regular_payments as $payment) {
                                                    $name = empty($payment['ENROLLMENT_NAME']) ? '' : $payment['ENROLLMENT_NAME'];
                                                    if (empty($name)) {
                                                        $enrollment_name = '';
                                                    } else {
                                                        $enrollment_name = "$name" . " - ";
                                                    }
                                                    $PK_USER_MASTER = empty($payment['PK_USER_MASTER']) ? '' : $payment['PK_USER_MASTER'];

                                                    // Check if this is a gift certificate payment
                                                    $is_gift_certificate = ($payment['TYPE'] == 'Gift Certificate' || $payment['TYPE'] == 'Refund Gift Certificate');

                                                    if (!$is_gift_certificate && !empty($payment['ENROLLMENT_BY_ID'])) {
                                                        $enrollment_by = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLOSER FROM DOA_USERS WHERE PK_USER = " . $payment['ENROLLMENT_BY_ID']);
                                                    } else {
                                                        $enrollment_by = null;
                                                    }

                                                    if (!$is_gift_certificate && !empty($payment['PK_ENROLLMENT_MASTER'])) {
                                                        $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER LEFT JOIN $account_database.DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $payment['PK_ENROLLMENT_MASTER']);
                                                    } else {
                                                        $service_provider = null;
                                                    }

                                                    $teacher = [];
                                                    if ($service_provider && $service_provider->RecordCount() > 0) {
                                                        while (!$service_provider->EOF) {
                                                            $teacher[] = $service_provider->fields['TEACHER'];
                                                            $service_provider->MoveNext();
                                                        }
                                                    }

                                                    $enrollment_balance = !empty($payment['TOTAL_AMOUNT']) ? $payment['TOTAL_AMOUNT'] - $payment['AMOUNT'] : 0;

                                                    // Get tip amount
                                                    $tip_amount = $payment['TIP_AMOUNT'] ?? 0;
                                                    $total_payment = $payment['AMOUNT'] + $tip_amount;
                                                    $total_amount += $payment['AMOUNT'];
                                                    $total_tips += $tip_amount;

                                                    // Payment type logic
                                                    if ($is_gift_certificate) {
                                                        $payment_type = 'Gift Certificate';
                                                        $enrollment_name = '';
                                                        $ENROLLMENT_ID = '';
                                                        $MISC_ID = '';
                                                        $client_name = '';
                                                        $total_amount_display = '';
                                                        $enrollment_date_display = '';
                                                        $enrollment_type_display = '';
                                                        $enrollment_balance_display = '';
                                                    } elseif ($payment['TYPE'] == 'Move') {
                                                        $payment_type = 'Wallet';
                                                        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
                                                        $MISC_ID = $payment['MISC_ID'] ?? '';
                                                        $client_name = $payment['CLIENT'] ?? '';
                                                        $total_amount_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'], 2) : '';
                                                        $enrollment_date_display = !empty($payment['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($payment['ENROLLMENT_DATE'])) : '';
                                                        $enrollment_type_display = !empty($payment['ENROLLMENT_TYPE']) ? $payment['ENROLLMENT_TYPE'] : '';
                                                        $enrollment_balance_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'] - $payment['AMOUNT'], 2) : '';
                                                    } elseif ($payment['PK_PAYMENT_TYPE'] == '2') {
                                                        $payment_info = json_decode($payment['PAYMENT_INFO']);
                                                        $payment_type = $payment['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                                                        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
                                                        $MISC_ID = $payment['MISC_ID'] ?? '';
                                                        $client_name = $payment['CLIENT'] ?? '';
                                                        $total_amount_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'], 2) : '';
                                                        $enrollment_date_display = !empty($payment['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($payment['ENROLLMENT_DATE'])) : '';
                                                        $enrollment_type_display = !empty($payment['ENROLLMENT_TYPE']) ? $payment['ENROLLMENT_TYPE'] : '';
                                                        $enrollment_balance_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'] - $payment['AMOUNT'], 2) : '';
                                                    } elseif (in_array($payment['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                                                        $payment_info = json_decode($payment['PAYMENT_INFO']);
                                                        $payment_type = $payment['PAYMENT_TYPE'] . " # " . ((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
                                                        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
                                                        $MISC_ID = $payment['MISC_ID'] ?? '';
                                                        $client_name = $payment['CLIENT'] ?? '';
                                                        $total_amount_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'], 2) : '';
                                                        $enrollment_date_display = !empty($payment['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($payment['ENROLLMENT_DATE'])) : '';
                                                        $enrollment_type_display = !empty($payment['ENROLLMENT_TYPE']) ? $payment['ENROLLMENT_TYPE'] : '';
                                                        $enrollment_balance_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'] - $payment['AMOUNT'], 2) : '';
                                                    } else {
                                                        $payment_type = $payment['PAYMENT_TYPE'];
                                                        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
                                                        $MISC_ID = $payment['MISC_ID'] ?? '';
                                                        $client_name = $payment['CLIENT'] ?? '';
                                                        $total_amount_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'], 2) : '';
                                                        $enrollment_date_display = !empty($payment['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($payment['ENROLLMENT_DATE'])) : '';
                                                        $enrollment_type_display = !empty($payment['ENROLLMENT_TYPE']) ? $payment['ENROLLMENT_TYPE'] : '';
                                                        $enrollment_balance_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'] - $payment['AMOUNT'], 2) : '';
                                                    }

                                                    // For non-gift certificate payments, set enrollment name
                                                    if (!$is_gift_certificate) {
                                                        $name = $payment['ENROLLMENT_NAME'] ?? '';
                                                        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
                                                        $MISC_ID = $payment['MISC_ID'] ?? '';
                                                        if (empty($name)) {
                                                            $enrollment_name = '';
                                                        } else {
                                                            $enrollment_name = "$name" . " - ";
                                                        }
                                                        $client_name = $payment['CLIENT'] ?? '';
                                                    }
                                                ?>
                                                    <tr>
                                                        <td style="text-align: center"><?= date('m/d/Y', strtotime($payment['PAYMENT_DATE'])) ?></td>
                                                        <td style="text-align: right">$<?= number_format($total_payment, 2) ?></td>
                                                        <td style="text-align: right">$<?= number_format($payment['AMOUNT'], 2) ?></td>
                                                        <td style="text-align: right">$<?= number_format($tip_amount, 2) ?></td>
                                                        <td style="text-align: center"><?= $payment_type ?></td>
                                                        <td style="text-align: center"><?= $payment['PAYMENT_TYPE'] ?></td>
                                                        <?php if ($payment['PAYMENT_TYPE'] == 'Credit Card' || $payment['PAYMENT_TYPE'] == 'Visa' || $payment['PAYMENT_TYPE'] == 'Master Card' || $payment['PAYMENT_TYPE'] == 'American Express' || $payment['PAYMENT_TYPE'] == 'Card' || $payment['PAYMENT_TYPE'] == 'Card On File') { ?>
                                                            <td style="text-align: center"><?= $payment['PAYMENT_TYPE'] ?></td>
                                                        <?php } else { ?>
                                                            <td style="text-align: center"></td>
                                                        <?php } ?>
                                                        <td style="text-align: center"><?= $payment['RECEIPT_NUMBER'] ?></td>
                                                        <td style="text-align: left"><?= empty($payment['MEMO']) ? '' : $payment['MEMO'] ?></td>
                                                        <td style="text-align: left"><?= $client_name ?></td>
                                                        <td style="text-align: center"><?= ($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $MISC_ID : $enrollment_name . $ENROLLMENT_ID ?></td>
                                                        <td style="text-align: center"><?= $enrollment_date_display ?></td>
                                                        <td style="text-align: center"><?= $enrollment_type_display ?></td>
                                                        <td style="text-align: right"><?= $total_amount_display ?></td>
                                                        <td style="text-align: right"><?= $enrollment_balance_display ?></td>
                                                        <td style="text-align: center"><?= !empty($enrollment_by->fields['CLOSER']) ? $enrollment_by->fields['CLOSER'] : '' ?></td>
                                                        <td style="text-align: center"><?= isset($teacher[0]) ? $teacher[0] : '' ?></td>
                                                        <td style="text-align: center"><?= isset($teacher[1]) ? $teacher[1] : '' ?></td>
                                                        <td style="text-align: center"><?= isset($teacher[2]) ? $teacher[2] : '' ?></td>
                                                    </tr>
                                                <?php
                                                    $i++;
                                                }
                                                ?>

                                                <!-- Display all refunds at the bottom -->
                                                <?php foreach ($refund_payments as $refund) {
                                                    // Check if this is a gift certificate refund
                                                    $is_gift_refund = ($refund['TYPE'] == 'Refund Gift Certificate');

                                                    $name = $refund['ENROLLMENT_NAME'] ?? '';
                                                    if (empty($name)) {
                                                        $enrollment_name = '';
                                                    } else {
                                                        $enrollment_name = "$name" . " - ";
                                                    }
                                                    $total_refund += $refund['AMOUNT'];
                                                    $refund_tip = $refund['TIP_AMOUNT'] ?? 0;
                                                    $total_refund_tips += $refund_tip;
                                                    $refund_total = $refund['AMOUNT'] + $refund_tip;
                                                    $PK_USER_MASTER = $refund['PK_USER_MASTER'] ?? '';

                                                    if (!$is_gift_refund && !empty($refund['ENROLLMENT_BY_ID'])) {
                                                        $enrollment_by = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLOSER FROM DOA_USERS WHERE PK_USER = " . $refund['ENROLLMENT_BY_ID']);
                                                    } else {
                                                        $enrollment_by = null;
                                                    }

                                                    if (!$is_gift_refund && !empty($refund['PK_ENROLLMENT_MASTER'])) {
                                                        $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER LEFT JOIN $account_database.DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $refund['PK_ENROLLMENT_MASTER']);
                                                    } else {
                                                        $service_provider = null;
                                                    }

                                                    $teacher = [];
                                                    if ($service_provider && $service_provider->RecordCount() > 0) {
                                                        while (!$service_provider->EOF) {
                                                            $teacher[] = $service_provider->fields['TEACHER'];
                                                            $service_provider->MoveNext();
                                                        }
                                                    }

                                                    $enrollment_balance = !empty($refund['TOTAL_AMOUNT']) ? $refund['TOTAL_AMOUNT'] - $refund['AMOUNT'] : 0;

                                                    // Payment type logic for refunds
                                                    if ($is_gift_refund) {
                                                        $refund_payment_type = 'Gift Certificate Refund';
                                                        $enrollment_name = '';
                                                        $ENROLLMENT_ID = '';
                                                        $MISC_ID = '';
                                                        $client_name = '';
                                                        $total_amount_display = '';
                                                        $enrollment_date_display = '';
                                                        $enrollment_type_display = '';
                                                        $enrollment_balance_display = '';
                                                    } elseif ($refund['PK_PAYMENT_TYPE'] == '2') {
                                                        $payment_info = json_decode($refund['PAYMENT_INFO']);
                                                        $refund_payment_type = $refund['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                                                        $ENROLLMENT_ID = $refund['ENROLLMENT_ID'] ?? '';
                                                        $MISC_ID = $refund['MISC_ID'] ?? '';
                                                        $client_name = $refund['CLIENT'] ?? '';
                                                        $total_amount_display = !empty($refund['TOTAL_AMOUNT']) ? '$' . number_format($refund['TOTAL_AMOUNT'], 2) : '';
                                                        $enrollment_date_display = !empty($refund['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($refund['ENROLLMENT_DATE'])) : '';
                                                        $enrollment_type_display = !empty($refund['ENROLLMENT_TYPE']) ? $refund['ENROLLMENT_TYPE'] : '';
                                                        $enrollment_balance_display = !empty($refund['TOTAL_AMOUNT']) ? '$' . number_format($refund['TOTAL_AMOUNT'] - $refund['AMOUNT'], 2) : '';
                                                    } elseif (in_array($refund['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                                                        $payment_info = json_decode($refund['PAYMENT_INFO']);
                                                        $refund_payment_type = $refund['PAYMENT_TYPE'] . " # " . ((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
                                                        $ENROLLMENT_ID = $refund['ENROLLMENT_ID'] ?? '';
                                                        $MISC_ID = $refund['MISC_ID'] ?? '';
                                                        $client_name = $refund['CLIENT'] ?? '';
                                                        $total_amount_display = !empty($refund['TOTAL_AMOUNT']) ? '$' . number_format($refund['TOTAL_AMOUNT'], 2) : '';
                                                        $enrollment_date_display = !empty($refund['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($refund['ENROLLMENT_DATE'])) : '';
                                                        $enrollment_type_display = !empty($refund['ENROLLMENT_TYPE']) ? $refund['ENROLLMENT_TYPE'] : '';
                                                        $enrollment_balance_display = !empty($refund['TOTAL_AMOUNT']) ? '$' . number_format($refund['TOTAL_AMOUNT'] - $refund['AMOUNT'], 2) : '';
                                                    } else {
                                                        $refund_payment_type = $refund['PAYMENT_TYPE'];
                                                        $ENROLLMENT_ID = $refund['ENROLLMENT_ID'] ?? '';
                                                        $MISC_ID = $refund['MISC_ID'] ?? '';
                                                        $client_name = $refund['CLIENT'] ?? '';
                                                        $total_amount_display = !empty($refund['TOTAL_AMOUNT']) ? '$' . number_format($refund['TOTAL_AMOUNT'], 2) : '';
                                                        $enrollment_date_display = !empty($refund['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($refund['ENROLLMENT_DATE'])) : '';
                                                        $enrollment_type_display = !empty($refund['ENROLLMENT_TYPE']) ? $refund['ENROLLMENT_TYPE'] : '';
                                                        $enrollment_balance_display = !empty($refund['TOTAL_AMOUNT']) ? '$' . number_format($refund['TOTAL_AMOUNT'] - $refund['AMOUNT'], 2) : '';
                                                    }

                                                    $name = $refund['ENROLLMENT_NAME'] ?? '';
                                                    $ENROLLMENT_ID = $refund['ENROLLMENT_ID'] ?? '';
                                                    $MISC_ID = $refund['MISC_ID'] ?? '';
                                                    if (empty($name)) {
                                                        $enrollment_name = '';
                                                    } else {
                                                        $enrollment_name = "$name" . " - ";
                                                    }
                                                ?>
                                                    <tr>
                                                        <td style="text-align: center; color: red"><?= date('m/d/Y', strtotime($refund['PAYMENT_DATE'])) ?></td>
                                                        <td style="text-align: right; color: red">$<?= number_format($refund_total, 2) ?></td>
                                                        <td style="text-align: right; color: red">$<?= number_format($refund['AMOUNT'], 2) ?></td>
                                                        <td style="text-align: right; color: red">$<?= number_format($refund_tip, 2) ?></td>
                                                        <?php if ($refund['PAYMENT_TYPE'] == 'Cash' && !$is_gift_refund) { ?>
                                                            <td style="text-align: center; color: red"><?= $refund['TYPE'] ?></td>
                                                        <?php } else { ?>
                                                            <td style="text-align: center; color: red"><?= $refund_payment_type ?></td>
                                                        <?php } ?>
                                                        <td style="text-align: center; color: red"><?= $refund['PAYMENT_TYPE'] ?></td>
                                                        <?php if ($refund['PAYMENT_TYPE'] == 'Credit Card' || $refund['PAYMENT_TYPE'] == 'Visa' || $refund['PAYMENT_TYPE'] == 'Master Card' || $refund['PAYMENT_TYPE'] == 'American Express' || $refund['PAYMENT_TYPE'] == 'Card' || $refund['PAYMENT_TYPE'] == 'Card On File') { ?>
                                                            <td style="text-align: center; color: red"><?= $refund['PAYMENT_TYPE'] ?></td>
                                                        <?php } else { ?>
                                                            <td style="text-align: center; color: red"></td>
                                                        <?php } ?>
                                                        <td style="text-align: center; color: red"><?= $refund['RECEIPT_NUMBER'] ?></td>
                                                        <td style="text-align: center; color: red"><?= $refund['MEMO'] ?? '' ?></td>
                                                        <td style="text-align: center; color: red"><?= $client_name ?></td>
                                                        <td style="text-align: center; color: red"><?= ($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $MISC_ID : $enrollment_name . $ENROLLMENT_ID ?></td>
                                                        <td style="text-align: center; color: red"><?= $enrollment_date_display ?></td>
                                                        <td style="text-align: center; color: red"><?= $enrollment_type_display ?></td>
                                                        <td style="text-align: right; color: red"><?= $total_amount_display ?></td>
                                                        <td style="text-align: right; color: red"><?= $enrollment_balance_display ?></td>
                                                        <td style="text-align: left; color: red"><?= !empty($enrollment_by->fields['CLOSER']) ? $enrollment_by->fields['CLOSER'] : '' ?></td>
                                                        <td style="text-align: center; color: red"><?= isset($teacher[0]) ? $teacher[0] : '' ?></td>
                                                        <td style="text-align: center; color: red"><?= isset($teacher[1]) ? $teacher[1] : '' ?></td>
                                                        <td style="text-align: center; color: red"><?= isset($teacher[2]) ? $teacher[2] : '' ?></td>
                                                    </tr>
                                                <?php } ?>

                                                <!-- Total row -->
                                                <tr style="font-weight: bold">
                                                    <td style="text-align: center">Total</td>
                                                    <td style="text-align: right">$<?= number_format(($total_amount + $total_tips) - ($total_refund + $total_refund_tips), 2) ?></td>
                                                    <td style="text-align: right">$<?= number_format($total_amount - $total_refund, 2) ?></td>
                                                    <td style="text-align: right">$<?= number_format($total_tips - $total_refund_tips, 2) ?></td>
                                                    <td colspan="15"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>

<script>
    $(document).ready(function() {
        // Initialize datepickers
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true,
            todayHighlight: true
        });

        // Form validation
        $('#reportForm').on('submit', function(e) {
            var startDate = $('#START_DATE').val();
            var endDate = $('#END_DATE').val();

            // Validate dates are filled
            if (!startDate || !endDate) {
                alert('Please select both start date and end date.');
                e.preventDefault();
                return false;
            }

            // Validate date range
            var start = new Date(startDate);
            var end = new Date(endDate);

            if (start > end) {
                alert('Start date cannot be after end date.');
                e.preventDefault();
                return false;
            }

            return true;
        });
    });
</script>