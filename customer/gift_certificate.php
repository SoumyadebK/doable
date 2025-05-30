<?php
require_once('../global/config.php');

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 4 ) {
    header("location:../login.php");
    exit;
}

if (empty($_GET['id'])) {
    $title = "Add Gift Certificate";
} else {
    $title = "Edit Gift Certificate";
}

$PAYMENT_GATEWAY = '';
$PK_PAYMENT_TYPE = '';
$NAME = '';
$CARD_NUMBER = '';
$SECURITY_CODE = '';
$EXPIRATION_DATE = '';
$CHECK_NUMBER = '';
$CHECK_DATE = '';
$PAYMENT_INFO = '';

$row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.PK_USER = ".$_SESSION['PK_USER']." AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY DOA_USERS.FIRST_NAME");
$PK_USER_MASTER = $row->fields['PK_USER_MASTER'];

if (empty($_GET['id'])) {
    $PK_GIFT_CERTIFICATE_SETUP ='';
    $DATE_OF_PURCHASE = '';
    $GIFT_NOTE = '';
    $AMOUNT = '';
    $ACTIVE = '';
} else {
    $res = $db_account->Execute("SELECT * FROM DOA_GIFT_CERTIFICATE_MASTER WHERE PK_GIFT_CERTIFICATE_MASTER = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_gift_certificates.php");
        exit;
    }
    $PK_GIFT_CERTIFICATE_SETUP = $res->fields['PK_GIFT_CERTIFICATE_SETUP'];
    $DATE_OF_PURCHASE = $res->fields['DATE_OF_PURCHASE'];
    $GIFT_NOTE = $res->fields['GIFT_NOTE'];
    $AMOUNT = $res->fields['AMOUNT'];
    $ACTIVE = $res->fields['ACTIVE'];
}

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use Square\SquareClient;
use Square\Environment;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\StripeClient;

$user_payment_gateway = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER, DOA_LOCATION.PAYMENT_GATEWAY_TYPE, DOA_LOCATION.SECRET_KEY, DOA_LOCATION.PUBLISHABLE_KEY, DOA_LOCATION.ACCESS_TOKEN, DOA_LOCATION.APP_ID, DOA_LOCATION.LOCATION_ID, DOA_LOCATION.LOGIN_ID, DOA_LOCATION.TRANSACTION_KEY, DOA_LOCATION.AUTHORIZE_CLIENT_KEY FROM DOA_LOCATION INNER JOIN DOA_USER_MASTER ON DOA_LOCATION.PK_LOCATION = DOA_USER_MASTER.PRIMARY_LOCATION_ID WHERE DOA_USER_MASTER.PK_USER_MASTER = '$PK_USER_MASTER'");
if($user_payment_gateway->RecordCount() > 0){
    $PAYMENT_GATEWAY = $user_payment_gateway->fields['PAYMENT_GATEWAY_TYPE'];
    $SQUARE_APP_ID = $user_payment_gateway->fields['APP_ID'];
    $SQUARE_LOCATION_ID = $user_payment_gateway->fields['LOCATION_ID'];
    $ACCESS_TOKEN = $user_payment_gateway->fields['ACCESS_TOKEN'];
    $PUBLISHABLE_KEY = $user_payment_gateway->fields['PUBLISHABLE_KEY'];
    $SECRET_KEY = $user_payment_gateway->fields['SECRET_KEY'];
    $LOGIN_ID = $user_payment_gateway->fields['LOGIN_ID'];
    $TRANSACTION_KEY = $user_payment_gateway->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY = $user_payment_gateway->fields['AUTHORIZE_CLIENT_KEY'];
} else {
    $account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
    $PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];
    $SQUARE_APP_ID 			= $account_data->fields['APP_ID'];
    $SQUARE_LOCATION_ID 	= $account_data->fields['LOCATION_ID'];
    $ACCESS_TOKEN 			= $account_data->fields['ACCESS_TOKEN'];
    $PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];
    $SECRET_KEY = $account_data->fields['SECRET_KEY'];
    $LOGIN_ID = $account_data->fields['LOGIN_ID'];
    $TRANSACTION_KEY = $account_data->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY = $account_data->fields['AUTHORIZE_CLIENT_KEY'];
}

$SQUARE_MODE 			= 2;
if ($SQUARE_MODE == 1)
    $SQ_URL = "https://connect.squareup.com";
else if ($SQUARE_MODE == 2)
    $SQ_URL = "https://connect.squareupsandbox.com";

if ($SQUARE_MODE == 1)
    $URL = "https://web.squarecdn.com/v1/square.js";
else if ($SQUARE_MODE == 2)
    $URL = "https://sandbox.web.squarecdn.com/v1/square.js";

