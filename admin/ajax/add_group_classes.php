<?php
require_once('../../global/config.php');
global $db;
global $db_account;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if (!empty($_GET['date']) && !empty($_GET['time'])) {
    $date = $_GET['date'];
    $time = $_GET['time'];
} else {
    $date = '';
    $time = '';
}

if (!empty($_GET['SERVICE_PROVIDER_ID'])) {
    $PK_USER = $_GET['SERVICE_PROVIDER_ID'];
} else {
    $PK_USER = '';
}

$location_operational_hour = $db_account->Execute("SELECT MIN(DOA_OPERATIONAL_HOUR.OPEN_TIME) AS OPEN_TIME, MAX(DOA_OPERATIONAL_HOUR.CLOSE_TIME) AS CLOSE_TIME, DAY_NUMBER FROM DOA_OPERATIONAL_HOUR WHERE CLOSED = 0 AND PK_LOCATION = ".$DEFAULT_LOCATION_ID);
if ($location_operational_hour->RecordCount() > 0) {
    $minTime = $location_operational_hour->fields['OPEN_TIME'];
    $maxTime = $location_operational_hour->fields['CLOSE_TIME'];
} else {
    $minTime = '00:00:00';
    $maxTime = '24:00:00';
}

/*$row = $db_account->Execute("SELECT * FROM DOA_APPOINTMENT_MASTER WHERE DATE = '".date('Y-m-d', strtotime($date))."' AND '".date('H:i:s', strtotime($time))."' >= START_TIME AND '".date('H:i:s', strtotime($time))."' <= END_TIME");
$selected_service_provider = [];
while (!$row->EOF) {
    $selected_service_provider[] = $row->fields['SERVICE_PROVIDER_ID'];
    $row->MoveNext();
}
$selected_service_provider_array = implode(',', $selected_service_provider);

if ($row->RecordCount() > 0) {
    $AND_PK_USER = " AND DOA_USERS.PK_USER NOT IN (".$selected_service_provider_array.")";
} else {
    $AND_PK_USER = ' ';
}*/

$AND_PK_USER = ' ';
?>

<form id="appointment_form" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="FUNCTION_NAME" value="saveGroupClassData">
    <input type="hidden" name="PK_APPOINTMENT_MASTER" class="PK_APPOINTMENT_MASTER" value="<?=(empty($_GET['id']))?'':$_GET['id']?>">
    <div class="p-40 " style="padding-top: 10px;">
        <div id="append_service_code">
        <div class="row" style="border-bottom: 1px solid grey;">
            <div class="col-4">
                <div class="form-group">
                    <label class="form-label">Service<span class="text-danger">*</span></label><br>
                    <select class="multi_select" required name="SERVICE_ID" onchange="selectThisService(this)">
                        <option value="">Select Service</option>
                        <?php
                        $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_CODE LEFT JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_CODE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER JOIN DOA_SERVICE_LOCATION ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_LOCATION.PK_SERVICE_MASTER WHERE DOA_SERVICE_CODE.IS_GROUP = 1 AND DOA_SERVICE_LOCATION.PK_LOCATION IN (".$DEFAULT_LOCATION_ID.") AND DOA_SERVICE_MASTER.IS_DELETED = 0");
                        while (!$row->EOF) { ?>
                            <option value="<?=$row->fields['PK_SERVICE_MASTER'].','.$row->fields['PK_SERVICE_CODE'];?>"><?=$row->fields['SERVICE_NAME'].' || '.$row->fields['SERVICE_CODE']?></option>
                        <?php $row->MoveNext(); } ?>
                    </select>
                </div>
            </div>
            <div class="col-4">
                <div class="form-group">
                    <label class="form-label">Scheduling Code<span class="text-danger">*</span></label><br>
                    <select class="multi_select" required id="PK_SCHEDULING_CODE" name="SCHEDULING_CODE">
                        <option value="">Select Scheduling Code</option>
                    </select>
                </div>
            </div>
            <!--<div class="col-4">
                <div class="form-group">
                    <label class="form-label">Service <span class="text-danger">*</span></label><br>
                    <select required name="SERVICE_ID" id="SERVICE_ID" onchange="selectThisService(this);">
                        <option value="">Select Service</option>
                        <?php
