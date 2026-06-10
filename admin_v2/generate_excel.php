<?php
require_once('../global/config.php');

// Get filter data from POST
$filter_data = isset($_POST['filter_data']) ? json_decode($_POST['filter_data'], true) : [];

// Build WHERE conditions for export
$where_conditions = ["DOA_USER_ROLES.PK_ROLES = 4"];
$where_conditions = ["DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")"];

// Apply same filters as main page
if (isset($filter_data['status_filter']) && $filter_data['status_filter'] !== '' && $filter_data['status_filter'] !== 'all') {
    $where_conditions[] = "DOA_USERS.ACTIVE = " . intval($filter_data['status_filter']);
}

if (isset($filter_data['tag_filter']) && $filter_data['tag_filter'] !== '' && $filter_data['tag_filter'] !== 'all') {
    $where_conditions[] = "EXISTS (SELECT 1 FROM $account_database.DOA_USER_TAG AS DOA_USER_TAG WHERE DOA_USER_TAG.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER AND DOA_USER_TAG.PK_TAG = " . intval($filter_data['tag_filter']) . ")";
}

if (isset($filter_data['date_filter'])) {
    $date_filter = $filter_data['date_filter'];
    $start_date = isset($filter_data['start_date']) ? $filter_data['start_date'] : '';
    $end_date = isset($filter_data['end_date']) ? $filter_data['end_date'] : '';

    if ($date_filter == 'range' && $start_date && $end_date) {
        $where_conditions[] = "DATE(DOA_USERS.CREATED_ON) BETWEEN '" . mysqli_real_escape_string($db->LinkID, $start_date) . "' AND '" . mysqli_real_escape_string($db->LinkID, $end_date) . "'";
    } elseif ($date_filter == 'today') {
        $where_conditions[] = "DATE(DOA_USERS.CREATED_ON) = CURDATE()";
    } elseif ($date_filter == 'week') {
        $where_conditions[] = "YEARWEEK(DOA_USERS.CREATED_ON) = YEARWEEK(CURDATE())";
    } elseif ($date_filter == 'month') {
        $where_conditions[] = "MONTH(DOA_USERS.CREATED_ON) = MONTH(CURDATE()) AND YEAR(DOA_USERS.CREATED_ON) = YEAR(CURDATE())";
    } elseif ($date_filter == 'year') {
        $where_conditions[] = "YEAR(DOA_USERS.CREATED_ON) = YEAR(CURDATE())";
    }
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

$query = "SELECT DOA_USERS.LAST_NAME, DOA_USERS.FIRST_NAME, CONCAT(DOA_CUSTOMER_DETAILS.PARTNER_FIRST_NAME, ' ', DOA_CUSTOMER_DETAILS.PARTNER_LAST_NAME) AS PARTNER_NAME, DOA_USERS.ADDRESS, DOA_USERS.CITY, DOA_STATES.STATE_NAME, DOA_USERS.ZIP, DOA_CUSTOMER_DETAILS.EMAIL, DOA_USERS.ACTIVE, DOA_USERS.CREATED_ON FROM DOA_USERS  INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER  INNER JOIN $account_database.DOA_CUSTOMER_DETAILS AS DOA_CUSTOMER_DETAILS ON DOA_CUSTOMER_DETAILS.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN DOA_STATES ON DOA_USERS.PK_STATES=DOA_STATES.PK_STATES $where_clause";

$result = $db->Execute($query);

// Export to Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="student_mailing_list_export.xls"');

echo "Last Name\tFirst Name\tPartner Name\tAddress\tCity\tState\tZip\tEmail Address\tStatus\n";

while (!$result->EOF) {
    $status = ($result->fields['ACTIVE'] == 1) ? "Active" : "Inactive";

    echo $result->fields['LAST_NAME'] . "\t";
    echo $result->fields['FIRST_NAME'] . "\t";
    echo $result->fields['PARTNER_NAME'] . "\t";
    echo $result->fields['ADDRESS'] . "\t";
    echo $result->fields['CITY'] . "\t";
    echo $result->fields['STATE_NAME'] . "\t";
    echo $result->fields['ZIP'] . "\t";
    echo $result->fields['EMAIL'] . "\t";
    echo $status . "\n";

    $result->MoveNext();
}
