<?php

use Stripe\Stripe;

require_once('../global/config.php');
require_once("../global/stripe-php-master/init.php");

global $db;
global $db_account;
global $master_database;
global $results_per_page;

$title = "All Enrollments";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

$PK_ENROLLMENT_MASTER_ARRAY = [];
$not_billed_enrollment = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_MASTER WHERE NOT EXISTS(SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_BILLING WHERE DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER )");
if ($not_billed_enrollment->RecordCount() > 0) {
    while (!$not_billed_enrollment->EOF) {
        $PK_ENROLLMENT_MASTER_ARRAY[] = $not_billed_enrollment->fields['PK_ENROLLMENT_MASTER'];
        $not_billed_enrollment->MoveNext();
    }

    $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` IN (" . implode(',', $PK_ENROLLMENT_MASTER_ARRAY) . ")");
    $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` IN (" . implode(',', $PK_ENROLLMENT_MASTER_ARRAY) . ")");
}



$START_DATE = ' ';
$END_DATE = ' ';
$ORDER_BY = ' DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC ';
if (!empty($_GET['FROM_DATE'])) {
    $START_DATE = " AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE >= '" . date('Y-m-d', strtotime($_GET['FROM_DATE'])) . "'";
    $ORDER_BY = ' DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE ASC ';
}
if (!empty($_GET['END_DATE'])) {
    $END_DATE = " AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE <= '" . date('Y-m-d', strtotime($_GET['END_DATE'])) . "'";
    $ORDER_BY = ' DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE DESC ';
}

