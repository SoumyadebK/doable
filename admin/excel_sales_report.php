<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "SALES REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$service_provider_id = $_GET['service_provider_id'];

$payment_date = "AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $service_provider_id . ") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' GROUP BY SERVICE_PROVIDER_ID ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE DESC";

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
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
$outputFileName = 'SALES_REPORT.xlsx';

$objReader      = PHPExcel_IOFactory::createReader($inputFileType);
$objReader->setIncludeCharts(TRUE);
$objPHPExcel     = new PHPExcel();
$objWriter         = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setWidth(18);

$cell_no = "A1";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($title);
$objPHPExcel->getActiveSheet()->mergeCells('A1:I1');
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18); // Set font size to 16
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
//$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);
$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:I2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('(' . date('m/d/Y', strtotime($from_date)) . ' - ' . date('m/d/Y', strtotime($to_date)) . ')');
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allBorders' => [   // <-- fix here
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

$objPHPExcel->getActiveSheet()
    ->getStyle('A1:E1')
    ->applyFromArray($styleArray);

$cell_no = "A4";
//$objPHPExcel->getActiveSheet()->mergeCells('C3:D3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Date");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "B4";
//$objPHPExcel->getActiveSheet()->mergeCells('C3:D3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Student");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "C4";
//$objPHPExcel->getActiveSheet()->mergeCells('E3:G3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Amount of Sale");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D4";
//$objPHPExcel->getActiveSheet()->mergeCells('E3:G3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Enrollment Name");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "E4";
$objPHPExcel->getActiveSheet()->mergeCells('H3:I3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Services");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F4";
$objPHPExcel->getActiveSheet()->mergeCells('H3:I3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Executive");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "G4";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($service_provider_title . "1");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H4";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($service_provider_title . "2");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "I4";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($service_provider_title . "3");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$i = 1;
$total_amount = 0;
$row = $db_account->Execute("
                                SELECT 
                                    DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                                    DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID,
                                    DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE AS DATE,
                                    DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                                    DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                                    DOA_ENROLLMENT_MASTER.MISC_ID,
                                    CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT,
                                    DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT AS TOTAL_AMOUNT,
                                    'PAID' AS STATUS
                                FROM DOA_ENROLLMENT_MASTER
                                INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER 
                                    ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                INNER JOIN $master_database.DOA_USERS AS DOA_USERS 
                                    ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER
                                LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION 
                                    ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION
                                LEFT JOIN DOA_ENROLLMENT_BILLING 
                                    ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                                WHERE DOA_USERS.IS_DELETED = 0 
                                AND DOA_USERS.ACTIVE = 1
                                AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' 
                                    AND '" . date('Y-m-d', strtotime($to_date)) . "'

                                UNION ALL

                                SELECT 
                                    DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                                    DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID,
                                    MAX(DOA_ENROLLMENT_CANCEL.CANCEL_DATE) AS DATE,
                                    DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                                    DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                                    DOA_ENROLLMENT_MASTER.MISC_ID,
                                    CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT,
                                    SUM(DOA_ENROLLMENT_CANCEL.CANCEL_AMOUNT) AS TOTAL_AMOUNT,
                                    'CANCELLED' AS STATUS
                                FROM DOA_ENROLLMENT_CANCEL
                                INNER JOIN DOA_ENROLLMENT_MASTER 
                                    ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_CANCEL.PK_ENROLLMENT_MASTER
                                INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER 
                                    ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                INNER JOIN $master_database.DOA_USERS AS DOA_USERS 
                                    ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER
                                LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION 
                                    ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION
                                WHERE DOA_USERS.IS_DELETED = 0 
                                AND DOA_USERS.ACTIVE = 1
                                AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                AND DOA_ENROLLMENT_CANCEL.CANCEL_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' 
                                    AND '" . date('Y-m-d', strtotime($to_date)) . "'
                                GROUP BY 
                                    DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                                    DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID,
                                    DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                                    DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                                    DOA_ENROLLMENT_MASTER.MISC_ID,
                                    DOA_USERS.FIRST_NAME, 
                                    DOA_USERS.LAST_NAME

                                ORDER BY DATE DESC
                            ");
while (!$row->EOF) {
    $enr_status = $row->fields['STATUS'];
    $name = $row->fields['ENROLLMENT_NAME'];
    $ENROLLMENT_ID = $row->fields['ENROLLMENT_ID'];
    if (empty($name)) {
        $enrollment_name = '';
    } else {
        $enrollment_name = "$name" . " - ";
    }

    $serviceCode = [];
    if ($enr_status == 'CANCELLED') {
        $total_amount -= $row->fields['TOTAL_AMOUNT'];
        $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE.SESSION_CREATED, DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED, DOA_ENROLLMENT_CANCEL.ACTUAL_AMOUNT, DOA_ENROLLMENT_CANCEL.CANCEL_AMOUNT FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE JOIN DOA_ENROLLMENT_CANCEL ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE = DOA_ENROLLMENT_CANCEL.PK_ENROLLMENT_SERVICE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
        $serviceCode = [];
        while (!$serviceCodeData->EOF) {
            $total_session = ($serviceCodeData->fields['PRICE_PER_SESSION'] > 0) ? ($serviceCodeData->fields['ACTUAL_AMOUNT'] / $serviceCodeData->fields['PRICE_PER_SESSION']) : 0;
            $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'] . ': ' . ($total_session - $serviceCodeData->fields['NUMBER_OF_SESSION']);
            $serviceCodeData->MoveNext();
        }
    } else {
        $total_amount += $row->fields['TOTAL_AMOUNT'];
        $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE.SESSION_CREATED, DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED, DOA_ENROLLMENT_CANCEL.ACTUAL_AMOUNT, DOA_ENROLLMENT_CANCEL.CANCEL_AMOUNT FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_CANCEL ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE = DOA_ENROLLMENT_CANCEL.PK_ENROLLMENT_SERVICE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
        while (!$serviceCodeData->EOF) {
            if ($serviceCodeData->fields['ACTUAL_AMOUNT'] > 0) {
                $total_session = ($serviceCodeData->fields['PRICE_PER_SESSION'] > 0) ? ($serviceCodeData->fields['ACTUAL_AMOUNT'] / $serviceCodeData->fields['PRICE_PER_SESSION']) : $serviceCodeData->fields['NUMBER_OF_SESSION'];
            } else {
                $total_session = $serviceCodeData->fields['NUMBER_OF_SESSION'];
            }
            $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'] . ': ' . $total_session;
            $serviceCodeData->MoveNext();
        }
    }

    $executive = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS EXECUTIVE FROM DOA_USERS WHERE PK_USER = " . $row->fields['ENROLLMENT_BY_ID']);

    $results = $db_account->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER, SERVICE_PROVIDER_PERCENTAGE FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
    $resultsArray = [];
    while (!$results->EOF) {
        $resultsArray[] = $results->fields['SERVICE_PROVIDER'] . ' (' . number_format($results->fields['SERVICE_PROVIDER_PERCENTAGE']) . '%)';
        $results->MoveNext();
    }


    $cell_no = "A" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(date('m/d/Y', strtotime($row->fields['DATE'])));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "B" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['CLIENT']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "C" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$" . $row->fields['TOTAL_AMOUNT']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    $cell_no = "D" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $row->fields['MISC_ID'] : $enrollment_name . $ENROLLMENT_ID);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "E" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(implode(', ', $serviceCode));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "F" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue((empty($executive->fields['EXECUTIVE']) ? '' : $executive->fields['EXECUTIVE']));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "G" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue((isset($resultsArray[0]) && $resultsArray[0]) ? $resultsArray[0] : '');
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "H" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue((isset($resultsArray[1]) && $resultsArray[1]) ? $resultsArray[1] : '');
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "I" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue((isset($resultsArray[2]) && $resultsArray[2]) ? $resultsArray[2] : '');
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $row->MoveNext();
    $i++;
}

$cell_no = "B" . $i;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('Total: $' . number_format($total_amount, 2));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);


// Find the last used row
$lastRow = $i - 1; // since $i was incremented after the last row

// Apply border style to the full data range (header + data)
$objPHPExcel->getActiveSheet()
    ->getStyle("A1:E" . $lastRow)
    ->applyFromArray([
        'borders' => [
            'allBorders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);

$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
header("location:" . $outputFileName);
