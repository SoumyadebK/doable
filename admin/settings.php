<?php

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "Settings";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2){
    header("location:../login.php");
    exit;
}

$res = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
if($res->RecordCount() == 0){
    header("location:login.php");
    exit;
}
$PK_BUSINESS_TYPE   = $res->fields['PK_BUSINESS_TYPE'];
$API_KEY  	        = $res->fields['API_KEY'];
$FRANCHISE          = $res->fields['FRANCHISE'];
$BUSINESS_NAME 	    = $res->fields['BUSINESS_NAME'];
$BUSINESS_LOGO      = $res->fields['BUSINESS_LOGO'];
$ADDRESS 	        = $res->fields['ADDRESS'];
$ADDRESS_1          = $res->fields['ADDRESS_1'];
$CITY  	            = $res->fields['CITY'];
$PK_STATES 	        = $res->fields['PK_STATES'];
$ZIP 	            = $res->fields['ZIP'];
$PK_COUNTRY  	    = $res->fields['PK_COUNTRY'];
$PHONE 	            = $res->fields['PHONE'];
$FAX 	            = $res->fields['FAX'];
$EMAIL              = $res->fields['EMAIL'];
$WEBSITE  	        = $res->fields['WEBSITE'];
$PK_ACCOUNT_TYPE    = $res->fields['PK_ACCOUNT_TYPE'];
$PK_TIMEZONE        = $res->fields['PK_TIMEZONE'];
$ACTIVE             = $res->fields['ACTIVE'];
$SERVICE_PROVIDER_TITLE = $res->fields['SERVICE_PROVIDER_TITLE'];
$OPERATION_TAB_TITLE = $res->fields['OPERATION_TAB_TITLE'];
$PK_CURRENCY            = $res->fields['PK_CURRENCY'];
$ENROLLMENT_ID_CHAR     = $res->fields['ENROLLMENT_ID_CHAR'];
$ENROLLMENT_ID_NUM      = $res->fields['ENROLLMENT_ID_NUM'];
$MISCELLANEOUS_ID_CHAR = $res->fields['MISCELLANEOUS_ID_CHAR'];
$MISCELLANEOUS_ID_NUM = $res->fields['MISCELLANEOUS_ID_NUM'];
$PAYMENT_GATEWAY_TYPE   = $res->fields['PAYMENT_GATEWAY_TYPE'];
$SECRET_KEY             = $res->fields['SECRET_KEY'];
$PUBLISHABLE_KEY        = $res->fields['PUBLISHABLE_KEY'];
$ACCESS_TOKEN           = $res->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID          = $res->fields['APP_ID'];
$SQUARE_LOCATION_ID     = $res->fields['LOCATION_ID'];
$LOGIN_ID               = $res->fields['LOGIN_ID'];
$TRANSACTION_KEY        = $res->fields['TRANSACTION_KEY'];
$AUTHORIZE_CLIENT_KEY   = $res->fields['AUTHORIZE_CLIENT_KEY'];
$APPOINTMENT_REMINDER = $res->fields['APPOINTMENT_REMINDER'];
$HOUR = $res->fields['HOUR'];
$USERNAME_PREFIX = $res->fields['USERNAME_PREFIX'];
$TIME_SLOT_INTERVAL = $res->fields['TIME_SLOT_INTERVAL'];

$FRANCHISE = $res->fields['FRANCHISE'];
$AM_USER_NAME = $res->fields['AM_USER_NAME'];
$AM_PASSWORD = $res->fields['AM_PASSWORD'];
$AM_REFRESH_TOKEN = $res->fields['AM_REFRESH_TOKEN'];

$TEXTING_FEATURE_ENABLED = $res->fields['TEXTING_FEATURE_ENABLED'];
$TWILIO_ACCOUNT_TYPE  = $res->fields['TWILIO_ACCOUNT_TYPE'];

$SID = '';
$TOKEN = '';
$PHONE_NO = '';

