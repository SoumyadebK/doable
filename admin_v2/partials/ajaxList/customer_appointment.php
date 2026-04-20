<?php
require_once('../../../global/config.php');
global $db;
global $db_account;

$PK_USER_MASTER = !empty($_GET['master_id']) ? $_GET['master_id'] : 0;


// Fetch appointments for this user
$appointments = $db_account->Execute("SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_SERVICE AS APT_ENR_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.COMMENT,
                            DOA_APPOINTMENT_MASTER.INTERNAL_COMMENT,
                            DOA_APPOINTMENT_MASTER.IMAGE,
                            DOA_APPOINTMENT_MASTER.VIDEO,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            APT_ENR.ENROLLMENT_NAME AS APT_ENR_NAME,
                            APT_ENR.ENROLLMENT_ID AS APT_ENR_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.IS_CHARGED,
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_SCHEDULING_CODE.SCHEDULING_CODE,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            DOA_SCHEDULING_CODE.UNIT,
                            SERVICE_PROVIDER.FIRST_NAME,
                            SERVICE_PROVIDER.LAST_NAME,
                            GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ', ') AS SERVICE_PROVIDER_NAME,
                            GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME,
                            DOA_LOCATION.LOCATION_NAME
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                
                        LEFT JOIN DOA_APPOINTMENT_ENROLLMENT ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_ENROLLMENT.PK_APPOINTMENT_MASTER AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP'
                        LEFT JOIN DOA_ENROLLMENT_MASTER AS APT_ENR ON DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_MASTER = APT_ENR.PK_ENROLLMENT_MASTER AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP'
                                
                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL'
                                
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_APPOINTMENT_MASTER.PK_LOCATION = DOA_LOCATION.PK_LOCATION
                        WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A'
                        AND DOA_USER_MASTER.PK_USER_MASTER = $PK_USER_MASTER
                        AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC', 'GROUP')
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC");

$has_appointments = $appointments->RecordCount() > 0;
?>

