<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

// Check if user is logged in and has permission
if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Get parameters
$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$service_provider_id = isset($_GET['service_provider_id']) ? $_GET['service_provider_id'] : '';
$service_master_id = isset($_GET['PK_SERVICE_MASTER']) ? $_GET['PK_SERVICE_MASTER'] : '';

// Get location name
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$concatenatedResults = implode(", ", $resultsArray);

// Get selected services information
$selected_services = [];
if (!empty($service_master_id) && $service_master_id != '0') {
    $service_ids_array = explode(',', $service_master_id);
    if (!empty($service_ids_array)) {
        $sanitized_ids = array_map('intval', $service_ids_array);
        $ids_string = implode(',', $sanitized_ids);
        $service_info_query = $db_account->Execute("SELECT PK_SERVICE_MASTER, SERVICE_NAME FROM DOA_SERVICE_MASTER WHERE PK_SERVICE_MASTER IN ($ids_string) ORDER BY SERVICE_NAME");
        if ($service_info_query && $service_info_query->RecordCount() > 0) {
            while (!$service_info_query->EOF) {
                $selected_services[$service_info_query->fields['PK_SERVICE_MASTER']] = $service_info_query->fields['SERVICE_NAME'];
                $service_info_query->MoveNext();
            }
        }
    }
}

// Get service providers
$provider_filter = "";
if (!empty($service_provider_id) && $service_provider_id != '0') {
    $provider_ids_array = explode(',', $service_provider_id);
    $sanitized_provider_ids = array_map('intval', $provider_ids_array);
    $provider_ids_string = implode(',', $sanitized_provider_ids);
    $provider_filter = " AND DU.PK_USER IN ($provider_ids_string)";
}

