<?php
require_once('global/config.php');
$reminder = $db->Execute("SELECT APPOINTMENT_REMINDER, HOUR FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
if ($reminder->fields['APPOINTMENT_REMINDER'] == "Yes") {
    $hour = $reminder->fields['HOUR'];
    //pre_r($hour);
}

$appointment = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
pre_r($appointment);

?>
