<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "SUMMARY OF STUDIO BUSINESS REPORT";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['week_number'])){
    $week_number = $_GET['week_number'];
    $YEAR = date('Y');
    $dto = new DateTime();
    $dto->setISODate($YEAR, $week_number+1);
    $from_date = $dto->modify('-1 day')->format('Y-m-d');
    $dto->modify('+6 days');
    $to_date = $dto->format('Y-m-d');
}


// Calculate the year and week number of the selected date
$selected_year = date('Y', strtotime($from_date));
$selected_week = date('W', strtotime($from_date));

// Calculate the previous year
$previous_year = $selected_year - 1;

// Find the first day of the selected week in the previous year
$first_day_of_week_previous_year = date('Y-m-d', strtotime($previous_year . 'W' . str_pad($selected_week, 2, '0', STR_PAD_LEFT)));

// Find the last day of the selected week in the previous year
$last_day_of_week_previous_year = date('Y-m-d', strtotime($first_day_of_week_previous_year . ' +6 days'));

$res = $db->Execute("SELECT BUSINESS_NAME FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$business_name = $res->RecordCount() > 0 ? $res->fields['BUSINESS_NAME'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>

<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">

        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">

            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                            <li class="breadcrumb-item active"><a href="customer_summary_report.php"><?=$title?></a></li>
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
                                <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?=$title?></h3>
                            </div>
                            <div class="table-responsive">
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:40%; text-align: center; vertical-align:auto; font-weight: bold"><?=$business_name?></th>
                                        <th style="width:20%; text-align: center; font-weight: bold"><?=$from_date?> - <?=$to_date?></th>
                                        <th style="width:20%; text-align: center; font-weight: bold">Week # <?=$week_number?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-responsive">
                                <label style="width:100%; text-align: center; font-weight: bold">CASH RECEIPTS</label>
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr style='font-weight: normal;'>
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold">Period</th>
                                        <th style="width:20%; text-align: center; font-weight: bold">Regular</th>
                                        <th style="width:20%; text-align: center; font-weight: bold">Misc. / NonUnit</th>
                                        <th style="width:30%; text-align: center; font-weight: bold">Total</th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $regular_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS CASH FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER != 5 AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = 3 AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $regular_cash = $regular_data->fields['CASH'] > 0 ? $regular_data->fields['CASH'] : '0.00';
                                        $misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS CASH FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = 5 AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = 3 AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $misc_cash = $misc_data->fields['CASH'] > 0 ? $misc_data->fields['CASH'] : '0.00';
                                        $total_cash = $regular_cash + $misc_cash
                                        ?>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Week</th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important;"><?=number_format($regular_cash,2,'.','')?></th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important"><?=number_format($misc_cash,2,'.','')?></th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important"><?=number_format($total_cash, 2, '.', '')?></th>
                                    </tr>
                                    <tr>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Week Refunds</th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important">0.00</th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important">0.00</th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important">0.00</th>
                                    </tr>
                                    <tr>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Transfer out</th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important">0.00</th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important">0.00</th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important">0.00</th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $net_regular_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS CASH FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER != 5 AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = 3 AND YEAR(DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE) = YEAR(CURDATE())");
                                        $net_regular_cash = $net_regular_data->fields['CASH'] > 0 ? $net_regular_data->fields['CASH'] : '0.00';
                                        $net_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS CASH FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = 5 AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = 3 AND YEAR(DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE) = YEAR(CURDATE())");
                                        $net_misc_cash = $net_misc_data->fields['CASH'] > 0 ? $net_misc_data->fields['CASH'] : '0.00';
                                        $net_total_cash = $net_misc_cash + $net_regular_cash;
                                        ?>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">NET Y.T.D.</th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important"><?=number_format($net_regular_cash,2,'.','')?></th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important"><?=number_format($net_misc_cash,2,'.','')?></th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important"><?=number_format($net_total_cash, 2, '.', '')?></th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $prev_regular_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS CASH FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER != 5 AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = 3 AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $prev_regular_cash = $prev_regular_data->RecordCount() > 0 ? $prev_regular_data->fields['CASH'] : '0.00';
                                        $prev_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS CASH FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER != 5 AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = 3 AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $prev_misc_cash = $prev_misc_data->RecordCount() > 0 ? $prev_misc_data->fields['CASH'] : '0.00';
                                        $total_prev_cash = $prev_misc_cash + $prev_regular_cash;
                                        ?>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">PRV. Y.T.D.</th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important"><?=number_format($prev_regular_cash,2,'.','')?></th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important"><?=number_format($prev_misc_cash,2,'.','')?></th>
                                        <th style="width:25%; text-align: center; font-weight: normal !important"><?=number_format($total_prev_cash, 2, '.', '')?></th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="table-responsive">
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold; border: 1px solid black; border-bottom: 0px solid black;" colspan="4">INQUIRIES</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" colspan="3">LESSONS TAUGHT | Exchange</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" rowspan="2">ACTIVE<br/>
                                            STUDENTS</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold; border-left: 1px solid black;" colspan="2">Contact</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Booked</th>
                                        <th style="width:10%; text-align: center; font-weight: bold; border-right: 1px solid black;">Showed</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Pvt Intv(front)</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Pvt Ren(back)</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"># in class [incl.core]</th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $customer_data = $db->Execute("SELECT COUNT(DOA_USERS.PK_USER) AS CUSTOMER FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USER_ROLES.PK_USER=DOA_USERS.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES=4 AND DOA_USERS.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $customer = $customer_data->RecordCount() > 0 ? $customer_data->fields['CUSTOMER'] : '0';
                                        $appointment_data = $db->Execute("SELECT COUNT(DISTINCT DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER) AS BOOKED FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_APPOINTMENT_CUSTOMER AS DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $account_database.DOA_APPOINTMENT_MASTER AS DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USERS.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $booked = $appointment_data->RecordCount() > 0 ? $appointment_data->fields['BOOKED'] : '0';
                                        $showed_data = $db->Execute("SELECT COUNT(DISTINCT DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER) AS SHOWED FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_APPOINTMENT_CUSTOMER AS DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $account_database.DOA_APPOINTMENT_MASTER AS DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 2 AND DOA_USERS.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $showed = $showed_data->RecordCount() > 0 ? $showed_data->fields['SHOWED'] : '0';

                                        $upto_three_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED) AS TOTAL_SESSION FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE = DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_CODE.IS_GROUP = 0 AND DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER <= 3");
                                        $upto_three = $upto_three_data->fields['TOTAL_SESSION'] > 0 ? $upto_three_data->fields['TOTAL_SESSION'] : '0';
                                        $above_three_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED) AS TOTAL_SESSION FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE = DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_CODE.IS_GROUP = 0 AND DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER > 3");
                                        $above_three = $above_three_data->fields['TOTAL_SESSION'] > 0 ? $above_three_data->fields['TOTAL_SESSION'] : '0';
                                        $group_class_data = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER) AS GROUP_CLASS FROM `DOA_APPOINTMENT_MASTER` LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER=DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS=2 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND APPOINTMENT_TYPE='GROUP'");
                                        $group_class = $group_class_data->RecordCount() > 0 ? $group_class_data->fields['GROUP_CLASS'] : '0';
                                        ?>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold; border-left: 1px solid black;">Week</th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=$customer?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=$booked?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important; border-right: 1px solid black;"><?=$showed?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=$upto_three?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=$above_three?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=$group_class." [".$group_class."]"?></th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Department</th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $customer_ytd_data = $db->Execute("SELECT COUNT(DOA_USERS.PK_USER) AS CUSTOMER FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USER_ROLES.PK_USER=DOA_USERS.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES=4 AND DOA_USERS.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $customer_ytd = $customer_ytd_data->RecordCount() > 0 ? $customer_ytd_data->fields['CUSTOMER'] : '0';
                                        $appointment_ytd_data = $db->Execute("SELECT COUNT(DISTINCT DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER) AS BOOKED FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_APPOINTMENT_CUSTOMER AS DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $account_database.DOA_APPOINTMENT_MASTER AS DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USERS.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $booked_ytd = $appointment_ytd_data->RecordCount() > 0 ? $appointment_ytd_data->fields['BOOKED'] : '0';
                                        $showed_ytd_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER) AS SHOWED FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.DATE BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND DATE_ADD(CURDATE(), INTERVAL (7 - DAYOFWEEK(CURDATE())) DAY)");
                                        $showed_ytd = $showed_ytd_data->RecordCount() > 0 ? $showed_ytd_data->fields['SHOWED'] : '0';

                                        $upto_three_ytd_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED) AS TOTAL_SESSION FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE = DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."' AND DOA_SERVICE_CODE.IS_GROUP = 0 AND DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER <= 3");
                                        $upto_three_ytd = $upto_three_ytd_data->fields['TOTAL_SESSION'] > 0 ? $upto_three_ytd_data->fields['TOTAL_SESSION'] : '0';
                                        $above_three_ytd_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED) AS TOTAL_SESSION FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE = DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."' AND DOA_SERVICE_CODE.IS_GROUP = 0 AND DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER > 3");
                                        $above_three_ytd = $above_three_ytd_data->fields['TOTAL_SESSION'] > 0 ? $above_three_ytd_data->fields['TOTAL_SESSION'] : '0';
                                        $group_class_ytd_data = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER) AS GROUP_CLASS FROM `DOA_APPOINTMENT_MASTER` LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER=DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS=2 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND APPOINTMENT_TYPE='GROUP' AND DOA_APPOINTMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $group_class_ytd = $group_class_ytd_data->RecordCount() > 0 ? $group_class_ytd_data->fields['GROUP_CLASS'] : '0';

                                        $intv_data = $db_account->Execute("SELECT SUM(CUSTOMER_COUNT) AS INTV FROM (SELECT COUNT(DISTINCT PK_USER_MASTER) AS CUSTOMER_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER=DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND APPOINTMENT_TYPE='NORMAL' AND DOA_APPOINTMENT_MASTER.CREATED_ON >= CURDATE() - INTERVAL 30 DAY GROUP BY PK_USER_MASTER HAVING COUNT(DISTINCT DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER) <= 3) AS INTV");
                                        $intv = $intv_data->RecordCount() > 0 ? $intv_data->fields['INTV'] : '0';
                                        ?>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold; border-left: 1px solid black;">YTD</th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($customer_ytd, 2, '.', '')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($booked_ytd, 2, '.', '')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important; border-right: 1px solid black;"><?=number_format($showed_ytd, 2, '.', '')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($upto_three_ytd, 1, '.', '')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($above_three_ytd, 1, '.', '')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=$group_class_ytd." [".$group_class_ytd."]"?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=$intv?> Intv(front)</th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $customer_prev_data = $db->Execute("SELECT COUNT(DOA_USERS.PK_USER) AS CUSTOMER FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USER_ROLES.PK_USER=DOA_USERS.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES=4 AND DOA_USERS.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $customer_prev = $customer_prev_data->RecordCount() > 0 ? $customer_prev_data->fields['CUSTOMER'] : '0';
                                        $appointment_prev_data = $db->Execute("SELECT COUNT(DISTINCT DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER) AS BOOKED FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_APPOINTMENT_CUSTOMER AS DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $account_database.DOA_APPOINTMENT_MASTER AS DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USERS.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $booked_prev = $appointment_ytd_data->RecordCount() > 0 ? $appointment_prev_data->fields['BOOKED'] : '0';
                                        $showed_prev_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER) AS SHOWED FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.DATE BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $showed_prev = $showed_prev_data->RecordCount() > 0 ? $showed_prev_data->fields['SHOWED'] : '0';

                                        $upto_three_prev_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED) AS TOTAL_SESSION FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE = DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."' AND DOA_SERVICE_CODE.IS_GROUP = 0 AND DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER <= 3");
                                        $upto_three_prev = $upto_three_prev_data->fields['TOTAL_SESSION'] > 0 ? $upto_three_prev_data->fields['TOTAL_SESSION'] : '0';
                                        $above_three_prev_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED) AS TOTAL_SESSION FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE = DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."' AND DOA_SERVICE_CODE.IS_GROUP = 0 AND DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER > 3");
                                        $above_three_prev = $above_three_prev_data->fields['TOTAL_SESSION'] > 0 ? $above_three_prev_data->fields['TOTAL_SESSION'] : '0';
                                        $group_class_prev_data = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER) AS GROUP_CLASS FROM `DOA_APPOINTMENT_MASTER` LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER=DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS=2 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND APPOINTMENT_TYPE='GROUP' AND DOA_APPOINTMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $group_class_prev = $group_class_prev_data->RecordCount() > 0 ? $group_class_prev_data->fields['GROUP_CLASS'] : '0';

                                        $ren_data = $db_account->Execute("SELECT SUM(CUSTOMER_COUNT) AS REN FROM (SELECT COUNT(DISTINCT PK_USER_MASTER) AS CUSTOMER_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER=DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND APPOINTMENT_TYPE='NORMAL' AND DOA_APPOINTMENT_MASTER.CREATED_ON >= CURDATE() - INTERVAL 30 DAY GROUP BY PK_USER_MASTER HAVING COUNT(DISTINCT DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER) > 3) AS REN");
                                        $ren = $ren_data->RecordCount() > 0 ? $ren_data->fields['REN'] : '0';
                                        ?>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold; border-left: 1px solid black; border-bottom: 1px solid black;">PREV</th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important; border-bottom: 1px solid black;"><?=number_format($customer_prev, 2, '.', '')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important; border-bottom: 1px solid black;"><?=number_format($booked_prev, 2, '.', '')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important; border-bottom: 1px solid black; border-right: 1px solid black;"><?=number_format($showed_prev, 2, '.', '')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($upto_three_prev, 2, '.', '')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($above_three_prev, 2, '.', '')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=$group_class_prev." [".$group_class_prev."]"?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=$ren?> Ren(back)</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="table-responsive">
                                <label style="width:100%; text-align: center; font-weight: bold">UNIT SALES TRACKING</label>
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:5%; text-align: center; vertical-align:auto; font-weight: normal !important"></th>
                                        <th style="width:19%; text-align: center; font-weight: bold" colspan="2">Pre Original</th>
                                        <th style="width:19%; text-align: center; font-weight: bold" colspan="2">Original</th>
                                        <th style="width:19%; text-align: center; font-weight: bold" colspan="2">Extension</th>
                                        <th style="width:19%; text-align: center; font-weight: bold" colspan="2">Renewal</th>
                                        <th style="width:19%; text-align: center; font-weight: bold" colspan="2">Total</th>
                                    </tr>
                                    <tr>
                                    </tr>
                                    <?php
                                    $t1_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS T_1 FROM DOA_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                    $t2_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS T_2 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND SESSION_CREATED = SESSION_COMPLETED AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                    $t3_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS T_3 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND SESSION_CREATED = SESSION_COMPLETED AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                    $t4_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS T_4 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND SESSION_CREATED = SESSION_COMPLETED AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                    $t1 = $t1_data->fields['T_1'] > 0 ? $t1_data->fields['T_1'] : 0;
                                    $t2 = $t2_data->fields['T_2'] > 0 ? $t2_data->fields['T_2'] : 0;
                                    $t3 = $t3_data->fields['T_3'] > 0 ? $t3_data->fields['T_3'] : 0;
                                    $t4 = $t4_data->fields['T_4'] > 0 ? $t4_data->fields['T_4'] : 0;
                                    $total_t = $t1 + $t2 + $t3 + $t4;

                                    $s1_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS S_1 FROM DOA_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                    $s2_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS S_2 FROM DOA_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                    $s3_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS S_3 FROM DOA_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                    $s4_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS S_4 FROM DOA_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 4 AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");

                                    $s1 = $s1_data->fields['S_1'] > 0 ? $s1_data->fields['S_1'] : 0;
                                    $s2 = $s2_data->fields['S_2'] > 0 ? $s2_data->fields['S_2'] : 0;
                                    $s3 = $s3_data->fields['S_3'] > 0 ? $s3_data->fields['S_3'] : 0;
                                    $s4 = $s4_data->fields['S_4'] > 0 ? $s4_data->fields['S_4'] : 0;
                                    $total_s = $s1 + $s2 + $s3 + $s4;
                                    ?>
                                        <th style="width:5%; text-align: center; vertical-align:auto; font-weight: bold" rowspan="3">Week</th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$t1?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$s1?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$t2?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$s2?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$t3?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$s3?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$t4?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$s4?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$total_t?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$total_s?></th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $units1_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS_1 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $units2_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS_2 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $units3_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS_3 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $units4_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS_4 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 4 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $units1 = $units1_data->fields['UNITS_1'] > 0 ? $units1_data->fields['UNITS_1'] : 0;
                                        $units2 = $units2_data->fields['UNITS_2'] > 0 ? $units2_data->fields['UNITS_2'] : 0;
                                        $units3 = $units3_data->fields['UNITS_3'] > 0 ? $units3_data->fields['UNITS_3'] : 0;
                                        $units4 = $units4_data->fields['UNITS_4'] > 0 ? $units4_data->fields['UNITS_4'] : 0;
                                        $total_units = $units1 + $units2 + $units3 + $units4;
                                        ?>
                                        <th style="width:18%; text-align: center; vertical-align:auto; font-weight: normal !important" colspan="2">Units: <?=number_format($units1, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($units2, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($units3, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($units4, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($total_units, 2, '.', '')?></th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $amount1_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_1 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $amount2_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_2 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $amount3_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_3 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $amount4_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_4 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 4 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $amount1 = $amount1_data->fields['AMOUNT_1'] > 0 ? $amount1_data->fields['AMOUNT_1'] : 0;
                                        $amount2 = $amount2_data->fields['AMOUNT_2'] > 0 ? $amount2_data->fields['AMOUNT_2'] : 0;
                                        $amount3 = $amount3_data->fields['AMOUNT_3'] > 0 ? $amount3_data->fields['AMOUNT_3'] : 0;
                                        $amount4 = $amount4_data->fields['AMOUNT_4'] > 0 ? $amount4_data->fields['AMOUNT_4'] : 0;
                                        $total_amount = $amount1 + $amount2 + $amount3 + $amount4;
                                        ?>
                                        <th style="width:18%; text-align: center; vertical-align:auto; font-weight: normal !important" colspan="2"><?=number_format($amount1, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($amount2, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($amount3, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($amount4, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($total_amount, 2, '.', '')?></th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $adjust1_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT, COUNT(DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE) AS SERVICE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $adjust2_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT, COUNT(DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE) AS SERVICE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $adjust3_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT, COUNT(DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE) AS SERVICE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $adjust4_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT, COUNT(DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE) AS SERVICE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 4 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $amount1 = $adjust1_data->fields['AMOUNT'] > 0 ? $adjust1_data->fields['AMOUNT'] : 0.00;
                                        $amount2 = $adjust2_data->fields['AMOUNT'] > 0 ? $adjust2_data->fields['AMOUNT'] : 0.00;
                                        $amount3 = $adjust3_data->fields['AMOUNT'] > 0 ? $adjust3_data->fields['AMOUNT'] : 0.00;
                                        $amount4 = $adjust4_data->fields['AMOUNT'] > 0 ? $adjust4_data->fields['AMOUNT'] : 0.00;
                                        $total_amount = $amount1 + $amount2 + $amount3 + $amount4;
                                        $service1 = $adjust1_data->RecordCount() > 0 ? $adjust1_data->fields['SERVICE'] : 0;
                                        $service2 = $adjust2_data->RecordCount() > 0 ? $adjust2_data->fields['SERVICE'] : 0;
                                        $service3 = $adjust3_data->RecordCount() > 0 ? $adjust3_data->fields['SERVICE'] : 0;
                                        $service4 = $adjust4_data->RecordCount() > 0 ? $adjust4_data->fields['SERVICE'] : 0;
                                        $total_service = $service1 + $service2 + $service3 + $service4;
                                        ?>
                                        <th style="width:9%; text-align: center;font-weight: bold">Adjust</th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=$service1."/".$amount1?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=$service2."/".number_format($amount2,2,'.','')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=$service3."/".number_format($amount3,2,'.','')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=$service4."/".number_format($amount4,2,'.','')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=$total_service."/".number_format($total_amount, 2,'.', '')?></th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $t1_net_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS T_1 FROM DOA_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND DATE_ADD(CURDATE(), INTERVAL (7 - DAYOFWEEK(CURDATE())) DAY)");
                                        $t2_net_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS T_2 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND SESSION_CREATED = SESSION_COMPLETED AND CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND DATE_ADD(CURDATE(), INTERVAL (7 - DAYOFWEEK(CURDATE())) DAY)");
                                        $t3_net_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS T_3 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND SESSION_CREATED = SESSION_COMPLETED AND CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND DATE_ADD(CURDATE(), INTERVAL (7 - DAYOFWEEK(CURDATE())) DAY)");
                                        $t4_net_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS T_4 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND SESSION_CREATED = SESSION_COMPLETED AND CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND DATE_ADD(CURDATE(), INTERVAL (7 - DAYOFWEEK(CURDATE())) DAY)");
                                        $t1_net = $t1_net_data->fields['T_1'] > 0 ? $t1_net_data->fields['T_1'] : 0;
                                        $t2_net = $t2_net_data->fields['T_2'] > 0 ? $t2_net_data->fields['T_2'] : 0;
                                        $t3_net = $t3_net_data->fields['T_3'] > 0 ? $t3_net_data->fields['T_3'] : 0;
                                        $t4_net = $t4_net_data->fields['T_4'] > 0 ? $t4_net_data->fields['T_4'] : 0;
                                        $total_t_net = $t1_net + $t2_net + $t3_net + $t4_net;

                                        $s1_net_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS S_1 FROM DOA_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $s2_net_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS S_2 FROM DOA_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $s3_net_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS S_3 FROM DOA_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $s4_net_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER) AS S_4 FROM DOA_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 4 AND CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $s1_net = $s1_net_data->RecordCount() > 0 ? $s1_net_data->fields['S_1'] : 0;
                                        $s2_net = $s2_net_data->RecordCount() > 0 ? $s2_net_data->fields['S_2'] : 0;
                                        $s3_net = $s3_net_data->RecordCount() > 0 ? $s3_net_data->fields['S_3'] : 0;
                                        $s4_net = $s4_net_data->RecordCount() > 0 ? $s4_net_data->fields['S_4'] : 0;
                                        $total_s_net = $s1_net + $s2_net + $s3_net + $s4_net;
                                        ?>
                                        <th style="width:9%; text-align: center; vertical-align:auto; font-weight: bold" rowspan="3">Net<br><br> YTD</th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$t1_net?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$s1_net?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$t2_net?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$s2_net?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$t3_net?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$s3_net?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$t4_net?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$s4_net?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$total_t_net?></th>
                                        <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$total_s_net?></th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $units1_net_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS_1 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $units2_net_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS_2 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $units3_net_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS_3 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $units4_net_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS_4 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 4 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $units1_net = $units1_net_data->fields['UNITS_1'] > 0 ? $units1_net_data->fields['UNITS_1'] : 0;
                                        $units2_net = $units2_net_data->fields['UNITS_2'] > 0 ? $units2_net_data->fields['UNITS_2'] : 0;
                                        $units3_net = $units3_net_data->fields['UNITS_3'] > 0 ? $units3_net_data->fields['UNITS_3'] : 0;
                                        $units4_net = $units4_net_data->fields['UNITS_4'] > 0 ? $units4_net_data->fields['UNITS_4'] : 0;
                                        $total_net_units = $units1_net + $units2_net + $units3_net + $units4_net;
                                        ?>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($units1_net, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($units2_net, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($units3_net, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($units4_net, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($total_net_units, 2, '.', '')?></th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $amount1_net_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_1 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $amount2_net_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_2 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $amount3_net_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_3 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $amount4_net_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_4 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 4 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $amount1_net = $amount1_net_data->fields['AMOUNT_1'] > 0 ? $amount1_net_data->fields['AMOUNT_1'] : 0;
                                        $amount2_net = $amount2_net_data->fields['AMOUNT_2'] > 0 ? $amount2_net_data->fields['AMOUNT_2'] : 0;
                                        $amount3_net = $amount3_net_data->fields['AMOUNT_3'] > 0 ? $amount3_net_data->fields['AMOUNT_3'] : 0;
                                        $amount4_net = $amount4_net_data->fields['AMOUNT_4'] > 0 ? $amount4_net_data->fields['AMOUNT_4'] : 0;
                                        $total_net_amount = $amount1_net + $amount2_net + $amount3_net + $amount4_net;
                                        ?>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($amount1_net, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($amount2_net, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($amount3_net, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($amount4_net, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($total_net_amount, 2, '.', '')?></th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $amount1_prev_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_1 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $amount2_prev_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_2 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $amount3_prev_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_3 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $amount4_prev_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_4 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 4 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $amount1_prev = $amount1_prev_data->fields['AMOUNT_1'] > 0 ? $amount1_prev_data->fields['AMOUNT_1'] : 0;
                                        $amount2_prev = $amount2_prev_data->fields['AMOUNT_2'] > 0 ? $amount2_prev_data->fields['AMOUNT_2'] : 0;
                                        $amount3_prev = $amount3_prev_data->fields['AMOUNT_3'] > 0 ? $amount3_prev_data->fields['AMOUNT_3'] : 0;
                                        $amount4_prev = $amount4_prev_data->fields['AMOUNT_4'] > 0 ? $amount4_prev_data->fields['AMOUNT_4'] : 0;
                                        $total_prev_amount = $amount1_prev + $amount2_prev + $amount3_prev + $amount4_prev;
                                        ?>
                                        <th style="width:9%; text-align: center; font-weight: bold">Prev.</th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($amount1_prev, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($amount2_prev, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($amount3_prev, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($amount4_prev, 2, '.', '')?></th>
                                        <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2"><?=number_format($total_prev_amount, 2, '.', '')?></th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="table-responsive">
                                <label style="width:100%; text-align: center; font-weight: bold">MISCELLANEOUS / FESTIVAL SALES TRACKING</label>
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" rowspan="2"></th>
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" colspan="2">NON-UNIT SALES</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" rowspan="2">SUNDRY</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" rowspan="2">MISCELLANEOUS</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">Private/coach</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Class</th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $week_class_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT, COUNT(DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE) AS SERVICE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_CODE.IS_GROUP = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $week_amount = $week_class_data->fields['AMOUNT'] > 0 ? $week_class_data->fields['AMOUNT'] : 0.00;
                                        $week_service = $week_class_data->RecordCount() > 0 ? $week_class_data->fields['SERVICE'] : 0;

                                        $week_sundry_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT, COUNT(DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE) AS SERVICE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_CODE.IS_SUNDRY = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $week_sundry_amount = $week_sundry_data->fields['AMOUNT'] > 0 ? $week_sundry_data->fields['AMOUNT'] : 0.00;

                                        $week_miscellaneous_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER=DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_MASTER.PK_SERVICE_CLASS = 5 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $week_miscellaneous_amount = $week_miscellaneous_data->fields['AMOUNT'] > 0 ? $week_miscellaneous_data->fields['AMOUNT'] : 0.00;
                                        ?>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">Week</th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($week_service,2,'.','')."/".number_format($week_amount,2,'.','')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($week_sundry_amount,2,'.','')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($week_miscellaneous_amount,2,'.','')?></th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $ytd_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT, COUNT(DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE) AS SERVICE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_CODE.IS_GROUP = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $ytd_amount = $ytd_data->fields['AMOUNT'] > 0 ? $ytd_data->fields['AMOUNT'] : 0.00;
                                        $ytd_service = $ytd_data->RecordCount() > 0 ? $ytd_data->fields['SERVICE'] : 0;

                                        $ytd_sundry_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT, COUNT(DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE) AS SERVICE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_CODE.IS_SUNDRY = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $ytd_sundry_amount = $ytd_sundry_data->fields['AMOUNT'] > 0 ? $ytd_sundry_data->fields['AMOUNT'] : 0.00;

                                        $ytd_miscellaneous_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER=DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_MASTER.PK_SERVICE_CLASS = 5 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $ytd_miscellaneous_amount = $ytd_miscellaneous_data->fields['AMOUNT'] > 0 ? $ytd_miscellaneous_data->fields['AMOUNT'] : 0.00;
                                        ?>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">YTD</th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($ytd_service,2,'.','')."/".number_format($ytd_amount,2,'.','')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($ytd_sundry_amount,2,'.','')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($ytd_miscellaneous_amount,2,'.','')?></th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $prev_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT, COUNT(DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE) AS SERVICE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_CODE.IS_GROUP = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $prev_amount = $prev_data->fields['AMOUNT'] > 0 ? $prev_data->fields['AMOUNT'] : 0.00;
                                        $prev_service = $prev_data->RecordCount() > 0 ? $prev_data->fields['SERVICE'] : 0;

                                        $prev_sundry_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_CODE.IS_SUNDRY = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $prev_sundry_amount = $prev_sundry_data->fields['AMOUNT'] > 0 ? $prev_sundry_data->fields['AMOUNT'] : 0.00;

                                        $prev_miscellaneous_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER=DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_MASTER.PK_SERVICE_CLASS = 5 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($first_day_of_week_previous_year))."' AND '".date('Y-m-d', strtotime($last_day_of_week_previous_year))."'");
                                        $prev_miscellaneous_amount = $prev_miscellaneous_data->fields['AMOUNT'] > 0 ? $prev_miscellaneous_data->fields['AMOUNT'] : 0.00;
                                        ?>
                                        <th style="width:10%; text-align: center; font-weight: bold">Prev.</th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($prev_service,2,'.','')."/".number_format($prev_amount,2,'.','')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($prev_sundry_amount,2,'.','')?></th>
                                        <th style="width:10%; text-align: center; font-weight: normal !important"><?=number_format($prev_miscellaneous_amount,2,'.','')?></th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php');?>

<script>
    // $(function () {
    //     $('#myTable').DataTable({
    //         "columnDefs": [
    //             { "targets": [0,2,5], "searchable": false }
    //         ]
    //     });
    // });
    function ConfirmDelete(anchor)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=anchor.attr("href");
    }
    // function editpage(id, master_id){
    //     window.location.href = "customer.php?id="+id+"&master_id="+master_id;
    //
    // }

</script>

</body>
</html>
