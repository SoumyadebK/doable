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

if(!empty($_POST) && $_POST['FUNCTION_NAME'] == 'confirmEnrollmentPayment'){
    $PK_ENROLLMENT_LEDGER = $_POST['PK_ENROLLMENT_LEDGER'];
    unset($_POST['PK_ENROLLMENT_LEDGER']);
    $AMOUNT = $_POST['AMOUNT'];
    if(empty($_POST['PK_ENROLLMENT_PAYMENT'])){
        if ($_POST['PK_PAYMENT_TYPE'] == 1) {
            if ($_POST['PAYMENT_GATEWAY'] == 'Stripe') {
                require_once("../global/stripe-php-master/init.php");
                \Stripe\Stripe::setApiKey($_POST['SECRET_KEY']);
                $STRIPE_TOKEN = $_POST['token'];
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
            $REMAINING_AMOUNT = $_POST['REMAINING_AMOUNT'];
            $WALLET_BALANCE = $_POST['WALLET_BALANCE'];

            if ($_POST['PK_PAYMENT_TYPE_REMAINING'] == 1) {
                require_once("../global/stripe-php-master/init.php");
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
            $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
            $DEBIT_AMOUNT = ($WALLET_BALANCE>$AMOUNT)?$AMOUNT:$WALLET_BALANCE;
            if ($wallet_data->RecordCount() > 0) {
                $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] - $DEBIT_AMOUNT;
            }
            $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
            $INSERT_DATA['DEBIT'] = $DEBIT_AMOUNT;
            $INSERT_DATA['DESCRIPTION'] = "Balance debited for payment of enrollment ".$_POST['PK_ENROLLMENT_MASTER'];
            $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');
        } else{
            $PAYMENT_INFO = 'Payment Done.';
        }

        $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
        $BILLING_DATA = $db_account->Execute("SELECT PK_ENROLLMENT_BILLING FROM DOA_ENROLLMENT_BILLING WHERE `PK_ENROLLMENT_MASTER`=".$_POST['PK_ENROLLMENT_MASTER']);
        $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = ($BILLING_DATA->RecordCount() > 0) ? $BILLING_DATA->fields['PK_ENROLLMENT_BILLING'] : 0;
        $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        $PAYMENT_DATA['AMOUNT'] = $AMOUNT;
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
        db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');

        $enrollment_balance = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '$_POST[PK_ENROLLMENT_MASTER]'");
        if ($enrollment_balance->RecordCount() > 0){
            $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_PAID'] = $enrollment_balance->fields['TOTAL_BALANCE_PAID']+$_POST['AMOUNT'];
            $ENROLLMENT_BALANCE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
            $ENROLLMENT_BALANCE_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
        }else{
            $ENROLLMENT_BALANCE_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
            $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_PAID'] = $_POST['AMOUNT'];
            $ENROLLMENT_BALANCE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
            $ENROLLMENT_BALANCE_DATA['CREATED_ON']  = date("Y-m-d H:i");
            db_perform_account('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'insert');
        }

        $PK_ENROLLMENT_PAYMENT = $db_account->insert_ID();
        $ledger_record = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_LEDGER` WHERE PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");
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
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
        $LEDGER_UPDATE_DATA['IS_PAID'] = 1;
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_UPDATE_DATA, 'update', "PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");
    }else{
        db_perform_account('DOA_ENROLLMENT_PAYMENT', $_POST, 'update'," PK_ENROLLMENT_PAYMENT =  '$_POST[PK_ENROLLMENT_PAYMENT]'");
        $PK_ENROLLMENT_PAYMENT = $_POST['PK_ENROLLMENT_PAYMENT'];
    }

    header('location:customer.php?id='.$_GET['id'].'&master_id='.$_GET['master_id']);
}

$PK_USER = '';
$PK_USER_MASTER = '';
$USER_NAME = '';
$FIRST_NAME = '';
$LAST_NAME = '';
$CUSTOMER_ID = '';
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
    $res = $db->Execute("SELECT * FROM DOA_USERS WHERE DOA_USERS.PK_USER = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_customers.php");
        exit;
    }
    $PK_USER = $_GET['id'];
    $PK_USER_MASTER = $_GET['master_id'];
    $USER_NAME = $res->fields['USER_NAME'];
    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
    $CUSTOMER_ID = $res->fields['USER_NAME'];
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

    $user_interest_other_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_INTEREST_OTHER_DATA` WHERE `PK_USER_MASTER` = '$_GET[master_id]'");
    if($user_interest_other_data->RecordCount() > 0){
        $WHAT_PROMPTED_YOU_TO_INQUIRE = $user_interest_other_data->fields['WHAT_PROMPTED_YOU_TO_INQUIRE'];
        $PK_SKILL_LEVEL = $user_interest_other_data->fields['PK_SKILL_LEVEL'];
        $PK_INQUIRY_METHOD = $user_interest_other_data->fields['PK_INQUIRY_METHOD'];
        $INQUIRY_TAKER_ID = $user_interest_other_data->fields['INQUIRY_TAKER_ID'];
    }

    $customer_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = '$_GET[master_id]'");
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
if(!empty($_GET['master_id'])) {
    $selected_primary_location = $db->Execute( "SELECT PRIMARY_LOCATION_ID FROM DOA_USER_MASTER WHERE PK_USER_MASTER = ".$_GET['master_id']);
    $primary_location = $selected_primary_location->fields['PRIMARY_LOCATION_ID'];
} else {
    $primary_location='';
}

?>
<!DOCTYPE html>
<html lang="en">
<style>
    .commentModel {
        z-index: 1011
    }
</style>
<?php require_once('../includes/header.php');?>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-4 align-self-center">
                    <h4 class="text-themecolor"><?php if(!empty($_GET['id'])) {
                            echo "Edit ".$FIRST_NAME." ".$LAST_NAME;
                        }?></h4>
                </div>
                <div class="col-md-4 align-self-center">
                <?php if(!empty($_GET['id'])) { ?>
                    <select required name="NAME" id="NAME" onchange="editpage(this);">
                        <option value="">Select Customer</option>
                        <?php
                        $row = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USERS.ACTIVE=1 AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY DOA_USERS.FIRST_NAME");
                        while (!$row->EOF) {?>
                            <option value="<?php echo $row->fields['PK_USER'];?>" data-master_id="<?php echo $row->fields['PK_USER_MASTER'];?>" <?=($row->fields['PK_USER_MASTER']==$_GET['master_id'])?'selected':''?>><?=$row->fields['NAME']?></option>
                        <?php $row->MoveNext(); } ?>
                    </select>
                <?php } ?>
            </div>
            <div class="col-md-4 align-self-center text-end">
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
                                                <?php if ($_GET['tab'] == 'comments') { ?>
                                                    <li> <a class="nav-link" id="comment_tab_link" data-bs-toggle="tab" href="#comments" role="tab" ><span class="hidden-sm-up"><i class="ti-comment"></i></span> <span class="hidden-xs-down">Comments</span></a> </li>
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
                                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#enrollment" onclick="showEnrollmentList(1)" role="tab" ><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">Enrollments</span></a> </li>
                                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#appointment" onclick="showListView(1)" role="tab" ><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">Appointments</span></a> </li>
                                                    <!--<li> <a class="nav-link" data-bs-toggle="tab" href="#billing" onclick="showBillingList(1)" role="tab" ><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>-->
                                                    <!--<li> <a class="nav-link" data-bs-toggle="tab" href="#accounts" onclick="showLedgerList(1)" role="tab" ><span class="hidden-sm-up"><i class="ti-book"></i></span> <span class="hidden-xs-down">Enrollment</span></a> </li>-->
                                                    <li> <a class="nav-link" id="comment_tab_link" data-bs-toggle="tab" href="#comments" role="tab" ><span class="hidden-sm-up"><i class="ti-comment"></i></span> <span class="hidden-xs-down">Comments</span></a> </li>
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
                                                                    <label class="form-label">Customer ID<span class="text-danger">*</span></label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" id="CUSTOMER_ID" name="CUSTOMER_ID" class="form-control" placeholder="Enter User Name" required value="<?=$CUSTOMER_ID?>">
                                                                        <div id="uname_result"></div>
                                                                    </div>
                                                                    <span id="lblError" style="color: red"></span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <input type="hidden" name="PK_ROLES[]" value="4">
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
                                                                    $customer_phone = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PHONE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
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
                                                                    $customer_email = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_EMAIL WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
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
                                                                    <select class="form-control" id="GENDER" name="GENDER">
                                                                        <option>Select Gender</option>
                                                                        <option value="Male" <?php if($GENDER == "Male") echo 'selected = "selected"';?>>Male</option>
                                                                        <option value="Female" <?php if($GENDER == "Female") echo 'selected = "selected"';?>>Female</option>
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
                                                                <div class="col-md-12 multiselect-box" style="width: 100%;">
                                                                    <select class="multi_sumo_select" name="PK_USER_LOCATION[]" id="PK_LOCATION_MULTIPLE" multiple required>
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

                                                            <div class="col-6">
                                                                <label class="col-md-12">Primary Location<span class="text-danger">*</span></label>
                                                                <div class="form-group" style="margin-bottom: 15px;">
                                                                    <select class="form-control" name="PRIMARY_LOCATION_ID" id="PK_LOCATION_SINGLE" required>
                                                                        <option value="">Select Primary Location</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=($primary_location == $row->fields['PK_LOCATION'])?"selected":""?>><?=$row->fields['LOCATION_NAME']?></option>
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

                                                        <?php if(!empty($_GET['id'])) { ?>
                                                            <div class="row <?=($INACTIVE_BY_ADMIN == 1)?'div_inactive':''?>" style="margin-bottom: 15px; margin-top: 15px;">
                                                                <div class="col-md-1">
                                                                    <label class="form-label">Active : </label>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE_CUSTOMER" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE_CUSTOMER" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
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
                                                                    <!--<div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Old Password</label>
                                                                            <input type="hidden" name="SAVED_OLD_PASSWORD" id="SAVED_OLD_PASSWORD" value="<?/*=$PASSWORD*/?>">
                                                                            <input type="password" required name="OLD_PASSWORD" id="OLD_PASSWORD" class="form-control">
                                                                        </div>
                                                                    </div>-->
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
                                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE_CUSTOMER" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE_CUSTOMER" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
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
                                                                        $row = $db->Execute("SELECT * FROM DOA_INTERESTS WHERE ACTIVE = 1 AND PK_BUSINESS_TYPE = ".$account_business_type->fields['PK_BUSINESS_TYPE']);
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
                                                                            $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES IN(2,3,5,6,7) AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
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

                                            <div class="tab-pane" id="enrollment" role="tabpanel">
                                                <div class="row">
                                                    <a class="btn btn-info d-none d-lg-block m-15 text-white right-aside" href="javascript:;" onclick="createEnrollment();" style="width: 120px; margin-left: 91%;"><i class="fa fa-plus-circle"></i> Enrollment</a>
                                                </div>
                                                <div id="enrollment_list" class="p-20">

                                                </div>
                                            </div>

                                            <!--Enrollment Model-->


                                            <div class="tab-pane" id="appointment" role="tabpanel">
                                                <a class="btn btn-info d-none d-lg-block m-15 text-white" href="javascript:;" onclick="createNewAppointment();" style="width: 125px; float: right;"><i class="fa fa-plus-circle"></i> Appointment</a>
                                                <div id="appointment_list" class="p-20">

                                                </div>
                                            </div>

                                            <div class="tab-pane" id="billing" role="tabpanel">
                                                <div id ="billing_list" class="p-20">

                                                </div>
                                            </div>


                                            <!--Payment Model-->
                                            <div id="paymentModel" class="modal">
                                                <!-- Modal content -->
                                                <div class="modal-content" style="width: 50%;">
                                                    <span class="close" style="margin-left: 96%;">&times;</span>

                                                    <div class="card" id="payment_confirmation_form_div_customer" style="display: none;">
                                                        <div class="card-body">
                                                            <h4><b>Payment</b></h4>

                                                            <form id="payment_confirmation_form_customer" role="form" action="" method="post">
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
                                                                                    <input type="text" name="AMOUNT" id="AMOUNT_TO_PAY_CUSTOMER" class="form-control" readonly>
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
                                                                                        $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
                                                                                        while (!$row->EOF) { ?>
                                                                                            <option value="<?php echo $row->fields['PK_PAYMENT_TYPE'];?>"><?=$row->fields['PAYMENT_TYPE']?></option>
                                                                                            <?php $row->MoveNext(); } ?>
                                                                                    </select>
                                                                                </div>
                                                                                <?php $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1"); ?>
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
                                                                        <div class="row payment_type_div" id="credit_card_payment_customer" style="display: none;">
                                                                            <div class="col-12">
                                                                                <div class="form-group" id="customer_card_div">

                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php } elseif ($PAYMENT_GATEWAY == 'Square'){?>
                                                                        <div class="payment_type_div" id="credit_card_payment_customer" style="display: none;">
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
                                            </div>

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

    <style>
        .progress-bar {
            border-radius: 5px;
            height:18px !important;
        }
    </style>
    <?php require_once('../includes/footer.php');?>
    <?php require_once('../admin/includes/enrollment_model.php');?>
    <?php require_once('../admin/includes/appointment_model.php');?>
    <?php require_once('../admin/includes/payment_list_model.php');?>


    <script>
        let PK_USER = parseInt(<?=empty($_GET['id'])?0:$_GET['id']?>);
        let PK_USER_MASTER = parseInt(<?=empty($_GET['master_id'])?0:$_GET['master_id']?>);
        // Get the modal
        var payment_model = document.getElementById("paymentModel");

        // Get the <span> element that closes the payment_model
        var payment_span = document.getElementsByClassName("close")[0];

        // When the user clicks the button, open the payment_model
        function openPaymentModel() {
            payment_model.style.display = "block";
        }

        // When the user clicks on <payment_span> (x), close the payment_model
        payment_span.onclick = function() {
            payment_model.style.display = "none";
        }

        // When the user clicks anywhere outside of the payment_model, close it
        window.onclick = function(event) {
            if (event.target == payment_model) {
                payment_model.style.display = "none";
            }
        }

        $(document).keydown(function(e) {
            // ESCAPE key pressed
            if (e.keyCode == 27) {
                payment_model.style.display = "none";
            }
        });


        // Get the modal
        var comment_model = document.getElementById("commentModel");

        // Get the <span> element that closes the comment_model
        var comment_span = document.getElementsByClassName("close_comment_model")[0];

        // When the user clicks the button, open the comment_model
        function openCommentModel() {
            comment_model.style.display = "block";
        }

        // When the user clicks on <comment_span> (x), close the comment_model
        comment_span.onclick = function() {
            comment_model.style.display = "none";
        }

        // When the user clicks anywhere outside of the comment_model, close it
        window.onclick = function(event) {
            if (event.target == comment_model) {
                comment_model.style.display = "none";
            }
        }

        $(document).keydown(function(e) {
            // ESCAPE key pressed
            if (e.keyCode == 27) {
                comment_model.style.display = "none";
            }
        });
    </script>

    <script>
        // Get the modal
        var enrollment_model = document.getElementById("enrollmentModel");

        // Get the <span> element that closes the enrollment_model
        var enrollment_span = document.getElementsByClassName("close_enrollment_model")[0];

        // When the user clicks the button, open the enrollment_model
        function openEnrollmentModel() {
            enrollment_model.style.display = "block";
        }

        // When the user clicks on <enrollment_span> (x), close the enrollment_model
        enrollment_span.onclick = function() {
            enrollment_model.style.display = "none";
        }

        // When the user clicks anywhere outside of the enrollment_model, close it
        window.onclick = function(event) {
            if (event.target == enrollment_model) {
                enrollment_model.style.display = "none";
            }
        }

        /*$(document).keydown(function(e) {
            // ESCAPE key pressed
            if (e.keyCode == 27) {
                enrollment_model.style.display = "none";
            }
        });*/
    </script>

        <script>
            // Get the modal
            var appointment_model = document.getElementById("appointmentModel");

            // Get the <span> element that closes the enrollment_model
            var appointment_span = document.getElementsByClassName("close_appointment_model")[0];

            // When the user clicks the button, open the enrollment_model
            function openAppointmentModel() {
                appointment_model.style.display = "block";
            }

            // When the user clicks on <appointment_span> (x), close the appointment_model
            appointment_span.onclick = function() {
                appointment_model.style.display = "none";
            }

            // When the user clicks anywhere outside of the appointment_model, close it
            window.onclick = function(event) {
                if (event.target == appointment_model) {
                    appointment_model.style.display = "none";
                }
            }

            $(document).keydown(function(e) {
                // ESCAPE key pressed
                if (e.keyCode == 27) {
                    appointment_model.style.display = "none";
                }
            });
        </script>

        <script>
            // Get the modal
            var payment_list_model = document.getElementById("paymentListModel");

            // Get the <span> element that closes the enrollment_model
            var payment_list_span = document.getElementsByClassName("close_payment_list_model")[0];

            // When the user clicks the button, open the payment_list_model
            function openPaymentListModel() {
                payment_list_model.style.display = "block";
            }

            // When the user clicks on <new_payment_span> (x), close the payment_list_model
            payment_list_span.onclick = function() {
                payment_list_model.style.display = "none";
            }

            // When the user clicks anywhere outside of the payment_list_model, close it
            window.onclick = function(event) {
                if (event.target == payment_list_model) {
                    payment_list_model.style.display = "none";
                }
            }

            /*$(document).keydown(function(e) {
                // ESCAPE key pressed
                if (e.keyCode == 27) {
                    payment_list_model.style.display = "none";
                }
            });*/
        </script>

    <script>
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
                                                                        <option value="Male" <?php if($GENDER == "Male") echo 'selected = "selected"';?>>Male</option>
                                                                        <option value="Female" <?php if($GENDER == "Female") echo 'selected = "selected"';?>>Female</option>
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
                    if (PK_USER == 0) {
                        $('#interest_tab_link')[0].click();
                    }else{
                        let PK_USER = $('.PK_USER').val();
                        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                        window.location.href='customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
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
                        let PK_USER = $('.PK_USER').val();
                        let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                        window.location.href='customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
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
                    let PK_USER = $('.PK_USER').val();
                    let PK_USER_MASTER = $('.PK_USER_MASTER').val();
                    window.location.href='customer.php?id='+PK_USER+'&master_id='+PK_USER_MASTER;
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
            var form = document.getElementById('payment_confirmation_form_customer');
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
                var form = document.getElementById('payment_confirmation_form_customer');
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
        $('#NAME').SumoSelect({placeholder: 'Select Customer', search: true, searchText: 'Search...'});

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
            $('#AMOUNT_TO_PAY_CUSTOMER').val(BILLED_AMOUNT);
            $('#payment_confirmation_form_div_customer').slideDown();
            openPaymentModel();
        }

        function selectPaymentTypeCustomer(param){
            let paymentType = $("#PK_PAYMENT_TYPE_CUSTOMER option:selected").text();
            $('.payment_type_div').slideUp();
            $('#card-element').remove();
            switch (paymentType) {
                case 'Credit Card':
                    $('#customer_card_div').html(`<div id="card-element"></div>`);
                    stripePaymentFunction();
                    $('#credit_card_payment_customer').slideDown();
                    break;

                case 'Check':
                    $('#check_payment_customer').slideDown();
                    break;

                case 'Wallet':
                    $('#wallet_balance_span').slideDown();
                    let AMOUNT_TO_PAY_CUSTOMER = parseFloat($('#AMOUNT_TO_PAY_CUSTOMER').val());
                    let WALLET_BALANCE = parseFloat($('#WALLET_BALANCE').val());

                    if(AMOUNT_TO_PAY_CUSTOMER > WALLET_BALANCE){
                        $('#REMAINING_AMOUNT_CUSTOMER').val(AMOUNT_TO_PAY_CUSTOMER-WALLET_BALANCE);
                        $('#remaining_amount_div').slideDown();
                        $('#PK_PAYMENT_TYPE_REMAINING_CUSTOMER').prop('required', true);
                    } else {
                        $('#remaining_amount_div').slideUp();
                        $('#PK_PAYMENT_TYPE_REMAINING_CUSTOMER').prop('required', false);
                    }
                    break;

                case 'Cash':
                default:
                    $('.payment_type_div').slideUp();
                    $('#wallet_balance_span').slideUp();
                    $('#remaining_amount_div').slideUp();
                    $('#PK_PAYMENT_TYPE_REMAINING_CUSTOMER').prop('required', false);
                    break;
            }
        }

        function selectRemainingPaymentType(param){
            let paymentType = $("#PK_PAYMENT_TYPE_REMAINING_CUSTOMER option:selected").text();
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

        function showEnrollmentList(page) {
            let PK_USER_MASTER=$('.PK_USER_MASTER').val();
            $.ajax({
                url: "pagination/enrollment.php",
                type: "GET",
                data: {search_text:'', page:page, master_id:PK_USER_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#enrollment_list').html(result)
                }
            });
            window.scrollTo(0,0);
        }

        function showListView(page) {
            let PK_USER_MASTER=$('.PK_USER_MASTER').val();
            $.ajax({
                url: "pagination/appointment.php",
                type: "GET",
                data: {search_text:'', page:page, master_id:PK_USER_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#appointment_list').html(result)
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
                    if (CUSTOMER_ID != '') {
                        $.ajax({
                            url: 'ajax/username_checker.php',
                            type: 'post',
                            data: { CUSTOMER_ID: CUSTOMER_ID },
                            success: function (response) {
                                $('#uname_result').html(response);
                            }
                        });
                    } else {
                        $("#uname_result").html("");
                    }
                });
            });
        </script>

</body>
</html>