<?php if ($has_appointments): ?>
    <?php while (!$appointments->EOF):
        $appointment = $appointments->fields;
        $appointment_id = $appointment['PK_APPOINTMENT_MASTER'];
        $service_name = $appointment['SERVICE_NAME'] ?: 'Private Lesson';
        $service_code = $appointment['SERVICE_CODE'];
        $scheduling_code = $appointment['SCHEDULING_CODE'];
        $instructor_name = $appointment['FIRST_NAME'] . ' ' . $appointment['LAST_NAME'];
        $location_name = $appointment['LOCATION_NAME'] ?: 'Studio Location';
        $appointment_date = $appointment['DATE'];
        $start_time = $appointment['START_TIME'];
        $end_time = $appointment['END_TIME'];
        $status = $appointment['APPOINTMENT_STATUS'];
        $session_type = $appointment['SCHEDULING_CODE']; // Assuming this field exists
        $appointment_color = $appointment['APPOINTMENT_COLOR'] ?: '#000000';

        // Format date and time
        $formatted_date = date('l F j', strtotime($appointment_date));
        $time_range = date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time));

        // Determine badge color based on session type
        $badge_class = '';
        $badge_text = '';
        if ($session_type == 'GROUP' || $service_code == 'GRP') {
            $badge_class = 'badge border';
            $badge_style = 'background-color: #ffeaf4; color: #f573b6;';
            $badge_text = 'GRP';
        } else {
            $badge_class = 'badge border';
            $badge_style = 'background-color: #eeebff; color: #8c75e7;';
            $badge_text = $appointment['SCHEDULING_CODE'];
        }
    ?>
        <div class="form-check border rounded-2 p-2 mb-2">
            <div class="d-flex">
                <span class="checkicon d-inline-flex me-2 align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 511.999 511.999" style="enable-background:new 0 0 511.999 511.999;" xml:space="preserve" width="12px" height="12px" fill="#1FC16B">
                        <!-- Green check icon SVG -->
                        <path d="M511.923,416.776l-7.748-62.787c-0.002-0.013-0.003-0.026-0.005-0.04c-2.064-16.192-12.655-30.269-27.642-36.737 l-58.468-25.236c-0.061-0.026-0.124-0.044-0.185-0.07c-0.054-0.022-0.104-0.05-0.159-0.072l-4.873-1.892v-11.023 c13.391-10.093,22.29-26.088,24.403-44.179c3.314-2.211,5.64-5.854,6.028-10.139l2.18-24.998c0.335-3.628-0.769-7.165-3.107-9.958 c-0.716-0.855-1.521-1.609-2.397-2.252l0.028-0.509c0.01-0.184,0.015-0.368,0.015-0.551c0-32.516-28.132-58.97-62.711-58.97 c-20.668,0-39.026,9.456-50.457,24.005l0.016-0.3c0.01-0.183,0.015-0.367,0.015-0.551c0-36.69-31.788-66.54-70.859-66.54 c-39.07,0-70.856,29.85-70.856,66.54c0,0.185,0.005,0.37,0.015,0.554l0.016,0.298c-11.431-14.549-29.791-24.006-50.46-24.006 c-34.578,0-62.708,26.454-62.708,58.97c0,0.185,0.005,0.37,0.016,0.555l0.027,0.495c-0.876,0.642-1.681,1.393-2.397,2.246 c-2.34,2.787-3.451,6.318-3.129,9.928l2.191,25.046c0.317,3.631,2.03,6.918,4.825,9.255c0.393,0.329,0.8,0.633,1.221,0.912 c2.13,18.083,11.027,34.065,24.398,44.15v11.022l-4.874,1.893c-0.053,0.021-0.102,0.048-0.154,0.069 c-0.063,0.026-0.127,0.044-0.189,0.071l-58.463,25.236c-14.984,6.467-25.575,20.542-27.645,36.77l-7.756,62.791 c-0.351,2.844,0.535,5.702,2.432,7.849C4.403,426.77,7.131,428,9.996,428l101.71,0.022c0,0,0.002,0,0.003,0h288.58 c0.001,0,0.002,0,0.002,0L502.001,428c2.865,0,5.592-1.23,7.49-3.377C511.388,422.477,512.274,419.619,511.923,416.776z" />
                    </svg>
                </span>
                <label class="form-check-label"><?php echo htmlspecialchars($service_name); ?></label>

                <?php if ($badge_text): ?>
                    <span class="badge <?php echo $badge_class; ?> ms-auto" style="<?php echo $badge_style; ?>"><?php echo $badge_text; ?></span>
                <?php endif; ?>
                <span class="badge bg-light fw-normal theme-text-light ms-1" style="background-color: <?php echo $appointment_color; ?>20 !important; color: <?php echo $nextLessonColor; ?> !important;">
                    <?php echo htmlspecialchars($status); ?>
                </span>

                <!-- You might want to add session count here if available -->
                <!-- <span class="badge bg-light fw-normal theme-text-light ms-1">1 of 4</span> -->
            </div>
            <div class="statusareatext f12 theme-text-light" style="margin-left: 27px;">
                <span class="text-uppercase"><?php echo $formatted_date . ', ' . $time_range . ' (PST)'; ?></span>
                <ul class="list-inline mb-0 mt-1">
                    <?php if ($instructor_name): ?>
                        <li class="list-inline-item fw-semibold">
                            <?php
                            // Get initials for badge
                            $initials = '';
                            if ($appointment['FIRST_NAME']) $initials .= substr($appointment['FIRST_NAME'], 0, 1);
                            if ($appointment['LAST_NAME']) $initials .= substr($appointment['LAST_NAME'], 0, 1);
                            ?>
                            <span class="namebadge badge badgeprimary badge-pill px-1"><?php echo $initials; ?></span>
                            <span class="name"><?php echo htmlspecialchars($instructor_name); ?></span>
                        </li>
                    <?php endif; ?>
                    <li class="list-inline-item fw-semibold">
                        <span class="badge rounded-pill bg-secondary p-1 d-inline-block me-1"></span>
                        <span class="name"><?php echo htmlspecialchars($location_name); ?></span>
                    </li>
                </ul>
            </div>
        </div>
    <?php
        $appointments->MoveNext();
    endwhile; ?>
<?php else: ?>
    <div class="text-center py-4">
        <p class="theme-text-light">No upcoming appointments found.</p>
    </div>
<?php endif; ?>

<button
    type="button"
    class="btn-secondary w-100 m-1"
    data-user="<?php echo $PK_USER_MASTER; ?>"
    onclick="loadCreateAppointmentModal(this.dataset.user)">
    Add Appointment
</button>