<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Enrollment Type";
else
    $title = "Edit Enrollment Type";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}


if(!empty($_POST)){
    $ENROLLMENT_TYPE_DATA = $_POST;
    if(empty($_GET['id'])){
        $ENROLLMENT_TYPE_DATA['ACTIVE'] = 1;
        $ENROLLMENT_TYPE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $ENROLLMENT_TYPE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_ENROLLMENT_TYPE', $ENROLLMENT_TYPE_DATA, 'insert');
    }else{
        $ENROLLMENT_TYPE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $ENROLLMENT_TYPE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $ENROLLMENT_TYPE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_ENROLLMENT_TYPE', $ENROLLMENT_TYPE_DATA, 'update'," PK_ENROLLMENT_TYPE =  '$_GET[id]'");
    }
    header("location:all_enrollment_types.php");
}

if(empty($_GET['id'])){
    $ENROLLMENT_TYPE = '';
    $ACTIVE = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_TYPE` WHERE PK_ENROLLMENT_TYPE = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_enrollment_types.php");
        exit;
    }
    $ENROLLMENT_TYPE = $res->fields['ENROLLMENT_TYPE'];
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
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_enrollment_types.php">All Enrollment Types</a></li>
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
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Enrollment Type<span class="text-danger">*</span></label>
                                            <div class="col-md-12">
                                                <input type="text" id="ENROLLMENT_TYPE" name="ENROLLMENT_TYPE" class="form-control" placeholder="Enter Enrollment Type" required value="<?php echo $ENROLLMENT_TYPE?>">
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
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_enrollment_types.php'">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php');?>
</body>
</html>