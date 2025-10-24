<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "SALES BY ENROLLMENT REPORT";

$type = $_GET['type'] ?? '';
$include_no_provider = isset($_GET['include_no_provider']) ? (int)$_GET['include_no_provider'] : 0;

// Fix date handling with proper validation
$from_date = isset($_GET['start_date']) ? date('Y-m-d', strtotime($_GET['start_date'])) : date('Y-m-d');
$to_date = isset($_GET['end_date']) ? date('Y-m-d', strtotime($_GET['end_date'])) : date('Y-m-d');

// DEBUG: Log parameters
error_log("=== EXCEL EXPORT DEBUG ===");
error_log("From Date: $from_date, To Date: $to_date");
error_log("Include No Provider: $include_no_provider");

// Fix for handling multiple service provider IDs
$service_provider_id = '0';
if (isset($_GET['PK_USER']) && is_array($_GET['PK_USER'])) {
    $service_provider_ids = array_filter($_GET['PK_USER']);
    $service_provider_id = !empty($service_provider_ids) ? implode(',', $service_provider_ids) : '0';
} else if (isset($_GET['PK_USER']) && !empty($_GET['PK_USER'])) {
    $service_provider_id = $_GET['PK_USER'];
}

error_log("Service Provider IDs: " . $service_provider_id);

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

// SECTION 1: GET TOTAL SUMMARY DATA (Same as web report)
$weekly_pre_original_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT > 0 AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

$weekly_original_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT > 0 AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

$weekly_extension_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT > 0 AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

$weekly_renewal_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT > 0 AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

// Store the total sold counts
$total_pre_original_sold = $weekly_pre_original_sold->fields['SOLD'] > 0 ? $weekly_pre_original_sold->fields['SOLD'] : 0;
$total_original_sold = $weekly_original_sold->fields['SOLD'] > 0 ? $weekly_original_sold->fields['SOLD'] : 0;
$total_extension_sold = $weekly_extension_sold->fields['SOLD'] > 0 ? $weekly_extension_sold->fields['SOLD'] : 0;
$total_renewal_sold = $weekly_renewal_sold->fields['SOLD'] > 0 ? $weekly_renewal_sold->fields['SOLD'] : 0;
$total_all_sold = $total_pre_original_sold + $total_original_sold + $total_extension_sold + $total_renewal_sold;

// Get total units from studio summary report
$weekly_pre_original_units = $db_account->Execute("
    SELECT COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_UNITS 
    FROM DOA_ENROLLMENT_MASTER em 
    INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER 
    INNER JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER 
    LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE 
    WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
    AND eb.TOTAL_AMOUNT > 0 
    AND em.PK_ENROLLMENT_TYPE = 5 
    AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL) 
    AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
");

$weekly_original_units = $db_account->Execute("
    SELECT COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_UNITS 
    FROM DOA_ENROLLMENT_MASTER em 
    INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER 
    INNER JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER 
    LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE 
    WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
    AND eb.TOTAL_AMOUNT > 0 
    AND em.PK_ENROLLMENT_TYPE = 2 
    AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL) 
    AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
");

$weekly_extension_units = $db_account->Execute("
    SELECT COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_UNITS 
    FROM DOA_ENROLLMENT_MASTER em 
    INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER 
    INNER JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER 
    LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE 
    WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
    AND eb.TOTAL_AMOUNT > 0 
    AND em.PK_ENROLLMENT_TYPE = 9 
    AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL) 
    AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
");

$weekly_renewal_units = $db_account->Execute("
    SELECT COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_UNITS 
    FROM DOA_ENROLLMENT_MASTER em 
    INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER 
    INNER JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER 
    LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE 
    WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
    AND eb.TOTAL_AMOUNT > 0 
    AND em.PK_ENROLLMENT_TYPE = 13 
    AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL) 
    AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
");

