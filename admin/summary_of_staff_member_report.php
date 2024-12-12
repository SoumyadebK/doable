<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "SUMMARY OF STAFF MEMBER REPORT";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

if (!empty($_GET['week_number'])){
    $week_number = $_GET['week_number'];
    $YEAR = date('Y');
    $dto = new DateTime();
    $dto->setISODate($YEAR, $week_number);
    $from_date = $dto->modify('-1 day')->format('Y-m-d');
    $dto->modify('+6 days');
    $to_date = $dto->format('Y-m-d');

    $weekly_date_condition = "'".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";
    $net_year_date_condition = "'".date('Y', strtotime($to_date))."-01-01' AND '".date('Y-m-d', strtotime($to_date))."'";
    $prev_year_date_condition = "'".(date('Y', strtotime($to_date))-1)."-01-01' AND '".(date('Y', strtotime($to_date))-1).date('-m-d', strtotime($to_date))."'";

    $appointment_date = "AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";
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

$res = $db->Execute("SELECT BUSINESS_NAME, FRANCHISE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$business_name = $res->RecordCount() > 0 ? $res->fields['BUSINESS_NAME'] : '';

if ($type === 'export') {
    $access_token = getAccessToken();
    $authorization = "Authorization: Bearer ".$access_token;

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
$selected_service_provider_row = $db->Execute("SELECT DISTINCT DOA_USERS.`PK_USER`, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM `DOA_USERS` LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USERS.PK_USER IN (".$PK_USER.") AND DOA_USER_ROLES.`PK_ROLES` = 5");
while (!$selected_service_provider_row->EOF) {
    $selected_service_provider[] = $selected_service_provider_row->fields['PK_USER'];
    $selected_service_provider_name[] = $selected_service_provider_row->fields['NAME'];
    $selected_service_provider_row->MoveNext();
}
$row = $db->Execute("SELECT PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS WHERE ACTIVE = 1 AND PK_USER IN (".implode(',', $selected_service_provider).")");
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
                <div class="col-md-2 align-self-center">
                    <div id="location" class="multiselect-box">
                        <select class="multi_select_service_provider" onchange="selectDefaultServiceProvider(this);" multiple>
                            <?php
                                $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USERS.ACTIVE = 1 AND DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_USER'];?>" <?=in_array($row->fields['PK_USER'], $selected_service_provider)?"selected":""?>><?=$row->fields['NAME']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-5 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                            <li class="breadcrumb-item active"><a href="summary_of_staff_member_report.php"><?=$title?></a></li>
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
                                    <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?=$title?></h3>
                                </div>
                                <div class="table-responsive">
                                    <table id="myTable" class="table table-bordered" data-page-length='50'>
                                        <thead>
                                        <tr>
                                            <th style="width:40%; text-align: center; vertical-align:auto; font-weight: bold"><?=$business_name." (".$concatenatedResults.")"?></th>
                                            <th style="width:20%; text-align: center; font-weight: bold">(<?=date('m/d/Y', strtotime($from_date))?> - <?=date('m/d/Y', strtotime($to_date))?>)</th>
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
                                            $regular_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS REGULAR_TOTAL FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND (DOA_ENROLLMENT_MASTER.MISC_TYPE = NULL || DOA_ENROLLMENT_MASTER.MISC_TYPE = '') AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE != 7 AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $weekly_date_condition");
                                            $other_payment_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS OTHER_TOTAL FROM DOA_ENROLLMENT_PAYMENT WHERE PK_ENROLLMENT_MASTER = 0 AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $weekly_date_condition");
                                            $misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND DOA_ENROLLMENT_MASTER.MISC_TYPE != '' AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE != 7 AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $weekly_date_condition");
                                            ?>
                                            <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Week</th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important;">$<?=number_format($regular_data->fields['REGULAR_TOTAL']+$other_payment_data->fields['OTHER_TOTAL'],2)?></th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important">$<?=number_format($misc_data->fields['MISC_TOTAL'],2)?></th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important"><b>$<?=number_format($regular_data->fields['REGULAR_TOTAL']+$other_payment_data->fields['OTHER_TOTAL']+$misc_data->fields['MISC_TOTAL'], 2)?></b></th>
                                        </tr>
                                        <tr>
                                            <?php
                                            $regular_refund_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS REGULAR_REFUND FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.MISC_TYPE = NULL || DOA_ENROLLMENT_MASTER.MISC_TYPE = '') AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $weekly_date_condition");
                                            $misc_refund_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS MISC_REFUND FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.MISC_TYPE != '' AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $weekly_date_condition");
                                            ?>
                                            <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Week Refunds</th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important;">$<?=($regular_refund_data->fields['REGULAR_REFUND'] > 0) ? '-' : ''?><?=number_format($regular_refund_data->fields['REGULAR_REFUND'],2)?></th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important">$<?=($regular_refund_data->fields['REGULAR_REFUND'] > 0) ? '-' : ''?><?=number_format($misc_refund_data->fields['MISC_REFUND'],2)?></th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important">$<?=($regular_refund_data->fields['REGULAR_REFUND'] > 0) ? '-' : ''?><?=number_format($regular_refund_data->fields['REGULAR_REFUND']+$misc_refund_data->fields['MISC_REFUND'], 2)?></th>
                                        </tr>
                                        <tr>
                                            <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Transfer out</th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important">0.00</th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important">0.00</th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important">0.00</th>
                                        </tr>
                                        <tr>
                                            <?php
                                            $regular_data_yearly = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS REGULAR_TOTAL FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE NOT IN (16,17,18) AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $net_year_date_condition");
                                            $other_payment_data_yearly = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS OTHER_TOTAL FROM DOA_ENROLLMENT_PAYMENT WHERE PK_ENROLLMENT_MASTER = 0 AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $net_year_date_condition");
                                            $misc_data_yearly = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE IN (16,17) AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $net_year_date_condition");
                                            ?>
                                            <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">NET Y.T.D.</th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important;">$<?=number_format($regular_data_yearly->fields['REGULAR_TOTAL']+$other_payment_data_yearly->fields['OTHER_TOTAL'],2)?></th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important">$<?=number_format($misc_data_yearly->fields['MISC_TOTAL'],2)?></th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important"><b>$<?=number_format($regular_data_yearly->fields['REGULAR_TOTAL']+$other_payment_data_yearly->fields['OTHER_TOTAL']+$misc_data_yearly->fields['MISC_TOTAL'], 2)?></b></th>
                                        </tr>
                                        <tr>
                                            <?php
                                            $regular_data_prev_year = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS REGULAR_TOTAL FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE IS NULL OR DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = '' OR DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE NOT IN (16,17,18)) AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $prev_year_date_condition");
                                            $other_payment_data_prev_year = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS OTHER_TOTAL FROM DOA_ENROLLMENT_PAYMENT WHERE PK_ENROLLMENT_MASTER = 0 AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $prev_year_date_condition");
                                            $misc_data_prev_year = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE IN (16,17) AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $prev_year_date_condition");
                                            ?>
                                            <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">PRV. Y.T.D.</th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important;">$<?=number_format($regular_data_prev_year->fields['REGULAR_TOTAL']+$other_payment_data_prev_year->fields['OTHER_TOTAL'],2)?></th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important">$<?=number_format($misc_data_prev_year->fields['MISC_TOTAL'],2)?></th>
                                            <th style="width:25%; text-align: center; font-weight: normal !important"><b>$<?=number_format($regular_data_prev_year->fields['REGULAR_TOTAL']+$other_payment_data_prev_year->fields['OTHER_TOTAL']+$misc_data_prev_year->fields['MISC_TOTAL'], 2)?></b></th>
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
                                            $active_interview_renewal_data = $db_account->Execute("SELECT (SELECT CONCAT(SUM(IF(Active.PK_USER_MASTER IS NOT NULL AND (11th_date IS NULL OR Active.DATE <= 11th_date), 1, 0)), '/', SUM(IF(Active.PK_USER_MASTER IS NOT NULL AND Active.DATE > 11th_date, 1, 0))) FROM (SELECT PK_USER_MASTER, SUM(DOA_SCHEDULING_CODE.UNIT) AS lessons, SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(DOA_APPOINTMENT_MASTER.DATE ORDER BY DOA_APPOINTMENT_MASTER.DATE SEPARATOR ','), ',', 11), ',', -1) AS 11th_date FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE WHERE DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE != 'GROUP' GROUP BY PK_USER_MASTER HAVING lessons > 10) markers RIGHT JOIN (SELECT PK_USER_MASTER, MAX(DOA_APPOINTMENT_MASTER.DATE) AS DATE FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$PK_USER.") AND IS_PAID = 1 AND IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.DATE BETWEEN DATE_ADD('$from_date', INTERVAL -23 DAY) AND DATE_ADD('$from_date', INTERVAL 6 DAY) GROUP BY PK_USER_MASTER) Active USING (PK_USER_MASTER)) AS ACTIVE_INTERVIEW_RENEWAL_COUNT");
                                            $active_interview_renewal_count = empty($active_interview_renewal_data->fields['ACTIVE_INTERVIEW_RENEWAL_COUNT']) ? 0 : explode('/', $active_interview_renewal_data->fields['ACTIVE_INTERVIEW_RENEWAL_COUNT']);

                                            $weekly_interview_renewal_data = $db_account->Execute("SELECT (SELECT CONCAT(SUM(IF(11th_date IS NULL OR WEEK.DATE <= 11th_date, WEEK.total_units, 0)), '/', SUM(IF(WEEK.DATE > 11th_date, WEEK.total_units, 0))) FROM (SELECT * FROM (SELECT PK_USER_MASTER, SUM(DOA_SCHEDULING_CODE.UNIT) AS lessons, SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(DATE ORDER BY DATE SEPARATOR ','), ',', 11), ',', -1) AS 11th_date FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE WHERE IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE != 'GROUP' GROUP BY PK_USER_MASTER) X WHERE lessons > 10) markers RIGHT JOIN (SELECT PK_USER_MASTER, DOA_APPOINTMENT_MASTER.DATE, SUM(DOA_SCHEDULING_CODE.UNIT) AS total_units FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$PK_USER.") AND IS_PAID = 1 AND IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $weekly_date_condition GROUP BY PK_USER_MASTER, DOA_APPOINTMENT_MASTER.DATE) WEEK USING (PK_USER_MASTER)) AS INTERVIEW_RENEWAL_COUNT");
                                            $weekly_interview_renewal_count = empty($weekly_interview_renewal_data->fields['INTERVIEW_RENEWAL_COUNT']) ? 0 : explode('/', $weekly_interview_renewal_data->fields['INTERVIEW_RENEWAL_COUNT']);
                                            $group_data_weekly = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_CUSTOMER) AS GROUP_COUNT FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$PK_USER.") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $weekly_date_condition");

                                            $weekly_customer_data = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USER_ROLES.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.JOINING_DATE BETWEEN $weekly_date_condition");
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
                                            $weekly_booked_data = $db_account->Execute("SELECT COUNT(DISTINCT(PK_USER_MASTER)) AS BOOKED_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$PK_USER.") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER IN (".implode(',', $weekly_customer_id).")");
                                            $weekly_showed_data = $db_account->Execute("SELECT IFNULL(count(*),0) AS SHOWED_COUNT FROM (SELECT PK_USER_MASTER, MIN(DOA_APPOINTMENT_MASTER.DATE) AS FIRST_APPT FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$PK_USER.") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 2 GROUP BY PK_USER_MASTER) A WHERE FIRST_APPT BETWEEN $weekly_date_condition");
                                            ?>
                                            <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold; border-left: 1px solid black;">Week</th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$weekly_customer_count?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$weekly_booked_data->fields['BOOKED_COUNT']?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important; border-right: 1px solid black;"><?=$weekly_showed_data->fields['SHOWED_COUNT']?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$weekly_interview_renewal_count[0]?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$weekly_interview_renewal_count[1]?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$group_data_weekly->fields['GROUP_COUNT']." [".$group_data_weekly->fields['GROUP_COUNT']."]"?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold">Department</th>
                                        </tr>
                                        <tr>
                                            <?php
                                            $yearly_interview_renewal_data = $db_account->Execute("SELECT (SELECT CONCAT(SUM(IF(11th_date IS NULL OR WEEK.DATE <= 11th_date, WEEK.total_units, 0)), '/', SUM(IF(WEEK.DATE > 11th_date, WEEK.total_units, 0))) FROM (SELECT * FROM (SELECT PK_USER_MASTER, SUM(DOA_SCHEDULING_CODE.UNIT) AS lessons, SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(DATE ORDER BY DATE SEPARATOR ','), ',', 11), ',', -1) AS 11th_date FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE WHERE IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE != 'GROUP' GROUP BY PK_USER_MASTER) X WHERE lessons > 10) markers RIGHT JOIN (SELECT PK_USER_MASTER, DOA_APPOINTMENT_MASTER.DATE, SUM(DOA_SCHEDULING_CODE.UNIT) AS total_units FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$PK_USER.") AND IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $net_year_date_condition GROUP BY PK_USER_MASTER, DOA_APPOINTMENT_MASTER.DATE) WEEK USING (PK_USER_MASTER)) AS INTERVIEW_RENEWAL_COUNT");
                                            $yearly_interview_renewal_count = empty($yearly_interview_renewal_data->fields['INTERVIEW_RENEWAL_COUNT']) ? 0 : explode('/', $yearly_interview_renewal_data->fields['INTERVIEW_RENEWAL_COUNT']);
                                            $group_data_yearly = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_CUSTOMER) AS GROUP_COUNT FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$PK_USER.") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $net_year_date_condition");

                                            $yearly_customer_data = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USER_ROLES.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.JOINING_DATE BETWEEN $net_year_date_condition");
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
                                            $yearly_booked_data = $db_account->Execute("SELECT COUNT(DISTINCT(PK_USER_MASTER)) AS BOOKED_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$PK_USER.") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER IN (".implode(',', $yearly_customer_id).")");
                                            $yearly_showed_data = $db_account->Execute("SELECT IFNULL(count(*),0) AS SHOWED_COUNT FROM (SELECT PK_USER_MASTER, MIN(DOA_APPOINTMENT_MASTER.DATE) AS FIRST_APPT FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$PK_USER.") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 2 GROUP BY PK_USER_MASTER) A WHERE FIRST_APPT BETWEEN $net_year_date_condition");
                                            ?>
                                            <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold; border-left: 1px solid black;">YTD</th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$yearly_customer_count?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$yearly_booked_data->fields['BOOKED_COUNT']?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important; border-right: 1px solid black;"><?=$yearly_showed_data->fields['SHOWED_COUNT']?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$yearly_interview_renewal_count[0]?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$yearly_interview_renewal_count[1]?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$group_data_yearly->fields['GROUP_COUNT']." [".$group_data_yearly->fields['GROUP_COUNT']."]"?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$active_interview_renewal_count[0]?> Intv(front)</th>
                                        </tr>
                                        <tr>
                                            <?php
                                            $prev_year_interview_renewal_data = $db_account->Execute("SELECT (SELECT CONCAT(SUM(IF(11th_date IS NULL OR WEEK.DATE <= 11th_date, WEEK.total_units, 0)), '/', SUM(IF(WEEK.DATE > 11th_date, WEEK.total_units, 0))) FROM (SELECT * FROM (SELECT PK_USER_MASTER, SUM(DOA_SCHEDULING_CODE.UNIT) AS lessons, SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(DATE ORDER BY DATE SEPARATOR ','), ',', 11), ',', -1) AS 11th_date FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE WHERE IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE != 'GROUP' GROUP BY PK_USER_MASTER) X WHERE lessons > 10) markers RIGHT JOIN (SELECT PK_USER_MASTER, DOA_APPOINTMENT_MASTER.DATE, SUM(DOA_SCHEDULING_CODE.UNIT) AS total_units FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $prev_year_date_condition GROUP BY PK_USER_MASTER, DOA_APPOINTMENT_MASTER.DATE) WEEK USING (PK_USER_MASTER)) AS INTERVIEW_RENEWAL_COUNT");
                                            $prev_year_interview_renewal_count = explode('/', $prev_year_interview_renewal_data->fields['INTERVIEW_RENEWAL_COUNT']);
                                            $group_data_prev_year = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_CUSTOMER) AS GROUP_COUNT FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$PK_USER.") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_PAID = 1 AND DOA_APPOINTMENT_MASTER.DATE BETWEEN $prev_year_date_condition");

                                            $prev_year_customer_data = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USER_ROLES.PK_USER = DOA_USERS.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.JOINING_DATE BETWEEN $prev_year_date_condition");
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
                                            $prev_year_booked_data = $db_account->Execute("SELECT COUNT(DISTINCT(DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER)) AS BOOKED_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$PK_USER.") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER IN (".implode(',', $prev_year_customer_id).")");
                                            $prev_year_showed_data = $db_account->Execute("SELECT IFNULL(count(*),0) AS SHOWED_COUNT FROM (SELECT PK_USER_MASTER, MIN(DOA_APPOINTMENT_MASTER.DATE) AS FIRST_APPT FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$PK_USER.") AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 2 GROUP BY PK_USER_MASTER) A WHERE FIRST_APPT BETWEEN $prev_year_date_condition");
                                            ?>
                                            <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold; border-left: 1px solid black; border-bottom: 1px solid black;">PREV</th>
                                            <th style="width:10%; text-align: center; border-bottom: 1px solid black; font-weight: normal !important"><?=$prev_year_customer_count?></th>
                                            <th style="width:10%; text-align: center; border-bottom: 1px solid black; font-weight: normal !important"><?=$prev_year_booked_data->fields['BOOKED_COUNT']?></th>
                                            <th style="width:10%; text-align: center; border-bottom: 1px solid black; font-weight: normal !important; border-right: 1px solid black;"><?=$prev_year_showed_data->fields['SHOWED_COUNT']?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$prev_year_interview_renewal_count[0]?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$prev_year_interview_renewal_count[1]?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$group_data_prev_year->fields['GROUP_COUNT']." [".$group_data_prev_year->fields['GROUP_COUNT']."]"?></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"><?=$active_interview_renewal_count[1]?> Ren(back)</th>
                                        </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="table-responsive">
                                    <?php if($res->fields['FRANCHISE']==1){?>
                                        <label style="width:100%; text-align: center; font-weight: bold">UNIT SALES TRACKING</label>
                                    <?php }else{ ?>
                                        <label style="width:100%; text-align: center; font-weight: bold">SALES TRACKING</label>
                                    <?php } ?>
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
                                        <?php
                                        $weekly_pre_original_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        $weekly_pre_original_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        $weekly_pre_original_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        $weekly_pre_original_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");

                                        $weekly_original_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        $weekly_original_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        $weekly_original_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        $weekly_original_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");

                                        $weekly_extension_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        $weekly_extension_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        $weekly_extension_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        $weekly_extension_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");

                                        $weekly_renewal_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        $weekly_renewal_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        $weekly_renewal_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        $weekly_renewal_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                        ?>
                                        <tr>
                                            <th style="width:5%; text-align: center; vertical-align:center; font-weight: bold" rowspan="3">Week</th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$weekly_pre_original_tried->fields['TRIED']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$weekly_pre_original_sold->fields['SOLD']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$weekly_original_tried->fields['TRIED']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$weekly_original_sold->fields['SOLD']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$weekly_extension_tried->fields['TRIED']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$weekly_extension_sold->fields['SOLD']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$weekly_renewal_tried->fields['TRIED']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$weekly_renewal_sold->fields['SOLD']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$weekly_pre_original_tried->fields['TRIED']+$weekly_original_tried->fields['TRIED']+$weekly_extension_tried->fields['TRIED']+$weekly_renewal_tried->fields['TRIED']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$weekly_pre_original_sold->fields['SOLD']+$weekly_original_sold->fields['SOLD']+$weekly_extension_sold->fields['SOLD']+$weekly_renewal_sold->fields['SOLD']?></th>
                                        </tr>
                                        <tr>
                                            <th style="width:18%; text-align: center; vertical-align:auto; font-weight: normal !important" colspan="2">Units: <?=number_format($weekly_pre_original_units->fields['UNITS'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($weekly_original_units->fields['UNITS'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($weekly_extension_units->fields['UNITS'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($weekly_renewal_units->fields['UNITS'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($weekly_pre_original_units->fields['UNITS']+$weekly_original_units->fields['UNITS']+$weekly_extension_units->fields['UNITS']+$weekly_renewal_units->fields['UNITS'], 2)?></th>
                                        </tr>
                                        <tr>
                                            <th style="width:18%; text-align: center; vertical-align:auto; font-weight: normal !important" colspan="2">$<?=number_format($weekly_pre_original_sales->fields['SALES'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">$<?=number_format($weekly_original_sales->fields['SALES'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">$<?=number_format($weekly_extension_sales->fields['SALES'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">$<?=number_format($weekly_renewal_sales->fields['SALES'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">$<?=number_format($weekly_pre_original_sales->fields['SALES']+$weekly_original_sales->fields['SALES']+$weekly_extension_sales->fields['SALES']+$weekly_renewal_sales->fields['SALES'], 2)?></th>
                                        </tr>



                                        <tr>
                                            <th style="width:9%; text-align: center;font-weight: bold">Adjust</th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">0 / 0.00/ $0.00</th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">0 / 0.00/ $0.00</th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">0 / 0.00/ $0.00</th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">0 / 0.00/ $0.00</th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">0 / 0.00/ $0.00</th>
                                        </tr>


                                        <?php
                                        $yearly_pre_original_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        $yearly_pre_original_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        $yearly_pre_original_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        $yearly_pre_original_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");

                                        $yearly_original_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        $yearly_original_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        $yearly_original_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        $yearly_original_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");

                                        $yearly_extension_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        $yearly_extension_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        $yearly_extension_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        $yearly_extension_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");

                                        $yearly_renewal_tried = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TRIED FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        $yearly_renewal_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        $yearly_renewal_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        $yearly_renewal_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                        ?>
                                        <tr>
                                            <th style="width:5%; text-align: center; vertical-align:auto; font-weight: bold" rowspan="3">Net YTD</th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$yearly_pre_original_tried->fields['TRIED']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$yearly_pre_original_sold->fields['SOLD']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$yearly_original_tried->fields['TRIED']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$yearly_original_sold->fields['SOLD']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$yearly_extension_tried->fields['TRIED']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$yearly_extension_sold->fields['SOLD']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$yearly_renewal_tried->fields['TRIED']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$yearly_renewal_sold->fields['SOLD']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">T : <?=$yearly_pre_original_tried->fields['TRIED']+$yearly_original_tried->fields['TRIED']+$yearly_extension_tried->fields['TRIED']+$yearly_renewal_tried->fields['TRIED']?></th>
                                            <th style="width:9%; text-align: center; font-weight: normal !important">S : <?=$yearly_pre_original_sold->fields['SOLD']+$yearly_original_sold->fields['SOLD']+$yearly_extension_sold->fields['SOLD']+$yearly_renewal_sold->fields['SOLD']?></th>
                                        </tr>
                                        <tr>
                                            <th style="width:18%; text-align: center; vertical-align:auto; font-weight: normal !important" colspan="2">Units: <?=number_format($yearly_pre_original_units->fields['UNITS'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($yearly_original_units->fields['UNITS'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($yearly_extension_units->fields['UNITS'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($yearly_renewal_units->fields['UNITS'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">Units: <?=number_format($yearly_pre_original_units->fields['UNITS']+$yearly_original_units->fields['UNITS']+$yearly_extension_units->fields['UNITS']+$yearly_renewal_units->fields['UNITS'], 2)?></th>
                                        </tr>
                                        <tr>
                                            <th style="width:18%; text-align: center; vertical-align:auto; font-weight: normal !important" colspan="2">$<?=number_format($yearly_pre_original_sales->fields['SALES'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">$<?=number_format($yearly_original_sales->fields['SALES'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">$<?=number_format($yearly_extension_sales->fields['SALES'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">$<?=number_format($yearly_renewal_sales->fields['SALES'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">$<?=number_format($yearly_pre_original_sales->fields['SALES']+$yearly_original_sales->fields['SALES']+$yearly_extension_sales->fields['SALES']+$yearly_renewal_sales->fields['SALES'], 2)?></th>
                                        </tr>


                                        <?php
                                        $prev_year_pre_original_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN $prev_year_date_condition");
                                        $prev_year_original_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN $prev_year_date_condition");
                                        $prev_year_extension_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN $prev_year_date_condition");
                                        $prev_year_renewal_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN $prev_year_date_condition");
                                        ?>
                                        <tr>
                                            <th style="width:5%; text-align: center; vertical-align:auto; font-weight: bold">Prev</th>
                                            <th style="width:18%; text-align: center; vertical-align:auto; font-weight: normal !important" colspan="2">$<?=number_format($prev_year_pre_original_sales->fields['SALES'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">$<?=number_format($prev_year_original_sales->fields['SALES'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">$<?=number_format($prev_year_extension_sales->fields['SALES'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">$<?=number_format($prev_year_renewal_sales->fields['SALES'], 2)?></th>
                                            <th style="width:18%; text-align: center; font-weight: normal !important" colspan="2">$<?=number_format($prev_year_pre_original_sales->fields['SALES']+$prev_year_original_sales->fields['SALES']+$prev_year_extension_sales->fields['SALES']+$prev_year_renewal_sales->fields['SALES'], 2)?></th>
                                        </tr>

                                        </thead>
                                    </table>
                                </div>
                                <div class="table-responsive">
                                    <?php if($res->fields['FRANCHISE']==1){?>
                                        <label style="width:100%; text-align: center; font-weight: bold">MISCELLANEOUS / FESTIVAL SALES TRACKING</label>
                                    <?php } else { ?>
                                        <label style="width:100%; text-align: center; font-weight: bold">MISCELLANEOUS</label>
                                    <?php } ?>
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
                                            $weekly_misc_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 16 AND ENROLLMENT_DATE BETWEEN $weekly_date_condition");
                                            ?>
                                            <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">Prev.</th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important">$0.00</th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important">$<?=number_format($weekly_misc_sales->fields['SALES'],2)?></th>
                                        </tr>
                                        <tr>
                                            <?php
                                            $yearly_misc_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 16 AND ENROLLMENT_DATE BETWEEN $net_year_date_condition");
                                            ?>
                                            <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">YTD</th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important">$0.00</th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important">$<?=number_format($yearly_misc_sales->fields['SALES'],2)?></th>
                                        </tr>
                                        <tr>
                                            <?php
                                            $prev_year_misc_sales = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS SALES FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID IN (".$PK_USER.") OR DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$PK_USER.")) AND PK_ENROLLMENT_TYPE = 16 AND ENROLLMENT_DATE BETWEEN $prev_year_date_condition");
                                            ?>
                                            <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">Prev.</th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important"></th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important">$0.00</th>
                                            <th style="width:10%; text-align: center; font-weight: normal !important">$<?=number_format($prev_year_misc_sales->fields['SALES'],2)?></th>
                                        </tr>
                                        </thead>
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

<?php require_once('../includes/footer.php');?>

<script>
    // $(function () {
    //     $('#myTable').DataTable({
    //         "columnDefs": [
    //             { "targets": [0,2,5], "searchable": false }
    //         ]
    //     });
    // });

    $('.multi_select_service_provider').SumoSelect({placeholder: 'Select Service Provider', selectAll: true, okCancelInMulti: true, triggerChangeCombined: true});

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

    function selectDefaultServiceProvider(param) {
        let PK_USER = $(param).val();
        let type = "<?php echo $_GET['type']; ?>"
        let week_number = "<?php echo $_GET['week_number']; ?>"
        window.location.href = "summary_of_staff_member_report.php?week_number="+week_number+"&type="+type+"&PK_USER="+PK_USER;
    }
</script>

</body>
</html>
