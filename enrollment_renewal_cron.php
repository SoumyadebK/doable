<?php
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    require_once("global/config.php");
    require_once("global/vendor/twilio/sdk/src/Twilio/autoload.php");
    require_once("global/authorizenet/autoload.php");
    require_once("global/vendor/autoload.php");
    require_once("global/stripe-php/init.php");
} else {
    require_once("/var/www/html/global/config.php");
    require_once("/var/www/html/global/vendor/twilio/sdk/src/Twilio/autoload.php");
    require_once("/var/www/html/global/authorizenet/autoload.php");
    require_once("/var/www/html/global/vendor/autoload.php");
    require_once("/var/www/html/global/stripe-php/init.php");
}

use Square\Environment;
use Square\Models\CreatePaymentRequest;
use Square\Models\Money;
use Square\SquareClient;

use Stripe\Stripe;
use Stripe\StripeClient;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

global $db;
$all_location = $db->Execute("SELECT DOA_LOCATION.*, DOA_ACCOUNT_MASTER.DB_NAME FROM DOA_LOCATION LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER WHERE PK_LOCATION = 13 AND (DOA_LOCATION.PAYMENT_GATEWAY_TYPE IS NOT NULL AND DOA_LOCATION.PAYMENT_GATEWAY_TYPE != '') AND DOA_ACCOUNT_MASTER.ACTIVE = 1 AND DOA_LOCATION.ACTIVE = 1");
while (!$all_location->EOF) {
    try {
        $DB_NAME = $all_location->fields['DB_NAME'];
        $db1 = new queryFactory();
        if ($_SERVER['HTTP_HOST'] == 'localhost') {
            $conn1 = $db1->connect('localhost', 'root', '', $DB_NAME);
            $http_path = 'http://localhost/doable/';
        } else {
            $conn1 = $db1->connect('localhost', 'root', 'b54eawxj5h8ev', $DB_NAME);
            $http_path = 'https://doable.net/';
        }
        if ($db1->error_number) {
            die("Connection Error");
        }

        $PK_LOCATION = $all_location->fields['PK_LOCATION'];
        $PK_ACCOUNT_MASTER = $all_location->fields['PK_ACCOUNT_MASTER'];

        $PAYMENT_GATEWAY = $all_location->fields['PAYMENT_GATEWAY_TYPE'];
        $GATEWAY_MODE  = $all_location->fields['GATEWAY_MODE'];

        $SECRET_KEY = $all_location->fields['SECRET_KEY'];
        $PUBLISHABLE_KEY = $all_location->fields['PUBLISHABLE_KEY'];

        $SQUARE_ACCESS_TOKEN = $all_location->fields['ACCESS_TOKEN'];
        $SQUARE_APP_ID = $all_location->fields['APP_ID'];
        $SQUARE_LOCATION_ID = $all_location->fields['LOCATION_ID'];

        $AUTHORIZE_LOGIN_ID = $all_location->fields['LOGIN_ID'];
        $AUTHORIZE_TRANSACTION_KEY = $all_location->fields['TRANSACTION_KEY'];
        $AUTHORIZE_CLIENT_KEY = $all_location->fields['AUTHORIZE_CLIENT_KEY'];

        $enrollment_data = $db1->Execute("SELECT * FROM DOA_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER RIGHT JOIN DOA_ENROLLMENT_LEDGER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.STATUS = 'A' AND DOA_ENROLLMENT_MASTER.ACTIVE_AUTO_PAY = 1 AND DOA_ENROLLMENT_MASTER.PK_LOCATION = '$PK_LOCATION' AND (DOA_ENROLLMENT_BILLING.PAYMENT_METHOD = 'Payment Plans' OR DOA_ENROLLMENT_BILLING.PAYMENT_METHOD = 'Flexible Payments') AND DOA_ENROLLMENT_LEDGER.DUE_DATE = '" . date('Y-m-d') . "' AND DOA_ENROLLMENT_LEDGER.IS_PAID = 0");
        while (!$enrollment_data->EOF) {
            $PAYMENT_STATUS = 'Failed';
            $PAYMENT_INFO_JSON = '';
            $PK_USER_MASTER = $enrollment_data->fields['PK_USER_MASTER'];
            $PAYMENT_METHOD_ID = $enrollment_data->fields['PAYMENT_METHOD_ID'];
            $AMOUNT_TO_PAY = $TOTAL_AMOUNT_PAID = ($enrollment_data->fields['AMOUNT_REMAIN'] > 0) ? $enrollment_data->fields['AMOUNT_REMAIN'] : $enrollment_data->fields['BILLED_AMOUNT'];

            $PK_ENROLLMENT_MASTER = $enrollment_data->fields['PK_ENROLLMENT_MASTER'];
            $PK_ENROLLMENT_BILLING = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
            $PK_ENROLLMENT_LEDGER = $enrollment_data->fields['PK_ENROLLMENT_LEDGER'];

            $RECEIPT_NUMBER_ORIGINAL = generateReceiptNumber($PK_ENROLLMENT_MASTER);

            $user_master = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$PK_USER_MASTER'");

            if ($PAYMENT_GATEWAY == 'Stripe') {
                $customer_payment_info = $db1->Execute("SELECT CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Stripe' AND PK_USER = " . $user_master->fields['PK_USER']);
                $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];

                $LAST4 = '';
                try {
                    $stripe = new StripeClient($SECRET_KEY);
                    Stripe::setApiKey($SECRET_KEY);

                    $stripe->customers->update($CUSTOMER_PAYMENT_ID, ['default_source' => $PAYMENT_METHOD_ID]);

                    $account = \Stripe\Customer::retrieve($CUSTOMER_PAYMENT_ID);
                    $charge = \Stripe\Charge::create(array(
                        "amount" => $AMOUNT_TO_PAY * 100,
                        "currency" => "usd",
                        "description" => "Receipt# " . $RECEIPT_NUMBER_ORIGINAL,
                        "customer" => $CUSTOMER_PAYMENT_ID,
                        "statement_descriptor" => "Receipt# " . $RECEIPT_NUMBER_ORIGINAL,
                    ));

                    $LAST4 = $charge->payment_method_details->card->last4;
                } catch (\Stripe\Exception\CardException $e) {
                    // Card declined or related issue
                    $PAYMENT_STATUS = 'Failed';
                    $PAYMENT_INFO = $e->getMessage();

                    $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                    $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                    echo json_encode($RETURN_DATA);
                }

                if (isset($charge) && $charge->paid == 1) {
                    $PAYMENT_STATUS = 'Success';
                    $PAYMENT_INFO_ARRAY = ['CHARGE_ID' => $charge->id, 'LAST4' => $LAST4];
                    $PAYMENT_INFO_JSON = json_encode($PAYMENT_INFO_ARRAY);
                } else {
                    $PAYMENT_STATUS = 'Failed';
                    $PAYMENT_INFO = 'Payment failed';

                    $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                    $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                    echo json_encode($RETURN_DATA);
                }
            } elseif ($PAYMENT_GATEWAY == 'Square') {
                $customer_payment_info = $db1->Execute("SELECT CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Square' AND PK_USER = " . $user_master->fields['PK_USER']);
                $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];

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

                // Create money object
                $money = new Money();
                $money->setAmount($AMOUNT_TO_PAY * 100);
                $money->setCurrency('USD');

                // Create payment request
                $paymentRequest = new CreatePaymentRequest($PAYMENT_METHOD_ID, uniqid(), $money);
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
                        $PAYMENT_INFO_JSON = json_encode($PAYMENT_INFO_ARRAY);
                    } else {
                        $PAYMENT_STATUS = 'Failed';
                        $PAYMENT_INFO = $response->getErrors()[0]->getDetail();

                        $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                        $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                        echo json_encode($RETURN_DATA);
                    }
                } catch (\Square\Exceptions\ApiException $e) {
                    $PAYMENT_STATUS = 'Failed';
                    $PAYMENT_INFO = $e->getMessage();

                    $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                    $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                    echo json_encode($RETURN_DATA);
                }
            } elseif ($PAYMENT_GATEWAY == 'Authorized.net') {
                $customer_payment_info = $db1->Execute("SELECT CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Authorized.net' AND PK_USER = " . $user_master->fields['PK_USER']);
                $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];

                $currency = "USD";
                $refID = "ref" . time();

                try {
                    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                    $merchantAuthentication->setName($AUTHORIZE_LOGIN_ID);
                    $merchantAuthentication->setTransactionKey($AUTHORIZE_TRANSACTION_KEY);

                    $profileToCharge = new AnetAPI\CustomerProfilePaymentType();
                    $profileToCharge->setCustomerProfileId($CUSTOMER_PAYMENT_ID);

                    $paymentProfile = new AnetAPI\PaymentProfileType();
                    $paymentProfile->setPaymentProfileId($PAYMENT_METHOD_ID);
                    $profileToCharge->setPaymentProfile($paymentProfile);

                    // Create order information
                    $order = new AnetAPI\OrderType();
                    $order->setDescription("Receipt# " . $RECEIPT_NUMBER_ORIGINAL);

                    // Create a transaction
                    $transactionRequestType = new AnetAPI\TransactionRequestType();
                    $transactionRequestType->setTransactionType("authCaptureTransaction");
                    $transactionRequestType->setAmount($AMOUNT_TO_PAY);
                    $transactionRequestType->setOrder($order);
                    $transactionRequestType->setProfile($profileToCharge);

                    $request = new AnetAPI\CreateTransactionRequest();
                    $request->setMerchantAuthentication($merchantAuthentication);
                    $request->setRefId($refID);
                    $request->setTransactionRequest($transactionRequestType);
                } catch (Exception $e) {
                    $PAYMENT_STATUS = 'Failed';
                    $PAYMENT_INFO = $e->getMessage();

                    $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                    $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                    echo json_encode($RETURN_DATA);
                }

                try {
                    $controller = new AnetController\CreateTransactionController($request);

                    if ($GATEWAY_MODE == 'live')
                        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                    else
                        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

                    if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                        $tresponse = $response->getTransactionResponse();

                        if ($tresponse != null && $tresponse->getMessages() != null) {
                            $PAYMENT_STATUS = 'Success';
                            $PAYMENT_INFO_ARRAY = ['CHARGE_ID' => $tresponse->getTransId(), 'LAST4' => $tresponse->getaccountNumber()];
                            $PAYMENT_INFO_JSON = json_encode($PAYMENT_INFO_ARRAY);
                        } else {
                            $PAYMENT_STATUS = 'Failed';
                            $PAYMENT_INFO = $tresponse->getErrors()[0]->getErrorCode();

                            $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                            $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                            echo json_encode($RETURN_DATA);
                        }
                    } else {
                        $PAYMENT_STATUS = 'Failed';
                        $PAYMENT_INFO = "Transaction Failed";

                        $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                        $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                        echo json_encode($RETURN_DATA);
                    }
                } catch (Exception $e) {
                    $PAYMENT_STATUS = 'Failed';
                    $PAYMENT_INFO = $e->getMessage();

                    $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                    $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                    echo json_encode($RETURN_DATA);
                }
            }

            if ($PAYMENT_STATUS == 'Success') {
                $enrollmentServiceData = $db1->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER);
                $ACTUAL_AMOUNT = $enrollment_data->fields['TOTAL_AMOUNT'];
                while (!$enrollmentServiceData->EOF) {
                    if ($enrollmentServiceData->fields['FINAL_AMOUNT'] > 0 && $ACTUAL_AMOUNT > 0) {
                        $servicePercent = ($enrollmentServiceData->fields['FINAL_AMOUNT'] * 100) / $ACTUAL_AMOUNT;
                        $serviceAmount = ($TOTAL_AMOUNT_PAID * $servicePercent) / 100;

                        $ENROLLMENT_SERVICE_UPDATE_DATA['TOTAL_AMOUNT_PAID'] = $enrollmentServiceData->fields['TOTAL_AMOUNT_PAID'] + $serviceAmount;
                        db_perform_account('DOA_ENROLLMENT_SERVICE', $ENROLLMENT_SERVICE_UPDATE_DATA, 'update', " PK_ENROLLMENT_SERVICE = " . $enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE']);

                        markAppointmentPaid($enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE']);
                    }

                    $enrollmentServiceData->MoveNext();
                }

                $sp_percent_data = $db1->Execute("SELECT SERVICE_PROVIDER_ID, SERVICE_PROVIDER_PERCENTAGE FROM DOA_ENROLLMENT_SERVICE_PROVIDER WHERE PK_ENROLLMENT_MASTER=" . $PK_ENROLLMENT_MASTER);
                while (!$sp_percent_data->EOF) {
                    $PERCENTAGE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $PERCENTAGE_DATA['SERVICE_PROVIDER_ID'] = $sp_percent_data->fields['SERVICE_PROVIDER_ID'];
                    $PERCENTAGE_DATA['PERCENTAGE_AMOUNT'] = ($AMOUNT_TO_PAY * $sp_percent_data->fields['SERVICE_PROVIDER_PERCENTAGE']) / 100;
                    db_perform_account('DOA_SERVICE_PROVIDER_AMOUNT', $PERCENTAGE_DATA, 'insert');
                    $sp_percent_data->MoveNext();
                }

                $RECEIPT_NUMBER = $RECEIPT_NUMBER_ORIGINAL;

                $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
                $PAYMENT_DATA['PK_PAYMENT_TYPE'] = 1;
                $PAYMENT_DATA['AMOUNT'] = $AMOUNT_TO_PAY;
                $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
                $PAYMENT_DATA['TYPE'] = 'Payment';
                $PAYMENT_DATA['PK_CUSTOMER_WALLET'] = 0;
                $PAYMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
                $PAYMENT_DATA['NOTE'] = 'Auto Payment via ' . $PAYMENT_GATEWAY;
                $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
                $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO_JSON;
                $PAYMENT_DATA['PAYMENT_STATUS'] = $PAYMENT_STATUS;
                $PAYMENT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
                $PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = 1;
                db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');

                $LEDGER_UPDATE_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
                $LEDGER_UPDATE_DATA['AMOUNT_REMAIN'] = 0;
                $LEDGER_UPDATE_DATA['IS_PAID'] = 1;
                db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_UPDATE_DATA, 'update', " PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");

                markEnrollmentComplete($PK_ENROLLMENT_MASTER);
            }

            echo $PAYMENT_INFO_JSON;
            echo "<br>";

            $enrollment_data->MoveNext();
        }
    } catch (mysqli_sql_exception $e) {
        echo "Connection failed: " . $e->getMessage();
    }

    echo "<br>---------------------------------<br>";


    $all_location->MoveNext();
}
