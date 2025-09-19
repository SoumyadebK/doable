<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
error_reporting(0);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "SUMMARY OF STAFF MEMBER REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$week_number = $_GET['week_number'];
$YEAR = date('Y');

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));

$weekly_date_condition = "'" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
$net_year_date_condition = "'" . date('Y', strtotime($to_date)) . "-01-01' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
$prev_year_date_condition = "'" . (date('Y', strtotime($to_date)) - 1) . "-01-01' AND '" . (date('Y', strtotime($to_date)) - 1) . date('-m-d', strtotime($to_date)) . "'";

$appointment_date = "AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";

// Calculate the year and week number of the selected date
$selected_year = date('Y', strtotime($from_date));
$selected_week = date('W', strtotime($from_date));

// Calculate the previous year
$previous_year = $selected_year - 1;

// Find the first day of the selected week in the previous year
$first_day_of_week_previous_year = date('Y-m-d', strtotime($previous_year . 'W' . str_pad($selected_week, 2, '0', STR_PAD_LEFT)));

// Find the last day of the selected week in the previous year
$last_day_of_week_previous_year = date('Y-m-d', strtotime($first_day_of_week_previous_year . ' +6 days'));

$res = $db->Execute("SELECT BUSINESS_NAME, FRANCHISE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$business_name = $res->RecordCount() > 0 ? $res->fields['BUSINESS_NAME'] : '';
if (preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $business_name)) {
    $business_name = '';
} else {
    $business_name = 'Franchisee: ' . $business_name;
}

