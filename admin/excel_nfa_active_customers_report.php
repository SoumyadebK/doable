<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "NFA ACTIVE CUSTOMERS REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

if (!empty($_GET['selected_range'])) {
    $selected_range = $_GET['selected_range'];
    $selected_date = date('Y-m-d', strtotime($_GET['selected_date']));
    $enrollment_date_condition = "AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE >= DATE_SUB('" . $selected_date . "', INTERVAL " . $selected_range . " MONTH) AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE <= '" . $selected_date . "'";
} else {
    $selected_date = date('Y-m-d', strtotime($_GET['selected_date']));
    $enrollment_date_condition = "AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE = '" . date('Y-m-d', strtotime($selected_date)) . "'";
}

$service_provider_id = $_GET['service_provider_id'];

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
$outputFileName = 'NFA_ACTIVE_CUSTOMERS_REPORT.xlsx';

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
$objPHPExcel->getActiveSheet()->mergeCells('A1:G1');
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18); // Set font size to 16
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
//$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);

$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:G2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' . " (" . $concatenatedResults . ")");
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
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Student");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "B4";
//$objPHPExcel->getActiveSheet()->mergeCells('C3:D3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Enrollment Name / Number");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "C4";
//$objPHPExcel->getActiveSheet()->mergeCells('E3:G3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D4";
//$objPHPExcel->getActiveSheet()->mergeCells('E3:G3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Session Left");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "E4";
//$objPHPExcel->getActiveSheet()->mergeCells('H3:I3');  
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Service Provider");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F4";
//$objPHPExcel->getActiveSheet()->mergeCells('H3:I3');  
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Last Appointment Date");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "G4";
//$objPHPExcel->getActiveSheet()->mergeCells('H3:I3');  
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Service Provider in the Last Appointment");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


$i = 5;

$row = $db_account->Execute("SELECT 
                                DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                                DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE,
                                DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION,
                                CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME,
                                DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                                DOA_ENROLLMENT_MASTER.ENROLLMENT_ID
                            FROM DOA_ENROLLMENT_SERVICE 
                            LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER 
                            JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE 
                            JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER 
                            JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                            JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER                                                                            
                            WHERE 
                                DOA_ENROLLMENT_MASTER.STATUS = 'A'  AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                AND DOA_SERVICE_CODE.IS_GROUP = 0  AND DOA_SERVICE_CODE.SERVICE_CODE LIKE '%PRI%'
                                AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0
                                AND DOA_SERVICE_MASTER.PK_SERVICE_CLASS != 5 
                                " . $enrollment_date_condition . "
                            ORDER BY CUSTOMER_NAME");
while (!$row->EOF) {
    $appointment = $db_account->Execute("SELECT PK_APPOINTMENT_MASTER FROM DOA_APPOINTMENT_MASTER WHERE DATE > CURDATE() AND PK_APPOINTMENT_STATUS = 1 AND PK_ENROLLMENT_SERVICE = " . $row->fields['PK_ENROLLMENT_SERVICE']);
    if ($appointment->RecordCount() == 0) {

        $results = $db_account->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
        $resultsArray = [];
        while (!$results->EOF) {
            $resultsArray[] = $results->fields['SERVICE_PROVIDER'];
            $results->MoveNext();
        }

        $last_data = $db_account->Execute("SELECT DATE, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER WHERE PK_APPOINTMENT_STATUS = 2 AND PK_ENROLLMENT_SERVICE = " . $row->fields['PK_ENROLLMENT_SERVICE'] . " ORDER BY DATE DESC, START_TIME DESC LIMIT 1");

        $NUMBER_OF_SESSION = getSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE']);
        if ($row->fields['NUMBER_OF_SESSION'] > $NUMBER_OF_SESSION) {

            $cell_no = "A" . $i;
            $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['CUSTOMER_NAME']);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

            $cell_no = "B" . $i;
            $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['ENROLLMENT_NAME'] . " / " . $row->fields['ENROLLMENT_ID']);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $cell_no = "C" . $i;
            $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['NUMBER_OF_SESSION']);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $cell_no = "D" . $i;
            $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['NUMBER_OF_SESSION'] - $NUMBER_OF_SESSION);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $cell_no = "E" . $i;
            $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue((isset($resultsArray[0]) && $resultsArray[0]) ? $resultsArray[0] : '');
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $cell_no = "F" . $i;
            $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(isset($last_data->fields['DATE']) ? date('m-d-Y', strtotime($last_data->fields['DATE'])) : '');
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $cell_no = "G" . $i;
            $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(isset($last_data->fields['SERVICE_PROVIDER']) ? $last_data->fields['SERVICE_PROVIDER'] : '');
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $i++;
        }
    }

    $row->MoveNext();
}


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
