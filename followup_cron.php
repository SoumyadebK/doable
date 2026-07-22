<?php

use Twilio\Rest\Client;

if ($_SERVER['HTTP_HOST'] == 'localhost') {
    require_once("global/config.php");
    require_once('global/common_functions_account.php');
    require_once("global/vendor/twilio/sdk/src/Twilio/autoload.php");
} else {
    require_once("/var/www/html/global/config.php");
    require_once('/var/www/html/global/common_functions_account.php');
    require_once("/var/www/html/global/vendor/twilio/sdk/src/Twilio/autoload.php");
}

global $db;
$all_location = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.HOUR, DOA_ACCOUNT_MASTER.DB_NAME, DOA_TIMEZONE.TIMEZONE FROM DOA_LOCATION LEFT JOIN DOA_TIMEZONE ON DOA_LOCATION.PK_TIMEZONE = DOA_TIMEZONE.PK_TIMEZONE LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER WHERE DOA_ACCOUNT_MASTER.ACTIVE = 1 AND DOA_LOCATION.ACTIVE = 1 /* PK_LOCATION = 13 */");
while (!$all_location->EOF) {
    date_default_timezone_set($all_location->fields['TIMEZONE']);

    $DB_NAME = $all_location->fields['DB_NAME'];
    $db_account = new queryFactory();
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        $conn1 = $db_account->connect('localhost', 'root', '', $DB_NAME);
        $http_path = 'http://localhost/doable/';
    } else {
        $conn1 = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $DB_NAME);
        $http_path = 'https://doable.net/';
    }
    if ($db_account->error_number) {
        die("Connection Error");
    }

    $PK_LOCATION = $all_location->fields['PK_LOCATION'];
    $PK_ACCOUNT_MASTER = $all_location->fields['PK_ACCOUNT_MASTER'];

    $all_followup = $db_account->Execute("SELECT * FROM DOA_AUTOMATIONS WHERE PK_LOCATION = '$PK_LOCATION' AND PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER' AND IS_ACTIVE = 1");
    while (!$all_followup->EOF) {
        $follow_up_data = $all_followup->fields;
        $TRIGGER_TYPE = $follow_up_data['TRIGGER_TYPE'];

        if ($TRIGGER_TYPE == 'NO_FUTURE_APPOINTMENTS') {
            noFutureAppointment($db_account, $PK_LOCATION, $follow_up_data);
        } elseif ($TRIGGER_TYPE == 'NO_SPECIFIC_SERVICES') {
            include("followup_cron_no_specific_services.php");
        } elseif ($TRIGGER_TYPE == 'NEW_LEAD_IS_GENERATED') {
            include("followup_cron_new_lead_is_generated.php");
        }

        $all_followup->MoveNext();
    }

    $all_location->MoveNext();
}

function noFutureAppointment($db_account, $PK_LOCATION, $follow_up_data)
{
    $PK_AUTOMATION_ID = $follow_up_data['PK_AUTOMATION_ID'];
    $APPOINTMENT_TYPE = ($follow_up_data['TRIGGER_VALUE'] == 'PRIVATE_CLASS') ? 'NORMAL' : 'GROUP';

    if ($follow_up_data['SCHEDULE_TYPE'] == 'simple') {
        $START_REMINDER_VALUE = $follow_up_data['START_REMINDER_VALUE'];
        $reminder_data = $db_account->Execute("SELECT * FROM DOA_AUTOMATION_MESSAGES WHERE PK_AUTOMATION_ID = '$PK_AUTOMATION_ID'");
        while (!$reminder_data->EOF) {
            $appointment_data = getLastAppointment($db_account, $PK_LOCATION, $APPOINTMENT_TYPE, $START_REMINDER_VALUE);
            while (!$appointment_data->EOF) {
                saveAutomationLog($db_account, $PK_LOCATION, $PK_AUTOMATION_ID, $reminder_data->fields, 'appointment', $appointment_data->fields);
                $appointment_data->MoveNext();
            }
            $START_REMINDER_VALUE += $follow_up_data['START_REMINDER_VALUE'];
            $reminder_data->MoveNext();
        }
    } elseif ($follow_up_data['SCHEDULE_TYPE'] == 'custom') {
        $REMINDER_VALUE = 0;
        $reminder_data = $db_account->Execute("SELECT * FROM DOA_AUTOMATION_MESSAGES WHERE PK_AUTOMATION_ID = '$PK_AUTOMATION_ID' AND IS_ENABLE = 1");
        while (!$reminder_data->EOF) {
            $REMINDER_VALUE += $reminder_data->fields['VALUE'];
            $appointment_data = getLastAppointment($db_account, $PK_LOCATION, $APPOINTMENT_TYPE, $REMINDER_VALUE);
            while (!$appointment_data->EOF) {
                saveAutomationLog($db_account, $PK_LOCATION, $PK_AUTOMATION_ID, $reminder_data->fields, 'appointment', $appointment_data->fields);
                $appointment_data->MoveNext();
            }
            $reminder_data->MoveNext();
        }
    }
}

