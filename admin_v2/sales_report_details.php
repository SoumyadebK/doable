<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "SALES REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'] ?? 'view';

$from_date = date('Y-m-d', strtotime($_GET['start_date'] ?? ''));
$to_date = date('Y-m-d', strtotime($_GET['end_date'] ?? ''));
$service_provider_id = $_GET['service_provider_id'] ?? '';

$selected_service_provider = [];
$selected_service_provider_name = [];
if (!empty($service_provider_id)) {
    $selected_service_provider_row = $db->Execute("SELECT DISTINCT DOA_USERS.`PK_USER`, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM `DOA_USERS` LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USERS.PK_USER IN (" . $service_provider_id . ") AND DOA_USER_ROLES.`PK_ROLES` = 5");
    while (!$selected_service_provider_row->EOF) {
        $selected_service_provider[] = $selected_service_provider_row->fields['PK_USER'];
        $selected_service_provider_name[] = $selected_service_provider_row->fields['NAME'];
        $selected_service_provider_row->MoveNext();
    }
}

$totalResults = count($selected_service_provider_name);
$concatenatedServiceProviders = "";
foreach ($selected_service_provider_name as $key => $result) {
    $concatenatedServiceProviders .= $result;
    if ($key < $totalResults - 1) {
        $concatenatedServiceProviders .= ", ";
    }
}

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';
if (preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $business_name)) {
    $business_name = '';
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

if (!empty($_GET['START_DATE'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = 'sales_report';
    $WEEK_NUMBER = explode(' ', $_GET['WEEK_NUMBER'] ?? '0')[2] ?? '0';
    $START_DATE = date('Y-m-d', strtotime($_GET['START_DATE']));
    $END_DATE = date('Y-m-d', strtotime($_GET['END_DATE']));
    $PK_USER = empty($_GET['PK_USER']) ? 0 : $_GET['PK_USER'];

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&service_provider_id=' . implode(',', (array)$PK_USER));
        exit;
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&service_provider_id=' . implode(',', (array)$PK_USER));
        exit;
    } else {
        header('location:sales_report_details.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type . '&service_provider_id=' . implode(',', (array)$PK_USER));
        exit;
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

    /* Table styling for distinct sections */
    .section-header {
        background-color: #f1f3f5 !important;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 13px;
        color: #495057;
    }

    .cancel-section-header {
        background-color: #fff3cd !important;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 13px;
        color: #856404;
    }

    .total-row {
        background-color: #e9ecef !important;
        font-weight: bold;
    }

    .net-total-row {
        background-color: #d1e7dd !important;
        font-weight: bold;
        font-size: 16px;
    }

    .cancelled-text {
        color: #dc3545 !important;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 1px !important;">
            <?php require_once('layout/report_menu.php') ?>
            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body" style="padding-bottom: 0px !important;">
                                <form class="form-material form-horizontal" action="" method="get" id="reportForm">
                                    <input type="hidden" name="start_date" id="start_date">
                                    <input type="hidden" name="end_date" id="end_date">
                                    <div class="row">
                                        <div class="col-2">
                                            <div id="location" style="width: 100%;">
                                                <select class="multi_select_service_provider" multiple id="service_provider_select" name="PK_USER[]">
                                                    <?php
                                                    $selected_provider_ids = [];
                                                    if (!empty($service_provider_id) && $service_provider_id != '0') {
                                                        $selected_provider_ids = explode(',', $service_provider_id);
                                                    }
                                                    $query = "SELECT DISTINCT DU.PK_USER, CONCAT(DU.FIRST_NAME, ' ', DU.LAST_NAME) AS NAME 
                                                              FROM DOA_USERS DU
                                                              INNER JOIN DOA_USER_ROLES DUR ON DU.PK_USER = DUR.PK_USER 
                                                              INNER JOIN DOA_USER_LOCATION DUL ON DU.PK_USER = DUL.PK_USER 
                                                              WHERE DU.ACTIVE = 1 AND DUR.PK_ROLES = 5 
                                                              AND DUL.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
                                                              AND DU.PK_ACCOUNT_MASTER = '" . $_SESSION['PK_ACCOUNT_MASTER'] . "'
                                                              ORDER BY DU.FIRST_NAME, DU.LAST_NAME";
                                                    $row = $db->Execute($query);
                                                    if ($row && $row->RecordCount() > 0) {
                                                        while (!$row->EOF) {
                                                            $user_id = $row->fields['PK_USER'];
                                                            $selected = in_array($user_id, $selected_provider_ids) ? 'selected' : '';
                                                            echo '<option value="' . $user_id . '" ' . $selected . '>' . htmlspecialchars($row->fields['NAME']) . '</option>';
                                                            $row->MoveNext();
                                                        }
                                                    } else {
                                                        echo '<option value="">No Service Providers Found</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
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
                                        <div class="col-4">
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
                    // Export logic (keeping your original structure)
                    $response = json_decode($post_data ?? '');
                    if (isset($response->error)) {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">' . $response->error_description . '</div>';
                    } elseif (isset($response->errors)) {
                        if (isset($response->errors->errors[0])) {
                            echo '<div class="alert alert-danger alert-dismissible" role="alert">' . $response->errors->errors[0] . '</div>';
                        } else {
                            echo '<div class="alert alert-danger alert-dismissible" role="alert">' . $response->message . '</div>';
                        }
                    } else {
                        echo "<h3>Data export to Arthur Murray API Successfully</h3>";
                    }
                } else {
                    // Build the service provider filter condition
                    $service_provider_filter = "";
                    if (!empty($service_provider_id) && $service_provider_id != '0') {
                        $service_provider_filter = "AND DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER IN (
                                                    SELECT DISTINCT PK_ENROLLMENT_MASTER 
                                                    FROM DOA_ENROLLMENT_SERVICE_PROVIDER 
                                                    WHERE SERVICE_PROVIDER_ID IN (" . $service_provider_id . ")
                                                )";
                    }

                    // Query 1: All Positive Sales (Includes Active and Cancelled original amounts)
                    $row_active = $db_account->Execute("
                        SELECT 
                            DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE AS DATE,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_ENROLLMENT_MASTER.MISC_ID,
                            CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT,
                            IFNULL(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT, 0) AS TOTAL_AMOUNT,
                            DOA_ENROLLMENT_MASTER.STATUS
                        FROM DOA_ENROLLMENT_MASTER
                        INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER 
                            ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                        INNER JOIN $master_database.DOA_USERS AS DOA_USERS 
                            ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER
                        LEFT JOIN DOA_ENROLLMENT_BILLING 
                            ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                        WHERE DOA_USERS.IS_DELETED = 0 
                        AND DOA_USERS.ACTIVE = 1
                        AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                        AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' 
                            AND '" . date('Y-m-d', strtotime($to_date)) . "'
                        $service_provider_filter
                        ORDER BY DATE DESC
                    ");

                    // Query 2: Cancellations (Only the unused/deducted amounts)
                    $row_cancelled = $db_account->Execute("
                        SELECT 
                            DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID,
                            MAX(DOA_ENROLLMENT_CANCEL.CANCEL_DATE) AS DATE,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_ENROLLMENT_MASTER.MISC_ID,
                            CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT,
                            SUM(DOA_ENROLLMENT_CANCEL.ACTUAL_AMOUNT) AS ORIGINAL_SALE,
                            SUM(DOA_ENROLLMENT_CANCEL.CANCEL_AMOUNT) AS CANCELLED_AMOUNT,
                            DOA_ENROLLMENT_MASTER.STATUS
                        FROM DOA_ENROLLMENT_CANCEL
                        INNER JOIN DOA_ENROLLMENT_MASTER 
                            ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_CANCEL.PK_ENROLLMENT_MASTER
                        INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER 
                            ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                        INNER JOIN $master_database.DOA_USERS AS DOA_USERS 
                            ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER
                        WHERE DOA_USERS.IS_DELETED = 0 
                        AND DOA_USERS.ACTIVE = 1
                        AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                        AND DOA_ENROLLMENT_CANCEL.CANCEL_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' 
                            AND '" . date('Y-m-d', strtotime($to_date)) . "'
                        $service_provider_filter
                        GROUP BY 
                            DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_ENROLLMENT_MASTER.MISC_ID,
                            DOA_USERS.FIRST_NAME, 
                            DOA_USERS.LAST_NAME,
                            DOA_ENROLLMENT_MASTER.STATUS
                        ORDER BY DATE DESC
                    ");

                    $total_sales = 0;
                    $total_sales_provider = 0;
                    $total_cancelled = 0;
                    $total_cancelled_provider = 0;
                ?>
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
                                                    <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="6"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= " (" . $concatenatedResults . ")" . " (" . $concatenatedServiceProviders . ")" ?></th>
                                                    <th style="width:50%; text-align: center; font-weight: bold" colspan="4">(<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</th>
                                                </tr>
                                                <tr>
                                                    <th style="width:10%; text-align: center">Date</th>
                                                    <th style="width:10%; text-align: center">Student</th>
                                                    <th style="width:10%; text-align: center">Amount of Sale</th>
                                                    <th style="width:10%; text-align: center">Service Provider Amount</th>
                                                    <th style="width:10%; text-align: center">Enrollment Name</th>
                                                    <th style="width:10%; text-align: center">Services</th>
                                                    <th style="width:10%; text-align: center">Executive</th>
                                                    <th style="width:12%; text-align: center">Teacher 1</th>
                                                    <th style="width:12%; text-align: center">Teacher 2</th>
                                                    <th style="width:12%; text-align: center">Teacher 3</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                                <!-- ============================================== -->
                                                <!-- SECTION 1: POSITIVE SALES                     -->
                                                <!-- ============================================== -->
                                                <tr>
                                                    <td colspan="10" class="section-header">1. POSITIVE SALES & ENROLLMENTS (Gross)</td>
                                                </tr>
                                                <?php
                                                while (!$row_active->EOF) {
                                                    $enr_status = $row_active->fields['STATUS'];
                                                    $name = $row_active->fields['ENROLLMENT_NAME'];
                                                    $ENROLLMENT_ID = $row_active->fields['ENROLLMENT_ID'];
                                                    $enrollment_name = empty($name) ? "" : "$name - ";

                                                    $sale_amount = $row_active->fields['TOTAL_AMOUNT'];
                                                    $total_sales += $sale_amount;

                                                    $serviceCode = [];
                                                    $serviceCodeData = $db_account->Execute(
                                                        "SELECT 
                                                            DOA_SERVICE_CODE.SERVICE_CODE, 
                                                            DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION,
                                                            DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION,
                                                            DOA_ENROLLMENT_SERVICE.ACTUAL_AMOUNT
                                                        FROM DOA_SERVICE_CODE 
                                                        JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE 
                                                        WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $row_active->fields['PK_ENROLLMENT_MASTER']
                                                    );
                                                    while (!$serviceCodeData->EOF) {
                                                        $session_count = $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                                        $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'] . ': ' . $session_count;
                                                        $serviceCodeData->MoveNext();
                                                    }

                                                    $executive = $db->Execute("SELECT CONCAT(FIRST_NAME, ' ', LAST_NAME) AS EXECUTIVE FROM DOA_USERS WHERE PK_USER = " . $row_active->fields['ENROLLMENT_BY_ID']);

                                                    $results = $db_account->Execute(
                                                        "SELECT 
                                                            CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER, 
                                                            SERVICE_PROVIDER_PERCENTAGE 
                                                        FROM DOA_ENROLLMENT_SERVICE_PROVIDER 
                                                        LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID 
                                                        WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $row_active->fields['PK_ENROLLMENT_MASTER']
                                                    );
                                                    $resultsArray = [];
                                                    $provider_percentage = 0;
                                                    while (!$results->EOF) {
                                                        $resultsArray[] = $results->fields['SERVICE_PROVIDER'] . ' (' . number_format($results->fields['SERVICE_PROVIDER_PERCENTAGE']) . '%)';
                                                        $provider_percentage = $results->fields['SERVICE_PROVIDER_PERCENTAGE'];
                                                        $results->MoveNext();
                                                    }

                                                    $provider_amount = ($sale_amount * $provider_percentage / 100);
                                                    $total_sales_provider += $provider_amount;
                                                ?>
                                                    <tr>
                                                        <td style="text-align: center"><?= date('m/d/Y', strtotime($row_active->fields['DATE'])) ?></td>
                                                        <td style="text-align: center"><?= $row_active->fields['CLIENT'] ?></td>
                                                        <td style="text-align: right">$<?= number_format($sale_amount, 2) ?></td>
                                                        <td style="text-align: right">$<?= number_format($provider_amount, 2) ?></td>
                                                        <td style="text-align: center">
                                                            <?= ($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $row_active->fields['MISC_ID'] : $enrollment_name . $ENROLLMENT_ID ?>
                                                            <?php if ($enr_status == 'C' || $enr_status == 'CA') { ?>
                                                                <br><small class="cancelled-text">(Cancelled - See Deductions)</small>
                                                            <?php } elseif ($enr_status == 'CO') { ?>
                                                                <br><small class="text-warning">(Completed)</small>
                                                            <?php } else { ?>
                                                                <br><small class="text-info">(Active)</small>
                                                            <?php } ?>
                                                        </td>
                                                        <td style="text-align: center"><?= implode(', ', $serviceCode) ?></td>
                                                        <td style="text-align: center"><?= empty($executive->fields['EXECUTIVE']) ? '' : $executive->fields['EXECUTIVE'] ?></td>
                                                        <td style="text-align: center"><?= (isset($resultsArray[0]) && $resultsArray[0]) ? $resultsArray[0] : '' ?></td>
                                                        <td style="text-align: center"><?= (isset($resultsArray[1]) && $resultsArray[1]) ? $resultsArray[1] : '' ?></td>
                                                        <td style="text-align: center"><?= (isset($resultsArray[2]) && $resultsArray[2]) ? $resultsArray[2] : '' ?></td>
                                                    </tr>
                                                <?php $row_active->MoveNext();
                                                } ?>

                                                <!-- Subtotal for Positive Sales -->
                                                <tr class="total-row">
                                                    <td colspan="2" style="text-align: right;">Gross Sales Subtotal:</td>
                                                    <td style="text-align: right;">$<?= number_format($total_sales, 2) ?></td>
                                                    <td style="text-align: right;">$<?= number_format($total_sales_provider, 2) ?></td>
                                                    <td colspan="6"></td>
                                                </tr>

                                                <!-- ============================================== -->
                                                <!-- SECTION 2: CANCELLATIONS / DEDUCTIONS         -->
                                                <!-- ============================================== -->
                                                <tr>
                                                    <td colspan="10" class="cancel-section-header">2. CANCELLATIONS & REFUNDS (Deductions)</td>
                                                </tr>
                                                <?php
                                                while (!$row_cancelled->EOF) {
                                                    $enr_status = $row_cancelled->fields['STATUS'];
                                                    $name = $row_cancelled->fields['ENROLLMENT_NAME'];
                                                    $ENROLLMENT_ID = $row_cancelled->fields['ENROLLMENT_ID'];
                                                    $enrollment_name = empty($name) ? "" : "$name - ";

                                                    $original_sale = $row_cancelled->fields['ORIGINAL_SALE'];
                                                    $cancelled_amount = $row_cancelled->fields['CANCELLED_AMOUNT'];
                                                    $total_cancelled += $cancelled_amount;

                                                    $serviceCode = [];
                                                    $serviceCodeData = $db_account->Execute(
                                                        "SELECT 
                                                            DOA_SERVICE_CODE.SERVICE_CODE, 
                                                            DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, 
                                                            DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, 
                                                            DOA_ENROLLMENT_CANCEL.ACTUAL_AMOUNT 
                                                        FROM DOA_SERVICE_CODE 
                                                        JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE 
                                                        JOIN DOA_ENROLLMENT_CANCEL ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE = DOA_ENROLLMENT_CANCEL.PK_ENROLLMENT_SERVICE 
                                                        WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $row_cancelled->fields['PK_ENROLLMENT_MASTER']
                                                    );
                                                    while (!$serviceCodeData->EOF) {
                                                        $original_session = ($serviceCodeData->fields['PRICE_PER_SESSION'] > 0) ? ($serviceCodeData->fields['ACTUAL_AMOUNT'] / $serviceCodeData->fields['PRICE_PER_SESSION']) : 0;
                                                        $remaining_session = $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                                        $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'] . ': ' . $remaining_session . ' / ' . number_format($original_session, 0);
                                                        $serviceCodeData->MoveNext();
                                                    }

                                                    $executive = $db->Execute("SELECT CONCAT(FIRST_NAME, ' ', LAST_NAME) AS EXECUTIVE FROM DOA_USERS WHERE PK_USER = " . $row_cancelled->fields['ENROLLMENT_BY_ID']);

                                                    $results = $db_account->Execute(
                                                        "SELECT 
                                                            CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER, 
                                                            SERVICE_PROVIDER_PERCENTAGE 
                                                        FROM DOA_ENROLLMENT_SERVICE_PROVIDER 
                                                        LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID 
                                                        WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $row_cancelled->fields['PK_ENROLLMENT_MASTER']
                                                    );
                                                    $resultsArray = [];
                                                    $provider_percentage = 0;
                                                    while (!$results->EOF) {
                                                        $resultsArray[] = $results->fields['SERVICE_PROVIDER'] . ' (' . number_format($results->fields['SERVICE_PROVIDER_PERCENTAGE']) . '%)';
                                                        $provider_percentage = $results->fields['SERVICE_PROVIDER_PERCENTAGE'];
                                                        $results->MoveNext();
                                                    }

                                                    $provider_cancel_amount = ($cancelled_amount * $provider_percentage / 100);
                                                    $total_cancelled_provider += $provider_cancel_amount;
                                                ?>
                                                    <tr class="cancelled-row">
                                                        <td style="text-align: center; color: #dc3545;"><?= date('m/d/Y', strtotime($row_cancelled->fields['DATE'])) ?></td>
                                                        <td style="text-align: center; color: #dc3545;"><?= $row_cancelled->fields['CLIENT'] ?></td>
                                                        <td style="text-align: right; color: #dc3545;">
                                                            -$<?= number_format($cancelled_amount, 2) ?>
                                                            <br><small style="color:#6c757d;" title="Original Enrollment Amount">(Orig: $<?= number_format($original_sale, 2) ?>)</small>
                                                        </td>
                                                        <td style="text-align: right; color: #dc3545;">
                                                            -$<?= number_format($provider_cancel_amount, 2) ?>
                                                        </td>
                                                        <td style="text-align: center; color: #dc3545;">
                                                            <?= ($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $row_cancelled->fields['MISC_ID'] : $enrollment_name . $ENROLLMENT_ID ?>
                                                            <br><small class="text-danger">(Cancelled)</small>
                                                        </td>
                                                        <td style="text-align: center; color: #dc3545;"><?= implode(', ', $serviceCode) ?></td>
                                                        <td style="text-align: center; color: #dc3545;"><?= empty($executive->fields['EXECUTIVE']) ? '' : $executive->fields['EXECUTIVE'] ?></td>
                                                        <td style="text-align: center; color: #dc3545;"><?= (isset($resultsArray[0]) && $resultsArray[0]) ? $resultsArray[0] : '' ?></td>
                                                        <td style="text-align: center; color: #dc3545;"><?= (isset($resultsArray[1]) && $resultsArray[1]) ? $resultsArray[1] : '' ?></td>
                                                        <td style="text-align: center; color: #dc3545;"><?= (isset($resultsArray[2]) && $resultsArray[2]) ? $resultsArray[2] : '' ?></td>
                                                    </tr>
                                                <?php $row_cancelled->MoveNext();
                                                } ?>

                                                <!-- Subtotal for Cancellations -->
                                                <tr class="total-row">
                                                    <td colspan="2" style="text-align: right;">Total Cancellations:</td>
                                                    <td style="text-align: right; color: #dc3545;">-$<?= number_format($total_cancelled, 2) ?></td>
                                                    <td style="text-align: right; color: #dc3545;">-$<?= number_format($total_cancelled_provider, 2) ?></td>
                                                    <td colspan="6"></td>
                                                </tr>

                                                <!-- ============================================== -->
                                                <!-- SECTION 3: FINAL NET TOTALS                    -->
                                                <!-- ============================================== -->
                                                <tr class="net-total-row">
                                                    <td colspan="2" style="text-align: right; font-size: 16px;">NET TOTALS:</td>
                                                    <td style="text-align: right; color: #0f5132; font-size: 16px;">$<?= number_format($total_sales - $total_cancelled, 2) ?></td>
                                                    <td style="text-align: right; color: #0f5132; font-size: 16px;">$<?= number_format($total_sales_provider - $total_cancelled_provider, 2) ?></td>
                                                    <td colspan="6"></td>
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
        $('.multi_select_service_provider').SumoSelect({
            placeholder: 'Select Service Provider',
            selectAll: true,
            triggerChangeCombined: true,
            search: true,
            searchText: 'Search Service Providers...'
        });

        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true,
            todayHighlight: true
        });

        $('#reportForm').on('submit', function(e) {
            var startDate = $('#START_DATE').val();
            var endDate = $('#END_DATE').val();

            if (!startDate || !endDate) {
                alert('Please select both start date and end date.');
                e.preventDefault();
                return false;
            }

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