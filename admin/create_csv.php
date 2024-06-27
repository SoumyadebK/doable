<?php
// Database connection
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$PK_USER_MASTER = $_GET['master_id_customer'];

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_SERVICE AS APT_ENR_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.COMMENT,
                            DOA_APPOINTMENT_MASTER.IMAGE,
                            DOA_APPOINTMENT_MASTER.VIDEO,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            APT_ENR.ENROLLMENT_NAME AS APT_ENR_NAME,
                            APT_ENR.ENROLLMENT_ID AS APT_ENR_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ', ') AS SERVICE_PROVIDER_NAME,
                            GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                
                        LEFT JOIN DOA_APPOINTMENT_ENROLLMENT ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_ENROLLMENT.PK_APPOINTMENT_MASTER AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP'
                        LEFT JOIN DOA_ENROLLMENT_MASTER AS APT_ENR ON DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_MASTER = APT_ENR.PK_ENROLLMENT_MASTER AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP'
                                
                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL'
                                
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER=".$PK_USER_MASTER."
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC";

// Query to fetch data
$data = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, CONCAT(DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, '-' ,DOA_ENROLLMENT_MASTER.ENROLLMENT_ID) AS ENROLLMENT, DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE, DOA_ENROLLMENT_PAYMENT.AMOUNT, DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER, DOA_PAYMENT_TYPE.PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.NOTE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE=DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.PAYMENT_STATUS = 'Success' AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER=".$PK_USER_MASTER);

// Name of the CSV file
$filename = 'export.csv';

// Open file in write mode ('w')
$file = fopen('php://output', 'w');

// Set headers to prompt download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="' . $filename . '"');

// Add column headers
$headers_1 = array('Enrollment', 'Payment Schedule', 'Amount', 'Receipt Number', '', '', '', '',  '', 'Receipt Number', 'Method', 'Memo'); // Adjust headers as needed
fputcsv($file, $headers_1);

// Add data rows

while(!$data->EOF) {
    $enrollment_data = [];
    $enrollment_data[] = $data->fields['ENROLLMENT'];
    $enrollment_data[] = $data->fields['PAYMENT_DATE'];
    $enrollment_data[] = $data->fields['AMOUNT'];
    $enrollment_data[] = $data->fields['RECEIPT_NUMBER'];
    $enrollment_data[] = '';
    $enrollment_data[] = '';
    $enrollment_data[] = '';
    $enrollment_data[] = '';
    $enrollment_data[] = '';
    $enrollment_data[] = $data->fields['RECEIPT_NUMBER'];
    $enrollment_data[] = $data->fields['PAYMENT_TYPE'];
    $enrollment_data[] = $data->fields['NOTE'];
    fputcsv($file, $enrollment_data);
    $data->MoveNext();
}

$headers_2 = array('Service', 'Apt #', 'Service Code', 'Date', 'Time', 'Status', 'Session Cost'); // Adjust headers as needed
fputcsv($file, $headers_2);

$service_code_array = [];
$i=1;
$appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY);
while (!$appointment_data->EOF) {
    if ($appointment_data->fields['APPOINTMENT_TYPE'] === 'NORMAL') {
        $PK_ENROLLMENT_SERVICE = $appointment_data->fields['PK_ENROLLMENT_SERVICE'];
        $ENROLLMENT_ID = $appointment_data->fields['ENROLLMENT_ID'];
        $ENROLLMENT_NAME = $appointment_data->fields['ENROLLMENT_NAME'];
    } else {
        $PK_ENROLLMENT_SERVICE = $appointment_data->fields['APT_ENR_SERVICE'];
        $ENROLLMENT_ID = $appointment_data->fields['APT_ENR_NAME'];
        $ENROLLMENT_NAME = $appointment_data->fields['APT_ENR_ID'];
    }

    if($appointment_data->fields['APPOINTMENT_STATUS'] != 'Cancelled') {
        $enr_service_data = $db_account->Execute("SELECT NUMBER_OF_SESSION, PRICE_PER_SESSION, SESSION_CREATED, SESSION_COMPLETED FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_SERVICE` = " . $PK_ENROLLMENT_SERVICE);
        if ($enr_service_data->RecordCount() > 0) {
            if (isset($service_code_array[$PK_ENROLLMENT_SERVICE])) {
                $service_code_array[$PK_ENROLLMENT_SERVICE] = $service_code_array[$PK_ENROLLMENT_SERVICE] - 1;
            } else {
                $service_code_array[$PK_ENROLLMENT_SERVICE] = $enr_service_data->fields['SESSION_CREATED'];
            }
        }
    }

    $details = [];
    $details[] = $appointment_data->fields['SERVICE_NAME'];
    if($appointment_data->fields['APPOINTMENT_STATUS'] == 'Cancelled') {
        $details[] = '';
    } else {
        $details[] = (isset($service_code_array[$PK_ENROLLMENT_SERVICE])) ? $service_code_array[$PK_ENROLLMENT_SERVICE] . ' of ' . $enr_service_data->fields['NUMBER_OF_SESSION'] : '';
    }
    $details[] = $appointment_data->fields['SERVICE_CODE'];
    $details[] = date('m/d/Y', strtotime($appointment_data->fields['DATE']));
    $details[] = date('h:i A', strtotime($appointment_data->fields['START_TIME'])) . " - " . date('h:i A', strtotime($appointment_data->fields['END_TIME']));
    $details[] = $appointment_data->fields['APPOINTMENT_STATUS'];
    $details[] = (isset($service_code_array[$PK_ENROLLMENT_SERVICE])) ? $enr_service_data->fields['PRICE_PER_SESSION'] : '';
    fputcsv($file, $details);
    $appointment_data->MoveNext();
    $i++;
}

// Close the file
fclose($file);
exit();
?>
