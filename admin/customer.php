<?php
require_once('../global/config.php');
require_once("../global/stripe-php-master/init.php");
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

use Square\Models\Address;
use Square\SquareClient;
use Square\Environment;

use Dompdf\Dompdf;
use Mpdf\Mpdf;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

$userType = "Customers";

$status_check = empty($_GET['status'])?'active':$_GET['status'];
if ($status_check == 'active'){
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

$user_role_condition = " AND PK_ROLES = 4";
if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

$CREATE_LOGIN = 0;
$user_doc_count = 0;

if (empty($_GET['id'])) {
    $title = "Add " . $userType;
    $header = '';
}
else {
    $title = "Edit " . $userType;
    $header = 'customer.php?id=' . $_GET['id'] . '&master_id=' . $_GET['master_id'] . '&tab=enrollment';
}

$PK_USER = $_GET['id'] ?? '';
$PK_USER_MASTER = $_GET['master_id'] ?? '';

if (!empty($_GET['tab']))
    $title = $userType;

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");

$PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];
$SECRET_KEY = $account_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $account_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $account_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $account_data->fields['LOCATION_ID'];

$card_details = '';

require_once("../global/stripe-php-master/init.php");
$customer_payment_info = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Stripe' AND PK_USER = " . $PK_USER);

if ($SECRET_KEY != '') {
    $stripe = new StripeClient($SECRET_KEY);
    $message = '';

    if ($customer_payment_info->RecordCount() > 0) {
        try {
            $customer_id = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
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
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] == 'saveCreditCard') {
    $STRIPE_TOKEN = $_POST['token'];
    $CUSTOMER_PAYMENT_ID = '';
    if ($customer_payment_info->RecordCount() > 0) {
        $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
    } else {
        try {
            $user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = ".$PK_USER);
            $customer = $stripe->customers->create([
                'email' => $user_data->fields['EMAIL_ID'],
                'name' => $user_data->fields['FIRST_NAME'].' '.$user_data->fields['LAST_NAME'],
                'phone' => $user_data->fields['PHONE'],
                'description' => 'Add Credit Card',
            ]);
            $CUSTOMER_PAYMENT_ID = $customer->id;
        } catch (ApiErrorException $e) {
            pre_r($e->getMessage());
        }

        $CUSTOMER_PAYMENT_DETAILS['PK_USER'] = $PK_USER;
        $CUSTOMER_PAYMENT_DETAILS['CUSTOMER_PAYMENT_ID'] = $CUSTOMER_PAYMENT_ID;
        $CUSTOMER_PAYMENT_DETAILS['PAYMENT_TYPE'] = 'Stripe';
        $CUSTOMER_PAYMENT_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_CUSTOMER_PAYMENT_INFO', $CUSTOMER_PAYMENT_DETAILS, 'insert');
    }
    try {
        $card = $stripe->customers->createSource($CUSTOMER_PAYMENT_ID, ['source' => $STRIPE_TOKEN]);
        $stripe->customers->update($CUSTOMER_PAYMENT_ID, ['default_source' => $card->id]);
    } catch (ApiErrorException $e) {
        pre_r($e->getMessage());
    }
    $message = "Credit Card has been saved";
}

/*$card_number = '';

if($PAYMENT_GATEWAY == "Stripe") {
    $user_payment_info_data = $db->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PK_USER_MASTER = '$_GET[master_id]'");
    if ($user_payment_info_data->RecordCount() > 0) {
        $SECRET_KEY = $account_data->fields['SECRET_KEY'];
        $stripe = new \Stripe\StripeClient($SECRET_KEY);
        $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];

        try {
            $all_payment_methods = $stripe->customers->allPaymentMethods(
                $CUSTOMER_PAYMENT_ID,
                ['type' => 'card']
            );
        } catch (\Stripe\Exception\ApiErrorException $e) {
            pre_r($e->getMessage());
        }

        $card_number = $all_payment_methods->card->last4;
    }
} elseif ($PAYMENT_GATEWAY == "Square") {
    $user_payment_info_data = $db->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PK_USER_MASTER = '$_GET[master_id]'");

    if ($user_payment_info_data->RecordCount() > 0) {
        require_once("../global/vendor/autoload.php");
        $client = new SquareClient([
            'accessToken' => $ACCESS_TOKEN,
            'environment' => Environment::SANDBOX,
        ]);

        $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];
        $card = new \Square\Models\Card();
        $card->setCustomerId($CUSTOMER_PAYMENT_ID);
        $all_payment_methods = $client->getCardsApi()->listCards();
        $all_payment_methods_array = json_decode($all_payment_methods->getBody());

        $card_number = $all_payment_methods_array->cards->last_4;
    }
}*/

$USER_NAME = '';
$FIRST_NAME = '';
$LAST_NAME = '';
$CUSTOMER_ID = '';
$UNIQUE_ID = '';
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
$PASSWORD = '';
$ACTIVE = '';
$WHAT_PROMPTED_YOU_TO_INQUIRE = '';
$PK_SKILL_LEVEL = '';
$PK_INQUIRY_METHOD = '';
$INQUIRY_TAKER_ID = '';
$INQUIRY_DATE = '';
$PK_CUSTOMER_DETAILS = '';
$CALL_PREFERENCE = '';
$REMINDER_OPTION = '';
$SPECIAL_DATE_1 = '';
$DATE_NAME_1 = '';
$SPECIAL_DATE_2 = '';
$DATE_NAME_2 = '';
$ATTENDING_WITH = '';
$PARTNER_FIRST_NAME = '';
$PARTNER_LAST_NAME = '';
$PARTNER_PHONE = '';
$PARTNER_EMAIL = '';
$PARTNER_GENDER = '';
$PARTNER_DOB = '';
$INACTIVE_BY_ADMIN = '';
$CREATED_ON = '';
if(!empty($_GET['id'])) {
    $res = $db->Execute("SELECT * FROM DOA_USERS WHERE IS_DELETED = 0 AND DOA_USERS.PK_USER = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_customers.php");
        exit;
    }
    $USER_NAME = $res->fields['USER_NAME'];
    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
    $CUSTOMER_ID = $res->fields['USER_ID'];
    $UNIQUE_ID = $res->fields['UNIQUE_ID'];
    $EMAIL_ID = $res->fields['EMAIL_ID'];
    $USER_IMAGE = $res->fields['USER_IMAGE'];
    $GENDER = $res->fields['GENDER'];
    $DOB = $res->fields['DOB'];
    $ADDRESS = $res->fields['ADDRESS'];
    $ADDRESS_1 = $res->fields['ADDRESS_1'];
    $PK_COUNTRY = $res->fields['PK_COUNTRY'];
    $PK_STATES = $res->fields['PK_STATES'];
    $CITY = $res->fields['CITY'];
    $ZIP = $res->fields['ZIP'];
    $PHONE = $res->fields['PHONE'];
    $NOTES = $res->fields['NOTES'];
    $ACTIVE = $res->fields['ACTIVE'];
    $PASSWORD = $res->fields['PASSWORD'];
    $INACTIVE_BY_ADMIN = $res->fields['INACTIVE_BY_ADMIN'];
    $CREATE_LOGIN = $res->fields['CREATE_LOGIN'];
    $CREATED_ON = $res->fields['CREATED_ON'];

    $user_interest_other_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_INTEREST_OTHER_DATA` WHERE `PK_USER_MASTER` = '$_GET[master_id]'");
    if($user_interest_other_data->RecordCount() > 0){
        $WHAT_PROMPTED_YOU_TO_INQUIRE = $user_interest_other_data->fields['WHAT_PROMPTED_YOU_TO_INQUIRE'];
        $PK_SKILL_LEVEL = $user_interest_other_data->fields['PK_SKILL_LEVEL'];
        $PK_INQUIRY_METHOD = $user_interest_other_data->fields['PK_INQUIRY_METHOD'];
        $INQUIRY_TAKER_ID = $user_interest_other_data->fields['INQUIRY_TAKER_ID'];
        $INQUIRY_DATE = $user_interest_other_data->fields['INQUIRY_DATE'];
    }

    $customer_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = '$_GET[master_id]'");
    if($customer_data->RecordCount() > 0){
        $PK_CUSTOMER_DETAILS = $customer_data->fields['PK_CUSTOMER_DETAILS'];
        $CALL_PREFERENCE = $customer_data->fields['CALL_PREFERENCE'];
        $REMINDER_OPTION = $customer_data->fields['REMINDER_OPTION'];
        $ATTENDING_WITH = $customer_data->fields['ATTENDING_WITH'];
        $PARTNER_FIRST_NAME = $customer_data->fields['PARTNER_FIRST_NAME'];
        $PARTNER_LAST_NAME = $customer_data->fields['PARTNER_LAST_NAME'];
        $PARTNER_PHONE = $customer_data->fields['PARTNER_PHONE'];
        $PARTNER_EMAIL = $customer_data->fields['PARTNER_EMAIL'];
        $PARTNER_GENDER = $customer_data->fields['PARTNER_GENDER'];
        $PARTNER_DOB = $customer_data->fields['PARTNER_DOB'];
    }
}

$primary_location = 0;
if(!empty($_GET['master_id'])) {
    $selected_primary_location = $db->Execute("SELECT PRIMARY_LOCATION_ID FROM DOA_USER_MASTER WHERE PK_USER_MASTER = " . $_GET['master_id']);
    if ($selected_primary_location->RecordCount() > 0) {
        $primary_location = $selected_primary_location->fields['PRIMARY_LOCATION_ID'];
    }
}

if ($PK_USER_MASTER > 0) {
    makeMiscComplete($PK_USER_MASTER);
    makeExpiryEnrollmentComplete($PK_USER_MASTER);
    checkAllEnrollmentStatus($PK_USER_MASTER);
}
?>
<!DOCTYPE html>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<html lang="en">
<style>
    .commentModel {
        z-index: 1011
    }
    .page-titles {
        padding: 0;
        position: fixed;
        width: auto;
        *background-color: whitesmoke;
        z-index: 1000; /* Ensure it's above other content */
        margin: 0 0 0 0 !important;
    }
    .SumoSelect {
        width: 100% !important;
    }

</style>
<style>
    table th{
        font-weight:bold;
    }

    /* Table sort indicators */

    th.sortable {
        position: relative;
        cursor: pointer;
    }

    th.sortable::after {
        font-family: FontAwesome;
        content: "\f0dc";
        position: absolute;
        right: 8px;
        color: #999;
    }

    th.sortable.asc::after {
        content: "\f0d8";
    }

    th.sortable.desc::after {
        content: "\f0d7";
    }

    th.sortable:hover::after {
        color: #333;
    }

</style>
<!-- CSS for Popup -->
<style>
    .popup {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        justify-content: center;
        align-items: center;
    }

    .popup-content {
        background-color: white;
        padding: 20px;
        border-radius: 10px;
        max-width: 80%;
        text-align: center;
    }

    .close {
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 30px;
        color: white;
        cursor: pointer;
    }
</style>
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

    nav-link {
        border: 1px solid #555;
    }
</style>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content" style="position: sticky; z-index: 1;">
            <div class="row page-titles" style="width: 97%;">
                <!--<div class="col-md-6 align-self-center">
                    <h4 class="text-themecolor"><?php /*if(!empty($_GET['id'])) {
                            echo "Edit ".$FIRST_NAME." ".$LAST_NAME;
                        }*/?></h4>
                </div>-->
                <div class="col-md-2 align-self-end">
                <?php if(!empty($_GET['id'])) { ?>
                    <select required name="NAME" id="NAME" onchange="editpage(this);">
                        <option value="">Select Customer</option>
                        <?php
                        $row = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY DOA_USERS.FIRST_NAME");
                        while (!$row->EOF) {?>
                            <option value="<?php echo $row->fields['PK_USER'];?>" data-master_id="<?php echo $row->fields['PK_USER_MASTER'];?>" <?=($row->fields['PK_USER_MASTER']==$_GET['master_id'])?'selected':''?>><?=$row->fields['NAME'].' ('.$row->fields['USER_NAME'].')'?></option>
                        <?php $row->MoveNext(); } ?>
                    </select>
                <?php } ?>
                </div>
                <div class="col-md-8 align-self-center">
                    <ul class="nav nav-pills navbar-expand-lg navbar-light bg-light px-2 py-1 d-non" role="tablist" style="width: 124%">
                        <?php if(in_array('Customers Profile Edit', $PERMISSION_ARRAY)){ ?>
                        <li> <a class="nav-link active" id="profile_tab_link" data-bs-toggle="tab" href="#profile" role="tab" style="font-weight: bold; font-size: 13px"><span class="hidden-sm-up"><i class="ti-id-badge"></i></span> <span class="hidden-xs-down">Profile</span></a> </li>
                        <li id="login_info_tab" style="display: <?=($CREATE_LOGIN == 1)?'':'none'?>"> <a class="nav-link" id="login_info_tab_link" data-bs-toggle="tab" href="#login" role="tab" style="font-weight: bold; font-size: 13px"><span class="hidden-sm-up"><i class="ti-lock"></i></span> <span class="hidden-xs-down">Login Info</span></a> </li>
                        <li> <a class="nav-link" data-bs-toggle="tab" href="#family" id="family_tab_link" role="tab" style="font-weight: bold; font-size: 13px"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Family</span></a> </li>
                        <?php } ?>
                        <!--<li> <a class="nav-link" data-bs-toggle="tab" href="#interest" id="interest_tab_link" role="tab" ><span class="hidden-sm-up"><i class="ti-pencil-alt"></i></span> <span class="hidden-xs-down">Interests</span></a> </li>-->

                        <?php if(!empty($_GET['id'])) { ?>
                            <?php if(in_array('Customers Profile Edit', $PERMISSION_ARRAY)){ ?>
                            <li> <a class="nav-link" id="document_tab_link" data-bs-toggle="tab" href="#document" onclick="showAgreementDocument()" role="tab" style="font-weight: bold; font-size: 13px"><span class="hidden-sm-up"><i class="ti-files"></i></span> <span class="hidden-xs-down">Documents</span></a> </li>
                            <li> <a class="nav-link" id="enrollment_tab_link" data-bs-toggle="tab" href="#enrollment" onclick="showEnrollmentList(1, 'normal')" role="tab" style="font-weight: bold; font-size: 13px"><span class="hidden-sm-up"><i class="ti-list"></i></span> <span class="hidden-xs-down">Active Enrollments</span></a> </li>
                            <li> <a class="nav-link" id="completed_enrollment_tab_link" data-bs-toggle="tab" href="#enrollment" onclick="showEnrollmentList(1, 'completed')" role="tab" style="font-weight: bold; font-size: 13px"><span class="hidden-sm-up"><i class="ti-view-list"></i></span> <span class="hidden-xs-down">Completed Enrollments</span></a> </li>
                            <li> <a class="nav-link" id="appointment_tab_link" data-bs-toggle="tab" href="#appointment" onclick="showAppointment(1, 'posted')" role="tab" style="font-weight: bold; font-size: 13px"><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">Appointments</span></a> </li>
                            <li> <a class="nav-link" id="appointment_tab_link" data-bs-toggle="tab" href="#demo_appointment" onclick="showDemoAppointment(1)" role="tab" style="font-weight: bold; font-size: 13px"><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">For Record Only</span></a> </li>
                            <!--<li> <a class="nav-link" data-bs-toggle="tab" href="#billing" onclick="showBillingList(1)" role="tab" ><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>-->
                            <!--<li> <a class="nav-link" data-bs-toggle="tab" href="#accounts" onclick="showLedgerList(1)" role="tab" ><span class="hidden-sm-up"><i class="ti-book"></i></span> <span class="hidden-xs-down">Enrollment</span></a> </li>-->
                            <li> <a class="nav-link" id="comment_tab_link" data-bs-toggle="tab" href="#comments" role="tab" style="font-weight: bold; font-size: 13px"><span class="hidden-sm-up"><i class="ti-comment"></i></span> <span class="hidden-xs-down">Comments</span></a> </li>
                            <li> <a class="nav-link" id="wallet_tab_link" data-bs-toggle="tab" href="#credit_card" role="tab" style="font-weight: bold; font-size: 13px"><span class="hidden-sm-up"><i class="ti-credit-card"></i></span> <span class="hidden-xs-down">Credit Card</span></a> </li>
                            <li> <a class="nav-link" id="wallet_tab_link" data-bs-toggle="tab" href="#wallet" role="tab" style="font-weight: bold; font-size: 13px"><span class="hidden-sm-up"><i class="ti-wallet"></i></span> <span class="hidden-xs-down">Wallet</span></a> </li>
                            <?php } ?>
                            <?php if(in_array('Customers Delete', $PERMISSION_ARRAY)){ ?>
                            <li> <a class="nav-link" id="delete_tab_link" data-bs-toggle="tab" href="#delete_customer" role="tab" style="font-weight: bold; font-size: 13px"><span class="hidden-sm-up"><i class="ti-trash"></i></span> <span class="hidden-xs-down">Delete</span></a> </li>
                            <?php } ?>
                        <?php } ?>
                    </ul>
                </div>
                <!--<div class="col-md-1 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center" style="width: 240px;">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><a href="all_customers.php">All Customers</a></li>
                            <li class="breadcrumb-item active"><a href="customer.php"><?php /*=$title*/?></a></li>
                        </ol>
                    </div>
                </div>-->
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <!--<div class="row">
                                        <div class="col-12 d-flex justify-content-end align-items-center" style="font-weight: bold; font-size: 15px; margin-top: 15px;">
                                            <?php
/*                                            $row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER FROM `DOA_ENROLLMENT_MASTER` WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]' ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC");

                                            $total_amount = 0;
                                            $total_paid_amount = 0;
                                            $total_used_amount = 0;
                                            $total_session_count = 0;
                                            $used_session_count = 0;

                                            while (!$row->EOF) {
                                                $billing_data = $db_account->Execute("SELECT SUM(TOTAL_AMOUNT) AS TOTAL_AMOUNT FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
                                                $total_amount += ($billing_data->RecordCount() > 0) ? $billing_data->fields['TOTAL_AMOUNT'] : 0;

                                                $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                                                while (!$serviceCodeData->EOF)
                                                {
                                                    $used_session = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT FROM `DOA_APPOINTMENT_MASTER` WHERE PK_APPOINTMENT_STATUS = 2 AND `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']." AND PK_SERVICE_CODE = ".$serviceCodeData->fields['PK_SERVICE_CODE']);
                                                    $paid_session = ($serviceCodeData->fields['PRICE_PER_SESSION'] > 0) ? $serviceCodeData->fields['TOTAL_AMOUNT_PAID']/$serviceCodeData->fields['PRICE_PER_SESSION'] : 1;
                                                    $total_paid_amount += $serviceCodeData->fields['TOTAL_AMOUNT_PAID'];
                                                    $total_used_amount += ($serviceCodeData->fields['PRICE_PER_SESSION']*$used_session->fields['USED_SESSION_COUNT']);
                                                    $total_session_count += $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                                    $used_session_count += $used_session->fields['USED_SESSION_COUNT'];

                                                    $serviceCodeData->MoveNext();
                                                }
                                                $row->MoveNext();
                                            }
                                            */?>
                                            <?php /*if (!empty($_GET['id'])) { */?>
                                                <div class="col-2 text-center">Enrolled : <?php /*=number_format($total_amount, 2);*/?></div>
                                                <div class="col-2 text-center">Paid : <?php /*=number_format($total_paid_amount, 2);*/?></div>
                                                <div class="col-2 text-center">Used : <?php /*=number_format((float)$total_used_amount, 2);*/?></div>
                                                <div class="col-2 text-center">Balance : <?php /*=number_format($total_amount-$total_paid_amount, 2)*/?></div>
                                                <div class="col-2 text-center" style="color:<?php /*=($total_paid_amount-$total_used_amount<0)?'red':'black'*/?>;">Service Credit : <?php /*=number_format((float)$total_paid_amount-$total_used_amount, 2);*/?></div>
                                                <div class="col-2 text-center">Session : <?php /*=$used_session_count.'/'.$total_session_count;*/?></div>
                                            <?php /*}*/?>
                                        </div>
                                    </div>-->
                                    <div class="card-body" style="margin-top: 25px;">
                                        <div class="tab-content tabcontent-border">
                                            <div class="tab-pane active" id="profile" role="tabpanel">
                                                <form class="form-material form-horizontal" id="profile_form">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveProfileData">
                                                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?=$PK_USER?>">
                                                    <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?=$PK_USER_MASTER?>">
                                                    <input type="hidden" class="TYPE" name="TYPE" value="2">
                                                    <div class="p-20">
                                                        <div class="row">
                                                            <div class="col-4">
                                                                <div class="form-group">
                                                                    <label class="form-label">First Name<span class="text-danger">*</span></label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" required value="<?=$FIRST_NAME?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-4">
                                                                <div class="form-group">
                                                                    <label class="form-label">Last Name</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?=$LAST_NAME?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-2">
                                                                <div class="form-group">
                                                                    <label class="form-label">Customer ID</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" id="CUSTOMER_ID" name="CUSTOMER_ID" class="form-control" placeholder="Enter User Name" value="<?=$CUSTOMER_ID?>">
                                                                        <div id="uname_result"></div>
                                                                    </div>
                                                                    <span id="lblError" style="color: red"></span>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <label class="form-label" style="font-size: 16px"><strong>#<?=$UNIQUE_ID?></strong></label>
                                                                    <input type="hidden" id="UNIQUE_ID" name="UNIQUE_ID" value="<?=$UNIQUE_ID?>">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <input type="hidden" name="PK_ROLES[]" value="4">
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <label class="form-label">Phone<span class="text-danger">*</span></label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone Number" value="<?php echo $PHONE?>" required>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="addMorePhone();"><i class="ti-plus"></i> New</a>
                                                            </div>
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <label class="form-label">Email<span class="text-danger">*</span></label>
                                                                    <div class="col-md-12">
                                                                        <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" value="<?=$EMAIL_ID?>" required>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="addMoreEmail();"><i class="ti-plus"></i> New</a>
                                                            </div>
                                                            <div class="col-2">
                                                                <label class="col-md-12 mt-3"><input type="checkbox" id="CREATE_LOGIN" name="CREATE_LOGIN" class="form-check-inline" <?=($CREATE_LOGIN == 1)?'checked':''?> style="margin-top: 30px;" onchange="createLogin(this);"> Create Login</label>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label class="form-label">Created On</label>
                                                                    <input type="text" class="form-control datepicker-normal" id="CREATED_ON" name="CREATED_ON" value="<?=($CREATED_ON == '' || $CREATED_ON == '0000-00-00')?date('m/d/Y'):date('m/d/Y', strtotime($CREATED_ON))?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-5" id="add_more_phone">
                                                                <?php
                                                                if(!empty($_GET['id'])) {
                                                                    $customer_phone = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PHONE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                                                                    while (!$customer_phone->EOF) { ?>
                                                                        <div class="row">
                                                                            <div class="col-9">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Phone<span class="text-danger">*</span></label>
                                                                                    <div class="col-md-12">
                                                                                        <input type="text" name="CUSTOMER_PHONE[]" class="form-control" placeholder="Enter Phone Number" value="<?=$customer_phone->fields['PHONE']?>" required>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-2" style="padding-top: 25px;">
                                                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                            </div>
                                                                        </div>
                                                                        <?php $customer_phone->MoveNext(); } ?>
                                                                <?php } ?>
                                                            </div>
                                                            <div class="col-5" id="add_more_email">
                                                                <?php
                                                                if(!empty($_GET['id'])) {
                                                                    $customer_email = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_EMAIL WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                                                                    while (!$customer_email->EOF) { ?>
                                                                        <div class="row">
                                                                            <div class="col-9">
                                                                                <div class="form-group">
                                                                                    <label class="col-md-12">Email<span class="text-danger">*</span></label>
                                                                                    <div class="col-md-12">
                                                                                        <input type="email" name="CUSTOMER_EMAIL[]" class="form-control" placeholder="Enter Email Address" value="<?=$customer_email->fields['EMAIL']?>" required>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-2" style="padding-top: 25px;">
                                                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                            </div>
                                                                        </div>
                                                                        <?php $customer_email->MoveNext(); } ?>
                                                                <?php } ?>
                                                            </div>
                                                        </div>

                                                        <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?=$PK_CUSTOMER_DETAILS?>">
                                                        <div class="row">
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <label class="form-label">Call Preference</label>
                                                                    <div class="col-md-12 custom-select">
                                                                        <select class="form-control" name="CALL_PREFERENCE">
                                                                            <option >Select</option>
                                                                            <option value="email" <?php if($CALL_PREFERENCE == "email") echo 'selected = "selected"';?>>Email</option>
                                                                            <option value="text message" <?php if($CALL_PREFERENCE == "text message") echo 'selected = "selected"';?>>Text Message</option>
                                                                            <option value="phone call" <?php if($CALL_PREFERENCE == "phone call") echo 'selected = "selected"';?>>Phone Call</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-9">
                                                                <div class="form-group">
                                                                    <label class="form-label">Reminder Options</label>
                                                                    <div class="row m-t-10">
                                                                        <div class="col-md-4">
                                                                            <label><input type="checkbox" class="form-check-inline" name="REMINDER_OPTION[]" <?=in_array('Email', explode(',', $REMINDER_OPTION))?'checked':''?> value="Email"> Email</label>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label><input type="checkbox" class="form-check-inline" name="REMINDER_OPTION[]" <?=in_array('Text Message', explode(',', $REMINDER_OPTION))?'checked':''?> value="Text Message"> Text Message</label>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label><input type="checkbox" class="form-check-inline" name="REMINDER_OPTION[]" <?=in_array('Phone Call', explode(',', $REMINDER_OPTION))?'checked':''?> value="Phone Call"> Phone Call</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Gender</label>
                                                                    <div class="custom-select">
                                                                        <select class="form-control" id="GENDER" name="GENDER">
                                                                            <option value="">Select Gender</option>
                                                                            <option value="Male" <?php if($GENDER == "Male") echo 'selected = "selected"';?>>Male</option>
                                                                            <option value="Female" <?php if($GENDER == "Female") echo 'selected = "selected"';?>>Female</option>
                                                                            <option value="Other" <?php if($GENDER == "Other") echo 'selected = "selected"';?>>Other</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Date of Birth</label>
                                                                    <input type="text" class="form-control datepicker-past" id="DOB" name="DOB" value="<?=($DOB == '' || $DOB == '0000-00-00')?'':date('m/d/Y', strtotime($DOB))?>">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">Address</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">Apt/Ste</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS_1?>">

                                                                    </div>
                                                                </div>

                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">Country<span class="text-danger">*</span></label>
                                                                    <div class="col-md-12">
                                                                        <div class="col-sm-12 custom-select">
                                                                            <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
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
                                                                    <label class="col-md-12">State<span class="text-danger">*</span></label>
                                                                    <div class="col-md-12">
                                                                        <div class="col-sm-12 custom-select">
                                                                            <div id="State_div"></div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">City</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city" value="<?php echo $CITY?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">Postal / Zip Code</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Postal / Zip Code" value="<?php echo $ZIP?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-6">
                                                                <label class="col-md-12">Primary Location<span class="text-danger">*</span></label>
                                                                <div class="custom-select" style="margin-bottom: 15px;">
                                                                    <select class="form-control" name="PRIMARY_LOCATION_ID" id="PK_LOCATION_SINGLE" onchange="selectThisPrimaryLocation(this)" required>
                                                                        <option value="">Select Primary Location</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=($primary_location == $row->fields['PK_LOCATION'])?"selected":""?>><?=$row->fields['LOCATION_NAME']?></option>
                                                                            <?php $row->MoveNext(); } ?>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="col-6">
                                                                <label class="col-md-12">Preferred Location</label>
                                                                <div class="col-md-12 multiselect-box" style="width: 100%;">
                                                                    <?php
                                                                    $selected_location = [];
                                                                    if(!empty($_GET['id'])) {
                                                                        $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = '$_GET[id]'");
                                                                        while (!$selected_location_row->EOF) {
                                                                            $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                                                            $selected_location_row->MoveNext();
                                                                        }
                                                                    }
                                                                    ?>
                                                                    <input type="hidden" id="selected_location" value="<?=implode(',', $selected_location);?>">
                                                                    <select class="multi_sumo_select" name="PK_USER_LOCATION[]" id="PK_LOCATION_MULTIPLE" multiple>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION != '$primary_location' AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=in_array($row->fields['PK_LOCATION'], $selected_location)?"selected":""?>><?=$row->fields['LOCATION_NAME']?></option>
                                                                        <?php $row->MoveNext(); } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">Remarks</label>
                                                                    <div class="col-md-12">
                                                                        <textarea class="form-control" rows="3" id="NOTES" name="NOTES"><?php echo $NOTES?></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-2" style="margin-left: 80%">
                                                                <div class="form-group">
                                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 15px;" onclick="addMoreSpecialDays(this);"><i class="ti-plus"></i> New</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="add_more_special_days">
                                                            <?php
                                                            $customer_special_date = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_SPECIAL_DATE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                                                            if($customer_special_date->RecordCount() > 0) {
                                                                while (!$customer_special_date->EOF) { ?>
                                                                    <div class="row">
                                                                        <div class="col-5">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Special Date</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="CUSTOMER_SPECIAL_DATE[]" value="<?=$customer_special_date->fields['SPECIAL_DATE']?>">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-5">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Date Name</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="text" class="form-control" name="CUSTOMER_SPECIAL_DATE_NAME[]" value="<?=$customer_special_date->fields['DATE_NAME']?>">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-2" style="padding-top: 25px;">
                                                                            <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                        </div>
                                                                    </div>
                                                                    <?php $customer_special_date->MoveNext(); } ?>
                                                            <?php } else { ?>
                                                                <div class="row">
                                                                    <div class="col-5">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Special Date</label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="CUSTOMER_SPECIAL_DATE[]">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-5">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Date Name</label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" class="form-control" name="CUSTOMER_SPECIAL_DATE_NAME[]">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-2" style="padding-top: 25px;">
                                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                        <hr>

                                                        <div class="row">
                                                            <div class="col-8">
                                                                <div class="form-group">
                                                                    <div class="row m-t-10">
                                                                        <div class="col-md-4">
                                                                            <label class="form-label">Will you be attending your lessons</label>
                                                                        </div>
                                                                        <div class="col-md-2">
                                                                            <label><input type="radio" name="ATTENDING_WITH" class="form-check-inline" onclick="($(this).is(':checked'))?$('#partner_details').slideUp():$('#partner_details').slideDown()" value="Solo" <?=(($ATTENDING_WITH == '')?'checked':(($ATTENDING_WITH=='Solo')?'checked':''))?>> Solo</label>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <label><input type="radio" name="ATTENDING_WITH" class="form-check-inline" onclick="($(this).is(':checked'))?$('#partner_details').slideDown():$('#partner_details').slideUp()" value="With a Partner" <?=(($ATTENDING_WITH=='With a Partner')?'checked':'')?>> With a Partner</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div id="partner_details" style="display: <?=(($ATTENDING_WITH=='With a Partner')?'':'none')?>;">
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Partner's First Name<span class="text-danger">*</span></label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" class="form-control" placeholder="Enter Partner's First Name" name="PARTNER_FIRST_NAME" value="<?=$PARTNER_FIRST_NAME?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Partner's Last Name</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" class="form-control" placeholder="Enter Partner's Last Name" name="PARTNER_LAST_NAME" value="<?=$PARTNER_LAST_NAME?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Partner's Phone</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" class="form-control" placeholder="Enter Partner's Phone" name="PARTNER_PHONE" value="<?=$PARTNER_PHONE?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Partner's Email</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" class="form-control" placeholder="Enter Partner's Email" name="PARTNER_EMAIL" value="<?=$PARTNER_EMAIL?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Partner's Gender</label>
                                                                        <div class="custom-select">
                                                                            <select class="form-control" id="PARTNER_GENDER" name="PARTNER_GENDER">
                                                                                <option value="">Select Gender</option>
                                                                                <option value="Male" <?=(($PARTNER_GENDER=='Male')?'selected':'')?>>Male</option>
                                                                                <option value="Female" <?=(($PARTNER_GENDER=='Female')?'selected':'')?>>Female</option>
                                                                                <option value="Other" <?=(($PARTNER_GENDER=='Other')?'selected':'')?>>Other</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Partner's Date of Birth</label>
                                                                        <input type="text" class="form-control datepicker-past" name="PARTNER_DOB" value="<?=($PARTNER_DOB=='' || $PARTNER_DOB == '0000-00-00')?'':date('m/d/Y', strtotime($PARTNER_DOB))?>">
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
                                                                <div class="form-group">
                                                                    <?php if($USER_IMAGE!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $USER_IMAGE;?>" data-fancybox-group="gallery"><img src = "<?php echo $USER_IMAGE;?>" style="width:120px; height:120px" /></a></div><?php } ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <?php if(!empty($_GET['id'])) { ?>
                                                            <div class="row <?=($INACTIVE_BY_ADMIN == 1)?'div_inactive':''?>" style="margin-bottom: 15px; margin-top: 15px;">
                                                                <div class="col-md-1">
                                                                    <label class="form-label">Active : </label>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE_CUSTOMER" value="1" <?php if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE_CUSTOMER" value="0" <?php if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                    <div class="form-group">
                                                        <button type="submit" id="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                        <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <div class="tab-pane" id="login" role="tabpanel">
                                                <form id="login_form">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveLoginData">
                                                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?=$PK_USER?>">
                                                    <input type="hidden" class="TYPE" name="TYPE" value="2">
                                                    <div class="p-20">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">User Name</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" id="USER_NAME" name="USER_NAME" class="form-control" placeholder="Enter User Name" onkeyup="ValidateUsername()" value="<?=$USER_NAME?>">
                                                                        <a class="btn-link" onclick="$('#change_password_div').slideToggle();">Change Password</a>
                                                                    </div>
                                                                </div>
                                                                <span id="lblError" style="color: red"></span>

                                                            </div>
                                                        </div>

                                                        <?php if(empty($_GET['id']) || $PASSWORD == '') { ?>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">Password</label>
                                                                        <div class="col-md-12">
                                                                            <input type="password" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">Confirm Password</label>
                                                                        <div class="col-md-12">
                                                                            <input type="password" class="form-control" placeholder="Confirm Password" aria-label="Password" aria-describedby="basic-addon3" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <b id="password_error" style="color: red;"></b>
                                                            <div class="row" id="password_note">
                                                                <div class="col-12">
                                                                    <span style="color: orange;">Note  : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-2">
                                                                    Password Strength:
                                                                </div>
                                                                <div class="col-3">
                                                                    <small id="password-text"></small>
                                                                </div>
                                                            </div>
                                                        <?php } else { ?>
                                                            <div class="row">
                                                                <div class="row" id="change_password_div" style="padding: 20px 20px 0px 20px; display: none;">
                                                                    <!--<div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Old Password</label>
                                                                            <input type="hidden" name="SAVED_OLD_PASSWORD" id="SAVED_OLD_PASSWORD" value="<?php /*$PASSWORD */?>">
                                                                            <input type="password" required name="OLD_PASSWORD" id="OLD_PASSWORD" class="form-control">
                                                                        </div>
                                                                    </div>-->
                                                                    <div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">New Password</label>
                                                                            <input type="password" name="PASSWORD" class="form-control" id="PASSWORD">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Confirm New Password</label>
                                                                            <input type="password" name="CONFIRM_PASSWORD" class="form-control" id="CONFIRM_PASSWORD">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <b id="password_error" style="color: red;"></b>
                                                            </div>
                                                        <?php } ?>

                                                        <?php if(!empty($_GET['id'])) { ?>
                                                            <div class="row <?=($INACTIVE_BY_ADMIN == 1)?'div_inactive':''?>" style="margin-bottom: 15px; margin-top: 15px;">
                                                                <div class="col-md-1">
                                                                    <label class="form-label">Active : </label>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE_CUSTOMER" value="1" <?php if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE_CUSTOMER" value="0" <?php if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                    <div class="form-group">
                                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                        <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <?php $family_member_count = 0;?>
                                            <div class="tab-pane" id="family" role="tabpanel">
                                                <form id="family_form" class="form-material form-horizontal">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveFamilyData">
                                                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?=$PK_USER?>">
                                                    <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?=$PK_USER_MASTER?>">
                                                    <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?=$PK_CUSTOMER_DETAILS?>">
                                                    <input type="hidden" class="TYPE" name="TYPE" value="2">
                                                    <div class="row" style="margin-bottom: 25px;">
                                                        <a href="javascript:;" style="float: right; margin-left: 91%; margin-top: 10px; color: green;" onclick="addMoreFamilyMember();"><b><i class="ti-plus"></i> New</b></a>
                                                    </div>
                                                    <?php
                                                    $family_member_details = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_DETAILS WHERE PK_CUSTOMER_PRIMARY = '$PK_CUSTOMER_DETAILS' AND IS_PRIMARY = 0");
                                                    if($PK_CUSTOMER_DETAILS > 0 && $family_member_details->RecordCount() > 0) {
                                                        while (!$family_member_details->EOF) { ?>
                                                            <div class="row family_member" style="padding: 35px; margin-top: -60px;">
                                                                <div class="row">
                                                                    <div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">First Name<span class="text-danger">*</span></label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" name="FAMILY_FIRST_NAME[]" class="form-control" placeholder="Enter First Name" value="<?=$family_member_details->fields['FIRST_NAME']?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Last Name</label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" name="FAMILY_LAST_NAME[]" class="form-control" placeholder="Enter Last Name" value="<?=$family_member_details->fields['LAST_NAME']?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Relationship</label>
                                                                            <div class="col-md-12 custom-select">
                                                                                <select class="form-control" name="PK_RELATIONSHIP[]">
                                                                                    <option>Select Relationship</option>
                                                                                    <?php
                                                                                    $row = $db->Execute("SELECT * FROM DOA_RELATIONSHIP WHERE ACTIVE = 1");
                                                                                    while (!$row->EOF) { ?>
                                                                                        <option value="<?php echo $row->fields['PK_RELATIONSHIP'];?>" <?=($family_member_details->fields['PK_RELATIONSHIP']==$row->fields['PK_RELATIONSHIP'])?'selected':''?> ><?=$row->fields['RELATIONSHIP']?></option>
                                                                                        <?php $row->MoveNext(); } ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-2">
                                                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="$(this).closest('.row').next().slideToggle();"><i class="ti-arrow-circle-down"></i> More Info</a>
                                                                    </div>
                                                                    <div class="col-1">
                                                                        <a href="javascript:;" class="btn btn-danger waves-effect waves-light text-white" style="margin-top: 30px;" onclick="removeThisFamilyMember(this);"><b><i class="ti-trash"></i></b></a>
                                                                    </div>
                                                                </div>

                                                                <div style="display: none;">
                                                                    <div class="row">
                                                                        <div class="col-5">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Phone</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="FAMILY_PHONE[]" class="form-control" placeholder="Enter Phone Number" value="<?=$family_member_details->fields['PHONE']?>">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-5">
                                                                            <div class="form-group">
                                                                                <label class="col-md-12">Email</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="email" name="FAMILY_EMAIL[]" class="form-control" placeholder="Enter Email Address" value="<?=$family_member_details->fields['EMAIL']?>">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Gender</label>
                                                                                <div class="custom-select">
                                                                                    <select class="form-control" name="FAMILY_GENDER[]">
                                                                                        <option>Select Gender</option>
                                                                                        <option value="Male" <?php if($family_member_details->fields['GENDER'] == "Male") echo 'selected = "selected"';?>>Male</option>
                                                                                        <option value="Female" <?php if($family_member_details->fields['GENDER'] == "Female") echo 'selected = "selected"';?>>Female</option>
                                                                                        <option value="Other" <?php if($family_member_details->fields['GENDER'] == "Other") echo 'selected = "selected"';?>>Other</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Date of Birth</label>
                                                                                <input type="text" class="form-control datepicker-past" name="FAMILY_DOB[]" value="<?=($family_member_details->fields['DOB']=='' || $family_member_details->fields['DOB']=='0000-00-00')?'':date('m/d/Y', strtotime($family_member_details->fields['DOB']))?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="row">
                                                                        <div class="col-2" style="margin-left: 80%">
                                                                            <div class="form-group">
                                                                                <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 15px;" data-counter="<?=$family_member_count?>" onclick="addMoreSpecialDaysFamily(this);"><i class="ti-plus"></i> New</a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="add_more_special_days">
                                                                        <?php
                                                                        $family_special_date = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_SPECIAL_DATE WHERE PK_CUSTOMER_DETAILS = ".$family_member_details->fields['PK_CUSTOMER_DETAILS']);
                                                                        if($family_special_date->RecordCount() > 0) {
                                                                            while (!$family_special_date->EOF) { ?>
                                                                                <div class="row">
                                                                                    <div class="col-5">
                                                                                        <div class="form-group">
                                                                                            <label class="form-label">Special Date</label>
                                                                                            <div class="col-md-12">
                                                                                                <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[<?=$family_member_count?>][]" value="<?=$family_special_date->fields['SPECIAL_DATE']?>">
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="col-5">
                                                                                        <div class="form-group">
                                                                                            <label class="form-label">Date Name</label>
                                                                                            <div class="col-md-12">
                                                                                                <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[<?=$family_member_count?>][]" value="<?=$family_special_date->fields['DATE_NAME']?>">
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="col-2" style="padding-top: 25px;">
                                                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                                    </div>
                                                                                </div>
                                                                                <?php $family_special_date->MoveNext();} ?>
                                                                        <?php } else { ?>
                                                                            <div class="row">
                                                                                <div class="col-5">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Special Date</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[<?=$family_member_count?>][]">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-5">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Date Name</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[<?=$family_member_count?>][]">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-2" style="padding-top: 25px;">
                                                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                                </div>
                                                                            </div>
                                                                        <?php } ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php $family_member_details->MoveNext();
                                                            $family_member_count++; } ?>
                                                    <?php } elseif(empty($_GET['id'])) { ?>
                                                        <div class="rom family_member" style="padding: 35px; margin-top: -60px;">
                                                            <div class="row">
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">First Name<span class="text-danger">*</span></label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="FAMILY_FIRST_NAME[]" class="form-control" placeholder="Enter First Name">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Last Name</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="FAMILY_LAST_NAME[]" class="form-control" placeholder="Enter Last Name">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Relationship</label>
                                                                        <div class="col-md-12">
                                                                            <select class="form-control" name="PK_RELATIONSHIP[]">
                                                                                <option>Select Relationship</option>
                                                                                <?php
                                                                                $row = $db->Execute("SELECT * FROM DOA_RELATIONSHIP WHERE ACTIVE = 1");
                                                                                while (!$row->EOF) { ?>
                                                                                    <option value="<?php echo $row->fields['PK_RELATIONSHIP'];?>"><?=$row->fields['RELATIONSHIP']?></option>
                                                                                    <?php $row->MoveNext(); } ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-2">
                                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="$(this).closest('.row').next().slideToggle();"><i class="ti-arrow-circle-down"></i> More Info</a>
                                                                </div>
                                                                <div class="col-1">
                                                                    <a href="javascript:;" class="btn btn-danger waves-effect waves-light text-white" style="margin-top: 30px;" onclick="removeThisFamilyMember(this);"><b><i class="ti-trash"></i></b></a>
                                                                </div>
                                                            </div>

                                                            <div style="display: none;">
                                                                <div class="row">
                                                                    <div class="col-5">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Phone</label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" name="FAMILY_PHONE[]" class="form-control" placeholder="Enter Phone Number">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-5">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Email</label>
                                                                            <div class="col-md-12">
                                                                                <input type="email" name="FAMILY_EMAIL[]" class="form-control" placeholder="Enter Email Address">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Gender</label>
                                                                            <select class="form-control" name="FAMILY_GENDER[]">
                                                                                <option>Select Gender</option>
                                                                                <option value="Male" <?php if($GENDER == "Male") echo 'selected = "selected"';?>>Male</option>
                                                                                <option value="Female" <?php if($GENDER == "Female") echo 'selected = "selected"';?>>Female</option>
                                                                                <option value="Other" <?php if($GENDER == "Other") echo 'selected = "selected"';?>>Other</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Date of Birth</label>
                                                                            <input type="text" class="form-control datepicker-past" name="FAMILY_DOB[]">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row border-top">
                                                                    <div class="col-2" style="margin-left: 80%">
                                                                        <div class="form-group">
                                                                            <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 15px;" data-counter="<?=$family_member_count?>" onclick="addMoreSpecialDaysFamily(this);"><i class="ti-plus"></i> New</a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="add_more_special_days">
                                                                    <?php
                                                                    $customer_special_date = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_SPECIAL_DATE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                                                                    if($customer_special_date->RecordCount() > 0) {
                                                                        while (!$customer_special_date->EOF) { ?>
                                                                            <div class="row">
                                                                                <div class="col-5">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Special Date</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[<?=$family_member_count?>][]" value="<?=$customer_special_date->fields['SPECIAL_DATE']?>">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-5">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Date Name</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[<?=$family_member_count?>][]" value="<?=$customer_special_date->fields['DATE_NAME']?>">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-2" style="padding-top: 25px;">
                                                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                                </div>
                                                                            </div>
                                                                            <?php $customer_special_date->MoveNext();} ?>
                                                                    <?php } else { ?>
                                                                        <div class="row">
                                                                            <div class="col-5">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Special Date</label>
                                                                                    <div class="col-md-12">
                                                                                        <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[<?=$family_member_count?>][]">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-5">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Date Name</label>
                                                                                    <div class="col-md-12">
                                                                                        <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[<?=$family_member_count?>][]">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-2" style="padding-top: 25px;">
                                                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                            </div>
                                                                        </div>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>

                                                    <div id="add_more_family_member"></div>
                                                    <div class="form-group">
                                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                        <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <div class="tab-pane" id="interest" role="tabpanel">
                                                <form id="interest_form">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveInterestData">
                                                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?=$PK_USER?>">
                                                    <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?=$PK_USER_MASTER?>">
                                                    <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?=$PK_CUSTOMER_DETAILS?>">
                                                    <input type="hidden" class="TYPE" name="TYPE" value="2">
                                                    <div class="p-20">
                                                        <div class="row">
                                                            <div class="col-12 mb-3 pb-3 border-bottom">
                                                                <label class="form-label">Interests</label>
                                                                <div class="col-md-12" style="margin-bottom: 0px;">
                                                                    <div class="row">
                                                                        <?php
                                                                        $PK_USER = empty($_GET['id'])?0:$_GET['id'];
                                                                        $user_interest = $db_account->Execute("SELECT PK_INTERESTS FROM `DOA_CUSTOMER_INTEREST` WHERE `PK_USER_MASTER` = '$PK_USER_MASTER'");
                                                                        $user_interest_array = [];
                                                                        if ($user_interest->RecordCount() > 0){
                                                                            while (!$user_interest->EOF){
                                                                                $user_interest_array[] = $user_interest->fields['PK_INTERESTS'];
                                                                                $user_interest->MoveNext();
                                                                            }
                                                                        }
                                                                        $account_business_type = $db->Execute("SELECT PK_BUSINESS_TYPE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                        $row = $db_account->Execute("SELECT * FROM DOA_INTERESTS WHERE ACTIVE = 1");
                                                                        while (!$row->EOF) { ?>
                                                                            <div class="col-3 mt-3">
                                                                                <label><input type="checkbox" name="PK_INTERESTS[]" value="<?php echo $row->fields['PK_INTERESTS'];?>" <?=(in_array($row->fields['PK_INTERESTS'], $user_interest_array))?'checked':''?> > <?=$row->fields['INTERESTS']?></label>
                                                                            </div>
                                                                            <?php $row->MoveNext(); } ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">What prompted you to inquire with us ?</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" class="form-control" name="WHAT_PROMPTED_YOU_TO_INQUIRE" value="<?=$WHAT_PROMPTED_YOU_TO_INQUIRE?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">How will you grade your present skills ?</label>
                                                                    <div class="col-md-12">
                                                                        <select class="form-control" name="PK_SKILL_LEVEL">
                                                                            <option value="">Select</option>
                                                                            <?php
                                                                            $row = $db_account->Execute("SELECT * FROM DOA_SKILL_LEVEL WHERE ACTIVE = 1");
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_SKILL_LEVEL'];?>" <?=($row->fields['PK_SKILL_LEVEL'] == $PK_SKILL_LEVEL)?'selected':''?>><?=$row->fields['SKILL_LEVEL']?></option>
                                                                                <?php $row->MoveNext(); } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Inquiry Method</label>
                                                                    <div class="col-md-12">
                                                                        <select class="form-control" name="PK_INQUIRY_METHOD">
                                                                            <option value="">Select</option>
                                                                            <?php
                                                                            $row = $db_account->Execute("SELECT * FROM DOA_INQUIRY_METHOD WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_INQUIRY_METHOD'];?>" <?=($row->fields['PK_INQUIRY_METHOD'] == $PK_INQUIRY_METHOD)?'selected':''?>><?=$row->fields['INQUIRY_METHOD']?></option>
                                                                                <?php $row->MoveNext(); } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Inquiry Taker</label>
                                                                    <div class="col-md-12">
                                                                        <select class="form-control" name="INQUIRY_TAKER_ID">
                                                                            <option>Select</option>
                                                                            <?php
                                                                            $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES IN(2,3,5,6,7) AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_USER'];?>" <?=($row->fields['PK_USER'] == $INQUIRY_TAKER_ID)?'selected':''?>><?=$row->fields['NAME']?></option>
                                                                            <?php $row->MoveNext(); } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Inquiry Date</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="INQUIRY_DATE" class="form-control datepicker-normal" value="<?=($INQUIRY_DATE == '' || $INQUIRY_DATE == '0000-00-00')?'':date('m/d/Y', strtotime($INQUIRY_DATE))?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                        <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <div class="tab-pane" id="document" role="tabpanel">
                                                <div class="card-body m-t-10" id="agreement_document">

                                                </div>
                                                <form id="document_form">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveDocumentData">
                                                    <input type="hidden" class="PK_USER" name="PK_USER" value="<?=$PK_USER?>">
                                                    <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?=$PK_USER_MASTER?>">
                                                    <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?=$PK_CUSTOMER_DETAILS?>">
                                                    <input type="hidden" class="TYPE" name="TYPE" value="2">
                                                    <div>
                                                        <div class="card-body" id="append_user_document">
                                                            <?php
                                                            if(!empty($_GET['id'])) { $user_doc_count = 0;
                                                                $row = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_DOCUMENT WHERE PK_USER_MASTER = '$PK_USER_MASTER'");
                                                                while (!$row->EOF) { ?>
                                                                    <div class="row">
                                                                        <div class="col-5">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Document Name</label>
                                                                                <input type="text" name="DOCUMENT_NAME[]" class="form-control" placeholder="Enter Document Name" value="<?=$row->fields['DOCUMENT_NAME']?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-5">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Document File</label>
                                                                                <input type="file" name="FILE_PATH[]" class="form-control">
                                                                                <a target="_blank" href="<?=$row->fields['FILE_PATH']?>">View</a>
                                                                                <input type="hidden" name="FILE_PATH_URL[]" value="<?=$row->fields['FILE_PATH']?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-2">
                                                                            <div class="form-group" style="margin-top: 30px;">
                                                                                <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <?php $row->MoveNext(); $user_doc_count++;} ?>
                                                            <?php } else { $user_doc_count = 1;?>
                                                                <div class="row">
                                                                    <div class="col-5">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Document Name</label>
                                                                            <input type="text" name="DOCUMENT_NAME[]" class="form-control" placeholder="Enter Document Name">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-5">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Document File</label>
                                                                            <input type="file" name="FILE_PATH[]" class="form-control">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-2">
                                                                        <div class="form-group" style="margin-top: 30px;">
                                                                            <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-11">
                                                            <div class="form-group">
                                                                <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addMoreUserDocument();"><i class="ti-plus"></i> New</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                        <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!--Enrollment Model-->
                                            <div class="tab-pane" id="enrollment" role="tabpanel">
                                                <div id="enrollment_list" class="p-20">

                                                </div>
                                            </div>

                                            <div class="tab-pane" id="appointment" role="tabpanel">
                                                <div class="row">
                                                    <div id="posted" class="col-md-3 align-self-center" style="margin-left: 20%">
                                                        <button type="button" class="btn btn-info d-none d-lg-block m-15 text-white" onclick="showAppointment(1, 'posted')"> Show Posted</button>
                                                    </div>
                                                    <div id="unposted" class="col-md-3 align-self-center" style="margin-left: 20%">
                                                        <button type="button" class="btn btn-info d-none d-lg-block m-15 text-white" onclick="showAppointment(1, 'unposted')"> Show Unposted</button>
                                                    </div>
                                                    <!--<div id="canceled" class="col-md-2 align-self-center" style="margin-left: -15%">
                                                        <button type="button" class="btn btn-info d-none d-lg-block m-15 text-white" onclick="showAppointment(1, 'cancelled')"> Show Canceled</button>
                                                    </div>-->
                                                    <div class="col-md-4">
                                                        <a class="btn btn-info d-none d-lg-block m-15 text-white" href="create_appointment.php?id_customer=<?=$_GET['id']?>&master_id_customer=<?=$_GET['master_id']?>&source=customer" style="width: 125px; float: right;"><i class="fa fa-plus-circle"></i> Appointment</a>
                                                    </div>
                                                </div>
                                                <div id="posted_list" style="margin-left: 2%; font-weight: bold;">
                                                    <label>List of Posted Appointments</label>
                                                </div>
                                                <div id="unposted_list" style="margin-left: 2%; font-weight: bold;">
                                                    <label>List of Unposted Appointments</label>
                                                </div>
                                                <div id="canceled_list" style="margin-left: 2%; font-weight: bold;">
                                                    <label>List of Canceled Appointments</label>
                                                </div>
                                                <div id="appointment_list" class="p-20">

                                                </div>
                                            </div>

                                            <div class="tab-pane" id="demo_appointment" role="tabpanel">
                                                <div id="demo_appointment_list" class="p-20">

                                                </div>
                                            </div>

                                            <div class="tab-pane" id="billing" role="tabpanel">
                                                <div id ="billing_list" class="p-20">

                                                </div>
                                            </div>


                                            <!--<div id="paymentModel" class="modal">
                                                <div class="modal-content" style="width: 50%;">
                                                    <span class="close" style="margin-left: 96%;">&times;</span>

                                                    <div class="card" id="payment_confirmation_form_div_customer" style="display: none;">
                                                        <div class="card-body">
                                                            <h4><b>Payment</b></h4>

                                                            <form id="payment_confirmation_form" role="form" action="" method="post">
                                                                <input type="hidden" name="FUNCTION_NAME" value="confirmEnrollmentPayment">
                                                                <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER">
                                                                <input type="hidden" name="PK_ENROLLMENT_BILLING" class="PK_ENROLLMENT_BILLING">
                                                                <input type="hidden" name="PK_ENROLLMENT_LEDGER" class="PK_ENROLLMENT_LEDGER">
                                                                <input type="hidden" name="SECRET_KEY" value="<?php /*=$SECRET_KEY*/?>">
                                                                <input type="hidden" name="PAYMENT_GATEWAY" value="<?php /*=$PAYMENT_GATEWAY*/?>">
                                                                <div class="p-20">
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Customer Name</label>
                                                                                <div class="col-md-12">
                                                                                    <p><?php /*=$FIRST_NAME." ".$LAST_NAME*/?></p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Enrollment Number</label>
                                                                                <div class="col-md-12">
                                                                                    <p id="enrollment_number"></p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Amount</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="AMOUNT" id="AMOUNT_TO_PAY" class="form-control" readonly>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Payment Type</label>
                                                                                <div class="col-md-12">
                                                                                    <select class="form-control" required name="PK_PAYMENT_TYPE" id="PK_PAYMENT_TYPE_CUSTOMER" onchange="selectPaymentTypeCustomer(this)">
                                                                                        <option value="">Select</option>
                                                                                        <?php
/*                                                                                        $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
                                                                                        while (!$row->EOF) { */?>
                                                                                            <option value="<?php /*echo $row->fields['PK_PAYMENT_TYPE'];*/?>"><?php /*=$row->fields['PAYMENT_TYPE']*/?></option>
                                                                                            <?php /*$row->MoveNext(); } */?>
                                                                                    </select>
                                                                                </div>
                                                                                <?php /*$wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1"); */?>
                                                                                <span id="wallet_balance_span" style="font-size: 10px;color: green; display: none;">Wallet Balance : $<?php /*=($wallet_data->RecordCount() > 0)?$wallet_data->fields['CURRENT_BALANCE']:0.00*/?></span>
                                                                                <input type="hidden" id="WALLET_BALANCE" name="WALLET_BALANCE" value="<?php /*=($wallet_data->RecordCount() > 0)?$wallet_data->fields['CURRENT_BALANCE']:0.00*/?>">
                                                                                <input type="hidden" name="PK_USER_MASTER" value="<?php /*=$PK_USER_MASTER*/?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>



                                                                    <div class="row" id="remaining_amount_div" style="display: none;">
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Remaining Amount</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="REMAINING_AMOUNT" id="REMAINING_AMOUNT_CUSTOMER" class="form-control" readonly>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Payment Type</label>
                                                                                <div class="col-md-12">
                                                                                    <select class="form-control" name="PK_PAYMENT_TYPE_REMAINING" id="PK_PAYMENT_TYPE_REMAINING_CUSTOMER" onchange="selectRemainingPaymentType(this)">
                                                                                        <option value="">Select</option>
                                                                                        <?php
/*                                                                                        $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE != 'Wallet' AND ACTIVE = 1");
                                                                                        while (!$row->EOF) { */?>
                                                                                            <option value="<?php /*echo $row->fields['PK_PAYMENT_TYPE'];*/?>"><?php /*=$row->fields['PAYMENT_TYPE']*/?></option>
                                                                                            <?php /*$row->MoveNext(); } */?>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="row remaining_payment_type_div" id="remaining_credit_card_payment" style="display: none;">
                                                                        <div class="col-12">
                                                                            <div class="form-group" id="remaining_card_div">

                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="row remaining_payment_type_div" id="remaining_check_payment" style="display: none;">
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Check Number</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="CHECK_NUMBER_REMAINING" class="form-control">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Check Date</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="CHECK_DATE_REMAINING" class="form-control datepicker-normal">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>


                                                                    <?php /*if ($PAYMENT_GATEWAY == 'Stripe'){ */?>
                                                                        <div class="row payment_type_div" id="credit_card_payment_customer" style="display: none;">
                                                                            <div class="col-12">
                                                                                <div class="form-group" id="customer_card_div">

                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php /*} elseif ($PAYMENT_GATEWAY == 'Square'){*/?>
                                                                        <div class="payment_type_div" id="credit_card_payment_customer" style="display: none;">
                                                                            <div class="row">
                                                                                <div class="col-12">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Name (As it appears on your card)</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" name="NAME" id="NAME" class="form-control" value="<?php /*=$NAME*/?>">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-12">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Card Number</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" name="CARD_NUMBER" id="CARD_NUMBER" class="form-control" value="<?php /*=$CARD_NUMBER*/?>">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-6">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Expiration Date</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" name="EXPIRATION_DATE" id="EXPIRATION_DATE" class="form-control" value="<?php /*=$EXPIRATION_DATE*/?>" placeholder="MM/YYYY">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-6">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Security Code</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" name="SECURITY_CODE" id="SECURITY_CODE" class="form-control" value="<?php /*=$SECURITY_CODE*/?>">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php /*} */?>


                                                                    <div class="row payment_type_div" id="check_payment_customer" style="display: none;">
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Check Number</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="CHECK_NUMBER" class="form-control">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Check Date</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="CHECK_DATE" class="form-control datepicker-normal">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>


                                                                    <div class="row">
                                                                        <div class="col-12">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Notes</label>
                                                                                <div class="col-md-12">
                                                                                    <textarea class="form-control" name="NOTE" rows="3"></textarea>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Process</button>
                                                                    </div>
                                                                </div>
                                                            </form>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>-->

                                            <div class="tab-pane" id="accounts" role="tabpanel">
                                                <a class="btn btn-info d-none d-lg-block m-15 text-white" href="javascript:;" onclick="viewPaymentList();" style="width: 150px; float: right;"><i class="fa fa-plus-circle"></i> Create Payment</a>
                                                <div id="ledger_list" class="p-20">

                                                </div>
                                            </div>

                                            <div class="tab-pane" id="comments" role="tabpanel">
                                                <div class="p-20">
                                                        <a class="btn btn-info d-none d-lg-block m-15 text-white" href="javascript:;" onclick="createUserComment();" style="width: 120px; float: right;"><i class="fa fa-plus-circle"></i> Create New</a>
                                                    <table id="myTable" class="table table-striped border">
                                                        <thead>
                                                        <tr>
                                                            <th>Commented Date</th>
                                                            <th>Commented User</th>
                                                            <th>Comment</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                        </thead>

                                                        <tbody>
                                                            <?php
                                                            $comment_data = $db->Execute("SELECT $account_database.DOA_COMMENT.PK_COMMENT, $account_database.DOA_COMMENT.COMMENT, $account_database.DOA_COMMENT.COMMENT_DATE, $account_database.DOA_COMMENT.ACTIVE, CONCAT($master_database.DOA_USERS.FIRST_NAME, ' ', $master_database.DOA_USERS.LAST_NAME) AS FULL_NAME FROM $account_database.`DOA_COMMENT` INNER JOIN $master_database.DOA_USERS ON $account_database.DOA_COMMENT.BY_PK_USER = $master_database.DOA_USERS.PK_USER WHERE $account_database.DOA_COMMENT.`FOR_PK_USER` = ".$PK_USER);
                                                            $i = 1;
                                                            while (!$comment_data->EOF) { ?>
                                                            <tr>
                                                                <td onclick="editComment(<?=$comment_data->fields['PK_COMMENT']?>);"><?=date('m/d/Y', strtotime($comment_data->fields['COMMENT_DATE']))?></td>
                                                                <td onclick="editComment(<?=$comment_data->fields['PK_COMMENT']?>);"><?=$comment_data->fields['FULL_NAME']?></td>
                                                                <td onclick="editComment(<?=$comment_data->fields['PK_COMMENT']?>);"><?=$comment_data->fields['COMMENT']?></td>
                                                                <td>
                                                                    <a href="javascript:;" onclick="editComment(<?=$comment_data->fields['PK_COMMENT']?>);"><i class="ti-pencil" style="font-size: 22px;"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <a href="javascript:;" onclick='javascript:deleteComment(<?=$comment_data->fields['PK_COMMENT']?>);return false;'><i class="ti-trash" style="font-size: 22px;"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <?php if($comment_data->fields['ACTIVE']==1){ ?>
                                                                        <span class="active-box-green"></span>
                                                                    <?php } else{ ?>
                                                                        <span class="active-box-red"></span>
                                                                    <?php } ?>
                                                                </td>
                                                            </tr>
                                                            <?php $comment_data->MoveNext();
                                                            $i++; } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div class="tab-pane" id="wallet" role="tabpanel">
                                                <div class="p-20">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <?php $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1"); ?>
                                                            <h3 id="wallet_balance_span">Wallet Balance : $<?=($wallet_data->RecordCount() > 0)?$wallet_data->fields['CURRENT_BALANCE']:0.00?></h3>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <a class="btn btn-info d-none d-lg-block text-white" href="javascript:" onclick="openWalletModel();" style="float: right; margin-bottom: 10px;"><i class="fa fa-plus-circle"></i> Add Money to Wallet</a>
                                                        </div>
                                                    </div>

                                                    <table id="myTable" class="table table-striped border">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Transaction Details</th>
                                                                <th>Debit</th>
                                                                <th>Credit</th>
                                                                <th>Balance</th>
                                                                <th></th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                        <?php
                                                        $walletTransaction = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET ASC");
                                                        $i = 1;
                                                        while (!$walletTransaction->EOF) {
                                                            $RECEIPT_NUMBER = $walletTransaction->fields['RECEIPT_NUMBER'];
                                                            $receiptData = $db_account->Execute("SELECT `PK_ENROLLMENT_MASTER`, `PK_ENROLLMENT_LEDGER` FROM `DOA_ENROLLMENT_PAYMENT` WHERE `RECEIPT_NUMBER` = ".$walletTransaction->fields['RECEIPT_NUMBER']." LIMIT 1");
                                                            ?>
                                                            <tr>
                                                                <td><?=date('m/d/Y h:i A', strtotime($walletTransaction->fields['CREATED_ON']))?></td>
                                                                <td><?=$walletTransaction->fields['DESCRIPTION']?></td>
                                                                <td><?=$walletTransaction->fields['DEBIT']?></td>
                                                                <td><?=$walletTransaction->fields['CREDIT']?></td>
                                                                <td><?=$walletTransaction->fields['CURRENT_BALANCE']?></td>
                                                                <?php if($RECEIPT_NUMBER != '') { ?>
                                                                    <td><a class="btn btn-info waves-effect waves-light text-white" href="generate_receipt_pdf.php?master_id=<?=$receiptData->fields['PK_ENROLLMENT_MASTER']?>&ledger_id=<?=$receiptData->fields['PK_ENROLLMENT_LEDGER']?>&receipt=<?=$walletTransaction->fields['RECEIPT_NUMBER']?>" target="_blank">Receipt</a></td>
                                                                <?php } else { ?>
                                                                    <td></td>
                                                                <?php } ?>
                                                            </tr>
                                                            <?php $walletTransaction->MoveNext();
                                                            $i++; } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div class="tab-pane" id="credit_card" role="tabpanel">
                                                <div class="p-20 payment_modal">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h3>Credit Card</h3>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <a class="btn btn-info d-none d-lg-block text-white" href="javascript:" onclick="addCreditCard(this)" style="float: right; margin-bottom: 10px;"><i class="fa fa-plus-circle"></i> Add Credit Card</a>
                                                        </div>
                                                        <?php if ($message != '') { ?>
                                                            <div class="alert alert-success">
                                                                <?=$message?>
                                                            </div>
                                                        <?php } ?>
                                                        <form class="form-material form-horizontal" id="save_credit_card_payment_form" action="" method="post" enctype="multipart/form-data">
                                                            <input type="hidden" name="FUNCTION_NAME" value="saveCreditCard">
                                                            <div class="row credit_card_div" style="display: none;">
                                                                <div class="col-6">
                                                                    <div class="form-group" id="card_div">

                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row credit_card_div" style="display: none;">
                                                                <div class="col-6">
                                                                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Save</button>
                                                                </div>
                                                            </div>
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

                                            <div class="tab-pane" id="delete_customer" role="tabpanel">
                                                <div class="p-20">
                                                    <div class="form-group">
                                                        <button type="button" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="deleteThisCustomer(<?=$PK_USER?>)" style="margin-top: 2%; margin-left: 46%;">Delete This Account</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!--Comment Model-->
                                            <div id="commentModel" class="modal">
                                                <!-- Modal content -->
                                                <div class="modal-content" style="width: 50%;">
                                                    <span class="close close_comment_model" style="margin-left: 96%;">&times;</span>
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <h4><b id="comment_header">Add Comment</b></h4>
                                                            <form id="comment_add_edit_form" role="form" action="" method="post">
                                                                <input type="hidden" name="FUNCTION_NAME" value="saveCommentData">
                                                                <input type="hidden" class="PK_USER" name="PK_USER" value="<?=$PK_USER?>">
                                                                <input type="hidden" name="PK_COMMENT" id="PK_COMMENT" value="0">
                                                                <div class="p-20">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Comments</label>
                                                                        <textarea class="form-control" rows="10" name="COMMENT" id="COMMENT" required></textarea>
                                                                    </div>
                                                                    <div class="form-group" id="comment_active" style="display: none;">
                                                                        <label class="form-label">Active</label>
                                                                        <div>
                                                                            <label><input type="radio" id="COMMENT_ACTIVE_1" name="ACTIVE" value="1">&nbsp;&nbsp;&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                            <label><input type="radio" id="COMMENT_ACTIVE_0" name="ACTIVE" value="0">&nbsp;&nbsp;&nbsp;No</label>
                                                                        </div>
                                                                    </div>

                                                                    <div class="form-group">
                                                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Submit</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!--Refund Model-->
<div class="modal fade" id="refund_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 450px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">How you want your money back?</label>
                            <div class="col-md-12">
                                <select class="form-control" required name="PK_PAYMENT_TYPE_REFUND" id="PK_PAYMENT_TYPE_REFUND" onchange="selectRefundType(this)">
                                    <option value="">Select</option>
                                    <?php
                                    $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_PAYMENT_TYPE'];?>"><?=$row->fields['PAYMENT_TYPE']?></option>
                                    <?php $row->MoveNext(); } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row" id="check_payment" style="display: none;">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Check Number</label>
                                    <div class="col-md-12">
                                        <input type="text" name="REFUND_CHECK_NUMBER" id="REFUND_CHECK_NUMBER" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Check Date</label>
                                    <div class="col-md-12">
                                        <input type="text" name="REFUND_CHECK_DATE" id="REFUND_CHECK_DATE" class="form-control datepicker-normal">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="REFUND_AMOUNT">How much refund you want?</label>
                            <div class="col-md-12">
                                <input class="form-control" name="REFUND_AMOUNT" id="REFUND_AMOUNT" value="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;" onclick="$('.trigger_this').trigger('click');">Process</button>
            </div>
        </div>
    </div>
</div>


<!--Confirm Model-->
<div class="modal fade" id="move_to_wallet_model" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 450px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <h5>Are you sure you want to move $<span id="move_amount">0.00</span> to wallet?</h5>
                            <input type="hidden" id="confirm_move" value="0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-info waves-effect waves-light m-l-20 text-white" onclick="$('#confirm_move').val(1);$('.trigger_this').trigger('click');">Yes</button>
                <button type="button" class="btn btn-danger waves-effect waves-light m-l-10 text-white" onclick="$('#confirm_move').val(0);$('#move_to_wallet_model').modal('hide');">No</button>
            </div>
        </div>
    </div>
</div>


<!--Export Model-->
<div class="modal fade" id="export_model" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 450px; margin-top: 200px;">
        <div class="modal-content">
            <div class="modal-header">
                <h4>How you want to Export?</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <a class="btn btn-info waves-effect waves-light text-white" href="create_csv.php?id_customer=<?=$_GET['id']?>&master_id_customer=<?=$PK_USER_MASTER?>&source=customer&type=active">Only Active</a>
                            <a class="btn btn-info waves-effect waves-light text-white" href="create_csv.php?id_customer=<?=$_GET['id']?>&master_id_customer=<?=$PK_USER_MASTER?>&source=customer&type=completed">Only Complete</a>
                            <a class="btn btn-info waves-effect waves-light text-white" href="create_csv.php?id_customer=<?=$_GET['id']?>&master_id_customer=<?=$PK_USER_MASTER?>&source=customer&type=all">Active & Complete</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php');?>

<!--Wallet Payment Model-->
<?php include('includes/add_money_to_wallet.php'); ?>

<!--Payment Model-->
<?php include('includes/enrollment_payment.php'); ?>

<!--Add Credit Card Model-->
<div class="modal fade payment_modal" id="add_credit_card_modal" tabindex="-1" aria-hidden="true">

</div>

<!--Edit Appointment Model-->
<div class="modal fade" id="edit_appointment_modal" tabindex="-1" aria-hidden="true">

</div>

<!--Verify Password Model-->
<div class="modal fade" id="verify_password_model" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="verify_password_form"  method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><b>Verify Password</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Enter your profile password</label>
                                <input type="password" id="verify_password" name="verify_password" class="form-control" placeholder="Password" required>
                                <p id="verify_password_error" style="color: red;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Process</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!--Edit Billing Due Date Model-->
<div class="modal fade" id="billing_due_date_model" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="edit_due_date_form"  method="post">
            <input type="hidden" name="PK_ENROLLMENT_LEDGER" id="PK_ENROLLMENT_LEDGER">
            <input type="hidden" name="old_due_date" id="old_due_date">
            <input type="hidden" name="edit_type" id="edit_type">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><b>Edit Due Date</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Due Date</label>
                                <input type="text" id="due_date" name="due_date" class="form-control datepicker-normal" placeholder="Due Date" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Enter your profile password</label>
                                <input type="password" id="due_date_verify_password" name="due_date_verify_password" class="form-control" placeholder="Password" required>
                                <p id="due_date_verify_password_error" style="color: red;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Process</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .progress-bar {
        border-radius: 5px;
        height:18px !important;
    }
</style>

    <script>
        let PK_USER = parseInt(<?=empty($_GET['id'])?0:$_GET['id']?>);
        let PK_USER_MASTER = parseInt(<?=empty($_GET['master_id'])?0:$_GET['master_id']?>);

        function createUserComment() {
            $('#comment_header').text("Add Comment");
            $('#PK_COMMENT').val(0);
            $('#COMMENT').val('');
            $('#COMMENT_DATE').val('');
            $('#comment_active').hide();
            openCommentModel();
        }

        function createEnrollment() {
            $('#enrollment_header').text("Add Enrollment");
            openEnrollmentModel();
        }

        function createNewAppointment() {
            $('#appointment_header').text("Add Appointment");
            openAppointmentModel();
        }

        function viewPaymentList() {
            $('#payment_header').text("Add Payment");
            openPaymentListModel();
        }

        function editComment(PK_COMMENT) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                dataType: 'JSON',
                data: {FUNCTION_NAME: 'getEditCommentData', PK_COMMENT: PK_COMMENT},
                success:function (data) {
                    $('#comment_header').text("Edit Comment");
                    $('#PK_COMMENT').val(data.fields.PK_COMMENT);
                    $('#COMMENT').val(data.fields.COMMENT);
                    $('#COMMENT_DATE').val(data.fields.COMMENT_DATE);
                    $('#COMMENT_ACTIVE_'+data.fields.ACTIVE).prop('checked', true);
                    $('#comment_active').show();
                    openCommentModel();
                }
            });
        }

        $(document).on('submit', '#comment_add_edit_form', function (event) {
            event.preventDefault();
            let form_data = new FormData($('#comment_add_edit_form')[0]); //$('#document_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                success:function (data) {
                    window.location.href=`customer.php?id=${PK_USER}&master_id=${PK_USER_MASTER}&on_tab=comments`;
                }
            });
        });

        function deleteComment(PK_COMMENT) {
            let conf = confirm("Are you sure you want to delete?");
            if(conf) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {FUNCTION_NAME: 'deleteCommentData', PK_COMMENT: PK_COMMENT},
                    success: function (data) {
                        window.location.href = `customer.php?id=${PK_USER}&master_id=${PK_USER_MASTER}&on_tab=comments`;
                    }
                });
            }
        }

        $('.multi_sumo_select').SumoSelect({placeholder: 'Select Location', selectAll: true});

        $(document).ready(function() {
            let tab_link = <?=empty($_GET['tab'])?0:$_GET['tab']?>;
            fetch_state(<?php  echo $PK_COUNTRY; ?>);
            if (tab_link.id == 'profile'){
                $('#profile_tab_link')[0].click();
            }
            if (tab_link.id == 'enrollment'){
                $('#enrollment_tab_link')[0].click();
            }
            if (tab_link.id == 'appointment'){
                $('#appointment_tab_link')[0].click();
            }
            if (tab_link.id == 'billing'){
                $('#billing_tab_link')[0].click();
            }
            if (tab_link.id == 'comments'){
                $('#comment_tab_link')[0].click();
            }
            let on_tab_link = <?=empty($_GET['on_tab'])?0:$_GET['on_tab']?>;
            if (on_tab_link.id == 'comments'){
                $('#comment_tab_link')[0].click();
            }
        });

        function fetch_state(PK_COUNTRY){
            jQuery(document).ready(function() {
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

        function selectRefundType(param) {
            let paymentType = parseInt($(param).val());
            if (paymentType === 2) {
                $(param).closest('.modal-body').find('#check_payment').slideDown();
            } else {
                $(param).closest('.modal-body').find('#check_payment').slideUp();
            }
        }
    </script>
    <script>

        function isGood(password) {
            let password_strength = document.getElementById("password-text");

            if (password.length == 0) {
                password_strength.innerHTML = "";
                return;
            }
            //Regular Expressions.
            let regex = new Array();
            regex.push("[A-Z]"); //Uppercase Alphabet.
            regex.push("[a-z]"); //Lowercase Alphabet.
            regex.push("[0-9]"); //Digit.
            regex.push("[$@$!%*#?&]"); //Special Character.
            let passed = 0;
            //Validate for each Regular Expression.
            for (let i = 0; i < regex.length; i++) {
                if (new RegExp(regex[i]).test(password)) {
                    passed++;
                }
            }
            //Display status.
            let strength = "";
            switch (passed) {
                case 0:
                case 1:
                case 2:
                    strength = "<small class='progress-bar bg-danger' style='width: 50%'>Weak</small>";
                    $('#password_note').slideDown();
                    break;
                case 3:
                    strength = "<small class='progress-bar bg-warning' style='width: 60%'>Medium</small>";
                    $('#password_note').slideDown();
                    break;
                case 4:
                    strength = "<small class='progress-bar bg-success' style='width: 100%'>Strong</small>";
                    $('#password_note').slideUp();
                    break;

            }
            // alert(strength);
            password_strength.innerHTML = strength;
        }

        function ValidateUsername() {
            let username = document.getElementById("USER_NAME").value;
            let lblError = document.getElementById("lblError");
            lblError.innerHTML = "";
            let expr = /^[a-zA-Z0-9_]{8,20}$/;
            if (!expr.test(username)) {
                lblError.innerHTML = "Only Alphabets, Numbers and Underscore and between 8 to 20 characters.";
            }
            else{
                lblError.innerHTML = "";
            }
        }

        $(document).on('click', '#cancel_button', function () {
            window.location.href='all_customers.php';
        });

        $(document).on('change', '.engagement_terms', function () {
            if ($(this).is(':checked')){
                $(this).closest('.col-1').next().slideDown();
            }else{
                $(this).closest('.col-1').next().slideUp();
            }
        });

        function createLogin(param) {
            if ($(param).is(':checked')){
                $('#login_info_tab').show();
                $('#phone_label').text('* (Please type your phone number)');
                $('#PHONE').prop('required', true);
                $('#email_label').text('* (Please type your email id)');
                $('#EMAIL_ID').prop('required', true);
                $('#submit_button').hide();
                $('#next_button_interest').hide();
                $('#next_button').show();
            }else {
                $('#login_info_tab').hide();
                $('#phone_label').text('');
                $('#PHONE').prop('required', false);
                $('#email_label').text('');
                $('#EMAIL_ID').prop('required', false);
                $('#submit_button').show();
                $('#next_button_interest').show();
                $('#next_button').hide();
            }
        }

        let counter = parseInt(<?=$user_doc_count?>);
        function addMoreUserDocument() {
            $('#append_user_document').append(`<div class="row">
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <label class="form-label">Document Name</label>
                                                        <input type="text" name="DOCUMENT_NAME[]" class="form-control" placeholder="Enter Document Name">
                                                    </div>
                                                </div>
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <label class="form-label">Document File</label>
                                                        <input type="file" name="FILE_PATH[]" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <div class="form-group" style="margin-top: 30px;">
                                                        <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                              </div>`);
            counter++;
        }

        function removeUserDocument(param) {
            $(param).closest('.row').remove();
            counter--;
        }

        function goLoginInfo() {
            let element = $('#profile').find('input');
            let count = element.length;
            element.each(function(){
                if($(this).prop('required') && ($(this).val() === '')){
                    $(this).focus();
                    return false;
                }
                count--;
                if (count === 0){
                    $('#login_info_tab_link')[0].click();
                }
            });
        }

        function goInterest() {
            let element = $('#profile').find('input');
            let count = element.length;
            element.each(function(){
                if($(this).prop('required') && ($(this).val() === '')){
                    $(this).focus();
                    return false;
                }
                count--;
                if (count === 0){
                    $('#interest_tab_link')[0].click();
                }
            });
        }

        function removeThis(param) {
            $(param).closest('.row').remove();
        }

        function addMorePhone(){
            $('#add_more_phone').append(`<div class="row">
                                            <div class="col-9">
                                                <div class="form-group">
                                                    <label class="form-label">Phone</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="CUSTOMER_PHONE[]" class="form-control" placeholder="Enter Phone Number">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-2" style="padding-top: 25px;">
                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                        </div>`);
        }
        function addMoreEmail(){
            $('#add_more_email').append(`<div class="row">
                                            <div class="col-9">
                                                <div class="form-group">
                                                    <label class="col-md-12">Email</label>
                                                    <div class="col-md-12">
                                                        <input type="email" name="CUSTOMER_EMAIL[]" class="form-control" placeholder="Enter Email Address">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-2" style="padding-top: 25px;">
                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                         </div>`);
        }

        function addMoreSpecialDays(param){
            $(param).closest('.row').next('.add_more_special_days').append(`<div class="row">
                                                    <div class="col-5">
                                                        <div class="form-group">
                                                            <label class="form-label">Special Date</label>
                                                            <div class="col-md-12">
                                                                <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="CUSTOMER_SPECIAL_DATE[]">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-5">
                                                        <div class="form-group">
                                                            <label class="form-label">Date Name</label>
                                                            <div class="col-md-12">
                                                                <input type="text" class="form-control" name="CUSTOMER_SPECIAL_DATE_NAME[]">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-2" style="padding-top: 25px;">
                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>`);
        }

        let family_special_day_count = parseInt(<?=($family_member_count==0)?0:($family_member_count-1)?>);
        function addMoreFamilyMember(){
            family_special_day_count++;
            $('#add_more_family_member').append(`<div class="row family_member" style="padding: 35px; margin-top: -60px;"">
                                                    <div class="row">
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <label class="form-label">First Name<span class="text-danger">*</span></label>
                                                                <div class="col-md-12">
                                                                    <input type="text" name="FAMILY_FIRST_NAME[]" class="form-control" placeholder="Enter First Name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <label class="form-label">Last Name</label>
                                                                <div class="col-md-12">
                                                                    <input type="text" name="FAMILY_LAST_NAME[]" class="form-control" placeholder="Enter Last Name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <label class="form-label">Relationship</label>
                                                                <div class="col-md-12 customer-select">
                                                                    <select class="form-control" name="PK_RELATIONSHIP[]">
                                                                        <option>Select Relationship</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT * FROM DOA_RELATIONSHIP WHERE ACTIVE = 1");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_RELATIONSHIP'];?>"><?=$row->fields['RELATIONSHIP']?></option>
                                                                        <?php $row->MoveNext(); } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="$(this).closest('.row').next().slideToggle();"><i class="ti-arrow-circle-down"></i> More Info</a>
                                                        </div>
                                                        <div class="col-1">
                                                            <a href="javascript:;" class="btn btn-danger waves-effect waves-light text-white" style="margin-top: 30px;" onclick="removeThisFamilyMember(this);"><b><i class="ti-trash"></i></b></a>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <div class="form-group">
                                                                    <label class="form-label">Phone</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="FAMILY_PHONE[]" class="form-control" placeholder="Enter Phone Number">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-5">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">Email</label>
                                                                    <div class="col-md-12">
                                                                        <input type="email" name="FAMILY_EMAIL[]" class="form-control" placeholder="Enter Email Address">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Gender</label>
                                                                    <div class="customer-select">
                                                                    <select class="form-control" name="FAMILY_GENDER[]">
                                                                        <option>Select Gender</option>
                                                                        <option value="Male" <?php if($GENDER == "Male") echo 'selected = "selected"';?>>Male</option>
                                                                        <option value="Female" <?php if($GENDER == "Female") echo 'selected = "selected"';?>>Female</option>
                                                                        <option value="Other" <?php if($GENDER == "Other") echo 'selected = "selected"';?>>Other</option>
                                                                    </select>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Date of Birth</label>
                                                                    <input type="text" class="form-control datepicker-past" name="FAMILY_DOB[]">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-2" style="margin-left: 80%">
                                                                <div class="form-group">
                                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 15px;" data-counter="${family_special_day_count}" onclick="addMoreSpecialDaysFamily(this);"><i class="ti-plus"></i> New</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="add_more_special_days">
                                                            <div class="row">
                                                                <div class="col-5">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Special Date</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[${family_special_day_count}][]">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-5">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Date Name</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[${family_special_day_count}][]">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-2" style="padding-top: 25px;">
                                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>`);
        }


        function addMoreSpecialDaysFamily(param){
            let data_counter = $(param).data('counter');
            $(param).closest('.row').next('.add_more_special_days').append(`<div class="row">
                                                                                <div class="col-5">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Special Date</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" placeholder="mm/dd" class="form-control datepicker-normal" name="FAMILY_SPECIAL_DATE[${data_counter}][]">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-5">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Date Name</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" class="form-control" name="FAMILY_SPECIAL_DATE_NAME[${data_counter}][]">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-2" style="padding-top: 25px;">
                                                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                                </div>
                                                                            </div>`);
        }

        function removeThisFamilyMember(param) {
            family_special_day_count--;
            $(param).closest('.family_member').remove();
        }

        function selectThisPrimaryLocation(param) {
            let primary_location = $(param).val();
            let selected_location = $('#selected_location').val();
            $.ajax({
                url: "ajax/get_all_locations.php",
                type: 'GET',
                data: {primary_location:primary_location, selected_location:selected_location},
                success:function (data) {
                    $('#PK_LOCATION_MULTIPLE').empty().append(data);
                    $('#PK_LOCATION_MULTIPLE')[0].sumo.reload();
                }
            });
        }

        $(document).on('submit', '#profile_form', function (event) {
            event.preventDefault();
            let form_data = new FormData($('#profile_form')[0]); //$('#profile_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                dataType: 'JSON',
                success:function (data) {
                    console.log(data);
                    $('.PK_USER').val(data.PK_USER);
                    $('.PK_USER_MASTER').val(data.PK_USER_MASTER);
                    $('.PK_CUSTOMER_DETAILS').val(data.PK_CUSTOMER_DETAILS);
                    if (PK_USER == 0) {
                        if ($('#CREATE_LOGIN').is(':checked')) {
                            $('#login_info_tab_link')[0].click();
                        } else {
                            $('#family_tab_link')[0].click();
                        }
                    }else{
                        let PK_USER = $('.PK_USER').val();
                        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                        window.location.href='customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
                    }
                }
            });
        });

        $(document).on('submit', '#login_form', function (event) {
            event.preventDefault();
            let PASSWORD = $('#PASSWORD').val();
            let CONFIRM_PASSWORD = $('#CONFIRM_PASSWORD').val();
            if (PASSWORD === CONFIRM_PASSWORD) {
                let SAVED_OLD_PASSWORD = $('#SAVED_OLD_PASSWORD').val();
                let OLD_PASSWORD = $('#OLD_PASSWORD').val();
                if (SAVED_OLD_PASSWORD)
                {
                    $.ajax({
                        url: "ajax/check_old_password.php",
                        type: 'POST',
                        data: {ENTERED_PASSWORD: OLD_PASSWORD, SAVED_PASSWORD: SAVED_OLD_PASSWORD},
                        success: function (data) {
                            if (data == 0){
                                $('#password_error').text('Old Password not matched');
                            }else{
                                let form_data = $('#login_form').serialize();
                                $.ajax({
                                    url: "ajax/AjaxFunctions.php",
                                    type: 'POST',
                                    data: form_data,
                                    success: function (data) {
                                        $('.PK_USER').val(data);
                                        if (PK_USER == 0) {
                                            $('#family_tab_link')[0].click();
                                        } else {
                                            let PK_USER = $('.PK_USER').val();
                                            let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                                            window.location.href='customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
                                        }
                                    }
                                });
                            }
                        }
                    });
                }else {
                    let form_data = $('#login_form').serialize();
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: form_data,
                        success: function (data) {
                            $('.PK_USER').val(data);
                            if (PK_USER == 0) {
                                $('#family_tab_link')[0].click();
                            } else {
                                let PK_USER = $('.PK_USER').val();
                                let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                                window.location.href='customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
                            }
                        }
                    });
                }
            }else{
                $('#password_error').text('Password and Confirm Password not matched');
            }
        });

        $(document).on('submit', '#family_form', function (event) {
            event.preventDefault();
            let form_data = $('#family_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success:function (data) {
                    let PK_USER = $('.PK_USER').val();
                    let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                    window.location.href='customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
                }
            });
        });

        $(document).on('submit', '#interest_form', function (event) {
            event.preventDefault();
            let form_data = $('#interest_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success:function (data) {
                    let PK_USER = $('.PK_USER').val();
                    let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                    window.location.href='customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
                }
            });
        });

        $(document).on('submit', '#document_form', function (event) {
            event.preventDefault();
            let form_data = new FormData($('#document_form')[0]); //$('#document_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                success:function (data) {
                    let PK_USER = $('.PK_USER').val();
                    let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                    window.location.href='customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
                }
            });
        });
    </script>

    <script>
        $('#NAME').SumoSelect({placeholder: 'Select Customer', search: true, searchText: 'Search...'});

        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        $('.datepicker-past').datepicker({
            format: 'mm/dd/yyyy',
            maxDate: 0
        });

        function confirmComplete(param)
        {
            let conf = confirm("Do you want to mark this appointment as completed?");
            if (conf) {
                let PK_APPOINTMENT_MASTER = $(param).data('id');
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {FUNCTION_NAME: 'markAppointmentCompleted', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER},
                    success:function (data) {
                        if (data == 1){
                            $(param).closest('td').html('<span class="status-box" style="background-color: #ff0019">Completed</span>');
                        } else {
                            alert("Something wrong");
                        }
                    }
                });
            }
        }

        function showEnrollmentList(page, type) {
            let PK_USER_MASTER=$('.PK_USER_MASTER').val();
            $.ajax({
                url: "pagination/enrollment.php",
                type: "GET",
                data: {search_text:'', page:page, type:type, pk_user:PK_USER, master_id:PK_USER_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#enrollment_list').html(result);
                }
            });
            window.scrollTo(0,0);
        }

        function showAgreementDocument() {
            let PK_USER_MASTER=$('.PK_USER_MASTER').val();
            $.ajax({
                url: "pagination/agreement_document.php",
                type: "GET",
                data: {master_id:PK_USER_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#agreement_document').html(result);
                }
            });
            window.scrollTo(0,0);
        }

        /*function showCompletedEnrollmentList(page) {
            let PK_USER_MASTER=$('.PK_USER_MASTER').val();
            $.ajax({
                url: "pagination/completed_enrollments.php",
                type: "GET",
                data: {search_text:'', page:page, master_id:PK_USER_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#completed_enrollment_list').html(result)
                }
            });
            window.scrollTo(0,0);
        }*/

        function showAppointment(page, type) {
            let PK_USER_MASTER=$('.PK_USER_MASTER').val();
            $.ajax({
                url: "pagination/appointment.php",
                type: "GET",
                data: {search_text:'', page:page, master_id:PK_USER_MASTER, type:type},
                async: false,
                cache: false,
                success: function (result) {
                    $('#appointment_list').html(result)
                    if (type === 'unposted') {
                        $('#unposted').hide()
                        $('#posted').show();
                        $('#canceled').show();
                        $('#posted_list').hide();
                        $('#unposted_list').show();
                        $('#canceled_list').hide();
                    } else {
                        if (type === 'posted') {
                            $('#posted').hide();
                            $('#unposted').show();
                            $('#canceled').show();
                            $('#unposted_list').hide();
                            $('#posted_list').show();
                            $('#canceled_list').hide();
                        } else {
                            if (type === 'cancelled') {
                                $('#posted').show();
                                $('#unposted').hide();
                                $('#unposted_list').hide();
                                $('#posted_list').hide();
                                $('#canceled_list').show();
                            } else {
                                $('#unposted').hide()
                                $('#posted').show();
                                $('#canceled').show();
                                $('#posted_list').hide();
                                $('#unposted_list').show();
                                $('#canceled_list').hide();
                            }
                        }
                    }
                }
            });
            window.scrollTo(0,0);
        }

        function showDemoAppointment(page) {
            let PK_USER_MASTER=$('.PK_USER_MASTER').val();
            $.ajax({
                url: "pagination/demo_appointment.php",
                type: "GET",
                data: {search_text:'', page:page, master_id:PK_USER_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#demo_appointment_list').html(result)
                }
            });
            window.scrollTo(0,0);
        }

        function showBillingList(page) {
            let PK_USER_MASTER=$('.PK_USER_MASTER').val();
            $.ajax({
                url: "pagination/billing.php",
                type: "GET",
                data: {search_text:'', page:page, master_id:PK_USER_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#billing_list').html(result)
                }
            });
            window.scrollTo(0,0);
        }

        function showLedgerList(page) {
            let PK_USER_MASTER=$('.PK_USER_MASTER').val();
            $.ajax({
                url: "pagination/ledger.php",
                type: "GET",
                data: {search_text:'', page:page, master_id:PK_USER_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#ledger_list').html(result)
                }
            });
            window.scrollTo(0,0);
        }

        function editpage(param){
            var id = $(param).val();
            var master_id = $(param).find(':selected').data('master_id');
            window.location.href = "customer.php?id="+id+"&master_id="+master_id;

        }


    </script>
