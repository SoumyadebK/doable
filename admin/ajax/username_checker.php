<?php
require_once('../../global/config.php');

if(isset($_POST['USER_NAME'])) {
    $USER_NAME = $_POST['USER_NAME'];
    $result = $db->Execute("SELECT * FROM `DOA_USERS` WHERE USER_NAME = '".$USER_NAME."'");

    if($result->RecordCount() == 0){
        echo '<span class="text-success">Username <b>'.$USER_NAME.'</b> is available!</span>';
    } else {
        echo '<span class="text-danger">Username <b>'.$USER_NAME.'</b> is already taken!</span>';
    }
}

?>