$text = $db->Execute( "SELECT * FROM `DOA_TEXT_SETTINGS` WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
if ($text->RecordCount() > 0) {
    $SID = $text->fields['SID'];
    $TOKEN = $text->fields['TOKEN'];
    $PHONE_NO = $text->fields['FROM_NO'];
}

$SMTP_HOST = '';
$SMTP_PORT = '';
$SMTP_USERNAME = '';
$SMTP_PASSWORD = '';
$email = $db_account->Execute("SELECT * FROM DOA_EMAIL_ACCOUNT WHERE PK_LOCATION = 0");
if ($email->RecordCount() > 0) {
    $SMTP_HOST = $email->fields['HOST'];
    $SMTP_PORT = $email->fields['PORT'];
    $SMTP_USERNAME = $email->fields['USER_NAME'];
    $SMTP_PASSWORD = $email->fields['PASSWORD'];
}

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'settings'");
if($help->RecordCount() > 0){
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
}

$user_data = $db->Execute("SELECT DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_data->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];

$payment_gateway_setting = $db->Execute( "SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");
$STRIPE_SECRET_KEY = $payment_gateway_setting->fields['SECRET_KEY'];
$STRIPE_PUBLISHABLE_KEY = $payment_gateway_setting->fields['PUBLISHABLE_KEY'];

require_once("../global/stripe-php-master/init.php");
$stripe = new StripeClient($STRIPE_SECRET_KEY);
$account_payment_info = $db->Execute("SELECT * FROM DOA_ACCOUNT_PAYMENT_INFO WHERE PK_LOCATION IS NULL AND PAYMENT_TYPE = 'Stripe' AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
if ($account_payment_info->RecordCount() > 0) {
    $customer_id = $account_payment_info->fields['ACCOUNT_PAYMENT_ID'];
    $stripe_customer = $stripe->customers->retrieve($customer_id);
    $card_id = $stripe_customer->default_source;

    $url = "https://api.stripe.com/v1/customers/".$customer_id."/cards/".$card_id;
    $AUTH = "Authorization: Bearer ".$STRIPE_SECRET_KEY;

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            $AUTH
        ),
    ));

    $response = curl_exec($curl);
    $card_details = json_decode($response, true);
    //pre_r($card_details);
}

