<?php
require_once('../../global/config.php');

if(isset($_POST['USER_NAME'])) {
    $USER_NAME = $_POST['USER_NAME'];
    $result = $db->Execute("SELECT * FROM `DOA_USERS` WHERE USER_NAME = '".$USER_NAME."'");

    if($result->RecordCount() > 0) {
        echo '<span class="text-danger">Username <b>'.$USER_NAME.'</b> is already taken!</span>';
    }
}

if(isset($_POST['CUSTOMER_ID'])) {
    $CUSTOMER_ID = $_POST['CUSTOMER_ID'];
    $result = $db->Execute("SELECT * FROM `DOA_USERS` WHERE USER_NAME = '".$CUSTOMER_ID."'");

    if($result->RecordCount() == 0){
        echo '';
    } else {
        echo '<span class="text-danger">Username <b>'.$CUSTOMER_ID.'</b> is already taken!</span>';
    }
}

if(isset($_POST['PHONE'])) {
    $PHONE = $_POST['PHONE'];
    $result = $db->Execute("SELECT * FROM `DOA_USERS` WHERE PHONE = '".$PHONE."'");

    if($result->RecordCount() == 0){
        echo '';
    } else {
        echo '<span class="text-danger"> <b>'.$PHONE.'</b> is already taken!</span>';
    }
}

if(isset($_POST['EMAIL_ID'])) {
    $EMAIL_ID = $_POST['EMAIL_ID'];
    $result = $db->Execute("SELECT * FROM `DOA_USERS` WHERE EMAIL_ID = '".$EMAIL_ID."'");

    if($result->RecordCount() == 0){
        echo '';
    } else {
        echo '<span class="text-danger"> <b>'.$EMAIL_ID.'</b> is already taken!</span>';
    }
}

?>