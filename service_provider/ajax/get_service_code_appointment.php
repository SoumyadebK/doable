<?php
require_once('../../global/config.php');
?>
<option value="">Select Service Code</option>
<?php
$row = $db->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE PK_SERVICE_MASTER = ".$_POST['PK_SERVICE_MASTER']);
while (!$row->EOF) { ?>
    <option value="<?php echo $row->fields['PK_SERVICE_CODE'];?>" data-duration="<?=$row->fields['DURATION']?>"><?=$row->fields['SERVICE_CODE']?></option>
<?php $row->MoveNext(); } ?>
