<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if (empty($_GET['id']))
    $title = "Add Service / Service Code";
else
    $title = "Edit Service / Service Code";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    if (isset($_POST['PK_LOCATION'])) {
        $res = $db_account->Execute("DELETE FROM `DOA_SERVICE_DOCUMENTS` WHERE PK_SERVICE_MASTER =  '$_GET[id]'");
        for ($i = 0; $i < count($_POST['PK_LOCATION']); $i++) {
            $SERVICE_DOCUMENT_DATA['PK_SERVICE_MASTER'] = $_GET['id'];
            $SERVICE_DOCUMENT_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'][$i];
            if (!empty($_FILES['FILE_PATH']['name'][$i])) {
                $extn             = explode(".", $_FILES['FILE_PATH']['name'][$i]);
                $iindex            = count($extn) - 1;
                $rand_string     = time() . "-" . rand(100000, 999999);
                $file11            = 'service_document_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
                $extension       = strtolower($extn[$iindex]);

                $image_path    = '../uploads/service_document/' . $file11;
                move_uploaded_file($_FILES['FILE_PATH']['tmp_name'][$i], $image_path);
                $SERVICE_DOCUMENT_DATA['FILE_PATH'] = $image_path;
            } else {
                $SERVICE_DOCUMENT_DATA['FILE_PATH'] = $_POST['FILE_PATH_URL'][$i];
            }

            if (empty($_GET['id'])) {
                $SERVICE_DOCUMENT_DATA['ACTIVE'] = 1;
                $SERVICE_DOCUMENT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
                $SERVICE_DOCUMENT_DATA['CREATED_ON']  = date("Y-m-d H:i");
            } else {
                $SERVICE_DOCUMENT_DATA['EDITED_BY']    = $_SESSION['PK_USER'];
                $SERVICE_DOCUMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
            }
            db_perform_account('DOA_SERVICE_DOCUMENTS', $SERVICE_DOCUMENT_DATA, 'insert');
        }
    }
    header("location:all_service_codes.php");
}

if (empty($_GET['id'])) {
    $SERVICE_NAME = '';
    $PK_SERVICE_CLASS = '';
    $MISC_TYPE = '';
    $IS_SCHEDULE = 1;
    $PK_LOCATION = '';
    $DESCRIPTION = '';
    $ACTIVE = '';

    $PK_SERVICE_CODE = '';
    $SERVICE_CODE = '';
    $PRICE = '';
    $IS_GROUP = 0;
    $IS_SUNDRY = 0;
    $CAPACITY = '';
    $IS_CHARGEABLE = 0;
    $COUNT_ON_CALENDAR = 1;
    $SORT_ORDER = 0;
} else {
    $res = $db_account->Execute("SELECT * FROM `DOA_SERVICE_MASTER` WHERE `PK_SERVICE_MASTER` = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_service_codes.php");
        exit;
    }
    $SERVICE_NAME = $res->fields['SERVICE_NAME'];
    $PK_SERVICE_CLASS = $res->fields['PK_SERVICE_CLASS'];
    $MISC_TYPE = $res->fields['MISC_TYPE'];
    $IS_SCHEDULE = $res->fields['IS_SCHEDULE'];
    $DESCRIPTION = $res->fields['DESCRIPTION'];
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $ACTIVE = $res->fields['ACTIVE'];

    $service_code = $db_account->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE PK_SERVICE_MASTER = '$_GET[id]'");
    $PK_SERVICE_CODE = $service_code->fields['PK_SERVICE_CODE'];
    $SERVICE_CODE = $service_code->fields['SERVICE_CODE'];
    $PRICE =  $service_code->fields['PRICE'];
    $IS_GROUP = $service_code->fields['IS_GROUP'];
    $IS_SUNDRY = $service_code->fields['IS_SUNDRY'];
    $CAPACITY = $service_code->fields['CAPACITY'];
    $IS_CHARGEABLE = $service_code->fields['IS_CHARGEABLE'];
    $COUNT_ON_CALENDAR = $service_code->fields['COUNT_ON_CALENDAR'];
    $SORT_ORDER = $service_code->fields['SORT_ORDER'];
}

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'service_codes'");
if ($help->RecordCount() > 0) {
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
}

