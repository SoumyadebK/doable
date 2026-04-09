<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$STANDING_ID = $_GET['STANDING_ID'];
$PK_APPOINTMENT_MASTER = $_GET['PK_APPOINTMENT_MASTER'];

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.STANDING_ID,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
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
                        WHERE DOA_APPOINTMENT_MASTER.STANDING_ID = " . $STANDING_ID . "
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE ASC, DOA_APPOINTMENT_MASTER.START_TIME ASC";

$i = 1;
$appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY);
$current_date = '';

// First, count appointments per date for rowspan
$temp_appointments = $db_account->Execute($ALL_APPOINTMENT_QUERY);
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
$appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY);

while (!$appointment_data->EOF) {
    $appointment_date = $appointment_data->fields['DATE'];
    $day_name = date('l', strtotime($appointment_date));
    $day_number = date('j', strtotime($appointment_date));
    $start_time = date('h:i A', strtotime($appointment_data->fields['START_TIME']));
    $end_time = date('h:i A', strtotime($appointment_data->fields['END_TIME']));
    $status = $appointment_data->fields['APPOINTMENT_STATUS'];
    $status_color = $appointment_data->fields['APPOINTMENT_COLOR'];
    $service_provider = ($appointment_data->fields['SERVICE_PROVIDER_NAME'] == null) ? '' : $appointment_data->fields['SERVICE_PROVIDER_NAME'];
    $CUSTOMER_NAME = ($appointment_data->fields['CUSTOMER_NAME'] == null) ? '' : $appointment_data->fields['CUSTOMER_NAME'];
    $service_name = $appointment_data->fields['SERVICE_NAME'];
    $service_code = $appointment_data->fields['SERVICE_CODE'];
    $color_code = $appointment_data->fields['COLOR_CODE'];
    $pk_appointment_status = $appointment_data->fields['PK_APPOINTMENT_STATUS'];
    $appointment_type = $appointment_data->fields['APPOINTMENT_TYPE'];

    // Get profile badge for customer
    $customer = getProfileBadge($CUSTOMER_NAME);
    $customer_initial = $customer['initials'];
    $customer_color = $customer['color'];

    // Get profile badge for service provider
    $profile = getProfileBadge($service_provider);
    $profile_initial = $profile['initials'];
    $profile_color = $profile['color'];
?>
    <tr class="added_standing standing-appointment-row">
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

        <!-- Checkbox Column -->
        <td>
            <a href="javascript:" onclick="ConfirmDelete(<?= $appointment_data->fields['PK_APPOINTMENT_MASTER'] ?>, 'normal');">
                <i class="fa fa-trash"></i>
            </a>
        </td>

        <!-- Customer Name Column -->
        <td style="vertical-align: middle;">
            <span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span>
            <?= $CUSTOMER_NAME ?>
        </td>

        <!-- Time Column -->
        <td style="vertical-align: middle;"><?= $start_time . ' – ' . $end_time; ?></td>

        <!-- Status Column -->
        <td style="vertical-align: middle;">
            <span class="status not-started" style="background-color: <?= $status_color ?>20 !important; color: <?= $status_color ?> !important;">
                <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" viewBox="0 0 512 512" width="12px" height="12px" fill="<?= $status_color; ?>">
                    <path d="m256 2c-140.1 0-254 113.9-254 254s113.9 254 254 254 254-113.9 254-254-113.9-254-254-254zm0 457.2c-112 0-203.2-91.2-203.2-203.2s91.2-203.2 203.2-203.2 203.2 91.2 203.2 203.2-91.2 203.2-203.2 203.2z"></path>
                    <path d="m256 129c-70 0-127 57-127 127s57 127 127 127 127-57 127-127-57-127-127-127z"></path>
                </svg>
                <?= $status ?>
            </span>
            <?php
            if ($appointment_data->fields['CUSTOMER_NAME']) {
                if ($pk_appointment_status == 2) { ?>
                    <i class="fa fa-check-circle" style="font-size:20px;color:#35e235; margin-left: 8px;"></i>
                <?php } else { ?>
                    <a href="javascript:" data-id="<?= $appointment_data->fields['PK_APPOINTMENT_MASTER'] ?>" onclick='confirmComplete($(this));'>
                        <i class="fa fa-check-circle" style="font-size:20px;color:#a9b7a9; margin-left: 8px;"></i>
                    </a>
            <?php }
            } ?>
        </td>

        <!-- Service Provider Column -->
        <td style="vertical-align: middle;">
            <span class="avatarname" style="color: #fff; background-color: <?= $profile_color ?>;"><?= $profile_initial; ?></span>
            <?= $service_provider ?>
        </td>

        <!-- Description Column -->
        <td style="vertical-align: middle;">
            <?php if (!empty($appointment_data->fields['ENROLLMENT_ID']) || !empty($appointment_data->fields['ENROLLMENT_NAME'])) { ?>
                <?= (($appointment_data->fields['ENROLLMENT_NAME']) ? $appointment_data->fields['ENROLLMENT_NAME'] . ' - ' : '') . $appointment_data->fields['ENROLLMENT_ID'] . " || " . $appointment_data->fields['SERVICE_NAME'] ?>
            <?php } elseif (empty($appointment_data->fields['SERVICE_NAME']) && empty($appointment_data->fields['SERVICE_CODE'])) { ?>
                <?= $appointment_data->fields['SERVICE_NAME'] ?>
            <?php } else { ?>
                <?= $appointment_data->fields['SERVICE_NAME'] ?>
            <?php } ?>
            <?php if ($appointment_data->fields['SERVICE_CODE']): ?>
                <span class="badge ms-auto" style="font-size: 12px !important; background-color: <?= $color_code ?>20 !important; color: <?= $color_code ?> !important; margin-left: 8px;">
                    <?= $service_code ?>
                </span>
            <?php endif; ?>
        </td>

        <!-- Actions Column (3 dots menu) -->
        <td class="text-center" style="vertical-align: middle;">
            <!-- <button type="button" class="bg-transparent p-0 border-0" onclick="loadViewAppointmentModal(<?= $appointment_data->fields['PK_APPOINTMENT_MASTER'] ?>, 'appointment')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1rem" height="1rem" fill="CurrentColor">
                    <circle cx="256" cy="256" r="48" />
                    <circle cx="256" cy="416" r="48" />
                    <circle cx="256" cy="96" r="48" />
                </svg>
            </button> -->
        </td>
    </tr>
<?php
    $appointment_data->MoveNext();
    $i++;
}

if ($appointment_data->RecordCount() == 0): ?>
    <tr class="added_standing">
        <td colspan="8" class="text-center py-4">
            <div class="text-muted">No appointments found for this standing group.</div>
        </td>
    </tr>
<?php endif; ?>