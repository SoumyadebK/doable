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

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1) {
    header("location:../login.php");
    exit;
}

if (!empty($_GET['cond']) && $_GET['cond'] == 'del') {
    $db->Execute("DELETE FROM `DOA_USERS` WHERE `PK_USER` = " . $_GET['PK_USER']);
    header('location:account.php?id=' . $_GET['id']);
}

$PK_USER = 0;
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
$TICKET_SYSTEM_ACCESS = '';
$USERNAME_PREFIX = '';
$FOCUSBIZ_API_KEY = '';
$AM_AMOUNT       = '';
$NOT_AM_AMOUNT      = '';
$RENEWAL_INTERVAL = '';

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
if (!empty($_GET['id'])) {
    $account_res = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER`  = '$_GET[id]'");
    if ($account_res->RecordCount() == 0) {
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
    $FOCUSBIZ_API_KEY = $account_res->fields['FOCUSBIZ_API_KEY'];
    $ACTIVE = $account_res->fields['ACTIVE'];
    $AM_AMOUNT = $account_res->fields['AM_AMOUNT'];
    $NOT_AM_AMOUNT = $account_res->fields['NOT_AM_AMOUNT'];
    $RENEWAL_INTERVAL = $account_res->fields['RENEWAL_INTERVAL'];

    $user_res = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_ACCOUNT_MASTER = '$_GET[id]' AND PK_USER = '$_GET[user_id]'");
    if ($user_res->RecordCount() > 0) {
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
        $TICKET_SYSTEM_ACCESS = $user_res->fields['TICKET_SYSTEM_ACCESS'];
    }

    $user_billing_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_BILLING_DETAILS WHERE PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
    if ($user_billing_data->RecordCount() > 0) {
        $START_DATE = $user_billing_data->fields['START_DATE'];
        $BILLING_TYPE = $user_billing_data->fields['BILLING_TYPE'];
        $AMOUNT = $user_billing_data->fields['AMOUNT'];
        $TOTAL_AMOUNT = $user_billing_data->fields['TOTAL_AMOUNT'];
        $NEXT_RENEWAL_DATE = $user_billing_data->fields['NEXT_RENEWAL_DATE'];
        $STATUS = $user_billing_data->fields['STATUS'];
    }
}

if (($AM_AMOUNT == '' || $AM_AMOUNT == 0.00) && ($NOT_AM_AMOUNT == '' || $NOT_AM_AMOUNT == 0.00)) {
    $res = $db->Execute("SELECT * FROM `DOA_OTHER_SETTING`");
    if ($res->RecordCount() > 0) {
        $AM_AMOUNT       = $res->fields['AM_AMOUNT'];
        $NOT_AM_AMOUNT   = $res->fields['NOT_AM_AMOUNT'];
    }
}

$location_data = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE ACTIVE = 1 AND `PK_ACCOUNT_MASTER`  = " . $PK_ACCOUNT_MASTER);
$location_count = ($location_data->RecordCount() > 0) ? $location_data->RecordCount() : 1;

$payment_gateway_setting = $db->Execute("SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");
$SECRET_KEY = $payment_gateway_setting->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_setting->fields['PUBLISHABLE_KEY'];
require_once("../global/stripe-php-master/init.php");
Stripe::setApiKey($SECRET_KEY);

if (!empty($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] == 'saveBillingData') {
    $BILLING_DETAILS['AM_AMOUNT'] = $_POST['AM_AMOUNT'];
    $BILLING_DETAILS['NOT_AM_AMOUNT'] = $_POST['NOT_AM_AMOUNT'];
    $BILLING_DETAILS['RENEWAL_INTERVAL'] = $_POST['RENEWAL_INTERVAL'];
    db_perform('DOA_ACCOUNT_MASTER', $BILLING_DETAILS, 'update', " PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

$account_payment_data = [];
$account_payment_info = $db->Execute("SELECT * FROM DOA_ACCOUNT_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Stripe' AND PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
while (!$account_payment_info->EOF) {
    require_once("../global/stripe-php-master/init.php");
    $stripe = new StripeClient($SECRET_KEY);
    $customer_id = $account_payment_info->fields['ACCOUNT_PAYMENT_ID'];
    $stripe_customer = $stripe->customers->retrieve($customer_id);
    $card_id = $stripe_customer->default_source;

    $url = "https://api.stripe.com/v1/customers/" . $customer_id . "/cards/" . $card_id;
    $AUTH = "Authorization: Bearer " . $SECRET_KEY;

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
<?php require_once('../includes/header.php'); ?>
<style>
    .SumoSelect {
        width: 100%;
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
                                <li class="breadcrumb-item"><a href="all_accounts.php">All Accounts</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 align-self-center">
                    <h4 style="color: #39B54A;">
                        <?php if (!empty($_GET['id'])) {
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
                                    <li class="active"> <a class="nav-link active" id="profile_tab_link" data-bs-toggle="tab" href="#profile" role="tab"><span class="hidden-sm-up"><i class="ti-folder"></i></span> <span class="hidden-xs-down">User Profile</span></a> </li>
                                    <?php if (!empty($_GET['id'])) { ?>
                                        <li> <a class="nav-link" data-bs-toggle="tab" href="#location" role="tab" id="location_tab"><span class="hidden-sm-up"><i class="ti-list"></i></span> <span class="hidden-xs-down">Location List</span></a> </li>
                                    <?php } ?>
                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#billing" role="tab" id="billingtab" onclick="stripePaymentFunction();"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content tabcontent-border">
                                    <!--User Profile Info Tab-->
                                    <div class="tab-pane active" id="profile" role="tabpanel">
                                        <form class="form-material form-horizontal" id="profile_info_form">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveProfileInfoData">
                                            <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?= $PK_ACCOUNT_MASTER ?>">
                                            <input type="hidden" class="PK_USER_EDIT" name="PK_USER_EDIT" value="<?= $PK_USER_EDIT ?>">
                                            <div class="p-20">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="form-label mb-0">Roles</label>
                                                        <input type="hidden" name="PK_ROLES" value="11">
                                                        <input type="text" class="form-control" value="Super Account Admin" readonly>
                                                    </div>
                                                    <?php if (empty($_GET['id'])) { ?>
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label class="col-md-12">User Name<span class="text-danger">*</span>
                                                                </label>
                                                                <div class="col-md-12">
                                                                    <input type="text" id="USER_NAME" name="USER_NAME" class="form-control" placeholder="Enter User Name" required data-validation-required-message="This field is required" value="<?= $USER_NAME ?>">
                                                                    <div id="username_result"></div>
                                                                </div>
                                                            </div>
                                                            <span id="lblError" style="color: red"></span>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label class="col-md-12">User Name<span class="text-danger">*</span>
                                                                </label>
                                                                <div class="col-md-12">
                                                                    <input type="text" id="USER_NAME" name="USER_NAME" class="form-control" placeholder="Enter User Name" required data-validation-required-message="This field is required" <?= empty($USER_NAME) ? '' : 'readonly' ?> value="<?= $USER_NAME ?>">
                                                                    <div id="username_result"></div>
                                                                </div>
                                                            </div>
                                                            <span id="lblError" style="color: red"></span>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">First Name<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" required value="<?= $FIRST_NAME ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Last Name
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?= $LAST_NAME ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Phone<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone No." required value="<?php echo $PHONE ?>">
                                                                <div id="phone_result"></div>
                                                            </div>
                                                            <span id="lblError" style="color: red"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Email<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" required value="<?= $EMAIL_ID ?>">
                                                                <div id="email_result"></div>
                                                            </div>
                                                            <span id="lblError" style="color: red"></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Gender</label>
                                                            <select class="form-control form-control" id="GENDER" name="GENDER">
                                                                <option value="1" <?php if ($GENDER == "1") echo 'selected = "selected"'; ?>>Male</option>
                                                                <option value="2" <?php if ($GENDER == "2") echo 'selected = "selected"'; ?>>Female</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Date of Birth</label>
                                                            <input type="text" class="form-control datepicker-past" id="DOB" name="DOB" value="<?= ($DOB) ? date('m/d/Y', strtotime($DOB)) : '' ?>">
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Address
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Apt/Ste
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS_1 ?>">
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>


                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Country<span class="text-danger">*</span></label>
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12">
                                                                    <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
                                                                        <option value="">Select Country</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_COUNTRY']; ?>" <?= ($row->fields['PK_COUNTRY'] == $PK_COUNTRY) ? "selected" : "" ?>><?= $row->fields['COUNTRY_NAME'] ?></option>
                                                                        <?php $row->MoveNext();
                                                                        } ?>
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
                                                                <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city" value="<?php echo $CITY ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Postal / Zip Code</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Postal / Zip Code" value="<?php echo $ZIP ?>">
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
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Remarks</label>
                                                            <div class="col-md-12">
                                                                <textarea class="form-control" rows="2" id="NOTES" name="NOTES"><?php echo $NOTES ?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <?php if ($USER_IMAGE != '') { ?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $USER_IMAGE; ?>" data-fancybox-group="gallery"><img src="<?php echo $USER_IMAGE; ?>" style="width:120px; height:120px" /></a></div><?php } ?>
                                                </div>

                                                <div class="row">
                                                    <div class="col-5">
                                                        <div class="form-group">
                                                            <label>Password<span class="text-danger">*</span></label>
                                                            <input type="password" autocomplete="off" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1" style="padding-top: 22px;">
                                                        <a href="javascript:" onclick="togglePasswordVisibility()" style="font-size: 25px;"><i class="icon-eye"></i></a>
                                                    </div>
                                                    <div class="col-5">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Confirm Password<span class="text-danger">*</span></label>
                                                            <input type="password" autocomplete="off" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1" style="padding-top: 22px;">
                                                        <a href="javascript:" onclick="toggleConfirmPasswordVisibility()" style="font-size: 25px;"><i class="icon-eye"></i></a>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12">
                                                        <span style="color:red">Note : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
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

                                                <div class="row m-b-20">
                                                    <div class="col-6">
                                                        <label class="col-md-12"><input type="checkbox" id="ABLE_TO_EDIT_PAYMENT_GATEWAY" name="ABLE_TO_EDIT_PAYMENT_GATEWAY" class="form-check-inline" <?= ($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1) ? 'checked' : '' ?>> Able to edit payment gateway</label>
                                                    </div>
                                                </div>

                                                <div class="row m-b-20">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Can Create Support Tickets : </label>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label><input type="radio" name="TICKET_SYSTEM_ACCESS" id="TICKET_SYSTEM_ACCESS" value="1" <?php if ($TICKET_SYSTEM_ACCESS == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <label><input type="radio" name="TICKET_SYSTEM_ACCESS" id="TICKET_SYSTEM_ACCESS" value="0" <?php if ($TICKET_SYSTEM_ACCESS == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                    </div>
                                                </div>

                                                <?php if (!empty($_GET['id'])) { ?>
                                                    <div class="row" style="margin-top: 15px;">
                                                        <div class="col-md-1">
                                                            <label class="form-label">Active : </label>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                        </div>
                                                    </div>
                                                <? } ?>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?= empty($_GET['id']) ? 'Continue' : 'Save' ?></button>
                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                            </div>
                                        </form>
                                    </div>

                                    <?php if (!empty($_GET['id'])) { ?>
                                        <!--User List Tab-->
                                        <div class="tab-pane p-20" id="location" role="tabpanel">
                                            <table id="myTable" class="table table-striped border">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Corporation Name</th>
                                                        <th>Location Name</th>
                                                        <th>City</th>
                                                        <th>Phone</th>
                                                        <th>Email</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <?php
                                                    $i = 1;
                                                    $row = $db->Execute("SELECT DOA_LOCATION.*, DOA_CORPORATION.CORPORATION_NAME FROM `DOA_LOCATION` LEFT JOIN DOA_CORPORATION ON DOA_LOCATION.PK_CORPORATION=DOA_CORPORATION.PK_CORPORATION WHERE DOA_LOCATION.PK_ACCOUNT_MASTER = '$_GET[id]'");
                                                    while (!$row->EOF) { ?>
                                                        <tr class="header" onclick="$(this).next().slideToggle(); showUserListByLocation(<?= $row->fields['PK_LOCATION'] ?>)" style="cursor: pointer;">
                                                            <td><?= $i; ?></td>
                                                            <td><a href="corporation.php?id=<?= $row->fields['PK_CORPORATION'] ?>"><?= $row->fields['CORPORATION_NAME'] ?></a></td>
                                                            <td><a href="location.php?id=<?= $row->fields['PK_LOCATION'] ?>"><?= $row->fields['LOCATION_NAME'] ?></a></td>
                                                            <td><?= $row->fields['CITY'] ?></td>
                                                            <td><?= $row->fields['PHONE'] ?></td>
                                                            <td><?= $row->fields['EMAIL'] ?></td>
                                                            <td>
                                                                <a onclick="showUserListByLocation(<?= $row->fields['PK_LOCATION'] ?>)"><i class="ti-user"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                <?php if ($row->fields['ACTIVE'] == 1) { ?>
                                                                    <span class="active-box-green"></span>
                                                                <?php } else { ?>
                                                                    <span class="active-box-red"></span>
                                                                <?php } ?>
                                                            </td>
                                                        </tr>
                                                        </tr>
                                                        <tr style="display: none">
                                                            <td style="vertical-align: middle;" colspan="7">
                                                                <div id="user_div_<?= $row->fields['PK_LOCATION'] ?>">

                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php $row->MoveNext();
                                                        $i++;
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } ?>


                                    <div class="tab-pane p-20" id="billing" role="tabpanel">
                                        <form class="form-material form-horizontal" id="billingForm" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveBillingData">
                                            <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?= $PK_ACCOUNT_MASTER ?>">
                                            <div class="p-20">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="form-label" style="margin-bottom: 20px;">Auto Renewal Interval</label><br>
                                                            <label style="margin-right: 70px;"><input type="radio" id="RENEWAL_INTERVAL" name="RENEWAL_INTERVAL" class="form-check-inline" value="monthly" <?= ($RENEWAL_INTERVAL == 'monthly' || $RENEWAL_INTERVAL == null || $RENEWAL_INTERVAL == '') ? 'checked' : '' ?>> Monthly</label>
                                                            <label style="margin-right: 70px;"><input type="radio" id="RENEWAL_INTERVAL" name="RENEWAL_INTERVAL" class="form-check-inline" value="yearly" <?= ($RENEWAL_INTERVAL == 'yearly') ? 'checked' : '' ?>> Yearly</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Arthur Murray Location Amount</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="AM_AMOUNT" name="AM_AMOUNT" class="form-control" placeholder="Enter Amount" value="<?= $AM_AMOUNT ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Not Arthur Murray Location Amount</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="NOT_AM_AMOUNT" name="NOT_AM_AMOUNT" class="form-control" placeholder="Enter Amount" value="<?= $NOT_AM_AMOUNT ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
                                                </div>
                                            </div>
                                        </form>

                                        <!-- <form class="form-material form-horizontal" id="billingForm" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveBillingData">
                                            <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?= $PK_ACCOUNT_MASTER ?>">
                                            <div class="p-20">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Subscription Start Date</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" placeholder="Select Date" value="<?= ($START_DATE == '') ? '' : date('m/d/Y', strtotime($START_DATE)) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Next Renewal Date</label>
                                                            <div class="col-md-12">
                                                                <p><?= ($NEXT_RENEWAL_DATE == '') ? '' : date('m/d/Y', strtotime($NEXT_RENEWAL_DATE)) ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Status</label>
                                                            <div class="col-md-12">
                                                                <p><?= $STATUS ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="row">
                                                            <div class="form-group">
                                                                <label class="form-label" style="margin-bottom: 5px;">Billing Type</label><br>
                                                                <label style="margin-right: 70px;"><input type="radio" name="BILLING_TYPE" class="form-check-inline BILLING_TYPE" value="PER_ACCOUNT" onchange="calculatePaymentAmount()" <?= ($BILLING_TYPE == 'PER_ACCOUNT') ? 'checked' : '' ?>>Bill Per Account</label>
                                                                <label style="margin-right: 70px;"><input type="radio" name="BILLING_TYPE" class="form-check-inline BILLING_TYPE" value="PER_LOCATION" onchange="calculatePaymentAmount()" <?= ($BILLING_TYPE == 'PER_LOCATION') ? 'checked' : '' ?>>Bill Per Location</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Amount</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="AMOUNT" name="AMOUNT" class="form-control" placeholder="Enter Amount" onkeyup="calculatePaymentAmount()" value="<?= $AMOUNT ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Total Amount</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="TOTAL_AMOUNT" name="TOTAL_AMOUNT" class="form-control" placeholder="Total Amount" readonly value="<?= $TOTAL_AMOUNT ?>">
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
                                                foreach ($account_payment_data as $key => $value) {
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
                                                        <input type="hidden" name="CUSTOMER_ID" value="<?= $value['CUSTOMER_ID'] ?>">
                                                        <div class="per_account" style="display: <?= ($BILLING_TYPE == 'PER_ACCOUNT') ? '' : 'none' ?>">
                                                            <div class="credit-card <?= $card_type ?> selectable" style="margin-right: 50%;">
                                                                <div class="credit-card-last4">
                                                                    <?= $value['LAST4'] ?>
                                                                </div>
                                                                <div class="credit-card-expiry">
                                                                    <?= $value['EXP_MONTH'] . '/' . $value['EXP_YEAR'] ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="per_location" style="display: <?= ($BILLING_TYPE == 'PER_LOCATION') ? '' : 'none' ?>;">
                                                            <div class="row" style="margin-bottom: 25px;">
                                                                <div class="col-6">
                                                                    <label style="margin-bottom: 10px;">Location</label>
                                                                    <select class="multi_select_location" name="PK_LOCATION[]">
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($row->fields['PK_LOCATION'] == $value['PK_LOCATION']) ? 'selected' : '' ?>><?= $row->fields['LOCATION_NAME'] ?></option>
                                                                        <?php $row->MoveNext();
                                                                        } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" name="LOCATION_CUSTOMER_ID[]" value="<?= $value['CUSTOMER_ID'] ?>">
                                                            <div class="credit-card <?= $card_type ?> selectable" style="margin-right: 50%;">
                                                                <div class="credit-card-last4">
                                                                    <?= $value['LAST4'] ?>
                                                                </div>
                                                                <div class="credit-card-expiry">
                                                                    <?= $value['EXP_MONTH'] . '/' . $value['EXP_YEAR'] ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                <?php }
                                                } ?>
                                            </div>

                                            <div class="form-group">
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Process</button>
                                            </div>
                                        </form> -->

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
            height: 18px !important;
        }
    </style>
    <?php require_once('../includes/footer.php'); ?>

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
            fetch_state(<?php echo $PK_COUNTRY; ?>);
            fetch_Account_State(<?php echo $ACCOUNT_PK_COUNTRY; ?>);
        });

        function fetch_state(PK_COUNTRY) {
            let data = "PK_COUNTRY=" + PK_COUNTRY + "&PK_STATES=<?= $PK_STATES; ?>";
            let value = $.ajax({
                url: "ajax/state.php",
                type: "POST",
                data: data,
                async: false,
                cache: false,
                success: function(result) {
                    document.getElementById('State_div').innerHTML = result;

                }
            }).responseText;
        }

        function fetch_Account_State(PK_COUNTRY) {
            let data = "PK_COUNTRY=" + PK_COUNTRY + "&PK_STATES=<?= $ACCOUNT_PK_STATES; ?>";
            let value = $.ajax({
                url: "ajax/state.php",
                type: "POST",
                data: data,
                async: false,
                cache: false,
                success: function(result) {
                    document.getElementById('Account_State_div').innerHTML = result;

                }
            }).responseText;
        }
    </script>
    <script>
        let PK_USER_EDIT = parseInt(<?= empty($PK_USER_EDIT) ? 0 : $PK_USER_EDIT ?>);

        function togglePasswordVisibility() {
            let passwordInput = document.getElementById("PASSWORD");
            if (passwordInput.type === "password") {
                passwordInput.type = "text"; // Show password
            } else {
                passwordInput.type = "password"; // Hide password
            }
        }

        function toggleConfirmPasswordVisibility() {
            let passwordInput = document.getElementById("CONFIRM_PASSWORD");
            if (passwordInput.type === "password") {
                passwordInput.type = "text"; // Show password
            } else {
                passwordInput.type = "password"; // Hide password
            }
        }

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
            } else {
                lblError.innerHTML = "";
            }
        }

        function editpage(PK_LOCATION) {
            window.location.href = "user_list.php?id=" + PK_LOCATION;
        }

        function confirmDelete(anchor) {
            let conf = confirm("Are you sure you want to delete?");
            if (conf)
                window.location = $(anchor).data("href");
        }

        $(document).on('click', '#cancel_button', function() {
            window.location.href = 'all_accounts.php';
        });

        let PK_ACCOUNT_MASTER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);

        $(document).on('submit', '#account_info_form', function(event) {
            event.preventDefault();
            let form_data = $('#account_info_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                dataType: 'JSON',
                success: function(data) {
                    $('.PK_ACCOUNT_MASTER').val(data);
                    if (PK_ACCOUNT_MASTER == 0) {
                        $('#profile_tab_link')[0].click();
                    } else {
                        window.location.href = 'all_accounts.php';
                    }
                }
            });
        });

        // $(document).on('submit', '#profile_info_form', function(event) {
        //     event.preventDefault();
        //     let PK_USER = $('.PK_USER').val();
        //     const PHONE = $('#PHONE').val().trim();
        //     const EMAIL_ID = $('#EMAIL_ID').val().trim();
        //     const USER_NAME = $('#USER_NAME').val().trim();
        //     $.ajax({
        //         url: 'ajax/username_checker.php',
        //         type: 'post',
        //         data: {
        //             USER_NAME: USER_NAME
        //         },
        //         success: function(response) {
        //             if (response && PK_USER_EDIT == 0) {
        //                 $('#USER_NAME').focus();
        //                 $('#username_result').html(response);
        //             } else {
        //                 let form_data = $('#profile_info_form').serialize();
        //                 $.ajax({
        //                     url: "ajax/AjaxFunctions.php",
        //                     type: 'POST',
        //                     data: form_data,
        //                     dataType: 'JSON',
        //                     success: function(data) {
        //                         $('.PK_ACCOUNT_MASTER').val(data);
        //                         window.location.href = 'all_accounts.php';
        //                     }
        //                 });
        //             }
        //         }
        //     });
        // });

        $(document).on('submit', '#profile_info_form', function(event) {
            event.preventDefault();
            let PK_USER = $('.PK_USER_EDIT').val();
            const PHONE = $('#PHONE').val().trim();
            const EMAIL_ID = $('#EMAIL_ID').val().trim();
            const USER_NAME = $('#USER_NAME').val().trim();
            const OLD_PHONE = '<?= $PHONE ?>';
            const OLD_EMAIL = '<?= $EMAIL_ID ?>';
            //alert(PHONE + ' ' + EMAIL_ID + ' ' + USER_NAME);
            if (PHONE != '') {
                $.ajax({
                    url: 'ajax/username_checker.php',
                    type: 'post',
                    data: {
                        PHONE: PHONE
                    },
                    success: function(response) {
                        if (response && (PK_USER == 0 || OLD_PHONE != PHONE)) {
                            $('#phone_result').html(response);
                        } else {
                            $('#phone_result').html('');
                            if (EMAIL_ID != '') {
                                $.ajax({
                                    url: 'ajax/username_checker.php',
                                    type: 'post',
                                    data: {
                                        EMAIL_ID: EMAIL_ID
                                    },
                                    success: function(response) {
                                        if (response && (PK_USER == 0 || OLD_EMAIL != EMAIL_ID)) {
                                            $('#email_result').html(response);
                                        } else {
                                            $('#email_result').html('');
                                            if (USER_NAME != '') {
                                                $.ajax({
                                                    url: 'ajax/username_checker.php',
                                                    type: 'post',
                                                    data: {
                                                        USER_NAME: USER_NAME
                                                    },
                                                    success: function(response) {
                                                        if (response && PK_USER == 0) {
                                                            $('#username_result').html(response);
                                                        } else {
                                                            $('#username_result').html('');
                                                            let form_data = $('#profile_info_form').serialize();
                                                            $.ajax({
                                                                url: "ajax/AjaxFunctions.php",
                                                                type: 'POST',
                                                                data: form_data,
                                                                dataType: 'JSON',
                                                                success: function(data) {
                                                                    $('.PK_ACCOUNT_MASTER').val(data);
                                                                    window.location.href = 'all_accounts.php';
                                                                }
                                                            });
                                                        }
                                                    }
                                                });
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    }
                });
            }
        });
    </script>

    <script>
        function calculatePaymentAmount() {
            let BILLING_TYPE = $('.BILLING_TYPE:checked').val();
            let AMOUNT = $('#AMOUNT').val();
            let LOCATION_COUNT = parseInt(<?= $location_count ?>);

            if (BILLING_TYPE == 'PER_ACCOUNT') {
                $('#TOTAL_AMOUNT').val(AMOUNT);
                $('.per_account').show();
                $('.per_location').hide();
            } else {
                $('#TOTAL_AMOUNT').val(AMOUNT * LOCATION_COUNT);
                $('.per_account').hide();
                $('.per_location').show();
            }
        }

        function showTwilioAccountSetting(param) {
            if ($(param).val() === '1') {
                $('#twilio_account_type').slideDown();
            } else {
                $('#twilio_account_type').slideUp();
            }
        }

        // function showUserListByLocation(param, PK_LOCATION) {
        //     let $nextRows = $(param).nextUntil('tr.header');

        //     if ($nextRows.length) {
        //         // If details are already shown, remove them
        //         $nextRows.remove();
        //     } else {
        //         // Otherwise, fetch and show details
        //         $.ajax({
        //             url: "ajax/get_user_list.php",
        //             type: 'GET',
        //             data: { PK_LOCATION: PK_LOCATION },
        //             success: function (result) {
        //                 $('#user_div_'+PK_LOCATION).html(result);
        //             }
        //         });
        //     }
        // }

        function showUserListByLocation(PK_LOCATION) {
            if (PK_LOCATION) {
                $.ajax({
                    url: "ajax/get_user_list.php",
                    type: "POST",
                    data: {
                        PK_LOCATION: PK_LOCATION
                    },
                    async: false,
                    cache: false,
                    success: function(result) {
                        $('#user_div_' + PK_LOCATION).html(result);
                    }
                });
            }
        }
    </script>


</body>

</html>