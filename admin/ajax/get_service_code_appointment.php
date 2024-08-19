<?php
require_once('../../global/config.php');
?>
<?php
$row = $db_account->Execute("SELECT DOA_SERVICE_CODE.*, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_SERVICE_CODE.PK_SERVICE_CODE = DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$_POST['PK_ENROLLMENT_MASTER']." AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = ".$_POST['PK_SERVICE_MASTER']);
while (!$row->EOF) { ?>
    <option value="<?php echo $row->fields['PK_SERVICE_CODE'];?>" data-duration="<?=$row->fields['DURATION']?>"><?=$row->fields['SERVICE_CODE']?></option>
<?php $row->MoveNext(); } ?>
