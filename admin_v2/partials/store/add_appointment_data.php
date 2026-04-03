<?php
require_once('../../../global/config.php');
global $db;
global $db_account;

if (empty($_POST['START_TIME']) || empty($_POST['END_TIME'])) {
    unset($_POST['START_TIME']);
    unset($_POST['END_TIME']);
}

if ($_POST['PK_ENROLLMENT_MASTER'] == 'AD-HOC') {
    $SERVICE_MASTER = explode(',', $_POST['PK_SERVICE_MASTER']);
    $PK_SERVICE_MASTER = $SERVICE_MASTER[0];
    $PK_SERVICE_CODE = $SERVICE_MASTER[1];

    $SCHEDULING_CODE = explode(',', $_POST['PK_SCHEDULING_CODE']);
    $PK_SCHEDULING_CODE = $SCHEDULING_CODE[0];
    $DURATION = $SCHEDULING_CODE[1];

    $PK_USER_MASTER = $_POST['SELECTED_CUSTOMER_ID'];

    $PK_LOCATION = $_POST['PK_LOCATION'];

    $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
    $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = 0;
    $APPOINTMENT_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
    $APPOINTMENT_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
    $APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
    $APPOINTMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
    $APPOINTMENT_DATA['DATE'] = (isset($_POST['APPOINTMENT_DATE']) && !empty($_POST['APPOINTMENT_DATE'])) ? date('Y-m-d', strtotime($_POST['APPOINTMENT_DATE'])) : date('Y-m-d');
    $APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
    $APPOINTMENT_DATA['COMMENT'] = (isset($_POST['COMMENT']) && !empty($_POST['COMMENT'])) ? $_POST['COMMENT'] : '';
    $APPOINTMENT_DATA['INTERNAL_COMMENT'] = (isset($_POST['INTERNAL_COMMENT']) && !empty($_POST['INTERNAL_COMMENT'])) ? $_POST['INTERNAL_COMMENT'] : '';
    $APPOINTMENT_DATA['ACTIVE'] = 1;
    $APPOINTMENT_DATA['APPOINTMENT_TYPE'] = 'AD-HOC';
    $APPOINTMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
    $APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");

    $START_TIME_ARRAY = explode(',', $_POST['START_TIME']);
    $END_TIME_ARRAY = explode(',', $_POST['END_TIME']);

    for ($i = 0; $i < count($START_TIME_ARRAY); $i++) {
        $APPOINTMENT_DATA['START_TIME'] = $START_TIME_ARRAY[$i];
        $APPOINTMENT_DATA['END_TIME'] = $END_TIME_ARRAY[$i];

        if ($APPOINTMENT_DATA['START_TIME'] != $APPOINTMENT_DATA['END_TIME']) {
            $APPOINTMENT_DATA['SERIAL_NUMBER'] = getAppointmentSerialNumber($PK_USER_MASTER);
            db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'insert');
            $PK_APPOINTMENT_MASTER = $db_account->insert_ID();

            checkAdhocAppointmentStatus($PK_APPOINTMENT_MASTER, $PK_SERVICE_MASTER, $PK_SERVICE_CODE, $PK_USER_MASTER, $PK_LOCATION);

            $APPOINTMENT_SP_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
            $APPOINTMENT_SP_DATA['PK_USER'] = $_POST['PK_SERVICE_PROVIDER'];
            db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $APPOINTMENT_SP_DATA, 'insert');

            $APPOINTMENT_CUSTOMER_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
            $APPOINTMENT_CUSTOMER_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
            db_perform_account('DOA_APPOINTMENT_CUSTOMER', $APPOINTMENT_CUSTOMER_DATA, 'insert');
        }
    }
} else {
    $PK_ENROLLMENT_MASTER_ARRAY = explode(',', $_POST['PK_ENROLLMENT_MASTER']);
    $PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER_ARRAY[0];
    $PK_ENROLLMENT_SERVICE = $PK_ENROLLMENT_MASTER_ARRAY[1];
    $PK_SERVICE_MASTER = $PK_ENROLLMENT_MASTER_ARRAY[2];
    $PK_SERVICE_CODE = $PK_ENROLLMENT_MASTER_ARRAY[3];

    $PK_USER_MASTER = 0;
    $SCHEDULING_CODE = explode(',', $_POST['PK_SCHEDULING_CODE']);
    $PK_SCHEDULING_CODE = $SCHEDULING_CODE[0];
    $DURATION = $SCHEDULING_CODE[1];

    $PK_USER_MASTER = $_POST['SELECTED_CUSTOMER_ID'];

    $START_TIME_ARRAY = explode(',', $_POST['START_TIME']);
    $END_TIME_ARRAY = explode(',', $_POST['END_TIME']);

    $enrollment_location = $db_account->Execute("SELECT PK_LOCATION, CHARGE_TYPE FROM `DOA_ENROLLMENT_MASTER` WHERE PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
    if ($enrollment_location->RecordCount() > 0) {
        $PK_LOCATION = $enrollment_location->fields['PK_LOCATION'];
    } else {
        $PK_LOCATION = 0;
    }

    $enrollment_service_data = $db_account->Execute("SELECT `NUMBER_OF_SESSION` FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_SERVICE` = " . $PK_ENROLLMENT_SERVICE);
    if ($enrollment_location->fields['CHARGE_TYPE'] == 'Membership') {
        $NUMBER_OF_SESSION = 99;
    } else {
        $NUMBER_OF_SESSION = $enrollment_service_data->fields['NUMBER_OF_SESSION'];
    }
    $SESSION_CREATED = getAllSessionCreatedCount($PK_ENROLLMENT_SERVICE, 'NORMAL');
    $SESSION_LEFT = $NUMBER_OF_SESSION - $SESSION_CREATED;





    $APPOINTMENT_DATE_ARRAY = [];
    $REPEAT = $_POST['REPEAT'];
    if ($REPEAT == 'Custom') {

        $STARTING_ON = $_POST['APPOINTMENT_DATE'];
        $LENGTH = isset($_POST['OCCURRENCE_AFTER']) ? $_POST['OCCURRENCE_AFTER'] : 12;
        $FREQUENCY = 'month';
        $END_DATE = isset($_POST['END_ON_APPOINTMENT_DATE']) ? date('Y-m-d', strtotime($_POST['END_ON_APPOINTMENT_DATE'])) : date('Y-m-d', strtotime('+ ' . $LENGTH . ' ' . $FREQUENCY, strtotime($STARTING_ON)));


        $OCCURRENCE = 'WEEKLY';
        if (!empty($OCCURRENCE)) {
            $APPOINTMENT_DATE = date('Y-m-d', strtotime($STARTING_ON));
            if ($OCCURRENCE == 'WEEKLY') {
                if (isset($_POST['DAYS'])) {
                    $DAYS = $_POST['DAYS'];
                } else {
                    $DAYS[] = strtolower(date('l', strtotime($STARTING_ON)));
                }
                while ($APPOINTMENT_DATE < $END_DATE) {
                    $appointment_day = date('l', strtotime($APPOINTMENT_DATE));
                    if (in_array(strtolower($appointment_day), $DAYS)) {
                        $APPOINTMENT_DATE_ARRAY[] = $APPOINTMENT_DATE;
                    }
                    $APPOINTMENT_DATE = date('Y-m-d', strtotime('+1 day ', strtotime($APPOINTMENT_DATE)));
                }
            } else {
                $OCCURRENCE_DAYS = (isset($_POST['OCCURRENCE_DAYS'])) ? 7 : $_POST['OCCURRENCE_DAYS'];

                while ($APPOINTMENT_DATE < $END_DATE) {
                    $APPOINTMENT_DATE_ARRAY[] = $APPOINTMENT_DATE;
                    $APPOINTMENT_DATE = date('Y-m-d', strtotime('+ ' . $OCCURRENCE_DAYS . ' day', strtotime($APPOINTMENT_DATE)));
                    //echo $APPOINTMENT_DATE . "<br>";
                }
            }
        }
    } else {
        $APPOINTMENT_DATE_ARRAY[] = (isset($_POST['APPOINTMENT_DATE']) && !empty($_POST['APPOINTMENT_DATE'])) ? date('Y-m-d', strtotime($_POST['APPOINTMENT_DATE'])) : date('Y-m-d');
    }

    $APPOINTMENT_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
    $APPOINTMENT_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
    $APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
    $APPOINTMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
    $APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
    $APPOINTMENT_DATA['COMMENT'] = (isset($_POST['COMMENT']) && !empty($_POST['COMMENT'])) ? $_POST['COMMENT'] : '';
    $APPOINTMENT_DATA['INTERNAL_COMMENT'] = (isset($_POST['INTERNAL_COMMENT']) && !empty($_POST['INTERNAL_COMMENT'])) ? $_POST['INTERNAL_COMMENT'] : '';
    $APPOINTMENT_DATA['ACTIVE'] = 1;
    $APPOINTMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
    $APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");

    $TOTAL_APPOINTMENT_TO_CREATE = isset($_POST['OCCURRENCE_AFTER']) ? $_POST['OCCURRENCE_AFTER'] : count($APPOINTMENT_DATE_ARRAY);
    for ($n = 0; $n < $TOTAL_APPOINTMENT_TO_CREATE; $n++) {
        $APPOINTMENT_DATA['DATE'] = $APPOINTMENT_DATE_ARRAY[$n];
        for ($i = 0; $i < count($START_TIME_ARRAY); $i++) {
            if ($i < $SESSION_LEFT) {
                $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_SERVICE;
                $APPOINTMENT_DATA['APPOINTMENT_TYPE'] = 'NORMAL';
            } else {
                $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
                $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = 0;
                $APPOINTMENT_DATA['APPOINTMENT_TYPE'] = 'AD-HOC';
            }

            $APPOINTMENT_DATA['SERIAL_NUMBER'] = getAppointmentSerialNumber($PK_USER_MASTER);
            $APPOINTMENT_DATA['START_TIME'] = $START_TIME_ARRAY[$i];
            $APPOINTMENT_DATA['END_TIME'] = $END_TIME_ARRAY[$i];

            if ($APPOINTMENT_DATA['START_TIME'] != $APPOINTMENT_DATA['END_TIME']) {
                db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'insert');
                $PK_APPOINTMENT_MASTER = $db_account->insert_ID();

                $APPOINTMENT_SP_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                $APPOINTMENT_SP_DATA['PK_USER'] = $_POST['PK_SERVICE_PROVIDER'];
                db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $APPOINTMENT_SP_DATA, 'insert');

                $APPOINTMENT_CUSTOMER_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                $APPOINTMENT_CUSTOMER_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                db_perform_account('DOA_APPOINTMENT_CUSTOMER', $APPOINTMENT_CUSTOMER_DATA, 'insert');
            }
        }
    }

    markAppointmentPaid($APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE']);

    if ($PK_USER_MASTER > 0) {
        $db_account->Execute("UPDATE DOA_APPOINTMENT_MASTER am JOIN DOA_APPOINTMENT_CUSTOMER ac ON am.PK_APPOINTMENT_MASTER = ac.PK_APPOINTMENT_MASTER SET am.PK_ENROLLMENT_MASTER = 0, am.PK_ENROLLMENT_SERVICE = 0, am.APPOINTMENT_TYPE = 'AD-HOC' WHERE am.APPOINTMENT_TYPE = 'NORMAL' AND ac.PK_USER_MASTER = '$PK_USER_MASTER'");
        $enrollment_data = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_MASTER WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY ENROLLMENT_DATE ASC");
        while (!$enrollment_data->EOF) {
            $PK_ENROLLMENT_MASTER = $enrollment_data->fields['PK_ENROLLMENT_MASTER'];
            markAdhocAppointmentNormal($PK_ENROLLMENT_MASTER);
            $enrollment_data->MoveNext();
        }
    }
}

//rearrangeSerialNumber($_POST['PK_ENROLLMENT_MASTER'], $price_per_session);

header("location:../../calendar.php?date=" . $APPOINTMENT_DATE_ARRAY[0]);
