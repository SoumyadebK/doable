<?php
require_once('../global/config.php');
require_once("../global/stripe/init.php");

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

$SQUARE_APP_ID 			= "sandbox-sq0idb-co7WstGtX_jQETuk18coQw";
$SQUARE_LOCATION_ID 	= "C0K6B0E6FNJRY";
$ACCESS_TOKEN 			= "EAAAEIhnXoKUu_9UUKem1yEohi8v3Q2Kg0eIR2SErebQA5gabFWENN_44xpjbRQ9";

if (empty($_GET['id']))
    $title = "Add Enrollment";
else
    $title = "Edit Enrollment";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if(!empty($_GET['customer_id'])) {
    $PK_USER_MASTER = $_GET['customer_id'];
}else{
    $PK_USER_MASTER = '';
}

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");

$PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];
$PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];

$SQUARE_MODE 			= 2;
if ($SQUARE_MODE == 1)
    $SQ_URL = "https://connect.squareup.com";
else if ($SQUARE_MODE == 2)
    $SQ_URL = "https://connect.squareupsandbox.com";

if ($SQUARE_MODE == 1)
    $URL = "https://web.squarecdn.com/v1/square.js";
else if ($SQUARE_MODE == 2)
    $URL = "https://sandbox.web.squarecdn.com/v1/square.js";

