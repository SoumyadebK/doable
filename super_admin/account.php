<?php

use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\StripeClient;

require_once('../global/config.php');
global $db;
global $db_account;

if (empty($_GET['id']))
    $title = "Add Account";
else
    $title = "Edit Account";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['cond']) && $_GET['cond'] == 'del'){
    $db->Execute("DELETE FROM `DOA_USERS` WHERE `PK_USER` = ".$_GET['PK_USER']);
    header('location:account.php?id='.$_GET['id']);
}

$PK_ACCOUNT_MASTER = '';
$PK_BUSINESS_TYPE = '';
$PK_ACCOUNT_TYPE = '';
$FRANCHISE = '';
$BUSINESS_NAME = '';
$ACCOUNT_ADDRESS = '';
$ACCOUNT_ADDRESS_1 = '';
$ACCOUNT_PK_COUNTRY = '';
$ACCOUNT_PK_STATES = '';
$PK_STATE = '';
$ACCOUNT_CITY = '';
$ACCOUNT_ZIP = '';
$ACCOUNT_PHONE = '';
$ACCOUNT_FAX = '';
$ACCOUNT_EMAIL = '';
$ACCOUNT_WEBSITE = '';
$TEXTING_FEATURE_ENABLED = '';
$TWILIO_ACCOUNT_TYPE = '';
$ACTIVE = '';
$ABLE_TO_EDIT_PAYMENT_GATEWAY = '';
$USERNAME_PREFIX = '';

$PK_USER_EDIT = '';
$USER_NAME = '';
$FIRST_NAME = '';
$LAST_NAME = '';
$EMAIL_ID = '';
$USER_IMAGE = '';
$GENDER = '';
$DOB = '';
$ADDRESS = '';
$ADDRESS_1 = '';
$PK_COUNTRY = '';
$PK_STATES = '';
$CITY = '';
$ZIP = '';
$PHONE = '';
$NOTES = '';

