<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$userType = "Customers";
$user_role_condition = " AND PK_ROLES = 4";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
$LOCATION_ARRAY = explode(',', $DEFAULT_LOCATION_ID);

$selected_date = !empty($_POST['CHOOSE_DATE']) ? date('Y-m-d', strtotime($_POST['CHOOSE_DATE'])) : date('Y-m-d');
$selected_service_providers = isset($_POST['SERVICE_PROVIDER_ID']) ? $_POST['SERVICE_PROVIDER_ID'] : [];
$selected_status = isset($_POST['STATUS_CODE']) ? $_POST['STATUS_CODE'] : '';
$selected_appointment_type = isset($_POST['APPOINTMENT_TYPE']) ? $_POST['APPOINTMENT_TYPE'] : '';

// Build WHERE conditions based on filters
$where_conditions = [];

// Date filter
$where_conditions[] = "DOA_APPOINTMENT_MASTER.DATE = '" . $selected_date . "'";

// Service Provider filter
if (!empty($selected_service_providers) && is_array($selected_service_providers)) {
    $service_provider_ids = implode(',', array_map('intval', $selected_service_providers));
    $where_conditions[] = "SERVICE_PROVIDER.PK_USER IN ($service_provider_ids)";
}

// Status filter
if (!empty($selected_status)) {
    $where_conditions[] = "DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS = '$selected_status'";
} else {
    // Default to showing active statuses if none selected
    $where_conditions[] = "DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS > 0";
}

// Appointment Type filter
if (!empty($selected_appointment_type)) {
    $where_conditions[] = "DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = '$selected_appointment_type'";
}

// Location filter (always applies)
$where_conditions[] = "DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID)";
$where_conditions[] = "(CUSTOMER.IS_DELETED = 0 OR CUSTOMER.IS_DELETED IS null)";

// Combine all WHERE conditions
$where_clause = "WHERE " . implode(" AND ", $where_conditions);

$query = "SELECT
            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
            DOA_APPOINTMENT_MASTER.STANDING_ID,
            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
            DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE,
            DOA_APPOINTMENT_MASTER.GROUP_NAME,
            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
            DOA_APPOINTMENT_MASTER.DATE AS DATE,
            DATE(DATE) AS APPOINTMENT_DATE,
            DAYNAME(DATE) AS DAY_NAME,      
            DAY(DATE) AS DAY_NUMBER,        
            DOA_APPOINTMENT_MASTER.START_TIME,
            DOA_APPOINTMENT_MASTER.END_TIME,
            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
            DOA_APPOINTMENT_MASTER.IS_PAID,
            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
            DOA_SERVICE_MASTER.SERVICE_NAME,
            DOA_SERVICE_CODE.SERVICE_CODE,
            DOA_APPOINTMENT_MASTER.IS_PAID,
            DOA_APPOINTMENT_MASTER.IS_CHARGED,
            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
            DOA_APPOINTMENT_STATUS.STATUS_CODE,
            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
            DOA_SCHEDULING_CODE.COLOR_CODE,
            GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ', ') AS SERVICE_PROVIDER_NAME,
            GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME
        FROM
            DOA_APPOINTMENT_MASTER
        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
        
        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
        LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                
        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
        $where_clause
        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
        ORDER BY DOA_APPOINTMENT_MASTER.START_TIME ASC";


$appointments = $db_account->Execute($query);

$current_date = '';
// First, count appointments per date
$temp_appointments = $db_account->Execute($query);
$date_counts = [];
while (!$temp_appointments->EOF) {
    $appointment_date = $temp_appointments->fields['DATE'];
    if (!isset($date_counts[$appointment_date])) {
        $date_counts[$appointment_date] = 0;
    }
    $date_counts[$appointment_date]++;
    $temp_appointments->MoveNext();
}

// Reset the result set