/*                        $row = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_CODE.DURATION, DOA_SERVICE_CODE.CAPACITY FROM DOA_SERVICE_CODE LEFT JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_CODE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_SERVICE_MASTER.ACTIVE = 1 AND DOA_SERVICE_CODE.IS_GROUP = 1 AND DOA_SERVICE_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                        while (!$row->EOF) { */?>
                            <option value="<?php /*=$row->fields['DURATION'].','.$row->fields['PK_SERVICE_CODE'].','.$row->fields['PK_SERVICE_MASTER'];*/?>"><?php /*=$row->fields['SERVICE_NAME'].' || '.$row->fields['SERVICE_CODE'];*/?></option>
                        <?php /*$row->MoveNext(); } */?>
                    </select>
                </div>
            </div>-->
            <!--<div class="col-3">
                <div class="form-group">
                    <label class="form-label">Primary <?php /*=$service_provider_title*/?> <span class="text-danger">*</span></label>
                    <select name="SERVICE_PROVIDER_ID_1" class="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID_1" required>
                    <option value="">Select <?php /*=$service_provider_title*/?></option>

                    </select>
                </div>
            </div>
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label">Secondary <?php /*=$service_provider_title*/?></label>
                    <select name="SERVICE_PROVIDER_ID_2" class="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID_2">
                    <option value="">Select <?php /*=$service_provider_title*/?></option>

                    </select>
                </div>
            </div>-->
            <div class="col-4">
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <select class="form-control" name="PK_LOCATION" id="PK_LOCATION">
                        <option value="">Select Location</option>
                        <?php
                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=(in_array($row->fields['PK_LOCATION'], explode(',', $_SESSION['DEFAULT_LOCATION_ID'])))?'selected':''?>><?=$row->fields['LOCATION_NAME']?></option>
                        <?php $row->MoveNext(); } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="row justify-content-evenly group_class_setting" style="margin-top: 20px; border-bottom: 1px solid grey;">
            <input type="hidden" name="PK_GROUP_CLASS[]" value="<?=$row->fields['PK_GROUP_CLASS']?>">
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label"><?=$service_provider_title?> <span class="text-danger">*</span></label>
                    <select name="SERVICE_PROVIDER_ID_0[]" class="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID_0" required multiple>
                        <?php
                        $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 5 ".$AND_PK_USER." AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY NAME");

                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_USER'];?>" <?=($PK_USER == $row->fields['PK_USER'])?"selected":""?>><?=$row->fields['NAME']?></option>
                            <?php $row->MoveNext(); } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Group Name</label><br>
                    <input class="form-control" type="text" name="GROUP_NAME[]" id="GROUP_NAME" placeholder="Group Name">
                </div>
            </div>
            <div class="col-2">
                <div class="form-group">
                    <label class="form-label">Starting On<span class="text-danger">*</span></label><br>
                    <input class="form-control datepicker-normal" type="text" name="STARTING_ON[]" value="<?=$date?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Time<span class="text-danger">*</span></label><br>
                    <input class="form-control timepicker-normal" type="text" name="START_TIME[]" value="<?=$time?>" required>
                </div>
            </div>
            <div class="col-2 days_div">
                <div class="form-group">
                    <label class="form-label">Select Days</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_0[]" value="monday"> Monday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_0[]" value="tuesday"> Tuesday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_0[]" value="wednesday"> Wednesday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_0[]" value="thursday"> Thursday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_0[]" value="friday"> Friday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_0[]" value="saturday"> Saturday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_0[]" value="sunday"> Sunday</label><br>
                </div>
            </div>
            <div class="col-2">
                <div class="form-group occurrence_div">
                    <label class="form-label">Select Occurrence<span class="text-danger">*</span></label><br>
                    <label><input type="radio" class="OCCURRENCE" name="OCCURRENCE_0" value="WEEKLY" required> Weekly</label><br>
                    <label><input type="radio" class="OCCURRENCE" name="OCCURRENCE_0" value="DAYS" required> Every <input type="text" name="OCCURRENCE_DAYS[]" style="width: 45px;"> Days</label>
                </div>
                <div class="form-group length_div">
                    <label class="form-label">Length<span class="text-danger">*</span></label><br>
                    <input type="number" id="LENGTH" class="form-control" name="LENGTH[]" style="width: 80px;" required>
                    <select class="form-control" name="FREQUENCY[]" style="width: 100px;" required>
                        <option value="week">Week(S)</option>
                        <option value="month">Month(S)</option>
                        <option value="year">Year(S)</option>
                    </select>
                    <label><input type="checkbox" class="YEAR" name="YEAR_0" value=""> Ongoing</label>
                </div>
            </div>
            <div class="col-1">
                <div class="form-group">
                    <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeClass(this);"><i class="ti-trash"></i></a>
                </div>
            </div>
        </div>
    </div>
        <div class="row" style="margin-top: 20px">
            <div class="form-group">
                <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addMoreClass();">Add More</a>
            </div>
        </div>
        <?php if ($time_zone == 1){ ?>
            <div class="form-group" style="margin-top: 25px;">
                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">SAVE</button>
                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
            </div>
        <?php } else { ?>
            <div class="alert alert-danger mt-2">
                <strong>Warning!</strong> Please go to your Business Profile or Location Profile to set the timezone first.
            </div>
        <?php } ?>
    </div>
