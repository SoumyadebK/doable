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

$week_number = $_SESSION['week_number'];
$YEAR = date('Y', strtotime($_SESSION['start_date']));

$from_date = date('Y-m-d', strtotime($_SESSION['start_date']));
$to_date = date('Y-m-d', strtotime($_SESSION['end_date']));

$payment_date = "AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
$enrollment_date = "AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
$appointment_date = "AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';
if (preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $business_name)) {
    $business_name = '';
} else {
    $business_name = '' . $business_name;
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
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>
<style>
    table,
    td,
    th {
        border: 1px solid black;
        padding: 10px;
    }

    #collapseTable {
        border-collapse: collapse;
    }

    body {
        font-size: 12px;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div>
                        <h3 class="card-title" style="text-align: center; font-size:medium; font-weight: bold"><?= $title ?></h3>
                    </div>

                    <div class="table-responsive">
                        <table id="collapseTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="10"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= " (" . $concatenatedResults . ")" ?></th>
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
                                $all_payments = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.TYPE, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.MISC_ID, ENROLLMENT_DATE, ENROLLMENT_TYPE, TOTAL_AMOUNT, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_USERS.IS_DELETED =0 AND IS_REFUNDED = 0 AND DOA_ENROLLMENT_PAYMENT.NOT_EXPORT_TO_AMI = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $payment_date . " ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC");

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
                                    NULL AS CLIENT
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
                                    if ($gift_data['TYPE'] == 'Refund Gift Certificate') {
                                        $refund_payments[] = $gift_data;
                                    } else {
                                        $regular_payments[] = $gift_data;
                                    }
                                    $gift_payments->MoveNext();
                                }

                                // Get wallet payments
                                $total_wallet = 0;
                                $wallet_payments = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.*, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, DOA_PAYMENT_TYPE.PAYMENT_TYPE, DOA_CUSTOMER_WALLET.BALANCE_LEFT FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_CUSTOMER_WALLET ON DOA_ENROLLMENT_PAYMENT.PK_CUSTOMER_WALLET = DOA_CUSTOMER_WALLET.PK_CUSTOMER_WALLET LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_CUSTOMER_WALLET.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE = DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.TYPE = 'Wallet' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO != 'Gift Certificate' AND DOA_ENROLLMENT_PAYMENT.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC");
                                ?>

                                <!-- Display wallet payments first -->
                                <?php while (!$wallet_payments->EOF) {
                                    $total_wallet += $wallet_payments->fields['AMOUNT'];
                                    if ($wallet_payments->fields['BALANCE_LEFT'] > 0) {
                                ?>
                                        <tr>
                                            <td style="text-align: center"><?= date('m/d/Y', strtotime($wallet_payments->fields['PAYMENT_DATE'])) ?></td>
                                            <td style="text-align: right">$<?= $wallet_payments->fields['BALANCE_LEFT'] ?></td>
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
                                            <td></td>
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

                                foreach ($regular_payments as $payment) {
                                    $name = $payment['ENROLLMENT_NAME'] ?? '';
                                    if (empty($name)) {
                                        $enrollment_name = '';
                                    } else {
                                        $enrollment_name = "$name" . " - ";
                                    }
                                    $PK_USER_MASTER = $payment['PK_USER_MASTER'] ?? '';

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
                                        $total_amount += $payment['AMOUNT'];
                                    } elseif ($payment['TYPE'] == 'Move') {
                                        $payment_type = 'Wallet';
                                        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
                                        $MISC_ID = $payment['MISC_ID'] ?? '';
                                        $client_name = $payment['CLIENT'] ?? '';
                                        $total_amount_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'], 2) : '';
                                        $enrollment_date_display = !empty($payment['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($payment['ENROLLMENT_DATE'])) : '';
                                        $enrollment_type_display = !empty($payment['ENROLLMENT_TYPE']) ? $payment['ENROLLMENT_TYPE'] : '';
                                        $enrollment_balance_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'] - $payment['AMOUNT'], 2) : '';
                                        $total_amount += $payment['AMOUNT'];
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
                                        $total_amount += $payment['AMOUNT'];
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
                                        $total_amount += $payment['AMOUNT'];
                                    } else {
                                        $payment_type = $payment['PAYMENT_TYPE'];
                                        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
                                        $MISC_ID = $payment['MISC_ID'] ?? '';
                                        $client_name = $payment['CLIENT'] ?? '';
                                        $total_amount_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'], 2) : '';
                                        $enrollment_date_display = !empty($payment['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($payment['ENROLLMENT_DATE'])) : '';
                                        $enrollment_type_display = !empty($payment['ENROLLMENT_TYPE']) ? $payment['ENROLLMENT_TYPE'] : '';
                                        $enrollment_balance_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'] - $payment['AMOUNT'], 2) : '';
                                        $total_amount += $payment['AMOUNT'];
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
                                        <td style="text-align: right">$<?= $payment['AMOUNT'] ?></td>
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
                                        <td style="text-align: right; color: red">$<?= $refund['AMOUNT'] ?></td>
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
                                    <td style="text-align: right">$<?= number_format($total_amount - $total_refund, 2) ?></td>
                                    <td colspan="15"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>