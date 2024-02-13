<?php
require_once('../../global/config.php');
global $db;
global $db_account;

if (empty($_GET['PK_USER_MASTER'])) {
    $PK_USER_MASTER = 0;
} else {
    $PK_USER_MASTER = $_GET['PK_USER_MASTER'];
}

if (empty($_GET['PK_USER'])) {
    $PK_USER = 0;
} else {
    $PK_USER = $_GET['PK_USER'];
}

if (!empty($_GET['date']) && !empty($_GET['time'])) {
    $date = $_GET['date'];
    $time = $_GET['time'];
    $DATE_ARR[0] = date("Y",strtotime($date));
    $DATE_ARR[1] = date("m",strtotime($date)) -1;
    $DATE_ARR[2] = date("d",strtotime($date));
} else {
    $date = '';
    $time = '';
}

if (!empty($_GET['SERVICE_PROVIDER_ID'])) {
    $SERVICE_PROVIDER_ID = $_GET['SERVICE_PROVIDER_ID'];
} else {
    $SERVICE_PROVIDER_ID = '';
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
    $AND_PK_USER = '';
}*/

$AND_PK_USER = '';
?>


<form id="appointment_form" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="FUNCTION_NAME" value="saveAppointmentData">
    <input type="hidden" name="PK_USER" value="<?=$PK_USER?>">
    <div class="p-40" style="padding-top: 10px;">
        <div class="row">
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label">Customer<span class="text-danger">*</span></label><br>
                    <select required name="CUSTOMER_ID[]" id="SELECT_CUSTOMER" onchange="selectThisCustomer(this);">
                        <option value="">Select Customer</option>
                        <?php
                        $row = $db->Execute("SELECT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 ORDER BY DOA_USERS.FIRST_NAME");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_USER_MASTER'];?>"  <?=($PK_USER_MASTER == $row->fields['PK_USER_MASTER'])?'selected':''?>><?=$row->fields['NAME'].' ('.$row->fields['USER_NAME'].')'.' ('.$row->fields['PHONE'].')'?></option>
                        <?php $row->MoveNext(); } ?>
                    </select>
                </div>
            </div>
            <div class="col-5">
                <div class="form-group">
                    <label class="form-label">Enrollment ID<span class="text-danger">*</span></label>
                    <select class="form-control" required name="PK_ENROLLMENT_MASTER" id="PK_ENROLLMENT_MASTER" onchange="selectThisEnrollment(this);">
                        <option value="">Select Enrollment ID</option>
                    </select>
                </div>
            </div>
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label"><?=$service_provider_title?><span class="text-danger">*</span></label>
                    <select required name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID" onchange="getSlots()">
                        <option value="">Select <?=$service_provider_title?></option>
                        <?php
                        $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 5 ".$AND_PK_USER." AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY NAME");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_USER'];?>" <?=($SERVICE_PROVIDER_ID == $row->fields['PK_USER'])?"selected":""?>><?=$row->fields['NAME']?></option>
                            <?php $row->MoveNext(); } ?>
                    </select>
                </div>
            </div>
        </div>

        <input type="hidden" name="DATE" id="DATE">
        <input type="hidden" name="START_TIME" id="START_TIME">
        <input type="hidden" name="END_TIME" id="END_TIME">

        <div class="row" id="schedule_div" style="display: <?=empty($_GET['id'])?'':'none'?>;">
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
        </div>



        <?php if ($time_zone == 1){ ?>
            <div class="form-group" style="margin-top: 25px;">
                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">SAVE</button>
                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                <?php if(!empty($_GET['id'])) { ?>
                    <a href="enrollment.php?customer_id=<?=$selected_customer_id;?>" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">Enroll</a>
                    <a href="customer.php?id=<?=$selected_user_id?>&master_id=<?=$selected_customer_id?>&tab=billing" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">Pay</a>
                    <a href="customer.php?id=<?=$selected_user_id?>&master_id=<?=$selected_customer_id?>&tab=appointment" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">View Appointment</a>
                <?php } ?>

            </div>
        <?php } else { ?>
            <div class="alert alert-danger mt-2">
                <strong>Warning!</strong> Please go to your Business Profile or Location Profile to set the timezone first.
            </div>
        <?php } ?>
    </div>
</form>

<script src="../assets/CalendarPicker/CalendarPicker.js"></script>
<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

