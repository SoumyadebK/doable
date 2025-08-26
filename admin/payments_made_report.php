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
    $business_name = ''.$business_name;
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
    // Append the current result to the concatenated string
    $concatenatedResults .= $result;

    // If it's not the last result, append a comma
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
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

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
                                <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                                <li class="breadcrumb-item active"><a href="customer_summary_report.php"><?= $title ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <?php
                if ($type === 'export') {
                    echo "<h3>Data export to Arthur Murray API Successfully</h3>";
                    /*$data = json_decode($post_data);
                if (isset($data->error)) {
                    echo '<div class="alert alert-danger alert-dismissible" role="alert">'.$data->error_description.'</div>';
                } elseif (isset($data->errors)) {
                    if (isset($data->errors->errors[0])) {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">' . $data->errors->errors[0] . '</div>';
                    } else {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">'.$data->message.'</div>';
                    }
                } else {
                    echo "<h3>Data export to Arthur Murray API Successfully</h3>";
                }*/
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
                                                    <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="10"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= $business_name . " (" . $concatenatedResults . ")" ?></th>
                                                    <th style="width:50%; text-align: center; font-weight: bold" colspan="7">(<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</th>
                                                </tr>
                                                <tr>
                                                    <th style="width:10%; text-align: center">Payment Date</th>
                                                    <th style="width:10%; text-align: center">Payment Amount</th>
                                                    <th style="width:10%; text-align: center">Payment Title</th>
                                                    <th style="width:12%; text-align: center">Payment Method</th>
                                                    <th style="width:10%; text-align: center">Card Type</th>
                                                    <th style="width:10%; text-align: center">Receipt</th>
                                                    <th style="width:10%; text-align: center">Memo</th>
                                                    <th style="width:10%; text-align: center">Client</th>
                                                    <th style="width:10%; text-align: center">Enrollment Name</th>
                                                    <th style="width:10%; text-align: center">Enrollment Date</th>
                                                    <th style="width:10%; text-align: center">Enrollment Type</th>
                                                    <th style="width:10%; text-align: center">Enrollment Cost</th>
                                                    <th style="width:10%; text-align: center">Enrollment Balance</th>
                                                    <th style="width:10%; text-align: center">Closer</th>
                                                    <th style="width:10%; text-align: center">Teacher1</th>
                                                    <th style="width:10%; text-align: center">Teacher2</th>
                                                    <th style="width:10%; text-align: center">Teacher3</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Get all payments first and separate regular payments from refunds
                                                $all_payments = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.TYPE, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, ENROLLMENT_NAME, ENROLLMENT_DATE, ENROLLMENT_TYPE, TOTAL_AMOUNT, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE != 7 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $payment_date . " ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC");

                                                // Separate regular payments and refunds
                                                $regular_payments = [];
                                                $refund_payments = [];

                                                while (!$all_payments->EOF) {
                                                    if ($all_payments->fields['TYPE'] == 'Refund') {
                                                        $refund_payments[] = $all_payments->fields;
                                                    } else {
                                                        $regular_payments[] = $all_payments->fields;
                                                    }
                                                    $all_payments->MoveNext();
                                                }

                                                // Get wallet payments
                                                $total_wallet = 0;
                                                $wallet_payments = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.*, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_CUSTOMER_WALLET ON DOA_ENROLLMENT_PAYMENT.PK_CUSTOMER_WALLET = DOA_CUSTOMER_WALLET.PK_CUSTOMER_WALLET LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_CUSTOMER_WALLET.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER LEFT JOIN $master_database.DOA_USER_LOCATION AS DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE = DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.TYPE = 'Wallet' AND DOA_ENROLLMENT_PAYMENT.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC");
                                                ?>

                                                <!-- Display wallet payments first -->
                                                <?php while (!$wallet_payments->EOF) { 
                                                    $total_wallet += $wallet_payments->fields['AMOUNT'];
                                                    ?>
                                                    <tr>
                                                        <td style="text-align: center"><?= date('m/d/Y', strtotime($wallet_payments->fields['PAYMENT_DATE'])) ?></td>
                                                        <td style="text-align: right">$<?= $wallet_payments->fields['AMOUNT'] ?></td>
                                                        <td style="text-align: left">Wallet</td>
                                                        <td style="text-align: center"><?= $wallet_payments->fields['PAYMENT_TYPE'] ?></td>
                                                        <td style="text-align: center">-</td>
                                                        <td style="text-align: center"><?= $wallet_payments->fields['RECEIPT_NUMBER'] ?></td>
                                                        <td style="text-align: left"><?= $wallet_payments->fields['MEMO'] ?? '-' ?></td>
                                                        <td style="text-align: left"><?= $wallet_payments->fields['CLIENT'] ?></td>
                                                        <td style="text-align: center">-</td>
                                                        <td style="text-align: center">-</td>
                                                        <td style="text-align: center">-</td>
                                                        <td style="text-align: right">-</td>
                                                        <td style="text-align: right">-</td>
                                                        <td style="text-align: left">-</td>
                                                        <td style="text-align: left">-</td>
                                                        <td></td>
                                                    </tr>
                                                    <?php
                                                    $wallet_payments->MoveNext();
                                                } ?>

                                                <!-- Display regular payments -->
                                                <?php
                                                $i = 1;
                                                $total_amount = 0;
                                                $total_refund = 0;

                                                foreach ($regular_payments as $payment) {
                                                    $PK_USER_MASTER = $payment['PK_USER_MASTER'];
                                                    $enrollment_by = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLOSER FROM DOA_USERS WHERE PK_USER = " . $payment['ENROLLMENT_BY_ID']);
                                                    $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER LEFT JOIN $account_database.DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $payment['PK_ENROLLMENT_MASTER']);
                                                    
                                                    $teacher = '';
                                                    if ($service_provider->RecordCount() > 0) {
                                                        while (!$service_provider->EOF) {
                                                            $teacher = $service_provider->fields['TEACHER'];
                                                            $service_provider->MoveNext();
                                                        }
                                                    }
                                                    
                                                    $enrollment_balance = $payment['TOTAL_AMOUNT'] - $payment['AMOUNT'];
                                                    $total_amount += $payment['AMOUNT'];
                                                    
                                                    if ($payment['TYPE'] == 'Move') {
                                                        $payment_type = 'Wallet';
                                                    } elseif ($payment['PK_PAYMENT_TYPE'] == '2') {
                                                        $payment_info = json_decode($payment['PAYMENT_INFO']);
                                                        $payment_type = $payment['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                                                    } elseif (in_array($payment['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                                                        $payment_info = json_decode($payment['PAYMENT_INFO']);
                                                        $payment_type = $payment['PAYMENT_TYPE'] . " # " . ((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
                                                    } elseif ($payment['PK_PAYMENT_TYPE'] == '7') {
                                                        $receipt_number_array = explode(',', $payment['RECEIPT_NUMBER']);
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
                                                        $payment_type = $payment['PAYMENT_TYPE'];
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td style="text-align: center"><?= date('m-d-Y', strtotime($payment['PAYMENT_DATE'])) ?></td>
                                                        <td style="text-align: right">$<?= $payment['AMOUNT'] ?></td>
                                                        <td style="text-align: left"><?= $payment_type ?></td>
                                                        <td style="text-align: center"><?= $payment['PAYMENT_TYPE'] ?></td>
                                                        <?php if ($payment['PAYMENT_TYPE'] == 'Credit Card' || $payment['PAYMENT_TYPE'] == 'Visa' || $payment['PAYMENT_TYPE'] == 'Master Card' || $payment['PAYMENT_TYPE'] == 'American Express' || $payment['PAYMENT_TYPE'] == 'Card' || $payment['PAYMENT_TYPE'] == 'Card On File') { ?>
                                                            <td style="text-align: center"><?= $payment['PAYMENT_TYPE'] ?></td>
                                                        <?php } else { ?>
                                                            <td style="text-align: center"></td>
                                                        <?php } ?>
                                                        <td style="text-align: right"><?= $payment['RECEIPT_NUMBER'] ?></td>
                                                        <td style="text-align: left"><?= $payment['MEMO'] ?></td>
                                                        <td style="text-align: left"><?= $payment['CLIENT'] ?></td>
                                                        <td style="text-align: left"><?= $payment['ENROLLMENT_NAME'] ?></td>
                                                        <td style="text-align: center"><?= date('m-d-Y', strtotime($payment['ENROLLMENT_DATE'])) ?></td>
                                                        <td style="text-align: right"><?= $payment['ENROLLMENT_TYPE'] ?></td>
                                                        <td style="text-align: right">$<?= $payment['TOTAL_AMOUNT'] ?></td>
                                                        <td style="text-align: right">$<?= number_format($enrollment_balance, 2) ?></td>
                                                        <td style="text-align: left"><?= !empty($enrollment_by->fields['CLOSER']) ? $enrollment_by->fields['CLOSER'] : '' ?></td>
                                                        <td style="text-align: left"><?= $teacher ?></td>
                                                        <td></td>
                                                    </tr>
                                                    <?php
                                                    $i++;
                                                }
                                                ?>

                                                <!-- Display all refunds at the bottom -->
                                                <?php foreach ($refund_payments as $refund) {
                                                    $total_refund += $refund['AMOUNT'];
                                                    $PK_USER_MASTER = $refund['PK_USER_MASTER'];
                                                    $enrollment_by = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLOSER FROM DOA_USERS WHERE PK_USER = " . $refund['ENROLLMENT_BY_ID']);
                                                    $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER LEFT JOIN $account_database.DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $refund['PK_ENROLLMENT_MASTER']);
                                                    
                                                    $teacher = '';
                                                    if ($service_provider->RecordCount() > 0) {
                                                        while (!$service_provider->EOF) {
                                                            $teacher = $service_provider->fields['TEACHER'];
                                                            $service_provider->MoveNext();
                                                        }
                                                    }
                                                    
                                                    $enrollment_balance = $refund['TOTAL_AMOUNT'] - $refund['AMOUNT'];
                                                    
                                                    // Payment type logic for refunds
                                                    if ($refund['PK_PAYMENT_TYPE'] == '2') {
                                                        $payment_info = json_decode($refund['PAYMENT_INFO']);
                                                        $refund_payment_type = $refund['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                                                    } elseif (in_array($refund['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                                                        $payment_info = json_decode($refund['PAYMENT_INFO']);
                                                        $refund_payment_type = $refund['PAYMENT_TYPE'] . " # " . ((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
                                                    } elseif ($refund['PK_PAYMENT_TYPE'] == '7') {
                                                        $receipt_number_array = explode(',', $refund['RECEIPT_NUMBER']);
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
                                                        $refund_payment_type = implode(', ', $payment_type_array);
                                                    } else {
                                                        $refund_payment_type = $refund['PAYMENT_TYPE'];
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td style="text-align: center; color: red"><?= date('m-d-Y', strtotime($refund['PAYMENT_DATE'])) ?></td>
                                                        <td style="text-align: right; color: red">$<?= $refund['AMOUNT'] ?></td>
                                                        <?php if ($refund['PAYMENT_TYPE'] == 'Cash') { ?>
                                                            <td style="text-align: left; color: red"><?= $refund['TYPE'] ?></td>
                                                        <?php } else { ?>
                                                            <td style="text-align: left; color: red"><?= '(Refund) '.$refund_payment_type ?></td>
                                                        <?php } ?>
                                                        <td style="text-align: center; color: red"><?= $refund['PAYMENT_TYPE'] ?></td>
                                                        <?php if ($refund['PAYMENT_TYPE'] == 'Credit Card' || $refund['PAYMENT_TYPE'] == 'Visa' || $refund['PAYMENT_TYPE'] == 'Master Card' || $refund['PAYMENT_TYPE'] == 'American Express' || $refund['PAYMENT_TYPE'] == 'Card' || $refund['PAYMENT_TYPE'] == 'Card On File') { ?>
                                                            <td style="text-align: center; color: red"><?= $refund['PAYMENT_TYPE'] ?></td>
                                                        <?php } else { ?>
                                                            <td style="text-align: center; color: red"></td>
                                                        <?php } ?>
                                                        <td style="text-align: right; color: red"><?= $refund['RECEIPT_NUMBER'] ?></td>
                                                        <td style="text-align: left; color: red"><?= $refund['MEMO'] ?></td>
                                                        <td style="text-align: left; color: red"><?= $refund['CLIENT'] ?></td>
                                                        <td style="text-align: left; color: red"><?= $refund['ENROLLMENT_NAME'] ?></td>
                                                        <td style="text-align: center; color: red"><?= date('m-d-Y', strtotime($refund['ENROLLMENT_DATE'])) ?></td>
                                                        <td style="text-align: right; color: red"><?= $refund['ENROLLMENT_TYPE'] ?></td>
                                                        <td style="text-align: right; color: red">$<?= $refund['TOTAL_AMOUNT'] ?></td>
                                                        <td style="text-align: right; color: red">$<?= number_format($enrollment_balance + $refund['AMOUNT'], 2) ?></td>
                                                        <td style="text-align: left; color: red"><?= !empty($enrollment_by->fields['CLOSER']) ? $enrollment_by->fields['CLOSER'] : '' ?></td>
                                                        <td style="text-align: left; color: red"><?= $teacher ?></td>
                                                        <td></td>
                                                    </tr>
                                                <?php } ?>

                                                <!-- Total row -->
                                                <tr style="font-weight: bold">
                                                    <td style="text-align: center">Total</td>
                                                    <td style="text-align: right">$<?= number_format(($total_amount + $total_wallet) - $total_refund, 2) ?></td>
                                                    <td colspan="15"></td> <!-- Empty cells for remaining columns -->
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