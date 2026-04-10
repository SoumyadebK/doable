<?php
require_once('../../../global/config.php');
global $db;
global $db_account;

$LOCATION_ARRAY = explode(',', $_SESSION['DEFAULT_LOCATION_ID']);

$SPECIAL_APPOINTMENT_DATE_ARRAY = [];
$REPEAT = $_POST['REPEAT'];
$standing_id = 0;

if ($REPEAT == 'Custom') {
    $STARTING_ON = $_POST['TO_DO_DATE'];
    $LENGTH = 12;
    $FREQUENCY = 'month';
    $END_DATE = isset($_POST['END_ON_TO_DO_DATE']) ? date('Y-m-d', strtotime($_POST['END_ON_TO_DO_DATE'])) : date('Y-m-d', strtotime('+ ' . $LENGTH . ' ' . $FREQUENCY, strtotime($STARTING_ON)));

    if (!empty($_POST['OCCURRENCE'])) {
        $APPOINTMENT_DATE = date('Y-m-d', strtotime($STARTING_ON));
        if ($_POST['OCCURRENCE'] == 'WEEKLY') {
            if (isset($_POST['DAYS'])) {
                $DAYS = $_POST['DAYS'];
            } else {
                $DAYS[] = strtolower(date('l', strtotime($STARTING_ON)));
            }
            while ($APPOINTMENT_DATE < $END_DATE) {
                $appointment_day = date('l', strtotime($APPOINTMENT_DATE));
                if (in_array(strtolower($appointment_day), $DAYS)) {
                    $SPECIAL_APPOINTMENT_DATE_ARRAY[] = $APPOINTMENT_DATE;
                }
                $APPOINTMENT_DATE = date('Y-m-d', strtotime('+1 day ', strtotime($APPOINTMENT_DATE)));
            }
        } else {
            $OCCURRENCE_DAYS = (empty($_POST['OCCURRENCE_DAYS'])) ? 7 : $_POST['OCCURRENCE_DAYS'];

            while ($APPOINTMENT_DATE < $END_DATE) {
                $SPECIAL_APPOINTMENT_DATE_ARRAY[] = $APPOINTMENT_DATE;
                $APPOINTMENT_DATE = date('Y-m-d', strtotime('+ ' . $OCCURRENCE_DAYS . ' day', strtotime($APPOINTMENT_DATE)));
                //echo $APPOINTMENT_DATE . "<br>";
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

$TOTAL_APPOINTMENT_TO_CREATE = isset($_POST['OCCURRENCE_AFTER']) ? $_POST['OCCURRENCE_AFTER'] : count($SPECIAL_APPOINTMENT_DATE_ARRAY);

if ($TOTAL_APPOINTMENT_TO_CREATE > 0) {
    if (isset($_POST['PK_USER'])) {
        for ($j = 0; $j < count($_POST['PK_USER']); $j++) {
            for ($i = 0; $i < $TOTAL_APPOINTMENT_TO_CREATE; $i++) {
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
            }
        }
    }
}

header("location:../../calendar.php?date=" . $SPECIAL_APPOINTMENT_DATA['DATE']);