<script type="text/javascript">
    $('#SELECT_CUSTOMER').SumoSelect({placeholder: 'Select Customer', search: true, searchText: 'Search...'});
    $('#SERVICE_PROVIDER_ID').SumoSelect({placeholder: 'Select <?=$service_provider_title?>', search: true, searchText: 'Search...'});

    $(document).ready(function () {
        getSlots();
    });
    <? if(!empty($DATE_ARR)) { ?>
    def_date = new Date(<?=$DATE_ARR[0]?>,<?=$DATE_ARR[1]?>,<?=$DATE_ARR[2]?>);
    <? } ?>
    myCalender = new CalendarPicker('#myCalendarWrapper', {
        // If max < min or min > max then the only available day will be today.
        default_date: def_date,
        //min: new Date(),
        max: new Date(nextYear, month), // NOTE: new Date(nextYear, 10) is "Sun Nov 01 <nextYear>"
        disabled_days: []
    });

    $('#previous-month').on('click', function (event) {
        event.preventDefault();
    });

    $('#next-month').on('click', function (event) {
        event.preventDefault();
    });

    myCalender.onValueChange((currentValue) => {
        getSlots();
    });

    function set_time(param, id, start_time, end_time, PK_APPOINTMENT_MASTER){
        if ($(param).data('is_selected') == 0) {
            start_time_array.push(start_time);
            end_time_array.push(end_time);
            $('#START_TIME').val(start_time_array);
            $('#END_TIME').val(end_time_array);
            $('#slot_btn_' + id).data('is_selected', 1);
            document.getElementById('slot_btn_' + id).style.setProperty('background-color', 'orange', 'important');
        } else {
            const start_time_index = start_time_array.indexOf(start_time);
            if (start_time_index > -1) {
                start_time_array.splice(start_time_index, 1);
            }

            const end_time_index = end_time_array.indexOf(end_time);
            if (end_time_index > -1) {
                end_time_array.splice(end_time_index, 1);
            }

            $('#START_TIME').val(start_time_array);
            $('#END_TIME').val(end_time_array);
            $('#slot_btn_' + id).data('is_selected', 0);
            document.getElementById('slot_btn_' + id).style.setProperty('background-color', 'greenyellow', 'important');
        }

        if (PK_APPOINTMENT_MASTER > 0) {
            let slot_btn = $(".slot_btn");
            slot_btn.each(function (index) {
                if ($(param).data('is_disable') == 0) {
                    $(param).css('background-color', 'greenyellow');
                }
            })
        }
    }

    $(document).on('click', '#cancel_button', function () {
        window.location.href='all_schedules.php?view=list'
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

/*    function selectThisEnrollment(param) {
        let PK_ENROLLMENT_MASTER = $(param).val();
        $.ajax({
            url: "ajax/get_service_provider.php",
            type: "POST",
            data: {PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER},
            async: false,
            cache: false,
            success: function (result) {
                $('#SERVICE_PROVIDER_ID').empty();
                $('#SERVICE_PROVIDER_ID').append(result);
                $('#SERVICE_PROVIDER_ID')[0].sumo.reload();
            }
        });
    }*/

    function getSlots(){
        let PK_ENROLLMENT_MASTER = $('#PK_ENROLLMENT_MASTER').val();
        /*let PK_SERVICE_MASTER = $('#PK_SERVICE_MASTER').val();
        let PK_SERVICE_CODE = $('#PK_SERVICE_CODE').val();*/
        let SERVICE_PROVIDER_ID = $('#SERVICE_PROVIDER_ID').val();
        let duration = $('#PK_ENROLLMENT_MASTER').find(':selected').data('duration');
        let selected_date  = myCalender.value.toDateString();
        let day = (selected_date.toString().split(' ')[0]).toUpperCase();
        let month = selected_date.toString().split(' ')[1];
        if(month == 'Jan')
            month = '01'
        else if(month == 'Feb')
            month = '02'
        else if(month == 'Mar')
            month = '03'
        else if(month == 'Apr')
            month = '04'
        else if(month == 'May')
            month = '05'
        else if(month == 'Jun')
            month = '06'
        else if(month == 'Jul')
            month = '07'
        else if(month == 'Aug')
            month = '08'
        else if(month == 'Sep')
            month = '09'
        else if(month == 'Oct')
            month = '10'
        else if(month == 'Nov')
            month = '11'
        else if(month == 'Dec')
            month = '12'
        let date = selected_date.toString().split(' ')[3]+'-'+month+'-'+selected_date.toString().split(' ')[2];
        let START_TIME = '';
        let END_TIME = '';

        duration = (duration > 0) ?duration: 30;

        console.log(SERVICE_PROVIDER_ID,duration,day);

        if (SERVICE_PROVIDER_ID > 0 && duration > 0) {
            $.ajax({
                url: "ajax/get_slots.php",
                type: "POST",
                data: {
                    PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER,
                    PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                    /*PK_SERVICE_MASTER: PK_SERVICE_MASTER,
                    PK_SERVICE_CODE: PK_SERVICE_CODE,*/
                    SERVICE_PROVIDER_ID: SERVICE_PROVIDER_ID,
                    duration: duration,
                    day: day,
                    date: date,
                    START_TIME: START_TIME,
                    END_TIME: END_TIME,
                    slot_time: '<?=$time?>'
                },
                async: false,
                cache: false,
                success: function (result) {
                    $('#slot_div').html(result);
                }
            });
        }else {
            $('#slot_div').html('');
        }
    }

    $(document).on('submit', '#appointment_form', function (event) {
        //event.preventDefault();
        $('.selected_slot').trigger('click');
        let selected_date  = myCalender.value.toDateString()
        let month = selected_date.toString().split(' ')[1];
        if(month == 'Jan')
            month = '01'
        else if(month == 'Feb')
            month = '02'
        else if(month == 'Mar')
            month = '03'
        else if(month == 'Apr')
            month = '04'
        else if(month == 'May')
            month = '05'
        else if(month == 'Jun')
            month = '06'
        else if(month == 'Jul')
            month = '07'
        else if(month == 'Aug')
            month = '08'
        else if(month == 'Sep')
            month = '09'
        else if(month == 'Oct')
            month = '10'
        else if(month == 'Nov')
            month = '11'
        else if(month == 'Dec')
            month = '12'
        let date = selected_date.toString().split(' ')[3]+'-'+month+'-'+selected_date.toString().split(' ')[2];
        $('#DATE').val(date);
    });

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
            $('#NO_SHOW').attr('required', true);
        }else {
            $('#no_show_div').slideUp();
            $('#NO_SHOW').attr('required', false);
        }
    }
</script>
