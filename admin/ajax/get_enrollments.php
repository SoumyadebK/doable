<?php
require_once('../../global/config.php');
?>
<option value="">Select Enrollment ID</option>
<?php
$row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_CODE.DURATION, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_ENROLLMENT_MASTER RIGHT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP != 1 AND DOA_ENROLLMENT_MASTER.STATUS = 'A' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = ".$_POST['PK_USER_MASTER']);
while (!$row->EOF) {
    $PK_ENROLLMENT_MASTER = $row->fields['PK_ENROLLMENT_MASTER'];
    $name = $row->fields['ENROLLMENT_NAME'];
    if(empty($name)){
        $enrollment_name = ' ';
    }else {
        $enrollment_name = "$name"." - ";
    }
    $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_SERVICE`) AS USED_SESSION_COUNT FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_SERVICE` = ".$row->fields['PK_ENROLLMENT_SERVICE']);

    $used_paid_count = $db_account->Execute("SELECT COUNT(PK_ENROLLMENT_MASTER) AS PAID_COUNT FROM DOA_ENROLLMENT_LEDGER WHERE IS_PAID=1 AND TRANSACTION_TYPE='Payment' AND PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
    if($used_paid_count->RecordCount()>0) {
        $paid_count = $used_paid_count->fields['PAID_COUNT'];
    }else{
        $paid_count = '';
    }
    if (($row->fields['NUMBER_OF_SESSION']-$used_session_count->fields['USED_SESSION_COUNT']) > 0) {?>
    <option value="<?php echo $row->fields['PK_ENROLLMENT_MASTER'].','.$row->fields['PK_ENROLLMENT_SERVICE'].','.$row->fields['PK_SERVICE_MASTER'].','.$row->fields['PK_SERVICE_CODE'];?>" data-duration="<?=$row->fields['DURATION']?>" data-no_of_session="<?=$row->fields['NUMBER_OF_SESSION']?>" <?=(($row->fields['NUMBER_OF_SESSION']-$used_session_count->fields['USED_SESSION_COUNT']) <= 0)?'disabled':''?>><?=$enrollment_name.$row->fields['ENROLLMENT_ID'].' || '.$row->fields['SERVICE_NAME'].' || '.$row->fields['SERVICE_CODE'].' || '.$used_session_count->fields['USED_SESSION_COUNT'].'/'.$row->fields['NUMBER_OF_SESSION'].' || Paid:'.$paid_count;?></option>
<?php } $row->MoveNext(); } ?>
