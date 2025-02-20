<?php
require_once('../global/config.php');
global $db;
global $db_account;

if (empty($_GET['id']))
    $title = "Add Email Accounts";
else
    $title = "Edit Email Accounts";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

$msg = '';
if (!empty($_POST)) {
    //$EMAIL_ACCOUNT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $EMAIL_ACCOUNT_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
    $EMAIL_ACCOUNT_DATA['HOST'] = $_POST['HOST'];
    $EMAIL_ACCOUNT_DATA['PORT'] = $_POST['PORT'];
    $EMAIL_ACCOUNT_DATA['USER_NAME'] = $_POST['USER_NAME'];
    $EMAIL_ACCOUNT_DATA['PASSWORD'] = $_POST['PASSWORD'];
    if (empty($_GET['id'])) {
        $EMAIL_ACCOUNT_DATA['ACTIVE'] = 1;
        $EMAIL_ACCOUNT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_ACCOUNT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_EMAIL_ACCOUNT', $EMAIL_ACCOUNT_DATA, 'insert');
        $PK_EMAIL_ACCOUNT = $db_account->insert_ID();
    } else {
        $EMAIL_ACCOUNT_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $EMAIL_ACCOUNT_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_ACCOUNT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_EMAIL_ACCOUNT', $EMAIL_ACCOUNT_DATA, 'update', " PK_EMAIL_ACCOUNT = '$_GET[id]'");
        $PK_EMAIL_ACCOUNT = $_GET['id'];
    }
    header("location:all_email_accounts.php");
}
$pageHeaderTitle = "";
if (empty($_GET['id'])) {
    $PK_LOCATION    = '';
    $HOST           = '';
    $PORT           = '';
    $USER_NAME      = '';
    $PASSWORD       = '';
    $ACTIVE         = '';
} else {
    $res = $db_account->Execute("SELECT * FROM DOA_EMAIL_ACCOUNT WHERE PK_EMAIL_ACCOUNT = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_email_accounts.php");
        exit;
    }
    $PK_LOCATION     = $res->fields['PK_LOCATION'];
    $HOST            = $res->fields['HOST'];
    $PORT            = $res->fields['PORT'];
    $USER_NAME       = $res->fields['USER_NAME'];
    $PASSWORD        = $res->fields['PASSWORD'];
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
            <div class="container-fluid extra-space body_content">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?=$title?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item"><a href="all_email_accounts.php">All Email Accounts</a></li>
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

                                    <div class="col-12">
                                        <label class="form-label">Location</label>
                                        <div class="col-md-12 multiselect-box">
                                            <select class="multi_sumo_select_location" name="PK_LOCATION[]" id="PK_LOCATION" multiple>
                                                <?php
                                                $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=($row->fields['PK_LOCATION'] == $PK_LOCATION)?"selected":""?>><?=$row->fields['LOCATION_NAME']?></option>
                                                <?php $row->MoveNext(); } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label for="HOST">Host</label>
                                        <input type="text" class="form-control" id="HOST" name="HOST" value="<?php echo $HOST ?>" required>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label for="PORT">Port</label>
                                        <input type="text" class="form-control" id="PORT" name="PORT" value="<?php echo $PORT ?>" required>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label for="USER_NAME">User Name</label>
                                        <input type="text" class="form-control" id="USER_NAME" name="USER_NAME" value="<?php echo $USER_NAME ?>" required>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label for="PASSWORD">Password</label>
                                        <input type="text" class="form-control" id="PASSWORD" name="PASSWORD" value="<?php echo $PASSWORD ?>">
                                    </div>

                                    <?php if(!empty($_GET['id'])){?>
                                        <input type="hidden" name="PK_EMAIL_ACCOUNT" value="<?php echo $_GET['id'] ?>">
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
                                    <button class="btn btn-inverse waves-effect waves-light" type="button" onclick="window.location.href='all_email_accounts.php'" >Cancel</button>
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

<script>
    $('#PK_LOCATION').SumoSelect({placeholder: 'Select Location', selectAll: true});
</script>

</body>
</html>

