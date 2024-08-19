<?php
require_once('../global/config.php');
global $db;

if (empty($_GET['id']))
    $title = "Add Role";
else
    $title = "Edit Role";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $PK_PERMISSION = $_POST['PK_PERMISSION'];
    unset($_POST['PK_PERMISSION']);
    $ROLES_DATA = $_POST;
    if(empty($_GET['id'])){
        $ROLES_DATA['IS_MANAGEMENT'] = $_POST['IS_MANAGEMENT'];
        $ROLES_DATA['ACTIVE'] = 1;
        $ROLES_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $ROLES_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_ROLES', $ROLES_DATA, 'insert');
        $PK_ROLES = $db->insert_ID();
    }else{
        $ROLES_DATA['IS_MANAGEMENT'] = isset($_POST['IS_MANAGEMENT'])?1:0;
        $ROLES_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $ROLES_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $ROLES_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_ROLES', $ROLES_DATA, 'update'," PK_ROLES =  '$_GET[id]'");
        $PK_ROLES = $_GET['id'];
    }

    $db->Execute("DELETE FROM `DOA_ROLES_PERMISSION` WHERE `PK_ROLES` = '$PK_ROLES'");
    if(count($PK_PERMISSION) > 0){
        for($i = 0; $i < count($PK_PERMISSION); $i++){
            $ROLE_PERMISSION_DATA['PK_ROLES'] = $PK_ROLES;
            $ROLE_PERMISSION_DATA['PK_PERMISSION'] = $PK_PERMISSION[$i];
            db_perform('DOA_ROLES_PERMISSION', $ROLE_PERMISSION_DATA, 'insert');
        }
    }

    header("location:all_roles.php");
}

$ROLES	       = '';

if(empty($_GET['id'])){
    $ROLES = '';
    $IS_MANAGEMENT = '';
    $ACTIVE = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_ROLES` WHERE PK_ROLES = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_roles.php");
        exit;
    }
    $ROLES = $res->fields['ROLES'];
    $IS_MANAGEMENT = $res->fields['IS_MANAGEMENT'];
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
                            <li class="breadcrumb-item"><a href="all_roles.php">All Roles</a></li>
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
                                            <label class="col-md-12" for="example-text">Roles<span class="text-danger">*</span></label>
                                            <div class="col-md-12">
                                                <input type="text" id="ROLES" name="ROLES" class="form-control" placeholder="Enter Roles" required value="<?php echo $ROLES?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label">Permission</label>
                                        <div class="col-md-12 multiselect-box">
                                            <select class="multi_sumo_select" name="PK_PERMISSION[]" id="PK_PERMISSION" multiple>
                                                <?php
                                                $selected_permission = [];
                                                if(!empty($_GET['id'])) {
                                                    $selected_permission_row = $db->Execute("SELECT * FROM `DOA_ROLES_PERMISSION` WHERE `PK_ROLES` = '$_GET[id]'");
                                                    while (!$selected_permission_row->EOF) {
                                                        $selected_permission[] = $selected_permission_row->fields['PK_PERMISSION'];
                                                        $selected_permission_row->MoveNext();
                                                    }
                                                }
                                                $row = $db->Execute("SELECT * FROM `DOA_PERMISSION` WHERE ACTIVE = 1");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_PERMISSION'];?>" <?=in_array($row->fields['PK_PERMISSION'], $selected_permission)?"selected":""?>><?=$row->fields['PERMISSION_NAME']?></option>
                                                <?php $row->MoveNext(); } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" style="margin-bottom: 15px; margin-top: 10px">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="" for="example-text">Management</label>
                                            <input type="checkbox" id="IS_MANAGEMENT" name="IS_MANAGEMENT" class="form-check-inline" style="margin-left: 10px;" <?=($IS_MANAGEMENT == 1)?'checked':''?>>
                                        </div>
                                    </div>
                                </div>

                                <?php if(!empty($_GET['id'])) { ?>
                                    <div class="row" style="margin-bottom: 15px; margin-top: 10px">
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
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_roles.php'">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>
<script>
    $('.multi_sumo_select').SumoSelect({placeholder: 'Select Permission', selectAll: true});
</script>
</body>
</html>