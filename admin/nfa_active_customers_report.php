<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "NFA ACTIVE ENROLLED CUSTOMERS REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$today = date('Y-m-d');

if (!empty($_GET['selected_range'])) {
    $selected_range = $_GET['selected_range'];
    $selected_date = date('Y-m-d', strtotime($_GET['selected_date']));
    $enrollment_date_condition = "AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE >= DATE_SUB('" . $selected_date . "', INTERVAL " . $selected_range . " MONTH) AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE <= '" . $selected_date . "'";
} else {
    $selected_date = date('Y-m-d', strtotime($_GET['selected_date']));
    $enrollment_date_condition = "AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE = '" . date('Y-m-d', strtotime($selected_date)) . "'";
}

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
    // Append the current result to the concatenated string
    $concatenatedResults .= $result;

    // If it's not the last result, append a comma
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

if (isset($_POST['SUBMIT'])) {
    $PK_ENROLLMENT_MASTER = $_POST['PK_ENROLLMENT_MASTER'];
    $PK_PAYMENT_TYPE_REFUND = ($_POST['PK_PAYMENT_TYPE_REFUND']) ?? 0;
    $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_NAME, ENROLLMENT_ID, PK_ENROLLMENT_BILLING FROM DOA_ENROLLMENT_MASTER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
    if (empty($enrollment_data->fields['ENROLLMENT_NAME'])) {
        $enrollment_name = '';
    } else {
        $enrollment_name = $enrollment_data->fields['ENROLLMENT_NAME'] . " - ";
    }
    if (empty($enrollment_data->fields['ENROLLMENT_ID'])) {
        $enrollment_id = $enrollment_data->fields['MISC_ID'];
    } else {
        $enrollment_id = $enrollment_data->fields['ENROLLMENT_ID'];
    }
    $TOTAL_POSITIVE_BALANCE = $_POST['TOTAL_POSITIVE_BALANCE'];
    $TOTAL_NEGATIVE_BALANCE = $_POST['TOTAL_NEGATIVE_BALANCE'];

    if ($TOTAL_POSITIVE_BALANCE == 0 && $TOTAL_NEGATIVE_BALANCE == 0) {
        $UPDATE_DATA['STATUS'] = 'C';
    } else {
        $UPDATE_DATA['STATUS'] = 'CA';
    }

    if ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 1) {
        $APPOINTMENT_UPDATE_DATA['PK_APPOINTMENT_STATUS'] = 6;
        $APPOINTMENT_UPDATE_DATA['STATUS'] = 'C';
        $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_ENROLLMENT` WHERE `PK_ENROLLMENT_MASTER` = '$PK_ENROLLMENT_MASTER' AND IS_CHARGED = 1");
        $CONDITION = " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER' AND IS_CHARGED = 0";
    } elseif ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 2) {
        $APPOINTMENT_UPDATE_DATA['PK_APPOINTMENT_STATUS'] = 6;
        $APPOINTMENT_UPDATE_DATA['STATUS'] = 'C';
        $CONDITION = " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER' AND IS_CHARGED = 0 AND IS_PAID = 0";
    } elseif ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 3) {
        $APPOINTMENT_UPDATE_DATA['PK_ENROLLMENT_MASTER'] = 0;
        $APPOINTMENT_UPDATE_DATA['PK_ENROLLMENT_SERVICE'] = 0;
        $APPOINTMENT_UPDATE_DATA['APPOINTMENT_TYPE'] = 'AD-HOC';
        $APPOINTMENT_UPDATE_DATA['IS_PAID'] = 0;
        $CONDITION = " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER' AND IS_CHARGED = 0";
    }
    db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_UPDATE_DATA, 'update', $CONDITION);

    $BALANCE = $TOTAL_POSITIVE_BALANCE + $TOTAL_NEGATIVE_BALANCE;

    $TOTAL_ACTUAL_AMOUNT = 0;
    for ($i = 0; $i < count($_POST['PK_ENROLLMENT_SERVICE']); $i++) {
        $enr_service_data = $db_account->Execute("SELECT PRICE_PER_SESSION, TOTAL_AMOUNT_PAID, FINAL_AMOUNT FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_SERVICE = " . $_POST['PK_ENROLLMENT_SERVICE'][$i]);
        if ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 1 || $_POST['CANCEL_FUTURE_APPOINTMENT'] == 3) {
            $ENR_SERVICE_UPDATE['NUMBER_OF_SESSION'] = getSessionCompletedCount($_POST['PK_ENROLLMENT_SERVICE'][$i]);
        } elseif ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 2) {
            $ENR_SERVICE_UPDATE['NUMBER_OF_SESSION'] = getPaidSessionCount($_POST['PK_ENROLLMENT_SERVICE'][$i]);
        }

        $TOTAL_PAID_AMOUNT = $ENR_SERVICE_UPDATE['NUMBER_OF_SESSION'] * $enr_service_data->fields['PRICE_PER_SESSION'];
        if ($TOTAL_POSITIVE_BALANCE >= 0) {
            $ENR_SERVICE_UPDATE['TOTAL_AMOUNT_PAID'] = ($enr_service_data->fields['TOTAL_AMOUNT_PAID'] < $TOTAL_PAID_AMOUNT) ? $enr_service_data->fields['TOTAL_AMOUNT_PAID'] : $TOTAL_PAID_AMOUNT;
        }

        $ENR_SERVICE_UPDATE['FINAL_AMOUNT'] = $TOTAL_PAID_AMOUNT;
        db_perform_account('DOA_ENROLLMENT_SERVICE', $ENR_SERVICE_UPDATE, 'update', " PK_ENROLLMENT_SERVICE = " . $_POST['PK_ENROLLMENT_SERVICE'][$i]);

        $CANCEL_ENROLLMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
        $CANCEL_ENROLLMENT_DATA['PK_ENROLLMENT_SERVICE'] = $_POST['PK_ENROLLMENT_SERVICE'][$i];
        $CANCEL_ENROLLMENT_DATA['ACTUAL_AMOUNT'] = $enr_service_data->fields['FINAL_AMOUNT'];
        $CANCEL_ENROLLMENT_DATA['CANCEL_AMOUNT'] = $enr_service_data->fields['FINAL_AMOUNT'] - $ENR_SERVICE_UPDATE['FINAL_AMOUNT'];
        $CANCEL_ENROLLMENT_DATA['CANCEL_DATE'] = date('Y-m-d H:i:s');
        db_perform_account('DOA_ENROLLMENT_CANCEL', $CANCEL_ENROLLMENT_DATA, 'insert');

        $TOTAL_ACTUAL_AMOUNT += $ENR_SERVICE_UPDATE['FINAL_AMOUNT'];
    }
    $ENR_BILLING_UPDATE['TOTAL_AMOUNT'] = $ENR_BILLING_UPDATE['BALANCE_PAYABLE'] = $TOTAL_ACTUAL_AMOUNT;
    db_perform_account('DOA_ENROLLMENT_BILLING', $ENR_BILLING_UPDATE, 'update', " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");

    /*if ($_POST['USE_AVAILABLE_CREDIT'] == 1) {
        $TOTAL_POSITIVE_BALANCE += $TOTAL_NEGATIVE_BALANCE;
        $TOTAL_NEGATIVE_BALANCE = $TOTAL_POSITIVE_BALANCE;
        for ($i = 0; $i < count($_POST['PK_ENROLLMENT_SERVICE']); $i++) {
            $ENR_SERVICE_UPDATE['TOTAL_AMOUNT_PAID'] = $_POST['TOTAL_AMOUNT_PAID'][$i];
            db_perform_account('DOA_ENROLLMENT_SERVICE', $ENR_SERVICE_UPDATE, 'update'," PK_ENROLLMENT_SERVICE = ".$_POST['PK_ENROLLMENT_SERVICE'][$i]);
        }
    }*/

    db_perform_account('DOA_ENROLLMENT_MASTER', $UPDATE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
    db_perform_account('DOA_ENROLLMENT_SERVICE', $UPDATE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
    db_perform_account('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");

    if ($TOTAL_NEGATIVE_BALANCE < 0) {
        $LEDGER_DATA_BILLING['TRANSACTION_TYPE'] = ($_POST['SUBMIT'] == 'Cancel and Store Info only') ? 'Balance Owed' : 'Billing';
        $LEDGER_DATA_BILLING['ENROLLMENT_LEDGER_PARENT'] = -1;
        $LEDGER_DATA_BILLING['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
        $LEDGER_DATA_BILLING['PK_ENROLLMENT_BILLING'] = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
        $LEDGER_DATA_BILLING['PAID_AMOUNT'] = 0.00;
        $LEDGER_DATA_BILLING['IS_PAID'] = 0;
        $LEDGER_DATA_BILLING['STATUS'] = 'A';
        $LEDGER_DATA_BILLING['DUE_DATE'] = date('Y-m-d');
        $LEDGER_DATA_BILLING['BILLED_AMOUNT'] = abs($TOTAL_NEGATIVE_BALANCE);
        $LEDGER_DATA_BILLING['BALANCE'] = abs($TOTAL_NEGATIVE_BALANCE);
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA_BILLING, 'insert');
        $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();
    } elseif ($TOTAL_POSITIVE_BALANCE >= 0) {
        $LEDGER_DATA['TRANSACTION_TYPE'] = (($TOTAL_POSITIVE_BALANCE == 0) ? 'Cancelled' : (($_POST['SUBMIT'] == 'Cancel and Store Info only') ? 'Refund Credit Available' : 'Refund'));
        $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = -1;
        $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
        $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
        $LEDGER_DATA['PAID_AMOUNT'] = 0.00;
        $LEDGER_DATA['IS_PAID'] = ($_POST['SUBMIT'] === 'Submit') ? 1 : 2;
        $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
        $LEDGER_DATA['BILLED_AMOUNT'] = 0.00;
        $LEDGER_DATA['BALANCE'] = $BALANCE;
        $LEDGER_DATA['STATUS'] = $UPDATE_DATA['STATUS'];
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
        $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();
    }

    $PK_USER_MASTER = $_POST['PK_USER_MASTER'];
    if ($TOTAL_POSITIVE_BALANCE >= 0) {
        /*$wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
        if ($wallet_data->RecordCount() > 0) {
            $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] + $BALANCE;
        } else {
            $INSERT_DATA['CURRENT_BALANCE'] = $TOTAL_POSITIVE_BALANCE;
        }
        $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
        $INSERT_DATA['CREDIT'] = $TOTAL_POSITIVE_BALANCE;
        $INSERT_DATA['DESCRIPTION'] = "Balance credited for cancellation of enrollment ".$enrollment_name.$enrollment_data->fields['ENROLLMENT_ID'];
        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');*/

        /*$LEDGER_DATA_REFUND['TRANSACTION_TYPE'] = ($_POST['SUBMIT'] == 'Cancel and Store Info only') ? 'Refund Credit Available' : 'Refund';
        $LEDGER_DATA_REFUND['ENROLLMENT_LEDGER_PARENT'] = -1;
        $LEDGER_DATA_REFUND['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
        $LEDGER_DATA_REFUND['PK_ENROLLMENT_BILLING'] = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
        $LEDGER_DATA_REFUND['PAID_AMOUNT'] = 0.00;
        $LEDGER_DATA_REFUND['IS_PAID'] = ($LEDGER_DATA_REFUND['TRANSACTION_TYPE'] === 'Refund') ? 1 : 2;
        $LEDGER_DATA_REFUND['DUE_DATE'] = date('Y-m-d');
        $LEDGER_DATA_REFUND['BILLED_AMOUNT'] = 0.00;
        $LEDGER_DATA_REFUND['BALANCE'] = $TOTAL_POSITIVE_BALANCE;
        $LEDGER_DATA_REFUND['STATUS'] = $UPDATE_DATA['STATUS'];
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA_REFUND, 'insert');
        $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();*/

        if ($_POST['SUBMIT'] === 'Submit') {
            $RECEIPT_NUMBER = generateReceiptNumber($PK_ENROLLMENT_MASTER);

            $old_payment_data = $db_account->Execute("SELECT PAYMENT_INFO FROM DOA_ENROLLMENT_PAYMENT WHERE PK_PAYMENT_TYPE = '$PK_PAYMENT_TYPE_REFUND' AND TYPE = 'Payment' AND IS_REFUNDED = 0 AND PAYMENT_STATUS = 'Success' AND PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' ORDER BY AMOUNT DESC LIMIT 1");
            $PAYMENT_INFO = ($old_payment_data->RecordCount() > 0) ? $old_payment_data->fields['PAYMENT_INFO'] : 'Refund';;
            if ($PK_PAYMENT_TYPE_REFUND == 1) {
                $payment_info = json_decode($old_payment_data->fields['PAYMENT_INFO']);
                if (isset($payment_info->CHARGE_ID)) {
                    $account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
                    $SECRET_KEY = $account_data->fields['SECRET_KEY'];

                    Stripe::setApiKey($SECRET_KEY);

                    $transaction_id = $payment_info->CHARGE_ID;
                    try {
                        $refund = \Stripe\Refund::create([
                            'charge' => $transaction_id,
                            'amount' => $TOTAL_POSITIVE_BALANCE * 100
                        ]);
                    } catch (Exception $e) {
                        echo $e->getMessage();
                        die();
                    }
                    $PAYMENT_INFO_ARRAY = ['REFUND_ID' => $refund->id, 'LAST4' => $payment_info->LAST4];
                    $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
                }
            } elseif ($PK_PAYMENT_TYPE_REFUND == 7) {
                $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
                if ($wallet_data->RecordCount() > 0) {
                    $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] + $BALANCE;
                } else {
                    $INSERT_DATA['CURRENT_BALANCE'] = $BALANCE;
                }
                $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                $INSERT_DATA['DEBIT'] = 0;
                $INSERT_DATA['CREDIT'] = $BALANCE;
                $INSERT_DATA['BALANCE_LEFT'] = $BALANCE;
                $INSERT_DATA['DESCRIPTION'] = "Balance credited from enrollment " . $enrollment_name . $enrollment_id;
                $INSERT_DATA['PK_PAYMENT_TYPE'] = 0;
                $INSERT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
                $INSERT_DATA['NOTE'] = "Balance credited from enrollment " . $enrollment_name . $enrollment_id;
                $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');
                $PK_CUSTOMER_WALLET = $db_account->Insert_ID();

                $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
                $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = 0;
                $PAYMENT_DATA['PK_PAYMENT_TYPE'] = 0;
                $PAYMENT_DATA['AMOUNT'] = $BALANCE;
                $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = 0;
                $PAYMENT_DATA['PK_CUSTOMER_WALLET'] = $PK_CUSTOMER_WALLET;
                $PAYMENT_DATA['PK_LOCATION'] = getPkLocation();
                $PAYMENT_DATA['TYPE'] = 'Wallet';
                $PAYMENT_DATA['NOTE'] = "Balance credited from enrollment " . $enrollment_name . $enrollment_id;
                $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
                $PAYMENT_DATA['PAYMENT_INFO'] = '';
                $PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
                $PAYMENT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
                $PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = 1;
                db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');
            } elseif ($PK_PAYMENT_TYPE_REFUND == 2) {
                $PAYMENT_INFO_ARRAY = ['CHECK_NUMBER' => $_POST['REFUND_CHECK_NUMBER'], 'CHECK_DATE' => date('Y-m-d', strtotime($_POST['REFUND_CHECK_DATE']))];
                $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
            }

            if ($TOTAL_POSITIVE_BALANCE > 0) {
                $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
                $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $PK_PAYMENT_TYPE_REFUND;
                $PAYMENT_DATA['AMOUNT'] = $TOTAL_POSITIVE_BALANCE;
                $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
                $PAYMENT_DATA['TYPE'] = 'Refund';
                $PAYMENT_DATA['NOTE'] = "Balance credited from enrollment " . $enrollment_name . $enrollment_id;
                $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
                $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                $PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
                $PAYMENT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
                $PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = 1;
                db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');
            }
        }
    }

    $db_account->Execute("UPDATE DOA_APPOINTMENT_MASTER SET PK_ENROLLMENT_MASTER = 0, PK_ENROLLMENT_SERVICE = 0, APPOINTMENT_TYPE = 'AD-HOC' WHERE APPOINTMENT_TYPE = 'NORMAL' AND PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
    markAdhocAppointmentNormal($PK_ENROLLMENT_MASTER);
    markAdhocAppointmentNormal($PK_ENROLLMENT_MASTER);
    markAdhocAppointmentNormal($PK_ENROLLMENT_MASTER);
    markAdhocAppointmentNormal($PK_ENROLLMENT_MASTER);
    markAdhocAppointmentNormal($PK_ENROLLMENT_MASTER);

    markEnrollmentComplete($PK_ENROLLMENT_MASTER);
    header('location:nfa_active_customers_report.php');
}

// Handle inactive action
if (isset($_GET['inactive']) && isset($_GET['enrollment'])) {
    $PK_USER = $_GET['inactive'];
    $PK_ENROLLMENT_MASTER = $_GET['enrollment'];

    // Update user to inactive
    $db->Execute("UPDATE DOA_USERS SET ACTIVE = 0 WHERE PK_USER = $PK_USER");

    // Optional: Also cancel enrollment
    // $db_account->Execute("UPDATE DOA_ENROLLMENT_MASTER SET STATUS = 'C' WHERE PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER");

    // Redirect back to same page
    header('location: nfa_active_customers_report.php');
    exit;
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
                                                <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="9"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= " (" . $concatenatedResults . ")" ?></th>
                                            </tr>
                                            <tr>
                                                <th style="text-align: center;">Customer Name</th>
                                                <th style="text-align: center;">Enrollment Name / Number</th>
                                                <th style="text-align: center;">Enrollment Date</th>
                                                <th style="text-align: center;">Total</th>
                                                <th style="text-align: center;">Session Left</th>
                                                <th style="text-align: center;">Service Provider</th>
                                                <th style="text-align: center;">Last Appointment Date</th>
                                                <th style="text-align: center;">Service Provider in the Last Appointment</th>
                                                <th style="text-align: center;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;

                                            $row = $db_account->Execute("SELECT 
                                                                            DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                                                                            DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE,
                                                                            DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION,
                                                                            CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME,
                                                                            DOA_USERS.PK_USER,
                                                                            DOA_USER_MASTER.PK_USER_MASTER,
                                                                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                                                                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                                                                            DOA_ENROLLMENT_MASTER.STATUS,
                                                                            DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE
                                                                        FROM DOA_ENROLLMENT_SERVICE 
                                                                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER 
                                                                        JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE 
                                                                        JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER 
                                                                        JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                                                        JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER                                                                            
                                                                        WHERE 
                                                                            DOA_ENROLLMENT_MASTER.STATUS IN ('A', 'C') AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                                                            AND DOA_SERVICE_CODE.IS_GROUP = 0 AND DOA_SERVICE_CODE.SERVICE_CODE LIKE '%PRI%'
                                                                            AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0
                                                                            AND DOA_SERVICE_MASTER.PK_SERVICE_CLASS != 5
                                                                            $enrollment_date_condition 
                                                                        
                                                                        ORDER BY CUSTOMER_NAME");
                                            while (!$row->EOF) {
                                                $appointment = $db_account->Execute("SELECT PK_APPOINTMENT_MASTER FROM DOA_APPOINTMENT_MASTER WHERE DATE > CURDATE() AND PK_APPOINTMENT_STATUS = 1 AND PK_ENROLLMENT_SERVICE = " . $row->fields['PK_ENROLLMENT_SERVICE']);
                                                if ($appointment->RecordCount() == 0) {

                                                    $results = $db_account->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                    $resultsArray = [];
                                                    while (!$results->EOF) {
                                                        $resultsArray[] = $results->fields['SERVICE_PROVIDER'];
                                                        $results->MoveNext();
                                                    }

                                                    $last_data = $db_account->Execute("SELECT DATE, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER WHERE PK_APPOINTMENT_STATUS = 2 AND PK_ENROLLMENT_SERVICE = " . $row->fields['PK_ENROLLMENT_SERVICE'] . " ORDER BY DATE DESC, START_TIME DESC LIMIT 1");

                                                    $NUMBER_OF_SESSION = getSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE']);
                                                    if ($row->fields['NUMBER_OF_SESSION'] > $NUMBER_OF_SESSION) {
                                            ?>
                                                        <tr>
                                                            <td style="text-align: center;"><a href="customer.php?id=<?= $row->fields['PK_USER'] ?>&master_id=<?= $row->fields['PK_USER_MASTER'] ?>&tab=profile" target="_blank" style="color: blue; font-weight: bold"><?= $row->fields['CUSTOMER_NAME'] ?></a></td>
                                                            <td style="text-align: center;"><?= $row->fields['ENROLLMENT_NAME'] . " / " . $row->fields['ENROLLMENT_ID'] ?></td>
                                                            <td style="text-align: center;"><?= date('m-d-Y', strtotime($row->fields['ENROLLMENT_DATE'])) ?></td>
                                                            <td style="text-align: center;"><?= $row->fields['NUMBER_OF_SESSION'] ?></td>
                                                            <td style="text-align: center;"><?= $row->fields['NUMBER_OF_SESSION'] - $NUMBER_OF_SESSION ?></td>
                                                            <td style="text-align: center;"><?= (isset($resultsArray[0]) && $resultsArray[0]) ? $resultsArray[0] : ''  ?></td>
                                                            <td style="text-align: center;"><?= isset($last_data->fields['DATE']) ? date('m-d-Y', strtotime($last_data->fields['DATE'])) : '' ?></td>
                                                            <td style="text-align: center;"><?= isset($last_data->fields['SERVICE_PROVIDER']) ? $last_data->fields['SERVICE_PROVIDER'] : '' ?></td>
                                                            <td style="text-align: center;">
                                                                <?php if ($row->fields['STATUS'] == 'A') { ?>
                                                                    <a href="javascript:;" onclick="cancelEnrollment(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>)" title="Cancel Enrollment">
                                                                        <i class="fa-solid fa-ban"></i>
                                                                    </a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <a href="?inactive=<?= $row->fields['PK_USER'] ?>&enrollment=<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>"
                                                                        onclick="return confirm('Are you sure you want to mark this customer as inactive?')"
                                                                        title="Mark Customer Inactive">
                                                                        <i class="fa-solid fa-person-arrow-down-to-line"></i>
                                                                    </a>
                                                                <?php } elseif ($row->fields['STATUS'] == 'C') { ?>
                                                                    <a style="color: red;" title="Cancelled before">
                                                                        <i class="fa-solid fa-x"></i>
                                                                    </a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <a href="?inactive=<?= $row->fields['PK_USER'] ?>&enrollment=<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>"
                                                                        onclick="return confirm('This enrollment is cancelled. Are you sure you want to mark this customer as inactive?')"
                                                                        title="Mark Customer Inactive">
                                                                        <i class="fa-solid fa-person-arrow-down-to-line"></i>
                                                                    </a>
                                                                <?php } ?>
                                                            </td>
                                                        </tr>
                                            <?php
                                                    }
                                                }
                                                $row->MoveNext();
                                                $i++;
                                            } ?>
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

    <div class="modal fade" id="enrollment_cancel_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="p-20" action="" method="post">

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
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>Cancel All Future Appointments? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" id="CANCEL_FUTURE_APPOINTMENT_1" value="1" checked /></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>Cancel Only Unpaid Future Appointments? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" id="CANCEL_FUTURE_APPOINTMENT_2" value="2" /></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>Move Future Appointments As Ad-Hoc? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" id="CANCEL_FUTURE_APPOINTMENT_3" value="3" /></label>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="javascript:" class="btn btn-info waves-effect waves-light text-white next" style="float: right;" onclick="$('#step_1').hide();$('#step_2').show();">Continue</a>
                                </div>

                                <div id="step_2" style="display: none;">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Use available credits to pay pending balances?</label>
                                            </div>
                                            <div class="col-md-2">
                                                <label><input type="radio" name="USE_AVAILABLE_CREDIT" value="1" checked />&nbsp;Yes</label>&nbsp;&nbsp;
                                                <!--<label><input type="radio" name="USE_AVAILABLE_CREDIT" value="0"/>&nbsp;No</label>-->
                                            </div>
                                        </div>
                                    </div>
                                    <a href="javascript:" class="btn btn-info waves-effect waves-light m-l-10 text-white next" style="float: right;" onclick="$('#step_2').hide();$('#step_3').show();showEnrollmentServiceDetails();">Continue</a>
                                    <a href="javascript:" class="btn btn-info waves-effect waves-light text-white prev" style="*float: right;" onclick="$('#step_2').hide();$('#step_1').show();">Go Back</a>
                                </div>

                                <div id="step_3" style="display: none;">
                                    <div id="enrollment_service_details">

                                    </div>
                                    <div class="form-group negative_balance_div" style="display: none;">
                                        <label class="form-label">How you want to your pay?</label>
                                        <div class="col-md-8">
                                            <select class="form-control" name="PK_PAYMENT_TYPE" id="PK_PAYMENT_TYPE">
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
                                    <div class="form-group negative_balance_div" style="display: none;">
                                        <div class="row">
                                            <b>Note: Please pay $<span id="total_negative_balance"></span> to cancel your enrollment.</b>
                                        </div>
                                    </div>

                                    <div class="form-group credit_balance_div" style="display: none;">
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
                                    <div class="form-group credit_balance_div" style="display: none;">
                                        <div class="row">
                                            <b>Note: Credit balance $<span id="total_credit_balance"></span> will be moved to Wallet.</b>
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

                                    <input type="hidden" name="SUBMIT" id="SUBMIT">
                                    <button type="submit" class="btn btn-info waves-effect waves-light text-white" onclick="$('#SUBMIT').val('Cancel and Store Info only')" style="float: right;">Cancel and Store Info only</button>
                                    <button type="submit" class="btn btn-info waves-effect waves-light text-white" onclick="$('#SUBMIT').val('Submit')" style="float: right; margin-right: 5px;">Submit</button>

                                    <a href="javascript:" class="btn btn-info waves-effect waves-light text-white" style="*float: right;" onclick="$('#step_3').hide();$('#step_2').show();">Go Back</a>
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

    <div class="modal fade" id="inactiveCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Customer Inactive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark this customer as inactive?</p>
                    <p><strong>This will:</strong></p>
                    <ul>
                        <li>Mark the customer as inactive</li>
                        <li>Cancel their active enrollment</li>
                        <li>They will no longer appear in active customer lists</li>
                    </ul>
                    <input type="hidden" id="inactive_user_master">
                    <input type="hidden" id="inactive_enrollment_master">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmInactive()">Mark Inactive</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>
    <script src="https://kit.fontawesome.com/959e6b14a9.js" crossorigin="anonymous"></script>
    <script>
        function cancelEnrollment(PK_ENROLLMENT_MASTER, PK_USER_MASTER) {
            $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
            $('.PK_USER_MASTER').val(PK_USER_MASTER);
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
                $(param).closest('.modal-body').find('#check_payment').slideDown();
            } else {
                $(param).closest('.modal-body').find('#check_payment').slideUp();
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
                    }
                    if (TOTAL_NEGATIVE_BALANCE < 0) {
                        $('.negative_balance_div').slideDown();
                        $('#total_negative_balance').text(Math.abs(parseFloat(TOTAL_NEGATIVE_BALANCE).toFixed(2)));
                    }
                }
            });
        }
    </script>
</body>

</html>