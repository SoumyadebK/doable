<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
$title = "ROYALTY SERVICE REPORT";

$week_number = '';
$from_date = '';
$to_date = '';

if (!empty($_GET['week_number'])) {
    $week_number = $_GET['week_number'];
    $YEAR = date('Y', strtotime($_GET['start_date']));

    $from_date = date('Y-m-d', strtotime($_GET['start_date']));
    $to_date = date('Y-m-d', strtotime($from_date . ' +6 day'));
}

$PAYMENT_QUERY = "SELECT 
                    DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_PAYMENT, 
                    DOA_ENROLLMENT_PAYMENT.AMOUNT, 
                    DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER, 
                    DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE,
                    DOA_ENROLLMENT_PAYMENT.PK_ORDER,
                    DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE,
                    DOA_PAYMENT_TYPE.PAYMENT_TYPE, 
                    CONCAT(CUSTOMER.FIRST_NAME, ' ' ,CUSTOMER.LAST_NAME) AS STUDENT_NAME, 
                    CLOSER.FIRST_NAME AS CLOSER_FIRST_NAME, 
                    CLOSER.LAST_NAME AS CLOSER_LAST_NAME, 
                    DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER, 
                    DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER, 
                    DOA_ENROLLMENT_MASTER.PK_LOCATION 
                FROM DOA_ENROLLMENT_PAYMENT 
                LEFT JOIN DOA_MASTER.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE 
                    ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE 
                LEFT JOIN DOA_ENROLLMENT_MASTER 
                    ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER 
                LEFT JOIN DOA_MASTER.DOA_USERS AS CLOSER 
                    ON DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = CLOSER.PK_USER 
                LEFT JOIN DOA_ORDER 
                    ON DOA_ENROLLMENT_PAYMENT.PK_ORDER = DOA_ORDER.PK_ORDER 
                LEFT JOIN DOA_MASTER.DOA_USER_MASTER AS DOA_USER_MASTER 
                    ON (CASE 
                            WHEN DOA_ENROLLMENT_PAYMENT.PK_ORDER IS NULL 
                                THEN DOA_ENROLLMENT_MASTER.PK_USER_MASTER 
                            ELSE DOA_ORDER.PK_USER_MASTER 
                        END) = DOA_USER_MASTER.PK_USER_MASTER 
                LEFT JOIN DOA_MASTER.DOA_USERS AS CUSTOMER 
                    ON CUSTOMER.PK_USER = DOA_USER_MASTER.PK_USER 
                WHERE CUSTOMER.IS_DELETED = 0 AND DOA_ENROLLMENT_PAYMENT.NOT_EXPORT_TO_AMI = 0
                    AND DOA_ENROLLMENT_PAYMENT.IS_REFUNDED = 0 AND (DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' || DOA_ENROLLMENT_PAYMENT.TYPE = 'Adjustment') AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE NOT IN (5) 
                    AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'
                    AND (DOA_ENROLLMENT_PAYMENT.PK_ORDER IS NOT NULL OR DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ")) 
                ORDER BY PAYMENT_DATE ASC, RECEIPT_NUMBER ASC";

$REFUND_QUERY = "SELECT
                        DOA_ENROLLMENT_PAYMENT.AMOUNT,
                        DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER,
                        DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE,
                        DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE,
                        DOA_PAYMENT_TYPE.PAYMENT_TYPE,
                        CONCAT(CUSTOMER.FIRST_NAME, ' ' ,CUSTOMER.LAST_NAME) AS STUDENT_NAME,
                        CLOSER.FIRST_NAME AS CLOSER_FIRST_NAME,
                        CLOSER.LAST_NAME AS CLOSER_LAST_NAME,
                        DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER,
                        DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER,
                        DOA_ENROLLMENT_MASTER.PK_LOCATION
                    FROM
                        DOA_ENROLLMENT_PAYMENT
                    LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE
                            
                    LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                    LEFT JOIN $master_database.DOA_USERS AS CLOSER ON DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = CLOSER.PK_USER
                    
                    LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                    LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON CUSTOMER.PK_USER = DOA_USER_MASTER.PK_USER
                    
                    WHERE CUSTOMER.IS_DELETED = 0 AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund' AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE NOT IN (5) AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ")
                    AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'
                    ORDER BY PAYMENT_DATE ASC, RECEIPT_NUMBER ASC";


$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';

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
    // Append the current result to the concatenated string
    $concatenatedResults .= $result;

    // If it's not the last result, append a comma
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
        //echo $j."--".$k."<br />";
        $cell[] = $cell1[$j] . $cell1[$k];
    }
}

