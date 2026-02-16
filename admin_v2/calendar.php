<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $upload_path;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
$LOCATION_ARRAY = explode(',', $DEFAULT_LOCATION_ID);

$title = "All Appointments";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || (in_array($_SESSION['PK_ROLES'], [1, 4]) && $_SESSION['PK_ROLES'] != 3)) {
    header("location:../login.php");
    exit;
}

$redirect_date = (!empty($_GET['date'])) ? date('Y-m-d', strtotime($_GET['date'])) : "";
$header = 'all_schedules.php';

$SERVICE_PROVIDER_ID = ' ';
if (isset($_GET['SERVICE_PROVIDER_ID']) && $_GET['SERVICE_PROVIDER_ID'] != '') {
    $service_providers = implode(',', $_GET['SERVICE_PROVIDER_ID']);
    $SERVICE_PROVIDER_ID = " AND DOA_USERS.PK_USER IN (" . $service_providers . ") ";
}

$appointment_type = '';

$dayConfig = [];
$location_operational_hour = $db_account->Execute("SELECT MIN(DOA_OPERATIONAL_HOUR.OPEN_TIME) AS OPEN_TIME, MAX(DOA_OPERATIONAL_HOUR.CLOSE_TIME) AS CLOSE_TIME, DAY_NUMBER FROM DOA_OPERATIONAL_HOUR WHERE CLOSED = 0 AND PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") GROUP BY DAY_NUMBER");
if ($location_operational_hour->RecordCount() > 0) {
    while (!$location_operational_hour->EOF) {
        $dayConfig[$location_operational_hour->fields['DAY_NUMBER']] = [
            'minTime' => $location_operational_hour->fields['OPEN_TIME'],
            'maxTime' => $location_operational_hour->fields['CLOSE_TIME']
        ];
        $location_operational_hour->MoveNext();
    }
}

if (isset($_GET['CHOOSE_DATE']) && $_GET['CHOOSE_DATE'] != '') {
    $CHOOSE_DATE = $_GET['CHOOSE_DATE'];
} else {
    $CHOOSE_DATE = date("Y-m-d");
}

$interval = $db->Execute("SELECT MIN(TIME_SLOT_INTERVAL) AS TIME_SLOT_INTERVAL FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")");
if ($interval->fields['TIME_SLOT_INTERVAL'] == "00:00:00") {
    $INTERVAL = "00:15:00";
} else {
    $INTERVAL = $interval->fields['TIME_SLOT_INTERVAL'];
}

$location_operational_hour = $db_account->Execute("SELECT MIN(DOA_OPERATIONAL_HOUR.OPEN_TIME) AS OPEN_TIME, MAX(DOA_OPERATIONAL_HOUR.CLOSE_TIME) AS CLOSE_TIME, DAY_NUMBER FROM DOA_OPERATIONAL_HOUR WHERE CLOSED = 0 AND PK_LOCATION = " . $DEFAULT_LOCATION_ID);
if ($location_operational_hour->RecordCount() > 0) {
    $minTime = $location_operational_hour->fields['OPEN_TIME'];
    $maxTime = $location_operational_hour->fields['CLOSE_TIME'];
} else {
    $minTime = '00:00:00';
    $maxTime = '24:00:00';
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php include 'layout/header.php'; ?>

<script src='../assets/full_calendar_new/moment.min.js'></script>

<link href='../assets/fullcalendar4/fullcalendar.min.css' rel='stylesheet' />
<link href='../assets/fullcalendar4/scheduler.min.css' rel='stylesheet' />

<script src='../assets/fullcalendar4/fullcalendar.min.js'></script>
<script src='../assets/fullcalendar4/scheduler.min.js'></script>

<style>
    .fc-basic-view .fc-day-number {
        display: table-cell;
    }

    .modal-header {
        display: block;
    }

    .modal-dialog {
        max-width: 1200px;
        width: 1100px;
        margin: 2rem auto;
    }

    .fc-time-grid .fc-slats td {
        height: 2.5em;
    }

    .fc-time-grid-event .fc-content {
        color: #000000 !important;
    }

    .fc-event .fc-content {
        color: #000000 !important;
    }

    .SumoSelect {
        width: 100%;
    }

    #add_buttons {
        z-index: 500;
    }

    .fc-more-popover .fc-event-container {
        max-height: 550px;
        overflow: scroll;
    }

    .fc-event {
        border: none !important;
        border-radius: 10px !important;
        margin: 1px 0px 1px 2px !important;
        text-align: left !important;
        padding: 8px !important;
    }

    .fc-content {
        margin: 5px !important;
    }

    .fc-resource-cell {
        padding-bottom: 10px !important;
    }

    .fc-time-grid-event .fc-time {
        font-size: 11px;
    }

    .fc-content .fc-title {
        margin-top: 3px;
        line-height: 19px;
        font-size: 12px;
    }
</style>
<style>
    .list-select {
        height: 30px;
        width: 100%;
        line-height: 2em;
        border: 1px solid #ccc;
        margin: 0;
        list-style: none;
        padding-left: 0;
    }

    .list-select li {
        padding: 1px 10px;
        z-index: 2;
    }

    .list-select li:not(.init) {
        float: left;
        width: 100%;
        display: none;
        *background: #ddd;
        border-top: 1px solid #eeecec;
    }

    .list-select li:not(.init):hover,
    .list-select li.selected:not(.init) {
        background: #09f;
        border-top: 1px solid #eeecec;
    }

    li.init {
        cursor: pointer;
    }
</style>
<style>
    .search-container {
        display: flex;
        align-items: center;
        gap: 10px;
        /* Default gap for desktop */
    }

    .search-button:hover {
        background-color: #0056b3;
    }

    /* Media query for tablets (for example, max-width 768px) */
    @media (max-width: 768px) {
        .search-container {
            gap: 8px;
            /* Reduced gap for tablet screens */
        }

        .SERVICE_PROVIDER_ID {
            width: 150px;
            /* Adjust input width for tablet */
        }

        .btn {
            font-size: 14px;
            /* Smaller button size for tablets */
        }
    }
</style>

