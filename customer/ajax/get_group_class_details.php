<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$res = $db_account->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.STANDING_ID, DOA_APPOINTMENT_MASTER.PK_LOCATION, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, DOA_APPOINTMENT_MASTER.COMMENT, DOA_APPOINTMENT_MASTER.INTERNAL_COMMENT, DOA_APPOINTMENT_MASTER.IMAGE, DOA_APPOINTMENT_MASTER.VIDEO, DOA_APPOINTMENT_MASTER.IMAGE_2, DOA_APPOINTMENT_MASTER.VIDEO_2, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE, DOA_APPOINTMENT_MASTER.ACTIVE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = '$_POST[PK_APPOINTMENT_MASTER]'");

if($res->RecordCount() == 0){
    header("location:all_schedule.php");
    exit;
}

$PK_APPOINTMENT_MASTER = $res->fields['PK_APPOINTMENT_MASTER'];
$STANDING_ID = $res->fields['STANDING_ID'];
$PK_SERVICE_MASTER = $res->fields['PK_SERVICE_MASTER'];
$SERVICE_NAME = $res->fields['SERVICE_NAME'];
$PK_SERVICE_CODE = $res->fields['PK_SERVICE_CODE'];
$PK_SCHEDULING_CODE = $res->fields['PK_SCHEDULING_CODE'];
$SERVICE_CODE = $res->fields['SERVICE_CODE'];
$PK_LOCATION = $res->fields['PK_LOCATION'];
$DATE = $res->fields['DATE'];
$START_TIME = $res->fields['START_TIME'];
$END_TIME = $res->fields['END_TIME'];
$PK_APPOINTMENT_STATUS = $res->fields['PK_APPOINTMENT_STATUS'];
$COMMENT = $res->fields['COMMENT'];
$INTERNAL_COMMENT = $res->fields['INTERNAL_COMMENT'];
$IMAGE = $res->fields['IMAGE'];
$VIDEO = $res->fields['VIDEO'];
$IMAGE_2 = $res->fields['IMAGE_2'];
$VIDEO_2 = $res->fields['VIDEO_2'];

$status_data = $db_account->Execute("SELECT DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_APPOINTMENT_STATUS_HISTORY.TIME_STAMP FROM DOA_APPOINTMENT_STATUS_HISTORY LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS=DOA_APPOINTMENT_STATUS_HISTORY.PK_APPOINTMENT_STATUS LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER=DOA_APPOINTMENT_STATUS_HISTORY.PK_USER WHERE PK_APPOINTMENT_MASTER = '$_POST[PK_APPOINTMENT_MASTER]'");
$CHANGED_BY = '';
while (!$status_data->EOF) {
    $CHANGED_BY .= "(".$status_data->fields['APPOINTMENT_STATUS']." by ".$status_data->fields['NAME']." at ".date('m-d-Y H:i:s A', strtotime($status_data->fields['TIME_STAMP'])).")<br>";
    $status_data->MoveNext();
}
?>

<!-- CSS for Popup -->
<style>
    .popup {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        justify-content: center;
        align-items: center;
    }

    .popup-content {
        background-color: white;
        padding: 20px;
        border-radius: 10px;
        max-width: 80%;
        text-align: center;
    }

    .close {
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 30px;
        color: white;
        cursor: pointer;
    }
