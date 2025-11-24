<?php

use Square\Environment;
use Square\Models\CreatePaymentRequest;
use Square\Models\Money;
use Square\SquareClient;
use Stripe\Stripe;
use Stripe\StripeClient;

require_once('../../global/config.php');
require_once("../../global/stripe-php/init.php");
global $db;
global $db_account;

$PK_LOCATION = $_POST['PK_LOCATION'];
$PK_CORPORATION = $_POST['PK_CORPORATION'];
$PAYMENT_FROM = $_POST['PAYMENT_FROM'];
$PK_VALUE = ($PAYMENT_FROM == 'corporation') ? $PK_CORPORATION : $PK_LOCATION;
$AMOUNT = $_POST['SUBSCRIPTION_AMOUNT'];

$payment_gateway_data = $db->Execute("SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");

$PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
$GATEWAY_MODE  = $payment_gateway_data->fields['GATEWAY_MODE'];

$SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];

$LOCATION_DATA['SUBSCRIPTION_AMOUNT'] = $_POST['SUBSCRIPTION_AMOUNT'];
$LOCATION_DATA['SUBSCRIPTION_START_DATE'] = date('Y-m-d', strtotime($_POST['SUBSCRIPTION_START_DATE']));
$LOCATION_DATA['NEXT_RENEWAL_DATE'] = date('Y-m-d', strtotime($_POST['NEXT_RENEWAL_DATE']));
$LOCATION_DATA['PAYMENT_FROM'] = $_POST['PAYMENT_FROM'];
db_perform('DOA_LOCATION', $LOCATION_DATA, 'update', " PK_LOCATION = '$_POST[PK_LOCATION]'");

