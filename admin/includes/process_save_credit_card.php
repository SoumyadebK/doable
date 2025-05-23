<?php
require_once('../../global/config.php');
require_once("../../global/stripe-php/init.php");
global $db;
global $db_account;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

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

$message = '';

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] == 'saveCreditCard') {
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
}
