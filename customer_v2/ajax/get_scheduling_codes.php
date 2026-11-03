<?php
require_once('../../global/config.php');

if (isset($_POST['PK_ENROLLMENT_MASTER'])) {
    $PK_ENROLLMENT_MASTER_ARRAY = explode(',', $_POST['PK_ENROLLMENT_MASTER']);
    $PK_SERVICE_MASTER = $PK_ENROLLMENT_MASTER_ARRAY[2];
} elseif (isset($_POST['PK_SERVICE_MASTER'])){
    $PK_SERVICE_MASTER_ARRAY = explode(',', $_POST['PK_SERVICE_MASTER']);
    $PK_SERVICE_MASTER = $PK_SERVICE_MASTER_ARRAY[0];
}

$no_of_session = $_POST['no_of_session'] ?? 2;
$used_session = $_POST['used_session'] ?? 1;
$session_left = (float)($no_of_session - $used_session);
?>
<option value="">Select Scheduling Code</option>
<?php
$row = $db_account->Execute("SELECT DOA_SCHEDULING_CODE.`PK_SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_NAME`, DOA_SCHEDULING_CODE.`DURATION` FROM `DOA_SCHEDULING_CODE` LEFT JOIN DOA_SCHEDULING_SERVICE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE=DOA_SCHEDULING_SERVICE.PK_SCHEDULING_CODE WHERE DOA_SCHEDULING_CODE.`ACTIVE` = 1 AND DOA_SCHEDULING_CODE.UNIT <= $session_left AND DOA_SCHEDULING_SERVICE.PK_SERVICE_MASTER=".$PK_SERVICE_MASTER. " ORDER BY CASE WHEN DOA_SCHEDULING_CODE.SORT_ORDER IS NULL THEN 1 ELSE 0 END, DOA_SCHEDULING_CODE.SORT_ORDER");
while (!$row->EOF) { ?>
    <option data-duration="<?=$row->fields['DURATION'];?>" value="<?=$row->fields['PK_SCHEDULING_CODE'].','.$row->fields['DURATION']?>"><?=$row->fields['SCHEDULING_NAME'].' ('.$row->fields['SCHEDULING_CODE'].')'?></option>
<?php $row->MoveNext(); } ?>
