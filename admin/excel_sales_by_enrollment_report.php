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
$include_no_provider = isset($_GET['include_no_provider']) ? $_GET['include_no_provider'] : 0;

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$service_provider_id = $_GET['service_provider_id'];

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
    $concatenatedResults .= $result;
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();
$objPHPExcel->setActiveSheetIndex(0);
$sheet = $objPHPExcel->getActiveSheet();

// Set column widths
$sheet->getColumnDimension("A")->setWidth(25);
$sheet->getColumnDimension("B")->setWidth(15);
$sheet->getColumnDimension("C")->setWidth(15);
$sheet->getColumnDimension("D")->setWidth(40);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

$rowNumber = 1;

// Title
$sheet->mergeCells('A1:D1');
$sheet->setCellValue('A1', $title);
$sheet->getStyle('A1')->getFont()->setSize(18);
$sheet->getStyle('A1')->getFont()->setBold(true);
$sheet->getRowDimension(1)->setRowHeight(36);
$sheet->getStyle('A1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:D1')->applyFromArray($styleArray);

// Business Name
$rowNumber = 2;
$sheet->mergeCells('A2:D2');
$sheet->setCellValue('A2', $business_name . " (" . $concatenatedResults . ")");
$sheet->getStyle('A2')->getFont()->setBold(true);
$sheet->getStyle('A2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2:D2')->applyFromArray($styleArray);

// Date Range
$rowNumber = 3;
$sheet->mergeCells('A3:D3');
$sheet->setCellValue('A3', date('m/d/Y', strtotime($from_date)) . ' - ' . date('m/d/Y', strtotime($to_date)));
$sheet->getStyle('A3')->getFont()->setBold(true);
$sheet->getStyle('A3')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$sheet->getStyle('A3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A3:D3')->applyFromArray($styleArray);

$rowNumber = 5;

