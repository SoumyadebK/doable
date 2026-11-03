<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;
?>
<option value="">Select Service Code</option>
<?php
$row = $db_account->Execute("SELECT * FROM `DOA_SERVICE_CODE` WHERE `PK_SERVICE_MASTER` = ".$_POST['PK_SERVICE_MASTER']);
while (!$row->EOF) { ?>
    <option value="<?php echo $row->fields['PK_SERVICE_CODE'];?>" data-details="<?=$row->fields['DESCRIPTION']?>" data-price="<?=$row->fields['PRICE']?>"><?=$row->fields['SERVICE_CODE']?></option>
<?php $row->MoveNext(); } ?>
