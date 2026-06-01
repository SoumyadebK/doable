<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "LESSONS TAUGHT BY SERVICE PROVIDER REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];
$include_no_provider = isset($_GET['include_no_provider']) ? $_GET['include_no_provider'] : 0;

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));

// Fix for handling multiple service provider IDs
if (isset($_GET['PK_USER']) && is_array($_GET['PK_USER'])) {
    $service_provider_id = implode(',', $_GET['PK_USER']);
} else {
    $service_provider_id = $_GET['service_provider_id'] ?? '';
}

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
    $concatenatedResults .= $result;
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

$cell1 = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
define('EOL', (PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

$total_fields = 70;
for ($i = 0; $i <= $total_fields; $i++) {
    if ($i <= 25)
        $cell[] = $cell1[$i];
    else {
        $j = floor($i / 26) - 1;
        $k = ($i % 26);
        $cell[] = $cell1[$j] . $cell1[$k];
    }
}

$inputFileType = 'Excel2007';
$outputFileName = 'LESSONS_TAUGHT_BY_SERVICE_PROVIDER_REPORT.xlsx';

$objReader = PHPExcel_IOFactory::createReader($inputFileType);
$objReader->setIncludeCharts(TRUE);
$objPHPExcel = new PHPExcel();
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

// Set column widths
$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(60);

// Style definitions
$headerStyle = array(
    'font' => array('bold' => true, 'size' => 12),
    'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
    'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => array('rgb' => 'D4EDDA'))
);

$providerHeaderStyle = array(
    'font' => array('bold' => true, 'size' => 11),
    'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER),
    'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => array('rgb' => 'E9ECEF'))
);

$groupStyle = array(
    'font' => array('bold' => true),
    'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => array('rgb' => 'FFF3CD'))
);

$todoStyle = array(
    'font' => array('bold' => true),
    'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => array('rgb' => 'EDFFFE'))
);

$totalStyle = array(
    'font' => array('bold' => true),
    'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => array('rgb' => 'F8F9FA'))
);

$borderStyle = array(
    'borders' => array(
        'allBorders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000'))
    )
);

// Title Row
$objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
$objPHPExcel->getActiveSheet()->setCellValue('A1', $title);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18)->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// Location Row
$objPHPExcel->getActiveSheet()->mergeCells('A2:C2');
$objPHPExcel->getActiveSheet()->setCellValue('A2', $concatenatedResults);
$objPHPExcel->getActiveSheet()->getStyle('A2')->getFont()->setBold(true);

