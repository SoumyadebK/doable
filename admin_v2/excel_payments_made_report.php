<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "PAYMENTS MADE REPORT";

$week_number = '';
$from_date = '';
$to_date = '';

if (empty($_GET['week_number'])) {
    $week_number = $_GET['week_number'];
    $YEAR = date('Y');

    $from_date = date('Y-m-d', strtotime($_GET['start_date']));
    $to_date = date('Y-m-d', strtotime($_GET['end_date']));

    $payment_date = "AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
    $enrollment_date = "AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
    $appointment_date = "AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
}

$account_data = $db->Execute("SELECT BUSINESS_NAME, FRANCHISE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';
if (preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $business_name)) {
    $business_name = '';
} else {
    $business_name = '' . $business_name;
}

$location_name = '';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$totalResults = count($resultsArray);
$concatenatedResults = "";
foreach ($resultsArray as $key => $result) {
    $concatenatedResults .= $result;
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

$cell1  = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
define('EOL', (PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

$total_fields = 70;
for ($i = 0; $i <= $total_fields; $i++) {
    if ($i <= 25)
        $cell[] = $cell1[$i];
    else {
        $j = floor($i / 26) - 1;
        $k = ($i % 26);
        $cell[] = $cell1[$j] . $cell1[$k];
    }
}

$inputFileType  = 'Excel2007';
$outputFileName = 'PAYMENT_MADE_REPORT.xlsx';

$objReader      = PHPExcel_IOFactory::createReader($inputFileType);
$objReader->setIncludeCharts(TRUE);
$objPHPExcel     = new PHPExcel();
$objWriter         = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("F")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("G")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("H")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("I")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("J")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("K")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("L")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("M")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("N")->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension("O")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("P")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("Q")->setWidth(13);

$objPHPExcel->getActiveSheet()->mergeCells('A1:Q1');

$cell_no = "A1";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($title);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);
$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:J2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' . " (" . $concatenatedResults . ")");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "K2";
$objPHPExcel->getActiveSheet()->mergeCells('K2:Q2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('(' . date('m/d/Y', strtotime($from_date)) . ' - ' . date('m/d/Y', strtotime($to_date)) . ')');
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A2:Q2')->applyFromArray($styleArray);

$cell_no = "A3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Payment Date");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "B3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Payment Amount");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "C3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Payment Title");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Payment Method");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "E3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Card Type");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Receipt");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "G3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("MEMO");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Client");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "I3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Enrollment Name");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "J3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Enrollment Date");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "K3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Enrollment Type");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "L3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Enrollment Cost");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "M3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Enrollment Balance");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "N3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Closer");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "O3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Teacher1");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "P3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Teacher2");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "Q3";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Teacher3");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->getStyle('A3:Q3')->applyFromArray($styleArray);

$i = 4;

// Get all payments first and separate regular payments from refunds
$all_payments = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.TYPE, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.MISC_ID, ENROLLMENT_DATE, ENROLLMENT_TYPE, TOTAL_AMOUNT, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_USERS.IS_DELETED =0 AND IS_REFUNDED = 0 AND DOA_ENROLLMENT_PAYMENT.NOT_EXPORT_TO_AMI = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $payment_date . " ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC");

