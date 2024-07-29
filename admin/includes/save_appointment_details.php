<?php
require_once('../../global/config.php');

global $db;
global $db_account;

if(isset($_FILES['IMAGE']['name']) && $_FILES['IMAGE']['name'] != ''){
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

if(isset($_FILES['VIDEO']['name']) && $_FILES['VIDEO']['name'] != ''){
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

$PK_APPOINTMENT_MASTER = $_POST['PK_APPOINTMENT_MASTER'];
$PK_APPOINTMENT_STATUS = $_POST['PK_APPOINTMENT_STATUS_NEW'];
$START_TIME = $_POST['START_TIME'];

$time = $db_account->Execute("SELECT DURATION FROM DOA_SCHEDULING_CODE WHERE PK_SCHEDULING_CODE = ".$_POST['PK_SCHEDULING_CODE']);
$duration = $time->fields['DURATION'];
$startTime = date('H:i:s', strtotime($START_TIME));
if ($duration > 0){
    $convertedTime = date('H:i:s',strtotime('+'.$duration.'minutes', strtotime($startTime)));
} else {
    $convertedTime = date('H:i:s',strtotime('+30 minutes', strtotime($startTime)));
}
$APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($START_TIME));
$APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($convertedTime));
$APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
$APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $PK_APPOINTMENT_STATUS;
$APPOINTMENT_DATA['COMMENT'] = $_POST['COMMENT'];
if (isset($_POST['NO_SHOW'])) {
    $APPOINTMENT_DATA['NO_SHOW'] = $_POST['NO_SHOW'];
}
if (isset($_POST['INTERNAL_COMMENT'])) {
    $APPOINTMENT_DATA['INTERNAL_COMMENT'] = $_POST['INTERNAL_COMMENT'];
}
$APPOINTMENT_DATA['IS_CHARGED'] = ($PK_APPOINTMENT_STATUS == 2) ? 1 : $_POST['IS_CHARGED'];
$APPOINTMENT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
$APPOINTMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");

if (isset($_POST['STANDING_ID'])) {
    db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'update', " STANDING_ID =  '$_POST[STANDING_ID]'");

    $appointment_id = [];
    $appointment_id_row = $db_account->Execute("SELECT `PK_APPOINTMENT_MASTER` FROM `DOA_APPOINTMENT_MASTER` WHERE `STANDING_ID` = '$_POST[STANDING_ID]'");
    while (!$appointment_id_row->EOF) {
        $appointment_id[] = $appointment_id_row->fields['PK_APPOINTMENT_MASTER'];
        $appointment_id_row->MoveNext();
    }

    $APPOINTMENT_SP_DATA['PK_USER'] = $_POST['SERVICE_PROVIDER_ID'];
    db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $APPOINTMENT_SP_DATA, 'update'," PK_APPOINTMENT_MASTER IN (".implode(',', $appointment_id).")");
} else {
    $GROUP_CLASS_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
    db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'update'," PK_APPOINTMENT_MASTER =  '$PK_APPOINTMENT_MASTER'");

    $APPOINTMENT_SP_DATA['PK_USER'] = $_POST['SERVICE_PROVIDER_ID'];
    db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $APPOINTMENT_SP_DATA, 'update'," PK_APPOINTMENT_MASTER =  '$PK_APPOINTMENT_MASTER'");
}




/*$COUNT_UPDATED = 0;
if ($_POST['IS_CHARGED_OLD'] != $_POST['IS_CHARGED']) {
    if ($_POST['IS_CHARGED'] == 1) {
        updateSessionCompletedCount($PK_APPOINTMENT_MASTER);
        $COUNT_UPDATED = 1;
    } elseif ($_POST['IS_CHARGED'] == 0) {
        updateSessionNotChargedCount($PK_APPOINTMENT_MASTER);
    }
} elseif ($_POST['IS_CHARGED'] == 1) {
    $COUNT_UPDATED = 1;
}*/

if ($_POST['PK_APPOINTMENT_STATUS_OLD'] != $_POST['PK_APPOINTMENT_STATUS_NEW']) {
    $APPOINTMENT_STATUS_HISTORY_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
    $APPOINTMENT_STATUS_HISTORY_DATA['PK_USER'] = $_SESSION['PK_USER'];
    $APPOINTMENT_STATUS_HISTORY_DATA['PK_APPOINTMENT_STATUS'] = $PK_APPOINTMENT_STATUS;
    $APPOINTMENT_STATUS_HISTORY_DATA['TIME_STAMP'] = date("Y-m-d H:i");
    db_perform_account('DOA_APPOINTMENT_STATUS_HISTORY', $APPOINTMENT_STATUS_HISTORY_DATA, 'insert');

    /*if ($PK_APPOINTMENT_STATUS == 2 && $COUNT_UPDATED == 0) {
        updateSessionCompletedCount($PK_APPOINTMENT_MASTER);
    } elseif ($PK_APPOINTMENT_STATUS == 6 && $_POST['IS_CHARGED'] == 0) {
        updateSessionCreatedCountByStatus($PK_APPOINTMENT_MASTER);
    }*/
}

updateSessionCreatedAndCompletedCount($PK_APPOINTMENT_MASTER);

header('Location: '.$_POST['REDIRECT_URL']);