if(!empty($_POST)){
    if ($_POST['FUNCTION_NAME'] == 'saveSettingsData') {
        $SETTINGS_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $SETTINGS_DATA['PK_TIMEZONE'] = $_POST['PK_TIMEZONE'];
        $SETTINGS_DATA['USERNAME_PREFIX'] = $_POST['USERNAME_PREFIX'];
        $SETTINGS_DATA['TIME_SLOT_INTERVAL'] = $_POST['TIME_SLOT_INTERVAL'];
        $SETTINGS_DATA['SERVICE_PROVIDER_TITLE'] = $_POST['SERVICE_PROVIDER_TITLE'];
        $SETTINGS_DATA['OPERATION_TAB_TITLE'] = $_POST['OPERATION_TAB_TITLE'];
        $SETTINGS_DATA['PK_CURRENCY'] = $_POST['PK_CURRENCY'];
        $SETTINGS_DATA['ENROLLMENT_ID_CHAR'] = $_POST['ENROLLMENT_ID_CHAR'];
        $SETTINGS_DATA['ENROLLMENT_ID_NUM'] = $_POST['ENROLLMENT_ID_NUM'];
        $SETTINGS_DATA['MISCELLANEOUS_ID_CHAR'] = $_POST['MISCELLANEOUS_ID_CHAR'];
        $SETTINGS_DATA['MISCELLANEOUS_ID_NUM'] = $_POST['MISCELLANEOUS_ID_NUM'];
        $SETTINGS_DATA['PAYMENT_GATEWAY_TYPE'] = $_POST['PAYMENT_GATEWAY_TYPE'];
        $SETTINGS_DATA['SECRET_KEY'] = $_POST['SECRET_KEY'];
        $SETTINGS_DATA['PUBLISHABLE_KEY'] = $_POST['PUBLISHABLE_KEY'];
        $SETTINGS_DATA['ACCESS_TOKEN'] = $_POST['ACCESS_TOKEN'];
        $SETTINGS_DATA['APP_ID'] = $_POST['APP_ID'];
        $SETTINGS_DATA['LOCATION_ID'] = $_POST['LOCATION_ID'];
        $SETTINGS_DATA['AUTHORIZE_CLIENT_KEY'] = $_POST['AUTHORIZE_CLIENT_KEY'];
        $SETTINGS_DATA['TRANSACTION_KEY'] = $_POST['TRANSACTION_KEY'];
        $SETTINGS_DATA['LOGIN_ID'] = $_POST['LOGIN_ID'];
        $SETTINGS_DATA['APPOINTMENT_REMINDER'] = $_POST['APPOINTMENT_REMINDER'];
        $SETTINGS_DATA['HOUR'] = empty($_POST['HOUR']) ? 0 : $_POST['HOUR'];
        $SETTINGS_DATA['AM_USER_NAME'] = $_POST['AM_USER_NAME'];
        $SETTINGS_DATA['AM_PASSWORD'] = $_POST['AM_PASSWORD'];
        $SETTINGS_DATA['AM_REFRESH_TOKEN'] = $_POST['AM_REFRESH_TOKEN'];
        $SETTINGS_DATA['ACTIVE'] = 1;
        $SETTINGS_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $SETTINGS_DATA['CREATED_ON'] = date("Y-m-d H:i");

        $settings = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        if ($settings->RecordCount() == 0) {
            db_perform('DOA_ACCOUNT_MASTER', $SETTINGS_DATA, 'insert');
        } else {
            $SETTINGS_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $SETTINGS_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_ACCOUNT_MASTER', $SETTINGS_DATA, 'update', " PK_ACCOUNT_MASTER =  '$_SESSION[PK_ACCOUNT_MASTER]'");
        }

        $TWILIO_SETTING_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $TWILIO_SETTING_DATA['SID'] = $_POST['SID'];
        $TWILIO_SETTING_DATA['TOKEN'] = $_POST['TOKEN'];
        $TWILIO_SETTING_DATA['FROM_NO'] = $_POST['PHONE_NO'];
        $TWILIO_SETTING_DATA['ACTIVE'] = 1;
        $TWILIO_SETTING_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $TWILIO_SETTING_DATA['CREATED_ON'] = date("Y-m-d H:i");

        $twilio_data = $db->Execute("SELECT * FROM DOA_TEXT_SETTINGS WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        if ($twilio_data->RecordCount() == 0) {
            db_perform('DOA_TEXT_SETTINGS', $TWILIO_SETTING_DATA, 'insert');
        } else {
            $TWILIO_SETTING_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $TWILIO_SETTING_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_TEXT_SETTINGS', $TWILIO_SETTING_DATA, 'update', " PK_ACCOUNT_MASTER =  '$_SESSION[PK_ACCOUNT_MASTER]'");
        }

        //$EMAIL_ACCOUNT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $EMAIL_ACCOUNT_DATA['PK_LOCATION'] = 0;
        $EMAIL_ACCOUNT_DATA['HOST'] = $_POST['SMTP_HOST'];
        $EMAIL_ACCOUNT_DATA['PORT'] = $_POST['SMTP_PORT'];
        $EMAIL_ACCOUNT_DATA['USER_NAME'] = $_POST['SMTP_USERNAME'];
        $EMAIL_ACCOUNT_DATA['PASSWORD'] = $_POST['SMTP_PASSWORD'];
        $EMAIL_ACCOUNT_DATA['ACTIVE'] = 1;
        $EMAIL_ACCOUNT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_ACCOUNT_DATA['CREATED_ON'] = date("Y-m-d H:i");

        $email_data = $db_account->Execute("SELECT * FROM DOA_EMAIL_ACCOUNT WHERE PK_LOCATION = 0");
        if ($email_data->RecordCount() == 0) {
            db_perform_account('DOA_EMAIL_ACCOUNT', $EMAIL_ACCOUNT_DATA, 'insert');
        } else {
            $EMAIL_ACCOUNT_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $EMAIL_ACCOUNT_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_EMAIL_ACCOUNT', $EMAIL_ACCOUNT_DATA, 'update', " PK_LOCATION = 0");
        }
    }
    header("location:settings.php");
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Settings page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    #advice-required-entry-ACCEPT_HANDLING{width: 150px;top: 20px;position: absolute;}
    .StripeElement {
        display: block;
        width: 100%;
        height: 34px;
        padding: 6px 12px;
        font-size: 14px;
        line-height: 1.42857143;
        color: #555;
        background-color: #fff;
        background-image: none;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .StripeElement--focus {
        box-shadow: 0 1px 3px 0 #cfd7df;
    }

    .StripeElement--invalid {
        border-color: #fa755a;
    }

    .StripeElement--webkit-autofill {
        background-color: #fefde5 !important;
    }
</style>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <?php require_once('../includes/setup_menu.php') ?>
        <div class="container-fluid body_content m-0">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="row" style="text-align: center;">
                                <h5 style="font-weight: bold;"><?=$header_text?></h5>
                            </div>
                            <!-- Tab panes -->
                            <div class="tab-content tabcontent-border">
                                <div class="tab-pane active" id="settings" role="tabpanel">

                                    <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveSettingsData">
                                        <div class="p-20">
                                            <div class="row" style="margin-bottom: 15px;">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Timezone<span class="text-danger">*</span></label>
                                                        <div class="col-md-12">
                                                            <select name="PK_TIMEZONE" id="PK_TIMEZONE" class="form-control" required>
                                                                <option value="">Select</option>
                                                                <?php $res_type = $db->Execute("SELECT * FROM DOA_TIMEZONE WHERE ACTIVE = 1 ORDER BY NAME ASC");
                                                                while (!$res_type->EOF) { ?>
                                                                    <option value="<?=$res_type->fields['PK_TIMEZONE']?>" <?php if($res_type->fields['PK_TIMEZONE'] == $PK_TIMEZONE) echo 'selected="selected"'; ?>><?=$res_type->fields['NAME']?></option>
                                                                    <?php	$res_type->MoveNext();
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
                                                                    <option value="<?=$res_type->fields['PK_CURRENCY']?>" <?=($res_type->fields['PK_CURRENCY'] == $PK_CURRENCY)?'selected':''?>><?=$res_type->fields['CURRENCY_NAME']." (".$res_type->fields['CURRENCY_SYMBOL'].")"?></option>
                                                                <?php	$res_type->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Username Prefix</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="USERNAME_PREFIX" name="USERNAME_PREFIX" class="form-control" placeholder="Username Prefix" value="<?php echo $USERNAME_PREFIX?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label for="example-text">Time Slot Interval</label>
                                                        <div>
                                                            <input type="text" id="TIME_SLOT_INTERVAL" name="TIME_SLOT_INTERVAL" class="form-control time-picker" placeholder="Enter Time Slot Interval" value="<?php echo $TIME_SLOT_INTERVAL?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Service Provider Title</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="SERVICE_PROVIDER_TITLE" name="SERVICE_PROVIDER_TITLE" class="form-control" placeholder="Enter Service Provider Title" value="<?php echo $SERVICE_PROVIDER_TITLE?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Operation Tab Title</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="OPERATION_TAB_TITLE" name="OPERATION_TAB_TITLE" class="form-control" placeholder="Enter Operation Tab Title" value="<?php echo $OPERATION_TAB_TITLE?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Enrollment Id Character</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="ENROLLMENT_ID_CHAR" name="ENROLLMENT_ID_CHAR" class="form-control" placeholder="Enrollment Id Character" value="<?php echo $ENROLLMENT_ID_CHAR?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Enrollment Id Number</label>
                                                        <div class="col-md-12">
                                                            <input type="number" id="ENROLLMENT_ID_NUM" name="ENROLLMENT_ID_NUM" class="form-control" placeholder="Enrollment Id Number" value="<?php echo $ENROLLMENT_ID_NUM?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Miscellaneous Id Character</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="MISCELLANEOUS_ID_CHAR" name="MISCELLANEOUS_ID_CHAR" class="form-control" placeholder="Miscellaneous Id Character" value="<?php echo $MISCELLANEOUS_ID_CHAR?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Miscellaneous Id Number</label>
                                                        <div class="col-md-12">
                                                            <input type="number" id="MISCELLANEOUS_ID_NUM" name="MISCELLANEOUS_ID_NUM" class="form-control" placeholder="Miscellaneous Id Number" value="<?php echo $MISCELLANEOUS_ID_NUM?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label" style="margin-bottom: 20px;">Send an Appointment Reminder Text message.</label><br>
                                                        <label style="margin-right: 70px;"><input type="radio" id="APPOINTMENT_REMINDER" name="APPOINTMENT_REMINDER" class="form-check-inline" value="1" <?=($APPOINTMENT_REMINDER=='1')?'checked':''?> onclick="showHourBox(this);">Yes</label>
                                                        <label style="margin-right: 70px;"><input type="radio" id="APPOINTMENT_REMINDER" name="APPOINTMENT_REMINDER" class="form-check-inline" value="0" <?=($APPOINTMENT_REMINDER=='0')?'checked':''?> onclick="showHourBox(this);">No</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 hour_box" id="yes" style="display: <?=($APPOINTMENT_REMINDER=='1')?'':'none'?>;">
                                                    <div class="form-group">
                                                        <label class="form-label">How many hours before the appointment ?</label>
                                                        <input type="text" class="form-control" name="HOUR" value="<?=$HOUR?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if($TEXTING_FEATURE_ENABLED == 1 && $TWILIO_ACCOUNT_TYPE == 1) { ?>
                                                <div class="row" style="margin-top: 30px;">
                                                    <b class="btn btn-light" style="margin-bottom: 20px;">Twilio Setting</b>
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">SID</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="SID" name="SID" class="form-control" placeholder="Enter SID" value="<?php echo $SID?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Token</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="TOKEN" name="TOKEN" class="form-control" placeholder="Enter TOKEN" value="<?php echo $TOKEN?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Phone No.</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="PHONE_NO" name="PHONE_NO" class="form-control" placeholder="Enter Phone No." value="<?php echo $PHONE_NO?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>

                                            <?php if ($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1) { ?>
                                                <div class="row" style="margin-top: 30px;">
                                                    <b class="btn btn-light" style="margin-bottom: 20px;">Payment Gateway Setting</b>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Stripe" <?=($PAYMENT_GATEWAY_TYPE=='Stripe')?'checked':''?> onclick="showPaymentGateway(this);">Stripe</label>
                                                                <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Square" <?=($PAYMENT_GATEWAY_TYPE=='Square')?'checked':''?> onclick="showPaymentGateway(this);">Square</label>
                                                                <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Authorized.net" <?=($PAYMENT_GATEWAY_TYPE=='Authorized.net')?'checked':''?> onclick="showPaymentGateway(this);">Authorized.net</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row payment_gateway" id="stripe" style="display: <?=($PAYMENT_GATEWAY_TYPE=='Stripe')?'':'none'?>;">
                                                        <div class="col-12">
                                                            <div class="form-group">
                                                                <label class="form-label">Secret Key</label>
                                                                <input type="text" class="form-control" name="SECRET_KEY" value="<?=$SECRET_KEY?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Publishable Key</label>
                                                                <input type="text" class="form-control" name="PUBLISHABLE_KEY" value="<?=$PUBLISHABLE_KEY?>">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row payment_gateway" id="square" style="display: <?=($PAYMENT_GATEWAY_TYPE=='Square')?'':'none'?>">
                                                        <div class="col-12">
                                                            <div class="form-group">
                                                                <label class="form-label">Application ID</label>
                                                                <input type="text" class="form-control" name="APP_ID" value="<?=$SQUARE_APP_ID?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Location ID</label>
                                                                <input type="text" class="form-control" name="LOCATION_ID" value="<?=$SQUARE_LOCATION_ID?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Access Token</label>
                                                                <input type="text" class="form-control" name="ACCESS_TOKEN" value="<?=$ACCESS_TOKEN?>">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row payment_gateway" id="authorized" style="display: <?=($PAYMENT_GATEWAY_TYPE=='Authorized.net')?'':'none'?>">
                                                        <div class="col-12">
                                                            <div class="form-group">
                                                                <label class="form-label">Login ID</label>
                                                                <input type="text" class="form-control" name="LOGIN_ID" value="<?=$LOGIN_ID?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Transaction Key</label>
                                                                <input type="text" class="form-control" name="TRANSACTION_KEY" value="<?=$TRANSACTION_KEY?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Authorize Client Key</label>
                                                                <input type="text" class="form-control" name="AUTHORIZE_CLIENT_KEY" value="<?=$AUTHORIZE_CLIENT_KEY?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>

                                            <div class="row" style="margin-top: 30px;">
                                                <b class="btn btn-light" style="margin-bottom: 20px;">SMTP Setup</b>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">SMTP HOST</label>
                                                        <input type="text" class="form-control" name="SMTP_HOST" value="<?=$SMTP_HOST?>">
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">SMTP PORT</label>
                                                        <input type="text" class="form-control" name="SMTP_PORT" value="<?=$SMTP_PORT?>">
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">SMTP USERNAME</label>
                                                        <input type="text" class="form-control" name="SMTP_USERNAME" value="<?=$SMTP_USERNAME?>">
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="form-label">SMTP PASSWORD</label>
                                                        <input type="text" class="form-control" name="SMTP_PASSWORD" value="<?=$SMTP_PASSWORD?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if($FRANCHISE == 1) { ?>
                                                <div class="row" style="margin-top: 30px;">
                                                    <b class="btn btn-light" style="margin-bottom: 20px;">Arthur Murray API Setup</b>
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="form-label">User Name</label>
                                                            <input type="text" class="form-control" name="AM_USER_NAME" value="<?=$AM_USER_NAME?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="form-label">Password</label>
                                                            <input type="text" class="form-control" name="AM_PASSWORD" value="<?=$AM_PASSWORD?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="form-label">Refresh Token</label>
                                                            <input type="text" class="form-control" name="AM_REFRESH_TOKEN" value="<?=$AM_REFRESH_TOKEN?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>

                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                            <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='business_profile.php'">Cancel</button>
                                        </div>
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
                                    <?=$help_title?>
                                </h4>
                                <div class="col-md-12">
                                    <text class="required-entry rich" id="DESCRIPTION"><?=$help_description?></text>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php');?>
    <script>
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        function removeThis(param) {
            $(param).closest('.row').remove();
        }

        function addMoreHoliday(){
            $('#holiday_list_div').append(`<div class="row">
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <input type="text" name="HOLIDAY_DATE[]" class="form-control datepicker-normal">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <input type="text" name="HOLIDAY_NAME[]" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3" style="padding-top: 5px;">
                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                </div>
                                            </div>`);

            $('.datepicker-normal').datepicker({
                format: 'mm/dd/yyyy',
            });
        }

        function showPaymentGateway(param) {
            $('.payment_gateway').slideUp();
            if($(param).val() === 'Stripe'){
                $('#stripe').slideDown();
            }else {
                if($(param).val() === 'Square'){
                    $('#square').slideDown();
                }else {
                    if($(param).val() === 'Authorized.net'){
                        $('#authorized').slideDown();
                    }
                }

            }
        }

        function showHourBox(param) {
            $('.hour_box').slideUp();
            if ($(param).val() === '1') {
                $('#yes').slideDown();
            }
        }
    </script>
    <script>
        $('.time-picker').timepicker({
            timeFormat: 'HH:mm:ss',
            interval: 5,
            minTime: '00',
            maxTime: '00:60:00',
            //defaultTime: '11',
            startTime: '00:00:00',
            dynamic: false,
            dropdown: true,
            scrollbar: true
        });
    </script>

    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript">
        function stripePaymentFunction() {
            let stripe = Stripe('<?=$STRIPE_PUBLISHABLE_KEY?>');
            let elements = stripe.elements();

            let style = {
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
            let card = elements.create('card', {style: style});

            if (($('#card-element')).length > 0) {
                card.mount('#card-element');
            }

            // Handle real-time validation errors from the card Element.
            card.addEventListener('change', function (event) {
                let displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            // Handle form submission.
            let form = document.getElementById('creditCardForm');
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                stripe.createToken(card).then(function (result) {
                    if (result.error) {
                        // Inform the user if there was an error.
                        let errorElement = document.getElementById('card-errors');
                        errorElement.textContent = result.error.message;
                    } else {
                        // Send the token to your server.
                        stripeTokenHandler(result.token);
                    }
                });
            });

            // Submit the form with the token ID.
            function stripeTokenHandler(token) {
                // Insert the token ID into the form, so it gets submitted to the server
                let form = document.getElementById('creditCardForm');
                let hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'token');
                hiddenInput.setAttribute('value', token.id);
                form.appendChild(hiddenInput);
                form.submit();
            }
        }
    </script>
</body>
</html>