<?php
// Database connection
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$type = !empty($_GET['type']) ? $_GET['type'] : 0;
$enr_condition = ' ';
if ($type == 'completed') {
    $enr_condition = " AND (DOA_ENROLLMENT_MASTER.STATUS = 'CO' || DOA_ENROLLMENT_MASTER.STATUS = 'C') ";
} elseif ($type == 'active') {
    $enr_condition = " AND (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') ";
}

$PK_USER_MASTER = $_GET['master_id_customer'];

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.PK_SERVICE_CODE,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER_NAME
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_ENROLLMENT ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_ENROLLMENT.PK_APPOINTMENT_MASTER
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER
                        %s
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A'
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC";

// Name of the CSV file
$filename = 'export.csv';

// Open file in write mode ('w')
$file = fopen('php://output', 'w');

// Set headers to prompt download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="' . $filename . '"');

$headers = array('Enrollment', 'Payment Schedule', 'Amount', 'Receipt Number', '', '', '', '',  '', 'Receipt Number', 'Method', 'Memo'); // Adjust headers as needed
fputcsv($file, $headers);

$enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, CONCAT(DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, '-' ,DOA_ENROLLMENT_MASTER.ENROLLMENT_ID) AS ENROLLMENT FROM `DOA_ENROLLMENT_MASTER` WHERE PK_LOCATION IN ($DEFAULT_LOCATION_ID) AND PK_USER_MASTER = $PK_USER_MASTER $enr_condition ORDER BY PK_ENROLLMENT_MASTER DESC");
while (!$enrollment_data->EOF) {
    $headers_1 = array('Enrollment', 'Payment Schedule', 'Amount', 'Receipt Number', '', '', '', '',  '', '', '', '', '',  ''); // Adjust headers as needed
    fputcsv($file, $headers_1);

    $payment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE, DOA_ENROLLMENT_PAYMENT.AMOUNT, DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER, DOA_PAYMENT_TYPE.PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.NOTE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE=DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.IS_REFUNDED = 0 AND DOA_ENROLLMENT_PAYMENT.PAYMENT_STATUS = 'Success' AND DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = " . $enrollment_data->fields['PK_ENROLLMENT_MASTER']);
    while (!$payment_data->EOF) {
        $enrollment_payment_data = [];
        $enrollment_payment_data[] = $enrollment_data->fields['ENROLLMENT'];
        $enrollment_payment_data[] = date('m/d/Y', strtotime($payment_data->fields['PAYMENT_DATE']));
        $enrollment_payment_data[] = $payment_data->fields['AMOUNT'];
        $enrollment_payment_data[] = $payment_data->fields['RECEIPT_NUMBER'];
        $enrollment_payment_data[] = '';
        $enrollment_payment_data[] = '';
        $enrollment_payment_data[] = '';
        $enrollment_payment_data[] = '';
        $enrollment_payment_data[] = '';
        $enrollment_payment_data[] = $payment_data->fields['RECEIPT_NUMBER'];
        $enrollment_payment_data[] = $payment_data->fields['PAYMENT_TYPE'];
        $enrollment_payment_data[] = $payment_data->fields['NOTE'];
        fputcsv($file, $enrollment_payment_data);
        $payment_data->MoveNext();
    }

    $headers_2 = array('Service', 'Apt #', 'Service Code', 'Date', 'Time', 'Status', $service_provider_title, 'Session Cost'); // Adjust headers as needed
    fputcsv($file, $headers_2);

    $service_code_array = [];
    $i = 1;
    $appointment_data = $db_account->Execute(sprintf($ALL_APPOINTMENT_QUERY, " WHERE (DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = " . $enrollment_data->fields['PK_ENROLLMENT_MASTER'] . " OR DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_MASTER = " . $enrollment_data->fields['PK_ENROLLMENT_MASTER'] . ") "));
    while (!$appointment_data->EOF) {
        $PK_ENROLLMENT_SERVICE = $appointment_data->fields['PK_ENROLLMENT_SERVICE'];

        if ($appointment_data->fields['APPOINTMENT_STATUS'] != 'Cancelled') {
            $SESSION_CREATED = getSessionCreatedCount($PK_ENROLLMENT_SERVICE, $appointment_data->fields['APPOINTMENT_TYPE']);
            $enr_service_data = $db_account->Execute("SELECT NUMBER_OF_SESSION, PRICE_PER_SESSION FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_SERVICE` = " . $PK_ENROLLMENT_SERVICE);
            if ($enr_service_data->RecordCount() > 0) {
                if (isset($service_code_array[$PK_ENROLLMENT_SERVICE])) {
                    $service_code_array[$PK_ENROLLMENT_SERVICE] = $service_code_array[$PK_ENROLLMENT_SERVICE] - 1;
                } else {
                    $service_code_array[$PK_ENROLLMENT_SERVICE] = $SESSION_CREATED;
                }
            }
        }

        $appointment_details = [];
        $appointment_details[] = $appointment_data->fields['SERVICE_NAME'];
        if ($appointment_data->fields['APPOINTMENT_STATUS'] == 'Cancelled') {
            $appointment_details[] = '';
        } else {
            $appointment_details[] = (isset($service_code_array[$PK_ENROLLMENT_SERVICE])) ? $service_code_array[$PK_ENROLLMENT_SERVICE] . ' of ' . $enr_service_data->fields['NUMBER_OF_SESSION'] : '';
        }
        $appointment_details[] = $appointment_data->fields['SERVICE_CODE'];
        $appointment_details[] = date('m/d/Y', strtotime($appointment_data->fields['DATE']));
        $appointment_details[] = date('h:i A', strtotime($appointment_data->fields['START_TIME'])) . " - " . date('h:i A', strtotime($appointment_data->fields['END_TIME']));
        $appointment_details[] = $appointment_data->fields['APPOINTMENT_STATUS'];
        $appointment_details[] = $appointment_data->fields['SERVICE_PROVIDER_NAME'];
        $appointment_details[] = (isset($service_code_array[$PK_ENROLLMENT_SERVICE])) ? $enr_service_data->fields['PRICE_PER_SESSION'] : '';
        fputcsv($file, $appointment_details);
        $appointment_data->MoveNext();
        $i++;
    }

    $blank_row1 = array('', '', '', '',  '', '', '', '', '',  '', '', '', '', '',  ''); // Blank Row
    fputcsv($file, $blank_row1);
    $blank_row2 = array('', '', '', '',  '', '', '', '', '',  '', '', '', '', '',  ''); // Blank Row
    fputcsv($file, $blank_row2);

    $enrollment_data->MoveNext();
}

// Close the file
fclose($file);
exit();
?>