$search_text = '';
$search = $START_DATE . $END_DATE . ' ';
if (!empty($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = $START_DATE . $END_DATE . " AND (DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LIKE '%" . $search_text . "%' OR DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME LIKE '%" . $search_text . "%' OR DOA_ENROLLMENT_MASTER.ENROLLMENT_ID LIKE '%" . $search_text . "%' OR DOA_USERS.FIRST_NAME LIKE '%" . $search_text . "%' OR DOA_USERS.LAST_NAME LIKE '%" . $search_text . "%'OR DOA_USERS.EMAIL_ID LIKE '%" . $search_text . "%' OR DOA_USERS.PHONE LIKE '%" . $search_text . "%') ";
}

// if (isset($_GET['search_text']) || isset($_GET['FROM_DATE']) || isset($_GET['END_DATE'])) {
//     $FROM_DATE = date('Y-m-d', strtotime($_GET['FROM_DATE']));
//     $END_DATE = date('Y-m-d', strtotime($_GET['END_DATE']));
//     $search_text = $_GET['search_text'];
//     $search = " AND (DOA_USERS.FIRST_NAME LIKE '%" . $search_text . "%' OR DOA_USERS.LAST_NAME LIKE '%" . $search_text . "%'OR DOA_USERS.EMAIL_ID LIKE '%" . $search_text . "%' OR DOA_USERS.PHONE LIKE '%" . $search_text . "%')" . " AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '$FROM_DATE' AND '$END_DATE'";
// } else {
//     $FROM_DATE = ' ';
//     $END_DATE = ' ';
//     $search_text = ' ';
//     $search = ' ';
// }

$query = $db_account->Execute("SELECT COUNT(DISTINCT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER)) AS TOTAL_RECORDS FROM DOA_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION LEFT JOIN DOA_ENROLLMENT_BALANCE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 " . $search);

$number_of_result = ($query->RecordCount() > 0) ? $query->fields['TOTAL_RECORDS'] : 1;
$number_of_page = ceil($number_of_result / $results_per_page);

if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

$page_first_result = ($page - 1) * $results_per_page;

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
        $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_ENROLLMENT` WHERE `PK_ENROLLMENT_MASTER` = '$PK_ENROLLMENT_MASTER' AND IS_CHARGED = 1");
        $CONDITION = " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER' AND IS_CHARGED = 0";
    } elseif ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 2) {
        $APPOINTMENT_UPDATE_DATA['PK_APPOINTMENT_STATUS'] = 6;
        $CONDITION = " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER' AND IS_CHARGED = 0 AND IS_PAID = 0";
    } else {
        $CONDITION = " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'";
    }
    $APPOINTMENT_UPDATE_DATA['STATUS'] = 'C';
    db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_UPDATE_DATA, 'update', $CONDITION);

    $BALANCE = $TOTAL_POSITIVE_BALANCE + $TOTAL_NEGATIVE_BALANCE;

    for ($i = 0; $i < count($_POST['PK_ENROLLMENT_SERVICE']); $i++) {
        $enr_service_data = $db_account->Execute("SELECT PRICE_PER_SESSION, FINAL_AMOUNT FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_SERVICE = " . $_POST['PK_ENROLLMENT_SERVICE'][$i]);
        if ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 1) {
            $ENR_SERVICE_UPDATE['NUMBER_OF_SESSION'] = getSessionCompletedCount($_POST['PK_ENROLLMENT_SERVICE'][$i]);
        } elseif ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 2) {
            $ENR_SERVICE_UPDATE['NUMBER_OF_SESSION'] = getPaidSessionCount($_POST['PK_ENROLLMENT_SERVICE'][$i]);
        } else {
            $ENR_SERVICE_UPDATE['NUMBER_OF_SESSION'] = getPaidSessionCount($_POST['PK_ENROLLMENT_SERVICE'][$i]);
        }

        if ($TOTAL_POSITIVE_BALANCE >= 0) {
            $ENR_SERVICE_UPDATE['TOTAL_AMOUNT_PAID'] = $ENR_SERVICE_UPDATE['NUMBER_OF_SESSION'] * $enr_service_data->fields['PRICE_PER_SESSION'];
        }
        $ENR_SERVICE_UPDATE['FINAL_AMOUNT'] = $ENR_SERVICE_UPDATE['TOTAL_AMOUNT_PAID'];
        db_perform_account('DOA_ENROLLMENT_SERVICE', $ENR_SERVICE_UPDATE, 'update', " PK_ENROLLMENT_SERVICE = " . $_POST['PK_ENROLLMENT_SERVICE'][$i]);

        $CANCEL_ENROLLMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
        $CANCEL_ENROLLMENT_DATA['PK_ENROLLMENT_SERVICE'] = $_POST['PK_ENROLLMENT_SERVICE'][$i];
        $CANCEL_ENROLLMENT_DATA['ACTUAL_AMOUNT'] = $enr_service_data->fields['FINAL_AMOUNT'];
        $CANCEL_ENROLLMENT_DATA['CANCEL_AMOUNT'] = $enr_service_data->fields['FINAL_AMOUNT'] - $ENR_SERVICE_UPDATE['FINAL_AMOUNT'];
        $CANCEL_ENROLLMENT_DATA['CANCEL_DATE'] = date('Y-m-d H:i:s');
        db_perform_account('DOA_ENROLLMENT_CANCEL', $CANCEL_ENROLLMENT_DATA, 'insert');
    }

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
    header('location:all_enrollments.php');
}

/*if(!empty($_GET['id']) && !empty($_GET['status'])) {
    if ($_GET['status'] == 'active') {
        $PK_ENROLLMENT_MASTER = $_GET['id'];
        $UPDATE_DATA['STATUS'] = 'A';
        db_perform_account('DOA_APPOINTMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        db_perform_account('DOA_ENROLLMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        db_perform_account('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        header('location:all_enrollments.php');
    }
}*/

?>

<!DOCTYPE html>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<style>
    table th {
        font-weight: bold;
    }

    .sortable.asc::after {
        content: " ▲";
    }

    .sortable.desc::after {
        content: " ▼";
    }
</style>
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
                    <div class="col-md-2 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-8 align-self-center">
                        <form class="form-material form-horizontal" action="" method="get">
                            <div class="input-group" style="width: 80%; margin: auto;">
                                <div style="margin-right: 5px">
                                    <input type="text" id="FROM_DATE" name="FROM_DATE" placeholder="From Date" class="form-control datepicker-past" value="<?= ($START_DATE == ' ' || $START_DATE == '0000-00-00') ? '' : date('m/d/Y', strtotime($_GET['FROM_DATE'])) ?>">
                                </div>
                                <div style="margin-right: 5px">
                                    <input type="text" id="END_DATE" name="END_DATE" placeholder="To Date" class="form-control datepicker-normal" value="<?= ($END_DATE == ' ' || $END_DATE == '0000-00-00') ? '' : date('m/d/Y', strtotime($_GET['END_DATE'])) ?>">
                                </div>
                                <div style="margin-right: 5px">
                                    <input class="form-control" type="text" name="search_text" placeholder="Search.." value="<?= $search_text ?>">
                                </div>
                                <div>
                                    <button class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" type="submit"><i class="fa fa-search"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php if ($_SESSION['PK_ROLES'] != 5) { ?>
                        <div class="col-md-2 align-self-center text-end">
                            <div class="d-flex justify-content-end align-items-center">
                                <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='enrollment.php'"><i class="fa fa-plus-circle"></i> Create New</button>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped border" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th data-type="number" class="sortable" style="cursor: pointer">No</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Customer</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Unique Id</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Enrollment Id</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Enrollment Name</th>
                                                <th data-type="number" class="sortable" style="cursor: pointer">Total Amount</th>
                                                <th data-date data-order class="sortable" style="cursor: pointer">Date</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Email ID</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Phone</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Service Provider</th>
                                                <th data-type="string" class="sortable" style="cursor: pointer">Location</th>
                                                <th>Actions</th>
                                                <th>Status</th>
                                                <?php if ($_SESSION['PK_ROLES'] != 5) { ?>
                                                    <th>Cancel</th>
                                                <?php } ?>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            $i = $page_first_result + 1;
                                            $row = $db_account->Execute("SELECT DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.MISC_TYPE, DOA_ENROLLMENT_MASTER.MISC_ID, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_ENROLLMENT_MASTER.STATUS, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_USERS.PK_USER, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_LOCATION.LOCATION_NAME, DOA_ENROLLMENT_BALANCE.TOTAL_BALANCE_PAID, DOA_ENROLLMENT_BALANCE.TOTAL_BALANCE_USED, DOA_USER_MASTER.PK_USER_MASTER, DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT FROM DOA_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION LEFT JOIN DOA_ENROLLMENT_BALANCE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 " . $search . " ORDER BY " . $ORDER_BY . " LIMIT " . $page_first_result . ',' . $results_per_page);
                                            while (!$row->EOF) {
                                                $name = $row->fields['ENROLLMENT_NAME'];
                                                if ($row->fields['MISC_TYPE']) {
                                                    $id = $row->fields['MISC_ID'];
                                                } else {
                                                    $id = $row->fields['ENROLLMENT_ID'];
                                                }
                                                if (empty($name)) {
                                                    $enrollment_name = ' ';
                                                } else {
                                                    $enrollment_name = "$name" . " - ";
                                                }
                                                $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE.SESSION_CREATED, DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                $serviceCode = [];
                                                while (!$serviceCodeData->EOF) {
                                                    $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'] . ': ' . $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                                    $serviceCodeData->MoveNext();
                                                }

                                                $results = $db_account->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                $resultsArray = [];
                                                while (!$results->EOF) {
                                                    $resultsArray[] = $results->fields['SERVICE_PROVIDER'];
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
                                            ?>
                                                <tr>
                                                    <td onclick="editpage(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);"><?= $i; ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);"><?= $row->fields['FIRST_NAME'] . " " . $row->fields['LAST_NAME'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);"><?= $row->fields['PK_ENROLLMENT_MASTER'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);"><?= $id ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);"><?= $enrollment_name . implode(', ', $serviceCode) ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);"><?= $row->fields['TOTAL_AMOUNT'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);"><?= date('m/d/Y', strtotime($row->fields['ENROLLMENT_DATE'])) ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);"><?= $row->fields['EMAIL_ID'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);"><?= $row->fields['PHONE'] ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);"><?= $concatenatedResults ?></td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);"><?= $row->fields['LOCATION_NAME'] ?></td>
                                                    <td>
                                                        <?php if (in_array('Enrollments Edit', $PERMISSION_ARRAY)) { ?>
                                                            <a href="enrollment.php?id=<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>" title="Edit" style="font-size:18px"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <?php } ?>

                                                        <?php if ($row->fields['ACTIVE'] == 1) { ?>
                                                            <span class="active-box-green"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <?php } else { ?>
                                                            <span class="active-box-red"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <?php } ?>
                                                        <?php if ($_SESSION['PK_ROLES'] != 5) { ?>
                                                            <a href="enrollment.php?id_customer=<?= $row->fields['PK_USER'] ?>&master_id_customer=<?= $row->fields['PK_USER_MASTER'] ?>" title="Add Enrollment" style="font-size:18px"><i class="fa fa-plus-circle"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <?php } ?>
                                                        <?php
                                                        $payment_data = $db_account->Execute("SELECT PK_ENROLLMENT_PAYMENT FROM `DOA_ENROLLMENT_PAYMENT` WHERE PK_PAYMENT_TYPE != 12 AND PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                        if ($payment_data->RecordCount() == 0) {
                                                        ?>
                                                            <?php if (in_array('Enrollments Delete', $PERMISSION_ARRAY)) { ?>
                                                                <a href="javascript:;" onclick="ConfirmDelete(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);" title="Delete" style="font-size:18px"><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </td>
                                                    <td onclick="editpage(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>);">
                                                        <?php if ($row->fields['STATUS'] == 'A') { ?>
                                                            <i class="fa fa-check-circle" style="font-size:21px;color:#35e235;"></i>
                                                        <?php } elseif ($row->fields['STATUS'] == 'CO') { ?>
                                                            <span class="fa fa-check-circle" style="font-size:21px;color:#0048ff;"></span>
                                                        <?php } elseif ($row->fields['STATUS'] == 'C') { ?>
                                                            <span class="fa fa-check-circle" style="font-size:21px;color:#ff0000;"></span>
                                                        <?php } ?>
                                                    </td>
                                                    <?php if ($_SESSION['PK_ROLES'] != 5) { ?>
                                                        <td>
                                                            <?php if ($row->fields['STATUS'] == 'A') { ?>
                                                                <a href="javascript:;" onclick="cancelEnrollment(<?= $row->fields['PK_ENROLLMENT_MASTER'] ?>, <?= $row->fields['PK_USER_MASTER'] ?>)"><img src="../assets/images/noun-cancel-button.png" alt="LOGO" style="height: 21px; width: 21px;"></a>
                                                            <?php } elseif ($row->fields['STATUS'] == 'C') { ?>
                                                                <p style="color: red;">Cancelled</p>
                                                                <!--<a href="all_enrollments.php?id=<?php /*=$row->fields['PK_ENROLLMENT_MASTER']*/ ?>&status=active">Active Enrollment</a>-->
                                                            <?php } ?>
                                                        </td>
                                                    <?php } ?>
                                                </tr>
                                            <?php $row->MoveNext();
                                                $i++;
                                            } ?>
                                        </tbody>
                                    </table>
                                    <div class="center">
                                        <div class="pagination outer">
                                            <ul>
                                                <?php if ($page > 1) { ?>
                                                    <li><a href="all_enrollments.php?page=1<?= ((empty($_GET['FROM_DATE'])) ? '' : '&FROM_DATE=' . $_GET['FROM_DATE']) . ((empty($_GET['END_DATE'])) ? '' : '&END_DATE=' . $_GET['END_DATE']) . (($search_text == '') ? '' : '&search_text=' . $search_text) ?>">&laquo;</a></li>
                                                    <li><a href="all_enrollments.php?page=<?= ($page - 1) . ((empty($_GET['FROM_DATE'])) ? '' : '&FROM_DATE=' . $_GET['FROM_DATE']) . ((empty($_GET['END_DATE'])) ? '' : '&END_DATE=' . $_GET['END_DATE']) . (($search_text == '') ? '' : '&search_text=' . $search_text) ?>">&lsaquo;</a></li>
                                                <?php }
                                                for ($page_count = 1; $page_count <= $number_of_page; $page_count++) {
                                                    if ($page_count == $page || $page_count == ($page + 1) || $page_count == ($page - 1) || $page_count == $number_of_page) {
                                                        echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_enrollments.php?page=' . $page_count . ((empty($_GET['FROM_DATE'])) ? '' : '&FROM_DATE=' . $_GET['FROM_DATE']) . ((empty($_GET['END_DATE'])) ? '' : '&END_DATE=' . $_GET['END_DATE']) . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                    } elseif ($page_count == ($number_of_page - 1)) {
                                                        echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                    } else {
                                                        echo '<li><a class="hidden" href="all_enrollments.php?page=' . $page_count . ((empty($_GET['FROM_DATE'])) ? '' : '&FROM_DATE=' . $_GET['FROM_DATE']) . ((empty($_GET['END_DATE'])) ? '' : '&END_DATE=' . $_GET['END_DATE']) . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                    }
                                                }
                                                if ($page < $number_of_page) { ?>
                                                    <li><a href="all_enrollments.php?page=<?= ($page + 1) . ((empty($_GET['FROM_DATE'])) ? '' : '&FROM_DATE=' . $_GET['FROM_DATE']) . ((empty($_GET['END_DATE'])) ? '' : '&END_DATE=' . $_GET['END_DATE']) . (($search_text == '') ? '' : '&search_text=' . $search_text) ?>">&rsaquo;</a></li>
                                                    <li><a href="all_enrollments.php?page=<?= $number_of_page . ((empty($_GET['FROM_DATE'])) ? '' : '&FROM_DATE=' . $_GET['FROM_DATE']) . ((empty($_GET['END_DATE'])) ? '' : '&END_DATE=' . $_GET['END_DATE']) . (($search_text == '') ? '' : '&search_text=' . $search_text) ?>">&raquo;</a></li>
                                                <?php } ?>
                                            </ul>
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
                                            <div class="col-md-8">
                                                <label>Cancel All Future Appointments? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" id="CANCEL_FUTURE_APPOINTMENT_1" value="1" checked /></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <label>Cancel Only Unpaid Future Appointments? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" id="CANCEL_FUTURE_APPOINTMENT_2" value="2" /></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <label>Keep All Future Appointments on Schedule <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" id="CANCEL_FUTURE_APPOINTMENT_3" value="3" /></label>
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

    <?php require_once('../includes/footer.php'); ?>

    <script>
        function ConfirmDelete(PK_ENROLLMENT_MASTER) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
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
                            FUNCTION_NAME: 'deleteEnrollmentData',
                            PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER
                        },
                        success: function(data) {
                            let currentURL = window.location.href;
                            let extractedPart = currentURL.substring(currentURL.lastIndexOf("/") + 1);
                            console.log(extractedPart);
                            window.location.href = extractedPart;
                        }
                    });
                }
            });
        }

        $(document).ready(function() {
            $("#FROM_DATE").datepicker({
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#END_DATE").datepicker("option", "minDate", selected);
                    $("#FROM_DATE, #END_DATE").trigger("change");
                }
            });
            $("#END_DATE").datepicker({
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#FROM_DATE").datepicker("option", "maxDate", selected)
                }
            });
        });

        $(document).ready(function() {
            $(".sortable").on("click", function() {
                var table = $(this).closest("table");
                var tbody = table.find("tbody");
                var rows = tbody.find("tr").toArray();
                var index = $(this).index();
                var asc = !$(this).hasClass("asc");
                var isDate = $(this).is("[data-date]");
                var type = $(this).data("type");

                // Remove old sorting indicators
                table.find(".sortable").removeClass("asc desc");
                $(this).addClass(asc ? "asc" : "desc");

                rows.sort(function(a, b) {
                    var A = $(a).children("td").eq(index).text().trim();
                    var B = $(b).children("td").eq(index).text().trim();

                    // Handle data type
                    if (isDate) {
                        A = new Date(A);
                        B = new Date(B);
                    } else if (type === "number") {
                        A = parseFloat(A.replace(/[^0-9.\-]/g, "")) || 0;
                        B = parseFloat(B.replace(/[^0-9.\-]/g, "")) || 0;
                    } else {
                        A = A.toLowerCase();
                        B = B.toLowerCase();
                    }

                    if (A < B) return asc ? -1 : 1;
                    if (A > B) return asc ? 1 : -1;
                    return 0;
                });

                // Append sorted rows
                $.each(rows, function(i, row) {
                    tbody.append(row);
                });
            });
        });

        function editpage(id) {
            //alert(i);
            window.location.href = "enrollment.php?id=" + id;
        }

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

        /*function checkCancelStatus(){
            let CANCEL_FUTURE_APPOINTMENT = $('input[name="CANCEL_FUTURE_APPOINTMENT"]:checked').val();
            let total_credit_balance = $('.CREDIT_BALANCE').val();
            let total_credit_balance_session_created = $('.CREDIT_BALANCE_SESSION_CREATED').val();

            if (CANCEL_FUTURE_APPOINTMENT == 1) {
                if (total_credit_balance == 0) {
                    $('.credit_balance_div').slideUp();
                    $('.negative_balance_div').slideUp();
                } else {
                    if (total_credit_balance > 0) {
                        $('.credit_balance_div').slideDown();
                        $('.negative_balance_div').slideUp();
                        $('.ACTUAL_CREDIT_BALANCE').val(total_credit_balance);
                        $('#total_credit_balance').text(parseFloat(total_credit_balance).toFixed(2));
                    } else {
                        $('.credit_balance_div').slideUp();
                        $('.negative_balance_div').slideDown();
                        $('#total_negative_balance').text(Math.abs(parseFloat(total_credit_balance).toFixed(2)));
                    }
                }
            } else {
                if (total_credit_balance_session_created == 0) {
                    $('.credit_balance_div').slideUp();
                    $('.negative_balance_div').slideUp();
                } else {
                    if (total_credit_balance_session_created > 0) {
                        $('.credit_balance_div').slideDown();
                        $('.negative_balance_div').slideUp();
                        $('.ACTUAL_CREDIT_BALANCE').val(total_credit_balance_session_created);
                        $('#total_credit_balance').text(parseFloat(total_credit_balance_session_created).toFixed(2));
                    } else {
                        $('.credit_balance_div').slideUp();
                        $('.negative_balance_div').slideDown();
                        $('#total_negative_balance').text(Math.abs(parseFloat(total_credit_balance_session_created).toFixed(2)));
                    }
                }
            }
        }*/
    </script>
</body>

</html>