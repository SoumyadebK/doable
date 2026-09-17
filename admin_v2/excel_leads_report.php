<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "LEADS REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// ---------------------------------------------------------------
// Parse incoming filters (posted as JSON in 'filter_data')
// ---------------------------------------------------------------
$filters = [];
if (!empty($_POST['filter_data'])) {
    $decoded = json_decode($_POST['filter_data'], true);
    if (is_array($decoded)) {
        $filters = $decoded;
    }
}

$date_filter   = isset($filters['date_filter'])   ? $filters['date_filter']   : '';
$status_filter = isset($filters['status_filter']) ? $filters['status_filter'] : '';
$lead_filter   = isset($filters['lead_filter'])   ? $filters['lead_filter']   : '';
$source_filter = isset($filters['source_filter']) ? $filters['source_filter'] : '';
$start_date    = isset($filters['start_date'])    ? $filters['start_date']    : '';
$end_date      = isset($filters['end_date'])      ? $filters['end_date']      : '';

// ---------------------------------------------------------------
// Safe multi-location IN (...) — DEFAULT_LOCATION_ID can be "3,5,7"
// ---------------------------------------------------------------
$allowed_location_ids = array_filter(
    array_map('intval', explode(',', (string)$_SESSION['DEFAULT_LOCATION_ID'])),
    function ($id) {
        return $id > 0;
    }
);
$allowed_location_csv = empty($allowed_location_ids) ? "0" : implode(',', $allowed_location_ids);
$location_condition   = "DOA_LEADS.PK_LOCATION IN (" . $allowed_location_csv . ")";

// ---------------------------------------------------------------
// Build WHERE conditions
// ---------------------------------------------------------------
$where_conditions = [];
$where_conditions[] = $location_condition;

if ($status_filter !== '' && $status_filter !== 'all') {
    $where_conditions[] = "DOA_LEADS.ACTIVE = " . intval($status_filter);
}

if ($lead_filter !== '' && $lead_filter !== 'all') {
    $where_conditions[] = "DOA_LEADS.PK_LEAD_STATUS = " . intval($lead_filter);
}

if ($source_filter !== '' && $source_filter !== 'all') {
    $where_conditions[] = "DOA_LEADS.OPPORTUNITY_SOURCE = '" . mysqli_real_escape_string($db->LinkID, $source_filter) . "'";
}

if ($date_filter == 'range' && $start_date && $end_date) {
    $where_conditions[] = "DATE(DOA_LEADS.CREATED_ON) BETWEEN '" . mysqli_real_escape_string($db->LinkID, $start_date) . "' AND '" . mysqli_real_escape_string($db->LinkID, $end_date) . "'";
} elseif ($date_filter == 'today') {
    $where_conditions[] = "DATE(DOA_LEADS.CREATED_ON) = CURDATE()";
} elseif ($date_filter == 'week') {
    $where_conditions[] = "YEARWEEK(DOA_LEADS.CREATED_ON) = YEARWEEK(CURDATE())";
} elseif ($date_filter == 'month') {
    $where_conditions[] = "MONTH(DOA_LEADS.CREATED_ON) = MONTH(CURDATE()) AND YEAR(DOA_LEADS.CREATED_ON) = YEAR(CURDATE())";
} elseif ($date_filter == 'year') {
    $where_conditions[] = "YEAR(DOA_LEADS.CREATED_ON) = YEAR(CURDATE())";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// ---------------------------------------------------------------
// Account / location info for the header
// ---------------------------------------------------------------
$account_data  = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']));
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';

$location_name = '';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION 
                         WHERE PK_LOCATION IN (" . $allowed_location_csv . ") 
                           AND ACTIVE = 1 
                           AND PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']));
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$concatenatedResults = implode(', ', $resultsArray);

// ---------------------------------------------------------------
// Fetch ALL matching leads (no pagination in export)
// ---------------------------------------------------------------
$query = "SELECT 
        DOA_LEADS.PK_LEADS,
        DOA_LEADS.FIRST_NAME,
        DOA_LEADS.LAST_NAME,
        DOA_LEADS.EMAIL_ID,
        DOA_LEADS.PHONE,
        DOA_LOCATION.LOCATION_NAME,
        DOA_LEAD_STATUS.LEAD_STATUS,
        DOA_LEADS.OPPORTUNITY_SOURCE,
        DOA_LEADS.DESCRIPTION,
        DOA_LEADS.IS_CALLED,
        DOA_LEADS.IS_APPOINTMENT_CREATED,
        DOA_LEADS.ACTIVE,
        DOA_LEADS.CREATED_ON
    FROM DOA_LEADS AS DOA_LEADS
    LEFT JOIN DOA_LEAD_STATUS ON DOA_LEAD_STATUS.PK_LEAD_STATUS = DOA_LEADS.PK_LEAD_STATUS
    LEFT JOIN DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION
    $where_clause 
    ORDER BY DOA_LEADS.CREATED_ON DESC, DOA_LEADS.PK_LEADS DESC";

$result = $db->Execute($query);

// ---------------------------------------------------------------
// Build filter summary text (for a header row)
// ---------------------------------------------------------------
$filter_parts = [];
if ($date_filter) {
    if ($date_filter == 'range' && $start_date && $end_date) {
        $filter_parts[] = 'Date Range: ' . date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));
    } else {
        $filter_parts[] = 'Date: ' . ucfirst($date_filter);
    }
}
if ($status_filter !== '' && $status_filter !== 'all') {
    $filter_parts[] = 'Status: ' . ($status_filter == 1 ? 'Active' : 'Inactive');
}
if ($lead_filter !== '' && $lead_filter !== 'all') {
    $ls_res = $db->Execute("SELECT LEAD_STATUS FROM DOA_LEAD_STATUS WHERE PK_LEAD_STATUS = " . intval($lead_filter));
    if ($ls_res && $ls_res->RecordCount() > 0) {
        $filter_parts[] = 'Lead Status: ' . $ls_res->fields['LEAD_STATUS'];
    }
}
if ($source_filter !== '' && $source_filter !== 'all') {
    $filter_parts[] = 'Source: ' . $source_filter;
}
$filter_summary = empty($filter_parts) ? 'All Leads' : implode('  |  ', $filter_parts);

