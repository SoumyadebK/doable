<?php
require_once('../../global/config.php');

if (isset($_POST['LOCATION_CODE'])) {
    $LOCATION_CODE = $_POST['LOCATION_CODE'];
    $result = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE LOCATION_CODE = '" . $LOCATION_CODE . "'");

    if ($result->RecordCount() == 0) {
        echo '';
    } else {
        echo '<span class="text-danger"> <b>' . $LOCATION_CODE . '</b> is already taken!</span>';
    }
}
