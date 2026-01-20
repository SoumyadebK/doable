<?php
require_once('../../global/config.php');
require_once("../../global/stripe-php/init.php");
global $db;
global $db_account;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

use Square\Environment;
use Square\Models\CreatePaymentRequest;
use Square\Models\Money;
use Square\SquareClient;

require_once('../../global/authorizenet/autoload.php');

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

$PK_USER = $_POST['PK_USER'];

$payment_gateway_data = getPaymentGatewayData();

$PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
$GATEWAY_MODE  = $payment_gateway_data->fields['GATEWAY_MODE'];

$SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];

$AUTHORIZE_LOGIN_ID         = $payment_gateway_data->fields['LOGIN_ID']; //"4Y5pCy8Qr";
$AUTHORIZE_TRANSACTION_KEY     = $payment_gateway_data->fields['TRANSACTION_KEY']; //"4ke43FW8z3287HV5";
$AUTHORIZE_CLIENT_KEY         = $payment_gateway_data->fields['AUTHORIZE_CLIENT_KEY']; //"8ZkyJnT87uFztUz56B4PfgCe7yffEZA4TR5dv8ALjqk5u9mr6d8Nmt8KHyp8s9Ay";

$MERCHANT_ID            = $payment_gateway_data->fields['MERCHANT_ID'];
$API_KEY                = $payment_gateway_data->fields['API_KEY'];
$PUBLIC_API_KEY         = $payment_gateway_data->fields['PUBLIC_API_KEY'];

