<?php
require_once('../../global/config.php');

$res = $db->Execute("SELECT DOA_GROUP_CLASS.PK_GROUP_CLASS, DOA_GROUP_CLASS.SERVICE_PROVIDER_ID_1, DOA_GROUP_CLASS.SERVICE_PROVIDER_ID_2, DOA_GROUP_CLASS.DATE, DOA_GROUP_CLASS.START_TIME, DOA_GROUP_CLASS.END_TIME, DOA_GROUP_CLASS.PK_APPOINTMENT_STATUS, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_GROUP_CLASS.ACTIVE FROM DOA_GROUP_CLASS LEFT JOIN DOA_SERVICE_MASTER ON DOA_GROUP_CLASS.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_GROUP_CLASS.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_GROUP_CLASS.PK_GROUP_CLASS = '$_POST[PK_GROUP_CLASS]'");

if($res->RecordCount() == 0){
    header("location:all_schedule.php");
    exit;
}

$PK_GROUP_CLASS = $res->fields['PK_GROUP_CLASS'];
$PK_SERVICE_MASTER = $res->fields['PK_SERVICE_MASTER'];
$SERVICE_NAME = $res->fields['SERVICE_NAME'];
$SERVICE_CODE = $res->fields['SERVICE_CODE'];
$SERVICE_PROVIDER_ID_1 = $res->fields['SERVICE_PROVIDER_ID_1'];
$SERVICE_PROVIDER_ID_2 = $res->fields['SERVICE_PROVIDER_ID_2'];
$DATE = $res->fields['DATE'];
$START_TIME = $res->fields['START_TIME'];
$END_TIME = $res->fields['END_TIME'];
$PK_APPOINTMENT_STATUS = $res->fields['PK_APPOINTMENT_STATUS'];
?>

<form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="FUNCTION_NAME" value="saveGroupClassData">
    <input type="hidden" name="PK_GROUP_CLASS" class="PK_GROUP_CLASS" value="<?=$PK_GROUP_CLASS?>">
    <div class="row">
        <div class="col-12">
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Service Name</label>
                        <p><?=$SERVICE_NAME?></p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Service Code</label>
                        <p><?=$SERVICE_CODE?></p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Primary <?=$service_provider_title?></label>
                        <select required name="SERVICE_PROVIDER_ID_1" class="form-control SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID_1">
                        <option value="">Select <?=$service_provider_title?></option>
                        <?php
                        $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS JOIN DOA_SERVICE_PROVIDER_SERVICES ON DOA_USERS.PK_USER = DOA_SERVICE_PROVIDER_SERVICES.PK_USER WHERE DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE ".$PK_SERVICE_MASTER." OR DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '%,".$PK_SERVICE_MASTER.",%' OR DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '".$PK_SERVICE_MASTER.",%' OR DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '%,".$PK_SERVICE_MASTER."'");
                        while (!$row->EOF) { ?>
                            <option value="<?=$row->fields['PK_USER'];?>" <?=($SERVICE_PROVIDER_ID_1==$row->fields['PK_USER'])?'selected':''?>><?=$row->fields['NAME']?></option>
                        <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Secondary <?=$service_provider_title?></label>
                        <select required name="SERVICE_PROVIDER_ID_2" class="form-control SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID_2">
                        <option value="">Select <?=$service_provider_title?></option>
                        <?php
                        $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS JOIN DOA_SERVICE_PROVIDER_SERVICES ON DOA_USERS.PK_USER = DOA_SERVICE_PROVIDER_SERVICES.PK_USER WHERE DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE ".$PK_SERVICE_MASTER." OR DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '%,".$PK_SERVICE_MASTER.",%' OR DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '".$PK_SERVICE_MASTER.",%' OR DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '%,".$PK_SERVICE_MASTER."'");
                        while (!$row->EOF) { ?>
                            <option value="<?=$row->fields['PK_USER'];?>" <?=($SERVICE_PROVIDER_ID_2==$row->fields['PK_USER'])?'selected':''?>><?=$row->fields['NAME']?></option>
                        <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="text" id="DATE" name="DATE" class="form-control datepicker-normal" required value="<?php echo ($DATE)?date('m/d/Y', strtotime($DATE)):''?>">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker" required value="<?php echo ($START_TIME)?date('h:i A', strtotime($START_TIME)):''?>">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="text" id="END_TIME" name="END_TIME" class="form-control time-picker" required value="<?php echo ($END_TIME)?date('h:i A', strtotime($END_TIME)):''?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <label class="form-label">Customer</label>
                    <div style="margin-bottom: 15px; margin-top: 10px; width: 700px;">
                        <select class="multi_sumo_select" name="PK_USER_MASTER[]" multiple>
                            <?php
                            $selected_customer = [];
                            $selected_customer_row = $db->Execute("SELECT DOA_GROUP_CLASS_CUSTOMER.PK_USER_MASTER FROM DOA_GROUP_CLASS_CUSTOMER LEFT JOIN DOA_USER_MASTER ON DOA_GROUP_CLASS_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_GROUP_CLASS_CUSTOMER.PK_GROUP_CLASS = '$PK_GROUP_CLASS'");
                            while (!$selected_customer_row->EOF) {
                                $selected_customer[] = $selected_customer_row->fields['PK_USER_MASTER'];
                                $selected_customer_row->MoveNext();
                            }
                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE PK_ROLES = 4 AND ACTIVE = 1 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_USER_MASTER'];?>" <?=in_array($row->fields['PK_USER_MASTER'], $selected_customer)?"selected":""?>><?=$row->fields['NAME']?></option>
                            <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Status:</label>
                        <select class="form-control" name="PK_APPOINTMENT_STATUS" id="PK_APPOINTMENT_STATUS">
                            <option value="">Select Status</option>
                            <?php
                            $selected_status = '';
                            $row = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_STATUS` WHERE `ACTIVE` = 1");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS'];?>" <?=($PK_APPOINTMENT_STATUS==$row->fields['PK_APPOINTMENT_STATUS'])?'selected':''?>><?=$row->fields['APPOINTMENT_STATUS']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                        <p id="appointment_status"><?=$selected_status?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
    <a onclick="closeEditAppointment()" class="btn btn-inverse waves-effect waves-light">Cancel</a>
</form>
