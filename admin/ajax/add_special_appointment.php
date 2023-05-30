<?php
require_once('../../global/config.php');
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
                        <input type="text" id="DATE" name="DATE" class="form-control datepicker-normal" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker" required>
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
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="DESCRIPTION" name="DESCRIPTION" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <label class="form-label">Service Provider</label>
                    <div class="col-md-12" style="margin-bottom: 15px; margin-top: 10px;">
                        <select class="multi_sumo_select" name="PK_USER[]" multiple>
                            <?php
                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_USER'];?>"><?=$row->fields['NAME']?></option>
                            <?php $row->MoveNext(); } ?>
                        </select>
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

    $('.multi_sumo_select').SumoSelect({placeholder: 'Select Location', selectAll: true});
</script>
