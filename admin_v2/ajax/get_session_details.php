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
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_MASTER.PK_SERVICE_MASTER,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
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
    $PK_ENROLLMENT_MASTER = $appointment_data->fields['PK_ENROLLMENT_MASTER'];
    $PK_ENROLLMENT_SERVICE = $appointment_data->fields['PK_ENROLLMENT_SERVICE'];
    $ENROLLMENT_ID = $appointment_data->fields['ENROLLMENT_ID'];
    $PK_SERVICE_MASTER = $appointment_data->fields['PK_SERVICE_MASTER'];
    $SERVICE_NAME = $appointment_data->fields['SERVICE_NAME'];
    $SERVICE_CODE = $appointment_data->fields['SERVICE_CODE'];
    $COLOR_CODE = $appointment_data->fields['COLOR_CODE'];
    $CUSTOMER_ID = $appointment_data->fields['CUSTOMER_ID'];
    $SERVICE_PROVIDER_ID = $appointment_data->fields['SERVICE_PROVIDER_ID'];
    $APPOINTMENT_TYPE = $appointment_data->fields['APPOINTMENT_TYPE'];

    $STATUS_CODE = $appointment_data->fields['STATUS_CODE'];
    $APPOINTMENT_STATUS = $appointment_data->fields['APPOINTMENT_STATUS'];
    $STATUS_COLOR = $appointment_data->fields['APPOINTMENT_COLOR'];

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

    if ($appointment_data->fields['PK_ENROLLMENT_MASTER'] != 0) {
        $enr_name = $appointment_data->fields['ENROLLMENT_NAME'];
        if (empty($enr_name)) {
            $heading = $ENROLLMENT_ID;
        } else {
            $heading = $ENROLLMENT_ID . ' || ' . $enr_name;
        }
    } elseif ($APPOINTMENT_TYPE == 'DEMO') {
        $heading = 'DEMO';
    } elseif ($APPOINTMENT_TYPE == 'AD-HOC') {
        $heading = 'AD-HOC';
    }

    if ($PK_ENROLLMENT_SERVICE != 0) {
        $appointment_position = 0;
        $enr_service_data = $db_account->Execute("SELECT NUMBER_OF_SESSION FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_SERVICE` = " . $PK_ENROLLMENT_SERVICE);
        if ($enr_service_data->RecordCount() > 0) {
            $appointment_position = getAppointmentPosition($PK_ENROLLMENT_SERVICE, $PK_APPOINTMENT_MASTER);
            $PAID_COUNT = getPaidCount($PK_ENROLLMENT_SERVICE);
            $paid_status = (($appointment_position <= $PAID_COUNT) ? ' (' . ($PAID_COUNT - $appointment_position) . ' Paid)' : ' (Unpaid)');
            $appointment_number = ($appointment_position > 0) ? '  ' . ($appointment_position) . '/' . $enr_service_data->fields['NUMBER_OF_SESSION'] : '';
        }
    } else {
        $appointment_number = '';
        $paid_status = '';
    }

    $profile = getProfileBadge($service_provider_name);
    $profile_name = $profile['initials'];
    $profile_color = $profile['color'];
