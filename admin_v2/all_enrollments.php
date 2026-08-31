<?php

use Stripe\Stripe;

require_once('../global/config.php');
require_once("../global/stripe-php-master/init.php");

global $db;
global $db_account;
global $master_database;
global $results_per_page;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
$LOCATION_ARRAY = explode(',', $DEFAULT_LOCATION_ID);

$title = "All Enrollments";

// ==============================================
// Get filter parameters - DEFINE ALL VARIABLES
// ==============================================
$search_text = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
$status_filter = isset($_GET['STATUS']) ? trim($_GET['STATUS']) : 'A';
$choose_date = isset($_GET['CHOOSE_DATE']) && $_GET['CHOOSE_DATE'] != '' ? date('Y-m-d', strtotime($_GET['CHOOSE_DATE'])) : '';
$date_from = isset($_GET['DATE_FROM']) && $_GET['DATE_FROM'] != '' ? date('Y-m-d', strtotime($_GET['DATE_FROM'])) : '';
$date_to = isset($_GET['DATE_TO']) && $_GET['DATE_TO'] != '' ? date('Y-m-d', strtotime($_GET['DATE_TO'])) : '';

// Capture ALL filter parameters from URL
$filter_params = [
    'search_text' => isset($_GET['search_text']) ? trim($_GET['search_text']) : '',
    'STATUS' => isset($_GET['STATUS']) ? trim($_GET['STATUS']) : 'A',
    'CHOOSE_DATE' => isset($_GET['CHOOSE_DATE']) ? trim($_GET['CHOOSE_DATE']) : '',
    'DATE_FROM' => isset($_GET['DATE_FROM']) ? trim($_GET['DATE_FROM']) : '',
    'DATE_TO' => isset($_GET['DATE_TO']) ? trim($_GET['DATE_TO']) : '',
    'sort_by' => isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'newest'
];

foreach ($filter_params as $key => $value) {
    $$key = $value;
}

// Build search condition - FIXED PHONE SEARCH
$search_condition = '';
if ($search_text != '') {
    $search_escaped = addslashes($search_text);
    $search_numeric = preg_replace('/\D/', '', $search_text);

    if (strlen($search_numeric) >= 3) {
        $search_condition = " AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME LIKE '%$search_escaped%' 
                         OR DOA_ENROLLMENT_MASTER.ENROLLMENT_ID LIKE '%$search_escaped%' 
                         OR DOA_USERS.FIRST_NAME LIKE '%$search_escaped%' 
                         OR DOA_USERS.LAST_NAME LIKE '%$search_escaped%' 
                         OR REPLACE(REPLACE(REPLACE(REPLACE(DOA_USERS.PHONE, '(', ''), ')', ''), '-', ''), ' ', '') LIKE '%$search_numeric%'
                         OR DOA_USERS.EMAIL_ID LIKE '%$search_escaped%')";
    } else {
        $search_condition = " AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME LIKE '%$search_escaped%' 
                         OR DOA_ENROLLMENT_MASTER.ENROLLMENT_ID LIKE '%$search_escaped%' 
                         OR DOA_USERS.FIRST_NAME LIKE '%$search_escaped%' 
                         OR DOA_USERS.LAST_NAME LIKE '%$search_escaped%' 
                         OR DOA_USERS.PHONE LIKE '%$search_escaped%' 
                         OR DOA_USERS.EMAIL_ID LIKE '%$search_escaped%')";
    }
}

// Build status condition
$status_condition = '';
if ($status_filter == 'A') {
    $status_condition = " AND DOA_ENROLLMENT_MASTER.STATUS IN ('A', 'CA') ";
} elseif ($status_filter == 'I') {
    $status_condition = " AND DOA_ENROLLMENT_MASTER.STATUS IN ('C', 'CO') ";
} else {
    $status_condition = " AND DOA_ENROLLMENT_MASTER.STATUS IN ('A', 'CA') ";
}

// Build date condition
$date_condition = '';
if ($choose_date != '') {
    $date_condition = " AND DATE(DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE) = '$choose_date'";
} elseif ($date_from != '' && $date_to != '') {
    $date_condition = " AND DATE(DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE) BETWEEN '$date_from' AND '$date_to'";
} elseif ($date_from != '') {
    $date_condition = " AND DATE(DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE) >= '$date_from'";
} elseif ($date_to != '') {
    $date_condition = " AND DATE(DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE) <= '$date_to'";
}

// Sorting
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'newest';

