<?php
require_once('../../global/config.php');
global $db;
global $db_account;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if (empty($_GET['id'])) {
    $PK_APPOINTMENT_MASTER = 0;
} else {
    $PK_APPOINTMENT_MASTER = $_GET['id'];
}

$appointment_details = $db_account->Execute("SELECT * FROM DOA_APPOINTMENT_MASTER WHERE PK_APPOINTMENT_MASTER=" . $PK_APPOINTMENT_MASTER);
if ($appointment_details->RecordCount() == 0) {
    $PK_USER_MASTER = 0;
} else {
    $PK_USER_MASTER = $appointment_details->fields['CUSTOMER_ID'];
}

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
    $DATE_ARR[0] = date("Y", strtotime($date));
    $DATE_ARR[1] = date("m", strtotime($date)) - 1;
    $DATE_ARR[2] = date("d", strtotime($date));
} else {
    $date = '';
    $time = '';
}

if (!empty($_GET['SERVICE_PROVIDER_ID'])) {
    $SERVICE_PROVIDER_ID = $_GET['SERVICE_PROVIDER_ID'];
} else {
    $SERVICE_PROVIDER_ID = '';
}

$service_master = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.PK_SERVICE_MASTER FROM DOA_SERVICE_CODE WHERE PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND SERVICE_CODE = 'COMM'");
if ($service_master->RecordCount() == 0) {
    $PK_SERVICE_MASTER = 0;
    $PK_SERVICE_CODE = 0;
} else {
    $PK_SERVICE_MASTER = $service_master->fields['PK_SERVICE_MASTER'];
    $PK_SERVICE_CODE = $service_master->fields['PK_SERVICE_CODE'];
}

?>