</style>
<form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="FUNCTION_NAME" value="saveGroupClassData">
    <input type="hidden" name="PK_APPOINTMENT_MASTER" class="PK_APPOINTMENT_MASTER" value="<?=$PK_APPOINTMENT_MASTER?>">
    <div class="row">
        <div class="col-12">
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Service Name</label>
                        <p><?=$SERVICE_NAME?></p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Service Code</label>
                        <p><?=$SERVICE_CODE?></p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Scheduling Code  <!--<span id="change_scheduling_code" style="margin-left: 30px;"><a href="javascript:;" onclick="changeSchedulingCode()">Change</a></span>-->
                            <span id="cancel_change_scheduling_code" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeSchedulingCode()">Cancel</a></span></label>
                        <div id="scheduling_code_select" style="display: none;">
                            <select class="form-control" required name="PK_SCHEDULING_CODE" id="PK_SCHEDULING_CODE" onchange="calculateEndTime(this)">
                                <option value="">Select Scheduling Code</option>
                                <?php
                                $selected_scheduling_code = '';
                                $row = $db_account->Execute("SELECT DOA_SCHEDULING_CODE.`PK_SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_NAME`, DOA_SCHEDULING_CODE.`DURATION` FROM `DOA_SCHEDULING_CODE` LEFT JOIN DOA_SCHEDULING_SERVICE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE=DOA_SCHEDULING_SERVICE.PK_SCHEDULING_CODE WHERE DOA_SCHEDULING_CODE.`ACTIVE` = 1 AND DOA_SCHEDULING_SERVICE.PK_SERVICE_MASTER=".$PK_SERVICE_MASTER. " ORDER BY CASE WHEN DOA_SCHEDULING_CODE.SORT_ORDER IS NULL THEN 1 ELSE 0 END, DOA_SCHEDULING_CODE.SORT_ORDER");
                                while (!$row->EOF) { if($PK_SCHEDULING_CODE==$row->fields['PK_SCHEDULING_CODE']){$selected_scheduling_code = $row->fields['SCHEDULING_CODE'];} ?>
                                    <option value="<?php echo $row->fields['PK_SCHEDULING_CODE'];?>" data-duration="<?php echo $row->fields['DURATION'];?>" <?=($PK_SCHEDULING_CODE==$row->fields['PK_SCHEDULING_CODE'])?'selected':''?>><?=$row->fields['SCHEDULING_CODE']?></option>
                                    <?php $row->MoveNext(); } ?>
                            </select>
                        </div>
                        <p id="scheduling_code_name"><?=$selected_scheduling_code?></p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <select class="form-control" name="PK_LOCATION" id="PK_LOCATION" disabled>
                            <option value="">Select Location</option>
                            <?php
                            $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=($row->fields['PK_LOCATION']==$PK_LOCATION)?'selected':''?>><?=$row->fields['LOCATION_NAME']?></option>
                            <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label"><?=$service_provider_title?></label>
                    <div style="margin-bottom: 15px; margin-top: 10px; width: 480px;">
                        <select name="SERVICE_PROVIDER_ID[]" class="SERVICE_PROVIDER_ID multi_sumo_select" id="SERVICE_PROVIDER_ID" multiple disabled>
                            <?php
                            $selected_service_provider = [];
                            $selected_service_provider_row = $db_account->Execute("SELECT DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER FROM DOA_APPOINTMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USER_MASTER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = $master_database.DOA_USER_MASTER.PK_USER WHERE DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = '$PK_APPOINTMENT_MASTER'");
                            while (!$selected_service_provider_row->EOF) {
                                $selected_service_provider[] = $selected_service_provider_row->fields['PK_USER'];
                                $selected_service_provider_row->MoveNext();
                            }
                            if (count($selected_service_provider) > 0) {
                                $orderBy = " ORDER BY FIELD(DOA_USERS.PK_USER, ".implode(',', $selected_service_provider).") DESC";
                            } else {
                                $orderBy = "";
                            }
                            $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER'].$orderBy);
                            while (!$row->EOF) {?>
                                <option value="<?php echo $row->fields['PK_USER'];?>" <?=in_array($row->fields['PK_USER'], $selected_service_provider)?"selected":""?>><?=$row->fields['NAME']?></option>
                            <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="text" id="DATE" name="DATE" class="form-control" required value="<?php echo ($DATE)?date('m/d/Y', strtotime($DATE)):''?>" readonly>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker" onchange="calculateEndTime(this)" required value="<?php echo ($START_TIME)?date('h:i A', strtotime($START_TIME)):''?>" readonly>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="text" id="END_TIME" name="END_TIME" class="form-control time-picker" required value="<?php echo ($END_TIME)?date('h:i A', strtotime($END_TIME)):''?>" readonly>
                    </div>
                </div>
            </div>
            <div class="row">
                <!--<div class="col-8">
                    <label class="form-label">Customer</label>
                    <div style="margin-bottom: 15px; margin-top: 10px; width: 480px;">
                        <select class="multi_sumo_select" name="PK_USER_MASTER[]" id="PK_USER_MASTER" multiple disabled>
                            <?php
/*                            $with_enr_customer = [];
                            $serviceCodeData = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_ENROLLMENT_MASTER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = $PK_SERVICE_MASTER AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = '$PK_SERVICE_CODE'");
                            while (!$serviceCodeData->EOF) {
                                $SESSION_CREATED = getSessionCreatedCount($serviceCodeData->fields['PK_ENROLLMENT_SERVICE'], 'GROUP');
                                if ($serviceCodeData->fields['NUMBER_OF_SESSION'] > $SESSION_CREATED) {
                                    $with_enr_customer[] = $serviceCodeData->fields['PK_USER_MASTER'];
                                }
                                $serviceCodeData->MoveNext();
                            }
                            $user_master_id = implode(',', $with_enr_customer);

                            $selected_customer = [];
                            $selected_customer_row = $db_account->Execute("SELECT DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN $master_database.DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = $master_database.DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = '$PK_APPOINTMENT_MASTER'");
                            while (!$selected_customer_row->EOF) {
                                $selected_customer[] = $selected_customer_row->fields['PK_USER_MASTER'];
                                $selected_customer_row->MoveNext();
                            }
                            if (count($selected_customer) > 0) {
                                $orderBy = " ORDER BY FIELD(PK_USER_MASTER, ".implode(',', $selected_customer).") DESC";
                            } else {
                                $orderBy = "";
                            }

                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_MASTER.PK_USER_MASTER IN (".$user_master_id.") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'".$orderBy);
                            $customer_name = '';
                            while (!$row->EOF) {
                                if (in_array($row->fields['PK_USER_MASTER'], $selected_customer)) {
                                    $selected_customer_id = $row->fields['PK_USER_MASTER'];
                                    $selected_user_id = $row->fields['PK_USER'];
                                    $customer_name.= '<p><i class="fa fa-check-square" style="font-size:15px; color: #069419"></i>&nbsp;&nbsp;<a href="customer.php?id='.$selected_user_id.'&master_id='.$selected_customer_id.'&tab=profile" target="_blank">'.$row->fields['NAME'].'<br></a></p>';
                                }*/?>
                                <option value="<?php /*echo $row->fields['PK_USER_MASTER'];*/?>" <?php /*=in_array($row->fields['PK_USER_MASTER'], $selected_customer)?"selected":""*/?>><?php /*=$row->fields['NAME']*/?></option>
                            <?php /*$row->MoveNext(); } */?>
                        </select>
                    </div>
                    <p><?php /*=$customer_name;*/?></p>
                </div>-->
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="PK_APPOINTMENT_STATUS" id="PK_APPOINTMENT_STATUS" disabled>
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

                <div class="row" id="add_info_div">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Comment</label>
                            <textarea class="form-control" name="COMMENT" rows="4" readonly><?=$COMMENT?></textarea><!--<span><?php /*=$CHANGED_BY*/?></span>-->
                        </div>
                    </div>
                    <!--<div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Internal Comment</label>
                            <textarea class="form-control" name="INTERNAL_COMMENT" rows="4"><?php /*=$INTERNAL_COMMENT*/?></textarea>
                        </div>
                    </div>-->
                    <div class="row">
                        <?php if ($IMAGE != '') {?>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Uploaded Image 1</label>
                                    <!--<input type="file" class="form-control" name="IMAGE" id="IMAGE">-->
                                    <div>
                                        <img src="<?=$IMAGE?>" onclick="showPopup('image', '<?=$IMAGE?>')" style="cursor: pointer; margin-top: 10px; max-width: 150px; height: auto;">
                                    </div>
                                </div>
                            </div>
                        <?php }?>

                        <!-- Video 1 -->
                        <?php if ($VIDEO != '') {?>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Uploaded Video 1</label>
                                    <!--<input type="file" class="form-control" name="VIDEO" id="VIDEO" accept="video/*">-->
                                    <?php if($VIDEO != '') { ?>
                                        <div style="display: flex; align-items: center; gap: 4px; margin-top: 10px">
                                            <video width="240" height="135" controls onclick="showPopup('video', '<?=$VIDEO?>')" style="cursor: pointer;">
                                                <source src="<?=$VIDEO?>" type="video/mp4">
                                            </video>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php }?>

                        <!-- Image 2 -->
                        <?php if ($IMAGE_2 != '') {?>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Uploaded Image 2</label>
                                    <!--<input type="file" class="form-control" name="IMAGE_2" id="IMAGE_2">-->
                                    <div>
                                        <img src="<?=$IMAGE_2?>" onclick="showPopup('image', '<?=$IMAGE_2?>')" style="cursor: pointer; margin-top: 10px; max-width: 150px; height: auto;">
                                    </div>
                                </div>
                            </div>
                        <?php }?>

                        <!-- Video 2 -->
                        <?php if ($VIDEO_2 != '') {?>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Uploaded Video 2</label>
                                    <!--<input type="file" class="form-control" name="VIDEO" id="VIDEO" accept="video/*">-->
                                    <?php if($VIDEO_2 != '') { ?>
                                        <div style="display: flex; align-items: center; gap: 4px; margin-top: 10px">
                                            <video width="240" height="135" controls onclick="showPopup('video', '<?=$VIDEO_2?>')" style="cursor: pointer;">
                                                <source src="<?=$VIDEO_2?>" type="video/mp4">
                                            </video>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php }?>
                    </div>
                    <!-- Popup Modal -->
                    <div id="mediaPopup" class="popup" onclick="closePopup()">
                        <span class="close" onclick="closePopup()">&times;</span>
                        <div class="popup-content" onclick="event.stopPropagation();">
                            <img id="popupImage" src="" style="display:none; max-width: 100%;">
                            <video id="popupVideo" controls style="display:none; max-width: 100%;">
                                <source id="popupVideoSource" src="" type="video/mp4">
                            </video>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--<div class="form-group">
        <label><input type="checkbox" name="STANDING_ID" value="<?php /*=$STANDING_ID*/?>"> All Session Details Will Be Changed</label>
    </div>-->
    <!--<button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>-->
    <a onclick="closeEditAppointment()" class="btn btn-inverse waves-effect waves-light">Cancel</a>
</form>

<script>
    $('.SERVICE_PROVIDER_ID').SumoSelect({placeholder: 'Select <?=$service_provider_title?>', selectAll: true, search: true, searchText: 'Search...'});

    function changeSchedulingCode(){
        $('#change_scheduling_code').hide();
        $('#cancel_change_scheduling_code').show();
        $('#scheduling_code_select').slideDown();
        $('#scheduling_code_name').slideUp();
        $('#date_time_div').slideUp();
        $('#schedule_div').slideDown();
    }

    function cancelChangeSchedulingCode() {
        $('#change_scheduling_code').show();
        $('#cancel_change_scheduling_code').hide();
        $('#scheduling_code_select').slideUp();
        $('#scheduling_code_name').slideDown();
        $('#date_time_div').slideDown();
        $('#schedule_div').slideUp();
    }

    function calculateEndTime() {
        let start_time = $('#START_TIME').val();
        let duration = $('#PK_SCHEDULING_CODE').find(':selected').data('duration');
        duration = (duration)?duration:30;

        if (start_time && duration) {
            start_time = moment(start_time, ["h:mm A"]).format("HH:mm");
            let end_time = addMinutes(start_time, duration);
            end_time = moment(end_time, ["HH:mm"]).format("h:mm A");
            $('#END_TIME').val(end_time);
        }
    }

    function addMinutes(time, minsToAdd) {
        function D(J){ return (J<10? '0':'') + J;};
        var piece = time.split(':');
        var mins = piece[0]*60 + +piece[1] + +minsToAdd;

        return D(mins%(24*60)/60 | 0) + ':' + D(mins%60);
    }
</script>
