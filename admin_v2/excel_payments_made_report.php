<?php
// Increase execution time and memory
set_time_limit(0);
ini_set('memory_limit', '1024M');

require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "PAYMENTS MADE REPORT";

$week_number = '';
$from_date = '';
$to_date = '';

if (!empty($_GET['week_number'])) {
    $week_number = $_GET['week_number'];
    $YEAR = date('Y');

    $from_date = date('Y-m-d', strtotime($_GET['start_date']));
    $to_date = date('Y-m-d', strtotime($_GET['end_date']));

    $payment_date = " AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
    $enrollment_date = " AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
    $appointment_date = " AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
} else {
    // Try to get from SESSION if not in GET
    if (isset($_SESSION['week_number']) && !empty($_SESSION['week_number'])) {
        $week_number = $_SESSION['week_number'];
        $from_date = date('Y-m-d', strtotime($_SESSION['start_date']));
        $to_date = date('Y-m-d', strtotime($_SESSION['end_date']));

        $payment_date = " AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
        $enrollment_date = " AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
        $appointment_date = " AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
    }
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

$inputFileType = 'Excel2007';
$outputFileName = 'PAYMENT_MADE_REPORT_' . date('Y-m-d_H-i-s') . '.xlsx';

$objPHPExcel = new PHPExcel();
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

// Set column widths
$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension("F")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("G")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("H")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("I")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("J")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("K")->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension("L")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("M")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("N")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("O")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("P")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("Q")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("R")->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension("S")->setWidth(13);

// Title
$objPHPExcel->getActiveSheet()->mergeCells('A1:S1');
$objPHPExcel->getActiveSheet()->setCellValue('A1', $title);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18)->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()
    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)
    ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// Row 2 - Location and Date Range (matching main view)
$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->mergeCells('A2:J2');
$objPHPExcel->getActiveSheet()->setCellValue('A2', ($account_data->fields['FRANCHISE'] == 1 ? 'Franchisee: ' : '') . $concatenatedResults);
$objPHPExcel->getActiveSheet()->getStyle('A2')->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()
    ->setWrapText(true)
    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)
    ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->mergeCells('K2:S2');
$objPHPExcel->getActiveSheet()->setCellValue('K2', '(' . date('m/d/Y', strtotime($from_date)) . ' - ' . date('m/d/Y', strtotime($to_date)) . ')');
$objPHPExcel->getActiveSheet()->getStyle('K2')->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle('K2')->getAlignment()
    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)
    ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A2:S2')->applyFromArray($styleArray);

// Headers - EXACTLY matching the main view
$headers = [
    'A' => 'Payment Date',
    'B' => 'Payment Amount',
    'C' => 'Enrollment Payment',
    'D' => 'Tip',
    'E' => 'Payment Title',
    'F' => 'Payment Method',
    'G' => 'Card Type',
    'H' => 'Receipt',
    'I' => 'Memo',
    'J' => 'Client',
    'K' => 'Enrollment Name',
    'L' => 'Enrollment Date',
    'M' => 'Enrollment Type',
    'N' => 'Enrollment Cost',
    'O' => 'Enrollment Balance',
    'P' => 'Closer',
    'Q' => 'Teacher1',
    'R' => 'Teacher2',
    'S' => 'Teacher3'
];

foreach ($headers as $col => $header) {
    $cell_no = $col . "3";
    $objPHPExcel->getActiveSheet()->setCellValue($cell_no, $header);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()
        ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)
        ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()->setRGB('E8F4FF');
}

$objPHPExcel->getActiveSheet()->getStyle('A3:S3')->applyFromArray($styleArray);

$row = 4;