$sort_options = [
    'name_asc' => ['field' => 'CONCAT(DOA_USERS.FIRST_NAME, " ", DOA_USERS.LAST_NAME)', 'order' => 'ASC', 'label' => 'Name (A-Z)'],
    'name_desc' => ['field' => 'CONCAT(DOA_USERS.FIRST_NAME, " ", DOA_USERS.LAST_NAME)', 'order' => 'DESC', 'label' => 'Name (Z-A)'],
    'newest' => ['field' => 'DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE', 'order' => 'DESC', 'label' => 'Newest First'],
    'oldest' => ['field' => 'DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE', 'order' => 'ASC', 'label' => 'Oldest First'],
    'enrollment_id_asc' => ['field' => 'DOA_ENROLLMENT_MASTER.ENROLLMENT_ID', 'order' => 'ASC', 'label' => 'Enrollment ID (A-Z)'],
    'enrollment_id_desc' => ['field' => 'DOA_ENROLLMENT_MASTER.ENROLLMENT_ID', 'order' => 'DESC', 'label' => 'Enrollment ID (Z-A)'],
    'amount_asc' => ['field' => 'DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT', 'order' => 'ASC', 'label' => 'Amount (Low-High)'],
    'amount_desc' => ['field' => 'DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT', 'order' => 'DESC', 'label' => 'Amount (High-Low)']
];

$sort_field = 'DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER';
$sort_direction = 'DESC';

if (isset($sort_options[$sort_by])) {
    $sort_field = $sort_options[$sort_by]['field'];
    $sort_direction = $sort_options[$sort_by]['order'];
} else {
    $sort_field = 'DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE';
    $sort_direction = 'DESC';
}

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

// Build the WHERE clause
$where_clause = " WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
                  AND DOA_USERS.ACTIVE = 1 
                  AND DOA_USERS.IS_DELETED = 0 
                  " . $status_condition . " 
                  " . $search_condition . " 
                  " . $date_condition;

