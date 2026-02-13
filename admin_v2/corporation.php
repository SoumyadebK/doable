<?php
require_once('../global/config.php');
global $db;
global $db_account;

if (empty($_GET['id']))
    $title = "Add Corporation";
else
    $title = "Edit Corporation";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];
$PK_CORPORATION =  (!empty($_GET['id'])) ? $_GET['id'] : 0;

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'corporation'");
if ($help->RecordCount() > 0) {
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
}

$SA_PAYMENT_GATEWAY_TYPE = '';
$SA_GATEWAY_MODE = '';
$SA_SECRET_KEY = '';
$SA_PUBLISHABLE_KEY = '';
$SA_ACCESS_TOKEN = '';
$SA_SQUARE_APP_ID = '';
$SA_SQUARE_LOCATION_ID = '';

$payment_gateway_setting = $db->Execute("SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");
if ($payment_gateway_setting->RecordCount() > 0) {
    $SA_PAYMENT_GATEWAY_TYPE = $payment_gateway_setting->fields['PAYMENT_GATEWAY_TYPE'];
    $SA_GATEWAY_MODE = $payment_gateway_setting->fields['GATEWAY_MODE'];
    $SA_SECRET_KEY = $payment_gateway_setting->fields['SECRET_KEY'];
    $SA_PUBLISHABLE_KEY = $payment_gateway_setting->fields['PUBLISHABLE_KEY'];
    $SA_ACCESS_TOKEN = $payment_gateway_setting->fields['ACCESS_TOKEN'];
    $SA_SQUARE_APP_ID = $payment_gateway_setting->fields['APP_ID'];
    $SA_SQUARE_LOCATION_ID = $payment_gateway_setting->fields['LOCATION_ID'];
}

