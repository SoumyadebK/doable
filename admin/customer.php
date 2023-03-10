<?php
require_once('../global/config.php');
$userType = "Customers";
$user_role_condition = " AND PK_ROLES = 4";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$CREATE_LOGIN = 0;
$user_doc_count = 0;

if (empty($_GET['id']))
    $title = "Add ".$userType;
else
    $title = "Edit ".$userType;

if (!empty($_GET['tab']))
    $title = $userType;

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];


$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");

$PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];
$SECRET_KEY = $account_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];

$ACCESS_TOKEN = $account_data->fields['ACCESS_TOKEN'];
$APP_ID = $account_data->fields['APP_ID'];
$LOCATION_ID = $account_data->fields['LOCATION_ID'];

if(!empty($_POST['PK_PAYMENT_TYPE'])){
    $PK_ENROLLMENT_LEDGER = $_POST['PK_ENROLLMENT_LEDGER'];
    unset($_POST['PK_ENROLLMENT_LEDGER']);
    if(empty($_POST['PK_ENROLLMENT_PAYMENT'])){
        if ($_POST['PK_PAYMENT_TYPE'] == 1) {
            if ($_POST['PAYMENT_GATEWAY'] == 'Stripe') {
                require_once("../global/stripe/init.php");
                \Stripe\Stripe::setApiKey($_POST['SECRET_KEY']);
                $STRIPE_TOKEN = $_POST['token'];
                $AMOUNT = $_POST['AMOUNT'];
                try {
                    $charge = \Stripe\Charge::create([
                        'amount' => ($AMOUNT * 100),
                        'currency' => 'usd',
                        'description' => $_POST['NOTE'],
                        'source' => $STRIPE_TOKEN
                    ]);
                } catch (Exception $e) {

                }
                if($charge->paid == 1){
                    $PAYMENT_INFO = $charge->id;
                }else{
                    $PAYMENT_INFO = 'Payment Unsuccessful.';
                }
            }
        } elseif ($_POST['PK_PAYMENT_TYPE'] == 7) {
            $AMOUNT = $_POST['AMOUNT'];
            $REMAINING_AMOUNT = $_POST['REMAINING_AMOUNT'];
            $WALLET_BALANCE = $_POST['WALLET_BALANCE'];

            if ($_POST['PK_PAYMENT_TYPE_REMAINING'] == 1) {
                require_once("../global/stripe/init.php");
                \Stripe\Stripe::setApiKey($_POST['SECRET_KEY']);
                $STRIPE_TOKEN = $_POST['token'];
                $REMAINING_AMOUNT = $_POST['REMAINING_AMOUNT'];
                try {
                    $charge = \Stripe\Charge::create([
                        'amount' => ($REMAINING_AMOUNT * 100),
                        'currency' => 'usd',
                        'description' => $_POST['NOTE'],
                        'source' => $STRIPE_TOKEN
                    ]);
                } catch (Exception $e) {

                }
                if($charge->paid == 1){
                    $PAYMENT_INFO = $charge->id;
                }else{
                    $PAYMENT_INFO = 'Payment Unsuccessful.';
                }
            }
            $PK_USER_MASTER = $_POST['PK_USER_MASTER'];
            $wallet_data = $db->Execute("SELECT * FROM DOA_USER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_USER_WALLET DESC LIMIT 1");
            $DEBIT_AMOUNT = ($WALLET_BALANCE>$AMOUNT)?$AMOUNT:$WALLET_BALANCE;
            if ($wallet_data->RecordCount() > 0) {
                $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] - $DEBIT_AMOUNT;
            }
            $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
            $INSERT_DATA['DEBIT'] = $DEBIT_AMOUNT;
            $INSERT_DATA['DESCRIPTION'] = "Balance debited for payment of enrollment ".$_POST['PK_ENROLLMENT_MASTER'];
            $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_USER_WALLET', $INSERT_DATA, 'insert');
        } else{
            $PAYMENT_INFO = 'Payment Done.';
        }

        $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
        $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $_POST['PK_ENROLLMENT_BILLING'];
        $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        if ($_POST['PK_PAYMENT_TYPE'] == 7) {
            $PAYMENT_DATA['REMAINING_AMOUNT'] = $_POST['REMAINING_AMOUNT'];
            $PAYMENT_DATA['CHECK_NUMBER'] = $_POST['CHECK_NUMBER_REMAINING'];
            $PAYMENT_DATA['CHECK_DATE'] = date('Y-m-d', strtotime($_POST['CHECK_DATE_REMAINING']));
        } else {
            $PAYMENT_DATA['REMAINING_AMOUNT'] = 0.00;
            $PAYMENT_DATA['CHECK_NUMBER'] = $_POST['CHECK_NUMBER'];
            $PAYMENT_DATA['CHECK_DATE'] = date('Y-m-d', strtotime($_POST['CHECK_DATE']));
        }
        $PAYMENT_DATA['NOTE'] = $_POST['NOTE'];
        $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
        $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
        db_perform('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');

        $enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '$_POST[PK_ENROLLMENT_MASTER]'");
        if ($enrollment_balance->RecordCount() > 0){
            $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_PAID'] = $enrollment_balance->fields['TOTAL_BALANCE_PAID']+$_POST['AMOUNT'];
            $ENROLLMENT_BALANCE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
            $ENROLLMENT_BALANCE_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
        }else{
            $ENROLLMENT_BALANCE_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
            $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_PAID'] = $_POST['AMOUNT'];
            $ENROLLMENT_BALANCE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
            $ENROLLMENT_BALANCE_DATA['CREATED_ON']  = date("Y-m-d H:i");
            db_perform('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'insert');
        }

        $PK_ENROLLMENT_PAYMENT = $db->insert_ID();
        $ledger_record = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_LEDGER` WHERE PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");
        $LEDGER_DATA['TRANSACTION_TYPE'] = 'Payment';
        $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = $PK_ENROLLMENT_LEDGER;
        $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
        $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $_POST['PK_ENROLLMENT_BILLING'];
        $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
        $LEDGER_DATA['BILLED_AMOUNT'] = 0.00;
        $LEDGER_DATA['PAID_AMOUNT'] = $ledger_record->fields['BILLED_AMOUNT'];
        $LEDGER_DATA['BALANCE'] = 0.00;
        $LEDGER_DATA['IS_PAID'] = 1;
        $LEDGER_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        $LEDGER_DATA['PK_ENROLLMENT_PAYMENT'] = $PK_ENROLLMENT_PAYMENT;
        db_perform('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
        $LEDGER_UPDATE_DATA['IS_PAID'] = 1;
        db_perform('DOA_ENROLLMENT_LEDGER', $LEDGER_UPDATE_DATA, 'update', "PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");
    }else{
        db_perform('DOA_ENROLLMENT_PAYMENT', $_POST, 'update'," PK_ENROLLMENT_PAYMENT =  '$_POST[PK_ENROLLMENT_PAYMENT]'");
        $PK_ENROLLMENT_PAYMENT = $_POST['PK_ENROLLMENT_PAYMENT'];
    }

    header('location:customer.php?id='.$_GET['id']);
}



/*if(!empty($_POST)){
    $USER_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $USER_DATA['PK_ROLES'] = $_POST['PK_ROLES'];
    $USER_DATA['FIRST_NAME'] = $_POST['FIRST_NAME'];
    $USER_DATA['LAST_NAME'] = $_POST['LAST_NAME'];
    $USER_DATA['EMAIL_ID'] = $_POST['EMAIL_ID'];
    $USER_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
    $USER_DATA['USER_TITLE'] = $_POST['USER_TITLE'];
    $USER_DATA['CREATE_LOGIN'] = isset($_POST['CREATE_LOGIN'])?1:0;

    if ($USER_DATA['CREATE_LOGIN'] == 1) {
        if (!empty($_POST['PASSWORD'])) {
            $USER_DATA['USER_ID'] = $_POST['USER_ID'];
            $USER_DATA['PASSWORD'] = password_hash($_POST['PASSWORD'], PASSWORD_DEFAULT);
        }
    }

    if($_FILES['USER_IMAGE']['name'] != ''){
        $extn 			= explode(".",$_FILES['USER_IMAGE']['name']);
        $iindex			= count($extn) - 1;
        $rand_string 	= time()."-".rand(100000,999999);
        $file11			= 'user_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
        $extension   	= strtolower($extn[$iindex]);

        if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
            $image_path    = '../uploads/user_image/'.$file11;
            move_uploaded_file($_FILES['USER_IMAGE']['tmp_name'], $image_path);
            $USER_DATA['USER_IMAGE'] = $image_path;
        }
    }

    $USER_PROFILE_DATA['GENDER'] = $_POST['GENDER'];
    $USER_PROFILE_DATA['DOB'] = $_POST['DOB'];
    $USER_PROFILE_DATA['ADDRESS'] = $_POST['ADDRESS'];
    $USER_PROFILE_DATA['ADDRESS_1'] = $_POST['ADDRESS_1'];
    $USER_PROFILE_DATA['PK_COUNTRY'] = $_POST['PK_COUNTRY'];
    $USER_PROFILE_DATA['PK_STATES'] = $_POST['PK_STATES'];
    $USER_PROFILE_DATA['CITY'] = $_POST['CITY'];
    $USER_PROFILE_DATA['ZIP'] = $_POST['ZIP'];
    $USER_PROFILE_DATA['PHONE'] = $_POST['PHONE'];
    $USER_PROFILE_DATA['NOTES'] = $_POST['NOTES'];

    if(empty($_GET['id'])){
        $USER_DATA['ACTIVE'] = 1;
        $USER_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $USER_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_USERS', $USER_DATA, 'insert');
        $PK_USER = $db->insert_ID();
        $USER_PROFILE_DATA['PK_USER'] = $PK_USER;
        $USER_PROFILE_DATA['ACTIVE'] = 1;
        $USER_PROFILE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $USER_PROFILE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'insert');
    }else{
        $USER_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $USER_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $USER_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_USERS', $USER_DATA, 'update'," PK_USER =  '$_GET[id]'");
        $USER_PROFILE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $USER_PROFILE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $USER_PROFILE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'update'," PK_USER =  '$_GET[id]'");
        $PK_USER = $_GET['id'];
    }

    if($type == 2){
        $CUSTOMER_USER_DATA['PK_USER'] = $PK_USER;
        $CUSTOMER_USER_DATA['CALL_PREFERENCE'] = $_POST['CALL_PREFERENCE'];
        $CUSTOMER_USER_DATA['REMINDER_OPTION'] = implode(',', $_POST['REMINDER_OPTION']);
        $CUSTOMER_USER_DATA['SPECIAL_DATE_1'] = $_POST['SPECIAL_DATE_1'];
        $CUSTOMER_USER_DATA['DATE_NAME_1'] = $_POST['DATE_NAME_1'];
        $CUSTOMER_USER_DATA['SPECIAL_DATE_2'] = $_POST['SPECIAL_DATE_2'];
        $CUSTOMER_USER_DATA['DATE_NAME_2'] = $_POST['DATE_NAME_2'];
        $CUSTOMER_USER_DATA['ATTENDING_WITH'] = $_POST['ATTENDING_WITH'];
        $CUSTOMER_USER_DATA['PARTNER_FIRST_NAME'] = $_POST['PARTNER_FIRST_NAME'];
        $CUSTOMER_USER_DATA['PARTNER_LAST_NAME'] = $_POST['PARTNER_LAST_NAME'];
        $CUSTOMER_USER_DATA['PARTNER_GENDER'] = $_POST['PARTNER_GENDER'];
        $CUSTOMER_USER_DATA['PARTNER_DOB'] = $_POST['PARTNER_DOB'];

        $check_customer_data = '';
        if (!empty($_GET['id'])){
            $check_customer_data = $db->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER` = '$_GET[id]'");
        }
        if ($check_customer_data == ''){
            db_perform('DOA_CUSTOMER_DETAILS', $CUSTOMER_USER_DATA, 'insert');
            $PK_CUSTOMER_DETAILS = $db->insert_ID();
        }else{
            db_perform('DOA_CUSTOMER_DETAILS', $CUSTOMER_USER_DATA, 'update'," PK_USER =  '$_GET[id]'");
            $PK_CUSTOMER_DETAILS = $_POST['PK_CUSTOMER_DETAILS'];
        }

        if (isset($_POST['CUSTOMER_PHONE'])){
            $res = $db->Execute("DELETE FROM `DOA_CUSTOMER_PHONE_EMAIL` WHERE `PK_CUSTOMER_DETAILS` = '$PK_CUSTOMER_DETAILS'");
            for($i = 0; $i < count($_POST['CUSTOMER_PHONE']); $i++){
                $CUSTOMER_EMAIL_PHONE['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                $CUSTOMER_EMAIL_PHONE['PHONE'] = $_POST['CUSTOMER_PHONE'][$i];
                $CUSTOMER_EMAIL_PHONE['EMAIL'] = $_POST['CUSTOMER_EMAIL'][$i];
                db_perform('DOA_CUSTOMER_PHONE_EMAIL', $CUSTOMER_EMAIL_PHONE, 'insert');
            }
        }

        if (isset($_POST['CUSTOMER_SPECIAL_DATE'])){
            $res = $db->Execute("DELETE FROM `DOA_SPECIAL_DATE` WHERE `PK_CUSTOMER_DETAILS` = '$PK_CUSTOMER_DETAILS'");
            for($i = 0; $i < count($_POST['CUSTOMER_SPECIAL_DATE']); $i++){
                $CUSTOMER_SPECIAL_DATE['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                $CUSTOMER_SPECIAL_DATE['SPECIAL_DATE'] = $_POST['CUSTOMER_SPECIAL_DATE'][$i];
                $CUSTOMER_SPECIAL_DATE['DATE_NAME'] = $_POST['CUSTOMER_SPECIAL_DATE_NAME'][$i];
                db_perform('DOA_SPECIAL_DATE', $CUSTOMER_SPECIAL_DATE, 'insert');
            }
        }

        if (isset($_POST['DOCUMENT_NAME'])){
            $res = $db->Execute("DELETE FROM `DOA_USER_DOCUMENT` WHERE `PK_USER` = '$PK_USER'");
            for($i = 0; $i < count($_POST['DOCUMENT_NAME']); $i++){
                $USER_DOCUMENT_DATA['PK_USER'] = $PK_USER;
                $USER_DOCUMENT_DATA['DOCUMENT_NAME'] = $_POST['DOCUMENT_NAME'][$i];
                if(!empty($_FILES['FILE_PATH']['name'][$i])){
                    $extn 			= explode(".",$_FILES['FILE_PATH']['name'][$i]);
                    $iindex			= count($extn) - 1;
                    $rand_string 	= time()."-".rand(100000,999999);
                    $file11			= 'user_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
                    $extension   	= strtolower($extn[$iindex]);

                    $image_path    = '../uploads/user_doc/'.$file11;
                    move_uploaded_file($_FILES['FILE_PATH']['tmp_name'][$i], $image_path);
                    $USER_DOCUMENT_DATA['FILE_PATH'] = $image_path;
                } else {
                    $USER_DOCUMENT_DATA['FILE_PATH'] = $_POST['FILE_PATH_URL'][$i];
                }
                db_perform('DOA_USER_DOCUMENT', $USER_DOCUMENT_DATA, 'insert');
            }
        }
        if (isset($_POST['PK_INTERESTS'])){
            $res = $db->Execute("DELETE FROM `DOA_USER_INTEREST` WHERE `PK_USER` = '$PK_USER'");
            for($i = 0; $i < count($_POST['PK_INTERESTS']); $i++){
                $USER_INTEREST_DATA['PK_USER'] = $PK_USER;
                $USER_INTEREST_DATA['PK_INTERESTS'] = $_POST['PK_INTERESTS'][$i];
                db_perform('DOA_USER_INTEREST', $USER_INTEREST_DATA, 'insert');
            }
        }
        if (isset($_POST['WHAT_PROMPTED_YOU_TO_INQUIRE']) || isset($_POST['PK_INQUIRY_METHOD']) || isset($_POST['INQUIRY_TAKER_ID'])){
            $USER_INTEREST_OTHER_DATA['PK_USER'] = $PK_USER;
            $USER_INTEREST_OTHER_DATA['WHAT_PROMPTED_YOU_TO_INQUIRE'
            ] = $_POST['WHAT_PROMPTED_YOU_TO_INQUIRE'];
            $USER_INTEREST_OTHER_DATA['PK_SKILL_LEVEL'] = $_POST['PK_SKILL_LEVEL'];
            $USER_INTEREST_OTHER_DATA['PK_INQUIRY_METHOD'] = $_POST['PK_INQUIRY_METHOD'];
            $USER_INTEREST_OTHER_DATA['INQUIRY_TAKER_ID'] = $_POST['INQUIRY_TAKER_ID'];

            $check_interest_other_data = '';
            if ($_GET['id']){
                $check_interest_other_data = $db->Execute("SELECT * FROM `DOA_USER_INTEREST_OTHER_DATA` WHERE `PK_USER` = '$_GET[id]'");
            }
            if ($check_interest_other_data != '' && $check_interest_other_data->RecordCount() > 0){
                db_perform('DOA_USER_INTEREST_OTHER_DATA', $USER_INTEREST_OTHER_DATA, 'update'," PK_USER =  '$_GET[id]'");
            }else{
                db_perform('DOA_USER_INTEREST_OTHER_DATA', $USER_INTEREST_OTHER_DATA, 'insert');
            }
        }
    }

    if($type == 3 && isset($_POST['PK_USER_LOCATION'])){
        $PK_USER_LOCATION = $_POST['PK_USER_LOCATION'];
        $res = $db->Execute("DELETE FROM `DOA_USER_LOCATION` WHERE `PK_USER` = '$PK_USER'");
        for($i = 0; $i < count($PK_USER_LOCATION); $i++){
            $SERVICE_PROVIDER_LOCATION_DATA['PK_USER'] = $PK_USER;
            $SERVICE_PROVIDER_LOCATION_DATA['PK_LOCATION'] = $PK_USER_LOCATION[$i];
            db_perform('DOA_USER_LOCATION', $SERVICE_PROVIDER_LOCATION_DATA, 'insert');
        }
    }

    if (isset($_POST['PK_RATE_TYPE'])) {
        $USER_RATE_ACTIVE['ACTIVE'] = 0;
        db_perform('DOA_USER_RATE', $USER_RATE_ACTIVE, 'update', " PK_USER = '$PK_USER'");
        $PK_RATE_TYPE = $_POST['PK_RATE_TYPE'];
        $RATE = $_POST['RATE'];
        for ($i = 0; $i < count($RATE); $i++) {
            if (isset($PK_RATE_TYPE[$i])) {
                $USER_RATE_DATA = [];
                $res = $db->Execute("SELECT * FROM `DOA_USER_RATE` WHERE PK_RATE_TYPE = '$PK_RATE_TYPE[$i]' AND PK_USER = '$PK_USER'");
                if ($res->RecordCount() == 0) {
                    $USER_RATE_DATA['PK_USER'] = $PK_USER;
                    $USER_RATE_DATA['PK_RATE_TYPE'] = $PK_RATE_TYPE[$i];
                    $USER_RATE_DATA['RATE'] = $RATE[$i];
                    $USER_RATE_DATA['ACTIVE'] = 1;
                    $USER_RATE_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                    $USER_RATE_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform('DOA_USER_RATE', $USER_RATE_DATA, 'insert');
                } else {
                    $USER_RATE_DATA['RATE'] = $RATE[$i];
                    $USER_RATE_DATA['ACTIVE'] = 1;
                    $USER_RATE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                    $USER_RATE_DATA['EDITED_ON'] = date("Y-m-d H:i");
                    db_perform('DOA_USER_RATE', $USER_RATE_DATA, 'update', " PK_RATE_TYPE = '$PK_RATE_TYPE[$i]' AND PK_USER = '$PK_USER'");
                }
            }
        }
    }
    header("location:all_users.php?type=".$type);
}*/

$PK_USER = '';
$PK_USER_MASTER = '';
$PK_ROLES = '';
$USER_ID = '';
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
$PK_LOCATION = '';
$USER_TITLE = '';
$NOTES = '';
$PASSWORD = '';
$ACTIVE = '';
$WHAT_PROMPTED_YOU_TO_INQUIRE = '';
$PK_SKILL_LEVEL = '';
$PK_INQUIRY_METHOD = '';
$INQUIRY_TAKER_ID = '';
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
$PARTNER_GENDER = '';
$PARTNER_DOB = '';
$INACTIVE_BY_ADMIN = '';
if(!empty($_GET['id'])) {
    $res = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.PK_ROLES, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_ID, DOA_USERS.EMAIL_ID, DOA_USERS.USER_IMAGE, DOA_USERS.ACTIVE, DOA_USERS.INACTIVE_BY_ADMIN, DOA_USERS.PK_LOCATION, DOA_USERS.USER_TITLE, DOA_USERS.CREATE_LOGIN, DOA_USERS.PASSWORD, DOA_USER_PROFILE.GENDER, DOA_USER_PROFILE.DOB, DOA_USER_PROFILE.ADDRESS, DOA_USER_PROFILE.ADDRESS_1, DOA_USER_PROFILE.CITY, DOA_USER_PROFILE.PK_STATES, DOA_USER_PROFILE.ZIP, DOA_USER_PROFILE.PK_COUNTRY, DOA_USERS.PHONE, DOA_USER_PROFILE.FAX, DOA_USER_PROFILE.WEBSITE, DOA_USER_PROFILE.NOTES FROM DOA_USERS LEFT JOIN DOA_USER_PROFILE ON DOA_USERS.PK_USER = DOA_USER_PROFILE.PK_USER WHERE DOA_USERS.PK_USER = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_customers.php");
        exit;
    }
    $PK_USER = $_GET['id'];
    $PK_USER_MASTER = $_GET['master_id'];
    $PK_ROLES = $res->fields['PK_ROLES'];
    $USER_ID = $res->fields['USER_ID'];
    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
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
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $USER_TITLE = $res->fields['USER_TITLE'];
    $NOTES = $res->fields['NOTES'];
    $NOTES = $res->fields['NOTES'];
    $ACTIVE = $res->fields['ACTIVE'];
    $PASSWORD = $res->fields['PASSWORD'];
    $INACTIVE_BY_ADMIN = $res->fields['INACTIVE_BY_ADMIN'];
    $CREATE_LOGIN = $res->fields['CREATE_LOGIN'];

    $user_interest_other_data = $db->Execute("SELECT * FROM `DOA_USER_INTEREST_OTHER_DATA` WHERE `PK_USER_MASTER` = '$_GET[master_id]'");
    if($user_interest_other_data->RecordCount() > 0){
        $WHAT_PROMPTED_YOU_TO_INQUIRE = $user_interest_other_data->fields['WHAT_PROMPTED_YOU_TO_INQUIRE'];
        $PK_SKILL_LEVEL = $user_interest_other_data->fields['PK_SKILL_LEVEL'];
        $PK_INQUIRY_METHOD = $user_interest_other_data->fields['PK_INQUIRY_METHOD'];
        $INQUIRY_TAKER_ID = $user_interest_other_data->fields['INQUIRY_TAKER_ID'];
    }

    $customer_data = $db->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = '$_GET[master_id]'");
    if($customer_data->RecordCount() > 0){
        $PK_CUSTOMER_DETAILS = $customer_data->fields['PK_CUSTOMER_DETAILS'];
        $CALL_PREFERENCE = $customer_data->fields['CALL_PREFERENCE'];
        $REMINDER_OPTION = $customer_data->fields['REMINDER_OPTION'];
        $ATTENDING_WITH = $customer_data->fields['ATTENDING_WITH'];
        $PARTNER_FIRST_NAME = $customer_data->fields['PARTNER_FIRST_NAME'];
        $PARTNER_LAST_NAME = $customer_data->fields['PARTNER_LAST_NAME'];
        $PARTNER_GENDER = $customer_data->fields['PARTNER_GENDER'];
        $PARTNER_DOB = $customer_data->fields['PARTNER_DOB'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><a href="all_customers.php">All Customers</a></li>
                            <li class="breadcrumb-item active"><a href="customer.php"><?=$title?></a></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-title">
                                            <?php
                                            if(!empty($_GET['id'])) {
                                                echo $FIRST_NAME." ".$LAST_NAME;
                                            }
                                            ?>
                                        </div>
                                        <div class="card-body">
                                            <!-- Nav tabs -->
                                            <?php if(!empty($_GET['tab'])) { ?>
                                                <ul class="nav nav-tabs" role="tablist">
                                                    <?php if ($_GET['tab'] == 'profile') { ?>
                                                        <li> <a class="nav-link active" id="profile_tab_link" data-bs-toggle="tab" href="#profile" role="tab" ><span class="hidden-sm-up"><i class="ti-id-badge"></i></span> <span class="hidden-xs-down">Profile</span></a> </li>
                                                    <?php } ?>
                                                    <?php if ($_GET['tab'] == 'appointment') { ?>
                                                        <li> <a class="nav-link" id="appointment_tab_link" data-bs-toggle="tab" href="#appointment" role="tab" ><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">Appointments</span></a> </li>
                                                    <?php } ?>
                                                    <?php if ($_GET['tab'] == 'billing') { ?>
                                                        <li> <a class="nav-link" id="billing_tab_link" data-bs-toggle="tab" href="#billing" role="tab" ><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>
                                                    <?php } ?>
                                                </ul>
                                            <?php } else { ?>
                                                <ul class="nav nav-tabs" role="tablist">
                                                    <li> <a class="nav-link active" data-bs-toggle="tab" href="#profile" role="tab" ><span class="hidden-sm-up"><i class="ti-id-badge"></i></span> <span class="hidden-xs-down">Profile</span></a> </li>
                                                    <li id="login_info_tab" style="display: <?=($CREATE_LOGIN == 1)?'':'none'?>"> <a class="nav-link" id="login_info_tab_link" data-bs-toggle="tab" href="#login" role="tab"><span class="hidden-sm-up"><i class="ti-lock"></i></span> <span class="hidden-xs-down">Login Info</span></a> </li>
                                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#family" id="family_tab_link" role="tab" ><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Family</span></a> </li>
                                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#interest" id="interest_tab_link" role="tab" ><span class="hidden-sm-up"><i class="ti-pencil-alt"></i></span> <span class="hidden-xs-down">Interests</span></a> </li>
                                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#document" id="document_tab_link" role="tab" ><span class="hidden-sm-up"><i class="ti-files"></i></span> <span class="hidden-xs-down">Documents</span></a> </li>
                                                    <?php if(!empty($_GET['id'])) { ?>
                                                        <li> <a class="nav-link" data-bs-toggle="tab" href="#appointment" role="tab" ><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">Appointments</span></a> </li>
                                                        <li> <a class="nav-link" data-bs-toggle="tab" href="#billing" role="tab" ><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>
                                                        <li> <a class="nav-link" data-bs-toggle="tab" href="#accounts" role="tab" ><span class="hidden-sm-up"><i class="ti-book"></i></span> <span class="hidden-xs-down">Accounts</span></a> </li>
                                                        <li> <a class="nav-link" data-bs-toggle="tab" href="#comments" role="tab" ><span class="hidden-sm-up"><i class="ti-comment"></i></span> <span class="hidden-xs-down">Comments</span></a> </li>
                                                    <?php } ?>
                                                </ul>
                                            <?php } ?>
                                            <!-- Tab panes -->
                                            <div class="tab-content tabcontent-border">

                                                <div class="tab-pane active" id="profile" role="tabpanel">
                                                    <form class="form-material form-horizontal" id="profile_form">
                                                        <input type="hidden" name="FUNCTION_NAME" value="saveProfileData">
                                                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?=$PK_USER?>">
                                                        <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?=$PK_USER_MASTER?>">
                                                        <input type="hidden" class="TYPE" name="TYPE" value="2">
                                                        <div class="p-20">
                                                            <div class="row">
                                                                <div class="col-5">
                                                                    <div class="form-group">
                                                                        <label class="form-label">First Name<span class="text-danger">*</span></label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" required value="<?=$FIRST_NAME?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-5">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Last Name</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?=$LAST_NAME?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <?php $row = $db->Execute("SELECT PK_ROLES, ROLES FROM DOA_ROLES WHERE ACTIVE='1' ".$user_role_condition." ORDER BY PK_ROLES"); ?>
                                                                    <input type="hidden" name="PK_ROLES" value="<?php echo $row->fields['PK_ROLES'];?>">
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Phone<span class="text-danger" id="phone_label"><?=($CREATE_LOGIN == 1)?'*':''?></span></label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone Number" value="<?php echo $PHONE?>" <?=($CREATE_LOGIN == 1)?'required':''?>>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-2">
                                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="addMorePhone();"><i class="ti-plus"></i> New</a>
                                                                </div>
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Email<span class="text-danger" id="email_label"><?=($CREATE_LOGIN == 1)?'*':''?></span></label>
                                                                        <div class="col-md-12">
                                                                            <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" value="<?=$EMAIL_ID?>" <?=($CREATE_LOGIN == 1)?'required':''?>>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-2">
                                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="addMoreEmail();"><i class="ti-plus"></i> New</a>
                                                                </div>
                                                                <div class="col-2">
                                                                    <label class="col-md-12 mt-3"><input type="checkbox" id="CREATE_LOGIN" name="CREATE_LOGIN" class="form-check-inline" <?=($CREATE_LOGIN == 1)?'checked':''?> style="margin-top: 30px;" onchange="createLogin(this);"> Create Login</label>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-5" id="add_more_phone">
                                                                    <?php
                                                                    if(!empty($_GET['id'])) {
                                                                        $customer_phone = $db->Execute("SELECT * FROM DOA_CUSTOMER_PHONE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                                                                        while (!$customer_phone->EOF) { ?>
                                                                            <div class="row">
                                                                                <div class="col-9">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Phone</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="text" name="CUSTOMER_PHONE[]" class="form-control" placeholder="Enter Phone Number" value="<?=$customer_phone->fields['PHONE']?>">
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
                                                                        $customer_email = $db->Execute("SELECT * FROM DOA_CUSTOMER_EMAIL WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                                                                        while (!$customer_email->EOF) { ?>
                                                                            <div class="row">
                                                                                <div class="col-9">
                                                                                    <div class="form-group">
                                                                                        <label class="col-md-12">Email</label>
                                                                                        <div class="col-md-12">
                                                                                            <input type="email" name="CUSTOMER_EMAIL[]" class="form-control" placeholder="Enter Email Address" value="<?=$customer_email->fields['EMAIL']?>">
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
                                                                        <div class="col-md-12">
                                                                            <select class="form-control" name="CALL_PREFERENCE">
                                                                                <option>Select</option>
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
                                                                        <select class="form-control" id="GENDER" name="GENDER">
                                                                            <option>Select Gender</option>
                                                                            <option value="1" <?php if($GENDER == "1") echo 'selected = "selected"';?>>Male</option>
                                                                            <option value="2" <?php if($GENDER == "2") echo 'selected = "selected"';?>>Female</option>
                                                                        </select>
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
                                                                        <label class="col-md-12">City</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city" value="<?php echo $CITY?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">Zip Code</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Zip Code" value="<?php echo $ZIP?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <label class="col-md-12">Preferred Location</label>
                                                                    <div class="col-md-12" style="margin-bottom: 15px;">
                                                                        <select class="multi_sumo_select" name="PK_USER_LOCATION[]" id="PK_LOCATION_MULTIPLE" multiple>
                                                                            <?php
                                                                            $selected_location = [];
                                                                            if(!empty($_GET['id'])) {
                                                                                $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = '$_GET[id]'");
                                                                                while (!$selected_location_row->EOF) {
                                                                                    $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                                                                    $selected_location_row->MoveNext();
                                                                                }
                                                                            }
                                                                            $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
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
                                                                $customer_special_date = $db->Execute("SELECT * FROM DOA_SPECIAL_DATE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                                                                if($customer_special_date->RecordCount() > 0) {
                                                                    while (!$customer_special_date->EOF) { ?>
                                                                        <div class="row">
                                                                            <div class="col-5">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Special Date</label>
                                                                                    <div class="col-md-12">
                                                                                        <input type="text" placeholder="mm/dd" class="form-control" name="CUSTOMER_SPECIAL_DATE[]" value="<?=$customer_special_date->fields['SPECIAL_DATE']?>">
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
                                                                                    <input type="text" placeholder="mm/dd" class="form-control" name="CUSTOMER_SPECIAL_DATE[]">
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
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Partner's Gender</label>
                                                                            <select class="form-control" id="PARTNER_GENDER" name="PARTNER_GENDER">
                                                                                <option value="">Select Gender</option>
                                                                                <option value="Male" <?=(($PARTNER_GENDER=='Male')?'selected':'')?>>Male</option>
                                                                                <option value="Female" <?=(($PARTNER_GENDER=='Female')?'selected':'')?>>Female</option>
                                                                            </select>
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
                                                        </div>
                                                        <div class="form-group">
                                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
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
                                                                            <input type="text" id="USER_ID" name="USER_ID" class="form-control" placeholder="Enter User Name" onkeyup="ValidateUsername()" value="<?=$USER_ID?>">
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
                                                                                <input type="password" required class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">Confirm Password</label>
                                                                            <div class="col-md-12">
                                                                                <input type="password" required class="form-control" placeholder="Confirm Password" aria-label="Password" aria-describedby="basic-addon3" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <b id="password_error" style="color: red;"></b>
                                                                <div class="row">
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
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Old Password</label>
                                                                                <input type="hidden" name="SAVED_OLD_PASSWORD" id="SAVED_OLD_PASSWORD" value="<?=$PASSWORD?>">
                                                                                <input type="password" required name="OLD_PASSWORD" id="OLD_PASSWORD" class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <label class="form-label">New Password</label>
                                                                                <input type="password" required name="PASSWORD" class="form-control" id="PASSWORD">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Confirm New Password</label>
                                                                                <input type="password" required name="CONFIRM_PASSWORD" class="form-control" id="CONFIRM_PASSWORD">
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
                                                                        <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                        <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                                    </div>
                                                                </div>
                                                            <? } ?>
                                                        </div>
                                                        <div class="form-group">
                                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                            <a class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="$('#change_password_div').slideToggle();">Change Password</a>
                                                            <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>

                                                <?php $family_member_count = 0;?>
                                                <div class="tab-pane" id="family" role="tabpanel">
                                                    <form id="family_form">
                                                        <input type="hidden" name="FUNCTION_NAME" value="saveFamilyData">
                                                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?=$PK_USER?>">
                                                        <input type="hidden" class="PK_USER_MASTER" name="PK_USER_MASTER" value="<?=$PK_USER_MASTER?>">
                                                        <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?=$PK_CUSTOMER_DETAILS?>">
                                                        <input type="hidden" class="TYPE" name="TYPE" value="2">
                                                        <div class="row" style="margin-bottom: 25px;">
                                                            <a href="javascript:;" style="float: right; margin-left: 91%; margin-top: 10px; color: green;" onclick="addMoreFamilyMember();"><b><i class="ti-plus"></i> New</b></a>
                                                        </div>
                                                        <?php
                                                        $family_member_details = $db->Execute("SELECT * FROM DOA_CUSTOMER_DETAILS WHERE PK_CUSTOMER_PRIMARY = '$PK_CUSTOMER_DETAILS' AND IS_PRIMARY = 0");
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
                                                                                <div class="col-md-12">
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
                                                                                    <select class="form-control" name="FAMILY_GENDER[]">
                                                                                        <option>Select Gender</option>
                                                                                        <option value="Male" <?php if($family_member_details->fields['GENDER'] == "Male") echo 'selected = "selected"';?>>Male</option>
                                                                                        <option value="Female" <?php if($family_member_details->fields['GENDER'] == "Female") echo 'selected = "selected"';?>>Female</option>
                                                                                    </select>
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
                                                                            $family_special_date = $db->Execute("SELECT * FROM DOA_SPECIAL_DATE WHERE PK_CUSTOMER_DETAILS = ".$family_member_details->fields['PK_CUSTOMER_DETAILS']);
                                                                            if($family_special_date->RecordCount() > 0) {
                                                                                while (!$family_special_date->EOF) { ?>
                                                                                    <div class="row">
                                                                                        <div class="col-5">
                                                                                            <div class="form-group">
                                                                                                <label class="form-label">Special Date</label>
                                                                                                <div class="col-md-12">
                                                                                                    <input type="text" placeholder="mm/dd" class="form-control" name="FAMILY_SPECIAL_DATE[<?=$family_member_count?>][]" value="<?=$family_special_date->fields['SPECIAL_DATE']?>">
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
                                                                                                <input type="text" placeholder="mm/dd" class="form-control" name="FAMILY_SPECIAL_DATE[<?=$family_member_count?>][]">
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
                                                                                    <option value="1" <?php if($GENDER == "1") echo 'selected = "selected"';?>>Male</option>
                                                                                    <option value="2" <?php if($GENDER == "2") echo 'selected = "selected"';?>>Female</option>
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
                                                                        $customer_special_date = $db->Execute("SELECT * FROM DOA_SPECIAL_DATE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                                                                        if($customer_special_date->RecordCount() > 0) {
                                                                            while (!$customer_special_date->EOF) { ?>
                                                                                <div class="row">
                                                                                    <div class="col-5">
                                                                                        <div class="form-group">
                                                                                            <label class="form-label">Special Date</label>
                                                                                            <div class="col-md-12">
                                                                                                <input type="text" placeholder="mm/dd" class="form-control" name="FAMILY_SPECIAL_DATE[<?=$family_member_count?>][]" value="<?=$customer_special_date->fields['SPECIAL_DATE']?>">
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
                                                                                            <input type="text" placeholder="mm/dd" class="form-control" name="FAMILY_SPECIAL_DATE[<?=$family_member_count?>][]">
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
                                                                            $user_interest = $db->Execute("SELECT PK_INTERESTS FROM `DOA_USER_INTEREST` WHERE `PK_USER_MASTER` = '$PK_USER_MASTER'");
                                                                            $user_interest_array = [];
                                                                            if ($user_interest->RecordCount() > 0){
                                                                                while (!$user_interest->EOF){
                                                                                    $user_interest_array[] = $user_interest->fields['PK_INTERESTS'];
                                                                                    $user_interest->MoveNext();
                                                                                }
                                                                            }
                                                                            $account_business_type = $db->Execute("SELECT PK_BUSINESS_TYPE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                            $row = $db->Execute("SELECT * FROM DOA_INTERESTS WHERE ACTIVE = 1 AND PK_BUSINESS_TYPE = ".$account_business_type->fields['PK_BUSINESS_TYPE']);
                                                                            while (!$row->EOF) { ?>
                                                                                <div class="col-3 mt-3">
                                                                                    <label><input type="checkbox" name="PK_INTERESTS[]" value="<?php echo $row->fields['PK_INTERESTS'];?>" <?=(in_array($row->fields['PK_INTERESTS'], $user_interest_array))?'checked':''?>> <?=$row->fields['INTERESTS']?></label>
                                                                                </div>
                                                                                <?php $row->MoveNext(); } ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label">What promoted you to inquire with us ?</label>
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
                                                                                $row = $db->Execute("SELECT * FROM DOA_SKILL_LEVEL WHERE ACTIVE = 1");
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
                                                                                $row = $db->Execute("SELECT * FROM DOA_INQUIRY_METHOD WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
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
                                                                                $row = $db->Execute("SELECT PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS WHERE PK_ROLES IN(2,3,5) AND PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                                                                while (!$row->EOF) { ?>
                                                                                    <option value="<?php echo $row->fields['PK_USER'];?>" <?=($row->fields['PK_USER'] == $INQUIRY_TAKER_ID)?'selected':''?>><?=$row->fields['NAME']?></option>
                                                                                    <?php $row->MoveNext(); } ?>
                                                                            </select>
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
                                                                    $row = $db->Execute("SELECT * FROM DOA_USER_DOCUMENT WHERE PK_USER_MASTER = '$PK_USER_MASTER'");
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

                                                <div class="tab-pane" id="appointment" role="tabpanel">
                                                    <div class="p-20">
                                                        <table id="myTable" class="table table-striped border">
                                                            <thead>
                                                            <tr>
                                                                <th>No</th>
                                                                <th>Customer</th>
                                                                <th>Enrollment - Serial</th>
                                                                <th>Service</th>
                                                                <th>Service Code</th>
                                                                <th>Service Provider</th>
                                                                <th>Date</th>
                                                                <th>Time</th>
                                                                <th>Status</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                            </thead>

                                                            <tbody>
                                                            <?php
                                                            $i=1;
                                                            $appointment_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_APPOINTMENT_MASTER.SERIAL_NUMBER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER LEFT JOIN DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS  WHERE DOA_APPOINTMENT_MASTER.CUSTOMER_ID = '$_GET[master_id]' ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC");
                                                            while (!$appointment_data->EOF) { ?>
                                                                <tr>
                                                                    <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$i;?></td>
                                                                    <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['CUSTOMER_NAME']?></td>
                                                                    <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['ENROLLMENT_ID']." - ".$appointment_data->fields['SERIAL_NUMBER']?></td>
                                                                    <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_NAME']?></td>
                                                                    <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_CODE']?></td>
                                                                    <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=$appointment_data->fields['SERVICE_PROVIDER_NAME']?></td>
                                                                    <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
                                                                    <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
                                                                    <td onclick="editpage(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);"><span class="status-box" style="background-color: <?=$appointment_data->fields['COLOR_CODE']?>"><?=$appointment_data->fields['APPOINTMENT_STATUS']?></span></td>
                                                                    <td style="text-align: center;">
                                                                        <a href="add_schedule.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    </td>
                                                                </tr>
                                                                <?php $appointment_data->MoveNext();
                                                                $i++; } ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                <div class="tab-pane" id="billing" role="tabpanel">
                                                    <div class="p-20">
                                                        <?php
                                                        $i=1;
                                                        $row = $db->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_LOCATION.LOCATION_NAME FROM `DOA_ENROLLMENT_MASTER` INNER JOIN DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER INNER JOIN DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER INNER JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION  WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER = '$_GET[master_id]' ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC");
                                                        while (!$row->EOF) {
                                                            $total_bill_and_paid = $db->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
                                                            $enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
                                                            ?>
                                                            <div class="row" onclick="$(this).next().slideToggle();" style="cursor:pointer; font-size: 15px; *border: 1px solid #ebe5e2; padding: 8px;">
                                                                <div class="col-3"><span class="hidden-sm-up" style="margin-right: 20px;"><i class="ti-arrow-circle-right"></i></span></i> <?=$row->fields['ENROLLMENT_ID']?></div>
                                                                <div class="col-3">Total Billed : <?=$total_bill_and_paid->fields['TOTAL_BILL'];?></div>
                                                                <div class="col-3">Total Paid : <?=$total_bill_and_paid->fields['TOTAL_PAID'];?></div>
                                                                <div class="col-3">Balance : <?=$total_bill_and_paid->fields['TOTAL_BILL']-$total_bill_and_paid->fields['TOTAL_PAID'];?></div>
                                                            </div>
                                                            <table id="myTable" class="table table-striped border" style="display: none">
                                                                <thead>
                                                                <tr>
                                                                    <th>Due Date</th>
                                                                    <th>Transaction Type</th>
                                                                    <th>Billed Amount</th>
                                                                    <th>Paid Amount</th>
                                                                    <th>Payment Type</th>
                                                                    <th>Description</th>
                                                                    <th>Paid</th>
                                                                    <th>Balance</th>
                                                                    <th>Actions</th>
                                                                </tr>
                                                                </thead>

                                                                <tbody>
                                                                <?php
                                                                $billed_amount = 0;
                                                                $paid_amount = 0;
                                                                $balance = 0;
                                                                $billing_details = $db->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM `DOA_ENROLLMENT_LEDGER` LEFT JOIN DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_LEDGER.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']." AND ENROLLMENT_LEDGER_PARENT = 0 ORDER BY DUE_DATE ASC, PK_ENROLLMENT_LEDGER ASC");
                                                                while (!$billing_details->EOF) { $billed_amount = $billing_details->fields['BILLED_AMOUNT']; $balance = ($billing_details->fields['BILLED_AMOUNT'] + $balance); ?>
                                                                    <tr>
                                                                        <td><?=date('m/d/Y', strtotime($billing_details->fields['DUE_DATE']))?></td>
                                                                        <td><?=$billing_details->fields['TRANSACTION_TYPE']?></td>
                                                                        <td><?=$billing_details->fields['BILLED_AMOUNT']?></td>
                                                                        <td></td>
                                                                        <td><?=$billing_details->fields['PAYMENT_TYPE']?></td>
                                                                        <td></td>
                                                                        <td><?=(($billing_details->fields['TRANSACTION_TYPE']=='Billing')?(($billing_details->fields['IS_PAID']==1)?'YES':'NO'):'')?></td>
                                                                        <td><?=number_format((float)$balance, 2, '.', '')?></td>
                                                                        <td>
                                                                            <?php if($billing_details->fields['IS_PAID']==0 && $billing_details->fields['STATUS']=='A') { ?>
                                                                                <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="payNow(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, <?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>, <?=$billing_details->fields['BILLED_AMOUNT']?>, '<?=$row->fields['ENROLLMENT_ID']?>');">Pay Now</a>
                                                                            <?php } ?>
                                                                        </td>
                                                                    </tr>
                                                                    <?php
                                                                    $payment_details = $db->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM `DOA_ENROLLMENT_LEDGER` LEFT JOIN DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_LEDGER.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE ENROLLMENT_LEDGER_PARENT = ".$billing_details->fields['PK_ENROLLMENT_LEDGER']);
                                                                    if ($payment_details->RecordCount() > 0){ $balance = ($billed_amount - $payment_details->fields['PAID_AMOUNT']); ?>
                                                                        <tr>
                                                                            <td><?=date('m/d/Y', strtotime($payment_details->fields['DUE_DATE']))?></td>
                                                                            <td><?=$payment_details->fields['TRANSACTION_TYPE']?></td>
                                                                            <td></td>
                                                                            <td><?=$payment_details->fields['PAID_AMOUNT']?></td>
                                                                            <td><?=$payment_details->fields['PAYMENT_TYPE']?></td>
                                                                            <td></td>
                                                                            <td><?=(($payment_details->fields['TRANSACTION_TYPE']=='Billing')?(($payment_details->fields['IS_PAID']==1)?'YES':'NO'):'')?></td>
                                                                            <td><?=number_format((float)$balance, 2, '.', '')?></td>
                                                                            <td>
                                                                            </td>
                                                                        </tr>
                                                                    <? } ?>
                                                                    <?php $billing_details->MoveNext(); } ?>
                                                                </tbody>
                                                            </table>
                                                            <?php $row->MoveNext();
                                                            $i++; } ?>
                                                    </div>
                                                </div>


                                                <!--Payment Model-->
                                                <div id="myModal" class="modal">
                                                    <!-- Modal content -->
                                                    <div class="modal-content" style="width: 50%;">
                                                        <span class="close" style="margin-left: 96%;">&times;</span>

                                                        <div class="card" id="payment_confirmation_form_div" style="display: none;">
                                                            <div class="card-body">
                                                                <h4><b>Payment</b></h4>

                                                                <form id="payment_confirmation_form" role="form" action="" method="post">
                                                                    <input type="hidden" name="FUNCTION_NAME" value="confirmEnrollmentPayment">
                                                                    <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER">
                                                                    <input type="hidden" name="PK_ENROLLMENT_BILLING" class="PK_ENROLLMENT_BILLING">
                                                                    <input type="hidden" name="PK_ENROLLMENT_LEDGER" class="PK_ENROLLMENT_LEDGER">
                                                                    <input type="hidden" name="SECRET_KEY" value="<?=$SECRET_KEY?>">
                                                                    <input type="hidden" name="PAYMENT_GATEWAY" value="<?=$PAYMENT_GATEWAY?>">
                                                                    <div class="p-20">
                                                                        <div class="row">
                                                                            <div class="col-6">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Customer Name</label>
                                                                                    <div class="col-md-12">
                                                                                        <p><?=$FIRST_NAME." ".$LAST_NAME?></p>
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
                                                                                        <select class="form-control" required name="PK_PAYMENT_TYPE" id="PK_PAYMENT_TYPE" onchange="selectPaymentType(this)">
                                                                                            <option value="">Select</option>
                                                                                            <?php
                                                                                            $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
                                                                                            while (!$row->EOF) { ?>
                                                                                                <option value="<?php echo $row->fields['PK_PAYMENT_TYPE'];?>"><?=$row->fields['PAYMENT_TYPE']?></option>
                                                                                                <?php $row->MoveNext(); } ?>
                                                                                        </select>
                                                                                    </div>
                                                                                    <?php $wallet_data = $db->Execute("SELECT * FROM DOA_USER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_USER_WALLET DESC LIMIT 1"); ?>
                                                                                    <span id="wallet_balance_span" style="font-size: 10px;color: green; display: none;">Wallet Balance : $<?=($wallet_data->RecordCount() > 0)?$wallet_data->fields['CURRENT_BALANCE']:0.00?></span>
                                                                                    <input type="hidden" id="WALLET_BALANCE" name="WALLET_BALANCE" value="<?=($wallet_data->RecordCount() > 0)?$wallet_data->fields['CURRENT_BALANCE']:0.00?>">
                                                                                    <input type="hidden" name="PK_USER_MASTER" value="<?=$PK_USER_MASTER?>">
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="row" id="remaining_amount_div" style="display: none;">
                                                                            <div class="col-6">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Remaining Amount</label>
                                                                                    <div class="col-md-12">
                                                                                        <input type="text" name="REMAINING_AMOUNT" id="REMAINING_AMOUNT" class="form-control" readonly>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Payment Type</label>
                                                                                    <div class="col-md-12">
                                                                                        <select class="form-control" name="PK_PAYMENT_TYPE_REMAINING" id="PK_PAYMENT_TYPE_REMAINING" onchange="selectRemainingPaymentType(this)">
                                                                                            <option value="">Select</option>
                                                                                            <?php
                                                                                            $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE != 'Wallet' AND ACTIVE = 1");
                                                                                            while (!$row->EOF) { ?>
                                                                                                <option value="<?php echo $row->fields['PK_PAYMENT_TYPE'];?>"><?=$row->fields['PAYMENT_TYPE']?></option>
                                                                                                <?php $row->MoveNext(); } ?>
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


                                                                        <?php if ($PAYMENT_GATEWAY == 'Stripe'){ ?>
                                                                            <div class="row payment_type_div" id="credit_card_payment" style="display: none;">
                                                                                <div class="col-12">
                                                                                    <div class="form-group" id="card_div">

                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        <?php } elseif ($PAYMENT_GATEWAY == 'Square'){?>
                                                                            <div class="payment_type_div" id="credit_card_payment" style="display: none;">
                                                                                <div class="row">
                                                                                    <div class="col-12">
                                                                                        <div class="form-group">
                                                                                            <label class="form-label">Name (As it appears on your card)</label>
                                                                                            <div class="col-md-12">
                                                                                                <input type="text" name="NAME" id="NAME" class="form-control" value="<?=$NAME?>">
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="row">
                                                                                    <div class="col-12">
                                                                                        <div class="form-group">
                                                                                            <label class="form-label">Card Number</label>
                                                                                            <div class="col-md-12">
                                                                                                <input type="text" name="CARD_NUMBER" id="CARD_NUMBER" class="form-control" value="<?=$CARD_NUMBER?>">
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="row">
                                                                                    <div class="col-6">
                                                                                        <div class="form-group">
                                                                                            <label class="form-label">Expiration Date</label>
                                                                                            <div class="col-md-12">
                                                                                                <input type="text" name="EXPIRATION_DATE" id="EXPIRATION_DATE" class="form-control" value="<?=$EXPIRATION_DATE?>" placeholder="MM/YYYY">
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="col-6">
                                                                                        <div class="form-group">
                                                                                            <label class="form-label">Security Code</label>
                                                                                            <div class="col-md-12">
                                                                                                <input type="text" name="SECURITY_CODE" id="SECURITY_CODE" class="form-control" value="<?=$SECURITY_CODE?>">
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        <?php } ?>


                                                                        <div class="row payment_type_div" id="check_payment" style="display: none;">
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
                                                </div>

                                                <div class="tab-pane" id="accounts" role="tabpanel">
                                                    <div class="p-20">
                                                        <?php $wallet_data = $db->Execute("SELECT * FROM DOA_USER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_USER_WALLET DESC LIMIT 1"); ?>
                                                        <h3 class="m-20">Wallet Balance : $<?=($wallet_data->RecordCount() > 0)?$wallet_data->fields['CURRENT_BALANCE']:0.00?></h3>
                                                        <?php
                                                        $i=1;
                                                        $row = $db->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE FROM `DOA_ENROLLMENT_MASTER`   WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]' ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC");
                                                        while (!$row->EOF) {
                                                            $total_bill_and_paid = $db->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
                                                            $enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
                                                            $total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
                                                            $service_credit = ($enrollment_balance->RecordCount() > 0)?($total_bill_and_paid->fields['TOTAL_PAID']-$enrollment_balance->fields['TOTAL_BALANCE_USED']):'0.00';
                                                            ?>
                                                            <div class="row" onclick="$(this).next().slideToggle()" style="cursor:pointer; font-size: 15px; *border: 1px solid #ebe5e2; padding: 8px;">
                                                                <div class="col-3"><span class="hidden-sm-up" style="margin-right: 20px;"><i class="ti-arrow-circle-right"></i></span></i> <?=$row->fields['ENROLLMENT_ID']?></div>
                                                                <div class="col-3">Paid : <?=$total_bill_and_paid->fields['TOTAL_PAID'];?></div>
                                                                <div class="col-3">Used : <?=($enrollment_balance->RecordCount() > 0)?$enrollment_balance->fields['TOTAL_BALANCE_USED']:'0.00';?></div>
                                                                <div class="col-3" style="color:<?=($service_credit<0)?'red':'black'?>;">Service Credit : <?=$service_credit?></div>
                                                            </div>
                                                            <table id="myTable" class="table table-striped border" style="display: none">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Service</th>
                                                                        <th>Apt #</th>
                                                                        <th>Service Code</th>
                                                                        <th>Date</th>
                                                                        <th>Time</th>
                                                                        <th>Session Cost</th>
                                                                        <th>Service Credit</th>
                                                                    </tr>
                                                                </thead>

                                                                <tbody>
                                                                <?php
                                                                $appointment_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.SERIAL_NUMBER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_CODE.PRICE AS SESSION_COST, DOA_APPOINTMENT_MASTER.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS  WHERE DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']." AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 2");
                                                                $total_session_cost = 0;
                                                                while (!$appointment_data->EOF) {
                                                                    $total_session_cost += $appointment_data->fields['SESSION_COST']?>
                                                                    <tr>
                                                                        <td><?=$appointment_data->fields['SERVICE_NAME']?></td>
                                                                        <td><?=$appointment_data->fields['SERIAL_NUMBER']?></td>
                                                                        <td><?=$appointment_data->fields['SERVICE_CODE']?></td>
                                                                        <td><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
                                                                        <td><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
                                                                        <td><?=$appointment_data->fields['SESSION_COST']?></td>
                                                                        <td style="color:<?=(($total_paid-$total_session_cost)<0)?'red':'black'?>;"><?=$total_paid-$total_session_cost?></td>
                                                                    </tr>
                                                                    <?php $appointment_data->MoveNext();
                                                                    } ?>
                                                                </tbody>
                                                            </table>
                                                            <?php $row->MoveNext();
                                                            $i++; } ?>
                                                    </div>
                                                </div>

                                                <div class="tab-pane" id="comments" role="tabpanel">
                                                    <div class="p-20">
                                                        <h3>Comments Tab Coming Soon</h3>
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

    <style>

        .progress-bar {
            border-radius: 5px;
            height:18px !important;
        }
    </style>
    <?php require_once('../includes/footer.php');?>
    <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

    <script>
        // Get the modal
        var modal = document.getElementById("myModal");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks the button, open the modal
        function openModel() {
            modal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

    <script>
        $('.multi_sumo_select').SumoSelect({placeholder: 'Select Location', selectAll: true});

        $(document).ready(function() {
            let tab_link = <?=empty($_GET['tab'])?0:$_GET['tab']?>;
            fetch_state(<?php  echo $PK_COUNTRY; ?>);
            if (tab_link.id == 'profile'){
                $('#profile_tab_link')[0].click();
            }
            if (tab_link.id == 'appointment'){
                $('#appointment_tab_link')[0].click();
            }
            if (tab_link.id == 'billing'){
                $('#billing_tab_link')[0].click();
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
    </script>
    <script>
        let PK_USER = parseInt(<?=empty($_GET['id'])?0:$_GET['id']?>);

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
            let username = document.getElementById("User_Id").value;
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
                $('#phone_label').text('*');
                $('#PHONE').prop('required', true);
                $('#email_label').text('*');
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
                                                                <input type="text" placeholder="mm/dd" class="form-control" name="CUSTOMER_SPECIAL_DATE[]">
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
                                                                    <select class="form-control" name="FAMILY_GENDER[]">
                                                                        <option>Select Gender</option>
                                                                        <option value="1" <?php if($GENDER == "1") echo 'selected = "selected"';?>>Male</option>
                                                                        <option value="2" <?php if($GENDER == "2") echo 'selected = "selected"';?>>Female</option>
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
                                                                            <input type="text" placeholder="mm/dd" class="form-control" name="FAMILY_SPECIAL_DATE[${family_special_day_count}][]">
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
                                                                                            <input type="text" placeholder="mm/dd" class="form-control" name="FAMILY_SPECIAL_DATE[${data_counter}][]">
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
                        window.location.href='all_customers.php';
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
                                            window.location.href = 'all_customers.php';
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
                                window.location.href = 'all_customers.php';
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
                    if (PK_USER == 0) {
                        $('#interest_tab_link')[0].click();
                    }else{
                        window.location.href='all_customers.php';
                    }
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
                    if (PK_USER == 0) {
                        $('#document_tab_link')[0].click();
                    }else{
                        window.location.href='all_customers.php';
                    }
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
                    window.location.href='all_customers.php';
                }
            });
        });
    </script>


    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript">
        function stripePaymentFunction() {

            // Create a Stripe client.
            var stripe = Stripe('<?=$PUBLISHABLE_KEY?>');

            // Create an instance of Elements.
            var elements = stripe.elements();

            // Custom styling can be passed to options when creating an Element.
            // (Note that this demo uses a wider set of styles than the guide below.)
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
            var card = elements.create('card', {style: style});

            // Add an instance of the card Element into the `card-element` <div>.
            if (($('#card-element')).length > 0) {
                card.mount('#card-element');
            }

            // Handle real-time validation errors from the card Element.
            card.addEventListener('change', function (event) {
                var displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            // Handle form submission.
            var form = document.getElementById('payment_confirmation_form');
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                stripe.createToken(card).then(function (result) {
                    if (result.error) {
                        // Inform the user if there was an error.
                        var errorElement = document.getElementById('card-errors');
                        errorElement.textContent = result.error.message;
                    } else {
                        // Send the token to your server.
                        stripeTokenHandler(result.token);
                    }
                });
            });

            // Submit the form with the token ID.
            function stripeTokenHandler(token) {
                // Insert the token ID into the form so it gets submitted to the server
                var form = document.getElementById('payment_confirmation_form');
                var hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'token');
                hiddenInput.setAttribute('value', token.id);
                form.appendChild(hiddenInput);

                //ACCEPT_HANDLING_ERROR
                // Submit the form
                form.submit();
            }
        }

    </script>
    <script>
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        $('.datepicker-past').datepicker({
            format: 'mm/dd/yyyy',
            maxDate: 0
        });

        function payNow(PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BILLED_AMOUNT, ENROLLMENT_ID) {
            $('#enrollment_number').text(ENROLLMENT_ID);
            $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
            $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
            $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
            $('#payment_confirmation_form_div').slideDown();
            openModel();
        }

        function selectPaymentType(param){
            let paymentType = $("#PK_PAYMENT_TYPE option:selected").text();
            $('.payment_type_div').slideUp();
            $('#card-element').remove();
            switch (paymentType) {
                case 'Credit Card':
                    $('#card_div').html(`<div id="card-element"></div>`);
                    stripePaymentFunction();
                    $('#credit_card_payment').slideDown();
                    break;

                case 'Check':
                    $('#check_payment').slideDown();
                    break;

                case 'Wallet':
                    $('#wallet_balance_span').slideDown();
                    let AMOUNT_TO_PAY = parseFloat($('#AMOUNT_TO_PAY').val());
                    let WALLET_BALANCE = parseFloat($('#WALLET_BALANCE').val());

                    if(AMOUNT_TO_PAY > WALLET_BALANCE){
                        $('#REMAINING_AMOUNT').val(AMOUNT_TO_PAY-WALLET_BALANCE);
                        $('#remaining_amount_div').slideDown();
                        $('#PK_PAYMENT_TYPE_REMAINING').prop('required', true);
                    } else {
                        $('#remaining_amount_div').slideUp();
                        $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
                    }
                    break;

                case 'Cash':
                default:
                    $('.payment_type_div').slideUp();
                    $('#wallet_balance_span').slideUp();
                    $('#remaining_amount_div').slideUp();
                    $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
                    break;
            }
        }

        function selectRemainingPaymentType(param){
            let paymentType = $("#PK_PAYMENT_TYPE_REMAINING option:selected").text();
            $('.remaining_payment_type_div').slideUp();
            $('#card-element').remove();
            switch (paymentType) {
                case 'Credit Card':
                    $('#remaining_card_div').html(`<div id="card-element"></div>`);
                    stripePaymentFunction();
                    $('#remaining_credit_card_payment').slideDown();
                    break;

                case 'Check':
                    $('#remaining_check_payment').slideDown();
                    break;

                case 'Cash':
                default:
                    $('.remaining_payment_type_div').slideUp();
                    break;
            }
        }
    </script>


</body>
</html>