<?php
require_once('../global/config.php');
global $db;
global $db_account;

$title = "Edit To-Do";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
    $SPECIAL_APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
    $SPECIAL_APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
    $SPECIAL_APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
    $SPECIAL_APPOINTMENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
    $PK_SPECIAL_APPOINTMENT = $_POST['PK_SPECIAL_APPOINTMENT'];

    $SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
    $SPECIAL_APPOINTMENT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
    $SPECIAL_APPOINTMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");

    if (isset($_POST['STANDING_ID'])) {
        db_perform_account('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'update', " STANDING_ID =  '$_POST[STANDING_ID]'");
    } else {
        $SPECIAL_APPOINTMENT_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
        db_perform_account('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'update'," PK_SPECIAL_APPOINTMENT =  '$PK_SPECIAL_APPOINTMENT'");
    }

    if (isset($_POST['PK_USER'])) {
        $db_account->Execute("DELETE FROM `DOA_SPECIAL_APPOINTMENT_USER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
        for ($i = 0; $i < count($_POST['PK_USER']); $i++) {
            $SPECIAL_APPOINTMENT_USER['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT;
            $SPECIAL_APPOINTMENT_USER['PK_USER'] = $_POST['PK_USER'][$i];
            db_perform_account('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'insert');
        }
    }

    if (isset($_POST['PK_USER_MASTER'])) {
        $db_account->Execute("DELETE FROM `DOA_SPECIAL_APPOINTMENT_CUSTOMER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
        for ($i = 0; $i < count($_POST['PK_USER_MASTER']); $i++) {
            $SPECIAL_APPOINTMENT_CUSTOMER_DATA['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT;
            $SPECIAL_APPOINTMENT_CUSTOMER_DATA['PK_USER_MASTER'] = $_POST['PK_USER_MASTER'][$i];
            db_perform_account('DOA_SPECIAL_APPOINTMENT_CUSTOMER', $SPECIAL_APPOINTMENT_CUSTOMER_DATA, 'insert');
        }
    }

    header("location:to_do_list.php");
}

$res = $db_account->Execute("SELECT DOA_SPECIAL_APPOINTMENT.*  FROM DOA_SPECIAL_APPOINTMENT WHERE PK_SPECIAL_APPOINTMENT = '$_GET[id]'");

$PK_SPECIAL_APPOINTMENT = $res->fields['PK_SPECIAL_APPOINTMENT'];
$STANDING_ID = $res->fields['STANDING_ID'];
$TITLE = $res->fields['TITLE'];
$DATE = $res->fields['DATE'];
$START_TIME = $res->fields['START_TIME'];
$END_TIME = $res->fields['END_TIME'];
$DESCRIPTION = $res->fields['DESCRIPTION'];
$PK_SCHEDULING_CODE = $res->fields['PK_SCHEDULING_CODE'];
$PK_APPOINTMENT_STATUS = $res->fields['PK_APPOINTMENT_STATUS'];
$ACTIVE = $res->fields['ACTIVE'];


?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    .ck-editor__editable_inline {
        min-height: 300px;
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
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="to_do_list.php">To-Do List</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card p-20">
                        <div class="card-body">
                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="FUNCTION_NAME" value="saveSpecialAppointmentData">
                                <input type="hidden" name="PK_SPECIAL_APPOINTMENT" class="PK_SPECIAL_APPOINTMENT" value="<?=$PK_SPECIAL_APPOINTMENT?>">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Title</label>
                                                    <input type="text" id="TITLE" name="TITLE" class="form-control" required value="<?php echo $TITLE?>">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Date</label>
                                                    <input type="text" id="DATE" name="DATE" class="form-control datepicker-normal" required value="<?php echo ($DATE)?date('m/d/Y', strtotime($DATE)):''?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Start Time</label>
                                                    <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker" required value="<?php echo ($START_TIME)?date('h:i A', strtotime($START_TIME)):''?>">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">End Time</label>
                                                    <input type="text" id="END_TIME" name="END_TIME" class="form-control time-picker" required value="<?php echo ($END_TIME)?date('h:i A', strtotime($END_TIME)):''?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="form-control" id="DESCRIPTION" name="DESCRIPTION" rows="3"><?=$DESCRIPTION?></textarea>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Customer</label>
                                                <div style="margin-bottom: 15px; margin-top: 10px; width: 480px;">
                                                    <select class="multi_sumo_select" name="PK_USER_MASTER[]" multiple>
                                                        <?php
                                                        $selected_customer = [];
                                                        $selected_customer_row = $db_account->Execute("SELECT DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_USER_MASTER FROM DOA_SPECIAL_APPOINTMENT_CUSTOMER LEFT JOIN $master_database.DOA_USER_MASTER ON DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_USER_MASTER = $master_database.DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_SPECIAL_APPOINTMENT = '$PK_SPECIAL_APPOINTMENT'");
                                                        while (!$selected_customer_row->EOF) {
                                                            $selected_customer[] = $selected_customer_row->fields['PK_USER_MASTER'];
                                                            $selected_customer_row->MoveNext();
                                                        }

                                                        $row = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_USER_MASTER'];?>" <?=in_array($row->fields['PK_USER_MASTER'], $selected_customer)?"selected":""?>><?=$row->fields['NAME']?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label"><?=$service_provider_title?></label>
                                                <div class="col-md-12" style="margin-bottom: 15px; margin-top: 10px;">
                                                    <select class="multi_sumo_select" name="PK_USER[]" multiple>
                                                        <?php
                                                        $selected_service_provider = [];
                                                        $selected_service_provider_row = $db_account->Execute("SELECT `PK_USER` FROM `DOA_SPECIAL_APPOINTMENT_USER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
                                                        while (!$selected_service_provider_row->EOF) {
                                                            $selected_service_provider[] = $selected_service_provider_row->fields['PK_USER'];
                                                            $selected_service_provider_row->MoveNext();
                                                        }

                                                        $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES IN(5) AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_USER'];?>" <?=in_array($row->fields['PK_USER'], $selected_service_provider)?"selected":""?>><?=$row->fields['NAME']?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Scheduling Code</label>
                                                <div class="col-md-12" style="margin-bottom: 15px; margin-top: 10px;">
                                                    <select class="form-control" name="PK_SCHEDULING_CODE" onchange="calculateEndTime(this)">
                                                        <?php
                                                        $booking_row = $db_account->Execute("SELECT DOA_SCHEDULING_CODE.`PK_SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_NAME`, DOA_SCHEDULING_CODE.`DURATION` FROM `DOA_SCHEDULING_CODE` WHERE DOA_SCHEDULING_CODE.TO_DOS = 1 AND DOA_SCHEDULING_CODE.`ACTIVE` = 1");
                                                        while (!$booking_row->EOF) { ?>
                                                            <option value="<?php echo $booking_row->fields['PK_SCHEDULING_CODE'];?>" data-duration="<?php echo $booking_row->fields['DURATION'];?>" data-scheduling_name="<?php echo $booking_row->fields['SCHEDULING_NAME']?>" data-is_default="<?php echo $booking_row->fields['IS_DEFAULT']?>" <?=($PK_SCHEDULING_CODE == $booking_row->fields['PK_SCHEDULING_CODE']) ? "selected" : ""?>><?=$booking_row->fields['SCHEDULING_NAME'].' ('.$booking_row->fields['SCHEDULING_CODE'].')'?></option>
                                                            <?php $booking_row->MoveNext(); } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Status:</label>
                                                    <select class="form-control" name="PK_APPOINTMENT_STATUS" id="PK_APPOINTMENT_STATUS">
                                                        <option value="">Select Status</option>
                                                        <?php
                                                        $selected_status = '';
                                                        $row = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_STATUS` WHERE `ACTIVE` = 1");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS'];?>" <?=($PK_APPOINTMENT_STATUS==$row->fields['PK_APPOINTMENT_STATUS'])?'selected':''?>><?=$row->fields['APPOINTMENT_STATUS']?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                    </select>
                                                    <p id="appointment_status"><?=$selected_status?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label><input type="checkbox" name="STANDING_ID" value="<?=$STANDING_ID?>"> All Standing Session Details Will Be Changed</label>
                                </div>

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                <a onclick="closeEditAppointment()" class="btn btn-inverse waves-effect waves-light">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>

</body>

<script>
    $('.multi_sumo_select').SumoSelect({placeholder: 'Select Customer', selectAll: true, search: true, searchText: 'Search...'});
</script>


<script type="text/javascript">
    $('#PK_USER_MASTER').SumoSelect({placeholder: 'Select Customer', selectAll: true, search: true, searchText: 'Search...'});

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $('#START_TIME').timepicker({
        timeFormat: 'hh:mm p',
        change: function () {
            calculateEndTime();
        },
    });

    $('#END_TIME').timepicker({
        timeFormat: 'hh:mm p',
    });

    $('.DAYS').on('change', function(){
        if ($('.DAYS').is(':checked')){
            $("input[name='OCCURRENCE'][value='WEEKLY']").prop('checked', true);
            $('.occurrence_div').addClass('disable-div');
        } else {
            $("input[name='OCCURRENCE'][value='WEEKLY']").prop('checked', false);
            $('.occurrence_div').removeClass('disable-div');
        }
    });

    $('.multi_sumo_select').SumoSelect({placeholder: 'Select <?=$service_provider_title?>', selectAll: true});
    $('.PK_SCHEDULING_CODE').SumoSelect({placeholder: 'Select Scheduling Code', selectAll: true});

    function calculateEndTime() {
        let start_time = $('#START_TIME').val();
        let duration = $('#PK_SCHEDULING_CODE').find(':selected').data('duration');
        let scheduling_name = $('#PK_SCHEDULING_CODE').find(':selected').data('scheduling_name');
        let is_default = $('#PK_SCHEDULING_CODE').find(':selected').data('is_default');
        $('#TITLE').val(scheduling_name);
        duration = (duration)?duration:0;

        if (start_time && duration) {
            start_time = moment(start_time, ["h:mm A"]).format("HH:mm");
            let end_time = addMinutes(start_time, duration);
            end_time = moment(end_time, ["HH:mm"]).format("h:mm A");
            $('#END_TIME').val(end_time);
        }

        if (is_default===1) {
            $('.customer_div').show();
        } else {
            $('.customer_div').hide();
        }
    }

    function addMinutes(time, minsToAdd) {
        function D(J){ return (J<10? '0':'') + J;};
        var piece = time.split(':');
        var mins = piece[0]*60 + +piece[1] + +minsToAdd;

        return D(mins%(24*60)/60 | 0) + ':' + D(mins%60);
    }

    $('#IS_STANDING').on('change', function(){
        if ($(this).is(':checked')){
            $('.is_required').prop('required', true);
            $(this).closest('.to_dos_class_setting').find('.standing').show();
            $(this).closest('.to_dos_class_setting').find('.END_TIME').hide();
        } else {
            $('.is_required').prop('required', false);
            $(this).closest('.to_dos_class_setting').find('.standing').hide();
            $(this).closest('.to_dos_class_setting').find('.END_TIME').show();
        }
    });
</script>

</html>