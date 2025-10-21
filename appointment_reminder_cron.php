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
$all_location = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.HOUR, DOA_ACCOUNT_MASTER.DB_NAME, DOA_TIMEZONE.TIMEZONE FROM DOA_LOCATION LEFT JOIN DOA_TIMEZONE ON DOA_LOCATION.PK_TIMEZONE = DOA_TIMEZONE.PK_TIMEZONE LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER  WHERE DOA_ACCOUNT_MASTER.ACTIVE = 1 AND DOA_LOCATION.ACTIVE = 1 AND DOA_LOCATION.TEXTING_FEATURE_ENABLED = 1 AND DOA_LOCATION.APPOINTMENT_REMINDER = 1");
while (!$all_location->EOF) {
    date_default_timezone_set($all_location->fields['TIMEZONE']);
    $currentHour = (int)date('G');
    if ($currentHour > 8 || $currentHour < 17) {
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

        $REMINDER_SECOND = $all_location->fields['HOUR'] * 3600;
        $REMIND_TIME = date('Y-m-d H:i:s', time() + $REMINDER_SECOND);

        [$SID, $TOKEN, $TWILIO_PHONE_NO] = getTwilioSettingData($PK_LOCATION);

        $APPOINTMENT_DATA = $db1->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DATE, START_TIME, CAST(CONCAT(DATE, ' ', START_TIME) AS DATETIME) AS APPOINTMENT_TIME, DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER AS CUSTOMER_ID FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION = '$PK_LOCATION' AND `SERVICE_CODE` LIKE '%PRI%' AND (APPOINTMENT_TYPE = 'NORMAL' || APPOINTMENT_TYPE = 'AD-HOC') AND PK_APPOINTMENT_STATUS = 1 AND IS_REMINDER_SEND = 0 AND STATUS = 'A' AND DOA_APPOINTMENT_CUSTOMER.IS_PARTNER = 0 AND DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER > 0 HAVING (APPOINTMENT_TIME <= '$REMIND_TIME' AND APPOINTMENT_TIME > '" . date('Y-m-d H:i:s', strtotime('-1 days')) . "') ORDER BY APPOINTMENT_TIME ASC");
        while (!$APPOINTMENT_DATA->EOF) {
            $customer_phone_number = $db->Execute("SELECT DOA_USERS.PHONE FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.IS_DELETED = 0 AND DOA_USERS.ACTIVE = 1 AND DOA_USER_MASTER.PK_USER_MASTER = " . $APPOINTMENT_DATA->fields['CUSTOMER_ID']);

            if ($customer_phone_number->RecordCount() > 0) {
                $message = 'This is a friendly reminder for your ' . date('m/d/Y', strtotime($APPOINTMENT_DATA->fields['DATE'])) . ' appointment with ' . $all_location->fields['LOCATION_NAME'] . ' at the following time: ' . date('h:i A', strtotime($APPOINTMENT_DATA->fields['START_TIME'])) . '. Please do not respond to this message. Thank You!';
                //echo $message . "<br>";
                try {
                    $client = new Client($SID, $TOKEN);
                    $response = $client->messages->create(
                        '+1' . $customer_phone_number->fields['PHONE'],
                        [
                            'from' => $TWILIO_PHONE_NO,
                            'body' => $message //$msg->fields['CONTENT']
                        ]
                    );
                    $IS_ERROR = 0;
                    $ERROR_MESSAGE = '';
                } catch (\Twilio\Exceptions\TwilioException $e) {
                    echo 'Error : ' . $e->getMessage() . "<br>";
                    $IS_ERROR = 1;
                    $ERROR_MESSAGE = $e->getMessage();
                } finally {
                    $PK_LOCATION = $PK_LOCATION;
                    $PK_USER_MASTER = $APPOINTMENT_DATA->fields['CUSTOMER_ID'];
                    $PHONE_NUMBER = $customer_phone_number->fields['PHONE'];
                    $MESSAGE = $message;
                    $TRIGGER_TIME = date('Y-m-d H:i:s');
                    $db1->Execute("INSERT INTO DOA_SMS_LOG (IS_ERROR, ERROR_MESSAGE, PK_LOCATION, PK_USER_MASTER, PHONE_NUMBER, MESSAGE, TRIGGER_TIME) VALUES ($IS_ERROR, '" . addslashes($ERROR_MESSAGE) . "', $PK_LOCATION, $PK_USER_MASTER, '$PHONE_NUMBER', '" . addslashes($MESSAGE) . "', '$TRIGGER_TIME')");
                    $db1->Execute("UPDATE `DOA_APPOINTMENT_MASTER` SET IS_REMINDER_SEND = 1, PK_APPOINTMENT_STATUS = 7 WHERE `PK_APPOINTMENT_MASTER` = " . $APPOINTMENT_DATA->fields['PK_APPOINTMENT_MASTER']);
                }
            }

            //echo $APPOINTMENT_DATA->fields['PK_APPOINTMENT_MASTER'] . " Reminder Sent to " . $customer_phone_number->fields['PHONE'] . "<br>";

            $APPOINTMENT_DATA->MoveNext();
        }
    }
    $all_location->MoveNext();
}
