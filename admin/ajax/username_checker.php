<?php
require_once('../../global/config.php');

if(isset($_POST['USER_ID'])) {
    $USER_ID = $_POST['USER_ID'];
    $result = $db->Execute("SELECT * FROM `DOA_USERS` WHERE USER_ID = '".$USER_ID."'");

    if($result->RecordCount() == 0){
        echo '<span class="text-success">Username <b>'.$USER_ID.'</b> is available!</span>';
    } else {
        echo '<span class="text-danger">Username <b>'.$USER_ID.'</b> is already taken!</span>';
    }
}

?>