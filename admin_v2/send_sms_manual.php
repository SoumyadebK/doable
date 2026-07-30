<?php

use Twilio\Rest\Client;

require_once("../global/config.php");
global $db;
global $db_account;

require_once("../global/vendor/twilio/sdk/src/Twilio/autoload.php");
require_once('../global/phpmailer/class.phpmailer.php');

$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
$send_to_all = isset($_POST['send_to_all']) ? intval($_POST['send_to_all']) : 0;
$reminder_type = isset($_POST['reminder_type']) ? $_POST['reminder_type'] : '';


// Get appointment details
$appointment_query = "SELECT PK_LOCATION, DATE, START_TIME FROM DOA_APPOINTMENT_MASTER WHERE PK_APPOINTMENT_MASTER = " . $appointment_id;
$appointment = $db_account->Execute($appointment_query);

if (!$appointment || $appointment->RecordCount() == 0) {
    $return_data['success'] = false;
    $return_data['message'] = 'Appointment not found';
    echo json_encode($return_data);
}

$PK_LOCATION = $appointment->fields['PK_LOCATION'];
$date = date('l, F j, Y', strtotime($appointment->fields['DATE']));
$time = date('g:i A', strtotime($appointment->fields['START_TIME']));

// Get location name
$location_query = "SELECT LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION = " . $PK_LOCATION;
$location = $db->Execute($location_query);
$location_name = $location->fields['LOCATION_NAME'];

// Send to all students
$students_query = "SELECT PK_USER_MASTER FROM DOA_APPOINTMENT_CUSTOMER 
            WHERE PK_APPOINTMENT_MASTER = " . $appointment_id . " AND IS_PARTNER = 0";
$students = $db_account->Execute($students_query);

if (!$students || $students->RecordCount() == 0) {
    $return_data['success'] = false;
    $return_data['message'] = 'No students found';
    echo json_encode($return_data);
}

if ($send_to_all == 1) {
    $success_count = 0;
    $fail_count = 0;
    $errors = [];

    while (!$students->EOF) {
        if ($reminder_type == 'sms') {
            $result = sendSmsToCustomer($db, $students->fields['PK_USER_MASTER'], $location_name, $date, $time, $PK_LOCATION);

            if ($result['success']) {
                $success_count++;
            } else {
                $fail_count++;
                $errors[] = $result['message'];
            }
        } else {
            $result = sendEmailToCustomer($db, $students->fields['PK_USER_MASTER'], $location_name, $date, $time);

            if ($result['success']) {
                $success_count++;
            } else {
                $fail_count++;
                $errors[] = $result['message'];
            }
        }

        $students->MoveNext();
    }

    $message = "Sent to $success_count students, failed: $fail_count";
    if ($fail_count > 0) {
        $message .= " - " . implode(", ", $errors);
    }

    $return_data['success'] = $fail_count == 0;
    $return_data['message'] = $message;
    echo json_encode($return_data);
} else {
    if ($reminder_type == 'sms') {
        $result = sendSmsToCustomer($db, $students->fields['PK_USER_MASTER'], $location_name, $date, $time, $PK_LOCATION);
    } else {
        $result = sendEmailToCustomer($db, $students->fields['PK_USER_MASTER'], $location_name, $date, $time);
    }

    $return_data['success'] = $result['success'];
    $return_data['message'] = $result['message'];
    echo json_encode($return_data);
}


