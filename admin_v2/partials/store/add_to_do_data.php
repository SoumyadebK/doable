<?php
require_once('../../../global/config.php');
global $db;
global $db_account;







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

header("location:" . $_SERVER['HTTP_REFERER']);
