<?php
require_once('../global/config.php');
$title = "All Schedules";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 4){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['id']) && !empty($_GET['action'])){
    if ($_GET['action'] == 'complete'){
        $db_account->Execute("UPDATE DOA_APPOINTMENT_MASTER SET PK_APPOINTMENT_STATUS = 2 WHERE PK_APPOINTMENT_MASTER = ".$_GET['id']);
        header("location:all_schedules.php");
    }
}

if (!empty($_GET['view'])){
    $view = 'list';
}else{
    $view = 'table';
}

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveAppointmentData'){
    unset($_POST['TIME']);
    unset($_POST['FUNCTION_NAME']);
    if (empty($_POST['START_TIME']) || empty($_POST['END_TIME'])){
        unset($_POST['START_TIME']);
        unset($_POST['END_TIME']);
    }
    $session_cost = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_SERVICE_MASTER = '$_POST[PK_SERVICE_MASTER]' AND PK_SERVICE_CODE = '$_POST[PK_SERVICE_CODE]'");
    $price_per_session = $session_cost->fields['PRICE_PER_SESSION'];
    if(empty($_POST['PK_APPOINTMENT_MASTER'])){
        $_POST['PK_APPOINTMENT_STATUS'] = 1;
        $_POST['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $_POST['ACTIVE'] = 1;
        $_POST['CREATED_BY']  = $_SESSION['PK_USER'];
        $_POST['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_APPOINTMENT_MASTER', $_POST, 'insert');
    }else{
        //$_POST['ACTIVE'] = $_POST['ACTIVE'];
        if($_FILES['IMAGE']['name'] != ''){
            $extn 			= explode(".",$_FILES['IMAGE']['name']);
            $iindex			= count($extn) - 1;
            $rand_string 	= time()."-".rand(100000,999999);
            $file11			= 'appointment_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
            $extension   	= strtolower($extn[$iindex]);

            if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
                $image_path    = '../uploads/appointment_image/'.$file11;
                move_uploaded_file($_FILES['IMAGE']['tmp_name'], $image_path);
                $_POST['IMAGE'] = $image_path;
            }
        }
        $_POST['EDITED_BY']	= $_SESSION['PK_USER'];
        $_POST['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_APPOINTMENT_MASTER', $_POST, 'update'," PK_APPOINTMENT_MASTER =  '$_POST[PK_APPOINTMENT_MASTER]'");

        if ($_POST['PK_APPOINTMENT_STATUS'] == 2 || ($_POST['PK_APPOINTMENT_STATUS'] == 4 && $_POST['NO_SHOW'] == 'Charge')) {
            $enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '$_POST[PK_ENROLLMENT_MASTER]'");
            if ($enrollment_balance->RecordCount() > 0) {
                $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_USED'] = $enrollment_balance->fields['TOTAL_BALANCE_USED'] + $price_per_session;
                $ENROLLMENT_BALANCE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                $ENROLLMENT_BALANCE_DATA['EDITED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
            }
        }
    }

    rearrangeSerialNumber($_POST['PK_ENROLLMENT_MASTER'], $price_per_session);

    header("location:all_schedules.php");
}

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveGroupClassData'){
    $PK_GROUP_CLASS = $_POST['PK_GROUP_CLASS'];
    $GROUP_CLASS_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
    $GROUP_CLASS_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
    //$GROUP_CLASS_DATA['SERVICE_PROVIDER_ID_1'] = $_POST['SERVICE_PROVIDER_ID_1'];
    //$GROUP_CLASS_DATA['SERVICE_PROVIDER_ID_2'] = $_POST['SERVICE_PROVIDER_ID_2'];
    $GROUP_CLASS_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
    $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
    $GROUP_CLASS_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
    $GROUP_CLASS_DATA['EDITED_ON'] = date("Y-m-d H:i");
    if (isset($_POST['STANDING_ID'])) {
        db_perform_account('DOA_GROUP_CLASS', $GROUP_CLASS_DATA, 'update', " STANDING_ID =  '$_POST[STANDING_ID]'");
    } else {
        $GROUP_CLASS_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
        db_perform_account('DOA_GROUP_CLASS', $GROUP_CLASS_DATA, 'update', " PK_GROUP_CLASS =  '$PK_GROUP_CLASS'");
    }

    $db_account->Execute("DELETE FROM `DOA_GROUP_CLASS_CUSTOMER` WHERE `PK_GROUP_CLASS` = '$PK_GROUP_CLASS'");
    if (isset($_POST['PK_USER_MASTER'])) {
        for ($i = 0; $i < count($_POST['PK_USER_MASTER']); $i++) {
            $GROUP_CLASS_USER_DATA['PK_GROUP_CLASS'] = $PK_GROUP_CLASS;
            $GROUP_CLASS_USER_DATA['PK_USER_MASTER'] = $_POST['PK_USER_MASTER'][$i];
            db_perform_account('DOA_GROUP_CLASS_CUSTOMER', $GROUP_CLASS_USER_DATA, 'insert');
        }
    }
    header("location:all_schedules.php?view=table");
}

