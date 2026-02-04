<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

$PK_VALUE = isset($_POST['id']) ? $_POST['id'] : 0;
$TYPE = isset($_POST['type']) ? $_POST['type'] : '';

if ($TYPE == 'appointment') {
    $APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.*,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_MASTER.PK_SERVICE_MASTER,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            GROUP_CONCAT(SERVICE_PROVIDER.PK_USER SEPARATOR ',') AS SERVICE_PROVIDER_ID,
                            GROUP_CONCAT(DOA_USER_MASTER.PK_USER_MASTER SEPARATOR ',') AS CUSTOMER_ID
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = " . $PK_VALUE;

    $appointment_data = $db_account->Execute($APPOINTMENT_QUERY);

    $PK_APPOINTMENT_MASTER = $appointment_data->fields['PK_APPOINTMENT_MASTER'];
    $ENROLLMENT_ID = $appointment_data->fields['ENROLLMENT_ID'];
    $SERVICE_NAME = $appointment_data->fields['SERVICE_NAME'];
    $SERVICE_CODE = $appointment_data->fields['SERVICE_CODE'];
    $COLOR_CODE = $appointment_data->fields['COLOR_CODE'];
    $CUSTOMER_ID = $appointment_data->fields['CUSTOMER_ID'];
    $SERVICE_PROVIDER_ID = $appointment_data->fields['SERVICE_PROVIDER_ID'];

    $DATE = date("m/d/Y", strtotime($appointment_data->fields['DATE']));
    $START_TIME = $appointment_data->fields['START_TIME'];
    $END_TIME = $appointment_data->fields['END_TIME'];
    $COMMENT = $appointment_data->fields['COMMENT'];
    $INTERNAL_COMMENT = $appointment_data->fields['INTERNAL_COMMENT'];


    $customer_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$CUSTOMER_ID'");

    $selected_customer = $customer_data->fields['NAME'];
    $customer_phone = $customer_data->fields['PHONE'];
    $customer_email = $customer_data->fields['EMAIL_ID'];
    $selected_customer_id = $customer_data->fields['PK_USER_MASTER'];
    $selected_user_id = $customer_data->fields['PK_USER'];

    $service_provider_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE FROM DOA_USERS WHERE DOA_USERS.PK_USER = '$SERVICE_PROVIDER_ID'");
    $service_provider_name = $service_provider_data->fields['NAME'];
?>
    <div class="p-2">
        <div class="appointment-profile d-flex">
            <div class="d-flex align-items-center gap-3 f12 theme-text-light">
                <div class="profilename-data">
                    <h6 class="mb-1"><?= $ENROLLMENT_ID ?></h6>
                </div>
            </div>
            <div class="profilebtn-area ms-auto">
                <a href="javascript:;" class="edit-btn" onclick="loadViewAppointmentModal(<?= $PK_APPOINTMENT_MASTER ?>)">
                    <i class="fa fa-edit" aria-hidden="true"></i>
                </a>
                <a href="javascript;" class="delete-btn">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </a>
            </div>
        </div>
        <span class="badge border ms-auto" style="color: #000; background-color: <?= $COLOR_CODE ?>"><?= $SERVICE_CODE ?></span>
        <div class="statusareatext f12 theme-text-light mt-2">
            <span class=""><?= date('l, M d', strtotime($DATE)) ?>, <?= date('h:i A', strtotime($START_TIME)) ?> - <?= date('h:i A', strtotime($END_TIME)) ?></span>
            <ul class="list-inline mb-0 mt-1">
                <li class="list-inline-item fw-semibold">
                    <span class="namebadge badge badgeprimary badge-pill px-1"><?= getInitials($service_provider_name) ?></span>
                    <a href="#!" class="name text-decoration-underline"><?= $service_provider_name ?></a>
                </li>
            </ul>
        </div>
        <hr class="my-2">
        <div class="appointment-profile d-flex">
            <div class="d-flex align-items-center gap-3 f12 theme-text-light">
                <div class="profilename-data f14">1 student:</div>
            </div>
        </div>
        <div class="collapse multi-collapse show" id="collaseexample1">
            <div class="d-flex align-items-center">
                <div>
                    <span class="badge bgsuccess d-inline-block p-1"></span>
                    <a href="#!" class="name text-decoration-underline f12 fw-semibold"><?= $selected_customer ?></a>
                    <div class="theme-text-light f12 ms-2"><?= $customer_phone ?></div>
                </div>
                <div class="d-flex gap-2 ms-auto">
                    <a href="javascript;" class="btn-icon">
                        <i class="fa fa-envelope" aria-hidden="true"></i>
                    </a>
                    <a href="javascript;" class="btn-icon">
                        <i class="fa fa-comment" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="my-2">


        <?php
        // Get next booked lesson for this customer
        $nextLessonQuery = "SELECT 
                    DOA_APPOINTMENT_MASTER.*,
                    DOA_SERVICE_MASTER.SERVICE_NAME,
                    DOA_SCHEDULING_CODE.SCHEDULING_CODE,
                    DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                    DOA_APPOINTMENT_STATUS.COLOR_CODE,
                    GROUP_CONCAT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) SEPARATOR ', ') AS SERVICE_PROVIDERS,
                    DOA_LOCATION.LOCATION_NAME
                FROM DOA_APPOINTMENT_MASTER
                LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS
                LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_APPOINTMENT_MASTER.PK_LOCATION = DOA_LOCATION.PK_LOCATION
                WHERE DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = '$CUSTOMER_ID'
                AND DOA_APPOINTMENT_MASTER.ACTIVE = 1
                AND DOA_APPOINTMENT_MASTER.DATE >= CURDATE()
                AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS IN (1, 2, 3) -- Scheduled, Confirmed, Completed
                AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER != '$PK_APPOINTMENT_MASTER' -- Exclude current appointment
                GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                ORDER BY DOA_APPOINTMENT_MASTER.DATE ASC, DOA_APPOINTMENT_MASTER.START_TIME ASC
                LIMIT 1";
        //echo $nextLessonQuery;

        $nextLesson = $db_account->Execute($nextLessonQuery);

        if ($nextLesson->RecordCount() > 0) {
            $nextLessonDate = date('l, M d', strtotime($nextLesson->fields['DATE']));
            $nextLessonStartTime = date('h:i A', strtotime($nextLesson->fields['START_TIME']));
            $nextLessonEndTime = date('h:i A', strtotime($nextLesson->fields['END_TIME']));
            $nextLessonServiceName = $nextLesson->fields['SERVICE_NAME'];
            $nextLessonSchedulingCode = $nextLesson->fields['SCHEDULING_CODE'];
            $nextLessonStatus = $nextLesson->fields['APPOINTMENT_STATUS'];
            $nextLessonColor = $nextLesson->fields['COLOR_CODE'];
            $nextLessonServiceProviders = $nextLesson->fields['SERVICE_PROVIDERS'];
            $nextLessonLocation = $nextLesson->fields['LOCATION_NAME'];

            // Determine appointment type badge
            $appointmentType = 'PRI'; // Default
            $badgeClass = 'ext-tag'; // Default class
            if (strpos($nextLessonSchedulingCode, 'GRP') !== false || strpos($nextLessonSchedulingCode, 'GROUP') !== false) {
                $appointmentType = 'GRP';
                $badgeClass = 'grp-tag';
            } elseif (strpos($nextLessonSchedulingCode, 'EXT') !== false) {
                $appointmentType = 'EXT';
                $badgeClass = 'ext-tag';
            }

            // Get service provider initials
            $providerInitials = '';
            if ($nextLessonServiceProviders) {
                $providers = explode(', ', $nextLessonServiceProviders);
                if (count($providers) > 0) {
                    $firstProvider = trim($providers[0]);
                    $nameParts = explode(' ', $firstProvider);
                    if (count($nameParts) >= 2) {
                        $providerInitials = substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1);
                    } else {
                        $providerInitials = substr($firstProvider, 0, 2);
                    }
                }
            }
        } else {
            $hasNextLesson = false;
        }
        ?>
        <div class="appointment-profile theme-text-light">
            <div class="theme-text-light f12 fw-medium">Next Lesson:</div>
            <?php if (isset($hasNextLesson) && !$hasNextLesson): ?>
                <span class="f12">No upcoming appointments scheduled.</span>
            <?php elseif ($nextLesson->RecordCount() > 0): ?>
                <span class="f12"><?php echo $nextLessonDate; ?>, <?php echo $nextLessonStartTime; ?> - <?php echo $nextLessonEndTime; ?></span>
            <?php endif; ?>
            <!-- <div class="statusarea ms-0">
                <span class="fw-medium">2 more booked</span>
            </div> -->
        </div>
        <hr class="my-2">
        <div class="appointment-profile theme-text-light mb-2">
            <div class="theme-text-light f12 fw-medium">Public Note:</div>
            <span class="f12 lh-2 d-inline-block"><?= $COMMENT ?></span>
        </div>
        <div class="appointment-profile theme-text-light">
            <div class="theme-text-light f12 fw-medium">Internal Note:</div>
            <span class="f12 lh-2 d-inline-block"><?= $INTERNAL_COMMENT ?></span>
        </div>
    </div>
<?php
}
?>