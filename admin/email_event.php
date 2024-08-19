<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Email Accounts";
else
    $title = "Edit Email Accounts";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    $EMAIL_ACCOUNT_DATA = $_POST;
    $EMAIL_ACCOUNT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if ($_GET['id'] == '') {
        $EMAIL_ACCOUNT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_ACCOUNT_DATA['CREATED_ON'] = date("Y-m-d H:i");

        db_perform('DOA_EMAIL_EVENT', $EMAIL_ACCOUNT_DATA, 'insert');
        header("location:all_email_events.php");
    } else {
        $EMAIL_ACCOUNT_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_ACCOUNT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_EMAIL_EVENT', $EMAIL_ACCOUNT_DATA, 'update', " PK_EMAIL_EVENT = '$_GET[id]'");
        header("location:all_email_events.php");
    }

}
$pageHeaderTitle = "";
if (empty($_GET['id'])) {
    $EMAIL_EVENT    = '';
    $ACTIVE         = '';
} else {
    $res = $db->Execute("SELECT * FROM DOA_EMAIL_EVENT WHERE PK_EMAIL_EVENT = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_email_events.php");
        exit;
    }
    $EMAIL_EVENT     = $res->fields['EMAIL_EVENT'];
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
    <?php require_once('../includes/left_menu.php') ?>

    <div class="page-wrapper">
        <div class="container-fluid extra-space">

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

            <div class="row mb-20">
                <div class="col-8">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">

                                <div class="col-md-12 mb-3">
                                    <label for="EMAIL_EVENT">Email Event</label>
                                    <input type="text" class="form-control" id="EMAIL_EVENT" name="EMAIL_EVENT" value="<?php echo $EMAIL_EVENT ?>" required>
                                    <div class="invalid-feedback">
                                        Please Enter Email Event
                                    </div>
                                </div>

                                <?php if(!empty($_GET['id'])){?>
                                    <input type="hidden" name="PK_EMAIL_EVENT" value="<?php echo $_GET['id'] ?>">
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
                                <button class="btn btn-inverse waves-effect waves-light" type="button" onclick="window.location.href='all_email_event.php'" >Cancel</button>
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