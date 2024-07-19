<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$primary_location = $_GET['primary_location'];
$selected_location = explode(',', $_GET['selected_location']);
?>
<?php
$row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION != '$primary_location' AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
while (!$row->EOF) { ?>
    <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=in_array($row->fields['PK_LOCATION'], $selected_location)?"selected":""?>><?=$row->fields['LOCATION_NAME']?></option>
<?php $row->MoveNext(); } ?>
