<?php

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Corporation";
else
    $title = "Edit Corporation";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

// if(!empty($_POST)){
//     $CORPORATION_DATA = $_POST;
//     if(empty($_GET['id'])){
//         $CORPORATION_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
//         $CORPORATION_DATA['ACTIVE'] = 1;
//         $CORPORATION_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
//         $CORPORATION_DATA['CREATED_ON']  = date("Y-m-d H:i");
//         db_perform('DOA_CORPORATION', $CORPORATION_DATA, 'insert');
//     }else{
//         $CORPORATION_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
//         $CORPORATION_DATA['ACTIVE'] = $_POST['ACTIVE'];
//         $CORPORATION_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
//         $CORPORATION_DATA['EDITED_ON'] = date("Y-m-d H:i");
//         pre_r(db_perform('DOA_CORPORATION', $CORPORATION_DATA, 'update'," PK_CORPORATION =  '$_GET[id]'"));die();
//         db_perform('DOA_CORPORATION', $CORPORATION_DATA, 'update'," PK_CORPORATION =  '$_GET[id]'");
//     }
//     header("location:all_corporations.php");
// }

if(!empty($_POST)){
    unset($_SESSION['mail_error']);
    unset($_SESSION['error']);
    $OLD_USERNAME_PREFIX = $_POST['OLD_USERNAME_PREFIX'];
    $USERNAME_PREFIX = $_POST['USERNAME_PREFIX'];
    if ($OLD_USERNAME_PREFIX != $USERNAME_PREFIX) {
        $account_data = $db->Execute("SELECT USERNAME_PREFIX FROM DOA_ACCOUNT_MASTER WHERE USERNAME_PREFIX = '$USERNAME_PREFIX'");
        if ($account_data->RecordCount() > 0 && $account_data->fields['USERNAME_PREFIX'] != null) {
            $_SESSION['error'] .= $USERNAME_PREFIX." Username Prefix already exists. Please use a different Username Prefix.";
            header("location:corporation.php"); exit();
        }
    }
    $CORPORATION_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $CORPORATION_DATA['CORPORATION_NAME'] = $_POST['CORPORATION_NAME'];
    $CORPORATION_DATA['PK_TIMEZONE'] = $_POST['PK_TIMEZONE'];
    $CORPORATION_DATA['USERNAME_PREFIX'] = $USERNAME_PREFIX;
    $CORPORATION_DATA['TIME_SLOT_INTERVAL'] = $_POST['TIME_SLOT_INTERVAL'];
    $CORPORATION_DATA['SERVICE_PROVIDER_TITLE'] = $_POST['SERVICE_PROVIDER_TITLE'];
    $CORPORATION_DATA['OPERATION_TAB_TITLE'] = $_POST['OPERATION_TAB_TITLE'];
    $CORPORATION_DATA['PK_CURRENCY'] = $_POST['PK_CURRENCY'];
    $CORPORATION_DATA['ENROLLMENT_ID_CHAR'] = $_POST['ENROLLMENT_ID_CHAR'];
    $CORPORATION_DATA['ENROLLMENT_ID_NUM'] = $_POST['ENROLLMENT_ID_NUM'];
    $CORPORATION_DATA['MISCELLANEOUS_ID_CHAR'] = $_POST['MISCELLANEOUS_ID_CHAR'];
    $CORPORATION_DATA['MISCELLANEOUS_ID_NUM'] = $_POST['MISCELLANEOUS_ID_NUM'];
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
    $CORPORATION_DATA['APPOINTMENT_REMINDER'] = $_POST['APPOINTMENT_REMINDER'];
    $CORPORATION_DATA['HOUR'] = empty($_POST['HOUR']) ? 0 : $_POST['HOUR'];
    $CORPORATION_DATA['AM_USER_NAME'] = $_POST['AM_USER_NAME'];
    $CORPORATION_DATA['AM_PASSWORD'] = $_POST['AM_PASSWORD'];
    //$CORPORATION_DATA['AM_REFRESH_TOKEN'] = $_POST['AM_REFRESH_TOKEN'];
    $CORPORATION_DATA['FOCUSBIZ_API_KEY'] = $_POST['FOCUSBIZ_API_KEY'];
    $CORPORATION_DATA['SALES_TAX'] = $_POST['SALES_TAX'];
    $CORPORATION_DATA['ACTIVE'] = 1;
    $CORPORATION_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
    $CORPORATION_DATA['CREATED_ON'] = date("Y-m-d H:i");

    $settings = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
    if ($settings->RecordCount() == 0) {
        db_perform('DOA_CORPORATION', $CORPORATION_DATA, 'insert');
    } else {
        $CORPORATION_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $CORPORATION_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_CORPORATION', $CORPORATION_DATA, 'update'," PK_CORPORATION =  '$_GET[id]'");
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

    $Name = "Test Username";
    $Body = "Just for test";

    $hostname = $EMAIL_ACCOUNT_DATA['HOST'];
    $port = $EMAIL_ACCOUNT_DATA['PORT'];
    $SendingEmail = $EMAIL_ACCOUNT_DATA['USER_NAME'];
    $SendingPwd = $EMAIL_ACCOUNT_DATA['PASSWORD'];

    $To = "deb.soumya93@gmail.com";
    $Subject = "Test SMTP Account";

    require_once('../global/phpmailer/class.phpmailer.php');

    //Create a new PHPMailer instance
    $mail = new PHPMailer();
    //Tell PHPMailer to use SMTP
    $mail->IsSMTP();
    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    //$mail->SMTPDebug = 2;

    //Ask for HTML-friendly debug output
    //$mail->Debugoutput = 'html';

    //Set the hostname of the mail server
    $mail->Host = $hostname;

    //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
    $mail->Port = $port;

    //Set the encryption system to use - ssl (deprecated) or tls
    $mail->SMTPSecure = 'ssl';

    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;

    //Username to use for SMTP authentication - use full email address for gmail
    $mail->Username = $SendingEmail;

    //Password to use for SMTP authentication
    $mail->Password = $SendingPwd;

    try {
        $mail->setFrom($SendingEmail, 'development');
    } catch (phpmailerException $e) {
        $_SESSION['mail_error'] = $e->errorMessage() . "<br>";
    }  //add sender email address.

    $mail->addAddress('deb.soumya93@gmail.com', "development");  //Set who the message is to be sent to.
    //Set the subject line
    $mail->Subject = 'SMTP Test Account';

    //Read an HTML message body from an external file, convert referenced images to embedded,
    //convert HTML into a basic plain-text alternative body
    $mail->Body = 'Just for test';

    //Replace the plain text body with one created manually
    $mail->AltBody = 'This is a plain-text message body';

    //Attach an image file
    //$mail->addAttachment('images/phpmailer_mini.gif');
    //$mail->SMTPAuth = true;
    //send the message, check for errors
    try {
        if (!$mail->send()) {
            $_SESSION['mail_error'] .= "Mailer Error: " . $mail->ErrorInfo;
        } else {
            $_SESSION['mail_error'] .= "Message sent!";
        }
    } catch (phpmailerException $e) {
        $_SESSION['mail_error'] .= "Mailer Error: " . $e->getMessage();
    }

    $email_data = $db_account->Execute("SELECT * FROM DOA_EMAIL_ACCOUNT WHERE PK_LOCATION = 0");
    if ($email_data->RecordCount() == 0) {
        db_perform_account('DOA_EMAIL_ACCOUNT', $EMAIL_ACCOUNT_DATA, 'insert');
    } else {
        $EMAIL_ACCOUNT_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_ACCOUNT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_EMAIL_ACCOUNT', $EMAIL_ACCOUNT_DATA, 'update', " PK_LOCATION = 0");
    }
    header("location:all_corporations.php");
}

if(empty($_GET['id'])){
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

    $FRANCHISE              = '';
    $AM_USER_NAME           = '';
    $AM_PASSWORD            = '';
    $AM_REFRESH_TOKEN       = '';

    $TEXTING_FEATURE_ENABLED    = '';
    $TWILIO_ACCOUNT_TYPE        = '';

    $ACTIVE                 = '';
}
else {
    $res = $db->Execute("SELECT * FROM `DOA_CORPORATION` WHERE PK_CORPORATION = '$_GET[id]'");
    if($res->RecordCount() == 0){
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

    $FRANCHISE              = $res->fields['FRANCHISE'];
    $AM_USER_NAME           = $res->fields['AM_USER_NAME'];
    $AM_PASSWORD            = $res->fields['AM_PASSWORD'];
    $AM_REFRESH_TOKEN       = $res->fields['AM_REFRESH_TOKEN'];

    $TEXTING_FEATURE_ENABLED    = $res->fields['TEXTING_FEATURE_ENABLED'];
    $TWILIO_ACCOUNT_TYPE        = $res->fields['TWILIO_ACCOUNT_TYPE'];

    $ACTIVE                 = $res->fields['ACTIVE'];
}

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

$user_data = $db->Execute("SELECT DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_data->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];

$payment_gateway_setting = $db->Execute( "SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");
$STRIPE_SECRET_KEY = $payment_gateway_setting->fields['SECRET_KEY'];
$STRIPE_PUBLISHABLE_KEY = $payment_gateway_setting->fields['PUBLISHABLE_KEY'];

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'corporation'");
if($help->RecordCount() > 0) {
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
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
                            <li class="breadcrumb-item"><a href="all_corporations.php">All Corporations</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-8">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label class="col-md-12" for="example-text">Corporation Name<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-12">
                                        <input type="text" id="CORPORATION_NAME" name="CORPORATION_NAME" class="form-control" placeholder="Enter Corporation Name" value="<?php echo $CORPORATION_NAME?>">
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
                                                <input type="hidden" name="OLD_USERNAME_PREFIX" id="OLD_USERNAME_PREFIX" value="<?php echo $USERNAME_PREFIX?>">
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
                                    <?php if (isset($_SESSION['error'])) {?>
                                        <div class="alert alert-danger">
                                            <strong><?=$_SESSION['error'];?></strong>
                                        </div>
                                    <?php } ?>
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

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12">Focusbiz API Key</label>
                                            <div class="col-md-12">
                                                <input type="text" id="FOCUSBIZ_API_KEY" name="FOCUSBIZ_API_KEY" class="form-control" placeholder="Enter Focusbiz API Key" value="<?php echo $FOCUSBIZ_API_KEY?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12">Sales Tax</label>
                                            <div class="input-group">
                                                <input type="text" id="SALES_TAX" name="SALES_TAX" class="form-control" placeholder="Enter Sales Tax" value="<?=$SALES_TAX?>">
                                                <span class="form-control input-group-text">%</span>
                                            </div>
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
                                                    <label class="form-label" style="margin-bottom: 20px;">Gateway Type</label><br>
                                                    <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Stripe" <?=($PAYMENT_GATEWAY_TYPE=='Stripe')?'checked':''?> onclick="showPaymentGateway(this);">Stripe</label>
                                                    <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Square" <?=($PAYMENT_GATEWAY_TYPE=='Square')?'checked':''?> onclick="showPaymentGateway(this);">Square</label>
                                                    <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Authorized.net" <?=($PAYMENT_GATEWAY_TYPE=='Authorized.net')?'checked':''?> onclick="showPaymentGateway(this);">Authorized.net</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label" style="margin-bottom: 20px;">Gateway Mode</label><br>
                                                    <label style="margin-right: 70px;"><input type="radio" id="GATEWAY_MODE" name="GATEWAY_MODE" class="form-check-inline" value="test" <?=($GATEWAY_MODE=='test' || $GATEWAY_MODE==null || $GATEWAY_MODE=='')?'checked':''?>> Test</label>
                                                    <label style="margin-right: 70px;"><input type="radio" id="GATEWAY_MODE" name="GATEWAY_MODE" class="form-check-inline" value="live" <?=($GATEWAY_MODE=='live')?'checked':''?>> Live</label>
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
                                    <?php if (isset($_SESSION['mail_error'])) {?>
                                        <div class="alert alert-danger">
                                            <strong><?=$_SESSION['mail_error'];?></strong>
                                        </div>
                                    <?php } ?>
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
                                        <!--<div class="col-4">
                                            <div class="form-group">
                                                <label class="form-label">Refresh Token</label>
                                                <input type="text" class="form-control" name="AM_REFRESH_TOKEN" value="<?php /*=$AM_REFRESH_TOKEN*/?>">
                                            </div>
                                        </div>-->
                                    </div>
                                <?php } ?>
                                <?php if(!empty($_GET['id'])) { ?>
                                    <div class="row" style="margin-bottom: 15px;">
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
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_corporations.php'">Cancel</button>
                            </form>
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
</div>
<?php require_once('../includes/footer.php');?>
</body>
</html>

<script>
    function showHourBox(radio) {
        if (radio.value == 1) {
            document.getElementById("yes").style.display = "block";
        } else {
            document.getElementById("yes").style.display = "none";
        }
    }

    function showPaymentGateway(radio) {
        var paymentGateway = document.querySelectorAll('.payment_gateway');
        paymentGateway.forEach(function (element) {
            element.style.display = 'none';
        });
        if (radio.value == 'Stripe') {
            document.getElementById('stripe').style.display = 'block';
        } else if (radio.value == 'Square') {
            document.getElementById('square').style.display = 'block';
        } else if (radio.value == 'Authorized.net') {
            document.getElementById('authorized').style.display = 'block';
        }
    }
</script>