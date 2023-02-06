<?php
require_once('../global/config.php');
$title = "All Appointments";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['id']) && !empty($_GET['action'])){
    if ($_GET['action'] == 'complete'){
        $db->Execute("UPDATE DOA_APPOINTMENT_MASTER SET PK_APPOINTMENT_STATUS = 2 WHERE PK_APPOINTMENT_MASTER = ".$_GET['id']);
        header("location:all_schedules.php?view=list");
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
    $session_cost = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_SERVICE_MASTER = '$_POST[PK_SERVICE_MASTER]' AND PK_SERVICE_CODE = '$_POST[PK_SERVICE_CODE]'");
    $price_per_session = $session_cost->fields['PRICE_PER_SESSION'];
    if(empty($_POST['PK_APPOINTMENT_MASTER'])){
        $_POST['PK_APPOINTMENT_STATUS'] = 1;
        $_POST['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $_POST['ACTIVE'] = 1;
        $_POST['CREATED_BY']  = $_SESSION['PK_USER'];
        $_POST['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_APPOINTMENT_MASTER', $_POST, 'insert');
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
        db_perform('DOA_APPOINTMENT_MASTER', $_POST, 'update'," PK_APPOINTMENT_MASTER =  '$_POST[PK_APPOINTMENT_MASTER]'");

        if ($_POST['PK_APPOINTMENT_STATUS'] == 2 || ($_POST['PK_APPOINTMENT_STATUS'] == 4 && $_POST['NO_SHOW'] == 'Charge')) {
            $enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '$_POST[PK_ENROLLMENT_MASTER]'");
            if ($enrollment_balance->RecordCount() > 0) {
                $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_USED'] = $enrollment_balance->fields['TOTAL_BALANCE_USED'] + $price_per_session;
                $ENROLLMENT_BALANCE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                $ENROLLMENT_BALANCE_DATA['EDITED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
            }
        }
    }

    rearrangeSerialNumber($_POST['PK_ENROLLMENT_MASTER'], $price_per_session);

    header("location:all_schedules.php");
}

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveSpecialAppointmentData'){
    $SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
    $SPECIAL_APPOINTMENT_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
    $SPECIAL_APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
    $SPECIAL_APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
    $SPECIAL_APPOINTMENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
    $PK_SPECIAL_APPOINTMENT = $_POST['PK_SPECIAL_APPOINTMENT'];

    $SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
    $SPECIAL_APPOINTMENT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
    $SPECIAL_APPOINTMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
    db_perform('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'update'," PK_SPECIAL_APPOINTMENT =  '$PK_SPECIAL_APPOINTMENT'");

    if (isset($_POST['PK_USER'])) {
        $db->Execute("DELETE FROM `DOA_SPECIAL_APPOINTMENT_USER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
        for ($i = 0; $i < count($_POST['PK_USER']); $i++) {
            $SPECIAL_APPOINTMENT_USER['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT;
            $SPECIAL_APPOINTMENT_USER['PK_USER'] = $_POST['PK_USER'][$i];
            db_perform('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'insert');
        }
    }
    header("location:all_schedules.php");
}

function rearrangeSerialNumber($PK_ENROLLMENT_MASTER, $price_per_session){
    global $db;
    $appointment_data = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_MASTER` WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' ORDER BY DATE ASC");
    $total_bill_and_paid = $db->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$PK_ENROLLMENT_MASTER);
    $total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
    $total_paid_appointment = intval($total_paid/$price_per_session);
    $i = 1;
    while (!$appointment_data->EOF){
        $UPDATE_DATA['SERIAL_NUMBER'] = $i;
        if ($i <= $total_paid_appointment){
            $UPDATE_DATA['IS_PAID'] = 1;
        } else {
            $UPDATE_DATA['IS_PAID'] = 0;
        }
        db_perform('DOA_APPOINTMENT_MASTER', $UPDATE_DATA, 'update'," PK_APPOINTMENT_MASTER =  ".$appointment_data->fields['PK_APPOINTMENT_MASTER']);
        $appointment_data->MoveNext();
        $i++;
    }
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

$location_operational_hour = $db->Execute("SELECT DOA_OPERATIONAL_HOUR.OPEN_TIME, DOA_OPERATIONAL_HOUR.CLOSE_TIME FROM DOA_OPERATIONAL_HOUR LEFT JOIN DOA_LOCATION ON DOA_OPERATIONAL_HOUR.PK_LOCATION = DOA_LOCATION.PK_LOCATION WHERE DOA_LOCATION.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND DOA_OPERATIONAL_HOUR.CLOSED = 0 ORDER BY DOA_LOCATION.PK_LOCATION LIMIT 1");
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

    .SumoSelect {
        width: 100%;
    }
</style>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>

<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid">
            <div class="row page-titles navbar-fixed-top">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='all_special_appointment.php'" ><i class="fa fa-plus-circle"></i> INT APP</button>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='add_schedule.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='add_multiple_appointment.php'" ><i class="fa fa-plus-circle"></i> Standing</button>
                        <button class="btn btn-info waves-effect waves-light m-l-10 text-white" onclick="showListView()" style="float:right;"><i class="ti-list"></i> List</button>
                        <button class="btn btn-info waves-effect waves-light m-l-10 text-white" onclick="showCalendarView()" style="float: right;"><i class="ti-calendar"></i> Calendar</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div id="appointment_list_half" class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="card-title"><?=$title?></h5>
                                </div>
                                <!-- <div class="col-4">
                                    <button class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="showCalendarView()" style="float: right;"><i class="ti-calendar"></i> Calendar</button>
                                </div> -->
                            </div>

                            <div id="list"  class="card-body" style="display: none;">
                                <table id="myTable" class="table table-striped border">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Customer</th>
                                            <th>Enrollment ID</th>
                                            <th>Service</th>
                                            <th>Service Code</th>
                                            <th><?=$service_provider_title?></th>
                                            <th>Day</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Paid</th>
                                            <th style="text-align: center;">Completed</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    if ($DEFAULT_LOCATION_ID > 0){
                                        $appointment_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, DOA_APPOINTMENT_MASTER.IS_PAID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER LEFT JOIN DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER INNER JOIN DOA_USER_LOCATION ON SERVICE_PROVIDER.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_USER_LOCATION.PK_LOCATION = '$DEFAULT_LOCATION_ID' AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC");
                                    } else {
                                        $appointment_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, DOA_APPOINTMENT_MASTER.IS_PAID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER LEFT JOIN DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC");
                                    }
                                    while (!$appointment_data->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['CUSTOMER_NAME']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['ENROLLMENT_ID']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_NAME']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_CODE']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_PROVIDER_NAME']?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('l', strtotime($appointment_data->fields['DATE']))?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
                                            <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=($appointment_data->fields['IS_PAID'] == 0)?'Unpaid':'Paid'?></td>
                                            <td style="text-align: center;">
                                                <?php if ($appointment_data->fields['PK_APPOINTMENT_STATUS'] == 2){ ?>
                                                    <i class="fa fa-check-circle" style="font-size:25px;color:#35e235;"></i>
                                                <?php } else { ?>
                                                    <a href="all_schedules.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>&action=complete" onclick='javascript:confirmComplete($(this));return false;'><i class="fa fa-check-circle" style="font-size:25px;color:#a9b7a9;"></i></a>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <a href="add_schedule.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php /*if($appointment_data->fields['ACTIVE']==1){ */?><!--
                                                    <span class="active-box-green"></span>
                                                <?php /*} else{ */?>
                                                    <span class="active-box-red"></span>
                                                --><?php /*} */?>
                                            </td>
                                        </tr>
                                        <?php $appointment_data->MoveNext();
                                        $i++; } ?>
                                    </tbody>
                                </table>
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

<!--<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>-->


<script>
    var view = '<?=$view?>';

    $(window).on('load', function () {
        if (view === 'list'){
            showListView();
        }else {
            showCalendarView();
        }
    })

    function showAppointmentEdit(info) {
        if (info.isSpecial === 0) {
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
            if (info.isSpecial === 1) {
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
                        $('.multi_sumo_select').SumoSelect({placeholder: 'Select Location', selectAll: true});
                    }
                });
            }
        }
    }

    function closeEditAppointment() {
        $('#edit_appointment_half').hide();
        $('#appointment_list_half').removeClass('col-6');
        $('#appointment_list_half').addClass('col-12');
    }

    var defaultResources = [
        <?php
        if ($DEFAULT_LOCATION_ID > 0){
            $service_provider_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USERS.PK_ROLES = 5 AND ACTIVE = 1 AND DOA_USER_LOCATION.PK_LOCATION = '$DEFAULT_LOCATION_ID' AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
        } else {
            $service_provider_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS WHERE DOA_USERS.PK_ROLES = 5 AND ACTIVE = 1 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
        }

        while (!$service_provider_data->EOF) { ?>
        {
            id: <?=$service_provider_data->fields['PK_USER']?>,
            title: '<?=$service_provider_data->fields['NAME'].' - 0'?>',
        },
        <?php $service_provider_data->MoveNext();
        } ?>
    ];

    var appointmentArray = [
        <?php
        if ($DEFAULT_LOCATION_ID > 0){
            $appointment_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_APPOINTMENT_MASTER.SERIAL_NUMBER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.IS_PAID, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER LEFT JOIN DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER INNER JOIN DOA_USER_LOCATION ON SERVICE_PROVIDER.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_USER_LOCATION.PK_LOCATION = '$DEFAULT_LOCATION_ID' AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        } else {
            $appointment_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_APPOINTMENT_MASTER.SERIAL_NUMBER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.IS_PAID, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER LEFT JOIN DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        }
        while (!$appointment_data->EOF) { ?>
        {
            id: <?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>,
            resourceId: <?=$appointment_data->fields['SERVICE_PROVIDER_ID']?>,
            title: '<?=$appointment_data->fields['CUSTOMER_NAME'].' ('.$appointment_data->fields['SERVICE_NAME'].'-'.$appointment_data->fields['SERVICE_CODE'].') '.'\n'.$appointment_data->fields['ENROLLMENT_ID'].' - '.$appointment_data->fields['SERIAL_NUMBER'].(($appointment_data->fields['IS_PAID'] == 0)?' (Unpaid)':' (Paid)')?>',
            start: new Date(<?=date("Y",strtotime($appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($appointment_data->fields['START_TIME']))?>,<?=date("i",strtotime($appointment_data->fields['START_TIME']))?>,1,1),
            end: new Date(<?=date("Y",strtotime($appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($appointment_data->fields['END_TIME']))?>,<?=date("i",strtotime($appointment_data->fields['END_TIME']))?>,1,1),
            color: '<?=$appointment_data->fields['COLOR_CODE']?>',
            isSpecial: 0,
        },
        <?php $appointment_data->MoveNext();
        } ?>
    ];

    var specialAppointmentArray = [
        <?php $special_appointment_data = $db->Execute("SELECT DOA_SPECIAL_APPOINTMENT.*, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM `DOA_SPECIAL_APPOINTMENT` LEFT JOIN DOA_APPOINTMENT_STATUS ON DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS WHERE DOA_SPECIAL_APPOINTMENT.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        while (!$special_appointment_data->EOF) { ?>
        {
            id: <?=$special_appointment_data->fields['PK_SPECIAL_APPOINTMENT']?>,
            resourceId: 0,
            title: '<?=$special_appointment_data->fields['TITLE']?>',
            start: new Date(<?=date("Y",strtotime($special_appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($special_appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($special_appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($special_appointment_data->fields['START_TIME']))?>,<?=date("i",strtotime($special_appointment_data->fields['START_TIME']))?>,1,1),
            end: new Date(<?=date("Y",strtotime($special_appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($special_appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($special_appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($special_appointment_data->fields['END_TIME']))?>,<?=date("i",strtotime($special_appointment_data->fields['END_TIME']))?>,1,1),
            color: '<?=$special_appointment_data->fields['COLOR_CODE']?>',
            isSpecial: 1,
        },
        <?php $special_appointment_data->MoveNext();
        } ?>
    ];

    var eventArray = [
        <?php $event_data = $db->Execute("SELECT DOA_EVENT.*, DOA_EVENT_TYPE.EVENT_TYPE, DOA_EVENT_TYPE.COLOR_CODE FROM DOA_EVENT LEFT JOIN DOA_EVENT_TYPE ON DOA_EVENT.PK_EVENT_TYPE = DOA_EVENT_TYPE.PK_EVENT_TYPE WHERE DOA_EVENT.ACTIVE = 1 AND DOA_EVENT.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        while (!$event_data->EOF) {
        $END_DATE = ($event_data->fields['END_DATE'] == '0000-00-00')?$event_data->fields['START_DATE']:$event_data->fields['END_DATE'];
        $END_TIME = ($event_data->fields['END_TIME'] == '00:00:00')?$event_data->fields['START_TIME']:$event_data->fields['END_TIME']; ?>
        {
            id: <?=$event_data->fields['PK_EVENT']?>,
            resourceId: 0,
            title: '<?=$event_data->fields['HEADER']?>',
            start: new Date(<?=date("Y",strtotime($event_data->fields['START_DATE']))?>,<?=intval((date("m",strtotime($event_data->fields['START_DATE'])) - 1))?>,<?=intval(date("d",strtotime($event_data->fields['START_DATE'])))?>,<?=date("H",strtotime($event_data->fields['START_TIME']))?>,<?=date("i",strtotime($event_data->fields['START_TIME']))?>,1,1),
            end: new Date(<?=date("Y",strtotime($END_DATE))?>,<?=intval((date("m",strtotime($END_DATE)) - 1))?>,<?=intval(date("d",strtotime($END_DATE)))?>,<?=date("H",strtotime($END_TIME))?>,<?=date("i",strtotime($END_TIME))?>,1,1),
            color: '<?=$event_data->fields['COLOR_CODE']?>',
            isSpecial: 2,
        },
        <?php $event_data->MoveNext();
        } ?>
    ];

    var finalArray = appointmentArray.concat(eventArray).concat(specialAppointmentArray);

    /*jQuery(document).ready(function($) {
        defaultEvents =
        console.log(defaultEvents);
        $('#calendar').FullCalendar({
            slotDuration: '00:15:00', /!* If we want to split day time each 15minutes *!/
            minTime: '00:00:00',
            maxTime: '24:00:00',
            defaultView: 'month',
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            events: defaultEvents,
            displayEventTime: true,
            droppable: false,
            eventLimit: false,
            selectable: true,
            editable: true,
        });
    });*/
    document.addEventListener('DOMContentLoaded', function() {
        let open_time = '<?=$location_operational_hour->fields['OPEN_TIME']?>';
        let close_time = '<?=$location_operational_hour->fields['CLOSE_TIME']?>';
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

            //// uncomment this line to hide the all-day slot
            //allDaySlot: false,

            resources: defaultResources /*[
                { id: 'a', title: 'Room A' },
                { id: 'b', title: 'Room B', eventColor: 'green' },
                { id: 'c', title: 'Room C', eventColor: 'orange' },
                { id: 'd', title: 'Room D', eventColor: 'red' }
            ]*/,
            events: finalArray /*[
                { id: '1', resourceId: 'a', start: '2016-01-06', end: '2016-01-08', title: 'event 1' },
                { id: '2', resourceId: 'a', start: '2016-01-07T09:00:00', end: '2016-01-07T14:00:00', title: 'event 2' },
                { id: '3', resourceId: 'b', start: '2016-01-07T12:00:00', end: '2016-01-08T06:00:00', title: 'event 3' },
                { id: '4', resourceId: 'c', start: '2016-01-07T07:30:00', end: '2016-01-07T09:30:00', title: 'event 4' },
                { id: '5', resourceId: 'd', start: '2016-01-07T10:00:00', end: '2016-01-07T15:00:00', title: 'event 5' }
            ]*/,

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
                    window.location.href = "add_schedule.php";
                }
                console.log(
                    'dayClick',
                    date.format(),
                    resource ? resource.id : '(no resource)'
                );
            }
        });


        $('.fc-body').css({"overflow-y":"scroll", "height":"400px", "display":"block"});

        $('.fc-agendaDay-button').click(function () {
            $('.fc-body').css({"overflow-y":"scroll", "height":"400px", "display":"block"});
        });
        $('.fc-agendaTwoDay-button').click(function () {
            $('.fc-body').css({"overflow-y":"scroll", "height":"400px", "display":"block"});
        });
        $('.fc-agendaWeek-button').click(function () {
            $('.fc-body').css({"overflow-y":"scroll", "height":"400px", "display":"block"});
        });
        $('.fc-month-button').click(function () {
            $('.fc-body').css({"overflow-y":"", "height":"", "display":""});
        });

        getServiceProviderCount();
        $('.fc-prev-button').click(function () {
            getServiceProviderCount();
        });
        $('.fc-next-button').click(function () {
            getServiceProviderCount();
        });
        $('.fc-today-button').click(function () {
            getServiceProviderCount();
        });

        /*var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            //initialDate: '2022-08-01',
            initialView: 'dayGridMonth',
            slotDuration: '00:15:00',
            slotLabelInterval: 15,
            slotMinutes: 15,
            nowIndicator: true,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            navLinks: true, // can click day/week names to navigate views
            editable: true,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true, // allow "more" link when too many events
            events: defaultEvents,
        });

        calendar.render();*/
    });

    function getServiceProviderCount() {
        let currentDate = new Date($('#calendar').fullCalendar('getDate'));
        let day = currentDate.getDate();
        let month = currentDate.getMonth() + 1;
        let year = currentDate.getFullYear();

        let all_service_provider = $('.fc-resource-cell').map(function(){
            return $(this).data('resource-id');
        }).get();

        console.log(currentDate, all_service_provider);

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: "POST",
            data: {FUNCTION_NAME:'getServiceProviderCount', currentDate:year+'-'+month+'-'+day, all_service_provider:all_service_provider},
            async: false,
            cache: false,
            success: function (result) {
                let appointment_data = JSON.parse(result);
                for(let i=0; i<appointment_data.length; i++) {
                    $('.fc-resource-cell[data-resource-id="'+appointment_data[i].SERVICE_PROVIDER_ID+'"]').text(appointment_data[i].SERVICE_PROVIDER_NAME+' - '+appointment_data[i].APPOINTMENT_COUNT);
                }
            }
        });
    }


    /*function viewAppointmentDetails(info) {
        $.ajax({
            url: "ajax/get_appointment_details.php",
            type: "POST",
            data: {PK_APPOINTMENT_MASTER: info.id},
            async: false,
            cache: false,
            success: function (result) {
                $('#appointment_details_div').html(result);
                $('#model-button').trigger('click');
            }
        });
    }*/

    function showListView() {
        $('#list').show();
        $('#calender').hide();
    }

    function showCalendarView() {
        $('#list').hide();
        $('#calender').show();
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