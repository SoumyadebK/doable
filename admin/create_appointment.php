<?php
require_once('../global/config.php');

use Twilio\Exceptions\ConfigurationException;
use Twilio\Rest\Client;

if (empty($_GET['id']))
    $title = "Create Appointment";
else
    $title = "Edit Appointment";

if (!empty($_GET['type'])) {
    $type = $_GET['type'];
} else {
    $type = '';
}

if (!empty($_GET['date'])) {
    $date_array = explode('T', $_GET['date']);
    $date = date("m/d/Y", strtotime($date_array[0]));
    $time = date("h:i A", strtotime($date_array[1]));

} else {
    $date = '';
    $time = '';
}

if (!empty($_GET['SERVICE_PROVIDER_ID'])) {
    $PK_USER = $_GET['SERVICE_PROVIDER_ID'];
} else {
    $PK_USER = '';
}


if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$FUNCTION_NAME = isset($_POST['FUNCTION_NAME']) ? $_POST['FUNCTION_NAME'] : '';

if ($FUNCTION_NAME == 'saveGroupClassData'){
    $SERVICE_ID = explode(',', $_POST['SERVICE_ID']);
    $DURATION = ($SERVICE_ID[0] > 0) ? $SERVICE_ID[0] : 30;
    $PK_SERVICE_CODE = $SERVICE_ID[1];
    $PK_SERVICE_MASTER = $SERVICE_ID[2];

    for ($i = 0; $i < count($_POST['STARTING_ON']); $i++) {
        $STARTING_ON = $_POST['STARTING_ON'][$i];
        $LENGTH = $_POST['LENGTH'][$i];
        $FREQUENCY = $_POST['FREQUENCY'][$i];
        $END_DATE = date('Y-m-d', strtotime('+ ' . $LENGTH . ' ' . $FREQUENCY, strtotime($STARTING_ON)));

        $START_TIME = $_POST['START_TIME'][$i];
        $END_TIME = date("H:i", strtotime($START_TIME) + ($DURATION * 60));

        $GROUP_CLASS_DATE_ARRAY = [];
        if (!empty($_POST['OCCURRENCE_'.$i])) {
            $SERVICE_DATE = date('Y-m-d', strtotime($STARTING_ON));
            if ($_POST['OCCURRENCE_'.$i] == 'WEEKLY') {
                if (isset($_POST['DAYS_'.$i])) {
                    $DAYS = $_POST['DAYS_'.$i];
                } else {
                    $DAYS[] = strtolower(date('l', strtotime($STARTING_ON)));
                }
                while ($SERVICE_DATE < $END_DATE) {
                    $appointment_day = date('l', strtotime($SERVICE_DATE));
                    if (in_array(strtolower($appointment_day), $DAYS)) {
                        $GROUP_CLASS_DATE_ARRAY[] = $SERVICE_DATE;
                    }
                    $SERVICE_DATE = date('Y-m-d', strtotime('+1 day ', strtotime($SERVICE_DATE)));
                }
            } else {
                $OCCURRENCE_DAYS = (empty($_POST['OCCURRENCE_DAYS'][$i])) ? 7 : $_POST['OCCURRENCE_DAYS'][$i];

                while ($SERVICE_DATE < $END_DATE) {
                    $GROUP_CLASS_DATE_ARRAY[] = $SERVICE_DATE;
                    $SERVICE_DATE = date('Y-m-d', strtotime('+ ' . $OCCURRENCE_DAYS . ' day', strtotime($SERVICE_DATE)));
                    //echo $SERVICE_DATE . "<br>";
                }
            }
        }

        if (count($GROUP_CLASS_DATE_ARRAY) > 0) {
            $group_class_data = $db_account->Execute("SELECT GROUP_CLASS_ID FROM `DOA_GROUP_CLASS` ORDER BY GROUP_CLASS_ID DESC LIMIT 1");
            if ($group_class_data->RecordCount() > 0) {
                $group_class_id = $group_class_data->fields['GROUP_CLASS_ID'] + 1;
            } else {
                $group_class_id = 1;
            }

            for ($j = 0; $j < count($GROUP_CLASS_DATE_ARRAY); $j++) {
                $GROUP_CLASS_DATA['GROUP_CLASS_ID'] = $group_class_id;
                $GROUP_CLASS_DATA['GROUP_NAME'] = $_POST['GROUP_NAME'];
                $GROUP_CLASS_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                $GROUP_CLASS_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                $GROUP_CLASS_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                //$GROUP_CLASS_DATA['SERVICE_PROVIDER_ID_1'] = $_POST['SERVICE_PROVIDER_ID_1'];
                //$GROUP_CLASS_DATA['SERVICE_PROVIDER_ID_2'] = $_POST['SERVICE_PROVIDER_ID_2'];
                $GROUP_CLASS_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
                $GROUP_CLASS_DATA['DATE'] = $GROUP_CLASS_DATE_ARRAY[$j];
                $GROUP_CLASS_DATA['START_TIME'] = date('H:i:s', strtotime($START_TIME));
                $GROUP_CLASS_DATA['END_TIME'] = date('H:i:s', strtotime($END_TIME));
                $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 1;
                $GROUP_CLASS_DATA['ACTIVE'] = 1;
                $GROUP_CLASS_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $GROUP_CLASS_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_GROUP_CLASS', $GROUP_CLASS_DATA, 'insert');
                $PK_GROUP_CLASS = $db_account->insert_ID();

                $db_account->Execute("DELETE FROM `DOA_GROUP_CLASS_USER` WHERE `PK_GROUP_CLASS` = '$PK_GROUP_CLASS'");
                for ($k = 0; $k < count($_POST['SERVICE_PROVIDER_ID_'.$i]); $k++) {
                    $GROUP_CLASS_USER_DATA['PK_GROUP_CLASS'] = $PK_GROUP_CLASS;
                    $GROUP_CLASS_USER_DATA['PK_USER'] = $_POST['SERVICE_PROVIDER_ID_'.$i][$k];
                    db_perform_account('DOA_GROUP_CLASS_USER', $GROUP_CLASS_USER_DATA, 'insert');
                }
            }
        }
    }

    header("location:all_schedules.php?view=table");
} elseif ($FUNCTION_NAME == 'saveSpecialAppointment') {
    $GROUP_CLASS_DATE_ARRAY = [];
    if (isset($_POST['IS_STANDING']) && $_POST['IS_STANDING'] == 1) {
        $STARTING_ON = date('Y-m-d', strtotime($_POST['DATE']));
        $LENGTH = $_POST['LENGTH'];
        $FREQUENCY = $_POST['FREQUENCY'];
        $END_DATE = date('Y-m-d', strtotime('+ ' . $LENGTH . ' ' . $FREQUENCY, strtotime($STARTING_ON)));

        $START_TIME = date('H:i', strtotime($_POST['START_TIME']));
        $END_TIME = date('H:i', strtotime($_POST['END_TIME']));

        if (!empty($_POST['OCCURRENCE'])) {
            $SERVICE_DATE = date('Y-m-d', strtotime($STARTING_ON));
            if ($_POST['OCCURRENCE'] == 'WEEKLY') {
                if (isset($_POST['DAYS'])) {
                    $DAYS = $_POST['DAYS'];
                } else {
                    $DAYS[] = strtolower(date('l', strtotime($STARTING_ON)));
                }
                while ($SERVICE_DATE < $END_DATE) {
                    $appointment_day = date('l', strtotime($SERVICE_DATE));
                    if (in_array(strtolower($appointment_day), $DAYS)) {
                        $GROUP_CLASS_DATE_ARRAY[] = $SERVICE_DATE;
                    }
                    $SERVICE_DATE = date('Y-m-d', strtotime('+1 day ', strtotime($SERVICE_DATE)));
                }
            } else {
                $OCCURRENCE_DAYS = (empty($_POST['OCCURRENCE_DAYS'])) ? 7 : $_POST['OCCURRENCE_DAYS'];

                while ($SERVICE_DATE < $END_DATE) {
                    $GROUP_CLASS_DATE_ARRAY[] = $SERVICE_DATE;
                    $SERVICE_DATE = date('Y-m-d', strtotime('+ ' . $OCCURRENCE_DAYS . ' day', strtotime($SERVICE_DATE)));
                    //echo $SERVICE_DATE . "<br>";
                }
            }
        }
    } else {
        $GROUP_CLASS_DATE_ARRAY[] = date('Y-m-d', strtotime($_POST['DATE']));
    }

    if (count($GROUP_CLASS_DATE_ARRAY) > 0) {
        for ($i = 0; $i < count($GROUP_CLASS_DATE_ARRAY); $i++) {
            $SPECIAL_APPOINTMENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
            $SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
            $SPECIAL_APPOINTMENT_DATA['DATE'] = $GROUP_CLASS_DATE_ARRAY[$i];
            $SPECIAL_APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
            $SPECIAL_APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
            $SPECIAL_APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
            $SPECIAL_APPOINTMENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
            $SPECIAL_APPOINTMENT_DATA['ACTIVE'] = 1;
            $SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
            $SPECIAL_APPOINTMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $SPECIAL_APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'insert');
            $PK_SPECIAL_APPOINTMENT = $db_account->insert_ID();

            if (isset($_POST['PK_USER'])) {
                $db_account->Execute("DELETE FROM `DOA_SPECIAL_APPOINTMENT_USER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
                for ($j = 0; $j < count($_POST['PK_USER']); $j++) {
                    $SPECIAL_APPOINTMENT_USER['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT;
                    $SPECIAL_APPOINTMENT_USER['PK_USER'] = $_POST['PK_USER'][$j];
                    db_perform_account('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'insert');
                }
            }

            if (isset($_POST['CUSTOMER_ID'])) {
                $db_account->Execute("DELETE FROM `DOA_SPECIAL_APPOINTMENT_CUSTOMER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
                for ($k = 0; $k < count($_POST['CUSTOMER_ID']); $k++) {
                    $SPECIAL_APPOINTMENT_CUSTOMER_DATA['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT;
                    $SPECIAL_APPOINTMENT_CUSTOMER_DATA['PK_USER_MASTER'] = $_POST['CUSTOMER_ID'][$k];
                    db_perform_account('DOA_SPECIAL_APPOINTMENT_CUSTOMER', $SPECIAL_APPOINTMENT_CUSTOMER_DATA, 'insert');
                }
            }
        }
    }
    header("location:all_schedules.php?view=table");
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

    header("location:all_schedules.php?view=table");
} elseif ($FUNCTION_NAME == 'saveAdhocAppointmentData') {
    unset($_POST['TIME']);
    unset($_POST['FUNCTION_NAME']);
    if (empty($_POST['START_TIME']) || empty($_POST['END_TIME'])){
        unset($_POST['START_TIME']);
        unset($_POST['END_TIME']);
    }
    $session_cost = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_SERVICE_MASTER = '$_POST[PK_SERVICE_MASTER]' AND PK_SERVICE_CODE = '$_POST[PK_SERVICE_CODE]'");
    $price_per_session = $session_cost->fields['PRICE_PER_SESSION'];

    $default_service_code = $db_account->Execute("SELECT * FROM `DOA_SERVICE_CODE` WHERE `IS_DEFAULT` = 1 LIMIT 1");

    $START_TIME_ARRAY = explode(',', $_POST['START_TIME']);
    $END_TIME_ARRAY = explode(',', $_POST['END_TIME']);
    for ($i=0; $i<count($START_TIME_ARRAY); $i++) {
        $APPOINTMENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $APPOINTMENT_DATA['CUSTOMER_ID'] = $_POST['CUSTOMER_ID'];

        //$PK_ENROLLMENT_MASTER_ARRAY = explode(',', $_POST['PK_ENROLLMENT_MASTER']);
        $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
        $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = 0;
        $APPOINTMENT_DATA['PK_SERVICE_MASTER'] = ($default_service_code->RecordCount() > 0) ? $default_service_code->fields['PK_SERVICE_MASTER'] : 0;
        $APPOINTMENT_DATA['PK_SERVICE_CODE'] = ($default_service_code->RecordCount() > 0) ? $default_service_code->fields['PK_SERVICE_CODE'] : 0;

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

    header("location:all_schedules.php?view=table");
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
        db_perform('DOA_APPOINTMENT_MASTER', $UPDATE_DATA, 'update'," PK_APPOINTMENT_MASTER =  ".$appointment_data->fields['PK_APPOINTMENT_MASTER']);
        $appointment_data->MoveNext();
        $i++;
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
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

    .button-selected {
        background-color: #690C24;
        border-color: #690C24;
    }

    .btn-info:hover {
        background-color: #690C24;
        border-color: #690C24;
    }
</style>
<link rel="stylesheet" href="../assets/CalendarPicker/CalendarPicker.style.css">
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">
                <div class="row page-titles navbar-fixed-top">
                    <div class="d-flex justify-content-center align-items-center">
                        <button type="button" id="group_class" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('group_class', this);"><i class="fa fa-plus-circle"></i> Group Class</button>
                        <button type="button" id="int_app" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('int_app', this);"><i class="fa fa-plus-circle"></i> To Dos</button>
                        <button type="button" id="appointment" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('appointment', this);"><i class="fa fa-plus-circle"></i> Appointment</button>
                        <button type="button" id="ad_hoc" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('ad_hoc', this);"><i class="fa fa-plus-circle"></i> Ad-hoc</button>
                        <button type="button" id="standing" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('standing', this);"><i class="fa fa-plus-circle"></i> Standing</button>
                    </div>
                </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body" id="create_form_div">
                            <h3 style="text-align: center">Select type of Appointment you want to create</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php');?>
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
            let PK_APPOINTMENT_MASTER = parseInt(<?=empty($_GET['id'])?0:$_GET['id']?>);
            $('.btn').removeClass('button-selected');
            $(param).addClass('button-selected');
            let url = '';
            if (type === 'group_class') {
                url = "ajax/add_group_classes.php?date=<?=$date?>&time=<?=$time?>&SERVICE_PROVIDER_ID=<?=$PK_USER?>";
            }
            if (type === 'int_app') {
                url = "ajax/add_special_appointment.php?date=<?=$date?>&time=<?=$time?>&SERVICE_PROVIDER_ID=<?=$PK_USER?>";
            }
            if (type === 'appointment') {
                url = "ajax/add_appointment.php?date=<?=$date?>&time=<?=$time?>&SERVICE_PROVIDER_ID=<?=$PK_USER?>";
            }
            if (type === 'ad_hoc') {
                url = "ajax/add_ad_hoc_appointment.php?date=<?=$date?>&time=<?=$time?>&SERVICE_PROVIDER_ID=<?=$PK_USER?>&id="+PK_APPOINTMENT_MASTER;
            }
            if (type === 'standing') {
                url = "ajax/add_multiple_appointment.php?date=<?=$date?>&time=<?=$time?>&SERVICE_PROVIDER_ID=<?=$PK_USER?>";
            }
            $.ajax({
                url: url,
                type: "POST",
                success: function (data) {
                    $('#create_form_div').html(data);
                }
            });
        }
    </script>
</body>
</html>
