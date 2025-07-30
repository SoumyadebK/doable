<?php
require_once('../../global/config.php');
require_once("../../global/stripe-php/init.php");
global $db;
global $db_account;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

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
    } elseif ($PAYMENT_GATEWAY == 'Authorized.net') {
        $customer_payment_info = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Authorized.net' AND PK_USER = " . $PK_USER);

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($AUTHORIZE_LOGIN_ID);
        $merchantAuthentication->setTransactionKey($AUTHORIZE_TRANSACTION_KEY);

        $card_number = preg_replace('/\s+/', '', $_POST['SAVE_CARD_NUMBER']);
        $card_exp_month = $_POST['SAVE_EXPIRATION_MONTH'];
        $card_exp_year = $_POST['SAVE_EXPIRATION_YEAR'];
        $card_exp_year_month = $card_exp_year . '-' . sprintf('%02d', $card_exp_month);
        $card_cvc = $_POST['SAVE_SECURITY_CODE'];

        // Create the payment data for a credit card
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($card_number);
        $creditCard->setExpirationDate($card_exp_year_month);
        $creditCard->setCardCode($card_cvc);

        // Add the payment data to a paymentType object
        $payment = new AnetAPI\PaymentType();
        $payment->setCreditCard($creditCard);

        $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
        $paymentProfile->setCustomerType('individual');
        $paymentProfile->setPayment($payment);

        if ($customer_payment_info->RecordCount() > 0) {
            $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
        } else {
            $customerProfile = new AnetAPI\CustomerProfileType();
            $customerProfile->setMerchantCustomerId("USER_ID_" . $PK_USER . "_" . time());
            //$customerProfile->setEmail($email);
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

                $CUSTOMER_PAYMENT_DETAILS['PK_USER'] = $PK_USER;
                $CUSTOMER_PAYMENT_DETAILS['CUSTOMER_PAYMENT_ID'] = $CUSTOMER_PAYMENT_ID;
                $CUSTOMER_PAYMENT_DETAILS['PAYMENT_TYPE'] = 'Authorized.net';
                $CUSTOMER_PAYMENT_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_CUSTOMER_PAYMENT_INFO', $CUSTOMER_PAYMENT_DETAILS, 'insert');
            }
        }

        $createPaymentProfileRequest = new AnetAPI\CreateCustomerPaymentProfileRequest();
        $createPaymentProfileRequest->setMerchantAuthentication($merchantAuthentication);
        $createPaymentProfileRequest->setCustomerProfileId($CUSTOMER_PAYMENT_ID);
        $createPaymentProfileRequest->setPaymentProfile($paymentProfile);

        if ($GATEWAY_MODE == 'live')
            $createPaymentProfileRequest->setValidationMode("liveMode");
        else
            $createPaymentProfileRequest->setValidationMode("testMode");

        $controller = new AnetController\CreateCustomerPaymentProfileController($createPaymentProfileRequest);

        if ($GATEWAY_MODE == 'live')
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
        else
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        // Step 6: Handle response
        if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
            $STATUS = true;
            $MESSAGE = "Credit Card Added Successfully";
        } else {
            $STATUS = false;
            $MESSAGE = 'Error adding credit card: ' . $response->getMessages()->getMessage()[0]->getText();
        }
        $RETURN_DATA['STATUS'] = $STATUS;
        $RETURN_DATA['MESSAGE'] = $MESSAGE;
        echo json_encode($RETURN_DATA);
    } else {
        $RETURN_DATA['STATUS'] = false;
        $RETURN_DATA['MESSAGE'] = 'Unsupported payment gateway.';
        echo json_encode($RETURN_DATA);
        exit;
    }
}