// Get all selected service providers
$each_service_provider = $db_account->Execute("SELECT DISTINCT DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID 
    FROM DOA_ENROLLMENT_MASTER 
    INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER 
    WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
    AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN ($service_provider_id) 
    GROUP BY SERVICE_PROVIDER_ID");

$all_providers_data = [];
$all_enrollments_with_providers = [];
$enrollment_units_data = [];

// First, get total units for each enrollment
$enrollment_units_query = $db_account->Execute("
    SELECT 
        em.PK_ENROLLMENT_MASTER,
        COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_UNITS
    FROM DOA_ENROLLMENT_MASTER em
    LEFT JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER
    LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
    WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
    AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL)
    AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
    GROUP BY em.PK_ENROLLMENT_MASTER
");

while (!$enrollment_units_query->EOF) {
    $enrollment_id = $enrollment_units_query->fields['PK_ENROLLMENT_MASTER'];
    $total_units = $enrollment_units_query->fields['TOTAL_UNITS'];
    $enrollment_units_data[$enrollment_id] = $total_units;
    $enrollment_units_query->MoveNext();
}

$total_all_units = array_sum($enrollment_units_data);

// Process each service provider
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
        $enrollments_query = $db_account->Execute("
            SELECT 
                em.PK_ENROLLMENT_MASTER,
                em.ENROLLMENT_DATE,
                em.PK_ENROLLMENT_TYPE,
                esp.SERVICE_PROVIDER_PERCENTAGE
            FROM DOA_ENROLLMENT_MASTER em
            INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
            INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER esp ON em.PK_ENROLLMENT_MASTER = esp.PK_ENROLLMENT_MASTER
            WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
            AND eb.TOTAL_AMOUNT > 0
            AND esp.SERVICE_PROVIDER_ID = $service_provider_id_per_table
            AND em.PK_ENROLLMENT_TYPE = $type_id
            AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
            GROUP BY em.PK_ENROLLMENT_MASTER
        ");

        $enrollment_count = 0;
        $total_units = 0;
        $enrollment_list = [];

        while (!$enrollments_query->EOF) {
            $enrollment_id = $enrollments_query->fields['PK_ENROLLMENT_MASTER'];
            $percentage = $enrollments_query->fields['SERVICE_PROVIDER_PERCENTAGE'];

            $total_enrollment_units = isset($enrollment_units_data[$enrollment_id]) ? $enrollment_units_data[$enrollment_id] : 0;
            $provider_units = $total_enrollment_units * ($percentage / 100);

            $enrollment_count++;
            $total_units += $provider_units;
            $enrollment_list[] = $enrollment_id . " (" . number_format($provider_units, 2) . " units)";

            if (!isset($all_enrollments_with_providers[$enrollment_id])) {
                $all_enrollments_with_providers[$enrollment_id] = [];
            }
            $all_enrollments_with_providers[$enrollment_id][] = [
                'provider_name' => $provider_name,
                'percentage' => $percentage,
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

// EXPORT SERVICE PROVIDERS DATA
foreach ($all_providers_data as $provider_data) {
    // Provider Header
    $sheet->setCellValue('A' . $rowNumber, $provider_data['name']);
    $sheet->mergeCells('A' . $rowNumber . ':D' . $rowNumber);
    $sheet->getStyle('A' . $rowNumber)->getFont()->setBold(true);
    $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
    $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->applyFromArray($styleArray);
    $rowNumber++;

    // Headers
    $headers = ['Enrollment Type', 'Total Enrollments', 'Total Units Sold', 'Enrollment IDs (with units)'];
    $col = 0;
    foreach ($headers as $header) {
        $sheet->setCellValueByColumnAndRow($col, $rowNumber, $header);
        $sheet->getStyleByColumnAndRow($col, $rowNumber)->getFont()->setBold(true);
        $sheet->getStyleByColumnAndRow($col, $rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyleByColumnAndRow($col, $rowNumber)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
        $col++;
    }
    $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->applyFromArray($styleArray);
    $rowNumber++;

    // Enrollment Types Data
    $enrollment_types = [
        'Pre Original' => 'pre_original',
        'Original' => 'original',
        'Extension' => 'extension',
        'Renewal' => 'renewal'
    ];

    foreach ($enrollment_types as $type_label => $type_key) {
        $sheet->setCellValue('A' . $rowNumber, $type_label);
        $sheet->setCellValue('B' . $rowNumber, $provider_data[$type_key]['sold']);
        $sheet->setCellValue('C' . $rowNumber, number_format($provider_data[$type_key]['units'], 2));
        $sheet->setCellValue('D' . $rowNumber, !empty($provider_data[$type_key]['enrollments']) ? implode(', ', $provider_data[$type_key]['enrollments']) : 'None');
        $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->applyFromArray($styleArray);
        $rowNumber++;
    }

    // Provider Totals
    $provider_total_enrollments = $provider_data['pre_original']['sold'] + $provider_data['original']['sold'] + $provider_data['extension']['sold'] + $provider_data['renewal']['sold'];
    $provider_total_units = $provider_data['pre_original']['units'] + $provider_data['original']['units'] + $provider_data['extension']['units'] + $provider_data['renewal']['units'];

    $sheet->setCellValue('A' . $rowNumber, 'Provider Total');
    $sheet->setCellValue('B' . $rowNumber, $provider_total_enrollments);
    $sheet->setCellValue('C' . $rowNumber, number_format($provider_total_units, 2));
    $sheet->setCellValue('D' . $rowNumber, '');
    $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->getFont()->setBold(true);
    $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E8F4FF');
    $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->applyFromArray($styleArray);
    $rowNumber += 2; // Add extra space between providers
}

// Add some basic summary if no providers found
if (empty($all_providers_data)) {
    $sheet->setCellValue('A' . $rowNumber, 'No data found for the selected criteria.');
    $sheet->mergeCells('A' . $rowNumber . ':D' . $rowNumber);
    $sheet->getStyle('A' . $rowNumber)->getFont()->setBold(true);
    $sheet->getStyle('A' . $rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $rowNumber++;
}

// Save the file
$outputFileName = 'SALES_BY_ENROLLMENT_REPORT_' . date('Y-m-d') . '.xlsx';
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

// Clear any previous output
if (ob_get_length()) {
    ob_clean();
}

// Set headers for download
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
