<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Appointment Status";
else
    $title = "Edit Appointment Status";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $APPOINTMENT_STATUS_DATA = $_POST;
    if(empty($_GET['id'])){
        $APPOINTMENT_STATUS_DATA['ACTIVE'] = 1;
        $APPOINTMENT_STATUS_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $APPOINTMENT_STATUS_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_APPOINTMENT_STATUS', $APPOINTMENT_STATUS_DATA, 'insert');
    }else{
        $APPOINTMENT_STATUS_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $APPOINTMENT_STATUS_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $APPOINTMENT_STATUS_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_APPOINTMENT_STATUS', $APPOINTMENT_STATUS_DATA, 'update'," PK_APPOINTMENT_STATUS =  '$_GET[id]'");
    }
    header("location:all_appointment_status.php");
}

if(empty($_GET['id'])){
    $APPOINTMENT_STATUS = '';
    $STATUS_CODE = '';
    $COLOR_CODE = '';
    $ACTIVE = '';
}
else {
    $res = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_STATUS` WHERE PK_APPOINTMENT_STATUS = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_appointment_status.php");
        exit;
    }
    $APPOINTMENT_STATUS = $res->fields['APPOINTMENT_STATUS'];
    $STATUS_CODE = $res->fields['STATUS_CODE'];
    $COLOR_CODE = $res->fields['COLOR_CODE'];
    $ACTIVE = $res->fields['ACTIVE'];
}
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_appointment_status.php">All Appointment Status</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label class="col-md-12" for="example-text">Appointment Status<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-12">
                                        <input type="text" id="APPOINTMENT_STATUS" name="APPOINTMENT_STATUS" class="form-control" placeholder="Enter Appointment Status" value="<?php echo $APPOINTMENT_STATUS?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-md-12" for="example-text">Status Code<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-12">
                                        <input type="text" id="STATUS_CODE" name="STATUS_CODE" style="text-transform:uppercase" class="form-control" placeholder="Enter Status Code" value="<?php echo $STATUS_CODE?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-md-12" for="example-text">Color Code<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-3" style="height: 50px;">
                                        <input type="color" id="COLOR_CODE" name="COLOR_CODE" class="form-control" value="<?php echo $COLOR_CODE?>">
                                    </div>
                                </div>

                                <?php if(!empty($_GET['id'])) { ?>
                                    <div class="row" style="margin-bottom: 15px;">
                                        <div class="col-6">
                                            <div class="col-md-2">
                                                <label>Active</label>
                                            </div>
                                            <div class="col-md-4">
                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                            </div>
                                        </div>
                                    </div>
                                <? } ?>

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_appointment_status.php'">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>
</body>
</html>