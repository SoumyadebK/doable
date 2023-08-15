<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Inquiry Method";
else
    $title = "Edit Inquiry Method";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $LOCATION_DATA = $_POST;
    $LOCATION_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];

    if(empty($_GET['id'])){
        $LOCATION_DATA['ACTIVE'] = 1;
        $LOCATION_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $LOCATION_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_INQUIRY_METHOD', $LOCATION_DATA, 'insert');
    }else{
        $LOCATION_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $LOCATION_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $LOCATION_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_INQUIRY_METHOD', $LOCATION_DATA, 'update'," PK_INQUIRY_METHOD =  '$_GET[id]'");
    }
    header("location:all_inquiry_methods.php");
}



if(empty($_GET['id'])){
    $INQUIRY_METHOD = '';
    $ACTIVE = '';
} else {
    $res = $db_account->Execute("SELECT * FROM `DOA_INQUIRY_METHOD` WHERE `PK_INQUIRY_METHOD` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_inquiry_methods.php");
        exit;
    }

    $INQUIRY_METHOD = $res->fields['INQUIRY_METHOD'];
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
        <div class="container-fluid" style="margin-top: 67px">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_inquiry_methods.php">All Inquiry Method</a></li>
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
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Inquiry Method<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="INQUIRY_METHOD" name="INQUIRY_METHOD" class="form-control" placeholder="Enter Inquiry Method" required value="<?php echo $INQUIRY_METHOD?>">
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

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_inquiry_methods.php'">Cancel</button>
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