if(!empty($_POST)){
    if ($_POST['PK_PAYMENT_TYPE'] == 1) {
        if ($_POST['PAYMENT_GATEWAY'] == 'Stripe') {
            require_once("../global/stripe-php-master/init.php");
            $stripe = new StripeClient($SECRET_KEY);
            $STRIPE_TOKEN = $_POST['token'];

            /*Check the user is already a stripe user or not*/
            $user_payment_info_data = $db_account->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PAYMENT_TYPE = 'Stripe' AND PK_USER_MASTER = ".$PK_USER_MASTER);
            if ($user_payment_info_data->RecordCount() > 0) {
                $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];
            } else {
                $user_master = $db_account->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE PK_USER_MASTER = ".$PK_USER_MASTER);
                try {
                    $customer = $stripe->customers->create([
                        'email' => $user_master->fields['EMAIL_ID'],
                        'name' => $user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME'],
                        'phone' => $user_master->fields['PHONE'],
                        'description' => $user_master->fields['PK_USER'],
                    ]);
                } catch (ApiErrorException $e) {
                    pre_r($e->getMessage());
                }

                $CUSTOMER_PAYMENT_ID = $customer->id;
                $STRIPE_DETAILS['PK_USER'] = $user_master->fields['PK_USER'];
                $STRIPE_DETAILS['CUSTOMER_PAYMENT_ID'] = $CUSTOMER_PAYMENT_ID;
                $STRIPE_DETAILS['PAYMENT_TYPE'] = 'Stripe';
                $STRIPE_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_CUSTOMER_PAYMENT_INFO', $STRIPE_DETAILS, 'insert');
            }

            $PAYMENT_METHOD_ID = '';
            if (isset($_POST['PAYMENT_METHOD_ID'])) {
                $PAYMENT_METHOD_ID = $_POST['PAYMENT_METHOD_ID'];
            } else {
                try {
                    $payment_method = $stripe->paymentMethods->create([
                        'type' => 'card',
                        'card' => [
                            'token' => $STRIPE_TOKEN
                        ],
                    ]);
                    $stripe->paymentMethods->attach(
                        $payment_method->id,
                        ['customer' => $CUSTOMER_PAYMENT_ID]
                    );
                    $PAYMENT_METHOD_ID = $payment_method->id;
                } catch (ApiErrorException $e) {
                    pre_r($e->getMessage());
                }
            }

            $AMOUNT = $_POST['AMOUNT'] * 100;
            try {
                Stripe::setApiKey($SECRET_KEY);
                $payment_intent = PaymentIntent::create([
                    'amount' => $AMOUNT,
                    'currency' => 'USD',
                    'customer' => $CUSTOMER_PAYMENT_ID,
                    'payment_method' => $PAYMENT_METHOD_ID,
                ]);
            } catch (Exception $e) {
                pre_r($e->getMessage());
            }

            $PAYMENT_INFO = $payment_intent->id;

        } elseif ($_POST['PAYMENT_GATEWAY'] == 'Square') {

            require_once("../global/vendor/autoload.php");

            $AMOUNT = $_POST['AMOUNT'];

            $client = new SquareClient([
                'accessToken' => $ACCESS_TOKEN,
                'environment' => Environment::SANDBOX,
            ]);

            $user_payment_info_data = $db_account->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PAYMENT_TYPE = 'Square' AND PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
            if ($user_payment_info_data->RecordCount() > 0) {
                $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];
            } else {
                $user_master = $db_account->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

                $address = new \Square\Models\Address();
                $address->setAddressLine1('500 Electric Ave');
                $address->setAddressLine2('Suite 600');
                $address->setLocality('New York');
                $address->setAdministrativeDistrictLevel1('NY');
                $address->setPostalCode('10003');
                $address->setCountry('US');

                $body = new \Square\Models\CreateCustomerRequest();
                $body->setGivenName($user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME']);
                $body->setFamilyName('Earhart');
                $body->setEmailAddress($user_master->fields['EMAIL_ID']);
                $body->setAddress($address);
                $body->setPhoneNumber($user_master->fields['PHONE']);
                $body->setReferenceId('YOUR_REFERENCE_ID');
                $body->setNote('a customer');

                try {
                    $api_response = $client->getCustomersApi()->createCustomer($body);
                } catch (\Square\Exceptions\ApiException $e) {
                    pre_r($e->getMessage());
                }

                $CUSTOMER_PAYMENT_ID = json_decode($api_response->getBody())->customer->id;
                $SQUARE_DETAILS['PK_USER'] = $user_master->fields['PK_USER'];
                $SQUARE_DETAILS['CUSTOMER_PAYMENT_ID'] = $CUSTOMER_PAYMENT_ID;
                $SQUARE_DETAILS['PAYMENT_TYPE'] = 'Square';
                $SQUARE_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_CUSTOMER_PAYMENT_INFO', $SQUARE_DETAILS, 'insert');

            }

            $card = new \Square\Models\Card();
            $card->setCardholderName($user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME']);
            //$card->setBillingAddress($billing_address);
            $card->setCustomerId($CUSTOMER_PAYMENT_ID);
            //$card->setReferenceId('user-id-1');

            $body = new \Square\Models\CreateCardRequest(
                uniqid(),
                $_POST['sourceId'],
                $card
            );

            $api_response = $client->getCardsApi()->createCard($body);

            if ($api_response->isSuccess()) {
                $result = $api_response->getResult();
            } else {
                $errors = $api_response->getErrors();
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

            $ANET_ENV = 'SANDBOX';

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
            pre_r($response);
        }
    } elseif ($_POST['PK_PAYMENT_TYPE'] == 7) {
        $AMOUNT = $_POST['AMOUNT'];
        $REMAINING_AMOUNT = $_POST['REMAINING_AMOUNT'];
        $WALLET_BALANCE = $_POST['WALLET_BALANCE'];

        if ($_POST['PK_PAYMENT_TYPE_REMAINING'] == 1) {
            require_once("../global/stripe-php-master/init.php");
            Stripe::setApiKey($_POST['SECRET_KEY']);
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
            if ($charge->paid == 1) {
                $PAYMENT_INFO = $charge->id;
            } else {
                $PAYMENT_INFO = 'Payment Unsuccessful.';
            }
        }

        //$PK_USER_MASTER = $_POST['PK_USER_MASTER'];
        $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID, MISC_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = ".$_POST['PK_ENROLLMENT_MASTER']);
        if(empty($enrollment_data->fields['ENROLLMENT_ID'])) {
            $enrollment_id = $enrollment_data->fields['MISC_ID'];
        } else {
            $enrollment_id = $enrollment_data->fields['ENROLLMENT_ID'];
        }
        $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
        $DEBIT_AMOUNT = ($WALLET_BALANCE > $AMOUNT) ? $AMOUNT : $WALLET_BALANCE;
        if ($wallet_data->RecordCount() > 0) {
            $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] - $DEBIT_AMOUNT;
        }
        $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
        $INSERT_DATA['DEBIT'] = $DEBIT_AMOUNT;
        $INSERT_DATA['DESCRIPTION'] = "Balance debited for payment of enrollment " . $enrollment_id;
        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');

    } else {
        $PAYMENT_INFO = 'Payment Done.';
    }

    $GIFT_CERTIFICATE_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $GIFT_CERTIFICATE_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
    if (empty($_GET['id'])) {
        $GIFT_CERTIFICATE_DATA['PK_GIFT_CERTIFICATE_SETUP'] = $_POST['GIFT_CERTIFICATE'];
        $GIFT_CERTIFICATE_DATA['DATE_OF_PURCHASE'] = date('Y-m-d', strtotime($_POST['DATE_OF_PURCHASE']));
        $GIFT_CERTIFICATE_DATA['GIFT_NOTE'] = $_POST['GIFT_NOTE'];
        $GIFT_CERTIFICATE_DATA['AMOUNT'] = $_POST['AMOUNT'];
        $GIFT_CERTIFICATE_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        $GIFT_CERTIFICATE_DATA['CHECK_NUMBER'] = $_POST['CHECK_NUMBER'];
        $GIFT_CERTIFICATE_DATA['CHECK_DATE'] = (!empty($_POST['CHECK_DATE']))?date('Y-m-d', strtotime($_POST['CHECK_DATE'])):'0000-00-00';
        $GIFT_CERTIFICATE_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
        $GIFT_CERTIFICATE_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $GIFT_CERTIFICATE_DATA['CREATED_ON'] = date("Y-m-d H:i");
        $GIFT_CERTIFICATE_DATA['ACTIVE'] = 1;
        db_perform_account('DOA_GIFT_CERTIFICATE_MASTER', $GIFT_CERTIFICATE_DATA, 'insert');
    } else {
        $GIFT_CERTIFICATE_DATA['GIFT_NOTE'] = $_POST['GIFT_NOTE'];
        $GIFT_CERTIFICATE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $GIFT_CERTIFICATE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        $GIFT_CERTIFICATE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        db_perform_account('DOA_GIFT_CERTIFICATE_MASTER', $GIFT_CERTIFICATE_DATA, 'update', "PK_GIFT_CERTIFICATE_MASTER = '$_GET[id]'");
    }

    header('location:all_gift_certificates.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=PT+Mono&display=swap" rel="stylesheet">
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

    /*STRIPE_CSS*/

    /* MAIN CREDIT CARD CONTAINER */

    .credit-card {
        margin: auto;
        margin-top: 20px;
        margin-bottom: 20px;
        border-radius: 7px;
        width: 95%;
        max-width: 250px;
        position: relative;
        transition: all 0.4s ease;
        box-shadow: 0 2px 4px 0 #cfd7df;
        min-height: 125px;
        padding: 13px;
        background: linear-gradient(to left, #283593, #1976d2);;
        color: #ffffff;
    }

    .credit-card.selectable:hover {
        cursor: pointer;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19), 0 6px 6px rgba(0, 0, 0, 0.23);
    }


    /*  NUMBER FORMATTING */

    .credit-card-last4 {
        font-family: "PT Mono", Helvetica, sans-serif;
        font-size: 18px;
    }

    .credit-card-last4:before {
        content: "**** **** **** ";
        color: #4f4d4d;
        font-size: 18px;
    }

    .credit-card.american-express .credit-card-last4:before,
    .credit-card.amex .credit-card-last4:before {
        content: "**** ****** *";
        margin-right: -10px;
    }

    .credit-card.diners-club .credit-card-last4:before,
    .credit-card.diners .credit-card-last4:before {
        content: "**** ****** ";
    }

    .credit-card-expiry {
        font-family: "PT Mono", Helvetica, sans-serif;
        font-size: 18px;
        position: absolute;
        bottom: 8px;
        left: 15px;
    }


    /* BRAND CUSTOMIZATION */

    .credit-card.visa {
        background: #4862e2;
        color: #eaeef2;
    }

    .credit-card.visa .credit-card-last4:before {
        color: #8999e5;
    }

    .credit-card.mastercard {
        background: #4f0cd6;
        color: #e3e8ef;
    }

    .credit-card.mastercard .credit-card-last4:before {
        color: #8a82dd;
    }

    .credit-card.american-express,
    .credit-card.amex {
        background: #1cd8b3;
        color: #f2fcfa;
    }

    .credit-card.american-express .credit-card-last4:before,
    .credit-card.amex .credit-card-last4:before {
        color: #99efe0;
    }

    .credit-card.diners, .credit-card.diners-club {
        background: #8a38ff;
        color: #f5efff;
    }

    .credit-card.diners .credit-card-last4:before, .credit-card.diners-club .credit-card-last4:before {
        color: #b284f4;
    }

    .credit-card.discover {
        background: #f16821;
        color: #fff4ef;
    }

    .credit-card.discover .credit-card-last4:before {
        color: #ffae84;
    }

    .credit-card.jcb {
        background: #cc3737;
        color: #f7e8e8;
    }

    .credit-card.jcb .credit-card-last4:before {
        color: #f28a8a;
    }

    .credit-card.unionpay {
        background: #47bfff;
        color: #fafdff;
    }

    .credit-card.unionpay .credit-card-last4:before {
        color: #99dcff;
    }


    /*   LOGOS  */

    .credit-card::after {
        content: " ";
        position: absolute;
        bottom: 10px;
        right: 15px;
    }

    .credit-card.visa::after {
        height: 16px;
        width: 50px;
        background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAQCAYAAABUWyyMAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAACXBIWXMAAC4jAAAuIwF4pT92AAABWWlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS40LjAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyI+CiAgICAgICAgIDx0aWZmOk9yaWVudGF0aW9uPjE8L3RpZmY6T3JpZW50YXRpb24+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgpMwidZAAAExUlEQVRIDXWWW4hWVRSA/9+ZNA3TtFJUZDIsTSNLUpIwHzTogl3oKkVERgQhvQRTINFDUdhDUdBDhBMUTUFRJnSzQglqIC+U3YZEI+wiWjmF42X07/v2WWs4M6MLvn+tvdbal7P23uf8zVar9Vyj0ZgL46EF/0ET2uEPeKzZbO4hbxT6BLoNfRy9klgnHIQzoBf/avzLsZ+APjgTPsO/ttZvBr7VcDXMgingnL/ATniK/G/IH4XdwsZsjcZ2zCXQD863ndgaYqhmq4ExARbDo7AThssGOpnnwHX96bDEpyP+4sn8EbuL2F+1uIURC6NWVkVuO7bFdM5HDAyTf2hPjbiFHyoEn4wOh0P/ip5kFrot9ELsI3AUXMR+mBmxfMB+fMoN4b+papZf+55MnNNdqhdsHL4fItl+xwIffGnklnWVTjjdPu1z4QAoJttxUcQy51mDSD7s+ohPxbe3RKqff1G5sG3hz4fYQvsWWAE3wjrohpwjd+NWfMpApcqva1IeinlLrnYRAnl8NpW0quKad5qA9sCeBbtBycGXRXxZ5R70bwv/PPw+tIXJ4pxn7FRCXq7lQ2zFfgfhEHgKlC77o9tKcm2wbH8ZvuOhL1GXS9VoXI/ZAUfBLd0MW0CZV6nGQGgvrzIOzPVlIlbwcRZwNtqFeB/KTkQ7XyyX014Ojuc9eAksTq7zIvqVl086iBVxEuWLSpXJNedHW3V3zdZczwOeCF85grV4T9jfo78D53NRznMPeNzWoF24960669WicTfuhfQdw+6CPaA454VQ7qaOQWEgn9oKTYH6Wf8x/Avwez5za3dhT4iYVf0alDxyVxpT8F0F+QJw0ZKyFWNO5JXzTnsa7MsEtDvvOGvDl3ftWv1DdsSjg6CafxLbYQLi8ZqFvwN9GziRx0p5nVy/I0oHzNZArOJv0GuDvu3kuZCl4NE4LXB3rPRl8DF508nTp9wO58BhG8jblWp8GzrVgjSGaCfVge4ExR3woq0CP1QpfRgXZGfslRHISn8S44zCb4XKEUGPhvvA3VTcXV8Eyrro4yt3e/FUP7+j8psxA9tvkf2Ud+xTFq1RE8+ekhfeXXNXOsHt13ZRG6leLwONQR+hfSkoxq34YOWIO6HFGYN/gPYr2H5o34UlkCcjXxYr8FnpnMt1vkwftcff8bPPHPxjaQ8VnCY66UTYDYo7kpKVWB55Dmr+hkjIs3tH+H1d+zdkhOB/Ifrk3XnTJHw5lndN6vPbxXb67Dt/xI5E9XyL+BfA89wBWRl3y934Cj4nTlrTo+f/tHJZ0T6YO1TuB3oxdJHjEXCX94PFsuoPgJLVfZ+8DtrX6ETMy1hxxI9+33yu63SYO+JBcCp2dtGb4eaw9eUDvcoDuDO++734s2EmeFEd8+cAVb4t7siDgb4U5/CyO04PY77GmM9gO0Y/jIWPwCLkn1ov//nwMDifhV0II4XBShXQi2C4ePEm2wudx+r+YUme/yL4rbKSR6F+LKpIq/UBxiSYDJ6EulyRY6UmOB7+riX1nGpH8sPohX0LpoMVmghvUDn/i1kJK6r45d4KB8CHfA98UI/A87APLoZpYNyq7oUd0M14G9HmX4f6CfrAMXeB35j6Oh3zEHSD/zg8xn3/A2haarqHiZpPAAAAAElFTkSuQmCC');
    }

    .credit-card.mastercard::after {
        width: 40px;
        height: 25px;
        background-image: url('data:image/png;base64,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');
    }

    .credit-card.amex::after,
    .credit-card.american-express::after {
        width: 50px;
        height: 14px;
        background-image: url('data:image/png;base64,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');
    }

    .credit-card.diners::after,
    .credit-card.diners-club::after {
        width: 30px;
        height: 24px;
        background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAYCAYAAADtaU2/AAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAACXBIWXMAAAsTAAALEwEAmpwYAAAED2lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS40LjAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgICAgICAgICAgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiCiAgICAgICAgICAgIHhtbG5zOnRpZmY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vdGlmZi8xLjAvIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iPgogICAgICAgICA8eG1wTU06RG9jdW1lbnRJRD54bXAuZGlkOkFDMEM4Rjk2NTQzRDExRTQ5MzZBQzlERDRCNDEwQzZDPC94bXBNTTpEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06RGVyaXZlZEZyb20gcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICA8c3RSZWY6aW5zdGFuY2VJRD54bXAuaWlkOkFDMEM4RjkzNTQzRDExRTQ5MzZBQzlERDRCNDEwQzZDPC9zdFJlZjppbnN0YW5jZUlEPgogICAgICAgICAgICA8c3RSZWY6ZG9jdW1lbnRJRD54bXAuZGlkOkFDMEM4Rjk0NTQzRDExRTQ5MzZBQzlERDRCNDEwQzZDPC9zdFJlZjpkb2N1bWVudElEPgogICAgICAgICA8L3htcE1NOkRlcml2ZWRGcm9t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');
    }

    .credit-card.discover::after {
        width: 50px;
        height: 14px;
        background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAOCAYAAABth09nAAAAAXNSR0IArs4c6QAAAAlwSFlzAAALEwAACxMBAJqcGAAAAVlpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IlhNUCBDb3JlIDUuNC4wIj4KICAgPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICAgICAgPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iPgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KTMInWQAAA/tJREFUSA19ll2IVVUUx73jmEYgiUpYiIREkFqQD0FhKiVIoPaSRaEPQlATZmk9JERBQVCSldjHk9iDqE8KQaTQB0lFoljDSGKiFI3VWJZZfo6n32+fta5nrrcW/O/6r8+999n77HNbVVWNHnVZqlardSlNYj1yffAWVFt9qSNPX/axxzB2kehhfERdjNs5njmO4RyyBlok52W80pNziLmVpPaPA8fgnT4btyWL0bmAdixJt1inr9FnRP/s8X+aWuda6lqQmZHsUxxklX9qR8J0Ob7vwzcDPQHsx/cPOb3oixG7A309OIjvELEetDt5Nb5ZwAV/h++kdXB7/Yp9HBvlVKqx+G4Bx4H8WnAejAH6zoCJwB05Rc0faOd6lT8X7BByHv0OuCYSDsA/D64/ZQgyO/yz4P0ZCH1fxJZiH2nEfoGvjNg+eH9wF+ZcHo/ce9EfBE+1FvJkGL+jz4G3wFTQZ7GyHcwFG4CyOxoPwHeB2TqRl8EU8CKYDCaCU+Bv8DBwUW+AuWAeSHFnRMqDkAfCmONYCvZBcDS4D/EwuAvcDyaBV4GyGDxXWFU9gx6fC3m+dKqbZcIcEvaAj4Bc2ZB5Mdibtbu6vemP2LcRO4POhZwN3zH0DeA02B75Tlh5KuxD8E/lKdivm9CwNd8ttgx5pRG8sXZVK9Efg6+Mod8Pv5NaD3zR9oIjEdf2XJtrD3dJGa5V+zd35mY87YnB346MSdHj67B/Cv0Q2uOlrAFbCquqheaXsylpyLjgvkiuvuTwYi2ncD32KuBTGwJeDNcBxdxyXaPz+tTfFHOEN42Lfg88Td8X0EvBVsY5gVYcdxC8BBxjL/AiUNbVatQ88j+jvieP1toIaG8CimfyC2DiOOCNVATuEfHlfxYod2ZMjT0aeMaVbkfLm8rbz1z7p9ytT8ExAD6srfoXeyOwtg8oSyK31wLlS+CknLjSFwlH4Z8Aj5LX7QrgApRHI+fn2iz1C+Bu+T1gfvi7qcfqqZUJPxIJe9IXfV2It9Nq8Bq4DawD7qjz3iZHbi11kH5wGHgWXciiEqiTd2BvAtPBNyBlM8Tvgw2nAS+EFHdrfsSWwX/IANpFPxExvyvWjwe/gXx46d+Jz8vgBFBWBdyRMWACGAQ77dOCWNj5VyE/Zp55eX70bsI+i/0j2kk0P4hTcHlcjhF397JHfhDtNUDsL7S10FY+3am4hrB9CMWP9lvm3IaB79Q5YA/frdMkeWn4oXTMk+Cy2ARccQHgK08pMyOvvNjGtDPW1Pi79ept5v8Xb/bpxqkr42fMiafDR1GeUAZzEP3BSy62T6ktzRjOK3YXX44x4s9mNqDeB2WsPT6+rGmnBSGt/mObOdr/Ap6tK4eqKaaFAAAAAElFTkSuQmCC');
    }

    .credit-card.jcb::after {
        width: 30px;
        height: 15px;
        background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAPCAYAAADzun+cAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAACXBIWXMAAAsTAAALEwEAmpwYAAABWWlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS40LjAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyI+CiAgICAgICAgIDx0aWZmOk9yaWVudGF0aW9uPjE8L3RpZmY6T3JpZW50YXRpb24+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgpMwidZAAACsklEQVQ4EX2U32uOYRjH32c2zIjyKyc7IDkRZw4cMEcjxIHSyoH4C6yNNJRJOZKWAyeEQqI4wQnlhE0phCHmR1NsI43ttdnm8fk8nktPWu+3Prvu676v57qv+3rve0kJpWk6FbMdVkICRemfS5LkiZPEbsSsgRpdUNpauEbc7WwiTVdjjXW+GDcd/4GJauAiVJJFGXusUhBrh/O4XYzHKsReryZwEzTBOFiZJ9ROgLIbgyRZgm11Ao1AsTPGzoAfxBl/CMxdzi3mX07jhl00YSjaZ1LHod8M6qEKxsB2TSbjFsG8fNFNJlPixkXFiT8y2Q1usAqmwC9QFvQUXoNdshhPth5cc3PVBf2gH9/OZtwIVX4UctP46AqXxIDN8BZMGK0/ztoK/Gb4Brb9IbyHOIixu4nbgu2BpXAH3+LaoM5AN1QmHwVPJ8obadstyPFnOADqLDSA2vHXlC5j4zDT8rlZ2DqYmftztG7sZbCtc2EhqPjIFsVls5gvVP2TC7SA8WJQFhWbZRP88TBx+nuM++A+qPkw4QdWdBc2wDN4BadBNcAy8ELZjeVsupbN/e3a4SvYbn/zIbC4uCfOq21wEHaC6oDUqgzYD1beAgOO2WAfdi/Es/F3U+dZO4J9B0fBFr6EPWCn3NgDbSXuObYTXsAbfA/n3fjuP4UWCA0z6IVyTOS2Ebvuvzld361vfEgHNUM9hO/cCBg3rpPrQvwOFJG10ncXby+eitVLyHm7IHFhohu2OjrEMHtudiE64Z3xTlWb0NYox7bbxCYqJvA2x+YRrzUuYFj6AM6bJxTrzsW3PSazAuVz0rcLVi76Z7hMj7BxOuNiPaw5bsFVKHatmMtYT/4YOlzoBRMPQpyKYVb1TewJHfQJboD/MIqyM51wigK9lGXGl6AWPG3IjbvhpK/iD/ZAl+AbzJMOAAAAAElFTkSuQmCC');
    }

    .credit-card.unionpay::after {
        width: 50px;
        height: 30px;
        background-image: url('data:image/png;base64,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');
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
                            <li class="breadcrumb-item"><a href="all_gift_certificates.php">All Gift Certificates</a></li>
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
                                <li class="active"> <a class="nav-link active" data-bs-toggle="tab" id="gift_certificate_link" href="#gift_certificate" role="tab"><span class="hidden-sm-up"><i class="ti-pencil-alt"></i></span> <span class="hidden-xs-down">Gift Certificate</span></a> </li>
                            </ul>

                            <div class="tab-content tabcontent-border">
                                <div class="tab-pane active" id="gift_certificate" role="tabpanel">
                                    <form class="form-material form-horizontal" id="payment_confirmation_form" action="" method="post" enctype="multipart/form-data">
                                        <div class="p-20">
                                            <div class="row">
                                                <?php if (empty($_GET['id'])) { ?>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label" for="GIFT_CERTIFICATE">Gift Certificate</label>
                                                            <select id="GIFT_CERTIFICATE" name="GIFT_CERTIFICATE" onchange="showMinMaxAmount()" class="form-control" required>
                                                                <option disabled selected>Select Gift Certificate Name</option>
                                                                <?php
                                                                $row = $db_account->Execute("SELECT CONCAT(GIFT_CERTIFICATE_NAME,'-',GIFT_CERTIFICATE_CODE) AS GIFT_CERTIFICATE, MINIMUM_AMOUNT, MAXIMUM_AMOUNT, PK_GIFT_CERTIFICATE_SETUP FROM DOA_GIFT_CERTIFICATE_SETUP WHERE CURRENT_DATE()>=EFFECTIVE_DATE AND CURRENT_DATE()<=END_DATE AND PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                                                while (!$row->EOF) {
                                                                    $selected = '';
                                                                    if($PK_GIFT_CERTIFICATE_SETUP != '' && $PK_GIFT_CERTIFICATE_SETUP == $row->fields['PK_GIFT_CERTIFICATE_SETUP']){
                                                                        $selected = 'selected';
                                                                    }
                                                                    ?>
                                                                    <option data-minimum="<?=$row->fields['MINIMUM_AMOUNT']?>" data-maximum="<?=$row->fields['MAXIMUM_AMOUNT']?>" value="<?php echo $row->fields['PK_GIFT_CERTIFICATE_SETUP']; ?>" <?php echo $selected ;?>><?php echo $row->fields['GIFT_CERTIFICATE']; ?></option>
                                                                    <?php $row->MoveNext(); } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">Date of Purchase</label>
                                                            <div class="col-md-12">
                                                                <input type="text" name="DATE_OF_PURCHASE" id="DATE_OF_PURCHASE" value="<?=($DATE_OF_PURCHASE == '')?date('m/d/Y'):date('m/d/Y', strtotime($DATE_OF_PURCHASE))?>" class="form-control datepicker-normal">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3 align-self-center">
                                                        <label class="form-label">Gift Note</label>
                                                        <textarea class="form-control" rows="3" name="GIFT_NOTE" id="GIFT_NOTE"><?php echo $GIFT_NOTE ?></textarea>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">Amount</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="AMOUNT" name="AMOUNT" class="form-control" placeholder="Enter Amount" required value="<?php echo $AMOUNT?>">
                                                            </div>
                                                            <p id="number_of_payment_error" style="color: red; display: none; font-size: 12px; margin: 5px;"></p>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
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
                                                <?php } else { ?>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label" for="GIFT_CERTIFICATE">Gift Certificate</label>
                                                            <select id="GIFT_CERTIFICATE" name="GIFT_CERTIFICATE" onchange="showMinMaxAmount()" class="form-control" disabled>
                                                                <option disabled selected>Select Gift Certificate Name</option>
                                                                <?php
                                                                $row = $db_account->Execute("SELECT CONCAT(GIFT_CERTIFICATE_NAME,'-',GIFT_CERTIFICATE_CODE) AS GIFT_CERTIFICATE, MINIMUM_AMOUNT, MAXIMUM_AMOUNT, PK_GIFT_CERTIFICATE_SETUP FROM DOA_GIFT_CERTIFICATE_SETUP WHERE CURRENT_DATE()>=EFFECTIVE_DATE AND CURRENT_DATE()<=END_DATE AND PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                                                while (!$row->EOF) {
                                                                    $selected = '';
                                                                    if($PK_GIFT_CERTIFICATE_SETUP != '' && $PK_GIFT_CERTIFICATE_SETUP == $row->fields['PK_GIFT_CERTIFICATE_SETUP']){
                                                                        $selected = 'selected';
                                                                    }
                                                                    ?>
                                                                    <option data-minimum="<?=$row->fields['MINIMUM_AMOUNT']?>" data-maximum="<?=$row->fields['MAXIMUM_AMOUNT']?>" value="<?php echo $row->fields['PK_GIFT_CERTIFICATE_SETUP']; ?>" <?php echo $selected ;?>><?php echo $row->fields['GIFT_CERTIFICATE']; ?></option>
                                                                    <?php $row->MoveNext(); } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">Date of Purchase</label>
                                                            <div class="col-md-12">
                                                                <input type="text" name="DATE_OF_PURCHASE" id="DATE_OF_PURCHASE" value="<?=($DATE_OF_PURCHASE == '')?date('m/d/Y'):date('m/d/Y', strtotime($DATE_OF_PURCHASE))?>" class="form-control datepicker-normal" disabled>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3 align-self-center">
                                                        <label class="form-label">Gift Note</label>
                                                        <textarea class="form-control" rows="3" name="GIFT_NOTE" id="GIFT_NOTE"><?php echo $GIFT_NOTE ?></textarea>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">Amount</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="AMOUNT" name="AMOUNT" class="form-control" placeholder="Enter Amount" required value="<?php echo $AMOUNT?>" disabled>
                                                            </div>
                                                            <p id="number_of_payment_error" style="color: red; display: none; font-size: 10px;"></p>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="form-label">Payment Details</label>
                                                            <div class="col-md-12">
                                                                <select class="form-control" required name="PK_PAYMENT_TYPE" id="PK_PAYMENT_TYPE" onchange="selectPaymentType(this)" disabled>
                                                                    <?php
                                                                    $row = $db->Execute("SELECT $master_database.DOA_PAYMENT_TYPE.PAYMENT_TYPE, $account_database.DOA_GIFT_CERTIFICATE_MASTER.CHECK_NUMBER, $account_database.DOA_GIFT_CERTIFICATE_MASTER.CHECK_DATE, $account_database.DOA_GIFT_CERTIFICATE_MASTER.PAYMENT_INFO FROM $master_database.DOA_PAYMENT_TYPE INNER JOIN $account_database.DOA_GIFT_CERTIFICATE_MASTER ON $master_database.DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE=$account_database.DOA_GIFT_CERTIFICATE_MASTER.PK_PAYMENT_TYPE WHERE $account_database.DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER='$_GET[id]'");
                                                                    while (!$row->EOF) { ?>
                                                                        <?php if ($row->fields['PAYMENT_TYPE'] == "Check") { ?>
                                                                            <option value=""><?=$row->fields['PAYMENT_TYPE'].' Number: '.$row->fields['CHECK_NUMBER'].', Date: '.$row->fields['CHECK_DATE']?></option>
                                                                        <?php } else if ($row->fields['PAYMENT_TYPE'] == "Credit Card") { ?>
                                                                            <option value=""><?='CC-Confirmation Number: '.$row->fields['PAYMENT_INFO']?></option>
                                                                        <?php } else { ?>
                                                                            <option value=""><?=$row->fields['PAYMENT_TYPE']?></option>
                                                                        <?php } ?>
                                                                        <?php $row->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                            <div id="wallet_balance_div">

                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>

                                            <input type="hidden" name="PAYMENT_GATEWAY" id="PAYMENT_GATEWAY" value="<?=$PAYMENT_GATEWAY?>">
                                            <?php if ($PAYMENT_GATEWAY == 'Stripe'){ ?>
                                                <div class="row payment_type_div" id="credit_card_payment" style="display: none;">
                                                    <div class="row" style="margin: auto;" id="card_list">
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="form-group" id="card_div">

                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } elseif ($PAYMENT_GATEWAY == 'Square') { ?>
                                                <div class="row payment_type_div" id="credit_card_payment" style="display: none;">
                                                    <div class="row" style="margin: auto;" id="card_list">
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="form-group" id="card-container">

                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } elseif ($PAYMENT_GATEWAY == 'Authorized.net'){?>
                                                <div class="payment_type_div" id="credit_card_payment" style="display: none;">
                                                    <div class="row" style="margin: auto;" id="card_list">
                                                    </div>
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
                                            <div id="payment-status-container"></div>


                                            <div class="row payment_type_div" id="check_payment" style="display: none;">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Check Number<span class="text-danger">*</span></label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="CHECK_NUMBER" id="CHECK_NUMBER" class="form-control"="">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Check Date<span class="text-danger">*</span></label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="CHECK_DATE" id="CHECK_DATE" class="form-control datepicker-normal">
                                                        </div>
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
                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <?php if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <?php if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>

                                            <div class="form-group">
                                                <button class="btn btn-info waves-effect waves-light m-r-10 text-white" type="submit"><?php if(empty($_GET['id'])){ echo 'Purchase'; } else { echo 'Pay'; }?></button>
                                                <button class="btn btn-inverse waves-effect waves-light" type="button" onclick="window.location.href='all_gift_certificates.php'" >Cancel</button>
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

<?php require_once('../includes/footer.php');?>

<script src="https://js.stripe.com/v3/"></script>

<script>
    $('.datepicker-future').datepicker({
        format: 'mm/dd/yyyy',
        minDate: 0
    });

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    function showMinMaxAmount() {
        let MINIMUM = $('#GIFT_CERTIFICATE').find(':selected').data('minimum');
        let MAXIMUM = $('#GIFT_CERTIFICATE').find(':selected').data('maximum')
        $('#number_of_payment_error').show();
        $('#number_of_payment_error').text("Minimum Amount = "+MINIMUM+", Maximum Amount = "+MAXIMUM);
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

                //getCreditCardList();
                $('#credit_card_payment').slideDown();
                break;

            case 'Check':
                $('#CHECK_NUMBER').prop('required', true);
                $('#CHECK_DATE').prop('required', true);
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
                $('#CHECK_NUMBER').prop('required', false);
                $('#CHECK_DATE').prop('required', false);
                $('.payment_type_div').slideUp();
                $('#wallet_balance_div').slideUp();
                $('#remaining_amount_div').slideUp();
                $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
                break;
        }
    }

    /*    function getCreditCardList() {
            let PK_USER_MASTER = $('#PK_USER_MASTER').val();
            let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
            $.ajax({
                url: "ajax/get_credit_card_list.php",
                type: 'POST',
                data: {PK_USER_MASTER: PK_USER_MASTER, PAYMENT_GATEWAY: PAYMENT_GATEWAY},
                success: function (data) {
                    $('#card_list').html(data);
                }
            });
        }*/

    $(document).on('submit', '#payment_confirmation_form', function (event) {
        //event.preventDefault();
        let MINIMUM = $('#GIFT_CERTIFICATE').find(':selected').data('minimum');
        let MAXIMUM = $('#GIFT_CERTIFICATE').find(':selected').data('maximum');
        let entered_amount = $('#AMOUNT').val();

        if (parseFloat(entered_amount)>=parseFloat(MINIMUM) && parseFloat(entered_amount)<=parseFloat(MAXIMUM)) {
            return true;
        } else {
            $('#number_of_payment_error').show();
            $('#number_of_payment_error').text("Minimum Amount = "+MINIMUM+", Maximum Amount = "+MAXIMUM);
            $('#number_of_payment_error').effect('shake');
            return false;
        }
    });

    $(document).on('click', '.credit-card', function () {
        $('.credit-card').css("opacity", "1");
        $(this).css("opacity", "0.6");
    });

    function getPaymentMethodId(param) {
        $('#PAYMENT_METHOD_ID').val($(param).attr('id'));
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
</body>
</html>
