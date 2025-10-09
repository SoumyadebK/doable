<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "LESSONS TAUGHT BY DEPARTMENT REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$service_provider_id = $_GET['PK_USER'];

$selected_service_provider = [];
$selected_service_provider_name = [];
$selected_service_provider_row = $db->Execute("SELECT DISTINCT DOA_USERS.`PK_USER`, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM `DOA_USERS` LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USERS.PK_USER IN (" . $service_provider_id . ") AND DOA_USER_ROLES.`PK_ROLES` = 5");
while (!$selected_service_provider_row->EOF) {
    $selected_service_provider[] = $selected_service_provider_row->fields['PK_USER'];
    $selected_service_provider_name[] = $selected_service_provider_row->fields['NAME'];
    $selected_service_provider_row->MoveNext();
}

$row = $db->Execute("SELECT PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS WHERE ACTIVE = 1 AND PK_USER IN (" . implode(',', $selected_service_provider) . ")");
$totalResults = count($selected_service_provider_name);
$concatenatedServiceProviders = "";
foreach ($selected_service_provider_name as $key => $result) {
    // Append the current result to the concatenated string
    $concatenatedServiceProviders .= $result;

    // If it's not the last result, append a comma
    if ($key < $totalResults - 1) {
        $concatenatedServiceProviders .= ", ";
    }
}

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
$outputFileName = 'NFA_ACTIVE_NO_ENROLLMENTS_REPORT.xlsx';

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
$objPHPExcel->getActiveSheet()->mergeCells('A1:D1');
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18); // Set font size to 16
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
//$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);

$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:B2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($business_name . " (" . $concatenatedResults . ")");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "C2";
$objPHPExcel->getActiveSheet()->mergeCells('C2:D2');
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
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Service Provider");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "B4";
//$objPHPExcel->getActiveSheet()->mergeCells('C3:D3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total Private Services");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "C4";
//$objPHPExcel->getActiveSheet()->mergeCells('E3:G3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Pvt Intv (Front)");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D4";
//$objPHPExcel->getActiveSheet()->mergeCells('E3:G3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Pvt Ren (Back)");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$i = 5;

$total_private_services = 0;
$total_front_end_services = 0;
$total_back_end_services = 0;

$row = $db_account->Execute("SELECT
                                CONCAT(u.FIRST_NAME, ' ', u.LAST_NAME) AS SERVICE_PROVIDER_NAME,
                                COUNT(r.PK_APPOINTMENT_MASTER) AS TOTAL_PRIVATE_SERVICES,
                                SUM(CASE WHEN r.lesson_number <= 12 THEN 1 ELSE 0 END) AS FRONT_END_SERVICES,
                                SUM(CASE WHEN r.lesson_number > 12 THEN 1 ELSE 0 END) AS BACK_END_SERVICES,
                                SUM(r.UNIT) AS TOTAL_UNITS_TAUGHT,
                                MIN(r.DATE) AS FIRST_SERVICE_DATE,
                                MAX(r.DATE) AS LAST_SERVICE_DATE
                            FROM (
                                SELECT
                                    t.PK_APPOINTMENT_MASTER,
                                    t.PK_USER_MASTER,
                                    t.PK_SCHEDULING_CODE,
                                    t.DATE,
                                    t.PK_APPOINTMENT_STATUS,
                                    t.UNIT,
                                    t.PK_USER,
                                    (SELECT COUNT(*) 
                                    FROM DOA_APPOINTMENT_MASTER apm2
                                    JOIN DOA_APPOINTMENT_CUSTOMER ac2 ON apm2.PK_APPOINTMENT_MASTER = ac2.PK_APPOINTMENT_MASTER
                                    JOIN DOA_APPOINTMENT_SERVICE_PROVIDER asp2 ON apm2.PK_APPOINTMENT_MASTER = asp2.PK_APPOINTMENT_MASTER
                                    JOIN DOA_SCHEDULING_CODE sc2 ON apm2.PK_SCHEDULING_CODE = sc2.PK_SCHEDULING_CODE
                                    JOIN DOA_SERVICE_CODE svc2 ON apm2.PK_SERVICE_CODE = svc2.PK_SERVICE_CODE
                                    WHERE svc2.IS_GROUP = 0
                                    AND apm2.PK_APPOINTMENT_STATUS = 2 
                                    AND apm2.STATUS = 'A'
                                    AND ac2.PK_USER_MASTER = t.PK_USER_MASTER
                                    AND asp2.PK_USER = t.PK_USER
                                    AND apm2.DATE <= t.DATE
                                    AND apm2.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                    ) AS lesson_number
                                FROM (
                                    SELECT
                                        apm.PK_APPOINTMENT_MASTER,
                                        ac.PK_USER_MASTER,
                                        apm.PK_SCHEDULING_CODE,
                                        apm.DATE,
                                        apm.PK_APPOINTMENT_STATUS,
                                        sc.UNIT,
                                        asp.PK_USER
                                    FROM DOA_APPOINTMENT_MASTER apm
                                    JOIN DOA_APPOINTMENT_CUSTOMER ac
                                        ON apm.PK_APPOINTMENT_MASTER = ac.PK_APPOINTMENT_MASTER
                                    JOIN DOA_APPOINTMENT_SERVICE_PROVIDER asp
                                        ON apm.PK_APPOINTMENT_MASTER = asp.PK_APPOINTMENT_MASTER
                                    JOIN DOA_SCHEDULING_CODE sc
                                        ON apm.PK_SCHEDULING_CODE = sc.PK_SCHEDULING_CODE
                                    JOIN DOA_SERVICE_CODE svc
                                        ON apm.PK_SERVICE_CODE = svc.PK_SERVICE_CODE
                                    WHERE
                                        svc.IS_GROUP = 0
                                        AND apm.PK_APPOINTMENT_STATUS = 2 
                                        AND apm.STATUS = 'A'
                                        AND apm.DATE BETWEEN '$from_date' AND '$to_date'
                                        AND asp.PK_USER IN ($service_provider_id)
                                        AND apm.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                    ORDER BY ac.PK_USER_MASTER, apm.DATE
                                ) AS t
                            ) AS r
                            LEFT JOIN DOA_MASTER.DOA_USERS u ON r.PK_USER = u.PK_USER
                            GROUP BY r.PK_USER
                            ORDER BY TOTAL_PRIVATE_SERVICES DESC
                        ");
while (!$row->EOF) {
    $total_private_services += $row->fields['TOTAL_PRIVATE_SERVICES'];
    $total_front_end_services += $row->fields['FRONT_END_SERVICES'];
    $total_back_end_services += $row->fields['BACK_END_SERVICES'];

    $cell_no = "A" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['SERVICE_PROVIDER_NAME']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

    $cell_no = "B" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['TOTAL_PRIVATE_SERVICES']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "C" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['FRONT_END_SERVICES']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = "D" . $i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['BACK_END_SERVICES']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $row->MoveNext();
    $i++;
}

$cell_no = "A" . $i;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "B" . $i;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($total_private_services);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "C" . $i;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($total_front_end_services);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D" . $i;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($total_back_end_services);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

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