function displayDates($date1, $date2, $format = 'm/d/Y' ) {
    $dates = array();
    $current = strtotime($date1);
    $date2 	 = strtotime($date2);
    $stepVal = '+1 day';
    while( $current <= $date2 ) {
        $dates[] = date($format, $current);
        $current = strtotime($stepVal, $current);
    }
    return $dates;
}

$OPEN_TIME = '09:00:00';
$CLOSE_TIME = '22:00:00';

$user_master_data = $account = $db->Execute("SELECT * FROM DOA_USER_MASTER WHERE PK_USER = ".$_SESSION['PK_USER']);
$PK_USER_MASTER_ARRAY = [];
$PK_ACCOUNT_MASTER_ARRAY = [];
while (!$user_master_data->EOF){
    $PK_USER_MASTER_ARRAY[] = $user_master_data->fields['PK_USER_MASTER'];
    $PK_ACCOUNT_MASTER_ARRAY[] = $user_master_data->fields['PK_ACCOUNT_MASTER'];
    $user_master_data->MoveNext();
}
$PK_USER_MASTERS = implode(',', $PK_USER_MASTER_ARRAY);
$PK_ACCOUNT_MASTERS = implode(',', $PK_ACCOUNT_MASTER_ARRAY);
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>

<link href='../assets/full_calendar_new/fullcalendar.min.css' rel='stylesheet' />
<link href='../assets/full_calendar_new/fullcalendar.print.css' rel='stylesheet' media='print' />
<link href='../assets/full_calendar_new/scheduler.min.css' rel='stylesheet' />

<!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">-->

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
</style>

<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <button class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="showCompleteListView(1)"><i class="ti-check"></i> Completed</button>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='add_schedule.php?id=<?php $_SESSION['PK_USER'] ?>'" ><i class="fa fa-plus-circle"></i> Create New</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div id="appointment_list_half" class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div id="appointment_list"  class="card-body table-responsive" style="display: none;">

                            </div>

                            <div id="completed_list"  class="card-body table-responsive" style="display: none;">

                            </div>

                            <div id="calender" class="card-body b-l calender-sidebar">
                                <div id="calendar"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="edit_appointment_half" class="col-6" style="display: none;">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-8">
                                    <h5 class="card-title">Edit Appointment</h5>
                                </div>
                                <div class="col-4">
                                    <a href="javascript:;" onclick="closeEditAppointment()" style="float: right;">Close</a>
                                </div>
                            </div>
                            <div class="card-body" id="appointment_details_div">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>



<?php require_once('../includes/footer.php');?>

<script src='../assets/full_calendar_new/moment.min.js'></script>
<script src='../assets/full_calendar_new/jquery.min.js'></script>
<script src='../assets/full_calendar_new/fullcalendar.min.js'></script>
<script src='../assets/full_calendar_new/scheduler.min.js'></script>
<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