</form>

<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

<script type="text/javascript">
    $('.multi_select').SumoSelect({search: true, searchText: 'Search...'});

    $('.datepicker-normal').datepicker({
        changeMonth: true,
        changeYear: true,
        format: 'mm/dd/yyyy',
    });

    $('.timepicker-normal').timepicker({
        timeFormat: 'hh:mm p',
        maxTime: '<?=$maxTime?>',
        minTime: '<?=$minTime?>'
    });

    $('.DAYS').on('change', function(){
        if ($(this).closest('.group_class_setting').find('.DAYS').is(':checked')){
            $(this).closest('.group_class_setting').find("input[class='OCCURRENCE'][value='WEEKLY']").prop('checked', true);
            $(this).closest('.group_class_setting').find('.occurrence_div').addClass('disable-div');
            //$(this).closest('.group_class_setting').find('.length_div').addClass('disable-div');
        } else {
            $(this).closest('.group_class_setting').find("input[class='OCCURRENCE'][value='WEEKLY']").prop('checked', false);
            $(this).closest('.group_class_setting').find('.occurrence_div').removeClass('disable-div');
            //$(this).closest('.group_class_setting').find('.length_div').removeClass('disable-div');
        }
    });

    $('.YEAR').on('change', function(){
        if ($(this).is(':checked')){
            let length = 52;
            $(this).closest('.group_class_setting').find('.days_div').addClass('disable-div');
            $(this).closest('.group_class_setting').find('.occurrence_div').addClass('disable-div');
            $(this).closest('.group_class_setting').find('#LENGTH').val(length);
        } else {
            $(this).closest('.group_class_setting').find('.days_div').removeClass('disable-div');
            $(this).closest('.group_class_setting').find('.occurrence_div').removeClass('disable-div');
        }
    });

    $('#SERVICE_ID').SumoSelect({placeholder: 'Select Services', search: true, searchText: 'Search...'});
    $('.SERVICE_PROVIDER_ID').SumoSelect({placeholder: 'Select <?=$service_provider_title?>', selectAll: true, search: true, searchText: 'Search...'});

    function selectThisService(param) {
        let PK_SERVICE_MASTER = $(param).val();
        $.ajax({
            url: "ajax/get_scheduling_codes.php",
            type: "POST",
            data: {PK_SERVICE_MASTER: PK_SERVICE_MASTER},
            async: false,
            cache: false,
            success: function (result) {
                $('#PK_SCHEDULING_CODE').empty();
                $('#PK_SCHEDULING_CODE').append(result);
                $('#PK_SCHEDULING_CODE')[0].sumo.reload();
            }
        });
    }