<script>
    $(document).ready(function () {
        $('#CUSTOMER_ID').on('blur', function () {
            const CUSTOMER_ID = $(this).val().trim();
            let PK_USER = $('.PK_USER').val()
            if (CUSTOMER_ID != '' && PK_USER=='') {
                $.ajax({
                    url: 'ajax/username_checker.php',
                    type: 'post',
                    data: { CUSTOMER_ID: CUSTOMER_ID, PK_USER: PK_USER },
                    success: function (response) {
                        $('#uname_result').html(response);
                        if (response == '') {
                            $('#submit').removeAttr('disabled')
                        } else {
                            $('#submit').attr('disabled', 'disabled')
                        }
                    }
                });
            } else {
                $("#uname_result").html("");
            }
        });
    });
</script>

<script>
    function payNow(PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BILLED_AMOUNT, ENROLLMENT_ID) {
        $('.partial_payment').show();
        $('#PARTIAL_PAYMENT').prop('checked', false);
        $('.partial_payment_div').slideUp();

        $('.PAYMENT_TYPE').val('');
        $('#remaining_amount_div').slideUp();

        $('#enrollment_number').text(ENROLLMENT_ID);
        $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
        $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#ACTUAL_AMOUNT').val(BILLED_AMOUNT);
        $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
        //$('#payment_confirmation_form_div_customer').slideDown();
        //openPaymentModel();
        $('#enrollment_payment_modal').modal('show');
    }

    function paySelected(PK_ENROLLMENT_MASTER, ENROLLMENT_ID) {
        $('.partial_payment').hide();
        $('#PARTIAL_PAYMENT').prop('checked', false);
        $('.partial_payment_div').slideUp();

        $('.PAYMENT_TYPE').val('');
        $('#remaining_amount_div').slideUp();

        let BILLED_AMOUNT = [];
        let PK_ENROLLMENT_LEDGER = [];

        $(".PAYMENT_CHECKBOX_"+PK_ENROLLMENT_MASTER+":checked").each(function() {
            BILLED_AMOUNT.push($(this).data('billed_amount'));
            PK_ENROLLMENT_LEDGER.push($(this).val());
        });

        let TOTAL = BILLED_AMOUNT.reduce(getSum, 0);

        function getSum(total, num) {
            return total + num;
        }

        $('#enrollment_number').text(ENROLLMENT_ID);
        $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
        $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#ACTUAL_AMOUNT').val(parseFloat(TOTAL).toFixed(2));
        $('#AMOUNT_TO_PAY').val(parseFloat(TOTAL).toFixed(2));
        //$('#payment_confirmation_form_div_customer').slideDown();
        //openPaymentModel();
        $('#enrollment_payment_modal').modal('show');
    }