// Function to send SMS
function sendSmsToCustomer($db, $customer_id, $location_name, $date, $time, $PK_LOCATION)
{
    [$SID, $TOKEN, $TWILIO_PHONE_NO] = getTwilioSettingData($PK_LOCATION);
    // Get customer details
    $customer_query = "SELECT 
            DOA_USERS.PHONE, 
            DOA_USERS.FIRST_NAME,
            DOA_USERS.LAST_NAME 
        FROM DOA_USERS 
        INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER 
        WHERE DOA_USERS.IS_DELETED = 0 
        AND DOA_USERS.ACTIVE = 1 
        AND DOA_USERS.PK_USER = " . intval($customer_id);

    $customer = $db->Execute($customer_query);

    if (!$customer || $customer->RecordCount() == 0) {
        return ['success' => false, 'message' => 'Customer not found'];
    }

    $phone = preg_replace('/[^0-9]/', '', $customer->fields['PHONE']);
    $customer_name = trim($customer->fields['FIRST_NAME'] . ' ' . $customer->fields['LAST_NAME']);

    // Remove leading 1 if present
    if (strlen($phone) == 11 && substr($phone, 0, 1) == '1') {
        $phone = substr($phone, 1);
    }

    $message = "Hi $customer_name, this is a reminder for your appointment at $location_name on $date at $time. Thank you!";

    try {
        $client = new Client($SID, $TOKEN);
        $response = $client->messages->create(
            '+1' . $phone,
            [
                'from' => $TWILIO_PHONE_NO,
                'body' => $message
            ]
        );

        // Log success
        $log_query = "INSERT INTO DOA_SMS_LOG 
                (IS_ERROR, ERROR_MESSAGE, PK_LOCATION, PK_USER_MASTER, PHONE_NUMBER, MESSAGE, TRIGGER_TIME) 
                VALUES (0, '', " . intval($PK_LOCATION) . ", " . intval($customer_id) . ", 
                '$phone', '" . addslashes($message) . "', '" . date('Y-m-d H:i:s') . "')";
        $db->Execute($log_query);

        return ['success' => true, 'message' => 'SMS sent successfully'];
    } catch (Exception $e) {
        // Log error
        $error_message = addslashes($e->getMessage());
        $log_query = "INSERT INTO DOA_SMS_LOG 
                (IS_ERROR, ERROR_MESSAGE, PK_LOCATION, PK_USER_MASTER, PHONE_NUMBER, MESSAGE, TRIGGER_TIME) 
                VALUES (1, '$error_message', " . intval($PK_LOCATION) . ", 
                " . intval($customer_id) . ", '$phone', '" . addslashes($message) . "', '" . date('Y-m-d H:i:s') . "')";
        $db->Execute($log_query);

        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function sendEmailToCustomer($db, $customer_id, $location_name, $date, $time)
{

    // Get customer details
    $customer_query = "SELECT 
            DOA_USERS.PHONE, 
            DOA_USERS.FIRST_NAME,
            DOA_USERS.LAST_NAME 
            DOA_USERS.EMAIL_ID,
        FROM DOA_USERS 
        INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER 
        WHERE DOA_USERS.IS_DELETED = 0 
        AND DOA_USERS.ACTIVE = 1 
        AND DOA_USERS.PK_USER = " . intval($customer_id);

    $customer = $db->Execute($customer_query);

    if (!$customer || $customer->RecordCount() == 0) {
        return ['success' => false, 'message' => 'Customer not found'];
    }

    $phone = preg_replace('/[^0-9]/', '', $customer->fields['PHONE']);
    $customer_name = trim($customer->fields['FIRST_NAME'] . ' ' . $customer->fields['LAST_NAME']);
    $email = trim($customer->fields['EMAIL_ID']);

    // Remove leading 1 if present
    if (strlen($phone) == 11 && substr($phone, 0, 1) == '1') {
        $phone = substr($phone, 1);
    }

    $message = "Hi $customer_name, this is a reminder for your appointment at $location_name on $date at $time. Thank you!";

    $hostname = 'smtp.protonmail.ch';
    $port = '587';
    $userName = 'demo@doable.net';
    $SendingPwd = '9B76V5Q2NPY7524W';

    $To = $email;
    $Subject = "Appointment Reminder from Doable";

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
    $mail->setFrom($userName, "Doable");
    $mail->addAddress($To, "Doable");  //Set who the message is to be sent to.
    //Set the subject line
    $mail->Subject = $Subject;

    // Tell PHPMailer this is an HTML email
    $mail->IsHTML(true);

    $mail->Body = '
                  <!DOCTYPE html>
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
                                <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:600;">Appointment Reminder</h1>
                                <p style="margin:4px 0 0; color:#c7d2fe; font-size:13px;">Doable Website</p>
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
                                <p style="margin:0; color:#9ca3af; font-size:12px;">This reminder was sent from the Doable website.</p>
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