// ---------------------------------------------------------------
// Create the Excel workbook
// ---------------------------------------------------------------
$inputFileType  = 'Excel2007';
$outputFileName = 'LEADS_REPORT_' . date('Ymd_His') . '.xlsx';

$objReader  = PHPExcel_IOFactory::createReader($inputFileType);
$objReader->setIncludeCharts(TRUE);
$objPHPExcel = new PHPExcel();
$objWriter   = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

// Column widths
$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(6);   // #
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(18);  // First Name
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(18);  // Last Name
$objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(32);  // Email
$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setWidth(18);  // Phone
$objPHPExcel->getActiveSheet()->getColumnDimension("F")->setWidth(22);  // Location
$objPHPExcel->getActiveSheet()->getColumnDimension("G")->setWidth(22);  // Lead Status
$objPHPExcel->getActiveSheet()->getColumnDimension("H")->setWidth(24);  // Opportunity Source
$objPHPExcel->getActiveSheet()->getColumnDimension("I")->setWidth(12);  // Called
$objPHPExcel->getActiveSheet()->getColumnDimension("J")->setWidth(20);  // Appointment Created
$objPHPExcel->getActiveSheet()->getColumnDimension("K")->setWidth(14);  // Created On
$objPHPExcel->getActiveSheet()->getColumnDimension("L")->setWidth(12);  // Status

// --- Title row ---
$cell_no = "A1";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($title);
$objPHPExcel->getActiveSheet()->mergeCells('A1:L1');
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(28);

// --- Location + date row ---
$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);