// Get gift certificate payments (both active and refunded)
$gift_payments = $db_account->Execute("SELECT
    DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_PAYMENT,
    DOA_ENROLLMENT_PAYMENT.PK_GIFT_CERTIFICATE_MASTER,
    DOA_PAYMENT_TYPE.PAYMENT_TYPE,
    DOA_ENROLLMENT_PAYMENT.TYPE,
    DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE,
    DOA_ENROLLMENT_PAYMENT.AMOUNT,
    DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO,
    DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE,
    DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER,
    DOA_ENROLLMENT_PAYMENT.IS_REFUNDED,
    DOA_PAYMENT_TYPE.PAYMENT_TYPE AS PAYMENT_TYPE_NAME,
    NULL AS ENROLLMENT_NAME,
    NULL AS ENROLLMENT_ID,
    NULL AS MISC_ID,
    NULL AS ENROLLMENT_DATE,
    NULL AS ENROLLMENT_TYPE,
    NULL AS TOTAL_AMOUNT,
    NULL AS ENROLLMENT_BY_ID,
    NULL AS PK_USER_MASTER,
    NULL AS CLIENT
FROM
    DOA_ENROLLMENT_PAYMENT
INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE
ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE
LEFT JOIN DOA_GIFT_CERTIFICATE_MASTER AS DOA_GIFT_CERTIFICATE_MASTER
ON DOA_ENROLLMENT_PAYMENT.PK_GIFT_CERTIFICATE_MASTER = DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER
WHERE (DOA_ENROLLMENT_PAYMENT.TYPE = 'Gift Certificate' OR DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund Gift Certificate')
AND DOA_ENROLLMENT_PAYMENT.NOT_EXPORT_TO_AMI = 0 
AND DOA_ENROLLMENT_PAYMENT.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
" . $payment_date . " 
ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC");

// Separate regular payments and refunds
$regular_payments = [];
$refund_payments = [];

// Process regular payments and refunds
while (!$all_payments->EOF) {
    if ($all_payments->fields['TYPE'] == 'Refund') {
        $refund_payments[] = $all_payments->fields;
    }
    if ($all_payments->fields['TYPE'] == 'Payment') {
        $regular_payments[] = $all_payments->fields;
    }
    $all_payments->MoveNext();
}

// Process gift certificate payments based on IS_REFUNDED flag
while (!$gift_payments->EOF) {
    $gift_data = $gift_payments->fields;
    if ($gift_data['TYPE'] == 'Refund Gift Certificate') {
        $refund_payments[] = $gift_data;
    } else {
        $regular_payments[] = $gift_data;
    }
    $gift_payments->MoveNext();
}

// Get wallet payments
$total_wallet = 0;
$wallet_payments = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.*, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, DOA_PAYMENT_TYPE.PAYMENT_TYPE, DOA_CUSTOMER_WALLET.BALANCE_LEFT FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_CUSTOMER_WALLET ON DOA_ENROLLMENT_PAYMENT.PK_CUSTOMER_WALLET = DOA_CUSTOMER_WALLET.PK_CUSTOMER_WALLET LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_CUSTOMER_WALLET.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE = DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.TYPE = 'Wallet' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO != 'Gift Certificate' AND DOA_ENROLLMENT_PAYMENT.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC");

// Display wallet payments first
while (!$wallet_payments->EOF) {
    $total_wallet += $wallet_payments->fields['AMOUNT'];
    if ($wallet_payments->fields['BALANCE_LEFT'] > 0) {

        $cell_no = "A" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(date('m/d/Y', strtotime($wallet_payments->fields['PAYMENT_DATE'])));
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "B" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('$' . $wallet_payments->fields['BALANCE_LEFT']);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $cell_no = "C" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('Wallet');
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "D" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($wallet_payments->fields['PAYMENT_TYPE']);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "E" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-');
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "F" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($wallet_payments->fields['RECEIPT_NUMBER']);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "G" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($wallet_payments->fields['MEMO'] ?? '-');
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "H" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($wallet_payments->fields['CLIENT']);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "I" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-');
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "J" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-');
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "K" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-');
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "L" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-');
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $cell_no = "M" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-');
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $cell_no = "N" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-');
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "O" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-');
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "P" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-');
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "Q" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-');
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':Q' . $i)->applyFromArray($styleArray);

        $i++;
    }
    $wallet_payments->MoveNext();
}

// Display regular payments
$j = 1;
$total_amount = 0;
$total_refund = 0;

foreach ($regular_payments as $payment) {
    $name = $payment['ENROLLMENT_NAME'] ?? '';
    if (empty($name)) {
        $enrollment_name = '';
    } else {
        $enrollment_name = "$name" . " - ";
    }

    $PK_USER_MASTER = $payment['PK_USER_MASTER'] ?? '';

    // Check if this is a gift certificate payment
    $is_gift_certificate = ($payment['TYPE'] == 'Gift Certificate' || $payment['TYPE'] == 'Refund Gift Certificate');

    if (!$is_gift_certificate && !empty($payment['ENROLLMENT_BY_ID'])) {
        $enrollment_by = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLOSER FROM DOA_USERS WHERE PK_USER = " . $payment['ENROLLMENT_BY_ID']);
    } else {
        $enrollment_by = null;
    }

    if (!$is_gift_certificate && !empty($payment['PK_ENROLLMENT_MASTER'])) {
        $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER LEFT JOIN $account_database.DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $payment['PK_ENROLLMENT_MASTER']);
    } else {
        $service_provider = null;
    }

    $teacher = [];
    if ($service_provider && $service_provider->RecordCount() > 0) {
        while (!$service_provider->EOF) {
            $teacher[] = $service_provider->fields['TEACHER'];
            $service_provider->MoveNext();
        }
    }

    $enrollment_balance = !empty($payment['TOTAL_AMOUNT']) ? $payment['TOTAL_AMOUNT'] - $payment['AMOUNT'] : 0;

    // Payment type logic
    if ($is_gift_certificate) {
        $payment_type = 'Gift Certificate';
        $enrollment_name = '';
        $ENROLLMENT_ID = '';
        $MISC_ID = '';
        $client_name = '';
        $total_amount_display = '';
        $enrollment_date_display = '';
        $enrollment_type_display = '';
        $enrollment_balance_display = '';
        $total_amount += $payment['AMOUNT'];
    } elseif ($payment['TYPE'] == 'Move') {
        $payment_type = 'Wallet';
        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
        $MISC_ID = $payment['MISC_ID'] ?? '';
        $client_name = $payment['CLIENT'] ?? '';
        $total_amount_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'], 2) : '';
        $enrollment_date_display = !empty($payment['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($payment['ENROLLMENT_DATE'])) : '';
        $enrollment_type_display = !empty($payment['ENROLLMENT_TYPE']) ? $payment['ENROLLMENT_TYPE'] : '';
        $enrollment_balance_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'] - $payment['AMOUNT'], 2) : '';
        $total_amount += $payment['AMOUNT'];
    } elseif ($payment['PK_PAYMENT_TYPE'] == '2') {
        $payment_info = json_decode($payment['PAYMENT_INFO']);
        $payment_type = $payment['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
        $MISC_ID = $payment['MISC_ID'] ?? '';
        $client_name = $payment['CLIENT'] ?? '';
        $total_amount_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'], 2) : '';
        $enrollment_date_display = !empty($payment['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($payment['ENROLLMENT_DATE'])) : '';
        $enrollment_type_display = !empty($payment['ENROLLMENT_TYPE']) ? $payment['ENROLLMENT_TYPE'] : '';
        $enrollment_balance_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'] - $payment['AMOUNT'], 2) : '';
        $total_amount += $payment['AMOUNT'];
    } elseif (in_array($payment['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
        $payment_info = json_decode($payment['PAYMENT_INFO']);
        $payment_type = $payment['PAYMENT_TYPE'] . " # " . ((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
        $MISC_ID = $payment['MISC_ID'] ?? '';
        $client_name = $payment['CLIENT'] ?? '';
        $total_amount_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'], 2) : '';
        $enrollment_date_display = !empty($payment['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($payment['ENROLLMENT_DATE'])) : '';
        $enrollment_type_display = !empty($payment['ENROLLMENT_TYPE']) ? $payment['ENROLLMENT_TYPE'] : '';
        $enrollment_balance_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'] - $payment['AMOUNT'], 2) : '';
        $total_amount += $payment['AMOUNT'];
    } else {
        $payment_type = $payment['PAYMENT_TYPE'];
        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
        $MISC_ID = $payment['MISC_ID'] ?? '';
        $client_name = $payment['CLIENT'] ?? '';
        $total_amount_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'], 2) : '';
        $enrollment_date_display = !empty($payment['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($payment['ENROLLMENT_DATE'])) : '';
        $enrollment_type_display = !empty($payment['ENROLLMENT_TYPE']) ? $payment['ENROLLMENT_TYPE'] : '';
        $enrollment_balance_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'] - $payment['AMOUNT'], 2) : '';
        $total_amount += $payment['AMOUNT'];
    }

    // For non-gift certificate payments, set enrollment name
    if (!$is_gift_certificate) {
        $name = $payment['ENROLLMENT_NAME'] ?? '';
        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
        $MISC_ID = $payment['MISC_ID'] ?? '';
        if (empty($name)) {
            $enrollment_name = '';
        } else {
            $enrollment_name = "$name" . " - ";
        }
        $client_name = $payment['CLIENT'] ?? '';
    }

    $cell_no = "A" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(date('m/d/Y', strtotime($payment['PAYMENT_DATE'])));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "B" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('$' . $payment['AMOUNT']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    $cell_no = "C" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($payment_type);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "D" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($payment['PAYMENT_TYPE']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "E" . $i;
    if ($payment['PAYMENT_TYPE'] == 'Credit Card' || $payment['PAYMENT_TYPE'] == 'Visa' || $payment['PAYMENT_TYPE'] == 'Master Card' || $payment['PAYMENT_TYPE'] == 'American Express' || $payment['PAYMENT_TYPE'] == 'Card' || $payment['PAYMENT_TYPE'] == 'Card On File') {
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($payment['PAYMENT_TYPE']);
    } else {
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-');
    }
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "F" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($payment['RECEIPT_NUMBER']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "G" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(empty($payment['MEMO']) ? '' : $payment['MEMO']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "H" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($client_name);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "I" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $MISC_ID : $enrollment_name . $ENROLLMENT_ID);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "J" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($enrollment_date_display);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "K" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($enrollment_type_display);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "L" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($total_amount_display);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    $cell_no = "M" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($enrollment_balance_display);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    $cell_no = "N" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(!empty($enrollment_by->fields['CLOSER']) ? $enrollment_by->fields['CLOSER'] : '');
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "O" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(isset($teacher[0]) ? $teacher[0] : '');
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "P" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(isset($teacher[1]) ? $teacher[1] : '');
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "Q" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(isset($teacher[2]) ? $teacher[2] : '');
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':Q' . $i)->applyFromArray($styleArray);

    $j++;
    $i++;
}

// Display all refunds at the bottom
foreach ($refund_payments as $refund) {
    // Check if this is a gift certificate refund
    $is_gift_refund = ($refund['TYPE'] == 'Refund Gift Certificate');

    $name = $refund['ENROLLMENT_NAME'] ?? '';
    if (empty($name)) {
        $enrollment_name = '';
    } else {
        $enrollment_name = "$name" . " - ";
    }
    $total_refund += $refund['AMOUNT'];
    $PK_USER_MASTER = $refund['PK_USER_MASTER'] ?? '';

    if (!$is_gift_refund && !empty($refund['ENROLLMENT_BY_ID'])) {
        $enrollment_by = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLOSER FROM DOA_USERS WHERE PK_USER = " . $refund['ENROLLMENT_BY_ID']);
    } else {
        $enrollment_by = null;
    }

    if (!$is_gift_refund && !empty($refund['PK_ENROLLMENT_MASTER'])) {
        $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER LEFT JOIN $account_database.DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $refund['PK_ENROLLMENT_MASTER']);
    } else {
        $service_provider = null;
    }

    $teacher = [];
    if ($service_provider && $service_provider->RecordCount() > 0) {
        while (!$service_provider->EOF) {
            $teacher[] = $service_provider->fields['TEACHER'];
            $service_provider->MoveNext();
        }
    }

    $enrollment_balance = !empty($refund['TOTAL_AMOUNT']) ? $refund['TOTAL_AMOUNT'] - $refund['AMOUNT'] : 0;

    // Payment type logic for refunds
    if ($is_gift_refund) {
        $refund_payment_type = 'Gift Certificate Refund';
        $enrollment_name = '';
        $ENROLLMENT_ID = '';
        $MISC_ID = '';
        $client_name = '';
        $total_amount_display = '';
        $enrollment_date_display = '';
        $enrollment_type_display = '';
        $enrollment_balance_display = '';
    } elseif ($refund['PK_PAYMENT_TYPE'] == '2') {
        $payment_info = json_decode($refund['PAYMENT_INFO']);
        $refund_payment_type = $refund['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
        $ENROLLMENT_ID = $refund['ENROLLMENT_ID'] ?? '';
        $MISC_ID = $refund['MISC_ID'] ?? '';
        $client_name = $refund['CLIENT'] ?? '';
        $total_amount_display = !empty($refund['TOTAL_AMOUNT']) ? '$' . number_format($refund['TOTAL_AMOUNT'], 2) : '';
        $enrollment_date_display = !empty($refund['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($refund['ENROLLMENT_DATE'])) : '';
        $enrollment_type_display = !empty($refund['ENROLLMENT_TYPE']) ? $refund['ENROLLMENT_TYPE'] : '';
        $enrollment_balance_display = !empty($refund['TOTAL_AMOUNT']) ? '$' . number_format($refund['TOTAL_AMOUNT'] - $refund['AMOUNT'], 2) : '';
    } elseif (in_array($refund['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
        $payment_info = json_decode($refund['PAYMENT_INFO']);
        $refund_payment_type = $refund['PAYMENT_TYPE'] . " # " . ((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
        $ENROLLMENT_ID = $refund['ENROLLMENT_ID'] ?? '';
        $MISC_ID = $refund['MISC_ID'] ?? '';
        $client_name = $refund['CLIENT'] ?? '';
        $total_amount_display = !empty($refund['TOTAL_AMOUNT']) ? '$' . number_format($refund['TOTAL_AMOUNT'], 2) : '';
        $enrollment_date_display = !empty($refund['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($refund['ENROLLMENT_DATE'])) : '';
        $enrollment_type_display = !empty($refund['ENROLLMENT_TYPE']) ? $refund['ENROLLMENT_TYPE'] : '';
        $enrollment_balance_display = !empty($refund['TOTAL_AMOUNT']) ? '$' . number_format($refund['TOTAL_AMOUNT'] - $refund['AMOUNT'], 2) : '';
    } else {
        $refund_payment_type = $refund['PAYMENT_TYPE'];
        $ENROLLMENT_ID = $refund['ENROLLMENT_ID'] ?? '';
        $MISC_ID = $refund['MISC_ID'] ?? '';
        $client_name = $refund['CLIENT'] ?? '';
        $total_amount_display = !empty($refund['TOTAL_AMOUNT']) ? '$' . number_format($refund['TOTAL_AMOUNT'], 2) : '';
        $enrollment_date_display = !empty($refund['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($refund['ENROLLMENT_DATE'])) : '';
        $enrollment_type_display = !empty($refund['ENROLLMENT_TYPE']) ? $refund['ENROLLMENT_TYPE'] : '';
        $enrollment_balance_display = !empty($refund['TOTAL_AMOUNT']) ? '$' . number_format($refund['TOTAL_AMOUNT'] - $refund['AMOUNT'], 2) : '';
    }

    $name = $refund['ENROLLMENT_NAME'] ?? '';
    $ENROLLMENT_ID = $refund['ENROLLMENT_ID'] ?? '';
    $MISC_ID = $refund['MISC_ID'] ?? '';
    if (empty($name)) {
        $enrollment_name = '';
    } else {
        $enrollment_name = "$name" . " - ";
    }

    $cell_no = "A" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(date('m/d/Y', strtotime($refund['PAYMENT_DATE'])));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "B" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('$' . $refund['AMOUNT']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    $cell_no = "C" . $i;
    if ($refund['PAYMENT_TYPE'] == 'Cash' && !$is_gift_refund) {
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($refund['TYPE']);
    } else {
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($refund_payment_type);
    }
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "D" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($refund['PAYMENT_TYPE']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "E" . $i;
    if ($refund['PAYMENT_TYPE'] == 'Credit Card' || $refund['PAYMENT_TYPE'] == 'Visa' || $refund['PAYMENT_TYPE'] == 'Master Card' || $refund['PAYMENT_TYPE'] == 'American Express' || $refund['PAYMENT_TYPE'] == 'Card' || $refund['PAYMENT_TYPE'] == 'Card On File') {
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($refund['PAYMENT_TYPE']);
    } else {
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-');
    }
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "F" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($refund['RECEIPT_NUMBER']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "G" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($refund['MEMO'] ?? '');
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "H" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($client_name);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "I" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $MISC_ID : $enrollment_name . $ENROLLMENT_ID);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "J" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($enrollment_date_display);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "K" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($enrollment_type_display);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "L" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($total_amount_display);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    $cell_no = "M" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($enrollment_balance_display);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    $cell_no = "N" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(!empty($enrollment_by->fields['CLOSER']) ? $enrollment_by->fields['CLOSER'] : '');
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "O" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(isset($teacher[0]) ? $teacher[0] : '');
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "P" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(isset($teacher[1]) ? $teacher[1] : '');
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "Q" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(isset($teacher[2]) ? $teacher[2] : '');
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $redTextStyle = [
        'font' => [
            'color' => ['rgb' => 'FF0000']
        ]
    ];

    $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':Q' . $i)->applyFromArray($redTextStyle);
    $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':Q' . $i)->applyFromArray($styleArray);

    $i++;
}

// Add total row
$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, '$' . number_format(($total_amount - $total_refund), 2));

$objPHPExcel->getActiveSheet()
    ->getStyle('A' . $i . ':Q' . $i)
    ->applyFromArray([
        'font' => [
            'bold' => true
        ],
        'borders' => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);

$objPHPExcel->getActiveSheet()
    ->getStyle('B' . $i)
    ->applyFromArray([
        'alignment' => [
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
        ]
    ]);

$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
header("location:" . $outputFileName);
