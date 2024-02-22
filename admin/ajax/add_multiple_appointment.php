<?php
require_once('../../global/config.php');
global $db;
global $db_account;

if (empty($_GET['PK_USER_MASTER'])) {
    $PK_USER_MASTER = 0;
} else {
    $PK_USER_MASTER = $_GET['PK_USER_MASTER'];
}

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

/*$row = $db_account->Execute("SELECT * FROM DOA_APPOINTMENT_MASTER WHERE DATE = '".date('Y-m-d', strtotime($date))."' AND '".date('H:i:s', strtotime($time))."' >= START_TIME AND '".date('H:i:s', strtotime($time))."' <= END_TIME");
$selected_service_provider = [];
while (!$row->EOF) {
    $selected_service_provider[] = $row->fields['SERVICE_PROVIDER_ID'];
    $row->MoveNext();
}
$selected_service_provider_array = implode(',', $selected_service_provider);

if ($row->RecordCount() > 0) {
    $AND_PK_USER = "AND NOT DOA_USERS.PK_USER IN (".$selected_service_provider_array.")";
} else {
    $AND_PK_USER = '';
}*/

$AND_PK_USER = '';
?>

<form id="multi_appointment_form" method="post" action="">
    <input type="hidden" name="FUNCTION_NAME" value="saveMultiAppointmentData">
    <input type="hidden" name="IS_SUBMIT" id="IS_SUBMIT" value="0">
    <div class="p-40" style="padding-top: 10px;">
        <div class="row">
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label">Customer<span class="text-danger">*</span></label><br>
                    <select class="multi_select" required name="CUSTOMER_ID[]" id="SELECT_CUSTOMER" onchange="selectThisCustomer(this);">
                        <option value="">Select Customer</option>
                        <?php
                        $row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND DOA_USERS.ACTIVE = 1 ORDER BY DOA_USERS.FIRST_NAME");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_USER_MASTER'];?>" <?=($PK_USER_MASTER == $row->fields['PK_USER_MASTER'])?'selected':''?>><?=$row->fields['NAME'].' ('.$row->fields['USER_NAME'].')'.' ('.$row->fields['PHONE'].')'?></option>
                        <?php $row->MoveNext(); } ?>
                    </select>
                </div>
            </div>
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label">Enrollment ID<span class="text-danger">*</span></label>
                    <select class="form-control" required name="PK_ENROLLMENT_MASTER" id="PK_ENROLLMENT_MASTER" onchange="getDuration()">
                        <option value="">Select Enrollment ID</option>
                    </select>
                </div>
            </div>
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label">Scheduling Code<span class="text-danger">*</span></label><br>
                    <select class="multi_select" required id="PK_SCHEDULING_CODE" name="PK_SCHEDULING_CODE" onchange="getDuration()">
                        <option value="">Select Scheduling Code</option>
                        <?php
                        $row = $db_account->Execute("SELECT `PK_SCHEDULING_CODE`, `SCHEDULING_CODE`, `SCHEDULING_NAME`, `DURATION` FROM `DOA_SCHEDULING_CODE` WHERE `ACTIVE` = 1");
                        while (!$row->EOF) { ?>
                            <option data-duration="<?=$row->fields['DURATION'];?>" value="<?=$row->fields['PK_SCHEDULING_CODE']?>"><?=$row->fields['SCHEDULING_CODE'].' ('.$row->fields['SCHEDULING_CODE'].')'?></option>
                            <?php $row->MoveNext(); } ?>
                    </select>
                </div>
            </div>
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label"><?=$service_provider_title?><span class="text-danger">*</span></label>
                    <select required name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID">
                        <option value="">Select <?=$service_provider_title?></option>
                    <?php
                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 5 ".$AND_PK_USER." AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY NAME");

                    while (!$row->EOF) { ?>
                        <option value="<?php echo $row->fields['PK_USER'];?>" <?=($PK_USER == $row->fields['PK_USER'])?"selected":""?>><?=$row->fields['NAME']?></option>
                        <?php $row->MoveNext(); } ?>
                    </select>
                </div>
            </div>
        </div>

        <input type="hidden" id="DURATION" name="DURATION">
        <input type="hidden" id="NUMBER_OF_SESSION" name="NUMBER_OF_SESSION">
        <div class="row">
            <div class="col-2">
                <div class="form-group">
                    <label class="form-label">Starting On<span class="text-danger">*</span></label><br>
                    <input class="form-control datepicker-normal" type="text" name="STARTING_ON" value="<?=$date?>" required>
                </div>
            </div>
            <div class="col-2">
                <div class="form-group">
                    <label class="form-label">Time<span class="text-danger">*</span></label><br>
                    <input class="form-control timepicker-normal" type="text" name="START_TIME" value="<?=$time?>" required>
                </div>
            </div>
            <div class="col-2">
                <div class="form-group">
                    <label class="form-label">Select Days</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS[]" value="monday"> Monday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS[]" value="tuesday"> Tuesday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS[]" value="wednesday"> Wednesday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS[]" value="thursday"> Thursday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS[]" value="friday"> Friday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS[]" value="saturday"> Saturday</label><br>
                    <label><input type="checkbox" class="DAYS" name="DAYS[]" value="sunday"> Sunday</label><br>
                </div>
            </div>
            <div class="col-2 occurrence_div">
                <div class="form-group">
                    <label class="form-label">Select Occurrence<span class="text-danger">*</span></label><br>
                    <label><input type="radio" name="OCCURRENCE" value="WEEKLY" required> Weekly</label><br>
                    <label><input type="radio" name="OCCURRENCE" value="DAYS" required> Every <input type="text" name="OCCURRENCE_DAYS" style="width: 45px;"> Days</label>
                </div>
            </div>
            <div class="col-2">
                <div class="form-group">
                    <label class="form-label">Length<span class="text-danger">*</span></label><br>
                    <input type="number" class="form-control" name="LENGTH" style="width: 80px;" required>
                    <select class="form-control" name="FREQUENCY" style="width: 100px;" required>
                        <option value="week">Week(S)</option>
                        <option value="month">Month(S)</option>
                        <option value="year">Year(S)</option>
                    </select>
                </div>
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
    $(document).ready(function () {
        $('#SELECT_CUSTOMER').trigger("change");
    });

    $('.multi_select').SumoSelect({search: true, searchText: 'Search...'});

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $('.timepicker-normal').timepicker({
        timeFormat: 'hh:mm p',
    });

    $('.DAYS').on('change', function(){
       if ($('.DAYS').is(':checked')){
           $("input[name='OCCURRENCE'][value='WEEKLY']").prop('checked', true);
           $('.occurrence_div').addClass('disable-div');
       } else {
           $("input[name='OCCURRENCE'][value='WEEKLY']").prop('checked', false);
           $('.occurrence_div').removeClass('disable-div');
       }
    });

    $('#SERVICE_PROVIDER_ID').SumoSelect({placeholder: 'Select <?=$service_provider_title?>', search: true, searchText: 'Search...'});

    function set_time(id, start_time, end_time){
        $('#START_TIME').val(start_time);
        $('#END_TIME').val(end_time);
        let slot_btn  = $(".slot_btn");
        slot_btn.each(function (index) {
            if ($(this).data('is_disable') == 0) {
                $(this).css('background-color', 'greenyellow');
            }
        })
        document.getElementById('slot_btn_'+id).style.setProperty('background-color', 'orange', 'important');
    }

    $(document).on('click', '#cancel_button', function () {
        window.location.href='all_schedules.php'
    });

    function selectThisCustomer(param) {
        let PK_USER_MASTER = $(param).val();
        $.ajax({
            url: "ajax/get_enrollments.php",
            type: "POST",
            data: {PK_USER_MASTER: PK_USER_MASTER},
            async: false,
            cache: false,
            success: function (result) {
                $('#PK_ENROLLMENT_MASTER').empty();
                $('#PK_ENROLLMENT_MASTER').append(result);
            }
        });
    }

    function getDuration() {
        let no_of_session = $('#PK_ENROLLMENT_MASTER').find(':selected').data('no_of_session');
        $('#NUMBER_OF_SESSION').val(no_of_session);
        let duration = $('#PK_SCHEDULING_CODE').find(':selected').data('duration');
        $('#DURATION').val(duration);
    }

    $(document).on('submit', '#multi_appointment_form', function (event) {
        event.preventDefault();
        let form_data = $('#multi_appointment_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            success:function (data) {
                if (data > 0) {
                    let conf = confirm(`According to the number of classes in the enrollment, ${data} appointments were generated.`);
                    if(conf) {
                        submitAppointmentForm();
                    }
                } else {
                    submitAppointmentForm();
                }
                // window.location.href='all_schedules.php';
            }
        });
    });

    function submitAppointmentForm() {
        $('#IS_SUBMIT').val(1);
        let form_data = $('#multi_appointment_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            success:function (data) {
                window.location.href='all_schedules.php';
            }
        });
    }
</script>