while (!$appointments->EOF) {
    $appointment_date = $appointments->fields['DATE'];
    $day_name = $appointments->fields['DAY_NAME'];
    $day_number = $appointments->fields['DAY_NUMBER'];
    $appointment_id = $appointments->fields['PK_APPOINTMENT_MASTER'];
    $title = $appointments->fields['ENROLLMENT_NAME'] ? $appointments->fields['ENROLLMENT_NAME'] : $appointments->fields['SERVICE_NAME'];
    $start_time = date('h:i A', strtotime($appointments->fields['START_TIME']));
    $end_time = date('h:i A', strtotime($appointments->fields['END_TIME']));
    $status = $appointments->fields['APPOINTMENT_STATUS'];
    $status_color = $appointments->fields['APPOINTMENT_COLOR'];
    $service_provider = ($appointments->fields['SERVICE_PROVIDER_NAME'] == null) ? '' : $appointments->fields['SERVICE_PROVIDER_NAME'];
    $CUSTOMER_NAME = ($appointments->fields['CUSTOMER_NAME'] == null) ? '' : $appointments->fields['CUSTOMER_NAME'];
    $service_name = $appointments->fields['SERVICE_NAME'];
    $service_code = $appointments->fields['SERVICE_CODE'];
    $color_code = $appointments->fields['COLOR_CODE'];
    $pk_appointment_status = $appointments->fields['PK_APPOINTMENT_STATUS'];

    $appointment_type = $appointments->fields['APPOINTMENT_TYPE'];
    // Title display
    $title_display = '';
    if ($appointment_type == 'NORMAL') {
        $title_display = 'Private Session';
        $TYPE = 'appointment';
    } elseif ($appointment_type == 'AD-HOC') {
        $title_display = 'Ad-Hoc';
        $TYPE = 'appointment';
    } elseif ($appointment_type == 'GROUP') {
        $title_display = 'Group Class';
        $TYPE = 'group_class';
    } elseif ($appointment_type == 'DEMO') {
        $title_display = 'Record Only';
        $TYPE = 'appointment';
    } else {
        $title_display = $appointments->fields['ENROLLMENT_NAME'] ?: $service_name;
    }

    $customer = getProfileBadge($CUSTOMER_NAME);
    $customer_initial = $customer['initials'];
    $customer_color = $customer['color'];

    $profile = getProfileBadge($service_provider);
    $profile_initial = $profile['initials'];
    $profile_color = $profile['color'];
?>
    <tr style="height: 55px;">
        <?php if ($current_date != $appointment_date): ?>
            <?php
            $current_date = $appointment_date;
            $rowspan_count = isset($date_counts[$appointment_date]) ? $date_counts[$appointment_date] : 1;
            ?>
            <td class="sticky-col date-col" rowspan="<?= $rowspan_count; ?>" style="vertical-align: top;">
                <div class="date-box">
                    <small><?= substr($day_name, 0, 3); ?></small>
                    <strong><?= $day_number; ?></strong>
                </div>
            </td>
        <?php endif; ?>

        <td style="vertical-align: middle;">
            <span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span>
            <?= $CUSTOMER_NAME ?>
        </td>
        <td style="vertical-align: middle;"><?= $start_time . ' – ' . $end_time; ?></td>
        <td style="vertical-align: middle;">
            <span class="status not-started" style="background-color: <?= $status_color ?>20 !important; color: <?= $status_color ?> !important;">
                <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" viewBox="0 0 512 512" width="12px" height="12px" fill="<?= $status_color; ?>">
                    <path d="m256 2c-140.1 0-254 113.9-254 254s113.9 254 254 254 254-113.9 254-254-113.9-254-254-254zm0 457.2c-112 0-203.2-91.2-203.2-203.2s91.2-203.2 203.2-203.2 203.2 91.2 203.2 203.2-91.2 203.2-203.2 203.2z"></path>
                    <path d="m256 129c-70 0-127 57-127 127s57 127 127 127 127-57 127-127-57-127-127-127z"></path>
                </svg>
                <?= $status ?>
            </span>
        </td>
        <td style="vertical-align: middle;">
            <span class="avatarname" style="color: #fff; background-color: <?= $profile_color ?>;"><?= $profile_initial; ?></span>
            <?= $service_provider ?>
        </td>
        <td style="vertical-align: middle;">
            <?= $service_name ?>&nbsp;&nbsp;<span class="badge ms-auto" style="font-size: 12px !important; background-color: <?= $color_code ?>20 !important; color: <?= $color_code ?> !important;"><?= $service_code ?></span>
        </td>
        <td class="text-center" style="vertical-align: middle;">
            <button type="button" class="bg-transparent p-0 border-0" onclick="loadViewAppointmentModal(<?= $appointment_id; ?>, <?= $TYPE ?>)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1rem" height="1rem" fill="CurrentColor">
                    <circle cx="256" cy="256" r="48" />
                    <circle cx="256" cy="416" r="48" />
                    <circle cx="256" cy="96" r="48" />
                </svg>
            </button>
        </td>
    </tr>
<?php
    $appointments->MoveNext();
}

if ($appointments->RecordCount() == 0): ?>
    <tr>
        <td colspan="7" class="text-center py-4">
            <div class="text-muted">No appointments found for the selected date/filters.</div>
        </td>
    </tr>
<?php endif; ?>