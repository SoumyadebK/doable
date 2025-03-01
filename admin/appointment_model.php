<?php
//require_once('../../global/config.php');

if (empty($_GET['id']))
    $title = "Create Appointment";
else
    $title = "Edit Appointment";

if (!empty($_GET['type'])) {
    $type = $_GET['type'];
} else {
    $type = 'appointment';
}


if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

$FUNCTION_NAME = isset($_POST['FUNCTION_NAME']) ? $_POST['FUNCTION_NAME'] : '';

if ($FUNCTION_NAME == 'saveGroupClassData'){
    $SERVICE_ID = explode(',', $_POST['SERVICE_ID']);
    $DURATION = $SERVICE_ID[0];
    $PK_SERVICE_CODE = $SERVICE_ID[1];
    $PK_SERVICE_MASTER = $SERVICE_ID[2];

    $STARTING_ON = $_POST['STARTING_ON'];
    $LENGTH = $_POST['LENGTH'];
    $FREQUENCY = $_POST['FREQUENCY'];
    $END_DATE = date('Y-m-d', strtotime('+ ' . $LENGTH . ' ' . $FREQUENCY, strtotime($STARTING_ON)));

    $START_TIME = $_POST['START_TIME'];
    $END_TIME = date("H:i", strtotime($START_TIME)+($DURATION*60));

    $GROUP_CLASS_DATE_ARRAY = [];
    if (!empty($_POST['OCCURRENCE'])){
        $SERVICE_DATE = date('Y-m-d', strtotime($STARTING_ON));
        if ($_POST['OCCURRENCE'] == 'WEEKLY'){
            if (isset($_POST['DAYS'])) {
                $DAYS = $_POST['DAYS'];
            } else {
                $DAYS[] = strtolower(date('l', strtotime($STARTING_ON)));
            }
            while ($SERVICE_DATE < $END_DATE) {
                $appointment_day = date('l', strtotime($SERVICE_DATE));
                if (in_array(strtolower($appointment_day), $DAYS)){
                    $GROUP_CLASS_DATE_ARRAY[] = $SERVICE_DATE;
                }
                $SERVICE_DATE = date('Y-m-d', strtotime('+1 day ', strtotime($SERVICE_DATE)));
            }
        }else {
            $OCCURRENCE_DAYS = (empty($_POST['OCCURRENCE_DAYS']))?7:$_POST['OCCURRENCE_DAYS'];

            while ($SERVICE_DATE < $END_DATE) {
                $GROUP_CLASS_DATE_ARRAY[] = $SERVICE_DATE;
                $SERVICE_DATE = date('Y-m-d', strtotime('+ '.$OCCURRENCE_DAYS.' day', strtotime($SERVICE_DATE)));
                //echo $SERVICE_DATE . "<br>";
            }
        }
    }

    if (count($GROUP_CLASS_DATE_ARRAY) > 0) {
        $standing_data = $db_account->Execute("SELECT STANDING_ID FROM `DOA_GROUP_CLASS` ORDER BY STANDING_ID DESC LIMIT 1");
        if ($standing_data->RecordCount() > 0) {
            $standing_id = $standing_data->fields['STANDING_ID']+1;
        } else {
            $standing_id = 1;
        }

        for ($i = 0; $i < count($GROUP_CLASS_DATE_ARRAY); $i++) {
            $GROUP_CLASS_DATA['STANDING_ID'] = $standing_id;
            $GROUP_CLASS_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
            $GROUP_CLASS_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
            $GROUP_CLASS_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
            $GROUP_CLASS_DATA['SERVICE_PROVIDER_ID_1'] = $_POST['SERVICE_PROVIDER_ID_1'];
            $GROUP_CLASS_DATA['SERVICE_PROVIDER_ID_2'] = $_POST['SERVICE_PROVIDER_ID_2'];
            $GROUP_CLASS_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
            $GROUP_CLASS_DATA['DATE'] = $GROUP_CLASS_DATE_ARRAY[$i];
            $GROUP_CLASS_DATA['START_TIME'] = date('H:i:s', strtotime($START_TIME));
            $GROUP_CLASS_DATA['END_TIME'] = date('H:i:s', strtotime($END_TIME));
            $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 1;
            $GROUP_CLASS_DATA['ACTIVE'] = 1;
            $GROUP_CLASS_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $GROUP_CLASS_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_GROUP_CLASS', $GROUP_CLASS_DATA, 'insert');
        }
    }

    header("location:all_schedules.php");
} elseif ($FUNCTION_NAME == 'saveSpecialAppointment') {
    $SPECIAL_APPOINTMENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
    $SPECIAL_APPOINTMENT_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
    $SPECIAL_APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
    $SPECIAL_APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
    $SPECIAL_APPOINTMENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];


    if(empty($_GET['id'])){
        $SPECIAL_APPOINTMENT_DATA['ACTIVE'] = 1;
        $SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
        $SPECIAL_APPOINTMENT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $SPECIAL_APPOINTMENT_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'insert');
        $PK_SPECIAL_APPOINTMENT = $db_account->insert_ID();
    }else{
        //$SPECIAL_APPOINTMENT_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
        $SPECIAL_APPOINTMENT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $SPECIAL_APPOINTMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'update'," PK_SPECIAL_APPOINTMENT =  '$_GET[id]'");
        $PK_SPECIAL_APPOINTMENT = $_GET['id'];
    }

    if (isset($_POST['PK_USER'])) {
        $db_account->Execute("DELETE FROM `DOA_SPECIAL_APPOINTMENT_USER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
        for ($i = 0; $i < count($_POST['PK_USER']); $i++) {
            $SPECIAL_APPOINTMENT_USER['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT;
            $SPECIAL_APPOINTMENT_USER['PK_USER'] = $_POST['PK_USER'][$i];
            db_perform('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'insert');
        }
    }

    header("location:all_special_appointment.php");
} elseif ($FUNCTION_NAME == 'saveAppointmentData') {
    unset($_POST['TIME']);
    unset($_POST['FUNCTION_NAME']);
    if (empty($_POST['START_TIME']) || empty($_POST['END_TIME'])){
        unset($_POST['START_TIME']);
        unset($_POST['END_TIME']);
    }
    $session_cost = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_SERVICE_MASTER = '$_POST[PK_SERVICE_MASTER]' AND PK_SERVICE_CODE = '$_POST[PK_SERVICE_CODE]'");
    $price_per_session = $session_cost->fields['PRICE_PER_SESSION'];

    $START_TIME_ARRAY = explode(',', $_POST['START_TIME']);
    $END_TIME_ARRAY = explode(',', $_POST['END_TIME']);
    for ($i=0; $i<count($START_TIME_ARRAY); $i++) {
        $APPOINTMENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $APPOINTMENT_DATA['CUSTOMER_ID'] = $_POST['CUSTOMER_ID'];

        $PK_ENROLLMENT_MASTER_ARRAY = explode(',', $_POST['PK_ENROLLMENT_MASTER']);
        $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER_ARRAY[0];
        $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_MASTER_ARRAY[1];
        $APPOINTMENT_DATA['PK_SERVICE_MASTER'] = $PK_ENROLLMENT_MASTER_ARRAY[2];
        $APPOINTMENT_DATA['PK_SERVICE_CODE'] = $PK_ENROLLMENT_MASTER_ARRAY[3];

        $APPOINTMENT_DATA['SERVICE_PROVIDER_ID'] = $_POST['SERVICE_PROVIDER_ID'];
        $APPOINTMENT_DATA['DATE'] = $_POST['DATE'];
        $APPOINTMENT_DATA['START_TIME'] = $START_TIME_ARRAY[$i];
        $APPOINTMENT_DATA['END_TIME'] = $END_TIME_ARRAY[$i];
        $APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
        $APPOINTMENT_DATA['ACTIVE'] = 1;
        $APPOINTMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'insert');
    }

    rearrangeSerialNumber($_POST['PK_ENROLLMENT_MASTER'], $price_per_session);

    header("location:customer.php?id=".$_POST['PK_USER']."&master_id=".$_POST['CUSTOMER_ID']);
}

function rearrangeSerialNumber($PK_ENROLLMENT_MASTER, $price_per_session){
    global $db;
    global $db_account;
    $appointment_data = $db_account->Execute("SELECT * FROM `DOA_APPOINTMENT_MASTER` WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' ORDER BY DATE ASC");
    $total_bill_and_paid = $db_account->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$PK_ENROLLMENT_MASTER);
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
        db_perform_account('DOA_APPOINTMENT_MASTER', $UPDATE_DATA, 'update'," PK_APPOINTMENT_MASTER =  ".$appointment_data->fields['PK_APPOINTMENT_MASTER']);
        $appointment_data->MoveNext();
        $i++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>
<style>
    .slot_btn{
        background-color: greenyellow;
    }
    .SumoSelect {
        width: 100%;
    }
    .disable-div {
        opacity: 0.5;
        pointer-events: none
    }

    /*.button-selected {
        background-color: #690C24;
        border-color: #690C24;
    }

    .btn-info:hover {
        background-color: #690C24;
        border-color: #690C24;*/
    }