?>
    <div class="p-2">
        <div class="appointment-profile d-flex">
            <div class="d-flex align-items-center gap-3 f12 theme-text-light">
                <div class="profilename-data">
                    <h6 class="mb-1"><?= $heading ?></h6>
                </div>
            </div>
            <div class="profilebtn-area ms-auto">
                <a href="javascript:;" class="edit-btn" onclick="loadViewAppointmentModal(<?= $PK_APPOINTMENT_MASTER ?>)">
                    <i class="fa fa-edit" aria-hidden="true"></i>
                </a>
                <a title="Delete" href="javascript:" onclick="deleteAppointment(<?= $PK_APPOINTMENT_MASTER ?>, 'normal');" class="delete-btn">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </a>
            </div>
        </div>
        <div>
            <span class="badge ms-auto f-12" style="color: <?= $COLOR_CODE ?>; background-color: <?= $COLOR_CODE ?>20;"><?= $SERVICE_CODE ?></span>&nbsp;
            <span class="ms-auto f-12" style="color:<?= $STATUS_COLOR ?>"><?= $APPOINTMENT_STATUS ?></span>&nbsp;&nbsp;
            <span class="ms-auto f-12"><?= $appointment_number ?></span>&nbsp;&nbsp;
            <span class="ms-auto f-12"><?= $paid_status ?></span>&nbsp;&nbsp;
        </div>
        <div class="statusareatext f12 theme-text-light mt-2">
            <span class=""><?= date('l, M d', strtotime($DATE)) ?>, <?= date('h:i A', strtotime($START_TIME)) ?> - <?= date('h:i A', strtotime($END_TIME)) ?></span>
            <ul class="list-inline mb-0 mt-1">
                <li class="list-inline-item fw-semibold">
                    <span class="namebadge badge sp_badge badge-pill" style="background-color: <?= $profile_color ?>"><?= $profile_name ?></span>
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
                    <a href="javascript:;" class="name text-decoration-underline f12 fw-semibold" onclick="loadViewCustomerModal(<?= $selected_user_id ?>, 0)"><?= $selected_customer ?></a>
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
        <div class="appointment-profile theme-text-light">
            <div class="theme-text-light f12 fw-medium">Next Lesson:</div>
            <span class="f12"><?= getNextScheduledAppointment($PK_APPOINTMENT_MASTER, $CUSTOMER_ID, $PK_SERVICE_MASTER) ?></span>
            <div class="statusarea ms-0">
                <span class="fw-medium"><?= getNextBookedCount($PK_APPOINTMENT_MASTER, $CUSTOMER_ID, $PK_ENROLLMENT_MASTER) ?> more booked</span>
            </div>
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
} elseif ($TYPE == 'group_class') {
    $APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.*,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_MASTER.PK_SERVICE_MASTER,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            GROUP_CONCAT(SERVICE_PROVIDER.PK_USER SEPARATOR ',') AS SERVICE_PROVIDER_ID
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                                                        
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = " . $PK_VALUE;

    $appointment_data = $db_account->Execute($APPOINTMENT_QUERY);

    $PK_APPOINTMENT_MASTER = $appointment_data->fields['PK_APPOINTMENT_MASTER'];
    $heading = ($appointment_data->fields['GROUP_NAME'] != '') ? $appointment_data->fields['GROUP_NAME'] : 'Group Class';

    $SERVICE_CODE = $appointment_data->fields['SERVICE_CODE'];
    $COLOR_CODE = $appointment_data->fields['COLOR_CODE'];

    $APPOINTMENT_STATUS = $appointment_data->fields['APPOINTMENT_STATUS'];
    $STATUS_COLOR = $appointment_data->fields['APPOINTMENT_COLOR'];
    $STATUS_CODE = $appointment_data->fields['STATUS_CODE'];

    $DATE = date("m/d/Y", strtotime($appointment_data->fields['DATE']));
    $START_TIME = $appointment_data->fields['START_TIME'];
    $END_TIME = $appointment_data->fields['END_TIME'];

    $COMMENT = $appointment_data->fields['COMMENT'];
    $INTERNAL_COMMENT = $appointment_data->fields['INTERNAL_COMMENT'];

    $SERVICE_PROVIDER_ID = $appointment_data->fields['SERVICE_PROVIDER_ID'];
    $service_provider_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE FROM DOA_USERS WHERE DOA_USERS.PK_USER = '$SERVICE_PROVIDER_ID'");
    $service_provider_name = $service_provider_data->fields['NAME'];

    $profile = getProfileBadge($service_provider_name);
    $profile_name = $profile['initials'];
    $profile_color = $profile['color'];
?>
    <div class="p-2">
        <div class="appointment-profile d-flex">
            <div class="d-flex align-items-center gap-3 f12 theme-text-light">
                <div class="profilename-data">
                    <h6 class="mb-1"><?= $heading ?></h6>
                </div>
            </div>
            <div class="profilebtn-area ms-auto">
                <a href="javascript:;" class="edit-btn" onclick="loadViewAppointmentModal(<?= $PK_APPOINTMENT_MASTER ?>)">
                    <i class="fa fa-edit" aria-hidden="true"></i>
                </a>
                <a title="Delete" href="javascript:" onclick="deleteAppointment(<?= $PK_APPOINTMENT_MASTER ?>, 'normal');" class="delete-btn">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </a>
            </div>
        </div>
        <div>
            <span class="badge border ms-auto " style="color: #fff; background-color: <?= $COLOR_CODE ?>"><?= $SERVICE_CODE ?></span>&nbsp;
            <span class="ms-auto f-12" style="color:<?= $STATUS_COLOR ?>"><?= $APPOINTMENT_STATUS ?></span>&nbsp;&nbsp;
        </div>
        <div class="statusareatext f12 theme-text-light mt-2">
            <span class=""><?= date('l, M d', strtotime($DATE)) ?>, <?= date('h:i A', strtotime($START_TIME)) ?> - <?= date('h:i A', strtotime($END_TIME)) ?></span>
            <ul class="list-inline mb-0 mt-1">
                <li class="list-inline-item fw-semibold">
                    <span class="namebadge badge sp_badge badge-pill" style="background-color: <?= $profile_color ?>"><?= $profile_name ?></span>
                    <a href="#!" class="name text-decoration-underline"><?= $service_provider_name ?></a>
                </li>
            </ul>
        </div>
        <hr class="my-2">
        <?php
        $appointment_customer_query = "SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM $master_database.DOA_USERS AS DOA_USERS INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER INNER JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER WHERE DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = '$PK_APPOINTMENT_MASTER'";
        $appointment_customer_data = $db_account->Execute($appointment_customer_query);
        ?>
        <div class="appointment-profile d-flex">
            <div class="d-flex align-items-center gap-3 f12 theme-text-light">
                <div class="profilename-data f14"><?= ($appointment_customer_data->RecordCount() > 0) ? $appointment_customer_data->RecordCount() : 'No' ?> Student:</div>
            </div>
        </div>
        <div class="collapse multi-collapse show" id="collaseexample1">
            <?php
            while (!$appointment_customer_data->EOF) {
                $selected_customer = $appointment_customer_data->fields['NAME'];
                $customer_phone = $appointment_customer_data->fields['PHONE'];
                $customer_email = $appointment_customer_data->fields['EMAIL_ID'];
                $selected_customer_id = $appointment_customer_data->fields['PK_USER_MASTER'];
                $selected_user_id = $appointment_customer_data->fields['PK_USER'];
            ?>
                <div class="d-flex align-items-center">
                    <div>
                        <span class="badge bgsuccess d-inline-block p-1"></span>
                        <a href="javascript:;" class="name text-decoration-underline f12 fw-semibold" onclick="loadViewCustomerModal(<?= $selected_user_id ?>, 0)"><?= $selected_customer ?></a>
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
            <?php
                $appointment_customer_data->MoveNext();
            }
            ?>
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
} elseif ($TYPE == 'special_appointment') {
    $SPECIAL_APPOINTMENT_QUERY = "SELECT
                                    DOA_SPECIAL_APPOINTMENT.*,
                                    DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                                    DOA_APPOINTMENT_STATUS.STATUS_CODE,
                                    DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                                    DOA_SCHEDULING_CODE.COLOR_CODE,
                                    DOA_SCHEDULING_CODE.DURATION,
                                    GROUP_CONCAT(SERVICE_PROVIDER.PK_USER SEPARATOR ',') AS SERVICE_PROVIDER_ID
                                    FROM
                                    `DOA_SPECIAL_APPOINTMENT`
                                    LEFT JOIN DOA_SPECIAL_APPOINTMENT_USER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT
                                    LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_SPECIAL_APPOINTMENT_USER.PK_USER = SERVICE_PROVIDER.PK_USER
                                    LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS
                                    LEFT JOIN DOA_SCHEDULING_CODE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE = DOA_SPECIAL_APPOINTMENT.PK_SCHEDULING_CODE
                                    WHERE DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = " . $PK_VALUE;

    $special_appointment_data = $db_account->Execute($SPECIAL_APPOINTMENT_QUERY);

    $PK_SPECIAL_APPOINTMENT = $special_appointment_data->fields['PK_SPECIAL_APPOINTMENT'];
    $TITLE = preg_replace("/\([^)]+\)/", "", $special_appointment_data->fields['TITLE']);
    $DATE = date("m/d/Y", strtotime($special_appointment_data->fields['DATE']));
    $START_TIME = $special_appointment_data->fields['START_TIME'];
    $END_TIME = $special_appointment_data->fields['END_TIME'];
    $DESCRIPTION = $special_appointment_data->fields['DESCRIPTION'];

    $APPOINTMENT_STATUS = $special_appointment_data->fields['APPOINTMENT_STATUS'];
    $STATUS_CODE = $special_appointment_data->fields['STATUS_CODE'];
    $STATUS_COLOR = $special_appointment_data->fields['APPOINTMENT_COLOR'];

    $SERVICE_PROVIDER_ID = $special_appointment_data->fields['SERVICE_PROVIDER_ID'];
    $service_provider_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE FROM DOA_USERS WHERE DOA_USERS.PK_USER = '$SERVICE_PROVIDER_ID'");
    $service_provider_name = $service_provider_data->fields['NAME'];

    $profile = getProfileBadge($service_provider_name);
    $profile_name = $profile['initials'];
    $profile_color = $profile['color'];

?>
    <div class="p-2">
        <div class="appointment-profile d-flex">
            <div class="d-flex align-items-center gap-3 f12 theme-text-light">
                <div class="profilename-data">
                    <h6 class="mb-1"><?= $TITLE ?></h6>
                </div>
            </div>
            <div class="profilebtn-area ms-auto">
                <a title="Edit" href="javascript:;" class="edit-btn" onclick="editSpecialAppointment(<?= $PK_SPECIAL_APPOINTMENT ?>)">
                    <i class="fa fa-edit" aria-hidden="true"></i>
                </a>
                <a title="Delete" href="javascript:" onclick="deleteAppointment(<?= $PK_SPECIAL_APPOINTMENT ?>, 'special_appointment');" class="delete-btn">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </a>
            </div>
        </div>
        <div>
            <span class="ms-auto f-12" style="color:<?= $STATUS_COLOR ?>"><?= $APPOINTMENT_STATUS ?></span>
        </div>
        <div class="statusareatext f12 theme-text-light mt-2">
            <span class=""><?= date('l, M d', strtotime($DATE)) ?>, <?= date('h:i A', strtotime($START_TIME)) ?> - <?= date('h:i A', strtotime($END_TIME)) ?></span>
            <ul class="list-inline mb-0 mt-1">
                <li class="list-inline-item fw-semibold">
                    <span class="namebadge badge sp_badge badge-pill" style="background-color: <?= $profile_color ?>"><?= $profile_name ?></span>
                    <a href="#!" class="name text-decoration-underline"><?= $service_provider_name ?></a>
                </li>
            </ul>
        </div>
        <hr class="my-2">
        <div class="appointment-profile theme-text-light mb-2">
            <div class="theme-text-light f12 fw-medium">Suggested Message:</div>
            <span class="f12 lh-2 d-inline-block"><?= $DESCRIPTION ?></span>
        </div>
    </div>
<?php
}
?>