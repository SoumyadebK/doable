<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Interest";
else
    $title = "Edit Interest";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $INTEREST_DATA = $_POST;
    if(empty($_GET['id'])){
        $INTEREST_DATA['ACTIVE'] = 1;
        $INTEREST_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $INTEREST_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_INTERESTS', $INTEREST_DATA, 'insert');
    }else{
        $INTEREST_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $INTEREST_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $INTEREST_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_INTERESTS', $INTEREST_DATA, 'update'," PK_INTERESTS =  '$_GET[id]'");
    }
    header("location:all_interests.php");
}

if(empty($_GET['id'])){
    $PK_BUSINESS_TYPE	= '';
    $INTERESTS          = '';
    $ACTIVE             = '';
} else {
    $res = $db_account->Execute("SELECT * FROM DOA_INTERESTS WHERE PK_INTERESTS = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_interests.php");
        exit;
    }
    $PK_BUSINESS_TYPE = $res->fields['PK_BUSINESS_TYPE'];
    $INTERESTS = $res->fields['INTERESTS'];
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
                            <li class="breadcrumb-item"><a href="all_interests.php">All Interests</a></li>
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
                                <!--<div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Business Type<span class="text-danger">*</span></label>
                                            <div class="col-md-12">
                                                <select class="form-control" required name="PK_BUSINESS_TYPE" id="PK_BUSINESS_TYPE">
                                                    <option>Select Business Type</option>
                                                    <?php
/*                                                    $row = $db->Execute("select * from DOA_BUSINESS_TYPE WHERE ACTIVE = '1' ORDER BY PK_BUSINESS_TYPE");
                                                    while (!$row->EOF) { */?>
                                                        <option value="<?php /*echo $row->fields['PK_BUSINESS_TYPE'];*/?>" <?php /*=($row->fields['PK_BUSINESS_TYPE'] == $PK_BUSINESS_TYPE)?"selected":""*/?>><?php /*=$row->fields['BUSINESS_TYPE']*/?></option>
                                                        <?php /*$row->MoveNext(); } */?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>-->

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Interest<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="INTERESTS" name="INTERESTS" class="form-control" placeholder="Enter Interests" required value="<?php echo $INTERESTS?>">
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
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_interests.php'">Cancel</button>
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