<?php

use Twilio\Rest\Client;

require_once('global/config.php');
require_once("global/vendor/twilio/sdk/src/Twilio/autoload.php");

global $db;

if (!empty($_POST) && $_POST['function'] == 'send_sms') {
    $all_location = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.HOUR, DOA_CORPORATION.CORPORATION_NAME, DOA_ACCOUNT_MASTER.DB_NAME FROM DOA_LOCATION LEFT JOIN DOA_CORPORATION ON DOA_LOCATION.PK_CORPORATION = DOA_CORPORATION.PK_CORPORATION LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER  WHERE PK_LOCATION = 13 AND DOA_ACCOUNT_MASTER.ACTIVE = 1 AND DOA_LOCATION.ACTIVE = 1 AND DOA_LOCATION.APPOINTMENT_REMINDER = 1");

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

        $customer_phone_number = $_POST['phone_number'];
        $message = $_POST['message'];

        //$msg = $db->Execute("SELECT CONTENT FROM DOA_TEXT_TEMPLATE WHERE PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
        try {
            $client = new Client($SID, $TOKEN);
            $response = $client->messages->create(
                '+1' . $customer_phone_number,
                [
                    'from' => $TWILIO_PHONE_NO,
                    'body' => $message //$msg->fields['CONTENT']
                ]
            );
        } catch (\Twilio\Exceptions\TwilioException $e) {
            echo 'Error : ' . $e->getMessage() . "<br>";
        }

        echo "SMS Sent to " . $customer_phone_number . "<br>";
        echo "Message SID: " . $response->sid . "<br>";
        echo "Message Status: " . $response->status . "<br>";

        $all_location->MoveNext();
    }
}



if (!empty($_POST) && $_POST['function'] == 'check_status') {
    $all_location = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.HOUR, DOA_CORPORATION.CORPORATION_NAME, DOA_ACCOUNT_MASTER.DB_NAME FROM DOA_LOCATION LEFT JOIN DOA_CORPORATION ON DOA_LOCATION.PK_CORPORATION = DOA_CORPORATION.PK_CORPORATION LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER  WHERE PK_LOCATION = 13 AND DOA_ACCOUNT_MASTER.ACTIVE = 1 AND DOA_LOCATION.ACTIVE = 1 AND DOA_LOCATION.APPOINTMENT_REMINDER = 1");

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

        $msg_id = $_POST['msg_id'];

        try {
            $client = new Client($SID, $TOKEN);
            $response = $client->messages($msg_id)->fetch();
        } catch (\Twilio\Exceptions\TwilioException $e) {
            echo 'Error : ' . $e->getMessage() . "<br>";
        }

        echo "Message SID: " . $response->sid . "<br>";
        echo "Message Status: " . $response->status . "<br>";
        echo "Message Body: " . $response->body . "<br>";
        echo "Error Code: " . $response->errorCode . "<br>";
        echo "Error Message: " . $response->errorMessage . "<br>";

        $all_location->MoveNext();
    }
}


?>
<br><br>
<h3>Use this form to send an SMS</h3>
<form method="POST" action="test_sms.php">
    <input type="hidden" name="function" value="send_sms">
    <label for="phone_number">Phone Number:</label>
    <input type="text" id="phone_number" name="phone_number" required>
    <br><br>
    <label for="message">Message:</label>
    <textarea id="message" name="message" required rows="5"></textarea>
    <br><br>
    <input type="submit" value="Send SMS">
</form>




<br><br><br><br><br><br><br><br><br><br>
<h3>After sending the message you can check the status of that by using Message SID</h3>
<form method="POST" action="test_sms.php">
    <input type="hidden" name="function" value="check_status">
    <label for="msg_id">Message SID:</label>
    <input type="text" id="msg_id" name="msg_id" required>
    <br><br>
    <input type="submit" value="Check Status">
</form>