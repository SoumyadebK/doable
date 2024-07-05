<?php

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "Business Profile";

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
$BUSINESS_ID   = $res->fields['BUSINESS_ID'];
$API_KEY  	        = $res->fields['API_KEY'];
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

$AM_USER_NAME = $res->fields['AM_USER_NAME'];
$AM_PASSWORD = $res->fields['AM_PASSWORD'];
$AM_REFRESH_TOKEN = $res->fields['AM_REFRESH_TOKEN'];

$SMTP_HOST = '';
$SMTP_PORT = '';
$SMTP_USERNAME = '';
$SMTP_PASSWORD = '';
$email = $db_account->Execute("SELECT * FROM DOA_EMAIL_ACCOUNT WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
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
    if ($_POST['FUNCTION_NAME'] == 'saveProfileData') {
        $ACCOUNT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $ACCOUNT_DATA['PK_BUSINESS_TYPE'] = $_POST['PK_BUSINESS_TYPE'];
        $ACCOUNT_DATA['BUSINESS_ID'] = $_POST['BUSINESS_ID'];
        $ACCOUNT_DATA['BUSINESS_NAME'] = $_POST['BUSINESS_NAME'];
        $ACCOUNT_DATA['ADDRESS'] = $_POST['ADDRESS'];
        $ACCOUNT_DATA['ADDRESS_1'] = $_POST['ADDRESS_1'];
        $ACCOUNT_DATA['CITY'] = $_POST['CITY'];
        $ACCOUNT_DATA['PK_STATES'] = $_POST['PK_STATES'];
        $ACCOUNT_DATA['ZIP'] = $_POST['ZIP'];
        $ACCOUNT_DATA['PK_COUNTRY'] = $_POST['PK_COUNTRY'];
        $ACCOUNT_DATA['PHONE'] = $_POST['PHONE'];
        $ACCOUNT_DATA['FAX'] = $_POST['FAX'];
        $ACCOUNT_DATA['EMAIL'] = $_POST['EMAIL'];
        $ACCOUNT_DATA['WEBSITE'] = $_POST['WEBSITE'];
        if ($_FILES['BUSINESS_LOGO']['name'] != '') {
            $USER_DATA = [];
            $extn = explode(".", $_FILES['BUSINESS_LOGO']['name']);
            $iindex = count($extn) - 1;
            $rand_string = time() . "-" . rand(100000, 999999);
            $file11 = 'business_logo_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
            $extension = strtolower($extn[$iindex]);

            if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
                $image_path = '../uploads/business_logo/' . $file11;
                move_uploaded_file($_FILES['BUSINESS_LOGO']['tmp_name'], $image_path);
                $ACCOUNT_DATA['BUSINESS_LOGO'] = $image_path;
            }
        }
        $account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        if ($account_data->RecordCount() == 0) {
            db_perform('DOA_ACCOUNT_MASTER', $ACCOUNT_DATA, 'insert');
        } else {
            db_perform('DOA_ACCOUNT_MASTER', $ACCOUNT_DATA, 'update', " PK_ACCOUNT_MASTER =  '$_SESSION[PK_ACCOUNT_MASTER]'");
        }
    }

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
        $SETTINGS_DATA['EDITED_BY'] = 0;
        $SETTINGS_DATA['EDITED_ON'] = "0000-00-00 00:00:00";
        $settings = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        if ($settings->RecordCount() == 0) {
            db_perform('DOA_ACCOUNT_MASTER', $SETTINGS_DATA, 'insert');
        } else {
            db_perform('DOA_ACCOUNT_MASTER', $SETTINGS_DATA, 'update', " PK_ACCOUNT_MASTER =  '$_SESSION[PK_ACCOUNT_MASTER]'");
        }

        $EMAIL_DATA['HOST'] = $_POST['SMTP_HOST'];
        $EMAIL_DATA['PORT'] = $_POST['SMTP_PORT'];
        $EMAIL_DATA['USER_NAME'] = $_POST['SMTP_USERNAME'];
        $EMAIL_DATA['PASSWORD'] = $_POST['SMTP_PASSWORD'];
        $EMAIL_DATA['ACTIVE'] = 1;
        $EMAIL_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_DATA['CREATED_ON'] = date("Y-m-d H:i");
        $EMAIL_DATA['EDITED_BY'] = 0;
        $EMAIL_DATA['EDITED_ON'] = "0000-00-00 00:00:00";