if ($PAYMENT_GATEWAY == 'Stripe' && (!empty($_POST['PAYMENT_METHOD_ID']) || !empty($_POST['stripe_token']))) {
    $stripe = new StripeClient($SECRET_KEY);
    $STRIPE_TOKEN = $_POST['stripe_token'];

    $PAYMENT_ID = '';
    $location_payment_info = $db->Execute("SELECT * FROM `DOA_PAYMENT_INFO` WHERE PAYMENT_TYPE = 'Stripe' AND CLASS = '$PAYMENT_FROM' AND PK_VALUE = '$PK_VALUE'");
    if ($location_payment_info->RecordCount() > 0) {
        $PAYMENT_ID = $location_payment_info->fields['PAYMENT_ID'];
    } else {
        try {
            $location_data = $db->Execute("SELECT * FROM DOA_LOCATION WHERE PK_LOCATION = " . $PK_LOCATION);

            $customer = $stripe->customers->create([
                'email' => $location_data->fields['EMAIL'],
                'name' => $location_data->fields['LOCATION_NAME'],
                'phone' => $location_data->fields['PHONE'],
                'description' => 'Add Credit Card for Location ID: ' . $PK_LOCATION,
            ]);
            $PAYMENT_ID = $customer->id;

            $LOCATION_PAYMENT_DETAILS['PK_VALUE'] = $PK_VALUE;
            $LOCATION_PAYMENT_DETAILS['CLASS'] = $PAYMENT_FROM;
            $LOCATION_PAYMENT_DETAILS['PAYMENT_ID'] = $PAYMENT_ID;
            $LOCATION_PAYMENT_DETAILS['PAYMENT_TYPE'] = 'Stripe';
            $LOCATION_PAYMENT_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_PAYMENT_INFO', $LOCATION_PAYMENT_DETAILS, 'insert');
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $PAYMENT_STATUS = 'Failed';
            $PAYMENT_INFO = $e->getMessage();
            goto FINALIZE_PAYMENT;
        }
    }

    try {
        $charge = \Stripe\Charge::create(array(
            "amount" => $AMOUNT * 100,
            "currency" => "usd",
            "description" => "Receipt# " . $PK_LOCATION . "_" . date('m/d/y'),
            "customer" => $PAYMENT_ID,
            "statement_descriptor" => "Receipt# " . $PK_LOCATION . "_" . date('m/d/y'),
        ));

        $LAST4 = $charge->payment_method_details->card->last4;

        if (isset($charge) && $charge->paid == 1) {
            $PAYMENT_STATUS = 'Success';
            $PAYMENT_INFO_ARRAY = ['CHARGE_ID' => $charge->id, 'LAST4' => $LAST4];
            $PAYMENT_INFO_JSON = json_encode($PAYMENT_INFO_ARRAY);
        } else {
            $PAYMENT_STATUS = 'Failed';
            $PAYMENT_INFO = 'Payment could not be processed. Please try again.';
            goto FINALIZE_PAYMENT;
        }
    } catch (\Stripe\Exception\CardException $e) {
        $PAYMENT_STATUS = 'Failed';
        $PAYMENT_INFO = $e->getMessage();
        goto FINALIZE_PAYMENT;
    }
} elseif ($PAYMENT_GATEWAY == 'Square' && (!empty($_POST['PAYMENT_METHOD_ID']) || !empty($_POST['square_token']))) {
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

    $location_data = $db->Execute("SELECT DOA_LOCATION.*, DOA_COUNTRY.COUNTRY_CODE, DOA_STATES.STATE_CODE FROM DOA_LOCATION LEFT JOIN DOA_COUNTRY ON DOA_LOCATION.PK_COUNTRY = DOA_COUNTRY.PK_COUNTRY LEFT JOIN DOA_STATES ON DOA_LOCATION.PK_STATES = DOA_STATES.PK_STATES WHERE PK_LOCATION = " . $PK_LOCATION);

    $PAYMENT_ID = '';
    $location_payment_info = $db->Execute("SELECT * FROM `DOA_PAYMENT_INFO` WHERE PAYMENT_TYPE = 'Square' AND CLASS = '$PAYMENT_FROM' AND PK_VALUE = '$PK_VALUE'");
    if ($location_payment_info->RecordCount() > 0) {
        $PAYMENT_ID = $location_payment_info->fields['PAYMENT_ID'];
    } else {
        try {
            $address = new \Square\Models\Address();
            $address->setAddressLine1($location_data->fields['ADDRESS']);
            $address->setAddressLine2($location_data->fields['ADDRESS_1']);
            $address->setLocality($location_data->fields['CITY']);
            $address->setAdministrativeDistrictLevel1($location_data->fields['STATE_CODE']);
            $address->setPostalCode($location_data->fields['ZIP_CODE']);
            $address->setCountry('US');

            $body = new \Square\Models\CreateCustomerRequest();
            $body->setGivenName($location_data->fields['LOCATION_NAME']);
            $body->setFamilyName($location_data->fields['LOCATION_CODE']);
            $body->setEmailAddress($location_data->fields['EMAIL']);
            $body->setAddress($address);
            $body->setPhoneNumber($location_data->fields['PHONE']);
            $body->setReferenceId('N/A');
            $body->setNote($location_data->fields['LOCATION_NAME'] . " from Doable");

            $api_response = $client->getCustomersApi()->createCustomer($body);

            $PAYMENT_ID = json_decode($api_response->getBody())->customer->id;

            $LOCATION_PAYMENT_DETAILS['PK_VALUE'] = $PK_VALUE;
            $LOCATION_PAYMENT_DETAILS['CLASS'] = $PAYMENT_FROM;
            $LOCATION_PAYMENT_DETAILS['PAYMENT_ID'] = $PAYMENT_ID;
            $LOCATION_PAYMENT_DETAILS['PAYMENT_TYPE'] = 'Square';
            $LOCATION_PAYMENT_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_PAYMENT_INFO', $LOCATION_PAYMENT_DETAILS, 'insert');
        } catch (\Square\Exceptions\ApiException $e) {
            $PAYMENT_STATUS = 'Failed';
            $PAYMENT_INFO = $e->getMessage();
            goto FINALIZE_PAYMENT;
        }
    }

    $square_token = $_POST['square_token'];
    if (!empty($_POST['PAYMENT_METHOD_ID'])) {
        $CUSTOMER_CARD_ID = $_POST['PAYMENT_METHOD_ID'];
    } else {
        try {
            // Save the new card for future use
            $card = new \Square\Models\Card();
            $card->setCardholderName($location_data->fields['LOCATION_NAME']);
            $card->setCustomerId($PAYMENT_ID);

            $body = new \Square\Models\CreateCardRequest(uniqid(), $square_token, $card);

            $api_response = $client->getCardsApi()->createCard($body);
            $result = $api_response->getResult();
            if ($api_response->isError()) {
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = 'Error saving card: ' . $api_response->getErrors()[0]->getDetail();
                goto FINALIZE_PAYMENT;
            } else {
                $CUSTOMER_CARD_ID = $result->getCard()->getId();
            }
        } catch (\Square\Exceptions\ApiException $e) {
            $PAYMENT_STATUS = 'Failed';
            $PAYMENT_INFO = $e->getMessage();
            goto FINALIZE_PAYMENT;
        }
    }

    // Create money object
    $money = new Money();
    $money->setAmount($AMOUNT * 100);
    $money->setCurrency('USD');

    // Create payment request
    $paymentRequest = new CreatePaymentRequest($CUSTOMER_CARD_ID, uniqid(), $money);
    $paymentRequest->setCustomerId($PAYMENT_ID);

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
            goto FINALIZE_PAYMENT;
        }
    } catch (\Square\Exceptions\ApiException $e) {
        $PAYMENT_STATUS = 'Failed';
        $PAYMENT_INFO = $e->getMessage();
        goto FINALIZE_PAYMENT;
    }
}

FINALIZE_PAYMENT:
if (!empty($_POST['PAYMENT_METHOD_ID']) || !empty($_POST['square_token'])) {
    $PAYMENT_DETAILS['PK_LOCATION'] = $PK_LOCATION;
    $PAYMENT_DETAILS['PK_CORPORATION'] = $PK_CORPORATION;
    $PAYMENT_DETAILS['PAYMENT_FROM'] = $PAYMENT_FROM;
    $PAYMENT_DETAILS['AMOUNT'] = $AMOUNT;
    $PAYMENT_DETAILS['PAYMENT_STATUS'] = $PAYMENT_STATUS;
    $PAYMENT_DETAILS['PAYMENT_INFO'] = ($PAYMENT_STATUS == 'Success') ? $PAYMENT_INFO_JSON : $PAYMENT_INFO;
    $PAYMENT_DETAILS['DATE_TIME'] = date('Y-m-d H:i');
    db_perform('DOA_PAYMENT_DETAILS', $PAYMENT_DETAILS, 'insert');
} else {
    $PAYMENT_STATUS = 'Success';
    $PAYMENT_INFO_JSON = 'Saved';
}

$RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
$RETURN_DATA['PAYMENT_INFO'] = ($PAYMENT_STATUS == 'Success') ? $PAYMENT_INFO_JSON : $PAYMENT_INFO;
echo json_encode($RETURN_DATA);
die();
