<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Special Appointment";
else
    $title = "Edit Special Appointment";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $SPECIAL_APPOINTMENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
    $SPECIAL_APPOINTMENT_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
    $SPECIAL_APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
    $SPECIAL_APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
    $SPECIAL_APPOINTMENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];


    if(empty($_GET['id'])){
        $SPECIAL_APPOINTMENT_DATA['ACTIVE'] = 1;
        $SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
        $SPECIAL_APPOINTMENT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $SPECIAL_APPOINTMENT_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'insert');
        $PK_SPECIAL_APPOINTMENT = $db->insert_ID();
    }else{
        //$SPECIAL_APPOINTMENT_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
        $SPECIAL_APPOINTMENT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $SPECIAL_APPOINTMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'update'," PK_SPECIAL_APPOINTMENT =  '$_GET[id]'");
        $PK_SPECIAL_APPOINTMENT = $_GET['id'];
    }

    if (isset($_POST['PK_USER'])) {
        $db->Execute("DELETE FROM `DOA_SPECIAL_APPOINTMENT_USER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
        for ($i = 0; $i < count($_POST['PK_USER']); $i++) {
            $SPECIAL_APPOINTMENT_USER['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT;
            $SPECIAL_APPOINTMENT_USER['PK_USER'] = $_POST['PK_USER'][$i];
            db_perform('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'insert');
        }
    }

    header("location:all_special_appointment.php");
}



if(empty($_GET['id'])){
    $PK_SPECIAL_APPOINTMENT = '';
    $TITLE = '';
    $DATE = '';
    $START_TIME = '';
    $END_TIME = '';
    $DESCRIPTION = '';
    $PK_APPOINTMENT_STATUS = '';
    $ACTIVE = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_SPECIAL_APPOINTMENT` WHERE `PK_SPECIAL_APPOINTMENT` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_special_appointment.php");
        exit;
    }

    $PK_SPECIAL_APPOINTMENT = $res->fields['PK_SPECIAL_APPOINTMENT'];
    $TITLE = $res->fields['TITLE'];
    $DATE = $res->fields['DATE'];
    $START_TIME = $res->fields['START_TIME'];
    $END_TIME = $res->fields['END_TIME'];
    $DESCRIPTION = $res->fields['DESCRIPTION'];
    $PK_APPOINTMENT_STATUS = $res->fields['PK_APPOINTMENT_STATUS'];
    $ACTIVE = $res->fields['ACTIVE'];
}

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    .ck-editor__editable_inline {
        min-height: 300px;
    }
    .SumoSelect {
        width: 100%;
    }
</style>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="all_special_appointment.php">All Special Appointment</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card p-20">
                        <div class="card-body">
                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-12">

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Title</label>
                                                    <input type="text" id="TITLE" name="TITLE" class="form-control" required value="<?php echo $TITLE?>">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Date</label>
                                                    <input type="text" id="DATE" name="DATE" class="form-control datepicker-normal" required value="<?php echo ($DATE)?date('m/d/Y', strtotime($DATE)):''?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Start Time</label>
                                                    <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker" required value="<?php echo ($START_TIME)?date('h:i A', strtotime($START_TIME)):''?>">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">End Time</label>
                                                    <input type="text" id="END_TIME" name="END_TIME" class="form-control time-picker" required value="<?php echo ($END_TIME)?date('h:i A', strtotime($END_TIME)):''?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="form-control" id="DESCRIPTION" name="DESCRIPTION" rows="3"><?=$DESCRIPTION?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label">Service Provider</label>
                                                <div class="col-md-12" style="margin-bottom: 15px; margin-top: 10px;">
                                                    <select class="multi_sumo_select" name="PK_USER[]" multiple>
                                                        <?php
                                                        $selected_service_provider = [];
                                                        if(!empty($_GET['id'])) {
                                                            $selected_service_provider_row = $db->Execute("SELECT `PK_USER` FROM `DOA_SPECIAL_APPOINTMENT_USER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
                                                            while (!$selected_service_provider_row->EOF) {
                                                                $selected_service_provider[] = $selected_service_provider_row->fields['PK_USER'];
                                                                $selected_service_provider_row->MoveNext();
                                                            }
                                                        }
                                                        $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS WHERE PK_ROLES = 5 AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_USER'];?>" <?=in_array($row->fields['PK_USER'], $selected_service_provider)?"selected":""?>><?=$row->fields['NAME']?></option>
                                                        <?php $row->MoveNext(); } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <?php if(!empty($_GET['id'])) { ?>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Status:</label>
                                                        <select class="form-control" name="PK_APPOINTMENT_STATUS" id="PK_APPOINTMENT_STATUS">
                                                            <option value="">Select Status</option>
                                                            <?php
                                                            $selected_status = '';
                                                            $row = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_STATUS` WHERE `ACTIVE` = 1");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS'];?>" <?=($PK_APPOINTMENT_STATUS==$row->fields['PK_APPOINTMENT_STATUS'])?'selected':''?>><?=$row->fields['APPOINTMENT_STATUS']?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                        </select>
                                                        <p id="appointment_status"><?=$selected_status?></p>
                                                    </div>
                                                </div>
                                            <? } ?>
                                        </div>
                                    </div>

                                </div>

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_special_appointment.php'">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>
<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

</body>
<script>
    $('.datepicker-normal').datepicker({
        changeMonth: true,
        changeYear: true,
        format: 'mm/dd/yyyy',
    });

    $('.time-picker').timepicker({
        timeFormat: 'hh:mm p',
    });

    $('.multi_sumo_select').SumoSelect({placeholder: 'Select Location', selectAll: true});
</script>
</html>