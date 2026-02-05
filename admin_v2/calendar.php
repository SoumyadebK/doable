<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $upload_path;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
$LOCATION_ARRAY = explode(',', $DEFAULT_LOCATION_ID);

$title = "All Appointments";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || (in_array($_SESSION['PK_ROLES'], [1, 4]) && $_SESSION['PK_ROLES'] != 3)) {
    header("location:../login.php");
    exit;
}

$redirect_date = (!empty($_GET['date'])) ? date('Y-m-d', strtotime($_GET['date'])) : "";
$header = 'all_schedules.php';

$SERVICE_PROVIDER_ID = ' ';
if (isset($_GET['SERVICE_PROVIDER_ID']) && $_GET['SERVICE_PROVIDER_ID'] != '') {
    $service_providers = implode(',', $_GET['SERVICE_PROVIDER_ID']);
    $SERVICE_PROVIDER_ID = " AND DOA_USERS.PK_USER IN (" . $service_providers . ") ";
}

$appointment_type = '';

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveAdhocAppointmentData') {
    unset($_POST['TIME']);
    unset($_POST['FUNCTION_NAME']);
    if (empty($_POST['START_TIME']) || empty($_POST['END_TIME'])) {
        unset($_POST['START_TIME']);
        unset($_POST['END_TIME']);
    }
    $session_cost = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_SERVICE_MASTER = '$_POST[PK_SERVICE_MASTER]' AND PK_SERVICE_CODE = '$_POST[PK_SERVICE_CODE]'");
    $price_per_session = $session_cost->fields['PRICE_PER_SESSION'];
    if (empty($_POST['PK_APPOINTMENT_MASTER'])) {
        $_POST['PK_APPOINTMENT_STATUS'] = 1;
        $_POST['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $_POST['ACTIVE'] = 1;
        $_POST['CREATED_BY']  = $_SESSION['PK_USER'];
        $_POST['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_APPOINTMENT_MASTER', $_POST, 'insert');
    } else {
        if (!file_exists('../' . $upload_path . '/appointment_image/')) {
            mkdir('../' . $upload_path . '/appointment_image/', 0777, true);
            chmod('../' . $upload_path . '/appointment_image/', 0777);
        }
        //$_POST['ACTIVE'] = $_POST['ACTIVE'];
        if ($_FILES['IMAGE']['name'] != '') {
            $extn             = explode(".", $_FILES['IMAGE']['name']);
            $iindex            = count($extn) - 1;
            $rand_string     = time() . "-" . rand(100000, 999999);
            $file11            = 'appointment_image_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
            $extension       = strtolower($extn[$iindex]);

            if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
                $image_path    = '../' . $upload_path . '/appointment_image/' . $file11;
                move_uploaded_file($_FILES['IMAGE']['tmp_name'], $image_path);
                $_POST['IMAGE'] = $image_path;
            }
        }
        $_POST['EDITED_BY']    = $_SESSION['PK_USER'];
        $_POST['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_APPOINTMENT_MASTER', $_POST, 'update', " PK_APPOINTMENT_MASTER =  '$_POST[PK_APPOINTMENT_MASTER]'");

        if ($_POST['PK_APPOINTMENT_STATUS'] == 2 || ($_POST['PK_APPOINTMENT_STATUS'] == 4 && $_POST['NO_SHOW'] == 'Charge')) {
            $enrollment_balance = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '$_POST[PK_ENROLLMENT_MASTER]'");
            if ($enrollment_balance->RecordCount() > 0) {
                $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_USED'] = $enrollment_balance->fields['TOTAL_BALANCE_USED'] + $price_per_session;
                $ENROLLMENT_BALANCE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                $ENROLLMENT_BALANCE_DATA['EDITED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
            }
        }
    }

    //rearrangeSerialNumber($_POST['PK_ENROLLMENT_MASTER'], $price_per_session);

    header("location:all_schedules.php?date=" . date('m/d/Y', strtotime($_POST['START_DATE'])));
}

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveSpecialAppointmentData') {
    $SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
    $SPECIAL_APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
    $SPECIAL_APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
    $SPECIAL_APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
    $SPECIAL_APPOINTMENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
    $PK_SPECIAL_APPOINTMENT = $_POST['PK_SPECIAL_APPOINTMENT'];

    $SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
    $SPECIAL_APPOINTMENT_DATA['EDITED_BY']    = $_SESSION['PK_USER'];
    $SPECIAL_APPOINTMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");

    $SPECIAL_APPOINTMENT_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
    db_perform_account('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'update', " PK_SPECIAL_APPOINTMENT =  '$PK_SPECIAL_APPOINTMENT'");

    header("location:all_schedules.php?date=" . date('m/d/Y', strtotime($_POST['DATE'])));
}

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveGroupClassData') {
    $PK_APPOINTMENT_MASTER = $_POST['PK_APPOINTMENT_MASTER'];
    $time = $db_account->Execute("SELECT DURATION FROM DOA_SCHEDULING_CODE WHERE PK_SCHEDULING_CODE = " . $_POST['PK_SCHEDULING_CODE']);
    $duration = $time->fields['DURATION'];
    $startTime = date('H:i:s', strtotime($_POST['START_TIME']));
    if ($duration > 0) {
        $convertedTime = date('H:i:s', strtotime('+' . $duration . 'minutes', strtotime($startTime)));
    } else {
        $convertedTime = date('H:i:s', strtotime('+30 minutes', strtotime($startTime)));
    }
    $GROUP_CLASS_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
    $GROUP_CLASS_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
    $GROUP_CLASS_DATA['END_TIME'] = date('H:i:s', strtotime($convertedTime));
    $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
    $GROUP_CLASS_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
    $GROUP_CLASS_DATA['COMMENT'] = $_POST['COMMENT'];
    $GROUP_CLASS_DATA['INTERNAL_COMMENT'] = $_POST['INTERNAL_COMMENT'];

    if (!file_exists('../' . $upload_path . '/appointment_image/')) {
        mkdir('../' . $upload_path . '/appointment_image/', 0777, true);
        chmod('../' . $upload_path . '/appointment_image/', 0777);
    }
    if ($_FILES['IMAGE']['name'] != '') {
        $extn             = explode(".", $_FILES['IMAGE']['name']);
        $iindex            = count($extn) - 1;
        $rand_string     = time() . "-" . rand(100000, 999999);
        $file11            = 'appointment_image_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
        $extension       = strtolower($extn[$iindex]);

        if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
            $image_path    = '../' . $upload_path . '/appointment_image/' . $file11;
            move_uploaded_file($_FILES['IMAGE']['tmp_name'], $image_path);
            $GROUP_CLASS_DATA['IMAGE'] = $image_path;
        }
    }

    if ($_FILES['IMAGE_2']['name'] != '') {
        $extn             = explode(".", $_FILES['IMAGE_2']['name']);
        $iindex            = count($extn) - 1;
        $rand_string     = time() . "-" . rand(100000, 999999);
        $file11            = 'appointment_image_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
        $extension       = strtolower($extn[$iindex]);

        if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
            $image_path    = '../' . $upload_path . '/appointment_image/' . $file11;
            move_uploaded_file($_FILES['IMAGE_2']['tmp_name'], $image_path);
            $GROUP_CLASS_DATA['IMAGE_2'] = $image_path;
        }
    }

    if (!file_exists('../' . $upload_path . '/appointment_video/')) {
        mkdir('../' . $upload_path . '/appointment_video/', 0777, true);
        chmod('../' . $upload_path . '/appointment_video/', 0777);
    }
    if ($_FILES['VIDEO']['name'] != '') {
        $extn             = explode(".", $_FILES['VIDEO']['name']);
        $iindex            = count($extn) - 1;
        $rand_string     = time() . "-" . rand(100000, 999999);
        $file11            = 'appointment_video_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
        $extension       = strtolower($extn[$iindex]);

        if ($extension == "mp4" || $extension == "avi" || $extension == "mov" || $extension == "wmv") {
            $video_path    = '../' . $upload_path . '/appointment_video/' . $file11;
            move_uploaded_file($_FILES["VIDEO"]["tmp_name"], $video_path);
            $GROUP_CLASS_DATA['VIDEO'] = $video_path;
        }
    }

    if ($_FILES['VIDEO_2']['name'] != '') {
        $extn             = explode(".", $_FILES['VIDEO_2']['name']);
        $iindex            = count($extn) - 1;
        $rand_string     = time() . "-" . rand(100000, 999999);
        $file11            = 'appointment_video_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
        $extension       = strtolower($extn[$iindex]);

        if ($extension == "mp4" || $extension == "avi" || $extension == "mov" || $extension == "wmv") {
            $video_path    = '../' . $upload_path . '/appointment_video/' . $file11;
            move_uploaded_file($_FILES["VIDEO_2"]["tmp_name"], $video_path);
            $GROUP_CLASS_DATA['VIDEO_2'] = $video_path;
        }
    }

    $GROUP_CLASS_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
    $GROUP_CLASS_DATA['EDITED_ON'] = date("Y-m-d H:i");

    if (isset($_POST['STANDING_ID']) && ($_POST['STANDING_ID'] > 0)) {
        db_perform_account('DOA_APPOINTMENT_MASTER', $GROUP_CLASS_DATA, 'update', " STANDING_ID =  '$_POST[STANDING_ID]'");
    } else {
        $GROUP_CLASS_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
        db_perform_account('DOA_APPOINTMENT_MASTER', $GROUP_CLASS_DATA, 'update', " PK_APPOINTMENT_MASTER =  '$PK_APPOINTMENT_MASTER'");
    }

    $existing_customer = (!empty($_POST['EXISTING_CUSTOMER'])) ? explode(',', $_POST['EXISTING_CUSTOMER']) : [];
    $existing_partner = (!empty($_POST['EXISTING_PARTNER'])) ? explode(',', $_POST['EXISTING_PARTNER']) : [];

    $SELECTED_CUSTOMERS = (!empty($_POST['PK_USER_MASTER'])) ? explode(',', $_POST['PK_USER_MASTER']) : [];
    $SELECTED_PARTNERS = (!empty($_POST['PARTNER'])) ? explode(',', $_POST['PARTNER']) : [];

    $customer_to_add = array_values(array_diff($SELECTED_CUSTOMERS, $existing_customer));
    $customer_to_remove = array_values(array_diff($existing_customer, $SELECTED_CUSTOMERS));

    $partner_to_add = array_values(array_diff($SELECTED_PARTNERS, $existing_partner));
    $partner_to_remove = array_values(array_diff($existing_partner, $SELECTED_PARTNERS));

    for ($i = 0; $i < count($SELECTED_CUSTOMERS); $i++) {
        if ($_POST['PK_APPOINTMENT_STATUS'] == 2) {
            updateSessionCompletedCountGroupClass($PK_APPOINTMENT_MASTER, $SELECTED_CUSTOMERS[$i]);
        } else {
            updateSessionCreatedCountGroupClass($PK_APPOINTMENT_MASTER, $SELECTED_CUSTOMERS[$i]);
        }
    }

    for ($i = 0; $i < count($SELECTED_PARTNERS); $i++) {
        if ($_POST['PK_APPOINTMENT_STATUS'] == 2) {
            updateSessionCompletedCountGroupClass($PK_APPOINTMENT_MASTER, $SELECTED_PARTNERS[$i]);
        } else {
            updateSessionCreatedCountGroupClass($PK_APPOINTMENT_MASTER, $SELECTED_PARTNERS[$i]);
        }
    }

    for ($j = 0; $j < count($customer_to_add); $j++) {
        $GROUP_CLASS_CUSTOMER_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
        $GROUP_CLASS_CUSTOMER_DATA['PK_USER_MASTER'] = $customer_to_add[$j];
        $GROUP_CLASS_CUSTOMER_DATA['IS_PARTNER'] = 0;
        db_perform_account('DOA_APPOINTMENT_CUSTOMER', $GROUP_CLASS_CUSTOMER_DATA, 'insert');

        $CUSTOMER_UPDATE_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
        $user_data = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $customer_to_add[$j]);
        $details = '(' . $user_data->fields['NAME'] . ' Added By ' . $_SESSION['FIRST_NAME'] . ' ' . $_SESSION['LAST_NAME'] . ' at ' . date("m/d/Y h:i A") . ')';
        $CUSTOMER_UPDATE_DATA['DETAILS'] = $details;
        db_perform_account('DOA_APPOINTMENT_CUSTOMER_UPDATE_HISTORY', $CUSTOMER_UPDATE_DATA, 'insert');
    }
    for ($j = 0; $j < count($customer_to_remove); $j++) {
        $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_CUSTOMER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER' AND `PK_USER_MASTER` = '$customer_to_remove[$j]' AND IS_PARTNER = 0");
        $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_ENROLLMENT` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER' AND `PK_USER_MASTER` = '$customer_to_remove[$j]'");

        $CUSTOMER_UPDATE_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
        $user_data = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $customer_to_remove[$j]);
        $details = '(' . $user_data->fields['NAME'] . ' Removed By ' . $_SESSION['FIRST_NAME'] . ' ' . $_SESSION['LAST_NAME'] . ' at ' . date("m/d/Y h:i A") . ')';
        $CUSTOMER_UPDATE_DATA['DETAILS'] = $details;
        db_perform_account('DOA_APPOINTMENT_CUSTOMER_UPDATE_HISTORY', $CUSTOMER_UPDATE_DATA, 'insert');
    }

    for ($j = 0; $j < count($partner_to_add); $j++) {
        $GROUP_CLASS_PARTNER_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
        $GROUP_CLASS_PARTNER_DATA['PK_USER_MASTER'] = $partner_to_add[$j];
        $GROUP_CLASS_PARTNER_DATA['IS_PARTNER'] = 1;
        db_perform_account('DOA_APPOINTMENT_CUSTOMER', $GROUP_CLASS_PARTNER_DATA, 'insert');

        $CUSTOMER_UPDATE_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
        $partner_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = " . $partner_to_add[$j]);
        $details = '(' . $partner_data->fields['PARTNER_FIRST_NAME'] . ' ' . $partner_data->fields['PARTNER_LAST_NAME'] . ' Added By ' . $_SESSION['FIRST_NAME'] . ' ' . $_SESSION['LAST_NAME'] . ' at ' . date("m/d/Y h:i A") . ')';
        $CUSTOMER_UPDATE_DATA['DETAILS'] = $details;
        db_perform_account('DOA_APPOINTMENT_CUSTOMER_UPDATE_HISTORY', $CUSTOMER_UPDATE_DATA, 'insert');
    }
    for ($j = 0; $j < count($partner_to_remove); $j++) {
        $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_CUSTOMER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER' AND `PK_USER_MASTER` = '$partner_to_remove[$j]' AND IS_PARTNER = 1");

        $is_customer_added = $db_account->Execute("SELECT * FROM `DOA_APPOINTMENT_ENROLLMENT` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER' AND `PK_USER_MASTER` = '$partner_to_remove[$j]'");
        if ($is_customer_added->RecordCount() == 0) {
            $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_ENROLLMENT` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER' AND `PK_USER_MASTER` = '$partner_to_remove[$j]'");
        }

        $CUSTOMER_UPDATE_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
        $partner_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = " . $partner_to_remove[$j]);
        $details = '(' . $partner_data->fields['PARTNER_FIRST_NAME'] . ' ' . $partner_data->fields['PARTNER_LAST_NAME'] . ' Removed By ' . $_SESSION['FIRST_NAME'] . ' ' . $_SESSION['LAST_NAME'] . ' at ' . date("m/d/Y h:i A") . ')';
        $CUSTOMER_UPDATE_DATA['DETAILS'] = $details;
        db_perform_account('DOA_APPOINTMENT_CUSTOMER_UPDATE_HISTORY', $CUSTOMER_UPDATE_DATA, 'insert');
    }

    $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_SERVICE_PROVIDER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER'");
    if (isset($_POST['SERVICE_PROVIDER_ID'])) {
        for ($k = 0; $k < count($_POST['SERVICE_PROVIDER_ID']); $k++) {
            $GROUP_CLASS_USER_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
            $GROUP_CLASS_USER_DATA['PK_USER'] = $_POST['SERVICE_PROVIDER_ID'][$k];
            db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $GROUP_CLASS_USER_DATA, 'insert');
        }
    }

    if (isset($_POST['PK_APPOINTMENT_STATUS'])) {
        $APPOINTMENT_STATUS_HISTORY_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
        $APPOINTMENT_STATUS_HISTORY_DATA['PK_USER'] = $_SESSION['PK_USER'];
        $APPOINTMENT_STATUS_HISTORY_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
        $APPOINTMENT_STATUS_HISTORY_DATA['TIME_STAMP'] = date("Y-m-d H:i");
        db_perform_account('DOA_APPOINTMENT_STATUS_HISTORY', $APPOINTMENT_STATUS_HISTORY_DATA, 'insert');
    }

    header("location:all_schedules.php?date=" . date('m/d/Y', strtotime($_POST['DATE'])));
}

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveEventData') {
    $PK_EVENT = $_POST['PK_EVENT'];
    if (!empty($_POST)) {
        $EVENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $EVENT_DATA['HEADER'] = $_POST['HEADER'];
        $EVENT_DATA['PK_EVENT_TYPE'] = $_POST['PK_EVENT_TYPE'];
        $EVENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
        $EVENT_DATA['SHARE_WITH_CUSTOMERS'] = isset($_POST['SHARE_WITH_CUSTOMERS']) ? 1 : 0;
        $EVENT_DATA['SHARE_WITH_SERVICE_PROVIDERS'] = isset($_POST['SHARE_WITH_SERVICE_PROVIDERS']) ? 1 : 0;
        $EVENT_DATA['SHARE_WITH_EMPLOYEES'] = isset($_POST['SHARE_WITH_EMPLOYEES']) ? 1 : 0;
        $EVENT_DATA['START_DATE'] = date('Y-m-d', strtotime($_POST['START_DATE']));
        $EVENT_DATA['END_DATE'] = !empty($_POST['END_DATE']) ? date('Y-m-d', strtotime($_POST['END_DATE'])) : NULL;
        $EVENT_DATA['ALL_DAY'] = $_POST['ALL_DAY'] ?? 0;
        if ($EVENT_DATA['ALL_DAY'] == 1) {
            $EVENT_DATA['START_TIME'] = '00:00:00';
            $EVENT_DATA['END_TIME'] = '23:30:00';
        } else {
            $EVENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
            $EVENT_DATA['END_TIME'] = !empty($_POST['END_TIME']) ? date('H:i:s', strtotime($_POST['END_TIME'])) : NULL;
        }
        if (empty($PK_EVENT)) {
            $EVENT_DATA['ACTIVE'] = 1;
            $EVENT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
            $EVENT_DATA['CREATED_ON']  = date("Y-m-d H:i");
            db_perform_account('DOA_EVENT', $EVENT_DATA, 'insert');
            $PK_EVENT = $db_account->insert_ID();
        } else {
            $EVENT_DATA['ACTIVE'] = $_POST['ACTIVE'];
            $EVENT_DATA['EDITED_BY']    = $_SESSION['PK_USER'];
            $EVENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_EVENT', $EVENT_DATA, 'update', " PK_EVENT =  '$PK_EVENT'");
            $PK_EVENT = $_POST['PK_EVENT'];
        }

        $db_account->Execute("DELETE FROM `DOA_EVENT_LOCATION` WHERE `PK_EVENT` = '$PK_EVENT'");
        if (isset($_POST['PK_LOCATION'])) {
            $PK_LOCATION = $_POST['PK_LOCATION'];
            for ($i = 0; $i < count($PK_LOCATION); $i++) {
                $EVENT_LOCATION_DATA['PK_EVENT'] = $PK_EVENT;
                $EVENT_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION[$i];
                db_perform_account('DOA_EVENT_LOCATION', $EVENT_LOCATION_DATA, 'insert');
            }
        }

        if (!file_exists('../' . $upload_path . '/event_image/')) {
            mkdir('../' . $upload_path . '/event_image/', 0777, true);
            chmod('../' . $upload_path . '/event_image/', 0777);
        }

        $db_account->Execute("DELETE FROM `DOA_EVENT_IMAGE` WHERE `PK_EVENT` = '$PK_EVENT'");
        for ($i = 0; $i < count($_FILES['IMAGE']['name']); $i++) {
            $EVENT_IMAGE_DATA['PK_EVENT'] = $PK_EVENT;
            if (!empty($_FILES['IMAGE']['name'][$i])) {
                $extn             = explode(".", $_FILES['IMAGE']['name'][$i]);
                $iindex            = count($extn) - 1;
                $rand_string     = time() . "-" . rand(100000, 999999);
                $file11            = 'event_image_' . $PK_EVENT . '_' . $rand_string . "." . $extn[$iindex];
                $extension       = strtolower($extn[$iindex]);

                $image_path    = '../' . $upload_path . '/event_image/' . $file11;
                move_uploaded_file($_FILES['IMAGE']['tmp_name'][$i], $image_path);
                $EVENT_IMAGE_DATA['IMAGE'] = $image_path;
            } else {
                $EVENT_IMAGE_DATA['IMAGE'] = $_POST['IMAGE_PATH'][$i];
            }
            $EVENT_IMAGE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
            $EVENT_IMAGE_DATA['CREATED_ON']  = date("Y-m-d H:i");
            db_perform_account('DOA_EVENT_IMAGE', $EVENT_IMAGE_DATA, 'insert');
        }
        header("location:all_schedules.php?date=" . date('m/d/Y', strtotime($_POST['START_DATE'])));
    }
}