$inputFileType  = 'Excel2007';
$outputFileName = 'ROYALTY_SERVICE_REPORT.xlsx';

$objReader      = PHPExcel_IOFactory::createReader($inputFileType);
$objReader->setIncludeCharts(TRUE);
$objPHPExcel     = new PHPExcel();
$objWriter         = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("F")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("G")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("H")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("I")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("J")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("K")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("L")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("M")->setWidth(18);

$cell_no = "A1";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($title);
$objPHPExcel->getActiveSheet()->mergeCells('A1:M1');
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18); // Set font size to 16
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
//$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);
$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:G2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' . " (" . $concatenatedResults . ")");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H2";
$objPHPExcel->getActiveSheet()->mergeCells('H2:J2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('(' . date('m/d/Y', strtotime($from_date)) . ' - ' . date('m/d/Y', strtotime($to_date)) . ')');
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "K2";
$objPHPExcel->getActiveSheet()->mergeCells('K2:M2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Week # " . $week_number);
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
$objPHPExcel->getActiveSheet()->getStyle('A2:M2')->applyFromArray($styleArray);

$cell_no = "E3";
$objPHPExcel->getActiveSheet()->mergeCells('E3:F3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Staff code");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "I3";
$objPHPExcel->getActiveSheet()->mergeCells('I3:K3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Studio Receipts");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);



$cell_no = "A4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Receipt Number");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "B4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Date");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "C4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Student Name");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "D4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Type");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "E4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Closer");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "F4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Teachers");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "G4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("First Payment or A/C");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "H4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units/Total Cost");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "I4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Regular");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "J4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Sundry");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "K4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Misc./NonUnit");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "L4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total Subject R/S Fee");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "M4";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Studio Grand Total");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


$line = 5;
$payment_data = $db_account->Execute($PAYMENT_QUERY);
$REGULAR_TOTAL = 0;
$SUNDRY_TOTAL = 0;
$MISC_TOTAL = 0;
$TOTAL_RS_FEE = 0;
$TOTAL_AMOUNT_PAID = 0;
$LOCATION_TOTAL = [];

$TOTAL_AMOUNT_PAID_DAILY = 0;
$REGULAR_TOTAL_DAILY = 0;
$SUNDRY_TOTAL_DAILY = 0;
$MISC_TOTAL_DAILY = 0;