// Store the total units
$total_pre_original_units = $weekly_pre_original_units->fields['TOTAL_UNITS'] > 0 ? $weekly_pre_original_units->fields['TOTAL_UNITS'] : 0;
$total_original_units = $weekly_original_units->fields['TOTAL_UNITS'] > 0 ? $weekly_original_units->fields['TOTAL_UNITS'] : 0;
$total_extension_units = $weekly_extension_units->fields['TOTAL_UNITS'] > 0 ? $weekly_extension_units->fields['TOTAL_UNITS'] : 0;
$total_renewal_units = $weekly_renewal_units->fields['TOTAL_UNITS'] > 0 ? $weekly_renewal_units->fields['TOTAL_UNITS'] : 0;
$total_all_units_studio = $total_pre_original_units + $total_original_units + $total_extension_units + $total_renewal_units;

// SECTION 2: GET SERVICE PROVIDER DATA
$all_providers_data = [];
$all_enrollments_with_providers = [];

error_log("Checking service provider data...");
error_log("Service Provider ID: " . $service_provider_id);

if (!empty($service_provider_id) && $service_provider_id != '0') {
    // Get all selected service providers
    $each_service_provider = $db_account->Execute("
        SELECT DISTINCT esp.SERVICE_PROVIDER_ID 
        FROM DOA_ENROLLMENT_MASTER em
        INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER esp ON em.PK_ENROLLMENT_MASTER = esp.PK_ENROLLMENT_MASTER 
        INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
        WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
        AND eb.TOTAL_AMOUNT > 0
        AND em.IS_SALE = 'Y'
        AND esp.SERVICE_PROVIDER_ID IN ($service_provider_id) 
        AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
        GROUP BY esp.SERVICE_PROVIDER_ID
    ");

    error_log("Service Provider Query Count: " . ($each_service_provider ? $each_service_provider->RecordCount() : 0));

    // Process each service provider
    if ($each_service_provider && $each_service_provider->RecordCount() > 0) {
        while (!$each_service_provider->EOF) {
            $service_provider_id_per_table = $each_service_provider->fields['SERVICE_PROVIDER_ID'];
            $name = $db->Execute("SELECT CONCAT(FIRST_NAME, ' ', LAST_NAME) AS TEACHER FROM DOA_USERS WHERE PK_USER = " . $service_provider_id_per_table);
            $provider_name = $name->fields['TEACHER'];

            error_log("Processing provider: " . $provider_name . " (ID: " . $service_provider_id_per_table . ")");

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
                // Get enrollments with percentages for fractional count
                $enrollments_with_percentage_query = $db_account->Execute("
                    SELECT 
                        em.PK_ENROLLMENT_MASTER,
                        esp.SERVICE_PROVIDER_PERCENTAGE,
                        COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_ENROLLMENT_UNITS
                    FROM DOA_ENROLLMENT_MASTER em
                    INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
                    INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER esp ON em.PK_ENROLLMENT_MASTER = esp.PK_ENROLLMENT_MASTER
                    LEFT JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER
                    LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
                    WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                    AND eb.TOTAL_AMOUNT > 0 
                    AND em.IS_SALE = 'Y'
                    AND esp.SERVICE_PROVIDER_ID = $service_provider_id_per_table
                    AND em.PK_ENROLLMENT_TYPE = $type_id
                    AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL)
                    AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
                    GROUP BY em.PK_ENROLLMENT_MASTER, esp.SERVICE_PROVIDER_PERCENTAGE
                ");

                $fractional_enrollment_count = 0;
                $total_units = 0;
                $enrollment_list = [];

                while (!$enrollments_with_percentage_query->EOF) {
                    $enrollment_id = $enrollments_with_percentage_query->fields['PK_ENROLLMENT_MASTER'];
                    $percentage = $enrollments_with_percentage_query->fields['SERVICE_PROVIDER_PERCENTAGE'];
                    $enrollment_units = $enrollments_with_percentage_query->fields['TOTAL_ENROLLMENT_UNITS'];

                    // Calculate fractional enrollment count
                    $fractional_enrollment_count += ($percentage / 100);

                    // Calculate provider's share of units
                    $provider_units = $enrollment_units * ($percentage / 100);
                    $total_units += $provider_units;

                    $enrollment_list[] = $enrollment_id . " (" . number_format($provider_units, 2) . " units - " . $percentage . "%)";

                    // Track enrollments with providers
                    if (!isset($all_enrollments_with_providers[$enrollment_id])) {
                        $all_enrollments_with_providers[$enrollment_id] = [];
                    }
                    $all_enrollments_with_providers[$enrollment_id][] = $service_provider_id_per_table;

                    $enrollments_with_percentage_query->MoveNext();
                }

                $provider_data[$type_name]['sold'] = $fractional_enrollment_count;
                $provider_data[$type_name]['units'] = $total_units;
                $provider_data[$type_name]['enrollments'] = $enrollment_list;

                error_log("  $type_name: " . $fractional_enrollment_count . " enrollments, " . $total_units . " units");
            }

            $all_providers_data[] = $provider_data;
            $each_service_provider->MoveNext();
        }
    }
}