$dayConfig = [];
$location_operational_hour = $db_account->Execute("SELECT MIN(DOA_OPERATIONAL_HOUR.OPEN_TIME) AS OPEN_TIME, MAX(DOA_OPERATIONAL_HOUR.CLOSE_TIME) AS CLOSE_TIME, DAY_NUMBER FROM DOA_OPERATIONAL_HOUR WHERE CLOSED = 0 AND PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") GROUP BY DAY_NUMBER");
if ($location_operational_hour->RecordCount() > 0) {
    while (!$location_operational_hour->EOF) {
        $dayConfig[$location_operational_hour->fields['DAY_NUMBER']] = [
            'minTime' => $location_operational_hour->fields['OPEN_TIME'],
            'maxTime' => $location_operational_hour->fields['CLOSE_TIME']
        ];
        $location_operational_hour->MoveNext();
    }
}

if (isset($_GET['CHOOSE_DATE']) && $_GET['CHOOSE_DATE'] != '') {
    $CHOOSE_DATE = $_GET['CHOOSE_DATE'];
} else {
    $CHOOSE_DATE = date("Y-m-d");
}

$interval = $db->Execute("SELECT MIN(TIME_SLOT_INTERVAL) AS TIME_SLOT_INTERVAL FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")");
if ($interval->fields['TIME_SLOT_INTERVAL'] == "00:00:00") {
    $INTERVAL = "00:15:00";
} else {
    $INTERVAL = $interval->fields['TIME_SLOT_INTERVAL'];
}


