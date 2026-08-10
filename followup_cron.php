<?php

use Twilio\Rest\Client;

if ($_SERVER['HTTP_HOST'] == 'localhost') {
    require_once("global/config.php");
    require_once('global/common_functions_account.php');
    require_once("global/vendor/twilio/sdk/src/Twilio/autoload.php");
    require_once('global/phpmailer/class.phpmailer.php');
} else {
    require_once("/var/www/html/global/config.php");
    require_once('/var/www/html/global/common_functions_account.php');
    require_once("/var/www/html/global/vendor/twilio/sdk/src/Twilio/autoload.php");
    require_once('/var/www/html/global/phpmailer/class.phpmailer.php');
}

global $db;
$all_location = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.HOUR, DOA_ACCOUNT_MASTER.DB_NAME, DOA_TIMEZONE.TIMEZONE FROM DOA_LOCATION LEFT JOIN DOA_TIMEZONE ON DOA_LOCATION.PK_TIMEZONE = DOA_TIMEZONE.PK_TIMEZONE LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER WHERE DOA_ACCOUNT_MASTER.ACTIVE = 1 AND DOA_LOCATION.ACTIVE = 1");
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
        } elseif ($TRIGGER_TYPE == 'NO_ACTIVE_ENROLLMENTS') {
            noActiveEnrollment($db_account, $PK_LOCATION, $follow_up_data);
        } elseif ($TRIGGER_TYPE == 'NO_SPECIFIC_SERVICES') {
            //include("followup_cron_no_specific_services.php");
        } elseif ($TRIGGER_TYPE == 'NEW_LEAD_IS_GENERATED') {
            newLeadIsGenerated($db_account, $PK_ACCOUNT_MASTER, $PK_LOCATION, $follow_up_data);
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

    echo $query . "<br>";

    $all_appointment = $db_account->Execute($query);
    return $all_appointment;
}

function noActiveEnrollment($db_account, $PK_LOCATION, $follow_up_data)
{
    $PK_AUTOMATION_ID = $follow_up_data['PK_AUTOMATION_ID'];

    if ($follow_up_data['SCHEDULE_TYPE'] == 'simple') {
        $START_REMINDER_VALUE = $follow_up_data['START_REMINDER_VALUE'];
        $reminder_data = $db_account->Execute("SELECT * FROM DOA_AUTOMATION_MESSAGES WHERE PK_AUTOMATION_ID = '$PK_AUTOMATION_ID'");
        while (!$reminder_data->EOF) {
            $enrollment_data = getLastActiveEnrollment($db_account, $PK_LOCATION, $START_REMINDER_VALUE);
            while (!$enrollment_data->EOF) {
                saveAutomationLog($db_account, $PK_LOCATION, $PK_AUTOMATION_ID, $reminder_data->fields, 'enrollment', $enrollment_data->fields);
                $enrollment_data->MoveNext();
            }
            $START_REMINDER_VALUE += $follow_up_data['START_REMINDER_VALUE'];
            $reminder_data->MoveNext();
        }
    } elseif ($follow_up_data['SCHEDULE_TYPE'] == 'custom') {
        $REMINDER_VALUE = 0;
        $reminder_data = $db_account->Execute("SELECT * FROM DOA_AUTOMATION_MESSAGES WHERE PK_AUTOMATION_ID = '$PK_AUTOMATION_ID' AND IS_ENABLE = 1");
        while (!$reminder_data->EOF) {
            $REMINDER_VALUE += $reminder_data->fields['VALUE'];
            $enrollment_data = getLastActiveEnrollment($db_account, $PK_LOCATION, $REMINDER_VALUE);
            while (!$enrollment_data->EOF) {
                saveAutomationLog($db_account, $PK_LOCATION, $PK_AUTOMATION_ID, $reminder_data->fields, 'enrollment', $enrollment_data->fields);
                $enrollment_data->MoveNext();
            }
            $reminder_data->MoveNext();
        }
    }
}

function getLastActiveEnrollment($db_account, $PK_LOCATION, $REMINDER_VALUE)
{
    $query = "SELECT
                EM.PK_USER_MASTER,
                EM.ENROLLMENT_BY_ID,
                MAX(EM.PK_ENROLLMENT_MASTER) AS PK_ENROLLMENT_MASTER,
                MAX(EM.COMPLETED_DATE) AS COMPLETED_DATE
            FROM DOA_ENROLLMENT_MASTER EM
            WHERE EM.ACTIVE = 1
            AND (EM.STATUS = 'CO' || EM.STATUS = 'C')
            AND EM.PK_LOCATION = '$PK_LOCATION'
            GROUP BY EM.PK_USER_MASTER
            HAVING DATEDIFF(CURDATE(), MAX(EM.COMPLETED_DATE)) = " . $REMINDER_VALUE;

    echo $query . "<br>";

    $all_enrollment = $db_account->Execute($query);
    return $all_enrollment;
}


