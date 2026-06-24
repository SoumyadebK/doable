<?php
require_once('../global/config.php');

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Function to generate unique gift certificate ID
function generateUniqueGiftCertificateId()
{
    global $db_account;

    $prefix = 'GC-';
    $maxAttempts = 10;
    $attempt = 0;

    do {
        // Generate a unique ID with timestamp and random
        $uniqueId = $prefix . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $attempt++;

        // Check if this ID already exists in the database
        $check = $db_account->Execute("SELECT PK_GIFT_CERTIFICATE_MASTER FROM DOA_GIFT_CERTIFICATE_MASTER WHERE UNIQUE_ID = '$uniqueId'");

        if ($check->RecordCount() == 0) {
            return $uniqueId;
        }
    } while ($attempt < $maxAttempts);

    // Fallback: use uniqid with more entropy
    return $prefix . strtoupper(uniqid() . rand(100, 999));
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

if (empty($_GET['id'])) {
    $PK_LOCATION = '';
    $PK_USER_MASTER = '';
    $TO = '';
    $FROM = '';
    $UNIQUE_ID = generateUniqueGiftCertificateId(); // Auto-generate unique ID
    $EMAIL_ID = '';
    $PHONE_NO = '';
    $PK_GIFT_CERTIFICATE_SETUP = '';
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
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $PK_USER_MASTER = $res->fields['PK_USER_MASTER'];
    $TO = $res->fields['RECIPIENT'];
    $FROM = $res->fields['SENDER'];
    $UNIQUE_ID = $res->fields['UNIQUE_ID'];
    $EMAIL_ID = $res->fields['EMAIL_ID'];
    $PHONE_NO = $res->fields['PHONE_NO'];
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
if ($user_payment_gateway->RecordCount() > 0) {
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
    $SQUARE_APP_ID             = $account_data->fields['APP_ID'];
    $SQUARE_LOCATION_ID     = $account_data->fields['LOCATION_ID'];
    $ACCESS_TOKEN             = $account_data->fields['ACCESS_TOKEN'];
    $PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];
    $SECRET_KEY = $account_data->fields['SECRET_KEY'];
    $LOGIN_ID = $account_data->fields['LOGIN_ID'];
    $TRANSACTION_KEY = $account_data->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY = $account_data->fields['AUTHORIZE_CLIENT_KEY'];
}

$SQUARE_MODE             = 2;
if ($SQUARE_MODE == 1)
    $SQ_URL = "https://connect.squareup.com";
else if ($SQUARE_MODE == 2)
    $SQ_URL = "https://connect.squareupsandbox.com";

if ($SQUARE_MODE == 1)
    $URL = "https://web.squarecdn.com/v1/square.js";
else if ($SQUARE_MODE == 2)
    $URL = "https://sandbox.web.squarecdn.com/v1/square.js";

if (!empty($_POST)) {
    // If UNIQUE_ID is not set in POST and it's a new record, generate one
    if (empty($_GET['id']) && empty($_POST['UNIQUE_ID'])) {
        $_POST['UNIQUE_ID'] = generateUniqueGiftCertificateId();
    }

    if ($_POST['PK_PAYMENT_TYPE'] == 1) {
        if ($_POST['PAYMENT_GATEWAY'] == 'Stripe') {
            require_once("../global/stripe-php-master/init.php");
            $stripe = new StripeClient($SECRET_KEY);
            $STRIPE_TOKEN = $_POST['token'];

            /*Check the user is already a stripe user or not*/
            $user_payment_info_data = $db_account->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PAYMENT_TYPE = 'Stripe' AND PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
            if ($user_payment_info_data->RecordCount() > 0) {
                $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];
            } else {
                $user_master = $db_account->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
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
            $card->setCustomerId($CUSTOMER_PAYMENT_ID);

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

        $PK_USER_MASTER = $_POST['PK_USER_MASTER'];
        $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID, MISC_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = " . $_POST['PK_ENROLLMENT_MASTER']);
        if (empty($enrollment_data->fields['ENROLLMENT_ID'])) {
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
    //$GIFT_CERTIFICATE_DATA['PK_USER_MASTER'] = $_POST['PK_USER_MASTER'];
    if (empty($_GET['id'])) {
        $GIFT_CERTIFICATE_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
        $GIFT_CERTIFICATE_DATA['RECIPIENT'] = $_POST['TO'];
        $GIFT_CERTIFICATE_DATA['SENDER'] = $_POST['FROM'];
        $GIFT_CERTIFICATE_DATA['EMAIL_ID'] = $_POST['EMAIL_ID'];
        $GIFT_CERTIFICATE_DATA['PHONE_NO'] = $_POST['PHONE_NO'];
        $GIFT_CERTIFICATE_DATA['UNIQUE_ID'] = $_POST['UNIQUE_ID'];
        $GIFT_CERTIFICATE_DATA['PK_GIFT_CERTIFICATE_SETUP'] = $_POST['GIFT_CERTIFICATE'];
        $GIFT_CERTIFICATE_DATA['DATE_OF_PURCHASE'] = date('Y-m-d', strtotime($_POST['DATE_OF_PURCHASE']));
        $GIFT_CERTIFICATE_DATA['GIFT_NOTE'] = $_POST['GIFT_NOTE'];
        $GIFT_CERTIFICATE_DATA['AMOUNT'] = $_POST['AMOUNT'];
        $GIFT_CERTIFICATE_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        $GIFT_CERTIFICATE_DATA['CHECK_NUMBER'] = $_POST['CHECK_NUMBER'];
        $GIFT_CERTIFICATE_DATA['CHECK_DATE'] = (!empty($_POST['CHECK_DATE'])) ? date('Y-m-d', strtotime($_POST['CHECK_DATE'])) : '0000-00-00';
        $GIFT_CERTIFICATE_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
        $GIFT_CERTIFICATE_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $GIFT_CERTIFICATE_DATA['CREATED_ON'] = date("Y-m-d H:i");
        $GIFT_CERTIFICATE_DATA['ACTIVE'] = 1;
        //pre_r($GIFT_CERTIFICATE_DATA);
        db_perform_account('DOA_GIFT_CERTIFICATE_MASTER', $GIFT_CERTIFICATE_DATA, 'insert');
        //echo (db_perform_account('DOA_GIFT_CERTIFICATE_MASTER', $GIFT_CERTIFICATE_DATA, 'insert'));
    } else {
        $GIFT_CERTIFICATE_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
        $GIFT_CERTIFICATE_DATA['RECIPIENT'] = $_POST['TO'];
        $GIFT_CERTIFICATE_DATA['SENDER'] = $_POST['FROM'];
        $GIFT_CERTIFICATE_DATA['EMAIL_ID'] = $_POST['EMAIL_ID'];
        $GIFT_CERTIFICATE_DATA['PHONE_NO'] = $_POST['PHONE_NO'];
        $GIFT_CERTIFICATE_DATA['UNIQUE_ID'] = $_POST['UNIQUE_ID'];
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
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=PT+Mono&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="assets/css/setup-styles.css" rel="stylesheet">

<style>
    :root {
        --primary-color: #39B54A;
        --primary-light: #5DCB6E;
        --primary-dark: #2D8F3B;
        --primary-rgb: 57, 181, 74;
        --success-color: #39B54A;
        --warning-color: #F59E0B;
        --danger-color: #EF4444;
        --gray-50: #F9FAFB;
        --gray-100: #F3F4F6;
        --gray-200: #E5E7EB;
        --gray-300: #D1D5DB;
        --gray-400: #9CA3AF;
        --gray-500: #6B7280;
        --gray-600: #4B5563;
        --gray-700: #374151;
        --gray-800: #1F2937;
        --gray-900: #111827;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --radius: 12px;
        --radius-sm: 8px;
        --radius-lg: 16px;
        --radius-pill: 50px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        background: var(--gray-50);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .page-wrapper {
        padding-top: 0px !important;
        background: var(--gray-50);
    }

    /* Breadcrumb */
    .breadcrumb-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .breadcrumb-wrapper h4 {
        font-size: 24px;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
        letter-spacing: -0.025em;
    }

    .breadcrumb-wrapper h4 i {
        color: var(--primary-color);
        margin-right: 10px;
    }

    .breadcrumb-nav {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-500);
    }

    .breadcrumb-nav a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .breadcrumb-nav a:hover {
        color: var(--primary-dark);
    }

    .breadcrumb-nav .separator {
        color: var(--gray-300);
    }

    .breadcrumb-nav .current {
        color: var(--gray-700);
        font-weight: 500;
    }

    /* Card */
    .card-modern {
        background: #ffffff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }

    .card-modern:hover {
        box-shadow: var(--shadow-md);
    }

    .card-modern .card-header {
        padding: 20px 24px;
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-modern .card-header h5 {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
    }

    .card-modern .card-header h5 i {
        color: var(--primary-color);
        margin-right: 8px;
    }

    .card-modern .card-header .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 500;
    }

    .card-modern .card-header .status-indicator.active {
        background: #D1FAE5;
        color: #065F46;
    }

    .card-modern .card-header .status-indicator.inactive {
        background: #FEE2E2;
        color: #991B1B;
    }

    .card-modern .card-header .status-indicator i {
        font-size: 8px;
        margin-right: 0;
    }

    .card-modern .card-body {
        padding: 28px 32px;
    }

    @media (max-width: 768px) {
        .card-modern .card-body {
            padding: 20px;
        }

        .container-fluid {
            padding: 16px !important;
        }
    }

    /* Form Grid */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px 24px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-grid.three-col {
        grid-template-columns: 1fr 1fr 1fr;
    }

    @media (max-width: 768px) {
        .form-grid.three-col {
            grid-template-columns: 1fr;
        }
    }

    .form-group-modern {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group-modern .form-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--gray-700);
        letter-spacing: 0.01em;
    }

    .form-group-modern .form-label .required {
        color: var(--danger-color);
        margin-left: 2px;
    }

    .form-group-modern .form-label .helper {
        font-weight: 400;
        color: var(--gray-400);
        font-size: 12px;
    }

    .form-control-modern {
        width: 100%;
        padding: 10px 14px;
        font-size: 14px;
        color: var(--gray-800);
        background: #fff;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        transition: all 0.2s ease;
        outline: none;
        font-family: inherit;
    }

    .form-control-modern:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .form-control-modern:hover {
        border-color: var(--gray-300);
    }

    .form-control-modern::placeholder {
        color: var(--gray-400);
        font-size: 13px;
    }

    .form-control-modern.is-invalid {
        border-color: var(--danger-color);
    }

    .form-control-modern.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    .form-control-modern:disabled {
        background: var(--gray-100);
        cursor: not-allowed;
    }

    .form-control-modern:read-only {
        background: var(--gray-50);
        cursor: default;
    }

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    textarea.form-control-modern {
        min-height: 80px;
        resize: vertical;
    }

    /* Radio & Checkbox */
    .radio-group-modern {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        padding-top: 4px;
    }

    .radio-group-modern .radio-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
    }

    .radio-group-modern .radio-item input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        flex-shrink: 0;
    }

    /* Buttons - Rounded Pill */
    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 28px;
        font-size: 14px;
        font-weight: 500;
        border: none;
        border-radius: var(--radius-pill);
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-family: inherit;
        line-height: 1.5;
    }

    .btn-modern-primary {
        background: var(--primary-color);
        color: #fff;
    }

    .btn-modern-primary:hover {
        background: var(--primary-dark);
        box-shadow: var(--shadow-md);
        transform: translateY(-1px);
        color: #fff;
    }

    .btn-modern-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-modern-secondary {
        background: var(--gray-100);
        color: var(--gray-700);
    }

    .btn-modern-secondary:hover {
        background: var(--gray-200);
        color: var(--gray-800);
    }

    .btn-modern-success {
        background: var(--success-color);
        color: #fff;
    }

    .btn-modern-success:hover {
        background: var(--primary-dark);
        box-shadow: var(--shadow-md);
        transform: translateY(-1px);
        color: #fff;
    }

    .btn-modern-refresh {
        background: var(--gray-100);
        color: var(--gray-600);
        padding: 10px 16px;
        border-radius: var(--radius-sm);
        border: 1.5px solid var(--gray-200);
        flex-shrink: 0;
    }

    .btn-modern-refresh:hover {
        background: var(--gray-200);
        color: var(--gray-800);
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px solid var(--gray-200);
        flex-wrap: wrap;
    }

    @media (max-width: 640px) {
        .form-actions {
            flex-direction: column;
        }

        .form-actions .btn-modern {
            width: 100%;
            justify-content: center;
        }

        .breadcrumb-wrapper {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    /* Stripe Element */
    #card-element,
    .StripeElement {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        background: #fff;
        transition: all 0.2s ease;
        min-height: 44px;
    }

    #card-element:focus,
    .StripeElement--focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .StripeElement--invalid {
        border-color: var(--danger-color);
    }

    /* Payment type sections */
    .payment-section {
        margin-top: 16px;
        padding: 20px;
        background: var(--gray-50);
        border-radius: var(--radius-sm);
        border: 1px solid var(--gray-200);
    }

    .payment-section .section-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 16px;
    }

    /* Form helper */
    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
    }

    .form-helper.error {
        color: var(--danger-color);
    }

    /* Unique ID container */
    .unique-id-container {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .unique-id-container .form-control-modern {
        flex: 1;
    }

    /* Credit Card styling */
    #advice-required-entry-ACCEPT_HANDLING {
        width: 150px;
        top: 20px;
        position: absolute;
    }

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
        background: linear-gradient(to left, #283593, #1976d2);
        color: #ffffff;
    }

    .credit-card.selectable:hover {
        cursor: pointer;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19), 0 6px 6px rgba(0, 0, 0, 0.23);
    }

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

    /* Credit Card brand colors */
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

    .credit-card.diners,
    .credit-card.diners-club {
        background: #8a38ff;
        color: #f5efff;
    }

    .credit-card.diners .credit-card-last4:before,
    .credit-card.diners-club .credit-card-last4:before {
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

    /* Payment status */
    .payment-status {
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        font-size: 14px;
        margin-top: 12px;
    }

    .payment-status.success {
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #A7F3D0;
    }

    .payment-status.error {
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FCA5A5;
    }

    /* SumoSelect override */
    .SumoSelect {
        width: 90%;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">
                <!-- Main Content -->
                <div class="row g-4">
                    <!-- Sidebar -->
                    <div class="col-12 col-md-4 col-xl-2">
                        <?php include 'layout/setup_sidebar.php'; ?>
                    </div>

                    <!-- Main Form -->
                    <div class="col-12 col-md-8 col-xl-10">
                        <div class="card-modern">
                            <div class="card-header">
                                <h5>
                                    <i class="bi bi-gift me-2" style="color: #39b54a;"></i>
                                    <?= !empty($_GET['id']) ? 'Edit Gift Certificate' : 'Create New Gift Certificate' ?>
                                </h5>
                                <?php if (!empty($_GET['id'])): ?>
                                    <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                        <i class="fas fa-circle"></i>
                                        <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <form class="form-material form-horizontal" id="payment_confirmation_form" action="" method="post" enctype="multipart/form-data">

                                    <div class="form-grid">
                                        <div class="form-group-modern">
                                            <label class="form-label">Location <span class="text-danger">*</span></label>
                                            <select class="form-control-modern" id="PK_LOCATION" name="PK_LOCATION" required>
                                                <option value="">Select Location</option>
                                                <?php
                                                $row = $db->Execute("SELECT * FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($PK_LOCATION == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= $row->fields['LOCATION_NAME'] ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                        </div>

                                        <!-- Unique ID - Auto-generated with refresh button -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Unique ID <span class="required">*</span></label>
                                            <div class="unique-id-container">
                                                <input type="text" class="form-control-modern" name="UNIQUE_ID" id="UNIQUE_ID" value="<?= htmlspecialchars($UNIQUE_ID) ?>" readonly required>
                                                <?php if (empty($_GET['id'])): ?>
                                                    <button type="button" class="btn-modern btn-modern-refresh" onclick="regenerateUniqueId()" title="Generate new unique ID">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (empty($_GET['id'])): ?>
                                                <div class="form-helper">Auto-generated unique identifier for this gift certificate. Click refresh to generate a new one.</div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="form-group-modern">
                                            <label class="form-label">To <span class="required">*</span></label>
                                            <input type="text" class="form-control-modern" name="TO" id="TO" value="<?= htmlspecialchars($TO) ?>" required>
                                        </div>

                                        <div class="form-group-modern">
                                            <label class="form-label">From <span class="required">*</span></label>
                                            <input type="text" class="form-control-modern" name="FROM" id="FROM" value="<?= htmlspecialchars($FROM) ?>" required>
                                        </div>

                                        <div class="form-group-modern">
                                            <label class="form-label">To Email <span class="required">*</span></label>
                                            <input type="email" class="form-control-modern" name="EMAIL_ID" id="EMAIL_ID" value="<?= htmlspecialchars($EMAIL_ID) ?>" required>
                                        </div>

                                        <div class="form-group-modern">
                                            <label class="form-label">To Phone No</label>
                                            <input type="text" class="form-control-modern" name="PHONE_NO" id="PHONE_NO" value="<?= htmlspecialchars($PHONE_NO) ?>">
                                        </div>

                                        <!-- Gift Certificate -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Gift Certificate <span class="required">*</span></label>
                                            <select id="GIFT_CERTIFICATE" name="GIFT_CERTIFICATE" onchange="showMinMaxAmount()" class="form-control-modern" <?= !empty($_GET['id']) ? 'disabled' : '' ?> required>
                                                <option value="">Select Gift Certificate</option>
                                                <?php
                                                $row = $db_account->Execute("SELECT CONCAT(GIFT_CERTIFICATE_NAME,'-',GIFT_CERTIFICATE_CODE) AS GIFT_CERTIFICATE, MINIMUM_AMOUNT, MAXIMUM_AMOUNT, PK_GIFT_CERTIFICATE_SETUP FROM DOA_GIFT_CERTIFICATE_SETUP WHERE CURRENT_DATE()>=EFFECTIVE_DATE AND CURRENT_DATE()<=END_DATE AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
                                                while (!$row->EOF) {
                                                    $selected = '';
                                                    if ($PK_GIFT_CERTIFICATE_SETUP != '' && $PK_GIFT_CERTIFICATE_SETUP == $row->fields['PK_GIFT_CERTIFICATE_SETUP']) {
                                                        $selected = 'selected';
                                                    }
                                                ?>
                                                    <option data-minimum="<?= $row->fields['MINIMUM_AMOUNT'] ?>" data-maximum="<?= $row->fields['MAXIMUM_AMOUNT'] ?>" value="<?php echo $row->fields['PK_GIFT_CERTIFICATE_SETUP']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row->fields['GIFT_CERTIFICATE']); ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                            <?php if (!empty($_GET['id'])): ?>
                                                <input type="hidden" name="GIFT_CERTIFICATE" value="<?= $PK_GIFT_CERTIFICATE_SETUP ?>">
                                            <?php endif; ?>
                                            <div class="form-helper" id="amount_limits">Select a gift certificate to see amount limits</div>
                                        </div>

                                        <!-- Date of Purchase -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Date of Purchase <span class="required">*</span></label>
                                            <input type="text" name="DATE_OF_PURCHASE" id="DATE_OF_PURCHASE" value="<?= ($DATE_OF_PURCHASE == '') ? date('m/d/Y') : date('m/d/Y', strtotime($DATE_OF_PURCHASE)) ?>" class="form-control-modern datepicker-normal" <?= !empty($_GET['id']) ? 'disabled' : '' ?> required>
                                            <?php if (!empty($_GET['id'])): ?>
                                                <input type="hidden" name="DATE_OF_PURCHASE" value="<?= date('m/d/Y', strtotime($DATE_OF_PURCHASE)) ?>">
                                            <?php endif; ?>
                                        </div>

                                        <!-- Amount -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Amount <span class="required">*</span></label>
                                            <div style="position: relative;">
                                                <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-weight: 600; color: var(--gray-500);">$</span>
                                                <input type="text" id="AMOUNT" name="AMOUNT" class="form-control-modern" style="padding-left: 32px;" placeholder="0.00" required value="<?php echo $AMOUNT ?>" <?= !empty($_GET['id']) ? 'disabled' : '' ?>>
                                            </div>
                                            <?php if (!empty($_GET['id'])): ?>
                                                <input type="hidden" name="AMOUNT" value="<?= $AMOUNT ?>">
                                            <?php endif; ?>
                                            <div id="number_of_payment_error" class="form-helper error" style="display: none;"></div>
                                        </div>

                                        <!-- Payment Type -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Payment Type <span class="required">*</span></label>
                                            <select class="form-control-modern" required name="PK_PAYMENT_TYPE" id="PK_PAYMENT_TYPE" onchange="selectPaymentType(this)" <?= !empty($_GET['id']) ? 'disabled' : '' ?>>
                                                <option value="">Select Payment Type</option>
                                                <?php
                                                $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_PAYMENT_TYPE']; ?>"><?= htmlspecialchars($row->fields['PAYMENT_TYPE']) ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                            <?php if (!empty($_GET['id'])): ?>
                                                <?php
                                                $row = $db->Execute("SELECT $master_database.DOA_PAYMENT_TYPE.PAYMENT_TYPE, $account_database.DOA_GIFT_CERTIFICATE_MASTER.CHECK_NUMBER, $account_database.DOA_GIFT_CERTIFICATE_MASTER.CHECK_DATE, $account_database.DOA_GIFT_CERTIFICATE_MASTER.PAYMENT_INFO FROM $master_database.DOA_PAYMENT_TYPE INNER JOIN $account_database.DOA_GIFT_CERTIFICATE_MASTER ON $master_database.DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE=$account_database.DOA_GIFT_CERTIFICATE_MASTER.PK_PAYMENT_TYPE WHERE $account_database.DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER='$_GET[id]'");
                                                while (!$row->EOF) { ?>
                                                    <input type="hidden" name="PK_PAYMENT_TYPE" value="<?= $row->fields['PK_PAYMENT_TYPE'] ?>">
                                                    <div style="padding: 10px 14px; background: var(--gray-50); border-radius: var(--radius-sm); color: var(--gray-700); font-size: 14px; margin-top: 4px;">
                                                        <?php if ($row->fields['PAYMENT_TYPE'] == "Check") { ?>
                                                            <?= $row->fields['PAYMENT_TYPE'] . ' - #' . $row->fields['CHECK_NUMBER'] . ', ' . date('m/d/Y', strtotime($row->fields['CHECK_DATE'])) ?>
                                                        <?php } else if ($row->fields['PAYMENT_TYPE'] == "Credit Card") { ?>
                                                            <?= 'CC - Confirmation: ' . $row->fields['PAYMENT_INFO'] ?>
                                                        <?php } else { ?>
                                                            <?= $row->fields['PAYMENT_TYPE'] ?>
                                                        <?php } ?>
                                                    </div>
                                                <?php $row->MoveNext();
                                                } ?>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Gift Note -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Gift Note</label>
                                            <textarea class="form-control-modern" rows="3" name="GIFT_NOTE" id="GIFT_NOTE" placeholder="Add a personal note for the recipient"><?php echo htmlspecialchars($GIFT_NOTE) ?></textarea>
                                            <div class="form-helper">Optional message to include with the gift certificate</div>
                                        </div>
                                    </div>

                                    <!-- Payment Gateway Info -->
                                    <input type="hidden" name="PAYMENT_GATEWAY" id="PAYMENT_GATEWAY" value="<?= $PAYMENT_GATEWAY ?>">

                                    <!-- Wallet Balance -->
                                    <div id="wallet_balance_div"></div>

                                    <!-- Credit Card Payment Section -->
                                    <?php if ($PAYMENT_GATEWAY == 'Stripe') { ?>
                                        <div class="payment-section payment_type_div" id="credit_card_payment" style="display: none;">
                                            <div class="section-title"><i class="fas fa-credit-card"></i> Credit Card Payment</div>
                                            <div id="card_list"></div>
                                            <div class="form-group-modern">
                                                <label class="form-label">Card Details</label>
                                                <div id="card_div">
                                                    <div id="card-element"></div>
                                                    <div id="card-errors" style="color: var(--danger-color); font-size: 13px; margin-top: 6px;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } elseif ($PAYMENT_GATEWAY == 'Square') { ?>
                                        <div class="payment-section payment_type_div" id="credit_card_payment" style="display: none;">
                                            <div class="section-title"><i class="fas fa-credit-card"></i> Credit Card Payment</div>
                                            <div id="card_list"></div>
                                            <div class="form-group-modern">
                                                <label class="form-label">Card Details</label>
                                                <div id="card-container"></div>
                                                <div id="payment-status-container"></div>
                                            </div>
                                        </div>
                                    <?php } elseif ($PAYMENT_GATEWAY == 'Authorized.net') { ?>
                                        <div class="payment-section payment_type_div" id="credit_card_payment" style="display: none;">
                                            <div class="section-title"><i class="fas fa-credit-card"></i> Credit Card Payment</div>
                                            <div id="card_list"></div>
                                            <div class="form-grid" style="margin-top: 12px;">
                                                <div class="form-group-modern">
                                                    <label class="form-label">Name on Card <span class="required">*</span></label>
                                                    <input type="text" name="NAME" id="NAME" class="form-control-modern" placeholder="Full name as it appears on card" value="<?= htmlspecialchars($NAME) ?>">
                                                </div>
                                                <div class="form-group-modern">
                                                    <label class="form-label">Email <span class="required">*</span></label>
                                                    <input type="email" name="EMAIL" id="EMAIL" class="form-control-modern" placeholder="Email for payment confirmation">
                                                </div>
                                                <div class="form-group-modern">
                                                    <label class="form-label">Card Number <span class="required">*</span></label>
                                                    <input type="text" name="CARD_NUMBER" id="CARD_NUMBER" class="form-control-modern" placeholder="XXXX XXXX XXXX XXXX" value="<?= htmlspecialchars($CARD_NUMBER) ?>">
                                                </div>
                                                <div class="form-group-modern">
                                                    <label class="form-label">Expiration Month <span class="required">*</span></label>
                                                    <input type="text" name="EXPIRATION_MONTH" id="EXPIRATION_MONTH" class="form-control-modern" placeholder="MM">
                                                </div>
                                                <div class="form-group-modern">
                                                    <label class="form-label">Expiration Year <span class="required">*</span></label>
                                                    <input type="text" name="EXPIRATION_YEAR" id="EXPIRATION_YEAR" class="form-control-modern" placeholder="YYYY">
                                                </div>
                                                <div class="form-group-modern">
                                                    <label class="form-label">Security Code <span class="required">*</span></label>
                                                    <input type="text" name="SECURITY_CODE" id="SECURITY_CODE" class="form-control-modern" placeholder="CVV" value="<?= htmlspecialchars($SECURITY_CODE) ?>">
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <!-- Check Payment Section -->
                                    <div class="payment-section payment_type_div" id="check_payment" style="display: none;">
                                        <div class="section-title"><i class="fas fa-check"></i> Check Payment</div>
                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Check Number <span class="required">*</span></label>
                                                <input type="text" name="CHECK_NUMBER" id="CHECK_NUMBER" class="form-control-modern" placeholder="Enter check number">
                                            </div>
                                            <div class="form-group-modern">
                                                <label class="form-label">Check Date <span class="required">*</span></label>
                                                <input type="text" name="CHECK_DATE" id="CHECK_DATE" class="form-control-modern datepicker-normal" placeholder="Select date">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Active Status -->
                                    <?php if (!empty($_GET['id'])): ?>
                                        <div class="form-group-modern" style="margin-top: 16px;">
                                            <label class="form-label">Status</label>
                                            <div class="radio-group-modern">
                                                <label class="radio-item">
                                                    <input type="radio" name="ACTIVE" id="ACTIVE1" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?>>
                                                    Active
                                                </label>
                                                <label class="radio-item">
                                                    <input type="radio" name="ACTIVE" id="ACTIVE2" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?>>
                                                    Inactive
                                                </label>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Form Actions -->
                                    <div class="form-actions">
                                        <button type="submit" class="btn-modern btn-modern-primary">
                                            <i class="fas fa-save"></i>
                                            <?php if (empty($_GET['id'])): ?>
                                                Purchase Gift Certificate
                                            <?php else: ?>
                                                Update Gift Certificate
                                            <?php endif; ?>
                                        </button>
                                        <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_gift_certificates.php'">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

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
            let MAXIMUM = $('#GIFT_CERTIFICATE').find(':selected').data('maximum');
            if (MINIMUM && MAXIMUM) {
                $('#amount_limits').text('Minimum: $' + MINIMUM + ' | Maximum: $' + MAXIMUM);
                $('#amount_limits').css('color', 'var(--gray-600)');
            } else {
                $('#amount_limits').text('Select a gift certificate to see amount limits');
            }
        }

        function regenerateUniqueId() {
            const prefix = 'GC-';
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            const timestamp = year + month + day;
            const random = Math.random().toString(36).substring(2, 8).toUpperCase();
            const newId = prefix + timestamp + '-' + random;
            document.getElementById('UNIQUE_ID').value = newId;
        }

        function selectPaymentType(param) {
            let paymentType = $("#PK_PAYMENT_TYPE option:selected").text();
            let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();

            $('.payment_type_div').slideUp();
            $('#card-element').remove();

            switch (paymentType) {
                case 'Credit Card':
                    if (PAYMENT_GATEWAY == 'Stripe') {
                        $('#card_div').html(`<div id="card-element"></div>`);
                        setTimeout(stripePaymentFunction, 100);
                    }
                    $('#credit_card_payment').slideDown();
                    break;

                case 'Check':
                    $('#CHECK_NUMBER').prop('required', true);
                    $('#CHECK_DATE').prop('required', true);
                    $('#check_payment').slideDown();
                    break;

                case 'Wallet':
                    let PK_USER_MASTER = $('#PK_USER_MASTER').val();
                    if (PK_USER_MASTER) {
                        $.ajax({
                            url: "ajax/wallet_balance.php",
                            type: 'POST',
                            data: {
                                PK_USER_MASTER: PK_USER_MASTER
                            },
                            success: function(data) {
                                $('#wallet_balance_div').html(data);
                                $('#wallet_balance_div').slideDown();
                            }
                        });
                    }
                    break;

                default:
                    $('#CHECK_NUMBER').prop('required', false);
                    $('#CHECK_DATE').prop('required', false);
                    $('.payment_type_div').slideUp();
                    $('#wallet_balance_div').slideUp();
                    break;
            }
        }

        $(document).on('submit', '#payment_confirmation_form', function(event) {
            let MINIMUM = $('#GIFT_CERTIFICATE').find(':selected').data('minimum');
            let MAXIMUM = $('#GIFT_CERTIFICATE').find(':selected').data('maximum');
            let entered_amount = $('#AMOUNT').val();

            if (MINIMUM && MAXIMUM) {
                if (parseFloat(entered_amount) >= parseFloat(MINIMUM) && parseFloat(entered_amount) <= parseFloat(MAXIMUM)) {
                    return true;
                } else {
                    $('#number_of_payment_error').show();
                    $('#number_of_payment_error').text("Minimum Amount = $" + MINIMUM + ", Maximum Amount = $" + MAXIMUM);
                    $('#number_of_payment_error').css('color', 'var(--danger-color)');
                    return false;
                }
            }
            return true;
        });

        $(document).on('click', '.credit-card', function() {
            $('.credit-card').css("opacity", "1");
            $(this).css("opacity", "0.6");
        });

        function getPaymentMethodId(param) {
            $('#PAYMENT_METHOD_ID').val($(param).attr('id'));
        }

        // Initialize amount limits on page load
        $(document).ready(function() {
            showMinMaxAmount();
        });
    </script>

    <?php if ($PAYMENT_GATEWAY == 'Stripe'): ?>
        <script type="text/javascript">
            function stripePaymentFunction() {
                var stripe = Stripe('<?= $PUBLISHABLE_KEY ?>');
                var elements = stripe.elements();

                var style = {
                    base: {
                        fontSize: '14px',
                        color: '#1F2937',
                        fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, sans-serif',
                        '::placeholder': {
                            color: '#9CA3AF'
                        }
                    },
                    invalid: {
                        color: '#EF4444',
                        iconColor: '#EF4444'
                    }
                };

                var card = elements.create('card', {
                    style: style
                });

                if ($('#card-element').length > 0) {
                    card.mount('#card-element');
                }

                card.addEventListener('change', function(event) {
                    var displayError = document.getElementById('card-errors');
                    if (event.error) {
                        displayError.textContent = event.error.message;
                    } else {
                        displayError.textContent = '';
                    }
                });

                var form = document.getElementById('payment_confirmation_form');
                form.addEventListener('submit', function(event) {
                    if ($('#credit_card_payment').is(':visible')) {
                        event.preventDefault();
                        stripe.createToken(card).then(function(result) {
                            if (result.error) {
                                var errorElement = document.getElementById('card-errors');
                                errorElement.textContent = result.error.message;
                            } else {
                                stripeTokenHandler(result.token);
                            }
                        });
                    }
                });

                function stripeTokenHandler(token) {
                    var form = document.getElementById('payment_confirmation_form');
                    var hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'token');
                    hiddenInput.setAttribute('value', token.id);
                    form.appendChild(hiddenInput);
                    form.submit();
                }
            }
        </script>
    <?php endif; ?>

    <?php if ($PAYMENT_GATEWAY == 'Square'): ?>
        <script type="text/javascript" src="<?= $URL ?>"></script>
        <script>
            const appId = '<?= $SQUARE_APP_ID ?>';
            const locationId = '<?= $SQUARE_LOCATION_ID ?>';

            async function initializeCard(payments) {
                const card = await payments.card();
                await card.attach('#card-container');
                return card;
            }

            async function tokenize(paymentMethod) {
                const tokenResult = await paymentMethod.tokenize();
                if (tokenResult.status === 'OK') {
                    return tokenResult.token;
                } else {
                    let errorMessage = `Tokenization failed with status: ${tokenResult.status}`;
                    if (tokenResult.errors) {
                        errorMessage += ` and errors: ${JSON.stringify(tokenResult.errors)}`;
                    }
                    throw new Error(errorMessage);
                }
            }

            function displayPaymentResults(status) {
                const statusContainer = document.getElementById('payment-status-container');
                if (status === 'SUCCESS') {
                    statusContainer.innerHTML = '<div class="payment-status success">Payment Successful!</div>';
                } else {
                    statusContainer.innerHTML = '<div class="payment-status error">Payment Failed. Please try again.</div>';
                }
            }

            document.addEventListener('DOMContentLoaded', async function() {
                if (!window.Square) {
                    throw new Error('Square.js failed to load properly');
                }

                let payments;
                try {
                    payments = window.Square.payments(appId, locationId);
                } catch {
                    const statusContainer = document.getElementById('payment-status-container');
                    statusContainer.innerHTML = '<div class="payment-status error">Payment system not available. Please try again later.</div>';
                    return;
                }

                let card;
                try {
                    card = await initializeCard(payments);
                } catch (e) {
                    console.error('Initializing Card failed', e);
                    return;
                }

                async function handlePaymentMethodSubmission(event, paymentMethod) {
                    event.preventDefault();

                    try {
                        const token = await tokenize(paymentMethod);
                        var form = document.getElementById('payment_confirmation_form');
                        var hiddenInput = document.createElement('input');
                        hiddenInput.setAttribute('type', 'hidden');
                        hiddenInput.setAttribute('name', 'sourceId');
                        hiddenInput.setAttribute('value', token);
                        form.appendChild(hiddenInput);
                        form.submit();
                    } catch (e) {
                        displayPaymentResults('FAILURE');
                        console.error(e.message);
                    }
                }

                const cardButton = document.createElement('button');
                cardButton.type = 'button';
                cardButton.className = 'btn-modern btn-modern-primary';
                cardButton.id = 'card-button';
                cardButton.innerHTML = '<i class="fas fa-credit-card"></i> Pay with Card';
                cardButton.style.marginTop = '12px';

                const cardContainer = document.getElementById('card-container');
                if (cardContainer) {
                    cardContainer.parentNode.insertBefore(cardButton, cardContainer.nextSibling);
                }

                cardButton.addEventListener('click', async function(event) {
                    await handlePaymentMethodSubmission(event, card);
                });
            });
        </script>
    <?php endif; ?>

</body>

</html>