<script>
    let view = '<?=$view?>';
    $(window).on('load', function () {
        if (view === 'list'){
            showListView();
        }else {
            showCalendarView();
        }
    })

    function showAppointmentEdit(info) {
        if (info.type === 'appointment') {
            $('#appointment_list_half').removeClass('col-12');
            $('#appointment_list_half').addClass('col-6');
            $.ajax({
                url: "ajax/get_appointment_details.php",
                type: "POST",
                data: {PK_APPOINTMENT_MASTER: info.id},
                async: false,
                cache: false,
                success: function (result) {
                    $('#appointment_details_div').html(result);
                    $('#edit_appointment_half').show();
                }
            });
        } else {
            if (info.type === 'special_appointment') {
                $('#appointment_list_half').removeClass('col-12');
                $('#appointment_list_half').addClass('col-6');
                $.ajax({
                    url: "ajax/get_special_appointment_details.php",
                    type: "POST",
                    data: {PK_APPOINTMENT_MASTER: info.id},
                    async: false,
                    cache: false,
                    success: function (result) {
                        $('#appointment_details_div').html(result);
                        $('#edit_appointment_half').show();
                        $('.multi_sumo_select').SumoSelect({placeholder: 'Select Service Provider', selectAll: true});

                        $('.datepicker-normal').datepicker({
                            format: 'mm/dd/yyyy',
                        });

                        $('.timepicker-normal').timepicker({
                            timeFormat: 'hh:mm p',
                        });
                    }
                });
            } else {
                if (info.type === 'group_class') {
                    $('#appointment_list_half').removeClass('col-12');
                    $('#appointment_list_half').addClass('col-6');
                    $.ajax({
                        url: "ajax/get_group_class_details.php",
                        type: "POST",
                        data: {PK_GROUP_CLASS: info.id},
                        async: false,
                        cache: false,
                        success: function (result) {
                            $('#appointment_details_div').html(result);
                            $('#edit_appointment_half').show();
                            $('.multi_sumo_select').SumoSelect({placeholder: 'Select Customer', selectAll: true, search:true, searchText:"Search Customer"});

                            $('.datepicker-normal').datepicker({
                                format: 'mm/dd/yyyy',
                            });

                            $('.timepicker-normal').timepicker({
                                timeFormat: 'hh:mm p',
                            });
                        }
                    });
                }
            }
        }
    }

    function closeEditAppointment() {
        $('#edit_appointment_half').hide();
        $('#appointment_list_half').removeClass('col-6');
        $('#appointment_list_half').addClass('col-12');
    }

    function showCalendarView() {
        showCalendarAppointment();
        $('#appointment_list').hide();
        $('#calender').show();
    }

    let finalArray = [];
    let defaultResources = [];
    function getAllCalendarData(){
        defaultResources = [
            <?php
            $service_provider_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND ACTIVE = 1 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
            $resourceIdArray = [];
            while (!$service_provider_data->EOF) { $resourceIdArray[] = $service_provider_data->fields['PK_USER'];?>
            {
                id: <?=$service_provider_data->fields['PK_USER']?>,
                title: '<?=$service_provider_data->fields['NAME'].' - 0'?>',
            },
            <?php $service_provider_data->MoveNext();
            } $resourceIdArray = json_encode($resourceIdArray)?>
        ];

        let specialAppointmentArray = [
            <?php $special_appointment_data = $db_account->Execute("SELECT DOA_SPECIAL_APPOINTMENT.*, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM `DOA_SPECIAL_APPOINTMENT` LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS WHERE DOA_SPECIAL_APPOINTMENT.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
            while (!$special_appointment_data->EOF) { ?>
            {
                id: <?=$special_appointment_data->fields['PK_SPECIAL_APPOINTMENT']?>,
                resourceId: 0,
                title: '<?=$special_appointment_data->fields['TITLE']?>',
                start: new Date(<?=date("Y",strtotime($special_appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($special_appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($special_appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($special_appointment_data->fields['START_TIME']))?>,<?=date("i",strtotime($special_appointment_data->fields['START_TIME']))?>,1,1),
                end: new Date(<?=date("Y",strtotime($special_appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($special_appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($special_appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($special_appointment_data->fields['END_TIME']))?>,<?=date("i",strtotime($special_appointment_data->fields['END_TIME']))?>,1,1),
                color: '<?=$special_appointment_data->fields['COLOR_CODE']?>',
                type: 'special_appointment',
            },
            <?php $special_appointment_data->MoveNext();
            } ?>
        ];

        let groupClassArray = [
            <?php
            $standing_data = $db_account->Execute("SELECT DOA_GROUP_CLASS.PK_GROUP_CLASS, DOA_GROUP_CLASS.DATE, DOA_GROUP_CLASS.START_TIME, DOA_GROUP_CLASS.END_TIME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_GROUP_CLASS.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_GROUP_CLASS LEFT JOIN DOA_SERVICE_MASTER ON DOA_GROUP_CLASS.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_GROUP_CLASS.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS LEFT JOIN DOA_SERVICE_CODE ON DOA_GROUP_CLASS.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_GROUP_CLASS.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_GROUP_CLASS.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
            while (!$standing_data->EOF) { ?>
            {
                id: <?=$standing_data->fields['PK_GROUP_CLASS']?>,
                resourceId: 0,
                title: '<?=$standing_data->fields['SERVICE_NAME'].' - '.$standing_data->fields['SERVICE_CODE']?>',
                start: new Date(<?=date("Y",strtotime($standing_data->fields['DATE']))?>,<?=intval((date("m",strtotime($standing_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($standing_data->fields['DATE'])))?>,<?=date("H",strtotime($standing_data->fields['START_TIME']))?>,<?=date("i",strtotime($standing_data->fields['START_TIME']))?>,1,1),
                end: new Date(<?=date("Y",strtotime($standing_data->fields['DATE']))?>,<?=intval((date("m",strtotime($standing_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($standing_data->fields['DATE'])))?>,<?=date("H",strtotime($standing_data->fields['END_TIME']))?>,<?=date("i",strtotime($standing_data->fields['END_TIME']))?>,1,1),
                color: '<?=$standing_data->fields['COLOR_CODE']?>',
                type: 'group_class',
            },
            <?php $standing_data->MoveNext();
            } ?>
        ];

        let appointmentArray = [
            <?php
            $appointment_data = $db_account->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_APPOINTMENT_MASTER.SERIAL_NUMBER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.IS_PAID, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = $master_database.DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON $master_database.DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER INNER JOIN $master_database.DOA_USER_LOCATION AS DOA_USER_LOCATION ON SERVICE_PROVIDER.PK_USER = $master_database.DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.CUSTOMER_ID IN (".$PK_USER_MASTERS.")");
            while (!$appointment_data->EOF) { ?>
            {
                id: <?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>,
                resourceId: <?=$appointment_data->fields['SERVICE_PROVIDER_ID']?>,
                title: '<?=$appointment_data->fields['CUSTOMER_NAME'].' ('.$appointment_data->fields['SERVICE_NAME'].'-'.$appointment_data->fields['SERVICE_CODE'].') '.'\n'.$appointment_data->fields['ENROLLMENT_ID'].' - '.$appointment_data->fields['SERIAL_NUMBER'].(($appointment_data->fields['IS_PAID'] == 0)?' (Unpaid)':' (Paid)')?>',
                start: new Date(<?=date("Y",strtotime($appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($appointment_data->fields['START_TIME']))?>,<?=date("i",strtotime($appointment_data->fields['START_TIME']))?>,1,1),
                end: new Date(<?=date("Y",strtotime($appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($appointment_data->fields['END_TIME']))?>,<?=date("i",strtotime($appointment_data->fields['END_TIME']))?>,1,1),
                color: '<?=$appointment_data->fields['COLOR_CODE']?>',
                type: 'appointment',
            },
            <?php $appointment_data->MoveNext();
            } ?>
        ];

        let eventArray = [
            <?php $event_data = $db_account->Execute("SELECT DOA_EVENT.*, DOA_EVENT_TYPE.EVENT_TYPE, DOA_EVENT_TYPE.COLOR_CODE FROM DOA_EVENT LEFT JOIN DOA_EVENT_TYPE ON DOA_EVENT.PK_EVENT_TYPE = DOA_EVENT_TYPE.PK_EVENT_TYPE WHERE DOA_EVENT.ACTIVE = 1 AND DOA_EVENT.SHARE_WITH_CUSTOMERS = 1 AND DOA_EVENT.PK_ACCOUNT_MASTER IN (".$PK_ACCOUNT_MASTERS.")");
            while (!$event_data->EOF) {
            if (isset($event_data->fields['END_DATE'])) {
                $END_DATE = date('Y-m-d', strtotime($event_data->fields['END_DATE'].'+1 day'));
            }else {
                $END_DATE = ($event_data->fields['END_DATE'] == '0000-00-00') ? $event_data->fields['START_DATE'] : $event_data->fields['END_DATE'];
            }
            $END_TIME = ($event_data->fields['END_TIME'] == '00:00:00')?$event_data->fields['START_TIME']:$event_data->fields['END_TIME'];
            $open_close_time_diff = (strtotime($CLOSE_TIME) - strtotime($OPEN_TIME)) - 1800;
            $start_end_time_diff = strtotime($END_DATE.' '.$END_TIME) - strtotime($event_data->fields['START_DATE'].' '.$event_data->fields['START_TIME']);?>
            {
                id: <?=$event_data->fields['PK_EVENT']?>,
                resourceIds: <?=$resourceIdArray?>,
                title: '<?=$event_data->fields['HEADER']?>',
                start: new Date(<?=date("Y",strtotime($event_data->fields['START_DATE']))?>,<?=intval((date("m",strtotime($event_data->fields['START_DATE'])) - 1))?>,<?=intval(date("d",strtotime($event_data->fields['START_DATE'])))?>,<?=date("H",strtotime($event_data->fields['START_TIME']))?>,<?=date("i",strtotime($event_data->fields['START_TIME']))?>,1,1),
                end: new Date(<?=date("Y",strtotime($END_DATE))?>,<?=intval((date("m",strtotime($END_DATE)) - 1))?>,<?=intval(date("d",strtotime($END_DATE)))?>,<?=date("H",strtotime($END_TIME))?>,<?=date("i",strtotime($END_TIME))?>,1,1),
                color: '<?=$event_data->fields['COLOR_CODE']?>',
                type: 'event',
                allDay: '<?=($start_end_time_diff >= $open_close_time_diff)?>'
            },
            <?php $event_data->MoveNext();
            } ?>
        ];
        finalArray = appointmentArray.concat(eventArray).concat(specialAppointmentArray).concat(groupClassArray);
        console.log(finalArray);
    }

    function showCalendarAppointment() {
        getAllCalendarData();
        let open_time = '<?=$OPEN_TIME?>';
        let close_time = '<?=$CLOSE_TIME?>';
        let clickCount = 0;
        $('#calendar').fullCalendar({
            schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
            defaultView: 'agendaDay',
            minTime: open_time,
            maxTime: close_time,
            slotDuration: '00:30:00',
            slotLabelInterval: 30,
            slotMinutes: 30,
            //defaultDate: '2016-01-07',
            editable: true,
            selectable: true,
            eventLimit: true, // allow "more" link when too many events
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'agendaDay,agendaTwoDay,agendaWeek,month'
            },
            views: {
                agendaTwoDay: {
                    type: 'agenda',
                    duration: { days: 2 },

                    // views that are more than a day will NOT do this behavior by default
                    // so, we need to explicitly enable it
                    groupByResource: true

                    //// uncomment this line to group by day FIRST with resources underneath
                    //groupByDateAndResource: true
                },
                day: {
                    titleFormat: 'dddd, MMMM Do YYYY'
                }
            },
            /*viewRender: function(view) {
                if(view.type == 'agendaDay') {
                    $('#calendar').fullCalendar( 'removeEventSource', ev1 );
                    $('#calendar').fullCalendar( 'addEventSource', ev2 );
                    return;
                } else {
                    $('#calendar').fullCalendar( 'removeEventSource', ev2 );
                    $('#calendar').fullCalendar( 'addEventSource', ev1 );
                    return;
                }
            },*/

            //// uncomment this line to hide the all-day slot
            //allDaySlot: false,

            resources: defaultResources /*[
                { id: 'a', title: 'Room A' },
                { id: 'b', title: 'Room B', eventColor: 'green' },
                { id: 'c', title: 'Room C', eventColor: 'orange' },
                { id: 'd', title: 'Room D', eventColor: 'red' }
            ]*/,
            events: finalArray,

            eventClick: function(info) {
                showAppointmentEdit(info);
                // window.location.href = "add_schedule.php?id="+info.id;
                //viewAppointmentDetails(info);
            },

            select: function(start, end, jsEvent, view, resource) {
                console.log(
                    'select',
                    start.format(),
                    end.format(),
                    resource ? resource.id : '(no resource)'
                );
            },
            dayClick: function(date, jsEvent, view, resource) {
                clickCount++;
                let singleClickTimer;
                if (clickCount === 1) {
                    singleClickTimer = setTimeout(function () {
                        clickCount = 0;
                    }, 400);
                } else if (clickCount === 2) {
                    clearTimeout(singleClickTimer);
                    clickCount = 0;
                    //window.location.href = "add_schedule.php";
                    openModel();
                }
                console.log(
                    'dayClick',
                    date.format(),
                    resource ? resource.id : '(no resource)'
                );
            }
        });


        $('.fc-body').css({"overflow-y":"scroll", "height":"600px", "display":"block"});

        $('.fc-agendaDay-button').click(function () {
            $('.fc-body').css({"overflow-y":"scroll", "height":"600px", "display":"block"});
        });
        $('.fc-agendaTwoDay-button').click(function () {
            $('.fc-body').css({"overflow-y":"scroll", "height":"600px", "display":"block"});
        });
        $('.fc-agendaWeek-button').click(function () {
            $('.fc-body').css({"overflow-y":"scroll", "height":"600px", "display":"block"});
        });
        $('.fc-month-button').click(function () {
            $('.fc-body').css({"overflow-y":"", "height":"", "display":""});
        });
    }

    function showListView(page) {
        let search_text = $('#search_text').val();
        let START_DATE = $('#START_DATE').val();
        let END_DATE = $('#END_DATE').val();
        $.ajax({
            url: "pagination/appointment.php",
            type: "GET",
            data: {search_text:search_text, page:page, START_DATE:START_DATE, END_DATE:END_DATE},
            async: false,
            cache: false,
            beforeSend: function (){
                $('.preloader').show();
            },
            success: function (result) {
                $('#appointment_list').html(result);
            },
            complete: function () {
                $('.preloader').hide();
            }
        });
        window.scrollTo(0,0);
        $('#appointment_list').show();
        $('#calender').hide();
    }



































    function editpage(id){
        window.location.href = "add_schedule.php?id="+id;
    }

    function confirmComplete(anchor)
    {
        let conf = confirm("Do you want to mark this appointment as completed?");
        if(conf)
            window.location=anchor.attr("href");
    }
</script>
</body>
</html>
