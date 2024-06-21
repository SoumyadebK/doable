<?php
// Database connection
require_once('../global/config.php');
global $db;
global $db_account;

$PK_USER_MASTER = $_GET['master_id_customer'];
// Query to fetch data
$data = $db_account->Execute("SELECT CONCAT(DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, '-' ,DOA_ENROLLMENT_MASTER.ENROLLMENT_ID) AS ENROLLMENT, DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE, DOA_ENROLLMENT_PAYMENT.AMOUNT, DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER, DOA_PAYMENT_TYPE.PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.NOTE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE=DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.PAYMENT_STATUS = 'Success' AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER=".$PK_USER_MASTER);
/*echo "SELECT CONCAT(DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, '-' ,DOA_ENROLLMENT_MASTER.ENROLLMENT_ID) AS ENROLLMENT, DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE, DOA_ENROLLMENT_PAYMENT.AMOUNT, DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER, DOA_PAYMENT_TYPE.PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.NOTE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE=DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.PAYMENT_STATUS = 'Success' AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER=".$PK_USER_MASTER;
die();*/

// Name of the CSV file
$filename = 'export.csv';

// Open file in write mode ('w')
$file = fopen('php://output', 'w');

// Set headers to prompt download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="' . $filename . '"');

// Add column headers
$headers = array('Enrollment', 'Payment Schedule', 'Amount', 'Receipt Number', '', '', '', '', 'Receipt Number', 'Method', 'Memo'); // Adjust headers as needed
fputcsv($file, $headers);

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
    $enrollment_data[] = $data->fields['RECEIPT_NUMBER'];
    $enrollment_data[] = $data->fields['PAYMENT_TYPE'];
    $enrollment_data[] = $data->fields['NOTE'];
    fputcsv($file, $enrollment_data);
    $data->MoveNext();
}

// Close the file
fclose($file);
exit();
?>
