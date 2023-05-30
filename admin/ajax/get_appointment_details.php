<?php
require_once('../../global/config.php');

$res = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_APPOINTMENT_MASTER` = '$_POST[PK_APPOINTMENT_MASTER]'");

if($res->RecordCount() == 0){
    header("location:all_services.php");
    exit;
}

$PK_APPOINTMENT_MASTER = $_POST['PK_APPOINTMENT_MASTER'];
$CUSTOMER_ID = $res->fields['CUSTOMER_ID'];
$PK_ENROLLMENT_MASTER = $res->fields['PK_ENROLLMENT_MASTER'];
$SERIAL_NUMBER = $res->fields['SERIAL_NUMBER'];
$PK_SERVICE_MASTER = $res->fields['PK_SERVICE_MASTER'];
$PK_SERVICE_CODE = $res->fields['PK_SERVICE_CODE'];
$SERVICE_PROVIDER_ID = $res->fields['SERVICE_PROVIDER_ID'];
$PK_APPOINTMENT_STATUS = $res->fields['PK_APPOINTMENT_STATUS'];
$NO_SHOW = $res->fields['NO_SHOW'];
$ACTIVE = $res->fields['ACTIVE'];
$DATE = date("m/d/Y",strtotime($res->fields['DATE']));
$DATE_ARR[0] = date("Y",strtotime($res->fields['DATE']));
$DATE_ARR[1] = date("m",strtotime($res->fields['DATE'])) -1;
$DATE_ARR[2] = date("d",strtotime($res->fields['DATE']));
$START_TIME = $res->fields['START_TIME'];
$END_TIME = $res->fields['END_TIME'];
$COMMENT = $res->fields['COMMENT'];
$IMAGE = $res->fields['IMAGE'];
?>

<form id="appointment_form" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="FUNCTION_NAME" value="saveAppointmentData">
    <input type="hidden" name="PK_APPOINTMENT_MASTER" class="PK_APPOINTMENT_MASTER" value="<?=$PK_APPOINTMENT_MASTER?>">
    <div style="padding-top: 10px;">
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Name: </label>
                        <div id="customer_select" style="display: none;">
                            <select name="CUSTOMER_ID" id="CUSTOMER_ID" onchange="selectThisCustomer(this);">
                                <option value="">Select Customer</option>
                                <?php
                                $selected_customer = '';
                                $selected_customer_id = '';
                                $selected_user_id = '';
                                $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_ID, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.PK_LOCATION, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND DOA_USERS.ACTIVE = 1 ORDER BY FIRST_NAME");
                                while (!$row->EOF) { if (($CUSTOMER_ID==$row->fields['PK_USER_MASTER'])){$selected_customer = $row->fields['NAME']; $customer_phone = $row->fields['PHONE']; $customer_email = $row->fields['EMAIL_ID']; $selected_customer_id = $row->fields['PK_USER_MASTER']; $selected_user_id = $row->fields['PK_USER'];} ?>
                                    <option value="<?php echo $row->fields['PK_USER_MASTER'];?>" <?=($CUSTOMER_ID==$row->fields['PK_USER_MASTER'])?'selected':''?>><?=$row->fields['NAME'].' ('.$row->fields['PHONE'].')'?></option>
                                <?php $row->MoveNext(); } ?>
                            </select>
                        </div>
                        <p><a href="customer.php?id=<?=$selected_customer_id?>&master_id=<?=$selected_customer_id?>&tab=profile" target="_blank"><?=$selected_customer?></a></p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Phone: </label>
                        <p><?=$customer_phone?></p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Email: </label>
                        <p><?=$customer_email?></p>
                    </div>
                </div>

                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Enrollment ID : </label>
                        <select class="form-control" required name="PK_SERVICE_MASTER" id="PK_SERVICE_MASTER" style="display: none;" onchange="selectThisEnrollment(this);" disabled>
                            <option value="">Select Enrollment ID</option>
                            <?php
                            $selected_enrollment = '';
                            $row = $db->Execute("SELECT PK_ENROLLMENT_MASTER, ENROLLMENT_ID FROM DOA_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);
                            while (!$row->EOF) { if($PK_ENROLLMENT_MASTER==$row->fields['PK_ENROLLMENT_MASTER']){$selected_enrollment = $row->fields['ENROLLMENT_ID'];} ?>
                                <option value="<?php echo $row->fields['PK_ENROLLMENT_MASTER'];?>" <?=($PK_ENROLLMENT_MASTER==$row->fields['PK_ENROLLMENT_MASTER'])?'selected':''?>><?=$row->fields['ENROLLMENT_ID']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                        <p><?=$selected_enrollment?></p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Apt #: </label>
                        <p><?=$SERIAL_NUMBER?></p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Service : </label>
                        <select class="form-control" required name="PK_SERVICE_MASTER" id="PK_SERVICE_MASTER" style="display: none;" onchange="selectThisService(this);" disabled>
                            <option value="">Select Service</option>
                            <?php
                            $selected_service = '';
                            $row = $db->Execute("SELECT DISTINCT(DOA_SERVICE_MASTER.PK_SERVICE_MASTER), DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                            while (!$row->EOF) { if($PK_SERVICE_MASTER==$row->fields['PK_SERVICE_MASTER']){$selected_service = $row->fields['SERVICE_NAME'];} ?>
                                <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" <?=($PK_SERVICE_MASTER==$row->fields['PK_SERVICE_MASTER'])?'selected':''?>><?=$row->fields['SERVICE_NAME']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                        <p><?=$selected_service?></p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Service Code : </label>
                        <select class="form-control" required name="PK_SERVICE_CODE" id="PK_SERVICE_CODE" style="display: none;" onchange="getSlots()" disabled>
                            <option value="">Select Service Code</option>
                            <?php
                            $selected_service_code = '';
                            $row = $db->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE PK_SERVICE_MASTER = ".$PK_SERVICE_MASTER);
                            while (!$row->EOF) { if($PK_SERVICE_CODE==$row->fields['PK_SERVICE_CODE']){$selected_service_code = $row->fields['SERVICE_CODE'];} ?>
                                <option value="<?php echo $row->fields['PK_SERVICE_CODE'];?>" data-duration="<?=$row->fields['DURATION']?>" <?=($PK_SERVICE_CODE==$row->fields['PK_SERVICE_CODE'])?'selected':''?>><?=$row->fields['SERVICE_CODE']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                        <p><?=$selected_service_code?></p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label"><?=$service_provider_title?> : <span id="change_service_provider" style="margin-left: 30px;"><a href="javascript:;" onclick="changeServiceProvider()">Change</a></span>
                            <span id="cancel_change_service_provider" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeServiceProvider()">Cancel</a></span></label>
                        <div id="service_provider_select" style="display: none;">
                            <select required name="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID" onchange="getSlots()">
                                <option value="">Select <?=$service_provider_title?></option>
                                <?php
                                $selected_service_provider = '';
                                $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5");
                                while (!$row->EOF) { if($SERVICE_PROVIDER_ID==$row->fields['PK_USER']){$selected_service_provider = $row->fields['NAME'];} ?>
                                    <option value="<?php echo $row->fields['PK_USER'];?>" <?=($SERVICE_PROVIDER_ID==$row->fields['PK_USER'])?'selected':''?>><?=$row->fields['NAME']?></option>
                                <?php $row->MoveNext(); } ?>
                            </select>
                        </div>
                        <p id="service_provider_name"><?=$selected_service_provider?></p>
                    </div>
                </div>
            </div>
            <span id="cancel_reschedule" style="display: none;"><a href="javascript:;" onclick="cancelReschedule()">Cancel</a></span>
            <div class="row" id="date_time_div">
                <div class="col-3">
                    <div class="form-group">
                        <label class="form-label">Date : </label>
                        <p><?=$DATE?></p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Time : <span id="reschedule" style="margin-left: 80px;"><a href="javascript:;" onclick="reschedule()">Reschedule</a></span></label>
                        <p><?=date('h:i A', strtotime($START_TIME)).' - '.date('h:i A', strtotime($END_TIME))?></p>
                    </div>
                </div>
                <div class="col-3">
                    <div class="form-group">
                        <span><a href="javascript:;" onclick="cancelAppointment()">Cancel</a></span>
                    </div>
                </div>
            </div>

        <!--<input type="hidden" name="DATE" id="DATE">
        <input type="hidden" name="START_TIME" id="START_TIME">
        <input type="hidden" name="END_TIME" id="END_TIME">

        <div class="row" id="schedule_div" style="display: none;">
            <div class="col-7">
                <div id="showcase-wrapper">
                    <div id="myCalendarWrapper">
                    </div>
                </div>
            </div>

            <div class="col-5" style="background-color: #add1b1;max-height:470px;overflow-y:scroll;" >
                <br />
                <div class="row" id="slot_div" >

                </div>
            </div>
        </div>-->


        <div class="row m-t-25">
            <div class="col-8">
                <div class="form-group">
                    <label class="form-label">Status : <span id="change_status" style="margin-left: 30px;"><a href="javascript:;" onclick="changeStatus()">Change</a></span>
                        <span id="cancel_change_status" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeStatus()">Cancel</a></span></label><br>
                    <select class="form-control" name="PK_APPOINTMENT_STATUS" id="PK_APPOINTMENT_STATUS" style="display: none;" onchange="changeAppointmentStatus(this)">
                        <option value="">Select Status</option>
                        <?php
                        $selected_status = '';
                        $row = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_STATUS` WHERE `ACTIVE` = 1");
                        while (!$row->EOF) { if($PK_APPOINTMENT_STATUS==$row->fields['PK_APPOINTMENT_STATUS']){$selected_status=$row->fields['APPOINTMENT_STATUS'];}?>
                            <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS'];?>" <?=($PK_APPOINTMENT_STATUS==$row->fields['PK_APPOINTMENT_STATUS'])?'selected':''?>><?=$row->fields['APPOINTMENT_STATUS']?></option>
                            <?php $row->MoveNext(); } ?>
                    </select>
                    <p id="appointment_status"><?=$selected_status?></p>
                </div>
            </div>
            <!-- <div class="col-3" style="width: 18%;">
                <div class="form-group m-t-30">
                    <a class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="cancelAppointment()">Cancel Appointments</a>
                </div>
            </div> -->
            <div class="col-4">
                <div class="form-group m-t-30">
                    <a class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="$('#add_info_div').slideToggle();">Add Info</a>
                </div>
            </div>
        </div>

        <div class="row" id="no_show_div" style="display: <?=($PK_APPOINTMENT_STATUS==4)?'':'none'?>;">
            <div class="col-8">
                <div class="form-group">
                    <label class="form-label">No Show</label>
                    <select class="form-control" name="NO_SHOW">
                        <option value="">Select</option>
                        <option value="Charge" <?=($NO_SHOW=='Charge')?'selected':''?>>Charge</option>
                        <option value="No Charge" <?=($NO_SHOW=='No Charge')?'selected':''?>>No Charge</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row" id="add_info_div" style="display: <?=($COMMENT)?'':'none'?>;">
            <div class="col-8">
                <div class="form-group">
                    <label class="form-label">Comment</label>
                    <textarea class="form-control" name="COMMENT" rows="6"><?=$COMMENT?></textarea>
                </div>
            </div>
            <div class="col-4">
                <div class="form-group">
                    <label class="form-label">Upload Image</label>
                    <input type="file" class="form-control" name="IMAGE" id="IMAGE">
                    <a href="<?=$IMAGE?>" target="_blank">
                        <img src="<?=$IMAGE?>" style="margin-top: 15px; width: 150px; height: auto;">
                    </a>
                </div>
            </div>
        </div>



        <div class="form-group" style="margin-top: 25px;">
            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">SAVE</button>
            <a onclick="closeEditAppointment()" class="btn btn-inverse waves-effect waves-light">Cancel</a>
            <a href="enrollment.php?customer_id=<?=$selected_customer_id;?>" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">Enroll</a>
            <a href="customer.php?id=<?=$selected_user_id?>&master_id=<?=$selected_customer_id?>&tab=billing" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">Pay</a>
            <a href="customer.php?id=<?=$selected_user_id?>&master_id=<?=$selected_customer_id?>&tab=appointment" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">View Appointment</a>
        </div>
    </div>