//        unset($_POST['FUNCTION_NAME']);
//        unset($_POST['SMTP_HOST']);
//        unset($_POST['SMTP_PORT']);
//        unset($_POST['SMTP_USERNAME']);
//        unset($_POST['SMTP_PASSWORD']);

        $email = $db_account->Execute("SELECT * FROM DOA_EMAIL_ACCOUNT WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        if ($email->RecordCount() == 0) {
            db_perform_account('DOA_EMAIL_ACCOUNT', $EMAIL_DATA, 'insert');
        } else {
            db_perform_account('DOA_EMAIL_ACCOUNT', $EMAIL_DATA, 'update', " PK_ACCOUNT_MASTER =  '$_SESSION[PK_ACCOUNT_MASTER]'");
        }
    }

    if ($_POST['FUNCTION_NAME'] == 'saveHolidayData') {
        unset($_POST['FUNCTION_NAME']);
        $db_account->Execute("DELETE FROM `DOA_HOLIDAY_LIST` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
        for ($i=0; $i < count($_POST['HOLIDAY_DATE']); $i++) {
            $HOLIDAY_LIST_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
            $HOLIDAY_LIST_DATA['HOLIDAY_DATE'] = date('Y-m-d', strtotime($_POST['HOLIDAY_DATE'][$i]));
            $HOLIDAY_LIST_DATA['HOLIDAY_NAME'] = $_POST['HOLIDAY_NAME'][$i];
            db_perform_account('DOA_HOLIDAY_LIST', $HOLIDAY_LIST_DATA, 'insert');
        }
    }

    if ($_POST['FUNCTION_NAME'] == 'saveCreditCard') {
        $STRIPE_TOKEN = $_POST['token'];
        $ACCOUNT_PAYMENT_ID = '';
        if ($account_payment_info->RecordCount() > 0) {
            $ACCOUNT_PAYMENT_ID = $account_payment_info->fields['ACCOUNT_PAYMENT_ID'];
        } else {
            try {
                $customer = $stripe->customers->create([
                    'email' => $EMAIL,
                    'name' => $BUSINESS_NAME,
                    'phone' => $PHONE,
                    'description' => $BUSINESS_NAME,
                ]);
                $ACCOUNT_PAYMENT_ID = $customer->id;
            } catch (ApiErrorException $e) {
                pre_r($e->getMessage());
            }

            $STRIPE_DETAILS['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
            $STRIPE_DETAILS['ACCOUNT_PAYMENT_ID'] = $ACCOUNT_PAYMENT_ID;
            $STRIPE_DETAILS['PAYMENT_TYPE'] = 'Stripe';
            $STRIPE_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_ACCOUNT_PAYMENT_INFO', $STRIPE_DETAILS, 'insert');
        }
        $card = $stripe->customers->createSource($ACCOUNT_PAYMENT_ID, ['source' => $STRIPE_TOKEN]);
        $stripe->customers->update($ACCOUNT_PAYMENT_ID, ['default_source' => $card->id]);
    }

    header("location:business_profile.php");
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
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="active"> <a class="nav-link active" data-bs-toggle="tab" id="profile_link" href="#profile" role="tab"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Profile</span></a> </li>
                                <!--<li> <a class="nav-link" data-bs-toggle="tab" id="settings_link" href="#settings" role="tab"><span class="hidden-sm-up"><i class="ti-settings"></i></span> <span class="hidden-xs-down">Settings</span></a> </li>-->
                                <li> <a class="nav-link" data-bs-toggle="tab" id="holiday_list_link" href="#holiday_list" role="tab"><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">Holiday List</span></a> </li>
                                <li> <a class="nav-link" data-bs-toggle="tab" id="credit_card_link" href="#credit_card" role="tab" onclick="stripePaymentFunction();"><span class="hidden-sm-up"><i class="ti-credit-card"></i></span> <span class="hidden-xs-down">Credit Card</span></a> </li>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tab-content tabcontent-border">
                                <div class="tab-pane active" id="profile" role="tabpanel">
                                    <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveProfileData">
                                        <div class="p-20">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Business Type<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <select class="form-control" required name="PK_BUSINESS_TYPE" id="PK_BUSINESS_TYPE">
                                                                <option>Select Business Type</option>
                                                                <?php
                                                                $result_dropdown_query = mysqli_query($conn,"SELECT PK_BUSINESS_TYPE,BUSINESS_TYPE FROM DOA_BUSINESS_TYPE WHERE ACTIVE='1' ORDER BY PK_BUSINESS_TYPE");
                                                                while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>
                                                                    <option value="<?php echo $result_dropdown['PK_BUSINESS_TYPE'];?>" <?php if($result_dropdown['PK_BUSINESS_TYPE'] == $PK_BUSINESS_TYPE) echo 'selected = "selected"';?> ><?=$result_dropdown['BUSINESS_TYPE']?></option>
                                                                    <?php
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Business ID<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="BUSINESS_ID" name="BUSINESS_ID" class="form-control" placeholder="Enter Business ID" required data-validation-required-message="This field is required" value="<?php echo $BUSINESS_ID?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <!--<div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Account Type<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <?php /*$i=1;
                                                            $result_dropdown_query = mysqli_query($conn,"select PK_ACCOUNT_TYPE,ACCOUNT_TYPE from DOA_ACCOUNT_TYPE WHERE ACTIVE='1' order by PK_ACCOUNT_TYPE");
                                                            while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { */?>
                                                                <input type="radio" id="PK_ACCOUNT_TYPE_<?php /*echo $i;*/?>" name="PK_ACCOUNT_TYPE" value="<?php /*echo $result_dropdown['PK_ACCOUNT_TYPE'];*/?>" <?php /*if($result_dropdown['PK_ACCOUNT_TYPE'] == $PK_ACCOUNT_TYPE) echo 'checked';*/?>>
                                                                <label for="contactChoice1"><?/*=$result_dropdown['ACCOUNT_TYPE']*/?></label>
                                                                <?php
/*                                                                $i++; }
                                                            */?>
                                                        </div>
                                                    </div>
                                                </div>-->
                                            </div>



                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Business Name<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="BUSINESS_NAME" name="BUSINESS_NAME" class="form-control" placeholder="Enter Business Name" required data-validation-required-message="This field is required" value="<?php echo $BUSINESS_NAME?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Address
                                                        </label>
                                                        <div class="col-md-12">
                                                            <textarea class="form-control" rows="2" id="ADDRESS" name="ADDRESS"><?php echo $ADDRESS?></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Apt/Ste
                                                        </label>
                                                        <div class="col-md-12">
                                                            <textarea class="form-control" rows="2" id="ADDRESS_1" name="ADDRESS_1" ><?php echo $ADDRESS_1?></textarea>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>


                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Country<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <div class="col-sm-12">
                                                                <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)">
                                                                    <option>Select Country</option>
                                                                    <?php
                                                                    $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                                    while (!$row->EOF) { ?>
                                                                        <option value="<?php echo $row->fields['PK_COUNTRY'];?>" <?=($row->fields['PK_COUNTRY'] == $PK_COUNTRY)?"selected":""?>><?=$row->fields['COUNTRY_NAME']?></option>
                                                                        <?php $row->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">State<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <div class="col-sm-12">
                                                                <div id="State_div"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">City</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter Your City" value="<?php echo $CITY?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Zip Code</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Zip Code" value="<?php echo $ZIP?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">

                                                    <div class="form-group">
                                                        <label class="col-md-12">Business Phone
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone Number" value="<?php echo $PHONE?>">
                                                        </div>
                                                    </div>

                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Business Fax
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="FAX" name="FAX" class="form-control" placeholder="Enter Fax" value="<?php echo $FAX;?>">
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Business Email<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="email" id="EMAIL" name="EMAIL" class="form-control" placeholder="Enter Email" required data-validation-required-message="This field is required" value="<?php echo $EMAIL?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Website
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="WEBSITE" name="WEBSITE" class="form-control" placeholder="Enter Website" value="<?php echo $WEBSITE?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row" style="margin-bottom: 15px;">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Business Logo
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="file" name="BUSINESS_LOGO" id="BUSINESS_LOGO" class="form-control" >
                                                        </div>
                                                    </div>
                                                    <?php if($BUSINESS_LOGO!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $BUSINESS_LOGO;?>" data-fancybox-group="gallery"><img src = "<?php echo $BUSINESS_LOGO;?>" style="width:auto; height:120px" /></a></div><?php } ?>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                            <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='business_profile.php'">Cancel</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane" id="settings" role="tabpanel">
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
                                                                <? $res_type = $db->Execute("SELECT * FROM DOA_TIMEZONE WHERE ACTIVE = 1 ORDER BY NAME ASC");
                                                                while (!$res_type->EOF) { ?>
                                                                    <option value="<?=$res_type->fields['PK_TIMEZONE']?>" <? if($res_type->fields['PK_TIMEZONE'] == $PK_TIMEZONE) echo 'selected="selected"'; ?>><?=$res_type->fields['NAME']?></option>
                                                                    <?	$res_type->MoveNext();
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
                                                                <? $res_type = $db->Execute("SELECT * FROM `DOA_CURRENCY` WHERE `ACTIVE` = 1");
                                                                while (!$res_type->EOF) { ?>
                                                                    <option value="<?=$res_type->fields['PK_CURRENCY']?>" <?=($res_type->fields['PK_CURRENCY'] == $PK_CURRENCY)?'selected':''?>><?=$res_type->fields['CURRENCY_NAME']." (".$res_type->fields['CURRENCY_SYMBOL'].")"?></option>
                                                                    <?	$res_type->MoveNext();
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


                                            <?php if ($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1) { ?>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="form-label" style="margin-bottom: 5px;">Payment Gateway</label><br>
                                                            <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Stripe" <?=($PAYMENT_GATEWAY_TYPE=='Stripe')?'checked':''?> onclick="showPaymentGateway(this);">Stripe</label>
                                                            <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Square" <?=($PAYMENT_GATEWAY_TYPE=='Square')?'checked':''?> onclick="showPaymentGateway(this);">Square</label>
                                                            <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Authorized.net" <?=($PAYMENT_GATEWAY_TYPE=='Authorized.net')?'checked':''?> onclick="showPaymentGateway(this);">Authorized.net</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row payment_gateway" id="stripe" style="display: <?=($PAYMENT_GATEWAY_TYPE=='Stripe')?'':'none'?>;">
                                                    <div class="col-6">
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
                                                    <div class="col-6">
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
                                                    <div class="col-6">
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
                                            <?php } ?>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label" style="margin-bottom: 5px;">Send an Appointment Reminder Text message.</label><br>
                                                        <label style="margin-right: 70px;"><input type="radio" id="APPOINTMENT_REMINDER" name="APPOINTMENT_REMINDER" class="form-check-inline" value="1" <?=($APPOINTMENT_REMINDER=='1')?'checked':''?> onclick="showHourBox(this);">Yes</label>
                                                        <label style="margin-right: 70px;"><input type="radio" id="APPOINTMENT_REMINDER" name="APPOINTMENT_REMINDER" class="form-check-inline" value="0" <?=($APPOINTMENT_REMINDER=='0')?'checked':''?> onclick="showHourBox(this);">No</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row hour_box" id="yes" style="display: <?=($APPOINTMENT_REMINDER=='1')?'':'none'?>;">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">How many hours before the appointment ?</label>
                                                        <input type="text" class="form-control" name="HOUR" value="<?=$HOUR?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row smtp" id="smtp" >
                                                <div class="form-group">
                                                    <label class="form-label">SMTP Setup</label>
                                                </div>
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

                                            <div class="row smtp" id="smtp" >
                                                <div class="form-group">
                                                    <label class="form-label">Arthur Murray API Setup</label>
                                                </div>
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



                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                            <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='business_profile.php'">Cancel</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane" id="holiday_list" role="tabpanel">
                                    <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveHolidayData">
                                        <div class="p-20" id="holiday_list_div">
                                            <div class="row">
                                                <div class="col-3">
                                                    <div class="form-group" style="text-align: center;">
                                                        <label class="form-label" style="font-weight: bold;">Holiday Date</label>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group" style="text-align: center;">
                                                        <label class="form-label" style="font-weight: bold;">Holiday Name</label>
                                                    </div>
                                                </div>
                                                <div class="col-3" style="margin-top: -30px;">
                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="addMoreHoliday();">Add More</a>
                                                </div>
                                            </div>
                                            <?php
                                            $holiday_list = $db_account->Execute("SELECT * FROM DOA_HOLIDAY_LIST WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                            if($holiday_list->RecordCount() > 0) {
                                                while (!$holiday_list->EOF) { ?>
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="HOLIDAY_DATE[]" class="form-control datepicker-normal" value="<?=date('m/d/Y', strtotime($holiday_list->fields['HOLIDAY_DATE']))?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="HOLIDAY_NAME[]" class="form-control" value="<?=$holiday_list->fields['HOLIDAY_NAME']?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3" style="padding-top: 5px;">
                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                                <?php $holiday_list->MoveNext(); } ?>
                                            <?php } else { ?>
                                                <div class="row">
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
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
                                        <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='business_profile.php'">Cancel</button>
                                    </form>
                                </div>

                                <div class="tab-pane" id="credit_card" role="tabpanel">
                                    <form class="form-material form-horizontal" id="creditCardForm" action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveCreditCard">
                                        <div class="p-20" id="credit_card_div">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div id="card-element"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
                                        <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='business_profile.php'">Cancel</button>
                                    </form>

                                    <?php if (isset($card_details['last4'])) {
                                        switch ($card_details['brand']) {
                                            case 'Visa':
                                            case 'Visa (debit)':
                                                $card_type = 'visa';
                                                break;
                                            case 'MasterCard':
                                            case 'Mastercard (2-series)':
                                            case 'Mastercard (debit)':
                                            case 'Mastercard (prepaid)':
                                                $card_type = 'mastercard';
                                                break;
                                            case 'American Express':
                                                $card_type = 'amex';
                                                break;
                                            case 'Discover':
                                            case 'Discover (debit)':
                                                $card_type = 'discover';
                                                break;
                                            case 'Diners Club':
                                            case 'Diners Club (14-digit card)':
                                                $card_type = 'diners';
                                                break;
                                            case 'JCB':
                                                $card_type = 'jcb';
                                                break;
                                            case 'UnionPay':
                                            case 'UnionPay (debit)':
                                            case 'UnionPay (19-digit card)':
                                                $card_type = 'unionpay';
                                                break;
                                            default:
                                                $card_type = '';
                                                break;

                                        } ?>
                                        <div class="p-20">
                                            <h5>Saved Card Details</h5>
                                            <div class="credit-card <?=$card_type?> selectable" style="margin-right: 80%;">
                                                <div class="credit-card-last4">
                                                    <?=$card_details['last4']?>
                                                </div>
                                                <div class="credit-card-expiry">
                                                    <?=$card_details['exp_month'].'/'.$card_details['exp_year']?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
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

        $(document).ready(function() {
            fetch_state(<?php  echo $PK_COUNTRY; ?>);
        });

        function fetch_state(PK_COUNTRY){
            $(document).ready(function(event) {
                let data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$PK_STATES;?>";
                let value = $.ajax({
                    url: "ajax/state.php",
                    type: "POST",
                    data: data,
                    async: false,
                    cache :false,
                    success: function (result) {
                        document.getElementById('State_div').innerHTML = result;
                    }
                }).responseText;
            });
        }


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