$providers_query = $db->Execute("
    SELECT DISTINCT DU.PK_USER, CONCAT(DU.FIRST_NAME, ' ', DU.LAST_NAME) AS PROVIDER_NAME
    FROM DOA_USERS DU
    INNER JOIN DOA_USER_ROLES DUR ON DU.PK_USER = DUR.PK_USER
    WHERE DU.ACTIVE = 1 
    AND DUR.PK_ROLES = 5
    AND DU.PK_ACCOUNT_MASTER = '" . $_SESSION['PK_ACCOUNT_MASTER'] . "'
    $provider_filter
    ORDER BY DU.FIRST_NAME, DU.LAST_NAME
");

// Collect all data
$all_providers_data = [];
$grand_totals = [];

// Initialize grand totals for selected services
foreach ($selected_services as $service_id => $service_name) {
    $service_key = 'service_' . $service_id;
    $grand_totals[$service_key] = [
        'service_name' => $service_name,
        'lessons' => 0,
        'customers' => 0
    ];
}
$grand_totals['total_lessons'] = 0;
$grand_totals['total_customers'] = 0;

if ($providers_query && $providers_query->RecordCount() > 0) {
    while (!$providers_query->EOF) {
        $provider_id = $providers_query->fields['PK_USER'];
        $provider_name = $providers_query->fields['PROVIDER_NAME'];

        $provider_data = [
            'name' => $provider_name,
            'services' => [],
            'total_lessons' => 0,
            'total_customers' => 0
        ];

        foreach ($selected_services as $service_id => $service_name) {
            $provider_data['services'][$service_id] = [
                'service_name' => $service_name,
                'lessons' => 0,
                'customers' => [],
                'sessions' => []
            ];
        }

        foreach ($selected_services as $service_id => $service_name) {
            $sql = "SELECT
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
                    LEFT JOIN DOA_MASTER.DOA_USER_MASTER dc ON ac.PK_USER_MASTER = dc.PK_USER_MASTER
                    LEFT JOIN DOA_MASTER.DOA_USERS us ON us.PK_USER = dc.PK_USER
                    LEFT JOIN DOA_SERVICE_MASTER sm ON sm.PK_SERVICE_MASTER = am.PK_SERVICE_MASTER
                    LEFT JOIN DOA_CUSTOMER_DETAILS cd ON ac.PK_USER_MASTER = cd.PK_USER_MASTER
                    WHERE DATE(am.DATE) BETWEEN '$from_date' AND '$to_date'
                    AND am.PK_APPOINTMENT_STATUS = 2
                    AND asp.PK_USER = $provider_id
                    AND am.PK_SERVICE_MASTER = $service_id
                    ORDER BY am.DATE";

            $lessons_query = $db_account->Execute($sql);
            $lesson_count = 0;
            $unique_customers = [];
            $session_details = [];
            $processed_appointments = [];

            if ($lessons_query && $lessons_query->RecordCount() > 0) {
                while (!$lessons_query->EOF) {
                    $appointment_id = $lessons_query->fields['PK_APPOINTMENT_MASTER'];
                    $num_sessions = $lessons_query->fields['NUMBER_OF_SESSION'] ? $lessons_query->fields['NUMBER_OF_SESSION'] : 1;
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
            }

            $provider_data['services'][$service_id]['lessons'] = $lesson_count;
            $provider_data['services'][$service_id]['customers'] = $unique_customers;
            $provider_data['services'][$service_id]['sessions'] = implode("\n", $session_details);

            $provider_data['total_lessons'] += $lesson_count;
            $provider_data['total_customers'] += count($unique_customers);

            $service_key = 'service_' . $service_id;
            $grand_totals[$service_key]['lessons'] += $lesson_count;
            $grand_totals[$service_key]['customers'] += count($unique_customers);
            $grand_totals['total_lessons'] += $lesson_count;
            $grand_totals['total_customers'] += count($unique_customers);
        }

        if ($provider_data['total_lessons'] > 0) {
            $all_providers_data[] = $provider_data;
        }
        $providers_query->MoveNext();
    }
}

// Create Excel file
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"services_of_lessons_taught_report_" . date('Y-m-d') . ".xls\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<title>Services of Lessons Taught Report</title>';
echo '<style>';
echo 'th { background-color: #4CAF50; color: white; border: 1px solid #ddd; padding: 8px; text-align: center; }';
echo 'td { border: 1px solid #ddd; padding: 8px; }';
echo 'table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }';
echo '.summary-table th { background-color: #d4edda; color: #000; }';
echo '.provider-header { background-color: #e9ecef; font-weight: bold; }';
echo '.provider-total { background-color: #f8f9fa; font-weight: bold; }';
echo '.grand-total { background-color: #c8e6c9; font-weight: bold; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Title and header
echo '<h2 style="text-align: center;">SERVICES OF LESSONS TAUGHT BY SERVICE PROVIDER REPORT</h2>';
echo '<h4 style="text-align: center;">' . $concatenatedResults . '</h4>';
echo '<h5 style="text-align: center;">(' . date('m/d/Y', strtotime($from_date)) . ' - ' . date('m/d/Y', strtotime($to_date)) . ')</h5>';
echo '<br><br>';

// Summary Totals Table
echo '<h3>SUMMARY TOTALS</h3>';
echo '<table class="summary-table">';
echo '<thead>';
echo '<tr>';
echo '<th style="background-color: #d4edda;">Service Type</th>';
foreach ($selected_services as $service_id => $service_name) {
    echo '<th style="background-color: #d4edda;" colspan="2">' . htmlspecialchars($service_name) . '</th>';
}
echo '</tr>';
echo '<tr>';
echo '<th style="background-color: #d4edda;">&nbsp;</th>';
foreach ($selected_services as $service_id => $service_name) {
    echo '<th style="background-color: #d4edda;">Lessons</th>';
    echo '<th style="background-color: #d4edda;">Customers</th>';
}
echo '</tr>';
echo '</thead>';
echo '<tbody>';
echo '<tr>';
echo '<td style="font-weight: bold;">Total</td>';
foreach ($selected_services as $service_id => $service_name) {
    $service_key = 'service_' . $service_id;
    echo '<td style="text-align: center;">' . $grand_totals[$service_key]['lessons'] . '</td>';
    echo '<td style="text-align: center;">' . $grand_totals[$service_key]['customers'] . '</td>';
}
echo '</tr>';
echo '<tr class="grand-total">';
echo '<td style="font-weight: bold;">GRAND TOTAL</td>';
echo '<td colspan="' . (count($selected_services) * 2) . '" style="text-align: center;">';
echo 'Total Lessons: ' . $grand_totals['total_lessons'] . ' | Total Customers: ' . $grand_totals['total_customers'];
echo '</td>';
echo '</tr>';
echo '</tbody>';
echo '</table>';
echo '<br><br>';

// Provider tables
foreach ($all_providers_data as $provider_data) {
    echo '<h3>' . htmlspecialchars($provider_data['name']) . '</h3>';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width: 20%;">Service Type</th>';
    echo '<th style="width: 15%;">Lessons Taught</th>';
    echo '<th style="width: 15%;">Customers Served</th>';
    echo '<th style="width: 50%;">Session Details</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($selected_services as $service_id => $service_name) {
        echo '<tr>';
        echo '<td style="text-align: center;">' . htmlspecialchars($service_name) . '</td>';
        echo '<td style="text-align: center;">' . $provider_data['services'][$service_id]['lessons'] . '</td>';
        echo '<td style="text-align: center;">' . count($provider_data['services'][$service_id]['customers']) . '</td>';
        echo '<td style="text-align: left; white-space: pre-wrap;">' . nl2br(htmlspecialchars($provider_data['services'][$service_id]['sessions'])) . '</td>';
        echo '</tr>';
    }

    echo '<tr class="provider-total">';
    echo '<td style="font-weight: bold; text-align: center;">PROVIDER TOTAL</td>';
    echo '<td style="font-weight: bold; text-align: center;">' . $provider_data['total_lessons'] . '</td>';
    echo '<td style="font-weight: bold; text-align: center;">' . $provider_data['total_customers'] . '</td>';
    echo '<td style="text-align: center;">Total Lessons: ' . $provider_data['total_lessons'] . ' | Total Customers: ' . $provider_data['total_customers'] . '</td>';
    echo '</tr>';

    echo '</tbody>';
    echo '</table>';
    echo '<br><br>';
}

echo '</body>';
echo '</html>';
