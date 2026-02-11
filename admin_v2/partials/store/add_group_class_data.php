<?php
require_once('../../../global/config.php');
global $db;
global $db_account;

$LOCATION_ARRAY = explode(',', $_SESSION['DEFAULT_LOCATION_ID']);

$SERVICE_ID = explode(',', $_POST['SERVICE_ID']);
$PK_SERVICE_MASTER = $SERVICE_ID[0];
$PK_SERVICE_CODE = $SERVICE_ID[1];

$SCHEDULING_CODE = explode(',', $_POST['SCHEDULING_CODE']);
$PK_SCHEDULING_CODE = $SCHEDULING_CODE[0];
$DURATION = $SCHEDULING_CODE[1];

for ($i = 0; $i < count($_POST['STARTING_ON']); $i++) {
    $GROUP_NAME = $_POST['GROUP_NAME'];
    $STARTING_ON = $_POST['STARTING_ON'][$i];
    $LENGTH = $_POST['LENGTH'][$i];
    $FREQUENCY = $_POST['FREQUENCY'][$i];
    $END_DATE = date('Y-m-d', strtotime('+ ' . $LENGTH . ' ' . $FREQUENCY, strtotime($STARTING_ON)));

    $START_TIME = $_POST['START_TIME'][$i];
    $END_TIME = date("H:i", strtotime($START_TIME) + ($DURATION * 60));

    $OCCURRENCE = 'WEEKLY';

    $GROUP_CLASS_DATE_ARRAY = [];
    if (!empty($OCCURRENCE)) {
        $SERVICE_DATE = date('Y-m-d', strtotime($STARTING_ON));
        if ($OCCURRENCE == 'WEEKLY') {
            if (isset($_POST['DAYS'][$i])) {
                $DAYS = explode(', ', $_POST['DAYS'][$i]);
            } else {
                $DAYS[] = strtolower(date('l', strtotime($STARTING_ON)));
            }
            $DAYS = array_map('strtolower', $DAYS);
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
            $GROUP_CLASS_DATA['PK_LOCATION'] = $LOCATION_ARRAY[0];
            $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 1;
            $GROUP_CLASS_DATA['ACTIVE'] = 1;
            $GROUP_CLASS_DATA['APPOINTMENT_TYPE'] = 'GROUP';
            $GROUP_CLASS_DATA['COMMENT'] = (isset($_POST['COMMENT']) && !empty($_POST['COMMENT'])) ? $_POST['COMMENT'] : '';
            $GROUP_CLASS_DATA['INTERNAL_COMMENT'] = (isset($_POST['INTERNAL_COMMENT']) && !empty($_POST['INTERNAL_COMMENT'])) ? $_POST['INTERNAL_COMMENT'] : '';
            $GROUP_CLASS_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $GROUP_CLASS_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_APPOINTMENT_MASTER', $GROUP_CLASS_DATA, 'insert');
            $PK_APPOINTMENT_MASTER = $db_account->insert_ID();

            $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_SERVICE_PROVIDER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER'");
            for ($k = 0; $k < count($_POST['PK_USER']); $k++) {
                $GROUP_CLASS_SP_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                $GROUP_CLASS_SP_DATA['PK_USER'] = $_POST['PK_USER'][$k];
                db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $GROUP_CLASS_SP_DATA, 'insert');
            }
        }
    }
}

header("location:" . $_SERVER['HTTP_REFERER']);
