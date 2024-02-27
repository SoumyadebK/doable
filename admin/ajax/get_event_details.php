<?php
require_once('../../global/config.php');
global $db;
global $db_account;

$res = $db_account->Execute("SELECT * FROM `DOA_EVENT` WHERE `PK_EVENT` = '$_POST[PK_EVENT]'");

if($res->RecordCount() == 0){
    header("location:all_schedules.php");
    exit;
}
$PK_EVENT = $res->fields['PK_EVENT'];
$HEADER = $res->fields['HEADER'];
$PK_EVENT_TYPE = $res->fields['PK_EVENT_TYPE'];
$START_DATE = $res->fields['START_DATE'];
$END_DATE = $res->fields['END_DATE'];
$START_TIME = $res->fields['START_TIME'];
$END_TIME = $res->fields['END_TIME'];
$ALL_DAY=$res->fields['ALL_DAY'];
$DESCRIPTION = $res->fields['DESCRIPTION'];
//$PK_LOCATION = $res->fields['PK_LOCATION'];
$SHARE_WITH_CUSTOMERS = $res->fields['SHARE_WITH_CUSTOMERS'];
$SHARE_WITH_SERVICE_PROVIDERS = $res->fields['SHARE_WITH_SERVICE_PROVIDERS'];
$SHARE_WITH_EMPLOYEES = $res->fields['SHARE_WITH_EMPLOYEES'];
$ACTIVE = $res->fields['ACTIVE'];

$location_operational_hour = $db->Execute("SELECT $account_database.DOA_OPERATIONAL_HOUR.OPEN_TIME, $account_database.DOA_OPERATIONAL_HOUR.CLOSE_TIME FROM $account_database.DOA_OPERATIONAL_HOUR LEFT JOIN $master_database.DOA_LOCATION ON $account_database.DOA_OPERATIONAL_HOUR.PK_LOCATION = $master_database.DOA_LOCATION.PK_LOCATION WHERE $master_database.DOA_LOCATION.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND $account_database.DOA_OPERATIONAL_HOUR.CLOSED = 0 ORDER BY $master_database.DOA_LOCATION.PK_LOCATION LIMIT 1");
if ($location_operational_hour->RecordCount() > 0) {
    $OPEN_TIME = $location_operational_hour->fields['OPEN_TIME'];
    $CLOSE_TIME = $location_operational_hour->fields['CLOSE_TIME'];
} else {
    $OPEN_TIME = '00:00:00';
    $CLOSE_TIME = '23:59:00';
}

?>
<style>
    .ck-editor__editable_inline {
        min-height: 300px;
    }
    .SumoSelect {
        width: 50%;
    }
