<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Skill Level";
else
    $title = "Edit Skill Level";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $SKILL_LEVEL_DATA = $_POST;
    if(empty($_GET['id'])){
        $SKILL_LEVEL_DATA['ACTIVE'] = 1;
        $SKILL_LEVEL_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $SKILL_LEVEL_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_SKILL_LEVEL', $SKILL_LEVEL_DATA, 'insert');
    }else{
        $SKILL_LEVEL_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $SKILL_LEVEL_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $SKILL_LEVEL_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_SKILL_LEVEL', $SKILL_LEVEL_DATA, 'update'," PK_SKILL_LEVEL =  '$_GET[id]'");
    }
    header("location:all_skill_levels.php");
}

if(empty($_GET['id'])){
    $SKILL_LEVEL = '';
    $ACTIVE = '';
}
else {
    $res = $db_account->Execute("SELECT * FROM `DOA_SKILL_LEVEL` WHERE PK_SKILL_LEVEL = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_skill_levels.php");
        exit;
    }
    $SKILL_LEVEL = $res->fields['SKILL_LEVEL'];
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
                            <li class="breadcrumb-item"><a href="all_skill_levels.php">All Skill Level</a></li>
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
                                    <label class="col-md-12" for="example-text">Skill Level<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-12">
                                        <input type="text" id="SKILL_LEVEL" name="SKILL_LEVEL" class="form-control" placeholder="Enter Skill Level" value="<?php echo $SKILL_LEVEL?>">
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
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_skill_levels.php'">Cancel</button>
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