?>

<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<?php require_once('../includes/header.php'); ?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet" />
<style>
    /* Ensure container doesn't clip */
    .col-4,
    .form-group,
    label {
        overflow: visible !important;
    }

    /* Tooltip wrapper next to icon */
    .tooltip-bubble {
        position: relative;
        display: inline-block;
        cursor: help;
        color: #39B54A;
    }

    /* Actual tooltip element inside DOM */
    .tooltip-bubble .tooltip-text {
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%) translateY(0);
        min-width: 160px;
        max-width: 260px;
        background: #39B54A;
        color: #fff;
        padding: 8px 10px;
        border-radius: 6px;
        font-size: 13px;
        line-height: 1.2;
        text-align: center;
        white-space: normal;
        opacity: 0;
        visibility: hidden;
        transition: opacity .18s ease, transform .18s ease;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .15);
        z-index: 9999;
        pointer-events: none;
    }

    /* little arrow */
    .tooltip-bubble .tooltip-text::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border-width: 6px;
        border-style: solid;
        border-color: #39B54A transparent transparent transparent;
    }

    /* show on hover or focus */
    .tooltip-bubble:hover .tooltip-text,
    .tooltip-bubble:focus-within .tooltip-text {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(-4px);
        pointer-events: auto;
    }

    /* Make it keyboard accessible (if icon is focusable) */
    .tooltip-bubble .ti-help-alt {
        outline: none;
    }
