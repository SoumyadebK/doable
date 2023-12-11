<?php

use Twilio\Rest\Client;

require_once('global/config.php');

$all_account = $db->Execute("SELECT PK_ACCOUNT_MASTER, HOUR FROM `DOA_ACCOUNT_MASTER` WHERE `ACTIVE` = 1 AND APPOINTMENT_REMINDER = 1");
while (!$all_account->EOF) {
    $PK_ACCOUNT_MASTER = $all_account->fields['PK_ACCOUNT_MASTER'];
    $REMINDER_SECOND = $all_account->fields['HOUR']*3600;
    $REMIND_TIME = date('Y-m-d H:i:s', time()+$REMINDER_SECOND);
    $APPOINTMENT_DATA = $db->Execute("SELECT PK_APPOINTMENT_MASTER, CUSTOMER_ID, CAST(CONCAT(DATE, ' ', START_TIME) AS DATETIME) AS APPOINTMENT_TIME FROM DOA_APPOINTMENT_MASTER WHERE PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER' AND PK_APPOINTMENT_STATUS = 1 AND IS_REMINDER_SEND = 0 AND STATUS = 'A' HAVING APPOINTMENT_TIME <= '$REMIND_TIME'");
    while (!$APPOINTMENT_DATA->EOF) {
        require_once("global/vendor/twilio/sdk/src/Twilio/autoload.php");
        $text_setting = $db->Execute("SELECT * FROM `DOA_TEXT_SETTINGS`");
        $sid = $text_setting->fields['SID'];
        $token = $TOKEN = $text_setting->fields['TOKEN'];

        $customer_phone_number = $db->Execute("SELECT DOA_USERS.PHONE FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = ".$APPOINTMENT_DATA->fields['CUSTOMER_ID']);

        $msg = $db->Execute("SELECT CONTENT FROM DOA_TEXT_TEMPLATE WHERE PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER' AND PK_EMAIL_TRIGGER = 1");
        try {
            $client = new Client($sid, $token);
            $response = $client->messages->create(
                '+1'.$customer_phone_number->fields['PHONE'],
                [
                    'from' => $text_setting->fields['FROM_NO'],
                    'body' => $msg->fields['CONTENT']
                ]
            );
        } catch (\Twilio\Exceptions\TwilioException $e) {
            echo $e->getMessage()."<br>";
        } finally {
            $db->Execute("UPDATE `DOA_APPOINTMENT_MASTER` SET `IS_REMINDER_SEND` = '1' AND PK_APPOINTMENT_STATUS = 7 WHERE `DOA_APPOINTMENT_MASTER`.`PK_APPOINTMENT_MASTER` = ".$APPOINTMENT_DATA->fields['PK_APPOINTMENT_MASTER']);
        }
        $APPOINTMENT_DATA->MoveNext();
    }
    $all_account->MoveNext();
}
