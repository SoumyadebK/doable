<?php
require_once('../../global/config.php');
?>
<option value="">Select</option>
<?php
$row = $db->Execute("SELECT DOA_SERVICE_CODE.*, DOA_FREQUENCY.FREQUENCY FROM DOA_SERVICE_CODE LEFT JOIN DOA_FREQUENCY ON DOA_SERVICE_CODE.PK_FREQUENCY = DOA_FREQUENCY.PK_FREQUENCY WHERE PK_SERVICE_MASTER = ".$_POST['PK_SERVICE_MASTER']);
while (!$row->EOF) { ?>
    <option value="<?php echo $row->fields['PK_SERVICE_CODE'];?>" data-service_details="<?=$row->fields['DESCRIPTION']?>" data-frequency="<?=$row->fields['FREQUENCY']?>" data-price="<?=$row->fields['PRICE']?>"><?=$row->fields['SERVICE_CODE']?></option>
<?php $row->MoveNext(); } ?>