$message = '';

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] == 'saveCreditCard') {
    if ($PAYMENT_GATEWAY == 'Stripe') {
        $stripe = new StripeClient($SECRET_KEY);

        $customer_payment_info = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Stripe' AND PK_USER = " . $PK_USER);

        $STRIPE_TOKEN = $_POST['stripe_token'];
        $CUSTOMER_PAYMENT_ID = '';
        if ($customer_payment_info->RecordCount() > 0) {
            $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
        } else {
            try {
                $user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = " . $PK_USER);
                $customer = $stripe->customers->create([
                    'email' => $user_data->fields['EMAIL_ID'],
                    'name' => $user_data->fields['FIRST_NAME'] . ' ' . $user_data->fields['LAST_NAME'],
                    'phone' => $user_data->fields['PHONE'],
                    'description' => 'Add Credit Card',
                ]);
                $CUSTOMER_PAYMENT_ID = $customer->id;
            } catch (ApiErrorException $e) {
                $STATUS = false;
                $MESSAGE = $e->getMessage();
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

            $STATUS = true;
            $MESSAGE = "Credit Card Added Successfully";
        } catch (ApiErrorException $e) {
            $STATUS = false;
            $MESSAGE = $e->getMessage();
        }

        $RETURN_DATA['STATUS'] = $STATUS;
        $RETURN_DATA['MESSAGE'] = $MESSAGE;
        echo json_encode($RETURN_DATA);
    } elseif ($PAYMENT_GATEWAY == 'Square') {
        require_once("../../global/vendor/autoload.php");

        if ($GATEWAY_MODE == 'live') {
            $client = new SquareClient([
                'accessToken' => $SQUARE_ACCESS_TOKEN,
                'environment' => Environment::PRODUCTION,
            ]);
        } else {
            $client = new SquareClient([
                'accessToken' => $SQUARE_ACCESS_TOKEN,
                'environment' => Environment::SANDBOX,
            ]);
        }

        $user_master = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE, DOA_USERS.ADDRESS, DOA_USERS.ADDRESS_1, DOA_USERS.CITY, DOA_COUNTRY.COUNTRY_CODE, DOA_STATES.STATE_CODE, DOA_USERS.ZIP FROM `DOA_USERS` LEFT JOIN DOA_COUNTRY ON DOA_USERS.PK_COUNTRY = DOA_COUNTRY.PK_COUNTRY LEFT JOIN DOA_STATES ON DOA_USERS.PK_STATES = DOA_STATES.PK_STATES WHERE DOA_USERS.PK_USER = '$PK_USER'");
        // Get or create Square customer
        $customer_payment_info = $db_account->Execute("SELECT CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Square' AND PK_USER = " . $PK_USER);
        if ($customer_payment_info->RecordCount() > 0) {
            $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
        } else {
            try {
                $address = new \Square\Models\Address();
                $address->setAddressLine1($user_master->fields['ADDRESS']);
                $address->setAddressLine2($user_master->fields['ADDRESS_1']);
                $address->setLocality($user_master->fields['CITY']);
                $address->setAdministrativeDistrictLevel1($user_master->fields['STATE_CODE']);
                $address->setPostalCode($user_master->fields['ZIP']);
                $address->setCountry('US');

                $body = new \Square\Models\CreateCustomerRequest();
                $body->setGivenName($user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME']);
                $body->setFamilyName($user_master->fields['FIRST_NAME']);
                $body->setEmailAddress($user_master->fields['EMAIL_ID']);
                $body->setAddress($address);
                $body->setPhoneNumber($user_master->fields['PHONE']);
                $body->setReferenceId('N/A');
                $body->setNote($user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME'] . " from Doable");

                $api_response = $client->getCustomersApi()->createCustomer($body);
            } catch (\Square\Exceptions\ApiException $e) {
                $RETURN_DATA['STATUS'] = false;
                $RETURN_DATA['MESSAGE'] = $e->getMessage();
                echo json_encode($RETURN_DATA);
                die();
            }

            $CUSTOMER_PAYMENT_ID = json_decode($api_response->getBody())->customer->id;

            $SQUARE_DETAILS['PK_USER'] = $user_master->fields['PK_USER'];
            $SQUARE_DETAILS['CUSTOMER_PAYMENT_ID'] = $CUSTOMER_PAYMENT_ID;
            $SQUARE_DETAILS['PAYMENT_TYPE'] = 'Square';
            $SQUARE_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_CUSTOMER_PAYMENT_INFO', $SQUARE_DETAILS, 'insert');
        }

        $square_token = $_POST['square_token'];

        try {
            // Save the new card for future use
            $card = new \Square\Models\Card();
            $card->setCardholderName($user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME']);
            $card->setCustomerId($CUSTOMER_PAYMENT_ID);

            $body = new \Square\Models\CreateCardRequest(uniqid(), $square_token, $card);
            $api_response = $client->getCardsApi()->createCard($body);

            $STATUS = true;
            $MESSAGE = "Credit Card Added Successfully";
        } catch (\Square\Exceptions\ApiException $e) {
            $STATUS = false;
            $MESSAGE = $e->getMessage();
        }

        $RETURN_DATA['STATUS'] = $STATUS;
        $RETURN_DATA['MESSAGE'] = $MESSAGE;
        echo json_encode($RETURN_DATA);
    } elseif ($PAYMENT_GATEWAY == 'Authorized.net') {
        $customer_payment_info = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Authorized.net' AND PK_USER = " . $PK_USER);
        $user_data = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE, DOA_USERS.ADDRESS, DOA_USERS.ADDRESS_1, DOA_USERS.CITY, DOA_COUNTRY.COUNTRY_CODE, DOA_STATES.STATE_CODE, DOA_USERS.ZIP FROM `DOA_USERS` LEFT JOIN DOA_COUNTRY ON DOA_USERS.PK_COUNTRY = DOA_COUNTRY.PK_COUNTRY LEFT JOIN DOA_STATES ON DOA_USERS.PK_STATES = DOA_STATES.PK_STATES WHERE DOA_USERS.PK_USER = " . $PK_USER);

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($AUTHORIZE_LOGIN_ID);
        $merchantAuthentication->setTransactionKey($AUTHORIZE_TRANSACTION_KEY);

        $card_number = preg_replace('/\s+/', '', $_POST['SAVE_CARD_NUMBER']);
        $card_exp_month = $_POST['SAVE_EXPIRATION_MONTH'];
        $card_exp_year = $_POST['SAVE_EXPIRATION_YEAR'];
        $card_exp_year_month = $card_exp_year . '-' . sprintf('%02d', $card_exp_month);
        $card_cvc = $_POST['SAVE_SECURITY_CODE'];

        // Create the payment data
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($card_number);
        $creditCard->setExpirationDate($card_exp_year_month);
        $creditCard->setCardCode($card_cvc);

        $payment = new AnetAPI\PaymentType();
        $payment->setCreditCard($creditCard);

        $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
        $paymentProfile->setCustomerType('individual');
        $paymentProfile->setPayment($payment);

        if ($customer_payment_info->RecordCount() > 0) {
            // ✅ Customer already exists, just add new card
            $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];

            $createPaymentProfileRequest = new AnetAPI\CreateCustomerPaymentProfileRequest();
            $createPaymentProfileRequest->setMerchantAuthentication($merchantAuthentication);
            $createPaymentProfileRequest->setCustomerProfileId($CUSTOMER_PAYMENT_ID);
            $createPaymentProfileRequest->setPaymentProfile($paymentProfile);
            $createPaymentProfileRequest->setValidationMode("testMode");
            //$createPaymentProfileRequest->setValidationMode($GATEWAY_MODE == 'live' ? "liveMode" : "testMode");

            $controller = new AnetController\CreateCustomerPaymentProfileController($createPaymentProfileRequest);
            $response = $controller->executeWithApiResponse($GATEWAY_MODE == 'live' ? \net\authorize\api\constants\ANetEnvironment::PRODUCTION : \net\authorize\api\constants\ANetEnvironment::SANDBOX);

            if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                $STATUS = true;
                $MESSAGE = "Credit Card Added Successfully";
            } else {
                $STATUS = false;
                $MESSAGE = 'Error adding credit card: ' . $response->getMessages()->getMessage()[0]->getText();
            }
        } else {
            // ✅ No customer profile yet, create new customer and card together
            try {
                $customerProfile = new AnetAPI\CustomerProfileType();
                $customerProfile->setMerchantCustomerId(substr("USER_" . $PK_USER, 0, 20));

                if (!empty($user_data->fields['EMAIL_ID']) && filter_var($user_data->fields['EMAIL_ID'], FILTER_VALIDATE_EMAIL)) {
                    $customerProfile->setEmail($user_data->fields['EMAIL_ID']);
                }

                $billTo = new AnetAPI\CustomerAddressType();
                $billTo->setFirstName($user_data->fields['FIRST_NAME']);
                $billTo->setLastName($user_data->fields['LAST_NAME']);
                $billTo->setAddress($user_data->fields['ADDRESS']);
                $billTo->setCity($user_data->fields['CITY']);
                $billTo->setState($user_data->fields['STATE_CODE']);
                $billTo->setZip($user_data->fields['ZIP']);
                $billTo->setCountry($user_data->fields['COUNTRY_CODE']);

                $paymentProfile->setBillTo($billTo);
                $customerProfile->setPaymentProfiles([$paymentProfile]);

                $createProfileRequest = new AnetAPI\CreateCustomerProfileRequest();
                $createProfileRequest->setMerchantAuthentication($merchantAuthentication);
                $createProfileRequest->setProfile($customerProfile);

                //$createProfileRequest->setValidationMode($GATEWAY_MODE == 'live' ? "liveMode" : "testMode");
                $createProfileRequest->setValidationMode("testMode");

                $controller = new AnetController\CreateCustomerProfileController($createProfileRequest);
                $response = $controller->executeWithApiResponse($GATEWAY_MODE == 'live' ? \net\authorize\api\constants\ANetEnvironment::PRODUCTION : \net\authorize\api\constants\ANetEnvironment::SANDBOX);

                if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                    $CUSTOMER_PAYMENT_ID = $response->getCustomerProfileId();

                    $CUSTOMER_PAYMENT_DETAILS = [
                        'PK_USER' => $PK_USER,
                        'CUSTOMER_PAYMENT_ID' => $CUSTOMER_PAYMENT_ID,
                        'PAYMENT_TYPE' => 'Authorized.net',
                        'CREATED_ON' => date("Y-m-d H:i")
                    ];
                    db_perform_account('DOA_CUSTOMER_PAYMENT_INFO', $CUSTOMER_PAYMENT_DETAILS, 'insert');

                    $STATUS = true;
                    $MESSAGE = "Customer and Card Added Successfully";
                } else {
                    $STATUS = false;
                    $MESSAGE = 'Error creating customer profile: ' . $response->getMessages()->getMessage()[0]->getText();
                }
            } catch (Exception $e) {
                $STATUS = false;
                $MESSAGE = 'Exception: ' . $e->getMessage();
            }
        }

        $RETURN_DATA['STATUS'] = $STATUS;
        $RETURN_DATA['MESSAGE'] = $MESSAGE;
        echo json_encode($RETURN_DATA);
        exit;
    }
}
