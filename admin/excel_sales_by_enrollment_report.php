<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "SALES BY ENROLLMENT REPORT";

$type = $_GET['type'];

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$service_provider_id = $_GET['PK_USER'];

$payment_date = "AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $service_provider_id . ") GROUP BY SERVICE_PROVIDER_ID ORDER BY DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE DESC";

$account_data = $db->Execute("SELECT BUSINESS_NAME, FRANCHISE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
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
$outputFileName = 'SALES_BY_ENROLLMENT_REPORT.xlsx';

$objReader      = PHPExcel_IOFactory::createReader($inputFileType);
$objReader->setIncludeCharts(TRUE);
$objPHPExcel     = new PHPExcel();
$objWriter         = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(20);

$objPHPExcel->getActiveSheet()->mergeCells('A1:C1');

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

$cell_no = "A1";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($title);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18); // Set font size to 16
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle('A1:C1')->applyFromArray($styleArray);

$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:C2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($business_name . " (" . $concatenatedResults . ")");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "A3";
$objPHPExcel->getActiveSheet()->mergeCells('A3:C3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(date('m/d/Y', strtotime($from_date)) . ' - ' . date('m/d/Y', strtotime($to_date)));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->getStyle('A2:C3')->applyFromArray($styleArray);

$rowNumber = 5; // Start data at row 5
$borderRows = [];

$each_service_provider = $db_account->Execute("SELECT distinct DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID FROM DOA_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $payment_date);
while (!$each_service_provider->EOF) {
    $name = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM DOA_USERS WHERE DOA_USERS.PK_USER = " . $each_service_provider->fields['SERVICE_PROVIDER_ID']);
    $service_provider_id_per_table = $each_service_provider->fields['SERVICE_PROVIDER_ID'];

    // Write service provider name (merged across all columns)
    $objPHPExcel->getActiveSheet()
        ->setCellValue('A' . $rowNumber, $name->fields['TEACHER'])
        ->mergeCells('A' . $rowNumber . ':C' . $rowNumber);

    // Style the service provider name with borders
    $objPHPExcel->getActiveSheet()
        ->getStyle('A' . $rowNumber . ':C' . $rowNumber)
        ->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            ],
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

    $rowNumber++; // Move to next row for headers
    $borderRows[] = $rowNumber; // Add header row to border array

    // Write headers
    $objPHPExcel->getActiveSheet()
        ->setCellValue('A' . $rowNumber, 'Enrollment Type')
        ->setCellValue('B' . $rowNumber, 'Total Enrollments')
        ->setCellValue('C' . $rowNumber, 'Total Units Sold');

    // Style headers - bold and center-aligned
    $headerStyle = [
        'font' => [
            'bold' => true
        ],
        'alignment' => [
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        ]
    ];
    $objPHPExcel->getActiveSheet()
        ->getStyle('A' . $rowNumber . ':C' . $rowNumber)
        ->applyFromArray($headerStyle);

    $rowNumber++;

    // Define the four enrollment types with their IDs and names
    $enrollment_types = [
        5 => 'Pre Original',
        2 => 'Original',
        9 => 'Extension',
        13 => 'Renewal'
    ];

    // Pre Original
    $pre_original_sold = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");
    $pre_original_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

    // Original
    $original_sold = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");
    $original_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

    // Extension
    $extension_sold = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");
    $extension_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

    // Renewal
    $renewal_sold = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");
    $renewal_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

    // Write data rows for each enrollment type
    // Pre Original
    $objPHPExcel->getActiveSheet()
        ->setCellValue('A' . $rowNumber, 'Pre Original')
        ->setCellValue('B' . $rowNumber, $pre_original_sold->fields['SOLD'] ?? 0)
        ->setCellValue('C' . $rowNumber, $pre_original_units->fields['UNITS'] ?? 0);
    $borderRows[] = $rowNumber;
    $rowNumber++;

    // Original
    $objPHPExcel->getActiveSheet()
        ->setCellValue('A' . $rowNumber, 'Original')
        ->setCellValue('B' . $rowNumber, $original_sold->fields['SOLD'] ?? 0)
        ->setCellValue('C' . $rowNumber, $original_units->fields['UNITS'] ?? 0);
    $borderRows[] = $rowNumber;
    $rowNumber++;

    // Extension
    $objPHPExcel->getActiveSheet()
        ->setCellValue('A' . $rowNumber, 'Extension')
        ->setCellValue('B' . $rowNumber, $extension_sold->fields['SOLD'] ?? 0)
        ->setCellValue('C' . $rowNumber, $extension_units->fields['UNITS'] ?? 0);
    $borderRows[] = $rowNumber;
    $rowNumber++;

    // Renewal
    $objPHPExcel->getActiveSheet()
        ->setCellValue('A' . $rowNumber, 'Renewal')
        ->setCellValue('B' . $rowNumber, $renewal_sold->fields['SOLD'] ?? 0)
        ->setCellValue('C' . $rowNumber, $renewal_units->fields['UNITS'] ?? 0);
    $borderRows[] = $rowNumber;
    $rowNumber++;

    // Add blank rows between service providers
    $rowNumber += 2;
    $each_service_provider->MoveNext();
}

// Apply borders only to header and data rows
$borderStyle = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

foreach ($borderRows as $row) {
    $objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':C' . $row)->applyFromArray($borderStyle);
}

$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
header("location:" . $outputFileName);