if(!empty($_POST['PK_PAYMENT_TYPE'])){

    $PK_ENROLLMENT_LEDGER = $_POST['PK_ENROLLMENT_LEDGER'];

    $account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");

    $SECRET_KEY = $account_data->fields['SECRET_KEY'];

    $LOGIN_ID = $account_data->fields['LOGIN_ID'];
    $TRANSACTION_KEY = $account_data->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY = $account_data->fields['AUTHORIZE_CLIENT_KEY'];

    unset($_POST['PK_ENROLLMENT_LEDGER']);
    if(empty($_POST['PK_ENROLLMENT_PAYMENT'])){
        if ($_POST['PK_PAYMENT_TYPE'] == 1) {
            if ($_POST['PAYMENT_GATEWAY'] == 'Stripe') {
                require_once("../global/stripe/init.php");
                \Stripe\Stripe::setApiKey($SECRET_KEY);

                $user_master = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

                $STRIPE_TOKEN = $_POST['token'];


                // Create a Customer:
                $customer = \Stripe\Customer::create([
                    'source' => $STRIPE_TOKEN,
                    'email' => $user_master->fields['EMAIL_ID'],
                ]);


                $AMOUNT = $_POST['AMOUNT'];
                try {
                    $charge = \Stripe\Charge::create([
                        'amount' => ($AMOUNT * 100),
                        'currency' => 'usd',
                        'description' => $_POST['NOTE'],
                        'customer' => $customer->id
                    ]);
                } catch (Exception $e) {

                }

                if ($charge->paid == 1) {
                    $PAYMENT_INFO = $charge->id;

                } else {
                    $PAYMENT_INFO = 'Payment Unsuccessful.';
                }



                $STRIPE_DETAILS['PK_USER']  = $user_master->fields['PK_USER'];
                $STRIPE_DETAILS['CUSTOMER_PAYMENT_ID'] = $customer->id;
                $STRIPE_DETAILS['PAYMENT_TYPE'] = $_POST['PAYMENT_GATEWAY'];
                $STRIPE_DETAILS['CREATED_ON'] = date("Y-m-d H:i");

                db_perform('DOA_CUSTOMER_PAYMENT_INFO', $STRIPE_DETAILS, 'insert');

            } elseif ($_POST['PAYMENT_GATEWAY'] == 'Square') {

                require_once("../global/square/autoload.php");

                $AMOUNT = $_POST['AMOUNT'];

                $api_config = new \SquareConnect\Configuration();
                $api_config->setHost($SQ_URL);

                $api_config->setAccessToken($ACCESS_TOKEN);
                $api_client = new \SquareConnect\ApiClient($api_config);
                $payments_api = new \SquareConnect\Api\PaymentsApi($api_client);

                $request_body = array(
                    "source_id" => $_POST['sourceId'],
                    "amount_money" => array(
                        "amount" => ($AMOUNT * 100),
                        "currency" => "USD"
                    ),
                    "idempotency_key" => uniqid(),
                    "statement_description_identifier" => "Doable"
                );

                try {
                    $result = $payments_api->createPayment($request_body);
                    //echo "<pre>";print_r($result); die;

                    if (strtoupper($result['payment']['status']) == 'COMPLETED') {

                        $PAYMENT_INFO = $result['payment']['id'] ;
                        $PAYMENT_INFO_LAST = $result['payment']['card_details']['card']['last_4'];
                        $PAYMENT_INFO_EXP_MONTH = $result['payment']['card_details']['card']['exp_month'];
                        $PAYMENT_INFO_EXP_YEAR = $result['payment']['card_details']['card']['exp_year'];
                        //$PAYMENT_INFO_CUSTOMER_ID = $result['payment']['card_details']['customer_id'];

                    } else {
                        $PAYMENT_INFO = "Payment Unsuccessful.";
                    }



                } catch (\SquareConnect\ApiException $e) {
                    $errors = $e->getResponseBody()->errors;
                        echo "<pre>";print_r($errors);

                    $PAYMENT_INFO = "";
                    foreach ($errors as $error) {
                        if ($PAYMENT_INFO != '')
                            $PAYMENT_INFO .= ', ';

                        $PAYMENT_INFO .= $error->detail;
                    }
                    echo $PAYMENT_INFO;
                }



            } elseif ($_POST['PAYMENT_GATEWAY'] == 'Authorized.net') {

                require_once('../global/authorizenet/vendor/autoload.php');

                $LOGIN_ID = $account_data->fields['LOGIN_ID'];
                $TRANSACTION_KEY = $account_data->fields['TRANSACTION_KEY'];

                // Product Details
                $itemName = $_POST['PK_ENROLLMENT_MASTER'];
                $itemNumber = $_POST['PK_ENROLLMENT_BILLING'];
                $itemPrice = $_POST['AMOUNT'];
                $currency = "USD";

                // Retrieve card and user info from the submitted form data
                $name = $_POST['NAME'];
                $email = $_POST['EMAIL'];
                $card_number = preg_replace('/\s+/', '', $_POST['CARD_NUMBER']);
                $card_exp_month = $_POST['EXPIRATION_MONTH'];
                $card_exp_year = $_POST['EXPIRATION_YEAR'];
                $card_exp_year_month = $card_exp_year . '-' . $card_exp_month;
                $card_cvc = $_POST['SECURITY_CODE'];

                $refID = 'ref' . time();

                $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                $merchantAuthentication->setName($LOGIN_ID);
                $merchantAuthentication->setTransactionKey($TRANSACTION_KEY);

                // Create the payment data for a credit card
                $creditCard = new AnetAPI\CreditCardType();
                $creditCard->setCardNumber($card_number);
                $creditCard->setExpirationDate($card_exp_year_month);
                $creditCard->setCardCode($card_cvc);

                // Add the payment data to a paymentType object
                $paymentOne = new AnetAPI\PaymentType();
                $paymentOne->setCreditCard($creditCard);

                // Create order information
                $order = new AnetAPI\OrderType();
                $order->setDescription($itemName);

                // Set the customer's identifying information
                $customerData = new AnetAPI\CustomerDataType();
                $customerData->setType("individual");
                $customerData->setEmail($email);

                $ANET_ENV = 'PRODUCTION';

                // Create a transaction
                $transactionRequestType = new AnetAPI\TransactionRequestType();
                $transactionRequestType->setTransactionType("authCaptureTransaction");
                $transactionRequestType->setAmount($itemPrice);
                $transactionRequestType->setOrder($order);
                $transactionRequestType->setPayment($paymentOne);
                $transactionRequestType->setCustomer($customerData);
                $request = new AnetAPI\CreateTransactionRequest();
                $request->setMerchantAuthentication($merchantAuthentication);
                $request->setRefId($refID);
                $request->setTransactionRequest($transactionRequestType);
                $controller = new AnetController\CreateTransactionController($request);
                $response = $controller->executeWithApiResponse(constant("\\net\authorize\api\constants\ANetEnvironment::$ANET_ENV"));

                if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                    $tresponse = $response->getTransactionResponse();
                    $PAYMENT_INFO = $tresponse->getTransId();
                } else {
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

        }else{
            $PAYMENT_INFO = 'Payment Done.';
        }

        $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
        $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $_POST['PK_ENROLLMENT_BILLING'];
        $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        $PAYMENT_DATA['AMOUNT'] = $_POST['AMOUNT'];
        if ($_POST['PK_PAYMENT_TYPE'] == 7) {
            $PAYMENT_DATA['REMAINING_AMOUNT'] = $_POST['REMAINING_AMOUNT'];
            $PAYMENT_DATA['CHECK_NUMBER'] = $_POST['CHECK_NUMBER_REMAINING'];
            $PAYMENT_DATA['CHECK_DATE'] = date('Y-m-d', strtotime($_POST['CHECK_DATE_REMAINING']));
        } elseif($_POST['PK_PAYMENT_TYPE'] == 2) {
            $PAYMENT_DATA['REMAINING_AMOUNT'] = 0.00;
            $PAYMENT_DATA['CHECK_NUMBER'] = $_POST['CHECK_NUMBER'];
            $PAYMENT_DATA['CHECK_DATE'] = date('Y-m-d', strtotime($_POST['CHECK_DATE']));
        }

        $PAYMENT_DATA['NOTE'] = $_POST['NOTE'];
        $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
        $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;

        if($_POST['PK_PAYMENT_TYPE'] == 1 && $_POST['PAYMENT_GATEWAY'] == 'Authorized.net') {
            $PAYMENT_DATA['NAME'] = $_POST['NAME'];
            $PAYMENT_DATA['CARD_NUMBER'] = $_POST['CARD_NUMBER'];
            $PAYMENT_DATA['EXPIRATION_DATE'] = $_POST['EXPIRATION_MONTH'] . "/" . $_POST['EXPIRATION_YEAR'];
            $PAYMENT_DATA['SECURITY_CODE'] = $_POST['SECURITY_CODE'];
        } elseif($_POST['PK_PAYMENT_TYPE'] == 1 && $_POST['PAYMENT_GATEWAY'] == 'Square') {
            $PAYMENT_DATA['CARD_NUMBER'] = $PAYMENT_INFO_LAST;
            $PAYMENT_DATA['EXPIRATION_DATE'] = $PAYMENT_INFO_EXP_MONTH . "/" . $PAYMENT_INFO_EXP_YEAR;
            $PAYMENT_DATA['CUSTOMER_ID'] = $PAYMENT_INFO_CUSTOMER_ID;
        }

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

    header('location:all_enrollments.php');
}

$PK_LOCATION = '';
$PK_AGREEMENT_TYPE = '';
$PK_DOCUMENT_LIBRARY = '';
$AGREEMENT_PDF_LINK = '';
$ENROLLMENT_BY_ID = $_SESSION['PK_USER'];
$ACTIVE = '';

$PK_ENROLLMENT_BILLING = '';
$BILLING_REF = '';
$BILLING_DATE = '';
$DOWN_PAYMENT = 0;
$BALANCE_PAYABLE = '';
$PAYMENT_METHOD = '';
$PAYMENT_TERM = '';
$NUMBER_OF_PAYMENT = '';
$FIRST_DUE_DATE = '';
$INSTALLMENT_AMOUNT = '';

$PK_ENROLLMENT_PAYMENT = '';
$PK_PAYMENT_TYPE = '';
$AMOUNT = '';
$NAME = '';
$CARD_NUMBER = '';
$SECURITY_CODE = '';
$EXPIRATION_DATE = '';
$CHECK_NUMBER = '';
$CHECK_DATE = '';
$NOTE = '';

if(!empty($_GET['id'])) {
    $res = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_enrollments.php");
        exit;
    }

    $PK_USER_MASTER = $res->fields['PK_USER_MASTER'];
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $PK_AGREEMENT_TYPE = $res->fields['PK_AGREEMENT_TYPE'];
    $PK_DOCUMENT_LIBRARY = $res->fields['PK_DOCUMENT_LIBRARY'];
    $AGREEMENT_PDF_LINK = $res->fields['AGREEMENT_PDF_LINK'];
    $ENROLLMENT_BY_ID = $res->fields['ENROLLMENT_BY_ID'];
    $ACTIVE = $res->fields['ACTIVE'];

    $billing_data = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER` = '$_GET[id]'");

    if($billing_data->RecordCount() > 0){
        $PK_ENROLLMENT_BILLING = $billing_data->fields['PK_ENROLLMENT_BILLING'];
        $BILLING_REF = $billing_data->fields['BILLING_REF'];
        $BILLING_DATE = $billing_data->fields['BILLING_DATE'];
        $DOWN_PAYMENT = $billing_data->fields['DOWN_PAYMENT'];
        $BALANCE_PAYABLE = $billing_data->fields['BALANCE_PAYABLE'];
        $PAYMENT_METHOD = $billing_data->fields['PAYMENT_METHOD'];
        $PAYMENT_TERM = $billing_data->fields['PAYMENT_TERM'];
        $NUMBER_OF_PAYMENT = $billing_data->fields['NUMBER_OF_PAYMENT'];
        $FIRST_DUE_DATE = $billing_data->fields['FIRST_DUE_DATE'];
        $INSTALLMENT_AMOUNT = $billing_data->fields['INSTALLMENT_AMOUNT'];
    }

    $payment_data = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_PAYMENT` WHERE `PK_ENROLLMENT_MASTER` = '$_GET[id]'");

    if($payment_data->RecordCount() > 0){
        $PK_ENROLLMENT_PAYMENT = $payment_data->fields['PK_ENROLLMENT_PAYMENT'];
        $PK_PAYMENT_TYPE = $payment_data->fields['PK_PAYMENT_TYPE'];
        $AMOUNT = $payment_data->fields['AMOUNT'];
        $NAME = $payment_data->fields['NAME'];
        $CARD_NUMBER = $payment_data->fields['CARD_NUMBER'];
        $SECURITY_CODE = $payment_data->fields['SECURITY_CODE'];
        $EXPIRATION_DATE = $payment_data->fields['EXPIRATION_DATE'];
        $CHECK_NUMBER = $payment_data->fields['CHECK_NUMBER'];
        $CHECK_DATE = $payment_data->fields['CHECK_DATE'];
        $NOTE = $payment_data->fields['NOTE'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>
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

    .SumoSelect{
        width: 90%;
    }
</style>
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
                            <li class="breadcrumb-item"><a href="all_enrollments.php">All Enrollments</a></li>
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
                                <li class="active"> <a class="nav-link active" data-bs-toggle="tab" id="enrollment_link" href="#enrollment" role="tab"><span class="hidden-sm-up"><i class="ti-pencil-alt"></i></span> <span class="hidden-xs-down">Enrollment</span></a> </li>
                                <li> <a class="nav-link" data-bs-toggle="tab" id="billing_link" href="#billing" role="tab" onclick="goToPaymentTab()"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>
                                <li> <a class="nav-link" data-bs-toggle="tab" id="ledger_link" href="#ledger" role="tab" onclick="goToLedgerTab()"><span class="hidden-sm-up"><i class="ti-book"></i></span> <span class="hidden-xs-down">Ledger</span></a> </li>
                                <?php if (!empty($_GET['id'])) { ?>
                                    <li> <a class="nav-link" data-bs-toggle="tab" id="history_link" href="#history" role="tab"><span class="hidden-sm-up"><i class="ti-book"></i></span> <span class="hidden-xs-down">History</span></a> </li>
                                <?php } ?>
                            </ul>


                            <!-- Tab panes -->
                            <div class="tab-content tabcontent-border">
                                <div class="tab-pane active" id="enrollment" role="tabpanel">
                                    <form id="enrollment_form">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveEnrollmentData">
                                        <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER" value="<?=(empty($_GET['id']))?'':$_GET['id']?>">
                                        <div class="p-20">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Customer<span class="text-danger">*</span></label><br>
                                                        <select required name="PK_USER_MASTER" id="PK_USER_MASTER" onchange="selectThisCustomer(this);">
                                                            <option value="">Select Customer</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_ID, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.PK_LOCATION, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USERS.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_USER_MASTER'];?>" data-location_id="<?=$row->fields['PK_LOCATION']?>" data-customer_name="<?=$row->fields['NAME']?>" <?=($PK_USER_MASTER == $row->fields['PK_USER_MASTER'])?'selected':''?>><?=$row->fields['NAME'].' ('.$row->fields['PHONE'].')'.' ('.$row->fields['EMAIL_ID'].')'?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Location<span class="text-danger">*</span></label>
                                                        <select class="form-control" required name="PK_LOCATION" id="PK_LOCATION">
                                                            <option value="">Select Location</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY LOCATION_NAME");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=($PK_LOCATION == $row->fields['PK_LOCATION'])?'selected':''?>><?=$row->fields['LOCATION_NAME']?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card-body" id="append_service_div">
                                                <div class="row">
                                                    <div class="col-2">
                                                        <div class="form-group">
                                                            <label class="form-label">Services</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-2">
                                                        <div class="form-group">
                                                            <label class="form-label">Service Codes</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-2">
                                                        <div class="form-group">
                                                            <label class="form-label">Service Details</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-2 session_div">
                                                        <div class="form-group">
                                                            <label class="form-label">Number of Sessions</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-2 session_div">
                                                        <div class="form-group">
                                                            <label class="form-label">Price Per Sessions</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-4 frequency_div" style="display: none; text-align: center;">
                                                        <div class="form-group">
                                                            <label class="form-label">Frequency</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-1" style="width: 11%;">
                                                        <div class="form-group">
                                                            <label class="form-label">Total</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php
                                                $PK_SERVICE_CLASS = 0;
                                                if(!empty($_GET['id'])) {
                                                $enrollment_service_data = $db->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = '$_GET[id]'");

                                                while (!$enrollment_service_data->EOF) {
                                                    $service_class = $db->Execute("SELECT PK_SERVICE_CLASS FROM DOA_SERVICE_MASTER WHERE PK_SERVICE_MASTER = ".$enrollment_service_data->fields['PK_SERVICE_MASTER']);
                                                    $PK_SERVICE_CLASS = $service_class->fields['PK_SERVICE_CLASS'];
                                                    ?>
                                                    <div class="row">
                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                                    <option>Select</option>
                                                                    <?php
                                                                    $row = $db->Execute("SELECT PK_SERVICE_MASTER, SERVICE_NAME, PK_SERVICE_CLASS FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY SERVICE_NAME");
                                                                    while (!$row->EOF) { ?>
                                                                        <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" data-service_class="<?=$row->fields['PK_SERVICE_CLASS']?>" data-service_code="<?=$enrollment_service_data->fields['PK_SERVICE_CODE']?>" <?=($row->fields['PK_SERVICE_MASTER'] == $enrollment_service_data->fields['PK_SERVICE_MASTER'])?'selected':''?>><?=$row->fields['SERVICE_NAME']?></option>
                                                                    <?php $row->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                                    <option value="">Select</option>
                                                                    <?php
                                                                    $row = $db->Execute("SELECT DOA_SERVICE_CODE.*, DOA_FREQUENCY.FREQUENCY FROM DOA_SERVICE_CODE LEFT JOIN DOA_FREQUENCY ON DOA_SERVICE_CODE.PK_FREQUENCY = DOA_FREQUENCY.PK_FREQUENCY WHERE PK_SERVICE_MASTER = ".$enrollment_service_data->fields['PK_SERVICE_MASTER']);
                                                                    while (!$row->EOF) { ?>
                                                                        <option value="<?php echo $row->fields['PK_SERVICE_CODE'];?>" data-service_details="<?=$row->fields['DESCRIPTION']?>" data-frequency="<?=$row->fields['FREQUENCY']?>" data-price="<?=$row->fields['PRICE']?>" <?=($row->fields['PK_SERVICE_CODE'] == $enrollment_service_data->fields['PK_SERVICE_CODE'])?'selected':''?>><?=$row->fields['SERVICE_CODE']?></option>
                                                                    <?php $row->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <?php if($PK_SERVICE_CLASS == 1){ ?>
                                                            <div class="col-4">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control FREQUENCY" name="FREQUENCY[]" value="<?=$enrollment_service_data->fields['FREQUENCY']?>" readonly>
                                                                </div>
                                                            </div>
                                                        <?php }elseif($PK_SERVICE_CLASS == 2){ ?>
                                                            <div class="col-2">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" value="<?=$enrollment_service_data->fields['SERVICE_DETAILS']?>">
                                                                </div>
                                                            </div>
                                                            <div class="col-2">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" value="<?=$enrollment_service_data->fields['NUMBER_OF_SESSION']?>" onkeyup="calculateServiceTotal(this)">
                                                                </div>
                                                            </div>
                                                        <?php } ?>

                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" value="<?=$enrollment_service_data->fields['PRICE_PER_SESSION']?>" onkeyup="calculateServiceTotal(this);">
                                                            </div>
                                                        </div>
                                                        <div class="col-1" style="width: 11%;">
                                                            <div class="form-group">
                                                                <input type="text" class="form-control TOTAL" value="<?=$enrollment_service_data->fields['TOTAL']?>" name="TOTAL[]">
                                                            </div>
                                                        </div>
                                                        <div class="col-1" style="width: 5%;">
                                                            <div class="form-group">
                                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php $enrollment_service_data->MoveNext(); } ?>
                                                <?php } else { ?>
                                                    <div class="row">
                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                                    <option>Select</option>
                                                                    <?php
                                                                    $row = $db->Execute("SELECT PK_SERVICE_MASTER, SERVICE_NAME, PK_SERVICE_CLASS FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY SERVICE_NAME");
                                                                    while (!$row->EOF) { ?>
                                                                        <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" data-service_class="<?=$row->fields['PK_SERVICE_CLASS']?>"><?=$row->fields['SERVICE_NAME']?></option>
                                                                    <?php $row->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                                    <option value="">Select</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" >
                                                            </div>
                                                        </div>
                                                        <div class="col-4 frequency_div" style="display: none;">
                                                            <div class="form-group">
                                                                <input type="text" class="form-control FREQUENCY" name="FREQUENCY[]" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="col-2 session_div">
                                                            <div class="form-group">
                                                                <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)">
                                                            </div>
                                                        </div>
                                                        <div class="col-2 session_div">
                                                            <div class="form-group">
                                                                <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);">
                                                            </div>
                                                        </div>
                                                        <div class="col-1" style="width: 11%;">
                                                            <div class="form-group">
                                                                <input type="text" class="form-control TOTAL" name="TOTAL[]">
                                                            </div>
                                                        </div>
                                                        <div class="col-1" style="width: 5%;">
                                                            <div class="form-group">
                                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>

                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-group" style="float: right;">
                                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addMoreServices();">Add More</a>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Agreement Type<span class="text-danger">*</span></label>
                                                        <select class="form-control" required name="PK_AGREEMENT_TYPE" id="PK_AGREEMENT_TYPE">
                                                            <option value="">Select Agreement Type</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT PK_AGREEMENT_TYPE, AGREEMENT_TYPE FROM DOA_AGREEMENT_TYPE WHERE ACTIVE = 1 ORDER BY PK_AGREEMENT_TYPE");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_AGREEMENT_TYPE'];?>" <?=($PK_AGREEMENT_TYPE == $row->fields['PK_AGREEMENT_TYPE'])?'selected':''?>><?=$row->fields['AGREEMENT_TYPE']?></option>
                                                                <?php $row->MoveNext(); } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Agreement Template<span class="text-danger">*</span></label>
                                                        <select class="form-control" required name="PK_DOCUMENT_LIBRARY" id="PK_DOCUMENT_LIBRARY">
                                                            <option value="">Select Agreement Template</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT PK_DOCUMENT_LIBRARY, DOCUMENT_NAME FROM DOA_DOCUMENT_LIBRARY WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY PK_DOCUMENT_LIBRARY");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_DOCUMENT_LIBRARY'];?>" <?=($PK_DOCUMENT_LIBRARY == $row->fields['PK_DOCUMENT_LIBRARY'])?'selected':''?>><?=$row->fields['DOCUMENT_NAME']?></option>
                                                                <?php $row->MoveNext(); } ?>
                                                        </select>
                                                        <?php if ($AGREEMENT_PDF_LINK != '' && $AGREEMENT_PDF_LINK != null) { ?>
                                                            <a href="../uploads/enrollment_pdf/<?=$AGREEMENT_PDF_LINK?>" target="_blank">View Agreement</a>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Enrollment By<span class="text-danger">*</span></label>
                                                        <select class="form-control" required name="ENROLLMENT_BY_ID" id="ENROLLMENT_BY_ID">
                                                            <option value="">Select</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT PK_USER, CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS WHERE PK_ROLES IN (2,3) AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY FIRST_NAME");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_USER'];?>" <?=($ENROLLMENT_BY_ID == $row->fields['PK_USER'])?'selected':''?>><?=$row->fields['NAME']?></option>
                                                                <?php $row->MoveNext(); } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

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

                                            <div class="form-group">
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Continue</button>
                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane" id="billing" role="tabpanel" style="pointer-events: <?=($PK_ENROLLMENT_BILLING>0)?'none':''?>; opacity: <?=($PK_ENROLLMENT_BILLING>0)?'60%':''?>">
                                    <div class="card">
                                        <div class="card-body">
                                            <form id="billing_form">
                                                <input type="hidden" name="FUNCTION_NAME" value="saveEnrollmentBillingData">
                                                <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER" value="<?=(empty($_GET['id']))?'':$_GET['id']?>">
                                                <input type="hidden" name="PK_ENROLLMENT_BILLING" class="PK_ENROLLMENT_BILLING" value="<?=$PK_ENROLLMENT_BILLING?>">
                                                <input type="hidden" name="PK_SERVICE_CLASS" class="PK_SERVICE_CLASS" value="<?=$PK_SERVICE_CLASS?>">
                                                <div class="p-20">
                                                    <div class="row" id="payment_tab_div">
                                                        <!--Data coming from ajax-->
                                                    </div>
                                                    <div class="row" style="margin-top: -50px;">
                                                        <h4><b>Payment Plans</b></h4>
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label class="form-label">Billing Ref #</label>
                                                                <div class="col-md-12">
                                                                    <input type="text" name="BILLING_REF" id="BILLING_REF" class="form-control" value="<?=$BILLING_REF?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label class="form-label">Billing Date</label>
                                                                <div class="col-md-12">
                                                                    <input type="text" name="BILLING_DATE" id="BILLING_DATE" value="<?=($BILLING_DATE == '')?date('m/d/Y'):date('m/d/Y', strtotime($BILLING_DATE))?>" class="form-control datepicker-normal">
                                                                </div>
                                                            </div>
                                                        </div>


                                                        <div class="row frequency_div">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">First Payment Date</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="MEMBERSHIP_PAYMENT_DATE" id="MEMBERSHIP_PAYMENT_DATE" value="<?=($FIRST_DUE_DATE)?date('m/d/Y', strtotime($FIRST_DUE_DATE)):''?>" class="form-control datepicker-future">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Amount</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="MEMBERSHIP_PAYMENT_AMOUNT" id="MEMBERSHIP_PAYMENT_AMOUNT" value="<?=$INSTALLMENT_AMOUNT?>" class="form-control">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row session_div">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Payment Method</label>
                                                                    <div class="col-md-12">
                                                                        <div class="row">
                                                                            <div class="col-md-3">
                                                                                <label><input type="radio" class="form-check-inline PAYMENT_METHOD" name="PAYMENT_METHOD" value="One Time" <?=($PAYMENT_METHOD == 'One Time')?'checked':''?>>One Time</label>
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                <label><input type="radio" class="form-check-inline PAYMENT_METHOD" name="PAYMENT_METHOD" value="Payment Plans" <?=($PAYMENT_METHOD == 'Payment Plans')?'checked':''?>>Payment Plans</label>
                                                                            </div>
                                                                            <div class="col-md-5">
                                                                                <label><input type="radio" class="form-check-inline PAYMENT_METHOD" name="PAYMENT_METHOD" value="Flexible Payments" <?=($PAYMENT_METHOD == 'Flexible Payments')?'checked':''?>>Flexible Payments</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <label class="form-label">Balance Payable</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="BALANCE_PAYABLE" id="BALANCE_PAYABLE" value="<?=$BALANCE_PAYABLE?>" class="form-control" value="0.00" readonly>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-3" id="down_payment_div" style="display: <?=($PAYMENT_METHOD == 'One Time')?'none':''?>">
                                                                <div class="form-group">
                                                                    <label class="form-label">Down Payment</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="DOWN_PAYMENT" id="DOWN_PAYMENT" value="<?=$DOWN_PAYMENT?>" class="form-control" onkeyup="calculatePayment()">
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row payment_method_div" id="payment_plans_div" style="display: <?=($PAYMENT_METHOD == 'Payment Plans')?'':'none'?>;">
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Payment Term</label>
                                                                        <div class="col-md-12">
                                                                            <select class="form-control" name="PAYMENT_TERM" id="PAYMENT_TERM">
                                                                                <option value="">Select</option>
                                                                                <option value="Monthly" <?=($PAYMENT_TERM == 'Monthly')?'selected':''?>>Monthly</option>
                                                                                <option value="Quarterly" <?=($PAYMENT_TERM == 'Quarterly')?'selected':''?>>Quarterly</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Number of Payments</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="NUMBER_OF_PAYMENT" id="NUMBER_OF_PAYMENT" value="<?=$NUMBER_OF_PAYMENT?>" class="form-control" onkeyup="calculatePaymentPlans();">
                                                                        </div>
                                                                        <p id="number_of_payment_error" style="color: red; display: none; font-size: 10px;">This value should be a whole number. Please correct</p>
                                                                    </div>
                                                                </div>
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">First Payment Date</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="FIRST_DUE_DATE" id="FIRST_DUE_DATE" value="<?=($FIRST_DUE_DATE)?date('m/d/Y', strtotime($FIRST_DUE_DATE)):''?>" class="form-control datepicker-future">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Installment Amount</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="INSTALLMENT_AMOUNT" id="INSTALLMENT_AMOUNT" value="<?=$INSTALLMENT_AMOUNT?>" class="form-control" onkeyup="calculateNumberOfPayment(this)">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row payment_method_div" id="flexible_plans_div" style="display: <?=($PAYMENT_METHOD == 'Flexible Payments')?'':'none'?>">
                                                                <div class="row">
                                                                    <div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Payment Date</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Amount</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-3" style="margin-top: -30px;">
                                                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="addMorePayments();">Add More</a>
                                                                    </div>
                                                                </div>
                                                                <?php
                                                                if(!empty($_GET['id'])) {
                                                                $flexible_payment_data = $db->Execute("SELECT * FROM DOA_ENROLLMENT_FLEXIBLE_PAYMENT_DETAILS WHERE PK_ENROLLMENT_MASTER = '$_GET[id]'");
                                                                while (!$flexible_payment_data->EOF) { ?>
                                                                    <div class="row">
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control datepicker-future" value="<?=($flexible_payment_data->fields['PAYMENT_DATE'])?date('m/d/Y', strtotime($flexible_payment_data->fields['PAYMENT_DATE'])):''?>">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control FLEXIBLE_PAYMENT_AMOUNT" value="<?=$flexible_payment_data->fields['AMOUNT']?>">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-3" style="padding-top: 5px;">
                                                                            <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                        </div>
                                                                    </div>
                                                                <?php $flexible_payment_data->MoveNext(); } ?>
                                                                <?php } else { ?>
                                                                    <div class="row">
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control datepicker-future">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <div class="col-md-12">
                                                                                    <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control FLEXIBLE_PAYMENT_AMOUNT">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-3" style="padding-top: 5px;">
                                                                            <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                        </div>
                                                                    </div>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                    </div>


                                                    <?php if($PK_ENROLLMENT_BILLING == '') {?>
                                                        <div class="form-group">
                                                            <a class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: none;" onclick="$('#enrollment_link')[0].click();">Back</a>
                                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: none;">Save & Continue</button>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane" id="ledger" role="tabpanel">
                                    <div class="p-20">
                                        <div class="row">
                                            <h4><b>Billing Details</b></h4>
                                            <table id="myTable" class="table table-striped border">
                                                <thead>
                                                    <tr>
                                                        <th>Due Date</th>
                                                        <th>Transaction Type</th>
                                                        <th>Billed Amount</th>
                                                        <th>Paid Amount</th>
                                                        <th>Balance</th>
                                                        <th>Payment Type</th>
                                                        <th>Description</th>
                                                        <th>Paid</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                <?php
                                                $billed_amount = 0;
                                                $paid_amount = 0;
                                                $balance = 0;
                                                $billing_details = $db->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM `DOA_ENROLLMENT_LEDGER` LEFT JOIN DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_LEDGER.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE PK_ENROLLMENT_MASTER = '$_GET[id]' AND ENROLLMENT_LEDGER_PARENT = 0 ORDER BY DUE_DATE ASC, PK_ENROLLMENT_LEDGER ASC");
                                                while (!$billing_details->EOF) { $billed_amount = $billing_details->fields['BILLED_AMOUNT']; $balance = ($billing_details->fields['BILLED_AMOUNT'] + $balance); ?>
                                                    <tr>
                                                        <td><?=date('m/d/Y', strtotime($billing_details->fields['DUE_DATE']))?></td>
                                                        <td><?=$billing_details->fields['TRANSACTION_TYPE']?></td>
                                                        <td><?=$billing_details->fields['BILLED_AMOUNT']?></td>
                                                        <td></td>
                                                        <td><?=number_format((float)$balance, 2, '.', '')?></td>
                                                        <td><?=$billing_details->fields['PAYMENT_TYPE']?></td>
                                                        <td></td>
                                                        <td><?=(($billing_details->fields['TRANSACTION_TYPE']=='Billing')?(($billing_details->fields['IS_PAID']==1)?'YES':'NO'):'')?></td>

                                                        <td>
                                                            <?php if($billing_details->fields['IS_PAID'] == 0 && $billing_details->fields['STATUS'] == 'A') { ?>
                                                            <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white myBtn" onclick="payNow(<?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>, <?=$billing_details->fields['BILLED_AMOUNT']?>);">Pay Now</a>
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
                                                            <td><?=number_format((float)$balance, 2, '.', '')?></td>
                                                            <td><?=$payment_details->fields['PAYMENT_TYPE']?></td>
                                                            <td></td>
                                                            <td><?=(($payment_details->fields['TRANSACTION_TYPE']=='Billing')?(($payment_details->fields['IS_PAID']==1)?'YES':'NO'):'')?></td>
                                                            <td>
                                                            </td>
                                                        </tr>
                                                    <? } ?>
                                                    <?php $billing_details->MoveNext(); } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($_GET['id'])) { ?>
                                <div class="tab-pane" id="history" role="tabpanel">
                                    <div class="p-20">
                                        <div class="row">
                                            <table id="myTable" class="table table-striped border">
                                                <thead>
                                                    <tr>
                                                        <th>Field Name</th>
                                                        <th>From</th>
                                                        <th>To</th>
                                                        <th>Update By</th>
                                                        <th>Time</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                $row = $db->Execute("SELECT DOA_UPDATE_HISTORY.*, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME FROM DOA_UPDATE_HISTORY INNER JOIN DOA_USERS ON DOA_UPDATE_HISTORY.EDITED_BY = DOA_USERS.PK_USER WHERE DOA_UPDATE_HISTORY.CLASS = 'enrollment' AND DOA_UPDATE_HISTORY.PRIMARY_KEY = ".$_GET['id']." ORDER BY DOA_UPDATE_HISTORY.PK_UPDATE_HISTORY DESC");
                                                while (!$row->EOF) { ?>
                                                    <tr>
                                                        <td><?=$row->fields['FIELD_NAME']?></td>
                                                        <td><?=$row->fields['FROM_VALUE']?></td>
                                                        <td><?=$row->fields['TO_VALUE']?></td>
                                                        <td><?=$row->fields['FIRST_NAME']." ".$row->fields['LAST_NAME']?></td>
                                                        <td><?=$row->fields['EDITED_ON']?></td>
                                                    </tr>
                                                <?php $row->MoveNext(); } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>

                                <!--Payment Model-->
                                <div id="myModal" class="modal">
                                    <!-- Modal content -->
                                    <div class="modal-content" style="width: 50%;">
                                        <span class="close" style="margin-left: 96%;">&times;</span>
                                        <div class="card" id="payment_confirmation_form_div" style="display: none;">
                                            <div class="card-body">
                                                <h4><b>Payment</b></h4>

                                                <form id="payment_confirmation_form" role="form" action="" method="post">
                                                    <input type="hidden" name="sourceId" id="sourceId" >
                                                    <input type="hidden" name="FUNCTION_NAME" value="confirmEnrollmentPayment">
                                                    <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER" value="<?=(empty($_GET['id']))?'':$_GET['id']?>">
                                                    <input type="hidden" name="PK_ENROLLMENT_BILLING" class="PK_ENROLLMENT_BILLING" value="<?=$PK_ENROLLMENT_BILLING?>">
                                                    <input type="hidden" name="PK_ENROLLMENT_LEDGER" class="PK_ENROLLMENT_LEDGER">
                                                    <input type="hidden" name="PAYMENT_GATEWAY" id="PAYMENT_GATEWAY" value="<?=$PAYMENT_GATEWAY?>">
                                                    <input type="hidden" name="PK_USER_MASTER" id="PK_USER_MASTER" value="<?=$PK_USER_MASTER?>">

                                                    <div class="p-20">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Amount</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="AMOUNT" id="AMOUNT_TO_PAY" value="<?=$AMOUNT?>" class="form-control" readonly>
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
                                                                    <div id="wallet_balance_div">

                                                                    </div>
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
                                                        <?php } elseif ($PAYMENT_GATEWAY == 'Square') { ?>
                                                            <div class="row payment_type_div" id="credit_card_payment" style="display: none;">
                                                                <div class="col-12">
                                                                    <div class="form-group" id="card-container">

                                                                    </div>
                                                                </div>
                                                                <div id="payment-status-container"></div>
                                                            </div>
                                                        <?php } elseif ($PAYMENT_GATEWAY == 'Authorized.net'){?>
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
                                                                        <label class="form-label">Email (For receiving payment confirmation mail)</label>
                                                                        <div class="col-md-12">
                                                                            <input type="email" name="EMAIL" id="EMAIL" class="form-control">
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
                                                                <div class="col-4">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Expiration Month</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="EXPIRATION_MONTH" id="EXPIRATION_MONTH" class="form-control" >
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-4">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Expiration Year</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="EXPIRATION_YEAR" id="EXPIRATION_YEAR" class="form-control" >
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-4">
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
                                                            <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Process</button>
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

<?php require_once('../includes/footer.php');?>

<!-- jQuery Modal -->
<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

<script src="https://js.stripe.com/v3/"></script>

<script>
    // Get the modal
    var modal = document.getElementById("myModal");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // When the user clicks the button, open the modal
    function openModel() {
        $('#PK_PAYMENT_TYPE').val('');
        $('.payment_type_div').slideUp();
        $('#wallet_balance_div').slideUp();
        $('#remaining_amount_div').slideUp();
        $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
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

<script type="text/javascript" src="<?=$URL?>"></script>
<script>
    const appId = '<?=$SQUARE_APP_ID ?>';
    const locationId = '<?=$SQUARE_LOCATION_ID ?>';

    async function initializeCard(payments) {
        const card = await payments.card();
        await card.attach('#card-container');

        return card;
    }

    async function createPayment(token) {
        document.getElementById('sourceId').value = token;
        $('#payment_confirmation_form').submit();

        /*const body = JSON.stringify({
          locationId,
          sourceId: token,
        });

        const paymentResponse = await fetch('payment.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body,
        });

        if (paymentResponse.ok) {
          return paymentResponse.json();
        }

        const errorBody = await paymentResponse.text();
        throw new Error(errorBody);*/

        token
    }

    async function tokenize(paymentMethod) {
        const tokenResult = await paymentMethod.tokenize();
        if (tokenResult.status === 'OK') {
            return tokenResult.token;
        } else {
            let errorMessage = `Tokenization failed with status: ${tokenResult.status}`;
            if (tokenResult.errors) {
                errorMessage += ` and errors: ${JSON.stringify(
                    tokenResult.errors
                )}`;
            }

            throw new Error(errorMessage);
        }
    }

    // status is either SUCCESS or FAILURE;
    function displayPaymentResults(status) {
        const statusContainer = document.getElementById(
            'payment-status-container'
        );
        if (status === 'SUCCESS') {
            statusContainer.classList.remove('is-failure');
            statusContainer.classList.add('is-success');
        } else {
            statusContainer.classList.remove('is-success');
            statusContainer.classList.add('is-failure');
        }

        statusContainer.style.visibility = 'visible';
    }

    document.addEventListener('DOMContentLoaded', async function () {
        if (!window.Square) {
            throw new Error('Square.js failed to load properly');
        }

        let payments;
        try {
            payments = window.Square.payments(appId, locationId);
        } catch {
            const statusContainer = document.getElementById(
                'payment-status-container'
            );
            statusContainer.className = 'missing-credentials';
            statusContainer.style.visibility = 'visible';
            return;
        }

        let card;
        try {
            card = await initializeCard(payments);
        } catch (e) {
            console.error('Initializing Card failed', e);
            return;
        }

        // Checkpoint 2.
        async function handlePaymentMethodSubmission(event, paymentMethod) {
            event.preventDefault();

            try {
                // disable the submit button as we await tokenization and make a payment request.
                cardButton.disabled = true;
                const token = await tokenize(paymentMethod);
                const paymentResults = await createPayment(token);
                displayPaymentResults('SUCCESS');

                console.debug('Payment Success', paymentResults);
            } catch (e) {
                cardButton.disabled = false;
                displayPaymentResults('FAILURE');
                console.error(e.message);
            }
        }

        const cardButton = document.getElementById('card-button');
        cardButton.addEventListener('click', async function (event) {
            await handlePaymentMethodSubmission(event, card);
        });
    });
</script>

<script>
    let PK_ENROLLMENT_MASTER = parseInt(<?=empty($_GET['id'])?0:$_GET['id']?>);
    var PK_SERVICE_CLASS = parseInt(<?=empty($PK_SERVICE_CLASS)?0:$PK_SERVICE_CLASS?>);
    if (PK_ENROLLMENT_MASTER > 0){
        selectThisService($('.PK_SERVICE_MASTER'));
    }

    $('#PK_USER_MASTER').SumoSelect({placeholder: 'Select Customer', search: true, searchText: 'Search...'});

    $('.datepicker-future').datepicker({
        format: 'mm/dd/yyyy',
        minDate: 0
    });

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });


    function selectThisCustomer(param){
        let location_id = $(param).find(':selected').data('location_id');
        $('#PK_LOCATION').val(location_id);
    }

    function addMoreServices() {
        $('#append_service_div').append(`<div class="row">
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                        <option>Select</option>
                                                        <?php
                                                        $row = $db->Execute("SELECT PK_SERVICE_MASTER, SERVICE_NAME, PK_SERVICE_CLASS FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY SERVICE_NAME");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" data-service_class="<?=$row->fields['PK_SERVICE_CLASS']?>"><?=$row->fields['SERVICE_NAME']?></option>
                                                        <?php $row->MoveNext(); } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                        <option value="">Select</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" >
                                                </div>
                                            </div>
                                            <div class="col-4 frequency_div" style="display: none;">
                                                <div class="form-group">
                                                    <input type="text" class="form-control FREQUENCY" name="FREQUENCY[]" readonly>
                                                </div>
                                            </div>
                                            <div class="col-2 session_div">
                                                <div class="form-group">
                                                    <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)">
                                                </div>
                                            </div>
                                            <div class="col-2 session_div">
                                                <div class="form-group">
                                                    <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);">
                                                </div>
                                            </div>
                                            <div class="col-1" style="width: 11%;">
                                                <div class="form-group">
                                                    <input type="text" class="form-control TOTAL" name="TOTAL[]">
                                                </div>
                                            </div>
                                            <div class="col-1" style="width: 5%;">
                                                <div class="form-group">
                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>`);
    }

    function removeThis(param) {
        $(param).closest('.row').remove();
    }

    function addMorePayments(){
        let total_bill = parseFloat(($('#total_bill').val())?$('#total_bill').val():0);
        let down_payment = parseFloat(($('#DOWN_PAYMENT').val())?$('#DOWN_PAYMENT').val():0);
        let total_flexible_payment = 0;
        $('.FLEXIBLE_PAYMENT_AMOUNT').each(function () {
            total_flexible_payment += parseFloat($(this).val());
        });
        if ((total_flexible_payment+down_payment) < total_bill) {
            $('#flexible_plans_div').append(`<div class="row">
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control datepicker-future">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control FLEXIBLE_PAYMENT_AMOUNT">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3" style="padding-top: 5px;">
                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                        </div>`);
            $('.datepicker-future').datepicker({
                format: 'mm/dd/yyyy',
                minDate: 0
            });
        }else {
            alert('Total Bill Amount Exceed');
        }
    }

    function selectThisService(param) {
        let PK_SERVICE_MASTER = $(param).val();
        PK_SERVICE_CLASS = $(param).find(':selected').data('service_class');
        let SERVICE_CODE = ($(param).find(':selected').data('service_code'))?$(param).find(':selected').data('service_code'):0;
        $('.PK_SERVICE_CLASS').val(PK_SERVICE_CLASS);
        if (PK_SERVICE_CLASS === 1){
            $('.session_div').hide();
            $('.frequency_div').show();
        }else {
            if (PK_SERVICE_CLASS === 2){
                $('.session_div').show();
                $('.frequency_div').hide();
            }
        }

        if (SERVICE_CODE == 0) {
            $.ajax({
                url: "ajax/get_service_codes.php",
                type: "POST",
                data: {PK_SERVICE_MASTER: PK_SERVICE_MASTER, SERVICE_CODE: SERVICE_CODE},
                async: false,
                cache: false,
                success: function (result) {
                    $(param).closest('.row').find('.PK_SERVICE_CODE').empty();
                    $(param).closest('.row').find('.PK_SERVICE_CODE').append(result);
                }
            });
        }
    }

    function selectThisServiceCode(param) {
        let service_details = $(param).find(':selected').data('service_details');
        let price = $(param).find(':selected').data('price');
        let frequency = $(param).find(':selected').data('frequency');
        $(param).closest('.row').find('.SERVICE_DETAILS').val(service_details);
        $(param).closest('.row').find('.PRICE_PER_SESSION').val(price);
        $(param).closest('.row').find('.FREQUENCY').val(frequency);
        PK_SERVICE_CLASS = $(param).closest('.row').find('.PK_SERVICE_MASTER').find(':selected').data('service_class');
        if (PK_SERVICE_CLASS === 1) {
            $(param).closest('.row').find('.NUMBER_OF_SESSION').val(1);
        }
        calculateServiceTotal(param);
    }

    function calculateServiceTotal(param) {
        let number_of_session = $(param).closest('.row').find('.NUMBER_OF_SESSION').val();
        number_of_session = (number_of_session)?number_of_session:0;
        let service_price = $(param).closest('.row').find('.PRICE_PER_SESSION').val();
        service_price = (service_price)?service_price:0;
        $(param).closest('.row').find('.TOTAL').val(parseFloat(parseFloat(service_price)*parseFloat(number_of_session)).toFixed(2));
    }

    $(document).on('click', '#cancel_button', function () {
        window.location.href='all_enrollments.php'
    });

    $(document).on('submit', '#enrollment_form', function (event) {
        event.preventDefault();
        let form_data = $('#enrollment_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            dataType: 'json',
            success:function (data) {
                $('.PK_ENROLLMENT_MASTER').val(data.PK_ENROLLMENT_MASTER);
                $('#MEMBERSHIP_PAYMENT_AMOUNT').val(parseFloat(data.TOTAL_AMOUNT).toFixed(2));
                $('#billing_link')[0].click();
            }
        });
    });

    function goToPaymentTab() {
        let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
        if (PK_ENROLLMENT_MASTER) {
            $.ajax({
                url: "ajax/show_payment_tab.php",
                type: 'POST',
                data: {PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER, PK_SERVICE_CLASS:PK_SERVICE_CLASS},
                success: function (data) {
                    $('#payment_tab_div').html(data);
                    calculatePayment();
                }
            });
        }else{
            alert('Please fill up the enrollment form first');
            $('#enrollment_link')[0].click();
        }
    }

    function goToLedgerTab() {
        let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
        if (!PK_ENROLLMENT_MASTER) {
            alert('Please fill up the enrollment form first');
            $('#enrollment_link')[0].click();
        }
    }

    function calculateDiscount(param) {
        let DISCOUNT = $(param).closest('.row').find('.DISCOUNT').val();
        let DISCOUNT_TYPE = $(param).closest('.row').find('.DISCOUNT_TYPE').val();
        let TOTAL = $(param).closest('.row').find('.TOTAL').val();

        if (DISCOUNT_TYPE == 1){
            let FINAL_AMOUNT = parseFloat(TOTAL-DISCOUNT);
            $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));
        } else {
            if (DISCOUNT_TYPE == 2) {
                let FINAL_AMOUNT = parseFloat(TOTAL - (TOTAL * (DISCOUNT / 100)));
                $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));
            }
        }
        let TOTAL_AMOUNT = 0;
        $('.FINAL_AMOUNT').each(function () {
            TOTAL_AMOUNT += parseFloat($(this).val());
        });
        $('#total_bill').val(parseFloat(TOTAL_AMOUNT).toFixed(2));
        $('#BALANCE_PAYABLE').val(parseFloat(TOTAL_AMOUNT).toFixed(2));
    }

    function calculatePayment() {
        let total_bill = parseFloat(($('#total_bill').val())?$('#total_bill').val():0);
        let down_payment = parseFloat(($('#DOWN_PAYMENT').val())?$('#DOWN_PAYMENT').val():0);
        let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val())?$('#BALANCE_PAYABLE').val():0);
        $('#BALANCE_PAYABLE').val(parseFloat(total_bill-down_payment).toFixed(2));
        calculatePaymentPlans();
    }

    $(document).on('change', '.PAYMENT_METHOD', function () {
        $('.payment_method_div').slideUp();
        $('#down_payment_div').slideDown();
        $('#FIRST_DUE_DATE').prop('required', false);
        if ($(this).val() == 'One Time'){
            let total_bill = parseFloat(($('#total_bill').val())?$('#total_bill').val():0);
            $('#DOWN_PAYMENT').val(0.00);
            $('#BALANCE_PAYABLE').val(total_bill.toFixed(2));
            $('#down_payment_div').slideUp();
            $('#AMOUNT_TO_PAY').val(total_bill.toFixed(2));
            $('#payment_confirmation_form_div').slideDown();
            openModel();
        }
        if ($(this).val() == 'Payment Plans'){
            $('#FIRST_DUE_DATE').prop('required', true);
            $('#payment_plans_div').slideDown();
        }
        if ($(this).val() == 'Flexible Payments'){
            $('#flexible_plans_div').slideDown();
            let total_bill = parseFloat(($('#total_bill').val())?$('#total_bill').val():0);
            $('#DOWN_PAYMENT').val(0.00);
            $('#BALANCE_PAYABLE').val(total_bill.toFixed(2));
            $('#down_payment_div').slideUp();
            $('#AMOUNT_TO_PAY').val(total_bill.toFixed(2));
            $('#payment_confirmation_form_div').slideDown();
            //openModel();
        }
    });

    function calculatePaymentPlans() {
        let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val())?$('#BALANCE_PAYABLE').val():0);
        let NUMBER_OF_PAYMENT = parseInt(($('#NUMBER_OF_PAYMENT').val())?$('#NUMBER_OF_PAYMENT').val():1);
        $('#INSTALLMENT_AMOUNT').val(parseFloat(balance_payable/NUMBER_OF_PAYMENT).toFixed(2));
    }

    function calculateNumberOfPayment(param) {
        let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val())?$('#BALANCE_PAYABLE').val():0);
        let entered_amount = $(param).val();
        let number_of_payment = balance_payable/entered_amount;
        $('#NUMBER_OF_PAYMENT').val(number_of_payment);
        if (Number.isInteger(number_of_payment)) {
            $('#number_of_payment_error').hide();
        }else {
            $('#number_of_payment_error').show();
        }
    }

    $(document).on('submit', '#billing_form', function (event) {
        event.preventDefault();
        let total_bill = parseFloat(($('#total_bill').val())?$('#total_bill').val():0);
        let down_payment = parseFloat(($('#DOWN_PAYMENT').val())?$('#DOWN_PAYMENT').val():0);
        let total_flexible_payment = 0;
        $('.FLEXIBLE_PAYMENT_AMOUNT').each(function () {
            total_flexible_payment += parseFloat($(this).val());
        });
        total_flexible_payment = isNaN(total_flexible_payment)?0:total_flexible_payment;
        if ((total_flexible_payment+down_payment) <= total_bill) {
            let number_of_payment = $('#NUMBER_OF_PAYMENT').val();
            if (Number.isInteger(Number(number_of_payment))) {
                let form_data = $('#billing_form').serialize();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: form_data,
                    dataType: 'json',
                    success: function (data) {
                        $('.PK_ENROLLMENT_BILLING').val(data.PK_ENROLLMENT_BILLING);
                        $('.PK_ENROLLMENT_LEDGER').val(data.PK_ENROLLMENT_LEDGER);
                        let today = new Date();
                        let firstPaymentDate = new Date($('#FIRST_DUE_DATE').val());

                        if (PK_SERVICE_CLASS == 1) {
                            let paymentDate = new Date($('#MEMBERSHIP_PAYMENT_DATE').val());
                            if ((today.getDate() + '/' + today.getMonth() === paymentDate.getDate() + '/' + paymentDate.getMonth())) {
                                let balance_payable = parseFloat(($('#MEMBERSHIP_PAYMENT_AMOUNT').val()) ? $('#MEMBERSHIP_PAYMENT_AMOUNT').val() : 0);
                                $('#AMOUNT_TO_PAY').val(balance_payable.toFixed(2));
                                $('#payment_confirmation_form_div').slideDown();
                            } else {
                                window.location.href = 'all_enrollments.php';
                            }
                        } else {
                            if (($('.PAYMENT_METHOD:checked').val() === 'One Time') || (parseFloat($('#DOWN_PAYMENT').val()) > 0) || ($('.PAYMENT_METHOD:checked').val() === 'Payment Plans' && (today.getDate() + '/' + today.getMonth() === firstPaymentDate.getDate() + '/' + firstPaymentDate.getMonth()))) {
                                if ($('.PAYMENT_METHOD:checked').val() === 'One Time') {
                                    let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
                                    $('#AMOUNT_TO_PAY').val(balance_payable.toFixed(2));
                                } else {
                                    if (parseFloat($('#DOWN_PAYMENT').val()) > 0) {
                                        let down_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
                                        $('#AMOUNT_TO_PAY').val(down_payment.toFixed(2));
                                    } else {
                                        if ($('.PAYMENT_METHOD:checked').val() === 'Payment Plans' && (today.getDate() + '/' + today.getMonth() === firstPaymentDate.getDate() + '/' + firstPaymentDate.getMonth())) {
                                            let installment_amount = parseFloat(($('#INSTALLMENT_AMOUNT').val()) ? $('#INSTALLMENT_AMOUNT').val() : 0);
                                            $('#AMOUNT_TO_PAY').val(installment_amount.toFixed(2));
                                        }
                                    }
                                }
                                $('#payment_confirmation_form_div').slideDown();
                                openModel();

                            } else {
                                window.location.href = 'all_enrollments.php';
                            }
                        }
                    }
                });
            } else {
                $('#number_of_payment_error').slideUp();
                $('#number_of_payment_error').slideDown();
            }
        }else {
            alert('Total Bill Amount Exceed');
        }
    });

    /*$(document).on('submit', '#payment_confirmation_form', function (event) {
        event.preventDefault();
        let form_data = $('#payment_confirmation_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            success:function (data) {
                //window.location.href='all_enrollments.php';
            }
        });
    });*/

    function payNow(PK_ENROLLMENT_LEDGER, BILLED_AMOUNT) {
        $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
        $('#payment_confirmation_form_div').slideDown();
        openModel();
    }

    function selectPaymentType(param){
        let paymentType = $("#PK_PAYMENT_TYPE option:selected").text();
        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        $('.payment_type_div').slideUp();
        $('#card-element').remove();
        switch (paymentType) {
            case 'Credit Card':
                if (PAYMENT_GATEWAY == 'Stripe') {
                    $('#card_div').html(`<div id="card-element"></div>`);
                    stripePaymentFunction();
                }
                $('#credit_card_payment').slideDown();
                break;

            case 'Check':
                $('#check_payment').slideDown();
                break;

            case 'Wallet':
                let PK_USER_MASTER = $('#PK_USER_MASTER').val();
                $.ajax({
                    url: "ajax/wallet_balance.php",
                    type: 'POST',
                    data: {PK_USER_MASTER: PK_USER_MASTER},
                    success: function (data) {
                        $('#wallet_balance_div').html(data);
                        $('#wallet_balance_div').slideDown();

                        let AMOUNT_TO_PAY = parseFloat($('#AMOUNT_TO_PAY').val());
                        let WALLET_BALANCE = parseFloat($('#WALLET_BALANCE').val());

                        if (AMOUNT_TO_PAY > WALLET_BALANCE) {
                            $('#REMAINING_AMOUNT').val(AMOUNT_TO_PAY - WALLET_BALANCE);
                            $('#remaining_amount_div').slideDown();
                            $('#PK_PAYMENT_TYPE_REMAINING').prop('required', true);
                        } else {
                            $('#remaining_amount_div').slideUp();
                            $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
                        }
                    }
                });
                break;

            case 'Cash':
            default:
                $('.payment_type_div').slideUp();
                $('#wallet_balance_div').slideUp();
                $('#remaining_amount_div').slideUp();
                $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
                break;
        }
    }

    function selectRemainingPaymentType(param){
        let paymentType = $("#PK_PAYMENT_TYPE_REMAINING option:selected").text();
        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        $('.remaining_payment_type_div').slideUp();
        $('#card-element').remove();
        switch (paymentType) {
            case 'Credit Card':
                if (PAYMENT_GATEWAY == 'Stripe') {
                    $('#card_div').html(`<div id="card-element"></div>`);
                    stripePaymentFunction();
                }
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