error_log("Total providers found: " . count($all_providers_data));

// SECTION 3: EXPORT SERVICE PROVIDERS DATA
foreach ($all_providers_data as $provider_data) {
    // Provider Header
    $sheet->setCellValue('A' . $rowNumber, $provider_data['name']);
    $sheet->mergeCells('A' . $rowNumber . ':D' . $rowNumber);
    $sheet->getStyle('A' . $rowNumber)->getFont()->setBold(true);
    $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
    $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->applyFromArray($styleArray);
    $rowNumber++;

    // Headers
    $headers = ['Enrollment Type', 'Total Enrollments', 'Total Units Sold', 'Enrollment IDs (with units & %)'];
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
        $sheet->setCellValue('B' . $rowNumber, number_format($provider_data[$type_key]['sold'], 2));
        $sheet->setCellValue('C' . $rowNumber, number_format($provider_data[$type_key]['units'], 2));
        $sheet->setCellValue('D' . $rowNumber, !empty($provider_data[$type_key]['enrollments']) ? implode(', ', $provider_data[$type_key]['enrollments']) : 'None');
        $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->applyFromArray($styleArray);
        $rowNumber++;
    }

    // Provider Totals
    $provider_total_enrollments = $provider_data['pre_original']['sold'] + $provider_data['original']['sold'] + $provider_data['extension']['sold'] + $provider_data['renewal']['sold'];
    $provider_total_units = $provider_data['pre_original']['units'] + $provider_data['original']['units'] + $provider_data['extension']['units'] + $provider_data['renewal']['units'];

    $sheet->setCellValue('A' . $rowNumber, 'Provider Total');
    $sheet->setCellValue('B' . $rowNumber, number_format($provider_total_enrollments, 2));
    $sheet->setCellValue('C' . $rowNumber, number_format($provider_total_units, 2));
    $sheet->setCellValue('D' . $rowNumber, '');
    $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->getFont()->setBold(true);
    $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E8F4FF');
    $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->applyFromArray($styleArray);
    $rowNumber += 2; // Add extra space between providers
}