</script>

<script>
    function openWalletModel() {
        $('#wallet_payment_model').modal('show');
    }
</script>

<script>
    function ConfirmPosted(PK_APPOINTMENT_MASTER)
    {
        var conf = confirm("Are you sure you want to Post it?");
        if(conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'updateAppointmentData', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER},
                success: function (data) {
                    window.location.href = 'customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER+'&tab=enrollment';
                }
            });
        }
    }

    function ConfirmUnposted(PK_APPOINTMENT_MASTER)
    {
        var conf = confirm("Are you sure you want to Unpost it?");
        if(conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'updateAppointmentDataUnpost', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER},
                success: function (data) {
                    window.location.href = 'customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER+'&tab=enrollment';
                }
            });
        }
    }

    function addCreditCard(param) {
        $('.credit_card_div').slideDown();
        $(param).closest('.payment_modal').find('#card_div').html(`<div id="card-element"></div><p id="card-errors" role="alert"></p>`);
        stripePaymentFunction('save_credit_card');
    }



    function deleteThisCustomer(PK_USER) {
        Swal.fire({
            title: "Are you sure?",
            text: "Deleting this profile will erase all data related to this person. Even previous numbers, reports, appointments and enrollments.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#verify_password_model').modal('show');
            } else {
                Swal.fire({
                    title: "Cancelled",
                    text: "Your imaginary file is safe :)",
                    icon: "error"
                });
            }
        });
    }

    $('#verify_password_form').on('submit', function (event) {
        event.preventDefault();
        let pk_user = $('.PK_USER').val();
        let password = $('#verify_password').val();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: {FUNCTION_NAME: 'deleteCustomerAfterVerify', pk_user:pk_user, PASSWORD: password},
            success: function (data) {
                $('#verify_password_error').slideUp();
                if (data == 1) {
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your file has been deleted.",
                        icon: "success",
                        timer: 3000,
                    }).then((result) => {
                        window.location.href='all_customers.php';
                    });
                } else {
                    $('#verify_password_error').text("Incorrect Password").slideDown();
                }
            }
        });
    });

    $('#edit_due_date_form').on('submit', function (event) {
        event.preventDefault();

        let PK_ENROLLMENT_LEDGER = $('#PK_ENROLLMENT_LEDGER').val();
        let old_due_date = $('#old_due_date').val();
        let due_date = $('#due_date').val();
        let edit_type = $('#edit_type').val();
        let due_date_verify_password = $('#due_date_verify_password').val();

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: {FUNCTION_NAME: 'updateBillingDueDate', PK_ENROLLMENT_LEDGER:PK_ENROLLMENT_LEDGER, old_due_date:old_due_date, due_date: due_date, edit_type:edit_type, due_date_verify_password:due_date_verify_password},
            success: function (data) {
                $('#due_date_verify_password_error').slideUp();
                if (data == 1) {
                    Swal.fire({
                        title: "Updated!",
                        text: "Due Date is Updated.",
                        icon: "success",
                        timer: 3000,
                    }).then((result) => {
                        $('#billing_due_date_model').modal('hide');
                        showEnrollmentList(1, 'normal');
                    });
                } else {
                    $('#due_date_verify_password_error').text("Incorrect Password").slideDown();
                }
            }
        });
    });
