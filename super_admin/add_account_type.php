<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Account Type";
else
    $title = "Edit Account Type";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $ACCOUNT_TYPE_DATA = $_POST;
    if(empty($_GET['id'])){
        $ACCOUNT_TYPE_DATA['ACTIVE'] = 1;
        $ACCOUNT_TYPE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $ACCOUNT_TYPE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_ACCOUNT_TYPE', $ACCOUNT_TYPE_DATA, 'insert');
    }else{
        $ACCOUNT_TYPE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $ACCOUNT_TYPE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $ACCOUNT_TYPE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_ACCOUNT_TYPE', $ACCOUNT_TYPE_DATA, 'update'," PK_ACCOUNT_TYPE =  '$_GET[id]'");
    }
    header("location:all_account_types.php");
}

if(empty($_GET['id'])){
    $ACCOUNT_TYPE = '';
    $SHORT_CODE = '';
    $ACTIVE = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_ACCOUNT_TYPE` WHERE PK_ACCOUNT_TYPE = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_account_types.php");
        exit;
    }
    $ACCOUNT_TYPE = $res->fields['ACCOUNT_TYPE'];
    $SHORT_CODE = $res->fields['SHORT_CODE'];
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
                            <li class="breadcrumb-item"><a href="all_account_types.php">All Account Types</a></li>
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
                                            <label class="col-md-12" for="example-text">Account type<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="ACCOUNT_TYPE" name="ACCOUNT_TYPE" class="form-control" placeholder="enter Account type" required value="<?php echo $ACCOUNT_TYPE?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Short code<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="SHORT_CODE" name="SHORT_CODE" class="form-control" placeholder="enter Short code" required value="<?php echo $SHORT_CODE?>">
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
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_account_types.php'">Cancel</button>
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