</style>

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
                                <li class="breadcrumb-item"><a href="all_service_codes.php">All Services / Service Codes</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-title" style="margin-top: 15px; margin-left: 15px;">
                                <?php
                                if (!empty($_GET['id'])) {
                                    echo $SERVICE_NAME;
                                }
                                ?>
                            </div>
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="active"> <a class="nav-link active" data-bs-toggle="tab" href="#service_info" role="tab"><span class="hidden-sm-up"><i class="ti-info"></i></span> <span class="hidden-xs-down">Info</span></a> </li>
                                    <li> <a class="nav-link <?= (!empty($_GET['id'])) ? '' : 'disabled' ?>" data-bs-toggle="tab" id="service_document_link" href="#service_document" role="tab" <?= (!empty($_GET['id'])) ? '' : 'disabled' ?>><span class="hidden-sm-up"><i class="ti-files"></i></span> <span class="hidden-xs-down">Service Document</span></a> </li>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content tabcontent-border">
                                    <div class="tab-pane active" id="service_info" role="tabpanel">
                                        <form class="form-material form-horizontal" id="service_info_form">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveServiceData">
                                            <input type="hidden" name="PK_SERVICE_MASTER" class="PK_SERVICE_MASTER" value="<?= (empty($_GET['id'])) ? '' : $_GET['id'] ?>">
                                            <input type="hidden" name="PK_SERVICE_CODE" class="PK_SERVICE_CODE" value="<?= (empty($PK_SERVICE_CODE)) ? '' : $PK_SERVICE_CODE ?>">
                                            <div class="p-20">
                                                <div class="row">
                                                    <div class="col-5 m-r-30">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Service Name<span class="text-danger">*</span></label>
                                                                    <input type="text" id="SERVICE_NAME" name="SERVICE_NAME" class="form-control" placeholder="Enter Service Name" required value="<?php echo $SERVICE_NAME ?>">
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Service Code<span class="text-danger">*</span>
                                                                        <span class="tooltip-bubble" tabindex="0">
                                                                            <i class="ti-help-alt" aria-hidden="true"></i>
                                                                            <span class="tooltip-text">
                                                                                Short Code to show on Calendar
                                                                            </span>
                                                                        </span>
                                                                    </label>
                                                                    <input type="text" id="SERVICE_CODE" name="SERVICE_CODE" class="form-control" placeholder="Enter Service Code" required value="<?php echo $SERVICE_CODE ?>">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <?php
                                                        if (!empty($_GET['id'])) { ?>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label>Is Chargeable?
                                                                            <span class="tooltip-bubble" tabindex="0">
                                                                                <i class="ti-help-alt" aria-hidden="true"></i>
                                                                                <span class="tooltip-text">
                                                                                    Is this Service used to create an enrollement and deduct from account upon Service delivery?
                                                                                </span>
                                                                            </span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <div class="col-md-12">
                                                                            <label><input type="radio" name="IS_CHARGEABLE" class="IS_CHARGEABLE" value="1" <?= (($IS_CHARGEABLE == 1) ? 'checked' : '') ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                            <label><input type="radio" name="IS_CHARGEABLE" class="IS_CHARGEABLE" value="0" <?= (($IS_CHARGEABLE == 0) ? 'checked' : '') ?> />&nbsp;No</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php } else { ?>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label>Is Chargeable?
                                                                            <span class="tooltip-bubble" tabindex="0">
                                                                                <i class="ti-help-alt" aria-hidden="true"></i>
                                                                                <span class="tooltip-text">
                                                                                    Is this Service used to create an enrollement and deduct from account upon Service delivery?
                                                                                </span>
                                                                            </span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <div class="col-md-12">
                                                                            <label><input type="radio" name="IS_CHARGEABLE" class="IS_CHARGEABLE" value="1" />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                            <label><input type="radio" name="IS_CHARGEABLE" class="IS_CHARGEABLE" value="0" checked />&nbsp;No</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>

                                                        <div class="row service_price" style="display: <?= ($IS_CHARGEABLE == 0) ? 'none' : '' ?>">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label>Price
                                                                        <span class="tooltip-bubble" tabindex="0">
                                                                            <i class="ti-help-alt" aria-hidden="true"></i>
                                                                            <span class="tooltip-text">
                                                                                Amount to charge for this Service
                                                                            </span>
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <div class="col-md-12">
                                                                        <div class="input-group">
                                                                            <span class="input-group-text"><?= $currency ?></span>
                                                                            <input type="text" id="PRICE" name="PRICE" class="form-control" placeholder="Price" value="<?= $PRICE ?>" required>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label class="form-label">Location
                                                                    <span class="tooltip-bubble" tabindex="0">
                                                                        <i class="ti-help-alt" aria-hidden="true"></i>
                                                                        <span class="tooltip-text">
                                                                            Location for this Code to be Active
                                                                        </span>
                                                                    </span>
                                                                </label>
                                                                <div class="col-12">
                                                                    <div class="form-group">
                                                                        <select class="form-control PK_LOCATION" name="PK_LOCATION" onchange="selectServiceClass(this)">
                                                                            <?php
                                                                            $row = $db->Execute("SELECT * FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($PK_LOCATION == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= $row->fields['LOCATION_NAME'] ?></option>
                                                                            <?php $row->MoveNext();
                                                                            } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="form-group">
                                                                    <label class="form-label">Description</label>
                                                                    <textarea class="form-control" rows="3" id="DESCRIPTION" name="DESCRIPTION"><?php echo $DESCRIPTION ?></textarea>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <?php if (!empty($_GET['id'])) { ?>
                                                            <div class="row" style="margin-bottom: 15px;">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">Active</label>
                                                                        <div class="col-md-12" style="padding: 8px;">
                                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </div>

                                                    <div class="col-6">
                                                        <h4 style="margin-left: 15%">Options</h4>
                                                        <div id="append_service_code">
                                                            <div class="row">
                                                                <div class="col-4">
                                                                    <div class="form-group">
                                                                        <label>Service Class
                                                                            <span class="tooltip-bubble" tabindex="0">
                                                                                <i class="ti-help-alt" aria-hidden="true"></i>
                                                                                <span class="tooltip-text">
                                                                                    How is this Service to be enrolled
                                                                                </span>
                                                                            </span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-8">
                                                                    <div class="form-group">
                                                                        <select class="form-control PK_SERVICE_CLASS" name="PK_SERVICE_CLASS" onchange="selectServiceClass(this)">
                                                                            <option value="">Select</option>
                                                                            <?php
                                                                            $row = $db->Execute("SELECT * FROM DOA_SERVICE_CLASS WHERE ACTIVE = 1");
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_SERVICE_CLASS']; ?>" <?= ($PK_SERVICE_CLASS == $row->fields['PK_SERVICE_CLASS']) ? 'selected' : '' ?>><?= $row->fields['SERVICE_CLASS'] ?></option>
                                                                            <?php $row->MoveNext();
                                                                            } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row service_class_type" id="misc_type_div" style="display: <?= ($PK_SERVICE_CLASS == 5) ? '' : 'none' ?>">
                                                                <div class="col-4">
                                                                    <div class="form-group">
                                                                        <label>Misc. Type</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-8">
                                                                    <div class="form-group">
                                                                        <select class="form-control MISC_TYPE" name="MISC_TYPE">
                                                                            <option value="">Select</option>
                                                                            <option value="GENERAL" <?= ($MISC_TYPE == 'GENERAL') ? 'selected' : '' ?>>General</option>
                                                                            <option value="DOR" <?= ($MISC_TYPE == 'DOR') ? 'selected' : '' ?>>DOR</option>
                                                                            <option value="SHOWCASE" <?= ($MISC_TYPE == 'SHOWCASE') ? 'selected' : '' ?>>Showcase</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-4">
                                                                    <div class="form-group">
                                                                        <label>Scheduling Code
                                                                            <span class="tooltip-bubble" tabindex="0">
                                                                                <i class="ti-help-alt" aria-hidden="true"></i>
                                                                                <span class="tooltip-text">
                                                                                    Scheduling Codes to show and to be allowed to book for this
                                                                                </span>
                                                                            </span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-8">
                                                                    <div class="col-md-12 multiselect-box">
                                                                        <label for="PK_SCHEDULING_CODE"></label>
                                                                        <select class="multi_select" id="PK_SCHEDULING_CODE" name="PK_SCHEDULING_CODE[]" multiple>
                                                                            <?php
                                                                            $selected_scheduling_code  = [];
                                                                            if (!empty($_GET['id'])) {
                                                                                $selected_scheduling_code_row = $db_account->Execute("SELECT `PK_SCHEDULING_CODE` FROM `DOA_SCHEDULING_SERVICE` WHERE `PK_SERVICE_MASTER` = '$_GET[id]'");
                                                                                while (!$selected_scheduling_code_row->EOF) {
                                                                                    $selected_scheduling_code[] = $selected_scheduling_code_row->fields['PK_SCHEDULING_CODE'];
                                                                                    $selected_scheduling_code_row->MoveNext();
                                                                                }
                                                                            }
                                                                            $scheduling_code = $db_account->Execute("SELECT * FROM `DOA_SCHEDULING_CODE` WHERE PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND `ACTIVE` = 1");
                                                                            while (!$scheduling_code->EOF) { ?>
                                                                                <option value="<?= $scheduling_code->fields['PK_SCHEDULING_CODE'] ?>" <?= in_array($scheduling_code->fields['PK_SCHEDULING_CODE'], $selected_scheduling_code) ? "selected" : "" ?>><?= $scheduling_code->fields['SCHEDULING_NAME'] . ' (' . $scheduling_code->fields['SCHEDULING_CODE'] . ')' ?></option>
                                                                            <?php $scheduling_code->MoveNext();
                                                                            } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-4">
                                                                    <div class="form-group">
                                                                        <label>Is Group?
                                                                            <span class="tooltip-bubble" tabindex="0">
                                                                                <i class="ti-help-alt" aria-hidden="true"></i>
                                                                                <span class="tooltip-text">
                                                                                    Is this Code a Group Class and allow Multiple Clients in it?
                                                                                </span>
                                                                            </span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-8">
                                                                    <div class="form-group">
                                                                        <div class="col-md-12">
                                                                            <label><input type="radio" name="IS_GROUP" class="IS_GROUP" value="1" <?= (($IS_GROUP == 1) ? 'checked' : '') ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                            <label><input type="radio" name="IS_GROUP" class="IS_GROUP" value="0" <?= (($IS_GROUP == 0) ? 'checked' : '') ?> />&nbsp;No</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row capacity_div" style="display: <?= (($IS_GROUP == 1) ? '' : 'none') ?>">
                                                                <div class="col-4">
                                                                    <div class="form-group">
                                                                        <label>Capacity</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-8">
                                                                    <div class="form-group">
                                                                        <div class="col-md-12">
                                                                            <input type="number" class="form-control" name="CAPACITY" id="CAPACITY" value="<?= $CAPACITY ?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-4">
                                                                    <div class="form-group">
                                                                        <label>Is Sundry?
                                                                            <span class="tooltip-bubble" tabindex="0">
                                                                                <i class="ti-help-alt" aria-hidden="true"></i>
                                                                                <span class="tooltip-text">
                                                                                    Is this a Product not a Service?
                                                                                </span>
                                                                            </span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-8">
                                                                    <div class="form-group">
                                                                        <div class="col-md-12">
                                                                            <label><input type="radio" name="IS_SUNDRY" class="IS_SUNDRY" value="1" <?= (($IS_SUNDRY == 1) ? 'checked' : '') ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                            <label><input type="radio" name="IS_SUNDRY" class="IS_SUNDRY" value="0" <?= (($IS_SUNDRY == 0) ? 'checked' : '') ?> />&nbsp;No</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-4">
                                                                    <div class="form-group">
                                                                        <label>Count on Calendar?
                                                                            <span class="tooltip-bubble" tabindex="0">
                                                                                <i class="ti-help-alt" aria-hidden="true"></i>
                                                                                <span class="tooltip-text">
                                                                                    Is this Service to be counted on Calendar Counter?
                                                                                </span>
                                                                            </span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-8">
                                                                    <div class="form-group">
                                                                        <div class="col-md-12">
                                                                            <label><input type="radio" name="COUNT_ON_CALENDAR" class="COUNT_ON_CALENDAR" value="1" <?= (($COUNT_ON_CALENDAR == 1) ? 'checked' : '') ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                            <label><input type="radio" name="COUNT_ON_CALENDAR" class="COUNT_ON_CALENDAR" value="0" <?= (($COUNT_ON_CALENDAR == 0) ? 'checked' : '') ?> />&nbsp;No</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-4">
                                                                    <div class="form-group">
                                                                        <label>Sort Order
                                                                            <span class="tooltip-bubble" tabindex="0">
                                                                                <i class="ti-help-alt" aria-hidden="true"></i>
                                                                                <span class="tooltip-text">
                                                                                    What order is this Schedule to appear in Drop Down Menus
                                                                                </span>
                                                                            </span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-8">
                                                                    <div class="form-group">
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="SORT_ORDER" name="SORT_ORDER" class="form-control" placeholder="Enter Sort Order" value="<?php echo $SORT_ORDER ?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Continue</button>
                                                    <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="tab-pane" id="service_document" role="tabpanel">
                                        <form method="post" action="" enctype="multipart/form-data">
                                            <div class="p-20">
                                                <div class="card-body" id="append_service_document">
                                                    <?php
                                                    if (!empty($_GET['id'])) {
                                                        $service_document = $db_account->Execute("SELECT * FROM DOA_SERVICE_DOCUMENTS WHERE PK_SERVICE_MASTER = '$_GET[id]'");
                                                        while (!$service_document->EOF) { ?>
                                                            <div class="row">
                                                                <div class="col-5">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Location</label>
                                                                        <select class="form-control PK_LOCATION" name="PK_LOCATION[]">
                                                                            <option>Select Location</option>
                                                                            <?php
                                                                            $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY LOCATION_NAME");
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($service_document->fields['PK_LOCATION'] == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= $row->fields['LOCATION_NAME'] ?></option>
                                                                            <?php $row->MoveNext();
                                                                            } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-5">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Document File</label>
                                                                        <input type="file" name="FILE_PATH[]" class="form-control">
                                                                        <a target="_blank" href="<?= $service_document->fields['FILE_PATH'] ?>">View</a>
                                                                        <input type="hidden" name="FILE_PATH_URL[]" value="<?= $service_document->fields['FILE_PATH'] ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-2">
                                                                    <div class="form-group" style="margin-top: 15px;">
                                                                        <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php $service_document->MoveNext();
                                                        } ?>
                                                    <?php } else { ?>
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <div class="form-group">
                                                                    <label class="form-label">Location</label>
                                                                    <select class="form-control PK_LOCATION" name="PK_LOCATION[]">
                                                                        <option>Select Location</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY LOCATION_NAME");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_LOCATION']; ?>"><?= $row->fields['LOCATION_NAME'] ?></option>
                                                                        <?php $row->MoveNext();
                                                                        } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-5">
                                                                <div class="form-group">
                                                                    <label class="form-label">Document File</label>
                                                                    <input type="file" name="FILE_PATH[]" class="form-control">
                                                                </div>
                                                            </div>
                                                            <div class="col-2">
                                                                <div class="form-group" style="margin-top: 15px;">
                                                                    <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeServiceDocument(this);"><i class="ti-trash"></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-11">
                                                    <div class="form-group" style="float: right;">
                                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addServiceDocument();">Add More</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white ">Submit</button>
                                            <button type="button" onclick="window.location.href='all_service_codes.php'" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <h4 class="col-md-12" STYLE="text-align: center">
                                        <?= $help_title ?>
                                    </h4>
                                    <div class="col-md-12">
                                        <text class="required-entry rich" id="DESCRIPTION"><?= $help_description ?></text>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
    <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>
    <script>
        let PK_SERVICE_MASTER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);

        $('.multi_select').SumoSelect({
            search: true,
            placeholder: 'Select Scheduling Code',
            searchText: 'Search...',
            selectAll: true
        });

        $('.multi_sumo_select_location').SumoSelect({
            placeholder: 'Select Location',
            selectAll: true
        });

        $(document).on('change', '.IS_CHARGEABLE', function() {
            if ($(this).val() == 1) {
                $('.service_price').slideDown();
                $("#PRICE").attr("required", "required");
            } else {
                $('.service_price').slideUp();
                $('#PRICE').removeAttr('required');
            }
        });

        $(document).on('change', '.IS_GROUP', function() {
            if ($(this).val() == 1) {
                $('.capacity_div').slideDown();
            } else {
                $('.capacity_div').slideUp();
            }
        });

        function selectServiceClass(param) {
            let PK_SERVICE_CLASS = parseInt($(param).val());
            $('.service_class_type').slideUp();

            /*if (PK_SERVICE_CLASS === 2){
                $('#schedule_div').slideDown();
            }*/

            if (PK_SERVICE_CLASS === 5) {
                $('#misc_type_div').slideDown();
            }
        }

        function addServiceDocument() {
            $('#append_service_document').append(`<div class="row">
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <label class="form-label">Location</label>
                                                        <select class="form-control PK_LOCATION" name="PK_LOCATION[]">
                                                            <option>Select Location</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY LOCATION_NAME");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_LOCATION']; ?>"><?= $row->fields['LOCATION_NAME'] ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <label class="form-label">Document File</label>
                                                        <input type="file" name="FILE_PATH[]" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <div class="form-group" style="margin-top: 15px;">
                                                        <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeServiceDocument(this);"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                            </div>`);
        }

        function removeServiceDocument(param) {
            $(param).closest('.row').remove();
        }

        $(document).on('click', '#cancel_button', function() {
            window.location.href = 'all_service_codes.php'
        });

        $(document).on('submit', '#service_info_form', function(event) {
            event.preventDefault();
            let form_data = $('#service_info_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success: function(data) {
                    if (PK_SERVICE_MASTER == 0) {
                        $('.disabled').attr('disabled', false).removeClass('disabled');
                        $('.PK_SERVICE_MASTER').val(data);
                        //$('#service_codes_link')[0].click();
                        window.location.href = 'all_service_codes.php';
                    } else {
                        window.location.href = 'all_service_codes.php';
                    }
                }
            });
        });

        /*
            $(document).on('submit', '#service_info_form', function (event) {
                event.preventDefault();
                let form_data = $('#service_info_form').serialize();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: form_data,
                    success:function (data) {
                        if (PK_SERVICE_MASTER == 0) {
                            $('.disabled').attr('disabled', false).removeClass('disabled');
                            $('.PK_SERVICE_MASTER').val(data);
                            $('#service_codes_link')[0].click();
                        }else{
                            window.location.href='all_services.php';
                        }
                    }
                });
            });
        */

        $(document).on('submit', '#service_code_form', function(event) {
            event.preventDefault();
            let form_data = $('#service_code_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success: function(data) {
                    window.location.href = 'all_services_codes.php';
                }
            });
        });
    </script>
</body>

</html>