$START_DATE = '';
$BILLING_TYPE = '';
$AMOUNT = '';
$TOTAL_AMOUNT = '';
$NEXT_RENEWAL_DATE = '';
$STATUS = '';
if(!empty($_GET['id'])) {
    $account_res = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER`  = '$_GET[id]'");
    if($account_res->RecordCount() == 0){
        header("location:all_accounts.php");
        exit;
    }
    $PK_ACCOUNT_MASTER = $_GET['id'];
    $PK_BUSINESS_TYPE = $account_res->fields['PK_BUSINESS_TYPE'];
    $PK_ACCOUNT_TYPE = $account_res->fields['PK_ACCOUNT_TYPE'];
    $FRANCHISE = $account_res->fields['FRANCHISE'];
    $BUSINESS_NAME = $account_res->fields['BUSINESS_NAME'];
    $ACCOUNT_ADDRESS = $account_res->fields['ADDRESS'];
    $ACCOUNT_ADDRESS_1 = $account_res->fields['ADDRESS_1'];
    $ACCOUNT_PK_COUNTRY = $account_res->fields['PK_COUNTRY'];
    $ACCOUNT_PK_STATES = $account_res->fields['PK_STATES'];
    $ACCOUNT_CITY = $account_res->fields['CITY'];
    $ACCOUNT_ZIP = $account_res->fields['ZIP'];
    $ACCOUNT_PHONE = $account_res->fields['PHONE'];
    $ACCOUNT_FAX = $account_res->fields['FAX'];
    $ACCOUNT_EMAIL = $account_res->fields['EMAIL'];
    $ACCOUNT_WEBSITE = $account_res->fields['WEBSITE'];
    $TEXTING_FEATURE_ENABLED = $account_res->fields['TEXTING_FEATURE_ENABLED'];
    $TWILIO_ACCOUNT_TYPE = $account_res->fields['TWILIO_ACCOUNT_TYPE'];
    $USERNAME_PREFIX = $account_res->fields['USERNAME_PREFIX'];
    $ACTIVE = $account_res->fields['ACTIVE'];

    $user_res = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_ACCOUNT_MASTER = '$_GET[id]' AND CREATED_BY = '$_SESSION[PK_USER]'");
    if($user_res->RecordCount() > 0) {
        $PK_USER_EDIT = $user_res->fields['PK_USER'];
        $USER_NAME = $user_res->fields['USER_NAME'];
        $FIRST_NAME = $user_res->fields['FIRST_NAME'];
        $LAST_NAME = $user_res->fields['LAST_NAME'];
        $EMAIL_ID = $user_res->fields['EMAIL_ID'];
        $USER_IMAGE = $user_res->fields['USER_IMAGE'];
        $GENDER = $user_res->fields['GENDER'];
        $DOB = $user_res->fields['DOB'];
        $ADDRESS = $user_res->fields['ADDRESS'];
        $ADDRESS_1 = $user_res->fields['ADDRESS_1'];
        $PK_COUNTRY = $user_res->fields['PK_COUNTRY'];
        $PK_STATES = $user_res->fields['PK_STATES'];
        $CITY = $user_res->fields['CITY'];
        $ZIP = $user_res->fields['ZIP'];
        $PHONE = $user_res->fields['PHONE'];
        $NOTES = $user_res->fields['NOTES'];
        $ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_res->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];
    }

    $user_billing_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_BILLING_DETAILS WHERE PK_ACCOUNT_MASTER = ".$PK_ACCOUNT_MASTER);
    if($user_billing_data->RecordCount() > 0) {
        $START_DATE = $user_billing_data->fields['START_DATE'];
        $BILLING_TYPE = $user_billing_data->fields['BILLING_TYPE'];
        $AMOUNT = $user_billing_data->fields['AMOUNT'];
        $TOTAL_AMOUNT = $user_billing_data->fields['TOTAL_AMOUNT'];
        $NEXT_RENEWAL_DATE = $user_billing_data->fields['NEXT_RENEWAL_DATE'];
        $STATUS = $user_billing_data->fields['STATUS'];
    }
}

$location_data = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE ACTIVE = 1 AND `PK_ACCOUNT_MASTER`  = ".$PK_ACCOUNT_MASTER);
$location_count = ($location_data->RecordCount() > 0) ? $location_data->RecordCount() : 1;

$payment_gateway_setting = $db->Execute( "SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");
$SECRET_KEY = $payment_gateway_setting->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_setting->fields['PUBLISHABLE_KEY'];
require_once("../global/stripe-php-master/init.php");
Stripe::setApiKey($SECRET_KEY);

if (!empty($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] == 'saveBillingData') {
    $AMOUNT = $_POST['AMOUNT'];

    if ($_POST['BILLING_TYPE'] == 'PER_ACCOUNT') {
        $ACCOUNT_PAYMENT_ID = $_POST['CUSTOMER_ID'];
        $account = \Stripe\Customer::retrieve($ACCOUNT_PAYMENT_ID);
        try {
            $charge = \Stripe\Charge::create(array(
                "amount" => $AMOUNT * 100,
                "currency" => "usd",
                "description" => $BUSINESS_NAME,
                "customer" => $ACCOUNT_PAYMENT_ID,
                "statement_descriptor" => "Subscription Charge",
            ));

            if ($charge->paid == 1) {
                $ACCOUNT_PAYMENT_DETAILS['PAYMENT_STATUS'] = 'Success';
                $ACCOUNT_PAYMENT_DETAILS['PAYMENT_INFO'] = $charge->id;

                $ACCOUNT_BILLING_DETAILS['STATUS'] = 'Active';
                $ACCOUNT_BILLING_DETAILS['NEXT_RENEWAL_DATE'] = date("Y-m-d", strtotime('+1 month', strtotime($NEXT_RENEWAL_DATE)));
            } else {
                $ACCOUNT_PAYMENT_DETAILS['PAYMENT_STATUS'] = 'Failed';
                $ACCOUNT_PAYMENT_DETAILS['PAYMENT_INFO'] = $charge->failure_message;

                $ACCOUNT_BILLING_DETAILS['STATUS'] = 'Pending';
            }
        } catch (Exception $e) {
            $ACCOUNT_PAYMENT_DETAILS['PAYMENT_STATUS'] = 'Failed';
            $ACCOUNT_PAYMENT_DETAILS['PAYMENT_INFO'] = $e->getMessage();

            $ACCOUNT_BILLING_DETAILS['STATUS'] = 'Pending';
        }
        $ACCOUNT_PAYMENT_DETAILS['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
        $ACCOUNT_PAYMENT_DETAILS['DATE_TIME'] = date('Y-m-d H:i');
        $ACCOUNT_PAYMENT_DETAILS['AMOUNT'] = $_POST['AMOUNT'];
        db_perform('DOA_ACCOUNT_PAYMENT_DETAILS', $ACCOUNT_PAYMENT_DETAILS, 'insert');
    } elseif ($_POST['BILLING_TYPE'] == 'PER_LOCATION') {
        $PK_LOCATION = $_POST['PK_LOCATION'];
        for ($i = 0; $i < count($PK_LOCATION); $i++) {
            $ACCOUNT_PAYMENT_ID = $_POST['LOCATION_CUSTOMER_ID'][$i];
            $account = \Stripe\Customer::retrieve($ACCOUNT_PAYMENT_ID);
            try {
                $charge = \Stripe\Charge::create(array(
                    "amount" => $AMOUNT * 100,
                    "currency" => "usd",
                    "description" => $BUSINESS_NAME,
                    "customer" => $ACCOUNT_PAYMENT_ID,
                    "statement_descriptor" => "Subscription Charge",
                ));

                if ($charge->paid == 1) {
                    $ACCOUNT_PAYMENT_DETAILS['PAYMENT_STATUS'] = 'Success';
                    $ACCOUNT_PAYMENT_DETAILS['PAYMENT_INFO'] = $charge->id;

                    $ACCOUNT_BILLING_DETAILS['STATUS'] = 'Active';
                    $ACCOUNT_BILLING_DETAILS['NEXT_RENEWAL_DATE'] = date("Y-m-d", strtotime('+1 month', strtotime($NEXT_RENEWAL_DATE)));
                } else {
                    $ACCOUNT_PAYMENT_DETAILS['PAYMENT_STATUS'] = 'Failed';
                    $ACCOUNT_PAYMENT_DETAILS['PAYMENT_INFO'] = $charge->failure_message;

                    $ACCOUNT_BILLING_DETAILS['STATUS'] = 'Pending';
                }
            } catch (Exception $e) {
                $ACCOUNT_PAYMENT_DETAILS['PAYMENT_STATUS'] = 'Failed';
                $ACCOUNT_PAYMENT_DETAILS['PAYMENT_INFO'] = $e->getMessage();

                $ACCOUNT_BILLING_DETAILS['STATUS'] = 'Pending';
            }
            $ACCOUNT_PAYMENT_DETAILS['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
            $ACCOUNT_PAYMENT_DETAILS['PK_LOCATION'] = $_POST['PK_LOCATION'][$i];
            $ACCOUNT_PAYMENT_DETAILS['DATE_TIME'] = date('Y-m-d H:i');
            $ACCOUNT_PAYMENT_DETAILS['AMOUNT'] = $_POST['AMOUNT'];
            db_perform('DOA_ACCOUNT_PAYMENT_DETAILS', $ACCOUNT_PAYMENT_DETAILS, 'insert');
        }
    }

    $ACCOUNT_BILLING_DETAILS['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
    $ACCOUNT_BILLING_DETAILS['BILLING_TYPE'] = $_POST['BILLING_TYPE'];
    $ACCOUNT_BILLING_DETAILS['START_DATE'] = date("Y-m-d", strtotime($_POST['START_DATE']));
    $ACCOUNT_BILLING_DETAILS['NEXT_RENEWAL_DATE'] = date("Y-m-d", strtotime('+1 month', strtotime($_POST['START_DATE'])));
    $ACCOUNT_BILLING_DETAILS['AMOUNT'] = $_POST['AMOUNT'];
    $ACCOUNT_BILLING_DETAILS['TOTAL_AMOUNT'] = $_POST['TOTAL_AMOUNT'];

    $account_billing_info = $db->Execute("SELECT * FROM DOA_ACCOUNT_BILLING_DETAILS WHERE PK_ACCOUNT_MASTER = ".$PK_ACCOUNT_MASTER);
    if ($account_billing_info->RecordCount() > 0) {
        $ACCOUNT_BILLING_DETAILS['EDITED_BY'] = $_SESSION['PK_USER'];
        $ACCOUNT_BILLING_DETAILS['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_ACCOUNT_BILLING_DETAILS', $ACCOUNT_BILLING_DETAILS, 'update', " PK_ACCOUNT_MASTER = ".$PK_ACCOUNT_MASTER);
    } else {
        $ACCOUNT_BILLING_DETAILS['CREATED_BY'] = $_SESSION['PK_USER'];
        $ACCOUNT_BILLING_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_ACCOUNT_BILLING_DETAILS', $ACCOUNT_BILLING_DETAILS, 'insert');
    }

    header("location:account.php?id=".$PK_ACCOUNT_MASTER);
}

$account_payment_data = [];
$account_payment_info = $db->Execute("SELECT * FROM DOA_ACCOUNT_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Stripe' AND PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
while (!$account_payment_info->EOF) {
        require_once("../global/stripe-php-master/init.php");
        $stripe = new StripeClient($SECRET_KEY);
        $customer_id = $account_payment_info->fields['ACCOUNT_PAYMENT_ID'];
        $stripe_customer = $stripe->customers->retrieve($customer_id);
        $card_id = $stripe_customer->default_source;

        $url = "https://api.stripe.com/v1/customers/".$customer_id."/cards/".$card_id;
        $AUTH = "Authorization: Bearer ".$SECRET_KEY;

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

        $account_payment_data[] = [
            'CUSTOMER_ID' => $customer_id,
            'PK_LOCATION' => $account_payment_info->fields['PK_LOCATION'],
            'CARD_TYPE' => $card_details['brand'],
            'LAST4' => $card_details['last4'],
            'EXP_MONTH' => $card_details['exp_month'],
            'EXP_YEAR' => $card_details['exp_year'],
        ];
        //pre_r($card_details);

    $account_payment_info->MoveNext();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    .SumoSelect {
        width: 100%;
    }
</style>
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
                            <li class="breadcrumb-item"><a href="all_accounts.php">All Accounts</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="col-md-6 align-self-center">
                <h4 style="color: #39B54A;">
                    <?php if(!empty($_GET['id'])) {
                        echo $BUSINESS_NAME;
                    } ?>
                </h4>
            </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card wizard-content">
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="active"> <a class="nav-link active" id="home_tab_link" data-bs-toggle="tab" href="#home" role="tab"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Account Info</span></a> </li>
                                    <?php if(empty($_GET['id'])) { ?>
                                        <li> <a class="nav-link" id="profile_tab_link" data-bs-toggle="tab" href="#profile" role="tab"><span class="hidden-sm-up"><i class="ti-folder"></i></span> <span class="hidden-xs-down">User Profile</span></a> </li>
                                    <?php } else { ?>
                                        <li> <a class="nav-link" data-bs-toggle="tab" href="#login" role="tab" id="logintab"><span class="hidden-sm-up"><i class="ti-list"></i></span> <span class="hidden-xs-down">User List</span></a> </li>
                                    <?php } ?>
                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#billing" role="tab" id="billingtab" onclick="stripePaymentFunction();"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content tabcontent-border">
                                    <!--Account Info Tab-->
                                    <div class="tab-pane active" id="home" role="tabpanel">

                                        <form class="form-material form-horizontal" id="account_info_form">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveAccountInfoData">
                                            <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?=$PK_ACCOUNT_MASTER?>">
                                            <div class="p-20">
                                                <div class="row align-items-end">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Type<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <select class="form-control" required name="PK_BUSINESS_TYPE" id="PK_BUSINESS_TYPE">
                                                                    <option value="">Select Business Type</option>
                                                                    <?php
                                                                    $row = $db->Execute("SELECT PK_BUSINESS_TYPE,BUSINESS_TYPE FROM DOA_BUSINESS_TYPE WHERE ACTIVE='1' ORDER BY PK_BUSINESS_TYPE");
                                                                    while (!$row->EOF) { ?>
                                                                        <option value="<?php echo $row->fields['PK_BUSINESS_TYPE'];?>" <?=($row->fields['PK_BUSINESS_TYPE'] == $PK_BUSINESS_TYPE)?"selected":""?>><?=$row->fields['BUSINESS_TYPE']?></option>
                                                                    <?php $row->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Account Type<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <?php
                                                                $row = $db->Execute("SELECT PK_ACCOUNT_TYPE,ACCOUNT_TYPE FROM DOA_ACCOUNT_TYPE WHERE ACTIVE='1' ORDER BY PK_ACCOUNT_TYPE");
                                                                while (!$row->EOF) { ?>
                                                                    <input type="radio" name="PK_ACCOUNT_TYPE" id="<?=$row->fields['PK_ACCOUNT_TYPE'];?>" value="<?=$row->fields['PK_ACCOUNT_TYPE'];?>" <?php if($row->fields['PK_ACCOUNT_TYPE'] == $PK_ACCOUNT_TYPE) echo 'checked';?> required>
                                                                    <label for="<?=$row->fields['PK_ACCOUNT_TYPE'];?>"><?=$row->fields['ACCOUNT_TYPE']?></label>
                                                                <?php $row->MoveNext(); } ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Arthur Murray Franchise ?</label>
                                                            <div class="col-md-12">
                                                                <label><input type="radio" name="FRANCHISE" id="FRANCHISE" value="1" <?php if($FRANCHISE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                                <label><input type="radio" name="FRANCHISE" id="FRANCHISE" value="0" <?php if($FRANCHISE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Name<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="BUSINESS_NAME" name="BUSINESS_NAME" class="form-control" placeholder="Enter Business Name" required value="<?php echo $BUSINESS_NAME?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Address</label>
                                                            <div class="col-md-12">
                                                                <textarea class="form-control" rows="2" id="ACCOUNT_ADDRESS" name="ACCOUNT_ADDRESS" placeholder="Enter Address"><?php echo $ACCOUNT_ADDRESS?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Apt/Ste</label>
                                                            <div class="col-md-12">
                                                                <textarea class="form-control" rows="2" id="ACCOUNT_ADDRESS_1" name="ACCOUNT_ADDRESS_1" placeholder="Enter Street/Apartment"><?php echo $ACCOUNT_ADDRESS_1?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Country</label>
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12">
                                                                    <select class="form-control" name="ACCOUNT_PK_COUNTRY" id="ACCOUNT_PK_COUNTRY" onChange="fetch_Account_State(this.value)">
                                                                        <option>Select Country</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_COUNTRY'];?>" <?=($row->fields['PK_COUNTRY'] == $ACCOUNT_PK_COUNTRY)?"selected":""?>><?=$row->fields['COUNTRY_NAME']?></option>
                                                                        <?php $row->MoveNext(); } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">State</label>
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12">
                                                                    <div id="Account_State_div"></div>
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
                                                                <input type="text" id="ACCOUNT_CITY" name="ACCOUNT_CITY" class="form-control" placeholder="Enter City" value="<?php echo $ACCOUNT_CITY?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Zip Code</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_ZIP" name="ACCOUNT_ZIP" class="form-control" placeholder="Enter Zip Code" value="<?php echo $ACCOUNT_ZIP?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Phone</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_PHONE" name="ACCOUNT_PHONE" class="form-control" placeholder="Enter Business Phone No." value="<?php echo $ACCOUNT_PHONE?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Fax</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_FAX" name="ACCOUNT_FAX" class="form-control" placeholder="Enter Business Fax" value="<?php echo $ACCOUNT_FAX;?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Email<span class="text-danger">*</span></label>
                                                            <div class="col-md-12">
                                                                <input type="email" id="ACCOUNT_EMAIL" name="ACCOUNT_EMAIL" class="form-control" placeholder="Enter Business Email" required value="<?php echo $ACCOUNT_EMAIL?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Website
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_WEBSITE" name="ACCOUNT_WEBSITE" class="form-control" placeholder="Enter Website" value="<?php echo $ACCOUNT_WEBSITE?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row" style="margin-bottom: 15px; margin-top: 15px;">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Texting Feature Enabled?</label>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label><input type="radio" name="TEXTING_FEATURE_ENABLED" id="TEXTING_FEATURE_ENABLED" value="1" <? if($TEXTING_FEATURE_ENABLED == 1) echo 'checked="checked"'; ?> onclick="showTwilioAccountSetting(this);"/>&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <label><input type="radio" name="TEXTING_FEATURE_ENABLED" id="TEXTING_FEATURE_ENABLED" value="0" <? if($TEXTING_FEATURE_ENABLED == 0) echo 'checked="checked"'; ?> onclick="showTwilioAccountSetting(this);"/>&nbsp;No</label>
                                                    </div>
                                                </div>

                                                <div class="row twilio_account_type" id="twilio_account_type" style="display: <?=($TEXTING_FEATURE_ENABLED=='1')?'':'none'?>;">
                                                    <div class="col-md-6">
                                                        <label><input type="radio" name="TWILIO_ACCOUNT_TYPE" id="TWILIO_ACCOUNT_TYPE" value="0" <? if($TWILIO_ACCOUNT_TYPE == 0) echo 'checked="checked"'; ?> />&nbsp;Using Doable's Twilio account</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <label><input type="radio" name="TWILIO_ACCOUNT_TYPE" id="TWILIO_ACCOUNT_TYPE" value="1" <? if($TWILIO_ACCOUNT_TYPE == 1) echo 'checked="checked"'; ?> />&nbsp;Using Their own Twilio Account</label>
                                                    </div>
                                                </div>

                                                <div class="row" style="margin-bottom: 15px; margin-top: 20px;">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Username Prefix
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="USERNAME_PREFIX" name="USERNAME_PREFIX" class="form-control" placeholder="Enter Username Prefix" value="<?php echo $USERNAME_PREFIX?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if(!empty($_GET['id'])) { ?>
                                                    <div class="row" style="margin-bottom: 15px; margin-top: 15px;">
                                                        <div class="col-md-1">
                                                            <label class="form-label">Active : </label>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                        </div>
                                                    </div>
                                                <? } ?>

                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                            </div>
                                        </form>
                                    </div>


                                    <?php if(empty($_GET['id'])) { ?>
                                    <!--User Profile Info Tab-->
                                    <div class="tab-pane p-20" id="profile" role="tabpanel">
                                        <form class="form-material form-horizontal" id="profile_info_form">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveProfileInfoData">
                                            <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?=$PK_ACCOUNT_MASTER?>">
                                            <input type="hidden" class="PK_USER_EDIT" name="PK_USER_EDIT" value="<?=$PK_USER_EDIT?>">
                                            <div class="p-20">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="form-label mb-0">Roles</label>
                                                        <input type="hidden" name="PK_ROLES" value="2">
                                                        <input type="text" class="form-control" value="Account Admin" readonly>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">User Name<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="USER_NAME" name="USER_NAME" class="form-control" placeholder="Enter User Name" required data-validation-required-message="This field is required" onkeyup="ValidateUsername()" value="<?=$USER_NAME?>">
                                                            </div>
                                                        </div>
                                                        <span id="lblError" style="color: red"></span>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">First Name<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" required value="<?=$FIRST_NAME?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Last Name
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?=$LAST_NAME?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Email<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" required data-validation-required-message="This field is required" value="<?=$EMAIL_ID?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Gender</label>
                                                            <select class="form-control form-control" id="GENDER" name="GENDER">
                                                                <option value="1" <?php if($GENDER == "1") echo 'selected = "selected"';?>>Male</option>
                                                                <option value="2" <?php if($GENDER == "2") echo 'selected = "selected"';?>>Female</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Date of Birth</label>
                                                            <input type="text" class="form-control datepicker-past"  id="DOB" name="DOB" value="<?=($DOB)?date('m/d/Y', strtotime($DOB)):''?>">
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Address
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Apt/Ste
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS_1?>">
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>


                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Country</label>
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
                                                            <label class="col-md-12">State</label>
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
                                                                <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city" value="<?php echo $CITY?>">
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
                                                            <label class="col-md-12">Phone
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone No." value="<?php echo $PHONE?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Remarks</label>
                                                            <div class="col-md-12">
                                                                <textarea class="form-control" rows="2" id="NOTES" name="NOTES"><?php echo $NOTES?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Image Upload</label>
                                                            <div class="col-md-12">
                                                                <input type="file" name="USER_IMAGE" id="USER_IMAGE" class="form-control">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <?php if($USER_IMAGE!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $USER_IMAGE;?>" data-fancybox-group="gallery"><img src = "<?php echo $USER_IMAGE;?>" style="width:120px; height:120px" /></a></div><?php } ?>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Password<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="password" autocomplete="off" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Confirm Password<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="password" autocomplete="off" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12">
                                                        <span style="color:red">Note  : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
                                                    </div>
                                                </div>
                                                <div class="row" style="margin-bottom: 20px;">
                                                    <div class="col-2">
                                                        Password Strength:
                                                    </div>
                                                    <div class="col-3">
                                                        <small id="password-text"></small>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <label class="col-md-12"><input type="checkbox" id="ABLE_TO_EDIT_PAYMENT_GATEWAY" name="ABLE_TO_EDIT_PAYMENT_GATEWAY" class="form-check-inline" <?=($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1)?'checked':''?>> Able to edit payment gateway</label>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                    <?php } else { ?>
                                    <!--User List Tab-->
                                    <div class="tab-pane p-20" id="login" role="tabpanel">
                                        <table id="myTable" class="table table-striped border">
                                            <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Name</th>
                                                <th>Username</th>
                                                <th>Roles</th>
                                                <th>Email Id</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>

                                            <tbody>
                                            <?php
                                            $i=1;
                                            $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES IN(2,3,5,6,7,8) AND DOA_USERS.PK_ACCOUNT_MASTER='$_GET[id]'");
                                            while (!$row->EOF) {
                                                $selected_roles = [];
                                                if(!empty($row->fields['PK_USER'])) {
                                                    $PK_USER = $row->fields['PK_USER'];
                                                    $selected_roles_row = $db->Execute("SELECT DOA_ROLES.ROLES FROM `DOA_USER_ROLES` LEFT JOIN DOA_ROLES ON DOA_USER_ROLES.PK_ROLES = DOA_ROLES.PK_ROLES WHERE `PK_USER` = '$PK_USER'");
                                                    while (!$selected_roles_row->EOF) {
                                                        $selected_roles[] = $selected_roles_row->fields['ROLES'];
                                                        $selected_roles_row->MoveNext();
                                                    }
                                                } ?>
                                                <tr>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$i;?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['NAME']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['USER_NAME']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=implode(', ', $selected_roles)?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['EMAIL_ID']?></td>
                                                    <td style="padding: 10px 0px 0px 0px;font-size: 20px;">
                                                        <a href="edit_account_user.php?id=<?=$row->fields['PK_USER']?>&ac_id=<?=$_GET['id']?>" title="Reset Password" style="color: #03a9f3;"><i class="ti-lock"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <?php if($row->fields['ACTIVE']==1){ ?>
                                                            <span title="Active" class="active-box-green"></span>
                                                        <?php } else{ ?>
                                                            <span title="Inactive" class="active-box-red"></span>
                                                        <?php } ?>&nbsp;&nbsp;
                                                        <a href="javascript:;" data-href="account.php?id=<?=$_GET['id']?>&PK_USER=<?=$row->fields['PK_USER']?>&cond=del" onclick="confirmDelete(this);" title="Delete" style="color: red;"><i class="ti-trash"></i></a>
                                                    </td>
                                                </tr>
                                                <?php $row->MoveNext();
                                                $i++; } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php } ?>


                                    <div class="tab-pane p-20" id="billing" role="tabpanel">
                                        <form class="form-material form-horizontal" id="billingForm" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveBillingData">
                                            <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?=$PK_ACCOUNT_MASTER?>">
                                            <div class="p-20">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Subscription Start Date</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" placeholder="Select Date" value="<?=($START_DATE == '') ? '' : date('m/d/Y', strtotime($START_DATE))?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Next Renewal Date</label>
                                                            <div class="col-md-12">
                                                                <p><?=($NEXT_RENEWAL_DATE == '') ? '' : date('m/d/Y', strtotime($NEXT_RENEWAL_DATE))?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Status</label>
                                                            <div class="col-md-12">
                                                                <p><?=$STATUS?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="row">
                                                            <div class="form-group">
                                                                <label class="form-label" style="margin-bottom: 5px;">Billing Type</label><br>
                                                                <label style="margin-right: 70px;"><input type="radio" name="BILLING_TYPE" class="form-check-inline BILLING_TYPE" value="PER_ACCOUNT" onchange="calculatePaymentAmount()" <?=($BILLING_TYPE == 'PER_ACCOUNT') ? 'checked' : ''?>>Bill Per Account</label>
                                                                <label style="margin-right: 70px;"><input type="radio" name="BILLING_TYPE" class="form-check-inline BILLING_TYPE" value="PER_LOCATION" onchange="calculatePaymentAmount()" <?=($BILLING_TYPE == 'PER_LOCATION') ? 'checked' : ''?>>Bill Per Location</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Amount</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="AMOUNT" name="AMOUNT" class="form-control" placeholder="Enter Amount" onkeyup="calculatePaymentAmount()" value="<?=$AMOUNT?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Total Amount</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="TOTAL_AMOUNT" name="TOTAL_AMOUNT" class="form-control" placeholder="Total Amount" readonly value="<?=$TOTAL_AMOUNT?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="add_card">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div id="card-element"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php
                                                foreach ($account_payment_data AS $key => $value) {
                                                    switch ($value['CARD_TYPE']) {
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

                                                    }
                                                    if ($value['PK_LOCATION'] == null) { ?>
                                                        <input type="hidden" name="CUSTOMER_ID" value="<?=$value['CUSTOMER_ID']?>">
                                                        <div class="per_account" style="display: <?=($BILLING_TYPE == 'PER_ACCOUNT') ? '' : 'none'?>">
                                                            <div class="credit-card <?=$card_type?> selectable" style="margin-right: 50%;">
                                                                <div class="credit-card-last4">
                                                                    <?=$value['LAST4']?>
                                                                </div>
                                                                <div class="credit-card-expiry">
                                                                    <?=$value['EXP_MONTH'].'/'.$value['EXP_YEAR']?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="per_location" style="display: <?=($BILLING_TYPE == 'PER_LOCATION') ? '' : 'none'?>;">
                                                            <div class="row" style="margin-bottom: 25px;">
                                                                <div class="col-6">
                                                                    <label style="margin-bottom: 10px;">Location</label>
                                                                    <select class="multi_select_location" name="PK_LOCATION[]">
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = ".$PK_ACCOUNT_MASTER);
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=($row->fields['PK_LOCATION'] == $value['PK_LOCATION']) ? 'selected' : ''?>><?=$row->fields['LOCATION_NAME']?></option>
                                                                        <?php $row->MoveNext(); } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" name="LOCATION_CUSTOMER_ID[]" value="<?=$value['CUSTOMER_ID']?>">
                                                            <div class="credit-card <?=$card_type?> selectable" style="margin-right: 50%;">
                                                                <div class="credit-card-last4">
                                                                    <?=$value['LAST4']?>
                                                                </div>
                                                                <div class="credit-card-expiry">
                                                                    <?=$value['EXP_MONTH'].'/'.$value['EXP_YEAR']?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                <?php }
                                                } ?>
                                            </div>

                                            <div class="form-group">
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Process</button>
                                            </div>
                                        </form>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    .progress-bar {
        border-radius: 5px;
        height:18px !important;
    }
</style>
<?php require_once('../includes/footer.php');?>

<script src="http://igorescobar.github.io/jQuery-Mask-Plugin/js/jquery.mask.min.js"></script>


<script>
    $('.datepicker-past').datepicker({
        format: 'mm/dd/yyyy',
        maxDate: 0
    });

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $(document).ready(function() {
        fetch_state(<?php  echo $PK_COUNTRY; ?>);
        fetch_Account_State(<?php  echo $ACCOUNT_PK_COUNTRY; ?>);
    });

    function fetch_state(PK_COUNTRY){
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
    }

    function fetch_Account_State(PK_COUNTRY){
        let data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$ACCOUNT_PK_STATES;?>";
        let value = $.ajax({
            url: "ajax/state.php",
            type: "POST",
            data: data,
            async: false,
            cache :false,
            success: function (result) {
                document.getElementById('Account_State_div').innerHTML = result;

            }
        }).responseText;
    }

</script>
<script>
    function isGood(password) {
        //alert(password);
        var password_strength = document.getElementById("password-text");

        //TextBox left blank.
        if (password.length == 0) {
            password_strength.innerHTML = "";
            return;
        }

        //Regular Expressions.
        var regex = new Array();
        regex.push("[A-Z]"); //Uppercase Alphabet.
        regex.push("[a-z]"); //Lowercase Alphabet.
        regex.push("[0-9]"); //Digit.
        regex.push("[$@$!%*#?&]"); //Special Character.

        var passed = 0;

        //Validate for each Regular Expression.
        for (var i = 0; i < regex.length; i++) {
            if (new RegExp(regex[i]).test(password)) {
                passed++;
            }
        }

        //Display status.
        var strength = "";
        switch (passed) {
            case 0:
            case 1:
            case 2:
                strength = "<small class='progress-bar bg-danger' style='width: 50%'>Weak</small>";
                break;
            case 3:
                strength = "<small class='progress-bar bg-warning' style='width: 60%'>Medium</small>";
                break;
            case 4:
                strength = "<small class='progress-bar bg-success' style='width: 100%'>Strong</small>";
                break;

        }
        // alert(strength);
        password_strength.innerHTML = strength;

    }

    function ValidateUsername() {
        var username = document.getElementById("User_Id").value;
        var lblError = document.getElementById("lblError");
        lblError.innerHTML = "";
        var expr = /^[a-zA-Z0-9_]{8,20}$/;
        if (!expr.test(username)) {
            lblError.innerHTML = "Only Alphabets, Numbers and Underscore and between 8 to 20 characters.";
        }
        else{
            lblError.innerHTML = "";
        }
    }

    function editpage(PK_USER, AC_ID){
        window.location.href = "edit_account_user.php?id="+PK_USER+"&ac_id="+AC_ID;
    }

    function confirmDelete(anchor)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=$(anchor).data("href");
    }

    $(document).on('click', '#cancel_button', function () {
        window.location.href='all_accounts.php';
    });

    let PK_ACCOUNT_MASTER = parseInt(<?=empty($_GET['id'])?0:$_GET['id']?>);

    $(document).on('submit', '#account_info_form', function (event) {
        event.preventDefault();
        let form_data = $('#account_info_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            dataType: 'JSON',
            success:function (data) {
                $('.PK_ACCOUNT_MASTER').val(data);
                if (PK_ACCOUNT_MASTER == 0) {
                    $('#profile_tab_link')[0].click();
                }else{
                   window.location.href='all_accounts.php';
                }
            }
        });
    });

    $(document).on('submit', '#profile_info_form', function (event) {
        event.preventDefault();
        let form_data = $('#profile_info_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            dataType: 'JSON',
            success:function (data) {
                $('.PK_ACCOUNT_MASTER').val(data);
                window.location.href='all_accounts.php';
            }
        });
    });
</script>

<script>
    function calculatePaymentAmount() {
        let BILLING_TYPE = $('.BILLING_TYPE:checked').val();
        let AMOUNT = $('#AMOUNT').val();
        let LOCATION_COUNT = parseInt(<?=$location_count?>);

        if (BILLING_TYPE == 'PER_ACCOUNT') {
            $('#TOTAL_AMOUNT').val(AMOUNT);
            $('.per_account').show();
            $('.per_location').hide();
        } else {
            $('#TOTAL_AMOUNT').val(AMOUNT*LOCATION_COUNT);
            $('.per_account').hide();
            $('.per_location').show();
        }
    }

    function showTwilioAccountSetting(param) {
        if($(param).val() === '1'){
            $('#twilio_account_type').slideDown();
        }else {
            $('#twilio_account_type').slideUp();
        }
    }
</script>


</body>
</html>