$total_record = $payment_data->RecordCount();
$i = 0;
while (!$payment_data->EOF) {
    if ($i == 0) {
        $last_date = $payment_data->fields['PAYMENT_DATE'];
    }
    $TOTAL_UNIT = 0;
    $REGULAR_AMOUNT = 0;
    $SUNDRY_AMOUNT = 0;
    $MISC_AMOUNT = 0;
    $teacher_data = $db_account->Execute("SELECT GROUP_CONCAT(DISTINCT(CONCAT(TEACHER.FIRST_NAME, ' ', TEACHER.LAST_NAME)) SEPARATOR ', ') AS TEACHER_NAME FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS TEACHER ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = TEACHER.PK_USER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $payment_data->fields['PK_ENROLLMENT_MASTER']);
    $enrollment_service_data = $db_account->Execute("SELECT SUM(`FINAL_AMOUNT`) AS TOTAL_AMOUNT, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $payment_data->fields['PK_ENROLLMENT_MASTER'] . " GROUP BY PK_ENROLLMENT_MASTER");
    $TOTAL_AMOUNT = ($enrollment_service_data->RecordCount() > 0) ? $enrollment_service_data->fields['TOTAL_AMOUNT'] : 0;
    $SERVICE_CLASS = ($enrollment_service_data->RecordCount() > 0) ? $enrollment_service_data->fields['PK_SERVICE_CLASS'] : '';

    $AMOUNT_PAID = $payment_data->fields['AMOUNT'];
    $TOTAL_AMOUNT_PAID += $AMOUNT_PAID;
    $TOTAL_AMOUNT_PAID_DAILY += $AMOUNT_PAID;

    if ($SERVICE_CLASS == 5) {
        $MISC_AMOUNT = $AMOUNT_PAID;
    } else {
        $REGULAR_AMOUNT = $AMOUNT_PAID;
    }

    if ($payment_data->fields['PK_ENROLLMENT_MASTER'] == 0 && $payment_data->fields['PK_ORDER'] != null) {
        $SUNDRY_AMOUNT += $AMOUNT_PAID;
    } else {
        $enrollment_service_code_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_SERVICE_CODE.IS_SUNDRY, DOA_SERVICE_CODE.IS_GROUP FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $payment_data->fields['PK_ENROLLMENT_MASTER']);
        while (!$enrollment_service_code_data->EOF) {
            if ($enrollment_service_code_data->fields['IS_GROUP'] == 0 && $enrollment_service_code_data->fields['PRICE_PER_SESSION'] > 0) {
                $TOTAL_UNIT += $enrollment_service_code_data->fields['NUMBER_OF_SESSION'];
            }
            if ($SERVICE_CLASS == 5 && $enrollment_service_code_data->fields['IS_SUNDRY'] == 1) {
                $servicePercent = ($enrollment_service_code_data->fields['FINAL_AMOUNT'] * 100) / $TOTAL_AMOUNT;
                $serviceAmount = ($AMOUNT_PAID * $servicePercent) / 100;
                $SUNDRY_AMOUNT += $serviceAmount;
            }
            $enrollment_service_code_data->MoveNext();
        }
    }

    if ($payment_data->fields['PK_PAYMENT_TYPE'] == '7') {
        $receipt_number = $payment_data->fields['RECEIPT_NUMBER'];
        $receipt_payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE IS_ORIGINAL_RECEIPT = 1 AND DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER = '$receipt_number'");
        $payment_type = $receipt_payment_details->fields['PAYMENT_TYPE'];
    } else {
        $payment_type = $payment_data->fields['PAYMENT_TYPE'];
    }

    if ($SUNDRY_AMOUNT > 0) {
        $MISC_AMOUNT = $AMOUNT_PAID - $SUNDRY_AMOUNT;
    }

    $REGULAR_TOTAL += $REGULAR_AMOUNT;
    $SUNDRY_TOTAL += $SUNDRY_AMOUNT;
    $MISC_TOTAL += $MISC_AMOUNT;

    $REGULAR_TOTAL_DAILY += $REGULAR_AMOUNT;
    $SUNDRY_TOTAL_DAILY += $SUNDRY_AMOUNT;
    $MISC_TOTAL_DAILY += $MISC_AMOUNT;

    $TOTAL_RS_FEE += $REGULAR_AMOUNT;
    if (isset($LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']])) {
        $LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']] = $LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']] + ($REGULAR_AMOUNT + $MISC_AMOUNT);
    } else {
        $LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']] = ($REGULAR_AMOUNT + $MISC_AMOUNT);
    }


    $cell_no = "A" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($payment_data->fields['RECEIPT_NUMBER']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "B" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(date('m/d/Y', strtotime($payment_data->fields['PAYMENT_DATE'])));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "C" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($payment_data->fields['STUDENT_NAME']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "D" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($payment_type);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "E" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($payment_data->fields['CLOSER_FIRST_NAME'] . " " . $payment_data->fields['CLOSER_LAST_NAME']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "F" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($teacher_data->fields['TEACHER_NAME']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    if ($SERVICE_CLASS == 5) {
        $first_payment = $payment_data->fields['CUSTOMER_ENROLLMENT_NUMBER'] . '/MISC';
    } else {
        switch ($payment_data->fields['CUSTOMER_ENROLLMENT_NUMBER']) {
            case 1:
                $first_payment = '1/PORI';
                break;
            case 2:
                $first_payment = '2/ORI';
                break;
            case 3:
                $first_payment = '3/EXT';
                break;

            default:
                $first_payment = $payment_data->fields['CUSTOMER_ENROLLMENT_NUMBER'] . '/REN';
                break;
        }
    }

    $cell_no = "G" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($first_payment);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "H" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($TOTAL_UNIT . ' / $' . $TOTAL_AMOUNT);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "I" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($REGULAR_AMOUNT);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "J" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($SUNDRY_AMOUNT);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "K" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($MISC_AMOUNT);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "L" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($AMOUNT_PAID);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "M" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($TOTAL_AMOUNT_PAID, 2, '.', ''));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


    $payment_data->MoveNext();
    $i++;
    $line++;
    if (($last_date != $payment_data->fields['PAYMENT_DATE']) || ($i == $total_record)) {
        $cell_no = "A" . $line;
        $objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':G' . $line);
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Daily Totals");
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $cell_no = "H" . $line;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($TOTAL_AMOUNT_PAID_DAILY, 2, '.', ''));
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $cell_no = "I" . $line;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($REGULAR_TOTAL_DAILY, 2, '.', ''));
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $cell_no = "J" . $line;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($SUNDRY_TOTAL_DAILY, 2, '.', ''));
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $cell_no = "K" . $line;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($MISC_TOTAL_DAILY, 2, '.', ''));
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $cell_no = "L" . $line;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($TOTAL_RS_FEE + $MISC_TOTAL, 2, '.', ''));
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $cell_no = "M" . $line;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($TOTAL_AMOUNT_PAID, 2, '.', ''));
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $line++;
        if ($i < $total_record) {
            $cell_no = "A" . $line;
            $objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':G' . $line);
            $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($business_name);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $cell_no = "H" . $line;
            $objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':J' . $line);
            $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('(' . date('m/d/Y', strtotime($from_date)) . ' - ' . date('m/d/Y', strtotime($to_date)) . ')');
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $cell_no = "K" . $line;
            $objPHPExcel->getActiveSheet()->mergeCells('K' . $line . ':M' . $line);
            $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Week # " . $week_number);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $line++;
        }
        $TOTAL_AMOUNT_PAID_DAILY = 0;
        $REGULAR_TOTAL_DAILY = 0;
        $SUNDRY_TOTAL_DAILY = 0;
        $MISC_TOTAL_DAILY = 0;
    }
    $last_date = $payment_data->fields['PAYMENT_DATE'];
}