$location_operational_hour = $db_account->Execute("SELECT MIN(DOA_OPERATIONAL_HOUR.OPEN_TIME) AS OPEN_TIME, MAX(DOA_OPERATIONAL_HOUR.CLOSE_TIME) AS CLOSE_TIME, DAY_NUMBER FROM DOA_OPERATIONAL_HOUR WHERE CLOSED = 0 AND PK_LOCATION = " . $DEFAULT_LOCATION_ID);
if ($location_operational_hour->RecordCount() > 0) {
    $minTime = $location_operational_hour->fields['OPEN_TIME'];
    $maxTime = $location_operational_hour->fields['CLOSE_TIME'];
} else {
    $minTime = '00:00:00';
    $maxTime = '24:00:00';
}

$payment_gateway_data = getPaymentGatewayData();

$PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
$GATEWAY_MODE  = $payment_gateway_data->fields['GATEWAY_MODE'];

$SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];

$AUTHORIZE_LOGIN_ID         = $payment_gateway_data->fields['LOGIN_ID']; //"4Y5pCy8Qr";
$AUTHORIZE_TRANSACTION_KEY     = $payment_gateway_data->fields['TRANSACTION_KEY']; //"4ke43FW8z3287HV5";
$AUTHORIZE_CLIENT_KEY         = $payment_gateway_data->fields['AUTHORIZE_CLIENT_KEY']; //"8ZkyJnT87uFztUz56B4PfgCe7yffEZA4TR5dv8ALjqk5u9mr6d8Nmt8KHyp8s9Ay";

$MERCHANT_ID            = $payment_gateway_data->fields['MERCHANT_ID'];
$API_KEY                = $payment_gateway_data->fields['API_KEY'];
$PUBLIC_API_KEY         = $payment_gateway_data->fields['PUBLIC_API_KEY'];
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php include 'layout/header.php'; ?>

<script src='../assets/full_calendar_new/moment.min.js'></script>

<link href='../assets/fullcalendar4/fullcalendar.min.css' rel='stylesheet' />
<link href='../assets/fullcalendar4/scheduler.min.css' rel='stylesheet' />

<script src='../assets/fullcalendar4/fullcalendar.min.js'></script>
<script src='../assets/fullcalendar4/scheduler.min.js'></script>

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

    .fc-time-grid-event .fc-content {
        color: #000000 !important;
    }

    .fc-event .fc-content {
        color: #000000 !important;
    }

    .SumoSelect {
        width: 100%;
    }

    #add_buttons {
        z-index: 500;
    }

    .fc-more-popover .fc-event-container {
        max-height: 550px;
        overflow: scroll;
    }

    .fc-event {
        border: none !important;
        border-radius: 10px !important;
        margin: 1px 0px 1px 2px !important;
        text-align: left !important;
        padding: 8px !important;
    }

    .fc-content {
        margin: 5px !important;
    }

    .fc-resource-cell {
        padding-bottom: 10px !important;
    }

    .fc-time-grid-event .fc-time {
        font-size: 11px;
    }

    .fc-content .fc-title {
        margin-top: 3px;
        line-height: 19px;
        font-size: 12px;
    }
</style>
<style>
    .list-select {
        height: 30px;
        width: 100%;
        line-height: 2em;
        border: 1px solid #ccc;
        margin: 0;
        list-style: none;
        padding-left: 0;
    }

    .list-select li {
        padding: 1px 10px;
        z-index: 2;
    }

    .list-select li:not(.init) {
        float: left;
        width: 100%;
        display: none;
        *background: #ddd;
        border-top: 1px solid #eeecec;
    }

    .list-select li:not(.init):hover,
    .list-select li.selected:not(.init) {
        background: #09f;
        border-top: 1px solid #eeecec;
    }

    li.init {
        cursor: pointer;
    }
</style>
<style>
    .search-container {
        display: flex;
        align-items: center;
        gap: 10px;
        /* Default gap for desktop */
    }

    .search-button:hover {
        background-color: #0056b3;
    }

    /* Media query for tablets (for example, max-width 768px) */
    @media (max-width: 768px) {
        .search-container {
            gap: 8px;
            /* Reduced gap for tablet screens */
        }

        .SERVICE_PROVIDER_ID {
            width: 150px;
            /* Adjust input width for tablet */
        }

        .btn {
            font-size: 14px;
            /* Smaller button size for tablets */
        }
    }
</style>

