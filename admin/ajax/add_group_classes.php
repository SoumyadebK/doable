<?php
require_once('../../global/config.php');
?>

<form id="appointment_form" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="FUNCTION_NAME" value="saveGroupClassData">
    <input type="hidden" name="PK_APPOINTMENT_MASTER" class="PK_APPOINTMENT_MASTER" value="<?=(empty($_GET['id']))?'':$_GET['id']?>">
    <div class="p-40" style="padding-top: 10px;">
        <div class="row">
            <div class="col-3">
                <div class="form-group">
                    <label class="form-label">Service <span class="text-danger">*</span></label><br>
                    <select required name="SERVICE_ID" id="SERVICE_ID" onchange="selectThisService(this);">
                        <option value="">Select Service</option>
                        <?php
                        $row = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_CODE.DURATION, DOA_SERVICE_CODE.CAPACITY FROM DOA_SERVICE_CODE LEFT JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_CODE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_SERVICE_MASTER.ACTIVE = 1 AND DOA_SERVICE_CODE.IS_GROUP = 1 AND DOA_SERVICE_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                        while (!$row->EOF) { ?>
                            <option value="<?=$row->fields['DURATION'].','.$row->fields['PK_SERVICE_CODE'].','.$row->fields['PK_SERVICE_MASTER'];?>"><?=$row->fields['SERVICE_NAME'].' || '.$row->fields['SERVICE_CODE'];?></option>
                        <?php $row->MoveNext(); } ?>
                    </select>
                </div>
            </div>
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
            <div class="col-3">
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

        <div class="row">
            <div class="col-2">
                <div class="form-group">
                    <label class="form-label">Starting On<span class="text-danger">*</span></label><br>
                    <input class="form-control datepicker-normal" type="text" name="STARTING_ON" required>
                </div>
            </div>
            <div class="col-2">
                <div class="form-group">
                    <label class="form-label">Time<span class="text-danger">*</span></label><br>
                    <input class="form-control timepicker-normal" type="text" name="START_TIME" required>
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
    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $('.timepicker-normal').timepicker({
        timeFormat: 'hh:mm p',
        minTime: '01:00 PM',
        maxTime: '09:00 PM'
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

    $('#SERVICE_ID').SumoSelect({placeholder: 'Select Services', search: true, searchText: 'Search...'});
    $('.SERVICE_PROVIDER_ID').SumoSelect({placeholder: 'Select <?=$service_provider_title?>', search: true, searchText: 'Search...'});

    function selectThisService(param) {
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
                $('#SERVICE_PROVIDER_ID_1')[0].sumo.reload();
                $('#SERVICE_PROVIDER_ID_2')[0].sumo.reload();
                $('#SERVICE_PROVIDER_ID_2').prop('required', false);
            }
        });
    }
</script>