// Date Range Row
$objPHPExcel->getActiveSheet()->mergeCells('D2:H2');
$objPHPExcel->getActiveSheet()->setCellValue('D2', '(' . date('m/d/Y', strtotime($from_date)) . ' - ' . date('m/d/Y', strtotime($to_date)) . ')');
$objPHPExcel->getActiveSheet()->getStyle('C2')->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle('C2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

// Enrollment types mapping
$enrollment_types = [
    5 => 'Pre Original',
    2 => 'Original',
    9 => 'Extension',
    13 => 'Renewal'
];

// Get all service providers
$providers_query = $db->Execute("
    SELECT DISTINCT DU.PK_USER, CONCAT(DU.FIRST_NAME, ' ', DU.LAST_NAME) AS PROVIDER_NAME
    FROM DOA_USERS DU
    INNER JOIN DOA_USER_ROLES DUR ON DU.PK_USER = DUR.PK_USER
    WHERE DU.ACTIVE = 1 
    AND DUR.PK_ROLES = 5
    AND DU.PK_ACCOUNT_MASTER = '" . $_SESSION['PK_ACCOUNT_MASTER'] . "'
    ORDER BY DU.FIRST_NAME, DU.LAST_NAME
");

$all_providers_data = [];
$grand_totals = [
    'pre_original' => ['lessons' => 0, 'customers' => 0],
    'original' => ['lessons' => 0, 'customers' => 0],
    'extension' => ['lessons' => 0, 'customers' => 0],
    'renewal' => ['lessons' => 0, 'customers' => 0],
    'group' => ['lessons' => 0, 'customers' => 0],
    'total_lessons' => 0,
    'total_customers' => 0
];

$current_row = 4;

while (!$providers_query->EOF) {
    $provider_id = $providers_query->fields['PK_USER'];
    $provider_name = $providers_query->fields['PROVIDER_NAME'];

    // Skip if specific providers are selected and this one isn't in the list
    if (!empty($service_provider_id) && $service_provider_id != '0' && !in_array($provider_id, explode(',', $service_provider_id))) {
        $providers_query->MoveNext();
        continue;
    }

    // Initialize provider data
    $provider_data = [
        'name' => $provider_name,
        'pre_original' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
        'original' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
        'extension' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
        'renewal' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
        'group' => ['lessons' => 0, 'customers' => 0, 'sessions' => []]
    ];

    // Get private lessons for this provider by enrollment type
    foreach ($enrollment_types as $type_id => $type_name) {
        $type_key = strtolower(str_replace(' ', '_', $type_name));

        $lessons_query = $db_account->Execute("
            SELECT
                am.PK_APPOINTMENT_MASTER,
                am.DATE,
                es.NUMBER_OF_SESSION,
                ac.PK_USER_MASTER,
                CONCAT(cd.PARTNER_FIRST_NAME, ' ', cd.PARTNER_LAST_NAME) AS PARTNER_NAME,
                CONCAT(us.FIRST_NAME, ' ', us.LAST_NAME) AS CUSTOMER_NAME,
                sm.SERVICE_NAME
            FROM DOA_APPOINTMENT_MASTER am
            INNER JOIN DOA_APPOINTMENT_SERVICE_PROVIDER asp ON am.PK_APPOINTMENT_MASTER = asp.PK_APPOINTMENT_MASTER
            LEFT JOIN DOA_APPOINTMENT_CUSTOMER ac ON am.PK_APPOINTMENT_MASTER = ac.PK_APPOINTMENT_MASTER
            LEFT JOIN DOA_ENROLLMENT_SERVICE es ON am.PK_ENROLLMENT_SERVICE = es.PK_ENROLLMENT_SERVICE
            LEFT JOIN DOA_ENROLLMENT_MASTER em ON am.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
            LEFT JOIN DOA_MASTER.DOA_USER_MASTER dc ON ac.PK_USER_MASTER = dc.PK_USER_MASTER
            LEFT JOIN DOA_MASTER.DOA_USERS us ON us.PK_USER = dc.PK_USER
            LEFT JOIN DOA_SERVICE_MASTER sm ON sm.PK_SERVICE_MASTER = am.PK_SERVICE_MASTER
            LEFT JOIN DOA_SERVICE_CODE sc ON am.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
            LEFT JOIN DOA_CUSTOMER_DETAILS cd ON ac.PK_USER_MASTER = cd.PK_USER_MASTER
            WHERE DATE(am.DATE) BETWEEN '$from_date' AND '$to_date'
            AND am.PK_APPOINTMENT_STATUS = 2
            AND asp.PK_USER = $provider_id
            AND em.PK_ENROLLMENT_TYPE = $type_id
            AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL)
            ORDER BY am.DATE
        ");

        $lesson_count = 0;
        $unique_customers = [];
        $session_details = [];
        $processed_appointments = [];

        while (!$lessons_query->EOF) {
            $appointment_id = $lessons_query->fields['PK_APPOINTMENT_MASTER'];
            // FIXED: Correct logic for NUMBER_OF_SESSION
            $num_sessions = $lessons_query->fields['NUMBER_OF_SESSION'] ? 1 : $lessons_query->fields['NUMBER_OF_SESSION'];
            $customer_id = $lessons_query->fields['PK_USER_MASTER'];
            $customer_name = $lessons_query->fields['CUSTOMER_NAME'] ? $lessons_query->fields['CUSTOMER_NAME'] : 'Unknown';
            $partner_name = (!empty($lessons_query->fields['PARTNER_NAME']) && trim($lessons_query->fields['PARTNER_NAME']) !== '')
                ? " (Partner: " . $lessons_query->fields['PARTNER_NAME'] . ")"
                : '';
            $service_date = $lessons_query->fields['DATE'];

            $lesson_count += $num_sessions;

            if ($customer_id > 0 && !in_array($customer_id, $unique_customers)) {
                $unique_customers[] = $customer_id;
            }

            if (!in_array($appointment_id, $processed_appointments)) {
                $session_details[] = date('m/d/Y', strtotime($service_date)) . " - " . $customer_name . $partner_name . " (" . $num_sessions . " lesson" . ($num_sessions > 1 ? 's' : '') . ")";
                $processed_appointments[] = $appointment_id;
            }

            $lessons_query->MoveNext();
        }

        $provider_data[$type_key]['lessons'] = $lesson_count;
        $provider_data[$type_key]['customers'] = $unique_customers;
        $provider_data[$type_key]['sessions'] = $session_details;

        $grand_totals[$type_key]['lessons'] += $lesson_count;
        $grand_totals[$type_key]['customers'] += count($unique_customers);
    }

    // Get group lessons for this provider
    $group_query = $db_account->Execute("
        SELECT
            am.PK_APPOINTMENT_MASTER,
            am.DATE,
            es.NUMBER_OF_SESSION,
            COUNT(DISTINCT ac.PK_USER_MASTER) AS ATTENDEE_COUNT,
            sm.SERVICE_NAME
        FROM DOA_APPOINTMENT_MASTER am
        INNER JOIN DOA_APPOINTMENT_SERVICE_PROVIDER asp ON am.PK_APPOINTMENT_MASTER = asp.PK_APPOINTMENT_MASTER
        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ac ON am.PK_APPOINTMENT_MASTER = ac.PK_APPOINTMENT_MASTER
        LEFT JOIN DOA_ENROLLMENT_SERVICE es ON am.PK_ENROLLMENT_SERVICE = es.PK_ENROLLMENT_SERVICE
        LEFT JOIN DOA_ENROLLMENT_MASTER em ON am.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
        LEFT JOIN DOA_SERVICE_MASTER sm ON sm.PK_SERVICE_MASTER = am.PK_SERVICE_MASTER
        LEFT JOIN DOA_SERVICE_CODE sc ON am.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
        WHERE DATE(am.DATE) BETWEEN '$from_date' AND '$to_date'
        AND am.PK_APPOINTMENT_STATUS = 2
        AND asp.PK_USER = $provider_id
        AND sc.IS_GROUP = 1
        GROUP BY am.PK_APPOINTMENT_MASTER, am.DATE, es.NUMBER_OF_SESSION, sm.SERVICE_NAME
        ORDER BY am.DATE
    ");

    $group_lesson_count = 0;
    $group_customer_count = 0;
    $group_details = [];

    while (!$group_query->EOF) {
        $num_sessions = $group_query->fields['NUMBER_OF_SESSION'] ? $group_query->fields['NUMBER_OF_SESSION'] : 1;
        $attendee_count = $group_query->fields['ATTENDEE_COUNT'] ? $group_query->fields['ATTENDEE_COUNT'] : 0;
        $service_date = $group_query->fields['DATE'];
        $service_name = $group_query->fields['SERVICE_NAME'] ? $group_query->fields['SERVICE_NAME'] : 'Group Lesson';

        $group_lesson_count += $num_sessions;
        $group_customer_count += $attendee_count;

        $group_details[] = date('m/d/Y', strtotime($service_date)) . " - " . $service_name . ": " . $num_sessions . " session" . ($num_sessions > 1 ? 's' : '') . " (" . $attendee_count . " attendees)";

        $group_query->MoveNext();
    }

    $provider_data['group']['lessons'] = $group_lesson_count;
    $provider_data['group']['customers'] = $group_customer_count;
    $provider_data['group']['sessions'] = $group_details;

    $grand_totals['group']['lessons'] += $group_lesson_count;
    $grand_totals['group']['customers'] += $group_customer_count;

    // Get to-do lessons for this provider
    $to_do_query = $db_account->Execute("
                                            SELECT
                                                am.PK_SPECIAL_APPOINTMENT,
                                                am.TITLE,
                                                COUNT(DISTINCT ac.PK_USER_MASTER) AS ATTENDEE_COUNT,
                                                sc.SCHEDULING_NAME,
                                                sc.SCHEDULING_CODE,
                                                am.DATE
                                            FROM DOA_SPECIAL_APPOINTMENT am
                                            INNER JOIN DOA_SPECIAL_APPOINTMENT_USER asp ON am.PK_SPECIAL_APPOINTMENT = asp.PK_SPECIAL_APPOINTMENT
                                            LEFT JOIN DOA_SPECIAL_APPOINTMENT_CUSTOMER ac ON am.PK_SPECIAL_APPOINTMENT = ac.PK_SPECIAL_APPOINTMENT
                                            LEFT JOIN DOA_SCHEDULING_CODE sc ON am.PK_SCHEDULING_CODE = sc.PK_SCHEDULING_CODE
                                            WHERE DATE(am.DATE) BETWEEN '$from_date' AND '$to_date'
                                            AND am.PK_APPOINTMENT_STATUS = 2
                                            AND asp.PK_USER = $provider_id
                                            GROUP BY am.PK_SPECIAL_APPOINTMENT, am.DATE
                                            ORDER BY am.DATE
                                        ");

    $to_do_lesson_count = 0;
    $to_do_customer_count = 0;
    $to_do_details = [];

    while (!$to_do_query->EOF) {
        $num_sessions = 1; // Assuming each record represents one session, adjust if needed
        $attendee_count = $to_do_query->fields['ATTENDEE_COUNT'] ? $to_do_query->fields['ATTENDEE_COUNT'] : 0;
        $service_date = $to_do_query->fields['DATE'];
        $service_name = $to_do_query->fields['SCHEDULING_NAME'] ? $to_do_query->fields['SCHEDULING_NAME'] : 'To Do Lesson';
        $scheduling_code = $to_do_query->fields['SCHEDULING_CODE'] ? $to_do_query->fields['SCHEDULING_CODE'] : '';

        $to_do_lesson_count += $num_sessions;
        $to_do_customer_count += $attendee_count;

        $to_do_details[] = date('m/d/Y', strtotime($service_date)) . " - " . $service_name . " (" . $scheduling_code . "): " . $num_sessions . " session" . ($num_sessions > 1 ? 's' : '') . " (" . $attendee_count . " attendees)";

        $to_do_query->MoveNext();
    }

    $provider_data['to_do']['lessons'] = $to_do_lesson_count;
    $provider_data['to_do']['customers'] = $to_do_customer_count;
    $provider_data['to_do']['sessions'] = $to_do_details;

    // Add to grand totals for to-do
    $grand_totals['to_do']['lessons'] += $to_do_lesson_count;
    $grand_totals['to_do']['customers'] += $to_do_customer_count;

    // Calculate provider totals
    $provider_total_lessons = $provider_data['pre_original']['lessons'] +
        $provider_data['original']['lessons'] +
        $provider_data['extension']['lessons'] +
        $provider_data['renewal']['lessons'] +
        $group_lesson_count +
        $to_do_lesson_count;

    $provider_total_customers = count($provider_data['pre_original']['customers']) +
        count($provider_data['original']['customers']) +
        count($provider_data['extension']['customers']) +
        count($provider_data['renewal']['customers']) +
        $group_customer_count +
        $to_do_customer_count;

    $provider_data['total_lessons'] = $provider_total_lessons;
    $provider_data['total_customers'] = $provider_total_customers;

    $provider_data['pre_original']['customer_count'] = count($provider_data['pre_original']['customers']);
    $provider_data['original']['customer_count'] = count($provider_data['original']['customers']);
    $provider_data['extension']['customer_count'] = count($provider_data['extension']['customers']);
    $provider_data['renewal']['customer_count'] = count($provider_data['renewal']['customers']);
    $provider_data['to_do']['customer_count'] = count($provider_data['to_do']['customers']);

    $all_providers_data[] = $provider_data;

    $providers_query->MoveNext();
}

// Calculate grand totals
$grand_totals['total_lessons'] = $grand_totals['pre_original']['lessons'] +
    $grand_totals['original']['lessons'] +
    $grand_totals['extension']['lessons'] +
    $grand_totals['renewal']['lessons'] +
    $grand_totals['group']['lessons'] +
    $grand_totals['to_do']['lessons'];

$grand_totals['total_customers'] = $grand_totals['pre_original']['customers'] +
    $grand_totals['original']['customers'] +
    $grand_totals['extension']['customers'] +
    $grand_totals['renewal']['customers'] +
    $grand_totals['group']['customers'] +
    $grand_totals['to_do']['customers'];

// ========== SUMMARY TOTALS TABLE ==========
$summary_start_row = $current_row;

$objPHPExcel->getActiveSheet()->mergeCells("A$current_row:H$current_row");
$objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "SUMMARY TOTALS");
$objPHPExcel->getActiveSheet()->getStyle("A$current_row")->applyFromArray($headerStyle);
$current_row++;

// Headers
$objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "Enrollment Type");
$objPHPExcel->getActiveSheet()->setCellValue("B$current_row", "Pre Original");
$objPHPExcel->getActiveSheet()->setCellValue("C$current_row", "Original");
$objPHPExcel->getActiveSheet()->setCellValue("D$current_row", "Extension");
$objPHPExcel->getActiveSheet()->setCellValue("E$current_row", "Renewal");
$objPHPExcel->getActiveSheet()->setCellValue("F$current_row", "Group");
$objPHPExcel->getActiveSheet()->setCellValue("G$current_row", "To Do");
$objPHPExcel->getActiveSheet()->setCellValue("H$current_row", "TOTAL");
$objPHPExcel->getActiveSheet()->getStyle("A$current_row:H$current_row")->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle("A$current_row:H$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$current_row++;

// Total Lessons row
$objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "Total Lessons");
$objPHPExcel->getActiveSheet()->setCellValue("B$current_row", $grand_totals['pre_original']['lessons']);
$objPHPExcel->getActiveSheet()->setCellValue("C$current_row", $grand_totals['original']['lessons']);
$objPHPExcel->getActiveSheet()->setCellValue("D$current_row", $grand_totals['extension']['lessons']);
$objPHPExcel->getActiveSheet()->setCellValue("E$current_row", $grand_totals['renewal']['lessons']);
$objPHPExcel->getActiveSheet()->setCellValue("F$current_row", $grand_totals['group']['lessons']);
$objPHPExcel->getActiveSheet()->setCellValue("G$current_row", $grand_totals['to_do']['lessons']);
$objPHPExcel->getActiveSheet()->setCellValue("H$current_row", $grand_totals['total_lessons']);
$objPHPExcel->getActiveSheet()->getStyle("A$current_row")->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle("H$current_row")->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle("A$current_row:H$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$current_row++;

// Total Customers row
$objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "Total Customers");
$objPHPExcel->getActiveSheet()->setCellValue("B$current_row", $grand_totals['pre_original']['customers']);
$objPHPExcel->getActiveSheet()->setCellValue("C$current_row", $grand_totals['original']['customers']);
$objPHPExcel->getActiveSheet()->setCellValue("D$current_row", $grand_totals['extension']['customers']);
$objPHPExcel->getActiveSheet()->setCellValue("E$current_row", $grand_totals['renewal']['customers']);
$objPHPExcel->getActiveSheet()->setCellValue("F$current_row", $grand_totals['group']['customers']);
$objPHPExcel->getActiveSheet()->setCellValue("G$current_row", $grand_totals['to_do']['customers']);
$objPHPExcel->getActiveSheet()->setCellValue("H$current_row", $grand_totals['total_customers']);
$objPHPExcel->getActiveSheet()->getStyle("A$current_row")->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle("H$current_row")->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle("A$current_row:H$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$current_row += 2;

// Apply borders to summary table
$objPHPExcel->getActiveSheet()->getStyle("A" . $summary_start_row . ":H" . ($current_row - 1))->applyFromArray($borderStyle);

// ========== PROVIDER TABLES ==========
foreach ($all_providers_data as $provider_data) {
    $provider_start_row = $current_row;

    // Provider header
    $objPHPExcel->getActiveSheet()->mergeCells("A$current_row:H$current_row");
    $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", $provider_data['name']);
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row")->applyFromArray($providerHeaderStyle);
    $current_row++;

    // Column headers
    $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "Enrollment Type");
    $objPHPExcel->getActiveSheet()->setCellValue("B$current_row", "Lessons Taught");
    $objPHPExcel->getActiveSheet()->setCellValue("C$current_row", "Customers Served");
    $objPHPExcel->getActiveSheet()->mergeCells("D$current_row:H$current_row");
    $objPHPExcel->getActiveSheet()->setCellValue("D$current_row", "Session Details");
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row:H$current_row")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row:H$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $current_row++;

    // Pre Original
    $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "Pre Original");
    $objPHPExcel->getActiveSheet()->setCellValue("B$current_row", $provider_data['pre_original']['lessons']);
    $objPHPExcel->getActiveSheet()->setCellValue("C$current_row", $provider_data['pre_original']['customer_count']);
    $session_text = !empty($provider_data['pre_original']['sessions']) ? implode("\n", array_slice($provider_data['pre_original']['sessions'], 0, 100)) : 'None';
    if (count($provider_data['pre_original']['sessions']) > 100) {
        $session_text .= "\n... and " . (count($provider_data['pre_original']['sessions']) - 100) . " more";
    }
    $objPHPExcel->getActiveSheet()->mergeCells("D$current_row:H$current_row");
    $objPHPExcel->getActiveSheet()->setCellValue("D$current_row", $session_text);
    $objPHPExcel->getActiveSheet()->getStyle("D$current_row")->getAlignment()->setWrapText(true);
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row:C$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $current_row++;

    // Original
    $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "Original");
    $objPHPExcel->getActiveSheet()->setCellValue("B$current_row", $provider_data['original']['lessons']);
    $objPHPExcel->getActiveSheet()->setCellValue("C$current_row", $provider_data['original']['customer_count']);
    $session_text = !empty($provider_data['original']['sessions']) ? implode("\n", array_slice($provider_data['original']['sessions'], 0, 100)) : 'None';
    if (count($provider_data['original']['sessions']) > 100) {
        $session_text .= "\n... and " . (count($provider_data['original']['sessions']) - 100) . " more";
    }
    $objPHPExcel->getActiveSheet()->mergeCells("D$current_row:H$current_row");
    $objPHPExcel->getActiveSheet()->setCellValue("D$current_row", $session_text);
    $objPHPExcel->getActiveSheet()->getStyle("D$current_row")->getAlignment()->setWrapText(true);
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row:C$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $current_row++;

    // Extension
    $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "Extension");
    $objPHPExcel->getActiveSheet()->setCellValue("B$current_row", $provider_data['extension']['lessons']);
    $objPHPExcel->getActiveSheet()->setCellValue("C$current_row", $provider_data['extension']['customer_count']);
    $session_text = !empty($provider_data['extension']['sessions']) ? implode("\n", array_slice($provider_data['extension']['sessions'], 0, 100)) : 'None';
    if (count($provider_data['extension']['sessions']) > 100) {
        $session_text .= "\n... and " . (count($provider_data['extension']['sessions']) - 100) . " more";
    }
    $objPHPExcel->getActiveSheet()->mergeCells("D$current_row:H$current_row");
    $objPHPExcel->getActiveSheet()->setCellValue("D$current_row", $session_text);
    $objPHPExcel->getActiveSheet()->getStyle("D$current_row")->getAlignment()->setWrapText(true);
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row:C$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $current_row++;

    // Renewal
    $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "Renewal");
    $objPHPExcel->getActiveSheet()->setCellValue("B$current_row", $provider_data['renewal']['lessons']);
    $objPHPExcel->getActiveSheet()->setCellValue("C$current_row", $provider_data['renewal']['customer_count']);
    $session_text = !empty($provider_data['renewal']['sessions']) ? implode("\n", array_slice($provider_data['renewal']['sessions'], 0, 100)) : 'None';
    if (count($provider_data['renewal']['sessions']) > 100) {
        $session_text .= "\n... and " . (count($provider_data['renewal']['sessions']) - 100) . " more";
    }
    $objPHPExcel->getActiveSheet()->mergeCells("D$current_row:H$current_row");
    $objPHPExcel->getActiveSheet()->setCellValue("D$current_row", $session_text);
    $objPHPExcel->getActiveSheet()->getStyle("D$current_row")->getAlignment()->setWrapText(true);
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row:C$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $current_row++;

    // Group Lessons
    $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "Group Lessons");
    $objPHPExcel->getActiveSheet()->setCellValue("B$current_row", $provider_data['group']['lessons']);
    $objPHPExcel->getActiveSheet()->setCellValue("C$current_row", $provider_data['group']['customers']);
    $session_text = !empty($provider_data['group']['sessions']) ? implode("\n", array_slice($provider_data['group']['sessions'], 0, 100)) : 'None';
    if (count($provider_data['group']['sessions']) > 100) {
        $session_text .= "\n... and " . (count($provider_data['group']['sessions']) - 100) . " more";
    }
    $objPHPExcel->getActiveSheet()->mergeCells("D$current_row:H$current_row");
    $objPHPExcel->getActiveSheet()->setCellValue("D$current_row", $session_text);
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row:H$current_row")->applyFromArray($groupStyle);
    $objPHPExcel->getActiveSheet()->getStyle("D$current_row")->getAlignment()->setWrapText(true);
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row:C$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $current_row++;

    // To Do Lessons
    $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "To Do");
    $objPHPExcel->getActiveSheet()->setCellValue("B$current_row", $provider_data['to_do']['lessons']);
    $objPHPExcel->getActiveSheet()->setCellValue("C$current_row", $provider_data['to_do']['customers']);
    $session_text = !empty($provider_data['to_do']['sessions']) ? implode("\n", array_slice($provider_data['to_do']['sessions'], 0, 100)) : 'None';
    if (count($provider_data['to_do']['sessions']) > 100) {
        $session_text .= "\n... and " . (count($provider_data['to_do']['sessions']) - 100) . " more";
    }
    $objPHPExcel->getActiveSheet()->mergeCells("D$current_row:H$current_row");
    $objPHPExcel->getActiveSheet()->setCellValue("D$current_row", $session_text);
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row:H$current_row")->applyFromArray($todoStyle);
    $objPHPExcel->getActiveSheet()->getStyle("D$current_row")->getAlignment()->setWrapText(true);
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row:C$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $current_row++;

    // Provider Total
    $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "PROVIDER TOTAL");
    $objPHPExcel->getActiveSheet()->setCellValue("B$current_row", $provider_data['total_lessons']);
    $objPHPExcel->getActiveSheet()->setCellValue("C$current_row", $provider_data['total_customers']);
    $objPHPExcel->getActiveSheet()->mergeCells("D$current_row:H$current_row");
    $objPHPExcel->getActiveSheet()->setCellValue("D$current_row", "Total Lessons: " . $provider_data['total_lessons'] . " | Total Customers: " . $provider_data['total_customers']);
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row:H$current_row")->applyFromArray($totalStyle);
    $objPHPExcel->getActiveSheet()->getStyle("A$current_row:C$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $current_row += 2;

    // Apply borders to provider table
    $objPHPExcel->getActiveSheet()->getStyle("A$provider_start_row:H" . ($current_row - 1))->applyFromArray($borderStyle);
}

