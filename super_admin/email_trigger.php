<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Email Trigger";
else
    $title = "Edit Email Trigger";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    $EMAIL_TRIGGER_DATA = $_POST;
    /*$EMAIL_TRIGGER_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];*/
    if ($_GET['id'] == '') {
        $EMAIL_TRIGGER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_TRIGGER_DATA['CREATED_ON'] = date("Y-m-d H:i");

        db_perform('DOA_EMAIL_TRIGGER', $EMAIL_TRIGGER_DATA, 'insert');
        header("location:all_email_triggers.php");
    } else {
        $EMAIL_TRIGGER_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_TRIGGER_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_EMAIL_TRIGGER', $EMAIL_TRIGGER_DATA, 'update', " PK_EMAIL_TRIGGER = '$_GET[id]'");
        header("location:all_email_triggers.php");
    }

}
$pageHeaderTitle = "";
if (empty($_GET['id'])) {
    $EMAIL_TRIGGER    = '';
    $ACTIVE         = '';
} else {
    $res = $db->Execute("SELECT * FROM DOA_EMAIL_TRIGGER WHERE PK_EMAIL_TRIGGER = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_email_triggers.php");
        exit;
    }
    $EMAIL_TRIGGER     = $res->fields['EMAIL_TRIGGER'];
    $ACTIVE          = $res->fields['ACTIVE'];
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
        <div class="container-fluid extra-space">

            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_email_triggers.php">All Email Triggers</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row mb-20">
                <div class="col-8">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">

                                <div class="col-md-12 mb-3">
                                    <label for="EMAIL_TRIGGER">Email Trigger</label>
                                    <input type="text" class="form-control" id="EMAIL_TRIGGER" name="EMAIL_TRIGGER" value="<?php echo $EMAIL_TRIGGER ?>" required>
                                    <div class="invalid-feedback">
                                        Please Enter Email Trigger
                                    </div>
                                </div>

                                <?php if(!empty($_GET['id'])){?>
                                    <input type="hidden" name="PK_EMAIL_TRIGGER" value="<?php echo $_GET['id'] ?>">
                                    <div class="col-md-12 mb-3">
                                        <label for="IMAGE">Active</label>
                                        <div class="custom-control custom-radio ">
                                            <input type="radio" id="ACTIVE1" name="ACTIVE" class="custom-control-input" <?php echo $ACTIVE == '1'?'checked':'' ?> value="1">
                                            <label class="custom-control-label" for="ACTIVE1">Yes</label>
                                        </div>
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="ACTIVE2" name="ACTIVE" class="custom-control-input" <?php echo $ACTIVE == '0'?'checked':'' ?> value="0">
                                            <label class="custom-control-label" for="ACTIVE2">No</label>
                                        </div>
                                    </div>
                                <?php } ?>


                                <button class="btn btn-info waves-effect waves-light m-r-10 text-white" type="submit"> <?php if(empty($_GET['id'])){ echo 'Save'; } else { echo 'Update'; }?></button>
                                <button class="btn btn-inverse waves-effect waves-light" type="button" onclick="window.location.href='all_email_triggers.php'" >Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" id="row_id" value="" />
    </div>
</div>
<?php require_once('../includes/footer.php');?>
</body>
</html>