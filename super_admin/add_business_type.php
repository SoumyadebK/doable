<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Business Type";
else
    $title = "Edit Business Type";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $BUSINESS_TYPE_DATA = $_POST;
    if(empty($_GET['id'])){
        $BUSINESS_TYPE_DATA['ACTIVE'] = 1;
        $BUSINESS_TYPE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $BUSINESS_TYPE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_BUSINESS_TYPE', $BUSINESS_TYPE_DATA, 'insert');
    }else{
        $BUSINESS_TYPE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $BUSINESS_TYPE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $BUSINESS_TYPE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_BUSINESS_TYPE', $BUSINESS_TYPE_DATA, 'update'," PK_BUSINESS_TYPE =  '$_GET[id]'");
    }
    header("location:all_business_types.php");
}

if(empty($_GET['id'])){
    $BUSINESS_TYPE = '';
    $TIME_SLOT_INTERVAL = '';
    $ACTIVE = '';
}
else {
    $res = $db->Execute("SELECT * FROM `DOA_BUSINESS_TYPE` WHERE PK_BUSINESS_TYPE = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_business_types.php");
        exit;
    }
    $BUSINESS_TYPE = $res->fields['BUSINESS_TYPE'];
    $TIME_SLOT_INTERVAL = $res->fields['TIME_SLOT_INTERVAL'];
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
                            <li class="breadcrumb-item"><a href="all_business_types.php">All Business Types</a></li>
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
                                <div class="row">
                                <div class="col-8 form-group">
                                    <label for="example-text">Business Type<span class="text-danger">*</span>
                                    </label>
                                    <div>
                                        <input type="text" id="BUSINESS_TYPE" name="BUSINESS_TYPE" class="form-control" placeholder="Enter Business Type" value="<?php echo $BUSINESS_TYPE?>">
                                    </div>
                                </div>
                                <div class="col-4 form-group">
                                    <label for="example-text">Time Slot Interval</label>
                                    <div>
                                        <input type="text" id="TIME_SLOT_INTERVAL" name="TIME_SLOT_INTERVAL" class="form-control time-picker" placeholder="Enter Time Slot Interval" value="<?php echo $TIME_SLOT_INTERVAL?>">
                                    </div>
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
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_business_types.php'">Cancel</button>
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

<script>
    $('.time-picker').timepicker({
        timeFormat: 'HH:mm:ss',
        interval: 5,
        minTime: '00',
        maxTime: '00:60:00',
        //defaultTime: '11',
        startTime: '00:00:00',
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });
</script>