function getLastAppointment($db_account, $PK_LOCATION, $APPOINTMENT_TYPE, $REMINDER_VALUE)
{
    $query = "SELECT
                AM.PK_ENROLLMENT_MASTER,
                AC.PK_USER_MASTER,
                MAX(AM.PK_APPOINTMENT_MASTER) AS PK_APPOINTMENT_MASTER,
                MAX(AM.DATE) AS LAST_APPOINTMENT_DATE
            FROM DOA_APPOINTMENT_CUSTOMER AC
            INNER JOIN DOA_APPOINTMENT_MASTER AM
                ON AM.PK_APPOINTMENT_MASTER = AC.PK_APPOINTMENT_MASTER
            WHERE AM.STATUS = 'A' AND AM.APPOINTMENT_TYPE = '$APPOINTMENT_TYPE'
            AND AM.PK_LOCATION = '$PK_LOCATION'
            GROUP BY AC.PK_USER_MASTER
            HAVING DATEDIFF(CURDATE(), MAX(AM.DATE)) = " . $REMINDER_VALUE;
    $all_appointment = $db_account->Execute($query);
    return $all_appointment;
}

function saveAutomationLog($db_account, $PK_LOCATION, $PK_AUTOMATION_ID, $reminder_data, $type, $data)
{
    global $db;
    if ($type == 'appointment') {
        $is_already_saved = $db_account->Execute("SELECT * FROM DOA_AUTOMATION_LOG WHERE PK_AUTOMATION_ID = '$PK_AUTOMATION_ID' AND PK_MESSAGE_ID = '$reminder_data[PK_MESSAGE_ID]' AND TYPE = '$type' AND PK_VALUE = '$data[PK_APPOINTMENT_MASTER]'");
        if ($is_already_saved->RecordCount() == 0) {
            $insert_log_data['PK_AUTOMATION_ID'] = $PK_AUTOMATION_ID;
            $insert_log_data['PK_MESSAGE_ID'] = $reminder_data['PK_MESSAGE_ID'];
            $insert_log_data['TYPE'] = $type;
            $insert_log_data['PK_VALUE'] = $data['PK_APPOINTMENT_MASTER'];
            $insert_log_data['PK_USER_MASTER'] = '';
            $insert_log_data['LAST_CLASS_SP_ID'] = '';
            $insert_log_data['PK_USER_MASTER'] = $data['PK_USER_MASTER'];

            /* if ($reminder_data['NOTIFY_CUSTOMER'] == 1) {
                //sms will send to customer
            } */

            $service_provider_name = '';
            if ($reminder_data['NOTIFY_SERVICE_PROVIDER_LAST'] == 1) {
                $last_sp_array = [];
                $appointment_service_provider = $db_account->Execute("SELECT * FROM DOA_APPOINTMENT_SERVICE_PROVIDER WHERE PK_APPOINTMENT_MASTER = '$data[PK_APPOINTMENT_MASTER]'");
                while (!$appointment_service_provider->EOF) {
                    $last_sp_array[] = $appointment_service_provider->fields['PK_USER'];
                    $appointment_service_provider->MoveNext();
                }
                $insert_log_data['LAST_CLASS_SP_ID'] = implode(',', $last_sp_array);

                $service_provider_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE FROM DOA_USERS WHERE PK_USER = " . $last_sp_array[0]);
                $service_provider_name = $service_provider_data->fields['NAME'];
            }

            $location_corporation_data = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.CITY, DOA_LOCATION.PHONE, DOA_LOCATION.EMAIL, DOA_LOCATION.ACTIVE, DOA_CORPORATION.CORPORATION_NAME FROM DOA_LOCATION LEFT JOIN DOA_CORPORATION ON DOA_LOCATION.PK_CORPORATION = DOA_CORPORATION.PK_CORPORATION WHERE DOA_LOCATION.PK_LOCATION = " . $PK_LOCATION);
            $location_name = $location_corporation_data->fields['LOCATION_NAME'];
            $corporation_name = $location_corporation_data->fields['CORPORATION_NAME'];

            $customer_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$data[PK_USER_MASTER]'");
            $student_name = $customer_data->fields['NAME'];
            $student_phone = $customer_data->fields['PHONE'];

            $saved_message = $reminder_data['MESSAGE_CONTENT'];

            $replacements = [
                '<span class="variable-badge" contenteditable="false">Student Name</span>' => $student_name,
                '<span class="variable-badge" contenteditable="false">Service Provider Name</span>' => $service_provider_name,
                '<span class="variable-badge" contenteditable="false">Corporation Name</span>' => $corporation_name,
                '<span class="variable-badge" contenteditable="false">Location</span>' => $location_name,
            ];

            $message = str_replace(array_keys($replacements), array_values($replacements), $saved_message);

            echo html_entity_decode($message) . "<br>";

            $insert_log_data['MESSAGE'] = $message;
            $insert_log_data['CREATED_ON'] = date("Y-m-d H:i:s");
            db_perform_account('DOA_AUTOMATION_LOG', $insert_log_data, 'insert');

            if ($reminder_data['MESSAGE_TYPE'] == 'SMS') {
                sendTwilioSMS($PK_LOCATION, $message, $student_phone);
            }
        }
    }
}

function sendTwilioSMS($PK_LOCATION, $message, $to_phone_number)
{
    [$SID, $TOKEN, $TWILIO_PHONE_NO] = getTwilioSettingData($PK_LOCATION);
    try {
        $client = new Client($SID, $TOKEN);
        $response = $client->messages->create(
            '+1' . $to_phone_number,
            [
                'from' => $TWILIO_PHONE_NO,
                'body' => $message
            ]
        );
        $IS_ERROR = 0;
        $ERROR_MESSAGE = '';
    } catch (\Twilio\Exceptions\TwilioException $e) {
        echo 'Error : ' . $e->getMessage() . "<br>";
        $IS_ERROR = 1;
        $ERROR_MESSAGE = $e->getMessage();
    }
}