</form>


<script>
    function changeServiceProvider(){
        $('#change_service_provider').hide();
        $('#cancel_change_service_provider').show();
        $('#service_provider_select').slideDown();
        $('#service_provider_name').slideUp();
        $('#date_time_div').slideUp();
        $('#schedule_div').slideDown();
    }

    function cancelChangeServiceProvider() {
        $('#change_service_provider').show();
        $('#cancel_change_service_provider').hide();
        $('#service_provider_select').slideUp();
        $('#service_provider_name').slideDown();
        $('#date_time_div').slideDown();
        $('#schedule_div').slideUp();
    }

    function reschedule() {
        $('#cancel_reschedule').show();
        $('#date_time_div').slideUp();
        $('#schedule_div').slideDown();
    }

    function cancelReschedule() {
        $('#cancel_reschedule').hide();
        $('#date_time_div').slideDown();
        $('#schedule_div').slideUp();
    }

    function changeStatus(){
        $('#cancel_change_status').show();
        $('#change_status').hide();
        $('#PK_APPOINTMENT_STATUS').slideDown();
        $('#appointment_status').slideUp();
    }

    function cancelChangeStatus() {
        $('#cancel_change_status').hide();
        $('#change_status').show();
        $('#PK_APPOINTMENT_STATUS').slideUp();
        $('#appointment_status').slideDown();
    }

    function changeAppointmentStatus(param) {
        if ($(param).val() == 4){
            $('#no_show_div').slideDown();
        }else {
            $('#no_show_div').slideUp();
        }
    }

    function cancelAppointment() {
        let text = "Did you really want to confirm Appointment?";
        if (confirm(text) == true) {
            let PK_APPOINTMENT_MASTER = $('.PK_APPOINTMENT_MASTER').val();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {FUNCTION_NAME: 'cancelAppointment', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    window.location.href='all_schedules.php';
                }
            });
        }
    }
</script>