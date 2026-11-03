<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

$response = ['success' => false, 'message' => ''];

if (isset($_POST['action']) && $_POST['action'] == 'send_email') {
    require_once('../../global/phpmailer/class.phpmailer.php');

    $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $email_subject = isset($_POST['email_subject']) ? htmlspecialchars($_POST['email_subject']) : '';
    $email_message = isset($_POST['email_message']) ? htmlspecialchars($_POST['email_message']) : '';
    $email_type = isset($_POST['email_type']) ? $_POST['email_type'] : 'appointment'; // appointment, group_class, special_appointment

    if (empty($email_subject) || empty($email_message)) {
        $response['message'] = 'Subject and message are required.';
        echo json_encode($response);
        exit;
    }

    // Get customer details
    $customer_query = $db->Execute("SELECT EMAIL_ID, CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME, PHONE FROM DOA_USERS WHERE PK_USER = '$customer_id'");

    if ($customer_query->RecordCount() == 0) {
        $response['message'] = 'Customer not found.';
        echo json_encode($response);
        exit;
    }

    $customer_email = $customer_query->fields['EMAIL_ID'];
    $customer_name = $customer_query->fields['NAME'];
    $customer_phone = $customer_query->fields['PHONE'];

    if (empty($customer_email) || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid or missing customer email address.';
        echo json_encode($response);
        exit;
    }

    // Get appointment details for the email body
    $appointment_details = '';
    if ($appointment_id > 0) {
        if ($email_type == 'appointment') {
            $appt_query = "SELECT 
                            DOA_APPOINTMENT_MASTER.*,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            GROUP_CONCAT(SERVICE_PROVIDER.PK_USER SEPARATOR ',') AS SERVICE_PROVIDER_ID
                        FROM DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = $appointment_id";

            $appt_data = $db_account->Execute($appt_query);
            if ($appt_data->RecordCount() > 0) {
                $service_provider_id = $appt_data->fields['SERVICE_PROVIDER_ID'];
                $provider_query = $db->Execute("SELECT CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS WHERE PK_USER = '$service_provider_id'");
                $provider_name = $provider_query->fields['NAME'];

                $appointment_details = "
                    <h3>Appointment Details:</h3>
                    <table style='width:100%; border-collapse:collapse; margin:15px 0;'>
                        <tr><td style='padding:8px; border-bottom:1px solid #eef0f4;'><strong>Service:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #eef0f4;'>" . htmlspecialchars($appt_data->fields['SERVICE_NAME']) . "</td></tr>
                        <tr><td style='padding:8px; border-bottom:1px solid #eef0f4;'><strong>Service Code:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #eef0f4;'>" . htmlspecialchars($appt_data->fields['SERVICE_CODE']) . "</td></tr>
                        <tr><td style='padding:8px; border-bottom:1px solid #eef0f4;'><strong>Date:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #eef0f4;'>" . date('l, M d, Y', strtotime($appt_data->fields['DATE'])) . "</td></tr>
                        <tr><td style='padding:8px; border-bottom:1px solid #eef0f4;'><strong>Time:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #eef0f4;'>" . date('h:i A', strtotime($appt_data->fields['START_TIME'])) . " - " . date('h:i A', strtotime($appt_data->fields['END_TIME'])) . "</td></tr>
                        <tr><td style='padding:8px; border-bottom:1px solid #eef0f4;'><strong>Provider:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #eef0f4;'>" . htmlspecialchars($provider_name) . "</td></tr>
                        <tr><td style='padding:8px;'><strong>Status:</strong></td>
                            <td style='padding:8px;'>" . htmlspecialchars($appt_data->fields['APPOINTMENT_STATUS']) . "</td></tr>
                    </table>
                ";
            }
        } elseif ($email_type == 'group_class') {
            $appt_query = "SELECT 
                            DOA_APPOINTMENT_MASTER.*,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            GROUP_CONCAT(SERVICE_PROVIDER.PK_USER SEPARATOR ',') AS SERVICE_PROVIDER_ID
                        FROM DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = $appointment_id";

            $appt_data = $db_account->Execute($appt_query);
            if ($appt_data->RecordCount() > 0) {
                $service_provider_id = $appt_data->fields['SERVICE_PROVIDER_ID'];
                $provider_query = $db->Execute("SELECT CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS WHERE PK_USER = '$service_provider_id'");
                $provider_name = $provider_query->fields['NAME'];

                $group_name = !empty($appt_data->fields['GROUP_NAME']) ? $appt_data->fields['GROUP_NAME'] : 'Group Class';

                $appointment_details = "
                    <h3>Group Class Details:</h3>
                    <table style='width:100%; border-collapse:collapse; margin:15px 0;'>
                        <tr><td style='padding:8px; border-bottom:1px solid #eef0f4;'><strong>Group:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #eef0f4;'>" . htmlspecialchars($group_name) . "</td></tr>
                        <tr><td style='padding:8px; border-bottom:1px solid #eef0f4;'><strong>Service:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #eef0f4;'>" . htmlspecialchars($appt_data->fields['SERVICE_NAME']) . "</td></tr>
                        <tr><td style='padding:8px; border-bottom:1px solid #eef0f4;'><strong>Service Code:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #eef0f4;'>" . htmlspecialchars($appt_data->fields['SERVICE_CODE']) . "</td></tr>
                        <tr><td style='padding:8px; border-bottom:1px solid #eef0f4;'><strong>Date:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #eef0f4;'>" . date('l, M d, Y', strtotime($appt_data->fields['DATE'])) . "</td></tr>
                        <tr><td style='padding:8px; border-bottom:1px solid #eef0f4;'><strong>Time:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #eef0f4;'>" . date('h:i A', strtotime($appt_data->fields['START_TIME'])) . " - " . date('h:i A', strtotime($appt_data->fields['END_TIME'])) . "</td></tr>
                        <tr><td style='padding:8px;'><strong>Instructor:</strong></td>
                            <td style='padding:8px;'>" . htmlspecialchars($provider_name) . "</td></tr>
                    </table>
                ";
            }
        }
    }

    // Email configuration
    $hostname = 'smtp.protonmail.ch';
    $port = '587';
    $userName = 'demo@doable.net';
    $SendingPwd = '9B76V5Q2NPY7524W';

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
    $mail->addAddress("roumya.karmakar.01@gmail.com", $customer_name);
    $mail->Subject = $email_subject;

    // Build email body with appointment details
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
                                <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:600;">' . htmlspecialchars($email_subject) . '</h1>
                                <p style="margin:4px 0 0; color:#c7d2fe; font-size:13px;">Doable Appointment System</p>
                            </td>
                        </tr>
                        
                        <!-- Body -->
                        <tr>
                            <td style="padding:32px;">
                                <p style="color:#111827; font-size:14px; line-height:1.6; margin-bottom:20px;">
                                    Dear ' . htmlspecialchars($customer_name) . ',
                                </p>
                                
                                <div style="background-color:#f9fafb; padding:20px; border-radius:4px; margin-bottom:20px;">
                                    ' . nl2br(htmlspecialchars($email_message)) . '
                                </div>
                                
                                ' . $appointment_details . '
                                
                                <p style="color:#6b7280; font-size:13px; margin-top:20px;">
                                    If you have any questions, please don\'t hesitate to contact us.
                                </p>
                                <p style="color:#6b7280; font-size:13px;">
                                    Best regards,<br>
                                    <strong>Doable Team</strong>
                                </p>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style="background-color:#f9fafb; padding:16px 32px; border-top:1px solid #eef0f4;">
                                <p style="margin:0; color:#9ca3af; font-size:12px;">This email was sent from the Doable system regarding your appointment.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';

    $mail->AltBody = strip_tags($email_message) . "\n\n" . strip_tags(str_replace(['<br>', '<tr>', '<td>', '</tr>', '</td>'], "\n", $appointment_details));

    try {
        if ($mail->send()) {
            $response['success'] = true;
            $response['message'] = 'Email sent successfully to ' . htmlspecialchars($customer_email);
        } else {
            $response['message'] = 'Failed to send email: ' . $mail->ErrorInfo;
        }
    } catch (phpmailerException $e) {
        $response['message'] = 'Email error: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