// SECTION 4: ENROLLMENTS WITHOUT SERVICE PROVIDERS
if ($include_no_provider == 1) {
    error_log("Processing enrollments without service providers...");

    $no_provider_data = [
        'pre_original' => ['sold' => 0, 'units' => 0, 'enrollments' => []],
        'original' => ['sold' => 0, 'units' => 0, 'enrollments' => []],
        'extension' => ['sold' => 0, 'units' => 0, 'enrollments' => []],
        'renewal' => ['sold' => 0, 'units' => 0, 'enrollments' => []],
        'total_units' => 0
    ];

    // First, get all enrollment IDs that have service providers in our selected providers
    $enrollments_with_providers = [];
    if (!empty($service_provider_id) && $service_provider_id != '0') {
        $enrollments_with_providers_query = $db_account->Execute("
            SELECT DISTINCT esp.PK_ENROLLMENT_MASTER
            FROM DOA_ENROLLMENT_SERVICE_PROVIDER esp
            INNER JOIN DOA_ENROLLMENT_MASTER em ON esp.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
            INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
            WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
            AND eb.TOTAL_AMOUNT > 0 AND em.IS_SALE = 'Y'
            AND esp.SERVICE_PROVIDER_ID IN ($service_provider_id)
            AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
        ");

        while (!$enrollments_with_providers_query->EOF) {
            $enrollments_with_providers[] = $enrollments_with_providers_query->fields['PK_ENROLLMENT_MASTER'];
            $enrollments_with_providers_query->MoveNext();
        }
    }

    $enrollment_types = [
        5 => 'pre_original',
        2 => 'original',
        9 => 'extension',
        13 => 'renewal'
    ];

    foreach ($enrollment_types as $type_id => $type_name) {
        // Get ALL enrollments of this type in our date range
        $all_enrollments_query = $db_account->Execute("
            SELECT 
                em.PK_ENROLLMENT_MASTER,
                COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS UNITS
            FROM DOA_ENROLLMENT_MASTER em
            INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
            LEFT JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER
            LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
            WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
            AND eb.TOTAL_AMOUNT > 0 
            AND em.IS_SALE = 'Y'
            AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL)
            AND em.PK_ENROLLMENT_TYPE = $type_id
            AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
            GROUP BY em.PK_ENROLLMENT_MASTER
        ");

        $enrollment_count = 0;
        $total_units = 0;
        $enrollment_list = [];

        while (!$all_enrollments_query->EOF) {
            $enrollment_id = $all_enrollments_query->fields['PK_ENROLLMENT_MASTER'];
            $units = $all_enrollments_query->fields['UNITS'];

            // Check if this enrollment does NOT have ANY of the selected service providers
            if (!in_array($enrollment_id, $enrollments_with_providers)) {
                $enrollment_count++;
                $total_units += $units;
                $enrollment_list[] = $enrollment_id . " (" . number_format($units, 2) . " units)";
            }

            $all_enrollments_query->MoveNext();
        }

        $no_provider_data[$type_name]['sold'] = $enrollment_count;
        $no_provider_data[$type_name]['units'] = $total_units;
        $no_provider_data[$type_name]['enrollments'] = $enrollment_list;
    }

    // Calculate no provider totals
    $no_provider_total_units = $no_provider_data['pre_original']['units'] + $no_provider_data['original']['units'] + $no_provider_data['extension']['units'] + $no_provider_data['renewal']['units'];
    $no_provider_data['total_units'] = $no_provider_total_units;

    error_log("No provider data - Pre Original: " . $no_provider_data['pre_original']['sold'] . ", Original: " . $no_provider_data['original']['sold'] . ", Extension: " . $no_provider_data['extension']['sold'] . ", Renewal: " . $no_provider_data['renewal']['sold']);

    // Only show no provider section if there are enrollments without providers
    if (
        $no_provider_total_units > 0 ||
        $no_provider_data['pre_original']['sold'] > 0 ||
        $no_provider_data['original']['sold'] > 0 ||
        $no_provider_data['extension']['sold'] > 0 ||
        $no_provider_data['renewal']['sold'] > 0
    ) {

        // No Provider Header
        $sheet->setCellValue('A' . $rowNumber, 'ENROLLMENTS WITHOUT SERVICE PROVIDERS');
        $sheet->mergeCells('A' . $rowNumber . ':D' . $rowNumber);
        $sheet->getStyle('A' . $rowNumber)->getFont()->setBold(true);
        $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F8D7DA');
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

        // Enrollment Types Data for No Provider
        foreach ($enrollment_types as $type_label => $type_key) {
            $sheet->setCellValue('A' . $rowNumber, $type_label);
            $sheet->setCellValue('B' . $rowNumber, $no_provider_data[$type_key]['sold']);
            $sheet->setCellValue('C' . $rowNumber, number_format($no_provider_data[$type_key]['units'], 2));
            $sheet->setCellValue('D' . $rowNumber, !empty($no_provider_data[$type_key]['enrollments']) ? implode(', ', $no_provider_data[$type_key]['enrollments']) : 'None');
            $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->applyFromArray($styleArray);
            $rowNumber++;
        }

        // No Provider Totals
        $no_provider_total_enrollments = $no_provider_data['pre_original']['sold'] + $no_provider_data['original']['sold'] + $no_provider_data['extension']['sold'] + $no_provider_data['renewal']['sold'];

        $sheet->setCellValue('A' . $rowNumber, 'No Provider Total');
        $sheet->setCellValue('B' . $rowNumber, $no_provider_total_enrollments);
        $sheet->setCellValue('C' . $rowNumber, number_format($no_provider_data['total_units'], 2));
        $sheet->setCellValue('D' . $rowNumber, '');
        $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->getFont()->setBold(true);
        $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F8D7DA');
        $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->applyFromArray($styleArray);
        $rowNumber += 2;
    }
}

// Show message only if absolutely no data found
if (empty($all_providers_data) && ($include_no_provider == 0 || $no_provider_data['total_units'] == 0)) {
    $sheet->setCellValue('A' . $rowNumber, 'No data found for the selected criteria.');
    $sheet->mergeCells('A' . $rowNumber . ':D' . $rowNumber);
    $sheet->getStyle('A' . $rowNumber)->getFont()->setBold(true);
    $sheet->getStyle('A' . $rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $rowNumber++;
}

// Add Summary Section
$sheet->setCellValue('A' . $rowNumber, 'TOTAL SOLD COUNTS & UNITS');
$sheet->mergeCells('A' . $rowNumber . ':F' . $rowNumber);
$sheet->getStyle('A' . $rowNumber)->getFont()->setBold(true);
$sheet->getStyle('A' . $rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A' . $rowNumber . ':F' . $rowNumber)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('D4EDDA');
$sheet->getStyle('A' . $rowNumber . ':F' . $rowNumber)->applyFromArray($styleArray);
$rowNumber++;

// Summary Headers
$summary_headers = ['Enrollment Type', 'Pre Original', 'Original', 'Extension', 'Renewal', 'Total'];
$sheet->setCellValue('A' . $rowNumber, $summary_headers[0]);
$sheet->setCellValue('B' . $rowNumber, $summary_headers[1]);
$sheet->setCellValue('C' . $rowNumber, $summary_headers[2]);
$sheet->setCellValue('D' . $rowNumber, $summary_headers[3]);
$sheet->setCellValue('E' . $rowNumber, $summary_headers[4]);
$sheet->setCellValue('F' . $rowNumber, $summary_headers[5]);

for ($col = 0; $col < 6; $col++) {
    $sheet->getStyleByColumnAndRow($col, $rowNumber)->getFont()->setBold(true);
    $sheet->getStyleByColumnAndRow($col, $rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyleByColumnAndRow($col, $rowNumber)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E8F4FF');
}
$sheet->getStyle('A' . $rowNumber . ':F' . $rowNumber)->applyFromArray($styleArray);
$rowNumber++;

// Sold Row
$sheet->setCellValue('A' . $rowNumber, 'Total Sold');
$sheet->setCellValue('B' . $rowNumber, $total_pre_original_sold);
$sheet->setCellValue('C' . $rowNumber, $total_original_sold);
$sheet->setCellValue('D' . $rowNumber, $total_extension_sold);
$sheet->setCellValue('E' . $rowNumber, $total_renewal_sold);
$sheet->setCellValue('F' . $rowNumber, $total_all_sold);
$sheet->getStyle('A' . $rowNumber)->getFont()->setBold(true);
$sheet->getStyle('A' . $rowNumber . ':F' . $rowNumber)->applyFromArray($styleArray);
$rowNumber++;

// Units Row
$sheet->setCellValue('A' . $rowNumber, 'Total Units');
$sheet->setCellValue('B' . $rowNumber, number_format($total_pre_original_units, 2));
$sheet->setCellValue('C' . $rowNumber, number_format($total_original_units, 2));
$sheet->setCellValue('D' . $rowNumber, number_format($total_extension_units, 2));
$sheet->setCellValue('E' . $rowNumber, number_format($total_renewal_units, 2));
$sheet->setCellValue('F' . $rowNumber, number_format($total_all_units_studio, 2));
$sheet->getStyle('A' . $rowNumber)->getFont()->setBold(true);
$sheet->getStyle('A' . $rowNumber . ':F' . $rowNumber)->applyFromArray($styleArray);
$rowNumber += 2;

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