function newLeadIsGenerated($db_account, $PK_ACCOUNT_MASTER, $PK_LOCATION, $follow_up_data)
{
    $PK_AUTOMATION_ID = $follow_up_data['PK_AUTOMATION_ID'];

    if ($follow_up_data['SCHEDULE_TYPE'] == 'simple') {
        $START_REMINDER_VALUE = $follow_up_data['START_REMINDER_VALUE'];
        $reminder_data = $db_account->Execute("SELECT * FROM DOA_AUTOMATION_MESSAGES WHERE PK_AUTOMATION_ID = '$PK_AUTOMATION_ID'");
        while (!$reminder_data->EOF) {
            $leads_data = getNewLeadsData($db_account, $PK_ACCOUNT_MASTER, $PK_LOCATION, $START_REMINDER_VALUE);
            while (!$leads_data->EOF) {
                saveAutomationLogLeadsData($db_account, $PK_LOCATION, $PK_AUTOMATION_ID, $reminder_data->fields, 'leads', $leads_data->fields);
                $leads_data->MoveNext();
            }
            $START_REMINDER_VALUE += $follow_up_data['START_REMINDER_VALUE'];
            $reminder_data->MoveNext();
        }
    } elseif ($follow_up_data['SCHEDULE_TYPE'] == 'custom') {
        $REMINDER_VALUE = 0;
        $reminder_data = $db_account->Execute("SELECT * FROM DOA_AUTOMATION_MESSAGES WHERE PK_AUTOMATION_ID = '$PK_AUTOMATION_ID' AND IS_ENABLE = 1");
        while (!$reminder_data->EOF) {
            $REMINDER_VALUE += $reminder_data->fields['VALUE'];
            $leads_data = getNewLeadsData($db_account, $PK_ACCOUNT_MASTER, $PK_LOCATION, $REMINDER_VALUE);
            while (!$leads_data->EOF) {
                saveAutomationLogLeadsData($db_account, $PK_LOCATION, $PK_AUTOMATION_ID, $reminder_data->fields, 'leads', $leads_data->fields);
                $leads_data->MoveNext();
            }
            $reminder_data->MoveNext();
        }
    }
}