</style>
<link rel="stylesheet" href="../assets/CalendarPicker/CalendarPicker.style.css">
<div id="appointmentModel" class="modal">
    <!-- Modal content -->
    <div class="modal-content" style="margin-top:2%; width: 100%;">
        <span class="close close_appointment_model" style="margin-left: 96%;">&times;</span>
        <div>
            <div class="col-md-12 align-self-center text-center">
                <div class="d-flex justify-content-center align-items-center">
                    <!--<button type="button" id="group_class" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('group_class', this);"><i class="fa fa-plus-circle"></i> Group Class</button>
                    <button type="button" id="int_app" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('int_app', this);"><i class="fa fa-plus-circle"></i> INT APP</button>-->
                    <button type="button" id="appointment" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('appointment', this);"><i class="fa fa-plus-circle"></i> Appointment</button>
                    <button type="button" id="standing" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('standing', this);"><i class="fa fa-plus-circle"></i> Standing</button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body" id="create_form_div">
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</html>
<script>
    let type = '<?=$type?>';
    $(window).on('load', function () {
        let param = $('#'+type);
        createAppointment(type, param)
    })

    let PK_APPOINTMENT_MASTER = 0;
    const nextYear 	= new Date().getFullYear() + 2;
    const month 	= new Date().getMonth();
    var def_date 	= new Date();
    let start_time_array = [];
    let end_time_array = [];
    var myCalender;

    function createAppointment(type, param) {
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

        let PK_USER = parseInt(<?=empty($_GET['id'])?0:$_GET['id']?>);
        let PK_USER_MASTER = parseInt(<?=empty($_GET['master_id'])?0:$_GET['master_id']?>);

        $.ajax({
            url: url,
            type: "GET",
            data: {PK_USER: PK_USER, PK_USER_MASTER: PK_USER_MASTER},
            success: function (data) {
                $('#create_form_div').html(data);
            }
        });
    }
</script>