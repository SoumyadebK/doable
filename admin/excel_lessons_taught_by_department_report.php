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

$total_units = 0;
$total_interview = 0;
$total_renewal = 0;

// Get data for each service provider using UNIT-BASED calculation (matching Studio Business and Web Report)
if ($service_provider_id) {
    $providers = explode(',', $service_provider_id);

    foreach ($providers as $provider_id) {
        // Get provider name
        $provider_info = $db->Execute("SELECT CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS WHERE PK_USER = $provider_id");
        $provider_name = $provider_info->fields['NAME'] ?? 'Unknown';

        // USE UNIT-BASED CALCULATION (Same as Studio Business Report)
        $provider_query = "SELECT 
            CONCAT(
                COALESCE(SUM(CASE WHEN W.DATE <= M.twelfth_date OR M.twelfth_date IS NULL THEN W.total_units ELSE 0 END), 0),
                '/',
                COALESCE(SUM(CASE WHEN W.DATE > M.twelfth_date THEN W.total_units ELSE 0 END), 0)
            ) AS INTERVIEW_RENEWAL_COUNT,
            COALESCE(SUM(W.total_units), 0) as total_units
        FROM (
            SELECT 
                AC.PK_USER_MASTER,
                SUM(SC.UNIT) AS total_lessons,
                SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(AM.DATE ORDER BY AM.DATE SEPARATOR ','), ',', 12), ',', -1) AS twelfth_date 
            FROM DOA_APPOINTMENT_CUSTOMER AC 
            INNER JOIN DOA_APPOINTMENT_MASTER AM ON AC.PK_APPOINTMENT_MASTER = AM.PK_APPOINTMENT_MASTER 
            INNER JOIN DOA_SCHEDULING_CODE SC ON AM.PK_SCHEDULING_CODE = SC.PK_SCHEDULING_CODE 
            WHERE AM.STATUS = 'A' 
                AND AM.PK_APPOINTMENT_STATUS IN (1,2,3,5,7,8) 
                AND AM.APPOINTMENT_TYPE IN ('NORMAL','AD-HOC')
                AND AM.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
            GROUP BY AC.PK_USER_MASTER 
            HAVING total_lessons >= 12
        ) AS M 
        RIGHT JOIN (
            SELECT 
                AC.PK_USER_MASTER,
                AM.DATE,
                SUM(SC.UNIT) AS total_units
            FROM DOA_APPOINTMENT_CUSTOMER AC 
            INNER JOIN DOA_APPOINTMENT_MASTER AM ON AC.PK_APPOINTMENT_MASTER = AM.PK_APPOINTMENT_MASTER 
            INNER JOIN DOA_SCHEDULING_CODE SC ON AM.PK_SCHEDULING_CODE = SC.PK_SCHEDULING_CODE 
            INNER JOIN DOA_SERVICE_CODE SVC ON AM.PK_SERVICE_CODE = SVC.PK_SERVICE_CODE
            INNER JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ASP ON AM.PK_APPOINTMENT_MASTER = ASP.PK_APPOINTMENT_MASTER
            WHERE AM.STATUS = 'A' 
                AND AM.PK_APPOINTMENT_STATUS IN (1,2,3,5,7,8) 
                AND AM.APPOINTMENT_TYPE IN ('NORMAL','AD-HOC') 
                AND SVC.IS_GROUP = 0
                AND AM.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                AND AM.DATE BETWEEN '$from_date' AND '$to_date'
                AND ASP.PK_USER = $provider_id
            GROUP BY AC.PK_USER_MASTER, AM.DATE
        ) AS W USING (PK_USER_MASTER)";

        $provider_result = $db_account->Execute($provider_query);
        $provider_counts = explode('/', $provider_result->fields['INTERVIEW_RENEWAL_COUNT'] ?? '0/0');

        $provider_interview = intval($provider_counts[0] ?? 0);
        $provider_renewal = intval($provider_counts[1] ?? 0);
        $provider_total = $provider_interview + $provider_renewal;
        $provider_units = $provider_result->fields['total_units'] ?? 0;

        // Write to Excel
        $cell_no = "A" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($provider_name);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

        $cell_no = "B" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($provider_units);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "C" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($provider_interview);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $cell_no = "D" . $i;
        $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($provider_renewal);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $total_units += $provider_units;
        $total_interview += $provider_interview;
        $total_renewal += $provider_renewal;

        $i++;
    }
}

// Update totals row
$cell_no = "A" . $i;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "B" . $i;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($total_units);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "C" . $i;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($total_interview);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D" . $i;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($total_renewal);
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