if ($type === 'export') {
    $access_token = getAccessToken();
    $authorization = "Authorization: Bearer " . $access_token;

    /*$row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);

    $regular_data = $db_account->Execute("SELECT SUM(DISTINCT DOA_ENROLLMENT_PAYMENT.AMOUNT) AS CASH FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER != 5 AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.IS_REFUNDED = 0 AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $regular_cash = $regular_data->fields['CASH'] > 0 ? $regular_data->fields['CASH'] : '0.00';
    $misc_data = $db_account->Execute("SELECT SUM(DISTINCT DOA_ENROLLMENT_PAYMENT.AMOUNT) AS CASH FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = 5 AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.IS_REFUNDED = 0 AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $misc_cash = $misc_data->fields['CASH'] > 0 ? $misc_data->fields['CASH'] : '0.00';

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
    $standing_data = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER) AS GROUP_CLASS FROM `DOA_APPOINTMENT_MASTER` LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER=DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS=2 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND APPOINTMENT_TYPE='GROUP'");
    $group_class = $standing_data->RecordCount() > 0 ? $standing_data->fields['GROUP_CLASS'] : '0';

    $intv_data = $db_account->Execute("SELECT SUM(CUSTOMER_COUNT) AS INTV FROM (SELECT COUNT(DISTINCT PK_USER_MASTER) AS CUSTOMER_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER=DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND APPOINTMENT_TYPE='NORMAL' AND DOA_APPOINTMENT_MASTER.CREATED_ON >= CURDATE() - INTERVAL 30 DAY GROUP BY PK_USER_MASTER HAVING COUNT(DISTINCT DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER) <= 3) AS INTV");
    $intv = $intv_data->fields['INTV'] > 0 ? $intv_data->fields['INTV'] : '0';
    $ren_data = $db_account->Execute("SELECT SUM(CUSTOMER_COUNT) AS REN FROM (SELECT COUNT(DISTINCT PK_USER_MASTER) AS CUSTOMER_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER=DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND APPOINTMENT_TYPE='NORMAL' AND DOA_APPOINTMENT_MASTER.CREATED_ON >= CURDATE() - INTERVAL 30 DAY GROUP BY PK_USER_MASTER HAVING COUNT(DISTINCT DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER) > 3) AS REN");
    $ren = $ren_data->fields['REN'] > 0 ? $ren_data->fields['REN'] : '0';

    $t1_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS T_1 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $t2_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS T_2 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND NUMBER_OF_SESSION = SESSION_COMPLETED AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $t3_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS T_3 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND NUMBER_OF_SESSION = SESSION_COMPLETED AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $t4_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS T_4 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND NUMBER_OF_SESSION = SESSION_COMPLETED AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $t1 = $t1_data->fields['T_1'] > 0 ? $t1_data->fields['T_1'] : 0;
    $t2 = $t2_data->fields['T_2'] > 0 ? $t2_data->fields['T_2'] : 0;
    $t3 = $t3_data->fields['T_3'] > 0 ? $t3_data->fields['T_3'] : 0;
    $t4 = $t4_data->fields['T_4'] > 0 ? $t4_data->fields['T_4'] : 0;

    $s1_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS S_1 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $s2_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS S_2 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $s3_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS S_3 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $s4_data = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS S_4 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER >= 4 AND CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $s1 = $s1_data->fields['S_1'] > 0 ? $s1_data->fields['S_1'] : 0;
    $s2 = $s2_data->fields['S_2'] > 0 ? $s2_data->fields['S_2'] : 0;
    $s3 = $s3_data->fields['S_3'] > 0 ? $s3_data->fields['S_3'] : 0;
    $s4 = $s4_data->fields['S_4'] > 0 ? $s4_data->fields['S_4'] : 0;

    $units1_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS_1 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $units2_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS_2 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $units3_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS_3 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $units4_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS_4 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 4 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $units1 = $units1_data->fields['UNITS_1'] > 0 ? $units1_data->fields['UNITS_1'] : 0;
    $units2 = $units2_data->fields['UNITS_2'] > 0 ? $units2_data->fields['UNITS_2'] : 0;
    $units3 = $units3_data->fields['UNITS_3'] > 0 ? $units3_data->fields['UNITS_3'] : 0;
    $units4 = $units4_data->fields['UNITS_4'] > 0 ? $units4_data->fields['UNITS_4'] : 0;

    $amount1_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_1 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $amount2_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_2 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 2 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $amount3_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_3 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 3 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $amount4_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS AMOUNT_4 FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND CUSTOMER_ENROLLMENT_NUMBER = 4 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $amount1 = $amount1_data->fields['AMOUNT_1'] > 0 ? $amount1_data->fields['AMOUNT_1'] : 0;
    $amount2 = $amount2_data->fields['AMOUNT_2'] > 0 ? $amount2_data->fields['AMOUNT_2'] : 0;
    $amount3 = $amount3_data->fields['AMOUNT_3'] > 0 ? $amount3_data->fields['AMOUNT_3'] : 0;
    $amount4 = $amount4_data->fields['AMOUNT_4'] > 0 ? $amount4_data->fields['AMOUNT_4'] : 0;

    $week_class_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT, SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS SERVICE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."' GROUP BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER HAVING COUNT(DISTINCT DOA_SERVICE_CODE.IS_GROUP) = 1 AND MIN(DOA_SERVICE_CODE.IS_GROUP) = '1' AND MAX(DOA_SERVICE_CODE.IS_GROUP) = '1'");
    $week_amount = 0;
    $week_service = 0;
    while(!$week_class_data->EOF){
        $week_amount += $week_class_data->fields['AMOUNT'] > 0 ? $week_class_data->fields['AMOUNT'] : 0.00;
        $week_service += $week_class_data->RecordCount() > 0 ? $week_class_data->fields['SERVICE'] : 0;
        $week_class_data->MoveNext();
    }

    $week_sundry_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT, SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS SERVICE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_CODE.PK_SERVICE_CODE=DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_CODE.IS_SUNDRY = 1 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $week_sundry_amount = $week_sundry_data->fields['AMOUNT'] > 0 ? $week_sundry_data->fields['AMOUNT'] : 0.00;

    $week_miscellaneous_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER=DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_SERVICE_MASTER.PK_SERVICE_CLASS = 5 AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
    $week_miscellaneous_amount = $week_miscellaneous_data->fields['AMOUNT'] > 0 ? $week_miscellaneous_data->fields['AMOUNT'] : 0.00;

    $data = [
        'type' => 'studio_business',
        'prepared_by' => $row->fields['FIRST_NAME'].' '.$row->fields['LAST_NAME'],
        'week_number' => $week_number,
        'week_year' => $YEAR,
        'week_ending' => $to_date,
        'line_items' => [],
        'cash'=> $regular_cash,
        'miscellaneous'=> $misc_cash,
        'refund_cash' => '',
        'refund_miscellaneous' => '',
        'contact' => $customer,
        'booked' => $booked,
        'showed' => $showed,
        'lessons_interviewed' => $upto_three,
        'lessons_renewed' => $above_three,
        'number_in_class' => $group_class,
        'active_students_interview' => $intv,
        'active_students_renewal' => $ren,
        'pre_original_tried' => $t1,
        'pre_original_sold' => $s1,
        'pre_original_units' => $units1,
        'pre_original_sales' => $amount1,
        'original_tried' => $t2,
        'original_sold' => $s2,
        'original_units' => $units2,
        'original_sales' => $amount2,
        'extension_tried' => $t3,
        'extension_sold' => $s3,
        'extension_units' => $units3,
        'extension_sales' => $amount3,
        'renewal_tried' => $t4,
        'renewal_sold' => $s4,
        'renewal_units' => $units4,
        'renewal_sales' => $amount4,
        'non_unit_private_lessons' => '',
        'non_unit_private_sales' => '',
        'non_unit_class_lessons' => $week_service,
        'non_unit_class_sales' => $week_amount,
        'miscellaneous_sales' => $week_miscellaneous_amount,
        'historical' => null
    ];

    $url = constant('ami_api_url').'/api/v1/reports';
    $post_data = callArturMurrayApi($url, $data, $authorization);*/

    //pre_r(json_decode($post_data));
}
$PK_USER = empty($_GET['PK_USER']) ? 0 : $_GET['PK_USER'];
$selected_service_provider = [];
$selected_service_provider_name = [];
$selected_service_provider_row = $db->Execute("SELECT DISTINCT DOA_USERS.`PK_USER`, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM `DOA_USERS` LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USERS.PK_USER IN (" . $PK_USER . ") AND DOA_USER_ROLES.`PK_ROLES` = 5");
while (!$selected_service_provider_row->EOF) {
    $selected_service_provider[] = $selected_service_provider_row->fields['PK_USER'];
    $selected_service_provider_name[] = $selected_service_provider_row->fields['NAME'];
    $selected_service_provider_row->MoveNext();
}

