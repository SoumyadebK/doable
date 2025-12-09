<?php
require_once('../global/config.php');

$title = "Default Call Setting";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    $DEFAUL_CALL_SETTING_DATA = $_POST;
    //$DEFAUL_CALL_SETTING_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $db_account->Execute("TRUNCATE TABLE DOA_DEFAULT_CALL_SETTING");
    $DEFAUL_CALL_SETTING_DATA['ACTIVE'] = 1;
    $DEFAUL_CALL_SETTING_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
    $DEFAUL_CALL_SETTING_DATA['CREATED_ON']  = date("Y-m-d H:i");
    //pre_r($DEFAUL_CALL_SETTING_DATA);
    db_perform_account('DOA_DEFAULT_CALL_SETTING', $DEFAUL_CALL_SETTING_DATA, 'insert');

    header("location:default_call_setting.php");
}


$PK_USER = '';
$PK_SERVICE_MASTER = '';
$PK_SCHEDULING_CODE = '';
$ACTIVE = '';
$res = $db_account->Execute("SELECT * FROM `DOA_DEFAULT_CALL_SETTING`");

if ($res->RecordCount() > 0) {

    $PK_USER = $res->fields['PK_USER'];
    $PK_SERVICE_MASTER = $res->fields['PK_SERVICE_MASTER'];
    $PK_SCHEDULING_CODE = $res->fields['PK_SCHEDULING_CODE'];
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
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="form-label" for="PK_USER">Service Provider<span class="text-danger">*</span></label><br>
                                                <select id="PK_USER" name="PK_USER" class="form-control" required>
                                                    <option value="">Select <?= $service_provider_title ?></option>
                                                    <?php
                                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);

                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_USER']; ?>" <?= $row->fields['PK_USER'] == $PK_USER ? "selected" : "" ?>><?= $row->fields['NAME'] ?></option>
                                                    <?php $row->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="form-label" for="PK_SERVICE_MASTER">Service<span class="text-danger">*</span></label><br>
                                                <select id="PK_SERVICE_MASTER" name="PK_SERVICE_MASTER" class="form-control" required>
                                                    <option value="">Select Service</option>
                                                    <?php
                                                    $row = $db_account->Execute("SELECT DISTINCT(DOA_SERVICE_MASTER.PK_SERVICE_MASTER), DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_MASTER WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE=1 AND PK_SERVICE_CLASS = 2 ORDER BY SERVICE_NAME");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>" <?= $row->fields['PK_SERVICE_MASTER'] == $PK_SERVICE_MASTER ? "selected" : "" ?>><?= $row->fields['SERVICE_NAME'] ?></option>
                                                    <?php $row->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="form-label" for="PK_SCHEDULING_CODE">Scheduling Code<span class="text-danger">*</span></label><br>
                                                <select id="PK_SCHEDULING_CODE" name="PK_SCHEDULING_CODE" class="form-control" required>
                                                    <option value="">Select Scheduling Code</option>
                                                    <?php
                                                    $row = $db_account->Execute("SELECT DOA_SCHEDULING_CODE.`PK_SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_NAME`, DOA_SCHEDULING_CODE.`DURATION` FROM `DOA_SCHEDULING_CODE` WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_SCHEDULING_CODE.`ACTIVE` = 1 ORDER BY CASE WHEN DOA_SCHEDULING_CODE.SORT_ORDER IS NULL THEN 1 ELSE 0 END, DOA_SCHEDULING_CODE.SORT_ORDER");
                                                    while (!$row->EOF) { ?>
                                                        <option data-duration="<?= $row->fields['DURATION']; ?>" value="<?= $row->fields['PK_SCHEDULING_CODE'] . ',' . $row->fields['DURATION'] ?>" <?= $row->fields['PK_SCHEDULING_CODE'] == $PK_SCHEDULING_CODE ? "selected" : "" ?>><?= $row->fields['SCHEDULING_NAME'] . ' (' . $row->fields['SCHEDULING_CODE'] . ')' ?></option>
                                                    <?php $row->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($_GET['id'])) { ?>
                                        <div class="row" style="margin-bottom: 15px;">
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

                                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                    <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_inquiry_methods.php'">Cancel</button>
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