</script>
<!-- JavaScript for Popup -->
<script>
    function showPopup(type, src) {
        let popup = document.getElementById("mediaPopup");
        let image = document.getElementById("popupImage");
        let video = document.getElementById("popupVideo");
        let videoSource = document.getElementById("popupVideoSource");

        if (type === 'image') {
            image.src = src;
            image.style.display = "block";
            video.style.display = "none";
        } else if (type === 'video') {
            videoSource.src = src;
            video.load();
            video.style.display = "block";
            image.style.display = "none";
        }

        popup.style.display = "flex";

        // Add event listener to detect ESC key press
        document.addEventListener("keydown", escClose);
    }

    function closePopup() {
        document.getElementById("mediaPopup").style.display = "none";
        document.removeEventListener("keydown", escClose); // Remove listener when popup is closed
    }

    // Function to detect ESC key press and close the popup
    function escClose(event) {
        if (event.key === "Escape") {
            closePopup();
        }
    }

    // Disable right-click on images and videos
    document.addEventListener("contextmenu", function (event) {
        let target = event.target;
        if (target.tagName === "IMG" || target.tagName === "VIDEO") {
            event.preventDefault(); // Prevent right-click menu
        }
    });

    // Optional: Disable right-click for the whole page
    // Uncomment the line below if you want to block right-click everywhere
    // document.addEventListener("contextmenu", (event) => event.preventDefault());

</script>
<!-- Popup Modal -->
<div id="mediaPopup" class="popup" onclick="closePopup()">
    <span class="close" onclick="closePopup()">&times;</span>
    <div class="popup-content" onclick="event.stopPropagation();">
        <img id="popupImage" src="" style="display:none; max-width: 100%;">
        <video id="popupVideo" controls style="display:none; max-width: 100%;">
            <source id="popupVideoSource" src="" type="video/mp4">
        </video>
    </div>
</div>
</body>
</html>