$row = $db->Execute("SELECT PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS WHERE ACTIVE = 1 AND PK_USER IN (" . implode(',', $selected_service_provider) . ")");
$totalResults = count($selected_service_provider_name);
$concatenatedResults = "";
foreach ($selected_service_provider_name as $key => $result) {
    // Append the current result to the concatenated string
    $concatenatedResults .= $result;

    // If it's not the last result, append a comma
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

$cell1  = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
define('EOL', (PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

$total_fields = 70;
for ($i = 0; $i <= $total_fields; $i++) {
    if ($i <= 25)
        $cell[] = $cell1[$i];
    else {
        $j = floor($i / 26) - 1;
        $k = ($i % 26);
        //echo $j."--".$k."<br />";
        $cell[] = $cell1[$j] . $cell1[$k];
    }
}

$inputFileType  = 'Excel2007';
$outputFileName = 'STAFF_MEMBER_REPORT.xlsx';

$objReader      = PHPExcel_IOFactory::createReader($inputFileType);
$objReader->setIncludeCharts(TRUE);
$objPHPExcel     = new PHPExcel();
$objWriter         = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("F")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("G")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("H")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("I")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("J")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("K")->setWidth(12);

$objPHPExcel->getActiveSheet()->mergeCells('A1:k1');

$cell_no = "A1";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($title);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18); // Set font size to 16
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:F2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($business_name . " (" . $concatenatedResults . ")");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "G2";
$objPHPExcel->getActiveSheet()->mergeCells('G2:K2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('(' . date('m/d/Y', strtotime($from_date)) . " - " . date('m/d/Y', strtotime($to_date)) . ')');
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A2:K2')->applyFromArray($styleArray);

$cell_no = "A4";
$objPHPExcel->getActiveSheet()->mergeCells('A4:k4');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("CASH RECEIPTS");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "A5";
$objPHPExcel->getActiveSheet()->mergeCells('A5:C5');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Period");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(5)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D5";
$objPHPExcel->getActiveSheet()->mergeCells('D5:E5');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Regular");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F5";
$objPHPExcel->getActiveSheet()->mergeCells('F5:H5');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Misc. / NonUnit");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "I5";
$objPHPExcel->getActiveSheet()->mergeCells('I5:K5');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A5:K5')->applyFromArray($styleArray);

$PERIOD = array();
$PERIOD[] = "Week";
$PERIOD[] = "Week Refunds";
$PERIOD[] = "Transfer out";
$PERIOD[] = "NET Y.T.D.";
$PERIOD[] = "PRV. Y.T.D.";

$regular_data = $db_account->Execute("SELECT COALESCE(SUM(p.AMOUNT * (sp.SERVICE_PROVIDER_PERCENTAGE / 100)),0) AS REGULAR_TOTAL FROM DOA_ENROLLMENT_PAYMENT p JOIN DOA_ENROLLMENT_MASTER m ON p.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER JOIN $master_database.DOA_USER_MASTER um ON m.PK_USER_MASTER = um.PK_USER_MASTER JOIN $master_database.DOA_USERS u ON um.PK_USER = u.PK_USER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER sp ON sp.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER WHERE sp.SERVICE_PROVIDER_ID = $PK_USER AND (m.MISC_TYPE IS NULL OR m.MISC_TYPE = '') AND u.IS_DELETED = 0 AND m.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND p.TYPE = 'Payment' AND p.PAYMENT_DATE BETWEEN $weekly_date_condition");
$other_payment_data = $db_account->Execute("SELECT COALESCE(SUM(p.AMOUNT * (sp.SERVICE_PROVIDER_PERCENTAGE/100)),0) AS OTHER_TOTAL FROM DOA_ENROLLMENT_PAYMENT p LEFT JOIN DOA_ENROLLMENT_MASTER m ON p.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER sp ON sp.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER WHERE sp.SERVICE_PROVIDER_ID = $PK_USER AND p.PK_ENROLLMENT_MASTER = 0 AND p.TYPE = 'Payment' AND p.PAYMENT_DATE BETWEEN $weekly_date_condition");
$misc_data = $db_account->Execute("SELECT COALESCE(SUM(p.AMOUNT * (sp.SERVICE_PROVIDER_PERCENTAGE/100)),0) AS MISC_TOTAL FROM DOA_ENROLLMENT_PAYMENT p JOIN DOA_ENROLLMENT_MASTER m ON p.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER JOIN $master_database.DOA_USER_MASTER um ON m.PK_USER_MASTER = um.PK_USER_MASTER JOIN $master_database.DOA_USERS u ON um.PK_USER = u.PK_USER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER sp ON sp.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER WHERE sp.SERVICE_PROVIDER_ID = $PK_USER AND m.MISC_TYPE != '' AND u.IS_DELETED = 0 AND m.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND p.TYPE = 'Payment' AND p.PAYMENT_DATE BETWEEN $weekly_date_condition");

$regular_refund_data = $db_account->Execute("SELECT COALESCE(SUM(p.AMOUNT),0) AS AMOUNT, COALESCE(SUM(p.AMOUNT * (sp.SERVICE_PROVIDER_PERCENTAGE/100)),0) AS REGULAR_REFUND FROM DOA_ENROLLMENT_PAYMENT p JOIN DOA_ENROLLMENT_MASTER m ON p.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER JOIN $master_database.DOA_USER_MASTER um ON m.PK_USER_MASTER = um.PK_USER_MASTER JOIN $master_database.DOA_USERS u ON um.PK_USER = u.PK_USER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER sp ON sp.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER WHERE (m.MISC_TYPE IS NULL OR m.MISC_TYPE = '') AND sp.SERVICE_PROVIDER_ID = $PK_USER AND u.IS_DELETED = 0 AND m.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND p.TYPE = 'Refund' AND p.PAYMENT_DATE BETWEEN $weekly_date_condition");
$misc_refund_data = $db_account->Execute("SELECT COALESCE(SUM(p.AMOUNT),0) AS AMOUNT, COALESCE(SUM(p.AMOUNT * (sp.SERVICE_PROVIDER_PERCENTAGE/100)),0) AS MISC_REFUND FROM DOA_ENROLLMENT_PAYMENT p JOIN DOA_ENROLLMENT_MASTER m ON p.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER JOIN $master_database.DOA_USER_MASTER um ON m.PK_USER_MASTER = um.PK_USER_MASTER JOIN $master_database.DOA_USERS u ON um.PK_USER = u.PK_USER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER sp ON sp.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER WHERE m.MISC_TYPE != '' AND sp.SERVICE_PROVIDER_ID = $PK_USER AND u.IS_DELETED = 0 AND m.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND p.TYPE = 'Refund' AND p.PAYMENT_DATE BETWEEN $weekly_date_condition");

$regular_data_yearly = $db_account->Execute("SELECT COALESCE(SUM(p.AMOUNT * (sp.SERVICE_PROVIDER_PERCENTAGE/100)),0) AS REGULAR_TOTAL FROM DOA_ENROLLMENT_PAYMENT p JOIN DOA_ENROLLMENT_MASTER m ON p.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER JOIN $master_database.DOA_USER_MASTER um ON m.PK_USER_MASTER = um.PK_USER_MASTER JOIN $master_database.DOA_USERS u ON um.PK_USER = u.PK_USER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER sp ON sp.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER WHERE m.PK_ENROLLMENT_TYPE NOT IN (16,17,18) AND sp.SERVICE_PROVIDER_ID = $PK_USER AND u.IS_DELETED = 0 AND m.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND p.TYPE = 'Payment' AND p.PAYMENT_DATE BETWEEN $net_year_date_condition");
$other_payment_data_yearly = $db_account->Execute("SELECT COALESCE(SUM(p.AMOUNT * (sp.SERVICE_PROVIDER_PERCENTAGE/100)),0) AS OTHER_TOTAL FROM DOA_ENROLLMENT_PAYMENT p JOIN DOA_ENROLLMENT_SERVICE_PROVIDER sp ON sp.PK_ENROLLMENT_MASTER = p.PK_ENROLLMENT_MASTER WHERE p.PK_ENROLLMENT_MASTER = 0 AND p.TYPE = 'Payment' AND sp.SERVICE_PROVIDER_ID = $PK_USER AND p.PAYMENT_DATE BETWEEN $net_year_date_condition");
$misc_data_yearly = $db_account->Execute("SELECT COALESCE(SUM(p.AMOUNT * (sp.SERVICE_PROVIDER_PERCENTAGE/100)),0) AS MISC_TOTAL FROM DOA_ENROLLMENT_PAYMENT p JOIN DOA_ENROLLMENT_MASTER m ON p.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER JOIN $master_database.DOA_USER_MASTER um ON m.PK_USER_MASTER = um.PK_USER_MASTER JOIN $master_database.DOA_USERS u ON um.PK_USER = u.PK_USER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER sp ON sp.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER WHERE m.PK_ENROLLMENT_TYPE IN (16,17) AND sp.SERVICE_PROVIDER_ID = $PK_USER AND u.IS_DELETED = 0 AND m.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND p.TYPE = 'Payment' AND p.PAYMENT_DATE BETWEEN $net_year_date_condition");

$regular_data_prev_year = $db_account->Execute("SELECT COALESCE(SUM(p.AMOUNT * (sp.SERVICE_PROVIDER_PERCENTAGE/100)),0) AS REGULAR_TOTAL FROM DOA_ENROLLMENT_PAYMENT p JOIN DOA_ENROLLMENT_MASTER m ON p.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER JOIN $master_database.DOA_USER_MASTER um ON m.PK_USER_MASTER = um.PK_USER_MASTER JOIN $master_database.DOA_USERS u ON um.PK_USER = u.PK_USER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER sp ON sp.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER WHERE (m.PK_ENROLLMENT_TYPE IS NULL OR m.PK_ENROLLMENT_TYPE = '' OR m.PK_ENROLLMENT_TYPE NOT IN (16,17,18)) AND sp.SERVICE_PROVIDER_ID = $PK_USER AND u.IS_DELETED = 0 AND m.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND p.TYPE = 'Payment' AND p.PAYMENT_DATE BETWEEN $prev_year_date_condition");
$other_payment_data_prev_year = $db_account->Execute("SELECT COALESCE(SUM(p.AMOUNT * (sp.SERVICE_PROVIDER_PERCENTAGE/100)),0) AS OTHER_TOTAL FROM DOA_ENROLLMENT_PAYMENT p JOIN DOA_ENROLLMENT_SERVICE_PROVIDER sp ON sp.PK_ENROLLMENT_MASTER = p.PK_ENROLLMENT_MASTER WHERE p.PK_ENROLLMENT_MASTER = 0 AND p.TYPE = 'Payment' AND sp.SERVICE_PROVIDER_ID = $PK_USER AND p.PAYMENT_DATE BETWEEN $prev_year_date_condition");
$misc_data_prev_year = $db_account->Execute("SELECT COALESCE(SUM(p.AMOUNT * (sp.SERVICE_PROVIDER_PERCENTAGE/100)),0) AS MISC_TOTAL FROM DOA_ENROLLMENT_PAYMENT p JOIN DOA_ENROLLMENT_MASTER m ON p.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER JOIN $master_database.DOA_USER_MASTER um ON m.PK_USER_MASTER = um.PK_USER_MASTER JOIN $master_database.DOA_USERS u ON um.PK_USER = u.PK_USER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER sp ON sp.PK_ENROLLMENT_MASTER = m.PK_ENROLLMENT_MASTER WHERE m.PK_ENROLLMENT_TYPE IN (16,17) AND sp.SERVICE_PROVIDER_ID = $PK_USER AND u.IS_DELETED = 0 AND m.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND p.TYPE = 'Payment' AND p.PAYMENT_DATE BETWEEN $prev_year_date_condition");

$REGULAR = array();
$REGULAR[] = "$" . number_format($regular_data->fields['REGULAR_TOTAL'] + $other_payment_data->fields['OTHER_TOTAL'], 2);
$REGULAR[] = ($regular_refund_data->fields['REGULAR_REFUND'] > 0) ? "$-" . number_format($regular_refund_data->fields['REGULAR_REFUND'], 2) : "$0.00";
$REGULAR[] = '$0.00';
$REGULAR[] = "$" . number_format($regular_data_yearly->fields['REGULAR_TOTAL'] + $other_payment_data_yearly->fields['OTHER_TOTAL'], 2);
$REGULAR[] = "$" . number_format($regular_data_prev_year->fields['REGULAR_TOTAL'] + $other_payment_data_prev_year->fields['OTHER_TOTAL'], 2);

$MISC = array();
$MISC[] = "$" . number_format($misc_data->fields['MISC_TOTAL'], 2);
$MISC[] = ($regular_refund_data->fields['MISC_REFUND'] > 0) ? "$-" . number_format($misc_refund_data->fields['MISC_REFUND'], 2) : "$0.00";
$MISC[] = '$0.00';
$MISC[] = "$" . number_format($misc_data_yearly->fields['MISC_TOTAL'], 2);
$MISC[] = "$" . number_format($misc_data_prev_year->fields['MISC_TOTAL'], 2);

$TOTAL = array();
$TOTAL[] = "$" . number_format($regular_data->fields['REGULAR_TOTAL'] + $other_payment_data->fields['OTHER_TOTAL'] + $misc_data->fields['MISC_TOTAL'], 2);
$TOTAL[] = ($regular_refund_data->fields['REGULAR_REFUND'] > 0) ? "$-" . number_format(($regular_refund_data->fields['REGULAR_REFUND'] + $misc_refund_data->fields['MISC_REFUND']), 2) : "$0.00";
$TOTAL[] = '$0.00';
$TOTAL[] = "$" . number_format(($regular_data_yearly->fields['REGULAR_TOTAL'] + $other_payment_data_yearly->fields['OTHER_TOTAL'] + $misc_data_yearly->fields['MISC_TOTAL']) - ($regular_refund_data->fields['REGULAR_REFUND'] + $misc_refund_data->fields['MISC_REFUND']), 2);
$TOTAL[] = "$" . number_format($regular_data_prev_year->fields['REGULAR_TOTAL'] + $other_payment_data_prev_year->fields['OTHER_TOTAL'] + $misc_data_prev_year->fields['MISC_TOTAL'], 2);

$line = 5;
foreach ($PERIOD as $key => $val) {
    $line++;

    $objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

    $objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':C' . $line);

    $cell_no = 'A' . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($val);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $objPHPExcel->getActiveSheet()->mergeCells('D' . $line . ':E' . $line);
    $cell_no = 'D' . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($REGULAR[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

    $objPHPExcel->getActiveSheet()->mergeCells('F' . $line . ':H' . $line);
    $cell_no = 'F' . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($MISC[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

    $objPHPExcel->getActiveSheet()->mergeCells('I' . $line . ':K' . $line);
    $cell_no = 'I' . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($TOTAL[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

    $styleArray = [
        'borders' => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);
}

$line += 2;
$cell_no = "A" . $line;
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':D' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("INQUIRIES");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "E" . $line;
$objPHPExcel->getActiveSheet()->mergeCells('E' . $line . ':G' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("LESSONS TAUGHT | Exchange");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H" . $line;
$line1 = $line + 1;
$objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':K' . $line1);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("ACTIVE STUDENTS");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$line++;

$cell_no = "A" . $line;
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':B' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(35);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "C" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Booked");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Showed");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "E" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Pvt Intv(front)");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Pvt Ren(back)");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "G" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("# in class [incl.core]");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$PERIOD = array();
$PERIOD[] = "Week";
$PERIOD[] = "Y.T.D.";
$PERIOD[] = "PREV";

//weekly
$active_interview_renewal_data = $db_account->Execute("SELECT (SELECT CONCAT(SUM(IF(Active.PK_USER_MASTER IS NOT NULL AND (11th_date IS NULL OR Active.DATE <= 11th_date), 1, 0)), '/', SUM(IF(Active.PK_USER_MASTER IS NOT NULL AND Active.DATE > 11th_date, 1, 0))) FROM (SELECT PK_USER_MASTER, SUM(DOA_SCHEDULING_CODE.UNIT) AS lessons, SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(DOA_APPOINTMENT_MASTER.DATE ORDER BY DOA_APPOINTMENT_MASTER.DATE SEPARATOR ','), ',', 11), ',', -1) AS 11th_date FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE WHERE DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE != 'GROUP' GROUP BY PK_USER_MASTER HAVING lessons > 10) markers RIGHT JOIN (SELECT PK_USER_MASTER, MAX(DOA_APPOINTMENT_MASTER.DATE) AS DATE FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND IS_PAID = 1 AND IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.DATE BETWEEN DATE_ADD('$from_date', INTERVAL -23 DAY) AND DATE_ADD('$from_date', INTERVAL 6 DAY) GROUP BY PK_USER_MASTER) Active USING (PK_USER_MASTER)) AS ACTIVE_INTERVIEW_RENEWAL_COUNT");
$active_interview_renewal_count = empty($active_interview_renewal_data->fields['ACTIVE_INTERVIEW_RENEWAL_COUNT']) ? 0 : explode('/', $active_interview_renewal_data->fields['ACTIVE_INTERVIEW_RENEWAL_COUNT']);

$weekly_interview_renewal_data = $db_account->Execute("SELECT (SELECT CONCAT(SUM(IF(11th_date IS NULL OR WEEK.DATE <= 11th_date, WEEK.total_units, 0)), '/', SUM(IF(WEEK.DATE > 11th_date, WEEK.total_units, 0))) FROM (SELECT * FROM (SELECT PK_USER_MASTER, SUM(DOA_SCHEDULING_CODE.UNIT) AS lessons, SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(DATE ORDER BY DATE SEPARATOR ','), ',', 11), ',', -1) AS 11th_date FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE WHERE IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE != 'GROUP' GROUP BY PK_USER_MASTER) X WHERE lessons > 10) markers RIGHT JOIN (SELECT PK_USER_MASTER, DOA_APPOINTMENT_MASTER.DATE, SUM(DOA_SCHEDULING_CODE.UNIT) AS total_units FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND IS_PAID = 1 AND IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $weekly_date_condition GROUP BY PK_USER_MASTER, DOA_APPOINTMENT_MASTER.DATE) WEEK USING (PK_USER_MASTER)) AS INTERVIEW_RENEWAL_COUNT");
$weekly_interview_renewal_count = empty($weekly_interview_renewal_data->fields['INTERVIEW_RENEWAL_COUNT']) ? 0 : explode('/', $weekly_interview_renewal_data->fields['INTERVIEW_RENEWAL_COUNT']);
$group_data_weekly = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_CUSTOMER) AS GROUP_COUNT FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $weekly_date_condition");

$weekly_customer_data = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USER_ROLES.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.JOINING_DATE BETWEEN $weekly_date_condition");
$weekly_customer_id = [0];
if ($weekly_customer_data->RecordCount() > 0) {
    $weekly_customer_count = $weekly_customer_data->RecordCount();
    while (!$weekly_customer_data->EOF) {
        $weekly_customer_id[] = $weekly_customer_data->fields['PK_USER_MASTER'];
        $weekly_customer_data->MoveNext();
    }
} else {
    $weekly_customer_count = 0;
}
$weekly_booked_data = $db_account->Execute("SELECT COUNT(DISTINCT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER)) AS BOOKED_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_APPOINTMENT_STATUS != 6 AND APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $weekly_date_condition");
$weekly_showed_data = $db_account->Execute("SELECT COUNT(DISTINCT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER)) AS SHOWED_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND IS_CHARGED = 1 AND APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $weekly_date_condition");

//yearly
$yearly_interview_renewal_data = $db_account->Execute("SELECT (SELECT CONCAT(SUM(IF(11th_date IS NULL OR WEEK.DATE <= 11th_date, WEEK.total_units, 0)), '/', SUM(IF(WEEK.DATE > 11th_date, WEEK.total_units, 0))) FROM (SELECT * FROM (SELECT PK_USER_MASTER, SUM(DOA_SCHEDULING_CODE.UNIT) AS lessons, SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(DATE ORDER BY DATE SEPARATOR ','), ',', 11), ',', -1) AS 11th_date FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE WHERE IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE != 'GROUP' GROUP BY PK_USER_MASTER) X WHERE lessons > 10) markers RIGHT JOIN (SELECT PK_USER_MASTER, DOA_APPOINTMENT_MASTER.DATE, SUM(DOA_SCHEDULING_CODE.UNIT) AS total_units FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $net_year_date_condition GROUP BY PK_USER_MASTER, DOA_APPOINTMENT_MASTER.DATE) WEEK USING (PK_USER_MASTER)) AS INTERVIEW_RENEWAL_COUNT");
$yearly_interview_renewal_count = empty($yearly_interview_renewal_data->fields['INTERVIEW_RENEWAL_COUNT']) ? 0 : explode('/', $yearly_interview_renewal_data->fields['INTERVIEW_RENEWAL_COUNT']);
$group_data_yearly = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_CUSTOMER) AS GROUP_COUNT FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $net_year_date_condition");

$yearly_customer_data = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USER_ROLES.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.JOINING_DATE BETWEEN $net_year_date_condition");
$yearly_customer_id = [0];
if ($yearly_customer_data->RecordCount() > 0) {
    $yearly_customer_count = $yearly_customer_data->RecordCount();
    while (!$yearly_customer_data->EOF) {
        $yearly_customer_id[] = $yearly_customer_data->fields['PK_USER_MASTER'];
        $yearly_customer_data->MoveNext();
    }
} else {
    $yearly_customer_count = 0;
}
$yearly_booked_data = $db_account->Execute("SELECT COUNT(DISTINCT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER)) AS BOOKED_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_APPOINTMENT_STATUS != 6 AND APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $net_year_date_condition");
$yearly_showed_data = $db_account->Execute("SELECT COUNT(DISTINCT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER)) AS SHOWED_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND IS_CHARGED = 1 AND APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $net_year_date_condition");

//prev
$prev_year_interview_renewal_data = $db_account->Execute("SELECT (SELECT CONCAT(SUM(IF(11th_date IS NULL OR WEEK.DATE <= 11th_date, WEEK.total_units, 0)), '/', SUM(IF(WEEK.DATE > 11th_date, WEEK.total_units, 0))) FROM (SELECT * FROM (SELECT PK_USER_MASTER, SUM(DOA_SCHEDULING_CODE.UNIT) AS lessons, SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(DATE ORDER BY DATE SEPARATOR ','), ',', 11), ',', -1) AS 11th_date FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE WHERE IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE != 'GROUP' GROUP BY PK_USER_MASTER) X WHERE lessons > 10) markers RIGHT JOIN (SELECT PK_USER_MASTER, DOA_APPOINTMENT_MASTER.DATE, SUM(DOA_SCHEDULING_CODE.UNIT) AS total_units FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $prev_year_date_condition GROUP BY PK_USER_MASTER, DOA_APPOINTMENT_MASTER.DATE) WEEK USING (PK_USER_MASTER)) AS INTERVIEW_RENEWAL_COUNT");
$prev_year_interview_renewal_count = explode('/', $prev_year_interview_renewal_data->fields['INTERVIEW_RENEWAL_COUNT']);
$group_data_prev_year = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_CUSTOMER) AS GROUP_COUNT FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $prev_year_date_condition");

$prev_year_customer_data = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USER_ROLES.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.JOINING_DATE BETWEEN $prev_year_date_condition");
$prev_year_customer_id = [0];
if ($prev_year_customer_data->RecordCount() > 0) {
    $prev_year_customer_count = $prev_year_customer_data->RecordCount();
    while (!$prev_year_customer_data->EOF) {
        $prev_year_customer_id[] = $prev_year_customer_data->fields['PK_USER_MASTER'];
        $prev_year_customer_data->MoveNext();
    }
} else {
    $prev_year_customer_count = 0;
}
$prev_year_booked_data = $db_account->Execute("SELECT COUNT(DISTINCT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER)) AS BOOKED_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_APPOINTMENT_STATUS != 6 AND APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $prev_year_date_condition");
$prev_year_showed_data = $db_account->Execute("SELECT COUNT(DISTINCT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER)) AS SHOWED_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $PK_USER . ") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND IS_CHARGED = 1 AND APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $prev_year_date_condition");

$CONTACT = array();
$CONTACT[] = $weekly_customer_count;
$CONTACT[] = $yearly_customer_count;
$CONTACT[] = $prev_year_customer_count;

$BOOKED = array();
$BOOKED[] = $weekly_booked_data->fields['BOOKED_COUNT'];
$BOOKED[] = $yearly_booked_data->fields['BOOKED_COUNT'];
$BOOKED[] = $prev_year_booked_data->fields['BOOKED_COUNT'];

$SHOWED = array();
$SHOWED[] = $weekly_showed_data->fields['SHOWED_COUNT'];
$SHOWED[] = $yearly_showed_data->fields['SHOWED_COUNT'];
$SHOWED[] = $prev_year_showed_data->fields['SHOWED_COUNT'];

$INTC = array();
$INTC[] = $weekly_interview_renewal_count[0];
$INTC[] = $yearly_interview_renewal_count[0];
$INTC[] = $prev_year_interview_renewal_count[0];

$REN = array();
$REN[] = $weekly_interview_renewal_count[1];
$REN[] = $yearly_interview_renewal_count[1];
$REN[] = $prev_year_interview_renewal_count[1];

$IN_CLASS = array();
$IN_CLASS[] = $group_data_weekly->fields['GROUP_COUNT'] . " [" . $group_data_weekly->fields['GROUP_COUNT'] . "]";
$IN_CLASS[] = $group_data_yearly->fields['GROUP_COUNT'] . " [" . $group_data_yearly->fields['GROUP_COUNT'] . "]";
$IN_CLASS[] = $group_data_prev_year->fields['GROUP_COUNT'] . " [" . $group_data_prev_year->fields['GROUP_COUNT'] . "]";

$STUD = array();
$STUD[] = "Department";
$STUD[] = $active_interview_renewal_count[0] . "Intv(front)";
$STUD[] = $active_interview_renewal_count[1] . "Ren(back)";

foreach ($PERIOD as $key => $val) {
    $line++;

    $objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

    $cell_no = 'A' . $line;
    $objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':B' . $line);
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($val);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'C' . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($BOOKED[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'D' . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($SHOWED[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'E' . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($INTC[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'F' . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($REN[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'G' . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($IN_CLASS[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'H' . $line;
    $objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':K' . $line);
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($STUD[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $styleArray = [
        'borders' => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);
}

$line++;
$line++;
$cell_no = "A" . $line;
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':k' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("UNIT SALES TRACKING");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(4)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


$line++;
$cell_no = "A" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("");
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(35);

$cell_no = "B" . $line;
$objPHPExcel->getActiveSheet()->mergeCells('B' . $line . ':C' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Pre Original");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D" . $line;
$objPHPExcel->getActiveSheet()->mergeCells('D' . $line . ':E' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Original");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F" . $line;
$objPHPExcel->getActiveSheet()->mergeCells('F' . $line . ':G' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Extension");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H" . $line;
$objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':I' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Renewal");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "J" . $line;
$objPHPExcel->getActiveSheet()->mergeCells('J' . $line . ':K' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$line++;
$cell_no = "A" . $line;
$line_1 = $line + 2;
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':A' . $line_1);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Week");
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$weekly_pre_original_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$weekly_pre_original_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$weekly_pre_original_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$weekly_pre_original_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");

$weekly_original_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$weekly_original_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$weekly_original_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$weekly_original_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");

$weekly_extension_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$weekly_extension_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$weekly_extension_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$weekly_extension_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");

$weekly_renewal_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$weekly_renewal_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$weekly_renewal_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$weekly_renewal_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");

$cell_no = 'B' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : " . $weekly_pre_original_tried->fields['TRIED']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'C' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : " . $weekly_pre_original_sold->fields['SOLD']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'D' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : " . $weekly_original_tried->fields['TRIED']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'E' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : " . $weekly_original_sold->fields['SOLD']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'F' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : " . $weekly_extension_tried->fields['TRIED']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'G' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : " . $weekly_extension_sold->fields['SOLD']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'H' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : " . $weekly_renewal_tried->fields['TRIED']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'I' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : " . $weekly_renewal_sold->fields['SOLD']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'J' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : " . ($weekly_pre_original_tried->fields['TRIED'] + $weekly_original_tried->fields['TRIED'] + $weekly_extension_tried->fields['TRIED'] + $weekly_renewal_tried->fields['TRIED']));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'K' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : " . ($weekly_pre_original_sold->fields['SOLD'] + $weekly_original_sold->fields['SOLD'] + $weekly_extension_sold->fields['SOLD'] + $weekly_renewal_sold->fields['SOLD']));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = 'B' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('B' . $line . ':C' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: " . number_format($weekly_pre_original_units->fields['UNITS'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'D' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('D' . $line . ':E' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: " . number_format($weekly_original_units->fields['UNITS'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'F' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('F' . $line . ':G' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: " . number_format($weekly_extension_units->fields['UNITS'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'H' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':I' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: " . number_format($weekly_renewal_units->fields['UNITS'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'J' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('J' . $line . ':K' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: " . number_format($weekly_pre_original_units->fields['UNITS'] + $weekly_original_units->fields['UNITS'] + $weekly_extension_units->fields['UNITS'] + $weekly_renewal_units->fields['UNITS'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = 'B' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('B' . $line . ':C' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($weekly_pre_original_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'D' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('D' . $line . ':E' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($weekly_original_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'F' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('F' . $line . ':G' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($weekly_extension_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'H' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':I' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($weekly_renewal_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'J' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('J' . $line . ':K' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($weekly_pre_original_sales->fields['SALES'] + $weekly_original_sales->fields['SALES'] + $weekly_extension_sales->fields['SALES'] + $weekly_renewal_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = 'A' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Adjust");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'B' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('B' . $line . ':C' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'D' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('D' . $line . ':E' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'F' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('F' . $line . ':G' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'H' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':I' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'J' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('J' . $line . ':K' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = "A" . $line;
$line_1 = $line + 2;
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':A' . $line_1);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Net YTD");
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$yearly_pre_original_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$yearly_pre_original_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$yearly_pre_original_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$yearly_pre_original_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");

$yearly_original_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$yearly_original_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$yearly_original_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$yearly_original_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");

$yearly_extension_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$yearly_extension_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$yearly_extension_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$yearly_extension_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");

$yearly_renewal_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$yearly_renewal_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$yearly_renewal_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$yearly_renewal_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");

$cell_no = 'B' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : " . $yearly_pre_original_tried->fields['TRIED']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'C' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : " . $yearly_pre_original_sold->fields['SOLD']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'D' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : " . $yearly_original_tried->fields['TRIED']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'E' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : " . $yearly_original_sold->fields['SOLD']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'F' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : " . $yearly_extension_tried->fields['TRIED']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'G' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : " . $yearly_extension_sold->fields['SOLD']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'H' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : " . $yearly_renewal_tried->fields['TRIED']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'I' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : " . $yearly_renewal_sold->fields['SOLD']);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'J' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : " . ($yearly_pre_original_tried->fields['TRIED'] + $yearly_original_tried->fields['TRIED'] + $yearly_extension_tried->fields['TRIED'] + $yearly_renewal_tried->fields['TRIED']));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'K' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : " . ($yearly_pre_original_sold->fields['SOLD'] + $yearly_original_sold->fields['SOLD'] + $yearly_extension_sold->fields['SOLD'] + $yearly_renewal_sold->fields['SOLD']));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = 'B' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('B' . $line . ':C' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: " . number_format($yearly_pre_original_units->fields['UNITS'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'D' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('D' . $line . ':E' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: " . number_format($yearly_original_units->fields['UNITS'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'F' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('F' . $line . ':G' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: " . number_format($yearly_extension_units->fields['UNITS'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'H' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':I' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: " . number_format($yearly_renewal_units->fields['UNITS'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'J' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('J' . $line . ':K' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: " . number_format($yearly_pre_original_units->fields['UNITS'] + $yearly_original_units->fields['UNITS'] + $yearly_extension_units->fields['UNITS'] + $yearly_renewal_units->fields['UNITS'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = 'B' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('B' . $line . ':C' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($yearly_pre_original_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'D' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('D' . $line . ':E' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($yearly_original_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'F' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('F' . $line . ':G' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($yearly_extension_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'H' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':I' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($yearly_renewal_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'J' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('J' . $line . ':K' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($yearly_pre_original_sales->fields['SALES'] + $yearly_original_sales->fields['SALES'] + $yearly_extension_sales->fields['SALES'] + $yearly_renewal_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = 'A' . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Prev");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$prev_year_pre_original_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $prev_year_date_condition");
$prev_year_original_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $prev_year_date_condition");
$prev_year_extension_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $prev_year_date_condition");
$prev_year_renewal_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $prev_year_date_condition");

$cell_no = 'B' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('B' . $line . ':C' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($prev_year_pre_original_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'D' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('D' . $line . ':E' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($prev_year_original_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'F' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('F' . $line . ':G' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($prev_year_extension_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'H' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':I' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($prev_year_renewal_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'J' . $line;
$objPHPExcel->getActiveSheet()->mergeCells('J' . $line . ':K' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . number_format($prev_year_pre_original_sales->fields['SALES'] + $prev_year_original_sales->fields['SALES'] + $prev_year_extension_sales->fields['SALES'] + $prev_year_renewal_sales->fields['SALES'], 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$line++;
$line++;
$cell_no = "A" . $line;
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':k' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("MISCELLANEOUS / FESTIVAL SALES TRACKING");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


$line++;
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = "A" . $line;
$line1 = $line + 1;
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':C' . $line1);

$cell_no = "D" . $line;
$objPHPExcel->getActiveSheet()->mergeCells('D' . $line . ':E' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("NON-UNIT SALES");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F" . $line;
$line1 = $line + 1;
$objPHPExcel->getActiveSheet()->mergeCells('F' . $line . ':H' . $line1);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("SUNDRY");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "I" . $line;
$line1 = $line + 1;
$objPHPExcel->getActiveSheet()->mergeCells('I' . $line . ':K' . $line1);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("MISCELLANEOUS");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$line++;
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = "D" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Private/coach");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "E" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Class");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);

$PERIOD = array();
$PERIOD[] = "Prev.";
$PERIOD[] = "Y.T.D.";
$PERIOD[] = "Prev.";

$PRIVATE = array();
$PRIVATE[] = "";
$PRIVATE[] = "";
$PRIVATE[] = "";

$CLASS = array();
$CLASS[] = "";
$CLASS[] = "";
$CLASS[] = "";

$SUNDRY = array();
$SUNDRY[] = "$0.00";
$SUNDRY[] = "$0.000";
$SUNDRY[] = "$0.000";

$weekly_misc_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 16 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
$yearly_misc_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 16 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
$prev_year_misc_sales = $db_account->Execute("SELECT SUM(FINAL_AMOUNT * (DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE/100)) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $PK_USER . ") AND PK_ENROLLMENT_TYPE = 16 AND ENROLLMENT_DATE BETWEEN $prev_year_date_condition");

$MISCELLANEOUS = array();
$MISCELLANEOUS[] = "$" . number_format($weekly_misc_sales->fields['SALES'], 2);
$MISCELLANEOUS[] = "$" . number_format($yearly_misc_sales->fields['SALES'], 2);
$MISCELLANEOUS[] = "$" . number_format($prev_year_misc_sales->fields['SALES'], 2);

foreach ($PERIOD as $key => $val) {
    $line++;

    $objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

    $cell_no = 'A' . $line;
    $objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':C' . $line);
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($val);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'D' . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($PRIVATE[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'E' . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($CLASS[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'F' . $line;
    $objPHPExcel->getActiveSheet()->mergeCells('F' . $line . ':H' . $line);
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($SUNDRY[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

    $cell_no = 'I' . $line;
    $objPHPExcel->getActiveSheet()->mergeCells('I' . $line . ':K' . $line);
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($MISCELLANEOUS[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

    $styleArray = [
        'borders' => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $objPHPExcel->getActiveSheet()->getStyle('A' . $line . ':K' . $line)->applyFromArray($styleArray);
}


$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
header("location:" . $outputFileName);
