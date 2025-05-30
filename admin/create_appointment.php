<?php
require_once('../global/config.php');
global $db;
global $db_account;

$LOCATION_ARRAY = explode(',', $_SESSION['DEFAULT_LOCATION_ID']);

use Twilio\Exceptions\ConfigurationException;
use Twilio\Rest\Client;

$title = "Create Appointment";

if (!empty($_GET['date'])) {
    $dateTime = DateTime::createFromFormat('D M d Y H:i:s e+', $_GET['date']);

    $date = $dateTime->format('m/d/Y');
    $time = $dateTime->format('h:i A');

    /*$date_array = explode('T', $_GET['date']);
    $date = date("m/d/Y", strtotime($date_array[0]));
    $time_array = explode(' ', $date_array[1]);
    $time = date("h:i A", strtotime($time_array[0]));*/
} else {
    $date = '';
    $time = '';
}

if (!empty($_GET['SERVICE_PROVIDER_ID'])) {
    $PK_USER = $_GET['SERVICE_PROVIDER_ID'];
} else {
    $PK_USER = '';
}

if (empty($_GET['master_id_customer'])) {
    $PK_USER_MASTER = 0;
} else {
    $PK_USER_MASTER = $_GET['master_id_customer'];
}

if (!empty($_GET['source']) && $_GET['source'] === 'customer') {
    $header = 'customer.php?id='.$_GET['id_customer'].'&master_id='.$_GET['master_id_customer'].'&tab=appointment';
    $source = 'customer';
    $id_customer = $_GET['id_customer'];
} else {
    $header = 'all_schedules.php';
    $source = '';
    $id_customer = '';
}

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4]) ){
    header("location:../login.php");
    exit;
}

$FUNCTION_NAME = isset($_POST['FUNCTION_NAME']) ? $_POST['FUNCTION_NAME'] : '';

