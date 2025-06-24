<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Lead Status";
else
    $title = "Edit Lead Status";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    $LEAD_STATUS_DATA = $_POST;
    $LEAD_STATUS_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if (empty($_GET['id'])) {
        $LEAD_STATUS_DATA['ACTIVE'] = 1;
        $LEAD_STATUS_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $LEAD_STATUS_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_LEAD_STATUS', $LEAD_STATUS_DATA, 'insert');
    } else {
        $LEAD_STATUS_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $LEAD_STATUS_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $LEAD_STATUS_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_LEAD_STATUS', $LEAD_STATUS_DATA, 'update', " PK_LEAD_STATUS =  '$_GET[id]'");
    }
    header("location:all_lead_status.php");
}

if (empty($_GET['id'])) {
    $LEAD_STATUS = '';
    $ACTIVE = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_LEAD_STATUS` WHERE PK_LEAD_STATUS = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_lead_status.php");
        exit;
    }
    $LEAD_STATUS = $res->fields['LEAD_STATUS'];
    $ACTIVE = $res->fields['ACTIVE'];
}

?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <div class="container-fluid body_content">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item"><a href="all_lead_status.php">All Lead Status</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
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
                                        <div class="col-md-4">
                                            <label>Status<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="LEAD_STATUS" name="LEAD_STATUS" placeholder="Enter Status" value="<?php echo $LEAD_STATUS ?>" required>
                                            <div class="invalid-feedback">
                                                Enter Lead Status
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($_GET['id'])) { ?>
                                        <div class="row" style="margin-top: 15px;">
                                            <div class="col-6">
                                                <div class="col-md-2">
                                                    <label>Active</label>
                                                </div>
                                                <div class="col-md-4">
                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                </div>
                                            </div>
                                        </div>
                                    <? } ?>

                                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="margin-top: 10px;">Submit</button>
                                    <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_lead_status.php'">Cancel</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>