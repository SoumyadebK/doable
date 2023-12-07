<?php
require_once('../../global/config.php');

if (!empty($_GET['date']) && !empty($_GET['time'])) {
    $date = $_GET['date'];
    $time = $_GET['time'];
} else {
    $date = '';
    $time = '';
}

if (!empty($_GET['id'])) {
    $PK_USER = $_GET['id'];
} else {
    $PK_USER = '';
}

?>
<form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="FUNCTION_NAME" value="saveSpecialAppointment">
    <div class="row">
        <div class="col-12">

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
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="text" id="END_TIME" name="END_TIME" class="form-control time-picker" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <label class="form-label"><?=$service_provider_title?></label>
                    <div class="col-md-12" style="margin-bottom: 15px; margin-top: 10px;">
                        <select class="multi_sumo_select" name="PK_USER[]" multiple>
                            <?php
                            $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES IN(5) AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_USER'];?>" <?=($PK_USER == $row->fields['PK_USER'])?"selected":""?>><?=$row->fields['NAME']?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label">Scheduling Code</label>
                    <div class="col-md-12" style="margin-bottom: 15px; margin-top: 10px;">
                        <select class="PK_SCHEDULING_CODE" name="PK_SCHEDULING_CODE" onchange="calculateEndTime(this)">
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
                            $booking_row = $db_account->Execute("SELECT PK_SCHEDULING_CODE, SCHEDULING_NAME, DURATION FROM DOA_SCHEDULING_CODE WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                            while (!$booking_row->EOF) { ?>
                                <option value="<?php echo $booking_row->fields['PK_SCHEDULING_CODE'];?>" data-duration="<?php echo $booking_row->fields['DURATION'];?>" <?=in_array($booking_row->fields['PK_SCHEDULING_CODE'], $selected_booking_code)?"selected":""?>><?=$booking_row->fields['SCHEDULING_NAME']?></option>
                                <?php $booking_row->MoveNext(); } ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="DESCRIPTION" name="DESCRIPTION" rows="3"></textarea>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
</form>

<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

<script type="text/javascript">
    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $('.time-picker').timepicker({
        timeFormat: 'hh:mm p',
    });

    $('.multi_sumo_select').SumoSelect({placeholder: 'Select <?=$service_provider_title?>', selectAll: true});
    $('.PK_SCHEDULING_CODE').SumoSelect({placeholder: 'Select Scheduling Code', selectAll: true});

    function calculateEndTime(param) {
        let start_time = $('#START_TIME').val();

        let duration = $(param).find(':selected').data('duration');
        duration = (duration)?duration:0;

        let timeParts = start_time.split(":");
        let minutes =  Number(timeParts[0]) * 60 + Number(timeParts[1]);
        alert(minutes)
        let end_time = minutes+duration;

        $('#END_TIME').val(end_time);

    }

</script>
