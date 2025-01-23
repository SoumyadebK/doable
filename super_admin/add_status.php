<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Order Status";
else
    $title = "Edit Order Status";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $STATUS_DATA = $_POST;
    if(empty($_GET['id'])){
        $STATUS_DATA['ACTIVE'] = 1;
        $STATUS_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $STATUS_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_ORDER_STATUS', $STATUS_DATA, 'insert');
    }else{
        $STATUS_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $STATUS_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $STATUS_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_ORDER_STATUS', $STATUS_DATA, 'update'," PK_ORDER_STATUS =  '$_GET[id]'");
    }
    header("location:all_status.php");
}

if(empty($_GET['id'])){
    $STATUS = '';
    $STATUS_CODE = '';
    $COLOR_CODE = '';
    $ACTIVE = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_ORDER_STATUS` WHERE PK_STATUS = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_status.php");
        exit;
    }
    $STATUS = $res->fields['STATUS'];
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
                            <li class="breadcrumb-item"><a href="all_status.php">All Order Status</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-material form-horizontal m-t-30" name="form1" id="form1" action="" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label>Status<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="STATUS" name="STATUS" placeholder="Enter Status" value="<?php echo $STATUS ?>" required>
                                        <div class="invalid-feedback">
                                            Enter Status
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Status Code</label>
                                        <input type="text" class="form-control" id="STATUS_CODE" name="STATUS_CODE" placeholder="Enter Status Code" value="<?php echo $STATUS_CODE ?>" required>
                                        <div class="invalid-feedback">
                                            Enter Status Code
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="example-text">Color Code<span class="text-danger">*</span>
                                        </label>
                                        <div>
                                            <input type="color" id="COLOR_CODE" name="COLOR_CODE" value="<?php echo $COLOR_CODE?>" style="margin: 10px; width: 200px;">
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
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_status.php'">Cancel</button>
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