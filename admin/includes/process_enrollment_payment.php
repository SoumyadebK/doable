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
require_once("../../global/stripe-php/init.php");
global $db;
global $db_account;

$header = '../'.$_POST['header'];

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
$PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];
$SECRET_KEY = $account_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $account_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $account_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $account_data->fields['LOCATION_ID'];

/*$SQUARE_MODE 			= 2;
if ($SQUARE_MODE == 1)
    $SQ_URL = "https://connect.squareup.com";
else if ($SQUARE_MODE == 2)
    $SQ_URL = "https://connect.squareupsandbox.com";

if ($SQUARE_MODE == 1)
    $URL = "https://web.squarecdn.com/v1/square.js";
else if ($SQUARE_MODE == 2)
    $URL = "https://sandbox.web.squarecdn.com/v1/square.js";*/

if(!empty($_POST) && $_POST['FUNCTION_NAME'] == 'confirmEnrollmentPayment') {
    $ENROLLMENT_LEDGER_PARENT = $_POST['PK_ENROLLMENT_LEDGER'];
    $ENROLLMENT_LEDGER_PARENT_ARRAY = explode(',', $ENROLLMENT_LEDGER_PARENT);
    unset($_POST['PK_ENROLLMENT_LEDGER']);

    $ACTUAL_AMOUNT = $_POST['ACTUAL_AMOUNT'] ?? 0;
    $AMOUNT_TO_PAY = $_POST['AMOUNT_TO_PAY'] ?? 0;
    $PARTIAL_AMOUNT = $_POST['PARTIAL_AMOUNT'] ?? 0;
    $REMAINING_AMOUNT = $_POST['REMAINING_AMOUNT'] ?? 0;

    $TOTAL_AMOUNT_PAID = (float)$AMOUNT_TO_PAY + (float)$PARTIAL_AMOUNT;

    $LAST4 = '****';
    $BILLED_AMOUNT = 0.00;
    $AMOUNT_BILLED = $AMOUNT_TO_PAY;
    $RECEIPT_NUMBER_ARRAY = [];

    $PAYMENT_INFO = '';
    $PAYMENT_INFO_JSON = '';
    $PAYMENT_STATUS = 'Success';
    $IS_ORIGINAL_RECEIPT = 1;

    $RECEIPT_NUMBER_ORIGINAL = generateReceiptNumber($_POST['PK_ENROLLMENT_MASTER']);

    if ($_POST['PK_PAYMENT_TYPE'] == 1 || $_POST['PK_PAYMENT_TYPE'] == 14) {
        if ($_POST['PAYMENT_GATEWAY'] == 'Stripe') {
            $user_master = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
            $customer_payment_info = $db_account->Execute("SELECT CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Stripe' AND PK_USER = ".$user_master->fields['PK_USER']);

            $STRIPE_TOKEN = $_POST['token'];
            $CUSTOMER_PAYMENT_ID = '';

            $error['error'] = $STRIPE_TOKEN.' - '.$user_master->fields['PHONE'];
            $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
            db_perform('error_info', $error, 'insert');

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
                }
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Invalid parameters
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $error['error'] = $e;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');
            } catch (\Stripe\Exception\AuthenticationException $e) {
                // Authentication with Stripe's API failed
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $error['error'] = $e;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                // Network communication with Stripe failed
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $error['error'] = $e;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // General API error
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $error['error'] = $e;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');
            } catch (Exception $e) {
                // Other non-Stripe exceptions
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $error['error'] = $e;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');
            }

            $CUSTOMER_PAYMENT_DETAILS['PK_USER'] = $user_master->fields['PK_USER'];
            $CUSTOMER_PAYMENT_DETAILS['CUSTOMER_PAYMENT_ID'] = $CUSTOMER_PAYMENT_ID;
            $CUSTOMER_PAYMENT_DETAILS['PAYMENT_TYPE'] = 'Stripe';
            $CUSTOMER_PAYMENT_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_CUSTOMER_PAYMENT_INFO', $CUSTOMER_PAYMENT_DETAILS, 'insert');


            $LAST4 = '';
            try {
                if (empty($_POST['PAYMENT_METHOD_ID'])) {
                    $card = $stripe->customers->createSource($CUSTOMER_PAYMENT_ID, ['source' => $STRIPE_TOKEN]);
                    $stripe->customers->update($CUSTOMER_PAYMENT_ID, ['default_source' => $card->id]);
                }

                $account = \Stripe\Customer::retrieve($CUSTOMER_PAYMENT_ID);
                $charge = \Stripe\Charge::create(array(
                    "amount" => $AMOUNT_TO_PAY * 100,
                    "currency" => "usd",
                    "description" => "Receipt# ".$RECEIPT_NUMBER_ORIGINAL,
                    "customer" => $CUSTOMER_PAYMENT_ID,
                    "statement_descriptor" => "Receipt# ".$RECEIPT_NUMBER_ORIGINAL,
                ));

                $LAST4 = $charge->payment_method_details->card->last4;
            } catch (\Stripe\Exception\CardException $e) {
                // Card declined or related issue
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $error['error'] = $e;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');
            } catch (\Stripe\Exception\RateLimitException $e) {
                // Too many requests
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $error['error'] = $e;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Invalid parameters
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $error['error'] = $e;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');
            } catch (\Stripe\Exception\AuthenticationException $e) {
                // Authentication error
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $error['error'] = $e;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                // Network communication error
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $error['error'] = $e;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // General API error
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $error['error'] = $e;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');
            } catch (Exception $e) {
                // Non-Stripe exceptions
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();

                $error['error'] = $e;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');
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
                $PAYMENT_INFO_JSON = json_encode($PAYMENT_INFO_ARRAY);
            } else {
                $PAYMENT_STATUS = 'Failed';

                $error['error'] = $PAYMENT_INFO_JSON = $PAYMENT_INFO;
                $error['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                db_perform('error_info', $error, 'insert');

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                echo json_encode($RETURN_DATA); die();
            }
        }

        elseif ($_POST['PAYMENT_GATEWAY'] == 'Square') {
            $sourceId = $_POST['sourceId'];
            require_once("../../global/vendor/autoload.php");

            $client = new SquareClient([
                'accessToken' => $SQUARE_ACCESS_TOKEN,
                'environment' => Environment::SANDBOX,
            ]);

            $user_master = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
            $customer_payment_info = $db_account->Execute("SELECT CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Square' AND PK_USER = ".$user_master->fields['PK_USER']);

            if ($customer_payment_info->RecordCount() > 0) {
                $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
            } else {
                $user_master = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

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
                db_perform_account('DOA_CUSTOMER_PAYMENT_INFO', $SQUARE_DETAILS, 'insert');

            }

            /*$card = new \Square\Models\Card();
            $card->setCardholderName($user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME']);
            //$card->setBillingAddress($billing_address);
            $card->setCustomerId($CUSTOMER_PAYMENT_ID);
            //$card->setReferenceId('user-id-1');

            $body = new \Square\Models\CreateCardRequest(
                uniqid(),
                $sourceId,
                $card
            );

            $api_response = $client->getCardsApi()->createCard($body);*/


            // Create a money object (amount in cents)
            $money = new Money();
            $money->setAmount($AMOUNT_TO_PAY * 100);  // amount in cents
            $money->setCurrency('USD'); // Currency type (USD, EUR, etc.)

            // Create the payment request
            $paymentRequest = new CreatePaymentRequest($sourceId, uniqid(), $money);

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
                } else {
                    $PAYMENT_STATUS = 'Failed';
                    $PAYMENT_INFO = $response->getErrors()[0]->getDetail();
                }
            } catch (\Square\Exceptions\ApiException $e) {
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();
            }
        }

        elseif ($_POST['PAYMENT_GATEWAY'] == 'Authorized.net') {
            $AUTHORIZE_MODE 			= 2;
            $AUTHORIZE_LOGIN_ID 		= $account_data->fields['LOGIN_ID']; //"4Y5pCy8Qr";
            $AUTHORIZE_TRANSACTION_KEY 	= $account_data->fields['TRANSACTION_KEY'];//"4ke43FW8z3287HV5";
            $AUTHORIZE_CLIENT_KEY 		= $account_data->fields['AUTHORIZE_CLIENT_KEY'];//"8ZkyJnT87uFztUz56B4PfgCe7yffEZA4TR5dv8ALjqk5u9mr6d8Nmt8KHyp8s9Ay";


            //$LOGIN_ID = '4Y5pCy8Qr'; //$account_data->fields['LOGIN_ID'];
            //$TRANSACTION_KEY = '8ZkyJnT87uFztUz56B4PfgCe7yffEZA4TR5dv8ALjqk5u9mr6d8Nmt8KHyp8s9Ay'; // $account_data->fields['TRANSACTION_KEY'];

            // Product Details
            $itemName = $_POST['PK_ENROLLMENT_MASTER'];
            $itemNumber = $_POST['PK_ENROLLMENT_BILLING'];
            $itemPrice = $AMOUNT_TO_PAY;
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
            $merchantAuthentication->setName($AUTHORIZE_LOGIN_ID);
            $merchantAuthentication->setTransactionKey($AUTHORIZE_TRANSACTION_KEY);

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

            try {
                if($AUTHORIZE_MODE == 1)
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
                    }
                } else {
                    $PAYMENT_STATUS = 'Failed';
                    $PAYMENT_INFO = "Transaction Failed";
                }
            } catch (\Square\Exceptions\ApiException $e) {
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();
            }
        }

    }

    elseif ($_POST['PK_PAYMENT_TYPE'] == 7) {
        $IS_ORIGINAL_RECEIPT = 0;
        $WALLET_BALANCE = $_POST['WALLET_BALANCE'];

        $PK_USER_MASTER = $_POST['PK_USER_MASTER'];
        $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID, MISC_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = ".$_POST['PK_ENROLLMENT_MASTER']);
        if(empty($enrollment_data->fields['ENROLLMENT_ID'])) {
            $enrollment_id = $enrollment_data->fields['MISC_ID'];
        } else {
            $enrollment_id = $enrollment_data->fields['ENROLLMENT_ID'];
        }

        $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
        $DEBIT_AMOUNT = ($WALLET_BALANCE > $AMOUNT_TO_PAY) ? $AMOUNT_TO_PAY : $WALLET_BALANCE;
        if ($wallet_data->RecordCount() > 0) {
            $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] - $DEBIT_AMOUNT;
        }

        $wallet_transaction = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE BALANCE_LEFT > 0 AND PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET ASC");
        while (!$wallet_transaction->EOF) {
            $PK_CUSTOMER_WALLET = $wallet_transaction->fields['PK_CUSTOMER_WALLET'];
            $BALANCE_LEFT = $wallet_transaction->fields['BALANCE_LEFT'] - $AMOUNT_BILLED;
            if ($BALANCE_LEFT >= 0) {
                $WALLET_UPDATE_DATA['BALANCE_LEFT'] = $BALANCE_LEFT;
                db_perform_account('DOA_CUSTOMER_WALLET', $WALLET_UPDATE_DATA, 'update', " PK_CUSTOMER_WALLET =  '$PK_CUSTOMER_WALLET'");
                $RECEIPT_NUMBER_ARRAY[] = $wallet_transaction->fields['RECEIPT_NUMBER'];
                break;
            } else {
                $WALLET_UPDATE_DATA['BALANCE_LEFT'] = 0;
                db_perform_account('DOA_CUSTOMER_WALLET', $WALLET_UPDATE_DATA, 'update', " PK_CUSTOMER_WALLET =  '$PK_CUSTOMER_WALLET'");
                $RECEIPT_NUMBER_ARRAY[] = $wallet_transaction->fields['RECEIPT_NUMBER'];
                $AMOUNT_BILLED -= $wallet_transaction->fields['BALANCE_LEFT'];
            }
            $wallet_transaction->MoveNext();
        }

        $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
        $INSERT_DATA['DEBIT'] = $DEBIT_AMOUNT;
        $INSERT_DATA['DESCRIPTION'] = "Balance debited for payment of enrollment ".$enrollment_id;
        $INSERT_DATA['RECEIPT_NUMBER'] = implode(',', $RECEIPT_NUMBER_ARRAY);
        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');

        $RECEIPT_NUMBER = implode(',', $RECEIPT_NUMBER_ARRAY);

        $PAYMENT_INFO_ARRAY = ['RECEIPT_NUMBER' => $RECEIPT_NUMBER];
        $PAYMENT_INFO_JSON = json_encode($PAYMENT_INFO_ARRAY);
    } else {
        $PAYMENT_INFO_JSON = 'Payment Done.';
    }

    $enrollmentServiceData = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = ".$_POST['PK_ENROLLMENT_MASTER']);
    $enrollmentBillingData = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER` = ".$_POST['PK_ENROLLMENT_MASTER']);
    $ACTUAL_AMOUNT = $enrollmentBillingData->fields['TOTAL_AMOUNT'];
    while (!$enrollmentServiceData->EOF) {
        $servicePercent = ($enrollmentServiceData->fields['FINAL_AMOUNT']*100)/$ACTUAL_AMOUNT;
        $serviceAmount = ($TOTAL_AMOUNT_PAID * $servicePercent)/100;

        $ENROLLMENT_SERVICE_UPDATE_DATA['TOTAL_AMOUNT_PAID'] = $enrollmentServiceData->fields['TOTAL_AMOUNT_PAID']+$serviceAmount;
        db_perform_account('DOA_ENROLLMENT_SERVICE', $ENROLLMENT_SERVICE_UPDATE_DATA, 'update'," PK_ENROLLMENT_SERVICE = ".$enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE']);

        markAppointmentPaid($enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE']);

        $enrollmentServiceData->MoveNext();
    }

    savePercentageData($_POST['PK_ENROLLMENT_MASTER'], $TOTAL_AMOUNT_PAID);

    $enrollment_billing_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_BILLING WHERE PK_ENROLLMENT_MASTER=".$_POST['PK_ENROLLMENT_MASTER']);

    if (count($RECEIPT_NUMBER_ARRAY) > 0) {
        $RECEIPT_NUMBER = implode(',', $RECEIPT_NUMBER_ARRAY);
    } else {
        $RECEIPT_NUMBER = $RECEIPT_NUMBER_ORIGINAL;
    }

    for ($i = 0; $i < count($ENROLLMENT_LEDGER_PARENT_ARRAY); $i++) {
        $ledger_data = $db_account->Execute("SELECT `BILLED_AMOUNT`, `DUE_DATE` FROM `DOA_ENROLLMENT_LEDGER` WHERE `PK_ENROLLMENT_LEDGER` = ".$ENROLLMENT_LEDGER_PARENT_ARRAY[$i]);
        if (count($ENROLLMENT_LEDGER_PARENT_ARRAY) > 1) {
            $PAID_AMOUNT = $ledger_data->fields['BILLED_AMOUNT'];
        } else {
            $PAID_AMOUNT = $AMOUNT_TO_PAY;
        }

        if ($AMOUNT_TO_PAY > 0) {
            /*$LEDGER_DATA_PAYMENT['TRANSACTION_TYPE'] = 'Payment';
            $LEDGER_DATA_PAYMENT['ENROLLMENT_LEDGER_PARENT'] = $ENROLLMENT_LEDGER_PARENT_ARRAY[$i];
            $LEDGER_DATA_PAYMENT['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
            $LEDGER_DATA_PAYMENT['PK_ENROLLMENT_BILLING'] = $enrollment_billing_data->fields['PK_ENROLLMENT_BILLING'];
            $LEDGER_DATA_PAYMENT['DUE_DATE'] = date('Y-m-d');
            $LEDGER_DATA_PAYMENT['BILLED_AMOUNT'] = 0.00;
            $LEDGER_DATA_PAYMENT['PAID_AMOUNT'] = $PAID_AMOUNT;
            $LEDGER_DATA_PAYMENT['BALANCE'] = 0.00;
            $LEDGER_DATA_PAYMENT['IS_PAID'] = 1;
            $LEDGER_DATA_PAYMENT['STATUS'] = 'A';
            db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA_PAYMENT, 'insert');
            $PK_ENROLLMENT_LEDGER1 = $db_account->insert_ID();*/

            $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
            $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $enrollment_billing_data->fields['PK_ENROLLMENT_BILLING'];
            $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
            $PAYMENT_DATA['AMOUNT'] = $PAID_AMOUNT;
            $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $ENROLLMENT_LEDGER_PARENT_ARRAY[$i];
            $TYPE = 'Payment';
            if ($_POST['PK_PAYMENT_TYPE'] == 2) {
                $PAYMENT_INFO_ARRAY = ['CHECK_NUMBER' => $_POST['CHECK_NUMBER'], 'CHECK_DATE' => date('Y-m-d', strtotime($_POST['CHECK_DATE']))];
                $PAYMENT_INFO_JSON = json_encode($PAYMENT_INFO_ARRAY);
            }
            $PAYMENT_DATA['TYPE'] = $TYPE;
            $PAYMENT_DATA['NOTE'] = $_POST['NOTE'];
            $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
            $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO_JSON;
            $PAYMENT_DATA['PAYMENT_STATUS'] = $PAYMENT_STATUS;
            $PAYMENT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
            $PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = $IS_ORIGINAL_RECEIPT;
            db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');
        }

        if (isset($_POST['PARTIAL_PAYMENT']) && $PARTIAL_AMOUNT > 0) {
            /*$LEDGER_DATA_PAYMENT['TRANSACTION_TYPE'] = 'Payment';
            $LEDGER_DATA_PAYMENT['ENROLLMENT_LEDGER_PARENT'] = $ENROLLMENT_LEDGER_PARENT_ARRAY[$i];
            $LEDGER_DATA_PAYMENT['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
            $LEDGER_DATA_PAYMENT['PK_ENROLLMENT_BILLING'] = $enrollment_billing_data->fields['PK_ENROLLMENT_BILLING'];
            $LEDGER_DATA_PAYMENT['DUE_DATE'] = date('Y-m-d');
            $LEDGER_DATA_PAYMENT['BILLED_AMOUNT'] = 0.00;
            $LEDGER_DATA_PAYMENT['PAID_AMOUNT'] = $AMOUNT_TO_PAY;
            $LEDGER_DATA_PAYMENT['BALANCE'] = 0.00;
            $LEDGER_DATA_PAYMENT['IS_PAID'] = 1;
            $LEDGER_DATA_PAYMENT['STATUS'] = 'A';
            db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA_PAYMENT, 'insert');
            $PK_ENROLLMENT_LEDGER2 = $db_account->insert_ID();*/

            $PAYMENT_INFO_JSON = '';
            $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
            $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $enrollment_billing_data->fields['PK_ENROLLMENT_BILLING'];
            $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE_PARTIAL'];
            $PAYMENT_DATA['AMOUNT'] = $PARTIAL_AMOUNT;
            $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $ENROLLMENT_LEDGER_PARENT_ARRAY[$i];
            $TYPE = 'Payment';
            if ($_POST['PK_PAYMENT_TYPE_PARTIAL'] == 2) {
                $PAYMENT_INFO_ARRAY = ['CHECK_NUMBER' => $_POST['CHECK_NUMBER_PARTIAL'], 'CHECK_DATE' => date('Y-m-d', strtotime($_POST['CHECK_DATE_PARTIAL']))];
                $PAYMENT_INFO_JSON = json_encode($PAYMENT_INFO_ARRAY);
            }
            $PAYMENT_DATA['TYPE'] = $TYPE;
            $PAYMENT_DATA['NOTE'] = $_POST['NOTE'];
            $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
            $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO_JSON;
            $PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
            $PAYMENT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER_ORIGINAL;
            $PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = 1;
            db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');
        }

        if ($REMAINING_AMOUNT > 0) {
            /*$LEDGER_DATA_BILLING['TRANSACTION_TYPE'] = 'Billing';
            $LEDGER_DATA_BILLING['ENROLLMENT_LEDGER_PARENT'] = 0;
            $LEDGER_DATA_BILLING['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
            $LEDGER_DATA_BILLING['PK_ENROLLMENT_BILLING'] = $enrollment_billing_data->fields['PK_ENROLLMENT_BILLING'];
            $LEDGER_DATA_BILLING['PAID_AMOUNT'] = 0.00;
            $LEDGER_DATA_BILLING['IS_PAID'] = 0;
            $LEDGER_DATA_BILLING['DUE_DATE'] = $ledger_data->fields['DUE_DATE'];
            $LEDGER_DATA_BILLING['BILLED_AMOUNT'] = $REMAINING_AMOUNT;
            $LEDGER_DATA_BILLING['BALANCE'] = $REMAINING_AMOUNT;
            $LEDGER_DATA_BILLING['STATUS'] = 'A';
            db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA_BILLING, 'insert');*/

            $LEDGER_UPDATE_DATA['AMOUNT_REMAIN'] = $REMAINING_AMOUNT;
            $LEDGER_UPDATE_DATA['IS_PAID'] = 0;
        } else {
            $LEDGER_UPDATE_DATA['AMOUNT_REMAIN'] = 0;
            $LEDGER_UPDATE_DATA['IS_PAID'] = ($PAYMENT_STATUS == 'Failed') ? 0 : 1;
        }
        $LEDGER_UPDATE_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_UPDATE_DATA, 'update', " PK_ENROLLMENT_LEDGER =  '$ENROLLMENT_LEDGER_PARENT_ARRAY[$i]'");
    }

    markEnrollmentComplete($_POST['PK_ENROLLMENT_MASTER']);

    $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
    $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
    echo json_encode($RETURN_DATA);

    //header('location:'.$header);
}
function savePercentageData($PK_ENROLLMENT_MASTER, $AMOUNT){
    global $db_account;
    $row = $db_account->Execute("SELECT SERVICE_PROVIDER_ID, SERVICE_PROVIDER_PERCENTAGE FROM DOA_ENROLLMENT_SERVICE_PROVIDER WHERE PK_ENROLLMENT_MASTER=".$PK_ENROLLMENT_MASTER);
    while (!$row->EOF) {
        $PERCENTAGE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
        $PERCENTAGE_DATA['SERVICE_PROVIDER_ID'] = $row->fields['SERVICE_PROVIDER_ID'];
        $PERCENTAGE_DATA['PERCENTAGE_AMOUNT'] = ($AMOUNT * $row->fields['SERVICE_PROVIDER_PERCENTAGE']) / 100;
        db_perform_account('DOA_SERVICE_PROVIDER_AMOUNT', $PERCENTAGE_DATA, 'insert');
        $row->MoveNext();
    }
}