</style>
<form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="FUNCTION_NAME" value="saveEventData">
    <input type="hidden" name="PK_EVENT" class="PK_EVENT" value="<?=$PK_EVENT?>">
    <div class="row">
        <div class="col-9">
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Header</label>
                        <input type="text" id="HEADER" name="HEADER" class="form-control" required value="<?php echo $HEADER?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Event Type</label>
                        <select class="form-control" name="PK_EVENT_TYPE" id="PK_EVENT_TYPE">
                            <option value="">Select Event Type</option>
                            <?php
                            $row = $db_account->Execute("SELECT * FROM `DOA_EVENT_TYPE` WHERE PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." AND `ACTIVE` = 1");
                            while (!$row->EOF) {?>
                                <option value="<?php echo $row->fields['PK_EVENT_TYPE'];?>" <?=($PK_EVENT_TYPE==$row->fields['PK_EVENT_TYPE'])?'selected':''?>><?=$row->fields['EVENT_TYPE']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" required value="<?php echo ($START_DATE)?date('m/d/Y', strtotime($START_DATE)):''?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" required value="<?php echo ($END_DATE == '0000-00-00')?'':date('m/d/Y', strtotime($END_DATE))?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" class="form-check-inline all_day" name="ALL_DAY" value="1" onchange="checkAllDay(this)" <?=($ALL_DAY == 1) ? 'checked' : ''?>> All Day
                </label>
            </div>

            <div class="row time" style="display: <?=($ALL_DAY == 1) ? 'none' : ''?>">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker"  value="<?php echo ($START_TIME)?date('h:i A', strtotime($START_TIME)):''?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="text" id="END_TIME" name="END_TIME" class="form-control time-picker"  value="<?php echo ($END_TIME == '00:00:00')?'':date('h:i A', strtotime($END_TIME))?>">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <label class="form-label">Location</label>
                    <div class="col-md-12 multiselect-box">
                        <select class="multi_sumo_select" name="PK_LOCATION[]" id="PK_LOCATION" multiple>
                            <?php
                            $selected_location = [];
                            if(!empty($_POST['PK_EVENT'])) {
                                $selected_location_row = $db_account->Execute("SELECT `PK_LOCATION` FROM `DOA_EVENT_LOCATION` WHERE `PK_EVENT` = '$_POST[PK_EVENT]'");
                                while (!$selected_location_row->EOF) {
                                    $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                    $selected_location_row->MoveNext();
                                }
                            }
                            $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=in_array($row->fields['PK_LOCATION'], $selected_location)?"selected":""?>><?=$row->fields['LOCATION_NAME']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
                <?php if(!empty($_POST['PK_EVENT'])) { ?>
                    <div class="col-6">
                        <div class="col-md-2">
                            <label>Active</label>
                        </div>
                        <div class="col-md-4">
                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                        </div>
                    </div>
                <? } ?>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="ckeditor" id="DESCRIPTION" name="DESCRIPTION"><?=$DESCRIPTION?></textarea>
                    </div>
                </div>
            </div>

            <div id="add_more_image">
                <?php
                if(!empty($_POST['PK_EVENT'])) {
                    $row = $db_account->Execute("SELECT * FROM DOA_EVENT_IMAGE WHERE PK_EVENT = ".$_POST['PK_EVENT']);
                    if ($row->RecordCount() > 0) {
                        while (!$row->EOF) { ?>
                            <div class="row">
                                <div class="col-4">
                                    <div class="form-group">
                                        <label class="form-label">Images</label>
                                        <input class="form-control-file" type="file" name="IMAGE[]">
                                        <img src="<?=$row->fields['IMAGE']?>" style="margin-top: 15px; width: 100px; height: auto;">
                                        <input type="hidden" name="IMAGE_PATH[]" value="<?=$row->fields['IMAGE']?>">
                                    </div>
                                </div>
                                <div class="col-2" style="margin-top: 20px">
                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                </div>
                            </div>
                            <?php
                            $row->MoveNext(); } ?>
                        <div class="row m-15">
                            <div class="col-12">
                                <a href="javascript:;" onclick="addMoreImages()"><i class="ti-plus"></i> Add More</a>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label class="form-label">Images</label>
                                    <input class="form-control-file" type="file" name="IMAGE[]">
                                </div>
                            </div>
                            <div class="col-2" style="margin-left: 20px">
                                <a href="javascript:;" onclick="addMoreImages()"><i class="ti-plus"></i> Add More</a>
                            </div>
                        </div>
                    <?php }
                } else { ?>
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Images</label>
                                <input class="form-control-file" type="file" name="IMAGE[]">
                            </div>
                        </div>
                        <div class="col-2" style="margin-left: 20px">
                            <a href="javascript:;" onclick="addMoreImages()"><i class="ti-plus"></i> Add More</a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="col-3">
            <h4 class="card-title">Share With</h4>
            <div class="m-l-20">
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" class="form-check-inline share_with" name="" onchange="checkAll(this)" <?=($SHARE_WITH_CUSTOMERS == 1 && $SHARE_WITH_SERVICE_PROVIDERS == 1 && $SHARE_WITH_EMPLOYEES == 1) ? 'checked' : ''?>> All
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" class="form-check-inline share_with" name="SHARE_WITH_CUSTOMERS" value="1" <?=($SHARE_WITH_CUSTOMERS == 1) ? 'checked' : ''?>> Customers
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" class="form-check-inline share_with" name="SHARE_WITH_SERVICE_PROVIDERS" value="1" <?=($SHARE_WITH_SERVICE_PROVIDERS == 1) ? 'checked' : ''?>> Service Providers
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" class="form-check-inline share_with" name="SHARE_WITH_EMPLOYEES" value="1" <?=($SHARE_WITH_EMPLOYEES == 1) ? 'checked' : ''?>> Employees
                    </label>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
    <a onclick="closeEditAppointment()" class="btn btn-inverse waves-effect waves-light">Cancel</a>
</form>
<script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/classic/ckeditor.js"></script>
<script>
    $('.multi_sumo_select').SumoSelect({placeholder: 'Select Location', selectAll: true});

    function checkAll(ele) {
        var checkboxes = $('.share_with');
        if (ele.checked) {
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].type == 'checkbox') {
                    checkboxes[i].checked = true;
                }
            }
        } else {
            for (var i = 0; i < checkboxes.length; i++) {
                console.log(i)
                if (checkboxes[i].type == 'checkbox') {
                    checkboxes[i].checked = false;
                }
            }
        }
    }

    function checkAllDay(all) {
        if (all.checked) {
            $('.time').slideUp();
        } else {
            $('.time').slideDown();
        }
    }

/*    ClassicEditor
        .create( document.querySelector( '#DESCRIPTION' ) )
        .catch( error => {
            console.error( error );
        } );*/

    function addMoreImages() {
        $('#add_more_image').append(`<div class="row">
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label class="form-label">Images</label>
                                                <input class="form-control-file" type="file" name="IMAGE[]">
                                            </div>
                                        </div>
                                        <div class="col-2" style="margin-top: 20px">
                                            <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                        </div>
                                    </div>`);
    }

    function removeThis(param) {
        $(param).closest('.row').remove();
    }
</script>
</html>