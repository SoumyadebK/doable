<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $upload_path;

$LOCATION_ARRAY = explode(',', $_SESSION['DEFAULT_LOCATION_ID']);

$title = "All Appointments";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || (in_array($_SESSION['PK_ROLES'], [1, 4, 5]) && $_SESSION['PK_ROLES'] != 3)){
    header("location:../login.php");
    exit;
}

$redirect_date = (!empty($_GET['date'])) ? date('Y-m-d', strtotime($_GET['date'].' +1 day')) : "";
$header = 'all_schedules.php';

$SERVICE_PROVIDER_ID = ' ';
if(isset($_GET['SERVICE_PROVIDER_ID']) && $_GET['SERVICE_PROVIDER_ID'] != ''){
    $service_providers = implode(',', $_GET['SERVICE_PROVIDER_ID']);
    $SERVICE_PROVIDER_ID = " AND DOA_USERS.PK_USER IN (".$service_providers.") ";
}

$appointment_type = '';

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveAdhocAppointmentData'){
    unset($_POST['TIME']);
    unset($_POST['FUNCTION_NAME']);
    if (empty($_POST['START_TIME']) || empty($_POST['END_TIME'])){
        unset($_POST['START_TIME']);
        unset($_POST['END_TIME']);
    }
    $session_cost = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_SERVICE_MASTER = '$_POST[PK_SERVICE_MASTER]' AND PK_SERVICE_CODE = '$_POST[PK_SERVICE_CODE]'");
    $price_per_session = $session_cost->fields['PRICE_PER_SESSION'];
    if(empty($_POST['PK_APPOINTMENT_MASTER'])){
        $_POST['PK_APPOINTMENT_STATUS'] = 1;
        $_POST['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $_POST['ACTIVE'] = 1;
        $_POST['CREATED_BY']  = $_SESSION['PK_USER'];
        $_POST['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_APPOINTMENT_MASTER', $_POST, 'insert');
    }else{
        if (!file_exists('../'.$upload_path.'/appointment_image/')) {
            mkdir('../'.$upload_path.'/appointment_image/', 0777, true);
            chmod('../'.$upload_path.'/appointment_image/', 0777);
        }
        //$_POST['ACTIVE'] = $_POST['ACTIVE'];
        if($_FILES['IMAGE']['name'] != ''){
            $extn 			= explode(".",$_FILES['IMAGE']['name']);
            $iindex			= count($extn) - 1;
            $rand_string 	= time()."-".rand(100000,999999);
            $file11			= 'appointment_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
            $extension   	= strtolower($extn[$iindex]);

            if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
                $image_path    = '../'.$upload_path.'/appointment_image/'.$file11;
                move_uploaded_file($_FILES['IMAGE']['tmp_name'], $image_path);
                $_POST['IMAGE'] = $image_path;
            }
        }
        $_POST['EDITED_BY']	= $_SESSION['PK_USER'];
        $_POST['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_APPOINTMENT_MASTER', $_POST, 'update'," PK_APPOINTMENT_MASTER =  '$_POST[PK_APPOINTMENT_MASTER]'");

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

    header("location:all_schedules.php");
}

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveSpecialAppointmentData'){
    $SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
    $SPECIAL_APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
    $SPECIAL_APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
    $SPECIAL_APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
    $SPECIAL_APPOINTMENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
    $PK_SPECIAL_APPOINTMENT = $_POST['PK_SPECIAL_APPOINTMENT'];

    $SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
    $SPECIAL_APPOINTMENT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
    $SPECIAL_APPOINTMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");

    if (count($_POST['PK_USER']) == 1) {
        $SPECIAL_APPOINTMENT_USER['PK_USER'] = $_POST['PK_USER'][0];
        //db_perform_account('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'update', " PK_SPECIAL_APPOINTMENT =  '$PK_SPECIAL_APPOINTMENT'");
    } elseif (count($_POST['PK_USER']) >= 1) {
        for ($j = 0; $j < count($_POST['PK_USER']); $j++) {
            if ($j == 0) {
                $SPECIAL_APPOINTMENT_USER['PK_USER'] = $_POST['PK_USER'][$j];
                db_perform_account('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'update', " PK_SPECIAL_APPOINTMENT =  '$PK_SPECIAL_APPOINTMENT'");
            } else {
                $SPECIAL_APPOINTMENT_DATA['STANDING_ID'] = $_POST['SELECTED_STANDING_ID'];
                $SPECIAL_APPOINTMENT_DATA['PK_LOCATION'] = $LOCATION_ARRAY[0];
                $SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
                $SPECIAL_APPOINTMENT_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
                $SPECIAL_APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
                $SPECIAL_APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
                $SPECIAL_APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
                $SPECIAL_APPOINTMENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
                $SPECIAL_APPOINTMENT_DATA['ACTIVE'] = 1;
                $SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];;
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

    if (isset($_POST['STANDING_ID'])) {
        db_perform_account('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'update', " STANDING_ID =  '$_POST[STANDING_ID]'");
    } else {
        $SPECIAL_APPOINTMENT_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
        db_perform_account('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'update'," PK_SPECIAL_APPOINTMENT =  '$PK_SPECIAL_APPOINTMENT'");
    }

    /*$db_account->Execute("DELETE FROM `DOA_SPECIAL_APPOINTMENT_USER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
    if (isset($_POST['PK_USER'])) {
        for ($i = 0; $i < count($_POST['PK_USER']); $i++) {
            $SPECIAL_APPOINTMENT_USER['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT;
            $SPECIAL_APPOINTMENT_USER['PK_USER'] = $_POST['PK_USER'][$i];
            db_perform_account('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'insert');
        }
    }

    $db_account->Execute("DELETE FROM `DOA_SPECIAL_APPOINTMENT_CUSTOMER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
    if (isset($_POST['PK_USER_MASTER'])) {
        for ($i = 0; $i < count($_POST['PK_USER_MASTER']); $i++) {
            $SPECIAL_APPOINTMENT_CUSTOMER_DATA['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT;
            $SPECIAL_APPOINTMENT_CUSTOMER_DATA['PK_USER_MASTER'] = $_POST['PK_USER_MASTER'][$i];
            db_perform_account('DOA_SPECIAL_APPOINTMENT_CUSTOMER', $SPECIAL_APPOINTMENT_CUSTOMER_DATA, 'insert');
        }
    }*/
    header("location:all_schedules.php?date=".date('m/d/Y', strtotime($_POST['DATE'])));
}

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveGroupClassData'){
    $PK_APPOINTMENT_MASTER = $_POST['PK_APPOINTMENT_MASTER'];
    $time = $db_account->Execute("SELECT DURATION FROM DOA_SCHEDULING_CODE WHERE PK_SCHEDULING_CODE = ".$_POST['PK_SCHEDULING_CODE']);
    $duration = $time->fields['DURATION'];
    $startTime = date('H:i:s', strtotime($_POST['START_TIME']));
    if ($duration > 0){
        $convertedTime = date('H:i:s',strtotime('+'.$duration.'minutes', strtotime($startTime)));
    } else {
        $convertedTime = date('H:i:s',strtotime('+30 minutes', strtotime($startTime)));
    }
    $GROUP_CLASS_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
    //$GROUP_CLASS_DATA['GROUP_NAME'] = $_POST['GROUP_NAME'];
    $GROUP_CLASS_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
    $GROUP_CLASS_DATA['END_TIME'] = date('H:i:s', strtotime($convertedTime));
    $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
    //$GROUP_CLASS_DATA['IS_CHARGED'] = ($GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] == 2) ? 1 : 0;
    $GROUP_CLASS_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
    $GROUP_CLASS_DATA['COMMENT'] = $_POST['COMMENT'];
    $GROUP_CLASS_DATA['INTERNAL_COMMENT'] = $_POST['INTERNAL_COMMENT'];

    if (!file_exists('../'.$upload_path.'/appointment_image/')) {
        mkdir('../'.$upload_path.'/appointment_image/', 0777, true);
        chmod('../'.$upload_path.'/appointment_image/', 0777);
    }
    if($_FILES['IMAGE']['name'] != ''){
        $extn 			= explode(".",$_FILES['IMAGE']['name']);
        $iindex			= count($extn) - 1;
        $rand_string 	= time()."-".rand(100000,999999);
        $file11			= 'appointment_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
        $extension   	= strtolower($extn[$iindex]);

        if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
            $image_path    = '../'.$upload_path.'/appointment_image/'.$file11;
            move_uploaded_file($_FILES['IMAGE']['tmp_name'], $image_path);
            $GROUP_CLASS_DATA['IMAGE'] = $image_path;
        }
    }

    if($_FILES['IMAGE_2']['name'] != ''){
        $extn 			= explode(".",$_FILES['IMAGE_2']['name']);
        $iindex			= count($extn) - 1;
        $rand_string 	= time()."-".rand(100000,999999);
        $file11			= 'appointment_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
        $extension   	= strtolower($extn[$iindex]);

        if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
            $image_path    = '../'.$upload_path.'/appointment_image/'.$file11;
            move_uploaded_file($_FILES['IMAGE_2']['tmp_name'], $image_path);
            $GROUP_CLASS_DATA['IMAGE_2'] = $image_path;
        }
    }

    if (!file_exists('../'.$upload_path.'/appointment_video/')) {
        mkdir('../'.$upload_path.'/appointment_video/', 0777, true);
        chmod('../'.$upload_path.'/appointment_video/', 0777);
    }
    if($_FILES['VIDEO']['name'] != ''){
        $extn 			= explode(".",$_FILES['VIDEO']['name']);
        $iindex			= count($extn) - 1;
        $rand_string 	= time()."-".rand(100000,999999);
        $file11			= 'appointment_video_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
        $extension   	= strtolower($extn[$iindex]);

        if($extension == "mp4" || $extension == "avi" || $extension == "mov" || $extension == "wmv") {
            $video_path    = '../'.$upload_path.'/appointment_video/'.$file11;
            move_uploaded_file($_FILES["VIDEO"]["tmp_name"], $video_path);
            $GROUP_CLASS_DATA['VIDEO'] = $video_path;
        }
    }

    if($_FILES['VIDEO_2']['name'] != ''){
        $extn 			= explode(".",$_FILES['VIDEO_2']['name']);
        $iindex			= count($extn) - 1;
        $rand_string 	= time()."-".rand(100000,999999);
        $file11			= 'appointment_video_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
        $extension   	= strtolower($extn[$iindex]);

        if($extension == "mp4" || $extension == "avi" || $extension == "mov" || $extension == "wmv") {
            $video_path    = '../'.$upload_path.'/appointment_video/'.$file11;
            move_uploaded_file($_FILES["VIDEO_2"]["tmp_name"], $video_path);
            $GROUP_CLASS_DATA['VIDEO_2'] = $video_path;
        }
    }

    $GROUP_CLASS_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
    $GROUP_CLASS_DATA['EDITED_ON'] = date("Y-m-d H:i");

    if (isset($_POST['STANDING_ID'])) {
        db_perform_account('DOA_APPOINTMENT_MASTER', $GROUP_CLASS_DATA, 'update', " STANDING_ID =  '$_POST[STANDING_ID]'");
    } else {
        $GROUP_CLASS_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
        db_perform_account('DOA_APPOINTMENT_MASTER', $GROUP_CLASS_DATA, 'update', " PK_APPOINTMENT_MASTER =  '$PK_APPOINTMENT_MASTER'");
    }

    $existing_customer = (!empty($_POST['EXISTING_CUSTOMER'])) ? explode(',', $_POST['EXISTING_CUSTOMER']) : [];
    $existing_partner = (!empty($_POST['EXISTING_PARTNER'])) ? explode(',', $_POST['EXISTING_PARTNER']) : [];

    $SELECTED_CUSTOMERS = $_POST['PK_USER_MASTER'] ?? [];
    $SELECTED_PARTNERS = $_POST['PARTNER'] ?? [];

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
        $user_data = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = ".$customer_to_add[$j]);
        $details = '('.$user_data->fields['NAME'].' Added By '.$_SESSION['FIRST_NAME'].' '.$_SESSION['LAST_NAME'].' at '.date("m/d/Y h:i A").')';
        $CUSTOMER_UPDATE_DATA['DETAILS'] = $details;
        db_perform_account('DOA_APPOINTMENT_CUSTOMER_UPDATE_HISTORY', $CUSTOMER_UPDATE_DATA, 'insert');
    }
    for ($j = 0; $j < count($customer_to_remove); $j++) {
        $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_CUSTOMER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER' AND `PK_USER_MASTER` = '$customer_to_remove[$j]' AND IS_PARTNER = 0");
        $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_ENROLLMENT` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER' AND `PK_USER_MASTER` = '$customer_to_remove[$j]'");

        $CUSTOMER_UPDATE_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
        $user_data = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = ".$customer_to_remove[$j]);
        $details = '('.$user_data->fields['NAME'].' Removed By '.$_SESSION['FIRST_NAME'].' '.$_SESSION['LAST_NAME'].' at '.date("m/d/Y h:i A").')';
        $CUSTOMER_UPDATE_DATA['DETAILS'] = $details;
        db_perform_account('DOA_APPOINTMENT_CUSTOMER_UPDATE_HISTORY', $CUSTOMER_UPDATE_DATA, 'insert');
    }

    for ($j = 0; $j < count($partner_to_add); $j++) {
        $GROUP_CLASS_PARTNER_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
        $GROUP_CLASS_PARTNER_DATA['PK_USER_MASTER'] = $partner_to_add[$j];
        $GROUP_CLASS_PARTNER_DATA['IS_PARTNER'] = 1;
        db_perform_account('DOA_APPOINTMENT_CUSTOMER', $GROUP_CLASS_PARTNER_DATA, 'insert');

        $CUSTOMER_UPDATE_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
        $partner_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = ".$partner_to_add[$j]);
        $details = '('.$partner_data->fields['PARTNER_FIRST_NAME'].' '.$partner_data->fields['PARTNER_LAST_NAME'].' Added By '.$_SESSION['FIRST_NAME'].' '.$_SESSION['LAST_NAME'].' at '.date("m/d/Y h:i A").')';
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
        $partner_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = ".$partner_to_remove[$j]);
        $details = '('.$partner_data->fields['PARTNER_FIRST_NAME'].' '.$partner_data->fields['PARTNER_LAST_NAME'].' Removed By '.$_SESSION['FIRST_NAME'].' '.$_SESSION['LAST_NAME'].' at '.date("m/d/Y h:i A").')';
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

    header("location:all_schedules.php?date=".date('m/d/Y', strtotime($_POST['DATE'])));
}

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveEventData'){
    $PK_EVENT = $_POST['PK_EVENT'];
    if(!empty($_POST)) {
        $EVENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $EVENT_DATA['HEADER'] = $_POST['HEADER'];
        $EVENT_DATA['PK_EVENT_TYPE'] = $_POST['PK_EVENT_TYPE'];
        $EVENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
        $EVENT_DATA['SHARE_WITH_CUSTOMERS'] = isset($_POST['SHARE_WITH_CUSTOMERS'])?1:0;
        $EVENT_DATA['SHARE_WITH_SERVICE_PROVIDERS'] = isset($_POST['SHARE_WITH_SERVICE_PROVIDERS'])?1:0;
        $EVENT_DATA['SHARE_WITH_EMPLOYEES'] = isset($_POST['SHARE_WITH_EMPLOYEES'])?1:0;
        $EVENT_DATA['START_DATE'] = date('Y-m-d', strtotime($_POST['START_DATE']));
        $EVENT_DATA['END_DATE'] = !empty($_POST['END_DATE'])?date('Y-m-d', strtotime($_POST['END_DATE'])):NULL;
        $EVENT_DATA['ALL_DAY'] = $_POST['ALL_DAY'] ?? 0;
        if ($EVENT_DATA['ALL_DAY'] == 1){
            $EVENT_DATA['START_TIME'] = '00:00:00';
            $EVENT_DATA['END_TIME'] = '23:30:00';
        }else {
            $EVENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
            $EVENT_DATA['END_TIME'] = !empty($_POST['END_TIME'])?date('H:i:s', strtotime($_POST['END_TIME'])):NULL;
        }
        if(empty($PK_EVENT)){
            $EVENT_DATA['ACTIVE'] = 1;
            $EVENT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
            $EVENT_DATA['CREATED_ON']  = date("Y-m-d H:i");
            db_perform_account('DOA_EVENT', $EVENT_DATA, 'insert');
            $PK_EVENT = $db_account->insert_ID();
        }else{
            $EVENT_DATA['ACTIVE'] = $_POST['ACTIVE'];
            $EVENT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
            $EVENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_EVENT', $EVENT_DATA, 'update'," PK_EVENT =  '$PK_EVENT'");
            $PK_EVENT = $_POST['PK_EVENT'];
        }

        $db_account->Execute("DELETE FROM `DOA_EVENT_LOCATION` WHERE `PK_EVENT` = '$PK_EVENT'");
        if(isset($_POST['PK_LOCATION'])){
            $PK_LOCATION = $_POST['PK_LOCATION'];
            for($i = 0; $i < count($PK_LOCATION); $i++){
                $EVENT_LOCATION_DATA['PK_EVENT'] = $PK_EVENT;
                $EVENT_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION[$i];
                db_perform_account('DOA_EVENT_LOCATION', $EVENT_LOCATION_DATA, 'insert');
            }
        }

        if (!file_exists('../'.$upload_path.'/event_image/')) {
            mkdir('../'.$upload_path.'/event_image/', 0777, true);
            chmod('../'.$upload_path.'/event_image/', 0777);
        }

        $db_account->Execute("DELETE FROM `DOA_EVENT_IMAGE` WHERE `PK_EVENT` = '$PK_EVENT'");
        for($i = 0; $i < count($_FILES['IMAGE']['name']); $i++){
            $EVENT_IMAGE_DATA['PK_EVENT'] = $PK_EVENT;
            if(!empty($_FILES['IMAGE']['name'][$i])){
                $extn 			= explode(".",$_FILES['IMAGE']['name'][$i]);
                $iindex			= count($extn) - 1;
                $rand_string 	= time()."-".rand(100000,999999);
                $file11			= 'event_image_'.$PK_EVENT.'_'.$rand_string.".".$extn[$iindex];
                $extension   	= strtolower($extn[$iindex]);

                $image_path    = '../'.$upload_path.'/event_image/'.$file11;
                move_uploaded_file($_FILES['IMAGE']['tmp_name'][$i], $image_path);
                $EVENT_IMAGE_DATA['IMAGE'] = $image_path;
            } else {
                $EVENT_IMAGE_DATA['IMAGE'] = $_POST['IMAGE_PATH'][$i];
            }
            $EVENT_IMAGE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
            $EVENT_IMAGE_DATA['CREATED_ON']  = date("Y-m-d H:i");
            db_perform_account('DOA_EVENT_IMAGE', $EVENT_IMAGE_DATA, 'insert');
        }
        header("location:all_schedules.php?date=".date('m/d/Y', strtotime($_POST['START_DATE'])));
    }
}

$dayNumber = date('N');
$location_operational_hour = $db_account->Execute("SELECT MIN(DOA_OPERATIONAL_HOUR.OPEN_TIME) AS OPEN_TIME, MAX(DOA_OPERATIONAL_HOUR.CLOSE_TIME) AS CLOSE_TIME FROM DOA_OPERATIONAL_HOUR WHERE DAY_NUMBER = '$dayNumber' AND CLOSED = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].")");
if ($location_operational_hour->RecordCount() > 0) {
    $OPEN_TIME = $location_operational_hour->fields['OPEN_TIME'] ?? '00:00:00';
    $CLOSE_TIME = $location_operational_hour->fields['CLOSE_TIME'] ?? '23:59:00';
} else {
    $OPEN_TIME = '00:00:00';
    $CLOSE_TIME = '23:59:00';
}

if (isset($_GET['CHOOSE_DATE']) && $_GET['CHOOSE_DATE'] != '') {
    $CHOOSE_DATE = $_GET['CHOOSE_DATE'];
} else {
    $CHOOSE_DATE = date("Y-m-d");
}

$interval = $db->Execute("SELECT TIME_SLOT_INTERVAL FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER']);
if ($interval->fields['TIME_SLOT_INTERVAL'] == "00:00:00") {
    $INTERVAL = "00:15:00";
}else {
    $INTERVAL = $interval->fields['TIME_SLOT_INTERVAL'];
}


$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");

$PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];
$SECRET_KEY = $account_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $account_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $account_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $account_data->fields['LOCATION_ID'];
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>

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
        color: #000000;
    }

    .fc-event .fc-content {
        color: #000000;
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
    .list-select li { padding: 1px 10px; z-index: 2; }
    .list-select li:not(.init) { float: left; width: 100%; display: none; *background: #ddd; border-top: 1px solid #eeecec;}
    .list-select li:not(.init):hover, .list-select li.selected:not(.init) { background: #09f; border-top: 1px solid #eeecec;}
    li.init { cursor: pointer; }
</style>
<style>
    .search-container {
        display: flex;
        align-items: center;
        gap: 10px; /* Default gap for desktop */
    }

    .search-button:hover {
        background-color: #0056b3;
    }

    /* Media query for tablets (for example, max-width 768px) */
    @media (max-width: 768px) {
        .search-container {
            gap: 8px; /* Reduced gap for tablet screens */
        }

        .SERVICE_PROVIDER_ID {
            width: 150px; /* Adjust input width for tablet */
        }

        .btn {
            font-size: 14px; /* Smaller button size for tablets */
        }
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
            <div class="row" >
                <div id="add_buttons" class="d-flex justify-content-center align-items-center" style="position: fixed; bottom: 0">
                    <!--<button type="button" id="group_class" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=group_class'"><i class="fa fa-plus-circle"></i> Group Class</button>
                    <button type="button" id="int_app" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=int_app'"><i class="fa fa-plus-circle"></i> INT APP</button>
                    <button type="button" id="appointment" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=appointment'"><i class="fa fa-plus-circle"></i> Appointment</button>
                    <button type="button" id="standing" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=standing'"><i class="fa fa-plus-circle"></i> Standing</button>
                    <button type="button" id="ad_hoc" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php?type=ad_hoc'"><i class="fa fa-plus-circle"></i> Ad-hoc Appointment</button>-->
                    <?php if(in_array('Calendar Schedule', $PERMISSION_ARRAY)){ ?>
                        <button type="button" id="appointments" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="showMessage()"><i class="fa fa-plus-circle"></i> Appointments</button>
                    <?php } ?>
                    <button type="button" id="operations" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='operations.php'"><i class="ti-layers-alt"></i> <?=$operation_tab_title?></button>
                </div>
            </div>

            <div class="row">
                <div id="appointment_list_half" class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="search_form" class="form-material form-horizontal" action="" method="get" style="margin-bottom: -30px;">
                                <div class="col-12 row m-10">
                                    <!--<div class="col-2">
                                        <h5 class="card-title"><?php /*=$title*/?></h5>
                                    </div>-->
                                    <div class="col-2" >
                                        <div class="form-material form-horizontal">
                                            <select class="form-control" name="STATUS_CODE" id="STATUS_CODE" onchange="$('#search_form').submit();">
                                                <option value="">Select Status</option>
                                                <?php
                                                $row = $db->Execute("SELECT * FROM DOA_APPOINTMENT_STATUS WHERE ACTIVE = 1");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS'];?>"><?=$row->fields['APPOINTMENT_STATUS']?></option>
                                                    <?php $row->MoveNext(); } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="form-material form-horizontal">
                                            <select class="form-control" name="APPOINTMENT_TYPE" id="APPOINTMENT_TYPE" onchange="$('#search_form').submit();">
                                                <option value="">Select Appointment Type</option>
                                                <option value="NORMAL" <?php if($appointment_type=="NORMAL"){echo "selected";}?>>Appointment</option>
                                                <option value="GROUP" <?php if($appointment_type=="GROUP"){echo "selected";}?>>Group Class</option>
                                                <option value="TO-DO" <?php if($appointment_type=="TO-DO"){echo "selected";}?>>To Dos</option>
                                                <option value="EVENT" <?php if($appointment_type=="EVENT"){echo "selected";}?>>Event</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <input type="hidden" id="IS_SELECTED" value="0">
                                        <input type="text" id="CHOOSE_DATE" name="CHOOSE_DATE" class="form-control datepicker-normal-calendar" placeholder="Choose Date" value="<?php /*=($_GET['CHOOSE_DATE']) ?? ''*/?>">
                                    </div>
                                    <div class="col-3">
                                        <div class="search-container">
                                            <select class="SERVICE_PROVIDER_ID multi_sumo_select" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID" style="height: 37px" multiple>
                                                <?php
                                                $row = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER=DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND ACTIVE=1 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY NAME");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?=$row->fields['PK_USER']?>" <?=(!empty($service_providers) && in_array($row->fields['PK_USER'], explode(',', $service_providers)))?"selected":""?>><?=$row->fields['NAME']?></option>
                                                <?php $row->MoveNext(); } ?>
                                            </select>
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white input-form-btn m-b-1" style="height: 37px"><i class="fa fa-search"></i></button>
                                        </div>
                                    </div>

                                    <!--<div class="col-2">
                                        <div class="input-group">
                                            <select class="SERVICE_PROVIDER_ID multi_sumo_select" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID" style="width: 150px; height: 37px" multiple>
                                                <?php
/*                                                $row = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER=DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND ACTIVE=1 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY NAME");
                                                while (!$row->EOF) { */?>
                                                    <option value="<?php /*=$row->fields['PK_USER']*/?>" <?php /*=(!empty($service_providers) && in_array($row->fields['PK_USER'], explode(',', $service_providers)))?"selected":""*/?>><?php /*=$row->fields['NAME']*/?></option>
                                                    <?php /*$row->MoveNext(); } */?>
                                            </select>

                                        </div>
                                    </div>


                                    <div class="col-1">
                                        <div>
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white input-form-btn m-b-1" style="margin-left:-14px; height: 37px; float: left"><i class="fa fa-search"></i></button>
                                        </div>
                                    </div>-->
                                    <div class="col-2">
                                        <div class="input-group" style="width: 100px; float: right;">
                                            <a onclick="zoomInOut('out');" class="btn btn-info waves-effect waves-light m-r-10 text-white input-form-btn m-b-1"><i class="fa fa-minus"></i></a>
                                            <a onclick="zoomInOut('in');" class="btn btn-info waves-effect waves-light m-r-10 text-white input-form-btn m-b-1"><i class="fa fa-plus"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                          <!--  <div id="appointment_list"  class="card-body table-responsive" style="display: none;">

                            </div>-->

                            <div class="card-body row">
                                <div class="col-12" id='calendar-container'>
                                    <div id='calendar'></div>
                                </div>

                                <div class="col-2" id='external-events' style="display: none;">
                                    <a href="javascript:;" onclick="closeCopyPasteDiv()" style="float: right; font-size: 25px;">&times;</a>
                                    <h5>Copy OR Move Events</h5>
                                    <?php if(in_array('Calendar Move/Copy', $PERMISSION_ARRAY) || in_array('Appointments Move/Copy', $PERMISSION_ARRAY) || in_array('To-Do Move/Copy', $PERMISSION_ARRAY)){ ?>
                                    <p>
                                        <input type='radio' name="copy_move" id='drop-copy' checked/>
                                        <label for='drop-copy'>Copy</label>

                                        <input type='radio' name="copy_move" id='drop-remove'/>
                                        <label for='drop-remove'>Move</label>
                                    </p>
                                    <?php } ?>
                                </div>
                            </div>

                    </div>
                </div>

                <?php if(in_array('Calendar Edit', $PERMISSION_ARRAY)){ ?>
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

<!--Payment Model-->
<?php include('includes/enrollment_payment.php'); ?>


<?php require_once('../includes/footer.php');?>

<script src='https://unpkg.com/popper.js/dist/umd/popper.min.js'></script>
<script src='https://unpkg.com/tooltip.js/dist/umd/tooltip.min.js'></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

<script>
    $(window).on('load', function () {
       let redirect_date = '<?=$redirect_date?>';
       if (redirect_date) {
           let currentDate = new Date(redirect_date);

           let day = currentDate.getDate();
           let month = currentDate.getMonth()+1;
           let year = currentDate.getFullYear();

           calendar.gotoDate(month+'/'+day+'/'+year);
       }
    });

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $('.datepicker-normal-calendar').datepicker({
        onSelect: function () {
            $('#IS_SELECTED').val(1);
            $("#search_form").submit();
        },
        format: 'mm/dd/yyyy',
    });

    function showMessage() {
        if(<?=count($LOCATION_ARRAY)?> === 1) {
            window.location.href = 'create_appointment.php';
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

        $(".PAYMENT_CHECKBOX_"+PK_ENROLLMENT_MASTER+":checked").each(function() {
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
    $('.multi_sumo_select').SumoSelect({placeholder: 'Service Provider', selectAll: true});

    var calendar;
    document.addEventListener('DOMContentLoaded', function() {
        let open_time = '<?=$OPEN_TIME?>';
        let close_time = '<?=$CLOSE_TIME?>';
        let clickCount = 0;

        var Calendar = FullCalendar.Calendar;
        var Draggable = FullCalendar.Draggable;

        var containerEl = document.getElementById('external-events');
        var calendarEl = document.getElementById('calendar');
        var checkbox = document.getElementById('drop-remove');

        new Draggable(containerEl, {
            itemSelector: '.fc-event',
            eventData: function(eventEl) {
                let color = eventEl.attributes["data-color"].value;
                let type = eventEl.attributes["data-type"].value;
                let duration = eventEl.attributes["data-duration"].value;
                return {
                    title: eventEl.innerText,
                    backgroundColor: color,
                    type: type,
                    duration: '00:'+duration
                };
            }
        });

        calendar = new Calendar(calendarEl, {
            schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
            editable: true,
            selectable: true,
            eventLimit: true,
            scrollTime: '00:00',
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'agendaDay,agendaWeek,month,'
            },
            views: {
                agendaDay: {
                    titleFormat: { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' }
                }
            },
            defaultView: 'agendaDay',
            slotDuration: '<?=$INTERVAL?>',
            slotLabelInterval: {minutes: 5},
            minTime: open_time,
            maxTime: close_time,
            contentHeight: 1000,
            windowResize: true,
            droppable: true,
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
            resources: function (info, successCallback, failureCallback) {
                //console.log(info);
                let selected_service_provider = [];
                let selectedOptions = $('#SERVICE_PROVIDER_ID').find('option:selected');
                selectedOptions.each(function(){
                    selected_service_provider.push($(this).val());
                });

                $.ajax({
                    url: "pagination/get_resource_data.php",
                    type: "POST",
                    data: {selected_service_provider: selected_service_provider},
                    dataType: 'json',
                    success: function (result) {
                        console.log(result);
                        successCallback(result);
                        if ((selected_service_provider.length > 1) && (calendar.view.type === 'agendaWeek')) {
                            calendar.setOption('editable', false);
                        } else {
                            if (selected_service_provider.length === 1) {
                                calendar.setOption('editable', true);
                            }
                        }
                        getServiceProviderCount();
                        if (calendar.view.type === 'month') {
                            calendar.changeView('month');
                        }
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        alert(xhr.status);
                        alert(thrownError);
                    }
                });
            },
            events: function (info, successCallback, failureCallback) {
                let STATUS_CODE = $('#STATUS_CODE').val();
                let APPOINTMENT_TYPE = $('#APPOINTMENT_TYPE').val();

                let START_DATE = moment(info.start).format();
                let END_DATE = moment(info.end).format();

                let selected_service_provider = [];
                let selectedOptions = $('#SERVICE_PROVIDER_ID').find('option:selected');
                selectedOptions.each(function(){
                    selected_service_provider.push($(this).val());
                });

                $.ajax({
                    url: "pagination/get_calendar_data.php",
                    type: "POST",
                    data: {START_DATE: START_DATE, END_DATE: END_DATE, STATUS_CODE: STATUS_CODE, APPOINTMENT_TYPE: APPOINTMENT_TYPE, SERVICE_PROVIDER_ID: selected_service_provider},
                    dataType: 'json',
                    success: function (result) {
                        console.log(result);
                        successCallback(result);
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        alert(xhr.status);
                        alert(thrownError);
                    }
                });

            },
            eventRender: function(info) {
                let event_data = info.event.extendedProps;
                let element = info.el;

                if (event_data.customerName) {
                    $(element).find(".fc-title").prepend(' <strong style="font-size: 13px">' + event_data.customerName + '</strong> ');
                }
                if (event_data.status) {
                    $(element).find(".fc-title").prepend(' <strong style="color: ' + event_data.statusColor + '">(' + event_data.status + ')</strong> ');
                }
                if (event_data.comment || event_data.internal_comment) {
                    $(element).find(".fc-title").prepend(' <i class="fa fa-comment-dots" style="font-size: 15px"></i> ');
                    $(info.el).popover({
                        title: info.event.title,
                        placement: 'top',
                        trigger: 'hover',
                        content: ((event_data.comment)?'Comment : '+event_data.comment+'<br>':'')+((event_data.internal_comment)?'Internal Comment : '+event_data.internal_comment:''),
                        container: 'body',
                        html: true,
                    });
                }
                if (event_data.statusCode) {
                    $(element).find(".fc-title").append(' <br><strong style="font-size: 13px">(' + event_data.statusCode + ')</strong> ');
                }
            },
            eventClick: function(info) {
                clickCount++;
                let singleClickTimer;
                if (clickCount === 1 && is_editable) {
                    singleClickTimer = setTimeout(function () {
                        if (clickCount === 1) {
                            showAppointmentEdit(info);
                        }
                        clickCount = 0;
                    }, 500);
                } else if (clickCount === 2) {
                    clearTimeout(singleClickTimer);
                    clickCount = 0;

                    let selected_service_provider = [];
                    let selectedOptions = $('#SERVICE_PROVIDER_ID').find('option:selected');
                    selectedOptions.each(function(){
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
            eventDrop: function (info) {
                modifyAppointment(info);
            },
            dateClick: function(data) {
                let date = data.date;
                let resource_id = (data.resource) ? data.resource.id : $('#SERVICE_PROVIDER_ID').val()[0];
                console.log(resource_id);
                clickCount++;
                let singleClickTimer;
                if (clickCount === 1) {
                    singleClickTimer = setTimeout(function () {
                        clickCount = 0;
                    }, 400);
                } else if (clickCount === 2) {
                    clearTimeout(singleClickTimer);
                    clickCount = 0;
                    if (resource_id) {
                        if (<?=count($LOCATION_ARRAY)?> === 1) {
                            $.ajax({
                                url: "ajax/check_service_provider_slot.php",
                                type: "POST",
                                data: {PK_USER: resource_id, DATE_TIME: date},
                                //dataType: 'json',
                                success: function (result) {
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
            loading: function( isLoading ) {
                if (isLoading === true) {
                    //alert('asd');
                } else {
                    getServiceProviderCount();
                }
            }
        });

        calendar.render();

        $('.fc-prev-button').click(function () {
            getServiceProviderCount();
        });
        $('.fc-next-button').click(function () {
            getServiceProviderCount();
        });
        $('.fc-today-button').click(function () {
            getServiceProviderCount();
        });
    });

    $(document).on('click', '.fc-agendaDay-button', function () {
        window.location.reload();
        /*calendar.setOption('editable', true);
        getServiceProviderCount();*/
    });

    $(document).on('click', '.fc-agendaWeek-button', function () {
        calendar.setOption('editable', false);
    });

    $(document).on('click', '.fc-month-button', function () {
        calendar.setOption('editable', false);
    });

    var interval = 15;
    function zoomInOut(type) {
        if (type == 'in' && interval > 10) {
            interval = interval - 5;
        } else {
            if (type == 'out') {
                interval = interval + 5;
            }
        }
        calendar.setOption('slotDuration', '00:'+interval+':00');
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
                data: {PK_APPOINTMENT_MASTER: info.event.id},
                async: false,
                cache: false,
                success: function (result) {
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
                    data: {PK_APPOINTMENT_MASTER: info.event.id},
                    async: false,
                    cache: false,
                    success: function (result) {
                        $('#appointment_details_div').html(result);
                        $('#edit_appointment_half').show();
                        //$('.multi_sumo_select').SumoSelect({placeholder: 'Select Service Provider', selectAll: true});
                        //$('.PK_SCHEDULING_CODE').SumoSelect({placeholder: 'Select Service Provider', selectAll: true});

                        $('.datepicker-normal-calendar').datepicker({
                            format: 'mm/dd/yyyy',
                        });

                        $('.timepicker-normal').timepicker({
                            timeFormat: 'hh:mm p',
                        });
                    }
                });
            } else {
                if (event_data.type === 'group_class') {
                    $('#appointment_list_half').removeClass('col-12');
                    $('#appointment_list_half').addClass('col-6');
                    $.ajax({
                        url: "ajax/get_group_class_details.php",
                        type: "POST",
                        data: {PK_APPOINTMENT_MASTER: info.event.id},
                        async: false,
                        cache: false,
                        success: function (result) {
                            $('#appointment_details_div').html(result);
                            $('#edit_appointment_half').show();
                            $('.multi_sumo_select').SumoSelect({placeholder: 'Select Customer', selectAll: true, search:true, searchText:"Search Customer"});

                            $('.datepicker-normal-calendar').datepicker({
                                format: 'mm/dd/yyyy',
                            });

                            $('.timepicker-normal').timepicker({
                                timeFormat: 'hh:mm p',
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
                            data: {PK_EVENT: info.event.id},
                            async: false,
                            cache: false,
                            success: function (result) {
                                $('#appointment_details_div').html(result);
                                $('#edit_appointment_half').show();

                                /*$('.datepicker-normal-calendar').datepicker({
                                    format: 'mm/dd/yyyy',
                                });

                                $('.timepicker-normal').timepicker({
                                    timeFormat: 'hh:mm p',
                                });*/
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
            data: {FUNCTION_NAME:'copyAppointment', OPERATION:operation, PK_ID:PK_ID, TYPE:TYPE, SERVICE_PROVIDER_ID:SERVICE_PROVIDER_ID, START_DATE_TIME:START_DATE_TIME},
            async: false,
            cache: false,
            success: function (data) {
                getServiceProviderCount();
                calendar.refetchEvents();
            }
        });
    }

    function modifyAppointment(info) {
        let event_data = info.event.extendedProps;
        let TYPE = event_data.type;
        let PK_ID = info.event.id;
        let SERVICE_PROVIDER_ID = (info.newResource) ? info.newResource.id : 0;
        let START_DATE_TIME = moment.utc(info.event._instance.range.start).format();
        let END_DATE_TIME = moment.utc(info.event._instance.range.end).format();

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: "POST",
            data: {FUNCTION_NAME:'modifyAppointment', PK_ID:PK_ID, TYPE:TYPE, SERVICE_PROVIDER_ID:SERVICE_PROVIDER_ID, START_DATE_TIME:START_DATE_TIME, END_DATE_TIME:END_DATE_TIME},
            async: false,
            cache: false,
            success: function (data) {
                getServiceProviderCount();
                calendar.refetchEvents();
            }
        });
    }

    function getServiceProviderCount() {
        let currentDate = new Date(calendar.getDate());
        let day = currentDate.getDate();
        let month = currentDate.getMonth()+1;
        let year = currentDate.getFullYear();

        let all_service_provider = $('.fc-resource-cell').map(function(){
            return $(this).data('resource-id');
        }).get();

        //$("#CHOOSE_DATE").val(month+'/'+day+'/'+year);

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: "POST",
            data: {FUNCTION_NAME:'getServiceProviderCount', currentDate:year+'-'+month+'-'+day, all_service_provider:all_service_provider},
            async: false,
            cache: false,
            success: function (result) {
                let appointment_data = JSON.parse(result);
                for(let i=0; i<appointment_data.length; i++) {
                    $('th[data-resource-id="'+appointment_data[i].SERVICE_PROVIDER_ID+'"]').text(appointment_data[i].SERVICE_PROVIDER_NAME+' - '+appointment_data[i].APPOINTMENT_COUNT);
                }
            }
        });
    }

    $(document).on('submit', '#search_form', function (event) {
        event.preventDefault();
        let IS_SELECTED = $('#IS_SELECTED').val();
        if (IS_SELECTED == 1) {
            let CHOOSE_DATE = $('#CHOOSE_DATE').val();
            let currentDate = new Date(CHOOSE_DATE);

            let day = currentDate.getDate();
            let month = currentDate.getMonth()+1;
            let year = currentDate.getFullYear();

            calendar.gotoDate(month+'/'+day+'/'+year);
            $('#IS_SELECTED').val(0);
        } else {
            calendar.refetchEvents();
        }
        calendar.refetchResources();
        setTimeout(function() { getServiceProviderCount() }, 1000);
    });

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
        if (type === 'ad_hoc') {
            url = "ajax/add_ad_hoc_appointment.php";
        }
        if (type === 'appointments') {
            url = "create_appointment.php";
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
    document.addEventListener("contextmenu", function (event) {
        let target = event.target;
        if (target.tagName === "IMG" || target.tagName === "VIDEO") {
            event.preventDefault(); // Prevent right-click menu
        }
    });

    // Optional: Disable right-click for the whole page
    // Uncomment the line below if you want to block right-click everywhere
    // document.addEventListener("contextmenu", (event) => event.preventDefault());

    // Function to delete uploaded image
    function ConfirmDeleteImage(PK_APPOINTMENT_MASTER, imageNumber)
    {
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
                    data: {FUNCTION_NAME: 'deleteImage', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER, imageNumber: imageNumber},
                    success: function (data) {
                        window.location.href = 'all_schedules.php';
                    }
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire("Cancelled", "Your image is safe.", "info"); // ✅ Show feedback for cancel
            }
        });
    }

    function ConfirmDeleteVideo(PK_APPOINTMENT_MASTER, videoNumber)
    {
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
                    data: {FUNCTION_NAME: 'deleteVideo', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER, videoNumber: videoNumber},
                    success: function (data) {
                        window.location.href = 'all_schedules.php';
                    }
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire("Cancelled", "Your video is safe.", "info"); // ✅ Show feedback for cancel
            }
        });
    }
</script>

</body>
</html>
