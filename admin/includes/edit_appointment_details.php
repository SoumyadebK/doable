<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;
global $service_provider_title;

$PK_USER = !empty($_GET['PK_USER']) ? $_GET['PK_USER'] : 0;
$PK_USER_MASTER = !empty($_GET['PK_USER_MASTER']) ? $_GET['PK_USER_MASTER'] : 0;

$PK_APPOINTMENT_MASTER = $_GET['PK_APPOINTMENT_MASTER'];

$appointment_data = $db_account->Execute("SELECT DOA_APPOINTMENT_MASTER.*, DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = ".$PK_APPOINTMENT_MASTER);

$STANDING_ID = $appointment_data->fields['STANDING_ID'];
$PK_SCHEDULING_CODE = $appointment_data->fields['PK_SCHEDULING_CODE'];
$PK_SERVICE_MASTER = $appointment_data->fields['PK_SERVICE_MASTER'];
$SERVICE_PROVIDER_ID = $appointment_data->fields['PK_USER'];
$PK_APPOINTMENT_STATUS = $appointment_data->fields['PK_APPOINTMENT_STATUS'];
$DATE = date("m/d/Y",strtotime($appointment_data->fields['DATE']));
$START_TIME = $appointment_data->fields['START_TIME'];
$END_TIME = $appointment_data->fields['END_TIME'];
$IS_CHARGED = $appointment_data->fields['IS_CHARGED'];
$COMMENT = $appointment_data->fields['COMMENT'];
$INTERNAL_COMMENT = $appointment_data->fields['INTERNAL_COMMENT'];

$status_data = $db_account->Execute("SELECT DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE AS STATUS_COLOR, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_APPOINTMENT_STATUS_HISTORY.TIME_STAMP FROM DOA_APPOINTMENT_STATUS_HISTORY LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS=DOA_APPOINTMENT_STATUS_HISTORY.PK_APPOINTMENT_STATUS LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER=DOA_APPOINTMENT_STATUS_HISTORY.PK_USER WHERE PK_APPOINTMENT_MASTER = ".$PK_APPOINTMENT_MASTER);
$CHANGED_BY = '';
while (!$status_data->EOF) {
    $CHANGED_BY .= "(<span style='color: ".$status_data->fields['STATUS_COLOR']."'>".$status_data->fields['APPOINTMENT_STATUS']."</span> by ".$status_data->fields['NAME']." at ".date('m-d-Y H:i:s A', strtotime($status_data->fields['TIME_STAMP'])).")<br>";
    $status_data->MoveNext();
}

?>
<div class="modal-dialog">
    <form id="wallet_payment_form"  method="post" action="includes/save_appointment_details.php" enctype="multipart/form-data">
        <input type="hidden" name="REDIRECT_URL" value="../customer.php?id=<?=$PK_USER?>&master_id=<?=$PK_USER_MASTER?>&tab=enrollment">
        <input type="hidden" name="PK_APPOINTMENT_MASTER" class="PK_APPOINTMENT_MASTER" value="<?=$PK_APPOINTMENT_MASTER?>">
        <div class="modal-content">
            <div class="modal-header">
                <h4><b>Edit Appointment</b></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="p-20">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label"><?=$service_provider_title?></label>
                                <select class="form-control" required name="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID">
                                    <option value="">Select <?=$service_provider_title?></option>
                                    <?php
                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES IN(5) AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_USER'];?>" <?=($SERVICE_PROVIDER_ID==$row->fields['PK_USER'])?'selected':''?>><?=$row->fields['NAME']?></option>
                                    <?php $row->MoveNext(); } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Scheduling Code</label>
                                <select class="form-control" required name="PK_SCHEDULING_CODE" id="PK_SCHEDULING_CODE">
                                    <option value="">Select Scheduling Code</option>
                                    <?php
                                    $row = $db_account->Execute("SELECT DOA_SCHEDULING_CODE.`PK_SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_NAME`, DOA_SCHEDULING_CODE.`DURATION` FROM `DOA_SCHEDULING_CODE` LEFT JOIN DOA_SCHEDULING_SERVICE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE=DOA_SCHEDULING_SERVICE.PK_SCHEDULING_CODE WHERE DOA_SCHEDULING_CODE.`ACTIVE` = 1 AND DOA_SCHEDULING_SERVICE.PK_SERVICE_MASTER=".$PK_SERVICE_MASTER);
                                    while (!$row->EOF) { ?>
                                        <option value="<?=$row->fields['PK_SCHEDULING_CODE'];?>" <?=($PK_SCHEDULING_CODE==$row->fields['PK_SCHEDULING_CODE'])?'selected':''?>><?=$row->fields['SCHEDULING_CODE']?></option>
                                    <?php $row->MoveNext(); } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Appointment Date</label>
                                <input type="text" id="DATE" name="DATE" class="form-control datepicker-normal" placeholder="Date" value="<?=$DATE?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Appointment Start Time</label>
                                <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker" value="<?php echo ($START_TIME)?date('h:i A', strtotime($START_TIME)):''?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <input type="hidden" name="PK_APPOINTMENT_STATUS_OLD" value="<?=$PK_APPOINTMENT_STATUS?>">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select class="form-control" name="PK_APPOINTMENT_STATUS_NEW" id="PK_APPOINTMENT_STATUS_NEW">
                                    <option value="">Select Status</option>
                                    <?php
                                    $selected_status = '';
                                    $row = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_STATUS` WHERE `ACTIVE` = 1");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS'];?>" <?=($PK_APPOINTMENT_STATUS==$row->fields['PK_APPOINTMENT_STATUS'])?'selected':''?>><?=$row->fields['APPOINTMENT_STATUS']?></option>
                                    <?php $row->MoveNext(); } ?>
                                </select>
                            </div>
                        </div>
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
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Comments (Visual for client)</label>
                                <textarea class="form-control" name="COMMENT" rows="4"><?=$COMMENT?></textarea>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Internal Comment</label>
                                <textarea class="form-control" name="INTERNAL_COMMENT" rows="4"><?=$INTERNAL_COMMENT?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <?=$CHANGED_BY?>
                        </div>
                    </div>

                </div>

                <?php if ($STANDING_ID > 0) { ?>
                    <div class="form-group">
                        <label><input type="checkbox" name="STANDING_ID" value="<?=$STANDING_ID?>"> All Session Details Will Be Changed</label>
                    </div>
                <?php } ?>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Process</button>
            </div>
        </div>
    </form>
</div>