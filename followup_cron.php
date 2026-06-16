<?php

use Twilio\Rest\Client;

if ($_SERVER['HTTP_HOST'] == 'localhost') {
    require_once("global/config.php");
    require_once("global/vendor/twilio/sdk/src/Twilio/autoload.php");
} else {
    require_once("/var/www/html/global/config.php");
    require_once("/var/www/html/global/vendor/twilio/sdk/src/Twilio/autoload.php");
}

global $db;
$all_location = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.HOUR, DOA_ACCOUNT_MASTER.DB_NAME, DOA_TIMEZONE.TIMEZONE FROM DOA_LOCATION LEFT JOIN DOA_TIMEZONE ON DOA_LOCATION.PK_TIMEZONE = DOA_TIMEZONE.PK_TIMEZONE LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER  WHERE /* DOA_ACCOUNT_MASTER.ACTIVE = 1 AND DOA_LOCATION.ACTIVE = 1 */ PK_LOCATION = 13");
while (!$all_location->EOF) {
    date_default_timezone_set($all_location->fields['TIMEZONE']);

    $DB_NAME = $all_location->fields['DB_NAME'];
    $db1 = new queryFactory();
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        $conn1 = $db1->connect('localhost', 'root', '', $DB_NAME);
        $http_path = 'http://localhost/doable/';
    } else {
        $conn1 = $db1->connect('localhost', 'root', 'b54eawxj5h8ev', $DB_NAME);
        $http_path = 'https://doable.net/';
    }
    if ($db1->error_number) {
        die("Connection Error");
    }

    $PK_LOCATION = $all_location->fields['PK_LOCATION'];
    $PK_ACCOUNT_MASTER = $all_location->fields['PK_ACCOUNT_MASTER'];


    $all_followup = $db1->Execute("SELECT * FROM DOA_AUTOMATIONS WHERE PK_LOCATION = '$PK_LOCATION' AND PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER' AND IS_ACTIVE = 1");
    while (!$all_followup->EOF) {
        $PK_AUTOMATION_ID = $all_followup->fields['PK_AUTOMATION_ID'];
        $TRIGGER_TYPE = $all_followup->fields['TRIGGER_TYPE'];
        $START_REMINDER_VALUE = $all_followup->fields['START_REMINDER_VALUE'];
        $END_REMINDER_VALUE = $START_REMINDER_VALUE + 1;
        $START_REMINDER_UNIT = strtoupper($all_followup->fields['START_REMINDER_UNIT']);

        if ($TRIGGER_TYPE == 'CUSTOMER_COMPLETE_CLASS') {
            $appointment_data = $db1->Execute("SELECT * FROM DOA_APPOINTMENT_MASTER WHERE PK_APPOINTMENT_STATUS = 2 AND TIMESTAMP(`DATE`, `END_TIME`) >= NOW() - INTERVAL $END_REMINDER_VALUE $START_REMINDER_UNIT AND TIMESTAMP(`DATE`, `END_TIME`) < NOW() - INTERVAL $START_REMINDER_VALUE $START_REMINDER_UNIT AND PK_LOCATION = '$PK_LOCATION'");
            while (!$appointment_data->EOF) {
                echo $PK_APPOINTMENT_MASTER = $appointment_data->fields['PK_APPOINTMENT_MASTER'];

                $appointment_data->MoveNext();
            }
        } elseif ($TRIGGER_TYPE == 'NO_FUTURE_APPOINTMENTS') {
            include("followup_cron_no_future_appointments.php");
        } elseif ($TRIGGER_TYPE == 'NO_ACTIVE_ENROLLMENTS') {
            include("followup_cron_no_active_enrollments.php");
        } elseif ($TRIGGER_TYPE == 'NO_SPECIFIC_SERVICES') {
            include("followup_cron_no_specific_services.php");
        }

        $all_followup->MoveNext();
    }

    $all_location->MoveNext();
}