// ========== NO PROVIDER SECTION ==========
if ($include_no_provider == 1) {
    $no_provider_query = $db_account->Execute("
        SELECT 
            am.PK_APPOINTMENT_MASTER,
            am.DATE,
            es.NUMBER_OF_SESSION,
            em.PK_ENROLLMENT_TYPE,
            ac.PK_USER_MASTER,
            CONCAT(us.FIRST_NAME, ' ', us.LAST_NAME) AS CUSTOMER_NAME,
            sm.SERVICE_NAME,
            sc.IS_GROUP,
            (SELECT COUNT(DISTINCT ac2.PK_USER_MASTER) 
             FROM DOA_APPOINTMENT_CUSTOMER ac2 
             WHERE ac2.PK_APPOINTMENT_MASTER = am.PK_APPOINTMENT_MASTER) AS ATTENDEE_COUNT
        FROM DOA_APPOINTMENT_MASTER am
        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ac ON am.PK_APPOINTMENT_MASTER = ac.PK_APPOINTMENT_MASTER
        LEFT JOIN DOA_ENROLLMENT_SERVICE es ON am.PK_ENROLLMENT_SERVICE = es.PK_ENROLLMENT_SERVICE
        LEFT JOIN DOA_ENROLLMENT_MASTER em ON am.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
        LEFT JOIN DOA_MASTER.DOA_USER_MASTER dc ON ac.PK_USER_MASTER = dc.PK_USER_MASTER
        LEFT JOIN DOA_MASTER.DOA_USERS us ON us.PK_USER = dc.PK_USER
        LEFT JOIN DOA_SERVICE_MASTER sm ON sm.PK_SERVICE_MASTER = am.PK_SERVICE_MASTER
        LEFT JOIN DOA_SERVICE_CODE sc ON am.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
        WHERE DATE(am.DATE) BETWEEN '$from_date' AND '$to_date'
        AND am.PK_APPOINTMENT_STATUS = 2
        AND NOT EXISTS (
            SELECT 1 FROM DOA_APPOINTMENT_SERVICE_PROVIDER asp 
            WHERE asp.PK_APPOINTMENT_MASTER = am.PK_APPOINTMENT_MASTER
        )
        ORDER BY am.DATE
    ");

    if ($no_provider_query && $no_provider_query->RecordCount() > 0) {
        $no_provider_private = [
            'pre_original' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
            'original' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
            'extension' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
            'renewal' => ['lessons' => 0, 'customers' => [], 'sessions' => []]
        ];
        $no_provider_group = ['lessons' => 0, 'customers' => 0, 'sessions' => []];
        $processed_group_appointments = [];

        while (!$no_provider_query->EOF) {
            $type_id = $no_provider_query->fields['PK_ENROLLMENT_TYPE'];
            $is_group = $no_provider_query->fields['IS_GROUP'];
            $num_sessions = $no_provider_query->fields['NUMBER_OF_SESSION'] ? $no_provider_query->fields['NUMBER_OF_SESSION'] : 1;
            $attendee_count = $no_provider_query->fields['ATTENDEE_COUNT'] ? $no_provider_query->fields['ATTENDEE_COUNT'] : 0;
            $customer_id = $no_provider_query->fields['PK_USER_MASTER'];
            $customer_name = $no_provider_query->fields['CUSTOMER_NAME'] ? $no_provider_query->fields['CUSTOMER_NAME'] : 'Unknown';
            $service_date = $no_provider_query->fields['DATE'];
            $service_name = $no_provider_query->fields['SERVICE_NAME'] ? $no_provider_query->fields['SERVICE_NAME'] : 'Lesson';
            $appointment_id = $no_provider_query->fields['PK_APPOINTMENT_MASTER'];

            if ($is_group == 1) {
                if (!in_array($appointment_id, $processed_group_appointments)) {
                    $no_provider_group['lessons'] += $num_sessions;
                    $processed_group_appointments[] = $appointment_id;
                }
                $no_provider_group['customers'] += $attendee_count;

                $session_key = $appointment_id . '_group';
                if (!isset($no_provider_group['sessions'][$session_key])) {
                    $no_provider_group['sessions'][$session_key] = date('m/d/Y', strtotime($service_date)) . " - " . $service_name . ": " . $num_sessions . " session" . ($num_sessions > 1 ? 's' : '') . " (" . $attendee_count . " attendees)";
                }
            } else {
                $type_key = '';
                switch ($type_id) {
                    case 5:
                        $type_key = 'pre_original';
                        break;
                    case 2:
                        $type_key = 'original';
                        break;
                    case 9:
                        $type_key = 'extension';
                        break;
                    case 13:
                        $type_key = 'renewal';
                        break;
                    default:
                        $no_provider_query->MoveNext();
                        continue 2;
                }

                $no_provider_private[$type_key]['lessons'] += $num_sessions;

                if ($customer_id > 0 && !in_array($customer_id, $no_provider_private[$type_key]['customers'])) {
                    $no_provider_private[$type_key]['customers'][] = $customer_id;
                }

                $session_key = $appointment_id . '_' . $type_key;
                if (!isset($no_provider_private[$type_key]['sessions'][$session_key])) {
                    $no_provider_private[$type_key]['sessions'][$session_key] = date('m/d/Y', strtotime($service_date)) . " - " . $customer_name . " (" . $num_sessions . " lesson" . ($num_sessions > 1 ? 's' : '') . ")";
                }
            }

            $no_provider_query->MoveNext();
        }

        // Convert sessions arrays to simple lists
        foreach ($no_provider_private as $key => $data) {
            $no_provider_private[$key]['sessions'] = array_values($data['sessions']);
        }
        $no_provider_group['sessions'] = array_values($no_provider_group['sessions']);

        // Calculate totals
        $no_provider_total_lessons = 0;
        $no_provider_total_customers = 0;

        $no_provider_private_counts = [];
        foreach ($no_provider_private as $key => $data) {
            $customer_count = count($data['customers']);
            $no_provider_private_counts[$key] = [
                'lessons' => $data['lessons'],
                'customers' => $customer_count,
                'sessions' => $data['sessions']
            ];
            $no_provider_total_lessons += $data['lessons'];
            $no_provider_total_customers += $customer_count;
        }

        $no_provider_total_lessons += $no_provider_group['lessons'];
        $no_provider_total_customers += $no_provider_group['customers'];

        // No Provider header
        $no_provider_start_row = $current_row;

        $objPHPExcel->getActiveSheet()->mergeCells("A$current_row:D$current_row");
        $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "LESSONS WITHOUT SERVICE PROVIDERS");
        $objPHPExcel->getActiveSheet()->getStyle("A$current_row")->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle("A$current_row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle("A$current_row")->getFill()->getStartColor()->setRGB('F8D7DA');
        $objPHPExcel->getActiveSheet()->getStyle("A$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $current_row++;

        // Column headers
        $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "Enrollment Type");
        $objPHPExcel->getActiveSheet()->setCellValue("B$current_row", "Lessons Taught");
        $objPHPExcel->getActiveSheet()->setCellValue("C$current_row", "Customers Served");
        $objPHPExcel->getActiveSheet()->setCellValue("D$current_row", "Session Details");
        $objPHPExcel->getActiveSheet()->getStyle("A$current_row:D$current_row")->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle("A$current_row:D$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $current_row++;

        // Display no provider data
        $no_provider_types = [
            'pre_original' => 'Pre Original',
            'original' => 'Original',
            'extension' => 'Extension',
            'renewal' => 'Renewal'
        ];

        foreach ($no_provider_types as $key => $label) {
            if ($no_provider_private_counts[$key]['lessons'] > 0) {
                $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", $label);
                $objPHPExcel->getActiveSheet()->setCellValue("B$current_row", $no_provider_private_counts[$key]['lessons']);
                $objPHPExcel->getActiveSheet()->setCellValue("C$current_row", $no_provider_private_counts[$key]['customers']);
                $session_text = !empty($no_provider_private_counts[$key]['sessions']) ? implode("\n", array_slice($no_provider_private_counts[$key]['sessions'], 0, 5)) : 'None';
                if (count($no_provider_private_counts[$key]['sessions']) > 5) {
                    $session_text .= "\n... and " . (count($no_provider_private_counts[$key]['sessions']) - 5) . " more";
                }
                $objPHPExcel->getActiveSheet()->setCellValue("D$current_row", $session_text);
                $objPHPExcel->getActiveSheet()->getStyle("D$current_row")->getAlignment()->setWrapText(true);
                $objPHPExcel->getActiveSheet()->getStyle("A$current_row:C$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $current_row++;
            }
        }

        // No Provider Group
        if ($no_provider_group['lessons'] > 0) {
            $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "Group Lessons");
            $objPHPExcel->getActiveSheet()->setCellValue("B$current_row", $no_provider_group['lessons']);
            $objPHPExcel->getActiveSheet()->setCellValue("C$current_row", $no_provider_group['customers']);
            $session_text = !empty($no_provider_group['sessions']) ? implode("\n", array_slice($no_provider_group['sessions'], 0, 5)) : 'None';
            if (count($no_provider_group['sessions']) > 5) {
                $session_text .= "\n... and " . (count($no_provider_group['sessions']) - 5) . " more";
            }
            $objPHPExcel->getActiveSheet()->setCellValue("D$current_row", $session_text);
            $objPHPExcel->getActiveSheet()->getStyle("A$current_row:C$current_row")->applyFromArray($groupStyle);
            $objPHPExcel->getActiveSheet()->getStyle("D$current_row")->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle("A$current_row:C$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $current_row++;
        }

        // No Provider Total
        $objPHPExcel->getActiveSheet()->setCellValue("A$current_row", "NO PROVIDER TOTAL");
        $objPHPExcel->getActiveSheet()->setCellValue("B$current_row", $no_provider_total_lessons);
        $objPHPExcel->getActiveSheet()->setCellValue("C$current_row", $no_provider_total_customers);
        $objPHPExcel->getActiveSheet()->setCellValue("D$current_row", "Total Lessons: " . $no_provider_total_lessons . " | Total Customers: " . $no_provider_total_customers);
        $objPHPExcel->getActiveSheet()->getStyle("A$current_row:D$current_row")->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle("A$current_row:D$current_row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle("A$current_row:D$current_row")->getFill()->getStartColor()->setRGB('F8D7DA');
        $objPHPExcel->getActiveSheet()->getStyle("A$current_row:C$current_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $current_row += 2;

        // Apply borders to no provider table
        $objPHPExcel->getActiveSheet()->getStyle("A$no_provider_start_row:D" . ($current_row - 1))->applyFromArray($borderStyle);
    }
}

$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
header("location:" . $outputFileName);