if (empty($_GET['id'])) {
    $CORPORATION_NAME       = '';
    $PK_TIMEZONE            = '';
    $PK_CURRENCY            = '';
    $USERNAME_PREFIX        = '';
    $TIME_SLOT_INTERVAL     = '';
    $SERVICE_PROVIDER_TITLE = '';
    $OPERATION_TAB_TITLE    = '';
    $ENROLLMENT_ID_CHAR     = '';
    $ENROLLMENT_ID_NUM      = '';
    $MISCELLANEOUS_ID_CHAR  = '';
    $MISCELLANEOUS_ID_NUM   = '';
    $APPOINTMENT_REMINDER   = '';
    $HOUR                   = '';
    $FOCUSBIZ_API_KEY       = '';
    $SALES_TAX              = '';

    $PAYMENT_GATEWAY_TYPE   = '';
    $GATEWAY_MODE           = '';
    $SECRET_KEY             = '';
    $PUBLISHABLE_KEY        = '';
    $ACCESS_TOKEN           = '';
    $SQUARE_APP_ID          = '';
    $SQUARE_LOCATION_ID     = '';
    $LOGIN_ID               = '';
    $TRANSACTION_KEY        = '';
    $AUTHORIZE_CLIENT_KEY   = '';
    $MERCHANT_ID            = '';
    $API_KEY                = '';
    $PUBLIC_API_KEY         = '';

    $FRANCHISE              = '';
    $AM_USER_NAME           = '';
    $AM_PASSWORD            = '';
    $AM_REFRESH_TOKEN       = '';

    $TEXTING_FEATURE_ENABLED = '';
    $TWILIO_ACCOUNT_TYPE     = '';
    $SID                     = '';
    $TOKEN                   = '';
    $TWILIO_PHONE_NO         = '';

    $ACTIVE                  = '';
    $START_DATE              = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_CORPORATION` WHERE PK_CORPORATION = '$PK_CORPORATION'");
    if ($res->RecordCount() == 0) {
        header("location:all_corporations.php");
        exit;
    }
    $CORPORATION_NAME       = $res->fields['CORPORATION_NAME'];
    $PK_TIMEZONE            = $res->fields['PK_TIMEZONE'];
    $PK_CURRENCY            = $res->fields['PK_CURRENCY'];
    $USERNAME_PREFIX        = $res->fields['USERNAME_PREFIX'];
    $TIME_SLOT_INTERVAL     = $res->fields['TIME_SLOT_INTERVAL'];
    $SERVICE_PROVIDER_TITLE = $res->fields['SERVICE_PROVIDER_TITLE'];
    $OPERATION_TAB_TITLE    = $res->fields['OPERATION_TAB_TITLE'];
    $ENROLLMENT_ID_CHAR     = $res->fields['ENROLLMENT_ID_CHAR'];
    $ENROLLMENT_ID_NUM      = $res->fields['ENROLLMENT_ID_NUM'];
    $MISCELLANEOUS_ID_CHAR  = $res->fields['MISCELLANEOUS_ID_CHAR'];
    $MISCELLANEOUS_ID_NUM   = $res->fields['MISCELLANEOUS_ID_NUM'];
    $APPOINTMENT_REMINDER   = $res->fields['APPOINTMENT_REMINDER'];
    $HOUR                   = $res->fields['HOUR'];
    $FOCUSBIZ_API_KEY       = $res->fields['FOCUSBIZ_API_KEY'];
    $SALES_TAX              = $res->fields['SALES_TAX'];

    $PAYMENT_GATEWAY_TYPE   = $res->fields['PAYMENT_GATEWAY_TYPE'];
    $GATEWAY_MODE           = $res->fields['GATEWAY_MODE'];
    $SECRET_KEY             = $res->fields['SECRET_KEY'];
    $PUBLISHABLE_KEY        = $res->fields['PUBLISHABLE_KEY'];
    $ACCESS_TOKEN           = $res->fields['ACCESS_TOKEN'];
    $SQUARE_APP_ID          = $res->fields['APP_ID'];
    $SQUARE_LOCATION_ID     = $res->fields['LOCATION_ID'];
    $LOGIN_ID               = $res->fields['LOGIN_ID'];
    $TRANSACTION_KEY        = $res->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY   = $res->fields['AUTHORIZE_CLIENT_KEY'];
    $MERCHANT_ID            = $res->fields['MERCHANT_ID'];
    $API_KEY                = $res->fields['API_KEY'];
    $PUBLIC_API_KEY         = $res->fields['PUBLIC_API_KEY'];

    $FRANCHISE              = $res->fields['FRANCHISE'];
    $AM_USER_NAME           = $res->fields['AM_USER_NAME'];
    $AM_PASSWORD            = $res->fields['AM_PASSWORD'];
    $AM_REFRESH_TOKEN       = $res->fields['AM_REFRESH_TOKEN'];

    $TEXTING_FEATURE_ENABLED    = $res->fields['TEXTING_FEATURE_ENABLED'];
    $TWILIO_ACCOUNT_TYPE        = $res->fields['TWILIO_ACCOUNT_TYPE'];
    $SID                        = $res->fields['SID'];
    $TOKEN                      = $res->fields['TOKEN'];
    $TWILIO_PHONE_NO            = $res->fields['TWILIO_PHONE_NO'];

    $ACTIVE                 = $res->fields['ACTIVE'];
    $START_DATE             = $res->fields['CREATED_ON'];
}

$user_data = $db->Execute("SELECT DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_data->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];

$am_location_data = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE ACTIVE = 1 AND FRANCHISE = 1 AND PK_CORPORATION = '$PK_CORPORATION' AND `PK_ACCOUNT_MASTER`  = " . $PK_ACCOUNT_MASTER);
$am_location_count = $am_location_data->RecordCount();

$non_am_location_data = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE ACTIVE = 1 AND FRANCHISE = 0 AND PK_CORPORATION = '$PK_CORPORATION' AND `PK_ACCOUNT_MASTER`  = " . $PK_ACCOUNT_MASTER);
$non_am_location_count = $non_am_location_data->RecordCount();

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER`  = " . $PK_ACCOUNT_MASTER);

$RENEWAL_INTERVAL = $account_data->fields['RENEWAL_INTERVAL'];

$AM_AMOUNT = $account_data->fields['AM_AMOUNT'];
$NOT_AM_AMOUNT = $account_data->fields['NOT_AM_AMOUNT'];

if (($AM_AMOUNT == '' || $AM_AMOUNT == 0.00) && ($NOT_AM_AMOUNT == '' || $NOT_AM_AMOUNT == 0.00)) {
    $res = $db->Execute("SELECT * FROM `DOA_OTHER_SETTING`");
    if ($res->RecordCount() > 0) {
        $AM_AMOUNT       = $res->fields['AM_AMOUNT'];
        $NOT_AM_AMOUNT   = $res->fields['NOT_AM_AMOUNT'];
    }
}

if (!empty($_POST)  && $_POST['FUNCTION_NAME'] == 'saveCorporationData') {
    $CORPORATION_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $CORPORATION_DATA['CORPORATION_NAME'] = $_POST['CORPORATION_NAME'];
    $CORPORATION_DATA['PK_TIMEZONE'] = $_POST['PK_TIMEZONE'];
    $CORPORATION_DATA['TIME_SLOT_INTERVAL'] = $_POST['TIME_SLOT_INTERVAL'];
    $CORPORATION_DATA['SERVICE_PROVIDER_TITLE'] = $_POST['SERVICE_PROVIDER_TITLE'];
    $CORPORATION_DATA['OPERATION_TAB_TITLE'] = $_POST['OPERATION_TAB_TITLE'];
    $CORPORATION_DATA['PK_CURRENCY'] = $_POST['PK_CURRENCY'];
    $CORPORATION_DATA['ENROLLMENT_ID_CHAR'] = $_POST['ENROLLMENT_ID_CHAR'];
    $CORPORATION_DATA['ENROLLMENT_ID_NUM'] = $_POST['ENROLLMENT_ID_NUM'];
    $CORPORATION_DATA['MISCELLANEOUS_ID_CHAR'] = $_POST['MISCELLANEOUS_ID_CHAR'];
    $CORPORATION_DATA['MISCELLANEOUS_ID_NUM'] = $_POST['MISCELLANEOUS_ID_NUM'];

    $CORPORATION_DATA['APPOINTMENT_REMINDER'] = $_POST['APPOINTMENT_REMINDER'];
    $CORPORATION_DATA['HOUR'] = empty($_POST['HOUR']) ? 0 : $_POST['HOUR'];

    $CORPORATION_DATA['FOCUSBIZ_API_KEY'] = $_POST['FOCUSBIZ_API_KEY'];
    $CORPORATION_DATA['SALES_TAX'] = $_POST['SALES_TAX'];

    $CORPORATION_DATA['TEXTING_FEATURE_ENABLED'] = $_POST['TEXTING_FEATURE_ENABLED'];
    $CORPORATION_DATA['TWILIO_ACCOUNT_TYPE'] = $_POST['TWILIO_ACCOUNT_TYPE'];
    $CORPORATION_DATA['SID'] = $_POST['SID'];
    $CORPORATION_DATA['TOKEN'] = $_POST['TOKEN'];
    $CORPORATION_DATA['TWILIO_PHONE_NO'] = $_POST['TWILIO_PHONE_NO'];

    $CORPORATION_DATA['PAYMENT_GATEWAY_TYPE'] = $_POST['PAYMENT_GATEWAY_TYPE'];
    $CORPORATION_DATA['GATEWAY_MODE'] = $_POST['GATEWAY_MODE'];
    $CORPORATION_DATA['SECRET_KEY'] = $_POST['SECRET_KEY'];
    $CORPORATION_DATA['PUBLISHABLE_KEY'] = $_POST['PUBLISHABLE_KEY'];
    $CORPORATION_DATA['ACCESS_TOKEN'] = $_POST['ACCESS_TOKEN'];
    $CORPORATION_DATA['APP_ID'] = $_POST['APP_ID'];
    $CORPORATION_DATA['LOCATION_ID'] = $_POST['LOCATION_ID'];
    $CORPORATION_DATA['AUTHORIZE_CLIENT_KEY'] = $_POST['AUTHORIZE_CLIENT_KEY'];
    $CORPORATION_DATA['TRANSACTION_KEY'] = $_POST['TRANSACTION_KEY'];
    $CORPORATION_DATA['LOGIN_ID'] = $_POST['LOGIN_ID'];
    $CORPORATION_DATA['MERCHANT_ID'] = $_POST['MERCHANT_ID'];
    $CORPORATION_DATA['API_KEY'] = $_POST['API_KEY'];
    $CORPORATION_DATA['PUBLIC_API_KEY'] = $_POST['PUBLIC_API_KEY'];

    if (empty($_GET['id'])) {
        $CORPORATION_DATA['ACTIVE'] = 1;
        $CORPORATION_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $CORPORATION_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_CORPORATION', $CORPORATION_DATA, 'insert');
    } else {
        $CORPORATION_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $CORPORATION_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $CORPORATION_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_CORPORATION', $CORPORATION_DATA, 'update', " PK_CORPORATION =  '$_GET[id]'");
    }

    header("location:all_corporations.php");
}

if (!empty($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] == 'savecredit_cardData') {

    header("location:all_corporations.php");
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
                                <li class="breadcrumb-item"><a href="all_corporations.php">All Corporations</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-8">
                        <div class="card">
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="active"> <a class="nav-link active" id="corporation_tab_link" data-bs-toggle="tab" href="#corporation" role="tab"><span class="hidden-sm-up"><i class="ti-folder"></i></span> <span class="hidden-xs-down">Corporation </span></a> </li>
                                    <?php if (!empty($_GET['id'])) { ?>
                                        <li> <a class="nav-link" id="payment_register_tab_link" data-bs-toggle="tab" href="#payment_register" role="tab"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Payment Register</span></a> </li>
                                        <li> <a class="nav-link" data-bs-toggle="tab" href="#credit_card" role="tab" id="credit_card_tab" onclick="getSavedCreditCardList();"><span class="hidden-sm-up"><i class="ti-credit-card"></i></span> <span class="hidden-xs-down">Credit Card</span></a> </li>
                                    <?php } ?>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content tabcontent-border">
                                    <div class="tab-pane p-20 active" id="corporation" role="tabpanel">
                                        <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveCorporationData">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Corporation Name<span class="text-danger">*</span>
                                                </label>
                                                <div class="col-md-12">
                                                    <input type="text" id="CORPORATION_NAME" name="CORPORATION_NAME" class="form-control" placeholder="Enter Corporation Name" value="<?php echo $CORPORATION_NAME ?>">
                                                </div>
                                            </div>
                                            <div class="row" style="margin-bottom: 15px;">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Timezone<span class="text-danger">*</span></label>
                                                        <div class="col-md-12">
                                                            <select name="PK_TIMEZONE" id="PK_TIMEZONE" class="form-control" required>
                                                                <option value="">Select</option>
                                                                <?php $res_type = $db->Execute("SELECT * FROM DOA_TIMEZONE WHERE ACTIVE = 1 ORDER BY NAME ASC");
                                                                while (!$res_type->EOF) { ?>
                                                                    <option value="<?= $res_type->fields['PK_TIMEZONE'] ?>" <?php if ($res_type->fields['PK_TIMEZONE'] == $PK_TIMEZONE) echo 'selected="selected"'; ?>><?= $res_type->fields['NAME'] ?></option>
                                                                <?php $res_type->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Currency</label>
                                                        <div class="col-md-12">
                                                            <select name="PK_CURRENCY" id="PK_CURRENCY" class="form-control required-entry">
                                                                <?php $res_type = $db->Execute("SELECT * FROM `DOA_CURRENCY` WHERE `ACTIVE` = 1");
                                                                while (!$res_type->EOF) { ?>
                                                                    <option value="<?= $res_type->fields['PK_CURRENCY'] ?>" <?= ($res_type->fields['PK_CURRENCY'] == $PK_CURRENCY) ? 'selected' : '' ?>><?= $res_type->fields['CURRENCY_NAME'] . " (" . $res_type->fields['CURRENCY_SYMBOL'] . ")" ?></option>
                                                                <?php $res_type->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- <div class="row">
                                                 <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Username Prefix</label>
                                                        <div class="col-md-12">
                                                            <input type="hidden" name="OLD_USERNAME_PREFIX" id="OLD_USERNAME_PREFIX" value="<?php echo $USERNAME_PREFIX ?>">
                                                            <input type="text" id="USERNAME_PREFIX" name="USERNAME_PREFIX" class="form-control" placeholder="Username Prefix" value="<?php echo $USERNAME_PREFIX ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label for="example-text">Time Slot Interval</label>
                                                        <div>
                                                            <input type="text" id="TIME_SLOT_INTERVAL" name="TIME_SLOT_INTERVAL" class="form-control time-picker" placeholder="Enter Time Slot Interval" value="<?php echo $TIME_SLOT_INTERVAL ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Service Provider Title</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="SERVICE_PROVIDER_TITLE" name="SERVICE_PROVIDER_TITLE" class="form-control" placeholder="Enter Service Provider Title" value="<?php echo $SERVICE_PROVIDER_TITLE ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Operation Tab Title</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="OPERATION_TAB_TITLE" name="OPERATION_TAB_TITLE" class="form-control" placeholder="Enter Operation Tab Title" value="<?php echo $OPERATION_TAB_TITLE ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Enrollment Id Character</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="ENROLLMENT_ID_CHAR" name="ENROLLMENT_ID_CHAR" class="form-control" placeholder="Enrollment Id Character" value="<?php echo $ENROLLMENT_ID_CHAR ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Enrollment Id Number</label>
                                                        <div class="col-md-12">
                                                            <input type="number" id="ENROLLMENT_ID_NUM" name="ENROLLMENT_ID_NUM" class="form-control" placeholder="Enrollment Id Number" value="<?php echo $ENROLLMENT_ID_NUM ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Miscellaneous Id Character</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="MISCELLANEOUS_ID_CHAR" name="MISCELLANEOUS_ID_CHAR" class="form-control" placeholder="Miscellaneous Id Character" value="<?php echo $MISCELLANEOUS_ID_CHAR ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Miscellaneous Id Number</label>
                                                        <div class="col-md-12">
                                                            <input type="number" id="MISCELLANEOUS_ID_NUM" name="MISCELLANEOUS_ID_NUM" class="form-control" placeholder="Miscellaneous Id Number" value="<?php echo $MISCELLANEOUS_ID_NUM ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label" style="margin-bottom: 20px;">Send an Appointment Reminder Text message.</label><br>
                                                        <label style="margin-right: 70px;"><input type="radio" id="APPOINTMENT_REMINDER" name="APPOINTMENT_REMINDER" class="form-check-inline" value="1" <?= ($APPOINTMENT_REMINDER == '1') ? 'checked' : '' ?> onclick="showHourBox(this);">Yes</label>
                                                        <label style="margin-right: 70px;"><input type="radio" id="APPOINTMENT_REMINDER" name="APPOINTMENT_REMINDER" class="form-check-inline" value="0" <?= ($APPOINTMENT_REMINDER == '0') ? 'checked' : '' ?> onclick="showHourBox(this);">No</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 hour_box" id="yes" style="display: <?= ($APPOINTMENT_REMINDER == '1') ? '' : 'none' ?>;">
                                                    <div class="form-group">
                                                        <label class="form-label">How many hours before the appointment ?</label>
                                                        <input type="text" class="form-control" name="HOUR" value="<?= $HOUR ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Focusbiz API Key</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="FOCUSBIZ_API_KEY" name="FOCUSBIZ_API_KEY" class="form-control" placeholder="Enter Focusbiz API Key" value="<?php echo $FOCUSBIZ_API_KEY ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Sales Tax</label>
                                                        <div class="input-group">
                                                            <input type="text" id="SALES_TAX" name="SALES_TAX" class="form-control" placeholder="Enter Sales Tax" value="<?= $SALES_TAX ?>">
                                                            <span class="form-control input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div> -->

                                            <div class="row" style="margin-bottom: 15px; margin-top: 15px;">
                                                <div class="col-md-2">
                                                    <label class="form-label">Texting Feature Enabled?</label>
                                                </div>
                                                <div class="col-md-3">
                                                    <label><input type="radio" name="TEXTING_FEATURE_ENABLED" id="TEXTING_FEATURE_ENABLED" value="1" <? if ($TEXTING_FEATURE_ENABLED == 1) echo 'checked="checked"'; ?> onclick="showTwilioAccountSetting(this);" />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                    <label><input type="radio" name="TEXTING_FEATURE_ENABLED" id="TEXTING_FEATURE_ENABLED" value="0" <? if ($TEXTING_FEATURE_ENABLED == 0) echo 'checked="checked"'; ?> onclick="showTwilioAccountSetting(this);" />&nbsp;No</label>
                                                </div>
                                            </div>

                                            <div class="row twilio_account_type" id="twilio_account_type" style="display: <?= ($TEXTING_FEATURE_ENABLED == '1') ? '' : 'none' ?>; margin-bottom: 15px;">
                                                <div class="col-md-6">
                                                    <label><input type="radio" name="TWILIO_ACCOUNT_TYPE" id="TWILIO_ACCOUNT_TYPE_0" value="0" <? if ($TWILIO_ACCOUNT_TYPE == 0) echo 'checked="checked"'; ?> onclick="showTwilioSetting(this);" />&nbsp;Using Doable's Twilio account</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                    <label><input type="radio" name="TWILIO_ACCOUNT_TYPE" id="TWILIO_ACCOUNT_TYPE_1" value="1" <? if ($TWILIO_ACCOUNT_TYPE == 1) echo 'checked="checked"'; ?> onclick="showTwilioSetting(this);" />&nbsp;Using Your own Twilio Account</label>
                                                </div>
                                            </div>

                                            <div id="twilio_setting_div" class="row" style="display: <?= ($TEXTING_FEATURE_ENABLED == 1 && $TWILIO_ACCOUNT_TYPE == 1) ? '' : 'none' ?>; margin-top: 30px;">
                                                <b class="btn btn-light" style="margin-bottom: 20px;">Twilio Setting</b>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">SID</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="SID" name="SID" class="form-control" placeholder="Enter SID" value="<?php echo $SID ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Token</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="TOKEN" name="TOKEN" class="form-control" placeholder="Enter TOKEN" value="<?php echo $TOKEN ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Phone No.</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="TWILIO_PHONE_NO" name="TWILIO_PHONE_NO" class="form-control" placeholder="Enter Phone No." value="<?php echo $TWILIO_PHONE_NO ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                            <?php if ($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1) { ?>
                                                <div class="row" style="margin-top: 30px;">
                                                    <b class="btn btn-light" style="margin-bottom: 20px;">Electronic Connection to Merchant Service</b>

                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label class="form-label" style="margin-bottom: 20px;">Gateway Type</label><br>
                                                                <label style="margin-right: 30px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Stripe" <?= ($PAYMENT_GATEWAY_TYPE == 'Stripe') ? 'checked' : '' ?> onclick="showPaymentGateway(this);">Stripe</label>
                                                                <label style="margin-right: 30px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Square" <?= ($PAYMENT_GATEWAY_TYPE == 'Square') ? 'checked' : '' ?> onclick="showPaymentGateway(this);">Square</label>
                                                                <label style="margin-right: 30px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Authorized.net" <?= ($PAYMENT_GATEWAY_TYPE == 'Authorized.net') ? 'checked' : '' ?> onclick="showPaymentGateway(this);">Authorized.net</label>
                                                                <label style="margin-right: 30px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Clover" <?= ($PAYMENT_GATEWAY_TYPE == 'Clover') ? 'checked' : '' ?> onclick="showPaymentGateway(this);">Clover</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label class="form-label" style="margin-bottom: 20px;">Gateway Mode</label><br>
                                                                <label style="margin-right: 70px;"><input type="radio" id="GATEWAY_MODE" name="GATEWAY_MODE" class="form-check-inline" value="test" <?= ($GATEWAY_MODE == 'test' || $GATEWAY_MODE == null || $GATEWAY_MODE == '') ? 'checked' : '' ?>> Test</label>
                                                                <label style="margin-right: 70px;"><input type="radio" id="GATEWAY_MODE" name="GATEWAY_MODE" class="form-check-inline" value="live" <?= ($GATEWAY_MODE == 'live') ? 'checked' : '' ?>> Live</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row payment_gateway" id="stripe" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Stripe') ? '' : 'none' ?>;">
                                                        <div class="col-12">
                                                            <div class="form-group">
                                                                <label class="form-label">Secret Key</label>
                                                                <input type="text" class="form-control" name="SECRET_KEY" value="<?= $SECRET_KEY ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Publishable Key</label>
                                                                <input type="text" class="form-control" name="PUBLISHABLE_KEY" value="<?= $PUBLISHABLE_KEY ?>">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row payment_gateway" id="square" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Square') ? '' : 'none' ?>">
                                                        <div class="col-12">
                                                            <div class="form-group">
                                                                <label class="form-label">Application ID</label>
                                                                <input type="text" class="form-control" name="APP_ID" value="<?= $SQUARE_APP_ID ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Location ID</label>
                                                                <input type="text" class="form-control" name="LOCATION_ID" value="<?= $SQUARE_LOCATION_ID ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Access Token</label>
                                                                <input type="text" class="form-control" name="ACCESS_TOKEN" value="<?= $ACCESS_TOKEN ?>">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row payment_gateway" id="authorized" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Authorized.net') ? '' : 'none' ?>">
                                                        <div class="col-12">
                                                            <div class="form-group">
                                                                <label class="form-label">Login ID</label>
                                                                <input type="text" class="form-control" name="LOGIN_ID" value="<?= $LOGIN_ID ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Transaction Key</label>
                                                                <input type="text" class="form-control" name="TRANSACTION_KEY" value="<?= $TRANSACTION_KEY ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Authorize Client Key</label>
                                                                <input type="text" class="form-control" name="AUTHORIZE_CLIENT_KEY" value="<?= $AUTHORIZE_CLIENT_KEY ?>">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row payment_gateway" id="Clover" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Clover') ? '' : 'none' ?>;">
                                                        <div class="col-12">
                                                            <div class="form-group">
                                                                <label class="form-label">Merchant ID</label>
                                                                <input type="text" class="form-control" name="MERCHANT_ID" value="<?= $MERCHANT_ID ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Private Token</label>
                                                                <input type="text" class="form-control" name="API_KEY" value="<?= $API_KEY ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Public Token</label>
                                                                <input type="text" class="form-control" name="PUBLIC_API_KEY" value="<?= $PUBLIC_API_KEY ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>

                                            <?php if (!empty($_GET['id'])) { ?>
                                                <div class="row" style="margin-bottom: 15px;">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="form-label" style="margin-bottom: 10px;">Active</label><br>
                                                            <label style="margin-right: 30px;"><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                            <label style="margin-right: 30px;"><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <? } ?>
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                            <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_corporations.php'">Cancel</button>
                                        </form>
                                    </div>

                                    <div class="tab-pane p-20" id="payment_register" role="tabpanel" style="margin-top: 15px;">
                                        <h4 style="text-align: center; margin-bottom: 20px;">Payment History</h4>
                                        <table id="payment_table" class="table table-striped border">
                                            <thead>
                                                <tr>
                                                    <th style="text-align: center;">Date</th>
                                                    <th style="text-align: center;">Status</th>
                                                    <th style="text-align: center;">Amount</th>
                                                    <th style="text-align: center;">Info</th>
                                                    <th style="text-align: center;">For Location</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $corporation_payments = $db->Execute("SELECT DOA_PAYMENT_DETAILS.*, DOA_LOCATION.LOCATION_NAME FROM DOA_PAYMENT_DETAILS INNER JOIN DOA_LOCATION ON DOA_PAYMENT_DETAILS.PK_LOCATION = DOA_LOCATION.PK_LOCATION WHERE DOA_PAYMENT_DETAILS.PAYMENT_FROM = 'corporation' AND DOA_PAYMENT_DETAILS.PK_CORPORATION = " . $PK_CORPORATION . " ORDER BY DOA_PAYMENT_DETAILS.DATE_TIME DESC");
                                                if ($corporation_payments->RecordCount() > 0) {
                                                    while (!$corporation_payments->EOF) {
                                                        $payment_info = json_decode($corporation_payments->fields['PAYMENT_INFO']);
                                                        $payment_type = (isset($payment_info->LAST4)) ? 'Credit Card' . " # " . $payment_info->LAST4 : $corporation_payments->fields['PAYMENT_INFO']; ?>
                                                        <tr style="color : <?= ($corporation_payments->fields['PAYMENT_STATUS'] == 'Failed') ? 'red' : 'black' ?>">
                                                            <td style="text-align: center;"><?= date('m/d/Y h:i A', strtotime($corporation_payments->fields['DATE_TIME'])) ?></td>
                                                            <td style="text-align: center;"><?= $corporation_payments->fields['PAYMENT_STATUS'] ?></td>
                                                            <td style="text-align: center;">$<?= number_format($corporation_payments->fields['AMOUNT'], 2) ?></td>
                                                            <td style="text-align: center;"><?= $payment_type ?></td>
                                                            <td style="text-align: center;"><?= $corporation_payments->fields['LOCATION_NAME'] ?></td>
                                                        </tr>
                                                    <?php $corporation_payments->MoveNext();
                                                    } ?>
                                                <?php } else { ?>
                                                    <tr>
                                                        <td colspan="5" style="text-align: center;">No payment records found.</td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="tab-pane p-20" id="credit_card" role="tabpanel">
                                        <form class="form-material form-horizontal" id="credit_card_form" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="PK_CORPORATION" id="PK_CORPORATION" value="<?= $PK_CORPORATION ?>">
                                            <input type="hidden" name="FROM" value="corporation">
                                            <div class="p-20">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <input type="hidden" name="PAYMENT_METHOD_ID" id="PAYMENT_METHOD_ID" value="">
                                                        <?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe') { ?>
                                                            <input type="hidden" name="stripe_token" id="stripe_token" value="">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="form-group" id="card_div"></div>
                                                                </div>
                                                            </div>
                                                        <?php } elseif ($SA_PAYMENT_GATEWAY_TYPE == 'Square') { ?>
                                                            <input type="hidden" name="square_token" id="square_token" value="">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div id="payment-card-container"></div>
                                                                    <div id="payment-status-container"></div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                        <div class="row" id="corporation_payment_status"></div>
                                                        <div class="form-group">
                                                            <button type="submit" id="corporation-pay-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Save</button>
                                                        </div>
                                                        <div id="card_list_div" style="margin-bottom: 25px;"></div>
                                                    </div>
                                                </div>
                                        </form>
                                    </div>

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
</body>

</html>

<script>
    $('.datepicker-past').datepicker({
        format: 'mm/dd/yyyy',
        maxDate: 0
    });

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    function showHourBox(radio) {
        if (radio.value == 1) {
            document.getElementById("yes").style.display = "block";
        } else {
            document.getElementById("yes").style.display = "none";
        }
    }

    function showTwilioAccountSetting(param) {
        if ($(param).val() === '1') {
            $('#twilio_account_type').slideDown();
        } else {
            $('#twilio_account_type').slideUp();
            $('#TWILIO_ACCOUNT_TYPE_0').prop('checked', true);
            $('#twilio_setting_div').slideUp();
        }
    }

    function showTwilioSetting(param) {
        if ($(param).val() === '1') {
            $('#twilio_setting_div').slideDown();
        } else {
            $('#twilio_setting_div').slideUp();
        }
    }

    function showPaymentGateway(radio) {
        $('.payment_gateway').slideUp();
        if (radio.value == 'Stripe') {
            document.getElementById('stripe').style.display = 'block';
        } else if (radio.value == 'Square') {
            document.getElementById('square').style.display = 'block';
        } else if (radio.value == 'Authorized.net') {
            document.getElementById('authorized').style.display = 'block';
        } else if (radio.value == 'Clover') {
            document.getElementById('Clover').style.display = 'block';
        }
    }

    function getSavedCreditCardList() {
        let payment_gateway_type = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
        if (payment_gateway_type == 'Square') {
            squarePaymentFunction();
        } else if (payment_gateway_type == 'Stripe') {
            stripePaymentFunction();
        }
        $.ajax({
            url: "ajax/get_credit_card_list_from_master.php",
            type: 'POST',
            data: {
                PK_VALUE: '<?= $PK_CORPORATION  ?>',
                class: 'corporation'
            },
            success: function(data) {
                $('#card_list_div').slideDown().html(data);
            }
        });
    }
</script>


<?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe') { ?>
    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript">
        var stripe = Stripe('<?= $SA_PUBLISHABLE_KEY ?>');
        var elements = stripe.elements();

        var style = {
            base: {
                height: '34px',
                padding: '6px 12px',
                fontSize: '14px',
                lineHeight: '1.42857143',
                color: '#555',
                backgroundColor: '#fff',
                border: '1px solid #ccc',
                borderRadius: '4px',
                '::placeholder': {
                    color: '#ddd'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };

        // Create an instance of the card Element.
        var stripe_card = elements.create('card', {
            style: style
        });
        var pay_type = '';

        function stripePaymentFunction() {
            if (($('#card-element')).length > 0) {
                stripe_card.mount('#card-element');
            }
            // Handle real-time validation errors from the card Element.
            stripe_card.addEventListener('change', function(event) {
                var displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                    addStripeTokenOnForm();
                }
            });
        }

        function addStripeTokenOnForm() {
            //event.preventDefault();
            stripe.createToken(stripe_card).then(function(result) {
                if (result.error) {
                    // Inform the user if there was an error.
                    let errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    // Send the token to your server.
                    $('#stripe_token').val(result.token.id);
                }
            });
        }
    </script>
<?php } ?>

<?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Square') {
    if ($SA_GATEWAY_MODE == 'live')
        $SQ_URL = "https://connect.squareup.com";
    else
        $SQ_URL = "https://connect.squareupsandbox.com";

    if ($SA_GATEWAY_MODE == 'live')
        $URL = "https://web.squarecdn.com/v1/square.js";
    else
        $URL = "https://sandbox.web.squarecdn.com/v1/square.js";
?>
    <script src="<?= $URL ?>"></script>
    <script type="text/javascript">
        let square_card;

        async function squarePaymentFunction() {
            let square_appId = '<?= $SA_SQUARE_APP_ID ?>';
            let square_locationId = '<?= $SA_SQUARE_LOCATION_ID ?>';
            const payments = Square.payments(square_appId, square_locationId);
            square_card = await payments.card();
            $('#payment-card-container').text('');
            await square_card.attach('#payment-card-container');
        }

        async function addSquareTokenOnForm() {
            const statusContainer = document.getElementById('payment-status-container');

            try {
                // Tokenize the card details
                const result = await square_card.tokenize();
                if (result.status === 'OK') {
                    // Add the token to the hidden input field
                    $('#square_token').val(result.token);
                } else {
                    // Handle tokenization errors
                    let errorMessage = `Tokenization failed with status: ${result.status}`;
                    if (result.errors) {
                        errorMessage += ` and errors: ${JSON.stringify(result.errors)}`;
                    }
                    throw new Error(errorMessage);
                }
            } catch (e) {
                console.error(e);
                statusContainer.innerHTML = `<p class="alert alert-danger">Payment Failed: ${e.message}</p>`;
            }
        }
    </script>
<?php } ?>

<script>
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    $(document).on('submit', '#credit_card_form', function(event) {
        $('#corporation-pay-button').prop('disabled', true);
        $('#corporation-pay-button').html(`<span class="spinner-border spinner-border-sm"></span> Processing...`);
        event.preventDefault();
        let PAYMENT_GATEWAY = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
        if (PAYMENT_GATEWAY == 'Square') {
            let PAYMENT_METHOD_ID = $('#PAYMENT_METHOD_ID').val();
            if (PAYMENT_METHOD_ID == '') {
                addSquareTokenOnForm();
                sleep(3000).then(() => {
                    submitCreditCardForm();
                });
            } else {
                submitCreditCardForm();
            }
        } else {
            submitCreditCardForm();
        }
    });

    function submitCreditCardForm() {
        let form_data = $('#credit_card_form').serialize();
        $.ajax({
            url: "includes/save_corporation_credit_card.php",
            type: 'POST',
            data: form_data,
            dataType: 'json',
            success: function(data) {
                if (data.STATUS == false) {
                    $('#corporation_payment_status').html(`<p class="alert alert-danger">${data.MESSAGE}</p>`);
                    $('#corporation-pay-button').prop('disabled', false);
                    $('#corporation-pay-button').html(`Save`);
                } else {
                    $('#corporation_payment_status').html(`<p class="alert alert-success">Credit Card Successfully Saved.</p>`);

                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                }
            }
        });
    }
</script>