$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:F2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Location: (" . $concatenatedResults . ")");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "G2";
$objPHPExcel->getActiveSheet()->mergeCells('G2:L2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Generated: " . date('m/d/Y g:i A'));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// --- Filter summary row ---
$cell_no = "A3";
$objPHPExcel->getActiveSheet()->mergeCells('A3:L3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Filters: " . $filter_summary);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setItalic(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setSize(11);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getRowDimension(3)->setRowHeight(20);

// --- Header row ---
$headers = [
    'A' => '#',
    'B' => 'First Name',
    'C' => 'Last Name',
    'D' => 'Email',
    'E' => 'Phone',
    'F' => 'Location',
    'G' => 'Lead Status',
    'H' => 'Opportunity Source',
    'I' => 'Called',
    'J' => 'Appointment Created',
    'K' => 'Created On',
    'L' => 'Status',
];

$header_row = 4;
foreach ($headers as $col => $label) {
    $objPHPExcel->getActiveSheet()->setCellValue($col . $header_row, $label);
    $objPHPExcel->getActiveSheet()->getStyle($col . $header_row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle($col . $header_row)->getFont()->getColor()->setRGB('FFFFFF');
    $objPHPExcel->getActiveSheet()->getStyle($col . $header_row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    $objPHPExcel->getActiveSheet()->getStyle($col . $header_row)->getFill()->getStartColor()->setRGB('39B54A');
    $objPHPExcel->getActiveSheet()->getStyle($col . $header_row)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($col . $header_row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}
$objPHPExcel->getActiveSheet()->getRowDimension($header_row)->setRowHeight(22);

// --- Data rows ---
$i = $header_row + 1;
$counter = 1;

if ($result && $result->RecordCount() > 0) {
    while (!$result->EOF) {
        $is_active  = ((int)$result->fields['ACTIVE'] === 1);
        $is_called  = !empty($result->fields['IS_CALLED']);
        $is_appt    = !empty($result->fields['IS_APPOINTMENT_CREATED']);
        $created_on = !empty($result->fields['CREATED_ON']) ? date('m-d-Y', strtotime($result->fields['CREATED_ON'])) : '';

        // A: #
        $objPHPExcel->getActiveSheet()->getCell("A" . $i)->setValue($counter);
        $objPHPExcel->getActiveSheet()->getStyle("A" . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // B: First Name
        $objPHPExcel->getActiveSheet()->getCell("B" . $i)->setValue($result->fields['FIRST_NAME'] ?? '');

        // C: Last Name
        $objPHPExcel->getActiveSheet()->getCell("C" . $i)->setValue($result->fields['LAST_NAME'] ?? '');

        // D: Email
        $objPHPExcel->getActiveSheet()->getCell("D" . $i)->setValue($result->fields['EMAIL_ID'] ?? '');

        // E: Phone
        $objPHPExcel->getActiveSheet()->getCell("E" . $i)->setValue($result->fields['PHONE'] ?? '');
        $objPHPExcel->getActiveSheet()->getStyle("E" . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // F: Location
        $objPHPExcel->getActiveSheet()->getCell("F" . $i)->setValue($result->fields['LOCATION_NAME'] ?? '');

        // G: Lead Status
        $objPHPExcel->getActiveSheet()->getCell("G" . $i)->setValue($result->fields['LEAD_STATUS'] ?? '');

        // H: Opportunity Source
        $objPHPExcel->getActiveSheet()->getCell("H" . $i)->setValue($result->fields['OPPORTUNITY_SOURCE'] ?? '');

        // I: Called
        $objPHPExcel->getActiveSheet()->getCell("I" . $i)->setValue($is_called ? 'Yes' : 'No');
        $objPHPExcel->getActiveSheet()->getStyle("I" . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        if ($is_called) {
            $objPHPExcel->getActiveSheet()->getStyle("I" . $i)->getFont()->getColor()->setRGB('15803D');
            $objPHPExcel->getActiveSheet()->getStyle("I" . $i)->getFont()->setBold(true);
        }

        // J: Appointment Created
        $objPHPExcel->getActiveSheet()->getCell("J" . $i)->setValue($is_appt ? 'Yes' : 'No');
        $objPHPExcel->getActiveSheet()->getStyle("J" . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        if ($is_appt) {
            $objPHPExcel->getActiveSheet()->getStyle("J" . $i)->getFont()->getColor()->setRGB('92400E');
            $objPHPExcel->getActiveSheet()->getStyle("J" . $i)->getFont()->setBold(true);
        }

        // K: Created On
        $objPHPExcel->getActiveSheet()->getCell("K" . $i)->setValue($created_on);
        $objPHPExcel->getActiveSheet()->getStyle("K" . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // L: Status
        $objPHPExcel->getActiveSheet()->getCell("L" . $i)->setValue($is_active ? 'Active' : 'Inactive');
        $objPHPExcel->getActiveSheet()->getStyle("L" . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle("L" . $i)->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle("L" . $i)->getFont()->getColor()->setRGB($is_active ? '15803D' : 'B91C1C');

        // Vertical alignment for the whole row
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'] as $col) {
            $objPHPExcel->getActiveSheet()->getStyle($col . $i)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }

        // Alternating row shading
        if ($counter % 2 === 0) {
            $objPHPExcel->getActiveSheet()->getStyle("A" . $i . ":L" . $i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle("A" . $i . ":L" . $i)->getFill()->getStartColor()->setRGB('F9FAFB');
        }

        $result->MoveNext();
        $i++;
        $counter++;
    }
    $last_data_row = $i - 1;
} else {
    // No results — write a single merged "no records" row
    $objPHPExcel->getActiveSheet()->mergeCells("A" . $i . ":L" . $i);
    $objPHPExcel->getActiveSheet()->getCell("A" . $i)->setValue("No leads found for the selected filters");
    $objPHPExcel->getActiveSheet()->getStyle("A" . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle("A" . $i)->getFont()->setItalic(true);
    $last_data_row = $i;
}

// --- Outer border around the table ---
$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => 'D1D5DB']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A' . $header_row . ':L' . $last_data_row)->applyFromArray($styleArray);

// --- Freeze header ---
$objPHPExcel->getActiveSheet()->freezePane('A' . ($header_row + 1));

// --- Autofilter ---
if ($result && $result->RecordCount() > 0) {
    $objPHPExcel->getActiveSheet()->setAutoFilter('A' . $header_row . ':L' . $last_data_row);
}

// --- Landscape orientation ---
$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(1);
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToHeight(0);

// ---------------------------------------------------------------
// Save the file and redirect (same pattern as your other exports)
// ---------------------------------------------------------------
$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
header("location:" . $outputFileName);
exit;
