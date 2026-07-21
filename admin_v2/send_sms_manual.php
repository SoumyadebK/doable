<?php

use Twilio\Rest\Client;

// Turn off HTML error display
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Don't display errors in output
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log');

// Ensure no HTML output before JSON
ob_start();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header immediately
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($success, $message, $data = null)
{
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Auth check
if (!isset($_SESSION['PK_USER']) || $_SESSION['PK_USER'] == 0 || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    sendJsonResponse(false, 'Unauthorized');
}

$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
$send_to_all = isset($_POST['send_to_all']) ? intval($_POST['send_to_all']) : 0;

if (!$appointment_id) {
    sendJsonResponse(false, 'Missing appointment ID');
}

try {
    // Include config
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        require_once("global/config.php");
        require_once("global/vendor/twilio/sdk/src/Twilio/autoload.php");
    } else {
        require_once("/var/www/html/global/config.php");
        require_once("/var/www/html/global/vendor/twilio/sdk/src/Twilio/autoload.php");
    }

    global $db;

    // Get appointment details
    $appointment_query = "SELECT PK_LOCATION, DATE, START_TIME FROM DOA_APPOINTMENT_MASTER WHERE PK_APPOINTMENT_MASTER = " . $appointment_id;
    $appointment = $db_account->Execute($appointment_query);

    if (!$appointment || $appointment->RecordCount() == 0) {
        sendJsonResponse(false, 'Appointment not found');
    }

    $PK_LOCATION = $appointment->fields['PK_LOCATION'];
    $date = date('l, F j, Y', strtotime($appointment->fields['DATE']));
    $time = date('g:i A', strtotime($appointment->fields['START_TIME']));

    // Get location name
    $location_query = "SELECT LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION = " . $PK_LOCATION;
    $location = $db->Execute($location_query);

    if (!$location || $location->RecordCount() == 0) {
        sendJsonResponse(false, 'Location not found');
    }
    $location_name = $location->fields['LOCATION_NAME'];

    // Get Twilio settings
    [$SID, $TOKEN, $TWILIO_PHONE_NO] = getTwilioSettingData($PK_LOCATION);

    echo "Twilio Settings: SID=$SID, TOKEN=$TOKEN, PHONE=$TWILIO_PHONE_NO\n"; // Debugging line

    // Validate Twilio credentials
    if (empty($SID) || empty($TOKEN) || empty($TWILIO_PHONE_NO)) {
        sendJsonResponse(false, "Twilio Settings: SID=$SID, TOKEN=$TOKEN, PHONE=$TWILIO_PHONE_NO\n" . 'Twilio settings are incomplete. Please configure Twilio in the admin panel.');
    }

    // Function to send SMS
    function sendSmsToCustomer($db, $customer_id, $appointment_id, $location_name, $date, $time, $SID, $TOKEN, $TWILIO_PHONE_NO, $PK_LOCATION)
    {
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

    // Send SMS
    if ($send_to_all == 1) {
        // Send to all students
        $students_query = "SELECT PK_USER_MASTER FROM DOA_APPOINTMENT_CUSTOMER 
            WHERE PK_APPOINTMENT_MASTER = " . $appointment_id . " AND IS_PARTNER = 0";
        $students = $db->Execute($students_query);

        if (!$students || $students->RecordCount() == 0) {
            sendJsonResponse(false, 'No students found');
        }

        $success_count = 0;
        $fail_count = 0;
        $errors = [];

        while (!$students->EOF) {
            $result = sendSmsToCustomer(
                $db,
                $students->fields['PK_USER_MASTER'],
                $appointment_id,
                $location_name,
                $date,
                $time,
                $SID,
                $TOKEN,
                $TWILIO_PHONE_NO,
                $PK_LOCATION
            );

            if ($result['success']) {
                $success_count++;
            } else {
                $fail_count++;
                $errors[] = $result['message'];
            }
            $students->MoveNext();
        }

        $message = "Sent to $success_count students, failed: $fail_count";
        if ($fail_count > 0) {
            $message .= " - " . implode(", ", $errors);
        }

        sendJsonResponse($fail_count == 0, $message);
    } else {
        // Send to single customer
        if (!$customer_id) {
            sendJsonResponse(false, 'Missing customer ID');
        }

        $result = sendSmsToCustomer(
            $db,
            $customer_id,
            $appointment_id,
            $location_name,
            $date,
            $time,
            $SID,
            $TOKEN,
            $TWILIO_PHONE_NO,
            $PK_LOCATION
        );

        sendJsonResponse($result['success'], $result['message']);
    }
} catch (Exception $e) {
    sendJsonResponse(false, 'Error: ' . $e->getMessage());
}
