<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "All Appointments";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$appointment_status = empty($_GET['STATUS_CODE']) ? '1, 2, 3, 5, 7, 8' : $_GET['STATUS_CODE'];

$appointment_type = '';
$APPOINTMENT_TYPE_QUERY = " AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC', 'GROUP') ";
if (isset($_GET['APPOINTMENT_TYPE']) && $_GET['APPOINTMENT_TYPE'] != '') {
    $appointment_type = $_GET['APPOINTMENT_TYPE'];
    $APPOINTMENT_TYPE_QUERY = " AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = '$appointment_type' ";
}

$SERVICE_PROVIDER_ID = ' ';
$APPOINTMENT_SERVICE_PROVIDER_ID = ' ';
if(isset($_GET['SERVICE_PROVIDER_ID']) && $_GET['SERVICE_PROVIDER_ID'] != ''){
    $service_providers = implode(',', $_GET['SERVICE_PROVIDER_ID']);
    $SERVICE_PROVIDER_ID = " AND DOA_USERS.PK_USER IN (".$service_providers.") ";
    $APPOINTMENT_SERVICE_PROVIDER_ID = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$service_providers.") ";
}

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.COMMENT,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            DOA_SCHEDULING_CODE.SCHEDULING_CODE,
                            GROUP_CONCAT(DISTINCT(DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER) SEPARATOR ',') AS SERVICE_PROVIDER_ID,
                            GROUP_CONCAT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) SEPARATOR ',') AS CUSTOMER_NAME
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                        AND DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN ($appointment_status)
                        ".$APPOINTMENT_TYPE_QUERY." 
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A' ".$APPOINTMENT_SERVICE_PROVIDER_ID."
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC";