/*    function selectThisService(param) {
        let SERVICE_ID = $(param).val();
        $.ajax({
            url: "ajax/get_service_provider.php",
            type: "POST",
            data: {PK_ENROLLMENT_MASTER: SERVICE_ID},
            async: false,
            cache: false,
            success: function (result) {
                $('.SERVICE_PROVIDER_ID').empty();
                $('.SERVICE_PROVIDER_ID').append(result);
                $('#SERVICE_PROVIDER_ID_0')[0].sumo.reload();
                $('#SERVICE_PROVIDER_ID_2')[0].sumo.reload();
                $('#SERVICE_PROVIDER_ID_2').prop('required', false);
            }
        });
    }*/

    var counter = 1;
    function addMoreClass() {
        $('#append_service_code').append(`<div class="row justify-content-evenly group_class_setting" style="margin-top: 20px; border-bottom: 1px solid grey;">
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label"><?=$service_provider_title?> <span class="text-danger">*</span></label>
                    <select name="SERVICE_PROVIDER_ID_${counter}[]" class="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID_${counter}" multiple required>
                    <?php
                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 5 ".$AND_PK_USER." AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY NAME");
                    while (!$row->EOF) { ?>
                        <option value="<?php echo $row->fields['PK_USER'];?>" <?=($PK_USER == $row->fields['PK_USER'])?"selected":""?>><?=$row->fields['NAME']?></option>
                    <?php $row->MoveNext(); } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Group Name</label><br>
                    <input class="form-control" type="text" name="GROUP_NAME[]" id="GROUP_NAME" placeholder="Group Name">
                </div>
            </div>
            <div class="col-2">
                <div class="form-group">
                    <label class="form-label">Starting On<span class="text-danger">*</span></label><br>
                    <input class="form-control datepicker-normal" type="text" name="STARTING_ON[]" value="<?=$date?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Time<span class="text-danger">*</span></label><br>
                    <input class="form-control timepicker-normal" type="text" name="START_TIME[]" value="<?=$time?>" required>
                </div>
            </div>
            <div class="col-2">
                <div class="form-group">
                    <label class="form-label">Select Days</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_${counter}[]" value="monday"> Monday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_${counter}[]" value="tuesday"> Tuesday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_${counter}[]" value="wednesday"> Wednesday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_${counter}[]" value="thursday"> Thursday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_${counter}[]" value="friday"> Friday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_${counter}[]" value="saturday"> Saturday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS_${counter}[]" value="sunday"> Sunday</label><br>
                </div>
            </div>
            <div class="col-2">
                <div class="form-group occurrence_div">
                    <label class="form-label">Select Occurrence<span class="text-danger">*</span></label><br>
                    <label><input type="radio" class="OCCURRENCE" name="OCCURRENCE_${counter}" value="WEEKLY" required> Weekly</label><br>
                    <label><input type="radio" class="OCCURRENCE" name="OCCURRENCE_${counter}" value="DAYS" required> Every <input type="text" name="OCCURRENCE_DAYS[]" style="width: 45px;"> Days</label>
                </div>
                <div class="form-group length_div">
                    <label class="form-label">Length<span class="text-danger">*</span></label><br>
                    <input type="number" id="LENGTH" class="form-control" name="LENGTH[]" style="width: 80px;" required>
                    <select class="form-control" name="FREQUENCY[]" style="width: 100px;" required>
                        <option value="week">Week(S)</option>
                        <option value="month">Month(S)</option>
                        <option value="year">Year(S)</option>
                    </select>
                    <label><input type="checkbox" class="YEAR" name="YEAR_${counter}" value=""></label>
                </div>
            </div>
            <div class="col-1">
                <div class="form-group">
                    <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeClass(this);"><i class="ti-trash"></i></a>
                </div>
            </div>
        </div>`);

        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        $('.timepicker-normal').timepicker({
            timeFormat: 'hh:mm p',
        });

        $('.DAYS').on('change', function(){
            if ($(this).closest('.group_class_setting').find('.DAYS').is(':checked')){
                $(this).closest('.group_class_setting').find("input[class='OCCURRENCE'][value='WEEKLY']").prop('checked', true);
                $(this).closest('.group_class_setting').find('.occurrence_div').addClass('disable-div');
                //$(this).closest('.group_class_setting').find('.length_div').addClass('disable-div');
            } else {
                $(this).closest('.group_class_setting').find("input[class='OCCURRENCE'][value='WEEKLY']").prop('checked', false);
                $(this).closest('.group_class_setting').find('.occurrence_div').removeClass('disable-div');
                //$(this).closest('.group_class_setting').find('.length_div').removeClass('disable-div');
            }
        });

        $('.YEAR').on('change', function(){
            if ($(this).is(':checked')){
                let length = 52;
                $(this).closest('.group_class_setting').find('.days_div').addClass('disable-div');
                $(this).closest('.group_class_setting').find('.occurrence_div').addClass('disable-div');
                $(this).closest('.group_class_setting').find('#LENGTH').val(length);
            } else {
                $(this).closest('.group_class_setting').find('.days_div').removeClass('disable-div');
                $(this).closest('.group_class_setting').find('.occurrence_div').removeClass('disable-div');
            }
        });

        $('#SERVICE_ID').SumoSelect({placeholder: 'Select Services', search: true, searchText: 'Search...'});
        $('.SERVICE_PROVIDER_ID').SumoSelect({placeholder: 'Select <?=$service_provider_title?>', selectAll: true, search: true, searchText: 'Search...'});

        counter++;
    }

    function removeClass(param) {
        $(param).closest('.row').remove();
        counter--;
    }
</script>