<style>
    .clearfix {
        display: flex;
        flex-wrap: nowrap;

    }

    .clearfix .box {
        background-color: #f1f1f1;
        height: 37px;
        text-align: center;
        padding-right: 10px;
        padding-left: 0px;
    }



    .radio-buttons {
        display: flex;
        gap: 15px;
    }

    .radio-buttons input[type="radio"] {
        display: none;
    }

    .radio-buttons label {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0px 15px;
        border-radius: 25px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        border: 2px solid #ccc;
        transition: all 0.3s ease;
        background: #f9f9f9;
        color: #666666ff;
    }

    .radio-buttons input[type="radio"]:checked+label {
        background: #39b54a;
        border-color: #39b54a;
        color: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .radio-buttons label:hover {
        transform: scale(1.05);
    }
</style>


<!-- View Appointment Css -->
<style>
    /* ================================
   TOOLBAR
================================ */
    .calendar-header {
        /* background: #fff;
        border-radius: 15px; */
        padding: 0px 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        /* box-shadow: 0 1px 4px rgba(0, 0, 0, .08); */
        flex-wrap: wrap;
        margin-top: 10px;
    }

    /* ================================
   PILL BUTTONS
================================ */
    .chip {
        border: 1px solid #e5e7eb;
        background: #fff;
        padding: 7px 20px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 25px;
        cursor: pointer;
        white-space: nowrap;
    }

    .chip-icon {
        width: 35px;
        height: 35px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    /* ================================
   DATE
================================ */
    .date-chip {
        font-weight: 600;
    }

    /* ================================
   AVATARS
================================ */
    .staff-avatars {
        display: flex;
        align-items: center;
        margin-left: 4px;
    }

    .staff-avatar {
        width: 21px;
        height: 21px;
        border-radius: 50%;
        font-size: 8px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        border: 2px solid #fff;
        margin-left: -6px;
    }

    .staff-avatar-me {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        border: 2px solid #fff;
        margin-left: -6px;
    }

    .a-ce {
        background: #ef4444
    }

    .a-rc {
        background: #f97316
    }

    .a-mc {
        background: #3b82f6
    }

    .a-pe {
        background: #39b54a
    }

    .a-more {
        background: #e5e7eb;
        color: #374151;
    }

    /* ================================
   VIEW TOGGLE
================================ */
    .view-toggle {
        display: flex;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        overflow: hidden;
    }

    .view-btn {
        padding: 6px 16px;
        border: none;
        background: #fff;
        font-size: 14px;
        color: #6b7280;
    }

    .view-btn.active {
        color: #39b54a;
        font-weight: 600;
    }



    .view-btn-icon {
        padding: 6px 16px;
        border: none;
        background: #fff;
        font-size: 14px;
        color: #6b7280;
    }

    .view-btn-icon.active {
        color: #39b54a;
        font-weight: 600;
    }

    /* ================================
   NEW APPOINTMENT
================================ */
    .btn-new {
        background: #39b54a;
        color: #fff;
        border-radius: 999px;
        padding: 8px 20px !important;
        font-size: 14px;
        font-weight: 600;
        border: none;
    }

    .SumoSelect .optWrapper {
        width: 200% !important;
    }

    /* ================================
   SEARCH FORM RESET
================================ */
    #search_form {
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        background: transparent !important;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        width: auto;
        min-width: auto;
    }

    .sp_badge {
        height: 25px;
        width: 25px;
        border-radius: 20px;
        padding: 6px 0px;
        font-size: 12px;
    }

    /* ================================
   RESPONSIVE DESIGN
================================ */
    @media (max-width: 1024px) {
        .calendar-header {
            padding: 10px 10px;
            gap: 6px;
        }

        .chip {
            padding: 6px 15px;
            font-size: 13px;
            gap: 15px;
        }

        #CHOOSE_DATE {
            min-width: 180px !important;
        }

        .chip.m-r-15 {
            margin-right: 8px !important;
        }

        .chip.m-r-20 {
            margin-right: 10px !important;
        }
    }

    @media (max-width: 768px) {
        .calendar-header {
            padding: 8px 8px;
            gap: 5px;
            justify-content: flex-start;
        }

        .chip {
            padding: 5px 12px;
            font-size: 12px;
            gap: 12px;
            flex: 0 1 auto;
        }

        .chip-icon {
            width: 32px;
            height: 32px;
        }

        #CHOOSE_DATE {
            min-width: 140px !important;
            font-size: 12px;
        }

        .staff-avatar {
            width: 18px;
            height: 18px;
            font-size: 7px;
        }

        .staff-avatar-me {
            width: 32px;
            height: 32px;
            font-size: 11px;
        }

        .chip.m-r-15 {
            margin-right: 5px !important;
        }

        .chip.m-r-20 {
            margin-right: 5px !important;
        }

        select.chip {
            min-width: 130px !important;
            font-size: 12px;
        }
    }

    @media (max-width: 480px) {
        .calendar-header {
            padding: 6px 6px;
            gap: 4px;
            justify-content: space-between;
        }

        .chip {
            padding: 4px 10px;
            font-size: 11px;
            gap: 8px;
            flex: 0 1 auto;
        }

        .chip-icon {
            width: 30px;
            height: 30px;
            font-size: 12px;
        }

        .chip.m-r-15 {
            margin-right: 0 !important;
        }

        .chip.m-r-20 {
            margin-right: 0 !important;
        }

        #CHOOSE_DATE {
            min-width: 110px !important;
            font-size: 11px;
            padding: 5px 10px !important;
        }

        .staff-avatars {
            display: none;
        }

        .staff-avatar-me {
            width: 28px;
            height: 28px;
            font-size: 10px;
        }

        select.chip {
            min-width: 100px !important;
            font-size: 11px;
            max-width: 100px;
            padding: 4px 8px !important;
        }
    }
</style>
<style>
    .overlay2 {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1049;
    }

    .side-drawer {
        position: fixed;
        top: 0;
        right: -500px;
        width: 500px;
        max-width: 90vw;
        height: 100vh;
        background: white;
        transition: right 0.3s ease;
        z-index: 1050;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
    }

    .side-drawer.open {
        right: 0;
    }

    .close-btn {
        font-size: 24px;
        cursor: pointer;
        background: none;
        border: none;
    }

    /* Make sure the drawer appears above calendar */
    .fc .fc-daygrid-day-frame,
    .fc .fc-timegrid-slot-lane {
        z-index: auto !important;
    }


    .side-drawer {
        margin-top: 70px;
        height: 92% !important;
        border-radius: 15px;
        max-width: 575px;
    }


    .edit-btn {
        font-size: 18px;
        color: #39b54a;
        margin-right: 5px;
    }

    .delete-btn {
        font-size: 18px;
        color: #ef4444;
    }

    .btn-icon {
        font-size: 18px;
        color: #6b7280;
    }

    .ext-tag {
        background-color: #eeebff;
        color: #8c75e7;
    }

    .pri-tag {
        background-color: #feebf4;
        color: #ed85b7;
    }

    .grp-tag {
        background-color: #ebf2ff;
        color: #6b82e2;
    }

    .f-12 {
        font-size: 12px;
    }
</style>

