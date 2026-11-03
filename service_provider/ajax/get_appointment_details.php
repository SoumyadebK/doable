<?php
require_once('../../global/config.php');

global $db;
global $db_account;
global $upload_path;

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.*,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_MASTER.PK_SERVICE_MASTER,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            GROUP_CONCAT(SERVICE_PROVIDER.PK_USER SEPARATOR ',') AS SERVICE_PROVIDER_ID,
                            GROUP_CONCAT(DOA_USER_MASTER.PK_USER_MASTER SEPARATOR ',') AS CUSTOMER_ID
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = ".$_POST['PK_APPOINTMENT_MASTER'];

$res = $db_account->Execute($ALL_APPOINTMENT_QUERY);

if($res->RecordCount() == 0){
    header("location:all_services.php");
    exit;
}

$PK_APPOINTMENT_MASTER = $_POST['PK_APPOINTMENT_MASTER'];
$STANDING_ID = $res->fields['STANDING_ID'];
$CUSTOMER_ID = $PK_USER_MASTER = $res->fields['CUSTOMER_ID'];
$PK_ENROLLMENT_MASTER = $res->fields['PK_ENROLLMENT_MASTER'];
$SERIAL_NUMBER = $res->fields['SERIAL_NUMBER'];
$PK_SERVICE_MASTER = $res->fields['PK_SERVICE_MASTER'];
$SERVICE_NAME = $res->fields['SERVICE_NAME'];
$PK_SERVICE_CODE = $res->fields['PK_SERVICE_CODE'];
$PK_SCHEDULING_CODE = $res->fields['PK_SCHEDULING_CODE'];
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
$INTERNAL_COMMENT = $res->fields['INTERNAL_COMMENT'];
$IMAGE = $res->fields['IMAGE'];
$VIDEO = $res->fields['VIDEO'];
$IS_CHARGED = $res->fields['IS_CHARGED'];

$status_data = $db_account->Execute("SELECT DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE AS STATUS_COLOR, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_APPOINTMENT_STATUS_HISTORY.TIME_STAMP FROM DOA_APPOINTMENT_STATUS_HISTORY LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS=DOA_APPOINTMENT_STATUS_HISTORY.PK_APPOINTMENT_STATUS LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER=DOA_APPOINTMENT_STATUS_HISTORY.PK_USER WHERE PK_APPOINTMENT_MASTER = '$_POST[PK_APPOINTMENT_MASTER]'");
$CHANGED_BY = '';
while (!$status_data->EOF) {
    $CHANGED_BY .= "(<span style='color: ".$status_data->fields['STATUS_COLOR']."'>".$status_data->fields['APPOINTMENT_STATUS']."</span> by ".$status_data->fields['NAME']." at ".date('m-d-Y H:i:s A', strtotime($status_data->fields['TIME_STAMP'])).")<br>";
    $status_data->MoveNext();
}


$customer_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$CUSTOMER_ID'");

$selected_customer = $customer_data->fields['NAME'];
$customer_phone = $customer_data->fields['PHONE'];
$customer_email = $customer_data->fields['EMAIL_ID'];
$selected_customer_id = $customer_data->fields['PK_USER_MASTER'];
$selected_user_id = $customer_data->fields['PK_USER'];

$partner_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = '$selected_customer_id'");

?>