$line++;
$cell_no = "A" . $line;
$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':M' . $line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Refunds or credits below completed tuition refund report & photocopy of front & back of canceled checks must be attached in order to receive credits. (Identify bank plan, rewrites & cancellation. Attach detail on authorized D-O-R transportation details.)");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$line++;
$cell_no = "A" . $line;
$line1 = $line + 1;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Receipt Number");
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':B' . $line1);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "C" . $line;
$line1 = $line + 1;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Date");
$objPHPExcel->getActiveSheet()->mergeCells('C' . $line . ':C' . $line1);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D" . $line;
$line1 = $line + 1;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Student Name");
$objPHPExcel->getActiveSheet()->mergeCells('D' . $line . ':F' . $line1);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "G" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Staff Code");
$objPHPExcel->getActiveSheet()->mergeCells('G' . $line . ':H' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "I" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("");
$objPHPExcel->getActiveSheet()->mergeCells('I' . $line . ':J' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "K" . $line;
$line1 = $line + 1;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Studio Refunds Deductions");
$objPHPExcel->getActiveSheet()->mergeCells('K' . $line . ':M' . $line1);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$line++;
$cell_no = "G" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Closer");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Teachers");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "I" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Type");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "J" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units/Total Cost");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);



$TOTAL_AMOUNT_REFUND = 0;
$refund_data = $db_account->Execute($REFUND_QUERY);
while (!$refund_data->EOF) {
    $line++;
    $REFUND_TOTAL_UNIT = 0;
    $teacher_data = $db_account->Execute("SELECT GROUP_CONCAT(DISTINCT(CONCAT(TEACHER.FIRST_NAME, ' ', TEACHER.LAST_NAME)) SEPARATOR ', ') AS TEACHER_NAME FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS TEACHER ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = TEACHER.PK_USER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $refund_data->fields['PK_ENROLLMENT_MASTER']);
    $enrollment_service_data = $db_account->Execute("SELECT SUM(`FINAL_AMOUNT`) AS TOTAL_AMOUNT, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $refund_data->fields['PK_ENROLLMENT_MASTER'] . " GROUP BY PK_ENROLLMENT_MASTER");
    $REFUND_TOTAL_AMOUNT = $enrollment_service_data->fields['TOTAL_AMOUNT'];

    $enrollment_service_code_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_SERVICE_CODE.IS_SUNDRY, DOA_SERVICE_CODE.IS_GROUP FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $refund_data->fields['PK_ENROLLMENT_MASTER']);
    while (!$enrollment_service_code_data->EOF) {
        if ($enrollment_service_code_data->fields['IS_GROUP'] == 0 && $enrollment_service_code_data->fields['PRICE_PER_SESSION'] > 0) {
            $REFUND_TOTAL_UNIT += $enrollment_service_code_data->fields['NUMBER_OF_SESSION'];
        }
        $enrollment_service_code_data->MoveNext();
    }

    $AMOUNT_REFUND = $refund_data->fields['AMOUNT'];
    $TOTAL_AMOUNT_REFUND += $AMOUNT_REFUND;

    $cell_no = "A" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($refund_data->fields['RECEIPT_NUMBER']);
    $objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':B' . $line);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "C" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(date('m/d/Y', strtotime($refund_data->fields['PAYMENT_DATE'])));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "D" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($refund_data->fields['STUDENT_NAME']);
    $objPHPExcel->getActiveSheet()->mergeCells('D' . $line . ':F' . $line);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "G" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($refund_data->fields['CLOSER_FIRST_NAME'] . " " . $refund_data->fields['CLOSER_LAST_NAME']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "H" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($teacher_data->fields['TEACHER_NAME']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "I" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($refund_data->fields['PAYMENT_TYPE']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "J" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($REFUND_TOTAL_UNIT . ' / $' . $REFUND_TOTAL_AMOUNT);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "K" . $line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-' . number_format($AMOUNT_REFUND, 2, '.', ''));
    $objPHPExcel->getActiveSheet()->mergeCells('K' . $line . ':M' . $line);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $refund_data->MoveNext();
}

$line++;
$cell_no = "A" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Refunds Total");
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':J' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "K" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-' . number_format($TOTAL_AMOUNT_REFUND, 2, '.', ''));
$objPHPExcel->getActiveSheet()->mergeCells('K' . $line . ':M' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);



$line++;
$cell_no = "A" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("");
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':B' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "C" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Regular Cash +");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Sundry +");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "E" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Misc./NonUnit -");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Sundry Deduct");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "G" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("=");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total sub.rlty");
$objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':I' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "J" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Sundry Cash Studio Total");
$objPHPExcel->getActiveSheet()->mergeCells('J' . $line . ':M' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);




$line++;
$cell_no = "A" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total receipts");
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':B' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "C" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($REGULAR_TOTAL, 2, '.', ''));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($SUNDRY_TOTAL, 2, '.', ''));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "E" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($MISC_TOTAL, 2, '.', ''));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "G" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("=");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($TOTAL_RS_FEE + $MISC_TOTAL, 2));
$objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':I' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "J" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('+');
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "K" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($SUNDRY_TOTAL, 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "L" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("=");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "M" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($TOTAL_RS_FEE + $MISC_TOTAL + $SUNDRY_TOTAL, 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);





$line++;
$cell_no = "A" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total refunds/credits");
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':B' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "G" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("=");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-' . number_format($TOTAL_AMOUNT_REFUND, 2, '.', ''));
$objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':I' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "M" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('-' . number_format($TOTAL_AMOUNT_REFUND, 2, '.', ''));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


$line++;
$cell_no = "A" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total subject to r/s fee");
$objPHPExcel->getActiveSheet()->mergeCells('A' . $line . ':D' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$location_name_all = '';
$royalty_percent = '';
$location_total = '';
$royalty_percent_array = [];
foreach ($LOCATION_TOTAL as $key => $value) {
    if ($key != null) {
        $location_name = $db->Execute("SELECT LOCATION_NAME, ROYALTY_PERCENTAGE FROM `DOA_LOCATION` WHERE `PK_LOCATION` = " . $key);
        $location_name_all .= $location_name->fields['LOCATION_NAME'] . " - ";
        $royalty_percent .= "X " . $location_name->fields['ROYALTY_PERCENTAGE'] . " %" . ' - ';
        $location_total .= "$" . number_format($value - $TOTAL_AMOUNT_REFUND, 2);
        $royalty_percent_array[$key]['ROYALTY_PERCENTAGE'] = $location_name->fields['ROYALTY_PERCENTAGE'];
        $royalty_percent_array[$key]['LOCATION_TOTAL'] = $value - $TOTAL_AMOUNT_REFUND;
    }
}
$cell_no = "E" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($location_name_all);
$objPHPExcel->getActiveSheet()->mergeCells('E' . $line . ':G' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($location_total . ' ---------- ' . number_format(($TOTAL_RS_FEE + $MISC_TOTAL) - $TOTAL_AMOUNT_REFUND, 2, '.', ''));
$objPHPExcel->getActiveSheet()->mergeCells('H' . $line . ':I' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "J" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($royalty_percent);
$objPHPExcel->getActiveSheet()->mergeCells('J' . $line . ':K' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$location_price = '';
foreach ($royalty_percent_array as $key => $value) {
    $location_price .= number_format(($value['LOCATION_TOTAL'] * ($value['ROYALTY_PERCENTAGE'] / 100)), 2) . " - ";
}
$cell_no = "L" . $line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($location_price);
$objPHPExcel->getActiveSheet()->mergeCells('L' . $line . ':M' . $line);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
header("location:" . $outputFileName);
