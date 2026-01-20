<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Event Type";
else
    $title = "Edit Event Type";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $EVENT_TYPE_DATA = $_POST;
    $EVENT_TYPE_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if(empty($_GET['id'])){
        $EVENT_TYPE_DATA['ACTIVE'] = 1;
        $EVENT_TYPE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $EVENT_TYPE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_EVENT_TYPE', $EVENT_TYPE_DATA, 'insert');
    }else{
        $EVENT_TYPE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $EVENT_TYPE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $EVENT_TYPE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_EVENT_TYPE', $EVENT_TYPE_DATA, 'update'," PK_EVENT_TYPE =  '$_GET[id]'");
    }
    header("location:all_event_types.php");
}

if(empty($_GET['id'])){
    $EVENT_TYPE = '';
    $COLOR_CODE = '';
    $ACTIVE = '';
}
else {
    $res = $db_account->Execute("SELECT * FROM `DOA_EVENT_TYPE` WHERE PK_EVENT_TYPE = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_event_types.php");
        exit;
    }
    $EVENT_TYPE = $res->fields['EVENT_TYPE'];
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
                            <li class="breadcrumb-item"><a href="all_events.php">All Event</a></li>
                            <li class="breadcrumb-item"><a href="all_event_types.php">All Event Type</a></li>
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
                                    <label class="col-md-12" for="example-text">Event Type<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-12">
                                        <input type="text" id="EVENT_TYPE" name="EVENT_TYPE" class="form-control" placeholder="Enter Event Type" value="<?php echo $EVENT_TYPE?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-md-12" for="example-text">Color Code<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-3">
                                        <input type="color" id="COLOR_CODE" name="COLOR_CODE" value="<?php echo $COLOR_CODE?>" style="margin: 10px; width: 150px;">
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
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_event_types.php'">Cancel</button>
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