if ($FUNCTION_NAME == 'saveGroupClassData'){
    $SERVICE_ID = explode(',', $_POST['SERVICE_ID']);
    $PK_SERVICE_MASTER = $SERVICE_ID[0];
    $PK_SERVICE_CODE = $SERVICE_ID[1];

    $SCHEDULING_CODE = explode(',', $_POST['SCHEDULING_CODE']);
    $PK_SCHEDULING_CODE = $SCHEDULING_CODE[0];
    $DURATION = $SCHEDULING_CODE[1];

    for ($i = 0; $i < count($_POST['STARTING_ON']); $i++) {
        $GROUP_NAME = $_POST['GROUP_NAME'][$i];
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
            $standing_id = 0;

            if (count($GROUP_CLASS_DATE_ARRAY) > 1) {
                $standing_data = $db_account->Execute("SELECT STANDING_ID FROM `DOA_APPOINTMENT_MASTER` ORDER BY STANDING_ID DESC LIMIT 1");
                if ($standing_data->RecordCount() > 0) {
                    $standing_id = $standing_data->fields['STANDING_ID'] + 1;
                } else {
                    $standing_id = 1;
                }
            }

            for ($j = 0; $j < count($GROUP_CLASS_DATE_ARRAY); $j++) {
                $GROUP_CLASS_DATA['SERIAL_NUMBER'] = getGroupClassSerialNumber();
                $GROUP_CLASS_DATA['STANDING_ID'] = $standing_id;
                $GROUP_CLASS_DATA['GROUP_NAME'] = $GROUP_NAME;
                $GROUP_CLASS_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                $GROUP_CLASS_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                $GROUP_CLASS_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;

                $GROUP_CLASS_DATA['DATE'] = $GROUP_CLASS_DATE_ARRAY[$j];
                $GROUP_CLASS_DATA['START_TIME'] = date('H:i:s', strtotime($START_TIME));
                $GROUP_CLASS_DATA['END_TIME'] = date('H:i:s', strtotime($END_TIME));
                $GROUP_CLASS_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
                $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 1;
                $GROUP_CLASS_DATA['ACTIVE'] = 1;
                $GROUP_CLASS_DATA['APPOINTMENT_TYPE'] = 'GROUP';
                $GROUP_CLASS_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $GROUP_CLASS_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_APPOINTMENT_MASTER', $GROUP_CLASS_DATA, 'insert');
                $PK_APPOINTMENT_MASTER = $db_account->insert_ID();

                $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_SERVICE_PROVIDER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER'");
                for ($k = 0; $k < count($_POST['SERVICE_PROVIDER_ID_'.$i]); $k++) {
                    $GROUP_CLASS_SP_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                    $GROUP_CLASS_SP_DATA['PK_USER'] = $_POST['SERVICE_PROVIDER_ID_'.$i][$k];
                    db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $GROUP_CLASS_SP_DATA, 'insert');
                }
            }
        }
    }
    header("location:".$header);
} elseif ($FUNCTION_NAME == 'saveSpecialAppointment') {
    $standing_id = 0;
    $SPECIAL_APPOINTMENT_DATE_ARRAY = [];

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
                        $SPECIAL_APPOINTMENT_DATE_ARRAY[] = $SERVICE_DATE;
                    }
                    $SERVICE_DATE = date('Y-m-d', strtotime('+1 day ', strtotime($SERVICE_DATE)));
                }
            } else {
                $OCCURRENCE_DAYS = (empty($_POST['OCCURRENCE_DAYS'])) ? 7 : $_POST['OCCURRENCE_DAYS'];

                while ($SERVICE_DATE < $END_DATE) {
                    $SPECIAL_APPOINTMENT_DATE_ARRAY[] = $SERVICE_DATE;
                    $SERVICE_DATE = date('Y-m-d', strtotime('+ ' . $OCCURRENCE_DAYS . ' day', strtotime($SERVICE_DATE)));
                    //echo $SERVICE_DATE . "<br>";
                }
            }
        }

        $special_appointment_data = $db_account->Execute("SELECT STANDING_ID FROM `DOA_SPECIAL_APPOINTMENT` ORDER BY STANDING_ID DESC LIMIT 1");
        if ($special_appointment_data->RecordCount() > 0) {
            $standing_id = $special_appointment_data->fields['STANDING_ID'] + 1;
        } else {
            $standing_id = 1;
        }
    } else {
        $SPECIAL_APPOINTMENT_DATE_ARRAY[] = date('Y-m-d', strtotime($_POST['DATE']));
    }

    if (count($SPECIAL_APPOINTMENT_DATE_ARRAY) > 0) {
        if (isset($_POST['PK_USER'])) {
            for ($j = 0; $j < count($_POST['PK_USER']); $j++) {
                for ($i = 0; $i < count($SPECIAL_APPOINTMENT_DATE_ARRAY); $i++) {
                    //$SPECIAL_APPOINTMENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                    $SPECIAL_APPOINTMENT_DATA['STANDING_ID'] = $standing_id;
                    $SPECIAL_APPOINTMENT_DATA['PK_LOCATION'] = $LOCATION_ARRAY[0];
                    $SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
                    $SPECIAL_APPOINTMENT_DATA['DATE'] = $SPECIAL_APPOINTMENT_DATE_ARRAY[$i];
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

                    $SPECIAL_APPOINTMENT_USER['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT;
                    $SPECIAL_APPOINTMENT_USER['PK_USER'] = $_POST['PK_USER'][$j];
                    db_perform_account('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'insert');

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
        }
    }
    header("location:".$header);
} elseif ($FUNCTION_NAME == 'saveAppointmentData') {
    unset($_POST['TIME']);
    unset($_POST['FUNCTION_NAME']);
    if (empty($_POST['START_TIME']) || empty($_POST['END_TIME'])){
        unset($_POST['START_TIME']);
        unset($_POST['END_TIME']);
    }

    $SCHEDULING_CODE = explode(',', $_POST['PK_SCHEDULING_CODE']);
    $PK_SCHEDULING_CODE = $SCHEDULING_CODE[0];
    $DURATION = $SCHEDULING_CODE[1];

    $PK_ENROLLMENT_MASTER_ARRAY = explode(',', $_POST['PK_ENROLLMENT_MASTER']);
    $PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER_ARRAY[0];
    $PK_ENROLLMENT_SERVICE = $PK_ENROLLMENT_MASTER_ARRAY[1];
    $PK_SERVICE_MASTER = $PK_ENROLLMENT_MASTER_ARRAY[2];
    $PK_SERVICE_CODE = $PK_ENROLLMENT_MASTER_ARRAY[3];

    /*$session_cost = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_SERVICE_MASTER = '$_POST[PK_SERVICE_MASTER]' AND PK_SERVICE_CODE = '$_POST[PK_SERVICE_CODE]'");
    $price_per_session = $session_cost->fields['PRICE_PER_SESSION'];*/

    $START_TIME_ARRAY = explode(',', $_POST['START_TIME']);
    $END_TIME_ARRAY = explode(',', $_POST['END_TIME']);

    $enrollment_location = $db_account->Execute("SELECT PK_LOCATION, CHARGE_TYPE FROM `DOA_ENROLLMENT_MASTER` WHERE PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);
    if ($enrollment_location->RecordCount() > 0) {
        $PK_LOCATION = $enrollment_location->fields['PK_LOCATION'];
    } else {
        $PK_LOCATION = 0;
    }

    $enrollment_service_data = $db_account->Execute("SELECT `NUMBER_OF_SESSION` FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_SERVICE` = ".$PK_ENROLLMENT_SERVICE);
    if ($enrollment_location->fields['CHARGE_TYPE'] == 'Membership') {
        $NUMBER_OF_SESSION = 99;
    } else {
        $NUMBER_OF_SESSION = $enrollment_service_data->fields['NUMBER_OF_SESSION'];
    }
    $SESSION_CREATED = getAllSessionCreatedCount($PK_ENROLLMENT_SERVICE, 'NORMAL');
    $SESSION_LEFT = $NUMBER_OF_SESSION - $SESSION_CREATED;

    $APPOINTMENT_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
    $APPOINTMENT_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
    $APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
    $APPOINTMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
    $APPOINTMENT_DATA['DATE'] = $_POST['DATE'];
    $APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
    $APPOINTMENT_DATA['ACTIVE'] = 1;
    $APPOINTMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
    $APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");

    for ($i=0; $i<count($START_TIME_ARRAY); $i++) {
        if ($i < $SESSION_LEFT) {
            $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
            $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_SERVICE;
            $APPOINTMENT_DATA['APPOINTMENT_TYPE'] = 'NORMAL';
        } else {
            $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
            $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = 0;
            $APPOINTMENT_DATA['APPOINTMENT_TYPE'] = 'AD-HOC';
        }

        $APPOINTMENT_DATA['SERIAL_NUMBER'] = getAppointmentSerialNumber($_POST['CUSTOMER_ID'][0]);
        $APPOINTMENT_DATA['START_TIME'] = $START_TIME_ARRAY[$i];
        $APPOINTMENT_DATA['END_TIME'] = $END_TIME_ARRAY[$i];

        if ($APPOINTMENT_DATA['START_TIME'] != $APPOINTMENT_DATA['END_TIME']) {
            db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'insert');
            $PK_APPOINTMENT_MASTER = $db_account->insert_ID();

            $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_SERVICE_PROVIDER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER'");
            for ($j = 0; $j < count($_POST['SERVICE_PROVIDER_ID']); $j++) {
                $APPOINTMENT_SP_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                $APPOINTMENT_SP_DATA['PK_USER'] = $_POST['SERVICE_PROVIDER_ID'][$j];
                db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $APPOINTMENT_SP_DATA, 'insert');
            }

            $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_CUSTOMER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER'");
            for ($k = 0; $k < count($_POST['CUSTOMER_ID']); $k++) {
                $APPOINTMENT_CUSTOMER_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                $APPOINTMENT_CUSTOMER_DATA['PK_USER_MASTER'] = $_POST['CUSTOMER_ID'][$k];
                db_perform_account('DOA_APPOINTMENT_CUSTOMER', $APPOINTMENT_CUSTOMER_DATA, 'insert');
            }
        }

        //updateSessionCreatedCount($APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE']);
    }
    markAppointmentPaid($APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE']);

    //rearrangeSerialNumber($_POST['PK_ENROLLMENT_MASTER'], $price_per_session);

    header("location:".$header);
} elseif ($FUNCTION_NAME == 'saveAdhocAppointmentData') {
    unset($_POST['TIME']);
    unset($_POST['FUNCTION_NAME']);
    if (empty($_POST['START_TIME']) || empty($_POST['END_TIME'])){
        unset($_POST['START_TIME']);
        unset($_POST['END_TIME']);
    }

    $SERVICE_ID = explode(',', $_POST['SERVICE_ID']);
    $PK_SERVICE_MASTER = $SERVICE_ID[0];
    $PK_SERVICE_CODE = $SERVICE_ID[1];

    $SCHEDULING_CODE = explode(',', $_POST['SCHEDULING_CODE']);
    $PK_SCHEDULING_CODE = $SCHEDULING_CODE[0];
    $DURATION = $SCHEDULING_CODE[1];

    /*$default_service_code = $db_account->Execute("SELECT * FROM `DOA_SERVICE_CODE` WHERE `IS_DEFAULT` = 1 LIMIT 1");*/

    $START_TIME_ARRAY = explode(',', $_POST['START_TIME']);
    $END_TIME_ARRAY = explode(',', $_POST['END_TIME']);

    /*$user_location = $db->Execute("SELECT PRIMARY_LOCATION_ID FROM DOA_USER_MASTER WHERE PK_USER_MASTER = ".$_POST['CUSTOMER_ID'][0]);
    if ($user_location->RecordCount() > 0) {
        $PK_LOCATION = $user_location->fields['PRIMARY_LOCATION_ID'];
    } else {
        $PK_LOCATION = 0;
    }*/

    $PK_LOCATION = $_POST['PK_LOCATION'];

    $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
    $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = 0;
    $APPOINTMENT_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
    $APPOINTMENT_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
    $APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
    $APPOINTMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
    $APPOINTMENT_DATA['DATE'] = $_POST['DATE'];
    $APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
    $APPOINTMENT_DATA['ACTIVE'] = 1;
    $APPOINTMENT_DATA['APPOINTMENT_TYPE'] = 'AD-HOC';
    $APPOINTMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
    $APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");

    for ($i=0; $i<count($START_TIME_ARRAY); $i++) {
        $APPOINTMENT_DATA['START_TIME'] = $START_TIME_ARRAY[$i];
        $APPOINTMENT_DATA['END_TIME'] = $END_TIME_ARRAY[$i];

        if ($APPOINTMENT_DATA['START_TIME'] != $APPOINTMENT_DATA['END_TIME']) {
            $APPOINTMENT_DATA['SERIAL_NUMBER'] = getAppointmentSerialNumber($_POST['CUSTOMER_ID'][0]);
            db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'insert');
            $PK_APPOINTMENT_MASTER = $db_account->insert_ID();

            checkAdhocAppointmentStatus($PK_APPOINTMENT_MASTER, $PK_SERVICE_MASTER, $PK_SERVICE_CODE, $_POST['CUSTOMER_ID'][0], $PK_LOCATION);

            $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_SERVICE_PROVIDER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER'");
            for ($j = 0; $j < count($_POST['SERVICE_PROVIDER_ID']); $j++) {
                $APPOINTMENT_SP_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                $APPOINTMENT_SP_DATA['PK_USER'] = $_POST['SERVICE_PROVIDER_ID'][$j];
                db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $APPOINTMENT_SP_DATA, 'insert');
            }

            $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_CUSTOMER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER'");
            for ($k = 0; $k < count($_POST['CUSTOMER_ID']); $k++) {
                $APPOINTMENT_CUSTOMER_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                $APPOINTMENT_CUSTOMER_DATA['PK_USER_MASTER'] = $_POST['CUSTOMER_ID'][$k];
                db_perform_account('DOA_APPOINTMENT_CUSTOMER', $APPOINTMENT_CUSTOMER_DATA, 'insert');
            }
        }
    }

    //rearrangeSerialNumber($_POST['PK_ENROLLMENT_MASTER'], $price_per_session);

    header("location:".$header);
} elseif ($FUNCTION_NAME == 'saveDemoAppointmentData') {
    unset($_POST['TIME']);
    unset($_POST['FUNCTION_NAME']);
    if (empty($_POST['START_TIME']) || empty($_POST['END_TIME'])){
        unset($_POST['START_TIME']);
        unset($_POST['END_TIME']);
    }

    $PK_SERVICE_MASTER = $_POST['PK_SERVICE_MASTER'];
    $PK_SERVICE_CODE = $_POST['PK_SERVICE_CODE'];

    $SCHEDULING_CODE = explode(',', $_POST['SCHEDULING_CODE']);
    $PK_SCHEDULING_CODE = $SCHEDULING_CODE[0];
    $DURATION = $SCHEDULING_CODE[1];

    /*$default_service_code = $db_account->Execute("SELECT * FROM `DOA_SERVICE_CODE` WHERE `IS_DEFAULT` = 1 LIMIT 1");*/

    $START_TIME_ARRAY = explode(',', $_POST['START_TIME']);
    $END_TIME_ARRAY = explode(',', $_POST['END_TIME']);

    /*$user_location = $db->Execute("SELECT PRIMARY_LOCATION_ID FROM DOA_USER_MASTER WHERE PK_USER_MASTER = ".$_POST['CUSTOMER_ID'][0]);
    if ($user_location->RecordCount() > 0) {
        $PK_LOCATION = $user_location->fields['PRIMARY_LOCATION_ID'];
    } else {
        $PK_LOCATION = 0;
    }*/

    $PK_LOCATION = $_POST['PK_LOCATION'];

    $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
    $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = 0;
    $APPOINTMENT_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
    $APPOINTMENT_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
    $APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
    $APPOINTMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
    $APPOINTMENT_DATA['DATE'] = $_POST['DATE'];
    $APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
    $APPOINTMENT_DATA['ACTIVE'] = 1;
    $APPOINTMENT_DATA['APPOINTMENT_TYPE'] = 'DEMO';
    $APPOINTMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
    $APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");

    for ($i=0; $i<count($START_TIME_ARRAY); $i++) {
        $APPOINTMENT_DATA['START_TIME'] = $START_TIME_ARRAY[$i];
        $APPOINTMENT_DATA['END_TIME'] = $END_TIME_ARRAY[$i];
        $APPOINTMENT_DATA['SERIAL_NUMBER'] = getAppointmentSerialNumber($_POST['CUSTOMER_ID'][0]);
        db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'insert');
        $PK_APPOINTMENT_MASTER = $db_account->insert_ID();


        $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_SERVICE_PROVIDER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER'");
        for ($j = 0; $j < count($_POST['SERVICE_PROVIDER_ID']); $j++) {
            $APPOINTMENT_SP_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
            $APPOINTMENT_SP_DATA['PK_USER'] = $_POST['SERVICE_PROVIDER_ID'][$j];
            db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $APPOINTMENT_SP_DATA, 'insert');
        }

        $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_CUSTOMER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER'");
        for ($k = 0; $k < count($_POST['CUSTOMER_ID']); $k++) {
            $APPOINTMENT_CUSTOMER_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
            $APPOINTMENT_CUSTOMER_DATA['PK_USER_MASTER'] = $_POST['CUSTOMER_ID'][$k];
            db_perform_account('DOA_APPOINTMENT_CUSTOMER', $APPOINTMENT_CUSTOMER_DATA, 'insert');
        }
    }

    //rearrangeSerialNumber($_POST['PK_ENROLLMENT_MASTER'], $price_per_session);

    header("location:".$header);
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
                        <?php if (count($LOCATION_ARRAY) == 1) { ?>
                            <button type="button" id="group_class" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('group_class', this);"><i class="fa fa-plus-circle"></i> Group Class</button>
                            <button type="button" id="int_app" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('int_app', this);"><i class="fa fa-plus-circle"></i> To Dos</button>
                            <button type="button" id="appointment" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('appointment', this);"><i class="fa fa-plus-circle"></i> Appointment</button>
                            <button type="button" id="ad_hoc" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('ad_hoc', this);"><i class="fa fa-plus-circle"></i> Ad-hoc</button>
                            <button type="button" id="standing" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('standing', this);"><i class="fa fa-plus-circle"></i> Standing</button>
                            <button type="button" id="standing" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="createAppointment('demo', this);"><i class="fa fa-plus-circle"></i> For Record Only</button>
                        <?php } ?>
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
        let PK_APPOINTMENT_MASTER = 0;
        const nextYear 	= new Date().getFullYear() + 2;
        const month 	= new Date().getMonth();
        var def_date 	= new Date();
        let start_time_array = [];
        let end_time_array = [];
        var myCalender;
        var PK_USER_MASTER = <?=$PK_USER_MASTER?>;
        var SELECTED_SERVICE_PROVIDER_ID = '<?=$PK_USER?>';

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
                url = "ajax/add_appointment.php?date=<?=$date?>&time=<?=$time?>&SERVICE_PROVIDER_ID=<?=$PK_USER?>&PK_USER_MASTER=<?=$PK_USER_MASTER?>";
            }
            if (type === 'ad_hoc') {
                url = "ajax/add_ad_hoc_appointment.php?date=<?=$date?>&time=<?=$time?>&SERVICE_PROVIDER_ID=<?=$PK_USER?>&PK_USER_MASTER=<?=$PK_USER_MASTER?>&id="+PK_APPOINTMENT_MASTER;
            }
            if (type === 'standing') {
                url = "ajax/add_multiple_appointment.php?date=<?=$date?>&time=<?=$time?>&SERVICE_PROVIDER_ID=<?=$PK_USER?>&PK_USER_MASTER=<?=$PK_USER_MASTER?>&source=<?=$source?>&id_customer=<?=$id_customer?>";
            }
            if (type === 'demo') {
                url = "ajax/add_demo_appointment.php?date=<?=$date?>&time=<?=$time?>&SERVICE_PROVIDER_ID=<?=$PK_USER?>&PK_USER_MASTER=<?=$PK_USER_MASTER?>";
            }
            $.ajax({
                url: url,
                type: "POST",
                success: function (data) {
                    $('#create_form_div').html(data);
                }
            });
        }

        function selectThisCustomer(param) {
            PK_USER_MASTER = $(param).val();
            $.ajax({
                url: "ajax/get_enrollments.php",
                type: "POST",
                data: {PK_USER_MASTER: PK_USER_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#PK_ENROLLMENT_MASTER').empty();
                    $('#PK_ENROLLMENT_MASTER').append(result);
                }
            });
        }

        function getSlots(){
            let PK_ENROLLMENT_MASTER = $('#PK_ENROLLMENT_MASTER').val();

            /*let PK_SERVICE_MASTER = $('#PK_SERVICE_MASTER').val();
            let PK_SERVICE_CODE = $('#PK_SERVICE_CODE').val();*/
            let SERVICE_PROVIDER_ID = $('#SERVICE_PROVIDER_ID').val();
            let duration = $('#PK_SCHEDULING_CODE').find(':selected').data('duration');
            let selected_date  = myCalender.value.toDateString();
            let day = (selected_date.toString().split(' ')[0]).toUpperCase();
            let month = selected_date.toString().split(' ')[1];

            let PK_LOCATION = $('#PK_ENROLLMENT_MASTER').find(':selected').data('location_id');
            if (!PK_LOCATION) {
                PK_LOCATION = $('#SELECT_CUSTOMER').find(':selected').data('location_id');
            }

            if(month == 'Jan')
                month = '01'
            else if(month == 'Feb')
                month = '02'
            else if(month == 'Mar')
                month = '03'
            else if(month == 'Apr')
                month = '04'
            else if(month == 'May')
                month = '05'
            else if(month == 'Jun')
                month = '06'
            else if(month == 'Jul')
                month = '07'
            else if(month == 'Aug')
                month = '08'
            else if(month == 'Sep')
                month = '09'
            else if(month == 'Oct')
                month = '10'
            else if(month == 'Nov')
                month = '11'
            else if(month == 'Dec')
                month = '12'
            let date = selected_date.toString().split(' ')[3]+'-'+month+'-'+selected_date.toString().split(' ')[2];
            let START_TIME = '';
            let END_TIME = '';

            //duration = (duration > 0) ?duration: 30;

            //console.log(SERVICE_PROVIDER_ID,duration,day);

            if (SERVICE_PROVIDER_ID > 0 && duration > 0) {
                start_time_array = [];
                end_time_array = [];
                $.ajax({
                    url: "ajax/get_slots.php",
                    type: "POST",
                    data: {
                        PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER,
                        PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                        /*PK_SERVICE_MASTER: PK_SERVICE_MASTER,
                        PK_SERVICE_CODE: PK_SERVICE_CODE,*/
                        SERVICE_PROVIDER_ID: SERVICE_PROVIDER_ID,
                        PK_LOCATION: PK_LOCATION,
                        duration: duration,
                        day: day,
                        date: date,
                        START_TIME: START_TIME,
                        END_TIME: END_TIME,
                        slot_time: '<?=$time?>'
                    },
                    async: false,
                    cache: false,
                    success: function (result) {
                        $('#slot_div').html(result);
                    }
                });
            }else {
                $('#slot_div').html('');
            }
        }

        function set_time(param, id, start_time, end_time, PK_APPOINTMENT_MASTER){
            if ($(param).data('is_selected') == 0) {
                start_time_array.push(start_time);
                end_time_array.push(end_time);
                $('#START_TIME').val(start_time_array.sort());
                $('#END_TIME').val(end_time_array.sort());
                $('#slot_btn_' + id).data('is_selected', 1);
                document.getElementById('slot_btn_' + id).style.setProperty('background-color', 'orange', 'important');
            } else {
                const start_time_index = start_time_array.indexOf(start_time);
                if (start_time_index > -1) {
                    start_time_array.splice(start_time_index, 1);
                }

                const end_time_index = end_time_array.indexOf(end_time);
                if (end_time_index > -1) {
                    end_time_array.splice(end_time_index, 1);
                }

                $('#START_TIME').val(start_time_array.sort());
                $('#END_TIME').val(end_time_array.sort());
                $('#slot_btn_' + id).data('is_selected', 0);
                document.getElementById('slot_btn_' + id).style.setProperty('background-color', 'greenyellow', 'important');
            }

            console.log(start_time_array.sort(), end_time_array.sort());

            if (PK_APPOINTMENT_MASTER > 0) {
                let slot_btn = $(".slot_btn");
                slot_btn.each(function (index) {
                    if ($(param).data('is_disable') == 0) {
                        $(param).css('background-color', 'greenyellow');
                    }
                })
            }
        }

        $(document).on('submit', '#appointment_form', function (event) {
            //event.preventDefault();
            $('.selected_slot').trigger('click');
            let selected_date  = myCalender.value.toDateString()
            let month = selected_date.toString().split(' ')[1];
            if(month == 'Jan')
                month = '01'
            else if(month == 'Feb')
                month = '02'
            else if(month == 'Mar')
                month = '03'
            else if(month == 'Apr')
                month = '04'
            else if(month == 'May')
                month = '05'
            else if(month == 'Jun')
                month = '06'
            else if(month == 'Jul')
                month = '07'
            else if(month == 'Aug')
                month = '08'
            else if(month == 'Sep')
                month = '09'
            else if(month == 'Oct')
                month = '10'
            else if(month == 'Nov')
                month = '11'
            else if(month == 'Dec')
                month = '12'
            let date = selected_date.toString().split(' ')[3]+'-'+month+'-'+selected_date.toString().split(' ')[2];
            $('#DATE').val(date);
        });
    </script>
</body>
</html>
