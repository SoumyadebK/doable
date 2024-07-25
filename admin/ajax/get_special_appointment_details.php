<?php
require_once('../../global/config.php');

$res = $db_account->Execute("SELECT DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT, DOA_SPECIAL_APPOINTMENT.TITLE, DOA_SPECIAL_APPOINTMENT.DATE, DOA_SPECIAL_APPOINTMENT.START_TIME, DOA_SPECIAL_APPOINTMENT.END_TIME, DOA_SPECIAL_APPOINTMENT.DESCRIPTION, DOA_SPECIAL_APPOINTMENT.PK_SCHEDULING_CODE, DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS, DOA_SPECIAL_APPOINTMENT.ACTIVE, DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_USER_MASTER  FROM `DOA_SPECIAL_APPOINTMENT` LEFT JOIN DOA_SPECIAL_APPOINTMENT_CUSTOMER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT=DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_SPECIAL_APPOINTMENT WHERE DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = '$_POST[PK_APPOINTMENT_MASTER]'");

if($res->RecordCount() == 0){
    header("location:all_special_appointment.php");
    exit;
}

$PK_SPECIAL_APPOINTMENT = $res->fields['PK_SPECIAL_APPOINTMENT'];
$TITLE = $res->fields['TITLE'];
$DATE = $res->fields['DATE'];
$START_TIME = $res->fields['START_TIME'];
$END_TIME = $res->fields['END_TIME'];
$DESCRIPTION = $res->fields['DESCRIPTION'];
$PK_USER_MASTER = $res->fields['PK_USER_MASTER'];
$PK_SCHEDULING_CODE = $res->fields['PK_SCHEDULING_CODE'];
$PK_APPOINTMENT_STATUS = $res->fields['PK_APPOINTMENT_STATUS'];
$ACTIVE = $res->fields['ACTIVE'];

?>

<form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="FUNCTION_NAME" value="saveSpecialAppointmentData">
    <input type="hidden" name="PK_SPECIAL_APPOINTMENT" class="PK_SPECIAL_APPOINTMENT" value="<?=$PK_SPECIAL_APPOINTMENT?>">
    <div class="row">
        <div class="col-12">
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" id="TITLE" name="TITLE" class="form-control" required value="<?php echo $TITLE?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="text" id="DATE" name="DATE" class="form-control datepicker-normal" required value="<?php echo ($DATE)?date('m/d/Y', strtotime($DATE)):''?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker" required value="<?php echo ($START_TIME)?date('h:i A', strtotime($START_TIME)):''?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="text" id="END_TIME" name="END_TIME" class="form-control time-picker" required value="<?php echo ($END_TIME)?date('h:i A', strtotime($END_TIME)):''?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="DESCRIPTION" name="DESCRIPTION" rows="3"><?=$DESCRIPTION?></textarea>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label">Customer</label>
                    <div style="margin-bottom: 15px; margin-top: 10px; width: 480px;">
                        <select class="multi_sumo_select" name="PK_USER_MASTER[]" multiple>
                            <?php
                            $selected_customer = [];
                            $selected_customer_row = $db_account->Execute("SELECT DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_USER_MASTER FROM DOA_SPECIAL_APPOINTMENT_CUSTOMER LEFT JOIN $master_database.DOA_USER_MASTER ON DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_USER_MASTER = $master_database.DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_SPECIAL_APPOINTMENT = '$PK_SPECIAL_APPOINTMENT'");
                            while (!$selected_customer_row->EOF) {
                                $selected_customer[] = $selected_customer_row->fields['PK_USER_MASTER'];
                                $selected_customer_row->MoveNext();
                            }

                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_USER_MASTER'];?>" <?=in_array($row->fields['PK_USER_MASTER'], $selected_customer)?"selected":""?>><?=$row->fields['NAME']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <label class="form-label"><?=$service_provider_title?></label>
                    <div class="col-md-12" style="margin-bottom: 15px; margin-top: 10px;">
                        <select class="multi_sumo_select" name="PK_USER[]" multiple>
                            <?php
                            $selected_service_provider = [];
                            $selected_service_provider_row = $db_account->Execute("SELECT `PK_USER` FROM `DOA_SPECIAL_APPOINTMENT_USER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
                            while (!$selected_service_provider_row->EOF) {
                                $selected_service_provider[] = $selected_service_provider_row->fields['PK_USER'];
                                $selected_service_provider_row->MoveNext();
                            }

                            $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES IN(5) AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_USER'];?>" <?=in_array($row->fields['PK_USER'], $selected_service_provider)?"selected":""?>><?=$row->fields['NAME']?></option>
                            <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label">Scheduling Code</label>
                    <div class="col-md-12" style="margin-bottom: 15px; margin-top: 10px;">
                        <select class="PK_SCHEDULING_CODE" name="PK_SCHEDULING_CODE" id="PK_SCHEDULING_CODE" onchange="calculateEndTime(this)">
                            <option disabled selected>Select Scheduling Code</option>
                            <?php
                            $selected_booking_code = [];
                            if(!empty($_GET['id'])) {
                                $selected_booking_code_row = $db_account->Execute("SELECT `PK_SCHEDULING_CODE` FROM `DOA_SCHEDULING_CODE` WHERE `PK_SCHEDULING_CODE` = ".$row->fields['PK_SCHEDULING_CODE']);
                                while (!$selected_booking_code_row->EOF) {
                                    $selected_booking_code[] = $selected_booking_code_row->fields['PK_SCHEDULING_CODE'];
                                    $selected_booking_code_row->MoveNext();
                                }
                            }
                            $booking_row = $db_account->Execute("SELECT DOA_SCHEDULING_CODE.`PK_SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_NAME`, DOA_SCHEDULING_CODE.`DURATION` FROM `DOA_SCHEDULING_CODE` LEFT JOIN DOA_SCHEDULING_SERVICE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE=DOA_SCHEDULING_SERVICE.PK_SCHEDULING_CODE WHERE DOA_SCHEDULING_CODE.`ACTIVE` = 1 AND DOA_SCHEDULING_SERVICE.PK_SERVICE_MASTER=0");
                            while (!$booking_row->EOF) { ?>
                                <option value="<?php echo $booking_row->fields['PK_SCHEDULING_CODE'];?>" data-duration="<?php echo $booking_row->fields['DURATION'];?>" data-scheduling_name="<?php echo $booking_row->fields['SCHEDULING_NAME']?>" data-is_default="<?php echo $booking_row->fields['IS_DEFAULT']?>" <?=($PK_SCHEDULING_CODE == $booking_row->fields['PK_SCHEDULING_CODE']) ? "selected" : ""?>><?=$booking_row->fields['SCHEDULING_NAME'].' ('.$booking_row->fields['SCHEDULING_CODE'].')'?></option>
                            <?php $booking_row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
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

<script>
    $('.multi_sumo_select').SumoSelect({placeholder: 'Select Customer', selectAll: true, search: true, searchText: 'Search...'});
</script>
