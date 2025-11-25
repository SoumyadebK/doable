<?php

use Dotenv\Regex\Success;
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

$FROM = isset($_POST['FROM']) ? $_POST['FROM'] : '';
if ($FROM == 'location') {
    $PK_VALUE = $_POST['PK_LOCATION'];
} elseif ($FROM == 'corporation') {
    $PK_VALUE = $_POST['PK_CORPORATION'];
}

$payment_gateway_data = $db->Execute("SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");

$PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
$GATEWAY_MODE  = $payment_gateway_data->fields['GATEWAY_MODE'];

$SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];


if ($PAYMENT_GATEWAY == 'Stripe') {
    $stripe = new StripeClient($SECRET_KEY);
    $STRIPE_TOKEN = $_POST['stripe_token'];

    $PAYMENT_ID = '';
    $corporation_payment_info = $db->Execute("SELECT * FROM `DOA_PAYMENT_INFO` WHERE PAYMENT_TYPE = 'Stripe' AND CLASS = 'corporation' AND PK_VALUE = '$PK_VALUE'");

    if ($corporation_payment_info->RecordCount() > 0) {
        $PAYMENT_ID = $location_payment_info->fields['PAYMENT_ID'];
    } else {
        try {
            $corporation_data = $db->Execute("SELECT * FROM DOA_CORPORATION WHERE PK_CORPORATION = " . $PK_CORPORATION);
            $account_master_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = " . $corporation_data->fields['PK_ACCOUNT_MASTER']);

            $customer = $stripe->customers->create([
                'email' => $account_master_data->fields['EMAIL'],
                'name' => $corporation_data->fields['CORPORATION_NAME'],
                'phone' => $account_master_data->fields['PHONE'],
                'description' => 'Add Credit Card for Corporation ID: ' . $PK_CORPORATION,
            ]);
            $PAYMENT_ID = $customer->id;

            $PAYMENT_DETAILS['PK_VALUE'] = $PK_CORPORATION;
            $PAYMENT_DETAILS['CLASS'] = 'corporation';
            $PAYMENT_DETAILS['PAYMENT_ID'] = $PAYMENT_ID;
            $PAYMENT_DETAILS['PAYMENT_TYPE'] = 'Stripe';
            $PAYMENT_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_PAYMENT_INFO', $PAYMENT_DETAILS, 'insert');
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $STATUS = false;
            $MESSAGE = $e->getMessage();

            $RETURN_DATA['STATUS'] = $STATUS;
            $RETURN_DATA['MESSAGE'] = $MESSAGE;
            echo json_encode($RETURN_DATA);
            die();
        }
    }

    try {
        $card = $stripe->customers->createSource($PAYMENT_ID, ['source' => $STRIPE_TOKEN]);
        $stripe->customers->update($PAYMENT_ID, ['default_source' => $card->id]);

        $STATUS = true;
        $MESSAGE = "Credit Card Added Successfully";

        $RETURN_DATA['STATUS'] = $STATUS;
        $RETURN_DATA['MESSAGE'] = $MESSAGE;
        echo json_encode($RETURN_DATA);
        die();
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        $STATUS = false;
        $MESSAGE = $e->getMessage();

        $RETURN_DATA['STATUS'] = $STATUS;
        $RETURN_DATA['MESSAGE'] = $MESSAGE;
        echo json_encode($RETURN_DATA);
        die();
    }
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

    $PAYMENT_ID = '';
    $corporation_payment_info = $db->Execute("SELECT * FROM `DOA_PAYMENT_INFO` WHERE PAYMENT_TYPE = 'Square' AND CLASS = '$FROM' AND PK_VALUE = '$PK_VALUE'");
    if ($corporation_payment_info->RecordCount() > 0) {
        $PAYMENT_ID = $corporation_payment_info->fields['PAYMENT_ID'];
    } else {
        try {
            if ($FROM == 'corporation') {
                $corporation_data = $db->Execute("SELECT * FROM DOA_CORPORATION WHERE PK_CORPORATION = " . $PK_VALUE);
                $account_master_data = $db->Execute("SELECT DOA_ACCOUNT_MASTER.*, DOA_STATES.STATE_CODE FROM DOA_ACCOUNT_MASTER LEFT JOIN DOA_STATES ON DOA_ACCOUNT_MASTER.PK_STATES = DOA_STATES.PK_STATES WHERE PK_ACCOUNT_MASTER = " . $corporation_data->fields['PK_ACCOUNT_MASTER']);
                try {
                    $address = new \Square\Models\Address();
                    $address->setAddressLine1($account_master_data->fields['ADDRESS']);
                    $address->setAddressLine2($account_master_data->fields['ADDRESS_1']);
                    $address->setLocality($account_master_data->fields['CITY']);
                    $address->setAdministrativeDistrictLevel1($account_master_data->fields['STATE_CODE']);
                    $address->setPostalCode($account_master_data->fields['ZIP']);
                    $address->setCountry('US');

                    $body = new \Square\Models\CreateCustomerRequest();
                    $body->setGivenName($corporation_data->fields['CORPORATION_NAME']);
                    $body->setFamilyName($corporation_data->fields['CORPORATION_NAME']);
                    $body->setEmailAddress($account_master_data->fields['EMAIL']);
                    $body->setAddress($address);
                    $body->setPhoneNumber($account_master_data->fields['PHONE']);
                    $body->setReferenceId('N/A');
                    $body->setNote($corporation_data->fields['CORPORATION_NAME'] . " from Doable");

                    $api_response = $client->getCustomersApi()->createCustomer($body);
                } catch (\Square\Exceptions\ApiException $e) {
                    $RETURN_DATA['STATUS'] = false;
                    $RETURN_DATA['MESSAGE'] = $e->getMessage();
                    echo json_encode($RETURN_DATA);
                    die();
                }
            } elseif ($FROM == 'location') {
                $location_data = $db->Execute("SELECT DOA_LOCATION.*, DOA_COUNTRY.COUNTRY_CODE, DOA_STATES.STATE_CODE FROM DOA_LOCATION LEFT JOIN DOA_COUNTRY ON DOA_LOCATION.PK_COUNTRY = DOA_COUNTRY.PK_COUNTRY LEFT JOIN DOA_STATES ON DOA_LOCATION.PK_STATES = DOA_STATES.PK_STATES WHERE PK_LOCATION = " . $PK_VALUE);
                try {
                    $address = new \Square\Models\Address();
                    $address->setAddressLine1($location_data->fields['ADDRESS']);
                    $address->setAddressLine2($location_data->fields['ADDRESS_1']);
                    $address->setLocality($location_data->fields['CITY']);
                    $address->setAdministrativeDistrictLevel1($location_data->fields['STATE_CODE']);
                    $address->setPostalCode($location_data->fields['ZIP_CODE']);
                    $address->setCountry('US');

                    pre_r($address);

                    $body = new \Square\Models\CreateCustomerRequest();
                    $body->setGivenName($location_data->fields['LOCATION_NAME']);
                    $body->setFamilyName($location_data->fields['LOCATION_CODE']);
                    $body->setEmailAddress($location_data->fields['EMAIL']);
                    $body->setAddress($address);
                    $body->setPhoneNumber($location_data->fields['PHONE']);
                    $body->setReferenceId('N/A');
                    $body->setNote($location_data->fields['LOCATION_NAME'] . " from Doable");

                    $api_response = $client->getCustomersApi()->createCustomer($body);
                } catch (\Square\Exceptions\ApiException $e) {
                    $RETURN_DATA['STATUS'] = false;
                    $RETURN_DATA['MESSAGE'] = $e->getMessage();
                    echo json_encode($RETURN_DATA);
                    die();
                }
            }

            $PAYMENT_ID = json_decode($api_response->getBody())->customer->id;

            $PAYMENT_DETAILS['PK_VALUE'] = $PK_VALUE;
            $PAYMENT_DETAILS['CLASS'] = $FROM;
            $PAYMENT_DETAILS['PAYMENT_ID'] = $PAYMENT_ID;
            $PAYMENT_DETAILS['PAYMENT_TYPE'] = 'Square';
            $PAYMENT_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_PAYMENT_INFO', $PAYMENT_DETAILS, 'insert');
        } catch (\Square\Exceptions\ApiException $e) {
            $STATUS = false;
            $MESSAGE = $e->getMessage();

            $RETURN_DATA['STATUS'] = $STATUS;
            $RETURN_DATA['MESSAGE'] = $MESSAGE;
            echo json_encode($RETURN_DATA);
            die();
        }
    }

    $square_token = $_POST['square_token'];

    if (empty($square_token)) {
        $STATUS = false;
        $MESSAGE = "Please provide a valid credit card.";

        $RETURN_DATA['STATUS'] = $STATUS;
        $RETURN_DATA['MESSAGE'] = $MESSAGE;
        echo json_encode($RETURN_DATA);
        die();
    } else {
        try {
            $user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = " . $_SESSION['PK_USER']);
            $CARD_HOLDER_NAME = $user_data->fields['FIRST_NAME'] . ' ' . $user_data->fields['LAST_NAME'];
            // Save the new card for future use
            $card = new \Square\Models\Card();
            $card->setCardholderName($CARD_HOLDER_NAME);
            $card->setCustomerId($PAYMENT_ID);

            $body = new \Square\Models\CreateCardRequest(uniqid(), $square_token, $card);
            $api_response = $client->getCardsApi()->createCard($body);

            $STATUS = true;
            $MESSAGE = "Credit Card Added Successfully";

            $RETURN_DATA['STATUS'] = $STATUS;
            $RETURN_DATA['MESSAGE'] = $MESSAGE;
            echo json_encode($RETURN_DATA);
            die();
        } catch (\Square\Exceptions\ApiException $e) {
            $STATUS = false;
            $MESSAGE = $e->getMessage();

            $RETURN_DATA['STATUS'] = $STATUS;
            $RETURN_DATA['MESSAGE'] = $MESSAGE;
            echo json_encode($RETURN_DATA);
            die();
        }
    }
}
