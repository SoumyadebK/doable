<?php
require_once('../../global/config.php');

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

if (empty($_GET['PK_USER_MASTER'])) {
    $PK_USER_MASTER = 0;
} else {
    $PK_USER_MASTER = $_GET['PK_USER_MASTER'];
}

?>
<form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="FUNCTION_NAME" value="saveSpecialAppointment">
    <div class="row to_dos_class_setting">
        <div class="col-12">
            <div class="row">
                <div class="col-6">
                    <label class="form-label"><?=$service_provider_title?></label>
                    <div class="col-md-12" style="margin-bottom: 15px; margin-top: 10px;">
                        <select class="multi_sumo_select" name="PK_USER[]" multiple>
                            <?php
                            $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES IN(5) AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY NAME");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_USER'];?>" <?=($PK_USER == $row->fields['PK_USER'])?"selected":""?>><?=$row->fields['NAME']?></option>
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
                                <option value="<?php echo $booking_row->fields['PK_SCHEDULING_CODE'];?>" data-duration="<?php echo $booking_row->fields['DURATION'];?>" data-scheduling_name="<?php echo $booking_row->fields['SCHEDULING_NAME']?>" data-is_default="<?php echo $booking_row->fields['IS_DEFAULT']?>" <?=in_array($booking_row->fields['PK_SCHEDULING_CODE'], $selected_booking_code)?"selected":""?>><?=$booking_row->fields['SCHEDULING_NAME']?></option>
                                <?php $booking_row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" id="TITLE" name="TITLE" class="form-control" required>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="text" id="DATE" name="DATE" class="form-control datepicker-normal" value="<?=$date?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker" onchange="calculateEndTime(this)" value="<?=$time?>" required>
                    </div>
                </div>
                <div class="col-6 END_TIME">
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="text" id="END_TIME" name="END_TIME" class="form-control time-picker" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="DESCRIPTION" name="DESCRIPTION" rows="3"></textarea>
                    </div>
                </div>
                <div class="col-6 customer_div" style="display: none">
                    <div>
                        <label class="form-label">Customer</label><br>
                        <select class="multi_sumo_select" name="CUSTOMER_ID[]" id="PK_USER_MASTER" onchange="selectThisCustomer(this);" multiple>
                            <?php
                            $row = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER, DOA_USER_MASTER.PRIMARY_LOCATION_ID FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY DOA_USERS.FIRST_NAME ASC");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_USER_MASTER'];?>" data-customer_id="<?=$row->fields['PK_USER_MASTER']?>" data-location_id="<?=$row->fields['PRIMARY_LOCATION_ID']?>" data-customer_name="<?=$row->fields['NAME']?>" <?=($PK_USER_MASTER == $row->fields['PK_USER_MASTER'])?'selected':''?>><?=$row->fields['NAME'].' ('.$row->fields['USER_NAME'].')'.' ('.$row->fields['PHONE'].')'.' ('.$row->fields['EMAIL_ID'].')'?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-2">
                <label class="col-md-12 mt-3"><input type="checkbox" id="IS_STANDING" name="IS_STANDING" class="form-check-inline" value="1"> Is Standing ?</label>
            </div>

            <div class="row standing" style="display: none;  margin-top: 10px;">
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
                        <label><input type="radio" class="is_required" name="OCCURRENCE" value="WEEKLY"> Weekly</label><br>
                        <label><input type="radio" class="is_required" name="OCCURRENCE" value="DAYS"> Every <input type="text" name="OCCURRENCE_DAYS" style="width: 45px;"> Days</label>
                    </div>
                </div>
                <div class="col-2">
                    <div class="form-group">
                        <label class="form-label">Length<span class="text-danger">*</span></label><br>
                        <input type="number" class="form-control is_required" name="LENGTH" style="width: 80px;">
                        <select class="form-control is_required" name="FREQUENCY" style="width: 100px;">
                            <option value="week">Week(S)</option>
                            <option value="month">Month(S)</option>
                            <option value="year">Year(S)</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="margin-top: 15px;">Submit</button>
</form>

<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

<script type="text/javascript">
    $('#PK_USER_MASTER').SumoSelect({placeholder: 'Select Customer', selectAll: true, search: true, searchText: 'Search...'});

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $('#START_TIME').timepicker({
        timeFormat: 'hh:mm p',
        change: function () {
            calculateEndTime();
        },
    });

    $('#END_TIME').timepicker({
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

    $('.multi_sumo_select').SumoSelect({placeholder: 'Select <?=$service_provider_title?>', selectAll: true});
    $('.PK_SCHEDULING_CODE').SumoSelect({placeholder: 'Select Scheduling Code', selectAll: true});

    function calculateEndTime() {
        let start_time = $('#START_TIME').val();
        let duration = $('#PK_SCHEDULING_CODE').find(':selected').data('duration');
        let scheduling_name = $('#PK_SCHEDULING_CODE').find(':selected').data('scheduling_name');
        let is_default = $('#PK_SCHEDULING_CODE').find(':selected').data('is_default');
        $('#TITLE').val(scheduling_name);
        duration = (duration)?duration:0;

        if (start_time && duration) {
            start_time = moment(start_time, ["h:mm A"]).format("HH:mm");
            let end_time = addMinutes(start_time, duration);
            end_time = moment(end_time, ["HH:mm"]).format("h:mm A");
            $('#END_TIME').val(end_time);
        }

        if (is_default===1) {
            $('.customer_div').show();
        } else {
            $('.customer_div').hide();
        }
    }

    function addMinutes(time, minsToAdd) {
        function D(J){ return (J<10? '0':'') + J;};
        var piece = time.split(':');
        var mins = piece[0]*60 + +piece[1] + +minsToAdd;

        return D(mins%(24*60)/60 | 0) + ':' + D(mins%60);
    }

    $('#IS_STANDING').on('change', function(){
        if ($(this).is(':checked')){
            $('.is_required').prop('required', true);
            $(this).closest('.to_dos_class_setting').find('.standing').show();
            $(this).closest('.to_dos_class_setting').find('.END_TIME').hide();
        } else {
            $('.is_required').prop('required', false);
            $(this).closest('.to_dos_class_setting').find('.standing').hide();
            $(this).closest('.to_dos_class_setting').find('.END_TIME').show();
        }
    });
</script>
