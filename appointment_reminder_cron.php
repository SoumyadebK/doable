<?php

use Twilio\Rest\Client;

if ($_SERVER['HTTP_HOST'] == 'localhost') {
    date_default_timezone_set('Asia/Kolkata');
    require_once("global/config.php");
    require_once("global/vendor/twilio/sdk/src/Twilio/autoload.php");
} else {
    date_default_timezone_set('Pacific/Honolulu');
    require_once("/var/www/html/global/config.php");
    require_once("/var/www/html/global/vendor/twilio/sdk/src/Twilio/autoload.php");
}

$currentHour = (int)date('G');
if ($currentHour > 7 || $currentHour < 21) {
    global $db;
    $all_location = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.HOUR, DOA_ACCOUNT_MASTER.DB_NAME FROM DOA_LOCATION LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER  WHERE DOA_ACCOUNT_MASTER.ACTIVE = 1 AND DOA_LOCATION.ACTIVE = 1 AND DOA_LOCATION.TEXTING_FEATURE_ENABLED = 1 AND DOA_LOCATION.APPOINTMENT_REMINDER = 1");
    while (!$all_location->EOF) {
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

        $APPOINTMENT_DATA = $db1->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DATE, START_TIME, CAST(CONCAT(DATE, ' ', START_TIME) AS DATETIME) AS APPOINTMENT_TIME, DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER AS CUSTOMER_ID FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE PK_LOCATION = '$PK_LOCATION' AND (APPOINTMENT_TYPE = 'NORMAL' || APPOINTMENT_TYPE = 'AD-HOC') AND PK_APPOINTMENT_STATUS = 1 AND IS_REMINDER_SEND = 0 AND STATUS = 'A' AND DOA_APPOINTMENT_CUSTOMER.IS_PARTNER = 0 AND DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER > 0 HAVING (APPOINTMENT_TIME <= '$REMIND_TIME' AND APPOINTMENT_TIME > '" . date('Y-m-d H:i:s', strtotime('-1 days')) . "') ORDER BY APPOINTMENT_TIME ASC");
        while (!$APPOINTMENT_DATA->EOF) {
            $customer_phone_number = $db->Execute("SELECT DOA_USERS.PHONE FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $APPOINTMENT_DATA->fields['CUSTOMER_ID']);

            $message = 'This is a friendly reminder for your ' . date('m/d/Y', strtotime($APPOINTMENT_DATA->fields['DATE'])) . ' appointment with ' . $all_location->fields['LOCATION_NAME'] . ' at the following time: ' . date('h:i A', strtotime($APPOINTMENT_DATA->fields['START_TIME'])) . '. Please do not respond to this message. Thank You!';
            try {
                $client = new Client($SID, $TOKEN);
                $response = $client->messages->create(
                    '+1' . $customer_phone_number->fields['PHONE'],
                    [
                        'from' => $TWILIO_PHONE_NO,
                        'body' => $message //$msg->fields['CONTENT']
                    ]
                );
            } catch (\Twilio\Exceptions\TwilioException $e) {
                echo 'Error : ' . $e->getMessage() . "<br>";
            } finally {
                $db1->Execute("UPDATE `DOA_APPOINTMENT_MASTER` SET IS_REMINDER_SEND = 1, PK_APPOINTMENT_STATUS = 7 WHERE `PK_APPOINTMENT_MASTER` = " . $APPOINTMENT_DATA->fields['PK_APPOINTMENT_MASTER']);
            }

            //echo $APPOINTMENT_DATA->fields['PK_APPOINTMENT_MASTER'] . " Reminder Sent to " . $customer_phone_number->fields['PHONE'] . "<br>";

            $APPOINTMENT_DATA->MoveNext();
        }
        $all_location->MoveNext();
    }
}
