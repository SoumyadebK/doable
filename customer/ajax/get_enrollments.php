<?php
require_once('../../global/config.php');
?>
<option value="">Select Enrollment ID</option>
<?php
$row = $db->Execute("SELECT PK_ENROLLMENT_MASTER, ENROLLMENT_ID FROM DOA_ENROLLMENT_MASTER WHERE PK_USER_MASTER = ".$_POST['PK_USER_MASTER']);
while (!$row->EOF) { ?>
    <option value="<?php echo $row->fields['PK_ENROLLMENT_MASTER'];?>"><?=$row->fields['ENROLLMENT_ID']?></option>
<?php $row->MoveNext(); } ?>