// Get total count
$count_query = $db_account->Execute("SELECT COUNT(DISTINCT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER)) AS TOTAL_RECORDS 
                                     FROM DOA_ENROLLMENT_MASTER 
                                     INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER 
                                     INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER 
                                     LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION 
                                     LEFT JOIN DOA_ENROLLMENT_BALANCE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER 
                                     LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER 
                                     " . $where_clause);

$number_of_result = ($count_query->RecordCount() > 0) ? $count_query->fields['TOTAL_RECORDS'] : 1;
$number_of_page = ceil($number_of_result / $results_per_page);

if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

$page_first_result = ($page - 1) * $results_per_page;

// Main query
$enrollment_query = "SELECT DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, 
                     DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, 
                     DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE, 
                     DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, 
                     DOA_ENROLLMENT_MASTER.MISC_TYPE, 
                     DOA_ENROLLMENT_MASTER.MISC_ID, 
                     DOA_ENROLLMENT_MASTER.ACTIVE, 
                     DOA_ENROLLMENT_MASTER.STATUS, 
                     DOA_ENROLLMENT_MASTER.PK_USER_MASTER, 
                     DOA_USERS.PK_USER, 
                     DOA_USERS.FIRST_NAME, 
                     DOA_USERS.LAST_NAME, 
                     DOA_USERS.EMAIL_ID, 
                     DOA_USERS.PHONE, 
                     DOA_LOCATION.LOCATION_NAME, 
                     DOA_ENROLLMENT_BALANCE.TOTAL_BALANCE_PAID, 
                     DOA_ENROLLMENT_BALANCE.TOTAL_BALANCE_USED, 
                     DOA_USER_MASTER.PK_USER_MASTER, 
                     DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT 
                     FROM DOA_ENROLLMENT_MASTER 
                     INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER 
                     INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER 
                     LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION 
                     LEFT JOIN DOA_ENROLLMENT_BALANCE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER 
                     LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER 
                     " . $where_clause . " 
                     ORDER BY $sort_field $sort_direction 
                     LIMIT " . $page_first_result . ',' . $results_per_page;

$enrollment_data = $db_account->Execute($enrollment_query);

// POST handling (keep original)
if (isset($_POST['SUBMIT'])) {
    $PK_ENROLLMENT_MASTER = $_POST['PK_ENROLLMENT_MASTER'];
    $PK_PAYMENT_TYPE_REFUND = ($_POST['PK_PAYMENT_TYPE_REFUND']) ?? 0;
    $enrollment_data_post = $db_account->Execute("SELECT ENROLLMENT_NAME, ENROLLMENT_ID, PK_ENROLLMENT_BILLING FROM DOA_ENROLLMENT_MASTER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
    if (empty($enrollment_data_post->fields['ENROLLMENT_NAME'])) {
        $enrollment_name = '';
    } else {
        $enrollment_name = $enrollment_data_post->fields['ENROLLMENT_NAME'] . " - ";
    }
    if (empty($enrollment_data_post->fields['ENROLLMENT_ID'])) {
        $enrollment_id = $enrollment_data_post->fields['MISC_ID'];
    } else {
        $enrollment_id = $enrollment_data_post->fields['ENROLLMENT_ID'];
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

    db_perform_account('DOA_ENROLLMENT_MASTER', $UPDATE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
    db_perform_account('DOA_ENROLLMENT_SERVICE', $UPDATE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
    db_perform_account('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");

    if ($TOTAL_NEGATIVE_BALANCE < 0) {
        $LEDGER_DATA_BILLING['TRANSACTION_TYPE'] = ($_POST['SUBMIT'] == 'Cancel and Store Info only') ? 'Balance Owed' : 'Billing';
        $LEDGER_DATA_BILLING['ENROLLMENT_LEDGER_PARENT'] = -1;
        $LEDGER_DATA_BILLING['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
        $LEDGER_DATA_BILLING['PK_ENROLLMENT_BILLING'] = $enrollment_data_post->fields['PK_ENROLLMENT_BILLING'];
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
        $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $enrollment_data_post->fields['PK_ENROLLMENT_BILLING'];
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
                $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $enrollment_data_post->fields['PK_ENROLLMENT_BILLING'];
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

    markEnrollmentComplete($PK_ENROLLMENT_MASTER);
    header('location:all_enrollments.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php include 'layout/header.php'; ?>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">

<style>
    .sortable-header {
        cursor: pointer;
        transition: color 0.2s;
        background: transparent;
        border: none;
        padding: 0;
        color: #6c757d;
    }

    .sortable-header:hover {
        color: #333 !important;
    }

    .sortable-header.asc svg {
        transform: rotate(180deg);
    }

    .sortable-header.desc svg {
        transform: rotate(0deg);
    }

    .sortable-header svg {
        transition: transform 0.2s ease;
        display: inline-block;
        margin-left: 4px;
    }

    .sortable-header.asc .fw-semibold,
    .sortable-header.desc .fw-semibold {
        color: #333;
        font-weight: 700 !important;
    }

    .date-range-group {
        display: flex;
        gap: 4px;
        align-items: center;
        background: white;
        border-radius: 50px;
        padding: 4px 12px;
        border: 1px solid #dee2e6;
        height: 37px;
    }

    .date-range-group input {
        border: none;
        padding: 4px 0;
        width: 85px;
        font-size: 0.8rem;
        outline: none;
        background: transparent;
    }

    .date-range-group span {
        color: #6c757d;
        font-size: 0.75rem;
        margin: 0 2px;
    }

    .date-range-group .fa-calendar {
        color: #6c757d;
        font-size: 14px;
    }

    .sort-dropdown-btn {
        height: 37px;
        border: 1px solid #dee2e6;
        background: #fff;
        border-radius: 50px;
        padding: 0 16px;
        font-size: 14px;
        color: #444;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }

    .sort-dropdown-btn:hover {
        background: #f8f9fa;
    }

    .reset-btn {
        height: 37px;
        border: 1px solid #dee2e6;
        background: #fff;
        border-radius: 50px;
        padding: 0 16px;
        font-size: 14px;
        color: #444;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }

    .reset-btn:hover {
        background: #f8f9fa;
    }

    .filter-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid mt-4">
                <div class="card-box" style="margin-top: 20px;">

                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <div class="d-flex align-items-center" style="gap: 12px;">
                            <span class="avatar-large">
                                <i class="bi bi-journal-text" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h4 class="mb-0">Enrollments</h4>
                                <small class="text-muted">Optionally describe this</small>
                            </div>
                        </div>

                        <button class="btn-new" onclick="createEnrollment();">
                            + Create New Enrollment
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 10px;">
                        <!-- Search -->
                        <form method="get" class="d-flex align-items-center" style="gap: 8px; flex-wrap: wrap;">
                            <div class="input-group" style="width: 280px;">
                                <input type="text" name="search_text" id="search_text" class="form-control search-box" placeholder="Search..." value="<?= htmlspecialchars($search_text) ?>" style="border-radius: 50px 0px 0px 50px;">
                                <button type="submit" class="btn-new" style="padding: 8px 16px !important; font-size: 16px; border-radius: 0px 50px 50px 0px;">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                            <input type="hidden" name="STATUS" value="<?= htmlspecialchars($status_filter) ?>">
                            <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sort_by) ?>">
                        </form>

                        <div class="filter-wrapper">
                            <!-- Date Range Filter -->
                            <div class="date-range-group">
                                <i class="fa fa-calendar"></i>
                                <input type="text" id="DATE_FROM" class="datepicker-normal" placeholder="From" value="<?= htmlspecialchars($_GET['DATE_FROM'] ?? '') ?>" autocomplete="off">
                                <span>–</span>
                                <input type="text" id="DATE_TO" class="datepicker-normal" placeholder="To" value="<?= htmlspecialchars($_GET['DATE_TO'] ?? '') ?>" autocomplete="off">
                                <?php if (!empty($date_from) || !empty($date_to)): ?>
                                    <button type="button" id="clearDateRange" class="btn btn-link p-0 ms-0" style="color: #dc3545; font-size: 0.9rem; text-decoration: none; padding: 0 4px;">✕</button>
                                <?php endif; ?>
                            </div>

                            <!-- Sort Dropdown -->
                            <div class="dropdown d-inline-block">
                                <button class="sort-dropdown-btn dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa fa-sort-amount-desc"></i> Sort By
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                                    <li><a class="dropdown-item <?= ($sort_by == 'newest') ? 'active bg-success text-white' : '' ?>" href="#" data-sort="newest">📅 Newest First</a></li>
                                    <li><a class="dropdown-item <?= ($sort_by == 'oldest') ? 'active bg-success text-white' : '' ?>" href="#" data-sort="oldest">📅 Oldest First</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item <?= ($sort_by == 'name_asc') ? 'active bg-success text-white' : '' ?>" href="#" data-sort="name_asc">👤 Name (A-Z)</a></li>
                                    <li><a class="dropdown-item <?= ($sort_by == 'name_desc') ? 'active bg-success text-white' : '' ?>" href="#" data-sort="name_desc">👤 Name (Z-A)</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item <?= ($sort_by == 'enrollment_id_asc') ? 'active bg-success text-white' : '' ?>" href="#" data-sort="enrollment_id_asc">🔢 ID (A-Z)</a></li>
                                    <li><a class="dropdown-item <?= ($sort_by == 'enrollment_id_desc') ? 'active bg-success text-white' : '' ?>" href="#" data-sort="enrollment_id_desc">🔢 ID (Z-A)</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item <?= ($sort_by == 'amount_asc') ? 'active bg-success text-white' : '' ?>" href="#" data-sort="amount_asc">💰 Amount (Low-High)</a></li>
                                    <li><a class="dropdown-item <?= ($sort_by == 'amount_desc') ? 'active bg-success text-white' : '' ?>" href="#" data-sort="amount_desc">💰 Amount (High-Low)</a></li>
                                </ul>
                            </div>

                            <!-- Reset Button -->
                            <button class="reset-btn" onclick="resetFilters()">
                                <i class="fa fa-refresh"></i> Reset
                            </button>

                            <!-- Status Toggle -->
                            <div class="view-toggle m-r-15" style="height: 37px;">
                                <button class="view-btn-icon <?= ($status_filter == 'A') ? 'active' : '' ?>" onclick="window.location.href='all_enrollments.php?STATUS=A<?= (!empty($_GET['search_text']) ? '&search_text=' . $_GET['search_text'] : '') . (!empty($_GET['DATE_FROM']) ? '&DATE_FROM=' . $_GET['DATE_FROM'] : '') . (!empty($_GET['DATE_TO']) ? '&DATE_TO=' . $_GET['DATE_TO'] : '') . '&sort_by=' . $sort_by ?>'">
                                    Active
                                </button>
                                <button class="view-btn-icon <?= ($status_filter == 'I') ? 'active' : '' ?>" onclick="window.location.href='all_enrollments.php?STATUS=I<?= (!empty($_GET['search_text']) ? '&search_text=' . $_GET['search_text'] : '') . (!empty($_GET['DATE_FROM']) ? '&DATE_FROM=' . $_GET['DATE_FROM'] : '') . (!empty($_GET['DATE_TO']) ? '&DATE_TO=' . $_GET['DATE_TO'] : '') . '&sort_by=' . $sort_by ?>'">
                                    Archived
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="text-muted f12"><?= $number_of_result ?> <?= ($status_filter == 'I') ? 'archived' : 'active' ?> enrollments</p>

                    <!-- Table -->
                    <div class="table-responsive schedule-wrapper">
                        <table class="table align-middle schedule-table mb-0" id="enrollmentTable">
                            <thead class="table-light">
                                <tr>
                                    <th><input type="checkbox"></th>
                                    <th>
                                        <button type="button" class="sortable-header bg-transparent p-0 border-0 theme-text-light" data-index="1" data-type="name">
                                            <span class="fw-semibold">Customer Name / Email</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="sortable-header bg-transparent p-0 border-0 theme-text-light" data-index="2" data-type="number">
                                            <span class="fw-semibold">Unique ID</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="sortable-header bg-transparent p-0 border-0 theme-text-light" data-index="3" data-type="string">
                                            <span class="fw-semibold">Enrollment ID</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="sortable-header bg-transparent p-0 border-0 theme-text-light" data-index="4" data-type="string">
                                            <span class="fw-semibold">Enrollment Name</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="sortable-header bg-transparent p-0 border-0 theme-text-light" data-index="5" data-type="date" data-date="true">
                                            <span class="fw-semibold">Date</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="sortable-header bg-transparent p-0 border-0 theme-text-light" data-index="6" data-type="string">
                                            <span class="fw-semibold">Phone</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="sortable-header bg-transparent p-0 border-0 theme-text-light" data-index="7" data-type="string">
                                            <span class="fw-semibold">Service Provider</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="sortable-header bg-transparent p-0 border-0 theme-text-light" data-index="8" data-type="string">
                                            <span class="fw-semibold">Status</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="sortable-header bg-transparent p-0 border-0 theme-text-light" data-index="9" data-type="currency">
                                            <span class="fw-semibold">Total Amount</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                                <path d="M11 7h-6l3-4z" />
                                                <path d="M5 9h6l-3 4z" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $i = $page_first_result + 1;
                                while (!$enrollment_data->EOF) {
                                    $name = $enrollment_data->fields['ENROLLMENT_NAME'];
                                    if ($enrollment_data->fields['MISC_TYPE']) {
                                        $id = $enrollment_data->fields['MISC_ID'];
                                    } else {
                                        $id = $enrollment_data->fields['ENROLLMENT_ID'];
                                    }
                                    if (empty($name)) {
                                        $enrollment_name = ' ';
                                    } else {
                                        $enrollment_name = "$name" . " - ";
                                    }
                                    $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE.SESSION_CREATED, DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $enrollment_data->fields['PK_ENROLLMENT_MASTER']);
                                    $serviceCode = [];
                                    while (!$serviceCodeData->EOF) {
                                        $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'] . ': ' . $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                        $serviceCodeData->MoveNext();
                                    }

                                    $results = $db_account->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $enrollment_data->fields['PK_ENROLLMENT_MASTER']);
                                    $resultsArray = [];
                                    $service_provider = " ";
                                    while (!$results->EOF) {
                                        $service_provider = ($results->fields['SERVICE_PROVIDER'] == null) ? " " : $results->fields['SERVICE_PROVIDER'];
                                        $resultsArray[] = $results->fields['SERVICE_PROVIDER'];
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

                                    $CUSTOMER_NAME = $enrollment_data->fields['FIRST_NAME'] . " " . $enrollment_data->fields['LAST_NAME'];
                                    $customer = getProfileBadge($CUSTOMER_NAME);
                                    $customer_initial = $customer['initials'];
                                    $customer_color = $customer['color'];

                                    $profile = getProfileBadge($service_provider);
                                    $profile_initial = $profile['initials'];
                                    $profile_color = $profile['color'];
                                ?>
                                    <tr style="height: 60px;">
                                        <td><input type="checkbox"></td>
                                        <td class="d-flex align-items-center" style="height: 60px;">
                                            <span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span>
                                            <div>
                                                <div><a href="../admin_v2/customer.php?id=<?= $enrollment_data->fields['PK_USER'] ?>&master_id=<?= $enrollment_data->fields['PK_USER_MASTER'] ?>"><?= $CUSTOMER_NAME ?></a></div>
                                                <small class="text-muted"><?= $enrollment_data->fields['EMAIL_ID'] ?></small>
                                            </div>
                                        </td>
                                        <td><?= $enrollment_data->fields['PK_ENROLLMENT_MASTER'] ?></td>
                                        <td><a href="../admin_v2/enrollment.php?id=<?= $enrollment_data->fields['PK_ENROLLMENT_MASTER'] ?>"><?= $id ?></a></td>
                                        <td><?= $enrollment_name . implode(', ', $serviceCode) ?></td>
                                        <td><?= date('m/d/Y', strtotime($enrollment_data->fields['ENROLLMENT_DATE'])) ?></td>
                                        <td><?= $enrollment_data->fields['PHONE'] ?></td>
                                        <td style="vertical-align: middle;">
                                            <?php if ($service_provider != ' ') { ?>
                                                <span class="avatarname" style="color: #fff; background-color: <?= $profile_color ?>;"><?= $profile_initial; ?></span>
                                                <?= $service_provider ?>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($enrollment_data->fields['STATUS'] == 'A' || $enrollment_data->fields['STATUS'] == 'CA') { ?>
                                                <span class="status not-started" style="border: 1px solid #e1e1e1; background-color: #fff;">
                                                    <i class="fa fa-check-circle" style="font-size:15px; color:#35e235;"></i> Active
                                                </span>
                                            <?php } elseif ($enrollment_data->fields['STATUS'] == 'CO') { ?>
                                                <span class="status not-started" style="border: 1px solid #e1e1e1; background-color: #fff;">
                                                    <i class="fa fa-check-circle" style="font-size:15px; color:#0048ff;"></i> Completed
                                                </span>
                                            <?php } elseif ($enrollment_data->fields['STATUS'] == 'C') { ?>
                                                <span class="status not-started" style="border: 1px solid #e1e1e1; background-color: #fff;">
                                                    <i class="fa fa-ban" style="font-size:15px; color:#ff0000;"></i> Cancelled
                                                </span>
                                            <?php } ?>
                                        </td>
                                        <td style="vertical-align: middle; text-align: right; padding-right: 40px;">$<?= str_replace(",", "", number_format($enrollment_data->fields['TOTAL_AMOUNT'], 2)) ?></td>
                                        <td style="vertical-align: middle; text-align: right; padding-right: 40px;">
                                            <?php
                                            $payment_data = $db_account->Execute("SELECT PK_ENROLLMENT_PAYMENT FROM `DOA_ENROLLMENT_PAYMENT` WHERE PK_PAYMENT_TYPE != 12 AND PK_ENROLLMENT_MASTER = " . $enrollment_data->fields['PK_ENROLLMENT_MASTER']);
                                            if ($payment_data->RecordCount() == 0) {
                                            ?>
                                                <?php if (in_array('Enrollments Delete', $PERMISSION_ARRAY)) { ?>
                                                    <a href="javascript:;" onclick="ConfirmDelete(<?= $enrollment_data->fields['PK_ENROLLMENT_MASTER'] ?>);" title="Delete" style="font-size:18px; color: #ff0000;"><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php } ?>
                                            <?php } ?>

                                            <?php if ($_SESSION['PK_ROLES'] != 5) {
                                                if ($enrollment_data->fields['STATUS'] == 'A') { ?>
                                                    <a href="javascript:;" onclick="cancelEnrollment(<?= $enrollment_data->fields['PK_ENROLLMENT_MASTER'] ?>, <?= $enrollment_data->fields['PK_USER_MASTER'] ?>)" style="font-size:18px; color: gray;" title="Cancel"><i class="fa fa-ban"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php } elseif ($enrollment_data->fields['STATUS'] == 'C') { ?>
                                                    <i class="fa fa-ban" style="font-size:18px; color: #ff0000;" title="Cancelled"></i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            <?php }
                                            } ?>

                                            <a href="../admin_v2/enrollment.php?id=<?= $enrollment_data->fields['PK_ENROLLMENT_MASTER'] ?>" title="Edit" style="font-size:18px; color: #198754;">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php $enrollment_data->MoveNext();
                                    $i++;
                                } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer -->
                    <div class="d-flex justify-content-between align-items-center mt-3 f12">

                        <small class="text-muted">Page <?= $page ?> of <?= $number_of_page ?></small>

                        <div class="center">
                            <div class="pagination outer">
                                <ul>
                                    <?php if ($page > 1) { ?>
                                        <li><a href="all_enrollments.php?page=1<?= ((empty($_GET['DATE_FROM'])) ? '' : '&DATE_FROM=' . $_GET['DATE_FROM']) . ((empty($_GET['DATE_TO'])) ? '' : '&DATE_TO=' . $_GET['DATE_TO']) . ((empty($_GET['search_text'])) ? '' : '&search_text=' . $search_text) . '&STATUS=' . $status_filter . '&sort_by=' . $sort_by ?>">&laquo;</a></li>
                                        <li><a href="all_enrollments.php?page=<?= ($page - 1) . ((empty($_GET['DATE_FROM'])) ? '' : '&DATE_FROM=' . $_GET['DATE_FROM']) . ((empty($_GET['DATE_TO'])) ? '' : '&DATE_TO=' . $_GET['DATE_TO']) . ((empty($_GET['search_text'])) ? '' : '&search_text=' . $search_text) . '&STATUS=' . $status_filter . '&sort_by=' . $sort_by ?>">&lsaquo;</a></li>
                                    <?php }
                                    for ($page_count = 1; $page_count <= $number_of_page; $page_count++) {
                                        if ($page_count == $page || $page_count == ($page + 1) || $page_count == ($page - 1) || $page_count == $number_of_page) {
                                            echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_enrollments.php?page=' . $page_count . ((empty($_GET['DATE_FROM'])) ? '' : '&DATE_FROM=' . $_GET['DATE_FROM']) . ((empty($_GET['DATE_TO'])) ? '' : '&DATE_TO=' . $_GET['DATE_TO']) . ((empty($_GET['search_text'])) ? '' : '&search_text=' . $search_text) . '&STATUS=' . $status_filter . '&sort_by=' . $sort_by . '">' . $page_count . ' </a></li>';
                                        } elseif ($page_count == ($number_of_page - 1)) {
                                            echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                        } else {
                                            echo '<li><a class="hidden" href="all_enrollments.php?page=' . $page_count . ((empty($_GET['DATE_FROM'])) ? '' : '&DATE_FROM=' . $_GET['DATE_FROM']) . ((empty($_GET['DATE_TO'])) ? '' : '&DATE_TO=' . $_GET['DATE_TO']) . ((empty($_GET['search_text'])) ? '' : '&search_text=' . $search_text) . '&STATUS=' . $status_filter . '&sort_by=' . $sort_by . '">' . $page_count . ' </a></li>';
                                        }
                                    }
                                    if ($page < $number_of_page) { ?>
                                        <li><a href="all_enrollments.php?page=<?= ($page + 1) . ((empty($_GET['DATE_FROM'])) ? '' : '&DATE_FROM=' . $_GET['DATE_FROM']) . ((empty($_GET['DATE_TO'])) ? '' : '&DATE_TO=' . $_GET['DATE_TO']) . ((empty($_GET['search_text'])) ? '' : '&search_text=' . $search_text) . '&STATUS=' . $status_filter . '&sort_by=' . $sort_by ?>">&rsaquo;</a></li>
                                        <li><a href="all_enrollments.php?page=<?= $number_of_page . ((empty($_GET['DATE_FROM'])) ? '' : '&DATE_FROM=' . $_GET['DATE_FROM']) . ((empty($_GET['DATE_TO'])) ? '' : '&DATE_TO=' . $_GET['DATE_TO']) . ((empty($_GET['search_text'])) ? '' : '&search_text=' . $search_text) . '&STATUS=' . $status_filter . '&sort_by=' . $sort_by ?>">&raquo;</a></li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>

                        <select class="form-select form-select-sm" style="width: auto;">
                            <option>50 / page</option>
                            <option>100 / page</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Enrollment Modal -->
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
                                            </div>
                                        </div>
                                    </div>
                                    <a href="javascript:" class="btn btn-info waves-effect waves-light m-l-10 text-white next" style="float: right;" onclick="$('#step_2').hide();$('#step_3').show();showEnrollmentServiceDetails();">Continue</a>
                                    <a href="javascript:" class="btn btn-info waves-effect waves-light text-white prev" style="*float: right;" onclick="$('#step_2').hide();$('#step_1').show();">Go Back</a>
                                </div>

                                <div id="step_3" style="display: none;">
                                    <div id="enrollment_service_details"></div>
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

                                    <a href="javascript:" class="btn btn-info waves-effect waves-light text-white" onclick="$('#step_3').hide();$('#step_2').show();">Go Back</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>
    <?php include 'partials/create_enrollment_modal.php'; ?>

    <script>
        $(document).ready(function() {
            // Initialize datepickers
            $('.datepicker-normal').datepicker({
                dateFormat: 'mm/dd/yy'
            });

            // Search with delay
            let searchTimeout;
            $('#search_text').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(submitFilters, 500);
            });

            // Date filters - auto submit on change
            $('#DATE_FROM, #DATE_TO').on('change', function() {
                submitFilters();
            });

            // Clear date range
            $('#clearDateRange').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#DATE_FROM, #DATE_TO').val('');
                submitFilters();
            });

            // Sort dropdown items
            $(document).on('click', '.dropdown-item[data-sort]', function(e) {
                e.preventDefault();
                let sortValue = $(this).data('sort');
                let searchText = $('#search_text').val() || '';
                let status = '<?= $status_filter ?>';
                let dateFrom = $('#DATE_FROM').val() || '';
                let dateTo = $('#DATE_TO').val() || '';

                let url = window.location.pathname + '?';
                let params = [];
                if (searchText) params.push('search_text=' + encodeURIComponent(searchText));
                if (status) params.push('STATUS=' + encodeURIComponent(status));
                if (dateFrom) params.push('DATE_FROM=' + encodeURIComponent(dateFrom));
                if (dateTo) params.push('DATE_TO=' + encodeURIComponent(dateTo));
                if (sortValue) params.push('sort_by=' + encodeURIComponent(sortValue));
                url += params.join('&');
                window.location.href = url;
            });

            // Table column sorting
            $(".sortable-header").on("click", function(e) {
                e.preventDefault();

                var table = $(this).closest("table");
                var tbody = table.find("tbody");
                var rows = tbody.find("tr").toArray();
                var index = $(this).data("index");
                var type = $(this).data("type") || "string";
                var isDate = $(this).data("date") || false;

                var isAsc = $(this).hasClass("asc");

                table.find(".sortable-header").removeClass("asc desc");
                $(this).addClass(isAsc ? "desc" : "asc");

                rows.sort(function(a, b) {
                    var A = $(a).children("td").eq(index).text().trim();
                    var B = $(b).children("td").eq(index).text().trim();

                    if (isDate || type === "date") {
                        var dateA = new Date(A);
                        var dateB = new Date(B);
                        if (isNaN(dateA)) dateA = new Date(0);
                        if (isNaN(dateB)) dateB = new Date(0);
                        A = dateA;
                        B = dateB;
                    } else if (type === "number" || type === "currency") {
                        A = parseFloat(A.replace(/[^0-9.\-]/g, "")) || 0;
                        B = parseFloat(B.replace(/[^0-9.\-]/g, "")) || 0;
                    } else if (type === "name") {
                        var nameA = A.toLowerCase().split(' ');
                        var nameB = B.toLowerCase().split(' ');
                        A = nameA[0] || '';
                        B = nameB[0] || '';
                    } else {
                        A = A.toLowerCase();
                        B = B.toLowerCase();
                    }

                    if (A < B) return isAsc ? -1 : 1;
                    if (A > B) return isAsc ? 1 : -1;
                    return 0;
                });

                $.each(rows, function(i, row) {
                    tbody.append(row);
                });
            });
        });

        function submitFilters() {
            let searchText = $('#search_text').val() || '';
            let status = '<?= $status_filter ?>';
            let dateFrom = $('#DATE_FROM').val() || '';
            let dateTo = $('#DATE_TO').val() || '';
            let sortBy = '<?= $sort_by ?>';

            let url = window.location.pathname + '?';
            let params = [];
            if (searchText) params.push('search_text=' + encodeURIComponent(searchText));
            if (status) params.push('STATUS=' + encodeURIComponent(status));
            if (dateFrom) params.push('DATE_FROM=' + encodeURIComponent(dateFrom));
            if (dateTo) params.push('DATE_TO=' + encodeURIComponent(dateTo));
            if (sortBy) params.push('sort_by=' + encodeURIComponent(sortBy));
            url += params.join('&');
            window.location.href = url;
        }

        function resetFilters() {
            window.location.href = window.location.pathname + '?STATUS=A&sort_by=newest';
        }

        function createEnrollment() {
            if (<?= count($LOCATION_ARRAY) ?> === 1) {
                $('#sideDrawer4, .overlay4').addClass('active');
            } else {
                swal("Select One Location!", "Only one location can be selected on top of the page in order to create an enrollment.", "error");
            }
        }

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
                            window.location.href = extractedPart;
                        }
                    });
                }
            });
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
    </script>
</body>

</html>