<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet" />

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="calendar-header">
            <button class="chip m-r-15" onclick="todayDate = new Date(); renderCalendar(todayDate);">Today</button>
            <button class="chip chip-icon" id="prevDay" onclick="if(calendar.view.type === 'agendaDay') { todayDate.setDate(todayDate.getDate() - 1); renderCalendar(todayDate); } else { calendar.prev(); setTimeout(function() { updateChooseDateInput(); }, 100); }"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>
            <button class="chip chip-icon m-r-20" id="nextDay" onclick="if(calendar.view.type === 'agendaDay') { todayDate.setDate(todayDate.getDate() + 1); renderCalendar(todayDate); } else { calendar.next(); setTimeout(function() { updateChooseDateInput(); }, 100); }"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>

            <form id="search_form">
                <input type="hidden" id="IS_SELECTED" value="0">
                <input type="text" id="CHOOSE_DATE" name="CHOOSE_DATE" class="chip date-chip m-r-15 datepicker-normal-calendar" placeholder="Choose Date" value="<?= !empty($_GET['date']) ? date('l, M d, Y', strtotime($_GET['date'])) : date('l, M d, Y') ?>" style="min-width: 240px;">

                <div class="chip m-r-15" style="height: 37px;">
                    <div class="staff-avatars">
                        <div class="staff-avatar a-ce">CE</div>
                        <div class="staff-avatar a-rc">RC</div>
                        <div class="staff-avatar a-mc">MC</div>
                        <div class="staff-avatar a-pe">PE</div>
                        <div class="staff-avatar a-pe">PE</div>
                        <div class="staff-avatar a-pe">PE</div>
                        <div class="staff-avatar a-more">+2</div>
                    </div>

                    <select class="SERVICE_PROVIDER_ID multi_sumo_select" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID" onchange="$('#search_form').submit();" style="border:none;" multiple>
                        <?php
                        $row = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.DISPLAY_ORDER FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.DISPLAY_ORDER ASC");
                        while (!$row->EOF) { ?>
                            <option value="<?= $row->fields['PK_USER'] ?>" <?= (!empty($service_providers) && in_array($row->fields['PK_USER'], explode(',', $service_providers))) ? "selected" : "" ?>><?= $row->fields['NAME'] ?></option>
                        <?php $row->MoveNext();
                        } ?>
                    </select>
                </div>

                <span class="staff-avatar-me a-pe m-r-15">RM</span>

                <select class="chip m-r-15" name="STATUS_CODE" id="STATUS_CODE" onchange="$('#search_form').submit();" style="height: 37px; min-width: 150px;">
                    <option value="">Select Status</option>
                    <?php
                    $row = $db->Execute("SELECT * FROM DOA_APPOINTMENT_STATUS WHERE ACTIVE = 1");
                    while (!$row->EOF) { ?>
                        <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS']; ?>"><?= $row->fields['APPOINTMENT_STATUS'] ?></option>
                    <?php $row->MoveNext();
                    } ?>
                </select>

                <select class="chip m-r-15" name="APPOINTMENT_TYPE" id="APPOINTMENT_TYPE" onchange="$('#search_form').submit();" style="height: 37px; min-width: 230px;">
                    <option value="">Select Appointment Type</option>
                    <option value="NORMAL">Appointment</option>
                    <option value="GROUP">Group Class</option>
                    <option value="TO-DO">To Dos</option>
                    <option value="EVENT">Event</option>
                </select>
            </form>

            <div class="view-toggle m-r-15" style="height: 37px; margin-left: auto;">
                <button class="view-btn active" data-view="day" onclick="changeView('agendaDay')">Day</button>
                <button class="view-btn" data-view="week" onclick="changeView('agendaWeek')">Week</button>
                <button class="view-btn" data-view="month" onclick="changeView('month')">Month</button>
            </div>

            <div class="view-toggle m-r-15" style="height: 37px;">
                <button class="view-btn-icon active">
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </button>
                <button class="view-btn-icon" onclick="window.location.href='calendar_list_view.php'">
                    <i class="fa fa-list" aria-hidden="true"></i>
                </button>
            </div>

            <button class="btn-new" id="openDrawer">＋ New Appointment</button>
        </div>


        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid body_content" style="margin-top: 10px; padding: 0px 15px !important;">
                <div class="row">
                    <div id="appointment_list_half" class="col-12">
                        <div class="card" style="border-radius: 15px;">

                            <div class="card-body row">
                                <div class="col-12" id='calendar-container'>
                                    <div id='calendar'></div>
                                </div>

                                <div class="col-2" id='external-events' style="display: none;">
                                    <a href="javascript:;" onclick="closeCopyPasteDiv()" style="float: right; font-size: 25px;">&times;</a>
                                    <h5>Copy OR Move Events</h5>
                                    <?php if (in_array('Calendar Move/Copy', $PERMISSION_ARRAY) || in_array('Appointments Move/Copy', $PERMISSION_ARRAY) || in_array('To-Do Move/Copy', $PERMISSION_ARRAY)) { ?>
                                        <div class="radio-buttons" style="margin-bottom: 15px;">
                                            <input type='radio' name="copy_move" id='drop-copy' checked />
                                            <label for='drop-copy'><i class="fa fa-copy"></i> Copy</label>

                                            <input type='radio' name="copy_move" id='drop-remove' />
                                            <label for='drop-remove'><i class="fa fa-cut"></i> Move</label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <?php if (in_array('Calendar Edit', $PERMISSION_ARRAY)) { ?>
                        <div id="edit_appointment_half" class="col-6" style="display: none;">
                            <div class="card">
                                <div class="card-body">
                                    <a href="javascript:;" onclick="closeEditAppointment()" style="float: right; font-size: 25px;">&times;</a>
                                    <div class="card-body" id="appointment_details_div">

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment Details -->
    <div class="overlay2"></div>
    <div class="side-drawer" id="sideDrawer2">
        <div class="drawer-header text-end border-bottom px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Appointment Details</h6>
            <span class="close-btn" id="closeDrawer2">&times;</span>
        </div>
        <div class="modal-body p-3" id="edit_appointment_div" style="overflow-y: auto; height: calc(100% - 130px); min-height: 820px;">
            <!-- Content will be loaded here via AJAX -->
        </div>
        <div class="modal-footer flex-nowrap border-top">
            <button type="button" class="btn-secondary w-100 m-1" id="closeDrawer2">Cancel</button>
            <button type="button" class="btn-primary w-100 m-1" onclick="submitEditAppointmentForm(this)">Save</button>
        </div>
    </div>

    <!-- Customer Details -->
    <div class="overlay3"></div>
    <div class="side-drawer" id="sideDrawer3">
        <div class="drawer-header text-end border-bottom px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <svg class="close-btn" id="closeDrawer3" xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 100 100" viewBox="0 0 100 100" width="16px" height="16px" fill="CurrentColor">
                    <path d="m44.93 76.47c.49.49 1.13.73 1.77.73s1.28-.24 1.77-.73c.98-.98.98-2.56 0-3.54l-21.43-21.43h51.96c1.38 0 2.5-1.12 2.5-2.5s-1.12-2.5-2.5-2.5h-51.96l21.43-21.43c.98-.98.98-2.56 0-3.54s-2.56-.98-3.54 0l-25.7 25.7c-.98.98-.98 2.56 0 3.54z" />
                </svg>
                <span>Customer Details</span>
            </h6>
            <span class="close-btn" id="closeDrawer3">&times;</span>
        </div>
        <div class="drawer-body" style="overflow-y: auto; height: calc(100% - 0px);">
            <!-- Content will be loaded here via AJAX -->
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <?php include 'partials/create_appointment_modal.php'; ?>

    <?php include 'partials/create_enrollment_modal.php'; ?>

    <script src='https://unpkg.com/popper.js/dist/umd/popper.min.js'></script>
    <script src='https://unpkg.com/tooltip.js/dist/umd/tooltip.min.js'></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <script>
        $(window).on('load', function() {
            let redirect_date = '<?= $redirect_date ?>';
            if (redirect_date) {
                let parts = redirect_date.split('-'); // "YYYY-MM-DD"
                let currentDate = new Date(parts[0], parts[1] - 1, parts[2]); // Local date
                renderCalendar(currentDate);
            }
        });

        $('#APPOINTMENT_DATE').datepicker({
            onSelect: function() {
                getSlots(this);
            }
        });

        $('#TO_DO_APPOINTMENT_DATE').datepicker({
            onSelect: function() {
                getSlots(this);
            }
        });

        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        $('.datepicker-normal-calendar').datepicker({
            dateFormat: "DD, M d, yy",
            onSelect: function() {
                $('#IS_SELECTED').val(1);
                $("#search_form").submit();
            }
        });


        function showMessage() {
            if (<?= count($LOCATION_ARRAY) ?> === 1) {
                let currentDate = new Date(calendar.getDate());
                window.location.href = 'create_appointment.php?date=' + currentDate;
            } else {
                swal("Select One Location!", "Only one location can be selected on top of the page in order to schedule an appointment.", "error");
            }
        }

        function payNow(PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BILLED_AMOUNT, ENROLLMENT_ID) {
            $('.partial_payment').show();
            $('#PARTIAL_PAYMENT').prop('checked', false);
            $('.partial_payment_div').slideUp();

            $('.PAYMENT_TYPE').val('');
            $('#remaining_amount_div').slideUp();

            $('#enrollment_number').text(ENROLLMENT_ID);
            $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
            $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
            $('#ACTUAL_AMOUNT').val(BILLED_AMOUNT);
            $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
            let PK_USER_MASTER = $('.PK_USER_MASTER').val();
            $('.CUSTOMER_ID').val(PK_USER_MASTER);
            //$('#payment_confirmation_form_div_customer').slideDown();
            //openPaymentModel();
            $('#enrollment_payment_modal').modal('show');
        }

        function paySelected(PK_ENROLLMENT_MASTER, ENROLLMENT_ID) {
            $('.partial_payment').hide();
            $('#PARTIAL_PAYMENT').prop('checked', false);
            $('.partial_payment_div').slideUp();

            $('.PAYMENT_TYPE').val('');
            $('#remaining_amount_div').slideUp();

            let BILLED_AMOUNT = [];
            let PK_ENROLLMENT_LEDGER = [];

            $(".PAYMENT_CHECKBOX_" + PK_ENROLLMENT_MASTER + ":checked").each(function() {
                BILLED_AMOUNT.push($(this).data('billed_amount'));
                PK_ENROLLMENT_LEDGER.push($(this).val());
            });

            let TOTAL = BILLED_AMOUNT.reduce(getSum, 0);

            function getSum(total, num) {
                return total + num;
            }

            $('#enrollment_number').text(ENROLLMENT_ID);
            $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
            $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
            $('#ACTUAL_AMOUNT').val(parseFloat(TOTAL).toFixed(2));
            $('#AMOUNT_TO_PAY').val(parseFloat(TOTAL).toFixed(2));
            //$('#payment_confirmation_form_div_customer').slideDown();
            //openPaymentModel();
            $('#enrollment_payment_modal').modal('show');
        }
    </script>

    <script>
        var is_editable = <?= in_array('Calendar Edit', $PERMISSION_ARRAY) ? 1 : 0; ?>;
        var move_copy = <?= in_array('Calendar Move/Copy', $PERMISSION_ARRAY) ? 1 : 0; ?>;
        $('.multi_sumo_select').SumoSelect({
            placeholder: 'All Staff',
            selectAll: true,
            okCancelInMulti: true,
            triggerChangeCombined: true
        });

        let calendar;
        let redirect_date = '<?= $redirect_date ?>';
        let todayDate = redirect_date ? new Date(redirect_date) : new Date();
        const dayConfigs = <?= json_encode($dayConfig) ?>;

        let activePopoverInstance = null;
        let activePopoverEl = null;
        let hideTimer = null;


        function renderCalendar(date) {
            const day = date.getDay();
            const config = dayConfigs[day] || {
                minTime: '00:00:00',
                maxTime: '24:00:00'
            };

            if (calendar) {
                calendar.destroy();
            }

            let clickCount = 0;
            var Draggable = FullCalendar.Draggable;

            var containerEl = document.getElementById('external-events');
            var calendarEl = document.getElementById('calendar');
            var checkbox = document.getElementById('drop-remove');

            // new Draggable(containerEl, {
            //     itemSelector: '.fc-event',
            //     eventData: function(eventEl) {
            //         let color = eventEl.attributes["data-color"].value;
            //         let type = eventEl.attributes["data-type"].value;
            //         let duration = eventEl.attributes["data-duration"].value;
            //         return {
            //             title: eventEl.innerText,
            //             backgroundColor: color,
            //             type: type,
            //             duration: '00:'+duration
            //         };
            //     }
            // });

            // Initialize Draggable only once
            if (!containerEl.dataset.draggableInitialized) {
                new FullCalendar.Draggable(containerEl, {
                    itemSelector: '.fc-event',
                    eventData: function(eventEl) {
                        let color = eventEl.attributes["data-color"].value;
                        let type = eventEl.attributes["data-type"].value;
                        let duration = eventEl.attributes["data-duration"].value;
                        return {
                            title: eventEl.innerText,
                            backgroundColor: color,
                            type: type,
                            duration: '00:' + duration
                        };
                    }
                });
                containerEl.dataset.draggableInitialized = true; // Mark as initialized
            }

            calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
                editable: true,
                selectable: true,
                eventLimit: true,
                scrollTime: '00:00',
                /* header: {
                    left: 'customToday,customPrev,customNext',
                    center: 'title',
                    right: 'agendaDay,agendaWeek,month,'
                }, */
                header: false,
                customButtons: {
                    customPrev: {
                        text: '<',
                        click: function() {
                            if (calendar.view.type == 'agendaDay') {
                                todayDate.setDate(todayDate.getDate() - 1);
                                renderCalendar(todayDate);
                                //calendar.gotoDate(todayDate);
                            } else {
                                calendar.prev();
                            }
                        }
                    },
                    customNext: {
                        text: '>',
                        click: function() {
                            if (calendar.view.type == 'agendaDay') {
                                todayDate.setDate(todayDate.getDate() + 1);
                                renderCalendar(todayDate);
                                //calendar.gotoDate(todayDate);
                            } else {
                                calendar.next();
                            }
                        }
                    },
                    customToday: {
                        text: 'Today',
                        click: function() {
                            todayDate = new Date();
                            renderCalendar(todayDate);
                            //calendar.gotoDate(todayDate);
                        }
                    }
                },

                /*header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'agendaDay,agendaWeek,month,'
                },*/
                views: {
                    agendaDay: {
                        titleFormat: {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            weekday: 'long'
                        }
                    }
                },
                defaultDate: date,
                defaultView: 'agendaDay',
                slotDuration: '00:10:00',
                slotLabelInterval: {
                    minutes: 60
                },
                minTime: config.minTime,
                maxTime: config.maxTime,
                contentHeight: 1000,
                windowResize: true,
                droppable: true,
                allDaySlot: false,
                drop: function(info) {
                    if (checkbox.checked) {
                        info.draggedEl.parentNode.removeChild(info.draggedEl);
                        copyAppointment(info, 'move');
                    } else {
                        copyAppointment(info, 'copy');
                    }
                },
                eventReceive: function(arg) { // called when a proper external event is dropped
                    arg.event.remove();
                },
                resourceOrder: 'sortOrder',
                resources: function(info, successCallback, failureCallback) {
                    //console.log(info);
                    let selected_service_provider = [];
                    let selectedOptions = $('#SERVICE_PROVIDER_ID').find('option:selected');
                    selectedOptions.each(function() {
                        selected_service_provider.push($(this).val());
                    });

                    $.ajax({
                        url: "pagination/get_resource_data.php",
                        type: "POST",
                        data: {
                            selected_service_provider: selected_service_provider
                        },
                        dataType: 'json',
                        success: function(result) {
                            console.log(result);
                            successCallback(result);
                            if ((selected_service_provider.length > 1) && (calendar.view.type === 'agendaWeek')) {
                                calendar.setOption('editable', false);
                            } else {
                                if (selected_service_provider.length === 1) {
                                    calendar.setOption('editable', true);
                                }
                            }
                            //getServiceProviderCount();
                            if (calendar.view.type === 'month') {
                                calendar.changeView('month');
                            }
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            console.log(xhr.status);
                            console.log(thrownError);
                        }
                    });
                },
                events: function(info, successCallback, failureCallback) {
                    $('#day-count').html('<i class="fas fa-spinner fa-pulse" style="font-size: 20px;"></i>');
                    $('#week-count').html('<i class="fas fa-spinner fa-pulse" style="font-size: 20px;"></i>');
                    let STATUS_CODE = $('#STATUS_CODE').val();
                    let APPOINTMENT_TYPE = $('#APPOINTMENT_TYPE').val();

                    let START_DATE = moment(info.start).format();
                    let END_DATE = moment(info.end).format();

                    let selected_service_provider = [];
                    let selectedOptions = $('#SERVICE_PROVIDER_ID').find('option:selected');
                    selectedOptions.each(function() {
                        selected_service_provider.push($(this).val());
                    });

                    $.ajax({
                        url: "pagination/get_calendar_data.php",
                        type: "POST",
                        data: {
                            START_DATE: START_DATE,
                            END_DATE: END_DATE,
                            STATUS_CODE: STATUS_CODE,
                            APPOINTMENT_TYPE: APPOINTMENT_TYPE,
                            SERVICE_PROVIDER_ID: selected_service_provider
                        },
                        dataType: 'json',
                        success: function(result) {
                            console.log(result);
                            successCallback(result);
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            console.log(xhr.status);
                            console.log(thrownError);
                        }
                    });
                },
                eventRender: function(info) {
                    let event_data = info.event.extendedProps;
                    let element = info.el;

                    if (event_data.customerName) {
                        $(element).find(".fc-title").append('<br><strong style="font-size: 12px; font-weight: bold;">' + event_data.customerName + '</strong> ');
                    }

                    if (event_data.comment || event_data.internal_comment) {
                        $(element).find(".fc-title").prepend(' <i class="fa fa-comment" style="font-size: 13px"></i> ');
                    }




                    function destroyActivePopover() {
                        if (hideTimer) {
                            clearTimeout(hideTimer);
                            hideTimer = null;
                        }

                        if (activePopoverInstance) {
                            activePopoverInstance.dispose();
                            activePopoverInstance = null;
                            activePopoverEl = null;
                        }
                    }



                    const el = info.el;

                    if (el.dataset.tooltipBound) return;
                    el.dataset.tooltipBound = '1';

                    function showPopover(html) {
                        destroyActivePopover();

                        // Element may be destroyed by FullCalendar
                        if (!document.body.contains(el)) return;

                        activePopoverEl = el;
                        activePopoverInstance = new bootstrap.Popover(el, {
                            content: html,
                            html: true,
                            placement: 'top',
                            trigger: 'manual',
                            container: 'body',
                            sanitize: false
                        });

                        activePopoverInstance.show();

                        // Keep alive when hovering popover
                        document.querySelectorAll('.popover').forEach(pop => {
                            pop.onmouseenter = () => {
                                if (hideTimer) clearTimeout(hideTimer);
                            };
                            pop.onmouseleave = () => {
                                hideTimer = setTimeout(destroyActivePopover, 150);
                            };
                        });
                    }

                    el.addEventListener('mouseenter', () => {
                        if (hideTimer) clearTimeout(hideTimer);

                        if (el.dataset.tooltipHtml) {
                            showPopover(el.dataset.tooltipHtml);
                            return;
                        }

                        showPopover('<div style="padding:8px">Loading…</div>');

                        $.ajax({
                            url: 'ajax/get_session_details.php',
                            type: 'POST',
                            data: {
                                id: info.event.id,
                                type: info.event.extendedProps.type || ''
                            },
                            success: function(html) {
                                if (!document.body.contains(el)) return;
                                el.dataset.tooltipHtml = html;
                                showPopover(html);
                            },
                            error: function() {
                                const html = '<div style="padding:8px">Unable to load details</div>';
                                el.dataset.tooltipHtml = html;
                                showPopover(html);
                            }
                        });
                    });

                    el.addEventListener('mouseleave', () => {
                        hideTimer = setTimeout(destroyActivePopover, 150);
                    });












                },
                eventClick: function(info) {
                    clickCount++;
                    let singleClickTimer;
                    if (clickCount === 1 && is_editable) {
                        singleClickTimer = setTimeout(function() {
                            if (clickCount === 1) {
                                let event_data = info.event;
                                let event_data_ext_prop = info.event.extendedProps;
                                let TYPE = event_data_ext_prop.type;
                                loadViewAppointmentModal(event_data.id, TYPE);
                                //showAppointmentEdit(info);
                            }
                            clickCount = 0;
                        }, 500);
                    } else if (clickCount === 2) {
                        clearTimeout(singleClickTimer);
                        clickCount = 0;

                        let selected_service_provider = [];
                        let selectedOptions = $('#SERVICE_PROVIDER_ID').find('option:selected');
                        selectedOptions.each(function() {
                            selected_service_provider.push($(this).val());
                        });
                        let appointment_type = info.event.extendedProps.type;
                        if (appointment_type !== 'not_available') {
                            if (calendar.view.type === 'agendaWeek' && move_copy) {
                                if (selected_service_provider.length === 1) {
                                    $('#calendar-container').removeClass('col-12').addClass('col-10');
                                    let event_data = info.event;
                                    let event_data_ext_prop = info.event.extendedProps;
                                    let TYPE = event_data_ext_prop.type;

                                    $('#external-events').show().addClass('col-2').append("<div class='fc-event fc-h-event' data-id='" + event_data.id + "' data-duration='" + event_data_ext_prop.duration + "' data-color='" + event_data.backgroundColor + "' data-type='" + TYPE + "' style='background-color: " + event_data.backgroundColor + ";'>" + event_data.title + "<span><a href='javascript:;' onclick='removeFromHere(this)' style='float: right; font-size: 25px; margin-top: -6px;'>&times;</a></span></div>");
                                }
                            } else {
                                if (calendar.view.type === 'agendaDay') {
                                    $('#calendar-container').removeClass('col-12').addClass('col-10');
                                    let event_data = info.event;
                                    let event_data_ext_prop = info.event.extendedProps;
                                    let TYPE = event_data_ext_prop.type;

                                    $('#external-events').show().addClass('col-2').append("<div class='fc-event fc-h-event' data-id='" + event_data.id + "' data-duration='" + event_data_ext_prop.duration + "' data-color='" + event_data.backgroundColor + "' data-type='" + TYPE + "' style='background-color: " + event_data.backgroundColor + ";'>" + event_data.title + "<span><a href='javascript:;' onclick='removeFromHere(this)' style='float: right; font-size: 25px; margin-top: -6px;'>&times;</a></span></div>");
                                }
                            }
                        }
                    }
                },
                eventDrop: function(info) {
                    modifyAppointment(info);
                },
                dateClick: function(data) {
                    let date = data.date;
                    let resource_id = (data.resource) ? data.resource.id : $('#SERVICE_PROVIDER_ID').val()[0];
                    console.log(resource_id);
                    clickCount++;
                    let singleClickTimer;
                    if (clickCount === 1) {
                        singleClickTimer = setTimeout(function() {
                            clickCount = 0;
                        }, 400);
                    } else if (clickCount === 2) {
                        clearTimeout(singleClickTimer);
                        clickCount = 0;
                        if (resource_id) {
                            if (<?= count($LOCATION_ARRAY) ?> === 1) {
                                $.ajax({
                                    url: "ajax/check_service_provider_slot.php",
                                    type: "POST",
                                    data: {
                                        PK_USER: resource_id,
                                        DATE_TIME: date
                                    },
                                    //dataType: 'json',
                                    success: function(result) {
                                        if (result == 1) {
                                            window.location.href = "create_appointment.php?date=" + date + "&SERVICE_PROVIDER_ID=" + resource_id;
                                        } else {
                                            swal("No slot available!", result, "error");
                                        }
                                    },
                                });
                            } else {
                                swal("Select One Location!", "Only one location can be selected on top of the page in order to schedule an appointment.", "error");
                            }
                        } else {
                            swal("Select One Service Provider!", "Please select any one Service Provider to continue", "error");
                        }
                    }
                },
                loading: function(isLoading) {
                    if (isLoading === true) {
                        //alert('asd');
                    } else {
                        getServiceProviderCount();
                    }
                },
                datesSet: function(info) {
                    // Update CHOOSE_DATE whenever dates change (navigation)
                    updateChooseDateInput(todayDate);
                    destroyActivePopover();
                },
                eventLeave: function() {
                    destroyActivePopover();
                }

            });

            calendar.render();

            // Update CHOOSE_DATE input with current date
            updateChooseDateInput(date);
        }

        function loadViewAppointmentModal(appointmentId, TYPE) {
            $('#sideDrawer2, .overlay2').addClass('active');
            $.ajax({
                url: "partials/view_appointment_modal.php",
                type: "POST",
                data: {
                    PK_APPOINTMENT_MASTER: appointmentId,
                    TYPE: TYPE
                },
                success: function(result) {
                    // Update the drawer content with view_appointment_modal
                    $('#edit_appointment_div').html(result);

                    // Re-initialize any scripts if needed
                    initializeModalScripts();
                },
                error: function(xhr, status, error) {
                    console.error("Error loading view_appointment_modal.php:", error);
                    $('#edit_appointment_div').html('<p>Error loading appointment details.</p>');
                }
            });
        }

        function loadViewCustomerModal(customerId, PK_ENROLLMENT_MASTER) {
            //$('#sideDrawer2, .overlay2').removeClass('active'); // Close appointment modal
            $('#sideDrawer3, .overlay3').addClass('active'); // Open customer modal

            $.ajax({
                url: "partials/view_customer_modal.php",
                type: "POST",
                data: {
                    PK_USER: customerId,
                    PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER
                },
                success: function(result) {
                    // Update the customer drawer content
                    $('#sideDrawer3 .drawer-body').html(result);
                    initializeModalScripts();
                },
                error: function(xhr, status, error) {
                    console.error("Error loading view_customer_modal.php:", error);
                    $('#sideDrawer3 .drawer-body').html('<p>Error loading customer details.</p>');
                }
            });
        }

        function loadCreateAppointmentModal(PK_USER_MASTER) {
            $('#sideDrawer, .overlay').addClass('active');
            $.ajax({
                url: "partials/create_appointment_modal.php",
                type: "POST",
                data: {
                    PK_USER_MASTER: PK_USER_MASTER
                },
                success: function(result) {
                    // Update the drawer content with create_appointment_modal
                    $('#sideDrawer .drawer-body').html(result);

                    // Re-initialize any scripts if needed
                    initializeModalScripts();
                },
                error: function(xhr, status, error) {
                    console.error("Error loading create_appointment_modal.php:", error);
                    $('#sideDrawer .drawer-body').html('<p>Error loading appointment creation form.</p>');
                }
            });
        }

        function initializeModalScripts() {
            // Re-initialize any scripts that were in view_appointment_modal.php
            // For example, datepickers, select menus, etc.

            // Initialize datepicker if exists
            if ($.fn.datepicker) {
                $('.datepicker').datepicker();
            }

            // Initialize SumoSelect if exists
            if ($.fn.SumoSelect) {
                $('.sumoselect').SumoSelect();
            }

            // Re-attach event handlers
            attachModalEventHandlers();
        }

        function attachModalEventHandlers() {
            // Attach event handlers for buttons in the modal
            $(document).off('click', '.modal-save-btn').on('click', '.modal-save-btn', function() {
                // Handle save button click
                saveAppointmentChanges();
            });

            $(document).off('click', '.modal-cancel-btn').on('click', '.modal-cancel-btn', function() {
                closeSideDrawer2();
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            //todayDate.setDate(todayDate.getDate() + 5);
            renderCalendar(todayDate);
            //renderCalendar(new Date());
        });

        /*$('.fc-prev-button').click(function () {
            getServiceProviderCount();
        });
        $('.fc-next-button').click(function () {
            getServiceProviderCount();
        });
        $('.fc-today-button').click(function () {
            getServiceProviderCount();
        });*/


        $(document).on('click', '.fc-agendaDay-button', function() {
            window.location.reload();
            /*calendar.setOption('editable', true);
            getServiceProviderCount();*/
        });

        $(document).on('click', '.fc-agendaWeek-button', function() {
            calendar.setOption('editable', false);
        });

        $(document).on('click', '.fc-month-button', function() {
            calendar.setOption('editable', false);
        });

        var interval = 15;

        function updateChooseDateInput(date) {
            let displayText = '';

            // Get calendar title based on current view
            if (calendar && calendar.view) {
                displayText = calendar.view.title;
            } else {
                // Fallback: Format date as "Day, Mon DD, YYYY"
                const options = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'short',
                    day: '2-digit'
                };
                displayText = date.toLocaleDateString('en-US', options);
            }

            $('#CHOOSE_DATE').val(displayText);
        }

        function changeView(view) {
            // Update active button styling
            $('.view-btn').removeClass('active');
            $('[data-view="' + view.replace('agenda', '').toLowerCase() + '"]').addClass('active');

            // Change calendar view
            if (view === 'agendaDay') {
                calendar.changeView('agendaDay');
                updateChooseDateInput(todayDate);
            } else if (view === 'agendaWeek') {
                calendar.changeView('agendaWeek');
                setTimeout(function() {
                    updateChooseDateInput(todayDate);
                }, 100);
            } else if (view === 'month') {
                calendar.changeView('month');
                setTimeout(function() {
                    updateChooseDateInput(todayDate);
                }, 100);
            }
        }

        function zoomInOut(type) {
            if (type == 'in' && interval > 10) {
                interval = interval - 5;
            } else {
                if (type == 'out') {
                    interval = interval + 5;
                }
            }
            calendar.setOption('slotDuration', '00:' + interval + ':00');
            getServiceProviderCount();
        }

        function showAppointmentEdit(info) {
            $('#calendar-container').removeClass('col-10').addClass('col-12');
            $('#external-events').hide();
            let event_data = info.event.extendedProps;
            if (event_data.type === 'appointment') {
                $('#appointment_list_half').removeClass('col-12');
                $('#appointment_list_half').addClass('col-6');
                $.ajax({
                    url: "ajax/get_appointment_details.php",
                    type: "POST",
                    data: {
                        PK_APPOINTMENT_MASTER: info.event.id
                    },
                    async: false,
                    cache: false,
                    success: function(result) {
                        $('#appointment_details_div').html(result);
                        $('#edit_appointment_half').show();
                    }
                });
            } else {
                if (event_data.type === 'special_appointment') {
                    $('#appointment_list_half').removeClass('col-12');
                    $('#appointment_list_half').addClass('col-6');
                    $.ajax({
                        url: "ajax/get_special_appointment_details.php",
                        type: "POST",
                        data: {
                            PK_APPOINTMENT_MASTER: info.event.id
                        },
                        async: false,
                        cache: false,
                        success: function(result) {
                            $('#appointment_details_div').html(result);
                            $('#edit_appointment_half').show();
                        }
                    });
                } else {
                    if (event_data.type === 'group_class') {
                        $('#appointment_list_half').removeClass('col-12');
                        $('#appointment_list_half').addClass('col-6');
                        $.ajax({
                            url: "ajax/get_group_class_details.php",
                            type: "POST",
                            data: {
                                PK_APPOINTMENT_MASTER: info.event.id
                            },
                            async: false,
                            cache: false,
                            success: function(result) {
                                $('#appointment_details_div').html(result);
                                $('#edit_appointment_half').show();
                                $('.multi_sumo_select').SumoSelect({
                                    placeholder: 'Select Customer',
                                    selectAll: true,
                                    search: true,
                                    searchText: "Search Customer"
                                });
                            }
                        });
                    } else {
                        if (event_data.type === 'event') {
                            $('#appointment_list_half').removeClass('col-12');
                            $('#appointment_list_half').addClass('col-6');
                            $.ajax({
                                url: "ajax/get_event_details.php",
                                type: "POST",
                                data: {
                                    PK_EVENT: info.event.id
                                },
                                async: false,
                                cache: false,
                                success: function(result) {
                                    $('#appointment_details_div').html(result);
                                    $('#edit_appointment_half').show();
                                }
                            });
                        }
                    }
                }
            }
        }

        function closeEditAppointment() {
            $('#edit_appointment_half').hide();
            $('#appointment_list_half').removeClass('col-6').addClass('col-12');
        }

        function closeCopyPasteDiv() {
            $('#calendar-container').removeClass('col-10').addClass('col-12');
            $('#external-events').hide();
        }

        function removeFromHere(param) {
            $(param).parent().parent().remove();
        }

        function copyAppointment(info, operation) {
            let eventEl = info.draggedEl;
            let PK_ID = eventEl.attributes["data-id"].value;
            let TYPE = eventEl.attributes["data-type"].value;
            let SERVICE_PROVIDER_ID = (info.resource) ? info.resource.id : $('#SERVICE_PROVIDER_ID').val()[0];
            let START_DATE_TIME = info.dateStr;
            //console.log(TYPE,PK_ID,SERVICE_PROVIDER_ID,START_DATE_TIME);
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    FUNCTION_NAME: 'copyAppointment',
                    OPERATION: operation,
                    PK_ID: PK_ID,
                    TYPE: TYPE,
                    SERVICE_PROVIDER_ID: SERVICE_PROVIDER_ID,
                    START_DATE_TIME: START_DATE_TIME
                },
                async: false,
                cache: false,
                success: function(data) {
                    //getServiceProviderCount();
                    calendar.refetchEvents();
                }
            });
        }

        function modifyAppointment(info) {
            let OLD_SERVICE_PROVIDER_ID = (info.oldResource) ? info.oldResource.id : 0;
            let event_data = info.event.extendedProps;
            let TYPE = event_data.type;
            let PK_ID = info.event.id;
            let SERVICE_PROVIDER_ID = (info.newResource) ? info.newResource.id : 0;
            let START_DATE_TIME = moment.utc(info.event._instance.range.start).format();
            let END_DATE_TIME = moment.utc(info.event._instance.range.end).format();

            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    FUNCTION_NAME: 'modifyAppointment',
                    PK_ID: PK_ID,
                    TYPE: TYPE,
                    OLD_SERVICE_PROVIDER_ID: OLD_SERVICE_PROVIDER_ID,
                    SERVICE_PROVIDER_ID: SERVICE_PROVIDER_ID,
                    START_DATE_TIME: START_DATE_TIME,
                    END_DATE_TIME: END_DATE_TIME
                },
                async: false,
                cache: false,
                success: function(data) {
                    //getServiceProviderCount();
                    calendar.refetchEvents();
                }
            });
        }

        function getServiceProviderCount() {
            let currentDate = new Date(calendar.getDate());
            //renderCalendar(currentDate);
            let day = currentDate.getDate();
            let month = currentDate.getMonth() + 1;
            let year = currentDate.getFullYear();

            let selected_service_provider = [];
            let selectedOptions = $('#SERVICE_PROVIDER_ID').find('option:selected');
            selectedOptions.each(function() {
                selected_service_provider.push($(this).val());
            });

            let calendar_view = calendar.view.type;

            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    FUNCTION_NAME: 'getServiceProviderCount',
                    currentDate: year + '-' + month + '-' + day,
                    selected_service_provider: selected_service_provider,
                    calendar_view: calendar_view
                },
                async: false,
                cache: false,
                success: function(result) {
                    let result_data = JSON.parse(result).service_provider;
                    for (let i = 0; i < result_data.length; i++) {

                        let sp_name = result_data[i].SERVICE_PROVIDER_NAME.trim();
                        let sp_initials = result_data[i].INITIALS;
                        let sp_color = result_data[i].COLOR;

                        let avatarHTML = `<div style="display:flex; flex-direction:column; align-items:center; text-align:center; gap:4px; width:100%; margin-top: 10px;">
                                                <div style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background-color:${sp_color};color:#fff;font-weight:600;font-size:14px;letter-spacing:1px;">
                                                    ${sp_initials}
                                                </div>
                                                <div style="max-width:100%;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                    ${sp_name}
                                                </div>
                                            </div>`;

                        $('th[data-resource-id="' + result_data[i].SERVICE_PROVIDER_ID + '"]').html(avatarHTML);
                    }
                }
            });
        }

        $(document).on('submit', '#search_form', function(event) {
            event.preventDefault();
            let IS_SELECTED = $('#IS_SELECTED').val();
            if (IS_SELECTED == 1) {
                let CHOOSE_DATE = $('#CHOOSE_DATE').val();
                let currentDate = new Date(CHOOSE_DATE);
                renderCalendar(currentDate);

                todayDate = currentDate;

                $('#IS_SELECTED').val(0);
            } else {
                calendar.refetchEvents();
            }
            calendar.refetchResources();
            setTimeout(function() {
                getServiceProviderCount()
            }, 1000);
        });

        /* function createAppointment(type, param) {
            $('.btn').removeClass('button-selected');
            $(param).addClass('button-selected');
            let url = '';
            if (type === 'group_class') {
                url = "ajax/add_group_classes.php";
            }
            if (type === 'int_app') {
                url = "ajax/add_special_appointment.php";
            }
            if (type === 'appointment') {
                url = "ajax/add_appointment.php";
            }
            if (type === 'standing') {
                url = "ajax/add_multiple_appointment.php";
            }
            if (type === 'ad_hoc') {
                url = "ajax/add_ad_hoc_appointment.php";
            }
            if (type === 'appointments') {
                url = "create_appointment.php";
            }
            $.ajax({
                url: url,
                type: "POST",
                success: function(data) {
                    $('#create_form_div').html(data);
                }
            });
        } */
    </script>


    <script>
        function deleteAppointment(PK_APPOINTMENT_MASTER, type) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteAppointment',
                            type: type,
                            PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                        },
                        success: function(data) {
                            calendar.refetchEvents();
                        }
                    });
                }
            });
        }
    </script>

    <!-- JavaScript for Popup -->
    <script>
        function showPopup(type, src) {
            let popup = document.getElementById("mediaPopup");
            let image = document.getElementById("popupImage");
            let video = document.getElementById("popupVideo");
            let videoSource = document.getElementById("popupVideoSource");

            if (type === 'image') {
                image.src = src;
                image.style.display = "block";
                video.style.display = "none";
            } else if (type === 'video') {
                videoSource.src = src;
                video.load();
                video.style.display = "block";
                image.style.display = "none";
            }

            popup.style.display = "flex";

            // Add event listener to detect ESC key press
            document.addEventListener("keydown", escClose);
        }

        function closePopup() {
            document.getElementById("mediaPopup").style.display = "none";
            document.removeEventListener("keydown", escClose); // Remove listener when popup is closed
        }

        // Function to detect ESC key press and close the popup
        function escClose(event) {
            if (event.key === "Escape") {
                closePopup();
            }
        }

        // Disable right-click on images and videos
        document.addEventListener("contextmenu", function(event) {
            let target = event.target;
            if (target.tagName === "IMG" || target.tagName === "VIDEO") {
                event.preventDefault(); // Prevent right-click menu
            }
        });

        // Optional: Disable right-click for the whole page
        // Uncomment the line below if you want to block right-click everywhere
        // document.addEventListener("contextmenu", (event) => event.preventDefault());

        // Function to delete uploaded image
        function ConfirmDeleteImage(PK_APPOINTMENT_MASTER, imageNumber) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteImage',
                            PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER,
                            imageNumber: imageNumber
                        },
                        success: function(data) {
                            window.location.href = 'all_schedules.php';
                        }
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire("Cancelled", "Your image is safe.", "info"); // ✅ Show feedback for cancel
                }
            });
        }

        function ConfirmDeleteVideo(PK_APPOINTMENT_MASTER, videoNumber) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteVideo',
                            PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER,
                            videoNumber: videoNumber
                        },
                        success: function(data) {
                            window.location.href = 'all_schedules.php';
                        }
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire("Cancelled", "Your video is safe.", "info"); // ✅ Show feedback for cancel
                }
            });
        }
    </script>

    <script>
        (function($) {
            $.fn.countTo = function(options) {
                return this.each(function() {
                    var $this = $(this);

                    // Always get fresh data attributes using attr()
                    var settings = $.extend({}, $.fn.countTo.defaults, {
                        from: parseFloat($this.attr('data-from')) || 0,
                        to: parseFloat($this.attr('data-to')) || 0,
                        speed: parseInt($this.attr('data-speed')) || 1000,
                        refreshInterval: parseInt($this.attr('data-refresh-interval')) || 100,
                        decimals: parseInt($this.attr('data-decimals')) || 0
                    }, options);

                    // Clear existing interval if any
                    if ($this.data('countToInterval')) {
                        clearInterval($this.data('countToInterval'));
                    }

                    var loops = Math.ceil(settings.speed / settings.refreshInterval),
                        increment = (settings.to - settings.from) / loops,
                        value = settings.from,
                        loopCount = 0;

                    function render(val) {
                        var formatted = settings.formatter.call($this, val, settings);
                        $this.html(formatted);
                    }

                    function updateTimer() {
                        value += increment;
                        loopCount++;
                        render(value);

                        if (typeof settings.onUpdate === 'function') {
                            settings.onUpdate.call($this, value);
                        }

                        if (loopCount >= loops) {
                            clearInterval(interval);
                            render(settings.to);
                            $this.removeData('countToInterval');

                            if (typeof settings.onComplete === 'function') {
                                settings.onComplete.call($this, settings.to);
                            }
                        }
                    }

                    render(value);
                    var interval = setInterval(updateTimer, settings.refreshInterval);
                    $this.data('countToInterval', interval);
                });
            };

            $.fn.countTo.defaults = {
                from: 0,
                to: 0,
                speed: 1000,
                refreshInterval: 100,
                decimals: 0,
                formatter: function(value, settings) {
                    return value.toFixed(settings.decimals);
                },
                onUpdate: null,
                onComplete: null
            };
        })(jQuery);
    </script>


    <script>
        $('.multi_sumo_select').SumoSelect({
            placeholder: 'Select <?= $service_provider_title ?>',
            selectAll: true
        });

        $('#TO_DO_START_TIME').timepicker({
            timeFormat: 'hh:mm p',
            maxTime: '<?= $maxTime ?>',
            minTime: '<?= $minTime ?>',
            change: function() {
                calculateEndTime();
            },
        });

        $('#TO_DO_END_TIME').timepicker({
            timeFormat: 'hh:mm p',
            maxTime: '<?= $maxTime ?>',
            minTime: '<?= $minTime ?>'
        });



        $(document).ready(function() {
            $(".btn-available").click(function() {
                $(this).toggleClass("active");
                $(".slot_div").toggle();
            });

            $('#openDrawer').click(function() {
                $('#sideDrawer, .overlay').addClass('active');
            });

            $('#closeDrawer, .overlay').click(function() {
                $('#sideDrawer, .overlay').removeClass('active');
            });

            $('#openDrawer2').click(function() {
                $('#sideDrawer2, .overlay2').addClass('active');
            });

            $('#closeDrawer2, .overlay2').click(function() {
                $('#sideDrawer2, .overlay2').removeClass('active');
            });

            $('#openDrawer3').click(function() {
                $('#sideDrawer3, .overlay3').addClass('active');
            });

            $('#closeDrawer3, .overlay3').click(function() {
                $('#sideDrawer3, .overlay3').removeClass('active');
            });

            $('#openDrawer4').click(function() {
                $('#sideDrawer4, .overlay4').addClass('active');
            });

            $('#closeDrawer4, .overlay4').click(function() {
                $('#sideDrawer4, .overlay4').removeClass('active');
            });

            $('#openDrawer5').click(function() {
                $('#sideDrawer5, .overlay5').addClass('active');
            });

            $('#closeDrawer5, .overlay5').click(function() {
                $('#sideDrawer5, .overlay5').removeClass('active');
            });

            $('#openDrawer6').click(function() {
                $('#sideDrawer6, .overlay6').addClass('active');
                $('#sideDrawer5, .overlay5').removeClass('active');
            });

            $('#closeDrawer6, .overlay6').click(function() {
                $('#sideDrawer6, .overlay6').removeClass('active');
                $('#sideDrawer5, .overlay5').removeClass('active');
            });
        });

        $(document).ready(function() {
            $('.ends input[type="radio"]').on('change', function() {

                // Disable all inputs first
                $('.ends .form-control').prop('disabled', true);

                // Enable input next to selected radio (if any)
                var $row = $(this).closest('.d-flex');
                if ($row.length) {
                    $row.find('.form-control')
                        .prop('disabled', false)
                        .focus();
                }

            });
        });
    </script>


    <script>
        function submitEditAppointmentForm() {
            let form = $('#edit_appointment_form');

            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return false;
            }

            form.submit();
        }
    </script>

</body>

</html>