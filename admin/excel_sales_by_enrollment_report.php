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

// Get all selected service providers
$each_service_provider = $db_account->Execute("SELECT DISTINCT DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID 
                                        FROM DOA_ENROLLMENT_MASTER 
                                        INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER 
                                        WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
                                        AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN ($service_provider_id) 
                                        GROUP BY SERVICE_PROVIDER_ID");

$all_providers_data = [];
$all_enrollments_with_providers = []; // Track which enrollments have which providers

// First, let's collect ALL enrollment data to analyze
while (!$each_service_provider->EOF) {
    $service_provider_id_per_table = $each_service_provider->fields['SERVICE_PROVIDER_ID'];
    $name = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM DOA_USERS WHERE DOA_USERS.PK_USER = " . $service_provider_id_per_table);
    $provider_name = $name->fields['TEACHER'];

    $provider_data = [
        'name' => $provider_name,
        'pre_original' => ['sold' => 0, 'units' => 0, 'enrollments' => []],
        'original' => ['sold' => 0, 'units' => 0, 'enrollments' => []],
        'extension' => ['sold' => 0, 'units' => 0, 'enrollments' => []],
        'renewal' => ['sold' => 0, 'units' => 0, 'enrollments' => []]
    ];

    $enrollment_types = [
        5 => 'pre_original',
        2 => 'original',
        9 => 'extension',
        13 => 'renewal'
    ];

    foreach ($enrollment_types as $type_id => $type_name) {
        // Get enrollments with details and percentage
        $enrollments_query = $db_account->Execute("
                                                SELECT 
                                                    em.PK_ENROLLMENT_MASTER,
                                                    em.ENROLLMENT_DATE,
                                                    em.PK_ENROLLMENT_TYPE,
                                                    COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_UNITS,
                                                    esp.SERVICE_PROVIDER_PERCENTAGE
                                                FROM DOA_ENROLLMENT_MASTER em
                                                INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
                                                INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER esp ON em.PK_ENROLLMENT_MASTER = esp.PK_ENROLLMENT_MASTER
                                                LEFT JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER
                                                LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
                                                WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                                AND eb.TOTAL_AMOUNT > 0
                                                AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL)
                                                AND esp.SERVICE_PROVIDER_ID = $service_provider_id_per_table
                                                AND em.PK_ENROLLMENT_TYPE = $type_id
                                                AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
                                                GROUP BY em.PK_ENROLLMENT_MASTER, esp.SERVICE_PROVIDER_PERCENTAGE
                                            ");

        $enrollment_count = 0;
        $total_units = 0;
        $enrollment_list = [];

        while (!$enrollments_query->EOF) {
            $enrollment_id = $enrollments_query->fields['PK_ENROLLMENT_MASTER'];
            $total_units_for_enrollment = $enrollments_query->fields['TOTAL_UNITS'];
            $provider_percentage = $enrollments_query->fields['SERVICE_PROVIDER_PERCENTAGE'];

            // Calculate units based on provider percentage
            $provider_units = $total_units_for_enrollment * ($provider_percentage / 100);

            $enrollment_count++;
            $total_units += $provider_units;
            $enrollment_list[] = $enrollment_id . " (" . $provider_percentage . "%)";

            // Track which providers are associated with each enrollment
            if (!isset($all_enrollments_with_providers[$enrollment_id])) {
                $all_enrollments_with_providers[$enrollment_id] = [];
            }
            $all_enrollments_with_providers[$enrollment_id][] = [
                'provider' => $provider_name,
                'percentage' => $provider_percentage,
                'units' => $provider_units
            ];

            $enrollments_query->MoveNext();
        }

        $provider_data[$type_name]['sold'] = $enrollment_count;
        $provider_data[$type_name]['units'] = $total_units;
        $provider_data[$type_name]['enrollments'] = $enrollment_list;
    }

    $all_providers_data[] = $provider_data;
    $each_service_provider->MoveNext();
}

// Calculate grand totals
$grand_total_enrollments = count($all_enrollments_with_providers);
$grand_total_units = 0;

foreach ($all_providers_data as $provider) {
    foreach (['pre_original', 'original', 'extension', 'renewal'] as $type) {
        $grand_total_units += $provider[$type]['units'];
    }
}

// Identify enrollments with multiple providers
$multi_provider_enrollments = [];
foreach ($all_enrollments_with_providers as $enrollment_id => $providers) {
    if (count($providers) > 1) {
        $multi_provider_enrollments[$enrollment_id] = $providers;
    }
}

foreach ($all_providers_data as $provider_data):
    $objPHPExcel->getActiveSheet()->setCellValue('A' . $rowNumber, $provider_data['name']);
    $objPHPExcel->getActiveSheet()->getStyle('A' . $rowNumber)->getFont()->setBold(true);
    $borderRows[] = $rowNumber;
    $rowNumber++;

    $headers = ['Enrollment Type', 'Total Enrollments', 'Total Units Sold',];
    $col = 0;
    foreach ($headers as $header) {
        $objPHPExcel->getActiveSheet()->setCellValue($cell[$col] . $rowNumber, $header);
        $objPHPExcel->getActiveSheet()->getStyle($cell[$col] . $rowNumber)->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle($cell[$col] . $rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $col++;
    }
    $borderRows[] = $rowNumber;
    $rowNumber++;

    $enrollment_types = [
        'Pre-Original' => 'pre_original',
        'Original' => 'original',
        'Extension' => 'extension',
        'Renewal' => 'renewal'
    ];

    foreach ($enrollment_types as $type_label => $type_key) {
        $objPHPExcel->getActiveSheet()->setCellValue('A' . $rowNumber, $type_label);
        $objPHPExcel->getActiveSheet()->setCellValue('B' . $rowNumber, $provider_data[$type_key]['sold']);
        $objPHPExcel->getActiveSheet()->setCellValue('C' . $rowNumber, number_format($provider_data[$type_key]['units'], 2));
        $borderRows[] = $rowNumber;
        $rowNumber++;
    }

    // Add an empty row after each provider
    $rowNumber++;
endforeach;

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
