<?php

use Twilio\Rest\Client;

global $db;

require_once('global/config.php');

if (isset($_SESSION['TEMP_PK_USER'])) {
    $TEMP_PK_USER = isset($_SESSION['TEMP_PK_USER']) ? $_SESSION['TEMP_PK_USER'] : '';
    $user_auth_data = $db->Execute("SELECT * FROM DOA_USER_AUTH_LOG WHERE PK_USER = '$TEMP_PK_USER' AND IS_VERIFIED = 0 ORDER BY LOGIN_TIME DESC LIMIT 1");
    $SAVED_OTP = $user_auth_data->fields['OTP'];

    $user_data = $db->Execute("SELECT PHONE FROM DOA_USERS WHERE PK_USER = '$TEMP_PK_USER'");

    $text_setting = $db->Execute("SELECT * FROM `DOA_TEXT_SETTINGS` WHERE PK_TEXT_SETTINGS = 1");
    $SID = $text_setting->fields['SID'];
    $TOKEN = $text_setting->fields['TOKEN'];
    $TWILIO_PHONE_NO = $text_setting->fields['FROM_NO'];

    $PHONE = $user_data->fields['PHONE'];
    $OTP = $SAVED_OTP;

    $message = $OTP . ' is your verification code for DOable.';
    //echo $message . "<br>"; die();
    try {
        $client = new Client($SID, $TOKEN);
        $response = $client->messages->create(
            '+1' . $PHONE,
            [
                'from' => $TWILIO_PHONE_NO,
                'body' => $message //$msg->fields['CONTENT']
            ]
        );

        $_SESSION['OTP_SEND_SUCCESS'] = 'An OTP is send to you mobile number ' . formatPhone($PHONE);

        header("location: verify_login_otp.php");
    } catch (\Twilio\Exceptions\TwilioException $e) {
        $msg = 'OTP Sending Error : ' . $e->getMessage();
        die();
    }
} else {
    header("location: login.php");
}