<form id="appointment_form" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="FUNCTION_NAME" value="saveDemoAppointmentData">
    <input type="hidden" name="PK_USER" value="<?= $PK_USER ?>">
    <div class="p-40" style="padding-top: 10px;">
        <div class="row">
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label">Customer<span class="text-danger">*</span></label><br>
                    <select class="multi_select" required name="CUSTOMER_ID[]" id="SELECT_CUSTOMER" onchange="selectThisCustomerLocation(this);">
                        <option value="">Select Customer</option>
                        <?php
                        $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER, DOA_USER_MASTER.PRIMARY_LOCATION_ID FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 ORDER BY DOA_USERS.FIRST_NAME");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_USER_MASTER']; ?>" data-pk_user="<?= $row->fields['PK_USER'] ?>" data-location_id="<?= $row->fields['PRIMARY_LOCATION_ID'] ?>" <?= ($PK_USER_MASTER == $row->fields['PK_USER_MASTER']) ? 'selected' : '' ?>><?= $row->fields['NAME'] . ' (' . $row->fields['USER_NAME'] . ')' . ' (' . $row->fields['PHONE'] . ')' ?></option>
                        <?php $row->MoveNext();
                        } ?>
                    </select>
                </div>
            </div>
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label">Location<span class="text-danger">*</span></label>
                    <select class="form-control" required name="PK_LOCATION" id="PK_LOCATION" onchange="selectThisLocation()">
                        <option value="">Select Location</option>
                    </select>
                </div>
            </div>
            <input type="hidden" name="PK_SERVICE_MASTER" value="<?= $PK_SERVICE_MASTER ?>">
            <input type="hidden" name="PK_SERVICE_CODE" value="<?= $PK_SERVICE_CODE ?>">
            <!--<div class="col-2">
                <div class="form-group">
                    <label class="form-label">Service<span class="text-danger">*</span></label><br>
                    <select class="multi_select" required name="SERVICE_ID" onchange="selectThisService(this)">
                        <?php
                        /*                        $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_CODE LEFT JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_CODE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER JOIN DOA_SERVICE_LOCATION ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_LOCATION.PK_SERVICE_MASTER WHERE DOA_SERVICE_CODE.SERVICE_CODE = 'COMM' AND DOA_SERVICE_CODE.IS_GROUP = 0 AND DOA_SERVICE_MASTER.PK_SERVICE_CLASS != 5 AND DOA_SERVICE_LOCATION.PK_LOCATION IN (".$DEFAULT_LOCATION_ID.") AND DOA_SERVICE_MASTER.IS_DELETED = 0");
                        while (!$row->EOF) { */ ?>
                            <option value="<?php /*=$row->fields['PK_SERVICE_MASTER'].','.$row->fields['PK_SERVICE_CODE'];*/ ?>"><?php /*=$row->fields['SERVICE_NAME'].' || '.$row->fields['SERVICE_CODE']*/ ?></option>
                        <?php /*$row->MoveNext(); } */ ?>
                    </select>
                </div>
            </div>-->
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label">Scheduling Code<span class="text-danger">*</span></label><br>
                    <select class="multi_select" required id="PK_SCHEDULING_CODE" name="SCHEDULING_CODE" onchange="getSlots()">
                        <option value="">Select Scheduling Code</option>
                        <?php
                        $row = $db_account->Execute("SELECT DOA_SCHEDULING_CODE.`PK_SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_NAME`, DOA_SCHEDULING_CODE.`DURATION` FROM `DOA_SCHEDULING_CODE` LEFT JOIN DOA_SCHEDULING_SERVICE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE = DOA_SCHEDULING_SERVICE.PK_SCHEDULING_CODE WHERE DOA_SCHEDULING_CODE.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_SCHEDULING_CODE.`ACTIVE` = 1 AND DOA_SCHEDULING_SERVICE.PK_SERVICE_MASTER=" . $PK_SERVICE_MASTER . " ORDER BY CASE WHEN DOA_SCHEDULING_CODE.SORT_ORDER IS NULL THEN 1 ELSE 0 END, DOA_SCHEDULING_CODE.SORT_ORDER");
                        while (!$row->EOF) { ?>
                            <option data-duration="<?= $row->fields['DURATION']; ?>" value="<?= $row->fields['PK_SCHEDULING_CODE'] . ',' . $row->fields['DURATION'] ?>"><?= $row->fields['SCHEDULING_NAME'] . ' (' . $row->fields['SCHEDULING_CODE'] . ')' ?></option>
                        <?php $row->MoveNext();
                        } ?>
                    </select>
                </div>
            </div>
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label"><?= $service_provider_title ?><span class="text-danger">*</span></label>
                    <select class="multi_select" required name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID" onchange="getSlots()">
                        <option value="">Select <?= $service_provider_title ?></option>

                    </select>
                </div>
            </div>
        </div>

        <input type="hidden" name="DATE" id="DATE">
        <input type="hidden" name="START_TIME" id="START_TIME">
        <input type="hidden" name="END_TIME" id="END_TIME">

        <div class="row" id="schedule_div">
            <div class="col-7">
                <div id="showcase-wrapper">
                    <div id="myCalendarWrapper">
                    </div>
                </div>
            </div>

            <div class="col-5" style="background-color: #add1b1;max-height:470px;overflow-y:scroll;">
                <br />
                <div class="row" id="slot_div">

                </div>
            </div>
        </div>



        <?php if ($time_zone == 1) { ?>
            <div class="form-group" style="margin-top: 25px;">
                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">SAVE</button>
                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                <?php /*if(!empty($_GET['id'])) { */ ?><!--
                    <a href="enrollment.php?customer_id=<?php /*=$selected_customer_id;*/ ?>" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">Enroll</a>
                    <a href="customer.php?id=<?php /*=$selected_user_id*/ ?>&master_id=<?php /*=$selected_customer_id*/ ?>&tab=billing" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">Pay</a>
                    <a href="customer.php?id=<?php /*=$selected_user_id*/ ?>&master_id=<?php /*=$selected_customer_id*/ ?>&tab=appointment" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">View Appointment</a>
                --><?php /*} */ ?>

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
    $('.multi_select').SumoSelect({
        search: true,
        searchText: 'Search...'
    });

    $(document).ready(function() {
        $('#SELECT_CUSTOMER').trigger("change");
        getSlots();
    });
    <?php if (!empty($DATE_ARR)) { ?>
        def_date = new Date(<?= $DATE_ARR[0] ?>, <?= $DATE_ARR[1] ?>, <?= $DATE_ARR[2] ?>);
    <?php } ?>
    myCalender = new CalendarPicker('#myCalendarWrapper', {
        // If max < min or min > max then the only available day will be today.
        default_date: def_date,
        //min: new Date(),
        max: new Date(nextYear, month), // NOTE: new Date(nextYear, 10) is "Sun Nov 01 <nextYear>"
        disabled_days: []
    });

    $('#previous-month').on('click', function(event) {
        event.preventDefault();
    });

    $('#next-month').on('click', function(event) {
        event.preventDefault();
    });

    myCalender.onValueChange((currentValue) => {
        getSlots();
    });

    $(document).on('click', '#cancel_button', function() {
        window.location.href = 'all_schedules.php?view=list'
    });

    function selectThisCustomerLocation(param) {
        let location_id = $(param).find(':selected').data('location_id');
        let PK_USER = $(param).find(':selected').data('pk_user');
        $.ajax({
            url: "ajax/get_locations.php",
            type: "POST",
            data: {
                PK_USER: PK_USER,
                LOCATION_ID: location_id
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#PK_LOCATION').empty().append(result).val(location_id);
                selectThisLocation();
            }
        });
    }

    function selectThisLocation() {
        let location_id = $('#PK_LOCATION').val();
        $.ajax({
            url: "ajax/get_instructor.php",
            type: "POST",
            data: {
                LOCATION_ID: location_id
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#SERVICE_PROVIDER_ID').empty().append(result);
                $('#SERVICE_PROVIDER_ID')[0].sumo.reload();
                $('#SERVICE_PROVIDER_ID')[0].sumo.selectItem(SELECTED_SERVICE_PROVIDER_ID);
            }
        });
    }

    function selectThisService(param) {
        let PK_SERVICE_MASTER = $(param).val();
        $.ajax({
            url: "ajax/get_scheduling_codes.php",
            type: "POST",
            data: {
                PK_SERVICE_MASTER: PK_SERVICE_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#PK_SCHEDULING_CODE').empty();
                $('#PK_SCHEDULING_CODE').append(result);
                $('#PK_SCHEDULING_CODE')[0].sumo.reload();
            }
        });
    }

    function changeServiceProvider() {
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

    function changeStatus() {
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
        if ($(param).val() == 4) {
            $('#no_show_div').slideDown();
            $('#NO_SHOW').attr('required', true);
        } else {
            $('#no_show_div').slideUp();
            $('#NO_SHOW').attr('required', false);
        }
    }
</script>