function getNewLeadsData($db_account, $PK_ACCOUNT_MASTER, $PK_LOCATION, $REMINDER_VALUE)
{
    global $db;
    $lead_status_data = $db->Execute("SELECT PK_LEAD_STATUS FROM DOA_LEAD_STATUS WHERE ACTIVE = 1 AND LEAD_STATUS LIKE '%New%' AND PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
    $PK_LEAD_STATUS = $lead_status_data->fields['PK_LEAD_STATUS'];

    $query = "SELECT *
                FROM DOA_LEADS
                WHERE PK_LEAD_STATUS = $PK_LEAD_STATUS
                AND CREATED_ON < DATE_SUB(NOW(), INTERVAL $REMINDER_VALUE DAY)
                AND PK_LOCATION = $PK_LOCATION
                AND ACTIVE = 1";

    echo $query . "<br>";

    $all_new_leads = $db->Execute($query);
    return $all_new_leads;
}


function saveAutomationLog($db_account, $PK_LOCATION, $PK_AUTOMATION_ID, $reminder_data, $type, $data)
{
    global $db;
    $PK_VALUE = ($type == 'appointment') ? $data['PK_APPOINTMENT_MASTER'] : $data['PK_ENROLLMENT_MASTER'];
    $is_already_saved = $db_account->Execute("SELECT * FROM DOA_AUTOMATION_LOG WHERE PK_AUTOMATION_ID = '$PK_AUTOMATION_ID' AND PK_MESSAGE_ID = '$reminder_data[PK_MESSAGE_ID]' AND TYPE = '$type' AND PK_VALUE = '$PK_VALUE'");
    if ($is_already_saved->RecordCount() == 0) {
        $insert_log_data['PK_AUTOMATION_ID'] = $PK_AUTOMATION_ID;
        $insert_log_data['PK_MESSAGE_ID'] = $reminder_data['PK_MESSAGE_ID'];
        $insert_log_data['TYPE'] = $type;
        $insert_log_data['PK_VALUE'] = $PK_VALUE;
        $insert_log_data['PK_USER_MASTER'] = '';
        $insert_log_data['LAST_CLASS_SP_ID'] = '';
        $insert_log_data['PK_USER_MASTER'] = $data['PK_USER_MASTER'];

        $service_provider_name = '';
        if ($type == 'appointment' && $reminder_data['NOTIFY_SERVICE_PROVIDER_LAST'] == 1) {
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

        if ($type == 'enrollment' && $reminder_data['NOTIFY_SERVICE_PROVIDER_ENROLL'] == 1) {
            $last_sp_array = [];
            $enrollment_service_provider = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE_PROVIDER WHERE PK_ENROLLMENT_MASTER = '$data[PK_ENROLLMENT_MASTER]'");
            while (!$enrollment_service_provider->EOF) {
                $last_sp_array[] = $enrollment_service_provider->fields['SERVICE_PROVIDER_ID'];
                $enrollment_service_provider->MoveNext();
            }
            $insert_log_data['LAST_ENROLLMENT_SP_ID'] = implode(',', $last_sp_array);

            $service_provider_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE FROM DOA_USERS WHERE PK_USER = " . $data['ENROLLMENT_BY_ID']);
            $service_provider_name = $service_provider_data->fields['NAME'];
        }

        $location_corporation_data = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.CITY, DOA_LOCATION.PHONE, DOA_LOCATION.EMAIL, DOA_LOCATION.ACTIVE, DOA_CORPORATION.CORPORATION_NAME FROM DOA_LOCATION LEFT JOIN DOA_CORPORATION ON DOA_LOCATION.PK_CORPORATION = DOA_CORPORATION.PK_CORPORATION WHERE DOA_LOCATION.PK_LOCATION = " . $PK_LOCATION);
        $location_name = $location_corporation_data->fields['LOCATION_NAME'];
        $corporation_name = $location_corporation_data->fields['CORPORATION_NAME'];

        $customer_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$data[PK_USER_MASTER]'");
        $student_name = $customer_data->fields['NAME'];
        $student_phone = $customer_data->fields['PHONE'];
        $student_email = $customer_data->fields['EMAIL_ID'];

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

        if ($reminder_data['NOTIFY_CUSTOMER'] == 1 && strpos($reminder_data['MESSAGE_TYPE'], 'SMS') !== false) {
            sendTwilioSMS($PK_LOCATION, $message, $student_phone);
        }

        if ($reminder_data['NOTIFY_CUSTOMER'] == 1 && strpos($reminder_data['MESSAGE_TYPE'], 'EMAIL') !== false) {
            sendEmailToCustomer($PK_LOCATION, $location_name, $message, $student_email);
        }
    }
}

function saveAutomationLogLeadsData($db_account, $PK_LOCATION, $PK_AUTOMATION_ID, $reminder_data, $type, $data)
{
    global $db;
    $PK_VALUE = $data['PK_LEADS'];
    $is_already_saved = $db_account->Execute("SELECT * FROM DOA_AUTOMATION_LOG WHERE PK_AUTOMATION_ID = '$PK_AUTOMATION_ID' AND PK_MESSAGE_ID = '$reminder_data[PK_MESSAGE_ID]' AND TYPE = '$type' AND PK_VALUE = '$PK_VALUE'");
    if ($is_already_saved->RecordCount() == 0) {
        $insert_log_data['PK_AUTOMATION_ID'] = $PK_AUTOMATION_ID;
        $insert_log_data['PK_MESSAGE_ID'] = $reminder_data['PK_MESSAGE_ID'];
        $insert_log_data['TYPE'] = $type;
        $insert_log_data['PK_VALUE'] = $PK_VALUE;
        $insert_log_data['PK_USER_MASTER'] = '';
        $insert_log_data['LAST_CLASS_SP_ID'] = '';

        $service_provider_name = '';

        $location_corporation_data = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.CITY, DOA_LOCATION.PHONE, DOA_LOCATION.EMAIL, DOA_LOCATION.ACTIVE, DOA_CORPORATION.CORPORATION_NAME FROM DOA_LOCATION LEFT JOIN DOA_CORPORATION ON DOA_LOCATION.PK_CORPORATION = DOA_CORPORATION.PK_CORPORATION WHERE DOA_LOCATION.PK_LOCATION = " . $PK_LOCATION);
        $location_name = $location_corporation_data->fields['LOCATION_NAME'];
        $corporation_name = $location_corporation_data->fields['CORPORATION_NAME'];

        $student_name = $data['FIRST_NAME'] . ' ' . $data['LAST_NAME'];

        $saved_message = $reminder_data['MESSAGE_CONTENT'];

        $replacements = [
            '<span class="variable-badge" contenteditable="false">Student Name</span>' => $student_name,
            '<span class="variable-badge" contenteditable="false">Service Provider Name</span>' => $service_provider_name,
            '<span class="variable-badge" contenteditable="false">Corporation Name</span>' => $corporation_name,
            '<span class="variable-badge" contenteditable="false">Location</span>' => $location_name,
        ];

        $message = str_replace(array_keys($replacements), array_values($replacements), $saved_message);

        echo html_entity_decode($message) . "<br>";

        $all_managers = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER
                                        FROM DOA_USERS 
                                        LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER
                                        LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER 
                                        WHERE DOA_USER_LOCATION.PK_LOCATION IN ($PK_LOCATION) 
                                        AND DOA_USERS.ACTIVE = 1 
                                        AND DOA_USER_ROLES.PK_ROLES = 3
                                        AND (DOA_USERS.IS_DELETED = 0 || DOA_USERS.IS_DELETED IS NULL)");
        $all_managers_ids = [];
        while (!$all_managers->EOF) {
            $all_managers_ids[] = $all_managers->fields['PK_USER'];
            $all_managers->MoveNext();
        }

        $all_managers_ids = implode(',', $all_managers_ids);

        $insert_log_data['STUDIO_MANAGER_ID'] = $all_managers_ids;
        $insert_log_data['MESSAGE'] = $message;
        $insert_log_data['CREATED_ON'] = date("Y-m-d H:i:s");
        db_perform_account('DOA_AUTOMATION_LOG', $insert_log_data, 'insert');
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

function sendEmailToCustomer($PK_LOCATION, $location_name, $message, $to_email)
{
    $locationSmtpSetting = getLocationSmtpSetting($PK_LOCATION);

    $hostname = $locationSmtpSetting['SMTP_HOST'];
    $port = $locationSmtpSetting['SMTP_PORT'];
    $userName = $locationSmtpSetting['SMTP_USERNAME'];
    $SendingPwd = $locationSmtpSetting['SMTP_PASSWORD'];

    $To = $to_email;
    $Subject = "Follow Up from " . $location_name;

    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPDebug = 0;
    $mail->Debugoutput = 'html';
    $mail->IsHTML(true);
    $mail->Host = $hostname;
    $mail->Port = $port;
    $mail->SMTPSecure = ($port == 465) ? 'ssl' : 'tls';
    $mail->SMTPAuth = true;
    $mail->Username = $userName;
    $mail->Password = $SendingPwd;
    $mail->setFrom($userName, $location_name);
    $mail->addAddress($To, $location_name);  //Set who the message is to be sent to.
    //Set the subject line
    $mail->Subject = $Subject;

    // Tell PHPMailer this is an HTML email
    $mail->IsHTML(true);

    $mail->Body = '<!DOCTYPE html>
                    <html>
                        <head>
                            <meta charset="UTF-8">
                        </head>
                        <body style="margin:0; padding:0; background-color:#f4f4f7; font-family: Arial, Helvetica, sans-serif;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7; padding:30px 0;">
                                <tr>
                                    <td align="center">
                                    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                                        <!-- Header -->
                                        <tr>
                                        <td style="background-color:#39b54a; padding:24px 32px;">
                                            <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:600;">Follow Up</h1>
                                            <p style="margin:4px 0 0; color:#c7d2fe; font-size:13px;">' . htmlspecialchars($location_name) . '</p>
                                        </td>
                                        </tr>

                                        <!-- Body -->
                                        <tr>
                                        <td style="padding:32px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:10px 0; border-bottom:1px solid #eef0f4; color:#111827; font-size:14px; font-weight:600;">' . htmlspecialchars($message) . '</td>
                                            </tr>
                                            </table>
                                        </td>
                                        </tr>

                                        <!-- Footer -->
                                        <tr>
                                        <td style="background-color:#f9fafb; padding:16px 32px; border-top:1px solid #eef0f4;">
                                            <p style="margin:0; color:#9ca3af; font-size:12px;">This reminder was sent from the ' . htmlspecialchars($location_name) . '.</p>
                                        </td>
                                        </tr>

                                    </table>
                                    </td>
                                </tr>
                            </table>
                        </body>
                    </html>';

    // Plain-text fallback for non-HTML email clients
    $mail->AltBody = $message;

    try {
        if (!$mail->send()) {
            return ['success' => false, 'message' => $mail->ErrorInfo];
        } else {
            return ['success' => true, 'message' => 'Email sent successfully'];
        }
    } catch (phpmailerException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