// QUERY 1: Get all payments - EXACTLY matching the main view query
$all_payments = $db_account->Execute("SELECT 
    DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, 
    DOA_ENROLLMENT_MASTER.PK_USER_MASTER, 
    DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE, 
    DOA_ENROLLMENT_PAYMENT.TYPE, 
    PAYMENT_DATE, 
    AMOUNT, 
    PAYMENT_INFO, 
    PAYMENT_TYPE, 
    RECEIPT_NUMBER, 
    MEMO, 
    CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, 
    DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, 
    DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, 
    DOA_ENROLLMENT_MASTER.MISC_ID, 
    ENROLLMENT_DATE, 
    ENROLLMENT_TYPE, 
    TOTAL_AMOUNT, 
    ENROLLMENT_BY_ID, 
    COALESCE(DOA_ENROLLMENT_TIP.TIP_AMOUNT, 0) AS TIP_AMOUNT 
FROM DOA_ENROLLMENT_PAYMENT 
INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER 
INNER JOIN " . $master_database . ".DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE 
INNER JOIN " . $master_database . ".DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER 
INNER JOIN " . $master_database . ".DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER 
INNER JOIN " . $master_database . ".DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE 
INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER 
LEFT JOIN DOA_ENROLLMENT_TIP ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_PAYMENT = DOA_ENROLLMENT_TIP.PK_ENROLLMENT_PAYMENT 
WHERE DOA_USERS.IS_DELETED = 0 
AND IS_REFUNDED = 0 
AND DOA_ENROLLMENT_PAYMENT.NOT_EXPORT_TO_AMI = 0 
AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
" . $payment_date . " 
GROUP BY DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_PAYMENT 
ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC");

// QUERY 2: Get gift certificate payments - EXACTLY matching the main view
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
    NULL AS CLIENT,
    0 AS TIP_AMOUNT
FROM
    DOA_ENROLLMENT_PAYMENT
INNER JOIN " . $master_database . ".DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE
ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE
LEFT JOIN DOA_GIFT_CERTIFICATE_MASTER AS DOA_GIFT_CERTIFICATE_MASTER
ON DOA_ENROLLMENT_PAYMENT.PK_GIFT_CERTIFICATE_MASTER = DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER
WHERE (DOA_ENROLLMENT_PAYMENT.TYPE = 'Gift Certificate' OR DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund Gift Certificate')
AND DOA_ENROLLMENT_PAYMENT.NOT_EXPORT_TO_AMI = 0 
AND DOA_ENROLLMENT_PAYMENT.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
" . $payment_date . " 
ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC");

// QUERY 3: Get wallet payments - EXACTLY matching the main view
$wallet_payments = $db_account->Execute("SELECT 
    DOA_ENROLLMENT_PAYMENT.*, 
    CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, 
    DOA_PAYMENT_TYPE.PAYMENT_TYPE, 
    DOA_CUSTOMER_WALLET.BALANCE_LEFT, 
    0 AS TIP_AMOUNT 
FROM DOA_ENROLLMENT_PAYMENT 
LEFT JOIN DOA_CUSTOMER_WALLET ON DOA_ENROLLMENT_PAYMENT.PK_CUSTOMER_WALLET = DOA_CUSTOMER_WALLET.PK_CUSTOMER_WALLET 
LEFT JOIN " . $master_database . ".DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_CUSTOMER_WALLET.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER 
LEFT JOIN " . $master_database . ".DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER 
LEFT JOIN " . $master_database . ".DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE = DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE 
WHERE DOA_ENROLLMENT_PAYMENT.TYPE = 'Wallet' 
AND DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO != 'Gift Certificate' 
AND DOA_ENROLLMENT_PAYMENT.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' 
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

$total_amount = 0;
$total_refund = 0;
$total_tips = 0;
$total_refund_tips = 0;
$processed_teachers = [];

// Display wallet payments first (matching main view)
while (!$wallet_payments->EOF) {
    $total_wallet = 0;
    $total_wallet += $wallet_payments->fields['AMOUNT'];
    if ($wallet_payments->fields['BALANCE_LEFT'] > 0) {
        $objPHPExcel->getActiveSheet()->setCellValue('A' . $row, date('m/d/Y', strtotime($wallet_payments->fields['PAYMENT_DATE'])));
        $objPHPExcel->getActiveSheet()->setCellValue('B' . $row, '$' . number_format($wallet_payments->fields['BALANCE_LEFT'], 2));
        $objPHPExcel->getActiveSheet()->setCellValue('C' . $row, '$' . number_format($wallet_payments->fields['BALANCE_LEFT'], 2));
        $objPHPExcel->getActiveSheet()->setCellValue('D' . $row, '$0.00');
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $row, 'Wallet');
        $objPHPExcel->getActiveSheet()->setCellValue('F' . $row, $wallet_payments->fields['PAYMENT_TYPE']);
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $row, '-');
        $objPHPExcel->getActiveSheet()->setCellValue('H' . $row, $wallet_payments->fields['RECEIPT_NUMBER']);
        $objPHPExcel->getActiveSheet()->setCellValue('I' . $row, $wallet_payments->fields['MEMO'] ?? '-');
        $objPHPExcel->getActiveSheet()->setCellValue('J' . $row, $wallet_payments->fields['CLIENT']);
        $objPHPExcel->getActiveSheet()->setCellValue('K' . $row, '-');
        $objPHPExcel->getActiveSheet()->setCellValue('L' . $row, '-');
        $objPHPExcel->getActiveSheet()->setCellValue('M' . $row, '-');
        $objPHPExcel->getActiveSheet()->setCellValue('N' . $row, '-');
        $objPHPExcel->getActiveSheet()->setCellValue('O' . $row, '-');
        $objPHPExcel->getActiveSheet()->setCellValue('P' . $row, '-');
        $objPHPExcel->getActiveSheet()->setCellValue('Q' . $row, '-');
        $objPHPExcel->getActiveSheet()->setCellValue('R' . $row, '-');
        $objPHPExcel->getActiveSheet()->setCellValue('S' . $row, '-');

        $objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':S' . $row)->applyFromArray($styleArray);
        $row++;
    }
    $wallet_payments->MoveNext();
}