<form class="form-material form-horizontal" id="appointment_form" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="PK_APPOINTMENT_MASTER" class="PK_APPOINTMENT_MASTER" value="<?=$PK_APPOINTMENT_MASTER?>">
    <input type="hidden" name="APPOINTMENT_TYPE" class="APPOINTMENT_TYPE" value="NORMAL">
    <input type="hidden" name="FUNCTION_NAME" value="saveAppointmentData">
    <div style="padding-top: 10px;">
        <div class="row">
            <div class="col-4">
                <div class="form-group">
                    <label class="form-label">Name: </label>
                    <p><?=$selected_customer?></p>
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
        </div>
        <?php if ($partner_data->RecordCount() > 0 && $partner_data->fields['ATTENDING_WITH'] == 'With a Partner') { ?>
            <div class="row" style="margin-top: -25px;">
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Partner Name: </label>
                        <p><?=$partner_data->fields['PARTNER_FIRST_NAME']?> <?=$partner_data->fields['PARTNER_LAST_NAME']?></p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Phone: </label>
                        <p><?=$partner_data->fields['PARTNER_PHONE']?></p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">Email: </label>
                        <p><?=$partner_data->fields['PARTNER_EMAIL']?></p>
                    </div>
                </div>
            </div>
        <?php } ?>
        <div class="row">
            <?php if ($SERVICE_NAME != 'For records only') { ?>
                <div class="col-4" id="enrollment_div">
                    <div class="form-group">
                        <label class="form-label">Enrollment ID : <span id="change_enrollment" style="margin-left: 30px;"><a href="javascript:" onclick="changeEnrollment()">Change</a></span>
                            <span id="cancel_change_enrollment" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeEnrollment()">Cancel</a></span></label>
                        <select  id="enrollment_select" class="form-control" name="PK_ENROLLMENT_MASTER" style="display: none;">
                            <option value="">Select Enrollment ID</option>
                            <?php
                            $selected_enrollment = '';
                            $row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_PACKAGE.PACKAGE_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.PK_LOCATION, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.CHARGE_TYPE, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID FROM DOA_ENROLLMENT_MASTER RIGHT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_PACKAGE ON DOA_ENROLLMENT_MASTER.PK_PACKAGE = DOA_PACKAGE.PK_PACKAGE WHERE DOA_SERVICE_MASTER.PK_SERVICE_CLASS != 5 AND DOA_SERVICE_CODE.IS_GROUP != 1 AND DOA_ENROLLMENT_MASTER.STATUS = 'A' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = ".$PK_USER_MASTER);
                            while (!$row->EOF) {
                                $name = $row->fields['ENROLLMENT_NAME'];
                                if(empty($name)){
                                    $enrollment_name = ' ';
                                }else {
                                    $enrollment_name = "$name"." - ";
                                }

                                $PACKAGE_NAME = $row->fields['PACKAGE_NAME'];
                                if(empty($PACKAGE_NAME)){
                                    $PACKAGE = ' ';
                                }else {
                                    $PACKAGE = "$PACKAGE_NAME"." || ";
                                }

                                if ($row->fields['CHARGE_TYPE'] == 'Membership') {
                                    $NUMBER_OF_SESSION = 99;//getAllSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE'], 'NORMAL');
                                } else {
                                    $NUMBER_OF_SESSION = $row->fields['NUMBER_OF_SESSION'];
                                }

                                $PRICE_PER_SESSION = $row->fields['PRICE_PER_SESSION'];
                                $TOTAL_AMOUNT_PAID = ($row->fields['TOTAL_AMOUNT_PAID'] != null) ? $row->fields['TOTAL_AMOUNT_PAID'] : 0;
                                $USED_SESSION_COUNT = getAllSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE'], 'NORMAL');
                                $paid_session = ($PRICE_PER_SESSION > 0) ? number_format(($TOTAL_AMOUNT_PAID/$PRICE_PER_SESSION), 2) : $NUMBER_OF_SESSION;

                                if ((($NUMBER_OF_SESSION - $USED_SESSION_COUNT) > 0) || ($row->fields['CHARGE_TYPE'] == 'Membership')) {
                                    if($PK_ENROLLMENT_MASTER==$row->fields['PK_ENROLLMENT_MASTER']){$selected_enrollment = $row->fields['ENROLLMENT_ID'];} ?>
                                    <option value="<?php echo $row->fields['PK_ENROLLMENT_MASTER'].','.$row->fields['PK_ENROLLMENT_SERVICE'].','.$row->fields['PK_SERVICE_MASTER'].','.$row->fields['PK_SERVICE_CODE'];?>" data-location_id="<?=$row->fields['PK_LOCATION']?>" data-no_of_session="<?=$NUMBER_OF_SESSION?>" data-used_session="<?=$USED_SESSION_COUNT?>" <?=(($NUMBER_OF_SESSION - $USED_SESSION_COUNT) <= 0) ? 'disabled':''?> <?=($PK_ENROLLMENT_MASTER==$row->fields['PK_ENROLLMENT_MASTER'])?'selected':''?>><?=$enrollment_name.$row->fields['ENROLLMENT_ID'].' || '.$PACKAGE.$row->fields['SERVICE_NAME'].' || '.$row->fields['SERVICE_CODE'].' || '.$USED_SESSION_COUNT.'/'.$NUMBER_OF_SESSION.' || Paid : '.$paid_session;?></option>
                                <?php }
                                $row->MoveNext();
                            } ?>
                        </select>
                        <p class="enrollment_info"><?=$selected_enrollment?></p>
                    </div>
                </div>
            <?php } ?>

            <div class="col-4 enrollment_info">
                <div class="form-group">
                    <label class="form-label">Apt #: </label>
                    <p><?=$SERIAL_NUMBER?></p>
                </div>
            </div>
            <div class="col-4 enrollment_info">
                <div class="form-group">
                    <label class="form-label">Service : </label>
                    <select class="form-control" required name="PK_SERVICE_MASTER" id="PK_SERVICE_MASTER" style="display: none;" onchange="selectThisService(this);" disabled>
                        <option value="">Select Service</option>
                        <?php
                        $selected_service = '';
                        $row = $db_account->Execute("SELECT DISTINCT(DOA_SERVICE_MASTER.PK_SERVICE_MASTER), DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_MASTER");
                        while (!$row->EOF) { if($PK_SERVICE_MASTER==$row->fields['PK_SERVICE_MASTER']){$selected_service = $row->fields['SERVICE_NAME'];} ?>
                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" <?=($PK_SERVICE_MASTER==$row->fields['PK_SERVICE_MASTER'])?'selected':''?>><?=$row->fields['SERVICE_NAME']?></option>
                            <?php $row->MoveNext(); } ?>
                    </select>
                    <p><?=$selected_service?></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-4">
                <div class="form-group">
                    <label class="form-label">Service Code : </label>
                    <?php if ($PK_SERVICE_CODE==0) {
                        $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE IS_DEFAULT=1");
                        $service_code = $row->fields['SERVICE_CODE'];?>
                        <p><?=$service_code?></p>
                    <?php } else { ?>
                        <select class="form-control" required name="PK_SERVICE_CODE" id="PK_SERVICE_CODE" style="display: none;" onchange="getSlots()" disabled>
                            <option value="">Select Service Code</option>
                            <?php
                            $selected_service_code = '';
                            $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE PK_SERVICE_MASTER = ".$PK_SERVICE_MASTER);
                            while (!$row->EOF) { if($PK_SERVICE_CODE==$row->fields['PK_SERVICE_CODE']){$selected_service_code = $row->fields['SERVICE_CODE'];} ?>
                                <option value="<?php echo $row->fields['PK_SERVICE_CODE'];?>" data-duration="<?=$row->fields['DURATION']?>" <?=($PK_SERVICE_CODE==$row->fields['PK_SERVICE_CODE'])?'selected':''?>><?=$row->fields['SERVICE_CODE']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                    <?php } ?>

                    <p><?=$selected_service_code?></p>
                </div>
            </div>
            <div class="col-4">
                <div class="form-group">
                    <label class="form-label">Scheduling Code : <span id="change_scheduling_code" style="margin-left: 30px;"><a href="javascript:;" onclick="changeSchedulingCode()">Change</a></span>
                        <span id="cancel_change_scheduling_code" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeSchedulingCode()">Cancel</a></span></label>
                    <div id="scheduling_code_select" style="display: none;">
                        <select class="form-control" required name="PK_SCHEDULING_CODE" id="PK_SCHEDULING_CODE">
                            <option value="">Select Scheduling Code</option>
                            <?php
                            $selected_scheduling_code = '';
                            $row = $db_account->Execute("SELECT DOA_SCHEDULING_CODE.`PK_SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_NAME`, DOA_SCHEDULING_CODE.`DURATION` FROM `DOA_SCHEDULING_CODE` LEFT JOIN DOA_SCHEDULING_SERVICE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE=DOA_SCHEDULING_SERVICE.PK_SCHEDULING_CODE WHERE DOA_SCHEDULING_CODE.`ACTIVE` = 1 AND DOA_SCHEDULING_SERVICE.PK_SERVICE_MASTER=".$PK_SERVICE_MASTER);
                            while (!$row->EOF) { if($PK_SCHEDULING_CODE==$row->fields['PK_SCHEDULING_CODE']){$selected_scheduling_code = $row->fields['SCHEDULING_CODE'];} ?>
                                <option value="<?php echo $row->fields['PK_SCHEDULING_CODE'];?>" <?=($PK_SCHEDULING_CODE==$row->fields['PK_SCHEDULING_CODE'])?'selected':''?>><?=$row->fields['SCHEDULING_CODE']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                    <p id="scheduling_code_name"><?=$selected_scheduling_code?></p>
                </div>
            </div>
            <div class="col-4">
                <div class="form-group">
                    <label class="form-label"><?=$service_provider_title?> : <span id="change_service_provider" style="margin-left: 30px;"><a href="javascript:;" onclick="changeServiceProvider()">Change</a></span>
                        <span id="cancel_change_service_provider" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeServiceProvider()">Cancel</a></span></label>
                    <div id="service_provider_select" style="display: none;">
                        <select class="form-control" required name="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID" onchange="getSlots()">
                            <option value="">Select <?=$service_provider_title?></option>
                            <?php
                            $selected_service_provider = '';
                            $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES IN(5) AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                            while (!$row->EOF) { if($SERVICE_PROVIDER_ID==$row->fields['PK_USER']){$selected_service_provider = $row->fields['NAME'];} ?>
                                <option value="<?php echo $row->fields['PK_USER'];?>" <?=($SERVICE_PROVIDER_ID==$row->fields['PK_USER'])?'selected':''?>><?=$row->fields['NAME']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                    <p id="service_provider_name"><?=$selected_service_provider?></p>
                </div>
            </div>
        </div>
        <!--<span id="cancel_reschedule" style="display: none;"><a href="javascript:;" onclick="cancelReschedule()">Cancel</a></span>-->
        <div class="row" id="date_time_div">
            <div class="col-4">
                <div class="form-group">
                    <label class="form-label">Date : </label>
                    <p><?=$DATE?></p>
                </div>
            </div>
            <!--<div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Time : </label>
                                <p><?php /*=date('h:i A', strtotime($START_TIME)).' - '.date('h:i A', strtotime($END_TIME))*/?></p>
                            </div>
                        </div>-->
            <div class="col-4">
                <div class="form-group">
                    <label class="form-label">Start Time</label>
                    <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker" value="<?php echo ($START_TIME)?date('h:i A', strtotime($START_TIME)):''?>" readonly>
                </div>
            </div>
            <div class="col-4">
                <div class="form-group">
                    <label class="form-label">End Time</label>
                    <input type="text" id="END_TIME" name="END_TIME" class="form-control time-picker" value="<?php echo ($END_TIME)?date('h:i A', strtotime($END_TIME)):''?>" readonly>
                </div>
            </div>
            <!--<div class="col-3">
                <div class="form-group">
                    <span><a href="javascript:;" onclick="cancelAppointment()">Cancel</a></span>
                </div>
            </div>-->
        </div>

        <div class="row m-t-25">
            <input type="hidden" name="PK_APPOINTMENT_STATUS_OLD" value="<?=$PK_APPOINTMENT_STATUS?>">
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">Status : <?php /*if($PK_APPOINTMENT_STATUS!=2) {*/?><!--<span id="change_status" style="margin-left: 30px;"><a href="javascript:;" onclick="changeStatus()">Change</a></span><?php /*}*/?>
                                <span id="cancel_change_status" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeStatus()">Cancel</a></span>--></label><br>
                    <select class="form-control" name="PK_APPOINTMENT_STATUS_NEW" id="PK_APPOINTMENT_STATUS" onchange="changeAppointmentStatus(this)" <?=($PK_APPOINTMENT_STATUS == 2)?'disabled':''?>>
                        <option value="">Select Status</option>
                        <?php
                        $selected_status = '';
                        $row = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_STATUS` WHERE `ACTIVE` = 1");
                        while (!$row->EOF) { if($PK_APPOINTMENT_STATUS==$row->fields['PK_APPOINTMENT_STATUS']){$selected_status=$row->fields['APPOINTMENT_STATUS'];}?>
                            <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS'];?>" <?=($PK_APPOINTMENT_STATUS==$row->fields['PK_APPOINTMENT_STATUS'])?'selected':''?>><?=$row->fields['APPOINTMENT_STATUS']?></option>
                            <?php $row->MoveNext(); } ?>
                    </select>
                    <!--<p id="appointment_status"><?php /*=$selected_status*/?></p>-->
                </div>
            </div>

            <?php if ($SERVICE_NAME != 'For records only') { ?>
                <div class="col-6">
                    <input type="hidden" name="IS_CHARGED_OLD" value="<?=$IS_CHARGED?>">
                    <div class="form-group">
                        <label class="form-label">Payment Status</label>
                        <select class="form-control" name="IS_CHARGED" id="IS_CHARGED">
                            <option value="1" <?=($IS_CHARGED==1)?'selected':''?>>Charge</option>
                            <option value="0" <?=($IS_CHARGED==0)?'selected':''?>>No charge</option>
                        </select>
                    </div>
                </div>
            <?php } ?>
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

        <div class="row" id="add_info_div">
            <?php if ($SERVICE_NAME != 'For records only') { ?>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Comments (Visual for client)</label>
                        <textarea class="form-control" name="COMMENT" rows="4"><?=$COMMENT?></textarea><span><?=$CHANGED_BY?></span>
                    </div>
                </div>
            <?php } ?>
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">Internal Comment</label>
                    <textarea class="form-control" name="INTERNAL_COMMENT" rows="4"><?=$INTERNAL_COMMENT?></textarea>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">Upload Image</label>
                    <input type="file" class="form-control" name="IMAGE" id="IMAGE">
                    <a href="<?=$IMAGE?>" target="_blank">
                        <img src="<?=$IMAGE?>" style="margin-top: 15px; width: 150px; height: auto;">
                    </a>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">Upload Video</label>
                    <input type="file" class="form-control" name="VIDEO" id="VIDEO" accept="video/*">
                    <a href="<?=$VIDEO?>" target="_blank">
                        <?php if($VIDEO != '') {?>
                            <video width="240" height="135" controls>
                                <source src="<?=$VIDEO?>" type="video/mp4">
                            </video>
                        <?php }?>
                    </a>
                </div>
            </div>
        </div>

        <?php if ($STANDING_ID > 0) { ?>
            <div class="form-group">
                <label><input type="checkbox" name="STANDING_ID" value="<?=$STANDING_ID?>"> All Session Details Will Be Changed</label>
            </div>
        <?php } ?>

        <div class="form-group" style="margin-top: 25px;">
            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">SAVE</button>
            <a onclick="closeEditAppointment()" class="btn btn-inverse waves-effect waves-light">Cancel</a>
            <!--<a href="enrollment.php?customer_id=<?php /*=$selected_customer_id;*/?>" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">Enroll</a>-->
            <!--<a href="customer.php?id=<?php /*=$selected_user_id*/?>&master_id=<?php /*=$selected_customer_id*/?>&tab=billing" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">Pay</a>
                    <a href="customer.php?id=<?php /*=$selected_user_id*/?>&master_id=<?php /*=$selected_customer_id*/?>&tab=appointment" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">View Appointment</a>-->
            <!--<a onclick="deleteThisAppointment(<?php /*=$PK_APPOINTMENT_MASTER*/?>)" class="btn btn-danger waves-effect waves-light"><i class="ti-trash"></i> Delete</a>-->
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

    function changeEnrollment(){
        $('#change_enrollment').hide();
        $('#cancel_change_enrollment').show();
        $('#enrollment_select').show();
        $('.enrollment_info').hide();
        $('#enrollment_div').removeClass('col-4').addClass('col-12');
        changeSchedulingCode();
    }

    function cancelChangeEnrollment() {
        $('#change_enrollment').show();
        $('#cancel_change_enrollment').hide();
        $('#enrollment_select').hide();
        $('.enrollment_info').show();
        $('#enrollment_div').removeClass('col-12').addClass('col-4');
    }

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