<style>
    .clearfix {
        display: flex;
        flex-wrap: nowrap;

    }

    .clearfix .box {
        background-color: #f1f1f1;
        height: 37px;
        text-align: center;
        padding-right: 10px;
        padding-left: 0px;
    }



    .radio-buttons {
        display: flex;
        gap: 15px;
    }

    .radio-buttons input[type="radio"] {
        display: none;
    }

    .radio-buttons label {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0px 15px;
        border-radius: 25px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        border: 2px solid #ccc;
        transition: all 0.3s ease;
        background: #f9f9f9;
        color: #666666ff;
    }

    .radio-buttons input[type="radio"]:checked+label {
        background: #39b54a;
        border-color: #39b54a;
        color: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .radio-buttons label:hover {
        transform: scale(1.05);
    }
</style>


<!-- View Appointment Css -->
<style>
    /* ================================
   TOOLBAR
================================ */
    .calendar-header {
        /* background: #fff;
        border-radius: 15px; */
        padding: 0px 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        /* box-shadow: 0 1px 4px rgba(0, 0, 0, .08); */
        flex-wrap: nowrap;
        margin-top: 10px;
    }

    /* ================================
   PILL BUTTONS
================================ */
    .chip {
        border: 1px solid #e5e7eb;
        background: #fff;
        padding: 7px 20px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 25px;
        cursor: pointer;
        white-space: nowrap;
    }

    .chip-icon {
        width: 35px;
        height: 35px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    /* ================================
   DATE
================================ */
    .date-chip {
        font-weight: 600;
    }

    /* ================================
   AVATARS
================================ */
    .staff-avatars {
        display: flex;
        align-items: center;
        margin-left: 4px;
    }

    .staff-avatar {
        width: 21px;
        height: 21px;
        border-radius: 50%;
        font-size: 8px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        border: 2px solid #fff;
        margin-left: -6px;
    }

    .staff-avatar-me {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        border: 2px solid #fff;
        margin-left: -6px;
    }

    .a-ce {
        background: #ef4444
    }

    .a-rc {
        background: #f97316
    }

    .a-mc {
        background: #3b82f6
    }

    .a-pe {
        background: #39b54a
    }

    .a-more {
        background: #e5e7eb;
        color: #374151;
    }

    /* ================================
   VIEW TOGGLE
================================ */
    .view-toggle {
        display: flex;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        overflow: hidden;
    }

    .view-btn {
        padding: 6px 16px;
        border: none;
        background: #fff;
        font-size: 14px;
        color: #6b7280;
    }

    .view-btn.active {
        color: #39b54a;
        font-weight: 600;
    }



    .view-btn-icon {
        padding: 6px 16px;
        border: none;
        background: #fff;
        font-size: 14px;
        color: #6b7280;
    }

    .view-btn-icon.active {
        color: #39b54a;
        font-weight: 600;
    }

    /* ================================
   NEW APPOINTMENT
================================ */
    .btn-new {
        background: #39b54a;
        color: #fff;
        border-radius: 999px;
        padding: 8px 20px !important;
        font-size: 14px;
        font-weight: 600;
        border: none;
    }

    .SumoSelect .optWrapper {
        width: 200% !important;
    }

    /* ================================
   SEARCH FORM RESET
================================ */
    #search_form {
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        background: transparent !important;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        width: auto;
        min-width: auto;
    }

    .sp_badge {
        height: 25px;
        width: 25px;
        border-radius: 20px;
        padding: 6px 0px;
        font-size: 12px;
    }
</style>
<style>
    .overlay2 {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1049;
    }

    .side-drawer {
        position: fixed;
        top: 0;
        right: -500px;
        width: 500px;
        max-width: 90vw;
        height: 100vh;
        background: white;
        transition: right 0.3s ease;
        z-index: 1050;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
    }

    .side-drawer.open {
        right: 0;
    }

    .close-btn {
        font-size: 24px;
        cursor: pointer;
        background: none;
        border: none;
    }

    /* Make sure the drawer appears above calendar */
    .fc .fc-daygrid-day-frame,
    .fc .fc-timegrid-slot-lane {
        z-index: auto !important;
    }


    .side-drawer {
        margin-top: 70px;
        height: 92% !important;
        border-radius: 15px;
        max-width: 575px;
    }


    .edit-btn {
        font-size: 18px;
        color: #39b54a;
        margin-right: 5px;
    }

    .delete-btn {
        font-size: 18px;
        color: #ef4444;
    }

    .btn-icon {
        font-size: 18px;
        color: #6b7280;
    }

    .ext-tag {
        background-color: #eeebff;
        color: #8c75e7;
    }

    .pri-tag {
        background-color: #feebf4;
        color: #ed85b7;
    }

    .grp-tag {
        background-color: #ebf2ff;
        color: #6b82e2;
    }

    .f-12 {
        font-size: 12px;
    }
</style>

<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet" />

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="calendar-header">
            <button class="chip m-r-15" onclick="todayDate = new Date(); renderCalendar(todayDate);">Today</button>
            <button class="chip chip-icon" id="prevDay" onclick="if(calendar.view.type === 'agendaDay') { todayDate.setDate(todayDate.getDate() - 1); renderCalendar(todayDate); } else { calendar.prev(); setTimeout(function() { updateChooseDateInput(); }, 100); }"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>
            <button class="chip chip-icon m-r-20" id="nextDay" onclick="if(calendar.view.type === 'agendaDay') { todayDate.setDate(todayDate.getDate() + 1); renderCalendar(todayDate); } else { calendar.next(); setTimeout(function() { updateChooseDateInput(); }, 100); }"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>

            <form id="search_form">
                <input type="hidden" id="IS_SELECTED" value="0">
                <input type="text" id="CHOOSE_DATE" name="CHOOSE_DATE" class="chip date-chip m-r-15 datepicker-normal-calendar" placeholder="Choose Date" value="<?= !empty($_GET['date']) ? date('l, M d, Y', strtotime($_GET['date'])) : date('l, M d, Y') ?>" style="min-width: 240px;">

                <div class="chip m-r-15" style="height: 37px;">
                    <div class=" staff-avatars">
                        <div class="staff-avatar a-ce">CE</div>
                        <div class="staff-avatar a-rc">RC</div>
                        <div class="staff-avatar a-mc">MC</div>
                        <div class="staff-avatar a-pe">PE</div>
                        <div class="staff-avatar a-pe">PE</div>
                        <div class="staff-avatar a-pe">PE</div>
                        <div class="staff-avatar a-more">+2</div>
                    </div>

                    <select class="SERVICE_PROVIDER_ID multi_sumo_select" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID" onchange="$('#search_form').submit();" style="border:none;" multiple>
                        <?php
                        $row = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.DISPLAY_ORDER FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.DISPLAY_ORDER ASC");
                        while (!$row->EOF) { ?>
                            <option value="<?= $row->fields['PK_USER'] ?>" <?= (!empty($service_providers) && in_array($row->fields['PK_USER'], explode(',', $service_providers))) ? "selected" : "" ?>><?= $row->fields['NAME'] ?></option>
                        <?php $row->MoveNext();
                        } ?>
                    </select>
                </div>

                <span class="staff-avatar-me a-pe m-r-15">RG</span>

                <select class="chip m-r-15" name="STATUS_CODE" id="STATUS_CODE" onchange="$('#search_form').submit();" style="height: 37px; min-width: 150px;">
                    <option value="">Select Status</option>
                    <?php
                    $row = $db->Execute("SELECT * FROM DOA_APPOINTMENT_STATUS WHERE ACTIVE = 1");
                    while (!$row->EOF) { ?>
                        <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS']; ?>"><?= $row->fields['APPOINTMENT_STATUS'] ?></option>
                    <?php $row->MoveNext();
                    } ?>
                </select>

                <select class="chip m-r-15" name="APPOINTMENT_TYPE" id="APPOINTMENT_TYPE" onchange="$('#search_form').submit();" style="height: 37px; min-width: 230px;">
                    <option value="">Select Appointment Type</option>
                    <option value="NORMAL" <?php if ($appointment_type == "NORMAL") {
                                                echo "selected";
                                            } ?>>Appointment</option>
                    <option value="GROUP" <?php if ($appointment_type == "GROUP") {
                                                echo "selected";
                                            } ?>>Group Class</option>
                    <option value="TO-DO" <?php if ($appointment_type == "TO-DO") {
                                                echo "selected";
                                            } ?>>To Dos</option>
                    <option value="EVENT" <?php if ($appointment_type == "EVENT") {
                                                echo "selected";
                                            } ?>>Event</option>
                </select>
            </form>

            <div class="view-toggle m-r-15" style="height: 37px; margin-left: auto;">
                <button class="view-btn active" data-view="day" onclick="changeView('agendaDay')">Day</button>
                <button class="view-btn" data-view="week" onclick="changeView('agendaWeek')">Week</button>
                <button class="view-btn" data-view="month" onclick="changeView('month')">Month</button>
            </div>

            <div class="view-toggle m-r-15" style="height: 37px;">
                <button class="view-btn-icon active">
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </button>
                <button class="view-btn-icon">
                    <i class="fa fa-list" aria-hidden="true"></i>
                </button>
            </div>

            <button class="btn-new" id="openDrawer">＋ New Appointment</button>
        </div>


        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid body_content" style="margin-top: 10px; padding: 0px 15px !important;">
                <div class="row">
                    <div id="appointment_list_half" class="col-12">
                        <div class="card" style="border-radius: 15px;">

                            <div class="card-body row">
                                <div class="col-12" id='calendar-container'>
                                    <div id='calendar'></div>
                                </div>

                                <div class="col-2" id='external-events' style="display: none;">
                                    <a href="javascript:;" onclick="closeCopyPasteDiv()" style="float: right; font-size: 25px;">&times;</a>
                                    <h5>Copy OR Move Events</h5>
                                    <?php if (in_array('Calendar Move/Copy', $PERMISSION_ARRAY) || in_array('Appointments Move/Copy', $PERMISSION_ARRAY) || in_array('To-Do Move/Copy', $PERMISSION_ARRAY)) { ?>
                                        <div class="radio-buttons" style="margin-bottom: 15px;">
                                            <input type='radio' name="copy_move" id='drop-copy' checked />
                                            <label for='drop-copy'><i class="fa fa-copy"></i> Copy</label>

                                            <input type='radio' name="copy_move" id='drop-remove' />
                                            <label for='drop-remove'><i class="fa fa-cut"></i> Move</label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <?php if (in_array('Calendar Edit', $PERMISSION_ARRAY)) { ?>
                        <div id="edit_appointment_half" class="col-6" style="display: none;">
                            <div class="card">
                                <div class="card-body">
                                    <a href="javascript:;" onclick="closeEditAppointment()" style="float: right; font-size: 25px;">&times;</a>
                                    <div class="card-body" id="appointment_details_div">

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <div id="myModal" class="modal">
                        <!-- Modal content -->
                        <div class="modal-content" style="margin-top:10%; width: 20%;">
                            <span class="close" style="margin-left: 96%;">&times;</span>
                            <div class="card" id="payment_confirmation_form_div">
                                <div class="card-body" style="text-align: center">
                                    <a href="create_appointment.php">Create Appointment</a><br><br>
                                    <!--<a href="event.php">Create Event</a>-->
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- Appointment Details -->
    <div class="overlay2"></div>
    <div class="side-drawer" id="sideDrawer2">
        <div class="drawer-header text-end border-bottom px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Appointment Details</h6>
            <span class="close-btn" id="closeDrawer2">&times;</span>
        </div>
        <div class="drawer-body" style="overflow-y: auto; height: calc(100% - 100px);">
            <!-- Content will be loaded here via AJAX -->
        </div>
        <div class="modal-footer flex-nowrap p-2 border-top">
            <button type="button" class="btn-secondary w-100 m-1" id="closeDrawer2">Cancel</button>
            <button type="button" class="btn-primary w-100 m-1">Save</button>
        </div>
    </div>

    <!-- Customer Details -->
    <div class="overlay3"></div>
    <div class="side-drawer" id="sideDrawer3">
        <div class="drawer-header text-end border-bottom px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 100 100" viewBox="0 0 100 100" width="16px" height="16px" fill="CurrentColor">
                    <path d="m44.93 76.47c.49.49 1.13.73 1.77.73s1.28-.24 1.77-.73c.98-.98.98-2.56 0-3.54l-21.43-21.43h51.96c1.38 0 2.5-1.12 2.5-2.5s-1.12-2.5-2.5-2.5h-51.96l21.43-21.43c.98-.98.98-2.56 0-3.54s-2.56-.98-3.54 0l-25.7 25.7c-.98.98-.98 2.56 0 3.54z" />
                </svg>
                <span>Customer Details</span>
            </h6>
            <span class="close-btn" id="closeDrawer3">&times;</span>
        </div>
        <div class="drawer-body" style="overflow-y: auto; height: calc(100% - 0px);">
            <!-- Content will be loaded here via AJAX -->
        </div>
    </div>

    <?php include 'partials/create_appointment_modal.php'; ?>

    <?php require_once('../includes/footer.php'); ?>

    <!--Payment Model-->
    <?php include('includes/enrollment_payment.php'); ?>

    <script src='https://unpkg.com/popper.js/dist/umd/popper.min.js'></script>
    <script src='https://unpkg.com/tooltip.js/dist/umd/tooltip.min.js'></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <script>
        $(window).on('load', function() {
            let redirect_date = '<?= $redirect_date ?>';
            if (redirect_date) {
                let parts = redirect_date.split('-'); // "YYYY-MM-DD"
                let currentDate = new Date(parts[0], parts[1] - 1, parts[2]); // Local date
                renderCalendar(currentDate);
            }
        });

        $('#APPOINTMENT_DATE').datepicker({
            onSelect: function() {
                getSlots();
            }
        });

        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        $('.datepicker-normal-calendar').datepicker({
            dateFormat: "DD, M d, yy",
            onSelect: function() {
                $('#IS_SELECTED').val(1);
                $("#search_form").submit();
            }
        });


        function showMessage() {
            if (<?= count($LOCATION_ARRAY) ?> === 1) {
                let currentDate = new Date(calendar.getDate());
                window.location.href = 'create_appointment.php?date=' + currentDate;
            } else {
                swal("Select One Location!", "Only one location can be selected on top of the page in order to schedule an appointment.", "error");
            }
        }

        function payNow(PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BILLED_AMOUNT, ENROLLMENT_ID) {
            $('.partial_payment').show();
            $('#PARTIAL_PAYMENT').prop('checked', false);
            $('.partial_payment_div').slideUp();

            $('.PAYMENT_TYPE').val('');
            $('#remaining_amount_div').slideUp();

            $('#enrollment_number').text(ENROLLMENT_ID);
            $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
            $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
            $('#ACTUAL_AMOUNT').val(BILLED_AMOUNT);
            $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
            let PK_USER_MASTER = $('.PK_USER_MASTER').val();
            $('.CUSTOMER_ID').val(PK_USER_MASTER);
            //$('#payment_confirmation_form_div_customer').slideDown();
            //openPaymentModel();
            $('#enrollment_payment_modal').modal('show');
        }

        function paySelected(PK_ENROLLMENT_MASTER, ENROLLMENT_ID) {
            $('.partial_payment').hide();
            $('#PARTIAL_PAYMENT').prop('checked', false);
            $('.partial_payment_div').slideUp();

            $('.PAYMENT_TYPE').val('');
            $('#remaining_amount_div').slideUp();

            let BILLED_AMOUNT = [];
            let PK_ENROLLMENT_LEDGER = [];

            $(".PAYMENT_CHECKBOX_" + PK_ENROLLMENT_MASTER + ":checked").each(function() {
                BILLED_AMOUNT.push($(this).data('billed_amount'));
                PK_ENROLLMENT_LEDGER.push($(this).val());
            });

            let TOTAL = BILLED_AMOUNT.reduce(getSum, 0);

            function getSum(total, num) {
                return total + num;
            }

            $('#enrollment_number').text(ENROLLMENT_ID);
            $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
            $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
            $('#ACTUAL_AMOUNT').val(parseFloat(TOTAL).toFixed(2));
            $('#AMOUNT_TO_PAY').val(parseFloat(TOTAL).toFixed(2));
            //$('#payment_confirmation_form_div_customer').slideDown();
            //openPaymentModel();
            $('#enrollment_payment_modal').modal('show');
        }
    </script>

    <script>
        var is_editable = <?= in_array('Calendar Edit', $PERMISSION_ARRAY) ? 1 : 0; ?>;
        var move_copy = <?= in_array('Calendar Move/Copy', $PERMISSION_ARRAY) ? 1 : 0; ?>;
        $('.multi_sumo_select').SumoSelect({
            placeholder: 'All Staff',
            selectAll: true,
            okCancelInMulti: true,
            triggerChangeCombined: true
        });

        let calendar;
        let redirect_date = '<?= $redirect_date ?>';
        let todayDate = redirect_date ? new Date(redirect_date) : new Date();
        const dayConfigs = <?= json_encode($dayConfig) ?>;

        function renderCalendar(date) {
            const day = date.getDay();
            const config = dayConfigs[day] || {
                minTime: '00:00:00',
                maxTime: '24:00:00'
            };

            if (calendar) {
                calendar.destroy();
            }

            let clickCount = 0;
            var Draggable = FullCalendar.Draggable;

            var containerEl = document.getElementById('external-events');
            var calendarEl = document.getElementById('calendar');
            var checkbox = document.getElementById('drop-remove');

            // new Draggable(containerEl, {
            //     itemSelector: '.fc-event',
            //     eventData: function(eventEl) {
            //         let color = eventEl.attributes["data-color"].value;
            //         let type = eventEl.attributes["data-type"].value;
            //         let duration = eventEl.attributes["data-duration"].value;
            //         return {
            //             title: eventEl.innerText,
            //             backgroundColor: color,
            //             type: type,
            //             duration: '00:'+duration
            //         };
            //     }
            // });

            // Initialize Draggable only once
            if (!containerEl.dataset.draggableInitialized) {
                new FullCalendar.Draggable(containerEl, {
                    itemSelector: '.fc-event',
                    eventData: function(eventEl) {
                        let color = eventEl.attributes["data-color"].value;
                        let type = eventEl.attributes["data-type"].value;
                        let duration = eventEl.attributes["data-duration"].value;
                        return {
                            title: eventEl.innerText,
                            backgroundColor: color,
                            type: type,
                            duration: '00:' + duration
                        };
                    }
                });
                containerEl.dataset.draggableInitialized = true; // Mark as initialized
            }

            calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
                editable: true,
                selectable: true,
                eventLimit: true,
                scrollTime: '00:00',
                /* header: {
                    left: 'customToday,customPrev,customNext',
                    center: 'title',
                    right: 'agendaDay,agendaWeek,month,'
                }, */
                header: false,
                customButtons: {
                    customPrev: {
                        text: '<',
                        click: function() {
                            if (calendar.view.type == 'agendaDay') {
                                todayDate.setDate(todayDate.getDate() - 1);
                                renderCalendar(todayDate);
                                //calendar.gotoDate(todayDate);
                            } else {
                                calendar.prev();
                            }
                        }
                    },
                    customNext: {
                        text: '>',
                        click: function() {
                            if (calendar.view.type == 'agendaDay') {
                                todayDate.setDate(todayDate.getDate() + 1);
                                renderCalendar(todayDate);
                                //calendar.gotoDate(todayDate);
                            } else {
                                calendar.next();
                            }
                        }
                    },
                    customToday: {
                        text: 'Today',
                        click: function() {
                            todayDate = new Date();
                            renderCalendar(todayDate);
                            //calendar.gotoDate(todayDate);
                        }
                    }
                },

                /*header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'agendaDay,agendaWeek,month,'
                },*/
                views: {
                    agendaDay: {
                        titleFormat: {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            weekday: 'long'
                        }
                    }
                },
                defaultDate: date,
                defaultView: 'agendaDay',
                slotDuration: '00:10:00',
                slotLabelInterval: {
                    minutes: 60
                },
                minTime: config.minTime,
                maxTime: config.maxTime,
                contentHeight: 1000,
                windowResize: true,
                droppable: true,
                allDaySlot: false,
                drop: function(info) {
                    if (checkbox.checked) {
                        info.draggedEl.parentNode.removeChild(info.draggedEl);
                        copyAppointment(info, 'move');
                    } else {
                        copyAppointment(info, 'copy');
                    }
                },
                eventReceive: function(arg) { // called when a proper external event is dropped
                    arg.event.remove();
                },
                resourceOrder: 'sortOrder',
                resources: function(info, successCallback, failureCallback) {
                    //console.log(info);
                    let selected_service_provider = [];
                    let selectedOptions = $('#SERVICE_PROVIDER_ID').find('option:selected');
                    selectedOptions.each(function() {
                        selected_service_provider.push($(this).val());
                    });

                    $.ajax({
                        url: "pagination/get_resource_data.php",
                        type: "POST",
                        data: {
                            selected_service_provider: selected_service_provider
                        },
                        dataType: 'json',
                        success: function(result) {
                            console.log(result);
                            successCallback(result);
                            if ((selected_service_provider.length > 1) && (calendar.view.type === 'agendaWeek')) {
                                calendar.setOption('editable', false);
                            } else {
                                if (selected_service_provider.length === 1) {
                                    calendar.setOption('editable', true);
                                }
                            }
                            //getServiceProviderCount();
                            if (calendar.view.type === 'month') {
                                calendar.changeView('month');
                            }
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            console.log(xhr.status);
                            console.log(thrownError);
                        }
                    });
                },
                events: function(info, successCallback, failureCallback) {
                    $('#day-count').html('<i class="fas fa-spinner fa-pulse" style="font-size: 20px;"></i>');
                    $('#week-count').html('<i class="fas fa-spinner fa-pulse" style="font-size: 20px;"></i>');
                    let STATUS_CODE = $('#STATUS_CODE').val();
                    let APPOINTMENT_TYPE = $('#APPOINTMENT_TYPE').val();

                    let START_DATE = moment(info.start).format();
                    let END_DATE = moment(info.end).format();

                    let selected_service_provider = [];
                    let selectedOptions = $('#SERVICE_PROVIDER_ID').find('option:selected');
                    selectedOptions.each(function() {
                        selected_service_provider.push($(this).val());
                    });

                    $.ajax({
                        url: "pagination/get_calendar_data.php",
                        type: "POST",
                        data: {
                            START_DATE: START_DATE,
                            END_DATE: END_DATE,
                            STATUS_CODE: STATUS_CODE,
                            APPOINTMENT_TYPE: APPOINTMENT_TYPE,
                            SERVICE_PROVIDER_ID: selected_service_provider
                        },
                        dataType: 'json',
                        success: function(result) {
                            console.log(result);
                            successCallback(result);
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            console.log(xhr.status);
                            console.log(thrownError);
                        }
                    });
                },
                eventRender: function(info) {
                    let event_data = info.event.extendedProps;
                    let element = info.el;

                    if (event_data.customerName) {
                        $(element).find(".fc-title").append('<br><strong style="font-size: 12px; font-weight: bold;">' + event_data.customerName + '</strong> ');
                    }

                    if (event_data.comment || event_data.internal_comment) {
                        $(element).find(".fc-title").prepend(' <i class="fa fa-comment" style="font-size: 13px"></i> ');
                    }








                    const $el = $(info.el);

                    if ($el.data('tooltip-bound')) return;
                    $el.data('tooltip-bound', true);

                    function showPopover(html) {
                        // initialize popover (manual trigger) and show
                        $el.popover('dispose'); // clear any previous
                        $el.popover({
                            //title: info.event.title,
                            content: html,
                            html: true,
                            placement: 'top',
                            trigger: 'manual',
                            container: 'body',
                            sanitize: false,
                        }).popover('show');

                        // keep popover visible when hovering it
                        $('.popover').off('mouseenter.tooltip').on('mouseenter.tooltip', function() {
                            clearTimeout($el.data('hideTimer'));
                        }).off('mouseleave.tooltip').on('mouseleave.tooltip', function() {
                            $el.data('hideTimer', setTimeout(() => $el.popover('hide'), 150));
                        });
                    }

                    $el.on('mouseenter', function() {
                        clearTimeout($el.data('hideTimer'));

                        // if already loaded, show cached HTML
                        if ($el.data('tooltipHtml')) {
                            return showPopover($el.data('tooltipHtml'));
                        }

                        // show temporary loading state (optional)
                        showPopover('<div style="padding:8px">Loading…</div>');

                        // fetch HTML via AJAX
                        $.ajax({
                            url: 'ajax/get_session_details.php', // your endpoint
                            type: 'POST',
                            data: {
                                id: info.event.id,
                                type: info.event.extendedProps.type || ''
                            },
                            success: function(html) {
                                $el.data('tooltipHtml', html);
                                showPopover(html);
                            },
                            error: function() {
                                $el.data('tooltipHtml', '<div style="padding:8px">Unable to load details</div>');
                                showPopover($el.data('tooltipHtml'));
                            }
                        });
                    }).on('mouseleave', function() {
                        // small delay so user can move to popover without it disappearing immediately
                        $el.data('hideTimer', setTimeout(() => $el.popover('hide'), 150));
                    });







                },
                eventClick: function(info) {
                    clickCount++;
                    let singleClickTimer;
                    if (clickCount === 1 && is_editable) {
                        singleClickTimer = setTimeout(function() {
                            if (clickCount === 1) {
                                loadViewAppointmentModal(info.event.id);
                                //showAppointmentEdit(info);
                            }
                            clickCount = 0;
                        }, 500);
                    } else if (clickCount === 2) {
                        clearTimeout(singleClickTimer);
                        clickCount = 0;

                        let selected_service_provider = [];
                        let selectedOptions = $('#SERVICE_PROVIDER_ID').find('option:selected');
                        selectedOptions.each(function() {
                            selected_service_provider.push($(this).val());
                        });
                        let appointment_type = info.event.extendedProps.type;
                        if (appointment_type !== 'not_available') {
                            if (calendar.view.type === 'agendaWeek' && move_copy) {
                                if (selected_service_provider.length === 1) {
                                    $('#calendar-container').removeClass('col-12').addClass('col-10');
                                    let event_data = info.event;
                                    let event_data_ext_prop = info.event.extendedProps;
                                    let TYPE = event_data_ext_prop.type;

                                    $('#external-events').show().addClass('col-2').append("<div class='fc-event fc-h-event' data-id='" + event_data.id + "' data-duration='" + event_data_ext_prop.duration + "' data-color='" + event_data.backgroundColor + "' data-type='" + TYPE + "' style='background-color: " + event_data.backgroundColor + ";'>" + event_data.title + "<span><a href='javascript:;' onclick='removeFromHere(this)' style='float: right; font-size: 25px; margin-top: -6px;'>&times;</a></span></div>");
                                }
                            } else {
                                if (calendar.view.type === 'agendaDay') {
                                    $('#calendar-container').removeClass('col-12').addClass('col-10');
                                    let event_data = info.event;
                                    let event_data_ext_prop = info.event.extendedProps;
                                    let TYPE = event_data_ext_prop.type;

                                    $('#external-events').show().addClass('col-2').append("<div class='fc-event fc-h-event' data-id='" + event_data.id + "' data-duration='" + event_data_ext_prop.duration + "' data-color='" + event_data.backgroundColor + "' data-type='" + TYPE + "' style='background-color: " + event_data.backgroundColor + ";'>" + event_data.title + "<span><a href='javascript:;' onclick='removeFromHere(this)' style='float: right; font-size: 25px; margin-top: -6px;'>&times;</a></span></div>");
                                }
                            }
                        }
                    }
                },
                eventDrop: function(info) {
                    modifyAppointment(info);
                },
                dateClick: function(data) {
                    let date = data.date;
                    let resource_id = (data.resource) ? data.resource.id : $('#SERVICE_PROVIDER_ID').val()[0];
                    console.log(resource_id);
                    clickCount++;
                    let singleClickTimer;
                    if (clickCount === 1) {
                        singleClickTimer = setTimeout(function() {
                            clickCount = 0;
                        }, 400);
                    } else if (clickCount === 2) {
                        clearTimeout(singleClickTimer);
                        clickCount = 0;
                        if (resource_id) {
                            if (<?= count($LOCATION_ARRAY) ?> === 1) {
                                $.ajax({
                                    url: "ajax/check_service_provider_slot.php",
                                    type: "POST",
                                    data: {
                                        PK_USER: resource_id,
                                        DATE_TIME: date
                                    },
                                    //dataType: 'json',
                                    success: function(result) {
                                        if (result == 1) {
                                            window.location.href = "create_appointment.php?date=" + date + "&SERVICE_PROVIDER_ID=" + resource_id;
                                        } else {
                                            swal("No slot available!", result, "error");
                                        }
                                    },
                                });
                            } else {
                                swal("Select One Location!", "Only one location can be selected on top of the page in order to schedule an appointment.", "error");
                            }
                        } else {
                            swal("Select One Service Provider!", "Please select any one Service Provider to continue", "error");
                        }
                    }
                },
                loading: function(isLoading) {
                    if (isLoading === true) {
                        //alert('asd');
                    } else {
                        getServiceProviderCount();
                    }
                },
                datesSet: function(info) {
                    // Update CHOOSE_DATE whenever dates change (navigation)
                    updateChooseDateInput(todayDate);
                }
            });

            calendar.render();

            // Update CHOOSE_DATE input with current date
            updateChooseDateInput(date);
        }

        function loadViewAppointmentModal(appointmentId) {
            $('#sideDrawer2, .overlay2').addClass('active');
            $.ajax({
                url: "partials/view_appointment_modal.php",
                type: "POST",
                data: {
                    PK_APPOINTMENT_MASTER: appointmentId
                },
                success: function(result) {
                    // Update the drawer content with view_appointment_modal
                    $('#sideDrawer2 .drawer-body').html(result);

                    // Re-initialize any scripts if needed
                    initializeModalScripts();
                },
                error: function(xhr, status, error) {
                    console.error("Error loading view_appointment_modal.php:", error);
                    $('#sideDrawer2 .drawer-body').html('<p>Error loading appointment details.</p>');
                }
            });
        }

        function loadViewCustomerModal(customerId, PK_ENROLLMENT_MASTER) {
            $('#sideDrawer2, .overlay2').removeClass('active'); // Close appointment modal
            $('#sideDrawer3, .overlay3').addClass('active'); // Open customer modal

            $.ajax({
                url: "partials/view_customer_modal.php",
                type: "POST",
                data: {
                    PK_USER: customerId,
                    PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER
                },
                success: function(result) {
                    // Update the customer drawer content
                    $('#sideDrawer3 .drawer-body').html(result);
                    initializeModalScripts();
                },
                error: function(xhr, status, error) {
                    console.error("Error loading view_customer_modal.php:", error);
                    $('#sideDrawer3 .drawer-body').html('<p>Error loading customer details.</p>');
                }
            });
        }

        function initializeModalScripts() {
            // Re-initialize any scripts that were in view_appointment_modal.php
            // For example, datepickers, select menus, etc.

            // Initialize datepicker if exists
            if ($.fn.datepicker) {
                $('.datepicker').datepicker();
            }

            // Initialize SumoSelect if exists
            if ($.fn.SumoSelect) {
                $('.sumoselect').SumoSelect();
            }

            // Re-attach event handlers
            attachModalEventHandlers();
        }

        function attachModalEventHandlers() {
            // Attach event handlers for buttons in the modal
            $(document).off('click', '.modal-save-btn').on('click', '.modal-save-btn', function() {
                // Handle save button click
                saveAppointmentChanges();
            });

            $(document).off('click', '.modal-cancel-btn').on('click', '.modal-cancel-btn', function() {
                closeSideDrawer2();
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            //todayDate.setDate(todayDate.getDate() + 5);
            renderCalendar(todayDate);
            //renderCalendar(new Date());
        });

        /*$('.fc-prev-button').click(function () {
            getServiceProviderCount();
        });
        $('.fc-next-button').click(function () {
            getServiceProviderCount();
        });
        $('.fc-today-button').click(function () {
            getServiceProviderCount();
        });*/


        $(document).on('click', '.fc-agendaDay-button', function() {
            window.location.reload();
            /*calendar.setOption('editable', true);
            getServiceProviderCount();*/
        });

        $(document).on('click', '.fc-agendaWeek-button', function() {
            calendar.setOption('editable', false);
        });

        $(document).on('click', '.fc-month-button', function() {
            calendar.setOption('editable', false);
        });

        var interval = 15;

        function updateChooseDateInput(date) {
            let displayText = '';

            // Get calendar title based on current view
            if (calendar && calendar.view) {
                displayText = calendar.view.title;
            } else {
                // Fallback: Format date as "Day, Mon DD, YYYY"
                const options = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'short',
                    day: '2-digit'
                };
                displayText = date.toLocaleDateString('en-US', options);
            }

            $('#CHOOSE_DATE').val(displayText);
        }

        function changeView(view) {
            // Update active button styling
            $('.view-btn').removeClass('active');
            $('[data-view="' + view.replace('agenda', '').toLowerCase() + '"]').addClass('active');

            // Change calendar view
            if (view === 'agendaDay') {
                calendar.changeView('agendaDay');
                updateChooseDateInput(todayDate);
            } else if (view === 'agendaWeek') {
                calendar.changeView('agendaWeek');
                setTimeout(function() {
                    updateChooseDateInput(todayDate);
                }, 100);
            } else if (view === 'month') {
                calendar.changeView('month');
                setTimeout(function() {
                    updateChooseDateInput(todayDate);
                }, 100);
            }
        }

        function zoomInOut(type) {
            if (type == 'in' && interval > 10) {
                interval = interval - 5;
            } else {
                if (type == 'out') {
                    interval = interval + 5;
                }
            }
            calendar.setOption('slotDuration', '00:' + interval + ':00');
            getServiceProviderCount();
        }

        function showAppointmentEdit(info) {
            $('#calendar-container').removeClass('col-10').addClass('col-12');
            $('#external-events').hide();
            let event_data = info.event.extendedProps;
            if (event_data.type === 'appointment') {
                $('#appointment_list_half').removeClass('col-12');
                $('#appointment_list_half').addClass('col-6');
                $.ajax({
                    url: "ajax/get_appointment_details.php",
                    type: "POST",
                    data: {
                        PK_APPOINTMENT_MASTER: info.event.id
                    },
                    async: false,
                    cache: false,
                    success: function(result) {
                        $('#appointment_details_div').html(result);
                        $('#edit_appointment_half').show();
                    }
                });
            } else {
                if (event_data.type === 'special_appointment') {
                    $('#appointment_list_half').removeClass('col-12');
                    $('#appointment_list_half').addClass('col-6');
                    $.ajax({
                        url: "ajax/get_special_appointment_details.php",
                        type: "POST",
                        data: {
                            PK_APPOINTMENT_MASTER: info.event.id
                        },
                        async: false,
                        cache: false,
                        success: function(result) {
                            $('#appointment_details_div').html(result);
                            $('#edit_appointment_half').show();
                        }
                    });
                } else {
                    if (event_data.type === 'group_class') {
                        $('#appointment_list_half').removeClass('col-12');
                        $('#appointment_list_half').addClass('col-6');
                        $.ajax({
                            url: "ajax/get_group_class_details.php",
                            type: "POST",
                            data: {
                                PK_APPOINTMENT_MASTER: info.event.id
                            },
                            async: false,
                            cache: false,
                            success: function(result) {
                                $('#appointment_details_div').html(result);
                                $('#edit_appointment_half').show();
                                $('.multi_sumo_select').SumoSelect({
                                    placeholder: 'Select Customer',
                                    selectAll: true,
                                    search: true,
                                    searchText: "Search Customer"
                                });
                            }
                        });
                    } else {
                        if (event_data.type === 'event') {
                            $('#appointment_list_half').removeClass('col-12');
                            $('#appointment_list_half').addClass('col-6');
                            $.ajax({
                                url: "ajax/get_event_details.php",
                                type: "POST",
                                data: {
                                    PK_EVENT: info.event.id
                                },
                                async: false,
                                cache: false,
                                success: function(result) {
                                    $('#appointment_details_div').html(result);
                                    $('#edit_appointment_half').show();
                                }
                            });
                        }
                    }
                }
            }
        }

        function closeEditAppointment() {
            $('#edit_appointment_half').hide();
            $('#appointment_list_half').removeClass('col-6').addClass('col-12');
        }

        function closeCopyPasteDiv() {
            $('#calendar-container').removeClass('col-10').addClass('col-12');
            $('#external-events').hide();
        }

        function removeFromHere(param) {
            $(param).parent().parent().remove();
        }

        function copyAppointment(info, operation) {
            let eventEl = info.draggedEl;
            let PK_ID = eventEl.attributes["data-id"].value;
            let TYPE = eventEl.attributes["data-type"].value;
            let SERVICE_PROVIDER_ID = (info.resource) ? info.resource.id : $('#SERVICE_PROVIDER_ID').val()[0];
            let START_DATE_TIME = info.dateStr;
            //console.log(TYPE,PK_ID,SERVICE_PROVIDER_ID,START_DATE_TIME);
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    FUNCTION_NAME: 'copyAppointment',
                    OPERATION: operation,
                    PK_ID: PK_ID,
                    TYPE: TYPE,
                    SERVICE_PROVIDER_ID: SERVICE_PROVIDER_ID,
                    START_DATE_TIME: START_DATE_TIME
                },
                async: false,
                cache: false,
                success: function(data) {
                    //getServiceProviderCount();
                    calendar.refetchEvents();
                }
            });
        }

        function modifyAppointment(info) {
            let OLD_SERVICE_PROVIDER_ID = (info.oldResource) ? info.oldResource.id : 0;
            let event_data = info.event.extendedProps;
            let TYPE = event_data.type;
            let PK_ID = info.event.id;
            let SERVICE_PROVIDER_ID = (info.newResource) ? info.newResource.id : 0;
            let START_DATE_TIME = moment.utc(info.event._instance.range.start).format();
            let END_DATE_TIME = moment.utc(info.event._instance.range.end).format();

            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    FUNCTION_NAME: 'modifyAppointment',
                    PK_ID: PK_ID,
                    TYPE: TYPE,
                    OLD_SERVICE_PROVIDER_ID: OLD_SERVICE_PROVIDER_ID,
                    SERVICE_PROVIDER_ID: SERVICE_PROVIDER_ID,
                    START_DATE_TIME: START_DATE_TIME,
                    END_DATE_TIME: END_DATE_TIME
                },
                async: false,
                cache: false,
                success: function(data) {
                    //getServiceProviderCount();
                    calendar.refetchEvents();
                }
            });
        }

        function getServiceProviderCount() {
            let currentDate = new Date(calendar.getDate());
            //renderCalendar(currentDate);
            let day = currentDate.getDate();
            let month = currentDate.getMonth() + 1;
            let year = currentDate.getFullYear();

            let selected_service_provider = [];
            let selectedOptions = $('#SERVICE_PROVIDER_ID').find('option:selected');
            selectedOptions.each(function() {
                selected_service_provider.push($(this).val());
            });

            let calendar_view = calendar.view.type;

            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    FUNCTION_NAME: 'getServiceProviderCount',
                    currentDate: year + '-' + month + '-' + day,
                    selected_service_provider: selected_service_provider,
                    calendar_view: calendar_view
                },
                async: false,
                cache: false,
                success: function(result) {
                    let result_data = JSON.parse(result).service_provider;
                    for (let i = 0; i < result_data.length; i++) {

                        let sp_name = result_data[i].SERVICE_PROVIDER_NAME.trim();
                        let sp_initials = result_data[i].INITIALS;
                        let sp_color = result_data[i].COLOR;

                        let avatarHTML = `<div style="display:flex; flex-direction:column; align-items:center; text-align:center; gap:4px; width:100%; margin-top: 10px;">
                                                <div style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background-color:${sp_color};color:#fff;font-weight:600;font-size:14px;letter-spacing:1px;">
                                                    ${sp_initials}
                                                </div>
                                                <div style="max-width:100%;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                    ${sp_name}
                                                </div>
                                            </div>`;

                        $('th[data-resource-id="' + result_data[i].SERVICE_PROVIDER_ID + '"]').html(avatarHTML);
                    }
                }
            });
        }

        $(document).on('submit', '#search_form', function(event) {
            event.preventDefault();
            let IS_SELECTED = $('#IS_SELECTED').val();
            if (IS_SELECTED == 1) {
                let CHOOSE_DATE = $('#CHOOSE_DATE').val();
                let currentDate = new Date(CHOOSE_DATE);
                renderCalendar(currentDate);

                todayDate = currentDate;

                $('#IS_SELECTED').val(0);
            } else {
                calendar.refetchEvents();
            }
            calendar.refetchResources();
            setTimeout(function() {
                getServiceProviderCount()
            }, 1000);
        });

        /* function createAppointment(type, param) {
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
            if (type === 'ad_hoc') {
                url = "ajax/add_ad_hoc_appointment.php";
            }
            if (type === 'appointments') {
                url = "create_appointment.php";
            }
            $.ajax({
                url: url,
                type: "POST",
                success: function(data) {
                    $('#create_form_div').html(data);
                }
            });
        } */
    </script>


    <script>
        function deleteAppointment(PK_APPOINTMENT_MASTER, type) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteAppointment',
                            type: type,
                            PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                        },
                        success: function(data) {
                            calendar.refetchEvents();
                        }
                    });
                }
            });
        }
    </script>

    <!-- JavaScript for Popup -->
    <script>
        function showPopup(type, src) {
            let popup = document.getElementById("mediaPopup");
            let image = document.getElementById("popupImage");
            let video = document.getElementById("popupVideo");
            let videoSource = document.getElementById("popupVideoSource");

            if (type === 'image') {
                image.src = src;
                image.style.display = "block";
                video.style.display = "none";
            } else if (type === 'video') {
                videoSource.src = src;
                video.load();
                video.style.display = "block";
                image.style.display = "none";
            }

            popup.style.display = "flex";

            // Add event listener to detect ESC key press
            document.addEventListener("keydown", escClose);
        }

        function closePopup() {
            document.getElementById("mediaPopup").style.display = "none";
            document.removeEventListener("keydown", escClose); // Remove listener when popup is closed
        }

        // Function to detect ESC key press and close the popup
        function escClose(event) {
            if (event.key === "Escape") {
                closePopup();
            }
        }

        // Disable right-click on images and videos
        document.addEventListener("contextmenu", function(event) {
            let target = event.target;
            if (target.tagName === "IMG" || target.tagName === "VIDEO") {
                event.preventDefault(); // Prevent right-click menu
            }
        });

        // Optional: Disable right-click for the whole page
        // Uncomment the line below if you want to block right-click everywhere
        // document.addEventListener("contextmenu", (event) => event.preventDefault());

        // Function to delete uploaded image
        function ConfirmDeleteImage(PK_APPOINTMENT_MASTER, imageNumber) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteImage',
                            PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER,
                            imageNumber: imageNumber
                        },
                        success: function(data) {
                            window.location.href = 'all_schedules.php';
                        }
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire("Cancelled", "Your image is safe.", "info"); // ✅ Show feedback for cancel
                }
            });
        }

        function ConfirmDeleteVideo(PK_APPOINTMENT_MASTER, videoNumber) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteVideo',
                            PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER,
                            videoNumber: videoNumber
                        },
                        success: function(data) {
                            window.location.href = 'all_schedules.php';
                        }
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire("Cancelled", "Your video is safe.", "info"); // ✅ Show feedback for cancel
                }
            });
        }
    </script>

    <script>
        (function($) {
            $.fn.countTo = function(options) {
                return this.each(function() {
                    var $this = $(this);

                    // Always get fresh data attributes using attr()
                    var settings = $.extend({}, $.fn.countTo.defaults, {
                        from: parseFloat($this.attr('data-from')) || 0,
                        to: parseFloat($this.attr('data-to')) || 0,
                        speed: parseInt($this.attr('data-speed')) || 1000,
                        refreshInterval: parseInt($this.attr('data-refresh-interval')) || 100,
                        decimals: parseInt($this.attr('data-decimals')) || 0
                    }, options);

                    // Clear existing interval if any
                    if ($this.data('countToInterval')) {
                        clearInterval($this.data('countToInterval'));
                    }

                    var loops = Math.ceil(settings.speed / settings.refreshInterval),
                        increment = (settings.to - settings.from) / loops,
                        value = settings.from,
                        loopCount = 0;

                    function render(val) {
                        var formatted = settings.formatter.call($this, val, settings);
                        $this.html(formatted);
                    }

                    function updateTimer() {
                        value += increment;
                        loopCount++;
                        render(value);

                        if (typeof settings.onUpdate === 'function') {
                            settings.onUpdate.call($this, value);
                        }

                        if (loopCount >= loops) {
                            clearInterval(interval);
                            render(settings.to);
                            $this.removeData('countToInterval');

                            if (typeof settings.onComplete === 'function') {
                                settings.onComplete.call($this, settings.to);
                            }
                        }
                    }

                    render(value);
                    var interval = setInterval(updateTimer, settings.refreshInterval);
                    $this.data('countToInterval', interval);
                });
            };

            $.fn.countTo.defaults = {
                from: 0,
                to: 0,
                speed: 1000,
                refreshInterval: 100,
                decimals: 0,
                formatter: function(value, settings) {
                    return value.toFixed(settings.decimals);
                },
                onUpdate: null,
                onComplete: null
            };
        })(jQuery);
    </script>


    <script>
        $('.multi_sumo_select').SumoSelect({
            placeholder: 'Select <?= $service_provider_title ?>',
            selectAll: true
        });

        $('#TO_DO_START_TIME').timepicker({
            timeFormat: 'hh:mm p',
            maxTime: '<?= $maxTime ?>',
            minTime: '<?= $minTime ?>',
            change: function() {
                calculateEndTime();
            },
        });

        $('#TO_DO_END_TIME').timepicker({
            timeFormat: 'hh:mm p',
            maxTime: '<?= $maxTime ?>',
            minTime: '<?= $minTime ?>'
        });



        $(document).ready(function() {
            $(".btn-available").click(function() {
                $(this).toggleClass("active");
                $(".slot_div").toggle();
            });

            $('#openDrawer').click(function() {
                $('#sideDrawer, .overlay').addClass('active');
            });

            $('#closeDrawer, .overlay').click(function() {
                $('#sideDrawer, .overlay').removeClass('active');
            });

            $('#openDrawer2').click(function() {
                $('#sideDrawer2, .overlay2').addClass('active');
            });

            $('#closeDrawer2, .overlay2').click(function() {
                $('#sideDrawer2, .overlay2').removeClass('active');
            });

            $('#openDrawer3').click(function() {
                $('#sideDrawer3, .overlay3').addClass('active');
            });

            $('#closeDrawer3, .overlay3').click(function() {
                $('#sideDrawer3, .overlay3').removeClass('active');
            });
        });

        $(document).ready(function() {
            $('.ends input[type="radio"]').on('change', function() {

                // Disable all inputs first
                $('.ends .form-control').prop('disabled', true);

                // Enable input next to selected radio (if any)
                var $row = $(this).closest('.d-flex');
                if ($row.length) {
                    $row.find('.form-control')
                        .prop('disabled', false)
                        .focus();
                }

            });

            $('.savebtngroup').click(function() {
                $(this).addClass("d-none");
                $('.added-item').removeClass("d-none");
                $('.newdatetime-format').addClass("d-none");
            });

            $('.adddaytime').click(function() {
                $('.newdatetime-format').removeClass("d-none");
            });
        });
    </script>




</body>

</html>