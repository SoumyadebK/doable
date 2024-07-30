<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$PK_USER = $_POST['PK_USER'];
$DATE_TIME = $_POST['DATE_TIME'];
$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$utc_tz =  new DateTimeZone('UTC');
$start_dt = new DateTime($DATE_TIME, $utc_tz);

$DATE = $start_dt->format('Y-m-d');
$START_TIME = $start_dt->format('H:i');

$day = strtoupper(date('D', strtotime($DATE)));
$time = date('H:i:s', strtotime($START_TIME));

$location_hour_data = $db_account->Execute("SELECT MIN(".$day."_START_TIME) AS OPEN_TIME, MAX(".$day."_END_TIME) AS CLOSE_TIME FROM `DOA_SERVICE_PROVIDER_LOCATION_HOURS` WHERE PK_USER = '$PK_USER' AND PK_LOCATION IN (".$DEFAULT_LOCATION_ID.")");

if (is_null($location_hour_data->fields['OPEN_TIME']) || is_null($location_hour_data->fields['CLOSE_TIME'])) {
    echo "No time slot is set for this Instructor";
} else {
    if ($time >= $location_hour_data->fields['OPEN_TIME'] && $time <= $location_hour_data->fields['CLOSE_TIME']) {
        echo 1;
    } else {
        echo "No slot available on your selected time. "," Available time is " . date('h:i A', strtotime($location_hour_data->fields['OPEN_TIME'])) . " to " . date('h:i A', strtotime($location_hour_data->fields['CLOSE_TIME'])) . "";
    }
}
