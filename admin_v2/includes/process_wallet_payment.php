<?php

use Mpdf\Mpdf;
use Square\Environment;
use Square\Models\CreatePaymentRequest;
use Square\Models\Money;
use Square\SquareClient;
use Stripe\Stripe;
use Stripe\StripeClient;

require_once('../../global/authorizenet/autoload.php');

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;


require_once('../../global/config.php');
require_once("../../global/stripe-php-master/init.php");
global $db;
global $db_account;

$header = '../' . $_POST['header'];

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

/*$SQUARE_MODE 			= 2;
if ($SQUARE_MODE == 1)
    $SQ_URL = "https://connect.squareup.com";
else if ($SQUARE_MODE == 2)
    $SQ_URL = "https://connect.squareupsandbox.com";

if ($SQUARE_MODE == 1)
    $URL = "https://web.squarecdn.com/v1/square.js";
else if ($SQUARE_MODE == 2)
    $URL = "https://sandbox.web.squarecdn.com/v1/square.js";*/

if (!empty($_POST) && $_POST['FUNCTION_NAME'] == 'processWalletPayment') {
    $RECEIPT_NUMBER = generateReceiptNumber(0);
    $PAYMENT_INFO = '';
    $PAYMENT_STATUS = 'Success';
    $AMOUNT = $_POST['AMOUNT'];

    if ($_POST['PK_PAYMENT_TYPE'] == 1 || $_POST['PK_PAYMENT_TYPE'] == 14) {
        if ($_POST['PAYMENT_GATEWAY'] == 'Stripe') {
            $user_master = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
            $customer_payment_info = $db_account->Execute("SELECT CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Stripe' AND PK_USER = " . $user_master->fields['PK_USER']);

            $STRIPE_TOKEN = $_POST['token'];
            $CUSTOMER_PAYMENT_ID = '';
            try {
                $stripe = new StripeClient($SECRET_KEY);
                Stripe::setApiKey($SECRET_KEY);

                if ($customer_payment_info->RecordCount() > 0) {
                    $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
                } else {
                    $customer = $stripe->customers->create([
                        'email' => $user_master->fields['EMAIL_ID'],
                        'name' => $user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME'],
                        'phone' => $user_master->fields['PHONE'],
                        'description' => $user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME'],
                    ]);
                    $CUSTOMER_PAYMENT_ID = $customer->id;

                    $STRIPE_DETAILS['PK_USER']  = $user_master->fields['PK_USER'];
                    $STRIPE_DETAILS['CUSTOMER_PAYMENT_ID'] = $CUSTOMER_PAYMENT_ID;
                    $STRIPE_DETAILS['PAYMENT_TYPE'] = 'Stripe';
                    $STRIPE_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_CUSTOMER_PAYMENT_INFO', $STRIPE_DETAILS, 'insert');
                }
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Invalid parameters
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            } catch (\Stripe\Exception\AuthenticationException $e) {
                // Authentication with Stripe's API failed
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                // Network communication with Stripe failed
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // General API error
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            } catch (Exception $e) {
                // Other non-Stripe exceptions
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            }

            $LAST4 = '';
            try {

                if (empty($_POST['PAYMENT_METHOD_ID'])) {
                    $card = $stripe->customers->createSource($CUSTOMER_PAYMENT_ID, ['source' => $STRIPE_TOKEN]);
                    $stripe->customers->update($CUSTOMER_PAYMENT_ID, ['default_source' => $card->id]);
                } else {
                    $stripe->customers->update($CUSTOMER_PAYMENT_ID, ['default_source' => $_POST['PAYMENT_METHOD_ID']]);
                }

                $account = \Stripe\Customer::retrieve($CUSTOMER_PAYMENT_ID);
                $charge = \Stripe\Charge::create(array(
                    "amount" => $AMOUNT * 100,
                    "currency" => "usd",
                    "description" => "Receipt# " . $RECEIPT_NUMBER,
                    "customer" => $CUSTOMER_PAYMENT_ID,
                    "statement_descriptor" => "Receipt# " . $RECEIPT_NUMBER,
                ));

                $LAST4 = $charge->payment_method_details->card->last4;

                if (!isset($_POST['SAVE_FOR_FUTURE'])) {
                    $stripe->customers->deleteSource($CUSTOMER_PAYMENT_ID, $charge->payment_method);
                }
            } catch (\Stripe\Exception\CardException $e) {
                // Card declined or related issue
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            } catch (\Stripe\Exception\RateLimitException $e) {
                // Too many requests
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Invalid parameters
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            } catch (\Stripe\Exception\AuthenticationException $e) {
                // Authentication error
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                // Network communication error
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // General API error
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            } catch (Exception $e) {
                // Non-Stripe exceptions
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            }

            register_shutdown_function(function () {
                $error = error_get_last();
                if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
                    $error['error'] = "Fatal Error: " . $error['message'];
                    $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                    db_perform('error_info', $error, 'insert');
                }
            });

            if (isset($charge) && $charge->paid == 1) {
                $PAYMENT_STATUS = 'Success';
                $PAYMENT_INFO_ARRAY = ['CHARGE_ID' => $charge->id, 'LAST4' => $LAST4];
                $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
            } else {
                $PAYMENT_STATUS = 'Failed';

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            }
        } elseif ($_POST['PAYMENT_GATEWAY'] == 'Square') {
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

            $user_master = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE, DOA_USERS.ADDRESS, DOA_USERS.ADDRESS_1, DOA_USERS.CITY, DOA_COUNTRY.COUNTRY_CODE, DOA_STATES.STATE_CODE, DOA_USERS.ZIP FROM `DOA_USERS` LEFT JOIN DOA_COUNTRY ON DOA_USERS.PK_COUNTRY = DOA_COUNTRY.PK_COUNTRY LEFT JOIN DOA_STATES ON DOA_USERS.PK_STATES = DOA_STATES.PK_STATES LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
            // Get or create Square customer
            $customer_payment_info = $db_account->Execute("SELECT CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Square' AND PK_USER = " . $user_master->fields['PK_USER']);
            if ($customer_payment_info->RecordCount() > 0) {
                $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
            } else {
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

                try {
                    $api_response = $client->getCustomersApi()->createCustomer($body);
                } catch (\Square\Exceptions\ApiException $e) {
                    $RETURN_DATA['STATUS'] = 'Failed';
                    $RETURN_DATA['PAYMENT_INFO'] = $e->getMessage();
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

            $sourceId = $_POST['token'];

            // Determine which card source to use
            if (!empty($_POST['PAYMENT_METHOD_ID'])) {
                $CUSTOMER_CARD_ID = $_POST['PAYMENT_METHOD_ID'];
            } else {
                $CUSTOMER_CARD_ID = $sourceId;
            }

            // Create money object
            $money = new Money();
            $money->setAmount($AMOUNT * 100);
            $money->setCurrency('USD');

            // Create payment request
            $paymentRequest = new CreatePaymentRequest($CUSTOMER_CARD_ID, uniqid(), $money);
            $paymentRequest->setCustomerId($CUSTOMER_PAYMENT_ID);

            // Create payment using the Square API
            $paymentsApi = $client->getPaymentsApi();
            try {
                $response = $paymentsApi->createPayment($paymentRequest);
                if ($response->isSuccess()) {
                    $paymentId = $response->getResult()->getPayment()->getId();
                    $last4Digits = $response->getResult()->getPayment()->getCardDetails()->getCard()->getLast4();

                    $PAYMENT_STATUS = 'Success';
                    $PAYMENT_INFO_ARRAY = ['CHARGE_ID' => $paymentId, 'LAST4' => $last4Digits];
                    $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);

                    $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                    $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                } else {
                    $PAYMENT_STATUS = 'Failed';
                    $PAYMENT_INFO = $response->getErrors()[0]->getDetail();

                    $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                    $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                    echo json_encode($RETURN_DATA);
                    die();
                }
            } catch (\Square\Exceptions\ApiException $e) {
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            }
        } elseif ($_POST['PAYMENT_GATEWAY'] == 'Authorized.net') {
            $user_master = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE, DOA_USERS.ZIP FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
            $customer_payment_info = $db_account->Execute("SELECT CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Authorized.net' AND PK_USER = " . $user_master->fields['PK_USER']);

            // Product Details
            $itemName = 0;
            $itemNumber = 0;
            $itemPrice = $AMOUNT;
            $currency = "USD";

            $refID = 'ref' . time();

            $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
            $merchantAuthentication->setName($AUTHORIZE_LOGIN_ID);
            $merchantAuthentication->setTransactionKey($AUTHORIZE_TRANSACTION_KEY);

            if (!empty($_POST['PAYMENT_METHOD_ID'])) {
                // Set payment using saved profile
                $profileToCharge = new AnetAPI\CustomerProfilePaymentType();
                $profileToCharge->setCustomerProfileId($customer_payment_info->fields['CUSTOMER_PAYMENT_ID']);

                $paymentProfile = new AnetAPI\PaymentProfileType();
                $paymentProfile->setPaymentProfileId($_POST['PAYMENT_METHOD_ID']);
                $profileToCharge->setPaymentProfile($paymentProfile);
            } else {
                // Retrieve card and user info from the submitted form data
                $name = $_POST['NAME'];
                $email = $_POST['EMAIL'];
                $card_number = preg_replace('/\s+/', '', $_POST['CARD_NUMBER']);
                $card_exp_month = $_POST['EXPIRATION_MONTH'];
                $card_exp_year = $_POST['EXPIRATION_YEAR'];
                $card_exp_year_month = $card_exp_year . '-' . sprintf('%02d', $card_exp_month);
                $card_cvc = $_POST['SECURITY_CODE'];

                // Create the payment data for a credit card
                $creditCard = new AnetAPI\CreditCardType();
                $creditCard->setCardNumber($card_number);
                $creditCard->setExpirationDate($card_exp_year_month);
                $creditCard->setCardCode($card_cvc);

                // Add the payment data to a paymentType object
                $paymentOne = new AnetAPI\PaymentType();
                $paymentOne->setCreditCard($creditCard);


                if (isset($_POST['SAVE_FOR_FUTURE'])) {
                    // Create Payment Profile
                    $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
                    $paymentProfile->setCustomerType('individual');
                    $paymentProfile->setPayment($paymentOne);

                    if ($customer_payment_info->RecordCount() > 0) {
                        // Existing customer profile
                        $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];

                        $createPaymentProfileRequest = new AnetAPI\CreateCustomerPaymentProfileRequest();
                        $createPaymentProfileRequest->setMerchantAuthentication($merchantAuthentication);
                        $createPaymentProfileRequest->setCustomerProfileId($CUSTOMER_PAYMENT_ID);
                        $createPaymentProfileRequest->setPaymentProfile($paymentProfile);

                        if ($GATEWAY_MODE == 'live')
                            $createPaymentProfileRequest->setValidationMode("liveMode");
                        else
                            $createPaymentProfileRequest->setValidationMode("testMode"); // Use 'liveMode' in production

                        $controller = new AnetController\CreateCustomerPaymentProfileController($createPaymentProfileRequest);

                        if ($GATEWAY_MODE == 'live')
                            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                        else
                            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

                        if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                            $PAYMENT_PROFILE_ID = $response->getCustomerPaymentProfileId();
                            //echo "Payment profile created successfully: " . $PAYMENT_PROFILE_ID;
                        } else {
                            $PAYMENT_STATUS = 'Failed';
                            $PAYMENT_INFO = "Error saving card: " . $response->getMessages()->getMessage()[0]->getText();

                            $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                            $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                            echo json_encode($RETURN_DATA);
                            die();
                        }
                    } else {
                        $customerProfile = new AnetAPI\CustomerProfileType();
                        $customerProfile->setMerchantCustomerId("USER_ID_" . $user_master->fields['PK_USER']);
                        $customerProfile->setEmail($user_master->fields['EMAIL_ID']);

                        $billTo = new AnetAPI\CustomerAddressType();
                        $billTo->setFirstName($user_master->fields['FIRST_NAME']);
                        $billTo->setLastName($user_master->fields['LAST_NAME']);
                        $billTo->setZip($user_master->fields['ZIP']);
                        $billTo->setCountry("US");
                        $paymentProfile->setBillTo($billTo);

                        $customerProfile->setPaymentProfiles([$paymentProfile]);

                        $createProfileRequest = new AnetAPI\CreateCustomerProfileRequest();
                        $createProfileRequest->setMerchantAuthentication($merchantAuthentication);
                        $createProfileRequest->setProfile($customerProfile);

                        if ($GATEWAY_MODE == 'live')
                            $createProfileRequest->setValidationMode("liveMode");
                        else
                            $createProfileRequest->setValidationMode("testMode");

                        $controller = new AnetController\CreateCustomerProfileController($createProfileRequest);

                        if ($GATEWAY_MODE == 'live')
                            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                        else
                            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

                        if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                            $CUSTOMER_PAYMENT_ID = $response->getCustomerProfileId();
                            $PAYMENT_PROFILE_ID = $response->getCustomerPaymentProfileIdList()[0];

                            // Save the customer profile ID in the database
                            $CUSTOMER_PAYMENT_DETAILS['PK_USER'] = $user_master->fields['PK_USER'];
                            $CUSTOMER_PAYMENT_DETAILS['CUSTOMER_PAYMENT_ID'] = $CUSTOMER_PAYMENT_ID;
                            $CUSTOMER_PAYMENT_DETAILS['PAYMENT_TYPE'] = 'Authorized.net';
                            $CUSTOMER_PAYMENT_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
                            db_perform_account('DOA_CUSTOMER_PAYMENT_INFO', $CUSTOMER_PAYMENT_DETAILS, 'insert');
                        } else {
                            $PAYMENT_STATUS = 'Failed';
                            $PAYMENT_INFO = "Error creating customer profile: " . $response->getMessages()->getMessage()[0]->getText();

                            $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                            $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                            echo json_encode($RETURN_DATA);
                            die();
                        }
                    }
                    $profileToCharge = new AnetAPI\CustomerProfilePaymentType();
                    $profileToCharge->setCustomerProfileId($CUSTOMER_PAYMENT_ID);
                }
            }

            // Create order information
            $order = new AnetAPI\OrderType();
            $order->setDescription($itemName);

            // Create a transaction
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($itemPrice);
            $transactionRequestType->setOrder($order);

            if (!empty($_POST['PAYMENT_METHOD_ID'])) {
                $transactionRequestType->setProfile($profileToCharge);
            } else {
                $transactionRequestType->setPayment($paymentOne);
            }

            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setRefId($refID);
            $request->setTransactionRequest($transactionRequestType);

            $controller = new AnetController\CreateTransactionController($request);

            try {
                if ($GATEWAY_MODE == 'live')
                    $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                else
                    $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

                if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                    $tresponse = $response->getTransactionResponse();

                    if ($tresponse != null && $tresponse->getMessages() != null) {
                        $PAYMENT_STATUS = 'Success';
                        $PAYMENT_INFO_ARRAY = ['CHARGE_ID' => $tresponse->getTransId(), 'LAST4' => $tresponse->getaccountNumber()];
                        $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
                    } else {
                        $PAYMENT_STATUS = 'Failed';
                        $PAYMENT_INFO = $tresponse->getErrors()[0]->getErrorCode();

                        $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                        $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                        echo json_encode($RETURN_DATA);
                        die();
                    }
                } else {
                    $PAYMENT_STATUS = 'Failed';
                    $PAYMENT_INFO = "Transaction Failed";

                    $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                    $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                    echo json_encode($RETURN_DATA);
                    die();
                }
            } catch (\Square\Exceptions\ApiException $e) {
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA);
                die();
            }
        }
    }

    if ($_POST['PK_PAYMENT_TYPE'] >= 1) {
        $payment_type = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE PK_PAYMENT_TYPE = " . $_POST['PK_PAYMENT_TYPE']);
        $PK_USER_MASTER = $_POST['PK_USER_MASTER'];
        $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
        if ($wallet_data->RecordCount() > 0) {
            $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] + $AMOUNT;
        } else {
            $INSERT_DATA['CURRENT_BALANCE'] = $AMOUNT;
        }
        $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
        $INSERT_DATA['DEBIT'] = 0;
        $INSERT_DATA['CREDIT'] = $AMOUNT;
        $INSERT_DATA['BALANCE_LEFT'] = $AMOUNT;
        $INSERT_DATA['DESCRIPTION'] = "Amount Credited to Your Wallet using " . $payment_type->fields['PAYMENT_TYPE'];
        $INSERT_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        $INSERT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
        $INSERT_DATA['NOTE'] = $_POST['NOTE'];
        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');
        $PK_CUSTOMER_WALLET = $db_account->Insert_ID();

        $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
        $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = 0;
        $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        $PAYMENT_DATA['AMOUNT'] = $AMOUNT;
        $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = 0;
        $TYPE = 'Wallet';
        if ($_POST['PK_PAYMENT_TYPE'] == 2) {
            $PAYMENT_INFO_ARRAY = ['CHECK_NUMBER' => $_POST['CHECK_NUMBER'], 'CHECK_DATE' => date('Y-m-d', strtotime($_POST['CHECK_DATE']))];
            $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
        }
        $PAYMENT_DATA['PK_CUSTOMER_WALLET'] = $PK_CUSTOMER_WALLET;
        $PAYMENT_DATA['PK_LOCATION'] = getPkLocation();
        $PAYMENT_DATA['TYPE'] = $TYPE;
        $PAYMENT_DATA['NOTE'] = $_POST['NOTE'];
        $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
        $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
        $PAYMENT_DATA['PAYMENT_STATUS'] = $PAYMENT_STATUS;
        $PAYMENT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
        $PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = 1;

        db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');
    }

    $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
    $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
    echo json_encode($RETURN_DATA);
}
