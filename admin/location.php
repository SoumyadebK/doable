<?php

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

require_once('../global/config.php');
global $db;
global $db_account;
global $upload_path;

if (empty($_GET['id']))
    $title = "Add Location";
else
    $title = "Edit Location";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

$FRANCHISE = 0;
$franchise_data = $db->Execute("SELECT FRANCHISE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
if ($franchise_data->RecordCount() > 0) {
    $FRANCHISE = $franchise_data->fields['FRANCHISE'];
}

if(empty($_GET['id'])){
    $account_res = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER`  = '$_SESSION[PK_ACCOUNT_MASTER]'");

    $PK_LOCATION = 0;
    $LOCATION_NAME = '';
    $LOCATION_CODE = '';
    $ADDRESS = $account_res->fields['ADDRESS'];
    $ADDRESS_1 = $account_res->fields['ADDRESS_1'];
    $PK_COUNTRY = $account_res->fields['PK_COUNTRY'];
    $PK_STATES = $account_res->fields['PK_STATES'];
    $CITY = $account_res->fields['CITY'];
    $ZIP_CODE = $account_res->fields['ZIP'];
    $PHONE = $account_res->fields['PHONE'];
    $EMAIL = $account_res->fields['EMAIL'];
    $IMAGE_PATH = '';
    $PK_TIMEZONE = '';
    $ROYALTY_PERCENTAGE = '';
    $ACTIVE = '';
    $PAYMENT_GATEWAY_TYPE = '';
    $SECRET_KEY = '';
    $PUBLISHABLE_KEY = '';
    $ACCESS_TOKEN = '';
    $SQUARE_APP_ID ='';
    $SQUARE_LOCATION_ID = '';
    $LOGIN_ID = '';
    $TRANSACTION_KEY = '';
    $AUTHORIZE_CLIENT_KEY = '';
    $AM_USER_NAME = '';
    $AM_PASSWORD = '';
    $AM_REFRESH_TOKEN = '';
    $SALES_TAX = '';
    $RECEIPT_CHARACTER = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE `PK_LOCATION` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_locations.php");
        exit;
    }

    $PK_LOCATION = $_GET['id'];
    $LOCATION_NAME = $res->fields['LOCATION_NAME'];
    $LOCATION_CODE = $res->fields['LOCATION_CODE'];
    $ADDRESS = $res->fields['ADDRESS'];
    $ADDRESS_1 = $res->fields['ADDRESS_1'];
    $PK_COUNTRY = $res->fields['PK_COUNTRY'];
    $PK_STATES = $res->fields['PK_STATES'];
    $CITY = $res->fields['CITY'];
    $ZIP_CODE = $res->fields['ZIP_CODE'];
    $PHONE = $res->fields['PHONE'];
    $EMAIL = $res->fields['EMAIL'];
    $IMAGE_PATH = $res->fields['IMAGE_PATH'];
    $PK_TIMEZONE = $res->fields['PK_TIMEZONE'];
    $ROYALTY_PERCENTAGE = $res->fields['ROYALTY_PERCENTAGE'];
    $ACTIVE = $res->fields['ACTIVE'];
    $PAYMENT_GATEWAY_TYPE   = $res->fields['PAYMENT_GATEWAY_TYPE'];
    $SECRET_KEY             = $res->fields['SECRET_KEY'];
    $PUBLISHABLE_KEY        = $res->fields['PUBLISHABLE_KEY'];
    $ACCESS_TOKEN           = $res->fields['ACCESS_TOKEN'];
    $SQUARE_APP_ID          = $res->fields['APP_ID'];
    $SQUARE_LOCATION_ID     = $res->fields['LOCATION_ID'];
    $LOGIN_ID               = $res->fields['LOGIN_ID'];
    $TRANSACTION_KEY        = $res->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY   = $res->fields['AUTHORIZE_CLIENT_KEY'];
    $AM_USER_NAME           = $res->fields['AM_USER_NAME'];
    $AM_PASSWORD            = $res->fields['AM_PASSWORD'];
    $AM_REFRESH_TOKEN       = $res->fields['AM_REFRESH_TOKEN'];
    $SALES_TAX              = $res->fields['SALES_TAX'];
    $RECEIPT_CHARACTER      = $res->fields['RECEIPT_CHARACTER'];
}

$SMTP_HOST = '';
$SMTP_PORT = '';
$SMTP_USERNAME = '';
$SMTP_PASSWORD = '';
$email = $db_account->Execute("SELECT * FROM DOA_EMAIL_ACCOUNT WHERE PK_LOCATION = ".$PK_LOCATION);
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
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'location'");
if($help->RecordCount() > 0) {
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
}

require_once("../global/stripe-php-master/init.php");
$stripe = new StripeClient($STRIPE_SECRET_KEY);
$account_payment_info = $db->Execute("SELECT * FROM DOA_ACCOUNT_PAYMENT_INFO WHERE PK_LOCATION = ".$PK_LOCATION." AND PAYMENT_TYPE = 'Stripe' AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
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
    if ($_POST['FUNCTION_NAME'] == 'saveLocationData') {
        $EMAIL_DATA['HOST'] = $_POST['SMTP_HOST'];
        $EMAIL_DATA['PORT'] = $_POST['SMTP_PORT'];
        $EMAIL_DATA['USER_NAME'] = $_POST['SMTP_USERNAME'];
        $EMAIL_DATA['PASSWORD'] = $_POST['SMTP_PASSWORD'];
        unset($_POST['FUNCTION_NAME']);
        unset($_POST['SMTP_HOST']);
        unset($_POST['SMTP_PORT']);
        unset($_POST['SMTP_USERNAME']);
        unset($_POST['SMTP_PASSWORD']);
        $LOCATION_DATA = $_POST;
        $LOCATION_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];

        if ($_FILES['IMAGE_PATH']['name'] != '') {
            if (!file_exists('../'.$upload_path.'/location_image/')) {
                mkdir('../'.$upload_path.'/location_image/', 0777, true);
                chmod('../'.$upload_path.'/location_image/', 0777);
            }

            $extn = explode(".", $_FILES['IMAGE_PATH']['name']);
            $iindex = count($extn) - 1;
            $rand_string = time() . "-" . rand(100000, 999999);
            $file11 = 'location_image_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
            $extension = strtolower($extn[$iindex]);

            if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
                $image_path = '../'.$upload_path.'/location_image/' . $file11;
                move_uploaded_file($_FILES['IMAGE_PATH']['tmp_name'], $image_path);
                $LOCATION_DATA['IMAGE_PATH'] = $image_path;
            }
        }

        if (empty($_GET['id'])) {
            $LOCATION_DATA['ACTIVE'] = 1;
            $LOCATION_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $LOCATION_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_LOCATION', $LOCATION_DATA, 'insert');
            $PK_LOCATION = $db->insert_ID();
            $LOCATION_ARRAY = explode(',', $_SESSION['DEFAULT_LOCATION_ID']);
            $LOCATION_ARRAY[] = $PK_LOCATION;
            $_SESSION['DEFAULT_LOCATION_ID'] = implode(',', $LOCATION_ARRAY);
        } else {
            $LOCATION_DATA['ACTIVE'] = $_POST['ACTIVE'];
            $LOCATION_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $LOCATION_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_LOCATION', $LOCATION_DATA, 'update', " PK_LOCATION =  '$_GET[id]'");
            $PK_LOCATION = $_GET['id'];
        }
        $EMAIL_DATA['PK_LOCATION'] = $PK_LOCATION;
        $EMAIL_DATA['ACTIVE'] = 1;
        $EMAIL_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_DATA['CREATED_ON'] = date("Y-m-d H:i");

        $email = $db_account->Execute("SELECT * FROM DOA_EMAIL_ACCOUNT WHERE PK_LOCATION = ".$PK_LOCATION);
        if ($email->RecordCount() == 0) {
            db_perform_account('DOA_EMAIL_ACCOUNT', $EMAIL_DATA, 'insert');
        } else {
            $EMAIL_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $EMAIL_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_EMAIL_ACCOUNT', $EMAIL_DATA, 'update', " PK_LOCATION = '$PK_LOCATION'");
        }
    }

    if ($_POST['FUNCTION_NAME'] == 'saveOperationalHours') {
        $ALL_DAYS = isset($_POST['ALL_DAYS'])?1:0;
        $operational_hours = $db_account->Execute("SELECT * FROM DOA_OPERATIONAL_HOUR WHERE `PK_LOCATION` = '$_GET[id]'");
        if($operational_hours->RecordCount() > 0){
            for ($i = 0; $i < count($_POST['OPEN_TIME']); $i++) {
                $PK_LOCATION = (int)$_GET['id'];
                $DAY_NUMBER = (int)($i+1);
                $OPERATIONAL_HOUR_DATA['PK_LOCATION'] = $_GET['id'];
                $OPERATIONAL_HOUR_DATA['DAY_NUMBER'] = $i + 1;
                $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = ($ALL_DAYS == 0) ? (($_POST['OPEN_TIME'][$i])?date('H:i', strtotime($_POST['OPEN_TIME'][$i])):'') : date('H:i', strtotime($_POST['OPEN_TIME'][0]));
                $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = ($ALL_DAYS == 0) ? (($_POST['CLOSE_TIME'][$i])?date('H:i', strtotime($_POST['CLOSE_TIME'][$i])):'') : date('H:i', strtotime($_POST['CLOSE_TIME'][0]));
                $OPERATIONAL_HOUR_DATA['CLOSED'] = isset($_POST['CLOSED_'.$i])?1:0;
                db_perform_account('DOA_OPERATIONAL_HOUR', $OPERATIONAL_HOUR_DATA, 'update', " PK_LOCATION =  $PK_LOCATION AND DAY_NUMBER = $DAY_NUMBER");
            }
        }else {
            if (count($_POST['OPEN_TIME']) > 0) {
                for ($i = 0; $i < count($_POST['OPEN_TIME']); $i++) {
                    $OPERATIONAL_HOUR_DATA['PK_LOCATION'] = $_GET['id'];
                    $OPERATIONAL_HOUR_DATA['DAY_NUMBER'] = $i + 1;
                    $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = ($ALL_DAYS == 0) ? (($_POST['OPEN_TIME'][$i]) ? date('H:i', strtotime($_POST['OPEN_TIME'][$i])) : '') : date('H:i', strtotime($_POST['OPEN_TIME'][0]));
                    $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = ($ALL_DAYS == 0) ? (($_POST['CLOSE_TIME'][$i]) ? date('H:i', strtotime($_POST['CLOSE_TIME'][$i])) : '') : date('H:i', strtotime($_POST['CLOSE_TIME'][0]));
                    $OPERATIONAL_HOUR_DATA['CLOSED'] = isset($_POST['CLOSED_' . $i]) ? 1 : 0;
                    db_perform_account('DOA_OPERATIONAL_HOUR', $OPERATIONAL_HOUR_DATA, 'insert');
                }
            }
        }
    }

    if ($_POST['FUNCTION_NAME'] == 'saveCreditCard') {
        $STRIPE_TOKEN = $_POST['token'];
        $ACCOUNT_PAYMENT_ID = '';
        if ($account_payment_info->RecordCount() > 0) {
            $ACCOUNT_PAYMENT_ID = $account_payment_info->fields['ACCOUNT_PAYMENT_ID'];
        } else {
            $account_data = $db->Execute("SELECT BUSINESS_NAME FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
            try {
                $customer = $stripe->customers->create([
                    'email' => $EMAIL,
                    'name' => $account_data->fields['BUSINESS_NAME'].'('.$LOCATION_NAME.')',
                    'phone' => $PHONE,
                    'description' => $account_data->fields['BUSINESS_NAME'].'('.$LOCATION_NAME.')',
                ]);
                $ACCOUNT_PAYMENT_ID = $customer->id;
            } catch (ApiErrorException $e) {
                pre_r($e->getMessage());
            }

            $STRIPE_DETAILS['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
            $STRIPE_DETAILS['PK_LOCATION'] = $PK_LOCATION;
            $STRIPE_DETAILS['ACCOUNT_PAYMENT_ID'] = $ACCOUNT_PAYMENT_ID;
            $STRIPE_DETAILS['PAYMENT_TYPE'] = 'Stripe';
            $STRIPE_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_ACCOUNT_PAYMENT_INFO', $STRIPE_DETAILS, 'insert');
        }
        $card = $stripe->customers->createSource($ACCOUNT_PAYMENT_ID, ['source' => $STRIPE_TOKEN]);
        $stripe->customers->update($ACCOUNT_PAYMENT_ID, ['default_source' => $card->id]);
    }
    header("location:all_locations.php");
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
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_locations.php">All Locations</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-8">
                    <div class="card">
                        <div class="card-title" style="margin-top: 15px; margin-left: 15px;">
                            <?php
                            if(!empty($_GET['id'])) {
                                echo $LOCATION_NAME;
                            }
                            ?>
                        </div>
                        <div class="card-body">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li> <a class="nav-link active" data-bs-toggle="tab" id="location_link" href="#location_div" role="tab"><span class="hidden-sm-up"><i class="ti-location-pin"></i></span> <span class="hidden-xs-down">Location</span></a> </li>
                                <?php if (!empty($_GET['id'])) { ?>
                                    <li> <a class="nav-link" data-bs-toggle="tab" id="operational_hours_link" href="#operational_hours" role="tab"><span class="hidden-sm-up"><i class="ti-time"></i></span> <span class="hidden-xs-down">Operational Hours</span></a> </li>
                                    <li> <a class="nav-link" data-bs-toggle="tab" id="credit_card_link" href="#credit_card" role="tab" onclick="stripePaymentFunction();"><span class="hidden-sm-up"><i class="ti-credit-card"></i></span> <span class="hidden-xs-down">Credit Card</span></a> </li>
                                <?php } ?>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tab-content tabcontent-border">
                                <div class="tab-pane active" id="location_div" role="tabpanel">
                                    <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveLocationData">
                                        <div class="p-20">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Location<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="LOCATION_NAME" name="LOCATION_NAME" class="form-control" placeholder="Enter Location Name" required value="<?php echo $LOCATION_NAME?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Location Code<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="LOCATION_CODE" name="LOCATION_CODE" class="form-control" placeholder="Enter Location Code" required value="<?php echo $LOCATION_CODE?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Address</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Apt/Ste</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Apartment OR Street" value="<?php echo $ADDRESS_1?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Country<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <div class="col-sm-12">
                                                                <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
                                                                    <option value="">Select Country</option>
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
                                                        <label class="col-md-12" for="example-text">State<span class="text-danger">*</span>
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
                                                        <label class="col-md-12" for="example-text">City</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter City" value="<?php echo $CITY?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Postal / Zip Code</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="ZIP_CODE" name="ZIP_CODE" class="form-control" placeholder="Enter Postal / Zip Code" value="<?php echo $ZIP_CODE?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Phone</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone No." value="<?php echo $PHONE?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Email</label>
                                                        <div class="col-md-12">
                                                            <input type="email" id="EMAIL" name="EMAIL" class="form-control" placeholder="enter Email Address" value="<?php echo $EMAIL?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Location Image</label>
                                                        <div class="col-md-12">
                                                            <input type="file" name="IMAGE_PATH" id="IMAGE_PATH" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Timezone<span class="text-danger">*</span></label>
                                                        <div class="col-md-12">
                                                            <select name="PK_TIMEZONE" id="PK_TIMEZONE" class="form-control required-entry" required>
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
                                            </div>
                                            <div class="row">
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Royalty Percentage</label>
                                                        <div class="input-group">
                                                            <input type="text" name="ROYALTY_PERCENTAGE" id="ROYALTY_PERCENTAGE" class="form-control" value="<?php echo $ROYALTY_PERCENTAGE?>">
                                                            <span class="form-control input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Sales Tax</label>
                                                        <div class="input-group">
                                                            <input type="text" name="SALES_TAX" id="SALES_TAX" class="form-control" value="<?php echo $SALES_TAX?>">
                                                            <span class="form-control input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Receipt Character<span class="text-danger">*</span></label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="RECEIPT_CHARACTER" name="RECEIPT_CHARACTER" class="form-control" placeholder="Receipt Character" required value="<?=$RECEIPT_CHARACTER?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <?php if($IMAGE_PATH!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $IMAGE_PATH;?>" data-fancybox-group="gallery"><img src = "<?php echo $IMAGE_PATH;?>" style="width:120px; height:120px" /></a></div><?php } ?>
                                            </div>

                                            <?php if ($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1) { ?>
                                                <div class="row" style="margin-top: 30px;">
                                                    <b class="btn btn-light" style="margin-bottom: 20px;">Payment Gateway Setting</b>
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

                                            <?php if ($FRANCHISE == 1) { ?>
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
                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <?php if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <?php if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>

                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                            <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_locations.php'">Cancel</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane" id="operational_hours" role="tabpanel">
                                    <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveOperationalHours">
                                        <div class="p-20" id="holiday_list_div">
                                            <div class="row">
                                                <div class="col-3">
                                                    <div class="form-group" style="text-align: center;">
                                                        <label class="form-label" for="example-text" style="font-weight: bold;">Day</label>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group" style="text-align: center;">
                                                        <label class="form-label" for="example-text" style="font-weight: bold;">Open Time</label>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group" style="text-align: center;">
                                                        <label class="form-label" for="example-text" style="font-weight: bold;">Close Time</label>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label><input type="checkbox" name="ALL_DAYS" class="form-check-inline" onclick="applyToAllDays(this)"> All Days</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                            $operational_hours = $db_account->Execute("SELECT * FROM DOA_OPERATIONAL_HOUR WHERE `PK_LOCATION` = '$PK_LOCATION'");
                                            if($operational_hours->RecordCount() > 0) {
                                                $i = 0;
                                                while (!$operational_hours->EOF) { ?>
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <select name="DAY_NUMBER[]" class="form-control required-entry" disabled>
                                                                    <option value="1" <?=($operational_hours->fields['DAY_NUMBER']==1)?'selected':''?>>Monday</option>
                                                                    <option value="2" <?=($operational_hours->fields['DAY_NUMBER']==2)?'selected':''?>>Tuesday</option>
                                                                    <option value="3" <?=($operational_hours->fields['DAY_NUMBER']==3)?'selected':''?>>Wednesday</option>
                                                                    <option value="4" <?=($operational_hours->fields['DAY_NUMBER']==4)?'selected':''?>>Thursday</option>
                                                                    <option value="5" <?=($operational_hours->fields['DAY_NUMBER']==5)?'selected':''?>>Friday</option>
                                                                    <option value="6" <?=($operational_hours->fields['DAY_NUMBER']==6)?'selected':''?>>Saturday</option>
                                                                    <option value="7" <?=($operational_hours->fields['DAY_NUMBER']==7)?'selected':''?>>Sunday</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="OPEN_TIME[]" class="form-control time-input time-picker OPEN_TIME" value="<?=($operational_hours->fields['OPEN_TIME']=='00:00:00')?'':date('h:i A', strtotime($operational_hours->fields['OPEN_TIME']))?>" style="pointer-events: <?=($operational_hours->fields['CLOSED']==1)?'none':''?>" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="CLOSE_TIME[]" class="form-control time-input time-picker CLOSE_TIME" value="<?=($operational_hours->fields['CLOSE_TIME']=='00:00:00')?'':date('h:i A', strtotime($operational_hours->fields['CLOSE_TIME']))?>" style="pointer-events: <?=($operational_hours->fields['CLOSED']==1)?'none':''?>" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12" style="margin-top: 10px;">
                                                                <label><input type="checkbox" name="CLOSED_<?=$i?>" onchange="closeThisDay(this)" <?=($operational_hours->fields['CLOSED']==1)?'checked':''?>> Closed</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php $operational_hours->MoveNext(); $i++;} ?>
                                            <?php } else {
                                                for ($i = 1; $i <= 7; $i++) { ?>
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <select name="DAY_NUMBER[]" class="form-control required-entry" disabled>
                                                                    <option value="1" <?=($i==1)?'selected':''?>>Monday</option>
                                                                    <option value="2" <?=($i==2)?'selected':''?>>Tuesday</option>
                                                                    <option value="3" <?=($i==3)?'selected':''?>>Wednesday</option>
                                                                    <option value="4" <?=($i==4)?'selected':''?>>Thursday</option>
                                                                    <option value="5" <?=($i==5)?'selected':''?>>Friday</option>
                                                                    <option value="6" <?=($i==6)?'selected':''?>>Saturday</option>
                                                                    <option value="7" <?=($i==7)?'selected':''?>>Sunday</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="OPEN_TIME[]" class="form-control time-input time-picker OPEN_TIME" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="CLOSE_TIME[]" class="form-control time-input time-picker CLOSE_TIME" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12" style="margin-top: 10px;">
                                                                <label><input type="checkbox" name="CLOSED_<?=$i-1?>" onchange="closeThisDay(this)"> Closed</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php }
                                            } ?>
                                        </div>
                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
                                        <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_locations.php'">Cancel</button>
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
                                        <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_locations.php'">Cancel</button>
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

<script>
    $('.time-picker').timepicker({
        timeFormat: 'hh:mm p',
        interval: 30,
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });

    function closeThisDay(param){
        if ($(param).is(':checked')){
            $(param).closest('.row').find('.time-input').val('');
            $(param).closest('.row').find('.time-input').css('pointer-events', 'none');
        }else {
            $(param).closest('.row').find('.time-input').css('pointer-events', '');
        }
    }

    $(document).ready(function() {
        fetch_state(<?php  echo $PK_COUNTRY; ?>);
    });

    function fetch_state(PK_COUNTRY){
        jQuery(document).ready(function($) {
            var data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$PK_STATES;?>";
            var value = $.ajax({
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

    function applyToAllDays(param) {
        if ($(param).is(':checked')) {
            let OPEN_TIME = $(".OPEN_TIME");
            $('.OPEN_TIME').val($(OPEN_TIME[0]).val());

            let CLOSE_TIME = $(".CLOSE_TIME");
            $('.CLOSE_TIME').val($(CLOSE_TIME[0]).val());
        } else {
            let OPEN_TIME = $(".OPEN_TIME");
            for(let i = 1; i < 7; i++) {
                $(OPEN_TIME[i]).val('');
            }

            let CLOSE_TIME = $(".CLOSE_TIME");
            for(let i = 1; i < 7; i++) {
                $(CLOSE_TIME[i]).val('');
            }
        }
    }
</script>
</body>
</html>