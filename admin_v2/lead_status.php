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
    $STATUS_COLOR = '#ffffff';
    $DISPLAY_ORDER = '';
    $TEXT_MESSAGE = '';
    $EMAIL_MESSAGE = '';
    $SEND_AFTER_DAYS = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_LEAD_STATUS` WHERE PK_LEAD_STATUS = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_lead_status.php");
        exit;
    }
    $LEAD_STATUS = $res->fields['LEAD_STATUS'];
    $ACTIVE = $res->fields['ACTIVE'];
    $STATUS_COLOR = $res->fields['STATUS_COLOR'];
    $DISPLAY_ORDER = $res->fields['DISPLAY_ORDER'];
    $TEXT_MESSAGE = $res->fields['TEXT_MESSAGE'];
    $EMAIL_MESSAGE = $res->fields['EMAIL_MESSAGE'];
    $SEND_AFTER_DAYS = $res->fields['SEND_AFTER_DAYS'];
}

?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid body_content" style="margin-top: 0px !important;">
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
                                            <div class="form-group">
                                                <label>Status Name<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="LEAD_STATUS" name="LEAD_STATUS" placeholder="Enter Status Name" value="<?php echo $LEAD_STATUS ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Status Color <span class="text-danger">*</span></label>
                                                <input type="color" class="form-control" id="STATUS_COLOR" name="STATUS_COLOR" placeholder="Enter Status" value="<?php echo $STATUS_COLOR ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Display Order <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="DISPLAY_ORDER" name="DISPLAY_ORDER" placeholder="Enter Status" value="<?php echo $DISPLAY_ORDER ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Text Message</label>
                                                <textarea class="form-control" id="TEXT_MESSAGE" name="TEXT_MESSAGE" placeholder="Enter Text Message" rows="5"><?php echo $TEXT_MESSAGE ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Email Message</label>
                                                <textarea class="form-control" id="EMAIL_MESSAGE" name="EMAIL_MESSAGE" placeholder="Enter Email Message" rows="5"><?php echo $EMAIL_MESSAGE ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group">
                                            <div class="col-md-4">
                                                <label>Send after days</label>
                                                <input type="text" class="form-control" id="SEND_AFTER_DAYS" name="SEND_AFTER_DAYS" placeholder="Enter Status" value="<?php echo $SEND_AFTER_DAYS ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($_GET['id'])) { ?>
                                        <div class="row" style="margin-top: 15px;">
                                            <div class="form-group">
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