<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Service";
else
    $title = "Edit Service";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $SERVICE_DATA = $_POST;
    $SERVICE_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if(empty($_GET['id'])){
        $SERVICE_DATA['ACTIVE'] = 1;
        $SERVICE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $SERVICE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_SERVICE_MASTER', $SERVICE_DATA, 'insert');
    }else{
        $SERVICE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $SERVICE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $SERVICE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_SERVICE_MASTER', $SERVICE_DATA, 'update'," PK_SERVICE_MASTER =  '$_GET[id]'");
    }
    header("location:all_services.php");
}

if(empty($_GET['id'])){
    $SERVICE_NAME = '';
    $DESCRIPTION = '';
    $DURATION = '';
    $PRICE = '';
    $ACTIVE = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_SERVICE_MASTER` WHERE `PK_SERVICE_MASTER` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_services.php");
        exit;
    }

    $SERVICE_NAME = $res->fields['SERVICE_NAME'];
    $DESCRIPTION = $res->fields['DESCRIPTION'];
    $DURATION = $res->fields['DURATION'];
    $PRICE = $res->fields['PRICE'];
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
    <?php require_once('../includes/left_menu.php') ?>
    <div class="page-wrapper">
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <form class="form-material form-horizontal m-t-30" name="form1" id="form1" action="" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="col-md-12" for="example-text">Service Name<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-12">
                                        <input type="text" id="SERVICE_NAME" name="SERVICE_NAME" class="form-control" placeholder="Enter Service name" required value="<?php echo $SERVICE_NAME?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-md-12">Description</label>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="3" id="DESCRIPTION" name="DESCRIPTION"><?php echo $DESCRIPTION?></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Duration<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="DURATION" name="DURATION" class="form-control" placeholder="Enter Duration" required value="<?php echo $DURATION?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Price<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12" >
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                                    <input type="number" id="PRICE" name="PRICE" class="form-control" placeholder="Enter Price" required value="<?php echo $PRICE?>">
                                                </div>
                                            </div>
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

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white ">Submit</button>
                                <button type="button" onclick="window.location.href='all_services.php'" class="btn btn-inverse waves-effect waves-light">Cancel</button>

                            </div>
                        </div>

                    </div>

                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>
</body>
</html>