// Display regular payments - EXACTLY matching the main view
foreach ($regular_payments as $payment) {
    $name = empty($payment['ENROLLMENT_NAME']) ? '' : $payment['ENROLLMENT_NAME'];
    if (empty($name)) {
        $enrollment_name = '';
    } else {
        $enrollment_name = "$name" . " - ";
    }
    $PK_USER_MASTER = empty($payment['PK_USER_MASTER']) ? '' : $payment['PK_USER_MASTER'];

    // Check if this is a gift certificate payment
    $is_gift_certificate = ($payment['TYPE'] == 'Gift Certificate' || $payment['TYPE'] == 'Refund Gift Certificate');

    if (!$is_gift_certificate && !empty($payment['ENROLLMENT_BY_ID'])) {
        $enrollment_by = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLOSER FROM DOA_USERS WHERE PK_USER = " . $payment['ENROLLMENT_BY_ID']);
    } else {
        $enrollment_by = null;
    }

    // Get teachers - cache to avoid multiple queries
    $enrollmentKey = $payment['PK_ENROLLMENT_MASTER'];
    if (!isset($processed_teachers[$enrollmentKey])) {
        $service_provider = $db_account->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER 
            FROM " . $account_database . ".DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER 
            LEFT JOIN " . $account_database . ".DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER 
                ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER 
            LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER 
            WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $payment['PK_ENROLLMENT_MASTER']);

        $teachers = [];
        while (!$service_provider->EOF) {
            $teachers[] = $service_provider->fields['TEACHER'];
            $service_provider->MoveNext();
        }
        $processed_teachers[$enrollmentKey] = $teachers;
    }
    $teachers = $processed_teachers[$enrollmentKey];

    $enrollment_balance = !empty($payment['TOTAL_AMOUNT']) ? $payment['TOTAL_AMOUNT'] - $payment['AMOUNT'] : 0;

    // Get tip amount
    $tip_amount = $payment['TIP_AMOUNT'] ?? 0;
    $total_payment = $payment['AMOUNT'] + $tip_amount;
    $total_amount += $payment['AMOUNT'];
    $total_tips += $tip_amount;

    // Payment type logic - EXACTLY matching the main view
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
    } elseif ($payment['TYPE'] == 'Move') {
        $payment_type = 'Wallet';
        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
        $MISC_ID = $payment['MISC_ID'] ?? '';
        $client_name = $payment['CLIENT'] ?? '';
        $total_amount_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'], 2) : '';
        $enrollment_date_display = !empty($payment['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($payment['ENROLLMENT_DATE'])) : '';
        $enrollment_type_display = !empty($payment['ENROLLMENT_TYPE']) ? $payment['ENROLLMENT_TYPE'] : '';
        $enrollment_balance_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'] - $payment['AMOUNT'], 2) : '';
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
    } else {
        $payment_type = $payment['PAYMENT_TYPE'];
        $ENROLLMENT_ID = $payment['ENROLLMENT_ID'] ?? '';
        $MISC_ID = $payment['MISC_ID'] ?? '';
        $client_name = $payment['CLIENT'] ?? '';
        $total_amount_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'], 2) : '';
        $enrollment_date_display = !empty($payment['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($payment['ENROLLMENT_DATE'])) : '';
        $enrollment_type_display = !empty($payment['ENROLLMENT_TYPE']) ? $payment['ENROLLMENT_TYPE'] : '';
        $enrollment_balance_display = !empty($payment['TOTAL_AMOUNT']) ? '$' . number_format($payment['TOTAL_AMOUNT'] - $payment['AMOUNT'], 2) : '';
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

    // Output row
    $objPHPExcel->getActiveSheet()->setCellValue('A' . $row, date('m/d/Y', strtotime($payment['PAYMENT_DATE'])));
    $objPHPExcel->getActiveSheet()->setCellValue('B' . $row, '$' . number_format($total_payment, 2));
    $objPHPExcel->getActiveSheet()->setCellValue('C' . $row, '$' . number_format($payment['AMOUNT'], 2));
    $objPHPExcel->getActiveSheet()->setCellValue('D' . $row, '$' . number_format($tip_amount, 2));
    $objPHPExcel->getActiveSheet()->setCellValue('E' . $row, $payment_type);
    $objPHPExcel->getActiveSheet()->setCellValue('F' . $row, $payment['PAYMENT_TYPE']);

    // Card Type
    if ($payment['PAYMENT_TYPE'] == 'Credit Card' || $payment['PAYMENT_TYPE'] == 'Visa' || $payment['PAYMENT_TYPE'] == 'Master Card' || $payment['PAYMENT_TYPE'] == 'American Express' || $payment['PAYMENT_TYPE'] == 'Card' || $payment['PAYMENT_TYPE'] == 'Card On File') {
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $row, $payment['PAYMENT_TYPE']);
    } else {
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $row, '');
    }

    $objPHPExcel->getActiveSheet()->setCellValue('H' . $row, $payment['RECEIPT_NUMBER']);
    $objPHPExcel->getActiveSheet()->setCellValue('I' . $row, empty($payment['MEMO']) ? '' : $payment['MEMO']);
    $objPHPExcel->getActiveSheet()->setCellValue('J' . $row, $client_name);
    $objPHPExcel->getActiveSheet()->setCellValue('K' . $row, ($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $MISC_ID : $enrollment_name . $ENROLLMENT_ID);
    $objPHPExcel->getActiveSheet()->setCellValue('L' . $row, $enrollment_date_display);
    $objPHPExcel->getActiveSheet()->setCellValue('M' . $row, $enrollment_type_display);
    $objPHPExcel->getActiveSheet()->setCellValue('N' . $row, $total_amount_display);
    $objPHPExcel->getActiveSheet()->setCellValue('O' . $row, $enrollment_balance_display);
    $objPHPExcel->getActiveSheet()->setCellValue('P' . $row, !empty($enrollment_by->fields['CLOSER']) ? $enrollment_by->fields['CLOSER'] : '');
    $objPHPExcel->getActiveSheet()->setCellValue('Q' . $row, isset($teachers[0]) ? $teachers[0] : '');
    $objPHPExcel->getActiveSheet()->setCellValue('R' . $row, isset($teachers[1]) ? $teachers[1] : '');
    $objPHPExcel->getActiveSheet()->setCellValue('S' . $row, isset($teachers[2]) ? $teachers[2] : '');

    $objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':S' . $row)->applyFromArray($styleArray);
    $row++;
}

// Display all refunds at the bottom - EXACTLY matching the main view
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
    $refund_tip = $refund['TIP_AMOUNT'] ?? 0;
    $total_refund_tips += $refund_tip;
    $refund_total = $refund['AMOUNT'] + $refund_tip;
    $PK_USER_MASTER = $refund['PK_USER_MASTER'] ?? '';

    if (!$is_gift_refund && !empty($refund['ENROLLMENT_BY_ID'])) {
        $enrollment_by = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLOSER FROM DOA_USERS WHERE PK_USER = " . $refund['ENROLLMENT_BY_ID']);
    } else {
        $enrollment_by = null;
    }

    // Get teachers for refund
    $enrollmentKey = $refund['PK_ENROLLMENT_MASTER'];
    if (!isset($processed_teachers[$enrollmentKey])) {
        $service_provider = $db_account->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER 
            FROM " . $account_database . ".DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER 
            LEFT JOIN " . $account_database . ".DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER 
                ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER 
            LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER 
            WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $refund['PK_ENROLLMENT_MASTER']);

        $teachers = [];
        while (!$service_provider->EOF) {
            $teachers[] = $service_provider->fields['TEACHER'];
            $service_provider->MoveNext();
        }
        $processed_teachers[$enrollmentKey] = $teachers;
    }
    $teachers = $processed_teachers[$enrollmentKey];

    $enrollment_balance = !empty($refund['TOTAL_AMOUNT']) ? $refund['TOTAL_AMOUNT'] - $refund['AMOUNT'] : 0;

    // Payment type logic for refunds - EXACTLY matching the main view
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

    // Output refund row with red color
    $objPHPExcel->getActiveSheet()->setCellValue('A' . $row, date('m/d/Y', strtotime($refund['PAYMENT_DATE'])));
    $objPHPExcel->getActiveSheet()->setCellValue('B' . $row, '$' . number_format($refund_total, 2));
    $objPHPExcel->getActiveSheet()->setCellValue('C' . $row, '$' . number_format($refund['AMOUNT'], 2));
    $objPHPExcel->getActiveSheet()->setCellValue('D' . $row, '$' . number_format($refund_tip, 2));

    if ($refund['PAYMENT_TYPE'] == 'Cash' && !$is_gift_refund) {
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $row, $refund['TYPE']);
    } else {
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $row, $refund_payment_type);
    }

    $objPHPExcel->getActiveSheet()->setCellValue('F' . $row, $refund['PAYMENT_TYPE']);

    if ($refund['PAYMENT_TYPE'] == 'Credit Card' || $refund['PAYMENT_TYPE'] == 'Visa' || $refund['PAYMENT_TYPE'] == 'Master Card' || $refund['PAYMENT_TYPE'] == 'American Express' || $refund['PAYMENT_TYPE'] == 'Card' || $refund['PAYMENT_TYPE'] == 'Card On File') {
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $row, $refund['PAYMENT_TYPE']);
    } else {
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $row, '');
    }

    $objPHPExcel->getActiveSheet()->setCellValue('H' . $row, $refund['RECEIPT_NUMBER']);
    $objPHPExcel->getActiveSheet()->setCellValue('I' . $row, $refund['MEMO'] ?? '');
    $objPHPExcel->getActiveSheet()->setCellValue('J' . $row, $client_name);
    $objPHPExcel->getActiveSheet()->setCellValue('K' . $row, ($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $MISC_ID : $enrollment_name . $ENROLLMENT_ID);
    $objPHPExcel->getActiveSheet()->setCellValue('L' . $row, $enrollment_date_display);
    $objPHPExcel->getActiveSheet()->setCellValue('M' . $row, $enrollment_type_display);
    $objPHPExcel->getActiveSheet()->setCellValue('N' . $row, $total_amount_display);
    $objPHPExcel->getActiveSheet()->setCellValue('O' . $row, $enrollment_balance_display);
    $objPHPExcel->getActiveSheet()->setCellValue('P' . $row, !empty($enrollment_by->fields['CLOSER']) ? $enrollment_by->fields['CLOSER'] : '');
    $objPHPExcel->getActiveSheet()->setCellValue('Q' . $row, isset($teachers[0]) ? $teachers[0] : '');
    $objPHPExcel->getActiveSheet()->setCellValue('R' . $row, isset($teachers[1]) ? $teachers[1] : '');
    $objPHPExcel->getActiveSheet()->setCellValue('S' . $row, isset($teachers[2]) ? $teachers[2] : '');

    // Apply red text style for refunds
    $redTextStyle = [
        'font' => [
            'color' => ['rgb' => 'FF0000']
        ]
    ];
    $objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':S' . $row)->applyFromArray($redTextStyle);
    $objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':S' . $row)->applyFromArray($styleArray);

    $row++;
}

// Total row - EXACTLY matching the main view
$objPHPExcel->getActiveSheet()->setCellValue('A' . $row, 'Total');
$objPHPExcel->getActiveSheet()->getStyle('A' . $row)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle('A' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->setCellValue('B' . $row, '$' . number_format(($total_amount + $total_tips) - ($total_refund + $total_refund_tips), 2));
$objPHPExcel->getActiveSheet()->getStyle('B' . $row)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle('B' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->setCellValue('C' . $row, '$' . number_format($total_amount - $total_refund, 2));
$objPHPExcel->getActiveSheet()->getStyle('C' . $row)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle('C' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->setCellValue('D' . $row, '$' . number_format($total_tips - $total_refund_tips, 2));
$objPHPExcel->getActiveSheet()->getStyle('D' . $row)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle('D' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':S' . $row)->applyFromArray([
    'font' => ['bold' => true],
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ],
    'fill' => [
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'color' => ['rgb' => 'E8F4FF']
    ]
]);

// Clear output buffer and send file
if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $outputFileName . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$objWriter->save('php://output');
exit;
