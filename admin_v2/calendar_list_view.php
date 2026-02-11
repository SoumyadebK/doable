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
        width: 150% !important;
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
            <button class="chip m-r-15" id="toDay">Today</button>
            <button class="chip chip-icon" id="prevDay"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>
            <button class="chip chip-icon m-r-20" id="nextDay"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>

            <form id="search_form">
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
                    <option value="AD-HOC">Ad-Hoc</option>
                    <option value="DEMO">Record Only</option>
                </select>
            </form>

            <div class="view-toggle m-r-15" style="height: 37px; margin-left: auto;">
                <button class="view-btn-icon" onclick="window.location.href='calendar.php'">
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </button>
                <button class="view-btn-icon active" onclick="window.location.href='calendar_list_view.php'">
                    <i class="fa fa-list" aria-hidden="true"></i>
                </button>
            </div>

            <button class="btn-new" id="openDrawer">＋ New Appointment</button>
        </div>


        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid body_content" style="margin-top: 10px; padding: 0px 15px !important;">
                <div class="table-responsive schedule-wrapper">
                    <table class="table align-middle schedule-table mb-0">
                        <thead>
                            <tr>
                                <th class="sticky-col date-col border-bottom"></th>
                                <th>Title</th>
                                <th>
                                    <button type="button" class="bg-transparent p-0 border-0 theme-text-light">
                                        <span class="fw-semibold">Time</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                            <path d="M11 7h-6l3-4z" />
                                            <path d="M5 9h6l-3 4z" />
                                        </svg>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="bg-transparent p-0 border-0 theme-text-light">
                                        <span class="fw-semibold">Status</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                            <path d="M11 7h-6l3-4z" />
                                            <path d="M5 9h6l-3 4z" />
                                        </svg>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="bg-transparent p-0 border-0 theme-text-light">
                                        <span class="fw-semibold">Service Provider</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                            <path d="M11 7h-6l3-4z" />
                                            <path d="M5 9h6l-3 4z" />
                                        </svg>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="bg-transparent p-0 border-0 theme-text-light">
                                        <span class="fw-semibold">Description</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 16 16" width="14px" height="14px" fill="CurrentColor">
                                            <path d="M11 7h-6l3-4z" />
                                            <path d="M5 9h6l-3 4z" />
                                        </svg>
                                    </button>
                                </th>
                                <th width="60px"></th>
                            </tr>
                        </thead>

                        <tbody>

                        </tbody>
                    </table>
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
        <div class="drawer-body" style="overflow-y: auto; height: calc(100% - 100px);">
            <!-- Content will be loaded here via AJAX -->
        </div>
        <!-- <div class="modal-footer flex-nowrap p-2 border-top">
            <button type="button" class="btn-secondary w-100 m-1" id="closeDrawer2">Cancel</button>
            <button type="button" class="btn-primary w-100 m-1">Save</button>
        </div> -->
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

    <?php include 'partials/create_appointment_modal.php'; ?>

    <?php require_once('../includes/footer.php'); ?>

    <script src='https://unpkg.com/popper.js/dist/umd/popper.min.js'></script>
    <script src='https://unpkg.com/tooltip.js/dist/umd/tooltip.min.js'></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <script>
        $(window).on('load', function() {
            $('#search_form').submit();
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

        function loadViewAppointmentModal(appointmentId) {
            $('#sideDrawer2, .overlay2').addClass('active');
            $.ajax({
                url: "partials/view_appointment_modal.php",
                type: "POST",
                data: {
                    PK_APPOINTMENT_MASTER: appointmentId
                },
                success: function(result) {
                    // Update the drawer content with view_appointment_modal
                    $('#sideDrawer2 .drawer-body').html(result);

                    // Re-initialize any scripts if needed
                    initializeModalScripts();
                },
                error: function(xhr, status, error) {
                    console.error("Error loading view_appointment_modal.php:", error);
                    $('#sideDrawer2 .drawer-body').html('<p>Error loading appointment details.</p>');
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

        // Helper function to format date for the picker (format: "DD, M d, yyyy")
        function formatDateForPicker(date) {
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            };
            return date.toLocaleDateString('en-US', options);
        }

        // Helper function to extract date from the picker input and return Date object
        function getDateFromPicker() {
            let dateString = $('#CHOOSE_DATE').val();
            // Parse the format "Day, Mon d, yy"
            let date;
            if (dateString) {
                date = new Date(dateString);
            } else {
                date = new Date();
            }
            return date;
        }

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



        $(document).on('submit', '#search_form', function(event) {
            event.preventDefault();
            let formData = $(this).serialize();

            $.ajax({
                url: "partials/appointment_table.php",
                type: "POST",
                data: formData,
                success: function(result) {
                    $('.schedule-table tbody').html(result);
                },
            });

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


    <script>
        $('.multi_sumo_select').SumoSelect({
            placeholder: 'Select <?= $service_provider_title ?>',
            selectAll: true,
            okCancelInMulti: true,
            triggerChangeCombined: true
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
            // Today button functionality
            $('#toDay').click(function() {
                let today = new Date();
                let formattedDate = formatDateForPicker(today);
                $('#CHOOSE_DATE').val(formattedDate);
                $('#search_form').submit();
            });

            // Previous day button functionality
            $('#prevDay').click(function() {
                let currentDate = getDateFromPicker();
                currentDate.setDate(currentDate.getDate() - 1);
                let formattedDate = formatDateForPicker(currentDate);
                $('#CHOOSE_DATE').val(formattedDate);
                $('#search_form').submit();
            });

            // Next day button functionality
            $('#nextDay').click(function() {
                let currentDate = getDateFromPicker();
                currentDate.setDate(currentDate.getDate() + 1);
                let formattedDate = formatDateForPicker(currentDate);
                $('#CHOOSE_DATE').val(formattedDate);
                $('#search_form').submit();
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




</body>

</html>