$SPECIAL_APPOINTMENT_QUERY = "SELECT
                                    DOA_SPECIAL_APPOINTMENT.*,
                                    DOA_APPOINTMENT_STATUS.STATUS_CODE,
                                    DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                                    DOA_SCHEDULING_CODE.COLOR_CODE,
                                    DOA_SCHEDULING_CODE.DURATION,
                                    GROUP_CONCAT(SERVICE_PROVIDER.PK_USER SEPARATOR ',') AS SERVICE_PROVIDER_ID
                                FROM
                                    `DOA_SPECIAL_APPOINTMENT`
                                LEFT JOIN DOA_SPECIAL_APPOINTMENT_USER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT
                                LEFT JOIN DOA_MASTER.DOA_USERS AS SERVICE_PROVIDER ON DOA_SPECIAL_APPOINTMENT_USER.PK_USER = SERVICE_PROVIDER.PK_USER
                                LEFT JOIN DOA_MASTER.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS
                                LEFT JOIN DOA_SCHEDULING_CODE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE = DOA_SPECIAL_APPOINTMENT.PK_SCHEDULING_CODE
                                AND DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN ($appointment_status)
                                GROUP BY DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT";

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveAppointmentData'){
    unset($_POST['TIME']);
    unset($_POST['FUNCTION_NAME']);
    if (empty($_POST['START_TIME']) || empty($_POST['END_TIME'])){
        unset($_POST['START_TIME']);
        unset($_POST['END_TIME']);
    }
    $SERVICE_PROVIDER_ID = $_POST['SERVICE_PROVIDER_ID'];
    unset($_POST['SERVICE_PROVIDER_ID']);
    /*$session_cost = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_SERVICE_MASTER = '$_POST[PK_SERVICE_MASTER]' AND PK_SERVICE_CODE = '$_POST[PK_SERVICE_CODE]'");
    $price_per_session = $session_cost->fields['PRICE_PER_SESSION'];*/
    if(empty($_POST['PK_APPOINTMENT_MASTER'])){
        $_POST['PK_APPOINTMENT_STATUS'] = 1;
        $_POST['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $_POST['ACTIVE'] = 1;
        $_POST['CREATED_BY']  = $_SESSION['PK_USER'];
        $_POST['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_APPOINTMENT_MASTER', $_POST, 'insert');
    }else{
        //$_POST['ACTIVE'] = $_POST['ACTIVE'];
        if($_FILES['IMAGE']['name'] != ''){
            $extn 			= explode(".",$_FILES['IMAGE']['name']);
            $iindex			= count($extn) - 1;
            $rand_string 	= time()."-".rand(100000,999999);
            $file11			= 'appointment_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
            $extension   	= strtolower($extn[$iindex]);

            if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
                $image_path    = '../uploads/appointment_image/'.$file11;
                move_uploaded_file($_FILES['IMAGE']['tmp_name'], $image_path);
                $APPOINTMENT_DATA['IMAGE'] = $image_path;
            }
        }

        if($_FILES['VIDEO']['name'] != ''){
            $extn 			= explode(".",$_FILES['VIDEO']['name']);
            $iindex			= count($extn) - 1;
            $rand_string 	= time()."-".rand(100000,999999);
            $file11			= 'appointment_video_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
            $extension   	= strtolower($extn[$iindex]);

            if($extension == "mp4" || $extension == "avi" || $extension == "mov" || $extension == "wmv") {
                $video_path    = '../uploads/appointment_video/'.$file11;
                move_uploaded_file($_FILES["VIDEO"]["tmp_name"], $video_path);
                $APPOINTMENT_DATA['VIDEO'] = $video_path;
            }
        }
        $time = $db_account->Execute("SELECT DURATION FROM DOA_SCHEDULING_CODE WHERE PK_SCHEDULING_CODE = ".$_POST['PK_SCHEDULING_CODE']);
        $duration = $time->fields['DURATION'];
        $startTime = date('H:i:s', strtotime($_POST['START_TIME']));
        if ($duration > 0){
            $convertedTime = date('H:i:s',strtotime('+'.$duration.'minutes', strtotime($startTime)));
        } else {
            $convertedTime = date('H:i:s',strtotime('+30 minutes', strtotime($startTime)));
        }
        $APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($convertedTime));
        $APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
        $APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
        $APPOINTMENT_DATA['NO_SHOW'] = $_POST['NO_SHOW'];
        $APPOINTMENT_DATA['COMMENT'] = $_POST['COMMENT'];
        $APPOINTMENT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $APPOINTMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'update'," PK_APPOINTMENT_MASTER =  '$_POST[PK_APPOINTMENT_MASTER]'");

        $APPOINTMENT_SP_DATA['PK_USER'] = $SERVICE_PROVIDER_ID;
        db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $APPOINTMENT_SP_DATA, 'update'," PK_APPOINTMENT_MASTER =  '$_POST[PK_APPOINTMENT_MASTER]'");

        /*if ($_POST['PK_APPOINTMENT_STATUS'] == 2 || ($_POST['PK_APPOINTMENT_STATUS'] == 4 && $_POST['NO_SHOW'] == 'Charge')) {
            $enrollment_balance = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '$_POST[PK_ENROLLMENT_MASTER]'");
            if ($enrollment_balance->RecordCount() > 0) {
                $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_USED'] = $enrollment_balance->fields['TOTAL_BALANCE_USED'] + $price_per_session;
                $ENROLLMENT_BALANCE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                $ENROLLMENT_BALANCE_DATA['EDITED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
            }
        }*/
    }

    if (isset($_POST['PK_APPOINTMENT_STATUS'])) {
        $appointment_data = $db_account->Execute("SELECT * FROM DOA_APPOINTMENT_MASTER WHERE PK_APPOINTMENT_MASTER =  '$_POST[PK_APPOINTMENT_MASTER]'");
        if ($appointment_data->RecordCount() > 0) {
            $APPOINTMENT_STATUS_HISTORY_DATA['PK_APPOINTMENT_MASTER'] = $appointment_data->fields['PK_APPOINTMENT_MASTER'];
            $APPOINTMENT_STATUS_HISTORY_DATA['PK_USER'] = $_SESSION['PK_USER'];
            $APPOINTMENT_STATUS_HISTORY_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
            $APPOINTMENT_STATUS_HISTORY_DATA['TIME_STAMP'] = date("Y-m-d H:i");
            db_perform_account('DOA_APPOINTMENT_STATUS_HISTORY', $APPOINTMENT_STATUS_HISTORY_DATA, 'insert');
        }

        if ($_POST['PK_APPOINTMENT_STATUS'] == 2) {
            updateSessionCompletedCount($_POST['PK_APPOINTMENT_MASTER']);
        } elseif ($_POST['PK_APPOINTMENT_STATUS'] == 6) {
            updateSessionCreatedCountByStatus($_POST['PK_APPOINTMENT_MASTER']);
        }
    }

    //rearrangeSerialNumber($_POST['PK_ENROLLMENT_MASTER'], $price_per_session);

    header("location:all_schedules.php?view=table");
}

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
        //$_POST['ACTIVE'] = $_POST['ACTIVE'];
        if($_FILES['IMAGE']['name'] != ''){
            $extn 			= explode(".",$_FILES['IMAGE']['name']);
            $iindex			= count($extn) - 1;
            $rand_string 	= time()."-".rand(100000,999999);
            $file11			= 'appointment_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
            $extension   	= strtolower($extn[$iindex]);

            if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
                $image_path    = '../uploads/appointment_image/'.$file11;
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

    header("location:all_schedules.php?view=table");
}

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveSpecialAppointmentData'){
    $SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
    $SPECIAL_APPOINTMENT_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
    $SPECIAL_APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
    $SPECIAL_APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
    $SPECIAL_APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
    $SPECIAL_APPOINTMENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
    $PK_SPECIAL_APPOINTMENT = $_POST['PK_SPECIAL_APPOINTMENT'];

    $SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
    $SPECIAL_APPOINTMENT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
    $SPECIAL_APPOINTMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
    db_perform_account('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'update'," PK_SPECIAL_APPOINTMENT =  '$PK_SPECIAL_APPOINTMENT'");

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
    header("location:all_schedules.php?view=table");
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
    $GROUP_CLASS_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
    $GROUP_CLASS_DATA['COMMENT'] = $_POST['COMMENT'];
    if($_FILES['IMAGE']['name'] != ''){
        $extn 			= explode(".",$_FILES['IMAGE']['name']);
        $iindex			= count($extn) - 1;
        $rand_string 	= time()."-".rand(100000,999999);
        $file11			= 'appointment_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
        $extension   	= strtolower($extn[$iindex]);

        if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
            $image_path    = '../uploads/appointment_image/'.$file11;
            move_uploaded_file($_FILES['IMAGE']['tmp_name'], $image_path);
            $GROUP_CLASS_DATA['IMAGE'] = $image_path;
        }
    }
    $GROUP_CLASS_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
    $GROUP_CLASS_DATA['EDITED_ON'] = date("Y-m-d H:i");
    //pre_r($GROUP_CLASS_DATA);
    if (isset($_POST['GROUP_CLASS_ID'])) {
        db_perform_account('DOA_APPOINTMENT_MASTER', $GROUP_CLASS_DATA, 'update', " GROUP_CLASS_ID =  '$_POST[GROUP_CLASS_ID]'");
    } else {
        $GROUP_CLASS_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
        db_perform_account('DOA_APPOINTMENT_MASTER', $GROUP_CLASS_DATA, 'update', " PK_APPOINTMENT_MASTER =  '$PK_APPOINTMENT_MASTER'");
    }

    $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_CUSTOMER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER'");
    if (isset($_POST['PK_USER_MASTER'])) {
        for ($j = 0; $j < count($_POST['PK_USER_MASTER']); $j++) {
            $GROUP_CLASS_CUSTOMER_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
            $GROUP_CLASS_CUSTOMER_DATA['PK_USER_MASTER'] = $_POST['PK_USER_MASTER'][$j];
            db_perform_account('DOA_APPOINTMENT_CUSTOMER', $GROUP_CLASS_CUSTOMER_DATA, 'insert');
            updateSessionCreatedCountGroupClass($PK_APPOINTMENT_MASTER, $_POST['PK_USER_MASTER'][$j]);
            if ($_POST['PK_APPOINTMENT_STATUS'] == 2) {
                updateSessionCompletedCountGroupClass($PK_APPOINTMENT_MASTER, $_POST['PK_USER_MASTER'][$j]);
            }
        }
    }

    if (isset($_POST['SERVICE_PROVIDER_ID'])) {
        $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_SERVICE_PROVIDER` WHERE `PK_APPOINTMENT_MASTER` = '$PK_APPOINTMENT_MASTER'");
        for ($k = 0; $k < count($_POST['SERVICE_PROVIDER_ID']); $k++) {
            $GROUP_CLASS_USER_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
            $GROUP_CLASS_USER_DATA['PK_USER'] = $_POST['SERVICE_PROVIDER_ID'][$k];
            db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $GROUP_CLASS_USER_DATA, 'insert');
        }
    }

    if (isset($_POST['PK_APPOINTMENT_STATUS'])) {
        $appointment_data = $db_account->Execute("SELECT * FROM DOA_APPOINTMENT_MASTER WHERE PK_APPOINTMENT_MASTER =  '$_POST[PK_APPOINTMENT_MASTER]'");
        if ($appointment_data->RecordCount() > 0) {
            $APPOINTMENT_STATUS_HISTORY_DATA['PK_APPOINTMENT_MASTER'] = $appointment_data->fields['PK_APPOINTMENT_MASTER'];
            $APPOINTMENT_STATUS_HISTORY_DATA['PK_USER'] = $_SESSION['PK_USER'];
            $APPOINTMENT_STATUS_HISTORY_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
            $APPOINTMENT_STATUS_HISTORY_DATA['TIME_STAMP'] = date("Y-m-d H:i");
            db_perform_account('DOA_APPOINTMENT_STATUS_HISTORY', $APPOINTMENT_STATUS_HISTORY_DATA, 'insert');
        }

        if ($_POST['PK_APPOINTMENT_STATUS'] == 2) {
            updateSessionCompletedCount($_POST['PK_APPOINTMENT_MASTER']);
        } elseif ($_POST['PK_APPOINTMENT_STATUS'] == 6) {
            updateSessionCreatedCountByStatus($_POST['PK_APPOINTMENT_MASTER']);
        }
    }

    header("location:all_schedules.php?view=table");
}

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] === 'saveEventData'){
    $PK_EVENT = $_POST['PK_EVENT'];
    if(!empty($_POST)){
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

        $db_account->Execute("DELETE FROM `DOA_EVENT_IMAGE` WHERE `PK_EVENT` = '$PK_EVENT'");
        for($i = 0; $i < count($_FILES['IMAGE']['name']); $i++){
            $EVENT_IMAGE_DATA['PK_EVENT'] = $PK_EVENT;
            if(!empty($_FILES['IMAGE']['name'][$i])){
                $extn 			= explode(".",$_FILES['IMAGE']['name'][$i]);
                $iindex			= count($extn) - 1;
                $rand_string 	= time()."-".rand(100000,999999);
                $file11			= 'event_image_'.$PK_EVENT.'_'.$rand_string.".".$extn[$iindex];
                $extension   	= strtolower($extn[$iindex]);

                $image_path    = '../uploads/event_image/'.$file11;
                move_uploaded_file($_FILES['IMAGE']['tmp_name'][$i], $image_path);
                $EVENT_IMAGE_DATA['IMAGE'] = $image_path;
            } else {
                $EVENT_IMAGE_DATA['IMAGE'] = $_POST['IMAGE_PATH'][$i];
            }
            $EVENT_IMAGE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
            $EVENT_IMAGE_DATA['CREATED_ON']  = date("Y-m-d H:i");
            db_perform_account('DOA_EVENT_IMAGE', $EVENT_IMAGE_DATA, 'insert');
        }
        header("location:all_schedules.php?view=table");
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
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>


<script src='../assets/full_calendar_new/moment.min.js'></script>

<link href='../assets/fullcalendar4/fullcalendar.min.css' rel='stylesheet' />
<link href='../assets/fullcalendar4/scheduler.min.css' rel='stylesheet' />
<script src='../assets/fullcalendar4/fullcalendar.min.js'></script>
<script src='../assets/fullcalendar4/scheduler.min.js'></script>

<!--<link href='../assets/packages/core/main.css' rel='stylesheet' />
<link href='../assets/packages/daygrid/main.css' rel='stylesheet' />
<link href='../assets/packages/timegrid/main.css' rel='stylesheet' />
<link href='../assets/packages/timeline/main.css' rel='stylesheet' />
<link href='../assets/packages/resource-timeline/main.css' rel='stylesheet' />
<script src='../assets/packages/core/main.js'></script>
<script src='../assets/packages/interaction/main.js'></script>
<script src='../assets/packages/daygrid/main.js'></script>
<script src='../assets/packages/timegrid/main.js'></script>
<script src='../assets/packages/resource-common/main.js'></script>
<script src='../assets/packages/resource-daygrid/main.js'></script>
<script src='../assets/packages/resource-timegrid/main.js'></script>
<script src='../assets/packages/timeline/main.js'></script>
<script src='../assets/packages/resource-common/main.js'></script>
<script src='../assets/packages/resource-timeline/main.js'></script>-->

<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.7.2/main.css">-->


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

    .SumoSelect {
        width: 100%;
    }
    #add_buttons {
        z-index: 500;
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
                    <button type="button" id="appointments" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='create_appointment.php'"><i class="fa fa-plus-circle"></i> Appointments</button>
                    <button type="button" id="operations" class="btn btn-info d-none d-lg-block m-l-10 text-white" onclick="window.location.href='operations.php'"><i class="ti-layers-alt"></i> <?=$operation_tab_title?></button>
                </div>
            </div>

            <div class="row">
                <div id="appointment_list_half" class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="search_form" class="form-material form-horizontal" action="" method="get" style="margin-bottom: -30px;">
                                <div class="col-12 row m-10">
                                    <div class="col-2">
                                        <h5 class="card-title"><?=$title?></h5>
                                    </div>
                                    <div class="col-2" >
                                        <div class="form-material form-horizontal">
                                            <select class="form-control" name="STATUS_CODE" id="STATUS_CODE" onchange="$('#search_form').submit();">
                                                <option value="">Select Status</option>
                                                <?php
                                                $row = $db->Execute("SELECT * FROM DOA_APPOINTMENT_STATUS WHERE ACTIVE = 1");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS'];?>" <?=($row->fields['PK_APPOINTMENT_STATUS'] == $appointment_status)?"selected":""?>><?=$row->fields['APPOINTMENT_STATUS']?></option>
                                                    <?php $row->MoveNext(); } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <div class="input-group">
                                            <input type="text" id="CHOOSE_DATE" name="CHOOSE_DATE" class="form-control datepicker-normal" placeholder="Choose Date" value="<?=($_GET['CHOOSE_DATE']) ?? ''?>">&nbsp;&nbsp;&nbsp;&nbsp;
                                            <select class="SERVICE_PROVIDER_ID multi_sumo_select" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID" multiple>
                                                <?php
                                                $row = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER=DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND ACTIVE=1 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY NAME");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?=$row->fields['PK_USER']?>" <?=(!empty($service_providers) && in_array($row->fields['PK_USER'], explode(',', $service_providers)))?"selected":""?>><?=$row->fields['NAME']?></option>
                                                <?php $row->MoveNext(); } ?>
                                            </select>
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white input-form-btn m-b-1" style="margin-left: 2px; height: 33px"><i class="fa fa-search"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-2" >
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
                                    <p>
                                        <input type='radio' name="copy_move" id='drop-copy' checked/>
                                        <label for='drop-copy'>Copy</label>

                                        <input type='radio' name="copy_move" id='drop-remove'/>
                                        <label for='drop-remove'>Move</label>
                                    </p>
                                </div>
                            </div>

                    </div>
                </div>

                <div id="edit_appointment_half" class="col-6" style="display: none;">
                    <div class="card">
                        <div class="card-body">
                            <a href="javascript:;" onclick="closeEditAppointment()" style="float: right; font-size: 25px;">&times;</a>
                            <div class="card-body" id="appointment_details_div">

                            </div>
                        </div>
                    </div>
                </div>

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


<?php require_once('../includes/footer.php');?>

<script>
    $('.datepicker-normal').datepicker({
        onSelect: function () {
            $("#search_form").submit();
        },
        format: 'mm/dd/yyyy',
    });
</script>

<!--<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.7.2/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.7.2/locales-all.min.js"></script>-->

<!--<script src='../assets/full_calendar_new/moment.min.js'></script>
<script src='../assets/full_calendar_new/jquery.min.js'></script>
<script src='../assets/full_calendar_new/fullcalendar.min.js'></script>
<script src='../assets/full_calendar_new/scheduler.min.js'></script>
<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>-->

<!--<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>-->

<script>
    $('.multi_sumo_select').SumoSelect({placeholder: 'Select Service Provider', selectAll: true});

    let finalArray = [];
    let defaultResources = [];
    function getAllCalendarData(){
        defaultResources = [
            <?php
            $service_provider_data = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND ACTIVE = 1 AND DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") ".$SERVICE_PROVIDER_ID." AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']. " ORDER BY DISPLAY_ORDER");
            $resourceIdArray = [];
            while (!$service_provider_data->EOF) {
                $resourceIdArray[] = $service_provider_data->fields['PK_USER'];?>
                {
                    id: <?=$service_provider_data->fields['PK_USER']?>,
                    title: '<?=$service_provider_data->fields['NAME'].' - 0'?>',
                },
                <?php $service_provider_data->MoveNext();
            } $resourceIdArray = json_encode($resourceIdArray) ?>
        ];

        let appointmentArray = [];
        <?php if ($appointment_type == 'NORMAL' || $appointment_type == 'GROUP' || $appointment_type == '') { ?>
        appointmentArray = [
            <?php
            $appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY);

            $paid_session = 0;
            while (!$appointment_data->EOF) {
                if ($appointment_data->fields['APPOINTMENT_TYPE'] === 'NORMAL' || $appointment_data->fields['APPOINTMENT_TYPE'] === 'AD-HOC'){
                    $title = $appointment_data->fields['CUSTOMER_NAME'].' ('.$appointment_data->fields['SERVICE_NAME'].'-'.$appointment_data->fields['SERVICE_CODE'].') '.'\n'.(($appointment_data->fields['ENROLLMENT_ID'] === 0) ? '(Ad-Hoc)' : $appointment_data->fields['ENROLLMENT_ID']).' - '.$appointment_data->fields['SERIAL_NUMBER'].(($appointment_data->fields['IS_PAID'] == 1)?' (Paid)':' (Unpaid)');
                    $type = "appointment";
                } else {
                    $title = count(explode(',', $appointment_data->fields['CUSTOMER_NAME'])).' - '.$appointment_data->fields['GROUP_NAME'].' - '.$appointment_data->fields['SERVICE_NAME'].' - '.$appointment_data->fields['SERVICE_CODE'];
                    $type = "group_class";
                } ?>
            {
                id: <?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>,
                resourceIds: <?=json_encode(explode(',', $appointment_data->fields['SERVICE_PROVIDER_ID']))?>,
                title: '<?=$title?>',
                start: new Date(<?=date("Y",strtotime($appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($appointment_data->fields['START_TIME']))?>,<?=date("i",strtotime($appointment_data->fields['START_TIME']))?>,1,1),
                end: new Date(<?=date("Y",strtotime($appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($appointment_data->fields['END_TIME']))?>,<?=date("i",strtotime($appointment_data->fields['END_TIME']))?>,1,1),
                color: '<?=$appointment_data->fields['COLOR_CODE']?>',
                type: '<?=$type?>',
                status: '<?=$appointment_data->fields['STATUS_CODE']?>',
                statusColor: '<?=$appointment_data->fields['APPOINTMENT_COLOR']?> !important',
                comment: '<?=($appointment_data->fields['COMMENT'])?>',
                statusCode : '<?=$appointment_data->fields['SCHEDULING_CODE']?>',
            },
            <?php $appointment_data->MoveNext();
            } ?>
        ];
        <?php } ?>

        let specialAppointmentArray = [];
        <?php if ($appointment_type == 'TO-DO' || $appointment_type == '') { ?>
        specialAppointmentArray = [
            <?php $special_appointment_data = $db_account->Execute($SPECIAL_APPOINTMENT_QUERY);
            while (!$special_appointment_data->EOF) { ?>
            {
                id: <?=$special_appointment_data->fields['PK_SPECIAL_APPOINTMENT']?>,
                resourceIds: <?=json_encode(explode(',', $special_appointment_data->fields['SERVICE_PROVIDER_ID']))?>,
                title: '<?=$special_appointment_data->fields['TITLE']?>',
                start: new Date(<?=date("Y",strtotime($special_appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($special_appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($special_appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($special_appointment_data->fields['START_TIME']))?>,<?=date("i",strtotime($special_appointment_data->fields['START_TIME']))?>,1,1),
                end: new Date(<?=date("Y",strtotime($special_appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($special_appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($special_appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($special_appointment_data->fields['END_TIME']))?>,<?=date("i",strtotime($special_appointment_data->fields['END_TIME']))?>,1,1),
                color: '<?=$special_appointment_data->fields['COLOR_CODE']?>',
                type: 'special_appointment',
                status: '<?=$special_appointment_data->fields['STATUS_CODE']?>',
                statusColor: '<?=$special_appointment_data->fields['APPOINTMENT_COLOR']?> !important',
            },
            <?php $special_appointment_data->MoveNext();
            } ?>
        ];
        <?php } ?>

        let eventArray = [];
        <?php if ($appointment_type == 'EVENT' || $appointment_type == '') { ?>
        eventArray = [
            <?php $event_data = $db_account->Execute("SELECT DISTINCT DOA_EVENT.*, DOA_EVENT_TYPE.EVENT_TYPE, DOA_EVENT_TYPE.COLOR_CODE FROM DOA_EVENT INNER JOIN DOA_EVENT_LOCATION ON DOA_EVENT.PK_EVENT = DOA_EVENT_LOCATION.PK_EVENT LEFT JOIN DOA_EVENT_TYPE ON DOA_EVENT.PK_EVENT_TYPE = DOA_EVENT_TYPE.PK_EVENT_TYPE WHERE DOA_EVENT.ACTIVE = 1 AND DOA_EVENT_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_EVENT.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' ORDER BY DOA_EVENT.START_DATE DESC LIMIT 2000");
            while (!$event_data->EOF) {
            if (isset($event_data->fields['END_DATE']) && $event_data->fields['ALL_DAY'] == 1) {
                $END_DATE = date('Y-m-d', strtotime($event_data->fields['END_DATE'].'+1 day'));
            }else {
                $END_DATE = ($event_data->fields['END_DATE'] == '0000-00-00') ? $event_data->fields['START_DATE'] : $event_data->fields['END_DATE'];
            }
            $END_TIME = ($event_data->fields['END_TIME'] == '00:00:00')?$event_data->fields['START_TIME']:$event_data->fields['END_TIME'];
            $open_close_time_diff = (strtotime($CLOSE_TIME) - strtotime($OPEN_TIME));
            $start_end_time_diff = strtotime($END_DATE.' '.$END_TIME) - strtotime($event_data->fields['START_DATE'].' '.$event_data->fields['START_TIME']);?>
            {
                id: <?=$event_data->fields['PK_EVENT']?>,
                resourceIds: <?=$resourceIdArray?>,
                title: '<?=$event_data->fields['HEADER']?>',
                start: new Date(<?=date("Y",strtotime($event_data->fields['START_DATE']))?>,<?=intval((date("m",strtotime($event_data->fields['START_DATE'])) - 1))?>,<?=intval(date("d",strtotime($event_data->fields['START_DATE'])))?>,<?=date("H",strtotime($event_data->fields['START_TIME']))?>,<?=date("i",strtotime($event_data->fields['START_TIME']))?>,1,1),
                end: new Date(<?=date("Y",strtotime($END_DATE))?>,<?=intval((date("m",strtotime($END_DATE)) - 1))?>,<?=intval(date("d",strtotime($END_DATE)))?>,<?=date("H",strtotime($END_TIME))?>,<?=date("i",strtotime($END_TIME))?>,1,1),
                color: '<?=$event_data->fields['COLOR_CODE']?>',
                type: 'event',
                allDay: <?=(($event_data->fields['ALL_DAY'] == 1) ? 1 : (($start_end_time_diff >= $open_close_time_diff) ? 1 : 0))?>
            },
            <?php $event_data->MoveNext();
            } ?>
        ];
        <?php } ?>

        finalArray = appointmentArray.concat(eventArray).concat(specialAppointmentArray);
    }

    var calendar;
    document.addEventListener('DOMContentLoaded', function() {
        getAllCalendarData();
        let open_time = '<?=$OPEN_TIME?>';
        let close_time = '<?=$CLOSE_TIME?>';
        let clickCount = 0;


        var Calendar = FullCalendar.Calendar;
        var Draggable = FullCalendar.Draggable;

        var containerEl = document.getElementById('external-events');
        var calendarEl = document.getElementById('calendar');
        var checkbox = document.getElementById('drop-remove');

        //var resourceTimeGridPlugin = FullCalendar.timeGrid;

        // initialize the external events
        // -----------------------------------------------------------------

        new Draggable(containerEl, {
            itemSelector: '.fc-event',
            eventData: function(eventEl) {
                let color = eventEl.attributes["data-color"].value;
                let type = eventEl.attributes["data-type"].value;
                return {
                    title: eventEl.innerText,
                    backgroundColor: color,
                    type: type
                };
            }
        });

        // initialize the calendar
        // -----------------------------------------------------------------

        calendar = new Calendar(calendarEl, {
            //timeZone: 'UTC',
            schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
            //plugins: [ 'interaction', 'dayGrid', 'timeGrid', 'resourceTimeline' ],
            editable: true,
            selectable: true,
            eventLimit: true,
            scrollTime: '00:00',
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'agendaDay,agendaWeek,month'
            },
            defaultView: 'agendaDay',
            //slotLabelInterval: '02:30:00',
            slotDuration: '<?=$INTERVAL?>',
            slotLabelInterval: {minutes: 15},
            /*slotLabelFormat: [
                { weekday: 'short', day: 'numeric' }, // top level of text
                {
                    hour: 'numeric',
                    minute: '2-digit',
                    omitZeroMinute: false,
                    meridiem: 'short'
                } // lower level of text
            ],*/
            slotMinTime: '08:00:00',
            slotMaxTime: '17:00:00',
            resourceAreaWidth: '20%',
            resourceAreaHeaderContent: 'Service Provider',
            //defaultView: 'resourceTimelineDay',

            //resourceAreaWidth: '15%',
            contentHeight: 665,
            windowResize: true,
            droppable: true,
            drop: function(info) {
                if (checkbox.checked) {
                    info.draggedEl.parentNode.removeChild(info.draggedEl);
                }
                copyAppointment(info);
            },

            /*viewRender: function(view) {
                if(view.type == 'agendaDay') {
                    $('#calendar').fullCalendar( 'removeEventSource', ev1 );
                    $('#calendar').fullCalendar( 'addEventSource', ev2 );
                    return;
                } else {
                    $('#calendar').fullCalendar( 'removeEventSource', ev2 );
                    $('#calendar').fullCalendar( 'addEventSource', ev1 );
                    return;
                }
            },*/

            //// uncomment this line to hide the all-day slot
            //allDaySlot: false,

            resources: defaultResources,
            events: finalArray,

            eventRender: function(info) {
                /*console.log(info.el);
                let event_data = info.event.extendedProps;
                let element = info.el;
                if (event_data.status) {
                    element.find(".fc-title").prepend(' <strong style="color: ' + event_data.statusColor + '">(' + event_data.status + ')</strong> ');
                }
                if (event_data.comment) {
                    element.find(".fc-title").prepend(' <i class="fa fa-comment-dots" style="font-size: 15px"></i> ');
                }
                if (event_data.statusCode) {
                    element.find(".fc-title").append(' <br><strong style="font-size: 13px">(' + event_data.statusCode + ')</strong> ');
                }*/

            },

            eventClick: function(info) {
                clickCount++;
                let singleClickTimer;
                if (clickCount === 1) {
                    singleClickTimer = setTimeout(function () {
                        if (clickCount === 1) {
                            showAppointmentEdit(info);
                        }
                        clickCount = 0;
                    }, 500);
                } else if (clickCount === 2) {
                    clearTimeout(singleClickTimer);
                    clickCount = 0;
                    $('#calendar-container').removeClass('col-12').addClass('col-10');
                    let event_data = info.event;
                    let event_data_ext_prop = info.event.extendedProps;
                    let TYPE = event_data_ext_prop.type;

                    $('#external-events').show().addClass('col-2').append("<div class='fc-event fc-h-event' data-id='"+event_data.id+"' data-color='"+event_data.backgroundColor+"' data-type='"+TYPE+"' style='background-color: "+event_data.backgroundColor+"'>"+event_data.title+"</div>"+'<a href="javascript:;" onclick="closeEditAppointment()" style="float: right; font-size: 25px;">&times;</a>');
                }
            },

            /*eventDragStop: function (info) {
              console.log(info.resourceIds);
            },*/

            eventDrop: function (info) {
                modifyAppointment(info);
            },

            dateClick: function(data) {
                let date = data.date;
                let resource_id = data.resource.id;
                clickCount++;
                let singleClickTimer;
                if (clickCount === 1) {
                    singleClickTimer = setTimeout(function () {
                        clickCount = 0;
                    }, 400);
                } else if (clickCount === 2) {
                    clearTimeout(singleClickTimer);
                    clickCount = 0;
                    window.location.href = "create_appointment.php?date="+moment(date).format()+"&SERVICE_PROVIDER_ID="+resource_id;
                    //openModel();
                }
            },
        });

        calendar.render();

        getServiceProviderCount();
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

    function showAppointmentEdit(info) {
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
                        $('.multi_sumo_select').SumoSelect({placeholder: 'Select Service Provider', selectAll: true});
                        $('.PK_SCHEDULING_CODE').SumoSelect({placeholder: 'Select Service Provider', selectAll: true});

                        $('.datepicker-normal').datepicker({
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

                            $('.datepicker-normal').datepicker({
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

                                /*$('.datepicker-normal').datepicker({
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

    function modifyAppointment(info) {
        let event_data = info.event.extendedProps;
        let TYPE = event_data.type;
        let PK_ID = info.event.id;
        let SERVICE_PROVIDER_ID = (info.newResource) ? info.newResource.id : 0;
        let START_DATE_TIME = moment.utc(info.event._instance.range.start).format();
        let END_DATE_TIME = moment.utc(info.event._instance.range.end).format();
        /*let DATE = START_DATE_TIME.getFullYear() + "-" + (START_DATE_TIME.getMonth()+1)  + "-" + START_DATE_TIME.getDate();
        let START_TIME = START_DATE_TIME.getUTCHours() + ":" + START_DATE_TIME.getUTCMinutes() + ":00";
        let END_TIME = END_DATE_TIME.getUTCHours() + ":" + END_DATE_TIME.getUTCMinutes() + ":00";*/

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: "POST",
            data: {FUNCTION_NAME:'modifyAppointment', PK_ID:PK_ID, TYPE:TYPE, SERVICE_PROVIDER_ID:SERVICE_PROVIDER_ID, START_DATE_TIME:START_DATE_TIME, END_DATE_TIME:END_DATE_TIME},
            async: false,
            cache: false,
            success: function (data) {
                console.log(data);
                getServiceProviderCount();
                if (TYPE === 'group_class') {
                    window.location.href = "all_schedules.php?CHOOSE_DATE="+data;
                }
            }
        });
    }

    function copyAppointment(info) {
        console.log(info);
        let eventEl = info.draggedEl;
        let PK_ID = eventEl.attributes["data-id"].value;
        let TYPE = eventEl.attributes["data-type"].value;

        let SERVICE_PROVIDER_ID = info.resource.id;
        let START_DATE_TIME = info.dateStr;

        console.log(TYPE,PK_ID,SERVICE_PROVIDER_ID,START_DATE_TIME);

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: "POST",
            data: {FUNCTION_NAME:'copyAppointment', PK_ID:PK_ID, TYPE:TYPE, SERVICE_PROVIDER_ID:SERVICE_PROVIDER_ID, START_DATE_TIME:START_DATE_TIME},
            async: false,
            cache: false,
            success: function (data) {
                /*console.log(data);
                getServiceProviderCount();
                if (TYPE === 'group_class') {
                    window.location.href = "all_schedules.php?CHOOSE_DATE="+data;
                }*/
            }
        });
    }

    function getServiceProviderCount() {
        let currentDate = new Date(calendar.getDate());
        let day = currentDate.getUTCDate();
        let month = currentDate.getMonth() + 1;
        let year = currentDate.getFullYear();

        let all_service_provider = $('.fc-resource-cell').map(function(){
            return $(this).data('resource-id');
        }).get();

        $("#CHOOSE_DATE").val(month+'/'+day+'/'+year);

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: "POST",
            data: {FUNCTION_NAME:'getServiceProviderCount', currentDate:year+'-'+month+'-'+day, all_service_provider:all_service_provider},
            async: false,
            cache: false,
            success: function (result) {
                let appointment_data = JSON.parse(result);
                for(let i=0; i<appointment_data.length; i++) {
                    $('tr[data-resource-id="'+appointment_data[i].SERVICE_PROVIDER_ID+'"]').text(appointment_data[i].SERVICE_PROVIDER_NAME+' - '+appointment_data[i].APPOINTMENT_COUNT);
                }
            }
        });
    }

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

</body>
</html>
